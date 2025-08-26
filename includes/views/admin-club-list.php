<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once plugin_dir_path(__FILE__) . '../helpers.php';
// Include UFSC CSV Export helper
require_once plugin_dir_path(__FILE__) . '../helpers/class-ufsc-csv-export.php';

// Handle CSV export for selected clubs
if (isset($_POST['export_selected']) && isset($_POST['selected_clubs']) && is_array($_POST['selected_clubs'])) {
    // Verify nonce
    if (!wp_verify_nonce(wp_unslash($_POST['export_nonce'] ?? ''), 'export_clubs_csv')) {
        wp_die('Security check failed');
    }
    
    $selected_ids = array_map('intval', $_POST['selected_clubs']);
    if (!empty($selected_ids)) {
        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
        
        $clubs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id IN ($placeholders) ORDER BY nom ASC",
            ...$selected_ids
        ));
        
        if (!empty($clubs)) {
            \UFSC\Helpers\CSVExport::export_clubs($clubs, 'clubs_ufsc_selection_' . gmdate('Y-m-d') . '.csv');
        }
    }
}

$search   = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$region   = isset($_GET['region']) ? sanitize_text_field(wp_unslash($_GET['region'])) : '';
$statut   = isset($_GET['statut']) ? sanitize_text_field(wp_unslash($_GET['statut'])) : '';
$date_affiliation_from = isset($_GET['date_affiliation_from']) ? sanitize_text_field(wp_unslash($_GET['date_affiliation_from'])) : '';
$date_affiliation_to = isset($_GET['date_affiliation_to']) ? sanitize_text_field(wp_unslash($_GET['date_affiliation_to'])) : '';
$page     = isset($_GET['paged']) ? max(1, intval(wp_unslash($_GET['paged']))) : 1;
$per_page = 20;
$offset   = ($page - 1) * $per_page;

global $wpdb;
$where_sql = ['1=1'];
$params = [];

if ($search) {
    $where_sql[] = 'nom LIKE %s';
    $params[] = '%' . $search . '%';
}
if ($region) {
    $where_sql[] = 'region = %s';
    $params[] = $region;
}
if ($statut) {
    $where_sql[] = 'statut = %s';
    $params[] = $statut;
}
if ($date_affiliation_from) {
    $where_sql[] = 'DATE(date_creation) >= %s';
    $params[] = $date_affiliation_from;
}
if ($date_affiliation_to) {
    $where_sql[] = 'DATE(date_creation) <= %s';
    $params[] = $date_affiliation_to;
}

$where_clause = implode(' AND ', $where_sql);

// 🔢 Total clubs
if (!empty($params)) {
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs WHERE $where_clause",
        ...$params
    ));
} else {
    // Safe query when no parameters - $where_clause only contains '1=1'
    $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs WHERE 1=1");
}

// 📥 Clubs paginés
$params_limit = array_merge($params, [$per_page, $offset]);
if (!empty($params)) {
    $clubs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE $where_clause ORDER BY nom ASC LIMIT %d OFFSET %d",
        ...$params_limit
    ));
} else {
    // Safe query when no parameters - using prepare for consistency
    $clubs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE 1=1 ORDER BY nom ASC LIMIT %d OFFSET %d",
        $per_page, $offset
    ));
}

$base_url = remove_query_arg('paged', isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '');
?>

