<?php if (!defined('ABSPATH')) {
    exit;
}; ?>

<table class="form-table">
    <tr><th><label for="nom">Nom</label></th>
        <td><input type="text" name="nom" value="<?php echo isset($current_licence) ? esc_attr($current_licence->nom) : ''; ?>" required class="regular-text"></td></tr>

    <tr><th><label for="prenom">Prénom</label></th>
        <td><input type="text" name="prenom" value="<?php echo isset($current_licence) ? esc_attr($current_licence->prenom) : ''; ?>" required class="regular-text"></td></tr>

    <tr><th><label for="sexe">Sexe</label></th>
        <td>
            <select name="sexe" required>
                <option value="M" <?php echo (isset($current_licence) && $current_licence->sexe === 'M') ? 'selected' : ''; ?>>Homme</option>
                <option value="F" <?php echo (isset($current_licence) && $current_licence->sexe === 'F') ? 'selected' : ''; ?>>Femme</option>
            </select>
        </td>
    </tr>

    <tr><th><label for="date_naissance">Date de naissance</label></th>
        <td><input type="date" name="date_naissance" value="<?php echo isset($current_licence) ? esc_attr($current_licence->date_naissance) : ''; ?>" required></td></tr>

    <tr><th><label for="email">Email</label></th>
        <td><input type="email" name="email" value="<?php echo isset($current_licence) ? esc_attr($current_licence->email) : ''; ?>" required class="regular-text"></td></tr>

    <tr><th><label for="adresse">Adresse</label></th>
        <td><input type="text" name="adresse" value="<?php echo isset($current_licence) ? esc_attr($current_licence->adresse) : ''; ?>" class="regular-text"></td></tr>

    <tr><th><label for="suite_adresse">Complément d'adresse</label></th>
        <td><input type="text" name="suite_adresse" value="<?php echo isset($current_licence) ? esc_attr($current_licence->suite_adresse) : ''; ?>" class="regular-text"></td></tr>

    <tr><th><label for="code_postal">Code postal</label></th>
        <td><input type="text" name="code_postal" value="<?php echo isset($current_licence) ? esc_attr($current_licence->code_postal) : ''; ?>" class="regular-text"></td></tr>

    <tr><th><label for="ville">Ville</label></th>
        <td><input type="text" name="ville" value="<?php echo isset($current_licence) ? esc_attr($current_licence->ville) : ''; ?>" class="regular-text"></td></tr>

    <tr><th><label for="tel_fixe">Téléphone fixe</label></th>
        <td><input type="text" name="tel_fixe" value="<?php echo isset($current_licence) ? esc_attr($current_licence->tel_fixe) : ''; ?>" class="regular-text"></td></tr>

    <tr><th><label for="tel_mobile">Téléphone mobile</label></th>
        <td><input type="text" name="tel_mobile" value="<?php echo isset($current_licence) ? esc_attr($current_licence->tel_mobile) : ''; ?>" class="regular-text"></td></tr>

    <tr><th><label for="profession">Profession</label></th>
        <td><input type="text" name="profession" value="<?php echo isset($current_licence) ? esc_attr($current_licence->profession) : ''; ?>" class="regular-text"></td></tr>

    <tr><th><label for="identifiant_laposte">Identifiant La Poste</label></th>
        <td>
            <input type="text" name="identifiant_laposte" value="<?php echo isset($current_licence) ? esc_attr($current_licence->identifiant_laposte) : ''; ?>" class="regular-text">
            <input type="hidden" name="identifiant_laposte_flag" value="<?php echo !empty($current_licence->identifiant_laposte_flag) ? '1' : '0'; ?>">
        </td></tr>

    <tr><th><label for="region">Région</label></th>
        <td>
            <select name="region" class="regular-text">
                <option value="">Sélectionner une région</option>
                <?php 
                require_once plugin_dir_path(__FILE__) . '../../helpers.php';
                $current_region = isset($current_licence) ? $current_licence->region : '';
                foreach (ufsc_get_regions() as $region_option): ?>
                    <option value="<?php echo esc_attr($region_option); ?>" <?php selected($current_region, $region_option); ?>><?php echo esc_html($region_option); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>

    <!-- ✅ Options Oui/Non -->
    <?php
    $yesno_fields = [
        'reduction_benevole' => 'Réduction bénévole',
        'reduction_postier' => 'Réduction postier',
        'fonction_publique' => 'Fonction publique',
        'competition' => 'Participe à des compétitions',
        'licence_delegataire' => 'Licence fédération délégataire',
        'diffusion_image' => 'Consentement diffusion image',
        'infos_fsasptt' => 'Infos FSASPTT',
        'infos_asptt' => 'Infos ASPTT',
        'infos_cr' => 'Infos Comité Régional',
        'infos_partenaires' => 'Infos partenaires',
        'honorabilite' => 'Déclaration honorabilité',
        'assurance_dommage_corporel' => 'Assurance dommage corporel',
        'assurance_assistance' => 'Assurance assistance',
    ];

    // Get current values if editing (passed from parent form)
    $current_licence = isset($current_licence) ? $current_licence : null;

foreach ($yesno_fields as $key => $label):
    $current_value = $current_licence ? (isset($current_licence->$key) ? $current_licence->$key : 0) : 0;
?>
        <tr>
            <th><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <select name="<?php echo esc_attr($key); ?>" id="<?php echo esc_attr($key); ?>">
                    <option value="0" <?php selected($current_value, 0); ?>>Non</option>
                    <option value="1" <?php selected($current_value, 1); ?>>Oui</option>
                </select>
            </td>
        </tr>
    <?php endforeach; ?>

    <tr><th><label for="numero_licence_delegataire">N° licence délégataire</label></th>
        <td><input type="text" name="numero_licence_delegataire" value="<?php echo isset($current_licence) ? esc_attr($current_licence->numero_licence_delegataire) : ''; ?>" class="regular-text"></td></tr>

    <tr><th><label for="note">Note</label></th>
        <td><textarea name="note" rows="4" class="large-text"><?php echo isset($current_licence) ? esc_textarea($current_licence->note) : ''; ?></textarea></td></tr>
</table>
