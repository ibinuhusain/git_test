<?php
require_once '../includes/auth.php';
requireAdmin();

$pdo = getConnection();

// Get all agents with their collection stats
$agents_stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(da.id) as total_assignments,
           COUNT(CASE WHEN da.status = 'completed' THEN 1 END) as completed_assignments,
           COALESCE(SUM(CASE WHEN da.status = 'completed' THEN c.amount_collected END), 0) as total_collected
    FROM users u
    LEFT JOIN daily_assignments da ON u.id = da.agent_id AND DATE(da.date_assigned) = ?
    LEFT JOIN collections c ON da.id = c.assignment_id
    WHERE u.role = 'agent'
    GROUP BY u.id
    ORDER BY u.name
");
$today = date('Y-m-d');
$agents_stmt->execute([$today]);
$agents = $agents_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending collections for each agent
$pending_stmt = $pdo->prepare("
    SELECT u.id as agent_id, COUNT(c.id) as pending_count
    FROM users u
    LEFT JOIN daily_assignments da ON u.id = da.agent_id AND DATE(da.date_assigned) = ?
    LEFT JOIN collections c ON da.id = c.assignment_id AND c.pending_amount > 0
    WHERE u.role = 'agent'
    GROUP BY u.id
");
$pending_stmt->execute([$today]);
$pending_results = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create a map of agent ID to pending count
$pending_map = [];
foreach ($pending_results as $result) {
    $pending_map[$result['agent_id']] = $result['pending_count'];
}
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
            <h2>All Agents</h2>
            
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
                            <td><?php echo $agent['id']; ?></td>
                            <td><?php echo htmlspecialchars($agent['username']); ?></td>
                            <td><?php echo htmlspecialchars($agent['phone']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>