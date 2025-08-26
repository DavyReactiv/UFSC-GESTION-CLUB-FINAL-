<?php
/**
 * Test file for WordPress user-club association functionality
 *
 * This test validates the user association features added to the UFSC club management system.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test WordPress user-club association functionality
 */
function ufsc_test_user_club_association()
{
    echo "<h2>🧪 Test - Association Utilisateur WordPress → Club</h2>";
    
    $tests_passed = 0;
    $total_tests = 5;
    $errors = [];
    
    try {
        // Test 1: Check if helper functions exist
        echo "<h3>Test 1: Vérification des fonctions helper</h3>";
        
        $required_functions = [
            'ufsc_get_wordpress_users_for_clubs',
            'ufsc_is_user_already_associated',
            'ufsc_get_user_display_info',
            'ufsc_get_user_club'
        ];
        
        $missing_functions = [];
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                $missing_functions[] = $function;
            }
        }
        
        if (empty($missing_functions)) {
            echo "<p>✅ Toutes les fonctions helper requises sont disponibles.</p>";
            $tests_passed++;
        } else {
            $error = "❌ Fonctions manquantes: " . implode(', ', $missing_functions);
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 2: Check database schema
        echo "<h3>Test 2: Vérification du schéma de base de données</h3>";
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ufsc_clubs';
        
        // Check if responsable_id column exists
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'responsable_id'");
        
        if ($column_exists) {
            echo "<p>✅ La colonne 'responsable_id' existe dans la table des clubs.</p>";
            $tests_passed++;
        } else {
            $error = "❌ La colonne 'responsable_id' n'existe pas dans la table des clubs.";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 3: Test user validation functions
        echo "<h3>Test 3: Test des fonctions de validation</h3>";
        
        if (function_exists('ufsc_is_user_already_associated')) {
            // Test with non-existent user
            $result = ufsc_is_user_already_associated(0);
            if ($result === false) {
                echo "<p>✅ La validation d'utilisateur inexistant fonctionne correctement.</p>";
                $tests_passed++;
            } else {
                $error = "❌ La validation d'utilisateur inexistant ne fonctionne pas correctement.";
                echo "<p>$error</p>";
                $errors[] = $error;
            }
        } else {
            $error = "❌ La fonction ufsc_is_user_already_associated n'existe pas.";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 4: Test WordPress user retrieval
        echo "<h3>Test 4: Test de récupération des utilisateurs WordPress</h3>";
        
        if (function_exists('ufsc_get_wordpress_users_for_clubs')) {
            $users = ufsc_get_wordpress_users_for_clubs();
            if (is_array($users)) {
                echo "<p>✅ La récupération des utilisateurs WordPress fonctionne (trouvé " . count($users) . " utilisateur(s)).</p>";
                $tests_passed++;
            } else {
                $error = "❌ La récupération des utilisateurs WordPress ne retourne pas un tableau.";
                echo "<p>$error</p>";
                $errors[] = $error;
            }
        } else {
            $error = "❌ La fonction ufsc_get_wordpress_users_for_clubs n'existe pas.";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 5: Test user display info function
        echo "<h3>Test 5: Test d'affichage des informations utilisateur</h3>";
        
        if (function_exists('ufsc_get_user_display_info')) {
            // Test with user ID 1 (usually the admin user)
            $user_info = ufsc_get_user_display_info(1);
            if ($user_info && isset($user_info->ID)) {
                echo "<p>✅ La récupération des informations d'affichage utilisateur fonctionne.</p>";
                $tests_passed++;
            } else {
                // Try with current user if ID 1 doesn't exist
                $current_user_id = get_current_user_id();
                if ($current_user_id > 0) {
                    $user_info = ufsc_get_user_display_info($current_user_id);
                    if ($user_info && isset($user_info->ID)) {
                        echo "<p>✅ La récupération des informations d'affichage utilisateur fonctionne.</p>";
                        $tests_passed++;
                    } else {
                        $error = "❌ La récupération des informations d'affichage utilisateur ne fonctionne pas.";
                        echo "<p>$error</p>";
                        $errors[] = $error;
                    }
                } else {
                    $error = "❌ Aucun utilisateur valide disponible pour le test.";
                    echo "<p>$error</p>";
                    $errors[] = $error;
                }
            }
        } else {
            $error = "❌ La fonction ufsc_get_user_display_info n'existe pas.";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
    } catch (Exception $e) {
        $error = "❌ Erreur lors des tests: " . $e->getMessage();
        echo "<p>$error</p>";
        $errors[] = $error;
    }
    
    // Summary
    echo "<h3>📊 Résumé des tests</h3>";
    echo "<p><strong>Tests réussis:</strong> $tests_passed/$total_tests</p>";
    
    if ($tests_passed === $total_tests) {
        echo "<p style='color: green; font-weight: bold;'>🎉 Tous les tests sont passés avec succès!</p>";
        echo "<p><em>L'association utilisateur WordPress → club est prête à être utilisée.</em></p>";
        return true;
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Certains tests ont échoué.</p>";
        if (!empty($errors)) {
            echo "<h4>Erreurs détectées:</h4>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>" . esc_html($error) . "</li>";
            }
            echo "</ul>";
        }
        return false;
    }
}

// Run the test if we're in admin context and have proper permissions
if (is_admin() && current_user_can('manage_ufsc')) {
    // Only run if specifically requested via URL parameter
    if (isset($_GET['run_user_club_test']) && $_GET['run_user_club_test'] === '1') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info is-dismissible">';
            ufsc_test_user_club_association();
            echo '</div>';
        });
    }
}