<?php
/**
 * UFSC Frontend Shortcodes
 *
 * Main frontend shortcodes for club registration, account management, 
 * license management and dashboard functionality
 *
 * @package UFSC_Gestion_Club
 * @since 1.2.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include additional frontend shortcodes
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/order-history.php';

/**
 * Register all frontend shortcodes
 */
function ufsc_register_frontend_shortcodes() {
    add_shortcode('ufsc_club_register', 'ufsc_club_register_shortcode');
    add_shortcode('ufsc_club_account', 'ufsc_club_account_shortcode');
    add_shortcode('ufsc_club_licenses', 'ufsc_club_licenses_shortcode');
    add_shortcode('ufsc_order_history', 'ufsc_order_history_shortcode');

    // Le shortcode dashboard peut être défini ailleurs (ex: frontend/frontend-club-dashboard.php).
    // On enregistre le shortcode seulement si la fonction existe ou sera fournie par ce fichier.
    if (function_exists('ufsc_club_dashboard_shortcode')) {
        add_shortcode('ufsc_club_dashboard', 'ufsc_club_dashboard_shortcode');
    } else {
        // On l’ajoutera quand même si ce wrapper est défini ci‑dessous.
        // (WordPress acceptera l’enregistrement : la fonction doit exister au moment de l’exécution du shortcode.)
        add_shortcode('ufsc_club_dashboard', 'ufsc_club_dashboard_shortcode');
    }
}
add_action('init', 'ufsc_register_frontend_shortcodes');

/**
 * Check if login is required for shortcodes based on admin setting
 * 
 * @return bool True if login is required, false otherwise
 */
function ufsc_shortcodes_require_login() {
    return (bool) get_option('ufsc_require_login_shortcodes', true);
}

/**
 * Wrapper function to handle login requirement for shortcodes
 * 
 * @param string $shortcode_name Name of the shortcode
 * @param callable $callback The shortcode callback function
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_shortcode_with_login_check($shortcode_name, $callback, $atts = array()) {
    // Check if login is required and user is not logged in
    if (ufsc_shortcodes_require_login() && !is_user_logged_in()) {
        return ufsc_render_login_requirement($shortcode_name);
    }
    
    // Execute the original shortcode callback
    return call_user_func($callback, $atts);
}

/**
 * Club Registration Shortcode
 * 
 * Displays club registration/affiliation form
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_club_register_shortcode($atts = array()) {
    return ufsc_shortcode_with_login_check('club_register', 'ufsc_club_register_content', $atts);
}

/**
 * Club registration content (internal function)
 */
function ufsc_club_register_content($atts = array()) {
    $atts = shortcode_atts(array(
        'redirect' => '',
        'style' => 'default'
    ), $atts, 'ufsc_club_register');

    // Include existing affiliation form if available
    if (function_exists('ufsc_render_affiliation_club_form')) {
        return ufsc_render_affiliation_club_form($atts);
    }

    // Fallback simple form
    return ufsc_render_simple_club_register_form($atts);
}

/**
 * Club Account Shortcode
 * 
 * Displays club account information and settings
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_club_account_shortcode($atts = array()) {
    return ufsc_shortcode_with_login_check('club_account', 'ufsc_club_account_content', $atts);
}

/**
 * Club account content (internal function)
 */
function ufsc_club_account_content($atts = array()) {
    $atts = shortcode_atts(array(
        'show_edit' => 'true',
        'show_documents' => 'true'
    ), $atts, 'ufsc_club_account');

    // Get user's club
    $club = ufsc_get_user_club();
    if (!$club) {
        return ufsc_render_no_club_message('account');
    }

    return ufsc_render_club_account_form($club, $atts);
}

/**
 * Club Licenses Shortcode
 * 
 * Displays club license management interface
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_club_licenses_shortcode($atts = array()) {
    return ufsc_shortcode_with_login_check('club_licenses', 'ufsc_club_licenses_content', $atts);
}

/**
 * Club licenses content (internal function)
 */
function ufsc_club_licenses_content($atts = array()) {
    $atts = shortcode_atts(array(
        'show_add_form' => 'true',
        'show_list' => 'true',
        'per_page' => '10'
    ), $atts, 'ufsc_club_licenses');

    // Get user's club
    $club = ufsc_get_user_club();
    if (!$club) {
        return ufsc_render_no_club_message('licenses');
    }

    return ufsc_render_club_licenses_interface($club, $atts);
}

/**
 * Club Dashboard Shortcode (wrapper avec contrôle login)
 * 
 * IMPORTANT :
 * - Protégé par if (!function_exists(...)) pour éviter une Fatal error
 *   si une autre implémentation complète existe déjà.
 */
