<?php
$page_title = "Detailed Analytics";
$required_role = "manager"; //
require_once '../includes/auth_check.php'; //
require_once '../includes/db_connect.php'; //

// --- 1. GET AND PREPARE FILTERS & PAGINATION ---
$today_date = date('Y-m-d'); //
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $today_date; //
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $today_date;     //
$referral_type = isset($_GET['referral_type']) ? $_GET['referral_type'] : 'all'; //
$doctor_id = isset($_GET['doctor_id']) && $_GET['doctor_id'] !== 'all' ? (int)$_GET['doctor_id'] : 'all'; //
$main_test = isset($_GET['main_test']) ? $_GET['main_test'] : 'all'; //
$sub_test_id = isset($_GET['sub_test']) && $_GET['sub_test'] !== 'all' ? (int)$_GET['sub_test'] : 'all'; //
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; //
$records_per_page = 20; //
$offset = ($page - 1) * $records_per_page; //

$showReferredByColumn = true; //

// --- 2. BUILD DYNAMIC WHERE CLAUSE FOR MAIN DATA QUERY ---
$base_query_from = "
    FROM bills b
    JOIN patients p ON b.patient_id = p.id
    JOIN users u ON b.receptionist_id = u.id --
    JOIN bill_items bi ON b.id = bi.bill_id AND bi.item_status = 0 -- Filter active items here
    JOIN tests t ON bi.test_id = t.id
    LEFT JOIN referral_doctors rd ON b.referral_doctor_id = rd.id
    LEFT JOIN doctor_test_payables dtp ON rd.id = dtp.doctor_id AND bi.test_id = dtp.test_id
"; //
$where_clauses = ["b.created_at BETWEEN ? AND ?", "b.bill_status != 'Void'"]; //
$end_date_for_query = $end_date . ' 23:59:59'; //
$params = [$start_date, $end_date_for_query]; //
$types = 'ss'; //
$active_filters = []; //
// --- Active filter display logic ---
if ($start_date !== $today_date || $end_date !== $today_date) { //
    if ($start_date === $end_date) { $active_filters[] = "Date: " . htmlspecialchars($start_date); } else { $active_filters[] = "Date: " . htmlspecialchars($start_date) . " to " . htmlspecialchars($end_date); } //
} else { $active_filters[] = "Date: Today"; } //
if ($referral_type !== 'all') { $where_clauses[] = "b.referral_type = ?"; $params[] = $referral_type; $types .= 's'; $active_filters[] = "Referral Type: " . htmlspecialchars($referral_type); } //
if ($doctor_id !== 'all') { $where_clauses[] = "b.referral_doctor_id = ?"; $params[] = $doctor_id; $types .= 'i'; $doc_stmt = $conn->prepare("SELECT doctor_name FROM referral_doctors WHERE id = ?"); $doc_stmt->bind_param("i", $doctor_id); $doc_stmt->execute(); $doc_name_result = $doc_stmt->get_result(); $doc_name = $doc_name_result->num_rows > 0 ? $doc_name_result->fetch_assoc()['doctor_name'] : 'N/A'; $active_filters[] = "Doctor: Dr. " . htmlspecialchars($doc_name); $doc_stmt->close(); } //
if ($main_test !== 'all') { $where_clauses[] = "t.main_test_name = ?"; $params[] = $main_test; $types .= 's'; $active_filters[] = "Test Category: " . htmlspecialchars($main_test); } //
if ($sub_test_id !== 'all') { $where_clauses[] = "t.id = ?"; $params[] = $sub_test_id; $types .= 'i'; $sub_stmt = $conn->prepare("SELECT sub_test_name FROM tests WHERE id = ?"); $sub_stmt->bind_param("i", $sub_test_id); $sub_stmt->execute(); $sub_name_result = $sub_stmt->get_result(); $sub_name = $sub_name_result->num_rows > 0 ? $sub_name_result->fetch_assoc()['sub_test_name'] : 'N/A'; $active_filters[] = "Test: " . htmlspecialchars($sub_name); $sub_stmt->close();} //
$where_sql = " WHERE " . implode(' AND ', $where_clauses); //

