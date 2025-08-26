<?php
if (!defined('ABSPATH')) exit;

/**
 * Robust override for the club licences page.
 * Works with tables: wp_ufsc_licences OR wp_ufsc_licenses
 * Resolves club_id via ufsc_get_user_club(), mapping table fallback, or shortcode/GET override.
 */
function ufsc_register_club_licences_override(){
    remove_shortcode('ufsc_club_licences');
    remove_shortcode('ufsc_club_licenses');
    add_shortcode('ufsc_club_licences', 'ufsc_club_licences_render_override');
    add_shortcode('ufsc_club_licenses', 'ufsc_club_licences_render_override'); // alias
}
add_action('plugins_loaded', 'ufsc_register_club_licences_override', 9999);
add_action('init', 'ufsc_register_club_licences_override', 9999);

function ufsc__detect_licence_table(){
    global $wpdb;
    $candidates = array($wpdb->prefix.'ufsc_licences', $wpdb->prefix.'ufsc_licenses');
    foreach ($candidates as $t){
        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $t));
        if ($exists === $t) return $t;
    }
    // fallback to the first name
    return $candidates[0];
}

if (!function_exists('ufsc__resolve_club_id')) {
function ufsc__resolve_club_id($atts){
    // 1) shortcode attr has priority (for debug) e.g. [ufsc_club_licences club_id="123"]
    if (!empty($atts['club_id'])) return (int) $atts['club_id'];
    // 2) GET override (for debug)
    if (!empty($_GET['club_id'])) return (int) $_GET['club_id'];

    // 3) official helper
    if (function_exists('ufsc_get_user_club')){
        $club = ufsc_get_user_club();
        if ($club && !empty($club->id)) return (int) $club->id;
        if (is_array($club) && !empty($club['id'])) return (int) $club['id'];
    }

    // 4) fallback: mapping table
    if (is_user_logged_in()){
        $user_id = get_current_user_id();
        global $wpdb;
        $map = $wpdb->prefix.'ufsc_user_clubs';
        $club_id = (int) $wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$map} WHERE user_id=%d ORDER BY id DESC LIMIT 1", $user_id));
        if ($club_id) return $club_id;
    }

    return 0;
}

function ufsc_club_licences_render_override($atts = array()){
    $atts = shortcode_atts(array('per_page'=>50, 'club_id'=>0), $atts, 'ufsc_club_licences');

    $is_logged = is_user_logged_in();
    $club_id   = ufsc__resolve_club_id($atts);
    $table     = ufsc__detect_licence_table();

    // Form page URL
    $form_url = '#';
    if (function_exists('ufsc_get_safe_page_url')){
        $pg = ufsc_get_safe_page_url('ajouter_licencie');
        if (!empty($pg['url'])) $form_url = $pg['url'];
    }

    global $wpdb;
    $rows = array();
    $sql_used = '';

    if ($is_logged && $club_id){
        // Primary by date_creation
        $sql_used = "date_creation";
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, nom, prenom, email,
                    COALESCE(statut, status, '') AS statut,
                    IFNULL(date_creation, NOW()) AS date_creation
             FROM {$table}
             WHERE club_id=%d
             ORDER BY date_creation DESC, id DESC
             LIMIT %d",
             $club_id, (int)$atts['per_page']
        ));
        if (empty($rows)){
            // Fallback by id
            $sql_used = "id";
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, nom, prenom, email,
                        COALESCE(statut, status, '') AS statut,
                        IFNULL(date_creation, NOW()) AS date_creation
                 FROM {$table}
                 WHERE club_id=%d
                 ORDER BY id DESC
                 LIMIT %d",
                 $club_id, (int)$atts['per_page']
            ));
        }
    }

    ob_start(); ?>

    <div class="ufsc-card" style="margin-bottom:18px">
      <h3>Ajouter une licence</h3>
      <?php if ($form_url && $form_url !== '#'): ?>
        <a class="ufsc-btn" href="<?php echo esc_url($form_url); ?>">Nouvelle licence</a>
      <?php else: ?>
        <div class="ufsc-banner">Page du formulaire non configurée.</div>
      <?php endif; ?>
    </div>

    <div class="ufsc-card">
      <h3>Licences du club</h3>
      <?php if (!$is_logged): ?>
        <p>Veuillez vous connecter à votre <strong>compte club</strong> pour voir vos licences.</p>
      <?php elseif (!$club_id): ?>
        <p>Club introuvable pour ce compte.</p>
      <?php elseif (empty($rows)): ?>
        <p>Aucune licence trouvée pour ce club.</p>
      <?php else: ?>
      <table class="ufsc-table">
        <thead>
          <tr>
            <th>NOM</th><th>PRÉNOM</th><th>EMAIL</th><th>STATUT</th><th>DATE</th><th>ACTIONS</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r):
            $st = trim(isset($r->statut) ? $r->statut : '');
            $is_draft = ($st === '' || strtolower($st) === 'brouillon');
            $date = esc_html( mysql2date( get_option('date_format'), $r->date_creation ) );
            $edit_url = $form_url && $form_url !== '#' ? add_query_arg(array('licence_id'=>(int)$r->id), $form_url) : '#';
        ?>
          <tr>
            <td><?php echo esc_html($r->nom); ?></td>
            <td><?php echo esc_html($r->prenom); ?></td>
            <td><?php echo esc_html($r->email); ?></td>
            <td><?php echo $is_draft ? 'brouillon' : esc_html($st); ?></td>
            <td><?php echo $date; ?></td>
            <td>
              <div class="ufsc-actions-inline">
                <?php if ($is_draft): ?>
                  <a href="<?php echo esc_url($edit_url); ?>" class="ufsc-chip">Modifier</a>
                  <button class="ufsc-chip ufsc-chip-danger ufsc-delete-draft" data-licence-id="<?php echo (int)$r->id; ?>">Supprimer</button>
                <?php else: ?>
                  <span class="ufsc-chip">—</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>

      <?php if ( current_user_can('manage_options') ) : ?>
        <div class="ufsc-banner" style="margin-top:12px">
          <strong>Diagnostic (admin uniquement) :</strong>
          club_id=<?php echo (int)$club_id; ?> — table=<code><?php echo esc_html($table); ?></code> —
          lignes=<?php echo isset($rows) ? count($rows) : 0; ?> — tri=<code><?php echo esc_html($sql_used); ?></code>
        </div>
      <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
}

}
