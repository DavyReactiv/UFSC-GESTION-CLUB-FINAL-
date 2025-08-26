<?php
/**
 * UFSC - Helpers d'upload de logo de club
 *
 * Déclare ufsc_validate_logo_upload() et ufsc_process_club_logo_upload()
 * afin qu'elles soient disponibles dès le chargement du frontend.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Valide le fichier de logo uploadé
 *
 * @param array $file $_FILES['club_logo']
 * @return true|WP_Error
 */
function ufsc_validate_logo_upload($file)
{
    // Taille max 2 Mo
    if (!empty($file['size']) && $file['size'] > 2 * 1024 * 1024) {
        return new WP_Error('file_too_large', 'Le fichier est trop volumineux (2MB maximum)');
    }

    // Types autorisés
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $file_type = wp_check_filetype($file['name'] ?? '');

    $mime_ok = in_array(($file['type'] ?? ''), $allowed_types, true);
    $ext_ok  = in_array(($file_type['type'] ?? ''), $allowed_types, true);

    if (!$mime_ok && !$ext_ok) {
        return new WP_Error('invalid_file_type', 'Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
    }

    return true;
}

/**
 * Traite l'upload de logo (soumission depuis le tableau de bord club)
 * - Attend $_POST['action'] === 'ufsc_update_club_logo'
 */
function ufsc_process_club_logo_upload()
{
    // Vérifie si la requête correspond
    if (!isset($_POST['action']) || $_POST['action'] !== 'ufsc_update_club_logo') {
        return;
    }

    // Nonce
    if (!wp_verify_nonce($_POST['ufsc_logo_nonce'] ?? '', 'ufsc_update_club_logo')) {
        wp_die('Erreur de sécurité', 'Erreur', ['response' => 403]);
    }

    // Connexion
    if (!is_user_logged_in()) {
        wp_die('Vous devez être connecté', 'Erreur', ['response' => 401]);
    }

    // Accès club
    if (!function_exists('ufsc_check_frontend_access')) {
        wp_die('Accès refusé', 'Erreur', ['response' => 403]);
    }

    $access_check = ufsc_check_frontend_access('home');
    if (empty($access_check['allowed'])) {
        wp_die('Accès refusé', 'Erreur', ['response' => 403]);
    }

    $club = $access_check['club'];
    if (!class_exists('UFSC_Club_Manager')) {
        wp_die('Service indisponible', 'Erreur', ['response' => 500]);
    }
    $club_manager = UFSC_Club_Manager::get_instance();

    // Fichier présent ?
    if (empty($_FILES['club_logo']['name'])) {
        $redirect_url = add_query_arg([
            'view' => 'home',
            'logo_update' => 'error',
            'message' => urlencode('Aucun fichier sélectionné')
        ], get_permalink());
        wp_redirect($redirect_url);
        exit;
    }

    $file = $_FILES['club_logo'];

    // Validation
    $validation = ufsc_validate_logo_upload($file);
    if (is_wp_error($validation)) {
        $redirect_url = add_query_arg([
            'view' => 'home',
            'logo_update' => 'error',
            'message' => urlencode($validation->get_error_message())
        ], get_permalink());
        wp_redirect($redirect_url);
        exit;
    }

    // Upload
    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $movefile = wp_handle_upload($file, ['test_form' => false]);

    if ($movefile && !isset($movefile['error'])) {
        // Mise à jour du logo
        $club_manager->update_club_field($club->id, 'logo_url', $movefile['url']);

        $redirect_url = add_query_arg([
            'view' => 'home',
            'logo_update' => 'success',
            'message' => urlencode('Logo mis à jour avec succès')
        ], get_permalink());
    } else {
        $redirect_url = add_query_arg([
            'view' => 'home',
            'logo_update' => 'error',
            'message' => urlencode("Erreur lors de l'upload: " . ($movefile['error'] ?? 'Erreur inconnue'))
        ], get_permalink());
    }

    wp_redirect($redirect_url);
    exit;
}