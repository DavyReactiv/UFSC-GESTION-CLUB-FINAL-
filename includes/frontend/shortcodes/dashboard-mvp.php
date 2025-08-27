<?php
/**
 * Dashboard MVP Implementation - Fixed Version
 * 
 * New simplified dashboard implementation that addresses all the issues:
 * - Logo upload functionality
 * - Statistics by age, gender, competition vs leisure
 * - Quota pack affiliation management
 * - Attestations display
 * - Recent licenses table
 * 
 * @package UFSC_Gestion_Club
 * @subpackage Frontend\Shortcodes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard MVP Shortcode
 */
function ufsc_club_dashboard_mvp_shortcode($atts) {
    // Use standardized frontend access control
    $access_check = ufsc_check_frontend_access('dashboard');
    
    if (!$access_check['allowed']) {
        return $access_check['error_message'];
    }
    
    $club = $access_check['club'];
    
    // Enqueue necessary assets
    ufsc_enqueue_dashboard_mvp_assets();
    
    // Start output buffering
    ob_start();
    
    // Render the MVP dashboard
    ufsc_render_dashboard_mvp($club);
    
    return ob_get_clean();
}

/**
 * Render the complete MVP dashboard
 */
function ufsc_render_dashboard_mvp($club) {
    echo '<div class="ufsc-dashboard-mvp">';
    
    // Header Section
    ufsc_render_dashboard_header($club);
    
    // KPIs Section
    ufsc_render_dashboard_kpis($club);
    
    // Quota Pack Affiliation Section
    ufsc_render_quota_pack_section($club);
    
    // Statistics Section
    ufsc_render_dashboard_statistics($club);
    
    // Downloads Section
    ufsc_render_downloads_section($club);
    
    // Recent Licenses Section
    ufsc_render_recent_licenses_section($club);
    
    echo '</div>';
}

/**
 * Render dashboard header with logo and status
 */
