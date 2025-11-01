<?php

// session and database
require_once '../includes/session.php';
require_once '../config/database.php';

//Require technician login
requireLogin('../login.php');
requireRole('technician', '../index.php');

$page_title = 'Update Task Status - ETERNATECH REPAIRS';
$error = '';
$success = '';
$my_tasks = [];
$request_id = $_GET['request_id'] ?? '';
$selected_task = null;

$technicianId = getCurrentUserId();

try {
    //Get technician's assigned tasks
    $sql = "SELECT r.*, c.CustName, dt.Name as DeviceTypeName, ta.AssignmentData, ta.Status as TaskStatus
            FROM Request r
            INNER JOIN TaskAssignment ta ON r.RequestId = ta.RequestId
            LEFT JOIN Customer c ON r.CustID = c.CustID
            LEFT JOIN DeviceType dt ON r.DeviceId = dt.DeviceId
            WHERE ta.TechnicianId = ?
            ORDER BY ta.AssignmentId DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$technicianId]);
    $my_tasks = $stmt->fetchAll();
    //Get available parts for dropdown
    $partsSql = "SELECT PartId, Name, PartNumber, PricePerUnit FROM Parts ORDER BY Name";
    $partsStmt = $pdo->prepare($partsSql);
    $partsStmt->execute();
    $available_parts = $partsStmt->fetchAll();

    //If request_id is provided, find the selected task
    if ($request_id) {
        foreach ($my_tasks as $task) {
            if ($task['RequestId'] === $request_id) {
                $selected_task = $task;
                break;
            }
        }
    }
} catch (Exception $e) {
    error_log('Update status error: ' . $e->getMessage());
    $error = 'Unable to load task data.';
}

//Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = sanitizeInput($_POST['request_id'] ?? '');
    $newStatus = sanitizeInput($_POST['status'] ?? '');
    $finalCost = sanitizeInput($_POST['final_cost'] ?? '');
    $techNotes = sanitizeInput($_POST['tech_notes'] ?? '');
    
    //Get parts data
    $partNames = $_POST['part_names'] ?? [];
    $partQuantities = $_POST['part_quantities'] ?? [];
    $partCosts = $_POST['part_costs'] ?? [];
    
    if (empty($requestId) || empty($newStatus)) {
        $error = 'Please select a task and status.';
    } else {
        try {
            //Check database connection first
            if (!$pdo) {
                throw new Exception('Database connection not available');
            }
            //validate
            $validateSql = "SELECT r.RequestId FROM Request r
                           INNER JOIN TaskAssignment ta ON r.RequestId = ta.RequestId
                           WHERE r.RequestId = ? AND ta.TechnicianId = ?";
            $validateStmt = $pdo->prepare($validateSql);
            if (!$validateStmt) {
                throw new Exception('Failed to prepare validation query');
            }
            
            $validateStmt->execute([$requestId, $technicianId]);
            
            if (!$validateStmt->fetch()) {
                $error = 'You can only update tasks assigned to you.';
            } else {
                $pdo->beginTransaction();
                
                //Update request status based on task status
                $requestStatus = 'pending'; //Default
                switch ($newStatus) {
                    case 'Assigned':
                        $requestStatus = 'pending';
                        break;
                    case 'Ongoing':
                        $requestStatus = 'in_progress';
                        break;
                    case 'Finished':
                        $requestStatus = 'completed';
                        break;
                }
                
                $updateSql = "UPDATE Request SET Status = ?";
                $updateParams = [$requestStatus];
                if (!empty($finalCost) && is_numeric($finalCost)) {
                    $updateSql .= ", FinalCost = ?";
                    $updateParams[] = floatval($finalCost);
                }
                
                $updateSql .= " WHERE RequestId = ?";
                $updateParams[] = $requestId;
                
                $updateStmt = $pdo->prepare($updateSql);
                if (!$updateStmt) {
                    throw new Exception('Failed to prepare update query');
                }
                $updateStmt->execute($updateParams);
                
                //Update taskAssignment status
                $taskUpdateSql = "UPDATE TaskAssignment SET Status = ?";
                $taskUpdateParams = [$newStatus];
                
                //Set completion date if finished
                if ($newStatus === 'Finished') {
                    $taskUpdateSql .= ", CompletionDate = ?";
                    $taskUpdateParams[] = date('Y-m-d');
                }
                
                $taskUpdateSql .= " WHERE RequestId = ? AND TechnicianId = ?";
                $taskUpdateParams[] = $requestId;
                $taskUpdateParams[] = $technicianId;
                
                $taskUpdateStmt = $pdo->prepare($taskUpdateSql);
                if (!$taskUpdateStmt) {
                    throw new Exception('Failed to prepare task update query');
                }
                $taskUpdateStmt->execute($taskUpdateParams);
                
                //Add parts used if provided
                $partIds = $_POST['part_ids'] ?? [];
                for ($i = 0; $i < count($partNames); $i++) {
                    $partId = !empty($partIds[$i]) ? intval($partIds[$i]) : null;
                    $partName = !empty($partNames[$i]) ? sanitizeInput($partNames[$i]) : '';
                    $quantity = !empty($partQuantities[$i]) ? intval($partQuantities[$i]) : 0;
                    $cost = !empty($partCosts[$i]) ? floatval($partCosts[$i]) : 0.00;
                    
                    //Skip empty entries
                    if (($partId === null && empty($partName)) || $quantity <= 0) {
                        continue;
                    }
                    
                    //part is selected from dropdown, get the name
                    if ($partId !== null) {
                        $partCheckSql = "SELECT Name, PricePerUnit FROM Parts WHERE PartId = ?";
                        $partCheckStmt = $pdo->prepare($partCheckSql);
                        $partCheckStmt->execute([$partId]);
                        $partInfo = $partCheckStmt->fetch();
                        
                        if (!$partInfo) {
                            throw new Exception("Selected part (ID: $partId) not found");
                        }
                        
                        $partName = $partInfo['Name'];
                        if ($cost == 0) {
                            $cost = $partInfo['PricePerUnit'] * $quantity;
                        }
                    }
                    
                    //Insert the part usage record
                    try {
                        $partSql = "INSERT INTO PartsUsed (RequestID, PartId, PartName, Quantity, Cost) 
                                   VALUES (?, ?, ?, ?, ?)";
                        $partStmt = $pdo->prepare($partSql);
                        if (!$partStmt) {
                            throw new Exception('Failed to prepare parts query');
                        }
                        $partStmt->execute([$requestId, $partId, $partName, $quantity, $cost]);
                        
                    } catch (Exception $partError) {
                        error_log("Part insertion failed: " . $partError->getMessage());
                        throw new Exception("Failed to record part usage: " . $partError->getMessage());
                    }
                }
                
                //Add to order flow if status is significant
                if (in_array($newStatus, ['Ongoing', 'Finished'])) {
                    try {
                        $orderSql = "INSERT INTO OrderFlow (RequestId, Status, StatusDate, UpdatedBy, SenderRole, ReceiverRole) 
                                    VALUES (?, ?, ?, ?, 'technician', 'admin')
                                    ON DUPLICATE KEY UPDATE Status = VALUES(Status), StatusDate = VALUES(StatusDate), UpdatedBy = VALUES(UpdatedBy)";
                        
                        $orderStmt = $pdo->prepare($orderSql);
                        if ($orderStmt) {
                            $orderStmt->execute([$requestId, $requestStatus, date('Y-m-d H:i:s'), $technicianId]);
                        }
                    } catch (Exception $orderError) {
                        try {
                            $orderSql = "INSERT INTO OrderFlow (RequestId, SenderRole, ReceiverRole, ActionDate) 
                                        VALUES (?, 'technician', 'admin', ?)";
                            $orderStmt = $pdo->prepare($orderSql);
                            if ($orderStmt) {
                                $orderStmt->execute([$requestId, date('Y-m-d H:i:s')]);
                            }
                        } catch (Exception $e) {
                            error_log("OrderFlow insert failed: " . $e->getMessage());
                        }
                    }
                }
                
                //Add technician notes if provided

                if (!empty($techNotes)) {
                    $notesSql = "INSERT INTO Message (RequestId, SentBy, MessageText, SentDate, IsFromTechnician) 
                                VALUES (?, ?, ?, ?, 1)";
                    
                    $notesStmt = $pdo->prepare($notesSql);
                    if ($notesStmt) {
                        $notesStmt->execute([$requestId, $technicianId, $techNotes, date('Y-m-d H:i:s')]);
                    }
                }
                
                $pdo->commit();
                $success = "Task status updated successfully to: {$newStatus}";
                
                //Clear form data
                $_POST = [];
                
                //Reload tasks
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$technicianId]);
                $my_tasks = $stmt->fetchAll();
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Status update error: ' . $e->getMessage());
            $error = 'Failed to update status: ' . $e->getMessage();
        }
    }
}

