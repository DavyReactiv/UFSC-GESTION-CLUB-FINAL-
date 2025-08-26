<?php
/**
 * Dashboard Overview Shortcode
 * 
 * Provides a comprehensive dashboard overview displaying:
 * - Club information and affiliation status
 * - Document statuses with badges and links
 * - Licence counters and detailed table
 * - Draft licences with management actions
 *
 * @package UFSC_Gestion_Club
 * @subpackage Frontend\Dashboard
 * @since 1.3.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render dashboard overview shortcode
 * 
 * Usage: [ufsc_dashboard_overview]
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function ufsc_dashboard_overview_shortcode($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'show_club_info' => 'yes',
        'show_documents' => 'yes',
        'show_licences' => 'yes',
        'show_drafts' => 'yes',
        'licence_limit' => '10'
    ], $atts, 'ufsc_dashboard_overview');
    
    // Check access
    $access_check = ufsc_check_frontend_access('dashboard');
    if (!$access_check['allowed']) {
        return $access_check['error_message'];
    }
    
    $club = $access_check['club'];
    $user_id = get_current_user_id();
    
    // Enqueue styles and scripts
    wp_enqueue_style('ufsc-licence-styles');
    wp_enqueue_script('ufsc-licence-multi');
    
    ob_start();
    ?>
    
    <div class="ufsc-dashboard-overview">
        
        <?php if ($atts['show_club_info'] === 'yes'): ?>
        <!-- Club Information Section -->
        <div class="ufsc-overview-section">
            <div class="ufsc-overview-section-header">
                <h3 class="ufsc-overview-section-title">
                    <i class="dashicons dashicons-building"></i> Informations du Club
                </h3>
            </div>
            <div class="ufsc-overview-section-content">
                <?php echo ufsc_render_club_info_section($club); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_documents'] === 'yes'): ?>
        <!-- Documents Section -->
        <div class="ufsc-overview-section">
            <div class="ufsc-overview-section-header">
                <h3 class="ufsc-overview-section-title">
                    <i class="dashicons dashicons-media-document"></i> Documents du Club
                </h3>
            </div>
            <div class="ufsc-overview-section-content">
                <?php echo ufsc_render_documents_section($club); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_licences'] === 'yes'): ?>
        <!-- Licences Section -->
        <div class="ufsc-overview-section">
            <div class="ufsc-overview-section-header">
                <h3 class="ufsc-overview-section-title">
                    <i class="dashicons dashicons-id"></i> Licences du Club
                </h3>
            </div>
            <div class="ufsc-overview-section-content">
                <?php echo ufsc_render_licences_section($club, (int) $atts['licence_limit']); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_drafts'] === 'yes'): ?>
        <!-- Draft Licences Section -->
        <div class="ufsc-overview-section ufsc-drafts-section">
            <div class="ufsc-overview-section-header">
                <h3 class="ufsc-overview-section-title">
                    <i class="dashicons dashicons-edit"></i> Licences en Attente (Brouillons)
                </h3>
                <span class="ufsc-draft-count">0</span>
            </div>
            <div class="ufsc-overview-section-content">
                <?php echo ufsc_render_drafts_section($club, $user_id); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
        // Check for pending affiliation data
        $pending_affiliation = get_user_meta($user_id, 'ufsc_pending_affiliation_data', true);
        if (!empty($pending_affiliation) && is_array($pending_affiliation)):
        ?>
        <!-- Pending Affiliation Banner -->
        <div class="ufsc-overview-section">
            <div class="ufsc-overview-section-content">
                <div class="ufsc-feedback-message warning">
                    <h4><i class="dashicons dashicons-clock"></i> Affiliation en cours de paiement</h4>
                    <p>Votre demande d'affiliation pour le club <strong><?php echo esc_html($pending_affiliation['nom_club'] ?? 'N/A'); ?></strong> est en attente de paiement.</p>
                    <p>Une fois le paiement confirmé, votre club sera activé et vous pourrez créer des licences.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <?php
    return ob_get_clean();
}
add_shortcode('ufsc_dashboard_overview', 'ufsc_dashboard_overview_shortcode');

/**
 * Render club information section
 * 
 * @param object $club Club object
 * @return string HTML output
 */
