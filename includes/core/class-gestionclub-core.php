<?php

if (!defined('ABSPATH')) {
    exit; // 🔐 Sécurité : blocage de l'accès direct
}

class UFSC_GestionClub_Core
{
    /**
     * Instance du gestionnaire de clubs
     * @var UFSC_Club_Manager
     */
    private static $club_manager;

    /**
     * Initialisation globale du plugin
     */
    public static function init()
    {
        // 🌍 Chargement de la traduction
        load_plugin_textdomain(
            'ufsc-domain',
            false,
            plugin_basename(dirname(__DIR__, 2)) . '/languages'
        );

        // 🧩 Inclusion des classes
        require_once UFSC_PLUGIN_PATH . 'includes/clubs/class-club-manager.php';
        require_once UFSC_PLUGIN_PATH . 'includes/clubs/admin-club-list-page.php';

        if (!class_exists('UFSC_Club_Manager')) {
            return;
        }

        self::$club_manager = UFSC_Club_Manager::get_instance();

        // ⚙️ Hooks
        // Note: Admin menu is handled by UFSC_Menu class to avoid duplication
        // The register_admin_menu method below is kept for legacy/backup purposes
        // add_action('admin_menu', [self::class, 'register_admin_menu']);
        add_action('init', [self::class, 'register_post_types']);
    }

    /**
     * Création du menu d'administration WordPress
     */
    public static function register_admin_menu()
    {
        // Menu principal
        add_menu_page(
            __('UFSC Clubs', 'plugin-ufsc-gestion-club-13072025'),
            __('UFSC Clubs', 'plugin-ufsc-gestion-club-13072025'),
            'ufsc_manage',
            'ufsc-dashboard',
            [self::class, 'render_admin_page'],
            'dashicons-groups',
            25
        );

        // Sous-menu : Clubs affiliés
        add_submenu_page(
            'ufsc-dashboard',
            __('Clubs affiliés', 'plugin-ufsc-gestion-club-13072025'),
            __('Clubs affiliés', 'plugin-ufsc-gestion-club-13072025'),
            'ufsc_manage',
            'ufsc-clubs',
            [self::class, 'render_club_list_page']
        );

        // Sous-menu : Ajouter un club
        add_submenu_page(
            'ufsc-dashboard',
            __('Nouvelle Affiliation', 'plugin-ufsc-gestion-club-13072025'),
            __('Ajouter un club', 'plugin-ufsc-gestion-club-13072025'),
            'ufsc_manage',
            'ufsc-ajouter-club',
            [self::class, 'render_add_club_page']
        );

        // Note: Licence menu items moved to UFSC_Menu class to avoid duplication

        // Sous-menu : Paramètres
        add_submenu_page(
            'ufsc-dashboard',
            __('Paramètres', 'plugin-ufsc-gestion-club-13072025'),
            __('Paramètres', 'plugin-ufsc-gestion-club-13072025'),
            'ufsc_manage',
            'ufsc-settings',
            [self::class, 'render_settings_page']
        );
    }

    /**
     * Page d'accueil du plugin (dashboard)
     */
    public static function render_admin_page()
    {
        echo '<div class="wrap ufsc-ui"><h1>Tableau de bord UFSC</h1><p>Bienvenue dans le plugin de gestion des clubs UFSC.</p></div>';
    }

    public static function render_club_list_page()
    {
        if (function_exists('ufsc_render_club_list_page')) {
            ufsc_render_club_list_page();
        } else {
            echo '<div class="notice notice-error"><p>⚠️ Fonction ufsc_render_club_list_page() non trouvée.</p></div>';
        }
    }

    public static function render_add_club_page()
    {
        echo '<div class="wrap ufsc-ui"><h1>Ajouter un nouveau club</h1><p>Formulaire à venir...</p></div>';
    }

    public static function render_licence_list_page()
    {
        echo '<div class="wrap ufsc-ui"><h1>Liste des licences</h1><p>Gestion des licences à venir...</p></div>';
    }

    public static function render_add_licence_page()
    {
        echo '<div class="wrap ufsc-ui"><h1>Ajouter une licence</h1><p>Formulaire d’ajout de licence à venir...</p></div>';
    }

    public static function render_settings_page()
    {
        echo '<div class="wrap ufsc-ui"><h1>Paramètres UFSC</h1><p>Configuration du plugin à venir...</p></div>';
    }

    /**
     * Déclaration de CPT si besoin (prévu pour les évolutions)
     */
    public static function register_post_types()
    {
        // Exemple : register_post_type('ufsc_evenement', [...]);
    }

    /**
     * Récupération de l’instance du gestionnaire de clubs
     */
    public static function get_club_manager()
    {
        return self::$club_manager;
    }
}
