<?php
if (!defined('ABSPATH')) {
    exit;
}

// ✅ Chargement du gestionnaire de licences
require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';

global $wpdb;
$licence_id = isset($_GET['licence_id']) ? intval(wp_unslash($_GET['licence_id'])) : 0;

if (!$licence_id) {
    echo '<div class="notice notice-error"><p>Aucune licence sélectionnée.</p></div>';
    return;
}

if (!current_user_can('ufsc_manage_licences')) {
    wp_die(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'));
}

if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'ufsc_edit_licence_' . $licence_id)) {
    wp_die(__('Action non autorisée.', 'plugin-ufsc-gestion-club-13072025'));
}

$manager = new UFSC_Licence_Manager();
$current_licence = $manager->get_licence_by_id($licence_id);
$is_validated = $current_licence && $current_licence->statut === 'validee';

if (!$current_licence) {
    echo '<div class="notice notice-error"><p>Licence introuvable.</p></div>';
    return;
}

$club = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d",
    $current_licence->club_id
));

if (!$club) {
    echo '<div class="notice notice-error"><p>Club associé introuvable.</p></div>';
    return;
}

// 📝 Traitement de la modification de licence
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('ufsc_edit_licence', 'ufsc_edit_licence_nonce')) {
    $data = [
        'club_id'                     => isset($_POST['club_id']) ? intval($_POST['club_id']) : $current_licence->club_id,
        'nom'                         => isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '',
        'prenom'                      => isset($_POST['prenom']) ? sanitize_text_field(wp_unslash($_POST['prenom'])) : '',
        'sexe'                        => (isset($_POST['sexe']) && wp_unslash($_POST['sexe']) === 'F') ? 'F' : 'M',
        'date_naissance'             => isset($_POST['date_naissance']) ? sanitize_text_field(wp_unslash($_POST['date_naissance'])) : '',
        'email'                       => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
        'adresse'                     => isset($_POST['adresse']) ? sanitize_text_field(wp_unslash($_POST['adresse'])) : '',
        'suite_adresse'              => isset($_POST['suite_adresse']) ? sanitize_text_field(wp_unslash($_POST['suite_adresse'])) : '',
        'code_postal'                => isset($_POST['code_postal']) ? sanitize_text_field(wp_unslash($_POST['code_postal'])) : '',
        'ville'                      => isset($_POST['ville']) ? sanitize_text_field(wp_unslash($_POST['ville'])) : '',
        'tel_fixe'                   => isset($_POST['tel_fixe']) ? sanitize_text_field(wp_unslash($_POST['tel_fixe'])) : '',
        'tel_mobile'                 => isset($_POST['tel_mobile']) ? sanitize_text_field(wp_unslash($_POST['tel_mobile'])) : '',
        'reduction_benevole'         => isset($_POST['reduction_benevole']) ? intval($_POST['reduction_benevole']) : 0,
        'reduction_postier'          => isset($_POST['reduction_postier']) ? intval($_POST['reduction_postier']) : 0,
        'identifiant_laposte'        => isset($_POST['identifiant_laposte']) ? sanitize_text_field(wp_unslash($_POST['identifiant_laposte'])) : '',
        'profession'                 => isset($_POST['profession']) ? sanitize_text_field(wp_unslash($_POST['profession'])) : '',
        'fonction_publique'          => isset($_POST['fonction_publique']) ? intval($_POST['fonction_publique']) : 0,
        'competition'                => isset($_POST['competition']) ? intval($_POST['competition']) : 0,
        'licence_delegataire'        => isset($_POST['licence_delegataire']) ? intval($_POST['licence_delegataire']) : 0,
        'numero_licence_delegataire' => isset($_POST['numero_licence_delegataire']) ? sanitize_text_field(wp_unslash($_POST['numero_licence_delegataire'])) : '',
        'diffusion_image'            => isset($_POST['diffusion_image']) ? intval($_POST['diffusion_image']) : 0,
        'infos_fsasptt'              => isset($_POST['infos_fsasptt']) ? intval($_POST['infos_fsasptt']) : 0,
        'infos_asptt'                => isset($_POST['infos_asptt']) ? intval($_POST['infos_asptt']) : 0,
        'infos_cr'                   => isset($_POST['infos_cr']) ? intval($_POST['infos_cr']) : 0,
        'infos_partenaires'          => isset($_POST['infos_partenaires']) ? intval($_POST['infos_partenaires']) : 0,
        'honorabilite'               => isset($_POST['honorabilite']) ? intval($_POST['honorabilite']) : 0,
        'assurance_dommage_corporel' => isset($_POST['assurance_dommage_corporel']) ? intval($_POST['assurance_dommage_corporel']) : 0,
        'assurance_assistance'       => isset($_POST['assurance_assistance']) ? intval($_POST['assurance_assistance']) : 0,
        'note'                       => isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '',
        'region'                     => isset($_POST['region']) ? sanitize_text_field(wp_unslash($_POST['region'])) : '',
        'is_included'                => isset($_POST['is_included']) ? 1 : 0,
    ];

    if ($is_validated) {
        $locked_fields = [
            'nom', 'prenom', 'sexe', 'date_naissance', 'adresse', 'suite_adresse',
            'code_postal', 'ville', 'region', 'profession', 'identifiant_laposte',
            'reduction_benevole', 'reduction_postier', 'fonction_publique',
            'competition', 'licence_delegataire', 'numero_licence_delegataire',
            'diffusion_image', 'infos_fsasptt', 'infos_asptt', 'infos_cr',
            'infos_partenaires', 'honorabilite', 'assurance_dommage_corporel',
            'assurance_assistance', 'note', 'is_included'
        ];
        foreach ($locked_fields as $field) {
            $data[$field] = $current_licence->$field;
        }
    }

    $success = $manager->update_licence($licence_id, $data);

    if ($success) {
        echo '<div class="notice notice-success"><p>✅ Licence modifiée avec succès.</p></div>';
        // Reload the licence data to show updated values
        $current_licence = $manager->get_licence_by_id($licence_id);
        $club = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d",
            $current_licence->club_id
        ));
    } else {
        echo '<div class="notice notice-error"><p>❌ Erreur lors de la modification de la licence.</p></div>';
    }
}

