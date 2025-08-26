<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Liste des régions UFSC officielles utilisées dans le plugin
 *
 * @return array
 */
function ufsc_get_regions()
{
    return [
        'UFSC AUVERGNE-RHONE-ALPES',
        'UFSC BOURGOGNE-FRANCHE-COMTE',
        'UFSC BRETAGNE',
        'UFSC CENTRE-VAL-DE-LOIRE',
        'UFSC DROM-COM',
        'UFSC GRAND EST',
        'UFSC HAUTS-DE-FRANCE',
        'UFSC ILE DE FRANCE',
        'UFSC NORMANDIE',
        'UFSC NOUVELLE AQUITAINE',
        'UFSC OCCITANIE',
        'UFSC PACA - CORSE',
        'UFSC PAYS DE LA LOIRE'
    ];
}

/**
 * Liste des statuts de club
 *
 * @return array
 */
function ufsc_get_statuts()
{
    return ['Actif', 'Inactif'];
}

/**
 * Récupère le club associé à un utilisateur WordPress
 *
 * @param int|null $user_id ID de l'utilisateur WordPress (optionnel, utilise l'utilisateur connecté par défaut)
 * @return object|null Objet club ou null si aucun club trouvé
 */
function ufsc_get_user_club($user_id = null)
{
    // If no user_id provided, use current user
    if ($user_id === null) {
        if (!is_user_logged_in()) {
            return null;
        }
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return null;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE responsable_id = %d",
            $user_id
        )
    );
}

/**
 * Safely check user capabilities without causing fatal errors
 * This function ensures WordPress is fully loaded before checking capabilities
 *
 * @param string $capability The capability to check
 * @param int|null $user_id Optional user ID, defaults to current user
 * @return bool True if user has capability, false otherwise
 */
function ufsc_safe_current_user_can($capability, $user_id = null)
{
    // If WordPress hasn't loaded yet, we can't check capabilities
    if (!function_exists('current_user_can')) {
        // Try to include pluggable.php if ABSPATH is defined
        if (defined('ABSPATH') && file_exists(ABSPATH . 'wp-includes/pluggable.php')) {
            require_once ABSPATH . 'wp-includes/pluggable.php';
        }
        
        // If still not available, return false for safety
        if (!function_exists('current_user_can')) {
            return false;
        }
    }
    
    // If user ID is provided, use that; otherwise use current user
    if ($user_id !== null) {
        return user_can($user_id, $capability);
    }
    
    return current_user_can($capability);
}

/**
 * Get WordPress users for club association dropdown
 *
 * @return array Array of user objects with id, login, display_name, and email
 */
function ufsc_get_wordpress_users_for_clubs()
{
    if (!function_exists('get_users')) {
        return [];
    }
    
    $users = get_users([
        'orderby' => 'display_name',
        'order' => 'ASC',
        'fields' => ['ID', 'user_login', 'display_name', 'user_email']
    ]);
    
    return $users;
}

/**
 * Check if a WordPress user is already associated with a club
 *
 * @param int $user_id WordPress user ID
 * @param int $exclude_club_id Optional club ID to exclude from check (for editing)
 * @return bool True if user is already associated with a club
 */
