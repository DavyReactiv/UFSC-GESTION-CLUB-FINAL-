<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode : [ufsc_ajouter_licence] 
 * DEPRECATED - Redirigé vers le nouveau flux [ufsc_ajouter_licencie]
 * Affiche un formulaire frontend pour ajouter une licence à un club
 */
function ufsc_shortcode_ajouter_licence()
{
    // Afficher un message de dépréciation et rediriger vers le nouveau shortcode
    $deprecation_notice = '<div class="ufsc-alert ufsc-alert-info">
        <p><strong>Information :</strong> Cette page utilise l\'ancien système. 
        Le nouveau flux "Ajouter un licencié" offre une expérience améliorée avec gestion intégrée de l\'achat de licences.</p>
        </div>';
    
    // Rediriger vers le nouveau shortcode pour maintenir la compatibilité
    if (function_exists('ufsc_ajouter_licencie_shortcode')) {
        return $deprecation_notice . ufsc_ajouter_licencie_shortcode([]);
    }
    
    // Fallback si le nouveau shortcode n'est pas disponible
    return $deprecation_notice . '<div class="ufsc-alert ufsc-alert-error">
        <p>Le nouveau système n\'est pas encore activé. Veuillez contacter l\'administrateur.</p>
        </div>';
}
add_shortcode('ufsc_ajouter_licence', 'ufsc_shortcode_ajouter_licence');