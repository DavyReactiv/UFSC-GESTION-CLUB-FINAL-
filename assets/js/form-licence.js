document.addEventListener('DOMContentLoaded', function() {
    // Auto-toggle fields based on reduction selections
    const reductionPostier = document.querySelector('#reduction_postier');
    if (reductionPostier) {
        const laposteField = document.querySelector('#identifiant_laposte')?.closest('.ufsc-form-field');
        const toggleLaposte = () => {
            if (reductionPostier.checked) {
                if (laposteField) {
                    laposteField.style.display = '';
                    const input = laposteField.querySelector('input');
                    if (input) input.required = true;
                }
            } else {
                if (laposteField) {
                    laposteField.style.display = 'none';
                    const input = laposteField.querySelector('input');
                    if (input) input.required = false;
                }
            }
        };
        reductionPostier.addEventListener('change', toggleLaposte);
        toggleLaposte();
    }

    // Auto-toggle delegataire license number field
    const licenceDelegataire = document.querySelector('#licence_delegataire');
    if (licenceDelegataire) {
        const numeroField = document.querySelector('#numero_licence_delegataire')?.closest('.ufsc-form-field');
        const toggleNumero = () => {
            if (licenceDelegataire.checked) {
                if (numeroField) {
                    numeroField.style.display = '';
                    const input = numeroField.querySelector('input');
                    if (input) input.required = true;
                }
            } else {
                if (numeroField) {
                    numeroField.style.display = 'none';
                    const input = numeroField.querySelector('input');
                    if (input) input.required = false;
                }
            }
        };
        licenceDelegataire.addEventListener('change', toggleNumero);
        toggleNumero();
    }

    // Form validation improvements
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            let hasErrors = false;

            // Check required fields
            form.querySelectorAll('input[required], select[required]').forEach(field => {
                const container = field.closest('.ufsc-form-field');
                if (!field.value.trim()) {
                    container.classList.add('error');
                    if (!container.querySelector('.error-message')) {
                        const span = document.createElement('span');
                        span.className = 'error-message';
                        span.textContent = 'Ce champ est requis';
                        container.appendChild(span);
                    }
                    hasErrors = true;
                } else {
                    container.classList.remove('error');
                    const msg = container.querySelector('.error-message');
                    if (msg) msg.remove();
                }
            });

            // Email validation
            form.querySelectorAll('input[type="email"]').forEach(field => {
                const container = field.closest('.ufsc-form-field');
                const email = field.value.trim();

                if (email && !isValidEmail(email)) {
                    container.classList.add('error');
                    if (!container.querySelector('.error-message')) {
                        const span = document.createElement('span');
                        span.className = 'error-message';
                        span.textContent = "Format d'email invalide";
                        container.appendChild(span);
                    }
                    hasErrors = true;
                } else if (email) {
                    container.classList.remove('error');
                    const msg = container.querySelector('.error-message');
                    if (msg) msg.remove();
                }
            });

            // Phone validation (basic)
            form.querySelectorAll('input[name="tel_fixe"], input[name="tel_mobile"]').forEach(field => {
                const container = field.closest('.ufsc-form-field');
                const phone = field.value.trim();

                if (phone && !isValidPhone(phone)) {
                    container.classList.add('error');
                    if (!container.querySelector('.error-message')) {
                        const span = document.createElement('span');
                        span.className = 'error-message';
                        span.textContent = 'Format de téléphone invalide';
                        container.appendChild(span);
                    }
                    hasErrors = true;
                } else if (phone) {
                    container.classList.remove('error');
                    const msg = container.querySelector('.error-message');
                    if (msg) msg.remove();
                }
            });

            if (hasErrors) {
                e.preventDefault();
                const firstError = document.querySelector('.ufsc-form-field.error');
                if (firstError) {
                    const offset = firstError.getBoundingClientRect().top + window.pageYOffset - 100;
                    window.scrollTo({ top: offset, behavior: 'smooth' });
                }
            }
        });
    });

    // Clear error states on input
    const clearError = (e) => {
        const container = e.target.closest('.ufsc-form-field');
        if (container) {
            container.classList.remove('error');
            const msg = container.querySelector('.error-message');
            if (msg) msg.remove();
        }
    };
    document.querySelectorAll('input, select, textarea').forEach(el => {
        el.addEventListener('input', clearError);
        el.addEventListener('change', clearError);
    });

    // Enhanced search filters
    document.querySelectorAll('.ufsc-filters-form').forEach(form => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('input, select').forEach(field => {
                if (!field.value) {
                    field.disabled = true;
                }
            });
        });
    });

    // Auto-uppercase postal code
    document.querySelectorAll('input[name="code_postal"]').forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.toUpperCase();
        });
    });

    // Helper functions
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    function isValidPhone(phone) {
        // French phone number validation (basic)
        const phoneRegex = /^(?:(?:\+33|0)[1-9](?:[.-\s]?\d{2}){4})$/;
        return phoneRegex.test(phone.replace(/[\s.-]/g, ''));
    }

    // Responsive form enhancements
    function adjustFormLayout() {
        const grids = document.querySelectorAll('.ufsc-form-grid-2, .ufsc-form-grid-3');
        if (window.innerWidth < 768) {
            grids.forEach(g => g.classList.add('ufsc-mobile-stack'));
        } else {
            grids.forEach(g => g.classList.remove('ufsc-mobile-stack'));
        }
    }

    window.addEventListener('resize', adjustFormLayout);
    adjustFormLayout();
});