// --- 3. GET TOTAL RECORD COUNT (Total Tests) FOR PAGINATION & CARD ---
// This remains the same, it correctly counts the filtered items.
$count_query = "SELECT COUNT(bi.id) as total " . $base_query_from . $where_sql; //
$stmt_count = $conn->prepare($count_query); //
if ($stmt_count === false) { die("Error preparing count query: " . $conn->error); } //
$stmt_count->bind_param($types, ...$params); //
$stmt_count->execute(); //
$total_records = $stmt_count->get_result()->fetch_assoc()['total']; // This IS the correct Total Tests count
$total_pages = ceil($total_records / $records_per_page); //
$stmt_count->close(); //

// --- 4. GET PAGINATED DATA FOR THE TABLE ---
// This query remains the same as the previous correct version
$data_query = "
    SELECT
        b.id as bill_id, b.invoice_number, p.name as patient_name, b.created_at,
        u.username as receptionist_name, --
        b.gross_amount, b.net_amount, b.discount, b.discount_by,
        b.referral_type, b.referral_source_other, rd.doctor_name, b.referral_doctor_id, --
        t.default_payable_amount,
        t.main_test_name, t.sub_test_name, t.price as test_price,
        dtp.payable_amount as specific_payable_amount,
        bi.id as bill_item_id
    " . $base_query_from . $where_sql . " ORDER BY b.id DESC, bi.id ASC LIMIT ? OFFSET ?"; //
$data_params = $params; $data_types = $types; //
$data_params[] = $records_per_page; $data_params[] = $offset; $data_types .= 'ii'; //
$stmt_data = $conn->prepare($data_query); //
if ($stmt_data === false) { die("Error preparing data query: " . $conn->error); } //
$stmt_data->bind_param($data_types, ...$data_params); //
$stmt_data->execute(); //
$report_data = $stmt_data->get_result()->fetch_all(MYSQLI_ASSOC); //
$stmt_data->close(); //

// --- 5. CALCULATE SUMMARY CARDS BASED ON *ALL* FILTERED ITEMS ---
// ****** NEW ACCURATE SUMMARY CALCULATION ******
$summary_select_parts = [
    "SUM(t.price) as total_gross_items", // Sum of prices of filtered tests
    "SUM(CASE WHEN b.gross_amount > 0 THEN b.discount * (t.price / b.gross_amount) ELSE 0 END) as total_proportional_discount", // Sum of proportional discounts
    "SUM(CASE WHEN b.discount_by = 'Doctor' AND b.gross_amount > 0 THEN b.discount * (t.price / b.gross_amount) ELSE 0 END) as total_proportional_discount_doctor",
    "SUM(CASE WHEN b.discount_by != 'Doctor' AND b.gross_amount > 0 THEN b.discount * (t.price / b.gross_amount) ELSE 0 END) as total_proportional_discount_center"
];
// If a specific doctor is selected, add payable calculation to the query
if ($doctor_id !== 'all') {
    $summary_select_parts[] = "SUM(CASE
        WHEN b.referral_type = 'Doctor' AND b.referral_doctor_id = ? AND (b.discount = 0 OR b.discount_by = 'Center')
        THEN COALESCE(dtp.payable_amount, t.default_payable_amount, 0)
        ELSE 0
    END) as total_doctor_payable_items";
    // Add doctor_id to params specifically for this part of the query
    $summary_params_payable = $params; // Copy existing params
    $summary_params_payable[] = $doctor_id; // Add doctor ID for the CASE statement
    $summary_types_payable = $types . 'i'; // Add type for doctor ID
} else {
    $summary_params_payable = $params; // Use original params if no specific doctor
    $summary_types_payable = $types;
}

$summary_query_items = "SELECT " . implode(", ", $summary_select_parts) . $base_query_from . $where_sql;

$stmt_summary_items = $conn->prepare($summary_query_items);
if ($stmt_summary_items === false) { die("Error preparing item summary query: " . $conn->error); }
$stmt_summary_items->bind_param($summary_types_payable, ...$summary_params_payable); // Use potentially modified params/types
$stmt_summary_items->execute();
$summary_items_row = $stmt_summary_items->get_result()->fetch_assoc();
$stmt_summary_items->close();

// Assign values based on the accurate item summary query
$total_tests = $total_records; // Use the count from section 3
$total_gross_items = (float)($summary_items_row['total_gross_items'] ?? 0);
$total_proportional_discount = (float)($summary_items_row['total_proportional_discount'] ?? 0);
$total_discount_by_doctor = (float)($summary_items_row['total_proportional_discount_doctor'] ?? 0);
$total_discount_by_center = (float)($summary_items_row['total_proportional_discount_center'] ?? 0);
$total_revenue = $total_gross_items - $total_proportional_discount; // Net revenue based on filtered items

