<?php
define('UFSC_PLUGIN_VERSION','20.8.2');
if (!defined('UFSC_ENABLE_DIAG_ENDPOINT')) define('UFSC_ENABLE_DIAG_ENDPOINT', false);

/**
 * Plugin Name: UFSC - Gestion de Club
 * Description: Plugin de gestion des affiliations et licences pour les clubs UFSC.
 * Version: 20.8.2
 * Author: Studio Reactiv
 * Author URI: https://studioreactiv.fr
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ufsc-gestion-club-final
 * Domain Path: /languages
 * Requires at least: 6.6
 * Tested up to: 6.8
 * Requires PHP: 8.2
 * Network: false
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UFSC_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Global helper functions (including ufsc_verify_club_access)
require_once UFSC_PLUGIN_PATH . 'includes/helpers.php';

require_once UFSC_PLUGIN_PATH . 'includes/install/migrations.php';
add_action('plugins_loaded', 'ufsc_run_migrations');
// === Capabilities on activation ===
if (!function_exists('ufsc_add_caps_on_activate')) {
    function ufsc_add_caps_on_activate() {
        $role = get_role('administrator');
        if ($role) {
            foreach (['manage_ufsc', 'ufsc_manage', 'ufsc_manage_own'] as $cap) {
                $role->add_cap($cap);
            }
        }
    }
}
register_activation_hook(__FILE__, 'ufsc_add_caps_on_activate');
// Ensure manage_ufsc capability is available and fallback to manage_options if missing
add_action('init', 'ufsc_ensure_manage_ufsc_cap');
function ufsc_ensure_manage_ufsc_cap() {
    $role = get_role('administrator');
    if ($role && !$role->has_cap('manage_ufsc')) {
        $role->add_cap('manage_ufsc');
    }
    add_filter('user_has_cap', 'ufsc_manage_ufsc_fallback', 0, 3);
}
function ufsc_manage_ufsc_fallback($allcaps, $caps, $args) {
    if (!isset($allcaps['manage_ufsc']) && isset($allcaps['manage_options'])) {
        $allcaps['manage_ufsc'] = $allcaps['manage_options'];
    }
    return $allcaps;
}
// Map legacy capabilities to new ones for backward compatibility
add_filter('user_has_cap', 'ufsc_map_legacy_caps', 10, 3);
function ufsc_map_legacy_caps($allcaps, $caps, $args) {
    $map = [
        'manage_ufsc'          => 'ufsc_manage',
        'manage_ufsc_clubs'    => 'ufsc_manage_own',
        'manage_ufsc_licenses' => 'ufsc_manage_own',
        'manage_ufsc_licences' => 'ufsc_manage_own',
    ];
    foreach ($map as $old => $new) {
        if (isset($allcaps[$new])) {
            $allcaps[$old] = $allcaps[$new];
        }
    }
    return $allcaps;
}
define('UFSC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UFSC_PLUGIN_MAIN_FILE', __FILE__);
if (!defined('UFSC_LICENCE_PRODUCT_ID')) {
    define('UFSC_LICENCE_PRODUCT_ID', 2934);
}

// Professional frontend enhancements - can be disabled by defining UFSC_DISABLE_FRONTEND_PRO as true
if (!defined('UFSC_ENABLE_FRONTEND_PRO')) {
    define('UFSC_ENABLE_FRONTEND_PRO', !defined('UFSC_DISABLE_FRONTEND_PRO') || !UFSC_DISABLE_FRONTEND_PRO);
}

// License form mode - can be set to 'woocommerce' to disable custom form and redirect to WooCommerce only
if (!defined('UFSC_LICENCE_MODE')) {
    define('UFSC_LICENCE_MODE', 'both'); // 'both', 'custom', 'woocommerce'
}

/**
 * Include required files
 */
// Helper classes
require_once UFSC_PLUGIN_PATH . 'includes/helpers/class-ufsc-csv-export.php';
require_once UFSC_PLUGIN_PATH . 'includes/helpers/ufsc-upload-validator.php';
require_once UFSC_PLUGIN_PATH . 'includes/helpers/attestations-helper.php';
require_once UFSC_PLUGIN_PATH . 'includes/helpers/security.php';
require_once UFSC_PLUGIN_PATH . 'includes/helpers/club-permissions.php';

// Compatibility shims
require_once UFSC_PLUGIN_PATH . 'includes/compat/monetico-compat.php';
require_once UFSC_PLUGIN_PATH . 'includes/compat/wc-id-reconciliation.php';

// Core files
require_once UFSC_PLUGIN_PATH . 'includes/core/class-gestionclub-core.php';
require_once UFSC_PLUGIN_PATH . 'includes/clubs/class-club-manager.php';

// Admin files
require_once UFSC_PLUGIN_PATH . 'includes/admin/class-dashboard.php';
require_once UFSC_PLUGIN_PATH . 'includes/admin/class-menu.php';
require_once UFSC_PLUGIN_PATH . 'includes/admin/class-document-manager.php';
require_once UFSC_PLUGIN_PATH . 'includes/admin/class-frontend-pro-settings.php';
require_once UFSC_PLUGIN_PATH . 'includes/admin/class-ufsc-admin-settings.php';
require_once UFSC_PLUGIN_PATH . 'includes/admin/class-sync-monitor.php';

// Include test file for development
if (WP_DEBUG) {
    require_once UFSC_PLUGIN_PATH . 'includes/admin/test-sync.php';
    require_once UFSC_PLUGIN_PATH . 'includes/tests/director-fields-test.php';
    require_once UFSC_PLUGIN_PATH . 'includes/tests/database-schema-test.php';
    require_once UFSC_PLUGIN_PATH . 'includes/tests/user-club-association-enhancement-test.php';
    require_once UFSC_PLUGIN_PATH . 'includes/tests/admin-settings-test.php';
    require_once UFSC_PLUGIN_PATH . 'includes/tests/frontend-refonte-test.php';
    require_once UFSC_PLUGIN_PATH . 'includes/tests/woocommerce-ecommerce-test.php';
}

// Include database validator for admin use
if (is_admin()) {
    require_once UFSC_PLUGIN_PATH . 'includes/admin/database-validator.php';
    require_once UFSC_PLUGIN_PATH . 'includes/admin/user-profile-enhancement.php';
    require_once UFSC_PLUGIN_PATH . 'includes/admin/licence-validation.php';
}

// Page creator - needed for activation hook
require_once UFSC_PLUGIN_PATH . 'includes/admin/ufsc-page-creator.php';

// Frontend files
$file = UFSC_PLUGIN_PATH . 'includes/frontend/frontend-club-dashboard.php';
if (file_exists($file)) {
    require_once $file;
} else {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('UFSC Gestion Club: missing file ' . $file);
    }
}
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/club-form-shortcode.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/affiliation-form-shortcode.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/licence-button-shortcode.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/club-menu-shortcode.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/login-register-shortcode.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/recent-licences-shortcode.php';

// New modular club dashboard
require_once UFSC_PLUGIN_PATH . 'includes/frontend/club/dashboard.php';

// New shortcodes for UFSC club management
require_once UFSC_PLUGIN_PATH . 'includes/shortcodes.php';

// Frontend attestation shortcodes and AJAX handlers
require_once UFSC_PLUGIN_PATH . 'includes/shortcodes-attestations.php';
require_once UFSC_PLUGIN_PATH . 'includes/ajax-handlers.php';

// AJAX handler for adding licences to cart
require_once UFSC_PLUGIN_PATH . 'includes/licences/ajax-add-to-cart.php';

// WooCommerce Integration
require_once UFSC_PLUGIN_PATH . 'includes/frontend/affiliation-woocommerce.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/woocommerce-licence-form.php';

// New WooCommerce integration for frontend refonte
require_once UFSC_PLUGIN_PATH . 'includes/frontend/woocommerce-affiliation-form.php';

// New helper files for frontend refonte
require_once UFSC_PLUGIN_PATH . 'includes/helpers/helpers-licence-status.php';
require_once UFSC_PLUGIN_PATH . 'includes/helpers/helpers-product-buttons.php';

// New frontend shortcodes
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/new-frontend-shortcodes.php';

// Dashboard overview shortcode
require_once UFSC_PLUGIN_PATH . 'includes/frontend/dashboard/overview.php';

// New WooCommerce integration class (consolidated)
if (file_exists(UFSC_PLUGIN_PATH . 'includes/class-ufsc-woocommerce-integration.php')) {
    require_once UFSC_PLUGIN_PATH . 'includes/class-ufsc-woocommerce-integration.php';
}

// Load cart item role metadata handling
if (file_exists(UFSC_PLUGIN_PATH . 'includes/woocommerce/cart-item-role-meta.php')) {
    require_once UFSC_PLUGIN_PATH . 'includes/woocommerce/cart-item-role-meta.php';
}

// New WooCommerce e-commerce features
if (file_exists(UFSC_PLUGIN_PATH . 'includes/woocommerce/auto-pack-affiliation.php')) {
    require_once UFSC_PLUGIN_PATH . 'includes/woocommerce/auto-pack-affiliation.php';
}

if (file_exists(UFSC_PLUGIN_PATH . 'includes/woocommerce/auto-order-admin-licences.php')) {
    require_once UFSC_PLUGIN_PATH . 'includes/woocommerce/auto-order-admin-licences.php';
}

// Frontend shortcodes
if (file_exists(UFSC_PLUGIN_PATH . 'includes/shortcodes-front.php')) {
    require_once UFSC_PLUGIN_PATH . 'includes/shortcodes-front.php';
}

/**
 * Initialize admin components.
 * This includes menu registration and document management.
 */
// Licences direct shortcode & ajax (added)
if (file_exists(UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/licenses-direct.php')) {
    require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/licenses-direct.php';
}
if (file_exists(UFSC_PLUGIN_PATH . 'includes/frontend/ajax/licenses-direct.php')) {
    require_once UFSC_PLUGIN_PATH . 'includes/frontend/ajax/licenses-direct.php';
}

/**
 * Bootstrap admin functionality by instantiating required classes.
 */
function ufsc_admin_bootstrap() {
    new UFSC_Menu();
    UFSC_Document_Manager::get_instance();
}
add_action('admin_init', 'ufsc_admin_bootstrap');

    /**
     * Load text domain for translations
     */
    function ufsc_gestion_club_load_textdomain() {
        load_plugin_textdomain(
            'plugin-ufsc-gestion-club-13072025',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
add_action('init', 'ufsc_gestion_club_load_textdomain');

/**
 * Initialize the plugin core after WordPress loads
 */
add_action('plugins_loaded', ['UFSC_GestionClub_Core', 'init']);

/**
 * Enqueue admin scripts and styles
 */
function ufsc_gestion_club_admin_enqueue_scripts($hook)
{
    // Check if we're on one of our plugin pages or specific edit pages
    $is_ufsc_page = false;
    
    if (is_string($hook) && strpos($hook, 'ufsc-') !== false) {
        $is_ufsc_page = true;
    }
    
    // Also check for specific page parameters
    $page = isset($_GET['page']) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // Always sanitize GET input
    if ($page && strpos((string) $page, 'ufsc') !== false) {
        $is_ufsc_page = true;
    }
    
    if (!$is_ufsc_page) {
        return;
    }

    wp_enqueue_style(
        'ufsc-admin-style',
        UFSC_PLUGIN_URL . 'assets/css/admin.css',
        [],
        UFSC_PLUGIN_VERSION
    );
    
    // Enqueue admin fixes CSS
    wp_enqueue_style(
        'ufsc-admin-fixes',
        UFSC_PLUGIN_URL . 'assets/css/admin-fixes.css',
        ['ufsc-admin-style'],
        UFSC_PLUGIN_VERSION
    );

    // Enqueue WordPress media scripts on edit club page
    if ('ufsc_edit_club' === $page) {
        wp_enqueue_media();
    }

    // Enqueue Chart.js - REMOVED: CDN not allowed
    // wp_enqueue_script(
    //     'chartjs',
    //     'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js',
    //     [],
    //     '4.4.0',
    //     true
    // );

    wp_enqueue_script(
        'ufsc-admin-script',
        UFSC_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery'],
        UFSC_PLUGIN_VERSION,
        true
    );

    // Enqueue attestations script for admin pages
    if ($is_ufsc_page) {
        wp_enqueue_script(
            'ufsc-attestations',
            UFSC_PLUGIN_URL . 'assets/js/ufsc-attestations.js',
            ['jquery'],
            UFSC_PLUGIN_VERSION,
            true
        );
        
        // Localize attestations script
        wp_localize_script('ufsc-attestations', 'ufscAttestations', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'uploadClubNonce' => wp_create_nonce('ufsc_upload_club_attestation'),
            'deleteClubNonce' => wp_create_nonce('ufsc_delete_club_attestation'),
            'uploadLicenceNonce' => wp_create_nonce('ufsc_upload_licence_attestation'),
            'deleteLicenceNonce' => wp_create_nonce('ufsc_delete_licence_attestation'),
            'can_manage' => current_user_can('ufsc_manage')
        ]);
    }

    // Localize script for dashboard data
    if ($is_ufsc_page) {
        wp_localize_script('ufsc-admin-script', 'ufsc_dashboard_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ufsc_dashboard_nonce')
        ]);
    }
}
add_action('admin_enqueue_scripts', 'ufsc_gestion_club_admin_enqueue_scripts');

