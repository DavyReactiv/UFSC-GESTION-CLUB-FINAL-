<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// 1. Sécurité
$licence_id = isset($_GET['licence_id']) ? intval(wp_unslash($_GET['licence_id'])) : 0;
if (!$licence_id) {
    echo '<div class="notice notice-error"><p>Licence introuvable.</p></div>';
    return;
}

// 2. Récupération de la licence
$table = $wpdb->prefix . 'ufsc_licences';
$licence = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $licence_id));

if (!$licence) {
    echo '<div class="notice notice-error"><p>Licence non trouvée.</p></div>';
    return;
}
?>

<div class="wrap">
    <h1>Compléter la licence — <?php echo esc_html($licence->nom); ?></h1>

    <!-- 🎯 FORMULAIRE avec action vers admin-post.php -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('ufsc_update_dirigeant', 'ufsc_licence_nonce'); ?>
        <input type="hidden" name="action" value="ufsc_update_dirigeant">
        <input type="hidden" name="licence_id" value="<?php echo esc_attr($licence->id); ?>">

        <!-- 👇 Tous les champs inchangés, on conserve ton code existant -->
        <table class="form-table">
            <!-- ... tes champs ici ... (inchangés) -->
        </table>

        <?php submit_button('Valider la licence'); ?>
    </form>
</div>
