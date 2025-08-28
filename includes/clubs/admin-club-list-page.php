<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'class-ufsc-club-list-table.php';

function ufsc_render_club_list_page() {
    wp_enqueue_style(
        'ufsc-admin-style',
        UFSC_PLUGIN_URL . 'assets/css/admin.css',
        [],
        UFSC_PLUGIN_VERSION
    );
    wp_enqueue_style(

        'ufsc-admin-licence-table-style',
        UFSC_PLUGIN_URL . 'assets/css/admin-licence-table.css',

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
        UFSC_PLUGIN_VERSION
    );
    wp_enqueue_script(
        'ufsc-admin-script',
        UFSC_PLUGIN_URL . 'assets/js/admin.js',

        ['jquery'],

        ['ufsc-datatables-config'],

        UFSC_PLUGIN_VERSION,
        true
    );

    $table = new UFSC_Club_List_Table();
    $table->prepare_items();

    // Helper to render list table with UFSC classes and accessibility tweaks.
    $render_table = static function ( $table ) {
        ob_start();
        $table->display();
        $html = ob_get_clean();

        // Replace default table classes with UFSC variants.
        $html = str_replace( 'wp-list-table widefat fixed striped', 'ufsc-table', (string) $html );
        // Apply ufsc-row to all rows for zebra styling.
        $html = preg_replace( '/<tr(?! class=)/', '<tr class="ufsc-row"', $html );

        echo $html;
        ?>
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
        <?php
    };

    echo '<div class="wrap ufsc-ui">';
    echo '<h1>' . esc_html__( 'Liste des clubs', 'plugin-ufsc-gestion-club-13072025' ) . '</h1>';

    $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';

    // Actions bar with search, filters, export and bulk actions.
    echo '<div class="ufsc-actions">';
    echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Recherche', 'plugin-ufsc-gestion-club-13072025' ) . '" />';
    echo '<select name="region"><option value="">' . esc_html__( 'Toutes r\u00e9gions', 'plugin-ufsc-gestion-club-13072025' ) . '</option></select>';
    echo '<button type="submit" class="button">' . esc_html__( 'Filtrer', 'plugin-ufsc-gestion-club-13072025' ) . '</button>';
    echo '<button type="submit" name="export" value="1" class="button">' . esc_html__( 'Exporter', 'plugin-ufsc-gestion-club-13072025' ) . '</button>';
    $bulk_actions = $table->get_bulk_actions();
    echo '<select name="action">';
    echo '<option value="">' . esc_html__( 'Actions group\u00e9es', 'plugin-ufsc-gestion-club-13072025' ) . '</option>';
    foreach ( $bulk_actions as $action => $label ) {
        echo '<option value="' . esc_attr( $action ) . '">' . esc_html( $label ) . '</option>';
    }
    echo '</select>';
    echo '<button type="submit" class="button">' . esc_html__( 'Appliquer', 'plugin-ufsc-gestion-club-13072025' ) . '</button>';
    echo '</div>';

    // Loading and error placeholders (hidden by default, can be toggled via JS if needed).
    echo '<div class="ufsc-loading-state" style="display:none"><span class="dashicons dashicons-update spin"></span> ' . esc_html__( 'Chargement...', 'plugin-ufsc-gestion-club-13072025' ) . '</div>';
    echo '<div class="ufsc-error-state" style="display:none"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Une erreur est survenue.', 'plugin-ufsc-gestion-club-13072025' ) . '</div>';

    if ( empty( $table->items ) ) {
        echo '<div class="ufsc-empty-state"><span class="dashicons dashicons-search"></span> ' . esc_html__( 'Aucun club trouv\u00e9.', 'plugin-ufsc-gestion-club-13072025' ) . '</div>';
    } else {
        $render_table( $table );
    }

    echo '</form>';
    echo '</div>';
}
