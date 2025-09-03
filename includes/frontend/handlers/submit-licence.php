<?php
if (!defined('ABSPATH')) exit;

function ufsc_handle_save_licence(){
    if (!isset($_POST['action']) || $_POST['action']!=='ufsc_submit_licence') return;

    $redirect = wp_get_referer() ?: home_url('/');

    $nonce = isset($_POST['ufsc_nonce']) ? sanitize_text_field(wp_unslash($_POST['ufsc_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'ufsc_add_licence_nonce')){
        wp_safe_redirect($redirect);
        exit;
    }

    if (!is_user_logged_in() || !current_user_can('ufsc_manage_own')){
        wp_safe_redirect(wp_login_url($redirect));
        exit;
    }

    $fields = array('nom','prenom','date_naissance','sexe','lieu_naissance','email','adresse','suite_adresse','code_postal','ville','tel_mobile','identifiant_laposte','identifiant_laposte_flag','profession','fonction','competition','licence_delegataire','numero_licence_delegataire','diffusion_image','infos_fsasptt','infos_asptt','infos_cr','infos_partenaires','honorabilite','assurance_dommage_corporel','assurance_assistance','ufsc_rules_ack');
    $data = array(); foreach($fields as $f){ $v = isset($_POST[$f])?wp_unslash($_POST[$f]):''; if($f==='email') $data[$f]=sanitize_email($v); elseif($f==='tel_mobile') $data[$f]=preg_replace('/[^0-9\s\.\-\+]/','',$v); else $data[$f]=sanitize_text_field($v); }

    if (!isset($data['identifiant_laposte_flag']) || $data['identifiant_laposte_flag'] === '') {
        if (isset($data['identifiant_laposte']) && in_array($data['identifiant_laposte'], array('0','1'), true)) {
            $data['identifiant_laposte_flag'] = (int) $data['identifiant_laposte'];
            $data['identifiant_laposte'] = '';
        } else {
            $data['identifiant_laposte_flag'] = empty($data['identifiant_laposte']) ? 0 : 1;
        }
    }
    if (empty($data['identifiant_laposte_flag'])) {
        $data['identifiant_laposte'] = '';
    }

    global $wpdb; $club_id = 0; $club_id = (int) $wpdb->get_var($wpdb->prepare('SELECT club_id FROM '.$wpdb->prefix.'ufsc_user_clubs WHERE user_id=%d LIMIT 1', get_current_user_id()));
    if (!$club_id) { wp_safe_redirect($redirect); exit; }
    $table = $wpdb->prefix.'ufsc_licences';

    // === UFSC quota check: 10 crédits + 3 membres du bureau obligatoires ===
    $bureau_roles = array('president','secretaire','tresorier');
    $is_bureau = in_array($data['fonction'], $bureau_roles, true);
    $bureau_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE club_id=%d AND fonction IN ('president','secretaire','tresorier')", $club_id));
    $total_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE club_id=%d", $club_id));
    $included_total = (int) get_option('ufsc_included_quota_per_pack', 10);
    $included_remaining = max(0, $included_total - $total_count);
    $is_included = $included_remaining > 0 ? 1 : 0;
    if ($bureau_count < 3 && !$is_bureau){ wp_safe_redirect($redirect); exit; }

    // Create / update in DB with statut en_attente unless already validated
    $licence_id_edit = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if ($licence_id_edit){
        $current_statut = $wpdb->get_var($wpdb->prepare("SELECT statut FROM {$table} WHERE id=%d AND club_id=%d", $licence_id_edit, $club_id));
        if ($current_statut === null){ wp_safe_redirect($redirect); exit; }
        if ($current_statut === 'validee'){
            $wpdb->update($table, $data, array('id'=>$licence_id_edit, 'club_id'=>$club_id));
        } else {
            $wpdb->update($table, array_merge($data, array('statut'=>'en_attente')), array('id'=>$licence_id_edit, 'club_id'=>$club_id));
        }
        $licence_id = $licence_id_edit;
    }
    else {
        $wpdb->insert($table, array_merge($data, array('club_id'=>$club_id,'statut'=>'en_attente','date_creation'=>current_time('mysql'))));
        $licence_id = (int)$wpdb->insert_id;
    }

    // Resolve Licence product ID
    $licence_product_id = 0; $pid1 = absint(get_option('ufsc_wc_individual_licence_product_id', 0));
    if ($pid1) $licence_product_id=$pid1; if (!$licence_product_id){ $csv=get_option('ufsc_wc_license_product_ids',''); if(!empty($csv)){ $parts=array_filter(array_map('absint', array_map('trim', explode(',', $csv)))); if(!empty($parts)) $licence_product_id=(int) array_shift($parts); } }
    if (!$licence_product_id){ $opts=get_option('ufsc_pack_settings',array()); if(!empty($opts['licence_product_id'])) $licence_product_id=(int)$opts['licence_product_id']; }

    if (class_exists('WC') && $licence_product_id){
        if (!WC()->cart){ wc_load_cart(); }
        $cart_item_data = array('ufsc_club_id'=>$club_id,'ufsc_licence_id'=>$licence_id,'ufsc_licence_data'=>$data,'ufsc_is_included'=>$is_included);
        wp_safe_redirect( add_query_arg('ufsc_pay_licence', $licence_id, home_url('/') ) ); exit;
    } else { wp_die(__('Produit Licence non configuré.','plugin-ufsc-gestion-club-13072025')); }
}
add_action('admin_post_nopriv_ufsc_submit_licence','ufsc_handle_save_licence');
add_action('admin_post_ufsc_submit_licence','ufsc_handle_save_licence');
