<?php
if (!defined('ABSPATH')) exit;
function ufsc_profix_ajax_add_to_cart(){
    check_ajax_referer('ufsc_front_nonce');
    if (!class_exists('WC')) wp_send_json_error(__('WooCommerce requis','plugin-ufsc-gestion-club-13072025'),400);
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    if (!$product_id) {
        // fallback to defined constant
        $product_id = defined('UFSC_LICENCE_PRODUCT_ID') ? (int)UFSC_LICENCE_PRODUCT_ID : 0;
    }
    if (!$product_id) wp_send_json_error('product_id requis');
    $qty = isset($_POST['quantity']) ? max(1, absint($_POST['quantity'])) : 1;
    $added = WC()->cart->add_to_cart($product_id, $qty);
    if (!$added) wp_send_json_error('Ajout au panier impossible');
    $data = [
        'redirect' => function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/panier/')
    ];
    wp_send_json_success($data);
}
add_action('wp_ajax_ufsc_add_to_cart','ufsc_profix_ajax_add_to_cart');
add_action('wp_ajax_nopriv_ufsc_add_to_cart','ufsc_profix_ajax_add_to_cart');
function ufsc_profix_ajax_delete_draft(){
    check_ajax_referer('ufsc_front_nonce'); global $wpdb;
    $id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0; if(!$id) wp_send_json_error('ID manquant');
    $table = $wpdb->prefix.'ufsc_licences';
    $ok = $wpdb->update($table, ['statut'=>'trash','deleted_at'=>current_time('mysql')], ['id'=>$id], ['%s','%s'], ['%d']);
    if ($ok!==false) { wp_send_json_success(); } else { wp_send_json_error('Suppression impossible'); }
}
add_action('wp_ajax_ufsc_delete_licence_draft','ufsc_profix_ajax_delete_draft');
add_action('wp_ajax_nopriv_ufsc_delete_licence_draft','ufsc_profix_ajax_delete_draft');