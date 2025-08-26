<?php
if (!defined('ABSPATH')) {
    exit;
} // 🔐 Sécurité

// Include UFSC CSV Export helper
require_once plugin_dir_path(__FILE__) . '../helpers/class-ufsc-csv-export.php';
require_once plugin_dir_path(__FILE__) . '../helpers.php';
require_once plugin_dir_path(__FILE__) . '../helpers/helpers-licence-status.php';
require_once plugin_dir_path(__FILE__) . 'class-licence-filters.php';

global $wpdb;

// Enqueue the license form CSS for filters
wp_enqueue_style(
    'ufsc-licence-form-style',
    UFSC_PLUGIN_URL . 'assets/css/form-licence.css',
    [],
    UFSC_GESTION_CLUB_VERSION
);

// Enqueue the admin license table CSS
wp_enqueue_style(
    'ufsc-admin-licence-table-style',
    UFSC_PLUGIN_URL . 'assets/css/admin-licence-table.css',
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

// Get club ID and verify club exists
$club_id = isset($_GET['club_id']) ? intval(wp_unslash($_GET['club_id'])) : 0;

// 🔎 Vérification du club
$club = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id = %d",
    $club_id
));
if (!$club) {
    echo '<div class="notice notice-error"><p>Club introuvable.</p></div>';
    return;
}

// Get filter parameters with club_id override
$filters = UFSC_Licence_Filters::get_filter_parameters(['club_id' => $club_id]);

// 📤 Export CSV
if (isset($_GET['export_csv']) && check_admin_referer('ufsc_export_licences_' . $club_id)) {
    // Check if this is a selected export or full export
    if (isset($_GET['export_selected']) && !empty($_GET['selected_ids'])) {
        // Export only selected licences
        $selected_ids = explode(',', sanitize_text_field($_GET['selected_ids']));
        $selected_ids = array_map('intval', $selected_ids);
        $selected_ids = array_filter($selected_ids, function($id) { return $id > 0; });
        
        if (!empty($selected_ids)) {
            $placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
            $query = "SELECT l.id, l.nom, l.prenom, l.sexe, l.date_naissance, l.email, l.region, l.ville, l.competition, l.is_included, l.date_inscription, l.adresse, l.suite_adresse, l.code_postal, l.tel_fixe, l.tel_mobile, l.profession, c.nom as club_nom
                     FROM {$wpdb->prefix}ufsc_licences l
                     LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                     WHERE l.id IN ($placeholders) AND l.club_id = %d
                     ORDER BY l.date_inscription DESC";
            
            $rows = $wpdb->get_results($wpdb->prepare($query, ...array_merge($selected_ids, [$club_id])));
        } else {
            $rows = [];
        }
    } else {
        // Export all with filters
        $where_data = UFSC_Licence_Filters::build_where_clause($filters);
        $where_clause = $where_data['where_clause'];
        $params = $where_data['params'];

        if (!empty($params)) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT l.id, l.nom, l.prenom, l.sexe, l.date_naissance, l.email, l.region, l.ville, l.competition, l.is_included, l.date_inscription, l.adresse, l.suite_adresse, l.code_postal, l.tel_fixe, l.tel_mobile, l.profession, c.nom as club_nom
                 FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause
                 ORDER BY l.date_inscription DESC",
                ...$params
            ));
        } else {
            $rows = $wpdb->get_results(
                "SELECT l.id, l.nom, l.prenom, l.sexe, l.date_naissance, l.email, l.region, l.ville, l.competition, l.is_included, l.date_inscription, l.adresse, l.suite_adresse, l.code_postal, l.tel_fixe, l.tel_mobile, l.profession, c.nom as club_nom
                 FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause
                 ORDER BY l.date_inscription DESC"
            );
        }
    }

    // Use UFSC-compliant export
    $filename = 'licences_' . sanitize_file_name($club->nom) . '_' . date('Y-m-d') . '.csv';
    UFSC_CSV_Export::export_licenses($rows, $filename);
}

// Get filtered license data using the filter system
$license_data = UFSC_Licence_Filters::get_filtered_licenses($filters);
$data = $license_data['data'];
$total_items = $license_data['total_items'];
$per_page = $license_data['per_page'];
$current_page = $license_data['current_page'];

// 🔗 URL & nonce pour export
$base_url = remove_query_arg(['paged', 'export_csv'], wp_unslash($_SERVER['REQUEST_URI']));
$export_nonce = wp_create_nonce('ufsc_export_licences_' . $club_id);
?>

