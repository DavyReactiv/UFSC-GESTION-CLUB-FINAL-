<?php
if (!defined('ABSPATH')) exit;

/**
 * Route front pour ajouter une licence au panier sans passer par /wp-admin/ (cookies Woo ok).
 * Utilisation : /?ufsc_pay_licence=ID
 */
add_action('template_redirect', function(){
    if (empty($_GET['ufsc_pay_licence'])) return;
    if (!is_user_logged_in()) { wp_safe_redirect( wp_login_url( wc_get_checkout_url() ) ); exit; }

    $licence_id = absint( wp_unslash( $_GET['ufsc_pay_licence'] ) );
    global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
    // Récupère la licence et le club
    $lic = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d", $licence_id));
    if (!$lic) { wp_safe_redirect( home_url('/') ); exit; }

    // Résolution produit licence
    $licence_product_id = 0;
    $pid1 = absint(get_option('ufsc_wc_individual_licence_product_id', 0));
    if ($pid1) $licence_product_id = $pid1;
    if (!$licence_product_id){
        $csv = get_option('ufsc_wc_license_product_ids', '');
        if ($csv){
            $parts = array_filter(array_map('absint', array_map('trim', explode(',', $csv))));
            if ($parts) $licence_product_id = (int) array_shift($parts);
        }
    }
    if (!$licence_product_id){
        $opts=get_option('ufsc_pack_settings',array());
        if (!empty($opts['licence_product_id'])) $licence_product_id=(int)$opts['licence_product_id'];
    }
    if (!$licence_product_id){ wp_die(__('Produit Licence non configuré.','plugin-ufsc-gestion-club-13072025')); }

    // Ajout au panier côté front
    if (class_exists('WC')){
        if (!WC()->cart) wc_load_cart();
        $data = array(
            'ufsc_club_id' => (int) $lic->club_id,
            'ufsc_licence_id' => (int) $lic->id,
            'ufsc_is_included' => (int) ($lic->is_included ?? 0),
        );
        WC()->cart->add_to_cart($licence_product_id, 1, 0, array(), $data);
        wp_safe_redirect( wc_get_checkout_url() ); exit;
    }
});

/**
 * Route front pour ajouter l'affiliation au panier et rediriger vers le paiement.
 * Utilisation : /?ufsc_pay_affiliation=ID_CLUB
 */
add_action('template_redirect', function(){
    if (empty($_GET['ufsc_pay_affiliation'])) return;
    if (!is_user_logged_in()) { wp_safe_redirect( wp_login_url( wc_get_checkout_url() ) ); exit; }

    $club_id = absint( wp_unslash( $_GET['ufsc_pay_affiliation'] ) );
    $club_manager = UFSC_Club_Manager::get_instance();
    $club = $club_manager->get_club($club_id);
    if (!$club) { wp_safe_redirect( home_url('/') ); exit; }

    if (class_exists('WC')){
        if (!WC()->cart) wc_load_cart();
        $data = array(
            'ufsc_club_id' => $club_id,
            'ufsc_club_nom' => $club->nom,
            'ufsc_product_type' => 'affiliation',
        );
        WC()->cart->add_to_cart( ufsc_get_affiliation_product_id_safe(), 1, 0, array(), $data );
        wp_safe_redirect( wc_get_checkout_url() ); exit;
    }
});