// Enqueue the license form CSS and JS
wp_enqueue_style(
    'ufsc-licence-form-style',
    UFSC_PLUGIN_URL . 'assets/css/form-licence.css',
    [],
    UFSC_PLUGIN_VERSION
);

wp_enqueue_script(
    'ufsc-licence-form-script',
    UFSC_PLUGIN_URL . 'assets/js/form-licence.js',
    ['jquery'],
    UFSC_PLUGIN_VERSION,
    true
);
?>

<div class="wrap">
    <h1><?php echo esc_html("Modifier la licence de {$current_licence->prenom} {$current_licence->nom} (Club: {$club->nom})"); ?></h1>

    <!-- License Actions -->
    <div class="ufsc-license-actions" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa;">
        <h3>Actions sur cette licence</h3>
        <p>
            <strong>Statut:</strong> 
            <?php 
            $status = $current_licence->statut ?? 'en_attente';
            switch($status) {
                case 'validee':
                    echo '<span style="color: #46b450;">✅ Validée</span>';
                    break;
                case 'refusee':
                    echo '<span style="color: #dc3232;">❌ Refusée</span>';
                    break;
                default:
                    echo '<span style="color: #ffb900;">⏳ En attente</span>';
            }
            ?>
        </p>
        
        <?php if ($status === 'validee'): ?>
        <div class="ufsc-attestation-download">
            <h4>Attestation de licence</h4>
            <p>Télécharger l'attestation de licence pour ce licencié.</p>
            <?php 
            $download_url = add_query_arg([
                'action' => 'ufsc_download_licence_attestation_admin',
                'licence_id' => $licence_id,
                'nonce' => wp_create_nonce('ufsc_licence_attestation_admin_' . $licence_id)
            ], admin_url('admin-ajax.php'));
            ?>
            <a href="<?php echo esc_url($download_url); ?>" class="button button-secondary">
                <span class="dashicons dashicons-download"></span> Télécharger l'attestation
            </a>
        </div>
        <?php else: ?>
        <div class="ufsc-attestation-unavailable">
            <h4>Attestation de licence</h4>
            <p><em>L'attestation sera disponible après validation de la licence.</em></p>
        </div>
        <?php endif; ?>
    </div>

    <?php $form_action = wp_nonce_url(
        admin_url("admin.php?page=ufsc-modifier-licence&licence_id={$licence_id}"),
        'ufsc_edit_licence_' . $licence_id
    ); ?>
    <form method="post" action="<?php echo esc_url($form_action); ?>">
        <?php wp_nonce_field('ufsc_edit_licence', 'ufsc_edit_licence_nonce'); ?>

        <?php require UFSC_PLUGIN_PATH . 'includes/frontend/parts/form-licence.php'; ?>

        <div class="ufsc-form-submit">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Mettre à jour la licence', 'plugin-ufsc-gestion-club-13072025'); ?>">
            <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_voir_licences&club_id=' . $current_licence->club_id)); ?>" class="button button-secondary">Retour à la liste</a>
        </div>
    </form>
</div>