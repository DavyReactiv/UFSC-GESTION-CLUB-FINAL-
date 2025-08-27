<?php
/**
 * UFSC Admin Settings
 *
 * Admin settings page for UFSC plugin configuration
 * Provides WooCommerce integration options and frontend behavior settings
 *
 * @package UFSC_Gestion_Club
 * @since 1.2.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * UFSC Admin Settings Class
 */
class UFSC_Admin_Settings {

    /**
     * Settings page hook suffix
     */
    private $hook_suffix;

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add settings page to WordPress admin
     */
    public function add_settings_page() {
        $this->hook_suffix = add_options_page(
            __('Paramètres UFSC', 'plugin-ufsc-gestion-club-13072025'),
            __('UFSC', 'plugin-ufsc-gestion-club-13072025'),
            'ufsc_manage',
            'ufsc-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register all settings using WordPress Settings API
     */
    public function register_settings() {
        // Register settings group
        register_setting(
            'ufsc_settings_group',
            'ufsc_wc_affiliation_product_id',
            array(
                'type' => 'integer',
                'default' => 4823,
                'sanitize_callback' => 'absint'
            )
        );

        register_setting(
            'ufsc_settings_group',
            'ufsc_wc_license_product_ids',
            array(
                'type' => 'string',
                'default' => '2934',
                'sanitize_callback' => array($this, 'sanitize_csv_ids')
            )
        );

        register_setting(
            'ufsc_settings_group',
            'ufsc_auto_create_user',
            array(
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean'
            )
        );

        register_setting(
            'ufsc_settings_group',
            'ufsc_require_login_shortcodes',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean'
            )
        );

        // New WooCommerce e-commerce settings
        register_setting(
            'ufsc_settings_group',
            'ufsc_wc_pack_10_product_id',
            array(
                'type' => 'integer',
                'default' => 0,
                'sanitize_callback' => 'absint'
            )
        );

        register_setting(
            'ufsc_settings_group',
            'ufsc_wc_individual_licence_product_id',
            array(
                'type' => 'integer',
                'default' => 0,
                'sanitize_callback' => 'absint'
            )
        );

        register_setting(
            'ufsc_settings_group',
            'ufsc_auto_pack_enabled',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean'
            )
        );

        register_setting(
            'ufsc_settings_group',
            'ufsc_auto_order_for_admin_licences',
            array(
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => 'rest_sanitize_boolean'
            )
        );

        // Add settings sections
        add_settings_section(
            'ufsc_woocommerce_section',
            __('Intégration WooCommerce', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'woocommerce_section_callback'),
            'ufsc-settings'
        );

        add_settings_section(
            'ufsc_frontend_section',
            __('Comportement Frontend', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'frontend_section_callback'),
            'ufsc-settings'
        );

        // Add settings fields
        add_settings_field(
            'ufsc_wc_affiliation_product_id',
            __('ID Produit Affiliation', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'render_affiliation_product_id_field'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        add_settings_field(
            'ufsc_wc_license_product_ids',
            __('IDs Produits Licences', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'render_license_product_ids_field'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        add_settings_field(
            'ufsc_auto_create_user',
            __('Création automatique d\'utilisateur', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'render_auto_create_user_field'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        add_settings_field(
            'ufsc_require_login_shortcodes',
            __('Connexion requise pour les shortcodes', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'render_require_login_field'),
            'ufsc-settings',
            'ufsc_frontend_section'
        );

        // New WooCommerce e-commerce settings fields
        add_settings_field(
            'ufsc_wc_pack_10_product_id',
            __('ID Produit Pack 10 Licences', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'render_pack_10_product_id_field'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        add_settings_field(
            'ufsc_wc_individual_licence_product_id',
            __('ID Produit Licence Individuelle', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'render_individual_licence_product_id_field'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        add_settings_field(
            'ufsc_auto_pack_enabled',
            __('Auto-ajout Pack 10 avec Affiliation', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'render_auto_pack_enabled_field'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        add_settings_field(
            'ufsc_auto_order_for_admin_licences',
            __('Commande auto pour licences admin', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'render_auto_order_for_admin_licences_field'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );
    }

    /**
     * Sanitize CSV of product IDs
     */
    public function sanitize_csv_ids($value) {
        if (empty($value)) {
            return '2934';
        }

        $ids = explode(',', $value);
        $sanitized_ids = array();

        foreach ($ids as $id) {
            $sanitized_id = absint(trim($id));
            if ($sanitized_id > 0) {
                $sanitized_ids[] = $sanitized_id;
            }
        }

        return !empty($sanitized_ids) ? implode(',', $sanitized_ids) : '2934';
    }

    /**
     * Retrieve a boolean option value.
     *
     * @param string $name    Option name.
     * @param bool   $default Default value.
     * @return bool
     */
    private function get_bool_option($name, $default = false) {
        return (bool) get_option($name, $default);
    }

    /**
     * Retrieve an integer option value.
     *
     * @param string $name    Option name.
     * @param int    $default Default value.
     * @return int
     */
    private function get_int_option($name, $default = 0) {
        return (int) get_option($name, $default);
    }

    /**
     * Render settings page with tabbed interface
     */
    public function render_settings_page() {
        if (!current_user_can('ufsc_manage')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.'));
        }

        $tabs = array(
            'general'     => __('Général', 'plugin-ufsc-gestion-club-13072025'),
            'woocommerce' => __('WooCommerce', 'plugin-ufsc-gestion-club-13072025'),
            'emails'      => __('Emails', 'plugin-ufsc-gestion-club-13072025'),
            'export'      => __('Export CSV', 'plugin-ufsc-gestion-club-13072025'),
            'access'      => __('Accès & Rattachement', 'plugin-ufsc-gestion-club-13072025'),
            'advanced'    => __('Avancé', 'plugin-ufsc-gestion-club-13072025'),
        );

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        if (!array_key_exists($current_tab, $tabs)) {
            $current_tab = 'general';
        }

        ?>
        <div class="wrap ufsc-ui">
            <h1>
                <span class="dashicons dashicons-admin-settings" style="font-size: 1.3em; margin-right: 8px;"></span>
                <?php _e('Paramètres UFSC', 'plugin-ufsc-gestion-club-13072025'); ?>
            </h1>

            <h2 class="nav-tab-wrapper ufsc-tabs" role="tablist">
                <?php foreach ($tabs as $key => $label) :
                    $active = ($current_tab === $key) ? ' nav-tab-active' : '';
                ?>
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'ufsc-settings', 'tab' => $key), admin_url('options-general.php'))); ?>"
                       class="nav-tab<?php echo $active; ?>"
                       id="tab-<?php echo esc_attr($key); ?>"
                       role="tab"
                       aria-selected="<?php echo $current_tab === $key ? 'true' : 'false'; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <div class="ufsc-settings-container" style="max-width: 800px;">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ufsc_settings_group');
                    $this->render_tab_sections($current_tab);
                    submit_button(__('Sauvegarder les paramètres', 'plugin-ufsc-gestion-club-13072025'));
                    ?>
                </form>

                <div class="card" style="margin-top: 20px;">
                    <h3>
                        <span class="dashicons dashicons-info" style="color: #0073aa;"></span>
                        <?php _e('Informations', 'plugin-ufsc-gestion-club-13072025'); ?>
                    </h3>
                    <p>
                        <?php _e('Ces paramètres contrôlent l\'intégration avec WooCommerce et le comportement des shortcodes frontend.', 'plugin-ufsc-gestion-club-13072025'); ?>
                    </p>
                    <p>
                        <strong><?php _e('Version du plugin:', 'plugin-ufsc-gestion-club-13072025'); ?></strong>
                        <?php echo defined('UFSC_PLUGIN_VERSION') ? UFSC_PLUGIN_VERSION : '20.8.1'; ?>
                    </p>
                </div>
            </div>
        </div>
        <script>
        (function() {
            const tabs = document.querySelectorAll('.ufsc-tabs a[role="tab"]');
            tabs.forEach((tab, index) => {
                tab.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                        e.preventDefault();
                        const dir = e.key === 'ArrowRight' ? 1 : -1;
                        const next = tabs[(index + dir + tabs.length) % tabs.length];
                        window.location = next.getAttribute('href');
                    }
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Render settings sections for the active tab
     *
     * @param string $tab Current tab identifier.
     */
    private function render_tab_sections($tab) {
        $map = array(
            'general'     => array(),
            'woocommerce' => array('ufsc_woocommerce_section'),
            'emails'      => array(),
            'export'      => array(),
            'access'      => array('ufsc_frontend_section'),
            'advanced'    => array(),
        );

        $page = 'ufsc-settings';

        if (empty($map[$tab])) {
            echo '<p>' . esc_html__('Aucun paramètre disponible pour cet onglet.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
            return;
        }

        global $wp_settings_sections, $wp_settings_fields;

        foreach ($map[$tab] as $section_id) {
            if (empty($wp_settings_sections[$page][$section_id])) {
                continue;
            }

            $section = $wp_settings_sections[$page][$section_id];

            if (!empty($section['title'])) {
                echo '<h2>' . esc_html($section['title']) . '</h2>';
            }

            if (!empty($section['callback'])) {
                call_user_func($section['callback'], $section);
            }

            if (!empty($wp_settings_fields[$page][$section_id])) {
                echo '<table class="form-table" role="presentation">';
                do_settings_fields($page, $section_id);
                echo '</table>';
            }
        }
    }

    /**
     * WooCommerce section callback
     */
    public function woocommerce_section_callback() {
        echo '<p>' . __('Configuration des produits WooCommerce pour l\'intégration avec les affiliations et licences UFSC.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
        echo '<p><strong>' . __('Nouvelles fonctionnalités e-commerce:', 'plugin-ufsc-gestion-club-13072025') . '</strong></p>';
        echo '<ul>';
        echo '<li>' . __('Auto-ajout du Pack 10 licences (350€) avec le produit Affiliation (150€) pour garantir le total de 500€', 'plugin-ufsc-gestion-club-13072025') . '</li>';
        echo '<li>' . __('Création automatique de commandes pour les licences créées en admin (is_included=0) pour traçabilité comptable', 'plugin-ufsc-gestion-club-13072025') . '</li>';
        echo '</ul>';
    }

    /**
     * Frontend/access section callback
     */
    public function frontend_section_callback() {
        echo '<p>' . __('Configuration de l\'accès aux shortcodes et du rattachement des utilisateurs.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * Render affiliation product ID field
     */
    public function render_affiliation_product_id_field() {
        $value = $this->get_int_option('ufsc_wc_affiliation_product_id', 4823);
        ?>
        <input type="number" 
               id="ufsc_wc_affiliation_product_id" 
               name="ufsc_wc_affiliation_product_id" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               class="regular-text" />
        <p class="description">
            <?php _e('ID du produit WooCommerce utilisé pour les affiliations de club (défaut: 4823).', 'plugin-ufsc-gestion-club-13072025'); ?>
        </p>
        <?php
    }

    /**
     * Render license product IDs field
     */
    public function render_license_product_ids_field() {
        $value = (string) get_option('ufsc_wc_license_product_ids', '2934');
        ?>
        <input type="text" 
               id="ufsc_wc_license_product_ids" 
               name="ufsc_wc_license_product_ids" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               placeholder="2934,2935,2936" />
        <p class="description">
            <?php _e('IDs des produits WooCommerce pour les licences, séparés par des virgules (défaut: 2934).', 'plugin-ufsc-gestion-club-13072025'); ?>
        </p>
        <?php
    }

    /**
     * Render auto create user field
     */
    public function render_auto_create_user_field() {
        $value = $this->get_bool_option('ufsc_auto_create_user', false);
        ?>
        <label for="ufsc_auto_create_user">
            <input type="checkbox" 
                   id="ufsc_auto_create_user" 
                   name="ufsc_auto_create_user" 
                   value="1" 
                   <?php checked($value); ?> />
            <?php _e('Créer automatiquement un utilisateur WordPress lors des commandes WooCommerce', 'plugin-ufsc-gestion-club-13072025'); ?>
        </label>
        <p class="description">
            <?php _e('Si activé, un compte utilisateur sera automatiquement créé pour les clients WooCommerce qui n\'en ont pas.', 'plugin-ufsc-gestion-club-13072025'); ?>
        </p>
        <?php
    }

    /**
     * Render require login field
     */
    public function render_require_login_field() {
        $value = $this->get_bool_option('ufsc_require_login_shortcodes', true);
        ?>
        <label for="ufsc_require_login_shortcodes">
            <input type="checkbox" 
                   id="ufsc_require_login_shortcodes" 
                   name="ufsc_require_login_shortcodes" 
                   value="1" 
                   <?php checked($value); ?> />
            <?php _e('Exiger une connexion pour accéder aux shortcodes UFSC', 'plugin-ufsc-gestion-club-13072025'); ?>
        </label>
        <p class="description">
            <?php _e('Si activé, les utilisateurs doivent être connectés pour voir les shortcodes [ufsc_club_*]. Si désactivé, les shortcodes sont visibles par tous.', 'plugin-ufsc-gestion-club-13072025'); ?>
        </p>
        <?php
    }

    /**
     * Render Pack 10 product ID field
     */
    public function render_pack_10_product_id_field() {
        $value = $this->get_int_option('ufsc_wc_pack_10_product_id', 0);
        ?>
        <input type="number" 
               id="ufsc_wc_pack_10_product_id" 
               name="ufsc_wc_pack_10_product_id" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               class="regular-text" />
        <p class="description">
            <?php _e('ID du produit WooCommerce Pack 10 licences (350€) ajouté automatiquement avec l\'affiliation.', 'plugin-ufsc-gestion-club-13072025'); ?>
        </p>
        <?php
    }

    /**
     * Render Individual licence product ID field
     */
    public function render_individual_licence_product_id_field() {
        $value = $this->get_int_option('ufsc_wc_individual_licence_product_id', 0);
        ?>
        <input type="number" 
               id="ufsc_wc_individual_licence_product_id" 
               name="ufsc_wc_individual_licence_product_id" 
               value="<?php echo esc_attr($value); ?>" 
               min="1" 
               class="regular-text" />
        <p class="description">
            <?php _e('ID du produit WooCommerce Licence individuelle (35€) utilisé pour les commandes automatiques.', 'plugin-ufsc-gestion-club-13072025'); ?>
        </p>
        <?php
    }

    /**
     * Render auto pack enabled field
     */
    public function render_auto_pack_enabled_field() {
        $value = $this->get_bool_option('ufsc_auto_pack_enabled', true);
        ?>
        <label for="ufsc_auto_pack_enabled">
            <input type="checkbox" 
                   id="ufsc_auto_pack_enabled" 
                   name="ufsc_auto_pack_enabled" 
                   value="1" 
                   <?php checked($value); ?> />
            <?php _e('Ajouter automatiquement le Pack 10 licences avec le produit Affiliation', 'plugin-ufsc-gestion-club-13072025'); ?>
        </label>
        <p class="description">
            <?php _e('Si activé, le Pack 10 licences sera automatiquement ajouté au panier quand une affiliation est achetée.', 'plugin-ufsc-gestion-club-13072025'); ?>
        </p>
        <?php
    }

    /**
     * Render auto order for admin licences field
     */
    public function render_auto_order_for_admin_licences_field() {
        $value = $this->get_bool_option('ufsc_auto_order_for_admin_licences', true);
        ?>
        <label for="ufsc_auto_order_for_admin_licences">
            <input type="checkbox" 
                   id="ufsc_auto_order_for_admin_licences" 
                   name="ufsc_auto_order_for_admin_licences" 
                   value="1" 
                   <?php checked($value); ?> />
            <?php _e('Créer automatiquement une commande WooCommerce pour les licences créées en admin (non incluses)', 'plugin-ufsc-gestion-club-13072025'); ?>
        </label>
        <p class="description">
            <?php _e('Si activé, une commande WooCommerce sera créée automatiquement pour chaque licence avec is_included=0.', 'plugin-ufsc-gestion-club-13072025'); ?>
        </p>
        <?php
    }
}

// Initialize the admin settings class
if (is_admin()) {
    new UFSC_Admin_Settings();
}