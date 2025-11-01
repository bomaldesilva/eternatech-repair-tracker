<?php
require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../admin_login.php');
requireRole('admin', '../index.php');

$page_title = 'Add Staff Member - ETERNATECH REPAIRS';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = sanitizeInput($_POST['role'] ?? '');
    $specialization = sanitizeInput($_POST['specialization'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword) || empty($role)) {
        $error = 'Name, email, password, and role are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!in_array($role, ['technician'])) {
        $error = 'Invalid role selected.';
    } else {
        try {
            $checkQueries = [
                "SELECT TechnicianId FROM Technician WHERE Email = :email",
                "SELECT AdminId FROM Admin WHERE Email = :email"
            ];
            
            $emailExists = false;
            foreach ($checkQueries as $checkSql) {
                $checkStmt = executeQuery($pdo, $checkSql, [':email' => $email]);
                if ($checkStmt && $checkStmt->fetch()) {
                    $emailExists = true;
                    break;
                }
            }
            
            if ($emailExists) {
                $error = 'An account with this email already exists.';
            } else {
                $passwordHash = hashPassword($password);
                if ($role === 'technician') {
                    $sql = "INSERT INTO Technician (Name, Email, PasswordHash, Speciality) 
                            VALUES (:name, :email, :password, :speciality)";
                    
                    $params = [
                        ':name' => $name,
                        ':email' => $email,
                        ':password' => $passwordHash,
                        ':speciality' => $specialization
                    ];
                } else {
                    $error = 'Invalid role selected.';
                }
                
                $stmt = executeQuery($pdo, $sql, $params);
                
                if ($stmt && $role === 'technician') {
                    $success = ucfirst($role) . " '{$name}' has been added successfully!";
                    // Clear
                    $_POST = [];
                } else {
                    $error = 'Failed to add staff member. Please try again.';
                }
            }
        } catch (Exception $e) {
            error_log('Add staff error: ' . $e->getMessage());
            $error = 'Failed to add staff member. Please try again.';
        }
    }
}

// Include header
include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Add Staff Member</h2>
        <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
        <div class="actions" style="margin: 20px 0;">
            <a href="admin_dashboard.php" class="btn">Back to Dashboard</a>
            <a href="add_staff.php" class="btn btn-outline">Add Another</a>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Staff Account Details</h3>
        
        <form method="POST" class="form" onsubmit="return validateStaffForm(this)">
            <div class="form-group">
                <label for="name">Full Name *</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       required 
                       placeholder="Enter staff member's full name">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       required 
                       placeholder="Enter email address">
                <small class="form-help">This will be used for login and system access</small>
            </div>
            
            <div class="form-group">
                <label for="role">Role *</label>
                <select id="role" name="role" required onchange="toggleSpecialization(this.value)">
                    <option value="">Select Role</option>
                    <option value="technician" <?php echo ($_POST['role'] ?? '') === 'technician' ? 'selected' : ''; ?>>Technician</option>
                </select>
                <small class="form-help">Staff role determines system permissions and access level</small>
            </div>
            
            <div id="specializationField" class="form-group" style="display: none;">
                <label for="specialization">Specialization</label>
                <input type="text" 
                       id="specialization" 
                       name="specialization" 
                       value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>"
                       placeholder="e.g., Laptop Repair, Mobile Devices, Hardware Diagnostics">
                <small class="form-help">Technical expertise area for task assignment</small>
            </div>
            
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       minlength="6"
                       placeholder="Enter temporary password (minimum 6 characters)">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       required 
                       minlength="6"
                       placeholder="Confirm the password">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="addStaffBtn" data-original-text="Add Staff Member">Add Staff Member</button>
                <a href="admin_dashboard.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
    
    <div class="card info-card">
        <h4>👷 Staff Access Information</h4>
        <ul>
            <li><strong>Technicians:</strong> View assigned tasks, update repair status, and manage work orders</li>
            <li><strong>Login:</strong> Staff members will use their email and the password you set</li>
            <li><strong>Security:</strong> They can change their password after first login</li>
            <li><strong>Specialization:</strong> Helps with appropriate task assignment</li>
        </ul>
    </div>
</main>

<script>
function validateStaffForm(form) {
    const addBtn = document.getElementById('addStaffBtn');
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
    
    setLoading(addBtn, true);
    return true;
}

function toggleSpecialization(role) {
    const specializationField = document.getElementById('specializationField');
    if (role === 'technician') {
        specializationField.style.display = 'block';
    } else {
        specializationField.style.display = 'none';
    }
}

// Show specialization field if technician is already selected
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.querySelector('select[name="role"]');
    if (roleSelect.value === 'technician') {
        toggleSpecialization('technician');
    }
});
</script>

<style>
.info-card {
    margin-top: 20px;
    background: #f8f9fa;
    border-left: 4px solid #17a2b8;
}

.info-card h4 {
    color: #17a2b8;
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
