<?php
$page_title = "Doctor Referral List";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

if (!isset($_GET['doctor_id']) || !is_numeric($_GET['doctor_id'])) {
    header("Location: view_doctors.php");
    exit();
}

$doctor_id = (int)$_GET['doctor_id'];

// Fetch the doctor's name for the page title
$stmt_doc = $conn->prepare("SELECT doctor_name FROM referral_doctors WHERE id = ?");
$stmt_doc->bind_param("i", $doctor_id);
$stmt_doc->execute();
$doc_result = $stmt_doc->get_result();
if ($doc_result->num_rows === 0) {
    die("Doctor not found.");
}
$doctor = $doc_result->fetch_assoc();
$stmt_doc->close();

// Fetch all bills referred by this doctor
$stmt_bills = $conn->prepare(
    "SELECT b.id, p.name as patient_name, b.created_at, b.net_amount, b.payment_status
     FROM bills b
     JOIN patients p ON b.patient_id = p.id
     WHERE b.referral_doctor_id = ? AND b.bill_status != 'Void'
     ORDER BY b.id DESC"
);
$stmt_bills->bind_param("i", $doctor_id);
$stmt_bills->execute();
$bills_result = $stmt_bills->get_result();

require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="content-header">
        <div class="header-container">
            <h1>Bills Referred by Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?></h1>
            <a href="view_doctors.php" class="btn btn-primary">Back to Doctors Report</a>
        </div>
    </div>

    <div class="page-card">
        <div class="table-responsive">
            <table class="table table-hover table-striped" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Bill No.</th>
                        <th>Patient Name</th>
                        <th>Bill Date</th>
                        <th>Payment Status</th>
                        <th>Net Amount (₹)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($bills_result->num_rows > 0): while($bill = $bills_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $bill['id']; ?></td>
                        <td><?php echo htmlspecialchars($bill['patient_name']); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($bill['created_at'])); ?></td>
                        <td><span class="status-<?php echo strtolower($bill['payment_status']); ?>"><?php echo $bill['payment_status']; ?></span></td>
                        <td><?php echo number_format($bill['net_amount'], 2); ?></td>
                        <td>
                            <a href="/diagnostic-center/templates/print_bill.php?bill_id=<?php echo $bill['id']; ?>" class="btn btn-sm btn-info" target="_blank">View Bill</a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="6" class="text-center">No bills have been referred by this doctor.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php 
$stmt_bills->close();
require_once '../includes/footer.php'; 
?>