// Assign doctor-specific card values
$doctor_gross = 0;
$doctor_net = 0;
$doctor_discount = 0;
$doctor_total_payable = 0;

if ($doctor_id !== 'all') {
    $doctor_gross = $total_gross_items;
    $doctor_net = $total_revenue;
    $doctor_discount = $total_proportional_discount;
    $doctor_total_payable = (float)($summary_items_row['total_doctor_payable_items'] ?? 0); // Get from the summary query directly
} else {
    // For 'All Doctors', these specific cards aren't shown, but general ones use overall totals
    // Keep general totals assignment from above ($total_revenue, etc.)
}


// --- Fetch data for dropdowns (Existing Code) ---
$doctors = $conn->query("SELECT id, doctor_name FROM referral_doctors WHERE is_active = 1 ORDER BY doctor_name"); //
$main_tests_result = $conn->query("SELECT DISTINCT main_test_name FROM tests ORDER BY main_test_name"); //
$all_tests_result = $conn->query("SELECT id, main_test_name, sub_test_name FROM tests ORDER BY main_test_name, sub_test_name"); //
$all_tests_by_category = []; //
while($row = $all_tests_result->fetch_assoc()) { $all_tests_by_category[$row['main_test_name']][] = ['id' => $row['id'], 'name' => $row['sub_test_name']]; } //

// --- Colspan (Existing Code) ---
$colspan = 10; //
if ($showReferredByColumn) { $colspan++; } //

require_once '../includes/header.php'; //
?>