function ufsc_is_user_already_associated($user_id, $exclude_club_id = 0)
{
    if (!$user_id) {
        return false;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    
    $query = "SELECT id FROM {$table_name} WHERE responsable_id = %d";
    $params = [$user_id];
    
    if ($exclude_club_id > 0) {
        $query .= " AND id != %d";
        $params[] = $exclude_club_id;
    }
    
    $existing_club = $wpdb->get_var($wpdb->prepare($query, ...$params));
    
    return !empty($existing_club);
}

/**
 * Get WordPress user info for display
 *
 * @param int $user_id WordPress user ID
 * @return object|null User object with display info or null if not found
 */
function ufsc_get_user_display_info($user_id)
{
    if (!$user_id || !function_exists('get_userdata')) {
        return null;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return null;
    }
    
    return (object) [
        'ID' => $user->ID,
        'login' => $user->user_login,
        'display_name' => $user->display_name,
        'email' => $user->user_email
    ];
}

/**
 * Handle frontend user association during club creation/affiliation
 *
 * @param bool $is_frontend Whether this is a frontend request
 * @return int|null User ID to associate with the club
 */
function ufsc_handle_frontend_user_association($is_frontend)
{
    if (!$is_frontend) {
        // Backend - use direct responsable_id field
        return isset($_POST['responsable_id']) ? intval(wp_unslash($_POST['responsable_id'])) : 0;
    }
    
    // Frontend - handle different association types
    $association_type = isset($_POST['user_association_type']) ? sanitize_text_field(wp_unslash($_POST['user_association_type'])) : 'current';
    
    switch ($association_type) {
        case 'current':
            return get_current_user_id();
            
        case 'create':
            return ufsc_create_user_for_club();
            
        case 'existing':
            $existing_user_id = isset($_POST['existing_user_id']) ? intval(wp_unslash($_POST['existing_user_id'])) : 0;
            if ($existing_user_id > 0) {
                // Validate user exists and is not already associated
                $user_exists = get_userdata($existing_user_id);
                if ($user_exists && !ufsc_is_user_already_associated($existing_user_id, 0)) {
                    return $existing_user_id;
                }
            }
            return null;
            
        default:
            return get_current_user_id();
    }
}

/**
 * Create a new WordPress user for club association
 *
 * @return int|null New user ID or null on failure
 */
function ufsc_create_user_for_club()
{
    // Get and validate user data
    $user_login = isset($_POST['new_user_login']) ? sanitize_user(wp_unslash($_POST['new_user_login'])) : '';
    $user_email = isset($_POST['new_user_email']) ? sanitize_email(wp_unslash($_POST['new_user_email'])) : '';
    $display_name = isset($_POST['new_user_display_name']) ? sanitize_text_field(wp_unslash($_POST['new_user_display_name'])) : '';
    
    if (empty($user_login) || empty($user_email)) {
        return null;
    }
    
    // Check if username or email already exists
    if (username_exists($user_login) || email_exists($user_email)) {
        return null;
    }
    
    // Generate password
    $password = wp_generate_password(12, false);
    
    // Create user
    $user_id = wp_create_user($user_login, $password, $user_email);
    
    if (is_wp_error($user_id)) {
        return null;
    }
    
    // Update display name if provided
    if (!empty($display_name)) {
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $display_name
        ]);
    }
    
    // Send password to user
    wp_new_user_notification($user_id, null, 'both');
    
    return $user_id;
}

/**
 * CORRECTION: Safe URL generation for plugin pages
 * 
 * This function ensures links in the frontend always work, even if page options are not set.
 * It provides fallback URLs and prevents broken navigation buttons.
 * 
 * @param string $page_type Type of page (dashboard, affiliation, club_form, etc.)
 * @param string $fallback_text Text to display if no page is configured
 * @return array Array with 'url' and 'available' keys
 * @since 1.0.2 Safe navigation implementation
 */
function ufsc_get_safe_page_url($page_type, $fallback_text = 'Page non configurée')
{
    $option_map = [
        'dashboard' => 'ufsc_club_dashboard_page_id',
        'affiliation' => 'ufsc_affiliation_page_id', 
        'club_form' => 'ufsc_club_form_page_id',
        'licences' => 'ufsc_licence_page_id',
        'attestations' => 'ufsc_attestation_page_id',
        'ajouter_licencie' => 'ufsc_ajouter_licencie_page_id'
    ];
    
    if (!isset($option_map[$page_type])) {
        return [
            'url' => '#',
            'available' => false,
            'error' => 'Type de page non reconnu'
        ];
    }
    
    $page_id = get_option($option_map[$page_type], 0);

    // Fallback: if club_form is not configured, use affiliation page
    if ($page_type === 'club_form' && (int) $page_id === 0) {
        $page_id = get_option('ufsc_affiliation_page_id', 0);
    }
    
    if ($page_id && get_post_status($page_id) === 'publish') {
        return [
            'url' => get_permalink($page_id),
            'available' => true,
            'error' => ''
        ];
    }
    
    // Return safe fallback
    return [
        'url' => '#',
        'available' => false,
        'error' => $fallback_text . ' (configurez la page dans les réglages UFSC)'
    ];
}