<div class="wrap">
    <h1>Liste des clubs</h1>

    <div class="ufsc-filters-container">
        <h3>Filtres de recherche</h3>
        <form method="get" class="ufsc-filters-form">
            <input type="hidden" name="page" value="ufsc-liste-clubs">
            
            <div class="ufsc-filters-grid">
                <div class="ufsc-filter-field">
                    <label for="s">Nom du club</label>
                    <input type="text" name="s" id="s" placeholder="🔍 Nom du club" value="<?php echo esc_attr($search); ?>">
                </div>

                <div class="ufsc-filter-field">
                    <label for="region">Région</label>
                    <select name="region" id="region">
                        <option value="">Toutes régions</option>
                        <?php foreach (ufsc_get_regions() as $r): ?>
                            <option value="<?php echo esc_attr($r); ?>" <?php echo selected($region, $r, false); ?>><?php echo esc_html($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ufsc-filter-field">
                    <label for="statut">Statut</label>
                    <select name="statut" id="statut">
                        <option value="">Tous statuts</option>
                        <?php foreach (ufsc_get_statuts() as $s): ?>
                            <option value="<?php echo esc_attr($s); ?>" <?php echo selected($statut, $s, false); ?>><?php echo esc_html($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="ufsc-filter-field">
                    <label for="date_affiliation_from">Date d'affiliation (de)</label>
                    <input type="date" name="date_affiliation_from" id="date_affiliation_from" value="<?php echo esc_attr($date_affiliation_from); ?>">
                </div>

                <div class="ufsc-filter-field">
                    <label for="date_affiliation_to">Date d'affiliation (à)</label>
                    <input type="date" name="date_affiliation_to" id="date_affiliation_to" value="<?php echo esc_attr($date_affiliation_to); ?>">
                </div>
            </div>

            <div class="ufsc-filters-actions">
                <button type="submit" class="button button-primary">Filtrer</button>
                <a href="<?php echo esc_url(remove_query_arg(['s', 'region', 'statut', 'date_affiliation_from', 'date_affiliation_to', 'paged'])); ?>" class="button">Réinitialiser</a>
            </div>
        </form>
    </div>

    <?php if (empty($clubs)): ?>
        <p>Aucun club trouvé.</p>
    <?php else: ?>
        
        <!-- Export form for selected clubs -->
        <form method="post" id="clubs-export-form">
            <?php wp_nonce_field('export_clubs_csv', 'export_nonce'); ?>
            
            <div style="margin-bottom: 15px; background: #f1f1f1; padding: 10px; border-radius: 5px;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <label style="font-weight: bold;">
                        <input type="checkbox" id="select-all-clubs"> Sélectionner tout
                    </label>
                    <span id="selected-count" style="color: #666;">0 club(s) sélectionné(s)</span>
                    <button type="submit" name="export_selected" class="button button-primary" id="export-selected-btn" disabled>
                        📥 Exporter la sélection (CSV)
                    </button>
                    <button type="button" class="button" onclick="window.location.href='<?php echo esc_url(admin_url('admin.php?page=ufsc-export-clubs')); ?>'">
                        📤 Exporter tous les clubs
                    </button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped" id="club-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all-header">
                        </th>
                        <th>Nom</th>
                        <th>Région</th>
                        <th>Ville</th>
                        <th>Statut</th>
                        <th>Documents</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Initialize document manager to check document status
                    $doc_manager = \UFSC\Admin\DocumentManager::get_instance();
                    
                    foreach ($clubs as $club): 
                        // Check document status
                        $missing_docs = $doc_manager->get_missing_documents($club->id);
                        $can_validate = empty($missing_docs);
                        $doc_count = 6 - count($missing_docs); // Total 6 required documents
                    ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_clubs[]" value="<?php echo esc_attr($club->id); ?>" class="club-checkbox">
                            </td>
                            <td>
                                <strong><?php echo esc_html($club->nom); ?></strong>
                                <?php if ($club->statut === 'Actif'): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;" title="Club validé"></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($club->region); ?></td>
                            <td><?php echo esc_html($club->ville); ?></td>
                            <td>
                                <?php 
                                $status_colors = [
                                    'en_attente' => '#e65100',
                                    'Actif' => '#2e7d32',
                                    'refuse' => '#d32f2f',
                                    'archive' => '#757575'
                                ];
                                $status_labels = [
                                    'en_attente' => 'En attente',
                                    'Actif' => 'Validé',
                                    'refuse' => 'Refusé',
                                    'archive' => 'Archivé'
                                ];
                                $current_status = $club->statut ?: 'en_attente';
                                $status_color = $status_colors[$current_status] ?? '#757575';
                                $status_label = $status_labels[$current_status] ?? 'Inconnu';
                                ?>
                                <span style="color: <?php echo esc_attr($status_color); ?>; font-weight: bold;">
                                    <?php echo esc_html($status_label); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($can_validate): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                    <span style="color: green; font-weight: bold;">Complet (6/6)</span>
                                    <?php if ($club->statut !== 'Actif'): ?>
                                        <br><small style="color: #e65100;">⚠️ Peut être validé</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color: #e65100;"></span>
                                    <span style="color: #e65100;">Incomplet (<?php echo esc_html($doc_count); ?>/6)</span>
                                    <br><small><?php echo esc_html(count($missing_docs)); ?> document(s) manquant(s)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="ufsc-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_view_club&id=' . $club->id)); ?>" 
                                       class="button button-small" title="Voir les détails">
                                        👁️ Voir
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_view_club&id=' . $club->id . '&edit=1')); ?>" 
                                       class="button button-small button-primary" title="Modifier le club">
                                        ✏️ Modifier
                                    </a>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_voir_licences&club_id=' . $club->id)); ?>" 
                                       class="button button-small button-secondary" title="Voir les licences">
                                        👥 Licences
                                    </a>
                                    <button type="button" 
                                            class="button button-small button-link-delete" 
                                            onclick="deleteClub(<?php echo esc_attr($club->id); ?>, '<?php echo esc_js($club->nom); ?>')"
                                            title="Supprimer le club">
                                        🗑️ Supprimer
                                    </button>
                                    <?php if ($can_validate && $club->statut !== 'Actif'): ?>
                                        <br>
                                        <button type="button" class="button button-primary button-small" onclick="validateClub(<?php echo esc_attr($club->id); ?>)" style="margin-top: 5px;">
                                            ✅ Valider le club
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <?php
        echo paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => $page,
            'total' => ceil($total / $per_page),
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);
        ?>
    <?php endif; ?>
</div>

<!-- 📊 Enhanced Table Functionality -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Checkbox functionality
    const selectAllMain = document.getElementById('select-all-clubs');
    const selectAllHeader = document.getElementById('select-all-header');
    const clubCheckboxes = document.querySelectorAll('.club-checkbox');
    const selectedCount = document.getElementById('selected-count');
    const exportBtn = document.getElementById('export-selected-btn');

    function updateUI() {
        const checkedBoxes = document.querySelectorAll('.club-checkbox:checked');
        const count = checkedBoxes.length;
        
        selectedCount.textContent = count + ' club(s) sélectionné(s)';
        exportBtn.disabled = count === 0;
        
        // Update select all checkboxes state
        if (count === 0) {
            selectAllMain.indeterminate = false;
            selectAllMain.checked = false;
            selectAllHeader.indeterminate = false;
            selectAllHeader.checked = false;
        } else if (count === clubCheckboxes.length) {
            selectAllMain.indeterminate = false;
            selectAllMain.checked = true;
            selectAllHeader.indeterminate = false;
            selectAllHeader.checked = true;
        } else {
            selectAllMain.indeterminate = true;
            selectAllHeader.indeterminate = true;
        }
    }

    // Select all functionality
    function toggleSelectAll(checked) {
        clubCheckboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        updateUI();
    }

    selectAllMain.addEventListener('change', (e) => toggleSelectAll(e.target.checked));
    selectAllHeader.addEventListener('change', (e) => toggleSelectAll(e.target.checked));

    // Individual checkbox functionality
    clubCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateUI);
    });

    // Initial state
    updateUI();

    // Export form validation
    document.getElementById('clubs-export-form').addEventListener('submit', (e) => {
        if (e.submitter && e.submitter.name === 'export_selected') {
            const checkedBoxes = document.querySelectorAll('.club-checkbox:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un club à exporter.');
                return false;
            }
            
            if (!confirm('Voulez-vous exporter ' + checkedBoxes.length + ' club(s) sélectionné(s) ?')) {
                e.preventDefault();
                return false;
            }
        }
    });

    // Table sorting functionality
    const ths = document.querySelectorAll('#club-table th');
    ths.forEach((th, colIndex) => {
        // Skip checkbox column
        if (colIndex === 0) return;
        
        th.style.cursor = 'pointer';
        th.title = 'Cliquer pour trier';
        
        th.addEventListener('click', () => {
            const table = th.closest('table');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const asc = th.classList.toggle('asc');
            
            // Remove asc class from other headers
            ths.forEach(otherTh => {
                if (otherTh !== th) otherTh.classList.remove('asc');
            });

            rows.sort((a, b) => {
                const aText = a.children[colIndex].innerText.toLowerCase();
                const bText = b.children[colIndex].innerText.toLowerCase();
                return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });

            const tbody = table.querySelector('tbody');
            rows.forEach(row => tbody.appendChild(row));
            
            // Re-bind checkbox events after sorting
            const newCheckboxes = document.querySelectorAll('.club-checkbox');
            newCheckboxes.forEach(checkbox => {
                checkbox.removeEventListener('change', updateUI);
                checkbox.addEventListener('change', updateUI);
            });
            
            updateUI();
        });
    });

    // Initial UI update
    updateUI();
});

