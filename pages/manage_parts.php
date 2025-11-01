<?php
//Parts Management - Admin Only



require_once '../includes/session.php';
require_once '../config/database.php';


requireLogin('../login.php');
requireRole('admin', '../index.php');

$page_title = 'Parts Management - ETERNATECH REPAIRS';
$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $name = sanitizeInput($_POST['name'] ?? '');
            $partNumber = sanitizeInput($_POST['part_number'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $costPerUnit = floatval($_POST['cost_per_unit'] ?? 0);
            $pricePerUnit = floatval($_POST['price_per_unit'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);
            $minimumStock = intval($_POST['minimum_stock'] ?? 5);
            
            if (empty($name)) {
                throw new Exception('Part name is required');
            }
            //createNewPart
            $sql = "INSERT INTO Parts (Name, PartNumber, Description, CostPerUnit, PricePerUnit, Quantity, MinimumStock, IsActive) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $partNumber, $description, $costPerUnit, $pricePerUnit, $quantity, $minimumStock]);
            
            $success = "Part added successfully!";
            
        } elseif ($action === 'update') {
            $partId = intval($_POST['part_id'] ?? 0);
            $name = sanitizeInput($_POST['name'] ?? '');
            $partNumber = sanitizeInput($_POST['part_number'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $costPerUnit = floatval($_POST['cost_per_unit'] ?? 0);
            $pricePerUnit = floatval($_POST['price_per_unit'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);
            $minimumStock = intval($_POST['minimum_stock'] ?? 5);
            
            if (empty($name) || $partId <= 0) {
                throw new Exception('Part name and valid part ID are required');
            }
            //updatePart
            $sql = "UPDATE Parts SET Name = ?, PartNumber = ?, Description = ?, CostPerUnit = ?, 
                    PricePerUnit = ?, Quantity = ?, MinimumStock = ? WHERE PartId = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $partNumber, $description, $costPerUnit, $pricePerUnit, $quantity, $minimumStock, $partId]);
            
            $success = "Part updated successfully!";
            
        } elseif ($action === 'delete') {
            $partId = intval($_POST['part_id'] ?? 0);
            
            if ($partId <= 0) {
                throw new Exception('Valid part ID is required');
            }
            
            // Check if part is used in repairs
            $usageCheck = $pdo->prepare("SELECT COUNT(*) as count FROM PartsUsed WHERE PartId = ?");
            $usageCheck->execute([$partId]);
            $usage = $usageCheck->fetch();
            
            if ($usage['count'] > 0) {
                //deactivate
                $sql = "UPDATE Parts SET IsActive = 0 WHERE PartId = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$partId]);
                $success = "Part deactivated (cannot delete as it's used in repairs)";
            } else {
                //deletePart
                $sql = "DELETE FROM Parts WHERE PartId = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$partId]);
                $success = "Part deleted successfully!";
            }
        } elseif ($action === 'adjust_stock') {
            $partId = intval($_POST['part_id'] ?? 0);
            $adjustment = intval($_POST['adjustment'] ?? 0);
            $reason = sanitizeInput($_POST['reason'] ?? '');
            
            if ($partId <= 0 || $adjustment == 0) {
                throw new Exception('Valid part ID and adjustment amount are required');
            }
            
            $sql = "UPDATE Parts SET Quantity = Quantity + ? WHERE PartId = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$adjustment, $partId]);
            
            $success = "Stock adjusted by $adjustment. Reason: " . ($reason ?: 'No reason provided');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// select * from parts...
try {
    $sql = "SELECT * FROM Parts ORDER BY IsActive DESC, Name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $all_parts = $stmt->fetchAll();
    
    //Get low stock parts
    $lowStockSql = "SELECT * FROM Parts WHERE IsActive = 1 AND Quantity <= MinimumStock ORDER BY Quantity ASC";
    $lowStockStmt = $pdo->prepare($lowStockSql);
    $lowStockStmt->execute();
    $low_stock_parts = $lowStockStmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Unable to load parts data: ' . $e->getMessage();
}

include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Parts Management</h2>
        <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Low Stock Alert-->
    <?php if (!empty($low_stock_parts)): ?>
    <div class="card" style="border-left: 4px solid #ff9800; margin-bottom: 30px;">
        <h3 style="color: #ff9800;">⚠ Low Stock Alert</h3>
        <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
            <?php foreach ($low_stock_parts as $part): ?>
            <div class="data-card" style="border-left: 3px solid #ff4444;">
                <h4><?php echo htmlspecialchars($part['Name']); ?></h4>
                <p><strong>Current Stock:</strong> <?php echo $part['Quantity']; ?></p>
                <p><strong>Minimum Stock:</strong> <?php echo $part['MinimumStock']; ?></p>
                <button onclick="adjustStock(<?php echo $part['PartId']; ?>, '<?php echo htmlspecialchars($part['Name']); ?>')" class="btn btn-small">Adjust Stock</button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add New Part -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Add New Part</h3>
        <form method="POST" onsubmit="return validatePartForm(this)">
            <input type="hidden" name="action" value="add">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <label>
                    <span>Part Name *</span>
                    <input type="text" name="name" required placeholder="e.g., LCD Screen">
                </label>
                
                <label>
                    <span>Part Number</span>
                    <input type="text" name="part_number" placeholder="e.g., LCD-001">
                </label>
                
                <label>
                    <span>Cost Per Unit</span>
                    <input type="number" name="cost_per_unit" step="0.01" min="0" placeholder="0.00 (RS.)">
                </label>
                
                <label>
                    <span>Price Per Unit</span>
                    <input type="number" name="price_per_unit" step="0.01" min="0" placeholder="0.00 (RS.)">
                </label>
                
                <label>
                    <span>Initial Quantity</span>
                    <input type="number" name="quantity" min="0" value="0">
                </label>
                
                <label>
                    <span>Minimum Stock Level</span>
                    <input type="number" name="minimum_stock" min="1" value="5">
                </label>
            </div>
            
            <label>
                <span>Description</span>
                <textarea name="description" rows="3" placeholder="Optional description of the part"></textarea>
            </label>
            
            <button type="submit" class="btn">Add Part</button>
        </form>
    </div>

    <!-- Parts List -->
    <div class="card">
        <h3>All Parts</h3>
        
        <?php if (empty($all_parts)): ?>
        <div class="empty-state">
            <h3>No Parts Found</h3>
            <p>Add your first part using the form above.</p>
        </div>
        <?php else: ?>
        
        <div class="table-responsive">
            <table class="parts-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Part Number</th>
                        <th>Stock</th>
                        <th>Cost</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_parts as $part): ?>
                    <tr class="<?php echo !$part['IsActive'] ? 'inactive' : ($part['Quantity'] <= $part['MinimumStock'] ? 'low-stock' : ''); ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($part['Name']); ?></strong>
                            <?php if ($part['Description']): ?>
                            <br><small style="color: var(--muted);"><?php echo htmlspecialchars(substr($part['Description'], 0, 50)) . (strlen($part['Description']) > 50 ? '...' : ''); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($part['PartNumber'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="<?php echo $part['Quantity'] <= $part['MinimumStock'] ? 'text-warning' : ''; ?>">
                                <?php echo $part['Quantity']; ?>
                            </span>
                            <small style="color: var(--muted);">(min: <?php echo $part['MinimumStock']; ?>)</small>
                        </td>
                        <td>RS. <?php echo number_format($part['CostPerUnit'], 2); ?></td>
                        <td>RS. <?php echo number_format($part['PricePerUnit'], 2); ?></td>
                        <td>
                            <span class="status-<?php echo $part['IsActive'] ? 'active' : 'inactive'; ?>">
                                <?php echo $part['IsActive'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="parts-actions">
                            <button onclick="editPart(<?php echo htmlspecialchars(json_encode($part)); ?>)" class="btn-edit">Edit</button>
                            <button onclick="adjustStock(<?php echo $part['PartId']; ?>, '<?php echo htmlspecialchars($part['Name']); ?>')" class="btn-edit">Stock</button>
                            <?php if ($part['IsActive']): ?>
                            <button onclick="deletePart(<?php echo $part['PartId']; ?>, '<?php echo htmlspecialchars($part['Name']); ?>')" class="btn-delete">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Edit Part Modal -->
<div id="editPartModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Part</h3>
        <form method="POST" onsubmit="return validatePartForm(this)">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="part_id" id="editPartId">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <label>
                    <span>Part Name *</span>
                    <input type="text" name="name" id="editName" required>
                </label>
                
                <label>
                    <span>Part Number</span>
                    <input type="text" name="part_number" id="editPartNumber">
                </label>
                
                <label>
                    <span>Cost Per Unit</span>
                    <input type="number" name="cost_per_unit" id="editCostPerUnit" step="0.01" min="0">
                </label>
                
                <label>
                    <span>Price Per Unit</span>
                    <input type="number" name="price_per_unit" id="editPricePerUnit" step="0.01" min="0">
                </label>
                
                <label>
                    <span>Quantity</span>
                    <input type="number" name="quantity" id="editQuantity" min="0">
                </label>
                
                <label>
                    <span>Minimum Stock Level</span>
                    <input type="number" name="minimum_stock" id="editMinimumStock" min="1">
                </label>
            </div>
            
            <label>
                <span>Description</span>
                <textarea name="description" id="editDescription" rows="3"></textarea>
            </label>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn">Update Part</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div id="stockAdjustModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeStockModal()">&times;</span>
        <h3>Adjust Stock</h3>
        <p id="stockPartName"></p>
        <form method="POST">
            <input type="hidden" name="action" value="adjust_stock">
            <input type="hidden" name="part_id" id="stockPartId">
            
            <label>
                <span>Adjustment (+ to add, - to remove)</span>
                <input type="number" name="adjustment" required placeholder="e.g., +10 or -5">
            </label>
            
            <label>
                <span>Reason</span>
                <input type="text" name="reason" placeholder="e.g., New shipment, Damaged items">
            </label>
            
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn">Adjust Stock</button>
                <button type="button" onclick="closeStockModal()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function validatePartForm(form) {
    const name = form.querySelector('input[name="name"]').value.trim();
    const pricePerUnit = parseFloat(form.querySelector('input[name="price_per_unit"]').value || 0);
    const costPerUnit = parseFloat(form.querySelector('input[name="cost_per_unit"]').value || 0);
    
    if (!name) {
        alert('Part name is required.');
        return false;
    }
    
    if (pricePerUnit > 0 && costPerUnit > 0 && pricePerUnit < costPerUnit) {
        if (!confirm('Price per unit is less than cost per unit. This will result in a loss. Continue?')) {
            return false;
        }
    }
    
    return true;
}

function editPart(part) {
    document.getElementById('editPartId').value = part.PartId;
    document.getElementById('editName').value = part.Name;
    document.getElementById('editPartNumber').value = part.PartNumber || '';
    document.getElementById('editDescription').value = part.Description || '';
    document.getElementById('editCostPerUnit').value = part.CostPerUnit;
    document.getElementById('editPricePerUnit').value = part.PricePerUnit;
    document.getElementById('editQuantity').value = part.Quantity;
    document.getElementById('editMinimumStock').value = part.MinimumStock;
    document.getElementById('editPartModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editPartModal').style.display = 'none';
}

function adjustStock(partId, partName) {
    document.getElementById('stockPartId').value = partId;
    document.getElementById('stockPartName').textContent = 'Adjusting stock for: ' + partName;
    document.getElementById('stockAdjustModal').style.display = 'block';
}

function closeStockModal() {
    document.getElementById('stockAdjustModal').style.display = 'none';
}

function deletePart(partId, partName) {
    if (confirm('Are you sure you want to delete "' + partName + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="part_id" value="${partId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.id === 'editPartModal') {
        closeEditModal();
    }
    if (e.target.id === 'stockAdjustModal') {
        closeStockModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>
