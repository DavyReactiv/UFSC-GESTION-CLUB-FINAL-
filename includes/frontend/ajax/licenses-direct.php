<?php
if (!defined('ABSPATH')) exit;

// Toggle quota
add_action('wp_ajax_ufscx_toggle_quota','ufscx_toggle_quota');
function ufscx_toggle_quota(){
    if (!check_ajax_referer('ufsc_front_nonce', 'ufsc_nonce', false)) {
        wp_send_json_error(['message' => esc_html__('Bad nonce', 'ufsc-domain')], 403);
    }
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if (!$id) wp_send_json_error(['message'=>esc_html__('ID manquant', 'ufsc-domain')]);

    global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, club_id, is_included FROM $t WHERE id=%d", $id));
    if (!$row) wp_send_json_error(['message'=>esc_html__('Licence introuvable', 'ufsc-domain')]);
    if (!current_user_can('edit_post', $id)) {
        wp_send_json_error(['message' => __('Unauthorized', 'ufsc-domain')], 403);
    }
    $club_id = ufscx_resolve_club_id();
    if (!$club_id || (int)$row->club_id !== (int)$club_id){
        wp_send_json_error(['message'=>esc_html__('Non autorisé', 'ufsc-domain')],403);
    }
    $new = $row->is_included ? 0 : 1;
    $wpdb->update($t, ['is_included'=>$new, 'date_modification'=>current_time('mysql')], ['id'=>$id]);
    wp_send_json_success(['is_included'=>$new]);
}

// Delete draft
add_action('wp_ajax_ufscx_delete_draft','ufscx_delete_draft');
function ufscx_delete_draft(){
    if (!check_ajax_referer('ufsc_front_nonce', 'ufsc_nonce', false)) {
        wp_send_json_error(['message' => esc_html__('Bad nonce', 'ufsc-domain')], 403);
    }
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, club_id, statut FROM $t WHERE id=%d", $id));
    if (!$row) wp_send_json_error(['message'=>esc_html__('Licence introuvable', 'ufsc-domain')]);
    if (!current_user_can('edit_post', $id)) {
        wp_send_json_error(['message' => __('Unauthorized', 'ufsc-domain')], 403);
    }
    $club_id = ufscx_resolve_club_id();
    if (!$club_id || (int)$row->club_id !== (int)$club_id){
        wp_send_json_error(['message'=>esc_html__('Non autorisé', 'ufsc-domain')],403);
    }
    if (!in_array(strtolower($row->statut), ['brouillon','draft','en_attente'], true)){
        wp_send_json_error(['message'=>esc_html__('Suppression autorisée uniquement pour les brouillons ou en attente.', 'ufsc-domain')]);
    }
    $wpdb->delete($t, ['id'=>$id]);
    wp_send_json_success(['deleted'=>true]);
}
