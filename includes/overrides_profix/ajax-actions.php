<?php
if (!defined('ABSPATH')) exit;
function ufsc_profix_ajax_add_to_cart() {
    check_ajax_referer('ufsc_front_nonce');
    if (is_user_logged_in() && !current_user_can('read')) {
        wp_send_json_error(esc_html__('Non autorisé', 'ufsc-domain'), 403);
    }

    if (!class_exists('WC')) {
        wp_send_json_error(esc_html__('WooCommerce requis', 'ufsc-domain'), 400);
    }

    $product_id = (int) get_option('ufsc_licence_product_id', 0);
    if (!$product_id) {
        wp_send_json_error(esc_html__('product_id requis', 'ufsc-domain'));
    }

    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    $club_id    = isset($_POST['club_id']) ? absint($_POST['club_id']) : 0;
    $qty        = isset($_POST['quantity']) ? max(1, absint($_POST['quantity'])) : 1;
    $item_data  = [
        'licence_id' => $licence_id,
        'club_id'    => $club_id,
    ];
    $added = WC()->cart->add_to_cart($product_id, $qty, 0, [], $item_data);
    if (!$added) {
        wp_send_json_error(esc_html__('Ajout au panier impossible', 'ufsc-domain'));
    }

    $redirect_to_checkout = get_option('ufsc_redirect_to_checkout', 'cart') === 'checkout';
    if ($redirect_to_checkout && function_exists('wc_get_checkout_url')) {
        $redirect = wc_get_checkout_url();
    } else {
        $redirect = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/panier/');
    }

    $payment_badge = '';
    if (function_exists('ufsc_get_payment_badge')) {
        $payment_badge = ufsc_get_payment_badge('pending');
    }

    wp_send_json_success([
        'redirect'      => $redirect,
        'payment_badge' => $payment_badge,
    ]);
}
add_action('wp_ajax_ufsc_add_to_cart','ufsc_profix_ajax_add_to_cart');
add_action('wp_ajax_nopriv_ufsc_add_to_cart','ufsc_profix_ajax_add_to_cart');

function ufsc_profix_ajax_save_draft() {
    check_ajax_referer('ufsc_front_nonce');
    if (!is_user_logged_in() || !current_user_can('read')) {
        wp_send_json_error(esc_html__('Connexion requise', 'ufsc-domain'));
    }
    global $wpdb;
    $table   = $wpdb->prefix . 'ufsc_licences';
    $club    = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    $club_id = ($club && isset($club->id)) ? (int) $club->id : 0;
    if (!$club_id) {
        wp_send_json_error(esc_html__('Club introuvable', 'ufsc-domain'));
    }

    $nom    = isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '';
    $prenom = isset($_POST['prenom']) ? sanitize_text_field(wp_unslash($_POST['prenom'])) : '';
    $email  = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $role   = isset($_POST['role']) ? sanitize_text_field(wp_unslash($_POST['role'])) : '';

    if ($nom === '' || $prenom === '' || $email === '') {
        wp_send_json_error(esc_html__('Nom, prénom et email requis', 'ufsc-domain'));
    }

    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    $now        = current_time('mysql');
    if ($licence_id) {
        $ok = $wpdb->update(
            $table,
            [
                'nom'           => $nom,
                'prenom'        => $prenom,
                'email'         => $email,
                'role'          => $role,
                'statut'        => 'brouillon',
                'date_creation' => $now,
            ],
            ['id' => $licence_id, 'club_id' => $club_id],
            ['%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d', '%d']
        );

        if ($ok !== false) {
            wp_send_json_success(['licence_id' => $licence_id]);
        } else {
            wp_send_json_error(esc_html__('Échec de mise à jour du brouillon', 'ufsc-domain'));
        }
    } else {
        $ok = $wpdb->insert(
            $table,
            [
                'club_id'       => $club_id,
                'role'          => $role,
                'nom'           => $nom,
                'prenom'        => $prenom,
                'email'         => $email,
                'statut'        => 'brouillon',
                'date_creation' => $now,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($ok) {
            wp_send_json_success(['licence_id' => (int) $wpdb->insert_id]);
        } else {
            wp_send_json_error(esc_html__('Échec de création du brouillon', 'ufsc-domain'));
        }
    }
}
add_action('wp_ajax_ufsc_save_licence_draft','ufsc_profix_ajax_save_draft');
add_action('wp_ajax_nopriv_ufsc_save_licence_draft','ufsc_profix_ajax_save_draft');
function ufsc_profix_ajax_delete_draft() {
    check_ajax_referer('ufsc_front_nonce');
    if (!is_user_logged_in() || !current_user_can('read')) {
        wp_send_json_error(esc_html__('Connexion requise', 'ufsc-domain'));
    }

    global $wpdb;
    $id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if (!$id) {
        wp_send_json_error(esc_html__('ID manquant', 'ufsc-domain'));
    }

    $club    = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    $club_id = ($club && isset($club->id)) ? (int) $club->id : 0;
    if (!$club_id) {
        wp_send_json_error(esc_html__('Club introuvable', 'ufsc-domain'));
    }

    $table   = $wpdb->prefix . 'ufsc_licences';
    $licence = $wpdb->get_row($wpdb->prepare("SELECT club_id FROM $table WHERE id=%d", $id));
    if (!$licence) {
        wp_send_json_error(esc_html__('Licence introuvable', 'ufsc-domain'));
    }
    if ((int) $licence->club_id !== $club_id) {
        wp_send_json_error(esc_html__('Utilisateur non autorisé', 'ufsc-domain'));
    }

    $ok = $wpdb->update(
        $table,
        ['statut' => 'trash', 'deleted_at' => current_time('mysql')],
        ['id' => $id, 'club_id' => $club_id],
        ['%s', '%s'],
        ['%d', '%d']
    );

    if ($ok !== false) {
        wp_send_json_success();
    } else {
        wp_send_json_error(esc_html__('Suppression impossible', 'ufsc-domain'));
    }
}
add_action('wp_ajax_ufsc_delete_licence_draft','ufsc_profix_ajax_delete_draft');

