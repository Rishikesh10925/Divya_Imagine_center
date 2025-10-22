<?php
$page_title = "Create Report from Template";
$required_role = "writer";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$test_id = isset($_GET['test_id']) ? (int)$_GET['test_id'] : 0;
if (!$test_id) {
    die("Error: No test selected.");
}

// Fetch the details of the selected test template
$stmt_test = $conn->prepare("SELECT main_test_name, sub_test_name, price FROM tests WHERE id = ?");
$stmt_test->bind_param("i", $test_id);
$stmt_test->execute();
$test_details = $stmt_test->get_result()->fetch_assoc();
$stmt_test->close();
if (!$test_details) {
    die("Error: Selected test not found.");
}

$error_message = '';

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        // 1. Create the new patient
        $patient_name = trim($_POST['patient_name']);
        $patient_age = (int)$_POST['patient_age'];
        $patient_sex = trim($_POST['patient_sex']);
        
        $stmt_patient = $conn->prepare("INSERT INTO patients (name, age, sex) VALUES (?, ?, ?)");
        $stmt_patient->bind_param("sis", $patient_name, $patient_age, $patient_sex);
        $stmt_patient->execute();
        $patient_id = $stmt_patient->insert_id;
        $stmt_patient->close();

        // 2. Create a minimal bill record for this report
        $receptionist_id = $_SESSION['user_id']; // Log which writer created it
        $test_price = $test_details['price'];
        
        $stmt_bill = $conn->prepare("INSERT INTO bills (patient_id, receptionist_id, gross_amount, net_amount, payment_status) VALUES (?, ?, ?, ?, 'Due')");
        $stmt_bill->bind_param("iidd", $patient_id, $receptionist_id, $test_price, $test_price);
        $stmt_bill->execute();
        $bill_id = $stmt_bill->insert_id;
        $stmt_bill->close();

        // 3. Create the pending bill_item, which is what the writer needs
        $stmt_item = $conn->prepare("INSERT INTO bill_items (bill_id, test_id) VALUES (?, ?)");
        $stmt_item->bind_param("ii", $bill_id, $test_id);
        $stmt_item->execute();
        $item_id = $stmt_item->insert_id;
        $stmt_item->close();

        $conn->commit();

        // 4. Redirect directly to the fill_report page with the new item ID
        header("Location: fill_report.php?item_id=" . $item_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to create report. Error: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="form-container" style="max-width: 600px;">
    <h1>Create Report for: <?php echo htmlspecialchars($test_details['sub_test_name']); ?></h1>
    <p>Please enter the patient's details below to proceed directly to the report editor.</p>

    <?php if ($error_message): ?>
        <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="create_report_from_template.php?test_id=<?php echo $test_id; ?>" method="POST">
        <fieldset>
            <legend>Patient Information</legend>
             <div class="form-group">
                <label for="patient_name">Patient Name</label>
                <input type="text" id="patient_name" name="patient_name" required>
            </div>
            <div class="form-row">
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
        </fieldset>
        
        <button type="submit" class="btn-submit">Proceed to Report Editor</button>
        <a href="view_templates.php" class="btn-cancel">Cancel</a>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>