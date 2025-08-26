<?php

/**
 * Shortcode pour ajouter un licencié avec intégration WooCommerce
 * 
 * NOUVEAU : Utilise AJAX pour ajouter les licenciés au panier WooCommerce
 * au lieu de les créer directement. Les licences sont créées seulement
 * après paiement confirmé.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode [ufsc_ajouter_licencie]
 * Formulaire AJAX pour ajouter un licencié au panier WooCommerce
 */
function ufsc_ajouter_licencie_shortcode($atts)
{
    // CORRECTION: Use standardized frontend access control
    $access_check = ufsc_check_frontend_access('licence');
    
    if (!$access_check['allowed']) {
        return $access_check['error_message'];
    }
    
    $club = $access_check['club'];

    // CORRECTION: Use standardized status checking
    if (!ufsc_is_club_active($club)) {
        return ufsc_render_club_status_alert($club, 'licence');
    }

    // Enqueue AJAX script and styles
    ufsc_enqueue_add_licencie_assets();

    return ufsc_render_ajax_licensee_form($club);
}

/**
 * Render the AJAX-enabled licensee form
 */
function ufsc_render_ajax_licensee_form($club)
{
    ob_start();
    ?>
    <div class="ufsc-container">
        <h2 class="ufsc-section-title">Ajouter un licencié</h2>
        
        <!-- Quota information -->
        <?php echo ufsc_render_quota_information($club); ?>
        
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3>Informations du licencié</h3>
                <p>Les informations saisies seront ajoutées au panier pour finaliser l'achat de la licence.</p>
            </div>
            <div class="ufsc-card-body">
                <form id="ufsc-add-licencie-form" class="ufsc-form">
                    
                    <div class="ufsc-form-row ufsc-form-group">
                    <div class="ufsc-form-row ufsc-form-group">
                        <div class="ufsc-form-col">
                            <label for="role">Rôle <span class="ufsc-form-required">*</span></label>
                            <select name="role" id="role" required>
                                <option value="adherent">Adhérent</option>
                                <option value="president">Président</option>
                                <option value="tresorier">Trésorier</option>
                                <option value="secretaire">Secrétaire</option>
                                <option value="entraineur">Entraîneur</option>
                            </select>
                        </div>
                    </div>
    
                        <div class="ufsc-form-col">
                            <label for="prenom">Prénom <span class="ufsc-form-required">*</span></label>
                            <input type="text" name="prenom" id="prenom" required maxlength="100">
                        </div>
                        <div class="ufsc-form-col">
                            <label for="nom">Nom <span class="ufsc-form-required">*</span></label>
                            <input type="text" name="nom" id="nom" required maxlength="100">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row ufsc-form-group">
                        <div class="ufsc-form-col">
                            <label for="date_naissance">Date de naissance <span class="ufsc-form-required">*</span></label>
                            <input type="date" name="date_naissance" id="date_naissance" required>
                        </div>
                        <div class="ufsc-form-col">
                            <label for="lieu_naissance">Lieu de naissance</label>
                            <input type="text" name="lieu_naissance" id="lieu_naissance" maxlength="100">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row ufsc-form-group">
                        <div class="ufsc-form-col">
                            <label for="email">Email <span class="ufsc-form-required">*</span></label>
                            <input type="email" name="email" id="email" required maxlength="100">
                        </div>
                        <div class="ufsc-form-col">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" name="telephone" id="telephone" maxlength="20">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="adresse">Adresse</label>
                        <textarea name="adresse" id="adresse" rows="3" maxlength="255"></textarea>
                    </div>
                    
                    <div class="ufsc-form-row ufsc-form-group">
                        <div class="ufsc-form-col">
                            <label for="ville">Ville</label>
                            <input type="text" name="ville" id="ville" maxlength="100">
                        </div>
                        <div class="ufsc-form-col">
                            <label for="code_postal">Code postal</label>
                            <input type="text" name="code_postal" id="code_postal" pattern="[0-9]{5}" maxlength="5" placeholder="12345">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-submit">
                        <button type="submit" class="ufsc-btn ufsc-btn-primary">
                            <i class="dashicons dashicons-cart"></i> Ajouter au panier
                        </button>
                        <p class="ufsc-form-note">
                            <strong>Note :</strong> Le licencié sera ajouté au panier. 
                            La licence sera créée automatiquement après confirmation du paiement.
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render quota information for the club
 */
function ufsc_render_quota_information($club)
{
    $club_manager = UFSC_Club_Manager::get_instance();
    $licences = $club_manager->get_licences_by_club($club->id);
    $quota_total = intval($club->quota_licences);
    $licences_count = count($licences);
    $is_unlimited = ($quota_total === 0);
    
    ob_start();
    ?>
    <div class="ufsc-quota-info">
        <?php if ($is_unlimited): ?>
            <div class="ufsc-alert ufsc-alert-info">
                <p><strong>Quota :</strong> Illimité - Vous pouvez ajouter autant de licenciés que nécessaire.</p>
                <p><strong>Licences actuelles :</strong> <?php echo $licences_count; ?></p>
            </div>
        <?php else: ?>
            <?php 
            $quota_remaining = max(0, $quota_total - $licences_count);
            $alert_class = $quota_remaining > 0 ? 'ufsc-alert-info' : 'ufsc-alert-warning';
            ?>
            <div class="ufsc-alert <?php echo $alert_class; ?>">
                <p><strong>Quota de licences :</strong> <?php echo $licences_count; ?> / <?php echo $quota_total; ?> utilisées</p>
                <?php if ($quota_remaining > 0): ?>
                    <p><strong>Licences restantes :</strong> <?php echo $quota_remaining; ?></p>
                <?php else: ?>
                    <p><strong>⚠️ Quota épuisé</strong> - Contactez l'administration UFSC pour augmenter votre quota.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Enqueue assets for AJAX form
 */
function ufsc_enqueue_add_licencie_assets()
{
    // Enqueue jQuery if not already loaded
    wp_enqueue_script('jquery');
    
    // Enqueue custom AJAX script
    wp_enqueue_script(
        'ufsc-add-licencie',
        UFSC_PLUGIN_URL . 'assets/js/ufsc-add-licencie.js',
        ['jquery'],
        UFSC_GESTION_CLUB_VERSION,
        true
    );
    
    // Localize script with AJAX data
    wp_localize_script('ufsc-add-licencie', 'ufscAjax', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'addLicencieNonce' => ufsc_create_nonce('ufsc_add_licencie_nonce')
    ]);
    
    // Enqueue frontend styles if available
    if (wp_style_is('ufsc-frontend', 'registered')) {
        wp_enqueue_style('ufsc-frontend');
    }
}

add_shortcode('ufsc_ajouter_licencie', 'ufsc_ajouter_licencie_shortcode');