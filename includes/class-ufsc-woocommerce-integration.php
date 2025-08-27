<?php
/**
 * UFSC WooCommerce Integration
 *
 * Handles WooCommerce order completion events for UFSC products
 * Manages affiliation and license creation from WooCommerce orders
 *
 * @package UFSC_Gestion_Club
 * @since 1.2.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class UFSC_WooCommerce_Integration
 */
class UFSC_WooCommerce_Integration {

    /**
     * Singleton instance
     *
     * @var UFSC_WooCommerce_Integration
     */
    private static $instance = null;

    /**
     * Affiliation product ID
     *
     * @var int
     */
    private $affiliation_product_id;

    /**
     * License product IDs (can be array for multiple license types)
     *
     * @var array
     */
    private $license_product_ids;

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Get singleton instance
     *
     * @return UFSC_WooCommerce_Integration
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the integration
     */
    private function init() {
        // Get product IDs from options with fallback to constants
        $this->affiliation_product_id = ufsc_get_affiliation_product_id_safe();
        $license_product_ids = get_option('ufsc_wc_license_product_ids', '2934');
        
        // Support comma-separated list of license product IDs
        $this->license_product_ids = is_array($license_product_ids) 
            ? $license_product_ids 
            : array_map('intval', explode(',', $license_product_ids));

        // Hook into WooCommerce order completion events
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'), 10, 1);
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'), 10, 1);
    }

    /**
     * Handle order status changed to completed
     *
     * @param int $order_id Order ID
     */
    public function handle_order_completed($order_id) {
        $this->process_order($order_id, 'completed');
    }

    /**
     * Handle payment completion
     *
     * @param int $order_id Order ID
     */
    public function handle_payment_complete($order_id) {
        $this->process_order($order_id, 'payment_complete');
    }

