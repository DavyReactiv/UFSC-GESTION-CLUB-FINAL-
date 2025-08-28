<?php
require_once __DIR__.'/persist.php';

/**
 * AJAX Add Licence to Cart Handler
 * 
 * Handles AJAX requests to add licences to WooCommerce cart.
 * Provides secure licence addition with club access verification
 * and payload validation.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle AJAX request to add licence to cart
 * 
 * This function handles both logged-in and guest users adding
 * licences to the WooCommerce cart with proper validation.
 */
function ufsc_handle_add_licence_to_cart() {
    // Verify nonce using standard frontend nonce
    if (!check_ajax_referer('ufsc_front_nonce', 'ufsc_nonce', false)) {
        wp_send_json_error(['message' => esc_html__('Security check failed.', 'ufsc-domain'), 'code' => 'nonce_failed']);
    }

    if (is_user_logged_in() && !current_user_can('read')) {
        wp_send_json_error(['message'=>esc_html__('Unauthorized access.', 'ufsc-domain'),'code'=>'cap_failed'], 403);
    }

    // Get and validate club ID
    $club_id = isset($_POST['club_id']) ? intval($_POST['club_id']) : 0;
    if (!$club_id) {
        wp_send_json_error(esc_html__('Club ID is required.', 'ufsc-domain'), 400);
    }

    // Verify club access if function exists
    if (function_exists('ufsc_verify_club_access')) {
        if (!ufsc_verify_club_access($club_id)) {
            wp_send_json_error(['message'=>esc_html__('Access denied to this club.', 'ufsc-domain'),'code'=>'club_forbidden']);
        }
    } else {
        // Fallback: try to infer club from current user
        global $wpdb; $club_guess = (int) $wpdb->get_var($wpdb->prepare('SELECT club_id FROM {$wpdb->prefix}ufsc_user_clubs WHERE user_id=%d LIMIT 1', get_current_user_id()));
        if ($club_guess>0) { $club_id = $club_guess; } // continue silently
    }

    // Validate minimal licence payload
    $nom = isset($_POST['nom']) ? sanitize_text_field($_POST['nom']) : '';
    $prenom = isset($_POST['prenom']) ? sanitize_text_field($_POST['prenom']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    // Validate required fields
    if (empty($nom) || empty($prenom) || empty($email)) {
        wp_send_json_error(esc_html__('Nom, prénom et email sont requis.', 'ufsc-domain'), 400);
    }

    // Validate email format
    if (!is_email($email)) {
        wp_send_json_error(esc_html__('Format d\'email invalide.', 'ufsc-domain'), 400);
    }

    // Resolve WooCommerce product ID
    $product_id = 0;
    if (function_exists('ufsc_get_licence_product_id_safe')) {
        $product_id = ufsc_get_licence_product_id_safe();
    } elseif (function_exists('ufsc_get_licence_product_id')) {
        $product_id = ufsc_get_licence_product_id();
    }

    // Allow filter to override product ID
    $product_id = apply_filters('ufsc_licence_product_id', $product_id);

    if (!$product_id) {
        wp_send_json_error(esc_html__('Product ID not found.', 'ufsc-domain'));
    }

    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        wp_send_json_error(esc_html__('WooCommerce is not active.', 'ufsc-domain'));
    }

    // Prepare licence payload for cart item data
    $licence_payload = [
        // Basic personal info
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'sexe' => isset($_POST['sexe']) ? sanitize_text_field($_POST['sexe']) : '',
        'date_naissance' => isset($_POST['date_naissance']) ? sanitize_text_field($_POST['date_naissance']) : '',
        'lieu_naissance' => isset($_POST['lieu_naissance']) ? sanitize_text_field($_POST['lieu_naissance']) : '',
        
        // Address info
        'adresse' => isset($_POST['adresse']) ? sanitize_text_field($_POST['adresse']) : '',
        'suite_adresse' => isset($_POST['suite_adresse']) ? sanitize_text_field($_POST['suite_adresse']) : '',
        'code_postal' => isset($_POST['code_postal']) ? sanitize_text_field($_POST['code_postal']) : '',
        'ville' => isset($_POST['ville']) ? sanitize_text_field($_POST['ville']) : '',
        'region' => isset($_POST['region']) ? sanitize_text_field($_POST['region']) : '',
        
        // Phone info (replacing deprecated 'telephone' field)
        'tel_fixe' => isset($_POST['tel_fixe']) ? sanitize_text_field($_POST['tel_fixe']) : '',
        'tel_mobile' => isset($_POST['tel_mobile']) ? sanitize_text_field($_POST['tel_mobile']) : '',
        
        // Professional info
        'reduction_benevole' => !empty($_POST['reduction_benevole']) ? 1 : 0,
        'reduction_postier' => !empty($_POST['reduction_postier']) ? 1 : 0,
        'identifiant_laposte' => isset($_POST['identifiant_laposte']) ? sanitize_text_field($_POST['identifiant_laposte']) : '',
        'profession' => isset($_POST['profession']) ? sanitize_text_field($_POST['profession']) : '',
        'fonction_publique' => !empty($_POST['fonction_publique']) ? 1 : 0,
        
        // Consent and preferences
        'diffusion_image' => !empty($_POST['diffusion_image']) ? 1 : 0,
        'infos_fsasptt' => !empty($_POST['infos_fsasptt']) ? 1 : 0,
        'infos_asptt' => !empty($_POST['infos_asptt']) ? 1 : 0,
        'infos_cr' => !empty($_POST['infos_cr']) ? 1 : 0,
        'infos_partenaires' => !empty($_POST['infos_partenaires']) ? 1 : 0,
        'honorabilite' => !empty($_POST['honorabilite']) ? 1 : 0,
        
        
// Sport/licence info
        'competition' => !empty($_POST['competition']) ? 1 : 0,
        'licence_delegataire' => !empty($_POST['licence_delegataire']) ? 1 : 0,
        'numero_licence_delegataire' => isset($_POST['numero_licence_delegataire']) ? sanitize_text_field($_POST['numero_licence_delegataire']) : '',
        
        // Insurance
        'assurance_dommage_corporel' => !empty($_POST['assurance_dommage_corporel']) ? 1 : 0,
        'assurance_assistance' => !empty($_POST['assurance_assistance']) ? 1 : 0,
        
        // Additional info
        'note' => isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '',
    ];

    // Persist to database for backend sync (after building payload)
    $existing_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    $persist_id = ufsc_persist_licence_from_post($club_id, $existing_id, array('statut'=>'brouillon'));
    if ($persist_id) { $_POST['licence_id'] = $persist_id; }



    // Generate unique key and prevent duplicates
    $unique_key = ufsc_generate_licence_key(array_merge($licence_payload, ['club_id' => $club_id]));

    if (ufsc_cart_contains_licence($unique_key)) {
        wp_send_json_error(esc_html__('Ce licencié est déjà dans votre panier.', 'ufsc-domain'));
    }

    // Prepare cart item data with the correct structure
    $cart_item_data = [
        'ufsc_licence_data' => $licence_payload,
        'ufsc_product_type' => 'licence',
        'ufsc_club_id' => $club_id,
        'unique_key' => $unique_key
    ];

    // Add to WooCommerce cart
    try {
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);
        
        if ($cart_item_key) {
            // Success - return cart URL for redirect
            $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : wc_get_page_permalink('cart');
            
            wp_send_json_success([
                'message' => esc_html__('Licence ajoutée au panier avec succès.', 'ufsc-domain'),
                'cart_url' => $cart_url,
                'cart_item_key' => $cart_item_key
            ]);
        } else {
            wp_send_json_error(esc_html__('Erreur lors de l\'ajout au panier.', 'ufsc-domain'));
        }
    } catch (Exception $e) {
        wp_send_json_error(sprintf(esc_html__('Erreur lors de l\'ajout au panier : %s', 'ufsc-domain'), $e->getMessage()));
    }
}

// Register AJAX actions for both logged-in and guest users
add_action('wp_ajax_ufsc_add_licence_to_cart', 'ufsc_handle_add_licence_to_cart');
add_action('wp_ajax_nopriv_ufsc_add_licence_to_cart', 'ufsc_handle_add_licence_to_cart');