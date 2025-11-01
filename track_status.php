<?php
//session and database
require_once 'includes/session.php';
require_once 'config/database.php';

$page_title = 'Track Repair Status - ETERNATECH REPAIRS';
$request = null;
$error = '';
$search_performed = false;

//Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = sanitizeInput($_POST['request_id'] ?? '');
    $search_performed = true;
    
    if (empty($requestId)) {
        $error = 'Please enter a request ID.';
    } else {
        try {
            $sql = "SELECT r.*, c.CustName, c.Email as CustomerEmail, 
                          dt.Name as DeviceTypeName
                    FROM Request r
                    LEFT JOIN Customer c ON r.CustID = c.CustID
                    LEFT JOIN DeviceType dt ON r.DeviceId = dt.DeviceId
                    WHERE r.RequestId = :requestId";
            
            $stmt = executeQuery($pdo, $sql, [':requestId' => $requestId]);
            
            if ($stmt && $request = $stmt->fetch()) {
                //If user is logged in
                if (isLoggedIn() && hasRole('customer')) {
                    $currentUserId = getCurrentUserId();
                    if ($request['CustID'] != $currentUserId) {
                        $error = 'You can only track your own requests.';
                        $request = null;
                    }
                }
            } else {
                $error = 'Request not found. Please check your request ID.';
            }
        } catch (Exception $e) {
            error_log('Track status error: ' . $e->getMessage());
            $error = 'Unable to retrieve request information. Please try again.';
        }
    }
}

//If user is logged in as customerget their requestss
$recent_requests = [];
if (isLoggedIn() && hasRole('customer')) {
    try {
        $userId = getCurrentUserId();
        $sql = "SELECT r.RequestId, r.ProblemDescription, r.Status, r.DateSubmitted, dt.Name as DeviceTypeName
                FROM Request r
                LEFT JOIN DeviceType dt ON r.DeviceId = dt.DeviceId
                WHERE r.CustID = :userId
                ORDER BY r.DateSubmitted DESC
                LIMIT 5";
        
        $stmt = executeQuery($pdo, $sql, [':userId' => $userId]);
        if ($stmt) {
            $recent_requests = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        error_log('Recent requests error: ' . $e->getMessage());
    }
}

//import header
include 'includes/header.php';
?>

<main class="container narrow">
    <h2>Track Repair Status</h2>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" class="card" onsubmit="return validateTrackForm(this)">
        <label>
            <span>Request ID</span>
            <input type="text" name="request_id" required value="<?php echo htmlspecialchars($_POST['request_id'] ?? ''); ?>"
                   placeholder="Enter your request ID (e.g., TKT-20250903-ABC123)">
        </label>
        
        <button type="submit" class="btn" id="trackBtn" data-original-text="Track Status">Track Status</button>
    </form>

    <?php if ($request): ?>
    <div class="card">
        <h3>Request Details</h3>
        <div class="data-card">
            <div class="data-header">
                <h4 class="data-title">Request #<?php echo htmlspecialchars($request['RequestId']); ?></h4>
                <span class="data-status status-<?php echo strtolower(str_replace(' ', '_', $request['Status'] ?? 'pending')); ?>">
                    <?php echo htmlspecialchars($request['Status'] ?? 'Pending'); ?>
                </span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Customer:</span>
                <span class="field-value"><?php echo htmlspecialchars($request['CustName']); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Device Type:</span>
                <span class="field-value"><?php echo htmlspecialchars($request['DeviceTypeName'] ?? 'N/A'); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Problem Description:</span>
                <span class="field-value"><?php echo htmlspecialchars($request['ProblemDescription']); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Date Submitted:</span>
                <span class="field-value"><?php echo date('M j, Y g:i A', strtotime($request['DateSubmitted'])); ?></span>
            </div>
            
            <?php if (!empty($request['TechnicianName'])): ?>
            <div class="data-field">
                <span class="field-label">Assigned Technician:</span>
                <span class="field-value"><?php echo htmlspecialchars($request['TechnicianName']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($request['FinalCost']) && $request['FinalCost'] > 0): ?>
            <div class="data-field">
                <span class="field-label">Final Cost:</span>
                <span class="field-value">$<?php echo number_format($request['FinalCost'], 2); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($request['OrderStatus'])): ?>
            <div class="data-field">
                <span class="field-label">Order Status:</span>
                <span class="field-value"><?php echo htmlspecialchars($request['OrderStatus']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif ($search_performed && empty($error)): ?>
    <div class="card">
        <div class="empty-state">
            <h3>No Request Found</h3>
            <p>Please check your request ID and try again.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($recent_requests)): ?>
    <div class="card">
        <h3>Your Recent Requests</h3>
        <div class="dashboard-grid">
            <?php foreach ($recent_requests as $req): ?>
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title">Request #<?php echo htmlspecialchars($req['RequestId']); ?></h4>
                    <span class="data-status status-<?php echo strtolower(str_replace(' ', '_', $req['Status'] ?? 'pending')); ?>">
                        <?php echo htmlspecialchars($req['Status'] ?? 'Pending'); ?>
                    </span>
                </div>
                
                <div class="data-field">
                    <span class="field-label">Device:</span>
                    <span class="field-value"><?php echo htmlspecialchars($req['DeviceTypeName'] ?? 'N/A'); ?></span>
                </div>
                
                <div class="data-field">
                    <span class="field-label">Problem:</span>
                    <span class="field-value"><?php echo htmlspecialchars(substr($req['ProblemDescription'], 0, 50)) . (strlen($req['ProblemDescription']) > 50 ? '...' : ''); ?></span>
                </div>
                
                <div class="data-field">
                    <span class="field-label">Submitted:</span>
                    <span class="field-value"><?php echo date('M j, Y', strtotime($req['DateSubmitted'])); ?></span>
                </div>
                
                <div class="data-actions">
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($req['RequestId']); ?>">
                        <button type="submit" class="btn btn-small">View Details</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!isLoggedIn()): ?>
    <div class="card">
        <h3>Need Help?</h3>
        <p>If you're a registered customer, <a href="login.php">log in</a> to view all your repair requests and get detailed status updates.</p>
        <p>New to our service? <a href="register.php">Create an account</a> to submit repair requests online.</p>
    </div>
    <?php endif; ?>
</main>

<script>
function validateTrackForm(form) {
    const trackBtn = document.getElementById('trackBtn');
    
    if (!validateForm(form)) {
        return false;
    }
    
    setLoading(trackBtn, true);
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>
