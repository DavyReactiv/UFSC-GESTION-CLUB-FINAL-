<?php
/**
 * Monetico Payment Plugin Compatibility Shim
 * 
 * Filters out specific deprecated property notices from the MoneticoPaiement plugin
 * while preserving other important error reporting.
 *
 * @package UFSC_Gestion_Club
 * @subpackage Compatibility
 * @since 1.0.3
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Custom error handler to filter out specific Monetico deprecated notices
 */
function ufsc_monetico_error_handler($errno, $errstr, $errfile, $errline) {
    // Only handle E_DEPRECATED errors
    if ($errno !== E_DEPRECATED) {
        return false; // Let the normal error handler deal with it
    }
    
    // Filter out specific MoneticoPaiement deprecated property notices
    $monetico_patterns = [
        '/MoneticoPaiement::\$monetico_code_site_payment_display_method/',
        '/MoneticoPaiement::\$/',
        '/Creation of dynamic property MoneticoPaiement::/'
    ];
    
    foreach ($monetico_patterns as $pattern) {
        if (preg_match($pattern, $errstr)) {
            // Suppress this specific error by returning true
            return true;
        }
    }
    
    // Let other deprecated notices through
    return false;
}

/**
 * Initialize Monetico compatibility shim
 */
function ufsc_init_monetico_compat() {
    // Check if MoneticoPaiement plugin is active
    if (class_exists('MoneticoPaiement') || function_exists('monetico_payment_init')) {
        // Set our custom error handler
        set_error_handler('ufsc_monetico_error_handler', E_DEPRECATED);
        
        // Log that we've enabled the compatibility shim
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UFSC: Monetico compatibility shim enabled to filter deprecated property notices');
        }
    }
}

/**
 * Restore the previous error handler
 */
function ufsc_restore_error_handler() {
    restore_error_handler();
}

// Initialize the compatibility shim
add_action('init', 'ufsc_init_monetico_compat', 1);

// Restore error handler on shutdown to be safe
add_action('shutdown', 'ufsc_restore_error_handler', 999);