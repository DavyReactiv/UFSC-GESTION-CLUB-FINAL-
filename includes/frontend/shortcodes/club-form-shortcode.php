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
    // Si l'utilisateur n'est pas connecté, afficher le bloc de connexion/inscription
    if (!is_user_logged_in()) {
        $register_page_id = get_option('ufsc_login_page_id', 0);
        $register_url = $register_page_id ? get_permalink($register_page_id) : wp_registration_url();
        return '<div class="ufsc-alert ufsc-alert-error">'
            . '<p>Vous devez être connecté pour accéder à ce formulaire.</p>'
            . '<p><a href="' . wp_login_url(get_permalink()) . '" class="ufsc-btn">Se connecter</a> ou '
            . '<a href="' . $register_url . '" class="ufsc-btn ufsc-btn-outline">Créer un compte</a></p>'
            . '</div>';
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
