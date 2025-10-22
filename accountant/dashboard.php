<?php
$page_title = "Accountant Dashboard";
$required_role = "accountant";
require_once '../includes/auth_check.php';
require_once '../includes/header.php';
?>

<style>
    :root {
        --bg: #f6f8fb;
        --text: #1f2937;
        --muted: #6b7280;
        --card: #ffffff;
        --shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        --radius: 12px;
        --green: #10b981;
        --green-100: #ecfdf5;
        --red: #ef4444;
        --red-100: #fef2f2;
        --blue: #3b82f6;
        --blue-100: #eff6ff;
        --orange: #f59e0b;
        --orange-100: #fff7ed;
        --brand: #6366f1;
    }

    .page-container { color: var(--text); }
    .dashboard-header h1 { margin: 0 0 .25rem; font-weight: 700; letter-spacing: .2px; }
    .dashboard-header p { margin: 0; color: var(--muted); }

    /* Filter bar */
    .filter-form.compact-filters {
        display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between;
        gap: 1rem; padding: 1rem 1.25rem; background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow);
    }
    .filter-form.compact-filters .filter-group { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem; }
    .filter-form.compact-filters .form-group { display: flex; flex-direction: column; gap: .35rem; }
    .filter-form.compact-filters .form-group label { font-size: .85rem; font-weight: 600; color: var(--muted); }
    .filter-form.compact-filters input[type="date"] { padding: .5rem .6rem; border: 1px solid #e5e7eb; border-radius: 8px; height: 38px; }
    .quick-date-pills { display:flex; gap:.5rem; }
    .quick-date-pills .btn-action { padding:.45rem .85rem; font-size:.9rem; background:#fff; border:1px solid #e5e7eb; border-radius:999px; transition:.2s; }
    .quick-date-pills .btn-action.active, .quick-date-pills .btn-action:hover { background: var(--brand); color:#fff; border-color: var(--brand); }
    .filter-actions { display:flex; gap:.5rem; }
    .filter-actions .btn-submit { background: var(--brand); color:#fff; border:none; padding:.5rem 1.1rem; border-radius:8px; height:38px; font-weight:700; }
    .filter-actions .btn-cancel { display:inline-flex; align-items:center; text-decoration:none; background:#6b7280; color:#fff; border:none; padding:.5rem 1.1rem; border-radius:8px; height:38px; }

    /* KPI cards */
    .summary-cards { display:grid; grid-template-columns: repeat(4, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    @media (max-width: 1100px) { .summary-cards { grid-template-columns: repeat(2, minmax(220px, 1fr)); } }
    @media (max-width: 640px) { .summary-cards { grid-template-columns: 1fr; } }
    .summary-card { position:relative; background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1rem 1rem; transition: transform .18s ease, box-shadow .18s ease; }
    .summary-card:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(0,0,0,.12); }
    .summary-card h3 { margin: 0 0 .35rem; font-size: .95rem; color: var(--muted); font-weight: 700; display:flex; align-items:center; gap:.5rem; }
    .summary-card p { margin: 0; font-size: 1.4rem; font-weight: 800; letter-spacing: .2px; }
    .summary-card .kpi-icon { position:absolute; right: 14px; top: 14px; font-size: 1.35rem; opacity:.9; }
    .summary-card.earnings { border-left: 6px solid var(--green); background: var(--green-100); }
    .summary-card.discounts { border-left: 6px solid var(--red); background: var(--red-100); }
    .summary-card.payouts { border-left: 6px solid var(--blue); background: var(--blue-100); }
    .summary-card.pending { border-left: 6px solid var(--orange); background: var(--orange-100); }
    .summary-card small { display:block; color: var(--muted); margin-top:.35rem; font-weight:600; }

    /* Charts grid */
    .charts-section { display:grid; grid-template-columns: 2fr 1fr; gap: 1rem; }
    @media (max-width: 1100px) { .charts-section { grid-template-columns: 1fr; } }
    .chart-card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1rem; }
    .chart-card h3 { margin:.1rem 0 1rem; font-size: 1rem; font-weight: 800; color: var(--text); display:flex; align-items:center; gap:.5rem; }
    .chart-card .subtle { color: var(--muted); font-weight:600; font-size:.85rem; }
    .chart-container { position:relative; height: 320px; }

    /* Status / Alert */
    .status-banner { display:none; margin: 0 0 1rem; padding: .75rem 1rem; border-radius: 8px; font-weight: 600; }
    .status-banner.error { display:block; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .status-banner.info { display:block; background: #eff6ff; color: #1e3a8a; border: 1px solid #bfdbfe; }
</style>

<div class="main-content page-container">
    <div class="dashboard-header">
        <div>
            <h1>Accountant's Dashboard</h1>
            <p>Financial overview and key performance indicators for the selected period.</p>
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
            <a href="dashboard.php" class="btn-cancel">Reset</a>
            <button type="submit" class="btn-submit">Apply</button>
        </div>
    </form>


    <div class="summary-cards" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
        <div class="summary-card clickable-card" data-url="manage_payments.php">
            <h3>Total Earnings</h3>
            <p id="kpi-total-earnings">₹ 0.00</p>
        </div>
        <div class="summary-card clickable-card" data-url="discount_report.php">
            <h3>Total Discounts Given</h3>
            <p id="kpi-total-discounts">₹ 0.00</p>
        </div>
        <div class="summary-card clickable-card" data-url="doctor_payouts.php">
            <h3>Total Payouts</h3>
            <p id="kpi-total-payouts">₹ 0.00</p>
        </div>
        <div class="summary-card clickable-card" data-url="doctor_payouts.php">
            <h3>Pending Payouts</h3>
            <p id="kpi-pending-payouts">₹ 0.00</p>
        </div>
    </div>

    <div class="charts-section">
        <div class="chart-container" style="grid-column: span 2;">
            <h3>Revenue vs. Expenses (Last 6 Months)</h3>
            <canvas id="revenueVsExpensesChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Expense Breakdown</h3>
            <canvas id="expenseBreakdownChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Top 5 Doctor Payouts Due</h3>
            <canvas id="topDoctorsPayoutsChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>Revenue by Payment Method</h3>
            <canvas id="paymentModeChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let charts = {};

    const formatCurrency = (value) => {
        const numericValue = Number(value ?? 0);
        return `₹ ${numericValue.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    };

    const chartConfigs = {
        doughnut: (data) => ({
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#fd7e14', '#6610f2'],
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, legend: { position: 'right' } }
        }),
        pie: (data) => ({
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'],
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, legend: { position: 'right' } }
        }),
        bar: (data, label = 'Amount') => ({
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: label,
                    data: data.values,
                    backgroundColor: '#4e73df'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return '₹' + value.toLocaleString('en-IN'); } } }],
                    xAxes: [{
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }]
                }
            }
        }),
        multiBar: (data) => ({
             type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: data.revenue,
                        backgroundColor: '#1cc88a',
                    },
                    {
                        label: 'Expenses',
                        data: data.expenses,
                        backgroundColor: '#e74a3b',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return '₹' + value.toLocaleString('en-IN'); } } }],
                    xAxes: [{
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }]
                }
            }
        })
    };

    function updateDashboard(data) {
        const metrics = data.metrics || {};
        document.getElementById('kpi-total-earnings').textContent = formatCurrency(metrics.total_earnings);
        document.getElementById('kpi-total-discounts').textContent = formatCurrency(metrics.total_discounts);
        document.getElementById('kpi-total-payouts').textContent = formatCurrency(metrics.total_payouts);
        const pendingEl = document.getElementById('kpi-pending-payouts');
        pendingEl.textContent = formatCurrency(metrics.pending_payouts);
        pendingEl.style.color = (metrics.pending_payouts ?? 0) > 0 ? '#e74a3b' : '#1cc88a';

        // Update Charts
        if (data.revenue_vs_expenses) {
            if (charts.revenueVsExpenses) charts.revenueVsExpenses.destroy();
            charts.revenueVsExpenses = new Chart(document.getElementById('revenueVsExpensesChart'), chartConfigs.multiBar(data.revenue_vs_expenses));
        }

        if (data.expense_breakdown) {
            if (charts.expenseBreakdown) charts.expenseBreakdown.destroy();
            charts.expenseBreakdown = new Chart(document.getElementById('expenseBreakdownChart'), chartConfigs.doughnut(data.expense_breakdown));
        }

        if (data.doctor_payouts) {
            if (charts.topDoctorsPayouts) charts.topDoctorsPayouts.destroy();
            charts.topDoctorsPayouts = new Chart(document.getElementById('topDoctorsPayoutsChart'), chartConfigs.bar(data.doctor_payouts));
        }

        if (data.payment_modes) {
            if (charts.paymentMode) charts.paymentMode.destroy();
            charts.paymentMode = new Chart(document.getElementById('paymentModeChart'), chartConfigs.pie(data.payment_modes));
        }
    }

    function fetchData() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const params = new URLSearchParams({
            action: 'getAccountantDashboardData',
            start_date: startDate,
            end_date: endDate
        });

        fetch(`ajax_handler.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Request failed with status ${response.status}`);
                }
                return response.json();
            })
            .then(updateDashboard)
            .catch(error => {
                console.error('Error fetching dashboard data:', error);
                updateDashboard({ metrics: {} });
            });
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
            let startDate = new Date();
            let endDate = new Date();

            switch(range) {
                case 'today':
                    // No change needed
                    break;
                case 'week':
                    const dayOfWeek = today.getDay();
                    startDate.setDate(today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1));
                    break;
                case 'month':
                    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
                case 'last_month':
                    startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    endDate = new Date(today.getFullYear(), today.getMonth(), 0);
                    break;
            }
            
            document.getElementById('start_date').value = startDate.toISOString().slice(0, 10);
            document.getElementById('end_date').value = endDate.toISOString().slice(0, 10);
            fetchData();
        });
    });

    // --- NEW: Add double-click navigation to KPI cards ---
    document.querySelectorAll('.summary-card.clickable-card').forEach(card => {
        card.addEventListener('dblclick', function() {
            const url = this.dataset.url;
            if (url) {
                // This will redirect the user to the specified page
                window.location.href = url;
            }
        });
    });
    
    // Initial fetch for the default date range ('This Month')
    document.querySelector('.quick-date-pills .btn-action[data-range="month"]').click();
});
</script>

<?php require_once '../includes/footer.php'; ?>