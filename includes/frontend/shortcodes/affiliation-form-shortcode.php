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
        return '<div class="ufsc-alert ufsc-alert-error">
                <p>Vous devez être connecté pour accéder au formulaire d\'affiliation.</p>' .
                ufsc_render_login_prompt() .
                '</div>';
    }

    // Vérifier si l'utilisateur a déjà un club affilié
    $user_id = get_current_user_id();
    
    // Vérifier que la fonction existe pour éviter les erreurs fatales
    if (!function_exists('ufsc_get_user_club')) {
        return '<div class="ufsc-alert ufsc-alert-error">
                <p>Erreur de configuration du plugin. Veuillez contacter l\'administrateur.</p>
                </div>';
    }
    
    $club = ufsc_get_user_club($user_id);

    if ($club && $club->statut !== 'Refusé') {
        $dashboard_button = ufsc_generate_safe_navigation_button('dashboard', 'Accéder à mon espace club', 'ufsc-btn ufsc-btn-primary', true);
        return '<div class="ufsc-alert ufsc-alert-info">
                <h4>✅ Vous avez déjà un club</h4>
                <p>Vous avez déjà un club en cours d\'affiliation ou affilié.</p>
                <p>' . $dashboard_button . '</p>
                </div>';
    }

    // Démarrer la capture de sortie
    ob_start();

    // Inclusion du formulaire club avec paramètre spécial pour affiliation
    require_once UFSC_PLUGIN_PATH . 'includes/clubs/form-club.php';

    // Appel de la fonction avec le paramètre affiliation=true
    ufsc_render_club_form(($club ? $club->id : 0), true, true);

    // Récupérer le contenu capturé
    return ob_get_clean();
}

add_shortcode('ufsc_formulaire_affiliation', 'ufsc_formulaire_affiliation_shortcode');

/**
 * Fonction pour ajouter automatiquement le produit d'affiliation au panier
 */
if (!function_exists('ufsc_add_affiliation_to_cart')) {
    function ufsc_add_affiliation_to_cart($club_id)
    {
        // ID du produit "Pack Affiliation"
        $product_id = get_option('ufsc_affiliation_product_id', 0);

        if (!$product_id) {
            return false;
        }

        // Vider le panier actuel
        WC()->cart->empty_cart();

        // Ajouter le produit au panier avec les métadonnées du club
        $cart_item_data = array(
            'ufsc_club_id' => $club_id,
            'ufsc_affiliation_type' => 'new_club'
        );

        // Ajouter au panier
        WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

        return true;
    }
}

/**
 * Traitement de la commande terminée
 */
if (!function_exists('ufsc_process_affiliation_order')) {
    function ufsc_process_affiliation_order($order_id)
    {
        $order = wc_get_order($order_id);

        // Vérifier si la commande est terminée
        if (!$order || $order->get_status() !== 'completed') {
            return;
        }

        // Parcourir les articles de la commande
        foreach ($order->get_items() as $item) {
            $club_id = wc_get_order_item_meta($item->get_id(), 'ufsc_club_id', true);
            $affiliation_type = wc_get_order_item_meta($item->get_id(), 'ufsc_affiliation_type', true);

            if ($club_id && $affiliation_type === 'new_club') {
                // Mettre à jour le club comme "En attente de validation"
                $club_manager = \UFSC\Clubs\ClubManager::get_instance();
                $club = $club_manager->get_club($club_id);

                if ($club) {
                    // Mise à jour du statut et date d'affiliation
                    $club_data = array(
                        'statut' => 'En attente de validation',
                        'date_affiliation' => current_time('mysql'),
                        'quota_licences' => 10 // Pack initial de 10 licences
                    );

                    $club_manager->update_club($club_id, $club_data);

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
                                'statut' => 'active',
                                'date_creation' => current_time('mysql'),
                                'date_expiration' => gmdate('Y-m-d', strtotime('+1 year')),
                                'type' => 'dirigeant'
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
add_action('woocommerce_order_status_completed', 'ufsc_process_affiliation_order');
