<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository for UFSC licences operations.
 */
class UFSC_Licenses_Repository {
    /** @var wpdb */
    private $wpdb;

    /** @var string */
    private $table;

    /** @var string */
    private $clubs_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb       = $wpdb;
        $this->table      = $wpdb->prefix . 'ufsc_licences';
        $this->clubs_table = $wpdb->prefix . 'ufsc_clubs';
        require_once plugin_dir_path(__FILE__) . 'class-licence-filters.php';
    }

    /**
     * Fetch licences list with optional filters and pagination.
     *
     * @param array $args Filters including per_page and page.
     * @return array { data, total_items, per_page, current_page }
     */
    public function get_list($args = []) {
        $defaults = [
            'page'      => 1,
            'per_page'  => 20,
        ];
        $filters = array_merge($defaults, (array)$args);

        $where_data  = UFSC_Licence_Filters::build_where_clause($filters);
        $where       = $where_data['where_clause'];
        $params      = $where_data['params'];

        // Count
        $count_sql = "SELECT COUNT(*) FROM {$this->table} l LEFT JOIN {$this->clubs_table} c ON l.club_id = c.id WHERE {$where}";
        $prepared_count = empty($params) ? $this->wpdb->prepare($count_sql) : $this->wpdb->prepare($count_sql, $params);
        $total_items    = (int)$this->wpdb->get_var($prepared_count);

        // Pagination
        $offset = ((int)$filters['page'] - 1) * (int)$filters['per_page'];
        $params[] = (int)$filters['per_page'];
        $params[] = (int)$offset;

        $list_sql = "SELECT l.*, c.nom as club_nom FROM {$this->table} l LEFT JOIN {$this->clubs_table} c ON l.club_id = c.id WHERE {$where} ORDER BY l.date_inscription DESC LIMIT %d OFFSET %d";
        $prepared_list = $this->wpdb->prepare($list_sql, $params);
        $data = $this->wpdb->get_results($prepared_list);

        return [
            'data'         => $data,
            'total_items'  => $total_items,
            'per_page'     => (int)$filters['per_page'],
            'current_page' => (int)$filters['page'],
        ];
    }

    /**
     * Get a licence by ID.
     *
     * @param int $id Licence ID.
     * @return object|null
     */
    public function get($id) {
        $sql = $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", (int)$id);
        return $this->wpdb->get_row($sql);
    }

    /**
     * Insert a licence record.
     *
     * @param array $data Associative data.
     * @return int Inserted ID.
     */
    public function insert($data) {
        list($fields, $placeholders, $values) = $this->prepare_insert($data);
        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $this->wpdb->query($this->wpdb->prepare($sql, $values));
        return (int)$this->wpdb->insert_id;
    }

    /**
     * Update a licence.
     *
     * @param int $id Licence ID.
     * @param array $data Data to update.
     * @return bool
     */
    public function update($id, $data) {
        list($set, $values) = $this->prepare_set($data);
        $values[] = (int)$id;
        $sql = "UPDATE {$this->table} SET {$set} WHERE id = %d";
        return false !== $this->wpdb->query($this->wpdb->prepare($sql, $values));
    }

    /**
     * Soft delete a licence (move to trash).
     *
     * @param int $id Licence ID.
     * @return bool
     */
    public function soft_delete($id) {
        $sql = "UPDATE {$this->table} SET statut = %s, deleted_at = %s WHERE id = %d";
        $prepared = $this->wpdb->prepare($sql, 'trash', current_time('mysql'), (int)$id);
        return false !== $this->wpdb->query($prepared);
    }

    /**
     * Restore a soft deleted licence.
     *
     * @param int $id Licence ID.
     * @return bool
     */
    public function restore($id) {
        $sql = "UPDATE {$this->table} SET statut = %s, deleted_at = NULL WHERE id = %d";
        $prepared = $this->wpdb->prepare($sql, 'en_attente', (int)$id);
        return false !== $this->wpdb->query($prepared);
    }

    /**
     * Change licence status.
     *
     * @param int    $id     Licence ID.
     * @param string $status New status.
     * @return bool
     */
    public function change_status($id, $status) {
        $sql = "UPDATE {$this->table} SET statut = %s WHERE id = %d";
        $prepared = $this->wpdb->prepare($sql, sanitize_text_field($status), (int)$id);
        return false !== $this->wpdb->query($prepared);
    }

    private function prepare_insert($data) {
        $fields = [];
        $placeholders = [];
        $values = [];
        foreach ($data as $field => $value) {
            $fields[] = $field;
            if (is_numeric($value)) {
                $placeholders[] = '%d';
                $values[] = (int)$value;
            } else {
                $placeholders[] = '%s';
                $values[] = (string)$value;
            }
        }
        return [implode(',', $fields), implode(',', $placeholders), $values];
    }

    private function prepare_set($data) {
        $parts = [];
        $values = [];
        foreach ($data as $field => $value) {
            if (is_numeric($value)) {
                $parts[] = "$field = %d";
                $values[] = (int)$value;
            } else {
                $parts[] = "$field = %s";
                $values[] = (string)$value;
            }
        }
        return [implode(', ', $parts), $values];
    }
}
