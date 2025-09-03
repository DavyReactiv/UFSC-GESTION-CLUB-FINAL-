<?php

/**
 * Shortcode pour le formulaire d'affiliation club avec intégration WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode [ufsc_formulaire_affiliation]
 */
function ufsc_formulaire_affiliation_shortcode($atts)
{
    // Si l'utilisateur n'est pas connecté, afficher formulaire de connexion
    if (!is_user_logged_in()) {
        return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card"><div class="ufsc-alert ufsc-alert-error">'
            . '<p>Vous devez être connecté pour accéder au formulaire d\'affiliation.</p>'
            . ufsc_render_login_prompt()
            . '</div></div></div></div>';
    }

    // Vérifier si l'utilisateur a déjà un club affilié
    $user_id = get_current_user_id();
    
    // Vérifier que la fonction existe pour éviter les erreurs fatales
    if (!function_exists('ufsc_get_user_club')) {
        return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card"><div class="ufsc-alert ufsc-alert-error">'
                . '<p>Erreur de configuration du plugin. Veuillez contacter l\'administrateur.</p>'
                . '</div></div></div></div>';
    }
    
    $club = ufsc_get_user_club($user_id);

    if ($club && $club->statut !== 'Refusé') {
        $dashboard_button = ufsc_generate_safe_navigation_button('dashboard', 'Accéder à mon espace club', 'ufsc-btn ufsc-btn-primary', true);
        return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card"><div class="ufsc-alert ufsc-alert-info">'
                . '<h4>✅ Vous avez déjà un club</h4>'
                . '<p>Vous avez déjà un club en cours d\'affiliation ou affilié.</p>'
                . '<p>' . $dashboard_button . '</p>'
                . '</div></div></div></div>';
    }

    // Démarrer la capture de sortie
    ob_start();

    echo '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card">';

    // Inclusion du formulaire club avec paramètre spécial pour affiliation
    require_once UFSC_PLUGIN_PATH . 'includes/clubs/form-club.php';

    // Appel de la fonction avec le paramètre affiliation=true
    ufsc_render_club_form(($club ? $club->id : 0), true, true);

    echo '</div></div></div>';

    // Récupérer le contenu capturé
    return ob_get_clean();
}

add_shortcode('ufsc_formulaire_affiliation', 'ufsc_formulaire_affiliation_shortcode');

/**
 * Ajouter le produit d'affiliation au panier WooCommerce.
 *
 * @param int         $club_id   ID du club à affilier.
 * @param string|null $club_name Nom du club (optionnel). Si non fourni, il sera récupéré.
 *
 * @return bool Succès ou échec de l'ajout au panier.
 */
if (!function_exists('ufsc_add_affiliation_to_cart')) {
    function ufsc_add_affiliation_to_cart($club_id, $club_name = null)
    {
        if (!function_exists('WC')) {
            return false;
        }

        $product_id = ufsc_get_affiliation_product_id_safe();
        if (!$product_id) {
            return false;
        }

        // Récupérer le nom du club si non fourni
        if (empty($club_name)) {
            $club_manager = UFSC_Club_Manager::get_instance();
            $club        = $club_manager->get_club($club_id);
            if ($club) {
                $club_name = $club->nom;
            }
        }

        WC()->cart->empty_cart();

        $cart_item_data = [
            'ufsc_club_id'    => $club_id,
            'ufsc_product_type' => 'affiliation',
        ];

        if (!empty($club_name)) {
            $cart_item_data['ufsc_club_nom'] = $club_name;
        }

        $added = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        return (bool) $added;
    }
}

/**
 * Traiter la commande d'affiliation lorsqu'elle est payée.
 */
if (!function_exists('ufsc_process_affiliation_order')) {
    function ufsc_process_affiliation_order($order_id)
    {
        $order = wc_get_order($order_id);

        if (!$order || !in_array($order->get_status(), ['completed', 'processing'])) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            if ($product_id == ufsc_get_affiliation_product_id_safe()) {
                $club_id = $item->get_meta('ufsc_club_id');

                if ($club_id) {
                    $club_manager = UFSC_Club_Manager::get_instance();
                    $club         = $club_manager->get_club($club_id);

                    if ($club) {
                        $club_data = [
                            'statut'          => 'En attente de validation',
                            'date_affiliation' => current_time('mysql'),
                            'quota_licences'  => 10,
                        ];

                        $club_manager->update_club($club_id, $club_data);

                        // Générer un numéro d'affiliation si absent
                        if (empty($club->num_affiliation)) {
                            $year            = gmdate('Y');
                            $count           = $club_manager->get_club_count_for_year($year);
                            $num_affiliation = sprintf('UFSC-%s-%03d', $year, $count + 1);
                            $club_manager->update_club($club_id, ['num_affiliation' => $num_affiliation]);
                        }

                        // Créer automatiquement les licences pour les dirigeants
                        $dirigeants = ['president', 'secretaire', 'tresorier'];
                        foreach ($dirigeants as $role) {
                            $prenom = $club->{$role . '_prenom'} ?? '';
                            $nom    = $club->{$role . '_nom'} ?? '';
                            $email  = $club->{$role . '_email'} ?? '';

                            if (!empty($prenom) && !empty($nom) && !empty($email)) {
                                $licence_data = [
                                    'club_id'        => $club_id,
                                    'nom'            => $nom,
                                    'prenom'         => $prenom,
                                    'email'          => $email,
                                    'telephone'      => $club->{$role . '_tel'},
                                    'fonction'       => ucfirst($role),
                                    'statut'         => 'pending',
                                    'date_creation'  => current_time('mysql'),
                                    'date_expiration' => gmdate('Y-m-d', strtotime('+1 year')),
                                    'type'           => 'dirigeant',
                                    'order_id'       => $order_id,
                                ];

                                $club_manager->add_licence($licence_data);
                            }
                        }

                        // Notifications
                        $admin_email = get_option('admin_email');
                        $subject     = 'Nouvelle affiliation club UFSC payée';
                        $message     = "Une nouvelle affiliation a été payée pour le club : {$club->nom}\n\n";
                        $message    .= "Commande : #{$order_id}\n";
                        $message    .= "Montant : {$order->get_total()} €\n\n";
                        $message    .= "Veuillez valider ce club dans l'administration.";

                        wp_mail($admin_email, $subject, $message);

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
