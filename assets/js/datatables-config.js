/**
 * Configuration DataTables pour le Plugin UFSC Gestion Club
 * 
 * Ce fichier configure les tableaux DataTables utilisés dans le plugin,
 * avec deux contextes distincts pour la table des licences:
 * - licenses-table-club: Vue par club avec checkbox de sélection (13 colonnes)
 * - licenses-table-global: Vue globale sans checkbox (13 colonnes)
 * 
 * @version 1.4.0
 * @date 2025-01-13
 * @update Ajout colonne Statut pour validation des licences
 */

jQuery(document).ready(function($) {
    function dataTablesAvailable() {
        return typeof $.fn.DataTable !== 'undefined';
    }

    function showFallback(selector) {
        const $table = $(selector);
        if (!$table.length) return;
        const $tbody = $table.find('tbody');
        if ($tbody.find('tr').length === 0) {
            const colspan = $table.find('thead th').length || 1;
            $tbody.html('<tr><td colspan="' + colspan + '">Aucune licence trouvée</td></tr>');
        }
        $table.show();
    }

    // Attendre que tous les scripts DataTables soient chargés
    if (!dataTablesAvailable()) {
        console.warn('DataTables non disponible - attente...');
        setTimeout(function() {
            if (!dataTablesAvailable()) {
                console.warn('DataTables toujours indisponible - utilisation de la table HTML');
                showFallback('#licenses-table-club');
                showFallback('#licenses-table-global');
                return;
            }
            initializeClubLicensesTable();
            initializeGlobalLicensesTable();
        }, 500);
        return;
    }

    initializeClubLicensesTable();
    initializeGlobalLicensesTable();
    
    /**
     * Configuration pour la table des licences du club (13 colonnes avec checkbox)
     * Utilisée dans includes/licences/admin-licence-list.php
     */
    function initializeClubLicensesTable() {
        const tableId = '#licenses-table-club';
        
        if ($(tableId).length > 0) {
            console.log('🏠 Initialisation table club (13 colonnes avec checkbox)');
            
            // Vérifier si DataTables est déjà initialisé
            if ($.fn.DataTable.isDataTable(tableId)) {
                console.log('DataTables déjà initialisé sur', tableId, '- destruction et réinitialisation');
                $(tableId).DataTable().destroy();
            }
            
            // Attendre que le DOM soit complètement chargé
            setTimeout(function() {
                const $table = $(tableId);
                const headerColCount = $table.find('thead tr th').length;
                
                console.log('Table club détectée:', headerColCount, 'colonnes');
                
                // Configuration spécifique pour la vue club (13 colonnes avec checkbox)
                const exportColumns = [];
                for (let i = 1; i < headerColCount - 1; i++) {
                    exportColumns.push(i);
                }

                const columnDefs = [
                    { targets: headerColCount - 1, orderable: false, searchable: false },
                    { targets: 0, orderable: false, searchable: false }
                ];

                initializeDataTable(tableId, exportColumns, columnDefs, true, 'Club');
            }, 100);
        }
    }
    
    /**
     * Configuration pour la table globale des licences (13 colonnes sans checkbox)
     * Utilisée dans includes/admin/class-menu.php
     */
    function initializeGlobalLicensesTable() {
        const tableId = '#licenses-table-global';
        
        if ($(tableId).length > 0) {
            console.log('🌐 Initialisation table globale (13 colonnes sans checkbox)');
            
            // Vérifier si DataTables est déjà initialisé
            if ($.fn.DataTable.isDataTable(tableId)) {
                console.log('DataTables déjà initialisé sur', tableId, '- destruction et réinitialisation');
                $(tableId).DataTable().destroy();
            }
            
            // Attendre que le DOM soit complètement chargé
            setTimeout(function() {
                const $table = $(tableId);
                const headerColCount = $table.find('thead tr th').length;
                
                console.log('Table globale détectée:', headerColCount, 'colonnes');
                
                // Configuration spécifique pour la vue globale (13 colonnes sans checkbox)
                const exportColumns = [];
                for (let i = 0; i < headerColCount - 1; i++) {
                    exportColumns.push(i);
                }

                const columnDefs = [
                    { targets: headerColCount - 1, orderable: false, searchable: false }
                ];

                initializeDataTable(tableId, exportColumns, columnDefs, false, 'Global');
            }, 100);
        }
    }
    
    /**
     * Fonction commune d'initialisation DataTables
     */
    function initializeDataTable(tableId, exportColumns, columnDefs, hasCheckbox, context) {
        try {
            console.log('✅ Initialisation DataTables', context, 'avec:');
            console.log('  - Colonnes d\'export:', exportColumns);
            console.log('  - Définitions de colonnes:', columnDefs.length);
            console.log('  - Checkbox présent:', hasCheckbox);
            
            $(tableId).DataTable({
                // Configuration générale
                responsive: true,
                processing: true,
                pageLength: 25,
                stateSave: true,
                scrollX: true,
                
                // Traduction en français
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
                },
                
                // Configuration des boutons d'export
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        text: '📥 Exporter Excel',
                        className: 'button button-primary',
                        exportOptions: {
                            columns: exportColumns
                        }
                    },
                    {
                        extend: 'csv',
                        text: '📋 Exporter CSV',
                        className: 'button',
                        exportOptions: {
                            columns: exportColumns
                        }
                    }
                ],
                
                // Configuration des colonnes
                columnDefs: columnDefs,
                
                // Tri par défaut (ID décroissant)
                order: [[hasCheckbox ? 1 : 0, 'desc']],
                
                // Configuration responsive
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.childRowImmediate,
                        type: 'none',
                        target: ''
                    }
                },
                
                // Personnalisations après initialisation
                initComplete: function() {
                    $('.dataTables_wrapper .dt-buttons').addClass('ufsc-export-buttons');
                    console.log('✅ Table', context, 'initialisée avec succès');
                    
                    // Vérifier si le tableau contient des données
                    const $tbody = $(tableId + ' tbody');
                    const $rows = $tbody.find('tr');
                    
                    if ($rows.length === 1) {
                        const $firstRow = $rows.first();
                        const $colspanCell = $firstRow.find('td[colspan]');
                        
                        if ($colspanCell.length === 1 && $colspanCell.text().includes('Aucune licence')) {
                            console.log("Aucune licence trouvée - état vide détecté");
                            $tbody.addClass('ufsc-empty-state');
                        }
                    }
                }
            });
        } catch (error) {
            console.error('❌ ERREUR lors de l\'initialisation DataTables', context, ':', error);

            // Essayer une initialisation de base en cas d'échec
            console.log('🔄 Tentative d\'initialisation basique pour', context, '...');
            try {
                $(tableId).DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' },
                    responsive: true,
                    pageLength: 25,
                    scrollX: true
                });
                console.log('✅ Initialisation basique réussie pour', context);
            } catch (basicError) {
                console.error('❌ Échec de l\'initialisation basique pour', context, ':', basicError);
                showFallback(tableId);
            }
        }
    }
    
    // Configuration pour d'autres tables DataTables si nécessaire
    if ($('#clubs-table').length > 0) {
        $('#clubs-table').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
            },
            responsive: true
        });
    }
    
    // Gestion globale des erreurs DataTables
    $.fn.dataTable.ext.errMode = function(settings, helpPage, message) {
        console.error('Erreur DataTables:', message);
        
        // Gestion spécifique de l'erreur "Incorrect column count"
        if (message && message.includes('Incorrect column count')) {
            console.error('Erreur de structure de tableau détectée');
            const tableId = settings.nTable ? settings.nTable.id : 'inconnu';
            console.log('Table ID:', tableId);
            
            // Vérifier la structure du tableau
            const $table = $(settings.nTable);
            const headerCols = $table.find('thead th').length;
            const bodyCols = $table.find('tbody tr:first td').length;
            const hasCheckbox = $table.find('thead tr th:first-child input[type="checkbox"]').length > 0;
            
            console.log('Colonnes dans l\'en-tête:', headerCols);
            console.log('Colonnes dans la première ligne du corps:', bodyCols);
            console.log('Colonne de sélection détectée:', hasCheckbox);
            
            // Messages d'aide selon le contexte
            if (tableId === 'licenses-table-club') {
                console.log('Configuration attendue: 13 colonnes avec checkbox (vue club)');
            } else if (tableId === 'licenses-table-global') {
                console.log('Configuration attendue: 13 colonnes sans checkbox (vue globale)');
            } else {
                console.log('Table non reconnue - vérifier la configuration');
            }
        } else if (settings && settings.jqXHR && settings.jqXHR.status === 0) {
            alert('Erreur de connexion au serveur. Veuillez vérifier votre connexion internet.');
        } else {
            // Message plus générique pour les autres erreurs
            console.log('Table ID:', settings.nTable ? settings.nTable.id : 'inconnu');
            console.log('Page d\'aide:', helpPage);
            console.log('Message d\'erreur complet:', message);
        }
    };
});
