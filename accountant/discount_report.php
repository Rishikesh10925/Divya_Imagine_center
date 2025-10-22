<?php
$page_title = "Discount Report";
$required_role = "accountant";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

$by = $_GET['by'] ?? 'center'; // Can be 'center' or 'doctor'
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$end_date_sql = $end_date . ' 23:59:59';
?>

<div class="main-content">
    <div class="table-container">
        <div class="header-bar">
            <h1>Discount Report: By <?php echo ucfirst($by); ?></h1>
            <p>Showing discounts applied from <?php echo date('d M Y', strtotime($start_date)); ?> to <?php echo date('d M Y', strtotime($end_date)); ?></p>
        </div>

        <?php if ($by === 'center'): ?>
            <table class="data-table">
                <thead><tr><th>Bill ID</th><th>Patient Name</th><th>Date</th><th>Discount Amount (₹)</th><th>Billed By</th></tr></thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT b.id, p.name, b.created_at, b.discount, u.username FROM bills b JOIN patients p ON b.patient_id = p.id JOIN users u ON b.receptionist_id = u.id WHERE b.discount_by = 'Center' AND b.discount > 0 AND b.created_at BETWEEN ? AND ? ORDER BY b.id DESC");
                    $stmt->bind_param("ss", $start_date, $end_date_sql);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><a href="/diagnostic-center/templates/print_bill.php?bill_id=<?php echo $row['id']; ?>" target="_blank"><?php echo $row['id']; ?></a></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($row['created_at'])); ?></td>
                            <td><?php echo number_format($row['discount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="5" class="text-center">No discounts by Center found in this period.</td></tr>
                    <?php endif; $stmt->close(); ?>
                </tbody>
            </table>
        <?php elseif ($by === 'doctor'): ?>
             <table class="data-table">
                <thead><tr><th>Doctor Name</th><th>Total Bills with Discount</th><th>Total Discount Amount (₹)</th></tr></thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT rd.doctor_name, COUNT(b.id) as bill_count, SUM(b.discount) as total_discount FROM bills b JOIN referral_doctors rd ON b.referral_doctor_id = rd.id WHERE b.discount_by = 'Doctor' AND b.discount > 0 AND b.created_at BETWEEN ? AND ? GROUP BY rd.id, rd.doctor_name ORDER BY total_discount DESC");
                    $stmt->bind_param("ss", $start_date, $end_date_sql);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0):
                        while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></td>
                            <td><?php echo $row['bill_count']; ?></td>
                            <td><?php echo number_format($row['total_discount'], 2); ?></td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="3" class="text-center">No discounts by Doctors found in this period.</td></tr>
                    <?php endif; $stmt->close(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>