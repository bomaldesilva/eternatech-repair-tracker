<?php

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');
requireRole('technician', '../index.php');

$page_title = 'Technician Dashboard - ETERNATECH REPAIRS';
$assigned_tasks = [];
$stats = ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0];
$error_message = '';

$currentUser = getCurrentUser();
$technicianId = getCurrentUserId();

try {
    if (!$technicianId) {
        throw new Exception("No technician ID found in session");
    }
    //checkDBCon
    if (!$pdo) {
        throw new Exception("Database connection not available");
    }

    $sql = "SELECT ta.*, r.ProblemDescription, r.Priority, r.DateSubmitted, r.Status as RequestStatus
            FROM TaskAssignment ta
            INNER JOIN Request r ON ta.RequestId = r.RequestId  
            WHERE ta.TechnicianId = ?
            ORDER BY ta.AssignmentId DESC";
    
    $stmt = $pdo->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL statement: " . implode(", ", $pdo->errorInfo()));
    }
    
    $result = $stmt->execute([$technicianId]);
    if (!$result) {
        throw new Exception("Failed to execute SQL statement: " . implode(", ", $stmt->errorInfo()));
    }
    
    $basic_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($basic_tasks as $task) {
        try {
            //getCustInfo
            $custSql = "SELECT CustName, Email FROM Customer WHERE CustID = (SELECT CustID FROM Request WHERE RequestId = ?)";
            $custStmt = $pdo->prepare($custSql);
            if ($custStmt) {
                $custStmt->execute([$task['RequestId']]);
                $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $customer = ['CustName' => 'Unknown', 'Email' => 'Unknown'];
            }
            //getDeviceInfo
            $deviceSql = "SELECT dt.Name as DeviceTypeName FROM DeviceType dt 
                          INNER JOIN Request r ON dt.DeviceId = r.DeviceId 
                          WHERE r.RequestId = ?";
            $deviceStmt = $pdo->prepare($deviceSql);
            if ($deviceStmt) {
                $deviceStmt->execute([$task['RequestId']]);
                $device = $deviceStmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $device = ['DeviceTypeName' => 'Unknown'];
            }
            //combineAll
            $full_task = array_merge($task, [
                'CustName' => $customer['CustName'] ?? 'Unknown Customer',
                'Email' => $customer['Email'] ?? 'No Email',
                'DeviceTypeName' => $device['DeviceTypeName'] ?? 'Unknown Device',
                'TaskStatus' => $task['Status']
            ]);
            
            $assigned_tasks[] = $full_task;
        } catch (Exception $e) {
            error_log("Error processing task {$task['RequestId']}: " . $e->getMessage());
            //AddTheTaskWith basic info
            $assigned_tasks[] = array_merge($task, [
                'CustName' => 'Unknown Customer',
                'Email' => 'No Email',
                'DeviceTypeName' => 'Unknown Device',
                'TaskStatus' => $task['Status']
            ]);
        }
    }
    
    //Calculatestats
    $stats['total'] = count($assigned_tasks);
    foreach ($assigned_tasks as $task) {
        $status = strtolower($task['TaskStatus'] ?? 'pending');
        if (strpos($status, 'finished') !== false || strpos($status, 'completed') !== false) {
            $stats['completed']++;
        } elseif (strpos($status, 'ongoing') !== false || strpos($status, 'progress') !== false) {
            $stats['in_progress']++;
        } else {
            $stats['pending']++;
        }
    }
    
    error_log("Technician Dashboard: Found " . count($assigned_tasks) . " tasks for TechnicianId $technicianId");
    
} catch (Exception $e) {
    error_log('Technician dashboard error: ' . $e->getMessage());
    $error_message = 'Unable to load dashboard data: ' . $e->getMessage();
}

