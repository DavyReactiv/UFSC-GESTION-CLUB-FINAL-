<?php
/**
 * WooCommerce Product ID Reconciliation
 * 
 * Synchronizes product ID options between different naming conventions
 * to fix mixed options causing wrong products in cart.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Synchronize WooCommerce product ID options on plugin load
 * 
 * Reconciles between:
 * - ufsc_licence_product_id ↔ ufsc_wc_license_product_ids
 * - ufsc_affiliation_product_id ↔ ufsc_wc_affiliation_product_id
 */
function ufsc_reconcile_wc_product_ids() {
    $corrections_made = [];
    
    // Reconcile licence product IDs
    $licence_id_old = get_option('ufsc_licence_product_id', 0);
    $licence_ids_new = get_option('ufsc_wc_license_product_ids', '');
    
    if ($licence_id_old && empty($licence_ids_new)) {
        // Migrate single ID to comma-separated list
        update_option('ufsc_wc_license_product_ids', (string) $licence_id_old);
        $corrections_made[] = 'ufsc_wc_license_product_ids mis à jour depuis ufsc_licence_product_id';
    } elseif (!$licence_id_old && !empty($licence_ids_new)) {
        // Extract first ID from comma-separated list
        $ids_array = array_map('intval', explode(',', $licence_ids_new));
        $first_id = reset($ids_array);
        if ($first_id) {
            update_option('ufsc_licence_product_id', $first_id);
            $corrections_made[] = 'ufsc_licence_product_id mis à jour depuis ufsc_wc_license_product_ids';
        }
    } elseif ($licence_id_old && !empty($licence_ids_new)) {
        // Ensure licence ID is present in the comma-separated list
        $ids_array = array_map('intval', explode(',', $licence_ids_new));
        if (!in_array($licence_id_old, $ids_array)) {
            $ids_array[] = $licence_id_old;
            $updated_list = implode(',', array_unique($ids_array));
            update_option('ufsc_wc_license_product_ids', $updated_list);
            $corrections_made[] = 'ufsc_licence_product_id ajouté à ufsc_wc_license_product_ids';
        }
    }
    
    // Reconcile affiliation product IDs
    $affiliation_id_old = get_option('ufsc_affiliation_product_id', 0);
    $affiliation_id_new = get_option('ufsc_wc_affiliation_product_id', 0);
    
    if ($affiliation_id_old && !$affiliation_id_new) {
        update_option('ufsc_wc_affiliation_product_id', $affiliation_id_old);
        $corrections_made[] = 'ufsc_wc_affiliation_product_id mis à jour depuis ufsc_affiliation_product_id';
    } elseif (!$affiliation_id_old && $affiliation_id_new) {
        update_option('ufsc_affiliation_product_id', $affiliation_id_new);
        $corrections_made[] = 'ufsc_affiliation_product_id mis à jour depuis ufsc_wc_affiliation_product_id';
    } elseif ($affiliation_id_old && $affiliation_id_new && $affiliation_id_old !== $affiliation_id_new) {
        // Prefer the newer option (ufsc_wc_affiliation_product_id)
        update_option('ufsc_affiliation_product_id', $affiliation_id_new);
        $corrections_made[] = 'ufsc_affiliation_product_id synchronisé avec ufsc_wc_affiliation_product_id';
    }
    
    // Show admin notice if corrections were made
    if (!empty($corrections_made)) {
        add_action('admin_notices', function() use ($corrections_made) {
            if (current_user_can('ufsc_manage')) {
                echo '<div class="notice notice-info is-dismissible">';
                echo '<p><strong>Plugin UFSC:</strong> Configuration des produits WooCommerce automatiquement synchronisée :</p>';
                echo '<ul>';
                foreach ($corrections_made as $correction) {
                    echo '<li>• ' . esc_html($correction) . '</li>';
                }
                echo '</ul>';
                echo '<p><a href="' . esc_url(admin_url('admin.php?page=ufsc-settings')) . '">Vérifier la configuration</a></p>';
                echo '</div>';
            }
        });
    }
}

/**
 * Initialize reconciliation on admin init
 */
add_action('admin_init', 'ufsc_reconcile_wc_product_ids');

/**
 * Defensive wrapper for ufsc_get_licence_product_id to ensure consistency
 * 
 * @return int Licence product ID
 */
function ufsc_get_licence_product_id_safe() {
    $licence_id = ufsc_get_licence_product_id();
    
    // If no ID found in old option, try new option
    if (!$licence_id) {
        $licence_ids = get_option('ufsc_wc_license_product_ids', '');
        if (!empty($licence_ids)) {
            $ids_array = array_map('intval', explode(',', $licence_ids));
            $licence_id = reset($ids_array);
        }
    }
    
    return (int) $licence_id;
}

/**
 * Defensive wrapper for ufsc_get_affiliation_product_id to ensure consistency
 * 
 * @return int Affiliation product ID
 */
function ufsc_get_affiliation_product_id_safe() {
    return (int) ufsc_get_affiliation_product_id();
}