<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Repository for UFSC clubs operations.
 */
class UFSC_Clubs_Repository {
    /** @var wpdb */
    private $wpdb;

    /** @var string */
    private $table;

    public function __construct() {
        global $wpdb;
        $this->wpdb  = $wpdb;
        $this->table = $wpdb->prefix . 'ufsc_clubs';
    }

    /**
     * Search clubs by term for AJAX typeahead.
     *
     * @param string $term Search term.
     * @return array List of matching clubs (id, nom).
     */
    public function search($term) {
        $like = '%' . $this->wpdb->esc_like($term) . '%';
        $sql  = $this->wpdb->prepare(
            "SELECT id, nom FROM {$this->table} WHERE nom LIKE %s ORDER BY nom ASC LIMIT 10",
            $like
        );
        return $this->wpdb->get_results($sql);
    }
}
