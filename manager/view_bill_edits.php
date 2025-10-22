<?php
$page_title = "Bill Edit History";
$required_role = "manager";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$feedback = '';

// --- HANDLE DELETE REQUEST ---
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $bill_id_to_delete = (int)$_GET['delete_id'];

    // Use a transaction to ensure all related data is deleted safely
    $conn->begin_transaction();
    try {
        // 1. Delete from the log table first
        $stmt1 = $conn->prepare("DELETE FROM bill_edit_log WHERE bill_id = ?");
        $stmt1->bind_param("i", $bill_id_to_delete);
        $stmt1->execute();
        $stmt1->close();

        // 2. Delete the associated test items
        $stmt2 = $conn->prepare("DELETE FROM bill_items WHERE bill_id = ?");
        $stmt2->bind_param("i", $bill_id_to_delete);
        $stmt2->execute();
        $stmt2->close();

        // 3. Delete the main bill record
        $stmt3 = $conn->prepare("DELETE FROM bills WHERE id = ?");
        $stmt3->bind_param("i", $bill_id_to_delete);
        $stmt3->execute();
        $stmt3->close();

        // If all deletions were successful, commit the changes
        $conn->commit();
        $feedback = "<div class='success-banner'>Bill #{$bill_id_to_delete} and all its related records have been permanently deleted.</div>";

    } catch (Exception $e) {
        // If any step fails, roll back all changes
        $conn->rollback();
        $feedback = "<div class='error-banner'>Error deleting bill: " . $e->getMessage() . "</div>";
    }
}


// Fetch all edit logs for display
$logs_result = $conn->query(
    "SELECT l.*, u.username as editor_name
     FROM bill_edit_log l
     JOIN users u ON l.editor_id = u.id
     ORDER BY l.changed_at DESC"
);

require_once '../includes/header.php';
?>
    <div class="table-container">
        <h1>Bill Modification Log</h1>
        <p>This log shows all changes made to bills. As a manager, you can edit or permanently delete these bills.</p>
        <?php echo $feedback; ?>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Bill No.</th>
                    <th>Edited By</th>
                    <th>Reason for Change</th>
                    <th>Date of Change</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($logs_result && $logs_result->num_rows > 0): ?>
                    <?php while($log = $logs_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><a href="/diagnostic-center/templates/print_bill.php?bill_id=<?php echo $log['bill_id']; ?>" target="_blank"><?php echo $log['bill_id']; ?></a></td>
                            <td><?php echo htmlspecialchars($log['editor_name']); ?></td>
                            <td><?php echo htmlspecialchars($log['reason_for_change']); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($log['changed_at'])); ?></td>
                            <td>
                                <!-- This button will show the old bill data in a simple alert box -->
                                <button class="btn-action btn-view" onclick="showPreviousData(this)" data-json='<?php echo htmlspecialchars($log['previous_data_json'], ENT_QUOTES); ?>'>View Old Data</button>
                                
                                <!-- NEW: Edit and Delete buttons -->
                                <a href="/diagnostic-center/receptionist/generate_bill.php?edit_id=<?php echo $log['bill_id']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="view_bill_edits.php?delete_id=<?php echo $log['bill_id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to permanently delete Bill #<?php echo $log['bill_id']; ?>? This action cannot be undone.');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No bill modifications have been logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<script>
function showPreviousData(button) {
    try {
        const jsonData = JSON.parse(button.getAttribute('data-json'));
        let formattedData = "PREVIOUS BILL DATA:\n\n";
        for (const key in jsonData) {
            formattedData += `${key}: ${jsonData[key]}\n`;
        }
        alert(formattedData);
    } catch (e) {
        alert("Could not parse the previous data.");
        console.error("JSON Parse Error:", e);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
