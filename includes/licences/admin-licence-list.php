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
    UFSC_GESTION_CLUB_VERSION
);

$list_table = new UFSC_Licence_List_Table($club_id);
$list_table->prepare_items();
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