/**
 * CORRECTION: Generate safe navigation button HTML
 * 
 * This function creates navigation buttons that handle missing page configurations gracefully.
 * It prevents broken links and provides helpful feedback to users and administrators.
 * 
 * @param string $page_type Type of page
 * @param string $button_text Button text
 * @param string $button_class CSS classes for the button
 * @param bool $show_error Whether to show error message for missing pages
 * @return string Button HTML
 * @since 1.0.2 Safe navigation button implementation
 */
function ufsc_generate_safe_navigation_button($page_type, $button_text, $button_class = 'ufsc-btn', $show_error = false)
{
    $page_info = ufsc_get_safe_page_url($page_type);
    
    if ($page_info['available']) {
        return '<a href="' . esc_url($page_info['url']) . '" class="' . esc_attr($button_class) . '">' . esc_html($button_text) . '</a>';
    }
    
    // Page not available - show disabled button or error
    if ($show_error || current_user_can('manage_ufsc')) {
        $error_msg = $show_error ? '<p><small class="ufsc-error-text">' . esc_html($page_info['error']) . '</small></p>' : '';
        return '<button class="' . esc_attr($button_class) . ' ufsc-btn-disabled" disabled title="' . esc_attr($page_info['error']) . '">' 
               . esc_html($button_text) . ' (indisponible)</button>' . $error_msg;
    }
    
    return '<button class="' . esc_attr($button_class) . ' ufsc-btn-disabled" disabled>' . esc_html($button_text) . ' (indisponible)</button>';
}

/**
 * CORRECTION: Check if a club has active status for frontend operations
 * 
 * This function provides a standardized way to check if a club is active across all frontend components.
 * It replaces scattered status checks throughout the codebase to ensure consistency.
 * 
 * @param object $club Club object
 * @return bool True if club is active and can perform frontend operations
 * @since 1.0.2 Standardized status checking implementation
 */
function ufsc_is_club_active($club)
{
    if (!$club || !isset($club->statut)) {
        return false;
    }
    
    // CORRECTION: Standardize status checking to use 'Actif' only for frontend consistency
    return $club->statut === 'Actif';
}

/**
 * CORRECTION: Get user-friendly status message for club
 * 
 * This function provides contextual status messages and appropriate actions for each club status.
 * It replaces hardcoded status messages throughout the frontend to improve UX consistency.
 * 
 * @param object $club Club object
 * @param string $context Context for the message (dashboard, licence, documents, etc.)
 * @return array Array with 'message', 'type' (error/warning/info), and optional 'action_url'/'action_text'
 * @since 1.0.2 Centralized status message handling
 */
