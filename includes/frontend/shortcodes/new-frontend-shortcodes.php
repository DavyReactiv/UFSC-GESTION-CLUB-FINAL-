<?php

/**
 * New Frontend Shortcodes for UFSC Plugin
 * 
 * Implements the new shortcodes required for the frontend refonte:
 * - [ufsc_licence_form]
 * - [ufsc_affiliation_form] 
 * - [ufsc_club_quota]
 * - [ufsc_club_stats]
 * - [ufsc_license_list]
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include form renderers
require_once plugin_dir_path(__FILE__) . '../forms/licence-form-render.php';
require_once plugin_dir_path(__FILE__) . '../forms/affiliation-form-render.php';

/**
 * [ufsc_licence_form] - Display license form (redirects to product page if needed)
 */
function ufsc_licence_form_shortcode($atts)
{
    $atts = shortcode_atts([
        'redirect_to_product' => 'yes',
        'show_title' => 'yes',
        'button_text' => ''
    ], $atts, 'ufsc_licence_form');

    $args = [
        'context' => 'shortcode',
        'redirect_to_product' => $atts['redirect_to_product'] === 'yes',
        'show_title' => $atts['show_title'] === 'yes'
    ];

    if (!empty($atts['button_text'])) {
        $args['submit_button_text'] = $atts['button_text'];
    }

    return ufsc_render_licence_form($args);
}
add_shortcode('ufsc_licence_form', 'ufsc_licence_form_shortcode');

/**
 * [ufsc_affiliation_form] - Display affiliation form (redirects to product page if needed)
 */
function ufsc_affiliation_form_shortcode($atts)
{
    $atts = shortcode_atts([
        'redirect_to_product' => 'yes',
        'show_title' => 'yes',
        'button_text' => ''
    ], $atts, 'ufsc_affiliation_form');

    $args = [
        'context' => 'shortcode',
        'redirect_to_product' => $atts['redirect_to_product'] === 'yes',
        'show_title' => $atts['show_title'] === 'yes'
    ];

    if (!empty($atts['button_text'])) {
        $args['submit_button_text'] = $atts['button_text'];
    }

    return ufsc_render_affiliation_form($args);
}
add_shortcode('ufsc_affiliation_form', 'ufsc_affiliation_form_shortcode');

/**
 * [ufsc_club_quota] - Display club quota information
 */
function ufsc_club_quota_shortcode($atts)
{
    $atts = shortcode_atts([
        'show_title' => 'yes',
        'format' => 'card' // 'card', 'inline', 'progress'
    ], $atts, 'ufsc_club_quota');

    // Check access
    $access_check = ufsc_check_frontend_access('quota');
    if (!$access_check['allowed']) {
        return $access_check['error_message'];
    }

    $club = $access_check['club'];
    $quota_info = ufsc_get_club_quota_info($club->id);

    $output = '';

    if ($atts['show_title'] === 'yes') {
        $output .= '<h4>Quota licences</h4>';
    }

    switch ($atts['format']) {
        case 'progress':
            if ($quota_info['has_quota']) {
                $percentage = $quota_info['total'] > 0 ? ($quota_info['used'] / $quota_info['total']) * 100 : 0;
                $output .= '<div class="ufsc-quota-progress">
                    <div class="ufsc-quota-bar">
                        <div class="ufsc-quota-fill" style="width: ' . esc_attr($percentage) . '%"></div>
                    </div>
                    <div class="ufsc-quota-text">' . $quota_info['used'] . ' / ' . $quota_info['total'] . ' licences utilisées</div>
                </div>';
            } else {
                $output .= '<div class="ufsc-quota-unlimited">
                    <span class="ufsc-badge ufsc-badge-success">Quota illimité</span>
                    <div class="ufsc-quota-text">' . $quota_info['used'] . ' licence(s) enregistrée(s)</div>
                </div>';
            }
            break;

        case 'inline':
            if ($quota_info['has_quota']) {
                $status_class = $quota_info['remaining'] <= 0 ? 'ufsc-quota-full' : 'ufsc-quota-available';
                $output .= '<span class="ufsc-quota-inline ' . $status_class . '">' . 
                          $quota_info['used'] . '/' . $quota_info['total'] . ' licences</span>';
            } else {
                $output .= '<span class="ufsc-quota-inline ufsc-quota-unlimited">' . 
                          $quota_info['used'] . ' licence(s) - Illimité</span>';
            }
            break;

        case 'card':
        default:
            $output .= '<div class="ufsc-quota-card">';
            if ($quota_info['has_quota']) {
                $status_class = $quota_info['remaining'] <= 0 ? 'ufsc-alert-error' : 'ufsc-alert-info';
                $output .= '<div class="ufsc-alert ' . $status_class . '">
                    <h5>Quota licences</h5>
                    <p><strong>' . $quota_info['used'] . '</strong> licence(s) utilisée(s) sur <strong>' . $quota_info['total'] . '</strong></p>';
                
                if ($quota_info['remaining'] > 0) {
                    $output .= '<p><span class="ufsc-badge ufsc-badge-success">' . $quota_info['remaining'] . ' restante(s)</span></p>';
                } else {
                    $output .= '<p><span class="ufsc-badge ufsc-badge-error">Quota atteint</span></p>';
                }
                $output .= '</div>';
            } else {
                $output .= '<div class="ufsc-alert ufsc-alert-success">
                    <h5>Quota licences</h5>
                    <p><strong>' . $quota_info['used'] . '</strong> licence(s) enregistrée(s)</p>
                    <p><span class="ufsc-badge ufsc-badge-success">Quota illimité</span></p>
                    </div>';
            }
            $output .= '</div>';
            break;
    }

    return $output;
}
add_shortcode('ufsc_club_quota', 'ufsc_club_quota_shortcode');