/**
 * AJAX handlers for admin actions
 */
// Delete club AJAX handler
add_action('wp_ajax_ufsc_delete_club', 'ufsc_handle_delete_club');
/**
 * Handle club deletion via AJAX
 * Requires admin privileges and nonce verification
 */
function ufsc_handle_delete_club() {
    // Verify nonce
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'ufsc_delete_club')) {
        wp_die(__('Security check failed.', 'plugin-ufsc-gestion-club-13072025'));
    }
    
    // Check permissions
    if (!current_user_can('ufsc_manage')) {
        wp_send_json_error(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'));
        return;
    }
    
    $club_id = isset($_POST['club_id']) ? absint(wp_unslash($_POST['club_id'])) : 0;
    if (!$club_id) {
        wp_send_json_error(__('Invalid request.', 'plugin-ufsc-gestion-club-13072025'));
        return;
    }
    
    $club_manager = UFSC_Club_Manager::get_instance();
    
    if ($club_manager->delete_club($club_id)) {
        wp_send_json_success(__('Club deleted successfully.', 'plugin-ufsc-gestion-club-13072025'));
    } else {
        wp_send_json_error(__('Failed to delete club.', 'plugin-ufsc-gestion-club-13072025'));
    }
}

// Club validation AJAX handler - connects to Document Manager's validate_club() method
// This hook validates clubs by checking required documents and updating status to 'valide'
// Handles permissions, nonce verification, document validation, and status updates
add_action('wp_ajax_ufsc_validate_club', array(UFSC_Document_Manager::get_instance(), 'validate_club'));

// Delete license AJAX handler
add_action('wp_ajax_ufsc_delete_licence', 'ufsc_handle_delete_licence');
/**
 * Handle license deletion via AJAX
 * Requires admin privileges and nonce verification
 */
function ufsc_handle_delete_licence() {
    // Verify nonce first
    check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce');

    if (!current_user_can('ufsc_manage_own')) {
        wp_send_json_error(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'), 403);
    }

    $licence_id = isset($_POST['licence_id']) ? absint(wp_unslash($_POST['licence_id'])) : 0;
    if (!$licence_id) {
        wp_send_json_error(__('Invalid request.', 'plugin-ufsc-gestion-club-13072025'));
    }

    ufscsn_require_manage_licence($licence_id);

    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $licence_manager = new UFSC_Licence_Manager();

    if ($licence_manager->delete_licence($licence_id)) {
        wp_send_json_success(__('License deleted successfully.', 'plugin-ufsc-gestion-club-13072025'));
    } else {
        wp_send_json_error(__('Failed to delete license.', 'plugin-ufsc-gestion-club-13072025'));
    }
}

// Restore licence AJAX handler
add_action('wp_ajax_ufsc_restore_licence', 'ufsc_handle_restore_licence');
function ufsc_handle_restore_licence() {
    check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce');

    if (!current_user_can('ufsc_manage_own')) {
        wp_send_json_error(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'), 403);
    }

    $licence_id = isset($_POST['licence_id']) ? absint(wp_unslash($_POST['licence_id'])) : 0;
    if (!$licence_id) {
        wp_send_json_error(__('Invalid request.', 'plugin-ufsc-gestion-club-13072025'));
    }

    ufscsn_require_manage_licence($licence_id);

    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $licence_manager = new UFSC_Licence_Manager();

    if ($licence_manager->update_licence_status($licence_id, 'en_attente')) {
        wp_send_json_success(__('Licence restored successfully.', 'plugin-ufsc-gestion-club-13072025'));
    } else {
        wp_send_json_error(__('Failed to restore licence.', 'plugin-ufsc-gestion-club-13072025'));
    }
}

// Change license status AJAX handler
add_action('wp_ajax_ufsc_change_licence_status', 'ufsc_handle_change_licence_status');
function ufsc_handle_change_licence_status() {
    // Verify nonce first
    check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce');

    if (!current_user_can('ufsc_manage_own')) {
        wp_send_json_error(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'), 403);
    }

    $licence_id = isset($_POST['licence_id']) ? absint(wp_unslash($_POST['licence_id'])) : 0;
    $new_status = isset($_POST['new_status']) ? sanitize_text_field(wp_unslash($_POST['new_status'])) : '';
    $reason = isset($_POST['reason']) ? sanitize_textarea_field(wp_unslash($_POST['reason'])) : '';

    if (!$licence_id) {
        wp_send_json_error(__('Invalid request.', 'plugin-ufsc-gestion-club-13072025'));
    }

    ufscsn_require_manage_licence($licence_id);

    // Validate status
    $valid_statuses = ['brouillon', 'en_attente', 'validee', 'refusee'];
    if (!in_array($new_status, $valid_statuses)) {
        wp_send_json_error(__('Invalid status.', 'plugin-ufsc-gestion-club-13072025'));
    }

    if (!class_exists('UFSC_Licence_Repository')) {
        require_once UFSC_PLUGIN_PATH . 'includes/repository/class-licence-repository.php';
    }

    $user = wp_get_current_user();
    $repo = new UFSC_Licence_Repository();
    $updated = $repo->update_status($licence_id, $new_status, $reason, $user->display_name);

    if ($updated) {
        wp_send_json_success([
            'message' => __('Statut mis à jour avec succès.', 'plugin-ufsc-gestion-club-13072025'),
            'licence' => $updated,
        ]);
    } else {
        wp_send_json_error(__('Échec de la mise à jour du statut.', 'plugin-ufsc-gestion-club-13072025'));
    }
}

// AJAX handlers for licence validation/rejection without payment dependency  
add_action('wp_ajax_ufsc_validate_licence', 'ufsc_handle_validate_licence');
if (!function_exists('ufsc_handle_validate_licence')) {
    function ufsc_handle_validate_licence() {
        // Verify nonce first
        if (!check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'plugin-ufsc-gestion-club-13072025'), 403);
        }

        if (!current_user_can('ufsc_manage_own')) {
            wp_send_json_error(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'), 403);
        }
        
        $licence_id = isset($_POST['licence_id']) ? absint(wp_unslash($_POST['licence_id'])) : 0;
        
        if (!$licence_id) {
            wp_send_json_error(__('Invalid licence ID.', 'plugin-ufsc-gestion-club-13072025'));
        }

        $licence = ufscsn_require_manage_licence($licence_id);

        global $wpdb;
        $licences_table = $wpdb->prefix . 'ufsc_licences';
        
        // Update licence status to validated (regardless of payment status)
        $result = $wpdb->update(
            $licences_table,
            [
                'statut' => 'validee',
                'date_modification' => current_time('mysql')
            ],
            ['id' => $licence_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ($result !== false) {
            // Log the validation action
            $user = wp_get_current_user();
            $log_data = [
                'licence_id' => $licence_id,
                'action' => 'validated',
                'licence_name' => $licence->prenom . ' ' . $licence->nom,
                'validated_by' => $user->display_name,
                'timestamp' => current_time('mysql')
            ];
            
            // Store audit log
            update_option('ufsc_licence_validation_log_' . $licence_id . '_' . time(), $log_data);
            
            wp_send_json_success([
                'message' => sprintf(__('Licence for %s %s validated successfully.', 'plugin-ufsc-gestion-club-13072025'), 
                                    $licence->prenom, $licence->nom),
                'new_status' => 'validee'
            ]);
        } else {
            wp_send_json_error(__('Failed to validate licence.', 'plugin-ufsc-gestion-club-13072025'));
        }
    }
}

add_action('wp_ajax_ufsc_reject_licence', 'ufsc_handle_reject_licence');
function ufsc_handle_reject_licence() {
    // Verify nonce first
    if (!check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce', false)) {
        wp_send_json_error(__('Security check failed.', 'plugin-ufsc-gestion-club-13072025'), 403);
    }

    if (!current_user_can('ufsc_manage_own')) {
        wp_send_json_error(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'), 403);
    }

    $licence_id = isset($_POST['licence_id']) ? absint(wp_unslash($_POST['licence_id'])) : 0;
    $reason = isset($_POST['reason']) ? sanitize_textarea_field(wp_unslash($_POST['reason'])) : '';

    if (!$licence_id) {
        wp_send_json_error(__('Invalid licence ID.', 'plugin-ufsc-gestion-club-13072025'));
    }

    $licence = ufscsn_require_manage_licence($licence_id);

    global $wpdb;
    $licences_table = $wpdb->prefix . 'ufsc_licences';

    // Update licence status to refused
    $result = $wpdb->update(
        $licences_table,
        [
            'statut' => 'refusee',
            'date_modification' => current_time('mysql'),
            'note_refus' => $reason
        ],
        ['id' => $licence_id],
        ['%s', '%s', '%s'],
        ['%d']
    );

    if ($result !== false) {
        // Log the rejection action
        $user = wp_get_current_user();
        $log_data = [
            'licence_id' => $licence_id,
            'action' => 'rejected',
            'licence_name' => $licence->prenom . ' ' . $licence->nom,
            'reason' => $reason,
            'rejected_by' => $user->display_name,
            'timestamp' => current_time('mysql')
        ];

        // Store audit log
        update_option('ufsc_licence_rejection_log_' . $licence_id . '_' . time(), $log_data);

        wp_send_json_success([
            'message' => sprintf(__('Licence for %s %s rejected successfully.', 'plugin-ufsc-gestion-club-13072025'),
                                $licence->prenom, $licence->nom),
            'new_status' => 'refusee'
        ]);
    } else {
        wp_send_json_error(__('Failed to reject licence.', 'plugin-ufsc-gestion-club-13072025'));
    }
}

// Add/Update club AJAX handler
add_action('wp_ajax_ufsc_save_club', 'ufsc_handle_save_club_ajax');
add_action('wp_ajax_nopriv_ufsc_save_club', 'ufsc_handle_save_club_ajax');
function ufsc_handle_save_club_ajax() {
    // Log the request for debugging
    ufsc_log_operation('club_save_start', [
        'user_id' => get_current_user_id(),
        'timestamp' => current_time('mysql'),
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
        'data_received' => !empty($_POST)
    ]);

    // Verify nonce
    if (!isset($_POST['ufsc_club_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ufsc_club_nonce']), 'ufsc_save_club')) {
        ufsc_log_operation('club_save_error', ['error' => 'Nonce verification failed']);
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'error_code' => 'NONCE_FAILED'
        ]);
        return;
    }

    // Get and validate club data
    $club_data = ufsc_validate_club_data($_POST);
    if (is_wp_error($club_data)) {
        ufsc_log_operation('club_save_error', [
            'error' => 'Validation failed',
            'errors' => $club_data->get_error_messages()
        ]);
        wp_send_json_error([
            'message' => 'Données invalides.',
            'errors' => $club_data->get_error_messages(),
            'error_code' => 'VALIDATION_FAILED'
        ]);
        return;
    }

    $club_manager = UFSC_Club_Manager::get_instance();
    $is_edit = isset($_POST['club_id']) && intval($_POST['club_id']) > 0;
    $club_id = $is_edit ? intval($_POST['club_id']) : 0;

    try {
        if ($is_edit) {
            // Only allow club update if the user can manage all clubs or owns this club
            if (!current_user_can('ufsc_manage') && !ufsc_verify_club_access($club_id)) {
                ufsc_log_operation('club_save_error', [
                    'error'    => 'unauthorized_update_attempt',
                    'club_id'  => $club_id,
                    'user_id'  => get_current_user_id()
                ]);

                wp_send_json_error([
                    'message'    => __('Accès non autorisé.', 'plugin-ufsc-gestion-club-13072025'),
                    'error_code' => 'UNAUTHORIZED'
                ]);
                return;
            }

            $result = $club_manager->update_club($club_id, $club_data);
            $operation = 'update';
        } else {
            $club_data['date_creation'] = current_time('mysql');
            $result = $club_manager->add_club($club_data);
            $club_id = $result;
            $operation = 'create';
        }

        if ($result) {
            // Log successful operation
            ufsc_log_operation('club_save_success', [
                'operation' => $operation,
                'club_id' => $club_id,
                'club_name' => $club_data['nom'] ?? '',
                'user_id' => get_current_user_id()
            ]);

            // Handle file uploads if any
            $upload_results = ufsc_handle_club_file_uploads($club_id, $_FILES);
            
            // Get fresh club data from database to ensure synchronization
            $fresh_club_data = $club_manager->get_club($club_id);
            
            $response_data = [
                'message' => $is_edit ? 'Club mis à jour avec succès.' : 'Club créé avec succès.',
                'club_id' => $club_id,
                'club_data' => $fresh_club_data,
                'operation' => $operation,
                'upload_results' => $upload_results,
                'timestamp' => current_time('mysql')
            ];

            // Send notification email for new clubs
            if (!$is_edit && !is_admin()) {
                ufsc_send_club_notification_email($fresh_club_data);
            }

            wp_send_json_success($response_data);
        } else {
            ufsc_log_operation('club_save_error', [
                'error' => 'Database operation failed',
                'operation' => $operation,
                'club_data' => $club_data
            ]);
            wp_send_json_error([
                'message' => 'Erreur lors de l\'enregistrement du club.',
                'error_code' => 'DATABASE_ERROR'
            ]);
        }
    } catch (Exception $e) {
        ufsc_log_operation('club_save_error', [
            'error' => 'Exception occurred',
            'exception_message' => $e->getMessage(),
            'exception_trace' => $e->getTraceAsString()
        ]);
        wp_send_json_error([
            'message' => 'Une erreur inattendue s\'est produite.',
            'error_code' => 'UNEXPECTED_ERROR'
        ]);
    }
}

// Get club data AJAX handler for real-time refresh
add_action('wp_ajax_ufsc_get_club_data', 'ufsc_handle_get_club_data');
add_action('wp_ajax_nopriv_ufsc_get_club_data', 'ufsc_handle_get_club_data');
function ufsc_handle_get_club_data() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ufsc_get_club_data')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    $club_id = ufscsn_resolve_club_id_sanitized();
    if (!$club_id) {
        wp_send_json_error(['message' => 'Invalid club ID']);
        return;
    }

    $club_manager = UFSC_Club_Manager::get_instance();
    $club_data = $club_manager->get_club($club_id);

    if ($club_data) {
        wp_send_json_success([
            'club_data' => $club_data,
            'timestamp' => current_time('mysql')
        ]);
    } else {
        wp_send_json_error(['message' => 'Club not found']);
    }
}

// Get all clubs data AJAX handler for list refresh
add_action('wp_ajax_ufsc_get_clubs_list', 'ufsc_handle_get_clubs_list');
function ufsc_handle_get_clubs_list() {
    // Check permissions
    if (!current_user_can('ufsc_manage')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }

    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'ufsc_get_clubs_list')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    $club_manager = UFSC_Club_Manager::get_instance();
    $clubs = $club_manager->get_clubs();

    wp_send_json_success([
        'clubs' => $clubs,
        'count' => count($clubs),
        'timestamp' => current_time('mysql')
    ]);
}

