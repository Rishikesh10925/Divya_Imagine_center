<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: /diagnostic-center/login.php"); exit(); }
$username = htmlspecialchars($_SESSION['username']);
$role = htmlspecialchars($_SESSION['role']);
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Diagnostic Center"; ?></title>
    
    <link rel="stylesheet" href="/diagnostic-center/assets/css/complete.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php if ($role === 'superadmin'): ?>
    <link rel="stylesheet" href="/diagnostic-center/assets/css/superadmin_styles.css">
    <?php elseif ($role === 'writer'): ?>
    <link rel="stylesheet" href="/diagnostic-center/assets/css/writer.css">
    <?php endif; ?>
</head>
<body class="role-<?php echo $role; ?>">
    <header class="main-header">
       <div class="header-container">
            <div class="logo-area"><a href="/diagnostic-center/index.php">Divya Imaging Center</a></div>
            <div class="user-info-area">
                <span>Welcome, <?php echo $username; ?> (<?php echo ucfirst($role); ?>)</span>
                <a href="/diagnostic-center/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </header>

    <nav class="main-navbar">
        <ul>
            <?php if ($role === 'superadmin'): ?>
                <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="compare.php" class="<?php echo ($current_page == 'compare.php') ? 'active' : ''; ?>">Compare</a></li>
                <li><a href="deep_analysis.php" class="<?php echo ($current_page == 'deep_analysis.php') ? 'active' : ''; ?>">Deep Analysis</a></li>
                <li><a href="lists.php" class="<?php echo in_array($current_page, ['lists.php', 'view_tests.php', 'view_doctors.php']) ? 'active' : ''; ?>">Lists</a></li>
                <li><a href="detailed_report.php" class="<?php echo ($current_page == 'detailed_report.php') ? 'active' : ''; ?>">Reports</a></li>
                <li><a href="manage_employees.php" class="<?php echo in_array($current_page, ['manage_employees.php', 'edit_employee.php']) ? 'active' : ''; ?>">Employees</a></li>
                <li><a href="view_audit_log.php" class="<?php echo ($current_page == 'view_audit_log.php') ? 'active' : ''; ?>">Audit Log</a></li>
                <li><a href="manage_calendar.php" class="<?php echo ($current_page == 'manage_calendar.php') ? 'active' : ''; ?>">Calendar</a></li>
            <?php elseif ($role === 'manager'): ?>
                <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="analytics.php" class="<?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>">Analytics</a></li>
                <li><a href="manage_tests.php" class="<?php echo ($current_page == 'manage_tests.php') ? 'active' : ''; ?>">Tests</a></li>
                <li><a href="manage_doctors.php" class="<?php echo in_array($current_page, ['manage_doctors.php', 'manage_doctor_commissions.php']) ? 'active' : ''; ?>">Doctors</a></li>
                <li><a href="expenses.php" class="<?php echo ($current_page == 'expenses.php') ? 'active' : ''; ?>">Expenses</a></li>
                <li><a href="requests.php" class="<?php echo ($current_page == 'requests.php') ? 'active' : ''; ?>">Requests</a></li>
                <li><a href="manage_employees.php" class="<?php echo ($current_page == 'manage_employees.php') ? 'active' : ''; ?>">Employees</a></li>
                                <li><a href="/diagnostic-center/manager/view_due_bills.php" class="<?php echo ($current_page == 'view_due_bills.php') ? 'active' : ''; ?>">Pending Bills</a></li>
                                <li><a href="print_reports.php" class="<?php echo ($current_page == 'print_reports.php') ? 'active' : ''; ?>">Print Reports</a></li>

            <?php elseif ($role === 'accountant'): ?>
                <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="manage_payments.php" class="<?php echo ($current_page == 'manage_payments.php') ? 'active' : ''; ?>">Payments</a></li>
                <li><a href="doctor_payouts.php" class="<?php echo ($current_page == 'doctor_payouts.php') ? 'active' : ''; ?>">Payouts</a></li>
                <li><a href="view_expenses.php" class="<?php echo in_array($current_page, ['view_expenses.php', 'log_expense.php']) ? 'active' : ''; ?>">Expenses</a></li>
            <?php elseif ($role === 'receptionist'): ?>
                <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="generate_bill.php" class="<?php echo in_array($current_page, ['generate_bill.php', 'edit_bill.php']) ? 'active' : ''; ?>">Generate Bill</a></li>
                <li><a href="bill_history.php" class="<?php echo ($current_page == 'bill_history.php') ? 'active' : ''; ?>">Bill History</a></li>
            <?php endif; ?>
        </ul>
    </nav>