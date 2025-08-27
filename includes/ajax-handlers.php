<?php

/**
 * AJAX handlers pour téléversement d'attestations frontend UFSC
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_ufsc_club_search', 'ufsc_ajax_club_search');
function ufsc_ajax_club_search() {
    if (!check_ajax_referer('ufsc_club_search', 'nonce', false)) {
        wp_send_json_error('Invalid nonce', 403);
    }
    if (!current_user_can('manage_ufsc_licenses')) {
        wp_send_json_error('Unauthorized', 403);
    }

    global $wpdb;
    $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
    $results = [];

    if ($term !== '') {
        $like = '%' . $wpdb->esc_like($term) . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT id, nom FROM {$wpdb->prefix}ufsc_clubs WHERE nom LIKE %s ORDER BY nom LIMIT 20", $like)
        );

        foreach ($rows as $row) {
            $results[] = [
                'id'    => (int) $row->id,
                'label' => $row->nom,
            ];
        }
    }

    wp_send_json_success($results);
}

/**
 * AJAX handler for attestation uploads
 * Handles both club and license attestation uploads
 */
add_action('wp_ajax_ufsc_upload_attestation', 'ufsc_handle_attestation_upload');
function ufsc_handle_attestation_upload() {
    // Security checks
    if (!check_ajax_referer('ufsc_attestation_nonce', '_ajax_nonce', false)) {
        wp_send_json_error([
            'message' => esc_html__('Security check failed.', 'ufsc-domain')
        ], 403);
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => esc_html__('You must be logged in to upload an attestation.', 'ufsc-domain')
        ], 401);
    }
    
    // Check user capabilities - allow club managers and admins
    if (!current_user_can('manage_ufsc') && !current_user_can('edit_posts')) {
        wp_send_json_error([
            'message' => esc_html__('Unauthorized access.', 'ufsc-domain')
        ], 403);
    }
    
    // Validate attestation type
    $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
    if (!in_array($type, ['club', 'license'], true)) {
        wp_send_json_error([
            'message' => esc_html__('Invalid attestation type.', 'ufsc-domain')
        ], 400);
    }
    
    
// Ownership check (basic placeholder): ensure the current user matches the resource owner if provided
$licence_id = isset($_POST['licence_id']) ? absint(wp_unslash($_POST['licence_id'])) : 0;
if ($type === 'license' && $licence_id) {
    if (!function_exists('ufsc_user_can_manage_licence')) {
        function ufsc_user_can_manage_licence($user_id_check, $licence_id_check) {
            global $wpdb;
            $table = $wpdb->prefix . 'ufsc_licences';
            $club_id = (int) $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$table} WHERE id = %d", $licence_id_check));
            if (!$club_id) { return false; }
            // Example link table user->club; adapt if your schema differs
            $rel = $wpdb->prefix . 'ufsc_user_clubs';
            $has = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rel} WHERE user_id = %d AND club_id = %d", $user_id_check, $club_id));
            return $has > 0;
        }
    }
    if (!ufsc_user_can_manage_licence($user_id, $licence_id)) {
        wp_send_json_error(['message' => esc_html__('Unauthorized resource.', 'ufsc-domain')], 403);
    }
}
// Check if file was uploaded
    if (!isset($_FILES['attestation_file']) || $_FILES['attestation_file']['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => esc_html__('Le fichier dépasse la taille maximale autorisée.', 'ufsc-domain'),
            UPLOAD_ERR_FORM_SIZE  => esc_html__('Le fichier dépasse la taille maximale du formulaire.', 'ufsc-domain'),
            UPLOAD_ERR_PARTIAL    => esc_html__('Le fichier n\'a été que partiellement téléchargé.', 'ufsc-domain'),
            UPLOAD_ERR_NO_FILE    => esc_html__('Aucun fichier n\'a été téléchargé.', 'ufsc-domain'),
            UPLOAD_ERR_NO_TMP_DIR => esc_html__('Répertoire temporaire manquant.', 'ufsc-domain'),
            UPLOAD_ERR_CANT_WRITE => esc_html__('Échec de l\'écriture du fichier sur le disque.', 'ufsc-domain'),
            UPLOAD_ERR_EXTENSION  => esc_html__('Une extension PHP a arrêté le téléchargement.', 'ufsc-domain')
        ];
        
        $error_code = $_FILES['attestation_file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $error_message = $upload_errors[$error_code] ?? esc_html__('Erreur inconnue lors du téléchargement.', 'ufsc-domain');
        
        wp_send_json_error([
            'message' => $error_message
        ], 400);
    }
    
    $file = $_FILES['attestation_file'];
    
    // Validate file type (PDF only)
    $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    if ($file_info['ext'] !== 'pdf' || $file_info['type'] !== 'application/pdf') {
        wp_send_json_error([
            'message' => esc_html__('Seuls les fichiers PDF sont autorisés.', 'ufsc-domain')
        ], 400);
    }
    
    // Check file size (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $max_size) {
        wp_send_json_error([
            'message' => esc_html__('Le fichier est trop volumineux. Taille maximale autorisée : 5MB.', 'ufsc-domain')
        ], 400);
    }
    
    // Prepare upload
    $user_id = get_current_user_id();
    $timestamp = time();
    $hash = wp_hash($user_id . $type . $timestamp);
    $short_hash = substr($hash, 0, 8);
    
    // Use wp_handle_upload for secure file handling
    $upload_overrides = [
        'test_form' => false,
        'test_size' => true,
        'test_type' => true,
        'mimes' => ['pdf' => 'application/pdf']
    ];
    
    // Filter the filename to include hash for security
    /* Named prefilter for safe removal later */
