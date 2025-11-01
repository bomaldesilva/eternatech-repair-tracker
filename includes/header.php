<?php
//Common Header for RepairShop
if (!isset($page_title)) {
    $page_title = 'ETERNATECH REPAIRS';
}

// Determine base path for assets
$base_path = '';
if (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) {
    $base_path = '../';
}

// Get current user data
$current_user = getCurrentUser();
$is_logged_in = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/svg+xml" href="<?php echo $base_path; ?>assets/images/favicon.svg">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/main.css?v=<?php echo time(); ?>">
    <script src="<?php echo $base_path; ?>assets/js/main.js?v=<?php echo time(); ?>"></script>
</head>
<body>
    <header class="container header">
        <a class="brand" href="<?php echo $base_path; ?>index.php">
            <img src="<?php echo $base_path; ?>assets/images/logo.svg" alt="ETERNATECH REPAIRS Logo" class="logo">
            <div class="brand-text">
                <span class="brand-name">ETERNATECH</span>
                <span class="brand-subtitle">REPAIRS</span>
            </div>
        </a>
        
        <nav>
            <?php if ($is_logged_in): ?>
                <span>Welcome, <?php echo htmlspecialchars($current_user['name']); ?>!</span>
                <?php if (hasRole('customer')): ?>
                    <a href="<?php echo $base_path; ?>pages/customer_dashboard.php">Dashboard</a>
                    <a href="<?php echo $base_path; ?>pages/submit_request.php">Submit Request</a>
                    <a href="<?php echo $base_path; ?>track_status.php">Track Status</a>
                    <a href="<?php echo $base_path; ?>pages/help.php">Help</a>
                <?php elseif (hasRole('admin')): ?>
                    <a href="<?php echo $base_path; ?>pages/admin_dashboard.php">Admin Dashboard</a>
                    <a href="<?php echo $base_path; ?>pages/add_staff.php">Add Staff</a>
                    <a href="<?php echo $base_path; ?>pages/assign_task.php">Assign Tasks</a>
                <?php elseif (hasRole('technician')): ?>
                    <a href="<?php echo $base_path; ?>pages/technician_dashboard.php">Dashboard</a>
                    <a href="<?php echo $base_path; ?>pages/update_status.php">Update Status</a>
                <!-- receptionist feature removed -->
                <?php endif; ?>
                <a href="<?php echo $base_path; ?>pages/my_account.php">My Account</a>
                <a href="<?php echo $base_path; ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>login.php">Login</a>
                <a href="<?php echo $base_path; ?>register.php">Register</a>
                <a href="<?php echo $base_path; ?>track_status.php">Track Status</a>
                <a href="<?php echo $base_path; ?>pages/help.php">Help</a>
            <?php endif; ?>
        </nav>
        
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-nav-overlay" onclick="closeMobileMenu()"></div>
    
    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <div class="mobile-nav-header">
            <div class="brand-text">
                <span class="brand-name">ETERNATECH</span>
                <span class="brand-subtitle">REPAIRS</span>
            </div>
            <button class="mobile-nav-close" onclick="closeMobileMenu()">×</button>
        </div>
        <div class="mobile-nav-links">
            <?php if ($is_logged_in): ?>
                <span>Welcome, <?php echo htmlspecialchars($current_user['name']); ?>!</span>
                <?php if (hasRole('customer')): ?>
                    <a href="<?php echo $base_path; ?>pages/customer_dashboard.php">Dashboard</a>
                    <a href="<?php echo $base_path; ?>pages/submit_request.php">Submit Request</a>
                    <a href="<?php echo $base_path; ?>track_status.php">Track Status</a>
                    <a href="<?php echo $base_path; ?>pages/help.php">Help</a>
                <?php elseif (hasRole('admin')): ?>
                    <a href="<?php echo $base_path; ?>pages/admin_dashboard.php">Admin Dashboard</a>
                    <a href="<?php echo $base_path; ?>pages/add_staff.php">Add Staff</a>
                    <a href="<?php echo $base_path; ?>pages/assign_task.php">Assign Tasks</a>
                <?php elseif (hasRole('technician')): ?>
                    <a href="<?php echo $base_path; ?>pages/technician_dashboard.php">Dashboard</a>
                    <a href="<?php echo $base_path; ?>pages/update_status.php">Update Status</a>
                <!-- receptionist feature removed -->
                <?php endif; ?>
                <a href="<?php echo $base_path; ?>pages/my_account.php">My Account</a>
                <a href="<?php echo $base_path; ?>logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo $base_path; ?>login.php">Login</a>
                <a href="<?php echo $base_path; ?>register.php">Register</a>
                <a href="<?php echo $base_path; ?>track_status.php">Track Status</a>
                <a href="<?php echo $base_path; ?>pages/help.php">Help</a>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Display flash messages
    $message = getMessage();
    if ($message):
    ?>
    <div class="container">
        <div class="msg <?php echo $message['type']; ?>">
            <?php echo htmlspecialchars($message['message']); ?>
        </div>
    </div>
    <?php endif; ?>
