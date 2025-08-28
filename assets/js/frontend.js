
// Simple toast system
function ufscToast(msg, type){
    try{
        var wrap = document.getElementById('ufsc-toasts');
        if(!wrap){
            wrap = document.createElement('div');
            wrap.id = 'ufsc-toasts';
            wrap.style.position='fixed'; wrap.style.right='16px'; wrap.style.bottom='16px';
            wrap.style.zIndex='99999';
            document.body.appendChild(wrap);
        }
        var el = document.createElement('div');
        el.className = 'ufsc-toast ufsc-toast-'+(type||'info');
        el.style.marginTop='8px'; el.style.padding='10px 14px'; el.style.borderRadius='8px';
        el.style.background = (type==='error')?'#fde8e8':(type==='success')?'#e6f7ea':'#e8f1ff';
        el.style.border = (type==='error')?'1px solid #f5a3a3':(type==='success')?'1px solid #79d18a':'1px solid #8fbaff';
        el.style.boxShadow='0 2px 12px rgba(0,0,0,.08)';
        el.style.color='#222'; el.style.fontSize='14px';
        el.textContent = msg;
        wrap.appendChild(el);
        setTimeout(function(){ el.style.opacity='0'; el.style.transition='opacity .4s'; setTimeout(function(){ el.remove(); }, 400); }, 2800);
    }catch(e){ console && console.log(e); }
}
// Script frontend UFSC with enhanced AJAX and data synchronization
jQuery(document).ready(function ($) {
    console.log('UFSC plugin frontend actif ✅');
    
    // Initialize UFSC data synchronization system
    const UFSCDataSync = {
        
        // Configuration
        config: {
            ajaxUrl: ufsc_frontend_config?.ajax_url || '/wp-admin/admin-ajax.php',
            nonce: ufsc_frontend_config?.nonce || '',
            canManage: ufsc_frontend_config?.can_manage || false,
            refreshInterval: 30000, // 30 seconds
            retryAttempts: 3,
            retryDelay: 1000
        },

        // Initialize the synchronization system
        init: function() {
            this.bindClubFormSubmission();
            this.initializeNotifications();
            this.setupDataRefresh();
            this.bindUIEvents();
            console.log('✅ UFSC Data Synchronization initialized');
        },

        // Handle club form AJAX submission
        bindClubFormSubmission: function() {
            $(document).on('submit', 'form.ufsc-club-form', function(e) {
                e.preventDefault();
                const $form = $(this);
                
                UFSCDataSync.logOperation('form_submission_start', {
                    form_action: $form.attr('action'),
                    has_files: $form.find('input[type="file"]').length > 0
                });

                UFSCDataSync.submitClubForm($form);
            });
        },

        // Submit club form via AJAX
        submitClubForm: function($form) {
            const self = this;
            const formData = new FormData($form[0]);
            formData.append('action', 'ufsc_save_club');
            
            // Show loading state
            self.showFormLoading($form, true);
            self.clearFormMessages($form);

            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000,
                success: function(response) {
                    self.showFormLoading($form, false);
                    
                    if (response.success) {
                        self.handleFormSuccess($form, response.data);
                        self.logOperation('form_submission_success', {
                            club_id: response.data.club_id,
                            operation: response.data.operation
                        });
                    } else {
                        self.handleFormError($form, response.data);
                        self.logOperation('form_submission_error', {
                            errors: response.data.errors || [response.data.message]
                        });
                    }
                },
                error: function(xhr, status, error) {
                    self.showFormLoading($form, false);
                    self.handleFormError($form, {
                        message: 'Erreur de connexion. Veuillez réessayer.',
                        error_code: 'NETWORK_ERROR'
                    });
                    self.logOperation('form_submission_network_error', {
                        status: status,
                        error: error,
                        xhr_status: xhr.status
                    });
                }
            });
        },

        // Handle successful form submission
        handleFormSuccess: function($form, data) {
            // Show success message
            this.showMessage($form, 'success', data.message);
            
            // Refresh data from database
            if (data.club_id) {
                this.refreshClubData(data.club_id);
            }
            
            // Show success animation if available
            if (typeof UFSCFormEnhancer !== 'undefined' && UFSCFormEnhancer.showSuccessAnimation) {
                UFSCFormEnhancer.showSuccessAnimation();
            }
            
            // Emit custom event for other components
            $(document).trigger('ufsc:club:saved', data);
            
            // Auto-redirect for affiliation if applicable
            if (data.operation === 'create' && $form.data('is-affiliation')) {
                setTimeout(() => {
                    window.location.href = data.redirect_url || '/checkout/';
                }, 2000);
            }
        },

        // Handle form submission error
        handleFormError: function($form, errorData) {
            let message = errorData.message || 'Une erreur est survenue.';
            
            // Show main error message
            this.showMessage($form, 'error', message);
            
            // Show field-specific errors if available
            if (errorData.errors && Array.isArray(errorData.errors)) {
                this.showFieldErrors($form, errorData.errors);
            }
            
            // Scroll to first error
            const $firstError = $form.find('.ufsc-field-error, .ufsc-alert-error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
        },

        // Show loading state on form
        showFormLoading: function($form, isLoading) {
            const $submitBtn = $form.find('button[type="submit"]');
            
            if (isLoading) {
                $submitBtn.prop('disabled', true).addClass('loading');
                
                // Add loading overlay if not exists
                if (!$form.find('.ufsc-form-loading-overlay').length) {
                    $form.append(`
                        <div class="ufsc-form-loading-overlay">
                            <div class="ufsc-loading-spinner"></div>
                            <span>Enregistrement en cours...</span>
                        </div>
                    `);
                }
            } else {
                $submitBtn.prop('disabled', false).removeClass('loading');
                $form.find('.ufsc-form-loading-overlay').remove();
            }
        },

        // Show success/error messages
        showMessage: function($form, type, message) {
            const alertClass = type === 'success' ? 'ufsc-alert-success' : 'ufsc-alert-error';
            const iconClass = type === 'success' ? 'dashicons-yes-alt' : 'dashicons-warning';
            
            const $alert = $(`
                <div class="ufsc-alert ${alertClass}" role="alert">
                    <i class="dashicons ${iconClass}"></i>
                    <p>${message}</p>
                    <button type="button" class="ufsc-alert-close" aria-label="Fermer">&times;</button>
                </div>
            `);
            
            $form.prepend($alert);
            
            // Auto-hide success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => $alert.fadeOut(), 5000);
            }
        },

        // Clear existing messages
        clearFormMessages: function($form) {
            $form.find('.ufsc-alert').remove();
            $form.find('.ufsc-field-error').removeClass('ufsc-field-error');
            $form.find('.ufsc-error-message').remove();
        },

        // Show field-specific errors
        showFieldErrors: function($form, errors) {
            errors.forEach(error => {
                // Try to match error to specific field
                const fieldMatch = error.match(/(\w+)/);
                if (fieldMatch) {
                    const fieldName = fieldMatch[1];
                    const $field = $form.find(`[name="${fieldName}"]`);
                    if ($field.length) {
                        $field.addClass('ufsc-field-error');
                        $field.after(`<div class="ufsc-error-message">${error}</div>`);
                    }
                }
            });
        },

        // Refresh club data from database
        refreshClubData: function(clubId) {
            const self = this;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: Object.assign({
                    action: 'ufsc_get_club_data',
                    ufsc_nonce: self.config.nonce
                }, self.config.canManage && clubId ? { club_id: clubId } : {}),
                success: function(response) {
                    if (response.success) {
                        self.updateUIWithFreshData(response.data.club_data);
                        self.logOperation('data_refresh_success', {
                            club_id: clubId,
                            timestamp: response.data.timestamp
                        });
                    }
                },
                error: function() {
                    self.logOperation('data_refresh_error', { club_id: clubId });
                }
            });
        },

        // Update UI with fresh data from database
        updateUIWithFreshData: function(clubData) {
            // Update any displayed club information
            if (clubData && typeof clubData === 'object') {
                $('.ufsc-club-name').text(clubData.nom || '');
                $('.ufsc-club-email').text(clubData.email || '');
                $('.ufsc-club-phone').text(clubData.telephone || '');
                $('.ufsc-club-status').text(clubData.statut || '');
                
                // Emit event for other components to update
                $(document).trigger('ufsc:club:dataUpdated', clubData);
            }
        },

        // Initialize notification system (fallback if Notyf not available)
        initializeNotifications: function() {
            if (typeof notyf === 'undefined') {
                // Create fallback notification system
                window.ufscNotifications = {
                    success: function(message) {
                        console.log('✅ Success:', message);
                        UFSCDataSync.showToast('success', message);
                    },
                    error: function(message) {
                        console.error('❌ Error:', message);
                        UFSCDataSync.showToast('error', message);
                    },
                    info: function(message) {
                        console.info('ℹ️ Info:', message);
                        UFSCDataSync.showToast('info', message);
                    }
                };
                console.warn('⚠️ Notyf library not found, using fallback notifications');
            } else {
                window.ufscNotifications = notyf;
            }
        },

        // Fallback toast notification system
        showToast: function(type, message) {
            const $toast = $(`
                <div class="ufsc-toast ufsc-toast-${type}">
                    <span>${message}</span>
                    <button type="button" class="ufsc-toast-close">&times;</button>
                </div>
            `);
            
            $('body').append($toast);
            
            // Auto-remove after 4 seconds
            setTimeout(() => $toast.fadeOut(() => $toast.remove()), 4000);
        },

        // Setup periodic data refresh for lists
        setupDataRefresh: function() {
            // Only refresh data on admin pages with club lists
            if ($('.ufsc-clubs-list').length > 0 && typeof ufsc_dashboard_data !== 'undefined') {
                setInterval(() => {
                    this.refreshClubsList();
                }, this.config.refreshInterval);
            }
        },

        // Refresh clubs list
        refreshClubsList: function() {
            const self = this;
            
            $.ajax({
                url: self.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ufsc_get_clubs_list',
                    ufsc_nonce: ufsc_dashboard_data?.nonce || ''
                },
                success: function(response) {
                    if (response.success) {
                        self.updateClubsList(response.data.clubs);
                        self.logOperation('clubs_list_refresh_success', {
                            count: response.data.count
                        });
                    }
                },
                error: function() {
                    self.logOperation('clubs_list_refresh_error');
                }
            });
        },

        // Update clubs list UI
        updateClubsList: function(clubs) {
            // Update DataTable if available
            if ($.fn.DataTable && $('.ufsc-clubs-table').DataTable()) {
                $('.ufsc-clubs-table').DataTable().clear().rows.add(clubs).draw();
            }
            
            // Emit event for other components
            $(document).trigger('ufsc:clubs:listUpdated', clubs);
        },

        // Bind additional UI events
        bindUIEvents: function() {
            // Close alert messages
            $(document).on('click', '.ufsc-alert-close, .ufsc-toast-close', function() {
                $(this).closest('.ufsc-alert, .ufsc-toast').fadeOut(() => {
                    $(this).remove();
                });
            });
            
            // Handle retry buttons
            $(document).on('click', '.ufsc-retry-btn', function() {
                const $form = $(this).closest('.ufsc-form');
                if ($form.length) {
                    UFSCDataSync.submitClubForm($form);
                }
            });
        },

        // Log operations for debugging
        logOperation: function(operation, data = {}) {
            if (typeof console !== 'undefined') {
                const logData = {
                    timestamp: new Date().toISOString(),
                    operation: operation,
                    ...data
                };
                console.log('🔄 UFSC Sync:', logData);
            }
        }
    };

    // Initialize data synchronization system
    UFSCDataSync.init();
    
    // Make it available globally for debugging
    window.UFSCDataSync = UFSCDataSync;
    
    // Enhanced download handling for professional features
    if (typeof window.UFSCPro !== 'undefined') {
        
        // Handle document downloads with loading and notifications
        $(document).on('click', '.ufsc-download-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const downloadUrl = $btn.data('download-url');
            
            if (!downloadUrl) {
                ufscNotifications.error('URL de téléchargement non trouvée');
                return;
            }
            
            // Show loading state
            UFSCPro.loading.showOn($btn, 'Téléchargement...');
            
            // Create invisible link for download
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.target = '_blank';
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Hide loading and show success
            setTimeout(() => {
                UFSCPro.loading.hideFrom($btn);
                ufscNotifications.success('Téléchargement démarré');
            }, 500);
        });
        
        // Handle licence actions with AJAX
        $(document).on('click', '[data-licence-id]', function(e) {
        e.preventDefault();
            const $btn = $(this);
            const licenceId = $btn.data('licence-id');
            
            // Show licence details modal or handle action
            ufscNotifications.info('Fonctionnalité en cours de développement');
        });
    }
    
    // User Association Options Handler
    function initUserAssociationOptions() {
        $(document).on('change', 'input[name="user_association_type"]', function() {
            const selectedType = $(this).val();
            
            // Hide all conditional fields
            $('.ufsc-conditional-fields').hide();
            
            // Show appropriate fields based on selection
            if (selectedType === 'create') {
                $('#create-user-fields').show();
                // Make new user fields required
                $('#create-user-fields').find('input[name="new_user_login"], input[name="new_user_email"]').prop('required', true);
                $('#existing-user-fields').find('select').prop('required', false);
            } else if (selectedType === 'existing') {
                $('#existing-user-fields').show();
                // Make existing user selection required
                $('#existing-user-fields').find('select').prop('required', true);
                $('#create-user-fields').find('input').prop('required', false);
            } else {
                // Current user selected - no additional fields needed
                $('#create-user-fields').find('input').prop('required', false);
                $('#existing-user-fields').find('select').prop('required', false);
            }
        });
        
        // Validate user association on form submission
        $(document).on('submit', 'form.ufsc-club-form', function(e) {
            const $form = $(this);
            const associationType = $form.find('input[name="user_association_type"]:checked').val();
            
            if (associationType === 'create') {
                const login = $form.find('input[name="new_user_login"]').val();
                const email = $form.find('input[name="new_user_email"]').val();
                
                if (!login || !email) {
                    e.preventDefault();
                    ufscNotifications.error('Veuillez remplir le nom d\'utilisateur et l\'email pour créer un nouveau compte.');
                    return false;
                }
                
                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    ufscNotifications.error('Veuillez saisir une adresse email valide.');
                    return false;
                }
                
            } else if (associationType === 'existing') {
                const userId = $form.find('select[name="existing_user_id"]').val();
                
                if (!userId) {
                    e.preventDefault();
                    ufscNotifications.error('Veuillez sélectionner un utilisateur existant.');
                    return false;
                }
            }
        });
    }
    
    // Initialize user association options
    initUserAssociationOptions();
    
    // Legacy form validation for non-AJAX forms
    $('form:not(.ufsc-form)').on('submit', function(e) {
        const $form = $(this);
        
        // Validate required fields
        let hasErrors = false;
        $form.find('[required]').each(function() {
            const $field = $(this);
            if (!$field.val().trim()) {
                $field.attr('aria-invalid', 'true').addClass('ufsc-field-error');
                hasErrors = true;
            }
        });
        
        if (hasErrors) {
            e.preventDefault();
            ufscNotifications.error('Veuillez remplir tous les champs obligatoires');
            return;
        }
    });
});


    // Save licence as draft
    jQuery(document).on('click', '.ufsc-save-draft', function(e){
        e.preventDefault();
        var $btn = jQuery(this);
        var $form = $btn.closest('form');
        var data = $form.serializeArray();
        data.push({name:'action', value:'ufsc_save_licence_draft'});
        data.push({name:'ufsc_nonce', value: (window.ufsc_frontend_config && ufsc_frontend_config.nonce) ? ufsc_frontend_config.nonce : ''});
        jQuery.post((window.ufsc_frontend_config ? ufsc_frontend_config.ajax_url : '/wp-admin/admin-ajax.php'), data)
            .done(function(resp){
                var msg = (resp && resp.data && resp.data.message) ? resp.data.message : 'Brouillon enregistré.';
                alert(msg);
                if (resp && resp.success && resp.data && resp.data.licence_id) {
                    $form.find('[name="licence_id"]').val(resp.data.licence_id);
                }
            }).fail(function(){ ufscToast('Erreur lors de l’enregistrement du brouillon.', 'error'); });
    });

    // Pay a draft licence
    jQuery(document).on('click', '.ufsc-pay-licence', function(e){
        e.preventDefault();
        var $btn = jQuery(this);
        var licenceId = parseInt($btn.data('licence-id'), 10);
        var clubId = parseInt($btn.data('club-id'), 10) || 0;
        var data = { action: 'ufsc_get_licence_pay_url', ufsc_nonce: (window.ufsc_frontend_config ? ufsc_frontend_config.nonce : ''), licence_id: licenceId };
        if (ufsc_frontend_config && ufsc_frontend_config.can_manage && clubId) {
            data.club_id = clubId;
        }
        jQuery.post((window.ufsc_frontend_config ? ufsc_frontend_config.ajax_url : '/wp-admin/admin-ajax.php'), data)
            .done(function(resp){
                if (resp && resp.success && resp.data && resp.data.url) {
                    window.location.href = resp.data.url;
                } else {
                    alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Impossible de générer le paiement.');
                }
            }).fail(function(){ alert('Erreur réseau.'); });
    });
    

