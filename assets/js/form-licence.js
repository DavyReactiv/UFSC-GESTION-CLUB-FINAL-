jQuery(document).ready(function($) {
    // Enhanced form interactions for UFSC License Form
    
    // Auto-toggle fields based on reduction selections
    $('#reduction_postier').change(function() {
        const $laposteField = $('#identifiant_laposte').closest('.ufsc-form-field');
        if ($(this).is(':checked')) {
            $laposteField.show().find('input').attr('required', true);
        } else {
            $laposteField.hide().find('input').attr('required', false);
        }
    }).trigger('change');
    
    // Auto-toggle delegataire license number field
    $('#licence_delegataire').change(function() {
        const $numeroField = $('#numero_licence_delegataire').closest('.ufsc-form-field');
        if ($(this).is(':checked')) {
            $numeroField.show().find('input').attr('required', true);
        } else {
            $numeroField.hide().find('input').attr('required', false);
        }
    }).trigger('change');
    
    // Form validation improvements
    $('form').on('submit', function(e) {
        let hasErrors = false;
        
        // Check required fields
        $(this).find('input[required], select[required]').each(function() {
            const $field = $(this);
            const $container = $field.closest('.ufsc-form-field');
            
            if (!$field.val().trim()) {
                $container.addClass('error');
                if (!$container.find('.error-message').length) {
                    $container.append('<span class="error-message">Ce champ est requis</span>');
                }
                hasErrors = true;
            } else {
                $container.removeClass('error').find('.error-message').remove();
            }
        });
        
        // Email validation
        $('input[type="email"]').each(function() {
            const $field = $(this);
            const $container = $field.closest('.ufsc-form-field');
            const email = $field.val().trim();
            
            if (email && !isValidEmail(email)) {
                $container.addClass('error');
                if (!$container.find('.error-message').length) {
                    $container.append('<span class="error-message">Format d\'email invalide</span>');
                }
                hasErrors = true;
            } else if (email) {
                $container.removeClass('error').find('.error-message').remove();
            }
        });
        
        // Phone validation (basic)
        $('input[name="tel_fixe"], input[name="tel_mobile"]').each(function() {
            const $field = $(this);
            const $container = $field.closest('.ufsc-form-field');
            const phone = $field.val().trim();
            
            if (phone && !isValidPhone(phone)) {
                $container.addClass('error');
                if (!$container.find('.error-message').length) {
                    $container.append('<span class="error-message">Format de téléphone invalide</span>');
                }
                hasErrors = true;
            } else if (phone) {
                $container.removeClass('error').find('.error-message').remove();
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('.ufsc-form-field.error:first').offset().top - 100
            }, 500);
        }
    });
    
    // Clear error states on input
    $('input, select, textarea').on('input change', function() {
        $(this).closest('.ufsc-form-field').removeClass('error').find('.error-message').remove();
    });
    
    // Enhanced search filters
    $('.ufsc-filters-form').on('submit', function() {
        // Remove empty filter values to clean up URL
        $(this).find('input, select').each(function() {
            if (!$(this).val()) {
                $(this).prop('disabled', true);
            }
        });
    });
    
    // Auto-uppercase postal code
    $('input[name="code_postal"]').on('input', function() {
        $(this).val($(this).val().toUpperCase());
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
        if ($(window).width() < 768) {
            $('.ufsc-form-grid-2, .ufsc-form-grid-3').addClass('ufsc-mobile-stack');
        } else {
            $('.ufsc-form-grid-2, .ufsc-form-grid-3').removeClass('ufsc-mobile-stack');
        }
    }
    
    $(window).on('resize', adjustFormLayout);
    adjustFormLayout();
});