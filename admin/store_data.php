<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = getConnection();

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_region'])) {
        $region_name = trim($_POST['region_name']);
        
        if (empty($region_name)) {
            $error = 'Region name is required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO regions (name) VALUES (?)");
                $stmt->execute([$region_name]);
                $message = 'Region added successfully!';
            } catch (PDOException $e) {
                $error = 'Error adding region: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['add_store'])) {
        $store_name = trim($_POST['store_name']);
        $mall = trim($_POST['mall']);
        $entity = trim($_POST['entity']);
        $brand = trim($_POST['brand']);
        $store_id = trim($_POST['store_id']);
        $region_id = $_POST['region_id'];
        
        if (empty($store_name) || empty($region_id)) {
            $error = 'Store name and region are required.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO stores (name, mall, entity, brand, region_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$store_name, $mall, $entity, $brand, $region_id]);
                $message = 'Store added successfully!';
            } catch (PDOException $e) {
                $error = 'Error adding store: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['import_stores'])) {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['excel_file']['tmp_name'];
            $file_type = $_FILES['excel_file']['type'];
            
            // Check if it's a valid Excel file
            $valid_types = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
            if (in_array($file_type, $valid_types)) {
                // Process Excel file - for now we'll use a simple CSV approach
                if (($handle = fopen($file_tmp, "r")) !== FALSE) {
                    // Skip header row
                    fgetcsv($handle);
                    
                    $added_count = 0;
                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) >= 4) {
                            $store_name = trim($data[0]);
                            $store_id = trim($data[1]);
                            $brand = trim($data[2]);
                            $mall = trim($data[3]);
                            $entity = trim($data[4]);
                            
                            if (!empty($store_name)) {
                                // Check if store already exists
                                $check_stmt = $pdo->prepare("SELECT id FROM stores WHERE name = ?");
                                $check_stmt->execute([$store_name]);
                                
                                if (!$check_stmt->fetch()) {
                                    // Find or create region based on mall
                                    $region_stmt = $pdo->prepare("SELECT id FROM regions WHERE name = ?");
                                    $region_stmt->execute([$mall]);
                                    $region = $region_stmt->fetch();
                                    
                                    if (!$region) {
                                        // Create region if it doesn't exist
                                        $region_insert = $pdo->prepare("INSERT INTO regions (name) VALUES (?)");
                                        $region_insert->execute([$mall]);
                                        $region_id = $pdo->lastInsertId();
                                    } else {
                                        $region_id = $region['id'];
                                    }
                                    
                                    $stmt = $pdo->prepare("INSERT INTO stores (name, mall, entity, brand, region_id) VALUES (?, ?, ?, ?, ?)");
                                    $stmt->execute([$store_name, $mall, $entity, $brand, $region_id]);
                                    $added_count++;
                                }
                            }
                        }
                    }
                    fclose($handle);
                    
                    if ($added_count > 0) {
                        $message = "Successfully imported $added_count stores!";
                    } else {
                        $error = "No stores were imported. Check your file format.";
                    }
                } else {
                    $error = "Could not read the uploaded file.";
                }
            } else {
                $error = "Invalid file type. Please upload a CSV or Excel file.";
            }
        } elseif (isset($_FILES['excel_file_stores']) && $_FILES['excel_file_stores']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['excel_file_stores']['tmp_name'];
            $fileName = $_FILES['excel_file_stores']['name'];
            $fileSize = $_FILES['excel_file_stores']['size'];
            $fileType = $_FILES['excel_file_stores']['type'];
            
            $allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
            if (in_array($fileType, $allowedTypes) || pathinfo($fileName, PATHINFO_EXTENSION) === 'csv') {
                try {
                    // Load the spreadsheet file
                    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();
                    
                    // Skip header row
                    array_shift($rows);
                    
                    $importedCount = 0;
                    foreach ($rows as $row) {
                        if (!empty($row[0]) && !empty($row[1])) { // Storename and Store_ID are required
                            $store_name = trim($row[0]);
                            $store_id = trim($row[1]);
                            $brand = trim($row[2] ?? '');
                            $mall = trim($row[3] ?? '');
                            $entity = trim($row[4] ?? '');
                            
                            // Check if store with this ID already exists
                            $checkStmt = $pdo->prepare("SELECT id FROM stores WHERE id = ?");
                            $checkStmt->execute([$store_id]);
                            $existingStore = $checkStmt->fetch();
                            
                            if (!$existingStore) {
                                // Insert the store with the specified ID
                                $insertStmt = $pdo->prepare("INSERT INTO stores (id, name, mall, entity, brand, address, region_id) VALUES (?, ?, ?, ?, ?, '', 1)");
                                $insertStmt->execute([$store_id, $store_name, $mall, $entity, $brand]);
                                $importedCount++;
                            } else {
                                // Update existing store
                                $updateStmt = $pdo->prepare("UPDATE stores SET name=?, mall=?, entity=?, brand=? WHERE id=?");
                                $updateStmt->execute([$store_name, $mall, $entity, $brand, $store_id]);
                                $importedCount++;
                            }
                        }
                    }
                    
                    $message = "Successfully imported $importedCount stores from Excel file.";
                } catch (Exception $e) {
                    $error = "Error importing stores: " . $e->getMessage();
                }
            } else {
                $error = "Invalid file type. Please upload an Excel (.xlsx, .xls) or CSV file.";
            }
        } else {
            $error = "Please select an Excel file to import.";
        }
    } elseif (isset($_POST['delete_store'])) {
        $store_id = $_POST['store_id'];
        
        try {
            // Start transaction to ensure data consistency
            $pdo->beginTransaction();
            
            // Get all daily assignment IDs associated with this store
            $stmt = $pdo->prepare("SELECT id FROM daily_assignments WHERE store_id = ?");
            $stmt->execute([$store_id]);
            $assignment_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // If there are assignments, delete related records in collections first
            if (!empty($assignment_ids)) {
                $placeholders = str_repeat('?,', count($assignment_ids) - 1) . '?';
                $stmt = $pdo->prepare("DELETE FROM collections WHERE assignment_id IN ($placeholders)");
                $stmt->execute($assignment_ids);
            }
            
            // Then delete the daily assignments
            $stmt = $pdo->prepare("DELETE FROM daily_assignments WHERE store_id = ?");
            $stmt->execute([$store_id]);
            
            // Finally, delete the store
            $stmt = $pdo->prepare("DELETE FROM stores WHERE id = ?");
            $stmt->execute([$store_id]);
            
            $pdo->commit();
            $message = 'Store deleted successfully!';
        } catch (PDOException $e) {
            $pdo->rollback();
            $error = 'Error deleting store: ' . $e->getMessage();
        }
    } elseif (isset($_POST['update_approval'])) {
        $submission_id = $_POST['submission_id'];
        $status = $_POST['status'];
        $approved_by = $_SESSION['user_id'];
        
        try {
            $stmt = $pdo->prepare("UPDATE bank_submissions SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $approved_by, $submission_id]);
            $message = 'Submission status updated successfully!';
        } catch (PDOException $e) {
            $error = 'Error updating submission: ' . $e->getMessage();
        }
    }
}