$ufsc_upload_prefilter_cb = function($file_array) use ($user_id, $type, $short_hash) {
    $pathinfo  = pathinfo($file_array['name']);
    $extension = isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : 'pdf';
    $file_array['name'] = sanitize_file_name(sprintf('ufsc_attestation_%s_%d_%s.%s', $type, (int)$user_id, $short_hash, $extension));
    return $file_array;
};
add_filter('wp_handle_upload_prefilter', $ufsc_upload_prefilter_cb);

$uploaded_file = wp_handle_upload($file, $upload_overrides);

remove_filter('wp_handle_upload_prefilter', $ufsc_upload_prefilter_cb);
// Remove the filter
    // Removed broad filter wipe; handled with remove_filter on our specific callback.
// remove_filter('wp_handle_upload_prefilter', $ufsc_upload_prefilter_cb);
    
    if (isset($uploaded_file['error'])) {
        wp_send_json_error([
            'message' => sprintf(esc_html__('Erreur lors du téléchargement : %s', 'ufsc-domain'), $uploaded_file['error'])
        ], 500);
    }
    
    // Store attestation data in user meta
    $attestations = get_user_meta($user_id, 'ufsc_attestations', true);
    if (!is_array($attestations)) {
        $attestations = [];
    }
    
    // Remove old attestation of the same type if it exists
    if (isset($attestations[$type]) && isset($attestations[$type]['file_path'])) {
        $old_file_path = $attestations[$type]['file_path'];
        if (file_exists($old_file_path)) {
            wp_delete_file($old_file_path);
        }
    }
    
    // Store new attestation data
    $attestations[$type] = [
        'url' => $uploaded_file['url'],
        'file_path' => $uploaded_file['file'],
        'original_name' => sanitize_file_name($file['name']),
        'uploaded_at' => $timestamp,
        'file_size' => $file['size']
    ];
    
    update_user_meta($user_id, 'ufsc_attestations', $attestations);
    
    // Log the upload for security
    error_log(sprintf(
        'UFSC Attestation Upload: User %d uploaded %s attestation (file: %s)',
        $user_id,
        $type,
        basename($uploaded_file['file'])
    ));
    
    // Trigger custom event for extensibility
    do_action('ufsc_attestation_uploaded', $user_id, $type, $uploaded_file);
    
    wp_send_json_success([
        'message' => esc_html__('Attestation téléchargée avec succès !', 'ufsc-domain'),
        'type' => $type,
        'url' => $uploaded_file['url'],
        'uploaded_at' => $timestamp
    ]);
}

/**
 * Helper function to validate uploaded file
 *
 * @param array $file File data from $_FILES
 * @return array|WP_Error Validation result
 */
