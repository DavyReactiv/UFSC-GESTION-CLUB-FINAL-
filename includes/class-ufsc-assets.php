<?php
if (!defined('ABSPATH')) exit;

class UFSC_Fix_Assets {
    public static function init(){
        add_action('wp_enqueue_scripts', [__CLASS__,'front']);
    }
    private static function cdn_available($url){
        $response = wp_remote_head($url, ['timeout' => 2]);
        return !(is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200);
    }
    private static function has_shortcodes(){
        if (!is_page()) return false;
        $post = get_post(get_queried_object_id());
        if (!$post) return false;
        $content = $post->post_content;
        $shortcodes = ['ufsc_formulaire_club','ufsc_licence_form','ufsc_club_account','ufsc_club_licences','ufsc_club_dashboard'];
        foreach($shortcodes as $sc){ if ( has_shortcode($content, $sc) ) return true; }
        return false;
    }
    public static function front(){
        if (!self::has_shortcodes()) return;
        // CSS
        
        // Optional libs used by some front features
        $assets_url = plugins_url('assets/', dirname(__FILE__));

        // Notyf (toast notifications)
        $notyf_js  = 'https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js';
        $notyf_css = 'https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css';
        if (self::cdn_available($notyf_js) && self::cdn_available($notyf_css)) {
            wp_enqueue_style('notyf-css', $notyf_css, [], '3');
            wp_enqueue_script('notyf', $notyf_js, [], '3', true);
        } else {
            wp_enqueue_style('notyf-css', $assets_url . 'notyf/notyf.min.css', [], '3');
            wp_enqueue_script('notyf', $assets_url . 'notyf/notyf.min.js', [], '3', true);
        }

        // jQuery DataTables (for enhanced tables if theme expects it)
        $dt_js  = 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js';
        $dt_css = 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css';
        if (self::cdn_available($dt_js) && self::cdn_available($dt_css)) {
            wp_enqueue_style('datatables-css', $dt_css, [], '1.13.8');
            wp_enqueue_script('datatables', $dt_js, ['jquery'], '1.13.8', true);
        } else {
            wp_enqueue_style('datatables-css', $assets_url . 'datatables/css/jquery.dataTables.min.css', [], '1.13.8');
            wp_enqueue_script('datatables', $assets_url . 'datatables/js/jquery.dataTables.min.js', ['jquery'], '1.13.8', true);
        }

        wp_enqueue_style('ufsc-front-fixes', plugins_url('assets/css/ufsc-front.css', dirname(__FILE__)), [], '20.3');
        wp_enqueue_style('ufsc-ui', plugins_url('assets/css/ufsc-ui.css', dirname(__FILE__)), [], '1.0');
        // JS
        $deps = ['jquery'];
        wp_enqueue_script('ufsc-front-fixes', plugins_url('assets/js/ufsc-front.js', dirname(__FILE__)), $deps, '20.3', true);
        wp_localize_script('ufsc-front-fixes','UFSC', [
            // Provide ajax endpoints for scripts
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'frontNonce' => wp_create_nonce('ufsc_front_nonce'),
            'i18n' => [
                'saving' => __('Enregistrement…','plugin-ufsc-gestion-club-13072025'),
                'saved'  => __('Brouillon enregistré.','plugin-ufsc-gestion-club-13072025'),
                'added'  => __('Licence ajoutée au panier.','plugin-ufsc-gestion-club-13072025'),
                'error'  => __('Une erreur est survenue.','plugin-ufsc-gestion-club-13072025'),
            ],
        ]);
    }
}
UFSC_Fix_Assets::init();
