<?php

/**
 * Frontend Refonte Validation Test
 * 
 * Simple test to validate the main components of the frontend refonte
 * This file can be used to check that all components load correctly
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Only run in debug mode
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    return;
}

/**
 * Test the main components of the frontend refonte
 */
function ufsc_test_frontend_refonte_components() {
    $test_results = [];
    $test_results['timestamp'] = current_time('mysql');
    
    // Test 1: Check if new form renderers exist
    $form_files = [
        'licence-form-render.php' => UFSC_PLUGIN_PATH . 'includes/frontend/forms/licence-form-render.php',
        'affiliation-form-render.php' => UFSC_PLUGIN_PATH . 'includes/frontend/forms/affiliation-form-render.php'
    ];
    
    foreach ($form_files as $name => $file) {
        $test_results['forms'][$name] = [
            'exists' => file_exists($file),
            'readable' => is_readable($file),
            'size' => file_exists($file) ? filesize($file) : 0
        ];
    }
    
    // Test 2: Check if helper files exist and functions are defined
    $helper_functions = [
        'ufsc_map_order_status_to_license_status',
        'ufsc_render_product_button', 
        'ufsc_generate_licence_button',
        'ufsc_get_license_status_badge'
    ];
    
    foreach ($helper_functions as $function) {
        $test_results['helper_functions'][$function] = function_exists($function);
    }
    
    // Test 3: Check if new shortcodes are registered
    $new_shortcodes = [
        'ufsc_licence_form',
        'ufsc_affiliation_form',
        'ufsc_club_quota',
        'ufsc_club_stats',
        'ufsc_license_list'
    ];
    
    global $shortcode_tags;
    foreach ($new_shortcodes as $shortcode) {
        $test_results['shortcodes'][$shortcode] = isset($shortcode_tags[$shortcode]);
    }
    
    // Test 4: Check WooCommerce integration hooks
    $wc_hooks = [
        'woocommerce_before_add_to_cart_button' => 'ufsc_add_licence_fields_to_product_page',
        'woocommerce_order_status_completed' => 'ufsc_process_license_creation_after_payment',
        'woocommerce_order_status_changed' => 'ufsc_handle_order_status_change'
    ];
    
    foreach ($wc_hooks as $hook => $function) {
        $test_results['wc_hooks'][$hook] = has_action($hook, $function) !== false;
    }
    
    // Test 5: Check AJAX actions
    $ajax_actions = [
        'wp_ajax_ufsc_add_licence_to_cart',
        'wp_ajax_ufsc_add_affiliation_to_cart'
    ];
    
    foreach ($ajax_actions as $action) {
        $test_results['ajax_actions'][$action] = has_action($action) !== false;
    }
    
    // Test 6: Check CSS file
    $css_file = UFSC_PLUGIN_PATH . 'assets/css/ufsc-frontend.css';
    $test_results['assets']['css'] = [
        'exists' => file_exists($css_file),
        'size' => file_exists($css_file) ? filesize($css_file) : 0,
        'classes_count' => 0
    ];
    
    if (file_exists($css_file)) {
        $css_content = file_get_contents($css_file);
        $test_results['assets']['css']['classes_count'] = preg_match_all('/\.ufsc-[a-zA-Z0-9_-]+/', $css_content);
    }
    
    // Test 7: Check JS file
    $js_file = UFSC_PLUGIN_PATH . 'assets/js/ufsc-frontend.js';
    $test_results['assets']['js'] = [
        'exists' => file_exists($js_file),
        'size' => file_exists($js_file) ? filesize($js_file) : 0
    ];
    
    return $test_results;
}

/**
 * Display test results in admin
 */
function ufsc_display_test_results() {
    if (!current_user_can('manage_ufsc')) {
        return;
    }
    
    $results = ufsc_test_frontend_refonte_components();
    
    echo '<div class="notice notice-info">';
    echo '<h3>🧪 UFSC Frontend Refonte - Test Results</h3>';
    echo '<p><strong>Timestamp:</strong> ' . esc_html($results['timestamp']) . '</p>';
    
    // Forms test
    echo '<h4>📝 Form Renderers</h4>';
    foreach ($results['forms'] as $name => $info) {
        $status = $info['exists'] && $info['readable'] ? '✅' : '❌';
        echo '<p>' . $status . ' ' . esc_html($name) . ' (' . esc_html($info['size']) . ' bytes)</p>';
    }
    
    // Helper functions test
    echo '<h4>🔧 Helper Functions</h4>';
    foreach ($results['helper_functions'] as $function => $exists) {
        $status = $exists ? '✅' : '❌';
        echo '<p>' . $status . ' ' . esc_html($function) . '()</p>';
    }
    
    // Shortcodes test
    echo '<h4>🏷️ New Shortcodes</h4>';
    foreach ($results['shortcodes'] as $shortcode => $registered) {
        $status = $registered ? '✅' : '❌';
        echo '<p>' . $status . ' [' . esc_html($shortcode) . ']</p>';
    }
    
    // WooCommerce hooks test
    echo '<h4>🛒 WooCommerce Integration</h4>';
    foreach ($results['wc_hooks'] as $hook => $attached) {
        $status = $attached ? '✅' : '❌';
        echo '<p>' . $status . ' ' . esc_html($hook) . '</p>';
    }
    
    // AJAX actions test
    echo '<h4>⚡ AJAX Actions</h4>';
    foreach ($results['ajax_actions'] as $action => $registered) {
        $status = $registered ? '✅' : '❌';
        echo '<p>' . $status . ' ' . esc_html($action) . '</p>';
    }
    
    // Assets test
    echo '<h4>🎨 Assets</h4>';
    $css_status = $results['assets']['css']['exists'] ? '✅' : '❌';
    echo '<p>' . $css_status . ' CSS: ' . esc_html($results['assets']['css']['size']) . ' bytes, ' . 
         esc_html($results['assets']['css']['classes_count']) . ' classes</p>';
         
    $js_status = $results['assets']['js']['exists'] ? '✅' : '❌';
    echo '<p>' . $js_status . ' JavaScript: ' . esc_html($results['assets']['js']['size']) . ' bytes</p>';
    
    echo '</div>';
}

// Only show test results on UFSC admin pages in debug mode
if (is_admin() && isset($_GET['page']) && strpos($_GET['page'], 'ufsc') !== false) {
    add_action('admin_notices', 'ufsc_display_test_results');
}