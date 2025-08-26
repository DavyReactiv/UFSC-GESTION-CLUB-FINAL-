<?php

/**
 * Dashboard Class
 *
 * Handles the dashboard functionality for the UFSC Gestion Club plugin
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard Class
 */
class UFSC_Dashboard
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize dashboard components
    }

    /**
     * Render the main dashboard page
     */
    public function render_dashboard()
    {
        global $wpdb;

        // Statistiques de base
        $clubs_total      = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs");
        $clubs_attente    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs WHERE statut = 'en_attente'");
        $licences_total   = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences");
        // For licenses, we'll use a recent date filter instead of status since there might not be a status field
        $licences_attente = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences WHERE date_inscription > DATE_SUB(NOW(), INTERVAL 30 DAY)");

        echo '<div class="wrap ufsc-dashboard">';
        echo '<h1>📊 Tableau de bord UFSC</h1>';
        echo '<p>' . esc_html__('Bienvenue sur le tableau de bord UFSC. Retrouvez ici un résumé et des statistiques sur l\'activité des clubs affiliés.', 'plugin-ufsc-gestion-club-13072025') . '</p>';

        // Formulaires de recherche
        echo '<div class="ufsc-search-forms">';
        echo '<form method="get" class="ufsc-search-form">
                <input type="hidden" name="page" value="ufsc-liste-clubs">
                <input type="text" name="club_search" class="regular-text" placeholder="🔍 Rechercher un club par nom...">
                <button type="submit" class="button button-primary">Rechercher un club</button>
              </form>';

        echo '<form method="get" class="ufsc-search-form">
                <input type="hidden" name="page" value="ufsc-liste-licences">
                <input type="text" name="licence_search" class="regular-text" placeholder="🔍 Rechercher un licencié par nom...">
                <button type="submit" class="button button-secondary">Rechercher un licencié</button>
              </form>';
        echo '</div>';

        // Widgets d'actions rapides
        echo '<div class="ufsc-quick-actions">';
        $this->render_quick_action_widget('Licences récentes', $licences_attente, 'ufsc-liste-licences', '⏳');
        $this->render_quick_action_widget('Clubs à valider', $clubs_attente, 'ufsc-liste-clubs', '🏢');
        $this->render_alerts_widget();
        echo '</div>';

        // Cartes de statistiques principales
        echo '<div class="ufsc-stats-cards">';
        $this->render_card('🏢 Clubs affiliés', $clubs_total);
        $this->render_card('🎫 Licences actives', $licences_total);
        $this->render_card('👥 Licenciés cette année', $this->get_current_year_licenses());
        $this->render_card('📈 Évolution mensuelle', $this->get_monthly_growth());
        echo '</div>';

        // Grille de graphiques principaux
        echo '<div class="ufsc-charts-grid">';
        
        // Première ligne
        echo '<div class="ufsc-chart-row">';
        $this->render_gender_chart();
        $this->render_region_chart();
        $this->render_competition_chart();
        echo '</div>';

        // Deuxième ligne
        echo '<div class="ufsc-chart-row">';
        $this->render_age_groups_chart();
        $this->render_employment_status_chart();
        $this->render_volunteer_ratio_chart();
        echo '</div>';

        // Troisième ligne
        echo '<div class="ufsc-chart-row">';
        $this->render_license_evolution_chart();
        $this->render_top_clubs_widget();
        $this->render_recent_activities_widget();
        echo '</div>';

        echo '</div>'; // End charts grid
        echo '</div>'; // End wrap

        $this->enqueue_dashboard_styles();
        $this->enqueue_chart_data();
    }

    /**
     * Render a dashboard card
     *
     * @param string $title Card title
     * @param mixed $value Card value
     */
    private function render_card($title, $value)
    {
        echo '<div class="ufsc-card">';
        echo '<h2>' . esc_html($title) . '</h2>';
        echo '<p>' . esc_html($value) . '</p>';
        echo '</div>';
    }

    /**
     * Render quick action widget with clickable button
     */
    private function render_quick_action_widget($title, $count, $page, $icon)
    {
        $url = admin_url('admin.php?page=' . $page);
        echo '<div class="ufsc-quick-action">';
        echo '<div class="ufsc-quick-action-icon">' . $icon . '</div>';
        echo '<div class="ufsc-quick-action-content">';
        echo '<h3>' . esc_html($title) . '</h3>';
        echo '<div class="ufsc-quick-action-count">' . esc_html($count) . '</div>';
        echo '<a href="' . esc_url($url) . '" class="button button-primary">Voir tout</a>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render alerts widget with system notifications
     */
    private function render_alerts_widget()
    {
        global $wpdb;
        
        $alerts = [];
        
        // Check for expired licenses (example logic)
        $expired_licenses = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences 
            WHERE YEAR(date_inscription) < YEAR(CURDATE())
        ");
        if ($expired_licenses > 0) {
            $alerts[] = ['type' => 'warning', 'message' => "$expired_licenses licence(s) expirée(s)"];
        }

        // Check for clubs without licenses
        $inactive_clubs = $wpdb->get_var("
            SELECT COUNT(DISTINCT c.id) FROM {$wpdb->prefix}ufsc_clubs c
            LEFT JOIN {$wpdb->prefix}ufsc_licences l ON c.id = l.club_id
            WHERE l.id IS NULL
        ");
        if ($inactive_clubs > 0) {
            $alerts[] = ['type' => 'info', 'message' => "$inactive_clubs club(s) sans licence"];
        }

        echo '<div class="ufsc-alerts-widget">';
        echo '<div class="ufsc-alerts-icon">⚠️</div>';
        echo '<div class="ufsc-alerts-content">';
        echo '<h3>Alertes & Actions</h3>';
        if (empty($alerts)) {
            echo '<p class="ufsc-no-alerts">Aucune alerte 👍</p>';
        } else {
            echo '<ul class="ufsc-alerts-list">';
            foreach ($alerts as $alert) {
                echo '<li class="ufsc-alert-' . esc_attr($alert['type']) . '">' . esc_html($alert['message']) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render gender distribution pie chart
     */
    private function render_gender_chart()
    {
        echo '<div class="ufsc-chart-widget">';
        echo '<h3>👥 Licenciés par sexe</h3>';
        echo '<div class="ufsc-chart-container">';
        echo '<canvas id="genderChart" width="300" height="200"></canvas>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render region distribution histogram
     */
    private function render_region_chart()
    {
        echo '<div class="ufsc-chart-widget">';
        echo '<h3>🗺️ Licenciés par région UFSC</h3>';
        echo '<div class="ufsc-chart-container">';
        echo '<canvas id="regionChart" width="300" height="200"></canvas>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render competition vs leisure chart
     */
    private function render_competition_chart()
    {
        echo '<div class="ufsc-chart-widget">';
        echo '<h3>🏆 Compétiteurs vs Loisirs</h3>';
        echo '<div class="ufsc-chart-container">';
        echo '<canvas id="competitionChart" width="300" height="200"></canvas>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render age groups chart
     */
    private function render_age_groups_chart()
    {
        echo '<div class="ufsc-chart-widget">';
        echo '<h3>🎂 Répartition par tranches d\'âge</h3>';
        echo '<div class="ufsc-chart-container">';
        echo '<canvas id="ageGroupsChart" width="300" height="200"></canvas>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render employment status chart
     */
    private function render_employment_status_chart()
    {
        echo '<div class="ufsc-chart-widget">';
        echo '<h3>💼 Licences par statut d\'emploi</h3>';
        echo '<div class="ufsc-chart-container">';
        echo '<canvas id="employmentChart" width="300" height="200"></canvas>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render volunteer ratio chart
     */
    private function render_volunteer_ratio_chart()
    {
        echo '<div class="ufsc-chart-widget">';
        echo '<h3>🤝 Ratio bénévoles/postiers</h3>';
        echo '<div class="ufsc-chart-container">';
        echo '<canvas id="volunteerChart" width="300" height="200"></canvas>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render license evolution line chart
     */
    private function render_license_evolution_chart()
    {
        echo '<div class="ufsc-chart-widget ufsc-chart-wide">';
        echo '<h3>📈 Évolution du nombre de licences sur l\'année</h3>';
        echo '<div class="ufsc-chart-container">';
        echo '<canvas id="evolutionChart" width="600" height="300"></canvas>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render top clubs widget
     */
    private function render_top_clubs_widget()
    {
        global $wpdb;
        
        $top_clubs = $wpdb->get_results("
            SELECT c.nom, COUNT(l.id) as licence_count
            FROM {$wpdb->prefix}ufsc_clubs c
            LEFT JOIN {$wpdb->prefix}ufsc_licences l ON c.id = l.club_id
            GROUP BY c.id, c.nom
            ORDER BY licence_count DESC
            LIMIT 5
        ");

        echo '<div class="ufsc-widget">';
        echo '<h3>🏅 Top 5 clubs les plus actifs</h3>';
        echo '<div class="ufsc-top-clubs-list">';
        if ($top_clubs) {
            foreach ($top_clubs as $index => $club) {
                $position = $index + 1;
                echo '<div class="ufsc-top-club-item">';
                echo '<span class="ufsc-club-position">' . $position . '</span>';
                echo '<span class="ufsc-club-name">' . esc_html($club->nom) . '</span>';
                echo '<span class="ufsc-club-count">' . esc_html($club->licence_count) . ' licences</span>';
                echo '</div>';
            }
        } else {
            echo '<p>Aucun club trouvé</p>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render recent activities timeline widget
     */
    private function render_recent_activities_widget()
    {
        global $wpdb;
        
        $recent_activities = $wpdb->get_results("
            (SELECT 'licence' as type, CONCAT(nom, ' ', prenom) as title, date_inscription as date_action
             FROM {$wpdb->prefix}ufsc_licences 
             ORDER BY date_inscription DESC LIMIT 3)
            UNION ALL
            (SELECT 'club' as type, nom as title, date_creation as date_action
             FROM {$wpdb->prefix}ufsc_clubs 
             ORDER BY date_creation DESC LIMIT 3)
            ORDER BY date_action DESC LIMIT 5
        ");

        echo '<div class="ufsc-widget">';
        echo '<h3>⏰ Dernières actions</h3>';
        echo '<div class="ufsc-timeline">';
        if ($recent_activities) {
            foreach ($recent_activities as $activity) {
                $icon = $activity->type === 'licence' ? '👤' : '🏢';
                $type_label = $activity->type === 'licence' ? 'Nouvelle licence' : 'Nouveau club';
                echo '<div class="ufsc-timeline-item">';
                echo '<div class="ufsc-timeline-icon">' . $icon . '</div>';
                echo '<div class="ufsc-timeline-content">';
                echo '<div class="ufsc-timeline-title">' . esc_html($activity->title) . '</div>';
                echo '<div class="ufsc-timeline-meta">' . $type_label . ' • ' . esc_html(date('d/m/Y', strtotime($activity->date_action))) . '</div>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>Aucune activité récente</p>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Get data aggregation methods
     */
    private function get_current_year_licenses()
    {
        global $wpdb;
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences 
            WHERE YEAR(date_inscription) = YEAR(CURDATE())
        ");
    }

    private function get_monthly_growth()
    {
        global $wpdb;
        $this_month = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences 
            WHERE YEAR(date_inscription) = YEAR(CURDATE()) 
            AND MONTH(date_inscription) = MONTH(CURDATE())
        ");
        $last_month = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences 
            WHERE date_inscription >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
            AND date_inscription < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
        ");
        
        if ($last_month == 0) return $this_month > 0 ? '+100%' : '0%';
        $growth = (($this_month - $last_month) / $last_month) * 100;
        return ($growth >= 0 ? '+' : '') . round($growth, 1) . '%';
    }

    /**
     * Get chart data for JavaScript
     */
    public function get_chart_data($chart_type)
    {
        global $wpdb;
        
        switch ($chart_type) {
            case 'gender':
                return $wpdb->get_results("
                    SELECT sexe as label, COUNT(*) as value 
                    FROM {$wpdb->prefix}ufsc_licences 
                    GROUP BY sexe
                ");
            
            case 'region':
                return $wpdb->get_results("
                    SELECT region as label, COUNT(*) as value 
                    FROM {$wpdb->prefix}ufsc_licences 
                    WHERE region IS NOT NULL AND region != ''
                    GROUP BY region
                    ORDER BY value DESC
                ");
            
            case 'competition':
                return $wpdb->get_results("
                    SELECT 
                        CASE competition 
                            WHEN 1 THEN 'Compétition' 
                            ELSE 'Loisir' 
                        END as label,
                        COUNT(*) as value 
                    FROM {$wpdb->prefix}ufsc_licences 
                    GROUP BY competition
                ");
            
            case 'age_groups':
                return $wpdb->get_results("
                    SELECT 
                        CASE 
                            WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) < 18 THEN 'Moins de 18 ans'
                            WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) BETWEEN 18 AND 25 THEN '18-25 ans'
                            WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) BETWEEN 26 AND 35 THEN '26-35 ans'
                            WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) BETWEEN 36 AND 50 THEN '36-50 ans'
                            WHEN TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) BETWEEN 51 AND 65 THEN '51-65 ans'
                            ELSE 'Plus de 65 ans'
                        END as label,
                        COUNT(*) as value
                    FROM {$wpdb->prefix}ufsc_licences 
                    WHERE date_naissance IS NOT NULL
                    GROUP BY label
                    ORDER BY 
                        CASE label
                            WHEN 'Moins de 18 ans' THEN 1
                            WHEN '18-25 ans' THEN 2
                            WHEN '26-35 ans' THEN 3
                            WHEN '36-50 ans' THEN 4
                            WHEN '51-65 ans' THEN 5
                            ELSE 6
                        END
                ");
            
            case 'employment':
                return $wpdb->get_results("
                    SELECT 
                        CASE fonction_publique 
                            WHEN 1 THEN 'Fonction publique' 
                            ELSE 'Secteur privé' 
                        END as label,
                        COUNT(*) as value 
                    FROM {$wpdb->prefix}ufsc_licences 
                    GROUP BY fonction_publique
                ");
            
            case 'volunteer':
                $benevoles = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences WHERE reduction_benevole = 1");
                $postiers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences WHERE reduction_postier = 1");
                $autres = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences WHERE reduction_benevole = 0 AND reduction_postier = 0");
                
                return [
                    (object)['label' => 'Bénévoles', 'value' => $benevoles],
                    (object)['label' => 'Postiers', 'value' => $postiers],
                    (object)['label' => 'Autres', 'value' => $autres]
                ];
            
            case 'evolution':
                return $wpdb->get_results("
                    SELECT 
                        MONTHNAME(date_inscription) as label,
                        COUNT(*) as value 
                    FROM {$wpdb->prefix}ufsc_licences 
                    WHERE YEAR(date_inscription) = YEAR(CURDATE())
                    GROUP BY MONTH(date_inscription)
                    ORDER BY MONTH(date_inscription)
                ");
        }
        
        return [];
    }

    /**
     * Enqueue chart data for JavaScript
     */
    private function enqueue_chart_data()
    {
        $chart_data = [
            'gender' => $this->get_chart_data('gender'),
            'region' => $this->get_chart_data('region'),
            'competition' => $this->get_chart_data('competition'),
            'age_groups' => $this->get_chart_data('age_groups'),
            'employment' => $this->get_chart_data('employment'),
            'volunteer' => $this->get_chart_data('volunteer'),
            'evolution' => $this->get_chart_data('evolution')
        ];
        
        echo '<script type="text/javascript">';
        echo 'window.ufscChartData = ' . wp_json_encode($chart_data) . ';';
        echo '</script>';
    }

    /**
     * Enqueue dashboard styles
     */
    private function enqueue_dashboard_styles()
    {
        echo '<style>
        .ufsc-dashboard {
            max-width: 1400px;
        }
        .ufsc-search-forms {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .ufsc-search-form {
            flex: 1;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .ufsc-quick-actions {
            display: flex;
            gap: 20px;
            margin: 30px 0;
        }
        .ufsc-quick-action {
            display: flex;
            align-items: center;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-width: 200px;
        }
        .ufsc-quick-action-icon {
            font-size: 2em;
            margin-right: 15px;
        }
        .ufsc-quick-action-content h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .ufsc-quick-action-count {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2271b1;
        }
        .ufsc-alerts-widget {
            display: flex;
            align-items: center;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            min-width: 250px;
        }
        .ufsc-alerts-icon {
            font-size: 2em;
            margin-right: 15px;
        }
        .ufsc-alerts-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }
        .ufsc-alerts-list li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .ufsc-alert-warning {
            color: #f56565;
        }
        .ufsc-alert-info {
            color: #3182ce;
        }
        .ufsc-no-alerts {
            color: #48bb78;
            font-weight: bold;
        }
        .ufsc-stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .ufsc-card {
            background: #fff;
            padding: 20px;
            border-left: 5px solid #2271b1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .ufsc-card h2 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #444;
        }
        .ufsc-card p {
            font-size: 2.5em;
            margin: 0;
            font-weight: bold;
            color: #2271b1;
        }
        .ufsc-charts-grid {
            margin-top: 30px;
        }
        .ufsc-chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .ufsc-chart-widget, .ufsc-widget {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .ufsc-chart-widget.ufsc-chart-wide {
            grid-column: 1 / -1;
        }
        .ufsc-chart-widget h3, .ufsc-widget h3 {
            margin: 0 0 20px 0;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        .ufsc-chart-container {
            position: relative;
            height: 250px;
            max-height: 250px;
        }
        .ufsc-chart-wide .ufsc-chart-container {
            height: 300px;
            max-height: 300px;
        }
        .ufsc-top-clubs-list {
            space-y: 10px;
        }
        .ufsc-top-club-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        .ufsc-club-position {
            background: #2271b1;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
        }
        .ufsc-club-name {
            flex: 1;
            font-weight: 600;
        }
        .ufsc-club-count {
            color: #666;
            font-size: 0.9em;
        }
        .ufsc-timeline {
            space-y: 15px;
        }
        .ufsc-timeline-item {
            display: flex;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .ufsc-timeline-item:last-child {
            border-bottom: none;
        }
        .ufsc-timeline-icon {
            font-size: 1.5em;
            margin-right: 12px;
            margin-top: 2px;
        }
        .ufsc-timeline-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .ufsc-timeline-meta {
            color: #666;
            font-size: 0.85em;
        }
        @media (max-width: 768px) {
            .ufsc-search-forms, .ufsc-quick-actions {
                flex-direction: column;
            }
            .ufsc-chart-row {
                grid-template-columns: 1fr;
            }
        }
        </style>';
    }
}
