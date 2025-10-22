<?php
$page_title = "Writer Dashboard";
$required_role = "writer";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Check for a success message from the session
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying it
}

// Fetch pending reports
$pending_stmt = $conn->prepare("SELECT bi.id, b.id as bill_id, p.name as patient_name, t.sub_test_name, b.created_at FROM bill_items bi JOIN bills b ON bi.bill_id = b.id JOIN patients p ON b.patient_id = p.id JOIN tests t ON bi.test_id = t.id WHERE bi.report_status = 'Pending' AND b.bill_status != 'Void' ORDER BY b.created_at ASC");
$pending_stmt->execute();
$pending_reports = $pending_stmt->get_result();

// Fetch recently completed reports
$completed_stmt = $conn->prepare("SELECT bi.id, b.id as bill_id, p.name as patient_name, t.sub_test_name, bi.updated_at FROM bill_items bi JOIN bills b ON bi.bill_id = b.id JOIN patients p ON b.patient_id = p.id JOIN tests t ON bi.test_id = t.id WHERE bi.report_status = 'Completed' AND b.bill_status != 'Void' ORDER BY bi.updated_at DESC LIMIT 10");
$completed_stmt->execute();
$completed_reports = $completed_stmt->get_result();

require_once '../includes/header.php';
?>

<link rel="stylesheet" href="/diagnostic-center/assets/css/writer-dashboard.css">

<div class="dashboard-container">
    <h1>Writer's Dashboard</h1>
    <p>Select a test from the pending queue to fill the report.</p>

    <?php if ($success_message): ?>
        <div class="success-banner"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <div class="actions-bar">
        <a href="view_templates.php" class="btn-action btn-primary">View All Report Templates</a>
    </div>

    <div class="table-container">
        <h2>Pending Reports Queue</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bill No.</th>
                    <th>Patient Name</th>
                    <th>Test Name</th>
                    <th>Bill Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pending_reports->num_rows > 0): ?>
                    <?php while($report = $pending_reports->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $report['bill_id']; ?></td>
                        <td><?php echo htmlspecialchars($report['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($report['sub_test_name']); ?></td>
                        <td><?php echo date('d-m-Y', strtotime($report['created_at'])); ?></td>
                        <td><a href="fill_report.php?item_id=<?php echo $report['id']; ?>" class="btn-action btn-edit">Write Report</a></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">The report queue is empty. Well done!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-container">
        <h2>Recently Completed Reports</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bill No.</th>
                    <th>Patient Name</th>
                    <th>Test Name</th>
                    <th>Completed On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($completed_reports->num_rows > 0): ?>
                    <?php while($report = $completed_reports->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $report['bill_id']; ?></td>
                        <td><?php echo htmlspecialchars($report['patient_name']); ?></td>
                        <td><?php echo htmlspecialchars($report['sub_test_name']); ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($report['updated_at'])); ?></td>
                        <td><a href="/diagnostic-center/templates/print_report.php?item_id=<?php echo $report['id']; ?>" class="btn-action btn-view" target="_blank">View Report</a></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">No reports have been completed recently.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php 
$pending_stmt->close();
$completed_stmt->close();
require_once '../includes/footer.php'; 
?>