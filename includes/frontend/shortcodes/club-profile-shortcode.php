<?php

/**
 * UFSC Club Profile Shortcode
 *
 * Combines club profile information and document management
 * into a single [ufsc_club_profile] shortcode. Access restricted
 * to authenticated users that manage the club.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render club profile and documents sections.
 *
 * @param array $atts Shortcode attributes (unused)
 * @return string HTML output
 */
function ufsc_club_profile_shortcode($atts = array()) {
    if (!is_user_logged_in()) {
        return '<div class="ufsc-alert ufsc-alert-error">' .
               esc_html__('Vous devez être connecté pour accéder à cette page.', 'plugin-ufsc-gestion-club-13072025') .
               '</div>';
    }

    $club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    if (!$club) {
        return '<div class="ufsc-alert ufsc-alert-error">' .
               esc_html__('Aucun club associé.', 'plugin-ufsc-gestion-club-13072025') .
               '</div>';
    }

    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">' .
               esc_html__('Accès refusé.', 'plugin-ufsc-gestion-club-13072025') .
               '</div>';
    }

    if (!function_exists('ufsc_club_render_profile')) {
        require_once UFSC_PLUGIN_PATH . 'includes/frontend/club/club-infos.php';
    }
    if (!function_exists('ufsc_club_render_documents')) {
        require_once UFSC_PLUGIN_PATH . 'includes/frontend/club/documents.php';
    }

    $output = '<div class="ufsc-club-profile">';
    $output .= ufsc_club_render_profile($club);
    $output .= ufsc_club_render_documents($club);
    $output .= '</div>';

    return $output;
}

// Register shortcode
add_shortcode('ufsc_club_profile', 'ufsc_club_profile_shortcode');

