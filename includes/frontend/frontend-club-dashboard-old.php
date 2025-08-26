<?php

/**
 * Dashboard Club Frontend - Interface professionnelle avec menu de navigation
 */

/**
 * Load MVP dashboard implementation
 */
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/dashboard-mvp.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/helpers/dashboard-data.php';

/**
 * Shortcode pour afficher le tableau de bord du club (MVP Implementation)
 */
function ufsc_club_dashboard_shortcode($atts)
{
    // Use the new MVP dashboard implementation
    return ufsc_club_dashboard_mvp_shortcode($atts);
}

    // Débuter le HTML pour le tableau de bord
    $output = '<div class="ufsc-container">';

    // Display logo upload result message if present
    if (isset($_GET['logo_update']) && isset($_GET['message'])) {
        $update_status = sanitize_text_field($_GET['logo_update']);
        $message = sanitize_text_field(urldecode($_GET['message']));
        
        if ($update_status === 'success') {
            $output .= '<div class="ufsc-alert ufsc-alert-success">';
            $output .= '<h4>Logo mis à jour</h4>';
            $output .= '<p>' . esc_html($message) . '</p>';
            $output .= '</div>';
        } else {
            $output .= '<div class="ufsc-alert ufsc-alert-error">';
            $output .= '<h4>Erreur de mise à jour</h4>';
            $output .= '<p>' . esc_html($message) . '</p>';
            $output .= '</div>';
        }
    }

    // CORRECTION: Use standardized status alert rendering
    $status_alert = ufsc_render_club_status_alert($club, 'dashboard');
    if (!empty($status_alert)) {
        $output .= $status_alert;
    }

    // Générer des badges de statut
    $status_badge = '';
    switch ($club->statut) {
        case 'Actif':
            $status_badge = '<span class="ufsc-badge ufsc-badge-active">Validé</span>';
            break;
        case 'En attente de validation':
        case 'En cours de validation':
        case 'En cours de création':
            $status_badge = '<span class="ufsc-badge ufsc-badge-pending">En attente</span>';
            break;
        case 'Refusé':
            $status_badge = '<span class="ufsc-badge ufsc-badge-error">Refusé</span>';
            break;
        default:
            $status_badge = '<span class="ufsc-badge ufsc-badge-inactive">Inactif</span>';
    }

    // En-tête avec nom du club et statut
    $output .= '<div class="ufsc-dashboard-header">
                <div class="ufsc-dashboard-title">
                    <h1>' . esc_html($club->nom) . ' ' . $status_badge . '</h1>';

    if (!empty($club->num_affiliation)) {
        $output .= '<h2>N° Affiliation: ' . esc_html($club->num_affiliation) . '</h2>';
    }

    $output .= '</div>';

    // Boutons d'action rapide dans l'en-tête
    if ($club->statut === 'Actif') {
        $output .= '<div class="ufsc-dashboard-actions">
                    <a href="' . esc_url(add_query_arg(['view' => 'licence_form'], get_permalink())) . '" class="ufsc-btn ufsc-btn-red">
                        <i class="dashicons dashicons-plus-alt2"></i> Ajouter un licencié
                    </a>
                    </div>';
    }
    $output .= '</div>';

    // Menu de navigation
    $output .= '<div class="ufsc-dashboard-nav">
                <ul>';

    $menu_items = [
        'home' => [
            'label' => '<i class="dashicons dashicons-dashboard"></i> Accueil',
            'url' => add_query_arg(['view' => 'home'], get_permalink()),
            'active' => $current_page === 'home'
        ],
        'licences' => [
            'label' => '<i class="dashicons dashicons-id"></i> Licences',
            'url' => add_query_arg(['view' => 'licences'], get_permalink()),
            'active' => $current_page === 'licences'
        ],
        'documents' => [
            'label' => '<i class="dashicons dashicons-media-document"></i> Documents',
            'url' => add_query_arg(['view' => 'documents'], get_permalink()),
            'active' => $current_page === 'documents'
        ],
        'attestations' => [
            'label' => '<i class="dashicons dashicons-awards"></i> Attestations',
            'url' => add_query_arg(['view' => 'attestations'], get_permalink()),
            'active' => $current_page === 'attestations'
        ],
        'paiements' => [
            'label' => '<i class="dashicons dashicons-money-alt"></i> Paiements',
            'url' => add_query_arg(['view' => 'paiements'], get_permalink()),
            'active' => $current_page === 'paiements'
        ],
        'profile' => [
            'label' => '<i class="dashicons dashicons-businessperson"></i> Profil du club',
            'url' => add_query_arg(['view' => 'profile'], get_permalink()),
            'active' => $current_page === 'profile'
        ]
    ];

    foreach ($menu_items as $key => $item) {
        $active_class = $item['active'] ? ' class="active"' : '';
        $output .= '<li' . $active_class . '><a href="' . esc_url($item['url']) . '">' . $item['label'] . '</a></li>';
    }

    $output .= '</ul>
                </div>';

    // Contenu principal basé sur la page active
    $output .= '<div class="ufsc-dashboard-content">';

    switch ($current_page) {
        case 'licences':
            $output .= ufsc_dashboard_licences_content($club);
            break;

        case 'documents':
            $output .= ufsc_dashboard_documents_content($club);
            break;

        case 'attestations':
            // Include attestations file and render content
            if (file_exists(UFSC_PLUGIN_PATH . 'includes/frontend/club/attestations.php')) {
                include_once UFSC_PLUGIN_PATH . 'includes/frontend/club/attestations.php';
                $output .= ufsc_club_render_attestations($club);
            } else {
                $output .= '<div class="ufsc-alert ufsc-alert-error">Section attestations non disponible.</div>';
            }
            break;

        case 'paiements':
            // Include payments file and render content
            if (file_exists(UFSC_PLUGIN_PATH . 'includes/frontend/club/paiements.php')) {
                include_once UFSC_PLUGIN_PATH . 'includes/frontend/club/paiements.php';
                $output .= ufsc_club_render_paiements($club);
            } else {
                $output .= '<div class="ufsc-alert ufsc-alert-error">Section paiements non disponible.</div>';
            }
            break;

        case 'profile':
            $output .= ufsc_dashboard_profile_content($club);
            break;

        case 'licence_form':
            $output .= ufsc_dashboard_licence_form($club);
            break;

        default:
            $output .= ufsc_dashboard_home_content($club);
    }

    $output .= '</div>'; // Fin du contenu principal

    // Ajouter le CSS spécifique au dashboard dans le footer
    $output .= '
    <style>
    /* Enhanced professional styling for UFSC dashboard */
    .ufsc-dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .ufsc-dashboard-title h1 {
        font-size: 28px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .ufsc-dashboard-title h2 {
        font-size: 16px;
        margin: 5px 0 0;
        color: #666;
        font-weight: normal;
    }
    
    /* Welcome block with logo */
    .ufsc-welcome-block {
        background: linear-gradient(135deg, var(--ufsc-navy) 0%, #3d3b73 100%);
        color: white;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .ufsc-welcome-content {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 40px;
        align-items: start;
    }
    
    .ufsc-club-logo-section {
        background: rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }
    
    .ufsc-club-logo-section h3 {
        margin: 0 0 15px;
        font-size: 18px;
        color: white;
    }
    
    .ufsc-current-logo {
        margin-bottom: 15px;
    }
    
    .ufsc-logo-display {
        max-width: 150px;
        max-height: 150px;
        width: auto;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        margin-bottom: 10px;
        display: block;
        margin-left: auto;
        margin-right: auto;
    }
    
    .ufsc-no-logo .ufsc-placeholder-logo {
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.1);
        border: 2px dashed rgba(255,255,255,0.3);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        color: rgba(255,255,255,0.7);
    }
    
    .ufsc-placeholder-logo .dashicons {
        font-size: 40px;
        margin-bottom: 5px;
    }
    
    .ufsc-logo-actions {
        margin-top: 10px;
    }
    
    .ufsc-logo-form {
        margin-top: 15px;
    }
    
    .ufsc-logo-form .ufsc-form-group {
        margin-bottom: 15px;
    }
    
    .ufsc-logo-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: white;
    }
    
    .ufsc-file-input {
        width: 100%;
        padding: 8px;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 4px;
        background: rgba(255,255,255,0.1);
        color: white;
        font-size: 14px;
    }
    
    .ufsc-file-input::file-selector-button {
        background: var(--ufsc-red);
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        margin-right: 10px;
        cursor: pointer;
    }
    
    .ufsc-welcome-text h2 {
        font-size: 32px;
        margin: 0 0 15px;
        font-weight: 600;
    }
    
    .ufsc-welcome-text p {
        font-size: 18px;
        margin: 0;
        opacity: 0.9;
        line-height: 1.6;
    }
    
    /* Enhanced navigation */
    .ufsc-dashboard-nav {
        background: var(--ufsc-navy);
        border-radius: 8px;
        margin-bottom: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .ufsc-dashboard-nav ul {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        flex-wrap: wrap;
    }
    
    .ufsc-dashboard-nav li {
        margin: 0;
    }
    
    .ufsc-dashboard-nav li a {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        color: white;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    
    .ufsc-dashboard-nav li a .dashicons {
        margin-right: 8px;
        font-size: 18px;
    }
    
    .ufsc-dashboard-nav li.active {
        background: var(--ufsc-red);
    }
    
    .ufsc-dashboard-nav li:first-child {
        border-top-left-radius: 8px;
        border-bottom-left-radius: 8px;
    }
    
    .ufsc-dashboard-nav li:last-child {
        border-top-right-radius: 8px;
        border-bottom-right-radius: 8px;
    }
    
    .ufsc-dashboard-nav li:hover:not(.active) {
        background: rgba(255, 255, 255, 0.1);
    }
    
    /* Enhanced alerts */
    .ufsc-alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .ufsc-alert-success {
        border-left-color: var(--ufsc-success);
        background-color: #f7fcf7;
    }
    
    .ufsc-alert-error {
        border-left-color: var(--ufsc-red);
        background-color: #fef7f7;
    }
    
    .ufsc-alert h4 {
        margin: 0 0 8px;
        font-size: 16px;
        font-weight: 600;
    }
    
    .ufsc-alert p {
        margin: 0;
        line-height: 1.5;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .ufsc-dashboard-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .ufsc-welcome-content {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .ufsc-dashboard-nav ul {
            flex-direction: column;
        }
        
        .ufsc-dashboard-nav li:first-child {
            border-radius: 8px 8px 0 0;
        }
        
        .ufsc-dashboard-nav li:last-child {
            border-radius: 0 0 8px 8px;
        }
        
        .ufsc-welcome-text h2 {
            font-size: 24px;
        }
    }
    </style>';

    // Ajouter le JS pour les interactions
    $output .= '
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Gestion du formulaire d\'édition des coordonnées
        $(".ufsc-edit-contact-link").on("click", function(e) {
            e.preventDefault();
            var field = $(this).data("field");
            $("#ufsc-edit-field-name").val(field);
            
            // Cacher tous les champs
            $("#ufsc-edit-email-field, #ufsc-edit-telephone-field").hide();
            
            // Afficher le champ concerné
            $("#ufsc-edit-" + field + "-field").show();
            
            // Afficher le formulaire
            $("#ufsc-edit-contact-form").slideDown();
        });
        
        // Annuler l\'édition
        $("#ufsc-cancel-edit").on("click", function() {
            $("#ufsc-edit-contact-form").slideUp();
        });
    });
    </script>
    ';

    $output .= '</div>'; // Fin du container

    return $output;
}

/**
 * Contenu de la page d'accueil du dashboard
 */
function ufsc_dashboard_home_content($club)
{
    // Récupérer les licences du club
    $club_manager = UFSC_Club_Manager::get_instance();
    $licences = $club_manager->get_licences_by_club($club->id);
    $licences_count = count($licences);
    $licence_active_count = 0;
    $licence_expiring_count = 0;

    // Calculer les statistiques de licences
    foreach ($licences as $licence) {
        if ($licence->statut === 'active') {
            $licence_active_count++;

            // Vérifier si la licence expire dans les 30 jours
            $expiry_date = strtotime($licence->date_expiration);
            $thirty_days = strtotime('+30 days');
            if ($expiry_date < $thirty_days) {
                $licence_expiring_count++;
            }
        }
    }

    // Calculer le quota disponible
    $quota_total = intval($club->quota_licences) > 0 ? intval($club->quota_licences) : 0;
    $quota_used = $licences_count;
    $quota_remaining = max(0, $quota_total - $quota_used);
    $quota_percentage = $quota_total > 0 ? min(100, ($quota_used / $quota_total) * 100) : 0;

    // Générer le contenu
    $output = '';

    // Bloc de bienvenue avec logo du club
    $output .= '<div class="ufsc-welcome-block">
                <div class="ufsc-welcome-content">
                    <div class="ufsc-club-logo-section">
                        <h3>Logo du club</h3>';
    
    if (!empty($club->logo_url)) {
        $output .= '<div class="ufsc-current-logo">
                        <img src="' . esc_url($club->logo_url) . '" alt="Logo de ' . esc_attr($club->nom) . '" class="ufsc-logo-display">
                        <div class="ufsc-logo-actions">
                            <a href="' . esc_url($club->logo_url) . '" download="logo_' . sanitize_file_name($club->nom) . '" class="ufsc-btn ufsc-btn-small">
                                <i class="dashicons dashicons-download"></i> Télécharger
                            </a>
                        </div>
                    </div>';
    } else {
        $output .= '<div class="ufsc-no-logo">
                        <div class="ufsc-placeholder-logo">
                            <i class="dashicons dashicons-format-image"></i>
                            <span>Aucun logo</span>
                        </div>
                    </div>';
    }
    
    $output .= '<div class="ufsc-logo-upload">
                    <form method="post" enctype="multipart/form-data" class="ufsc-logo-form">
                        ' . wp_nonce_field('ufsc_update_club_logo', 'ufsc_logo_nonce', true, false) . '
                        <input type="hidden" name="action" value="ufsc_update_club_logo">
                        <div class="ufsc-form-group">
                            <label for="club_logo">Modifier le logo :</label>
                            <input type="file" name="club_logo" id="club_logo" accept="image/*" class="ufsc-file-input">
                            <small>Formats acceptés: JPG, PNG, GIF. Taille max: 2 MB</small>
                        </div>
                        <button type="submit" class="ufsc-btn ufsc-btn-primary ufsc-btn-small">
                            <i class="dashicons dashicons-upload"></i> ' . (!empty($club->logo_url) ? 'Remplacer' : 'Ajouter') . ' le logo
                        </button>
                    </form>
                </div>
                </div>
                
                <div class="ufsc-welcome-text">
                    <h2>Bienvenue dans votre espace club</h2>
                    <p>Gérez vos licences, téléchargez vos documents officiels et suivez votre affiliation.</p>
                </div>
                </div>
                </div>';

    // Statistiques
    $output .= '<div class="ufsc-dashboard-stats">
                <div class="ufsc-stat-card">
                    <div class="ufsc-stat-icon"><i class="dashicons dashicons-id"></i></div>
                    <div class="ufsc-stat-value">' . $licences_count . '</div>
                    <div class="ufsc-stat-label">Licences totales</div>
                </div>
                
                <div class="ufsc-stat-card">
                    <div class="ufsc-stat-icon"><i class="dashicons dashicons-yes-alt"></i></div>
                    <div class="ufsc-stat-value">' . $licence_active_count . '</div>
                    <div class="ufsc-stat-label">Licences actives</div>
                </div>
                
                <div class="ufsc-stat-card">
                    <div class="ufsc-stat-icon"><i class="dashicons dashicons-chart-bar"></i></div>
                    <div class="ufsc-stat-value' . ($quota_remaining > 0 ? '' : ' ufsc-text-danger') . '">' . $quota_remaining . '</div>
                    <div class="ufsc-stat-label">Licences disponibles</div>
                </div>
                
                <div class="ufsc-stat-card">
                    <div class="ufsc-stat-icon"><i class="dashicons dashicons-calendar-alt"></i></div>
                    <div class="ufsc-stat-value' . ($licence_expiring_count > 0 ? ' ufsc-text-warning' : '') . '">' . $licence_expiring_count . '</div>
                    <div class="ufsc-stat-label">Licences expirant bientôt</div>
                </div>
                </div>';

    // Quota de licences
    $output .= '<div class="ufsc-card">
                <div class="ufsc-card-header">Quota de licences</div>
                <div class="ufsc-card-body">';

    if ($quota_total > 0) {
        $output .= '<div class="ufsc-quota-box">
                    <div class="ufsc-quota-info">
                        <span>Utilisation: ' . $quota_used . ' / ' . $quota_total . '</span>
                        <span>' . round($quota_percentage) . '%</span>
                    </div>
                    <div class="ufsc-quota-progress">
                        <div class="ufsc-quota-bar" style="width: ' . $quota_percentage . '%;"></div>
                    </div>';

        if ($quota_remaining <= 0) {
            $output .= '<div class="ufsc-form-hint" style="color:var(--ufsc-red); margin-top:8px;">
                        Votre quota est épuisé. <a href="mailto:contact@ufsc-france.org?subject=Demande%20d%27augmentation%20de%20quota">Contactez l\'administration</a> pour l\'augmenter.
                        </div>';
        } else {
            $output .= '<div class="ufsc-form-hint" style="margin-top:8px;">
                        Licences incluses dans votre pack d\'affiliation. 
                        <a href="' . esc_url(add_query_arg(['view' => 'licence_form'], get_permalink())) . '">Ajouter une licence</a>
                        </div>';
        }

        $output .= '</div>';
    } else {
        $output .= '<p>Aucun quota de licences défini pour votre club.</p>';
    }

    $output .= '</div>
                </div>';

    // Téléchargements rapides
    if ($club->statut === 'Actif') {
        $output .= '<div class="ufsc-card">
                    <div class="ufsc-card-header">Téléchargements</div>
                    <div class="ufsc-card-body">
                        <div class="ufsc-download-buttons">
                            <a href="' . esc_url(add_query_arg(['action' => 'attestation_affiliation', 'club_id' => $club->id, 'nonce' => wp_create_nonce('ufsc_attestation_' . $club->id)])) . '" class="ufsc-download-btn">
                                <i class="dashicons dashicons-download"></i>
                                <span>Attestation d\'affiliation</span>
                            </a>
                            <a href="' . esc_url(add_query_arg(['action' => 'attestation_assurance', 'club_id' => $club->id, 'nonce' => wp_create_nonce('ufsc_attestation_' . $club->id)])) . '" class="ufsc-download-btn">
                                <i class="dashicons dashicons-download"></i>
                                <span>Attestation d\'assurance</span>
                            </a>
                        </div>
                    </div>
                    </div>';
    }

    // Dernières licences
    if (!empty($licences)) {
        $output .= '<div class="ufsc-card">
                    <div class="ufsc-card-header">
                        <span>Dernières licences</span>
                        <a href="' . esc_url(add_query_arg(['view' => 'licences'], get_permalink())) . '" class="ufsc-btn-text">Voir toutes</a>
                    </div>
                    <div class="ufsc-card-body">
                        <div class="ufsc-table-responsive">
                            <table class="ufsc-table">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Fonction</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>';

        // Afficher les 5 dernières licences
        $recent_licences = array_slice($licences, 0, 5);

        foreach ($recent_licences as $licence) {
            $status_class = 'ufsc-badge-inactive';
            $status_text = 'Inactive';

            if ($licence->statut === 'active') {
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
            } elseif ($licence->statut === 'pending') {
                $status_class = 'ufsc-badge-pending';
                $status_text = 'En attente';
            }

            $output .= '<tr>
                        <td>' . esc_html($licence->nom) . '</td>
                        <td>' . esc_html($licence->prenom) . '</td>
                        <td>' . esc_html($licence->fonction ?? '-') . '</td>
                        <td><span class="ufsc-badge ' . $status_class . '">' . $status_text . '</span></td>
                        </tr>';
        }

        $output .= '</tbody>
                    </table>
                    </div>
                    </div>
                    </div>';
    }

    return $output;
}

/**
 * Contenu de la page des licences
 */
function ufsc_dashboard_licences_content($club)
{
    $club_manager = UFSC_Club_Manager::get_instance();
    $licences = $club_manager->get_licences_by_club($club->id);

    $output = '<h2 class="ufsc-section-title">Gestion des licences</h2>';

    // Bouton pour ajouter une licence
    $output .= '<div class="ufsc-action-bar">
                <a href="' . esc_url(add_query_arg(['view' => 'licence_form'], get_permalink())) . '" class="ufsc-btn ufsc-btn-red">
                    <i class="dashicons dashicons-plus-alt2"></i> Ajouter un licencié
                </a>
                </div>';

    if (empty($licences)) {
        $output .= '<div class="ufsc-empty-state">
                    <div class="ufsc-empty-icon"><i class="dashicons dashicons-id"></i></div>
                    <h3>Aucune licence</h3>
                    <p>Vous n\'avez pas encore de licences enregistrées.</p>
                    <a href="' . esc_url(add_query_arg(['view' => 'licence_form'], get_permalink())) . '" class="ufsc-btn">
                        Ajouter votre premier licencié
                    </a>
                    </div>';
    } else {
        $output .= '<div class="ufsc-card">
                    <div class="ufsc-card-body">
                        <div class="ufsc-table-responsive">
                            <table class="ufsc-table ufsc-licences-table" data-tooltip="Tableau des licences avec fonctions de recherche et tri">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Fonction</th>
                                        <th>Email</th>
                                        <th>Date d\'expiration</th>
                                        <th>Statut</th>
                                        <th class="no-sort">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>';

        foreach ($licences as $licence) {
            $expiry_date = new DateTime($licence->date_expiration);
            $today = new DateTime();
            $days_remaining = $today->diff($expiry_date)->days;
            $is_expired = $expiry_date < $today;

            $status_class = 'ufsc-badge-inactive';
            $status_text = 'Inactive';

            if ($licence->statut === 'active') {
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
            } elseif ($licence->statut === 'pending') {
                $status_class = 'ufsc-badge-pending';
                $status_text = 'En attente';
            }

            $output .= '<tr>
                        <td>' . esc_html($licence->nom) . '</td>
                        <td>' . esc_html($licence->prenom) . '</td>
                        <td>' . esc_html($licence->fonction ?? '-') . '</td>
                        <td>' . esc_html($licence->email ?? '-') . '</td>
                        <td data-sort="' . $expiry_date->getTimestamp() . '">' . esc_html($expiry_date->format('d/m/Y')) . '</td>
                        <td><span class="ufsc-badge ' . $status_class . '">' . $status_text . '</span></td>
                        <td>
                            <div class="ufsc-action-buttons">
                                <button type="button" class="ufsc-btn-sm ufsc-btn-pro" 
                                        data-tooltip="Voir le détail de la licence" 
                                        data-licence-id="' . $licence->id . '"
                                        aria-label="Voir le détail de la licence de ' . esc_attr($licence->prenom . ' ' . $licence->nom) . '">
                                    <i class="dashicons dashicons-visibility" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="ufsc-btn-sm ufsc-btn-pro" 
                                        data-tooltip="Télécharger la carte licence"
                                        data-download-licence="' . $licence->id . '"
                                        aria-label="Télécharger la carte licence de ' . esc_attr($licence->prenom . ' ' . $licence->nom) . '">
                                    <i class="dashicons dashicons-download" aria-hidden="true"></i>
                                </button>
                            </div>
                        </td>
                        </tr>';
        }

        $output .= '</tbody>
                    </table>
                    </div>
                    </div>
                    </div>';
    }

    return $output;
}

/**
 * Contenu de la page des documents
 */
function ufsc_dashboard_documents_content($club)
{
    $output = '<h2 class="ufsc-section-title">Documents du club</h2>';

    // Get document manager instance
    $doc_manager = UFSC_Document_Manager::get_instance();

    // Documents fournis par le club (seuls documents à afficher côté club)
    $docs = [
        'statuts' => "Statuts du club",
        'recepisse' => "Récépissé de déclaration en préfecture",
        'jo' => "Parution au journal officiel",
        'pv_ag' => "Dernier PV d'Assemblée Générale",
        'cer' => "Contrat d'Engagement Républicain",
        'attestation_cer' => "Attestation liée au CER"
    ];

    // Check document status
    $missing_docs = $doc_manager->get_missing_documents($club->id);
    $total_docs = count($docs);
    $provided_docs = $total_docs - count($missing_docs);

    // Confidentiality note
    $output .= '<div class="ufsc-info-box" style="margin-bottom: 20px;">
                <h4><i class="dashicons dashicons-lock"></i> Confidentialité</h4>
                <p>Ces documents sont propres à votre club et ne sont accessibles qu\'à votre organisation. Respect total de la confidentialité inter-clubs.</p>
                </div>';

    $output .= '<div class="ufsc-card">
                <div class="ufsc-card-header">
                    Documents transmis par votre club
                    <span class="ufsc-document-counter">(' . $provided_docs . '/' . $total_docs . ')</span>';
    
    if (empty($missing_docs)) {
        $output .= '<span class="ufsc-badge ufsc-badge-success" style="margin-left: 10px;">Complet</span>';
    } else {
        $output .= '<span class="ufsc-badge ufsc-badge-warning" style="margin-left: 10px;">' . count($missing_docs) . ' manquant(s)</span>';
    }
    
    $output .= '</div>
                <div class="ufsc-card-body">';

    if (!empty($missing_docs)) {
        $output .= '<div class="ufsc-alert ufsc-alert-warning" style="margin-bottom: 20px;">
                    <p><strong>Documents manquants :</strong></p>
                    <ul>';
        foreach ($missing_docs as $missing_doc) {
            $output .= '<li>' . esc_html($missing_doc) . '</li>';
        }
        $output .= '</ul>
                    <p>Votre club ne peut pas être validé tant que tous les documents obligatoires ne sont pas fournis.</p>
                    </div>';
    }

    $output .= '<div class="ufsc-documents-grid">';

    foreach ($docs as $key => $label) {
        $doc_column = 'doc_' . $key;
        $has_document = !empty($club->{$doc_column});
        
        if ($has_document) {
            $secure_link = $doc_manager->get_secure_download_link($club->id, $key);
            $output .= '<div class="ufsc-document-item ufsc-document-available">
                        <div class="ufsc-document-icon"><i class="dashicons dashicons-media-document"></i></div>
                        <div class="ufsc-document-info">
                            <h4>' . esc_html($label) . '</h4>
                            <span class="ufsc-document-status">✓ Fourni</span>
                        </div>
                        <a href="' . esc_url($secure_link) . '" target="_blank" class="ufsc-btn ufsc-btn-primary">Voir</a>
                        </div>';
        } else {
            $output .= '<div class="ufsc-document-item ufsc-document-missing">
                        <div class="ufsc-document-icon"><i class="dashicons dashicons-warning"></i></div>
                        <div class="ufsc-document-info">
                            <h4>' . esc_html($label) . '</h4>
                            <span class="ufsc-document-status">❌ Manquant</span>
                        </div>
                        <span class="ufsc-btn ufsc-btn-disabled">Non fourni</span>
                        </div>';
        }
    }

    $output .= '</div>';

    // Information message
    if (!empty($missing_docs)) {
        $output .= '<div class="ufsc-info-box" style="margin-top: 20px;">
                    <h4>Comment fournir les documents manquants ?</h4>
                    <p>Pour transmettre les documents manquants, veuillez contacter l\'administration UFSC ou votre référent régional.</p>
                    <p><strong>Email :</strong> <a href="mailto:admin@ufsc.fr">admin@ufsc.fr</a></p>
                    </div>';
    } else {
        $output .= '<div class="ufsc-success-box" style="margin-top: 20px;">
                    <h4>✅ Dossier complet</h4>
                    <p>Tous les documents obligatoires ont été fournis. Votre club peut être validé par l\'administration.</p>
                    </div>';
    }

    $output .= '</div>
                </div>';

    return $output;
}

/**
 * Contenu de la page profil
 */
function ufsc_dashboard_profile_content($club)
{
    $output = '<h2 class="ufsc-section-title">Profil du club</h2>';

    // Informations principales - Complete profile as per requirements
    $output .= '<div class="ufsc-card">
                <div class="ufsc-card-header">Informations générales</div>
                <div class="ufsc-card-body">
                    <div class="ufsc-profile-grid">
                        <div class="ufsc-profile-item">
                            <div class="ufsc-profile-label">Nom du club</div>
                            <div class="ufsc-profile-value">' . esc_html($club->nom) . '</div>
                        </div>
                        
                        <div class="ufsc-profile-item">
                            <div class="ufsc-profile-label">Adresse postale</div>
                            <div class="ufsc-profile-value">' . esc_html($club->adresse ?? 'Non renseignée') . '</div>
                        </div>
                        
                        <div class="ufsc-profile-item">
                            <div class="ufsc-profile-label">Code postal</div>
                            <div class="ufsc-profile-value">' . esc_html($club->code_postal ?? 'Non renseigné') . '</div>
                        </div>
                        
                        <div class="ufsc-profile-item">
                            <div class="ufsc-profile-label">Ville</div>
                            <div class="ufsc-profile-value">' . esc_html($club->ville) . '</div>
                        </div>
                        
                        <div class="ufsc-profile-item">
                            <div class="ufsc-profile-label">Région</div>
                            <div class="ufsc-profile-value">' . esc_html($club->region ?? 'Non définie') . '</div>
                        </div>
                        
                        <div class="ufsc-profile-item">
                            <div class="ufsc-profile-label">Email</div>
                            <div class="ufsc-profile-value">' . esc_html($club->email) . '</div>
                        </div>
                        
                        <div class="ufsc-profile-item">
                            <div class="ufsc-profile-label">Téléphone</div>
                            <div class="ufsc-profile-value">' . esc_html($club->telephone) . '</div>
                        </div>
                        
                        <div class="ufsc-profile-item">
                            <div class="ufsc-profile-label">Type de structure</div>
                            <div class="ufsc-profile-value">' . esc_html($club->type ?? 'Non renseigné') . '</div>
                        </div>';

    if (!empty($club->num_affiliation)) {
        $output .= '<div class="ufsc-profile-item">
                    <div class="ufsc-profile-label">Numéro d\'affiliation</div>
                    <div class="ufsc-profile-value">' . esc_html($club->num_affiliation) . '</div>
                    </div>';
    }

    if (!empty($club->date_affiliation)) {
        $date_affiliation = new DateTime($club->date_affiliation);
        $output .= '<div class="ufsc-profile-item">
                    <div class="ufsc-profile-label">Date d\'affiliation</div>
                    <div class="ufsc-profile-value">' . esc_html($date_affiliation->format('d/m/Y')) . '</div>
                    </div>';
    }

    $output .= '</div>'; // Fin profile-grid

    // Updated modification request section with correct email
    $output .= '<div class="ufsc-action-note">
                <p>Pour demander une modification de vos informations, cliquez sur le bouton ci-dessous :</p>
                <p><a href="mailto:service.informatique@ufsc-france.org?subject=Demande%20de%20modification%20-%20Club%20' . urlencode($club->nom) . '&body=Bonjour,%0D%0A%0D%0AJe%20souhaite%20demander%20une%20modification%20pour%20le%20club%20' . urlencode($club->nom) . '%20(ID:%20' . $club->id . ')%0D%0A%0D%0AMerci." class="ufsc-btn ufsc-btn-primary">
                <i class="dashicons dashicons-email"></i> Demander une modification</a></p>
                </div>';

    $output .= '</div>'; // Fin card-body
    $output .= '</div>'; // Fin card

    // Dirigeants du club
    $output .= '<div class="ufsc-card">
                <div class="ufsc-card-header">Dirigeants du club</div>
                <div class="ufsc-card-body">
                    <div class="ufsc-dirigeants-grid">';

    $roles = [
        'president' => 'Président',
        'secretaire' => 'Secrétaire',
        'tresorier' => 'Trésorier',
        'entraineur' => 'Entraîneur'
    ];

    foreach ($roles as $key => $label) {
        if (!empty($club->{$key . '_nom'})) {
            $prenom = !empty($club->{$key . '_prenom'}) ? $club->{$key . '_prenom'} : '';
            $full_name = trim($prenom . ' ' . $club->{$key . '_nom'});
            
            $output .= '<div class="ufsc-dirigeant-card" data-role="' . esc_attr($key) . '">
                        <div class="ufsc-dirigeant-role" data-role="' . esc_attr($key) . '">' . esc_html($label) . '</div>
                        <div class="ufsc-dirigeant-name">' . esc_html($full_name) . '</div>';

            if (!empty($club->{$key . '_email'})) {
                $output .= '<div class="ufsc-dirigeant-contact">
                            <i class="dashicons dashicons-email"></i>
                            <span>' . esc_html($club->{$key . '_email'}) . '</span>
                            </div>';
            }

            if (!empty($club->{$key . '_tel'})) {
                $output .= '<div class="ufsc-dirigeant-contact">
                            <i class="dashicons dashicons-phone"></i>
                            <span>' . esc_html($club->{$key . '_tel'}) . '</span>
                            </div>';
            }

            $output .= '</div>';
        }
    }

    $output .= '</div>
                </div>
                </div>';

    return $output;
}

/**
 * Formulaire de demande de licence
 */
function ufsc_dashboard_licence_form($club)
{
    // Code du formulaire de licence
    $output = '<h2 class="ufsc-section-title">Ajouter un licencié</h2>';

    // Vérifier si le quota est atteint
    $club_manager = UFSC_Club_Manager::get_instance();
    $licences = $club_manager->get_licences_by_club($club->id);
    $licences_count = count($licences);
    $quota_total = intval($club->quota_licences) > 0 ? intval($club->quota_licences) : 0;
    $quota_remaining = max(0, $quota_total - $licences_count);

    if ($quota_remaining <= 0) {
        $output .= '<div class="ufsc-alert ufsc-alert-warning">
                    <h4>Quota de licences épuisé</h4>
                    <p>Vous avez utilisé l\'ensemble de vos licences incluses dans votre pack d\'affiliation.</p>
                    <p>Pour demander des licences supplémentaires, merci de contacter l\'administration.</p>
                    <p><a href="mailto:contact@ufsc-france.org?subject=Demande%20d%27augmentation%20de%20quota" class="ufsc-btn">Contacter l\'administration</a></p>
                    </div>';
        return $output;
    }

    // Si un formulaire de licence existe déjà, l'inclure ici
    if (function_exists('ufsc_render_licence_form')) {
        ob_start();
        ufsc_render_licence_form($club->id);
        $form_content = ob_get_clean();
        $output .= $form_content;
    } else {
        // Formulaire de licence basique si la fonction n'existe pas
        $output .= '<div class="ufsc-card">
                    <div class="ufsc-card-header">Nouvelle licence</div>
                    <div class="ufsc-card-body">
                        <form method="post" class="ufsc-form">
                            ' . wp_nonce_field('ufsc_save_licence', 'ufsc_licence_nonce', true, false) . '
                            <input type="hidden" name="club_id" value="' . esc_attr($club->id) . '">
                            
                            <div class="ufsc-form-row">
                                <label for="nom">Nom <span class="ufsc-form-required">*</span></label>
                                <div><input type="text" name="nom" id="nom" required></div>
                            </div>
                            
                            <div class="ufsc-form-row">
                                <label for="prenom">Prénom <span class="ufsc-form-required">*</span></label>
                                <div><input type="text" name="prenom" id="prenom" required></div>
                            </div>
                            
                            <div class="ufsc-form-row">
                                <label for="email">Email</label>
                                <div><input type="email" name="email" id="email"></div>
                            </div>
                            
                            <div class="ufsc-form-row">
                                <label for="telephone">Téléphone</label>
                                <div><input type="tel" name="telephone" id="telephone"></div>
                            </div>
                            
                            <div class="ufsc-form-row">
                                <label for="fonction">Fonction</label>
                                <div>
                                    <select name="fonction" id="fonction">
                                        <option value="">-- Sélectionner --</option>
                                        <option value="Dirigeant">Dirigeant</option>
                                        <option value="Entraîneur">Entraîneur</option>
                                        <option value="Compétiteur">Compétiteur</option>
                                        <option value="Loisir">Loisir</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="ufsc-form-row" style="justify-content: flex-end; margin-top: 30px;">
                                <div></div>
                                <div>
                                    <button type="submit" class="ufsc-btn ufsc-btn-red" name="ufsc_save_licence_submit">Enregistrer la licence</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    </div>';
    }

    return $output;
}

/**
 * Process club logo upload
 */
function ufsc_process_club_logo_upload()
{
    // Check if this is a logo upload request
    if (!isset($_POST['action']) || $_POST['action'] !== 'ufsc_update_club_logo') {
        return;
    }

    // Verify nonce
    if (!wp_verify_nonce($_POST['ufsc_logo_nonce'] ?? '', 'ufsc_update_club_logo')) {
        wp_die('Erreur de sécurité', 'Erreur', ['response' => 403]);
    }

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_die('Vous devez être connecté', 'Erreur', ['response' => 401]);
    }

    // Get user's club
    $access_check = ufsc_check_frontend_access('home');
    if (!$access_check['allowed']) {
        wp_die('Accès refusé', 'Erreur', ['response' => 403]);
    }

    $club = $access_check['club'];
    $club_manager = UFSC_Club_Manager::get_instance();

    // Check if file was uploaded
    if (empty($_FILES['club_logo']['name'])) {
        $redirect_url = add_query_arg([
            'view' => 'home',
            'logo_update' => 'error',
            'message' => urlencode('Aucun fichier sélectionné')
        ], get_permalink());
        wp_redirect($redirect_url);
        exit;
    }

    $file = $_FILES['club_logo'];
    
    // Validate file
    $validation = ufsc_validate_logo_upload($file);
    if (is_wp_error($validation)) {
        $redirect_url = add_query_arg([
            'view' => 'home',
            'logo_update' => 'error',
            'message' => urlencode($validation->get_error_message())
        ], get_permalink());
        wp_redirect($redirect_url);
        exit;
    }

    // Handle upload
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($file, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        // Update club logo URL
        $club_manager->update_club_field($club->id, 'logo_url', $movefile['url']);
        
        $redirect_url = add_query_arg([
            'view' => 'home',
            'logo_update' => 'success',
            'message' => urlencode('Logo mis à jour avec succès')
        ], get_permalink());
    } else {
        $redirect_url = add_query_arg([
            'view' => 'home',
            'logo_update' => 'error',
            'message' => urlencode('Erreur lors de l\'upload: ' . ($movefile['error'] ?? 'Erreur inconnue'))
        ], get_permalink());
    }

    wp_redirect($redirect_url);
    exit;
}

/**
 * Validate logo upload
 */
function ufsc_validate_logo_upload($file)
{
    // Check file size (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        return new WP_Error('file_too_large', 'Le fichier est trop volumineux (2MB maximum)');
    }

    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $file_type = wp_check_filetype($file['name']);
    
    if (!in_array($file['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
        return new WP_Error('invalid_file_type', 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
    }

    return true;
}

// Register logo upload handler
add_action('init', 'ufsc_process_club_logo_upload');

add_shortcode('ufsc_club_dashboard', 'ufsc_club_dashboard_shortcode');