function ufsc_get_club_status_message($club, $context = 'general')
{
    if (!$club || !isset($club->statut)) {
        return [
            'message' => 'Erreur : informations du club introuvables.',
            'type' => 'error',
            'action_url' => '',
            'action_text' => ''
        ];
    }
    
    $status = $club->statut;
    
    // Active club - no message needed in most contexts
    if ($status === 'Actif') {
        return [
            'message' => '',
            'type' => 'success',
            'action_url' => '',
            'action_text' => ''
        ];
    }
    
    // Status-specific messages
    switch ($status) {
        case 'En cours de création':
        case 'Refusé':
            $affiliation_page = ufsc_get_safe_page_url('affiliation');
            return [
                'message' => 'Votre club n\'est pas encore affilié. Pour accéder à toutes les fonctionnalités, vous devez procéder à l\'affiliation de votre club.',
                'type' => 'warning',
                'action_url' => $affiliation_page['available'] ? $affiliation_page['url'] : '',
                'action_text' => $affiliation_page['available'] ? 'Affilier mon club' : ''
            ];
            
        case 'En attente de validation':
        case 'En cours de validation':
            return [
                'message' => 'Votre demande d\'affiliation est en cours de traitement. Nous avons bien reçu votre demande et votre paiement. Notre équipe est en train d\'étudier votre dossier. Vous recevrez une notification par email dès que votre affiliation sera validée.',
                'type' => 'info',
                'action_url' => '',
                'action_text' => ''
            ];
            
        case 'Inactif':
            return [
                'message' => 'Votre club est actuellement inactif. Pour réactiver votre club et accéder aux fonctionnalités, veuillez contacter l\'administration UFSC.',
                'type' => 'warning',
                'action_url' => 'mailto:admin@ufsc.fr',
                'action_text' => 'Contacter l\'administration'
            ];
            
        default:
            $affiliation_page = ufsc_get_safe_page_url('affiliation');
            return [
                'message' => 'Statut du club : ' . esc_html($status) . '. Pour utiliser cette fonctionnalité, votre club doit avoir le statut "Actif".',
                'type' => 'warning',
                'action_url' => $affiliation_page['available'] ? $affiliation_page['url'] : '',
                'action_text' => $affiliation_page['available'] ? 'Finaliser l\'affiliation' : ''
            ];
    }
}

/**
 * Render a standardized club status alert
 * 
 * @param object $club Club object
 * @param string $context Context for the message
 * @return string HTML for the status alert
 */
