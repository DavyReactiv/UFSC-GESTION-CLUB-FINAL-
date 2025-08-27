<?php

/**
 * Test Helper Class
 *
 * Provides testing utilities for the UFSC document management system
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test Helper Class
 */
class UFSC_Test_Helper
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return UFSC_Test_Helper
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_ajax_ufsc_test_document_system', array($this, 'test_document_system'));
    }

    /**
     * Test the document management system
     */
    public function test_document_system()
    {
        // Check permissions
        if (!current_user_can('ufsc_manage')) {
            wp_send_json_error('Accès non autorisé.');
        }

        $tests = [];

        // Test 1: Check if all required classes exist
        $tests['classes'] = [
            'status' => 'success',
            'message' => 'Toutes les classes requises sont disponibles',
            'details' => []
        ];

        $required_classes = [
            'UFSC_Club_Manager',
            'UFSC_Document_Manager'
        ];

        foreach ($required_classes as $class) {
            if (class_exists($class)) {
                $tests['classes']['details'][] = "✓ $class - OK";
            } else {
                $tests['classes']['status'] = 'error';
                $tests['classes']['message'] = 'Des classes requises sont manquantes';
                $tests['classes']['details'][] = "✗ $class - MANQUANT";
            }
        }

        // Test 2: Check database structure
        global $wpdb;
        $table_name = $wpdb->prefix . 'ufsc_clubs';
        
        $tests['database'] = [
            'status' => 'success',
            'message' => 'Structure de base de données correcte',
            'details' => []
        ];

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $tests['database']['status'] = 'error';
            $tests['database']['message'] = 'Table de clubs manquante';
            $tests['database']['details'][] = "✗ Table $table_name n'existe pas";
        } else {
            $tests['database']['details'][] = "✓ Table $table_name existe";

            // Check required columns
            $required_columns = [
                'doc_statuts',
                'doc_recepisse', 
                'doc_jo',
                'doc_pv_ag',
                'doc_cer',
                'doc_attestation_cer',
                'doc_attestation_affiliation'
            ];

            $existing_columns = $wpdb->get_col("DESCRIBE $table_name");
            foreach ($required_columns as $column) {
                if (in_array($column, $existing_columns)) {
                    $tests['database']['details'][] = "✓ Colonne $column - OK";
                } else {
                    $tests['database']['status'] = 'warning';
                    $tests['database']['message'] = 'Certaines colonnes de documents sont manquantes';
                    $tests['database']['details'][] = "⚠ Colonne $column - MANQUANTE";
                }
            }
        }

        // Test 3: Check upload directory permissions
        $upload_dir = wp_upload_dir();
        $tests['uploads'] = [
            'status' => 'success',
            'message' => 'Répertoire d\'upload accessible',
            'details' => []
        ];

        if (!is_writable($upload_dir['path'])) {
            $tests['uploads']['status'] = 'error';
            $tests['uploads']['message'] = 'Répertoire d\'upload non accessible en écriture';
            $tests['uploads']['details'][] = "✗ " . $upload_dir['path'] . " non accessible en écriture";
        } else {
            $tests['uploads']['details'][] = "✓ " . $upload_dir['path'] . " accessible en écriture";
        }

        // Test 4: Test document manager functionality
        $tests['document_manager'] = [
            'status' => 'success',
            'message' => 'Gestionnaire de documents fonctionnel',
            'details' => []
        ];

        try {
            $doc_manager = UFSC_Document_Manager::get_instance();
            $tests['document_manager']['details'][] = "✓ Instance du gestionnaire créée";

            // Test secure link generation
            $test_link = $doc_manager->get_secure_download_link(1, 'statuts');
            if (strpos($test_link, '_wpnonce') !== false) {
                $tests['document_manager']['details'][] = "✓ Génération de liens sécurisés";
            } else {
                $tests['document_manager']['status'] = 'warning';
                $tests['document_manager']['details'][] = "⚠ Liens sécurisés non générés correctement";
            }

        } catch (Exception $e) {
            $tests['document_manager']['status'] = 'error';
            $tests['document_manager']['message'] = 'Erreur du gestionnaire de documents';
            $tests['document_manager']['details'][] = "✗ " . $e->getMessage();
        }

        // Test 5: Check if hooks are properly registered
        $tests['hooks'] = [
            'status' => 'success',
            'message' => 'Hooks WordPress correctement enregistrés',
            'details' => []
        ];

        global $wp_filter;
        $required_hooks = [
            'wp_ajax_ufsc_validate_club',
            'wp_ajax_ufsc_upload_club_attestation',
            'wp_ajax_ufsc_delete_club_attestation',
            'wp_ajax_ufsc_upload_licence_attestation',
            'wp_ajax_ufsc_delete_licence_attestation'
        ];

        foreach ($required_hooks as $hook) {
            if (isset($wp_filter[$hook])) {
                $tests['hooks']['details'][] = "✓ Hook $hook enregistré";
            } else {
                $tests['hooks']['status'] = 'warning';
                $tests['hooks']['details'][] = "⚠ Hook $hook non trouvé";
            }
        }

        // Calculate overall status
        $overall_status = 'success';
        $error_count = 0;
        $warning_count = 0;

        foreach ($tests as $test) {
            if ($test['status'] === 'error') {
                $overall_status = 'error';
                $error_count++;
            } elseif ($test['status'] === 'warning' && $overall_status !== 'error') {
                $overall_status = 'warning';
                $warning_count++;
            }
        }

        $result = [
            'overall_status' => $overall_status,
            'summary' => [
                'total_tests' => count($tests),
                'errors' => $error_count,
                'warnings' => $warning_count,
                'success' => count($tests) - $error_count - $warning_count
            ],
            'tests' => $tests,
            'timestamp' => current_time('mysql')
        ];

        wp_send_json_success($result);
    }

    /**
     * Create a test club with sample data
     *
     * @return int|false Club ID or false on failure
     */
    public function create_test_club()
    {
        if (!current_user_can('ufsc_manage')) {
            return false;
        }

        $club_manager = UFSC_Club_Manager::get_instance();
        
        $test_data = [
            'nom' => 'Club Test UFSC - ' . date('Y-m-d H:i:s'),
            'adresse' => '123 rue de Test',
            'code_postal' => '75001',
            'ville' => 'Paris',
            'region' => 'Île-de-France',
            'telephone' => '01 23 45 67 89',
            'email' => 'test@club-ufsc.fr',
            'num_declaration' => 'TEST' . time(),
            'date_declaration' => date('Y-m-d'),
            'president_nom' => 'Jean Dupont',
            'president_email' => 'president@club-ufsc.fr',
            'president_tel' => '01 23 45 67 89',
            'secretaire_nom' => 'Marie Martin',
            'secretaire_email' => 'secretaire@club-ufsc.fr',
            'secretaire_tel' => '01 23 45 67 89',
            'tresorier_nom' => 'Paul Bernard',
            'tresorier_email' => 'tresorier@club-ufsc.fr',
            'tresorier_tel' => '01 23 45 67 89',
            'statut' => 'en_attente',
            'date_creation' => current_time('mysql')
        ];

        return $club_manager->add_club($test_data);
    }

    /**
     * Get system information for debugging
     *
     * @return array System information
     */
    public function get_system_info()
    {
        global $wpdb;

        return [
            'wordpress' => [
                'version' => get_bloginfo('version'),
                'multisite' => is_multisite(),
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ],
            'php' => [
                'version' => PHP_VERSION,
                'max_upload_size' => ini_get('upload_max_filesize'),
                'max_post_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit')
            ],
            'plugin' => [
                'version' => UFSC_PLUGIN_VERSION,
                'path' => UFSC_PLUGIN_PATH,
                'url' => UFSC_PLUGIN_URL
            ],
            'database' => [
                'version' => $wpdb->get_var('SELECT VERSION()'),
                'charset' => $wpdb->charset,
                'collate' => $wpdb->collate
            ],
            'uploads' => wp_upload_dir()
        ];
    }
}