function ufsc_render_club_info_section($club) {
    ob_start();
    ?>
    
    <div class="ufsc-club-info-grid">
        <div class="ufsc-club-info-item">
            <span class="ufsc-club-info-label">Nom du club:</span>
            <span class="ufsc-club-info-value"><?php echo esc_html($club->nom ?? 'N/A'); ?></span>
        </div>
        
        <div class="ufsc-club-info-item">
            <span class="ufsc-club-info-label">Ville:</span>
            <span class="ufsc-club-info-value"><?php echo esc_html($club->ville ?? 'N/A'); ?></span>
        </div>
        
        <div class="ufsc-club-info-item">
            <span class="ufsc-club-info-label">Région:</span>
            <span class="ufsc-club-info-value"><?php echo esc_html($club->region ?? 'N/A'); ?></span>
        </div>
        
        <div class="ufsc-club-info-item">
            <span class="ufsc-club-info-label">Email:</span>
            <span class="ufsc-club-info-value">
                <?php if (!empty($club->email)): ?>
                    <a href="mailto:<?php echo esc_attr($club->email); ?>"><?php echo esc_html($club->email); ?></a>
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </span>
        </div>
        
        <div class="ufsc-club-info-item">
            <span class="ufsc-club-info-label">Téléphone:</span>
            <span class="ufsc-club-info-value">
                <?php if (!empty($club->telephone)): ?>
                    <a href="tel:<?php echo esc_attr($club->telephone); ?>"><?php echo esc_html($club->telephone); ?></a>
                <?php else: ?>
                    N/A
                <?php endif; ?>
            </span>
        </div>
        
        <div class="ufsc-club-info-item">
            <span class="ufsc-club-info-label">Statut d'affiliation:</span>
            <span class="ufsc-club-info-value">
                <?php 
                $status = ufsc_get_club_status_display($club);
                echo '<span class="ufsc-document-status ' . esc_attr($status['class']) . '">' . esc_html($status['text']) . '</span>';
                ?>
            </span>
        </div>
        
        <?php if (!empty($club->num_affiliation)): ?>
        <div class="ufsc-club-info-item">
            <span class="ufsc-club-info-label">Numéro d'affiliation:</span>
            <span class="ufsc-club-info-value"><?php echo esc_html($club->num_affiliation); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($club->date_affiliation)): ?>
        <div class="ufsc-club-info-item">
            <span class="ufsc-club-info-label">Date d'affiliation:</span>
            <span class="ufsc-club-info-value"><?php echo esc_html(date('d/m/Y', strtotime($club->date_affiliation))); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Render documents section
 * 
 * @param object $club Club object
 * @return string HTML output
 */
function ufsc_render_documents_section($club) {
    $documents = [
        'statuts' => 'Statuts',
        'recepisse' => 'Récépissé',
        'cer' => 'Certificat CER',
        'assurance' => 'Assurance',
        'rib' => 'RIB'
    ];
    
    ob_start();
    ?>
    
    <div class="ufsc-documents-grid">
        <?php foreach ($documents as $key => $label): ?>
        <div class="ufsc-document-item">
            <span class="ufsc-document-name"><?php echo esc_html($label); ?></span>
            <?php
            $document_status = ufsc_get_document_status($club, $key);
            ?>
            <span class="ufsc-document-status <?php echo esc_attr($document_status['class']); ?>">
                <?php echo esc_html($document_status['text']); ?>
                <?php if ($document_status['has_file'] && !empty($document_status['url'])): ?>
                    <a href="<?php echo esc_url($document_status['url']); ?>" target="_blank" title="Voir le document">
                        <i class="dashicons dashicons-external"></i>
                    </a>
                <?php endif; ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php
    return ob_get_clean();
}

/**
 * Render licences section
 * 
 * @param object $club Club object
 * @param int $limit Number of licences to display
 * @return string HTML output
 */