//import header
include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Welcome, <?php echo htmlspecialchars($currentUser['name']); ?>!</h2>
        <div>
            <a href="update_status.php" class="btn" style="margin-right: 10px;">Update Status</a>
            <a href="technician_dashboard.php" class="btn btn-outline">Refresh</a>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="msg error">
        <?php echo htmlspecialchars($error_message); ?>
        <br><small>Debug: TechnicianId = <?php echo $technicianId; ?></small>
    </div>
    <?php endif; ?>

    <!-- Stats Overview -->
    <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
        <div class="data-card">
            <div class="data-header">
                <h4 class="data-title">Total Assigned</h4>
            </div>
            <div style="font-size: 2em; font-weight: bold; color: var(--accent); text-align: center;">
                <?php echo $stats['total']; ?>
            </div>
        </div>
        
        <div class="data-card">
            <div class="data-header">
                <h4 class="data-title">Pending</h4>
            </div>
            <div style="font-size: 2em; font-weight: bold; color: #ff9800; text-align: center;">
                <?php echo $stats['pending']; ?>
            </div>
        </div>
        
        <div class="data-card">
            <div class="data-header">
                <h4 class="data-title">In Progress</h4>
            </div>
            <div style="font-size: 2em; font-weight: bold; color: #2196f3; text-align: center;">
                <?php echo $stats['in_progress']; ?>
            </div>
        </div>
        
        <div class="data-card">
            <div class="data-header">
                <h4 class="data-title">Completed</h4>
            </div>
            <div style="font-size: 2em; font-weight: bold; color: #4caf50; text-align: center;">
                <?php echo $stats['completed']; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Quick Actions</h3>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title">Update Task Status</h4>
                </div>
                <p>Update the progress of your assigned repair tasks.</p>
                <div class="data-actions">
                    <a href="update_status.php" class="btn btn-small">Update Status</a>
                </div>
            </div>
            
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title">Add Parts Used</h4>
                </div>
                <p>Record parts and components used in repairs.</p>
                <div class="data-actions">
                    <a href="update_status.php" class="btn btn-small">Add Parts</a>
                </div>
            </div>
            
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title">Work Notes</h4>
                </div>
                <p>Add technical notes and repair documentation.</p>
                <div class="data-actions">
                    <a href="update_status.php" class="btn btn-small btn-secondary">Add Notes</a>
                </div>
            </div>
        </div>
    </div>

    <h3>My Assigned Tasks</h3>
    
    <?php if (empty($assigned_tasks)): ?>
    <div class="card">
        <div class="empty-state">
            <h3>No Assigned Tasks</h3>
            <p>You don't have any assigned repair tasks yet.</p>
            <p>TechnicianId: <?php echo $technicianId; ?></p>
            <p>Check back later or contact your administrator.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="dashboard-grid">
        <?php foreach ($assigned_tasks as $task): ?>
        <div class="data-card">
            <div class="data-header">
                <h4 class="data-title">Request #<?php echo htmlspecialchars($task['RequestId']); ?></h4>
                <span class="data-status status-<?php echo strtolower(str_replace(' ', '_', $task['TaskStatus'] ?? 'pending')); ?>">
                    <?php echo htmlspecialchars($task['TaskStatus'] ?? 'Pending'); ?>
                </span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Customer:</span>
                <span class="field-value"><?php echo htmlspecialchars($task['CustName']); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Email:</span>
                <span class="field-value"><?php echo htmlspecialchars($task['Email']); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Device Type:</span>
                <span class="field-value"><?php echo htmlspecialchars($task['DeviceTypeName'] ?? 'N/A'); ?></span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Problem:</span>
                <span class="field-value" title="<?php echo htmlspecialchars($task['ProblemDescription']); ?>">
                    <?php echo htmlspecialchars(substr($task['ProblemDescription'], 0, 50)) . (strlen($task['ProblemDescription']) > 50 ? '...' : ''); ?>
                </span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Priority:</span>
                <span class="field-value priority-<?php echo strtolower($task['Priority'] ?? 'medium'); ?>">
                    <?php echo ucfirst($task['Priority'] ?? 'Medium'); ?>
                </span>
            </div>
            
            <div class="data-field">
                <span class="field-label">Assigned:</span>
                <span class="field-value"><?php echo date('M j, Y g:i A', strtotime($task['DateSubmitted'])); ?></span>
            </div>
            
            <?php if (!empty($task['AssignmentData'])): ?>
            <div class="data-field">
                <span class="field-label">Assignment Details:</span>
                <span class="field-value" title="<?php echo htmlspecialchars($task['AssignmentData']); ?>">
                    <?php echo htmlspecialchars(substr($task['AssignmentData'], 0, 50)) . (strlen($task['AssignmentData']) > 50 ? '...' : ''); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($task['FinalCost']) && $task['FinalCost'] > 0): ?>
            <div class="data-field">
                <span class="field-label">Cost:</span>
                <span class="field-value">RS. <?php echo number_format($task['FinalCost'], 2); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="data-actions">
                <a href="update_status.php?request_id=<?php echo urlencode($task['RequestId']); ?>" class="btn btn-small">Update</a>
                <a href="../track_status.php?request_id=<?php echo urlencode($task['RequestId']); ?>" class="btn btn-small btn-secondary">Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="technician_dashboard.php" class="btn btn-outline">View All Tasks</a>
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
