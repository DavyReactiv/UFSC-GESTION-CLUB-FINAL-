<?php
/**
 * Dashboard MVP Data Helpers
 * 
 * Helper functions for retrieving and processing dashboard data
 * 
 * @package UFSC_Gestion_Club
 * @subpackage Frontend\Helpers
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get comprehensive club statistics
 */
function ufsc_get_club_stats($club_id) {
    global $wpdb;
    
    // Safety check for club manager
    if (!class_exists('UFSC_Club_Manager')) {
        return [
            'total_licences' => 0,
            'active_licences' => 0,
            'available_licences' => 0,
            'expiring_soon' => 0
        ];
    }
    
    $club_manager = UFSC_Club_Manager::get_instance();
    $club = $club_manager->get_club($club_id);
    $licences = $club_manager->get_licences_by_club($club_id);
    
    $stats = [
        'total_licences' => count($licences),
        'active_licences' => 0,
        'available_licences' => 0,
        'expiring_soon' => 0
    ];
    
    // Get quota information
    $quota_total = !empty($club->quota_licences) ? intval($club->quota_licences) : 10; // Default to 10
    
    $today = new DateTime();
    $thirty_days_from_now = new DateTime('+30 days');
    
    foreach ($licences as $licence) {
        // Count active licenses
        if ($licence->statut === 'validee' || $licence->statut === 'active') {
            $stats['active_licences']++;
            
            // Check if expiring soon (if date_expiration exists)
            if (!empty($licence->date_expiration)) {
                $expiry_date = new DateTime($licence->date_expiration);
                if ($expiry_date <= $thirty_days_from_now && $expiry_date > $today) {
                    $stats['expiring_soon']++;
                }
            }
        }
    }
    
    // Calculate available licenses (quota - total used)
    $stats['available_licences'] = max(0, $quota_total - $stats['total_licences']);
    
    return $stats;
}

/**
 * Get detailed club statistics with distributions
 */
function ufsc_get_club_detailed_stats($club_id) {
    // Safety check for club manager
    if (!class_exists('UFSC_Club_Manager')) {
        return [
            'total_licences' => 0,
            'by_gender' => ['M' => 0, 'F' => 0],
            'by_age_group' => [
                'U11' => 0,
                '12-15' => 0,
                '16-18' => 0,
                '19-34' => 0,
                '35-49' => 0,
                '50+' => 0
            ],
            'by_type' => ['competition' => 0, 'leisure' => 0]
        ];
    }
    
    $club_manager = UFSC_Club_Manager::get_instance();
    $licences = $club_manager->get_licences_by_club($club_id);
    
    $stats = [
        'total_licences' => count($licences),
        'by_gender' => ['M' => 0, 'F' => 0],
        'by_age_group' => [
            'U11' => 0,
            '12-15' => 0,
            '16-18' => 0,
            '19-34' => 0,
            '35-49' => 0,
            '50+' => 0
        ],
        'by_type' => ['competition' => 0, 'leisure' => 0]
    ];
    
    foreach ($licences as $licence) {
        // Gender distribution
        $gender = strtoupper($licence->sexe ?? 'M');
        if (isset($stats['by_gender'][$gender])) {
            $stats['by_gender'][$gender]++;
        }
        
        // Age group distribution
        if (!empty($licence->date_naissance)) {
            $age_group = ufsc_get_age_group($licence->date_naissance);
            if (isset($stats['by_age_group'][$age_group])) {
                $stats['by_age_group'][$age_group]++;
            }
        }
        
        // Competition vs leisure
        $is_competition = !empty($licence->competition) && $licence->competition == 1;
        if ($is_competition) {
            $stats['by_type']['competition']++;
        } else {
            $stats['by_type']['leisure']++;
        }
    }
    
    return $stats;
}

/**
 * Get quota pack information
 */
