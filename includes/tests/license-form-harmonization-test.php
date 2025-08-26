<?php
/**
 * Test for License Form Harmonization
 * Verifies that frontend form includes all fields from WooCommerce form
 */

if (!defined('ABSPATH')) {
    exit;
}

function ufsc_test_license_form_harmonization() {
    $results = [
        'tests_passed' => 0,
        'tests_total' => 0,
        'errors' => []
    ];

    echo '<div class="ufsc-test-container">';
    echo '<h2>Test: Harmonisation formulaire de licence front-end</h2>';
    
    // Test 1: Verify frontend form file exists and is updated
    echo '<h3>Test 1: Vérification fichier formulaire front-end</h3>';
    $results['tests_total']++;
    
    $form_file = UFSC_PLUGIN_PATH . 'includes/frontend/parts/licence-form.php';
    if (file_exists($form_file)) {
        $form_content = file_get_contents($form_file);
        
        // Check if error message is fixed
        $has_correct_error = strpos($form_content, 'Champs obligatoires manquants') !== false;
        $has_old_error = strpos($form_content, 'Erreur de sécurité. Veuillez recharger la page.') !== false;
        
        if ($has_correct_error && !$has_old_error) {
            echo "<p>✅ Message d'erreur corrigé</p>";
            $results['tests_passed']++;
        } else {
            $error = "❌ Message d'erreur non corrigé - Correct: " . ($has_correct_error ? "OK" : "KO") . 
                    ", Old: " . ($has_old_error ? "Present" : "Absent");
            echo "<p>$error</p>";
            $results['errors'][] = $error;
        }
    } else {
        $error = "❌ Fichier formulaire front-end introuvable";
        echo "<p>$error</p>";
        $results['errors'][] = $error;
    }
    
    // Test 2: Check for WooCommerce form fields in frontend form
    echo '<h3>Test 2: Vérification des champs harmonisés</h3>';
    $results['tests_total']++;
    
    if (file_exists($form_file)) {
        $form_content = file_get_contents($form_file);
        
        // Required fields that should be present
        $required_fields = [
            'suite_adresse' => 'Complément d\'adresse',
            'tel_fixe' => 'Téléphone fixe',
            'tel_mobile' => 'Téléphone mobile', 
            'profession' => 'Profession',
            'identifiant_laposte' => 'Identifiant La Poste',
            'region' => 'Région',
            'numero_licence_delegataire' => 'N° licence délégataire',
            'note' => 'Note'
        ];
        
        $missing_fields = [];
        foreach ($required_fields as $field => $label) {
            if (strpos($form_content, 'name="' . $field . '"') === false) {
                $missing_fields[] = $label;
            }
        }
        
        if (empty($missing_fields)) {
            echo "<p>✅ Tous les champs requis sont présents</p>";
            $results['tests_passed']++;
        } else {
            $error = "❌ Champs manquants: " . implode(', ', $missing_fields);
            echo "<p>$error</p>";
            $results['errors'][] = $error;
        }
    }
    
    // Test 3: Check for checkbox fields
    echo '<h3>Test 3: Vérification des options à cocher</h3>';
    $results['tests_total']++;
    
    if (file_exists($form_file)) {
        $form_content = file_get_contents($form_file);
        
        // Checkbox fields that should be present
        $checkbox_fields = [
            'reduction_benevole',
            'reduction_postier',
            'fonction_publique',
            'competition',
            'licence_delegataire',
            'diffusion_image',
            'infos_fsasptt',
            'infos_asptt',
            'infos_cr',
            'infos_partenaires',
            'honorabilite',
            'assurance_dommage_corporel',
            'assurance_assistance'
        ];
        
        $missing_checkboxes = [];
        foreach ($checkbox_fields as $field) {
            if (strpos($form_content, 'name="' . $field . '"') === false) {
                $missing_checkboxes[] = $field;
            }
        }
        
        if (empty($missing_checkboxes)) {
            echo "<p>✅ Toutes les cases à cocher sont présentes (" . count($checkbox_fields) . " options)</p>";
            $results['tests_passed']++;
        } else {
            $error = "❌ Cases à cocher manquantes: " . implode(', ', $missing_checkboxes);
            echo "<p>$error</p>";
            $results['errors'][] = $error;
        }
    }
    
    // Test 4: Check data preparation includes all fields
    echo '<h3>Test 4: Vérification préparation des données</h3>';
    $results['tests_total']++;
    
    if (file_exists($form_file)) {
        $form_content = file_get_contents($form_file);
        
        // Check if data preparation includes new fields
        $data_fields_check = [
            'suite_adresse',
            'tel_fixe', 
            'tel_mobile',
            'profession',
            'identifiant_laposte',
            'region'
        ];
        
        $missing_data_fields = [];
        foreach ($data_fields_check as $field) {
            if (strpos($form_content, "'" . $field . "'") === false) {
                $missing_data_fields[] = $field;
            }
        }
        
        if (empty($missing_data_fields)) {
            echo "<p>✅ Préparation des données inclut tous les nouveaux champs</p>";
            $results['tests_passed']++;
        } else {
            $error = "❌ Champs manquants dans la préparation des données: " . implode(', ', $missing_data_fields);
            echo "<p>$error</p>";
            $results['errors'][] = $error;
        }
    }
    
    // Test 5: Check regions data file accessibility
    echo '<h3>Test 5: Vérification données régions</h3>';
    $results['tests_total']++;
    
    $regions_file = UFSC_PLUGIN_PATH . 'data/regions.php';
    if (file_exists($regions_file)) {
        $regions = require $regions_file;
        if (is_array($regions) && !empty($regions)) {
            echo "<p>✅ Fichier régions accessible avec " . count($regions) . " régions</p>";
            $results['tests_passed']++;
        } else {
            $error = "❌ Fichier régions vide ou format incorrect";
            echo "<p>$error</p>";
            $results['errors'][] = $error;
        }
    } else {
        $error = "❌ Fichier régions introuvable";
        echo "<p>$error</p>";
        $results['errors'][] = $error;
    }
    
    // Summary
    echo '<h3>Résumé des tests</h3>';
    $success_rate = $results['tests_total'] > 0 ? round(($results['tests_passed'] / $results['tests_total']) * 100) : 0;
    
    if ($results['tests_passed'] === $results['tests_total']) {
        echo '<p class="ufsc-test-success">✅ Tous les tests sont passés (' . $results['tests_passed'] . '/' . $results['tests_total'] . ')</p>';
    } else {
        echo '<p class="ufsc-test-partial">⚠️ Tests partiellement réussis: ' . $results['tests_passed'] . '/' . $results['tests_total'] . ' (' . $success_rate . '%)</p>';
        
        if (!empty($results['errors'])) {
            echo '<h4>Erreurs détectées:</h4>';
            echo '<ul>';
            foreach ($results['errors'] as $error) {
                echo '<li>' . $error . '</li>';
            }
            echo '</ul>';
        }
    }
    
    echo '</div>';
    
    return $results;
}

// Add test to admin menu if running in admin context
if (is_admin() && current_user_can('manage_ufsc')) {
    add_action('admin_menu', function() {
        add_submenu_page(
            'ufsc-gestion-club',
            'Test Harmonisation Formulaire',
            'Test Form Harmonization',
            'manage_options',
            'ufsc-test-form-harmonization',
            function() {
                echo '<div class="wrap">';
                echo '<h1>Test: Harmonisation du formulaire de licence</h1>';
                ufsc_test_license_form_harmonization();
                echo '</div>';
            }
        );
    });
}