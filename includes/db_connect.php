<?php
// --- Database Configuration ---
// These are the default credentials for a standard XAMPP installation.
// You might need to change them based on your setup.

$servername = "localhost"; // The server where your database is hosted (usually localhost)
$username = "root";        // Your MySQL username (default for XAMPP is 'root')
$password = "";            // Your MySQL password (default for XAMPP is empty)
$dbname = "diagnostic_center_db"; // The name of the database

// --- Create Connection ---
// We use mysqli for a more secure and modern connection.
$conn = new mysqli($servername, $username, $password, $dbname);

// --- Check Connection ---
// This will terminate the script and show an error if the connection fails.
// This is crucial for debugging during development.
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set character set to utf8mb4 for better Unicode support
$conn->set_charset("utf8mb4");

// The $conn variable can now be used in other PHP files to interact with the database.
?>
