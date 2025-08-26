/**
 * UFSC Add Licensee - AJAX Form Handler
 * 
 * Handles the AJAX submission of the "Add Licensee" form
 * to add licensees to WooCommerce cart instead of direct creation.
 * 
 * @package UFSC_Gestion_Club
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle form submission
        $('#ufsc-add-licencie-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalBtnText = $submitBtn.text();
            
            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true).text('Ajout en cours...');
            
            // Clear previous messages
            $('.ufsc-form-message').remove();
            
            // Prepare form data
            const formData = new FormData(this);
            formData.append('action', 'ufsc_add_licencie_to_cart');
            formData.append('nonce', ufscAjax.addLicencieNonce);
            
            // Send AJAX request
            $.ajax({
                url: ufscAjax.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showMessage(response.data.message, 'success');
                        
                        // Reset form
                        $form[0].reset();
                        
                        // Update cart count if available
                        if (response.data.cart_count) {
                            $('.ufsc-cart-count').text(response.data.cart_count);
                        }
                        
                        // Show cart actions
                        showCartActions(response.data.cart_url, response.data.checkout_url);
                        
                    } else {
                        // Show error message
                        showMessage(response.data.message || 'Erreur lors de l\'ajout.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
                    console.error('AJAX Error:', error);
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });
        
        /**
         * Show form message
         */
        function showMessage(message, type) {
            const messageClass = type === 'success' ? 'ufsc-alert-success' : 'ufsc-alert-error';
            const messageHtml = `
                <div class="ufsc-form-message ufsc-alert ${messageClass}">
                    <p>${message}</p>
                </div>
            `;
            
            $('#ufsc-add-licencie-form').before(messageHtml);
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $('.ufsc-form-message').offset().top - 50
            }, 500);
        }
        
        /**
         * Show cart action buttons
         */
        function showCartActions(cartUrl, checkoutUrl) {
            const actionsHtml = `
                <div class="ufsc-form-message ufsc-cart-actions">
                    <h4>Que souhaitez-vous faire maintenant ?</h4>
                    <p>
                        <a href="${cartUrl}" class="ufsc-btn ufsc-btn-outline">Voir le panier</a>
                        <a href="${checkoutUrl}" class="ufsc-btn ufsc-btn-primary">Finaliser la commande</a>
                        <button type="button" class="ufsc-btn ufsc-btn-secondary" onclick="$('.ufsc-cart-actions').slideUp();">Ajouter un autre licencié</button>
                    </p>
                </div>
            `;
            
            $('.ufsc-form-message').replaceWith(actionsHtml);
        }
        
        // Handle form field validation
        $('#ufsc-add-licencie-form input[required]').on('blur', function() {
            validateField($(this));
        });
        
        /**
         * Validate individual field
         */
        function validateField($field) {
            const value = $field.val().trim();
            const fieldName = $field.attr('name');
            
            // Remove existing validation message
            $field.next('.field-error').remove();
            $field.removeClass('error');
            
            // Check required fields
            if ($field.prop('required') && !value) {
                addFieldError($field, 'Ce champ est obligatoire.');
                return false;
            }
            
            // Specific validations
            switch (fieldName) {
                case 'email':
                    if (value && !isValidEmail(value)) {
                        addFieldError($field, 'Format d\'email invalide.');
                        return false;
                    }
                    break;
                    
                case 'date_naissance':
                    if (value && !isValidDate(value)) {
                        addFieldError($field, 'Format de date invalide (AAAA-MM-JJ).');
                        return false;
                    }
                    break;
                    
                case 'code_postal':
                    if (value && !/^[0-9]{5}$/.test(value)) {
                        addFieldError($field, 'Code postal invalide (5 chiffres).');
                        return false;
                    }
                    break;
            }
            
            return true;
        }
        
        /**
         * Add field error message
         */
        function addFieldError($field, message) {
            $field.addClass('error');
            $field.after(`<span class="field-error">${message}</span>`);
        }
        
        /**
         * Validate email format
         */
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        /**
         * Validate date format
         */
        function isValidDate(dateString) {
            const regex = /^\d{4}-\d{2}-\d{2}$/;
            if (!regex.test(dateString)) return false;
            
            const date = new Date(dateString);
            const timestamp = date.getTime();
            
            if (typeof timestamp !== 'number' || Number.isNaN(timestamp)) {
                return false;
            }
            
            return dateString === date.toISOString().split('T')[0];
        }
    });
    
})(jQuery);