function ufsc_get_quota_pack_info($club_id) {
    global $wpdb;
    
    // Safety check for club manager
    if (!class_exists('UFSC_Club_Manager')) {
        return [
            'quota_total' => 10,
            'inclus_used' => 0,
            'inclus_percentage' => 0,
            'bureau_used' => 0,
            'bureau_percentage' => 0,
            'bureau_note' => ''
        ];
    }
    
    $club_manager = UFSC_Club_Manager::get_instance();
    $club = $club_manager->get_club($club_id);
    $licences = $club_manager->get_licences_by_club($club_id);
    
    // Get quota total (default to 10 if empty)
    $quota_total = !empty($club->quota_licences) ? intval($club->quota_licences) : 10;
    
    // Count included licenses
    $inclus_used = 0;
    $bureau_used = 0;
    $bureau_note = '';

    if (!function_exists('remove_accents')) {
        require_once ABSPATH . WPINC . '/formatting.php';
    }

    foreach ($licences as $licence) {
        // Count included licenses (assuming is_included field or using total count)
        if (!empty($licence->is_included) && $licence->is_included == 1) {
            $inclus_used++;
        }
        
        // Count board members (if role field exists)
        if (!empty($licence->fonction) || !empty($licence->role)) {
            $role = $licence->fonction ?? $licence->role ?? '';
            $role_clean = strtolower(remove_accents($role));

            if (in_array($role_clean, ['president', 'secretaire', 'tresorier'], true)) {
                $bureau_used++;
            }
        }
    }
    
    // If no role field mapping exists, use fallback
    if ($bureau_used === 0 && count($licences) > 0) {
        $bureau_note = 'Renseignement du rôle bientôt disponible';
    }
    
    // If no is_included field, use total count as fallback
    if ($inclus_used === 0) {
        $inclus_used = count($licences);
    }
    
    // Calculate percentages
    $inclus_percentage = $quota_total > 0 ? min(100, round(($inclus_used / $quota_total) * 100, 1)) : 0;
    $bureau_percentage = min(100, round(($bureau_used / 3) * 100, 1));
    
    return [
        'quota_total' => $quota_total,
        'inclus_used' => $inclus_used,
        'inclus_percentage' => $inclus_percentage,
        'bureau_used' => $bureau_used,
        'bureau_percentage' => $bureau_percentage,
        'bureau_note' => $bureau_note
    ];
}

/**
 * Get age group from birth date
 */
function ufsc_get_age_group($birth_date) {
    $birth = new DateTime($birth_date);
    $today = new DateTime();
    $age = $today->diff($birth)->y;
    
    if ($age <= 11) {
        return 'U11';
    } elseif ($age <= 15) {
        return '12-15';
    } elseif ($age <= 18) {
        return '16-18';
    } elseif ($age <= 34) {
        return '19-34';
    } elseif ($age <= 49) {
        return '35-49';
    } else {
        return '50+';
    }
}

/**
 * Get recent licenses for a club
 */
function ufsc_get_recent_licenses($club_id, $limit = 5) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ufsc_licences';
    
    $query = $wpdb->prepare(
        "SELECT * FROM {$table_name} 
         WHERE club_id = %d 
         ORDER BY date_creation DESC 
         LIMIT %d",
        $club_id,
        $limit
    );
    
    return $wpdb->get_results($query);
}

/**
 * Render club status badge
 */
function ufsc_render_club_status_badge($status) {
    switch (strtolower($status)) {
        case 'actif':
        case 'validé':
        case 'affilié':
            return '<span class="ufsc-badge ufsc-badge-success">Validé</span>';
        case 'en cours de validation':
        case 'en attente':
        case 'en cours de création':
            return '<span class="ufsc-badge ufsc-badge-pending">En attente</span>';
        case 'refusé':
            return '<span class="ufsc-badge ufsc-badge-error">Refusé</span>';
        default:
            return '<span class="ufsc-badge ufsc-badge-inactive">Inactif</span>';
    }
}

/**
 * Render license status badge
 */
function ufsc_render_license_status_badge($status, $payment_status = '') {
    return ufsc_get_license_status_badge($status, $payment_status);
}

