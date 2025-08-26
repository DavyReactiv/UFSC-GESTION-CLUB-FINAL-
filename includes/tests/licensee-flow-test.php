<?php
/**
 * Test file for new licensee addition flow
 * This file tests the integrated licensee addition with purchase management
 * 
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for new licensee addition flow
 */
class UFSC_Licensee_Flow_Test
{
    /**
     * Run all licensee flow tests
     */
    public static function run_tests()
    {
        echo "<h2>Test du nouveau flux d'ajout de licencié</h2>";
        
        $results = [];
        
        $results[] = self::test_shortcode_registration();
        $results[] = self::test_backward_compatibility();
        $results[] = self::test_function_existence();
        $results[] = self::test_quota_logic();
        
        // Display results
        echo "<h3>Résultats des tests :</h3>";
        foreach ($results as $result) {
            $status = $result['passed'] ? '✅' : '❌';
            echo "<p>{$status} {$result['name']}: {$result['message']}</p>";
        }
        
        return $results;
    }
    
    /**
     * Test if new shortcode is properly registered
     */
    private static function test_shortcode_registration()
    {
        global $shortcode_tags;
        
        $shortcode_exists = isset($shortcode_tags['ufsc_ajouter_licencie']);
        
        return [
            'name' => 'Enregistrement du shortcode [ufsc_ajouter_licencie]',
            'passed' => $shortcode_exists,
            'message' => $shortcode_exists ? 'Shortcode correctement enregistré' : 'Shortcode non trouvé'
        ];
    }
    
    /**
     * Test backward compatibility with old shortcode
     */
    private static function test_backward_compatibility()
    {
        global $shortcode_tags;
        
        $old_shortcode_exists = isset($shortcode_tags['ufsc_ajouter_licence']);
        
        return [
            'name' => 'Rétrocompatibilité [ufsc_ajouter_licence]',
            'passed' => $old_shortcode_exists,
            'message' => $old_shortcode_exists ? 'Ancien shortcode toujours disponible' : 'Ancien shortcode manquant'
        ];
    }
    
    /**
     * Test if required functions exist
     */
    private static function test_function_existence()
    {
        $functions_to_check = [
            'ufsc_ajouter_licencie_shortcode',
            'ufsc_handle_licence_submission',
            'ufsc_check_licence_quota',
            'ufsc_club_render_licences'
        ];
        
        $missing_functions = [];
        foreach ($functions_to_check as $function) {
            if (!function_exists($function)) {
                $missing_functions[] = $function;
            }
        }
        
        $passed = empty($missing_functions);
        $message = $passed ? 'Toutes les fonctions requises existent' : 'Fonctions manquantes: ' . implode(', ', $missing_functions);
        
        return [
            'name' => 'Existence des fonctions requises',
            'passed' => $passed,
            'message' => $message
        ];
    }
    
    /**
     * Test quota logic simulation
     */
    private static function test_quota_logic()
    {
        // Simulate quota check function behavior
        $test_club = (object) ['quota_licences' => 10];
        $test_licences = array_fill(0, 10, (object) ['id' => 1]); // 10 licenses
        
        if (function_exists('ufsc_check_licence_quota')) {
            $quota_info = ufsc_check_licence_quota($test_club, $test_licences);
            $passed = isset($quota_info['can_add']) && $quota_info['can_add'] === false;
            $message = $passed ? 'Logique de quota fonctionne correctement' : 'Problème avec la logique de quota';
        } else {
            $passed = false;
            $message = 'Fonction ufsc_check_licence_quota non trouvée';
        }
        
        return [
            'name' => 'Logique de vérification des quotas',
            'passed' => $passed,
            'message' => $message
        ];
    }
}

// Auto-run tests if called directly (for development)
if (defined('WP_CLI') || (defined('DOING_AJAX') && DOING_AJAX)) {
    UFSC_Licensee_Flow_Test::run_tests();
}