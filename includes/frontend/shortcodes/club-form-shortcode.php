<?php

/**
 * Shortcode pour le formulaire de club
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode [ufsc_formulaire_club]
 */
function ufsc_formulaire_club_shortcode($atts)
{
    // Si l'utilisateur n'est pas connecté, afficher le formulaire de connexion/inscription
    if (!is_user_logged_in()) {
        if (!function_exists('ufsc_login_register_shortcode')) {
            require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/login-register-shortcode.php';
        }

        // Rediriger vers la même page après connexion/inscription
        return ufsc_login_register_shortcode(array(
            'redirect' => get_permalink(),
        ));

        return ufsc_login_register_shortcode([
            'redirect' => get_permalink(),
            'show_register' => 'yes',
        ]);
    }

    // Démarrer la capture de sortie
    ob_start();

    // Inclusion du formulaire
    require_once UFSC_PLUGIN_PATH . 'includes/clubs/form-club.php';

    // Appel de la fonction avec les paramètres frontend=true et affiliation=true
    ufsc_render_club_form(0, true, true);

    // Récupérer le contenu capturé
    return ob_get_clean();
}

add_shortcode('ufsc_formulaire_club', 'ufsc_formulaire_club_shortcode');
