<?php

//session management and database
require_once 'includes/session.php';
require_once 'config/database.php';

// Allow admins and staff to access register page to add customers
// redirect customers already logged in
if (isLoggedIn() && hasRole('customer')) {
    header('Location: pages/customer_dashboard.php');
    exit;
}

$page_title = 'Register - ETERNATECH REPAIRS';
$error = '';
$success = '';

//Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($address) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            //Check email already exists
            $checkSql = "SELECT CustID FROM Customer WHERE Email = :email";
            $checkStmt = executeQuery($pdo, $checkSql, [':email' => $email]);
            
            if ($checkStmt && $checkStmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                // Hash password
                $passwordHash = hashPassword($password);
                
                //create new customer
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
                    $success = 'Registration successful! You can now log in.';
                    // Clear form data
                    $_POST = [];
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (Exception $e) {
            error_log('Registration error: ' . $e->getMessage());
            $error = 'Registration failed. Please try again.';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<main class="container narrow">
    <h2>Register</h2>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" class="card" onsubmit="return validateRegistrationForm(this)">
        <label>
            <span>Full Name</span>
            <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                   placeholder="Enter your full name">
        </label>
        
        <label>
            <span>Email</span>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   placeholder="Enter your email address">
        </label>
        
        <label>
            <span>Address</span>
            <input type="text" name="address" required value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>"
                   placeholder="Enter your address">
        </label>
        
        <label>
            <span>Password</span>
            <input type="password" name="password" required minlength="6"
                   placeholder="Enter password (minimum 6 characters)">
        </label>
        
        <label>
            <span>Confirm Password</span>
            <input type="password" name="confirm_password" required minlength="6"
                   placeholder="Confirm your password">
        </label>
        
        <button type="submit" class="btn" id="registerBtn" data-original-text="Register">Register</button>
        
        <p class="muted">Already have an account? <a href="login.php">Login here</a></p>
    </form>
</main>

<script>
function validateRegistrationForm(form) {
    const registerBtn = document.getElementById('registerBtn');
    const password = form.password.value;
    const confirmPassword = form.confirm_password.value;
    
    if (!validateForm(form)) {
        return false;
    }
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    setLoading(registerBtn, true);
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>
