<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch all events for FullCalendar
// FullCalendar expects keys like 'title' and 'start' (for date)
$result = $conn->query("SELECT id, title, event_date as start, event_type FROM calendar_events");
$events = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($events);
exit();