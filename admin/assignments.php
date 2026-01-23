<?php
require_once '../includes/auth.php';
requireAdmin();

$pdo = getConnection();

// Handle form submission for assigning shops to agents
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_shops'])) {
    $agent_id = $_POST['agent_id'];
    $selected_stores = $_POST['stores'];
    $assignment_date = $_POST['assignment_date'] ?? date('Y-m-d');
    
    // Check if any of these stores are already assigned to the same agent on the same date
    $conflicts = [];
    foreach ($selected_stores as $store_id) {
        $check_stmt = $pdo->prepare("SELECT id FROM daily_assignments WHERE agent_id = ? AND store_id = ? AND DATE(date_assigned) = ?");
        $check_stmt->execute([$agent_id, $store_id, $assignment_date]);
        if ($check_stmt->fetch()) {
            // Get store name for error message
            $store_info = $pdo->prepare("SELECT name FROM stores WHERE id = ?");
            $store_info->execute([$store_id]);
            $store = $store_info->fetch();
            $conflicts[] = $store ? $store['name'] : "Store ID: $store_id";
        }
    }
    
    if (!empty($conflicts)) {
        $error_message = "These stores are already assigned to this agent on $assignment_date: " . implode(", ", $conflicts);
    } else {
        $success_count = 0;
        foreach ($selected_stores as $store_id) {
            $stmt = $pdo->prepare("INSERT INTO daily_assignments (agent_id, store_id, date_assigned) VALUES (?, ?, ?)");
            if ($stmt->execute([$agent_id, $store_id, $assignment_date])) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $success_message = "$success_count shop(s) assigned successfully!";
        }
    }
}

// Handle Excel import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_excel'])) {
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
                $duplicate_count = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($data) >= 5) {
                        $agent_name = trim($data[0]);
                        $region = trim($data[1]);
                        $mall = trim($data[2]);
                        $entity = trim($data[3]);
                        $brand = trim($data[4]);
                        
                        // Find agent by name
                        $agent_stmt = $pdo->prepare("SELECT id FROM users WHERE name = ? AND role = 'agent'");
                        $agent_stmt->execute([$agent_name]);
                        $agent = $agent_stmt->fetch();
                        
                        if ($agent) {
                            $agent_id = $agent['id'];
                            
                            // Find store by entity/mall details
                            $store_stmt = $pdo->prepare("SELECT id FROM stores WHERE entity = ? AND mall = ?");
                            $store_stmt->execute([$entity, $mall]);
                            $store = $store_stmt->fetch();
                            
                            if ($store) {
                                $store_id = $store['id'];
                                
                                // Check if this store is already assigned to the same agent today
                                $check_stmt = $pdo->prepare("SELECT id FROM daily_assignments WHERE agent_id = ? AND store_id = ? AND DATE(date_assigned) = ?");
                                $check_stmt->execute([$agent_id, $store_id, date('Y-m-d')]);
                                
                                if (!$check_stmt->fetch()) {
                                    // Create assignment only if it doesn't already exist
                                    $assignment_stmt = $pdo->prepare("INSERT INTO daily_assignments (agent_id, store_id, date_assigned) VALUES (?, ?, ?)");
                                    $assignment_stmt->execute([$agent_id, $store_id, date('Y-m-d')]);
                                    $added_count++;
                                } else {
                                    $duplicate_count++;
                                }
                            }
                        }
                    }
                }
                fclose($handle);
                
                $message_parts = [];
                if ($added_count > 0) {
                    $message_parts[] = "Successfully imported $added_count assignments!";
                }
                if ($duplicate_count > 0) {
                    $message_parts[] = "$duplicate_count duplicate assignments skipped.";
                }
                
                if (!empty($message_parts)) {
                    $success_message = implode(" ", $message_parts);
                } else {
                    $error_message = "No assignments were imported. Check your file format.";
                }
            } else {
                $error_message = "Could not read the uploaded file.";
            }
        } else {
            $error_message = "Invalid file type. Please upload a CSV or Excel file.";
        }
    } else {
        $error_message = "Please select an Excel file to import.";
    }
}

// Get all agents
$agents_stmt = $pdo->query("SELECT id, name, username FROM users WHERE role = 'agent'");
$agents = $agents_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all stores
$stores_stmt = $pdo->query("SELECT s.id, s.name, s.mall, s.entity, s.brand, r.name as region_name FROM stores s LEFT JOIN regions r ON s.region_id = r.id");
$stores = $stores_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's assignments
$today = date('Y-m-d');
$assignments_stmt = $pdo->prepare("
    SELECT da.*, u.name as agent_name, s.name as store_name, r.name as region_name
    FROM daily_assignments da
    JOIN users u ON da.agent_id = u.id
    JOIN stores s ON da.store_id = s.id
    LEFT JOIN regions r ON s.region_id = r.id
    WHERE DATE(da.date_assigned) = ?
    ORDER BY u.name, s.name
");
$assignments_stmt->execute([$today]);
$today_assignments = $assignments_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - Apparels Collection</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="manifest" href="../manifest.json">
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="../images/logo/apparel-logo.svg" alt="Apparels Logo" style="height: 40px;">
                <h1>Assignments</h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">Home</a>
                <a href="assignments.php" class="active">Assignments</a>
                <a href="agents.php">Agents</a>
                <a href="management.php">Management</a>
                <a href="store_data.php">Store Data</a>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="content">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <h2>Assign Agents to Entities</h2>
            <form method="post" action="">
                <input type="hidden" name="assign_shops" value="1">
                
                <div class="form-group">
                    <label for="assignment_date">Assignment Date:</label>
                    <input type="date" id="assignment_date" name="assignment_date" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="agent_id">Agent Name:</label>
                    <select id="agent_id" name="agent_id" required>
                        <option value="">Choose an agent</option>
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent['id']; ?>"><?php echo htmlspecialchars($agent['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Assignment Details:</label>
                    <table class="assignment-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Region</th>
                                <th>Mall</th>
                                <th>Entity</th>
                                <th>Brand</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stores as $store): ?>
                                <tr>
                                    <td><input type="checkbox" id="store_<?php echo $store['id']; ?>" name="stores[]" value="<?php echo $store['id']; ?>"></td>
                                    <td><?php echo htmlspecialchars($store['region_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($store['mall'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($store['entity'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($store['brand'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <button type="submit" class="btn">Assign Entities</button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h2>Import Assignments via Excel</h2>
            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="import_excel" value="1">
                
                <div class="form-group">
                    <label for="excel_file">Upload Excel File:</label>
                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                    <small>Excel file should have columns: Agent_Name, Region, Mall, Entity, Brand</small>
                </div>
                
                <button type="submit" class="btn">Import from Excel</button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h2>Today's Assignments (<?php echo date('M j, Y'); ?>)</h2>
            <?php if (count($today_assignments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Store</th>
                            <th>Region</th>
                            <th>Mall</th>
                            <th>Entity</th>
                            <th>Brand</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($today_assignments as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['agent_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['region_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['mall'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['entity'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['brand'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-<?php echo $assignment['status']; ?>">
                                        <?php 
                                            echo ucfirst(str_replace('_', ' ', $assignment['status'])); 
                                        ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No assignments for today.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>