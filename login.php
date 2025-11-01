<?php


//Include session management and database
require_once 'includes/session.php';
require_once 'config/database.php';

//Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    switch ($role) {
        case 'customer':
            header('Location: pages/customer_dashboard.php');
            break;
        case 'admin':
            header('Location: pages/admin_dashboard.php');
            break;
        case 'technician':
            header('Location: pages/technician_dashboard.php');
            break;
        default:
            header('Location: index.php');
    }
    exit;
}

$page_title = 'Login - ETERNATECH REPAIRS';
$error = '';

//handleFormSubmission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? '');
    
    if (empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } else {
        //hashThePassword
        $passwordHash = hashPassword($password);
        
        try {
            $user = null;
            $tableName = '';
            $idField = '';
            $nameField = '';
            switch ($role) {
                case 'customer':
                    $tableName = 'Customer';
                    $idField = 'CustID';
                    $nameField = 'CustName';
                    break;
                case 'admin':
                    $tableName = 'Admin';
                    $idField = 'AdminId';
                    $nameField = 'Name';
                    break;
                case 'technician':
                    $tableName = 'Technician';
                    $idField = 'TechnicianId';
                    $nameField = 'Name';
                    break;
                default:
                    $error = 'Invalid role selected.';
                    break;
            }
            
            if (empty($error)) {
                if ($role === 'admin') {
                    // For admin: check both Email and Username fields
                    $sql = "SELECT $idField as id, $nameField as name, Email, PasswordHash, Username 
                            FROM $tableName 
                            WHERE Email = :email OR Username = :username";
                    
                    $stmt = executeQuery($pdo, $sql, [':email' => $email, ':username' => $email]);
                    
                    if ($stmt && $user = $stmt->fetch()) {
                        // Check password - handle multiple formats
                        $passwordMatch = false;
                        if (password_verify($password, $user['PasswordHash'])) {
                            // Modern password_hash() format (starts with $2y$)
                            $passwordMatch = true;
                        } else if ($password === $user['PasswordHash']) {
                            // Plain text password match (legacy format)
                            $passwordMatch = true;
                        } else if (hash('sha256', $password) === $user['PasswordHash']) {
                            // SHA256 hash format (used by hashPassword() function)
                            $passwordMatch = true;
                        }
                        
                        if ($passwordMatch) {
                            setUserSession($user['id'], $user['name'], $user['Email'], $role);
                            header('Location: pages/admin_dashboard.php');
                            exit;
                        } else {
                            $error = 'Invalid credentials.';
                        }
                    } else {
                        $error = 'Invalid credentials.';
                    }
                } else {
                    $sql = "SELECT $idField as id, $nameField as name, Email, PasswordHash 
                            FROM $tableName 
                            WHERE Email = :email AND PasswordHash = :password";
                    
                    $stmt = executeQuery($pdo, $sql, [
                        ':email' => $email,
                        ':password' => $passwordHash
                    ]);
                    
                    if ($stmt && $user = $stmt->fetch()) {
                        setUserSession($user['id'], $user['name'], $user['Email'], $role);
                        
                        //Redirect based on role
                        switch ($role) {
                            case 'customer':
                                header('Location: pages/customer_dashboard.php');
                                break;
                            case 'technician':
                                header('Location: pages/technician_dashboard.php');
                                break;
                        }
                        exit;
                    } else {
                        $error = 'Invalid email or password.';
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}

//import header
include 'includes/header.php';
?>

<main class="container narrow">
    <h2>Login</h2>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" class="card" onsubmit="return validateLoginForm(this)">
        <label>
            <span>Email</span>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </label>
        
        <label>
            <span>Password</span>
            <input type="password" name="password" required>
        </label>
        
        <label>
            <span>Role</span>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="customer" <?php echo ($_POST['role'] ?? '') === 'customer' ? 'selected' : ''; ?>>Customer</option>
                <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="technician" <?php echo ($_POST['role'] ?? '') === 'technician' ? 'selected' : ''; ?>>Technician</option>
            </select>
        </label>
        
        <button type="submit" class="btn" id="loginBtn" data-original-text="Login">Login</button>
        
        <p class="muted">Don't have an account? <a href="register.php">Register here</a></p>
    </form>
</main>

<script>
function validateLoginForm(form) {
    const loginBtn = document.getElementById('loginBtn');
    
    if (!validateForm(form)) {
        return false;
    }
    
    setLoading(loginBtn, true);
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>
