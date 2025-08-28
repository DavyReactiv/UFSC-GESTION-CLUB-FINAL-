<?php

/**
 * Attestations Management for Club Dashboard
 * 
 * Handles certificate and attestation downloads for clubs in the frontend dashboard.
 * Provides secure access with proper club ownership verification.
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
 * Render attestations section for club dashboard
 *
 * @param object $club Club object
 * @return string HTML output for attestations section
 */
function ufsc_club_render_attestations($club)
{
    // Security check: verify club access
    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé : vous ne pouvez accéder qu\'aux données de votre propre club.</div>';
    }

    $output = '<h2 class="ufsc-section-title">Attestations</h2>';

    // CORRECTION: Use standardized status checking and alert rendering
    if (!ufsc_is_club_active($club)) {
        $output .= ufsc_render_club_status_alert($club, 'attestation');
        return $output;
    }

    // Main attestations section - only insurance and affiliation
    $output .= ufsc_render_official_attestations($club);

    // License-related attestations (added by admin)
    $output .= ufsc_render_licence_attestations($club);

    return $output;
}

/**
 * Render official attestations section
 *
 * @param object $club Club object
 * @return string HTML output for official attestations
 */
function ufsc_render_official_attestations($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-awards"></i> Attestations officielles UFSC';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    $output .= '<div class="ufsc-info-box ufsc-mb-20">
                <p>Ces documents sont transmis après validation par l\'administration UFSC.</p>
                </div>';

    $output .= '<div class="ufsc-attestations-grid">';

    // Only show insurance and affiliation attestations as per requirements
    $attestations = [
        'affiliation' => [
            'title' => 'Attestation d\'affiliation',
            'description' => 'Certifie l\'affiliation de votre club à l\'UFSC',
            'icon' => 'yes-alt',
            'status' => 'available',
            'file_field' => 'doc_attestation_affiliation'
        ],
        'assurance' => [
            'title' => 'Attestation d\'assurance',
            'description' => 'Justifie la couverture d\'assurance fédérale',
            'icon' => 'shield',
            'status' => 'available',
            'file_field' => 'doc_attestation_assurance'
        ]
    ];

    foreach ($attestations as $key => $attestation) {
        $output .= '<div class="ufsc-attestation-card">';
        $output .= '<div class="ufsc-attestation-header">';
        $output .= '<div class="ufsc-attestation-icon">';
        $output .= '<i class="dashicons dashicons-' . $attestation['icon'] . '"></i>';
        $output .= '</div>';
        $output .= '<div class="ufsc-attestation-info">';
        $output .= '<h4>' . esc_html($attestation['title']) . '</h4>';
        $output .= '<p>' . esc_html($attestation['description']) . '</p>';
        $output .= '</div>';
        $output .= '</div>';

        $output .= '<div class="ufsc-attestation-actions">';
        
        // Check if attestation is available using new helper functions
        $is_available = false;
        $attestation_url = '';
        
        if ($key === 'affiliation') {
            $attestation_url = ufsc_get_club_attestation_url($club->id, 'affiliation');
            $is_available = !empty($attestation_url);
        } elseif ($key === 'assurance') {
            $attestation_url = ufsc_get_club_attestation_url($club->id, 'assurance');
            $is_available = !empty($attestation_url);
        } elseif (isset($attestation['file_field'])) {
            // Legacy check for other attestations
            $is_available = !empty($club->{$attestation['file_field']});
        }

        if ($is_available && $attestation['status'] === 'available') {
            // Generate download link
            $download_url = ufsc_get_attestation_download_url($key, $club->id);
            $output .= '<a href="' . esc_url($download_url) . '" class="ufsc-btn ufsc-btn-primary">';
            $output .= '<i class="dashicons dashicons-download"></i> Télécharger';
            $output .= '</a>';
            
            // Add view button for PDF/images if direct URL is available
            if ($attestation_url) {
                $file_extension = ufsc_get_attestation_file_extension($attestation_url);
                if (in_array($file_extension, ['pdf', 'jpg', 'jpeg', 'png'])) {
                    $output .= '<a href="' . esc_url($attestation_url) . '" target="_blank" class="ufsc-btn ufsc-btn-outline">';
                    $output .= '<i class="dashicons dashicons-visibility"></i> Voir';
                    $output .= '</a>';
                }
            }

            // Generate email link
            $email_url = ufsc_get_attestation_email_url($key, $club);
            $output .= '<a href="' . esc_url($email_url) . '" class="ufsc-btn ufsc-btn-outline">';
            $output .= '<i class="dashicons dashicons-email"></i> Envoyer par email';
            $output .= '</a>';
        } else {
            $output .= '<span class="ufsc-btn ufsc-btn-disabled">Non disponible</span>';
            $output .= '<p class="ufsc-attestation-note">Cette attestation sera transmise après validation par l\'administration.</p>';
        }

        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render license-related attestations section
 *
 * @param object $club Club object
 * @return string HTML output for license attestations
 */
function ufsc_render_licence_attestations($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-id"></i> Attestations de licences';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    $output .= '<div class="ufsc-info-box ufsc-mb-20">
                <p><strong>Note :</strong> Les attestations de licence sont ajoutées en back-office par l\'administration pour chaque licence validée.</p>
                </div>';

    // Get club licenses - only show validated licenses
    $club_manager = UFSC_Club_Manager::get_instance();
    $licences = $club_manager->get_licences_by_club($club->id);
    $validated_licences = array_filter($licences, function($licence) {
        return isset($licence->statut) && $licence->statut === 'validee';
    });

    if (empty($validated_licences)) {
        $output .= '<div class="ufsc-empty-state">';
        $output .= '<div class="ufsc-empty-icon"><i class="dashicons dashicons-id"></i></div>';
        $output .= '<h3>Aucune licence validée</h3>';
        $output .= '<p>Vous devez avoir des licences validées pour accéder aux attestations individuelles.</p>';
        $output .= '<p>Les licences sont validées après paiement et validation par l\'administration.</p>';
        $output .= '<p><a href="' . esc_url(add_query_arg(['view' => 'licences'], get_permalink())) . '" class="ufsc-btn">Gérer les licences</a></p>';
        $output .= '</div>';
    } else {
        $output .= '<p>Attestations disponibles pour vos licenciés validés :</p>';
        
        $output .= '<div class="ufsc-licences-attestations">';
        
        // Bulk actions
        $output .= '<div class="ufsc-bulk-actions">';
        $output .= '<a href="' . esc_url(ufsc_get_bulk_attestation_url($club->id)) . '" class="ufsc-btn ufsc-btn-red">';
        $output .= '<i class="dashicons dashicons-download"></i> Télécharger toutes les attestations';
        $output .= '</a>';
        $output .= '</div>';

        // Individual license attestations table
        $output .= '<div class="ufsc-table-responsive">';
        $output .= '<table class="ufsc-table ufsc-table--static ufsc-table-compact">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th>Nom</th>';
        $output .= '<th>Prénom</th>';
        $output .= '<th>Fonction</th>';
        $output .= '<th>Statut</th>';
        $output .= '<th>Actions</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';

        foreach ($validated_licences as $licence) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($licence->nom) . '</td>';
            $output .= '<td>' . esc_html($licence->prenom) . '</td>';
            $output .= '<td>' . esc_html($licence->fonction ?? '-') . '</td>';
            $output .= '<td>';
            $output .= '<span class="ufsc-badge ufsc-badge-active">Validée</span>';
            $output .= '</td>';
            $output .= '<td>';
            $download_url = ufsc_get_licence_attestation_url($licence->id, $club->id);
            $output .= '<a href="' . esc_url($download_url) . '" class="ufsc-btn ufsc-btn-small">';
            $output .= '<i class="dashicons dashicons-download"></i> Télécharger';
            $output .= '</a>';
            
            $output .= '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render custom attestation requests section
 *
 * @param object $club Club object
 * @return string HTML output for custom attestation requests
 */
function ufsc_render_custom_attestation_requests($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-edit"></i> Demandes d\'attestations spécifiques';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    $output .= '<p>Besoin d\'une attestation spécifique non disponible ci-dessus ?</p>';
    
    $output .= '<div class="ufsc-custom-attestation-options">';
    
    // Common custom attestation types
    $custom_types = [
        'participation_competition' => 'Attestation de participation à une compétition',
        'formation_dirigeant' => 'Attestation de formation de dirigeant',
        'stage_technique' => 'Attestation de stage technique',
        'qualification_arbitre' => 'Attestation de qualification d\'arbitre',
        'autre' => 'Autre demande spécifique'
    ];

    $output .= '<form method="post" class="ufsc-custom-attestation-form">';
    $output .= wp_nonce_field('ufsc_request_custom_attestation', 'ufsc_custom_attestation_nonce', true, false);
    if (current_user_can('ufsc_manage')) {
        $output .= '<input type="hidden" name="club_id" value="' . esc_attr($club->id) . '">';
    }

    $output .= '<div class="ufsc-form-row">';
    $output .= '<label for="attestation_type">Type d\'attestation demandée :</label>';
    $output .= '<div>';
    $output .= '<select name="attestation_type" id="attestation_type" required>';
    $output .= '<option value="">-- Sélectionner --</option>';
    foreach ($custom_types as $value => $label) {
        $output .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
    }
    $output .= '</select>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="ufsc-form-row">';
    $output .= '<label for="attestation_details">Détails de la demande :</label>';
    $output .= '<div>';
    $output .= '<textarea name="attestation_details" id="attestation_details" rows="4" placeholder="Décrivez précisément votre demande d\'attestation..." required></textarea>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="ufsc-form-row">';
    $output .= '<label for="contact_email">Email de contact :</label>';
    $output .= '<div>';
    $output .= '<input type="email" name="contact_email" id="contact_email" value="' . esc_attr($club->email) . '" required>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '<div class="ufsc-form-row">';
    $output .= '<div></div>';
    $output .= '<div>';
    $output .= '<button type="submit" name="ufsc_submit_custom_attestation" class="ufsc-btn ufsc-btn-primary">';
    $output .= '<i class="dashicons dashicons-email"></i> Envoyer la demande';
    $output .= '</button>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '</form>';
    $output .= '</div>';

    // Handle form submission
    if (isset($_POST['ufsc_submit_custom_attestation'])) {
        $output .= ufsc_handle_custom_attestation_request($club);
    }

    $output .= '<div class="ufsc-form-note">';
    $output .= '<p><strong>Note :</strong> Les demandes d\'attestations spécifiques sont traitées manuellement par notre équipe. Vous recevrez une réponse sous 3-5 jours ouvrés.</p>';
    $output .= '</div>';

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Get download URL for attestation
 *
 * @param string $attestation_type Type of attestation
 * @param int $club_id Club ID
 * @return string Download URL
 */
function ufsc_get_attestation_download_url($attestation_type, $club_id)
{
    $args = [
        'action' => 'ufsc_download_attestation',
        'type' => $attestation_type,
        'nonce' => wp_create_nonce('ufsc_attestation_' . $attestation_type . '_' . $club_id)
    ];
    if (current_user_can('ufsc_manage')) {
        $args['club_id'] = $club_id;
    }
    return add_query_arg($args, admin_url('admin-ajax.php'));
}

/**
 * Get email URL for attestation
 *
 * @param string $attestation_type Type of attestation
 * @param object $club Club object
 * @return string Email URL
 */
function ufsc_get_attestation_email_url($attestation_type, $club)
{
    $subject = 'Demande d\'envoi par email - Attestation ' . $attestation_type;
    $body = "Bonjour,\n\nPourriez-vous m'envoyer par email l'attestation " . $attestation_type . " pour notre club ?\n\n";
    $body .= "Club: " . $club->nom . "\n";
    $body .= "N° d'affiliation: " . ($club->num_affiliation ?? 'En cours') . "\n\n";
    $body .= "Merci.\n\nCordialement.";

    return 'mailto:attestations@ufsc-france.org?subject=' . urlencode($subject) . '&body=' . urlencode($body);
}

/**
 * Get bulk attestation download URL
 *
 * @param int $club_id Club ID
 * @return string Bulk download URL
 */
function ufsc_get_bulk_attestation_url($club_id)
{
    $args = [
        'action' => 'ufsc_download_bulk_attestations',
        'nonce' => wp_create_nonce('ufsc_bulk_attestations_' . $club_id)
    ];
    if (current_user_can('ufsc_manage')) {
        $args['club_id'] = $club_id;
    }
    return add_query_arg($args, admin_url('admin-ajax.php'));
}

/**
 * Get license attestation download URL
 *
 * @param int $licence_id License ID
 * @param int $club_id Club ID
 * @return string License attestation URL
 */
function ufsc_get_licence_attestation_url($licence_id, $club_id)
{
    $args = [
        'action' => 'ufsc_download_licence_attestation',
        'licence_id' => $licence_id,
        'nonce' => wp_create_nonce('ufsc_licence_attestation_' . $licence_id . '_' . $club_id)
    ];
    if (current_user_can('ufsc_manage')) {
        $args['club_id'] = $club_id;
    }
    return add_query_arg($args, admin_url('admin-ajax.php'));
}

/**
 * Handle custom attestation request
 *
 * @param object $club Club object
 * @return string Success or error message HTML
 */
function ufsc_handle_custom_attestation_request($club)
{
    // Security checks
    if (!wp_verify_nonce($_POST['ufsc_custom_attestation_nonce'], 'ufsc_request_custom_attestation')) {
        return '<div class="ufsc-alert ufsc-alert-error">Erreur de sécurité. Veuillez réessayer.</div>';
    }

    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé.</div>';
    }

    // Validate form data
    $attestation_type = sanitize_text_field($_POST['attestation_type']);
    $details = sanitize_textarea_field($_POST['attestation_details']);
    $contact_email = sanitize_email($_POST['contact_email']);

    if (empty($attestation_type) || empty($details) || !is_email($contact_email)) {
        return '<div class="ufsc-alert ufsc-alert-error">Veuillez remplir tous les champs obligatoires.</div>';
    }

    // Send email to administration
    $to = 'attestations@ufsc-france.org';
    $subject = 'Demande d\'attestation spécifique - Club ' . $club->nom;
    
    $message = "Nouvelle demande d'attestation spécifique\n\n";
    $message .= "Club: " . $club->nom . "\n";
    $message .= "Ville: " . $club->ville . "\n";
    $message .= "N° d'affiliation: " . ($club->num_affiliation ?? 'En cours') . "\n";
    $message .= "Email de contact: " . $contact_email . "\n\n";
    $message .= "Type d'attestation: " . $attestation_type . "\n\n";
    $message .= "Détails:\n" . $details . "\n\n";
    $message .= "Demande soumise le: " . current_time('d/m/Y à H:i') . "\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $contact_email
    ];

    if (wp_mail($to, $subject, $message, $headers)) {
        return '<div class="ufsc-alert ufsc-alert-success">Votre demande a été envoyée avec succès. Vous recevrez une réponse sous 3-5 jours ouvrés.</div>';
    } else {
        return '<div class="ufsc-alert ufsc-alert-error">Erreur lors de l\'envoi de la demande. Veuillez réessayer ou nous contacter directement.</div>';
    }
}