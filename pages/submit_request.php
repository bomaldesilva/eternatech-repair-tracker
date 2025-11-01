<?php

require_once '../includes/session.php';
require_once '../config/database.php';

// Require customer login
requireLogin('../login.php');
requireRole('customer', '../index.php');

$page_title = 'Submit Repair Request - ETERNATECH REPAIRS';
$device_types = [];
$error = '';
$success = '';

//Get device types for dropdown
try {
    $sql = "SELECT DeviceId, Name FROM DeviceType ORDER BY Name";
    $stmt = executeQuery($pdo, $sql);
    if ($stmt) {
        $device_types = $stmt->fetchAll();
    }
} catch (Exception $e) {
    error_log('Device types error: ' . $e->getMessage());
}

//Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceId = sanitizeInput($_POST['device_id'] ?? '');
    $problemDescription = sanitizeInput($_POST['problem_description'] ?? '');
    $priority = sanitizeInput($_POST['priority'] ?? 'medium');
    
    if (empty($deviceId) || empty($problemDescription)) {
        $error = 'Device type and problem description are required.';
    } else {
        try {
            $userId = getCurrentUserId();
            $sql = "INSERT INTO Request (DeviceId, CustID, ProblemDescription, Priority, Status, FinalCost, DateSubmitted) 
                    VALUES (:deviceId, :custId, :problemDescription, :priority, :status, :finalCost, :dateSubmitted)";
            $stmt = executeQuery($pdo, $sql, [
                ':deviceId' => $deviceId,
                ':custId' => $userId,
                ':problemDescription' => $problemDescription,
                ':priority' => $priority,
                ':status' => 'pending',
                ':finalCost' => 0.00,
                ':dateSubmitted' => date('Y-m-d H:i:s')
            ]);
            
            if ($stmt) {
                //auto-generated RequestId
                $requestId = $pdo->lastInsertId();
                $success = "Request submitted successfully! Your request ID is: <strong>#$requestId</strong>. Please save this ID to track your repair status.";
                //Clear form
                $_POST = [];
            } else {
                $error = 'Failed to submit request. Please try again.';
            }
        } catch (Exception $e) {
            error_log('Submit request error: ' . $e->getMessage());
            $error = 'Failed to submit request. Please try again.';
        }
    }
}

$currentUser = getCurrentUser();

include '../includes/header.php';
?>

<main class="container narrow">
    <h2>Submit Repair Request</h2>
    <p>Fill out the form below to submit your device for repair.</p>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo $success; ?></div>
        <div class="actions" style="margin: 20px 0;">
            <a href="customer_dashboard.php" class="btn">View Dashboard</a>
            <a href="../track_status.php" class="btn btn-outline">Track Status</a>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="card" onsubmit="return validateSubmitForm(this)">
        <div style="background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: var(--accent);">Customer Information</h4>
            <p style="margin: 0;"><strong>Name:</strong> <?php echo htmlspecialchars($currentUser['name']); ?></p>
            <p style="margin: 5px 0 0 0;"><strong>Email:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
        </div>
        
        <label>
            <span>Device Type *</span>
            <select name="device_id" required>
                <option value="">Select Device Type</option>
                <?php foreach ($device_types as $device): ?>
                    <option value="<?php echo htmlspecialchars($device['DeviceId']); ?>" 
                            <?php echo ($_POST['device_id'] ?? '') == $device['DeviceId'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($device['Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        
        <label>
            <span>Problem Description *</span>
            <textarea name="problem_description" rows="5" required 
                      placeholder="Please describe the problem with your device in detail..."><?php echo htmlspecialchars($_POST['problem_description'] ?? ''); ?></textarea>
        </label>
        
        <label>
            <span>Priority Level</span>
            <select name="priority">
                <option value="low" <?php echo ($_POST['priority'] ?? 'medium') === 'low' ? 'selected' : ''; ?>>Low - No rush</option>
                <option value="medium" <?php echo ($_POST['priority'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Medium - Normal turnaround</option>
                <option value="high" <?php echo ($_POST['priority'] ?? 'medium') === 'high' ? 'selected' : ''; ?>>High - Urgent repair needed</option>
            </select>
        </label>
        
        <div style="background: rgba(79, 195, 247, 0.1); border: 1px solid var(--accent); border-radius: 8px; padding: 15px; margin: 20px 0;">
            <h4 style="margin: 0 0 10px 0; color: var(--accent);">What happens next?</h4>
            <ol style="margin: 0; padding-left: 20px; color: var(--muted);">
                <li>Your request will be reviewed by our team</li>
                <li>A technician will be assigned to your case</li>
                <li>You'll receive updates on the repair progress</li>
                <li>We'll notify you when your device is ready</li>
            </ol>
        </div>
        
        <button type="submit" class="btn" id="submitBtn" data-original-text="Submit Request">Submit Request</button>
        
        <p style="text-align: center; margin-top: 15px;">
            <a href="customer_dashboard.php" class="muted">← Back to Dashboard</a>
        </p>
    </form>
</main>

<script>
function validateSubmitForm(form) {
    const submitBtn = document.getElementById('submitBtn');
    const problemDescription = form.problem_description.value.trim();
    
    if (!validateForm(form)) {
        return false;
    }
    
    if (problemDescription.length < 10) {
        alert('Please provide a more detailed problem description (at least 10 characters).');
        form.problem_description.focus();
        return false;
    }
    
    setLoading(submitBtn, true);
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>
