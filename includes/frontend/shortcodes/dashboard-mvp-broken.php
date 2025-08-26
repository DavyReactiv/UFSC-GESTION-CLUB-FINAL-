<?php
/**
 * Dashboard MVP Implementation
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
    ?>
    <div class="ufsc-dashboard-mvp">
        
        <!-- Header Section -->
        <?php ufsc_render_dashboard_header($club); ?>
        
        <!-- KPIs Section -->
        <?php ufsc_render_dashboard_kpis($club); ?>
        
        <!-- Quota Pack Affiliation Section -->
        <?php ufsc_render_quota_pack_section($club); ?>
        
        <!-- Statistics Section -->
        <?php ufsc_render_dashboard_statistics($club); ?>
        
        <!-- Downloads Section -->
        <?php ufsc_render_downloads_section($club); ?>
        
        <!-- Recent Licenses Section -->
        <?php ufsc_render_recent_licenses_section($club); ?>
        
    </div>
    <?php
}

/**
 * Render dashboard header with logo and status
 */
function ufsc_render_dashboard_header($club) {
    ?>
    <div class="ufsc-dashboard-header">
        <div class="ufsc-club-identity">
            <div class="ufsc-club-logo-container">
                <?php ufsc_render_club_logo_with_upload($club); ?>
            </div>
            <div class="ufsc-club-info">
                <h1><?php echo esc_html($club->nom); ?> <?php echo ufsc_render_club_status_badge($club->statut); ?></h1>
                <?php if (!empty($club->num_affiliation)): ?>
                    <p class="ufsc-affiliation-number">N° Affiliation: <?php echo esc_html($club->num_affiliation); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
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
    
    ?>
    <div class="ufsc-logo-upload-section">
        <div class="ufsc-club-logo-display">
            <?php if ($has_logo): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo du club" class="ufsc-club-logo">
            <?php else: ?>
                <div class="ufsc-no-logo">
                    <div class="ufsc-no-logo-icon">🏢</div>
                    <span>Aucun logo</span>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (ufsc_is_club_active($club)): ?>
            <div class="ufsc-logo-actions">
                <button type="button" class="ufsc-upload-logo-btn ufsc-btn ufsc-btn-sm">
                    <?php echo $has_logo ? 'Changer le logo' : 'Ajouter le logo'; ?>
                </button>
                <?php if ($has_logo): ?>
                    <button type="button" class="ufsc-remove-logo-btn ufsc-btn ufsc-btn-outline ufsc-btn-sm">
                        Supprimer
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render KPI cards
 */
function ufsc_render_dashboard_kpis($club) {
    $stats = ufsc_get_club_stats($club->id);
    
    ?>
    <div class="ufsc-kpis-section">
        <div class="ufsc-kpis-grid">
            
            <!-- Total Licenses -->
            <div class="ufsc-kpi-card">
                <div class="ufsc-kpi-icon">📊</div>
                <div class="ufsc-kpi-content">
                    <div class="ufsc-kpi-number"><?php echo $stats['total_licences']; ?></div>
                    <div class="ufsc-kpi-label">Licences totales</div>
                </div>
            </div>
            
            <!-- Active Licenses -->
            <div class="ufsc-kpi-card">
                <div class="ufsc-kpi-icon">✅</div>
                <div class="ufsc-kpi-content">
                    <div class="ufsc-kpi-number"><?php echo $stats['active_licences']; ?></div>
                    <div class="ufsc-kpi-label">Licences actives</div>
                </div>
            </div>
            
            <!-- Available Licenses -->
            <div class="ufsc-kpi-card">
                <div class="ufsc-kpi-icon">🎯</div>
                <div class="ufsc-kpi-content">
                    <div class="ufsc-kpi-number"><?php echo $stats['available_licences']; ?></div>
                    <div class="ufsc-kpi-label">Licences disponibles</div>
                </div>
            </div>
            
            <!-- Expiring Soon -->
            <?php if ($stats['expiring_soon'] > 0): ?>
                <div class="ufsc-kpi-card ufsc-kpi-warning">
                    <div class="ufsc-kpi-icon">⚠️</div>
                    <div class="ufsc-kpi-content">
                        <div class="ufsc-kpi-number"><?php echo $stats['expiring_soon']; ?></div>
                        <div class="ufsc-kpi-label">Expirent bientôt</div>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    <?php
}

/**
 * Render quota pack affiliation section
 */
function ufsc_render_quota_pack_section($club) {
    $quota_info = ufsc_get_quota_pack_info($club->id);
    
    ?>
    <div class="ufsc-quota-section">
        <h3>Quota Pack Affiliation</h3>
        
        <div class="ufsc-quota-grid">
            <!-- Included Licenses -->
            <div class="ufsc-quota-card">
                <h4>Licences incluses</h4>
                <div class="ufsc-progress-bar">
                    <div class="ufsc-progress-fill" style="width: <?php echo $quota_info['inclus_percentage']; ?>%"></div>
                </div>
                <div class="ufsc-quota-text">
                    <?php echo $quota_info['inclus_used']; ?>/<?php echo $quota_info['quota_total']; ?>
                    <span class="ufsc-quota-label">(<?php echo $quota_info['inclus_percentage']; ?>%)</span>
                </div>
            </div>
            
            <!-- Board Members -->
            <div class="ufsc-quota-card">
                <h4>Membres du bureau</h4>
                <div class="ufsc-progress-bar">
                    <div class="ufsc-progress-fill" style="width: <?php echo $quota_info['bureau_percentage']; ?>%"></div>
                </div>
                <div class="ufsc-quota-text">
                    <?php echo $quota_info['bureau_used']; ?>/3
                    <span class="ufsc-quota-label">(Président/Secrétaire/Trésorier)</span>
                </div>
                <?php if ($quota_info['bureau_note']): ?>
                    <div class="ufsc-quota-note"><?php echo $quota_info['bureau_note']; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render statistics section
 */
function ufsc_render_dashboard_statistics($club) {
    $stats = ufsc_get_club_detailed_stats($club->id);
    
    ?>
    <div class="ufsc-stats-section">
        <h3>Statistiques</h3>
        
        <div class="ufsc-stats-grid">
            
            <!-- Gender Distribution -->
            <div class="ufsc-stat-card">
                <h4>Répartition par sexe</h4>
                <div class="ufsc-stat-bars">
                    <?php foreach ($stats['by_gender'] as $gender => $count): ?>
                        <?php 
                        $percentage = $stats['total_licences'] > 0 ? round(($count / $stats['total_licences']) * 100, 1) : 0;
                        $gender_label = $gender === 'M' ? 'Masculin' : 'Féminin';
                        ?>
                        <div class="ufsc-stat-bar">
                            <div class="ufsc-stat-label"><?php echo $gender_label; ?></div>
                            <div class="ufsc-bar-container">
                                <div class="ufsc-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                            <div class="ufsc-stat-value"><?php echo $count; ?> (<?php echo $percentage; ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Age Distribution -->
            <div class="ufsc-stat-card">
                <h4>Répartition par âge</h4>
                <div class="ufsc-stat-bars">
                    <?php foreach ($stats['by_age_group'] as $age_group => $count): ?>
                        <?php 
                        $percentage = $stats['total_licences'] > 0 ? round(($count / $stats['total_licences']) * 100, 1) : 0;
                        ?>
                        <div class="ufsc-stat-bar">
                            <div class="ufsc-stat-label"><?php echo esc_html($age_group); ?></div>
                            <div class="ufsc-bar-container">
                                <div class="ufsc-bar-fill" style="width: <?php echo $percentage; %>%"></div>
                            </div>
                            <div class="ufsc-stat-value"><?php echo $count; ?> (<?php echo $percentage; ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Competition vs Leisure -->
            <div class="ufsc-stat-card">
                <h4>Compétition vs Loisir</h4>
                <div class="ufsc-stat-bars">
                    <?php foreach ($stats['by_type'] as $type => $count): ?>
                        <?php 
                        $percentage = $stats['total_licences'] > 0 ? round(($count / $stats['total_licences']) * 100, 1) : 0;
                        $type_label = $type === 'competition' ? 'Compétition' : 'Loisir';
                        ?>
                        <div class="ufsc-stat-bar">
                            <div class="ufsc-stat-label"><?php echo $type_label; ?></div>
                            <div class="ufsc-bar-container">
                                <div class="ufsc-bar-fill" style="width: <?php echo $percentage; %>%"></div>
                            </div>
                            <div class="ufsc-stat-value"><?php echo $count; ?> (<?php echo $percentage; ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
        </div>
    </div>
    <?php
}

/**
 * Render downloads section
 */
function ufsc_render_downloads_section($club) {
    ?>
    <div class="ufsc-downloads-section">
        <h3>Téléchargements</h3>
        
        <div class="ufsc-downloads-grid">
            
            <!-- Club Attestations -->
            <div class="ufsc-download-group">
                <h4>Attestations du club</h4>
                <div class="ufsc-download-list">
                    <?php echo ufsc_render_club_attestation_download($club, 'affiliation'); ?>
                    <?php echo ufsc_render_club_attestation_download($club, 'assurance'); ?>
                </div>
            </div>
            
            <!-- Individual Attestations -->
            <?php $individual_attestations = ufsc_get_recent_individual_attestations($club->id); ?>
            <?php if (!empty($individual_attestations)): ?>
                <div class="ufsc-download-group">
                    <h4>Dernières attestations individuelles</h4>
                    <div class="ufsc-download-list">
                        <?php foreach ($individual_attestations as $attestation): ?>
                            <div class="ufsc-download-item">
                                <span class="ufsc-download-name"><?php echo esc_html($attestation['name']); ?></span>
                                <a href="<?php echo esc_url($attestation['url']); ?>" 
                                   class="ufsc-btn ufsc-btn-sm ufsc-btn-outline" 
                                   target="_blank" rel="noopener">Télécharger</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    <?php
}

/**
 * Render recent licenses section
 */
function ufsc_render_recent_licenses_section($club) {
    $recent_licenses = ufsc_get_recent_licenses($club->id, 5);
    
    ?>
    <div class="ufsc-recent-licenses-section">
        <h3>Dernières licences</h3>
        
        <?php if (!empty($recent_licenses)): ?>
            <div class="ufsc-licenses-table">
                <table class="ufsc-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Fonction</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_licenses as $license): ?>
                            <tr>
                                <td><?php echo esc_html($license->nom); ?></td>
                                <td><?php echo esc_html($license->prenom); ?></td>
                                <td><?php echo esc_html($license->fonction ?? 'Non renseigné'); ?></td>
                                <td><?php echo ufsc_render_license_status_badge($license->statut); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="ufsc-empty-state">
                <div class="ufsc-empty-icon">📝</div>
                <p>Aucune licence enregistrée</p>
                <?php if (ufsc_is_club_active($club)): ?>
                    <div class="ufsc-empty-actions">
                        <?php 
                        // Link to unified form instead of WooCommerce product
                        $add_page = function_exists('ufsc_get_safe_page_url')
                            ? ufsc_get_safe_page_url('ajouter_licencie')
                            : ['available' => true, 'url' => home_url('/ajouter-licencie/')];

                        if (!empty($add_page['available']) && !empty($add_page['url'])) {
                            echo '<a href="' . esc_url($add_page['url']) . '" class="ufsc-btn ufsc-btn-sm">Ajouter une première licence</a>';
                        } else {
                            echo '<a href="' . esc_url(home_url('/ajouter-licencie/')) . '" class="ufsc-btn ufsc-btn-sm">Ajouter une première licence</a>';
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
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
            UFSC_GESTION_CLUB_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('ufsc-club-logo', 'ufscLogoUpload', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'setLogoNonce' => wp_create_nonce('ufsc_set_club_logo_nonce'),
            'maxSizeMB' => 2
        ]);
    }
    
    // Enqueue dashboard styles
    wp_enqueue_style(
        'ufsc-dashboard-mvp',
        UFSC_PLUGIN_URL . 'assets/css/dashboard-mvp.css',
        [],
        UFSC_GESTION_CLUB_VERSION
    );
}