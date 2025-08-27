<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-ufsc-licence-list-table.php';
require_once plugin_dir_path(__FILE__) . '../helpers.php';
require_once plugin_dir_path(__FILE__) . '../helpers/helpers-licence-status.php';

global $wpdb;

$club_id = isset($_GET['club_id']) ? intval(wp_unslash($_GET['club_id'])) : 0;
$club = null;
if ($club_id) {
    $club = $wpdb->get_row($wpdb->prepare("SELECT nom FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d", $club_id));
}

wp_enqueue_style(
    'ufsc-admin-licence-table-style',
    UFSC_PLUGIN_URL . 'assets/css/admin-licence-table.css',
    [],
    UFSC_PLUGIN_VERSION
);


$list_table = new UFSC_Licence_List_Table($club_id);
$list_table->prepare_items();

// Enqueue DataTables CSS and JS
wp_enqueue_style(
    'datatables-css',
    'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
    [],
    '1.13.6'
);
wp_enqueue_style(
    'datatables-buttons-css',
    'https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css',
    [],
    '2.4.2'
);
wp_enqueue_style(
    'datatables-responsive-css',
    'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css',
    [],
    '2.5.0'
);

wp_enqueue_script(
    'datatables-js',
    'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
    [],
    '1.13.6',
    true
);
wp_enqueue_script(
    'datatables-buttons-js',
    'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
    ['datatables-js'],
    '2.4.2',
    true
);
wp_enqueue_script(
    'datatables-buttons-html5-js',
    'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
    ['datatables-buttons-js'],
    '2.4.2',
    true
);
wp_enqueue_script(
    'datatables-responsive-js',
    'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
    ['datatables-js'],
    '2.5.0',
    true
);
wp_enqueue_script(
    'jszip-js',
    'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
    [],
    '3.10.1',
    true
);

wp_enqueue_script(
    'ufsc-datatables-config',
    UFSC_PLUGIN_URL . 'assets/js/datatables-config.js',
    ['datatables-js', 'datatables-buttons-js', 'datatables-responsive-js'],
    UFSC_PLUGIN_VERSION,
    true
);

// Get club ID and verify club exists
$club_id = isset($_GET['club_id']) ? intval(wp_unslash($_GET['club_id'])) : 0;

// 🔎 Vérification du club
$club = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d",
    $club_id
));
if (!$club) {
    echo '<div class="notice notice-error"><p>Club introuvable.</p></div>';
    return;
}

// Get filter parameters with club_id override
$filters = UFSC_Licence_Filters::get_filter_parameters(['club_id' => $club_id]);

// 📤 Export CSV
if (isset($_GET['export_csv']) && check_admin_referer('ufsc_export_licences_' . $club_id)) {
    // Check if this is a selected export or full export
    if (isset($_GET['export_selected']) && !empty($_GET['selected_ids'])) {
        // Export only selected licences
        $selected_ids = explode(',', sanitize_text_field($_GET['selected_ids']));
        $selected_ids = array_map('intval', $selected_ids);
        $selected_ids = array_filter($selected_ids, function($id) { return $id > 0; });
        
        if (!empty($selected_ids)) {
            $placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
            $query = "SELECT l.id, l.nom, l.prenom, l.sexe, l.date_naissance, l.email, l.region, l.ville, l.competition, l.is_included, l.date_inscription, l.adresse, l.suite_adresse, l.code_postal, l.tel_fixe, l.tel_mobile, l.profession, c.nom as club_nom
                     FROM {$wpdb->prefix}ufsc_licences l
                     LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                     WHERE l.id IN ($placeholders) AND l.club_id = %d
                     ORDER BY l.date_inscription DESC";
            
            $rows = $wpdb->get_results($wpdb->prepare($query, ...array_merge($selected_ids, [$club_id])));
        } else {
            $rows = [];
        }
    } else {
        // Export all with filters
        $where_data = UFSC_Licence_Filters::build_where_clause($filters);
        $where_clause = $where_data['where_clause'];
        $params = $where_data['params'];

        if (!empty($params)) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT l.id, l.nom, l.prenom, l.sexe, l.date_naissance, l.email, l.region, l.ville, l.competition, l.is_included, l.date_inscription, l.adresse, l.suite_adresse, l.code_postal, l.tel_fixe, l.tel_mobile, l.profession, c.nom as club_nom
                 FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause
                 ORDER BY l.date_inscription DESC",
                ...$params
            ));
        } else {
            $rows = $wpdb->get_results(
                "SELECT l.id, l.nom, l.prenom, l.sexe, l.date_naissance, l.email, l.region, l.ville, l.competition, l.is_included, l.date_inscription, l.adresse, l.suite_adresse, l.code_postal, l.tel_fixe, l.tel_mobile, l.profession, c.nom as club_nom
                 FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause
                 ORDER BY l.date_inscription DESC"
            );
        }
    }

    // Use UFSC-compliant export
    $filename = 'licences_' . sanitize_file_name($club->nom) . '_' . date('Y-m-d') . '.csv';
    UFSC_CSV_Export::export_licenses($rows, $filename);
}

// Get filtered license data using the filter system
$license_data = UFSC_Licence_Filters::get_filtered_licenses($filters);
$data = $license_data['data'];
$total_items = $license_data['total_items'];
$per_page = $license_data['per_page'];
$current_page = $license_data['current_page'];

// 🔗 URL & nonce pour export
$base_url = remove_query_arg(['paged', 'export_csv'], wp_unslash($_SERVER['REQUEST_URI']));
$export_nonce = wp_create_nonce('ufsc_export_licences_' . $club_id);

?>

<div class="wrap">
    <h1>Licences <?php echo $club ? '– ' . esc_html($club->nom) : ''; ?></h1>
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php if ($club_id) : ?>
            <input type="hidden" name="club_id" value="<?php echo esc_attr($club_id); ?>" />
        <?php endif; ?>
        <?php $list_table->search_box(__('Recherche', 'plugin-ufsc-gestion-club-13072025'), 'licence'); ?>
        <?php $list_table->display(); ?>
    </form>
</div>
