<?php

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');

$page_title = 'My Account - ETERNATECH REPAIRS';
$error = '';
$success = '';

$currentUser = getCurrentUser();
$userRole = getCurrentUserRole();
$userId = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All password fields are required');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match');
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters long');
            }
            
            $tableName = '';
            $idField = '';
            switch ($userRole) {
                case 'customer':
                    $tableName = 'customer';
                    $idField = 'CustID';
                    break;
                case 'technician':
                    $tableName = 'technician';
                    $idField = 'TechnicianId';
                    break;
                case 'admin':
                    $tableName = 'admin';
                    $idField = 'AdminId';
                    break;
                default:
                    throw new Exception('Invalid user role');
            }
            
            // Verify current password
            $sql = "SELECT PasswordHash FROM $tableName WHERE $idField = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $userRecord = $stmt->fetch();
            
            if (!$userRecord) {
                throw new Exception('User not found');
            }
            
            // Check current password
            $currentPasswordHash = hash('sha256', $currentPassword);
            if ($currentPasswordHash !== $userRecord['PasswordHash']) {
                throw new Exception('Current password is incorrect');
            }
            
            // Update password
            $newPasswordHash = hash('sha256', $newPassword);
            $updateSql = "UPDATE $tableName SET PasswordHash = ? WHERE $idField = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$newPasswordHash, $userId]);
            
            $success = 'Password changed successfully!';
            
        } elseif ($action === 'delete_account') {
            $passwordConfirm = $_POST['delete_password'] ?? '';
            
            if (empty($passwordConfirm)) {
                throw new Exception('Password is required to delete account');
            }
            
            $tableName = '';
            $idField = '';
            switch ($userRole) {
                case 'customer':
                    $tableName = 'customer';
                    $idField = 'CustID';
                    break;
                case 'technician':
                    $tableName = 'technician';
                    $idField = 'TechnicianId';
                    break;
                case 'admin':
                    $tableName = 'admin';
                    $idField = 'AdminId';
                    break;
                default:
                    throw new Exception('Invalid user role');
            }
            
            // Verify password
            $sql = "SELECT PasswordHash FROM $tableName WHERE $idField = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            $userRecord = $stmt->fetch();
            
            if (!$userRecord) {
                throw new Exception('User not found');
            }
            
            $passwordHash = hash('sha256', $passwordConfirm);
            if ($passwordHash !== $userRecord['PasswordHash']) {
                throw new Exception('Password is incorrect');
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // For customers, check if they have any active requests
                if ($userRole === 'customer') {
                    $requestCheck = $pdo->prepare("SELECT COUNT(*) as count FROM request WHERE CustID = ? AND Status IN ('pending', 'approved', 'in_progress')");
                    $requestCheck->execute([$userId]);
                    $activeRequests = $requestCheck->fetch()['count'];
                    
                    if ($activeRequests > 0) {
                        throw new Exception('Cannot delete account: You have active repair requests. Please wait for completion or contact support.');
                    }
                    
                    //Deactivate customer instead of deleting to preserve request history
                    $deactivateSql = "UPDATE customer SET Email = CONCAT('DELETED_', CustID, '_', Email), CustName = 'DELETED USER' WHERE CustID = ?";
                    $stmt = $pdo->prepare($deactivateSql);
                    $stmt->execute([$userId]);
                    
                } elseif ($userRole === 'technician') {
                    // Check for active assignments
                    $assignmentCheck = $pdo->prepare("SELECT COUNT(*) as count FROM taskassignment WHERE TechnicianId = ? AND Status IN ('Assigned', 'Ongoing')");
                    $assignmentCheck->execute([$userId]);
                    $activeAssignments = $assignmentCheck->fetch()['count'];
                    
                    if ($activeAssignments > 0) {
                        throw new Exception('Cannot delete account: You have active task assignments. Please complete them first.');
                    }
                    
                    //  Deactivate technician
                    $deactivateSql = "UPDATE technician SET Email = CONCAT('DELETED_', TechnicianId, '_', Email), Name = 'DELETED TECHNICIAN' WHERE TechnicianId = ?";
                    $stmt = $pdo->prepare($deactivateSql);
                    $stmt->execute([$userId]);
                    
                } elseif ($userRole === 'admin') {
                    //Check if this is the last admin
                    $adminCount = $pdo->query("SELECT COUNT(*) as count FROM admin")->fetch()['count'];
                    if ($adminCount <= 1) {
                        throw new Exception('Cannot delete account: At least one admin account must remain active.');
                    }
                    
                    //  Delete admin accoount
                    $deleteSql = "DELETE FROM admin WHERE AdminId = ?";
                    $stmt = $pdo->prepare($deleteSql);
                    $stmt->execute([$userId]);
                }
                
                $pdo->commit();
                
                //destroy session
                session_destroy();
                
                // Return success message for JavaScript handling
                echo json_encode(['success' => true, 'message' => 'Account deleted successfully. You will be redirected to the homepage.']);
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
        
    } catch (Exception $e) {
        if ($action === 'delete_account') {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        $error = $e->getMessage();
    }
}

include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2>My Account</h2>
            <p style="color: var(--muted); margin: 5px 0;">Manage your account settings</p>
        </div>
        <div>
            <?php if ($userRole === 'customer'): ?>
                <a href="customer_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
            <?php elseif ($userRole === 'technician'): ?>
                <a href="technician_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
            <?php elseif ($userRole === 'admin'): ?>
                <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Account Information -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Account Information</h3>
        <div style="display: grid; gap: 15px;">
            <div>
                <label style="font-weight: bold; color: var(--muted);">Name:</label>
                <p style="margin: 5px 0; font-size: 1.1em;"><?php echo htmlspecialchars($currentUser['name']); ?></p>
            </div>
            <div>
                <label style="font-weight: bold; color: var(--muted);">Email:</label>
                <p style="margin: 5px 0; font-size: 1.1em;"><?php echo htmlspecialchars($currentUser['email']); ?></p>
            </div>
            <div>
                <label style="font-weight: bold; color: var(--muted);">Role:</label>
                <p style="margin: 5px 0; font-size: 1.1em; text-transform: capitalize;"><?php echo htmlspecialchars($currentUser['role']); ?></p>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Change Password</h3>
        <p style="color: var(--muted); margin-bottom: 20px;">Update your account password for security</p>
        
        <button onclick="openPasswordModal()" class="btn" style="background: #4CAF50;">Change Password</button>
    </div>

    <!-- Delete Account -->
    <div class="card" style="border-left: 4px solid #F44336;">
        <h3 style="color: #F44336;">Danger Zone</h3>
        <p style="color: var(--muted); margin-bottom: 20px;">
            Permanently delete your account. This action cannot be undone.
            <?php if ($userRole === 'customer'): ?>
                <br><small>Note: Accounts with active repair requests cannot be deleted.</small>
            <?php elseif ($userRole === 'technician'): ?>
                <br><small>Note: Accounts with active task assignments cannot be deleted.</small>
            <?php elseif ($userRole === 'admin'): ?>
                <br><small>Note: At least one admin account must remain active.</small>
            <?php endif; ?>
        </p>
        
        <button onclick="openDeleteModal()" class="btn" style="background: #F44336;">Delete Account</button>
    </div>
</main>

<!-- Change Password Modal -->
<div id="passwordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--card); padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; border: 1px solid var(--border);">
        <h3 style="margin-top: 0; color: var(--text);">Change Password</h3>
        
        <form id="passwordForm" method="POST" onsubmit="return changePassword(event)">
            <input type="hidden" name="action" value="change_password">
            
            <label style="display: block; margin-bottom: 15px;">
                <span style="display: block; margin-bottom: 5px; color: var(--text);">Current Password *</span>
                <input type="password" name="current_password" required style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; background: var(--bg); color: var(--text);">
            </label>
            
            <label style="display: block; margin-bottom: 15px;">
                <span style="display: block; margin-bottom: 5px; color: var(--text);">New Password *</span>
                <input type="password" name="new_password" required minlength="6" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; background: var(--bg); color: var(--text);">
            </label>
            
            <label style="display: block; margin-bottom: 20px;">
                <span style="display: block; margin-bottom: 5px; color: var(--text);">Confirm New Password *</span>
                <input type="password" name="confirm_password" required minlength="6" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; background: var(--bg); color: var(--text);">
            </label>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closePasswordModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn" style="background: #4CAF50;">Change Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--card); padding: 30px; border-radius: 8px; width: 90%; max-width: 500px; border: 1px solid #F44336;">
        <h3 style="margin-top: 0; color: #F44336;">⚠️ Delete Account</h3>
        <p style="color: var(--text); margin-bottom: 20px;">
            This action is permanent and cannot be undone. All your data will be removed or anonymized.
        </p>
        
        <form id="deleteForm" method="POST" onsubmit="return deleteAccount(event)">
            <input type="hidden" name="action" value="delete_account">
            
            <label style="display: block; margin-bottom: 20px;">
                <span style="display: block; margin-bottom: 5px; color: var(--text);">Enter your password to confirm deletion *</span>
                <input type="password" name="delete_password" required style="width: 100%; padding: 10px; border: 1px solid #F44336; border-radius: 4px; background: var(--bg); color: var(--text);">
            </label>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn" style="background: #F44336;">Delete Account</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'block';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
    document.getElementById('passwordForm').reset();
}