/**
 * Enqueue frontend scripts and styles
 */
function ufsc_gestion_club_enqueue_scripts()
{
    $frontend_style = ufsc_get_asset('frontend.css');
    wp_enqueue_style(
        'ufsc-frontend-style',
        $frontend_style['url'],
        [],
        $frontend_style['version']
    );

    // Enqueue new responsive frontend CSS
    $frontend_css = ufsc_get_asset('ufsc-frontend.css');
    if (file_exists($frontend_css['path'])) {
        wp_enqueue_style(
            'ufsc-frontend-responsive',
            $frontend_css['url'],
            ['ufsc-frontend-style'],
            $frontend_css['version']
        );
    }

    $frontend_script = ufsc_get_asset('frontend.js');
    wp_enqueue_script(
        'ufsc-frontend-script',
        $frontend_script['url'],
        ['jquery'],
        $frontend_script['version'],
        true
    );

    // Enqueue charts script for statistics
    wp_enqueue_script(
        'ufsc-charts-script',
        UFSC_PLUGIN_URL . 'assets/js/ufsc-charts.js',
        ['jquery'],
        UFSC_PLUGIN_VERSION,
        true
    );

    // Localize script with AJAX configuration
    wp_localize_script('ufsc-frontend-script', 'ufsc_frontend_config', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ufsc_frontend_nonce'),
        'can_manage' => current_user_can('ufsc_manage'),
        'messages' => [
            'loading' => __('Chargement...', 'plugin-ufsc-gestion-club-13072025'),
            'success' => __('Opération réussie', 'plugin-ufsc-gestion-club-13072025'),
            'error' => __('Une erreur est survenue', 'plugin-ufsc-gestion-club-13072025'),
            'network_error' => __('Erreur de connexion', 'plugin-ufsc-gestion-club-13072025'),
            'validation_error' => __('Veuillez corriger les erreurs', 'plugin-ufsc-gestion-club-13072025'),
        ],
        'debug_mode' => WP_DEBUG
    ]);

    // Enqueue club form enhancements on pages that may have club forms
    ufsc_enqueue_form_enhancements();

    // Enqueue professional frontend enhancements if enabled
    if (UFSC_ENABLE_FRONTEND_PRO && class_exists('UFSC_Frontend_Pro_Settings') && UFSC_Frontend_Pro_Settings::is_enabled()) {
        ufsc_enqueue_frontend_pro_assets();
    }
}

/**
 * Enqueue club form enhancement assets
 */
function ufsc_enqueue_form_enhancements()
{
    global $post;
    
    // Only enqueue on pages that may contain club forms
    $should_enqueue = false;
    
    // Check if we're in admin and on a club page
    $page = isset($_GET['page']) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // Always sanitize GET input
    if (is_admin() && $page && strpos((string) $page, 'ufsc') !== false) {
        $should_enqueue = true;
    }
    
    // Check if we're on a page with club form shortcodes
    if (is_a($post, 'WP_Post') && (
        has_shortcode($post->post_content, 'ufsc_club_form') ||
        has_shortcode($post->post_content, 'ufsc_affiliation_form') ||
        strpos((string) ($post->post_content ?? ''), 'ufsc_render_club_form') !== false
    )) {
        $should_enqueue = true;
    }
    
    // Also enqueue on typical club/affiliation page slugs
    if (is_page() && in_array($post->post_name, ['club', 'affiliation', 'clubs', 'adhesion', 'inscription'])) {
        $should_enqueue = true;
    }
    
    if ($should_enqueue) {
        wp_enqueue_style(
            'ufsc-form-enhancements-style',
            UFSC_PLUGIN_URL . 'assets/css/form-enhancements.css',
            ['ufsc-frontend-style'],
            UFSC_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'ufsc-form-enhancements-script',
            UFSC_PLUGIN_URL . 'assets/js/form-enhancements.js',

            ['jquery', 'ufsc-frontend-script'],
            UFSC_PLUGIN_VERSION,

            ['ufsc-frontend-script'],
            UFSC_GESTION_CLUB_VERSION,

            true
        );
        wp_script_add_data('ufsc-form-enhancements-script', 'type', 'module');
        
        // Localize script with configuration
        wp_localize_script('ufsc-form-enhancements-script', 'ufsc_form_config', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ufsc_form_enhancements_nonce'),
            'messages' => [
                'loading' => __('Chargement...', 'plugin-ufsc-gestion-club-13072025'),
                'uploading' => __('Téléchargement en cours...', 'plugin-ufsc-gestion-club-13072025'),
                'validating' => __('Validation...', 'plugin-ufsc-gestion-club-13072025'),
                'success' => __('Succès !', 'plugin-ufsc-gestion-club-13072025'),
                'error' => __('Erreur', 'plugin-ufsc-gestion-club-13072025')
            ]
        ]);
    }
}

/**
 * Enqueue professional frontend assets and dependencies
 */
function ufsc_enqueue_frontend_pro_assets()
{
    // External libraries removed for WordPress.org compliance
    // CDN assets are not allowed - functionality may be limited
    
    // Enqueue our professional assets
    wp_enqueue_style(
        'ufsc-frontend-pro-style',
        UFSC_PLUGIN_URL . 'assets/css/frontend-pro.css',
        ['ufsc-frontend-style'],
        UFSC_PLUGIN_VERSION
    );

    // Enqueue enhanced license form styles
    wp_enqueue_style(
        'ufsc-licence-form-enhanced',
        UFSC_PLUGIN_URL . 'assets/css/licence-form-enhanced.css',
        ['ufsc-frontend-pro-style'],
        UFSC_PLUGIN_VERSION
    );

    // Enqueue new multi-licence styles
    wp_enqueue_style(
        'ufsc-licence-styles',
        UFSC_PLUGIN_URL . 'assets/css/ufsc-licence.css',
        ['ufsc-frontend-pro-style'],
        UFSC_PLUGIN_VERSION
    );

    // Enqueue dashboard fixes for UI improvements
    wp_enqueue_style(
        'ufsc-dashboard-fixes',
        UFSC_PLUGIN_URL . 'assets/css/ufsc-dashboard-fixes.css',
        ['ufsc-licence-styles'],
        UFSC_PLUGIN_VERSION
    );

    wp_enqueue_script(
        'ufsc-frontend-pro-script',
        plugins_url('assets/js/frontend-pro.js', __FILE__),
        ['jquery', 'ufsc-frontend-script'],
        UFSC_PLUGIN_VERSION,
        true
    );

    // Enqueue multi-licence script
    wp_enqueue_script(
        'ufsc-licence-multi',
        UFSC_PLUGIN_URL . 'assets/js/ufsc-licence-multi.js',
        ['jquery', 'ufsc-frontend-pro-script'],
        UFSC_PLUGIN_VERSION,
        true
    );

    // Localize script with configuration
    wp_localize_script('ufsc-frontend-pro-script', 'ufsc_frontend_pro_config', [
        'enabled' => true,
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ufsc_frontend_pro_nonce'),
        'messages' => [
            'loading' => __('Chargement...', 'plugin-ufsc-gestion-club-13072025'),
            'success' => __('Opération réussie', 'plugin-ufsc-gestion-club-13072025'),
            'error' => __('Une erreur est survenue', 'plugin-ufsc-gestion-club-13072025'),
        ]
    ]);

    // Localize multi-licence script
    wp_localize_script('ufsc-licence-multi', 'ufscLicenceConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonces' => [
            'licence_draft' => wp_create_nonce('ufsc_licence_draft_nonce'),
            'add_licencie' => wp_create_nonce('ufsc_add_licencie_nonce')
        ],
        'messages' => [
            'loading' => __('Chargement...', 'plugin-ufsc-gestion-club-13072025'),
            'success' => __('Opération réussie', 'plugin-ufsc-gestion-club-13072025'),
            'error' => __('Une erreur est survenue', 'plugin-ufsc-gestion-club-13072025'),
        ],
        'licenceProductUrl' => get_permalink(ufsc_get_licence_product_id())
    ]);

    // Add global variable to enable features
    wp_add_inline_script('ufsc-frontend-pro-script', 'var ufsc_frontend_pro_enabled = true;', 'before');
}
add_action('wp_enqueue_scripts', 'ufsc_gestion_club_enqueue_scripts');

/**
 * Get frontend page IDs for conditional enqueuing
 * 
 * @return array Array of page IDs
 * @since 1.3.0
 */
function ufsc_get_front_page_ids() {
    $page_ids = array();
    
    $option_keys = array(
        'ufsc_club_dashboard_page_id',
        'ufsc_affiliation_page_id', 
        'ufsc_club_account_page_id',
        'ufsc_licence_page_id',
        'ufsc_ajouter_licencie_page_id',
        'ufsc_demander_licence_page_id',
        'ufsc_attestation_page_id',
        'ufsc_liste_clubs_page_id',
        'ufsc_login_page_id'
    );
    
    foreach ($option_keys as $key) {
        $page_id = get_option($key, 0);
        if ($page_id && get_post_status($page_id) === 'publish') {
            $page_ids[] = $page_id;
        }
    }
    
    return $page_ids;
}

/**
 * Check if current page is a plugin frontend page
 * 
 * @return bool True if current page is a plugin page
 * @since 1.3.0
 */
function ufsc_is_plugin_front_page() {
    if (!is_page()) {
        return false;
    }
    
    global $post;
    if (!$post) {
        return false;
    }
    
    $plugin_page_ids = ufsc_get_front_page_ids();
    return in_array($post->ID, $plugin_page_ids);
}

/**
 * Enqueue frontend assets conditionally
 * 
 * Enqueues CSS only on plugin pages or when relevant shortcodes are detected
 * 
 * @since 1.3.0
 */
