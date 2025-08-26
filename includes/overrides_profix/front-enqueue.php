<?php
if (!defined('ABSPATH')) exit;
add_action('wp_enqueue_scripts', function(){
    wp_register_script('ufsc-front-profix', plugins_url('../../assets/js/ufsc-front-profix.js', __FILE__), array('jquery'), '20.6.0', true);
    wp_localize_script('ufsc-front-profix','UFSC_PRO',array(
        'ajaxUrl'=>admin_url('admin-ajax.php'),
        'nonce'=>wp_create_nonce('ufsc_front_nonce'),
        'i18n'=>array('saved'=>__('Brouillon enregistré.','plugin-ufsc-gestion-club-13072025'),'deleted'=>__('Brouillon supprimé.','plugin-ufsc-gestion-club-13072025'),'error'=>__('Une erreur est survenue.','plugin-ufsc-gestion-club-13072025'))
    ));
    wp_enqueue_script('ufsc-front-profix');
    wp_enqueue_style('ufsc-frontend', plugins_url('../../assets/css/ufsc-frontend.css', __FILE__), [], '1.0');
},20);