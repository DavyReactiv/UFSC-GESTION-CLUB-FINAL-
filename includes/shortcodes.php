<?php

/**
 * Shortcodes pour la gestion front-end des clubs affiliés UFSC
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Global flag to prevent duplicate "no club" messages
global $ufsc_no_club_message_shown;
$ufsc_no_club_message_shown = false;

/**
 * Helper function to get standardized "no club" message
 * Prevents duplicate messages by using a global flag
 */
function ufsc_get_no_club_message($context = 'general') {
    global $ufsc_no_club_message_shown;
    
    // If message already shown, return empty string
    if ($ufsc_no_club_message_shown) {
        return '';
    }
    
    // Mark message as shown
    $ufsc_no_club_message_shown = true;
    
    switch ($context) {
        case 'attestation':
            return '<div class="ufsc-alert ufsc-alert-error">
                    <p>Vous n\'êtes pas associé à un club affilié.</p>
                    </div>';
        case 'dashboard':
            $affiliation_button = ufsc_generate_safe_navigation_button('affiliation', 'Créer un club', 'ufsc-btn', true);
            return '<div class="ufsc-alert ufsc-alert-error">
                    <p>Vous n\'êtes pas associé à un club. Veuillez contacter l\'administrateur ou créer un club.</p>
                    <p>' . $affiliation_button . '</p>
                    </div>';
        default:
            return '<div class="ufsc-alert ufsc-alert-error">
                    <p>Vous n\'êtes pas associé à un club.</p>
                    </div>';
    }
}

/**
 * Shortcode [ufsc_affiliation_club_form]
 * Affiche le formulaire d'affiliation club avec upload des documents
 */
function ufsc_affiliation_club_form_shortcode($atts)
{
    return ufsc_render_affiliation_club_form($atts);
}
add_shortcode('ufsc_affiliation_club_form', 'ufsc_affiliation_club_form_shortcode');

/**
 * Shortcode [ufsc_club_attestation]
 * Affiche le bouton de téléchargement de l'attestation d'affiliation
 */
function ufsc_club_attestation_shortcode($atts)
{
    return ufsc_render_club_attestation($atts);
}
add_shortcode('ufsc_club_attestation', 'ufsc_club_attestation_shortcode');

/**
 * Shortcode [ufsc_liste_clubs]
 * Affiche la liste publique des clubs affiliés
 */
function ufsc_liste_clubs_shortcode($atts)
{
    return ufsc_render_liste_clubs($atts);
}
add_shortcode('ufsc_liste_clubs', 'ufsc_liste_clubs_shortcode');

/**
 * Load additional shortcode files
 */
require_once plugin_dir_path(__FILE__) . 'frontend/shortcodes/licence-button-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'frontend/shortcodes/ajouter-licencie-shortcode.php';

// Load new frontend shortcodes (already included in main plugin file, but ensuring consistency)
// require_once plugin_dir_path(__FILE__) . 'frontend/shortcodes/new-frontend-shortcodes.php';

/**
 * ========================================
 * SHORTCODE ALIASES FOR BACKWARD COMPATIBILITY
 * ========================================
 * 
 * These aliases ensure that content using French shortcode names
 * will work with the existing English shortcode implementations.
 * This provides seamless backward compatibility.
 */

/**
 * Alias: [ufsc_licence_button] → [ufsc_bouton_licence]
 * Maps the English shortcode name to the existing French implementation
 */
function ufsc_licence_button_shortcode($atts) {
    return ufsc_bouton_licence_shortcode($atts);
}
add_shortcode('ufsc_licence_button', 'ufsc_licence_button_shortcode');

/**
 * Alias: [ufsc_club_licences] → [ufsc_club_licenses] 
 * Maps the French shortcode name to the existing English implementation
 */
function ufsc_club_licences_shortcode($atts) {
    return ufsc_club_licenses_shortcode($atts);
}
add_shortcode('ufsc_club_licences', 'ufsc_club_licences_shortcode');

