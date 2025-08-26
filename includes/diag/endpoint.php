<?php
if (!defined('ABSPATH')) exit;

/**
 * Mini endpoint de diagnostic: /?ufsc_diag=1
 * Désactivé par défaut; l'activer via define('UFSC_ENABLE_DIAG_ENDPOINT', true);
 */
add_action('init', function(){
    if ( ! ( defined('UFSC_ENABLE_DIAG_ENDPOINT') && UFSC_ENABLE_DIAG_ENDPOINT ) ) return;
    if ( ! isset($_GET['ufsc_diag']) ) return;
    if ( ! current_user_can('manage_options') ) return;

    global $wpdb;
    $uid = get_current_user_id();
    $club_id = (int) get_user_meta($uid,'ufsc_club_id',true);
    $t = $wpdb->prefix.'ufsc_licences';
    $counts = $wpdb->get_results( $wpdb->prepare("SELECT statut, COUNT(*) c FROM {$t} WHERE club_id=%d GROUP BY statut", $club_id), ARRAY_A );
    header('Content-Type: text/plain; charset=utf-8');
    echo "UFSC DIAG\n";
    echo "User ID: {$uid}\nClub ID: {$club_id}\n";
    echo "By statut:\n";
    foreach($counts as $row){ echo ($row['statut']?:'NULL').": {$row['c']}\n"; }
    exit;
});
