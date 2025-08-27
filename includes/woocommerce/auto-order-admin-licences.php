<?php

/**
 * Auto Order for Admin Licences
 * 
 * Automatically creates WooCommerce orders for licences created in admin
 * when is_included=0 (not included in quota)
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class UFSC_Auto_Order_Admin_Licences {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Only run if setting is enabled
        if (!$this->is_auto_order_enabled()) {
            return;
        }
        
        // Hook after licence creation
        add_action('ufsc_licence_created', array($this, 'maybe_create_order_for_licence'), 10, 2);
    }
    
    /**
     * Check if auto order feature is enabled
     * 
     * @return bool
     */
    private function is_auto_order_enabled() {
        return get_option('ufsc_auto_order_for_admin_licences', true);
    }
    
    /**
     * Get individual licence product ID
     * 
     * @return int
     */
    private function get_individual_licence_product_id() {
        return get_option('ufsc_wc_individual_licence_product_id', 0);
    }
    
    /**
     * Maybe create order for licence if it's not included
     * 
     * @param int $licence_id
     * @param array $licence_data
     */
    public function maybe_create_order_for_licence($licence_id, $licence_data) {
        // Check if this is an admin-created licence (not included in quota)
        if (!isset($licence_data['is_included']) || $licence_data['is_included'] != 0) {
            return;
        }
        
        // Check if we're in admin context (not a frontend purchase)
        if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX && !$this->is_admin_ajax())) {
            return;
        }
        
        $product_id = $this->get_individual_licence_product_id();
        if (!$product_id) {
            return;
        }
        
        // Get licence data
        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        $licence_manager = new UFSC_Licence_Manager();
        $licence = $licence_manager->get_licence_by_id($licence_id);
        
        if (!$licence) {
            return;
        }
        
        // Get club data to find the responsible user
        require_once UFSC_PLUGIN_PATH . 'includes/clubs/class-club-manager.php';
        $club_manager = UFSC_Club_Manager::get_instance();
        $club = $club_manager->get_club($licence->club_id);
        
        // Create WooCommerce order
        $this->create_woocommerce_order($licence, $club, $product_id);
    }
    
    /**
     * Check if this is an admin AJAX request (not frontend)
     * 
     * @return bool
     */
    private function is_admin_ajax() {
        // Check if the AJAX request is coming from admin
        $referer = wp_get_referer();
        return $referer && strpos($referer, '/wp-admin/') !== false;
    }
    
    /**
     * Create WooCommerce order for licence
     * 
     * @param object $licence
     * @param object $club
     * @param int $product_id
     */
    private function create_woocommerce_order($licence, $club, $product_id) {
        if (!class_exists('WC_Order')) {
            return;
        }
        
        // Find customer user (club manager if available)
        $customer_id = $this->find_customer_user($club);
        
        // Create order
        $order = wc_create_order(array(
            'customer_id' => $customer_id,
            'status' => 'pending'
        ));
        
        if (is_wp_error($order)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('UFSC: Error creating order for licence ' . $licence->id . ': ' . $order->get_error_message());
            }
            return;
        }
        
        // Add product to order
        $order->add_product(wc_get_product($product_id), 1);
        
        // Add billing information from club if available
        if ($club) {
            $billing_data = array(
                'first_name' => $club->president_prenom ?? '',
                'last_name' => $club->president_nom ?? '',
                'company' => $club->nom ?? '',
                'email' => $club->president_email ?? $club->email ?? '',
                'phone' => $club->president_telephone ?? $club->telephone ?? '',
                'address_1' => $club->adresse ?? '',
                'postcode' => $club->code_postal ?? '',
                'city' => $club->ville ?? '',
                'country' => 'FR'
            );
            
            $order->set_address($billing_data, 'billing');
        }
        
        // Add metadata
        $order->add_meta_data('ufsc_licence_id', $licence->id);
        $order->add_meta_data('ufsc_club_id', $licence->club_id);
        $order->add_meta_data('ufsc_auto_created', true);
        $order->add_meta_data('ufsc_licence_name', $licence->prenom . ' ' . $licence->nom);
        
        // Add order note
        $order->add_order_note(
            sprintf(
                __('Commande créée automatiquement pour la licence %s %s (ID: %d) du club %s.', 'plugin-ufsc-gestion-club-13072025'),
                $licence->prenom,
                $licence->nom,
                $licence->id,
                $club ? $club->nom : 'ID: ' . $licence->club_id
            )
        );
        
        // Calculate totals and save
        $order->calculate_totals();
        $order->save();
        
        // Log the creation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('UFSC: Auto-created order ' . $order->get_id() . ' for licence ' . $licence->id);
        }
    }
    
    /**
     * Find customer user for the club
     * 
     * @param object $club
     * @return int
     */
    private function find_customer_user($club) {
        if (!$club) {
            return 0;
        }
        
        // Try to find user by president email
        if (!empty($club->president_email)) {
            $user = get_user_by('email', $club->president_email);
            if ($user) {
                return $user->ID;
            }
        }
        
        // Try to find user by club email
        if (!empty($club->email)) {
            $user = get_user_by('email', $club->email);
            if ($user) {
                return $user->ID;
            }
        }
        
        // Try to find user associated with the club
        $users = get_users(array(
            'meta_key' => 'ufsc_club_id',
            'meta_value' => $club->id,
            'number' => 1
        ));
        
        if (!empty($users)) {
            return $users[0]->ID;
        }
        
        return 0; // Guest order
    }
}

// Initialize only if WooCommerce is active
if (class_exists('WooCommerce')) {
    new UFSC_Auto_Order_Admin_Licences();
}