/**
 * Render club attestation download link
 */
function ufsc_render_club_attestation_download($club, $type) {
    $field_map = [
        'affiliation' => 'doc_attestation_affiliation',
        'assurance' => 'doc_attestation_assurance'
    ];
    
    $labels = [
        'affiliation' => 'Attestation d\'affiliation',
        'assurance' => 'Attestation d\'assurance'
    ];
    
    if (!isset($field_map[$type]) || !isset($labels[$type])) {
        return '';
    }
    
    $field_name = $field_map[$type];
    $label = $labels[$type];
    
    // Check if attestation exists
    $attestation_raw = $club->{$field_name} ?? '';
    
    if (empty($attestation_raw)) {
        return '';
    }
    
    // Resolve document URL
    $attestation_url = ufsc_resolve_document_url($attestation_raw);
    
    if (empty($attestation_url)) {
        return '';
    }
    
    return sprintf(
        '<div class="ufsc-download-item">
            <span class="ufsc-download-name">%s</span>
            <a href="%s" class="ufsc-btn ufsc-btn-sm ufsc-btn-outline" target="_blank" rel="noopener">
                📄 Télécharger
            </a>
        </div>',
        esc_html($label),
        esc_url($attestation_url)
    );
}

/**
 * Helper to render attestation link for club/license attestations
 * 
 * @param string $raw Raw attestation value (ID or URL)
 * @param string $label Display label for the attestation
 * @return string HTML output for attestation link
 */
function ufsc_render_attestation_link($raw, $label) {
    if (empty($raw)) {
        return '';
    }
    
    $attestation_url = ufsc_resolve_document_url($raw);
    
    if (empty($attestation_url)) {
        return '';
    }
    
    return sprintf(
        '<div class="ufsc-attestation-link">
            <span class="ufsc-attestation-name">%s</span>
            <a href="%s" class="ufsc-btn ufsc-btn-sm ufsc-btn-outline" target="_blank" rel="noopener">
                📄 Télécharger
            </a>
        </div>',
        esc_html($label),
        esc_url($attestation_url)
    );
}

/**
 * Resolve document URL from ID or URL
 */
if (!function_exists('ufsc_resolve_document_url')) {
function ufsc_resolve_document_url($raw) {
    if (empty($raw)) {
        return '';
    }
    
    // If it's already a URL, return it
    if (filter_var($raw, FILTER_VALIDATE_URL)) {
        return esc_url($raw);
    }
    
    // If it's a numeric ID, get attachment URL
    if (is_numeric($raw)) {
        $url = wp_get_attachment_url(intval($raw));
        return $url ? esc_url($url) : '';
    }
    
    // If it's a relative path, make it absolute
    if (strpos($raw, '/') === 0) {
        return esc_url(home_url($raw));
    }
    
    return '';
}
}

/**
 * Get recent individual attestations
 */
function ufsc_get_recent_individual_attestations($club_id, $limit = 3) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'ufsc_licences';
    
    $query = $wpdb->prepare(
        "SELECT nom, prenom, attestation_url 
         FROM {$table_name} 
         WHERE club_id = %d 
         AND attestation_url IS NOT NULL 
         AND attestation_url != '' 
         AND statut = 'validee'
         ORDER BY date_creation DESC 
         LIMIT %d",
        $club_id,
        $limit
    );
    
    $results = $wpdb->get_results($query);
    $attestations = [];
    
    foreach ($results as $result) {
        $attestations[] = [
            'name' => $result->prenom . ' ' . $result->nom,
            'url' => ufsc_resolve_document_url($result->attestation_url)
        ];
    }
    
    return array_filter($attestations, function($item) {
        return !empty($item['url']);
    });
}

/**
 * Check if user is club manager
 */
function ufsc_is_user_club_manager() {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user_id = get_current_user_id();
    $club = ufsc_get_user_club($user_id);
    
    return !empty($club);
}