<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ufscsn_resolve_club_id_sanitized')) {
    function ufscsn_resolve_club_id_sanitized(): int {
        $source = $_REQUEST['club_id'] ?? 0;
        return $source ? absint(wp_unslash($source)) : 0;
    }
}

if (!function_exists('ufscsn_require_manage_licence')) {
    function ufscsn_require_manage_licence(int $licence_id) {
        if (!current_user_can('manage_ufsc_licenses')) {
            wp_send_json_error(__('Access denied.', 'plugin-ufsc-gestion-club-13072025'), 403);
        }

        $club_id = ufscsn_resolve_club_id_sanitized();
        if (!$club_id) {
            wp_send_json_error(__('Club ID missing.', 'plugin-ufsc-gestion-club-13072025'), 403);
        }

        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        $licence_manager = new UFSC_Licence_Manager();
        $licence = $licence_manager->get_licence_by_id($licence_id);
        if (!$licence || intval($licence->club_id) !== intval($club_id)) {
            wp_send_json_error(__('Club mismatch.', 'plugin-ufsc-gestion-club-13072025'), 403);
        }

        return $licence;
    }
}
