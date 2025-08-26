<?php
if (!defined('ABSPATH')) exit;
add_action('wp_enqueue_scripts', function(){
    wp_register_style('ufsc-forms', plugins_url('assets/css/ufsc-forms.css', dirname(__FILE__)), array(), UFSC_PLUGIN_VERSION);
    global $post; if ($post && (has_shortcode($post->post_content,'ufsc_licence_form') || has_shortcode($post->post_content,'ufsc_club_licenses'))){ wp_enqueue_style('ufsc-forms'); }
});

add_action('wp_enqueue_scripts', function(){
    global $post;
    if ($post && (has_shortcode($post->post_content,'ufsc_licence_form') || has_shortcode($post->post_content,'ufsc_club_licenses'))){
        wp_enqueue_script('ufsc-frontend', plugins_url('assets/js/ufsc-frontend.js', dirname(__FILE__)), array('jquery'), UFSC_PLUGIN_VERSION, true);
        wp_localize_script('ufsc-frontend','ufsc_ajax', {url: admin_url('admin-ajax.php')});
    }
});
