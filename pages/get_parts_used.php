<?php

require_once '../includes/session.php';
require_once '../config/database.php';

requireLogin('../login.php');
requireRole('admin', '../index.php');

$requestId = intval($_GET['request_id'] ?? 0);

if ($requestId <= 0) {
    echo '<p style="color: red;">Invalid request ID.</p>';
    exit;
}

try {
    //select * parts used for this request
    $partsSql = "SELECT pu.*, p.Name as PartName, p.PartNumber, p.Description
                FROM partsused pu
                LEFT JOIN parts p ON pu.PartId = p.PartId
                WHERE pu.RequestID = ?
                ORDER BY pu.DateUsed DESC";
    $partsStmt = $pdo->prepare($partsSql);
    $partsStmt->execute([$requestId]);
    $partsUsed = $partsStmt->fetchAll();
    
    //Get request detls
    $requestSql = "SELECT r.*, c.CustName, dt.Name as DeviceTypeName
                  FROM request r
                  JOIN customer c ON r.CustID = c.CustID
                  LEFT JOIN devicetype dt ON r.DeviceId = dt.DeviceId
                  WHERE r.RequestId = ?";
    $requestStmt = $pdo->prepare($requestSql);
    $requestStmt->execute([$requestId]);
    $request = $requestStmt->fetch();
    
    if (!$request) {
        echo '<p style="color: red;">Request not found.</p>';
        exit;
    }
    
    echo '<div style="background: #494b4dff; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
    echo '<h4>Request Information</h4>';
    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
    echo '<div><strong>Customer:</strong> ' . htmlspecialchars($request['CustName']) . '</div>';
    echo '<div><strong>Device:</strong> ' . htmlspecialchars($request['DeviceTypeName'] ?? 'Unknown') . '</div>';
    echo '<div><strong>Problem:</strong> ' . htmlspecialchars(substr($request['ProblemDescription'], 0, 100)) . '</div>';
    echo '<div><strong>Status:</strong> ' . htmlspecialchars(ucfirst($request['Status'])) . '</div>';
    echo '</div>';
    echo '</div>';
    
    if (empty($partsUsed)) {
        echo '<div style="text-align: center; padding: 20px;">';
        echo '<h4>No Parts Used</h4>';
        echo '<p>No parts were recorded for this repair request.</p>';
        echo '</div>';
    } else {
        $totalCost = 0;
        
        echo '<h4>Parts Used (' . count($partsUsed) . ' items)</h4>';
        echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
        echo '<thead>';
        echo '<tr style="background: #333; color: white;">';
        echo '<th style="padding: 10px; text-align: left; border: 1px solid #555;">Part Name</th>';
        echo '<th style="padding: 10px; text-align: left; border: 1px solid #555;">Part Number</th>';
        echo '<th style="padding: 10px; text-align: center; border: 1px solid #555;">Quantity</th>';
        echo '<th style="padding: 10px; text-align: right; border: 1px solid #555;">Unit Cost</th>';
        echo '<th style="padding: 10px; text-align: right; border: 1px solid #555;">Total Cost</th>';
        echo '<th style="padding: 10px; text-align: left; border: 1px solid #555;">Date Used</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($partsUsed as $part) {
            $partTotalCost = $part['UnitCost'] * $part['Quantity'];
            $totalCost += $partTotalCost;
            
            echo '<tr>';
            echo '<td style="padding: 10px; border: 1px solid #ddd;">';
            echo '<strong>' . htmlspecialchars($part['PartName'] ?? 'Unknown Part') . '</strong>';
            if (!empty($part['Description'])) {
                echo '<br><small style="color: #666;">' . htmlspecialchars(substr($part['Description'], 0, 50)) . '</small>';
            }
            echo '</td>';
            echo '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($part['PartNumber'] ?? 'N/A') . '</td>';
            echo '<td style="padding: 10px; text-align: center; border: 1px solid #ddd;">' . $part['Quantity'] . '</td>';
            echo '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">RS. ' . number_format($part['UnitCost'], 2) . '</td>';
            echo '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">RS. ' . number_format($partTotalCost, 2) . '</td>';
            echo '<td style="padding: 10px; border: 1px solid #ddd;">' . date('M j, Y g:i A', strtotime($part['DateUsed'])) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '<tfoot>';
        echo '<tr style="background: #444; color: white; font-weight: bold;">';
        echo '<td colspan="4" style="padding: 10px; text-align: right; border: 1px solid #555;">Total Parts Cost:</td>';
        echo '<td style="padding: 10px; text-align: right; border: 1px solid #555;">RS. ' . number_format($totalCost, 2) . '</td>';
        echo '<td style="padding: 10px; border: 1px solid #555;"></td>';
        echo '</tr>';
        echo '</tfoot>';
        echo '</table>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
