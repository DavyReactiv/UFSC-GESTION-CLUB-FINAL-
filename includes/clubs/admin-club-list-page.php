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

    echo '<div class="wrap ufsc-ui">';
    echo '<h1>' . esc_html__( 'Liste des clubs', 'plugin-ufsc-gestion-club-13072025' ) . '</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
    $table->search_box( __( 'Recherche', 'plugin-ufsc-gestion-club-13072025' ), 'club' );
    $table->display();
    echo '</form>';
    echo '</div>';
}