function ufsc_render_licences_section($club, $limit = 10) {
    $club_manager = \UFSC\Clubs\ClubManager::get_instance();
    $licences = $club_manager->get_licences_by_club($club->id);
    
    // Calculate statistics
    $stats = ufsc_calculate_licence_stats($licences, $club);
    
    ob_start();
    ?>
    
    <!-- Licence Counters -->
    <div class="ufsc-licence-counters">
        <div class="ufsc-counter-card">
            <div class="ufsc-counter-value"><?php echo esc_html($stats['total']); ?></div>
            <div class="ufsc-counter-label">Total</div>
        </div>
        
        <div class="ufsc-counter-card success">
            <div class="ufsc-counter-value"><?php echo esc_html($stats['active']); ?></div>
            <div class="ufsc-counter-label">Actives</div>
        </div>
        
        <div class="ufsc-counter-card warning">
            <div class="ufsc-counter-value"><?php echo esc_html($stats['pending']); ?></div>
            <div class="ufsc-counter-label">En attente</div>
        </div>
        
        <div class="ufsc-counter-card danger">
            <div class="ufsc-counter-value"><?php echo esc_html($stats['expired']); ?></div>
            <div class="ufsc-counter-label">Expirées</div>
        </div>
    </div>
    
    <?php if (!empty($licences)): ?>
    <!-- Licences Table -->
    <table class="ufsc-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Date de naissance</th>
                <th>Statut</th>
                <th>Date de création</th>
                <th>Date d'expiration</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $displayed = 0;
            foreach ($licences as $licence): 
                if ($displayed >= $limit) break;
                $displayed++;
            ?>
            <tr>
                <td><?php echo esc_html($licence->nom ?? 'N/A'); ?></td>
                <td><?php echo esc_html($licence->prenom ?? 'N/A'); ?></td>
                <td>
                    <?php 
                    if (!empty($licence->date_naissance)) {
                        echo esc_html(date('d/m/Y', strtotime($licence->date_naissance)));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
                <td>
                    <?php 
                    $status = ufsc_get_licence_status_display($licence);
                    echo '<span class="ufsc-document-status ' . esc_attr($status['class']) . '">' . esc_html($status['text']) . '</span>';
                    ?>
                </td>
                <td>
                    <?php 
                    if (!empty($licence->date_creation)) {
                        echo esc_html(date('d/m/Y', strtotime($licence->date_creation)));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
                <td>
                    <?php 
                    if (!empty($licence->date_expiration)) {
                        echo esc_html(date('d/m/Y', strtotime($licence->date_expiration)));
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (count($licences) > $limit): ?>
    <p class="ufsc-table-note">
        <em>Affichage des <?php echo $limit; ?> premières licences sur <?php echo count($licences); ?> au total.</em>
    </p>
    <?php endif; ?>
    
    <?php else: ?>
    <p>Aucune licence trouvée pour ce club.</p>
    <?php endif; ?>
    
    <?php
    return ob_get_clean();
}

/**
 * Render drafts section
 * 
 * @param object $club Club object
 * @param int $user_id User ID
 * @return string HTML output
 */
function ufsc_render_drafts_section($club, $user_id) {
    $drafts = ufsc_get_licence_drafts($club->id, $user_id);
    
    ob_start();
    ?>
    
    <?php if (!empty($drafts)): ?>
    <div class="ufsc-drafts-list">
        <?php foreach ($drafts as $draft_id => $draft): ?>
        <div class="ufsc-draft-item" data-draft-id="<?php echo esc_attr($draft_id); ?>">
            <div class="ufsc-draft-info">
                <div class="ufsc-draft-name">
                    <?php echo esc_html($draft['prenom'] ?? 'N/A'); ?> <?php echo esc_html($draft['nom'] ?? 'N/A'); ?>
                </div>
                <div class="ufsc-draft-meta">
                    <?php if (!empty($draft['date_naissance'])): ?>
                        Né(e) le <?php echo esc_html(date('d/m/Y', strtotime($draft['date_naissance']))); ?>
                    <?php endif; ?>
                    <?php if (!empty($draft['created_at'])): ?>
                        • Créé le <?php echo esc_html(date('d/m/Y H:i', strtotime($draft['created_at']))); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="ufsc-draft-actions">
                <button type="button" class="ufsc-btn ufsc-btn-primary ufsc-add-draft-to-cart" data-draft-id="<?php echo esc_attr($draft_id); ?>">
                    <i class="dashicons dashicons-cart"></i> Ajouter au panier
                </button>
                <button type="button" class="ufsc-btn ufsc-btn-secondary ufsc-delete-draft" data-draft-id="<?php echo esc_attr($draft_id); ?>">
                    <i class="dashicons dashicons-trash"></i> Supprimer
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="ufsc-drafts-global-actions">
            <button type="button" class="ufsc-btn ufsc-btn-success ufsc-add-all-drafts">
                <i class="dashicons dashicons-cart"></i> Ajouter tous les brouillons au panier
            </button>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update draft count
        if (typeof window.UFSCMultiLicence !== 'undefined') {
            window.UFSCMultiLicence.updateDraftCount(<?php echo count($drafts); ?>);
        } else {
            document.querySelector('.ufsc-draft-count').textContent = '<?php echo count($drafts); ?>';
        }
    });
    </script>
    
    <?php else: ?>
    <p>Aucun brouillon de licence en attente.</p>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hide drafts section if no drafts
        var draftsSection = document.querySelector('.ufsc-drafts-section');
        if (draftsSection) {
            draftsSection.style.display = 'none';
        }
    });
    </script>
    <?php endif; ?>
    
    <?php
    return ob_get_clean();
}

