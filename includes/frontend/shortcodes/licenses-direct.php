<?php
// Direct licences listing shortcode with filters/sort/CSV and actions
if (!defined('ABSPATH')) { exit; }

if (!function_exists('ufscx_register_licences_direct_shortcode')) {
function ufscx_register_licences_direct_shortcode() {
    add_shortcode('ufsc_licences_direct', 'ufscx_licences_direct_shortcode');
}
add_action('init', 'ufscx_register_licences_direct_shortcode');
}

if (!function_exists('ufscx_resolve_club_id')) {
function ufscx_resolve_club_id($user_id = 0){
    if (!$user_id) $user_id = get_current_user_id();
    if (!$user_id) return 0;
    // usermeta keys
    $club_id = (int) get_user_meta($user_id, 'ufsc_club_id', true);
    if (!$club_id) $club_id = (int) get_user_meta($user_id, 'club_id', true);
    if ($club_id) return $club_id;
    global $wpdb;
    $t = $wpdb->prefix.'ufsc_clubs';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t)) === $t){
        $club_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE responsable_id = %d LIMIT 1", $user_id));
        if ($club_id) return $club_id;
    }
    return 0;
}
}

if (!function_exists('ufscx_licences_direct_shortcode')) {
function ufscx_licences_direct_shortcode($atts){
    if (!is_user_logged_in()){
        return '<div class="ufsc-alert ufsc-alert-error">Vous devez être connecté.</div>';
    }
    $club_id = ufscx_resolve_club_id();
    if (!$club_id){
        return '<div class="ufsc-alert ufsc-alert-error">Club introuvable pour ce compte.</div>';
    }
    global $wpdb;
    $club_name = $wpdb->get_var(
        $wpdb->prepare("SELECT nom FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d", $club_id)
    );
    $a = shortcode_atts([
        'per_page' => 25,
        'add_url'  => home_url('/ajouter-licencie/'),
        'enable_csv' => 'yes'
    ], $atts, 'ufsc_licences_direct');

    // Enqueue assets minimal
    wp_register_style('ufscx-licences-direct', plugins_url('../../../assets/css/ufsc-licenses-direct.css', __FILE__), [], '1.0');
    wp_enqueue_style('ufscx-licences-direct');
    wp_enqueue_script('ufscx-licences-direct', plugins_url('../../../assets/js/ufsc-licenses-direct.js', __FILE__), ['jquery'], '1.0', true);
    wp_localize_script('ufscx-licences-direct', 'UFSCX_AJAX', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ufscx_licences'),
    ]);

    // Fetch data
    global $wpdb;
    $t = $wpdb->prefix.'ufsc_licences';
    $licences = [];
    if ($wpdb->get_var("SHOW TABLES LIKE '$t'") == $t){
        $licences = $wpdb->get_results($wpdb->prepare("SELECT id, nom, prenom, email, sexe, date_naissance, ville, 
                IF(COALESCE(competition,0)=1,'Compétiteur','Loisir') AS categorie,
                IF(COALESCE(is_included,0)=1,'Oui','Non') AS quota,
                COALESCE(statut,'') AS statut,
                COALESCE(date_modification,date_creation) AS date_licence
            FROM $t WHERE club_id = %d ORDER BY id DESC", $club_id), ARRAY_A);
    }

    ob_start(); ?>
    <div class="ufscx-licences">
      <div class="ufscx-header">
        <a class="ufscx-btn ufscx-btn-primary" href="<?php echo esc_url($a['add_url']); ?>">+ Ajouter un licencié</a>
        <?php if ($a['enable_csv']==='yes'): ?>
        <button type="button" class="ufscx-btn ufscx-btn-soft" id="ufscx-export">Exporter CSV</button>
        <?php endif; ?>
        <div class="ufscx-club">
            <?php echo esc_html($club_name ?: "Club #$club_id"); ?>
        </div>
      </div>

      <div class="ufscx-filters">
        <input type="search" id="ufscx-q" placeholder="Rechercher (nom, prénom, email, ville)">
        <select id="ufscx-status">
          <option value="">Statut : Tous</option>
          <option value="validee">Validée</option>
          <option value="brouillon">Brouillon</option>
          <option value="pending_payment">En attente</option>
        </select>
        <select id="ufscx-cat">
          <option value="">Catégorie : Tous</option>
          <option value="Compétiteur">Compétiteur</option>
          <option value="Loisir">Loisir</option>
        </select>
        <select id="ufscx-quota">
          <option value="">Quota : Tous</option>
          <option value="Oui">Oui</option>
          <option value="Non">Non</option>
        </select>
        <select id="ufscx-pp">
          <option>10</option><option selected>25</option><option>50</option><option>100</option>
        </select>
      </div>

      <table class="ufscx-table" id="ufscx-table" data-per-page="<?php echo (int)$a['per_page'];?>">
        <thead>
          <tr>
            <th data-k="id" class="sort">#</th>
            <th data-k="nom" class="sort">Nom</th>
            <th data-k="prenom" class="sort">Prénom</th>
            <th data-k="email" class="sort">Email</th>
            <th data-k="sexe" class="sort">Sexe</th>
            <th data-k="date_naissance" class="sort">Naissance</th>
            <th data-k="ville" class="sort">Ville</th>
            <th data-k="categorie" class="sort">Catégorie</th>
            <th data-k="quota" class="sort">Quota</th>
            <th data-k="statut" class="sort">Statut</th>
            <th data-k="date_licence" class="sort">Date Licence</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

      <script type="application/json" id="ufscx-data"><?php echo wp_json_encode($licences); ?></script>
    </div>
    <?php
    return ob_get_clean();
}
}