function ufsc_enqueue_frontend_assets() {
    $should_enqueue = false;
    
    // Check if we're on a plugin page
    if (ufsc_is_plugin_front_page()) {
        $should_enqueue = true;
    }
    
    // Check if current post contains relevant shortcodes
    global $post;
    if (is_a($post, 'WP_Post')) {
        $shortcodes_to_check = array(
            'ufsc_login_register',
            'ufsc_recent_licences',
            'ufsc_club_menu',
            'ufsc_club_dashboard',
            'ufsc_club_form',
            'ufsc_affiliation_form',
            'ufsc_licence_button',
            'ufsc_club_account',
            'ufsc_club_licences',
            'ufsc_ajouter_licencie',
            'ufsc_attestation_form',
            'ufsc_liste_clubs',
            // New shortcodes from frontend refonte
            'ufsc_licence_form',
            'ufsc_club_quota',
            'ufsc_club_stats', 
            'ufsc_license_list'
        );
        
        foreach ($shortcodes_to_check as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $should_enqueue = true;
                break;
            }
        }
    }
    
    if ($should_enqueue) {
        $frontend_css = ufsc_get_asset('ufsc-frontend.css');
        wp_register_style(
            'ufsc-frontend',
            $frontend_css['url'],
            array(),
            $frontend_css['version']
        );
        wp_enqueue_style('ufsc-frontend');

        // Enqueue frontend JavaScript for forms and interactions
        $frontend_js = ufsc_get_asset('ufsc-frontend.js');
        wp_enqueue_script(
            'ufsc-frontend-js',
            $frontend_js['url'],
            ['jquery'],
            $frontend_js['version'],
            true
        );
        
        // Localize frontend script with configuration
        wp_localize_script('ufsc-frontend-js', 'ufscFrontendConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonces' => [
                'licence_form' => wp_create_nonce('ufsc_add_licence_nonce'),
                'affiliation_form' => wp_create_nonce('ufsc_front_nonce'),
                'download_attestation' => wp_create_nonce('ufsc_download_attestation')
            ],
            'messages' => [
                'loading' => __('Chargement...', 'plugin-ufsc-gestion-club-13072025'),
                'success' => __('Succès !', 'plugin-ufsc-gestion-club-13072025'),
                'error' => __('Erreur', 'plugin-ufsc-gestion-club-13072025'),
                'duplicate' => __('Licencié déjà enregistré', 'plugin-ufsc-gestion-club-13072025')
            ]
        ]);
    }
}
add_action('wp_enqueue_scripts', 'ufsc_enqueue_frontend_assets');

/**
 * Enqueue WooCommerce specific styles
 */
function ufsc_enqueue_woocommerce_styles()
{
    global $post;
    if (is_product() || is_cart() || is_checkout() ||
        (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ufsc_bouton_licence'))) {
        wp_enqueue_style(
            'ufsc-woocommerce-styles',
            UFSC_PLUGIN_URL . 'assets/css/woocommerce-custom.css',
            [],
            UFSC_PLUGIN_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'ufsc_enqueue_woocommerce_styles');

/**
 * Compatibility shim for club info section function
 * 
 * Ensures ufsc_render_club_info_section() works even if the expected
 * function name differs between implementations.
 */
if (!function_exists('ufsc_render_club_info_section') && function_exists('ufsc_club_render_profile')) {
    function ufsc_render_club_info_section($club) {
        return ufsc_club_render_profile($club);
    }
}

/**
 * Plugin activation hook
 */
function ufsc_gestion_club_activate()
{
    // Create database tables
    if (class_exists('UFSC_Club_Manager')) {
        $manager = UFSC_Club_Manager::get_instance();
        $manager->create_table();
    }

    // CORRECTION: Set default page options to prevent broken links
    // These options are used throughout the frontend for navigation buttons
    if (!get_option('ufsc_club_dashboard_page_id')) {
        add_option('ufsc_club_dashboard_page_id', 0);
    }
    if (!get_option('ufsc_affiliation_page_id')) {
        add_option('ufsc_affiliation_page_id', 0);
    }
    if (!get_option('ufsc_club_form_page_id')) {
        add_option('ufsc_club_form_page_id', 0);
    }
    if (!get_option('ufsc_licence_page_id')) {
        add_option('ufsc_licence_page_id', 0);
    }
    if (!get_option('ufsc_attestation_page_id')) {
        add_option('ufsc_attestation_page_id', 0);
    }

    // Set default WooCommerce product IDs
    if (!get_option('ufsc_wc_affiliation_product_id')) {
        add_option('ufsc_wc_affiliation_product_id', 4823);
    }
    if (!get_option('ufsc_affiliation_product_id')) {
        add_option('ufsc_affiliation_product_id', 4823);
    }
    if (!get_option('ufsc_licence_product_id')) {
        add_option('ufsc_licence_product_id', 2934);
    }

    // Set transient to show activation notice
    set_transient('ufsc_show_activation_notice', true, 60);
    
    // Clear permalinks
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'ufsc_gestion_club_activate');

/**
 * Plugin deactivation hook
 */
function ufsc_gestion_club_deactivate()
{
    // Clear permalinks
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'ufsc_gestion_club_deactivate');

/**
 * Debug information - REMOVE IN PRODUCTION
 */
function ufsc_show_debug_info()
{
    if (!current_user_can('ufsc_manage')) {
        return;
    }

    echo '<div class="notice notice-info is-dismissible"><p><strong>Vérification des fichiers UFSC:</strong></p><ul>';

    $files_to_check = [
        'Fichier principal' => UFSC_PLUGIN_PATH . 'Plugin_UFSC_GESTION_CLUB_13072025.php',
        'Core' => UFSC_PLUGIN_PATH . 'includes/core/class-gestionclub-core.php',
        'Club Manager' => UFSC_PLUGIN_PATH . 'includes/clubs/class-club-manager.php',
        'Dashboard' => UFSC_PLUGIN_PATH . 'includes/admin/class-dashboard.php',
        'Menu' => UFSC_PLUGIN_PATH . 'includes/admin/class-menu.php',
        'Frontend Dashboard' => UFSC_PLUGIN_PATH . 'includes/frontend/frontend-club-dashboard.php',
        'CSS Admin' => UFSC_PLUGIN_PATH . 'assets/css/admin.css',
        'JS Admin' => UFSC_PLUGIN_PATH . 'assets/js/admin.js',
        'CSS Frontend' => UFSC_PLUGIN_PATH . 'assets/css/frontend.css',
        'JS Frontend' => UFSC_PLUGIN_PATH . 'assets/js/frontend.js',
        'WooCommerce CSS' => UFSC_PLUGIN_PATH . 'assets/css/woocommerce-custom.css'
    ];

    foreach ($files_to_check as $name => $file) {
        $exists = file_exists($file);
        $status = $exists ? '✅ Trouvé' : '❌ Introuvable';
        $color = $exists ? 'green' : 'red';

        echo '<li><strong>' . esc_html($name) . '</strong>: <span style="color:' . esc_attr($color) . '">' . esc_html($status) . '</span> (' . esc_html($file) . ')</li>';
    }

    echo '</ul></div>';
}
add_action('admin_notices', 'ufsc_show_debug_info');

/**
 * Validate club data for AJAX submissions
 */
function ufsc_validate_club_data($post_data) {
    $errors = new WP_Error();
    
    // Required fields validation
    $required_fields = [
        'nom' => 'Le nom du club est obligatoire',
        'region' => 'La région est obligatoire',
        'adresse' => 'L\'adresse est obligatoire',
        'code_postal' => 'Le code postal est obligatoire',
        'ville' => 'La ville est obligatoire',
        'email' => 'L\'email du club est obligatoire',
        'telephone' => 'Le téléphone est obligatoire',
        'num_declaration' => 'Le numéro de déclaration est obligatoire',
        'date_declaration' => 'La date de déclaration est obligatoire'
    ];

    foreach ($required_fields as $field => $message) {
        if (empty($post_data[$field])) {
            $errors->add('required_field', $message);
        }
    }

    // Dirigeants validation (president, secretary, treasurer required)
    $dirigeant_roles = ['president', 'secretaire', 'tresorier'];
    $dirigeant_fields = ['nom', 'prenom', 'email', 'tel'];
    
    foreach ($dirigeant_roles as $role) {
        foreach ($dirigeant_fields as $field) {
            $field_name = "{$role}_{$field}";
            if (empty($post_data[$field_name])) {
                $errors->add('dirigeant_required', "Le {$field} du {$role} est obligatoire");
            }
        }
    }

    // Email validation
    if (!empty($post_data['email']) && !is_email($post_data['email'])) {
        $errors->add('invalid_email', 'L\'email du club n\'est pas valide');
    }

    // Dirigeants email validation
    foreach ($dirigeant_roles as $role) {
        $email_field = "{$role}_email";
        if (!empty($post_data[$email_field]) && !is_email($post_data[$email_field])) {
            $errors->add('invalid_email', "L'email du {$role} n'est pas valide");
        }
    }

    // Code postal validation
    if (!empty($post_data['code_postal']) && !preg_match('/^[0-9]{5}$/', $post_data['code_postal'])) {
        $errors->add('invalid_postal', 'Le code postal doit contenir 5 chiffres');
    }

    // If validation passed, return sanitized data
    if (!$errors->has_errors()) {
        $clean_data = [];
        
        // Basic club info
        $basic_fields = [
            'nom', 'region', 'adresse', 'complement_adresse', 'code_postal', 'ville',
            'email', 'telephone', 'type', 'siren', 'ape', 'ccn', 'ancv',
            'num_declaration', 'date_declaration'
        ];
        
        foreach ($basic_fields as $field) {
            if (isset($post_data[$field])) {
                if ($field === 'email') {
                    $clean_data[$field] = sanitize_email(wp_unslash($post_data[$field]));
                } elseif ($field === 'adresse') {
                    $clean_data[$field] = sanitize_textarea_field(wp_unslash($post_data[$field]));
                } else {
                    $clean_data[$field] = sanitize_text_field(wp_unslash($post_data[$field]));
                }
            }
        }

        // Dirigeants data
        $all_roles = ['president', 'secretaire', 'tresorier', 'entraineur'];
        foreach ($all_roles as $role) {
            $clean_data["{$role}_nom"] = sanitize_text_field(wp_unslash($post_data["{$role}_nom"] ?? ''));
            $clean_data["{$role}_prenom"] = sanitize_text_field(wp_unslash($post_data["{$role}_prenom"] ?? ''));
            $clean_data["{$role}_email"] = sanitize_email(wp_unslash($post_data["{$role}_email"] ?? ''));
            $clean_data["{$role}_tel"] = sanitize_text_field(wp_unslash($post_data["{$role}_tel"] ?? ''));
        }

        // Admin fields (if user has permission)
        if (current_user_can('ufsc_manage')) {
            if (isset($post_data['statut'])) {
                $clean_data['statut'] = sanitize_text_field(wp_unslash($post_data['statut']));
            }
            if (isset($post_data['num_affiliation'])) {
                $clean_data['num_affiliation'] = sanitize_text_field(wp_unslash($post_data['num_affiliation']));
            }
            if (isset($post_data['quota_licences'])) {
                $clean_data['quota_licences'] = intval($post_data['quota_licences']);
            }
        } else {
            // Frontend users get default status
            $clean_data['statut'] = 'En cours de validation';
        }

        return $clean_data;
    }

    return $errors;
}

/**
 * Handle file uploads for club documents
 * Uses centralized validation for security
 */
function ufsc_handle_club_file_uploads($club_id, $files) {
    if (empty($files)) {
        return [];
    }

    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $club_manager = UFSC_Club_Manager::get_instance();
    $upload_results = [];
    
    $allowed_docs = UFSC_Upload_Validator::get_allowed_document_types();

    foreach ($allowed_docs as $doc_key => $doc_label) {
        if (!empty($files[$doc_key]['name'])) {
            $file = $files[$doc_key];
            
            // Use centralized validation
            $validation = UFSC_Upload_Validator::validate_document($file, $club_id, $doc_key);
            
            if (is_wp_error($validation)) {
                $upload_results[$doc_key] = [
                    'success' => false,
                    'error' => $validation->get_error_message()
                ];
                continue;
            }

            // Override filename with secure name
            $file['name'] = $validation['filename'];
            
            // Upload file
            $upload_overrides = [
                'test_form' => false,
                'unique_filename_callback' => function($dir, $name, $ext) {
                    return $name; // Use the secure filename we generated
                }
            ];
            $movefile = wp_handle_upload($file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                // Update club with document URL
                $update_result = $club_manager->update_club_document($club_id, $doc_key, $movefile['url']);
                
                $upload_results[$doc_key] = [
                    'success' => true,
                    'url' => $movefile['url'],
                    'filename' => $file['name']
                ];
                
                ufsc_log_operation('file_upload_success', [
                    'club_id' => $club_id,
                    'document_type' => $doc_key,
                    'filename' => $file['name'],
                    'url' => $movefile['url']
                ]);
            } else {
                $upload_results[$doc_key] = [
                    'success' => false,
                    'error' => $movefile['error'] ?? 'Erreur lors du téléchargement'
                ];
                
                ufsc_log_operation('file_upload_error', [
                    'club_id' => $club_id,
                    'document_type' => $doc_key,
                    'filename' => $file['name'],
                    'error' => $movefile['error'] ?? 'Unknown error'
                ]);
            }
        }
    }

    return $upload_results;
}

/**
 * Handle admin license attestation download
 */
function ufsc_handle_licence_attestation_admin_download() {
    // Check permissions

    if (!current_user_can('ufsc_manage_own')) {

        wp_die('Permissions insuffisantes', 'Erreur', ['response' => 403]);
    }

    $licence_id = intval( wp_unslash( $_GET['licence_id'] ?? '0' ) ); // Sanitize GET parameters
    $nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) );

    // Verify nonce
    if (!wp_verify_nonce($nonce, 'ufsc_licence_attestation_admin_' . $licence_id)) {
        wp_die('Erreur de sécurité', 'Erreur', ['response' => 403]);
    }

    if (!$licence_id) {
        wp_die('ID de licence manquant', 'Erreur', ['response' => 400]);
    }

    // Get license data
    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $licence_manager = new UFSC_Licence_Manager();
    $licence = $licence_manager->get_licence_by_id($licence_id);

    if (!$licence) {
        wp_die('Licence introuvable', 'Erreur', ['response' => 404]);
    }

    // Get club data
    global $wpdb;
    $club = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d",
        $licence->club_id
    ));

    if (!$club) {
        wp_die('Club associé introuvable', 'Erreur', ['response' => 404]);
    }

    // Generate and serve license attestation
    ufsc_generate_licence_attestation_pdf($licence, $club);
}

