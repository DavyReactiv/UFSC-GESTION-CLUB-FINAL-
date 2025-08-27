<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class UFSC_Licenses_List_Table extends WP_List_Table {
    /**
     * Optional club filter passed from the page.
     *
     * @var int
     */
    protected $club_id = 0;
    private $external_data = null;

    public function __construct( $club_id = 0 ) {
        $this->club_id = (int) $club_id;
        parent::__construct(
            [
                'singular' => 'licence',
                'plural'   => 'licences',
                'ajax'     => false,
            ]
        );
    }

    /**
     * Columns displayed in the list table.
     */
    public function get_columns() {
        return [
            'cb'             => '<input type="checkbox" />',
            'id'             => __( 'ID', 'plugin-ufsc-gestion-club-13072025' ),
            'nom'            => __( 'Nom', 'plugin-ufsc-gestion-club-13072025' ),
            'prenom'         => __( 'Prénom', 'plugin-ufsc-gestion-club-13072025' ),
            'email'          => __( 'Email', 'plugin-ufsc-gestion-club-13072025' ),
            'sexe'           => __( 'Sexe', 'plugin-ufsc-gestion-club-13072025' ),
            'date_naissance' => __( 'Naissance', 'plugin-ufsc-gestion-club-13072025' ),
            'club'           => __( 'Club', 'plugin-ufsc-gestion-club-13072025' ),
            'categorie'      => __( 'Catégorie', 'plugin-ufsc-gestion-club-13072025' ),
            'quota'          => __( 'Quota', 'plugin-ufsc-gestion-club-13072025' ),
            'statut'         => __( 'Statut', 'plugin-ufsc-gestion-club-13072025' ),
            'date_licence'   => __( 'Date licence', 'plugin-ufsc-gestion-club-13072025' ),
            'actions'        => __( 'Actions', 'plugin-ufsc-gestion-club-13072025' ),
        ];
    }

    /**
     * Sortable columns.
     */
    protected function get_sortable_columns() {
        return [
            'id'           => [ 'id', true ],
            'nom'          => [ 'nom', false ],
            'date_licence' => [ 'date_inscription', true ],
        ];
    }

    /**
     * Bulk actions.
     */
    public function get_bulk_actions() {
        return [
            'validate' => __( 'Valider', 'plugin-ufsc-gestion-club-13072025' ),
            'pending'  => __( 'Attente', 'plugin-ufsc-gestion-club-13072025' ),
            'refuse'   => __( 'Refuser', 'plugin-ufsc-gestion-club-13072025' ),
            'trash'    => __( 'Corbeille', 'plugin-ufsc-gestion-club-13072025' ),
            'restore'  => __( 'Restaurer', 'plugin-ufsc-gestion-club-13072025' ),
        ];
    }


    protected function column_cb( $item ) {
        $checkbox = sprintf( '<input type="checkbox" name="licence_ids[]" value="%d" />', $item['id'] );
        $actions  = [];

        if ( current_user_can( 'manage_ufsc_licenses' ) ) {
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

        if ( current_user_can( 'manage_ufsc_licenses' ) ) {
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


        $nom       = esc_html( $item['nom'] );
        $name_html = '<span class="ufsc-text-ellipsis" title="' . esc_attr( $item['nom'] ) . '">' . $nom . '</span>';

        return sprintf( '%1$s %2$s %3$s', $checkbox, $name_html, $this->row_actions( $actions ) );
    }

    protected function column_prenom( $item ) {
        $prenom = esc_html( $item['prenom'] );
        return '<span class="ufsc-text-ellipsis" title="' . esc_attr( $item['prenom'] ) . '">' . $prenom . '</span>';
    }

    protected function column_email( $item ) {
        $email = esc_html( $item['email'] );
        return '<span class="ufsc-text-ellipsis" title="' . esc_attr( $item['email'] ) . '">' . $email . '</span>';
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'sexe':
                return esc_html( strtoupper( $item['sexe'] ) );
            case 'date_naissance':
                return esc_html( mysql2date( get_option( 'date_format' ), $item['date_naissance'] ) );
            case 'club':
                return esc_html( $item['club'] );
            case 'categorie':
                return esc_html( $item['categorie'] );
            case 'quota':
                return $item['quota'] ? __( 'Oui', 'plugin-ufsc-gestion-club-13072025' ) : __( 'Non', 'plugin-ufsc-gestion-club-13072025' );
            case 'statut':
                return $this->render_status_badge( $item['statut'] );
            case 'date_licence':
                return esc_html( mysql2date( get_option( 'date_format' ), $item['date_licence'] ) );
            default:
                return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
        }
    }

    /**
     * Column displaying action links.
     */
    protected function column_actions( $item ) {
        $id      = (int) $item['id'];
        $actions = [];

        $actions['view'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( wp_nonce_url( admin_url( 'admin.php?page=ufsc_view_licence&id=' . $id ), 'ufsc_view_licence_' . $id ) ),
            esc_html__( 'Voir', 'plugin-ufsc-gestion-club-13072025' )
        );

        $actions['edit'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( wp_nonce_url( admin_url( 'admin.php?page=ufsc-modifier-licence&licence_id=' . $id ), 'ufsc_edit_licence_' . $id ) ),
            esc_html__( 'Éditer', 'plugin-ufsc-gestion-club-13072025' )
        );

        $actions['validate'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ufsc_validate_licence&licence_id=' . $id ), 'ufsc_validate_licence_' . $id ) ),
            esc_html__( 'Valider', 'plugin-ufsc-gestion-club-13072025' )
        );

        $actions['pending'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ufsc_pending_licence&licence_id=' . $id ), 'ufsc_pending_licence_' . $id ) ),
            esc_html__( 'Attente', 'plugin-ufsc-gestion-club-13072025' )
        );

        $actions['refuse'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ufsc_refuse_licence&licence_id=' . $id ), 'ufsc_refuse_licence_' . $id ) ),
            esc_html__( 'Refuser', 'plugin-ufsc-gestion-club-13072025' )
        );

        $actions['trash'] = sprintf(
            '<a href="%s" class="submitdelete">%s</a>',
            esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ufsc_trash_licence&licence_id=' . $id ), 'ufsc_trash_licence_' . $id ) ),
            esc_html__( 'Corbeille', 'plugin-ufsc-gestion-club-13072025' )
        );

        $actions['restore'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ufsc_restore_licence&licence_id=' . $id ), 'ufsc_restore_licence_' . $id ) ),
            esc_html__( 'Restaurer', 'plugin-ufsc-gestion-club-13072025' )
        );

        return implode( ' | ', $actions );
    }


    /**
     * Retrieve items to display.
     */

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

        $per_page = isset( $_REQUEST['per_page'] ) ? (int) $_REQUEST['per_page'] : 20;
        if ( ! in_array( $per_page, [ 20, 50, 100 ], true ) ) {
            $per_page = 20;
        }
        $current_page = $this->get_pagenum();

        $search = isset( $_REQUEST['s'] ) ? wp_unslash( trim( $_REQUEST['s'] ) ) : '';
        $club   = isset( $_REQUEST['filter_club'] ) ? (int) $_REQUEST['filter_club'] : 0;
        $statut = isset( $_REQUEST['filter_statut'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_statut'] ) ) : '';
        $cat    = isset( $_REQUEST['filter_categorie'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_categorie'] ) ) : '';
        $start  = isset( $_REQUEST['start_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['start_date'] ) ) : '';
        $end    = isset( $_REQUEST['end_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['end_date'] ) ) : '';

        $table       = $wpdb->prefix . 'ufsc_licences l';
        $clubs_table = $wpdb->prefix . 'ufsc_clubs c';
        $where       = 'WHERE 1=1';
        $params      = [];

        if ( $this->club_id ) {
            $where   .= ' AND l.club_id = %d';
            $params[] = $this->club_id;
        }

        if ( $club ) {
            $where   .= ' AND l.club_id = %d';
            $params[] = $club;
        }

        if ( $statut ) {
            $where   .= ' AND l.statut = %s';
            $params[] = $statut;
        }

        if ( $cat ) {
            $where   .= ' AND l.categorie = %s';
            $params[] = $cat;
        }

        if ( $start ) {
            $where   .= ' AND l.date_inscription >= %s';
            $params[] = $start;
        }

        if ( $end ) {
            $where   .= ' AND l.date_inscription <= %s';
            $params[] = $end;
        }

        if ( '' !== $search ) {
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $where   .= ' AND (l.nom LIKE %s OR l.prenom LIKE %s OR l.email LIKE %s OR c.nom LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $allowed_orderby = [ 'id', 'nom', 'date_inscription' ];
        $orderby         = isset( $_REQUEST['orderby'] ) ? sanitize_key( $_REQUEST['orderby'] ) : 'date_inscription';
        if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
            $orderby = 'date_inscription';
        }

        $order = ! empty( $_REQUEST['order'] ) && 'asc' === strtolower( $_REQUEST['order'] ) ? 'ASC' : 'DESC';

        $count_sql   = "SELECT COUNT(*) FROM {$table} LEFT JOIN {$clubs_table} ON l.club_id = c.id {$where}";
        $total_items = empty( $params ) ? (int) $wpdb->get_var( $count_sql ) : (int) $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) );

        $offset = ( $current_page - 1 ) * $per_page;

        $select_sql = "SELECT l.id, l.nom, l.prenom, l.email, l.sexe, l.date_naissance, c.nom AS club, l.categorie, l.is_included AS quota, l.statut, l.date_inscription AS date_licence
                        FROM {$table}
                        LEFT JOIN {$clubs_table} ON l.club_id = c.id
                        {$where}
                        ORDER BY {$orderby} {$order}
                        LIMIT %d OFFSET %d";

        if ( empty( $params ) ) {
            $this->items = $wpdb->get_results( $wpdb->prepare( $select_sql, $per_page, $offset ), ARRAY_A );
        } else {
            $params_with_limit = array_merge( $params, [ $per_page, $offset ] );
            $this->items       = $wpdb->get_results( $wpdb->prepare( $select_sql, ...$params_with_limit ), ARRAY_A );
        }

        $this->set_pagination_args(
            [
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => (int) ceil( $total_items / $per_page ),
            ]
        );
    }

    /**
     * Filters displayed above the table.
     */
    public function extra_tablenav( $which ) {
        if ( 'top' !== $which ) {
            return;
        }

        global $wpdb;
        $club   = isset( $_REQUEST['filter_club'] ) ? (int) $_REQUEST['filter_club'] : 0;
        $statut = isset( $_REQUEST['filter_statut'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_statut'] ) ) : '';
        $cat    = isset( $_REQUEST['filter_categorie'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter_categorie'] ) ) : '';
        $start  = isset( $_REQUEST['start_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['start_date'] ) ) : '';
        $end    = isset( $_REQUEST['end_date'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['end_date'] ) ) : '';
        $per    = isset( $_REQUEST['per_page'] ) ? (int) $_REQUEST['per_page'] : 20;

        echo '<div class="alignleft actions">';

        echo '<select name="filter_club" class="ufsc-club-ajax" style="width:180px" data-placeholder="' . esc_attr__( 'Club', 'plugin-ufsc-gestion-club-13072025' ) . '">';
        if ( $club ) {
            $club_name = $wpdb->get_var( $wpdb->prepare( "SELECT nom FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d", $club ) );
            echo '<option value="' . esc_attr( $club ) . '" selected>' . esc_html( $club_name ) . '</option>';
        }
        echo '</select>';

        $statuses = [
            ''           => __( 'Statut', 'plugin-ufsc-gestion-club-13072025' ),
            'validee'    => __( 'Validée', 'plugin-ufsc-gestion-club-13072025' ),
            'en_attente' => __( 'En attente', 'plugin-ufsc-gestion-club-13072025' ),
            'refusee'    => __( 'Refusée', 'plugin-ufsc-gestion-club-13072025' ),
            'trash'      => __( 'Corbeille', 'plugin-ufsc-gestion-club-13072025' ),
        ];
        echo '<select name="filter_statut">';
        foreach ( $statuses as $value => $label ) {
            printf( '<option value="%s" %s>%s</option>', esc_attr( $value ), selected( $statut, $value, false ), esc_html( $label ) );
        }
        echo '</select>';

        echo '<select name="filter_categorie">';
        echo '<option value="">' . esc_html__( 'Catégorie', 'plugin-ufsc-gestion-club-13072025' ) . '</option>';
        $cats = $wpdb->get_col( "SELECT DISTINCT categorie FROM {$wpdb->prefix}ufsc_licences WHERE categorie <> '' ORDER BY categorie" );
        foreach ( $cats as $c ) {
            printf( '<option value="%s" %s>%s</option>', esc_attr( $c ), selected( $cat, $c, false ), esc_html( $c ) );
        }
        echo '</select>';

        printf( '<input type="date" name="start_date" value="%s" />', esc_attr( $start ) );
        printf( '<input type="date" name="end_date" value="%s" />', esc_attr( $end ) );

        echo '<select name="per_page">';
        foreach ( [ 20, 50, 100 ] as $pp ) {
            printf( '<option value="%d" %s>%d</option>', $pp, selected( $per, $pp, false ), $pp );
        }
        echo '</select>';

        submit_button( __( 'Filtrer', 'plugin-ufsc-gestion-club-13072025' ), '', 'filter_action', false );
        echo '</div>';
    }

    /**
     * Render a visual badge for the status.
     */
    private function render_status_badge( $status ) {
        $status = strtolower( $status );
        $class  = 'ufsc-badge ufsc-badge--pending';
        $label  = ucfirst( $status );



        if ( in_array( $status, ['validee', 'validée', 'active', 'actif'], true ) ) {
            $class = 'ufsc-badge ufsc-badge--ok';
            $label = __( 'Validée', 'plugin-ufsc-gestion-club-13072025' );
        } elseif ( in_array( $status, ['refusee', 'refusée', 'inactif'], true ) ) {
            $class = 'ufsc-badge ufsc-badge--err';
            $label = __( 'Refusée', 'plugin-ufsc-gestion-club-13072025' );
        } elseif ( in_array( $status, ['en attente', 'en_attente', 'pending'], true ) ) {
            $class = 'ufsc-badge ufsc-badge--pending';
            $label = __( 'En attente', 'plugin-ufsc-gestion-club-13072025' );
        } elseif ( in_array( $status, ['expiree', 'expirée', 'expired'], true ) ) {
            $class = 'ufsc-badge ufsc-badge--expired';
            $label = __( 'Expirée', 'plugin-ufsc-gestion-club-13072025' );
        } elseif ( 'trash' === $status ) {
            $class = 'ufsc-badge ufsc-badge-default';
            $label = __( 'Corbeille', 'plugin-ufsc-gestion-club-13072025' );
        }

        return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
    }
}

?>

