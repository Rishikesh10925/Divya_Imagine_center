<?php
$page_title = "Detailed Report";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$view = $_GET['view'] ?? 'bills';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$receptionist_id = $_GET['receptionist_id'] ?? null;
$doctor_id = $_GET['doctor_id'] ?? null;
$category = $_GET['category'] ?? null; // New filter for test category

// Build dynamic WHERE clauses for bills table alias 'b'
$where_clauses = ["b.created_at BETWEEN ? AND ?"];
$params = [$start_date, $end_date . ' 23:59:59'];
$types = 'ss';

if (!empty($receptionist_id)) { $where_clauses[] = "b.receptionist_id = ?"; $params[] = $receptionist_id; $types .= 'i'; }
if (!empty($doctor_id)) { $where_clauses[] = "b.referral_doctor_id = ?"; $params[] = $doctor_id; $types .= 'i'; }

// Add category filter specifically for the tests view
if ($view === 'tests' && !empty($category)) {
    $where_clauses[] = "t.main_test_name = ?";
    $params[] = $category;
    $types .= 's';
}
$where_sql = implode(' AND ', $where_clauses);

require_once '../includes/header.php';
?>
<div class="main-content table-container">
    <h1>Detailed Report: <?php echo ucfirst($view); ?></h1>
    <p>A detailed breakdown of all records matching the filters from the dashboard.</p>
    
    <table class="data-table">
        <thead>
            <?php if ($view === 'bills'): ?>
                <tr><th>Bill ID</th><th>Patient</th><th>Date</th><th>Receptionist</th><th>Referred By</th><th>Discount</th><th>Discount By</th><th>Net Amount</th></tr>
            <?php elseif ($view === 'expenses'): ?>
                <tr><th>ID</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th><th>Logged By</th><th>Proof</th></tr>
            <?php elseif ($view === 'patients'): ?>
                <tr><th>ID</th><th>Name</th><th>Age/Gender</th><th>Registered On</th></tr>
            <?php elseif ($view === 'tests'): ?>
                 <tr><th>Test Category</th><th>Sub-Test</th><th>Count</th><th>Total Revenue</th><th>Total Discount (Proportional)</th></tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php
            $sql = '';
            if ($view === 'bills') {
                $sql = "SELECT b.id, p.name as patient_name, b.created_at, u.username as receptionist_name, rd.doctor_name, b.referral_source_other, b.referral_type, b.discount, b.discount_by, b.net_amount FROM bills b JOIN patients p ON b.patient_id = p.id JOIN users u ON b.receptionist_id = u.id LEFT JOIN referral_doctors rd ON b.referral_doctor_id = rd.id WHERE $where_sql ORDER BY b.id DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $referred_by = 'Self';
                    if ($row['referral_type'] === 'Doctor' && $row['doctor_name']) {
                        $referred_by = 'Dr. ' . htmlspecialchars($row['doctor_name']);
                    } elseif ($row['referral_type'] === 'Other') {
                        $referred_by = htmlspecialchars($row['referral_source_other']);
                    }
                    echo "<tr><td>{$row['id']}</td><td>".htmlspecialchars($row['patient_name'])."</td><td>".date('d-m-Y', strtotime($row['created_at']))."</td><td>".htmlspecialchars($row['receptionist_name'])."</td><td>{$referred_by}</td><td>".number_format($row['discount'], 2)."</td><td>".htmlspecialchars($row['discount_by'])."</td><td>".number_format($row['net_amount'], 2)."</td></tr>";
                }
            } elseif ($view === 'patients') {
                $sql = "SELECT DISTINCT p.id, p.name, p.age, p.sex, p.created_at FROM patients p JOIN bills b ON p.id = b.patient_id WHERE " . $where_sql . " ORDER BY p.id DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                     echo "<tr><td>{$row['id']}</td><td>".htmlspecialchars($row['name'])."</td><td>{$row['age']} / {$row['sex']}</td><td>".date('d-m-Y', strtotime($row['created_at']))."</td></tr>";
                }
            } elseif ($view === 'tests') {
                $sql = "SELECT t.main_test_name, t.sub_test_name, COUNT(bi.id) as test_count, SUM(t.price) as total_revenue, SUM(CASE WHEN b.gross_amount > 0 THEN b.discount * (t.price / b.gross_amount) ELSE 0 END) as total_discount FROM bill_items bi JOIN tests t ON bi.test_id = t.id JOIN bills b ON bi.bill_id = b.id WHERE " . $where_sql . " GROUP BY t.id, t.main_test_name, t.sub_test_name ORDER BY total_revenue DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    echo "<tr><td>".htmlspecialchars($row['main_test_name'])."</td><td>".htmlspecialchars($row['sub_test_name'])."</td><td>{$row['test_count']}</td><td>".number_format($row['total_revenue'], 2)."</td><td>".number_format($row['total_discount'], 2)."</td></tr>";
                }
            } else { // Expenses view
                $sql = "SELECT e.id, e.expense_type, e.amount, e.status, e.created_at, e.proof_path, u.username as accountant_name FROM expenses e JOIN users u ON e.accountant_id = u.id WHERE e.created_at BETWEEN ? AND ? ORDER BY e.id DESC";
                $stmt = $conn->prepare($sql);
                $expense_params = [$start_date, $end_date . ' 23:59:59'];
                $stmt->bind_param('ss', ...$expense_params);
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_assoc()) {
                    $proof_link = 'N/A';
                    if (!empty($row['proof_path'])) {
                        $url = '/diagnostic-center/' . ltrim(str_replace('../', '', $row['proof_path']), '/');
                        $proof_link = "<a href='{$url}' target='_blank' class='btn-action btn-view'>View</a>";
                    }
                    echo "<tr><td>{$row['id']}</td><td>".htmlspecialchars($row['expense_type'])."</td><td>".number_format($row['amount'], 2)."</td><td>".htmlspecialchars($row['status'])."</td><td>".date('d-m-Y', strtotime($row['created_at']))."</td><td>".htmlspecialchars($row['accountant_name'])."</td><td>{$proof_link}</td></tr>";
                }
            }

            if ($result->num_rows === 0) {
                $colspan = ($view === 'bills') ? 8 : (($view === 'tests') ? 5 : 7);
                echo "<tr><td colspan='{$colspan}' class='text-center'>No records found for the selected criteria.</td></tr>";
            }
            $stmt->close();
            ?>
        </tbody>
    </table>
</div>
<?php require_once '../includes/footer.php'; ?>