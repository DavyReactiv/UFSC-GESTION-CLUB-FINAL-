<?php
/**
 * Licence Filters Class
 * 
 * Provides reusable filtering functionality for license lists
 * Used by both admin license list and export functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class UFSC_Licence_Filters
{
    /**
     * Get filter parameters from request
     * 
     * @param array $override_params Optional parameters to override
     * @return array Sanitized filter parameters
     */
    public static function get_filter_parameters($override_params = [])
    {
        $defaults = [
            'club_id' => 0,
            'search' => '',
            'search_prenom' => '',
            'search_email' => '',
            'search_ville' => '',
            'search_telephone' => '',
            'date_naissance_from' => '',
            'date_naissance_to' => '',
            'date_inscription_from' => '',
            'date_inscription_to' => '',
            'region' => '',
            'filter_club' => 0,
            'page' => 1,
            'per_page' => 20
        ];

        $params = [];
        
        // Extract from GET parameters
        $params['club_id'] = isset($_GET['club_id']) ? intval(wp_unslash($_GET['club_id'])) : 0;
        $params['search'] = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $params['search_prenom'] = isset($_GET['search_prenom']) ? sanitize_text_field(wp_unslash($_GET['search_prenom'])) : '';
        $params['search_email'] = isset($_GET['search_email']) ? sanitize_email(wp_unslash($_GET['search_email'])) : '';
        $params['search_ville'] = isset($_GET['search_ville']) ? sanitize_text_field(wp_unslash($_GET['search_ville'])) : '';
        $params['search_telephone'] = isset($_GET['search_telephone']) ? sanitize_text_field(wp_unslash($_GET['search_telephone'])) : '';
        $params['date_naissance_from'] = isset($_GET['date_naissance_from']) ? sanitize_text_field(wp_unslash($_GET['date_naissance_from'])) : '';
        $params['date_naissance_to'] = isset($_GET['date_naissance_to']) ? sanitize_text_field(wp_unslash($_GET['date_naissance_to'])) : '';
        $params['date_inscription_from'] = isset($_GET['date_inscription_from']) ? sanitize_text_field(wp_unslash($_GET['date_inscription_from'])) : '';
        $params['date_inscription_to'] = isset($_GET['date_inscription_to']) ? sanitize_text_field(wp_unslash($_GET['date_inscription_to'])) : '';
        $params['region'] = isset($_GET['region']) ? sanitize_text_field(wp_unslash($_GET['region'])) : '';
        $params['filter_club'] = isset($_GET['filter_club']) ? intval(wp_unslash($_GET['filter_club'])) : 0;
        $params['page'] = isset($_GET['paged']) ? max(1, intval(wp_unslash($_GET['paged']))) : 1;
        $params['per_page'] = 20;

        // Apply overrides
        $params = array_merge($params, $override_params);
        
        // Apply defaults for missing values
        $params = array_merge($defaults, $params);

        return $params;
    }

    /**
     * Build SQL WHERE clause and parameters for licence filtering
     * 
     * @param array $filters Filter parameters
     * @return array Array with 'where_clause' and 'params'
     */
    public static function build_where_clause($filters)
    {
        $where = [];
        $params = [];

        // Base condition - if specific club is selected
        if ($filters['club_id'] > 0) {
            $where[] = 'l.club_id = %d';
            $params[] = $filters['club_id'];
        } elseif ($filters['filter_club'] > 0) {
            $where[] = 'l.club_id = %d';
            $params[] = $filters['filter_club'];
        }

        if ($filters['region'] !== '') {
            $where[] = 'l.region = %s';
            $params[] = $filters['region'];
        }
        
        if ($filters['search'] !== '') {
            $where[] = 'l.nom LIKE %s';
            $params[] = '%' . $filters['search'] . '%';
        }
        
        if ($filters['search_prenom'] !== '') {
            $where[] = 'l.prenom LIKE %s';
            $params[] = '%' . $filters['search_prenom'] . '%';
        }
        
        if ($filters['search_email'] !== '') {
            $where[] = 'l.email LIKE %s';
            $params[] = '%' . $filters['search_email'] . '%';
        }
        
        if ($filters['search_ville'] !== '') {
            $where[] = 'l.ville LIKE %s';
            $params[] = '%' . $filters['search_ville'] . '%';
        }
        
        if ($filters['search_telephone'] !== '') {
            $where[] = '(l.tel_fixe LIKE %s OR l.tel_mobile LIKE %s)';
            $params[] = '%' . $filters['search_telephone'] . '%';
            $params[] = '%' . $filters['search_telephone'] . '%';
        }
        
        if ($filters['date_naissance_from'] !== '') {
            $where[] = 'l.date_naissance >= %s';
            $params[] = $filters['date_naissance_from'];
        }
        
        if ($filters['date_naissance_to'] !== '') {
            $where[] = 'l.date_naissance <= %s';
            $params[] = $filters['date_naissance_to'];
        }
        
        if ($filters['date_inscription_from'] !== '') {
            $where[] = 'DATE(l.date_inscription) >= %s';
            $params[] = $filters['date_inscription_from'];
        }
        
        if ($filters['date_inscription_to'] !== '') {
            $where[] = 'DATE(l.date_inscription) <= %s';
            $params[] = $filters['date_inscription_to'];
        }

        $where_clause = !empty($where) ? implode(' AND ', $where) : '1=1';

        return [
            'where_clause' => $where_clause,
            'params' => $params
        ];
    }

    /**
     * Render the filters form HTML
     * 
     * @param array $filters Current filter values
     * @param string $page_slug The page slug for the form action
     * @param int $club_id Optional club ID for club-specific filtering
     * @param bool $show_export Whether to show export button
     */
    public static function render_filters_form($filters, $page_slug, $club_id = 0, $show_export = true)
    {
        global $wpdb;
        
        // Load helpers for regions
        require_once plugin_dir_path(__FILE__) . '../helpers.php';
        
        ?>
        <div class="ufsc-filters-container">
            <h3><?php _e('Filtres de recherche', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
            <form method="get" class="ufsc-filters-form">
                <input type="hidden" name="page" value="<?php echo esc_attr($page_slug); ?>">
                <?php if ($club_id): ?>
                    <input type="hidden" name="club_id" value="<?php echo esc_attr($club_id); ?>">
                <?php endif; ?>

                <div class="ufsc-filters-grid">
                    <div class="ufsc-filter-field">
                        <label for="s"><?php _e('Nom', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="text" name="s" id="s" value="<?php echo esc_attr($filters['search']); ?>" placeholder="<?php _e('Rechercher par nom', 'plugin-ufsc-gestion-club-13072025'); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="search_prenom"><?php _e('Prénom', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="text" name="search_prenom" id="search_prenom" value="<?php echo esc_attr($filters['search_prenom']); ?>" placeholder="<?php _e('Rechercher par prénom', 'plugin-ufsc-gestion-club-13072025'); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="search_email"><?php _e('Email', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="email" name="search_email" id="search_email" value="<?php echo esc_attr($filters['search_email']); ?>" placeholder="<?php _e('Rechercher par email', 'plugin-ufsc-gestion-club-13072025'); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="search_ville"><?php _e('Ville', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="text" name="search_ville" id="search_ville" value="<?php echo esc_attr($filters['search_ville']); ?>" placeholder="<?php _e('Rechercher par ville', 'plugin-ufsc-gestion-club-13072025'); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="region"><?php _e('Région', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <select name="region" id="region">
                            <option value=""><?php _e('Toutes régions', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                            <?php foreach (ufsc_get_regions() as $r): ?>
                                <option value="<?php echo esc_attr($r); ?>" <?php selected($filters['region'], $r); ?>><?php echo esc_html($r); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="search_telephone"><?php _e('Téléphone', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="text" name="search_telephone" id="search_telephone" value="<?php echo esc_attr($filters['search_telephone']); ?>" placeholder="<?php _e('Rechercher par téléphone', 'plugin-ufsc-gestion-club-13072025'); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="date_naissance_from"><?php _e('Date de naissance (de)', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="date" name="date_naissance_from" id="date_naissance_from" value="<?php echo esc_attr($filters['date_naissance_from']); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="date_naissance_to"><?php _e('Date de naissance (à)', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="date" name="date_naissance_to" id="date_naissance_to" value="<?php echo esc_attr($filters['date_naissance_to']); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="date_inscription_from"><?php _e('Date d\'enregistrement (de)', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="date" name="date_inscription_from" id="date_inscription_from" value="<?php echo esc_attr($filters['date_inscription_from']); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="date_inscription_to"><?php _e('Date d\'enregistrement (à)', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <input type="date" name="date_inscription_to" id="date_inscription_to" value="<?php echo esc_attr($filters['date_inscription_to']); ?>">
                    </div>
                    
                    <?php if (!$club_id): ?>
                    <div class="ufsc-filter-field">
                        <label for="filter_club"><?php _e('Club', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        <select name="filter_club" id="filter_club">
                            <option value=""><?php _e('Tous les clubs', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                            <?php
                            $all_clubs = $wpdb->get_results("SELECT id, nom FROM {$wpdb->prefix}ufsc_clubs ORDER BY nom ASC");
                            foreach ($all_clubs as $club_option): ?>
                                <option value="<?php echo esc_attr($club_option->id); ?>" <?php selected($filters['filter_club'], $club_option->id); ?>>
                                    <?php echo esc_html($club_option->nom); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ufsc-filters-actions">
                    <button type="submit" class="button button-primary"><?php _e('Filtrer', 'plugin-ufsc-gestion-club-13072025'); ?></button>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $page_slug . ($club_id ? '&club_id=' . $club_id : ''))); ?>" 
                       class="button"><?php _e('Réinitialiser', 'plugin-ufsc-gestion-club-13072025'); ?></a>

                    <?php if ($show_export): ?>
                        <a href="<?php echo esc_url(add_query_arg([
                            'export_csv' => 1,
                            's' => $filters['search'],
                            'search_prenom' => $filters['search_prenom'],
                            'search_email' => $filters['search_email'],
                            'search_ville' => $filters['search_ville'],
                            'search_telephone' => $filters['search_telephone'],
                            'date_naissance_from' => $filters['date_naissance_from'],
                            'date_naissance_to' => $filters['date_naissance_to'],
                            'date_inscription_from' => $filters['date_inscription_from'],
                            'date_inscription_to' => $filters['date_inscription_to'],
                            'region' => $filters['region'],
                            'filter_club' => $filters['filter_club'],
                            'paged' => 1,
                            '_wpnonce' => wp_create_nonce('ufsc_export_licences_' . ($club_id ?: 'all'))
                        ])); ?>" class="button"><?php _e('Exporter CSV', 'plugin-ufsc-gestion-club-13072025'); ?></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Render the filters CSS
     */
    public static function render_filters_css()
    {
        ?>
        <style>
        .ufsc-filters-container {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .ufsc-filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .ufsc-filter-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .ufsc-filter-field input,
        .ufsc-filter-field select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .ufsc-filters-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .ufsc-filters-grid {
                grid-template-columns: 1fr !important;
            }
        }
        </style>
        <?php
    }

    /**
     * Get filtered license data with pagination
     * 
     * @param array $filters Filter parameters
     * @return array Array with 'data', 'total_items', 'per_page', 'current_page'
     */
    public static function get_filtered_licenses($filters)
    {
        global $wpdb;
        
        $where_data = self::build_where_clause($filters);
        $where_clause = $where_data['where_clause'];
        $params = $where_data['params'];
        
        // Get total count
        if (!empty($params)) {
            $total_items = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause",
                ...$params
            ));
        } else {
            $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause");
        }
        
        // Get paginated data
        $offset = ($filters['page'] - 1) * $filters['per_page'];
        $params_limit = array_merge($params, [$filters['per_page'], $offset]);
        
        if (!empty($params)) {
            $data = $wpdb->get_results($wpdb->prepare(
                "SELECT l.*, c.nom as club_nom FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause
                 ORDER BY l.date_inscription DESC
                 LIMIT %d OFFSET %d",
                ...$params_limit
            ));
        } else {
            $data = $wpdb->get_results($wpdb->prepare(
                "SELECT l.*, c.nom as club_nom FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause
                 ORDER BY l.date_inscription DESC
                 LIMIT %d OFFSET %d",
                $filters['per_page'], $offset
            ));
        }
        
        return [
            'data' => $data,
            'total_items' => $total_items,
            'per_page' => $filters['per_page'],
            'current_page' => $filters['page']
        ];
    }
}