function ufsc_validate_attestation_file($file) {
    $errors = new WP_Error();
    
    // Check if file exists
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors->add('no_file', esc_html__('Aucun fichier téléchargé.', 'ufsc-domain'));
        return $errors;
    }
    
    // Check file size
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        $errors->add('file_too_large', esc_html__('Fichier trop volumineux (max 5MB).', 'ufsc-domain'));
    }
    
    // Check file type
    $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    if ($file_info['ext'] !== 'pdf' || $file_info['type'] !== 'application/pdf') {
        $errors->add('invalid_type', esc_html__('Seuls les fichiers PDF sont autorisés.', 'ufsc-domain'));
    }
    
    // Check for malicious content (basic check)
    $file_content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
    if (strpos($file_content, '%PDF') !== 0) {
        $errors->add('invalid_pdf', esc_html__('Le fichier ne semble pas être un PDF valide.', 'ufsc-domain'));
    }
    
    if ($errors->has_errors()) {
        return $errors;
    }
    
    return true;
}

/**
 * Get user attestations
 *
 * @param int $user_id User ID
 * @return array User attestations
 */
function ufsc_get_user_attestations($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $attestations = get_user_meta($user_id, 'ufsc_attestations', true);
    return is_array($attestations) ? $attestations : [];
}

/**
 * Delete user attestation
 *
 * @param int $user_id User ID
 * @param string $type Attestation type (club|license)
 * @return bool Success status
 */
function ufsc_delete_user_attestation($user_id, $type) {
    if (!in_array($type, ['club', 'license'], true)) {
        return false;
    }
    
    $attestations = ufsc_get_user_attestations($user_id);
    
    if (isset($attestations[$type])) {
        // Delete physical file
        if (isset($attestations[$type]['file_path']) && file_exists($attestations[$type]['file_path'])) {
            wp_delete_file($attestations[$type]['file_path']);
        }
        
        // Remove from meta
        unset($attestations[$type]);
        update_user_meta($user_id, 'ufsc_attestations', $attestations);
        
        return true;
    }
    
    return false;
}

/**
 * ========================================
 * NEW FRONTEND AJAX HANDLERS
 * ========================================
 */

// Include frontend AJAX handlers
require_once UFSC_PLUGIN_PATH . 'includes/frontend/ajax/licence-add.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/ajax/licence-drafts.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/ajax/logo-upload.php';

/**
 * AJAX handler for licence duplicate detection
 */
add_action('wp_ajax_ufsc_check_licence_duplicate', 'ufsc_ajax_check_licence_duplicate');
function ufsc_ajax_check_licence_duplicate() {
    // Security check
    if (!check_ajax_referer('ufsc_licence_duplicate_nonce', '_ajax_nonce', false)) {
        wp_send_json_error([
            'message' => esc_html__('Échec de vérification de sécurité.', 'ufsc-domain')
        ], 403);
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => esc_html__('Vous devez être connecté.', 'ufsc-domain')
        ], 401);
    }
    
    // Get data from POST
    $nom = isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '';
    $prenom = isset($_POST['prenom']) ? sanitize_text_field(wp_unslash($_POST['prenom'])) : '';
    $date_naissance = isset($_POST['date_naissance']) ? sanitize_text_field(wp_unslash($_POST['date_naissance'])) : '';
    $club_id = isset($_POST['club_id']) ? intval($_POST['club_id']) : 0;
    
    // Validate required fields
    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($club_id)) {
        wp_send_json_error([
            'message' => esc_html__('Champs obligatoires manquants.', 'ufsc-domain')
        ], 400);
    }
    
    // Check duplicate
    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $licence_manager = new UFSC_Licence_Manager();
    
    $duplicate_id = $licence_manager->check_duplicate_licence([
        'nom' => $nom,
        'prenom' => $prenom,
        'date_naissance' => $date_naissance,
        'club_id' => $club_id
    ]);
    
    if ($duplicate_id) {
        wp_send_json_error([
            'is_duplicate' => true,
            'message' => sprintf(
                esc_html__('Une licence existe déjà pour %1$s %2$s né(e) le %3$s.', 'ufsc-domain'),
                $prenom,
                $nom,
                date('d/m/Y', strtotime($date_naissance))
            ),
            'duplicate_id' => $duplicate_id
        ], 409);
    } else {
        wp_send_json_success([
            'is_duplicate' => false,
            'message' => esc_html__('Aucun doublon détecté.', 'ufsc-domain')
        ]);
    }
}