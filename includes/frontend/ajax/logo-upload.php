<?php
// AJAX handler for club logo upload (direct file upload preferred)
if (!defined('ABSPATH')) { exit; }

function ufsc_handle_set_club_logo() {
    if (!ufsc_check_ajax_nonce('ufsc_set_club_logo_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Erreur de sécurité. Veuillez recharger la page.']);
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Vous devez être connecté pour modifier le logo.']);
    }
    $access_check = ufsc_check_frontend_access('dashboard');
    if (!$access_check['allowed']) {
        wp_send_json_error(['message' => 'Vous n\'êtes pas autorisé à effectuer cette action.']);
    }
    $club = $access_check['club'];
    if (!ufsc_is_club_active($club)) {
        wp_send_json_error(['message' => 'Votre club doit être validé pour modifier le logo.']);
    }

    $attachment_id = 0;

    // Preferred: direct upload
    if (!empty($_FILES['logo_file']) && isset($_FILES['logo_file']['tmp_name'])) {
        $file = $_FILES['logo_file'];
        $max_size = 2 * 1024 * 1024; // 2MB
        if (!empty($file['size']) && $file['size'] > $max_size) {
            wp_send_json_error(['message' => 'Fichier trop volumineux. Taille max: 2 Mo.']);
        }
        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'webp'         => 'image/webp',
        ];
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $overrides = [ 'test_form' => false, 'mimes' => $allowed_mimes ];
        $movefile = wp_handle_upload($file, $overrides);
        if (!$movefile || !empty($movefile['error'])) {
            wp_send_json_error(['message' => !empty($movefile['error']) ? $movefile['error'] : 'Téléversement impossible.']);
        }
        $filetype = wp_check_filetype(basename($movefile['file']), $allowed_mimes);
        $attachment_post = [
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_text_field(pathinfo($movefile['file'], PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author'    => get_current_user_id(),
        ];
        $attachment_id = wp_insert_attachment($attachment_post, $movefile['file']);
        if (is_wp_error($attachment_id) || !$attachment_id) {
            @unlink($movefile['file']);
            wp_send_json_error(['message' => 'Erreur lors de l\'enregistrement du fichier.']);
        }
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
        wp_update_attachment_metadata($attachment_id, $attach_data);
    }

    // Legacy: provided attachment_id
    if (!$attachment_id) {
        $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
    }
    if (!$attachment_id) {
        wp_send_json_error(['message' => 'Aucun fichier fourni.']);
    }

    $attachment = get_post($attachment_id);
    if (!$attachment || $attachment->post_type !== 'attachment' || !wp_attachment_is_image($attachment_id)) {
        wp_send_json_error(['message' => 'Le fichier doit être une image valide.']);
    }

    global $wpdb; $table_name = $wpdb->prefix . 'ufsc_clubs';
    // Ensure columns exist
    $col1 = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table_name} LIKE %s", 'logo_attachment_id'));
    if (!$col1) { $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN logo_attachment_id BIGINT UNSIGNED NULL"); }
    $col2 = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table_name} LIKE %s", 'logo_url'));
    if (!$col2) { $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN logo_url VARCHAR(255) NULL"); }
    
    // Also update logo_url for persistence
    $logo_url = wp_get_attachment_url($attachment_id);
    $update_data = [
        'logo_attachment_id' => $attachment_id,
        'logo_url' => $logo_url
    ];
    
    $result = $wpdb->update($table_name, $update_data, ['id' => $club->id], ['%d', '%s'], ['%d']);
    
    // $result can be 0 if no rows were affected (e.g., same values), which is not an error
    if ($result === false && $wpdb->last_error) {
        wp_send_json_error(['message' => 'Erreur lors de la sauvegarde du logo.']);
    }
    
    // Verify the attachment still exists and update succeeded (even if 0 rows affected)
    if (!wp_attachment_is_image($attachment_id)) {
        wp_send_json_error(['message' => 'Le fichier attachement n\'est plus valide.']);
    }

    $logo_thumbnail = wp_get_attachment_image_url($attachment_id, 'thumbnail');
    wp_send_json_success([
        'message'        => 'Logo mis à jour avec succès.',
        'logo_url'       => $logo_url,
        'logo_thumbnail' => $logo_thumbnail ?: $logo_url,
        'attachment_id'  => $attachment_id
    ]);
}
add_action('wp_ajax_ufsc_set_club_logo', 'ufsc_handle_set_club_logo');
add_action('wp_ajax_nopriv_ufsc_set_club_logo', 'ufsc_handle_set_club_logo');