<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// --- Get and Validate Filters ---

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$months = isset($_GET['months']) ? (array)$_GET['months'] : [];
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$main_test = isset($_GET['main_test']) && $_GET['main_test'] !== 'all' ? $_GET['main_test'] : null;
$metric = $_GET['metric'] ?? 'referral_count';

if (!$doctor_id || empty($months) || !$year) {
    http_response_code(400);
    echo json_encode(['error' => 'Doctor, year, and at least one month are required.']);
    exit;
}

// --- Select the correct SQL aggregate function based on the metric ---
$metric_sql = '';
switch ($metric) {
    case 'revenue':
        $metric_sql = 'SUM(t.price)';
        break;
    case 'net_amount':
        $metric_sql = 'SUM(b.net_amount / (SELECT COUNT(*) FROM bill_items WHERE bill_id = b.id))';
        break;
    case 'discount':
        $metric_sql = 'SUM(b.discount / (SELECT COUNT(*) FROM bill_items WHERE bill_id = b.id))';
        break;
    default: // referral_count
        $metric_sql = 'COUNT(bi.id)';
        break;
}

// --- Build Query ---

$sql = "SELECT 
            DATE_FORMAT(b.created_at, '%Y-%m') as month,
            {$metric_sql} as value
        FROM bills b
        JOIN bill_items bi ON b.id = bi.bill_id
        JOIN tests t ON bi.test_id = t.id
        WHERE b.referral_doctor_id = ?
          AND YEAR(b.created_at) = ?";

$params = [$doctor_id, $year];
$types = 'ii';

// Add month filters
if (!empty($months)) {
    $month_placeholders = implode(',', array_fill(0, count($months), '?'));
    $sql .= " AND MONTH(b.created_at) IN ({$month_placeholders})";
    foreach($months as $month) {
        $params[] = (int)$month;
        $types .= 'i';
    }
}

// Add test category filter if provided
if ($main_test) {
    $sql .= " AND t.main_test_name = ?";
    $params[] = $main_test;
    $types .= 's';
}

$sql .= " GROUP BY month ORDER BY month ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fill missing months with zero values for chart continuity
$allMonths = array_map(function($m) use ($year) {
    return sprintf('%04d-%02d', $year, $m);
}, $months);
$dataByMonth = [];
foreach ($data as $row) {
    $dataByMonth[$row['month']] = $row['value'];
}
$finalData = [];
foreach ($allMonths as $month) {
    $finalData[] = [
        'month' => $month,
        'value' => isset($dataByMonth[$month]) ? $dataByMonth[$month] : 0
    ];
}

echo json_encode($finalData);
exit();
