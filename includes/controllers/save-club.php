<?php

if (!defined('ABSPATH')) {
    exit;
}

function ufsc_handle_club_submission()
{
    if (!isset($_POST['ufsc_club_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ufsc_club_nonce']), 'ufsc_save_club')) {
        wp_die(__('Security check failed.', 'plugin-ufsc-gestion-club-13072025'));
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_clubs';

    // Check for club_id in both GET and POST for edit detection
    $club_id = 0;
    if (isset($_GET['club_id'])) {
        $club_id = intval(wp_unslash($_GET['club_id']));
    } elseif (isset($_POST['club_id'])) {
        $club_id = intval(wp_unslash($_POST['club_id']));
    }
    $is_edit = $club_id > 0;

    $data = [
        'nom' => sanitize_text_field(wp_unslash($_POST['nom'])),
        'ville' => sanitize_text_field(wp_unslash($_POST['ville'])),
        'email' => sanitize_email(wp_unslash($_POST['email'])),
        'telephone' => sanitize_text_field(wp_unslash($_POST['telephone'])),
        'region' => sanitize_text_field(wp_unslash($_POST['region'])),
        'type' => sanitize_text_field(wp_unslash($_POST['type'])),
        'statut' => sanitize_text_field(wp_unslash($_POST['statut'])),
        'siren' => sanitize_text_field(wp_unslash($_POST['siren'])),
        'ape' => sanitize_text_field(wp_unslash($_POST['ape'])),
        'ccn' => sanitize_text_field(wp_unslash($_POST['ccn'])),
        'ancv' => sanitize_text_field(wp_unslash($_POST['ancv'])),
        'num_declaration' => sanitize_text_field(wp_unslash($_POST['num_declaration'])),
        'date_declaration' => sanitize_text_field(wp_unslash($_POST['date_declaration'])),
    ];

    // Dirigeants
    $roles = ['president', 'secretaire', 'tresorier', 'entraineur'];
    foreach ($roles as $r) {
        $data["{$r}_nom"] = sanitize_text_field(wp_unslash($_POST["{$r}_nom"]));
        $data["{$r}_tel"] = sanitize_text_field(wp_unslash($_POST["{$r}_tel"]));
        $data["{$r}_email"] = sanitize_email(wp_unslash($_POST["{$r}_email"]));
    }

    // WordPress user association handling
    if (is_admin() && current_user_can('manage_ufsc')) {
        // Admin - handle direct responsable_id assignment
        $responsable_id = isset($_POST['responsable_id']) ? intval(wp_unslash($_POST['responsable_id'])) : 0;
        
        // Validate user association
        if ($responsable_id > 0) {
            // Check if user exists
            $user_exists = get_userdata($responsable_id);
            if (!$user_exists) {
                wp_die('L\'utilisateur sélectionné n\'existe pas.');
            }
            
            // Check if user is already associated with another club
            if (ufsc_is_user_already_associated($responsable_id, $club_id)) {
                wp_die('Cet utilisateur est déjà associé à un autre club. Un utilisateur ne peut être associé qu\'à un seul club.');
            }
        }
        
        $data['responsable_id'] = $responsable_id ?: null;
    } else if (!is_admin()) {
        // Frontend - handle user association for affiliation
        $association_type = isset($_POST['user_association_type']) ? sanitize_text_field(wp_unslash($_POST['user_association_type'])) : 'current';
        $responsable_id = null;
        
        switch ($association_type) {
            case 'current':
                $responsable_id = get_current_user_id();
                break;
                
            case 'create':
                $responsable_id = ufsc_create_user_for_club();
                if (!$responsable_id) {
                    wp_die('Erreur lors de la création du compte utilisateur. Vérifiez que le nom d\'utilisateur et l\'email ne sont pas déjà utilisés.');
                }
                break;
                
            case 'existing':
                $existing_user_id = isset($_POST['existing_user_id']) ? intval(wp_unslash($_POST['existing_user_id'])) : 0;
                if ($existing_user_id > 0) {
                    // Validate user exists and is not already associated
                    $user_exists = get_userdata($existing_user_id);
                    if (!$user_exists) {
                        wp_die(__('Selected user does not exist.', 'plugin-ufsc-gestion-club-13072025'));
                    }
                    if (ufsc_is_user_already_associated($existing_user_id, 0)) {
                        wp_die(__('This user is already associated with another club.', 'plugin-ufsc-gestion-club-13072025'));
                    }
                    $responsable_id = $existing_user_id;
                }
                break;
        }
        
        // Validate that we have a user ID
        if (!$responsable_id) {
            wp_die(__('Error associating user with club.', 'plugin-ufsc-gestion-club-13072025'));
        }
        
        $data['responsable_id'] = $responsable_id;
    }

    // Création ou mise à jour
    if ($is_edit) {
        $wpdb->update($table, $data, ['id' => $club_id]);
    } else {
        $wpdb->insert($table, $data);
        $club_id = $wpdb->insert_id;
    }

    // 📎 Upload des documents avec WordPress
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $doc_keys = [
        'statuts', 'recepisse', 'jo', 'pv_ag', 'cer', 'attestation_cer'
    ];

    foreach ($doc_keys as $key) {
        if (!empty($_FILES[$key]['name'])) {
            $file = $_FILES[$key];
            $filename = sanitize_file_name($file['name']);

            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                continue;
            }

            // Upload via WordPress
            $upload_overrides = array('test_form' => false);
            $movefile = wp_handle_upload($file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                // Fichier uploadé avec succès
                // Enregistrer le lien du document en base si nécessaire
            }
        }
    }

    // 🎫 Création automatique des 3 licences incluses (si nouvelle insertion)
    if (!$is_edit) {
        require_once plugin_dir_path(__FILE__) . '/../clubs/class-club-manager.php';
        $manager = \UFSC\Clubs\ClubManager::get_instance();

        $licences_table = $wpdb->prefix . 'ufsc_licences';
        $now = current_time('mysql');

        $default_dirigeants = [
            'Président'  => 'president',
            'Secrétaire' => 'secretaire',
            'Trésorier'  => 'tresorier'
        ];

        foreach ($default_dirigeants as $label => $slug) {
            $wpdb->insert($licences_table, [
                'club_id' => $club_id,
                'nom' => sanitize_text_field($_POST["{$slug}_nom"]),
                'prenom' => '',
                'email' => sanitize_email($_POST["{$slug}_email"]),
                'date_naissance' => null,
                'sexe' => 'M',
                'categorie' => 'Dirigeant',
                'statut' => 'Validé',
                'is_included' => 1,
                'note' => 'Licence dirigeant générée automatiquement',
                'date_inscription' => $now
            ]);
        }
    }

    // ✅ Redirection
    wp_redirect(admin_url('admin.php?page=ufsc_voir_clubs&success=1'));
    exit;
}
add_action('admin_post_ufsc_save_club', 'ufsc_handle_club_submission');
