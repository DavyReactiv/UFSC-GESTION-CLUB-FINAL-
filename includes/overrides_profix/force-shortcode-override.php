<?php
if (!defined('ABSPATH')) exit;
add_action('init', function(){
  remove_shortcode('ufsc_club_licences');
  add_shortcode('ufsc_club_licences', function($atts=array()){
    $atts=shortcode_atts(array('per_page'=>50),$atts,'ufsc_club_licences');
    $club_id=function_exists('ufsc_resolve_current_club_id')?ufsc_resolve_current_club_id():0;
    if(!$club_id) return '<div class="ufsc-alert">'.esc_html__('Connexion club requise.','plugin-ufsc-gestion-club-13072025').'</div>';
    global $wpdb; $t=$wpdb->prefix.'ufsc_licences';
    $rows=$wpdb->get_results($wpdb->prepare(
      "SELECT id,nom,prenom,email,COALESCE(statut,status,'') AS statut,IFNULL(date_creation,NOW()) AS date_creation
       FROM {$t}
       WHERE (club_id=%d)
          OR (club_id IS NULL AND (created_by=%d OR user_id=%d))
          OR (club_id=0 AND (created_by=%d OR user_id=%d))
       ORDER BY id DESC LIMIT %d",
      $club_id, get_current_user_id(), get_current_user_id(), get_current_user_id(), get_current_user_id(), (int)$atts['per_page']
    ));
    ob_start(); ?>
    <div class="ufsc-licenses-list">
      <table class="ufsc-table" style="width:100%;border-collapse:collapse">
        <thead><tr><th>#</th><th><?php _e('Nom','plugin-ufsc-gestion-club-13072025'); ?></th><th><?php _e('Prénom','plugin-ufsc-gestion-club-13072025'); ?></th><th>Email</th><th><?php _e('Statut','plugin-ufsc-gestion-club-13072025'); ?></th><th><?php _e('Actions','plugin-ufsc-gestion-club-13072025'); ?></th></tr></thead>
        <tbody>
        <?php if($rows): foreach($rows as $r): $is_draft=in_array(strtolower(trim($r->statut)),array('brouillon','draft','')); ?>
          <tr>
            <td><?php echo (int)$r->id; ?></td>
            <td class="ufsc-text-ellipsis" title="<?php echo esc_attr($r->nom); ?>"><?php echo esc_html($r->nom); ?></td>
            <td class="ufsc-text-ellipsis" title="<?php echo esc_attr($r->prenom); ?>"><?php echo esc_html($r->prenom); ?></td>
            <td class="ufsc-text-ellipsis" title="<?php echo esc_attr($r->email); ?>"><?php echo esc_html($r->email); ?></td>
            <td><?php echo $is_draft?'<span class="ufsc-badge ufsc-badge--pending">Brouillon</span>':'<span class="ufsc-badge ufsc-badge--ok">Validée</span>'; ?></td>
            <td>
            <?php if($is_draft): ?>
              <button class="button ufsc-pay-licence" data-licence-id="<?php echo (int)$r->id; ?>"><?php _e('Envoyer au paiement','plugin-ufsc-gestion-club-13072025'); ?></button>
              <button class="button ufsc-delete-draft" data-licence-id="<?php echo (int)$r->id; ?>"><?php _e('Supprimer brouillon','plugin-ufsc-gestion-club-13072025'); ?></button>
            <?php else: ?>
              <span style="opacity:.65">—</span>
            <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" style="padding:12px;text-align:center;color:#666"><?php _e('Aucune licence trouvée pour ce club.','plugin-ufsc-gestion-club-13072025'); ?></td></tr>
        <?php endif; ?>
        </tbody>
      </table>
      <?php if(current_user_can('manage_options')): ?><div style="margin-top:8px;font-size:12px;color:#666">Diagnostic: club_id=<?php echo (int)$club_id; ?></div><?php endif; ?>
    </div>
    <?php return ob_get_clean();
  });
},99999);