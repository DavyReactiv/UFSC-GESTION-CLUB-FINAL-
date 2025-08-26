<?php
if (!defined('ABSPATH')) exit;
function ufsc_run_migrations(){
    global $wpdb; $cur=get_option('ufsc_db_version','0'); $t=$wpdb->prefix.'ufsc_licences';
    if (version_compare($cur,'1','<')){
        $cols=array("fonction VARCHAR(40) NULL","diffusion_image TINYINT(1) NOT NULL DEFAULT 0","infos_fsasptt TINYINT(1) NOT NULL DEFAULT 0","infos_asptt TINYINT(1) NOT NULL DEFAULT 0","infos_cr TINYINT(1) NOT NULL DEFAULT 0","infos_partenaires TINYINT(1) NOT NULL DEFAULT 0","honorabilite TINYINT(1) NOT NULL DEFAULT 0","assurance_dommage_corporel TINYINT(1) NOT NULL DEFAULT 0","assurance_assistance TINYINT(1) NOT NULL DEFAULT 0","date_achat DATETIME NULL","order_id BIGINT(20) UNSIGNED NULL DEFAULT 0","date_expiration DATE NULL");
        foreach($cols as $c){ $name=preg_replace('/\s.+$/','',$c); if(!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t} LIKE %s",$name))){ $wpdb->query("ALTER TABLE {$t} ADD COLUMN {$c}"); } }
        $wpdb->query("ALTER TABLE {$t} ADD INDEX idx_club (club_id)");
        $wpdb->query("ALTER TABLE {$t} ADD INDEX idx_statut (statut)");
        $wpdb->query("ALTER TABLE {$t} ADD INDEX idx_email (email(50))");
        $wpdb->query("ALTER TABLE {$t} ADD INDEX idx_date_achat (date_achat)");
        $wpdb->query("ALTER TABLE {$t} ADD INDEX idx_expiration (date_expiration)");
        update_option('ufsc_db_version','1');
    }
}
add_action('admin_init','ufsc_run_migrations');