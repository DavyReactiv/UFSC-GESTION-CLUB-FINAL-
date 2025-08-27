<?php
/**
 * License Validation Handler
 * 
 * Handles secure admin-post actions for license validation with proper
 * security checks, access verification, and user feedback.
 *
 * @package UFSC_Gestion_Club
 * @subpackage Admin
 * @since 1.3.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include required helpers
require_once UFSC_PLUGIN_PATH . 'includes/helpers/helpers-licence-status.php';

/**
 * Handle single license validation via admin-post
 * 
 * Securely validates a single license with all necessary checks:
 * - User capabilities (manage_options)
 * - Nonce verification
 * - Club access verification
 * - License status validation
 */
if (!function_exists('ufsc_admin_post_validate_licence')) {
    function ufsc_admin_post_validate_licence() {
        // Check user capabilities
        if (!current_user_can('manage_ufsc_licenses')) {
            wp_die(
                __('Accès refusé. Vous n\'avez pas les permissions nécessaires.', 'plugin-ufsc-gestion-club-13072025'),
                __('Erreur de permission', 'plugin-ufsc-gestion-club-13072025'),
                ['response' => 403]
            );
        }
        
        // Get and validate license ID
        $licence_id = isset($_GET['licence_id']) ? absint($_GET['licence_id']) : 0;
        if (!$licence_id) {
            wp_die(
                __('ID de licence invalide.', 'plugin-ufsc-gestion-club-13072025'),
                __('Erreur de paramètre', 'plugin-ufsc-gestion-club-13072025'),
                ['response' => 400]
            );
        }
        
        // Verify nonce
        $nonce_action = 'ufsc_validate_licence_' . $licence_id;
        check_admin_referer($nonce_action);
        
        // Get license manager and retrieve license
        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        $licence_manager = UFSC_Licence_Manager::get_instance();
        $licence = $licence_manager->get_licence_by_id($licence_id);
        
        if (!$licence) {
            wp_die(
                __('Licence introuvable.', 'plugin-ufsc-gestion-club-13072025'),
                __('Licence non trouvée', 'plugin-ufsc-gestion-club-13072025'),
                ['response' => 404]
            );
        }
        
        // Verify club access
        if (!ufsc_verify_club_access($licence->club_id)) {
            wp_die(
                __('Accès refusé au club associé à cette licence.', 'plugin-ufsc-gestion-club-13072025'),
                __('Accès refusé', 'plugin-ufsc-gestion-club-13072025'),
                ['response' => 403]
            );
        }
        
        // Check if license can be validated (must be pending)
        if (!ufsc_is_pending_status($licence->statut)) {
            $redirect_url = add_query_arg([
                'page' => 'ufsc-liste-licences',
                'message' => 'error',
                'error_code' => 'invalid_status'
            ], admin_url('admin.php'));
            
            wp_redirect($redirect_url);
            exit;
        }
        
        // Check if license is paid or included in quota
        if (!ufsc_is_licence_paid($licence_id)) {
            $redirect_url = add_query_arg([
                'page' => 'ufsc-liste-licences',
                'message' => 'error',
                'error_code' => 'unpaid'
            ], admin_url('admin.php'));
            
            wp_redirect($redirect_url);
            exit;
        }
        
        // Update license status to validee (canonical validated status)
        $success = $licence_manager->update_licence_status($licence_id, 'validee');
        
        // Determine redirect URL based on success
        if ($success) {
            $redirect_url = add_query_arg([
                'page' => 'ufsc-liste-licences',
                'message' => 'validated',
                'licence_id' => $licence_id
            ], admin_url('admin.php'));
        } else {
            $redirect_url = add_query_arg([
                'page' => 'ufsc-liste-licences',
                'message' => 'error',
                'error_code' => 'update_failed'
            ], admin_url('admin.php'));
        }
        
        wp_redirect($redirect_url);
        exit;
    }
}

/**
 * Handle bulk license validation via admin-post
 * 
 * Processes multiple license validations with proper security and feedback.
 */
