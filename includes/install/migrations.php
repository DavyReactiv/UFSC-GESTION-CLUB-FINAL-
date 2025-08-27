<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('ufsc_run_migrations')) {
function ufsc_run_migrations() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $dbv_opt = 'ufsc_db_schema_version';
    $current = get_option($dbv_opt, '');
    $target = '2.0.8-logo-fields';

    // Always ensure columns/tables exist (idempotent)
    $licences_table = $wpdb->prefix . 'ufsc_licences';

    // Add role column if missing
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$licences_table} LIKE %s", 'role'));
    if (!$col) {
        $wpdb->query("ALTER TABLE {$licences_table} ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'adherent' AFTER statut");
    }

    // Add deleted_at column if missing (for corbeille/soft delete)
    $col_del = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$licences_table} LIKE %s", 'deleted_at'));
    if (!$col_del) {
        $wpdb->query("ALTER TABLE {$licences_table} ADD COLUMN deleted_at datetime NULL DEFAULT NULL AFTER role");
    }

    // Add payment_status column if missing
    $col_pay = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$licences_table} LIKE %s", 'payment_status'));
    if (!$col_pay) {

        $wpdb->query("ALTER TABLE {$licences_table} ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER order_id");

        $wpdb->query("ALTER TABLE {$licences_table} ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'pending'");

    }

    // Add helpful indexes
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$licences_table}", ARRAY_A);
    $have_idx = function($name) use ($indexes) {
        foreach ($indexes as $idx) { if (!empty($idx['Key_name']) && $idx['Key_name'] === $name) return true; }
        return false;
    };
    if (!$have_idx('idx_club_status')) {
        $wpdb->query("ALTER TABLE {$licences_table} ADD INDEX idx_club_status (club_id, statut)");
    }
    if (!$have_idx('idx_role')) {
        $wpdb->query("ALTER TABLE {$licences_table} ADD INDEX idx_role (role)");
    }
    if (!$have_idx('idx_created')) {
        $wpdb->query("ALTER TABLE {$licences_table} ADD INDEX idx_created (date_creation)");
    }

    // Ensure club logo columns exist
    $clubs_table = $wpdb->prefix . 'ufsc_clubs';
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$clubs_table} LIKE %s", 'logo_attachment_id'));
    if (!$col) {
        $wpdb->query("ALTER TABLE {$clubs_table} ADD COLUMN logo_attachment_id BIGINT UNSIGNED NULL");
    }
    $col = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$clubs_table} LIKE %s", 'logo_url'));
    if (!$col) {
        $wpdb->query("ALTER TABLE {$clubs_table} ADD COLUMN logo_url VARCHAR(255) NULL");
    }

    // Logs table
    $logs_table = $wpdb->prefix . 'ufsc_licence_logs';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $logs_table)) === $logs_table;
    if (!$exists) {
        $sql = "CREATE TABLE {$logs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            licence_id bigint(20) unsigned NOT NULL,
            old_status varchar(50) NULL,
            new_status varchar(50) NOT NULL,
            user_id bigint(20) unsigned NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            request_ip varchar(64) NULL,
            user_agent varchar(191) NULL,
            PRIMARY KEY (id),
            KEY licence_id (licence_id),
            KEY new_status (new_status),
            KEY created_at (created_at)
        ) {$charset};";
        $wpdb->query($sql);
    }

    if ($current !== $target) {
        update_option($dbv_opt, $target, true);
    }
}
}
