<?php
if (! defined('ABSPATH')) {
    exit;
}

// Chargement de la classe via le singleton
require_once plugin_dir_path(__FILE__) . '../clubs/class-club-manager.php';
$manager = UFSC_Club_Manager::get_instance();

// Récupère l’ID du club de l’utilisateur
$user_id = get_current_user_id();
$club_id = get_user_meta($user_id, 'ufsc_club_id', true);

if (! $club_id) {
    echo '<div class="notice notice-error"><p>❌ Aucun club rattaché à votre compte.</p></div>';
    return;
}

$club_data = $manager->wpdb->get_row(
    $manager->wpdb->prepare(
        "SELECT * FROM {$manager->table_clubs} WHERE id = %d",
        $club_id
    )
);

if (! $club_data) {
    echo '<div class="notice notice-error"><p>❌ Club introuvable.</p></div>';
    return;
}

// Traitement du POST
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['ufsc_update_club_infos'])
    && check_admin_referer('ufsc_edit_club_infos', 'ufsc_edit_club_infos_nonce')
) {
    $new_data = [
        'nom'       => sanitize_text_field(wp_unslash($_POST['nom'])),
        'ville'     => sanitize_text_field(wp_unslash($_POST['ville'])),
        'email'     => sanitize_email(wp_unslash($_POST['email'])),
        'telephone' => sanitize_text_field(wp_unslash($_POST['telephone'])),
        'type'      => sanitize_text_field(wp_unslash($_POST['type'])),
        'statut'    => sanitize_text_field(wp_unslash($_POST['statut'])),
        'region'    => sanitize_text_field(wp_unslash($_POST['region'])),
    ];

    $updated = $manager->update_club($club_id, $new_data);

    if (false !== $updated) {
        echo '<div class="notice notice-success"><p>✅ Informations mises à jour avec succès.</p></div>';
        // Mise à jour des données pour réaffichage
        foreach ($new_data as $k => $v) {
            $club_data->$k = $v;
        }
    } else {
        echo '<div class="notice notice-warning"><p>⚠️ Aucune modification détectée.</p></div>';
    }
}
?>

<form method="post" class="ufsc-club-infos-form">
    <?php wp_nonce_field('ufsc_edit_club_infos', 'ufsc_edit_club_infos_nonce'); ?>

    <table class="form-table">
        <!-- Prefixe chaque champ -->
        <!-- Ici un aperçu simplifié -->
        <tr><th><label for="nom">Nom du club</label></th>
            <td><input name="nom" id="nom" value="<?php echo esc_attr($club_data->nom); ?>" required class="regular-text"></td></tr>
        <!-- Refaire les autres champs liste… -->
    </table>

    <p>
        <button type="submit" name="ufsc_update_club_infos" class="button button-primary">
            💾 Enregistrer les modifications
        </button>
    </p>
</form>
