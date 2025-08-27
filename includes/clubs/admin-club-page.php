<?php
if (!defined('ABSPATH')) {
    exit;
}

// 🔐 Chargement sécurisé des régions et statuts
$regions_path = plugin_dir_path(__FILE__) . '../data/regions.php';
$regions = file_exists($regions_path) ? require $regions_path : [];

$statuts_club = [
    'Actif',
    'Inactif',
    'En attente',
    'Suspendu',
    'Dissous'
];

// ✅ Inclusion de la classe de gestion
require_once plugin_dir_path(__FILE__) . 'class-club-manager.php';

global $wpdb;

// 🔍 Récupération du club
$club_id = isset($_GET['club_id']) ? intval(wp_unslash($_GET['club_id'])) : 0;
if (!$club_id) {
    echo '<div class="notice notice-error"><p>Aucun club sélectionné.</p></div>';
    return;
}

$club = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d",
    $club_id
));
if (!$club) {
    echo '<div class="notice notice-error"><p>Club introuvable.</p></div>';
    return;
}

// 📦 Filtres dynamiques pour les licences
$filters = [];
$params = [$club_id];
$where = "club_id = %d";

$filter_fields = [
    'sexe'        => ['type' => '%s', 'column' => 'sexe'],
    'categorie'   => ['type' => '%s', 'column' => 'categorie', 'like' => true],
    'is_included' => ['type' => '%d', 'column' => 'is_included'],
    'date_from'   => ['type' => '%s', 'column' => 'date_naissance', 'operator' => '>='],
    'date_to'     => ['type' => '%s', 'column' => 'date_naissance', 'operator' => '<='],
];

foreach ($filter_fields as $key => $config) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $val = sanitize_text_field(wp_unslash($_GET[$key]));
        $filters[$key] = $val;
        $operator = $config['operator'] ?? '=';
        $like = $config['like'] ?? false;

        $where .= " AND {$config['column']} $operator " . ($like ? "LIKE" : $config['type']);
        $params[] = $like ? "%$val%" : ($config['type'] === '%d' ? intval($val) : $val);
    }
}

// 📤 Export CSV
if (isset($_GET['export_licences']) && isset($_GET['_wpnonce']) && wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'export_licences_' . $club_id)) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=licences-club-' . $club_id . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID','Nom','Prénom','Sexe','Naissance','Email','Incluse','Inscription']);

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id, nom, prenom, sexe, date_naissance, email, is_included, date_inscription
         FROM {$wpdb->prefix}ufsc_licences
         WHERE $where
         ORDER BY date_inscription DESC",
        ...$params
    ));

    foreach ($rows as $r) {
        fputcsv($output, [
            $r->id, $r->nom, $r->prenom, $r->sexe,
            $r->date_naissance, $r->email,
            $r->is_included ? 'Oui' : 'Non',
            $r->date_inscription
        ]);
    }
    if (is_resource($output)) {
        fclose($output);
    }
    exit;
}

// 📚 Pagination
$page      = isset($_GET['paged']) ? max(1, intval(wp_unslash($_GET['paged']))) : 1;
$per_page  = 20;
$offset    = ($page - 1) * $per_page;
$total     = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences WHERE $where",
    ...$params
));

$params_limit = array_merge($params, [$per_page, $offset]);
$licences = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ufsc_licences
     WHERE $where
     ORDER BY date_inscription DESC
     LIMIT %d OFFSET %d",
    ...$params_limit
));
?>

<div class="wrap ufsc-ui">
    <h1>Licences — <?php echo esc_html($club->nom); ?></h1>

    <!-- 🔍 Filtres -->
    <form method="get" class="ufsc-licences-filters">
        <input type="hidden" name="page" value="ufsc_voir_licences">
        <input type="hidden" name="club_id" value="<?php echo esc_attr($club_id); ?>">

        <label>Sexe:
            <select name="sexe">
                <option value="">—Tous—</option>
                <option value="M" <?php echo selected('M', $filters['sexe'] ?? '', false); ?>>M</option>
                <option value="F" <?php echo selected('F', $filters['sexe'] ?? '', false); ?>>F</option>
            </select>
        </label>

        <label>Catégorie:
            <input name="categorie" type="text" value="<?php echo esc_attr($filters['categorie'] ?? ''); ?>" placeholder="e.g. Dirigeant">
        </label>

        <label>Date naissance:
            <input name="date_from" type="date" value="<?php echo esc_attr($filters['date_from'] ?? ''); ?>"> à
            <input name="date_to" type="date" value="<?php echo esc_attr($filters['date_to'] ?? ''); ?>">
        </label>

        <label>Incluse:
            <select name="is_included">
                <option value="">—Tous—</option>
                <option value="1" <?php echo selected('1', $filters['is_included'] ?? '', false); ?>>Oui</option>
                <option value="0" <?php echo selected('0', $filters['is_included'] ?? '', false); ?>>Non</option>
            </select>
        </label>

        <input type="submit" class="button" value="Filtrer">
        <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(add_query_arg('export_licences', '1'), 'export_licences_' . $club_id)); ?>">Exporter CSV</a>
    </form>

    <!-- 📊 Tableau -->
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th><th>Nom</th><th>Prénom</th><th>Sexe</th>
                <th>Date naissance</th><th>Email</th><th>Incluse</th><th>Inscription</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($licences): foreach ($licences as $l): ?>
                <tr>
                    <td><?php echo esc_html($l->id); ?></td>
                    <td><?php echo esc_html($l->nom); ?></td>
                    <td><?php echo esc_html($l->prenom); ?></td>
                    <td><?php echo esc_html($l->sexe); ?></td>
                    <td><?php echo esc_html($l->date_naissance); ?></td>
                    <td><?php echo esc_html($l->email); ?></td>
                    <td><?php echo $l->is_included ? '✅' : '💰'; ?></td>
                    <td><?php echo esc_html($l->date_inscription); ?></td>
                </tr>
            <?php endforeach;
            else: ?>
                <tr><td colspan="8">Aucune licence trouvée.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 🔁 Pagination -->
    <div class="tablenav">
        <div class="tablenav-pages">
            <?php
            echo paginate_links([
                'base'      => add_query_arg('paged', '%#%'),
                'format'    => '',
                'current'   => $page,
                'total'     => ceil($total / $per_page),
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;'
            ]);
?>
        </div>
    </div>
</div>
