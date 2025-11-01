<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../admin_login.php');
requireRole('admin', '../index.php');

$page_title = 'Admin Dashboard - ETERNATECH REPAIRS';

$stats = [];
$requests_by_status = [
    'pending' => [],
    'approved' => [],
    'rejected' => [],
    'in_progress' => [],
    'completed' => []
];

$currentUser = getCurrentUser();

try {
    $sql = "SELECT r.*, c.CustName, c.Email as CustomerEmail, c.Address,
                   dt.Name as DeviceTypeName, r.DateSubmitted,
                   DATEDIFF(CURDATE(), DATE(r.DateSubmitted)) as DaysOld
            FROM Request r
            LEFT JOIN Customer c ON r.CustID = c.CustID
            LEFT JOIN DeviceType dt ON r.DeviceId = dt.DeviceId
            ORDER BY r.DateSubmitted DESC";
    
    $stmt = executeQuery($pdo, $sql);
    if ($stmt) {
        $all_requests = $stmt->fetchAll();
        //requests by status
        foreach ($all_requests as $request) {
            $status = strtolower($request['Status']);
            if (isset($requests_by_status[$status])) {
                $requests_by_status[$status][] = $request;
            }
        }
        $stats = [
            'total' => count($all_requests),
            'pending' => count($requests_by_status['pending']),
            'approved' => count($requests_by_status['approved']),
            'rejected' => count($requests_by_status['rejected']),
            'in_progress' => count($requests_by_status['in_progress']),
            'completed' => count($requests_by_status['completed']),
            // Add alternative key names for compatibility
            'total_requests' => count($all_requests),
            'pending_requests' => count($requests_by_status['pending']),
            'completed_requests' => count($requests_by_status['completed']),
            'unassigned_requests' => count($requests_by_status['pending']) // Using pending as unassigned
        ];
        
    }
    
    //Get customer count
    $sql = "SELECT COUNT(*) as count FROM Customer";
    $stmt = executeQuery($pdo, $sql);
    if ($stmt && $row = $stmt->fetch()) {
        $stats['customers'] = $row['count'];
        $stats['total_customers'] = $row['count']; 
    }
    
    $sql = "SELECT COUNT(*) as count FROM Technician";
    $stmt = executeQuery($pdo, $sql);
    if ($stmt && $row = $stmt->fetch()) {
        $stats['technicians'] = $row['count'];
        $stats['total_technicians'] = $row['count'];
    }

} catch (Exception $e) {
    error_log('Admin dashboard error: ' . $e->getMessage());
}

