<?php

/**
 * Shortcodes frontend pour téléversement d'attestations UFSC
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue frontend assets for attestation shortcodes
 */
function ufsc_attestations_enqueue_frontend_assets() {
    // Only enqueue on pages that might have our shortcodes
    if (is_admin() || !is_singular()) {
        return;
    }
    
    // Check if any of our shortcodes are present on the page
    global $post;
    if (!$post || !has_shortcode($post->post_content, 'ufsc_attestation_upload_club') && 
        !has_shortcode($post->post_content, 'ufsc_attestation_upload_license') && 
        !has_shortcode($post->post_content, 'ufsc_attestation_list')) {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'ufsc-attestations-frontend',
        UFSC_PLUGIN_URL . 'assets/css/attestations-frontend.css',
        ['ufsc-frontend-style'],
        UFSC_PLUGIN_VERSION
    );
    
    // Enqueue JS
    wp_enqueue_script(
        'ufsc-attestations-frontend',
        UFSC_PLUGIN_URL . 'assets/js/attestations-frontend.js',
        ['jquery'],
        UFSC_PLUGIN_VERSION,
        true
    );
    
    // Localize script with AJAX data
    wp_localize_script('ufsc-attestations-frontend', 'ufscAttestationsFrontend', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => ufsc_create_nonce('ufsc_front_nonce'),
        'messages' => [
            'uploading' => __('Téléchargement en cours...', 'plugin-ufsc-gestion-club-13072025'),
            'success' => __('Attestation téléchargée avec succès', 'plugin-ufsc-gestion-club-13072025'),
            'error' => __('Erreur lors du téléchargement', 'plugin-ufsc-gestion-club-13072025'),
            'invalid_file' => __('Fichier invalide. Seuls les PDF sont autorisés.', 'plugin-ufsc-gestion-club-13072025'),
            'file_too_large' => __('Fichier trop volumineux. Taille maximale : 5MB.', 'plugin-ufsc-gestion-club-13072025'),
            'no_file' => __('Veuillez sélectionner un fichier.', 'plugin-ufsc-gestion-club-13072025')
        ]
    ]);
}
add_action('wp_enqueue_scripts', 'ufsc_attestations_enqueue_frontend_assets');

/**
 * Shortcode [ufsc_attestation_upload_club]
 * Affiche le formulaire de téléversement d'attestation club
 */
function ufsc_attestation_upload_club_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="ufsc-alert ufsc-alert-error">
                <p>Vous devez être connecté pour télécharger une attestation.</p>' .
                ufsc_render_login_prompt() .
                '</div>';
    }
    
    $atts = shortcode_atts([
        'title' => 'Télécharger attestation club',
        'description' => 'Téléchargez votre attestation d\'affiliation club (PDF uniquement, max 5MB)'
    ], $atts, 'ufsc_attestation_upload_club');
    
    ob_start();
    ?>
    <div class="ufsc-attestation-upload-form" data-type="club">
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php echo esc_html($atts['title']); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <p><?php echo esc_html($atts['description']); ?></p>
                
                <form class="ufsc-attestation-form" enctype="multipart/form-data">
                    <div class="ufsc-form-group">
                        <label for="ufsc-attestation-club-file">Fichier PDF :</label>
                        <input type="file" id="ufsc-attestation-club-file" name="attestation_file" accept=".pdf,application/pdf" required>
                        <small class="ufsc-help-text">Format PDF uniquement, taille maximum 5MB</small>
                    </div>
                    
                    <div class="ufsc-form-actions">
                        <button type="submit" class="ufsc-btn ufsc-btn-primary">
                            <span class="ufsc-btn-text">Télécharger</span>
                            <span class="ufsc-btn-loading" style="display: none;">Téléchargement...</span>
                        </button>
                    </div>
                    
                    <div class="ufsc-upload-feedback" style="display: none;"></div>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ufsc_attestation_upload_club', 'ufsc_attestation_upload_club_shortcode');

/**
 * Shortcode [ufsc_attestation_upload_license]
 * Affiche le formulaire de téléversement d'attestation licence
 */
function ufsc_attestation_upload_license_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="ufsc-alert ufsc-alert-error">
                <p>Vous devez être connecté pour télécharger une attestation.</p>' .
                ufsc_render_login_prompt() .
                '</div>';
    }
    
    $atts = shortcode_atts([
        'title' => 'Télécharger attestation licence',
        'description' => 'Téléchargez votre attestation de licence individuelle (PDF uniquement, max 5MB)'
    ], $atts, 'ufsc_attestation_upload_license');
    
    ob_start();
    ?>
    <div class="ufsc-attestation-upload-form" data-type="license">
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php echo esc_html($atts['title']); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <p><?php echo esc_html($atts['description']); ?></p>
                
                <form class="ufsc-attestation-form" enctype="multipart/form-data">
                    <div class="ufsc-form-group">
                        <label for="ufsc-attestation-license-file">Fichier PDF :</label>
                        <input type="file" id="ufsc-attestation-license-file" name="attestation_file" accept=".pdf,application/pdf" required>
                        <small class="ufsc-help-text">Format PDF uniquement, taille maximum 5MB</small>
                    </div>
                    
                    <div class="ufsc-form-actions">
                        <button type="submit" class="ufsc-btn ufsc-btn-primary">
                            <span class="ufsc-btn-text">Télécharger</span>
                            <span class="ufsc-btn-loading" style="display: none;">Téléchargement...</span>
                        </button>
                    </div>
                    
                    <div class="ufsc-upload-feedback" style="display: none;"></div>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ufsc_attestation_upload_license', 'ufsc_attestation_upload_license_shortcode');

/**
 * Shortcode [ufsc_attestation_list]
 * Affiche la liste des attestations téléchargées par l'utilisateur
 */
function ufsc_attestation_list_shortcode($atts) {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="ufsc-alert ufsc-alert-error">
                <p>Vous devez être connecté pour voir vos attestations.</p>' .
                ufsc_render_login_prompt() .
                '</div>';
    }
    
    $atts = shortcode_atts([
        'title' => 'Mes attestations'
    ], $atts, 'ufsc_attestation_list');
    
    $user_id = get_current_user_id();
    $attestations = get_user_meta($user_id, 'ufsc_attestations', true);
    
    if (!is_array($attestations)) {
        $attestations = [];
    }
    
    ob_start();
    ?>
    <div class="ufsc-attestation-list">
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php echo esc_html($atts['title']); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <?php if (empty($attestations)): ?>
                    <div class="ufsc-empty-state">
                        <p>Aucune attestation téléchargée pour le moment.</p>
                    </div>
                <?php else: ?>
                    <div class="ufsc-attestation-grid">
                        <?php foreach ($attestations as $type => $attestation): ?>
                            <div class="ufsc-attestation-item">
                                <div class="ufsc-attestation-type">
                                    <i class="dashicons dashicons-media-document"></i>
                                    <span><?php echo $type === 'club' ? 'Attestation Club' : 'Attestation Licence'; ?></span>
                                </div>
                                <div class="ufsc-attestation-info">
                                    <small>Téléchargé le <?php echo date_i18n(get_option('date_format'), $attestation['uploaded_at']); ?></small>
                                </div>
                                <div class="ufsc-attestation-actions">
                                    <a href="<?php echo esc_url($attestation['url']); ?>" target="_blank" class="ufsc-btn ufsc-btn-small">
                                        <i class="dashicons dashicons-download"></i> Télécharger
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ufsc_attestation_list', 'ufsc_attestation_list_shortcode');