function ufsc_render_club_status_alert($club, $context = 'general')
{
    $status_info = ufsc_get_club_status_message($club, $context);
    
    // No message needed
    if (empty($status_info['message'])) {
        return '';
    }
    
    $alert_class = 'ufsc-alert ufsc-alert-' . $status_info['type'];
    $output = '<div class="' . esc_attr($alert_class) . '">';
    
    // Add appropriate title based on type
    switch ($status_info['type']) {
        case 'warning':
            $output .= '<h4>⚠️ Action requise</h4>';
            break;
        case 'info':
            $output .= '<h4>ℹ️ Information</h4>';
            break;
        case 'error':
            $output .= '<h4>❌ Erreur</h4>';
            break;
    }
    
    $output .= '<p>' . esc_html($status_info['message']) . '</p>';
    
    // Add action button if provided
    if (!empty($status_info['action_url']) && !empty($status_info['action_text'])) {
        $button_class = $status_info['type'] === 'warning' ? 'ufsc-btn ufsc-btn-red' : 'ufsc-btn ufsc-btn-outline';
        $output .= '<p><a href="' . esc_url($status_info['action_url']) . '" class="' . esc_attr($button_class) . '">' . esc_html($status_info['action_text']) . '</a></p>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * CORRECTION: Comprehensive frontend access control check
 * 
 * This function centralizes all frontend access control logic including:
 * - User authentication verification
 * - Club association validation 
 * - Error message standardization
 * It replaces duplicate access control code across multiple shortcodes and components.
 * 
 * @param string $context Context for the check (dashboard, licence, documents, etc.)
 * @return array Array with 'allowed' (bool), 'error_message' (string), 'club' (object|null)
 * @since 1.0.2 Unified frontend access control implementation
 */
function ufsc_check_frontend_access($context = 'general')
{
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return [
            'allowed' => false,
            'error_message' => '<div class="ufsc-alert ufsc-alert-error">
                <h4>Connexion requise</h4>
                <p>Vous devez être connecté pour accéder à cette page.</p>
                <p><a href="' . wp_login_url(get_permalink()) . '" class="ufsc-btn">Se connecter</a></p>
                </div>',
            'club' => null
        ];
    }
    
    $user_id = get_current_user_id();
    
    // Check if the helper function exists to avoid fatal errors
    if (!function_exists('ufsc_get_user_club')) {
        return [
            'allowed' => false,
            'error_message' => '<div class="ufsc-alert ufsc-alert-error">
                <h4>Erreur de configuration</h4>
                <p>Erreur de configuration du plugin. Veuillez contacter l\'administrateur.</p>
                </div>',
            'club' => null
        ];
    }
    
    // Get user's club
    $club = ufsc_get_user_club($user_id);
    
    if (!$club) {
        // Use the shared function to prevent duplicate messages, but with safe fallback
        $shortcodes_file = plugin_dir_path(__FILE__) . 'shortcodes.php';
        if (file_exists($shortcodes_file)) {
            require_once $shortcodes_file;
            if (function_exists('ufsc_get_no_club_message')) {
                $error_message = ufsc_get_no_club_message($context);
            } else {
                $error_message = '<div class="ufsc-alert ufsc-alert-error">
                    <p>Vous n\'êtes pas associé à un club.</p>
                    </div>';
            }
        } else {
            $error_message = '<div class="ufsc-alert ufsc-alert-error">
                <p>Vous n\'êtes pas associé à un club.</p>
                </div>';
        }
        
        return [
            'allowed' => false,
            'error_message' => $error_message,
            'club' => null
        ];
    }
    
    return [
        'allowed' => true,
        'error_message' => '',
        'club' => $club
    ];
}

/**

 * Display frontend alert if key pages are not configured
 * 
 * @param string $page_type Type of page to check (dashboard, affiliation, licences, attestations)
 * @return string HTML alert or empty string
 * @since 1.0.3
 */
function ufsc_get_frontend_page_alert($page_type)
{
    $page_info = ufsc_get_safe_page_url($page_type);
    
    if ($page_info['available']) {
        return '';
    }
    
    // Only show alert to logged-in users who might be club administrators
    if (!is_user_logged_in()) {
        return '';
    }
    
    $page_names = [
        'dashboard' => 'Espace Club',
        'affiliation' => 'Affiliation',
        'licences' => 'Licences',
        'attestations' => 'Attestations'
    ];
    
    $page_name = isset($page_names[$page_type]) ? $page_names[$page_type] : $page_type;
    
    $alert_html = '<div class="ufsc-alert ufsc-alert-warning" style="margin: 20px 0; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">';
    $alert_html .= '<p><strong>⚠️ Configuration requise :</strong> ';
    $alert_html .= 'La page ' . esc_html($page_name) . ' n\'est pas configurée. ';
    
    if (current_user_can('manage_ufsc')) {
        $settings_url = admin_url('admin.php?page=ufsc-settings');
        $alert_html .= '<a href="' . esc_url($settings_url) . '">Configurer maintenant</a>';
    } else {
        $alert_html .= 'Contactez l\'administrateur du site.';
    }
    
    $alert_html .= '</p></div>';
    
    return $alert_html;
}

/**
 * Shortcode to display page configuration alert
 * Usage: [ufsc_page_alert type="licences"]
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML alert
 * @since 1.0.3
 */
function ufsc_page_alert_shortcode($atts)
{
    $atts = shortcode_atts([
        'type' => 'dashboard'
    ], $atts, 'ufsc_page_alert');
    
    return ufsc_get_frontend_page_alert($atts['type']);
}
add_shortcode('ufsc_page_alert', 'ufsc_page_alert_shortcode');

/**

 * Display admin notice if pages are not configured
 * 
 * @return void
 * @since 1.0.3
 */
function ufsc_admin_page_configuration_notices()
{
    if (!current_user_can('manage_ufsc')) {
        return;
    }

    $pages_to_check = [
        'ufsc_club_dashboard_page_id' => 'Espace Club (Dashboard)',
        'ufsc_affiliation_page_id' => 'Affiliation',
        'ufsc_licence_page_id' => 'Licences',
        'ufsc_attestation_page_id' => 'Attestations'
    ];

    $unconfigured_pages = [];
    
    foreach ($pages_to_check as $option_name => $page_name) {
        $page_id = get_option($option_name, 0);
        if (!$page_id || get_post_status($page_id) !== 'publish') {
            $unconfigured_pages[] = $page_name;
        }
    }

    if (!empty($unconfigured_pages)) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Plugin UFSC:</strong> Certaines pages ne sont pas configurées : ';
        echo esc_html(implode(', ', $unconfigured_pages));
        echo '. <a href="' . esc_url(admin_url('admin.php?page=ufsc-settings')) . '">Configurer maintenant</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'ufsc_admin_page_configuration_notices');

/**
 * Get the configured WooCommerce affiliation product ID
 * 
 * @return int Product ID for affiliation
 * @since 1.0.3
 */
function ufsc_get_affiliation_product_id()
{
    $product_id = get_option('ufsc_affiliation_product_id', 4823);
    return (int) $product_id;
}

/**
 * Get the configured WooCommerce licence product ID
 * 
 * @return int Product ID for licence
 * @since 1.0.3
 */
function ufsc_get_licence_product_id()
{
    $product_id = get_option('ufsc_licence_product_id', 2934);
    return (int) $product_id;
}

/**
 * ADDED: Payment and Quota Verification Functions
 * 
 * These functions handle license payment verification and quota management
 */

/**
 * Check if a license is paid or included in quota
 *
 * @param int $licence_id License ID
 * @return bool True if license is paid or included
 */
if (!function_exists('ufsc_is_licence_paid')) {
    function ufsc_is_licence_paid($licence_id) {
        $licence_id = (int) $licence_id;
        if (!$licence_id) { 
            return false; 
        }
        
        global $wpdb;

        // Lire la licence
        $lic = $wpdb->get_row($wpdb->prepare(
            "SELECT id, club_id, is_included, order_id, payment_status
             FROM {$wpdb->prefix}ufsc_licences WHERE id = %d",
            $licence_id
        ));
        
        if (!$lic) { 
            return false; 
        }

        // Inclus dans quota
        if ((int)$lic->is_included === 1) { 
            return true; 
        }

        // Commande directement rattachée
        if (!empty($lic->order_id) && function_exists('wc_get_order')) {
            $order = wc_get_order($lic->order_id);
            if ($order && in_array($order->get_status(), ['completed', 'processing'], true)) {
                return true;
            }
        }

        // Recherche via meta d'item si pas d'order_id
        if (empty($lic->order_id)) {
            $found = (int) $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$wpdb->prefix}woocommerce_order_itemmeta oim
                JOIN {$wpdb->prefix}woocommerce_order_items oi ON oim.order_item_id = oi.order_item_id
                JOIN {$wpdb->prefix}posts p ON oi.order_id = p.ID
                WHERE oim.meta_key = 'ufsc_licence_id'
                  AND oim.meta_value = %d
                  AND p.post_status IN ('wc-completed', 'wc-processing')
            ", $licence_id));
            if ($found > 0) { 
                return true; 
            }
        }

        // Statut de paiement manuel éventuel
        if (in_array((string)$lic->payment_status, ['paid', 'completed', 'included'], true)) {
            return true; 
        }

        return false;
    }
}

/**
 * Get club's included license quota
 *
 * @param int $club_id Club ID
 * @return int Number of included licenses
 */
function ufsc_get_club_included_quota($club_id) {
    if (!$club_id) {
        return 0;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    
    $quota = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT quota_licences FROM {$table_name} WHERE id = %d",
            $club_id
        )
    );
    
    return (int) $quota ?: 0;
}

