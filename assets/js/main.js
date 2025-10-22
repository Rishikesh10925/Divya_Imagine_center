document.addEventListener('DOMContentLoaded', function() {
    
    // --- Initialize Select2 for searchable doctor dropdown ---
    if ($('#referral_doctor_id').length) {
        $('#referral_doctor_id').select2({
            placeholder: "Select or search for a doctor",
            allowClear: true,
            dropdownParent: $('#referral_doctor_id').parent()
        });
    }

    // --- Referral Dropdown Logic (for generate_bill.php) ---
    const referralTypeSelect = document.getElementById('referral_type');
    if (referralTypeSelect) {
        const doctorSelectGroup = document.getElementById('doctor-select-group');
        const otherDoctorNameGroup = document.getElementById('other-doctor-name-group');
        const otherSourceGroup = document.getElementById('other-source-group');
        const referralDoctorSelect = document.getElementById('referral_doctor_id');

        function toggleReferralFields() {
            const selectedValue = referralTypeSelect.value;
            if(doctorSelectGroup) doctorSelectGroup.style.display = 'none';
            if(otherDoctorNameGroup) otherDoctorNameGroup.style.display = 'none';
            if(otherSourceGroup) otherSourceGroup.style.display = 'none';

            if (selectedValue === 'Doctor') {
                if (doctorSelectGroup) doctorSelectGroup.style.display = 'block';
                if (referralDoctorSelect && referralDoctorSelect.value === 'other') {
                    if (otherDoctorNameGroup) otherDoctorNameGroup.style.display = 'block';
                }
            } else if (selectedValue === 'Other') {
                if (otherSourceGroup) otherSourceGroup.style.display = 'block';
            }
        }

        referralTypeSelect.addEventListener('change', toggleReferralFields);
        if (referralDoctorSelect) {
            referralDoctorSelect.addEventListener('change', function() {
                if (this.value === 'other') {
                    if (otherDoctorNameGroup) otherDoctorNameGroup.style.display = 'block';
                } else {
                    if (otherDoctorNameGroup) otherDoctorNameGroup.style.display = 'none';
                }
            });
        }
        toggleReferralFields();
    }

    // --- Test Selection & Bill Calculation ---
    const mainTestSelect = document.getElementById('main-test-select');
    const subTestSelect = document.getElementById('sub-test-select');
    
    if (mainTestSelect && subTestSelect && typeof testsData !== 'undefined') {
        mainTestSelect.addEventListener('change', function() {
            const selectedCategory = this.value;
            subTestSelect.innerHTML = '<option value="">-- Select Specific Test --</option>';
            if (selectedCategory && testsData[selectedCategory]) {
                subTestSelect.disabled = false;
                testsData[selectedCategory].forEach(test => {
                    const option = document.createElement('option');
                    option.value = test.id;
                    option.textContent = `${test.sub_test_name} (₹ ${test.price})`;
                    option.dataset.price = test.price;
                    option.dataset.name = `${test.main_test_name} - ${test.sub_test_name}`;
                    subTestSelect.appendChild(option);
                });
            } else {
                subTestSelect.disabled = true;
                subTestSelect.innerHTML = '<option value="">-- Select Category First --</option>';
            }
        });

        subTestSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption.value) return;
            // Use the globally accessible function to add the test
            if (typeof window.addTestToList === 'function') {
                window.addTestToList(selectedOption.value, selectedOption.dataset.name, selectedOption.dataset.price);
            }
            this.selectedIndex = 0;
            mainTestSelect.selectedIndex = 0;
            this.disabled = true;
        });
    }

    // --- Bill Calculation and List Management Logic ---
    const billForm = document.getElementById('bill-form');
    if (billForm) {
        const selectedTestsList = document.getElementById('selected-tests-list');
        const grossAmountInput = document.getElementById('gross_amount');
        const discountInput = document.getElementById('discount');
        const netAmountInput = document.getElementById('net_amount');
        const selectedTestsJsonInput = document.getElementById('selected_tests_json');
        
        // Payment Status Logic Elements
        const paymentStatusSelect = document.getElementById('payment_status');
        const halfPaidDetails = document.getElementById('half-paid-details');
        const amountPaidInput = document.getElementById('amount_paid');
        const balanceAmountInput = document.getElementById('balance_amount');

        let selectedTests = {};

        // Make addTestToList globally available so it can be called from edit_bill.php as well
        window.addTestToList = function(testId, testName, testPrice) {
            if (testId && !selectedTests[testId]) {
                selectedTests[testId] = { name: testName, price: parseFloat(testPrice) };
                const listItem = document.createElement('li');
                listItem.setAttribute('data-id', testId);
                listItem.textContent = testName;
                const removeBtn = document.createElement('button');
                removeBtn.textContent = 'Remove';
                removeBtn.className = 'btn-remove';
                removeBtn.onclick = function() {
                    delete selectedTests[testId];
                    listItem.remove();
                    updateBill();
                };
                listItem.appendChild(removeBtn);
                if (selectedTestsList) {
                    selectedTestsList.appendChild(listItem);
                }
                updateBill();
            }
        }

        function updateBill() {
            let grossAmount = 0;
            Object.values(selectedTests).forEach(test => {
                grossAmount += test.price;
            });

            let discount = parseFloat(discountInput.value) || 0;
            if (discount < 0) discount = 0;
            
            let netAmount = grossAmount - discount;
            if (netAmount < 0) {
                netAmount = 0;
                discountInput.value = grossAmount.toFixed(2);
            }
            grossAmountInput.value = grossAmount.toFixed(2);
            netAmountInput.value = netAmount.toFixed(2);

            // Update half-paid calculation whenever the bill total changes
            updateHalfPaid(); 
            
            const testIds = Object.keys(selectedTests);
            selectedTestsJsonInput.value = JSON.stringify(testIds);
            const submitBtn = billForm.querySelector('.btn-submit');
            if (submitBtn) {
                submitBtn.disabled = testIds.length === 0;
            }
        }
        
        function updateHalfPaid() {
            // Ensure all required elements exist before proceeding
            if (!paymentStatusSelect || !halfPaidDetails || !netAmountInput || !amountPaidInput || !balanceAmountInput) return;
            
            if (paymentStatusSelect.value === 'Half Paid') {
                halfPaidDetails.style.display = 'flex'; // Use 'flex' to show the row
                let netAmount = parseFloat(netAmountInput.value) || 0;
                let amountPaid = parseFloat(amountPaidInput.value) || 0;
                
                // Prevent paying more than the net amount
                if (amountPaid > netAmount) {
                    amountPaid = netAmount;
                    amountPaidInput.value = amountPaid.toFixed(2);
                }

                let balance = netAmount - amountPaid;
                balanceAmountInput.value = balance.toFixed(2);
                amountPaidInput.max = netAmount.toFixed(2); // Set max attribute for validation
            } else {
                halfPaidDetails.style.display = 'none'; // Hide the section
                amountPaidInput.value = ''; // Clear the values when not visible
                balanceAmountInput.value = '';
            }
        }

        // Attach event listeners
        if(discountInput) {
            discountInput.addEventListener('input', updateBill);
        }
        
        if(paymentStatusSelect) {
            paymentStatusSelect.addEventListener('change', updateHalfPaid);
        }
        if(amountPaidInput) {
            amountPaidInput.addEventListener('input', updateHalfPaid);
        }

        // Initial call to set the correct state when the page loads
        updateBill();
    }


    // --- Bill History Live Search Logic ---
    const billSearchInput = document.getElementById('bill-search');
    const billHistoryTableBody = document.getElementById('bill-history-table-body');
    const paginationContainer = document.querySelector('.pagination');

    if (billSearchInput && billHistoryTableBody) {
        billSearchInput.addEventListener('keyup', function() {
            const searchTerm = this.value;
            if (paginationContainer) {
                paginationContainer.style.display = searchTerm ? 'none' : 'block';
            }
            // Use the correct path for the AJAX handler
            fetch(`ajax_handler.php?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.text())
                .then(data => {
                    billHistoryTableBody.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error:', error);
                    billHistoryTableBody.innerHTML = '<tr><td colspan="6">Error loading data.</td></tr>';
                });
        });
    }

    // --- Manager Analytics: Dynamic Filters (No changes needed here) ---
    const analyticsReferralType = document.getElementById('analytics_referral_type');
    const analyticsDoctorFilter = document.getElementById('analytics_doctor_filter');
    const analyticsMainTest = document.getElementById('analytics_main_test');
    const analyticsSubTest = document.getElementById('analytics_sub_test');

    function toggleAnalyticsDoctorFilter() {
        if (analyticsReferralType && analyticsDoctorFilter) {
            analyticsDoctorFilter.style.display = (analyticsReferralType.value === 'Doctor') ? 'block' : 'none';
        }
    }

    function populateSubTests() {
        if (analyticsMainTest && analyticsSubTest && typeof allTestsData !== 'undefined') {
            const selectedCategory = analyticsMainTest.value;
            analyticsSubTest.innerHTML = '<option value="all">All Tests</option>';
            if (selectedCategory && allTestsData[selectedCategory]) {
                allTestsData[selectedCategory].forEach(test => {
                    const option = document.createElement('option');
                    option.value = test.id;
                    option.textContent = test.name;
                    if (test.id == currentSubTestId) {
                        option.selected = true;
                    }
                    analyticsSubTest.appendChild(option);
                });
            }
        }
    }

    if (analyticsReferralType) {
        analyticsReferralType.addEventListener('change', toggleAnalyticsDoctorFilter);
        toggleAnalyticsDoctorFilter();
    }

    if (analyticsMainTest) {
        analyticsMainTest.addEventListener('change', populateSubTests);
        populateSubTests();
    }
});

