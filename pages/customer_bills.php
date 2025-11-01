<?php

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');
requireRole('customer', '../index.php');

$page_title = 'My Bills - ETERNATECH REPAIRS';
$error = '';

$customerId = getCurrentUserId();

try {
    //all bills spec customer
    $billsSql = "SELECT b.*, r.RequestId, r.ProblemDescription, r.DateSubmitted, dt.Name as DeviceTypeName
                FROM bills b
                JOIN request r ON b.RequestId = r.RequestId
                LEFT JOIN devicetype dt ON r.DeviceId = dt.DeviceId
                WHERE r.CustID = ?
                ORDER BY b.UploadDate DESC";
    $billsStmt = $pdo->prepare($billsSql);
    $billsStmt->execute([$customerId]);
    $customerBills = $billsStmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Unable to load bills: ' . $e->getMessage();
    $customerBills = [];
}

include '../includes/header.php';
?>

<main class="container">
    <div class="page-header">
        <h2>Billing Information</h2>
        <a href="customer_dashboard.php" class="btn-outline">← Back to Dashboard</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($customerBills)): ?>
    <div class="card">
        <div class="empty-state">
            <h3>No Bills Available</h3>
            <p>You don't have any bills yet. Bills will appear here once your repair requests are completed and processed.</p>
            <a href="submit_request.php" class="btn">Submit New Request</a>
        </div>
    </div>
    <?php else: ?>
    
    <div class="card">
        <h3>Your Bills (<?php echo count($customerBills); ?>)</h3>
        <p style="color: var(--muted); margin-bottom: 20px;">View and download your repair bills</p>
        
        <div class="bills-grid">
            <?php foreach ($customerBills as $bill): ?>
            <div class="bill-card">
                <div class="bill-header">
                    <h4>Bill #<?php echo htmlspecialchars($bill['BillNumber']); ?></h4>
                    <span class="bill-status status-<?php echo strtolower($bill['Status']); ?>">
                        <?php echo ucfirst($bill['Status']); ?>
                    </span>
                </div>
                
                <div class="bill-details">
                    <div class="detail-row">
                        <span class="label">Request:</span>
                        <span class="value">#<?php echo $bill['RequestId']; ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label">Device:</span>
                        <span class="value"><?php echo htmlspecialchars($bill['DeviceTypeName'] ?? 'Unknown'); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label">Problem:</span>
                        <span class="value"><?php echo htmlspecialchars(substr($bill['ProblemDescription'], 0, 50)); ?>...</span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label">Service Date:</span>
                        <span class="value"><?php echo date('M j, Y', strtotime($bill['DateSubmitted'])); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="label">Bill Date:</span>
                        <span class="value"><?php echo date('M j, Y', strtotime($bill['UploadDate'])); ?></span>
                    </div>
                </div>
                
                <div class="bill-amount">
                    <div class="amount-breakdown">
                        <?php if ($bill['PartsTotal'] > 0): ?>
                        <div class="breakdown-item">
                            <span>Parts:</span>
                            <span>RS. <?php echo number_format($bill['PartsTotal'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($bill['LaborTotal'] > 0): ?>
                        <div class="breakdown-item">
                            <span>Labor:</span>
                            <span>RS. <?php echo number_format($bill['LaborTotal'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="breakdown-total">
                            <span>Total:</span>
                            <span>RS. <?php echo number_format($bill['TotalAmount'], 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($bill['Notes'])): ?>
                <div class="bill-notes">
                    <strong>Notes:</strong>
                    <p><?php echo htmlspecialchars($bill['Notes']); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="bill-actions">
                    <?php 
                    $pdfPath = $bill['FilePath'];
                    ?>
                    <a href="<?php echo htmlspecialchars($pdfPath); ?>" 
                       target="_blank" class="btn btn-primary">
                        View PDF Bill
                    </a>
                    <a href="<?php echo htmlspecialchars($pdfPath); ?>" 
                       download="<?php echo htmlspecialchars($bill['FileName']); ?>" 
                       class="btn btn-outline">
                        Download
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<style>
.bills-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.bill-card {
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 20px;
    background: var(--card);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.bill-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.bill-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
}

.bill-header h4 {
    margin: 0;
    color: var(--accent);
}

.bill-status {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-draft {
    background: #fff3cd;
    color: #856404;
}

.status-sent {
    background: #d1ecf1;
    color: #0c5460;
}

.status-paid {
    background: #d4edda;
    color: #155724;
}

.status-overdue {
    background: #f8d7da;
    color: #721c24;
}

.bill-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.detail-row .label {
    color: var(--muted);
    font-weight: 500;
}

.detail-row .value {
    color: var(--text);
}

.bill-amount {
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(79, 195, 247, 0.1);
    border-radius: 6px;
}

.amount-breakdown {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    color: var(--text);
}

.breakdown-total {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    font-size: 1.1em;
    color: var(--accent);
    padding-top: 8px;
    border-top: 1px solid rgba(79, 195, 247, 0.3);
}

.bill-notes {
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.bill-notes p {
    margin: 5px 0 0 0;
    color: var(--muted);
    font-style: italic;
}

.bill-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-primary {
    background: var(--accent);
    color: white;
    flex: 1;
}

.btn-outline {
    flex: 1;
}

@media (max-width: 768px) {
    .bills-grid {
        grid-template-columns: 1fr;
    }
    
    .bill-actions {
        flex-direction: column;
    }
    
    .btn-primary,
    .btn-outline {
        flex: none;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
