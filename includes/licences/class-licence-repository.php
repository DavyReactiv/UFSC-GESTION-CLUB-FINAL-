<?php
if (!defined('ABSPATH')) {
    exit;
}

class UFSC_Licence_Repository {
    private $wpdb;
    private $table;

    public function __construct() {
        global $wpdb;
        $this->wpdb  = $wpdb;
        $this->table = $wpdb->prefix . 'ufsc_licences';
    }

    public function get($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id));
    }

    public function insert($data) {
        $this->wpdb->insert($this->table, $data);
        return (int) $this->wpdb->insert_id;
    }

    public function update($id, $data) {
        return false !== $this->wpdb->update($this->table, $data, ['id' => $id]);
    }
}
