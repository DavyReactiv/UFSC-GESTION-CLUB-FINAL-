<?php

/**
 * Document Management for Club Dashboard
 * 
 * Handles document display and management for clubs in the frontend dashboard.
 * Provides secure access to club documents with proper ownership verification.
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
 * Render documents section for club dashboard
 *
 * @param object $club Club object
 * @return string HTML output for documents section
 */
function ufsc_club_render_documents($club)
{
    // Security check: verify club access
    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé : vous ne pouvez accéder qu\'aux données de votre propre club.</div>';
    }

    $output = '<h2 class="ufsc-section-title">Documents</h2>';

    // Display upload result message if present
    if (isset($_GET['doc_update']) && isset($_GET['message'])) {
        $update_status = sanitize_text_field( wp_unslash( $_GET['doc_update'] ) );
        $message = sanitize_text_field( urldecode( wp_unslash( $_GET['message'] ) ) );
        
        if ($update_status === 'success') {
            $output .= '<div class="ufsc-alert ufsc-alert-success">';
            $output .= '<h4>Mise à jour réussie</h4>';
            $output .= '<p>' . esc_html($message) . '</p>';
            $output .= '</div>';
        } else {
            $output .= '<div class="ufsc-alert ufsc-alert-error">';
            $output .= '<h4>Erreur lors de la mise à jour</h4>';
            $output .= '<p>' . esc_html($message) . '</p>';
            $output .= '</div>';
        }
    }

    // Official UFSC documents section
    $output .= ufsc_render_official_documents($club);

    // Club submitted documents section
    $output .= ufsc_render_club_documents($club);

    // General documents/resources section
    $output .= ufsc_render_general_documents();

    return $output;
}

/**
 * Render official UFSC documents section
 *
 * @param object $club Club object
 * @return string HTML output for official documents
 */
