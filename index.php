<?php

//getsession and database
require_once 'includes/session.php';
require_once 'config/database.php';

$page_title = 'ETERNATECH REPAIRS - Fast, Reliable Device Repairs';

//import header
include 'includes/header.php';
?>

<main class="container">
    <section class="hero">
        <h2>Fast, reliable device repairs</h2>
        <p>Submit a request, track your repair, and stay informed.</p>
        <div class="actions">
            <?php if (isLoggedIn()): ?>
                <?php if (hasRole('customer')): ?>
                    <a class="btn" href="pages/submit_request.php">Submit New Request</a>
                    <a class="btn btn-outline" href="pages/customer_dashboard.php">My Dashboard</a>
                <?php elseif (hasRole('admin')): ?>
                    <a class="btn" href="pages/admin_dashboard.php">Admin Dashboard</a>
                    <a class="btn btn-outline" href="pages/assign_task.php">Assign Tasks</a>
                <?php elseif (hasRole('technician')): ?>
                    <a class="btn" href="pages/technician_dashboard.php">My Dashboard</a>
                    <a class="btn btn-outline" href="pages/update_status.php">Update Status</a>
                <!-- receptionist feature removed -->
                <?php endif; ?>
            <?php else: ?>
                <a class="btn" href="register.php">Get Started</a>
                <a class="btn btn-outline" href="track_status.php">Track Repair</a>
            <?php endif; ?>
        </div>
    </section>

    <?php if (isLoggedIn()): ?>
    <section class="card">
        <h3>Quick Actions</h3>
        <div class="dashboard-grid">
            <?php if (hasRole('customer')): ?>
                <div class="data-card">
                    <div class="data-header">
                        <h4 class="data-title">Customer Services</h4>
                    </div>
                    <p>Manage your repair requests and track progress.</p>
                    <div class="data-actions">
                        <a href="pages/submit_request.php" class="btn btn-small">Submit Request</a>
                        <a href="pages/customer_dashboard.php" class="btn btn-small btn-secondary">View Requests</a>
                    </div>
                </div>
            <?php elseif (hasRole('admin')): ?>
                <div class="data-card">
                    <div class="data-header">
                        <h4 class="data-title">Admin Panel</h4>
                    </div>
                    <p>Manage staff, assign tasks, and monitor system.</p>
                    <div class="data-actions">
                        <a href="pages/add_staff.php" class="btn btn-small">Add Staff</a>
                        <a href="pages/admin_dashboard.php" class="btn btn-small btn-secondary">Dashboard</a>
                    </div>
                </div>
            <?php elseif (hasRole('technician')): ?>
                <div class="data-card">
                    <div class="data-header">
                        <h4 class="data-title">Technician Panel</h4>
                    </div>
                    <p>View assigned tasks and update repair status.</p>
                    <div class="data-actions">
                        <a href="pages/technician_dashboard.php" class="btn btn-small">My Tasks</a>
                        <a href="pages/update_status.php" class="btn btn-small btn-secondary">Update Status</a>
                    </div>
                </div>
            <!-- receptionist feature removed -->
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="card">
        <h3>Our Services</h3>
        <div class="dashboard-grid">
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title">Device Repair</h4>
                </div>
                <p>Professional repair services for laptops, desktops, smartphones, and tablets.</p>
            </div>
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title">Fast Turnaround</h4>
                </div>
                <p>Quick diagnosis and repair with real-time status tracking.</p>
            </div>
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title">Quality Parts</h4>
                </div>
                <p>We use only genuine and high-quality replacement parts.</p>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
