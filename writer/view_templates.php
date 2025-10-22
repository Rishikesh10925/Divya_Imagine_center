<?php
$page_title = "View Report Templates";
$required_role = ['writer', 'manager']; 
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$selected_category = isset($_GET['category']) && $_GET['category'] !== 'all' ? $_GET['category'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

$categories_stmt = $conn->query("SELECT DISTINCT main_test_name FROM tests ORDER BY main_test_name ASC");
$categories = $categories_stmt->fetch_all(MYSQLI_ASSOC);

$query = "SELECT t.id, t.main_test_name, t.sub_test_name, t.document 
          FROM tests t
          WHERE 1=1";

$params = [];
$types = '';

if ($selected_category !== 'all') {
    $query .= " AND t.main_test_name = ?";
    $params[] = $selected_category;
    $types .= 's';
}
if (!empty($search_term)) {
    $query .= " AND t.sub_test_name LIKE ?";
    $params[] = "%" . $search_term . "%";
    $types .= 's';
}
$query .= " ORDER BY t.main_test_name, t.sub_test_name";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$templates = $stmt->get_result();
$stmt->close();

require_once '../includes/header.php';
$base_templates_dir = realpath(__DIR__ . '/../report_templates');
if ($base_templates_dir === false) {
    $base_templates_dir = __DIR__ . '/../report_templates';
}
$is_manager = ($_SESSION['role'] ?? '') === 'manager';

$flash_success = $_SESSION['success_message'] ?? '';
$flash_error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<div class="dashboard-container">
    <h1>Available Report Templates</h1>
    <p>Search, filter, and select a template to start a new patient report.</p>

    <?php if ($flash_success): ?>
        <div class="success-banner"><?php echo htmlspecialchars($flash_success); ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="error-banner"><?php echo htmlspecialchars($flash_error); ?></div>
    <?php endif; ?>

    <div class="filter-form">
        <form action="view_templates.php" method="GET" class="template-filter-form">
            <div class="form-group">
                <label for="category_filter">Filter by Category:</label>
                <select name="category" id="category_filter" onchange="this.form.submit()">
                    <option value="all">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['main_test_name']); ?>" <?php if ($selected_category == $category['main_test_name']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($category['main_test_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="search">Search by Test Name:</label>
                <input type="text" name="search" id="search" placeholder="Enter test name..." value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            <button type="submit" class="btn-submit">Search</button>
        </form>
    </div>

    <?php if ($is_manager): ?>
    <div class="upload-form" style="margin-top:20px; border-top:1px solid #ddd; padding-top:20px;">
        <h3>Upload / Replace Template (Manager only)</h3>
        <form action="upload_template.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="category">Category (MRI/CT/XRay):</label>
                <input type="text" id="category" name="category" required placeholder="e.g. MRI">
            </div>
            <div class="form-group">
                <label for="template_file">.docx Template File:</label>
                <input type="file" id="template_file" name="template_file" accept=".docx" required>
            </div>
            <div style="margin-top:10px;">
                <button type="submit" class="btn-submit">Upload Template</button>
            </div>
            <p style="font-size: 0.9rem; color:#555; margin-top:5px;">Tip: name the file using the sub-test (e.g., <em>brain_mri_template.docx</em> for "Brain MRI").</p>
        </form>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Test Name</th>
                    <th>Template Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($templates->num_rows > 0): ?>
                    <?php while($template = $templates->fetch_assoc()): ?>
                        <?php
                            $category_slug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $template['main_test_name']);
                            $category_dir = $base_templates_dir ? $base_templates_dir . '/' . $category_slug : '';
                            $sub_slug = strtolower($template['sub_test_name']);
                            $sub_slug = preg_replace('/[^a-z0-9]+/', '_', $sub_slug);
                            $candidate_files = [];
                            if ($category_dir) {
                                $candidate_files[] = $category_dir . '/' . $sub_slug . '.docx';
                                $candidate_files[] = $category_dir . '/' . $sub_slug . '_template.docx';
                                $candidate_files[] = $category_dir . '/' . $sub_slug . '.DOCX';
                                $candidate_files[] = $category_dir . '/' . $sub_slug . '_template.DOCX';
                            }

                            $found_template = '';
                            foreach ($candidate_files as $candidate) {
                                if ($candidate && file_exists($candidate)) {
                                    $found_template = $candidate;
                                    break;
                                }
                            }
                            if (!$found_template && $category_dir && is_dir($category_dir)) {
                                $glob = glob($category_dir . '/*.docx');
                                if ($glob && count($glob) > 0) {
                                    $found_template = $glob[0];
                                }
                            }

                            $template_status = $found_template ? 'Available' : 'Missing';
                            $downloadPath = '';
                            if ($found_template) {
                                $relative = $found_template;
                                $project_root = realpath(__DIR__ . '/..');
                                if ($project_root && strpos($relative, $project_root) === 0) {
                                    $relative = substr($relative, strlen($project_root));
                                }
                                $relative = str_replace('\\', '/', $relative);
                                $relative = ltrim($relative, '/');
                                $downloadPath = 'download_template.php?file=' . urlencode($relative);
                            }
                            $usePath = $found_template ? 'create_report_from_template.php?test_id=' . $template['id'] : '';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($template['main_test_name']); ?></td>
                            <td><?php echo htmlspecialchars($template['sub_test_name']); ?></td>
                            <td>
                                <?php if ($template_status === 'Available'): ?>
                                    <span style="color: #0a8754; font-weight:600;">Available</span>
                                <?php else: ?>
                                    <span style="color: #b21f1f; font-weight:600;">Missing</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($found_template): ?>
                                    <a href="<?php echo $usePath; ?>" class="btn-action btn-primary">Use Template</a>
                                    <a href="<?php echo $downloadPath; ?>" class="btn-action btn-view">Download</a>
                                <?php else: ?>
                                    <span style="font-size:0.9rem; color:#666;">Upload template to enable.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No report templates found matching your criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>