/**
 * [ufsc_club_stats] - Display club statistics/KPIs
 */
function ufsc_club_stats_shortcode($atts)
{
    $atts = shortcode_atts([
        'show_title' => 'yes',
        'layout' => 'cards', // 'cards', 'list', 'inline'
        'include' => 'licenses,affiliation,status' // comma-separated stats to include
    ], $atts, 'ufsc_club_stats');

    // Check access
    $access_check = ufsc_check_frontend_access('stats');
    if (!$access_check['allowed']) {
        return $access_check['error_message'];
    }

    $club = $access_check['club'];
    $include_stats = array_map('trim', explode(',', $atts['include']));

    // Gather statistics
    $stats = [];

    if (in_array('licenses', $include_stats)) {
        $quota_info = ufsc_get_club_quota_info($club->id);
        $stats['licenses'] = [
            'label' => 'Licences',
            'value' => $quota_info['used'],
            'suffix' => $quota_info['has_quota'] ? '/' . $quota_info['total'] : '',
            'icon' => '👥'
        ];
    }

    if (in_array('affiliation', $include_stats)) {
        $affiliation_date = !empty($club->date_affiliation) ? 
            date_i18n('j F Y', strtotime($club->date_affiliation)) : 'Non définie';
        $stats['affiliation'] = [
            'label' => 'Affiliation',
            'value' => $affiliation_date,
            'suffix' => '',
            'icon' => '📅'
        ];
    }

    if (in_array('status', $include_stats)) {
        $status_info = ufsc_get_club_status_message($club, 'stats');
        $status_display = $club->statut;
        if ($club->statut === 'Actif') {
            $status_display = '✅ Actif';
        }
        $stats['status'] = [
            'label' => 'Statut',
            'value' => $status_display,
            'suffix' => '',
            'icon' => '🏛️'
        ];
    }

    if (empty($stats)) {
        return '<div class="ufsc-alert ufsc-alert-warning">Aucune statistique à afficher.</div>';
    }

    $output = '';

    if ($atts['show_title'] === 'yes') {
        $output .= '<h4>Statistiques du club</h4>';
    }

    switch ($atts['layout']) {
        case 'list':
            $output .= '<ul class="ufsc-stats-list">';
            foreach ($stats as $key => $stat) {
                $output .= '<li class="ufsc-stat-item">
                    <span class="ufsc-stat-icon">' . $stat['icon'] . '</span>
                    <span class="ufsc-stat-label">' . esc_html($stat['label']) . ':</span>
                    <span class="ufsc-stat-value">' . esc_html($stat['value']) . $stat['suffix'] . '</span>
                </li>';
            }
            $output .= '</ul>';
            break;

        case 'inline':
            $output .= '<div class="ufsc-stats-inline">';
            $stat_items = [];
            foreach ($stats as $key => $stat) {
                $stat_items[] = '<span class="ufsc-stat-inline">
                    ' . $stat['icon'] . ' ' . esc_html($stat['value']) . $stat['suffix'] . ' ' . esc_html($stat['label']) . '
                </span>';
            }
            $output .= implode(' • ', $stat_items);
            $output .= '</div>';
            break;

        case 'cards':
        default:
            $output .= '<div class="ufsc-stats-cards">';
            foreach ($stats as $key => $stat) {
                $output .= '<div class="ufsc-stat-card">
                    <div class="ufsc-stat-icon">' . $stat['icon'] . '</div>
                    <div class="ufsc-stat-content">
                        <div class="ufsc-stat-value">' . esc_html($stat['value']) . $stat['suffix'] . '</div>
                        <div class="ufsc-stat-label">' . esc_html($stat['label']) . '</div>
                    </div>
                </div>';
            }
            $output .= '</div>';
            break;
    }

    return $output;
}
add_shortcode('ufsc_club_stats', 'ufsc_club_stats_shortcode');

/**
 * [ufsc_license_list] - Display filterable list of licenses
 */
