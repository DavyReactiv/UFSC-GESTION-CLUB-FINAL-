<?php

/**
 * Intégration WooCommerce pour les affiliations de club
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constantes pour les produits WooCommerce
 */
define('UFSC_AFFILIATION_PRODUCT_ID', 4823); // ID du produit affiliation
if (!defined('UFSC_LICENCE_PRODUCT_ID')) {
    define('UFSC_LICENCE_PRODUCT_ID', 2934); // ID du produit licence
}


/**
 * Personnaliser le titre du produit dans le panier
 */
function ufsc_customize_affiliation_cart_item_name($title, $cart_item, $cart_item_key)
{
    if (isset($cart_item['ufsc_product_type']) && $cart_item['ufsc_product_type'] === 'affiliation' && isset($cart_item['ufsc_club_nom'])) {
        $title = sprintf(
            'Pack Affiliation Club - %s',
            $cart_item['ufsc_club_nom']
        );
    }

    return $title;
}
add_filter('woocommerce_cart_item_name', 'ufsc_customize_affiliation_cart_item_name', 10, 3);

/**
 * Afficher les informations de l'affiliation dans le panier et lors du checkout
 */
function ufsc_display_affiliation_data_in_cart($item_data, $cart_item)
{
    if (isset($cart_item['ufsc_product_type']) && $cart_item['ufsc_product_type'] === 'affiliation') {
        $item_data[] = [
            'key'   => 'Contenu du pack',
            'value' => 'Affiliation du club pour 1 an + 10 licences incluses'
        ];
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'ufsc_display_affiliation_data_in_cart', 10, 2);


/**
 * Sauvegarder les données d'affiliation dans les métadonnées de la commande
 */
function ufsc_add_affiliation_data_to_order_items($item, $cart_item_key, $values, $order)
{
    if (isset($values['ufsc_club_id'])) {
        $item->update_meta_data('ufsc_club_id', $values['ufsc_club_id']);
    }

    if (isset($values['ufsc_club_nom'])) {
        $item->update_meta_data('ufsc_club_nom', $values['ufsc_club_nom']);
    }

    if (isset($values['ufsc_product_type'])) {
        $item->update_meta_data('ufsc_product_type', $values['ufsc_product_type']);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'ufsc_add_affiliation_data_to_order_items', 10, 4);
