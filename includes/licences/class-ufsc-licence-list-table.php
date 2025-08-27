<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class UFSC_Licence_List_Table extends WP_List_Table {
    protected $club_id = 0;
    private $external_data = null;

    public function __construct( $club_id = 0 ) {
        $this->club_id = (int) $club_id;
        parent::__construct([
            'singular' => 'licence',
            'plural'   => 'licences',
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
        return [
            'cb'               => '<input type="checkbox" />',
            'nom'              => __( 'Nom', 'plugin-ufsc-gestion-club-13072025' ),
            'prenom'           => __( 'Prénom', 'plugin-ufsc-gestion-club-13072025' ),
            'email'            => __( 'Email', 'plugin-ufsc-gestion-club-13072025' ),
            'club'             => __( 'Club', 'plugin-ufsc-gestion-club-13072025' ),
            'statut'           => __( 'Statut', 'plugin-ufsc-gestion-club-13072025' ),
            'date_inscription' => __( 'Inscription', 'plugin-ufsc-gestion-club-13072025' ),
        ];
    }

    protected function column_cb( $item ) {
        return sprintf('<input type="checkbox" name="licence_ids[]" value="%d" />', $item['id']);
    }

    protected function column_nom( $item ) {
        $actions = [];

        if ( current_user_can( 'ufsc_manage_licences' ) ) {
            $view_url = wp_nonce_url(
                admin_url( 'admin.php?page=ufsc_view_licence&id=' . $item['id'] ),
                'ufsc_view_licence_' . $item['id']
            );
            $actions['view'] = sprintf(
                '<a href="%s" title="%s"><span class="dashicons dashicons-visibility"></span></a>',
                esc_url( $view_url ),
                esc_attr__( 'Voir la licence', 'plugin-ufsc-gestion-club-13072025' )
            );
        }

        if ( current_user_can( 'ufsc_manage_licences' ) ) {
            $edit_url = wp_nonce_url(
                admin_url( 'admin.php?page=ufsc-modifier-licence&licence_id=' . $item['id'] ),
                'ufsc_edit_licence_' . $item['id']
            );
            $actions['edit'] = sprintf(
                '<a href="%s" title="%s"><span class="dashicons dashicons-edit"></span></a>',
                esc_url( $edit_url ),
                esc_attr__( 'Modifier la licence', 'plugin-ufsc-gestion-club-13072025' )
            );

            $validate_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=ufsc_validate_licence&licence_id=' . $item['id'] ),
                'ufsc_validate_licence_' . $item['id']
            );
            $actions['validate'] = sprintf(
                '<a href="%s" title="%s" onclick="return confirm(\'%s\');"><span class="dashicons dashicons-yes-alt"></span></a>',
                esc_url( $validate_url ),
                esc_attr__( 'Valider la licence', 'plugin-ufsc-gestion-club-13072025' ),
                esc_attr__( 'Confirmer la validation ?', 'plugin-ufsc-gestion-club-13072025' )
            );

            $delete_url = wp_nonce_url(
                admin_url( 'admin-post.php?action=ufsc_delete_licence&licence_id=' . $item['id'] ),
                'ufsc_delete_licence_' . $item['id']
            );
            $actions['delete'] = sprintf(
                '<a href="%s" title="%s" onclick="return confirm(\'%s\');"><span class="dashicons dashicons-trash"></span></a>',
                esc_url( $delete_url ),
                esc_attr__( 'Supprimer la licence', 'plugin-ufsc-gestion-club-13072025' ),
                esc_attr__( 'Supprimer définitivement ?', 'plugin-ufsc-gestion-club-13072025' )
            );

            $reassign_nonce = wp_create_nonce( 'ufsc_reassign_licence_' . $item['id'] );
            $actions['reassign'] = sprintf(
                '<a href="#" class="ufsc-reassign-licence" data-id="%d" data-nonce="%s" title="%s"><span class="dashicons dashicons-randomize"></span></a>',
                $item['id'],
                esc_attr( $reassign_nonce ),
                esc_attr__( 'Réaffecter la licence à un autre club', 'plugin-ufsc-gestion-club-13072025' )
            );
        }

        return sprintf( '%1$s %2$s', esc_html( $item['nom'] ), $this->row_actions( $actions ) );
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'statut':
                return $this->render_status_badge( $item['statut'] );
            case 'club':
                return esc_html( $item['club_nom'] );
            default:
                return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
        }
    }

    protected function get_sortable_columns() {
        return [
            'nom'              => ['nom', false],
            'prenom'           => ['prenom', false],
            'date_inscription' => ['date_inscription', true],
        ];
    }

    public function set_external_data($data, $total_items, $per_page) {
        $this->external_data = [
            'items'       => array_map('get_object_vars', $data),
            'total_items' => (int) $total_items,
            'per_page'    => (int) $per_page,
        ];
    }

    public function prepare_items() {
        if ($this->external_data) {
            $this->items = $this->external_data['items'];
            $total_items = $this->external_data['total_items'];
            $per_page    = $this->external_data['per_page'];
            $total_pages = (int) ceil($total_items / $per_page);
            $this->set_pagination_args([
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => $total_pages,
            ]);
            return;
        }

        global $wpdb;
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $search = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';

        $table       = $wpdb->prefix . 'ufsc_licences';
        $clubs_table = $wpdb->prefix . 'ufsc_clubs';
        $where       = 'WHERE 1=1';
        $params      = [];

        if ( $this->club_id ) {
            $where .= ' AND l.club_id = %d';
            $params[] = $this->club_id;
        }

        if ( '' !== $search ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= ' AND (l.nom LIKE %s OR l.prenom LIKE %s OR l.email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $orderby = ! empty( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'date_inscription';
        $order   = ! empty( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ? 'ASC' : 'DESC';

        $count_sql = "SELECT COUNT(*) FROM {$table} l {$where}";
        if ( empty( $params ) ) {
            $total_items = (int) $wpdb->get_var( $count_sql );
        } else {
            $total_items = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );
        }

        $limit  = $per_page;
        $offset = ( $current_page - 1 ) * $per_page;

        $select_sql = "SELECT l.id, l.nom, l.prenom, l.email, l.statut, l.date_inscription, c.nom AS club_nom
                FROM {$table} l
                LEFT JOIN {$clubs_table} c ON l.club_id = c.id
                {$where}
                ORDER BY {$orderby} {$order}
                LIMIT %d OFFSET %d";

        if ( empty( $params ) ) {
            $this->items = $wpdb->get_results( sprintf( $select_sql, $limit, $offset ), ARRAY_A );
        } else {
            $params_with_limit = array_merge( $params, [ $limit, $offset ] );
            $this->items       = $wpdb->get_results( $wpdb->prepare( $select_sql, ...$params_with_limit ), ARRAY_A );
        }

        $total_pages = (int) ceil( $total_items / $per_page );
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => $total_pages,
        ]);
    }

    private function render_status_badge( $status ) {
        $status = strtolower( $status );
        $class  = 'ufsc-badge ufsc-badge-default';
        $label  = ucfirst( $status );

        if ( in_array( $status, ['validee', 'validée', 'active', 'actif'], true ) ) {
            $class = 'ufsc-badge ufsc-badge-success';
            $label = __( 'Validée', 'plugin-ufsc-gestion-club-13072025' );
        } elseif ( in_array( $status, ['refusee', 'refusée', 'inactif'], true ) ) {
            $class = 'ufsc-badge ufsc-badge-error';
            $label = __( 'Refusée', 'plugin-ufsc-gestion-club-13072025' );
        } elseif ( in_array( $status, ['en attente', 'en_attente', 'pending'], true ) ) {
            $class = 'ufsc-badge ufsc-badge-warning';
            $label = __( 'En attente', 'plugin-ufsc-gestion-club-13072025' );
        }

        return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
    }
}
