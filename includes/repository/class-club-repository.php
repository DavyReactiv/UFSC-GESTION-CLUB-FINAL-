<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class UFSC_Club_Repository
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'ufsc_clubs';
    }

    public function get_name(int $club_id): ?string
    {
        if ($club_id <= 0) {
            return null;
        }

        $name = $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT nom FROM {$this->table} WHERE id = %d", $club_id)
        );

        return $name ?: null;
    }

    public function has_remaining_included_quota(int $club_id): bool
    {
        if ($club_id <= 0) {
            return false;
        }

        if (function_exists('ufsc_has_included_quota')) {
            return ufsc_has_included_quota($club_id);
        }

        $quota = (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT quota_licences FROM {$this->table} WHERE id = %d", $club_id)
        );

        $licences_table = $this->wpdb->prefix . 'ufsc_licences';
        $used = (int) $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT COUNT(*) FROM {$licences_table} WHERE club_id = %d AND is_included = 1", $club_id)
        );

        return $quota > $used;
    }
}
