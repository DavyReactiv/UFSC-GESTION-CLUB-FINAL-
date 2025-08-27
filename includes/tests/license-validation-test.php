<?php
/**
 * License Validation Test
 * 
 * Tests the new license validation functionality including:
 * - update_licence_status method
 * - admin-post handlers
 * - bulk validation
 * - frontend validation button
 *
 * @package UFSC_Gestion_Club
 * @subpackage Tests
 * @since 1.3.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * License Validation Test Class
 */
class UFSC_License_Validation_Test
{
    private $results = [];
    private $licence_manager;
    
    public function __construct()
    {
        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        $this->licence_manager = UFSC_Licence_Manager::get_instance();
    }
    
    /**
     * Run all tests
     */
    public function run_tests()
    {
        echo "<h2>🧪 Tests de validation des licences</h2>";
        
        $this->test_update_licence_status_method();
        $this->test_admin_post_handlers_exist();
        $this->test_css_file_exists();
        $this->test_compatibility_shim();
        $this->test_frontend_button_logic();
        
        $this->display_results();
    }
    
    /**
     * Test update_licence_status method
     */
    private function test_update_licence_status_method()
    {
        $test_name = "Méthode update_licence_status existe";
        
        if (method_exists($this->licence_manager, 'update_licence_status')) {
            $this->results[$test_name] = ['status' => 'pass', 'message' => 'Méthode trouvée dans UFSC_Licence_Manager'];
        } else {
            $this->results[$test_name] = ['status' => 'fail', 'message' => 'Méthode manquante dans UFSC_Licence_Manager'];
            return;
        }
        
        // Test with valid status
        $reflection = new ReflectionMethod($this->licence_manager, 'update_licence_status');
        if ($reflection->isPublic()) {
            $this->results[$test_name . ' - Visibilité'] = ['status' => 'pass', 'message' => 'Méthode est publique'];
        } else {
            $this->results[$test_name . ' - Visibilité'] = ['status' => 'fail', 'message' => 'Méthode devrait être publique'];
        }
    }
    
    /**
     * Test admin-post handlers registration
     */
    private function test_admin_post_handlers_exist()
    {
        $test_name = "Handlers admin-post enregistrés";
        
        $file_path = UFSC_PLUGIN_PATH . 'includes/admin/licence-validation.php';
        if (file_exists($file_path)) {
            $this->results[$test_name] = ['status' => 'pass', 'message' => 'Fichier licence-validation.php existe'];
            
            // Check if functions are defined
            $content = file_get_contents($file_path);
            if (strpos($content, 'ufsc_admin_post_validate_licence') !== false) {
                $this->results[$test_name . ' - Handler unitaire'] = ['status' => 'pass', 'message' => 'Handler validation unitaire défini'];
            } else {
                $this->results[$test_name . ' - Handler unitaire'] = ['status' => 'fail', 'message' => 'Handler validation unitaire manquant'];
            }
            
            if (strpos($content, 'ufsc_handle_bulk_validate_licences') !== false) {
                $this->results[$test_name . ' - Handler bulk'] = ['status' => 'pass', 'message' => 'Handler validation bulk défini'];
            } else {
                $this->results[$test_name . ' - Handler bulk'] = ['status' => 'fail', 'message' => 'Handler validation bulk manquant'];
            }
        } else {
            $this->results[$test_name] = ['status' => 'fail', 'message' => 'Fichier licence-validation.php manquant'];
        }
    }
    
    /**
     * Test CSS file exists and is enqueued
     */
    private function test_css_file_exists()
    {
        $test_name = "Fichier CSS correctifs UI";
        
        $css_path = UFSC_PLUGIN_PATH . 'assets/css/ufsc-dashboard-fixes.css';
        if (file_exists($css_path)) {
            $this->results[$test_name] = ['status' => 'pass', 'message' => 'Fichier ufsc-dashboard-fixes.css existe'];
            
            // Check if it contains key styles
            $content = file_get_contents($css_path);
            if (strpos($content, 'max-width: 1200px') !== false) {
                $this->results[$test_name . ' - Container max-width'] = ['status' => 'pass', 'message' => 'Constraint largeur conteneur trouvée'];
            } else {
                $this->results[$test_name . ' - Container max-width'] = ['status' => 'fail', 'message' => 'Constraint largeur conteneur manquante'];
            }
            
            if (strpos($content, 'table-layout: fixed') !== false) {
                $this->results[$test_name . ' - Table layout fixed'] = ['status' => 'pass', 'message' => 'Table layout fixed trouvé'];
            } else {
                $this->results[$test_name . ' - Table layout fixed'] = ['status' => 'fail', 'message' => 'Table layout fixed manquant'];
            }
            
            if (strpos($content, '128px') !== false) {
                $this->results[$test_name . ' - Avatar 128px'] = ['status' => 'pass', 'message' => 'Taille avatar 128px trouvée'];
            } else {
                $this->results[$test_name . ' - Avatar 128px'] = ['status' => 'fail', 'message' => 'Taille avatar 128px manquante'];
            }
        } else {
            $this->results[$test_name] = ['status' => 'fail', 'message' => 'Fichier CSS ufsc-dashboard-fixes.css manquant'];
        }
    }
    
