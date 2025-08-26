<?php

/**
 * Club Information Management
 * 
 * Handles club profile information display and editing for the frontend dashboard.
 * Provides secure access to club data with proper ownership verification.
 *
 * @package UFSC_Gestion_Club
 * @subpackage Frontend\Club
 * @since 1.0.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render club profile information section
 *
 * @param object $club Club object with all club data
 * @return string HTML output for club profile section
 */
function ufsc_club_render_profile($club)
{
    // Enqueue compact styles for better readability
    wp_enqueue_style(
        'ufsc-club-infos-compact',
        UFSC_PLUGIN_URL . 'assets/css/club-infos-compact.css',
        ['ufsc-theme'],
        UFSC_GESTION_CLUB_VERSION
    );
    
    // Security check: ensure we have a valid club object
    if (!$club || !isset($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Erreur : données du club introuvables.</div>';
    }

    // Additional security: verify current user owns this club
    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé : vous ne pouvez accéder qu\'aux données de votre propre club.</div>';
    }

    $output = '<h2 class="ufsc-section-title">Profil du club</h2>';

    // Handle contact information update
    if (isset($_POST['ufsc_update_contact_submit']) && wp_verify_nonce($_POST['ufsc_contact_nonce'], 'ufsc_update_contact')) {
        $output .= ufsc_handle_contact_update($club);
    }

    // Main club information card
    $output .= '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">Informations générales</div>';
    $output .= '<div class="ufsc-card-body">';
    $output .= '<div class="ufsc-profile-grid">';

    // Club logo (if available)
    if (!empty($club->logo_url)) {
        $output .= '<div class="ufsc-profile-item ufsc-profile-logo">';
        $output .= '<div class="ufsc-profile-label">Logo du club</div>';
        $output .= '<div class="ufsc-profile-value">';
        $output .= '<img src="' . esc_url($club->logo_url) . '" alt="Logo ' . esc_attr($club->nom) . '" class="ufsc-club-logo">';
        $output .= '</div>';
        $output .= '</div>';
    }

    // Club name
    $output .= '<div class="ufsc-profile-item">';
    $output .= '<div class="ufsc-profile-label">Nom du club</div>';
    $output .= '<div class="ufsc-profile-value">' . esc_html($club->nom) . '</div>';
    $output .= '</div>';

    // Region
    $output .= '<div class="ufsc-profile-item">';
    $output .= '<div class="ufsc-profile-label">Région</div>';
    $output .= '<div class="ufsc-profile-value">' . esc_html($club->region ?? 'Non définie') . '</div>';
    $output .= '</div>';

    // Complete address
    $output .= '<div class="ufsc-profile-item">';
    $output .= '<div class="ufsc-profile-label">Adresse</div>';
    $output .= '<div class="ufsc-profile-value">';
    $output .= esc_html($club->adresse);
    if (!empty($club->complement_adresse)) {
        $output .= '<br>' . esc_html($club->complement_adresse);
    }
    $output .= '<br>' . esc_html($club->code_postal) . ' ' . esc_html($club->ville);
    $output .= '</div>';
    $output .= '</div>';

    // Email (editable)
    $output .= '<div class="ufsc-profile-item">';
    $output .= '<div class="ufsc-profile-label">Email</div>';
    $output .= '<div class="ufsc-profile-value">' . esc_html($club->email) . ' ';
    $output .= '<a href="#" class="ufsc-edit-contact-link" data-field="email" title="Modifier">';
    $output .= '<i class="dashicons dashicons-edit-page"></i>';
    $output .= '</a>';
    $output .= '</div>';
    $output .= '</div>';

    // Phone (editable)
    $output .= '<div class="ufsc-profile-item">';
    $output .= '<div class="ufsc-profile-label">Téléphone</div>';
    $output .= '<div class="ufsc-profile-value">' . esc_html($club->telephone) . ' ';
    $output .= '<a href="#" class="ufsc-edit-contact-link" data-field="telephone" title="Modifier">';
    $output .= '<i class="dashicons dashicons-edit-page"></i>';
    $output .= '</a>';
    $output .= '</div>';
    $output .= '</div>';

    // Website
    if (!empty($club->url_site)) {
        $output .= '<div class="ufsc-profile-item">';
        $output .= '<div class="ufsc-profile-label">Site internet</div>';
        $output .= '<div class="ufsc-profile-value">';
        $output .= '<a href="' . esc_url($club->url_site) . '" target="_blank" rel="noopener">';
        $output .= '<i class="dashicons dashicons-admin-site"></i> ' . esc_html($club->url_site);
        $output .= '</a>';
        $output .= '</div>';
        $output .= '</div>';
    }

    // Social media
    $social_links = [];
    if (!empty($club->url_facebook)) {
        $social_links[] = '<a href="' . esc_url($club->url_facebook) . '" target="_blank" rel="noopener" title="Facebook"><i class="dashicons dashicons-facebook"></i></a>';
    }
    if (!empty($club->url_instagram)) {
        $social_links[] = '<a href="' . esc_url($club->url_instagram) . '" target="_blank" rel="noopener" title="Instagram"><i class="dashicons dashicons-instagram"></i></a>';
    }
    
    if (!empty($social_links)) {
        $output .= '<div class="ufsc-profile-item">';
        $output .= '<div class="ufsc-profile-label">Réseaux sociaux</div>';
        $output .= '<div class="ufsc-profile-value ufsc-social-links">';
        $output .= implode(' ', $social_links);
        $output .= '</div>';
        $output .= '</div>';
    }

    // Structure type
    $output .= '<div class="ufsc-profile-item">';
    $output .= '<div class="ufsc-profile-label">Type de structure</div>';
    $output .= '<div class="ufsc-profile-value">' . esc_html($club->type ?? 'Non renseigné') . '</div>';
    $output .= '</div>';

    // SIREN
    if (!empty($club->siren)) {
        $output .= '<div class="ufsc-profile-item">';
        $output .= '<div class="ufsc-profile-label">SIREN</div>';
        $output .= '<div class="ufsc-profile-value">' . esc_html($club->siren) . '</div>';
        $output .= '</div>';
    }

    // RNA
    if (!empty($club->rna_number)) {
        $output .= '<div class="ufsc-profile-item">';
        $output .= '<div class="ufsc-profile-label">Numéro RNA</div>';
        $output .= '<div class="ufsc-profile-value">' . esc_html($club->rna_number) . '</div>';
        $output .= '</div>';
    }

    // Affiliation number (if available)
    if (!empty($club->num_affiliation)) {
        $output .= '<div class="ufsc-profile-item">';
        $output .= '<div class="ufsc-profile-label">Numéro d\'affiliation</div>';
        $output .= '<div class="ufsc-profile-value">' . esc_html($club->num_affiliation) . '</div>';
        $output .= '</div>';
    }

    // Affiliation date (if available)
    if (!empty($club->date_affiliation)) {
        $date_affiliation = new DateTime($club->date_affiliation);
        $output .= '<div class="ufsc-profile-item">';
        $output .= '<div class="ufsc-profile-label">Date d\'affiliation</div>';
        $output .= '<div class="ufsc-profile-value">' . esc_html($date_affiliation->format('d/m/Y')) . '</div>';
        $output .= '</div>';
    }

    $output .= '</div>'; // End profile-grid

    // Contact edit form (hidden by default)
    $output .= ufsc_render_contact_edit_form($club);

    // Action note
    $output .= '<div class="ufsc-action-note">';
    $output .= '<p>Pour toute autre modification de vos informations, veuillez contacter l\'administration par email.</p>';
    $output .= '<p><a href="mailto:demande-modification@ufsc-france.org?subject=Demande%20de%20modification%20-%20Club%20' . urlencode($club->nom) . '" class="ufsc-btn ufsc-btn-outline">';
    $output .= '<i class="dashicons dashicons-email"></i> Demander une modification</a></p>';
    $output .= '</div>';

    $output .= '</div>'; // End card-body
    $output .= '</div>'; // End card

    // Club leadership section
    $output .= ufsc_render_club_leadership($club);

    return $output;
}