/**
 * Alias: [ufsc_attestation_form] → [ufsc_club_attestation]
 * Maps the form-specific shortcode name to the existing attestation implementation
 */
function ufsc_attestation_form_shortcode($atts) {
    return ufsc_club_attestation_shortcode($atts);
}
add_shortcode('ufsc_attestation_form', 'ufsc_attestation_form_shortcode');

/**
 * Fonction de rendu pour le formulaire d'affiliation club
 *
 * @param array $atts Attributs du shortcode
 * @return string HTML du formulaire
 */
function ufsc_render_affiliation_club_form($atts = [])
{
    // Si l'utilisateur n'est pas connecté, afficher formulaire de connexion
    if (!is_user_logged_in()) {
        return '<div class="ufsc-alert ufsc-alert-error">
                <p>Vous devez être connecté pour accéder au formulaire d\'affiliation.</p>
                <p><a href="' . wp_login_url(get_permalink()) . '" class="ufsc-btn">Se connecter</a> ou 
                <a href="' . wp_registration_url() . '" class="ufsc-btn ufsc-btn-outline">Créer un compte</a></p>
                </div>';
    }

    // Vérifier si l'utilisateur a déjà un club affilié
    $user_id = get_current_user_id();
    
    // Vérifier que la fonction existe pour éviter les erreurs fatales
    if (!function_exists('ufsc_get_user_club')) {
        return '<div class="ufsc-alert ufsc-alert-error">
                <p>Erreur de configuration du plugin. Veuillez contacter l\'administrateur.</p>
                </div>';
    }
    
    $club = ufsc_get_user_club($user_id);

    if ($club && $club->statut !== 'Refusé') {
        $dashboard_button = ufsc_generate_safe_navigation_button('dashboard', 'Accéder à mon espace club', 'ufsc-btn ufsc-btn-primary', true);
        return '<div class="ufsc-alert ufsc-alert-info">
                <h4>✅ Vous avez déjà un club</h4>
                <p>Vous avez déjà un club en cours d\'affiliation ou affilié.</p>
                <p>' . $dashboard_button . '</p>
                </div>';
    }

    // Démarrer la capture de sortie
    ob_start();

    // Inclusion du formulaire club avec paramètre spécial pour affiliation
    require_once UFSC_PLUGIN_PATH . 'includes/clubs/form-club.php';

    // Appel de la fonction avec le paramètre affiliation=true
    ufsc_render_club_form(($club ? $club->id : 0), true, true);

    // Récupérer le contenu capturé
    return ob_get_clean();
}

/**
 * Fonction de rendu pour le bouton d'attestation d'affiliation
 *
 * @param array $atts Attributs du shortcode
 * @return string HTML du bouton ou message d'erreur
 */
