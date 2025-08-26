<?php
/**
 * AJAX handler for adding licensee to WooCommerce cart
 * 
 * @package UFSC_Gestion_Club
 * @subpackage Frontend\Ajax
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for adding licensee to cart
 */
function ufsc_handle_add_licencie_to_cart() {
    try {
        // Verify nonce
        if (!ufsc_check_ajax_nonce('ufsc_add_licencie_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Erreur de sécurité. Veuillez recharger la page.']);
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vous devez être connecté pour ajouter un licencié.']);
            return;
        }

        // Get user's club
        $access_check = ufsc_check_frontend_access('licence');
        if (!$access_check['allowed']) {
            wp_send_json_error(['message' => 'Vous n\'êtes pas autorisé à effectuer cette action.']);
            return;
        }

        $club = $access_check['club'];

        // Check club status
        if (!ufsc_is_club_active($club)) {
            wp_send_json_error(['message' => 'Votre club doit être validé pour ajouter des licenciés.']);
            return;
        }

        // Get licence product ID - use defensive programming
        $licence_product_id = 0;
        if (function_exists('ufsc_get_licence_product_id_safe')) {
            $licence_product_id = (int) ufsc_get_licence_product_id_safe();
        } elseif (function_exists('ufsc_get_licence_product_id')) {
            $licence_product_id = (int) ufsc_get_licence_product_id();
        }
        
        if (!$licence_product_id) {
            wp_send_json_error(['message' => 'Produit licence non configuré. Contactez l\'administrateur.']);
            return;
        }

        // Check quota if not unlimited using new helper functions
        $quota_total = intval($club->quota_licences);
        $quota_usage = ufsc_get_quota_usage($club->id);
        
        if ($quota_total > 0 && $quota_usage >= $quota_total) {
            wp_send_json_error(['message' => 'Quota de licences épuisé pour votre club.']);
            return;
        }

        // Sanitize and validate form data - Updated field names to match new form
        $nom = sanitize_text_field($_POST['nom'] ?? '');
        $prenom = sanitize_text_field($_POST['prenom'] ?? '');
        $date_naissance = sanitize_text_field($_POST['date_naissance'] ?? '');
        $sexe = sanitize_text_field($_POST['sexe'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $telephone = sanitize_text_field($_POST['telephone'] ?? '');
        $adresse = sanitize_text_field($_POST['adresse'] ?? '');
        $code_postal = sanitize_text_field($_POST['code_postal'] ?? '');
        $ville = sanitize_text_field($_POST['ville'] ?? '');
        $region = sanitize_text_field($_POST['region'] ?? '');
        $profession = sanitize_text_field($_POST['profession'] ?? '');
        $niveau_pratique = sanitize_text_field($_POST['niveau_pratique'] ?? '');

        // Validate required fields
        if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($sexe) || empty($email) || empty($adresse) || empty($code_postal) || empty($ville)) {
            wp_send_json_error(['message' => 'Veuillez remplir tous les champs obligatoires.']);
            return;
        }

        // Check for duplicates before adding to cart
        if (class_exists('UFSC_Licence_Manager')) {
            require_once plugin_dir_path(dirname(__FILE__)) . '../licences/class-licence-manager.php';
            $licence_manager = new UFSC_Licence_Manager();
            
            $duplicate_check_data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'date_naissance' => $date_naissance,
                'club_id' => $club->id
            ];
            
            $duplicate_id = $licence_manager->check_duplicate_licence($duplicate_check_data);
            if ($duplicate_id) {
                wp_send_json_error([
                    'message' => 'Licencié déjà enregistré',
                    'details' => 'Une licence existe déjà pour ' . $prenom . ' ' . $nom . ' (né(e) le ' . $date_naissance . ') dans ce club.'
                ], 409);
                return;
            }
        }

        // Validate email format
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Format d\'email invalide.']);
            return;
        }

        // Validate date format
        $date_check = DateTime::createFromFormat('Y-m-d', $date_naissance);
        if (!$date_check || $date_check->format('Y-m-d') !== $date_naissance) {
            wp_send_json_error(['message' => 'Format de date invalide.']);
            return;
        }

        // Prepare cart item data with licensee information - Updated with all new fields
        $role = isset($_POST['role']) ? ufsc_sanitize_role($_POST['role']) : 'adherent';
        $cart_item_data = [
            'ufsc_licence_data' => [
                'role' => $role,
                'nom' => $nom,
                'prenom' => $prenom,
                'date_naissance' => $date_naissance,
                'sexe' => $sexe,
                'email' => $email,
                'telephone' => $telephone,
                'adresse' => $adresse,
                'code_postal' => $code_postal,
                'ville' => $ville,
                'region' => $region,
                'profession' => $profession,
                'niveau_pratique' => $niveau_pratique,
                'club_id' => $club->id
            ],
            'ufsc_product_type' => 'licence',
            'ufsc_club_id' => $club->id,
            'ufsc_club_nom' => $club->nom,
        ];

        // Generate unique key and prevent duplicates
        $unique_key = ufsc_generate_licence_key($cart_item_data['ufsc_licence_data']);

        if (ufsc_cart_contains_licence($unique_key)) {
            wp_send_json_error(['message' => 'Ce licencié est déjà présent dans votre panier.']);
            return;
        }

        $cart_item_data['unique_key'] = $unique_key;

        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart($licence_product_id, 1, 0, [], $cart_item_data);

        if ($cart_item_key) {
            $cart_count = WC()->cart->get_cart_contents_count();
            
            wp_send_json_success([
                'message' => 'Licencié ajouté au panier avec succès.',
                'cart_count' => $cart_count,
                'cart_url' => wc_get_cart_url(),
                'checkout_url' => wc_get_checkout_url()
            ]);
        } else {
            wp_send_json_error(['message' => 'Erreur lors de l\'ajout au panier.']);
        }
        
    } catch (Exception $e) {
        // Log the error for debugging
        if (function_exists('error_log')) {
            error_log('UFSC Licence Add Error: ' . $e->getMessage());
        }
        
        wp_send_json_error(['message' => 'Une erreur inattendue s\'est produite. Veuillez réessayer.']);
    }
}

// Register AJAX handlers
add_action('wp_ajax_ufsc_add_licencie_to_cart', 'ufsc_handle_add_licencie_to_cart');
add_action('wp_ajax_nopriv_ufsc_add_licencie_to_cart', 'ufsc_handle_add_licencie_to_cart');