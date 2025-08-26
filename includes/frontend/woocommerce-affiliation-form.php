<?php

/**
 * WooCommerce Affiliation Form Integration
 * 
 * Integrates the affiliation form directly into WooCommerce product pages
 * and handles cart operations for affiliation products.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the form renderer
require_once plugin_dir_path(__FILE__) . 'forms/affiliation-form-render.php';

/**
 * Add affiliation form to WooCommerce product page
 */
function ufsc_add_affiliation_form_to_product_page()
{
    global $product;

    // Check if this is the affiliation product
    if (!$product || $product->get_id() != ufsc_get_affiliation_product_id()) {
        return;
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        echo '<div class="ufsc-alert ufsc-alert-error">
              <p>Vous devez être <a href="' . esc_url(wp_login_url(get_permalink())) . '">connecté</a> pour procéder à une affiliation.</p>
              </div>';
        return;
    }

    // Render the affiliation form
    echo ufsc_render_affiliation_form([
        'context' => 'woocommerce',
        'show_title' => true,
        'submit_button_text' => 'Ajouter au panier'
    ]);
}
add_action('woocommerce_before_add_to_cart_button', 'ufsc_add_affiliation_form_to_product_page');

/**
 * Handle affiliation form submission via AJAX
 */
function ufsc_handle_affiliation_form_submission()
{
    // Verify nonce
    if (!wp_verify_nonce($_POST['_ufsc_affiliation_nonce'] ?? '', 'ufsc_affiliation_nonce')) {
        wp_send_json_error('Erreur de sécurité.');
        return;
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('Vous devez être connecté.');
        return;
    }

    // Check if WooCommerce is active
    if (!function_exists('WC')) {
        wp_send_json_error('WooCommerce n\'est pas disponible.');
        return;
    }

    $user_id = get_current_user_id();
    $is_renewal = isset($_POST['is_renewal']) && $_POST['is_renewal'] === '1';
    $existing_club_id = isset($_POST['existing_club_id']) ? intval($_POST['existing_club_id']) : 0;

    // Check for existing affiliation in cart
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (isset($cart_item['product_id']) && $cart_item['product_id'] == ufsc_get_affiliation_product_id()) {
            wp_send_json_error('Une demande d\'affiliation est déjà dans votre panier.');
            return;
        }
    }

    // Sanitize and validate form data
    $club_data = [
        'nom' => sanitize_text_field($_POST['nom'] ?? ''),
        'description' => sanitize_textarea_field($_POST['description'] ?? ''),
        'adresse' => sanitize_text_field($_POST['adresse'] ?? ''),
        'code_postal' => sanitize_text_field($_POST['code_postal'] ?? ''),
        'ville' => sanitize_text_field($_POST['ville'] ?? ''),
        'region' => sanitize_text_field($_POST['region'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'telephone' => sanitize_text_field($_POST['telephone'] ?? ''),
        'site_web' => esc_url_raw($_POST['site_web'] ?? ''),
        'siret' => sanitize_text_field($_POST['siret'] ?? ''),
        'num_rna' => sanitize_text_field($_POST['num_rna'] ?? ''),
        'activites' => sanitize_textarea_field($_POST['activites'] ?? ''),
        'responsable_id' => $user_id,
        'is_renewal' => $is_renewal,
        'existing_club_id' => $existing_club_id
    ];

    // Validate required fields
    $required_fields = ['nom', 'adresse', 'code_postal', 'ville', 'region', 'email'];
    foreach ($required_fields as $field) {
        if (empty($club_data[$field])) {
            wp_send_json_error('Veuillez remplir tous les champs obligatoires.');
            return;
        }
    }

    // Validate terms acceptance
    if (!isset($_POST['accept_terms']) || !isset($_POST['accept_data'])) {
        wp_send_json_error('Vous devez accepter les conditions générales et la politique de confidentialité.');
        return;
    }

    // Store club data in session for cart processing
    WC()->session->set('ufsc_pending_affiliation_data', $club_data);

    // ENHANCEMENT: Also persist pending affiliation data to user meta 
    // This allows dashboard to show "affiliation in progress" even if session expires
    update_user_meta($user_id, 'ufsc_pending_affiliation_data', $club_data);

    // Generate unique key and prevent duplicates
    $unique_key = ufsc_generate_licence_key($club_data);

    if (ufsc_cart_contains_licence($unique_key)) {
        wp_send_json_error('Une demande similaire est déjà dans votre panier.');
        return;
    }

    // Add to cart
    $cart_item_data = [
        'ufsc_product_type' => 'affiliation',
        'ufsc_club_nom' => $club_data['nom'],
        'ufsc_is_renewal' => $is_renewal,
        'unique_key' => $unique_key
    ];

    $added = WC()->cart->add_to_cart(ufsc_get_affiliation_product_id(), 1, 0, [], $cart_item_data);

    if ($added) {
        wp_send_json_success([
            'message' => 'Affiliation ajoutée au panier avec succès.',
            'cart_url' => wc_get_cart_url()
        ]);
    } else {
        wp_send_json_error('Erreur lors de l\'ajout au panier.');
    }
}
add_action('wp_ajax_ufsc_add_affiliation_to_cart', 'ufsc_handle_affiliation_form_submission');

/**
 * Add affiliation data from session to cart item
 */
function ufsc_add_affiliation_data_from_session($cart_item_data, $product_id, $variation_id)
{
    if ($product_id == ufsc_get_affiliation_product_id()) {
        $affiliation_data = WC()->session->get('ufsc_pending_affiliation_data');
        
        if ($affiliation_data && is_array($affiliation_data)) {
            $cart_item_data['ufsc_affiliation_data'] = $affiliation_data;
            $cart_item_data['ufsc_product_type'] = 'affiliation';
            
            // Clear session data
            WC()->session->__unset('ufsc_pending_affiliation_data');
            
            // Generate deterministic unique key based on club data
            $cart_item_data['unique_key'] = ufsc_generate_licence_key($affiliation_data);
        }
    }
    
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'ufsc_add_affiliation_data_from_session', 5, 3);

/**
 * Display affiliation data in cart
 */
function ufsc_display_affiliation_cart_data($item_data, $cart_item)
{
    if (isset($cart_item['ufsc_product_type']) && $cart_item['ufsc_product_type'] === 'affiliation') {
        if (isset($cart_item['ufsc_affiliation_data']['nom'])) {
            $item_data[] = [
                'key'   => 'Club',
                'value' => $cart_item['ufsc_affiliation_data']['nom']
            ];
        }
        
        if (isset($cart_item['ufsc_is_renewal']) && $cart_item['ufsc_is_renewal']) {
            $item_data[] = [
                'key'   => 'Type',
                'value' => 'Renouvellement'
            ];
        }
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'ufsc_display_affiliation_cart_data', 10, 2);

/**
 * Save affiliation data to order line items
 */
function ufsc_save_affiliation_data_to_order_items($item, $cart_item_key, $values, $order)
{
    if (isset($values['ufsc_affiliation_data'])) {
        foreach ($values['ufsc_affiliation_data'] as $key => $value) {
            if (!empty($value)) {
                $item->add_meta_data('ufsc_' . $key, $value);
            }
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'ufsc_save_affiliation_data_to_order_items', 10, 4);

/**
 * Process affiliation after payment
 */
function ufsc_process_affiliation_after_payment($order_id)
{
    $order = wc_get_order($order_id);

    // Check if order is paid/completed
    if (!$order || !in_array($order->get_status(), ['completed', 'processing'])) {
        return;
    }

    // Process each affiliation item
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();

        // Check if this is an affiliation
        if ($product_id == ufsc_get_affiliation_product_id()) {
            ufsc_create_or_update_club_from_order_item($item, $order);
        }
    }
}
add_action('woocommerce_order_status_completed', 'ufsc_process_affiliation_after_payment');
add_action('woocommerce_order_status_processing', 'ufsc_process_affiliation_after_payment');

/**
 * Create or update club from order item
 */
function ufsc_create_or_update_club_from_order_item($item, $order)
{
    $club_manager = UFSC_Club_Manager::get_instance();
    
    // Get affiliation data from order item meta
    $club_data = [
        'nom' => $item->get_meta('ufsc_nom'),
        'description' => $item->get_meta('ufsc_description'),
        'adresse' => $item->get_meta('ufsc_adresse'),
        'code_postal' => $item->get_meta('ufsc_code_postal'),
        'ville' => $item->get_meta('ufsc_ville'),
        'region' => $item->get_meta('ufsc_region'),
        'email' => $item->get_meta('ufsc_email'),
        'telephone' => $item->get_meta('ufsc_telephone'),
        'site_web' => $item->get_meta('ufsc_site_web'),
        'siret' => $item->get_meta('ufsc_siret'),
        'num_rna' => $item->get_meta('ufsc_num_rna'),
        'activites' => $item->get_meta('ufsc_activites'),
        'responsable_id' => $item->get_meta('ufsc_responsable_id'),
        'statut' => 'En attente de validation',
        'date_affiliation' => current_time('mysql')
    ];

    $is_renewal = $item->get_meta('ufsc_is_renewal');
    $existing_club_id = $item->get_meta('ufsc_existing_club_id');

    if ($is_renewal && $existing_club_id) {
        // Update existing club
        $club_data['id'] = $existing_club_id;
        $success = $club_manager->update_club($existing_club_id, $club_data);
        
        if ($success) {
            // Add note to order
            $order->add_order_note('Renouvellement d\'affiliation traité pour le club: ' . $club_data['nom']);
        }
    } else {
        // Create new club
        $club_id = $club_manager->create_club($club_data);
        
        if ($club_id) {
            // Add club ID to order meta
            $order->add_meta_data('ufsc_club_id', $club_id);
            $order->save();
            
            // Add note to order
            $order->add_order_note('Nouvelle affiliation créée pour le club: ' . $club_data['nom'] . ' (ID: ' . $club_id . ')');
        }
    }
}

/**
 * Prevent multiple affiliations in cart
 */
function ufsc_prevent_multiple_affiliations($passed, $product_id)
{
    if ($product_id == ufsc_get_affiliation_product_id()) {
        // Check if affiliation is already in cart
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['product_id']) && $cart_item['product_id'] == ufsc_get_affiliation_product_id()) {
                wc_add_notice('Une demande d\'affiliation est déjà dans votre panier.', 'error');
                return false;
            }
        }
    }
    
    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'ufsc_prevent_multiple_affiliations', 10, 2);