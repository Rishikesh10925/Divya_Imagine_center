<?php
$page_title = "Preview Bill";
$required_role = "receptionist";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

if (!isset($_GET['bill_id']) || !is_numeric($_GET['bill_id'])) {
    header("Location: generate_bill.php");
    exit();
}

$bill_id = (int)$_GET['bill_id'];

$stmt = $conn->prepare(
    "SELECT b.*, p.name as patient_name, p.age, p.sex, p.address, p.city, p.mobile_number, u.username as receptionist_username, rd.doctor_name as referral_doctor_name, b.referral_source_other
     FROM bills b
     JOIN patients p ON b.patient_id = p.id
     JOIN users u ON b.receptionist_id = u.id
     LEFT JOIN referral_doctors rd ON b.referral_doctor_id = rd.id
     WHERE b.id = ?"
);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$bill_result = $stmt->get_result();

if ($bill_result->num_rows === 0) {
    die("Error: The bill you are trying to preview could not be found.");
}
$bill = $bill_result->fetch_assoc();
$stmt->close();

$items_stmt = $conn->prepare(
    "SELECT t.main_test_name, t.sub_test_name, t.price
     FROM bill_items bi
     JOIN tests t ON bi.test_id = t.id
     WHERE bi.bill_id = ?"
);
$items_stmt->bind_param("i", $bill_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

require_once '../includes/header.php';
?>

<div class="page-container">
    <h1>Bill Preview</h1>
    <p>Please review the bill details below. If everything is correct, proceed to print.</p>
    
    <div class="preview-area">
        <div class="patient-details-header">
            <strong>Patient:</strong> <?php echo htmlspecialchars($bill['patient_name']); ?> | 
            <strong>Age/Gender:</strong> <?php echo $bill['age']; ?>/<?php echo $bill['sex']; ?> | 
            <strong>Bill No:</strong> <?php echo $bill['id']; ?>
        </div>
        <div class="patient-address-details" style="padding: 10px 5px; background: #f9f9f9; border-bottom: 1px solid #eee; margin-bottom:15px; text-align: center;">
            <strong>Address:</strong> <?php echo htmlspecialchars($bill['address']); ?>, <?php echo htmlspecialchars($bill['city']); ?> | 
            <strong>Mobile:</strong> <?php echo htmlspecialchars($bill['mobile_number']); ?>
        </div>
        <table class="data-table">
            <thead>
                <tr><th>Test Name</th><th style="text-align:right;">Price</th></tr>
            </thead>
            <tbody>
                <?php while($item = $items_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['main_test_name'] . ' - ' . $item['sub_test_name']); ?></td>
                    <td style="text-align:right;"><?php echo number_format($item['price'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr><td style="text-align:right;">Gross Amount:</td><td style="text-align:right;"><?php echo number_format($bill['gross_amount'], 2); ?></td></tr>
                <tr><td style="text-align:right;">Discount:</td><td style="text-align:right;">- <?php echo number_format($bill['discount'], 2); ?></td></tr>
                <tr><td style="text-align:right;"><strong>Net Amount:</strong></td><td style="text-align:right;"><strong><?php echo number_format($bill['net_amount'], 2); ?></strong></td></tr>
            </tfoot>
        </table>
    </div>

    <div class="action-buttons">
        <a href="/diagnostic-center/templates/print_bill.php?bill_id=<?php echo $bill_id; ?>" target="_blank" class="btn-submit">Confirm & Print</a>
        <a href="edit_bill.php?bill_id=<?php echo $bill_id; ?>" class="btn-edit">Edit Bill</a>
        <a href="dashboard.php" class="btn-cancel">Cancel</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>