<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Check if user is logged in at all
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Redirect to login page if not logged in
    header("Location: /diagnostic-center/index.php");
    exit();
}

// 2. Check if the page has a role requirement defined
if (isset($required_role)) {
    $user_role = $_SESSION['role'];
    $is_allowed = false;

    // New: Handle if $required_role is an array of roles
    if (is_array($required_role)) {
        if (in_array($user_role, $required_role)) {
            $is_allowed = true;
        }
    } 
    // Old: Handle if $required_role is a single string
    else if (is_string($required_role)) {
        if ($user_role === $required_role) {
            $is_allowed = true;
        }
    }

    // 3. If the user's role is not allowed, block access.
    if (!$is_allowed) {
        http_response_code(403); // Set HTTP status to Forbidden
        die("Forbidden: You do not have permission to access this page.");
    }
}
// If no $required_role is set, access is allowed for any logged-in user.
?>