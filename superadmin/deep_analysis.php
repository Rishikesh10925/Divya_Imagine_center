<?php
$page_title = "Deep Analysis";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch data for filters
$doctors = $conn->query("SELECT id, doctor_name FROM referral_doctors WHERE is_active = 1 ORDER BY doctor_name");
$main_tests = $conn->query("SELECT DISTINCT main_test_name FROM tests WHERE main_test_name IS NOT NULL AND main_test_name != '' ORDER BY main_test_name");

require_once '../includes/header.php';
?>
<main class="main-content">
    <div class="content-header">
        <div class="header-container">
            <h1>Doctor Deep Dive Analysis</h1>
            <p>Analyze a single doctor's performance over time with custom benchmarks.</p>
        </div>
    </div>

    <div class="page-card filter-bar">
        <form id="deep-analysis-form" class="filter-form-flex">
            <div class="form-group">
                <label for="doctor_id">Doctor</label>
                <select id="doctor_id" name="doctor_id" required>
                    <option value="">-- Select a Doctor --</option>
                    <?php while($doc = $doctors->fetch_assoc()): ?>
                        <option value="<?php echo $doc['id']; ?>">Dr. <?php echo htmlspecialchars($doc['doctor_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="months">Months (multi-select)</label>
                <select id="months" name="months[]" multiple required>
                    <?php for ($m=1; $m<=12; $m++): $month = date('F', mktime(0,0,0,$m, 1, date('Y'))); ?>
                        <option value="<?php echo $m; ?>" selected><?php echo $month; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="year">Year</label>
                <select id="year" name="year" required>
                    <?php $currentYear = date('Y');
                    for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php if($y == $currentYear) echo 'selected'; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="metric">Analysis Metric</label>
                <select id="metric" name="metric">
                    <option value="referral_count">Referral Count</option>
                    <option value="revenue">Revenue</option>
                    <option value="net_amount">Net Amount</option>
                    <option value="discount">Discount</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Generate Chart</button>
            </div>
        </form>
    </div>

    <div class="page-card">
        <div class="chart-container" id="deep-analysis-chart-container" style="height: 500px;">
            <canvas id="deep-analysis-chart"></canvas>
            <p id="deep-analysis-placeholder" class="placeholder-text">Please select a doctor and generate a chart.</p>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deepAnalysisForm = document.getElementById('deep-analysis-form');
    let deepAnalysisChart = null;
    
    if (!deepAnalysisForm) {
        console.error('Form not found!');
        return;
    }
    
    console.log('Deep Analysis initialized');
    
    const loadChart = () => {
        const formData = new FormData(deepAnalysisForm);
        const params = new URLSearchParams(formData).toString();
        const placeholder = document.getElementById('deep-analysis-placeholder');
        const canvas = document.getElementById('deep-analysis-chart');
        
        console.log('Loading chart with params:', params);
        
        placeholder.style.display = 'block';
        placeholder.innerHTML = '<div class="spinner"></div><p>Generating analysis...</p>';
        canvas.style.display = 'none';
        
        fetch(`ajax_deep_analysis.php?${params}&_=${Date.now()}`, { cache: 'no-store' })
            .then(res => res.json())
            .then(data => {
                console.log('Data received:', data);
                
                if (!data || data.length === 0) {
                    placeholder.innerHTML = '<p class="placeholder-text">No data found for selected filters.</p>';
                    return;
                }
                
                placeholder.style.display = 'none';
                canvas.style.display = 'block';
                
                if (deepAnalysisChart) {
                    deepAnalysisChart.destroy();
                }
                
                const metric = formData.get('metric');
                const ctx = canvas.getContext('2d');
                const months = data.map(item => item.month);
                const values = data.map(item => parseFloat(item.value));
                
                deepAnalysisChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: months.map(m => {
                            const d = new Date(m + '-02');
                            return d.toLocaleString('default', { month: 'short', year: '2-digit' });
                        }),
                        datasets: [{
                            label: metric.replace('_', ' ').toUpperCase(),
                            data: values,
                            backgroundColor: 'rgba(78, 115, 223, 0.8)',
                            borderColor: 'rgba(78, 115, 223, 1)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Deep Analysis Chart',
                                font: { size: 18 }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
                
                console.log('Chart created successfully');
            })
            .catch(err => {
                console.error('Error:', err);
                placeholder.style.display = 'block';
                placeholder.innerHTML = '<p class="placeholder-text error">Error loading data.</p>';
            });
    };
    
    // Form submit handler
    deepAnalysisForm.addEventListener('submit', (e) => {
        e.preventDefault();
        console.log('Form submitted');
        loadChart();
    });
    
    // Auto-refresh on filter change
    const filters = ['doctor_id', 'months', 'year', 'metric'];
    filters.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', () => {
                console.log('Filter changed:', id);
                const doctorSelect = document.getElementById('doctor_id');
                if (doctorSelect && doctorSelect.value) {
                    console.log('Auto-refreshing...');
                    loadChart();
                }
            });
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
