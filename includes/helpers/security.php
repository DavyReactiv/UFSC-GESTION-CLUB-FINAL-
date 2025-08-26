<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper functions for nonce generation and validation.
 */

if (!function_exists('ufsc_create_nonce')) {
    function ufsc_create_nonce(string $action): string {
        return wp_create_nonce($action);
    }
}

if (!function_exists('ufsc_nonce_field')) {
    function ufsc_nonce_field(string $action, string $name = 'ufsc_nonce'): string {
        return wp_nonce_field($action, $name, true, false);
    }
}

if (!function_exists('ufsc_check_ajax_nonce')) {
    function ufsc_check_ajax_nonce(string $action, string $query_arg = 'ufsc_nonce', bool $die = true): bool {
        return check_ajax_referer($action, $query_arg, $die);
    }
}

if (!function_exists('ufsc_check_admin_nonce')) {
    function ufsc_check_admin_nonce(string $action, string $query_arg = 'ufsc_nonce', bool $die = true): bool {
        return check_admin_referer($action, $query_arg, $die);
    }
}
