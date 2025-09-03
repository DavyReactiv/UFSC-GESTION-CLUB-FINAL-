<?php

/**
 * UFSC Licences Shortcode
 *
 * Displays the licence management table for the current user's club
 * via the [ufsc_licences] shortcode. Users must be authenticated and
 * associated with the club they attempt to view.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render licence management table
 *
 * @param array $atts Shortcode attributes (unused)
 * @return string HTML output
 */
function ufsc_licences_shortcode($atts = array()) {
    // Authentication check
    if (!is_user_logged_in()) {
        return '<div class="ufsc-alert ufsc-alert-error">' .
               esc_html__('Vous devez être connecté pour accéder à cette page.', 'plugin-ufsc-gestion-club-13072025') .
               '</div>';
    }

    // Retrieve club for current user
    $club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    if (!$club) {
        return '<div class="ufsc-alert ufsc-alert-error">' .
               esc_html__('Aucun club associé.', 'plugin-ufsc-gestion-club-13072025') .
               '</div>';
    }

    // Verify ownership
    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">' .
               esc_html__('Accès refusé.', 'plugin-ufsc-gestion-club-13072025') .
               '</div>';
    }

    // Load rendering functions
    if (!function_exists('ufsc_club_render_licences')) {
        require_once UFSC_PLUGIN_PATH . 'includes/frontend/club/licences.php';
    }

    return ufsc_club_render_licences($club);
}

// Register shortcode
add_shortcode('ufsc_licences', 'ufsc_licences_shortcode');

