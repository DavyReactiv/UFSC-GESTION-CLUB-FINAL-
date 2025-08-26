<?php

/**
 * UFSC Club Menu Shortcode - Responsive navigation menu for club users
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Club menu shortcode function
 * 
 * Displays a responsive navigation menu for logged-in club users
 * 
 * @param array $atts Shortcode attributes (reserved for future use)
 * @return string HTML output or empty string if user not authorized
 * @since 1.3.0
 */
function ufsc_club_menu_shortcode($atts = array()) {
    // Security check: only for logged-in users with associated club
    if (!is_user_logged_in()) {
        return '';
    }
    
    // Check if user has an associated club
    $club = null;
    if (function_exists('ufsc_get_user_club')) {
        $club = ufsc_get_user_club(); // This version gets current user's club
    }
    
    if (!$club) {
        return '';
    }
    
    // Parse attributes
    $atts = shortcode_atts(array(
        'exclude' => '',
        'order' => '',
        'show_logout' => 'yes', // yes, no
        'show_buy' => 'auto'    // auto, yes, no
    ), $atts, 'ufsc_club_menu');
    
    // Get menu pages configuration
    $menu_pages = ufsc_get_club_menu_pages($atts, $club);
    
    /**
     * Filter the club menu pages before rendering
     * 
     * @param array $menu_pages Array of menu items
     * @param array $atts Shortcode attributes
     * @param object $club Current user's club object
     * @since 1.3.0
     */
    $menu_pages = apply_filters('ufsc_club_menu_pages', $menu_pages, $atts, $club);
    
    if (empty($menu_pages)) {
        return '';
    }
    
    // Generate menu HTML
    $output = ufsc_render_club_menu($menu_pages);

    // Enqueue CSS only once
    ufsc_enqueue_club_menu_css();

    return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card">'
        . $output . '</div></div></div>';
}

/**
 * Get club menu pages configuration
 * 
 * @param array $atts Shortcode attributes
 * @param object $club Current user's club object
 * @return array Array of menu items with url, label, and active status
 * @since 1.3.0
 */
function ufsc_get_club_menu_pages($atts = array(), $club = null) {
    $menu_items = array();
    $current_url = ufsc_get_current_page_url();
    
    // Define menu items with their option keys and labels
    $pages_config = array(
        'ufsc_club_dashboard_page_id' => __('Tableau de Bord', 'plugin-ufsc-gestion-club-13072025'),
        'ufsc_affiliation_page_id' => __('Affiliation', 'plugin-ufsc-gestion-club-13072025'),
        'ufsc_club_account_page_id' => __('Compte Club', 'plugin-ufsc-gestion-club-13072025'),
        'ufsc_licence_page_id' => __('Licences', 'plugin-ufsc-gestion-club-13072025'),
        'ufsc_ajouter_licencie_page_id' => __('Ajouter Licencié', 'plugin-ufsc-gestion-club-13072025')
    );
    
    // Add "Demander Licence" based on show_buy setting
    $show_buy = isset($atts['show_buy']) ? $atts['show_buy'] : 'auto';
    $should_show_buy = false;
    
    if ($show_buy === 'yes') {
        $should_show_buy = true;
    } elseif ($show_buy === 'auto') {
        // Check if licence product exists and is available
        $licence_product_id = ufsc_get_licence_product_id();
        if ($licence_product_id && get_post_status($licence_product_id) === 'publish') {
            $should_show_buy = true;
        }
    }
    
    if ($should_show_buy) {
        $pages_config['ufsc_demander_licence_page_id'] = __('Demander Licence', 'plugin-ufsc-gestion-club-13072025');
    }
    
    // Add remaining pages
    $pages_config['ufsc_attestation_page_id'] = __('Attestations', 'plugin-ufsc-gestion-club-13072025');
    $pages_config['ufsc_liste_clubs_page_id'] = __('Liste Clubs', 'plugin-ufsc-gestion-club-13072025');
    
    foreach ($pages_config as $option_key => $label) {
        $page_id = get_option($option_key, 0);
        
        if ($page_id && get_post_status($page_id) === 'publish') {
            $page_url = get_permalink($page_id);
            if ($page_url) {
                $item = array(
                    'url' => $page_url,
                    'label' => $label,
                    'active' => ($page_url === $current_url),
                    'option_key' => $option_key
                );
                
                // Add inactive badge to dashboard if club is not active
                if ($option_key === 'ufsc_club_dashboard_page_id' && $club && !ufsc_is_club_active($club)) {
                    $item['badge'] = __('Inactif', 'plugin-ufsc-gestion-club-13072025');
                    $item['badge_class'] = 'ufsc-badge-inactive';
                }
                
                $menu_items[] = $item;
            }
        }
    }
    
    // Add logout link if enabled
    $show_logout = isset($atts['show_logout']) ? $atts['show_logout'] : 'yes';
    if ($show_logout === 'yes') {
        $menu_items[] = array(
            'url' => wp_logout_url(),
            'label' => __('Déconnexion', 'plugin-ufsc-gestion-club-13072025'),
            'active' => false,
            'option_key' => 'logout',
            'class' => 'ufsc-menu-logout'
        );
    }
    
    return $menu_items;
}