// Function to validate a club
function validateClub(clubId) {
    if (confirm('Voulez-vous valider ce club ? Cette action marquera le club comme validé et permettra la génération de son attestation d\'affiliation.')) {
        // Show loading
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Validation...';
        button.disabled = true;
        
        // AJAX call to validate club
        const formData = new FormData();
        formData.append('action', 'ufsc_validate_club');
        formData.append('club_id', clubId);
        formData.append('nonce', '<?php echo esc_js(wp_create_nonce('ufsc_validate_club')); ?>');
        
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Club validé avec succès !');
                location.reload(); // Reload to update the display
            } else {
                alert('Erreur : ' + (data.data || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            alert('Erreur de communication : ' + error.message);
        })
        .finally(() => {
            button.textContent = originalText;
            button.disabled = false;
        });
    }
}

// Function to delete a club
function deleteClub(clubId, clubName) {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement le club "' + clubName + '" ?\n\nCette action est irréversible et supprimera également toutes les licences associées à ce club.')) {
        // Show loading
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Suppression...';
        button.disabled = true;
        
        // AJAX call to delete club
        const formData = new FormData();
        formData.append('action', 'ufsc_delete_club');
        formData.append('club_id', clubId);
        formData.append('nonce', '<?php echo esc_js(wp_create_nonce('ufsc_delete_club')); ?>');
        
        fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Club supprimé avec succès !');
                location.reload(); // Reload to update the display
            } else {
                alert('Erreur : ' + (data.data || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            alert('Erreur de communication : ' + error.message);
        })
        .finally(() => {
            button.textContent = originalText;
            button.disabled = false;
        });
    }
}
</script>