function ufsc_render_official_documents($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-shield-alt"></i> Documents officiels UFSC';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    // CORRECTION: Use standardized status checking for document availability
    if (!ufsc_is_club_active($club)) {
        $output .= '<div class="ufsc-alert ufsc-alert-info">';
        $output .= '<p>Les documents officiels seront disponibles une fois votre club affilié et validé.</p>';
        $output .= '</div>';
    } else {
        $output .= '<div class="ufsc-documents-grid">';

        // Official documents list
        $official_docs = [
            'attestation_affiliation' => [
                'title' => 'Attestation d\'affiliation',
                'description' => 'Document officiel attestant votre affiliation à l\'UFSC',
                'icon' => 'media-document',
                'action' => 'attestation_affiliation',
                'available' => ufsc_club_has_admin_attestation($club->id, 'affiliation') || (!empty($club->doc_attestation_affiliation) || ufsc_is_club_active($club))
            ],
            'attestation_assurance' => [
                'title' => 'Attestation d\'assurance',
                'description' => 'Justificatif d\'assurance fédérale pour votre club',
                'icon' => 'shield',
                'action' => 'attestation_assurance',
                'available' => ufsc_club_has_admin_attestation($club->id, 'assurance') || ufsc_is_club_active($club)
            ],
            'carte_affiliation' => [
                'title' => 'Carte d\'affiliation',
                'description' => 'Carte d\'affiliation officielle de votre club',
                'icon' => 'id-alt',
                'action' => 'carte_affiliation',
                'available' => ufsc_is_club_active($club)
            ]
        ];

        foreach ($official_docs as $doc_key => $doc_info) {
            $output .= '<div class="ufsc-document-item">';
            $output .= '<div class="ufsc-document-icon">';
            $output .= '<i class="dashicons dashicons-' . $doc_info['icon'] . '"></i>';
            $output .= '</div>';
            $output .= '<div class="ufsc-document-info">';
            $output .= '<h4>' . esc_html($doc_info['title']) . '</h4>';
            $output .= '<p>' . esc_html($doc_info['description']) . '</p>';
            $output .= '</div>';
            $output .= '<div class="ufsc-document-actions">';

            if ($doc_info['available']) {
                $download_url = ufsc_get_attestation_download_url($doc_info['action'], $club->id);
                $output .= '<a href="' . esc_url($download_url) . '" class="ufsc-btn ufsc-btn-primary">';
                $output .= '<i class="dashicons dashicons-download"></i> Télécharger';
                $output .= '</a>';
            } else {
                $output .= '<span class="ufsc-btn ufsc-btn-disabled">Non disponible</span>';
            }

            $output .= '</div>';
            $output .= '</div>';
        }

        $output .= '</div>';
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render club submitted documents section
 *
 * @param object $club Club object
 * @return string HTML output for club documents
 */
function ufsc_render_club_documents($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-portfolio"></i> Documents transmis par le club';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    // Documents that clubs typically submit
    $club_docs = [
        'statuts' => [
            'title' => 'Statuts du club',
            'description' => 'Statuts officiels de votre association',
            'field' => 'statuts'
        ],
        'recepisse' => [
            'title' => 'Récépissé de déclaration',
            'description' => 'Récépissé de déclaration en préfecture',
            'field' => 'recepisse'
        ],
        'jo' => [
            'title' => 'Parution au Journal Officiel',
            'description' => 'Justificatif de parution au JO',
            'field' => 'jo'
        ],
        'pv_ag' => [
            'title' => 'Dernier PV d\'Assemblée Générale',
            'description' => 'Procès-verbal de la dernière AG',
            'field' => 'pv_ag'
        ],
        'cer' => [
            'title' => 'Contrat d\'Engagement Républicain',
            'description' => 'CER signé par le club',
            'field' => 'cer'
        ],
        'attestation_cer' => [
            'title' => 'Attestation liée au CER',
            'description' => 'Attestation complémentaire au CER',
            'field' => 'attestation_cer'
        ]
    ];

    $has_documents = false;
    $documents_grid = '<div class="ufsc-documents-grid">';

    foreach ($club_docs as $doc_key => $doc_info) {
        $field_value = $club->{$doc_info['field']} ?? '';
        
        if (!empty($field_value)) {
            $has_documents = true;
            $documents_grid .= '<div class="ufsc-document-item">';
            $documents_grid .= '<div class="ufsc-document-icon">';
            $documents_grid .= '<i class="dashicons dashicons-media-document"></i>';
            $documents_grid .= '</div>';
            $documents_grid .= '<div class="ufsc-document-info">';
            $documents_grid .= '<h4>' . esc_html($doc_info['title']) . '</h4>';
            $documents_grid .= '<p>' . esc_html($doc_info['description']) . '</p>';
            $documents_grid .= '</div>';
            $documents_grid .= '<div class="ufsc-document-actions">';
            
            // Secure document view link
            $view_url = ufsc_get_secure_document_url($field_value, $club->id);
            $documents_grid .= '<a href="' . esc_url($view_url) . '" target="_blank" class="ufsc-btn">';
            $documents_grid .= '<i class="dashicons dashicons-visibility"></i> Voir';
            $documents_grid .= '</a>';
            
            $documents_grid .= '</div>';
            $documents_grid .= '</div>';
        }
    }

    $documents_grid .= '</div>';

    if (!$has_documents) {
        $output .= '<div class="ufsc-empty-state">';
        $output .= '<div class="ufsc-empty-icon"><i class="dashicons dashicons-media-document"></i></div>';
        $output .= '<h3>Aucun document transmis</h3>';
        $output .= '<p>Vous n\'avez pas encore transmis de documents pour votre club.</p>';
        $output .= '<p>Ces documents sont généralement fournis lors du processus d\'affiliation.</p>';
        $output .= '</div>';
    } else {
        $output .= $documents_grid;
    }

    // Document upload form for club members
    $output .= '<div class="ufsc-document-upload">';
    $output .= '<h4>Mettre à jour vos documents</h4>';
    $output .= '<p>Vous pouvez télécharger ou remplacer vos documents directement depuis cette interface.</p>';
    
    $output .= '<form method="post" enctype="multipart/form-data" class="ufsc-document-form">';
    $output .= wp_nonce_field('ufsc_update_club_documents', 'ufsc_update_documents_nonce', true, false);
    $output .= '<input type="hidden" name="action" value="ufsc_update_club_documents">';
    
    foreach ($club_docs as $doc_key => $doc_info) {
        $current_file = $club->{$doc_info['field']} ?? '';
        $output .= '<div class="ufsc-document-upload-item">';
        $output .= '<label for="' . $doc_key . '">' . esc_html($doc_info['title']) . '</label>';
        $output .= '<input type="file" name="' . $doc_key . '" id="' . $doc_key . '" accept=".pdf,.jpg,.jpeg,.png">';
        $output .= '<small>Formats acceptés: PDF, JPG, PNG. Taille max: 5 MB</small>';
        
        if (!empty($current_file)) {
            $output .= '<div class="current-file">';
            $output .= '<span class="dashicons dashicons-yes"></span> ';
            $output .= '<small>Document actuel disponible</small>';
            $output .= '</div>';
        }
        $output .= '</div>';
    }
    
    $output .= '<div class="ufsc-form-actions">';
    $output .= '<button type="submit" class="ufsc-btn ufsc-btn-primary">';
    $output .= '<i class="dashicons dashicons-upload"></i> Mettre à jour les documents';
    $output .= '</button>';
    $output .= '</div>';
    $output .= '</form>';
    $output .= '</div>';

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render general documents and resources section
 *
 * @return string HTML output for general documents
 */
function ufsc_render_general_documents()
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-book"></i> Documents généraux et ressources';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';
    $output .= '<div class="ufsc-documents-grid">';

    // General resources available to all clubs
    $general_docs = [
        'reglement_interieur' => [
            'title' => 'Règlement intérieur UFSC',
            'description' => 'Règlement intérieur de la fédération',
            'icon' => 'book',
            'url' => UFSC_PLUGIN_URL . 'assets/docs/reglement_interieur.pdf',
            'available' => true
        ],
        'guide_affiliation' => [
            'title' => 'Guide d\'affiliation',
            'description' => 'Guide complet pour l\'affiliation des clubs',
            'icon' => 'info',
            'url' => UFSC_PLUGIN_URL . 'assets/docs/guide_affiliation.pdf',
            'available' => true
        ],
        'modeles_documents' => [
            'title' => 'Modèles de documents',
            'description' => 'Modèles de documents utiles pour votre club',
            'icon' => 'format-aside',
            'url' => UFSC_PLUGIN_URL . 'assets/docs/modeles_documents.zip',
            'available' => true
        ],
        'calendrier_competitions' => [
            'title' => 'Calendrier des compétitions',
            'description' => 'Planning des compétitions UFSC',
            'icon' => 'calendar-alt',
            'url' => UFSC_PLUGIN_URL . 'assets/docs/calendrier_competitions.pdf',
            'available' => true
        ]
    ];

    foreach ($general_docs as $doc_key => $doc_info) {
        $output .= '<div class="ufsc-document-item">';
        $output .= '<div class="ufsc-document-icon">';
        $output .= '<i class="dashicons dashicons-' . $doc_info['icon'] . '"></i>';
        $output .= '</div>';
        $output .= '<div class="ufsc-document-info">';
        $output .= '<h4>' . esc_html($doc_info['title']) . '</h4>';
        $output .= '<p>' . esc_html($doc_info['description']) . '</p>';
        $output .= '</div>';
        $output .= '<div class="ufsc-document-actions">';

        if ($doc_info['available'] && $doc_info['url'] !== '#') {
            $output .= '<a href="' . esc_url($doc_info['url']) . '" target="_blank" class="ufsc-btn">';
            $output .= '<i class="dashicons dashicons-download"></i> Télécharger';
            $output .= '</a>';
        } else {
            $output .= '<span class="ufsc-btn ufsc-btn-disabled">Bientôt disponible</span>';
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
 * Get secure download URL for official documents (same as attestation downloads)
 *
 * @param string $action Document action type
 * @param int $club_id Club ID
 * @return string Secure download URL
 */
function ufsc_get_attestation_download_url($action, $club_id)
{
    return add_query_arg([
        'action' => 'ufsc_download_attestation',
        'attestation_type' => $action,
        'club_id' => $club_id,
        'nonce' => wp_create_nonce('ufsc_download_attestation_' . $action . '_' . $club_id)
    ], admin_url('admin-ajax.php'));
}

/**
 * Get secure document URL for club documents
 *
 * @param string $document_url Original document URL
 * @param int $club_id Club ID for security verification
 * @return string Secure document URL
 */
function ufsc_get_secure_document_url($document_url, $club_id)
{
    // If it's already a URL, add security parameters
    if (filter_var($document_url, FILTER_VALIDATE_URL)) {
        return add_query_arg([
            'club_verify' => wp_create_nonce('ufsc_document_view_' . $club_id)
        ], $document_url);
    }

    // If it's a file path, create a secure viewing URL
    return add_query_arg([
        'action' => 'ufsc_view_club_document',
        'document' => base64_encode($document_url),
        'club_id' => $club_id,
        'nonce' => wp_create_nonce('ufsc_view_document_' . $club_id)
    ], admin_url('admin-ajax.php'));
}

/**
 * Handle attestation download requests
 * This function should be called from an AJAX handler
 *
 * @return void
 */
function ufsc_handle_attestation_download()
{
    // Security checks
    if (!is_user_logged_in()) {
        wp_die('Accès non autorisé', 'Erreur', ['response' => 403]);
    }

    $attestation_type = sanitize_text_field( wp_unslash( $_GET['attestation_type'] ?? '' ) );
    $club_id = intval( wp_unslash( $_GET['club_id'] ?? 0 ) );
    $nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) );

    // Verify nonce
    if (!wp_verify_nonce($nonce, 'ufsc_download_attestation_' . $attestation_type . '_' . $club_id)) {
        wp_die('Erreur de sécurité', 'Erreur', ['response' => 403]);
    }

    // Verify club access
    if (!ufsc_verify_club_access($club_id)) {
        wp_die('Accès refusé à ce club', 'Erreur', ['response' => 403]);
    }

    // Get club data
    $club_manager = UFSC_Club_Manager::get_instance();
    $club = $club_manager->get_club($club_id);

    if (!$club) {
        wp_die('Club introuvable', 'Erreur', ['response' => 404]);
    }

    // Handle different attestation types
    switch ($attestation_type) {
        case 'attestation_affiliation':
            ufsc_download_attestation_affiliation($club);
            break;
        case 'attestation_assurance':
            ufsc_download_attestation_assurance($club);
            break;
        case 'carte_affiliation':
            ufsc_download_carte_affiliation($club);
            break;
        default:
            wp_die('Type d\'attestation non reconnu', 'Erreur', ['response' => 400]);
    }
}

/**
 * Handle document download requests
 * This function should be called from an AJAX handler
 *
 * @return void
 */
function ufsc_handle_document_download()
{
    // Security checks
    if (!is_user_logged_in()) {
        wp_die('Accès non autorisé', 'Erreur', ['response' => 403]);
    }

    $document_type = sanitize_text_field( wp_unslash( $_GET['document_type'] ?? '' ) );
    $club_id = intval( wp_unslash( $_GET['club_id'] ?? 0 ) );
    $nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ?? '' ) );

    // Verify nonce
    if (!wp_verify_nonce($nonce, 'ufsc_download_' . $document_type . '_' . $club_id)) {
        wp_die('Erreur de sécurité', 'Erreur', ['response' => 403]);
    }

    // Verify club access
    if (!ufsc_verify_club_access($club_id)) {
        wp_die('Accès refusé à ce club', 'Erreur', ['response' => 403]);
    }

    // Get club data
    $club_manager = UFSC_Club_Manager::get_instance();
    $club = $club_manager->get_club($club_id);

    if (!$club) {
        wp_die('Club introuvable', 'Erreur', ['response' => 404]);
    }

    // Handle different document types
    switch ($document_type) {
        case 'attestation_affiliation':
            ufsc_download_attestation_affiliation($club);
            break;
        case 'attestation_assurance':
            ufsc_download_attestation_assurance($club);
            break;
        case 'carte_affiliation':
            ufsc_download_carte_affiliation($club);
            break;
        default:
            wp_die('Type de document non reconnu', 'Erreur', ['response' => 400]);
    }
}

/**
 * Check if club has admin uploaded attestation
 *
 * @param int $club_id Club ID
 * @param string $type Type of attestation (affiliation or assurance)
 * @return bool True if attestation exists
 */
function ufsc_club_has_admin_attestation($club_id, $type)
{
    if (!in_array($type, ['affiliation', 'assurance'])) {
        return false;
    }
    
    $meta_key = '_ufsc_attestation_' . $type;
    $media_id = get_post_meta($club_id, $meta_key, true);
    
    return !empty($media_id) && get_post($media_id) && get_post_type($media_id) === 'attachment';
}

/**
 * Download affiliation attestation
 *
 * @param object $club Club object
 * @return void
 */
function ufsc_download_attestation_affiliation($club)
{
    // First check for admin-uploaded attestation
    $admin_media_id = get_post_meta($club->id, '_ufsc_attestation_affiliation', true);
    
    if ($admin_media_id && get_post($admin_media_id) && get_post_type($admin_media_id) === 'attachment') {
        // Serve the admin-uploaded file
        $file_path = get_attached_file($admin_media_id);
        $attachment = get_post($admin_media_id);
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            wp_die('Fichier introuvable ou inaccessible', 'Erreur', ['response' => 404]);
        }
        
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $filename = 'attestation_affiliation_' . sanitize_file_name($club->nom) . '_' . date('Y-m-d') . '.' . $file_extension;
        
        // Determine content type
        $content_type = 'application/octet-stream';
        if ($file_extension === 'pdf') {
            $content_type = 'application/pdf';
        } elseif (in_array($file_extension, ['jpg', 'jpeg'])) {
            $content_type = 'image/jpeg';
        } elseif ($file_extension === 'png') {
            $content_type = 'image/png';
        }
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private');
        header('Pragma: private');
        
        readfile($file_path);
        exit;
    }
    
    // Fallback to legacy doc_attestation_affiliation field
    if (!empty($club->doc_attestation_affiliation)) {
        $file_path = ufsc_get_file_path_from_url($club->doc_attestation_affiliation);
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            wp_die('Fichier introuvable ou inaccessible', 'Erreur', ['response' => 404]);
        }

        // Force download
        $filename = 'attestation_affiliation_' . sanitize_file_name($club->nom) . '_' . date('Y-m-d') . '.pdf';
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private');
        header('Pragma: private');
        
        readfile($file_path);
        exit;
    }
    
    // Check if attestation file exists in old field
    if (empty($club->doc_attestation_affiliation)) {
        wp_die('Attestation non disponible', 'Erreur', ['response' => 404]);
    }

    $file_path = ufsc_get_file_path_from_url($club->doc_attestation_affiliation);
    
    if (!file_exists($file_path) || !is_readable($file_path)) {
        wp_die('Fichier introuvable ou inaccessible', 'Erreur', ['response' => 404]);
    }

    // Force download
    $filename = 'attestation_affiliation_' . sanitize_file_name($club->nom) . '_' . date('Y-m-d') . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: private');
    header('Pragma: private');
    
    readfile($file_path);
    exit;
}

