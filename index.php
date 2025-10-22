<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in by checking for the 'user_id' session variable
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // If logged in, redirect to the user's specific dashboard
    $role = $_SESSION['role'];
    header("Location: {$role}/dashboard.php");
    exit(); // Important to stop script execution after a redirect
} else {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit();
}
?>
