<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// --- 1. Get and Validate ALL Filters from the Request ---
$doctor_id = !empty($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$end_date_for_query = $end_date . ' 23:59:59';
$receptionist_id = !empty($_GET['receptionist_id']) ? (int)$_GET['receptionist_id'] : null;
// CRITICAL FIX: Also get the doctor filter from main dashboard (if a specific doctor is selected)
$main_doctor_filter = !empty($_GET['main_doctor_id']) ? (int)$_GET['main_doctor_id'] : null;

if (!$doctor_id) {
    echo json_encode(['error' => 'Doctor ID is required']);
    exit();
}

// CRITICAL FIX: If a specific doctor is filtered in the main dashboard, ensure we only show that doctor's data
// This prevents showing data for all doctors when only one is selected in the filter
if ($main_doctor_filter && $main_doctor_filter != $doctor_id) {
    // The clicked doctor doesn't match the main filter, return empty
    echo json_encode(['subtest_data' => []]);
    exit();
}

// --- 2. Build the breakdown query directly ---
// CRITICAL FIX: Group by test names only (not test ID) to aggregate all instances of same test
$detail_sql = "SELECT 
            t.main_test_name,
            t.sub_test_name,
            COUNT(bi.id) as test_count,
            SUM(t.price) as revenue
        FROM bills b
        JOIN bill_items bi ON b.id = bi.bill_id
        JOIN tests t ON bi.test_id = t.id
        WHERE b.referral_doctor_id = ?
            AND b.created_at BETWEEN ? AND ?
            AND b.bill_status != 'Void'";

$detail_params = [$doctor_id, $start_date, $end_date_for_query];
$detail_types = 'iss';

// CRITICAL FIX: Apply the receptionist filter if it exists (must match main dashboard)
if ($receptionist_id) {
    $detail_sql .= " AND b.receptionist_id = ?";
    $detail_params[] = $receptionist_id;
    $detail_types .= 'i';
}

// CRITICAL FIX: Group by main_test_name and sub_test_name ONLY (not t.id)
// This ensures all instances of "CT Scan" are counted together, regardless of price variations
$detail_sql .= " GROUP BY t.main_test_name, t.sub_test_name
                 ORDER BY t.main_test_name, test_count DESC";

$detail_stmt = $conn->prepare($detail_sql);
if ($detail_stmt === false) {
    echo json_encode(['error' => 'Failed to prepare the database query.']);
    exit();
}

$detail_stmt->bind_param($detail_types, ...$detail_params);
$detail_stmt->execute();
$result = $detail_stmt->get_result();

// --- 3. Organize the results by main test category ---
$subtest_data = [];
while ($row = $result->fetch_assoc()) {
    $main_test = $row['main_test_name'];
    if (!isset($subtest_data[$main_test])) {
        $subtest_data[$main_test] = [];
    }
    $subtest_data[$main_test][] = [
        'sub_test_name' => $row['sub_test_name'] ?: 'N/A',
        'test_count' => (int)$row['test_count'],
        'revenue' => (float)$row['revenue']
    ];
}
$detail_stmt->close();

// --- 4. Assemble and return the final JSON response ---
// The response now ONLY contains the breakdown. The totals are handled by JavaScript.
$response = [
    'subtest_data' => $subtest_data
];

echo json_encode($response);
exit();
?>
