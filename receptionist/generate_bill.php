<?php
// --- PDF Library Autoloader ---
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$page_title = "Generate Bill";
$required_role = "receptionist";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// #############################################################################
// ### IMPORTANT CONFIGURATION: SET YOUR PDF SAVE PATH HERE ###
// #############################################################################
// Set the *base path* where the 'YEAR/MONTH/DAY' folders will be created.
// *IMPORTANT*: Make sure your web server (e.g., Apache) has permission to write to this directory.
// Example for XAMPP on Windows: 'C:/xampp/htdocs/diagnostic-center/saved_bills'
// Example for Linux: '/var/www/html/diagnostic-center/saved_bills'
$pdf_save_path = '../saved_bills'; // <-- CHANGE THIS PATH AS NEEDED

/**
 * Generates and saves the bill PDF to a structured directory.
 */
function generateAndSaveBillPdf($bill_id, $conn, $patient_name, $base_save_path) {
    // 1. --- Fetch all necessary bill data ---
    $stmt = $conn->prepare(
        "SELECT b.*, p.name as patient_name, p.age, p.sex, p.mobile_number, rd.doctor_name as referral_doctor_name
         FROM bills b
         JOIN patients p ON b.patient_id = p.id
         LEFT JOIN referral_doctors rd ON b.referral_doctor_id = rd.id
         WHERE b.id = ?"
    );
    $stmt->bind_param("i", $bill_id);
    $stmt->execute();
    $bill = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$bill) { return false; }

    $items_stmt = $conn->prepare(
        "SELECT t.main_test_name, t.sub_test_name, t.price
         FROM bill_items bi JOIN tests t ON bi.test_id = t.id
         WHERE bi.bill_id = ?"
    );
    $items_stmt->bind_param("i", $bill_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    $items_html = '';
    while($item = $items_result->fetch_assoc()) {
        $test_name = htmlspecialchars($item['main_test_name'] . ' - ' . $item['sub_test_name']);
        $price = number_format($item['price'], 2);
        $items_html .= "<tr><td>{$test_name}</td><td style='text-align:right;'>{$price}</td></tr>";
    }
    $items_stmt->close();
    
    // 2. --- Prepare the HTML content for the PDF ---
    $bill_date = date('d-m-Y', strtotime($bill['created_at']));
    $ref_physician = ($bill['referral_doctor_name']) ? 'Dr. ' . htmlspecialchars($bill['referral_doctor_name']) : 'Self';

    $html = <<<HTML
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; } .container { width: 100%; margin: 0 auto; }
        .header { text-align: center; border: 1px solid #000; padding: 10px; margin-bottom: 20px; } .header h1 { margin: 0; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; } .details-table td { padding: 4px; }
        .items-table { width: 100%; border-collapse: collapse; } .items-table th, .items-table td { border: 1px solid #000; padding: 8px; }
        .items-table th { background-color: #f2f2f2; } .totals { float: right; width: 250px; margin-top: 20px; } .totals td { text-align: right; }
    </style>
    <div class="container">
        <div class="header"><h1>DIVYA IMAGING CENTER</h1></div> <h3 style="text-align:center; margin-bottom:20px;">BILL RECEIPT</h3>
        <table class="details-table">
            <tr><td><strong>BILL NO:</strong> {$bill['id']}</td><td><strong>BILL DATE:</strong> {$bill_date}</td></tr>
            <tr><td><strong>Patient Name:</strong> {$bill['patient_name']}</td><td><strong>Mobile No:</strong> {$bill['mobile_number']}</td></tr>
            <tr><td><strong>Age & Gender:</strong> {$bill['age']} / {$bill['sex']}</td><td></td></tr>
            <tr><td><strong>Ref. Physician:</strong> {$ref_physician}</td><td></td></tr>
        </table>
        <table class="items-table">
            <thead><tr><th>Investigation Name</th><th style='text-align:right;'>Amount</th></tr></thead>
            <tbody>{$items_html}</tbody>
        </table>
        <table class="totals">
            <tr><td>Sub Total:</td><td>{$bill['gross_amount']}</td></tr>
            <tr><td>Disc Amt:</td><td>{$bill['discount']}</td></tr>
            <tr><td><strong>TOTAL:</strong></td><td><strong>{$bill['net_amount']}</strong></td></tr>
        </table>
    </div>
    HTML;

    // 3. --- Generate the PDF ---
    $options = new Options(); $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options); $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait'); $dompdf->render();

    // 4. --- Create directory structure and save the file ---
    $year = date('Y'); $month = date('m_F'); $day = date('d');
    $directory = rtrim($base_save_path, '/') . "/{$year}/{$month}/{$day}";
    if (!is_dir($directory)) { mkdir($directory, 0775, true); }
    $safe_patient_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $patient_name);
    $filename = "{$bill['id']}_{$safe_patient_name}.pdf";
    $file_path = "{$directory}/{$filename}";
    file_put_contents($file_path, $dompdf->output());
    return true;
}


