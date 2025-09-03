<?php
/**
 * UFSC Login Form Shortcode
 *
 * Displays a simple login form with a registration CTA. If the user is
 * authenticated, it shows either the club creation form or a link to the
 * dashboard depending on whether the user already has an associated club.
 *
 * Usage: [ufsc_login_form]
 *
 * @package UFSC_Gestion_Club
 */

if (!defined('ABSPATH')) {
    exit;
}

function ufsc_login_form_shortcode($atts = []) {
    // Logged in users
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $club    = function_exists('ufsc_get_user_club') ? ufsc_get_user_club($user_id) : null;

        // If user has no club, show club creation form directly
        if (!$club) {
            if (!function_exists('ufsc_render_club_form')) {
                require_once UFSC_PLUGIN_PATH . 'includes/clubs/form-club.php';
            }
            ob_start();
            echo '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card">';
            ufsc_render_club_form(0, true, false);
            echo '</div></div></div>';
            return ob_get_clean();
        }

        // If club exists, show dashboard access
        $dashboard = ufsc_get_safe_page_url('dashboard');
        if ($dashboard['available']) {
            return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card">'
                . '<a href="' . esc_url($dashboard['url']) . '" class="ufsc-btn ufsc-btn-primary">'
                . esc_html__('Accéder au tableau de bord', 'plugin-ufsc-gestion-club-13072025')
                . '</a></div></div></div>';
        }

        return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card">'
            . esc_html__('Tableau de bord indisponible', 'plugin-ufsc-gestion-club-13072025')
            . '</div></div></div>';
    }

    // Not logged in: show login form with registration CTA
    if (!function_exists('ufsc_render_login_form')) {
        require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/login-register-shortcode.php';
    }

    $output = '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card">';
    $output .= ufsc_render_login_form('');
    $output .= '<p class="ufsc-login-cta"><a class="ufsc-btn ufsc-btn-secondary" href="'
        . esc_url(wp_registration_url()) . '">'
        . esc_html__('Créer un compte', 'plugin-ufsc-gestion-club-13072025') . '</a></p>';
    $output .= '</div></div></div>';

    return $output;
}
add_shortcode('ufsc_login_form', 'ufsc_login_form_shortcode');
