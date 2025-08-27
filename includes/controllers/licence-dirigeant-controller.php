<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_post_ufsc_update_dirigeant', 'ufsc_update_dirigeant_handler');

function ufsc_update_dirigeant_handler()
{
    // 🔒 Vérification nonce
    if (!isset($_POST['ufsc_licence_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ufsc_licence_nonce']), 'ufsc_update_dirigeant')) {
        wp_die('Erreur de sécurité.');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_licences';

    $licence_id = isset($_POST['licence_id']) ? intval(wp_unslash($_POST['licence_id'])) : 0;
    if (!$licence_id) {
        wp_redirect(add_query_arg('message', 'licence_invalide', wp_get_referer()));
        exit;
    }

    // 🧼 Sanitize données
    $data = [
        'prenom'                    => sanitize_text_field(wp_unslash($_POST['prenom'])),
        'date_naissance'           => sanitize_text_field(wp_unslash($_POST['date_naissance'])),
        'sexe'                     => sanitize_text_field(wp_unslash($_POST['sexe'])),
        'email'                    => sanitize_email(wp_unslash($_POST['email'])),
        'adresse'                  => sanitize_text_field(wp_unslash($_POST['adresse'])),
        'adresse2'                 => sanitize_text_field(wp_unslash($_POST['adresse2'])),
        'code_postal'              => sanitize_text_field(wp_unslash($_POST['code_postal'])),
        'ville'                    => sanitize_text_field(wp_unslash($_POST['ville'])),
        'tel_fixe'                 => sanitize_text_field(wp_unslash($_POST['tel_fixe'])),
        'tel_mobile'               => sanitize_text_field(wp_unslash($_POST['tel_mobile'])),
        'reduction_benevole'       => isset($_POST['reduction_benevole']) ? 1 : 0,
        'reduction_postier'        => isset($_POST['reduction_postier']) ? 1 : 0,
        'identifiant_laposte'      => sanitize_text_field(wp_unslash($_POST['identifiant_laposte'])),
        'profession'               => sanitize_text_field(wp_unslash($_POST['profession'])),
        'fonction_publique'        => isset($_POST['fonction_publique']) ? 1 : 0,
        'diffusion_image'          => isset($_POST['diffusion_image']) ? 1 : 0,
        'infos_fsasptt'            => isset($_POST['infos_fsasptt']) ? 1 : 0,
        'infos_asptt'              => isset($_POST['infos_asptt']) ? 1 : 0,
        'infos_comite'             => isset($_POST['infos_comite']) ? 1 : 0,
        'infos_partenaires'        => isset($_POST['infos_partenaires']) ? 1 : 0,
        'honorabilite'             => isset($_POST['honorabilite']) ? 1 : 0,
        'competition'              => isset($_POST['competition']) ? 1 : 0,
        'delegataire'              => isset($_POST['delegataire']) ? 1 : 0,
        'numero_licence_delegataire' => sanitize_text_field(wp_unslash($_POST['numero_licence_delegataire'])),
        'note'                     => sanitize_textarea_field(wp_unslash($_POST['note'])),
        'assurance_dommage_corporel' => isset($_POST['assurance_dommage_corporel']) ? 1 : 0,
        'assurance_assistance'       => isset($_POST['assurance_assistance']) ? 1 : 0,
    ];

    // 💾 Mise à jour base de données
    $updated = $wpdb->update($table, $data, ['id' => $licence_id]);

    // 🔁 Redirection avec message
    if ($updated !== false) {
        $club_id_param = isset($_GET['club_id']) ? intval(wp_unslash($_GET['club_id'])) : 0;
        wp_redirect(add_query_arg('message', 'licence_updated', admin_url('admin.php?page=ufsc_voir_licences&club_id=' . $club_id_param)));
    } else {
        wp_redirect(add_query_arg('message', 'update_error', wp_get_referer()));
    }

    exit;
}