/**
 * Generate and serve license attestation PDF
 */
function ufsc_generate_licence_attestation_pdf($licence, $club) {
    // For now, generate a simple text-based attestation
    // In a real implementation, you'd use a PDF library like TCPDF or FPDF
    
    $filename = 'attestation_licence_' . sanitize_file_name($licence->prenom . '_' . $licence->nom) . '_' . date('Y-m-d') . '.txt';
    
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private');
    header('Pragma: private');
    
    echo "ATTESTATION DE LICENCE UFSC\n";
    echo "===========================\n\n";
    echo "Je soussigné(e), représentant de l'Union Fédérale de Sport et Culture,\n";
    echo "atteste que :\n\n";
    echo "Nom : " . strtoupper($licence->nom) . "\n";
    echo "Prénom : " . ucfirst($licence->prenom) . "\n";
    echo "Date de naissance : " . ($licence->date_naissance ? date('d/m/Y', strtotime($licence->date_naissance)) : 'Non renseignée') . "\n";
    echo "Email : " . $licence->email . "\n\n";
    echo "est titulaire d'une licence UFSC au sein du club :\n";
    echo "Club : " . $club->nom . "\n";
    echo "Ville : " . $club->ville . "\n";
    echo "Numéro d'affiliation : " . ($club->num_affiliation ?? 'En cours') . "\n\n";
    echo "Statut de la licence : " . ($licence->statut ?? 'En attente') . "\n";
    echo "Date d'émission : " . date('d/m/Y à H:i') . "\n\n";
    echo "Cette attestation est valable pour la saison en cours.\n\n";
    echo "Fait le " . date('d/m/Y') . "\n";
    echo "Pour l'UFSC\n";
    
    exit;
}

/**
 * Ancienne définition de ufsc_is_licence_paid supprimée.
 * La version canonique est dans includes/helpers.php
 */

// Register admin license attestation download handler
add_action('wp_ajax_ufsc_download_licence_attestation_admin', 'ufsc_handle_licence_attestation_admin_download');

// Register attestation generation handler (handled by UFSC_Document_Manager class)

// Register license card download handler
add_action('init', 'ufsc_handle_licence_card_download');

/**
 * Handle license card download requests
 */
function ufsc_handle_licence_card_download() {
    $action = isset($_GET['action']) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // Sanitize GET parameters
    if ('download_licence_card' !== $action) {
        return;
    }

    $licence_id = isset($_GET['licence_id']) ? absint( wp_unslash( $_GET['licence_id'] ) ) : 0;
    $nonce = isset($_GET['nonce']) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
    
    if (!$licence_id) {
        wp_die('Invalid license ID', 'Error', ['response' => 400]);
    }
    
    // Verify nonce
    if (!wp_verify_nonce($nonce, 'ufsc_download_licence_' . $licence_id)) {
        wp_die('Security check failed', 'Error', ['response' => 403]);
    }
    
    // Check user permissions
    if (!is_user_logged_in()) {
        wp_die('You must be logged in', 'Error', ['response' => 401]);
    }
    
    // Get license data
    global $wpdb;
    $licence = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$wpdb->prefix}ufsc_licences WHERE id = %d
    ", $licence_id));
    
    if (!$licence) {
        wp_die('License not found', 'Error', ['response' => 404]);
    }
    
    // Check if user can access this license (own club only)
    if (!ufsc_verify_club_access($licence->club_id)) {
        wp_die('Access denied', 'Error', ['response' => 403]);
    }
    
    // Check if license is active or validated
    if ($licence->statut !== 'active' && $licence->statut !== 'validee') {
        wp_die('License is not active or validated', 'Error', ['response' => 400]);
    }
    
    // For now, redirect to a placeholder or generate a simple PDF
    // In a real implementation, this would generate a proper license card
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="licence_' . $licence_id . '.txt"');
    
    echo "LICENCE UFSC\n";
    echo "============\n\n";
    echo "Nom: " . $licence->nom . "\n";
    echo "Prénom: " . $licence->prenom . "\n";
    echo "Date d'expiration: " . $licence->date_expiration . "\n";
    echo "Statut: " . $licence->statut . "\n";
    
    exit;
}

// ========================
// ATTESTATION UPLOAD AJAX HANDLERS
// ========================

// Club attestation upload handler
add_action('wp_ajax_ufsc_upload_club_attestation', 'ufsc_handle_upload_club_attestation');
function ufsc_handle_upload_club_attestation() {
    // Verify nonce first
    check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce');

    // Check permissions
    if (!current_user_can('ufsc_manage')) {
        wp_send_json_error('Accès non autorisé.', 403);
    }

    $club_id = isset($_POST['club_id']) ? absint(wp_unslash($_POST['club_id'])) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
    
    if (!$club_id || !in_array($type, ['affiliation', 'assurance'])) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('Aucun fichier téléchargé ou erreur d\'upload.');
    }
    
    $file = $_FILES['file'];
    
    // Validate file
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    $allowed_mimes = [
        'application/pdf',
        'image/jpeg', 
        'image/jpg',
        'image/png'
    ];
    
    $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    if (!$file_info['ext'] || !in_array($file_info['ext'], $allowed_types) || !in_array($file_info['type'], $allowed_mimes)) {
        wp_send_json_error('Type de fichier non autorisé. Utilisez PDF, JPG, JPEG ou PNG.');
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        wp_send_json_error('Fichier trop volumineux. Taille maximale : 5MB.');
    }
    
    // Get upload directory
    $upload_dir = wp_upload_dir();
    if (!$upload_dir || !empty($upload_dir['error'])) {
        wp_send_json_error('Erreur de répertoire d\'upload.');
    }
    
    // Create UFSC attestations subdirectory
    $ufsc_dir = $upload_dir['basedir'] . '/ufsc-attestations';
    if (!file_exists($ufsc_dir)) {
        wp_mkdir_p($ufsc_dir);
    }
    
    // Generate secure filename
    $timestamp = time();
    $extension = $file_info['ext'];
    $filename = "club_{$club_id}_{$type}_{$timestamp}.{$extension}";
    $file_path = $ufsc_dir . '/' . $filename;
    
    // Get old file to delete later
    $old_file_url = get_post_meta($club_id, "_ufsc_attestation_{$type}", true);
    $old_file_path = null;
    if ($old_file_url) {
        $old_file_path = str_replace($upload_dir['baseurl'] . '/ufsc-attestations/', $ufsc_dir . '/', (string) $old_file_url);
    }
    
    // Move uploaded file to uploads directory
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        wp_send_json_error('Erreur lors du déplacement du fichier.');
    }
    
    // Create WordPress attachment
    $attachment_data = array(
        'post_mime_type' => $file_info['type'],
        'post_title'     => ufsc_get_attestation_download_filename($club_id, $type),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    
    // Insert the attachment
    $attachment_id = wp_insert_attachment($attachment_data, $file_path);
    
    if (!is_wp_error($attachment_id)) {
        // Include image.php for wp_generate_attachment_metadata
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }
        
        // Generate attachment metadata
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        
        // Delete old attachment if exists
        $old_attachment_id = ufsc_club_get_attestation_attachment_id($club_id, $type);
        if ($old_attachment_id) {
            wp_delete_attachment($old_attachment_id, true);
        }
        
        // Store attachment ID using helper function
        ufsc_club_set_attestation_attachment_id($club_id, $type, $attachment_id);
        
        // Get attachment URL
        $file_url = wp_get_attachment_url($attachment_id);
        
        // Also store URL for backward compatibility
        update_post_meta($club_id, "_ufsc_attestation_{$type}", $file_url);
    } else {
        // Fallback to old system if attachment creation fails
        $file_url = $upload_dir['baseurl'] . '/ufsc-attestations/' . $filename;
        update_post_meta($club_id, "_ufsc_attestation_{$type}", $file_url);
        
        // Delete old file if it exists
        $old_file_url = get_post_meta($club_id, "_ufsc_attestation_{$type}", true);
        if ($old_file_url) {
            $old_file_path = str_replace($upload_dir['baseurl'] . '/ufsc-attestations/', $ufsc_dir . '/', (string) $old_file_url);
            if ($old_file_path && file_exists($old_file_path) && $old_file_path !== $file_path) {
                unlink($old_file_path);
            }
        }
    }
    
    wp_send_json_success([
        'message' => 'Attestation téléchargée avec succès.',
        'file_url' => $file_url,
        'filename' => $filename
    ]);
}

// Club attestation delete handler
add_action('wp_ajax_ufsc_delete_club_attestation', 'ufsc_handle_delete_club_attestation');
function ufsc_handle_delete_club_attestation() {
    // Verify nonce first
    check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce');

    // Check permissions
    if (!current_user_can('ufsc_manage')) {
        wp_send_json_error('Accès non autorisé.', 403);
    }

    $club_id = isset($_POST['club_id']) ? absint(wp_unslash($_POST['club_id'])) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
    
    if (!$club_id || !in_array($type, ['affiliation', 'assurance'])) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    // Use helper function for deletion
    if (ufsc_delete_club_attestation($club_id, $type)) {
        wp_send_json_success(['message' => 'Attestation supprimée avec succès.']);
    } else {
        wp_send_json_error('Erreur lors de la suppression de l\'attestation.');
    }
}

// Club attestation attach existing media handler
add_action('wp_ajax_ufsc_attach_existing_club_attestation', 'ufsc_handle_attach_existing_club_attestation');
function ufsc_handle_attach_existing_club_attestation() {
    // Verify nonce first
    check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce');

    // Check permissions
    if (!current_user_can('ufsc_manage')) {
        wp_send_json_error('Accès non autorisé.', 403);
    }

    $club_id = isset($_POST['club_id']) ? absint(wp_unslash($_POST['club_id'])) : 0;
    $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
    $attachment_id = isset($_POST['attachment_id']) ? absint(wp_unslash($_POST['attachment_id'])) : 0;
    
    if (!$club_id || !in_array($type, ['affiliation', 'assurance']) || !$attachment_id) {
        wp_send_json_error('Paramètres invalides.');
    }
    
    // Verify attachment exists
    if (!wp_attachment_is_image($attachment_id) && get_post_mime_type($attachment_id) !== 'application/pdf') {
        wp_send_json_error('Le fichier sélectionné n\'est pas un format valide.');
    }
    
    // Delete old attachment if exists
    $old_attachment_id = ufsc_club_get_attestation_attachment_id($club_id, $type);
    if ($old_attachment_id && $old_attachment_id !== $attachment_id) {
        wp_delete_attachment($old_attachment_id, true);
    }
    
    // Store attachment ID using helper function
    if (ufsc_club_set_attestation_attachment_id($club_id, $type, $attachment_id)) {
        // Also store URL for backward compatibility
        $attachment_url = wp_get_attachment_url($attachment_id);
        if ($attachment_url) {
            update_post_meta($club_id, "_ufsc_attestation_{$type}", $attachment_url);
        }
        
        wp_send_json_success([
            'message' => 'Attestation associée avec succès.',
            'attachment_id' => $attachment_id,
            'attachment_url' => $attachment_url
        ]);
    } else {
        wp_send_json_error('Erreur lors de la sauvegarde de l\'attestation.');
    }
}

