<?php

/**
 * License Status Mapping Helpers
 * 
 * Provides mapping between WooCommerce order statuses and license statuses,
 * including hooks for automatic status synchronization and status normalization.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Map WooCommerce order status to license status
 * 
 * @param string $order_status WooCommerce order status
 * @param bool $manual_validation Whether manual validation is required
 * @return string License status
 */
function ufsc_map_order_status_to_license_status($order_status, $manual_validation = false)
{
    $status_map = [
        'pending'    => 'draft',
        'on-hold'    => 'draft', 
        'processing' => $manual_validation ? 'pending' : 'validated',
        'completed'  => 'validated',
        'cancelled'  => 'refused',
        'refunded'   => 'revoked',
        'failed'     => 'refused'
    ];

    return isset($status_map[$order_status]) ? $status_map[$order_status] : 'draft';
}

/**
 * Check if manual validation is enabled
 * 
 * @return bool True if manual validation is required
 */
function ufsc_is_manual_validation_enabled()
{
    return get_option('ufsc_manual_validation', false);
}

/**
 * Get license payment status based on order
 * 
 * @param WC_Order $order WooCommerce order object
 * @return string Payment status (pending, paid, failed, refunded)
 */
function ufsc_get_license_payment_status($order)
{
    if (!$order) {
        return 'pending';
    }

    $order_status = $order->get_status();
    
    $payment_status_map = [
        'pending'    => 'pending',
        'on-hold'    => 'pending',
        'processing' => 'paid',
        'completed'  => 'paid',
        'cancelled'  => 'failed',
        'refunded'   => 'refunded',
        'failed'     => 'failed'
    ];

    return isset($payment_status_map[$order_status]) ? $payment_status_map[$order_status] : 'pending';
}

/**
 * Process license creation after WooCommerce order payment
 * 
 * @param int $order_id Order ID
 */
function ufsc_process_license_creation_after_payment($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Check if order is paid/completed
    if (!in_array($order->get_status(), ['completed', 'processing'])) {
        return;
    }

    // Check if already processed
    if ($order->get_meta('ufsc_licences_processed')) {
        return;
    }

    $license_manager = UFSC_Licence_Manager::get_instance();
    $manual_validation = ufsc_is_manual_validation_enabled();

    // Process each license item
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();

        // Check if this is a license product
        if ($product_id == ufsc_get_licence_product_id()) {
            $license_data = ufsc_extract_license_data_from_order_item($item);
            
            if ($license_data) {
                // Set initial status based on payment and validation settings
                $license_data['statut'] = ufsc_map_order_status_to_license_status(
                    $order->get_status(), 
                    $manual_validation
                );
                
                $license_data['payment_status'] = ufsc_get_license_payment_status($order);
                $license_data['order_id'] = $order_id;
                $license_data['date_creation'] = current_time('mysql');

                // Create the license
                $license_id = $license_manager->create_licence($license_data);
                
                if ($license_id) {
                    // Add license ID to order meta
                    $item->add_meta_data('ufsc_license_id', $license_id);
                    $item->save();

                    // Add note to order
                    $order->add_order_note('Licence créée: ' . $license_data['prenom'] . ' ' . $license_data['nom'] . ' (ID: ' . $license_id . ')');
                }
            }
        }
    }

    // Mark as processed
    $order->add_meta_data('ufsc_licences_processed', true);
    $order->save();
}

/**
 * Extract license data from WooCommerce order item
 * 
 * @param WC_Order_Item $item Order item
 * @return array|false License data array or false if invalid
 */
function ufsc_extract_license_data_from_order_item($item)
{
    // Try to get license data from meta (new format)
    $license_data = $item->get_meta('ufsc_licence_data');
    
    if (is_array($license_data) && !empty($license_data)) {
        return $license_data;
    }

    // Fallback to individual meta fields (old format compatibility)
    $legacy_data = [
        'nom' => $item->get_meta('ufsc_licencie_nom'),
        'prenom' => $item->get_meta('ufsc_licencie_prenom'),
        'date_naissance' => $item->get_meta('ufsc_licencie_date_naissance'),
        'email' => $item->get_meta('ufsc_licencie_email'),
        'telephone' => $item->get_meta('ufsc_licencie_telephone'),
        'adresse' => $item->get_meta('ufsc_licencie_adresse'),
        'code_postal' => $item->get_meta('ufsc_licencie_code_postal'),
        'ville' => $item->get_meta('ufsc_licencie_ville'),
        'club_id' => $item->get_meta('ufsc_club_id')
    ];

    // Check if we have minimum required data
    if (!empty($legacy_data['nom']) && !empty($legacy_data['prenom']) && !empty($legacy_data['club_id'])) {
        return array_filter($legacy_data); // Remove empty values
    }

    return false;
}

/**
 * Handle order status changes and update related licenses
 * 
 * @param int $order_id Order ID
 * @param string $old_status Old order status
 * @param string $new_status New order status
 */
function ufsc_handle_order_status_change($order_id, $old_status, $new_status)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }

    // Skip if already processed for this status change
    $last_processed_status = $order->get_meta('ufsc_last_processed_status');
    if ($last_processed_status === $new_status) {
        return;
    }

    $license_manager = UFSC_Licence_Manager::get_instance();
    $manual_validation = ufsc_is_manual_validation_enabled();

    // Update licenses for this order
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        
        // Check if this is a license product
        if ($product_id == ufsc_get_licence_product_id()) {
            $license_id = $item->get_meta('ufsc_license_id');
            
            if ($license_id) {
                $new_license_status = ufsc_map_order_status_to_license_status($new_status, $manual_validation);
                $payment_status = ufsc_get_license_payment_status($order);
                
                // Update license status
                $license_manager->update_licence_status($license_id, $new_license_status);
                
                // Update payment status in license meta if needed
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'ufsc_licences',
                    [
                        'payment_status' => $payment_status,
                        'date_modification' => current_time('mysql')
                    ],
                    ['id' => $license_id],
                    ['%s', '%s'],
                    ['%d']
                );

                // Add note to order
                $order->add_order_note('Statut licence mis à jour: ' . $new_license_status . ' (Licence ID: ' . $license_id . ')');
            }
        }
    }

    // Mark this status as processed
    $order->update_meta_data('ufsc_last_processed_status', $new_status);
    $order->save();
}

