<?php

/**
 * Document Manager Class
 *
 * Handles secure document access and management for UFSC clubs
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Document Manager Class
 */
class UFSC_Document_Manager
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return UFSC_Document_Manager
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('init', array($this, 'handle_document_download'));
        // Note: wp_ajax_ufsc_validate_club hook is registered in main plugin file for better maintainability
        add_action('wp_ajax_nopriv_ufsc_download_document', array($this, 'handle_secure_download'));
        add_action('wp_ajax_ufsc_download_document', array($this, 'handle_secure_download'));
    }

    /**
     * Handle document download requests
     */
    public function handle_document_download()
    {
        if (!isset($_GET['ufsc_download_doc'])) {
            return;
        }

        $club_id = isset($_GET['club_id']) ? intval($_GET['club_id']) : 0;
        $doc_type = isset($_GET['doc_type']) ? sanitize_text_field($_GET['doc_type']) : '';
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';

        // Verify nonce
        if (!wp_verify_nonce($nonce, 'ufsc_download_doc_' . $club_id . '_' . $doc_type)) {
            wp_die('Accès non autorisé.');
        }

        // Check permissions
        if (!$this->can_access_document($club_id, $doc_type)) {
            wp_die('Vous n\'avez pas l\'autorisation d\'accéder à ce document.');
        }

        // Get document URL
        $document_url = $this->get_document_url($club_id, $doc_type);
        if (!$document_url) {
            wp_die('Document introuvable.');
        }

        // Serve the file
        $this->serve_document($document_url, $doc_type);
    }

    /**
     * Check if current user can access a document
     *
     * @param int $club_id Club ID
     * @param string $doc_type Document type
     * @return bool
     */
    private function can_access_document($club_id, $doc_type)
    {
        // Admin always has access
        if (current_user_can('ufsc_manage')) {
            return true;
        }

        // Frontend users can only access their own club's documents
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            
            // Check if user is associated with this club
            global $wpdb;
            $club = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d AND responsable_id = %d",
                $club_id,
                $user_id
            ));

            if ($club) {
                // Users can only access certain documents based on club status
                // CORRECTION: Updated to use 'Actif' status instead of 'valide' for consistency
                if ($club->statut === 'Actif' && $doc_type === 'attestation_affiliation') {
                    return true;
                }
                
                // Club owners can access their own documents
                return true;
            }
        }

        return false;
    }

    /**
     * Get document URL from database
     *
     * @param int $club_id Club ID
     * @param string $doc_type Document type
     * @return string|false
     */
    private function get_document_url($club_id, $doc_type)
    {
        $club_manager = UFSC_Club_Manager::get_instance();
        $club = $club_manager->get_club($club_id);

        if (!$club) {
            return false;
        }

        $doc_column = 'doc_' . $doc_type;
        return isset($club->{$doc_column}) ? $club->{$doc_column} : false;
    }

    /**
     * Serve document file with proper headers
     *
     * @param string $document_url Document URL
     * @param string $doc_type Document type
     */
    private function serve_document($document_url, $doc_type)
    {
        // Get file path from URL
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $document_url);

        if (!file_exists($file_path)) {
            wp_die('Fichier introuvable sur le serveur.');
        }

        // Get file info
        $file_info = pathinfo($file_path);
        $file_extension = strtolower($file_info['extension']);

        // Set appropriate content type
        $content_types = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        $content_type = isset($content_types[$file_extension]) ? $content_types[$file_extension] : 'application/octet-stream';

        // Set headers
        header('Content-Type: ' . $content_type);
        header('Content-Length: ' . filesize($file_path));
        header('Content-Disposition: inline; filename="' . $file_info['basename'] . '"');
        header('Cache-Control: private, max-age=3600');

        // Output file
        readfile($file_path);
        exit;
    }

    /**
     * Generate secure download link for a document
     *
     * @param int $club_id Club ID
     * @param string $doc_type Document type
     * @return string
     */
    public function get_secure_download_link($club_id, $doc_type)
    {
        $nonce = wp_create_nonce('ufsc_download_doc_' . $club_id . '_' . $doc_type);
        
        return add_query_arg([
            'ufsc_download_doc' => '1',
            'club_id' => $club_id,
            'doc_type' => $doc_type,
            '_wpnonce' => $nonce
        ], home_url());
    }

    /**
     * Handle secure download via AJAX
     */
    public function handle_secure_download()
    {
        $club_id = isset($_POST['club_id']) ? intval($_POST['club_id']) : 0;
        $doc_type = isset($_POST['doc_type']) ? sanitize_text_field($_POST['doc_type']) : '';

        if (!$this->can_access_document($club_id, $doc_type)) {
            wp_send_json_error('Accès non autorisé.');
        }

        $document_url = $this->get_document_url($club_id, $doc_type);
        if (!$document_url) {
            wp_send_json_error('Document introuvable.');
        }

        $secure_link = $this->get_secure_download_link($club_id, $doc_type);
        wp_send_json_success(['download_url' => $secure_link]);
    }

    /**
     * Check if all required documents are present for a club
     *
     * @param int $club_id Club ID
     * @return array Missing documents
     */
    public function get_missing_documents($club_id)
    {
        $club_manager = UFSC_Club_Manager::get_instance();
        $club = $club_manager->get_club($club_id);

        if (!$club) {
            return [];
        }

        $required_documents = [
            'statuts' => 'Statuts du club',
            'recepisse' => 'Récépissé de déclaration en préfecture',
            'jo' => 'Parution au journal officiel',
            'pv_ag' => 'Dernier PV d\'Assemblée Générale',
            'cer' => 'Contrat d\'Engagement Républicain',
            'attestation_cer' => 'Attestation liée au CER'
        ];

        $missing = [];
        foreach ($required_documents as $doc_key => $doc_label) {
            $doc_column = 'doc_' . $doc_key;
            if (empty($club->{$doc_column})) {
                $missing[$doc_key] = $doc_label;
            }
        }

        return $missing;
    }

    /**
     * Validate a club via AJAX
     */
    public function validate_club()
    {
        // Check permissions
        if (!current_user_can('ufsc_manage')) {
            wp_send_json_error('Accès non autorisé.');
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ufsc_validate_club')) {
            wp_send_json_error('Erreur de sécurité.');
        }

        $club_id = isset($_POST['club_id']) ? absint(wp_unslash($_POST['club_id'])) : 0;
        if (!$club_id) {
            wp_send_json_error('ID de club manquant.');
        }

        // Check if club can be validated
        if (!$this->can_validate_club($club_id)) {
            $missing_docs = $this->get_missing_documents($club_id);
            $core_required = ['statuts', 'recepisse', 'cer'];
            $missing_core = array_intersect_key($missing_docs, array_flip($core_required));
            
            if (!empty($missing_core)) {
                $missing_list = implode(', ', $missing_core);
                wp_send_json_error('Le club ne peut pas être validé. Documents obligatoires manquants : ' . $missing_list);
            } else {
                wp_send_json_error('Le club ne peut pas être validé car des documents obligatoires sont manquants.');
            }
        }

        // Update club status to 'Actif' (standardized status)
        // CORRECTION: Changed from 'valide' to 'Actif' to standardize club status throughout the plugin
        $club_manager = UFSC_Club_Manager::get_instance();
        $result = $club_manager->update_club($club_id, [
            'statut' => 'Actif',
            'date_affiliation' => current_time('mysql')
        ]);

        if ($result) {
            wp_send_json_success('Club validé avec succès.');
        } else {
            wp_send_json_error('Erreur lors de la validation du club.');
        }
    }

    /**
     * Check if a club can be validated (core required documents present)
     *
     * @param int $club_id Club ID
     * @return bool
     */
    public function can_validate_club($club_id)
    {
        $missing_docs = $this->get_missing_documents($club_id);
        
        // Allow validation if only optional documents are missing
        // Required core documents: statuts, recepisse, cer
        $core_required = ['statuts', 'recepisse', 'cer'];
        $missing_core = array_intersect_key($missing_docs, array_flip($core_required));
        
        // Club can be validated if no core documents are missing
        return empty($missing_core);
    }
}