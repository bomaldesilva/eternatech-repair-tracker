
<?php
/**
 * Add Customer Page - RepairShop
 */

// Include session management and database
require_once '../includes/session.php';
require_once '../config/database.php';

// Require admin login
requireLogin('../admin_login.php');
requireRole('admin', '../index.php');

$page_title = 'Add Walk-in Customer - ETERNATECH REPAIRS';
$error = '';
$success = '';
$customer_id = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    // Validation
    if (empty($name)) {
        $error = 'Customer name is required.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email already exists
            $checkSql = "SELECT CustID FROM Customer WHERE Email = :email";
            $checkStmt = executeQuery($pdo, $checkSql, [':email' => $email]);
            
            if ($checkStmt && $existingCustomer = $checkStmt->fetch()) {
                $error = "A customer with this email already exists (ID: {$existingCustomer['CustID']}).";
            } else {
                // Generate a temporary password for walk-in customer
                $tempPassword = 'temp' . rand(1000, 9999);
                $passwordHash = hashPassword($tempPassword);
                
                // Insert new customer  
                $insertSql = "INSERT INTO Customer (CustName, Email, Address, PasswordHash, RegistrationDate) 
                             VALUES (:name, :email, :address, :password, :regDate)";
                
                $insertStmt = executeQuery($pdo, $insertSql, [
                    ':name' => $name,
                    ':email' => $email,
                    ':address' => $address,
                    ':password' => $passwordHash,
                    ':regDate' => date('Y-m-d H:i:s')
                ]);
                
                if ($insertStmt) {
                    $customer_id = $pdo->lastInsertId();
                    $success = "Customer '{$name}' has been successfully registered!";
                    
                    // Store temp password to show to admin
                    $_SESSION['temp_password'] = $tempPassword;
                    $_SESSION['temp_customer_id'] = $customer_id;
                    
                    // Clear form data
                    $_POST = [];
                } else {
                    $error = 'Failed to register customer. Please try again.';
                }
            }
        } catch (Exception $e) {
            error_log('Add customer error: ' . $e->getMessage());
            $error = 'Failed to register customer. Please try again.';
        }
    }
}

$temp_password = $_SESSION['temp_password'] ?? '';
$temp_customer_id = $_SESSION['temp_customer_id'] ?? '';

// Clear temporary session data after displaying
if ($temp_password) {
    unset($_SESSION['temp_password'], $_SESSION['temp_customer_id']);
}

// Include header
include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Add Walk-in Customer</h2>
        <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
        
        <?php if ($temp_password && $temp_customer_id): ?>
        <div class="card" style="background: rgba(79, 195, 247, 0.1); border: 1px solid var(--accent);">
            <h4 style="margin: 0 0 15px 0; color: var(--accent);">Customer Login Information</h4>
            <div class="data-field">
                <span class="field-label">Customer ID:</span>
                <span class="field-value"><strong><?php echo htmlspecialchars($temp_customer_id); ?></strong></span>
            </div>
            <div class="data-field">
                <span class="field-label">Temporary Password:</span>
                <span class="field-value"><strong><?php echo htmlspecialchars($temp_password); ?></strong></span>
            </div>
            <p style="margin: 15px 0 0 0; color: var(--muted); font-size: 0.9em;">
                <strong>Important:</strong> Please provide this temporary password to the customer. 
                They can use it to log in and change their password later.
            </p>
        </div>
        
        <div class="actions" style="margin: 20px 0;">
            <a href="admin_dashboard.php" class="btn">Back to Dashboard</a>
            <a href="add_customer.php" class="btn btn-outline">Add Another Customer</a>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card">
        <h3>Customer Account Details</h3>
        
        <form method="POST" class="form">
            <div class="form-group">
                <label for="name">Customer Name *</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       required 
                       placeholder="Enter customer's full name">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       required 
                       placeholder="Enter customer's email address">
                <small class="form-help">This will be used for login and notifications</small>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" 
                       id="address" 
                       name="address" 
                       value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                       placeholder="Enter customer's address">
                <small class="form-help">Customer's physical address for service records</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Register Customer</button>
                <a href="admin_dashboard.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    
    <div class="card info-card">
        <h4>🏪 Walk-in Customer Information</h4>
        <ul>
            <li><strong>Registration:</strong> Automatic temporary password generation</li>
            <li><strong>Login:</strong> Customer can use email and temporary password</li>
            <li><strong>Process:</strong> Help customer submit repair request after registration</li>
            <li><strong>Access:</strong> Customer dashboard to track repair status</li>
        </ul>
    </div>
</main>

<style>
.info-card {
    margin-top: 20px;
    background: #f8f9fa;
    border-left: 4px solid #ff9800;
}

.info-card h4 {
    color: #ff9800;
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
