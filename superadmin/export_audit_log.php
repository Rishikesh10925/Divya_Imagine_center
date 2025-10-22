<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$filename = "system_audit_log_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Log ID', 'Performed By (Username)', 'Action Type', 'Target ID', 'Details', 'Timestamp']);

$result = $conn->query("SELECT id, username, action_type, target_id, details, logged_at FROM system_audit_log ORDER BY logged_at DESC");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['username'],
            $row['action_type'],
            $row['target_id'],
            $row['details'],
            $row['logged_at']
        ]);
    }
}

fclose($output);
exit();