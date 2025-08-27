<?php

/**
 * UFSC Sync Monitor Class
 * 
 * Provides synchronization monitoring and diagnostic tools
 * 
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * UFSC Sync Monitor Class
 */
class UFSC_Sync_Monitor
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
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
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_ufsc_clear_logs', [$this, 'handle_clear_logs']);
        add_action('wp_ajax_ufsc_test_sync', [$this, 'handle_test_sync']);
    }

    /**
     * Add admin menu for sync monitoring
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'ufsc_dashboard',
            'Synchronisation',
            'Synchronisation',
            'manage_ufsc',
            'ufsc_sync_monitor',
            [$this, 'render_sync_monitor_page']
        );
    }

    /**
     * Render sync monitor page
     */
    public function render_sync_monitor_page()
    {
        // Handle form submissions
        if (isset($_POST['action']) && check_admin_referer('ufsc_sync_monitor')) {
            if ($_POST['action'] === 'clear_logs') {
                $this->clear_all_logs();
                echo '<div class="notice notice-success"><p>Logs supprimés avec succès.</p></div>';
            }
        }

        $recent_logs = $this->get_recent_logs();
        $sync_stats = $this->get_sync_statistics();
        $system_status = $this->check_system_status();
        ?>
        <div class="wrap ufsc-ui">
            <h1>
                <span class="dashicons dashicons-update"></span>
                Synchronisation et Diagnostic
            </h1>

            <!-- System Status -->
            <div class="ufsc-admin-card">
                <h2>État du Système</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Composant</th>
                            <th>État</th>
                            <th>Détails</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($system_status as $component => $status): ?>
                            <tr>
                                <td><?php echo esc_html($component); ?></td>
                                <td>
                                    <span class="badge <?php echo $status['status'] === 'OK' ? 'badge-green' : 'badge-red'; ?>">
                                        <?php echo esc_html($status['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($status['details']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sync Statistics -->
            <div class="ufsc-admin-card">
                <h2>Statistiques de Synchronisation</h2>
                <div class="ufsc-stats-grid">
                    <div class="ufsc-stat-item">
                        <h3><?php echo esc_html($sync_stats['total_operations']); ?></h3>
                        <p>Opérations totales (24h)</p>
                    </div>
                    <div class="ufsc-stat-item">
                        <h3><?php echo esc_html($sync_stats['successful_operations']); ?></h3>
                        <p>Opérations réussies</p>
                    </div>
                    <div class="ufsc-stat-item">
                        <h3><?php echo esc_html($sync_stats['failed_operations']); ?></h3>
                        <p>Opérations échouées</p>
                    </div>
                    <div class="ufsc-stat-item">
                        <h3><?php echo esc_html($sync_stats['success_rate']); ?>%</h3>
                        <p>Taux de réussite</p>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="ufsc-admin-card">
                <h2>Activité Récente</h2>
                <div class="ufsc-admin-toolbar">
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('ufsc_sync_monitor'); ?>
                        <input type="hidden" name="action" value="clear_logs">
                        <button type="submit" class="button" onclick="return confirm('Êtes-vous sûr de vouloir supprimer tous les logs ?')">
                            Effacer les logs
                        </button>
                    </form>
                    <button type="button" class="button button-primary" onclick="ufscTestSync()">
                        Tester la synchronisation
                    </button>
                    <button type="button" class="button" onclick="location.reload()">
                        Actualiser
                    </button>
                </div>

                <?php if (empty($recent_logs)): ?>
                    <p>Aucune activité récente enregistrée.</p>
                <?php else: ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Opération</th>
                                <th>Utilisateur</th>
                                <th>Statut</th>
                                <th>Détails</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr class="<?php echo strpos($log['operation'], 'error') !== false ? 'ufsc-log-error' : ''; ?>">
                                    <td><?php echo esc_html($log['timestamp']); ?></td>
                                    <td>
                                        <code><?php echo esc_html($log['operation']); ?></code>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($log['user_id']) {
                                            $user = get_user_by('id', $log['user_id']);
                                            echo $user ? esc_html($user->display_name) : 'Utilisateur #' . $log['user_id'];
                                        } else {
                                            echo 'Système';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (strpos($log['operation'], 'error') !== false): ?>
                                            <span class="badge badge-red">Erreur</span>
                                        <?php elseif (strpos($log['operation'], 'success') !== false): ?>
                                            <span class="badge badge-green">Succès</span>
                                        <?php else: ?>
                                            <span class="badge" style="background-color: #0073aa;">Info</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['data'])): ?>
                                            <details>
                                                <summary>Voir détails</summary>
                                                <pre style="font-size: 11px; max-height: 100px; overflow-y: auto;"><?php echo esc_html(wp_json_encode($log['data'], JSON_PRETTY_PRINT)); ?></pre>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Diagnostic Tools -->
            <div class="ufsc-admin-card">
                <h2>Outils de Diagnostic</h2>
                <div class="ufsc-diagnostic-tools">
                    <h3>Points de Vérification</h3>
                    <ul class="ufsc-checklist">
                        <li class="<?php echo $this->check_ajax_endpoints() ? 'ufsc-check-pass' : 'ufsc-check-fail'; ?>">
                            <span class="dashicons <?php echo $this->check_ajax_endpoints() ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            Endpoints AJAX fonctionnels
                        </li>
                        <li class="<?php echo $this->check_database_tables() ? 'ufsc-check-pass' : 'ufsc-check-fail'; ?>">
                            <span class="dashicons <?php echo $this->check_database_tables() ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            Tables de base de données présentes
                        </li>
                        <li class="<?php echo $this->check_javascript_libraries() ? 'ufsc-check-pass' : 'ufsc-check-fail'; ?>">
                            <span class="dashicons <?php echo $this->check_javascript_libraries() ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            Bibliothèques JavaScript disponibles
                        </li>
                        <li class="<?php echo $this->check_file_permissions() ? 'ufsc-check-pass' : 'ufsc-check-fail'; ?>">
                            <span class="dashicons <?php echo $this->check_file_permissions() ? 'dashicons-yes' : 'dashicons-no'; ?>"></span>
                            Permissions de fichiers correctes
                        </li>
                    </ul>

                    <h3>Bonnes Pratiques de Synchronisation</h3>
                    <div class="ufsc-best-practices">
                        <h4>1. Validation des Données</h4>
                        <p>Toutes les données sont validées côté serveur avant insertion en base.</p>
                        
                        <h4>2. Gestion des Erreurs</h4>
                        <p>Les erreurs sont loggées et remontées à l'utilisateur avec des messages clairs.</p>
                        
                        <h4>3. Actualisation Automatique</h4>
                        <p>Les données affichées sont automatiquement actualisées après modification.</p>
                        
                        <h4>4. Fallback Gracieux</h4>
                        <p>Si AJAX échoue, le système bascule automatiquement vers une soumission traditionnelle.</p>
                        
                        <h4>5. Logging Complet</h4>
                        <p>Toutes les opérations importantes sont enregistrées pour faciliter le diagnostic.</p>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .ufsc-admin-card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
            padding: 20px;
        }

        .ufsc-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .ufsc-stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .ufsc-stat-item h3 {
            font-size: 2em;
            margin: 0;
            color: #0073aa;
        }

        .ufsc-admin-toolbar {
            margin-bottom: 15px;
        }

        .ufsc-admin-toolbar .button {
            margin-right: 10px;
        }

        .ufsc-log-error {
            background-color: #ffeaea;
        }

        .ufsc-checklist {
            list-style: none;
            padding: 0;
        }

        .ufsc-checklist li {
            padding: 8px 0;
            display: flex;
            align-items: center;
        }

        .ufsc-checklist .dashicons {
            margin-right: 8px;
        }

        .ufsc-check-pass .dashicons {
            color: #46b450;
        }

        .ufsc-check-fail .dashicons {
            color: #dc3232;
        }

        .ufsc-best-practices {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-top: 15px;
        }

        .ufsc-best-practices h4 {
            color: #0073aa;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .ufsc-best-practices h4:first-child {
            margin-top: 0;
        }
        </style>

        <script>
        function ufscTestSync() {
            const button = event.target;
            button.disabled = true;
            button.textContent = 'Test en cours...';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ufsc_test_sync',
                    nonce: '<?php echo wp_create_nonce('ufsc_test_sync'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Test de synchronisation réussi !');
                    } else {
                        alert('Erreur lors du test : ' + (response.data.message || 'Erreur inconnue'));
                    }
                },
                error: function() {
                    alert('Erreur de connexion lors du test.');
                },
                complete: function() {
                    button.disabled = false;
                    button.textContent = 'Tester la synchronisation';
                }
            });
        }
        </script>
        <?php
    }

    /**
     * Get recent logs
     */
    private function get_recent_logs()
    {
        $today = date('Ymd');
        $yesterday = date('Ymd', strtotime('-1 day'));
        
        $logs = [];
        
        // Get today's logs
        $today_logs = get_option('ufsc_operation_log_' . $today, []);
        $logs = array_merge($logs, $today_logs);
        
        // Get yesterday's logs if we need more
        if (count($logs) < 50) {
            $yesterday_logs = get_option('ufsc_operation_log_' . $yesterday, []);
            $logs = array_merge($logs, $yesterday_logs);
        }
        
        // Sort by timestamp (newest first)
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        // Return only the most recent 50
        return array_slice($logs, 0, 50);
    }

    /**
     * Get synchronization statistics
     */
    private function get_sync_statistics()
    {
        $logs = $this->get_recent_logs();
        $total = count($logs);
        $successful = 0;
        $failed = 0;
        
        foreach ($logs as $log) {
            if (strpos($log['operation'], 'success') !== false) {
                $successful++;
            } elseif (strpos($log['operation'], 'error') !== false) {
                $failed++;
            }
        }
        
        $success_rate = $total > 0 ? round(($successful / $total) * 100, 1) : 0;
        
        return [
            'total_operations' => $total,
            'successful_operations' => $successful,
            'failed_operations' => $failed,
            'success_rate' => $success_rate
        ];
    }

    /**
     * Check system status
     */
    private function check_system_status()
    {
        return [
            'Base de données' => [
                'status' => $this->check_database_tables() ? 'OK' : 'ERREUR',
                'details' => $this->check_database_tables() ? 'Tables présentes et accessibles' : 'Tables manquantes ou inaccessibles'
            ],
            'AJAX Endpoints' => [
                'status' => $this->check_ajax_endpoints() ? 'OK' : 'ERREUR',
                'details' => $this->check_ajax_endpoints() ? 'Endpoints fonctionnels' : 'Certains endpoints sont inaccessibles'
            ],
            'JavaScript' => [
                'status' => $this->check_javascript_libraries() ? 'OK' : 'ATTENTION',
                'details' => $this->check_javascript_libraries() ? 'Toutes les bibliothèques disponibles' : 'Certaines bibliothèques manquantes (fallback actif)'
            ],
            'Permissions' => [
                'status' => $this->check_file_permissions() ? 'OK' : 'ERREUR',
                'details' => $this->check_file_permissions() ? 'Permissions correctes' : 'Problèmes de permissions détectés'
            ]
        ];
    }

    /**
     * Check if database tables exist
     */
    private function check_database_tables()
    {
        global $wpdb;
        
        $clubs_table = $wpdb->prefix . 'ufsc_clubs';
        $licences_table = $wpdb->prefix . 'ufsc_licences';
        
        $clubs_exists = $wpdb->get_var("SHOW TABLES LIKE '$clubs_table'") === $clubs_table;
        $licences_exists = $wpdb->get_var("SHOW TABLES LIKE '$licences_table'") === $licences_table;
        
        return $clubs_exists && $licences_exists;
    }

    /**
     * Check if AJAX endpoints are working
     */
    private function check_ajax_endpoints()
    {
        // This is a basic check - in a real implementation, you might want to test actual endpoints
        return has_action('wp_ajax_ufsc_save_club') && has_action('wp_ajax_ufsc_get_club_data');
    }

    /**
     * Check if JavaScript libraries are available
     */
    private function check_javascript_libraries()
    {
        // Check if main JS files exist
        $frontend_js = UFSC_PLUGIN_PATH . 'assets/js/frontend.js';
        $admin_js = UFSC_PLUGIN_PATH . 'assets/js/admin.js';
        $form_js = UFSC_PLUGIN_PATH . 'assets/js/form-enhancements.js';
        
        return file_exists($frontend_js) && file_exists($admin_js) && file_exists($form_js);
    }

    /**
     * Check file permissions
     */
    private function check_file_permissions()
    {
        $upload_dir = wp_upload_dir();
        return is_writable($upload_dir['basedir']);
    }

    /**
     * Clear all logs
     */
    private function clear_all_logs()
    {
        global $wpdb;
        
        // Clear operation logs for the last 7 days
        for ($i = 0; $i < 7; $i++) {
            $date = date('Ymd', strtotime("-$i days"));
            delete_option('ufsc_operation_log_' . $date);
        }
    }

    /**
     * Handle clear logs AJAX request
     */
    public function handle_clear_logs()
    {
        if (!check_ajax_referer('ufsc_clear_logs', 'nonce', false)) {
            wp_send_json_error('Nonce verification failed');
            return;
        }
        
        if (!current_user_can('manage_ufsc')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $this->clear_all_logs();
        wp_send_json_success('Logs cleared successfully');
    }

    /**
     * Handle test sync AJAX request
     */
    public function handle_test_sync()
    {
        if (!check_ajax_referer('ufsc_test_sync', 'nonce', false)) {
            wp_send_json_error(['message' => 'Nonce verification failed']);
            return;
        }
        
        if (!current_user_can('manage_ufsc')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        // Test database connection
        if (!$this->check_database_tables()) {
            wp_send_json_error(['message' => 'Database tables not accessible']);
            return;
        }
        
        // Test AJAX endpoints
        if (!$this->check_ajax_endpoints()) {
            wp_send_json_error(['message' => 'AJAX endpoints not properly registered']);
            return;
        }
        
        // Log test operation
        ufsc_log_operation('sync_test_success', [
            'user_id' => get_current_user_id(),
            'test_timestamp' => current_time('mysql')
        ]);
        
        wp_send_json_success([
            'message' => 'All synchronization components are working correctly',
            'timestamp' => current_time('mysql')
        ]);
    }
}

// Initialize the sync monitor
UFSC_Sync_Monitor::get_instance();