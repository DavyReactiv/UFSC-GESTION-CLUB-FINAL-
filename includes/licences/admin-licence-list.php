<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-ufsc-licence-list-table.php';
require_once plugin_dir_path(__FILE__) . '../helpers.php';
require_once plugin_dir_path(__FILE__) . '../helpers/helpers-licence-status.php';

require_once plugin_dir_path(__FILE__) . 'class-licence-filters.php';

require_once plugin_dir_path(__FILE__) . '../repository/class-licence-repository.php';


global $wpdb;

$club_id = isset($club_id) ? (int) $club_id : (isset($_GET['club_id']) ? intval(wp_unslash($_GET['club_id'])) : 0);
if ($club_id && !isset($_GET['club_id'])) {
    // Keep query parameters consistent for downstream forms
    $_GET['club_id'] = $club_id;
}
$club = null;
if ($club_id) {
    $club = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d", $club_id));
    if (!$club) {
        echo '<div class="notice notice-error"><p>Club introuvable.</p></div>';
        return;
    }
}

wp_enqueue_style(
    'ufsc-admin-licence-table-style',
    UFSC_PLUGIN_URL . 'assets/css/admin-licence-table.css',
    [],
    UFSC_PLUGIN_VERSION
);


// List table will be instantiated after fetching data

// Enqueue DataTables CSS and JS with local fallback
$dt_base_url  = UFSC_PLUGIN_URL . 'assets/datatables/';
$dt_base_path = dirname(__DIR__, 2) . '/assets/datatables/';

$dt_styles = [
    'datatables-css'            => ['css/jquery.dataTables.min.css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', '1.13.6'],
    'datatables-buttons-css'    => ['css/buttons.dataTables.min.css', 'https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css', '2.4.2'],
    'datatables-responsive-css' => ['css/responsive.dataTables.min.css', 'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css', '2.5.0'],
];

foreach ($dt_styles as $handle => $data) {
    [$rel_path, $cdn, $ver] = $data;
    $src = file_exists($dt_base_path . $rel_path) ? $dt_base_url . $rel_path : $cdn;
    wp_enqueue_style($handle, $src, [], $ver);
}

