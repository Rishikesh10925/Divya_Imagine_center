<?php
$page_title = "System Audit Log";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// --- Filter Logic ---
$where_clauses = [];
$params = [];
$types = '';

if (!empty($_GET['user_id'])) {
    $where_clauses[] = "user_id = ?";
    $params[] = (int)$_GET['user_id'];
    $types .= 'i';
}
if (!empty($_GET['action_type'])) {
    $where_clauses[] = "action_type = ?";
    $params[] = $_GET['action_type'];
    $types .= 's';
}
if (!empty($_GET['start_date'])) {
    $where_clauses[] = "logged_at >= ?";
    $params[] = $_GET['start_date'];
    $types .= 's';
}
if (!empty($_GET['end_date'])) {
    $where_clauses[] = "logged_at <= ?";
    $params[] = $_GET['end_date'] . ' 23:59:59';
    $types .= 's';
}

// --- Data Fetching ---
$sql = "SELECT id, username, action_type, target_id, details, logged_at FROM system_audit_log";
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}
$sql .= " ORDER BY logged_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs_result = $stmt->get_result();

// Data for filter dropdowns
$users = $conn->query("SELECT id, username FROM users ORDER BY username");
$action_types = $conn->query("SELECT DISTINCT action_type FROM system_audit_log ORDER BY action_type");

require_once '../includes/header.php';
?>

<style>
    .filter-container {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 1.5rem;
        padding: 1.5rem;
        background-color: #fff;
        border-radius: 0.35rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        margin-bottom: 1.5rem;
    }
    .filter-container .form-group {
        flex: 1 1 180px; /* Flex-grow, flex-shrink, and base width */
        margin-bottom: 0;
    }
    .filter-container .form-control {
        width: 100%;
    }
    .filter-container .btn {
        flex-shrink: 0; /* Prevent button from shrinking */
        height: calc(1.5em + 0.75rem + 2px); /* Match height of form inputs */
    }
</style>

<div class="main-content">
    <div class="content-header">
        <div class="header-container">
            <h1>System Audit Log</h1>
        </div>
    </div>

    <div class="page-card">
        <form id="audit-filter-form" class="filter-container" method="GET">
            <div class="form-group">
                <label for="user_id_filter">User</label>
                <select name="user_id" id="user_id_filter" class="form-control form-control-sm">
                    <option value="">All Users</option>
                    <?php while ($u = $users->fetch_assoc()) {
                        echo "<option value='{$u['id']}' " . ((!empty($_GET['user_id']) && $_GET['user_id'] == $u['id']) ? 'selected' : '') . ">" . htmlspecialchars($u['username']) . "</option>";
                    } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="action_type_filter">Action Type</label>
                <select name="action_type" id="action_type_filter" class="form-control form-control-sm">
                    <option value="">All Actions</option>
                    <?php while ($a = $action_types->fetch_assoc()) {
                        echo "<option value='{$a['action_type']}' " . ((!empty($_GET['action_type']) && $_GET['action_type'] == $a['action_type']) ? 'selected' : '') . ">" . htmlspecialchars($a['action_type']) . "</option>";
                    } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="start_date_filter">Start Date</label>
                <input type="date" name="start_date" id="start_date_filter" class="form-control form-control-sm" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="end_date_filter">End Date</label>
                <input type="date" name="end_date" id="end_date_filter" class="form-control form-control-sm" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>

    <div class="table-container page-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>Performed By</th>
                        <th>Action Type</th>
                        <th>Target ID</th>
                        <th>Details</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs_result->num_rows > 0): ?>
                        <?php while ($log = $logs_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo htmlspecialchars($log['username']); ?></td>
                                <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                                <td><?php echo $log['target_id'] ? $log['target_id'] : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td><?php echo date('d-m-Y H:i:s', strtotime($log['logged_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No audit logs found matching your criteria.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
