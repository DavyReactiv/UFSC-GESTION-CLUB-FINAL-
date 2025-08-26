<?php
/**
 * Dashboard Cards Display
 * 
 * Renders enhanced dashboard cards with club information, KPIs, and quick actions
 * 
 * @package UFSC_Gestion_Club
 * @subpackage Frontend\Club\Partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render enhanced dashboard cards
 * 
 * @param object $club Club object
 * @return string HTML output
 */
function ufsc_render_dashboard_cards($club) {
    if (!$club) {
        return '';
    }
    
    // Enqueue dashboard assets
    ufsc_enqueue_dashboard_assets();
    
    ob_start();
    ?>
    <div class="ufsc-dashboard-grid">
        
        <!-- Club Identity Card -->
        <div class="ufsc-dashboard-card">
            <div class="ufsc-dashboard-card-header">
                <h3>Identité du club</h3>
            </div>
            <div class="ufsc-dashboard-card-body">
                <?php echo ufsc_render_club_identity_card($club); ?>
            </div>
        </div>
        
        <!-- Enhanced KPIs Card with Quota Management -->
        <div class="ufsc-dashboard-card">
            <div class="ufsc-dashboard-card-header">
                <h3>Licences & Quota</h3>
            </div>
            <div class="ufsc-dashboard-card-body">
                <?php echo ufsc_render_enhanced_licence_kpis($club); ?>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <div class="ufsc-dashboard-card">
            <div class="ufsc-dashboard-card-header">
                <h3>Actions rapides</h3>
            </div>
            <div class="ufsc-dashboard-card-body">
                <?php echo ufsc_render_quick_actions_card($club); ?>
            </div>
        </div>
        
        <!-- Recent Activity Card -->
        <div class="ufsc-dashboard-card ufsc-dashboard-card-wide">
            <div class="ufsc-dashboard-card-header">
                <h3>Activité récente</h3>
            </div>
            <div class="ufsc-dashboard-card-body">
                <?php echo ufsc_render_recent_activity_card($club); ?>
            </div>
        </div>
        
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render enhanced licence KPIs with quota management
 * 
 * @param object $club Club object
 * @return string HTML output
 */
function ufsc_render_enhanced_licence_kpis($club) {
    // Include quota helper if not already included
    if (!function_exists('ufsc_get_club_quota_info')) {
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'forms/licence-form-render.php';
    }
    
    $quota_info = ufsc_get_club_quota_info($club->id);
    
    ob_start();
    ?>
    <div class="ufsc-kpis-container">
        <?php if ($quota_info['has_quota']): ?>
            <div class="ufsc-quota-card <?php echo $quota_info['remaining'] <= 0 ? 'ufsc-quota-full' : ''; ?>">
                <div class="ufsc-quota-visual">
                    <div class="ufsc-quota-circle">
                        <div class="ufsc-quota-number"><?php echo esc_html($quota_info['used']); ?></div>
                        <div class="ufsc-quota-total">/ <?php echo esc_html($quota_info['total']); ?></div>
                    </div>
                    <?php 
                    $percentage = $quota_info['total'] > 0 ? ($quota_info['used'] / $quota_info['total']) * 100 : 0;
                    ?>
                    <div class="ufsc-quota-progress-circle" style="--progress: <?php echo esc_attr($percentage); ?>%"></div>
                </div>
                <div class="ufsc-quota-info">
                    <h4>Quota licences</h4>
                    <?php if ($quota_info['remaining'] > 0): ?>
                        <p class="ufsc-quota-remaining"><strong><?php echo $quota_info['remaining']; ?></strong> licence(s) restante(s)</p>
                    <?php else: ?>
                        <p class="ufsc-quota-full-text"><span class="ufsc-badge ufsc-badge-error">Quota atteint</span></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="ufsc-quota-unlimited-card">
                <div class="ufsc-quota-visual">
                    <div class="ufsc-quota-infinity">∞</div>
                    <div class="ufsc-quota-number"><?php echo esc_html($quota_info['used']); ?></div>
                </div>
                <div class="ufsc-quota-info">
                    <h4>Licences enregistrées</h4>
                    <p><span class="ufsc-badge ufsc-badge-success">Quota illimité</span></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render quick actions card
 * 
 * @param object $club Club object
 * @return string HTML output
 */
function ufsc_render_quick_actions_card($club) {
    // Include button helpers if not already included
    if (!function_exists('ufsc_generate_dashboard_action_buttons')) {
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'helpers/helpers-product-buttons.php';
    }
    
    ob_start();
    ?>
    <div class="ufsc-quick-actions">
        <?php if (ufsc_is_club_active($club)): ?>
            <div class="ufsc-action-group">
                <h5>Gestion des licences</h5>
                <?php 
                // Link to unified form instead of WooCommerce product
                $add_page = function_exists('ufsc_get_safe_page_url')
                    ? ufsc_get_safe_page_url('ajouter_licencie')
                    : ['available' => true, 'url' => home_url('/ajouter-licencie/')];

                if (!empty($add_page['available']) && !empty($add_page['url'])) {
                    echo '<a href="' . esc_url($add_page['url']) . '" class="ufsc-btn ufsc-btn-block">Nouvelle licence</a>';
                } else {
                    echo '<a href="' . esc_url(home_url('/ajouter-licencie/')) . '" class="ufsc-btn ufsc-btn-block">Nouvelle licence</a>';
                }
                ?>
            </div>
            
            <div class="ufsc-action-group">
                <h5>Documents</h5>
                <?php
                $attestation_page = ufsc_get_safe_page_url('attestations');
                if ($attestation_page['available']):
                ?>
                    <a href="<?php echo esc_url($attestation_page['url']); ?>" class="ufsc-btn ufsc-btn-outline ufsc-btn-block">
                        📄 Attestations
                    </a>
                <?php else: ?>
                    <button class="ufsc-btn ufsc-btn-disabled ufsc-btn-block" disabled>
                        📄 Attestations (non configuré)
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="ufsc-action-group">
                <h5>Affiliation requise</h5>
                <p class="ufsc-text-muted">Votre club doit être actif pour accéder aux actions.</p>
                <?php echo ufsc_generate_affiliation_button(['classes' => 'ufsc-btn-block']); ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render recent activity card with pagination
 * 
 * @param object $club Club object  
 * @return string HTML output
 */
function ufsc_render_recent_activity_card($club) {
    // Get license manager
    if (!class_exists('UFSC_Licence_Manager')) {
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'licences/class-licence-manager.php';
    }
    
    $license_manager = UFSC_Licence_Manager::get_instance();
    $recent_licenses = $license_manager->get_licences([
        'club_id' => $club->id,
        'limit' => 5,
        'orderby' => 'date_creation',
        'order' => 'DESC'
    ]);
    
    ob_start();
    ?>
    <div class="ufsc-recent-activity">
        <?php if (!empty($recent_licenses)): ?>
            <div class="ufsc-activity-list">
                <?php foreach ($recent_licenses as $license): ?>
                    <div class="ufsc-activity-item">
                        <div class="ufsc-activity-avatar">
                            <?php echo strtoupper(substr($license->prenom, 0, 1) . substr($license->nom, 0, 1)); ?>
                        </div>
                        <div class="ufsc-activity-content">
                            <div class="ufsc-activity-name">
                                <strong><?php echo esc_html($license->prenom . ' ' . $license->nom); ?></strong>
                            </div>
                            <div class="ufsc-activity-date">
                                <?php echo esc_html(date_i18n('j F Y', strtotime($license->date_creation))); ?>
                            </div>
                        </div>
                        <div class="ufsc-activity-status">
                            <?php echo ufsc_get_license_status_badge($license->statut, $license->payment_status ?? ''); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ufsc-activity-footer">
                <button class="ufsc-btn ufsc-btn-outline ufsc-btn-sm" onclick="ufscToggleAllLicenses()">
                    Voir toutes les licences
                </button>
            </div>
        <?php else: ?>
            <div class="ufsc-empty-activity">
                <div class="ufsc-empty-icon">📝</div>
                <h5>Aucune activité</h5>
                <p>Aucune licence n'a encore été enregistrée pour ce club.</p>
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
    return ob_get_clean();
}