/**
 * Get number of included licenses already used by club
 *
 * @param int $club_id Club ID
 * @return int Number of included licenses used
 */
function ufsc_get_club_included_used($club_id) {
    if (!$club_id) {
        return 0;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_licences';
    
    $used = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE club_id = %d AND is_included = 1",
            $club_id
        )
    );
    
    return (int) $used ?: 0;
}

/**
 * Check if club has remaining included quota
 *
 * @param int $club_id Club ID
 * @return bool True if quota is available
 */
function ufsc_has_included_quota($club_id) {
    $quota = ufsc_get_club_included_quota($club_id);
    $used = ufsc_get_club_included_used($club_id);
    
    return $quota > $used;
}

/**
 * Check if a license should be counted in quota
 * 
 * According to requirements: pending and validated/active licenses count,
 * drafts and refused licenses do not count.
 *
 * @param object|array $licence License object or array with 'statut' field
 * @return bool True if license counts toward quota
 */
function ufsc_is_license_counted_in_quota($licence) {
    if (!$licence) {
        return false;
    }
    
    // Get status from object or array
    $status = is_object($licence) ? ($licence->statut ?? '') : ($licence['statut'] ?? '');
    
    if (empty($status)) {
        return false;
    }
    
    // Normalize status for consistent comparison
    $normalized_status = ufsc_normalize_licence_status($status);
    
    // Only pending and validated licenses count toward quota
    return in_array($normalized_status, ['en_attente', 'validee'], true);
}

