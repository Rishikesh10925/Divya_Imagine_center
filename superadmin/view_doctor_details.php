<?php
$page_title = "Doctor Details";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Check if a doctor ID is provided in the URL
if (!isset($_GET['doctor_id']) || !is_numeric($_GET['doctor_id'])) {
    header("Location: view_doctors.php");
    exit();
}

$doctor_id = (int)$_GET['doctor_id'];

// --- Fetch Doctor's General Information ---
$stmt_doctor = $conn->prepare("SELECT * FROM referral_doctors WHERE id = ?");
$stmt_doctor->bind_param("i", $doctor_id);
$stmt_doctor->execute();
$doctor_result = $stmt_doctor->get_result();
if ($doctor_result->num_rows === 0) {
    die("Error: Doctor not found.");
}
$doctor = $doctor_result->fetch_assoc();
$stmt_doctor->close();

// --- Fetch Doctor's Specific Payouts and Group them by Category ---
$stmt_payouts = $conn->prepare(
    "SELECT t.main_test_name, t.sub_test_name, dtp.payable_amount
     FROM doctor_test_payables dtp
     JOIN tests t ON dtp.test_id = t.id
     WHERE dtp.doctor_id = ?
     ORDER BY t.main_test_name, t.sub_test_name"
);
$stmt_payouts->bind_param("i", $doctor_id);
$stmt_payouts->execute();
$payouts_result = $stmt_payouts->get_result();
$payouts_by_category = [];
while ($row = $payouts_result->fetch_assoc()) {
    $payouts_by_category[$row['main_test_name']][] = $row;
}
$stmt_payouts->close();

require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="content-header">
        <div class="header-container">
            <h1>Doctor Summary: Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?></h1>
            <a href="view_doctors.php" class="btn btn-primary">Back to Doctors List</a>
        </div>
    </div>

    <!-- Doctor's Information Card -->
    <div class="page-card mb-4">
        <div class="chart-header">
            <h3>Doctor Information</h3>
        </div>
        <div class="doctor-details-grid">
            <div><strong>Name:</strong> Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?></div>
            <div><strong>Status:</strong> <?php echo $doctor['is_active'] ? '<span class="status-paid">Active</span>' : '<span class="status-pending">Inactive</span>'; ?></div>
            <div><strong>Hospital:</strong> <?php echo htmlspecialchars($doctor['hospital_name'] ?: 'N/A'); ?></div>
            <div><strong>Phone:</strong> <?php echo htmlspecialchars($doctor['phone_number'] ?: 'N/A'); ?></div>
            <div><strong>Email:</strong> <?php echo htmlspecialchars($doctor['email'] ?: 'N/A'); ?></div>
            <div><strong>Area:</strong> <?php echo htmlspecialchars($doctor['area'] ?: 'N/A'); ?></div>
            <div><strong>City:</strong> <?php echo htmlspecialchars($doctor['city'] ?: 'N/A'); ?></div>
            <div class="default-payout"><strong>Default Payout Per Test:</strong> ₹<?php echo number_format($doctor['default_payable_amount'], 2); ?></div>
        </div>
    </div>

    <!-- Test-Specific Payouts Card -->
    <div class="page-card">
        <div class="chart-header">
            <h3>Test-Specific Payouts</h3>
        </div>
        <div style="padding: 1.5rem 1.5rem 0;">
            <p>This section lists all tests where a specific payout amount has been set for this doctor, overriding the default rate.</p>
        </div>
        
        <div style="padding: 0 1.5rem 1.5rem;">
            <?php if (!empty($payouts_by_category)): ?>
                <?php foreach ($payouts_by_category as $category => $tests): ?>
                    <h4 class="category-title"><?php echo htmlspecialchars($category); ?></h4>
                    <div class="payout-list">
                        <?php foreach ($tests as $test): ?>
                            <div class="payout-item">
                                <span class="test-name"><?php echo htmlspecialchars($test['sub_test_name']); ?></span>
                                <span class="payout-amount">₹<?php echo number_format($test['payable_amount'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center mt-3">No specific test payouts have been configured for this doctor. The default payout rate will be used for all tests.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
    .doctor-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
        padding: 1rem 1.5rem 1.5rem;
    }
    .doctor-details-grid > div {
        background-color: #f8f9fc;
        padding: 0.75rem 1rem;
        border-radius: 5px;
        border: 1px solid #e3e6f0;
        font-size: 0.9rem;
    }
    .default-payout {
        grid-column: 1 / -1;
        font-weight: bold;
        background-color: #e9f5ff !important;
        border-color: #b8d4fe !important;
        color: #004085;
    }
    .category-title {
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e3e6f0;
        color: var(--primary);
        font-size: 1.1rem;
    }
    .payout-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .payout-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        background-color: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 5px;
        transition: background-color 0.2s ease;
    }
    .payout-item:hover {
        background-color: #f8f9fc;
    }
    .test-name {
        font-weight: 500;
    }
    .payout-amount {
        font-weight: 700;
        color: #1cc88a;
        background-color: rgba(28, 200, 138, 0.1);
        padding: 0.25rem 0.5rem;
        border-radius: 20px;
        font-size: 0.85rem;
    }
    .mb-4 { margin-bottom: 1.5rem !important; }
    .mt-3 { margin-top: 1rem !important; }
    .text-center { text-align: center; }
</style>

<?php require_once '../includes/footer.php'; ?>

