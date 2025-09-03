<?php

/**
 * Product Button Helpers
 * 
 * Centralized functions for generating WooCommerce product buttons
 * with quota management, status checking, and error handling.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render a product button with context-aware features
 * 
 * @param int $product_id WooCommerce product ID
 * @param string $label Button label
 * @param string $classes Additional CSS classes
 * @param string $context Button context (licence, affiliation, etc.)
 * @param array $options Additional options
 * @return string HTML button
 */
function ufsc_render_product_button($product_id, $label, $classes = '', $context = 'general', $options = [])
{
    // Default options
    $defaults = [
        'show_quota' => true,
        'show_tooltip' => true,
        'check_club_status' => true,
        'redirect_url' => '',
        'button_type' => 'link', // 'link', 'button', 'form'
        'extra_attrs' => ''
    ];
    $options = wp_parse_args($options, $defaults);

    // Get product
    $product = wc_get_product($product_id);
    if (!$product) {
        return '<span class="ufsc-error">Produit introuvable</span>';
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(get_permalink());
        return '<a href="' . esc_url($login_url) . '" class="ufsc-btn ufsc-btn-outline ' . esc_attr($classes) . '">
                Se connecter pour ' . esc_html(strtolower($label)) . '
                </a>';
    }

    // Get user's club for context checking
    $club = null;
    $button_disabled = false;
    $disabled_reason = '';
    $tooltip = '';

    if ($options['check_club_status']) {
        $access_check = ufsc_check_frontend_access($context);
        if (!$access_check['allowed']) {
            return ufsc_render_disabled_button($label, $classes, 'Club requis', 'Vous devez être associé à un club.');
        }
        $club = $access_check['club'];
    }

    // Context-specific checks
    switch ($context) {
        case 'licence':
            if ($club) {
                // Check club status
                if (!ufsc_is_club_active($club)) {
                    return ufsc_render_disabled_button($label, $classes, 'Club inactif', 'Votre club doit être actif pour ajouter des licences.');
                }

                // Check quota
                if ($options['show_quota']) {
                    $quota_info = ufsc_get_club_quota_info($club->id);
                    if ($quota_info['has_quota'] && $quota_info['remaining'] <= 0) {
                        return ufsc_render_disabled_button($label, $classes, 'Quota atteint', 'Votre club a atteint son quota de licences (' . $quota_info['used'] . '/' . $quota_info['total'] . ').');
                    }
                    
                    // Add quota info to tooltip
                    if ($quota_info['has_quota']) {
                        $tooltip = 'Quota: ' . $quota_info['used'] . '/' . $quota_info['total'] . ' licences utilisées';
                    }
                }
            }
            break;

        case 'affiliation':
            // Check if user already has an active club
            $user_id = get_current_user_id();
            $existing_club = ufsc_get_user_club($user_id);
            
            if ($existing_club && ufsc_is_club_active($existing_club)) {
                return ufsc_render_disabled_button($label, $classes, 'Déjà affilié', 'Votre club "' . $existing_club->nom . '" est déjà affilié et actif.');
            }

            // Check if affiliation is already in cart
            if (function_exists('WC') && WC()->cart) {
                foreach (WC()->cart->get_cart() as $cart_item) {
                    if (isset($cart_item['product_id']) && $cart_item['product_id'] == ufsc_get_affiliation_product_id_safe()) {
                        return ufsc_render_disabled_button($label, $classes, 'En cours', 'Une demande d\'affiliation est déjà dans votre panier.');
                    }
                }
            }
            break;
    }

    // Generate the button
    $product_url = get_permalink($product);
    $final_url = !empty($options['redirect_url']) ? $options['redirect_url'] : $product_url;
    
    $button_classes = 'ufsc-btn ufsc-btn-primary ' . $classes;
    $button_attrs = '';
    
    if ($options['show_tooltip'] && !empty($tooltip)) {
        $button_attrs .= ' title="' . esc_attr($tooltip) . '"';
    }

    $extra_attrs = !empty($options['extra_attrs']) ? ' ' . $options['extra_attrs'] : '';

    switch ($options['button_type']) {
        case 'button':
            return '<button type="button" class="' . esc_attr($button_classes) . '"' . $button_attrs . $extra_attrs . ' onclick="window.location.href=\'' . esc_url($final_url) . '\'">' .
                   esc_html($label) . '</button>';

        case 'form':
            return '<form method="get" action="' . esc_url($final_url) . '" style="display:inline;">
                    <button type="submit" class="' . esc_attr($button_classes) . '"' . $button_attrs . $extra_attrs . '>' . esc_html($label) . '</button>
                    </form>';

        case 'link':
        default:
            return '<a href="' . esc_url($final_url) . '" class="' . esc_attr($button_classes) . '"' . $button_attrs . $extra_attrs . '>' .
                   esc_html($label) . '</a>';
    }
}