    /**
     * Test compatibility shim
     */
    private function test_compatibility_shim()
    {
        $test_name = "Shim compatibilité fonctions club";
        
        // Check if ufsc_render_club_info_section exists
        if (function_exists('ufsc_render_club_info_section')) {
            $this->results[$test_name] = ['status' => 'pass', 'message' => 'Fonction ufsc_render_club_info_section disponible'];
        } else {
            $this->results[$test_name] = ['status' => 'fail', 'message' => 'Fonction ufsc_render_club_info_section manquante'];
        }
        
        // Check if ufsc_club_render_profile exists
        if (function_exists('ufsc_club_render_profile')) {
            $this->results[$test_name . ' - Profile function'] = ['status' => 'pass', 'message' => 'Fonction ufsc_club_render_profile disponible'];
        } else {
            $this->results[$test_name . ' - Profile function'] = ['status' => 'warning', 'message' => 'Fonction ufsc_club_render_profile non trouvée (normal si pas encore définie)'];
        }
    }
    
    /**
     * Test frontend validation button integration
     */
    private function test_frontend_button_logic()
    {
        $test_name = "Bouton validation frontend";
        
        $licences_file = UFSC_PLUGIN_PATH . 'includes/frontend/club/licences.php';
        if (file_exists($licences_file)) {
            $content = file_get_contents($licences_file);
            
            if (strpos($content, 'current_user_can(\'manage_options\')') !== false) {
                $this->results[$test_name . ' - Permission check'] = ['status' => 'pass', 'message' => 'Vérification permissions admin trouvée'];
            } else {
                $this->results[$test_name . ' - Permission check'] = ['status' => 'fail', 'message' => 'Vérification permissions admin manquante'];
            }
            
            if (strpos($content, 'ufsc_validate_licence') !== false) {
                $this->results[$test_name . ' - Action handler'] = ['status' => 'pass', 'message' => 'Référence action validation trouvée'];
            } else {
                $this->results[$test_name . ' - Action handler'] = ['status' => 'fail', 'message' => 'Référence action validation manquante'];
            }
            
            if (strpos($content, 'pending') !== false || strpos($content, 'en_attente') !== false) {
                $this->results[$test_name . ' - Status check'] = ['status' => 'pass', 'message' => 'Vérification statut pending trouvée'];
            } else {
                $this->results[$test_name . ' - Status check'] = ['status' => 'fail', 'message' => 'Vérification statut pending manquante'];
            }
        } else {
            $this->results[$test_name] = ['status' => 'fail', 'message' => 'Fichier licences.php introuvable'];
        }
    }
    
    /**
     * Display test results
     */
    private function display_results()
    {
        echo "<div style='background: #f9f9f9; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>📊 Résultats des tests</h3>";
        
        $total = count($this->results);
        $passed = count(array_filter($this->results, function($r) { return $r['status'] === 'pass'; }));
        $failed = count(array_filter($this->results, function($r) { return $r['status'] === 'fail'; }));
        $warnings = count(array_filter($this->results, function($r) { return $r['status'] === 'warning'; }));
        
        echo "<p><strong>Total: {$total} | ✅ Réussis: {$passed} | ❌ Échoués: {$failed} | ⚠️ Avertissements: {$warnings}</strong></p>";
        
        echo "<ul style='list-style: none; padding: 0;'>";
        foreach ($this->results as $test => $result) {
            $icon = $result['status'] === 'pass' ? '✅' : ($result['status'] === 'fail' ? '❌' : '⚠️');
            $color = $result['status'] === 'pass' ? 'green' : ($result['status'] === 'fail' ? 'red' : 'orange');
            echo "<li style='margin: 5px 0; color: {$color};'>";
            echo "{$icon} <strong>{$test}</strong>: {$result['message']}";
            echo "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
}

// Run tests if accessed directly (in debug mode)
if (WP_DEBUG && isset($_GET['test']) && $_GET['test'] === 'license_validation') {
    add_action('wp_loaded', function() {
        if (current_user_can('ufsc_manage')) {
            $test = new UFSC_License_Validation_Test();
            $test->run_tests();
        }
    });
}