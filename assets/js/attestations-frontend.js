/**
 * Frontend JavaScript for UFSC Attestation Uploads
 * 
 * @package UFSC_Gestion_Club
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        initAttestationUpload();
    });

    /**
     * Initialize attestation upload functionality
     */
    function initAttestationUpload() {
        $('.ufsc-attestation-form').on('submit', handleFormSubmit);
        $('.ufsc-attestation-upload-form input[type="file"]').on('change', validateFileSelection);
    }

    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $container = $form.closest('.ufsc-attestation-upload-form');
        const type = $container.data('type');
        const $fileInput = $form.find('input[type="file"]');
        const $submitBtn = $form.find('button[type="submit"]');
        const $feedback = $form.find('.ufsc-upload-feedback');
        
        // Validate file selection
        if (!$fileInput[0].files.length) {
            showFeedback($feedback, 'error', ufscAttestationsFrontend.messages.no_file);
            return;
        }
        
        const file = $fileInput[0].files[0];
        
        // Client-side validation
        const validation = validateFile(file);
        if (!validation.valid) {
            showFeedback($feedback, 'error', validation.message);
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'ufsc_upload_attestation');
        formData.append('type', type);
        formData.append('attestation_file', file);
        formData.append('ufsc_nonce', ufscAttestationsFrontend.nonce);
        
        // Update UI for loading state
        setLoadingState($submitBtn, true);
        showFeedback($feedback, 'info', ufscAttestationsFrontend.messages.uploading);
        
        // Send AJAX request
        $.ajax({
            url: ufscAttestationsFrontend.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000, // 30 seconds timeout
            success: function(response) {
                if (response.success) {
                    showFeedback($feedback, 'success', response.data.message);
                    $form[0].reset(); // Clear the form
                    
                    // Trigger custom event for extensibility
                    $(document).trigger('ufsc:attestation:uploaded', {
                        type: response.data.type,
                        url: response.data.url,
                        uploaded_at: response.data.uploaded_at
                    });
                    
                    // Optionally reload the page after a delay to update any lists
                    setTimeout(function() {
                        if ($('.ufsc-attestation-list').length > 0) {
                            location.reload();
                        }
                    }, 2000);
                } else {
                    showFeedback($feedback, 'error', response.data.message || ufscAttestationsFrontend.messages.error);
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = ufscAttestationsFrontend.messages.error;
                
                if (status === 'timeout') {
                    errorMessage = 'Le téléchargement a pris trop de temps. Veuillez réessayer.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (status === 'error' && xhr.status !== 0) {
                    errorMessage = 'Erreur serveur (' + xhr.status + '). Veuillez réessayer.';
                }
                
                showFeedback($feedback, 'error', errorMessage);
                console.error('UFSC Attestation Upload Error:', error, xhr);
            },
            complete: function() {
                setLoadingState($submitBtn, false);
            }
        });
    }

    /**
     * Validate file selection on change
     */
    function validateFileSelection(e) {
        const file = e.target.files[0];
        const $container = $(this).closest('.ufsc-attestation-upload-form');
        const $feedback = $container.find('.ufsc-upload-feedback');
        
        if (!file) {
            hideFeedback($feedback);
            return;
        }
        
        const validation = validateFile(file);
        if (!validation.valid) {
            showFeedback($feedback, 'error', validation.message);
            $(this).val(''); // Clear invalid selection
        } else {
            hideFeedback($feedback);
        }
    }

    /**
     * Validate file client-side
     */
    function validateFile(file) {
        // Check file type
        if (file.type !== 'application/pdf') {
            return {
                valid: false,
                message: ufscAttestationsFrontend.messages.invalid_file
            };
        }
        
        // Check file size (5MB max)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            return {
                valid: false,
                message: ufscAttestationsFrontend.messages.file_too_large
            };
        }
        
        // Check file name for basic security
        const fileName = file.name.toLowerCase();
        if (fileName.includes('<') || fileName.includes('>') || fileName.includes('"') || fileName.includes("'")) {
            return {
                valid: false,
                message: 'Le nom du fichier contient des caractères non autorisés.'
            };
        }
        
        return { valid: true };
    }

    /**
     * Show feedback message
     */
    function showFeedback($element, type, message) {
        const iconClass = type === 'success' ? 'dashicons-yes-alt' : 
                         type === 'error' ? 'dashicons-warning' : 'dashicons-info';
        
        $element.removeClass('ufsc-feedback-success ufsc-feedback-error ufsc-feedback-info')
               .addClass('ufsc-feedback-' + type)
               .html('<i class="dashicons ' + iconClass + '"></i> ' + message)
               .show();
    }

    /**
     * Hide feedback message
     */
    function hideFeedback($element) {
        $element.hide().empty();
    }

    /**
     * Set loading state for submit button
     */
    function setLoadingState($button, loading) {
        const $btnText = $button.find('.ufsc-btn-text');
        const $btnLoading = $button.find('.ufsc-btn-loading');
        
        if (loading) {
            $button.prop('disabled', true).addClass('ufsc-btn-loading-state');
            $btnText.hide();
            $btnLoading.show();
        } else {
            $button.prop('disabled', false).removeClass('ufsc-btn-loading-state');
            $btnText.show();
            $btnLoading.hide();
        }
    }

    /**
     * Format file size for display
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Expose functions globally for extensibility
    window.UFSCAttestations = {
        validateFile: validateFile,
        formatFileSize: formatFileSize,
        showFeedback: showFeedback,
        hideFeedback: hideFeedback
    };

})(jQuery);