/**
 * Render a disabled button with tooltip
 * 
 * @param string $label Button label
 * @param string $classes CSS classes
 * @param string $reason Short reason for disability
 * @param string $tooltip Full tooltip explanation
 * @return string HTML disabled button
 */
function ufsc_render_disabled_button($label, $classes = '', $reason = '', $tooltip = '')
{
    $button_classes = 'ufsc-btn ufsc-btn-disabled ' . $classes;
    $button_text = $label;
    
    if (!empty($reason)) {
        $button_text .= ' (' . $reason . ')';
    }
    
    $tooltip_attr = !empty($tooltip) ? ' title="' . esc_attr($tooltip) . '"' : '';
    
    return '<button type="button" class="' . esc_attr($button_classes) . '" disabled' . $tooltip_attr . '>' . 
           esc_html($button_text) . '</button>';
}

/**
 * Generate licence button with automatic context detection
 * 
 * @param array $args Button arguments
 * @return string HTML button
 */
function ufsc_generate_licence_button($args = [])
{
    $defaults = [
        'label' => 'Nouvelle licence',
        'classes' => '',
        'show_quota' => true,
        'context' => 'licence'
    ];
    $args = wp_parse_args($args, $defaults);

    return ufsc_render_product_button(
        ufsc_get_licence_product_id(),
        $args['label'],
        $args['classes'],
        $args['context'],
        [
            'show_quota' => $args['show_quota'],
            'check_club_status' => true
        ]
    );
}

/**
 * Generate affiliation button with automatic context detection
 * 
 * @param array $args Button arguments
 * @return string HTML button
 */
function ufsc_generate_affiliation_button($args = [])
{
    $defaults = [
        'label' => 'Affiliation club',
        'classes' => '',
        'context' => 'affiliation'
    ];
    $args = wp_parse_args($args, $defaults);

    // Determine if this is a renewal
    $user_id = get_current_user_id();
    $existing_club = $user_id ? ufsc_get_user_club($user_id) : null;

    if ($existing_club && !ufsc_is_club_active($existing_club)) {
        $args['label'] = 'Renouveler l\'affiliation';
    }

    $extra_attrs = '';
    if ($existing_club) {
        $extra_attrs = 'data-club-id="' . intval($existing_club->id) . '"';
    }

    $args['classes'] .= ' ufsc-pay-affiliation';

    return ufsc_render_product_button(
        ufsc_get_affiliation_product_id_safe(),
        $args['label'],
        $args['classes'],
        $args['context'],
        [
            'check_club_status' => false, // Affiliation doesn't require existing active club
            'extra_attrs' => $extra_attrs
        ]
    );
}

/**
 * Generate quick action buttons for dashboard
 * 
 * @param object $club Club object
 * @return string HTML buttons
 */
function ufsc_generate_dashboard_action_buttons($club = null)
{
    if (!$club) {
        $access_check = ufsc_check_frontend_access('dashboard');
        if (!$access_check['allowed']) {
            return '';
        }
        $club = $access_check['club'];
    }

    $buttons = [];

    // License button
    if (ufsc_is_club_active($club)) {
        $buttons[] = ufsc_generate_licence_button([
            'label' => 'Nouvelle licence',
            'classes' => 'ufsc-btn-sm'
        ]);
    }

    // Affiliation/renewal button
    if (!ufsc_is_club_active($club)) {
        $buttons[] = ufsc_generate_affiliation_button([
            'classes' => 'ufsc-btn-sm'
        ]);
    }

    // Attestations button (if club is active)
    if (ufsc_is_club_active($club)) {
        $attestation_page = ufsc_get_safe_page_url('attestations');
        if ($attestation_page['available']) {
            $buttons[] = '<a href="' . esc_url($attestation_page['url']) . '" class="ufsc-btn ufsc-btn-outline ufsc-btn-sm">Attestations</a>';
        }
    }

    return empty($buttons) ? '' : '<div class="ufsc-action-buttons">' . implode(' ', $buttons) . '</div>';
}

/**
 * Generate cart-aware buttons (prevents adding if already in cart)
 * 
 * @param int $product_id Product ID
 * @param string $label Button label
 * @param array $options Button options
 * @return string HTML button
 */
function ufsc_generate_cart_aware_button($product_id, $label, $options = [])
{
    // Check if product is already in cart
    if (function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['product_id']) && $cart_item['product_id'] == $product_id) {
                return '<div class="ufsc-alert ufsc-alert-info">
                        <p>Produit déjà dans le panier</p>
                        <p><a href="' . wc_get_cart_url() . '" class="ufsc-btn ufsc-btn-sm">Voir le panier</a></p>
                        </div>';
            }
        }
    }

    // Determine context based on product ID
    $context = 'general';
    if ($product_id == ufsc_get_licence_product_id()) {
        $context = 'licence';
    } elseif ($product_id == ufsc_get_affiliation_product_id_safe()) {
        $context = 'affiliation';
    }

    return ufsc_render_product_button($product_id, $label, '', $context, $options);
}