<!-- 📱 CSS Styles -->
<style>
.ufsc-filters-container {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.ufsc-filters-container h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #1d2327;
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

.inline-form {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.inline-form input, .inline-form select {
    margin: 0;
}

#club-table th {
    position: relative;
}

#club-table th.asc::after {
    content: ' ↑';
    color: #2271b1;
    font-weight: bold;
}

#club-table th:not(.asc):not(:first-child)::after {
    content: ' ↕';
    color: #ccc;
}

.club-checkbox {
    transform: scale(1.1);
}

#selected-count {
    font-weight: 500;
    background: #fff;
    padding: 2px 8px;
    border-radius: 3px;
    border: 1px solid #ddd;
}

#export-selected-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Action buttons styling */
.ufsc-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    align-items: center;
}

.ufsc-actions .button {
    margin: 0;
    white-space: nowrap;
    font-size: 12px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.2;
}

.button-link-delete {
    color: #d63638 !important;
    text-decoration: none !important;
    border-color: #d63638;
    background: transparent;
}

.button-link-delete:hover {
    color: #fff !important;
    background-color: #d63638;
    border-color: #a00;
}

.button-link-delete:focus {
    box-shadow: 0 0 0 1px #d63638;
}

/* Responsive actions for smaller screens */
@media (max-width: 768px) {
    .ufsc-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ufsc-actions .button {
        text-align: center;
        margin-bottom: 2px;
    }
}
</style>
