<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

$main_test = isset($_GET['main_test']) && $_GET['main_test'] !== '' ? $_GET['main_test'] : null;

if (!$main_test) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT DISTINCT sub_test_name 
        FROM tests 
        WHERE main_test_name = ? 
        AND sub_test_name IS NOT NULL 
        AND sub_test_name != '' 
        ORDER BY sub_test_name";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare query.']);
    exit;
}

$stmt->bind_param('s', $main_test);
$stmt->execute();
$result = $stmt->get_result();
$subtests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($subtests);
exit();
