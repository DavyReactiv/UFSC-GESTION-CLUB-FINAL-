<?php

/**
 * UFSC Dashboard Shortcode
 *
 * Provides the [ufsc_dashboard] shortcode which renders the
 * club dashboard interface. Access is restricted to authenticated
 * users that own a club.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the club dashboard for the current user.
 *
 * @param array $atts Shortcode attributes.
 * @return string Dashboard HTML or error message.
 */
function ufsc_dashboard_shortcode($atts = array()) {
    // Verify authentication
    if (!is_user_logged_in()) {
        return '<div class="ufsc-alert ufsc-alert-error">' .
               esc_html__('Vous devez être connecté pour accéder à cette page.', 'plugin-ufsc-gestion-club-13072025') .
               '</div>';
    }

    // Check club association
    $club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    if (!$club || !ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">' .
               esc_html__('Accès refusé : aucun club associé.', 'plugin-ufsc-gestion-club-13072025') .
               '</div>';
    }

    // Ensure dashboard class is loaded
    if (!class_exists('UFSC_Club_Dashboard')) {
        require_once UFSC_PLUGIN_PATH . 'includes/frontend/club/dashboard.php';
    }

    $dashboard = new UFSC_Club_Dashboard();
    return $dashboard->render($atts);
}

// Register shortcode
add_shortcode('ufsc_dashboard', 'ufsc_dashboard_shortcode');

