<?php

/**
 * UFSC Page Creator - Automatic creation of required frontend pages
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the mapping of required frontend pages
 * 
 * @return array Associative array of option_key => page_config
 * @since 1.3.0
 */
function ufsc_get_frontend_required_pages() {
    $pages = array(
        'ufsc_club_dashboard_page_id' => array(
            'title' => __('Tableau de Bord Club', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'tableau-de-bord-club',
            'content' => '[ufsc_club_dashboard]'
        ),
        'ufsc_affiliation_page_id' => array(
            'title' => __('Affiliation Club', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'affiliation-club',
            'content' => '[ufsc_affiliation_form]'
        ),
        'ufsc_club_account_page_id' => array(
            'title' => __('Compte Club', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'compte-club',
            'content' => '[ufsc_club_account]'
        ),
        'ufsc_licence_page_id' => array(
            'title' => __('Gestion des Licences', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'gestion-licences',
            'content' => '[ufsc_club_licences]'
        ),
        'ufsc_ajouter_licencie_page_id' => array(
            'title' => __('Ajouter un Licencié', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'ajouter-licencie',
            'content' => '[ufsc_ajouter_licencie]'
        ),
        'ufsc_demander_licence_page_id' => array(
            'title' => __('Demander une Licence', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'demander-licence',
            'content' => '[ufsc_licence_button]'
        ),
        'ufsc_attestation_page_id' => array(
            'title' => __('Attestations', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'attestations-club',
            'content' => '[ufsc_attestation_form]'
        ),
        'ufsc_liste_clubs_page_id' => array(
            'title' => __('Liste des Clubs', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'liste-clubs',
            'content' => '[ufsc_liste_clubs]'
        ),
        'ufsc_login_page_id' => array(
            'title' => __('Connexion Club', 'plugin-ufsc-gestion-club-13072025'),
            'slug' => 'connexion-club',
            'content' => '[ufsc_login_register]'
        )
    );
    
    /**
     * Filter the required frontend pages configuration
     * 
     * @param array $pages Array of page configurations
     * @since 1.3.0
     */
    return apply_filters('ufsc_frontend_required_pages', $pages);
}

/**
 * Ensure all required frontend pages exist
 * 
 * Creates pages if they don't exist or if their stored IDs point to non-published pages
 * 
 * @since 1.3.0
 */
function ufsc_ensure_frontend_pages() {
    $pages = ufsc_get_frontend_required_pages();
    
    foreach ($pages as $option_key => $page_config) {
        $page_id = get_option($option_key, 0);
        $needs_creation = false;
        
        // Check if page ID exists and is published
        if ($page_id) {
            $post_status = get_post_status($page_id);
            if ($post_status !== 'publish') {
                $needs_creation = true;
            }
        } else {
            $needs_creation = true;
        }
        
        // Also check if a page with the target slug already exists
        if ($needs_creation) {
            $existing_page = get_page_by_path($page_config['slug']);
            if ($existing_page && $existing_page->post_status === 'publish') {
                // Page exists with slug, update the option
                update_option($option_key, $existing_page->ID);
                continue;
            }
        }
        
        // Create the page if needed
        if ($needs_creation) {
            $page_data = array(
                'post_title' => $page_config['title'],
                'post_name' => $page_config['slug'],
                'post_content' => $page_config['content'],
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => 1, // Default to admin user
                'comment_status' => 'closed',
                'ping_status' => 'closed'
            );
            
            $new_page_id = wp_insert_post($page_data);
            
            if ($new_page_id && !is_wp_error($new_page_id)) {
                update_option($option_key, $new_page_id);
            }
        }
    }
    
    // Flush rewrite rules after page creation
    flush_rewrite_rules();
}

/**
 * One-time check to ensure pages exist after plugin update
 * 
 * @since 1.3.0
 */
function ufsc_admin_init_page_check() {
    // Only run once after plugin activation/update
    if (!get_option('ufsc_auto_page_check_done', false)) {
        ufsc_ensure_frontend_pages();
        update_option('ufsc_auto_page_check_done', true);
    }
}

// Hook into admin_init for lazy page creation check
add_action('admin_init', 'ufsc_admin_init_page_check');

// Register activation hook - this will be called from the main plugin file
if (defined('UFSC_PLUGIN_MAIN_FILE')) {
    register_activation_hook(UFSC_PLUGIN_MAIN_FILE, 'ufsc_ensure_frontend_pages');
}

/**
 * Retrieve the URL of the automatically created "Connexion Club" page.
 *
 * This helper ensures other components can easily link to the login page
 * created by this module.
 *
 * @return string|false The URL of the login page or false if it doesn't exist.
 * @since 1.3.0
 */
function ufsc_get_login_page_url() {
    $page_id = (int) get_option('ufsc_login_page_id', 0);

    if ($page_id && get_post_status($page_id) === 'publish') {
        return get_permalink($page_id);
    }

    return false;
}

/**
 * Display a notice if required frontend pages are missing or return 404.
 *
 * Provides an action link to recreate the pages automatically.
 *
 * @since 1.3.0
 */
function ufsc_notice_missing_frontend_pages() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $pages          = ufsc_get_frontend_required_pages();
    $missing_titles = array();

    foreach ($pages as $option_key => $page_config) {
        $page_id = (int) get_option($option_key, 0);
        if (!$page_id || get_post_status($page_id) !== 'publish') {
            $missing_titles[] = $page_config['title'];
            continue;
        }

        $permalink = get_permalink($page_id);
        if (!$permalink) {
            $missing_titles[] = $page_config['title'];
            continue;
        }

        $response = wp_remote_head($permalink, array('timeout' => 3));
        if (is_wp_error($response) || 404 === wp_remote_retrieve_response_code($response)) {
            $missing_titles[] = $page_config['title'];
        }
    }

    if (empty($missing_titles)) {
        return;
    }

    $action_url = wp_nonce_url(
        admin_url('admin-post.php?action=ufsc_recreate_frontend_pages'),
        'ufsc_recreate_frontend_pages'
    );

    echo '<div class="notice notice-warning"><p>' .
        esc_html(
            sprintf(
                __('Certaines pages requises sont manquantes ou introuvables : %s', 'plugin-ufsc-gestion-club-13072025'),
                implode(', ', $missing_titles)
            )
        ) .
        '</p><p><a class="button" href="' . esc_url($action_url) . '">' .
        esc_html__('Recréer automatiquement', 'plugin-ufsc-gestion-club-13072025') .
        '</a></p></div>';
}
add_action('admin_notices', 'ufsc_notice_missing_frontend_pages');

/**
 * Handle admin action to recreate frontend pages.
 *
 * @since 1.3.0
 */
function ufsc_handle_recreate_frontend_pages() {
    if (
        !current_user_can('manage_options') ||
        !check_admin_referer('ufsc_recreate_frontend_pages')
    ) {
        wp_die(__('Action non autorisée.', 'plugin-ufsc-gestion-club-13072025'));
    }

    ufsc_ensure_frontend_pages();
    delete_option('ufsc_auto_page_check_done');

    wp_safe_redirect(admin_url());
    exit;
}
add_action('admin_post_ufsc_recreate_frontend_pages', 'ufsc_handle_recreate_frontend_pages');
