<?php

/**
 * Intégration du formulaire de licence complet directement dans WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle license data from URL parameters when adding to cart
 */
function ufsc_handle_licence_url_data() {
    if (isset($_GET['ufsc_licence_data']) && !empty($_GET['ufsc_licence_data'])) {
        $encoded_data = sanitize_text_field($_GET['ufsc_licence_data']);
        $licence_data = json_decode(base64_decode($encoded_data), true);
        
        if ($licence_data && is_array($licence_data)) {
            // Store in session or pass to cart
            WC()->session->set('ufsc_pending_licence_data', $licence_data);
        }
    }
}
add_action('init', 'ufsc_handle_licence_url_data');

/**
 * Add license data to cart item when adding via URL
 */
function ufsc_add_licence_data_from_session($cart_item_data, $product_id, $variation_id) {
    if ($product_id == ufsc_get_licence_product_id()) {
        $licence_data = WC()->session->get('ufsc_pending_licence_data');
        
        if ($licence_data && is_array($licence_data)) {
            $cart_item_data['ufsc_licence_data'] = $licence_data;
            $cart_item_data['ufsc_product_type'] = 'licence';
            $cart_item_data['ufsc_club_id'] = $licence_data['club_id'] ?? 0;
            
            // Clear session data
            WC()->session->__unset('ufsc_pending_licence_data');
            
            // Generate deterministic unique key based on licence data
            $cart_item_data['unique_key'] = ufsc_generate_licence_key($licence_data);
        }
    }
    
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'ufsc_add_licence_data_from_session', 5, 3);

// Include the unified form renderer
require_once plugin_dir_path(__FILE__) . 'forms/licence-form-render.php';

/**
 * Ajouter le formulaire de licence complet sur la page produit WooCommerce
 */
function ufsc_add_licence_fields_to_product_page()
{
    global $product;

    // Vérifier si c'est le produit de licence
    if (!$product || $product->get_id() != ufsc_get_licence_product_id()) {
        return;
    }

    // Vérifier si l'utilisateur est connecté
    if (!is_user_logged_in()) {
        echo '<div class="ufsc-alert ufsc-alert-error">
              <p>Vous devez être <a href="' . esc_url(wp_login_url(get_permalink())) . '">connecté</a> pour commander une licence.</p>
              </div>';
        return;
    }

    // Use the unified form renderer
    echo ufsc_render_licence_form([
        'context' => 'woocommerce',
        'show_title' => true,
        'submit_button_text' => 'Ajouter au panier'
    ]);
}
add_action('woocommerce_before_add_to_cart_button', 'ufsc_add_licence_fields_to_product_page');

/**
 * Valider les champs du formulaire de licence lors de l'ajout au panier
 */
function ufsc_validate_licence_fields($passed, $product_id, $quantity)
{
    if ($product_id != ufsc_get_licence_product_id()) {
        return $passed;
    }

    // Security check - verify nonce
    if (!isset($_POST['ufsc_wc_licence_nonce']) || !wp_verify_nonce($_POST['ufsc_wc_licence_nonce'], 'ufsc_woocommerce_licence')) {
        wc_add_notice('Erreur de sécurité. Veuillez réessayer.', 'error');
        return false;
    }

    // Champs obligatoires
    $required_fields = [
        'ufsc_licence_nom' => 'Le nom du licencié',
        'ufsc_licence_prenom' => 'Le prénom du licencié',
        'ufsc_licence_sexe' => 'Le sexe du licencié',
        'ufsc_licence_date_naissance' => 'La date de naissance',
        'ufsc_licence_email' => 'L\'email du licencié'
    ];

    foreach ($required_fields as $field => $label) {
        if (!isset($_POST[$field]) || empty(wp_unslash($_POST[$field]))) {
            wc_add_notice($label . ' est obligatoire.', 'error');
            $passed = false;
        }
    }

    // Validation de l'email
    if (isset($_POST['ufsc_licence_email']) && !empty(wp_unslash($_POST['ufsc_licence_email']))) {
        if (!is_email(wp_unslash($_POST['ufsc_licence_email']))) {
            wc_add_notice('L\'adresse email n\'est pas valide.', 'error');
            $passed = false;
        }
    }

    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'ufsc_validate_licence_fields', 10, 3);

