<?php

require_once '../includes/session.php';
require_once '../config/database.php';


requireLogin('../login.php');
requireRole('admin', '../index.php');

$page_title = 'Bill Management - ETERNATECH REPAIRS';
$error = '';
$success = '';


$uploadsDir = '../uploads/bills';
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0777, true);
    chmod($uploadsDir, 0777);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'upload_bill') {
            $requestId = intval($_POST['request_id'] ?? 0);
            $billNumber = sanitizeInput($_POST['bill_number'] ?? '');
            $totalAmount = floatval($_POST['total_amount'] ?? 0);
            $laborTotal = floatval($_POST['labor_total'] ?? 0);
            $notes = sanitizeInput($_POST['notes'] ?? '');
            
            //  Validation
            if ($requestId <= 0) {
                throw new Exception('Please select a valid request');
            }
            
            if (empty($billNumber)) {
                throw new Exception('Bill number is required');
            }
            
            if ($totalAmount <= 0) {
                throw new Exception('Total amount must be greater than zero');
            }
            
            //Check if request exists and is completed
            $requestCheck = $pdo->prepare("SELECT r.*, c.CustName, c.Email FROM request r 
                                          JOIN customer c ON r.CustID = c.CustID 
                                          WHERE r.RequestId = ? AND r.Status = 'completed'");
            $requestCheck->execute([$requestId]);
            $request = $requestCheck->fetch();
            
            if (!$request) {
                throw new Exception('Request not found or not completed');
            }
            
            //Check if bill already exists for this request
            $billCheck = $pdo->prepare("SELECT BillId FROM bills WHERE RequestId = ?");
            $billCheck->execute([$requestId]);
            if ($billCheck->fetch()) {
                throw new Exception('A bill already exists for this request');
            }
            
            //Handle file upload
            if (!isset($_FILES['bill_file'])) {
                throw new Exception('No file was uploaded');
            }
            
            $file = $_FILES['bill_file'];
            $uploadError = $file['error'];
            
            // Check for upload errors
            switch ($uploadError) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    throw new Exception('File is too large (exceeds PHP upload_max_filesize limit of 2MB)');
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('File is too large (exceeds form MAX_FILE_SIZE)');
                case UPLOAD_ERR_PARTIAL:
                    throw new Exception('File was only partially uploaded');
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('Please select a PDF file to upload');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception('Server error: No temporary directory');
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception('Server error: Cannot write file to disk');
                case UPLOAD_ERR_EXTENSION:
                    throw new Exception('File upload stopped by extension');
                default:
                    throw new Exception('Unknown upload error occurred');
            }
            
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];
            
            //validate file type
            $allowedTypes = ['application/pdf'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Only PDF files are allowed');
            }
            
            //validate file size -2mb
            if ($fileSize > 2 * 1024 * 1024) {
                throw new Exception('File size must be less than 2MB (current PHP limit)');
            }
            
            //Gen unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'bill_' . $requestId . '_' . time() . '.' . $fileExtension;
            
            //Create year/month dir
            $yearMonth = date('Y/m');
            $targetDir = $uploadsDir . '/' . $yearMonth;
            if (!file_exists($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    throw new Exception('Failed to create upload directory');
                }
                chmod($targetDir, 0777); //permission
            }
            
            $targetPath = $targetDir . '/' . $newFileName;
            
            //Move
            if (!move_uploaded_file($fileTmpName, $targetPath)) {
                $error = error_get_last();
                throw new Exception('Failed to upload file: ' . ($error['message'] ?? 'Unknown error. Check directory permissions.'));
            }
            
            //caclPartsTotFrom...
            $partsQuery = $pdo->prepare("SELECT SUM(Cost) as parts_total FROM partsused WHERE RequestID = ?");
            $partsQuery->execute([$requestId]);
            $partsResult = $partsQuery->fetch();
            $partsTotal = $partsResult['parts_total'] ?? 0;
            
            //Insert bill record
            $insertSql = "INSERT INTO bills (RequestId, BillNumber, FileName, FilePath, TotalAmount, PartsTotal, LaborTotal, UploadedBy, Notes) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                $requestId,
                $billNumber,
                $fileName,
                $targetPath,
                $totalAmount,
                $partsTotal,
                $laborTotal,
                getCurrentUserId(),
                $notes
            ]);
            
            $success = "Bill uploaded successfully for Request #{$requestId}";
            
        } elseif ($action === 'delete_bill') {
            $billId = intval($_POST['bill_id'] ?? 0);
            
            if ($billId <= 0) {
                throw new Exception('Invalid bill ID');
            }
           
            $billQuery = $pdo->prepare("SELECT * FROM bills WHERE BillId = ?");
            $billQuery->execute([$billId]);
            $bill = $billQuery->fetch();
            
            if (!$bill) {
                throw new Exception('Bill not found');
            }
            
            //DelF
            if (file_exists($bill['FilePath'])) {
                unlink($bill['FilePath']);
            }
            
            //Del db rec
            $deleteSql = "DELETE FROM bills WHERE BillId = ?";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([$billId]);
            
            $success = "Bill deleted successfully";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

try {
    $completedRequestsSql = "SELECT r.RequestId, r.ProblemDescription, r.FinalCost, r.DateSubmitted,
                           c.CustName, c.Email, dt.Name as DeviceTypeName
                           FROM request r
                           JOIN customer c ON r.CustID = c.CustID
                           LEFT JOIN devicetype dt ON r.DeviceId = dt.DeviceId
                           LEFT JOIN bills b ON r.RequestId = b.RequestId
                           WHERE r.Status = 'completed' AND b.BillId IS NULL
                           ORDER BY r.DateSubmitted DESC";
    $completedStmt = $pdo->prepare($completedRequestsSql);
    $completedStmt->execute();
    $completedRequests = $completedStmt->fetchAll();
    
    //Get * frombills
    $billsSql = "SELECT b.*, r.ProblemDescription, c.CustName, c.Email, dt.Name as DeviceTypeName, a.Name as AdminName
                FROM bills b
                JOIN request r ON b.RequestId = r.RequestId
                JOIN customer c ON r.CustID = c.CustID
                LEFT JOIN devicetype dt ON r.DeviceId = dt.DeviceId
                JOIN admin a ON b.UploadedBy = a.AdminId
                ORDER BY b.UploadDate DESC";
    $billsStmt = $pdo->prepare($billsSql);
    $billsStmt->execute();
    $allBills = $billsStmt->fetchAll();
    
} catch (Exception $e) {
    $error = 'Unable to load data: ' . $e->getMessage();
    $completedRequests = [];
    $allBills = [];
}

//import header
include '../includes/header.php';
?>

<main class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Bill Management</h2>
        <a href="admin_dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Upload New Bill -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Upload New Bill</h3>
        
        <?php if (empty($completedRequests)): ?>
        <div class="empty-state">
            <h4>No Completed Requests</h4>
            <p>All completed requests already have bills uploaded.</p>
        </div>
        <?php else: ?>
        <form method="POST" enctype="multipart/form-data" onsubmit="return validateBillForm(this)">
            <input type="hidden" name="action" value="upload_bill">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <label>
                    <span>Select Completed Request *</span>
                    <select name="request_id" required onchange="loadRequestDetails(this)">
                        <option value="">Choose a request...</option>
                        <?php foreach ($completedRequests as $req): ?>
                        <option value="<?php echo $req['RequestId']; ?>" 
                                data-customer="<?php echo htmlspecialchars($req['CustName']); ?>"
                                data-device="<?php echo htmlspecialchars($req['DeviceTypeName'] ?? 'Unknown'); ?>"
                                data-cost="<?php echo $req['FinalCost']; ?>">
                            #<?php echo $req['RequestId']; ?> - <?php echo htmlspecialchars($req['CustName']); ?> 
                            (<?php echo htmlspecialchars(substr($req['ProblemDescription'], 0, 50)); ?>...)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <label>
                    <span>Bill Number *</span>
                    <input type="text" name="bill_number" required placeholder="e.g., INV-2025-001">
                </label>
            </div>
            
            <div id="requestDetails" style="display: none; background: #413939ff; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4>Request Details</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div>
                        <strong>Customer:</strong> <span id="customerName"></span>
                    </div>
                    <div>
                        <strong>Device:</strong> <span id="deviceType"></span>
                    </div>
                    <div>
                        <strong>Final Cost:</strong> $<span id="finalCost"></span>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <label>
                    <span>Total Amount *</span>
                    <input type="number" name="total_amount" step="0.01" min="0" required>
                </label>
                
                <label>
                    <span>Labor Cost</span>
                    <input type="number" name="labor_total" step="0.01" min="0" placeholder="0.00">
                </label>
                
                <label>
                    <span>Bill File (PDF) * (Max 2MB)</span>
                    <input type="file" name="bill_file" accept=".pdf" required>
                </label>
            </div>
            
            <label>
                <span>Notes</span>
                <textarea name="notes" rows="3" placeholder="Additional notes about this bill"></textarea>
            </label>
            
            <button type="submit" class="btn">Upload Bill</button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Existing Bills-->
    <div class="card">
        <h3>Uploaded Bills (<?php echo count($allBills); ?>)</h3>
        
        <?php if (empty($allBills)): ?>
        <div class="empty-state">
            <h4>No Bills Found</h4>
            <p>Upload your first bill using the form above.</p>
        </div>
        <?php else: ?>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bill #</th>
                        <th>Request</th>
                        <th>Customer</th>
                        <th>Device</th>
                        <th>Amount</th>
                        <th>Parts Used</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allBills as $bill): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($bill['BillNumber']); ?></strong>
                            <br><small style="color: var(--muted);">Status: <?php echo ucfirst($bill['Status']); ?></small>
                        </td>
                        <td>
                            <strong>#<?php echo $bill['RequestId']; ?></strong>
                            <br><small><?php echo htmlspecialchars(substr($bill['ProblemDescription'], 0, 40)); ?>...</small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($bill['CustName']); ?>
                            <br><small style="color: var(--muted);"><?php echo htmlspecialchars($bill['Email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($bill['DeviceTypeName'] ?? 'Unknown'); ?></td>
                        <td>
                            <strong>RS. <?php echo number_format($bill['TotalAmount'], 2); ?></strong>
                            <?php if ($bill['PartsTotal'] > 0): ?>
                            <br><small>Parts: RS. <?php echo number_format($bill['PartsTotal'], 2); ?></small>
                            <?php endif; ?>
                            <?php if ($bill['LaborTotal'] > 0): ?>
                            <br><small>Labor: RS. <?php echo number_format($bill['LaborTotal'], 2); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick="showPartsUsed(<?php echo $bill['RequestId']; ?>)" class="btn-small btn-info">
                                View Parts
                            </button>
                        </td>
                        <td>
                            <?php echo date('M j, Y', strtotime($bill['UploadDate'])); ?>
                            <br><small>by <?php echo htmlspecialchars($bill['AdminName']); ?></small>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="<?php echo htmlspecialchars($bill['FilePath']); ?>" 
                                   target="_blank" class="btn-small btn-view">View PDF</a>
                                <button onclick="deleteBill(<?php echo $bill['BillId']; ?>, '<?php echo htmlspecialchars($bill['BillNumber']); ?>')" 
                                        class="btn-small btn-delete">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<!--Parts Used Modal-->
<div id="partsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--card); padding: 30px; border-radius: 8px; width: 90%; max-width: 700px; border: 1px solid var(--border);">
        <h3 style="margin-top: 0; color: var(--text);">Parts Used in Request #<span id="partsRequestId"></span></h3>
        
        <div id="partsContent">
            <p>Loading parts information...</p>
        </div>
        
        <div style="text-align: right; margin-top: 20px;">
            <button onclick="closePartsModal()" class="btn btn-outline">Close</button>
        </div>
    </div>
</div>

<style>
.table-responsive {
    overflow-x: auto;
    margin: 20px 0;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #5a5050ff;
}

.data-table th {
    background-color: var(--card);
    font-weight: bold;
}

.btn-group {
    display: flex;
    gap: 5px;
    flex-direction: column;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-view {
    background-color: #007bff;
    color: white;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
}

.btn-delete {
    background-color: #dc3545;
    color: white;
}
</style>

<script>
function loadRequestDetails(select) {
    const option = select.selectedOptions[0];
    if (option && option.value) {
        const details = document.getElementById('requestDetails');
        document.getElementById('customerName').textContent = option.dataset.customer;
        document.getElementById('deviceType').textContent = option.dataset.device;
        document.getElementById('finalCost').textContent = option.dataset.cost;
        details.style.display = 'block';
        
        // Pre-fill total amount with final cost
        document.querySelector('input[name="total_amount"]').value = option.dataset.cost;
    } else {
        document.getElementById('requestDetails').style.display = 'none';
    }
}

function validateBillForm(form) {
    const file = form.bill_file.files[0];
    if (file) {
        if (file.type !== 'application/pdf') {
            alert('Please select a PDF file only.');
            return false;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB (current PHP limit).');
            return false;
        }
    }
    return true;
}

function showPartsUsed(requestId) {
    document.getElementById('partsRequestId').textContent = requestId;
    document.getElementById('partsModal').style.display = 'block';
    
    //Fetch parts data
    fetch(`get_parts_used.php?request_id=${requestId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('partsContent').innerHTML = data;
        })
        .catch(error => {
            document.getElementById('partsContent').innerHTML = '<p style="color: red;">Error loading parts data.</p>';
        });
}

function closePartsModal() {
    document.getElementById('partsModal').style.display = 'none';
}

function deleteBill(billId, billNumber) {
    if (confirm(`Are you sure you want to delete bill "${billNumber}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_bill">
            <input type="hidden" name="bill_id" value="${billId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