<div class="wrap">
    <h1>Licences <?php echo $club ? '– ' . esc_html($club->nom) : ''; ?></h1>

    <!-- Render enhanced filters using the new filter component -->
    <?php 
    UFSC_Licence_Filters::render_filters_form($filters, 'ufsc_voir_licences', $club_id, true);
    UFSC_Licence_Filters::render_filters_css();
    ?>

    <!-- Results summary and export buttons -->
    <div class="ufsc-results-summary">
        <p><strong><?php echo esc_html($total_items); ?></strong> licence(s) trouvée(s) pour ce club</p>
        
        <!-- Export action buttons -->
        <div class="ufsc-export-actions" style="margin-top: 10px;">
            <button type="button" id="ufsc-export-selected" class="button button-secondary" disabled>
                📊 Exporter la sélection (<span id="ufsc-selection-count">0</span>)
            </button>
            <a href="<?php echo esc_url(add_query_arg(['export_csv' => '1', '_wpnonce' => $export_nonce], $base_url)); ?>" class="button button-secondary">
                📊 Exporter tout
            </a>
        </div>
        
        <!-- Bulk validation actions -->
        <?php if (current_user_can('manage_ufsc')): ?>
        <div class="ufsc-bulk-actions" style="margin-top: 10px;">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block;">
                <?php wp_nonce_field('bulk-licences'); ?>
                <input type="hidden" name="action" value="ufsc_bulk_validate_licences">
                <button type="button" id="ufsc-bulk-validate" class="button button-primary" disabled>
                    ✅ Valider la sélection (<span id="ufsc-validation-count">0</span>)
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- 📋 Tableau des licences avec DataTables -->
    <div class="ufsc-licences-table-wrapper">
        <table id="licenses-table-club" class="widefat fixed striped display nowrap ufsc-licences-table" style="width:100%">
            <thead>
            <tr>
                <th style="width: 40px;"><input type="checkbox" id="ufsc-select-all" title="Sélectionner tout"></th>
                <th>ID</th><th>Nom</th><th>Prénom</th><th>Sexe</th><th>Naissance</th>
                <th>Email</th><th>Ville</th><th>Région</th><th>Club</th><th>Statut</th><th>Compétition</th><th>Inclus</th><th>Inscrit</th><th>Attestation</th><th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($data): foreach ($data as $lic): ?>
                <tr>
                    <td><input type="checkbox" name="licence_ids[]" value="<?php echo esc_attr($lic->id); ?>" class="ufsc-licence-checkbox"></td>
                    <td><?php echo esc_html($lic->id); ?></td>
                    <td><?php echo esc_html($lic->nom); ?></td>
                    <td><?php echo esc_html($lic->prenom); ?></td>
                    <td>
                        <span class="ufsc-badge <?php echo $lic->sexe === 'F' ? 'badge-pink' : 'badge-blue'; ?>">
                            <?php echo $lic->sexe === 'F' ? 'Femme' : 'Homme'; ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($lic->date_naissance); ?></td>
                    <td><?php echo esc_html($lic->email); ?></td>
                    <td><?php echo esc_html($lic->ville); ?></td>
                    <td><?php echo esc_html($lic->region); ?></td>
                    <td><strong><?php echo esc_html($lic->club_nom); ?></strong></td>
                    <td>
                        <?php
                        $statut = $lic->statut ?? 'en_attente';
                    $statut_labels = [
                        'en_attente' => 'En attente',
                        'validee' => 'Validée',
                        'refusee' => 'Refusée'
                    ];
                    $statut_colors = [
                        'en_attente' => 'badge-orange',
                        'validee' => 'badge-green',
                        'refusee' => 'badge-red'
                    ];
                    ?>
                    <span class="ufsc-badge <?php echo $statut_colors[$statut] ?? 'badge-gray'; ?>">
                        <?php echo esc_html($statut_labels[$statut] ?? 'Inconnu'); ?>
                    </span>
                </td>
                <td>
                    <span class="ufsc-badge <?php echo $lic->competition ? 'badge-green' : 'badge-orange'; ?>">
                        <?php echo $lic->competition ? 'Compétition' : 'Loisir'; ?>
                    </span>
                </td>
                <td>
                    <span class="ufsc-badge <?php echo $lic->is_included ? 'badge-green' : 'badge-red'; ?>">
                        <?php echo $lic->is_included ? 'Inclus' : 'Payant'; ?>
                    </span>
                </td>
                <td><?php echo esc_html(gmdate('d/m/Y', strtotime($lic->date_inscription))); ?></td>
                <td>
                    <?php 
                    $attestation_url = $lic->attestation_url ?? null;
                    if ($attestation_url): 
                        $download_nonce = wp_create_nonce('ufsc_download_licence_attestation_' . $lic->id);
                        $download_url = add_query_arg([
                            'action' => 'ufsc_download_licence_attestation',
                            'licence_id' => $lic->id,
                            'nonce' => $download_nonce
                        ], admin_url('admin-ajax.php'));
                    ?>
                        <div class="ufsc-attestation-cell">
                            <a href="<?php echo esc_url($download_url); ?>" 
                               class="button button-small" 
                               title="Télécharger l'attestation">
                                📄 Télécharger
                            </a>
                            <button type="button" 
                                    class="button button-small ufsc-replace-licence-attestation" 
                                    data-licence-id="<?php echo esc_attr($lic->id); ?>"
                                    title="Remplacer l'attestation">
                                🔄 Remplacer
                            </button>
                            <button type="button" 
                                    class="button button-small ufsc-delete-licence-attestation" 
                                    data-licence-id="<?php echo esc_attr($lic->id); ?>"
                                    title="Supprimer l'attestation">
                                🗑️ Supprimer
                            </button>
                        </div>
                    <?php else: ?>
                        <button type="button" 
                                class="button button-small ufsc-upload-licence-attestation" 
                                data-licence-id="<?php echo esc_attr($lic->id); ?>"
                                title="Ajouter une attestation">
                            ➕ Ajouter
                        </button>
                    <?php endif; ?>
                </td>
                <td class="ufsc-col-actions">
                    <div class="ufsc-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_view_licence&id=' . $lic->id)); ?>" 
                           class="button button-small" title="Voir les détails">
                            👁️ Voir
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc-modifier-licence&licence_id=' . $lic->id)); ?>" 
                           class="button button-small button-primary" title="Modifier la licence">
                            ✏️ Modifier
                        </a>
                        
                        <?php 
                        // Admin can validate/reject regardless of payment status
                        $status = ufsc_normalize_licence_status($lic->statut ?? '');
                        ?>
                        
                        <?php if (ufsc_is_pending_status($status)): ?>
                            <button type="button" 
                                    class="button button-small button-secondary validate-licence-btn" 
                                    data-licence-id="<?php echo esc_attr($lic->id); ?>"
                                    data-licence-name="<?php echo esc_attr($lic->prenom . ' ' . $lic->nom); ?>"
                                    title="Valider la licence">
                                ✅ Valider
                            </button>
                            
                            <button type="button" 
                                    class="button button-small button-secondary reject-licence-btn" 
                                    data-licence-id="<?php echo esc_attr($lic->id); ?>"
                                    data-licence-name="<?php echo esc_attr($lic->prenom . ' ' . $lic->nom); ?>"
                                    title="Refuser la licence">
                                ❌ Refuser
                            </button>
                        <?php elseif ($status === 'validee'): ?>
                            <span class="ufsc-status-badge ufsc-status-validated">✅ Validée</span>
                            <button type="button" 
                                    class="button button-small button-secondary reject-licence-btn" 
                                    data-licence-id="<?php echo esc_attr($lic->id); ?>"
                                    data-licence-name="<?php echo esc_attr($lic->prenom . ' ' . $lic->nom); ?>"
                                    title="Annuler la validation">
                                ❌ Annuler
                            </button>
                        <?php elseif ($status === 'refusee'): ?>
                            <button type="button" 
                                    class="button button-small button-secondary validate-licence-btn" 
                                    data-licence-id="<?php echo esc_attr($lic->id); ?>"
                                    data-licence-name="<?php echo esc_attr($lic->prenom . ' ' . $lic->nom); ?>"
                                    title="Valider la licence">
                                ✅ Valider
                            </button>
                            <span class="ufsc-status-badge ufsc-status-refused">❌ Refusée</span>
                        <?php else: ?>
                            <span class="ufsc-status-badge ufsc-status-draft">📝 <?php echo esc_html(ucfirst($status)); ?></span>
                        <?php endif; ?>
                        
                        <button type="button" 
                                class="button button-small button-link-delete delete-licence-btn" 
                                data-licence-id="<?php echo esc_attr($lic->id); ?>"
                                data-licence-name="<?php echo esc_attr($lic->prenom . ' ' . $lic->nom); ?>"
                                title="Supprimer la licence">
                            🗑️ Supprimer
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach;
        else: ?>
            <tr><td colspan="15">Aucune licence trouvée avec les critères sélectionnés.</td></tr>
        <?php endif; ?>
        </tbody>
        </table>
    </div>

