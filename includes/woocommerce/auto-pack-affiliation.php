<?php

/**
 * Auto Pack Affiliation
 * 
 * Automatically adds Pack 10 licences when Affiliation product is added to cart
 * and manages the relationship between these products.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class UFSC_Auto_Pack_Affiliation {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WooCommerce hooks
     */
    private function init_hooks() {
        // Only run if setting is enabled
        if (!$this->is_auto_pack_enabled()) {
            return;
        }
        
        // Hook when product is added to cart
        add_action('woocommerce_add_to_cart', array($this, 'maybe_add_pack_to_cart'), 10, 6);
        
        // Hook when cart item is removed
        add_action('woocommerce_cart_item_removed', array($this, 'maybe_remove_pack_from_cart'), 10, 2);
        
        // Safety net - check before calculating totals
        add_action('woocommerce_before_calculate_totals', array($this, 'ensure_pack_presence'), 10, 1);
    }
    
    /**
     * Check if auto pack feature is enabled
     * 
     * @return bool
     */
    private function is_auto_pack_enabled() {
        return get_option('ufsc_auto_pack_enabled', true);
    }
    
    /**
     * Get affiliation product ID
     * 
     * @return int
     */
    private function get_affiliation_product_id() {
        return ufsc_get_affiliation_product_id_safe();
    }
    
    /**
     * Get Pack 10 licences product ID
     * 
     * @return int
     */
    private function get_pack_10_product_id() {
        return get_option('ufsc_wc_pack_10_product_id', 0);
    }
    
    /**
     * Maybe add pack to cart when affiliation is added
     * 
     * @param string $cart_item_key
     * @param int $product_id
     * @param int $quantity
     * @param int $variation_id
     * @param array $variation
     * @param array $cart_item_data
     */
    public function maybe_add_pack_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        // Check if this is the affiliation product
        if ($product_id != $this->get_affiliation_product_id()) {
            return;
        }
        
        $pack_product_id = $this->get_pack_10_product_id();
        if (!$pack_product_id) {
            return;
        }
        
        // Check if pack is already in cart
        if ($this->is_pack_in_cart()) {
            return;
        }
        
        // Add pack to cart with link metadata
        $pack_cart_item_data = array(
            'ufsc_linked_to_affiliation' => $cart_item_key,
            'ufsc_auto_added_pack' => true
        );
        
        WC()->cart->add_to_cart($pack_product_id, 1, 0, array(), $pack_cart_item_data);
        
        // Add notice
        wc_add_notice(__('Pack 10 licences ajouté automatiquement avec votre affiliation.', 'plugin-ufsc-gestion-club-13072025'), 'notice');
    }
    
    /**
     * Maybe remove pack when affiliation is removed
     * 
     * @param string $cart_item_key
     * @param WC_Cart $cart
     */
    public function maybe_remove_pack_from_cart($cart_item_key, $cart) {
        $removed_item = $cart->removed_cart_contents[$cart_item_key] ?? null;
        
        if (!$removed_item) {
            return;
        }
        
        // Check if removed item was affiliation
        if ($removed_item['product_id'] != $this->get_affiliation_product_id()) {
            return;
        }
        
        // Find and remove linked pack
        foreach ($cart->get_cart() as $pack_cart_item_key => $pack_cart_item) {
            if (isset($pack_cart_item['ufsc_linked_to_affiliation']) 
                && $pack_cart_item['ufsc_linked_to_affiliation'] === $cart_item_key) {
                $cart->remove_cart_item($pack_cart_item_key);
                wc_add_notice(__('Pack 10 licences retiré car l\'affiliation a été supprimée.', 'plugin-ufsc-gestion-club-13072025'), 'notice');
                break;
            }
        }
    }
    
    /**
     * Safety net - ensure pack is present when affiliation is in cart
     * 
     * @param WC_Cart $cart
     */
    public function ensure_pack_presence($cart) {
        // Avoid infinite loops
        if (did_action('woocommerce_before_calculate_totals') > 1) {
            return;
        }
        
        $has_affiliation = false;
        $has_pack = false;
        $affiliation_cart_item_key = null;
        
        $affiliation_product_id = $this->get_affiliation_product_id();
        $pack_product_id = $this->get_pack_10_product_id();
        
        if (!$pack_product_id) {
            return;
        }
        
        // Check cart contents
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if ($cart_item['product_id'] == $affiliation_product_id) {
                $has_affiliation = true;
                $affiliation_cart_item_key = $cart_item_key;
            }
            
            if ($cart_item['product_id'] == $pack_product_id) {
                $has_pack = true;
            }
        }
        
        // If we have affiliation but no pack, add it
        if ($has_affiliation && !$has_pack) {
            $pack_cart_item_data = array(
                'ufsc_linked_to_affiliation' => $affiliation_cart_item_key,
                'ufsc_auto_added_pack' => true
            );
            
            $cart->add_to_cart($pack_product_id, 1, 0, array(), $pack_cart_item_data);
        }
    }
    
    /**
     * Check if pack is already in cart
     * 
     * @return bool
     */
    private function is_pack_in_cart() {
        $pack_product_id = $this->get_pack_10_product_id();
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($cart_item['product_id'] == $pack_product_id) {
                return true;
            }
        }
        
        return false;
    }
}

// Initialize only if WooCommerce is active
if (class_exists('WooCommerce')) {
    new UFSC_Auto_Pack_Affiliation();
}