$dt_scripts = [
    'datatables-js'               => ['js/jquery.dataTables.min.js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], '1.13.6'],
    'datatables-buttons-js'       => ['js/dataTables.buttons.min.js', 'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js', ['jquery', 'datatables-js'], '2.4.2'],
    'datatables-buttons-html5-js' => ['js/buttons.html5.min.js', 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js', ['jquery', 'datatables-buttons-js'], '2.4.2'],
    'datatables-responsive-js'    => ['js/dataTables.responsive.min.js', 'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js', ['jquery', 'datatables-js'], '2.5.0'],
    'jszip-js'                    => ['js/jszip.min.js', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', [], '3.10.1'],
];

foreach ($dt_scripts as $handle => $data) {
    [$rel_path, $cdn, $deps, $ver] = $data;
    $src = file_exists($dt_base_path . $rel_path) ? $dt_base_url . $rel_path : $cdn;
    wp_enqueue_script($handle, $src, $deps, $ver, true);
}

wp_enqueue_script(
    'ufsc-datatables-config',
    UFSC_PLUGIN_URL . 'assets/js/datatables-config.js',
    ['datatables-js', 'datatables-buttons-js', 'datatables-responsive-js'],
    UFSC_PLUGIN_VERSION,
    true
);

// Get filter parameters with club_id override, allowing precomputed filters
$filters = isset($filters) ? $filters : UFSC_Licence_Filters::get_filter_parameters(['club_id' => $club_id]);

// Retrieve licence data via repository
$repo = new UFSC_Licence_Repository();
$search_keyword = $filters['search_global'] ?: $filters['search'];
$result = $repo->search([
    'club_id' => $filters['club_id'] ?: $filters['filter_club'],
    'region'  => $filters['region'],
    'statut'  => !empty($filters['statuses']) ? $filters['statuses'][0] : '',
    'keyword' => $search_keyword,
    'page'    => $filters['page'],
    'per_page'=> $filters['per_page'],
]);

$license_data = is_wp_error($result) ? [
    'data' => [],
    'total' => 0,
    'per_page' => $filters['per_page'],
] : [
    'data' => $result['items'],
    'total' => $result['total'],
    'per_page' => $result['per_page'],
];

$no_license_notice = '';
if (empty($license_data['data'])) {
    $active_filters = $filters;
    unset($active_filters['club_id'], $active_filters['page'], $active_filters['per_page']);
    $active_filters = array_filter(
        $active_filters,
        function ($value) {
            return $value !== '' && $value !== 0 && $value !== '0';
        }
    );

    $filters_info = '';
    if (!empty($active_filters)) {
        $pairs = [];
        foreach ($active_filters as $key => $value) {
            $pairs[] = esc_html($key . '=' . $value);
        }
        $filters_info = ' ' . esc_html__('Filtres actifs:', 'plugin-ufsc-gestion-club-13072025') . ' ' . implode(', ', $pairs);
    }

    $no_license_notice = '<div class="notice notice-warning"><p>' .
        sprintf(
            esc_html__('Aucune licence trouvée pour ce club (ID %d).', 'plugin-ufsc-gestion-club-13072025'),
            $club_id
        ) .
        $filters_info .
        '</p></div>';
}

$list_table = new UFSC_Licence_List_Table($club_id);
$list_table->set_external_data($license_data['data'], $license_data['total'], $license_data['per_page']);
$list_table->prepare_items();

// 📤 Export CSV
if (isset($_GET['export_csv']) && check_admin_referer('ufsc_export_licences_' . $club_id)) {
    // Check if this is a selected export or full export
    if (isset($_GET['export_selected']) && !empty($_GET['selected_ids'])) {
        // Export only selected licences
        $selected_ids = explode(',', sanitize_text_field( wp_unslash( $_GET['selected_ids'] ) ));
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

// 🔗 URL & nonce pour export
$base_url = remove_query_arg(['paged', 'export_csv'], wp_unslash($_SERVER['REQUEST_URI']));
$export_nonce = wp_create_nonce('ufsc_export_licences_' . $club_id);


?>

<div class="wrap ufsc-ui">
    <h1>Licences <?php echo $club ? '– ' . esc_html($club->nom) : ''; ?></h1>
    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
        <?php if ($club_id) : ?>
            <input type="hidden" name="club_id" value="<?php echo esc_attr($club_id); ?>" />
        <?php endif; ?>

        <?php
        // Build actions bar.
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        echo '<div class="ufsc-actions">';
        echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Recherche', 'plugin-ufsc-gestion-club-13072025' ) . '" />';
        echo '<select name="statut"><option value="">' . esc_html__( 'Tous statuts', 'plugin-ufsc-gestion-club-13072025' ) . '</option></select>';
        echo '<button type="submit" class="button">' . esc_html__( 'Filtrer', 'plugin-ufsc-gestion-club-13072025' ) . '</button>';
        echo '<button type="submit" name="export_csv" value="1" class="button">' . esc_html__( 'Exporter', 'plugin-ufsc-gestion-club-13072025' ) . '</button>';
        $bulk_actions = $list_table->get_bulk_actions();
        echo '<select name="action">';
        echo '<option value="">' . esc_html__( 'Actions group\u00e9es', 'plugin-ufsc-gestion-club-13072025' ) . '</option>';
        foreach ( $bulk_actions as $action => $label ) {
            echo '<option value="' . esc_attr( $action ) . '">' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<button type="submit" class="button">' . esc_html__( 'Appliquer', 'plugin-ufsc-gestion-club-13072025' ) . '</button>';
        echo '</div>';

        // Loading, error and empty states.
        echo '<div class="ufsc-loading-state" style="display:none"><span class="dashicons dashicons-update spin"></span> ' . esc_html__( 'Chargement...', 'plugin-ufsc-gestion-club-13072025' ) . '</div>';
        echo '<div class="ufsc-error-state" style="display:none"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Une erreur est survenue.', 'plugin-ufsc-gestion-club-13072025' ) . '</div>';

        if ( empty( $list_table->items ) ) {
            echo '<div class="ufsc-empty-state"><span class="dashicons dashicons-search"></span> ' . esc_html__( 'Aucune licence trouv\u00e9e.', 'plugin-ufsc-gestion-club-13072025' ) . '</div>';
        } else {
            ob_start();
            $list_table->display();
            $table_html = ob_get_clean();
            $table_html = str_replace( 'wp-list-table widefat fixed striped', 'ufsc-table', $table_html );
            $table_html = preg_replace( '/<tr(?![^>]*class=)/', '<tr class="ufsc-row" ', $table_html );
            echo $table_html;
        }

        ?>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.ufsc-table th.sortable').forEach(function (th) {
        if (!th.hasAttribute('aria-sort')) {
            th.setAttribute('aria-sort', 'none');
        }
        th.tabIndex = 0;
        th.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var link = th.querySelector('a');
                if (link) { link.click(); }
            }
        });
    });
});
</script>