$error_message = '';
$success_message = '';

// --- FORM SUBMISSION LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        $patient_name = trim($_POST['patient_name']);
        $patient_age = (int)$_POST['patient_age'];
        $patient_sex = trim($_POST['patient_sex']);
        $patient_address = trim($_POST['patient_address']);
        $patient_city = trim($_POST['patient_city']);
        $patient_mobile = trim($_POST['patient_mobile']);

        $stmt_patient = $conn->prepare("INSERT INTO patients (name, age, sex, address, city, mobile_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_patient->bind_param("sissss", $patient_name, $patient_age, $patient_sex, $patient_address, $patient_city, $patient_mobile);
        $stmt_patient->execute();
        $patient_id = $stmt_patient->insert_id;
        $stmt_patient->close();

        $receptionist_id = $_SESSION['user_id'];
        $referral_type = trim($_POST['referral_type']);
        $referral_doctor_id = null;
        $referral_source_other = null;

        if ($referral_type === 'Doctor') {
            if (!empty($_POST['referral_doctor_id']) && $_POST['referral_doctor_id'] === 'other') {
                $new_doctor_name = trim($_POST['other_doctor_name']);
                if (!empty($new_doctor_name)) {
                    $stmt_doc_check = $conn->prepare("SELECT id FROM referral_doctors WHERE doctor_name = ?");
                    $stmt_doc_check->bind_param("s", $new_doctor_name);
                    $stmt_doc_check->execute();
                    $doc_result = $stmt_doc_check->get_result();
                    if ($doc_result->num_rows > 0) {
                        $referral_doctor_id = $doc_result->fetch_assoc()['id'];
                    } else {
                        $stmt_doc_insert = $conn->prepare("INSERT INTO referral_doctors (doctor_name, is_active) VALUES (?, 1)");
                        $stmt_doc_insert->bind_param("s", $new_doctor_name);
                        $stmt_doc_insert->execute();
                        $referral_doctor_id = $stmt_doc_insert->insert_id;
                        $stmt_doc_insert->close();
                    }
                    $stmt_doc_check->close();
                }
            } else if (!empty($_POST['referral_doctor_id'])) {
                $referral_doctor_id = (int)$_POST['referral_doctor_id'];
            }
        } else if ($referral_type === 'Other') {
            $referral_source_other = trim($_POST['referral_source_other_select']);
        }
        
        $gross_amount = (float)$_POST['gross_amount']; $discount = (float)$_POST['discount'];
        $discount_by = trim($_POST['discount_by']); $net_amount = (float)$_POST['net_amount'];
        $payment_mode = trim($_POST['payment_mode']); $payment_status = trim($_POST['payment_status']);
        $amount_paid = 0.00; $balance_amount = 0.00;

        if ($payment_status === 'Half Paid') {
            $amount_paid = isset($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : 0.00;
            if ($amount_paid <= 0) { $payment_status = 'Due'; $amount_paid = 0; $balance_amount = $net_amount; } 
            else if ($amount_paid >= $net_amount) { $payment_status = 'Paid'; $amount_paid = $net_amount; $balance_amount = 0; } 
            else { $balance_amount = $net_amount - $amount_paid; }
        } else if ($payment_status === 'Paid') { $amount_paid = $net_amount; $balance_amount = 0; } 
        else { $amount_paid = 0; $balance_amount = $net_amount; }

        $stmt_bill = $conn->prepare("INSERT INTO bills (patient_id, receptionist_id, referral_type, referral_doctor_id, referral_source_other, gross_amount, discount, discount_by, net_amount, amount_paid, balance_amount, payment_mode, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_bill->bind_param("iisisddsdddss", $patient_id, $receptionist_id, $referral_type, $referral_doctor_id, $referral_source_other, $gross_amount, $discount, $discount_by, $net_amount, $amount_paid, $balance_amount, $payment_mode, $payment_status);
        $stmt_bill->execute();
        $bill_id = $stmt_bill->insert_id;
        $stmt_bill->close();

        $selected_tests = json_decode($_POST['selected_tests_json'], true);
        if (empty($selected_tests)) { throw new Exception("No tests were selected."); }
        $stmt_items = $conn->prepare("INSERT INTO bill_items (bill_id, test_id) VALUES (?, ?)");
        foreach ($selected_tests as $test_id) {
            // ### THIS IS THE FIX ###
            // Assign the value to a variable before passing it to bind_param
            $current_test_id = (int)$test_id;
            $stmt_items->bind_param("ii", $bill_id, $current_test_id);
            $stmt_items->execute();
        }
        $stmt_items->close();

        $conn->commit();
        
        try { generateAndSaveBillPdf($bill_id, $conn, $patient_name, $pdf_save_path); } 
        catch (Exception $pdf_e) { error_log("PDF generation failed for bill #{$bill_id}: " . $pdf_e->getMessage()); }

        require_once '../includes/functions.php';
        log_system_action($conn, 'BILL_CREATED', $bill_id, "Generated bill for patient '{$patient_name}' with Net Amount: {$net_amount}.");
        header("Location: preview_bill.php?bill_id=" . $bill_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to create bill. Error: " . $e->getMessage();
    }
}

// --- DATA FETCHING FOR FORM ---
$doctors_result = $conn->query("SELECT id, doctor_name FROM referral_doctors WHERE is_active = 1 ORDER BY doctor_name ASC");
$tests_result = $conn->query("SELECT id, main_test_name, sub_test_name, price FROM tests ORDER BY main_test_name, sub_test_name ASC");
$tests_by_category = [];
while ($test = $tests_result->fetch_assoc()) {
    $tests_by_category[$test['main_test_name']][] = $test;
}

require_once '../includes/header.php';
?>

<div class="form-container">
    <h1>Generate New Patient Bill</h1>
    <?php if ($error_message): ?><div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

    <form action="generate_bill.php" method="POST" id="bill-form">
        <fieldset>
            <legend>Patient Information</legend>
             <div class="form-row">
                <div class="form-group">
                    <label for="patient_name">Patient Name</label>
                    <input type="text" id="patient_name" name="patient_name" required>
                </div>
                <div class="form-group">
                    <label for="patient_age">Age</label>
                    <input type="number" id="patient_age" name="patient_age" required min="0">
                </div>
                 <div class="form-group">
                    <label for="patient_sex">Gender</label>
                    <select id="patient_sex" name="patient_sex" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="patient_address">Address</label>
                <textarea id="patient_address" name="patient_address" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="patient_city">City</label>
                    <input type="text" id="patient_city" name="patient_city">
                </div>
                <div class="form-group">
                    <label for="patient_mobile">Mobile Number</label>
                    <input type="text" id="patient_mobile" name="patient_mobile">
                </div>
            </div>
        </fieldset>

       <fieldset>
            <legend>Referral Information</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="referral_type">Referral Type</label>
                    <select id="referral_type" name="referral_type" required>
                        <option value="Self">Self</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Other">Other Source</option>
                    </select>
                </div>
                <div class="form-group" id="doctor-select-group" style="display:none;">
                    <label for="referral_doctor_id">Referring Doctor</label>
                    <select id="referral_doctor_id" name="referral_doctor_id" style="width: 100%;">
                        <option value="">Select Doctor</option>
                        <?php while($doctor = $doctors_result->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['id']; ?>">Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?></option>
                        <?php endwhile; ?>
                        <option value="other">Other...</option>
                    </select>
                </div>
                 <div class="form-group" id="other-doctor-name-group" style="display:none;">
                    <label for="other_doctor_name">Specify Doctor Name</label>
                    <input type="text" id="other_doctor_name" name="other_doctor_name" placeholder="Enter doctor's full name">
                </div>
                <div class="form-group" id="other-source-group" style="display:none;">
                    <label for="referral_source_other_select">Source</label>
                    <select id="referral_source_other_select" name="referral_source_other_select">
                        <option value="">Select Source</option>
                        <option value="Friend">Friend/Family</option>
                        <option value="Newspaper">Newspaper Ad</option>
                        <option value="TV Ad">TV Ad</option>
                        <option value="Social Media">Social Media</option>
                        <option value="Walk-in">Walk-in</option>
                    </select>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <legend>Tests Selection</legend>
            <div class="form-row">
                <div class="form-group">
                    <label for="main-test-select">1. Select Test Category</label>
                    <select id="main-test-select">
                        <option value="">-- Select Category --</option>
                        <?php foreach (array_keys($tests_by_category) as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="sub-test-select">2. Select Specific Test</label>
                    <select id="sub-test-select" disabled>
                        <option value="">-- Select Category First --</option>
                    </select>
                </div>
            </div>
            <div id="selected-tests">
                <h4>Selected Tests</h4>
                <ul id="selected-tests-list"></ul>
            </div>
        </fieldset>

        <fieldset>
            <legend>Billing Details</legend>
            <div class="form-row">
                <div class="form-group"><label for="gross_amount">Gross Amount</label><input type="text" id="gross_amount" name="gross_amount" readonly required></div>
                <div class="form-group">
                    <label for="discount_by">Discount By</label>
                    <select id="discount_by" name="discount_by">
                        <option value="Center" selected>Center</option>
                        <option value="Doctor">Doctor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="discount">Discount (in amount)</label>
                    <input type="number" id="discount" name="discount" value="0" step="0.01" min="0">
                </div>
                <div class="form-group"><label for="net_amount">Net Amount</label><input type="text" id="net_amount" name="net_amount" readonly required></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="payment_mode">Payment Mode</label>
                    <select id="payment_mode" name="payment_mode" required>
                        <option value="Cash">Cash</option><option value="Card">Card</option><option value="UPI">UPI</option><option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment_status">Payment Status</label>
                    <select id="payment_status" name="payment_status" required>
                        <option value="Paid" selected>Paid</option>
                        <option value="Due">Due</option>
                        <option value="Half Paid">Half Paid</option>
                    </select>
                </div>
            </div>
            <div class="form-row" id="half-paid-details" style="display: none; background-color: #f0f8ff; padding: 15px; border-radius: 5px; margin-top: 10px;">
                <div class="form-group">
                    <label for="amount_paid">Amount Paid Now</label>
                    <input type="number" id="amount_paid" name="amount_paid" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label for="balance_amount">Balance Amount</label>
                    <input type="text" id="balance_amount" name="balance_amount" readonly>
                </div>
            </div>
        </fieldset>
        
        <input type="hidden" name="selected_tests_json" id="selected_tests_json" required>
        <button type="submit" class="btn-submit">Generate Bill</button>
    </form>
</div>
<script>
    const testsData = <?php echo json_encode($tests_by_category); ?>;
</script>
<?php require_once '../includes/footer.php'; ?>