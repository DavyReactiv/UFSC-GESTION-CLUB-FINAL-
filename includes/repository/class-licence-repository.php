<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class UFSC_Licence_Repository
{
    private wpdb $wpdb;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'ufsc_licences';
    }

    public function insert(array $data): int
    {
        // ensure versioning fields exist
        $data['version']    = $data['version'] ?? 1;
        $data['updated_at'] = $data['updated_at'] ?? current_time('mysql');

        $this->wpdb->insert($this->table, $data);
        $id = (int) $this->wpdb->insert_id;

        // prime cache
        if ($id > 0) {
            wp_cache_set($id, (object) array_merge(['id' => $id], $data), 'ufsc_licences');
        }

        return $id;
    }

    public function get_status(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }

        return $this->wpdb->get_var(
            $this->wpdb->prepare("SELECT statut FROM {$this->table} WHERE id = %d", $id)
        ) ?: null;
    }

    public function update(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        $current = $this->get_by_id($id);
        if (!$current) {
            return false;
        }

        $current_version   = (int) ($current->version ?? 0);
        $data['version']    = $current_version + 1;
        $data['updated_at'] = current_time('mysql');

        $this->wpdb->query('START TRANSACTION');
        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $id, 'version' => $current_version],
            null,
            ['%d', '%d']
        );

        if ($result === false || $result === 0) {
            $this->wpdb->query('ROLLBACK');
            return false;
        }

        $this->wpdb->query('COMMIT');
        wp_cache_delete($id, 'ufsc_licences');
        return true;
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $this->wpdb->query('START TRANSACTION');
        $result = $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
        if ($result === false) {
            $this->wpdb->query('ROLLBACK');
            return false;
        }

        $this->wpdb->query('COMMIT');
        wp_cache_delete($id, 'ufsc_licences');
        return true;
    }

    public function get_by_id(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        $cached = wp_cache_get($id, 'ufsc_licences');
        if (false !== $cached) {
            return $cached;
        }

        $licence = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id)
        );
        if ($licence) {
            wp_cache_set($id, $licence, 'ufsc_licences');
        }
        return $licence;
    }

    /**
     * Update the status of a licence with optimistic locking and logging.
     *
     * @return object|null Refreshed licence entity or null on failure
     */
    public function update_status(int $id, string $status, string $reason = '', string $changed_by = ''): ?object
    {
        if ($id <= 0) {
            return null;
        }

        $current = $this->get_by_id($id);
        if (!$current) {
            return null;
        }

        $current_version = (int) ($current->version ?? 0);
        $data = [
            'statut'     => $status,
            'version'    => $current_version + 1,
            'updated_at' => current_time('mysql'),
        ];

        $this->wpdb->query('START TRANSACTION');
        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $id, 'version' => $current_version],
            ['%s', '%d', '%s'],
            ['%d', '%d']
        );

        if ($result === false || $result === 0) {
            $this->wpdb->query('ROLLBACK');
            return null;
        }

        // Log the status change
        $log_data = [
            'licence_id' => $id,
            'new_status' => $status,
            'reason'     => $reason,
            'changed_by' => $changed_by,
            'timestamp'  => current_time('mysql'),
        ];
        update_option('ufsc_licence_status_log_' . $id . '_' . time(), $log_data);

        $this->wpdb->query('COMMIT');
        wp_cache_delete($id, 'ufsc_licences');

        return $this->get_by_id($id);
    }

    public function find_duplicate(array $data): int|false
    {
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['date_naissance']) || empty($data['club_id'])) {
            return false;
        }

        $existing = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->table} WHERE nom = %s AND prenom = %s AND date_naissance = %s AND club_id = %d AND statut != 'refuse'",
                sanitize_text_field($data['nom']),
                sanitize_text_field($data['prenom']),
                sanitize_text_field($data['date_naissance']),
                (int) $data['club_id']
            )
        );

        return $existing ? (int) $existing->id : false;
    }

    public function get_all_by_filters(array $filters = []): array
    {
        if (!isset($filters['club_id']) || (int) $filters['club_id'] <= 0) {
            return [];
        }

        $where = ['1=1'];
        $params = [];

        $where[] = 'l.club_id = %d';
        $params[] = (int) $filters['club_id'];

        if (!empty($filters['search'])) {
            $where[] = '(l.nom LIKE %s OR l.prenom LIKE %s OR l.email LIKE %s)';
            $search = '%' . sanitize_text_field($filters['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $where_clause = implode(' AND ', $where);
        $clubs_table = $this->wpdb->prefix . 'ufsc_clubs';
        $query = "SELECT l.*, c.nom as club_nom
                  FROM {$this->table} l
                  LEFT JOIN {$clubs_table} c ON l.club_id = c.id
                  WHERE {$where_clause}
                  ORDER BY l.date_inscription DESC";

        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($query, ...$params));
        }

        return $this->wpdb->get_results($query);
    }
}