<style>
    /* Existing Styles */
    .action-header, .action-cell { display: none; } /* */
    .show-delete-actions .action-header, .show-delete-actions .action-cell { display: table-cell; } /* */
    #delete-mode-toggle { cursor: pointer; transition: all 0.3s ease; border: 2px solid transparent; } /* */
    #delete-mode-toggle.delete-active { border-color: var(--danger-color, #dc3545); box-shadow: 0 0 10px rgba(220, 53, 69, 0.5); } /* */
    .quick-date-pills .btn-action.active { /* */
        background-color: var(--primary-color, #007bff); /* */
        color: white; /* */
        border-color: var(--primary-color, #007bff); /* */
        font-weight: bold; /* */
    }
    .data-table tbody tr.bill-start td { /* */
        border-top: 1.5px solid #aaa; /* */
    }
    .data-table tbody tr td { /* */
        border-top: 1px solid #e3e6f0; /* */
    }
    .data-table tbody tr.bill-continue .merged-cell { /* */
        color: transparent; /* */
        user-select: none; /* */
     }
</style>

<div class="page-container">
    <h1>Detailed Analysis & Reporting</h1>
    <form method="GET" action="analytics.php" id="filter-form" class="filter-form compact-filters">
        <div class="filter-group">
            <div class="form-group"><label>Quick Dates</label>
                <div class="quick-date-pills">
                    <button type="button" class="btn-action" data-range="today">Today</button>
                    <button type="button" class="btn-action" data-range="week">This Week</button>
                    <button type="button" class="btn-action" data-range="month">This Month</button>
                    <button type="button" class="btn-action" data-range="last_month">Last Month</button>
                </div>
            </div>
            <div class="form-group"><label for="start_date">Start</label><input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" style="color: #000 !important;"></div>
            <div class="form-group"><label for="end_date">End</label><input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" style="color: #000 !important;"></div>
        </div>
         <div class="filter-group"><div class="form-group"><label for="analytics_referral_type">Referral Type</label><select name="referral_type" id="analytics_referral_type" style="color: #000 !important;"><option value="all">All Types</option><option value="Doctor" <?php if($referral_type == 'Doctor') echo 'selected'; ?>>Doctor</option><option value="Self" <?php if($referral_type == 'Self') echo 'selected'; ?>>Self</option><option value="Other" <?php if($referral_type == 'Other') echo 'selected'; ?>>Other</option></select></div><div class="form-group" id="analytics_doctor_filter" style="display:<?php echo ($referral_type === 'Doctor') ? 'flex' : 'none'; ?>;"><label for="doctor_id">Doctor</label><select name="doctor_id" style="color: #000 !important;"><option value="all">All Doctors</option><?php $doctors->data_seek(0); while($doc = $doctors->fetch_assoc()): ?><option value="<?php echo $doc['id']; ?>" <?php if($doctor_id == $doc['id']) echo 'selected'; ?>>Dr. <?php echo htmlspecialchars($doc['doctor_name']); ?></option><?php endwhile; ?></select></div></div>
        <div class="filter-group"><div class="form-group"><label for="analytics_main_test">Test Category</label><select name="main_test" id="analytics_main_test" style="color: #000 !important;"><option value="all">All Categories</option><?php $main_tests_result->data_seek(0); while($cat = $main_tests_result->fetch_assoc()): ?><option value="<?php echo htmlspecialchars($cat['main_test_name']); ?>" <?php if($main_test == $cat['main_test_name']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['main_test_name']); ?></option><?php endwhile; ?></select></div><div class="form-group"><label for="analytics_sub_test">Specific Test</label><select name="sub_test" id="analytics_sub_test" style="color: #000 !important;"><option value="all">All Tests</option></select></div></div>
        <div class="filter-actions"><a href="analytics.php" class="btn-cancel">Reset</a><button type="submit" class="btn-submit">Apply</button><a href="#" id="export-link" class="btn-export">Download CSV</a></div>
    </form>
    <?php if(!empty($active_filters)): ?>
    <div class="active-filters" style="margin-top: 1.5rem; padding: 10px; background-color: #e9ecef; border-radius: 8px;">
        <strong style="margin-right: 10px;">Active Filters:</strong>
        <?php foreach($active_filters as $filter): ?><span style="display: inline-block; background-color: #007bff; color: white; padding: 5px 10px; border-radius: 15px; margin: 2px; font-size: 0.9em;"><?php echo $filter; ?></span><?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="summary-cards">
        <?php if ($doctor_id !== 'all'): // Specific Doctor View ?>
            <div class="summary-card" id="delete-mode-toggle"><h3>Total Tests</h3><p><?php echo $total_records; ?></p></div>
            <div class="summary-card"><h3>Gross Amount (Filtered)</h3><p>₹ <?php echo number_format($doctor_gross, 2); ?></p></div>
            <div class="summary-card"><h3>Discount (Filtered)</h3><p>₹ <?php echo number_format($doctor_discount, 2); ?></p></div>
            <div class="summary-card"><h3>Net Revenue (Filtered)</h3><p>₹ <?php echo number_format($doctor_net, 2); ?></p></div>
            <div class="summary-card"><h3>Doctor Payable (Filtered)</h3><p>₹ <?php echo number_format($doctor_total_payable, 2); ?></p></div>
        <?php else: // All Doctors View ?>
            <div class="summary-card" id="delete-mode-toggle"><h3>Total Tests</h3><p><?php echo $total_records; ?></p></div>
            <div class="summary-card"><h3>Total Revenue (Filtered)</h3><p>₹ <?php echo number_format($total_revenue, 2); ?></p></div>
            <div class="summary-card"><h3>Discount by Center (Filtered)</h3><p>₹ <?php echo number_format($total_discount_by_center, 2); ?></p></div>
            <div class="summary-card"><h3>Discount by Doctors (Filtered)</h3><p>₹ <?php echo number_format($total_discount_by_doctor, 2); ?></p></div>
        <?php endif; ?>
    </div>
    <table class="data-table" id="analytics-table">
         <thead>
            <tr>
                <th>S.No.</th><th>Bill ID</th><th>Patient</th>
                <th>Receptionist</th>
                <?php if ($showReferredByColumn): ?><th>Referred By</th><?php endif; ?>
                <th>Main Test</th><th>Sub Test</th>
                <th>Discount</th>
                <th>Test Price</th>
                <th>Doctor Payable (₹)</th><th>Date</th>
                <th class="action-header">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // ****** Table Body Generation Logic (remains same as previous correct version) ******
            if (!empty($report_data)):
                $start_num = ($page - 1) * $records_per_page + 1; //
                $current_bill_id = null; //

                foreach($report_data as $row): //
                    $is_first_row_for_bill = ($row['bill_id'] !== $current_bill_id); //
                    $row_class = $is_first_row_for_bill ? 'bill-start' : 'bill-continue'; //

                    // Calculate payable amount
                    $payable_for_this_row = 0; //
                     if ($row['referral_type'] == 'Doctor' && ($row['discount'] == 0 || $row['discount_by'] == 'Center')) { //
                        if (isset($row['specific_payable_amount']) && $row['specific_payable_amount'] !== null) { //
                            $payable_for_this_row = (float)$row['specific_payable_amount']; //
                        }
                        elseif (isset($row['default_payable_amount'])) { //
                            $payable_for_this_row = (float)$row['default_payable_amount']; //
                        }
                    }
            ?>
            <tr class="<?php echo $row_class; ?>">
                 <td><?php echo $start_num++; ?></td>
                 <td class="<?php echo $is_first_row_for_bill ? '' : 'merged-cell'; ?>"><?php if ($is_first_row_for_bill) echo htmlspecialchars($row['bill_id']); else echo '&nbsp;'; ?></td>
                 <td class="<?php echo $is_first_row_for_bill ? '' : 'merged-cell'; ?>"><?php if ($is_first_row_for_bill) echo htmlspecialchars($row['patient_name']); else echo '&nbsp;'; ?></td>
                 <td class="<?php echo $is_first_row_for_bill ? '' : 'merged-cell'; ?>"><?php if ($is_first_row_for_bill) echo htmlspecialchars($row['receptionist_name']); else echo '&nbsp;'; ?></td>
                 <?php if ($showReferredByColumn): ?>
                    <td class="<?php echo $is_first_row_for_bill ? '' : 'merged-cell'; ?>"><?php if ($is_first_row_for_bill) { if ($row['referral_type'] == 'Doctor' && !empty($row['doctor_name'])) { echo 'Dr. ' . htmlspecialchars($row['doctor_name']); } elseif ($row['referral_type'] == 'Other') { echo 'Other (' . htmlspecialchars($row['referral_source_other']) . ')'; } else { echo 'Self'; } } else { echo '&nbsp;'; } ?></td>
                <?php endif; ?>
                <td><?php echo htmlspecialchars($row['main_test_name']); ?></td>
                <td><?php echo htmlspecialchars($row['sub_test_name']); ?></td>
                <td class="<?php echo $is_first_row_for_bill ? '' : 'merged-cell'; ?>"><?php if ($is_first_row_for_bill) { $discount_display = number_format($row['discount'], 2); if ($row['discount'] > 0) { $discount_display .= ($row['discount_by'] == 'Doctor') ? ' (D)' : ' (C)'; } echo $discount_display; } else { echo '&nbsp;'; } ?></td>
                <td><?php echo number_format($row['test_price'], 2); ?></td>
                <td><?php echo number_format($payable_for_this_row, 2); ?></td>
                 <td class="<?php echo $is_first_row_for_bill ? '' : 'merged-cell'; ?>"><?php if ($is_first_row_for_bill) echo date('d-m-Y', strtotime($row['created_at'])); else echo '&nbsp;'; ?></td>
                <td class="action-cell"><form action="delete_bill_item.php" method="POST" style="display:inline;"><input type="hidden" name="bill_item_id" value="<?php echo $row['bill_item_id']; ?>"><button type="submit" class="btn-action btn-danger" onclick="return confirm('Are you sure you want to hide this test from the report?');">Delete</button></form></td>
            </tr>
            <?php
                    $current_bill_id = $row['bill_id']; //
                endforeach;
            else: ?>
                <tr><td colspan="<?php echo $colspan; ?>" style="text-align:center;">No data found for the selected filters.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="pagination"><?php $query_params = $_GET; for ($i = 1; $i <= $total_pages; $i++) { $query_params['page'] = $i; $link = 'analytics.php?' . http_build_query($query_params); $active_class = ($i == $page) ? 'active' : ''; echo "<a href='{$link}' class='{$active_class}'>{$i}</a> "; } ?></div> </div>
<script>
// ... (All existing JavaScript code remains unchanged) ...
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date(); //
    const todayStr = today.toISOString().slice(0, 10); //
    const currentStartDate = '<?php echo $start_date; ?>'; //
    const currentEndDate = '<?php echo $end_date; ?>'; //

    document.querySelectorAll('[data-range]').forEach(button => { //
        button.addEventListener('click', function() { //
            const range = this.dataset.range; //
            let startDateStr, endDateStr; let tempDate = new Date(); //
            switch(range) { //
                case 'today': startDateStr = endDateStr = todayStr; break; //
                case 'week': //
                    const dayOfWeek = tempDate.getDay(); //
                    const firstDayOfWeek = new Date(tempDate.setDate(tempDate.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1) )); //
                    startDateStr = firstDayOfWeek.toISOString().slice(0, 10); //
                    endDateStr = todayStr; //
                    break;
                case 'month': //
                    startDateStr = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10); //
                    endDateStr = todayStr; //
                    break;
                case 'last_month': //
                    const lastMonthFirstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1); //
                    const lastMonthLastDay = new Date(today.getFullYear(), today.getMonth(), 0); //
                    startDateStr = lastMonthFirstDay.toISOString().slice(0, 10); //
                    endDateStr = lastMonthLastDay.toISOString().slice(0, 10); //
                    break;
            }
            document.getElementById('start_date').value = startDateStr; //
            document.getElementById('end_date').value = endDateStr; //
            highlightActiveDateButton(this.dataset.range); //
            document.getElementById('filter-form').submit(); //
        });
    });

    function highlightActiveDateButton(activeRange = null) { //
        document.querySelectorAll('.quick-date-pills .btn-action').forEach(btn => { //
            btn.classList.remove('active'); //
            if (activeRange && btn.dataset.range === activeRange) { //
                btn.classList.add('active'); //
            }
        });
    }

    function highlightActiveDateButtonOnLoad() { //
        if (currentStartDate === todayStr && currentEndDate === todayStr) { //
            highlightActiveDateButton('today'); //
        }
         else if (currentStartDate === new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10) && currentEndDate === todayStr) { //
             highlightActiveDateButton('month'); //
         }
        // Add checks for 'week' and 'last_month' if needed
        else {
            highlightActiveDateButton(null); //
        }
    }
    highlightActiveDateButtonOnLoad(); // Call on load


    const allTestsData = <?php echo json_encode($all_tests_by_category); ?>; //
    const currentSubTestId = '<?php echo $sub_test_id; ?>'; //
    document.getElementById('analytics_referral_type').addEventListener('change', function() { document.getElementById('analytics_doctor_filter').style.display = (this.value === 'Doctor') ? 'flex' : 'none'; }); //
    document.getElementById('analytics_main_test').addEventListener('change', function() { //
        const subtestSelect = document.getElementById('analytics_sub_test'); //
        subtestSelect.innerHTML = '<option value="all">All Tests</option>'; //
        const selectedCategory = this.value; //
        if (selectedCategory && allTestsData[selectedCategory]) { //
            allTestsData[selectedCategory].forEach(test => { //
                const option = new Option(test.name, test.id); //
                if (test.id == currentSubTestId) option.selected = true; //
                subtestSelect.add(option); //
            });
        }
    });
    document.getElementById('analytics_referral_type').dispatchEvent(new Event('change')); //
    document.getElementById('analytics_main_test').dispatchEvent(new Event('change')); //

    const deleteModeToggle = document.getElementById('delete-mode-toggle'); //
    const analyticsTable = document.getElementById('analytics-table'); //
    if (deleteModeToggle && analyticsTable) { //
        deleteModeToggle.addEventListener('dblclick', function(event) { //
            event.preventDefault(); //
            if (analyticsTable.classList.contains('show-delete-actions')) { //
                analyticsTable.classList.remove('show-delete-actions'); //
                this.classList.remove('delete-active'); //
                return; //
            }
            const password = prompt("To enter Delete Mode, please enter your password:"); //
            if (!password) { return; } //
            fetch('verify_password.php', { //
                method: 'POST', //
                headers: { 'Content-Type': 'application/json' }, //
                body: JSON.stringify({ password: password }) //
            })
            .then(response => response.json()) //
            .then(data => { //
                if (data.success) { //
                    analyticsTable.classList.add('show-delete-actions'); //
                    deleteModeToggle.classList.add('delete-active'); //
                } else { //
                    alert(data.message || 'Incorrect password.'); //
                }
            })
            .catch(error => { console.error('Error:', error); alert('An error occurred during password verification.'); }); //
        });
    }

    function updateExportLink() { //
        const exportLink = document.getElementById('export-link'); //
        if (exportLink) { //
            const currentParams = new URLSearchParams(window.location.search); //
            currentParams.delete('page'); //
            exportLink.href = 'export_analytics.php?' + currentParams.toString(); //
        }
    }
    updateExportLink(); //
});
</script>

<?php require_once '../includes/footer.php'; ?>