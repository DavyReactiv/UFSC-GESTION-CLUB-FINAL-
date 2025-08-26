<?php

namespace UFSC\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class FrontendProSettings {
    
    /**
     * Initialize the settings
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }
    
    /**
     * Add settings page to admin menu
     */
    public static function add_settings_page() {
        add_submenu_page(
            'ufsc-gestion-club',
            'Frontend Pro',
            'Frontend Pro',
            'manage_ufsc',
            'ufsc-frontend-pro',
            [__CLASS__, 'settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        register_setting('ufsc_frontend_pro', 'ufsc_frontend_pro_enabled', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        register_setting('ufsc_frontend_pro', 'ufsc_frontend_pro_notifications', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        register_setting('ufsc_frontend_pro', 'ufsc_frontend_pro_datatables', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        register_setting('ufsc_frontend_pro', 'ufsc_frontend_pro_tooltips', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
        register_setting('ufsc_frontend_pro', 'ufsc_frontend_pro_accessibility', [
            'sanitize_callback' => 'absint',
            'default' => 0
        ]);
    }
    
    /**
     * Settings page content
     */
    public static function settings_page() {
        // Save settings
        if (isset($_POST['submit']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'ufsc_frontend_pro_settings')) {
            update_option('ufsc_frontend_pro_enabled', isset($_POST['ufsc_frontend_pro_enabled']));
            update_option('ufsc_frontend_pro_notifications', isset($_POST['ufsc_frontend_pro_notifications']));
            update_option('ufsc_frontend_pro_datatables', isset($_POST['ufsc_frontend_pro_datatables']));
            update_option('ufsc_frontend_pro_tooltips', isset($_POST['ufsc_frontend_pro_tooltips']));
            update_option('ufsc_frontend_pro_accessibility', isset($_POST['ufsc_frontend_pro_accessibility']));
            
            echo '<div class="notice notice-success"><p>Paramètres sauvegardés avec succès !</p></div>';
        }
        
        $enabled = get_option('ufsc_frontend_pro_enabled', true);
        $notifications = get_option('ufsc_frontend_pro_notifications', true);
        $datatables = get_option('ufsc_frontend_pro_datatables', true);
        $tooltips = get_option('ufsc_frontend_pro_tooltips', true);
        $accessibility = get_option('ufsc_frontend_pro_accessibility', true);
        ?>
        
        <div class="wrap">
            <h1>
                <span class="dashicons dashicons-admin-appearance" style="font-size: 1.3em; margin-right: 8px;"></span>
                UFSC Frontend Professionnel
            </h1>
            
            <div class="ufsc-settings-container" style="max-width: 800px;">
                <div class="card">
                    <h2>Configuration des améliorations frontend</h2>
                    <p>Activez ou désactivez les fonctionnalités professionnelles pour optimiser l'expérience utilisateur de votre site UFSC.</p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('ufsc_frontend_pro_settings'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ufsc_frontend_pro_enabled">Activer les fonctionnalités pro</label>
                                </th>
                                <td>
                                    <input type="checkbox" id="ufsc_frontend_pro_enabled" name="ufsc_frontend_pro_enabled" 
                                           value="1" <?php checked($enabled); ?>>
                                    <p class="description">
                                        Active toutes les améliorations frontend professionnelles. 
                                        <strong>Recommandé pour une meilleure expérience utilisateur.</strong>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ufsc_frontend_pro_notifications">Notifications Toast</label>
                                </th>
                                <td>
                                    <input type="checkbox" id="ufsc_frontend_pro_notifications" name="ufsc_frontend_pro_notifications" 
                                           value="1" <?php checked($notifications); ?>>
                                    <p class="description">
                                        Notifications élégantes pour les actions utilisateur (succès, erreur, avertissement).
                                        Utilise la librairie Notyf (CDN).
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ufsc_frontend_pro_datatables">Tableaux DataTables</label>
                                </th>
                                <td>
                                    <input type="checkbox" id="ufsc_frontend_pro_datatables" name="ufsc_frontend_pro_datatables" 
                                           value="1" <?php checked($datatables); ?>>
                                    <p class="description">
                                        Tables interactives avec recherche, tri et pagination pour les licences et documents.
                                        Utilise DataTables (CDN).
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ufsc_frontend_pro_tooltips">Tooltips contextuels</label>
                                </th>
                                <td>
                                    <input type="checkbox" id="ufsc_frontend_pro_tooltips" name="ufsc_frontend_pro_tooltips" 
                                           value="1" <?php checked($tooltips); ?>>
                                    <p class="description">
                                        Bulles d'aide accessibles pour les champs de formulaire et actions complexes.
                                        Compatible clavier et lecteurs d'écran.
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ufsc_frontend_pro_accessibility">Améliorations d'accessibilité</label>
                                </th>
                                <td>
                                    <input type="checkbox" id="ufsc_frontend_pro_accessibility" name="ufsc_frontend_pro_accessibility" 
                                           value="1" <?php checked($accessibility); ?>>
                                    <p class="description">
                                        Attributs ARIA, navigation clavier améliorée, liens de saut et annonces aux lecteurs d'écran.
                                        <strong>Fortement recommandé pour la conformité.</strong>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button('Sauvegarder les paramètres'); ?>
                    </form>
                </div>
                
                <div class="card" style="margin-top: 20px;">
                    <h3>
                        <span class="dashicons dashicons-info" style="color: #0073aa;"></span>
                        Informations techniques
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                        <div>
                            <h4>📊 Statut actuel</h4>
                            <ul>
                                <li><strong>Version:</strong> <?php echo UFSC_GESTION_CLUB_VERSION; ?></li>
                                <li><strong>Fonctionnalités pro:</strong> 
                                    <?php echo $enabled ? '<span style="color: green;">✅ Activées</span>' : '<span style="color: red;">❌ Désactivées</span>'; ?>
                                </li>
                                <li><strong>Constante PHP:</strong> 
                                    <?php echo UFSC_ENABLE_FRONTEND_PRO ? '<span style="color: green;">TRUE</span>' : '<span style="color: red;">FALSE</span>'; ?>
                                </li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4>🔧 Contrôle par code</h4>
                            <p>Pour désactiver via le code, ajoutez dans <code>wp-config.php</code> :</p>
                            <code style="background: #f1f1f1; padding: 5px; border-radius: 3px; display: block;">
                                define('UFSC_DISABLE_FRONTEND_PRO', true);
                            </code>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-left: 4px solid #0073aa; border-radius: 4px;">
                        <h4>📚 Documentation</h4>
                        <p>
                            Consultez le fichier <code>FRONTEND-PRO-GUIDE.md</code> pour la documentation complète 
                            des fonctionnalités et exemples d'utilisation.
                        </p>
                        <p>
                            <strong>Fichier de démonstration :</strong> 
                            <code>assets/demo-frontend-pro.html</code> - Testez toutes les fonctionnalités
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .ufsc-settings-container .card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .ufsc-settings-container .card h2,
        .ufsc-settings-container .card h3 {
            margin-top: 0;
            color: #23282d;
        }
        
        .ufsc-settings-container .form-table th {
            width: 200px;
            font-weight: 600;
        }
        
        .ufsc-settings-container .description {
            font-style: normal;
            color: #666;
            margin-top: 5px;
        }
        </style>
        
        <?php
    }
    
    /**
     * Check if frontend pro is enabled (considering both constant and option)
     */
    public static function is_enabled() {
        if (defined('UFSC_DISABLE_FRONTEND_PRO') && UFSC_DISABLE_FRONTEND_PRO) {
            return false;
        }
        
        return get_option('ufsc_frontend_pro_enabled', true) && UFSC_ENABLE_FRONTEND_PRO;
    }
}

// Initialize settings if in admin
if (is_admin()) {
    \UFSC\Admin\FrontendProSettings::init();
}