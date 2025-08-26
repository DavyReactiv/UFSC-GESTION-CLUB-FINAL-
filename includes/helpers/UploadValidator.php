<?php

namespace UFSC\Helpers;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validate file upload for club documents
 *
 * Performs comprehensive validation including:
 * - Upload error checking
 * - File size validation (5MB max)
 * - Real MIME type validation using wp_check_filetype_and_ext()
 * - Allowed MIME types restriction
 * - Secure file renaming
 *
 * @param array $file File array from $_FILES
 * @param int $club_id Club ID for filename generation
 * @param string $doc_type Document type for filename generation
 * @return array|WP_Error Array with 'success' and 'filename' on success, WP_Error on failure
 */
function ufsc_validate_club_document_upload($file, $club_id, $doc_type) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur.',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée.',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé.',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé.',
            UPLOAD_ERR_NO_TMP_DIR => 'Répertoire temporaire manquant.',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque.',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement du fichier.'
        ];
        
        $message = isset($error_messages[$file['error']]) 
            ? $error_messages[$file['error']] 
            : 'Erreur inconnue lors du téléchargement.';
            
        return new WP_Error('upload_error', $message);
    }
    
    // Check file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        return new WP_Error('file_too_large', 'Fichier trop volumineux. Taille maximale: 5 Mo.');
    }
    
    // Validate file type using WordPress function for security
    $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    
    // Check if file type was detected
    if (!$file_info['type'] || !$file_info['ext']) {
        return new WP_Error('invalid_file_type', 'Type de fichier non reconnu ou invalide.');
    }
    
    // Allowed MIME types
    $allowed_mimes = [
        'application/pdf',
        'image/jpeg', 
        'image/png'
    ];
    
    if (!in_array($file_info['type'], $allowed_mimes)) {
        return new WP_Error('file_type_not_allowed', 'Type de fichier non autorisé. Formats acceptés: PDF, JPG, PNG.');
    }
    
    // Generate secure filename: club_{ID}_{doc_type}_{timestamp}.{ext}
    $timestamp = time();
    $extension = $file_info['ext'];
    $secure_filename = "club_{$club_id}_{$doc_type}_{$timestamp}.{$extension}";
    
    return [
        'success' => true,
        'filename' => $secure_filename,
        'type' => $file_info['type'],
        'ext' => $file_info['ext']
    ];
}

/**
 * UFSC Upload Validator Class
 * 
 * Static class for centralized upload validation
 */
class UploadValidator {
    
    /**
     * Validate club document upload
     *
     * @param array $file File array from $_FILES
     * @param int $club_id Club ID for filename generation
     * @param string $doc_type Document type for filename generation
     * @return array|WP_Error Array with validation result or WP_Error on failure
     */
    public static function validate_document($file, $club_id, $doc_type) {
        return ufsc_validate_club_document_upload($file, $club_id, $doc_type);
    }
    
    /**
     * Get allowed document types for clubs
     *
     * @return array Array of allowed document types with labels
     */
    public static function get_allowed_document_types() {
        return [
            'statuts' => 'Statuts du club',
            'recepisse' => 'Récépissé de déclaration',
            'jo' => 'Parution au JO',
            'pv_ag' => 'Dernier PV d\'AG',
            'cer' => 'Contrat d\'engagement républicain',
            'attestation_cer' => 'Attestation liée au CER',
            'attestation_affiliation' => 'Attestation d\'affiliation'
        ];
    }
    
    /**
     * Get allowed MIME types
     *
     * @return array Array of allowed MIME types
     */
    public static function get_allowed_mime_types() {
        return [
            'application/pdf',
            'image/jpeg', 
            'image/png'
        ];
    }
    
    /**
     * Get maximum file size in bytes
     *
     * @return int Maximum file size (5MB)
     */
    public static function get_max_file_size() {
        return 5 * 1024 * 1024; // 5MB
    }
}