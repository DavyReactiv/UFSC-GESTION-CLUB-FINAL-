<?php
if (!defined('ABSPATH')) exit;

// Toggle quota
add_action('wp_ajax_ufscx_toggle_quota','ufscx_toggle_quota');
function ufscx_toggle_quota(){
    ufsc_check_ajax_nonce('ufscx_licences','nonce');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if (!$id) wp_send_json_error(['message'=>'ID manquant']);

    global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, club_id, is_included FROM $t WHERE id=%d", $id));
    if (!$row) wp_send_json_error(['message'=>'Licence introuvable']);
    $club_id = ufscx_resolve_club_id();
    if (!$club_id || (int)$row->club_id !== (int)$club_id){
        wp_send_json_error(['message'=>'Non autorisé'],403);
    }
    $new = $row->is_included ? 0 : 1;
    $wpdb->update($t, ['is_included'=>$new, 'date_modification'=>current_time('mysql')], ['id'=>$id]);
    wp_send_json_success(['is_included'=>$new]);
}

// Delete draft
add_action('wp_ajax_ufscx_delete_draft','ufscx_delete_draft');
function ufscx_delete_draft(){
    ufsc_check_ajax_nonce('ufscx_licences','nonce');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
    $row = $wpdb->get_row($wpdb->prepare("SELECT id, club_id, statut FROM $t WHERE id=%d", $id));
    if (!$row) wp_send_json_error(['message'=>'Licence introuvable']);
    $club_id = ufscx_resolve_club_id();
    if (!$club_id || (int)$row->club_id !== (int)$club_id){
        wp_send_json_error(['message'=>'Non autorisé'],403);
    }
    if (!in_array(strtolower($row->statut), ['brouillon','draft'], true)){
        wp_send_json_error(['message'=>'Suppression autorisée uniquement pour les brouillons.']);
    }
    $wpdb->delete($t, ['id'=>$id]);
    wp_send_json_success(['deleted'=>true]);
}