// Get all regions
$regions_stmt = $pdo->query("SELECT * FROM regions ORDER BY name");
$regions = $regions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all stores with region names
$stores_stmt = $pdo->query("
    SELECT s.*, r.name as region_name 
    FROM stores s 
    LEFT JOIN regions r ON s.region_id = r.id 
    ORDER BY r.name, s.name
");
$stores = $stores_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending bank submissions for approval
$pending_submissions_stmt = $pdo->query("
    SELECT bs.*, u.name as agent_name, u.username as agent_username
    FROM bank_submissions bs
    JOIN users u ON bs.agent_id = u.id
    WHERE bs.status = 'pending'
    ORDER BY bs.created_at DESC
");
$pending_submissions = $pending_submissions_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Data - Apparels Collection</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="manifest" href="../manifest.json">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Store Data</h1>
            <div class="nav-links">
                <a href="dashboard.php">Home</a>
                <a href="assignments.php">Assignments</a>
                <a href="agents.php">Agents</a>
                <a href="management.php">Management</a>
                <a href="store_data.php" class="active">Store Data</a>
                <a href="bank_approvals.php">Bank Approvals</a>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <h2>Import Stores via Excel</h2>
            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="import_stores" value="1">
                
                <div class="form-group">
                    <label for="excel_file">Upload Excel File:</label>
                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                    <small>Excel file should have columns: Storename, Store ID, Brand, Mall, Entity</small>
                </div>
                
                <button type="submit" class="btn">Import from Excel</button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h2>Add New Region</h2>
            <form method="post" action="">
                <input type="hidden" name="add_region" value="1">
                
                <div class="form-group">
                    <label for="region_name">Region Name:</label>
                    <input type="text" id="region_name" name="region_name" required>
                </div>
                
                <button type="submit" class="btn">Add Region</button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h2>Add New Store</h2>
            <form method="post" action="">
                <input type="hidden" name="add_store" value="1">
                
                <div class="form-group">
                    <label for="store_name">Store Name:</label>
                    <input type="text" id="store_name" name="store_name" required>
                </div>
                
                <div class="form-group">
                    <label for="mall">Mall:</label>
                    <input type="text" id="mall" name="mall">
                </div>
                
                <div class="form-group">
                    <label for="entity">Entity:</label>
                    <input type="text" id="entity" name="entity">
                </div>
                
                <div class="form-group">
                    <label for="brand">Brand:</label>
                    <input type="text" id="brand" name="brand">
                </div>
                
                <div class="form-group">
                    <label for="store_id">Store ID:</label>
                    <input type="text" id="store_id" name="store_id">
                </div>
                
                <div class="form-group">
                    <label for="region_id">Region:</label>
                    <select id="region_id" name="region_id" required>
                        <option value="">Select a region</option>
                        <?php foreach ($regions as $region): ?>
                            <option value="<?php echo $region['id']; ?>"><?php echo htmlspecialchars($region['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn">Add Store</button>
            </form>
            
            <hr style="margin: 30px 0;">

            <h2>Import Stores via Excel</h2>
            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="import_stores" value="1">
                
                <div class="form-group">
                    <label for="excel_file_stores">Upload Excel File:</label>
                    <input type="file" id="excel_file_stores" name="excel_file_stores" accept=".xlsx,.xls" required>
                    <small>Excel file should have columns: Storename, Store_ID, Brand, Mall, Entity</small>
                </div>
                
                <button type="submit" class="btn">Import Stores from Excel</button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h2>Regions</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Region Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regions as $region): ?>
                        <tr>
                            <td><?php echo $region['id']; ?></td>
                            <td><?php echo htmlspecialchars($region['name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <h2>Stores</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Store Name</th>
                        <th>Mall</th>
                        <th>Entity</th>
                        <th>Brand</th>
                        <th>Region</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stores as $store): ?>
                        <tr>
                            <td><?php echo $store['id']; ?></td>
                            <td><?php echo htmlspecialchars($store['name']); ?></td>
                            <td><?php echo htmlspecialchars($store['mall'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($store['entity'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($store['brand'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($store['region_name'] ?? 'N/A'); ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this store?');">
                                    <input type="hidden" name="delete_store" value="1">
                                    <input type="hidden" name="store_id" value="<?php echo $store['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <hr style="margin: 30px 0;">
            
            <h2>Bank Submissions for Approval</h2>
            <?php if (count($pending_submissions) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Amount</th>
                            <th>Receipt Image</th>
                            <th>Date Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_submissions as $submission): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($submission['agent_name']); ?> (<?php echo htmlspecialchars($submission['agent_username']); ?>)</td>
                                <td><?php echo number_format($submission['total_amount'], 2); ?></td>
                                <td>
                                    <?php if ($submission['receipt_image']): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($submission['receipt_image']); ?>" target="_blank">View Receipt</a>
                                    <?php else: ?>
                                        No receipt
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="update_approval" value="1">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        <select name="status" required>
                                            <option value="">Choose action</option>
                                            <option value="approved">Approve</option>
                                            <option value="rejected">Reject</option>
                                        </select>
                                        <button type="submit" class="btn">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending bank submissions for approval.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>