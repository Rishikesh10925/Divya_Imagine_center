<?php
$page_title = "Manager Dashboard";
$required_role = "manager";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
require_once '../includes/header.php';
?>

<style>
    .kpi-link, .summary-card-clickable {
        text-decoration: none;
        display: block;
        color: inherit; /* Inherit text color from parent */
    }
    .summary-card-clickable {
        cursor: pointer;
    }
    .chart-container canvas {
        cursor: pointer; /* Change cursor to pointer on hover for charts */
    }
    .text-danger { color: #e74a3b; } /* Style for pending count */
</style>

<div class="main-content page-container">
    <div class="dashboard-header" >
        <div>
            <h1>Manager's Dashboard</h1>
            <p>Business intelligence and operational overview for the selected period.</p>
        </div>
    </div>

    <form id="date-filter-form" class="filter-form compact-filters" style="margin-bottom: 2rem;">
        <div class="filter-group">
            <div class="form-group">
                <label>Quick Dates</label>
                <div class="quick-date-pills">
                    <button type="button" class="btn-action" data-range="today">Today</button>
                    <button type="button" class="btn-action" data-range="week">This Week</button>
                    <button type="button" class="btn-action active" data-range="month">This Month</button>
                    <button type="button" class="btn-action" data-range="last_month">Last Month</button>
                </div>
            </div>
            <div class="form-group">
                <label for="start_date">Start</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>" style="color: #000 !important;">
            </div>
            <div class="form-group">
                <label for="end_date">End</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>" style="color: #000 !important;">
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn-submit">Apply</button>
        </div>
    </form>


    <div class="summary-cards">
        <div class="summary-card">
            <a href="#" id="kpi-link-patients" class="kpi-link">
                <h3>Total Patients</h3>
                <p id="kpi-total-patients">0</p>
            </a>
        </div>
        <div class="summary-card">
            <a href="#" id="kpi-link-bills" class="kpi-link">
                <h3>Total Bills</h3>
                <p id="kpi-total-bills">0</p>
            </a>
        </div>
        
        <div class="summary-card summary-card-clickable" id="pending-bills-card" title="Double-click to view details">
            <h3>Pending Bills</h3>
            <p id="kpi-pending-bills" class="text-danger">0</p>
        </div>
        
        <div class="summary-card">
            <a href="#" id="kpi-link-tests" class="kpi-link">
                <h3>Tests Performed</h3>
                <p id="kpi-total-tests">0</p>
            </a>
        </div>
        <div class="summary-card">
            <a href="#" id="kpi-link-revenue" class="kpi-link">
                <h3>Total Revenue</h3>
                <p id="kpi-total-revenue">₹ 0.00</p>
            </a>
        </div>
    </div>

    <div class="charts-section">
        <div class="chart-container"><h3>Top 5 Test Categories</h3><canvas id="topTestCategoriesChart"></canvas></div>
        <div class="chart-container"><h3>Referral Sources</h3><canvas id="referralSourceChart"></canvas></div>
        <div class="chart-container"><h3>Top 5 Referring Doctors (by Patients)</h3><canvas id="topDoctorsChart"></canvas></div>
        <div class="chart-container"><h3>Revenue by Payment Method</h3><canvas id="paymentModeChart"></canvas></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let charts = {};

    function handleChartClick(event, elements, chartInstance, filterParam) {
        if (elements.length === 0) return;
        const elementIndex = elements[0].index;
        let filterValue;
        if (filterParam === 'doctor_id' && chartInstance.data.ids) {
            filterValue = chartInstance.data.ids[elementIndex];
        } else {
            filterValue = chartInstance.data.labels[elementIndex];
        }
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const url = `analytics.php?start_date=${startDate}&end_date=${endDate}&${filterParam}=${encodeURIComponent(filterValue)}`;
        window.location.href = url;
    }
    
    const chartConfigs = {
        doughnut: (data, onClickHandler) => ({ type: 'doughnut', data: data, options: { responsive: true, maintainAspectRatio: false, legend: { position: 'right' }, onClick: onClickHandler } }),
        pie: (data, onClickHandler) => ({ type: 'pie', data: data, options: { responsive: true, maintainAspectRatio: false, legend: { position: 'right' }, onClick: onClickHandler } }),
        bar: (data, onClickHandler, label = 'Count') => ({ type: 'bar', data: { labels: data.labels, datasets: [{ label: label, data: data.data, backgroundColor: '#4e73df' }] }, options: { responsive: true, maintainAspectRatio: false, legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true } }] }, onClick: onClickHandler } })
    };

    function updateKpiLinks() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const baseUrl = `analytics.php?start_date=${startDate}&end_date=${endDate}`;
        document.getElementById('kpi-link-patients').href = baseUrl;
        document.getElementById('kpi-link-bills').href = baseUrl;
        document.getElementById('kpi-link-tests').href = baseUrl;
        document.getElementById('kpi-link-revenue').href = baseUrl;
    }

    function updateDashboard(data) {
        // Update original KPIs
        document.getElementById('kpi-total-patients').textContent = data.kpis.total_patients || 0;
        document.getElementById('kpi-total-bills').textContent = data.kpis.total_bills || 0;
        document.getElementById('kpi-total-tests').textContent = data.kpis.tests_performed || 0;
        document.getElementById('kpi-total-revenue').textContent = `₹ ${parseFloat(data.kpis.total_revenue || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        
        // --- NEW: Update the Pending Bills KPI ---
        document.getElementById('kpi-pending-bills').textContent = data.kpis.pending_bills_count || 0;

        // Update original charts
        const topTestData = { labels: data.charts.top_test_categories.labels, datasets: [{ data: data.charts.top_test_categories.data, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'] }] };
        if (charts.topTestCategories) charts.topTestCategories.destroy();
        charts.topTestCategories = new Chart(document.getElementById('topTestCategoriesChart'), chartConfigs.doughnut(topTestData, (e, els) => handleChartClick(e, els, charts.topTestCategories, 'main_test')));

        const referralData = { labels: data.charts.referral_sources.labels, datasets: [{ data: data.charts.referral_sources.data, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'] }] };
        if (charts.referralSource) charts.referralSource.destroy();
        charts.referralSource = new Chart(document.getElementById('referralSourceChart'), chartConfigs.pie(referralData, (e, els) => handleChartClick(e, els, charts.referralSource, 'referral_type')));

        const topDoctorsData = { labels: data.charts.top_doctors.labels, data: data.charts.top_doctors.data };
        if (charts.topDoctors) charts.topDoctors.destroy();
        charts.topDoctors = new Chart(document.getElementById('topDoctorsChart'), chartConfigs.bar(topDoctorsData, (e, els) => handleChartClick(e, els, charts.topDoctors, 'doctor_id')));
        charts.topDoctors.data.ids = data.charts.top_doctors.ids;
        
        const paymentModesData = { labels: data.charts.payment_modes.labels, data: data.charts.payment_modes.data };
        if (charts.paymentMode) charts.paymentMode.destroy();
        charts.paymentMode = new Chart(document.getElementById('paymentModeChart'), chartConfigs.bar(paymentModesData, null, 'Revenue'));
        
        updateKpiLinks();
    }

    function fetchData() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        fetch(`ajax_handler.php?start_date=${startDate}&end_date=${endDate}`)
            .then(response => response.json())
            .then(data => { updateDashboard(data); })
            .catch(error => console.error('Error fetching dashboard data:', error));
    }

    document.getElementById('date-filter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        document.querySelectorAll('.quick-date-pills .btn-action').forEach(btn => btn.classList.remove('active'));
        fetchData();
    });

    document.querySelectorAll('.quick-date-pills .btn-action').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.quick-date-pills .btn-action').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const range = this.dataset.range;
            const today = new Date();
            let startDateStr, endDateStr;

            switch(range) {
                case 'today': startDateStr = endDateStr = today.toISOString().slice(0, 10); break;
                case 'week':
                    let firstDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1)));
                    startDateStr = firstDayOfWeek.toISOString().slice(0, 10);
                    endDateStr = new Date().toISOString().slice(0, 10);
                    break;
                case 'month':
                    startDateStr = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10);
                    endDateStr = new Date().toISOString().slice(0, 10);
                    break;
                case 'last_month':
                    const lastMonthFirstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    const lastMonthLastDay = new Date(today.getFullYear(), today.getMonth(), 0);
                    startDateStr = lastMonthFirstDay.toISOString().slice(0, 10);
                    endDateStr = lastMonthLastDay.toISOString().slice(0, 10);
                    break;
            }
            document.getElementById('start_date').value = startDateStr;
            document.getElementById('end_date').value = endDateStr;
            fetchData();
        });
    });
    
    // --- NEW: Double-click listener for the Pending Bills card ---
    document.getElementById('pending-bills-card').addEventListener('dblclick', function() {
        window.location.href = 'view_due_bills.php';
    });
    
    document.querySelector('.quick-date-pills .btn-action[data-range="month"]').click();
});
</script>

<?php require_once '../includes/footer.php'; ?>