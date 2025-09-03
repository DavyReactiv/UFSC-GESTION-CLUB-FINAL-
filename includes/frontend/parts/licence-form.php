<?php
if (!defined('ABSPATH')) {
    exit;
}

// Use standardized frontend access control
$access_check = ufsc_check_frontend_access('licence');

if (!$access_check['allowed']) {
    echo $access_check['error_message'];
    return;
}

$club = $access_check['club'];

// Handle form submission - different actions based on button clicked
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['ufsc_add_licence_nonce'])
    && wp_verify_nonce(wp_unslash($_POST['ufsc_add_licence_nonce']), 'ufsc_add_licence')
) {
    // Determine action based on submitted button
    $action = 'cart';
    if (isset($_POST['ufsc_save_draft'])) {
        $action = 'draft';
    } elseif (isset($_POST['ufsc_set_pending'])) {
        $action = 'pending';
    }
    
    // Validate required fields only for cart action
    if ($action === 'cart') {
        $required_fields = ['nom', 'prenom', 'sexe', 'date_naissance', 'email'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            echo '<div class="ufsc-alert ufsc-alert-error">
                  <h4>Champs obligatoires manquants</h4>
                  <p>Tous les champs marqués d\'un * sont obligatoires pour ajouter au panier.</p>
                  </div>';
            return;
        }
    } else {
        // For draft/pending, only require basic fields
        if (empty($_POST['nom']) || empty($_POST['prenom'])) {
            echo '<div class="ufsc-alert ufsc-alert-error">
                  <h4>Champs minimum requis</h4>
                  <p>Le nom et prénom sont obligatoires pour sauvegarder.</p>
                  </div>';
            return;
        }
    }

    // Prepare license data
    $licence_data = [
        'club_id'        => $club->id,
        'nom'            => sanitize_text_field(wp_unslash($_POST['nom'])),
        'prenom'         => sanitize_text_field(wp_unslash($_POST['prenom'])),
        'sexe'           => (!empty($_POST['sexe']) && wp_unslash($_POST['sexe']) === 'F') ? 'F' : 'M',
        'date_naissance' => sanitize_text_field(wp_unslash($_POST['date_naissance'] ?? '')),
        'email'          => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        
        // Adresse complète
        'adresse'        => sanitize_text_field(wp_unslash($_POST['adresse'] ?? '')),
        'suite_adresse'  => sanitize_text_field(wp_unslash($_POST['suite_adresse'] ?? '')),
        'code_postal'    => sanitize_text_field(wp_unslash($_POST['code_postal'] ?? '')),
        'ville'          => sanitize_text_field(wp_unslash($_POST['ville'] ?? '')),
        
        // Téléphones
        'tel_fixe'       => sanitize_text_field(wp_unslash($_POST['tel_fixe'] ?? '')),
        'tel_mobile'     => sanitize_text_field(wp_unslash($_POST['tel_mobile'] ?? '')),
        
        // Informations supplémentaires
        'profession'     => sanitize_text_field(wp_unslash($_POST['profession'] ?? '')),
        'identifiant_laposte' => sanitize_text_field(wp_unslash($_POST['identifiant_laposte'] ?? '')),
        'region'         => sanitize_text_field(wp_unslash($_POST['region'] ?? '')),
        'numero_licence_delegataire' => sanitize_text_field(wp_unslash($_POST['numero_licence_delegataire'] ?? '')),
        'note'           => sanitize_textarea_field(wp_unslash($_POST['note'] ?? '')),
        'fonction'       => sanitize_text_field(wp_unslash($_POST['fonction'] ?? '')),
    ];
    
    // Options à cocher
    $checkboxes = [
        'reduction_benevole',
        'reduction_postier',
        'fonction_publique',
        'competition',
        'licence_delegataire',
        'diffusion_image',
        'infos_fsasptt',
        'infos_asptt',
        'infos_cr',
        'infos_partenaires',
        'honorabilite',
        'assurance_dommage_corporel',
        'assurance_assistance'
    ];
    
    foreach ($checkboxes as $key) {
        $licence_data[$key] = isset($_POST[$key]) ? 1 : 0;
    }

    // Add license to cart and redirect
    if ($action === 'cart') {
        if (function_exists('wc_get_cart_url')) {
            $product_id = defined('UFSC_LICENCE_PRODUCT_ID') ? UFSC_LICENCE_PRODUCT_ID : 2934;
            $cart_url = add_query_arg([
                'add-to-cart' => $product_id,
                'ufsc_licence_data' => base64_encode(json_encode($licence_data))
            ], wc_get_cart_url());
            
            wp_redirect($cart_url);
            exit;
        } else {
            echo '<div class="ufsc-alert ufsc-alert-error">
                  <h4>Erreur</h4>
                  <p>WooCommerce n\'est pas activé ou disponible. Impossible de procéder à l\'achat.</p>
                  </div>';
            return;
        }
    } else {
        // Save as draft or pending
        require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        $licence_manager = new UFSC_Licence_Manager();
        
        // Set status based on action
        $licence_data['statut'] = ($action === 'draft') ? 'brouillon' : 'en_attente';
        
        // Check quota for pending licenses (drafts don't consume quota)
        if ($action === 'pending') {
            $quota_total = intval($club->quota_licences) > 0 ? intval($club->quota_licences) : 0;
            if ($quota_total > 0) {
                $quota_usage = ufsc_get_quota_usage($club->id);
                if ($quota_usage >= $quota_total) {
                    echo '<div class="ufsc-alert ufsc-alert-error">
                          <h4>Quota épuisé</h4>
                          <p>Votre club a atteint son quota de licences (' . $quota_total . '). Vous pouvez sauvegarder en brouillon ou contacter l\'administration.</p>
                          </div>';
                    return;
                }
            }
        }
        
        $licence_id = $licence_manager->create_licence($licence_data);
        
        if ($licence_id) {
            $status_text = ($action === 'draft') ? 'brouillon' : 'attente';
            $redirect_url = add_query_arg([
                'view' => 'licences',
                'action_result' => 'success',
                'message' => urlencode("Licence mise en {$status_text} avec succès")
            ], get_permalink());
        } else {
            $redirect_url = add_query_arg([
                'view' => 'licence_form',
                'action_result' => 'error',
                'message' => urlencode('Erreur lors de la sauvegarde')
            ], get_permalink());
        }
        
        wp_redirect($redirect_url);
        exit;
    }
}

