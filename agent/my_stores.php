<?php
require_once '../includes/auth.php';
requireLogin();

if (hasRole('admin')) {
    header("Location: ../admin/dashboard.php");
    exit();
}

$pdo = getConnection();
$agent_id = $_SESSION['user_id'];

// Fetch all assigned stores for the current agent
$stmt = $pdo->prepare("
    SELECT da.*, s.name as store_name, s.address as store_address, s.city, s.state, s.store_id as store_unique_id, s.region_name, s.mall, s.entity, s.brand
    FROM daily_assignments da
    JOIN stores s ON da.store_id = s.id
    WHERE da.agent_id = ?
    ORDER BY da.created_at DESC
");
$stmt->execute([$agent_id]);
$assigned_stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assigned Stores - Apparels Collection</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="manifest" href="../manifest.json">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="../images/logo.svg" alt="Apparels Collection Logo" />
                <h1>My Assigned Stores</h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="my_stores.php" class="active">My Stores</a>
                <a href="submissions.php">Submissions</a>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="content">
            <h2>All Assigned Stores</h2>
            
            <?php if (empty($assigned_stores)): ?>
                <div class="alert alert-info">
                    You don't have any stores assigned yet. Please contact your administrator.
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="store-table">
                        <thead>
                            <tr>
                                <th>Store ID</th>
                                <th>Store Name</th>
                                <th>Address</th>
                                <th>City</th>
                                <th>State</th>
                                <th>Region</th>
                                <th>Assigned Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assigned_stores as $store): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($store['store_unique_id']); ?></td>
                                <td><?php echo htmlspecialchars($store['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($store['store_address']); ?></td>
                                <td><?php echo htmlspecialchars($store['city'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($store['state'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($store['region_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($store['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $store['status']; ?>">
                                        <?php echo ucfirst($store['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="store.php?assignment_id=<?php echo $store['id']; ?>" class="btn btn-small">Manage</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="summary-stats">
                    <h3>Summary</h3>
                    <p>Total Assigned Stores: <?php echo count($assigned_stores); ?></p>
                    <p>Completed: <?php echo count(array_filter($assigned_stores, function($s) { return $s['status'] === 'completed'; })); ?></p>
                    <p>Pending: <?php echo count(array_filter($assigned_stores, function($s) { return $s['status'] === 'pending'; })); ?></p>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px;">
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>