/**
 * Render dashboard home/overview section
 *
 * @param object $club Club object
 * @return string HTML output for dashboard home
 */
function ufsc_club_render_home($club)
{
    // Security check
    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé.</div>';
    }

    $output = '';

    // Welcome block
    $output .= '<div class="ufsc-welcome-block">';
    $output .= '<h2>Bienvenue dans votre espace club</h2>';
    $output .= '<p>Gérez vos licences, téléchargez vos documents officiels et suivez votre affiliation.</p>';
    $output .= '</div>';

    // Get license statistics
    $club_manager = UFSC_Club_Manager::get_instance();
    $licences = $club_manager->get_licences_by_club($club->id);
    $stats = ufsc_calculate_licence_stats($licences, $club);

    // Statistics cards
    $output .= '<div class="ufsc-dashboard-stats">';
    
    $output .= '<div class="ufsc-stat-card">';
    $output .= '<div class="ufsc-stat-icon"><i class="dashicons dashicons-id"></i></div>';
    $output .= '<div class="ufsc-stat-value">' . $stats['total'] . '</div>';
    $output .= '<div class="ufsc-stat-label">Licences totales</div>';
    $output .= '</div>';

    $output .= '<div class="ufsc-stat-card">';
    $output .= '<div class="ufsc-stat-icon"><i class="dashicons dashicons-yes-alt"></i></div>';
    $output .= '<div class="ufsc-stat-value">' . $stats['active'] . '</div>';
    $output .= '<div class="ufsc-stat-label">Licences actives</div>';
    $output .= '</div>';

    $output .= '<div class="ufsc-stat-card">';
    $output .= '<div class="ufsc-stat-icon"><i class="dashicons dashicons-chart-bar"></i></div>';
    $output .= '<div class="ufsc-stat-value' . ($stats['remaining'] <= 0 ? ' ufsc-text-danger' : '') . '">' . $stats['remaining'] . '</div>';
    $output .= '<div class="ufsc-stat-label">Licences disponibles</div>';
    $output .= '</div>';

    $output .= '<div class="ufsc-stat-card">';
    $output .= '<div class="ufsc-stat-icon"><i class="dashicons dashicons-calendar-alt"></i></div>';
    $output .= '<div class="ufsc-stat-value' . ($stats['expiring'] > 0 ? ' ufsc-text-warning' : '') . '">' . $stats['expiring'] . '</div>';
    $output .= '<div class="ufsc-stat-label">Licences expirant bientôt</div>';
    $output .= '</div>';

    $output .= '</div>';

    // License quota section
    $output .= ufsc_render_quota_section($club, $stats);

    // Quick downloads section (only for active clubs)
    // CORRECTION: Use standardized status checking for quick downloads
    if (ufsc_is_club_active($club)) {
        $output .= ufsc_render_quick_downloads($club);
    }

    // Recent licenses table
    if (!empty($licences)) {
        $output .= ufsc_render_recent_licences($licences);
    }

    return $output;
}

