document.addEventListener('DOMContentLoaded', () => {
    // Initialize dashboard charts if we're on the dashboard page
    if (typeof window.ufscChartData !== 'undefined') {
        initializeDashboardCharts();
    }

    // Existing functionality for other pages
    initializeSearchFilters();
    initializeSortableHeaders();
    
    // Initialize form validation
    initializeFormValidation();
});

/**
 * Initialize all dashboard charts
 */
function initializeDashboardCharts() {
    const chartData = window.ufscChartData;

    // Gender pie chart
    if (document.getElementById('genderChart')) {
        const genderData = chartData.gender.map(item => ({
            label: item.label === 'M' ? 'Hommes' : 'Femmes',
            value: parseInt(item.value)
        }));

        new Chart(document.getElementById('genderChart'), {
            type: 'pie',
            data: {
                labels: genderData.map(item => item.label),
                datasets: [{
                    data: genderData.map(item => item.value),
                    backgroundColor: ['#3b82f6', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Region histogram
    if (document.getElementById('regionChart')) {
        const regionData = chartData.region;
        new Chart(document.getElementById('regionChart'), {
            type: 'bar',
            data: {
                labels: regionData.map(item => item.label.replace('UFSC ', '')),
                datasets: [{
                    label: 'Licenciés',
                    data: regionData.map(item => parseInt(item.value)),
                    backgroundColor: '#3b82f6',
                    borderColor: '#1d4ed8',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    x: {
                        ticks: {
                            maxRotation: 45
                        }
                    }
                }
            }
        });
    }

    // Competition donut chart
    if (document.getElementById('competitionChart')) {
        const competitionData = chartData.competition;
        new Chart(document.getElementById('competitionChart'), {
            type: 'doughnut',
            data: {
                labels: competitionData.map(item => item.label),
                datasets: [{
                    data: competitionData.map(item => parseInt(item.value)),
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Age groups bar chart
    if (document.getElementById('ageGroupsChart')) {
        const ageData = chartData.age_groups;
        new Chart(document.getElementById('ageGroupsChart'), {
            type: 'bar',
            data: {
                labels: ageData.map(item => item.label),
                datasets: [{
                    label: 'Licenciés',
                    data: ageData.map(item => parseInt(item.value)),
                    backgroundColor: '#8b5cf6',
                    borderColor: '#7c3aed',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    x: {
                        ticks: {
                            maxRotation: 45
                        }
                    }
                }
            }
        });
    }

    // Employment status chart
    if (document.getElementById('employmentChart')) {
        const employmentData = chartData.employment;
        new Chart(document.getElementById('employmentChart'), {
            type: 'bar',
            data: {
                labels: employmentData.map(item => item.label),
                datasets: [{
                    label: 'Licenciés',
                    data: employmentData.map(item => parseInt(item.value)),
                    backgroundColor: '#f97316',
                    borderColor: '#ea580c',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Volunteer ratio pie chart
    if (document.getElementById('volunteerChart')) {
        const volunteerData = chartData.volunteer;
        new Chart(document.getElementById('volunteerChart'), {
            type: 'pie',
            data: {
                labels: volunteerData.map(item => item.label),
                datasets: [{
                    data: volunteerData.map(item => parseInt(item.value)),
                    backgroundColor: ['#06b6d4', '#84cc16', '#6b7280'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // License evolution line chart
    if (document.getElementById('evolutionChart')) {
        const evolutionData = chartData.evolution;
        new Chart(document.getElementById('evolutionChart'), {
            type: 'line',
            data: {
                labels: evolutionData.map(item => item.label),
                datasets: [{
                    label: 'Nouvelles licences',
                    data: evolutionData.map(item => parseInt(item.value)),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

/**
 * Initialize search filters (existing functionality)
 */
function initializeSearchFilters() {
    const searchInput = document.querySelector('#ufsc-search-input');
    const filterForm = document.querySelector('.ufsc-licences-filters-form');

    if (searchInput && filterForm) {
        searchInput.addEventListener('keyup', debounce(() => {
            filterForm.submit();
        }, 300));
    }
}

/**
 * Initialize sortable headers (existing functionality)
 */
function initializeSortableHeaders() {
    document.querySelectorAll('table.widefat th.sortable').forEach(header => {
        header.addEventListener('click', () => {
            const currentUrl = new URL(window.location.href);
            const column = header.dataset.column;
            let order = currentUrl.searchParams.get('order') === 'asc' ? 'desc' : 'asc';
            currentUrl.searchParams.set('orderby', column);
            currentUrl.searchParams.set('order', order);
            window.location.href = currentUrl.toString();
        });
    });
}

// Fonction de debounce pour limiter les appels en saisie
function debounce(fn, delay) {
    let timer = null;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

/**
 * Initialize comprehensive form validation
 * Validates club and license forms with real-time feedback
 */
function initializeFormValidation() {
    // Club form validation
    const clubForm = document.querySelector('form[action*="ufsc_"]');
    if (clubForm) {
        initializeClubFormValidation(clubForm);
    }
    
    // License form validation  
    const licenseForm = document.querySelector('form input[name="ufsc_add_licence_nonce"]');
    if (licenseForm) {
        initializeLicenseFormValidation(licenseForm.closest('form'));
    }
}

/**
 * Initialize club form validation
 */
function initializeClubFormValidation(form) {
    // Required dirigeant fields (president, secretary, treasurer)
    const dirigeantRoles = ['president', 'secretaire', 'tresorier'];
    const dirigeantFields = ['nom', 'prenom', 'tel', 'email'];
    
    // Create validation rules
    const validationRules = [];
    
    // Basic club info validation
    validationRules.push({
        field: 'nom',
        message: 'Le nom du club est obligatoire',
        validate: (value) => value.trim().length > 0
    });
    
    validationRules.push({
        field: 'region',
        message: 'La région est obligatoire',
        validate: (value) => value.trim().length > 0
    });
    
    validationRules.push({
        field: 'ville',
        message: 'La ville est obligatoire',
        validate: (value) => value.trim().length > 0
    });
    
    validationRules.push({
        field: 'email',
        message: 'L\'email du club est obligatoire et doit être valide',
        validate: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
    });
    
    validationRules.push({
        field: 'telephone',
        message: 'Le téléphone du club est obligatoire',
        validate: (value) => value.trim().length > 0
    });
    
    // Dirigeant validation rules
    dirigeantRoles.forEach(role => {
        dirigeantFields.forEach(field => {
            const fieldName = `${role}_${field}`;
            let message = '';
            let validateFn = null;
            
            if (field === 'nom') {
                message = `Le nom du ${role} est obligatoire`;
                validateFn = (value) => value.trim().length > 0;
            } else if (field === 'prenom') {
                message = `Le prénom du ${role} est obligatoire`;
                validateFn = (value) => value.trim().length > 0;
            } else if (field === 'email') {
                message = `L'email du ${role} est obligatoire et doit être valide`;
                validateFn = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            } else if (field === 'tel') {
                message = `Le téléphone du ${role} est obligatoire`;
                validateFn = (value) => value.trim().length > 0;
            }
            
            if (validateFn) {
                validationRules.push({
                    field: fieldName,
                    message: message,
                    validate: validateFn
                });
            }
        });
    });
    
    // Add real-time validation
    addRealTimeValidation(form, validationRules);
    
    // Add form submit validation
    form.addEventListener('submit', function(e) {
        if (!validateForm(form, validationRules)) {
            e.preventDefault();
            showValidationSummary();
        }
    });
}

/**
 * Initialize license form validation
 */
function initializeLicenseFormValidation(form) {
    // Required license fields (name, address, contact info)
    const validationRules = [
        {
            field: 'nom',
            message: 'Le nom est obligatoire',
            validate: (value) => value.trim().length > 0
        },
        {
            field: 'prenom',
            message: 'Le prénom est obligatoire',
            validate: (value) => value.trim().length > 0
        },
        {
            field: 'email',
            message: 'L\'email est obligatoire et doit être valide',
            validate: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
        },
        {
            field: 'adresse',
            message: 'L\'adresse est obligatoire',
            validate: (value) => value.trim().length > 0
        },
        {
            field: 'code_postal',
            message: 'Le code postal est obligatoire (5 chiffres)',
            validate: (value) => /^\d{5}$/.test(value)
        },
        {
            field: 'ville',
            message: 'La ville est obligatoire',
            validate: (value) => value.trim().length > 0
        },
        {
            field: 'region',
            message: 'La région est obligatoire',
            validate: (value) => value.trim().length > 0
        },
        {
            field: 'tel_mobile',
            message: 'Au moins un numéro de téléphone est obligatoire',
            validate: (value, form) => {
                const telFixe = form.querySelector('[name="tel_fixe"]')?.value || '';
                return value.trim().length > 0 || telFixe.trim().length > 0;
            }
        }
    ];
    
    // Add real-time validation
    addRealTimeValidation(form, validationRules);
    
    // Add form submit validation
    form.addEventListener('submit', function(e) {
        if (!validateForm(form, validationRules)) {
            e.preventDefault();
            showValidationSummary();
        }
    });
}

/**
 * Add real-time validation to form fields
 */
function addRealTimeValidation(form, validationRules) {
    validationRules.forEach(rule => {
        const field = form.querySelector(`[name="${rule.field}"]`);
        if (field) {
            // Add validation on blur and input events
            ['blur', 'input'].forEach(event => {
                field.addEventListener(event, function() {
                    validateField(field, rule, form);
                });
            });
        }
    });
}

/**
 * Validate a single field
 */
function validateField(field, rule, form) {
    const value = field.value;
    const isValid = rule.validate(value, form);
    
    // Remove existing error styling and messages
    field.classList.remove('ufsc-field-error');
    const existingError = field.parentNode.querySelector('.ufsc-error-message');
    if (existingError) {
        existingError.remove();
    }
    
    // Add error styling and message if invalid
    if (!isValid && value.length > 0) { // Only show error if user has started typing
        field.classList.add('ufsc-field-error');
        const errorMessage = document.createElement('div');
        errorMessage.className = 'ufsc-error-message';
        errorMessage.textContent = rule.message;
        field.parentNode.appendChild(errorMessage);
    }
    
    return isValid;
}

/**
 * Validate entire form
 */
function validateForm(form, validationRules) {
    let isValid = true;
    const errors = [];
    
    validationRules.forEach(rule => {
        const field = form.querySelector(`[name="${rule.field}"]`);
        if (field) {
            const fieldValid = validateField(field, rule, form);
            if (!fieldValid) {
                isValid = false;
                errors.push(rule.message);
            }
        }
    });
    
    return isValid;
}

/**
 * Show validation summary
 */
function showValidationSummary() {
    const errors = document.querySelectorAll('.ufsc-error-message');
    if (errors.length > 0) {
        // Scroll to first error
        errors[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Show notification
        const notification = document.createElement('div');
        notification.className = 'notice notice-error is-dismissible';
        notification.innerHTML = `
            <p><strong>Erreurs dans le formulaire :</strong> Veuillez corriger les champs en rouge avant de continuer.</p>
            <button type="button" class="notice-dismiss" onclick="this.parentNode.remove()">
                <span class="screen-reader-text">Ignorer cette notice.</span>
            </button>
        `;
        
        // Insert notification at top of form
        const form = document.querySelector('form');
        if (form) {
            form.insertBefore(notification, form.firstChild);
        }
    }
}
