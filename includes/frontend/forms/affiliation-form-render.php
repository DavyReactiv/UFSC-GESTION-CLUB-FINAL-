<?php

/**
 * Unified Affiliation Form Renderer
 * 
 * Provides consistent affiliation form rendering for both WooCommerce product pages 
 * and standalone shortcode usage.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render unified affiliation form
 * 
 * @param array $args Form arguments and configuration
 * @return string HTML form output
 */
function ufsc_render_affiliation_form($args = [])
{
    // Default arguments
    $defaults = [
        'context' => 'shortcode', // 'woocommerce' or 'shortcode'
        'show_title' => true,
        'submit_button_text' => 'Procéder à l\'affiliation',
        'form_id' => 'ufsc-affiliation-form',
        'redirect_to_product' => false
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<div class="ufsc-alert ufsc-alert-error">
            <h4>Connexion requise</h4>
            <p>Vous devez être connecté pour procéder à une affiliation.</p>
            <p><a href="' . wp_login_url(get_permalink()) . '" class="ufsc-btn">Se connecter</a></p>
            </div>';
    }
    
    $user_id = get_current_user_id();
    
    // Check if user already has a club
    $existing_club = ufsc_get_user_club($user_id);
    $is_renewal = false;
    
    if ($existing_club) {
        $is_renewal = true;
        
        // If club is already active, show info message
        if (ufsc_is_club_active($existing_club)) {
            return '<div class="ufsc-alert ufsc-alert-info">
                <h4>Club déjà affilié</h4>
                <p>Votre club "' . esc_html($existing_club->nom) . '" est déjà affilié et actif.</p>
                <p>Si vous souhaitez renouveler votre affiliation ou mettre à jour vos informations, contactez l\'administration UFSC.</p>
                </div>';
        }
    }
    
    // If this is a shortcode context and not on product page, redirect to product
    if ($args['context'] === 'shortcode' && $args['redirect_to_product']) {
        $product_url = get_permalink(wc_get_product(ufsc_get_affiliation_product_id()));
        if ($product_url) {
            $action_text = $is_renewal ? 'Renouveler l\'affiliation' : 'Procéder à l\'affiliation';
            return '<div class="ufsc-alert ufsc-alert-info">
                <h4>' . ($is_renewal ? 'Renouvellement d\'affiliation' : 'Affiliation club') . '</h4>
                <p>Pour ' . ($is_renewal ? 'renouveler votre affiliation' : 'affilier votre club') . ', veuillez utiliser notre système de commande intégré.</p>
                <p><a href="' . esc_url($product_url) . '" class="ufsc-btn ufsc-btn-primary">' . esc_html($action_text) . '</a></p>
                </div>';
        }
    }
    
    // Check if there's already an affiliation product in cart
    if (function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['product_id']) && $cart_item['product_id'] == ufsc_get_affiliation_product_id()) {
                return '<div class="ufsc-alert ufsc-alert-warning">
                    <h4>Affiliation en cours</h4>
                    <p>Une demande d\'affiliation est déjà dans votre panier.</p>
                    <p><a href="' . wc_get_cart_url() . '" class="ufsc-btn">Voir le panier</a></p>
                    </div>';
            }
        }
    }
    
    // Load regions data
    $regions_file = plugin_dir_path(__FILE__) . '../../../data/regions.php';
    $regions = file_exists($regions_file) ? require $regions_file : [];
    
    // Generate nonce
    $nonce = wp_create_nonce('ufsc_affiliation_nonce');
    
    // Start form output
    $output = '';
    
    if ($args['show_title']) {
        $title = $is_renewal ? 'Renouvellement d\'affiliation' : 'Affiliation de club';
        $output .= '<h3>' . esc_html($title) . '</h3>';
    }
    
    if ($is_renewal) {
        $output .= '<div class="ufsc-alert ufsc-alert-info">
            <p><strong>Renouvellement:</strong> Vous procédez au renouvellement de l\'affiliation pour le club "' . esc_html($existing_club->nom) . '".</p>
            </div>';
    }
    
    $output .= '<form id="' . esc_attr($args['form_id']) . '" class="ufsc-affiliation-form" method="post">';
    $output .= wp_nonce_field('ufsc_affiliation_nonce', '_ufsc_affiliation_nonce', true, false);
    $output .= '<input type="hidden" name="action" value="ufsc_add_affiliation_to_cart">';
    $output .= '<input type="hidden" name="context" value="' . esc_attr($args['context']) . '">';
    $output .= '<input type="hidden" name="is_renewal" value="' . ($is_renewal ? '1' : '0') . '">';
    if ($existing_club) {
        $output .= '<input type="hidden" name="existing_club_id" value="' . esc_attr($existing_club->id) . '">';
    }
    
    // Club information section
    $output .= '<div class="ufsc-form-section">
        <h4>Informations du club</h4>
        <div class="ufsc-form-field">
            <label for="nom">Nom du club *</label>
            <input type="text" id="nom" name="nom" required maxlength="200" value="' . ($existing_club ? esc_attr($existing_club->nom) : '') . '">
        </div>
        
        <div class="ufsc-form-field">
            <label for="description">Description du club</label>
            <textarea id="description" name="description" rows="4" maxlength="1000">' . ($existing_club ? esc_textarea($existing_club->description) : '') . '</textarea>
        </div>
        
        <div class="ufsc-form-row">
            <div class="ufsc-form-field">
                <label for="adresse">Adresse *</label>
                <input type="text" id="adresse" name="adresse" required maxlength="200" value="' . ($existing_club ? esc_attr($existing_club->adresse) : '') . '">
            </div>
        </div>
        
        <div class="ufsc-form-row">
            <div class="ufsc-form-field">
                <label for="code_postal">Code postal *</label>
                <input type="text" id="code_postal" name="code_postal" required pattern="[0-9]{5}" maxlength="5" value="' . ($existing_club ? esc_attr($existing_club->code_postal) : '') . '">
            </div>
            <div class="ufsc-form-field">
                <label for="ville">Ville *</label>
                <input type="text" id="ville" name="ville" required maxlength="100" value="' . ($existing_club ? esc_attr($existing_club->ville) : '') . '">
            </div>
        </div>';
    
    // Region selection
    if (!empty($regions)) {
        $selected_region = $existing_club ? $existing_club->region : '';
        $output .= '<div class="ufsc-form-field">
            <label for="region">Région UFSC *</label>
            <select id="region" name="region" required>
                <option value="">Sélectionner une région...</option>';
        
        foreach ($regions as $region) {
            $selected = ($region === $selected_region) ? ' selected' : '';
            $output .= '<option value="' . esc_attr($region) . '"' . $selected . '>' . esc_html($region) . '</option>';
        }
        
        $output .= '</select>
        </div>';
    }
    
    $output .= '</div>';
    
    // Contact information section
    $output .= '<div class="ufsc-form-section">
        <h4>Contact</h4>
        <div class="ufsc-form-row">
            <div class="ufsc-form-field">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required maxlength="150" value="' . ($existing_club ? esc_attr($existing_club->email) : '') . '">
            </div>
            <div class="ufsc-form-field">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" maxlength="20" value="' . ($existing_club ? esc_attr($existing_club->telephone) : '') . '">
            </div>
        </div>
        
        <div class="ufsc-form-field">
            <label for="site_web">Site web</label>
            <input type="url" id="site_web" name="site_web" maxlength="200" placeholder="https://" value="' . ($existing_club ? esc_attr($existing_club->site_web) : '') . '">
        </div>
    </div>';
    
    // Additional information section
    $output .= '<div class="ufsc-form-section">
        <h4>Informations complémentaires</h4>
        <div class="ufsc-form-row">
            <div class="ufsc-form-field">
                <label for="siret">SIRET</label>
                <input type="text" id="siret" name="siret" maxlength="14" pattern="[0-9]{14}" value="' . ($existing_club ? esc_attr($existing_club->siret) : '') . '">
            </div>
            <div class="ufsc-form-field">
                <label for="num_rna">Numéro RNA</label>
                <input type="text" id="num_rna" name="num_rna" maxlength="10" value="' . ($existing_club ? esc_attr($existing_club->num_rna) : '') . '">
            </div>
        </div>
        
        <div class="ufsc-form-field">
            <label for="activites">Activités pratiquées</label>
            <textarea id="activites" name="activites" rows="3" maxlength="500" placeholder="Décrivez les activités sportives proposées par votre club...">' . ($existing_club ? esc_textarea($existing_club->activites) : '') . '</textarea>
        </div>
    </div>';
    
    // Terms and conditions
    $output .= '<div class="ufsc-form-section">
        <div class="ufsc-form-field">
            <label class="ufsc-checkbox-label">
                <input type="checkbox" id="accept_terms" name="accept_terms" required>
                <span class="ufsc-checkbox-custom"></span>
                J\'accepte les <a href="#" target="_blank">conditions générales</a> et le <a href="#" target="_blank">règlement intérieur</a> de l\'UFSC *
            </label>
        </div>
        
        <div class="ufsc-form-field">
            <label class="ufsc-checkbox-label">
                <input type="checkbox" id="accept_data" name="accept_data" required>
                <span class="ufsc-checkbox-custom"></span>
                J\'accepte le traitement de mes données personnelles conformément à la <a href="#" target="_blank">politique de confidentialité</a> *
            </label>
        </div>
    </div>';
    
    // Form actions
    $output .= '<div class="ufsc-form-actions">
        <button type="submit" class="ufsc-btn ufsc-btn-primary" id="ufsc-submit-affiliation">
            ' . esc_html($args['submit_button_text']) . '
        </button>
        <div class="ufsc-form-loading" style="display:none;">
            <span class="ufsc-spinner"></span> Traitement en cours...
        </div>
    </div>';
    
    $output .= '</form>';
    
    // Add JavaScript for form handling
    $output .= '<script>
    jQuery(document).ready(function($) {
        $("#' . esc_js($args['form_id']) . '").on("submit", function(e) {
            e.preventDefault();
            ufscHandleAffiliationFormSubmit(this);
        });
    });
    </script>';
    
    return $output;
}