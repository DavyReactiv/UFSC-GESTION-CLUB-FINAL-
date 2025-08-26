<?php
if (!defined('ABSPATH')) exit;

/**
 * Capture le POST du formulaire licence en frontend (évite /wp-admin/admin-post.php).
 * Crée/MAJ le brouillon puis redirige vers le router ?ufsc_pay_licence=ID
 */
/* Legacy redirect disabled by v20.3 fixes */
add_action('template_redirect', function(){
    if ( ! apply_filters('ufsc_enable_legacy_redirects', false) ) { return; }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (empty($_POST['action']) || $_POST['action'] !== 'ufsc_submit_licence') return;

    if (!ufsc_check_admin_nonce('ufsc_add_licence_nonce', 'ufsc_nonce', false)){
        wp_die(__('Jeton de sécurité invalide.','plugin-ufsc-gestion-club-13072025'));
    }
    if (!is_user_logged_in()){ wp_safe_redirect( wp_login_url( get_permalink() ) ); exit; }

    global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
    $club_id = (int) $wpdb->get_var($wpdb->prepare('SELECT club_id FROM '.$wpdb->prefix.'ufsc_user_clubs WHERE user_id=%d LIMIT 1', get_current_user_id()));

    $fields = array('nom','prenom','date_naissance','sexe','lieu_naissance','email','adresse','suite_adresse','code_postal','ville','tel_mobile','identifiant_laposte','profession','fonction','competition','licence_delegataire','numero_licence_delegataire','diffusion_image','infos_fsasptt','infos_asptt','infos_cr','infos_partenaires','honorabilite','assurance_dommage_corporel','assurance_assistance','ufsc_rules_ack');
    $data = array();
    foreach ($fields as $f){ $v = isset($_POST[$f])?wp_unslash($_POST[$f]):''; $data[$f] = is_string($v)?sanitize_text_field($v):$v; }
    $data['email'] = sanitize_email($data['email']);

    $licence_id = isset($_POST['licence_id'])?absint($_POST['licence_id']):0;
    if ($licence_id){
        $wpdb->update($t, array_merge($data, array('statut'=>'en_attente')), array('id'=>$licence_id,'club_id'=>$club_id));
    } else {
        $wpdb->insert($t, array_merge($data, array('club_id'=>$club_id,'statut'=>'en_attente','date_creation'=>current_time('mysql'))));
        $licence_id = (int) $wpdb->insert_id;
    }

    // Redirige vers le routeur qui ajoute au panier côté front
    wp_safe_redirect( add_query_arg('ufsc_pay_licence', $licence_id, home_url('/')) ); exit;
});
