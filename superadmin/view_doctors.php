<?php
$page_title = "All Doctors";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// FIXED: This query now uses the correct table 'doctor_test_payables' and column 'payable_amount'.
// It also correctly calculates the estimated payout by using the doctor's default amount as a fallback.
$sql = "SELECT 
            rd.id, 
            rd.doctor_name, 
            COUNT(DISTINCT b.id) as total_referrals,
            SUM(COALESCE(dtp.payable_amount, rd.default_payable_amount)) as estimated_payout
        FROM referral_doctors rd
        LEFT JOIN bills b ON rd.id = b.referral_doctor_id
        LEFT JOIN bill_items bi ON b.id = bi.bill_id
        LEFT JOIN doctor_test_payables dtp ON dtp.doctor_id = rd.id AND dtp.test_id = bi.test_id
        GROUP BY rd.id, rd.doctor_name
        ORDER BY rd.doctor_name ASC";

$doctors_result = $conn->query($sql);

// Add error handling to prevent crashes and show a clear message
if (!$doctors_result) {
    die("Error fetching doctor data: " . $conn->error);
}

require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="content-header">
        <div class="header-container">
            <h1>Referring Doctors Report</h1>
        </div>
    </div>
    
    <div class="page-card">
        <div class="chart-header">
            <h3>Performance Overview of All Doctors</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Doctor Name</th>
                        <th>Total Bills Referred</th>
                        <th>Estimated Payout (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($doctors_result->num_rows > 0): while($doc = $doctors_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="view_doctor_details.php?doctor_id=<?php echo $doc['id']; ?>">
                                Dr. <?php echo htmlspecialchars($doc['doctor_name']); ?>
                            </a>
                        </td>
                        <td>
                            <a href="view_doctor_referrals.php?doctor_id=<?php echo $doc['id']; ?>">
                                <?php echo $doc['total_referrals']; ?>
                            </a>
                        </td>
                        <td><?php echo number_format($doc['estimated_payout'] ?? 0, 2); ?></td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="3" class="text-center">No doctors found in the system.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

