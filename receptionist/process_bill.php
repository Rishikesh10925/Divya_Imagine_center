<?php
$page_title = "Process Bill";
$required_role = "receptionist";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

$error_message = '';
$bill_id = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        // 1. Process patient data
        $patient_name = trim($_POST['patient_name']);
        $patient_age = (int)$_POST['patient_age'];
        $patient_sex = trim($_POST['patient_sex']);
        $patient_address = trim($_POST['patient_address']);
        
        $stmt_patient = $conn->prepare("INSERT INTO patients (name, age, sex, address) VALUES (?, ?, ?, ?)");
        if (!$stmt_patient) {
            throw new Exception("Patient preparation failed: " . $conn->error);
        }
        $stmt_patient->bind_param("siss", $patient_name, $patient_age, $patient_sex, $patient_address);
        if (!$stmt_patient->execute()) {
            throw new Exception("Patient insertion failed: " . $stmt_patient->error);
        }
        $patient_id = $stmt_patient->insert_id;
        $stmt_patient->close();

        // 2. Process bill data
        $receptionist_id = $_SESSION['user_id'];
        $referral_type = trim($_POST['referral_type']);
        
        // Handle NULL values properly
        $referral_doctor_id = ($referral_type === 'Doctor' && !empty($_POST['referral_doctor_id'])) 
            ? (int)$_POST['referral_doctor_id'] 
            : NULL;
            
        $referral_source_other = ($referral_type === 'Other' && !empty($_POST['referral_source_other'])) 
            ? trim($_POST['referral_source_other']) 
            : NULL;
        
        $gross_amount = (float)$_POST['gross_amount'];
        $discount = (float)$_POST['discount'];
        $net_amount = (float)$_POST['net_amount'];
        $payment_mode = trim($_POST['payment_mode']);

        // Fix for the binding issue - create variables that can be passed by reference
        $stmt_bill = $conn->prepare("INSERT INTO bills (patient_id, receptionist_id, referral_type, referral_doctor_id, referral_source_other, gross_amount, discount, net_amount, payment_mode, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Paid')");
        if (!$stmt_bill) {
            throw new Exception("Bill preparation failed: " . $conn->error);
        }
        
        // Create specific variables for binding
        $bill_patient_id = $patient_id;
        $bill_receptionist_id = $receptionist_id;
        $bill_referral_type = $referral_type;
        $bill_referral_doctor_id = $referral_doctor_id;
        $bill_referral_source_other = $referral_source_other;
        $bill_gross_amount = $gross_amount;
        $bill_discount = $discount;
        $bill_net_amount = $net_amount;
        $bill_payment_mode = $payment_mode;
        
        $stmt_bill->bind_param(
            "iisssddds", 
            $bill_patient_id, 
            $bill_receptionist_id, 
            $bill_referral_type, 
            $bill_referral_doctor_id, 
            $bill_referral_source_other, 
            $bill_gross_amount, 
            $bill_discount, 
            $bill_net_amount, 
            $bill_payment_mode
        );
        
        if (!$stmt_bill->execute()) {
            throw new Exception("Bill insertion failed: " . $stmt_bill->error);
        }
        $bill_id = $stmt_bill->insert_id;
        $stmt_bill->close();

        // 3. Process bill items
        $selected_tests = json_decode($_POST['selected_tests_json'], true);
        if (empty($selected_tests)) {
            throw new Exception("No tests were selected.");
        }
        
        $stmt_items = $conn->prepare("INSERT INTO bill_items (bill_id, test_id) VALUES (?, ?)");
        if (!$stmt_items) {
            throw new Exception("Items preparation failed: " . $conn->error);
        }
        
        foreach ($selected_tests as $test_id) {
            $test_id_int = (int)$test_id;
            $stmt_items->bind_param("ii", $bill_id, $test_id_int);
            if (!$stmt_items->execute()) {
                throw new Exception("Item insertion failed: " . $stmt_items->error);
            }
        }
        $stmt_items->close();

        $conn->commit();
        header("Location: print_bill.php?bill_id=" . $bill_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to create bill: " . $e->getMessage();
        error_log("Bill processing error: " . $e->getMessage());
    }
}

require_once '../includes/header.php';
?>
<div class="form-container">
    <?php if ($error_message): ?>
        <div class="error-banner">
            <h3>Error Processing Bill</h3>
            <p><?php echo htmlspecialchars($error_message); ?></p>
            <p>Please check the data and try again.</p>
        </div>
        <a href="generate_bill.php" class="btn-back">Back to Bill Generation</a>
    <?php else: ?>
        <div class="success-banner">Bill generated successfully!</div>
        <a href="print_bill.php?bill_id=<?php echo $bill_id; ?>" class="btn-submit">View/Print Bill</a>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>