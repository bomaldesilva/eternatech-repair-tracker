<?php

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../admin_login.php');
requireRole('admin', '../index.php');

$page_title = 'Assign Tasks - ETERNATECH REPAIRS';
$error = '';
$success = '';
$unassigned_requests = [];
$technicians = [];
$request_id = $_GET['request_id'] ?? '';

try {
    $sql = "SELECT r.*, c.CustName, c.Email, dt.Name as DeviceTypeName
            FROM Request r
            LEFT JOIN Customer c ON r.CustID = c.CustID
            LEFT JOIN DeviceType dt ON r.DeviceId = dt.DeviceId
            LEFT JOIN TaskAssignment ta ON r.RequestId = ta.RequestId
            WHERE ta.RequestId IS NULL AND r.Status = 'Pending'
            ORDER BY r.DateSubmitted ASC";
    
    $stmt = executeQuery($pdo, $sql);
    if ($stmt) {
        $unassigned_requests = $stmt->fetchAll();
    }
    $sql = "SELECT TechnicianId, Name, Email, Speciality FROM Technician ORDER BY Name";
    $stmt = executeQuery($pdo, $sql);
    if ($stmt) {
        $technicians = $stmt->fetchAll();
    }
} catch (Exception $e) {
    error_log('Assign task error: ' . $e->getMessage());
    $error = 'Unable to load assignment data.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = sanitizeInput($_POST['request_id'] ?? '');
    $technicianId = sanitizeInput($_POST['technician_id'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (empty($requestId) || empty($technicianId)) {
        $error = 'Please select both a request and a technician.';
    } else {
        try {
            // Check if request is already assigned
            $checkSql = "SELECT AssignmentId FROM TaskAssignment WHERE RequestId = :requestId";
            $checkStmt = executeQuery($pdo, $checkSql, [':requestId' => $requestId]);
            
            if ($checkStmt && $checkStmt->fetch()) {
                $error = 'This request has already been assigned.';
            } else {
                // Start transaction
                $pdo->beginTransaction();
                
                // Insert task assignment
                $assignSql = "INSERT INTO TaskAssignment (RequestId, TechnicianId, AssignmentData, Status) 
                             VALUES (:requestId, :technicianId, :assignmentData, :status)";
                
                error_log("Task Assignment Debug - RequestId: " . $requestId);
                error_log("Task Assignment Debug - TechnicianId: " . $technicianId);
                error_log("Task Assignment Debug - Notes: " . $notes);
                
                $assignStmt = executeQuery($pdo, $assignSql, [
                    ':requestId' => $requestId,
                    ':technicianId' => $technicianId,
                    ':assignmentData' => $notes,
                    ':status' => 'Assigned'
                ]);
                
                //update status
                $updateSql = "UPDATE Request SET Status = 'in_progress' WHERE RequestId = :requestId";
                $updateStmt = executeQuery($pdo, $updateSql, [':requestId' => $requestId]);
                
                if ($assignStmt && $updateStmt) {
                    $pdo->commit();
                    
                    //technician name for success message
                    $techName = '';
                    foreach ($technicians as $tech) {
                        if ($tech['TechnicianId'] == $technicianId) {
                            $techName = $tech['Name'];
                            break;
                        }
                    }
                    
                    $success = "Request #{$requestId} has been successfully assigned to {$techName}!";
                    
                    //Reload
                    $stmt = executeQuery($pdo, "SELECT r.*, c.CustName, c.Email, dt.Name as DeviceTypeName
                                                FROM Request r
                                                LEFT JOIN Customer c ON r.CustID = c.CustID
                                                LEFT JOIN DeviceType dt ON r.DeviceId = dt.DeviceId
                                                LEFT JOIN TaskAssignment ta ON r.RequestId = ta.RequestId
                                                WHERE ta.RequestId IS NULL AND r.Status = 'Pending'
                                                ORDER BY r.DateSubmitted ASC");
                    if ($stmt) {
                        $unassigned_requests = $stmt->fetchAll();
                    }
                } else {
                    $pdo->rollBack();
                    $error = 'Failed to assign task. Please try again.';
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Task assignment error: ' . $e->getMessage());
            $error = 'Failed to assign task. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Assign Tasks</h2>
        <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (empty($technicians)): ?>
    <div class="card">
        <div class="empty-state">
            <h3>No Technicians Available</h3>
            <p>You need to add technicians before you can assign tasks.</p>
            <a href="add_staff.php" class="btn">Add Technician</a>
        </div>
    </div>
    <?php elseif (empty($unassigned_requests)): ?>
    <div class="card">
        <div class="empty-state">
            <h3>No Unassigned Requests</h3>
            <p>All pending requests have been assigned to technicians.</p>
            <a href="admin_dashboard.php" class="btn">View Dashboard</a>
        </div>
    </div>
    <?php else: ?>
    
    <div class="card" style="margin-bottom: 30px;">
        <h3>Quick Assignment</h3>
        <form method="POST" onsubmit="return validateAssignForm(this)">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <label>
                    <span>Select Request *</span>
                    <select name="request_id" required>
                        <option value="">Choose a request to assign</option>
                        <?php foreach ($unassigned_requests as $request): ?>
                            <option value="<?php echo htmlspecialchars($request['RequestId']); ?>"
                                    <?php echo $request_id === $request['RequestId'] ? 'selected' : ''; ?>>
                                #<?php echo htmlspecialchars($request['RequestId']); ?> - 
                                <?php echo htmlspecialchars($request['CustName']); ?> - 
                                <?php echo htmlspecialchars($request['DeviceTypeName'] ?? 'Unknown Device'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <label>
                    <span>Assign to Technician *</span>
                    <select name="technician_id" required>
                        <option value="">Choose a technician</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo htmlspecialchars($tech['TechnicianId']); ?>">
                                <?php echo htmlspecialchars($tech['Name']); ?>
                                <?php if (!empty($tech['Speciality'])): ?>
                                    - <?php echo htmlspecialchars($tech['Speciality']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            
            <label>
                <span>Assignment Notes (Optional)</span>
                <textarea name="notes" rows="3" placeholder="Add any special instructions or notes for the technician..."></textarea>
            </label>
            
            <button type="submit" class="btn" id="assignBtn" data-original-text="Assign Task">Assign Task</button>
        </form>
    </div>

    <!-- Unassigne Requests List -->
    <h3>Unassigned Requests (<?php echo count($unassigned_requests); ?>)</h3>
    
    <div class="dashboard-grid">
        <?php foreach ($unassigned_requests as $request): ?>
        <div class="data-card">
            <div class="data-header">
                <h4 class="data-title">Request #<?php echo htmlspecialchars($request['RequestId']); ?></h4>
                <span class="data-status status-pending">Unassigned</span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Customer:</span>
                <span class="field-value"><?php echo htmlspecialchars($request['CustName']); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Email:</span>
                <span class="field-value"><?php echo htmlspecialchars($request['Email']); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Device Type:</span>
                <span class="field-value"><?php echo htmlspecialchars($request['DeviceTypeName'] ?? 'N/A'); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Problem:</span>
                <span class="field-value" title="<?php echo htmlspecialchars($request['ProblemDescription']); ?>">
                    <?php echo htmlspecialchars(substr($request['ProblemDescription'], 0, 100)) . (strlen($request['ProblemDescription']) > 100 ? '...' : ''); ?>
                </span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Priority:</span>
                <span class="field-value priority-<?php echo strtolower($request['Priority'] ?? 'medium'); ?>">
                    <?php echo ucfirst($request['Priority'] ?? 'Medium'); ?>
                </span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Submitted:</span>
                <span class="field-value"><?php echo date('M j, Y g:i A', strtotime($request['DateSubmitted'])); ?></span>
            </div>
            
            <div class="data-actions">
                <button onclick="quickAssign('<?php echo htmlspecialchars($request['RequestId']); ?>')" class="btn btn-small">Quick Assign</button>
                <form method="POST" action="../track_status.php" style="margin: 0; display: inline;">
                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['RequestId']); ?>">
                    <button type="submit" class="btn btn-small btn-secondary">View Details</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Available Tech -->
    <?php if (!empty($technicians)): ?>
    <div class="card" style="margin-top: 30px;">
        <h3>Available Technicians</h3>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <?php foreach ($technicians as $tech): ?>
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title"><?php echo htmlspecialchars($tech['Name']); ?></h4>
                </div>
                
                <div class="data-field">
                    <span class="field-label">Email:</span>
                    <span class="field-value"><?php echo htmlspecialchars($tech['Email']); ?></span>
                </div>
                
                <?php if (!empty($tech['Speciality'])): ?>
                <div class="data-field">
                    <span class="field-label">Specialization:</span>
                    <span class="field-value"><?php echo htmlspecialchars($tech['Speciality']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
function validateAssignForm(form) {
    const assignBtn = document.getElementById('assignBtn');
    
    if (!validateForm(form)) {
        return false;
    }
    
    setLoading(assignBtn, true);
    return true;
}

function quickAssign(requestId) {
   
    document.querySelector('select[name="request_id"]').value = requestId;
    
    document.querySelector('form').scrollIntoView({ behavior: 'smooth' });
    
    setTimeout(() => {
        document.querySelector('select[name="technician_id"]').focus();
    }, 500);
}
</script>

<?php include '../includes/footer.php'; ?>
