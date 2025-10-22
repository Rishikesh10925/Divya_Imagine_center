<?php
$page_title = "Compare Doctors";
$required_role = "superadmin";
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';

// Fetch data for filter dropdowns
$doctors_result = $conn->query("SELECT id, doctor_name FROM referral_doctors WHERE is_active = 1 ORDER BY doctor_name");
$doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);

$tests_result = $conn->query("SELECT DISTINCT main_test_name FROM tests WHERE main_test_name IS NOT NULL AND main_test_name != '' ORDER BY main_test_name");
$main_tests = $tests_result->fetch_all(MYSQLI_ASSOC);

require_once '../includes/header.php';
?>
<style>
    .doctor-selection-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 2rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        margin-bottom: 2rem;
    }
    .doctor-select-row {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 1.5rem;
        align-items: end;
    }
    .compare-results-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-top: 2rem;
    }
    .doctor-result-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border-top: 4px solid #4e73df;
    }
    .doctor-result-card.doctor-2 {
        border-top-color: #1cc88a;
    }
    .doctor-card-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e3e6f0;
    }
    .doctor-icon {
        font-size: 2rem;
    }
    .doctor-card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #5a5c69;
        margin: 0;
    }
    .placeholder-text {
        text-align: center;
        color: #858796;
        padding: 3rem 2rem;
        border: 2px dashed #e3e6f0;
        border-radius: 0.35rem;
        background-color: #f8f9fc;
        font-size: 0.95rem;
    }
    .summary-table {
        width: 100%;
        margin-bottom: 1.5rem;
        border-collapse: collapse;
        background: #f8f9fc;
        border-radius: 0.35rem;
        overflow: hidden;
    }
    .summary-table th {
        background: #4e73df;
        color: white;
        padding: 0.75rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .summary-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #e3e6f0;
        font-size: 0.9rem;
    }
    .summary-table tr:last-child td {
        border-bottom: none;
    }
    .stat-value {
        font-weight: 700;
        color: #2e59d9;
    }
    @media (max-width: 1024px) {
        .doctor-select-row {
            grid-template-columns: 1fr;
        }
        .compare-results-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="main-content">
    <div class="content-header">
        <h1>Doctor Comparison Analysis</h1>
        <p>Select two doctors and apply filters to compare their performance side-by-side.</p>
    </div>

    <!-- Common Filters Section -->
    <div class="page-card" style="margin-bottom: 1.5rem;">
        <h3>Common Filters</h3>
        <form id="common-filters-form" class="filter-form-flex">
            <div class="form-group">
                <label for="common_start">Start Date</label>
                <input type="date" id="common_start" name="start_date" class="form-control" value="<?php echo date('Y-m-01'); ?>">
            </div>
            <div class="form-group">
                <label for="common_end">End Date</label>
                <input type="date" id="common_end" name="end_date" class="form-control" value="<?php echo date('Y-m-t'); ?>">
            </div>
            <div class="form-group">
                <label for="common_test">Main Test Category</label>
                <select id="common_test" name="main_test" class="form-control">
                    <option value="">-- All Categories --</option>
                    <?php foreach ($main_tests as $t): ?>
                        <option value="<?php echo htmlspecialchars($t['main_test_name']); ?>"><?php echo htmlspecialchars($t['main_test_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="common_subtest">Sub Test</label>
                <select id="common_subtest" name="sub_test" class="form-control">
                    <option value="">-- All Sub Tests --</option>
                </select>
            </div>
        </form>
    </div>

    <div class="compare-container">
        <!-- Left Panel for Doctor 1 -->
        <div class="compare-panel page-card" id="panel-1">
            <div class="panel-header">
                <h2>Comparison Panel 1</h2>
            </div>
            <form class="compare-form" data-panel-id="1">
                <div class="form-group">
                    <label for="doctor_1">Select Doctor</label>
                    <select id="doctor_1" name="doctor_id" class="form-control" required>
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Analyze</button>
            </form>
            <div class="comparison-results-container" id="compare-results-1">
                <p class="placeholder-text">Select a doctor and click "Analyze".</p>
            </div>
        </div>

        <!-- Right Panel for Doctor 2 -->
        <div class="compare-panel page-card" id="panel-2">
            <div class="panel-header">
                <h2>Comparison Panel 2</h2>
            </div>
            <form class="compare-form" data-panel-id="2">
                <div class="form-group">
                    <label for="doctor_2">Select Doctor</label>
                    <select id="doctor_2" name="doctor_id" class="form-control" required>
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">Dr. <?php echo htmlspecialchars($doctor['doctor_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">Analyze</button>
            </form>
            <div class="comparison-results-container" id="compare-results-2">
                <p class="placeholder-text">Select a doctor and click "Analyze".</p>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const compareForms = document.querySelectorAll('.compare-form');
    const commonFiltersForm = document.getElementById('common-filters-form');
    
    if (compareForms.length > 0) {
        // Function to load comparison data for a panel
        const loadComparison = (panelId, doctorId) => {
            const resultsContainer = document.getElementById(`compare-results-${panelId}`);
            if (!resultsContainer) return;
            
            resultsContainer.innerHTML = '<div class="spinner" style="margin: 2rem auto;"></div><p style="text-align:center; color: #858796;">Loading analysis...</p>';
            
            const params = new URLSearchParams({
                doctor_id: doctorId,
                start_date: document.getElementById('common_start')?.value || '',
                end_date: document.getElementById('common_end')?.value || '',
                main_test: document.getElementById('common_test')?.value || '',
                sub_test: document.getElementById('common_subtest')?.value || ''
            });
            
            fetch(`ajax_compare_handler.php?${params.toString()}`)
                .then(response => response.text())
                .then(html => {
                    resultsContainer.innerHTML = html;
                })
                .catch(err => {
                    resultsContainer.innerHTML = '<p class="placeholder-text" style="background: #fff3cd; border-color: #ffc107; color: #856404;">⚠️ Could not load data. Please try again.</p>';
                    console.error('Comparison fetch error:', err);
                });
        };
        
        // Handle form submissions
        compareForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const panelId = form.getAttribute('data-panel-id');
                const doctorSelect = form.querySelector('select[name="doctor_id"]');
                
                if (!doctorSelect.value) {
                    alert('Please select a doctor.');
                    return;
                }
                
                loadComparison(panelId, doctorSelect.value);
            });
        });
        
        // Auto-refresh when common filters change (if doctors are already selected)
        ['common_start', 'common_end', 'common_test', 'common_subtest'].forEach(filterId => {
            const element = document.getElementById(filterId);
            if (element) {
                element.addEventListener('change', function() {
                    // Re-run comparison for any panel that has a doctor selected
                    compareForms.forEach(form => {
                        const panelId = form.getAttribute('data-panel-id');
                        const doctorSelect = form.querySelector('select[name="doctor_id"]');
                        if (doctorSelect.value) {
                            loadComparison(panelId, doctorSelect.value);
                        }
                    });
                });
            }
        });
        
        // Handle main test change to populate sub-tests
        const commonTestSelect = document.getElementById('common_test');
        const commonSubtestSelect = document.getElementById('common_subtest');
        
        if (commonTestSelect && commonSubtestSelect) {
            commonTestSelect.addEventListener('change', function() {
                const mainTest = this.value;
                commonSubtestSelect.innerHTML = '<option value="">-- All Sub Tests --</option>';
                
                if (!mainTest) return;
                
                fetch(`ajax_get_subtests.php?main_test=${encodeURIComponent(mainTest)}`)
                    .then(response => response.json())
                    .then(subtests => {
                        subtests.forEach(subtest => {
                            const option = document.createElement('option');
                            option.value = subtest.sub_test_name;
                            option.textContent = subtest.sub_test_name;
                            commonSubtestSelect.appendChild(option);
                        });
                    })
                    .catch(err => console.error('Error loading subtests:', err));
            });
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
