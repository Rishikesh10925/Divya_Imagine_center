<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// --- 1. Get Filters ---
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$end_date_for_query = $end_date . ' 23:59:59';
$main_test_filter = !empty($_GET['main_test']) ? $_GET['main_test'] : null;
$sub_test_filter = !empty($_GET['sub_test']) ? $_GET['sub_test'] : null;
$doctor_id = !empty($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;

if (!$doctor_id) {
    echo "<tr><td colspan='100%' class='text-center text-danger'>Doctor ID is required.</td></tr>";
    exit();
}

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
    // Summary metrics for comparison
    "COALESCE(COUNT(DISTINCT b.patient_id), 0) as total_patients",
    "COALESCE(COUNT(bi.id), 0) as total_test_count",
    "COALESCE(SUM(b.gross_amount / COALESCE(bic.item_count, 1)), 0) as gross_amount",
    "COALESCE(SUM(b.discount / COALESCE(bic.item_count, 1)), 0) as total_discount",
    "COALESCE(SUM(b.net_amount / COALESCE(bic.item_count, 1)), 0) as net_amount",
    // Payable should use doctor-specific payable if set, else test default; include center-absorbed discounts
    "COALESCE(SUM(CASE WHEN b.payment_status = 'Paid' AND (b.discount = 0 OR b.discount_by = 'Center' OR b.discount_by IS NULL) THEN COALESCE(dtp.payable_amount, t.default_payable_amount) ELSE 0 END), 0) as total_payable"
];

foreach ($main_tests as $test_name) {
    $alias = str_replace([' ', '/', '-'], '_', strtolower($test_name));
    $select_clauses[] = "COUNT(CASE WHEN t.main_test_name = '{$conn->real_escape_string($test_name)}' THEN bi.id END) as {$alias}_count";
    $select_clauses[] = "SUM(CASE WHEN t.main_test_name = '{$conn->real_escape_string($test_name)}' THEN t.price END) as {$alias}_revenue";
}

$sql = "SELECT " . implode(', ', $select_clauses) . "
    FROM referral_doctors rd
    LEFT JOIN bills b ON rd.id = b.referral_doctor_id AND b.created_at BETWEEN ? AND ? AND b.bill_status != 'Void'
    LEFT JOIN (SELECT bill_id, COUNT(id) as item_count FROM bill_items GROUP BY bill_id) bic ON b.id = bic.bill_id
    LEFT JOIN bill_items bi ON b.id = bi.bill_id
    LEFT JOIN tests t ON bi.test_id = t.id
    LEFT JOIN doctor_test_payables dtp ON rd.id = dtp.doctor_id AND bi.test_id = t.id
    ";

// --- 4. Add dynamic WHERE clauses for filtering ---
$where_clauses = ["rd.id = ?"];
$params = [$start_date, $end_date_for_query, $doctor_id];
$types = 'ssi';

if ($main_test_filter) {
    $where_clauses[] = "t.main_test_name = ?";
    $params[] = $main_test_filter;
    $types .= 's';
}
if ($sub_test_filter) {
    $where_clauses[] = "t.sub_test_name = ?";
    $params[] = $sub_test_filter;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY rd.id, rd.doctor_name";

// --- 5. Execute the query and fetch results ---
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo "<tr><td colspan='100%' class='text-center text-danger'>Error preparing statement: " . $conn->error . "</td></tr>";
    exit();
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$doctor_data = $result->fetch_assoc(); // Expecting only one row for one doctor
$stmt->close();

// --- 6. Render the neat summary table ---
if (!$doctor_data) {
    echo '<p class="placeholder-text" style="margin: 2rem 0;">📊 No data found for this doctor in the selected period.</p>';
    exit();
}
?>

<!-- Key Metrics Summary -->
<table class="summary-table">
    <thead>
        <tr>
            <th colspan="2" style="text-align: center;">📊 KEY METRICS</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Doctor Name</td>
            <td class="stat-value">Dr. <?php echo htmlspecialchars($doctor_data['doctor_name']); ?></td>
        </tr>
        <tr>
            <td>👥 Total Patients</td>
            <td class="stat-value"><?php echo number_format($doctor_data['total_patients']); ?></td>
        </tr>
        <tr>
            <td>🧪 Total Tests</td>
            <td class="stat-value"><?php echo number_format($doctor_data['total_test_count']); ?></td>
        </tr>
        <tr>
            <td>💰 Gross Amount</td>
            <td class="stat-value">₹<?php echo number_format($doctor_data['gross_amount'], 2); ?></td>
        </tr>
        <tr>
            <td>🏷️ Total Discount</td>
            <td class="stat-value">₹<?php echo number_format($doctor_data['total_discount'], 2); ?></td>
        </tr>
        <tr>
            <td>💵 Net Amount</td>
            <td class="stat-value" style="color: #1cc88a;">₹<?php echo number_format($doctor_data['net_amount'], 2); ?></td>
        </tr>
        <tr>
            <td>💳 Payable Amount</td>
            <td class="stat-value" style="color: #f6c23e;">₹<?php echo number_format($doctor_data['total_payable'], 2); ?></td>
        </tr>
    </tbody>
</table>

<!-- Test Category Breakdown -->
<h4 style="margin: 1.5rem 0 1rem 0; color: #5a5c69; font-size: 0.95rem; font-weight: 700;">📋 TEST CATEGORY BREAKDOWN</h4>
<div style="overflow-x: auto; border-radius: 0.35rem; border: 1px solid #e3e6f0;">
    <table class="data-table" style="margin: 0; font-size: 0.85rem;">
        <thead style="background: #f8f9fc;">
            <tr>
                <?php foreach ($main_tests as $testName): ?>
                    <th colspan="2" style="text-align: center; border-right: 1px solid #e3e6f0; padding: 0.6rem 0.5rem;"><?php echo htmlspecialchars($testName); ?></th>
                <?php endforeach; ?>
            </tr>
            <tr style="background: #f1f3f5;">
                <?php foreach ($main_tests as $testName): ?>
                    <th style="text-align: center; font-size: 0.75rem; padding: 0.5rem;">Tests</th>
                    <th style="text-align: center; font-size: 0.75rem; padding: 0.5rem; border-right: 1px solid #e3e6f0;">Revenue</th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php foreach ($main_tests as $test_name):
                    $alias = str_replace([' ', '/', '-'], '_', strtolower($test_name));
                    $count = $doctor_data[$alias . '_count'] ?? 0;
                    $revenue = $doctor_data[$alias . '_revenue'] ?? 0;
                ?>
                    <td style="text-align: center; font-weight: 600; color: #4e73df; padding: 0.75rem 0.5rem;"><?php echo $count; ?></td>
                    <td style="text-align: right; padding: 0.75rem 0.5rem; border-right: 1px solid #e3e6f0;">₹<?php echo number_format($revenue, 2); ?></td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
</div>
