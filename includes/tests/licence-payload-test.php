<?php

/**
 * Licence Payload Harmonization Test
 * 
 * Tests to validate that the AJAX add-to-cart endpoint includes all required
 * licence fields in the payload, as specified in the problem statement.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.1
 */

// Only run in debug mode or test environment
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    return;
}

/**
 * Test licence payload completeness
 */
function ufsc_test_licence_payload_completeness() {
    $test_results = [
        'timestamp' => current_time('mysql'),
        'test_name' => 'Licence Payload Completeness Test',
        'status' => 'running'
    ];
    
    // Define all expected fields according to problem statement
    $expected_fields = [
        // Basic personal info
        'nom', 'prenom', 'sexe', 'date_naissance', 'email',
        
        // Address info
        'adresse', 'suite_adresse', 'code_postal', 'ville', 'region',
        
        // Phone info (should be tel_fixe/tel_mobile, NOT telephone)
        'tel_fixe', 'tel_mobile',
        
        // Professional info
        'reduction_benevole', 'reduction_postier', 'identifiant_laposte', 
        'profession', 'fonction_publique',
        
        // Consent and preferences
        'diffusion_image', 'infos_fsasptt', 'infos_asptt', 'infos_cr', 
        'infos_partenaires', 'honorabilite',
        
        // Sport/licence info
        'competition', 'licence_delegataire', 'numero_licence_delegataire',
        
        // Insurance
        'assurance_dommage_corporel', 'assurance_assistance',
        
        // Additional info
        'note'
    ];
    
    // Fields that should NOT be present
    $deprecated_fields = ['telephone']; // Should be replaced by tel_fixe/tel_mobile
    
    // Mock $_POST data with all fields
    $mock_post_data = [];
    foreach ($expected_fields as $field) {
        if (in_array($field, ['reduction_benevole', 'reduction_postier', 'fonction_publique', 
                              'competition', 'licence_delegataire', 'diffusion_image', 
                              'infos_fsasptt', 'infos_asptt', 'infos_cr', 'infos_partenaires', 
                              'honorabilite', 'assurance_dommage_corporel', 'assurance_assistance'])) {
            $mock_post_data[$field] = '1'; // Checkbox fields
        } elseif ($field === 'email') {
            $mock_post_data[$field] = 'test@example.com';
        } elseif ($field === 'note') {
            $mock_post_data[$field] = 'Test note content';
        } else {
            $mock_post_data[$field] = 'test_value';
        }
    }
    
    // Add required fields for AJAX handler
    $mock_post_data['club_id'] = 1;
    $mock_post_data['_ufsc_licence_nonce'] = 'test_nonce';
    
    // Backup original $_POST
    $original_post = $_POST;
    $_POST = $mock_post_data;
    
    try {
        // Extract the licence payload creation logic from ajax-add-to-cart.php
        // We'll test this by looking at the file content and simulating the payload creation
        $ajax_file_path = UFSC_PLUGIN_PATH . 'includes/licences/ajax-add-to-cart.php';
        
        if (!file_exists($ajax_file_path)) {
            $test_results['status'] = 'failed';
            $test_results['error'] = 'AJAX file not found';
            return $test_results;
        }
        
        // Read and analyze the current ajax file
        $ajax_content = file_get_contents($ajax_file_path);
        
        // Check which expected fields are present in the licence_payload array
        $present_fields = [];
        $missing_fields = [];
        $deprecated_present = [];
        
        foreach ($expected_fields as $field) {
            // Look for the field as a key in the licence_payload array
            if (preg_match("/'{$field}'\s*=>/", $ajax_content)) {
                $present_fields[] = $field;
            } else {
                $missing_fields[] = $field;
            }
        }
        
        foreach ($deprecated_fields as $field) {
            // Look for the field as a key in the licence_payload array
            if (preg_match("/'{$field}'\s*=>/", $ajax_content)) {
                $deprecated_present[] = $field;
            }
        }
        
        $test_results['analysis'] = [
            'total_expected' => count($expected_fields),
            'present_count' => count($present_fields),
            'missing_count' => count($missing_fields),
            'present_fields' => $present_fields,
            'missing_fields' => $missing_fields,
            'deprecated_present' => $deprecated_present
        ];
        
        // Determine test status
        if (empty($missing_fields) && empty($deprecated_present)) {
            $test_results['status'] = 'passed';
            $test_results['message'] = 'All required fields present, no deprecated fields found';
        } else {
            $test_results['status'] = 'failed';
            $messages = [];
            if (!empty($missing_fields)) {
                $messages[] = 'Missing fields: ' . implode(', ', $missing_fields);
            }
            if (!empty($deprecated_present)) {
                $messages[] = 'Deprecated fields present: ' . implode(', ', $deprecated_present);
            }
            $test_results['message'] = implode('; ', $messages);
        }
        
    } catch (Exception $e) {
        $test_results['status'] = 'error';
        $test_results['error'] = $e->getMessage();
    } finally {
        // Restore original $_POST
        $_POST = $original_post;
    }
    
    return $test_results;
}

/**
 * Run the test and output results
 */
function ufsc_run_licence_payload_test() {
    if (!current_user_can('manage_ufsc_licenses')) {
        return;
    }
    
    $results = ufsc_test_licence_payload_completeness();
    
    echo '<div class="notice notice-info"><h3>Licence Payload Test Results</h3>';
    echo '<p><strong>Status:</strong> ' . esc_html($results['status']) . '</p>';
    echo '<p><strong>Timestamp:</strong> ' . esc_html($results['timestamp']) . '</p>';
    
    if (isset($results['message'])) {
        echo '<p><strong>Message:</strong> ' . esc_html($results['message']) . '</p>';
    }
    
    if (isset($results['analysis'])) {
        $analysis = $results['analysis'];
        echo '<p><strong>Analysis:</strong></p>';
        echo '<ul>';
        echo '<li>Expected fields: ' . esc_html($analysis['total_expected']) . '</li>';
        echo '<li>Present fields: ' . esc_html($analysis['present_count']) . '</li>';
        echo '<li>Missing fields: ' . esc_html($analysis['missing_count']) . '</li>';
        echo '</ul>';
        
        if (!empty($analysis['missing_fields'])) {
            echo '<p><strong>Missing fields:</strong> ' . esc_html(implode(', ', $analysis['missing_fields'])) . '</p>';
        }
        
        if (!empty($analysis['deprecated_present'])) {
            echo '<p><strong>Deprecated fields found:</strong> ' . esc_html(implode(', ', $analysis['deprecated_present'])) . '</p>';
        }
    }
    
    echo '</div>';
}

// Allow test to be run via admin
if (function_exists('is_admin') && is_admin() && isset($_GET['ufsc_test_payload'])) {
    add_action('admin_notices', 'ufsc_run_licence_payload_test');
}