if (!function_exists('ufsc_club_dashboard_shortcode')) {
    function ufsc_club_dashboard_shortcode($atts = array()) {
        return ufsc_shortcode_with_login_check('club_dashboard', 'ufsc_club_dashboard_content', $atts);
    }
}

/**
 * Club dashboard content (internal function)
 */
function ufsc_club_dashboard_content($atts = array()) {
    $atts = shortcode_atts(array(
        'layout' => 'grid',
        'show_stats' => 'true',
        'show_quick_actions' => 'true'
    ), $atts, 'ufsc_club_dashboard');

    // Get user's club
    $club = ufsc_get_user_club();
    if (!$club) {
        // When no club is associated, prompt guests to log in before creating one
        if (!is_user_logged_in()) {
            return ufsc_render_login_requirement('club_dashboard');
        }
        return ufsc_render_no_club_message('dashboard');
    }

    return ufsc_render_club_dashboard($club, $atts);
}

/**
 * Render login requirement message
 * 
 * @param string $context Context for the message
 * @return string HTML output
 */
function ufsc_render_login_requirement($context = '') {
    $nonce = wp_create_nonce('ufsc_frontend_action');

    $extra_message = '';
    if ($context === 'club_dashboard') {
        $extra_message = '<p>' . esc_html__('Veuillez vous connecter pour créer votre club.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card ufsc-login-required">'
        . '<div class="ufsc-alert ufsc-alert-info">'
            . '<h4>' . esc_html__('Connexion requise', 'plugin-ufsc-gestion-club-13072025') . '</h4>'
            . '<p>' . esc_html__('Vous devez être connecté pour accéder à cette section.', 'plugin-ufsc-gestion-club-13072025') . '</p>'
            . $extra_message
            . ufsc_render_login_prompt()
        . '</div>'
        . '<input type="hidden" name="ufsc_nonce" value="' . esc_attr($nonce) . '">'
        . '</div></div></div>';
}

/**
 * Render no club message
 * 
 * @param string $context Context for the message
 * @return string HTML output
 */
function ufsc_render_no_club_message($context = '') {
    $nonce = wp_create_nonce('ufsc_frontend_action');

    $message = '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card ufsc-no-club">
        <div class="ufsc-alert ufsc-alert-warning">
            <h4>' . esc_html__('Aucun club associé', 'plugin-ufsc-gestion-club-13072025') . '</h4>
            <p>' . esc_html__('Vous n\'êtes pas encore associé à un club.', 'plugin-ufsc-gestion-club-13072025') . '</p>';

    if ($context === 'dashboard') {
        $register_url = ufsc_get_page_url('club_form') ?: '#';
        $message .= '<div class="ufsc-button-group">
                <a href="' . esc_url($register_url) . '" class="ufsc-btn ufsc-btn-primary">' .
                    esc_html__('Créer un club', 'plugin-ufsc-gestion-club-13072025') . '</a>
            </div>';
    }

    $message .= '</div>
        <input type="hidden" name="ufsc_nonce" value="' . esc_attr($nonce) . '">
    </div></div></div>';

    return $message;
}

/**
 * Render simple club register form (fallback)
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_render_simple_club_register_form($atts) {
    $nonce = wp_create_nonce('ufsc_club_register');
    
    ob_start();
    ?>
    <div class="ufsc-container"><div class="ufsc-grid">
    <div class="ufsc-club-register-form">
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Créer un club', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <form method="post" class="ufsc-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('ufsc_club_register', 'ufsc_club_register_nonce'); ?>
                    
                    <div class="ufsc-form-group">
                        <label for="club_nom" class="ufsc-label required">
                            <?php esc_html_e('Nom du club', 'plugin-ufsc-gestion-club-13072025'); ?>
                        </label>
                        <input type="text" id="club_nom" name="club_nom" class="ufsc-input" required 
                               value="<?php echo esc_attr($_POST['club_nom'] ?? ''); ?>">
                    </div>
                    
                    <div class="ufsc-form-group">
                        <label for="club_adresse" class="ufsc-label required">
                            <?php esc_html_e('Adresse', 'plugin-ufsc-gestion-club-13072025'); ?>
                        </label>
                        <textarea id="club_adresse" name="club_adresse" class="ufsc-textarea" required 
                                  rows="3"><?php echo esc_textarea($_POST['club_adresse'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <div class="ufsc-form-group">
                            <label for="club_ville" class="ufsc-label required">
                                <?php esc_html_e('Ville', 'plugin-ufsc-gestion-club-13072025'); ?>
                            </label>
                            <input type="text" id="club_ville" name="club_ville" class="ufsc-input" required 
                                   value="<?php echo esc_attr($_POST['club_ville'] ?? ''); ?>">
                        </div>
                        <div class="ufsc-form-group">
                            <label for="club_code_postal" class="ufsc-label required">
                                <?php esc_html_e('Code postal', 'plugin-ufsc-gestion-club-13072025'); ?>
                            </label>
                            <input type="text" id="club_code_postal" name="club_code_postal" class="ufsc-input" required 
                                   value="<?php echo esc_attr($_POST['club_code_postal'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-actions">
                        <button type="submit" name="ufsc_submit_club" class="ufsc-btn ufsc-btn-primary">
                            <?php esc_html_e('Créer le club', 'plugin-ufsc-gestion-club-13072025'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div></div>
    <?php
    return ob_get_clean();
}

// Include helper functions for document URL resolution
require_once UFSC_PLUGIN_PATH . 'includes/frontend/helpers/dashboard-data.php';

/**
 * Helper function to render info row
 * 
 * @param string $label Field label
 * @param string $value Field value
 * @param string $type Field type (text, email, url, address, date, status)
 * @return string HTML output
 */
function ufsc_render_info_row($label, $value, $type = 'text') {
    if (empty($value) && $value !== '0') {
        return '';
    }
    
    $escaped_label = esc_html($label);
    
    switch ($type) {
        case 'email':
            $escaped_value = '<a href="mailto:' . esc_attr($value) . '">' . esc_html($value) . '</a>';
            break;
            
        case 'url':
            $escaped_value = '<a href="' . esc_url($value) . '" target="_blank" rel="noopener">' . esc_html($value) . '</a>';
            break;
            
        case 'address':
            $escaped_value = nl2br(esc_html($value));
            break;
            
        case 'date':
            $escaped_value = esc_html(date_i18n(get_option('date_format'), strtotime($value)));
            break;
            
        case 'status':
            $status_class = 'ufsc-status ufsc-status-' . esc_attr(sanitize_title($value));
            $escaped_value = '<span class="' . $status_class . '">' . esc_html($value) . '</span>';
            break;
            
        case 'text':
        default:
            $escaped_value = esc_html($value);
            break;
    }
    
    return '<div class="ufsc-info-row">
                <span class="ufsc-info-label">' . $escaped_label . ':</span>
                <span class="ufsc-info-value">' . $escaped_value . '</span>
            </div>';
}

/**
 * Render club account form
 * 
 * @param object $club Club object
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_render_club_account_form($club, $atts) {
    $nonce = wp_create_nonce('ufsc_club_account');
    
    // Check for update success message
    $updated = isset($_GET['ufsc_updated']) && $_GET['ufsc_updated'] == '1';
    
    ob_start();
    ?>
    <style>
    .ufsc-club-account .ufsc-club-info-grid {
        display: grid;
        gap: 10px;
        margin: 15px 0;
    }
    .ufsc-club-account .ufsc-info-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .ufsc-club-account .ufsc-info-label {
        font-weight: 600;
        min-width: 150px;
        margin-right: 15px;
    }
    .ufsc-club-account .ufsc-info-value {
        flex: 1;
    }
    .ufsc-club-account .ufsc-dirigeants-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin: 15px 0;
    }
    .ufsc-club-account .ufsc-dirigeant-card {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 4px;
        background: #f9f9f9;
    }
    .ufsc-club-account .ufsc-dirigeant-card h4 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 14px;
        font-weight: 600;
    }
    .ufsc-club-account .ufsc-documents-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin: 15px 0;
    }
    .ufsc-club-account .ufsc-document-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #fff;
    }
    .ufsc-club-account .ufsc-document-name {
        font-weight: 500;
    }
    .ufsc-club-account .ufsc-btn-small {
        font-size: 12px;
        padding: 5px 10px;
    }
    .ufsc-club-account .ufsc-edit-form {
        margin-top: 20px;
    }
    .ufsc-club-account .ufsc-form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    .ufsc-club-account .ufsc-form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    .ufsc-club-account .ufsc-input {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .ufsc-club-account .ufsc-status {
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
    }
    .ufsc-club-account .ufsc-status-actif {
        background: #d4edda;
        color: #155724;
    }
    .ufsc-club-account .ufsc-alert-success {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        border: 1px solid #c3e6cb;
    }
    .ufsc-club-account .ufsc-btn {
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        font-size: 14px;
        font-weight: 500;
    }
    .ufsc-club-account .ufsc-btn-primary {
        background: #007cba;
        color: white;
    }
    .ufsc-club-account .ufsc-btn-outline {
        background: transparent;
        color: #007cba;
        border: 1px solid #007cba;
    }
    .ufsc-club-account .ufsc-btn-secondary {
        background: #6c757d;
        color: white;
    }
    .ufsc-club-account .ufsc-form-actions {
        margin: 20px 0;
    }
    .ufsc-club-account .ufsc-form-actions .ufsc-btn + .ufsc-btn {
        margin-left: 10px;
    }
    @media (max-width: 768px) {
        .ufsc-club-account .ufsc-form-row {
            grid-template-columns: 1fr;
        }
        .ufsc-club-account .ufsc-dirigeants-grid,
        .ufsc-club-account .ufsc-documents-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <div class="ufsc-container"><div class="ufsc-grid">
    <div class="ufsc-club-account">

        <?php if ($updated): ?>
        <div class="ufsc-card">
            <div class="ufsc-card-body">
                <div class="ufsc-alert ufsc-alert-success">
                    <p><strong><?php esc_html_e('Succès !', 'plugin-ufsc-gestion-club-13072025'); ?></strong>
                       <?php esc_html_e('Les informations du club ont été mises à jour.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Informations générales -->
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Informations générales', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <div class="ufsc-club-info-grid">
                    <?php echo ufsc_render_info_row('Nom du club', $club->nom ?? '', 'text'); ?>
                    <?php echo ufsc_render_info_row('Statut', $club->statut ?? 'Non défini', 'status'); ?>
                    <?php if (!empty($club->num_affiliation)): ?>
                        <?php echo ufsc_render_info_row('Numéro d\'affiliation', $club->num_affiliation, 'text'); ?>
                    <?php endif; ?>
                    <?php echo ufsc_render_info_row('Email du club', $club->email ?? '', 'email'); ?>
                    <?php echo ufsc_render_info_row('Téléphone du club', $club->telephone ?? '', 'text'); ?>
                    <?php echo ufsc_render_info_row('Quota de licences', ($club->quota_licences == 0 ? 'Illimité' : $club->quota_licences), 'text'); ?>
                </div>
            </div>
        </div>

        <!-- Coordonnées -->
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Coordonnées', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <div class="ufsc-club-info-grid">
                    <?php echo ufsc_render_info_row('Adresse', $club->adresse ?? '', 'address'); ?>
                    <?php if (!empty($club->complement_adresse)): ?>
                        <?php echo ufsc_render_info_row('Complément d\'adresse', $club->complement_adresse, 'text'); ?>
                    <?php endif; ?>
                    <?php echo ufsc_render_info_row('Code postal', $club->code_postal ?? '', 'text'); ?>
                    <?php echo ufsc_render_info_row('Ville', $club->ville ?? '', 'text'); ?>
                    <?php echo ufsc_render_info_row('Région', $club->region ?? '', 'text'); ?>
                    <?php if (!empty($club->precision_distribution)): ?>
                        <?php echo ufsc_render_info_row('Précision distribution', $club->precision_distribution, 'text'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Présence en ligne -->
        <?php if (!empty($club->url_site) || !empty($club->url_facebook) || !empty($club->url_instagram)): ?>
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Présence en ligne', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <div class="ufsc-club-info-grid">
                    <?php if (!empty($club->url_site)): ?>
                        <?php echo ufsc_render_info_row('Site web', $club->url_site, 'url'); ?>
                    <?php endif; ?>
                    <?php if (!empty($club->url_facebook)): ?>
                        <?php echo ufsc_render_info_row('Facebook', $club->url_facebook, 'url'); ?>
                    <?php endif; ?>
                    <?php if (!empty($club->url_instagram)): ?>
                        <?php echo ufsc_render_info_row('Instagram', $club->url_instagram, 'url'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Identité légale et administrative -->
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Identité légale et administrative', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <div class="ufsc-club-info-grid">
                    <?php if (!empty($club->rna_number)): ?>
                        <?php echo ufsc_render_info_row('Numéro RNA', $club->rna_number, 'text'); ?>
                    <?php endif; ?>
                    <?php if (!empty($club->siren)): ?>
                        <?php echo ufsc_render_info_row('SIREN', $club->siren, 'text'); ?>
                    <?php endif; ?>
                    <?php if (!empty($club->ape)): ?>
                        <?php echo ufsc_render_info_row('Code APE', $club->ape, 'text'); ?>
                    <?php endif; ?>
                    <?php if (!empty($club->iban)): ?>
                        <?php echo ufsc_render_info_row('IBAN', $club->iban, 'text'); ?>
                    <?php endif; ?>
                    <?php if (!empty($club->num_declaration)): ?>
                        <?php echo ufsc_render_info_row('Numéro de déclaration', $club->num_declaration, 'text'); ?>
                    <?php endif; ?>
                    <?php if (!empty($club->date_declaration)): ?>
                        <?php echo ufsc_render_info_row('Date de déclaration', $club->date_declaration, 'date'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Équipe dirigeante -->
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Équipe dirigeante', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <div class="ufsc-dirigeants-grid">
                    <?php 
                    $roles = ['president' => 'Président(e)', 'secretaire' => 'Secrétaire', 'tresorier' => 'Trésorier(e)', 'entraineur' => 'Entraîneur(e)'];
                    foreach ($roles as $role => $label): 
                        $nom = $club->{$role . '_nom'} ?? '';
                        $prenom = $club->{$role . '_prenom'} ?? '';
                        $email = $club->{$role . '_email'} ?? '';
                        $tel = $club->{$role . '_tel'} ?? '';
                        
                        if (!empty($nom) || !empty($prenom) || !empty($email) || !empty($tel)):
                    ?>
                    <div class="ufsc-dirigeant-card">
                        <h4><?php echo esc_html($label); ?></h4>
                        <?php if (!empty($nom) || !empty($prenom)): ?>
                            <?php echo ufsc_render_info_row('Nom', trim($prenom . ' ' . $nom), 'text'); ?>
                        <?php endif; ?>
                        <?php if (!empty($email)): ?>
                            <?php echo ufsc_render_info_row('Email', $email, 'email'); ?>
                        <?php endif; ?>
                        <?php if (!empty($tel)): ?>
                            <?php echo ufsc_render_info_row('Téléphone', $tel, 'text'); ?>
                        <?php endif; ?>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <?php if ($atts['show_documents'] === 'true'): ?>
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Documents', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <div class="ufsc-documents-grid">
                    <?php 
                    $documents = [
                        'doc_statuts' => 'Statuts',
                        'doc_recepisse' => 'Récépissé',
                        'doc_jo' => 'Journal Officiel',
                        'doc_pv_ag' => 'PV Assemblée Générale',
                        'doc_cer' => 'Certificat CER',
                        'doc_attestation_cer' => 'Attestation CER',
                        'doc_attestation_affiliation' => 'Attestation d\'affiliation'
                    ];
                    
                    foreach ($documents as $field => $label):
                        $doc_value = $club->{$field} ?? '';
                        if (!empty($doc_value)):
                            $doc_url = ufsc_resolve_document_url($doc_value);
                            if ($doc_url):
                    ?>
                    <div class="ufsc-document-item">
                        <span class="ufsc-document-name"><?php echo esc_html($label); ?></span>
                        <a href="<?php echo esc_url($doc_url); ?>" class="ufsc-btn ufsc-btn-small ufsc-btn-outline" target="_blank">
                            <?php esc_html_e('Télécharger', 'plugin-ufsc-gestion-club-13072025'); ?>
                        </a>
                    </div>
                    <?php 
                            endif;
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit button and form -->
        <?php if ($atts['show_edit'] === 'true'): ?>
        <div class="ufsc-form-actions">
            <button type="button" class="ufsc-btn ufsc-btn-outline" onclick="ufscToggleEditForm()">
                <?php esc_html_e('Modifier les informations de contact', 'plugin-ufsc-gestion-club-13072025'); ?>
            </button>
        </div>

        <div id="ufsc-edit-form" class="ufsc-card ufsc-edit-form" style="display: none;">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Modifier les informations de contact', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
                <p><?php esc_html_e('Vous pouvez uniquement modifier les emails et numéros de téléphone.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
            </div>
            <div class="ufsc-card-body">
                <form method="post" class="ufsc-form">
                    <?php wp_nonce_field('ufsc_club_account', 'ufsc_club_account_nonce'); ?>
                    <input type="hidden" name="ufsc_action" value="update_club">

                    <!-- Club contact info -->
                    <h4><?php esc_html_e('Informations du club', 'plugin-ufsc-gestion-club-13072025'); ?></h4>
                    <div class="ufsc-form-row">
                        <div class="ufsc-form-group">
                            <label for="club_email"><?php esc_html_e('Email du club', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="email" id="club_email" name="email" value="<?php echo esc_attr($club->email ?? ''); ?>" class="ufsc-input">
                        </div>
                        <div class="ufsc-form-group">
                            <label for="club_telephone"><?php esc_html_e('Téléphone du club', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="text" id="club_telephone" name="telephone" value="<?php echo esc_attr($club->telephone ?? ''); ?>" class="ufsc-input">
                        </div>
                    </div>

                    <!-- Dirigeants contact info -->
                    <?php foreach ($roles as $role => $label): ?>
                    <h4><?php echo esc_html($label); ?></h4>
                    <div class="ufsc-form-row">
                        <div class="ufsc-form-group">
                            <label for="<?php echo esc_attr($role); ?>_email"><?php echo esc_html($label . ' - Email'); ?></label>
                            <input type="email" id="<?php echo esc_attr($role); ?>_email" name="<?php echo esc_attr($role); ?>_email" 
                                   value="<?php echo esc_attr($club->{$role . '_email'} ?? ''); ?>" class="ufsc-input">
                        </div>
                        <div class="ufsc-form-group">
                            <label for="<?php echo esc_attr($role); ?>_tel"><?php echo esc_html($label . ' - Téléphone'); ?></label>
                            <input type="text" id="<?php echo esc_attr($role); ?>_tel" name="<?php echo esc_attr($role); ?>_tel" 
                                   value="<?php echo esc_attr($club->{$role . '_tel'} ?? ''); ?>" class="ufsc-input">
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="ufsc-form-actions">
                        <button type="submit" class="ufsc-btn ufsc-btn-primary">
                            <?php esc_html_e('Enregistrer les modifications', 'plugin-ufsc-gestion-club-13072025'); ?>
                        </button>
                        <button type="button" class="ufsc-btn ufsc-btn-secondary" onclick="ufscToggleEditForm()">
                            <?php esc_html_e('Annuler', 'plugin-ufsc-gestion-club-13072025'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        function ufscToggleEditForm() {
            var form = document.getElementById('ufsc-edit-form');
            if (form.style.display === 'none') {
                form.style.display = 'block';
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                form.style.display = 'none';
            }
        }
        </script>
        <?php endif; ?>

        <input type="hidden" name="ufsc_nonce" value="<?php echo esc_attr($nonce); ?>">
    </div>
    </div></div>
    <?php
    return ob_get_clean();
}

/**
 * Render club licenses interface
 * 
 * @param object $club Club object
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_render_club_licenses_interface($club, $atts) {
    $nonce = wp_create_nonce('ufsc_club_licenses');
    
    ob_start();
    ?>
    <div class="ufsc-club-licenses">
        <?php if ($atts['show_add_form'] === 'true'): ?>
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Ajouter une licence', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <div class="ufsc-button-group">
                    <?php 
                    $licence_page_url = ufsc_get_page_url('licence') ?: '#';
                    ?>
                    <a href="<?php echo esc_url($licence_page_url); ?>" class="ufsc-btn ufsc-btn-primary">
                        <?php esc_html_e('Nouvelle licence', 'plugin-ufsc-gestion-club-13072025'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_list'] === 'true'): ?>
        <div class="ufsc-card">
            <div class="ufsc-card-header">
                <h3><?php esc_html_e('Licences du club', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            </div>
            <div class="ufsc-card-body">
                <?php echo ufsc_render_club_licenses_list($club, $atts); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <input type="hidden" name="ufsc_nonce" value="<?php echo esc_attr($nonce); ?>">
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render club dashboard
 * 
 * @param object $club Club object
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_render_club_dashboard($club, $atts) {
    $nonce = wp_create_nonce('ufsc_club_dashboard');
    
    ob_start();
    ?>
    <div class="ufsc-club-dashboard">
        <div class="ufsc-dashboard-header">
            <h2><?php printf(esc_html__('Tableau de bord - %s', 'plugin-ufsc-gestion-club-13072025'), esc_html($club->nom ?? '')); ?></h2>
        </div>
        
        <?php if ($atts['show_stats'] === 'true'): ?>
        <div class="ufsc-dashboard-stats">
            <div class="ufsc-stat-card">
                <div class="ufsc-stat-number"><?php echo esc_html(ufsc_get_club_license_count($club->id ?? 0)); ?></div>
                <div class="ufsc-stat-label"><?php esc_html_e('Licences', 'plugin-ufsc-gestion-club-13072025'); ?></div>
            </div>
            <div class="ufsc-stat-card">
                <div class="ufsc-stat-number"><?php echo esc_html($club->quota_licences ?? 0); ?></div>
                <div class="ufsc-stat-label"><?php esc_html_e('Quota disponible', 'plugin-ufsc-gestion-club-13072025'); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_quick_actions'] === 'true'): ?>
        <div class="ufsc-dashboard-actions">
            <div class="ufsc-card">
                <div class="ufsc-card-header">
                    <h3><?php esc_html_e('Actions rapides', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
                </div>
                <div class="ufsc-card-body">
                    <div class="ufsc-action-grid">
                        <a href="<?php echo esc_url(ufsc_get_page_url('licence') ?: '#'); ?>" class="ufsc-action-item">
                            <span class="ufsc-action-icon">📝</span>
                            <span class="ufsc-action-text"><?php esc_html_e('Nouvelle licence', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                        </a>
                        <a href="<?php echo esc_url(ufsc_get_page_url('attestation') ?: '#'); ?>" class="ufsc-action-item">
                            <span class="ufsc-action-icon">📄</span>
                            <span class="ufsc-action-text"><?php esc_html_e('Attestations', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <input type="hidden" name="ufsc_nonce" value="<?php echo esc_attr($nonce); ?>">
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Helper function to get club license count
 * 
 * @param int $club_id Club ID
 * @return int License count
 */
function ufsc_get_club_license_count($club_id) {
    global $wpdb;
    
    if (!$club_id) {
        return 0;
    }
    
    $table_name = $wpdb->prefix . 'ufsc_licences';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return 0;
    }
    
    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE club_id = %d",
        $club_id
    ));
}