/**
 * Download insurance attestation
 *
 * @param object $club Club object
 * @return void
 */
function ufsc_download_attestation_assurance($club)
{
    // First check for admin-uploaded attestation
    $admin_media_id = get_post_meta($club->id, '_ufsc_attestation_assurance', true);
    
    if ($admin_media_id && get_post($admin_media_id) && get_post_type($admin_media_id) === 'attachment') {
        // Serve the admin-uploaded file
        $file_path = get_attached_file($admin_media_id);
        $attachment = get_post($admin_media_id);
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            wp_die('Fichier introuvable ou inaccessible', 'Erreur', ['response' => 404]);
        }
        
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $filename = 'attestation_assurance_' . sanitize_file_name($club->nom) . '_' . date('Y-m-d') . '.' . $file_extension;
        
        // Determine content type
        $content_type = 'application/octet-stream';
        if ($file_extension === 'pdf') {
            $content_type = 'application/pdf';
        } elseif (in_array($file_extension, ['jpg', 'jpeg'])) {
            $content_type = 'image/jpeg';
        } elseif ($file_extension === 'png') {
            $content_type = 'image/png';
        }
        
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: private');
        header('Pragma: private');
        
        readfile($file_path);
        exit;
    }
    
    // Fallback to generated text file (legacy behavior)
    $filename = 'attestation_assurance_' . sanitize_file_name($club->nom) . '_' . date('Y-m-d') . '.txt';
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "Attestation d'assurance UFSC\n\n";
    echo "Club: " . $club->nom . "\n";
    echo "Numéro d'affiliation: " . ($club->num_affiliation ?? 'En cours') . "\n";
    echo "Date: " . date('d/m/Y') . "\n\n";
    echo "Cette attestation certifie que le club est couvert par l'assurance fédérale UFSC.\n";
    
    exit;
}

