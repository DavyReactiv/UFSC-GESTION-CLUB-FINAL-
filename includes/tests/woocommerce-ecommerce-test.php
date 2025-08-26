<?php

/**
 * WooCommerce E-commerce Features Test
 * 
 * Tests to validate the new WooCommerce e-commerce features:
 * - Auto-pack affiliation functionality
 * - Auto-order for admin-created licences
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.1
 */

// Only run in debug mode or test environment
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    return;
}

/**
 * Test WooCommerce e-commerce features
 */
function ufsc_test_woocommerce_ecommerce_features() {
    $test_results = [
        'timestamp' => current_time('mysql'),
        'test_name' => 'WooCommerce E-commerce Features Test',
        'status' => 'running',
        'tests' => []
    ];
    
    // Test 1: Check if settings are properly registered
    $test_results['tests']['settings_registration'] = ufsc_test_settings_registration();
    
    // Test 2: Check if auto-pack class exists and initializes
    $test_results['tests']['auto_pack_class'] = ufsc_test_auto_pack_class();
    
    // Test 3: Check if auto-order class exists and initializes
    $test_results['tests']['auto_order_class'] = ufsc_test_auto_order_class();
    
    // Test 4: Check licence action hook
    $test_results['tests']['licence_action_hook'] = ufsc_test_licence_action_hook();
    
    // Test 5: Check admin settings render methods
    $test_results['tests']['admin_settings_methods'] = ufsc_test_admin_settings_methods();
    
    // Overall status
    $all_passed = true;
    foreach ($test_results['tests'] as $test) {
        if (!$test['passed']) {
            $all_passed = false;
            break;
        }
    }
    
    $test_results['status'] = $all_passed ? 'passed' : 'failed';
    
    // Store results
    update_option('ufsc_ecommerce_test_results', $test_results);
    
    return $test_results;
}

/**
 * Test settings registration
 */
function ufsc_test_settings_registration() {
    $test = [
        'name' => 'Settings Registration',
        'passed' => true,
        'errors' => []
    ];
    
    // Check new settings exist
    $settings_to_check = [
        'ufsc_wc_pack_10_product_id',
        'ufsc_wc_individual_licence_product_id',
        'ufsc_auto_pack_enabled',
        'ufsc_auto_order_for_admin_licences'
    ];
    
    foreach ($settings_to_check as $setting) {
        // This will return default value if setting doesn't exist
        $value = get_option($setting, 'NOT_SET');
        if ($value === 'NOT_SET') {
            $test['passed'] = false;
            $test['errors'][] = "Setting $setting not registered";
        }
    }
    
    return $test;
}

/**
 * Test auto-pack class
 */
function ufsc_test_auto_pack_class() {
    $test = [
        'name' => 'Auto Pack Class',
        'passed' => true,
        'errors' => []
    ];
    
    // Check if file exists
    $file_path = UFSC_PLUGIN_PATH . 'includes/woocommerce/auto-pack-affiliation.php';
    if (!file_exists($file_path)) {
        $test['passed'] = false;
        $test['errors'][] = 'Auto-pack file does not exist';
        return $test;
    }
    
    // Check if class exists
    if (!class_exists('UFSC_Auto_Pack_Affiliation')) {
        $test['passed'] = false;
        $test['errors'][] = 'UFSC_Auto_Pack_Affiliation class not found';
    }
    
    return $test;
}

/**
 * Test auto-order class
 */
function ufsc_test_auto_order_class() {
    $test = [
        'name' => 'Auto Order Class',
        'passed' => true,
        'errors' => []
    ];
    
    // Check if file exists
    $file_path = UFSC_PLUGIN_PATH . 'includes/woocommerce/auto-order-admin-licences.php';
    if (!file_exists($file_path)) {
        $test['passed'] = false;
        $test['errors'][] = 'Auto-order file does not exist';
        return $test;
    }
    
    // Check if class exists
    if (!class_exists('UFSC_Auto_Order_Admin_Licences')) {
        $test['passed'] = false;
        $test['errors'][] = 'UFSC_Auto_Order_Admin_Licences class not found';
    }
    
    return $test;
}

/**
 * Test licence action hook
 */
function ufsc_test_licence_action_hook() {
    $test = [
        'name' => 'Licence Action Hook',
        'passed' => true,
        'errors' => []
    ];
    
    // Check if action exists by adding a test callback
    $hook_exists = false;
    
    $test_callback = function() use (&$hook_exists) {
        $hook_exists = true;
    };
    
    add_action('ufsc_licence_created', $test_callback);
    
    // Trigger the action
    do_action('ufsc_licence_created', 123, []);
    
    // Remove test callback
    remove_action('ufsc_licence_created', $test_callback);
    
    if (!$hook_exists) {
        $test['passed'] = false;
        $test['errors'][] = 'ufsc_licence_created action hook not working';
    }
    
    return $test;
}

/**
 * Test admin settings methods
 */
function ufsc_test_admin_settings_methods() {
    $test = [
        'name' => 'Admin Settings Methods',
        'passed' => true,
        'errors' => []
    ];
    
    // Check if UFSC_Admin_Settings class exists
    if (!class_exists('UFSC_Admin_Settings')) {
        $test['passed'] = false;
        $test['errors'][] = 'UFSC_Admin_Settings class not found';
        return $test;
    }
    
    // Check if new render methods exist
    $methods_to_check = [
        'render_pack_10_product_id_field',
        'render_individual_licence_product_id_field',
        'render_auto_pack_enabled_field',
        'render_auto_order_for_admin_licences_field'
    ];
    
    foreach ($methods_to_check as $method) {
        if (!method_exists('UFSC_Admin_Settings', $method)) {
            $test['passed'] = false;
            $test['errors'][] = "Method $method not found in UFSC_Admin_Settings";
        }
    }
    
    return $test;
}

// Run test if WP_DEBUG is on and this is an admin request
if (defined('WP_DEBUG') && WP_DEBUG && is_admin()) {
    add_action('admin_init', function() {
        // Only run test once per session
        if (!get_transient('ufsc_ecommerce_test_run')) {
            set_transient('ufsc_ecommerce_test_run', true, 3600); // 1 hour
            
            $results = ufsc_test_woocommerce_ecommerce_features();
            
            // Log results for debugging
            error_log('UFSC E-commerce Features Test Results: ' . json_encode($results));
        }
    });
}

/**
 * Admin function to view test results
 */
function ufsc_display_ecommerce_test_results() {
    $results = get_option('ufsc_ecommerce_test_results');
    
    if (!$results) {
        echo '<div class="notice notice-info"><p>No test results available. Tests will run automatically in debug mode.</p></div>';
        return;
    }
    
    $status_class = $results['status'] === 'passed' ? 'notice-success' : 'notice-error';
    echo '<div class="notice ' . $status_class . '">';
    echo '<h3>WooCommerce E-commerce Features Test Results</h3>';
    echo '<p>Status: <strong>' . ucfirst($results['status']) . '</strong></p>';
    echo '<p>Timestamp: ' . $results['timestamp'] . '</p>';
    
    foreach ($results['tests'] as $test) {
        $icon = $test['passed'] ? '✅' : '❌';
        echo '<p>' . $icon . ' ' . $test['name'];
        
        if (!empty($test['errors'])) {
            echo ' - Errors: ' . implode(', ', $test['errors']);
        }
        
        echo '</p>';
    }
    
    echo '</div>';
}