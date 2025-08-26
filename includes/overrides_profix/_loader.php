<?php
if (!defined('ABSPATH')) exit;
add_action('plugins_loaded', function(){
    remove_all_actions('wp_ajax_ufsc_save_licence_draft');
    remove_all_actions('wp_ajax_nopriv_ufsc_save_licence_draft');
    remove_all_actions('wp_ajax_ufsc_delete_licence_draft');
    remove_all_actions('wp_ajax_nopriv_ufsc_delete_licence_draft');
    remove_all_actions('wp_ajax_ufsc_add_to_cart');
    remove_all_actions('wp_ajax_nopriv_ufsc_add_to_cart');
}, 1);
$__candidates = array(
    dirname(__DIR__) . '/licences/class-licence-manager.php',
    dirname(__DIR__,2) . '/includes/licences/class-licence-manager.php',
);
foreach ($__candidates as $__file) { if (file_exists($__file)) { require_once $__file; break; } }
require_once __DIR__.'/helpers-compat.php';
require_once __DIR__.'/front-enqueue.php';
require_once __DIR__.'/ajax-actions.php';
require_once __DIR__.'/force-shortcode-override.php';
require_once __DIR__.'/admin-ajax-validate.php';
require_once __DIR__.'/admin-enqueue.php';