/**
 * Get actual quota usage for a club
 * 
 * Counts only licenses with status pending or validated/active.
 * Directors are included in quota (no exemption).
 *
 * @param int $club_id Club ID
 * @return int Number of licenses consuming quota
 */
function ufsc_get_quota_usage($club_id) {
    if (!$club_id) {
        return 0;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_licences';
    
    // Get all licenses for the club
    $licences = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT statut FROM {$table_name} WHERE club_id = %d",
            $club_id
        )
    );
    
    if (!$licences) {
        return 0;
    }
    
    // Count only those that consume quota
    $quota_count = 0;
    foreach ($licences as $licence) {
        if (ufsc_is_license_counted_in_quota($licence)) {
            $quota_count++;
        }
    }
    
    return $quota_count;
}

/**
 * Get remaining quota for a club
 *
 * @param int $club_id Club ID
 * @param int|null $quota_total Total quota limit (if null, gets from club record)
 * @return int Number of remaining licenses in quota (minimum 0)
 */
function ufsc_get_quota_remaining($club_id, $quota_total = null) {
    if (!$club_id) {
        return 0;
    }
    
    // Get quota total if not provided
    if ($quota_total === null) {
        $quota_total = ufsc_get_club_included_quota($club_id);
    }
    
    // If no quota limit set, return 0 (no remaining)
    if ($quota_total <= 0) {
        return 0;
    }
    
    $quota_used = ufsc_get_quota_usage($club_id);
    
    return max(0, $quota_total - $quota_used);
}



/**
 * Rôles autorisés pour une licence UFSC
 * slug => libellé
 */
function ufsc_roles_allowed() {
    return [
        'president'  => __('Président', 'plugin-ufsc-gestion-club-13072025'),
        'tresorier'  => __('Trésorier', 'plugin-ufsc-gestion-club-13072025'),
        'secretaire' => __('Secrétaire', 'plugin-ufsc-gestion-club-13072025'),
        'adherent'   => __('Adhérent', 'plugin-ufsc-gestion-club-13072025'),
        'entraineur' => __('Entraîneur', 'plugin-ufsc-gestion-club-13072025'),
    ];
}

/** Sanitize rôle à partir d'une valeur postée */
function ufsc_sanitize_role($raw) {
    $roles = ufsc_roles_allowed();
    $slug  = sanitize_key($raw);
    return array_key_exists($slug, $roles) ? $slug : 'adherent';
}
