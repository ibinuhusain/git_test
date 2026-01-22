<?php
require_once '../includes/auth.php';
requireAdmin();

$pdo = getConnection();

// Handle Excel import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_agents'])) {
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
                        $agent_name = trim($data[0]);
                        $agent_number = trim($data[1]);
                        $username = trim($data[2]);
                        $phone = trim($data[3]);
                        
                        if (!empty($agent_name) && !empty($username)) {
                            // Hash a default password
                            $default_password = password_hash('password123', PASSWORD_DEFAULT);
                            
                            // Check if username already exists
                            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                            $check_stmt->execute([$username]);
                            
                            if (!$check_stmt->fetch()) {
                                $stmt = $pdo->prepare("INSERT INTO users (name, phone, username, password, role) VALUES (?, ?, ?, ?, 'agent')");
                                $stmt->execute([$agent_name, $phone, $username, $default_password]);
                                $added_count++;
                            }
                        }
                    }
                }
                fclose($handle);
                
                if ($added_count > 0) {
                    $success_message = "Successfully imported $added_count agents!";
                } else {
                    $error_message = "No agents were imported. Check your file format.";
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
$agents_stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'agent' ORDER BY name");
$agents_stmt->execute();
// Get all agents
$agents_stmt = $pdo->query("SELECT * FROM users WHERE role = 'agent' ORDER BY name");
$agents = $agents_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agents - Apparels Collection</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="manifest" href="../manifest.json">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Agents</h1>
            <div class="nav-links">
                <a href="dashboard.php">Home</a>
                <a href="assignments.php">Assignments</a>
                <a href="agents.php" class="active">Agents</a>
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
            
            <h2>Import Agents via Excel</h2>
            <form method="post" action="" enctype="multipart/form-data">
                <input type="hidden" name="import_agents" value="1">
                
                <div class="form-group">
                    <label for="excel_file">Upload Excel File:</label>
                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                    <small>Excel file should have columns: Agent Name, Number, Username, Phone Number</small>
                </div>
                
                <button type="submit" class="btn">Import from Excel</button>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h2>Agent List</h2>
            <h2>All Agents</h2>
            
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Agent Name</th>
                        <th>Number</th>
                        <th>Username</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agents as $agent): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($agent['name']); ?></td>
                            <td><?php echo htmlspecialchars($agent['id']); ?></td>
                            <td><?php echo $agent['id']; ?></td>
                            <td><?php echo htmlspecialchars($agent['username']); ?></td>
                            <td><?php echo htmlspecialchars($agent['phone']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</body>
</html>