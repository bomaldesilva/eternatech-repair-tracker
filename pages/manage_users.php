<?php

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../admin_login.php');
requireRole('admin', '../index.php');

$page_title = 'User Management - ETERNATECH REPAIRS';
$error = '';
$success = '';

// CSRF Token 
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'reset_password') {
            $userId = intval($_POST['user_id'] ?? 0);
            $userRole = sanitizeInput($_POST['role'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validation
            if ($userId <= 0 || !in_array($userRole, ['admin', 'customer', 'technician'])) {
                throw new Exception('Invalid user data.');
            }
            
            if (empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('Both password fields are required.');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('Passwords do not match.');
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception('Password must be at least 6 characters long.');
            }
            
            // Update password in appropriate table
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            switch ($userRole) {
                case 'admin':
                    $updateSql = "UPDATE admin SET PasswordHash = ? WHERE AdminId = ?";
                    break;
                case 'customer':
                    $updateSql = "UPDATE customer SET PasswordHash = ? WHERE CustID = ?";
                    break;
                case 'technician':
                    $updateSql = "UPDATE technician SET PasswordHash = ? WHERE TechnicianId = ?";
                    break;
            }
            
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute([$hashedPassword, $userId]);
            
            $success = "Password updated successfully for {$userRole} ID {$userId}.";
            
        } elseif ($action === 'delete_user') {
            $userId = intval($_POST['user_id'] ?? 0);
            $userRole = sanitizeInput($_POST['role'] ?? '');
            
            if ($userId <= 0 || !in_array($userRole, ['admin', 'customer', 'technician'])) {
                throw new Exception('Invalid user data.');
            }
            
            // Prevent self-deletion for admins
            if ($userRole === 'admin' && $userId == getCurrentUserId()) {
                throw new Exception('You cannot delete your own admin account.');
            }
            
            // Check for active requests/tasks
            $canDelete = true;
            $requestCount = 0;
            
            if ($userRole === 'customer') {
                $checkSql = "SELECT COUNT(*) as count FROM request WHERE CustID = ? AND Status IN ('pending', 'approved', 'in_progress')";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([$userId]);
                $requestCount = $checkStmt->fetch()['count'];
                
                if ($requestCount > 0) {
                    throw new Exception("Cannot delete customer: {$requestCount} active requests exist.");
                }
                
                // Deactivate customer instead of deleting to preserve history
                $deactivateSql = "UPDATE customer SET Email = CONCAT('DELETED_', CustID, '_', Email), CustName = 'DELETED USER' WHERE CustID = ?";
                $stmt = $pdo->prepare($deactivateSql);
                $stmt->execute([$userId]);
                
            } elseif ($userRole === 'technician') {
                $checkSql = "SELECT COUNT(*) as count FROM taskassignment WHERE TechnicianId = ?";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([$userId]);
                $requestCount = $checkStmt->fetch()['count'];
                
                if ($requestCount > 0) {
                    throw new Exception("Cannot delete technician: {$requestCount} assigned tasks exist.");
                }
                
                $deleteSql = "DELETE FROM technician WHERE TechnicianId = ?";
                $stmt = $pdo->prepare($deleteSql);
                $stmt->execute([$userId]);
                
            } elseif ($userRole === 'admin') {
                $deleteSql = "DELETE FROM admin WHERE AdminId = ?";
                $stmt = $pdo->prepare($deleteSql);
                $stmt->execute([$userId]);
            }
            
            $success = ucfirst($userRole) . " deleted successfully.";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all users from each table
try {
    // Get customers
    $customerSql = "SELECT CustID, CustName, Email, Address, RegistrationDate,
                           (SELECT COUNT(*) FROM request WHERE CustID = c.CustID) as request_count,
                           CASE WHEN CustName = 'DELETED USER' THEN 0 ELSE 1 END as is_active
                    FROM customer c 
                    ORDER BY RegistrationDate DESC";
    $customerStmt = $pdo->prepare($customerSql);
    $customerStmt->execute();
    $customers = $customerStmt->fetchAll();
    
    // Get technicians
    $technicianSql = "SELECT TechnicianId, Name, Email, Speciality,
                             (SELECT COUNT(*) FROM taskassignment WHERE TechnicianId = t.TechnicianId) as task_count
                      FROM technician t 
                      ORDER BY Name";
    $technicianStmt = $pdo->prepare($technicianSql);
    $technicianStmt->execute();
    $technicians = $technicianStmt->fetchAll();
    
    // Get admins
    $adminSql = "SELECT AdminId, Name, Username 
                 FROM admin 
                 ORDER BY Name";
    $adminStmt = $pdo->prepare($adminSql);
    $adminStmt->execute();
    $admins = $adminStmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Unable to load user data: ' . $e->getMessage();
    $customers = [];
    $technicians = [];
    $admins = [];
}

include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>User Management</h2>
        <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="tab-container">
        <div class="tab-nav">
            <button class="tab-btn active" onclick="showTab('customers')">
                Customers (<?php echo count($customers); ?>)
            </button>
            <button class="tab-btn" onclick="showTab('technicians')">
                Technicians (<?php echo count($technicians); ?>)
            </button>
            <button class="tab-btn" onclick="showTab('admins')">
                Admins (<?php echo count($admins); ?>)
            </button>
        </div>

        <!-- Customers Tab -->
        <div id="customers" class="tab-content active">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Customers</h3>
                    <a href="add_customer.php" class="btn btn-primary">+ Add New Customer</a>
                </div>
                
                <?php if (empty($customers)): ?>
                <div class="empty-state">
                    <h4>No Customers Found</h4>
                    <p>No customers are registered in the system.</p>
                </div>
                <?php else: ?>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Requests</th>
                                <th>Registered</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['CustID']; ?></td>
                                <td><?php echo htmlspecialchars($customer['CustName']); ?></td>
                                <td><?php echo htmlspecialchars($customer['Email']); ?></td>
                                <td><?php echo htmlspecialchars($customer['Address'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($customer['request_count'] > 0): ?>
                                        <span class="badge badge-info"><?php echo $customer['request_count']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($customer['RegistrationDate'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $customer['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $customer['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($customer['is_active']): ?>
                                    <div class="btn-group">
                                        <button onclick="resetPassword(<?php echo $customer['CustID']; ?>, 'customer', '<?php echo htmlspecialchars($customer['CustName']); ?>')" 
                                                class="btn-small btn-info">Reset Password</button>
                                        
                                        <?php if ($customer['request_count'] == 0): ?>
                                            <button onclick="deleteUser(<?php echo $customer['CustID']; ?>, 'customer', '<?php echo htmlspecialchars($customer['CustName']); ?>')" 
                                                    class="btn-small btn-delete">Delete</button>
                                        <?php else: ?>
                                            <button class="btn-small btn-delete" disabled 
                                                    title="Cannot delete: <?php echo $customer['request_count']; ?> requests exist">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                        <span class="text-muted">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Technicians Tab -->
        <div id="technicians" class="tab-content">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Technicians</h3>
                    <a href="add_staff.php" class="btn btn-primary">+ Add New Technician</a>
                </div>
                
                <?php if (empty($technicians)): ?>
                <div class="empty-state">
                    <h4>No Technicians Found</h4>
                    <p>No technicians are registered in the system.</p>
                </div>
                <?php else: ?>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Speciality</th>
                                <th>Assigned Tasks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($technicians as $technician): ?>
                            <tr>
                                <td><?php echo $technician['TechnicianId']; ?></td>
                                <td><?php echo htmlspecialchars($technician['Name']); ?></td>
                                <td><?php echo htmlspecialchars($technician['Email']); ?></td>
                                <td><?php echo htmlspecialchars($technician['Speciality'] ?? 'General'); ?></td>
                                <td>
                                    <?php if ($technician['task_count'] > 0): ?>
                                        <span class="badge badge-info"><?php echo $technician['task_count']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button onclick="resetPassword(<?php echo $technician['TechnicianId']; ?>, 'technician', '<?php echo htmlspecialchars($technician['Name']); ?>')" 
                                                class="btn-small btn-info">Reset Password</button>
                                        
                                        <?php if ($technician['task_count'] == 0): ?>
                                            <button onclick="deleteUser(<?php echo $technician['TechnicianId']; ?>, 'technician', '<?php echo htmlspecialchars($technician['Name']); ?>')" 
                                                    class="btn-small btn-delete">Delete</button>
                                        <?php else: ?>
                                            <button class="btn-small btn-delete" disabled 
                                                    title="Cannot delete: <?php echo $technician['task_count']; ?> tasks assigned">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admins Tab -->
        <div id="admins" class="tab-content">
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>Admins</h3>
                    <a href="add_admin.php" class="btn btn-primary">+ Add New Admin</a>
                </div>
                
                <?php if (empty($admins)): ?>
                <div class="empty-state">
                    <h4>No Admins Found</h4>
                    <p>No admins are registered in the system.</p>
                </div>
                <?php else: ?>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['AdminId']; ?></td>
                                <td><?php echo htmlspecialchars($admin['Name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['Username']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button onclick="resetPassword(<?php echo $admin['AdminId']; ?>, 'admin', '<?php echo htmlspecialchars($admin['Name']); ?>')" 
                                                class="btn-small btn-info">Reset Password</button>
                                        
                                        <?php if ($admin['AdminId'] != getCurrentUserId()): ?>
                                            <button onclick="deleteUser(<?php echo $admin['AdminId']; ?>, 'admin', '<?php echo htmlspecialchars($admin['Name']); ?>')" 
                                                    class="btn-small btn-delete">Delete</button>
                                        <?php else: ?>
                                            <button class="btn-small btn-delete" disabled 
                                                    title="Cannot delete your own account">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div id="passwordModal" style="display: none;">
    <div class="modal-overlay" onclick="closePasswordModal()"></div>
    <div class="modal-content">
        <h3>Reset Password</h3>
        <p>Reset password for: <strong id="resetUserName"></strong></p>
        
        <form method="POST">
            <input type="hidden" id="resetUserId" name="user_id">
            <input type="hidden" id="resetUserRole" name="role">
            <input type="hidden" name="action" value="reset_password">
            
            <label>
                <span>New Password *</span>
                <input type="password" name="new_password" required minlength="6" 
                       placeholder="Minimum 6 characters">
            </label>
            
            <label>
                <span>Confirm Password *</span>
                <input type="password" name="confirm_password" required minlength="6">
            </label>
            
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closePasswordModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" style="display: none;">
    <div class="modal-overlay" onclick="closeDeleteModal()"></div>
    <div class="modal-content">
        <h3>Confirm Delete</h3>
        <p>Are you sure you want to delete: <strong id="deleteUserName"></strong>?</p>
        <p style="color: red; font-size: 14px;">This action cannot be undone.</p>
        
        <form method="POST">
            <input type="hidden" id="deleteUserId" name="user_id">
            <input type="hidden" id="deleteUserRole" name="role">
            <input type="hidden" name="action" value="delete_user">
            
            <div style="text-align: right; margin-top: 20px;">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-delete">Delete User</button>
            </div>
        </form>
    </div>
</div>

<style>
.tab-container {
    margin-top: 20px;
}

.tab-nav {
    display: flex;
    border-bottom: 2px solid var(--border);
    margin-bottom: 0;
}

.tab-btn {
    padding: 15px 25px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    color: var(--muted);
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-btn:hover {
    color: var(--text);
    background: var(--hover);
}

.tab-btn.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    background: var(--card);
}

.tab-content {
    display: none;
    margin-top: 0;
}

.tab-content.active {
    display: block;
}

.table-responsive {
    overflow-x: auto;
    margin: 20px 0;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

.data-table th {
    background-color: var(--hover);
    font-weight: bold;
}

.btn-group {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
}

.btn-delete {
    background-color: #dc3545;
    color: white;
}

.btn-delete:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.5;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-active { background: #d4edda; color: #155724; }
.status-inactive { background: #f8d7da; color: #721c24; }

.badge {
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}

.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-muted { background: #e2e3e5; color: #6c757d; }

#passwordModal, #deleteModal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: var(--card);
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 400px;
    border: 1px solid var(--border);
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--muted);
}

.text-muted {
    color: var(--muted);
    font-style: italic;
}
</style>

<script>
function showTab(tabName) {
    //hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.remove('active'));
    
    //remove active class from all tab buttons
    const tabBtns = document.querySelectorAll('.tab-btn');
    tabBtns.forEach(btn => btn.classList.remove('active'));
    
    //show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    //add active class to clicked tab button
    event.target.classList.add('active');
}

function resetPassword(userId, role, userName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserRole').value = role;
    document.getElementById('resetUserName').textContent = userName;
    document.getElementById('passwordModal').style.display = 'block';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}

function deleteUser(userId, role, userName) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUserRole').value = role;
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closePasswordModal();
        closeDeleteModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
