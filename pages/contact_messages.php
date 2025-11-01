<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';

if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Contact Messages - Admin';

if ($_POST['action'] ?? '' === 'update_status') {
    $message_id = (int) $_POST['message_id'];
    $new_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE contact_messages SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$new_status, $message_id]);
    
    $success_msg = "Message status updated successfully!";
}

if ($_POST['action'] ?? '' === 'delete_message') {
    $message_id = (int) $_POST['message_id'];
    
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$message_id]);
    
    $success_msg = "Message deleted successfully!";
}

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// messages
$stmt = $pdo->prepare("SELECT * FROM contact_messages $where_clause ORDER BY created_at DESC");
$stmt->execute($params);
$messages = $stmt->fetchAll();

//message counts for stats
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM contact_messages GROUP BY status");
$status_counts = [];
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}

$total_messages = array_sum($status_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <div class="header-content">
                <h1>Contact Messages</h1>
                <p class="header-subtitle">Manage customer inquiries and messages</p>
            </div>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="msg success"><?php echo $success_msg; ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_messages; ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number pending"><?php echo $status_counts['new'] ?? 0; ?></div>
                <div class="stat-label">New Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number progress"><?php echo $status_counts['read'] ?? 0; ?></div>
                <div class="stat-label">Read Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number completed"><?php echo $status_counts['replied'] ?? 0; ?></div>
                <div class="stat-label">Replied Messages</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <form method="GET" class="filters-form" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <label style="flex: 1; min-width: 200px;">
                    Search Messages:
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, subject...">
                </label>
                
                <label style="min-width: 150px;">
                    Status Filter:
                    <select name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Messages</option>
                        <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                    </select>
                </label>
                
                <button type="submit" class="btn">Filter</button>
                <a href="contact_messages.php" class="btn btn-outline">Clear</a>
            </form>
        </div>

        <!-- Messages List -->
        <?php if (empty($messages)): ?>
            <div class="empty-state-card">
                <div class="empty-icon">✉</div>
                <h3>No Messages Found</h3>
                <p>No contact messages match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="requests-grid">
                <?php foreach ($messages as $message): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div class="request-id">#<?php echo $message['id']; ?></div>
                            <span class="status-badge status-<?php echo $message['status']; ?>">
                                <?php echo ucfirst($message['status']); ?>
                            </span>
                        </div>
                        
                        <div class="request-body">
                            <div class="request-field">
                                <span class="field-label">Name:</span>
                                <span class="field-value"><?php echo htmlspecialchars($message['name']); ?></span>
                            </div>
                            
                            <div class="request-field">
                                <span class="field-label">Email:</span>
                                <span class="field-value">
                                    <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" style="color: var(--accent);">
                                        <?php echo htmlspecialchars($message['email']); ?>
                                    </a>
                                </span>
                            </div>
                            
                            <?php if ($message['phone']): ?>
                                <div class="request-field">
                                    <span class="field-label">Phone:</span>
                                    <span class="field-value">
                                        <a href="tel:<?php echo htmlspecialchars($message['phone']); ?>" style="color: var(--accent);">
                                            <?php echo htmlspecialchars($message['phone']); ?>
                                        </a>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($message['device_type']): ?>
                                <div class="request-field">
                                    <span class="field-label">Device:</span>
                                    <span class="field-value"><?php echo htmlspecialchars(ucfirst($message['device_type'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="request-field">
                                <span class="field-label">Subject:</span>
                                <span class="field-value"><?php echo htmlspecialchars($message['subject']); ?></span>
                            </div>
                            
                            <div class="request-field" style="flex-direction: column; align-items: flex-start;">
                                <span class="field-label">Message:</span>
                                <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 4px; margin-top: 5px; width: 100%; font-size: 13px; line-height: 1.4;">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                            </div>
                            
                            <div class="request-field">
                                <span class="field-label">Received:</span>
                                <span class="field-value"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="request-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <select name="status" onchange="this.form.submit()" class="btn btn-small" style="background: var(--accent); color: #000;">
                                    <option value="new" <?php echo $message['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="read" <?php echo $message['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                    <option value="replied" <?php echo $message['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                </select>
                            </form>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?')">
                                <input type="hidden" name="action" value="delete_message">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <button type="submit" class="btn btn-small" style="background: #f44336; color: white;">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
</body>
</html>