function ufsc_license_list_shortcode($atts)
{
    $atts = shortcode_atts([
        'per_page' => '25',
        'status' => '', // empty = all, or specific status
        'search' => '', // pre-filled search
        'show_filters' => 'yes',
        'show_pagination' => 'yes',
        'show_actions' => 'yes'
    ], $atts, 'ufsc_license_list');

    // Check access
    $access_check = ufsc_check_frontend_access('licenses');
    if (!$access_check['allowed']) {
        return $access_check['error_message'];
    }

    $club = $access_check['club'];
    $per_page = intval($atts['per_page']);
    $current_page = isset($_GET['license_page']) ? max(1, intval($_GET['license_page'])) : 1;

    // Get license manager
    require_once plugin_dir_path(dirname(__FILE__)) . '../licences/class-licence-manager.php';
    $licence_manager = new UFSC_Licence_Manager();

    // Build filters
    $filters = ['club_id' => $club->id];
    
    if (!empty($atts['status'])) {
        $filters['statut'] = $atts['status'];
    }
    
    if (!empty($_GET['license_search'])) {
        $filters['search'] = sanitize_text_field($_GET['license_search']);
    } elseif (!empty($atts['search'])) {
        $filters['search'] = $atts['search'];
    }

    if (!empty($_GET['license_status']) && $_GET['license_status'] !== 'all') {
        $filters['statut'] = sanitize_text_field($_GET['license_status']);
    }

    // Get licenses with pagination
    $offset = ($current_page - 1) * $per_page;
    $licenses = $licence_manager->get_licences($filters);
    
    // Simple pagination - get total count and slice
    $total_licenses = count($licenses);
    $licenses = array_slice($licenses, $offset, $per_page);
    $total_pages = ceil($total_licenses / $per_page);

    $output = '<div class="ufsc-license-list-container">';

    // Filters
    if ($atts['show_filters'] === 'yes') {
        $current_status = $_GET['license_status'] ?? 'all';
        $current_search = $_GET['license_search'] ?? '';
        
        $output .= '<form method="get" class="ufsc-license-filters">
            <div class="ufsc-filter-row">
                <div class="ufsc-filter-field">
                    <label for="license_search">Rechercher:</label>
                    <input type="text" id="license_search" name="license_search" value="' . esc_attr($current_search) . '" placeholder="Nom, prénom...">
                </div>
                <div class="ufsc-filter-field">
                    <label for="license_status">Statut:</label>
                    <select id="license_status" name="license_status">
                        <option value="all"' . ($current_status === 'all' ? ' selected' : '') . '>Tous</option>
                        <option value="pending"' . ($current_status === 'pending' ? ' selected' : '') . '>En attente</option>
                        <option value="validated"' . ($current_status === 'validated' ? ' selected' : '') . '>Validé</option>
                        <option value="refused"' . ($current_status === 'refused' ? ' selected' : '') . '>Refusé</option>
                    </select>
                </div>
                <div class="ufsc-filter-actions">
                    <button type="submit" class="ufsc-btn ufsc-btn-sm">Filtrer</button>
                    <a href="?" class="ufsc-btn ufsc-btn-outline ufsc-btn-sm">Reset</a>
                </div>
            </div>
        </form>';
    }

    // License list
    if (empty($licenses)) {
        $output .= '<div class="ufsc-alert ufsc-alert-info">Aucune licence trouvée.</div>';
    } else {
        $output .= '<div class="ufsc-license-list">';
        
        foreach ($licenses as $license) {
            $status_class = 'ufsc-license-status-' . $license->statut;
            $status_label = ucfirst($license->statut);
            
            $output .= '<div class="ufsc-license-item ' . $status_class . '">
                <div class="ufsc-license-info">
                    <h5>' . esc_html($license->prenom . ' ' . $license->nom) . '</h5>
                    <div class="ufsc-license-meta">
                        <span class="ufsc-license-date">Né(e) le ' . esc_html(date_i18n('j F Y', strtotime($license->date_naissance))) . '</span>
                        <span class="ufsc-license-email">' . esc_html($license->email) . '</span>
                    </div>
                </div>
                <div class="ufsc-license-status">
                    <span class="ufsc-badge ufsc-badge-' . esc_attr($license->statut) . '">' . esc_html($status_label) . '</span>
                </div>';
            
            if ($atts['show_actions'] === 'yes') {
                $output .= '<div class="ufsc-license-actions">
                    <button class="ufsc-btn ufsc-btn-sm ufsc-btn-outline" onclick="ufscViewLicense(' . $license->id . ')">Voir</button>';
                
                if ($license->statut === 'validated') {
                    $output .= '<a href="#" class="ufsc-btn ufsc-btn-sm ufsc-btn-primary" onclick="ufscDownloadAttestation(' . $license->id . ')">Attestation</a>';
                }
                
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
    }

    // Pagination
    if ($atts['show_pagination'] === 'yes' && $total_pages > 1) {
        $output .= '<div class="ufsc-pagination">';
        
        if ($current_page > 1) {
            $prev_url = add_query_arg('license_page', $current_page - 1);
            $output .= '<a href="' . esc_url($prev_url) . '" class="ufsc-btn ufsc-btn-sm">← Précédent</a>';
        }
        
        $output .= '<span class="ufsc-pagination-info">Page ' . $current_page . ' sur ' . $total_pages . '</span>';
        
        if ($current_page < $total_pages) {
            $next_url = add_query_arg('license_page', $current_page + 1);
            $output .= '<a href="' . esc_url($next_url) . '" class="ufsc-btn ufsc-btn-sm">Suivant →</a>';
        }
        
        $output .= '</div>';
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('ufsc_license_list', 'ufsc_license_list_shortcode');