function ufsc_handle_bulk_validate_licences() {
    // Check user capabilities
    if (!current_user_can('manage_ufsc_licenses')) {
        wp_die(
            __('Accès refusé. Vous n\'avez pas les permissions nécessaires.', 'plugin-ufsc-gestion-club-13072025'),
            __('Erreur de permission', 'plugin-ufsc-gestion-club-13072025'),
            ['response' => 403]
        );
    }
    
    // Verify nonce
    check_admin_referer('bulk-licences');
    
    // Get selected license IDs
    $licence_ids = isset($_POST['licence']) ? array_map('absint', $_POST['licence']) : [];
    if (empty($licence_ids)) {
        $redirect_url = add_query_arg([
            'page' => 'ufsc-liste-licences',
            'message' => 'error',
            'error_code' => 'no_selection'
        ], admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    // Get license manager
    require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
    $licence_manager = UFSC_Licence_Manager::get_instance();
    
    $validated_count = 0;
    $error_count = 0;
    
    foreach ($licence_ids as $licence_id) {
        $licence = $licence_manager->get_licence_by_id($licence_id);
        
        // Skip if licence doesn't exist or can't be validated
        if (!$licence || !ufsc_is_pending_status($licence->statut)) {
            $error_count++;
            continue;
        }
        
        // Verify club access for this licence
        if (!ufsc_verify_club_access($licence->club_id)) {
            $error_count++;
            continue;
        }
        
        // Check if license is paid or included in quota
        if (!ufsc_is_licence_paid($licence_id)) {
            $error_count++;
            continue;
        }
        
        // Update status to validee (canonical validated status)
        if ($licence_manager->update_licence_status($licence_id, 'validee')) {
            $validated_count++;
        } else {
            $error_count++;
        }
    }
    
    // Redirect with results
    $redirect_url = add_query_arg([
        'page' => 'ufsc-liste-licences',
        'message' => 'bulk_validated',
        'validated' => $validated_count,
        'errors' => $error_count
    ], admin_url('admin.php'));
    
    wp_redirect($redirect_url);
    exit;
}

/**
 * Display admin notices for license validation actions
 */
function ufsc_licence_validation_admin_notices() {
    if (!isset($_GET['message'])) {
        return;
    }
    
    $message = sanitize_text_field($_GET['message']);
    
    switch ($message) {
        case 'validated':
            $licence_id = isset($_GET['licence_id']) ? absint($_GET['licence_id']) : 0;
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(
                __('Licence #%d validée avec succès !', 'plugin-ufsc-gestion-club-13072025'),
                $licence_id
            ) . '</p>';
            echo '</div>';
            break;
            
        case 'bulk_validated':
            $validated = isset($_GET['validated']) ? absint($_GET['validated']) : 0;
            $errors = isset($_GET['errors']) ? absint($_GET['errors']) : 0;
            
            if ($validated > 0) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . sprintf(
                    _n(
                        '%d licence validée avec succès.',
                        '%d licences validées avec succès.',
                        $validated,
                        'plugin-ufsc-gestion-club-13072025'
                    ),
                    $validated
                ) . '</p>';
                echo '</div>';
            }
            
            if ($errors > 0) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p>' . sprintf(
                    _n(
                        '%d licence n\'a pas pu être validée.',
                        '%d licences n\'ont pas pu être validées.',
                        $errors,
                        'plugin-ufsc-gestion-club-13072025'
                    ),
                    $errors
                ) . '</p>';
                echo '</div>';
            }
            break;
            
        case 'error':
            $error_code = isset($_GET['error_code']) ? sanitize_text_field($_GET['error_code']) : '';
            $error_messages = [
                'invalid_status' => __('La licence ne peut pas être validée dans son état actuel.', 'plugin-ufsc-gestion-club-13072025'),
                'unpaid' => __('La licence ne peut pas être validée car elle n\'est pas payée ou incluse dans le quota.', 'plugin-ufsc-gestion-club-13072025'),
                'update_failed' => __('Erreur lors de la mise à jour de la licence.', 'plugin-ufsc-gestion-club-13072025'),
                'no_selection' => __('Aucune licence sélectionnée pour la validation.', 'plugin-ufsc-gestion-club-13072025'),
            ];
            
            $error_message = isset($error_messages[$error_code]) 
                ? $error_messages[$error_code] 
                : __('Une erreur inconnue s\'est produite.', 'plugin-ufsc-gestion-club-13072025');
                
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>' . esc_html($error_message) . '</p>';
            echo '</div>';
            break;
    }
}

// Register admin-post handlers
add_action('admin_post_ufsc_validate_licence', 'ufsc_admin_post_validate_licence');
add_action('admin_post_ufsc_bulk_validate_licences', 'ufsc_handle_bulk_validate_licences');

// Register admin notices
add_action('admin_notices', 'ufsc_licence_validation_admin_notices');

// Force validate (admin only)
add_action('admin_post_ufsc_force_validate_licence', function(){
    if ( ! current_user_can('manage_options') ) {
        wp_die(__('Non autorisé', 'plugin-ufsc-gestion-club-13072025'));
    }
    $licence_id = isset($_POST['licence_id']) ? absint($_POST['licence_id']) : 0;
    if (!$licence_id) wp_die('Licence introuvable');
    if (isset($GLOBALS['licence_manager'])) {
        $ok = $GLOBALS['licence_manager']->update_licence_status($licence_id, 'validee');
    } else {
        global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
        $ok = $wpdb->update($t, array('statut'=>'validee'), array('id'=>$licence_id));
    }
    wp_redirect(add_query_arg(array('page'=>'ufsc-liste-licences','message'=>$ok?'validated':'error'), admin_url('admin.php')));
    exit;
});
