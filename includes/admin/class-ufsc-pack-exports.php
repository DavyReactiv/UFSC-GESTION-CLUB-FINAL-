<?php
if (!defined('ABSPATH')) { exit; }

class UFSC_Pack_Exports_Admin {
    const OPT_KEY = 'ufsc_pack_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // CSV exports
        add_action('admin_post_ufsc_export_clubs_csv', array($this, 'export_clubs_csv'));
        add_action('admin_post_ufsc_export_licences_csv', array($this, 'export_licences_csv'));
    }

    public function menu() {
        add_submenu_page(
            'ufsc_licenses_admin', // register under UFSC menu
            __('Réglages Packs & Exports', 'plugin-ufsc-gestion-club-13072025'),
            __('Réglages & Exports', 'plugin-ufsc-gestion-club-13072025'),
            'manage_ufsc',
            'ufsc-pack-exports',
            array($this, 'render_page')
        );
    }

    public function register_settings() {
        register_setting(self::OPT_KEY, self::OPT_KEY, array($this, 'sanitize'));
        add_settings_section('pack_section', __('Pack Affiliation', 'plugin-ufsc-gestion-club-13072025'), '__return_false', self::OPT_KEY);
        add_settings_field('pack_product_id', __('Produit Pack (ID)', 'plugin-ufsc-gestion-club-13072025'), array($this, 'field_number'), self::OPT_KEY, 'pack_section', array('key'=>'pack_product_id'));
        add_settings_field('licence_product_id', __('Produit Licence (ID)', 'plugin-ufsc-gestion-club-13072025'), array($this, 'field_number'), self::OPT_KEY, 'pack_section', array('key'=>'licence_product_id'));
        add_settings_field('pack_credits', __('Crédits par pack', 'plugin-ufsc-gestion-club-13072025'), array($this, 'field_number'), self::OPT_KEY, 'pack_section', array('key'=>'pack_credits', 'min'=>1, 'placeholder'=>10));

        add_settings_section('export_section', __('Exports CSV', 'plugin-ufsc-gestion-club-13072025'), '__return_false', self::OPT_KEY);
        add_settings_field('export_forms', __('Exporter', 'plugin-ufsc-gestion-club-13072025'), array($this, 'export_controls'), self::OPT_KEY, 'export_section');
    }

    public function sanitize($opts) {
        $clean = array();
        $clean['pack_product_id'] = isset($opts['pack_product_id']) ? absint($opts['pack_product_id']) : 0;
        $clean['licence_product_id'] = isset($opts['licence_product_id']) ? absint($opts['licence_product_id']) : 0;
        $clean['pack_credits'] = isset($opts['pack_credits']) ? max(1, absint($opts['pack_credits'])) : 10;
        return $clean;
    }

    public function field_number($args) {
        $opts = get_option(self::OPT_KEY, array('pack_credits'=>10));
        $key = $args['key'];
        $val = isset($opts[$key]) ? (int)$opts[$key] : '';
        $min = isset($args['min']) ? intval($args['min']) : 0;
        $ph  = isset($args['placeholder']) ? esc_attr($args['placeholder']) : '';
        printf('<input type="number" name="%s[%s]" value="%s" min="%d" placeholder="%s" />', esc_attr(self::OPT_KEY), esc_attr($key), esc_attr($val), $min, $ph);
    }

    public function export_controls() {
        if (!current_user_can('manage_ufsc')) return;
        $nonce = wp_create_nonce('ufsc_export_csv');
        ?>
        <h4><?php esc_html_e('Clubs', 'plugin-ufsc-gestion-club-13072025'); ?></h4>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank">
            <input type="hidden" name="action" value="ufsc_export_clubs_csv" />
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
            <?php
            $club_fields = array(
                'id'=>'ID',
                'name'=>'Nom',
                'email'=>'Email',
                'ville'=>'Ville',
                'region'=>'Région',
                'logo_url'=>'Logo URL',
                'pack_credits_total'=>'Pack crédits total',
                'pack_credits_used'=>'Pack crédits utilisés',
                'created_at'=>'Créé le',
            );
            foreach ($club_fields as $k=>$label) {
                echo '<label style="display:inline-block;margin:0 12px 8px 0;"><input type="checkbox" name="fields[]" value="'.esc_attr($k).'" checked /> '.esc_html($label).'</label>';
            }
            ?>
            <p><button class="button button-primary"><?php esc_html_e('Exporter Clubs (CSV)', 'plugin-ufsc-gestion-club-13072025'); ?></button></p>
        </form>

        <h4><?php esc_html_e('Licences', 'plugin-ufsc-gestion-club-13072025'); ?></h4>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank">
            <input type="hidden" name="action" value="ufsc_export_licences_csv" />
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
            <?php
            $lic_fields = array(
                'id'=>'ID',
                'club_id'=>'Club ID',
                'nom'=>'Nom',
                'prenom'=>'Prénom',
                'sexe'=>'Sexe',
                'date_naissance'=>'Date de naissance',
                'email'=>'Email',
                'ville'=>'Ville',
                'competition'=>'Compétition',
                'statut'=>'Statut',
                'date_creation'=>'Date création',
                'is_included'=>'Inclus (pack?)'
            );
            foreach ($lic_fields as $k=>$label) {
                echo '<label style="display:inline-block;margin:0 12px 8px 0;"><input type="checkbox" name="fields[]" value="'.esc_attr($k).'" checked /> '.esc_html($label).'</label>';
            }
            ?>
            <p><button class="button button-primary"><?php esc_html_e('Exporter Licences (CSV)', 'plugin-ufsc-gestion-club-13072025'); ?></button></p>
        </form>
        <?php
    }

    public function render_page() {
        if (!current_user_can('manage_ufsc')) wp_die(__('Unauthorized', 'plugin-ufsc-gestion-club-13072025'));
        ?>
        <div class="wrap ufsc-ui">
            <h1><?php esc_html_e('Réglages Packs & Exports', 'plugin-ufsc-gestion-club-13072025'); ?></h1>
            <form method="post" action="options.php">
                <?php
                    settings_fields(self::OPT_KEY);
                    do_settings_sections(self::OPT_KEY);
                    submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function output_csv($filename, $rows, $fields) {
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $out = fopen('php://output', 'w');
        // Header row
        fputcsv($out, $fields);
        foreach ($rows as $r) {
            $line = array();
            foreach ($fields as $col) {
                $line[] = isset($r[$col]) ? $r[$col] : '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    public function export_clubs_csv() {
        if (!current_user_can('manage_ufsc')) wp_die('Forbidden');
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ufsc_export_csv')) wp_die('Bad nonce');
        $fields = isset($_POST['fields']) ? array_map('sanitize_key', (array) $_POST['fields']) : array();
        if (empty($fields)) $fields = array('id','name','email');
        global $wpdb;
        $table = $wpdb->prefix . 'ufsc_clubs';
        $allowed = array('id','name','email','ville','region','logo_url','pack_credits_total','pack_credits_used','created_at');
        $select = array_intersect($fields, $allowed);
        if (empty($select)) $select = array('id','name','email');
        $rows = $wpdb->get_results("SELECT ".implode(',', array_map('esc_sql',$select))." FROM {$table}", ARRAY_A);
        $this->output_csv('clubs.csv', $rows, $select);
    }

    public function export_licences_csv() {
        if (!current_user_can('manage_ufsc')) wp_die('Forbidden');
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ufsc_export_csv')) wp_die('Bad nonce');
        $fields = isset($_POST['fields']) ? array_map('sanitize_key', (array) $_POST['fields']) : array();
        if (empty($fields)) $fields = array('id','nom','prenom');
        global $wpdb;
        $table = $wpdb->prefix . 'ufsc_licences';
        $allowed = array('id','club_id','nom','prenom','sexe','date_naissance','email','ville','competition','statut','date_creation','is_included');
        $select = array_intersect($fields, $allowed);
        if (empty($select)) $select = array('id','nom','prenom');
        $rows = $wpdb->get_results("SELECT ".implode(',', array_map('esc_sql',$select))." FROM {$table}", ARRAY_A);
        $this->output_csv('licences.csv', $rows, $select);
    }
}

if (is_admin()) {
    new UFSC_Pack_Exports_Admin();
}
