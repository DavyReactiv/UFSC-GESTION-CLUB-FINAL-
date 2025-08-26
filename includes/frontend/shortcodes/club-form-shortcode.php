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
    // Si l'utilisateur n'est pas connecté, afficher un message d'erreur
    if (!is_user_logged_in()) {
        return '<p class="ufsc-error">Vous devez être connecté pour accéder à ce formulaire.</p>';
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
