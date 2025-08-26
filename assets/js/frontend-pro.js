/**
 * UFSC Professional Frontend Enhancements
 * Professional UI improvements and interactive features
 * Can be disabled via UFSC_ENABLE_FRONTEND_PRO constant
 */

(function($) {
    'use strict';

    // Global UFSC Pro object
    window.UFSCPro = {
        config: {
            toastDuration: 4000,
            loadingDelay: 300,
            datatableLanguage: {
                "decimal": "",
                "emptyTable": "Aucune donnée disponible",
                "info": "Affichage de _START_ à _END_ sur _TOTAL_ entrées",
                "infoEmpty": "Affichage de 0 à 0 sur 0 entrées",
                "infoFiltered": "(filtré à partir de _MAX_ entrées au total)",
                "infoPostFix": "",
                "thousands": ",",
                "lengthMenu": "Afficher _MENU_ entrées",
                "loadingRecords": "Chargement...",
                "processing": "Traitement...",
                "search": "Rechercher:",
                "zeroRecords": "Aucun enregistrement correspondant trouvé",
                "paginate": {
                    "first": "Premier",
                    "last": "Dernier",
                    "next": "Suivant",
                    "previous": "Précédent"
                },
                "aria": {
                    "sortAscending": ": activer pour trier la colonne par ordre croissant",
                    "sortDescending": ": activer pour trier la colonne par ordre décroissant"
                }
            }
        },
        
        notifications: {},
        loading: {},
        datatables: {},
        accessibility: {},
        tooltips: {}
    };

    // =============================================================================
    // TOAST NOTIFICATIONS SYSTEM
    // =============================================================================
    UFSCPro.notifications = {
        
        /**
         * Initialize Notyf library
         */
        init: function() {
            if (typeof Notyf !== 'undefined') {
                this.notyf = new Notyf({
                    duration: UFSCPro.config.toastDuration,
                    position: {
                        x: 'right',
                        y: 'top'
                    },
                    types: [
                        {
                            type: 'success',
                            background: 'var(--ufsc-success)',
                            icon: {
                                className: 'dashicons dashicons-yes-alt',
                                tagName: 'span',
                                color: 'white'
                            }
                        },
                        {
                            type: 'error',
                            background: 'var(--ufsc-error)',
                            icon: {
                                className: 'dashicons dashicons-dismiss',
                                tagName: 'span',
                                color: 'white'
                            }
                        },
                        {
                            type: 'warning',
                            background: 'var(--ufsc-warning)',
                            icon: {
                                className: 'dashicons dashicons-warning',
                                tagName: 'span',
                                color: 'white'
                            }
                        },
                        {
                            type: 'info',
                            background: 'var(--ufsc-info)',
                            icon: {
                                className: 'dashicons dashicons-info',
                                tagName: 'span',
                                color: 'white'
                            }
                        }
                    ]
                });
                
                console.log('✅ UFSC Pro: Toast notifications initialized');
            } else {
                console.warn('⚠️ UFSC Pro: Notyf library not loaded');
                // Fallback to basic alerts
                this.useFallback = true;
            }
        },

        /**
         * Show success notification
         */
        success: function(message) {
            if (this.notyf && !this.useFallback) {
                this.notyf.success(message);
            } else {
                this.fallback('success', message);
            }
        },

        /**
         * Show error notification
         */
        error: function(message) {
            if (this.notyf && !this.useFallback) {
                this.notyf.error(message);
            } else {
                this.fallback('error', message);
            }
        },

        /**
         * Show warning notification
         */
        warning: function(message) {
            if (this.notyf && !this.useFallback) {
                this.notyf.open({
                    type: 'warning',
                    message: message
                });
            } else {
                this.fallback('warning', message);
            }
        },

        /**
         * Show info notification
         */
        info: function(message) {
            if (this.notyf && !this.useFallback) {
                this.notyf.open({
                    type: 'info',
                    message: message
                });
            } else {
                this.fallback('info', message);
            }
        },

        /**
         * Fallback notification system
         */
        fallback: function(type, message) {
            const alertClass = 'ufsc-alert-' + type;
            const alertHtml = `
                <div class="ufsc-alert ${alertClass}" role="alert" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    max-width: 400px;
                    padding: 1rem;
                    border-radius: 0.375rem;
                    background: white;
                    border-left: 4px solid var(--ufsc-${type});
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    animation: slideInRight 0.3s ease;
                ">
                    <button type="button" class="ufsc-alert-close" style="
                        float: right;
                        background: none;
                        border: none;
                        font-size: 1.5rem;
                        cursor: pointer;
                        margin-left: 1rem;
                    ">&times;</button>
                    ${message}
                </div>
            `;
            
            const $alert = $(alertHtml);
            $('body').append($alert);
            
            // Auto dismiss
            setTimeout(() => {
                $alert.fadeOut(300, function() {
                    $(this).remove();
                });
            }, UFSCPro.config.toastDuration);
            
            // Manual dismiss
            $alert.find('.ufsc-alert-close').on('click', function() {
                $alert.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };

    // =============================================================================
    // LOADING SYSTEM
    // =============================================================================
    UFSCPro.loading = {
        
        /**
         * Show global loading overlay
         */
        show: function(message = 'Chargement...') {
            const overlay = this.createOverlay(message);
            $('body').append(overlay);
            
            // Small delay for better UX
            setTimeout(() => {
                overlay.addClass('active');
            }, 50);
        },

        /**
         * Hide global loading overlay
         */
        hide: function() {
            const $overlay = $('.ufsc-loading-overlay');
            $overlay.removeClass('active');
            
            setTimeout(() => {
                $overlay.remove();
            }, 150);
        },

        /**
         * Show loading on specific element
         */
        showOn: function(element, message = '') {
            const $element = $(element);
            const $spinner = $('<span class="ufsc-loading-inline" aria-hidden="true"></span>');
            
            if (message) {
                $spinner.after(' ' + message);
            }
            
            $element.prop('disabled', true);
            $element.prepend($spinner);
            $element.addClass('loading');
        },

        /**
         * Hide loading from specific element
         */
        hideFrom: function(element) {
            const $element = $(element);
            $element.find('.ufsc-loading-inline').remove();
            $element.prop('disabled', false);
            $element.removeClass('loading');
        },

        /**
         * Create loading overlay HTML
         */
        createOverlay: function(message) {
            return $(`
                <div class="ufsc-loading-overlay" role="progressbar" aria-label="${message}">
                    <div class="ufsc-loading-content">
                        <div class="ufsc-loading-spinner">
                            <div></div>
                            <div></div>
                            <div></div>
                            <div></div>
                        </div>
                        <div class="ufsc-loading-message" style="
                            margin-top: 1rem;
                            text-align: center;
                            color: var(--ufsc-gray-600);
                            font-weight: 500;
                        ">${message}</div>
                    </div>
                </div>
            `);
        }
    };

    // =============================================================================
    // DATATABLES ENHANCEMENT
    // =============================================================================
    UFSCPro.datatables = {
        
        /**
         * Initialize DataTables on specified elements
         */
        init: function() {
            if (typeof $.fn.DataTable === 'undefined') {
                console.warn('⚠️ UFSC Pro: DataTables library not loaded');
                return;
            }

            // Initialize on license tables
            this.initLicenseTables();
            
            // Initialize on document tables
            this.initDocumentTables();
            
            // Initialize on member tables
            this.initMemberTables();
            
            console.log('✅ UFSC Pro: DataTables initialized');
        },

        /**
         * Initialize license tables
         */
        initLicenseTables: function() {
            $('.ufsc-dashboard table').each(function() {
                const $table = $(this);
                
                // Check if table has license-related content
                if ($table.find('th:contains("Licence"), th:contains("Statut"), th:contains("Date")').length > 0) {
                    $table.addClass('ufsc-datatable');
                    $table.wrap('<div class="ufsc-datatable-wrapper"></div>');
                    
                    $table.DataTable({
                        language: UFSCPro.config.datatableLanguage,
                        responsive: true,
                        pageLength: 10,
                        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
                        order: [[0, 'desc']], // Sort by first column (usually date) descending
                        columnDefs: [
                            {
                                targets: 'no-sort',
                                orderable: false
                            }
                        ]
                    });
                }
            });
        },

        /**
         * Initialize document tables
         */
        initDocumentTables: function() {
            $('.ufsc-documents-table').each(function() {
                const $table = $(this);
                $table.addClass('ufsc-datatable');
                $table.wrap('<div class="ufsc-datatable-wrapper"></div>');
                
                $table.DataTable({
                    language: UFSCPro.config.datatableLanguage,
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25], [5, 10, 25]],
                    columnDefs: [
                        {
                            targets: -1, // Last column (actions)
                            orderable: false
                        }
                    ]
                });
            });
        },

        /**
         * Initialize member tables
         */
        initMemberTables: function() {
            $('.ufsc-members-table').each(function() {
                const $table = $(this);
                $table.addClass('ufsc-datatable');
                $table.wrap('<div class="ufsc-datatable-wrapper"></div>');
                
                $table.DataTable({
                    language: UFSCPro.config.datatableLanguage,
                    responsive: true,
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    columnDefs: [
                        {
                            targets: 'no-sort',
                            orderable: false
                        }
                    ]
                });
            });
        }
    };

    // =============================================================================
    // ACCESSIBILITY ENHANCEMENTS
    // =============================================================================
    UFSCPro.accessibility = {
        
        /**
         * Initialize accessibility enhancements
         */
        init: function() {
            this.addSkipLinks();
            this.enhanceNavigation();
            this.improveForms();
            this.addLiveRegion();
            
            console.log('✅ UFSC Pro: Accessibility enhancements initialized');
        },

        /**
         * Add skip links for keyboard navigation
         */
        addSkipLinks: function() {
            const skipLinks = `
                <div class="ufsc-skip-links" aria-label="Liens de navigation rapide">
                    <a href="#ufsc-main-content" class="ufsc-skip-link">Aller au contenu principal</a>
                    <a href="#ufsc-navigation" class="ufsc-skip-link">Aller à la navigation</a>
                </div>
            `;
            
            $('.ufsc-container, .ufsc-dashboard').first().prepend(skipLinks);
        },

        /**
         * Enhance navigation with ARIA attributes
         */
        enhanceNavigation: function() {
            // Add proper ARIA labels to navigation
            $('.ufsc-dashboard-nav').attr({
                'role': 'navigation',
                'aria-label': 'Navigation du tableau de bord',
                'id': 'ufsc-navigation'
            });

            // Enhance menu items
            $('.ufsc-dashboard-nav ul').attr('role', 'menubar');
            $('.ufsc-dashboard-nav li').attr('role', 'none');
            $('.ufsc-dashboard-nav a').attr('role', 'menuitem');

            // Mark current page
            $('.ufsc-dashboard-nav a.active').attr('aria-current', 'page');

            // Add main content landmark
            $('.ufsc-dashboard-content, .ufsc-container > div:first-child').attr({
                'id': 'ufsc-main-content',
                'role': 'main'
            });
        },

        /**
         * Improve form accessibility
         */
        improveForms: function() {
            // Associate labels with inputs
            $('input, select, textarea').each(function() {
                const $input = $(this);
                const $label = $input.closest('.form-group, .ufsc-form-group').find('label');
                
                if ($label.length && !$input.attr('id')) {
                    const id = 'ufsc-field-' + Math.random().toString(36).substr(2, 9);
                    $input.attr('id', id);
                    $label.attr('for', id);
                }
            });

            // Add required field indicators
            $('input[required], select[required], textarea[required]').each(function() {
                const $input = $(this);
                const $label = $(`label[for="${$input.attr('id')}"]`);
                
                if ($label.length && !$label.hasClass('required')) {
                    $label.addClass('required');
                    $input.attr('aria-required', 'true');
                }
            });

            // Add error messaging
            $('.error, .ufsc-error').each(function() {
                const $error = $(this);
                const $input = $error.closest('.form-group, .ufsc-form-group').find('input, select, textarea');
                
                if ($input.length) {
                    const errorId = 'error-' + Math.random().toString(36).substr(2, 9);
                    $error.attr('id', errorId);
                    $input.attr({
                        'aria-invalid': 'true',
                        'aria-describedby': errorId
                    });
                }
            });
        },

        /**
         * Add live region for dynamic updates
         */
        addLiveRegion: function() {
            if (!$('#ufsc-live-region').length) {
                $('body').append('<div id="ufsc-live-region" aria-live="polite" aria-atomic="true" class="ufsc-sr-only"></div>');
            }
        },

        /**
         * Announce message to screen readers
         */
        announce: function(message) {
            $('#ufsc-live-region').text(message);
        }
    };

    // =============================================================================
    // TOOLTIPS SYSTEM
    // =============================================================================
    UFSCPro.tooltips = {
        
        /**
         * Initialize tooltips
         */
        init: function() {
            this.createTooltips();
            this.bindEvents();
            
            console.log('✅ UFSC Pro: Tooltips initialized');
        },

        /**
         * Create tooltips for elements with data-tooltip attribute
         */
        createTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                
                if (!$element.hasClass('ufsc-tooltip')) {
                    $element.addClass('ufsc-tooltip');
                    $element.attr({
                        'tabindex': '0',
                        'aria-describedby': 'tooltip-' + Math.random().toString(36).substr(2, 9)
                    });
                }
            });
        },

        /**
         * Bind tooltip events
         */
        bindEvents: function() {
            $(document).on('keydown', '.ufsc-tooltip', function(e) {
                // Show tooltip on Enter or Space
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('focus');
                }
                
                // Hide tooltip on Escape
                if (e.key === 'Escape') {
                    $(this).trigger('blur');
                }
            });
        },

        /**
         * Add tooltip to element
         */
        add: function(element, text) {
            $(element).attr('data-tooltip', text).addClass('ufsc-tooltip');
            this.createTooltips();
        }
    };

    // =============================================================================
    // AJAX ENHANCEMENT
    // =============================================================================
    UFSCPro.ajax = {
        
        /**
         * Enhanced AJAX wrapper with loading and notifications
         */
        request: function(options) {
            const defaults = {
                showLoading: true,
                showNotifications: true,
                loadingMessage: 'Chargement...',
                successMessage: 'Opération réussie',
                errorMessage: 'Une erreur est survenue'
            };
            
            const settings = $.extend({}, defaults, options);
            
            // Show loading
            if (settings.showLoading) {
                if (settings.loadingElement) {
                    UFSCPro.loading.showOn(settings.loadingElement, settings.loadingMessage);
                } else {
                    UFSCPro.loading.show(settings.loadingMessage);
                }
            }
            
            // Prepare AJAX options
            const ajaxOptions = $.extend({}, settings, {
                success: function(response, textStatus, jqXHR) {
                    // Hide loading
                    if (settings.showLoading) {
                        if (settings.loadingElement) {
                            UFSCPro.loading.hideFrom(settings.loadingElement);
                        } else {
                            UFSCPro.loading.hide();
                        }
                    }
                    
                    // Show success notification
                    if (settings.showNotifications && response.success) {
                        UFSCPro.notifications.success(response.data?.message || settings.successMessage);
                    }
                    
                    // Announce to screen readers
                    if (response.success) {
                        UFSCPro.accessibility.announce(response.data?.message || settings.successMessage);
                    }
                    
                    // Call original success callback
                    if (settings.originalSuccess) {
                        settings.originalSuccess(response, textStatus, jqXHR);
                    }
                },
                
                error: function(jqXHR, textStatus, errorThrown) {
                    // Hide loading
                    if (settings.showLoading) {
                        if (settings.loadingElement) {
                            UFSCPro.loading.hideFrom(settings.loadingElement);
                        } else {
                            UFSCPro.loading.hide();
                        }
                    }
                    
                    // Show error notification
                    if (settings.showNotifications) {
                        const errorMessage = jqXHR.responseJSON?.data?.message || settings.errorMessage;
                        UFSCPro.notifications.error(errorMessage);
                    }
                    
                    // Announce to screen readers
                    UFSCPro.accessibility.announce('Erreur: ' + (jqXHR.responseJSON?.data?.message || settings.errorMessage));
                    
                    // Call original error callback
                    if (settings.originalError) {
                        settings.originalError(jqXHR, textStatus, errorThrown);
                    }
                }
            });
            
            // Store original callbacks
            if (options.success) {
                ajaxOptions.originalSuccess = options.success;
            }
            if (options.error) {
                ajaxOptions.originalError = options.error;
            }
            
            return $.ajax(ajaxOptions);
        }
    };

    // =============================================================================
    // INITIALIZATION
    // =============================================================================
    $(document).ready(function() {
        // Check if professional features are enabled
        if (typeof ufsc_frontend_pro_enabled === 'undefined' || !ufsc_frontend_pro_enabled) {
            console.log('ℹ️ UFSC Pro: Professional features disabled');
            return;
        }
        
        console.log('🚀 UFSC Pro: Initializing professional frontend enhancements...');
        
        // Initialize all modules
        UFSCPro.notifications.init();
        UFSCPro.accessibility.init();
        UFSCPro.tooltips.init();
        
        // Initialize DataTables after a short delay to ensure DOM is ready
        setTimeout(() => {
            UFSCPro.datatables.init();
        }, 100);
        
        // Enhance existing forms
        $('form').on('submit', function() {
            const $form = $(this);
            const $submitBtn = $form.find('input[type="submit"], button[type="submit"]');
            
            if ($submitBtn.length) {
                UFSCPro.loading.showOn($submitBtn, 'Envoi...');
            }
        });
        
        // Add tooltips to common elements
        $('input[type="email"]').each(function() {
            if (!$(this).attr('data-tooltip')) {
                UFSCPro.tooltips.add(this, 'Entrez une adresse email valide');
            }
        });
        
        $('input[type="tel"]').each(function() {
            if (!$(this).attr('data-tooltip')) {
                UFSCPro.tooltips.add(this, 'Entrez un numéro de téléphone valide');
            }
        });
        
        console.log('✅ UFSC Pro: All professional enhancements initialized successfully');
    });

    // =============================================================================
    // PUBLIC API
    // =============================================================================
    
    // Expose useful functions globally
    window.ufscNotify = UFSCPro.notifications;
    window.ufscLoading = UFSCPro.loading;
    window.ufscAjax = UFSCPro.ajax;

})(jQuery);