<?php
/**
 * Simple test script to verify AJAX handlers
 * Run from WordPress admin to test the new AJAX endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

function ufsc_run_sync_tests() {
    if (!current_user_can('ufsc_manage')) {
        return 'Permission denied';
    }

    $test_results = [];

    // Test 1: Check if AJAX handlers are registered
    $test_results['ajax_handlers'] = [
        'ufsc_save_club' => has_action('wp_ajax_ufsc_save_club'),
        'ufsc_get_club_data' => has_action('wp_ajax_ufsc_get_club_data'),
        'ufsc_get_clubs_list' => has_action('wp_ajax_ufsc_get_clubs_list'),
    ];

    // Test 2: Check if validation function exists
    $test_results['validation_function'] = function_exists('ufsc_validate_club_data');

    // Test 3: Check if logging function exists
    $test_results['logging_function'] = function_exists('ufsc_log_operation');

    // Test 4: Check if sync monitor class is loaded
    $test_results['sync_monitor'] = class_exists('UFSC_Sync_Monitor');

    // Test 5: Check database tables
    global $wpdb;
    $clubs_table = $wpdb->prefix . 'ufsc_clubs';
    $licences_table = $wpdb->prefix . 'ufsc_licences';
    
    $test_results['database'] = [
        'clubs_table' => $wpdb->get_var("SHOW TABLES LIKE '$clubs_table'") === $clubs_table,
        'licences_table' => $wpdb->get_var("SHOW TABLES LIKE '$licences_table'") === $licences_table,
    ];

    // Test 6: Test logging system
    ufsc_log_operation('test_operation', ['test_data' => 'test_value']);
    $test_results['logging_test'] = 'Log entry created';

    return $test_results;
}

// Add a admin notice to show test results when on admin pages
add_action('admin_notices', function() {
    if (isset($_GET['ufsc_run_tests']) && current_user_can('ufsc_manage')) {
        $results = ufsc_run_sync_tests();
        echo '<div class="notice notice-info"><p><strong>UFSC Sync Test Results:</strong></p>';
        echo '<pre>' . print_r($results, true) . '</pre></div>';
    }
});