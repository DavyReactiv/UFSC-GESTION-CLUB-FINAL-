<?php
/**
 * Test file for attestation functionality
 * This file tests the admin attestation upload and frontend display features
 * 
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for attestation functionality
 */
class UFSC_Attestation_Functionality_Test
{
    /**
     * Run all attestation functionality tests
     */
    public static function run_tests()
    {
        $results = [];
        
        $results[] = self::test_helper_function_exists();
        $results[] = self::test_meta_field_handling();
        $results[] = self::test_ajax_handler_registration();
        $results[] = self::test_frontend_display_logic();
        
        return $results;
    }
    
    /**
     * Test that helper function exists
     */
    private static function test_helper_function_exists()
    {
        $required_functions = [
            'ufsc_club_has_admin_attestation',
            'ufsc_get_club_attestation_url',
            'ufsc_club_get_attestation_attachment_id',
            'ufsc_club_set_attestation_attachment_id',
            'ufsc_get_attestation_file_extension',
            'ufsc_output_file_download'
        ];
        
        $missing_functions = [];
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                $missing_functions[] = $function;
            }
        }
        
        $all_exist = empty($missing_functions);
        
        return [
            'test' => 'Helper Functions Exist',
            'passed' => $all_exist,
            'message' => $all_exist ? 
                'All required helper functions exist' : 
                'Missing helper functions: ' . implode(', ', $missing_functions),
            'details' => $missing_functions
        ];
    }
    
    /**
     * Test meta field handling
     */
    private static function test_meta_field_handling()
    {
        $errors = [];
        $passed = true;
        
        // Test that meta keys are properly formatted
        $meta_keys = ['_ufsc_attestation_affiliation', '_ufsc_attestation_assurance'];
        
        foreach ($meta_keys as $meta_key) {
            if (!preg_match('/^_ufsc_attestation_(affiliation|assurance)$/', $meta_key)) {
                $errors[] = "Invalid meta key format: $meta_key";
                $passed = false;
            }
        }
        
        // Test helper function logic with sample data
        if (function_exists('ufsc_club_has_admin_attestation')) {
            // Test with invalid type
            $result = ufsc_club_has_admin_attestation(1, 'invalid_type');
            if ($result !== false) {
                $errors[] = 'Helper function should return false for invalid types';
                $passed = false;
            }
        } else {
            $errors[] = 'Helper function does not exist';
            $passed = false;
        }
        
        return [
            'test' => 'Meta Field Handling',
            'passed' => $passed,
            'message' => $passed ? 
                'Meta field handling works correctly' : 
                'Issues found with meta field handling',
            'details' => $errors
        ];
    }
    
    /**
     * Test AJAX handler registration
     */
    private static function test_ajax_handler_registration()
    {
        $errors = [];
        $passed = true;
        
        // Check if remove attestation function exists
        if (!function_exists('ufsc_handle_remove_attestation')) {
            $errors[] = 'AJAX handler function ufsc_handle_remove_attestation not found';
            $passed = false;
        }
        
        // Check if new actions are registered
        $new_actions = [
            'wp_ajax_ufsc_upload_club_attestation',
            'wp_ajax_ufsc_delete_club_attestation',
            'wp_ajax_ufsc_attach_existing_club_attestation',
            'wp_ajax_ufsc_upload_licence_attestation',
            'wp_ajax_ufsc_delete_licence_attestation'
        ];
        
        foreach ($new_actions as $action) {
            if (!has_action($action)) {
                $errors[] = "AJAX action $action not registered";
                $passed = false;
            }
        }
        
        return [
            'test' => 'AJAX Handler Registration',
            'passed' => $passed,
            'message' => $passed ? 
                'AJAX handlers properly registered' : 
                'Issues found with AJAX handler registration',
            'details' => $errors
        ];
    }
    
    /**
     * Test frontend display logic
     */
    private static function test_frontend_display_logic()
    {
        $errors = [];
        $passed = true;
        
        // Test download function modifications
        $download_functions = [
            'ufsc_download_attestation_affiliation',
            'ufsc_download_attestation_assurance'
        ];
        
        foreach ($download_functions as $function_name) {
            if (!function_exists($function_name)) {
                $errors[] = "Download function $function_name not found";
                $passed = false;
            }
        }
        
        return [
            'test' => 'Frontend Display Logic',
            'passed' => $passed,
            'message' => $passed ? 
                'Frontend display logic properly implemented' : 
                'Issues found with frontend display logic',
            'details' => $errors
        ];
    }
    
    /**
     * Output test results in HTML format
     */
    public static function output_test_results()
    {
        $results = self::run_tests();
        
        echo '<div class="wrap ufsc-ui">';
        echo '<h1>Attestation Functionality Test Results</h1>';
        
        $total_tests = count($results);
        $passed_tests = array_sum(array_column($results, 'passed'));
        
        echo '<div class="notice ' . ($passed_tests === $total_tests ? 'notice-success' : 'notice-warning') . '">';
        echo '<p><strong>Test Summary:</strong> ' . $passed_tests . '/' . $total_tests . ' tests passed</p>';
        echo '</div>';
        
        echo '<table class="widefat">';
        echo '<thead><tr><th>Test</th><th>Status</th><th>Message</th><th>Details</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($results as $result) {
            $status_class = $result['passed'] ? 'notice-success' : 'notice-error';
            $status_text = $result['passed'] ? '✓ PASS' : '✗ FAIL';
            
            echo '<tr>';
            echo '<td>' . esc_html($result['test']) . '</td>';
            echo '<td><span class="' . $status_class . '">' . $status_text . '</span></td>';
            echo '<td>' . esc_html($result['message']) . '</td>';
            echo '<td>';
            if (!empty($result['details'])) {
                echo '<ul>';
                foreach ($result['details'] as $detail) {
                    echo '<li>' . esc_html($detail) . '</li>';
                }
                echo '</ul>';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
}

// Auto-run tests if accessed directly in admin
if (is_admin() && isset($_GET['run_attestation_tests'])) {
    add_action('admin_notices', function() {
        UFSC_Attestation_Functionality_Test::output_test_results();
    });
}