/**
 * Helper function to render club licenses list
 * 
 * @param object $club Club object
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_render_club_licenses_list($club, $atts) {
    global $wpdb;
    
    $club_id = $club->id ?? 0;
    if (!$club_id) {
        return '<p>' . esc_html__('Aucune licence trouvée.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }
    
    $table_name = $wpdb->prefix . 'ufsc_licences';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return '<p>' . esc_html__('Table des licences non trouvée.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }
    
    $per_page = intval($atts['per_page']);
    $licenses = $wpdb->get_results($wpdb->prepare(
        "SELECT id, nom, prenom, email,
                COALESCE(statut, status, '') AS statut,
                date_creation
         FROM $table_name
         WHERE club_id = %d
         ORDER BY date_creation DESC
         LIMIT %d",
        $club_id,
        $per_page
    ));
    
    // Fallback in case some installs use a different date column name or views
    if (empty($licenses)) {
        $licenses = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nom, prenom, email,
                    COALESCE(statut, status, '') AS statut,
                    IFNULL(date_creation, NOW()) AS date_creation
             FROM $table_name
             WHERE club_id = %d
             ORDER BY id DESC
             LIMIT %d",
            $club_id,
            $per_page
        ));
    }
    
    if (empty($licenses)) {
        return '<p>' . esc_html__('Aucune licence trouvée pour ce club.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }
    
    ob_start();
    ?>
    <div class="ufsc-licenses-list">
        <table class="ufsc-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Nom', 'plugin-ufsc-gestion-club-13072025'); ?></th>
                    <th><?php esc_html_e('Prénom', 'plugin-ufsc-gestion-club-13072025'); ?></th>
                    <th><?php esc_html_e('Email', 'plugin-ufsc-gestion-club-13072025'); ?></th>
                    <th><?php esc_html_e('Statut', 'plugin-ufsc-gestion-club-13072025'); ?></th>
                    <th><?php esc_html_e('Date', 'plugin-ufsc-gestion-club-13072025'); ?></th>
                    <th><?php esc_html_e('Actions', 'plugin-ufsc-gestion-club-13072025'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license): ?>
                <tr>
                    <td><?php echo esc_html($license->nom ?? ''); ?></td>
                    <td><?php echo esc_html($license->prenom ?? ''); ?></td>
                    <td><?php echo esc_html($license->email ?? ''); ?></td>
                    <td>
                        <span class="ufsc-status ufsc-status-<?php echo esc_attr(sanitize_title($license->statut ?? '')); ?>">
                            <?php echo esc_html( ($license->statut ?? '') !== '' ? $license->statut : 'brouillon'); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license->date_creation ?? ''))); ?></td>
                
<td>
    <?php $__st = trim($license->statut ?? ''); if ($__st === 'brouillon' || $__st === '') : ?>
        <button type="button"
                class="ufsc-btn ufsc-btn-primary ufsc-pay-licence"
                data-licence-id="<?php echo esc_attr($license->id); ?>"
                data-club-id="<?php echo esc_attr($club_id); ?>">
            🛒 <?php esc_html_e('Régler cette licence', 'plugin-ufsc-gestion-club-13072025'); ?>
        </button>

    <button type="button"
            class="ufsc-btn ufsc-btn-danger ufsc-delete-draft"
            data-licence-id="<?php echo esc_attr($license->id); ?>"
            data-club-id="<?php echo esc_attr($club_id); ?>"
            title="<?php esc_attr_e('Supprimer le brouillon','plugin-ufsc-gestion-club-13072025'); ?>">
      🗑️ <?php esc_html_e('Supprimer','plugin-ufsc-gestion-club-13072025'); ?>
    </button>
    
    <?php else: ?>
        <span class="ufsc-muted">—</span>
    <?php endif; ?>
</td>
</tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Helper function to get page URL by type
 * 
 * @param string $page_type Page type
 * @return string|null Page URL or null if not found
 */
