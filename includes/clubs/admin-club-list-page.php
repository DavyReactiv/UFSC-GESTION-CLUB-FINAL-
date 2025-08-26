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
        UFSC_GESTION_CLUB_VERSION
    );
    wp_enqueue_style(
        'ufsc-admin-licence-table-style',
        UFSC_PLUGIN_URL . 'assets/css/admin-licence-table.css',
        [],
        UFSC_GESTION_CLUB_VERSION
    );
    wp_enqueue_script(
        'ufsc-admin-script',
        UFSC_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery'],
        UFSC_GESTION_CLUB_VERSION,
        true
    );

    $table = new UFSC_Club_List_Table();
    $table->prepare_items();

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Liste des clubs', 'plugin-ufsc-gestion-club-13072025' ) . '</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="' . esc_attr( $_REQUEST['page'] ) . '" />';
    $table->search_box( __( 'Recherche', 'plugin-ufsc-gestion-club-13072025' ), 'club' );
    $table->display();
    echo '</form>';
    echo '</div>';
}
