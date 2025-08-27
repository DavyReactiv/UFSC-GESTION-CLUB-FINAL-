<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ufscx_resolve_club_id')) {
    require_once dirname(__DIR__) . '/shortcodes/licenses-direct.php';
}
$club_id = ufscx_resolve_club_id();
if (!$club_id) {
    echo '<p>🔒 Accès refusé. Club introuvable.</p>';
    return;
}

require_once dirname(__DIR__, 2) . '/licences/class-licence-filters.php';
require_once dirname(__DIR__, 2) . '/licences/class-ufsc-licenses-repository.php';

$filters = UFSC_Licence_Filters::get_filter_parameters(['club_id' => $club_id]);
if (empty($filters['statuses'])) {
    // By default only show validated licences or those without a status
    $filters['statuses'] = ['validee', ''];
}
$repo = new UFSC_Licenses_Repository();
$license_data = $repo->get_list($filters);
$licences = $license_data['data'];
$search = $filters['search_global'];
?>

<form method="get" class="ufsc-search-form ufsc-mb-20">
    <input type="hidden" name="page_id" value="<?php echo esc_attr(get_the_ID()); ?>">
    <input type="text" name="search_licence" placeholder="🔍 Rechercher..." value="<?php echo esc_attr($search); ?>" class="ufsc-w-250">
    <button type="submit" class="button button-primary">Filtrer</button>
</form>

<?php if ($licences):; ?>
    <table class="widefat striped fixed">
        <thead>
            <tr>
                <th>#</th><th>Nom</th><th>Prénom</th><th>Sexe</th>
                <th>Date de naissance</th><th>Email</th>
                <th>Incluse</th><th>Statut</th><th>Attestation</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($licences as $l):; ?>
                <tr>
                    <td><?php echo esc_html($l->id); ?></td>
                    <td><?php echo esc_html($l->nom); ?></td>
                    <td><?php echo esc_html($l->prenom); ?></td>
                    <td><?php echo esc_html($l->sexe); ?></td>
                    <td><?php echo esc_html($l->date_naissance); ?></td>
                    <td><?php echo esc_html($l->email); ?></td>
                    <td><?php echo $l->is_included ? '✅' : '💰'; ?></td>
                    <td><?php echo ufsc_get_license_status_badge($l->statut, $l->payment_status ?? ''); ?></td>
                    <td>
                        <?php 
                        $attestation_url = $l->attestation_url ?? null;
                        if ($attestation_url): 
                            $download_nonce = wp_create_nonce('ufsc_download_licence_attestation_' . $l->id);
                            $download_url = add_query_arg([
                                'action' => 'ufsc_download_licence_attestation',
                                'licence_id' => $l->id,
                                'nonce' => $download_nonce
                            ], admin_url('admin-ajax.php'));
                        ?>
                            <a href="<?php echo esc_url($download_url); ?>" 
                               class="ufsc-btn ufsc-btn-secondary" 
                               title="Télécharger l'attestation">
                                📄 Télécharger
                            </a>
                        <?php else: ?>
                            <span class="ufsc-color-gray">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach;; ?>
        </tbody>
    </table>
    <?php
    $total_pages = (int) ceil($license_data['total_items'] / $license_data['per_page']);
    if ($total_pages > 1) {
        echo '<div class="tablenav"><div class="tablenav-pages">' .
            paginate_links([
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $license_data['current_page'],
                'total' => $total_pages,
                'add_args' => array_filter([
                    'search_licence' => $search,
                    'statut' => $filters['statuses'],
                ]),
            ]) .
            '</div></div>';
    }
    ?>
<?php else:; ?>
    <p>Aucune licence trouvée pour ce club.</p>
<?php endif;; ?>
