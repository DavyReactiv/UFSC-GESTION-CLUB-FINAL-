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

    /**
     * Search licences with filters and pagination.
     *
     * @param array $args {
     *     @type int    $club_id  Club ID filter.
     *     @type string $region   Region filter.
     *     @type string $statut   Status filter.
     *     @type string $keyword  Search keyword for name, firstname or email.
     *     @type int    $page     Page number.
     *     @type int    $per_page Items per page.
     * }
     *
     * @return WP_Error|array {
     *     @type array $items    List of licences.
     *     @type int   $total    Total items matching filters.
     *     @type int   $page     Current page.
     *     @type int   $per_page Items per page.
     * }
     */
    public function search(array $args): WP_Error|array
    {
        $defaults = [
            'club_id'  => 0,
            'region'   => '',
            'statut'   => '',
            'keyword'  => '',
            'page'     => 1,
            'per_page' => 20,
        ];

        $args = wp_parse_args($args, $defaults);

        if ($args['page'] < 1 || $args['per_page'] < 1) {
            return new WP_Error('invalid_pagination', __('Pagination parameters invalid.', 'plugin-ufsc-gestion-club-13072025'));
        }

        $where  = ['1=1'];
        $params = [];

        if ($args['club_id'] > 0) {
            $where[]  = 'l.club_id = %d';
            $params[] = (int) $args['club_id'];
        }

        if ($args['region'] !== '') {
            $where[]  = 'l.region = %s';
            $params[] = $args['region'];
        }

        if ($args['statut'] !== '') {
            $where[]  = 'l.statut = %s';
            $params[] = $args['statut'];
        }

        if ($args['keyword'] !== '') {
            $like = '%' . $this->wpdb->esc_like($args['keyword']) . '%';
            $where[] = '(l.nom LIKE %s OR l.prenom LIKE %s OR l.email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $where_clause = implode(' AND ', $where);
        $clubs_table = $this->wpdb->prefix . 'ufsc_clubs';

        $count_sql   = "SELECT COUNT(*) FROM {$this->table} l LEFT JOIN {$clubs_table} c ON l.club_id = c.id WHERE {$where_clause}";
        $count_query = empty($params)
            ? $count_sql
            : $this->wpdb->prepare($count_sql, ...$params);
        $total       = (int) $this->wpdb->get_var($count_query);

        $offset           = ((int) $args['page'] - 1) * (int) $args['per_page'];
        $params_with_limit = array_merge($params, [(int) $args['per_page'], (int) $offset]);

        $list_sql   = "SELECT l.*, c.nom as club FROM {$this->table} l LEFT JOIN {$clubs_table} c ON l.club_id = c.id WHERE {$where_clause} ORDER BY l.date_inscription DESC LIMIT %d OFFSET %d";
        $list_query = empty($params)
            ? $this->wpdb->prepare($list_sql, (int) $args['per_page'], (int) $offset)
            : $this->wpdb->prepare($list_sql, ...$params_with_limit);
        $items      = $this->wpdb->get_results($list_query, ARRAY_A);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => (int) $args['page'],
            'per_page' => (int) $args['per_page'],
        ];
    }

    public function get_all_by_filters(array $filters = []): array
    {
        $filters = array_merge($filters, [
            'page'     => 1,
            'per_page' => PHP_INT_MAX,
        ]);

        $result = $this->search($filters);

        if (is_wp_error($result)) {
            return [];
        }

        return $result['items'];
    }
}

