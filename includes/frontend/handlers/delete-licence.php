<?php
if (!defined('ABSPATH')) exit;

function ufsc_delete_licence() {
    if (!is_user_logged_in() || !current_user_can('ufsc_manage_own')) {
        wp_die(__('Accès refusé.', 'plugin-ufsc-gestion-club-13072025'));
    }

    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if (!$licence_id) {
        wp_die(__('Licence invalide.', 'plugin-ufsc-gestion-club-13072025'));
    }

    check_admin_referer('ufsc_delete_licence_' . $licence_id);

    global $wpdb;
    $table    = $wpdb->prefix . 'ufsc_licences';
    $club_rel = $wpdb->prefix . 'ufsc_user_clubs';

    $club_id       = (int) $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$club_rel} WHERE user_id=%d LIMIT 1", get_current_user_id()));
    $licence_club  = (int) $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$table} WHERE id=%d", $licence_id));

    if (!$club_id || $club_id !== $licence_club) {
        wp_die(__('Licence introuvable ou accès refusé.', 'plugin-ufsc-gestion-club-13072025'));
    }

    $wpdb->delete($table, ['id' => $licence_id], ['%d']);

    $redirect = remove_query_arg(['licence_id', '_wpnonce'], wp_get_referer() ?: home_url('/'));
    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_nopriv_ufsc_delete_licence', 'ufsc_delete_licence');
add_action('admin_post_ufsc_delete_licence', 'ufsc_delete_licence');
