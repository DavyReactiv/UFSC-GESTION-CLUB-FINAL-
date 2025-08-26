/**
 * UFSC Attestations Management JavaScript
 * Handles upload, replacement, and deletion of attestations
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        initAttestationHandlers();
    });

    /**
     * Initialize all attestation event handlers
     */
    function initAttestationHandlers() {
        // Club attestation handlers
        $(document).on('click', '.ufsc-upload-club-attestation', handleClubAttestationUpload);
        $(document).on('click', '.ufsc-replace-club-attestation', handleClubAttestationReplace);
        $(document).on('click', '.ufsc-delete-club-attestation', handleClubAttestationDelete);
        
        // License attestation handlers
        $(document).on('click', '.ufsc-upload-licence-attestation', handleLicenceAttestationUpload);
        $(document).on('click', '.ufsc-replace-licence-attestation', handleLicenceAttestationReplace);
        $(document).on('click', '.ufsc-delete-licence-attestation', handleLicenceAttestationDelete);
        
        // File input change handlers
        $(document).on('change', '.ufsc-attestation-file-input', handleFileInputChange);
    }

    /**
     * Handle club attestation upload
     */
    function handleClubAttestationUpload(e) {
        e.preventDefault();
        
        const button = $(this);
        const clubId = button.data('club-id');
        const type = button.data('type');
        
        // Use WordPress Media Library if available
        if (typeof wp !== 'undefined' && wp.media) {
            openMediaLibrary(clubId, type, button);
        } else {
            // Fallback to direct file upload
            createFileInput(clubId, type, button);
        }
    }

    /**
     * Handle club attestation replacement
     */
    function handleClubAttestationReplace(e) {
        e.preventDefault();
        
        if (!confirm('Voulez-vous remplacer cette attestation ?')) {
            return;
        }
        
        const button = $(this);
        const clubId = button.data('club-id');
        const type = button.data('type');
        
        // Use WordPress Media Library if available
        if (typeof wp !== 'undefined' && wp.media) {
            openMediaLibrary(clubId, type, button);
        } else {
            // Fallback to direct file upload
            createFileInput(clubId, type, button);
        }
    }

    /**
     * Handle club attestation deletion
     */
    function handleClubAttestationDelete(e) {
        e.preventDefault();
        
        if (!confirm('Voulez-vous vraiment supprimer cette attestation ?')) {
            return;
        }
        
        const button = $(this);
        const clubId = button.data('club-id');
        const type = button.data('type');
        
        deleteClubAttestation(clubId, type, button);
    }

    /**
     * Handle license attestation upload
     */
    function handleLicenceAttestationUpload(e) {
        e.preventDefault();
        
        const button = $(this);
        const licenceId = button.data('licence-id');
        
        // Create file input
        const fileInput = $('<input type="file" accept=".pdf,.jpg,.jpeg,.png" style="display:none">');
        $('body').append(fileInput);
        
        fileInput.on('change', function() {
            const file = this.files[0];
            if (file) {
                uploadLicenceAttestation(licenceId, file, button);
            }
            fileInput.remove();
        });
        
        fileInput.click();
    }

    /**
     * Handle license attestation replacement
     */
    function handleLicenceAttestationReplace(e) {
        e.preventDefault();
        
        if (!confirm('Voulez-vous remplacer cette attestation ?')) {
            return;
        }
        
        const button = $(this);
        const licenceId = button.data('licence-id');
        
        // Create file input
        const fileInput = $('<input type="file" accept=".pdf,.jpg,.jpeg,.png" style="display:none">');
        $('body').append(fileInput);
        
        fileInput.on('change', function() {
            const file = this.files[0];
            if (file) {
                uploadLicenceAttestation(licenceId, file, button);
            }
            fileInput.remove();
        });
        
        fileInput.click();
    }

    /**
     * Handle license attestation deletion
     */
    function handleLicenceAttestationDelete(e) {
        e.preventDefault();
        
        if (!confirm('Voulez-vous vraiment supprimer cette attestation ?')) {
            return;
        }
        
        const button = $(this);
        const licenceId = button.data('licence-id');
        
        deleteLicenceAttestation(licenceId, button);
    }

    /**
     * Open WordPress Media Library
     */
    function openMediaLibrary(clubId, type, button) {
        // Create media frame
        const frame = wp.media({
            title: 'Sélectionner une attestation',
            button: {
                text: 'Utiliser cette attestation'
            },
            multiple: false,
            library: {
                type: ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png']
            }
        });
        
        // When an image is selected
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            
            // Validate file type
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!allowedTypes.includes(attachment.mime)) {
                showError('Type de fichier non autorisé. Utilisez PDF, JPG, JPEG ou PNG.');
                return;
            }
            
            // Validate file size (5MB)
            const maxSize = 5 * 1024 * 1024;
            if (attachment.filesizeInBytes && attachment.filesizeInBytes > maxSize) {
                showError('Fichier trop volumineux. Taille maximale : 5MB');
                return;
            }
            
            // Use existing attachment
            attachExistingMedia(clubId, type, attachment.id, button);
        });
        
        // Open the frame
        frame.open();
    }
    
    /**
     * Create fallback file input
     */
    function createFileInput(clubId, type, button) {
        const fileInput = $('<input type="file" accept=".pdf,.jpg,.jpeg,.png" style="display:none">');
        $('body').append(fileInput);
        
        fileInput.on('change', function() {
            const file = this.files[0];
            if (file) {
                uploadClubAttestation(clubId, type, file, button);
            }
            fileInput.remove();
        });
        
        fileInput.click();
    }
    
    /**
     * Attach existing media to club
     */
    function attachExistingMedia(clubId, type, attachmentId, button) {
        // Show loading state
        const originalText = button.text();
        button.text('Association...').prop('disabled', true);
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'ufsc_attach_existing_club_attestation');
        formData.append('club_id', clubId);
        formData.append('type', type);
        formData.append('attachment_id', attachmentId);
        formData.append('nonce', ufscAttestations.uploadClubNonce);
        
        // Send AJAX request
        $.ajax({
            url: ufscAttestations.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess('Attestation associée avec succès !');
                    location.reload(); // Reload to update UI
                } else {
                    showError(response.data || 'Erreur lors de l\'association');
                }
            },
            error: function(xhr, status, error) {
                showError('Erreur de communication : ' + error);
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }
    function uploadClubAttestation(clubId, type, file, button) {
        // Validate file
        if (!validateFile(file)) {
            return;
        }
        
        // Show loading state
        const originalText = button.text();
        button.text('Téléchargement...').prop('disabled', true);
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'ufsc_upload_club_attestation');
        formData.append('club_id', clubId);
        formData.append('type', type);
        formData.append('file', file);
        formData.append('nonce', ufscAttestations.uploadClubNonce);
        
        // Send AJAX request
        $.ajax({
            url: ufscAttestations.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess('Attestation téléchargée avec succès !');
                    location.reload(); // Reload to update UI
                } else {
                    showError(response.data || 'Erreur lors du téléchargement');
                }
            },
            error: function(xhr, status, error) {
                showError('Erreur de communication : ' + error);
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Delete club attestation
     */
    function deleteClubAttestation(clubId, type, button) {
        // Show loading state
        const originalText = button.text();
        button.text('Suppression...').prop('disabled', true);
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'ufsc_delete_club_attestation');
        formData.append('club_id', clubId);
        formData.append('type', type);
        formData.append('nonce', ufscAttestations.deleteClubNonce);
        
        // Send AJAX request
        $.ajax({
            url: ufscAttestations.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess('Attestation supprimée avec succès !');
                    location.reload(); // Reload to update UI
                } else {
                    showError(response.data || 'Erreur lors de la suppression');
                }
            },
            error: function(xhr, status, error) {
                showError('Erreur de communication : ' + error);
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Upload license attestation
     */
    function uploadLicenceAttestation(licenceId, file, button) {
        // Validate file
        if (!validateFile(file)) {
            return;
        }
        
        // Show loading state
        const originalText = button.text();
        button.text('Téléchargement...').prop('disabled', true);
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'ufsc_upload_licence_attestation');
        formData.append('licence_id', licenceId);
        formData.append('file', file);
        formData.append('nonce', ufscAttestations.uploadLicenceNonce);
        
        // Send AJAX request
        $.ajax({
            url: ufscAttestations.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess('Attestation de licence téléchargée avec succès !');
                    location.reload(); // Reload to update UI
                } else {
                    showError(response.data || 'Erreur lors du téléchargement');
                }
            },
            error: function(xhr, status, error) {
                showError('Erreur de communication : ' + error);
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Delete license attestation
     */
    function deleteLicenceAttestation(licenceId, button) {
        // Show loading state
        const originalText = button.text();
        button.text('Suppression...').prop('disabled', true);
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'ufsc_delete_licence_attestation');
        formData.append('licence_id', licenceId);
        formData.append('nonce', ufscAttestations.deleteLicenceNonce);
        
        // Send AJAX request
        $.ajax({
            url: ufscAttestations.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccess('Attestation de licence supprimée avec succès !');
                    location.reload(); // Reload to update UI
                } else {
                    showError(response.data || 'Erreur lors de la suppression');
                }
            },
            error: function(xhr, status, error) {
                showError('Erreur de communication : ' + error);
            },
            complete: function() {
                button.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Validate uploaded file
     */
    function validateFile(file) {
        // Check file size (5MB max)
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (file.size > maxSize) {
            showError('Fichier trop volumineux. Taille maximale : 5MB');
            return false;
        }
        
        // Check file type
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showError('Type de fichier non autorisé. Utilisez PDF, JPG, JPEG ou PNG.');
            return false;
        }
        
        return true;
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        // Create or update admin notice
        const notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }

    /**
     * Show error message
     */
    function showError(message) {
        // Create or update admin notice
        const notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 8 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 8000);
    }

    /**
     * Handle file input change for custom styling
     */
    function handleFileInputChange(e) {
        const input = $(this);
        const file = e.target.files[0];
        const label = input.siblings('.ufsc-file-label');
        
        if (file) {
            label.text(file.name);
        } else {
            label.text('Choisir un fichier...');
        }
    }

})(jQuery);