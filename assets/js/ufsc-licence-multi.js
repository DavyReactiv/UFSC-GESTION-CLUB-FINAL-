/**
 * UFSC Multi-Licence Form Handler
 * 
 * Handles multiple licence entry, draft management, and bulk operations
 * for the improved licence product UX.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.1
 */

(function($) {
    'use strict';

    // Configuration object - to be populated by wp_localize_script
    var ufscLicenceConfig = window.ufscLicenceConfig || {
        ajaxUrl: '',
        nonces: {},
        messages: {},
        licenceProductUrl: ''
    };

    /**
     * Multi-licence form manager
     */
    var UFSCMultiLicence = {
        
        // Form state
        currentCards: [],
        nextCardId: 1,
        maxCards: 10,
        
        /**
         * Initialize the multi-licence form
         */
        init: function() {
            this.bindEvents();
            this.addNewCard(); // Start with one empty card
            this.updateGlobalActions();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Add new card button
            $(document).on('click', '.ufsc-add-licence-card', function(e) {
                e.preventDefault();
                self.addNewCard();
            });
            
            // Remove card button
            $(document).on('click', '.ufsc-remove-card', function(e) {
                e.preventDefault();
                var cardId = $(this).closest('.ufsc-licence-card').data('card-id');
                self.removeCard(cardId);
            });
            
            // Save as draft button
            $(document).on('click', '.ufsc-save-draft', function(e) {
                e.preventDefault();
                var cardId = $(this).closest('.ufsc-licence-card').data('card-id');
                self.saveDraft(cardId);
            });
            
            // Add to cart button
            $(document).on('click', '.ufsc-add-to-cart', function(e) {
                e.preventDefault();
                var cardId = $(this).closest('.ufsc-licence-card').data('card-id');
                self.addToCart(cardId);
            });
            
            // Add all drafts to cart
            $(document).on('click', '.ufsc-add-all-drafts', function(e) {
                e.preventDefault();
                self.addAllDraftsToCart();
            });
            
            // Delete draft button (from dashboard)
            $(document).on('click', '.ufsc-delete-draft', function(e) {
                e.preventDefault();
                var draftId = $(this).data('draft-id');
                self.deleteDraft(draftId);
            });
            
            // Add draft to cart (from dashboard)
            $(document).on('click', '.ufsc-add-draft-to-cart', function(e) {
                e.preventDefault();
                var draftId = $(this).data('draft-id');
                self.addDraftToCart(draftId);
            });
            
            // Form field validation
            $(document).on('blur', '.ufsc-licence-card input, .ufsc-licence-card select', function() {
                self.validateCard($(this).closest('.ufsc-licence-card').data('card-id'));
            });
        },
        
        /**
         * Add a new licence card
         */
        addNewCard: function() {
            if (this.currentCards.length >= this.maxCards) {
                this.showMessage('Nombre maximum de licences atteint (' + this.maxCards + ').', 'warning');
                return;
            }
            
            var cardId = this.nextCardId++;
            var cardHtml = this.generateCardHtml(cardId);
            
            $('.ufsc-licence-cards-container').append(cardHtml);
            this.currentCards.push(cardId);
            this.updateGlobalActions();
            
            // Focus on first input
            $('.ufsc-licence-card[data-card-id="' + cardId + '"] input[name="nom"]').focus();
        },
        
        /**
         * Remove a licence card
         */
        removeCard: function(cardId) {
            if (this.currentCards.length <= 1) {
                this.showMessage('Vous devez garder au moins une licence.', 'warning');
                return;
            }
            
            $('.ufsc-licence-card[data-card-id="' + cardId + '"]').fadeOut(300, function() {
                $(this).remove();
            });
            
            this.currentCards = this.currentCards.filter(function(id) {
                return id !== cardId;
            });
            
            this.updateGlobalActions();
        },
        
        /**
         * Generate HTML for a licence card
         */
        generateCardHtml: function(cardId) {
            var isFirst = this.currentCards.length === 0;
            
            return `
                <div class="ufsc-licence-card ufsc-fade-in" data-card-id="${cardId}">
                    <div class="ufsc-licence-card-header">
                        <h3 class="ufsc-licence-card-title">Licence #${cardId}</h3>
                        <div class="ufsc-licence-card-actions">
                            ${!isFirst ? '<button type="button" class="ufsc-card-action-btn ufsc-remove-card ufsc-btn-danger" title="Supprimer"><i class="dashicons dashicons-trash"></i></button>' : ''}
                        </div>
                    </div>
                    
                    <div class="ufsc-licence-form-grid">
                        <div class="ufsc-licence-form-field required">
                            <label for="nom_${cardId}">Nom</label>
                            <input type="text" id="nom_${cardId}" name="nom" required maxlength="100">
                        </div>
                        
                        <div class="ufsc-licence-form-field required">
                            <label for="prenom_${cardId}">Prénom</label>
                            <input type="text" id="prenom_${cardId}" name="prenom" required maxlength="100">
                        </div>
                        
                        <div class="ufsc-licence-form-field required">
                            <label for="date_naissance_${cardId}">Date de naissance</label>
                            <input type="date" id="date_naissance_${cardId}" name="date_naissance" required>
                        </div>
                        
                        <div class="ufsc-licence-form-field required">
                            <label for="sexe_${cardId}">Sexe</label>
                            <select id="sexe_${cardId}" name="sexe" required>
                                <option value="">Sélectionner...</option>
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        
                        <div class="ufsc-licence-form-field required">
                            <label for="email_${cardId}">Email</label>
                            <input type="email" id="email_${cardId}" name="email" required maxlength="150">
                        </div>
                        
                        <div class="ufsc-licence-form-field required">
                            <label for="adresse_${cardId}">Adresse</label>
                            <input type="text" id="adresse_${cardId}" name="adresse" required maxlength="200">
                        </div>
                        
                        <div class="ufsc-licence-form-field required">
                            <label for="code_postal_${cardId}">Code postal</label>
                            <input type="text" id="code_postal_${cardId}" name="code_postal" required pattern="[0-9]{5}" maxlength="5">
                        </div>
                        
                        <div class="ufsc-licence-form-field required">
                            <label for="ville_${cardId}">Ville</label>
                            <input type="text" id="ville_${cardId}" name="ville" required maxlength="100">
                        </div>
                        
                        <div class="ufsc-licence-form-field">
                            <label for="region_${cardId}">Région UFSC</label>
                            <select id="region_${cardId}" name="region">
                                <option value="">Sélectionner...</option>
                                <option value="Nord">Nord</option>
                                <option value="Sud">Sud</option>
                                <option value="Est">Est</option>
                                <option value="Ouest">Ouest</option>
                                <option value="Centre">Centre</option>
                            </select>
                        </div>
                        
                        <div class="ufsc-licence-form-field">
                            <label for="telephone_${cardId}">Téléphone</label>
                            <input type="tel" id="telephone_${cardId}" name="telephone" maxlength="20">
                        </div>
                        
                        <div class="ufsc-licence-form-field">
                            <label for="profession_${cardId}">Profession</label>
                            <input type="text" id="profession_${cardId}" name="profession" maxlength="100">
                        </div>
                        
                        <div class="ufsc-licence-form-field">
                            <label for="niveau_pratique_${cardId}">Niveau de pratique</label>
                            <select id="niveau_pratique_${cardId}" name="niveau_pratique">
                                <option value="">Sélectionner...</option>
                                <option value="Débutant">Débutant</option>
                                <option value="Intermédiaire">Intermédiaire</option>
                                <option value="Avancé">Avancé</option>
                                <option value="Expert">Expert</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ufsc-licence-card-bottom">
                        <span class="ufsc-card-status draft">Brouillon</span>
                        <div class="ufsc-card-actions">
                            <button type="button" class="ufsc-btn ufsc-btn-secondary ufsc-save-draft">
                                <i class="dashicons dashicons-saved"></i> Enregistrer brouillon
                            </button>
                            <button type="button" class="ufsc-btn ufsc-btn-primary ufsc-add-to-cart">
                                <i class="dashicons dashicons-cart"></i> Ajouter au panier
                            </button>
                        </div>
                    </div>
                </div>
            `;
        },
        
        /**
         * Validate a licence card
         */
        validateCard: function(cardId) {
            var $card = $('.ufsc-licence-card[data-card-id="' + cardId + '"]');
            var isValid = true;
            var data = this.getCardData(cardId);
            
            // Basic validation
            if (!data.nom || !data.prenom || !data.date_naissance || !data.sexe || !data.email) {
                isValid = false;
            }
            
            // Email validation
            if (data.email && !this.isValidEmail(data.email)) {
                isValid = false;
            }
            
            // Update card status
            var $status = $card.find('.ufsc-card-status');
            if (isValid) {
                $status.removeClass('draft').addClass('ready').text('Prêt');
            } else {
                $status.removeClass('ready').addClass('draft').text('Brouillon');
            }
            
            return isValid;
        },
        
        /**
         * Get data from a licence card
         */
        getCardData: function(cardId) {
            var $card = $('.ufsc-licence-card[data-card-id="' + cardId + '"]');
            var data = {};
            
            $card.find('input, select').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    data[name] = $(this).val();
                }
            });
            
            return data;
        },
        
        /**
         * Save licence as draft
         */
        saveDraft: function(cardId) {
            var data = this.getCardData(cardId);
            var self = this;
            
            if (!data.nom || !data.prenom || !data.date_naissance) {
                this.showMessage('Nom, prénom et date de naissance sont obligatoires pour sauvegarder un brouillon.', 'error');
                return;
            }
            
            // Show loading
            var $btn = $('.ufsc-licence-card[data-card-id="' + cardId + '"] .ufsc-save-draft');
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="ufsc-loading"></span> Sauvegarde...');
            
            $.ajax({
                url: ufscLicenceConfig.ajaxUrl,
                type: 'POST',
                data: $.extend(data, {
                    action: 'ufsc_save_licence_draft',
                    ufsc_nonce: ufscLicenceConfig.nonces.licence_draft
                }),
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        self.updateDraftCount(response.data.draft_count);
                        
                        // Clear form if successful
                        $('.ufsc-licence-card[data-card-id="' + cardId + '"] input, .ufsc-licence-card[data-card-id="' + cardId + '"] select').val('');
                        self.validateCard(cardId);
                    } else {
                        self.showMessage(response.data.message || 'Erreur lors de la sauvegarde du brouillon.', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },
        
        /**
         * Add licence to cart
         */
        addToCart: function(cardId) {
            var data = this.getCardData(cardId);
            var self = this;
            
            if (!this.validateCard(cardId)) {
                this.showMessage('Veuillez compléter tous les champs obligatoires.', 'error');
                return;
            }
            
            // Show loading
            var $btn = $('.ufsc-licence-card[data-card-id="' + cardId + '"] .ufsc-add-to-cart');
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="ufsc-loading"></span> Ajout...');
            
            $.ajax({
                url: ufscLicenceConfig.ajaxUrl,
                type: 'POST',
                data: $.extend(data, {
                    action: 'ufsc_add_licencie_to_cart',
                    ufsc_nonce: ufscLicenceConfig.nonces.add_licencie
                }),
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        
                        // Clear form if successful
                        $('.ufsc-licence-card[data-card-id="' + cardId + '"] input, .ufsc-licence-card[data-card-id="' + cardId + '"] select').val('');
                        self.validateCard(cardId);
                        
                        // Show cart option
                        if (response.data.cart_url) {
                            setTimeout(function() {
                                if (confirm('Licence ajoutée au panier ! Voulez-vous voir le panier maintenant ?')) {
                                    window.location.href = response.data.cart_url;
                                }
                            }, 1000);
                        }
                    } else {
                        self.showMessage(response.data.message || 'Erreur lors de l\'ajout au panier.', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },
        
        /**
         * Add all drafts to cart
         */
        addAllDraftsToCart: function() {
            var self = this;
            
            if (!confirm('Êtes-vous sûr de vouloir ajouter tous les brouillons au panier ?')) {
                return;
            }
            
            // Show loading
            var $btn = $('.ufsc-add-all-drafts');
            var originalText = $btn.html();
            $btn.prop('disabled', true).html('<span class="ufsc-loading"></span> Ajout en cours...');
            
            $.ajax({
                url: ufscLicenceConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ufsc_add_all_drafts_to_cart',
                    ufsc_nonce: ufscLicenceConfig.nonces.licence_draft
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        self.updateDraftCount(0);
                        
                        // Redirect to cart
                        if (response.data.cart_url) {
                            setTimeout(function() {
                                window.location.href = response.data.cart_url;
                            }, 1500);
                        }
                    } else {
                        self.showMessage(response.data.message || 'Erreur lors de l\'ajout au panier.', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },
        
        /**
         * Delete a draft
         */
        deleteDraft: function(draftId) {
            var self = this;
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce brouillon ?')) {
                return;
            }
            
            $.ajax({
                url: ufscLicenceConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ufsc_delete_licence_draft',
                    draft_id: draftId,
                    ufsc_nonce: ufscLicenceConfig.nonces.licence_draft
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        $('.ufsc-draft-item[data-draft-id="' + draftId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                        self.updateDraftCount(response.data.draft_count);
                    } else {
                        self.showMessage(response.data.message || 'Erreur lors de la suppression.', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
                }
            });
        },
        
        /**
         * Add a single draft to cart
         */
        addDraftToCart: function(draftId) {
            var self = this;
            
            $.ajax({
                url: ufscLicenceConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ufsc_add_draft_to_cart',
                    draft_id: draftId,
                    ufsc_nonce: ufscLicenceConfig.nonces.licence_draft
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        $('.ufsc-draft-item[data-draft-id="' + draftId + '"]').fadeOut(300, function() {
                            $(this).remove();
                        });
                        self.updateDraftCount(response.data.draft_count);
                        
                        // Option to view cart
                        if (response.data.cart_url) {
                            setTimeout(function() {
                                if (confirm('Licence ajoutée au panier ! Voulez-vous voir le panier maintenant ?')) {
                                    window.location.href = response.data.cart_url;
                                }
                            }, 1000);
                        }
                    } else {
                        self.showMessage(response.data.message || 'Erreur lors de l\'ajout au panier.', 'error');
                    }
                },
                error: function() {
                    self.showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
                }
            });
        },
        
        /**
         * Update global actions based on current state
         */
        updateGlobalActions: function() {
            var canAddMore = this.currentCards.length < this.maxCards;
            $('.ufsc-add-licence-card').prop('disabled', !canAddMore);
            
            var countText = this.currentCards.length + '/' + this.maxCards + ' licences';
            $('.ufsc-card-count').text(countText);
        },
        
        /**
         * Update draft count display
         */
        updateDraftCount: function(count) {
            $('.ufsc-draft-count').text(count);
            
            if (count === 0) {
                $('.ufsc-add-all-drafts').prop('disabled', true);
                $('.ufsc-drafts-section').hide();
            } else {
                $('.ufsc-add-all-drafts').prop('disabled', false);
                $('.ufsc-drafts-section').show();
            }
        },
        
        /**
         * Show feedback message
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            var $message = $('<div class="ufsc-feedback-message ' + type + ' ufsc-fade-in">' + message + '</div>');
            
            // Remove existing messages
            $('.ufsc-feedback-message').fadeOut(200, function() {
                $(this).remove();
            });
            
            // Add new message
            $('.ufsc-licence-multi-container').prepend($message);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        /**
         * Email validation
         */
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }
    };
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize if we're on a page with the multi-licence container
        if ($('.ufsc-licence-multi-container').length) {
            UFSCMultiLicence.init();
        }
        
        console.log('UFSC Multi-Licence JavaScript initialized');
    });
    
    // Expose to global scope for external access
    window.UFSCMultiLicence = UFSCMultiLicence;

})(jQuery);