<?php
$page_title = "Fill Test Report";
$required_role = "writer";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
if (!$item_id) {
    header("Location: dashboard.php");
    exit();
}

// Fetch all necessary details for the report header
$stmt_fetch = $conn->prepare(
    "SELECT 
        b.id as bill_id, p.name as patient_name, p.age, p.sex, 
        b.created_at as bill_date, t.sub_test_name, t.document, 
        rd.doctor_name as referring_doctor_name, b.referral_source_other, b.referral_type
     FROM bill_items bi 
     JOIN bills b ON bi.bill_id = b.id 
     JOIN patients p ON b.patient_id = p.id 
     JOIN tests t ON bi.test_id = t.id 
     LEFT JOIN referral_doctors rd ON b.referral_doctor_id = rd.id 
     WHERE bi.id = ?"
);
$stmt_fetch->bind_param("i", $item_id);
$stmt_fetch->execute();
$report_details = $stmt_fetch->get_result()->fetch_assoc();
$stmt_fetch->close();
if (!$report_details) die("Report details not found.");

// Handle form submission to save the report
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_content = trim($_POST['report_content']);
    $stmt_update = $conn->prepare("UPDATE bill_items SET report_content = ?, report_status = 'Completed' WHERE id = ?");
    $stmt_update->bind_param("si", $report_content, $item_id);
    if ($stmt_update->execute()) {
        $_SESSION['success_message'] = "Report for Bill #{$report_details['bill_id']} has been saved successfully.";
        header("Location: /diagnostic-center/writer/dashboard.php");
        exit();
    }
}

require_once '../includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.7.0/mammoth.browser.min.js"></script>
<script src="https://cdn.tiny.cloud/1/r41uafihmk98jko98ir0noqb18kzh4r4xtzbknnosb4dk2sd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<div class="form-container">
    <h1>Writing Report for: <?php echo htmlspecialchars($report_details['sub_test_name']); ?></h1>
    <div class="patient-details-header">
        <strong>Patient:</strong> <span id="patient-name"><?php echo htmlspecialchars($report_details['patient_name']); ?></span> | 
        <strong>Age/Gender:</strong> <span id="patient-age"><?php echo $report_details['age']; ?></span>/<span id="patient-sex"><?php echo $report_details['sex']; ?></span> | 
        <strong>Bill No:</strong> <span id="bill-id"><?php echo $report_details['bill_id']; ?></span>
    </div>
    
    <div id="report-data" 
        data-document-path="<?php echo htmlspecialchars($report_details['document']); ?>"
        data-referring-doctor="<?php echo htmlspecialchars($report_details['referring_doctor_name'] ?? 'Self'); ?>"
        style="display: none;">
    </div>

    <form action="fill_report.php?item_id=<?php echo $item_id; ?>" method="POST">
        <textarea id="report_content" name="report_content"></textarea>
        <div style="margin-top:20px; text-align: right;">
            <a href="dashboard.php" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-submit">Save & Complete Report</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#report_content',
        
        // --- FIX: REMOVED DEPRECATED 'spellchecker' PLUGIN ---
        plugins: 'lists link image table code help wordcount autolink', 
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist outdent indent | link image',
        
        // --- FIX: ENABLED BROWSER'S NATIVE SPELL CHECK ---
        browser_spellcheck: true,
        
        height: 700,
        menubar: false,
        content_style: 'body { font-family: Times New Roman, serif; font-size: 12pt; margin: 1rem auto; max-width: 8.5in; padding: 1in; }',
        
            setup: function(editor) {
            // --- PREDICTIVE AUTOCOMPLETE LOGIC (This part is correct and will now work) ---
            const medicalTerms = [
                'No significant abnormalities noted.', 'Findings are within normal limits.', 
                'Clinical correlation is recommended.', 'Further evaluation is advised.',
                'degenerative changes', 'inflammatory changes', 'post-traumatic changes',
                'lesion', 'edema', 'fracture', 'effusion', 'hematoma', 'stenosis',
                'echotexture', 'vascularity', 'calcification', 'nodule', 'mass', 'cyst',
                'Abdomen', 'Pelvis', 'Thorax', 'Cervical Spine', 'Lumbar Spine', 'Dorsal Spine',
                'MRI', 'CT Scan', 'Ultrasound', 'X-Ray',
                'benign', 'malignant', 'acute', 'chronic', 'mild', 'moderate', 'severe'
            ];

            editor.ui.registry.addAutocompleter('medicalTerms', {
                ch: '', 
                minChars: 2,
                columns: 1,
                fetch: function(pattern) {
                    return new Promise((resolve) => {
                        const lowerCasePattern = pattern.toLowerCase();
                        const matches = medicalTerms.filter(term => 
                            term.toLowerCase().includes(lowerCasePattern)
                        );
                        resolve(matches.map(term => ({
                            value: term,
                            text: term,
                            icon: '✓'
                        })));
                    });
                },
                onAction: function(autocompleteApi, rng, value) {
                    editor.selection.setRng(rng);
                    editor.insertContent(value);
                    autocompleteApi.hide();
                }
            });

            // This part requests server-side generation of the .docx template and patient info,
            // then converts the generated .docx to HTML (via mammoth) and inserts into the editor.
            editor.on('init', function() {
                const reportDataContainer = document.getElementById('report-data');
                const docxPath = reportDataContainer.dataset.documentPath;

                const patientName = document.getElementById('patient-name').textContent;
                const patientAge = document.getElementById('patient-age').textContent;
                const patientSex = document.getElementById('patient-sex').textContent;
                const billId = document.getElementById('bill-id').textContent;
                const referredBy = `Dr. ${reportDataContainer.dataset.referringDoctor}`;

                const patientHeaderHtml = `
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;" border="1">
                        <tbody>
                            <tr>
                                <td style="padding: 8px;"><strong>Patient Name:</strong></td>
                                <td style="padding: 8px;">${patientName}</td>
                                <td style="padding: 8px;"><strong>Age/Gender:</strong></td>
                                <td style="padding: 8px;">${patientAge} / ${patientSex}</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px;"><strong>Bill No:</strong></td>
                                <td style="padding: 8px;">${billId}</td>
                                <td style="padding: 8px;"><strong>Referred By:</strong></td>
                                <td style="padding: 8px;"><strong>${referredBy}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <hr style="margin-bottom: 20px;">`;

                // Show a loading placeholder while server generates the document
                editor.setContent('<p><em>Loading template...</em></p>');

                // Call server to generate the report .docx
                fetch('generate_report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'item_id=' + encodeURIComponent('<?php echo $item_id; ?>')
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        editor.setContent(`<p style="color: red;"><strong>Error:</strong> ${data.error}</p>`);
                        return;
                    }

                    // data.file is a relative path like writer/reports/....docx
                    const docxUrl = window.location.origin + '/diagnostic-center/' + data.file.replace(/^\/+/, '');

                    return fetch(docxUrl)
                        .then(res => {
                            if (!res.ok) throw new Error('Failed to download generated document');
                            return res.arrayBuffer();
                        })
                        .then(arrayBuffer => mammoth.convertToHtml({ arrayBuffer: arrayBuffer }))
                        .then(result => {
                            editor.setContent(patientHeaderHtml + result.value);
                        });
                })
                .catch(err => {
                    console.error('Error generating/loading template:', err);
                    editor.setContent(`<p style="color: red;"><strong>Error:</strong> Could not load the report template. Details: ${err.message}</p>`);
                });
            });
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>