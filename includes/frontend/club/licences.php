<?php
if (!defined('ABSPATH')) exit;

/**
 * Front - Section Licences
 * Rendu principal de la page "Gestion des licences" dans l'espace club
 */
function ufsc_club_render_licences($club){
    if (!$club || empty($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-warn">'.esc_html__('Aucun club associé.', 'plugin-ufsc-gestion-club-13072025').'</div>';
    }
    ob_start();
    ufsc_render_club_licences_list($club);
    return ob_get_clean();
}

/**
 * Rend le bloc d'actions pour une licence donnée.
 *
 * @param int    $id          Identifiant de la licence
 * @param string $statut      Statut actuel de la licence
 * @param bool   $is_included Indique si la licence est incluse dans le quota
 * @param string $edit_url    URL d'édition de la licence
 * @param string $cart_url    URL du panier WooCommerce
 * @param int    $order_id    Identifiant de la commande associée
 * @return string             HTML des boutons d'action
 */
function ufscsn_render_actions($id, $statut, $is_included, $edit_url, $cart_url, $order_id = 0) {
    ob_start();
    ?>
    <div class="ufsc-actions">
      <a class="button button-secondary" title="<?php esc_attr_e('Modifier','plugin-ufsc-gestion-club-13072025'); ?>" href="<?php echo esc_url($edit_url); ?>"><?php _e('Modifier','plugin-ufsc-gestion-club-13072025'); ?></a>
      <?php if ($statut === 'brouillon'): ?>
        <button type="button" class="button ufsc-delete-draft" data-licence-id="<?php echo (int)$id; ?>"><?php _e('Supprimer','plugin-ufsc-gestion-club-13072025'); ?></button>
        <button type="button" class="button button-primary ufsc-add-to-cart" data-licence-id="<?php echo (int)$id; ?>"><?php _e('Ajouter au panier','plugin-ufsc-gestion-club-13072025'); ?></button>
        <button type="button" class="button ufsc-include-quota" data-licence-id="<?php echo (int)$id; ?>"><?php echo $is_included ? esc_html__('Retirer du quota','plugin-ufsc-gestion-club-13072025') : esc_html__('Inclure au quota','plugin-ufsc-gestion-club-13072025'); ?></button>
      <?php elseif ($statut === 'in_cart'): ?>
        <a class="button" href="<?php echo esc_url($cart_url); ?>"><?php _e('Voir panier','plugin-ufsc-gestion-club-13072025'); ?></a>
      <?php elseif (in_array($statut, ['en_attente','refusee'], true)): ?>
        <?php if (empty($order_id)): ?>
          <a class="button button-primary" href="<?php echo esc_url( add_query_arg('ufsc_pay_licence', (int)$id, home_url('/')) ); ?>"><?php _e('Payer','plugin-ufsc-gestion-club-13072025'); ?></a>
        <?php else: ?>
          <a class="button" href="<?php echo esc_url( home_url('/mon-compte/orders/') ); ?>"><?php _e('Voir commande','plugin-ufsc-gestion-club-13072025'); ?></a>
        <?php endif; ?>
      <?php else: ?>
        <a class="button" href="<?php echo esc_url( add_query_arg('view_licence', (int)$id, get_permalink()) ); ?>"><?php _e('Voir','plugin-ufsc-gestion-club-13072025'); ?></a>
      <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Sous-fonction : tableau des licences avec actions
 */
function ufsc_render_club_licences_list($club){
    global $wpdb; 
    $t = $wpdb->prefix.'ufsc_licences';
    $club_id = (int)$club->id;

    // Filtres basiques GET
    $per_page = max(10, absint( wp_unslash( $_GET['pp'] ?? 10 ) ));
    $page     = max(1, absint( wp_unslash( $_GET['p'] ?? 1 ) ));
    $offset   = ($page - 1) * $per_page;
    $statut   = isset($_GET['statut']) ? sanitize_key($_GET['statut']) : '';
    $s        = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

    $where = ["club_id = %d", "(statut IS NULL OR statut <> 'trash')"];
    $args  = [$club_id];

    if ($statut) { $where[] = "statut = %s"; $args[] = $statut; }
    if ($s) { 
        $like = '%'.$wpdb->esc_like($s).'%';
        $where[] = "(nom LIKE %s OR prenom LIKE %s OR email LIKE %s)"; 
        array_push($args, $like, $like, $like);
    }
    $sql_where = implode(' AND ', $where);

    // Total
    $total = (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$t} WHERE {$sql_where}", $args) );

    // Lignes
    $args_rows = $args; $args_rows[] = $per_page; $args_rows[] = $offset;
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$t} WHERE {$sql_where} ORDER BY date_creation DESC LIMIT %d OFFSET %d", 
        $args_rows
    ) );

    $roles_allowed = function_exists('ufsc_roles_allowed') ? ufsc_roles_allowed() : [
        'president'=>'Président','tresorier'=>'Trésorier','secretaire'=>'Secrétaire','adherent'=>'Adhérent','entraineur'=>'Entraîneur'
    ];
    ?>
    <div class="ufsc-front-wrap">
      <h3><?php _e('Gestion des licences', 'plugin-ufsc-gestion-club-13072025'); ?></h3>

      <form class="ufsc-filters" method="get">
        <input type="hidden" name="section" value="licences"/>
        <div class="ufsc-filters-row">
          <div class="ufsc-flex-1">
            <label><?php _e('Recherche','plugin-ufsc-gestion-club-13072025'); ?></label>
            <input type="search" name="s" value="<?php echo esc_attr($s); ?>" placeholder="<?php esc_attr_e('Nom, prénom ou email…','plugin-ufsc-gestion-club-13072025'); ?>" />
          </div>
          <div>
            <label><?php _e('Statut','plugin-ufsc-gestion-club-13072025'); ?></label>
            <select name="statut">
              <option value=""><?php _e('Tous','plugin-ufsc-gestion-club-13072025'); ?></option>
              <?php foreach(['brouillon','in_cart','pending_payment','validee','refusee','expiree'] as $st): ?>
                <option value="<?php echo esc_attr($st); ?>" <?php selected($statut,$st); ?>><?php echo esc_html(ucfirst($st)); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label><?php _e('Par page','plugin-ufsc-gestion-club-13072025'); ?></label>
            <select name="pp"><?php foreach([10,20,50] as $pp): ?><option value="<?php echo $pp; ?>" <?php selected($per_page,$pp); ?>><?php echo $pp; ?></option><?php endforeach; ?></select>
          </div>
          <div class="ufsc-filter-actions"><button class="button"><?php _e('Filtrer','plugin-ufsc-gestion-club-13072025'); ?></button></div>
        </div>
      </form>

      <?php if (!$rows): ?>
        <div class="ufsc-empty"><?php _e('Aucune licence trouvée avec ces critères.','plugin-ufsc-gestion-club-13072025'); ?></div>
      <?php else: ?>
      <table class="ufsc-front-table ufsc-table">
        <thead>
          <tr>
            <th><?php _e('Licencié','plugin-ufsc-gestion-club-13072025'); ?></th>
            <th class="ufsc-col--role"><?php _e('Rôle','plugin-ufsc-gestion-club-13072025'); ?></th>
            <th class="ufsc-col--status"><?php _e('Statut','plugin-ufsc-gestion-club-13072025'); ?></th>
            <th class="ufsc-col--email"><?php _e('Email','plugin-ufsc-gestion-club-13072025'); ?></th>
            <th class="ufsc-col--date"><?php _e('Créée le','plugin-ufsc-gestion-club-13072025'); ?></th>
            <th class="ufsc-col--actions"><?php _e('Actions','plugin-ufsc-gestion-club-13072025'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $r): 
            $stat = $r->statut ?: 'brouillon';
            $role = $r->role ?: 'adherent';
            ?>
            <tr class="ufsc-row" data-licence-id="<?php echo (int)$r->id; ?>">
              <td data-label="<?php esc_attr_e('Licencié','plugin-ufsc-gestion-club-13072025'); ?>">
                <strong><?php echo esc_html(trim(($r->prenom ?? '').' '.($r->nom ?? ''))); ?></strong>
              </td>
              <td class="ufsc-col--role" data-label="<?php esc_attr_e('Rôle','plugin-ufsc-gestion-club-13072025'); ?>">
                <span class="ufsc-badge ufsc-badge-role ufsc-role-<?php echo esc_attr($role); ?>"><?php echo esc_html($roles_allowed[$role] ?? $role); ?></span>
              </td>
              <td class="ufsc-col--status" data-label="<?php esc_attr_e('Statut','plugin-ufsc-gestion-club-13072025'); ?>">
                <?php echo ufsc_get_license_status_badge($stat, $r->payment_status ?? ''); ?>
              </td>
              <td class="ufsc-col--email" data-label="<?php esc_attr_e('Email','plugin-ufsc-gestion-club-13072025'); ?>">
                <?php if(!empty($r->email)): ?><a href="mailto:<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></a><?php endif; ?>
              </td>
              <td class="ufsc-col--date" data-label="<?php esc_attr_e('Créée le','plugin-ufsc-gestion-club-13072025'); ?>">
                <?php echo $r->date_creation ? esc_html( date_i18n(get_option('date_format'), strtotime($r->date_creation)) ) : '—'; ?>
              </td>
              <td class="ufsc-col--actions" data-label="<?php esc_attr_e('Actions','plugin-ufsc-gestion-club-13072025'); ?>">
                <?php
                  $edit_url = add_query_arg('licence_id', (int)$r->id, get_permalink());
                  $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/panier/');
                  $order_id = isset($r->order_id) ? (int) $r->order_id : 0;
                  echo ufscsn_render_actions((int)$r->id, $stat, !empty($r->is_included), $edit_url, $cart_url, $order_id);
                ?>
              </td>
            </tr>
            <tr class="ufsc-row-details" data-licence-id="<?php echo (int)$r->id; ?>">
              <td colspan="6">
                <?php
                  echo ufscsn_render_actions((int)$r->id, $stat, !empty($r->is_included), $edit_url, $cart_url, $order_id);
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php 
        $pages = max(1, ceil($total / $per_page)); 
        if ($pages>1): ?>
          <div class="ufsc-pagination">
            <?php for($i=1;$i<=$pages;$i++): 
              $url = add_query_arg(['section'=>'licences','p'=>$i,'pp'=>$per_page,'statut'=>$statut,'s'=>$s], get_permalink());
              ?>
              <a class="ufsc-page <?php echo $i===$page?'is-active':''; ?>" href="<?php echo esc_url($url); ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
          </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
    <?php
}
