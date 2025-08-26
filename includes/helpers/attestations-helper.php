<?php
/**
 * Attestations Helper Functions
 * 
 * Helper functions for managing club attestations
 *
 * @package UFSC_Gestion_Club
 * @subpackage Helpers
 * @since 1.0.3
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get club attestation URL by type
 * 
 * @param int $club_id Club ID
 * @param string $type Attestation type ('affiliation' or 'assurance')
 * @return string|false URL if exists, false otherwise
 */
function ufsc_get_club_attestation_url($club_id, $type) {
    if (!in_array($type, ['affiliation', 'assurance'])) {
        return false;
    }
    
    // First try the new meta field system (WordPress attachments)
    $attachment_id = ufsc_club_get_attestation_attachment_id($club_id, $type);
    if ($attachment_id) {
        $url = wp_get_attachment_url($attachment_id);
        if ($url) {
            return $url;
        }
    }
    
    // Fallback to legacy direct URL storage
    $legacy_url = get_post_meta($club_id, "_ufsc_attestation_{$type}", true);
    if ($legacy_url) {
        return $legacy_url;
    }
    
    // Legacy field compatibility (old structure)
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    $legacy_field = "doc_attestation_{$type}";
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT {$legacy_field} FROM {$table_name} WHERE id = %d",
        $club_id
    ));
    
    return $result ?: false;
}

/**
 * Get club attestation attachment ID
 * 
 * @param int $club_id Club ID
 * @param string $type Attestation type ('affiliation' or 'assurance')
 * @return int|false Attachment ID if exists, false otherwise
 */
function ufsc_club_get_attestation_attachment_id($club_id, $type) {
    if (!in_array($type, ['affiliation', 'assurance'])) {
        return false;
    }
    
    $attachment_id = get_post_meta($club_id, "_ufsc_attestation_{$type}_id", true);
    return $attachment_id ? intval($attachment_id) : false;
}

/**
 * Set club attestation attachment ID
 * 
 * @param int $club_id Club ID
 * @param string $type Attestation type ('affiliation' or 'assurance')
 * @param int $attachment_id WordPress attachment ID
 * @return bool Success
 */
function ufsc_club_set_attestation_attachment_id($club_id, $type, $attachment_id) {
    if (!in_array($type, ['affiliation', 'assurance'])) {
        return false;
    }
    
    return update_post_meta($club_id, "_ufsc_attestation_{$type}_id", intval($attachment_id));
}

/**
 * Check if club has admin attestation
 * 
 * @param int $club_id Club ID
 * @param string $type Attestation type ('affiliation' or 'assurance')
 * @return bool True if attestation exists
 */
function ufsc_club_has_admin_attestation($club_id, $type) {
    return (bool) ufsc_get_club_attestation_url($club_id, $type);
}

/**
 * Get attestation file extension from URL
 * 
 * @param string $url File URL
 * @return string File extension
 */
function ufsc_get_attestation_file_extension($url) {
    $path_info = pathinfo($url);
    return strtolower($path_info['extension'] ?? '');
}

/**
 * Get attestation download filename
 * 
 * @param int $club_id Club ID
 * @param string $type Attestation type
 * @return string Formatted filename
 */
function ufsc_get_attestation_download_filename($club_id, $type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    
    $club_name = $wpdb->get_var($wpdb->prepare(
        "SELECT nom FROM {$table_name} WHERE id = %d",
        $club_id
    ));
    
    $club_name = $club_name ? sanitize_file_name($club_name) : "club_{$club_id}";
    $type_label = ($type === 'affiliation') ? 'affiliation' : 'assurance';
    
    return "attestation_{$type_label}_{$club_name}";
}

/**
 * Output file download with proper headers
 * 
 * @param string $file_path Full file path
 * @param string $download_filename Filename for download
 * @param string $mime_type MIME type
 */
function ufsc_output_file_download($file_path, $download_filename, $mime_type = null) {
    if (!file_exists($file_path)) {
        wp_die('Fichier non trouvé.');
    }
    
    // Detect MIME type if not provided
    if (!$mime_type) {
        $wp_filetype = wp_check_filetype($file_path);
        $mime_type = $wp_filetype['type'] ?: 'application/octet-stream';
    }
    
    // Get file extension for filename
    $extension = ufsc_get_attestation_file_extension($file_path);
    if ($extension && !preg_match('/\.' . preg_quote($extension, '/') . '$/', $download_filename)) {
        $download_filename .= '.' . $extension;
    }
    
    // Set headers for download
    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output file and exit
    readfile($file_path);
    exit;
}

/**
 * Delete club attestation
 * 
 * @param int $club_id Club ID
 * @param string $type Attestation type
 * @return bool Success
 */
function ufsc_delete_club_attestation($club_id, $type) {
    if (!in_array($type, ['affiliation', 'assurance'])) {
        return false;
    }
    
    // Delete attachment if using new system
    $attachment_id = ufsc_club_get_attestation_attachment_id($club_id, $type);
    if ($attachment_id) {
        wp_delete_attachment($attachment_id, true);
        delete_post_meta($club_id, "_ufsc_attestation_{$type}_id");
    }
    
    // Delete legacy URL meta
    delete_post_meta($club_id, "_ufsc_attestation_{$type}");
    
    return true;
}