function ufsc_get_page_url($page_type) {
    $option_name = 'ufsc_frontend_pro_' . $page_type . '_page';
    $page_id = get_option($option_name);
    if ($page_id) { return get_permalink($page_id); }

    // Fallback: search for a page containing the corresponding shortcode
    $sc = '';
    switch ($page_type) {
        case 'licence':
            $sc = 'ufsc_licence_form';
            break;
        case 'licenses':
        case 'licences':
            $sc = 'ufsc_club_licenses';
            break;
    }
    if ($sc) {
        $q = new WP_Query([
            'post_type' => 'page',
            's' => '[' . $sc,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        if ($q->have_posts()) {
            return get_permalink($q->posts[0]->ID);
        }
    }
    return null;
}

/**
 * Enqueue frontend shortcode assets
 */
function ufsc_enqueue_shortcode_assets() {
    if (ufsc_has_shortcode()) {
        wp_enqueue_style('ufsc-frontend-shortcodes');
        wp_enqueue_script('ufsc-frontend-shortcodes');
    }
}
add_action('wp_enqueue_scripts', 'ufsc_enqueue_shortcode_assets');

/**
 * Check if page has UFSC shortcodes
 * 
 * @return bool
 */
function ufsc_has_shortcode() {
    global $post;
    
    if (!$post) {
        return false;
    }
    
    $shortcodes = array('ufsc_club_register', 'ufsc_club_account', 'ufsc_club_licenses', 'ufsc_club_dashboard');
    
    foreach ($shortcodes as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Handle club account update via POST
 */
function ufsc_handle_club_account_update() {
    // Only process if this is a club account update
    if (!isset($_POST['ufsc_action']) || $_POST['ufsc_action'] !== 'update_club') {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['ufsc_club_account_nonce']) || !wp_verify_nonce($_POST['ufsc_club_account_nonce'], 'ufsc_club_account')) {
        wp_die(__('Security check failed.', 'plugin-ufsc-gestion-club-13072025'), 403);
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die(__('You must be logged in to perform this action.', 'plugin-ufsc-gestion-club-13072025'), 403);
        return;
    }
    
    // Get user's club
    if (!function_exists('ufsc_get_user_club')) {
        wp_die(__('Required function not available.', 'plugin-ufsc-gestion-club-13072025'), 500);
        return;
    }
    
    $club = ufsc_get_user_club();
    if (!$club || !isset($club->id)) {
        wp_die(__('No club associated with your account.', 'plugin-ufsc-gestion-club-13072025'), 403);
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    
    // Verify table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_die(__('Database table not found.', 'plugin-ufsc-gestion-club-13072025'), 500);
        return;
    }
    
    // Define allowed fields (emails and phones only) - STRICT WHITELIST
    $allowed_fields = [
        'email' => 'sanitize_email',
        'telephone' => 'ufsc_sanitize_phone',
        'president_email' => 'sanitize_email',
        'president_tel' => 'ufsc_sanitize_phone',
        'secretaire_email' => 'sanitize_email',
        'secretaire_tel' => 'ufsc_sanitize_phone',
        'tresorier_email' => 'sanitize_email',
        'tresorier_tel' => 'ufsc_sanitize_phone',
        'entraineur_email' => 'sanitize_email',
        'entraineur_tel' => 'ufsc_sanitize_phone'
    ];
    
    $update_data = [];
    
    // Process only allowed fields with strict whitelist
    foreach ($allowed_fields as $field => $sanitize_func) {
        if (isset($_POST[$field])) {
            $value = $_POST[$field];
            
            // Apply appropriate sanitization
            if ($sanitize_func === 'sanitize_email') {
                $value = sanitize_email($value);
                // Allow empty emails or valid emails only
                if ($value === '' || is_email($value)) {
                    $update_data[$field] = $value;
                }
            } elseif ($sanitize_func === 'ufsc_sanitize_phone') {
                $value = ufsc_sanitize_phone($value);
                // Limit phone number length
                if (strlen($value) <= 20) {
                    $update_data[$field] = $value;
                }
            }
        }
    }
    
    // Update the database if we have valid data
    if (!empty($update_data)) {
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => intval($club->id)],
            null,
            ['%d']
        );
        
        if ($result !== false) {
            // Redirect to avoid form resubmission
            $redirect_url = add_query_arg('ufsc_updated', '1', wp_get_referer());
            $redirect_url = $redirect_url ? $redirect_url : home_url();
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    // If we reach here, something went wrong
    wp_die(__('Failed to update club information.', 'plugin-ufsc-gestion-club-13072025'), 500);
}
add_action('init', 'ufsc_handle_club_account_update');

/**
 * Sanitize phone number
 * 
 * @param string $phone Phone number
 * @return string Sanitized phone number
 */
function ufsc_sanitize_phone($phone) {
    // Allow digits, spaces, dots, dashes, plus sign
    return preg_replace('/[^0-9\s\.\-\+]/', '', $phone);
}
