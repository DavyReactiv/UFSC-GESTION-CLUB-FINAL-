<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode : [ufsc_ajouter_licence]
 * Affiche un formulaire frontend pour ajouter une licence à un club
 */
function ufsc_shortcode_ajouter_licence()
{
    ob_start();

    // 🛡️ Vérifie si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        echo '<p>Vous devez être connecté pour accéder à cette page.</p>';
        return ob_get_clean();
    }

    $user_id = get_current_user_id();
    $club_id = get_user_meta($user_id, 'ufsc_club_id', true);

    if (!$club_id) {
        echo '<p>Aucun club associé à votre compte. Contactez l’administrateur.</p>';
        return ob_get_clean();
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_licences';

    // 💾 Traitement du formulaire
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ufsc_add_licence'])) {
        check_admin_referer('ufsc_add_licence_form', 'ufsc_nonce');

        $data = [
            'club_id'                     => $club_id,
            'nom'                         => sanitize_text_field(wp_unslash($_POST['nom'])),
            'prenom'                      => sanitize_text_field(wp_unslash($_POST['prenom'])),
            'sexe'                        => (wp_unslash($_POST['sexe']) === 'F' ? 'F' : 'M'),
            'date_naissance'             => sanitize_text_field(wp_unslash($_POST['date_naissance'])),
            'email'                       => sanitize_email(wp_unslash($_POST['email'])),
            'adresse'                     => sanitize_text_field(wp_unslash($_POST['adresse'])),
            'suite_adresse'              => sanitize_text_field(wp_unslash($_POST['suite_adresse'])),
            'code_postal'                => sanitize_text_field(wp_unslash($_POST['code_postal'])),
            'ville'                      => sanitize_text_field(wp_unslash($_POST['ville'])),
            'tel_fixe'                   => sanitize_text_field(wp_unslash($_POST['tel_fixe'])),
            'tel_mobile'                 => sanitize_text_field(wp_unslash($_POST['tel_mobile'])),
            'reduction_benevole'         => isset($_POST['reduction_benevole']) ? 1 : 0,
            'reduction_postier'          => isset($_POST['reduction_postier']) ? 1 : 0,
            'identifiant_laposte'        => sanitize_text_field(wp_unslash($_POST['identifiant_laposte'])),
            'profession'                 => sanitize_text_field(wp_unslash($_POST['profession'])),
            'fonction_publique'          => isset($_POST['fonction_publique']) ? 1 : 0,
            'diffusion_image'            => isset($_POST['diffusion_image']) ? 1 : 0,
            'infos_fsasptt'              => isset($_POST['infos_fsasptt']) ? 1 : 0,
            'infos_asptt'                => isset($_POST['infos_asptt']) ? 1 : 0,
            'infos_cr'                   => isset($_POST['infos_cr']) ? 1 : 0,
            'infos_partenaires'          => isset($_POST['infos_partenaires']) ? 1 : 0,
            'honorabilite'               => isset($_POST['honorabilite']) ? 1 : 0,
            'competition'                => isset($_POST['competition']) ? 1 : 0,
            'licence_delegataire'        => isset($_POST['licence_delegataire']) ? 1 : 0,
            'numero_licence_delegataire' => sanitize_text_field(wp_unslash($_POST['numero_licence_delegataire'])),
            'note'                       => sanitize_textarea_field(wp_unslash($_POST['note'])),
            'assurance_dommage_corporel' => isset($_POST['assurance_dommage_corporel']) ? 1 : 0,
            'assurance_assistance'       => isset($_POST['assurance_assistance']) ? 1 : 0,
            'region'                     => sanitize_text_field(wp_unslash($_POST['region'])),
        ];

        // 🎯 Gestion du quota des 10 licences offertes
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE club_id = %d", $club_id));
        $data['is_included'] = ($count < 10) ? 1 : 0;

        $wpdb->insert($table, $data);

        echo '<div class="ufsc-success">✅ Licence ajoutée avec succès (' . ($data['is_included'] ? 'incluse' : 'payante') . ').</div>';
    }

    // 📊 Statistiques du quota
    $total     = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE club_id = %d", $club_id));
    $incluses  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE club_id = %d AND is_included = 1", $club_id));
    $payantes  = $total - $incluses;
    $montant   = $payantes * 35;
    $percent   = min(100, round(($incluses / 10) * 100));

    // 🎨 Affichage du formulaire UI
    include UFSC_PLUGIN_PATH . 'includes/frontend/parts/licence-form.php';

    return ob_get_clean();
}
add_shortcode('ufsc_ajouter_licence', 'ufsc_shortcode_ajouter_licence');
