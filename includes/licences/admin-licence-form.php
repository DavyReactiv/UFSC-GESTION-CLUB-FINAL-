<?php
if (!defined('ABSPATH')) {
    exit;
}

// ✅ Chargement du gestionnaire de licences
require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';

global $wpdb;
$club_id = isset($_GET['club_id']) ? intval(wp_unslash($_GET['club_id'])) : 0;

// Get club info if club_id is provided
$club = null;
if ($club_id) {
    $club = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d",
        $club_id
    ));
}

// 📊 Récupération des statistiques de licences (only if club is selected)
$total = 0;
$included = 0;
$payantes = 0;
$montant = 0;
$included_percent = 0;

if ($club_id && $club) {
    $table = $wpdb->prefix . 'ufsc_licences';
    $total     = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE club_id = %d", $club_id));
    $included  = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE club_id = %d AND is_included = 1", $club_id));
    
    // Use new quota calculation for accurate counting
    $quota_usage = ufsc_get_quota_usage($club_id);
    $quota_total = intval($club->quota_licences) > 0 ? intval($club->quota_licences) : 10;
    
    $payantes  = $total - $included;
    $montant   = $payantes * 35;
    $included_percent = min(100, round(($quota_usage / $quota_total) * 100));
}

// 📝 Traitement de l'ajout de licence
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('ufsc_add_licence', 'ufsc_add_licence_nonce')) {
    $manager = new UFSC_Licence_Manager();

    // Get club_id from form or URL parameter
    $submitted_club_id = isset($_POST['club_id']) ? intval(wp_unslash($_POST['club_id'])) : $club_id;

    $data = [
        'club_id'                     => $submitted_club_id,
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
        'is_included'                => ($included < 10) ? 1 : 0,
    ];

    // Validation des champs obligatoires pour l'adresse et les coordonnées
    $validation_errors = [];
    
    // Validation des champs obligatoires de base
    if (empty($data['nom'])) {
        $validation_errors[] = 'Le nom est obligatoire.';
    }
    if (empty($data['prenom'])) {
        $validation_errors[] = 'Le prénom est obligatoire.';
    }
    if (empty($data['email']) || !is_email($data['email'])) {
        $validation_errors[] = 'Un email valide est obligatoire.';
    }
    
    // Validation des champs d'adresse (nouveaux requis)
    if (empty($data['adresse'])) {
        $validation_errors[] = 'L\'adresse est obligatoire.';
    }
    if (empty($data['code_postal'])) {
        $validation_errors[] = 'Le code postal est obligatoire.';
    } elseif (!preg_match('/^\d{5}$/', $data['code_postal'])) {
        $validation_errors[] = 'Le code postal doit contenir 5 chiffres.';
    }
    if (empty($data['ville'])) {
        $validation_errors[] = 'La ville est obligatoire.';
    }
    if (empty($data['region'])) {
        $validation_errors[] = 'La région est obligatoire.';
    }
    
    // Validation des téléphones (au moins un requis)
    if (empty($data['tel_mobile']) && empty($data['tel_fixe'])) {
        $validation_errors[] = 'Au moins un numéro de téléphone (fixe ou mobile) est obligatoire.';
    }
    
    if (empty($validation_errors)) {
        $manager->add_licence($data);
        echo '<div class="notice notice-success"><p>✅ Licence ajoutée avec succès — ' . ($data['is_included'] ? 'incluse' : 'payante') . '.</p></div>';
        
        // 🔁 Mise à jour des statistiques avec le club_id soumis
        $club_id = $submitted_club_id; // Update for stats calculation
        
        // Recalculate club info if changed
        if ($club_id) {
            $club = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d",
                $club_id
            ));
        }
        
        $included++;
        $total++;
        $payantes = $total - $included;
        $montant = $payantes * 35;
        $included_percent = min(100, round(($included / 10) * 100));
    } else {
        echo '<div class="notice notice-error"><p><strong>Erreurs dans le formulaire :</strong><br>' . implode('<br>', $validation_errors) . '</p></div>';
    }
}

// Enqueue the license form CSS and JS
wp_enqueue_style(
    'ufsc-licence-form-style',
    UFSC_PLUGIN_URL . 'assets/css/form-licence.css',
    [],
    UFSC_GESTION_CLUB_VERSION
);

wp_enqueue_script(
    'ufsc-licence-form-script',
    UFSC_PLUGIN_URL . 'assets/js/form-licence.js',
    ['jquery'],
    UFSC_GESTION_CLUB_VERSION,
    true
);
?>

<div class="wrap">
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