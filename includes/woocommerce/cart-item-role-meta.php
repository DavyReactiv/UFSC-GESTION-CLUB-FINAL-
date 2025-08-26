<?php
if (!defined('ABSPATH')) exit;

/** Carry UFSC role from POST to cart item */
add_filter('woocommerce_add_cart_item_data', function($cart_item_data, $product_id, $variation_id){
    if (!empty($_POST['role'])) {
        if (!function_exists('ufsc_sanitize_role')) return $cart_item_data;
        $cart_item_data['ufsc_role'] = ufsc_sanitize_role($_POST['role']);
    }
    return $cart_item_data;
}, 10, 3);

/** Add role as order item meta for readability */
add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order){
    if (!empty($values['ufsc_role'])) {
        $roles = function_exists('ufsc_roles_allowed') ? ufsc_roles_allowed() : [];
        $label = isset($roles[$values['ufsc_role']]) ? $roles[$values['ufsc_role']] : $values['ufsc_role'];
        $item->add_meta_data(__('Rôle', 'plugin-ufsc-gestion-club-13072025'), $label);
    }
}, 10, 4);
