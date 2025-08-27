<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_ufsc_include_quota', 'ufsc_ajax_include_quota');
function ufsc_ajax_include_quota(){
    $nonce = isset($_REQUEST['_ajax_nonce']) ? $_REQUEST['_ajax_nonce'] : '';
    if (!wp_verify_nonce($nonce, 'ufsc_front_nonce')) {
        wp_send_json_error(esc_html__('Bad nonce', 'ufsc-domain'), 403);
    }
    if (!is_user_logged_in() || !current_user_can('read')) {
        wp_send_json_error(esc_html__('Non connecté', 'ufsc-domain'), 401);
    }

    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    $season = isset($_POST['season']) ? sanitize_text_field($_POST['season']) : date('Y');
    $uid = get_current_user_id();
    $club_id = (int) get_user_meta($uid, 'ufsc_club_id', true);

    if (!$licence_id || !$club_id) {
        wp_send_json_error(esc_html__('Données manquantes', 'ufsc-domain'));
    }

    global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
    // Option simple quota: ufsc_quota_{club}_{season}_allowed
    $allowed = (int) get_option('ufsc_quota_'.$club_id.'_'.$season.'_allowed', 0);
    $used    = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE club_id=%d AND season=%s AND billing_source='quota' AND statut='validee'", $club_id, $season) );

    if ($allowed && $used >= $allowed) {
        wp_send_json_error(esc_html__('Quota atteint', 'ufsc-domain'));
    }

    $ok = $wpdb->update($t, [
        'statut' => 'validee',
        'billing_source' => 'quota',
        'season' => $season
    ], ['id'=>$licence_id, 'club_id'=>$club_id], ['%s','%s','%s'], ['%d','%d']);

    if ($ok !== false) {
        if (function_exists('ufsc__log_status_change')) {
            ufsc__log_status_change($licence_id, 'validee', $uid);
        }
        wp_send_json_success();
    }

    wp_send_json_error(esc_html__('Échec inclusion quota', 'ufsc-domain'));
}
