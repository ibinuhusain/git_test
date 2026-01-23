<?php
require_once '../includes/auth.php';
requireAdmin();

$pdo = getConnection();
$today = date('Y-m-d'); // Define today's date

// Get total agents
$stmt = $pdo->prepare("SELECT COUNT(*) as total_agents FROM users WHERE role = 'agent'");
$stmt->execute();
$total_agents = $stmt->fetchColumn() ?: 0;

// Get total stores
$stmt = $pdo->prepare("SELECT COUNT(*) as total_stores FROM stores");
$stmt->execute();
$total_stores = $stmt->fetchColumn() ?: 0;

// Get total regions
$stmt = $pdo->prepare("SELECT COUNT(*) as total_regions FROM regions");
$stmt->execute();
$total_regions = $stmt->fetchColumn() ?: 0;

// Get completed assignments
$stmt = $pdo->prepare("SELECT COUNT(*) as completed_assignments FROM daily_assignments WHERE status = 'completed'");
$stmt->execute();
$completed_assignments = $stmt->fetchColumn() ?: 0;

// Get total assignments
$stmt = $pdo->prepare("SELECT COUNT(*) as total_assignments FROM daily_assignments");
$stmt->execute();
$total_assignments = $stmt->fetchColumn() ?: 0;

// Get total bank submissions
$stmt = $pdo->prepare("SELECT COUNT(*) as total_submissions FROM bank_submissions");
$stmt->execute();
$total_submissions = $stmt->fetchColumn() ?: 0;

// Calculate completion percentage
$completion_percentage = $total_assignments > 0 ? round(($completed_assignments / $total_assignments) * 100, 2) : 0;

// Agents assigned today
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT da.agent_id) as agents_assigned 
    FROM daily_assignments da 
    WHERE DATE(da.date_assigned) = ?
");
$stmt->execute([$today]);
$agents_assigned = $stmt->fetchColumn() ?: 0;

// Total stores/assigned entities
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_stores 
    FROM daily_assignments da 
    WHERE DATE(da.date_assigned) = ?
");
$stmt->execute([$today]);
$total_stores = $stmt->fetchColumn() ?: 0;

// Completed stores/entities
$stmt = $pdo->prepare("
    SELECT COUNT(*) as completed_stores 
    FROM daily_assignments da 
    WHERE DATE(da.date_assigned) = ? AND da.status = 'completed'
");
$stmt->execute([$today]);
$completed_stores = $stmt->fetchColumn() ?: 0;

// Total malls (from assigned stores)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT s.mall) as total_malls 
    FROM daily_assignments da 
    JOIN stores s ON da.store_id = s.id
    WHERE DATE(da.date_assigned) = ?
");
$stmt->execute([$today]);
$total_malls = $stmt->fetchColumn() ?: 0;

// Completed malls (malls with all stores completed)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT s.mall) as completed_malls 
    FROM daily_assignments da 
    JOIN stores s ON da.store_id = s.id
    WHERE DATE(da.date_assigned) = ? 
    AND da.status = 'completed'
");
$stmt->execute([$today]);
$completed_malls = $stmt->fetchColumn() ?: 0;

// Total bank submissions
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_submissions 
    FROM bank_submissions bs 
    WHERE DATE(bs.created_at) = ?
");
$stmt->execute([$today]);
$total_submissions = $stmt->fetchColumn() ?: 0;

// Total collected amount today
$stmt = $pdo->prepare("
    SELECT SUM(c.amount_collected) as total_collected 
    FROM collections c 
    JOIN daily_assignments da ON c.assignment_id = da.id 
    WHERE DATE(da.date_assigned) = ?
");
$stmt->execute([$today]);
$total_collected = $stmt->fetchColumn() ?: 0;

// Completed orders today
$stmt = $pdo->prepare("
    SELECT COUNT(*) as completed_orders 
    FROM daily_assignments da 
    WHERE DATE(da.date_assigned) = ? AND da.status = 'completed'
");
$stmt->execute([$today]);
$completed_orders = $stmt->fetchColumn() ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Apparels Collection</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="manifest" href="../manifest.json">
    <script>
        // Register service worker for PWA functionality
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('../sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful');
                    })
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed');
                    });
            });
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="../images/logo/apparel-logo.svg" alt="Apparels Logo" style="height: 40px;">
                <h1>Admin Dashboard</h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="active">Home</a>
                <a href="assignments.php">Assignments</a>
                <a href="agents.php">Agents</a>
                <a href="management.php">Management</a>
                <a href="store_data.php">Store Data</a>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="content">
            <h2>System Overview</h2>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo $total_agents; ?></h3>
                    <p>Agents</p>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo $total_stores; ?></h3>
                    <p>Shops Assigned</p>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo $total_regions; ?></h3>
                    <p>Regions Assigned</p>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo $completion_percentage; ?>%</h3>
                    <p>Completion Status</p>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <h3>Additional Information</h3>
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <h3><?php echo $total_assignments; ?></h3>
                        <p>Total Assignments</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $completed_assignments; ?></h3>
                        <p>Completed Assignments</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $total_submissions; ?></h3>
                        <p>Bank Submissions</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $agents_assigned; ?>/<?php echo $total_agents; ?></h3>
                        <p>Agents Assigned/Total</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $total_stores; ?></h3>
                        <p>Shops Assigned</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $completed_stores; ?>/<?php echo $total_stores; ?></h3>
                        <p>Shops Completed</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $total_malls; ?></h3>
                        <p>Regions/Malls Assigned</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $completed_malls; ?>/<?php echo $total_malls; ?></h3>
                        <p>Malls Completed</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo $total_submissions; ?></h3>
                        <p>Total Bank Submissions</p>
                    </div>
                    
                    <div class="stat-card">
                        <h3><?php echo number_format(($total_stores > 0 ? ($completed_stores / $total_stores) * 100 : 0), 2); ?>%</h3>
                        <p>Completion Rate</p>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 30px;">
                <h3>Data Export</h3>
                <a href="export_daily.php" class="btn btn-success">Export Daily Data</a>
                <a href="export_weekly.php" class="btn btn-success">Export Weekly Data</a>
            </div>
        </div>
    </div>
</body>
</html>