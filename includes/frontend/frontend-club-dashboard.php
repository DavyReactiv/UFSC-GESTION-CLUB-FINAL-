<?php

/**
 * Dashboard Club Frontend - MVP Implementation
 */

/**
 * Load MVP dashboard implementation
 */
require_once UFSC_PLUGIN_PATH . 'includes/frontend/shortcodes/dashboard-mvp.php';
require_once UFSC_PLUGIN_PATH . 'includes/frontend/helpers/dashboard-data.php';

// NEW: Ensure logo upload helpers are loaded so hooks are valid
require_once UFSC_PLUGIN_PATH . 'includes/frontend/helpers/logo-upload.php';

/**
 * Shortcode pour afficher le tableau de bord du club (MVP Implementation)
 */
function ufsc_club_dashboard_shortcode($atts)
{
    // Use the new MVP dashboard implementation
    return ufsc_club_dashboard_mvp_shortcode($atts);
}

// Register logo upload handler ONLY if the function exists
if (function_exists('ufsc_process_club_logo_upload')) {
    add_action('init', 'ufsc_process_club_logo_upload');
}

add_shortcode('ufsc_club_dashboard', 'ufsc_club_dashboard_shortcode');