/**
 * Get document status for display
 * 
 * @param object $club Club object
 * @param string $document_key Document key
 * @return array Status information
 */
function ufsc_get_document_status($club, $document_key) {
    $document_url = '';
    $has_file = false;
    
    // Check if document exists in club data
    if (isset($club->{$document_key}) && !empty($club->{$document_key})) {
        $document_url = $club->{$document_key};
        $has_file = true;
    }
    
    // If not found, check user meta (for uploaded documents)
    if (!$has_file) {
        $user_id = get_current_user_id();
        $user_documents = get_user_meta($user_id, 'ufsc_club_documents', true);
        if (is_array($user_documents) && isset($user_documents[$document_key])) {
            $document_url = $user_documents[$document_key]['url'] ?? '';
            $has_file = !empty($document_url);
        }
    }
    
    return [
        'has_file' => $has_file,
        'url' => $document_url,
        'text' => $has_file ? 'Présent' : 'Manquant',
        'class' => $has_file ? 'present' : 'missing'
    ];
}

/**
 * Get club status for display
 * 
 * @param object $club Club object
 * @return array Status information
 */
function ufsc_get_club_status_display($club) {
    $status = $club->statut ?? 'En attente';
    
    switch (strtolower($status)) {
        case 'actif':
        case 'active':
        case 'validé':
        case 'valide':
            return ['text' => 'Actif', 'class' => 'present'];
        case 'en attente':
        case 'pending':
            return ['text' => 'En attente', 'class' => 'missing'];
        case 'suspendu':
        case 'suspended':
            return ['text' => 'Suspendu', 'class' => 'missing'];
        default:
            return ['text' => ucfirst($status), 'class' => 'missing'];
    }
}

/**
 * Get licence status for display
 * 
 * @param object $licence Licence object
 * @return array Status information
 */
function ufsc_get_licence_status_display($licence) {
    $status = $licence->statut ?? 'En attente';
    
    // Check if licence is expired
    if (!empty($licence->date_expiration)) {
        $expiration_date = strtotime($licence->date_expiration);
        if ($expiration_date < time()) {
            return ['text' => 'Expirée', 'class' => 'missing'];
        }
    }
    
    switch (strtolower($status)) {
        case 'actif':
        case 'active':
        case 'validé':
        case 'valide':
            return ['text' => 'Active', 'class' => 'present'];
        case 'en attente':
        case 'pending':
            return ['text' => 'En attente', 'class' => 'missing'];
        case 'suspendu':
        case 'suspended':
            return ['text' => 'Suspendue', 'class' => 'missing'];
        default:
            return ['text' => ucfirst($status), 'class' => 'missing'];
    }
}

/**
 * Calculate licence statistics
 * 
 * @param array $licences Array of licence objects
 * @param object $club Club object
 * @return array Statistics
 */
function ufsc_calculate_licence_stats($licences, $club) {
    $stats = [
        'total' => count($licences),
        'active' => 0,
        'pending' => 0,
        'expired' => 0,
        'expiring' => 0
    ];
    
    $now = time();
    $thirty_days = 30 * 24 * 60 * 60;
    
    foreach ($licences as $licence) {
        $status = strtolower($licence->statut ?? 'en attente');
        
        // Check expiration
        $is_expired = false;
        $is_expiring = false;
        
        if (!empty($licence->date_expiration)) {
            $expiration_time = strtotime($licence->date_expiration);
            if ($expiration_time < $now) {
                $is_expired = true;
            } elseif ($expiration_time < ($now + $thirty_days)) {
                $is_expiring = true;
            }
        }
        
        if ($is_expired) {
            $stats['expired']++;
        } elseif ($status === 'actif' || $status === 'active' || $status === 'validé' || $status === 'valide') {
            $stats['active']++;
            if ($is_expiring) {
                $stats['expiring']++;
            }
        } else {
            $stats['pending']++;
        }
    }
    
    return $stats;
}