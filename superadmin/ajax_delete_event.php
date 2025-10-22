<?php
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

$event_id = (int)$_POST['event_id'];

// Get event info for logging before deleting
$stmt_fetch = $conn->prepare("SELECT title FROM calendar_events WHERE id = ?");
$stmt_fetch->bind_param("i", $event_id);
$stmt_fetch->execute();
$event = $stmt_fetch->get_result()->fetch_assoc();
$stmt_fetch->close();

if ($event) {
    // 1. Delete the event
    $stmt_delete = $conn->prepare("DELETE FROM calendar_events WHERE id = ?");
    $stmt_delete->bind_param("i", $event_id);
    
    if ($stmt_delete->execute()) {
        // 2. Log the deletion
        $log_stmt = $conn->prepare("INSERT INTO system_audit_log (user_id, username, action_type, target_id, details) VALUES (?, ?, 'EVENT_DELETED', ?, ?)");
        $details = "Deleted event '{$event['title']}' (ID: {$event_id}).";
        $log_stmt->bind_param("isis", $_SESSION['user_id'], $_SESSION['username'], $event_id, $details);
        $log_stmt->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete event from database.']);
    }
    $stmt_delete->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Event not found.']);
}
exit();