<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class UFSC_Club_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'club',
            'plural'   => 'clubs',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'            => '<input type="checkbox" />',
            'nom'           => __( 'Nom', 'plugin-ufsc-gestion-club-13072025' ),
            'region'        => __( 'Région', 'plugin-ufsc-gestion-club-13072025' ),
            'email'         => __( 'Email', 'plugin-ufsc-gestion-club-13072025' ),
            'statut'        => __( 'Statut', 'plugin-ufsc-gestion-club-13072025' ),
            'date_creation' => __( 'Création', 'plugin-ufsc-gestion-club-13072025' ),
        ];
    }

    protected function column_cb( $item ) {
        return sprintf('<input type="checkbox" name="club_ids[]" value="%d" />', $item['id']);
    }

    protected function column_nom( $item ) {
        $actions = [
            'edit' => sprintf(
                '<a href="%s" title="%s"><span class="dashicons dashicons-edit"></span></a>',
                esc_url( admin_url( 'admin.php?page=ufsc_edit_club&id=' . $item['id'] ) ),
                esc_attr__( 'Modifier le club', 'plugin-ufsc-gestion-club-13072025' )
            ),
            'view' => sprintf(
                '<a href="%s" title="%s"><span class="dashicons dashicons-visibility"></span></a>',
                esc_url( admin_url( 'admin.php?page=ufsc_view_club&id=' . $item['id'] ) ),
                esc_attr__( 'Voir le club', 'plugin-ufsc-gestion-club-13072025' )
            ),
        ];
        return sprintf('%1$s %2$s', esc_html($item['nom']), $this->row_actions($actions));
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'statut':
                return $this->render_status_badge( $item['statut'] );
            default:
                return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
        }
    }

    protected function get_sortable_columns() {
        return [
            'nom'           => ['nom', false],
            'date_creation' => ['date_creation', true],
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $search = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        $table = $wpdb->prefix . 'ufsc_clubs';
        $where = 'WHERE 1=1';
        $params = [];
        if ( $search !== '' ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= ' AND (nom LIKE %s OR email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        $orderby = ! empty( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'date_creation';
        $order   = ! empty( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ? 'ASC' : 'DESC';

        $total_items = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", ...$params ) );

        $params[] = $per_page;
        $params[] = ( $current_page - 1 ) * $per_page;
        $sql = "SELECT id, nom, region, email, statut, date_creation FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

        $this->items = $items;
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ]);
    }

    private function render_status_badge( $status ) {
        $status = strtolower( $status );
        $class  = 'ufsc-badge ufsc-badge-default';
        $label  = ucfirst( $status );

        if ( in_array( $status, ['actif', 'active'], true ) ) {
            $class = 'ufsc-badge ufsc-badge-success';
            $label = __( 'Actif', 'plugin-ufsc-gestion-club-13072025' );
        } elseif ( in_array( $status, ['inactif', 'inactive', 'refuse', 'refus\u00e9'], true ) ) {
            $class = 'ufsc-badge ufsc-badge-error';
            $label = __( 'Inactif', 'plugin-ufsc-gestion-club-13072025' );
        } elseif ( in_array( $status, ['en attente', 'en_attente', 'pending'], true ) ) {
            $class = 'ufsc-badge ufsc-badge-warning';
            $label = __( 'En attente', 'plugin-ufsc-gestion-club-13072025' );
        }

        return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
    }
}