</div>

<script>
// Licence selection and export functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('ufsc-select-all');
    const licenceCheckboxes = document.querySelectorAll('.ufsc-licence-checkbox');
    const exportSelectedBtn = document.getElementById('ufsc-export-selected');
    const selectionCount = document.getElementById('ufsc-selection-count');
    const bulkValidateBtn = document.getElementById('ufsc-bulk-validate');
    const validationCount = document.getElementById('ufsc-validation-count');

    // Update selection count and button states
    function updateSelectionState() {
        const checkedBoxes = document.querySelectorAll('.ufsc-licence-checkbox:checked');
        const count = checkedBoxes.length;
        
        // Count pending licences for validation
        const pendingCheckedBoxes = Array.from(checkedBoxes).filter(checkbox => {
            const row = checkbox.closest('tr');
            const statusBadge = row.querySelector('.ufsc-badge');
            return statusBadge && (statusBadge.textContent.includes('En attente') || statusBadge.textContent.includes('attente'));
        });
        const pendingCount = pendingCheckedBoxes.length;
        
        selectionCount.textContent = count;
        exportSelectedBtn.disabled = count === 0;
        
        if (validationCount) {
            validationCount.textContent = pendingCount;
        }
        if (bulkValidateBtn) {
            bulkValidateBtn.disabled = pendingCount === 0;
        }
        
        // Update select all checkbox state
        if (count === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (count === licenceCheckboxes.length) {
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
            licenceCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectionState();
        });
    }

    // Handle individual checkbox changes
    licenceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectionState);
    });

    // Handle export selected button
    if (exportSelectedBtn) {
        exportSelectedBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.ufsc-licence-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Veuillez sélectionner au moins une licence à exporter.');
                return;
            }

            const selectedIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            // Create a form to submit the export request
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = '<?php echo admin_url('admin.php'); ?>';
            
            // Add hidden fields
            const fields = {
                'page': '<?php echo esc_js($_GET['page'] ?? ''); ?>',
                'club_id': '<?php echo esc_js($club_id); ?>',
                'export_csv': '1',
                'export_selected': '1',
                'selected_ids': selectedIds.join(','),
                '_wpnonce': '<?php echo wp_create_nonce('ufsc_export_licences_' . $club_id); ?>'
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

    // Handle bulk validation button
    if (bulkValidateBtn) {
        bulkValidateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const checkedBoxes = document.querySelectorAll('.ufsc-licence-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Veuillez sélectionner au moins une licence à valider.');
                return;
            }

            // Filter for pending licences only
            const pendingBoxes = Array.from(checkedBoxes).filter(checkbox => {
                const row = checkbox.closest('tr');
                const statusBadge = row.querySelector('.ufsc-badge');
                return statusBadge && (statusBadge.textContent.includes('En attente') || statusBadge.textContent.includes('attente'));
            });

            if (pendingBoxes.length === 0) {
                alert('Aucune licence en attente sélectionnée. Seules les licences en attente peuvent être validées.');
                return;
            }

            const pendingCount = pendingBoxes.length;
            if (!confirm(`Confirmer la validation de ${pendingCount} licence(s) en attente ?`)) {
                return;
            }

            const selectedIds = pendingBoxes.map(cb => cb.value);
            
            // Get the form and add hidden inputs for selected licences
            const form = bulkValidateBtn.closest('form');
            if (form) {
                // Remove any existing licence inputs
                const existingInputs = form.querySelectorAll('input[name="licence[]"]');
                existingInputs.forEach(input => input.remove());
                
                // Add hidden inputs for each selected licence
                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'licence[]';
                    input.value = id;
                    form.appendChild(input);
                });
                
                // Submit the form
                form.submit();
            }
        });
    }

    // Initialize state
    updateSelectionState();
    
    // Handle individual licence validation/rejection
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('validate-licence-btn')) {
            e.preventDefault();
            
            const licenceId = e.target.dataset.licenceId;
            const licenceName = e.target.dataset.licenceName;
            
            if (!confirm(`Confirmer la validation de la licence pour ${licenceName} ?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'ufsc_validate_licence');
            formData.append('licence_id', licenceId);
            formData.append('nonce', '<?php echo wp_create_nonce('ufsc_admin_licence_action'); ?>');
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Erreur: ' + (data.data ? data.data : 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur de communication avec le serveur');
            });
        }
        
        if (e.target.classList.contains('reject-licence-btn')) {
            e.preventDefault();
            
            const licenceId = e.target.dataset.licenceId;
            const licenceName = e.target.dataset.licenceName;
            
            const reason = prompt(`Motif de refus pour la licence de ${licenceName} (optionnel):`, '');
            if (reason === null) {
                return; // User cancelled
            }
            
            const formData = new FormData();
            formData.append('action', 'ufsc_reject_licence');
            formData.append('licence_id', licenceId);
            formData.append('reason', reason);
            formData.append('nonce', '<?php echo wp_create_nonce('ufsc_admin_licence_action'); ?>');
            
            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Erreur: ' + (data.data ? data.data : 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erreur de communication avec le serveur');
            });
        }
    });
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

.ufsc-export-actions .button {
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
