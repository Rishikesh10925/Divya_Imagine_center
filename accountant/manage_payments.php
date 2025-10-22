<?php
$page_title = "Manage Payments";
$required_role = "accountant";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

if (isset($_POST['update_status']) && isset($_POST['bill_id'])) {
    $bill_id_to_update = (int)$_POST['bill_id'];
    $new_status = $_POST['new_status'];
    if ($new_status === 'Paid' || $new_status === 'Pending') {
        $update_stmt = $conn->prepare("UPDATE bills SET payment_status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $bill_id_to_update);
        $update_stmt->execute();
        $update_stmt->close();
    }
}

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query_select = "SELECT b.id, p.name as patient_name, b.net_amount, b.created_at, b.payment_status, b.payment_mode FROM bills b JOIN patients p ON b.patient_id = p.id";
$count_select = "SELECT COUNT(b.id) FROM bills b JOIN patients p ON b.patient_id = p.id";
$params = [];
$types = '';
$base_where = " WHERE b.bill_status != 'Void' ";

if (!empty($search_term)) {
    $where_clause = " AND (b.id LIKE ? OR p.name LIKE ?)";
    $full_where = $base_where . $where_clause;
    $search_like = "%{$search_term}%";
    $params = [$search_like, $search_like];
    $types = 'ss';
} else {
    $full_where = $base_where;
}

$final_count_query = $count_select . $full_where;
$count_stmt = $conn->prepare($final_count_query);
if (!empty($params)) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_results = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_results / $limit);
$count_stmt->close();

$final_query = $query_select . $full_where . " ORDER BY b.id DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($final_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bills_result = $stmt->get_result();
require_once '../includes/header.php';
?>
    <div class="main-content">
        <div class="table-container">
            <h1>Manage Bill Payments</h1>
            <form method="GET" action="manage_payments.php" class="search-bar-container"><input type="text" name="search" placeholder="Search by Bill No or Patient Name..." value="<?php echo htmlspecialchars($search_term); ?>"><button type="submit">Search</button></form>
            <table class="data-table">
                <thead><tr><th>Bill No.</th><th>Patient Name</th><th>Date</th><th>Amount</th><th>Payment Mode</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if ($bills_result->num_rows > 0): while($bill = $bills_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $bill['id']; ?></td>
                        <td><?php echo htmlspecialchars($bill['patient_name']); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($bill['created_at'])); ?></td>
                        <td><?php echo number_format($bill['net_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($bill['payment_mode']); ?></td>
                        <td><span class="status-<?php echo strtolower($bill['payment_status']); ?>"><?php echo $bill['payment_status']; ?></span></td>
                        <td>
                            <?php if ($bill['payment_status'] == 'Pending'): ?>
                            <form action="manage_payments.php" method="POST" style="display:inline;"><input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>"><input type="hidden" name="new_status" value="Paid"><button type="submit" name="update_status" class="btn-action btn-paid">Mark as Paid</button></form>
                            <?php endif; ?>
                            <a href="/diagnostic-center/templates/print_bill.php?bill_id=<?php echo $bill['id']; ?>" class="btn-action btn-view" target="_blank">View</a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center;">No bills found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="pagination"><?php for ($i = 1; $i <= $total_pages; $i++): ?><a href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search_term); ?>" class="<?php if($page == $i) echo 'active'; ?>"><?php echo $i; ?></a><?php endfor; ?></div>
        </div>
    </div>
<?php $stmt->close(); require_once '../includes/footer.php'; ?>