// License attestation upload handler
add_action('wp_ajax_ufsc_upload_licence_attestation', 'ufsc_handle_upload_licence_attestation');
function ufsc_handle_upload_licence_attestation() {
    // Verify nonce first
    check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce');

    $licence_id = isset($_POST['licence_id']) ? absint(wp_unslash($_POST['licence_id'])) : 0;

    if (!$licence_id) {
        wp_send_json_error('ID de licence invalide.');
    }

    $licence = ufscsn_require_manage_licence($licence_id);

    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('Aucun fichier téléchargé ou erreur d\'upload.');
    }
    
    $file = $_FILES['file'];
    
    // Validate file
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    $allowed_mimes = [
        'application/pdf',
        'image/jpeg', 
        'image/jpg',
        'image/png'
    ];
    
    $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    if (!$file_info['ext'] || !in_array($file_info['ext'], $allowed_types) || !in_array($file_info['type'], $allowed_mimes)) {
        wp_send_json_error('Type de fichier non autorisé. Utilisez PDF, JPG, JPEG ou PNG.');
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        wp_send_json_error('Fichier trop volumineux. Taille maximale : 5MB.');
    }
    
    // Get upload directory
    $upload_dir = wp_upload_dir();
    if (!$upload_dir || !empty($upload_dir['error'])) {
        wp_send_json_error('Erreur de répertoire d\'upload.');
    }
    
    // Create UFSC attestations subdirectory
    $ufsc_dir = $upload_dir['basedir'] . '/ufsc-attestations';
    if (!file_exists($ufsc_dir)) {
        wp_mkdir_p($ufsc_dir);
    }
    
    // Generate secure filename
    $timestamp = time();
    $extension = $file_info['ext'];
    $filename = "licence_{$licence_id}_attestation_{$timestamp}.{$extension}";
    $file_path = $ufsc_dir . '/' . $filename;
    
    // Use existing licence data to check for old file
    $old_file_url = $licence->attestation_url ?? null;
    $old_file_path = null;
    if ($old_file_url) {
        $old_file_path = str_replace($upload_dir['baseurl'] . '/ufsc-attestations/', $ufsc_dir . '/', (string) $old_file_url);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        wp_send_json_error('Erreur lors du déplacement du fichier.');
    }
    
    // Store file URL in database
    $file_url = $upload_dir['baseurl'] . '/ufsc-attestations/' . $filename;
    
    global $wpdb;
    $licences_table = $wpdb->prefix . 'ufsc_licences';
    $updated = $wpdb->update(
        $licences_table,
        ['attestation_url' => $file_url],
        ['id' => $licence_id],
        ['%s'],
        ['%d']
    );
    
    if ($updated === false) {
        wp_send_json_error('Erreur lors de la mise à jour en base de données.');
    }
    
    // Delete old file if it exists
    if ($old_file_path && file_exists($old_file_path)) {
        if (!unlink($old_file_path) && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("UFSC: Failed to delete old license attestation file: {$old_file_path}");
        }
    }
    
    wp_send_json_success([
        'message' => 'Attestation de licence téléchargée avec succès.',
        'file_url' => $file_url,
        'filename' => $filename
    ]);
}

// License attestation delete handler
add_action('wp_ajax_ufsc_delete_licence_attestation', 'ufsc_handle_delete_licence_attestation');
function ufsc_handle_delete_licence_attestation() {
    // Verify nonce first
    check_ajax_referer('ufsc_admin_nonce', 'ufsc_nonce');

    $licence_id = isset($_POST['licence_id']) ? absint(wp_unslash($_POST['licence_id'])) : 0;

    if (!$licence_id) {
        wp_send_json_error('ID de licence invalide.');
    }

    $licence = ufscsn_require_manage_licence($licence_id);
    
    // Delete file from filesystem
    if (!empty($licence->attestation_url)) {
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'] . '/ufsc-attestations/', $upload_dir['basedir'] . '/ufsc-attestations/', (string) $licence->attestation_url);
        
        if (file_exists($file_path)) {
            if (!unlink($file_path) && defined('WP_DEBUG') && WP_DEBUG) {
                error_log("UFSC: Failed to delete license attestation file: {$file_path}");
            }
        }
    }
    
    // Remove from database
    global $wpdb;
    $licences_table = $wpdb->prefix . 'ufsc_licences';
    $updated = $wpdb->update(
        $licences_table,
        ['attestation_url' => null],
        ['id' => $licence_id],
        ['%s'],
        ['%d']
    );
    
    if ($updated === false) {
        wp_send_json_error('Erreur lors de la mise à jour en base de données.');
    }
    
    wp_send_json_success(['message' => 'Attestation de licence supprimée avec succès.']);
}

// Secure license attestation download handler
add_action('wp_ajax_ufsc_download_licence_attestation', 'ufsc_handle_licence_attestation_download');
add_action('wp_ajax_nopriv_ufsc_download_licence_attestation', 'ufsc_handle_licence_attestation_download');
function ufsc_handle_licence_attestation_download() {
    // Security checks
    if (!is_user_logged_in()) {
        wp_die('Accès non autorisé', 'Erreur', ['response' => 403]);
    }

    $licence_id = isset($_GET['licence_id']) ? absint( wp_unslash( $_GET['licence_id'] ) ) : 0; // Sanitize GET parameters
    $nonce = isset($_GET['nonce']) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

    if (!$licence_id) {
        wp_die('ID de licence invalide', 'Erreur', ['response' => 400]);
    }

    // Verify nonce
    if (!wp_verify_nonce($nonce, 'ufsc_download_licence_attestation_' . $licence_id)) {
        wp_die('Erreur de sécurité', 'Erreur', ['response' => 403]);
    }

    // Get license data
    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $licence_manager = new UFSC_Licence_Manager();
    $licence = $licence_manager->get_licence_by_id($licence_id);

    if (!$licence) {
        wp_die('Licence introuvable', 'Erreur', ['response' => 404]);
    }

    // Check if user can access this license (club owner or admin)

    if (!current_user_can('ufsc_manage_own') && !ufsc_verify_club_access($licence->club_id)) {

        wp_die('Accès refusé à cette licence', 'Erreur', ['response' => 403]);
    }

    // Check if attestation exists
    if (empty($licence->attestation_url)) {
        wp_die('Aucune attestation disponible', 'Erreur', ['response' => 404]);
    }

    // Get file path from URL
    $upload_dir = wp_upload_dir();
    $file_path = str_replace($upload_dir['baseurl'] . '/ufsc-attestations/', $upload_dir['basedir'] . '/ufsc-attestations/', (string) $licence->attestation_url);

    // Security check: ensure file is within attestations directory
    $realpath = realpath($file_path);
    $allowed_dir = realpath($upload_dir['basedir'] . '/ufsc-attestations/');
    
    if (!$realpath || !$allowed_dir || strpos((string) $realpath, (string) $allowed_dir) !== 0) {
        wp_die('Chemin de fichier non autorisé', 'Erreur', ['response' => 403]);
    }

    // Check if file exists
    if (!file_exists($realpath)) {
        wp_die('Fichier introuvable', 'Erreur', ['response' => 404]);
    }

    // Serve the file
    $filename = basename($realpath);
    $mime_type = wp_check_filetype($filename)['type'];

    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($realpath));
    header('Cache-Control: private');
    header('Pragma: private');
    header('Expires: 0');

    readfile($realpath);
    exit;
}

/**
 * Send notification email for new club creation
 */
function ufsc_send_club_notification_email($club_data) {
    $admin_email = get_option('admin_email');
    $subject = 'Nouveau club UFSC créé';
    
    $message = "Un nouveau club a été créé sur votre site.\n\n";
    $message .= "Nom: " . ($club_data->nom ?? '') . "\n";
    $message .= "Email: " . ($club_data->email ?? '') . "\n";
    $message .= "Téléphone: " . ($club_data->telephone ?? '') . "\n";
    $message .= "Ville: " . ($club_data->ville ?? '') . "\n";
    $message .= "Région: " . ($club_data->region ?? '') . "\n";
    $message .= "Statut: " . ($club_data->statut ?? '') . "\n\n";
    $message .= "Date de création: " . current_time('Y-m-d H:i:s') . "\n";
    
    wp_mail($admin_email, $subject, $message);
}

/**
 * Log operations for debugging and monitoring
 */
function ufsc_log_operation($operation_type, $data = []) {
    // Only log if WP_DEBUG is enabled or if it's an error
    if (!WP_DEBUG && !in_array($operation_type, ['club_save_error', 'file_upload_error'])) {
        return;
    }

    $log_entry = [
        'timestamp' => current_time('Y-m-d H:i:s'),
        'operation' => $operation_type,
        'user_id' => get_current_user_id(),
        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'data' => $data
    ];

    // WordPress doesn't allow error_log by default, so we'll use WordPress's own logging
    if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
        error_log('UFSC Plugin Log: ' . wp_json_encode($log_entry));
    }

    // Alternative: Store in custom database table or use WordPress transients for temporary storage
    $log_option_key = 'ufsc_operation_log_' . date('Ymd');
    $current_log = get_option($log_option_key, []);
    $current_log[] = $log_entry;
    
    // Keep only last 100 entries per day to avoid database bloat
    if (count($current_log) > 100) {
        $current_log = array_slice($current_log, -100);
    }
    
    update_option($log_option_key, $current_log, false);
}

/**
 * Show admin notice after plugin activation
 */
add_action('admin_notices', 'ufsc_activation_admin_notice');
function ufsc_activation_admin_notice() {
    if ( get_transient('ufsc_show_activation_notice') ) {
        delete_transient('ufsc_show_activation_notice');
        if ( current_user_can('manage_options') ) {
            echo '<div class="notice notice-success is-dismissible">'
               . '<h3>' . esc_html__('UFSC Gestion Club - Plugin Activated!', 'plugin-ufsc-gestion-club-13072025') . '</h3>'
               . '<p>' . esc_html__('Database tables created successfully.', 'plugin-ufsc-gestion-club-13072025') . '</p>'
               . '</div>';
        }
    }
}




// === Frontend: save licence as draft (club profile) ===
add_action('wp_ajax_ufsc_save_licence_draft', 'ufsc_handle_save_licence_draft');
function ufsc_handle_save_licence_draft(){
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { wp_send_json_error(['message'=>__('Méthode non autorisée.','plugin-ufsc-gestion-club-13072025')], 405); }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message'=>__('Veuillez vous connecter.','plugin-ufsc-gestion-club-13072025')], 401);
    }
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'ufsc_frontend_nonce') && !wp_verify_nonce($nonce, 'ufsc_front_nonce')) {
        wp_send_json_error(['message'=>__('Jeton invalide.','plugin-ufsc-gestion-club-13072025')], 400);
    }

    $data = wp_unslash($_POST);
    $club_id = isset($data['club_id']) ? absint($data['club_id']) : 0;
    if (!$club_id) {
        wp_send_json_error(['message'=>__('Club manquant.','plugin-ufsc-gestion-club-13072025')], 400);
    }

    // Verify that the current user has rights on the target club

    if (!current_user_can('ufsc_manage_own') && !ufsc_verify_club_access($club_id)) {

        wp_send_json_error([
            'message' => __('Vous n\'avez pas les droits nécessaires pour ce club.','plugin-ufsc-gestion-club-13072025')
        ], 403);
    }

    global $wpdb;

    // Prepare data for licence manager
    $lic_data = [
        'club_id'        => $club_id,
        'nom'            => isset($data['nom']) ? sanitize_text_field($data['nom']) : '',
        'prenom'         => isset($data['prenom']) ? sanitize_text_field($data['prenom']) : '',
        'date_naissance' => isset($data['date_naissance']) ? sanitize_text_field($data['date_naissance']) : '',
        'email'          => isset($data['email']) ? sanitize_email($data['email']) : '',
        'adresse'        => isset($data['adresse']) ? sanitize_text_field($data['adresse']) : '',
        'code_postal'    => isset($data['code_postal']) ? sanitize_text_field($data['code_postal']) : '',
        'ville'          => isset($data['ville']) ? sanitize_text_field($data['ville']) : '',
        'tel_mobile'     => isset($data['tel_mobile']) ? sanitize_text_field($data['tel_mobile']) : '',
        'competition'    => isset($data['competition']) ? (int)$data['competition'] : 0,
        'statut'         => 'brouillon',
        'is_included'    => 0,
    ];

    // Allow update when editing an existing draft
    $licence_id = isset($data['licence_id']) ? absint($data['licence_id']) : 0;
    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $manager = new UFSC_Licence_Manager();

    if ($licence_id) {
        // Update only if licence belongs to this club and is a draft
        $table = $wpdb->prefix . 'ufsc_licences';
        $club_for_lic = (int) $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$table} WHERE id=%d", $licence_id));
        $status_cur = (string) $wpdb->get_var($wpdb->prepare("SELECT statut FROM {$table} WHERE id=%d", $licence_id));
        if ($club_for_lic !== $club_id || $status_cur !== 'brouillon') {
            wp_send_json_error(['message'=>__('Impossible de modifier cette licence.','plugin-ufsc-gestion-club-13072025')], 403);
        }
        $wpdb->update($table, $lic_data, ['id'=>$licence_id], null, ['%d']);
        wp_send_json_success(['message'=>__('Brouillon mis à jour.','plugin-ufsc-gestion-club-13072025'), 'licence_id'=>$licence_id]);
    } else {
        $new_id = $manager->create_licence($lic_data);
        if ($new_id) {
            wp_send_json_success(['message'=>__('Brouillon enregistré.','plugin-ufsc-gestion-club-13072025'), 'licence_id'=>$new_id]);
        }
        wp_send_json_error(['message'=>__('Erreur lors de l’enregistrement.','plugin-ufsc-gestion-club-13072025')], 500);
    }
}


