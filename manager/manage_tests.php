<?php
$page_title = "Manage Tests";
$required_role = "manager"; //
require_once '../includes/auth_check.php'; //
require_once '../includes/db_connect.php'; //

$feedback = '';

// --- Handle Add/Edit Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction(); //
    try {
        $test_id = isset($_POST['test_id']) ? (int)$_POST['test_id'] : 0; //

        // ****** UPDATED: Get Main Test Name from select or input ******
        $main_test_input_type = $_POST['main_test_input_type'] ?? 'select';
        if ($main_test_input_type === 'new' && !empty(trim($_POST['new_main_test_name']))) {
            $main_test_name = trim($_POST['new_main_test_name']);
        } elseif (!empty($_POST['main_test_select'])) {
            $main_test_name = trim($_POST['main_test_select']);
        } else {
             throw new Exception("Main Test Name is required."); // Ensure a value is provided
        }
        // ****** END UPDATE ******

        $sub_test_name = trim($_POST['sub_test_name']); //
        $price = (float)$_POST['price']; //
        $default_payable_amount = (float)$_POST['default_payable_amount']; //

        // --- Document Upload Logic (remains the same) ---
        $document_path = null; //
         if (isset($_FILES['document']) && $_FILES['document']['error'] == UPLOAD_ERR_OK) { //
            $upload_dir = '../uploads/test_documents/'; //
            if (!is_dir($upload_dir)) { //
                mkdir($upload_dir, 0777, true); //
            }
            $file_extension = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION); //
            $safe_sub_test_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $sub_test_name); //
            $new_file_name = uniqid('doc_', true) . '_' . $safe_sub_test_name . '.' . $file_extension; //
            $destination = $upload_dir . $new_file_name; //

            if (move_uploaded_file($_FILES['document']['tmp_name'], $destination)) { //
                $document_path = $new_file_name; //
            } else {
                throw new Exception("Failed to upload document."); //
            }
        } elseif ($test_id && !empty($_POST['existing_document'])) { //
             $existing_doc_path = '../uploads/test_documents/' . basename($_POST['existing_document']); //
             if (file_exists($existing_doc_path)) { //
                 $document_path = basename($_POST['existing_document']); //
             } else {
                 $document_path = null; //
                 $feedback .= "<div class='error-banner'>Warning: The previously associated document was not found and has been removed.</div>"; //
             }
        }

        // --- Check if test name already exists (for ADDING only) ---
        if (!$test_id) { //
            $stmt_check = $conn->prepare("SELECT id FROM tests WHERE main_test_name = ? AND sub_test_name = ?"); //
            $stmt_check->bind_param("ss", $main_test_name, $sub_test_name); //
            $stmt_check->execute(); //
            $stmt_check->store_result(); //
            if ($stmt_check->num_rows > 0) { //
                throw new Exception("A test with the same Main Test and Sub Test name already exists."); //
            }
            $stmt_check->close(); //
        }


        if ($test_id) { // UPDATE LOGIC //
            $stmt = $conn->prepare("UPDATE tests SET main_test_name = ?, sub_test_name = ?, price = ?, default_payable_amount = ?, document = ? WHERE id = ?"); //
            $stmt->bind_param("ssddsi", $main_test_name, $sub_test_name, $price, $default_payable_amount, $document_path, $test_id); //
        } else { // ADD NEW LOGIC //
            $stmt = $conn->prepare("INSERT INTO tests (main_test_name, sub_test_name, price, default_payable_amount, document) VALUES (?, ?, ?, ?, ?)"); //
            $stmt->bind_param("ssdds", $main_test_name, $sub_test_name, $price, $default_payable_amount, $document_path); //
        }
        $stmt->execute(); //
        $stmt->close(); //
        $conn->commit(); //
        $feedback .= "<div class='success-banner'>Test saved successfully!</div>"; //

        // --- Clear edit state after successful save/update ---
        $is_editing = false;
        $edit_test = null;
        // Redirect to clear POST data and show updated list + feedback
        $_SESSION['feedback'] = $feedback; // Store feedback in session
        header("Location: manage_tests.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback(); //
        $error_details = $e->getMessage(); //
        if ($conn->errno === 1062) { //
            $error_details = "A test with the same Main Test and Sub Test name already exists."; //
        }
        // --- Keep feedback in variable instead of session for immediate display ---
        $feedback = "<div class='error-banner'>An error occurred: " . htmlspecialchars($error_details) . "</div>";
    }
}

// --- Retrieve feedback from session if redirected ---
if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']);
}


// --- Fetch data for the page (Existing Logic) ---
$edit_test = null; //
$is_editing = isset($_GET['edit']); //

