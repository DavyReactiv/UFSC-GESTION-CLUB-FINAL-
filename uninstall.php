<?php
/**
 * UFSC Gestion Club - Uninstall script
 * 
 * This file is executed when the plugin is deleted through WordPress admin.
 * It removes all plugin data including database tables and options.
 * 
 * @package UFSC_Gestion_Club
 * @version 1.2.0
 */

// Exit if accessed directly or if uninstall not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Security check: verify this is an authorized uninstall
if (!current_user_can('activate_plugins')) {
    return;
}

// Additional security: verify this is the correct plugin
if (!defined('WP_UNINSTALL_PLUGIN') || WP_UNINSTALL_PLUGIN !== 'Plugin_UFSC_GESTION_CLUB_13072025/Plugin_UFSC_GESTION_CLUB_13072025.php') {
    return;
}

/**
 * Remove all plugin data
 * This is a complete cleanup - use with caution!
 */
function ufsc_uninstall_cleanup() {
    global $wpdb;
    
    // Security: Only proceed if user has manage_options capability
    if (!current_user_can('manage_ufsc')) {
        return;
    }
    
    // Remove custom database tables
    $tables_to_remove = [
        $wpdb->prefix . 'ufsc_clubs',
        $wpdb->prefix . 'ufsc_licences'
    ];
    
    foreach ($tables_to_remove as $table) {
        // Note: Table names cannot be prepared with $wpdb->prepare() 
        // Using $wpdb->prefix is safe as it's controlled by WordPress
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }
    
    // Remove plugin options
    $options_to_remove = [
        'ufsc_gestion_club_version',
        'ufsc_gestion_club_db_version',
        'ufsc_frontend_pro_enabled',
        'ufsc_frontend_pro_notifications',
        'ufsc_frontend_pro_datatables',
        'ufsc_frontend_pro_license_page',
        'ufsc_frontend_pro_club_page',
        'ufsc_frontend_pro_affiliation_page',
        'ufsc_frontend_pro_attestation_page',
        'ufsc_licence_product_id',
        'ufsc_affiliation_product_id',
        'ufsc_settings',
        // WooCommerce integration options
        'ufsc_wc_affiliation_product_id',
        'ufsc_wc_license_product_ids'
    ];
    
    foreach ($options_to_remove as $option) {
        delete_option($option);
    }
    
    // Remove any transients
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '%_transient_ufsc_%'
        )
    );
    
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            '%_transient_timeout_ufsc_%'
        )
    );
    
    // Remove user meta related to the plugin
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            'ufsc_%'
        )
    );
    
    // Remove custom post meta if any
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            'ufsc_%'
        )
    );
    
    // Clean up uploaded files directory (optional - commented out for safety)
    // $upload_dir = wp_upload_dir();
    // $ufsc_dir = $upload_dir['basedir'] . '/ufsc-documents/';
    // if (is_dir($ufsc_dir)) {
    //     // Remove directory and all files (use with extreme caution!)
    //     // This is commented out to prevent accidental data loss
    // }
}

// Execute cleanup
ufsc_uninstall_cleanup();

// Log the uninstall for debugging (optional)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('UFSC Gestion Club plugin uninstalled successfully');
}