<?php
$page_title = "Master Lists";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/header.php';
?>

<main class="main-content">
    <div class="content-header">
        <div class="header-container">
            <h1>Master Data Lists</h1>
        </div>
    </div>
    <p class="mb-4">Select a category below to view the master list of all records in the system.</p>
    
    <div class="kpi-grid">
        <a href="view_tests.php" class="kpi-card-link">
            <div class="kpi-card border-left-info">
                <div class="kpi-content">
                    <div class="kpi-label text-info">View All</div>
                    <div class="kpi-value">Diagnostic Tests</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-vial fa-2x"></i></div>
            </div>
        </a>
        <a href="view_doctors.php" class="kpi-card-link">
            <div class="kpi-card border-left-primary">
                <div class="kpi-content">
                    <div class="kpi-label text-primary">View All</div>
                    <div class="kpi-value">Referring Doctors</div>
                </div>
                <div class="kpi-icon"><i class="fas fa-user-md fa-2x"></i></div>
            </div>
        </a>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>