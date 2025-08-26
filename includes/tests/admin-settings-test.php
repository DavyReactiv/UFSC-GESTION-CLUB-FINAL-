<?php
/**
 * Simple integration test for UFSC Admin Settings
 * 
 * This test verifies that the admin settings page loads correctly
 * and that settings can be saved and retrieved properly.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test UFSC Admin Settings functionality
 */
function test_ufsc_admin_settings() {
    $results = [];
    
    // Test 1: Check if admin settings class exists
    if (class_exists('UFSC_Admin_Settings')) {
        $results['admin_class'] = 'PASS - UFSC_Admin_Settings class exists';
    } else {
        $results['admin_class'] = 'FAIL - UFSC_Admin_Settings class not found';
    }
    
    // Test 2: Test default option values
    $affiliation_id = (int) get_option('ufsc_wc_affiliation_product_id', 0);
    if ($affiliation_id === 4823) {
        $results['default_affiliation'] = 'PASS - Default affiliation product ID is correct';
    } else {
        $results['default_affiliation'] = 'FAIL - Default affiliation product ID incorrect: ' . var_export($affiliation_id, true);
    }
    
    $license_ids = get_option('ufsc_wc_license_product_ids', 'not_set');
    if ($license_ids === 'not_set' || $license_ids === '2934') {
        $results['default_license'] = 'PASS - Default license product IDs are correct';
    } else {
        $results['default_license'] = 'FAIL - Default license product IDs incorrect: ' . $license_ids;
    }
    
    $auto_create = (bool) get_option('ufsc_auto_create_user', false);
    if ($auto_create === false) {
        $results['default_auto_create'] = 'PASS - Default auto create user setting is correct';
    } else {
        $results['default_auto_create'] = 'FAIL - Default auto create user setting incorrect: ' . var_export($auto_create, true);
    }
    
    $require_login = (bool) get_option('ufsc_require_login_shortcodes', true);
    if ($require_login === true) {
        $results['default_require_login'] = 'PASS - Default require login setting is correct';
    } else {
        $results['default_require_login'] = 'FAIL - Default require login setting incorrect: ' . var_export($require_login, true);
    }
    
    // Test 3: Test setting and getting values
    update_option('ufsc_wc_affiliation_product_id', 3000);
    $saved_value = get_option('ufsc_wc_affiliation_product_id');
    if ($saved_value == 3000) {
        $results['save_affiliation'] = 'PASS - Can save and retrieve affiliation product ID';
    } else {
        $results['save_affiliation'] = 'FAIL - Cannot save affiliation product ID: ' . $saved_value;
    }
    
    // Test 4: Test CSV sanitization (if admin class is available)
    if (class_exists('UFSC_Admin_Settings')) {
        $admin_settings = new UFSC_Admin_Settings();
        
        // Test valid CSV
        $test_csv = '2934, 2935, 2936';
        $sanitized = $admin_settings->sanitize_csv_ids($test_csv);
        if ($sanitized === '2934,2935,2936') {
            $results['csv_sanitization'] = 'PASS - CSV sanitization works correctly';
        } else {
            $results['csv_sanitization'] = 'FAIL - CSV sanitization incorrect: ' . $sanitized;
        }
        
        // Test empty CSV
        $empty_sanitized = $admin_settings->sanitize_csv_ids('');
        if ($empty_sanitized === '2934') {
            $results['csv_empty'] = 'PASS - Empty CSV returns default value';
        } else {
            $results['csv_empty'] = 'FAIL - Empty CSV sanitization incorrect: ' . $empty_sanitized;
        }
    }
    
    // Clean up test values
    delete_option('ufsc_wc_affiliation_product_id');
    
    return $results;
}

/**
 * Display test results
 */
function display_ufsc_admin_settings_test_results() {
    if (!current_user_can('manage_ufsc')) {
        return;
    }
    
    echo '<div class="wrap">';
    echo '<h2>UFSC Admin Settings - Test Results</h2>';
    
    $results = test_ufsc_admin_settings();
    
    echo '<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">';
    foreach ($results as $test_name => $result) {
        $color = strpos($result, 'PASS') === 0 ? 'green' : 'red';
        echo '<p style="color: ' . $color . ';"><strong>' . ucfirst(str_replace('_', ' ', $test_name)) . ':</strong> ' . esc_html($result) . '</p>';
    }
    echo '</div>';
    
    $total_tests = count($results);
    $passed_tests = count(array_filter($results, function($result) {
        return strpos($result, 'PASS') === 0;
    }));
    
    echo '<p><strong>Summary:</strong> ' . $passed_tests . '/' . $total_tests . ' tests passed.</p>';
    echo '</div>';
}

// Add test to admin if we're in debug mode
if (WP_DEBUG && is_admin()) {
    add_action('admin_notices', function() {
        if (isset($_GET['ufsc_test_admin_settings'])) {
            display_ufsc_admin_settings_test_results();
        }
    });
    
    // Add a test link for admins
    add_action('admin_bar_menu', function($wp_admin_bar) {
        if (current_user_can('manage_ufsc')) {
            $wp_admin_bar->add_node([
                'id' => 'ufsc_test_admin_settings',
                'title' => 'Test UFSC Admin Settings',
                'href' => admin_url('admin.php?page=ufsc-settings&ufsc_test_admin_settings=1')
            ]);
        }
    });
}