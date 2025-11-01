<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../admin_login.php');
requireRole('admin', '../index.php');

$page_title = 'Add Admin - ETERNATECH REPAIRS';
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields except username are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if email already exists
            $checkEmailSql = "SELECT COUNT(*) as count FROM admin WHERE Email = ?";
            $checkEmailStmt = $pdo->prepare($checkEmailSql);
            $checkEmailStmt->execute([$email]);
            $emailExists = $checkEmailStmt->fetch()['count'] > 0;
            
            // Check if username already exists (if provided)
            $usernameExists = false;
            if (!empty($username)) {
                $checkUsernameSql = "SELECT COUNT(*) as count FROM admin WHERE Username = ?";
                $checkUsernameStmt = $pdo->prepare($checkUsernameSql);
                $checkUsernameStmt->execute([$username]);
                $usernameExists = $checkUsernameStmt->fetch()['count'] > 0;
            }
            
            if ($emailExists) {
                $error = 'An admin with this email already exists.';
            } elseif ($usernameExists) {
                $error = 'An admin with this username already exists.';
            } else {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new admin
                $insertSql = "INSERT INTO admin (Name, Email, Username, PasswordHash) VALUES (?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertSql);
                
                // Use NULL for username if empty
                $usernameValue = !empty($username) ? $username : null;
                
                $insertStmt->execute([$name, $email, $usernameValue, $hashedPassword]);
                
                $success = 'Admin account created successfully!';
                
                // Clear form data
                $name = $email = $username = '';
            }
        } catch (Exception $e) {
            error_log('Add admin error: ' . $e->getMessage());
            $error = 'Failed to create admin account. Please try again.';
        }
    }
}

include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Add New Admin</h2>
        <a href="manage_users.php" class="btn btn-outline">← Back to User Management</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Admin Account Details</h3>
        
        <form method="POST" class="form">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo htmlspecialchars($name ?? ''); ?>"
                       required 
                       placeholder="Enter admin's full name">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                       required 
                       placeholder="Enter admin's email address">
                <small class="form-help">This will be used for login and notifications</small>
            </div>
            
            <div class="form-group">
                <label for="username">Username (Optional)</label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?php echo htmlspecialchars($username ?? ''); ?>"
                       placeholder="Enter username (optional)">
                <small class="form-help">If not provided, admin can login using email only</small>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       minlength="6"
                       placeholder="Enter password (minimum 6 characters)">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       required 
                       minlength="6"
                       placeholder="Re-enter password">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Admin Account</button>
                <a href="manage_users.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    
    <div class="card info-card">
        <h4>📋 Admin Account Information</h4>
        <ul>
            <li><strong>Email:</strong> Required for login and system notifications</li>
            <li><strong>Username:</strong> Optional alternative login identifier</li>
            <li><strong>Password:</strong> Minimum 6 characters, automatically encrypted</li>
            <li><strong>Permissions:</strong> Full system access including user management</li>
        </ul>
    </div>
</main>

<style>
.info-card {
    margin-top: 20px;
    background: #f8f9fa;
    border-left: 4px solid #007bff;
}

.info-card h4 {
    color: #007bff;
    margin-bottom: 15px;
}

.info-card ul {
    margin: 0;
    padding-left: 20px;
}

.info-card li {
    margin-bottom: 8px;
    color: #666;
}

.form-help {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 0.9em;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.form-actions .btn {
    padding: 12px 24px;
}
</style>

<?php include '../includes/footer.php'; ?>
