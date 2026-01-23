<?php
require_once '../includes/auth.php';
requireLogin();

if (hasRole('admin')) {
    header("Location: ../admin/dashboard.php");
    exit();
}

$pdo = getConnection();
$agent_id = $_SESSION['user_id'];

// Get agent's assignments
$stmt = $pdo->prepare("
    SELECT da.*, s.name as store_name, s.address as store_address, s.city, s.mall, s.entity, s.brand, r.name as region_name
    FROM daily_assignments da
    JOIN stores s ON da.store_id = s.id
    LEFT JOIN regions r ON s.region_id = r.id
    WHERE da.agent_id = ?
    ORDER BY s.name
");
$stmt->execute([$agent_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stores - Apparels Collection</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="manifest" href="../manifest.json">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My Stores</h1>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="store.php">Store</a>
                <a href="submissions.php">Submissions</a>
                <a href="my_stores.php" class="active">My Stores</a>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="content">
            <h2>Your Assigned Stores</h2>
            <?php if (count($assignments) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Store Name</th>
                            <th>City</th>
                            <th>Region</th>
                            <th>Mall</th>
                            <th>Entity</th>
                            <th>Brand</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($assignment['store_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['city'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['region_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['mall'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['entity'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['brand'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($assignment['store_address']); ?></td>
                                <td>
                                    <span class="<?php echo $assignment['status'] === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="store.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn">Manage</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>You have no assigned stores.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>