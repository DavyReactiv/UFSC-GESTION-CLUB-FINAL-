<?php

/**
 * Intégration WooCommerce pour les affiliations de club
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constantes pour les produits WooCommerce
 */
define('UFSC_AFFILIATION_PRODUCT_ID', 4823); // ID du produit affiliation
if (!defined('UFSC_LICENCE_PRODUCT_ID')) {
    define('UFSC_LICENCE_PRODUCT_ID', 2934); // ID du produit licence
}

/**
 * Ajouter le produit d'affiliation au panier WooCommerce
 *
 * @param int $club_id ID du club
 * @return bool Succès ou échec
 */
if (!function_exists('ufsc_add_affiliation_to_cart')) {
    function ufsc_add_affiliation_to_cart($club_id)
    {
        if (!function_exists('WC')) {
            return false;
        }

        // Vider le panier actuel
        WC()->cart->empty_cart();

        // Récupérer les informations du club
        $club_manager = UFSC_Club_Manager::get_instance();
        $club = $club_manager->get_club($club_id);

        if (!$club) {
            return false;
        }

        // Préparer les métadonnées pour le panier
        $cart_item_data = [
            'ufsc_club_id' => $club_id,
            'ufsc_club_nom' => $club->nom,
            'ufsc_product_type' => 'affiliation'
        ];

        // Ajouter au panier (using configurable product ID)
        $added = WC()->cart->add_to_cart(ufsc_get_affiliation_product_id(), 1, 0, [], $cart_item_data);

        return $added;
    }
}

/**
 * Personnaliser le titre du produit dans le panier
 */
function ufsc_customize_affiliation_cart_item_name($title, $cart_item, $cart_item_key)
{
    if (isset($cart_item['ufsc_product_type']) && $cart_item['ufsc_product_type'] === 'affiliation' && isset($cart_item['ufsc_club_nom'])) {
        $title = sprintf(
            'Pack Affiliation Club - %s',
            $cart_item['ufsc_club_nom']
        );
    }

    return $title;
}
add_filter('woocommerce_cart_item_name', 'ufsc_customize_affiliation_cart_item_name', 10, 3);

/**
 * Afficher les informations de l'affiliation dans le panier et lors du checkout
 */
function ufsc_display_affiliation_data_in_cart($item_data, $cart_item)
{
    if (isset($cart_item['ufsc_product_type']) && $cart_item['ufsc_product_type'] === 'affiliation') {
        $item_data[] = [
            'key'   => 'Contenu du pack',
            'value' => 'Affiliation du club pour 1 an + 10 licences incluses'
        ];
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'ufsc_display_affiliation_data_in_cart', 10, 2);

/**
 * Traiter la commande d'affiliation terminée
 */
if (!function_exists('ufsc_process_affiliation_order')) {
    function ufsc_process_affiliation_order($order_id)
    {
        $order = wc_get_order($order_id);

        // Vérifier si la commande est terminée
        if (!$order || !in_array($order->get_status(), ['completed', 'processing'])) {
            return;
        }

        // Parcourir les articles de la commande
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            // Vérifier si c'est une affiliation UFSC
            if ($product_id == ufsc_get_affiliation_product_id()) {
                $club_id = $item->get_meta('ufsc_club_id');

                if ($club_id) {
                    // Mettre à jour le club comme "En attente de validation"
                    $club_manager = UFSC_Club_Manager::get_instance();
                    $club = $club_manager->get_club($club_id);

                    if ($club) {
                        // Mise à jour du statut et date d'affiliation
                        $club_data = [
                            'statut' => 'En attente de validation',
                            'date_affiliation' => current_time('mysql'),
                            'quota_licences' => 10 // Pack initial de 10 licences
                        ];

                        $club_manager->update_club($club_id, $club_data);

                        // Générer un numéro d'affiliation (si aucun n'existe déjà)
                        if (empty($club->num_affiliation)) {
                            $year = gmdate('Y');
                            $count = $club_manager->get_club_count_for_year($year);
                            $num_affiliation = sprintf('UFSC-%s-%03d', $year, $count + 1);
                            $club_manager->update_club($club_id, ['num_affiliation' => $num_affiliation]);
                        }

                        // Créer automatiquement les licences pour les dirigeants
                        $dirigeants = ['president', 'secretaire', 'tresorier'];
                        foreach ($dirigeants as $role) {
                            $prenom = $club->{$role . '_prenom'} ?? '';
                            $nom = $club->{$role . '_nom'} ?? '';
                            $email = $club->{$role . '_email'} ?? '';
                            
                            if (!empty($prenom) && !empty($nom) && !empty($email)) {
                                $licence_data = [
                                    'club_id' => $club_id,
                                    'nom' => $nom,
                                    'prenom' => $prenom,
                                    'email' => $email,
                                    'telephone' => $club->{$role . '_tel'},
                                    'fonction' => ucfirst($role),
                                    'statut' => 'pending', // En attente de validation admin
                                    'date_creation' => current_time('mysql'),
                                    'date_expiration' => gmdate('Y-m-d', strtotime('+1 year')),
                                    'type' => 'dirigeant',
                                    'order_id' => $order_id
                                ];

                                $club_manager->add_licence($licence_data);
                            }
                        }

                        // Notification à l'administrateur
                        $admin_email = get_option('admin_email');
                        $subject = 'Nouvelle affiliation club UFSC payée';
                        $message = "Une nouvelle affiliation a été payée pour le club : {$club->nom}\n\n";
                        $message .= "Commande : #{$order_id}\n";
                        $message .= "Montant : {$order->get_total()} €\n\n";
                        $message .= "Veuillez valider ce club dans l'administration.";

                        wp_mail($admin_email, $subject, $message);

                        // Notification au club
                        if (!empty($club->email)) {
                            $subject = 'Votre affiliation UFSC est en cours de traitement';
                            $message = "Bonjour,\n\n";
                            $message .= "Nous avons bien reçu votre paiement d'affiliation pour le club : {$club->nom}.\n\n";
                            $message .= "Votre dossier est en cours de traitement et sera validé dans les meilleurs délais.\n";
                            $message .= "Vous recevrez une notification dès que votre affiliation sera validée.\n\n";
                            $message .= "Cordialement,\n";
                            $message .= "L'équipe UFSC";

                            wp_mail($club->email, $subject, $message);
                        }
                    }
                }
            }
        }
    }
}
add_action('woocommerce_order_status_completed', 'ufsc_process_affiliation_order');
add_action('woocommerce_order_status_processing', 'ufsc_process_affiliation_order');

/**
 * Sauvegarder les données d'affiliation dans les métadonnées de la commande
 */
function ufsc_add_affiliation_data_to_order_items($item, $cart_item_key, $values, $order)
{
    if (isset($values['ufsc_club_id'])) {
        $item->update_meta_data('ufsc_club_id', $values['ufsc_club_id']);
    }

    if (isset($values['ufsc_club_nom'])) {
        $item->update_meta_data('ufsc_club_nom', $values['ufsc_club_nom']);
    }

    if (isset($values['ufsc_product_type'])) {
        $item->update_meta_data('ufsc_product_type', $values['ufsc_product_type']);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'ufsc_add_affiliation_data_to_order_items', 10, 4);
