/**
 * Enhanced Club Affiliation Form JavaScript
 * Features: Progress tracking, real-time validation, tooltips, loading states
 */

(function($) {
    'use strict';

    // Configuration
    const config = {
        progressSteps: [
            { id: 'general', label: 'Informations générales', section: '.ufsc-form-section:nth-child(1)' },
            { id: 'legal', label: 'Informations légales', section: '.ufsc-form-section:nth-child(2)' },
            { id: 'managers', label: 'Dirigeants', section: '.ufsc-form-section:nth-child(3)' },
            { id: 'documents', label: 'Documents', section: '.ufsc-form-section:nth-child(4)' }
        ],
        validationRules: {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            postal: /^[0-9]{5}$/,
            phone: /^(?:(?:\+33|0)[1-9](?:[.-\s]?\d{2}){4})$/,
            siren: /^[0-9]{9}$/,
            required: /^.+$/ // At least one character
        },
        tooltips: {
            'nom': 'Nom complet et officiel de votre club tel qu\'il apparaît dans vos statuts',
            'email': 'Adresse email principale qui sera utilisée pour toutes les communications officielles',
            'telephone': 'Numéro de téléphone principal du club (format: 01 23 45 67 89)',
            'code_postal': 'Code postal de l\'adresse officielle du club (5 chiffres)',
            'siren': 'Numéro SIREN de votre association (9 chiffres, disponible sur votre récépissé de déclaration)',
            'num_declaration': 'Numéro de déclaration en préfecture (commence généralement par W)',
            'statuts': 'Statuts de l\'association signés et datés (format PDF recommandé)',
            'recepisse': 'Récépissé de déclaration délivré par la préfecture',
            'cer': 'Contrat d\'engagement républicain signé par le représentant légal'
        }
    };

    // Main form enhancement object
    const UFSCFormEnhancer = {
        
        // Initialize all enhancements
        init: function() {
            if (!this.isClubForm()) return;
            
            console.log('🚀 UFSC Form Enhancer: Initializing...');
            
            this.createProgressBar();
            this.setupValidation();
            this.setupTooltips();
            this.setupLoadingStates();
            this.trackProgress();
            this.bindEvents();
            
            console.log('✅ UFSC Form Enhancer: All enhancements loaded');
        },

        // Check if we're on a club form page
        isClubForm: function() {
            return $('.ufsc-form').length > 0 && $('.ufsc-form-section').length >= 4;
        },

        // Create and insert progress bar
        createProgressBar: function() {
            const progressHTML = `
                <div class="ufsc-form-progress" role="progressbar" aria-label="Progression du formulaire">
                    <div class="ufsc-progress-header">
                        <i class="dashicons dashicons-chart-line"></i>
                        Progression du formulaire
                    </div>
                    <div class="ufsc-progress-steps">
                        <div class="ufsc-progress-line">
                            <div class="ufsc-progress-line-fill"></div>
                        </div>
                        ${config.progressSteps.map((step, index) => `
                            <div class="ufsc-progress-step" data-step="${step.id}">
                                <div class="ufsc-progress-step-number">${index + 1}</div>
                                <div class="ufsc-progress-step-label">${step.label}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            $('.ufsc-form').before(progressHTML);
        },

        // Setup real-time validation
        setupValidation: function() {
            const self = this;
            
            // Email validation
            this.setupFieldValidation('input[type="email"]', 'email', 'Adresse email valide', 'Format d\'email invalide');
            
            // Postal code validation
            this.setupFieldValidation('input[name="code_postal"]', 'postal', 'Code postal valide', 'Format: 5 chiffres');
            
            // Phone validation
            this.setupFieldValidation('input[type="tel"], input[name="telephone"]', 'phone', 'Numéro valide', 'Format: 01 23 45 67 89');
            
            // SIREN validation
            this.setupFieldValidation('input[name="siren"]', 'siren', 'SIREN valide', 'Format: 9 chiffres');
            
            // Dirigeants phone validation  
            this.setupFieldValidation('input[name$="_tel"]', 'phone', 'Téléphone valide', 'Format: 01 23 45 67 89');
            
            // Dirigeants email validation
            this.setupFieldValidation('input[name$="_email"]', 'email', 'Email valide', 'Format d\'email invalide');
            
            // Dirigeants nom validation
            this.setupFieldValidation('input[name$="_nom"]', 'required', 'Nom renseigné', 'Le nom est obligatoire');
            
            // Dirigeants prenom validation
            this.setupFieldValidation('input[name$="_prenom"]', 'required', 'Prénom renseigné', 'Le prénom est obligatoire');
        },

        // Setup validation for specific field type
        setupFieldValidation: function(selector, rule, validMsg, invalidMsg) {
            const self = this;
            
            $(document).on('input blur', selector, function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    self.clearValidation($field);
                    return;
                }
                
                const isValid = self.validateField(value, rule);
                self.showValidation($field, isValid, validMsg, invalidMsg);
            });
        },

        // Validate field value against rule
        validateField: function(value, rule) {
            if (rule === 'phone') {
                // Clean phone number for validation
                const cleanPhone = value.replace(/[\s.-]/g, '');
                return config.validationRules.phone.test(cleanPhone);
            }
            
            return config.validationRules[rule] ? config.validationRules[rule].test(value) : false;
        },

        // Show validation feedback
        showValidation: function($field, isValid, validMsg, invalidMsg) {
            const $container = $field.closest('.ufsc-form-row > div');
            
            // Remove existing validation
            $container.find('.ufsc-validation-icon, .ufsc-validation-message').remove();
            $field.removeClass('valid invalid');
            
            // Add validation wrapper if not exists
            if (!$field.parent().hasClass('ufsc-field-validation')) {
                $field.wrap('<div class="ufsc-field-validation"></div>');
            }
            
            const $wrapper = $field.parent();
            $wrapper.removeClass('valid invalid');
            
            if (isValid) {
                $field.addClass('valid');
                $wrapper.addClass('valid');
                $wrapper.append('<span class="ufsc-validation-icon valid">✓</span>');
                $wrapper.after(`<div class="ufsc-validation-message valid show">${validMsg}</div>`);
            } else {
                $field.addClass('invalid');
                $wrapper.addClass('invalid');
                $wrapper.append('<span class="ufsc-validation-icon invalid">✗</span>');
                $wrapper.after(`<div class="ufsc-validation-message invalid show">${invalidMsg}</div>`);
            }
        },

        // Clear validation feedback
        clearValidation: function($field) {
            const $container = $field.closest('.ufsc-form-row > div');
            $container.find('.ufsc-validation-icon, .ufsc-validation-message').remove();
            $field.removeClass('valid invalid');
            
            if ($field.parent().hasClass('ufsc-field-validation')) {
                $field.parent().removeClass('valid invalid');
            }
        },

        // Setup tooltips
        setupTooltips: function() {
            const self = this;
            
            Object.keys(config.tooltips).forEach(fieldName => {
                const $field = $(`input[name="${fieldName}"], select[name="${fieldName}"]`);
                if ($field.length) {
                    const $label = $field.closest('.ufsc-form-row').find('label');
                    if ($label.length) {
                        const tooltipHTML = `
                            <span class="ufsc-tooltip-trigger" tabindex="0" role="button" aria-label="Aide pour ce champ">
                                <span class="ufsc-tooltip-icon">?</span>
                                <div class="ufsc-tooltip-content" role="tooltip">
                                    ${config.tooltips[fieldName]}
                                </div>
                            </span>
                        `;
                        $label.append(tooltipHTML);
                    }
                }
            });
        },

        // Setup loading states
        setupLoadingStates: function() {
            const self = this;
            
            // File upload loaders
            $('input[type="file"]').on('change', function() {
                const $input = $(this);
                const $container = $input.closest('.ufsc-form-row > div');
                
                if (this.files && this.files.length > 0) {
                    const file = this.files[0];
                    
                    // Remove existing loader
                    $container.find('.ufsc-upload-loader').remove();
                    
                    // Add upload loader
                    const loaderHTML = `
                        <div class="ufsc-upload-loader active">
                            <div class="ufsc-loader">
                                <div class="ufsc-loader-spinner"></div>
                            </div>
                            <span>Validation du fichier "${file.name}"...</span>
                        </div>
                    `;
                    $container.append(loaderHTML);
                    
                    // Simulate file validation delay
                    setTimeout(() => {
                        $container.find('.ufsc-upload-loader').removeClass('active').fadeOut();
                        self.showFileSuccess($container, file.name);
                    }, 1500);
                }
            });
            
            // Integrate with AJAX form submission from UFSCDataSync
            $(document).on('ufsc:club:saved', function(event, data) {
                if (data.upload_results) {
                    self.handleUploadResults(data.upload_results);
                }
            });
        },

        // Handle upload results from AJAX response
        handleUploadResults: function(uploadResults) {
            Object.keys(uploadResults).forEach(docType => {
                const result = uploadResults[docType];
                const $fileInput = $(`input[name="${docType}"]`);
                const $container = $fileInput.closest('.ufsc-form-row > div');
                
                if (result.success) {
                    this.showFileSuccess($container, result.filename);
                } else {
                    this.showFileError($container, result.error);
                }
            });
        },

        // Show file upload error
        showFileError: function($container, errorMessage) {
            const errorHTML = `
                <div class="ufsc-validation-message invalid show">
                    <i class="dashicons dashicons-warning"></i>
                    ${errorMessage}
                </div>
            `;
            $container.append(errorHTML);
        },

        // Show file upload success
        showFileSuccess: function($container, fileName) {
            const successHTML = `
                <div class="ufsc-validation-message valid show">
                    <i class="dashicons dashicons-yes-alt"></i>
                    Fichier "${fileName}" prêt pour l'envoi
                </div>
            `;
            $container.append(successHTML);
        },

        // Show submission loader
        showSubmissionLoader: function() {
            const loaderHTML = `
                <div class="ufsc-form-loading-overlay active">
                    <div class="ufsc-form-loading-content">
                        <div class="ufsc-hourglass"></div>
                        <h3>Envoi en cours...</h3>
                        <p>Veuillez patienter pendant le traitement de votre demande</p>
                    </div>
                </div>
            `;
            $('body').append(loaderHTML);
        },

        // Validate entire form
        validateForm: function($form) {
            let isValid = true;
            
            // Check required fields
            $form.find('input[required], select[required]').each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                }
            });
            
            // Check validation states
            $form.find('.invalid').each(function() {
                isValid = false;
            });
            
            return isValid;
        },

        // Track progress through form sections
        trackProgress: function() {
            const self = this;
            
            // Update progress on scroll
            $(window).on('scroll', function() {
                self.updateProgress();
            });
            
            // Update progress on input
            $('.ufsc-form').on('input change', function() {
                setTimeout(() => self.updateProgress(), 100);
            });
            
            // Initial progress update
            setTimeout(() => self.updateProgress(), 500);
        },

        // Update progress bar
        updateProgress: function() {
            const $steps = $('.ufsc-progress-step');
            const $progressFill = $('.ufsc-progress-line-fill');
            
            let currentStep = 0;
            let completedSteps = 0;
            
            config.progressSteps.forEach((step, index) => {
                const $section = $(step.section);
                const $stepEl = $(`.ufsc-progress-step[data-step="${step.id}"]`);
                
                if ($section.length) {
                    const sectionTop = $section.offset().top;
                    const sectionBottom = sectionTop + $section.height();
                    const scrollTop = $(window).scrollTop() + 200; // Offset for header
                    
                    // Check if section is in viewport
                    if (scrollTop >= sectionTop && scrollTop <= sectionBottom) {
                        currentStep = index;
                    }
                    
                    // Check if section is completed (has valid inputs)
                    const requiredFields = $section.find('input[required], select[required]').length;
                    const filledFields = $section.find('input[required], select[required]').filter(function() {
                        return $(this).val().trim() !== '';
                    }).length;
                    
                    if (requiredFields > 0 && filledFields === requiredFields) {
                        $stepEl.addClass('completed').removeClass('active');
                        completedSteps++;
                    } else if (index === currentStep) {
                        $stepEl.addClass('active').removeClass('completed');
                    } else {
                        $stepEl.removeClass('active completed');
                    }
                }
            });
            
            // Update progress line
            const progressPercent = (completedSteps / config.progressSteps.length) * 100;
            $progressFill.css('width', progressPercent + '%');
        },

        // Bind additional events
        bindEvents: function() {
            const self = this;
            
            // Smooth scroll to sections when clicking progress steps
            $('.ufsc-progress-step').on('click', function() {
                const stepId = $(this).data('step');
                const step = config.progressSteps.find(s => s.id === stepId);
                
                if (step && $(step.section).length) {
                    $('html, body').animate({
                        scrollTop: $(step.section).offset().top - 100
                    }, 500);
                }
            });
            
            // Enhance form submission success
            $('.ufsc-form').on('submit', function() {
                const $form = $(this);
                
                // If we detect a successful submission (no errors), show success animation
                setTimeout(() => {
                    if ($('.ufsc-alert-success').length && !$('.ufsc-success-animation').length) {
                        self.showSuccessAnimation();
                    }
                }, 1000);
            });
            
            // Keyboard accessibility for tooltips
            $(document).on('keydown', '.ufsc-tooltip-trigger', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).focus();
                }
            });
        },

        // Show success animation
        showSuccessAnimation: function() {
            const successHTML = `
                <div class="ufsc-success-animation">
                    <div class="ufsc-success-icon"></div>
                    <h3 class="ufsc-success-title">Demande envoyée avec succès !</h3>
                    <p class="ufsc-success-message">
                        Votre demande d'affiliation a été transmise avec succès. 
                        Vous recevrez une confirmation par email sous 24h.
                    </p>
                    <div class="ufsc-success-actions">
                        <a href="#" class="ufsc-btn ufsc-btn-primary" onclick="window.location.reload()">
                            Nouvelle demande
                        </a>
                    </div>
                </div>
            `;
            
            $('.ufsc-alert-success').replaceWith(successHTML);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        UFSCFormEnhancer.init();
    });

    // Also handle AJAX-loaded content
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).find('.ufsc-form').length) {
            setTimeout(() => UFSCFormEnhancer.init(), 100);
        }
    });

    // Expose to global scope for debugging
    window.UFSCFormEnhancer = UFSCFormEnhancer;

})(jQuery);