<?php

if (!defined('ABSPATH')) {
    exit;
}

function ufsc_render_club_list_page()
{
    global $wpdb;
    
    // Enqueue the CSS and JS for enhanced filters
    wp_enqueue_style(
        'ufsc-admin-style',
        UFSC_PLUGIN_URL . 'assets/css/admin.css',
        [],
        UFSC_GESTION_CLUB_VERSION
    );
    
    // Enqueue DataTables CSS and JS
    wp_enqueue_style(
        'datatables-css',
        'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
        [],
        '1.13.6'
    );
    wp_enqueue_style(
        'datatables-buttons-css',
        'https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css',
        [],
        '2.4.2'
    );
    wp_enqueue_style(
        'datatables-responsive-css',
        'https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css',
        [],
        '2.5.0'
    );
    
    wp_enqueue_script(
        'datatables-js',
        'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
        ['jquery'],
        '1.13.6',
        true
    );
    wp_enqueue_script(
        'datatables-buttons-js',
        'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
        ['datatables-js'],
        '2.4.2',
        true
    );
    wp_enqueue_script(
        'datatables-buttons-html5-js',
        'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
        ['datatables-buttons-js'],
        '2.4.2',
        true
    );
    wp_enqueue_script(
        'datatables-responsive-js',
        'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
        ['datatables-js'],
        '2.5.0',
        true
    );
    wp_enqueue_script(
        'jszip-js',
        'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
        [],
        '3.10.1',
        true
    );
    
    wp_enqueue_script(
        'ufsc-datatables-config',
        UFSC_PLUGIN_URL . 'assets/js/datatables-config.js',
        ['datatables-js', 'datatables-buttons-js', 'datatables-responsive-js'],
        UFSC_GESTION_CLUB_VERSION,
        true
    );
    
    wp_enqueue_script(
        'ufsc-admin-script',
        UFSC_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery', 'ufsc-datatables-config'],
        UFSC_GESTION_CLUB_VERSION,
        true
    );
    
    // Get filter parameters from GET request
    $page = isset($_GET['paged']) ? max(1, intval(wp_unslash($_GET['paged']))) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // Filter parameters
    $search_nom = isset($_GET['search_nom']) ? sanitize_text_field(wp_unslash($_GET['search_nom'])) : '';
    $search_email = isset($_GET['search_email']) ? sanitize_email(wp_unslash($_GET['search_email'])) : '';
    $search_telephone = isset($_GET['search_telephone']) ? sanitize_text_field(wp_unslash($_GET['search_telephone'])) : '';
    $search_numero = isset($_GET['search_numero']) ? sanitize_text_field(wp_unslash($_GET['search_numero'])) : '';
    $region = isset($_GET['region']) ? sanitize_text_field(wp_unslash($_GET['region'])) : '';
    $statut = isset($_GET['statut']) ? sanitize_text_field(wp_unslash($_GET['statut'])) : '';
    $date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
    $date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';
    
    // Build WHERE clause
    $where = [];
    $params = [];
    
    if ($search_nom !== '') {
        $where[] = 'nom LIKE %s';
        $params[] = '%' . $search_nom . '%';
    }
    
    if ($search_email !== '') {
        $where[] = 'email LIKE %s';
        $params[] = '%' . $search_email . '%';
    }
    
    if ($search_telephone !== '') {
        $where[] = 'telephone LIKE %s';
        $params[] = '%' . $search_telephone . '%';
    }
    
    if ($search_numero !== '') {
        $where[] = '(siren LIKE %s OR num_affiliation LIKE %s OR num_declaration LIKE %s)';
        $params[] = '%' . $search_numero . '%';
        $params[] = '%' . $search_numero . '%';
        $params[] = '%' . $search_numero . '%';
    }
    
    if ($region !== '') {
        $where[] = 'region = %s';
        $params[] = $region;
    }
    
    if ($statut !== '') {
        $where[] = 'statut = %s';
        $params[] = $statut;
    }
    
    if ($date_from !== '') {
        $where[] = 'date_creation >= %s';
        $params[] = $date_from;
    }
    
    if ($date_to !== '') {
        $where[] = 'date_creation <= %s';
        $params[] = $date_to . ' 23:59:59';
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Count total items for pagination
    if (!empty($params)) {
        $total_items = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs $where_clause",
            ...$params
        ));
    } else {
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs $where_clause");
    }
    
    // Get paginated data
    $params_limit = array_merge($params, [$per_page, $offset]);
    if (!empty($params)) {
        $clubs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ufsc_clubs $where_clause ORDER BY date_creation DESC LIMIT %d OFFSET %d",
            ...$params_limit
        ));
    } else {
        $clubs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ufsc_clubs $where_clause ORDER BY date_creation DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
    }
    
    // Get regions for filter dropdown
    $regions_path = plugin_dir_path(__FILE__) . '../data/regions.php';
    $regions = file_exists($regions_path) ? require $regions_path : [];
    
    // Available statuses
    $statuts = ['Actif', 'Inactif', 'En attente', 'En cours de validation', 'Suspendu', 'Dissous', 'Refusé'];
    
    // Export CSV functionality
    if (isset($_GET['export_csv']) && check_admin_referer('ufsc_export_clubs')) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=clubs_ufsc_' . gmdate('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Nom', 'Région', 'Ville', 'Email', 'Téléphone', 'SIREN', 'Numéro affiliation', 'Statut', 'Date création']);
        
        // Check if this is a selected export or full export
        if (isset($_GET['export_selected']) && !empty($_GET['selected_ids'])) {
            // Export only selected clubs
            $selected_ids = explode(',', sanitize_text_field($_GET['selected_ids']));
            $selected_ids = array_map('intval', $selected_ids);
            $selected_ids = array_filter($selected_ids, function($id) { return $id > 0; });
            
            if (!empty($selected_ids)) {
                $placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
                $query = "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id IN ($placeholders) ORDER BY date_creation DESC";
                $export_clubs = $wpdb->get_results($wpdb->prepare($query, ...$selected_ids));
            } else {
                $export_clubs = [];
            }
        } else {
            // Export all matching records (not just current page)
            if (!empty($params)) {
                $export_clubs = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ufsc_clubs $where_clause ORDER BY date_creation DESC",
                    ...array_slice($params, 0, -2) // Remove limit/offset params
                ));
            } else {
                $export_clubs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ufsc_clubs $where_clause ORDER BY date_creation DESC");
            }
        }
        
        foreach ($export_clubs as $club) {
            fputcsv($output, [
                $club->id,
                $club->nom,
                $club->region,
                $club->ville,
                $club->email,
                $club->telephone,
                $club->siren,
                $club->num_affiliation,
                $club->statut,
                $club->date_creation
            ]);
        }
        fclose($output);
        exit;
    }
    
    // Calculate pagination
    $total_pages = ceil($total_items / $per_page);
    $base_url = remove_query_arg(['paged', 'export_csv'], wp_unslash($_SERVER['REQUEST_URI']));
    $export_nonce = wp_create_nonce('ufsc_export_clubs');
    ?>
    
    <div class="wrap">
        <h1>Clubs affiliés UFSC</h1>
        
        <!-- Enhanced Filters -->
        <div class="ufsc-filters-container">
            <h3>Filtres de recherche</h3>
            <form method="get" class="ufsc-filters-form">
                <input type="hidden" name="page" value="ufsc_clubs_list">
                
                <div class="ufsc-filters-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div class="ufsc-filter-field">
                        <label for="search_nom">Nom du club</label>
                        <input type="text" name="search_nom" id="search_nom" value="<?php echo esc_attr($search_nom); ?>" placeholder="Rechercher par nom">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="search_email">Email</label>
                        <input type="email" name="search_email" id="search_email" value="<?php echo esc_attr($search_email); ?>" placeholder="Rechercher par email">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="search_telephone">Téléphone</label>
                        <input type="text" name="search_telephone" id="search_telephone" value="<?php echo esc_attr($search_telephone); ?>" placeholder="Rechercher par téléphone">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="search_numero">Numéro (SIREN/Code/Affiliation)</label>
                        <input type="text" name="search_numero" id="search_numero" value="<?php echo esc_attr($search_numero); ?>" placeholder="SIREN, affiliation, déclaration...">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="region">Région</label>
                        <select name="region" id="region">
                            <option value="">Toutes les régions</option>
                            <?php foreach ($regions as $reg): ?>
                                <option value="<?php echo esc_attr($reg); ?>" <?php selected($region, $reg); ?>>
                                    <?php echo esc_html($reg); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="statut">Statut</label>
                        <select name="statut" id="statut">
                            <option value="">Tous les statuts</option>
                            <?php foreach ($statuts as $s): ?>
                                <option value="<?php echo esc_attr($s); ?>" <?php selected($statut, $s); ?>>
                                    <?php echo esc_html($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="date_from">Date d'inscription (de)</label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                    </div>
                    
                    <div class="ufsc-filter-field">
                        <label for="date_to">Date d'inscription (à)</label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                    </div>
                </div>
                
                <div class="ufsc-filters-actions" style="margin-bottom: 20px;">
                    <button type="submit" class="button button-primary">Filtrer</button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_clubs_list')); ?>" class="button">Réinitialiser</a>
                    
                    <!-- Export buttons -->
                    <button type="button" id="ufsc-export-selected-clubs" class="button button-secondary" disabled>
                        📊 Exporter la sélection (<span id="ufsc-club-selection-count">0</span>)
                    </button>
                    <a href="<?php echo esc_url(add_query_arg(['export_csv' => '1', '_wpnonce' => $export_nonce], $base_url)); ?>" class="button button-secondary">
                        📊 Exporter tout
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Results summary -->
        <p><strong><?php echo esc_html($total_items); ?></strong> club(s) trouvé(s)</p>
        
        <?php if (empty($clubs)): ?>
            <div class="notice notice-warning">
                <p>Aucun club trouvé avec les critères de recherche actuels.</p>
            </div>
        <?php else: ?>
            <!-- Enhanced table with DataTables integration -->
            <table id="clubs-table" class="widefat fixed striped ufsc-table display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="ufsc-select-all-clubs" title="Sélectionner tout"></th>
                        <th style="width: 20%;">Nom</th>
                        <th style="width: 12%;">Région</th>
                        <th style="width: 12%;">Ville</th>
                        <th style="width: 12%;">Statut</th>
                        <th style="width: 15%;">Utilisateur associé</th>
                        <th style="width: 12%;">Date création</th>
                        <th style="width: 13%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clubs as $club): ?>
                        <?php
                        $edit_url = admin_url('admin.php?page=ufsc_edit_club&id=' . $club->id);
                        $lic_url  = admin_url('admin.php?page=ufsc_voir_licences&club_id=' . $club->id);
                        
                        // Get license count for this club
                        $licence_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences WHERE club_id = %d",
                            $club->id
                        ));
                        
                        // Status badge styling
                        $status_class = '';
                        switch(strtolower($club->statut)) {
                            case 'actif':
                                $status_class = 'badge-green';
                                break;
                            case 'inactif':
                            case 'suspendu':
                            case 'refusé':
                                $status_class = 'badge-red';
                                break;
                            default:
                                $status_class = 'badge-orange';
                        }
                        ?>
                        <tr>
                            <td><input type="checkbox" name="club_ids[]" value="<?php echo esc_attr($club->id); ?>" class="ufsc-club-checkbox"></td>
                            <td>
                                <strong><?php echo esc_html($club->nom); ?></strong>
                                <?php if ($club->email): ?>
                                    <br><small><?php echo esc_html($club->email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($club->region); ?></td>
                            <td><?php echo esc_html($club->ville); ?></td>
                            <td>
                                <span class="ufsc-badge <?php echo esc_attr($status_class); ?>">
                                    <?php echo esc_html($club->statut); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($club->responsable_id) {
                                    $user_info = ufsc_get_user_display_info($club->responsable_id);
                                    if ($user_info) {
                                        echo '<strong>' . esc_html($user_info->display_name) . '</strong>';
                                        echo '<br><small>' . esc_html($user_info->login) . '</small>';
                                        echo '<br><small>' . esc_html($user_info->email) . '</small>';
                                    } else {
                                        echo '<span style="color: #d63638;">Utilisateur introuvable</span>';
                                    }
                                } else {
                                    echo '<span style="color: #8c8f94;">Aucun utilisateur</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo esc_html(gmdate('d/m/Y', strtotime($club->date_creation))); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Modifier</a>
                                <a href="<?php echo esc_url($lic_url); ?>" class="button button-small">
                                    Licences (<?php echo esc_html($licence_count); ?>)
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Note: Pagination is now handled by DataTables -->
        <?php endif; ?>
    </div>
    
    <style>
    .ufsc-filters-container {
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 4px;
        padding: 20px;
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
    
    .badge-orange {
        background: #ff8c00;
    }
    
    .badge-blue {
        background: #007cba;
    }
    
    .ufsc-table th {
        background: #f7f7f7;
        font-weight: 600;
    }
    
    /* DataTables integration styling */
    .dataTables_wrapper {
        margin-top: 20px;
    }
    
    .dataTables_filter {
        margin-bottom: 10px;
    }
    
    .ufsc-quick-filters {
        margin-top: 10px;
        padding: 10px;
        background: #f9f9f9;
        border-radius: 4px;
    }
    
    .ufsc-quick-filters span {
        font-weight: 600;
        margin-right: 10px;
    }
    
    .ufsc-quick-filters button {
        margin-right: 5px;
        padding: 4px 8px;
        font-size: 12px;
    }
    
    /* Enhanced status badges */
    .ufsc-badge {
        display: inline-block;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 600;
        border-radius: 12px;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-green {
        background: linear-gradient(135deg, #46b450, #4CAF50);
        box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
    }
    
    .badge-red {
        background: linear-gradient(135deg, #dc3232, #f44336);
        box-shadow: 0 2px 4px rgba(244, 67, 54, 0.3);
    }
    
    .badge-orange {
        background: linear-gradient(135deg, #ff8c00, #FF9800);
        box-shadow: 0 2px 4px rgba(255, 152, 0, 0.3);
    }
    
    .badge-blue {
        background: linear-gradient(135deg, #007cba, #2196F3);
        box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
    }
    
    /* Responsive improvements */
    @media (max-width: 768px) {
        .ufsc-filters-grid {
            grid-template-columns: 1fr !important;
        }
        
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            text-align: center;
            margin-bottom: 10px;
        }
    }
    </style>

    <script>
    // Club selection and export functionality
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('ufsc-select-all-clubs');
        const clubCheckboxes = document.querySelectorAll('.ufsc-club-checkbox');
        const exportSelectedBtn = document.getElementById('ufsc-export-selected-clubs');
        const selectionCount = document.getElementById('ufsc-club-selection-count');

        // Update selection count and export button state
        function updateSelectionState() {
            const checkedBoxes = document.querySelectorAll('.ufsc-club-checkbox:checked');
            const count = checkedBoxes.length;
            
            selectionCount.textContent = count;
            exportSelectedBtn.disabled = count === 0;
            
            // Update select all checkbox state
            if (count === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (count === clubCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
                selectAllCheckbox.checked = false;
            }
        }

        // Handle select all checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                clubCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelectionState();
            });
        }

        // Handle individual checkbox changes
        clubCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectionState);
        });

        // Handle export selected button
        if (exportSelectedBtn) {
            exportSelectedBtn.addEventListener('click', function() {
                const checkedBoxes = document.querySelectorAll('.ufsc-club-checkbox:checked');
                if (checkedBoxes.length === 0) {
                    alert('Veuillez sélectionner au moins un club à exporter.');
                    return;
                }

                const selectedIds = Array.from(checkedBoxes).map(cb => cb.value);
                
                // Create a form to submit the export request
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = '<?php echo admin_url('admin.php'); ?>';
                
                // Add hidden fields
                const fields = {
                    'page': 'ufsc_clubs_list',
                    'export_csv': '1',
                    'export_selected': '1',
                    'selected_ids': selectedIds.join(','),
                    '_wpnonce': '<?php echo wp_create_nonce('ufsc_export_clubs'); ?>'
                };
                
                Object.keys(fields).forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = fields[key];
                    form.appendChild(input);
                });
                
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        }

        // Initialize state
        updateSelectionState();
    });
    </script>

    <style>
    /* Export buttons styling */
    .ufsc-export-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    #ufsc-export-selected:disabled,
    #ufsc-export-selected-clubs:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    .ufsc-filters-actions .button {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    /* Checkbox styling improvements */
    .ufsc-licence-checkbox,
    .ufsc-club-checkbox {
        transform: scale(1.1);
        margin: 0;
    }
    
    #ufsc-select-all,
    #ufsc-select-all-clubs {
        transform: scale(1.1);
        margin: 0;
    }
    
    /* Selection count styling */
    #ufsc-selection-count,
    #ufsc-club-selection-count {
        font-weight: bold;
        color: #0073aa;
    }
    </style>

    <?php
}
