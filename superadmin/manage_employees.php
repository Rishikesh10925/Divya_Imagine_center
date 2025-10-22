<?php
$page_title = "Employee Management";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$feedback = isset($_SESSION['feedback']) ? $_SESSION['feedback'] : '';
unset($_SESSION['feedback']);

// --- HANDLE ADD NEW EMPLOYEE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    // Basic validation
    if (empty($username) || empty($password) || empty($role)) {
        $feedback = "<div class='error-banner'>Username, password, and role are required.</div>";
    } else {
        // Check if username already exists
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $feedback = "<div class='error-banner'>Error: Username '{$username}' already exists.</div>";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user into the database
            $stmt_insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $username, $hashed_password, $role);

            if ($stmt_insert->execute()) {
                $new_user_id = $stmt_insert->insert_id;
                $feedback = "<div class='success-banner'>Employee '{$username}' added successfully.</div>";

                // Log the creation action
                require_once '../includes/functions.php';
                $log_details = "Created new user '{$username}' (ID: {$new_user_id}) with role '{$role}'.";
                log_system_action($conn, 'USER_CREATED', $new_user_id, $log_details);
            } else {
                $feedback = "<div class='error-banner'>Error: Could not add the employee.</div>";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}


// Fetch all users for the table
$users_result = $conn->query("SELECT id, username, role, is_active, created_at FROM users ORDER BY username ASC");

require_once '../includes/header.php';
?>
<div class="management-container" style="padding: 20px;">
    <h1>Employee Management</h1>
    <p>Add, view, manage, and delete user accounts in the system.</p>
    
    <?php if($feedback) echo $feedback; ?>

    <div class="management-form" style="margin-bottom: 30px;">
        <h3>Add New Employee</h3>
        <form action="manage_employees.php" method="POST">
            <input type="hidden" name="add_employee" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">-- Select Role --</option>
                        <option value="receptionist">Receptionist</option>
                        <option value="manager">Manager</option>
                        <option value="accountant">Accountant</option>
                        <option value="writer">Writer</option>
                        <option value="superadmin">Superadmin</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-submit" style="margin-top: 28px;">Add Employee</button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-container">
        <h3>Existing Employees</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_result->num_rows > 0): while($user = $users_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo ucfirst($user['role']); ?></td>
                    <td><?php echo $user['is_active'] ? '<span class="status-paid">Active</span>' : '<span class="status-pending">Inactive</span>'; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <a href="edit_employee.php?id=<?php echo $user['id']; ?>" class="btn-action btn-edit">Edit</a>
                        <?php if($user['id'] !== $_SESSION['user_id']): // Prevent deleting self ?>
                        <a href="delete_employee.php?id=<?php echo $user['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to permanently delete user <?php echo htmlspecialchars($user['username']); ?>? This action cannot be undone.');">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" style="text-align:center;">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>