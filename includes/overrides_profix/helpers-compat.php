<?php
if (!defined('ABSPATH')) exit;
if (!function_exists('ufsc_get_user_club')){
function ufsc_get_user_club(){
    if (!is_user_logged_in()) return null;
    $user_id=get_current_user_id();
    global $wpdb; $club=null; $link=$wpdb->prefix.'ufsc_user_clubs';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s",$link))==$link){
        $club_id=(int)$wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$link} WHERE user_id=%d ORDER BY is_manager DESC LIMIT 1",$user_id));
        if ($club_id){ $club=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id=%d",$club_id)); }
    }
    if (!$club){
        $club=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE user_id=%d OR owner_id=%d LIMIT 1",$user_id,$user_id));
    }
    return $club;
}}
if (!function_exists('ufsc_resolve_current_club_id')){
function ufsc_resolve_current_club_id(){
    $c=function_exists('ufsc_get_user_club')?ufsc_get_user_club():null;
    if ($c && !empty($c->id)) return (int)$c->id;
    if ($c && !empty($c->club_id)) return (int)$c->club_id;
    $m=(int)get_user_meta(get_current_user_id(),'ufsc_club_id',true); if ($m) return $m;
    global $wpdb; $link=$wpdb->prefix.'ufsc_user_clubs';
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s",$link))==$link){
        $cid=(int)$wpdb->get_var($wpdb->prepare("SELECT club_id FROM {$link} WHERE user_id=%d ORDER BY is_manager DESC LIMIT 1",get_current_user_id()));
        if ($cid) return $cid;
    }
    $cid=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}ufsc_clubs WHERE user_id=%d OR owner_id=%d LIMIT 1",get_current_user_id(),get_current_user_id()));
    if ($cid) return $cid;
    if (current_user_can('manage_options')){
        $cid=(int)$wpdb->get_var("SELECT id FROM {$wpdb->prefix}ufsc_clubs ORDER BY id ASC LIMIT 1"); if ($cid) return $cid;
    }
    return 0;
}}