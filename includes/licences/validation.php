<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validate licence data for required fields.
 *
 * @param array $data Input data (sanitized).
 * @param bool  $contact_only Validate only email and phone (for limited updates).
 * @return array List of error messages (empty if valid).
 */
function ufsc_validate_licence_data(array $data, bool $contact_only = false): array {
    $errors = [];

    if ($contact_only) {
        if (empty($data['email']) || !is_email($data['email'])) {
            $errors[] = __('Un email valide est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
        }
        if (empty($data['tel_mobile']) && empty($data['tel_fixe'])) {
            $errors[] = __('Un numéro de téléphone mobile ou fixe est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
        }
        return $errors;
    }

    $required = [
        'nom'            => __('Le nom est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
        'prenom'         => __('Le prénom est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
        'email'          => __('Un email valide est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
        'date_naissance' => __('La date de naissance est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
        'club_id'        => __('Le club est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
        'adresse'        => __('L\'adresse est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
        'code_postal'    => __('Le code postal est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
        'ville'          => __('La ville est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
        'region'         => __('La région est obligatoire.', 'plugin-ufsc-gestion-club-13072025'),
    ];

    foreach ($required as $field => $message) {
        if (empty($data[$field])) {
            $errors[] = $message;
        }
    }

    if (!empty($data['email']) && !is_email($data['email'])) {
        $errors[] = __('Un email valide est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }

    if (empty($data['tel_mobile']) && empty($data['tel_fixe'])) {
        $errors[] = __('Un numéro de téléphone mobile ou fixe est obligatoire.', 'plugin-ufsc-gestion-club-13072025');
    }

    $consents = [
        'honorabilite'               => __('Vous devez certifier votre honorabilité.', 'plugin-ufsc-gestion-club-13072025'),
        'assurance_dommage_corporel' => __('Vous devez accepter l\'assurance dommage corporel.', 'plugin-ufsc-gestion-club-13072025'),
        'assurance_assistance'       => __('Vous devez accepter l\'assurance assistance.', 'plugin-ufsc-gestion-club-13072025'),
    ];

    foreach ($consents as $field => $message) {
        if (empty($data[$field])) {
            $errors[] = $message;
        }
    }

    return $errors;
}