//import header
include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2>Admin Dashboard</h2>
            <p style="color: var(--muted); margin: 5px 0;">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?></p>
        </div>
        <div>
            <a href="manage_parts.php" class="btn btn-small" style="background: #4CAF50;">Manage Parts</a>
            <a href="manage_bills.php" class="btn btn-small" style="background: #FF9800;">Manage Bills</a>
            <a href="contact_messages.php" class="btn btn-small" style="background: #2196F3;">Contact Messages</a>
            <a href="add_staff.php" class="btn btn-small">Add Staff</a>
            <a href="manage_users.php" class="btn btn-small" style="background: #9C27B0;">Manage Users</a>
            <a href="assign_task.php" class="btn btn-small btn-outline">Assign Tasks</a>
        </div>
    </div>

    <!-- Parts Inventory Overview -->
    <?php
    try {
        // Get parts statistics
        $partsStatsSql = "SELECT 
            COUNT(*) as total_parts,
            SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) as active_parts,
            SUM(CASE WHEN IsActive = 1 AND Quantity <= MinimumStock THEN 1 ELSE 0 END) as low_stock_parts,
            SUM(CASE WHEN IsActive = 1 THEN Quantity ELSE 0 END) as total_quantity
            FROM Parts";
        $partsStatsStmt = $pdo->prepare($partsStatsSql);
        $partsStatsStmt->execute();
        $partsStats = $partsStatsStmt->fetch();
    } catch (Exception $e) {
        $partsStats = ['total_parts' => 0, 'active_parts' => 0, 'low_stock_parts' => 0, 'total_quantity' => 0];
    }
    ?>
    
    <div class="card" style="margin-bottom: 30px;">
        <h3 style="margin-bottom: 20px;">Parts Inventory Overview</h3>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 20px;">
            <div class="data-card" style="border-left: 4px solid #2196F3;">
                <div class="data-header">
                    <h4 class="data-title">Total Parts</h4>
                    <span class="data-value"><?php echo $partsStats['total_parts'] ?? 0; ?></span>
                </div>
            </div>
            
            <div class="data-card" style="border-left: 4px solid #4CAF50;">
                <div class="data-header">
                    <h4 class="data-title">Active Parts</h4>
                    <span class="data-value"><?php echo $partsStats['active_parts'] ?? 0; ?></span>
                </div>
            </div>
            
            <div class="data-card" style="border-left: 4px solid #FF9800;">
                <div class="data-header">
                    <h4 class="data-title">Low Stock</h4>
                    <span class="data-value"><?php echo $partsStats['low_stock_parts'] ?? 0; ?></span>
                </div>
            </div>
            
            <div class="data-card" style="border-left: 4px solid #9C27B0;">
                <div class="data-header">
                    <h4 class="data-title">Total Quantity</h4>
                    <span class="data-value"><?php echo $partsStats['total_quantity'] ?? 0; ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($partsStats['low_stock_parts'] > 0): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-top: 15px;">
            <h4 style="color: #856404; margin: 0 0 10px 0;">⚠️ Low Stock Alert</h4>
            <p style="color: #856404; margin: 0;">
                <?php echo $partsStats['low_stock_parts']; ?> part(s) are running low on stock. 
                <a href="manage_parts.php" style="color: #856404; text-decoration: underline;">Manage inventory →</a>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Parts Usage -->
    <?php
    try {
        //Get parts usage recent
        $recentPartsSql = "SELECT pu.*, r.RequestId, c.CustName, p.Name as PartName, p.PartNumber
                          FROM partsused pu
                          JOIN request r ON pu.RequestID = r.RequestId
                          JOIN customer c ON r.CustID = c.CustID
                          LEFT JOIN parts p ON pu.PartId = p.PartId
                          ORDER BY pu.DateUsed DESC
                          LIMIT 5";
        $recentPartsStmt = $pdo->prepare($recentPartsSql);
        $recentPartsStmt->execute();
        $recentParts = $recentPartsStmt->fetchAll();
    } catch (Exception $e) {
        $recentParts = [];
    }
    ?>
    
    <?php if (!empty($recentParts)): ?>
    <div class="card" style="margin-bottom: 30px;">
        <h3 style="margin-bottom: 20px;">Recent Parts Usage</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #333; color: white;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Request #</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Customer</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Part Used</th>
                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #555;">Quantity</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #555;">Cost</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Date Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentParts as $part): ?>
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 12px;">
                            <strong>#<?php echo $part['RequestId']; ?></strong>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($part['CustName']); ?>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?php echo htmlspecialchars($part['PartName'] ?? 'Unknown Part'); ?></strong>
                            <?php if (!empty($part['PartNumber'])): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($part['PartNumber']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php echo $part['Quantity']; ?>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            RS. <?php echo number_format($part['Cost'], 2); ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo date('M j, Y', strtotime($part['DateUsed'])); ?>
                            <br><small style="color: #666;"><?php echo date('g:i A', strtotime($part['DateUsed'])); ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 15px;">
            <a href="manage_bills.php" class="btn btn-outline">View All Parts Usage →</a>
            <a href="parts_usage_report.php" class="btn btn-outline">Detailed Parts Report →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Statistics Overview -->
    <div class="dashboard-grid" style="margin-bottom: 40px;">
        <div class="data-card" style="border-left: 4px solid #2196F3;">
            <div class="data-header">
                <h3 class="data-title">Total Requests</h3>
                <span class="data-value"><?php echo $stats['total'] ?? 0; ?></span>
            </div>
        </div>
        
        <div class="data-card" style="border-left: 4px solid #FF9800;">
            <div class="data-header">
                <h3 class="data-title">Pending</h3>
                <span class="data-value"><?php echo $stats['pending'] ?? 0; ?></span>
            </div>
        </div>
        
        <div class="data-card" style="border-left: 4px solid #4CAF50;">
            <div class="data-header">
                <h3 class="data-title">Completed</h3>
                <span class="data-value"><?php echo $stats['completed'] ?? 0; ?></span>
            </div>
        </div>
        
        <div class="data-card" style="border-left: 4px solid #F44336;">
            <div class="data-header">
                <h3 class="data-title">Rejected</h3>
                <span class="data-value"><?php echo $stats['rejected'] ?? 0; ?></span>
            </div>
        </div>
        
        <div class="data-card" style="border-left: 4px solid #9C27B0;">
            <div class="data-header">
                <h3 class="data-title">In Progress</h3>
                <span class="data-value"><?php echo $stats['in_progress'] ?? 0; ?></span>
            </div>
        </div>
        
        <div class="data-card" style="border-left: 4px solid #607D8B;">
            <div class="data-header">
                <h3 class="data-title">Customers</h3>
                <span class="data-value"><?php echo $stats['customers'] ?? 0; ?></span>
            </div>
        </div>
    </div>

    <!-- Request Categories -->
    <?php
    $categories = [
        'pending' => ['title' => 'Pending Requests', 'color' => '#FF9800', 'description' => 'Requests waiting for approval'],
        'approved' => ['title' => 'Approved Requests', 'color' => '#2196F3', 'description' => 'Approved requests ready for assignment'],
        'in_progress' => ['title' => 'In Progress', 'color' => '#9C27B0', 'description' => 'Currently being worked on'],
        'completed' => ['title' => 'Completed Requests', 'color' => '#4CAF50', 'description' => 'Successfully completed repairs'],
        'rejected' => ['title' => 'Rejected Requests', 'color' => '#F44336', 'description' => 'Rejected or cancelled requests']
    ];
    
    foreach ($categories as $status => $category):
        $requests = $requests_by_status[$status];
        if (empty($requests)) continue;
    ?>
    
    <div class="card" style="margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border);">
            <div>
                <h3 style="margin: 0; color: <?php echo $category['color']; ?>;">
                    <?php echo $category['title']; ?> (<?php echo count($requests); ?>)
                </h3>
                <p style="margin: 5px 0 0 0; color: var(--muted); font-size: 0.9em;">
                    <?php echo $category['description']; ?>
                </p>
            </div>
            <?php if ($status === 'pending'): ?>
                <a href="assign_task.php" class="btn btn-small" style="background: <?php echo $category['color']; ?>;">
                    Assign Tasks
                </a>
            <?php endif; ?>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Request ID</th>
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Customer</th>
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Device</th>
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Problem</th>
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Priority</th>
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Date</th>
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Age</th>
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Cost</th>
                        <th style="text-align: left; padding: 12px 8px; font-weight: 600;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 12px 8px; font-weight: 600; color: <?php echo $category['color']; ?>;">
                            #<?php echo htmlspecialchars($request['RequestId']); ?>
                        </td>
                        <td style="padding: 12px 8px;">
                            <div>
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($request['CustName']); ?></div>
                                <div style="font-size: 0.85em; color: var(--muted);"><?php echo htmlspecialchars($request['CustomerEmail']); ?></div>
                            </div>
                        </td>
                        <td style="padding: 12px 8px;">
                            <span style="background: rgba(<?php 
                                echo $status === 'pending' ? '255,152,0' : 
                                    ($status === 'completed' ? '76,175,80' : 
                                    ($status === 'rejected' ? '244,67,54' : '156,39,176'));
                            ?>, 0.2); color: <?php echo $category['color']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 0.85em;">
                                <?php echo htmlspecialchars($request['DeviceTypeName'] ?? 'Unknown'); ?>
                            </span>
                        </td>
                        <td style="padding: 12px 8px; max-width: 200px;">
                            <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($request['ProblemDescription']); ?>">
                                <?php echo htmlspecialchars(substr($request['ProblemDescription'], 0, 50)); ?>
                                <?php if (strlen($request['ProblemDescription']) > 50) echo '...'; ?>
                            </div>
                        </td>
                        <td style="padding: 12px 8px;">
                            <span style="background: <?php 
                                echo $request['Priority'] === 'high' ? 'rgba(244,67,54,0.2); color: #F44336' : 
                                    ($request['Priority'] === 'medium' ? 'rgba(255,152,0,0.2); color: #FF9800' : 
                                    'rgba(76,175,80,0.2); color: #4CAF50');
                            ?>; padding: 3px 6px; border-radius: 3px; font-size: 0.8em; text-transform: uppercase;">
                                <?php echo htmlspecialchars($request['Priority']); ?>
                            </span>
                        </td>
                        <td style="padding: 12px 8px; font-size: 0.9em; color: var(--muted);">
                            <?php echo date('M j, Y', strtotime($request['DateSubmitted'])); ?>
                        </td>
                        <td style="padding: 12px 8px; font-size: 0.9em;">
                            <span style="color: <?php echo $request['DaysOld'] > 7 ? '#F44336' : ($request['DaysOld'] > 3 ? '#FF9800' : 'var(--muted)'); ?>;">
                                <?php echo $request['DaysOld']; ?> days
                            </span>
                        </td>
                        <td style="padding: 12px 8px; font-weight: 500;">
                            RS. <?php echo number_format($request['FinalCost'] ?? 0, 2); ?>
                        </td>
                        <td style="padding: 12px 8px;">
                            <div style="display: flex; gap: 5px;">
                                <a href="../track_status.php?request_id=<?php echo $request['RequestId']; ?>" 
                                   class="btn btn-small btn-outline" style="padding: 4px 8px; font-size: 0.8em;">
                                    View
                                </a>
                                <?php if ($status === 'pending'): ?>
                                    <a href="assign_task.php?request_id=<?php echo $request['RequestId']; ?>" 
                                       class="btn btn-small" style="padding: 4px 8px; font-size: 0.8em; background: #4CAF50;">
                                        Assign
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php endforeach; ?>
    
    <?php if (empty(array_filter($requests_by_status))): ?>
    <div class="card" style="text-align: center; padding: 60px 20px;">
        <h3 style="color: var(--muted); margin-bottom: 10px;">No Requests Found</h3>
        <p style="color: var(--muted); margin-bottom: 20px;">There are no customer requests in the system yet.</p>
        <a href="../index.php" class="btn btn-outline">Go to Homepage</a>
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>