function openDeleteModal() {
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.getElementById('deleteForm').reset();
}

function changePassword(event) {
    event.preventDefault();
    
    const form = event.target;
    const newPassword = form.new_password.value;
    const confirmPassword = form.confirm_password.value;
    
    if (newPassword !== confirmPassword) {
        showMessage('error', 'New passwords do not match');
        return false;
    }
    
    if (newPassword.length < 6) {
        showMessage('error', 'Password must be at least 6 characters long');
        return false;
    }
    
    // Submit form normally
    form.submit();
}

function deleteAccount(event) {
    event.preventDefault();
    
    if (!confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.')) {
        return false;
    }
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Deleting...';
    submitBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('success', data.message);
            setTimeout(() => {
                window.location.href = '../index.php';
            }, 2000);
        } else {
            showMessage('error', data.message);
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showMessage('error', 'An error occurred while deleting the account');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function showMessage(type, message) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.msg');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `msg ${type}`;
    messageDiv.textContent = message;
    
    // Insert after the header
    const container = document.querySelector('main.container');
    const firstCard = container.querySelector('.card');
    container.insertBefore(messageDiv, firstCard);
    
    // Close modals on success
    if (type === 'success') {
        closePasswordModal();
        closeDeleteModal();
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const passwordModal = document.getElementById('passwordModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === passwordModal) {
        closePasswordModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