function ufsc_render_club_attestation($atts = [])
{
    // CORRECTION: Use standardized frontend access control for attestations
    $access_check = ufsc_check_frontend_access('attestation');
    
    if (!$access_check['allowed']) {
        return $access_check['error_message'];
    }
    
    $club = $access_check['club'];

    // CORRECTION: Use standardized status checking - only active clubs can access attestations
    if (!ufsc_is_club_active($club)) {
        return '<div class="ufsc-alert ufsc-alert-warning">
                <h4>⚠️ Attestation non disponible</h4>
                <p>Votre club doit être validé par l\'administration UFSC pour accéder aux attestations.</p>
                <p>Statut actuel de votre club : <strong>' . esc_html($club->statut) . '</strong></p>
                </div>';
    }

    // Vérifier si l'attestation est disponible
    if (empty($club->doc_attestation_affiliation)) {
        return '<div class="ufsc-alert ufsc-alert-warning">
                <h4>Attestation en préparation</h4>
                <p>L\'attestation d\'affiliation n\'est pas encore disponible pour votre club.</p>
                <p>Si votre club est validé depuis plus de 24h, veuillez contacter l\'administration UFSC.</p>
                </div>';
    }

    // Traitement du téléchargement si demandé
    if (isset($_GET['download_attestation']) && $_GET['download_attestation'] === '1' && wp_verify_nonce($_GET['_wpnonce'], 'download_attestation_' . $club->id)) {
        $file_url = $club->doc_attestation_affiliation;
        
        // Si c'est une URL complète, on la convertit en chemin local
        if (filter_var($file_url, FILTER_VALIDATE_URL)) {
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
        } else {
            // Si c'est déjà un chemin local
            $file_path = $file_url;
        }
        
        if (file_exists($file_path) && is_readable($file_path)) {
            // Force download using WordPress functions
            $filename = 'attestation_affiliation_' . sanitize_file_name($club->nom) . '_' . gmdate('Y-m-d') . '.pdf';
            
            // Use WordPress built-in file serving for security
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '"');
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: private');
            
            // Use WP filesystem approach - but for download, readfile is acceptable here
            // as it's within controlled conditions and for PDF serving
            readfile($file_path);
            exit;
        } else {
            // Redirect with error message
            wp_redirect(add_query_arg('attestation_error', '1', get_permalink()));
            exit;
        }
    }

    // Afficher message d'erreur si nécessaire
    if (isset($_GET['attestation_error'])) {
        return '<div class="ufsc-alert ufsc-alert-error">
                <p>Impossible de télécharger l\'attestation. Le fichier n\'est pas accessible.</p>
                </div>';
    }

    // Afficher le bouton de téléchargement
    $download_url = add_query_arg([
        'download_attestation' => '1',
        '_wpnonce' => wp_create_nonce('download_attestation_' . $club->id)
    ], get_permalink());
    
    return '<div class="ufsc-attestation-download">
            <h3>Attestation d\'affiliation UFSC</h3>
            <p>Votre attestation d\'affiliation est disponible au téléchargement.</p>
            <p><a href="' . esc_url($download_url) . '" class="ufsc-btn ufsc-btn-primary" target="_blank">
                📄 Télécharger l\'attestation d\'affiliation
            </a></p>
            </div>';
}

/**
 * Fonction de rendu pour la liste publique des clubs affiliés
 *
 * @param array $atts Attributs du shortcode
 * @return string HTML de la liste des clubs
 */
function ufsc_render_liste_clubs($atts = [])
{
    // Récupérer tous les clubs affiliés
    $club_manager = UFSC_Club_Manager::get_instance();
    $all_clubs = $club_manager->get_clubs();
    
    // Filtrer pour ne garder que les clubs validés/actifs
    $active_clubs = array_filter($all_clubs, function($club) {
        return in_array($club->statut, ['Actif', 'Validé', 'Affilié']);
    });

    if (empty($active_clubs)) {
        return '<div class="ufsc-alert ufsc-alert-info">
                <p>Aucun club affilié n\'est actuellement répertorié.</p>
                </div>';
    }

    // Construire la liste
    ob_start();
    ?>
    <div class="ufsc-liste-clubs">
        <h3>Clubs affiliés UFSC</h3>
        <div class="ufsc-clubs-grid">
            <?php foreach ($active_clubs as $club): ?>
            <div class="ufsc-club-card">
                <h4><?php echo esc_html($club->nom); ?></h4>
                <p><strong>Commune :</strong> <?php echo esc_html($club->ville); ?></p>
                
                <?php if (!empty($club->num_affiliation)): ?>
                <p><strong>N° d'affiliation :</strong> <?php echo esc_html($club->num_affiliation); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($club->region)): ?>
                <p><strong>Région :</strong> <?php echo esc_html($club->region); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($club->url_site)): ?>
                <p><a href="<?php echo esc_url($club->url_site); ?>" target="_blank" rel="noopener">🌐 Site web</a></p>
                <?php endif; ?>
                
                <?php if (!empty($club->url_facebook)): ?>
                <p><a href="<?php echo esc_url($club->url_facebook); ?>" target="_blank" rel="noopener">📘 Facebook</a></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}