/**
 * Download affiliation card
 *
 * @param object $club Club object
 * @return void
 */
function ufsc_download_carte_affiliation($club)
{
    // Similar implementation as insurance attestation
    // In real implementation, this would generate or serve an actual affiliation card
    
    $filename = 'carte_affiliation_' . sanitize_file_name($club->nom) . '_' . date('Y-m-d') . '.txt';
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "Carte d'affiliation UFSC\n\n";
    echo "Club: " . $club->nom . "\n";
    echo "Ville: " . $club->ville . "\n";
    echo "Numéro d'affiliation: " . ($club->num_affiliation ?? 'En cours') . "\n";
    echo "Statut: " . $club->statut . "\n";
    echo "Date d'affiliation: " . ($club->date_affiliation ? date('d/m/Y', strtotime($club->date_affiliation)) : 'En cours') . "\n";
    
    exit;
}

/**
 * Convert URL to file path
 *
 * @param string $url File URL
 * @return string File path
 */
function ufsc_get_file_path_from_url($url)
{
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $upload_dir = wp_upload_dir();
        return str_replace($upload_dir['baseurl'], $upload_dir['basedir'], (string) $url);
    }

    return (string) $url; // Already a file path
}

/**
 * Process document update form submission
 *
 * @return void
 */
function ufsc_process_document_update()
{
    // Check if this is a document update request
    if (!isset($_POST['action']) || $_POST['action'] !== 'ufsc_update_club_documents') {
        return;
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['ufsc_update_documents_nonce'] ?? '', 'ufsc_update_club_documents')) {
        wp_die('Erreur de sécurité', 'Erreur', ['response' => 403]);
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die('Vous devez être connecté', 'Erreur', ['response' => 401]);
    }

    // Get user's club
    $access_check = ufsc_check_frontend_access('documents');
    if (!$access_check['allowed']) {
        wp_die('Accès refusé', 'Erreur', ['response' => 403]);
    }

    $club = $access_check['club'];
    $club_manager = UFSC_Club_Manager::get_instance();

    // Document fields mapping
    $doc_fields = [
        'statuts' => 'statuts',
        'recepisse' => 'recepisse', 
        'jo' => 'jo',
        'pv_ag' => 'pv_ag',
        'cer' => 'cer',
        'attestation_cer' => 'attestation_cer'
    ];

    $uploaded_files = [];
    $errors = [];

    // Process each uploaded file
    foreach ($doc_fields as $field_key => $db_field) {
        if (!empty($_FILES[$field_key]['name'])) {
            $file = $_FILES[$field_key];
            
            // Validate file
            $validation = ufsc_validate_document_upload($file);
            if (is_wp_error($validation)) {
                $errors[] = "Erreur pour {$field_key}: " . $validation->get_error_message();
                continue;
            }

            // Handle upload
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            $upload_overrides = ['test_form' => false];
            $movefile = wp_handle_upload($file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                // Update club document field
                $club_manager->update_club_field($club->id, $db_field, $movefile['url']);
                $uploaded_files[] = $field_key;
            } else {
                $errors[] = "Erreur d'upload pour {$field_key}: " . ($movefile['error'] ?? 'Erreur inconnue');
            }
        }
    }

    // Prepare response message
    $message = '';
    if (!empty($uploaded_files)) {
        $message .= 'Documents mis à jour avec succès: ' . implode(', ', $uploaded_files);
    }
    if (!empty($errors)) {
        $message .= !empty($uploaded_files) ? ' Erreurs: ' : '';
        $message .= implode('; ', $errors);
    }

    // Redirect back with message
    $redirect_url = add_query_arg([
        'view' => 'documents',
        'doc_update' => !empty($uploaded_files) ? 'success' : 'error',
        'message' => urlencode($message)
    ], get_permalink());

    wp_redirect($redirect_url);
    exit;
}

/**
 * Validate document upload
 *
 * @param array $file File array from $_FILES
 * @return bool|WP_Error True if valid, WP_Error if invalid
 */
function ufsc_validate_document_upload($file)
{
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return new WP_Error('file_too_large', 'Le fichier est trop volumineux (5MB maximum)');
    }

    // Check file type
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    $file_type = wp_check_filetype($file['name']);
    
    if (!in_array($file['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
        return new WP_Error('invalid_file_type', 'Type de fichier non autorisé. Utilisez PDF, JPG ou PNG.');
    }

    return true;
}

// Register AJAX handlers for document downloads using attestation system
add_action('wp_ajax_ufsc_download_attestation', 'ufsc_handle_attestation_download');
add_action('wp_ajax_nopriv_ufsc_download_attestation', function() {
    wp_die('Vous devez être connecté', 'Erreur', ['response' => 401]);
});

// Register document update handler
add_action('init', 'ufsc_process_document_update');