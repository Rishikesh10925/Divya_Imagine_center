<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch events ONLY for the current day
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT id, title, event_date as start, event_type FROM calendar_events WHERE event_date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($events);
exit();