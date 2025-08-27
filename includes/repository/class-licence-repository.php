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
        $this->wpdb->insert($this->table, $data);
        return (int) $this->wpdb->insert_id;
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

        $result = $this->wpdb->update($this->table, $data, ['id' => $id], null, ['%d']);
        return $result !== false;
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $result = $this->wpdb->delete($this->table, ['id' => $id], ['%d']);
        return $result !== false;
    }

    public function get_by_id(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }

        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id)
        );
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
