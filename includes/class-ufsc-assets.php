<?php
if (!defined('ABSPATH')) exit;

class UFSC_Fix_Assets {
    public static function init(){
        add_action('wp_enqueue_scripts', [__CLASS__,'front']);
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
        // Notyf (toast notifications)
        wp_enqueue_style('notyf-css', 'https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css', [], '3');
        wp_enqueue_script('notyf', 'https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js', [], '3', true);

        // jQuery DataTables (for enhanced tables if theme expects it)
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css', [], '1.13.8');
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['jquery'], '1.13.8', true);

        wp_enqueue_style('ufsc-front-fixes', plugins_url('assets/css/ufsc-front.css', dirname(__FILE__)), [], '20.3');
        wp_enqueue_style('ufsc-ui', plugins_url('assets/css/ufsc-ui.css', dirname(__FILE__)), [], '1.0');
        // JS
        $deps = ['jquery'];
        wp_enqueue_script('ufsc-front-fixes', plugins_url('assets/js/ufsc-front.js', dirname(__FILE__)), $deps, '20.3', true);
        wp_localize_script('ufsc-front-fixes','UFSC', [
            // Provide ajax endpoints for scripts
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonces' => [
                'add_licence_to_cart' => wp_create_nonce('ufsc_add_licence_to_cart'),
                'add_to_cart'         => wp_create_nonce('ufsc_add_to_cart'),
                'delete_draft'        => wp_create_nonce('ufsc_delete_licence_draft'),
                'include_quota'       => wp_create_nonce('ufsc_include_quota'),
            ],
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
