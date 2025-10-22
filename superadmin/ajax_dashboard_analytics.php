<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// --- 1. Get Filters ---
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$end_date_for_query = $end_date . ' 23:59:59';
$receptionist_id = !empty($_GET['receptionist_id']) ? (int)$_GET['receptionist_id'] : null;
$doctor_id = !empty($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$sort_by = $_GET['sort_by'] ?? 'net_amount';

// --- 2. Get all distinct main test categories to build dynamic columns ---
$tests_result = $conn->query("SELECT DISTINCT main_test_name FROM tests WHERE main_test_name IS NOT NULL AND main_test_name != '' ORDER BY main_test_name");
$main_tests = [];
while ($row = $tests_result->fetch_assoc()) {
    $main_tests[] = $row['main_test_name'];
}

// --- 3. Build the powerful, dynamic SQL query ---
$select_clauses = [
    "rd.id as doctor_id",
    "rd.doctor_name",
    // Fixed test count query: count bills and tests accurately without duplication
    "COALESCE(COUNT(DISTINCT b.id), 0) as total_bills",
    "COALESCE(COUNT(bi.id), 0) as total_test_count",
    // Completed = tests on Paid bills; Pending = tests on non-Paid, non-void bills
    "COALESCE(COUNT(CASE WHEN b.payment_status = 'Paid' THEN bi.id END), 0) as completed_test_count",
    "COALESCE(COUNT(CASE WHEN b.payment_status <> 'Paid' AND b.bill_status != 'Void' THEN bi.id END), 0) as pending_test_count",
    // Number of distinct tests types assigned to the doctor in the period
    "COALESCE(COUNT(DISTINCT bi.test_id), 0) as distinct_test_types",
    "COALESCE(SUM(b.gross_amount / COALESCE(bic.item_count, 1)), 0) as gross_amount",
    "COALESCE(SUM(b.discount / COALESCE(bic.item_count, 1)), 0) as total_discount",
    "COALESCE(SUM(b.net_amount / COALESCE(bic.item_count, 1)), 0) as net_amount",
    
    // **START OF SQL FIX**
    // Corrected rd.default_payable_amount to t.default_payable_amount
    // The default payable amount is on the 'tests' table (aliased as 't'), not the 'referral_doctors' table.
    "COALESCE(SUM(CASE WHEN b.payment_status = 'Paid' AND b.discount = 0 THEN COALESCE(dtp.payable_amount, t.default_payable_amount) ELSE 0 END), 0) as total_payable"
    // **END OF SQL FIX**
];

// Dynamically create a sub-column for each main test category
foreach ($main_tests as $test_name) {
    $alias = str_replace([' ', '/', '-'], '_', strtolower($test_name));
    $select_clauses[] = "COUNT(CASE WHEN t.main_test_name = '{$conn->real_escape_string($test_name)}' THEN bi.id END) as {$alias}_count";
    $select_clauses[] = "SUM(CASE WHEN t.main_test_name = '{$conn->real_escape_string($test_name)}' THEN t.price END) as {$alias}_revenue";
}

// --- 4. Add dynamic WHERE clauses for filtering ---
$params = [$start_date, $end_date_for_query];
$types = 'ss';

// Build the base SQL query
$sql = "SELECT " . implode(', ', $select_clauses) . "
        FROM referral_doctors rd ";

// Build the bills JOIN clause dynamically (filters on date, status, and optional receptionist)
$bills_join_sql = "LEFT JOIN bills b ON rd.id = b.referral_doctor_id AND b.created_at BETWEEN ? AND ? AND b.bill_status != 'Void' ";

// Add receptionist filter IF provided
if ($receptionist_id) {
    $bills_join_sql .= " AND b.receptionist_id = ? ";
    $params[] = $receptionist_id;
    $types .= 'i';
}

// Add the (now dynamic) bills join to the main query
$sql .= $bills_join_sql;

// Add the rest of the joins
$sql .= "
    -- Join a subquery that counts items per bill to prevent duplication.
    LEFT JOIN (SELECT bill_id, COUNT(id) as item_count FROM bill_items GROUP BY bill_id) bic ON b.id = bic.bill_id
    LEFT JOIN bill_items bi ON b.id = bi.bill_id
    LEFT JOIN tests t ON bi.test_id = t.id
    LEFT JOIN doctor_test_payables dtp ON rd.id = dtp.doctor_id AND bi.test_id = t.id
    ";

// If a specific doctor is requested, filter on rd.id
if ($doctor_id) {
    $sql .= " WHERE rd.id = ?";
    $params[] = $doctor_id;
    $types .= 'i';
}
$sql .= " GROUP BY rd.id, rd.doctor_name";

// --- 5. Add dynamic ORDER BY for sorting ---
$allowed_sort_columns = ['gross_amount', 'total_test_count', 'net_amount'];
if (in_array($sort_by, $allowed_sort_columns)) {
    $sql .= " ORDER BY {$sort_by} DESC";
} else {
    $sql .= " ORDER BY net_amount DESC"; // Default sort
}

// --- 6. Execute the query and fetch results ---
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // Basic error logging for debugging
    error_log("SQL Prepare Error: " . $conn->error);
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed.', 'sql' => $sql, 'params' => $params, 'types' => $types]);
    exit();
}

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$doctor_data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- 7. Prepare the final JSON response ---
$response = [
    'main_test_headers' => $main_tests,
    'doctor_data' => $doctor_data
];

echo json_encode($response);
exit();
?>