if ($is_editing && !$feedback) { // Only fetch if editing AND no error occurred on POST
    $edit_id = (int)$_GET['edit']; //
    $edit_stmt = $conn->prepare("SELECT id, main_test_name, sub_test_name, price, default_payable_amount, document FROM tests WHERE id = ?"); //
    $edit_stmt->bind_param("i", $edit_id); //
    $edit_stmt->execute(); //
    $edit_test = $edit_stmt->get_result()->fetch_assoc(); //
    $edit_stmt->close(); //
}

// --- Get Filter Values (Existing Logic) ---
$filter_main_test = isset($_GET['main_test']) && $_GET['main_test'] !== 'all' ? trim($_GET['main_test']) : 'all'; //
$filter_sub_test = isset($_GET['sub_test']) ? trim($_GET['sub_test']) : ''; //

// --- Fetch distinct main test names FOR FILTER AND FORM DROPDOWNS ---
$categories_result = $conn->query("SELECT DISTINCT main_test_name FROM tests ORDER BY main_test_name ASC"); //
$categories = $categories_result->fetch_all(MYSQLI_ASSOC); //
// --- NEW: Convert to a simple array for easier checking ---
$existing_categories = array_column($categories, 'main_test_name');

// --- Fetch all tests FOR THE TABLE DISPLAY (with filters applied - Existing Logic) ---
$sql = "SELECT id, main_test_name, sub_test_name, price, default_payable_amount, document FROM tests"; //
$where_clauses = []; //
$params = []; //
$types = ''; //

if ($filter_main_test !== 'all') { //
    $where_clauses[] = "main_test_name = ?"; //
    $params[] = $filter_main_test; //
    $types .= 's'; //
}
if (!empty($filter_sub_test)) { //
    $where_clauses[] = "sub_test_name LIKE ?"; //
    $params[] = "%{$filter_sub_test}%"; //
    $types .= 's'; //
}

if (!empty($where_clauses)) { //
    $sql .= " WHERE " . implode(" AND ", $where_clauses); //
}

$sql .= " ORDER BY main_test_name, sub_test_name"; //

$stmt_tests = $conn->prepare($sql); //
if ($stmt_tests === false) { die("Error preparing test list query: " . $conn->error); } //

if (!empty($params)) { //
    $stmt_tests->bind_param($types, ...$params); //
}
$stmt_tests->execute(); //
$tests_result = $stmt_tests->get_result(); //
$tests = $tests_result->fetch_all(MYSQLI_ASSOC); //
$stmt_tests->close(); //


require_once '../includes/header.php'; //
?>

