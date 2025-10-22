<?php
$required_role = 'writer';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

// This endpoint generates a report from a local Word template using PHPWord.
// Expected POST params: item_id
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid method']);
    exit();
}

if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing item_id']);
    exit();
}

$item_id = (int)$_POST['item_id'];

// fetch report details and template mapping
$stmt = $conn->prepare(
    "SELECT bi.id as item_id,
            b.id as bill_id,
            p.name as patient_name,
            p.age,
            p.sex,
            t.main_test_name,
            t.sub_test_name,
            t.document,
            b.created_at,
            rd.doctor_name as referring_doctor_name,
            b.referral_source_other,
            b.referral_type
     FROM bill_items bi
     JOIN bills b ON bi.bill_id = b.id
     JOIN patients p ON b.patient_id = p.id
     JOIN tests t ON bi.test_id = t.id
     LEFT JOIN referral_doctors rd ON b.referral_doctor_id = rd.id
     WHERE bi.id = ?"
);
$stmt->bind_param('i', $item_id);
$stmt->execute();
$details = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$details) {
    http_response_code(404);
    echo json_encode(['error' => 'Report item not found']);
    exit();
}

$main_test = $details['main_test_name'];
$doc_path = $details['document'];

// Map to local report_templates/ location
$base_templates_dir = realpath(__DIR__ . '/../report_templates');
if ($base_templates_dir === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Templates directory not found']);
    exit();
}

$category_dir = $base_templates_dir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $main_test);
$template_file = '';

// Look for a .docx file in category matching the stored document name or any docx
if (!is_dir($category_dir)) {
    http_response_code(404);
    echo json_encode(['error' => 'Template not found for selected test.']);
    exit();
}

// If a document path exists in DB, try to resolve it
if (!empty($doc_path)) {
    $candidate = realpath(__DIR__ . '/../' . ltrim($doc_path, '/\\'));
    if ($candidate && strpos($candidate, $base_templates_dir) === 0 && file_exists($candidate)) {
        $template_file = $candidate;
    }
}

if (empty($template_file) && $category_dir) {
    $sub_slug = strtolower($details['sub_test_name']);
    $sub_slug = preg_replace('/[^a-z0-9]+/', '_', $sub_slug);
    $candidates = [
        $category_dir . '/' . $sub_slug . '.docx',
        $category_dir . '/' . $sub_slug . '_template.docx',
        $category_dir . '/' . $sub_slug . '.DOCX',
        $category_dir . '/' . $sub_slug . '_template.DOCX'
    ];
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $template_file = $candidate;
            break;
        }
    }
    if (empty($template_file)) {
        $fallback = glob($category_dir . '/*.docx');
        if ($fallback && count($fallback) > 0) {
            $template_file = $fallback[0];
        }
    }
}

if (empty($template_file) || !file_exists($template_file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Template not found for selected test.']);
    exit();
}

// Prepare output reports directory
$reports_dir = realpath(__DIR__ . '/reports');
if ($reports_dir === false) {
    // attempt to create
    if (!mkdir(__DIR__ . '/reports', 0777, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create reports directory.']);
        exit();
    }
    $reports_dir = realpath(__DIR__ . '/reports');
}

try {
    $templateProcessor = new TemplateProcessor($template_file);

    // Fill placeholders
    $templateProcessor->setValue('patient_name', $details['patient_name']);
    $templateProcessor->setValue('age', $details['age']);
    $templateProcessor->setValue('gender', $details['sex']);

    $ref_doctor = $details['referring_doctor_name'] ?? '';
    if (!$ref_doctor && !empty($details['referral_source_other'])) {
        $ref_doctor = $details['referral_source_other'];
    }
    if (!$ref_doctor && !empty($details['referral_type'])) {
        $ref_doctor = $details['referral_type'];
    }
    $templateProcessor->setValue('ref_doctor', $ref_doctor ?: 'Self');

    $report_date = $details['created_at'] ? date('Y-m-d', strtotime($details['created_at'])) : date('Y-m-d');
    $templateProcessor->setValue('date', $report_date);

    // Construct output filename
    $safe_name = preg_replace('/[^A-Za-z0-9_-]/', '_', $details['patient_name']);
    $safe_test = preg_replace('/[^A-Za-z0-9_-]/', '_', $details['main_test_name']);
    $out_filename = $safe_name . '_' . $safe_test . '_' . date('Ymd_His') . '.docx';
    $out_path = $reports_dir . '/' . $out_filename;

    $templateProcessor->saveAs($out_path);

    // Optionally: update DB with document path relative to project
    $relative_path = 'writer/reports/' . $out_filename;
    $stmtu = $conn->prepare("UPDATE bill_items SET document = ? WHERE id = ?");
    $stmtu->bind_param('si', $relative_path, $item_id);
    $stmtu->execute();
    $stmtu->close();

    echo json_encode(['success' => true, 'file' => $relative_path]);
    exit();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate report: ' . $e->getMessage()]);
    exit();
}

?>