    /**
     * Process order for UFSC products
     *
     * @param int $order_id Order ID
     * @param string $trigger The trigger that called this method
     */
    public function process_order($order_id, $trigger = '') {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Prevent duplicate processing
        $processed_key = '_ufsc_processed_' . $trigger;
        if ($order->get_meta($processed_key)) {
            return;
        }

        // Mark as processed
        $order->update_meta_data($processed_key, current_time('mysql'));
        $order->save();

        // Process each item in the order
        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();

            // Check if this is an affiliation product
            if ($product_id == $this->affiliation_product_id) {
                $this->process_affiliation_item($order, $item);
            }

            // Check if this is a license product
            if (in_array($product_id, $this->license_product_ids)) {
                $this->process_license_item($order, $item, $quantity);
            }
        }
    }

    /**
     * Process affiliation item
     *
     * @param WC_Order $order Order object
     * @param WC_Order_Item_Product $item Order item
     */
    private function process_affiliation_item($order, $item) {
        $club_id = $item->get_meta('ufsc_club_id');
        
        if (!$club_id) {
            // Log warning if no club ID found
            error_log('UFSC: No club ID found in affiliation order item');
            return;
        }

        // Try to use internal function if available
        if (function_exists('ufsc_activate_affiliation')) {
            ufsc_activate_affiliation($club_id, $order->get_id());
        } else {
            // Fallback to direct processing
            $this->fallback_activate_affiliation($club_id, $order);
        }

        // ENHANCEMENT: Clean up pending affiliation data from user meta after successful processing
        $user_id = $order->get_user_id();
        if ($user_id) {
            delete_user_meta($user_id, 'ufsc_pending_affiliation_data');
        }
        
        // ADDED: Create automatic leader licenses after affiliation
        $this->create_leader_licenses($club_id, $order);
    }

    /**
     * Process license item
     *
     * @param WC_Order $order Order object
     * @param WC_Order_Item_Product $item Order item
     * @param int $quantity Number of licenses to create
     */
    private function process_license_item($order, $item, $quantity) {
        // If an existing draft licence is being paid, link it instead of creating a new one
        if (is_array($license_data) && !empty($license_data['licence_id'])) {
            $licence_id = absint($license_data['licence_id']);
            global $wpdb;
            $table = $wpdb->prefix . 'ufsc_licences';
            // Update the existing licence: mark pending validation and attach order
            $wpdb->update($table, [
                'statut'   => 'en_attente',
                'order_id' => $order->get_id(),
            ], ['id' => $licence_id], ['%s','%d'], ['%d']);
            // Expose in order item meta for traceability
            $item->add_meta_data('ufsc_existing_licence_id', $licence_id, true);
            $item->save();
            return;
        }

        $club_id = $item->get_meta('ufsc_club_id');
        $license_data = $item->get_meta('ufsc_licence_data');

        if (!$club_id) {
            error_log('UFSC: No club ID found in license order item');
            return;
        }

        // Create multiple licenses based on quantity
        for ($i = 0; $i < $quantity; $i++) {
            // Try to use internal function if available
            if (function_exists('ufsc_create_license')) {
                ufsc_create_license($club_id, $license_data, $order->get_id());
            } else {
                // Fallback to direct processing
                $this->fallback_create_license($club_id, $license_data, $order, $i);
            }
        }
    }

    /**
     * Fallback affiliation activation
     *
     * @param int $club_id Club ID
     * @param WC_Order $order Order object
     */
    private function fallback_activate_affiliation($club_id, $order) {
        // Try to get club manager
        if (class_exists('UFSC_Club_Manager')) {
            $club_manager = UFSC_Club_Manager::get_instance();
            $club = $club_manager->get_club($club_id);

            if ($club) {
                // Update club status
                $club_data = array(
                    'statut' => 'En attente de validation',
                    'date_affiliation' => current_time('mysql'),
                    'quota_licences' => 10, // Default pack of 10 licenses
                    'order_id' => $order->get_id()
                );

                $club_manager->update_club($club_id, $club_data);

                // Generate affiliation number if not exists
                if (empty($club->num_affiliation)) {
                    $year = gmdate('Y');
                    $count = $club_manager->get_club_count_for_year($year);
                    $num_affiliation = sprintf('UFSC-%s-%03d', $year, $count + 1);
                    $club_manager->update_club($club_id, array('num_affiliation' => $num_affiliation));
                }
            }
        } else {
            // Update user meta as last resort
            $user_id = $order->get_user_id();
            if ($user_id) {
                update_user_meta($user_id, 'ufsc_club_status', 'affiliated');
                update_user_meta($user_id, 'ufsc_affiliation_order_id', $order->get_id());
            }
        }
    }

    /**
     * Fallback license creation
     *
     * @param int $club_id Club ID
     * @param array $license_data License data
     * @param WC_Order $order Order object
     * @param int $index License index for multiple licenses
     */
    private function fallback_create_license($club_id, $license_data, $order, $index = 0) {
        global $wpdb;

        // Create post_type ufsc_license if it exists
        if (post_type_exists('ufsc_license')) {
            $post_data = array(
                'post_title' => sprintf('Licence %s %s', 
                    $license_data['prenom'] ?? 'Nouveau', 
                    $license_data['nom'] ?? 'Licencié'
                ),
                'post_type' => 'ufsc_license',
                'post_status' => 'draft',
                'post_author' => $order->get_user_id(),
                'meta_input' => array(
                    'club_id' => $club_id,
                    'order_id' => $order->get_id(),
                    'license_data' => $license_data,
                    'created_from_woocommerce' => true
                )
            );

            wp_insert_post($post_data);
        } else {
            // Insert directly into licenses table if it exists
            $table_name = $wpdb->prefix . 'ufsc_licences';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'club_id' => $club_id,
                        'prenom' => $license_data['prenom'] ?? '',
                        'nom' => $license_data['nom'] ?? '',
                        'email' => $license_data['email'] ?? '',
                        'role' => ($license_data['role'] ?? 'adherent'),
                        'statut' => 'en_attente',
                        'date_creation' => current_time('mysql'),
                        'order_id' => $order->get_id()
                    ),
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
                );
            } else {
                // Last resort: update user/club meta
                $user_id = $order->get_user_id();
                if ($user_id) {
                    $existing_licenses = get_user_meta($user_id, 'ufsc_licences', true) ?: array();
                    $existing_licenses[] = array(
                        'club_id' => $club_id,
                        'license_data' => $license_data,
                        'order_id' => $order->get_id(),
                        'created' => current_time('mysql')
                    );
                    update_user_meta($user_id, 'ufsc_licences', $existing_licenses);
                }
            }
        }
    }

    /**
     * Get affiliation product ID
     *
     * @return int
     */
    public function get_affiliation_product_id() {
        return $this->affiliation_product_id;
    }

    /**
     * Get license product IDs
     *
     * @return array
     */
    public function get_license_product_ids() {
        return $this->license_product_ids;
    }

    /**
     * Check if product is UFSC affiliation product
     *
     * @param int $product_id Product ID
     * @return bool
     */
    public function is_affiliation_product($product_id) {
        return $product_id == $this->affiliation_product_id;
    }

    /**
     * Check if product is UFSC license product
     *
     * @param int $product_id Product ID
     * @return bool
     */
    public function is_license_product($product_id) {
        return in_array($product_id, $this->license_product_ids);
    }
    
    /**
     * ADDED: Create automatic leader licenses after affiliation
     *
     * @param int $club_id Club ID
     * @param WC_Order $order Order object
     */
    private function create_leader_licenses($club_id, $order) {
        // Check if leaders have already been created (anti-duplication)
        $option_key = 'ufsc_leaders_created_club_' . $club_id;
        if (get_option($option_key)) {
            return; // Already created
        }
        
        // Get club data to retrieve leader information
        if (class_exists('UFSC_Club_Manager')) {
            $club_manager = UFSC_Club_Manager::get_instance();
            $club = $club_manager->get_club($club_id);
            
            if ($club) {
                $leader_roles = ['president', 'secretaire', 'tresorier'];
                $created_count = 0;
                
                foreach ($leader_roles as $role) {
                    $prenom = $club->{$role . '_prenom'} ?? '';
                    $nom = $club->{$role . '_nom'} ?? '';
                    $email = $club->{$role . '_email'} ?? '';
                    $telephone = $club->{$role . '_tel'} ?? '';
                    
                    // Only create license if we have minimum required data
                    if (!empty($prenom) && !empty($nom)) {
                        $licence_data = [
                            'club_id' => $club_id,
                            'nom' => $nom,
                            'prenom' => $prenom,
                            'email' => $email,
                            'telephone' => $telephone,
                            'fonction' => ucfirst($role),
                            'role' => $role,
                            'statut' => 'en_attente', // Use standardized status
                            'is_included' => 1, // Included in quota
                            'date_creation' => current_time('mysql'),
                            'date_expiration' => gmdate('Y-m-d', strtotime('+1 year')),
                            'type' => 'dirigeant',
                            'order_id' => $order->get_id()
                        ];
                        
                        // Try to use the licence manager
                        if (class_exists('UFSC_Licence_Manager')) {
                            $licence_manager = UFSC_Licence_Manager::get_instance();
                            $licence_id = $licence_manager->create_licence($licence_data);
                            
                            if ($licence_id) {
                                $created_count++;
                            }
                        } else {
                            // Fallback to direct database insertion
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'ufsc_licences';
                            
                            $result = $wpdb->insert($table_name, $licence_data);
                            if ($result) {
                                $created_count++;
                            }
                        }
                    }
                }
                
                // Mark as created only if at least one license was created
                if ($created_count > 0) {
                    update_option($option_key, current_time('mysql'));
                    
                    // Add note to order
                    $order->add_order_note(
                        sprintf(__('%d licences dirigeants créées automatiquement', 'plugin-ufsc-gestion-club-13072025'), $created_count)
                    );
                }
            }
        }
    }
}

// Initialize the integration
if (class_exists('WooCommerce')) {
    UFSC_WooCommerce_Integration::get_instance();
}

/** Resolve club_id for current user/order/data */
if (!function_exists('ufsc__resolve_club_id')) {
function ufsc__resolve_club_id($license_data = [], $order = null){
    $uid = get_current_user_id();
    $club_id = (int) get_user_meta($uid, 'ufsc_club_id', true);
    if (!$club_id && !empty($license_data['club_id'])) $club_id = (int) $license_data['club_id'];
    if (!$club_id && $order) {
        $club_id = (int) ( is_object($order) ? $order->get_meta('_ufsc_club_id') : 0 );
    }
    return $club_id ?: 0;
}

}
