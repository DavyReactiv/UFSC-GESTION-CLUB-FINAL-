<?php
/**
 * Menu Class
 *
 * Handles the admin menu setup for UFSC Gestion Club
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Ensure class-dashboard.php exists before requiring it
require_once plugin_dir_path(__FILE__) . 'class-dashboard.php';

// Include UFSC CSV Export helper
require_once plugin_dir_path(__FILE__) . '../helpers/class-ufsc-csv-export.php';

/**
 * Menu Class
 */
class UFSC_Menu
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_menus'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only enqueue on UFSC admin pages
        if (strpos($hook, 'ufsc') === false && strpos($hook, 'plugin-ufsc-gestion-club') === false) {
            return;
        }

        // Enqueue licence actions script on pages that manage licences
        $licence_pages = [
            'ufsc_page_ufsc-licences',
            'ufsc_page_ufsc-edit-club',
            'plugin-ufsc-gestion-club-13072025_page_ufsc-licences'
        ];

        if (in_array($hook, $licence_pages) || strpos($hook, 'ufsc-licences') !== false || strpos($hook, 'ufsc-edit-club') !== false) {
            wp_enqueue_script(
                'ufsc-licence-actions',
                UFSC_PLUGIN_URL . 'assets/js/admin-licence-actions.js',
                ['jquery'],
                UFSC_PLUGIN_VERSION,
                true
            );

            // Enqueue admin licences table CSS
            wp_enqueue_style(
                'ufsc-admin-licences',
                UFSC_PLUGIN_URL . 'assets/css/admin-licences.css',
                [],
                UFSC_PLUGIN_VERSION
            );

            // Localize script with nonces and AJAX URL
            
        // Enqueue enhanced admin UI styles/scripts for UFSC screens
        wp_enqueue_style('ufsc-admin-ui', plugins_url('../../assets/css/ufsc-admin-ui.css', __FILE__), [], '1.0.0');
        wp_enqueue_script('ufsc-admin-ui', plugins_url('../../assets/js/ufsc-admin-ui.js', __FILE__), [], '1.0.0', true);
    
            wp_localize_script('ufsc-licence-actions', 'ufscLicenceConfig', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonces' => [
                    'delete_licence' => wp_create_nonce('ufsc_delete_licence'),
                    'change_licence_status' => wp_create_nonce('ufsc_change_licence_status'),
                ],
                'messages' => [
                    'confirmDelete' => __('Êtes-vous sûr de vouloir supprimer cette licence ?', 'plugin-ufsc-gestion-club-13072025'),
                    'deleteSuccess' => __('Licence supprimée avec succès.', 'plugin-ufsc-gestion-club-13072025'),
                    'deleteError' => __('Erreur lors de la suppression.', 'plugin-ufsc-gestion-club-13072025'),
                    'statusUpdateSuccess' => __('Statut mis à jour avec succès.', 'plugin-ufsc-gestion-club-13072025'),
                    'statusUpdateError' => __('Erreur lors de la mise à jour du statut.', 'plugin-ufsc-gestion-club-13072025'),
                ]
            ]);
        }
    }

    /**
     * Register admin menus
     */
    public function register_menus()
    {
        // 🏠 MENU PRINCIPAL
        add_menu_page(
            'UFSC – Gestion des clubs et licences',
            'UFSC',
            'manage_ufsc',
            'plugin-ufsc-gestion-club-13072025',
            array($this, 'render_dashboard_page'),
            'dashicons-groups',
            25
        );

        // 🏢 CLUBS
        add_submenu_page(
            'plugin-ufsc-gestion-club-13072025',
            'Ajouter un nouveau club affilié',
            'Ajouter un club',
            'manage_ufsc',
            'ufsc-ajouter-club',
            array($this, 'render_ajouter_club_page')
        );

        add_submenu_page(
            'plugin-ufsc-gestion-club-13072025',
            'Liste des clubs affiliés enregistrés',
            'Clubs affiliés',
            'manage_ufsc',
            'ufsc-liste-clubs',
            array($this, 'render_liste_clubs_page')
        );

        // Add hidden submenu page for editing clubs
        add_submenu_page(
            null, // Parent slug (null makes it hidden from menu)
            'Modifier un club',
            'Modifier un club',
            'manage_ufsc',
            'ufsc_edit_club',
            array($this, 'render_edit_club_page')
        );

        // Add hidden submenu page for viewing club details
        add_submenu_page(
            null,
            'Détails du club',
            'Détails du club',
            'manage_ufsc',
            'ufsc_view_club',
            array($this, 'render_view_club_page')
        );

        // 🧾 LICENCES
        add_submenu_page(
            'plugin-ufsc-gestion-club-13072025',
            'Liste de toutes les licences enregistrées',
            'Licences',
            'manage_ufsc',
            'ufsc-liste-licences',
            array($this, 'render_liste_licences_page')
        );

        // Add new licence submenu
        add_submenu_page(
            'plugin-ufsc-gestion-club-13072025',
            'Ajouter une nouvelle licence',
            'Ajouter une licence',
            'manage_ufsc',
            'ufsc-ajouter-nouvelle-licence',
            array($this, 'render_ajouter_licence_page')
        );

        // Edit licence submenu (hidden from menu but accessible via URL)
        add_submenu_page(
            null, // null parent = hidden from menu
            'Modifier une licence',
            'Modifier une licence',
            'manage_ufsc',
            'ufsc-modifier-licence',
            array($this, 'render_modifier_licence_page')
        );

        // Add hidden submenu page for viewing license details
        add_submenu_page(
            null,
            'Détails de la licence',
            'Détails de la licence',
            'manage_ufsc',
            'ufsc_view_licence',
            array($this, 'render_view_licence_page')
        );

        // Add hidden submenu page for viewing club licences
        add_submenu_page(
            null,
            'Licences du club',
            'Licences du club',
            'manage_ufsc',
            'ufsc_voir_licences',
            array($this, 'render_voir_licences_page')
        );

        // 📤 EXPORTS
        add_submenu_page(
            'plugin-ufsc-gestion-club-13072025',
            'Exporter la liste des clubs (CSV)',
            'Export clubs',
            'manage_ufsc',
            'ufsc-export-clubs',
            array($this, 'render_export_clubs_page')
        );

        add_submenu_page(
            'plugin-ufsc-gestion-club-13072025',
            'Exporter la liste des licences (CSV)',
            'Export licences',
            'manage_ufsc',
            'ufsc-export-licences',
            array($this, 'render_export_licences_page')
        );

        // ⚙️ PARAMÈTRES
        add_submenu_page(
            'plugin-ufsc-gestion-club-13072025',
            'Configuration & options du plugin UFSC',
            'Paramètres',
            'manage_ufsc',
            'ufsc-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page()
    {
        $dashboard = new UFSC_Dashboard();
        $dashboard->render_dashboard();
    }

    /**
     * Render liste clubs page
     */
    public function render_liste_clubs_page()
    {
        // Load the advanced club list view with search, filters, and export functionality
        require_once UFSC_PLUGIN_PATH . 'includes/views/admin-club-list.php';
    }

    /**
     * Render ajouter club page
     */
    public function render_ajouter_club_page()
    {
        // Handle form submission
        $form_submitted = false;
        $errors = [];
        $success = false;
        $club_data = [];

        if (isset($_POST['ufsc_add_club_submit']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $form_submitted = true;

            // Verify nonce
            if (!isset($_POST['ufsc_add_club_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ufsc_add_club_nonce']), 'ufsc_add_club_nonce')) {
                $errors[] = 'Erreur de sécurité. Veuillez recharger la page.';
            } else {
                // Sanitize and collect form data
                $club_data = [
                    'nom' => isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '',
                    'adresse' => isset($_POST['adresse']) ? sanitize_textarea_field(wp_unslash($_POST['adresse'])) : '',
                    'complement_adresse' => isset($_POST['complement_adresse']) ? sanitize_text_field(wp_unslash($_POST['complement_adresse'])) : '',
                    'code_postal' => isset($_POST['code_postal']) ? sanitize_text_field(wp_unslash($_POST['code_postal'])) : '',
                    'ville' => isset($_POST['ville']) ? sanitize_text_field(wp_unslash($_POST['ville'])) : '',
                    'region' => isset($_POST['region']) ? sanitize_text_field(wp_unslash($_POST['region'])) : '',
                    'precision_distribution' => isset($_POST['precision_distribution']) ? sanitize_text_field(wp_unslash($_POST['precision_distribution'])) : '',
                    'url_site' => isset($_POST['url_site']) ? esc_url_raw(wp_unslash($_POST['url_site'])) : '',
                    'url_facebook' => isset($_POST['url_facebook']) ? esc_url_raw(wp_unslash($_POST['url_facebook'])) : '',
                    'telephone' => isset($_POST['telephone']) ? sanitize_text_field(wp_unslash($_POST['telephone'])) : '',
                    'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
                    'num_declaration' => isset($_POST['num_declaration']) ? sanitize_text_field(wp_unslash($_POST['num_declaration'])) : '',
                    'date_declaration' => isset($_POST['date_declaration']) ? sanitize_text_field(wp_unslash($_POST['date_declaration'])) : '',
                    'siren' => isset($_POST['siren']) ? sanitize_text_field(wp_unslash($_POST['siren'])) : '',
                    'ape' => isset($_POST['ape']) ? sanitize_text_field(wp_unslash($_POST['ape'])) : '',
                    'ccn' => isset($_POST['ccn']) ? sanitize_text_field(wp_unslash($_POST['ccn'])) : '',
                    'ancv' => isset($_POST['ancv']) ? sanitize_text_field(wp_unslash($_POST['ancv'])) : '',
                    'president_nom' => isset($_POST['president_nom']) ? sanitize_text_field(wp_unslash($_POST['president_nom'])) : '',
                    'president_tel' => isset($_POST['president_tel']) ? sanitize_text_field(wp_unslash($_POST['president_tel'])) : '',
                    'president_email' => isset($_POST['president_email']) ? sanitize_email(wp_unslash($_POST['president_email'])) : '',
                    'secretaire_nom' => isset($_POST['secretaire_nom']) ? sanitize_text_field(wp_unslash($_POST['secretaire_nom'])) : '',
                    'secretaire_tel' => isset($_POST['secretaire_tel']) ? sanitize_text_field(wp_unslash($_POST['secretaire_tel'])) : '',
                    'secretaire_email' => isset($_POST['secretaire_email']) ? sanitize_email(wp_unslash($_POST['secretaire_email'])) : '',
                    'tresorier_nom' => isset($_POST['tresorier_nom']) ? sanitize_text_field(wp_unslash($_POST['tresorier_nom'])) : '',
                    'tresorier_tel' => isset($_POST['tresorier_tel']) ? sanitize_text_field(wp_unslash($_POST['tresorier_tel'])) : '',
                    'tresorier_email' => isset($_POST['tresorier_email']) ? sanitize_email(wp_unslash($_POST['tresorier_email'])) : '',
                    'entraineur_nom' => isset($_POST['entraineur_nom']) ? sanitize_text_field(wp_unslash($_POST['entraineur_nom'])) : '',
                    'entraineur_tel' => isset($_POST['entraineur_tel']) ? sanitize_text_field(wp_unslash($_POST['entraineur_tel'])) : '',
                    'entraineur_email' => isset($_POST['entraineur_email']) ? sanitize_email(wp_unslash($_POST['entraineur_email'])) : '',
                    'statut' => 'en_attente',
                    'date_creation' => current_time('mysql')
                ];

                // Validation of required fields
                if (empty($club_data['nom'])) {
                    $errors[] = 'Le nom de l\'association est obligatoire.';
                }
                if (empty($club_data['adresse'])) {
                    $errors[] = 'Le numéro et nom de rue est obligatoire.';
                }
                if (empty($club_data['code_postal'])) {
                    $errors[] = 'Le code postal est obligatoire.';
                }
                if (empty($club_data['ville'])) {
                    $errors[] = 'La ville est obligatoire.';
                }
                if (empty($club_data['region'])) {
                    $errors[] = 'La région est obligatoire.';
                }
                if (empty($club_data['telephone'])) {
                    $errors[] = 'Le téléphone de l\'association est obligatoire.';
                }
                if (empty($club_data['email'])) {
                    $errors[] = 'L\'adresse email de l\'association est obligatoire.';
                }
                if (empty($club_data['num_declaration'])) {
                    $errors[] = 'Le N° de déclaration en préfecture est obligatoire.';
                }
                if (empty($club_data['date_declaration'])) {
                    $errors[] = 'La date de déclaration en préfecture est obligatoire.';
                }

                // Validate postal code format
                if (!empty($club_data['code_postal']) && !preg_match('/^[0-9]{5}$/', $club_data['code_postal'])) {
                    $errors[] = 'Le code postal doit contenir exactement 5 chiffres.';
                }

                // Validate email format
                if (!empty($club_data['email']) && !is_email($club_data['email'])) {
                    $errors[] = 'L\'adresse email de l\'association n\'est pas valide.';
                }

                // Validate SIREN format if provided
                if (!empty($club_data['siren']) && !preg_match('/^[0-9]{9}$/', $club_data['siren'])) {
                    $errors[] = 'Le numéro SIREN doit contenir exactement 9 chiffres.';
                }

                // Validate required documents for club creation
                $required_documents = ['statuts', 'recepisse', 'jo', 'pv_ag', 'cer', 'attestation_cer'];
                foreach ($required_documents as $doc) {
                    if (empty($_FILES[$doc]['name'])) {
                        $document_labels = [
                            'statuts' => 'Statuts du club',
                            'recepisse' => 'Récépissé de déclaration en préfecture',
                            'jo' => 'Parution au journal officiel',
                            'pv_ag' => 'Dernier PV d\'Assemblée Générale',
                            'cer' => 'Contrat d\'Engagement Républicain',
                            'attestation_cer' => 'Attestation liée au CER'
                        ];
                        $errors[] = 'Le document "' . $document_labels[$doc] . '" est obligatoire pour créer un club.';
                    }
                }

                // Validate file types for uploaded documents
                if (!empty($_FILES)) {
                    $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                    $max_file_size = 5 * 1024 * 1024; // 5MB
                    
                    foreach ($_FILES as $file_key => $file) {
                        if (!empty($file['name'])) {
                            // Check file type
                            if (!in_array($file['type'], $allowed_types)) {
                                $errors[] = 'Le fichier ' . $file['name'] . ' doit être au format PDF, JPEG ou PNG.';
                            }
                            
                            // Check file size
                            if ($file['size'] > $max_file_size) {
                                $errors[] = 'Le fichier ' . $file['name'] . ' ne doit pas dépasser 5MB.';
                            }
                            
                            // Check for upload errors
                            if ($file['error'] !== UPLOAD_ERR_OK) {
                                $errors[] = 'Erreur lors du téléchargement du fichier ' . $file['name'] . '.';
                            }
                        }
                    }
                }

                // Save club if no errors
                if (empty($errors)) {
                    $club_manager = UFSC_Club_Manager::get_instance();
                    $result = $club_manager->add_club($club_data);

                    if ($result) {
                        $club_id = $result;
                        
                        // Process file uploads
                        if (!empty($_FILES)) {
                            if (!function_exists('wp_handle_upload')) {
                                require_once(ABSPATH . 'wp-admin/includes/file.php');
                            }
                            
                            $document_types = [
                                'statuts' => 'statuts',
                                'recepisse' => 'recepisse', 
                                'jo' => 'jo',
                                'pv_ag' => 'pv_ag',
                                'cer' => 'cer',
                                'attestation_cer' => 'attestation_cer'
                            ];
                            
                            foreach ($document_types as $file_key => $doc_type) {
                                if (!empty($_FILES[$file_key]['name'])) {
                                    $file = $_FILES[$file_key];
                                    
                                    // Create filename with club ID for security
                                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                                    $new_filename = 'club_' . $club_id . '_' . $doc_type . '_' . time() . '.' . $file_extension;
                                    
                                    // Override filename
                                    $_FILES[$file_key]['name'] = $new_filename;
                                    
                                    $upload_overrides = array(
                                        'test_form' => false,
                                        'unique_filename_callback' => function($dir, $name, $ext) {
                                            return $name;
                                        }
                                    );
                                    
                                    $movefile = wp_handle_upload($_FILES[$file_key], $upload_overrides);
                                    
                                    if ($movefile && !isset($movefile['error'])) {
                                        // Store document URL in database
                                        $club_manager->update_club_document($club_id, $doc_type, $movefile['url']);
                                    } else {
                                        $errors[] = 'Erreur lors du téléchargement du fichier ' . $file['name'] . ': ' . ($movefile['error'] ?? 'Erreur inconnue');
                                    }
                                }
                            }
                        }
                        
                        if (empty($errors)) {
                            $success = true;
                            // Clear form data for next entry
                            $club_data = [];
                        }
                    } else {
                        $errors[] = 'Erreur lors de l\'enregistrement du club. Veuillez réessayer.';
                    }
                }
            }
        }

        // Load regions for dropdown
        $regions = require UFSC_PLUGIN_PATH . 'data/regions.php';
        ?>
        <div class="wrap ufsc-ui">
            <h1><?php echo esc_html__('Ajouter un club', 'plugin-ufsc-gestion-club-13072025'); ?></h1>
            <p><?php echo esc_html__('Créez une nouvelle affiliation pour un club. Remplissez le formulaire avec les informations du club à enregistrer.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
            
            <?php if ($form_submitted): ?>
                <?php if ($success): ?>
                    <div class="notice notice-success">
                        <p><strong>Succès :</strong> Le club a été créé avec succès!</p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-error">
                        <p><strong>Erreurs détectées :</strong></p>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="" id="ufsc-club-form" enctype="multipart/form-data">
                <?php wp_nonce_field('ufsc_add_club_nonce', 'ufsc_add_club_nonce'); ?>
                <input type="hidden" name="ufsc_add_club_submit" value="1">
                
                <!-- Informations générales -->
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-groups"></span> Informations générales</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="nom">Nom du club / association <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="nom" id="nom" type="text" class="regular-text" value="<?php echo esc_attr($club_data['nom'] ?? ''); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="adresse">Numéro et nom de rue <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="adresse" id="adresse" type="text" class="regular-text" value="<?php echo esc_attr($club_data['adresse'] ?? ''); ?>" required>
                                <p class="description">Exemple: 123 rue de la République</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="complement_adresse">Complément adresse</label>
                            </th>
                            <td>
                                <input name="complement_adresse" id="complement_adresse" type="text" class="regular-text" value="<?php echo esc_attr($club_data['complement_adresse'] ?? ''); ?>">
                                <p class="description">Immeuble, bâtiment, etc.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="code_postal">Code postal <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="code_postal" id="code_postal" type="text" class="regular-text" value="<?php echo esc_attr($club_data['code_postal'] ?? ''); ?>" pattern="[0-9]{5}" maxlength="5" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ville">Ville <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="ville" id="ville" type="text" class="regular-text" value="<?php echo esc_attr($club_data['ville'] ?? ''); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="region">Région <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <select name="region" id="region" required>
                                    <option value="">-- Choisir une région --</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo esc_attr($region); ?>" <?php selected($club_data['region'] ?? '', $region); ?>>
                                            <?php echo esc_html($region); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="precision_distribution">Précision distribution</label>
                            </th>
                            <td>
                                <input name="precision_distribution" id="precision_distribution" type="text" class="regular-text" value="<?php echo esc_attr($club_data['precision_distribution'] ?? ''); ?>">
                                <p class="description">BP, etc.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="telephone">Téléphone de l'association <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="telephone" id="telephone" type="tel" class="regular-text" value="<?php echo esc_attr($club_data['telephone'] ?? ''); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="email">Adresse email de l'association <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="email" id="email" type="email" class="regular-text" value="<?php echo esc_attr($club_data['email'] ?? ''); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="url_site">URL du site internet</label>
                            </th>
                            <td>
                                <input name="url_site" id="url_site" type="url" class="regular-text" value="<?php echo esc_attr($club_data['url_site'] ?? ''); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="url_facebook">URL page Facebook</label>
                            </th>
                            <td>
                                <input name="url_facebook" id="url_facebook" type="url" class="regular-text" value="<?php echo esc_attr($club_data['url_facebook'] ?? ''); ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Informations légales -->
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-clipboard"></span> Informations légales</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="num_declaration">N° de déclaration en préfecture <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="num_declaration" id="num_declaration" type="text" class="regular-text" value="<?php echo esc_attr($club_data['num_declaration'] ?? ''); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="date_declaration">Date de déclaration en préfecture <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="date_declaration" id="date_declaration" type="date" class="regular-text" value="<?php echo esc_attr($club_data['date_declaration'] ?? ''); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="siren">Numéro SIREN</label>
                            </th>
                            <td>
                                <input name="siren" id="siren" type="text" class="regular-text" value="<?php echo esc_attr($club_data['siren'] ?? ''); ?>" pattern="[0-9]{9}" maxlength="9">
                                <p class="description">9 chiffres</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ape">Code APE / NAF</label>
                            </th>
                            <td>
                                <input name="ape" id="ape" type="text" class="regular-text" value="<?php echo esc_attr($club_data['ape'] ?? ''); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ccn">Convention collective</label>
                            </th>
                            <td>
                                <select name="ccn" id="ccn">
                                    <option value="">-- Choisir --</option>
                                    <option value="CCNS" <?php selected($club_data['ccn'] ?? '', 'CCNS'); ?>>CCNS</option>
                                    <option value="Animation" <?php selected($club_data['ccn'] ?? '', 'Animation'); ?>>Animation</option>
                                    <option value="Autres" <?php selected($club_data['ccn'] ?? '', 'Autres'); ?>>Autres</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ancv">Numéro ANCV</label>
                            </th>
                            <td>
                                <input name="ancv" id="ancv" type="text" class="regular-text" value="<?php echo esc_attr($club_data['ancv'] ?? ''); ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Dirigeants -->
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-businessperson"></span> Dirigeants</h2>
                    
                    <?php 
                    $dirigeants = [
                        'president' => 'Président',
                        'secretaire' => 'Secrétaire', 
                        'tresorier' => 'Trésorier',
                        'entraineur' => 'Entraîneur (facultatif)'
                    ];
                    
                    foreach ($dirigeants as $key => $label): ?>
                        <h3><?php echo esc_html($label); ?></h3>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($key); ?>_nom">Nom et prénom</label>
                                </th>
                                <td>
                                    <input name="<?php echo esc_attr($key); ?>_nom" id="<?php echo esc_attr($key); ?>_nom" type="text" class="regular-text" value="<?php echo esc_attr($club_data[$key . '_nom'] ?? ''); ?>">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($key); ?>_tel">Téléphone</label>
                                </th>
                                <td>
                                    <input name="<?php echo esc_attr($key); ?>_tel" id="<?php echo esc_attr($key); ?>_tel" type="tel" class="regular-text" value="<?php echo esc_attr($club_data[$key . '_tel'] ?? ''); ?>">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($key); ?>_email">Adresse email</label>
                                </th>
                                <td>
                                    <input name="<?php echo esc_attr($key); ?>_email" id="<?php echo esc_attr($key); ?>_email" type="email" class="regular-text" value="<?php echo esc_attr($club_data[$key . '_email'] ?? ''); ?>">
                                </td>
                            </tr>
                        </table>
                    <?php endforeach; ?>
                </div>

                <!-- Documents administratifs obligatoires -->
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-media-document"></span> Documents administratifs obligatoires</h2>
                    <p class="description" style="color: #d63638; font-weight: bold;">
                        Tous les documents ci-dessous sont obligatoires pour créer un club. Formats acceptés : PDF, JPEG, PNG (max 5MB par fichier).
                    </p>
                    
                    <?php 
                    $required_documents = [
                        'statuts' => 'Statuts du club',
                        'recepisse' => 'Récépissé de déclaration en préfecture',
                        'jo' => 'Parution au journal officiel',
                        'pv_ag' => 'Dernier PV d\'Assemblée Générale',
                        'cer' => 'Contrat d\'Engagement Républicain',
                        'attestation_cer' => 'Attestation liée au CER'
                    ];
                    ?>
                    
                    <table class="form-table" role="presentation">
                        <?php foreach ($required_documents as $doc_key => $doc_label): ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($doc_key); ?>"><?php echo esc_html($doc_label); ?> <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input type="file" name="<?php echo esc_attr($doc_key); ?>" id="<?php echo esc_attr($doc_key); ?>" 
                                       accept=".pdf,.jpg,.jpeg,.png" required class="regular-text">
                                <p class="description">Format PDF, JPEG ou PNG - Maximum 5MB</p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <div class="notice notice-info inline" style="margin: 20px 0;">
                        <p><strong>Information importante :</strong> Après validation de l'affiliation par l'administration, une "Attestation d'affiliation UFSC" sera automatiquement générée et ajoutée au dossier du club.</p>
                    </div>
                </div>

                <p class="submit">
                    <?php submit_button(__('Ajouter le club', 'plugin-ufsc-gestion-club-13072025'), 'primary', 'submit', false); ?>
                </p>
            </form>
        </div>

        <style>
        .ufsc-admin-section {
            background: #fff;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 20px 0;
            padding: 20px;
        }
        .ufsc-admin-section h2 {
            margin-top: 0;
            color: #1d2327;
            border-bottom: 1px solid #c3c4c7;
            padding-bottom: 10px;
        }
        .ufsc-admin-section h2 .dashicons {
            margin-right: 8px;
            color: #2271b1;
        }
        .ufsc-admin-section h3 {
            margin: 20px 0 10px 0;
            color: #1d2327;
        }
        .ufsc-required {
            color: #d63638;
            font-weight: bold;
        }
        #ufsc-club-form .form-table th {
            width: 200px;
            padding: 15px 10px 15px 0;
        }
        #ufsc-club-form .form-table td {
            padding: 15px 10px;
        }
        #ufsc-club-form .regular-text {
            width: 25em;
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Client-side validation
            const form = document.getElementById('ufsc-club-form');
            const requiredFields = form.querySelectorAll('[required]');
            
            form.addEventListener('submit', function(e) {
                let hasErrors = false;
                
                requiredFields.forEach(function(field) {
                    if (field.type === 'file') {
                        // Special handling for file inputs
                        if (!field.files || field.files.length === 0) {
                            field.style.borderColor = '#d63638';
                            hasErrors = true;
                        } else {
                            // Validate file type and size
                            const file = field.files[0];
                            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
                            const maxSize = 5 * 1024 * 1024; // 5MB
                            
                            if (!allowedTypes.includes(file.type)) {
                                alert('Le fichier ' + file.name + ' doit être au format PDF, JPEG ou PNG.');
                                field.style.borderColor = '#d63638';
                                hasErrors = true;
                            } else if (file.size > maxSize) {
                                alert('Le fichier ' + file.name + ' ne doit pas dépasser 5MB.');
                                field.style.borderColor = '#d63638';
                                hasErrors = true;
                            } else {
                                field.style.borderColor = '';
                            }
                        }
                    } else if (!field.value.trim()) {
                        field.style.borderColor = '#d63638';
                        hasErrors = true;
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                // Validate postal code
                const codePostal = document.getElementById('code_postal');
                if (codePostal.value && !/^[0-9]{5}$/.test(codePostal.value)) {
                    codePostal.style.borderColor = '#d63638';
                    hasErrors = true;
                }
                
                // Validate SIREN
                const siren = document.getElementById('siren');
                if (siren.value && !/^[0-9]{9}$/.test(siren.value)) {
                    siren.style.borderColor = '#d63638';
                    hasErrors = true;
                }
                
                if (hasErrors) {
                    e.preventDefault();
                    alert('Veuillez corriger les erreurs dans le formulaire. Tous les documents sont obligatoires.');
                }
            });
            
            // Real-time validation feedback
            requiredFields.forEach(function(field) {
                if (field.type === 'file') {
                    field.addEventListener('change', function() {
                        if (this.files && this.files.length > 0) {
                            this.style.borderColor = '';
                        }
                    });
                } else {
                    field.addEventListener('input', function() {
                        if (this.value.trim()) {
                            this.style.borderColor = '';
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render liste licences page
     */
    public function render_liste_licences_page()
    {
        wp_enqueue_style(
            'ufsc-admin-licence-table-style',
            UFSC_PLUGIN_URL . 'assets/css/admin-licence-table.css',
            [],
            UFSC_PLUGIN_VERSION
        );

        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-ufsc-licence-list-table.php';
        $table = new UFSC_Licence_List_Table();
        $table->prepare_items();

        ?>
        <div class="wrap ufsc-ui">
            <h1><?php echo esc_html__('Liste des licences', 'plugin-ufsc-gestion-club-13072025'); ?></h1>
            <p><?php echo esc_html__('Gérez toutes les licences enregistrées dans le système.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
            <p>
                <a href="https://ufsc-france.fr/ajouter-licencie/" class="button button-primary">
                    <?php echo esc_html__('Ajouter une nouvelle licence', 'plugin-ufsc-gestion-club-13072025'); ?>
                </a>
            </p>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>" />
                <?php $table->search_box(__('Recherche', 'plugin-ufsc-gestion-club-13072025'), 'licence'); ?>
                <?php $table->display(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render export clubs page
     */
    public function render_export_clubs_page()
    {
        global $wpdb;
        
        // Handle selected clubs export
        if (isset($_POST['ufsc_export_selected_clubs']) && wp_verify_nonce(wp_unslash($_POST['ufsc_export_selected_nonce'] ?? ''), 'ufsc_export_selected_nonce')) {
            if (isset($_POST['selected_clubs']) && is_array($_POST['selected_clubs'])) {
                $selected_ids = array_map('intval', $_POST['selected_clubs']);
                if (!empty($selected_ids)) {
                    $placeholders = implode(',', array_fill(0, count($selected_ids), '%d'));
                    
                    $clubs = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}ufsc_clubs WHERE id IN ($placeholders) ORDER BY nom ASC",
                        ...$selected_ids
                    ));
                    
                    if (!empty($clubs)) {

                        // Use the new configurable CSV export
                        UFSC_CSV_Export::export_clubs($clubs, 'clubs-selection-' . date('Y-m-d') . '.csv');

                        UFSC_CSV_Export::export_clubs($clubs, 'clubs_ufsc_selection_' . date('Y-m-d') . '.csv');
 
                    }
                }
            }
        }
        
        // Handle export all request
        if (isset($_POST['ufsc_export_clubs']) && wp_verify_nonce(wp_unslash($_POST['ufsc_export_clubs_nonce'] ?? ''), 'ufsc_export_clubs_nonce')) {
            $clubs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ufsc_clubs ORDER BY nom ASC");
            
            if (!empty($clubs)) {

                // Use the new configurable CSV export
                UFSC_CSV_Export::export_clubs($clubs);

                UFSC_CSV_Export::export_clubs($clubs, 'clubs_ufsc_complet_' . date('Y-m-d') . '.csv');
            }
        }
        
        // Get search and filter parameters
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $region = isset($_GET['region']) ? sanitize_text_field(wp_unslash($_GET['region'])) : '';
        $statut = isset($_GET['statut']) ? sanitize_text_field(wp_unslash($_GET['statut'])) : '';
        
        // Build query for available clubs
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
        
        $where_clause = implode(' AND ', $where_sql);
        
        // Get total clubs count
        if (!empty($params)) {
            $total_clubs = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs WHERE $where_clause",
                ...$params
            ));
        } else {
            $total_clubs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs WHERE $where_clause");
        }
        
        // Get filtered clubs for selection
        if (!empty($params)) {
            $clubs = $wpdb->get_results($wpdb->prepare(
                "SELECT id, nom, region, ville, statut FROM {$wpdb->prefix}ufsc_clubs WHERE $where_clause ORDER BY nom ASC",
                ...$params
            ));
        } else {
            $clubs = $wpdb->get_results("SELECT id, nom, region, ville, statut FROM {$wpdb->prefix}ufsc_clubs WHERE $where_clause ORDER BY nom ASC");
        }
        
        // Load regions for filters
        require_once UFSC_PLUGIN_PATH . 'includes/helpers.php';
        
        ?>
        <div class="wrap ufsc-ui">
            <h1><?php echo esc_html__('Export des clubs', 'plugin-ufsc-gestion-club-13072025'); ?></h1>
            <p><?php echo esc_html__('Exportez tous les clubs ou sélectionnez individuellement les clubs à exporter au format CSV.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
            
            <!-- Export complet -->
            <div class="card" style="max-width: 600px; margin-bottom: 30px;">
                <h2>Export complet</h2>
                <p>Cette action exportera <strong><?php echo esc_html($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_clubs")); ?> club(s)</strong> au format CSV avec toutes les informations disponibles.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('ufsc_export_clubs_nonce', 'ufsc_export_clubs_nonce'); ?>
                    <input type="hidden" name="ufsc_export_clubs" value="1">
                    
                    <?php submit_button(__('📥 Exporter tous les clubs (CSV)', 'plugin-ufsc-gestion-club-13072025'), 'primary', 'submit', true); ?>
                </form>
            </div>
            
            <!-- Export sélectif -->
            <div class="card">
                <h2>Export sélectif</h2>
                <p>Sélectionnez individuellement les clubs à exporter. Utilisez les filtres pour affiner votre recherche.</p>
                
                <!-- Filtres -->
                <div class="ufsc-filters-container">
                    <h3>Filtres de recherche</h3>
                    <form method="get" class="ufsc-filters-form">
                        <input type="hidden" name="page" value="ufsc-export-clubs">
                        
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
                                        <option value="<?php echo esc_attr($r); ?>" <?php selected($region, $r); ?>><?php echo esc_html($r); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="ufsc-filter-field">
                                <label for="statut">Statut</label>
                                <select name="statut" id="statut">
                                    <option value="">Tous statuts</option>
                                    <?php foreach (ufsc_get_statuts() as $s): ?>
                                        <option value="<?php echo esc_attr($s); ?>" <?php selected($statut, $s); ?>><?php echo esc_html($s); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ufsc-filters-actions">
                            <button type="submit" class="button button-primary">Filtrer</button>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc-export-clubs')); ?>" class="button">Réinitialiser</a>
                        </div>
                    </form>
                </div>
                
                <!-- Liste des clubs avec sélection -->
                <?php if (!empty($clubs)): ?>
                <form method="post" id="export-selection-form">
                    <?php wp_nonce_field('ufsc_export_selected_nonce', 'ufsc_export_selected_nonce'); ?>
                    <input type="hidden" name="ufsc_export_selected_clubs" value="1">
                    
                    <div style="margin-bottom: 15px; background: #f1f1f1; padding: 10px; border-radius: 5px;">
                        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                            <label style="font-weight: bold;">
                                <input type="checkbox" id="select-all-clubs"> Sélectionner tout
                            </label>
                            <span id="selected-count" style="color: #666;">0 club(s) sélectionné(s)</span>
                            <button type="submit" class="button button-primary" id="export-selected-btn" disabled>
                                📥 Exporter la sélection (CSV)
                            </button>
                        </div>
                    </div>
                    
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr style="position: sticky; top: 0; background: #f9f9f9;">
                                    <th style="width: 40px; padding: 8px;">
                                        <input type="checkbox" id="select-all-header">
                                    </th>
                                    <th style="padding: 8px;">Nom</th>
                                    <th style="padding: 8px;">Région</th>
                                    <th style="padding: 8px;">Ville</th>
                                    <th style="padding: 8px;">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clubs as $club): ?>
                                    <tr>
                                        <td style="padding: 8px;">
                                            <input type="checkbox" name="selected_clubs[]" value="<?php echo esc_attr($club->id); ?>" class="club-checkbox">
                                        </td>
                                        <td style="padding: 8px;"><strong><?php echo esc_html($club->nom); ?></strong></td>
                                        <td style="padding: 8px;"><?php echo esc_html($club->region); ?></td>
                                        <td style="padding: 8px;"><?php echo esc_html($club->ville); ?></td>
                                        <td style="padding: 8px;">
                                            <?php
                                            $status_colors = [
                                                'Actif' => '#46b450',
                                                'en_attente' => '#ffb900',
                                                'refuse' => '#dc3232',
                                                'archive' => '#82878c'
                                            ];
                                            $status_labels = [
                                                'Actif' => 'Validé',
                                                'en_attente' => 'En attente',
                                                'refuse' => 'Refusé',
                                                'archive' => 'Archivé'
                                            ];
                                            $status = $club->statut ?: 'en_attente';
                                            $color = $status_colors[$status] ?? '#82878c';
                                            $label = $status_labels[$status] ?? 'Inconnu';
                                            ?>
                                            <span style="display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; color: white; background-color: <?php echo esc_attr($color); ?>;">
                                                <?php echo esc_html($label); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <p><strong>Note :</strong> <?php echo esc_html($total_clubs); ?> club(s) trouvé(s) avec les filtres actuels.</p>
                </form>
                <?php else: ?>
                    <p>Aucun club trouvé avec les critères sélectionnés.</p>
                <?php endif; ?>
            </div>
            
            <div class="notice notice-info" style="margin-top: 20px;">
                <p><strong>💡 Astuce avancée :</strong> Pour une gestion plus poussée avec pagination et fonctionnalités avancées, utilisez la page <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc-liste-clubs')); ?>">Liste des clubs</a>.</p>
            </div>
        </div>
        
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

        @media (max-width: 768px) {
            .ufsc-filters-grid {
                grid-template-columns: 1fr !important;
            }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                
                const allChecked = count === clubCheckboxes.length && count > 0;
                const someChecked = count > 0 && count < clubCheckboxes.length;
                
                if (selectAllMain) {
                    selectAllMain.checked = allChecked;
                    selectAllMain.indeterminate = someChecked;
                }
                if (selectAllHeader) {
                    selectAllHeader.checked = allChecked;
                    selectAllHeader.indeterminate = someChecked;
                }
            }

            function toggleAll(checked) {
                clubCheckboxes.forEach(checkbox => {
                    checkbox.checked = checked;
                });
                updateUI();
            }

            // Event listeners
            if (selectAllMain) {
                selectAllMain.addEventListener('change', function() {
                    toggleAll(this.checked);
                });
            }

            if (selectAllHeader) {
                selectAllHeader.addEventListener('change', function() {
                    toggleAll(this.checked);
                });
            }

            clubCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateUI);
            });

            // Export form validation
            document.getElementById('export-selection-form').addEventListener('submit', function(e) {
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
            });

            // Initial UI update
            updateUI();
        });
        </script>
        <?php
    }

    /**
     * Render export licences page
     */
    public function render_export_licences_page()
    {
        global $wpdb;
        
        // Enqueue the CSS and JS for enhanced filters
        wp_enqueue_style(
            'ufsc-admin-style',
            UFSC_PLUGIN_URL . 'assets/css/admin.css',
            [],
            UFSC_PLUGIN_VERSION
        );
        
        // Handle export processing
        if (isset($_POST['ufsc_export_licences']) && check_admin_referer('ufsc_export_licences_nonce', 'ufsc_export_licences_nonce')) {
            $this->process_license_export();
            return;
        }
        
        // Get filter parameters (same as license list page)
        $search = isset($_GET['search_nom']) ? sanitize_text_field(wp_unslash($_GET['search_nom'])) : '';
        $search_prenom = isset($_GET['search_prenom']) ? sanitize_text_field(wp_unslash($_GET['search_prenom'])) : '';
        $search_email = isset($_GET['search_email']) ? sanitize_email(wp_unslash($_GET['search_email'])) : '';
        $search_ville = isset($_GET['search_ville']) ? sanitize_text_field(wp_unslash($_GET['search_ville'])) : '';
        $search_telephone = isset($_GET['search_telephone']) ? sanitize_text_field(wp_unslash($_GET['search_telephone'])) : '';
        $date_naissance_from = isset($_GET['date_naissance_from']) ? sanitize_text_field(wp_unslash($_GET['date_naissance_from'])) : '';
        $date_naissance_to = isset($_GET['date_naissance_to']) ? sanitize_text_field(wp_unslash($_GET['date_naissance_to'])) : '';
        $date_inscription_from = isset($_GET['date_inscription_from']) ? sanitize_text_field(wp_unslash($_GET['date_inscription_from'])) : '';
        $date_inscription_to = isset($_GET['date_inscription_to']) ? sanitize_text_field(wp_unslash($_GET['date_inscription_to'])) : '';
        $region = isset($_GET['region']) ? sanitize_text_field(wp_unslash($_GET['region'])) : '';
        $filter_club = isset($_GET['filter_club']) ? intval(wp_unslash($_GET['filter_club'])) : 0;
        $export_type = isset($_GET['export_type']) ? sanitize_text_field(wp_unslash($_GET['export_type'])) : 'individual';
        $statut_filter = isset($_GET['statut_filter']) ? sanitize_text_field(wp_unslash($_GET['statut_filter'])) : '';
        
        // Build WHERE clause for preview
        $where = [];
        $params = [];
        
        if ($search !== '') {
            $where[] = 'l.nom LIKE %s';
            $params[] = '%' . $search . '%';
        }
        if ($search_prenom !== '') {
            $where[] = 'l.prenom LIKE %s';
            $params[] = '%' . $search_prenom . '%';
        }
        if ($search_email !== '') {
            $where[] = 'l.email LIKE %s';
            $params[] = '%' . $search_email . '%';
        }
        if ($search_ville !== '') {
            $where[] = 'l.ville LIKE %s';
            $params[] = '%' . $search_ville . '%';
        }
        if ($search_telephone !== '') {
            $where[] = '(l.tel_fixe LIKE %s OR l.tel_mobile LIKE %s)';
            $params[] = '%' . $search_telephone . '%';
            $params[] = '%' . $search_telephone . '%';
        }
        if ($date_naissance_from !== '') {
            $where[] = 'l.date_naissance >= %s';
            $params[] = $date_naissance_from;
        }
        if ($date_naissance_to !== '') {
            $where[] = 'l.date_naissance <= %s';
            $params[] = $date_naissance_to;
        }
        if ($date_inscription_from !== '') {
            $where[] = 'DATE(l.date_inscription) >= %s';
            $params[] = $date_inscription_from;
        }
        if ($date_inscription_to !== '') {
            $where[] = 'DATE(l.date_inscription) <= %s';
            $params[] = $date_inscription_to;
        }
        if ($region !== '') {
            $where[] = 'l.region = %s';
            $params[] = $region;
        }
        if ($filter_club > 0) {
            $where[] = 'l.club_id = %d';
            $params[] = $filter_club;
        }
        if ($statut_filter !== '') {
            $where[] = 'l.statut = %s';
            $params[] = $statut_filter;
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get count for preview
        $total_count = 0;
        if (!empty($params)) {
            $total_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 $where_clause",
                ...$params
            ));
        } else {
            $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 $where_clause");
        }
        
        // Get regions for filter dropdown
        require_once plugin_dir_path(__FILE__) . '../helpers.php';
        $regions = function_exists('ufsc_get_regions') ? ufsc_get_regions() : [];
        
        // Get clubs for filter dropdown
        $all_clubs = $wpdb->get_results("SELECT id, nom FROM {$wpdb->prefix}ufsc_clubs ORDER BY nom ASC");
        ?>
        
        <div class="wrap ufsc-ui">
            <h1><?php echo esc_html__('Export des licences', 'plugin-ufsc-gestion-club-13072025'); ?></h1>
            <p><?php echo esc_html__('Configurez les filtres de recherche et le type d\'export souhaité, puis exportez les données des licences au format CSV.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
            
            <!-- Enhanced Filters (same as license list page) -->
            <div class="ufsc-filters-container">
                <h3>Filtres de recherche</h3>
                <form method="get" class="ufsc-filters-form">
                    <input type="hidden" name="page" value="ufsc-export-licences">
                    
                    <div class="ufsc-filters-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div class="ufsc-filter-field">
                            <label for="search_nom"><?php _e('Nom', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="text" name="search_nom" id="search_nom" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Rechercher par nom', 'plugin-ufsc-gestion-club-13072025'); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="search_prenom"><?php _e('Prénom', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="text" name="search_prenom" id="search_prenom" value="<?php echo esc_attr($search_prenom); ?>" placeholder="<?php _e('Rechercher par prénom', 'plugin-ufsc-gestion-club-13072025'); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="search_email"><?php _e('Email', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="email" name="search_email" id="search_email" value="<?php echo esc_attr($search_email); ?>" placeholder="<?php _e('Rechercher par email', 'plugin-ufsc-gestion-club-13072025'); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="search_ville"><?php _e('Ville', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="text" name="search_ville" id="search_ville" value="<?php echo esc_attr($search_ville); ?>" placeholder="<?php _e('Rechercher par ville', 'plugin-ufsc-gestion-club-13072025'); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="search_telephone"><?php _e('Téléphone', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="text" name="search_telephone" id="search_telephone" value="<?php echo esc_attr($search_telephone); ?>" placeholder="<?php _e('Rechercher par téléphone', 'plugin-ufsc-gestion-club-13072025'); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="date_naissance_from"><?php _e('Date de naissance (de)', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="date" name="date_naissance_from" id="date_naissance_from" value="<?php echo esc_attr($date_naissance_from); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="date_naissance_to"><?php _e('Date de naissance (à)', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="date" name="date_naissance_to" id="date_naissance_to" value="<?php echo esc_attr($date_naissance_to); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="date_inscription_from"><?php _e('Date d\'enregistrement (de)', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="date" name="date_inscription_from" id="date_inscription_from" value="<?php echo esc_attr($date_inscription_from); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="date_inscription_to"><?php _e('Date d\'enregistrement (à)', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <input type="date" name="date_inscription_to" id="date_inscription_to" value="<?php echo esc_attr($date_inscription_to); ?>">
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="region"><?php _e('Région', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <select name="region" id="region">
                                <option value=""><?php _e('Toutes régions', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                                <?php foreach ($regions as $r): ?>
                                    <option value="<?php echo esc_attr($r); ?>" <?php selected($region, $r); ?>><?php echo esc_html($r); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="ufsc-filter-field">
                            <label for="filter_club"><?php _e('Club', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <select name="filter_club" id="filter_club">
                                <option value=""><?php _e('Tous les clubs', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                                <?php foreach ($all_clubs as $club_option): ?>
                                    <option value="<?php echo esc_attr($club_option->id); ?>" <?php selected($filter_club, $club_option->id); ?>>
                                        <?php echo esc_html($club_option->nom); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- NEW: Export Type Filter -->
                        <div class="ufsc-filter-field">
                            <label for="export_type"><?php _e('Type d\'export', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <select name="export_type" id="export_type">
                                <option value="individual" <?php selected($export_type, 'individual'); ?>><?php _e('Individuel (une ligne par licence)', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                                <option value="group" <?php selected($export_type, 'group'); ?>><?php _e('Groupé (résumé par club)', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                            </select>
                        </div>
                        
                        <!-- NEW: Status Filter -->
                        <div class="ufsc-filter-field">
                            <label for="statut_filter"><?php _e('Statut des licences', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                            <select name="statut_filter" id="statut_filter">
                                <option value=""><?php _e('Tous les statuts', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                                <option value="en_attente" <?php selected($statut_filter ?? '', 'en_attente'); ?>><?php _e('En attente', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                                <option value="validee" <?php selected($statut_filter ?? '', 'validee'); ?>><?php _e('Validées', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                                <option value="refusee" <?php selected($statut_filter ?? '', 'refusee'); ?>><?php _e('Refusées', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ufsc-filters-actions" style="margin-bottom: 20px;">
                        <button type="submit" class="button button-secondary">Appliquer les filtres</button>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc-export-licences')); ?>" class="button">Réinitialiser</a>
                    </div>
                </form>
            </div>
            
            <!-- Export Preview and Download -->
            <div class="ufsc-export-preview">
                <h3>Aperçu de l'export</h3>
                <p><strong><?php echo esc_html($total_count); ?></strong> licence(s) correspondront aux critères sélectionnés.</p>
                
                <?php if ($export_type === 'group'): ?>
                    <p><em>Export groupé :</em> Les données seront regroupées par club avec totaux et statistiques.</p>
                <?php else: ?>
                    <p><em>Export individuel :</em> Une ligne par licence avec toutes les informations détaillées.</p>
                <?php endif; ?>
                
                <?php if ($total_count > 0): ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('ufsc_export_licences_nonce', 'ufsc_export_licences_nonce'); ?>
                        <input type="hidden" name="ufsc_export_licences" value="1">
                        <input type="hidden" name="search_nom" value="<?php echo esc_attr($search); ?>">
                        <input type="hidden" name="search_prenom" value="<?php echo esc_attr($search_prenom); ?>">
                        <input type="hidden" name="search_email" value="<?php echo esc_attr($search_email); ?>">
                        <input type="hidden" name="search_ville" value="<?php echo esc_attr($search_ville); ?>">
                        <input type="hidden" name="search_telephone" value="<?php echo esc_attr($search_telephone); ?>">
                        <input type="hidden" name="date_naissance_from" value="<?php echo esc_attr($date_naissance_from); ?>">
                        <input type="hidden" name="date_naissance_to" value="<?php echo esc_attr($date_naissance_to); ?>">
                        <input type="hidden" name="date_inscription_from" value="<?php echo esc_attr($date_inscription_from); ?>">
                        <input type="hidden" name="date_inscription_to" value="<?php echo esc_attr($date_inscription_to); ?>">
                        <input type="hidden" name="region" value="<?php echo esc_attr($region); ?>">
                        <input type="hidden" name="filter_club" value="<?php echo esc_attr($filter_club); ?>">
                        <input type="hidden" name="export_type" value="<?php echo esc_attr($export_type); ?>">
                        <input type="hidden" name="statut_filter" value="<?php echo esc_attr($statut_filter); ?>">>
                        
                        <?php submit_button(__('📊 Télécharger l\'export CSV', 'plugin-ufsc-gestion-club-13072025'), 'primary', 'submit', true); ?>
                    </form>
                <?php else: ?>
                    <div class="notice notice-warning">
                        <p>Aucune licence ne correspond aux critères sélectionnés. Modifiez vos filtres pour inclure des données.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .ufsc-export-preview {
            background: #f9f9f9;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .ufsc-export-preview h3 {
            margin-top: 0;
        }
        </style>
        
        <?php
    }

    /**
     * Render edit club page
     */
    public function render_edit_club_page()
    {
        // Get club ID from URL parameter
        $club_id = isset($_GET['id']) ? intval(wp_unslash($_GET['id'])) : 0;
        if (!$club_id) {
            echo '<div class="wrap ufsc-ui"><div class="notice notice-error"><p>Aucun club sélectionné.</p></div></div>';
            return;
        }

        // Get club data
        $club_manager = UFSC_Club_Manager::get_instance();
        $club = $club_manager->get_club($club_id);
        if (!$club) {
            echo '<div class="wrap ufsc-ui"><div class="notice notice-error"><p>Club introuvable.</p></div></div>';
            return;
        }

        // Handle form submission for updates
        $form_submitted = false;
        $errors = [];
        $success = false;

        if (isset($_POST['ufsc_update_club_submit']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $form_submitted = true;

            // Verify nonce
            if (!isset($_POST['ufsc_update_club_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ufsc_update_club_nonce']), 'ufsc_update_club_nonce')) {
                $errors[] = 'Erreur de sécurité. Veuillez recharger la page.';
            } else {
                // Handle admin attestation uploads (media library)
                if (isset($_POST['attestation_affiliation_media_id']) || isset($_POST['attestation_assurance_media_id'])) {
                    $attestation_success_messages = [];
                    
                    // Handle attestation affiliation
                    if (isset($_POST['attestation_affiliation_media_id']) && !empty($_POST['attestation_affiliation_media_id'])) {
                        $media_id = intval($_POST['attestation_affiliation_media_id']);
                        if (get_post($media_id) && get_post_type($media_id) === 'attachment') {
                            update_post_meta($club_id, '_ufsc_attestation_affiliation', $media_id);
                            $attestation_success_messages[] = 'Attestation d\'affiliation mise à jour';
                        }
                    }
                    
                    // Handle attestation assurance  
                    if (isset($_POST['attestation_assurance_media_id']) && !empty($_POST['attestation_assurance_media_id'])) {
                        $media_id = intval($_POST['attestation_assurance_media_id']);
                        if (get_post($media_id) && get_post_type($media_id) === 'attachment') {
                            update_post_meta($club_id, '_ufsc_attestation_assurance', $media_id);
                            $attestation_success_messages[] = 'Attestation d\'assurance mise à jour';
                        }
                    }
                    
                    if (!empty($attestation_success_messages)) {
                        $success = true;
                        // Refresh club data to show updated attestations
                        $club = $club_manager->get_club($club_id);
                    }
                }

                // Handle document uploads using centralized validation
                if (!empty($_FILES)) {
                    if (!function_exists('wp_handle_upload')) {
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                    }
                    
                    $document_types = UFSC_Upload_Validator::get_allowed_document_types();
                    
                    foreach ($document_types as $doc_key => $doc_label) {
                        if (!empty($_FILES[$doc_key]['name'])) {
                            $file = $_FILES[$doc_key];
                            
                            // Use centralized validation
                            $validation = UFSC_Upload_Validator::validate_document($file, $club_id, $doc_key);
                            
                            if (is_wp_error($validation)) {
                                $errors[] = 'Erreur pour ' . $doc_label . ': ' . $validation->get_error_message();
                                continue;
                            }
                            
                            // Override filename with secure name
                            $_FILES[$doc_key]['name'] = $validation['filename'];
                            
                            $upload_overrides = array(
                                'test_form' => false,
                                'unique_filename_callback' => function($dir, $name, $ext) {
                                    return $name; // Use the secure filename we generated
                                }
                            );
                            
                            $movefile = wp_handle_upload($_FILES[$doc_key], $upload_overrides);
                            
                            if ($movefile && !isset($movefile['error'])) {
                                // Store document URL in database
                                $club_manager->update_club_document($club_id, $doc_key, $movefile['url']);
                                $success = true;
                            } else {
                                $errors[] = 'Erreur lors du téléchargement de ' . $doc_label . ': ' . ($movefile['error'] ?? 'Erreur inconnue');
                            }
                        }
                    }
                    
                    if (empty($errors) && !$success) {
                        $errors[] = 'Aucun fichier sélectionné pour le téléchargement.';
                    }
                    
                    if ($success) {
                        // Refresh club data
                        $club = $club_manager->get_club($club_id);
                    }
                }
            }
        }

        ?>
        <div class="wrap ufsc-ui">
            <h1><?php echo esc_html__('Gestion des documents', 'plugin-ufsc-gestion-club-13072025'); ?> - <?php echo esc_html($club->nom); ?></h1>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc-liste-clubs')); ?>" class="button">
                    ← Retour à la liste des clubs
                </a>
            </p>
            
            <?php if ($form_submitted): ?>
                <?php if ($success): ?>
                    <div class="notice notice-success">
                        <p><strong>Succès :</strong> 
                        <?php 
                        if (isset($attestation_success_messages) && !empty($attestation_success_messages)) {
                            echo implode(' et ', $attestation_success_messages) . '.';
                        } else {
                            echo 'Les documents ont été mis à jour avec succès!';
                        }
                        ?></p>
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="notice notice-error">
                        <p><strong>Erreurs détectées :</strong></p>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="" id="ufsc-edit-club-form" enctype="multipart/form-data">
                <?php wp_nonce_field('ufsc_update_club_nonce', 'ufsc_update_club_nonce'); ?>
                <input type="hidden" name="ufsc_update_club_submit" value="1">
                
                <!-- Documents administratifs -->
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-media-document"></span> Documents administratifs obligatoires</h2>
                    <p class="description">
                        Les documents existants sont affichés ci-dessous. Vous pouvez télécharger de nouveaux fichiers pour les remplacer.
                    </p>
                    
                    <?php 
                    $required_documents = [
                        'statuts' => 'Statuts du club',
                        'recepisse' => 'Récépissé de déclaration en préfecture',
                        'jo' => 'Parution au journal officiel',
                        'pv_ag' => 'Dernier PV d\'Assemblée Générale',
                        'cer' => 'Contrat d\'Engagement Républicain',
                        'attestation_cer' => 'Attestation liée au CER'
                    ];
                    
                    // Check if all required documents are present
                    $missing_docs = [];
                    foreach ($required_documents as $doc_key => $doc_label) {
                        $doc_column = 'doc_' . $doc_key;
                        if (empty($club->{$doc_column})) {
                            $missing_docs[] = $doc_label;
                        }
                    }
                    ?>
                    
                    <?php if (!empty($missing_docs)): ?>
                    <div class="notice notice-warning inline" style="margin: 20px 0;">
                        <p><strong>Attention :</strong> Des documents obligatoires sont manquants :</p>
                        <ul>
                            <?php foreach ($missing_docs as $missing_doc): ?>
                                <li><?php echo esc_html($missing_doc); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p>Le club ne peut pas être validé tant que tous les documents obligatoires ne sont pas fournis.</p>
                    </div>
                    <?php else: ?>
                    <div class="notice notice-success inline" style="margin: 20px 0;">
                        <p><strong>✓ Tous les documents obligatoires sont présents.</strong> Le club peut être validé.</p>
                    </div>
                    <?php endif; ?>
                    
                    <table class="form-table" role="presentation">
                        <?php foreach ($required_documents as $doc_key => $doc_label): 
                            $doc_column = 'doc_' . $doc_key;
                            $has_document = !empty($club->{$doc_column});
                        ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($doc_key); ?>"><?php echo esc_html($doc_label); ?></label>
                            </th>
                            <td>
                                <?php if ($has_document): ?>
                                    <div class="ufsc-existing-document" style="margin-bottom: 10px;">
                                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                        <strong>Document existant :</strong>
                                        <a href="<?php echo esc_url($club->{$doc_column}); ?>" target="_blank" class="button button-small">
                                            Voir le document
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="ufsc-missing-document" style="margin-bottom: 10px; color: #d63638;">
                                        <span class="dashicons dashicons-warning"></span>
                                        <strong>Document manquant - OBLIGATOIRE</strong>
                                    </div>
                                <?php endif; ?>
                                
                                <input type="file" name="<?php echo esc_attr($doc_key); ?>" id="<?php echo esc_attr($doc_key); ?>" 
                                       accept=".pdf,.jpg,.jpeg,.png" class="regular-text">
                                <p class="description">
                                    <?php if ($has_document): ?>
                                        Télécharger un nouveau fichier pour remplacer l'existant.
                                    <?php else: ?>
                                        Télécharger le document manquant.
                                    <?php endif; ?>
                                    Format PDF, JPEG ou PNG - Maximum 5MB
                                </p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    
                </div>

                <!-- Attestations administratives -->
                <?php if ($club->statut === 'Actif'): ?>
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-media-document"></span> Attestations administratives (Admin uniquement)</h2>
                    <p class="description">
                        Téléversez les attestations officielles pour ce club validé. Ces fichiers seront accessibles uniquement au club concerné.
                    </p>
                    
                    <?php
                    // Get club attestations using helper functions
                    $attestation_affiliation_url = ufsc_get_club_attestation_url($club_id, 'affiliation');
                    $attestation_assurance_url = ufsc_get_club_attestation_url($club_id, 'assurance');
                    ?>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label>Attestation d'affiliation</label>
                            </th>
                            <td>
                                <?php if ($attestation_affiliation_url): ?>
                                    <div class="ufsc-existing-document" style="margin-bottom: 10px;">
                                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                        <strong>Attestation existante :</strong>
                                        <?php 
                                        $file_extension = ufsc_get_attestation_file_extension($attestation_affiliation_url);
                                        if (in_array($file_extension, ['pdf', 'jpg', 'jpeg', 'png'])): ?>
                                            <a href="<?php echo esc_url($attestation_affiliation_url); ?>" target="_blank" class="button button-small">
                                                Voir
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url($attestation_affiliation_url); ?>" download class="button button-small">
                                            Télécharger
                                        </a>
                                        <button type="button" class="button button-small ufsc-replace-club-attestation" 
                                                data-club-id="<?php echo $club_id; ?>" data-type="affiliation">
                                            Remplacer
                                        </button>
                                        <button type="button" class="button button-small ufsc-delete-club-attestation" 
                                                data-club-id="<?php echo $club_id; ?>" data-type="affiliation">
                                            Supprimer
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="ufsc-missing-document" style="margin-bottom: 10px; color: #666;">
                                        <span class="dashicons dashicons-format-aside"></span>
                                        <strong>Aucune attestation d'affiliation</strong>
                                        <button type="button" class="button ufsc-upload-club-attestation" 
                                                data-club-id="<?php echo $club_id; ?>" data-type="affiliation">
                                            Téléverser
                                        </button>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="description">
                                    Formats acceptés : PDF, JPG, JPEG, PNG (max. 5MB)
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label>Attestation d'assurance</label>
                            </th>
                            <td>
                                <?php if ($attestation_assurance_url): ?>
                                    <div class="ufsc-existing-document" style="margin-bottom: 10px;">
                                        <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                                        <strong>Attestation existante :</strong>
                                        <?php 
                                        $file_extension = ufsc_get_attestation_file_extension($attestation_assurance_url);
                                        if (in_array($file_extension, ['pdf', 'jpg', 'jpeg', 'png'])): ?>
                                            <a href="<?php echo esc_url($attestation_assurance_url); ?>" target="_blank" class="button button-small">
                                                Voir
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url($attestation_assurance_url); ?>" download class="button button-small">
                                            Télécharger
                                        </a>
                                        <button type="button" class="button button-small ufsc-replace-club-attestation" 
                                                data-club-id="<?php echo $club_id; ?>" data-type="assurance">
                                            Remplacer
                                        </button>
                                        <button type="button" class="button button-small ufsc-delete-club-attestation" 
                                                data-club-id="<?php echo $club_id; ?>" data-type="assurance">
                                            Supprimer
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="ufsc-missing-document" style="margin-bottom: 10px; color: #666;">
                                        <span class="dashicons dashicons-format-aside"></span>
                                        <strong>Aucune attestation d'assurance</strong>
                                        <button type="button" class="button ufsc-upload-club-attestation" 
                                                data-club-id="<?php echo $club_id; ?>" data-type="assurance">
                                            Téléverser
                                        </button>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="description">
                                    Formats acceptés : PDF, JPG, JPEG, PNG (max. 5MB)
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php else: ?>
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-lock"></span> Attestations administratives</h2>
                    <div class="notice notice-warning inline">
                        <p><strong>Club non validé :</strong> Les attestations ne peuvent être téléversées que pour les clubs avec le statut "Actif".</p>
                    </div>
                </div>
                <?php endif; ?>

                <p class="submit">
                    <?php submit_button(__('Mettre à jour les documents', 'plugin-ufsc-gestion-club-13072025'), 'primary', 'submit', false); ?>
                </p>
            </form>
        </div>

        <style>
        .ufsc-admin-section {
            background: #fff;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 20px 0;
            padding: 20px;
        }
        .ufsc-admin-section h2 {
            margin-top: 0;
            color: #1d2327;
            border-bottom: 1px solid #c3c4c7;
            padding-bottom: 10px;
        }
        .ufsc-admin-section h2 .dashicons {
            margin-right: 8px;
            color: #2271b1;
        }
        .ufsc-existing-document {
            background: #f0f8f0;
            padding: 10px;
            border-left: 4px solid #46b450;
            border-radius: 3px;
        }
        .ufsc-missing-document {
            background: #fef7f1;
            padding: 10px;
            border-left: 4px solid #d63638;
            border-radius: 3px;
        }
        </style>

        <script>
        // Form validation and UI enhancements for club edit page
        jQuery(document).ready(function($) {
            // Any additional club edit functionality can be added here
            // Attestation functionality is now handled by ufsc-attestations.js
        });
        </script>
        <?php
    }

    /**
     * Register settings for the settings page
     */
    public function register_settings()
    {
        // Register the main settings group
        register_setting('ufsc_settings', 'ufsc_general_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
            'default' => array()
        ));

        // Register individual page settings
        register_setting('ufsc_settings', 'ufsc_club_dashboard_page_id', array(
            'sanitize_callback' => 'absint',
            'default' => 0
        ));
        register_setting('ufsc_settings', 'ufsc_affiliation_page_id', array(
            'sanitize_callback' => 'absint',
            'default' => 0
        ));
        register_setting('ufsc_settings', 'ufsc_club_form_page_id', array(
            'sanitize_callback' => 'absint',
            'default' => 0
        ));
        register_setting('ufsc_settings', 'ufsc_licence_page_id', array(
            'sanitize_callback' => 'absint',
            'default' => 0
        ));
        register_setting('ufsc_settings', 'ufsc_attestation_page_id', array(
            'sanitize_callback' => 'absint',
            'default' => 0
        ));

        // Register WooCommerce product ID settings
        register_setting('ufsc_settings', 'ufsc_wc_affiliation_product_id', array(
            'sanitize_callback' => 'absint',
            'default' => 4823
        ));
        register_setting('ufsc_settings', 'ufsc_licence_product_id', array(
            'sanitize_callback' => 'absint',
            'default' => 2934
        ));

        // Register new WooCommerce integration settings
        register_setting('ufsc_settings', 'ufsc_wc_license_product_ids', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '2934'
        ));
        register_setting('ufsc_settings', 'ufsc_auto_create_user', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => false
        ));
        register_setting('ufsc_settings', 'ufsc_require_login_shortcodes', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => true
        ));

        // Add a basic settings section
        add_settings_section(
            'ufsc_general_section',
            __('Paramètres généraux', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'general_section_callback'),
            'ufsc-settings'
        );

        // Add some basic settings fields
        add_settings_field(
            'ufsc_plugin_active',
            __('Plugin actif', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'plugin_active_field_callback'),
            'ufsc-settings',
            'ufsc_general_section'
        );

        // ================================
        // PAGE CONFIGURATION SECTION
        // ================================
        add_settings_section(
            'ufsc_page_config_section',
            __('Configuration des pages', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'page_config_section_callback'),
            'ufsc-settings'
        );

        // Dashboard page setting
        add_settings_field(
            'club_dashboard_page',
            __('Page Espace Club (Dashboard)', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'club_dashboard_page_callback'),
            'ufsc-settings',
            'ufsc_page_config_section'
        );

        // Affiliation page setting
        add_settings_field(
            'affiliation_page',
            __('Page Affiliation', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'affiliation_page_callback'),
            'ufsc-settings',
            'ufsc_page_config_section'
        );

        // Club form page setting
        add_settings_field(
            'club_form_page',
            __('Page Formulaire de club', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'club_form_page_callback'),
            'ufsc-settings',
            'ufsc_page_config_section'
        );

        // Licences page setting
        add_settings_field(
            'licences_page',
            __('Page Licences', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'licences_page_callback'),
            'ufsc-settings',
            'ufsc_page_config_section'
        );

        // Attestations page setting
        add_settings_field(
            'attestations_page',
            __('Page Attestations', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'attestations_page_callback'),
            'ufsc-settings',
            'ufsc_page_config_section'
        );

        // ================================
        // WOOCOMMERCE CONFIGURATION SECTION
        // ================================
        add_settings_section(
            'ufsc_woocommerce_section',
            __('Configuration WooCommerce', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'woocommerce_section_callback'),
            'ufsc-settings'
        );

        // Affiliation product ID setting
        add_settings_field(
            'affiliation_product_id',
            __('ID Produit Affiliation', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'affiliation_product_id_callback'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        // Licence product ID setting
        add_settings_field(
            'licence_product_id',
            __('ID Produit Licence', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'licence_product_id_callback'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        // WooCommerce license product IDs (CSV) - NEW
        add_settings_field(
            'ufsc_wc_license_product_ids',
            __('IDs Produits Licences (CSV)', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'wc_license_product_ids_callback'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        // Auto-create user from order - NEW
        add_settings_field(
            'ufsc_auto_create_user',
            __('Création automatique d\'utilisateur', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'auto_create_user_callback'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        // Require login for shortcodes - NEW
        add_settings_field(
            'ufsc_require_login_shortcodes',
            __('Connexion requise pour les shortcodes', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'require_login_shortcodes_callback'),
            'ufsc-settings',
            'ufsc_woocommerce_section'
        );

        // ================================
        // CSV EXPORT CONFIGURATION SECTION
        // ================================
        add_settings_section(
            'ufsc_csv_export_section',
            __('Configuration des exports CSV', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'csv_export_section_callback'),
            'ufsc-settings'
        );

        // CSV Separator
        add_settings_field(
            'csv_separator',
            __('Séparateur CSV', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'csv_separator_field_callback'),
            'ufsc-settings',
            'ufsc_csv_export_section'
        );

        // CSV Encoding
        add_settings_field(
            'csv_encoding',
            __('Encodage CSV', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'csv_encoding_field_callback'),
            'ufsc-settings',
            'ufsc_csv_export_section'
        );

        // Club fields to export
        add_settings_field(
            'club_export_fields',
            __('Champs clubs à exporter', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'club_export_fields_callback'),
            'ufsc-settings',
            'ufsc_csv_export_section'
        );

        // License fields to export
        add_settings_field(
            'license_export_fields',
            __('Champs licences à exporter', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'license_export_fields_callback'),
            'ufsc-settings',
            'ufsc_csv_export_section'
        );

        // License status filter
        add_settings_field(
            'license_status_filter',
            __('Filtre statut licences', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'license_status_filter_callback'),
            'ufsc-settings',
            'ufsc_csv_export_section'
        );

        // Custom filename
        add_settings_field(
            'custom_filename',
            __('Nom de fichier personnalisé', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'custom_filename_callback'),
            'ufsc-settings',
            'ufsc_csv_export_section'
        );

        // ================================
        // VALIDATION WORKFLOW SECTION
        // ================================
        add_settings_section(
            'ufsc_validation_section',
            __('Workflow de validation', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'validation_section_callback'),
            'ufsc-settings'
        );

        // Enable manual validation
        add_settings_field(
            'enable_manual_validation',
            __('Validation manuelle des licences', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'enable_manual_validation_callback'),
            'ufsc-settings',
            'ufsc_validation_section'
        );

        // Enable email notifications
        add_settings_field(
            'enable_email_notifications',
            __('Notifications email automatiques', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'enable_email_notifications_callback'),
            'ufsc-settings',
            'ufsc_validation_section'
        );

        // Email validation message
        add_settings_field(
            'email_validation_message',
            __('Message email validation', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'email_validation_message_callback'),
            'ufsc-settings',
            'ufsc_validation_section'
        );

        // Email rejection message
        add_settings_field(
            'email_rejection_message',
            __('Message email refus', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'email_rejection_message_callback'),
            'ufsc-settings',
            'ufsc_validation_section'
        );

        // ================================
        // FRONTEND DISPLAY SECTION
        // ================================
        add_settings_section(
            'ufsc_frontend_section',
            __('Affichage Front-End', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'frontend_section_callback'),
            'ufsc-settings'
        );

        // Visible club fields frontend
        add_settings_field(
            'frontend_club_fields',
            __('Champs clubs visibles (frontend)', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'frontend_club_fields_callback'),
            'ufsc-settings',
            'ufsc_frontend_section'
        );

        // Visible license fields frontend
        add_settings_field(
            'frontend_license_fields',
            __('Champs licences visibles (frontend)', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'frontend_license_fields_callback'),
            'ufsc-settings',
            'ufsc_frontend_section'
        );

        // ================================
        // SECURITY & GDPR SECTION
        // ================================
        add_settings_section(
            'ufsc_security_section',
            __('Sécurité & RGPD', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'security_section_callback'),
            'ufsc-settings'
        );

        // Hide sensitive fields in exports
        add_settings_field(
            'hide_sensitive_export',
            __('Masquer champs sensibles (export)', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'hide_sensitive_export_callback'),
            'ufsc-settings',
            'ufsc_security_section'
        );

        // Hide sensitive fields in frontend
        add_settings_field(
            'hide_sensitive_frontend',
            __('Masquer champs sensibles (frontend)', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'hide_sensitive_frontend_callback'),
            'ufsc-settings',
            'ufsc_security_section'
        );

        // ================================
        // MISCELLANEOUS SECTION
        // ================================
        add_settings_section(
            'ufsc_misc_section',
            __('Divers', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'misc_section_callback'),
            'ufsc-settings'
        );

        // Custom logo for exports
        add_settings_field(
            'export_logo_url',
            __('Logo personnalisé pour exports', 'plugin-ufsc-gestion-club-13072025'),
            array($this, 'export_logo_callback'),
            'ufsc-settings',
            'ufsc_misc_section'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($settings)
    {
        $sanitized = array();
        
        // General settings
        if (isset($settings['plugin_active'])) {
            $sanitized['plugin_active'] = (bool) $settings['plugin_active'];
        }
        
        // CSV Export settings
        if (isset($settings['csv_separator'])) {
            $sanitized['csv_separator'] = sanitize_text_field($settings['csv_separator']);
        }
        if (isset($settings['csv_encoding'])) {
            $sanitized['csv_encoding'] = sanitize_text_field($settings['csv_encoding']);
        }
        if (isset($settings['club_export_fields']) && is_array($settings['club_export_fields'])) {
            $sanitized['club_export_fields'] = array_map('sanitize_text_field', $settings['club_export_fields']);
        }
        if (isset($settings['license_export_fields']) && is_array($settings['license_export_fields'])) {
            $sanitized['license_export_fields'] = array_map('sanitize_text_field', $settings['license_export_fields']);
        }
        if (isset($settings['license_status_filter'])) {
            $sanitized['license_status_filter'] = sanitize_text_field($settings['license_status_filter']);
        }
        if (isset($settings['custom_filename'])) {
            $sanitized['custom_filename'] = sanitize_text_field($settings['custom_filename']);
        }
        
        // Validation workflow settings
        if (isset($settings['enable_manual_validation'])) {
            $sanitized['enable_manual_validation'] = (bool) $settings['enable_manual_validation'];
        }
        if (isset($settings['enable_email_notifications'])) {
            $sanitized['enable_email_notifications'] = (bool) $settings['enable_email_notifications'];
        }
        if (isset($settings['email_validation_message'])) {
            $sanitized['email_validation_message'] = sanitize_textarea_field($settings['email_validation_message']);
        }
        if (isset($settings['email_rejection_message'])) {
            $sanitized['email_rejection_message'] = sanitize_textarea_field($settings['email_rejection_message']);
        }
        
        // Frontend display settings
        if (isset($settings['frontend_club_fields']) && is_array($settings['frontend_club_fields'])) {
            $sanitized['frontend_club_fields'] = array_map('sanitize_text_field', $settings['frontend_club_fields']);
        }
        if (isset($settings['frontend_license_fields']) && is_array($settings['frontend_license_fields'])) {
            $sanitized['frontend_license_fields'] = array_map('sanitize_text_field', $settings['frontend_license_fields']);
        }
        
        // Security & GDPR settings
        if (isset($settings['hide_sensitive_export']) && is_array($settings['hide_sensitive_export'])) {
            $sanitized['hide_sensitive_export'] = array_map('sanitize_text_field', $settings['hide_sensitive_export']);
        }
        if (isset($settings['hide_sensitive_frontend']) && is_array($settings['hide_sensitive_frontend'])) {
            $sanitized['hide_sensitive_frontend'] = array_map('sanitize_text_field', $settings['hide_sensitive_frontend']);
        }
        
        // Miscellaneous settings
        if (isset($settings['export_logo_url'])) {
            $sanitized['export_logo_url'] = esc_url_raw($settings['export_logo_url']);
        }
        
        return $sanitized;
    }

    /**
     * General section callback
     */
    public function general_section_callback()
    {
        echo '<p>' . esc_html__('Configurez les paramètres généraux du plugin UFSC.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * Plugin active field callback
     */
    public function plugin_active_field_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $active = isset($options['plugin_active']) ? $options['plugin_active'] : true;
        
        echo '<input type="checkbox" id="ufsc_plugin_active" name="ufsc_general_settings[plugin_active]" value="1" ' . checked(1, $active, false) . ' />';
        echo '<label for="ufsc_plugin_active">' . esc_html__('Activer le plugin UFSC', 'plugin-ufsc-gestion-club-13072025') . '</label>';
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        ?>
        <div class="wrap ufsc-ui">
            <h1>
                <span class="dashicons dashicons-admin-settings" style="font-size: 1.3em; margin-right: 8px;"></span>
                <?php echo esc_html__('Paramètres du plugin UFSC', 'plugin-ufsc-gestion-club-13072025'); ?>
            </h1>
            <p class="description"><?php echo esc_html__('Configurez les options et paramètres du plugin UFSC. Personnalisez le comportement selon vos besoins et ceux de votre fédération.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
            
            <div class="ufsc-settings-container" style="max-width: 1200px;">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('ufsc_settings');
                    do_settings_sections('ufsc-settings');
                    submit_button(__('Enregistrer les paramètres', 'plugin-ufsc-gestion-club-13072025'), 'primary', 'submit', true, array('style' => 'margin-top: 20px;'));
                    ?>
                </form>
                
                <div class="card" style="margin-top: 30px; padding: 20px; background: #e7f3ff; border-left: 4px solid #0073aa;">
                    <h3>
                        <span class="dashicons dashicons-info" style="color: #0073aa;"></span>
                        <?php esc_html_e('Informations importantes', 'plugin-ufsc-gestion-club-13072025'); ?>
                    </h3>
                    <ul>
                        <li><strong><?php esc_html_e('Exports CSV :', 'plugin-ufsc-gestion-club-13072025'); ?></strong> <?php esc_html_e('Les paramètres d\'export s\'appliquent immédiatement aux nouveaux exports.', 'plugin-ufsc-gestion-club-13072025'); ?></li>
                        <li><strong><?php esc_html_e('Validation manuelle :', 'plugin-ufsc-gestion-club-13072025'); ?></strong> <?php esc_html_e('Si désactivée, toutes les nouvelles licences seront automatiquement validées.', 'plugin-ufsc-gestion-club-13072025'); ?></li>
                        <li><strong><?php esc_html_e('RGPD :', 'plugin-ufsc-gestion-club-13072025'); ?></strong> <?php esc_html_e('Les options de masquage des champs sensibles aident à respecter les exigences de protection des données.', 'plugin-ufsc-gestion-club-13072025'); ?></li>
                        <li><strong><?php esc_html_e('Frontend :', 'plugin-ufsc-gestion-club-13072025'); ?></strong> <?php esc_html_e('Les modifications d\'affichage frontend peuvent nécessiter de vider le cache.', 'plugin-ufsc-gestion-club-13072025'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <style>
        .ufsc-settings-container .form-table th {
            width: 250px;
            padding: 15px 0;
        }
        .ufsc-settings-container .form-table td {
            padding: 15px 0;
        }
        .ufsc-settings-container fieldset {
            margin: 0;
            padding: 0;
            border: none;
        }
        .ufsc-settings-container fieldset label {
            display: block;
            margin: 5px 0;
            font-weight: normal;
        }
        .ufsc-settings-container fieldset label input[type="checkbox"] {
            margin-right: 8px;
        }
        .ufsc-settings-container h2 {
            background: #f1f1f1;
            padding: 10px 15px;
            margin: 20px 0 0 0;
            border-left: 4px solid #0073aa;
            font-size: 16px;
        }
        .ufsc-settings-container .card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 0;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        </style>
        <?php
    }

    /**
     * Render ajouter licence page
     */
    public function render_ajouter_licence_page()
    {
        global $wpdb;
        
        // Load required files
        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        require_once UFSC_PLUGIN_PATH . 'includes/helpers.php';
        
        // Get all clubs for selection
        $clubs = $wpdb->get_results("SELECT id, nom FROM {$wpdb->prefix}ufsc_clubs ORDER BY nom");
        
        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('ufsc_add_licence_admin', 'ufsc_add_licence_admin_nonce')) {
            $manager = new UFSC_Licence_Manager();
            
            $club_id = isset($_POST['club_id']) ? intval($_POST['club_id']) : 0;
            if (!$club_id) {
                echo '<div class="notice notice-error"><p>Veuillez sélectionner un club.</p></div>';
            } else {
                $data = [
                    'club_id'                     => $club_id,
                    'nom'                         => isset($_POST['nom']) ? sanitize_text_field($_POST['nom']) : '',
                    'prenom'                      => isset($_POST['prenom']) ? sanitize_text_field($_POST['prenom']) : '',
                    'sexe'                        => (isset($_POST['sexe']) && $_POST['sexe'] === 'F') ? 'F' : 'M',
                    'date_naissance'             => isset($_POST['date_naissance']) ? sanitize_text_field($_POST['date_naissance']) : '',
                    'email'                       => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
                    'adresse'                     => isset($_POST['adresse']) ? sanitize_text_field($_POST['adresse']) : '',
                    'suite_adresse'              => isset($_POST['suite_adresse']) ? sanitize_text_field($_POST['suite_adresse']) : '',
                    'code_postal'                => isset($_POST['code_postal']) ? sanitize_text_field($_POST['code_postal']) : '',
                    'ville'                      => isset($_POST['ville']) ? sanitize_text_field($_POST['ville']) : '',
                    'tel_fixe'                   => isset($_POST['tel_fixe']) ? sanitize_text_field($_POST['tel_fixe']) : '',
                    'tel_mobile'                 => isset($_POST['tel_mobile']) ? sanitize_text_field($_POST['tel_mobile']) : '',
                    'reduction_benevole'         => isset($_POST['reduction_benevole']) ? intval($_POST['reduction_benevole']) : 0,
                    'reduction_postier'          => isset($_POST['reduction_postier']) ? intval($_POST['reduction_postier']) : 0,
                    'identifiant_laposte'        => isset($_POST['identifiant_laposte']) ? sanitize_text_field($_POST['identifiant_laposte']) : '',
                    'profession'                 => isset($_POST['profession']) ? sanitize_text_field($_POST['profession']) : '',
                    'fonction_publique'          => isset($_POST['fonction_publique']) ? intval($_POST['fonction_publique']) : 0,
                    'competition'                => isset($_POST['competition']) ? intval($_POST['competition']) : 0,
                    'licence_delegataire'        => isset($_POST['licence_delegataire']) ? intval($_POST['licence_delegataire']) : 0,
                    'numero_licence_delegataire' => isset($_POST['numero_licence_delegataire']) ? sanitize_text_field($_POST['numero_licence_delegataire']) : '',
                    'diffusion_image'            => isset($_POST['diffusion_image']) ? intval($_POST['diffusion_image']) : 0,
                    'infos_fsasptt'              => isset($_POST['infos_fsasptt']) ? intval($_POST['infos_fsasptt']) : 0,
                    'infos_asptt'                => isset($_POST['infos_asptt']) ? intval($_POST['infos_asptt']) : 0,
                    'infos_cr'                   => isset($_POST['infos_cr']) ? intval($_POST['infos_cr']) : 0,
                    'infos_partenaires'          => isset($_POST['infos_partenaires']) ? intval($_POST['infos_partenaires']) : 0,
                    'honorabilite'               => isset($_POST['honorabilite']) ? intval($_POST['honorabilite']) : 0,
                    'assurance_dommage_corporel' => isset($_POST['assurance_dommage_corporel']) ? intval($_POST['assurance_dommage_corporel']) : 0,
                    'assurance_assistance'       => isset($_POST['assurance_assistance']) ? intval($_POST['assurance_assistance']) : 0,
                    'note'                       => isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '',
                    'region'                     => isset($_POST['region']) ? sanitize_text_field($_POST['region']) : '',
                    'is_included'                => isset($_POST['is_included']) ? 1 : 0,
                ];
                
                $licence_id = $manager->add_licence($data);
                
                if ($licence_id) {
                    echo '<div class="notice notice-success"><p>✅ Licence ajoutée avec succès (ID: ' . $licence_id . ').</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>❌ Erreur lors de l\'ajout de la licence.</p></div>';
                }
            }
        }
        
        
        // Enqueue the license form CSS and JS
        wp_enqueue_style(
            'ufsc-licence-form-style',
            UFSC_PLUGIN_URL . 'assets/css/form-licence.css',
            [],
            UFSC_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'ufsc-licence-form-script',
            UFSC_PLUGIN_URL . 'assets/js/form-licence.js',
            ['jquery'],
            UFSC_PLUGIN_VERSION,
            true
        );
        ?>
        <div class="wrap ufsc-ui">
            <h1><?php echo esc_html__('Ajouter une nouvelle licence', 'plugin-ufsc-gestion-club-13072025'); ?></h1>
            <p><?php echo esc_html__('Créez une nouvelle licence pour un licencié. Remplissez le formulaire avec les informations du licencié.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('ufsc_add_licence_admin', 'ufsc_add_licence_admin_nonce'); ?>
                
                <?php 
                // Set current licence to null for new licence creation
                $current_licence = null;
                // Include the comprehensive licence form
                require_once UFSC_PLUGIN_PATH . 'includes/frontend/parts/form-licence.php'; 
                ?>
                
                <!-- Additional admin-specific fields -->
                <div class="ufsc-form-section ufsc-section-admin">
                    <h3><?php echo esc_html__('Options administratives', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
                    <div class="ufsc-checkbox-group">
                        <div class="ufsc-checkbox-item">
                            <input type="checkbox" name="is_included" id="is_included" value="1">
                            <label for="is_included"><?php echo esc_html__('Cette licence est incluse dans le quota', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                        </div>
                    </div>
                </div>
                
                <?php submit_button(__('Ajouter la licence', 'plugin-ufsc-gestion-club-13072025')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render modifier licence page
     */
    public function render_modifier_licence_page()
    {
        require_once UFSC_PLUGIN_PATH . 'includes/licences/admin-licence-edit.php';
    }
    
    /**
     * Process license export with filters and type support
     */
    private function process_license_export()
    {
        global $wpdb;
        
        // Get filter parameters from POST
        $search = isset($_POST['search_nom']) ? sanitize_text_field(wp_unslash($_POST['search_nom'])) : '';
        $search_prenom = isset($_POST['search_prenom']) ? sanitize_text_field(wp_unslash($_POST['search_prenom'])) : '';
        $search_email = isset($_POST['search_email']) ? sanitize_email(wp_unslash($_POST['search_email'])) : '';
        $search_ville = isset($_POST['search_ville']) ? sanitize_text_field(wp_unslash($_POST['search_ville'])) : '';
        $search_telephone = isset($_POST['search_telephone']) ? sanitize_text_field(wp_unslash($_POST['search_telephone'])) : '';
        $date_naissance_from = isset($_POST['date_naissance_from']) ? sanitize_text_field(wp_unslash($_POST['date_naissance_from'])) : '';
        $date_naissance_to = isset($_POST['date_naissance_to']) ? sanitize_text_field(wp_unslash($_POST['date_naissance_to'])) : '';
        $date_inscription_from = isset($_POST['date_inscription_from']) ? sanitize_text_field(wp_unslash($_POST['date_inscription_from'])) : '';
        $date_inscription_to = isset($_POST['date_inscription_to']) ? sanitize_text_field(wp_unslash($_POST['date_inscription_to'])) : '';
        $region = isset($_POST['region']) ? sanitize_text_field(wp_unslash($_POST['region'])) : '';
        $filter_club = isset($_POST['filter_club']) ? intval(wp_unslash($_POST['filter_club'])) : 0;
        $export_type = isset($_POST['export_type']) ? sanitize_text_field(wp_unslash($_POST['export_type'])) : 'individual';
        $statut_filter = isset($_POST['statut_filter']) ? sanitize_text_field(wp_unslash($_POST['statut_filter'])) : '';
        
        // Build WHERE clause
        $where = [];
        $params = [];
        
        if ($search !== '') {
            $where[] = 'l.nom LIKE %s';
            $params[] = '%' . $search . '%';
        }
        if ($search_prenom !== '') {
            $where[] = 'l.prenom LIKE %s';
            $params[] = '%' . $search_prenom . '%';
        }
        if ($search_email !== '') {
            $where[] = 'l.email LIKE %s';
            $params[] = '%' . $search_email . '%';
        }
        if ($search_ville !== '') {
            $where[] = 'l.ville LIKE %s';
            $params[] = '%' . $search_ville . '%';
        }
        if ($search_telephone !== '') {
            $where[] = '(l.tel_fixe LIKE %s OR l.tel_mobile LIKE %s)';
            $params[] = '%' . $search_telephone . '%';
            $params[] = '%' . $search_telephone . '%';
        }
        if ($date_naissance_from !== '') {
            $where[] = 'l.date_naissance >= %s';
            $params[] = $date_naissance_from;
        }
        if ($date_naissance_to !== '') {
            $where[] = 'l.date_naissance <= %s';
            $params[] = $date_naissance_to;
        }
        if ($date_inscription_from !== '') {
            $where[] = 'DATE(l.date_inscription) >= %s';
            $params[] = $date_inscription_from;
        }
        if ($date_inscription_to !== '') {
            $where[] = 'DATE(l.date_inscription) <= %s';
            $params[] = $date_inscription_to;
        }
        if ($region !== '') {
            $where[] = 'l.region = %s';
            $params[] = $region;
        }
        if ($filter_club > 0) {
            $where[] = 'l.club_id = %d';
            $params[] = $filter_club;
        }
        if ($statut_filter !== '') {
            $where[] = 'l.statut = %s';
            $params[] = $statut_filter;
        }
        
        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        if ($export_type === 'group') {
            // Handle grouped export (keep existing logic for now, may be enhanced later)
            $this->process_group_license_export($where_clause, $params);
        } else {
            // Individual export: one line per license
            // Query individual licenses
            if (!empty($params)) {
                $licenses = $wpdb->get_results($wpdb->prepare(
                    "SELECT l.*, c.nom as club_nom, c.region as club_region
                     FROM {$wpdb->prefix}ufsc_licences l
                     LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                     $where_clause
                     ORDER BY c.nom ASC, l.nom ASC, l.prenom ASC",
                    ...$params
                ));
            } else {
                $licenses = $wpdb->get_results(
                    "SELECT l.*, c.nom as club_nom, c.region as club_region
                     FROM {$wpdb->prefix}ufsc_licences l
                     LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                     $where_clause
                     ORDER BY c.nom ASC, l.nom ASC, l.prenom ASC"
                );
            }
            
            // Use the new configurable CSV export
            UFSC_CSV_Export::export_licenses($licenses);
        }
    }
    /**
     * Process group license export (maintains existing functionality)
     */
    private function process_group_license_export($where_clause, $params)
    {
        global $wpdb;
        
        $filename = 'licences_ufsc_groupe_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Grouped export: one line per club with totals
        fputcsv($output, [
            'Club', 'Région', 'Total licences', 'Licences incluses', 'Licences payantes', 
            'Hommes', 'Femmes', 'Compétition', 'Loisir', 'Montant total (€)'
        ]);
        
        // Query grouped by club
        if (!empty($params)) {
            $clubs_data = $wpdb->get_results($wpdb->prepare(
                "SELECT 
                    c.nom as club_nom,
                    c.region as club_region,
                    COUNT(l.id) as total_licences,
                    SUM(l.is_included) as licences_incluses,
                    SUM(CASE WHEN l.is_included = 0 THEN 1 ELSE 0 END) as licences_payantes,
                    SUM(CASE WHEN l.sexe = 'M' THEN 1 ELSE 0 END) as hommes,
                    SUM(CASE WHEN l.sexe = 'F' THEN 1 ELSE 0 END) as femmes,
                    SUM(l.competition) as competition,
                    SUM(CASE WHEN l.competition = 0 THEN 1 ELSE 0 END) as loisir
                 FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 $where_clause
                 GROUP BY l.club_id, c.nom, c.region
                 ORDER BY c.nom ASC",
                ...$params
            ));
        } else {
            $clubs_data = $wpdb->get_results(
                "SELECT 
                    c.nom as club_nom,
                    c.region as club_region,
                    COUNT(l.id) as total_licences,
                    SUM(l.is_included) as licences_incluses,
                    SUM(CASE WHEN l.is_included = 0 THEN 1 ELSE 0 END) as licences_payantes,
                    SUM(CASE WHEN l.sexe = 'M' THEN 1 ELSE 0 END) as hommes,
                    SUM(CASE WHEN l.sexe = 'F' THEN 1 ELSE 0 END) as femmes,
                    SUM(l.competition) as competition,
                    SUM(CASE WHEN l.competition = 0 THEN 1 ELSE 0 END) as loisir
                 FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 $where_clause
                 GROUP BY l.club_id, c.nom, c.region
                 ORDER BY c.nom ASC"
            );
        }
        
        foreach ($clubs_data as $club) {
            $montant_total = $club->licences_payantes * 35; // 35€ per paid license
            fputcsv($output, [
                $club->club_nom,
                $club->club_region,
                $club->total_licences,
                $club->licences_incluses,
                $club->licences_payantes,
                $club->hommes,
                $club->femmes,
                $club->competition,
                $club->loisir,
                number_format($montant_total, 2)
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Process main license list export with filters
     */
    private function process_main_license_export()
    {
        global $wpdb;
        
        // Load the licence filters class
        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-filters.php';
        
        // Get filter parameters from URL
        $filters = UFSC_Licence_Filters::get_filter_parameters();
        
        // Build WHERE clause for export
        $where_data = UFSC_Licence_Filters::build_where_clause($filters);
        $where_clause = $where_data['where_clause'];
        $params = $where_data['params'];
        
        // Get all matching records for export
        if (!empty($params)) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT l.*, c.nom as club_nom, c.region as club_region
                 FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause
                 ORDER BY c.nom ASC, l.nom ASC, l.prenom ASC",
                ...$params
            ));
        } else {
            $rows = $wpdb->get_results(
                "SELECT l.*, c.nom as club_nom, c.region as club_region
                 FROM {$wpdb->prefix}ufsc_licences l
                 LEFT JOIN {$wpdb->prefix}ufsc_clubs c ON l.club_id = c.id
                 WHERE $where_clause
                 ORDER BY c.nom ASC, l.nom ASC, l.prenom ASC"
            );
        }
        
        // Use UFSC-compliant export
        $filename = 'licences_ufsc_' . date('Y-m-d') . '.csv';
        UFSC_CSV_Export::export_licenses($rows, $filename);
    }

    /**
     * Render club view/details page
     */
    public function render_view_club_page()
    {
        // Get club ID from URL parameter
        $club_id = isset($_GET['id']) ? intval(wp_unslash($_GET['id'])) : 0;
        if (!$club_id) {
            echo '<div class="wrap ufsc-ui"><div class="notice notice-error"><p>Aucun club sélectionné.</p></div></div>';
            return;
        }

        // Check if edit mode is requested
        $edit_mode = isset($_GET['edit']) && $_GET['edit'] === '1';

        // Get club data
        $club_manager = UFSC_Club_Manager::get_instance();
        $club = $club_manager->get_club($club_id);
        if (!$club) {
            echo '<div class="wrap ufsc-ui"><div class="notice notice-error"><p>Club introuvable.</p></div></div>';
            return;
        }

        // Handle form submission for club update
        $form_submitted = false;
        $errors = [];
        $success = false;

        if ($edit_mode && isset($_POST['ufsc_update_club_submit']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $form_submitted = true;

            // Verify nonce
            if (!isset($_POST['ufsc_update_club_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ufsc_update_club_nonce']), 'ufsc_update_club_nonce')) {
                $errors[] = 'Erreur de sécurité. Veuillez recharger la page.';
            } else {
                // Sanitize and collect form data
                $club_data = [
                    'nom' => isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '',
                    'adresse' => isset($_POST['adresse']) ? sanitize_textarea_field(wp_unslash($_POST['adresse'])) : '',
                    'complement_adresse' => isset($_POST['complement_adresse']) ? sanitize_text_field(wp_unslash($_POST['complement_adresse'])) : '',
                    'code_postal' => isset($_POST['code_postal']) ? sanitize_text_field(wp_unslash($_POST['code_postal'])) : '',
                    'ville' => isset($_POST['ville']) ? sanitize_text_field(wp_unslash($_POST['ville'])) : '',
                    'region' => isset($_POST['region']) ? sanitize_text_field(wp_unslash($_POST['region'])) : '',
                    'precision_distribution' => isset($_POST['precision_distribution']) ? sanitize_text_field(wp_unslash($_POST['precision_distribution'])) : '',
                    'url_site' => isset($_POST['url_site']) ? esc_url_raw(wp_unslash($_POST['url_site'])) : '',
                    'url_facebook' => isset($_POST['url_facebook']) ? esc_url_raw(wp_unslash($_POST['url_facebook'])) : '',
                    'telephone' => isset($_POST['telephone']) ? sanitize_text_field(wp_unslash($_POST['telephone'])) : '',
                    'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
                    'num_declaration' => isset($_POST['num_declaration']) ? sanitize_text_field(wp_unslash($_POST['num_declaration'])) : '',
                    'date_declaration' => isset($_POST['date_declaration']) ? sanitize_text_field(wp_unslash($_POST['date_declaration'])) : '',
                    'siren' => isset($_POST['siren']) ? sanitize_text_field(wp_unslash($_POST['siren'])) : '',
                    'ape' => isset($_POST['ape']) ? sanitize_text_field(wp_unslash($_POST['ape'])) : '',
                    'ccn' => isset($_POST['ccn']) ? sanitize_text_field(wp_unslash($_POST['ccn'])) : '',
                    'ancv' => isset($_POST['ancv']) ? sanitize_text_field(wp_unslash($_POST['ancv'])) : '',
                    'president_nom' => isset($_POST['president_nom']) ? sanitize_text_field(wp_unslash($_POST['president_nom'])) : '',
                    'president_tel' => isset($_POST['president_tel']) ? sanitize_text_field(wp_unslash($_POST['president_tel'])) : '',
                    'president_email' => isset($_POST['president_email']) ? sanitize_email(wp_unslash($_POST['president_email'])) : '',
                    'secretaire_nom' => isset($_POST['secretaire_nom']) ? sanitize_text_field(wp_unslash($_POST['secretaire_nom'])) : '',
                    'secretaire_tel' => isset($_POST['secretaire_tel']) ? sanitize_text_field(wp_unslash($_POST['secretaire_tel'])) : '',
                    'secretaire_email' => isset($_POST['secretaire_email']) ? sanitize_email(wp_unslash($_POST['secretaire_email'])) : '',
                    'tresorier_nom' => isset($_POST['tresorier_nom']) ? sanitize_text_field(wp_unslash($_POST['tresorier_nom'])) : '',
                    'tresorier_tel' => isset($_POST['tresorier_tel']) ? sanitize_text_field(wp_unslash($_POST['tresorier_tel'])) : '',
                    'tresorier_email' => isset($_POST['tresorier_email']) ? sanitize_email(wp_unslash($_POST['tresorier_email'])) : '',
                    'entraineur_nom' => isset($_POST['entraineur_nom']) ? sanitize_text_field(wp_unslash($_POST['entraineur_nom'])) : '',
                    'entraineur_tel' => isset($_POST['entraineur_tel']) ? sanitize_text_field(wp_unslash($_POST['entraineur_tel'])) : '',
                    'entraineur_email' => isset($_POST['entraineur_email']) ? sanitize_email(wp_unslash($_POST['entraineur_email'])) : '',
                ];

                // Validation of required fields
                if (empty($club_data['nom'])) {
                    $errors[] = 'Le nom de l\'association est obligatoire.';
                }
                if (empty($club_data['adresse'])) {
                    $errors[] = 'Le numéro et nom de rue est obligatoire.';
                }
                if (empty($club_data['code_postal'])) {
                    $errors[] = 'Le code postal est obligatoire.';
                }
                if (empty($club_data['ville'])) {
                    $errors[] = 'La ville est obligatoire.';
                }
                if (empty($club_data['region'])) {
                    $errors[] = 'La région est obligatoire.';
                }
                if (empty($club_data['telephone'])) {
                    $errors[] = 'Le téléphone de l\'association est obligatoire.';
                }
                if (empty($club_data['email'])) {
                    $errors[] = 'L\'adresse email de l\'association est obligatoire.';
                }

                // Validate postal code format
                if (!empty($club_data['code_postal']) && !preg_match('/^[0-9]{5}$/', $club_data['code_postal'])) {
                    $errors[] = 'Le code postal doit contenir exactement 5 chiffres.';
                }

                // Validate email format
                if (!empty($club_data['email']) && !is_email($club_data['email'])) {
                    $errors[] = 'L\'adresse email de l\'association n\'est pas valide.';
                }

                // Validate SIREN format if provided
                if (!empty($club_data['siren']) && !preg_match('/^[0-9]{9}$/', $club_data['siren'])) {
                    $errors[] = 'Le numéro SIREN doit contenir exactement 9 chiffres.';
                }

                // Update club if no errors
                if (empty($errors)) {
                    $result = $club_manager->update_club($club_id, $club_data);

                    if ($result) {
                        $success = true;
                        // Reload the club data to show updated values
                        $club = $club_manager->get_club($club_id);
                    } else {
                        $errors[] = 'Erreur lors de la mise à jour du club. Veuillez réessayer.';
                    }
                }
            }
        }

        // Check if user wants to delete the club
        if (isset($_POST['delete_club']) && wp_verify_nonce(wp_unslash($_POST['delete_club_nonce'] ?? ''), 'delete_club_' . $club_id)) {
            if ($club_manager->delete_club($club_id)) {
                wp_redirect(admin_url('admin.php?page=ufsc-liste-clubs&deleted=1'));
                exit;
            } else {
                $error_message = 'Erreur lors de la suppression du club.';
            }
        }

        // Load regions for dropdown in edit mode
        if ($edit_mode) {
            $regions = require UFSC_PLUGIN_PATH . 'data/regions.php';
        }
        ?>
        <div class="wrap ufsc-ui">
            <h1>
                <?php if ($edit_mode): ?>
                    <?php echo esc_html__('Modifier le club', 'plugin-ufsc-gestion-club-13072025'); ?> - <?php echo esc_html($club->nom); ?>
                <?php else: ?>
                    <?php echo esc_html__('Détails du club', 'plugin-ufsc-gestion-club-13072025'); ?> - <?php echo esc_html($club->nom); ?>
                <?php endif; ?>
            </h1>
            
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc-liste-clubs')); ?>" class="button">
                    ← Retour à la liste des clubs
                </a>
                <?php if ($edit_mode): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_view_club&id=' . $club->id)); ?>" class="button">
                        👁️ Voir en lecture seule
                    </a>
                <?php else: ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_view_club&id=' . $club->id . '&edit=1')); ?>" class="button button-primary">
                        ✏️ Modifier
                    </a>
                <?php endif; ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_edit_club&id=' . $club->id)); ?>" class="button button-secondary">
                    📄 Gestion des documents
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_voir_licences&club_id=' . $club->id)); ?>" class="button button-secondary">
                    👥 Voir les licences
                </a>
            </p>

            <?php if (isset($error_message)): ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html($error_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($form_submitted): ?>
                <?php if ($success): ?>
                    <div class="notice notice-success">
                        <p><strong>Succès :</strong> Le club a été mis à jour avec succès!</p>
                    </div>
                <?php elseif (!empty($errors)): ?>
                    <div class="notice notice-error">
                        <p><strong>Erreurs détectées :</strong></p>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($edit_mode): ?>
            <!-- Edit Mode Form -->
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=ufsc_view_club&id=' . $club->id . '&edit=1')); ?>" id="ufsc-club-edit-form">
                <?php wp_nonce_field('ufsc_update_club_nonce', 'ufsc_update_club_nonce'); ?>
                <input type="hidden" name="ufsc_update_club_submit" value="1">
                
                <!-- Informations générales -->
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-groups"></span> Informations générales</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="nom">Nom du club / association <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="nom" id="nom" type="text" class="regular-text" value="<?php echo esc_attr($club->nom); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="adresse">Numéro et nom de rue <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="adresse" id="adresse" type="text" class="regular-text" value="<?php echo esc_attr($club->adresse); ?>" required>
                                <p class="description">Exemple: 123 rue de la République</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="complement_adresse">Complément adresse</label>
                            </th>
                            <td>
                                <input name="complement_adresse" id="complement_adresse" type="text" class="regular-text" value="<?php echo esc_attr($club->complement_adresse); ?>">
                                <p class="description">Immeuble, bâtiment, etc.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="code_postal">Code postal <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="code_postal" id="code_postal" type="text" class="regular-text" value="<?php echo esc_attr($club->code_postal); ?>" pattern="[0-9]{5}" maxlength="5" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ville">Ville <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="ville" id="ville" type="text" class="regular-text" value="<?php echo esc_attr($club->ville); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="region">Région <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <select name="region" id="region" required>
                                    <option value="">-- Choisir une région --</option>
                                    <?php foreach ($regions as $region): ?>
                                        <option value="<?php echo esc_attr($region); ?>" <?php selected($club->region, $region); ?>>
                                            <?php echo esc_html($region); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="precision_distribution">Précision distribution</label>
                            </th>
                            <td>
                                <input name="precision_distribution" id="precision_distribution" type="text" class="regular-text" value="<?php echo esc_attr($club->precision_distribution); ?>">
                                <p class="description">BP, etc.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="telephone">Téléphone de l'association <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="telephone" id="telephone" type="tel" class="regular-text" value="<?php echo esc_attr($club->telephone); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="email">Adresse email de l'association <span class="ufsc-required">*</span></label>
                            </th>
                            <td>
                                <input name="email" id="email" type="email" class="regular-text" value="<?php echo esc_attr($club->email); ?>" required>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="url_site">URL du site internet</label>
                            </th>
                            <td>
                                <input name="url_site" id="url_site" type="url" class="regular-text" value="<?php echo esc_attr($club->url_site); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="url_facebook">URL page Facebook</label>
                            </th>
                            <td>
                                <input name="url_facebook" id="url_facebook" type="url" class="regular-text" value="<?php echo esc_attr($club->url_facebook); ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Informations légales -->
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-clipboard"></span> Informations légales</h2>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="num_declaration">N° de déclaration en préfecture</label>
                            </th>
                            <td>
                                <input name="num_declaration" id="num_declaration" type="text" class="regular-text" value="<?php echo esc_attr($club->num_declaration); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="date_declaration">Date de déclaration en préfecture</label>
                            </th>
                            <td>
                                <input name="date_declaration" id="date_declaration" type="date" class="regular-text" value="<?php echo esc_attr($club->date_declaration); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="siren">Numéro SIREN</label>
                            </th>
                            <td>
                                <input name="siren" id="siren" type="text" class="regular-text" value="<?php echo esc_attr($club->siren); ?>" pattern="[0-9]{9}" maxlength="9">
                                <p class="description">9 chiffres</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ape">Code APE / NAF</label>
                            </th>
                            <td>
                                <input name="ape" id="ape" type="text" class="regular-text" value="<?php echo esc_attr($club->ape); ?>">
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ccn">Convention collective</label>
                            </th>
                            <td>
                                <select name="ccn" id="ccn">
                                    <option value="">-- Choisir --</option>
                                    <option value="CCNS" <?php selected($club->ccn, 'CCNS'); ?>>CCNS</option>
                                    <option value="Animation" <?php selected($club->ccn, 'Animation'); ?>>Animation</option>
                                    <option value="Autres" <?php selected($club->ccn, 'Autres'); ?>>Autres</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="ancv">Numéro ANCV</label>
                            </th>
                            <td>
                                <input name="ancv" id="ancv" type="text" class="regular-text" value="<?php echo esc_attr($club->ancv); ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Dirigeants -->
                <div class="ufsc-admin-section">
                    <h2><span class="dashicons dashicons-businessperson"></span> Dirigeants</h2>
                    
                    <?php 
                    $dirigeants = [
                        'president' => 'Président',
                        'secretaire' => 'Secrétaire', 
                        'tresorier' => 'Trésorier',
                        'entraineur' => 'Entraîneur (facultatif)'
                    ];
                    
                    foreach ($dirigeants as $key => $label): ?>
                        <h3><?php echo esc_html($label); ?></h3>
                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($key); ?>_nom">Nom et prénom</label>
                                </th>
                                <td>
                                    <input name="<?php echo esc_attr($key); ?>_nom" id="<?php echo esc_attr($key); ?>_nom" type="text" class="regular-text" value="<?php echo esc_attr($club->{$key . '_nom'}); ?>">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($key); ?>_tel">Téléphone</label>
                                </th>
                                <td>
                                    <input name="<?php echo esc_attr($key); ?>_tel" id="<?php echo esc_attr($key); ?>_tel" type="tel" class="regular-text" value="<?php echo esc_attr($club->{$key . '_tel'}); ?>">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($key); ?>_email">Adresse email</label>
                                </th>
                                <td>
                                    <input name="<?php echo esc_attr($key); ?>_email" id="<?php echo esc_attr($key); ?>_email" type="email" class="regular-text" value="<?php echo esc_attr($club->{$key . '_email'}); ?>">
                                </td>
                            </tr>
                        </table>
                    <?php endforeach; ?>
                </div>

                <p class="submit">
                    <?php submit_button(__('Mettre à jour le club', 'plugin-ufsc-gestion-club-13072025'), 'primary', 'submit', false); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_view_club&id=' . $club->id)); ?>" class="button button-secondary">Annuler</a>
                </p>
            </form>
            
            <?php else: ?>
            <!-- View Mode -->
            <div class="ufsc-club-details">
                <!-- Informations générales -->
                <div class="ufsc-detail-section">
                    <h2><span class="dashicons dashicons-groups"></span> Informations générales</h2>
                    <table class="form-table" role="presentation">
                        <tr><th>Nom du club / association</th><td><?php echo esc_html($club->nom); ?></td></tr>
                        <tr><th>Email</th><td><?php echo esc_html($club->email); ?></td></tr>
                        <tr><th>Téléphone</th><td><?php echo esc_html($club->telephone); ?></td></tr>
                        <tr><th>Adresse</th><td><?php echo esc_html($club->adresse); ?></td></tr>
                        <?php if ($club->complement_adresse): ?>
                        <tr><th>Complément d'adresse</th><td><?php echo esc_html($club->complement_adresse); ?></td></tr>
                        <?php endif; ?>
                        <tr><th>Code postal</th><td><?php echo esc_html($club->code_postal); ?></td></tr>
                        <tr><th>Ville</th><td><?php echo esc_html($club->ville); ?></td></tr>
                        <tr><th>Région</th><td><?php echo esc_html($club->region); ?></td></tr>
                        <?php if ($club->url_site): ?>
                        <tr><th>Site internet</th><td><a href="<?php echo esc_url($club->url_site); ?>" target="_blank"><?php echo esc_html($club->url_site); ?></a></td></tr>
                        <?php endif; ?>
                        <?php if ($club->url_facebook): ?>
                        <tr><th>Page Facebook</th><td><a href="<?php echo esc_url($club->url_facebook); ?>" target="_blank"><?php echo esc_html($club->url_facebook); ?></a></td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Informations légales -->
                <div class="ufsc-detail-section">
                    <h2><span class="dashicons dashicons-clipboard"></span> Informations légales</h2>
                    <table class="form-table" role="presentation">
                        <?php if ($club->num_declaration): ?>
                        <tr><th>N° de déclaration</th><td><?php echo esc_html($club->num_declaration); ?></td></tr>
                        <?php endif; ?>
                        <?php if ($club->date_declaration): ?>
                        <tr><th>Date de déclaration</th><td><?php echo esc_html(date('d/m/Y', strtotime($club->date_declaration))); ?></td></tr>
                        <?php endif; ?>
                        <?php if ($club->siren): ?>
                        <tr><th>SIREN</th><td><?php echo esc_html($club->siren); ?></td></tr>
                        <?php endif; ?>
                        <?php if ($club->ape): ?>
                        <tr><th>Code APE</th><td><?php echo esc_html($club->ape); ?></td></tr>
                        <?php endif; ?>
                        <?php if ($club->ccn): ?>
                        <tr><th>Convention collective</th><td><?php echo esc_html($club->ccn); ?></td></tr>
                        <?php endif; ?>
                        <tr><th>Statut</th><td>
                            <?php 
                            $status_labels = [
                                'en_attente' => 'En attente',
                                'Actif' => 'Validé',
                                'refuse' => 'Refusé',
                                'archive' => 'Archivé'
                            ];
                            $status = $club->statut ?: 'en_attente';
                            echo esc_html($status_labels[$status] ?? 'Inconnu');
                            ?>
                        </td></tr>
                        <tr><th>Date de création</th><td><?php echo esc_html(date('d/m/Y H:i', strtotime($club->date_creation))); ?></td></tr>
                    </table>
                </div>

                <!-- Dirigeants -->
                <div class="ufsc-detail-section">
                    <h2><span class="dashicons dashicons-businessperson"></span> Dirigeants</h2>
                    <?php 
                    $dirigeants = [
                        'president' => 'Président',
                        'secretaire' => 'Secrétaire',
                        'tresorier' => 'Trésorier',
                        'entraineur' => 'Entraîneur'
                    ];
                    
                    foreach ($dirigeants as $key => $label):
                        $nom = $club->{$key . '_nom'};
                        $tel = $club->{$key . '_tel'};
                        $email = $club->{$key . '_email'};
                        
                        if ($nom || $tel || $email):
                    ?>
                        <h3><?php echo esc_html($label); ?></h3>
                        <table class="form-table" role="presentation">
                            <?php if ($nom): ?>
                            <tr><th>Nom</th><td><?php echo esc_html($nom); ?></td></tr>
                            <?php endif; ?>
                            <?php if ($tel): ?>
                            <tr><th>Téléphone</th><td><?php echo esc_html($tel); ?></td></tr>
                            <?php endif; ?>
                            <?php if ($email): ?>
                            <tr><th>Email</th><td><?php echo esc_html($email); ?></td></tr>
                            <?php endif; ?>
                        </table>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>

                <!-- Zone de danger -->
                <div class="ufsc-detail-section ufsc-danger-zone">
                    <h2><span class="dashicons dashicons-warning"></span> Zone de danger</h2>
                    <p>Cette action est irréversible. Toutes les licences associées à ce club seront également supprimées.</p>
                    <form method="post" onsubmit="return confirmDelete('<?php echo esc_js($club->nom); ?>')">
                        <?php wp_nonce_field('delete_club_' . $club_id, 'delete_club_nonce'); ?>
                        <input type="hidden" name="delete_club" value="1">
                        <button type="submit" class="button button-link-delete">🗑️ Supprimer définitivement ce club</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .ufsc-admin-section {
            background: #fff;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 20px 0;
            padding: 20px;
        }
        .ufsc-admin-section h2 {
            margin-top: 0;
            color: #1d2327;
            border-bottom: 1px solid #c3c4c7;
            padding-bottom: 10px;
        }
        .ufsc-admin-section h2 .dashicons {
            margin-right: 8px;
            color: #2271b1;
        }
        .ufsc-admin-section h3 {
            margin: 20px 0 10px 0;
            color: #1d2327;
        }
        .ufsc-required {
            color: #d63638;
            font-weight: bold;
        }
        #ufsc-club-edit-form .form-table th {
            width: 200px;
            padding: 15px 10px 15px 0;
        }
        #ufsc-club-edit-form .form-table td {
            padding: 15px 10px;
        }
        #ufsc-club-edit-form .regular-text {
            width: 25em;
        }
        .ufsc-detail-section {
            background: #fff;
            border: 1px solid #c3c4c7;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            margin: 20px 0;
            padding: 20px;
        }
        .ufsc-detail-section h2 {
            margin-top: 0;
            color: #1d2327;
            border-bottom: 1px solid #c3c4c7;
            padding-bottom: 10px;
        }
        .ufsc-detail-section h2 .dashicons {
            margin-right: 8px;
            color: #2271b1;
        }
        .ufsc-detail-section h3 {
            margin: 20px 0 10px 0;
            color: #1d2327;
        }
        .ufsc-danger-zone {
            border-left: 4px solid #d63638;
        }
        .ufsc-danger-zone h2 .dashicons {
            color: #d63638;
        }
        .button-link-delete {
            color: #d63638 !important;
            text-decoration: none !important;
            font-weight: normal;
        }
        .button-link-delete:hover {
            color: #a00 !important;
        }

        /* Enhanced visual status badges */
        .ufsc-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            text-align: center;
            min-width: 60px;
        }

        .badge-pink {
            background: linear-gradient(135deg, #e91e63, #f06292);
            box-shadow: 0 2px 4px rgba(233, 30, 99, 0.3);
        }

        .badge-blue {
            background: linear-gradient(135deg, #2196f3, #64b5f6);
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.3);
        }

        .badge-green {
            background: linear-gradient(135deg, #4caf50, #81c784);
            box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
        }

        .badge-orange {
            background: linear-gradient(135deg, #ff9800, #ffb74d);
            box-shadow: 0 2px 4px rgba(255, 152, 0, 0.3);
        }

        .badge-red {
            background: linear-gradient(135deg, #f44336, #ef5350);
            box-shadow: 0 2px 4px rgba(244, 67, 54, 0.3);
        }
        </style>

        <script>
        function confirmDelete(clubName) {
            return confirm('Êtes-vous sûr de vouloir supprimer définitivement le club "' + clubName + '" ?\n\nCette action est irréversible et supprimera également toutes les licences associées.');
        }

        // Client-side validation for edit form
        document.addEventListener('DOMContentLoaded', function() {
            const editForm = document.getElementById('ufsc-club-edit-form');
            if (editForm) {
                const requiredFields = editForm.querySelectorAll('[required]');
                
                editForm.addEventListener('submit', function(e) {
                    let hasErrors = false;
                    
                    requiredFields.forEach(function(field) {
                        if (!field.value.trim()) {
                            field.style.borderColor = '#d63638';
                            hasErrors = true;
                        } else {
                            field.style.borderColor = '';
                        }
                    });
                    
                    // Validate postal code
                    const codePostal = document.getElementById('code_postal');
                    if (codePostal.value && !/^[0-9]{5}$/.test(codePostal.value)) {
                        codePostal.style.borderColor = '#d63638';
                        hasErrors = true;
                    }
                    
                    // Validate SIREN
                    const siren = document.getElementById('siren');
                    if (siren.value && !/^[0-9]{9}$/.test(siren.value)) {
                        siren.style.borderColor = '#d63638';
                        hasErrors = true;
                    }
                    
                    // Validate email fields
                    const emailFields = editForm.querySelectorAll('input[type="email"]');
                    emailFields.forEach(function(emailField) {
                        if (emailField.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
                            emailField.style.borderColor = '#d63638';
                            hasErrors = true;
                        }
                    });
                    
                    if (hasErrors) {
                        e.preventDefault();
                        alert('Veuillez corriger les erreurs dans le formulaire avant de soumettre.');
                    }
                });
                
                // Real-time validation feedback
                requiredFields.forEach(function(field) {
                    field.addEventListener('input', function() {
                        if (this.value.trim()) {
                            this.style.borderColor = '';
                        }
                    });
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Render license view/details page
     */
    public function render_view_licence_page()
    {
        // Get license ID from URL parameter
        $licence_id = isset($_GET['id']) ? intval(wp_unslash($_GET['id'])) : 0;
        if (!$licence_id) {
            echo '<div class="wrap ufsc-ui"><div class="notice notice-error"><p>Aucune licence sélectionnée.</p></div></div>';
            return;
        }

        if (!current_user_can('ufsc_manage_licences')) {
            wp_die(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'));
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(wp_unslash($_GET['_wpnonce']), 'ufsc_view_licence_' . $licence_id)) {
            wp_die(__('Action non autorisée.', 'plugin-ufsc-gestion-club-13072025'));
        }

        // Check if edit mode is requested
        $edit_mode = isset($_GET['edit']) && $_GET['edit'] === '1';

        // Get license data
        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        $licence_manager = new UFSC_Licence_Manager();
        $licence = $licence_manager->get_licence_by_id($licence_id);
        
        if (!$licence) {
            echo '<div class="wrap ufsc-ui"><div class="notice notice-error"><p>Licence introuvable.</p></div></div>';
            return;
        }

        // Get club data
        $club_manager = UFSC_Club_Manager::get_instance();
        $club = $club_manager->get_club($licence->club_id);

        // Handle form submission for license update (if in edit mode)
        if ($edit_mode && isset($_POST['ufsc_update_licence_submit']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Redirect to the dedicated edit page for actual editing
            $redirect_url = wp_nonce_url(
                admin_url('admin.php?page=ufsc-modifier-licence&licence_id=' . $licence_id),
                'ufsc_edit_licence_' . $licence_id
            );
            wp_redirect($redirect_url);
            exit;
        }

        // Check if user wants to delete the license
        if (isset($_POST['delete_licence']) && wp_verify_nonce(wp_unslash($_POST['delete_licence_nonce'] ?? ''), 'delete_licence_' . $licence_id)) {
            if ($licence_manager->delete_licence($licence_id)) {
                $redirect_url = $club ? admin_url('admin.php?page=ufsc_voir_licences&club_id=' . $club->id . '&deleted=1') : admin_url('admin.php?page=ufsc-liste-licences&deleted=1');
                wp_redirect($redirect_url);
                exit;
            } else {
                $error_message = 'Erreur lors de la suppression de la licence.';
            }
        }

        ?>
        <div class="wrap ufsc-ui">
            <h1><?php echo esc_html__('Détails de la licence', 'plugin-ufsc-gestion-club-13072025'); ?> - <?php echo esc_html($licence->prenom . ' ' . $licence->nom); ?></h1>
            
            <p>
                <?php if ($club): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_voir_licences&club_id=' . $club->id)); ?>" class="button">
                    ← Retour aux licences du club
                </a>
                <?php else: ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc-liste-licences')); ?>" class="button">
                    ← Retour à la liste des licences
                </a>
                <?php endif; ?>
                <?php $edit_link = wp_nonce_url(
                    admin_url('admin.php?page=ufsc-modifier-licence&licence_id=' . $licence->id),
                    'ufsc_edit_licence_' . $licence->id
                ); ?>
                <a href="<?php echo esc_url($edit_link); ?>" class="button button-primary">
                    ✏️ Modifier
                </a>
                <?php if ($club): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ufsc_view_club&id=' . $club->id)); ?>" class="button button-secondary">
                    🏢 Voir le club
                </a>
                <?php endif; ?>
            </p>

            <?php if (isset($error_message)): ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html($error_message); ?></p>
                </div>
            <?php endif; ?>

            <div class="ufsc-licence-details">
                <!-- Informations personnelles -->
                <div class="ufsc-detail-section">
                    <h2><span class="dashicons dashicons-admin-users"></span> Informations personnelles</h2>
                    <table class="form-table" role="presentation">
                        <tr><th>Nom</th><td><?php echo esc_html($licence->nom); ?></td></tr>
                        <tr><th>Prénom</th><td><?php echo esc_html($licence->prenom); ?></td></tr>
                        <tr><th>Sexe</th><td><?php echo $licence->sexe === 'F' ? 'Femme' : 'Homme'; ?></td></tr>
                        <tr><th>Date de naissance</th><td><?php echo esc_html(date('d/m/Y', strtotime($licence->date_naissance))); ?></td></tr>
                        <tr><th>Email</th><td><?php echo esc_html($licence->email); ?></td></tr>
                        <?php if ($licence->profession): ?>
                        <tr><th>Profession</th><td><?php echo esc_html($licence->profession); ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Adresse -->
                <div class="ufsc-detail-section">
                    <h2><span class="dashicons dashicons-location"></span> Adresse</h2>
                    <table class="form-table" role="presentation">
                        <tr><th>Adresse</th><td><?php echo esc_html($licence->adresse); ?></td></tr>
                        <?php if ($licence->suite_adresse): ?>
                        <tr><th>Complément</th><td><?php echo esc_html($licence->suite_adresse); ?></td></tr>
                        <?php endif; ?>
                        <tr><th>Code postal</th><td><?php echo esc_html($licence->code_postal); ?></td></tr>
                        <tr><th>Ville</th><td><?php echo esc_html($licence->ville); ?></td></tr>
                        <tr><th>Région</th><td><?php echo esc_html($licence->region); ?></td></tr>
                        <?php if ($licence->tel_fixe): ?>
                        <tr><th>Téléphone fixe</th><td><?php echo esc_html($licence->tel_fixe); ?></td></tr>
                        <?php endif; ?>
                        <?php if ($licence->tel_mobile): ?>
                        <tr><th>Téléphone mobile</th><td><?php echo esc_html($licence->tel_mobile); ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Informations club -->
                <div class="ufsc-detail-section">
                    <h2><span class="dashicons dashicons-groups"></span> Informations club</h2>
                    <table class="form-table" role="presentation">
                        <tr><th>Club</th><td><?php echo $club ? esc_html($club->nom) : 'Club introuvable'; ?></td></tr>
                        <tr><th>Type de licence</th><td>
                            <span class="ufsc-badge <?php echo $licence->competition ? 'badge-green' : 'badge-orange'; ?>">
                                <?php echo $licence->competition ? 'Compétition' : 'Loisir'; ?>
                            </span>
                        </td></tr>
                        <tr><th>Statut de paiement</th><td>
                            <span class="ufsc-badge <?php echo $licence->is_included ? 'badge-green' : 'badge-red'; ?>">
                                <?php echo $licence->is_included ? 'Inclus dans le quota' : 'Payant'; ?>
                            </span>
                        </td></tr>
                        <tr><th>Date d'inscription</th><td><?php echo esc_html(date('d/m/Y H:i', strtotime($licence->date_inscription))); ?></td></tr>
                    </table>
                </div>

                <!-- Options et réductions -->
                <div class="ufsc-detail-section">
                    <h2><span class="dashicons dashicons-money-alt"></span> Options et réductions</h2>
                    <table class="form-table" role="presentation">
                        <tr><th>Réduction bénévole</th><td><?php echo $licence->reduction_benevole ? 'Oui' : 'Non'; ?></td></tr>
                        <tr><th>Réduction postier</th><td><?php echo $licence->reduction_postier ? 'Oui' : 'Non'; ?></td></tr>
                        <?php if ($licence->identifiant_laposte): ?>
                        <tr><th>Identifiant La Poste</th><td><?php echo esc_html($licence->identifiant_laposte); ?></td></tr>
                        <?php endif; ?>
                        <tr><th>Fonction publique</th><td><?php echo $licence->fonction_publique ? 'Oui' : 'Non'; ?></td></tr>
                    </table>
                </div>

                <!-- Zone de danger -->
                <div class="ufsc-detail-section ufsc-danger-zone">
                    <h2><span class="dashicons dashicons-warning"></span> Zone de danger</h2>
                    <p>Cette action est irréversible. La licence sera définitivement supprimée.</p>
                    <form method="post" onsubmit="return confirmDeleteLicence('<?php echo esc_js($licence->prenom . ' ' . $licence->nom); ?>')">
                        <?php wp_nonce_field('delete_licence_' . $licence_id, 'delete_licence_nonce'); ?>
                        <input type="hidden" name="delete_licence" value="1">
                        <button type="submit" class="button button-link-delete">🗑️ Supprimer définitivement cette licence</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function confirmDeleteLicence(licenceName) {
            return confirm('Êtes-vous sûr de vouloir supprimer définitivement la licence de "' + licenceName + '" ?\n\nCette action est irréversible.');
        }
        </script>
        <?php
    }

    /**
     * Render voir licences page (club-specific license list)
     */
    public function render_voir_licences_page()
    {
        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-filters.php';

        // Resolve club ID from request or current user
        $club_id = isset($_GET['club_id']) ? absint(wp_unslash($_GET['club_id'])) : 0;
        if (!$club_id) {
            $club_id = $this->resolve_admin_club_id();
            if ($club_id) {
                // Ensure downstream components see the resolved club
                $_GET['club_id'] = $club_id;
            }
        }

        // Prepare filters with detected club ID so queries target the proper club
        $filters = UFSC_Licence_Filters::get_filter_parameters(['club_id' => $club_id]);

        require_once UFSC_PLUGIN_PATH . 'includes/licences/admin-licence-list.php';
    }

    /**
     * Resolve club ID for the current admin user
     *
     * @return int Club ID or 0 if none found
     */
    private function resolve_admin_club_id(): int
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return 0;
        }

        $club_id = (int) get_user_meta($user_id, 'ufsc_club_id', true);
        if (!$club_id) {
            $club_id = (int) get_user_meta($user_id, 'club_id', true);
        }

        if (!$club_id) {
            global $wpdb;
            $club_id = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}ufsc_clubs WHERE responsable_id = %d LIMIT 1",
                    $user_id
                )
            );
        }

        return $club_id;
    }

    // ================================
    // SECTION CALLBACKS
    // ================================

    /**
     * Page configuration section callback
     */
    public function page_config_section_callback()
    {
        echo '<p>' . esc_html__('Configurez les pages WordPress à utiliser pour les différentes fonctionnalités du plugin UFSC.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * WooCommerce section callback
     */
    public function woocommerce_section_callback()
    {
        echo '<p>' . esc_html__('Configurez les identifiants des produits WooCommerce pour les affiliations et licences.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * CSV Export section callback
     */
    public function csv_export_section_callback()
    {
        echo '<p>' . esc_html__('Configurez les options d\'exportation CSV pour personnaliser le format et le contenu des exports.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * Validation section callback
     */
    public function validation_section_callback()
    {
        echo '<p>' . esc_html__('Configurez le workflow de validation des licences et les notifications email.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * Frontend section callback
     */
    public function frontend_section_callback()
    {
        echo '<p>' . esc_html__('Choisissez les champs visibles côté club/adhérent sur le frontend.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * Security section callback
     */
    public function security_section_callback()
    {
        echo '<p>' . esc_html__('Options de sécurité et conformité RGPD pour masquer les champs sensibles.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * Misc section callback
     */
    public function misc_section_callback()
    {
        echo '<p>' . esc_html__('Paramètres divers pour personnaliser l\'apparence et le comportement du plugin.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    // ================================
    // FIELD CALLBACKS - PAGE CONFIGURATION
    // ================================

    /**
     * Get all published pages for dropdown
     */
    private function get_pages_for_dropdown()
    {
        $pages = get_pages(array(
            'post_status' => 'publish',
            'post_type' => 'page',
            'sort_column' => 'post_title',
            'sort_order' => 'ASC'
        ));
        
        return $pages;
    }

    /**
     * Club dashboard page field callback
     */
    public function club_dashboard_page_callback()
    {
        $page_id = get_option('ufsc_club_dashboard_page_id', 0);
        $pages = $this->get_pages_for_dropdown();
        ?>
        <select id="ufsc_club_dashboard_page_id" name="ufsc_club_dashboard_page_id">
            <option value="0"><?php esc_html_e('-- Sélectionner une page --', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($page_id, $page->ID); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Page qui servira de tableau de bord pour l\'espace club.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Affiliation page field callback
     */
    public function affiliation_page_callback()
    {
        $page_id = get_option('ufsc_affiliation_page_id', 0);
        $pages = $this->get_pages_for_dropdown();
        ?>
        <select id="ufsc_affiliation_page_id" name="ufsc_affiliation_page_id">
            <option value="0"><?php esc_html_e('-- Sélectionner une page --', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($page_id, $page->ID); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Page contenant le formulaire d\'affiliation des clubs.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Club form page field callback
     */
    public function club_form_page_callback()
    {
        $page_id = get_option('ufsc_club_form_page_id', 0);
        $pages = $this->get_pages_for_dropdown();
        ?>
        <select id="ufsc_club_form_page_id" name="ufsc_club_form_page_id">
            <option value="0"><?php esc_html_e('-- Sélectionner une page --', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($page_id, $page->ID); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Page contenant le formulaire de création ou d\'édition de club.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Licences page field callback
     */
    public function licences_page_callback()
    {
        $page_id = get_option('ufsc_licence_page_id', 0);
        $pages = $this->get_pages_for_dropdown();
        ?>
        <select id="ufsc_licence_page_id" name="ufsc_licence_page_id">
            <option value="0"><?php esc_html_e('-- Sélectionner une page --', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($page_id, $page->ID); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Page pour la gestion des licences.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Attestations page field callback
     */
    public function attestations_page_callback()
    {
        $page_id = get_option('ufsc_attestation_page_id', 0);
        $pages = $this->get_pages_for_dropdown();
        ?>
        <select id="ufsc_attestation_page_id" name="ufsc_attestation_page_id">
            <option value="0"><?php esc_html_e('-- Sélectionner une page --', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <?php foreach ($pages as $page): ?>
                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected($page_id, $page->ID); ?>>
                    <?php echo esc_html($page->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Page pour la gestion des attestations.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    // ================================
    // FIELD CALLBACKS - WOOCOMMERCE CONFIGURATION
    // ================================

    /**
     * Affiliation product ID field callback
     */
    public function affiliation_product_id_callback()
    {
        $product_id = ufsc_get_affiliation_product_id_safe();
        ?>
        <input type="number" id="ufsc_wc_affiliation_product_id" name="ufsc_wc_affiliation_product_id"
               value="<?php echo esc_attr($product_id); ?>" min="1" step="1" />
        <p class="description">
            <?php esc_html_e('ID du produit WooCommerce pour les affiliations de club (par défaut: 4823).', 'plugin-ufsc-gestion-club-13072025'); ?>
            <?php if ($product_id) { ?>
                <?php 
                if (function_exists('wc_get_product')) {
                    $product = wc_get_product($product_id);
                    if ($product) { ?>
                        <br><strong><?php esc_html_e('Produit actuel:', 'plugin-ufsc-gestion-club-13072025'); ?></strong> 
                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $product_id . '&action=edit')); ?>" target="_blank">
                            <?php echo esc_html($product->get_name()); ?>
                        </a>
                    <?php } else { ?>
                        <br><span style="color: #d63638;"><?php esc_html_e('⚠️ Produit introuvable avec cet ID', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                    <?php } 
                } else { ?>
                    <br><span style="color: #d63638;"><?php esc_html_e('⚠️ WooCommerce n\'est pas activé', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                <?php } ?>

            <?php } ?>

        </p>
        <?php
    }

    /**
     * Licence product ID field callback
     */
    public function licence_product_id_callback()
    {
        $product_id = get_option('ufsc_licence_product_id', 2934);
        ?>
        <input type="number" id="ufsc_licence_product_id" name="ufsc_licence_product_id" 
               value="<?php echo esc_attr($product_id); ?>" min="1" step="1" />
        <p class="description">
            <?php esc_html_e('ID du produit WooCommerce pour les licences (par défaut: 2934).', 'plugin-ufsc-gestion-club-13072025'); ?>
            <?php if ($product_id) { ?>
                <?php 

                if (function_exists('wc_get_product')) {
                    $product = wc_get_product($product_id);
                    if ($product) { ?>
                        <br><strong><?php esc_html_e('Produit actuel:', 'plugin-ufsc-gestion-club-13072025'); ?></strong> 
                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $product_id . '&action=edit')); ?>" target="_blank">
                            <?php echo esc_html($product->get_name()); ?>
                        </a>
                    <?php } else { ?>
                        <br><span style="color: #d63638;"><?php esc_html_e('⚠️ Produit introuvable avec cet ID', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                    <?php } 
                } else { ?>
                    <br><span style="color: #d63638;"><?php esc_html_e('⚠️ WooCommerce n\'est pas activé', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                <?php } ?>

            <?php } ?>

        </p>
        <?php
    }

    // ================================
    // FIELD CALLBACKS - CSV EXPORT
    // ================================

    /**
     * CSV Separator field callback
     */
    public function csv_separator_field_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $separator = isset($options['csv_separator']) ? $options['csv_separator'] : ';';
        ?>
        <select id="csv_separator" name="ufsc_general_settings[csv_separator]">
            <option value=";" <?php selected($separator, ';'); ?>><?php esc_html_e('Point-virgule (;)', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <option value="," <?php selected($separator, ','); ?>><?php esc_html_e('Virgule (,)', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <option value="\t" <?php selected($separator, "\t"); ?>><?php esc_html_e('Tabulation', 'plugin-ufsc-gestion-club-13072025'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Séparateur utilisé dans les fichiers CSV exportés.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * CSV Encoding field callback
     */
    public function csv_encoding_field_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $encoding = isset($options['csv_encoding']) ? $options['csv_encoding'] : 'UTF-8';
        ?>
        <select id="csv_encoding" name="ufsc_general_settings[csv_encoding]">
            <option value="UTF-8" <?php selected($encoding, 'UTF-8'); ?>><?php esc_html_e('UTF-8 (recommandé)', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <option value="ISO-8859-1" <?php selected($encoding, 'ISO-8859-1'); ?>><?php esc_html_e('ISO-8859-1 (Latin-1)', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <option value="Windows-1252" <?php selected($encoding, 'Windows-1252'); ?>><?php esc_html_e('Windows-1252', 'plugin-ufsc-gestion-club-13072025'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Encodage des caractères pour les fichiers CSV.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Club export fields callback
     */
    public function club_export_fields_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $selected_fields = isset($options['club_export_fields']) ? $options['club_export_fields'] : [];
        
        $available_fields = [
            'nom' => 'Nom du club',
            'sigle' => 'Sigle',
            'adresse' => 'Adresse',
            'ville' => 'Ville',
            'region' => 'Région',
            'telephone' => 'Téléphone',
            'email' => 'Email',
            'site_web' => 'Site web',
            'siren' => 'SIREN',
            'code_ape' => 'Code APE',
            'numero_rna' => 'Numéro RNA',
            'president_nom' => 'Président nom',
            'president_prenom' => 'Président prénom',
            'president_email' => 'Président email',
            'president_telephone' => 'Président téléphone',
            'secretaire_nom' => 'Secrétaire nom',
            'secretaire_prenom' => 'Secrétaire prénom',
            'secretaire_email' => 'Secrétaire email',
            'secretaire_telephone' => 'Secrétaire téléphone',
            'tresorier_nom' => 'Trésorier nom',
            'tresorier_prenom' => 'Trésorier prénom',
            'tresorier_email' => 'Trésorier email',
            'tresorier_telephone' => 'Trésorier téléphone',
            'date_creation' => 'Date de création'
        ];
        
        echo '<fieldset>';
        foreach ($available_fields as $field => $label) {
            $checked = in_array($field, $selected_fields) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="ufsc_general_settings[club_export_fields][]" value="' . esc_attr($field) . '" ' . $checked . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Sélectionnez les champs à inclure dans l\'export CSV des clubs.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * License export fields callback
     */
    public function license_export_fields_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $selected_fields = isset($options['license_export_fields']) ? $options['license_export_fields'] : [];
        
        $available_fields = [
            'nom' => 'Nom',
            'prenom' => 'Prénom',
            'sexe' => 'Sexe',
            'date_naissance' => 'Date de naissance',
            'email' => 'Email',
            'adresse' => 'Adresse',
            'ville' => 'Ville',
            'region' => 'Région',
            'tel_fixe' => 'Téléphone fixe',
            'tel_mobile' => 'Téléphone mobile',
            'profession' => 'Profession',
            'club' => 'Club',
            'type_licence' => 'Type de licence',
            'competition' => 'Compétition',
            'date_inscription' => 'Date d\'inscription',
            'statut' => 'Statut'
        ];
        
        echo '<fieldset>';
        foreach ($available_fields as $field => $label) {
            $checked = in_array($field, $selected_fields) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="ufsc_general_settings[license_export_fields][]" value="' . esc_attr($field) . '" ' . $checked . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Sélectionnez les champs à inclure dans l\'export CSV des licences.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * License status filter callback
     */
    public function license_status_filter_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $filter = isset($options['license_status_filter']) ? $options['license_status_filter'] : 'all';
        ?>
        <select id="license_status_filter" name="ufsc_general_settings[license_status_filter]">
            <option value="all" <?php selected($filter, 'all'); ?>><?php esc_html_e('Toutes les licences', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <option value="validee" <?php selected($filter, 'validee'); ?>><?php esc_html_e('Seulement les validées', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <option value="en_attente" <?php selected($filter, 'en_attente'); ?>><?php esc_html_e('Seulement en attente', 'plugin-ufsc-gestion-club-13072025'); ?></option>
            <option value="refusee" <?php selected($filter, 'refusee'); ?>><?php esc_html_e('Seulement les refusées', 'plugin-ufsc-gestion-club-13072025'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('Filtrer les licences par statut lors de l\'export.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Custom filename callback
     */
    public function custom_filename_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $filename = isset($options['custom_filename']) ? $options['custom_filename'] : '';
        ?>
        <input type="text" id="custom_filename" name="ufsc_general_settings[custom_filename]" value="<?php echo esc_attr($filename); ?>" class="regular-text" placeholder="export_ufsc">
        <p class="description"><?php esc_html_e('Nom de base pour les fichiers exportés (sans extension). Laisser vide pour utiliser le nom par défaut.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    // ================================
    // FIELD CALLBACKS - VALIDATION
    // ================================

    /**
     * Enable manual validation callback
     */
    public function enable_manual_validation_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $enabled = isset($options['enable_manual_validation']) ? $options['enable_manual_validation'] : true;
        ?>
        <input type="checkbox" id="enable_manual_validation" name="ufsc_general_settings[enable_manual_validation]" value="1" <?php checked(1, $enabled); ?>>
        <label for="enable_manual_validation"><?php esc_html_e('Activer la validation manuelle des licences', 'plugin-ufsc-gestion-club-13072025'); ?></label>
        <p class="description"><?php esc_html_e('Si désactivé, toutes les licences seront automatiquement validées.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Enable email notifications callback
     */
    public function enable_email_notifications_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $enabled = isset($options['enable_email_notifications']) ? $options['enable_email_notifications'] : false;
        ?>
        <input type="checkbox" id="enable_email_notifications" name="ufsc_general_settings[enable_email_notifications]" value="1" <?php checked(1, $enabled); ?>>
        <label for="enable_email_notifications"><?php esc_html_e('Envoyer des emails automatiques lors de la validation/refus', 'plugin-ufsc-gestion-club-13072025'); ?></label>
        <p class="description"><?php esc_html_e('Les adhérents recevront un email lors de la validation ou du refus de leur licence.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Email validation message callback
     */
    public function email_validation_message_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $message = isset($options['email_validation_message']) ? $options['email_validation_message'] : __('Votre licence a été validée avec succès !', 'plugin-ufsc-gestion-club-13072025');
        ?>
        <textarea id="email_validation_message" name="ufsc_general_settings[email_validation_message]" rows="4" cols="50" class="large-text"><?php echo esc_textarea($message); ?></textarea>
        <p class="description"><?php esc_html_e('Message personnalisé envoyé lors de la validation d\'une licence.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Email rejection message callback
     */
    public function email_rejection_message_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $message = isset($options['email_rejection_message']) ? $options['email_rejection_message'] : __('Votre demande de licence a été refusée. Veuillez contacter votre club pour plus d\'informations.', 'plugin-ufsc-gestion-club-13072025');
        ?>
        <textarea id="email_rejection_message" name="ufsc_general_settings[email_rejection_message]" rows="4" cols="50" class="large-text"><?php echo esc_textarea($message); ?></textarea>
        <p class="description"><?php esc_html_e('Message personnalisé envoyé lors du refus d\'une licence.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    // ================================
    // FIELD CALLBACKS - FRONTEND
    // ================================

    /**
     * Frontend club fields callback
     */
    public function frontend_club_fields_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $selected_fields = isset($options['frontend_club_fields']) ? $options['frontend_club_fields'] : [];
        
        $available_fields = [
            'nom' => 'Nom du club',
            'ville' => 'Ville',
            'region' => 'Région',
            'telephone' => 'Téléphone',
            'email' => 'Email',
            'site_web' => 'Site web',
            'president_nom' => 'Président',
            'secretaire_nom' => 'Secrétaire',
            'tresorier_nom' => 'Trésorier'
        ];
        
        echo '<fieldset>';
        foreach ($available_fields as $field => $label) {
            $checked = in_array($field, $selected_fields) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="ufsc_general_settings[frontend_club_fields][]" value="' . esc_attr($field) . '" ' . $checked . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Champs visibles sur le frontend pour les clubs.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * Frontend license fields callback
     */
    public function frontend_license_fields_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $selected_fields = isset($options['frontend_license_fields']) ? $options['frontend_license_fields'] : [];
        
        $available_fields = [
            'nom' => 'Nom',
            'prenom' => 'Prénom',
            'club' => 'Club',
            'type_licence' => 'Type de licence',
            'date_inscription' => 'Date d\'inscription',
            'statut' => 'Statut'
        ];
        
        echo '<fieldset>';
        foreach ($available_fields as $field => $label) {
            $checked = in_array($field, $selected_fields) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="ufsc_general_settings[frontend_license_fields][]" value="' . esc_attr($field) . '" ' . $checked . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Champs visibles sur le frontend pour les licences.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    // ================================
    // FIELD CALLBACKS - SECURITY
    // ================================

    /**
     * Hide sensitive export callback
     */
    public function hide_sensitive_export_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $hidden_fields = isset($options['hide_sensitive_export']) ? $options['hide_sensitive_export'] : [];
        
        $sensitive_fields = [
            'email' => 'Adresses email',
            'telephone' => 'Numéros de téléphone',
            'adresse' => 'Adresses postales',
            'date_naissance' => 'Dates de naissance',
            'siren' => 'Numéros SIREN',
            'profession' => 'Professions'
        ];
        
        echo '<fieldset>';
        foreach ($sensitive_fields as $field => $label) {
            $checked = in_array($field, $hidden_fields) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="ufsc_general_settings[hide_sensitive_export][]" value="' . esc_attr($field) . '" ' . $checked . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Champs sensibles à masquer dans les exports pour la conformité RGPD.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    /**
     * Hide sensitive frontend callback
     */
    public function hide_sensitive_frontend_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $hidden_fields = isset($options['hide_sensitive_frontend']) ? $options['hide_sensitive_frontend'] : [];
        
        $sensitive_fields = [
            'email' => 'Adresses email',
            'telephone' => 'Numéros de téléphone',
            'adresse' => 'Adresses postales',
            'date_naissance' => 'Dates de naissance'
        ];
        
        echo '<fieldset>';
        foreach ($sensitive_fields as $field => $label) {
            $checked = in_array($field, $hidden_fields) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="ufsc_general_settings[hide_sensitive_frontend][]" value="' . esc_attr($field) . '" ' . $checked . '> ' . esc_html($label) . '</label><br>';
        }
        echo '</fieldset>';
        echo '<p class="description">' . esc_html__('Champs sensibles à masquer sur le frontend public.', 'plugin-ufsc-gestion-club-13072025') . '</p>';
    }

    // ================================
    // FIELD CALLBACKS - MISCELLANEOUS
    // ================================

    /**
     * Export logo callback
     */
    public function export_logo_callback()
    {
        $options = get_option('ufsc_general_settings', array());
        $logo_url = isset($options['export_logo_url']) ? $options['export_logo_url'] : '';
        ?>
        <input type="url" id="export_logo_url" name="ufsc_general_settings[export_logo_url]" value="<?php echo esc_attr($logo_url); ?>" class="regular-text" placeholder="https://example.com/logo.png">
        <p class="description"><?php esc_html_e('URL du logo à inclure dans les exports (format d\'image supporté).', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * WooCommerce license product IDs callback
     */
    public function wc_license_product_ids_callback()
    {
        $value = get_option('ufsc_wc_license_product_ids', '2934');
        ?>
        <input type="text" id="ufsc_wc_license_product_ids" name="ufsc_wc_license_product_ids" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php esc_html_e('IDs des produits WooCommerce pour les licences, séparés par des virgules (ex: 2934,2935,2936).', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Auto-create user callback
     */
    public function auto_create_user_callback()
    {
        $value = get_option('ufsc_auto_create_user', false);
        ?>
        <input type="checkbox" id="ufsc_auto_create_user" name="ufsc_auto_create_user" value="1" <?php checked(1, $value); ?>>
        <label for="ufsc_auto_create_user"><?php esc_html_e('Créer automatiquement un utilisateur WordPress depuis une commande WooCommerce', 'plugin-ufsc-gestion-club-13072025'); ?></label>
        <p class="description"><?php esc_html_e('Si activé, un compte utilisateur sera créé automatiquement lors du traitement d\'une commande.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Require login for shortcodes callback
     */
    public function require_login_shortcodes_callback()
    {
        $value = get_option('ufsc_require_login_shortcodes', true);
        ?>
        <input type="checkbox" id="ufsc_require_login_shortcodes" name="ufsc_require_login_shortcodes" value="1" <?php checked(1, $value); ?>>
        <label for="ufsc_require_login_shortcodes"><?php esc_html_e('Exiger la connexion pour accéder aux shortcodes frontend', 'plugin-ufsc-gestion-club-13072025'); ?></label>
        <p class="description"><?php esc_html_e('Si activé, les shortcodes [ufsc_club_register], [ufsc_club_account], [ufsc_club_licenses], [ufsc_club_dashboard] nécessiteront une connexion.', 'plugin-ufsc-gestion-club-13072025'); ?></p>
        <?php
    }

    /**
     * Sanitize checkbox values
     */
    public function sanitize_checkbox($value)
    {
        return !empty($value) ? 1 : 0;
    }
}
