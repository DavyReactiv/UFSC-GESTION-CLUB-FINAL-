<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_ufsc_licenses')) {
    wp_die(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'));
}

require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-repository.php';

$repo       = new UFSC_Licence_Repository();
$licence_id = isset($_GET['licence_id']) ? absint($_GET['licence_id']) : 0;
$licence    = $licence_id ? $repo->get($licence_id) : null;
$errors     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('ufsc_license_admin_action', 'ufsc_license_admin_nonce')) {
    $data = [
        'nom'            => sanitize_text_field($_POST['nom'] ?? ''),
        'prenom'         => sanitize_text_field($_POST['prenom'] ?? ''),
        'email'          => sanitize_email($_POST['email'] ?? ''),
        'date_naissance' => sanitize_text_field($_POST['date_naissance'] ?? ''),
        'categorie'      => sanitize_text_field($_POST['categorie'] ?? ''),
        'club_id'        => intval($_POST['club_id'] ?? 0),
    ];

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

    if (empty($errors)) {
        if ($licence_id) {
            $repo->update($licence_id, $data);
            echo '<div class="notice notice-success"><p>' . esc_html__('Licence mise à jour.', 'plugin-ufsc-gestion-club-13072025') . '</p></div>';
        } else {
            $licence_id = $repo->insert($data);
            echo '<div class="notice notice-success"><p>' . esc_html__('Licence créée.', 'plugin-ufsc-gestion-club-13072025') . '</p></div>';
        }
        $licence = $repo->get($licence_id);
    } else {
        echo '<div class="notice notice-error"><p>' . implode('<br>', array_map('esc_html', $errors)) . '</p></div>';
    }
}

function ufsc_get_club_name($club_id) {
    global $wpdb;
    return $club_id ? $wpdb->get_var($wpdb->prepare("SELECT nom FROM {$wpdb->prefix}ufsc_clubs WHERE id=%d", $club_id)) : '';
}

$action_url = admin_url('admin.php?page=ufsc_license_add_admin' . ($licence_id ? '&licence_id=' . $licence_id : ''));

wp_enqueue_script('jquery-ui-autocomplete');
?>
<div class="wrap">
<h1><?php echo $licence_id ? esc_html__('Modifier une licence', 'plugin-ufsc-gestion-club-13072025') : esc_html__('Ajouter une licence', 'plugin-ufsc-gestion-club-13072025'); ?></h1>
<form method="post" action="<?php echo esc_url($action_url); ?>">
<?php wp_nonce_field('ufsc_license_admin_action', 'ufsc_license_admin_nonce'); ?>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="ufsc-club-search"><?php esc_html_e('Club', 'plugin-ufsc-gestion-club-13072025'); ?></label></th>
                <td>
                    <input type="text" id="ufsc-club-search" value="<?php echo esc_attr(ufsc_get_club_name($licence->club_id ?? 0)); ?>" class="regular-text" />
                    <input type="hidden" name="club_id" id="ufsc-club-id" value="<?php echo esc_attr($licence->club_id ?? 0); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="nom"><?php esc_html_e('Nom', 'plugin-ufsc-gestion-club-13072025'); ?></label></th>
                <td><input name="nom" id="nom" type="text" value="<?php echo esc_attr($licence->nom ?? ''); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="prenom"><?php esc_html_e('Prénom', 'plugin-ufsc-gestion-club-13072025'); ?></label></th>
                <td><input name="prenom" id="prenom" type="text" value="<?php echo esc_attr($licence->prenom ?? ''); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="email"><?php esc_html_e('Email', 'plugin-ufsc-gestion-club-13072025'); ?></label></th>
                <td><input name="email" id="email" type="email" value="<?php echo esc_attr($licence->email ?? ''); ?>" class="regular-text" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="date_naissance"><?php esc_html_e('Date de naissance', 'plugin-ufsc-gestion-club-13072025'); ?></label></th>
                <td><input name="date_naissance" id="date_naissance" type="date" value="<?php echo esc_attr($licence->date_naissance ?? ''); ?>" required></td>
            </tr>
            <tr>
                <th scope="row"><label for="categorie"><?php esc_html_e('Catégorie', 'plugin-ufsc-gestion-club-13072025'); ?></label></th>
                <td><input name="categorie" id="categorie" type="text" value="<?php echo esc_attr($licence->categorie ?? ''); ?>" class="regular-text" required></td>
            </tr>
        </tbody>
    </table>
    <?php submit_button($licence_id ? __('Mettre à jour la licence', 'plugin-ufsc-gestion-club-13072025') : __('Ajouter la licence', 'plugin-ufsc-gestion-club-13072025')); ?>
</form>
</div>

<script>
jQuery(function($){
    $('#ufsc-club-search').autocomplete({
        source: function(request, response){
            $.getJSON(ajaxurl, {action: 'ufsc_club_search', term: request.term}, function(res){
                if (res.success) {
                    response($.map(res.data, function(item){ return { label: item.label, value: item.label, id: item.id }; }));
                } else {
                    response([]);
                }
            });
        },
        select: function(event, ui){
            $('#ufsc-club-id').val(ui.item.id);
        }
    });
});
</script>
