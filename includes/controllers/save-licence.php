<?php
if (!defined('ABSPATH')) {
    exit;
}

function ufsc_handle_save_licence() {
    $action = isset($_POST['action']) ? sanitize_text_field(wp_unslash($_POST['action'])) : '';
    $nonce_field = $action === 'ufsc_update_licence' ? 'ufsc_update_licence_nonce' : 'ufsc_add_licence_nonce';

    if (!isset($_POST[$nonce_field]) || !wp_verify_nonce(wp_unslash($_POST[$nonce_field]), $action)) {
        wp_die(__('Security check failed.', 'plugin-ufsc-gestion-club-13072025'));
    }

    if (!current_user_can('ufsc_manage') && !current_user_can('ufsc_manage_own')) {
        wp_die(__('Permissions insuffisantes.', 'plugin-ufsc-gestion-club-13072025'));
    }

    $club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club(get_current_user_id()) : null;
    if (!$club) {
        wp_die(__('Club introuvable.', 'plugin-ufsc-gestion-club-13072025'));
    }
    $club_id = is_object($club) ? intval($club->id) : intval($club['id']);

    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_licences';

    $licence_id = isset($_POST['licence_id']) ? intval(wp_unslash($_POST['licence_id'])) : 0;
    if ($action === 'ufsc_update_licence') {
        if (!$licence_id) {
            wp_die(__('Licence invalide.', 'plugin-ufsc-gestion-club-13072025'));
        }
        $existing_club = (int) $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$table} WHERE id=%d", $licence_id));
        if (!current_user_can('ufsc_manage') && $existing_club !== $club_id) {
            wp_die(__('Accès refusé.', 'plugin-ufsc-gestion-club-13072025'));
        }
    }

    $allowed_fields = [
        'nom', 'prenom', 'sexe', 'date_naissance', 'email', 'adresse', 'suite_adresse',
        'code_postal', 'ville', 'tel_fixe', 'tel_mobile', 'profession', 'identifiant_laposte',
        'region', 'numero_licence_delegataire', 'note', 'fonction'
    ];

    $data = [];
    foreach ($allowed_fields as $field) {
        $value = isset($_POST[$field]) ? wp_unslash($_POST[$field]) : '';
        if ($field === 'email') {
            $data[$field] = sanitize_email($value);
        } else {
            $data[$field] = sanitize_text_field($value);
        }
    }

    $checkboxes = [
        'reduction_benevole', 'reduction_postier', 'fonction_publique', 'competition',
        'licence_delegataire', 'diffusion_image', 'infos_fsasptt', 'infos_asptt',
        'infos_cr', 'infos_partenaires', 'honorabilite', 'assurance_dommage_corporel',
        'assurance_assistance'
    ];

    foreach ($checkboxes as $key) {
        $data[$key] = isset($_POST[$key]) ? 1 : 0;
    }

    if ($action === 'ufsc_update_licence') {
        $wpdb->update($table, $data, ['id' => $licence_id]);
    } else {
        $data['club_id'] = $club_id;
        $data['date_inscription'] = current_time('mysql');
        $data['statut'] = 'brouillon';
        $wpdb->insert($table, $data);
    }

    $redirect = wp_get_referer() ? wp_get_referer() : home_url('/');
    $redirect = add_query_arg('licence_saved', '1', $redirect);
    wp_safe_redirect($redirect);
    exit;
}

add_action('admin_post_ufsc_add_licence', 'ufsc_handle_save_licence');
add_action('admin_post_nopriv_ufsc_add_licence', 'ufsc_handle_save_licence');
add_action('admin_post_ufsc_update_licence', 'ufsc_handle_save_licence');
add_action('admin_post_nopriv_ufsc_update_licence', 'ufsc_handle_save_licence');
