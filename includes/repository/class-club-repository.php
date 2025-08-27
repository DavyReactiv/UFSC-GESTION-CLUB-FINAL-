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

    /**
     * Search clubs with optional filters and pagination.
     *
     * @param array $args {
     *     @type string $region   Club region.
     *     @type string $statut   Club status.
     *     @type string $keyword  Keyword to search in club name.
     *     @type string $date_from Optional affiliation date start (Y-m-d).
     *     @type string $date_to   Optional affiliation date end (Y-m-d).
     *     @type int    $page     Page number.
     *     @type int    $per_page Items per page.
     * }
     *
     * @return WP_Error|array {
     *     @type array $items    List of clubs.
     *     @type int   $total    Total items matching filters.
     *     @type int   $page     Current page.
     *     @type int   $per_page Items per page.
     * }
     */
    public function search(array $args): WP_Error|array
    {
        $defaults = [
            'region'   => '',
            'statut'   => '',
            'keyword'  => '',
            'date_from'=> '',
            'date_to'  => '',
            'page'     => 1,
            'per_page' => 20,
        ];

        $args = wp_parse_args($args, $defaults);

        if ($args['page'] < 1 || $args['per_page'] < 1) {
            return new WP_Error('invalid_pagination', __('Pagination parameters invalid.', 'plugin-ufsc-gestion-club-13072025'));
        }

        $where  = ['1=1'];
        $params = [];

        if ($args['keyword'] !== '') {
            $where[]  = 'nom LIKE %s';
            $params[] = '%' . $this->wpdb->esc_like($args['keyword']) . '%';
        }

        if ($args['region'] !== '') {
            $where[]  = 'region = %s';
            $params[] = $args['region'];
        }

        if ($args['statut'] !== '') {
            $where[]  = 'statut = %s';
            $params[] = $args['statut'];
        }

        if ($args['date_from'] !== '') {
            $where[]  = 'DATE(date_creation) >= %s';
            $params[] = $args['date_from'];
        }

        if ($args['date_to'] !== '') {
            $where[]  = 'DATE(date_creation) <= %s';
            $params[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where);

        $count_sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$where_clause}";
        $count_query = empty($params)
            ? $this->wpdb->prepare($count_sql)
            : $this->wpdb->prepare($count_sql, ...$params);
        $total = (int) $this->wpdb->get_var($count_query);

        $offset = ((int) $args['page'] - 1) * (int) $args['per_page'];
        $params_with_limit = array_merge($params, [(int) $args['per_page'], (int) $offset]);

        $list_sql = "SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY nom ASC LIMIT %d OFFSET %d";
        $list_query = empty($params)
            ? $this->wpdb->prepare($list_sql, (int) $args['per_page'], (int) $offset)
            : $this->wpdb->prepare($list_sql, ...$params_with_limit);
        $items = $this->wpdb->get_results($list_query);

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => (int) $args['page'],
            'per_page' => (int) $args['per_page'],
        ];
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
