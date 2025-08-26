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
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array('message' => __('Connexion requise.', 'plugin-ufsc-gestion-club-13072025')) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_licences';

    // Resolve current club the SAME way as listing
    $club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    $club_id = ($club && isset($club->id)) ? (int) $club->id : 0;
    if ( ! $club_id ) {
        wp_send_json_error( array('message' => __('Club introuvable pour cet utilisateur.', 'plugin-ufsc-gestion-club-13072025')) );
    }

    // Sanitize minimal fields (others can exister mais on ne les impose pas ici)
    $nom    = isset($_POST['nom'])    ? sanitize_text_field( wp_unslash($_POST['nom']) )    : '';
    $prenom = isset($_POST['prenom']) ? sanitize_text_field( wp_unslash($_POST['prenom']) ) : '';
    $email  = isset($_POST['email'])  ? sanitize_email(       wp_unslash($_POST['email']) )  : '';

    if ( $nom === '' || $prenom === '' || $email === '' ) {
        wp_send_json_error( array('message' => __('Nom, prénom et email sont requis.', 'plugin-ufsc-gestion-club-13072025')) );
    }

    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;

    // Prepare safe columns used in list rendering
    $now = current_time('mysql');

    if ( $licence_id ) {
        // Update existing draft (restrict to this club)
        $updated = $wpdb->update(
            $table,
            array(
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
            array('%s','%s','%s','%s','%s','%s'),
            array('%d','%d')
        );
        if ( $updated !== false ) {
            wp_send_json_success( array('licence_id' => $licence_id) );
        } else {
            wp_send_json_error( array('message' => __('Échec de mise à jour du brouillon.', 'plugin-ufsc-gestion-club-13072025')) );
        }
    } else {
        // Create draft
        $inserted = $wpdb->insert($table, array('club_id'=>$club_id,
                'role' => $role,
                'nom'            => $nom,
                'prenom'         => $prenom,
                'email'          => $email,
                'statut'         => 'brouillon',
                'date_creation'  => $now,
            ),
            array('%d','%s','%s','%s','%s','%s')
        );
        if ( $inserted ) {
            $new_id = (int) $wpdb->insert_id;
            wp_send_json_success( array('licence_id' => $new_id) );
        } else {
            wp_send_json_error( array('message' => __('Échec de création du brouillon.', 'plugin-ufsc-gestion-club-13072025')) );
        }
    }
}
add_action('wp_ajax_ufsc_save_licence_draft', 'ufsc_ajax_save_draft');
add_action('wp_ajax_nopriv_ufsc_save_licence_draft', 'ufsc_ajax_save_draft');

/**
 * Delete a draft (only if belongs to current user's club)
 */
function ufsc_ajax_delete_draft(){
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array('message' => __('Connexion requise.', 'plugin-ufsc-gestion-club-13072025')) );
    }
    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if ( ! $licence_id ) {
        wp_send_json_error( array('message' => __('Licence introuvable.', 'plugin-ufsc-gestion-club-13072025')) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ufsc_licences';

    $club = function_exists('ufsc_get_user_club') ? ufsc_get_user_club() : null;
    $club_id = ($club && isset($club->id)) ? (int) $club->id : 0;

    if ( ! $club_id ) {
        wp_send_json_error( array('message' => __('Club introuvable.', 'plugin-ufsc-gestion-club-13072025')) );
    }

    $deleted = $wpdb->delete( $table, array('id' => $licence_id, 'club_id' => $club_id), array('%d','%d') );
    if ( $deleted ) {
        wp_send_json_success();
    } else {
        wp_send_json_error( array('message' => __('Suppression impossible.', 'plugin-ufsc-gestion-club-13072025')) );
    }
}
add_action('wp_ajax_ufsc_delete_licence_draft','ufsc_ajax_delete_draft');
add_action('wp_ajax_nopriv_ufsc_delete_licence_draft','ufsc_ajax_delete_draft');
