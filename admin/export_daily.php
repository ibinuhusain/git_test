<?php
require_once '../includes/auth.php';
requireAdmin();

try {
    $pdo = getConnection();

    // Get today's collections
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT c.*, da.date_assigned, u.name as agent_name, u.username as agent_username, 
               s.name as store_name, r.name as region_name
        FROM collections c
        JOIN daily_assignments da ON c.assignment_id = da.id
        JOIN users u ON da.agent_id = u.id
        JOIN stores s ON da.store_id = s.id
        LEFT JOIN regions r ON s.region_id = r.id
        WHERE DATE(da.date_assigned) = ?
        ORDER BY u.name, s.name
    ");
    $stmt->execute([$today]);
    $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create CSV content
    $filename = "daily_collections_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if ($output === false) {
        throw new Exception("Could not open output stream for writing.");
    }

    // Add BOM to handle special characters in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write headers
    fputcsv($output, [
        'Date', 
        'Agent Name', 
        'Agent Username', 
        'Store Name', 
        'Region', 
        'Amount Collected', 
        'Pending Amount', 
        'Comments', 
        'Submitted to Bank', 
        'Submitted At'
    ]);

    // Write data rows
    foreach ($collections as $collection) {
        fputcsv($output, [
            $collection['date_assigned'],
            $collection['agent_name'],
            $collection['agent_username'],
            $collection['store_name'],
            $collection['region_name'] ?? 'N/A',
            $collection['amount_collected'],
            $collection['pending_amount'],
            $collection['comments'],
            $collection['submitted_to_bank'] ? 'Yes' : 'No',
            $collection['submitted_at'] ?? ''
        ]);
    }

    fclose($output);
    exit;
} catch (Exception $e) {
    // Log the error for debugging purposes
    error_log("Export error: " . $e->getMessage());
    
    // Show a user-friendly error message
    $error_msg = "Export failed: " . $e->getMessage();
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Export Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; }
            .error { color: red; padding: 15px; border: 1px solid red; background-color: #ffe6e6; }
        </style>
    </head>
    <body>
        <div class='error'>
            <h2>Export Failed</h2>
            <p>An error occurred during the export process.</p>
            <p><strong>Please contact the support team for assistance.</strong></p>
            <p>Error details: " . htmlspecialchars($error_msg) . "</p>
        </div>
        <button onclick='history.back()'>Go Back</button>
    </body>
    </html>";
    exit;
}
?>