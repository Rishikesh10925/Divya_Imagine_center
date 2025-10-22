<?php
$page_title = "View Expenses";
$required_role = "accountant";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

$query = "SELECT e.id, e.expense_type, e.amount, e.status, e.created_at, e.proof_path, u.username as accountant_name FROM expenses e JOIN users u ON e.accountant_id = u.id WHERE e.created_at BETWEEN ? AND ? ORDER BY e.created_at DESC";
$stmt = $conn->prepare($query);
$end_date_for_query = $end_date . ' 23:59:59';
$stmt->bind_param("ss", $start_date, $end_date_for_query);
$stmt->execute();
$expenses_result = $stmt->get_result();

$total_expenses = 0;
$paid_expenses = 0;
$pending_expenses = 0;
$expenses_data = $expenses_result->fetch_all(MYSQLI_ASSOC);
foreach ($expenses_data as $expense) {
    $total_expenses += $expense['amount'];
    if ($expense['status'] === 'Paid') {
        $paid_expenses += $expense['amount'];
    } else {
        $pending_expenses += $expense['amount'];
    }
}
require_once '../includes/header.php';
?>
    <div class="main-content">
        <div class="header-bar">
            <h1>Expense Records</h1>
            <p>View and manage all logged expenses.</p>
        </div>
        <a href="log_expense.php" class="btn-submit log-new-expense-btn"><i class="fas fa-plus"></i> Log New Expense</a>
        <div class="table-container no-padding-shadow">
            <form method="GET" action="view_expenses.php" class="filter-form">
                <div class="form-group"><label for="start_date">Start Date:</label><input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>"></div>
                <div class="form-group"><label for="end_date">End Date:</label><input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>"></div>
                <button type="submit">Filter</button>
            </form>
            <div class="summary-cards margin-top-1-5rem">
                <div class="summary-card"><h3>Total Expenses</h3><p>₹ <?php echo number_format($total_expenses, 2); ?></p></div>
                <div class="summary-card"><h3>Paid Expenses</h3><p>₹ <?php echo number_format($paid_expenses, 2); ?></p></div>
                <div class="summary-card"><h3>Pending Payments</h3><p>₹ <?php echo number_format($pending_expenses, 2); ?></p></div>
            </div>
            <table class="data-table margin-top-1-5rem">
                <thead><tr><th>ID</th><th>Expense Type</th><th>Amount</th><th>Status</th><th>Date</th><th>Logged By</th><th>Proof</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (count($expenses_data) > 0): foreach($expenses_data as $expense): ?>
                    <tr>
                        <td><?php echo $expense['id']; ?></td>
                        <td><?php echo htmlspecialchars($expense['expense_type']); ?></td>
                        <td>₹ <?php echo number_format($expense['amount'], 2); ?></td>
                        <td><span class="status-<?php echo strtolower($expense['status']); ?>"><?php echo $expense['status']; ?></span></td>
                        <td><?php echo date('d-m-Y', strtotime($expense['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($expense['accountant_name']); ?></td>
                        <td>
                            <?php if (!empty($expense['proof_path'])):
                                // UPDATED: Link points to the new download script for zipping
                                $proof_url = 'download_proof.php?file=' . urlencode(ltrim(str_replace('../', '', $expense['proof_path']), '/'));
                            ?>
                            <a href="<?php echo $proof_url; ?>" class="btn-action btn-view">Download Proof</a>
                            <?php else: ?> N/A <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn-action btn-edit">Edit</a>
                            <form action="delete_expense.php" method="POST" class="inline-form"><input type="hidden" name="expense_id" value="<?php echo $expense['id']; ?>"><button type="submit" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this expense?')">Delete</button></form>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="8" class="text-center">No expenses found for the selected period.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php $stmt->close(); require_once '../includes/footer.php'; ?>