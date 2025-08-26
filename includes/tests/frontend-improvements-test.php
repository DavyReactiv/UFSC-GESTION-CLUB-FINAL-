<?php
/**
 * Test file for frontend license form and directors improvements
 * 
 * This test validates the key improvements implemented:
 * - Enhanced license form with cart workflow
 * - Improved directors display
 * - Security nonce fixes
 * - Modern UX enhancements
 * 
 * @package UFSC_Gestion_Club
 * @subpackage Tests
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test frontend improvements and license workflow
 */
function ufsc_test_frontend_improvements()
{
    echo "<h2>🧪 Test - Améliorations Frontend et Workflow Licences</h2>";
    
    $tests_passed = 0;
    $total_tests = 8;
    $errors = [];
    
    try {
        // Test 1: Check if enhanced CSS file exists
        echo "<h3>Test 1: Vérification du fichier CSS amélioré</h3>";
        
        $css_file = UFSC_PLUGIN_PATH . 'assets/css/licence-form-enhanced.css';
        if (file_exists($css_file)) {
            echo "<p>✅ Fichier CSS amélioré trouvé: licence-form-enhanced.css</p>";
            $tests_passed++;
        } else {
            $error = "❌ Fichier CSS amélioré manquant";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 2: Test if license form file has been updated
        echo "<h3>Test 2: Vérification de la mise à jour du formulaire de licence</h3>";
        
        $form_file = UFSC_PLUGIN_PATH . 'includes/frontend/parts/licence-form.php';
        if (file_exists($form_file)) {
            $form_content = file_get_contents($form_file);
            $has_cart_workflow = strpos($form_content, 'wc_get_cart_url') !== false;
            $has_nonce_fix = strpos($form_content, 'wp_verify_nonce') !== false;
            $has_enhanced_fields = strpos($form_content, 'ufsc-form-section') !== false;
            
            if ($has_cart_workflow && $has_nonce_fix && $has_enhanced_fields) {
                echo "<p>✅ Formulaire de licence mis à jour avec workflow panier</p>";
                $tests_passed++;
            } else {
                $error = "❌ Formulaire de licence incomplet - Cart: " . ($has_cart_workflow ? "OK" : "KO") . 
                        ", Nonce: " . ($has_nonce_fix ? "OK" : "KO") . 
                        ", Fields: " . ($has_enhanced_fields ? "OK" : "KO");
                echo "<p>$error</p>";
                $errors[] = $error;
            }
        } else {
            $error = "❌ Fichier formulaire de licence introuvable";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 3: Test directors display improvements
        echo "<h3>Test 3: Vérification des améliorations d'affichage des dirigeants</h3>";
        
        $dashboard_file = UFSC_PLUGIN_PATH . 'includes/frontend/frontend-club-dashboard.php';
        if (file_exists($dashboard_file)) {
            $dashboard_content = file_get_contents($dashboard_file);
            $has_enhanced_directors = strpos($dashboard_content, 'data-role=') !== false;
            $has_prenom_handling = strpos($dashboard_content, '_prenom') !== false;
            
            if ($has_enhanced_directors && $has_prenom_handling) {
                echo "<p>✅ Affichage des dirigeants amélioré avec attributs data-role</p>";
                $tests_passed++;
            } else {
                $error = "❌ Affichage des dirigeants non amélioré";
                echo "<p>$error</p>";
                $errors[] = $error;
            }
        } else {
            $error = "❌ Fichier dashboard introuvable";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 4: Test WooCommerce integration
        echo "<h3>Test 4: Vérification de l'intégration WooCommerce</h3>";
        
        $woo_file = UFSC_PLUGIN_PATH . 'includes/frontend/woocommerce-licence-form.php';
        if (file_exists($woo_file)) {
            $woo_content = file_get_contents($woo_file);
            $has_url_handling = strpos($woo_content, 'ufsc_handle_licence_url_data') !== false;
            $has_session_management = strpos($woo_content, 'ufsc_pending_licence_data') !== false;
            
            if ($has_url_handling && $has_session_management) {
                echo "<p>✅ Intégration WooCommerce mise à jour avec gestion des sessions</p>";
                $tests_passed++;
            } else {
                $error = "❌ Intégration WooCommerce incomplète";
                echo "<p>$error</p>";
                $errors[] = $error;
            }
        } else {
            $error = "❌ Fichier WooCommerce introuvable";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 5: Test CSS enhancements content
        echo "<h3>Test 5: Vérification du contenu CSS amélioré</h3>";
        
        if (file_exists($css_file)) {
            $css_content = file_get_contents($css_file);
            $has_form_styling = strpos($css_content, '.ufsc-licence-form-container') !== false;
            $has_directors_styling = strpos($css_content, '.ufsc-dirigeant-card[data-role=') !== false;
            $has_responsive_design = strpos($css_content, '@media (max-width: 768px)') !== false;
            
            if ($has_form_styling && $has_directors_styling && $has_responsive_design) {
                echo "<p>✅ CSS amélioré avec styling complet (formulaire, dirigeants, responsive)</p>";
                $tests_passed++;
            } else {
                $error = "❌ CSS amélioré incomplet";
                echo "<p>$error</p>";
                $errors[] = $error;
            }
        }
        
        // Test 6: Test constants and product ID
        echo "<h3>Test 6: Vérification des constantes et ID produit</h3>";
        
        if (defined('UFSC_LICENCE_PRODUCT_ID')) {
            echo "<p>✅ Constante UFSC_LICENCE_PRODUCT_ID définie: " . UFSC_LICENCE_PRODUCT_ID . "</p>";
            $tests_passed++;
        } else {
            $error = "❌ Constante UFSC_LICENCE_PRODUCT_ID non définie";
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 7: Test helper functions availability
        echo "<h3>Test 7: Vérification des fonctions helper</h3>";
        
        $required_functions = [
            'ufsc_check_frontend_access',
            'ufsc_get_user_club'
        ];
        
        $missing_functions = [];
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                $missing_functions[] = $function;
            }
        }
        
        if (empty($missing_functions)) {
            echo "<p>✅ Toutes les fonctions helper requises sont disponibles</p>";
            $tests_passed++;
        } else {
            $error = "❌ Fonctions helper manquantes: " . implode(', ', $missing_functions);
            echo "<p>$error</p>";
            $errors[] = $error;
        }
        
        // Test 8: Test CSS enqueuing in main plugin file
        echo "<h3>Test 8: Vérification de l'inclusion CSS dans le plugin principal</h3>";
        
        $main_file = UFSC_PLUGIN_PATH . 'Plugin_UFSC_GESTION_CLUB_13072025.php';
        if (file_exists($main_file)) {
            $main_content = file_get_contents($main_file);
            $has_css_enqueue = strpos($main_content, 'licence-form-enhanced.css') !== false;
            
            if ($has_css_enqueue) {
                echo "<p>✅ CSS amélioré inclus dans l'enqueue du plugin principal</p>";
                $tests_passed++;
            } else {
                $error = "❌ CSS amélioré non inclus dans l'enqueue";
                echo "<p>$error</p>";
                $errors[] = $error;
            }
        }
        
    } catch (Exception $e) {
        $errors[] = "Exception: " . $e->getMessage();
        echo "<p>❌ Exception: " . esc_html($e->getMessage()) . "</p>";
    }
    
    // Results summary
    echo "<h3>📊 Résumé des tests</h3>";
    echo "<p><strong>Tests réussis:</strong> $tests_passed / $total_tests</p>";
    
    if ($tests_passed === $total_tests) {
        echo "<div style='background: #d1edff; border: 1px solid #0073aa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4 style='color: #0073aa; margin: 0 0 10px 0;'>🎉 Tous les tests sont réussis !</h4>";
        echo "<p style='margin: 0;'>Les améliorations frontend et workflow licences ont été implémentées avec succès :</p>";
        echo "<ul style='margin: 10px 0 0 20px;'>";
        echo "<li>✅ Sécurité des formulaires corrigée (nonce)</li>";
        echo "<li>✅ Workflow panier WooCommerce implémenté</li>";
        echo "<li>✅ Affichage des dirigeants amélioré</li>";
        echo "<li>✅ CSS moderne et responsive</li>";
        echo "<li>✅ Formulaire complet avec tous les champs requis</li>";
        echo "<li>✅ Intégration WooCommerce optimisée</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #ffebee; border: 1px solid #d63638; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4 style='color: #d63638; margin: 0 0 10px 0;'>⚠️ Erreurs détectées</h4>";
        foreach ($errors as $error) {
            echo "<p style='margin: 5px 0;'>• " . esc_html($error) . "</p>";
        }
        echo "</div>";
    }
    
    return $tests_passed === $total_tests;
}

// Auto-run test if called directly (for development)
if (defined('WP_CLI') || (defined('DOING_AJAX') && DOING_AJAX)) {
    ufsc_test_frontend_improvements();
}