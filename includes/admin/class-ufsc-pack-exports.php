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
            'ufsc_manage',
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
        if (!current_user_can('ufsc_manage')) return;
        global $wpdb;
        $nonce = wp_create_nonce('ufsc_export_csv');

        // Estimated counts
        $club_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs");
        $lic_count  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences");

        // Field groups
        $club_groups = array(
            'informations' => array(
                'id' => 'ID',
                'name' => 'Nom',
                'email' => 'Email',
                'ville' => 'Ville',
                'region' => 'Région',
                'logo_url' => 'Logo URL'
            ),
            'pack' => array(
                'pack_credits_total' => 'Pack crédits total',
                'pack_credits_used'  => 'Pack crédits utilisés',
                'created_at'         => 'Créé le'
            )
        );

        $lic_groups = array(
            'identite' => array(
                'id' => 'ID',
                'club_id' => 'Club ID',
                'nom' => 'Nom',
                'prenom' => 'Prénom',
                'sexe' => 'Sexe',
                'date_naissance' => 'Date de naissance'
            ),
            'contact' => array(
                'email' => 'Email',
                'ville' => 'Ville'
            ),
            'status' => array(
                'competition' => 'Compétition',
                'statut' => 'Statut',
                'date_creation' => 'Date création',
                'is_included' => 'Inclus (pack?)'
            )
        );

        ?>
        <div class="ufsc-export-section">
            <h4><?php esc_html_e('Clubs', 'plugin-ufsc-gestion-club-13072025'); ?></h4>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank" class="ufsc-export-form" data-target="clubs">
                <input type="hidden" name="action" value="ufsc_export_clubs_csv" />
                <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
                <button type="button" class="button open-export-modal" data-modal="clubs"><?php esc_html_e('Configurer l\'export', 'plugin-ufsc-gestion-club-13072025'); ?></button>
            </form>

            <h4><?php esc_html_e('Licences', 'plugin-ufsc-gestion-club-13072025'); ?></h4>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" target="_blank" class="ufsc-export-form" data-target="licences">
                <input type="hidden" name="action" value="ufsc_export_licences_csv" />
                <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>" />
                <button type="button" class="button open-export-modal" data-modal="licences"><?php esc_html_e('Configurer l\'export', 'plugin-ufsc-gestion-club-13072025'); ?></button>
            </form>
        </div>

        <!-- Clubs Modal -->
        <div id="ufsc-export-modal-clubs" class="ufsc-export-modal" style="display:none;">
            <div class="ufsc-export-modal-content">
                <h3><?php esc_html_e('Exporter Clubs', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
                <?php foreach ($club_groups as $group => $fields): ?>
                    <div class="ufsc-field-card">
                        <h4><?php echo esc_html(ucfirst($group)); ?></h4>
                        <?php foreach ($fields as $key => $label): ?>
                            <label><input type="checkbox" data-field value="<?php echo esc_attr($key); ?>" checked> <?php echo esc_html($label); ?></label><br>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <p><a href="#" class="select-all"><?php esc_html_e('Tout sélectionner', 'plugin-ufsc-gestion-club-13072025'); ?></a> | <a href="#" class="select-none"><?php esc_html_e('Tout désélectionner', 'plugin-ufsc-gestion-club-13072025'); ?></a></p>
                <p>
                    <label><?php esc_html_e('Séparateur', 'plugin-ufsc-gestion-club-13072025'); ?>
                        <select class="separator">
                            <option value=";" selected>;</option>
                            <option value=",">,</option>
                            <option value="\t"><?php esc_html_e('Tabulation', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                        </select>
                    </label>
                </p>
                <p><label><input type="checkbox" class="bom" checked> <?php esc_html_e('Ajouter BOM UTF-8', 'plugin-ufsc-gestion-club-13072025'); ?></label></p>
                <p><?php esc_html_e('Filtres actifs', 'plugin-ufsc-gestion-club-13072025'); ?> : <em><?php esc_html_e('Aucun', 'plugin-ufsc-gestion-club-13072025'); ?></em></p>
                <p><?php esc_html_e('Lignes estimées', 'plugin-ufsc-gestion-club-13072025'); ?> : <strong><?php echo esc_html($club_count); ?></strong></p>
                <div class="ufsc-modal-actions">
                    <button type="button" class="button button-primary confirm-export"><?php esc_html_e('Exporter', 'plugin-ufsc-gestion-club-13072025'); ?></button>
                    <button type="button" class="button close-export-modal"><?php esc_html_e('Annuler', 'plugin-ufsc-gestion-club-13072025'); ?></button>
                </div>
            </div>
        </div>

        <!-- Licences Modal -->
        <div id="ufsc-export-modal-licences" class="ufsc-export-modal" style="display:none;">
            <div class="ufsc-export-modal-content">
                <h3><?php esc_html_e('Exporter Licences', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
                <?php foreach ($lic_groups as $group => $fields): ?>
                    <div class="ufsc-field-card">
                        <h4><?php echo esc_html(ucfirst($group)); ?></h4>
                        <?php foreach ($fields as $key => $label): ?>
                            <label><input type="checkbox" data-field value="<?php echo esc_attr($key); ?>" checked> <?php echo esc_html($label); ?></label><br>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <p><a href="#" class="select-all"><?php esc_html_e('Tout sélectionner', 'plugin-ufsc-gestion-club-13072025'); ?></a> | <a href="#" class="select-none"><?php esc_html_e('Tout désélectionner', 'plugin-ufsc-gestion-club-13072025'); ?></a></p>
                <p>
                    <label><?php esc_html_e('Séparateur', 'plugin-ufsc-gestion-club-13072025'); ?>
                        <select class="separator">
                            <option value=";" selected>;</option>
                            <option value=",">,</option>
                            <option value="\t"><?php esc_html_e('Tabulation', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                        </select>
                    </label>
                </p>
                <p><label><input type="checkbox" class="bom" checked> <?php esc_html_e('Ajouter BOM UTF-8', 'plugin-ufsc-gestion-club-13072025'); ?></label></p>
                <p><?php esc_html_e('Filtres actifs', 'plugin-ufsc-gestion-club-13072025'); ?> : <em><?php esc_html_e('Aucun', 'plugin-ufsc-gestion-club-13072025'); ?></em></p>
                <p><?php esc_html_e('Lignes estimées', 'plugin-ufsc-gestion-club-13072025'); ?> : <strong><?php echo esc_html($lic_count); ?></strong></p>
                <div class="ufsc-modal-actions">
                    <button type="button" class="button button-primary confirm-export"><?php esc_html_e('Exporter', 'plugin-ufsc-gestion-club-13072025'); ?></button>
                    <button type="button" class="button close-export-modal"><?php esc_html_e('Annuler', 'plugin-ufsc-gestion-club-13072025'); ?></button>
                </div>
            </div>
        </div>

        <style>
            .ufsc-export-modal {position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:10000;display:flex;align-items:center;justify-content:center;}
            .ufsc-export-modal-content {background:#fff;padding:20px;max-width:600px;width:90%;max-height:80vh;overflow:auto;border-radius:4px;}
            .ufsc-field-card {border:1px solid #ddd;padding:10px;margin-bottom:10px;border-radius:3px;background:#fafafa;}
            .ufsc-modal-actions {margin-top:15px;text-align:right;}
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function(){
            function setupModal(type){
                var modal = document.getElementById('ufsc-export-modal-'+type);
                var form  = document.querySelector('form.ufsc-export-form[data-target="'+type+'"]');
                if(!modal || !form) return;

                modal.querySelector('.select-all').addEventListener('click', function(e){
                    e.preventDefault();
                    modal.querySelectorAll('input[data-field]').forEach(function(cb){ cb.checked = true; });
                });
                modal.querySelector('.select-none').addEventListener('click', function(e){
                    e.preventDefault();
                    modal.querySelectorAll('input[data-field]').forEach(function(cb){ cb.checked = false; });
                });
                modal.querySelector('.close-export-modal').addEventListener('click', function(){ modal.style.display='none'; });
                modal.querySelector('.confirm-export').addEventListener('click', function(){
                    form.querySelectorAll('input[name="fields[]"],input[name="separator"],input[name="bom"]').forEach(function(el){ el.remove(); });
                    modal.querySelectorAll('input[data-field]:checked').forEach(function(cb){
                        var i = document.createElement('input');
                        i.type='hidden'; i.name='fields[]'; i.value=cb.value; form.appendChild(i);
                    });
                    var sep = document.createElement('input');
                    sep.type='hidden'; sep.name='separator'; sep.value = modal.querySelector('.separator').value; form.appendChild(sep);
                    var bom = document.createElement('input');
                    bom.type='hidden'; bom.name='bom'; bom.value = modal.querySelector('.bom').checked ? '1':'0'; form.appendChild(bom);
                    form.submit();
                    modal.style.display='none';
                });
            }

            document.querySelectorAll('.open-export-modal').forEach(function(btn){
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    var type = btn.getAttribute('data-modal');
                    var modal = document.getElementById('ufsc-export-modal-'+type);
                    if(modal) modal.style.display='flex';
                });
            });

            setupModal('clubs');
            setupModal('licences');
        });
        </script>
        <?php
    }

    public function render_page() {
        if (!current_user_can('ufsc_manage')) wp_die(__('Unauthorized', 'plugin-ufsc-gestion-club-13072025'));
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

    private function output_csv($filename, $rows, $fields, $separator = ';', $bom = false) {
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $out = fopen('php://output', 'w');
        if ($bom) {
            fprintf($out, "\xEF\xBB\xBF");
        }
        // Header row
        fputcsv($out, $fields, $separator);
        foreach ($rows as $r) {
            $line = array();
            foreach ($fields as $col) {
                $line[] = isset($r[$col]) ? $r[$col] : '';
            }
            fputcsv($out, $line, $separator);
        }
        fclose($out);
        exit;
    }

    public function export_clubs_csv() {
        if (!current_user_can('ufsc_manage')) wp_die('Forbidden');
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ufsc_export_csv')) wp_die('Bad nonce');
        $fields = isset($_POST['fields']) ? array_map('sanitize_key', (array) $_POST['fields']) : array();
        if (empty($fields)) $fields = array('id','name','email');
        $separator = isset($_POST['separator']) ? sanitize_text_field(wp_unslash($_POST['separator'])) : ';';
        $allowed_sep = array(';', ',', "\t");
        if (!in_array($separator, $allowed_sep, true)) $separator = ';';
        $bom = isset($_POST['bom']) && $_POST['bom'] === '1';
        global $wpdb;
        $table = $wpdb->prefix . 'ufsc_clubs';
        $allowed = array('id','name','email','ville','region','logo_url','pack_credits_total','pack_credits_used','created_at');
        $select = array_intersect($fields, $allowed);
        if (empty($select)) $select = array('id','name','email');
        $rows = $wpdb->get_results("SELECT ".implode(',', array_map('esc_sql',$select))." FROM {$table}", ARRAY_A);
        $this->output_csv('clubs.csv', $rows, $select, $separator, $bom);
    }

    public function export_licences_csv() {
        if (!current_user_can('ufsc_manage')) wp_die('Forbidden');
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ufsc_export_csv')) wp_die('Bad nonce');
        $fields = isset($_POST['fields']) ? array_map('sanitize_key', (array) $_POST['fields']) : array();
        if (empty($fields)) $fields = array('id','nom','prenom');
        $separator = isset($_POST['separator']) ? sanitize_text_field(wp_unslash($_POST['separator'])) : ';';
        $allowed_sep = array(';', ',', "\t");
        if (!in_array($separator, $allowed_sep, true)) $separator = ';';
        $bom = isset($_POST['bom']) && $_POST['bom'] === '1';
        global $wpdb;
        $table = $wpdb->prefix . 'ufsc_licences';
        $allowed = array('id','club_id','nom','prenom','sexe','date_naissance','email','ville','competition','statut','date_creation','is_included');
        $select = array_intersect($fields, $allowed);
        if (empty($select)) $select = array('id','nom','prenom');
        $rows = $wpdb->get_results("SELECT ".implode(',', array_map('esc_sql',$select))." FROM {$table}", ARRAY_A);
        $this->output_csv('licences.csv', $rows, $select, $separator, $bom);
    }
}

if (is_admin()) {
    new UFSC_Pack_Exports_Admin();
}