/**
 * Ajouter les données de la licence comme métadonnées du produit dans le panier
 */
function ufsc_add_licence_data_to_cart_item($cart_item_data, $product_id, $variation_id)
{
    if ($product_id != ufsc_get_licence_product_id()) {
        return $cart_item_data;
    }

    if (isset($_POST['ufsc_club_id'])) {
        $cart_item_data['ufsc_club_id'] = intval(wp_unslash($_POST['ufsc_club_id']));
    }

    // Données de base
    $licence_data = [
        'nom' => isset($_POST['ufsc_licence_nom']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_nom'])) : '',
        'prenom' => isset($_POST['ufsc_licence_prenom']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_prenom'])) : '',
        'sexe' => isset($_POST['ufsc_licence_sexe']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_sexe'])) : '',
        'date_naissance' => isset($_POST['ufsc_licence_date_naissance']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_date_naissance'])) : '',
        'email' => isset($_POST['ufsc_licence_email']) ? sanitize_email(wp_unslash($_POST['ufsc_licence_email'])) : '',

        // Adresse
        'adresse' => isset($_POST['ufsc_licence_adresse']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_adresse'])) : '',
        'suite_adresse' => isset($_POST['ufsc_licence_suite_adresse']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_suite_adresse'])) : '',
        'code_postal' => isset($_POST['ufsc_licence_code_postal']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_code_postal'])) : '',
        'ville' => isset($_POST['ufsc_licence_ville']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_ville'])) : '',

        // Téléphones
        'tel_fixe' => isset($_POST['ufsc_licence_tel_fixe']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_tel_fixe'])) : '',
        'tel_mobile' => isset($_POST['ufsc_licence_tel_mobile']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_tel_mobile'])) : '',

        // Autres informations
        'profession' => isset($_POST['ufsc_licence_profession']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_profession'])) : '',
        'identifiant_laposte' => isset($_POST['ufsc_licence_identifiant_laposte']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_identifiant_laposte'])) : '',
        'region' => isset($_POST['ufsc_licence_region']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_region'])) : '',
        'numero_licence_delegataire' => isset($_POST['ufsc_licence_numero_licence_delegataire']) ? sanitize_text_field(wp_unslash($_POST['ufsc_licence_numero_licence_delegataire'])) : '',
        'note' => isset($_POST['ufsc_licence_note']) ? sanitize_textarea_field(wp_unslash($_POST['ufsc_licence_note'])) : '',
    ];

    // Options à cocher
    $checkboxes = [
        'reduction_benevole',
        'reduction_postier',
        'fonction_publique',
        'competition',
        'licence_delegataire',
        'diffusion_image',
        'infos_fsasptt',
        'infos_asptt',
        'infos_cr',
        'infos_partenaires',
        'honorabilite',
        'assurance_dommage_corporel',
        'assurance_assistance'
    ];

    foreach ($checkboxes as $key) {
        $licence_data[$key] = isset($_POST['ufsc_licence_' . $key]) ? 1 : 0;
    }

    $cart_item_data['ufsc_licence_data'] = $licence_data;
    $cart_item_data['ufsc_product_type'] = 'licence';

    // Generate a deterministic unique key for the licence
    $unique_key = ufsc_generate_licence_key(array_merge($licence_data, [
        'club_id' => $cart_item_data['ufsc_club_id'] ?? 0,
    ]));

    // Prevent duplicate licences in the cart
    if (ufsc_cart_contains_licence($unique_key)) {
        wc_add_notice('Ce licencié est déjà présent dans votre panier.', 'error');
        return false;
    }

    $cart_item_data['unique_key'] = $unique_key;

    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'ufsc_add_licence_data_to_cart_item', 10, 3);

/**
 * Afficher les informations de la licence dans le panier et lors du checkout
 */
function ufsc_display_licence_data_in_cart($item_data, $cart_item)
{
    if (isset($cart_item['ufsc_licence_data'])) {
        $licence_data = $cart_item['ufsc_licence_data'];

        $item_data[] = [
            'key'   => 'Licencié',
            'value' => $licence_data['prenom'] . ' ' . $licence_data['nom']
        ];

        $item_data[] = [
            'key'   => 'Date de naissance',
            'value' => gmdate('d/m/Y', strtotime($licence_data['date_naissance']))
        ];

        if (!empty($licence_data['ville'])) {
            $item_data[] = [
                'key'   => 'Ville',
                'value' => $licence_data['ville']
            ];
        }
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'ufsc_display_licence_data_in_cart', 10, 2);

/**
 * Sauvegarder les données de licence dans les métadonnées de la commande
 */
function ufsc_add_licence_data_to_order_items($item, $cart_item_key, $values, $order)
{
    if (isset($values['ufsc_licence_data'])) {
        $item->update_meta_data('ufsc_licence_data', $values['ufsc_licence_data']);
    }

    if (isset($values['ufsc_club_id'])) {
        $item->update_meta_data('ufsc_club_id', $values['ufsc_club_id']);
    }

    if (isset($values['ufsc_product_type'])) {
        $item->update_meta_data('ufsc_product_type', $values['ufsc_product_type']);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'ufsc_add_licence_data_to_order_items', 10, 4);

/**
 * Appliquer une remise sur le produit licence si l'utilisateur a encore du quota
 */
function ufsc_apply_quota_discount($cart)
{
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    if (!is_user_logged_in()) {
        return;
    }

    // Vérifier si le produit licence est dans le panier
    $found = false;
    $club_id = 0;
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['product_id']) && $cart_item['product_id'] == ufsc_get_licence_product_id()) {
            $found = true;
            if (isset($cart_item['ufsc_club_id'])) {
                $club_id = $cart_item['ufsc_club_id'];
            }
        }
    }

    if (!$found || !$club_id) {
        return;
    }

    // Vérifier si le club a encore du quota
    $club_manager = \UFSC\Clubs\ClubManager::get_instance();
    $club = $club_manager->get_club($club_id);

    if (!$club) {
        return;
    }

    $quota_total = intval($club->quota_licences) > 0 ? intval($club->quota_licences) : 0;
    $licences_count = ufsc_get_quota_usage($club_id);
    $quota_remaining = ufsc_get_quota_remaining($club_id, $quota_total);

    if ($quota_remaining > 0) {
        // Appliquer 100% de remise sur le produit licence
        $product = wc_get_product(ufsc_get_licence_product_id());
        if ($product) {
            $normal_price = $product->get_price();

            // Ajouter une notice explicative
            wc_add_notice('Licence incluse dans votre quota d\'affiliation - 100% de remise appliquée!', 'success');

            // Appliquer la remise
            $cart->add_fee('Licence incluse dans le quota', -$normal_price, true, 'standard');
        }
    }
}
add_action('woocommerce_cart_calculate_fees', 'ufsc_apply_quota_discount');

/**
 * Traiter la commande de licence terminée
 */
function ufsc_process_licence_order($order_id)
{
    $order = wc_get_order($order_id);

    // Vérifier si la commande est terminée
    if (!$order || $order->get_status() !== 'completed') {
        return;
    }

    // Parcourir les articles de la commande
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();

        // Vérifier si c'est une licence UFSC
        if ($product_id == ufsc_get_licence_product_id()) {
            $club_id = $item->get_meta('ufsc_club_id');
            $licence_data = $item->get_meta('ufsc_licence_data');

            if ($club_id && $licence_data) {
                // Créer la licence dans la base de données
                $club_manager = \UFSC\Clubs\ClubManager::get_instance();

                // Compléter les données de licence
                $licence_data['club_id'] = $club_id;
                
                // Set status as pending for manual validation (requirement from problem statement)
                $licence_data['statut'] = 'pending';
                $status_text = 'en attente de validation';
                
                $licence_data['date_creation'] = current_time('mysql');
                $licence_data['date_expiration'] = gmdate('Y-m-d', strtotime('+1 year'));
                $licence_data['order_id'] = $order_id;

                // Check for duplicates and handle appropriately
                require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
                $licence_manager = new UFSC_Licence_Manager();
                
                $duplicate_id = $licence_manager->check_duplicate_licence($licence_data);
                if ($duplicate_id) {
                    // Attach existing licence ID to line item instead of creating new one
                    $item->add_meta_data('ufsc_licence_id', $duplicate_id, true);
                    $item->save_meta_data();
                    error_log("UFSC: Duplicate licence detected for order {$order_id}, attached existing licence ID {$duplicate_id}");
                    continue; // Skip creation, use existing licence
                }

                // CORRECTION: License creation now works via Club Manager delegation to License Manager
                // This fixes the critical crash when purchasing licenses
                $licence_id = $club_manager->add_licence($licence_data);

                if ($licence_id) {
                    // Store mapping for potential future cancellation/refund
                    $item->add_meta_data('ufsc_licence_id', $licence_id, true);
                    $item->save_meta_data();

                    // Send notifications for pending status
                    ufsc_send_licence_notifications($licence_data, $club, $order_id, $status_text);
                    
                    error_log("UFSC: License {$licence_id} created for order {$order_id} with status {$licence_data['statut']}");
                }
            }
        }
    }
}
add_action('woocommerce_order_status_completed', 'ufsc_process_licence_order');

/**
 * ========================================
 * ENHANCED WOOCOMMERCE INTEGRATION
 * ========================================
 * Support for cart metadata, order lifecycle and license status management
 */

/**
 * Add cart item data to order line items
 * This preserves UFSC licensee information from cart to order
 */
function ufsc_add_cart_item_data_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['ufsc_licencie_prenom'])) {
        $item->add_meta_data('ufsc_licencie_prenom', $values['ufsc_licencie_prenom'], true);
    }
    if (isset($values['ufsc_licencie_nom'])) {
        $item->add_meta_data('ufsc_licencie_nom', $values['ufsc_licencie_nom'], true);
    }
    if (isset($values['ufsc_licencie_date_naissance'])) {
        $item->add_meta_data('ufsc_licencie_date_naissance', $values['ufsc_licencie_date_naissance'], true);
    }
    if (isset($values['ufsc_licencie_lieu_naissance'])) {
        $item->add_meta_data('ufsc_licencie_lieu_naissance', $values['ufsc_licencie_lieu_naissance'], true);
    }
    if (isset($values['ufsc_licencie_email'])) {
        $item->add_meta_data('ufsc_licencie_email', $values['ufsc_licencie_email'], true);
    }
    if (isset($values['ufsc_licencie_telephone'])) {
        $item->add_meta_data('ufsc_licencie_telephone', $values['ufsc_licencie_telephone'], true);
    }
    if (isset($values['ufsc_licencie_adresse'])) {
        $item->add_meta_data('ufsc_licencie_adresse', $values['ufsc_licencie_adresse'], true);
    }
    if (isset($values['ufsc_licencie_ville'])) {
        $item->add_meta_data('ufsc_licencie_ville', $values['ufsc_licencie_ville'], true);
    }
    if (isset($values['ufsc_licencie_code_postal'])) {
        $item->add_meta_data('ufsc_licencie_code_postal', $values['ufsc_licencie_code_postal'], true);
    }
    if (isset($values['ufsc_club_id'])) {
        $item->add_meta_data('ufsc_club_id', $values['ufsc_club_id'], true);
    }
    if (isset($values['ufsc_club_nom'])) {
        $item->add_meta_data('ufsc_club_nom', $values['ufsc_club_nom'], true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'ufsc_add_cart_item_data_to_order', 10, 4);

/**
 * Display cart item data in cart and checkout
 */
function ufsc_display_cart_item_data($item_data, $cart_item) {
    if (isset($cart_item['ufsc_licencie_prenom']) && isset($cart_item['ufsc_licencie_nom'])) {
        $item_data[] = [
            'key'     => 'Licencié',
            'value'   => $cart_item['ufsc_licencie_prenom'] . ' ' . $cart_item['ufsc_licencie_nom'],
            'display' => ''
        ];
    }
    
    if (isset($cart_item['ufsc_licencie_date_naissance'])) {
        $date = DateTime::createFromFormat('Y-m-d', $cart_item['ufsc_licencie_date_naissance']);
        $item_data[] = [
            'key'     => 'Date de naissance',
            'value'   => $date ? $date->format('d/m/Y') : $cart_item['ufsc_licencie_date_naissance'],
            'display' => ''
        ];
    }
    
    if (isset($cart_item['ufsc_club_nom'])) {
        $item_data[] = [
            'key'     => 'Club',
            'value'   => $cart_item['ufsc_club_nom'],
            'display' => ''
        ];
    }
    
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'ufsc_display_cart_item_data', 10, 2);

/**
 * Process license creation when order is completed
 * UPDATED: Better handling of quota and status logic
 */
function ufsc_process_licence_order_enhanced($order_id) {
    $order = wc_get_order($order_id);

    if (!$order || $order->get_status() !== 'completed') {
        return;
    }

    $club_manager = \UFSC\Clubs\ClubManager::get_instance();

    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();

        // Check if this is a license product
        if ($product_id == ufsc_get_licence_product_id()) {
            // Get licensee data from order meta
            $licence_data = [
                'prenom' => $item->get_meta('ufsc_licencie_prenom'),
                'nom' => $item->get_meta('ufsc_licencie_nom'),
                'date_naissance' => $item->get_meta('ufsc_licencie_date_naissance'),
                'lieu_naissance' => $item->get_meta('ufsc_licencie_lieu_naissance'),
                'email' => $item->get_meta('ufsc_licencie_email'),
                'telephone' => $item->get_meta('ufsc_licencie_telephone'),
                'adresse' => $item->get_meta('ufsc_licencie_adresse'),
                'ville' => $item->get_meta('ufsc_licencie_ville'),
                'code_postal' => $item->get_meta('ufsc_licencie_code_postal'),
                'club_id' => $item->get_meta('ufsc_club_id')
            ];

            // Validate required data
            if (empty($licence_data['prenom']) || empty($licence_data['nom']) || empty($licence_data['club_id'])) {
                error_log("UFSC: Incomplete license data for order {$order_id}, item {$item->get_id()}");
                continue;
            }

            // Get club and check quota
            $club = $club_manager->get_club($licence_data['club_id']);
            if (!$club) {
                error_log("UFSC: Club not found for order {$order_id}, club_id {$licence_data['club_id']}");
                continue;
            }

            $quota_total = intval($club->quota_licences);
            $licences_count = ufsc_get_quota_usage($licence_data['club_id']);
            $is_unlimited = ($quota_total === 0);

            // Set status as pending for manual validation (requirement from problem statement)
            $licence_data['statut'] = 'pending';
            $status_text = 'en attente de validation';

            $licence_data['date_creation'] = current_time('mysql');
            $licence_data['date_expiration'] = gmdate('Y-m-d', strtotime('+1 year'));
            $licence_data['order_id'] = $order_id;

            // Check for duplicates and handle appropriately
            require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
            $licence_manager = new UFSC_Licence_Manager();
            
            $duplicate_id = $licence_manager->check_duplicate_licence($licence_data);
            if ($duplicate_id) {
                // Attach existing licence ID to line item instead of creating new one
                $item->add_meta_data('ufsc_licence_id', $duplicate_id, true);
                $item->save_meta_data();
                error_log("UFSC: Duplicate licence detected for order {$order_id}, attached existing licence ID {$duplicate_id}");
                continue; // Skip creation, use existing licence
            }

            $licence_id = $club_manager->add_licence($licence_data);

            if ($licence_id) {
                // Store mapping for potential future cancellation/refund
                $item->add_meta_data('ufsc_licence_id', $licence_id, true);
                $item->save_meta_data();

                // Send notifications
                ufsc_send_licence_notifications($licence_data, $club, $order_id, $status_text);
                
                error_log("UFSC: License {$licence_id} created for order {$order_id} with status {$licence_data['statut']}");
            } else {
                error_log("UFSC: Failed to create license for order {$order_id}");
            }
        }
    }
}
add_action('woocommerce_order_status_completed', 'ufsc_process_licence_order_enhanced');

/**
 * Handle order cancellation - revoke associated licenses
 */
function ufsc_handle_order_cancellation($order_id) {
    $order = wc_get_order($order_id);
    
    if (!$order) {
        return;
    }

    $club_manager = \UFSC\Clubs\ClubManager::get_instance();

    foreach ($order->get_items() as $item) {
        $licence_id = $item->get_meta('ufsc_licence_id');
        
        if ($licence_id) {
            // Update license status to revoked
            $club_manager->update_licence_status($licence_id, 'revoked');
            error_log("UFSC: License {$licence_id} revoked due to order {$order_id} cancellation");
        }
    }
}
add_action('woocommerce_order_status_cancelled', 'ufsc_handle_order_cancellation');
add_action('woocommerce_order_status_refunded', 'ufsc_handle_order_cancellation');

/**
 * Send license creation notifications
 */
function ufsc_send_licence_notifications($licence_data, $club, $order_id, $status_text) {
    // Admin notification
    $admin_email = get_option('admin_email');
    $club_name = $club->nom ?? 'ID: ' . $licence_data['club_id'];
    
    $subject = $licence_data['statut'] === 'validee' ? 
        'Nouvelle licence UFSC validée' : 
        'Nouvelle demande de licence UFSC';
    
    $message = "Une nouvelle licence a été achetée et {$status_text}.\n\n";
    $message .= "Club: {$club_name}\n";
    $message .= "Licencié: {$licence_data['prenom']} {$licence_data['nom']}\n";
    $message .= "Né(e) le: " . gmdate('d/m/Y', strtotime($licence_data['date_naissance'])) . "\n";
    $message .= "Email: {$licence_data['email']}\n";
    $message .= "Commande: #{$order_id}\n";
    $message .= "Statut: " . ucfirst($licence_data['statut']) . "\n\n";
    
    if ($licence_data['statut'] === 'validee') {
        $message .= "Cette licence a été automatiquement validée.";
    } else {
        $message .= "Veuillez valider cette licence dans l'administration.";
    }

    wp_mail($admin_email, $subject, $message);

    // Club notification if email available
    if (!empty($club->email)) {
        $club_subject = $licence_data['statut'] === 'validee' ?
            'Licence UFSC validée' :
            'Demande de licence UFSC enregistrée';
            
        $club_message = "Bonjour,\n\n";
        $club_message .= "La demande de licence pour {$licence_data['prenom']} {$licence_data['nom']} a été {$status_text}.\n\n";
        
        if ($licence_data['statut'] === 'validee') {
            $club_message .= "La licence est maintenant active et disponible dans votre espace club.";
        } else {
            $club_message .= "La licence sera activée après validation par l'administration UFSC.";
        }
        
        $club_message .= "\n\nCordialement,\nUFSC";
        
        wp_mail($club->email, $club_subject, $club_message);
    }
}
