<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once dirname(__DIR__) . '/repository/class-licence-repository.php';
require_once dirname(__DIR__) . '/repository/class-club-repository.php';

class UFSC_Licence_Manager
{
    private UFSC_Licence_Repository $licence_repository;
    private UFSC_Club_Repository $club_repository;

    public function __construct()
    {
        $this->licence_repository = new UFSC_Licence_Repository();
        $this->club_repository    = new UFSC_Club_Repository();
    }

    /**
     * ➕ Ajoute une licence, avec le nom du club en "note"
     *
     * @param array $data Données de la licence
     * @return int ID de la licence insérée
     */
    public function add_licence(array $data): int
    {
        $club_name = $this->club_repository->get_name((int) ($data['club_id'] ?? 0));
        $note      = $club_name ?? '';

        $insert = [
            'club_id'                     => (int) ($data['club_id'] ?? 0),
            'nom'                         => sanitize_text_field($data['nom'] ?? ''),
            'prenom'                      => sanitize_text_field($data['prenom'] ?? ''),
            'sexe'                        => in_array($data['sexe'] ?? 'M', ['F', 'M'], true) ? $data['sexe'] : 'M',
            'date_naissance'             => sanitize_text_field($data['date_naissance'] ?? ''),
            'email'                       => sanitize_email($data['email'] ?? ''),
            'adresse'                     => sanitize_text_field($data['adresse'] ?? ''),
            'suite_adresse'              => sanitize_text_field($data['suite_adresse'] ?? ''),
            'code_postal'                => sanitize_text_field($data['code_postal'] ?? ''),
            'ville'                      => sanitize_text_field($data['ville'] ?? ''),
            'tel_fixe'                   => sanitize_text_field($data['tel_fixe'] ?? ''),
            'tel_mobile'                 => sanitize_text_field($data['tel_mobile'] ?? ''),
            'reduction_benevole'         => (int) ($data['reduction_benevole'] ?? 0),
            'reduction_postier'          => (int) ($data['reduction_postier'] ?? 0),
            'identifiant_laposte'        => sanitize_text_field($data['identifiant_laposte'] ?? ''),
            'profession'                 => sanitize_text_field($data['profession'] ?? ''),
            'fonction_publique'          => (int) ($data['fonction_publique'] ?? 0),
            'competition'                => (int) ($data['competition'] ?? 0),
            'licence_delegataire'        => (int) ($data['licence_delegataire'] ?? 0),
            'numero_licence_delegataire' => sanitize_text_field($data['numero_licence_delegataire'] ?? ''),
            'diffusion_image'            => (int) ($data['diffusion_image'] ?? 0),
            'infos_fsasptt'              => (int) ($data['infos_fsasptt'] ?? 0),
            'infos_asptt'                => (int) ($data['infos_asptt'] ?? 0),
            'infos_cr'                   => (int) ($data['infos_cr'] ?? 0),
            'infos_partenaires'          => (int) ($data['infos_partenaires'] ?? 0),
            'honorabilite'               => (int) ($data['honorabilite'] ?? 0),
            'assurance_dommage_corporel' => (int) ($data['assurance_dommage_corporel'] ?? 0),
            'assurance_assistance'       => (int) ($data['assurance_assistance'] ?? 0),
            'note'                       => sanitize_textarea_field($data['note'] ?? $note),
            'region'                     => sanitize_text_field($data['region'] ?? ''),
            'statut'                     => !empty($data['statut']) ? sanitize_text_field($data['statut']) : 'en_attente',
            'is_included'                => !empty($data['is_included']) ? 1 : 0,
            'payment_status'             => sanitize_text_field($data['payment_status'] ?? 'pending'),
            'date_inscription'           => current_time('mysql')
        ];

        $licence_id = $this->licence_repository->insert($insert);

        if ($licence_id > 0) {
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
    public function create_licence(array $data): int|false
    {
        if (empty($data['nom']) || empty($data['prenom']) || empty($data['club_id'])) {
            return false;
        }

        $duplicate_id = $this->check_duplicate_licence($data);
        if ($duplicate_id) {
            return $duplicate_id;
        }

        if (empty($data['statut'])) {
            $data['statut'] = 'en_attente';
        }

        if (!empty($data['is_included']) && (int) $data['is_included'] === 1) {
            if (!$this->club_has_remaining_included_quota((int) $data['club_id'])) {
                $data['is_included'] = 0;
            }
        }

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
    public function check_duplicate_licence(array $data): int|false
    {
        return $this->licence_repository->find_duplicate($data);
    }

    /**
     * 📅 Calculer la catégorie par âge
     *
     * @param string $date_naissance Date de naissance (Y-m-d)
     * @return string Catégorie d'âge
     */
    public function calculate_age_category(?string $date_naissance): string
    {
        if (empty($date_naissance)) {
            return 'Inconnu';
        }

        $birth_date = new DateTime($date_naissance);
        $today      = new DateTime();
        $age        = $today->diff($birth_date)->y;

        if ($age < 18) {
            return 'Moins de 18 ans';
        }
        if ($age < 25) {
            return '18-24 ans';
        }
        if ($age < 35) {
            return '25-34 ans';
        }
        if ($age < 45) {
            return '35-44 ans';
        }
        if ($age < 55) {
            return '45-54 ans';
        }
        return '55 ans et plus';
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
    public function get_licence_by_id(int $id): ?object
    {
        return $this->licence_repository->get_by_id($id);
    }

    /**
     * ✏️ Met à jour une licence
     *
     * @param int $id ID de la licence
     * @param array $data Données de la licence
     * @return bool Succès de la mise à jour
     */
    public function update_licence(int $id, array $data): bool
    {
        $status = $this->licence_repository->get_status($id);
        if ($status === null) {
            return false;
        }

        if ($status === 'validee') {
            $allowed_fields = ['email', 'tel_mobile', 'tel_fixe', 'payment_status'];
            $data          = array_intersect_key($data, array_flip($allowed_fields));

            $update = [];

            if (isset($data['email'])) {
                $update['email'] = sanitize_email($data['email']);
            }
            if (isset($data['tel_mobile'])) {
                $update['tel_mobile'] = sanitize_text_field($data['tel_mobile']);
            }
            if (isset($data['tel_fixe'])) {
                $update['tel_fixe'] = sanitize_text_field($data['tel_fixe']);
            }
            if (isset($data['payment_status'])) {
                $update['payment_status'] = $this->normalize_payment_status($data['payment_status']);
            }

            if (empty($update)) {
                return true;
            }
        } else {
            $payment_status = $this->normalize_payment_status($data['payment_status'] ?? 'pending');

            $update = [
                'nom'                         => sanitize_text_field($data['nom'] ?? ''),
                'prenom'                      => sanitize_text_field($data['prenom'] ?? ''),
                'sexe'                        => in_array($data['sexe'] ?? 'M', ['F', 'M'], true) ? $data['sexe'] : 'M',
                'date_naissance'             => sanitize_text_field($data['date_naissance'] ?? ''),
                'email'                       => sanitize_email($data['email'] ?? ''),
                'adresse'                     => sanitize_text_field($data['adresse'] ?? ''),
                'suite_adresse'              => sanitize_text_field($data['suite_adresse'] ?? ''),
                'code_postal'                => sanitize_text_field($data['code_postal'] ?? ''),
                'ville'                      => sanitize_text_field($data['ville'] ?? ''),
                'tel_fixe'                   => sanitize_text_field($data['tel_fixe'] ?? ''),
                'tel_mobile'                 => sanitize_text_field($data['tel_mobile'] ?? ''),
                'reduction_benevole'         => (int) ($data['reduction_benevole'] ?? 0),
                'reduction_postier'          => (int) ($data['reduction_postier'] ?? 0),
                'identifiant_laposte'        => sanitize_text_field($data['identifiant_laposte'] ?? ''),
                'profession'                 => sanitize_text_field($data['profession'] ?? ''),
                'fonction_publique'          => (int) ($data['fonction_publique'] ?? 0),
                'competition'                => (int) ($data['competition'] ?? 0),
                'licence_delegataire'        => (int) ($data['licence_delegataire'] ?? 0),
                'numero_licence_delegataire' => sanitize_text_field($data['numero_licence_delegataire'] ?? ''),
                'diffusion_image'            => (int) ($data['diffusion_image'] ?? 0),
                'infos_fsasptt'              => (int) ($data['infos_fsasptt'] ?? 0),
                'infos_asptt'                => (int) ($data['infos_asptt'] ?? 0),
                'infos_cr'                   => (int) ($data['infos_cr'] ?? 0),
                'infos_partenaires'          => (int) ($data['infos_partenaires'] ?? 0),
                'honorabilite'               => (int) ($data['honorabilite'] ?? 0),
                'assurance_dommage_corporel' => (int) ($data['assurance_dommage_corporel'] ?? 0),
                'assurance_assistance'       => (int) ($data['assurance_assistance'] ?? 0),
                'payment_status'             => $payment_status,
                'note'                       => sanitize_textarea_field($data['note'] ?? ''),
                'region'                     => sanitize_text_field($data['region'] ?? ''),
                'is_included'                => !empty($data['is_included']) ? 1 : 0,
            ];
        }

        if (isset($data['club_id'])) {
            $update['club_id'] = (int) $data['club_id'];
        }

        return $this->licence_repository->update($id, $update);
    }

    /**
     * 🗑️ Supprime une licence
     *
     * @param int $id ID de la licence
     * @return bool Succès de la suppression
     */
    public function delete_licence(int $id): bool
    {
        return $this->licence_repository->delete($id);
    }

    /**
     * 🔄 Met à jour le statut d'une licence
     *
     * @param int $licence_id ID de la licence
     * @param string $new_status Nouveau statut (pending, active, refused, revoked)
     * @return bool Succès de la mise à jour
     */
    public function update_licence_status(int $licence_id, string $new_status): bool
    {
        $valid_statuses = ['pending', 'active', 'refused', 'revoked', 'draft', 'validated', 'en_attente', 'validee', 'refusee'];

        if (!in_array($new_status, $valid_statuses, true)) {
            return false;
        }

        $update = [
            'statut'            => sanitize_text_field($new_status),
            'date_modification' => current_time('mysql'),
        ];

        return $this->licence_repository->update($licence_id, $update);
    }

    /**
     * 📋 Récupère toutes les licences avec des filtres optionnels
     *
     * @param array $filters Filtres de recherche
     * @return array Liste des licences
     */
    public function get_licences(array $filters = []): array
    {
        return $this->licence_repository->get_all_by_filters($filters);
    }

    /**
     * Normalize payment status values
     *
     * @param string $status Payment status
     * @return string Normalized status
     */
    private function normalize_payment_status(string $status): string
    {
        $status = sanitize_text_field($status);
        if ($status === 'completed') {
            $status = 'paid';
        }
        $allowed = ['pending', 'paid', 'failed', 'refunded', 'included'];
        return in_array($status, $allowed, true) ? $status : 'pending';
    }

    /**
     * ADDED: Vérifier si le club a encore du quota inclus disponible
     *
     * @param int $club_id ID du club
     * @return bool True si du quota est disponible
     */
    private function club_has_remaining_included_quota(int $club_id): bool
    {
        return $this->club_repository->has_remaining_included_quota($club_id);
    }

    /**
     * 🏢 Récupère une instance singleton du gestionnaire de licences
     *
     * @return UFSC_Licence_Manager Instance du gestionnaire
     */
    public static function get_instance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
}
