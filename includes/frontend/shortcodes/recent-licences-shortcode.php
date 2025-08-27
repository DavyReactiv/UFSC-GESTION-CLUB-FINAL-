<?php

/**
 * UFSC Recent Licences Shortcode
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Recent licences shortcode function
 * 
 * Displays a list of recent licences for the current club
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 * @since 1.3.0
 */
function ufsc_recent_licences_shortcode($atts = array()) {
    // Parse attributes
    $atts = shortcode_atts(array(
        'limit' => '5',
        'show_link' => 'yes' // yes, no
    ), $atts, 'ufsc_recent_licences');
    
    // Security check: only for logged-in users
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
    
    // Get club manager instance
    $club_manager = UFSC_Club_Manager::get_instance();
    if (!$club_manager) {
        return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card"><div class="ufsc-alert ufsc-alert-error"><p>'
            . __('Erreur : impossible de charger les données.', 'plugin-ufsc-gestion-club-13072025') . '</p></div></div></div></div>';
    }
    
    // Get licences for the club
    $licences = $club_manager->get_licences_by_club($club->id);
    
    if (empty($licences)) {
        return '<div class="ufsc-container"><div class="ufsc-grid">' . ufsc_render_no_licences_message() . '</div></div>';
    }
    
    // Sort licences by creation date (most recent first)
    usort($licences, function($a, $b) {
        $date_a = isset($a->date_creation) ? strtotime($a->date_creation) : 0;
        $date_b = isset($b->date_creation) ? strtotime($b->date_creation) : 0;
        return $date_b - $date_a;
    });
    
    // Limit the number of licences
    $limit = intval($atts['limit']);
    if ($limit > 0 && count($licences) > $limit) {
        $licences = array_slice($licences, 0, $limit);
    }
    
    // Render the widget
    return '<div class="ufsc-container"><div class="ufsc-grid">'
        . ufsc_render_recent_licences_widget($licences, $atts) . '</div></div>';
}

/**
 * Render no licences message
 * 
 * @return string HTML output
 */
function ufsc_render_no_licences_message() {
    $output = '<div class="ufsc-card ufsc-recent-licences-widget">';
    $output .= '<h3>' . __('Licences récentes', 'plugin-ufsc-gestion-club-13072025') . '</h3>';
    $output .= '<div class="ufsc-alert ufsc-alert-info">';
    $output .= '<p>' . __('Aucune licence trouvée pour ce club.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}

/**
 * Render recent licences widget
 * 
 * @param array $licences Array of licence objects
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_render_recent_licences_widget($licences, $atts) {
    $output = '<div class="ufsc-card ufsc-recent-licences-widget">';
    $output .= '<h3>' . __('Licences récentes', 'plugin-ufsc-gestion-club-13072025') . '</h3>';
    
    $output .= '<div class="ufsc-licences-list">';
    
    foreach ($licences as $licence) {
        $output .= ufsc_render_licence_item($licence);
    }
    
    $output .= '</div>';
    
    // Add "View all" link if enabled
    if ($atts['show_link'] === 'yes') {
        $licences_url = ufsc_get_licences_page_url();
        if ($licences_url) {
            $output .= '<div class="ufsc-widget-footer">';
            $output .= '<a href="' . esc_url($licences_url) . '" class="ufsc-btn ufsc-btn-outline">';
            $output .= __('Voir toutes les licences', 'plugin-ufsc-gestion-club-13072025');
            $output .= '</a>';
            $output .= '</div>';
        }
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Render individual licence item
 * 
 * @param object $licence Licence object
 * @return string HTML output
 */
function ufsc_render_licence_item($licence) {
    $output = '<div class="ufsc-licence-item">';
    
    // Licence holder name
    $name = '';
    if (isset($licence->prenom) && isset($licence->nom)) {
        $name = trim($licence->prenom . ' ' . $licence->nom);
    } elseif (isset($licence->nom)) {
        $name = $licence->nom;
    } else {
        $name = __('Nom non disponible', 'plugin-ufsc-gestion-club-13072025');
    }
    
    $output .= '<div class="ufsc-licence-name">' . esc_html($name) . '</div>';
    
    // Status badge
    $status = isset($licence->statut) ? $licence->statut : 'draft';
    $payment = isset($licence->payment_status) ? $licence->payment_status : '';
    $output .= '<div class="ufsc-licence-meta">';
    $output .= ufsc_get_license_status_badge($status, $payment);
    
    // Date
    $date = '';
    if (isset($licence->date_creation) && !empty($licence->date_creation)) {
        $timestamp = strtotime($licence->date_creation);
        if ($timestamp) {
            $date = date_i18n('d/m/Y', $timestamp);
        }
    }
    
    if ($date) {
        $output .= '<span class="ufsc-licence-date">' . esc_html($date) . '</span>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
}


/**
 * Get licences page URL from options
 * 
 * @return string|false Licences page URL or false if not configured
 */
function ufsc_get_licences_page_url() {
    $licences_page_id = get_option('ufsc_licence_page_id', 0);
    
    if ($licences_page_id && get_post_status($licences_page_id) === 'publish') {
        return get_permalink($licences_page_id);
    }
    
    return false;
}

// Register the shortcode
add_shortcode('ufsc_recent_licences', 'ufsc_recent_licences_shortcode');