/**
 * Get current page URL for active state detection
 * 
 * @return string Current page URL
 * @since 1.3.0
 */
function ufsc_get_current_page_url() {
    global $wp;
    
    if (is_page()) {
        return get_permalink();
    }
    
    return home_url($wp->request);
}

/**
 * Render the club menu HTML
 * 
 * @param array $menu_items Array of menu items
 * @return string HTML output
 * @since 1.3.0
 */
function ufsc_render_club_menu($menu_items) {
    if (empty($menu_items)) {
        return '';
    }
    
    $output = '<nav class="ufsc-club-menu-wrapper">';
    $output .= '<ul class="ufsc-menu-list">';
    
    foreach ($menu_items as $item) {
        $active_class = $item['active'] ? ' ufsc-club-menu-item-active' : '';
        $item_class = isset($item['class']) ? ' ' . $item['class'] : '';
        
        $output .= '<li class="ufsc-menu-item' . esc_attr($active_class) . esc_attr($item_class) . '">';
        $output .= '<a href="' . esc_url($item['url']) . '" class="ufsc-menu-link">';
        $output .= esc_html($item['label']);
        
        // Add badge if present
        if (isset($item['badge'])) {
            $badge_class = isset($item['badge_class']) ? $item['badge_class'] : 'ufsc-badge';
            $output .= ' <span class="' . esc_attr($badge_class) . '">' . esc_html($item['badge']) . '</span>';
        }
        
        $output .= '</a>';
        $output .= '</li>';
    }
    
    $output .= '</ul>';
    $output .= '</nav>';
    
    return $output;
}

/**
 * Enqueue club menu CSS (only once per page load)
 * 
 * @since 1.3.0
 */
function ufsc_enqueue_club_menu_css() {
    // Use a constant to ensure CSS is only printed once
    if (defined('UFSC_CLUB_MENU_CSS_PRINTED')) {
        return;
    }
    
    define('UFSC_CLUB_MENU_CSS_PRINTED', true);
    
    // Print inline CSS
    ?>
    <style type="text/css">
    .ufsc-club-menu-wrapper {
        margin: 20px 0;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .ufsc-menu-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .ufsc-menu-item {
        margin: 0;
        position: relative;
    }
    
    .ufsc-menu-link {
        display: block;
        padding: 10px 15px;
        text-decoration: none;
        color: #333;
        background: #fff;
        border-radius: 5px;
        border: 1px solid #ddd;
        transition: all 0.3s ease;
        font-size: 14px;
        font-weight: 500;
    }
    
    .ufsc-menu-link:hover {
        background: #007cba;
        color: #fff;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .ufsc-club-menu-item-active .ufsc-menu-link {
        background: #007cba;
        color: #fff;
        border-color: #005177;
    }
    
    .ufsc-club-menu-item-active .ufsc-menu-link:hover {
        background: #005177;
    }
    
    /* Badge styles */
    .ufsc-badge-inactive {
        background: #dc3545;
        color: #fff;
        padding: 2px 6px;
        font-size: 11px;
        border-radius: 10px;
        margin-left: 5px;
        font-weight: normal;
    }
    
    /* Logout menu item */
    .ufsc-menu-logout .ufsc-menu-link {
        background: #6c757d;
        color: #fff;
        border-color: #545b62;
    }
    
    .ufsc-menu-logout .ufsc-menu-link:hover {
        background: #5a6268;
        border-color: #4e555b;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .ufsc-menu-list {
            flex-direction: column;
        }
        
        .ufsc-menu-item {
            width: 100%;
        }
        
        .ufsc-menu-link {
            text-align: center;
        }
    }
    
    @media (max-width: 480px) {
        .ufsc-club-menu {
            padding: 8px;
        }
        
        .ufsc-menu-link {
            padding: 8px 12px;
            font-size: 13px;
        }
    }
    </style>
    <?php
}

// Register the shortcode
add_shortcode('ufsc_club_menu', 'ufsc_club_menu_shortcode');