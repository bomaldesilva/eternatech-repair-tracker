<?php

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');
requireRole('customer', '../index.php');

$page_title = 'Customer Dashboard - ETERNATECH REPAIRS';
$requests = [];
$stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];

$currentUser = getCurrentUser();
$userId = getCurrentUserId();

try {
    //Select*FromReq
    $sql = "SELECT r.*, dt.Name as DeviceTypeName
            FROM Request r
            LEFT JOIN DeviceType dt ON r.DeviceId = dt.DeviceId
            WHERE r.CustID = :userId
            ORDER BY r.DateSubmitted DESC";
    
    $stmt = executeQuery($pdo, $sql, [':userId' => $userId]);
    if ($stmt) {
        $requests = $stmt->fetchAll();
        $stats['total'] = count($requests);
        foreach ($requests as $request) {
            $status = strtolower($request['Status'] ?? 'pending');
            if (strpos($status, 'pending') !== false) {
                $stats['pending']++;
            } elseif (strpos($status, 'progress') !== false || strpos($status, 'assigned') !== false) {
                $stats['in_progress']++;
            } elseif (strpos($status, 'completed') !== false) {
                $stats['completed']++;
            }
        }
    }
} catch (Exception $e) {
    error_log('Customer dashboard error: ' . $e->getMessage());
    setMessage('Unable to load dashboard data.', 'error');
}

include '../includes/header.php';
?>

<style>
.page-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: flex-start !important;
    margin-bottom: 30px !important;
    padding-bottom: 20px !important;
    border-bottom: 1px solid var(--border) !important;
}

.stats-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) !important;
    gap: 20px !important;
    margin-bottom: 30px !important;
}

.stat-card {
    background: var(--card) !important;
    border: 1px solid var(--border) !important;
    border-radius: 12px !important;
    padding: 24px !important;
    text-align: center !important;
}

.requests-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)) !important;
    gap: 20px !important;
}

.request-card {
    background: var(--card) !important;
    border: 1px solid var(--border) !important;
    border-radius: 12px !important;
    overflow: hidden !important;
}
</style>

<main class="container">
    <div class="page-header">
        <div class="header-content">
            <h1>Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!</h1>
            <p class="header-subtitle">Track and manage your repair requests</p>
        </div>
        <div class="header-actions">
            <a href="submit_request.php" class="btn">Submit New Request</a>
            <a href="customer_bills.php" class="btn" style="background: #FF9800;">My Bills</a>
            <a href="help.php" class="btn btn-outline">Help & Support</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Requests</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number pending"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number progress"><?php echo $stats['in_progress']; ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number completed"><?php echo $stats['completed']; ?></div>
            <div class="stat-label">Completed</div>
        </div>
    </div>

    <div class="section-header">
                <h2>Welcome to Your Dashboard</h2>
    </div>
    
    <?php if (empty($requests)): ?>
    <div class="empty-state-card">
        <div class="empty-icon">📱</div>
        <h3>No Repair Requests Yet</h3>
        <p>You haven't submitted any repair requests yet. Get started by submitting your first request!</p>
        <a href="submit_request.php" class="btn">Submit Your First Request</a>
    </div>
    <?php else: ?>
    <div class="requests-grid">
        <?php foreach ($requests as $request): ?>
        <div class="request-card">
            <div class="request-header">
                <div class="request-id">Request #<?php echo htmlspecialchars($request['RequestId']); ?></div>
                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '_', $request['Status'] ?? 'pending')); ?>">
                    <?php echo htmlspecialchars($request['Status'] ?? 'Pending'); ?>
                </span>
            </div>
            
            <div class="request-body">
                <div class="request-field">
                    <span class="field-label">Device Type:</span>
                    <span class="field-value"><?php echo htmlspecialchars($request['DeviceTypeName'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="request-field">
                    <span class="field-label">Problem:</span>
                    <span class="field-value" title="<?php echo htmlspecialchars($request['ProblemDescription']); ?>">
                        <?php echo htmlspecialchars(substr($request['ProblemDescription'], 0, 60)) . (strlen($request['ProblemDescription']) > 60 ? '...' : ''); ?>
                    </span>
                </div>
                
                <div class="request-field">
                    <span class="field-label">Submitted:</span>
                    <span class="field-value"><?php echo date('M j, Y g:i A', strtotime($request['DateSubmitted'])); ?></span>
                </div>
                
                <?php if (!empty($request['TechnicianName'])): ?>
                <div class="request-field">
                    <span class="field-label">Technician:</span>
                    <span class="field-value"><?php echo htmlspecialchars($request['TechnicianName']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($request['FinalCost']) && $request['FinalCost'] > 0): ?>
                <div class="request-field">
                    <span class="field-label">Cost:</span>
                    <span class="field-value cost">RS. <?php echo number_format($request['FinalCost'], 2); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="request-actions">
                <form method="POST" action="../track_status.php" style="margin: 0;">
                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['RequestId']); ?>">
                    <button type="submit" class="btn btn-outline btn-small">View Details</button>
                </form>
                
                <?php if (empty($request['TechnicianName']) && $request['Status'] === 'Pending'): ?>
                <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($request)); ?>)" class="btn btn-secondary btn-small">Edit</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="hideEditModal()">&times;</span>
        <h3>Edit Request</h3>
        <form id="editForm" method="POST" action="submit_request.php">
            <input type="hidden" id="editRequestId" name="request_id">
            
            <label>
                <span>Problem Description</span>
                <textarea id="editProblem" name="problem_description" rows="4" required></textarea>
            </label>
            
            <button type="submit" class="btn">Update Request</button>
        </form>
    </div>
</div>

<script>
function showEditModal(request) {
    document.getElementById('editRequestId').value = request.RequestId;
    document.getElementById('editProblem').value = request.ProblemDescription;
    document.getElementById('editModal').style.display = 'block';
}

function hideEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        hideEditModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
