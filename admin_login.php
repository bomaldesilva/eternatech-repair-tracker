<?php

// Include session management and database
require_once 'includes/session.php';
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    switch ($role) {
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

$page_title = 'Staff Login - ETERNATECH REPAIRS';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitizeInput($_POST['identifier'] ?? ''); // Username/Email
    $password = $_POST['password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? '');

    if (empty($identifier) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } else {
         $passwordHash = hashPassword($password);
        try {
            $user = null;
            $tableName = '';
            $idField = '';
            $nameField = '';
            $loginField = '';

            //Determine table and fields based on role

            switch ($role) {
                case 'admin':
                    $tableName = 'Admin';
                    $idField = 'AdminId';
                    $nameField = 'Name';
                    $loginField = 'Username'; // Admin uses username
                    break;
                case 'technician':
                    $tableName = 'Technician';
                    $idField = 'TechnicianId';
                    $nameField = 'Name';
                    $loginField = 'Email'; // Technician uses email
                    break;
                default:
                    $error = 'Invalid role selected.';
                    break;
            }

            if (empty($error)) {
                if ($role === 'technician') {
                    //Technician table doesn't have Username column
                    // $sql = "SELECT $idField as id, $nameField as name, Email, PasswordHash 
                    //         FROM $tableName 
                    //         WHERE $loginField = :email  AND PasswordHash = :password";
                             $sql = "SELECT $idField as id, $nameField as name, Email, PasswordHash 
                            FROM $tableName 
                            WHERE Email = :email AND PasswordHash = :password";
                              $stmt = executeQuery($pdo, $sql, [
                            ':email' => $identifier,
                        ':password' => $passwordHash
                    ]);

                    //    $sql = "SELECT $idField as id, $nameField as name, Email, PasswordHash 
                    //         FROM $tableName 
                    //         WHERE Email = :email AND PasswordHash = :password";
                    
                    // $stmt = executeQuery($pdo, $sql, [
                    //     ':email' => $email,
                    //     ':password' => $passwordHash
                    // ]);

                } else {
                    //Admin table - check both Username and Email fields
                    $sql = "SELECT $idField as id, $nameField as name, Email, PasswordHash, Username 
                            FROM $tableName 
                            WHERE Username = :username OR Email = :email";
                      $stmt = executeQuery($pdo, $sql, [':username' => $identifier, ':email' => $identifier]);
                }
                // $stmt = executeQuery($pdo, $sql, [':username' => $identifier, ':email' => $identifier]);

                if ($stmt && $user = $stmt->fetch()) {
                    $passwordMatch = false;

                    error_log("Admin login attempt - User found: " . $user['name'] . " (ID: " . $user['id'] . ")");
                    error_log("Password provided: '$password', Hash stored: '" . substr($user['PasswordHash'], 0, 20) . "...'");

                    // Check password based on role
                    if ($role === 'admin') {
                        // Admin password check - handle multiple formats
                        if (password_verify($password, $user['PasswordHash'])) {
                            // Modern password_hash() format (starts with $2y$)
                            $passwordMatch = true;
                            error_log("Password matched using password_verify()");
                        } else if ($password === $user['PasswordHash']) {
                            // Plain text password match (legacy format)
                            $passwordMatch = true;
                            error_log("Password matched using plain text comparison");
                        } else if (hash('sha256', $password) === $user['PasswordHash']) {
                            // SHA256 hash format (used by hashPassword() function)
                            $passwordMatch = true;
                            error_log("Password matched using SHA256 hash");
                        } else {
                            error_log("Password verification failed - no match found");
                        }
                    } else {
                        // Technician hashed password check
                        $passwordMatch = ($user['PasswordHash'] === hashPassword($password));
                    }

                    if ($passwordMatch) {
                        // Set session data
                        setUserSession($user['id'], $user['name'], $user['Email'], $role);

                        // Redirect based on role
                        switch ($role) {
                            case 'admin':
                                setMessage('Welcome back, Admin!', 'success');
                                header('Location: pages/admin_dashboard.php');
                                break;
                            case 'technician':
                                setMessage('Welcome back!', 'success');
                                header('Location: pages/technician_dashboard.php');
                                break;
                        }
                        exit;
                    } else {
                        $error = 'Invalid credentials1.';
                        error_log("Login failed for identifier '$identifier' - password mismatch");
                    }
                } else {
                    $error = 'Invalid credentials2.';
                    error_log("Login failed for identifier '$identifier' - user not found or query failed");
                    if (!$stmt) {
                        error_log("Database query failed for identifier '$identifier'");
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Staff login error: ' . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<style>
    body {
        background: linear-gradient(135deg, #000000ff 60%, #00060fff 90%, #081920ff 100%);
        background-attachment: fixed;
        min-height: 100vh;
        position: relative;
    }

    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image:
            radial-gradient(circle at 20% 20%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba(255, 119, 85, 0.3) 0%, transparent 50%),
            radial-gradient(circle at 40% 40%, rgba(120, 219, 226, 0.3) 0%, transparent 50%);
        background-size: 200% 200%, 150% 150%, 180% 180%;
        background-position: -50% -50%, 150% 150%, 50% 50%;
        animation: hardwareFloat 20s ease-in-out infinite;
        z-index: -2;
    }

    body::after {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 139, 0.6);
        z-index: -1;
    }

    @keyframes hardwareFloat {

        0%,
        100% {
            background-position: -50% -50%, 150% 150%, 50% 50%;
        }

        33% {
            background-position: 0% 0%, 100% 100%, 30% 70%;
        }

        66% {
            background-position: 50% 50%, 200% 50%, 70% 30%;
        }
    }

    .container.narrow {
        position: relative;
        z-index: 10;
    }

    .card {
        background: rgba(18, 19, 24, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(79, 195, 247, 0.3);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    h2 {
        color: #4fc3f7;
        text-align: center;
        margin-bottom: 1.5rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }
</style>

<main class="container narrow">
    <h2>Staff Login</h2>

    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="card" onsubmit="return validateStaffLoginForm(this)">
        <label>
            <span>Role</span>
            <select name="role" required onchange="updateLoginFields(this.value)">
                <option value="">Select Role</option>
                <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="technician" <?php echo ($_POST['role'] ?? '') === 'technician' ? 'selected' : ''; ?>>Technician</option>
            </select>
        </label>

        <label>
            <span id="identifierLabel">Username/Email</span>
            <input type="text" name="identifier" required value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                placeholder="Enter your username or email" id="identifierInput">
        </label>

        <label>
            <span>Password</span>
            <input type="password" name="password" required>
        </label>

        <button type="submit" class="btn" id="staffLoginBtn" data-original-text="Login">Login</button>

        <p class="muted">Customer? <a href="login.php">Customer login</a></p>
    </form>
</main>

<script>
    function updateLoginFields(role) {
        const identifierLabel = document.getElementById('identifierLabel');
        const identifierInput = document.getElementById('identifierInput');

        if (role === 'admin') {
            identifierLabel.textContent = 'Username';
            identifierInput.placeholder = 'Enter your username';
            identifierInput.type = 'text';
        } else if (role === 'technician') {
            identifierLabel.textContent = 'Email';
            identifierInput.placeholder = 'Enter your email';
            identifierInput.type = 'email';
        } else {
            identifierLabel.textContent = 'Username/Email';
            identifierInput.placeholder = 'Enter your username or email';
            identifierInput.type = 'text';
        }
    }

    function validateStaffLoginForm(form) {
        const loginBtn = document.getElementById('staffLoginBtn');

        if (!validateForm(form)) {
            return false;
        }

        setLoading(loginBtn, true);
        return true;
    }

    // Update fields on page load if role is selected
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.querySelector('select[name="role"]');
        if (roleSelect.value) {
            updateLoginFields(roleSelect.value);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>