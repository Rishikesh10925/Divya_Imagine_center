<?php
$page_title = "Edit Employee";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$feedback = '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$user_id) {
    header("Location: manage_employees.php");
    exit();
}

// Fetch current user data before any changes
$stmt_fetch_orig = $conn->prepare("SELECT username, role, is_active FROM users WHERE id = ?");
$stmt_fetch_orig->bind_param("i", $user_id);
$stmt_fetch_orig->execute();
$original_user = $stmt_fetch_orig->get_result()->fetch_assoc();
$stmt_fetch_orig->close();

if (!$original_user) {
    die("User not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_user_id = (int)$_POST['user_id'];
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = trim($_POST['new_password']);
    
    $log_user_id = $_SESSION['user_id'];
    $log_username = $_SESSION['username'];
    $log_stmt = $conn->prepare("INSERT INTO system_audit_log (user_id, username, action_type, target_id, details) VALUES (?, ?, ?, ?, ?)");

    if ($posted_user_id === $_SESSION['user_id'] && !$is_active) {
        $feedback = "<div class='error-banner'>You cannot deactivate your own account.</div>";
    } else {
        // Log individual changes
        if ($original_user['username'] !== $username) {
            $log_action = 'USER_USERNAME_CHANGED';
            $log_details = "Username for user ID {$posted_user_id} changed from '{$original_user['username']}' to '{$username}'.";
            $log_stmt->bind_param("issis", $log_user_id, $log_username, $log_action, $posted_user_id, $log_details);
            $log_stmt->execute();
        }
        if ($original_user['role'] !== $role) {
            $log_action = 'USER_ROLE_CHANGED';
            $log_details = "Role for user '{$username}' (ID: {$posted_user_id}) changed from '{$original_user['role']}' to '{$role}'.";
            $log_stmt->bind_param("issis", $log_user_id, $log_username, $log_action, $posted_user_id, $log_details);
            $log_stmt->execute();
        }
        if ($original_user['is_active'] != $is_active) {
            $log_action = 'USER_STATUS_CHANGED';
            $status_text = $is_active ? 'Activated' : 'Deactivated';
            $log_details = "User account '{$username}' (ID: {$posted_user_id}) was {$status_text}.";
            $log_stmt->bind_param("issis", $log_user_id, $log_username, $log_action, $posted_user_id, $log_details);
            $log_stmt->execute();
        }

        // Update basic info
        $stmt_update = $conn->prepare("UPDATE users SET username = ?, role = ?, is_active = ? WHERE id = ?");
        $stmt_update->bind_param("ssii", $username, $role, $is_active, $posted_user_id);
        $stmt_update->execute();

        // Update password and log if provided
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_pass->bind_param("si", $hashed_password, $posted_user_id);
            $stmt_pass->execute();

            $log_action = 'USER_PASSWORD_CHANGED';
            $log_details = "Password changed for user '{$username}' (ID: {$posted_user_id}).";
            $log_stmt->bind_param("issis", $log_user_id, $log_username, $log_action, $posted_user_id, $log_details);
            $log_stmt->execute();
        }
        
        $log_stmt->close();
        $feedback = "<div class='success-banner'>User updated successfully!</div>";
        // Refresh original user data to show new state
        $original_user = ['username' => $username, 'role' => $role, 'is_active' => $is_active];
    }
}

require_once '../includes/header.php';
?>
    <div class="management-container">
        <h1>Edit Employee: <?php echo htmlspecialchars($original_user['username']); ?></h1>
        <?php echo $feedback; ?>
        <div class="management-form">
            <form action="edit_employee.php?id=<?php echo $user_id; ?>" method="POST">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <fieldset>
                    <legend>User Details</legend>
                    <div class="form-row">
                        <div class="form-group"><label for="username">Username</label><input type="text" id="username" name="username" value="<?php echo htmlspecialchars($original_user['username']); ?>" required></div>
                        <div class="form-group"><label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="receptionist" <?php echo ($original_user['role'] == 'receptionist') ? 'selected' : ''; ?>>Receptionist</option>
                                <option value="manager" <?php echo ($original_user['role'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                                <option value="accountant" <?php echo ($original_user['role'] == 'accountant') ? 'selected' : ''; ?>>Accountant</option>
                                <option value="writer" <?php echo ($original_user['role'] == 'writer') ? 'selected' : ''; ?>>Writer</option>
                                <option value="superadmin" <?php echo ($original_user['role'] == 'superadmin') ? 'selected' : ''; ?>>Superadmin</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group"><label><input type="checkbox" name="is_active" value="1" <?php echo $original_user['is_active'] ? 'checked' : ''; ?>> Active</label></div>
                </fieldset>
                <fieldset>
                    <legend>Change Password</legend>
                    <div class="form-group"><label for="new_password">New Password</label><input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password"></div>
                </fieldset>
                <button type="submit" class="btn-submit">Update User</button>
                <a href="manage_employees.php" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
<?php require_once '../includes/footer.php'; ?>