/**
 * Redirect directly to checkout when ufsc_checkout=1 is present
 */
add_filter('woocommerce_add_to_cart_redirect', function($url){
    if (isset($_REQUEST['ufsc_checkout']) && $_REQUEST['ufsc_checkout'] == '1') {
        return wc_get_checkout_url();
    }
    return $url;
}, 10, 1);


// === Frontend: build a pay URL for an existing draft licence ===
add_action('wp_ajax_ufsc_get_licence_pay_url', 'ufsc_get_licence_pay_url');
function ufsc_get_licence_pay_url() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message'=>__('Veuillez vous connecter.','plugin-ufsc-gestion-club-13072025')], 401);
    }
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'ufsc_frontend_nonce') && !wp_verify_nonce($nonce, 'ufsc_front_nonce')) {
        wp_send_json_error(['message'=>__('Jeton invalide.','plugin-ufsc-gestion-club-13072025')], 400);
    }
    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    $club_id    = isset($_POST['club_id']) ? absint($_POST['club_id']) : 0;
    if (!$licence_id || !$club_id) {
        wp_send_json_error(['message'=>__('Paramètres manquants.','plugin-ufsc-gestion-club-13072025')], 400);
    }
    global $wpdb;
    // Basic ownership: ensure licence belongs to the club and user is linked to club
    $table = $wpdb->prefix . 'ufsc_licences';
    $lic_club = (int) $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$table} WHERE id=%d", $licence_id));
    if ($lic_club !== $club_id) {
        wp_send_json_error(['message'=>__('Licence introuvable pour ce club.','plugin-ufsc-gestion-club-13072025')], 403);
    }
    $rel = $wpdb->prefix . 'ufsc_user_clubs';
    $has = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rel} WHERE user_id=%d AND club_id=%d", get_current_user_id(), $club_id));
    if (!$has) {
        wp_send_json_error(['message'=>__('Accès non autorisé.','plugin-ufsc-gestion-club-13072025')], 403);
    }

    $product_id = ufsc_get_licence_product_id();
    if (!$product_id) {
        wp_send_json_error(['message'=>__('Produit licence introuvable.','plugin-ufsc-gestion-club-13072025')], 500);
    }
    $payload = [
        'licence_id' => $licence_id,
        'club_id'    => $club_id,
        'existing_draft' => 1,
    ];
    $encoded = base64_encode(wp_json_encode($payload));
    // Direct add-to-cart URL with redirect to checkout
    $url = add_query_arg([
        'add-to-cart'        => $product_id,
        'ufsc_licence_data'  => rawurlencode($encoded),
        'ufsc_checkout'      => '1',
    ], wc_get_cart_url()); // using cart URL to ensure Woo init; WC will redirect to checkout

    wp_send_json_success(['url'=>$url]);
}

// === Frontend: include a licence via quota ===
add_action('wp_ajax_ufsc_include_quota', 'ufsc_handle_include_quota');
function ufsc_handle_include_quota(){
    if ( ! is_user_logged_in() ) {
        wp_send_json_error(['message'=>__('Veuillez vous connecter.','plugin-ufsc-gestion-club-13072025')], 401);
    }
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if ( ! wp_verify_nonce($nonce, 'ufsc_include_quota') && ! wp_verify_nonce($nonce, 'ufsc_front_nonce') ) {
        wp_send_json_error(['message'=>__('Jeton invalide.','plugin-ufsc-gestion-club-13072025')], 400);
    }
    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if ( ! $licence_id ) {
        wp_send_json_error(['message'=>__('Licence manquante.','plugin-ufsc-gestion-club-13072025')], 400);
    }
    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $manager = new UFSC_Licence_Manager();
    $licence = $manager->get_licence_by_id($licence_id);
    if ( ! $licence ) {
        wp_send_json_error(['message'=>__('Licence introuvable.','plugin-ufsc-gestion-club-13072025')], 404);
    }
    $club_id = (int) $licence->club_id;
    if ( ! ufsc_verify_club_access($club_id) ) {
        wp_send_json_error(['message'=>__('Accès non autorisé.','plugin-ufsc-gestion-club-13072025')], 403);
    }
    if ( function_exists('ufsc_has_included_quota') && ! ufsc_has_included_quota($club_id) ) {
        wp_send_json_error(['message'=>__('Quota atteint.','plugin-ufsc-gestion-club-13072025')]);
    }
    global $wpdb; $t = $wpdb->prefix . 'ufsc_licences';
    $ok = $wpdb->update($t, [
        'statut'        => 'validee',
        'billing_source'=> 'quota',
        'is_included'   => 1,
        'date_modification' => current_time('mysql')
    ], ['id'=>$licence_id], ['%s','%s','%d','%s'], ['%d']);
    if ( $ok !== false ) {
        if ( function_exists('ufsc__log_status_change') ) {
            ufsc__log_status_change($licence_id, 'validee', get_current_user_id());
        }
        wp_send_json_success();
    }
    wp_send_json_error(['message'=>__('Échec inclusion quota.','plugin-ufsc-gestion-club-13072025')]);
}

// === Frontend: add existing licence to WooCommerce cart ===
add_action('wp_ajax_ufsc_add_to_cart', 'ufsc_handle_add_to_cart');
function ufsc_handle_add_to_cart(){
    if ( ! is_user_logged_in() ) {
        wp_send_json_error(['message'=>__('Veuillez vous connecter.','plugin-ufsc-gestion-club-13072025')], 401);
    }
    if ( ! class_exists('WC') ) {
        wp_send_json_error(['message'=>__('WooCommerce requis.','plugin-ufsc-gestion-club-13072025')], 400);
    }

    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if ( ! $licence_id ) {
        wp_send_json_error(['message'=>__('Licence manquante.','plugin-ufsc-gestion-club-13072025')], 400);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if ( ! wp_verify_nonce( $nonce, 'ufsc_add_to_cart_' . $licence_id ) &&
         ! wp_verify_nonce( $nonce, 'ufsc_add_to_cart' ) &&
         ! wp_verify_nonce( $nonce, 'ufsc_front_nonce' ) ) {
        wp_send_json_error(['message'=>__('Jeton invalide.','plugin-ufsc-gestion-club-13072025')], 400);
    }

    if ( ! current_user_can('ufsc_manage_own') && ! current_user_can('ufsc_manage') ) {
        wp_send_json_error(['message'=>__('Droit insuffisant.','plugin-ufsc-gestion-club-13072025')], 403);
    }
    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $manager = new UFSC_Licence_Manager();
    $licence = $manager->get_licence_by_id($licence_id);
    if ( ! $licence ) {
        wp_send_json_error(['message'=>__('Licence introuvable.','plugin-ufsc-gestion-club-13072025')], 404);
    }
    $club_id = (int) $licence->club_id;
    if ( ! ufsc_verify_club_access($club_id) ) {
        wp_send_json_error(['message'=>__('Accès non autorisé.','plugin-ufsc-gestion-club-13072025')], 403);
    }
    $user_club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    if ( ! $user_club || (int) $user_club->id !== $club_id ) {
        wp_send_json_error(['message'=>__('Licence non associée à votre club.','plugin-ufsc-gestion-club-13072025')], 403);
    }
    $product_id = function_exists('ufsc_get_licence_product_id_safe') ? ufsc_get_licence_product_id_safe() : ufsc_get_licence_product_id();
    if ( ! $product_id ) {
        wp_send_json_error(['message'=>__('Produit licence introuvable.','plugin-ufsc-gestion-club-13072025')], 500);
    }
    $cart_item_data = [
        'ufsc_existing_licence' => $licence_id,
        'ufsc_club_id'          => $club_id,
        'ufsc_product_type'     => 'licence',
    ];
    $added = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);
    if ( $added ) {
        $redirect = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
        wp_send_json_success(['redirect' => $redirect]);
    }
    wp_send_json_error(['message'=>__('Ajout au panier impossible.','plugin-ufsc-gestion-club-13072025')]);
}

// Ensure caps exist even if plugin was updated without reactivation
add_action('admin_init', function (){
    $role = get_role('administrator');
    if ($role) {

        foreach (['ufsc_manage', 'ufsc_manage_own'] as $cap) {

            if (!$role->has_cap($cap)) { $role->add_cap($cap); }
        }
    }
});



/**
 * Delete a draft licence (frontend)
 */
add_action('wp_ajax_ufsc_delete_licence_draft', 'ufsc_handle_delete_licence_draft');
function ufsc_handle_delete_licence_draft(){
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { wp_send_json_error(['message'=>__('Méthode non autorisée.','plugin-ufsc-gestion-club-13072025')], 405); }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message'=>__('Veuillez vous connecter.','plugin-ufsc-gestion-club-13072025')], 401);
    }
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'ufsc_frontend_nonce') && !wp_verify_nonce($nonce, 'ufsc_front_nonce')) {
        wp_send_json_error(['message'=>__('Jeton invalide.','plugin-ufsc-gestion-club-13072025')], 400);
    }
    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if (!$licence_id) {
        wp_send_json_error(['message'=>__('Licence manquante.','plugin-ufsc-gestion-club-13072025')], 400);
    }
    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_licences';
    $club_rel = $wpdb->prefix . 'ufsc_user_clubs';
    $club_id = (int) $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$club_rel} WHERE user_id=%d LIMIT 1", get_current_user_id()));
    if (!$club_id) {
        wp_send_json_error(['message'=>__('Accès non autorisé.','plugin-ufsc-gestion-club-13072025')], 403);
    }
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, club_id, statut FROM {$table} WHERE id=%d", $licence_id));
    if (!$row || (int)$row->club_id !== $club_id) {
        wp_send_json_error(['message'=>__('Licence introuvable.','plugin-ufsc-gestion-club-13072025')], 404);
    }
    if ($row->statut !== 'brouillon') {
        wp_send_json_error(['message'=>__('Seuls les brouillons peuvent être supprimés.','plugin-ufsc-gestion-club-13072025')], 422);
    }
    $deleted = $wpdb->delete($table, ['id'=>$licence_id], ['%d']);
    if ($deleted) {
        wp_send_json_success(['message'=>__('Brouillon supprimé.','plugin-ufsc-gestion-club-13072025')]);
    }
    wp_send_json_error(['message'=>__('Suppression impossible.','plugin-ufsc-gestion-club-13072025')], 500);
}


/**
 * Database migrations (idempotent).
 * - Ensures required columns and indexes exist.
 * - Stores schema version.
 */