//Include header
include '../includes/header.php';
?>

<main class="container narrow">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Update Task Status</h2>
        <a href="technician_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (empty($my_tasks)): ?>
    <div class="card">
        <div class="empty-state">
            <h3>No Assigned Tasks</h3>
            <p>You don't have any assigned tasks to update.</p>
            <a href="technician_dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
    <?php else: ?>
    
    <form method="POST" class="card" onsubmit="return validateUpdateForm(this)">
        <label>
            <span>Select Task to Update *</span>
            <select name="request_id" required onchange="updateTaskDetails(this.value)">
                <option value="">Choose a task</option>
                <?php foreach ($my_tasks as $task): ?>
                    <option value="<?php echo htmlspecialchars($task['RequestId']); ?>"
                            <?php echo $task['RequestId'] === $request_id ? 'selected' : ''; ?>
                            data-customer="<?php echo htmlspecialchars($task['CustName']); ?>"
                            data-device="<?php echo htmlspecialchars($task['DeviceTypeName'] ?? 'N/A'); ?>"
                            data-problem="<?php echo htmlspecialchars($task['ProblemDescription']); ?>"
                            data-current-status="<?php echo htmlspecialchars($task['Status']); ?>"
                            data-cost="<?php echo htmlspecialchars($task['FinalCost']); ?>">
                        #<?php echo htmlspecialchars($task['RequestId']); ?> - 
                        <?php echo htmlspecialchars($task['CustName']); ?> - 
                        <?php echo htmlspecialchars($task['DeviceTypeName'] ?? 'Unknown'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <!-- Task Details (populated by JavaScript) -->
        <div id="taskDetails" style="display: none; background: var(--card); border: 1px solid var(--border); border-radius: 8px; padding: 15px; margin: 20px 0;">
            <h4 style="margin: 0 0 15px 0; color: var(--accent);">Task Details</h4>
            <div class="data-field">
                <span class="field-label">Customer:</span>
                <span id="detailCustomer" class="field-value"></span>
            </div>
            <div class="data-field">
                <span class="field-label">Device:</span>
                <span id="detailDevice" class="field-value"></span>
            </div>
            <div class="data-field">
                <span class="field-label">Problem:</span>
                <span id="detailProblem" class="field-value"></span>
            </div>
            <div class="data-field">
                <span class="field-label">Current Status:</span>
                <span id="detailStatus" class="field-value"></span>
            </div>
        </div>
        
        <label>
            <span>New Status *</span>
            <select name="status" required>
                <option value="">Select new status</option>
                <option value="Assigned">Assigned</option>
                <option value="Ongoing">Ongoing</option>
                <option value="Finished">Finished</option>
            </select>
        </label>
        
        <label>
            <span>Final Cost (if completing repair)</span>
            <input type="number" name="final_cost" step="0.01" min="0" placeholder="0.00">
            <small style="color: var(--muted);">Leave empty if repair is not yet complete</small>
        </label>
        
        <label>
            <span>Parts Used</span>
            <div id="parts-container">
                <div class="part-entry">
                    <select name="part_ids[]" style="width: 45%; margin-right: 5px;" onchange="updatePartPrice(this)">
                        <option value="">Select a part</option>
                        <?php foreach ($available_parts as $part): ?>
                            <option value="<?php echo $part['PartId']; ?>" 
                                    data-price="<?php echo $part['PricePerUnit']; ?>"
                                    data-name="<?php echo htmlspecialchars($part['Name']); ?>">
                                <?php echo htmlspecialchars($part['Name'] . ' - ' . ($part['PartNumber'] ?? 'No Part Number')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="part_names[]" placeholder="Or enter custom part" style="width: 35%; margin-right: 5px;">
                    <input type="number" name="part_quantities[]" placeholder="Qty" min="1" value="1" style="width: 10%; margin-right: 5px;">
                    <input type="number" name="part_costs[]" placeholder="Cost" step="0.01" min="0" style="width: 15%;">
                </div>
            </div>
            <button type="button" onclick="addPartEntry()" class="btn btn-small btn-secondary" style="margin-top: 10px;">+ Add Another Part</button>
            <small style="color: var(--muted); display: block; margin-top: 5px;">Select from available parts or enter custom parts.</small>
        </label>
        
        <label>
            <span>Technician Notes</span>
            <textarea name="tech_notes" rows="4" placeholder="Add any technical notes, findings, or updates for this repair..."></textarea>
            <small style="color: var(--muted);">These notes will be visible to administrators and the customer</small>
        </label>
        
        <button type="submit" class="btn" id="updateBtn" data-original-text="Update Status">Update Status</button>
    </form>

    <!-- My Current Tasks -->
    <div class="card" style="margin-top: 30px;">
        <h3>My Current Tasks</h3>
        <div class="dashboard-grid" style="grid-template-columns: 1fr;">
            <?php foreach ($my_tasks as $task): ?>
            <div class="data-card">
                <div class="data-header">
                    <h4 class="data-title">Request #<?php echo htmlspecialchars($task['RequestId']); ?></h4>
                    <span class="data-status status-<?php echo strtolower(str_replace(' ', '_', $task['Status'] ?? 'pending')); ?>">
                        <?php echo htmlspecialchars($task['Status'] ?? 'Pending'); ?>
                    </span>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="data-field">
                        <span class="field-label">Customer:</span>
                        <span class="field-value"><?php echo htmlspecialchars($task['CustName']); ?></span>
                    </div>
                    
                    <div class="data-field">
                        <span class="field-label">Device:</span>
                        <span class="field-value"><?php echo htmlspecialchars($task['DeviceTypeName'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                
                <div class="data-actions">
                    <button onclick="selectTask('<?php echo htmlspecialchars($task['RequestId']); ?>')" class="btn btn-small">Select for Update</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
function updatePartPrice(selectElement) {
    const option = selectElement.options[selectElement.selectedIndex];
    const partEntry = selectElement.closest('.part-entry');
    const costInput = partEntry.querySelector('input[name="part_costs[]"]');
    const nameInput = partEntry.querySelector('input[name="part_names[]"]');
    const quantityInput = partEntry.querySelector('input[name="part_quantities[]"]');
    
    if (option.value) {
        const price = parseFloat(option.dataset.price || 0);
        const name = option.dataset.name || '';
        const quantity = parseInt(quantityInput.value) || 1;
        
        costInput.value = (price * quantity).toFixed(2);
        nameInput.value = ''; //Clear custom name when part is selected
        nameInput.style.display = 'none'; //Hide custom name field
        
    } else {
        nameInput.style.display = 'inline-block'; // Show custom name field
        costInput.value = '';
    }
}

function addPartEntry() {
    const container = document.getElementById('parts-container');
    const newEntry = document.createElement('div');
    newEntry.className = 'part-entry';
    newEntry.style.marginTop = '10px';
    newEntry.innerHTML = `
        <select name="part_ids[]" style="width: 45%; margin-right: 5px;" onchange="updatePartPrice(this)">
            <option value="">Select a part</option>
            <?php foreach ($available_parts as $part): ?>
                <option value="<?php echo $part['PartId']; ?>" 
                        data-price="<?php echo $part['PricePerUnit']; ?>"
                        data-name="<?php echo htmlspecialchars($part['Name']); ?>">
                    <?php echo htmlspecialchars($part['Name'] . ' - ' . ($part['PartNumber'] ?? 'No Part Number')); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="part_names[]" placeholder="Or enter custom part" style="width: 35%; margin-right: 5px;">
        <input type="number" name="part_quantities[]" placeholder="Qty" min="1" value="1" style="width: 10%; margin-right: 5px;">
        <input type="number" name="part_costs[]" placeholder="Cost" step="0.01" min="0" style="width: 10%; margin-right: 5px;">
        <button type="button" onclick="removePartEntry(this)" class="btn btn-small" style="width: 5%; background: #ff4444;">×</button>
    `;
    container.appendChild(newEntry);
}

function removePartEntry(button) {
    button.parentElement.remove();
}

function validateUpdateForm(form) {
    const updateBtn = document.getElementById('updateBtn');
    
    if (!validateForm(form)) {
        return false;
    }
    
    const status = form.status.value;
    const finalCost = form.final_cost.value;
    
    //Validate parts
    const partSelects = form.querySelectorAll('select[name="part_ids[]"]');
    const partNames = form.querySelectorAll('input[name="part_names[]"]');
    const partQuantities = form.querySelectorAll('input[name="part_quantities[]"]');
    
    for (let i = 0; i < partSelects.length; i++) {
        const partId = partSelects[i].value;
        const partName = partNames[i].value;
        const quantity = parseInt(partQuantities[i].value);
        
        if ((partId || partName) && (!quantity || quantity <= 0)) {
            alert('Please enter a valid quantity for all selected parts.');
            return false;
        }
    }
    
    if ((status === 'Finished') && !finalCost) {
        if (!confirm('You are marking this task as finished without setting a final cost. Continue?')) {
            return false;
        }
    }
    
    setLoading(updateBtn, true);
    return true;
}

function updateTaskDetails(requestId) {
    const select = document.querySelector('select[name="request_id"]');
    const option = select.querySelector(`option[value="${requestId}"]`);
    const detailsDiv = document.getElementById('taskDetails');
    
    if (option && requestId) {
        document.getElementById('detailCustomer').textContent = option.dataset.customer || 'N/A';
        document.getElementById('detailDevice').textContent = option.dataset.device || 'N/A';
        document.getElementById('detailProblem').textContent = option.dataset.problem || 'N/A';
        document.getElementById('detailStatus').textContent = option.dataset.currentStatus || 'N/A';
        
        //Pre-fill cost if available
        const currentCost = option.dataset.cost;
        const costInput = document.querySelector('input[name="final_cost"]');
        if (currentCost && currentCost !== '0' && costInput) {
            costInput.value = currentCost;
        }
        
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
    }
}

function selectTask(requestId) {
    document.querySelector('select[name="request_id"]').value = requestId;
    updateTaskDetails(requestId);
    document.querySelector('select[name="request_id"]').scrollIntoView({ behavior: 'smooth' });
}

//Update task details on page load if request_id is pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const requestSelect = document.querySelector('select[name="request_id"]');
    if (requestSelect.value) {
        updateTaskDetails(requestSelect.value);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
