<?php

/**
 * Club Dashboard - Main Frontend Controller
 * 
 * Secure club dashboard interface with modular section management.
 * This file serves as the main controller for the club frontend space.
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
 * Class UFSC_Club_Dashboard
 * 
 * Main class for handling the club dashboard frontend interface.
 * Provides secure access control and modular section management.
 */
class UFSC_Club_Dashboard
{
    /**
     * Current user ID
     *
     * @var int
     */
    private $user_id;

    /**
     * Current club object
     *
     * @var object|null
     */
    private $club;

    /**
     * Current dashboard section
     *
     * @var string
     */
    private $current_section;

    /**
     * Available dashboard sections
     *
     * @var array
     */
    private $sections;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->init_sections();
        $this->load_required_files();
    }

    /**
     * Initialize available dashboard sections
     *
     * @return void
     */
    private function init_sections()
    {
        $this->sections = [
            'home' => [
                'label' => '<i class="dashicons dashicons-dashboard"></i> Accueil',
                'file' => 'club-infos.php',
                'capability' => 'read'
            ],
            'licences' => [
                'label' => '<i class="dashicons dashicons-id"></i> Licences',
                'file' => 'licences.php',
                'capability' => 'read'
            ],
            'documents' => [
                'label' => '<i class="dashicons dashicons-media-document"></i> Documents',
                'file' => 'documents.php',
                'capability' => 'read'
            ],
            'attestations' => [
                'label' => '<i class="dashicons dashicons-awards"></i> Attestations',
                'file' => 'attestations.php',
                'capability' => 'read'
            ],
            'statistiques' => [
                'label' => '<i class="dashicons dashicons-chart-bar"></i> Statistiques',
                'file' => 'statistiques.php',
                'capability' => 'read'
            ],
            'paiements' => [
                'label' => '<i class="dashicons dashicons-money-alt"></i> Paiements',
                'file' => 'paiements.php',
                'capability' => 'read'
            ],
            'notifications' => [
                'label' => '<i class="dashicons dashicons-bell"></i> Notifications',
                'file' => 'notifications.php',
                'capability' => 'read'
            ],
            'profile' => [
                'label' => '<i class="dashicons dashicons-businessperson"></i> Profil du club',
                'file' => 'club-infos.php',
                'capability' => 'read'
            ]
        ];
    }

    /**
     * Load required modular files
     *
     * @return void
     */
    private function load_required_files()
    {
        $club_path = UFSC_PLUGIN_PATH . 'includes/frontend/club/';
        
        $files = [
            'club-infos.php',
            'documents.php',
            'licences.php',
            'statistiques.php',
            'attestations.php',
            'paiements.php',
            'notifications.php'
        ];

        foreach ($files as $file) {
            $file_path = $club_path . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }

    /**
     * Security check: Verify user access to club dashboard
     *
     * @return bool|WP_Error True if access granted, WP_Error otherwise
     */
    private function check_access()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return new WP_Error(
                'not_logged_in',
                'Vous devez être connecté pour accéder à votre espace club.',
                ['login_url' => wp_login_url(get_permalink())]
            );
        }

        $this->user_id = get_current_user_id();

        // Check user capabilities
        if (!current_user_can('read')) {
            return new WP_Error(
                'insufficient_permissions',
                'Vous n\'avez pas les permissions nécessaires pour accéder à cette page.'
            );
        }

        // Get user's club
        $this->club = ufsc_get_user_club($this->user_id);

        if (!$this->club) {
            return new WP_Error(
                'no_club_associated',
                'Vous n\'êtes pas associé à un club.',
                ['form_url' => esc_url(get_permalink(get_option('ufsc_club_form_page_id')))]
            );
        }

        // Verify club ownership - ensure user can only access their own club data
        if (!$this->verify_club_ownership()) {
            return new WP_Error(
                'club_access_denied',
                'Accès refusé : vous ne pouvez accéder qu\'aux données de votre propre club.'
            );
        }

        return true;
    }

    /**
     * Verify that the current user has access to the club data
     *
     * @return bool True if user owns/manages the club
     */
    private function verify_club_ownership()
    {
        if (!$this->club || !$this->user_id) {
            return false;
        }

        // CORRECTION: Use responsable_id instead of user_id for proper club-user association
        return (int) $this->club->responsable_id === $this->user_id;
    }

    /**
     * Check if the current user has a failed affiliation payment
     *
     * @return bool True if a failed affiliation order exists
     */
    private function has_failed_affiliation_payment()
    {
        if (!function_exists('wc_get_orders')) {
            return false;
        }

        $orders = wc_get_orders([
            'limit' => 5,
            'status' => ['failed', 'cancelled'],
            'customer_id' => $this->user_id
        ]);

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if ((int) $item->get_product_id() === ufsc_get_affiliation_product_id()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Render the complete dashboard
     *
     * @param array $atts Shortcode attributes
     * @return string Dashboard HTML output
     */
    public function render($atts = [])
    {
        // Check access and security
        $access_check = $this->check_access();
        
        if (is_wp_error($access_check)) {
            return $this->render_error($access_check);
        }

        // Display affiliation button if club is inactive or has failed payment
        $affiliation_prompt = '';
        if ($this->club && (!ufsc_is_club_active($this->club) || $this->has_failed_affiliation_payment())) {
            $affiliation_prompt = '<div class="ufsc-affiliation-action">' .
                ufsc_generate_affiliation_button(['product_id' => 4823]) .
                '</div>';
        }

        // Determine current section
        $this->current_section = isset($_GET['section']) ?
            sanitize_text_field($_GET['section']) : 'home';

        // Validate section exists
        if (!isset($this->sections[$this->current_section])) {
            $this->current_section = 'home';
        }

        // Check section-specific capabilities
        $section_config = $this->sections[$this->current_section];
        if (!current_user_can($section_config['capability'])) {
            $this->current_section = 'home';
        }

        // Build dashboard output
        $output = $affiliation_prompt;
        $output .= '<div class="ufsc-club-dashboard ufsc-container">';
        
        // Header
        $output .= $this->render_header();
        
        // Navigation
        $output .= $this->render_navigation();
        
        // Main content
        $output .= '<div class="ufsc-dashboard-content">';
        $output .= $this->render_section_content();
        $output .= '</div>';
        
        // Footer scripts and styles
        $output .= $this->render_footer_assets();
        
        $output .= '</div>';

        return $output;
    }

    /**
     * Render error messages for access issues
     *
     * @param WP_Error $error Error object
     * @return string Error HTML
     */
    private function render_error($error)
    {
        $error_data = $error->get_error_data();
        $output = '<div class="ufsc-alert ufsc-alert-error">';
        $output .= '<p>' . esc_html($error->get_error_message()) . '</p>';

        // Add action buttons based on error type
        switch ($error->get_error_code()) {
            case 'not_logged_in':
                $output .= ufsc_render_login_prompt($error_data['login_url']);
                break;
                
            case 'no_club_associated':
                if (isset($error_data['form_url'])) {
                    $output .= '<p><a href="' . esc_url($error_data['form_url']) . '" class="ufsc-btn">Créer un club</a></p>';
                }
                break;
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Render dashboard header with club info and status
     *
     * @return string Header HTML
     */
    private function render_header()
    {
        $output = '<div class="ufsc-dashboard-header">';
        $output .= '<div class="ufsc-dashboard-title">';
        
        // Club name and status badge
        $status_badge = $this->get_status_badge($this->club->statut);
        $output .= '<h1>' . esc_html($this->club->nom) . ' ' . $status_badge . '</h1>';

        // Affiliation number if available
        if (!empty($this->club->num_affiliation)) {
            $output .= '<h2>N° Affiliation: ' . esc_html($this->club->num_affiliation) . '</h2>';
        }

        $output .= '</div>';

        // Quick action buttons
        // CORRECTION: Use standardized status checking for quick actions
        if (ufsc_is_club_active($this->club)) {
            $output .= '<div class="ufsc-dashboard-actions">';
            $output .= '<a href="' . esc_url(add_query_arg(['section' => 'licences', 'action' => 'new'], get_permalink())) . '" class="ufsc-btn ufsc-btn-red">';
            $output .= '<i class="dashicons dashicons-plus-alt2"></i> Nouvelle licence';
            $output .= '</a>';
            $output .= '</div>';
        }

        $output .= '</div>';

        // Status alerts
        $output .= $this->render_status_alerts();

        return $output;
    }

    /**
     * Get status badge HTML for club status
     *
     * @param string $status Club status
     * @return string Badge HTML
     */
    private function get_status_badge($status)
    {
        switch ($status) {
            case 'Actif':
                return '<span class="ufsc-badge ufsc-badge-active">Validé</span>';
            case 'En attente de validation':
            case 'En cours de validation':
            case 'En cours de création':
                return '<span class="ufsc-badge ufsc-badge-pending">En attente</span>';
            case 'Refusé':
                return '<span class="ufsc-badge ufsc-badge-error">Refusé</span>';
            default:
                return '<span class="ufsc-badge ufsc-badge-inactive">Inactif</span>';
        }
    }

    /**
     * Render status alerts based on club status
     *
     * @return string Alerts HTML
     */
    private function render_status_alerts()
    {
        $output = '';
        
        switch ($this->club->statut) {
            case 'En cours de création':
            case 'Refusé':
                $output .= '<div class="ufsc-alert ufsc-alert-warning">';
                $output .= '<h4>Votre club n\'est pas encore affilié</h4>';
                $output .= '<p>Pour accéder à toutes les fonctionnalités, vous devez procéder à l\'affiliation de votre club.</p>';
                $output .= '<p><a href="' . esc_url(get_permalink(get_option('ufsc_affiliation_page_id'))) . '" class="ufsc-btn ufsc-btn-red">Affilier mon club</a></p>';
                $output .= '</div>';
                break;
                
            case 'En attente de validation':
            case 'En cours de validation':
                $output .= '<div class="ufsc-alert ufsc-alert-info">';
                $output .= '<h4>Votre demande d\'affiliation est en cours de traitement</h4>';
                $output .= '<p>Nous avons bien reçu votre demande d\'affiliation et votre paiement. Notre équipe est en train d\'étudier votre dossier.</p>';
                $output .= '<p>Vous recevrez une notification par email dès que votre affiliation sera validée.</p>';
                $output .= '</div>';
                break;
        }
        
        return $output;
    }

    /**
     * Render navigation menu
     *
     * @return string Navigation HTML
     */
    private function render_navigation()
    {
        $output = '<div class="ufsc-dashboard-nav">';
        $output .= '<ul>';

        foreach ($this->sections as $key => $section) {
            // Check if user has capability for this section
            if (!current_user_can($section['capability'])) {
                continue;
            }

            $active_class = ($this->current_section === $key) ? ' class="active"' : '';
            $url = add_query_arg(['section' => $key], get_permalink());
            
            $output .= '<li' . $active_class . '>';
            $output .= '<a href="' . esc_url($url) . '">' . $section['label'] . '</a>';
            $output .= '</li>';
        }

        $output .= '</ul>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Render content for current section
     *
     * @return string Section content HTML
     */
    private function render_section_content()
    {
        // Route to appropriate section renderer
        switch ($this->current_section) {
            case 'home':
                return function_exists('ufsc_club_render_home') ? 
                    ufsc_club_render_home($this->club) : 
                    $this->render_home_section();
            case 'licences':
                return function_exists('ufsc_club_render_licences') ? 
                    ufsc_club_render_licences($this->club) : 
                    '<p>Section licences en cours de développement.</p>';
            case 'documents':
                return function_exists('ufsc_club_render_documents') ? 
                    ufsc_club_render_documents($this->club) : 
                    '<p>Section documents en cours de développement.</p>';
            case 'attestations':
                return function_exists('ufsc_club_render_attestations') ? 
                    ufsc_club_render_attestations($this->club) : 
                    '<p>Section attestations en cours de développement.</p>';
            case 'statistiques':
                return function_exists('ufsc_club_render_statistiques') ? 
                    ufsc_club_render_statistiques($this->club) : 
                    '<p>Section statistiques en cours de développement.</p>';
            case 'paiements':
                return function_exists('ufsc_club_render_paiements') ? 
                    ufsc_club_render_paiements($this->club) : 
                    '<p>Section paiements en cours de développement.</p>';
            case 'notifications':
                return function_exists('ufsc_club_render_notifications') ? 
                    ufsc_club_render_notifications($this->club) : 
                    '<p>Section notifications en cours de développement.</p>';
            case 'profile':
                return function_exists('ufsc_club_render_profile') ? 
                    ufsc_club_render_profile($this->club) : 
                    '<p>Section profil en cours de développement.</p>';
            default:
                return function_exists('ufsc_club_render_home') ? 
                    ufsc_club_render_home($this->club) : 
                    $this->render_home_section();
        }
    }

    /**
     * Render home section with dashboard overview
     *
     * @return string Home section HTML
     */
    private function render_home_section()
    {
        // Include the dashboard cards partial
        require_once UFSC_PLUGIN_PATH . 'includes/frontend/club/partials/dashboard-cards.php';
        
        // Return enhanced dashboard cards
        return ufsc_render_dashboard_cards($this->club);
    }

    /**
     * Render footer assets (CSS and JS)
     *
     * @return string Assets HTML
     */
    private function render_footer_assets()
    {
        // Include dashboard-specific styles
        $output = '<style>';
        $output .= $this->get_dashboard_css();
        $output .= '</style>';

        // Include dashboard-specific JavaScript
        $output .= '<script type="text/javascript">';
        $output .= $this->get_dashboard_js();
        $output .= '</script>';

        return $output;
    }

    /**
     * Get dashboard CSS styles
     *
     * @return string CSS content
     */
    private function get_dashboard_css()
    {
        return '
        /* UFSC Club Dashboard Styles */
        .ufsc-club-dashboard {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e8e8e8;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-title h1 {
            font-size: 28px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #2c3e50;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-title h2 {
            font-size: 16px;
            margin: 5px 0 0;
            color: #666;
            font-weight: normal;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-nav {
            background: #2c3e50;
            border-radius: 8px;
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-nav li {
            margin: 0;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-nav li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            border-right: 1px solid rgba(255,255,255,0.1);
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-nav li a .dashicons {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-nav li.active {
            background: #e74c3c;
        }
        
        .ufsc-club-dashboard .ufsc-dashboard-nav li:hover:not(.active) {
            background: rgba(255, 255, 255, 0.1);
        }
        
        /* Badges */
        .ufsc-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .ufsc-badge-active {
            background: #27ae60;
            color: white;
        }
        
        .ufsc-badge-pending {
            background: #f39c12;
            color: white;
        }
        
        .ufsc-badge-error {
            background: #e74c3c;
            color: white;
        }
        
        .ufsc-badge-inactive {
            background: #95a5a6;
            color: white;
        }
        
        /* Cards */
        .ufsc-card {
            background: white;
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .ufsc-card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e8e8e8;
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .ufsc-card-body {
            padding: 20px;
        }
        
        /* Alerts */
        .ufsc-alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .ufsc-alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #27ae60;
        }
        
        .ufsc-alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #e74c3c;
        }
        
        .ufsc-alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #f39c12;
        }
        
        .ufsc-alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #3498db;
        }
        
        /* Buttons */
        .ufsc-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
            line-height: 1.4;
        }
        
        .ufsc-btn:hover {
            background: #2980b9;
            color: white;
            text-decoration: none;
        }
        
        .ufsc-btn-red {
            background: #e74c3c;
        }
        
        .ufsc-btn-red:hover {
            background: #c0392b;
        }
        
        .ufsc-btn-outline {
            background: transparent;
            color: #3498db;
            border: 2px solid #3498db;
        }
        
        .ufsc-btn-outline:hover {
            background: #3498db;
            color: white;
        }
        
        .ufsc-btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Statistics */
        .ufsc-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .ufsc-stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e8e8e8;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .ufsc-stat-icon {
            font-size: 24px;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .ufsc-stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .ufsc-stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Forms */
        .ufsc-form-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            margin-bottom: 20px;
            align-items: start;
        }
        
        .ufsc-form-row label {
            font-weight: 600;
            color: #2c3e50;
            padding-top: 8px;
        }
        
        .ufsc-form-row input,
        .ufsc-form-row select,
        .ufsc-form-row textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .ufsc-form-required {
            color: #e74c3c;
        }
        
        /* Tables */
        .ufsc-table-responsive {
            overflow-x: auto;
        }
        
        .ufsc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .ufsc-table th,
        .ufsc-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e8e8e8;
        }
        
        .ufsc-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .ufsc-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Action buttons */
        .ufsc-action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .ufsc-action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        /* Section titles */
        .ufsc-section-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .ufsc-club-dashboard .ufsc-dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .ufsc-club-dashboard .ufsc-dashboard-nav ul {
                flex-direction: column;
            }
            
            .ufsc-club-dashboard .ufsc-dashboard-nav li a {
                border-right: none;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            
            .ufsc-dashboard-stats {
                grid-template-columns: 1fr;
            }
            
            .ufsc-form-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }
            
            .ufsc-table-responsive {
                font-size: 14px;
            }
            
            .ufsc-action-bar {
                flex-direction: column;
                gap: 10px;
                align-items: stretch;
            }
        }
        
        @media (max-width: 480px) {
            .ufsc-club-dashboard {
                padding: 10px;
            }
            
            .ufsc-card-body {
                padding: 15px;
            }
            
            .ufsc-dashboard-stats {
                gap: 15px;
            }
            
            .ufsc-stat-card {
                padding: 15px;
            }
        }
        ';
    }

    /**
     * Get dashboard JavaScript
     *
     * @return string JavaScript content
     */
    private function get_dashboard_js()
    {
        return '
        jQuery(document).ready(function($) {
            console.log("UFSC Club Dashboard loaded");
            
            // Add any dashboard-specific JavaScript here
        });
        ';
    }
}

/**
 * Shortcode function for [espace_club]
 *
 * @param array $atts Shortcode attributes
 * @return string Dashboard HTML
 */
function ufsc_espace_club_shortcode($atts)
{
    $dashboard = new UFSC_Club_Dashboard();
    return $dashboard->render($atts);
}

// Register the new shortcode
add_shortcode('espace_club', 'ufsc_espace_club_shortcode');