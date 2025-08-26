<?php
if (!defined('ABSPATH')) exit;
add_action('wp_enqueue_scripts', function(){
    $asset = ufsc_get_asset('ufsc-forms.css');
    wp_register_style('ufsc-forms', $asset['url'], [], $asset['version']);
    global $post; if ($post && (has_shortcode($post->post_content,'ufsc_licence_form') || has_shortcode($post->post_content,'ufsc_club_licenses'))){ wp_enqueue_style('ufsc-forms'); }
});

add_action('wp_enqueue_scripts', function(){
    global $post;
    if ($post && (has_shortcode($post->post_content,'ufsc_licence_form') || has_shortcode($post->post_content,'ufsc_club_licenses'))){
        $asset = ufsc_get_asset('ufsc-frontend.js');
        wp_enqueue_script('ufsc-frontend', $asset['url'], ['jquery'], $asset['version'], true);
        wp_localize_script('ufsc-frontend','ufsc_ajax', ['url' => admin_url('admin-ajax.php')]);
    }
});
