<?php

require_once '../includes/session.php';
require_once '../config/database.php';


requireLogin('../login.php');
requireRole('admin', '../index.php');

$page_title = 'Parts Usage Report - ETERNATECH REPAIRS';

$filterPeriod = $_GET['period'] ?? '30';
$filterRequest = $_GET['request_id'] ?? '';
$filterPart = $_GET['part_id'] ?? '';

$whereConditions = [];
$params = [];

if ($filterPeriod && $filterPeriod !== 'all') {
    $whereConditions[] = "pu.DateUsed >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = intval($filterPeriod);
}

if ($filterRequest) {
    $whereConditions[] = "pu.RequestID = ?";
    $params[] = intval($filterRequest);
}

if ($filterPart) {
    $whereConditions[] = "pu.PartId = ?";
    $params[] = intval($filterPart);
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

try {
    $partsSql = "SELECT pu.*, r.RequestId, r.ProblemDescription, r.Status, r.DateSubmitted,
                       c.CustName, c.Email as CustomerEmail,
                       p.Name as PartName, p.PartNumber, p.Description as PartDescription,
                       dt.Name as DeviceTypeName
                FROM partsused pu
                JOIN request r ON pu.RequestID = r.RequestId
                JOIN customer c ON r.CustID = c.CustID
                LEFT JOIN parts p ON pu.PartId = p.PartId
                LEFT JOIN devicetype dt ON r.DeviceId = dt.DeviceId
                $whereClause
                ORDER BY pu.DateUsed DESC";
    
    $stmt = $pdo->prepare($partsSql);
    $stmt->execute($params);
    $partsUsage = $stmt->fetchAll();
//getStatSummary
    $statsSql = "SELECT 
                   COUNT(*) as total_usage_records,
                   SUM(pu.Quantity) as total_parts_used,
                   SUM(pu.Cost) as total_parts_cost,
                   COUNT(DISTINCT pu.RequestID) as requests_with_parts,
                   COUNT(DISTINCT pu.PartId) as unique_parts_used
                 FROM partsused pu
                 JOIN request r ON pu.RequestID = r.RequestId
                 $whereClause";
    
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch();
    
    //Get most used parts
    $topPartsSql = "SELECT p.Name as PartName, p.PartNumber, 
                          SUM(pu.Quantity) as total_quantity,
                          SUM(pu.Cost) as total_cost,
                          COUNT(*) as usage_count
                   FROM partsused pu
                   LEFT JOIN parts p ON pu.PartId = p.PartId
                   JOIN request r ON pu.RequestID = r.RequestId
                   $whereClause
                   GROUP BY pu.PartId, p.Name, p.PartNumber
                   ORDER BY total_quantity DESC
                   LIMIT 10";
    
    $topPartsStmt = $pdo->prepare($topPartsSql);
    $topPartsStmt->execute($params);
    $topParts = $topPartsStmt->fetchAll();
    
    // Get all parts for dropdown
    $allPartsSql = "SELECT PartId, Name, PartNumber FROM parts WHERE IsActive = 1 ORDER BY Name";
    $allPartsStmt = $pdo->prepare($allPartsSql);
    $allPartsStmt->execute();
    $allParts = $allPartsStmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Unable to load parts usage data: ' . $e->getMessage();
    $partsUsage = [];
    $stats = [];
    $topParts = [];
    $allParts = [];
}

include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2>Parts Usage Report</h2>
            <p style="color: var(--muted); margin: 5px 0;">Detailed analysis of parts usage across repair requests</p>
        </div>
        <div>
            <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
            <a href="manage_parts.php" class="btn" style="background: #4CAF50;">Manage Parts</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Filters</h3>
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: end;">
            <label>
                <span>Time Period</span>
                <select name="period">
                    <option value="7" <?php echo $filterPeriod == '7' ? 'selected' : ''; ?>>Last 7 days</option>
                    <option value="30" <?php echo $filterPeriod == '30' ? 'selected' : ''; ?>>Last 30 days</option>
                    <option value="90" <?php echo $filterPeriod == '90' ? 'selected' : ''; ?>>Last 90 days</option>
                    <option value="365" <?php echo $filterPeriod == '365' ? 'selected' : ''; ?>>Last year</option>
                    <option value="all" <?php echo $filterPeriod == 'all' ? 'selected' : ''; ?>>All time</option>
                </select>
            </label>
            
            <label>
                <span>Specific Request</span>
                <input type="number" name="request_id" value="<?php echo htmlspecialchars($filterRequest); ?>" placeholder="Request ID">
            </label>
            
            <label>
                <span>Specific Part</span>
                <select name="part_id">
                    <option value="">All parts</option>
                    <?php foreach ($allParts as $part): ?>
                    <option value="<?php echo $part['PartId']; ?>" <?php echo $filterPart == $part['PartId'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($part['Name']); ?>
                        <?php if ($part['PartNumber']): ?>
                        (<?php echo htmlspecialchars($part['PartNumber']); ?>)
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </label>
            
            <button type="submit" class="btn">Apply Filters</button>
        </form>
    </div>

    <!-- Summary Statistics -->
    <?php if (!empty($stats)): ?>
    <div class="dashboard-grid" style="margin-bottom: 30px;">
        <div class="data-card" style="border-left: 4px solid #2196F3;">
            <div class="data-header">
                <h4 class="data-title">Total Usage Records</h4>
                <span class="data-value"><?php echo $stats['total_usage_records'] ?? 0; ?></span>
            </div>
        </div>
        
        <div class="data-card" style="border-left: 4px solid #4CAF50;">
            <div class="data-header">
                <h4 class="data-title">Parts Used</h4>
                <span class="data-value"><?php echo $stats['total_parts_used'] ?? 0; ?></span>
            </div>
        </div>
        
        <div class="data-card" style="border-left: 4px solid #FF9800;">
            <div class="data-header">
                <h4 class="data-title">Total Cost</h4>
                <span class="data-value">RS. <?php echo number_format($stats['total_parts_cost'] ?? 0, 2); ?></span>
            </div>
        </div>
        
        <div class="data-card" style="border-left: 4px solid #9C27B0;">
            <div class="data-header">
                <h4 class="data-title">Requests with Parts</h4>
                <span class="data-value"><?php echo $stats['requests_with_parts'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Most Used Parts -->
    <?php if (!empty($topParts)): ?>
    <div class="card" style="margin-bottom: 30px;">
        <h3>Most Used Parts</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #333; color: white;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Part Name</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Part Number</th>
                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #555;">Total Quantity</th>
                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #555;">Times Used</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #555;">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topParts as $part): ?>
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 12px;">
                            <strong><?php echo htmlspecialchars($part['PartName']); ?></strong>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($part['PartNumber'] ?? 'N/A'); ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php echo $part['total_quantity']; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php echo $part['usage_count']; ?>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            RS. <?php echo number_format($part['total_cost'], 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Detailed Usage Records -->
    <div class="card">
        <h3>Detailed Usage Records (<?php echo count($partsUsage); ?>)</h3>
        
        <?php if (empty($partsUsage)): ?>
        <div class="empty-state">
            <h4>No Parts Usage Found</h4>
            <p>No parts usage records match your current filters.</p>
        </div>
        <?php else: ?>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #333; color: white;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Request</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Customer</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Device</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Part Used</th>
                        <th style="padding: 12px; text-align: center; border-bottom: 2px solid #555;">Qty</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #555;">Unit Cost</th>
                        <th style="padding: 12px; text-align: right; border-bottom: 2px solid #555;">Total Cost</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #555;">Date Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partsUsage as $usage): ?>
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 12px;">
                            <strong>#<?php echo $usage['RequestId']; ?></strong>
                            <br><small style="color: #666;"><?php echo htmlspecialchars(substr($usage['ProblemDescription'], 0, 30)); ?>...</small>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($usage['CustName']); ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($usage['CustomerEmail']); ?></small>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo htmlspecialchars($usage['DeviceTypeName'] ?? 'Unknown'); ?>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?php echo htmlspecialchars($usage['PartName']); ?></strong>
                            <?php if (!empty($usage['PartNumber'])): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($usage['PartNumber']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php echo $usage['Quantity']; ?>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            RS. <?php echo number_format($usage['UnitCost'], 2); ?>
                        </td>
                        <td style="padding: 12px; text-align: right;">
                            RS. <?php echo number_format($usage['Cost'], 2); ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php echo date('M j, Y', strtotime($usage['DateUsed'])); ?>
                            <br><small style="color: #666;"><?php echo date('g:i A', strtotime($usage['DateUsed'])); ?></small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
