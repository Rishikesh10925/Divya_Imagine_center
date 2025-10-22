<?php
$required_role = 'manager';
require_once __DIR__ . '/../includes/auth_check.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: view_templates.php');
    exit();
}

if (!isset($_FILES['template_file']) || $_FILES['template_file']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = 'Template upload failed or no file provided.';
    header('Location: view_templates.php');
    exit();
}

$category = isset($_POST['category']) ? trim($_POST['category']) : '';
if (empty($category)) {
    $_SESSION['error_message'] = 'Category is required.';
    header('Location: view_templates.php');
    exit();
}

$allowed_ext = ['docx'];
$originalName = $_FILES['template_file']['name'];
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_ext)) {
    $_SESSION['error_message'] = 'Only .docx files are allowed.';
    header('Location: view_templates.php');
    exit();
}

$base_dir = __DIR__ . '/../report_templates';
$safe_category = preg_replace('/[^a-zA-Z0-9_-]/', '_', $category);
$target_dir = $base_dir . '/' . $safe_category;
if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        $_SESSION['error_message'] = 'Failed to create category directory.';
        header('Location: view_templates.php');
        exit();
    }
}

$target_path = $target_dir . '/' . basename($originalName);

// Move uploaded file, overwriting existing
if (!move_uploaded_file($_FILES['template_file']['tmp_name'], $target_path)) {
    $_SESSION['error_message'] = 'Failed to move uploaded file.';
    header('Location: view_templates.php');
    exit();
}

$_SESSION['success_message'] = 'Template uploaded successfully.';
header('Location: view_templates.php');
exit();
?>