function ufsc_render_dashboard_header($club) {
    $output = '<div class="ufsc-dashboard-header">';
    $output .= '<div class="ufsc-club-identity">';
    $output .= '<div class="ufsc-club-logo-container">';
    $output .= ufsc_render_club_logo_with_upload($club);
    $output .= '</div>';
    $output .= '<div class="ufsc-club-info">';
    $output .= '<h1>' . esc_html($club->nom) . ' ' . ufsc_render_club_status_badge($club->statut) . '</h1>';
    
    if (!empty($club->num_affiliation)) {
        $output .= '<p class="ufsc-affiliation-number">N° Affiliation: ' . esc_html($club->num_affiliation) . '</p>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    echo $output;
}

/**
 * Render club logo with upload functionality
 */
function ufsc_render_club_logo_with_upload($club) {
    $logo_url = '';
    $has_logo = false;
    
    // Try to get logo from different sources
    if (!empty($club->logo_attachment_id)) {
        $logo_url = wp_get_attachment_image_url($club->logo_attachment_id, 'medium');
        $has_logo = !empty($logo_url);
    } elseif (!empty($club->logo_url)) {
        $logo_url = $club->logo_url;
        $has_logo = true;
    }
    
    $output = '<div class="ufsc-logo-upload-section">';
    $output .= '<div class="ufsc-club-logo-display">';
    
    if ($has_logo) {
        $output .= '<img src="' . esc_url($logo_url) . '" alt="Logo du club" class="ufsc-club-logo">';
    } else {
        $output .= '<div class="ufsc-no-logo">';
        $output .= '<div class="ufsc-no-logo-icon">🏢</div>';
        $output .= '<span>Aucun logo</span>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    if (ufsc_is_club_active($club)) {
        $output .= '<div class="ufsc-logo-actions">';
        $output .= '<button type="button" class="ufsc-upload-logo-btn ufsc-btn ufsc-btn-sm">';
        $output .= $has_logo ? 'Changer le logo' : 'Ajouter le logo';
        $output .= '</button>';
        
        if ($has_logo) {
            $output .= '<button type="button" class="ufsc-remove-logo-btn ufsc-btn ufsc-btn-outline ufsc-btn-sm">';
            $output .= 'Supprimer';
            $output .= '</button>';
        }
        
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Render KPI cards
 */
function ufsc_render_dashboard_kpis($club) {
    $stats = ufsc_get_club_stats($club->id);
    
    $output = '<div class="ufsc-kpis-section">';
    $output .= '<div class="ufsc-kpis-grid">';
    
    // Total Licenses
    $output .= '<div class="ufsc-kpi-card">';
    $output .= '<div class="ufsc-kpi-icon">📊</div>';
    $output .= '<div class="ufsc-kpi-content">';
    $output .= '<div class="ufsc-kpi-number">' . $stats['total_licences'] . '</div>';
    $output .= '<div class="ufsc-kpi-label">Licences totales</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Active Licenses
    $output .= '<div class="ufsc-kpi-card">';
    $output .= '<div class="ufsc-kpi-icon">✅</div>';
    $output .= '<div class="ufsc-kpi-content">';
    $output .= '<div class="ufsc-kpi-number">' . $stats['active_licences'] . '</div>';
    $output .= '<div class="ufsc-kpi-label">Licences actives</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Available Licenses
    $output .= '<div class="ufsc-kpi-card">';
    $output .= '<div class="ufsc-kpi-icon">🎯</div>';
    $output .= '<div class="ufsc-kpi-content">';
    $output .= '<div class="ufsc-kpi-number">' . $stats['available_licences'] . '</div>';
    $output .= '<div class="ufsc-kpi-label">Licences disponibles</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Expiring Soon
    if ($stats['expiring_soon'] > 0) {
        $output .= '<div class="ufsc-kpi-card ufsc-kpi-warning">';
        $output .= '<div class="ufsc-kpi-icon">⚠️</div>';
        $output .= '<div class="ufsc-kpi-content">';
        $output .= '<div class="ufsc-kpi-number">' . $stats['expiring_soon'] . '</div>';
        $output .= '<div class="ufsc-kpi-label">Expirent bientôt</div>';
        $output .= '</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    echo $output;
}

/**
 * Render quota pack affiliation section
 */
function ufsc_render_quota_pack_section($club) {
    $quota_info = ufsc_get_quota_pack_info($club->id);
    
    $output = '<div class="ufsc-quota-section">';
    $output .= '<h3>Quota Pack Affiliation</h3>';
    $output .= '<div class="ufsc-quota-grid">';
    
    // Included Licenses
    $output .= '<div class="ufsc-quota-card">';
    $output .= '<h4>Licences incluses</h4>';
    $output .= '<div class="ufsc-progress-bar">';
    $output .= '<div class="ufsc-progress-fill" style="width: ' . $quota_info['inclus_percentage'] . '%"></div>';
    $output .= '</div>';
    $output .= '<div class="ufsc-quota-text">';
    $output .= $quota_info['inclus_used'] . '/' . $quota_info['quota_total'];
    $output .= ' <span class="ufsc-quota-label">(' . $quota_info['inclus_percentage'] . '%)</span>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Board Members
    $output .= '<div class="ufsc-quota-card">';
    $output .= '<h4>Membres du bureau</h4>';
    $output .= '<div class="ufsc-progress-bar">';
    $output .= '<div class="ufsc-progress-fill" style="width: ' . $quota_info['bureau_percentage'] . '%"></div>';
    $output .= '</div>';
    $output .= '<div class="ufsc-quota-text">';
    $output .= $quota_info['bureau_used'] . '/3';
    $output .= ' <span class="ufsc-quota-label">(Président/Secrétaire/Trésorier)</span>';
    $output .= '</div>';
    
    if ($quota_info['bureau_note']) {
        $output .= '<div class="ufsc-quota-note">' . esc_html($quota_info['bureau_note']) . '</div>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    echo $output;
}

/**
 * Render statistics section
 */
function ufsc_render_dashboard_statistics($club) {
    $stats = ufsc_get_club_detailed_stats($club->id);
    
    $output = '<div class="ufsc-stats-section">';
    $output .= '<h3>Statistiques</h3>';
    $output .= '<div class="ufsc-stats-grid">';
    
    // Gender Distribution
    $output .= '<div class="ufsc-stat-card">';
    $output .= '<h4>Répartition par sexe</h4>';
    $output .= '<div class="ufsc-stat-bars">';
    
    foreach ($stats['by_gender'] as $gender => $count) {
        $percentage = $stats['total_licences'] > 0 ? round(($count / $stats['total_licences']) * 100, 1) : 0;
        $gender_label = $gender === 'M' ? 'Masculin' : 'Féminin';
        
        $output .= '<div class="ufsc-stat-bar">';
        $output .= '<div class="ufsc-stat-label">' . esc_html($gender_label) . '</div>';
        $output .= '<div class="ufsc-bar-container">';
        $output .= '<div class="ufsc-bar-fill" style="width: ' . $percentage . '%"></div>';
        $output .= '</div>';
        $output .= '<div class="ufsc-stat-value">' . $count . ' (' . $percentage . '%)</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    // Age Distribution
    $output .= '<div class="ufsc-stat-card">';
    $output .= '<h4>Répartition par âge</h4>';
    $output .= '<div class="ufsc-stat-bars">';
    
    foreach ($stats['by_age_group'] as $age_group => $count) {
        $percentage = $stats['total_licences'] > 0 ? round(($count / $stats['total_licences']) * 100, 1) : 0;
        
        $output .= '<div class="ufsc-stat-bar">';
        $output .= '<div class="ufsc-stat-label">' . esc_html($age_group) . '</div>';
        $output .= '<div class="ufsc-bar-container">';
        $output .= '<div class="ufsc-bar-fill" style="width: ' . $percentage . '%"></div>';
        $output .= '</div>';
        $output .= '<div class="ufsc-stat-value">' . $count . ' (' . $percentage . '%)</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    // Competition vs Leisure
    $output .= '<div class="ufsc-stat-card">';
    $output .= '<h4>Compétition vs Loisir</h4>';
    $output .= '<div class="ufsc-stat-bars">';
    
    foreach ($stats['by_type'] as $type => $count) {
        $percentage = $stats['total_licences'] > 0 ? round(($count / $stats['total_licences']) * 100, 1) : 0;
        $type_label = $type === 'competition' ? 'Compétition' : 'Loisir';
        
        $output .= '<div class="ufsc-stat-bar">';
        $output .= '<div class="ufsc-stat-label">' . $type_label . '</div>';
        $output .= '<div class="ufsc-bar-container">';
        $output .= '<div class="ufsc-bar-fill" style="width: ' . $percentage . '%"></div>';
        $output .= '</div>';
        $output .= '<div class="ufsc-stat-value">' . $count . ' (' . $percentage . '%)</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    echo $output;
}

/**
 * Render downloads section
 */
function ufsc_render_downloads_section($club) {
    $output = '<div class="ufsc-downloads-section">';
    $output .= '<h3>Téléchargements</h3>';
    $output .= '<div class="ufsc-downloads-grid">';
    
    // Club Attestations
    $output .= '<div class="ufsc-download-group">';
    $output .= '<h4>Attestations du club</h4>';
    $output .= '<div class="ufsc-download-list">';
    $output .= ufsc_render_club_attestation_download($club, 'affiliation');
    $output .= ufsc_render_club_attestation_download($club, 'assurance');
    $output .= '</div>';
    $output .= '</div>';
    
    // Individual Attestations
    $individual_attestations = ufsc_get_recent_individual_attestations($club->id);
    if (!empty($individual_attestations)) {
        $output .= '<div class="ufsc-download-group">';
        $output .= '<h4>Dernières attestations individuelles</h4>';
        $output .= '<div class="ufsc-download-list">';
        
        foreach ($individual_attestations as $attestation) {
            $output .= '<div class="ufsc-download-item">';
            $output .= '<span class="ufsc-download-name">' . esc_html($attestation['name']) . '</span>';
            $output .= '<a href="' . esc_url($attestation['url']) . '" class="ufsc-btn ufsc-btn-sm ufsc-btn-outline" target="_blank" rel="noopener">Télécharger</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    $output .= '</div>';
    
    echo $output;
}

/**
 * Render recent licenses section
 */
function ufsc_render_recent_licenses_section($club) {
    $recent_licenses = ufsc_get_recent_licenses($club->id, 5);
    
    $output = '<div class="ufsc-recent-licenses-section">';
    $output .= '<h3>Dernières licences</h3>';
    
    if (!empty($recent_licenses)) {
        $output .= '<div class="ufsc-licenses-table">';
        $output .= '<table class="ufsc-table">';
        $output .= '<thead>';
        $output .= '<tr>';
        $output .= '<th>Nom</th>';
        $output .= '<th>Prénom</th>';
        $output .= '<th>Fonction</th>';
        $output .= '<th>Statut</th>';
        $output .= '</tr>';
        $output .= '</thead>';
        $output .= '<tbody>';
        
        foreach ($recent_licenses as $license) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($license->nom) . '</td>';
            $output .= '<td>' . esc_html($license->prenom) . '</td>';
            $output .= '<td>' . esc_html($license->fonction ?? 'Non renseigné') . '</td>';
            $output .= '<td>' . ufsc_render_license_status_badge($license->statut) . '</td>';
            $output .= '</tr>';
        }
        
        $output .= '</tbody>';
        $output .= '</table>';
        $output .= '</div>';
    } else {
        $output .= '<div class="ufsc-empty-state">';
        $output .= '<div class="ufsc-empty-icon">📝</div>';
        $output .= '<p>Aucune licence enregistrée</p>';
        
        if (ufsc_is_club_active($club)) {
            $output .= '<div class="ufsc-empty-actions">';
            
            // Link to unified form instead of WooCommerce product
            $add_page = function_exists('ufsc_get_safe_page_url')
                ? ufsc_get_safe_page_url('ajouter_licencie')
                : ['available' => true, 'url' => home_url('/ajouter-licencie/')];

            if (!empty($add_page['available']) && !empty($add_page['url'])) {
                $output .= '<a href="' . esc_url($add_page['url']) . '" class="ufsc-btn ufsc-btn-sm">Ajouter une première licence</a>';
            } else {
                $output .= '<a href="' . esc_url(home_url('/ajouter-licencie/')) . '" class="ufsc-btn ufsc-btn-sm">Ajouter une première licence</a>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    echo $output;
}

/**
 * Enqueue dashboard MVP assets
 */
function ufsc_enqueue_dashboard_mvp_assets() {
    // Enqueue logo upload script for club managers (without wp_enqueue_media)
    if (ufsc_is_user_club_manager()) {
        // Enqueue logo upload script
        wp_enqueue_script(
            'ufsc-club-logo',
            UFSC_PLUGIN_URL . 'assets/js/ufsc-club-logo.js',
            ['jquery'],
            UFSC_PLUGIN_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('ufsc-club-logo', 'ufscLogoUpload', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'setLogoNonce' => ufsc_create_nonce('ufsc_set_club_logo_nonce'),
            'maxSizeMB' => 2
        ]);
    }
    
    // Enqueue dashboard styles
    wp_enqueue_style(
        'ufsc-dashboard-mvp',
        UFSC_PLUGIN_URL . 'assets/css/dashboard-mvp.css',
        [],
        UFSC_PLUGIN_VERSION
    );
}