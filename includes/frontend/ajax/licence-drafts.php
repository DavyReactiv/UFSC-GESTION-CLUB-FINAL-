<?php
require_once dirname(dirname(__DIR__)).'/licences/persist.php';
// Frontend AJAX: licence drafts (save/delete)
if ( ! defined('ABSPATH') ) exit;

/**
 * Save (create/update) a licence as draft.
 * Expects POST fields at minimum: nom, prenom, email
 * Optionally accepts: licence_id (to update)
 */
function ufsc_ajax_save_draft(){
    if ( ! ufsc_check_ajax_nonce('ufsc_front_nonce', '_ajax_nonce', false) ) {
        wp_send_json_error( array('message' => esc_html__('Jeton invalide.', 'ufsc-domain')) );
    }
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array('message' => esc_html__('Connexion requise.', 'ufsc-domain')) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_licences';

    // Resolve current club the SAME way as listing
    $club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    $club_id = ($club && isset($club->id)) ? (int) $club->id : 0;
    if ( ! $club_id ) {
        wp_send_json_error( array('message' => esc_html__('Club introuvable pour cet utilisateur.', 'ufsc-domain')) );
    }

    // Sanitize minimal fields (others can exister mais on ne les impose pas ici)
    $nom    = isset($_POST['nom'])    ? sanitize_text_field( wp_unslash($_POST['nom']) )    : '';
    $prenom = isset($_POST['prenom']) ? sanitize_text_field( wp_unslash($_POST['prenom']) ) : '';
    $email  = isset($_POST['email'])  ? sanitize_email(       wp_unslash($_POST['email']) )  : '';
    $role   = isset($_POST['role'])   ? sanitize_text_field( wp_unslash($_POST['role']) )   : '';

    if ( $nom === '' || $prenom === '' || $email === '' ) {
        wp_send_json_error( array('message' => esc_html__('Nom, prénom et email sont requis.', 'ufsc-domain')) );
    }

    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;

    // Prepare safe columns used in list rendering
    $now = current_time('mysql');

    if ( $licence_id ) {
        // Update existing draft (restrict to this club)
        $updated = $wpdb->update(
            $table,
            array(

                'nom'           => $nom,
                'prenom'        => $prenom,
                'email'         => $email,
                'statut'        => 'brouillon',
                'date_creation' => $now,
                'role'           => $role,
                'nom'            => $nom,
                'prenom'         => $prenom,
                'email'          => $email,
                'statut'         => 'brouillon',
                'date_creation'  => $now,
            ),
            array(
                'id'      => $licence_id,
                'club_id' => $club_id,
            ),
            array('%s','%s','%s','%s','%s'),
            array('%d','%d')
        );
        if ( $updated !== false ) {
            wp_send_json_success( array('licence_id' => $licence_id) );
        } else {
            wp_send_json_error( array('message' => esc_html__('Échec de mise à jour du brouillon.', 'ufsc-domain')) );
        }
    } else {
        // Create draft
        $inserted = $wpdb->insert(
            $table,
            array(
                'club_id'       => $club_id,
                'role'          => $role,
                'nom'           => $nom,
                'prenom'        => $prenom,
                'email'         => $email,
                'statut'        => 'brouillon',
                'date_creation' => $now,
            ),
            array('%d','%s','%s','%s','%s','%s','%s')
        );
        if ( $inserted ) {
            $new_id = (int) $wpdb->insert_id;
            wp_send_json_success( array('licence_id' => $new_id) );
        } else {
            wp_send_json_error( array('message' => esc_html__('Échec de création du brouillon.', 'ufsc-domain')) );
        }
    }
}
add_action('wp_ajax_ufsc_save_licence_draft', 'ufsc_ajax_save_draft');
add_action('wp_ajax_nopriv_ufsc_save_licence_draft', 'ufsc_ajax_save_draft');

/**
 * Delete a draft (only if belongs to current user's club)
 */
function ufsc_ajax_delete_draft(){
    if ( ! ufsc_check_ajax_nonce('ufsc_front_nonce', '_ajax_nonce', false) ) {
        wp_send_json_error( array('message' => esc_html__('Jeton invalide.', 'ufsc-domain')) );
    }
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array('message' => esc_html__('Connexion requise.', 'ufsc-domain')) );
    }
    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if ( ! $licence_id ) {
        wp_send_json_error( array('message' => esc_html__('Licence introuvable.', 'ufsc-domain')) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_licences';

    $club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    $club_id = ($club && isset($club->id)) ? (int) $club->id : 0;

    if ( ! $club_id ) {
        wp_send_json_error( array('message' => esc_html__('Club introuvable.', 'ufsc-domain')) );
    }

    $deleted = $wpdb->delete( $table, array('id' => $licence_id, 'club_id' => $club_id), array('%d','%d') );
    if ( $deleted ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( array('message' => esc_html__('Suppression impossible.', 'ufsc-domain')) );
    }
}
add_action('wp_ajax_ufsc_delete_licence_draft','ufsc_ajax_delete_draft');
