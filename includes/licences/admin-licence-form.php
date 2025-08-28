<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('ufsc_manage_own')) {
    wp_die(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'));
}


if ( ! class_exists( 'UFSC_Licence_Repository' ) ) {
    require_once UFSC_PLUGIN_PATH . 'includes/repository/class-licence-repository.php';
}

require_once UFSC_PLUGIN_PATH . 'includes/repository/class-licence-repository.php';

require_once UFSC_PLUGIN_PATH . 'includes/licences/validation.php';

$repo       = new UFSC_Licence_Repository();
$licence_id = isset($_GET['licence_id']) ? absint( wp_unslash( $_GET['licence_id'] ) ) : 0;
$licence    = $licence_id ? $repo->get_by_id($licence_id) : null;
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('ufsc_license_admin_action', 'ufsc_license_admin_nonce')) {
    $data = [
        'nom'                        => sanitize_text_field($_POST['nom'] ?? ''),
        'prenom'                     => sanitize_text_field($_POST['prenom'] ?? ''),
        'email'                      => sanitize_email($_POST['email'] ?? ''),
        'date_naissance'             => sanitize_text_field($_POST['date_naissance'] ?? ''),
        'categorie'                  => sanitize_text_field($_POST['categorie'] ?? ''),
        'club_id'                    => intval($_POST['club_id'] ?? 0),

        'adresse'                    => sanitize_text_field($_POST['adresse'] ?? ''),
        'code_postal'                => sanitize_text_field($_POST['code_postal'] ?? ''),
        'ville'                      => sanitize_text_field($_POST['ville'] ?? ''),
        'region'                     => sanitize_text_field($_POST['region'] ?? ''),
        'tel_mobile'                 => sanitize_text_field($_POST['tel_mobile'] ?? ''),
        'tel_fixe'                   => sanitize_text_field($_POST['tel_fixe'] ?? ''),
        'honorabilite'               => !empty($_POST['honorabilite']) ? 1 : 0,
        'assurance_dommage_corporel' => !empty($_POST['assurance_dommage_corporel']) ? 1 : 0,
        'assurance_assistance'       => !empty($_POST['assurance_assistance']) ? 1 : 0,
    ];

    $errors = ufsc_validate_licence_data($data);
    if (empty($data['nom'])) {
        $errors[] = __('Le nom est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['prenom'])) {
        $errors[] = __('Le prénom est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['email']) || !is_email($data['email'])) {
        $errors[] = __('Un email valide est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['date_naissance'])) {
        $errors[] = __('La date de naissance est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['categorie'])) {
        $errors[] = __('La catégorie est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['club_id'])) {
        $errors[] = __('Le club est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['sexe'])) {
        $errors[] = __('Le sexe est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['adresse'])) {
        $errors[] = __('L\'adresse est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['code_postal']) || !preg_match('/^\d{5}$/', $data['code_postal'])) {
        $errors[] = __('Un code postal valide est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['ville'])) {
        $errors[] = __('La ville est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['tel_mobile'])) {
        $errors[] = __('Le téléphone mobile est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }
    if (empty($data['region'])) {
        $errors[] = __('La région est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }

    if (empty($errors)) {
        if ($licence_id) {
            $repo->update($licence_id, $data);
            echo '<div class="notice notice-success"><p>' . esc_html__('Licence mise à jour.', 'plugin-ufsc-gestion-club-13072025') . '</p></div>';
        } else {
            $licence_id = $repo->insert($data);
            echo '<div class="notice notice-success"><p>' . esc_html__('Licence créée.', 'plugin-ufsc-gestion-club-13072025') . '</p></div>';
        }
        $licence = $repo->get_by_id($licence_id);
    } else {
        echo '<div class="notice notice-error"><p>' . implode('<br>', array_map('esc_html', $errors)) . '</p></div>';
    }
}

$action_url = admin_url('admin.php?page=ufsc_license_add_admin' . ($licence_id ? '&licence_id=' . $licence_id : ''));

?>
<div class="wrap ufsc-ui">
    <h1><?php echo esc_html("Ajouter une licence" . ($club && $club_id ? " pour le club « {$club->nom} »" : "")); ?></h1>

    <?php if ($club && $club_id): ?>
    <div class="ufsc-quota-box">
        <strong>Quota de licences</strong>
        <div class="ufsc-quota-progress">
            <div class="ufsc-quota-bar" style="width: <?php echo esc_attr($included_percent); ?>%;"></div>
        </div>
        <p>
            <span class="ufsc-badge badge-green">Quota utilisé : <?php echo esc_html($quota_usage); ?>/<?php echo esc_html($quota_total); ?></span>
            <span class="ufsc-badge badge-red">Payantes : <?php echo esc_html($payantes); ?></span><br>
            <strong>Montant total :</strong> <?php echo number_format($montant, 2, ',', ' '); ?> €
        </p>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url("admin.php?page=ufsc-ajouter-licence" . ($club_id ? "&club_id={$club_id}" : ""))); ?>">
        <?php wp_nonce_field('ufsc_add_licence', 'ufsc_add_licence_nonce'); ?>

        <?php require UFSC_PLUGIN_PATH . 'includes/frontend/parts/form-licence.php'; ?>

        <div class="ufsc-form-submit">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Ajouter la licence', 'plugin-ufsc-gestion-club-13072025'); ?>">
        </div>
    </form>
</div>