/**
 * Register WooCommerce hooks for license status synchronization
 */
function ufsc_register_license_status_hooks()
{
    // Create licenses when order is paid
    add_action('woocommerce_order_status_completed', 'ufsc_process_license_creation_after_payment');
    add_action('woocommerce_order_status_processing', 'ufsc_process_license_creation_after_payment');

    // Handle status changes
    add_action('woocommerce_order_status_changed', 'ufsc_handle_order_status_change', 10, 3);
}

// Initialize hooks
ufsc_register_license_status_hooks();

/**
 * Get payment status badge HTML
 *
 * @param string $payment_status Payment status
 * @return string HTML badge
 */
function ufsc_get_payment_badge($payment_status)
{
    $is_paid = in_array($payment_status, ['paid', 'completed'], true);
    $class   = $is_paid ? 'ufsc-badge-paid' : 'ufsc-badge-unpaid';
    $label   = $is_paid
        ? __('Payée', 'plugin-ufsc-gestion-club-13072025')
        : __('Non payée', 'plugin-ufsc-gestion-club-13072025');

    return '<span class="ufsc-badge ' . esc_attr($class) . '">' . esc_html($label) . '</span>';
}

/**
 * Get license status badge HTML
 *
 * @param string $status License status
 * @param string $payment_status Payment status (optional)
 * @return string HTML badge
 */
function ufsc_get_license_status_badge($status, $payment_status = '')
{
    $status_config = [
        'draft' => ['label' => 'Brouillon', 'class' => 'ufsc-badge-draft'],
        'pending' => ['label' => 'En attente', 'class' => 'ufsc-badge-pending'],
        'validated' => ['label' => 'Validé', 'class' => 'ufsc-badge-success'],
        'refused' => ['label' => 'Refusé', 'class' => 'ufsc-badge-error'],
        'revoked' => ['label' => 'Révoqué', 'class' => 'ufsc-badge-warning']
    ];

    $config = isset($status_config[$status]) ? $status_config[$status] : 
              ['label' => ucfirst($status), 'class' => 'ufsc-badge-default'];

    $badge = '<span class="ufsc-badge ' . esc_attr($config['class']) . '">' . esc_html($config['label']) . '</span>';

    // Add payment status as sub-badge if provided and not validated
    if (!empty($payment_status) && $status !== 'validated' && $payment_status !== 'paid') {
        $payment_config = [
            'pending' => ['label' => 'Paiement en attente', 'class' => 'ufsc-subbadge-warning'],
            'failed' => ['label' => 'Paiement échoué', 'class' => 'ufsc-subbadge-error'],
            'refunded' => ['label' => 'Remboursé', 'class' => 'ufsc-subbadge-info']
        ];

        if (isset($payment_config[$payment_status])) {
            $badge .= ' <span class="ufsc-subbadge ' . esc_attr($payment_config[$payment_status]['class']) . '">' . 
                     esc_html($payment_config[$payment_status]['label']) . '</span>';
        }
    }

    return $badge;
}

/**
 * ADDED: Status Normalization Functions for License Management
 * 
 * These functions provide consistent status handling across the application
 */

/**
 * Normalize license status to canonical form
 * 
 * Maps various status values to canonical statuses:
 * - pending -> en_attente
 * - "en attente" (with space) -> en_attente
 * - empty/null -> en_attente (default)
 * - active/validated/validée/valide -> validee
 * - refused -> refusee
 *
 * @param string $status Current status
 * @return string Normalized status
 */
function ufsc_normalize_licence_status($status) {
    // Handle null or empty status
    if (empty($status)) {
        return 'en_attente';
    }
    
    $status = trim($status);
    
    // Use mb_strtolower for proper accent handling
    if (function_exists('mb_strtolower')) {
        $status = mb_strtolower($status, 'UTF-8');
    } else {
        $status = strtolower($status);
    }
    
    $status_map = [
        'pending' => 'en_attente',
        'en attente' => 'en_attente',  // Handle space variant
        'active' => 'validee',
        'validated' => 'validee',
        'validée' => 'validee',        // Handle accented variant
        'validée' => 'validee',        // Handle uppercase accent case (É->é)
        'valide' => 'validee',         // Handle alternative variant
        'refused' => 'refusee',
        'rejected' => 'refusee'
    ];
    
    return $status_map[$status] ?? $status;
}

/**
 * Check if a license status is pending (awaiting validation)
 *
 * @param string $status License status
 * @return bool True if status is pending
 */
function ufsc_is_pending_status($status) {
    $normalized = ufsc_normalize_licence_status($status);
    return $normalized === 'en_attente';
}

/**
 * Check if a license status is validated (approved)
 *
 * @param string $status License status
 * @return bool True if status is validated
 */
function ufsc_is_validated_status($status) {
    $normalized = ufsc_normalize_licence_status($status);
    return $normalized === 'validee';
}

/**
 * Check if a license status is refused (rejected)
 *
 * @param string $status License status
 * @return bool True if status is refused
 */
function ufsc_is_refused_status($status) {
    $normalized = ufsc_normalize_licence_status($status);
    return $normalized === 'refusee';
}