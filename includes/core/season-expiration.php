<?php
if (!defined('ABSPATH')) exit;

add_option('ufsc_current_season', date('Y').'-'.(date('Y')+1), '', 'no');
add_option('ufsc_expire_month', 8);
add_option('ufsc_expire_day', 31);

add_action('init', function(){
    if (!wp_next_scheduled('ufsc_daily_cron')) {
        wp_schedule_event(time()+3600, 'daily', 'ufsc_daily_cron');
    }
});

add_action('admin_init', function(){
    global $wpdb;
    $t = $wpdb->prefix . 'ufsc_licences';
    if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t} LIKE %s", 'date_expiration'))){
        $wpdb->query("ALTER TABLE {$t} ADD COLUMN date_expiration DATE NULL, ADD INDEX (date_expiration)");
    }
});

function ufsc_compute_expiration_date($ts=null){
    $y = (int)date('Y', $ts ?: current_time('timestamp'));
    $m = (int)get_option('ufsc_expire_month', 8);
    $d = (int)get_option('ufsc_expire_day', 31);
    $exp = mktime(0,0,0,$m,$d,$y);
    if (($ts ?: current_time('timestamp')) > $exp){
        $exp = mktime(0,0,0,$m,$d,$y+1);
    }
    return date('Y-m-d', $exp);
}

add_action('woocommerce_order_status_changed', function($order_id, $from, $to, $order){
    if (!in_array($to, array('processing','completed'), true)) return;
    global $wpdb;
    $t = $wpdb->prefix . 'ufsc_licences';
    $date = sanitize_text_field(ufsc_compute_expiration_date());
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$t} SET date_expiration = IFNULL(date_expiration, %s) WHERE order_id = %d",
            $date,
            absint($order_id)
        )
    );
}, 20, 4);

add_action('ufsc_daily_cron', function(){
    global $wpdb;
    $t = $wpdb->prefix . 'ufsc_licences';
    $today = sanitize_text_field(date('Y-m-d', current_time('timestamp')));
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$t} SET statut='expiree' WHERE date_expiration IS NOT NULL AND date_expiration < %s AND statut IN ('validee','validée')",
            $today
        )
    );
});