<style>
    /* Existing Styles */
    .form-container-collapsible { /* */
        max-height: 0; /* */
        overflow: hidden; /* */
        transition: max-height 0.5s ease-in-out, padding 0.5s ease-in-out, border 0.5s ease-in-out; /* */
        padding: 0 20px; /* */
        margin: 0; /* */
        border: 1px solid transparent; /* */
        background-color: #f9fafb; /* */
        border-radius: var(--border-radius, 8px); /* */
    }
    .form-container-collapsible.visible { /* */
        max-height: 1000px; /* */
        padding: 20px; /* */
        border: 1px solid var(--border-color, #eee); /* */
        box-shadow: var(--shadow, 0 4px 12px rgba(0,0,0,0.08)); /* */
        margin-top: 1.5rem; /* */
    }
    .filter-bar { /* */
        display: flex; /* */
        flex-wrap: wrap; /* */
        gap: 1.5rem; /* */
        align-items: flex-end; /* */
        padding: 1rem 1.5rem; /* */
        background-color: #f8f9fa; /* */
        border-radius: var(--border-radius, 8px); /* */
        margin-bottom: 1.5rem; /* */
        border: 1px solid var(--border-color, #eee); /* */
    }
    .filter-bar .form-group { /* */
        margin-bottom: 0; /* */
        flex-grow: 1; /* */
    }
     .filter-bar .form-group label { /* */
        margin-bottom: 0.3rem; /* */
        font-size: 0.9em; /* */
        color: var(--text-light); /* */
    }
    .filter-bar input[type="text"], .filter-bar select { /* */
         width: 100%; /* */
         box-sizing: border-box; /* */
         padding: 0.5rem; /* */
         border: 1px solid #ccc; /* */
         border-radius: 4px; /* */
         color: #333 !important; /* */
    }
     .filter-bar .btn-submit, .filter-bar .btn-cancel { /* */
        height: calc(1.5em + 1rem + 2px); /* */
        padding: 0.5rem 1.2rem; /* */
        margin-left: 1rem; /* */
        flex-shrink: 0; /* */
     }
    /* NEW: Hide the 'New Category' input initially */
    #new_main_test_group {
        display: none;
    }
</style>

<div class="page-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1>Manage Tests</h1>
        <button id="toggle-form-btn" class="btn-submit"><?php echo ($is_editing || $feedback) ? 'Close Form' : 'Add New Test'; ?></button> </div>

    <?php echo $feedback; ?>

    <div class="management-form form-container-collapsible <?php echo ($is_editing || $feedback) ? 'visible' : ''; ?>" id="add-edit-form-container"> <h3><?php echo $edit_test ? 'Edit Test Details' : 'Add New Test'; ?></h3>
        <form action="manage_tests.php<?php echo $is_editing ? '?edit='.$edit_test['id'] : ''; ?>" method="POST" enctype="multipart/form-data">
             <input type="hidden" name="test_id" value="<?php echo $edit_test['id'] ?? 0; ?>">
            <?php if ($edit_test && !empty($edit_test['document'])): ?>
                <input type="hidden" name="existing_document" value="<?php echo htmlspecialchars($edit_test['document']); ?>">
            <?php endif; ?>
            <input type="hidden" id="main_test_input_type" name="main_test_input_type" value="select"> <div class="form-row">
                <div class="form-group">
                    <label for="main_test_select">Main Test Name</label>
                    <select id="main_test_select" name="main_test_select" required>
                        <option value="">-- Select Category --</option>
                        <?php
                        $selected_main_test = $edit_test['main_test_name'] ?? '';
                        foreach ($existing_categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>" <?php echo ($selected_main_test === $category) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="--new--">** Add New Category **</option>
                    </select>
                </div>
                 <div class="form-group" id="new_main_test_group"> <label for="new_main_test_name">New Category Name</label>
                    <input type="text" id="new_main_test_name" name="new_main_test_name">
                 </div>
                 <div class="form-group"><label>Sub Test Name</label><input type="text" name="sub_test_name" required value="<?php echo htmlspecialchars($edit_test['sub_test_name'] ?? ''); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price (₹)</label>
                    <input type="number" name="price" step="0.01" value="<?php echo htmlspecialchars($edit_test['price'] ?? ''); ?>" required min="0">
                </div>
                <div class="form-group">
                    <label>Default Payable Amount (₹)</label>
                    <input type="number" name="default_payable_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($edit_test['default_payable_amount'] ?? '0.00'); ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="document_upload">Upload Report Template (.docx)</label>
                    <input type="file" name="document" id="document_upload" accept=".docx">
                     <?php if ($edit_test && !empty($edit_test['document'])): ?>
                         <?php
                           $doc_file_path = '../uploads/test_documents/' . htmlspecialchars($edit_test['document']);
                           if (file_exists($doc_file_path)) {
                         ?>
                           <p style="margin-top: 5px;">Current Template:
                               <a href="<?php echo $doc_file_path; ?>" target="_blank">
                                   <?php echo htmlspecialchars($edit_test['document']); ?>
                               </a>
                           </p>
                         <?php } else { ?>
                            <p style="margin-top: 5px; color: red;">Current template file not found.</p>
                         <?php } ?>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn-submit"><?php echo $edit_test ? 'Update Test' : 'Save Test'; ?></button>
            <a href="manage_tests.php" class="btn-cancel">Cancel</a>
        </form>
    </div>

    <div class="table-container">
        <h3>Existing Tests</h3>

        <form action="manage_tests.php" method="GET" class="filter-bar">
             <div class="form-group">
                <label for="filter_main_test">Main Test Category</label>
                <select name="main_test" id="filter_main_test"> <option value="all">All Categories</option> <?php foreach($categories as $category): ?> <option value="<?php echo htmlspecialchars($category['main_test_name']); ?>" <?php echo ($filter_main_test === $category['main_test_name']) ? 'selected' : ''; ?>> <?php echo htmlspecialchars($category['main_test_name']); ?> </option>
                    <?php endforeach; ?> </select>
             </div>
             <div class="form-group">
                <label for="filter_sub_test">Sub Test Name</label>
                <input type="text" name="sub_test" id="filter_sub_test" placeholder="Search sub test..." value="<?php echo htmlspecialchars($filter_sub_test); ?>"> </div>
             <button type="submit" class="btn-submit">Filter</button> <a href="manage_tests.php" class="btn-cancel" style="text-decoration: none;">Reset</a> </form>
        <table class="data-table">
             <thead> <tr> <th>Main Test</th> <th>Sub Test</th> <th>Price (₹)</th> <th>Default Payable (₹)</th> <th>Template</th> <th>Actions</th> </tr>
            </thead>
            <tbody> <?php if (!empty($tests)): foreach ($tests as $test): ?> <tr> <td><?php echo htmlspecialchars($test['main_test_name']); ?></td> <td><?php echo htmlspecialchars($test['sub_test_name']); ?></td> <td><?php echo number_format($test['price'], 2); ?></td> <td><?php echo number_format($test['default_payable_amount'], 2); ?></td> <td> <?php
                                $doc_display = 'No Template'; //
                                if (!empty($test['document'])) { //
                                    $doc_file_path = '../uploads/test_documents/' . htmlspecialchars($test['document']); //
                                    if (file_exists($doc_file_path)) { //
                                        $doc_display = '<a href="' . $doc_file_path . '" target="_blank">View Template</a>'; //
                                    } else {
                                        $doc_display = '<span style="color: red;">File Missing</span>'; //
                                    }
                                }
                                echo $doc_display; //
                              ?>
                        </td>
                        <td> <a href="?edit=<?php echo $test['id']; ?>" class="btn-action btn-edit">Edit</a> </td>
                    </tr>
                <?php endforeach; else: ?> <tr><td colspan="6" style="text-align:center;">No tests found<?php echo (!empty($params) ? ' matching your filters' : ''); ?>.</td></tr> <?php endif; ?> </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Collapsible form logic ---
    const toggleBtn = document.getElementById('toggle-form-btn');
    const formContainer = document.getElementById('add-edit-form-container');
    const isEditing = <?php echo json_encode($is_editing); ?>;
    // --- Keep form open if there was a POST error ---
    const hasFeedback = <?php echo json_encode(!empty($feedback)); ?>;

    if (isEditing || hasFeedback) { // Keep open if editing or if there was feedback (likely an error)
        formContainer.classList.add('visible');
        toggleBtn.textContent = (isEditing && !hasFeedback) ? 'Close Form' : 'Cancel'; // Show 'Close' only if editing *without* error
    }

    toggleBtn.addEventListener('click', function() {
        if (formContainer.classList.contains('visible')) {
            // If visible, always act as cancel/close
             window.location.href = 'manage_tests.php'; // Go back to clean state
        } else {
            // If hidden, show it
            formContainer.classList.toggle('visible');
            this.textContent = 'Cancel'; // Button now acts as Cancel
        }
    });

    // ****** NEW: Dropdown/Input toggle logic ******
    const mainTestSelect = document.getElementById('main_test_select');
    const newMainTestGroup = document.getElementById('new_main_test_group');
    const newMainTestInput = document.getElementById('new_main_test_name');
    const mainTestInputType = document.getElementById('main_test_input_type');

    mainTestSelect.addEventListener('change', function() {
        if (this.value === '--new--') {
            newMainTestGroup.style.display = 'block';
            newMainTestInput.required = true; // Make required when visible
            mainTestInputType.value = 'new'; // Track that new input is used
            this.removeAttribute('required'); // Dropdown no longer strictly required
        } else {
            newMainTestGroup.style.display = 'none';
            newMainTestInput.required = false; // Not required when hidden
            newMainTestInput.value = ''; // Clear value when hidden
            mainTestInputType.value = 'select'; // Track that select is used
            this.setAttribute('required', 'required'); // Dropdown is required again if not adding new
        }
    });

    // --- Trigger change on load if editing to potentially show 'New Category' field ---
    // (This part is tricky if the saved main_test_name *was* a new one, might need adjustment
    // if you frequently edit tests immediately after adding with a new category)
    if (isEditing) {
        // Simple check: if the saved value isn't in the options (excluding '--new--'), assume it was new.
        let found = false;
        for (let i = 0; i < mainTestSelect.options.length; i++) {
            if (mainTestSelect.options[i].value === mainTestSelect.value && mainTestSelect.value !== '--new--') {
                found = true;
                break;
            }
        }
        // If the current value wasn't found in the list, select '--new--' and show the input
        if (!found && mainTestSelect.value !== '') {
             mainTestSelect.value = '--new--';
             newMainTestGroup.style.display = 'block';
             newMainTestInput.required = true;
             newMainTestInput.value = "<?php echo htmlspecialchars($edit_test['main_test_name'] ?? '', ENT_QUOTES); ?>"; // Pre-fill with current name
             mainTestInputType.value = 'new';
             mainTestSelect.removeAttribute('required');
        } else if (mainTestSelect.value === '--new--') {
             // Ensure the field is shown if '--new--' happens to be selected on load (unlikely but safe)
             newMainTestGroup.style.display = 'block';
             newMainTestInput.required = true;
             mainTestInputType.value = 'new';
             mainTestSelect.removeAttribute('required');
        }
    }


});
</script>

<?php require_once '../includes/footer.php'; ?>