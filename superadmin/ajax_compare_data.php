<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Accept filters
$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$test_name = isset($_GET['test_name']) && $_GET['test_name'] !== '' ? $_GET['test_name'] : null;
$sub_test_name = isset($_GET['sub_test_name']) && $_GET['sub_test_name'] !== '' ? $_GET['sub_test_name'] : null;
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] : null;

if (!$doctor_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Doctor ID is required.']);
    exit;
}

// Default date range: last 12 months if not provided
if (!$start_date || !$end_date) {
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-12 months'));
}

$end_datetime = $end_date . ' 23:59:59';

$sql = "SELECT 
            DATE_FORMAT(b.created_at, '%Y-%m') as month,
            t.main_test_name,
            COUNT(bi.id) as test_count
        FROM bills b
        JOIN bill_items bi ON b.id = bi.bill_id
        JOIN tests t ON bi.test_id = t.id
        WHERE b.referral_doctor_id = ?
        AND b.created_at BETWEEN ? AND ?
        ";

// Optional test filter
if ($test_name) {
    $sql .= " AND t.main_test_name = ?";
}

// Optional sub-test filter
if ($sub_test_name) {
    $sql .= " AND t.sub_test_name = ?";
}

$sql .= " GROUP BY month, t.main_test_name
        ORDER BY month, t.main_test_name";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare query.']);
    exit;
}

// Bind parameters based on which filters are provided
if ($test_name && $sub_test_name) {
    $stmt->bind_param('issss', $doctor_id, $start_date, $end_datetime, $test_name, $sub_test_name);
} elseif ($test_name) {
    $stmt->bind_param('isss', $doctor_id, $start_date, $end_datetime, $test_name);
} elseif ($sub_test_name) {
    $stmt->bind_param('isss', $doctor_id, $start_date, $end_datetime, $sub_test_name);
} else {
    $stmt->bind_param('iss', $doctor_id, $start_date, $end_datetime);
}

$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($data);
exit();
