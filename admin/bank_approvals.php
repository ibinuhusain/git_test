<?php
require_once '../includes/auth.php';
requireAdmin();

$pdo = getConnection();

// Handle approval/rejection
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_reject_submission'])) {
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

// Get pending bank submissions for approval with agent details
$pending_submissions_stmt = $pdo->query("
    SELECT bs.*, u.name as agent_name, u.username as agent_username,
           DATE_FORMAT(bs.created_at, '%M %d, %Y at %h:%i %p') as formatted_created_at
    FROM bank_submissions bs
    JOIN users u ON bs.agent_id = u.id
    WHERE bs.status = 'pending'
    ORDER BY bs.created_at DESC
");
$pending_submissions = $pending_submissions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get agent statistics for the dashboard
$agents_stmt = $pdo->prepare("
    SELECT u.id, u.name, u.username,
           (SELECT COUNT(*) FROM daily_assignments da WHERE da.agent_id = u.id AND da.status = 'completed') as completed_stores,
           (SELECT COUNT(DISTINCT s.mall) FROM daily_assignments da 
            JOIN stores s ON da.store_id = s.id 
            WHERE da.agent_id = u.id AND da.status = 'completed') as completed_malls,
           (SELECT COUNT(DISTINCT s.region_id) FROM daily_assignments da 
            JOIN stores s ON da.store_id = s.id 
            WHERE da.agent_id = u.id AND da.status = 'completed') as completed_regions
    FROM users u
    WHERE u.role = 'agent'
    ORDER BY u.name
");
$agents_stmt->execute();
$agents = $agents_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Submissions Approval - Apparels Collection</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/app.js"></script>
    <link rel="manifest" href="../manifest.json">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bank Submissions Approval</h1>
            <div class="hamburger">
                <div class="hamburger-line"></div>
                <div class="hamburger-line"></div>
                <div class="hamburger-line"></div>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">Home</a>
                <a href="assignments.php">Assignments</a>
                <a href="agents.php">Agents</a>
                <a href="management.php">Management</a>
                <a href="store_data.php">Store Data</a>
                <a href="bank_approvals.php" class="active">Bank Approvals</a>
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
            
            <!-- Agent Statistics -->
            <h2>Agent Performance</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Agent ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Completed Stores</th>
                            <th>Completed Malls</th>
                            <th>Completed Regions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent): ?>
                            <tr>
                                <td><?php echo $agent['id']; ?></td>
                                <td><?php echo htmlspecialchars($agent['name']); ?></td>
                                <td><?php echo htmlspecialchars($agent['username']); ?></td>
                                <td><?php echo $agent['completed_stores']; ?></td>
                                <td><?php echo $agent['completed_malls']; ?></td>
                                <td><?php echo $agent['completed_regions']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <!-- Pending Submissions -->
            <h2>Pending Bank Submissions</h2>
            <?php if (count($pending_submissions) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Amount</th>
                                <th>Submitted Date</th>
                                <th>Receipt</th>
                                <th>Collection Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_submissions as $submission): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($submission['agent_name']); ?></strong><br>
                                        <small>(<?php echo htmlspecialchars($submission['agent_username']); ?>)</small>
                                    </td>
                                    <td><?php echo number_format($submission['total_amount'], 2); ?></td>
                                    <td><?php echo $submission['formatted_created_at']; ?></td>
                                    <td>
                                        <?php if ($submission['receipt_image']): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($submission['receipt_image']); ?>" target="_blank">View Receipt</a>
                                        <?php else: ?>
                                            No receipt
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <!-- Fetch collection details for this agent's assignments on the submission day -->
                                        <?php
                                        $collection_details_stmt = $pdo->prepare("
                                            SELECT SUM(c.amount_collected) as total_collected, 
                                                   SUM(c.pending_amount) as total_pending
                                            FROM collections c
                                            JOIN daily_assignments da ON c.assignment_id = da.id
                                            WHERE da.agent_id = ? AND DATE(da.date_assigned) = DATE(?)
                                        ");
                                        $collection_details_stmt->execute([$submission['agent_id'], $submission['created_at']]);
                                        $collection_details = $collection_details_stmt->fetch(PDO::FETCH_ASSOC);
                                        
                                        $cash_collected = $collection_details['total_collected'] ?: 0;
                                        $pending_amount = $collection_details['total_pending'] ?: 0;
                                        ?>
                                        <div class="collection-details">
                                            <p><strong>Cash Collected:</strong> <?php echo number_format($cash_collected, 2); ?></p>
                                            <p><strong>Pending Amount:</strong> <?php echo number_format($pending_amount, 2); ?></p>
                                            <p><strong>Payment Mode:</strong> Bank Deposit</p>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to approve this submission?');">
                                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <input type="hidden" name="approve_reject_submission" value="1">
                                            <button type="submit" class="btn btn-success">Approve</button>
                                        </form>
                                        <form method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to reject this submission?');">
                                            <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <input type="hidden" name="approve_reject_submission" value="1">
                                            <button type="submit" class="btn btn-danger">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No pending bank submissions for approval.</p>
            <?php endif; ?>
            
            <!-- Approved/Rejected Submissions -->
            <h2 style="margin-top: 40px;">Recent Bank Submissions</h2>
            <?php
            $recent_submissions_stmt = $pdo->query("
                SELECT bs.*, u.name as agent_name, u.username as agent_username,
                       u2.name as approved_by_name,
                       DATE_FORMAT(bs.approved_at, '%M %d, %Y at %h:%i %p') as formatted_approved_at,
                       DATE_FORMAT(bs.created_at, '%M %d, %Y at %h:%i %p') as formatted_created_at
                FROM bank_submissions bs
                JOIN users u ON bs.agent_id = u.id
                LEFT JOIN users u2 ON bs.approved_by = u2.id
                WHERE bs.status != 'pending'
                ORDER BY bs.created_at DESC
                LIMIT 10
            ");
            $recent_submissions = $recent_submissions_stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if (count($recent_submissions) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Agent</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Submitted Date</th>
                                <th>Approved By</th>
                                <th>Approved At</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_submissions as $submission): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($submission['agent_name']); ?></strong><br>
                                        <small>(<?php echo htmlspecialchars($submission['agent_username']); ?>)</small>
                                    </td>
                                    <td><?php echo number_format($submission['total_amount'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        if ($submission['status'] === 'approved') {
                                            $status_class = 'status-approved';
                                        } elseif ($submission['status'] === 'rejected') {
                                            $status_class = 'status-rejected';
                                        } else {
                                            $status_class = 'status-pending';
                                        }
                                        ?>
                                        <span class="<?php echo $status_class; ?>">
                                            <?php echo ucfirst($submission['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $submission['formatted_created_at']; ?></td>
                                    <td><?php echo $submission['approved_by_name'] ?? 'N/A'; ?></td>
                                    <td><?php echo $submission['formatted_approved_at'] ?? 'N/A'; ?></td>
                                    <td>
                                        <?php if ($submission['receipt_image']): ?>
                                            <a href="../uploads/<?php echo htmlspecialchars($submission['receipt_image']); ?>" target="_blank">View Receipt</a>
                                        <?php else: ?>
                                            No receipt
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No recent bank submissions found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
        .collection-details {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #003459;
        }
        
        .collection-details p {
            margin: 5px 0;
        }
        
        .status-approved {
            color: green;
            font-weight: bold;
        }
        
        .status-rejected {
            color: red;
            font-weight: bold;
        }
        
        .status-pending {
            color: orange;
            font-weight: bold;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .nav-links {
                flex-direction: column;
                width: 100%;
                margin-top: 15px;
            }
            
            .nav-links a {
                width: 100%;
                text-align: left;
                margin-bottom: 5px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            table {
                min-width: 600px;
                font-size: 0.9em;
            }
            
            th, td {
                padding: 8px 10px;
                white-space: nowrap;
            }
            
            .collection-details {
                font-size: 0.85em;
                padding: 8px;
            }
            
            .btn {
                padding: 8px 12px;
                font-size: 0.9em;
                margin: 2px;
            }
        }
        
        @media (max-width: 480px) {
            table {
                min-width: 500px;
            }
            
            th, td {
                padding: 6px 8px;
                font-size: 0.8em;
            }
            
            .collection-details p {
                margin: 3px 0;
                font-size: 0.8em;
            }
        }
    </style>
</body>
</html>