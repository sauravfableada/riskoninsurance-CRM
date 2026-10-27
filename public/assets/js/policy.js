/**
 * Insurance Policy CRUD Handler
 * Multi-step form with conditional steps based on Policy Type
 */
(function() {
    'use strict';

    const API_BASE = '/api/policies';
    let currentStep = 1;
    let totalSteps = 4;
    let isVehiclePolicy = false;
    let dropdowns = {};
    let policyData = null;
    let policySearchDebounce = null;
    const listState = {
        search: '',
        createdScope: 'me',
        policyBucket: 'policies',
        holderId: '',
        policyTypeId: '',
        companyId: '',
        startDate: '',
        endDate: '',
    };

    /**
     * Initialize module
     */
    function init() {
        const mode = window.policyMode;
        
        if (mode === 'create' || mode === 'edit') {
            initMultiStepForm();
            loadDropdowns().then(() => {
                if (mode === 'edit' && window.editPolicyId) {
                    loadPolicyForEdit(window.editPolicyId);
                }
            });
        } else if (mode === 'list' || !mode) {
            initPolicyList();
        } else if (mode === 'view' && window.viewPolicyId) {
            loadDropdowns().finally(() => {
                loadPolicyForView(window.viewPolicyId);
            });
        }
    }

    /**
     * Initialize multi-step form
     */
    function initMultiStepForm() {
        // Next button
        document.getElementById('btnNext')?.addEventListener('click', nextStep);
        
        // Previous button
        document.getElementById('btnPrevious')?.addEventListener('click', previousStep);
        
        // Submit button
        document.getElementById('btnSubmit')?.addEventListener('click', submitForm);
        
        // Policy Type change - determine if vehicle policy
        document.getElementById('policyTypeId')?.addEventListener('change', function() {
            const selectedType = dropdowns.policy_types?.find(t => t.id == this.value);
            checkVehiclePolicy(selectedType);
        });

        // Company change - load branches
        document.getElementById('companyId')?.addEventListener('change', function() {
            loadBranches(this.value);
        });

        // Policy Type change - load sub types
        document.getElementById('policyTypeId')?.addEventListener('change', function() {
            loadSubTypes(this.value);
        });

        // Family Group change - filter holders
        document.getElementById('familyGroupId')?.addEventListener('change', function() {
            loadHoldersByFamilyGroup(this.value);
        });

        // PDF Upload
        document.getElementById('btnUploadPdf')?.addEventListener('click', function() {
            document.getElementById('policyPdfInput')?.click();
        });

        document.getElementById('policyPdfInput')?.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                window.DashboardAPI?.showToast('PDF selected: ' + this.files[0].name, 'info');
            }
        });
    }

    /**
     * Check if selected policy type is vehicle
     */
    function checkVehiclePolicy(policyType) {
        const step2Label = document.getElementById('step2Label');
        const step3Label = document.getElementById('step3Label');
        const vehicleIdvField = document.getElementById('vehicleIdvField');
        const sumAssuredField = document.getElementById('sumAssuredField');
        const cngLpgField = document.getElementById('cngLpgField');

        isVehiclePolicy = policyType && policyType.name && 
            policyType.name.toLowerCase().includes('vehicle');

        if (isVehiclePolicy) {
            // Show vehicle step
            step2Label.style.display = 'block';
            step3Label.querySelector('.step-label').textContent = 'Policy Details';
            totalSteps = 4;
            
            // Show vehicle fields
            vehicleIdvField?.classList.remove('d-none');
            sumAssuredField?.classList.add('d-none');
            cngLpgField?.classList.remove('d-none');
        } else {
            // Hide vehicle step - skip to policy details
            step2Label.style.display = 'none';
            step3Label.querySelector('.step-label').textContent = 'Policy Details';
            totalSteps = 3;
            
            // Show sum assured instead of vehicle IDV
            vehicleIdvField?.classList.add('d-none');
            sumAssuredField?.classList.remove('d-none');
            cngLpgField?.classList.add('d-none');

            // If currently on step 2, skip to step 3
            if (currentStep === 2) {
                currentStep = 3;
                updateStepDisplay();
            }
        }
        
        updateProgressBar();
    }

    /**
     * Load all dropdowns
     */
    async function loadDropdowns() {
        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/dropdowns`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                dropdowns = result.data;
                populateDropdowns();
                populatePolicyListFilters();
            }
        } catch (error) {
            console.error('Load dropdowns error:', error);
        }
    }

    /**
     * Populate all dropdowns
     */
    function populateDropdowns() {
        populateSelect('staffName', dropdowns.staff_members || [], 'Select Staff Name');
        populateSelect('familyGroupId', dropdowns.family_groups, 'Select Family Group');
        populateSelect('holderId', dropdowns.customers, 'Select Policy Holder');
        populateSelect('policyTypeId', dropdowns.policy_types, 'Select Policy Type');
        populateSelect('segmentId', dropdowns.segments, 'Select Segment');
        populateSelect('companyId', dropdowns.companies, 'Select Company');
        populateSelect('policySubTypeId', dropdowns.policy_sub_types || [], 'Select Sub Type');
        populateSelect('branchId', dropdowns.branches || [], 'Select Branch');
    }

    function populatePolicyListFilters() {
        populateSelect('filterHolderId', dropdowns.customers || [], 'All Holders');
        populateSelect('filterPolicyTypeId', dropdowns.policy_types || [], 'All Policy Types');
        populateSelect('filterCompanyId', dropdowns.companies || [], 'All Companies');
    }

    function initPolicyList() {
        const searchInput = document.getElementById('policySearch');
        const holderSelect = document.getElementById('filterHolderId');
        const policyTypeSelect = document.getElementById('filterPolicyTypeId');
        const companySelect = document.getElementById('filterCompanyId');
        const startDateInput = document.getElementById('filterStartDate');
        const endDateInput = document.getElementById('filterEndDate');
        const createdScopeTabs = document.querySelectorAll('[data-created-scope]');
        const policyBucketTabs = document.querySelectorAll('[data-policy-bucket]');

        createdScopeTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                createdScopeTabs.forEach(item => item.classList.remove('active'));
                this.classList.add('active');
                listState.createdScope = this.dataset.createdScope || 'me';
                loadPolicies(listState.search, 1);
            });
        });

        policyBucketTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                policyBucketTabs.forEach(item => {
                    item.classList.remove('btn-dark-blue');
                    item.classList.add('crm-action-btn');
                });
                this.classList.remove('crm-action-btn');
                this.classList.add('btn-dark-blue');
                listState.policyBucket = this.dataset.policyBucket || 'policies';
                loadPolicies(listState.search, 1);
            });
        });

        searchInput?.addEventListener('input', function() {
            clearTimeout(policySearchDebounce);
            const value = this.value || '';
            policySearchDebounce = setTimeout(() => {
                loadPolicies(value, 1);
            }, 350);
        });

        holderSelect?.addEventListener('change', function() {
            listState.holderId = this.value || '';
            loadPolicies(listState.search, 1);
        });

        policyTypeSelect?.addEventListener('change', function() {
            listState.policyTypeId = this.value || '';
            loadPolicies(listState.search, 1);
        });

        companySelect?.addEventListener('change', function() {
            listState.companyId = this.value || '';
            loadPolicies(listState.search, 1);
        });

        startDateInput?.addEventListener('change', function() {
            listState.startDate = this.value || '';
            loadPolicies(listState.search, 1);
        });

        endDateInput?.addEventListener('change', function() {
            listState.endDate = this.value || '';
            loadPolicies(listState.search, 1);
        });

        loadDropdowns().finally(() => {
            loadPolicies('', 1);
        });
    }

    /**
     * Populate a select element
     */
    function populateSelect(elementId, data, defaultText) {
        const select = document.getElementById(elementId);
        if (!select || !data) return;

        let html = `<option value="">-- ${defaultText} --</option>`;
        data.forEach(item => {
            html += `<option value="${item.id}">${escapeHtml(item.name)}</option>`;
        });
        select.innerHTML = html;
    }

    /**
     * Load branches by company
     */
    async function loadBranches(companyId) {
        if (!companyId) {
            populateSelect('branchId', dropdowns.branches || [], 'Select Branch');
            return;
        }

        // Use already-loaded dropdown data first for fast UI response
        if (Array.isArray(dropdowns.branches)) {
            const filteredBranches = dropdowns.branches.filter(item => String(item.company_id) === String(companyId));
            populateSelect('branchId', filteredBranches, 'Select Branch');
            return;
        }

        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/branches/${companyId}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                populateSelect('branchId', result.data, 'Select Branch');
            }
        } catch (error) {
            console.error('Load branches error:', error);
        }
    }

    /**
     * Load sub types by policy type
     */
    async function loadSubTypes(policyTypeId) {
        if (!policyTypeId) {
            populateSelect('policySubTypeId', dropdowns.policy_sub_types || [], 'Select Sub Type');
            return;
        }

        // Use already-loaded dropdown data first for fast UI response
        if (Array.isArray(dropdowns.policy_sub_types)) {
            const filteredSubTypes = dropdowns.policy_sub_types.filter(item => String(item.policy_type_id) === String(policyTypeId));
            populateSelect('policySubTypeId', filteredSubTypes, 'Select Sub Type');
            return;
        }

        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/sub-types/${policyTypeId}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                populateSelect('policySubTypeId', result.data, 'Select Sub Type');
            }
        } catch (error) {
            console.error('Load sub types error:', error);
        }
    }

    /**
     * Load holders by family group
     * When family group is selected, only show holders that belong to that group
     */
    async function loadHoldersByFamilyGroup(familyGroupId) {
        const holderSelect = document.getElementById('holderId');

        if (!familyGroupId) {
            // If no family group selected, show all customers
            populateSelect('holderId', dropdowns.customers || [], 'Select Policy Holder');
            return;
        }

        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/customers-by-family-group/${familyGroupId}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                if (result.data && result.data.length > 0) {
                    populateSelect('holderId', result.data, 'Select Policy Holder');
                } else {
                    // If no customers found for this family group, show empty with message
                    holderSelect.innerHTML = '<option value="">-- No holders in this family group --</option>';
                }
            } else {
                // Fallback to all customers on error
                populateSelect('holderId', dropdowns.customers || [], 'Select Policy Holder');
            }
        } catch (error) {
            console.error('Load holders by family group error:', error);
            // Fallback to all customers on error
            populateSelect('holderId', dropdowns.customers || [], 'Select Policy Holder');
        }
    }

    /**
     * Next step
     */
    function nextStep() {
        if (!validateCurrentStep()) return;

        if (isVehiclePolicy) {
            // Normal flow for vehicle: 1 -> 2 -> 3 -> 4
            if (currentStep < totalSteps) {
                currentStep++;
            }
        } else {
            // Skip step 2 for non-vehicle: 1 -> 3 -> 4
            if (currentStep === 1) {
                currentStep = 3;
            } else if (currentStep < totalSteps + 1) {
                currentStep++;
            }
        }

        updateStepDisplay();
    }

    /**
     * Previous step
     */
    function previousStep() {
        if (isVehiclePolicy) {
            // Normal flow for vehicle
            if (currentStep > 1) {
                currentStep--;
            }
        } else {
            // Skip step 2 for non-vehicle
            if (currentStep === 3) {
                currentStep = 1;
            } else if (currentStep > 1) {
                currentStep--;
            }
        }

        updateStepDisplay();
    }

    /**
     * Update step display
     */
    function updateStepDisplay() {
        // Hide all steps
        document.querySelectorAll('.step-content').forEach(el => el.classList.add('d-none'));
        
        // Show current step
        const currentStepEl = document.getElementById(`step${currentStep}`);
        if (currentStepEl) {
            currentStepEl.classList.remove('d-none');
        }

        // Update step indicators
        document.querySelectorAll('.step-circle').forEach((el, index) => {
            const stepNum = index + 1;
            el.classList.remove('bg-primary', 'bg-success', 'bg-secondary');
            
            if (stepNum === currentStep) {
                el.classList.add('bg-primary');
            } else if (stepNum < currentStep) {
                el.classList.add('bg-success');
            } else {
                el.classList.add('bg-secondary');
            }
        });

        // Update buttons
        const btnPrevious = document.getElementById('btnPrevious');
        const btnNext = document.getElementById('btnNext');
        const btnSubmit = document.getElementById('btnSubmit');

        btnPrevious.style.display = currentStep === 1 ? 'none' : 'inline-block';

        if ((isVehiclePolicy && currentStep === 4) || (!isVehiclePolicy && currentStep === 4)) {
            btnNext.classList.add('d-none');
            btnSubmit.classList.remove('d-none');
        } else {
            btnNext.classList.remove('d-none');
            btnSubmit.classList.add('d-none');
        }

        updateProgressBar();
    }

    /**
     * Update progress bar
     */
    function updateProgressBar() {
        const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
        document.getElementById('progressBar').style.width = `${progress}%`;
    }

    /**
     * Validate current step
     */
    function validateCurrentStep() {
        const currentStepEl = document.getElementById(`step${currentStep}`);
        if (!currentStepEl) return true;

        const requiredFields = currentStepEl.querySelectorAll('[required]');
        let valid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!valid) {
            window.DashboardAPI?.showToast('Please fill all required fields', 'error');
        }

        return valid;
    }

    /**
     * Submit form
     */
    async function submitForm() {
        if (!validateCurrentStep()) return;

        const isEdit = window.policyMode === 'edit';
        const btnSubmit = document.getElementById('btnSubmit');
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        try {
            const token = localStorage.getItem('crm_auth_token');
            const formData = new FormData(document.getElementById('policyForm'));

            // Always store staff name text in DB (not numeric user id).
            const staffSelect = document.getElementById('staffName');
            if (staffSelect) {
                const selectedOption = staffSelect.options[staffSelect.selectedIndex];
                const staffLabel = selectedOption ? selectedOption.textContent.trim() : '';
                if (staffSelect.value && staffLabel && !staffLabel.startsWith('--')) {
                    formData.set('staff_name', staffLabel);
                } else {
                    formData.set('staff_name', '');
                }
            }

            // Convert dates to proper format
            const dateFields = ['start_date', 'end_date', 'payment_date'];
            dateFields.forEach(field => {
                const value = formData.get(field);
                if (value) {
                    formData.set(field, value);
                }
            });

            const url = isEdit ? `${API_BASE}/${window.editPolicyId}` : API_BASE;
            const method = isEdit ? 'POST' : 'POST'; // Use POST with _method for PUT

            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.success) {
                window.DashboardAPI?.showToast(result.message || 'Policy saved successfully', 'success');
                setTimeout(() => {
                    window.location.href = '/masters/policies';
                }, 1500);
            } else {
                let errorMsg = result.message || 'Failed to save policy';
                if (result.errors) {
                    const errors = Object.values(result.errors).flat();
                    errorMsg += ': ' + errors.join(', ');
                }
                window.DashboardAPI?.showToast(errorMsg, 'error');
            }
        } catch (error) {
            console.error('Submit error:', error);
            window.DashboardAPI?.showToast('An error occurred while saving', 'error');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = isEdit ? '<i class="bi bi-check-lg me-1"></i>Update' : '<i class="bi bi-check-lg me-1"></i>Submit';
        }
    }

    /**
     * Load policy for edit
     */
    async function loadPolicyForEdit(id) {
        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                policyData = result.data;
                populateForm(policyData);
            }
        } catch (error) {
            console.error('Load policy error:', error);
        }
    }

    /**
     * Populate form with policy data
     */
    function populateForm(data) {
        const form = document.getElementById('policyForm');
        if (!form) return;

        // Set all form fields
        Object.keys(data).forEach(key => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field && data[key] !== null && data[key] !== undefined) {
                if (field.type === 'date' && data[key]) {
                    field.value = data[key].split('T')[0];
                } else if (field.type === 'file') {
                    // Skip file inputs
                } else if (field.tagName === 'SELECT') {
                    if (field.id === 'staffName') {
                        // Stored value is staff name; select the matching option by label.
                        const matchingOption = Array.from(field.options).find(
                            option => option.textContent.trim().toLowerCase() === String(data[key]).trim().toLowerCase()
                        );
                        if (matchingOption) {
                            field.value = matchingOption.value;
                        } else {
                            field.add(new Option(data[key], data[key]));
                            field.value = data[key];
                        }
                    } else {
                        const hasOption = Array.from(field.options).some(option => option.value == data[key]);
                        if (!hasOption) {
                            field.add(new Option(data[key], data[key]));
                        }
                        field.value = data[key];
                    }
                } else {
                    field.value = data[key];
                }
            }
        });

        // Check policy type and update steps
        if (data.policy_type) {
            checkVehiclePolicy(data.policy_type);
        }

        // Load dependent dropdowns
        if (data.family_group_id) {
            loadHoldersByFamilyGroup(data.family_group_id).then(() => {
                // After loading filtered holders, set the selected holder
                const holderSelect = document.getElementById('holderId');
                if (holderSelect && data.holder_id) {
                    // Check if the holder exists in the filtered list
                    const hasOption = Array.from(holderSelect.options).some(option => option.value == data.holder_id);
                    if (!hasOption) {
                        // If not found, add it temporarily so we can select it
                        holderSelect.add(new Option(data.holder?.name || 'Selected Holder', data.holder_id));
                    }
                    holderSelect.value = data.holder_id;
                }
            });
        }

        if (data.company_id) {
            loadBranches(data.company_id).then(() => {
                const branchSelect = document.getElementById('branchId');
                if (branchSelect && data.branch_id) {
                    branchSelect.value = data.branch_id;
                }
            });
        }

        if (data.policy_type_id) {
            loadSubTypes(data.policy_type_id).then(() => {
                const subTypeSelect = document.getElementById('policySubTypeId');
                if (subTypeSelect && data.policy_sub_type_id) {
                    subTypeSelect.value = data.policy_sub_type_id;
                }
            });
        }
    }

    /**
     * Load policy for view
     */
    async function loadPolicyForView(id) {
        const container = document.getElementById('policyViewContainer');
        if (!container) return;

        try {
            const token = localStorage.getItem('crm_auth_token');
            const response = await fetch(`${API_BASE}/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                renderPolicyView(result.data, container);
            } else {
                container.innerHTML = `<div class="alert alert-danger">${result.message || 'Failed to load policy'}</div>`;
            }
        } catch (error) {
            console.error('Load policy error:', error);
            container.innerHTML = '<div class="alert alert-danger">Error loading policy details</div>';
        }
    }

    /**
     * Render policy view
     */
    function renderPolicyView(data, container) {
        const isVehicle = data.policy_type?.name?.toLowerCase().includes('vehicle');

        const textOrDash = (value) => {
            if (value === null || value === undefined || value === '') {
                return '--';
            }
            return String(value);
        };

        const formatDate = (value) => {
            if (!value) return '--';
            const input = String(value).trim();
            const ymdPattern = /^\d{4}-\d{2}-\d{2}$/;
            let date = null;

            if (ymdPattern.test(input)) {
                const [year, month, day] = input.split('-').map(Number);
                date = new Date(year, month - 1, day);
            } else {
                date = new Date(input);
            }

            if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
                return input;
            }

            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        };

        const formatAmount = (value) => {
            if (value === null || value === undefined || value === '') {
                return '--';
            }

            const number = Number(value);
            if (Number.isNaN(number)) {
                return String(value);
            }

            return number.toFixed(2);
        };

        const detailRows = [
            {
                leftLabel: 'Policy Holder',
                leftValue: textOrDash(data.holder?.name),
                rightLabel: 'Policy Type',
                rightValue: textOrDash(data.policy_type?.name),
            },
            {
                leftLabel: 'Policy Sub Type',
                leftValue: textOrDash(data.policy_sub_type?.name),
                rightLabel: 'Segment',
                rightValue: textOrDash(data.segment?.name),
            },
            {
                leftLabel: 'Insurance Company',
                leftValue: textOrDash(data.company?.name),
                rightLabel: 'Branch',
                rightValue: textOrDash(data.branch?.name),
            },
            {
                leftLabel: 'Start Date',
                leftValue: formatDate(data.start_date),
                rightLabel: 'End Date',
                rightValue: formatDate(data.end_date),
            },
            {
                leftLabel: 'Policy Mode',
                leftValue: textOrDash(data.payment_mode || data.payment_type),
                rightLabel: 'Premium Amount',
                rightValue: formatAmount(data.premium_amount),
            },
            {
                leftLabel: isVehicle ? 'Vehicle IDV' : 'Sum Assured',
                leftValue: formatAmount(isVehicle ? data.vehicle_idv : data.sum_assured),
                rightLabel: 'Tax Amount',
                rightValue: formatAmount(data.tax_amount),
            },
            {
                leftLabel: 'Payment Date',
                leftValue: formatDate(data.payment_date),
                rightLabel: 'Payment Mode',
                rightValue: textOrDash(data.payment_type || data.payment_mode),
            },
            {
                leftLabel: 'Nominee Name',
                leftValue: textOrDash(data.nominee_name),
                rightLabel: 'Nominee Contact Number',
                rightValue: textOrDash(data.nominee_contact),
            },
            {
                leftLabel: 'Payment Amount',
                leftValue: formatAmount(data.payment_amount),
                rightLabel: 'Remarks',
                rightValue: textOrDash(data.remarks),
            },
            {
                leftLabel: 'Staff Name',
                leftValue: textOrDash(resolveStaffName(data.staff_name)),
                rightLabel: 'Family Group',
                rightValue: textOrDash(data.family_group?.group_name),
            }
        ];

        if (isVehicle) {
            detailRows.splice(3, 0,
                {
                    leftLabel: 'Registration Number',
                    leftValue: textOrDash(data.vehicle_registration_number),
                    rightLabel: 'Vehicle Model',
                    rightValue: textOrDash(data.vehicle_model),
                },
                {
                    leftLabel: 'Engine Number',
                    leftValue: textOrDash(data.engine_number),
                    rightLabel: 'Chassis Number',
                    rightValue: textOrDash(data.chassis_number),
                },
                {
                    leftLabel: 'Fuel Type',
                    leftValue: textOrDash(data.fuel_type),
                    rightLabel: 'Engine Capacity',
                    rightValue: textOrDash(data.engine_capacity),
                }
            );
        }

        const rowsHtml = detailRows.map((row) => {
            return `
                <div class="policy-view-row row g-3">
                    <div class="col-lg-6">
                        <div class="policy-view-item">
                            <span class="policy-view-label">${escapeHtml(row.leftLabel)}:</span>
                            <span class="policy-view-value">${escapeHtml(row.leftValue)}</span>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="policy-view-item">
                            <span class="policy-view-label">${escapeHtml(row.rightLabel)}:</span>
                            <span class="policy-view-value">${escapeHtml(row.rightValue)}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        const pdfHtml = data.policy_pdf ? `
            <div class="policy-view-row row g-3">
                <div class="col-lg-6">
                    <div class="policy-view-item">
                        <span class="policy-view-label">Policy PDF:</span>
                        <span class="policy-view-value">
                            <a href="/storage/${data.policy_pdf}" target="_blank" class="btn btn-sm crm-action-btn policy-view-pdf-btn">
                                <i class="bi bi-file-pdf me-1"></i>View PDF
                            </a>
                        </span>
                    </div>
                </div>
                <div class="col-lg-6"></div>
            </div>
        ` : '';

        const policyNumberText = textOrDash(data.policy_number);
        const policyNumberDisplay = policyNumberText === '--' ? '--' : '#' + policyNumberText;

        container.innerHTML = `
            <div class="policy-view-details">
                <div class="policy-view-policy-number">
                    Policy Number - <span>${escapeHtml(policyNumberDisplay)}</span>
                </div>
                ${rowsHtml}
                ${pdfHtml}
            </div>
        `;
    }

    function resolveStaffName(value) {
        if (value === null || value === undefined || value === '' || value === 0 || value === '0') {
            return '--';
        }

        const staffList = Array.isArray(dropdowns.staff_members) ? dropdowns.staff_members : [];

        // Backward compatibility for old records storing numeric user id in staff_name.
        const byId = staffList.find(item => String(item.id) === String(value));
        if (byId && byId.name) {
            return byId.name;
        }

        // Current format: stored as staff name text.
        const byName = staffList.find(item => String(item.name).trim().toLowerCase() === String(value).trim().toLowerCase());
        if (byName && byName.name) {
            return byName.name;
        }

        return String(value);
    }

    /**
     * Load policies for list
     */
    async function loadPolicies(search = '', page = 1) {
        const tbody = document.querySelector('#policyTable tbody');
        if (!tbody) return;

        listState.search = search ?? '';

        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

        try {
            const token = localStorage.getItem('crm_auth_token');
            const params = new URLSearchParams();
            params.set('search', listState.search);
            params.set('page', String(page));
            params.set('created_scope', listState.createdScope);
            params.set('policy_bucket', listState.policyBucket);
            if (listState.holderId) params.set('holder_id', listState.holderId);
            if (listState.policyTypeId) params.set('policy_type_id', listState.policyTypeId);
            if (listState.companyId) params.set('company_id', listState.companyId);
            if (listState.startDate) params.set('start_date', listState.startDate);
            if (listState.endDate) params.set('end_date', listState.endDate);

            const response = await fetch(`${API_BASE}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + token,
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                renderPolicyTable(result.data.data || [], result.data.from || 1);
                renderPolicyPagination(result.data);
            } else {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">${result.message || 'Failed to load data'}</td></tr>`;
            }
        } catch (error) {
            console.error('Load error:', error);
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">Error loading data</td></tr>';
        }
    }

    /**
     * Render policy table
     */
    function renderPolicyTable(data, startIndex = 1) {
        const tbody = document.querySelector('#policyTable tbody');
        if (!tbody) return;

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">No policies found</td></tr>';
            return;
        }

        tbody.innerHTML = data.map((item, index) => {
            const holderName = item.holder ? item.holder.name : '--';
            const companyName = item.company ? item.company.name : '--';
            const policyTypeName = item.policy_type ? item.policy_type.name : '--';

            return `
                <tr>
                    <td class="ps-4">${startIndex + index}</td>
                    <td>${escapeHtml(item.policy_number)}</td>
                    <td>${escapeHtml(holderName)}</td>
                    <td>${escapeHtml(companyName)}</td>
                    <td>${escapeHtml(policyTypeName)}</td>
                    <td>${item.start_date || '--'}</td>
                    <td>${item.end_date || '--'}</td>
                    <td>${item.premium_amount ? '₹' + item.premium_amount : '--'}</td>
                    <td class="text-end pe-4">
                        <a href="/masters/policies/${item.id}/view" class="btn btn-sm btn-outline-dark-blue me-1" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="/masters/policies/${item.id}/edit" class="btn btn-sm crm-action-btn me-1" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-sm crm-action-btn" onclick="deletePolicy(${item.id}, '${escapeHtml(item.policy_number)}')" title="Delete">
                            <i class="bi bi-trash text-danger"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    /**
     * Render pagination
     */
    function renderPolicyPagination(data) {
        const container = document.getElementById('policyPagination');
        if (!container || !data.last_page || data.last_page <= 1) {
            if (container) container.innerHTML = '';
            return;
        }

        let html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">';

        html += `<li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePagePolicy(${data.current_page - 1}); return false;">Previous</a>
        </li>`;

        for (let i = 1; i <= data.last_page; i++) {
            if (i === 1 || i === data.last_page || (i >= data.current_page - 2 && i <= data.current_page + 2)) {
                html += `<li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePagePolicy(${i}); return false;">${i}</a>
                </li>`;
            } else if (i === data.current_page - 3 || i === data.current_page + 3) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        html += `<li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePagePolicy(${data.current_page + 1}); return false;">Next</a>
        </li>`;

        html += '</ul></nav>';
        container.innerHTML = html;
    }

    /**
     * Delete policy
     */
    async function deletePolicy(id, policyNumber) {
        const doDelete = async () => {
            try {
                const token = localStorage.getItem('crm_auth_token');
                const response = await fetch(`${API_BASE}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + token,
                    }
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    window.DashboardAPI?.showToast(result.message || 'Deleted successfully', 'success');
                    loadPolicies(listState.search, 1);
                } else {
                    window.DashboardAPI?.showToast(result.message || 'Failed to delete', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                window.DashboardAPI?.showToast('An error occurred while deleting', 'error');
            }
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Are you sure?',
                text: `Delete policy "${policyNumber}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    doDelete();
                }
            });
        } else {
            window.crmConfirm(`Are you sure you want to delete policy "${policyNumber}"?`).then((result) => { if (result.isConfirmed) { doDelete(); } });
        }
    }

    /**
     * Change page
     */
    function changePagePolicy(page) {
        loadPolicies(listState.search, page);
    }

    /**
     * Open modal for quick add
     */
    function openModal(type) {
        // Redirect to respective create page or open modal
        const routes = {
            familyGroup: '/masters/family-groups',
            customer: '/masters/customers/create',
            policyType: '/masters/insurance-policy-types',
            policySubType: '/masters/insurance-policy-sub-types',
            segment: '/masters/segments',
            company: '/masters/company-master',
            branch: '/masters/branches'
        };

        if (routes[type]) {
            window.location.href = routes[type];
        }
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        let html = div.innerHTML;
        html = html.replace(/'/g, "&#39;");
        return html;
    }

    // Expose functions globally
    window.deletePolicy = deletePolicy;
    window.changePagePolicy = changePagePolicy;
    window.openModal = openModal;

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

