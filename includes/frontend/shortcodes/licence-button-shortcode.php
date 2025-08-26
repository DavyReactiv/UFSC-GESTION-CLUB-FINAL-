<?php

/**
 * Shortcode pour le bouton d'achat de licence
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode [ufsc_bouton_licence]
 */
function ufsc_bouton_licence_shortcode($atts)
{
    // CORRECTION: Use standardized frontend access control
    $access_check = ufsc_check_frontend_access('licence');
    
    if (!$access_check['allowed']) {
        return $access_check['error_message'];
    }
    
    $club = $access_check['club'];

    // CORRECTION: Use standardized status checking 
    if (!ufsc_is_club_active($club)) {
        return ufsc_render_club_status_alert($club, 'licence');
    }

    // Récupérer le nombre de licences
    $club_manager = \UFSC\Clubs\ClubManager::get_instance();
    $licences = $club_manager->get_licences_by_club($club->id);
    $quota_total = intval($club->quota_licences);
    $licences_count = count($licences);
    
    // CORRECTION: quota_total = 0 means unlimited quota
    $is_unlimited_quota = ($quota_total === 0);
    $quota_remaining = $is_unlimited_quota ? PHP_INT_MAX : max(0, $quota_total - $licences_count);
    $can_add_licence = $is_unlimited_quota || $quota_remaining > 0;

    // Lien vers la page produit (using configurable product ID)
    $product_url = get_permalink(ufsc_get_licence_product_id());

    // Générer le bouton avec informations de quota
    $output = '<div class="ufsc-licence-button-container">';

    // Show quota status only if not unlimited
    if (!$is_unlimited_quota) {
        $output .= '<div class="ufsc-licence-quota-status">';
        $output .= '<span class="ufsc-licence-count">' . $licences_count . ' / ' . $quota_total . '</span> licences utilisées';

        if ($quota_remaining > 0) {
            $output .= ' - <span class="ufsc-quota-remaining">' . $quota_remaining . ' restante(s)</span>';
        } else {
            $output .= ' - <span class="ufsc-quota-exhausted">Quota épuisé</span>';
        }

        $output .= '</div>';
    } else {
        // For unlimited quota, show a different message
        $output .= '<div class="ufsc-licence-quota-status">';
        $output .= '<span class="ufsc-licence-count">' . $licences_count . '</span> licence(s) - <span class="ufsc-quota-unlimited">Quota illimité</span>';
        $output .= '</div>';
    }

    // Show button only if licenses can be added (unlimited or quota available)
    if ($can_add_licence) {
        $output .= '<a href="' . esc_url($product_url) . '" class="ufsc-btn ufsc-btn-red ufsc-licence-button">
                    <i class="dashicons dashicons-plus-alt2"></i> Demander une licence
                    </a>';
        $output .= '<p class="ufsc-licence-info">La demande de licence nécessite de remplir un formulaire complet et obligatoire.</p>';
    } else {
        $output .= '<div class="ufsc-alert ufsc-alert-warning">
                    <p><strong>Quota épuisé</strong> - Vous avez atteint le nombre maximum de licences autorisées pour votre club.</p>
                    </div>';
    }

    $output .= '</div>';

    return $output;
}

add_shortcode('ufsc_bouton_licence', 'ufsc_bouton_licence_shortcode');
