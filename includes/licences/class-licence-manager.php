<?php

if (!defined('ABSPATH')) {
    exit;
}

class UFSC_Licence_Manager
{
    private $wpdb;
    private $table_licences;
    private $table_clubs;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_licences = $wpdb->prefix . 'ufsc_licences';
        $this->table_clubs = $wpdb->prefix . 'ufsc_clubs';
    }

    /**
     * ➕ Ajoute une licence, avec le nom du club en "note"
     *
     * @param array $data Données de la licence
     * @return int ID de la licence insérée
     */
    public function add_licence($data)
    {
        $club = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT nom FROM {$this->table_clubs} WHERE id = %d", intval($data['club_id']))
        );
        $note = $club ? $club->nom : '';

        $insert = [
            'club_id'                     => intval($data['club_id']),
            'nom'                         => sanitize_text_field($data['nom']),
            'prenom'                      => sanitize_text_field($data['prenom']),
            'sexe'                        => in_array($data['sexe'], ['F','M']) ? $data['sexe'] : 'M',
            'date_naissance'             => sanitize_text_field($data['date_naissance']),
            'email'                       => sanitize_email($data['email']),
            'adresse'                     => sanitize_text_field($data['adresse']),
            'suite_adresse'              => sanitize_text_field($data['suite_adresse']),
            'code_postal'                => sanitize_text_field($data['code_postal']),
            'ville'                      => sanitize_text_field($data['ville']),
            'tel_fixe'                   => sanitize_text_field($data['tel_fixe']),
            'tel_mobile'                 => sanitize_text_field($data['tel_mobile']),
            'reduction_benevole'         => intval($data['reduction_benevole']),
            'reduction_postier'          => intval($data['reduction_postier']),
            'identifiant_laposte'        => sanitize_text_field($data['identifiant_laposte']),
            'profession'                 => sanitize_text_field($data['profession']),
            'fonction_publique'          => intval($data['fonction_publique']),
            'competition'                => intval($data['competition']),
            'licence_delegataire'        => intval($data['licence_delegataire']),
            'numero_licence_delegataire' => sanitize_text_field($data['numero_licence_delegataire']),
            'diffusion_image'            => intval($data['diffusion_image']),
            'infos_fsasptt'              => intval($data['infos_fsasptt']),
            'infos_asptt'                => intval($data['infos_asptt']),
            'infos_cr'                   => intval($data['infos_cr']),
            'infos_partenaires'          => intval($data['infos_partenaires']),
            'honorabilite'               => intval($data['honorabilite']),
            'assurance_dommage_corporel' => intval($data['assurance_dommage_corporel']),
            'assurance_assistance'       => intval($data['assurance_assistance']),
            'note'                       => sanitize_textarea_field($data['note']),
            'region'                     => sanitize_text_field($data['region']),
            'statut'                     => !empty($data['statut']) ? sanitize_text_field($data['statut']) : 'en_attente',
            'is_included'                => !empty($data['is_included']) ? 1 : 0,
            'date_inscription'          => current_time('mysql')
        ];

        $this->wpdb->insert($this->table_licences, $insert);
        $licence_id = $this->wpdb->insert_id;
        
        // Trigger action for licence creation
        if ($licence_id) {
            do_action('ufsc_licence_created', $licence_id, $insert);
        }
        
        return $licence_id;
    }

    /**
     * ➕ Créer une licence avec gestion des statuts
     *
     * @param array $data Données de la licence
     * @return int|false ID de la licence insérée ou false en cas d'erreur
     */
    public function create_licence($data)
    {
        // Validation des champs obligatoires
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['club_id'])) {
            return false;
        }

        // Vérifier les doublons
        $duplicate_id = $this->check_duplicate_licence($data);
        if ($duplicate_id) {
            return $duplicate_id; // Retourner l'ID existant
        }

        // Définir le statut par défaut
        if (empty($data['statut'])) {
            $data['statut'] = 'en_attente'; // Statut par défaut: en attente (standardisé)
        }

        // Vérifier et gérer le quota inclus
        if (!empty($data['is_included']) && $data['is_included'] == 1) {
            if (!$this->club_has_remaining_included_quota($data['club_id'])) {
                // Forcer is_included à 0 si le quota est dépassé
                $data['is_included'] = 0;
            }
        }

        // Calculer la catégorie par âge si la date de naissance est fournie
        if (!empty($data['date_naissance'])) {
            $data['categorie'] = $this->calculate_age_category($data['date_naissance']);
        }

        return $this->add_licence($data);
    }

    /**
     * 🔍 Vérifier les doublons de licence
     *
     * @param array $data Données de la licence
     * @return int|false ID de la licence existante ou false si pas de doublon
     */
    public function check_duplicate_licence($data)
    {
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['date_naissance']) || empty($data['club_id'])) {
            return false;
        }

        $existing_licence = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->table_licences} 
                 WHERE nom = %s AND prenom = %s AND date_naissance = %s AND club_id = %d 
                 AND statut != 'refuse'",
                sanitize_text_field($data['nom']),
                sanitize_text_field($data['prenom']),
                sanitize_text_field($data['date_naissance']),
                intval($data['club_id'])
            )
        );

        return $existing_licence ? $existing_licence->id : false;
    }

    /**
     * 📅 Calculer la catégorie par âge
     *
     * @param string $date_naissance Date de naissance (Y-m-d)
     * @return string Catégorie d'âge
     */
    public function calculate_age_category($date_naissance)
    {
        $birth_date = new DateTime($date_naissance);
        $today = new DateTime();
        $age = $today->diff($birth_date)->y;

        if ($age < 18) {
            return 'Moins de 18 ans';
        } elseif ($age < 25) {
            return '18-24 ans';
        } elseif ($age < 35) {
            return '25-34 ans';
        } elseif ($age < 45) {
            return '35-44 ans';
        } elseif ($age < 55) {
            return '45-54 ans';
        } else {
            return '55 ans et plus';
        }
    }

    /**
     * 🔧 Méthodes pour récupérer, modifier ou supprimer une licence
     */

    /**
     * 📄 Récupère une licence par son ID
     *
     * @param int $id ID de la licence
     * @return object|null Licence trouvée ou null
     */
    public function get_licence_by_id($id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_licences} WHERE id = %d", intval($id))
        );
    }

    /**
     * ✏️ Met à jour une licence
     *
     * @param int $id ID de la licence
     * @param array $data Données de la licence
     * @return bool Succès de la mise à jour
     */
    public function update_licence($id, $data)
    {
        $update = [
            'nom'                         => sanitize_text_field($data['nom']),
            'prenom'                      => sanitize_text_field($data['prenom']),
            'sexe'                        => in_array($data['sexe'], ['F','M']) ? $data['sexe'] : 'M',
            'date_naissance'             => sanitize_text_field($data['date_naissance']),
            'email'                       => sanitize_email($data['email']),
            'adresse'                     => sanitize_text_field($data['adresse']),
            'suite_adresse'              => sanitize_text_field($data['suite_adresse']),
            'code_postal'                => sanitize_text_field($data['code_postal']),
            'ville'                      => sanitize_text_field($data['ville']),
            'tel_fixe'                   => sanitize_text_field($data['tel_fixe']),
            'tel_mobile'                 => sanitize_text_field($data['tel_mobile']),
            'reduction_benevole'         => intval($data['reduction_benevole']),
            'reduction_postier'          => intval($data['reduction_postier']),
            'identifiant_laposte'        => sanitize_text_field($data['identifiant_laposte']),
            'profession'                 => sanitize_text_field($data['profession']),
            'fonction_publique'          => intval($data['fonction_publique']),
            'competition'                => intval($data['competition']),
            'licence_delegataire'        => intval($data['licence_delegataire']),
            'numero_licence_delegataire' => sanitize_text_field($data['numero_licence_delegataire']),
            'diffusion_image'            => intval($data['diffusion_image']),
            'infos_fsasptt'              => intval($data['infos_fsasptt']),
            'infos_asptt'                => intval($data['infos_asptt']),
            'infos_cr'                   => intval($data['infos_cr']),
            'infos_partenaires'          => intval($data['infos_partenaires']),
            'honorabilite'               => intval($data['honorabilite']),
            'assurance_dommage_corporel' => intval($data['assurance_dommage_corporel']),
            'assurance_assistance'       => intval($data['assurance_assistance']),
            'note'                       => sanitize_textarea_field($data['note']),
            'region'                     => sanitize_text_field($data['region']),
            'is_included'                => !empty($data['is_included']) ? 1 : 0,
        ];

        $result = $this->wpdb->update(
            $this->table_licences,
            $update,
            ['id' => intval($id)],
            null,
            ['%d']
        );

        return $result !== false;
    }

    /**
     * 🗑️ Supprime une licence
     *
     * @param int $id ID de la licence
     * @return bool Succès de la suppression
     */
    public function delete_licence($id)
    {
        $result = $this->wpdb->delete(
            $this->table_licences,
            ['id' => intval($id)],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * 🔄 Met à jour le statut d'une licence
     *
     * @param int $licence_id ID de la licence
     * @param string $new_status Nouveau statut (pending, active, refused, revoked)
     * @return bool Succès de la mise à jour
     */
    public function update_licence_status($licence_id, $new_status)
    {
        $valid_statuses = ['pending', 'active', 'refused', 'revoked', 'draft', 'validated', 'en_attente', 'validee', 'refusee'];
        
        if (!in_array($new_status, $valid_statuses)) {
            return false;
        }

        $result = $this->wpdb->update(
            $this->table_licences,
            [
                'statut' => sanitize_text_field($new_status),
                'date_modification' => current_time('mysql')
            ],
            ['id' => intval($licence_id)],
            ['%s', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * 📋 Récupère toutes les licences avec des filtres optionnels
     *
     * @param array $filters Filtres de recherche
     * @return array Liste des licences
     */
    public function get_licences($filters = [])
    {
        if (!isset($filters['club_id']) || intval($filters['club_id']) <= 0) {
            return [];
        }

        $where = ['1=1'];
        $params = [];

        $where[] = 'l.club_id = %d';
        $params[] = intval($filters['club_id']);

        if (!empty($filters['search'])) {
            $where[] = '(l.nom LIKE %s OR l.prenom LIKE %s OR l.email LIKE %s)';
            $search_term = '%' . sanitize_text_field($filters['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }

        $where_clause = implode(' AND ', $where);

        $query = "SELECT l.*, c.nom as club_nom 
                  FROM {$this->table_licences} l
                  LEFT JOIN {$this->table_clubs} c ON l.club_id = c.id
                  WHERE {$where_clause}
                  ORDER BY l.date_inscription DESC";

        if (!empty($params)) {
            return $this->wpdb->get_results($this->wpdb->prepare($query, ...$params));
        } else {
            return $this->wpdb->get_results($query);
        }
    }

    /**
     * ADDED: Vérifier si le club a encore du quota inclus disponible
     *
     * @param int $club_id ID du club
     * @return bool True si du quota est disponible
     */
    private function club_has_remaining_included_quota($club_id) {
        if (!$club_id) {
            return false;
        }
        
        // Utiliser les fonctions helper globales
        if (function_exists('ufsc_has_included_quota')) {
            return ufsc_has_included_quota($club_id);
        }
        
        // Fallback si les helpers ne sont pas disponibles
        global $wpdb;
        $clubs_table = $wpdb->prefix . 'ufsc_clubs';
        $licences_table = $wpdb->prefix . 'ufsc_licences';
        
        $quota = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT quota_licences FROM {$clubs_table} WHERE id = %d",
                $club_id
            )
        );
        
        $used = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$licences_table} WHERE club_id = %d AND is_included = 1",
                $club_id
            )
        );
        
        return (int) $quota > (int) $used;
    }

    /**
     * 🏢 Récupère une instance singleton du gestionnaire de licences
     *
     * @return UFSC_Licence_Manager Instance du gestionnaire
     */
    public static function get_instance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
}
