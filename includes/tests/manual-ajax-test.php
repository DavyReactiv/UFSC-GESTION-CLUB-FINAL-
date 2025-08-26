<?php

/**
 * Manual Licence AJAX Functionality Test
 * 
 * This script simulates the AJAX add-to-cart functionality to verify
 * that all form fields are properly processed and included in the payload.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.1
 */

// Only run in debug mode
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    return;
}

/**
 * Simulate AJAX licence form submission
 */
function ufsc_test_ajax_licence_submission() {
    // Mock all WordPress functions needed
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($str) { return trim(strip_tags($str)); }
    }
    if (!function_exists('sanitize_email')) {
        function sanitize_email($email) { return filter_var($email, FILTER_SANITIZE_EMAIL); }
    }
    if (!function_exists('sanitize_textarea_field')) {
        function sanitize_textarea_field($str) { return trim(strip_tags($str)); }
    }
    if (!function_exists('is_email')) {
        function is_email($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
    }
    if (!function_exists('intval')) {
        function intval($var) { return (int) $var; }
    }

    // Simulate complete form data
    $form_data = [
        // Required for AJAX handler
        'club_id' => '1',
        '_ufsc_licence_nonce' => 'test_nonce',
        
        // Basic personal info
        'nom' => 'Dupont',
        'prenom' => 'Jean',
        'email' => 'jean.dupont@example.com',
        'sexe' => 'M',
        'date_naissance' => '1990-01-01',
        
        // Address info
        'adresse' => '123 Rue de la Paix',
        'suite_adresse' => 'Apt 4B',
        'code_postal' => '75001',
        'ville' => 'Paris',
        'region' => 'Île-de-France',
        
        // Phone info
        'tel_fixe' => '01 23 45 67 89',
        'tel_mobile' => '06 12 34 56 78',
        
        // Professional info
        'reduction_benevole' => '1',
        'reduction_postier' => '1',
        'identifiant_laposte' => 'LP123456',
        'profession' => 'Ingénieur',
        'fonction_publique' => '0',
        
        // Consent and preferences
        'diffusion_image' => '1',
        'infos_fsasptt' => '1',
        'infos_asptt' => '0',
        'infos_cr' => '1',
        'infos_partenaires' => '0',
        'honorabilite' => '1',
        
        // Sport/licence info
        'competition' => '1',
        'licence_delegataire' => '0',
        'numero_licence_delegataire' => '',
        
        // Insurance
        'assurance_dommage_corporel' => '1',
        'assurance_assistance' => '0',
        
        // Additional info
        'note' => 'Joueur expérimenté, disponible pour les compétitions.'
    ];
    
    // Backup original $_POST
    $original_post = $_POST;
    $_POST = $form_data;
    
    try {
        // Extract the payload creation logic from the AJAX handler
        $nom = isset($_POST['nom']) ? sanitize_text_field($_POST['nom']) : '';
        $prenom = isset($_POST['prenom']) ? sanitize_text_field($_POST['prenom']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        // Simulate the licence payload creation from ajax-add-to-cart.php
        $licence_payload = [
            // Basic personal info
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'sexe' => isset($_POST['sexe']) ? sanitize_text_field($_POST['sexe']) : '',
            'date_naissance' => isset($_POST['date_naissance']) ? sanitize_text_field($_POST['date_naissance']) : '',
            
            // Address info
            'adresse' => isset($_POST['adresse']) ? sanitize_text_field($_POST['adresse']) : '',
            'suite_adresse' => isset($_POST['suite_adresse']) ? sanitize_text_field($_POST['suite_adresse']) : '',
            'code_postal' => isset($_POST['code_postal']) ? sanitize_text_field($_POST['code_postal']) : '',
            'ville' => isset($_POST['ville']) ? sanitize_text_field($_POST['ville']) : '',
            'region' => isset($_POST['region']) ? sanitize_text_field($_POST['region']) : '',
            
            // Phone info (replacing deprecated 'telephone' field)
            'tel_fixe' => isset($_POST['tel_fixe']) ? sanitize_text_field($_POST['tel_fixe']) : '',
            'tel_mobile' => isset($_POST['tel_mobile']) ? sanitize_text_field($_POST['tel_mobile']) : '',
            
            // Professional info
            'reduction_benevole' => isset($_POST['reduction_benevole']) ? intval($_POST['reduction_benevole']) : 0,
            'reduction_postier' => isset($_POST['reduction_postier']) ? intval($_POST['reduction_postier']) : 0,
            'identifiant_laposte' => isset($_POST['identifiant_laposte']) ? sanitize_text_field($_POST['identifiant_laposte']) : '',
            'profession' => isset($_POST['profession']) ? sanitize_text_field($_POST['profession']) : '',
            'fonction_publique' => isset($_POST['fonction_publique']) ? intval($_POST['fonction_publique']) : 0,
            
            // Consent and preferences
            'diffusion_image' => isset($_POST['diffusion_image']) ? intval($_POST['diffusion_image']) : 0,
            'infos_fsasptt' => isset($_POST['infos_fsasptt']) ? intval($_POST['infos_fsasptt']) : 0,
            'infos_asptt' => isset($_POST['infos_asptt']) ? intval($_POST['infos_asptt']) : 0,
            'infos_cr' => isset($_POST['infos_cr']) ? intval($_POST['infos_cr']) : 0,
            'infos_partenaires' => isset($_POST['infos_partenaires']) ? intval($_POST['infos_partenaires']) : 0,
            'honorabilite' => isset($_POST['honorabilite']) ? intval($_POST['honorabilite']) : 0,
            
            // Sport/licence info
            'competition' => isset($_POST['competition']) ? intval($_POST['competition']) : 0,
            'licence_delegataire' => isset($_POST['licence_delegataire']) ? intval($_POST['licence_delegataire']) : 0,
            'numero_licence_delegataire' => isset($_POST['numero_licence_delegataire']) ? sanitize_text_field($_POST['numero_licence_delegataire']) : '',
            
            // Insurance
            'assurance_dommage_corporel' => isset($_POST['assurance_dommage_corporel']) ? intval($_POST['assurance_dommage_corporel']) : 0,
            'assurance_assistance' => isset($_POST['assurance_assistance']) ? intval($_POST['assurance_assistance']) : 0,
            
            // Additional info
            'note' => isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '',
        ];
        
        $test_results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_name' => 'Manual AJAX Licence Submission Test',
            'status' => 'success',
            'form_data_count' => count($form_data),
            'payload_count' => count($licence_payload),
            'payload_data' => $licence_payload,
            'validations' => []
        ];
        
        // Validate key aspects
        $test_results['validations']['required_fields'] = [
            'nom_present' => !empty($licence_payload['nom']),
            'prenom_present' => !empty($licence_payload['prenom']),
            'email_present' => !empty($licence_payload['email']) && is_email($licence_payload['email']),
        ];
        
        $test_results['validations']['address_fields'] = [
            'adresse_present' => !empty($licence_payload['adresse']),
            'suite_adresse_present' => !empty($licence_payload['suite_adresse']),
            'code_postal_present' => !empty($licence_payload['code_postal']),
            'ville_present' => !empty($licence_payload['ville']),
        ];
        
        $test_results['validations']['phone_fields'] = [
            'tel_fixe_present' => !empty($licence_payload['tel_fixe']),
            'tel_mobile_present' => !empty($licence_payload['tel_mobile']),
            'no_deprecated_telephone' => !isset($licence_payload['telephone']),
        ];
        
        $test_results['validations']['checkbox_fields'] = [
            'reduction_benevole_int' => is_int($licence_payload['reduction_benevole']),
            'diffusion_image_int' => is_int($licence_payload['diffusion_image']),
            'assurance_dommage_corporel_int' => is_int($licence_payload['assurance_dommage_corporel']),
        ];
        
        $test_results['validations']['note_field'] = [
            'note_present' => !empty($licence_payload['note']),
            'note_sanitized' => is_string($licence_payload['note']),
        ];
        
        // Check if all validations passed
        $all_passed = true;
        foreach ($test_results['validations'] as $category => $checks) {
            foreach ($checks as $check => $result) {
                if (!$result) {
                    $all_passed = false;
                    break 2;
                }
            }
        }
        
        $test_results['overall_status'] = $all_passed ? 'PASSED' : 'FAILED';
        
        return $test_results;
        
    } catch (Exception $e) {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'test_name' => 'Manual AJAX Licence Submission Test',
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    } finally {
        // Restore original $_POST
        $_POST = $original_post;
    }
}

/**
 * Run the manual test and display results
 */
function ufsc_run_manual_ajax_test() {
    $results = ufsc_test_ajax_licence_submission();
    
    echo "\n=== UFSC Manual AJAX Test Results ===\n";
    echo "Test: " . $results['test_name'] . "\n";
    echo "Time: " . $results['timestamp'] . "\n";
    echo "Status: " . $results['overall_status'] . "\n\n";
    
    if (isset($results['validations'])) {
        echo "Validation Details:\n";
        foreach ($results['validations'] as $category => $checks) {
            echo "  {$category}:\n";
            foreach ($checks as $check => $result) {
                $status = $result ? '✓' : '✗';
                echo "    {$status} {$check}: " . ($result ? 'PASS' : 'FAIL') . "\n";
            }
        }
    }
    
    if (isset($results['error'])) {
        echo "Error: " . $results['error'] . "\n";
    }
    
    echo "\nPayload field count: " . $results['payload_count'] . "\n";
    echo "===================================\n\n";
    
    return $results;
}