// Delete draft with confirmation
jQuery(document).on('click', '.ufsc-delete-draft', function(e){
    e.preventDefault();
    var $btn = jQuery(this);
    var licenceId = parseInt($btn.data('licence-id'), 10);
    var clubId    = parseInt($btn.data('club-id'), 10);
    if (!licenceId || !clubId) return;
    if (!confirm('Confirmer la suppression de ce brouillon ?')) return;
    jQuery.post((window.ufsc_frontend_config ? ufsc_frontend_config.ajax_url : (window.UFSC_AJAX ? UFSC_AJAX.url : '/wp-admin/admin-ajax.php')), {
        action: 'ufsc_delete_licence_draft',
        ufsc_nonce: (window.ufsc_frontend_config ? ufsc_frontend_config.nonce : (window.UFSC_AJAX ? UFSC_AJAX.nonce : '')),
        licence_id: licenceId
    }).done(function(resp){
        if (resp && resp.success){
            ufscToast(resp.data && resp.data.message ? resp.data.message : 'Brouillon supprimé.', 'success');
            // Remove the table row smoothly
            var $tr = $btn.closest('tr');
            $tr.fadeOut(200, function(){ jQuery(this).remove(); });
        } else {
            ufscToast((resp && resp.data && resp.data.message) ? resp.data.message : 'Suppression impossible.', 'error');
        }
    }).fail(function(){
        ufscToast('Erreur réseau.', 'error');
    });
});