/**
 * Handle contact information update
 *
 * @param object $club Club object
 * @return string Success or error message HTML
 */
function ufsc_handle_contact_update($club)
{
    // Security checks
    // CORRECTION: Updated to use responsable_id instead of user_id for proper club-user association
    if (!current_user_can('edit_posts') && get_current_user_id() != $club->responsable_id) {
        return '<div class="ufsc-alert ufsc-alert-error">Permission refusée.</div>';
    }

    $club_id = intval($_POST['club_id']);
    $field_name = sanitize_text_field($_POST['field_name']);

    // Verify club ownership
    if ($club_id !== $club->id || !ufsc_verify_club_access($club_id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé.</div>';
    }

    $allowed_fields = ['email', 'telephone'];
    if (!in_array($field_name, $allowed_fields)) {
        return '<div class="ufsc-alert ufsc-alert-error">Champ non autorisé.</div>';
    }

    $new_value = sanitize_text_field($_POST[$field_name]);

    // Validate email if updating email
    if ($field_name === 'email' && !is_email($new_value)) {
        return '<div class="ufsc-alert ufsc-alert-error">Format d\'email invalide.</div>';
    }

    // Update the field
    $club_manager = UFSC_Club_Manager::get_instance();
    $success = $club_manager->update_club_field($club_id, $field_name, $new_value);

    if ($success) {
        return '<div class="ufsc-alert ufsc-alert-success">Coordonnées mises à jour avec succès.</div>';
    } else {
        return '<div class="ufsc-alert ufsc-alert-error">Erreur lors de la mise à jour.</div>';
    }
}

/**
 * Render contact edit form
 *
 * @param object $club Club object
 * @return string Edit form HTML
 */
function ufsc_render_contact_edit_form($club)
{
    $output = '<div id="ufsc-edit-contact-form" style="display:none; margin-top:20px; padding:15px; background:#f9f9f9; border-radius:5px;">';
    $output .= '<h4>Modifier mes coordonnées</h4>';
    $output .= '<form method="post" class="ufsc-inline-form">';
    $output .= wp_nonce_field('ufsc_update_contact', 'ufsc_contact_nonce', true, false);
    $output .= '<input type="hidden" name="ufsc_update_contact_submit" value="1">';
    $output .= '<input type="hidden" name="club_id" value="' . esc_attr($club->id) . '">';
    $output .= '<input type="hidden" id="ufsc-edit-field-name" name="field_name" value="">';

    // Email field
    $output .= '<div id="ufsc-edit-email-field" style="display:none">';
    $output .= '<label for="email">Adresse email:</label>';
    $output .= '<input type="email" name="email" id="email" value="' . esc_attr($club->email) . '" required>';
    $output .= '</div>';

    // Phone field
    $output .= '<div id="ufsc-edit-telephone-field" style="display:none">';
    $output .= '<label for="telephone">Téléphone:</label>';
    $output .= '<input type="tel" name="telephone" id="telephone" value="' . esc_attr($club->telephone) . '">';
    $output .= '</div>';

    // Action buttons
    $output .= '<div style="margin-top:10px;">';
    $output .= '<button type="submit" class="ufsc-btn">Enregistrer</button>';
    $output .= '<button type="button" class="ufsc-btn ufsc-btn-outline" id="ufsc-cancel-edit">Annuler</button>';
    $output .= '</div>';

    $output .= '</form>';
    $output .= '</div>';

    return $output;
}

/**
 * Render club leadership section
 *
 * @param object $club Club object
 * @return string Leadership section HTML
 */
function ufsc_render_club_leadership($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">Dirigeants du club</div>';
    $output .= '<div class="ufsc-card-body">';
    $output .= '<div class="ufsc-dirigeants-grid">';

    $roles = [
        'president' => 'Président',
        'secretaire' => 'Secrétaire',
        'tresorier' => 'Trésorier',
        'entraineur' => 'Entraîneur'
    ];

    $has_leadership = false;
    foreach ($roles as $key => $label) {
        if (!empty($club->{$key . '_nom'})) {
            $has_leadership = true;
            $output .= '<div class="ufsc-dirigeant-card">';
            $output .= '<div class="ufsc-dirigeant-role">' . esc_html($label) . '</div>';
            $output .= '<div class="ufsc-dirigeant-name">' . esc_html($club->{$key . '_nom'}) . '</div>';

            if (!empty($club->{$key . '_email'})) {
                $output .= '<div class="ufsc-dirigeant-contact">';
                $output .= '<i class="dashicons dashicons-email"></i> ' . esc_html($club->{$key . '_email'});
                $output .= '</div>';
            }

            if (!empty($club->{$key . '_tel'})) {
                $output .= '<div class="ufsc-dirigeant-contact">';
                $output .= '<i class="dashicons dashicons-phone"></i> ' . esc_html($club->{$key . '_tel'});
                $output .= '</div>';
            }

            $output .= '</div>';
        }
    }

    if (!$has_leadership) {
        $output .= '<p>Aucun dirigeant renseigné. Contactez l\'administration pour mettre à jour ces informations.</p>';
    }

    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Calculate license statistics
 *
 * @param array $licences Array of license objects
 * @param object $club Club object
 * @return array Statistics array
 */
function ufsc_calculate_licence_stats($licences, $club)
{
    $stats = [
        'total' => count($licences),
        'active' => 0,
        'expiring' => 0,
        'quota_total' => intval($club->quota_licences ?? 0),
        'remaining' => 0
    ];

    foreach ($licences as $licence) {
        if ($licence->statut === 'active') {
            $stats['active']++;

            // Check if expiring within 30 days
            $expiry_date = strtotime($licence->date_expiration);
            $thirty_days = strtotime('+30 days');
            if ($expiry_date < $thirty_days) {
                $stats['expiring']++;
            }
        }
    }

    $stats['remaining'] = max(0, $stats['quota_total'] - $stats['total']);

    return $stats;
}

/**
 * Render quota section
 *
 * @param object $club Club object
 * @param array $stats License statistics
 * @return string Quota section HTML
 */
function ufsc_render_quota_section($club, $stats)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">Quota de licences</div>';
    $output .= '<div class="ufsc-card-body">';

    if ($stats['quota_total'] > 0) {
        $quota_percentage = min(100, ($stats['total'] / $stats['quota_total']) * 100);
        
        $output .= '<div class="ufsc-quota-box">';
        $output .= '<div class="ufsc-quota-info">';
        $output .= '<span>Utilisation: ' . $stats['total'] . ' / ' . $stats['quota_total'] . '</span>';
        $output .= '<span>' . round($quota_percentage) . '%</span>';
        $output .= '</div>';
        $output .= '<div class="ufsc-quota-progress">';
        $output .= '<div class="ufsc-quota-bar" style="width: ' . $quota_percentage . '%;"></div>';
        $output .= '</div>';

        if ($stats['remaining'] <= 0) {
            $output .= '<div class="ufsc-form-hint" style="color:var(--ufsc-red); margin-top:8px;">';
            $output .= 'Votre quota est épuisé. <a href="mailto:contact@ufsc-france.org?subject=Demande%20d%27augmentation%20de%20quota">Contactez l\'administration</a> pour l\'augmenter.';
            $output .= '</div>';
        } else {
            $output .= '<div class="ufsc-form-hint" style="margin-top:8px;">';
            $output .= 'Licences incluses dans votre pack d\'affiliation. ';
            $output .= '<a href="' . esc_url(add_query_arg(['section' => 'licences', 'action' => 'new'], get_permalink())) . '">Ajouter une licence</a>';
            $output .= '</div>';
        }

        $output .= '</div>';
    } else {
        $output .= '<p>Aucun quota de licences défini pour votre club.</p>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render quick downloads section
 *
 * @param object $club Club object
 * @return string Downloads section HTML
 */
function ufsc_render_quick_downloads($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">Téléchargements rapides</div>';
    $output .= '<div class="ufsc-card-body">';
    $output .= '<div class="ufsc-download-buttons">';

    $downloads = [
        [
            'label' => 'Attestation d\'affiliation',
            'url' => add_query_arg([
                'action' => 'attestation_affiliation',
                'club_id' => $club->id,
                'nonce' => wp_create_nonce('ufsc_attestation_' . $club->id)
            ])
        ],
        [
            'label' => 'Attestation d\'assurance',
            'url' => add_query_arg([
                'action' => 'attestation_assurance', 
                'club_id' => $club->id,
                'nonce' => wp_create_nonce('ufsc_attestation_' . $club->id)
            ])
        ]
    ];

    foreach ($downloads as $download) {
        $output .= '<a href="' . esc_url($download['url']) . '" class="ufsc-download-btn">';
        $output .= '<i class="dashicons dashicons-download"></i>';
        $output .= '<span>' . esc_html($download['label']) . '</span>';
        $output .= '</a>';
    }

    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render recent licenses table
 *
 * @param array $licences Array of license objects
 * @return string Recent licenses HTML
 */
function ufsc_render_recent_licences($licences)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<span>Dernières licences</span>';
    $output .= '<a href="' . esc_url(add_query_arg(['section' => 'licences'], get_permalink())) . '" class="ufsc-btn-text">Voir toutes</a>';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';
    $output .= '<div class="ufsc-table-responsive">';
    $output .= '<table class="ufsc-table">';
    $output .= '<thead>';
    $output .= '<tr><th>Nom</th><th>Prénom</th><th>Fonction</th><th>Statut</th></tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    // Show last 5 licenses
    $recent_licences = array_slice($licences, 0, 5);

    foreach ($recent_licences as $licence) {
        $status_info = ufsc_get_licence_status_info($licence);
        
        $output .= '<tr>';
        $output .= '<td>' . esc_html($licence->nom) . '</td>';
        $output .= '<td>' . esc_html($licence->prenom) . '</td>';
        $output .= '<td>' . esc_html($licence->fonction ?? '-') . '</td>';
        $output .= '<td><span class="ufsc-badge ' . $status_info['class'] . '">' . $status_info['text'] . '</span></td>';
        $output .= '</tr>';
    }

    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Get license status information
 *
 * @param object $licence License object
 * @return array Status info with class and text
 */
function ufsc_get_licence_status_info($licence)
{
    $status_class = 'ufsc-badge-inactive';
    $status_text = 'Inactive';

    if ($licence->statut === 'active' || $licence->statut === 'validee') {
        $expiry_date = new DateTime($licence->date_expiration);
        $today = new DateTime();
        $is_expired = $expiry_date < $today;
        $days_remaining = $today->diff($expiry_date)->days;

        if ($is_expired) {
            $status_class = 'ufsc-badge-inactive';
            $status_text = 'Expirée';
        } elseif ($days_remaining <= 30) {
            $status_class = 'ufsc-badge-pending';
            $status_text = 'Expire bientôt';
        } else {
            $status_class = 'ufsc-badge-active';
            $status_text = 'Active';
        }
    } elseif ($licence->statut === 'pending' || $licence->statut === 'en_attente') {
        $status_class = 'ufsc-badge-pending';
        $status_text = 'En attente';
    } elseif ($licence->statut === 'refusee') {
        $status_class = 'ufsc-badge-inactive';
        $status_text = 'Refusée';
    }

    return [
        'class' => $status_class,
        'text' => $status_text
    ];
}
