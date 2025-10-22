<?php
$page_title = "All Tests";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// The incorrect line "require_once '../assets/superadmin_final.css';" has been removed.

$tests_result = $conn->query("SELECT id, main_test_name, sub_test_name, price, report_format FROM tests ORDER BY main_test_name, sub_test_name");

if (!$tests_result) {
    die("Error fetching test data: " . $conn->error);
}

require_once '../includes/header.php'; // This file correctly includes the CSS for you.
?>

<main class="main-content">
    <div class="content-header">
        <div class="header-container">
            <h1>Master List: All Diagnostic Tests</h1>
        </div>
    </div>
    
    <div class="page-card">
        <div class="chart-header mb-3">
            <h3>Complete List of Available Tests</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Test Name</th>
                        <th>Price (₹)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tests_result->num_rows > 0): while($test = $tests_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($test['main_test_name']); ?></td>
                        <td><?php echo htmlspecialchars($test['sub_test_name']); ?></td>
                        <td><?php echo number_format($test['price'], 2); ?></td>
                        <td>
                            <button class="btn btn-info btn-sm view-template-btn" 
                                    data-title="<?php echo htmlspecialchars($test['sub_test_name']); ?>" 
                                    data-template="<?php echo htmlspecialchars($test['report_format'], ENT_QUOTES, 'UTF-8'); ?>">
                                View Template
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="4" class="text-center">No tests found in the system.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.view-template-btn').forEach(button => {
        button.addEventListener('click', function() {
            const title = this.getAttribute('data-title');
            const template = this.getAttribute('data-template');
            
            Swal.fire({
                title: `Report Template for: ${title}`,
                html: `<pre style="white-space: pre-wrap; text-align: left; background-color: #f8f9fc; padding: 1rem; border-radius: 5px; border: 1px solid #e3e6f0;">${template || 'No template defined.'}</pre>`,
                width: '600px',
                confirmButtonText: 'Close'
            });
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>