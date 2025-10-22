<?php
$page_title = "Log Expense";
$required_role = "accountant";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $expense_type = trim($_POST['expense_type']);
    $amount = (float)$_POST['amount'];
    $status = trim($_POST['status']);
    $accountant_id = $_SESSION['user_id'];
    $proof_path = null;

    if (isset($_FILES['proof']) && $_FILES['proof']['error'] == 0) {
        $target_dir = "../uploads/expenses/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_extension = pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION);
        $target_file = $target_dir . uniqid('expense_') . '.' . $file_extension;
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array(strtolower($file_extension), $allowed_types) && $_FILES["proof"]["size"] < 5000000) {
            if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_file)) {
                $proof_path = $target_file;
            } else {
                $error_message = "Sorry, there was an error uploading your file.";
            }
        } else {
            $error_message = "Invalid file type or size (Max 5MB, JPG, PNG, PDF).";
        }
    }

    if (empty($error_message)) {
        $stmt = $conn->prepare("INSERT INTO expenses (expense_type, amount, status, proof_path, accountant_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sdssi", $expense_type, $amount, $status, $proof_path, $accountant_id);
        if ($stmt->execute()) {
            $success_message = "Expense logged successfully!";
        } else {
            $error_message = "Database error: Could not log expense.";
        }
        $stmt->close();
    }
}
require_once '../includes/header.php';
?>
    <div class="main-content">
        <div class="form-container">
            <h1>Log New Expense</h1>
            <?php if ($error_message): ?><div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
            <?php if ($success_message): ?><div class="success-banner"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
            <form action="log_expense.php" method="POST" enctype="multipart/form-data">
                <fieldset>
                    <legend>Expense Details</legend>
                    <div class="form-group">
                        <label for="expense_type">Expense Type (e.g., "Office Supplies", "Rent")</label>
                        <input type="text" id="expense_type" name="expense_type" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount (₹)</label>
                            <input type="number" id="amount" name="amount" required step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label for="status">Payment Status</label>
                            <select id="status" name="status" required>
                                <option value="Paid">Paid</option>
                                <option value="Due">Due</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="proof">Upload Proof/Bill (Optional)</label>
                        <input type="file" id="proof" name="proof" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </fieldset>
                <button type="submit" class="btn-submit">Log Expense</button>
            </form>
        </div>
    </div>
<?php require_once '../includes/footer.php'; ?>