// Calculate quota information using new helper functions
$quota_total = intval($club->quota_licences) > 0 ? intval($club->quota_licences) : 0;
$licences_count = ufsc_get_quota_usage($club->id); // Use new helper that counts only quota-consuming licenses
$quota_remaining = ufsc_get_quota_remaining($club->id, $quota_total);
$quota_percentage = $quota_total > 0 ? min(100, ($licences_count / $quota_total) * 100) : 0;
?>

<div class="ufsc-licence-form-container">
    <?php 
    // Display action result message if present
    if (isset($_GET['action_result']) && isset($_GET['message'])) {
        $result_status = sanitize_text_field( wp_unslash( $_GET['action_result'] ) );
        $message = sanitize_text_field( urldecode( wp_unslash( $_GET['message'] ) ) );
        
        if ($result_status === 'success') {
            echo '<div class="ufsc-alert ufsc-alert-success">';
            echo '<h4>Opération réussie</h4>';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="ufsc-alert ufsc-alert-error">';
            echo '<h4>Erreur</h4>';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
    }
    ?>
    
    <div class="ufsc-card">
        <div class="ufsc-card-header">
            <h3>Nouvelle licence UFSC</h3>
            <p>Remplissez ce formulaire pour ajouter un nouveau licencié à votre club.</p>
        </div>
        
        <?php if ($quota_total > 0): ?>
        <div class="ufsc-quota-info">
            <div class="ufsc-quota-header">
                <h4>Quota de licences</h4>
                <span class="ufsc-quota-count"><?php echo $licences_count; ?> / <?php echo $quota_total; ?></span>
            </div>
            <div class="ufsc-quota-progress">
                <div class="ufsc-quota-bar" style="width: <?php echo $quota_percentage; ?>%;"></div>
            </div>
            <?php if ($quota_remaining > 0): ?>
                <p class="ufsc-quota-message ufsc-quota-ok">
                    ✅ Licences restantes dans votre quota: <strong><?php echo $quota_remaining; ?></strong>
                </p>
                <p class="ufsc-quota-note">
                    <small>📝 Note: Les brouillons ne consomment pas de quota. Seules les licences "en attente" et "validées" comptent.</small>
                </p>
            <?php else: ?>
                <p class="ufsc-quota-message ufsc-quota-exceeded">
                    ⚠️ Quota épuisé. Vous pouvez créer des brouillons ou contacter l'administration.
                </p>
                <p class="ufsc-quota-note">
                    <small>📝 Note: Les brouillons ne consomment pas de quota et peuvent être créés librement.</small>
                </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="ufsc-card-body">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ufsc-form ufsc-licence-form">
                <div id="ufsc-form-status" class="ufsc-form-status" role="status" aria-live="polite" aria-atomic="true"></div>
                <?php wp_nonce_field('ufsc_add_licence', 'ufsc_add_licence_nonce'); ?>
                <input type="hidden" name="action" value="ufsc_add_licence">
                
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Informations personnelles</h4>
                    
                    <div class="ufsc-form-grid-2">
                        <div class="ufsc-form-field">
                            <label for="nom">Nom <span class="ufsc-form-required">*</span></label>
                            <input type="text" name="nom" id="nom" required class="ufsc-form-input" 
                                   value="<?php echo isset($_POST['nom']) ? esc_attr($_POST['nom']) : ''; ?>">
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label for="prenom">Prénom <span class="ufsc-form-required">*</span></label>
                            <input type="text" name="prenom" id="prenom" required class="ufsc-form-input"
                                   value="<?php echo isset($_POST['prenom']) ? esc_attr($_POST['prenom']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-grid-2">
                        <div class="ufsc-form-field">
                            <label for="sexe">Sexe <span class="ufsc-form-required">*</span></label>
                            <select name="sexe" id="sexe" required class="ufsc-form-select">
                                <option value="">-- Sélectionner --</option>
                                <option value="M" <?php echo (isset($_POST['sexe']) && $_POST['sexe'] === 'M') ? 'selected' : ''; ?>>Homme</option>
                                <option value="F" <?php echo (isset($_POST['sexe']) && $_POST['sexe'] === 'F') ? 'selected' : ''; ?>>Femme</option>
                            </select>
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label for="date_naissance">Date de naissance <span class="ufsc-form-required">*</span></label>
                            <input type="date" name="date_naissance" id="date_naissance" required class="ufsc-form-input"
                                   value="<?php echo isset($_POST['date_naissance']) ? esc_attr($_POST['date_naissance']) : ''; ?>">
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Contact</h4>
                    
                    <div class="ufsc-form-field">
                        <label for="email">Adresse email <span class="ufsc-form-required">*</span></label>
                        <input type="email" name="email" id="email" required class="ufsc-form-input"
                               value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                        <small class="ufsc-form-hint">L'email est obligatoire pour l'envoi de la carte de licence</small>
                    </div>
                    
                    <div class="ufsc-form-grid-2">
                        <div class="ufsc-form-field">
                            <label for="tel_fixe">Téléphone fixe</label>
                            <input type="tel" name="tel_fixe" id="tel_fixe" class="ufsc-form-input"
                                   value="<?php echo isset($_POST['tel_fixe']) ? esc_attr($_POST['tel_fixe']) : ''; ?>"
                                   placeholder="Ex: 01 23 45 67 89">
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label for="tel_mobile">Téléphone mobile</label>
                            <input type="tel" name="tel_mobile" id="tel_mobile" class="ufsc-form-input"
                                   value="<?php echo isset($_POST['tel_mobile']) ? esc_attr($_POST['tel_mobile']) : ''; ?>"
                                   placeholder="Ex: 06 12 34 56 78">
                        </div>
                    </div>
                </div>
                
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Adresse</h4>
                    
                    <div class="ufsc-form-field">
                        <label for="adresse">Adresse</label>
                        <input type="text" name="adresse" id="adresse" class="ufsc-form-input"
                               value="<?php echo isset($_POST['adresse']) ? esc_attr($_POST['adresse']) : ''; ?>"
                               placeholder="Numéro et nom de rue">
                    </div>
                    
                    <div class="ufsc-form-field">
                        <label for="suite_adresse">Complément d'adresse</label>
                        <input type="text" name="suite_adresse" id="suite_adresse" class="ufsc-form-input"
                               value="<?php echo isset($_POST['suite_adresse']) ? esc_attr($_POST['suite_adresse']) : ''; ?>"
                               placeholder="Appartement, étage, bâtiment...">
                    </div>
                    
                    <div class="ufsc-form-grid-2">
                        <div class="ufsc-form-field">
                            <label for="code_postal">Code postal</label>
                            <input type="text" name="code_postal" id="code_postal" class="ufsc-form-input"
                                   value="<?php echo isset($_POST['code_postal']) ? esc_attr($_POST['code_postal']) : ''; ?>"
                                   pattern="[0-9]{5}" maxlength="5" placeholder="75000">
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label for="ville">Ville</label>
                            <input type="text" name="ville" id="ville" class="ufsc-form-input"
                                   value="<?php echo isset($_POST['ville']) ? esc_attr($_POST['ville']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Informations supplémentaires</h4>
                    
                    <div class="ufsc-form-grid-2">
                        <div class="ufsc-form-field">
                            <label for="profession">Profession</label>
                            <input type="text" name="profession" id="profession" class="ufsc-form-input"
                                   value="<?php echo isset($_POST['profession']) ? esc_attr($_POST['profession']) : ''; ?>">
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label for="identifiant_laposte">Identifiant La Poste</label>
                            <input type="text" name="identifiant_laposte" id="identifiant_laposte" class="ufsc-form-input"
                                   value="<?php echo isset($_POST['identifiant_laposte']) ? esc_attr($_POST['identifiant_laposte']) : ''; ?>">
                            <input type="hidden" name="identifiant_laposte_flag" value="<?php echo !empty($_POST['identifiant_laposte_flag']) ? '1' : '0'; ?>">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-field">
                        <label for="region">Région</label>
                        <select name="region" id="region" class="ufsc-form-select">
                            <option value="">-- Choisir une région --</option>
                            <?php
                            $regions = require plugin_dir_path(__FILE__) . '../../../data/regions.php';
                            foreach ($regions as $region) {
                                $selected = (isset($_POST['region']) && $_POST['region'] === $region) ? 'selected' : '';
                                echo '<option value="' . esc_attr($region) . '" ' . $selected . '>' . esc_html($region) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Réductions et statuts</h4>
                    
                    <div class="ufsc-form-grid-2">
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="reduction_benevole" id="reduction_benevole" value="1" 
                                       <?php echo isset($_POST['reduction_benevole']) ? 'checked' : ''; ?>>
                                Réduction bénévole
                            </label>
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="reduction_postier" id="reduction_postier" value="1"
                                       <?php echo isset($_POST['reduction_postier']) ? 'checked' : ''; ?>>
                                Réduction postier
                            </label>
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="fonction_publique" id="fonction_publique" value="1"
                                       <?php echo isset($_POST['fonction_publique']) ? 'checked' : ''; ?>>
                                Fonction publique
                            </label>
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="competition" id="competition" value="1"
                                       <?php echo isset($_POST['competition']) ? 'checked' : ''; ?>>
                                Participe à des compétitions
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Licences et autorisations</h4>
                    
                    <div class="ufsc-form-field">
                        <label>
                            <input type="checkbox" name="licence_delegataire" id="licence_delegataire" value="1"
                                   <?php echo isset($_POST['licence_delegataire']) ? 'checked' : ''; ?>>
                            Licence fédération délégataire
                        </label>
                    </div>
                    
                    <div class="ufsc-form-field">
                        <label for="numero_licence_delegataire">N° licence délégataire</label>
                        <input type="text" name="numero_licence_delegataire" id="numero_licence_delegataire" class="ufsc-form-input"
                               value="<?php echo isset($_POST['numero_licence_delegataire']) ? esc_attr($_POST['numero_licence_delegataire']) : ''; ?>">
                    </div>
                    
                    <div class="ufsc-form-field">
                        <label>
                            <input type="checkbox" name="diffusion_image" id="diffusion_image" value="1"
                                   <?php echo isset($_POST['diffusion_image']) ? 'checked' : ''; ?>>
                            Consentement diffusion image
                        </label>
                    </div>
                </div>
                
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Communications</h4>
                    
                    <div class="ufsc-form-grid-2">
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="infos_fsasptt" id="infos_fsasptt" value="1"
                                       <?php echo isset($_POST['infos_fsasptt']) ? 'checked' : ''; ?>>
                                Infos FSASPTT
                            </label>
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="infos_asptt" id="infos_asptt" value="1"
                                       <?php echo isset($_POST['infos_asptt']) ? 'checked' : ''; ?>>
                                Infos ASPTT
                            </label>
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="infos_cr" id="infos_cr" value="1"
                                       <?php echo isset($_POST['infos_cr']) ? 'checked' : ''; ?>>
                                Infos Comité Régional
                            </label>
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="infos_partenaires" id="infos_partenaires" value="1"
                                       <?php echo isset($_POST['infos_partenaires']) ? 'checked' : ''; ?>>
                                Infos partenaires
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Déclarations et assurances</h4>
                    
                    <div class="ufsc-form-field">
                        <label>
                            <input type="checkbox" name="honorabilite" id="honorabilite" value="1"
                                   <?php echo isset($_POST['honorabilite']) ? 'checked' : ''; ?>>
                            Déclaration honorabilité
                        </label>
                    </div>
                    
                    <div class="ufsc-form-grid-2">
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="assurance_dommage_corporel" id="assurance_dommage_corporel" value="1"
                                       <?php echo isset($_POST['assurance_dommage_corporel']) ? 'checked' : ''; ?>>
                                Assurance dommage corporel
                            </label>
                        </div>
                        
                        <div class="ufsc-form-field">
                            <label>
                                <input type="checkbox" name="assurance_assistance" id="assurance_assistance" value="1"
                                       <?php echo isset($_POST['assurance_assistance']) ? 'checked' : ''; ?>>
                                Assurance assistance
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="ufsc-form-section">
                    <h4 class="ufsc-form-section-title">Fonction au club</h4>
                    
                    <div class="ufsc-form-field">
                        <label for="fonction">Fonction</label>
                        <select name="fonction" id="fonction" class="ufsc-form-select">
                            <option value="">-- Sélectionner --</option>
                            <option value="Dirigeant" <?php echo (isset($_POST['fonction']) && $_POST['fonction'] === 'Dirigeant') ? 'selected' : ''; ?>>Dirigeant</option>
                            <option value="Entraîneur" <?php echo (isset($_POST['fonction']) && $_POST['fonction'] === 'Entraîneur') ? 'selected' : ''; ?>>Entraîneur</option>
                            <option value="Compétiteur" <?php echo (isset($_POST['fonction']) && $_POST['fonction'] === 'Compétiteur') ? 'selected' : ''; ?>>Compétiteur</option>
                            <option value="Loisir" <?php echo (isset($_POST['fonction']) && $_POST['fonction'] === 'Loisir') ? 'selected' : ''; ?>>Pratiquant loisir</option>
                            <option value="Arbitre" <?php echo (isset($_POST['fonction']) && $_POST['fonction'] === 'Arbitre') ? 'selected' : ''; ?>>Arbitre</option>
                            <option value="Autre" <?php echo (isset($_POST['fonction']) && $_POST['fonction'] === 'Autre') ? 'selected' : ''; ?>>Autre</option>
                        </select>
                    </div>
                    
                    <div class="ufsc-form-field">
                        <label for="note">Note</label>
                        <textarea name="note" id="note" rows="4" class="ufsc-form-textarea"
                                  placeholder="Informations complémentaires..."><?php echo isset($_POST['note']) ? esc_textarea($_POST['note']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="ufsc-form-actions">
                    <div class="ufsc-form-actions-primary">
                        <button type="submit" name="ufsc_add_licence" class="ufsc-btn ufsc-btn-red ufsc-btn-large">
                            <i class="dashicons dashicons-cart"></i>
                            Ajouter au panier
                        </button>
                    </div>
                    
                    <div class="ufsc-form-actions-secondary">
                        <button type="button" id="ufsc-save-draft" name="ufsc_save_draft" class="ufsc-btn ufsc-btn-outline ufsc-btn-medium ufsc-btn-save-draft">
                            <i class="dashicons dashicons-edit"></i>
                            Mettre en brouillon
                        </button>
                        <button type="submit" name="ufsc_set_pending" class="ufsc-btn ufsc-btn-outline ufsc-btn-medium">
                            <i class="dashicons dashicons-clock"></i>
                            Mettre en attente
                        </button>
                        <a href="<?php echo esc_url(add_query_arg(['view' => 'licences'], get_permalink())); ?>" 
                           class="ufsc-btn ufsc-btn-ghost">
                            Annuler
                        </a>
                    </div>
                </div>
                
                <div class="ufsc-form-note">
                    <p><small>
                        <i class="dashicons dashicons-info"></i>
                        En cliquant sur "Ajouter au panier", vous serez redirigé vers le panier pour finaliser votre commande.
                        <?php if ($quota_remaining > 0): ?>
                            Cette licence sera incluse dans votre quota.
                        <?php else: ?>
                            Cette licence sera facturée au tarif normal.
                        <?php endif; ?>
                    </small></p>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.ufsc-licence-form');
    if (!form) return;
    const statusEl = document.getElementById('ufsc-form-status');
    const controls = form.querySelectorAll('input, select, textarea, button');
    const setState = (state, message = '') => {
        form.dataset.state = state;
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.setAttribute('data-state', state);
            statusEl.setAttribute('aria-live', state === 'error' ? 'assertive' : 'polite');
        }
        if (state === 'loading' || state === 'disabled') {
            controls.forEach(el => el.setAttribute('disabled', 'disabled'));
        } else {
            controls.forEach(el => el.removeAttribute('disabled'));
        }
    };
    setState('idle');
    const quotaExceeded = document.querySelector('.ufsc-quota-message.ufsc-quota-exceeded');
    if (quotaExceeded) {
        setState('disabled', quotaExceeded.textContent.trim());
        return;
    }
    form.addEventListener('submit', function () {
        setState('loading', 'Envoi en cours...');
    });
    const success = document.querySelector('.ufsc-alert-success');
    const error = document.querySelector('.ufsc-alert-error');
    if (success) {
        setState('success', success.querySelector('p')?.textContent.trim() || '');
    }
    if (error) {
        setState('error', error.querySelector('p')?.textContent.trim() || '');
    }
});
</script>