function ufsc_run_migrations() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $version_option = 'ufsc_gc_db_version';
    $current = get_option($version_option, '');
    $target  = '2025.08.23';

    // Run only if not run before or on version mismatch
    if ($current === $target) { return; }

    // Tables
    $t_lic = $wpdb->prefix . 'ufsc_licences';
    $t_club = $wpdb->prefix . 'ufsc_clubs';
    $t_link = $wpdb->prefix . 'ufsc_user_clubs';

    // Ensure columns exist for licences
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t_lic} LIKE %s", 'statut'));
    if (!$col) {
        $wpdb->query(
            $wpdb->prepare(
                "ALTER TABLE {$t_lic} ADD COLUMN statut VARCHAR(20) NOT NULL DEFAULT %s",
                'brouillon'
            )
        );
    }
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t_lic} LIKE %s", 'date_creation'));
    if (!$col) {
        $wpdb->query("ALTER TABLE {$t_lic} ADD COLUMN date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t_lic} LIKE %s", 'club_id'));
    if (!$col) {
        $wpdb->query(
            $wpdb->prepare(
                "ALTER TABLE {$t_lic} ADD COLUMN club_id BIGINT UNSIGNED NOT NULL DEFAULT %d",
                0
            )
        );
    }
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t_lic} LIKE %s", 'payment_status'));
    if (!$col) {
        $wpdb->query(
            $wpdb->prepare(
                "ALTER TABLE {$t_lic} ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT %s",
                'pending'
            )
        );
    }

    // Ensure columns for clubs logo
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t_club} LIKE %s", 'pack_credits_total'));
    if (!$col) {
        $wpdb->query(
            $wpdb->prepare(
                "ALTER TABLE {$t_club} ADD COLUMN pack_credits_total INT NOT NULL DEFAULT %d",
                0
            )
        );
    }
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t_club} LIKE %s", 'pack_credits_used'));
    if (!$col) {
        $wpdb->query(
            $wpdb->prepare(
                "ALTER TABLE {$t_club} ADD COLUMN pack_credits_used INT NOT NULL DEFAULT %d",
                0
            )
        );
    }
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t_club} LIKE %s", 'logo_attachment_id'));
    if (!$col) {
        $wpdb->query("ALTER TABLE {$t_club} ADD COLUMN logo_attachment_id BIGINT UNSIGNED NULL");
    }
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t_club} LIKE %s", 'logo_url'));
    if (!$col) {
        $wpdb->query("ALTER TABLE {$t_club} ADD COLUMN logo_url VARCHAR(255) NULL");
    }

    // Indexes (licences)
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$t_lic}", ARRAY_A);
    $idx_names = array();
    foreach ($indexes as $ix) { $idx_names[$ix['Key_name']] = true; }
    if (empty($idx_names['club_id'])) {
        $wpdb->query("ALTER TABLE {$t_lic} ADD INDEX club_id (club_id)");
    }
    if (empty($idx_names['statut'])) {
        $wpdb->query("ALTER TABLE {$t_lic} ADD INDEX statut (statut)");
    }
    if (empty($idx_names['date_creation'])) {
        $wpdb->query("ALTER TABLE {$t_lic} ADD INDEX date_creation (date_creation)");
    }

    // Indexes (link table)
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t_link)) == $t_link) {
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$t_link}", ARRAY_A);
        $idx_names = array();
        foreach ($indexes as $ix) { $idx_names[$ix['Key_name']] = true; }
        if (empty($idx_names['user_id'])) {
            $wpdb->query("ALTER TABLE {$t_link} ADD INDEX user_id (user_id)");
        }
        if (empty($idx_names['club_id'])) {
            $wpdb->query("ALTER TABLE {$t_link} ADD INDEX club_id (club_id)");
        }
    }

    update_option($version_option, $target);
}

// Hook on activation and early admin init (for upgrades without reactivation)
register_activation_hook(__FILE__, 'ufsc_run_migrations');
add_action('admin_init', 'ufsc_run_migrations');

// Load Packs & Exports admin
if (is_admin()) { require_once UFSC_PLUGIN_PATH . 'includes/admin/class-ufsc-pack-exports.php'; }


// === WooCommerce Pack Credits Logic ===
add_action('woocommerce_before_calculate_totals', 'ufsc_apply_pack_credit_to_cart', 20, 1);
function ufsc_apply_pack_credit_to_cart($cart){
    if (is_admin() && !defined('DOING_AJAX')) return;
    if (empty($cart)) return;
    $opts = get_option('ufsc_pack_settings', array('pack_credits'=>10));
    $licence_product_id = isset($opts['licence_product_id']) ? (int)$opts['licence_product_id'] : 0;
    if (!$licence_product_id) return;

    $club_id = 0;
    if (is_user_logged_in()) {
        global $wpdb;
        $club_id = (int) $wpdb->get_var($wpdb->prepare('SELECT club_id FROM '.$wpdb->prefix.'ufsc_user_clubs WHERE user_id=%d LIMIT 1', get_current_user_id()));
    }
    if (!$club_id) return;

    // Get remaining credits
    $club_table = $wpdb->prefix . 'ufsc_clubs';
    $credits = (int) $wpdb->get_var($wpdb->prepare('SELECT (pack_credits_total - pack_credits_used) FROM '.$club_table.' WHERE id=%d', $club_id));

    foreach ($cart->get_cart() as $key => $item) {
        $product = $item['data'];
        if (!$product) continue;
        if ((int)$product->get_id() !== $licence_product_id) continue;
        if ($credits > 0 && !isset($item['ufsc_pack_credit_applied'])) {
            // Apply free price
            $product->set_price(0);
            $item['ufsc_pack_credit_applied'] = 1;
            $credits--; // tentative reserve for current cart
        }
    }
}

// Persist metadata to order item
add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order){
    if (isset($values['ufsc_pack_credit_applied']) && $values['ufsc_pack_credit_applied']) {
        $item->add_meta_data('ufsc_pack_credit_applied', 1, true);
    }
    if (isset($values['ufsc_licence_data'])) {
        $item->add_meta_data('ufsc_licence_data', $values['ufsc_licence_data'], true);
    }
}, 10, 4);

// On order status change, grant pack credits or consume them
add_action('woocommerce_order_status_changed', function($order_id, $from, $to, $order){
    if (!$order) return;
    $opts = get_option('ufsc_pack_settings', array('pack_credits'=>10));
    $pack_product_id = isset($opts['pack_product_id']) ? (int)$opts['pack_product_id'] : 0;
    $licence_product_id = isset($opts['licence_product_id']) ? (int)$opts['licence_product_id'] : 0;
    $credits_per_pack = isset($opts['pack_credits']) ? (int)$opts['pack_credits'] : 10;
    if (!$pack_product_id && !$licence_product_id) return;
    // Only on processing/completed
    if (!in_array($to, array('processing','completed'), true)) return;

    $user_id = $order->get_user_id();
    if (!$user_id) return;
    global $wpdb;
    $club_id = (int) $wpdb->get_var($wpdb->prepare('SELECT club_id FROM '.$wpdb->prefix.'ufsc_user_clubs WHERE user_id=%d LIMIT 1', $user_id));
    if (!$club_id) return;
    $club_table = $wpdb->prefix . 'ufsc_clubs';

    foreach ($order->get_items() as $item_id => $item) {
        $product_id = (int) $item->get_product_id();
        if ($pack_product_id && $product_id === $pack_product_id) {
            $qty = (int) $item->get_quantity();
            if ($qty > 0) {
                $wpdb->query($wpdb->prepare('UPDATE '.$club_table.' SET pack_credits_total = pack_credits_total + %d WHERE id=%d', $qty * $credits_per_pack, $club_id));
            }
        }
        if ($licence_product_id && $product_id === $licence_product_id) {
            $applied = (int) $item->get_meta('ufsc_pack_credit_applied', true);
            if ($applied) {
                $wpdb->query($wpdb->prepare('UPDATE '.$club_table.' SET pack_credits_used = pack_credits_used + %d WHERE id=%d', 1, $club_id));
            }
        }
    }
}, 20, 4);

require_once UFSC_PLUGIN_PATH . 'includes/frontend/hooks/cart-router.php';

require_once UFSC_PLUGIN_PATH . 'includes/frontend/ajax/licence-drafts.php';

require_once UFSC_PLUGIN_PATH . 'includes/frontend/hooks/form-capture.php';
require_once UFSC_PLUGIN_PATH . 'includes/diag/endpoint.php';


// === UFSC v20.3 Fixes: Assets + Front AJAX binding ===
if ( defined('UFSC_PLUGIN_PATH') ) {
    $__ufsc_fix_assets = UFSC_PLUGIN_PATH . 'includes/class-ufsc-assets.php';
    if ( file_exists($__ufsc_fix_assets) ) require_once $__ufsc_fix_assets;
}

// UFSC v20.4 overrides
if ( defined('UFSC_PLUGIN_PATH') ) {
  $__ov = UFSC_PLUGIN_PATH . 'includes/overrides/club-licenses-override.php';
  if ( file_exists($__ov) ) require_once $__ov;
}

// Ensure override is required (safety)
if (defined('UFSC_PLUGIN_PATH')) { $ov = UFSC_PLUGIN_PATH.'includes/overrides/club-licenses-override.php'; if (file_exists($ov)) require_once $ov; }

// UFSC profix overrides loader
if ( defined('ABSPATH') ) { require_once __DIR__ . '/includes/overrides_profix/_loader.php'; }

/**
 * Handle licence deletion.
 */
function ufsc_admin_post_delete_licence() {

    if ( ! current_user_can('ufsc_manage_own') ) {

        wp_die(__('Accès refusé.', 'plugin-ufsc-gestion-club-13072025'));
    }

    $licence_id = isset($_GET['licence_id']) ? absint( wp_unslash( $_GET['licence_id'] ) ) : 0;
    if ( ! $licence_id ) {
        wp_die(__('ID de licence invalide.', 'plugin-ufsc-gestion-club-13072025'));
    }

    check_admin_referer('ufsc_delete_licence_' . $licence_id);

    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-ufsc-licenses-repository.php';
    $repo = new UFSC_Licenses_Repository();
    $success = $repo->soft_delete($licence_id);

    $message  = $success ? 'deleted' : 'delete_error';
    $redirect = add_query_arg([
        'page'       => 'ufsc_licenses_admin',
        'message'    => $message,
        'licence_id' => $licence_id,
    ], admin_url('admin.php'));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_ufsc_delete_licence', 'ufsc_admin_post_delete_licence');

/**
 * Handle licence reassignment.
 */
function ufsc_admin_post_reassign_licence() {

    if ( ! current_user_can('ufsc_manage_own') ) {

        wp_die(__('Accès refusé.', 'plugin-ufsc-gestion-club-13072025'));
    }

    $licence_id  = isset($_GET['licence_id']) ? absint( wp_unslash( $_GET['licence_id'] ) ) : 0;
    $new_club_id = isset($_GET['new_club_id']) ? absint( wp_unslash( $_GET['new_club_id'] ) ) : 0;

    if ( ! $licence_id || ! $new_club_id ) {
        wp_die(__('Paramètres invalides.', 'plugin-ufsc-gestion-club-13072025'));
    }

    check_admin_referer('ufsc_reassign_licence_' . $licence_id);

    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-ufsc-licenses-repository.php';
    $repo = new UFSC_Licenses_Repository();
    $updated = $repo->update($licence_id, ['club_id' => $new_club_id]);

    $message  = $updated !== false ? 'reassigned' : 'reassign_error';
    $redirect = add_query_arg([
        'page'       => 'ufsc_licenses_admin',
        'message'    => $message,
        'licence_id' => $licence_id,
    ], admin_url('admin.php'));

    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_ufsc_reassign_licence', 'ufsc_admin_post_reassign_licence');

/**
 * Display admin notices for licence actions.
 */
function ufsc_licence_actions_admin_notices() {
    if ( ! isset($_GET['message']) ) {
        return;
    }

    $message    = sanitize_text_field( wp_unslash( $_GET['message'] ) );
    $licence_id = isset($_GET['licence_id']) ? absint( wp_unslash( $_GET['licence_id'] ) ) : 0;

    switch ( $message ) {
        case 'deleted':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                sprintf(__('Licence #%d supprimée avec succès.', 'plugin-ufsc-gestion-club-13072025'), $licence_id) .
                '</p></div>';
            break;
        case 'reassigned':
            echo '<div class="notice notice-success is-dismissible"><p>' .
                sprintf(__('Licence #%d réaffectée avec succès.', 'plugin-ufsc-gestion-club-13072025'), $licence_id) .
                '</p></div>';
            break;
        case 'delete_error':
            echo '<div class="notice notice-error is-dismissible"><p>' .
                __('Erreur lors de la suppression de la licence.', 'plugin-ufsc-gestion-club-13072025') .
                '</p></div>';
            break;
        case 'reassign_error':
            echo '<div class="notice notice-error is-dismissible"><p>' .
                __('Erreur lors de la réaffectation de la licence.', 'plugin-ufsc-gestion-club-13072025') .
                '</p></div>';
            break;
    }
}
add_action('admin_notices', 'ufsc_licence_actions_admin_notices');

/**
 * Inline script for reassign action.
 */
function ufsc_reassign_licence_inline_script() {
    if ( ! isset($_GET['page']) || 'ufsc_licenses_admin' !== $_GET['page'] ) {
        return;
    }
    ?>
    <script>
    document.addEventListener('click',function(e){
        var link=e.target.closest('.ufsc-reassign-licence');
        if(!link){return;}
        e.preventDefault();
        var id=link.getAttribute('data-id');
        var nonce=link.getAttribute('data-nonce');
        var newClub=prompt('<?php echo esc_js(__('ID du nouveau club ?', 'plugin-ufsc-gestion-club-13072025')); ?>');
        if(!newClub){return;}
        if(!confirm('<?php echo esc_js(__('Confirmer la réaffectation ?', 'plugin-ufsc-gestion-club-13072025')); ?>')){return;}
        var url='<?php echo admin_url('admin-post.php'); ?>?action=ufsc_reassign_licence&licence_id='+id+'&new_club_id='+encodeURIComponent(newClub)+'&_wpnonce='+nonce;
        window.location.href=url;
    });
    </script>
    <?php
}
add_action('admin_footer', 'ufsc_reassign_licence_inline_script');
