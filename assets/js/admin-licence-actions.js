/**
 * Admin Licence Actions
 * 
 * Consolidated JavaScript functions for licence management actions
 * Prevents duplication across different admin pages
 * 
 * @package UFSC_Gestion_Club
 */

// Global namespace for UFSC licence actions
window.UFSCLicenceActions = window.UFSCLicenceActions || {};

(function($) {
    'use strict';

    // Configuration object - populated by wp_localize_script
    var config = window.ufscLicenceConfig || {
        nonces: {},
        ajaxUrl: '',
        messages: {}
    };

    /**
     * Delete a licence with confirmation
     * 
     * @param {number} licenceId The licence ID to delete
     * @param {string} licenceName The licence holder name for confirmation
     */
    function deleteLicence(licenceId, licenceName) {
        if (!licenceId) {
            alert('ID de licence manquant.');
            return;
        }

        var confirmMessage = 'Êtes-vous sûr de vouloir supprimer la licence de ' + licenceName + ' ?';
        if (!confirm(confirmMessage)) {
            return;
        }

        // Show loading state
        var deleteButton = $('[data-licence-id="' + licenceId + '"] .delete-licence-btn');
        var originalText = deleteButton.text();
        deleteButton.text('Suppression...').prop('disabled', true);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ufsc_delete_licence',
                licence_id: licenceId,
                nonce: config.nonces.delete_licence
            },
            success: function(response) {
                if (response.success) {
                    // Remove the licence row with animation
                    var row = $('[data-licence-id="' + licenceId + '"]');
                    row.fadeOut(300, function() {
                        row.remove();
                        showMessage(response.data || 'Licence supprimée avec succès.', 'success');
                    });
                } else {
                    showMessage(response.data || 'Erreur lors de la suppression.', 'error');
                    deleteButton.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showMessage('Erreur de connexion.', 'error');
                deleteButton.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Change licence status
     * 
     * @param {number} licenceId The licence ID
     * @param {string} newStatus The new status (validee, refusee, en_attente)
     * @param {string} loadingText Text to show during loading
     * @param {string} reason Optional reason for status change
     */
    function changeLicenceStatus(licenceId, newStatus, loadingText, reason) {
        if (!licenceId || !newStatus) {
            alert('Paramètres manquants.');
            return;
        }

        loadingText = loadingText || 'Mise à jour...';
        reason = reason || '';

        // Show loading state
        var statusButton = $('[data-licence-id="' + licenceId + '"] .status-btn');
        var originalText = statusButton.text();
        statusButton.text(loadingText).prop('disabled', true);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ufsc_change_licence_status',
                licence_id: licenceId,
                new_status: newStatus,
                reason: reason,
                nonce: config.nonces.change_licence_status
            },
            success: function(response) {
                if (response.success) {
                    // Update the status display
                    updateStatusDisplay(licenceId, newStatus);
                    showMessage(response.data || 'Statut mis à jour avec succès.', 'success');
                } else {
                    showMessage(response.data || 'Erreur lors de la mise à jour.', 'error');
                    statusButton.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                showMessage('Erreur de connexion.', 'error');
                statusButton.text(originalText).prop('disabled', false);
            }
        });
    }

    /**
     * Validate a licence (shortcut for changing status to 'validee')
     * 
     * @param {number} licenceId The licence ID
     */
    function validateLicence(licenceId) {
        changeLicenceStatus(licenceId, 'validee', 'Validation...');
    }

    /**
     * Reject a licence with reason
     * 
     * @param {number} licenceId The licence ID
     */
    function rejectLicence(licenceId) {
        var reason = prompt('Raison du refus (optionnel):');
        if (reason !== null) { // User didn't cancel
            changeLicenceStatus(licenceId, 'refusee', 'Refus...', reason);
        }
    }

    /**
     * Update status display in the UI
     * 
     * @param {number} licenceId The licence ID
     * @param {string} newStatus The new status
     */
    function updateStatusDisplay(licenceId, newStatus) {
        var row = $('[data-licence-id="' + licenceId + '"]');
        var statusCell = row.find('.status-cell');
        
        // Remove old status classes
        statusCell.removeClass('status-validee status-refusee status-en_attente');
        
        // Add new status class
        statusCell.addClass('status-' + newStatus);
        
        // Update status text
        var statusText = getStatusText(newStatus);
        statusCell.find('.status-text').text(statusText);
        
        // Update action buttons
        updateActionButtons(licenceId, newStatus);
    }

    /**
     * Get localized status text
     * 
     * @param {string} status The status code
     * @return {string} Localized status text
     */
    function getStatusText(status) {
        var statusMap = {
            'en_attente': 'En attente',
            'validee': 'Validée',
            'refusee': 'Refusée'
        };
        return statusMap[status] || status;
    }

    /**
     * Update action buttons based on status
     * 
     * @param {number} licenceId The licence ID
     * @param {string} status The current status
     */
    function updateActionButtons(licenceId, status) {
        var row = $('[data-licence-id="' + licenceId + '"]');
        var actionsCell = row.find('.actions-cell');
        
        // Show/hide buttons based on status
        actionsCell.find('.validate-btn').toggle(status !== 'validee');
        actionsCell.find('.reject-btn').toggle(status !== 'refusee');
    }

    /**
     * Show a message to the user
     * 
     * @param {string} message The message text
     * @param {string} type The message type (success, error, warning)
     */
    function showMessage(message, type) {
        type = type || 'info';
        
        // Create message element
        var messageEl = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Insert at top of page or in designated area
        var messageContainer = $('.ufsc-messages');
        if (messageContainer.length) {
            messageContainer.html(messageEl);
        } else {
            $('.wrap h1').after(messageEl);
        }
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            messageEl.fadeOut();
        }, 5000);
    }

    /**
     * Initialize event handlers
     */
    function initializeEventHandlers() {
        // Delete licence buttons
        $(document).on('click', '.delete-licence-btn', function(e) {
            e.preventDefault();
            var licenceId = $(this).data('licence-id');
            var licenceName = $(this).data('licence-name');
            deleteLicence(licenceId, licenceName);
        });

        // Validate licence buttons
        $(document).on('click', '.validate-licence-btn', function(e) {
            e.preventDefault();
            var licenceId = $(this).data('licence-id');
            validateLicence(licenceId);
        });

        // Reject licence buttons
        $(document).on('click', '.reject-licence-btn', function(e) {
            e.preventDefault();
            var licenceId = $(this).data('licence-id');
            rejectLicence(licenceId);
        });

        // Generic status change buttons
        $(document).on('click', '.change-status-btn', function(e) {
            e.preventDefault();
            var licenceId = $(this).data('licence-id');
            var newStatus = $(this).data('new-status');
            var loadingText = $(this).data('loading-text') || 'Mise à jour...';
            changeLicenceStatus(licenceId, newStatus, loadingText);
        });
    }

    // Public API
    UFSCLicenceActions = {
        deleteLicence: deleteLicence,
        changeLicenceStatus: changeLicenceStatus,
        validateLicence: validateLicence,
        rejectLicence: rejectLicence,
        showMessage: showMessage,
        init: function(configData) {
            if (configData) {
                config = $.extend(config, configData);
            }
            initializeEventHandlers();
        }
    };

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        UFSCLicenceActions.init();
    });

})(jQuery);

    // Quick buttons in list: approve/refuse
    $(document).on('click', '.change-status-approve, .change-status-refuse', function(e){
        e.preventDefault();
        var $btn = $(this);
        var id = parseInt($btn.data('licence-id'), 10);
        var newStatus = $btn.data('new-status');
        changeLicenceStatus(id, newStatus, $btn.text());
    });
