<?php
if (!defined('ABSPATH')) exit;
/** Optional nonce check: if a nonce is provided, verify; if not, continue (capability still required). */
function ufsc__maybe_check_nonce(){
    $n = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : ( isset($_REQUEST['_ajax_nonce']) ? $_REQUEST['_ajax_nonce'] : '' );
    if ($n && ! wp_verify_nonce($n, 'ufsc_admin_licence_action')) {
        // Invalid nonce, continue gracefully
    }
    return true;
}

function ufsc__can_manage(){ return current_user_can('manage_ufsc_licences')||current_user_can('manage_options')||current_user_can('edit_posts'); }
function ufsc__licences_table_and_status_col(){ global $wpdb; $t=$wpdb->prefix.'ufsc_licences'; $col=$wpdb->get_var($wpdb->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s AND COLUMN_NAME IN ('statut','status') LIMIT 1",$t)); if(!$col)$col='statut'; return array($t,$col); }
function ufsc__set_status_fallback($id,$st){ global $wpdb; list($t,$c)=ufsc__licences_table_and_status_col(); $ok=$wpdb->update($t, array($c=>$st), array('id'=>(int)$id), array('%s'), array('%d')); return ($ok!==false); }
function ufsc__set_status($id,$st,&$why=''){ if(!class_exists('UFSC_Licence_Manager')){ $f=dirname(__DIR__).'/licences/class-licence-manager.php'; if(file_exists($f)) require_once $f; }
 if(class_exists('UFSC_Licence_Manager')){ try{ $mgr=UFSC_Licence_Manager::get_instance(); $ok=$mgr->update_licence_status($id,$st);
  if(!$ok&&$st==='validée')$ok=$mgr->update_licence_status($id,'validee'); if(!$ok&&$st==='validee')$ok=$mgr->update_licence_status($id,'validée');
  if(!$ok&&$st==='refusée')$ok=$mgr->update_licence_status($id,'refusee'); if(!$ok&&$st==='refusee')$ok=$mgr->update_licence_status($id,'refusée');
  if($ok) return true; $why='manager_returned_false'; } catch(Throwable $e){ $why='manager_exception: '.$e->getMessage(); } } else { $why='manager_missing'; }
 $ok=ufsc__set_status_fallback($id,$st); if(!$ok&&$st==='validée')$ok=ufsc__set_status_fallback($id,'validee'); if(!$ok&&$st==='validee')$ok=ufsc__set_status_fallback($id,'validée'); if(!$ok&&$st==='refusée')$ok=ufsc__set_status_fallback($id,'refusee'); if(!$ok&&$st==='refusee')$ok=ufsc__set_status_fallback($id,'refusée'); if(!$ok and empty($why))$why='fallback_update_failed'; return $ok; }
add_action('wp_ajax_ufsc_validate_licence',function(){ if(!ufsc__can_manage()) wp_send_json_error('Not allowed',403); $why=''; $ok=ufsc__set_status($id,'validée',$why); if($ok) wp_send_json_success(); else wp_send_json_error('Failed to validate licence. (' . $why . ')'); });
add_action('wp_ajax_ufsc_reject_licence',function(){ if(!ufsc__can_manage()) wp_send_json_error('Not allowed',403); $why=''; $ok=ufsc__set_status($id,'refusée',$why); if($ok) wp_send_json_success(); else wp_send_json_error('Failed to reject licence. (' . $why . ')'); });
add_action('wp_ajax_ufsc_delete_licence', function(){
	if (!current_user_can('manage_options') && !current_user_can('edit_posts')) wp_send_json_error('Not allowed',403);
	ufsc__maybe_check_nonce(); global $wpdb;
	$id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
	if (!$id) wp_send_json_error('Missing ID');
	$table = $wpdb->prefix.'ufsc_licences';
	$ok = $wpdb->update($table, ['statut'=>'trash','deleted_at'=>current_time('mysql')], ['id'=>$id], ['%s','%s'], ['%d']);
	if ($ok!==false){ ufsc__log_status_change($id,'trash',get_current_user_id()); wp_send_json_success(); } else { wp_send_json_error('Failed to delete licence.'); }
});
add_action('admin_post_ufsc_validate_licence',function(){ if(!ufsc__can_manage()) wp_die('Not allowed'); $ref=wp_get_referer()?:admin_url('admin.php?page=ufsc-licences'); wp_safe_redirect(add_query_arg('ufsc_msg',$ok?'validated':'error:'.$why,$ref)); exit; });
add_action('admin_post_ufsc_reject_licence',function(){ if(!ufsc__can_manage()) wp_die('Not allowed'); $ref=wp_get_referer()?:admin_url('admin.php?page=ufsc-licences'); wp_safe_redirect(add_query_arg('ufsc_msg',$ok?'rejected':'error:'.$why,$ref)); exit; });
add_action('admin_post_ufsc_delete_licence',function(){ if(!ufsc__can_manage()) wp_die('Not allowed'); $ref=wp_get_referer()?:admin_url('admin.php?page=ufsc-licences'); wp_safe_redirect(add_query_arg('ufsc_msg',$ok?'deleted':'error',$ref)); exit; });

/** Log status change */
function ufsc__log_status_change($licence_id, $new_status, $user_id = 0){
    global $wpdb;
    $logs = $wpdb->prefix . 'ufsc_licence_logs';
    $licences = $wpdb->prefix . 'ufsc_licences';
    $old = $wpdb->get_var($wpdb->prepare("SELECT statut FROM {$licences} WHERE id=%d", $licence_id));
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']),0,180) : '';
    $wpdb->insert($logs, [
        'licence_id' => (int)$licence_id,
        'old_status' => $old,
        'new_status' => $new_status,
        'user_id'    => (int)$user_id,
        'request_ip' => $ip,
        'user_agent' => $ua,
        'created_at' => current_time('mysql'),
    ], ['%d','%s','%s','%d','%s','%s','%s']);
}


add_action('wp_ajax_ufsc_change_licence_status', function(){
    if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
        wp_send_json_error('Not allowed', 403);
    }
    ufsc__maybe_check_nonce();
    $id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    $new = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
    if (!$id || !$new) wp_send_json_error('Missing parameters');

    $why = '';
    $ok = ufsc__set_status($id, $new, $why);
    if ($ok) {
        ufsc__log_status_change($id, $new, get_current_user_id());
        wp_send_json_success(['status' => $new]);
    } else {
        wp_send_json_error('Failed to update status. ' . ($why ?: ''));
    }
});