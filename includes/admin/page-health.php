<?php
if (!defined('ABSPATH')) exit;
function ufsc_admin_health_page(){
    if (!current_user_can('manage_options')) return;
    $pid1 = absint(get_option('ufsc_wc_individual_licence_product_id', 0));
    $pids_csv = get_option('ufsc_wc_license_product_ids', '');
    $pid = $pid1 ?: (int) (array_filter(array_map('absint', array_map('trim', explode(',', $pids_csv))))[0] ?? 0);
    echo '<div class="wrap ufsc-ui"><h1>UFSC — Santé du module</h1><table class="widefat striped">';
    echo '<tr><th>WooCommerce</th><td>'.(class_exists('WooCommerce')?'OK':'Manquant').'</td></tr>';
    echo '<tr><th>Produit Licence</th><td>'.($pid?('OK (#'.$pid.')'):'Manquant').'</td></tr>';
    echo '<tr><th>CRON</th><td>'.(wp_next_scheduled('ufsc_daily_cron')?'OK':'Non planifié').'</td></tr>';
    echo '</table><p><a class="button button-primary" href="'.esc_url(admin_url('admin.php?page=ufsc-pack-exports')).'">Réglages & Exports</a></p></div>';
}
add_action('admin_menu', function(){
    add_submenu_page('ufsc', 'Santé du module', 'Santé du module', 'manage_options', 'ufsc-health', 'ufsc_admin_health_page');
});
add_action('admin_notices', function(){
    if (!current_user_can('manage_options')) return;
    $pid1 = absint(get_option('ufsc_wc_individual_licence_product_id', 0));
    $pids_csv = get_option('ufsc_wc_license_product_ids', '');
    $pid = $pid1 ?: (int) (array_filter(array_map('absint', array_map('trim', explode(',', $pids_csv))))[0] ?? 0);
    if (!$pid){ echo '<div class="notice notice-warning"><p><strong>UFSC :</strong> Produit Licence non configuré. <a href="'.esc_url(admin_url('admin.php?page=ufsc-pack-exports')).'">Ouvrir les réglages</a>.</p></div>'; }
});
