<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get available clubs for the dropdown
global $wpdb;
$clubs = $wpdb->get_results("SELECT id, nom FROM {$wpdb->prefix}ufsc_clubs ORDER BY nom ASC");

// Get current values if editing
$current_licence = isset($current_licence) ? $current_licence : null;
$current_club_id = $current_licence ? $current_licence->club_id : (isset($_GET['club_id']) ? intval($_GET['club_id']) : 0);
if (!current_user_can('ufsc_manage')) {
    $current_club_id = (int) get_user_meta(get_current_user_id(), 'ufsc_club_id', true);
}
$current_club_name = '';
foreach ($clubs as $club_tmp) {
    if ((int) $club_tmp->id === (int) $current_club_id) {
        $current_club_name = $club_tmp->nom;
        break;
    }
}
$is_validated = $current_licence && $current_licence->statut === 'validee';

// Load regions helper
require_once plugin_dir_path(__FILE__) . '../../helpers.php';
?>

<div class="ufsc-licence-form">
    <?php if ($is_validated): ?>
    <div class="ufsc-notice ufsc-notice-info">
        <?php _e('Cette licence est validée. Seuls l\'email et les numéros de téléphone peuvent être modifiés.', 'plugin-ufsc-gestion-club-13072025'); ?>
    </div>
    <?php endif; ?>

    <!-- Identity Section -->
    <div class="ufsc-form-section ufsc-section-identity">
        <h3><?php _e('Informations d\'identité', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
        
        <div class="ufsc-form-grid">
            <!-- Club Selection -->
            <div class="ufsc-form-field">
                <label for="club_id" class="required"><?php _e('Club', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <?php if (current_user_can('ufsc_manage')): ?>
                    <select name="club_id" id="club_id" required <?php echo ($current_licence && !is_admin()) ? 'disabled' : ''; ?>>
                        <option value=""><?php _e('Sélectionner un club', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                        <?php foreach ($clubs as $club): ?>
                            <option value="<?php echo esc_attr($club->id); ?>" <?php selected($current_club_id, $club->id); ?>>
                                <?php echo esc_html($club->nom); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($current_licence && !is_admin()): ?>
                        <input type="hidden" name="club_id" value="<?php echo esc_attr($current_licence->club_id); ?>">
                        <span class="help-text"><?php _e('Le club ne peut pas être modifié après création', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span><?php echo esc_html($current_club_name); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- Name -->
            <div class="ufsc-form-field">
                <label for="nom" class="required"><?php _e('Nom', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <input type="text" name="nom" id="nom" value="<?php echo $current_licence ? esc_attr($current_licence->nom) : ''; ?>" required>
            </div>
            
            <!-- First Name -->
            <div class="ufsc-form-field">
                <label for="prenom" class="required"><?php _e('Prénom', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <input type="text" name="prenom" id="prenom" value="<?php echo $current_licence ? esc_attr($current_licence->prenom) : ''; ?>" required>
            </div>
        </div>
        
        <div class="ufsc-form-grid-3">
            <!-- Gender -->
            <div class="ufsc-form-field">
                <label for="sexe" class="required"><?php _e('Sexe', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <select name="sexe" id="sexe" required>
                    <option value="M" <?php echo ($current_licence && $current_licence->sexe === 'M') ? 'selected' : ''; ?>><?php _e('Homme', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                    <option value="F" <?php echo ($current_licence && $current_licence->sexe === 'F') ? 'selected' : ''; ?>><?php _e('Femme', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                </select>
            </div>
            
            <!-- Birth Date -->
            <div class="ufsc-form-field">
                <label for="date_naissance" class="required"><?php _e('Date de naissance', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <input type="date" name="date_naissance" id="date_naissance" value="<?php echo $current_licence ? esc_attr($current_licence->date_naissance) : ''; ?>" required>
            </div>
            
            <!-- Email -->
            <div class="ufsc-form-field">
                <label for="email" class="required"><?php _e('Email', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <input type="email" name="email" id="email" value="<?php echo $current_licence ? esc_attr($current_licence->email) : ''; ?>" required>
                <span class="help-text"><?php _e('Adresse email de contact principale', 'plugin-ufsc-gestion-club-13072025'); ?></span>
            </div>
        </div>
    </div>

    <!-- Contact & Address Section -->
    <div class="ufsc-form-section ufsc-section-contact">
        <h3><?php _e('Coordonnées et adresse', 'plugin-ufsc-gestion-club-13072025'); ?> <span class="ufsc-required-info">*</span></h3>

        <div class="ufsc-form-grid">
            <!-- Address -->
            <div class="ufsc-form-field">
                <label for="adresse" class="required"><?php _e('Adresse', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <input type="text" name="adresse" id="adresse" value="<?php echo $current_licence ? esc_attr($current_licence->adresse) : ''; ?>" required>
            </div>
        </div>

        <div class="ufsc-form-grid-3">
            <!-- Postal Code -->
            <div class="ufsc-form-field">
                <label for="code_postal" class="required"><?php _e('Code postal', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <input type="text" name="code_postal" id="code_postal" value="<?php echo $current_licence ? esc_attr($current_licence->code_postal) : ''; ?>" required pattern="[0-9]{5}" title="Code postal français (5 chiffres)">
            </div>

            <!-- City -->
            <div class="ufsc-form-field">
                <label for="ville" class="required"><?php _e('Ville', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <input type="text" name="ville" id="ville" value="<?php echo $current_licence ? esc_attr($current_licence->ville) : ''; ?>" required>
            </div>

            <!-- Region -->
            <div class="ufsc-form-field">
                <label for="region" class="required"><?php _e('Région', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <select name="region" id="region" required>
                    <option value=""><?php _e('Sélectionner une région', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                    <?php
                    $current_region = $current_licence ? $current_licence->region : '';
                    foreach (ufsc_get_regions() as $region_option): ?>
                        <option value="<?php echo esc_attr($region_option); ?>" <?php selected($current_region, $region_option); ?>>
                            <?php echo esc_html($region_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="ufsc-form-grid-2">
            <!-- Mobile Phone -->
            <div class="ufsc-form-field">
                <label for="tel_mobile" class="required"><?php _e('Téléphone mobile', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                <input type="tel" name="tel_mobile" id="tel_mobile" value="<?php echo $current_licence ? esc_attr($current_licence->tel_mobile) : ''; ?>" required>
                <span class="help-text"><?php _e('Format: 06 12 34 56 78 (au moins un téléphone requis)', 'plugin-ufsc-gestion-club-13072025'); ?></span>
            </div>
        </div>

        <details class="ufsc-optional-fields">
            <summary><?php _e('Champs optionnels', 'plugin-ufsc-gestion-club-13072025'); ?></summary>
            <div class="ufsc-form-grid">
                <!-- Address Complement -->
                <div class="ufsc-form-field">
                    <label for="suite_adresse"><?php _e('Complément d\'adresse', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                    <input type="text" name="suite_adresse" id="suite_adresse" value="<?php echo $current_licence ? esc_attr($current_licence->suite_adresse) : ''; ?>">
                    <span class="help-text"><?php _e('Bâtiment, étage, appartement... (optionnel)', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                </div>

                <!-- Fixed Phone -->
                <div class="ufsc-form-field">
                    <label for="tel_fixe"><?php _e('Téléphone fixe', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                    <input type="tel" name="tel_fixe" id="tel_fixe" value="<?php echo $current_licence ? esc_attr($current_licence->tel_fixe) : ''; ?>">
                    <span class="help-text"><?php _e('Format: 01 23 45 67 89 (optionnel si mobile fourni)', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                </div>
            </div>
        </details>
    </div>

    <!-- Professional Info & Reductions Section -->
    <div class="ufsc-form-section ufsc-section-reductions">
        <h3><?php _e('Informations professionnelles et réductions', 'plugin-ufsc-gestion-club-13072025'); ?></h3>

        <details class="ufsc-optional-fields">
            <summary><?php _e('Champs optionnels', 'plugin-ufsc-gestion-club-13072025'); ?></summary>
            <div class="ufsc-form-grid-2">
                <!-- Profession -->
                <div class="ufsc-form-field">
                    <label for="profession"><?php _e('Profession', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                    <input type="text" name="profession" id="profession" value="<?php echo $current_licence ? esc_attr($current_licence->profession) : ''; ?>">
                </div>

                <!-- La Poste Identifier -->
                <div class="ufsc-form-field">
                    <label for="identifiant_laposte"><?php _e('Identifiant La Poste', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                    <input type="text" name="identifiant_laposte" id="identifiant_laposte" value="<?php echo $current_licence ? esc_attr($current_licence->identifiant_laposte) : ''; ?>">
                    <span class="help-text"><?php _e('Nécessaire pour la réduction postier', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                </div>
            </div>

            <div class="ufsc-checkbox-group">
                <!-- Volunteer Reduction -->
                <div class="ufsc-checkbox-item">
                    <input type="checkbox" name="reduction_benevole" id="reduction_benevole" value="1"
                        <?php echo ($current_licence && $current_licence->reduction_benevole) ? 'checked' : ''; ?>>
                    <label for="reduction_benevole"><?php _e('Réduction bénévole', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                </div>

                <!-- Postal Reduction -->
                <div class="ufsc-checkbox-item">
                    <input type="checkbox" name="reduction_postier" id="reduction_postier" value="1"
                        <?php echo ($current_licence && $current_licence->reduction_postier) ? 'checked' : ''; ?>>
                    <label for="reduction_postier"><?php _e('Réduction postier', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                </div>

                <!-- Public Service -->
                <div class="ufsc-checkbox-item">
                    <input type="checkbox" name="fonction_publique" id="fonction_publique" value="1"
                        <?php echo ($current_licence && $current_licence->fonction_publique) ? 'checked' : ''; ?>>
                    <label for="fonction_publique"><?php _e('Fonction publique', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                </div>
            </div>
        </details>
    </div>

    <!-- Additional Information Section -->
    <div class="ufsc-form-section ufsc-section-info">
        <h3><?php _e('Informations complémentaires', 'plugin-ufsc-gestion-club-13072025'); ?></h3>

        <details class="ufsc-optional-fields">
            <summary><?php _e('Champs optionnels', 'plugin-ufsc-gestion-club-13072025'); ?></summary>
            <div class="ufsc-form-grid-2">
                <!-- Delegataire License Number -->
                <div class="ufsc-form-field">
                    <label for="numero_licence_delegataire"><?php _e('N° licence délégataire', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                    <input type="text" name="numero_licence_delegataire" id="numero_licence_delegataire"
                        value="<?php echo $current_licence ? esc_attr($current_licence->numero_licence_delegataire) : ''; ?>">
                    <span class="help-text"><?php _e('Si vous possédez une licence fédération délégataire', 'plugin-ufsc-gestion-club-13072025'); ?></span>
                </div>

                <div class="ufsc-form-field">
                    <!-- Placeholder for balance -->
                </div>
            </div>

            <div class="ufsc-checkbox-group">
                <!-- Competition -->
                <div class="ufsc-checkbox-item">
                    <input type="checkbox" name="competition" id="competition" value="1"
                        <?php echo ($current_licence && $current_licence->competition) ? 'checked' : ''; ?>>
                    <label for="competition"><?php _e('Participe à des compétitions', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                </div>

                <!-- Delegataire License -->
                <div class="ufsc-checkbox-item">
                    <input type="checkbox" name="licence_delegataire" id="licence_delegataire" value="1"
                        <?php echo ($current_licence && $current_licence->licence_delegataire) ? 'checked' : ''; ?>>
                    <label for="licence_delegataire"><?php _e('Licence fédération délégataire', 'plugin-ufsc-gestion-club-13072025'); ?></label>
                </div>
            </div>
        </details>
    </div>

    <!-- Authorizations & Communications Section -->
    <div class="ufsc-form-section ufsc-section-authorizations">
        <h3><?php _e('Autorisations et communications', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
        
        <div class="ufsc-checkbox-group">
            <!-- Image Rights -->
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="diffusion_image" id="diffusion_image" value="1" 
                    <?php echo ($current_licence && $current_licence->diffusion_image) ? 'checked' : ''; ?>>
                <label for="diffusion_image"><?php _e('Consentement diffusion image', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
            
            <!-- FSASPTT Info -->
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="infos_fsasptt" id="infos_fsasptt" value="1" 
                    <?php echo ($current_licence && $current_licence->infos_fsasptt) ? 'checked' : ''; ?>>
                <label for="infos_fsasptt"><?php _e('Recevoir les infos FSASPTT', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
            
            <!-- ASPTT Info -->
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="infos_asptt" id="infos_asptt" value="1" 
                    <?php echo ($current_licence && $current_licence->infos_asptt) ? 'checked' : ''; ?>>
                <label for="infos_asptt"><?php _e('Recevoir les infos ASPTT', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
            
            <!-- Regional Committee Info -->
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="infos_cr" id="infos_cr" value="1" 
                    <?php echo ($current_licence && $current_licence->infos_cr) ? 'checked' : ''; ?>>
                <label for="infos_cr"><?php _e('Recevoir les infos Comité Régional', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
            
            <!-- Partners Info -->
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="infos_partenaires" id="infos_partenaires" value="1" 
                    <?php echo ($current_licence && $current_licence->infos_partenaires) ? 'checked' : ''; ?>>
                <label for="infos_partenaires"><?php _e('Recevoir les infos partenaires', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
            
            <!-- Honorability -->
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="honorabilite" id="honorabilite" value="1" 
                    <?php echo ($current_licence && $current_licence->honorabilite) ? 'checked' : ''; ?>>
                <label for="honorabilite"><?php _e('Déclaration d\'honorabilité', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
            
            <!-- Body Damage Insurance -->
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="assurance_dommage_corporel" id="assurance_dommage_corporel" value="1" 
                    <?php echo ($current_licence && $current_licence->assurance_dommage_corporel) ? 'checked' : ''; ?>>
                <label for="assurance_dommage_corporel"><?php _e('Assurance dommage corporel', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
            
            <!-- Assistance Insurance -->
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="assurance_assistance" id="assurance_assistance" value="1" 
                    <?php echo ($current_licence && $current_licence->assurance_assistance) ? 'checked' : ''; ?>>
                <label for="assurance_assistance"><?php _e('Assurance assistance', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    <div class="ufsc-form-section ufsc-section-notes">
        <h3><?php _e('Notes et commentaires', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
        
        <div class="ufsc-form-field">
            <label for="note"><?php _e('Note', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            <textarea name="note" id="note" rows="4"><?php echo $current_licence ? esc_textarea($current_licence->note) : ''; ?></textarea>
            <span class="help-text"><?php _e('Informations complémentaires ou remarques particulières', 'plugin-ufsc-gestion-club-13072025'); ?></span>
        </div>
        
        <?php if ($current_licence): ?>
        <div class="ufsc-checkbox-group">
            <div class="ufsc-checkbox-item">
                <input type="checkbox" name="is_included" id="is_included" value="1" 
                    <?php echo ($current_licence && $current_licence->is_included) ? 'checked' : ''; ?>>
                <label for="is_included"><?php _e('Licence incluse dans le quota', 'plugin-ufsc-gestion-club-13072025'); ?></label>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($is_validated): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const allowed = ['email', 'tel_mobile', 'tel_fixe'];
    document.querySelectorAll('.ufsc-licence-form input:not([type="hidden"]), .ufsc-licence-form select, .ufsc-licence-form textarea').forEach(function (el) {
        if (!allowed.includes(el.id)) {
            el.readOnly = true;
            el.disabled = true;
        }
    });
});
</script>
<?php endif; ?>
