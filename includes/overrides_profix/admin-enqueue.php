<?php
if (!defined('ABSPATH')) exit;
add_action('admin_enqueue_scripts',function(){
    wp_register_script('ufsc-admin-profix',plugins_url('../../assets/js/ufsc-admin-profix.js',__FILE__),array('jquery'),'20.6.0',true);
    wp_localize_script('ufsc-admin-profix','UFSC_ADMIN',array('ajaxUrl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('ufsc_admin_licence_action')));
    wp_enqueue_script('ufsc-admin-profix');
    wp_enqueue_style('ufsc-admin-ui',plugins_url('../../assets/css/ufsc-admin-ui.css',__FILE__),[], '1.0.0');
    wp_enqueue_script('ufsc-admin-ui',plugins_url('../../assets/js/ufsc-admin-ui.js',__FILE__),[], '1.0.0',true);
},20);