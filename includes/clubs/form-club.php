<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Formulaire de club réutilisable avec design amélioré
 *
 * @param int $club_id ID du club (0 pour nouveau club)
 * @param bool $is_frontend Si le formulaire est affiché en frontend
 * @param bool $is_affiliation Si le formulaire est utilisé pour une affiliation
 */
function ufsc_render_club_form($club_id = 0, $is_frontend = false, $is_affiliation = false)
{
    // Détecter si nous sommes en frontend
    $is_frontend = $is_frontend || !is_admin();

    // Récupérer le club si en mode édition
    $club = null;
    if ($club_id > 0) {
        $club_manager = UFSC_Club_Manager::get_instance();
        $club = $club_manager->get_club($club_id);
    }

    $is_edit = isset($club);
    $regions = require plugin_dir_path(__FILE__) . '../../data/regions.php';
    $statuts = ['Actif', 'Inactif', 'En cours de validation', 'Archivé', 'Refusé'];
    $docs = [
        'statuts' => "Statuts du club",
        'recepisse' => "Récépissé de déclaration",
        'jo' => "Parution au JO",
        'pv_ag' => "Dernier PV d'AG",
        'cer' => "Contrat d'engagement républicain",
        'attestation_cer' => "Attestation liée au CER"
    ];
    $roles = [
        'president' => 'Président',
        'secretaire' => 'Secrétaire',
        'tresorier' => 'Trésorier',
        'entraineur' => 'Entraîneur (facultatif)'
    ];

    // Traitement du formulaire
    $form_submitted = false;
    $errors = [];
    $success = false;

    if (isset($_POST['ufsc_save_club_submit']) && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $form_submitted = true;

        // Vérification du nonce
        if (!isset($_POST['ufsc_club_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ufsc_club_nonce']), 'ufsc_save_club')) {
            $errors[] = 'Erreur de sécurité. Veuillez recharger la page.';
        } else {
            // Récupération des données de base
            $club_data = [
                'nom' => isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '',
                'region' => isset($_POST['region']) ? sanitize_text_field(wp_unslash($_POST['region'])) : '',
                'adresse' => isset($_POST['adresse']) ? sanitize_textarea_field(wp_unslash($_POST['adresse'])) : '',
                'complement_adresse' => isset($_POST['complement_adresse']) ? sanitize_text_field(wp_unslash($_POST['complement_adresse'])) : '',
                'code_postal' => isset($_POST['code_postal']) ? sanitize_text_field(wp_unslash($_POST['code_postal'])) : '',
                'ville' => isset($_POST['ville']) ? sanitize_text_field(wp_unslash($_POST['ville'])) : '',
                'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
                'telephone' => isset($_POST['telephone']) ? sanitize_text_field(wp_unslash($_POST['telephone'])) : '',
                'type' => isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '',
                'statut' => $is_frontend ? 'En cours de création' : (isset($_POST['statut']) ? sanitize_text_field(wp_unslash($_POST['statut'])) : 'En cours de création'),
                'url_site' => isset($_POST['url_site']) ? esc_url_raw(wp_unslash($_POST['url_site'])) : '',
                'url_facebook' => isset($_POST['url_facebook']) ? esc_url_raw(wp_unslash($_POST['url_facebook'])) : '',
                'url_instagram' => isset($_POST['url_instagram']) ? esc_url_raw(wp_unslash($_POST['url_instagram'])) : '',
                'siren' => isset($_POST['siren']) ? sanitize_text_field(wp_unslash($_POST['siren'])) : '',
                'rna_number' => isset($_POST['rna_number']) ? sanitize_text_field(wp_unslash($_POST['rna_number'])) : '',
                'iban' => isset($_POST['iban']) ? sanitize_text_field(wp_unslash($_POST['iban'])) : '',
                'ape' => isset($_POST['ape']) ? sanitize_text_field(wp_unslash($_POST['ape'])) : '',
                'ccn' => isset($_POST['ccn']) ? sanitize_text_field(wp_unslash($_POST['ccn'])) : '',
                'ancv' => isset($_POST['ancv']) ? sanitize_text_field(wp_unslash($_POST['ancv'])) : '',
                'num_declaration' => isset($_POST['num_declaration']) ? sanitize_text_field(wp_unslash($_POST['num_declaration'])) : '',
                'date_declaration' => isset($_POST['date_declaration']) ? sanitize_text_field(wp_unslash($_POST['date_declaration'])) : '',
                'responsable_id' => ufsc_handle_frontend_user_association($is_frontend),
            ];

            // Ajouter les données des dirigeants
            foreach ($roles as $key => $label) {
                $club_data["{$key}_prenom"] = isset($_POST["{$key}_prenom"]) ? sanitize_text_field(wp_unslash($_POST["{$key}_prenom"])) : '';
                $club_data["{$key}_nom"] = isset($_POST["{$key}_nom"]) ? sanitize_text_field(wp_unslash($_POST["{$key}_nom"])) : '';
                $club_data["{$key}_tel"] = isset($_POST["{$key}_tel"]) ? sanitize_text_field(wp_unslash($_POST["{$key}_tel"])) : '';
                $club_data["{$key}_email"] = isset($_POST["{$key}_email"]) ? sanitize_email(wp_unslash($_POST["{$key}_email"])) : '';
            }

            // Ajouter les champs admin uniquement si pas en frontend
            if (!$is_frontend) {
                $club_data['num_affiliation'] = isset($_POST['num_affiliation']) ? sanitize_text_field(wp_unslash($_POST['num_affiliation'])) : '';
                $club_data['quota_licences'] = isset($_POST['quota_licences']) ? intval(wp_unslash($_POST['quota_licences'])) : 0;
            }

            // Validation
            if (empty($club_data['nom'])) {
                $errors[] = 'Le nom du club est obligatoire.';
            }
            if (empty($club_data['region'])) {
                $errors[] = 'La région est obligatoire.';
            }
            if (empty($club_data['adresse'])) {
                $errors[] = 'L\'adresse du club est obligatoire.';
            }
            if (empty($club_data['code_postal'])) {
                $errors[] = 'Le code postal est obligatoire.';
            }
            if (empty($club_data['ville'])) {
                $errors[] = 'La ville est obligatoire.';
            }
            if (empty($club_data['email'])) {
                $errors[] = 'L\'email du club est obligatoire.';
            }
            if (empty($club_data['telephone'])) {
                $errors[] = 'Le téléphone du club est obligatoire.';
            }
            if (empty($club_data['num_declaration'])) {
                $errors[] = 'Le numéro de déclaration est obligatoire.';
            }
            if (empty($club_data['date_declaration'])) {
                $errors[] = 'La date de déclaration est obligatoire.';
            }

            // Validation des dirigeants - tous les champs obligatoires (prénom, nom, email, téléphone)
            $dirigeant_roles = ['president', 'secretaire', 'tresorier']; // Entraîneur reste facultatif
            foreach ($dirigeant_roles as $role) {
                if (empty($club_data["{$role}_prenom"])) {
                    $errors[] = "Le prénom du {$role} est obligatoire.";
                }
                if (empty($club_data["{$role}_nom"])) {
                    $errors[] = "Le nom du {$role} est obligatoire.";
                }
                if (empty($club_data["{$role}_email"])) {
                    $errors[] = "L'email du {$role} est obligatoire.";
                }
                if (empty($club_data["{$role}_tel"])) {
                    $errors[] = "Le téléphone du {$role} est obligatoire.";
                }
            }

            // Vérification des documents obligatoires pour l'affiliation (si affiliation)
            if ($is_affiliation) {
                $required_docs = ['statuts', 'recepisse', 'cer'];
                foreach ($required_docs as $doc) {
                    if (empty($club->{$doc}) && empty($_FILES[$doc]['name'])) {
                        $errors[] = 'Le document "' . $docs[$doc] . '" est obligatoire pour l\'affiliation.';
                    }
                }
            }

            // Si pas d'erreurs, enregistrer le club
            if (empty($errors)) {
                $club_manager = UFSC_Club_Manager::get_instance();

                if ($is_edit) {
                    $result = $club_manager->update_club($club_id, $club_data);
                } else {
                    $club_data['date_creation'] = current_time('mysql');
                    $result = $club_manager->add_club($club_data);
                    $club_id = $result; // Récupère l'ID du nouveau club
                }

                if ($result) {
                    $success = true;

                    // Traitement des fichiers
                    if (!empty($_FILES)) {
                        // Traitement du logo
                        if (!empty($_FILES['logo_upload']['name'])) {
                            $logo_file = $_FILES['logo_upload'];
                            
                            // Vérification du type de fichier pour le logo
                            $allowed_logo_types = ['image/jpeg', 'image/png', 'image/gif'];
                            if (in_array($logo_file['type'], $allowed_logo_types) && $logo_file['size'] <= 2097152) { // 2MB max
                                if (!function_exists('wp_handle_upload')) {
                                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                                }

                                $upload_overrides = array('test_form' => false);
                                $movefile = wp_handle_upload($logo_file, $upload_overrides);

                                if ($movefile && !isset($movefile['error'])) {
                                    // Mise à jour du logo dans la base de données
                                    $club_manager->update_club_field($club_id, 'logo_url', $movefile['url']);
                                }
                            }
                        }

                        // Traitement des documents
                        foreach ($docs as $key => $label) {
                            if (!empty($_FILES[$key]['name'])) {
                                $file = $_FILES[$key];

                                // Vérification du type de fichier
                                $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
                                if (!in_array($file['type'], $allowed_types)) {
                                    continue; // Ignorer les fichiers non autorisés
                                }

                                // Utiliser WordPress pour gérer l'upload
                                if (!function_exists('wp_handle_upload')) {
                                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                                }

                                // Upload via WordPress
                                $upload_overrides = array('test_form' => false);
                                $movefile = wp_handle_upload($file, $upload_overrides);

                                if ($movefile && !isset($movefile['error'])) {
                                    // Fichier uploadé avec succès
                                    $doc_url = $movefile['url'];
                                    if (method_exists($club_manager, 'update_club_document')) {
                                        $club_manager->update_club_document($club_id, $key, $doc_url);
                                    }
                                }
                            }
                        }
                    }

                    // Redirection après création en frontend pour une affiliation
                    if ($is_frontend && $is_affiliation) {
                        $product_id = ufsc_get_affiliation_product_id_safe();
                        $product    = wc_get_product($product_id);

                        if ($product) {
                            wp_safe_redirect(get_permalink($product_id));
                            exit;
                        }

                        $success = false;
                        $errors[] = "Produit d'affiliation introuvable. Veuillez contacter l'administrateur.";
                    }

                    // Notification admin si frontend sans affiliation
                    if ($is_frontend && !$is_affiliation) {
                        $admin_email = get_option('admin_email');
                        $subject = 'Nouveau club UFSC';
                        $message = "Un nouveau club a été créé sur votre site.\n\n";
                        $message .= "Nom: {$club_data['nom']}\n";
                        $message .= "Email: {$club_data['email']}\n";
                        $message .= "Téléphone: {$club_data['telephone']}\n";
                        $message .= "Ville: {$club_data['ville']}, Région: {$club_data['region']}\n\n";

                        wp_mail($admin_email, $subject, $message);
                    }
                } else {
                    $errors[] = 'Erreur lors de l\'enregistrement du club.';
                }
            }
        }
    }

    // Messages de succès/erreur avec le nouveau design
    if ($form_submitted) {
        if ($success) {
            echo '<div class="ufsc-alert ufsc-alert-success">';
            echo '<p>' . ($is_frontend ? 'Votre club a été ' : 'Le club a été ') . ($is_edit ? 'mis à jour' : 'créé') . ' avec succès.</p>';
            echo '</div>';

            if ($is_frontend && !$is_edit && !$is_affiliation) {
                // Afficher uniquement un message et pas le formulaire
                echo '<div class="ufsc-container">';
                echo '<div class="ufsc-card">';
                echo '<div class="ufsc-card-header">Demande enregistrée</div>';
                echo '<div class="ufsc-card-body">';
                echo '<p>Votre club a été enregistré avec succès.</p>';

                if ($is_frontend) {
                    echo '<p><a href="' . esc_url(get_permalink(get_option('ufsc_affiliation_page_id'))) . '" class="ufsc-btn ufsc-btn-red">
                        Procéder à l\'affiliation du club</a></p>';
                }

                echo '</div>';
                echo '</div>';
                echo '</div>';
                return;
            }
        } else {
            echo '<div class="ufsc-alert ufsc-alert-error">';
            echo '<p>Des erreurs ont été détectées :</p>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }

    // Classe du conteneur adaptée au contexte
    $container_class = $is_frontend ? 'ufsc-container' : '';

    // Titre de la page adapté au contexte
    $form_title = '';
    if ($is_affiliation) {
        $form_title = $is_edit ? 'Mettre à jour mon affiliation' : 'Demande d\'affiliation club';
    } else {
        $form_title = $is_edit ? 'Modifier mon club' : 'Créer un club';
    }
 ?>
    
    <div class="<?php echo $container_class; ?>">
        <?php if ($is_frontend && $is_affiliation): ?>
        <div class="ufsc-card">
            <div class="ufsc-card-header">Informations importantes</div>
            <div class="ufsc-card-body">
                <div class="ufsc-alert ufsc-alert-info">
                    <h4>Pack Affiliation Club - 500 €</h4>
                    <p>En validant ce formulaire, vous serez redirigé vers la page de paiement pour finaliser votre affiliation.</p>
                    <p><strong>Le pack inclut :</strong></p>
                    <ul>
                        <li>Affiliation du club pour 1 an</li>
                        <li>10 licences nominatives incluses :</li>
                        <ul>
                            <li>3 licences dirigeants (président, secrétaire, trésorier)</li>
                            <li>7 licences supplémentaires à attribuer librement</li>
                        </ul>
                        <li>Accès à l'espace club en ligne</li>
                        <li>Assurance fédérale</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($is_frontend): ?>
        <h2 class="ufsc-section-title"><?php echo esc_html($form_title); ?></h2>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data" class="ufsc-form" data-ajax-enabled="true" data-is-affiliation="<?php echo $is_affiliation ? 'true' : 'false'; ?>">
            <?php wp_nonce_field('ufsc_save_club', 'ufsc_club_nonce'); ?>
            <input type="hidden" name="ufsc_save_club_submit" value="1">
            
            <?php if ($is_edit): ?>
                <input type="hidden" name="club_id" value="<?php echo esc_attr($club_id); ?>">
            <?php endif; ?>
            
            <!-- Add sync indicator -->
            <div class="ufsc-sync-indicator" id="ufsc-sync-status" role="status" aria-live="polite">
                <span class="ufsc-sr-only">État de la synchronisation</span>
            </div>
            
            <!-- Informations générales -->
            <div class="ufsc-form-section">
                <div class="ufsc-form-section-header">
                    <i class="dashicons dashicons-groups"></i> Informations générales
                </div>
                <div class="ufsc-form-section-description">
                    <p>Renseignez les informations de base de votre club ou association, incluant l'adresse de correspondance officielle.</p>
                </div>
                <div class="ufsc-form-section-body">
                    <div class="ufsc-form-row">
                        <label for="nom">Nom du club / association <span class="ufsc-form-required">*</span></label>
                        <div>
                            <input type="text" name="nom" id="nom" placeholder="Nom complet de votre club ou association" value="<?php echo esc_attr($club->nom ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="region">Région <span class="ufsc-form-required">*</span></label>
                        <div>
                            <select name="region" id="region" required>
                                <option value="">-- Sélectionnez votre région --</option>
                                <?php foreach ($regions as $reg): ?>
                                    <option value="<?php echo esc_attr($reg); ?>"
                                    <?php echo selected($club->region ?? '', $reg, false); ?>>
                                        <?php echo esc_html($reg); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="adresse">Adresse du club <span class="ufsc-form-required">*</span></label>
                        <div>
                            <input type="text" name="adresse" id="adresse" placeholder="Numéro et nom de la rue" value="<?php echo esc_attr($club->adresse ?? ''); ?>" required>
                            <div class="ufsc-form-hint">Adresse de correspondance officielle du club</div>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="complement_adresse">Complément d'adresse</label>
                        <div>
                            <input type="text" name="complement_adresse" id="complement_adresse" placeholder="Bâtiment, étage, appartement..." value="<?php echo esc_attr($club->complement_adresse ?? ''); ?>">
                            <div class="ufsc-form-hint">Informations complémentaires (optionnel)</div>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="code_postal">Code postal <span class="ufsc-form-required">*</span></label>
                        <div>
                            <input type="text" name="code_postal" id="code_postal" placeholder="75001" pattern="[0-9]{5}" maxlength="5" value="<?php echo esc_attr($club->code_postal ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="ville">Ville <span class="ufsc-form-required">*</span></label>
                        <div>
                            <input type="text" name="ville" id="ville" placeholder="Ville de correspondance du club" value="<?php echo esc_attr($club->ville ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="email">Adresse email du club <span class="ufsc-form-required">*</span></label>
                        <div>
                            <input type="email" name="email" id="email" placeholder="contact@monclub.fr" value="<?php echo esc_attr($club->email ?? ''); ?>" required>
                            <div class="ufsc-form-hint">Email de contact principal du club</div>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="telephone">Téléphone du club <span class="ufsc-form-required">*</span></label>
                        <div>
                            <input type="tel" name="telephone" id="telephone" placeholder="01 23 45 67 89" value="<?php echo esc_attr($club->telephone ?? ''); ?>" required>
                            <div class="ufsc-form-hint">Numéro de téléphone principal du club</div>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="type">Type de structure</label>
                        <div>
                            <input type="text" name="type" id="type" value="<?php echo esc_attr($club->type ?? ''); ?>">
                            <div class="ufsc-form-hint">Association loi 1901, SARL, etc.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Photo/Logo et informations complémentaires -->
            <div class="ufsc-form-section">
                <div class="ufsc-form-section-header">
                    <i class="dashicons dashicons-format-image"></i> Logo et Présence Web
                </div>
                <div class="ufsc-form-section-description">
                    <p>Personnalisez l'identité visuelle de votre club et renseignez vos canaux de communication.</p>
                </div>
                <div class="ufsc-form-section-body">
                    <div class="ufsc-form-row">
                        <label for="logo_upload">Logo du club</label>
                        <div>
                            <input type="file" name="logo_upload" id="logo_upload" accept="image/*">
                            <div class="ufsc-form-hint">Formats acceptés: JPG, PNG. Taille max: 2 MB</div>
                            <?php if (!empty($club->logo_url)): ?>
                                <div class="ufsc-current-logo">
                                    <img src="<?php echo esc_url($club->logo_url); ?>" alt="Logo actuel" style="max-width: 100px; margin-top: 10px;">
                                    <p><small>Logo actuel</small></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="ufsc-form-row">
                        <label for="url_site">Site internet du club</label>
                        <div>
                            <input type="url" name="url_site" id="url_site" placeholder="https://www.monclub.fr" value="<?php echo esc_attr($club->url_site ?? ''); ?>">
                            <div class="ufsc-form-hint">URL complète de votre site web (optionnel)</div>
                        </div>
                    </div>

                    <div class="ufsc-form-row">
                        <label for="url_facebook">Page Facebook</label>
                        <div>
                            <input type="url" name="url_facebook" id="url_facebook" placeholder="https://www.facebook.com/monclub" value="<?php echo esc_attr($club->url_facebook ?? ''); ?>">
                            <div class="ufsc-form-hint">Lien vers votre page Facebook (optionnel)</div>
                        </div>
                    </div>

                    <div class="ufsc-form-row">
                        <label for="url_instagram">Compte Instagram</label>
                        <div>
                            <input type="url" name="url_instagram" id="url_instagram" placeholder="https://www.instagram.com/monclub" value="<?php echo esc_attr($club->url_instagram ?? ''); ?>">
                            <div class="ufsc-form-hint">Lien vers votre compte Instagram (optionnel)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Informations légales et financières -->
            <div class="ufsc-form-section">
                <div class="ufsc-form-section-header">
                    <i class="dashicons dashicons-admin-page"></i> Informations Légales et Financières
                </div>
                <div class="ufsc-form-section-description">
                    <p>Informations juridiques et bancaires nécessaires pour les clubs et associations.</p>
                </div>
                <div class="ufsc-form-section-body">
                    <div class="ufsc-form-row">
                        <label for="siren">Numéro SIREN</label>
                        <div>
                            <input type="text" name="siren" id="siren" placeholder="123 456 789" pattern="[0-9\s]{9,14}" value="<?php echo esc_attr($club->siren ?? ''); ?>">
                            <div class="ufsc-form-hint">Numéro SIREN de votre structure (9 chiffres)</div>
                        </div>
                    </div>

                    <div class="ufsc-form-row">
                        <label for="rna_number">Numéro RNA (si association)</label>
                        <div>
                            <input type="text" name="rna_number" id="rna_number" placeholder="W751234567" value="<?php echo esc_attr($club->rna_number ?? ''); ?>">
                            <div class="ufsc-form-hint">Numéro RNA pour les associations déclarées</div>
                        </div>
                    </div>

                    <div class="ufsc-form-row">
                        <label for="iban">IBAN/RIB du club</label>
                        <div>
                            <input type="text" name="iban" id="iban" placeholder="FR76 1234 5678 9012 3456 7890 123" value="<?php echo esc_attr($club->iban ?? ''); ?>">
                            <div class="ufsc-form-hint">IBAN pour la gestion des paiements (optionnel)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Administration (seulement en backend) -->
            <?php if (!$is_frontend): ?>
            <div class="ufsc-form-section">
                <div class="ufsc-form-section-header">
                    <i class="dashicons dashicons-admin-tools"></i> Administration
                </div>
                <div class="ufsc-form-section-body">
                    <div class="ufsc-form-row">
                        <label for="statut">Statut du club</label>
                        <div>
                            <select name="statut" id="statut">
                                <?php foreach ($statuts as $s): ?>
                                    <option value="<?php echo esc_attr($s); ?>
                                    <?php echo selected($club->statut ?? '', $s, false); ?>>
                                        <?php echo esc_html($s); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="num_affiliation">Numéro d'affiliation</label>
                        <div>
                            <input type="text" name="num_affiliation" id="num_affiliation" value="<?php echo esc_attr($club->num_affiliation ?? ''); ?>">
                            <div class="ufsc-form-hint">Format recommandé: UFSC-YYYY-XXX</div>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="quota_licences">Quota de licences</label>
                        <div>
                            <input type="number" name="quota_licences" id="quota_licences" min="0" value="<?php echo esc_attr($club->quota_licences ?? '0')?>">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="responsable_id">Utilisateur WordPress associé</label>
                        <div>
                            <select name="responsable_id" id="responsable_id">
                                <option value="">-- Aucun utilisateur associé --</option>
                                <?php 
                                // Get WordPress users for dropdown
                                $wordpress_users = ufsc_get_wordpress_users_for_clubs();
                                $current_responsable_id = $club->responsable_id ?? '';
                                
                                foreach ($wordpress_users as $user): 
                                    // Check if this user is already associated with another club
                                    $is_already_used = ufsc_is_user_already_associated($user->ID, $club_id ?? 0);
                                    $disabled = $is_already_used && $user->ID != $current_responsable_id ? 'disabled' : '';
                                    $title = $is_already_used && $user->ID != $current_responsable_id ? 'Cet utilisateur est déjà associé à un autre club' : '';
                                ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>" 
                                            <?php echo selected($current_responsable_id, $user->ID, false); ?>
                                            <?php echo $disabled; ?>
                                            title="<?php echo esc_attr($title); ?>">
                                        <?php echo esc_html($user->display_name . ' (' . $user->user_login . ') - ' . $user->user_email); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="ufsc-form-hint">
                                Associer un utilisateur WordPress qui aura accès à la gestion du club.
                                <?php if ($current_responsable_id): ?>
                                    <br><strong>Actuellement associé :</strong> 
                                    <?php 
                                    $current_user_info = ufsc_get_user_display_info($current_responsable_id);
                                    if ($current_user_info) {
                                        echo esc_html($current_user_info->display_name . ' (' . $current_user_info->login . ')');
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
                    
                    <?php if ($is_frontend && $is_affiliation && !$is_edit): ?>
                    <!-- Frontend User Association for Affiliation -->
                    <div class="ufsc-form-row">
                        <label>Utilisateur WordPress pour gérer le club</label>
                        <div>
                            <div class="ufsc-user-association-options">
                                <div class="ufsc-radio-group">
                                    <label class="ufsc-radio-option">
                                        <input type="radio" name="user_association_type" value="current" checked>
                                        <span>Utiliser mon compte actuel</span>
                                        <?php if (is_user_logged_in()): ?>
                                            <?php $current_user = wp_get_current_user(); ?>
                                            <div class="ufsc-form-hint">
                                                Compte: <?php echo esc_html($current_user->display_name . ' (' . $current_user->user_login . ')'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </label>
                                    
                                    <label class="ufsc-radio-option">
                                        <input type="radio" name="user_association_type" value="create">
                                        <span>Créer un nouveau compte utilisateur</span>
                                    </label>
                                    
                                    <label class="ufsc-radio-option">
                                        <input type="radio" name="user_association_type" value="existing">
                                        <span>Associer un compte existant</span>
                                    </label>
                                </div>
                                
                                <!-- Create new user fields -->
                                <div id="create-user-fields" class="ufsc-conditional-fields" style="display: none;">
                                    <div class="ufsc-form-row">
                                        <label for="new_user_login">Nom d'utilisateur <span class="required">*</span></label>
                                        <input type="text" name="new_user_login" id="new_user_login">
                                    </div>
                                    <div class="ufsc-form-row">
                                        <label for="new_user_email">Email <span class="required">*</span></label>
                                        <input type="email" name="new_user_email" id="new_user_email">
                                    </div>
                                    <div class="ufsc-form-row">
                                        <label for="new_user_display_name">Nom d'affichage</label>
                                        <input type="text" name="new_user_display_name" id="new_user_display_name">
                                    </div>
                                    <div class="ufsc-form-hint">
                                        Un mot de passe sera généré automatiquement et envoyé par email.
                                    </div>
                                </div>
                                
                                <!-- Existing user selection -->
                                <div id="existing-user-fields" class="ufsc-conditional-fields" style="display: none;">
                                    <div class="ufsc-form-row">
                                        <label for="existing_user_id">Sélectionner un utilisateur <span class="required">*</span></label>
                                        <select name="existing_user_id" id="existing_user_id">
                                            <option value="">-- Choisir un utilisateur --</option>
                                            <?php 
                                            $wordpress_users = ufsc_get_wordpress_users_for_clubs();
                                            foreach ($wordpress_users as $user): 
                                                $is_already_used = ufsc_is_user_already_associated($user->ID, 0);
                                                if (!$is_already_used):
                                            ?>
                                                <option value="<?php echo esc_attr($user->ID); ?>">
                                                    <?php echo esc_html($user->display_name . ' (' . $user->user_login . ') - ' . $user->user_email); ?>
                                                </option>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ufsc-form-hint">
                                <strong>Important :</strong> L'utilisateur associé pourra gérer le club depuis l'espace membre. 
                                Cette association ne pourra être modifiée que par un administrateur après la création.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informations légales -->
            <div class="ufsc-form-section">
                <div class="ufsc-form-section-header">
                    <i class="dashicons dashicons-clipboard"></i> Informations légales
                </div>
                <div class="ufsc-form-section-description">
                    <p>Informations administratives et légales de votre structure.</p>
                </div>
                <div class="ufsc-form-section-body">
                    <div class="ufsc-form-row">
                        <label for="siren">Numéro SIREN</label>
                        <div>
                            <input type="text" name="siren" id="siren" placeholder="123 456 789" value="<?php echo esc_attr($club->siren ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="ape">Code APE / NAF</label>
                        <div>
                            <input type="text" name="ape" id="ape" placeholder="9499Z" value="<?php echo esc_attr($club->ape ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="ccn">Convention collective</label>
                        <div>
                            <input type="text" name="ccn" id="ccn" placeholder="Sport" value="<?php echo esc_attr($club->ccn ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="ancv">Numéro ANCV</label>
                        <div>
                            <input type="text" name="ancv" id="ancv" placeholder="Si applicable" value="<?php echo esc_attr($club->ancv ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="num_declaration">N° déclaration préfecture <span class="ufsc-form-required">*</span></label>
                        <div>
                            <input type="text" name="num_declaration" id="num_declaration" placeholder="W123456789" value="<?php echo esc_attr($club->num_declaration ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="ufsc-form-row">
                        <label for="date_declaration">Date déclaration <span class="ufsc-form-required">*</span></label>
                        <div>
                            <input type="date" name="date_declaration" id="date_declaration" value="<?php echo esc_attr($club->date_declaration ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Dirigeants -->
            <div class="ufsc-form-section">
                <div class="ufsc-form-section-header">
                    <i class="dashicons dashicons-businessperson"></i> Dirigeants
                    <span class="ufsc-badge ufsc-badge-featured">Champs obligatoires</span>
                </div>
                <div class="ufsc-form-section-description">
                    <p>Informations des dirigeants du club. Ces personnes recevront automatiquement une licence dirigeant lors de l'affiliation.</p>
                </div>
                <div class="ufsc-form-section-body">
                    <?php foreach ($roles as $key => $label): ?>
                        <?php $is_required = $key !== 'entraineur'; // Tous les dirigeants obligatoires sauf entraîneur ?>
                        <div class="ufsc-dirigeant-section">
                            <h4><?php echo esc_html($label); ?> <?php echo $is_required ? '<span class="ufsc-form-required">*</span>' : ''; ?></h4>
                            
                            <div class="ufsc-form-row-group">
                                <div class="ufsc-form-row">
                                    <label for="<?php echo $key; ?>_prenom">Prénom <?php echo $is_required ? '<span class="ufsc-form-required">*</span>' : ''; ?></label>
                                    <div>
                                        <input type="text" name="<?php echo $key; ?>_prenom" id="<?php echo $key; ?>_prenom" placeholder="Prénom" 
                                            value="<?php echo esc_attr($club->{$key . '_prenom'} ?? ''); ?>"
                                            <?php echo $is_required ? 'required' : ''; ?>>
                                    </div>
                                </div>
                                
                                <div class="ufsc-form-row">
                                    <label for="<?php echo $key; ?>_nom">Nom <?php echo $is_required ? '<span class="ufsc-form-required">*</span>' : ''; ?></label>
                                    <div>
                                        <input type="text" name="<?php echo $key; ?>_nom" id="<?php echo $key; ?>_nom" placeholder="Nom" 
                                            value="<?php echo esc_attr($club->{$key . '_nom'} ?? ''); ?>"
                                            <?php echo $is_required ? 'required' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="ufsc-form-row">
                                <label for="<?php echo $key; ?>_tel">Téléphone <?php echo $is_required ? '<span class="ufsc-form-required">*</span>' : ''; ?></label>
                                <div>
                                    <input type="tel" name="<?php echo $key; ?>_tel" id="<?php echo $key; ?>_tel" placeholder="01 23 45 67 89"
                                        value="<?php echo esc_attr($club->{$key . '_tel'} ?? ''); ?>"
                                        <?php echo $is_required ? 'required' : ''; ?>>
                                </div>
                            </div>
                            
                            <div class="ufsc-form-row">
                                <label for="<?php echo $key; ?>_email">Email <?php echo $is_required ? '<span class="ufsc-form-required">*</span>' : ''; ?></label>
                                <div>
                                    <input type="email" name="<?php echo $key; ?>_email" id="<?php echo $key; ?>_email" placeholder="prenom.nom@email.fr"
                                        value="<?php echo esc_attr($club->{$key . '_email'} ?? ''); ?>"
                                        <?php echo $is_required ? 'required' : ''; ?>>
                                    <?php if ($is_affiliation && $key !== 'entraineur'): ?>
                                        <div class="ufsc-form-hint">Une licence dirigeant sera automatiquement générée</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($key !== array_key_last($roles)): ?>
                            <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Documents administratifs -->
            <div class="ufsc-form-section">
                <div class="ufsc-form-section-header">
                    <i class="dashicons dashicons-media-document"></i> Documents administratifs
                    <?php if ($is_affiliation): ?>
                    <span class="ufsc-badge ufsc-badge-featured">Obligatoire pour l'affiliation</span>
                    <?php endif; ?>
                </div>
                <div class="ufsc-form-section-description">
                    <p>Téléchargez les documents administratifs requis pour votre club.</p>
                    <p><strong>Formats acceptés :</strong> PDF, JPG, PNG. <strong>Taille max :</strong> 5 Mo par document.</p>
                </div>
                <div class="ufsc-form-section-body">
                    <?php foreach ($docs as $key => $label):
                        $is_required = $is_affiliation && in_array($key, ['statuts', 'recepisse', 'cer']);
                     ?>
                        <div class="ufsc-form-row">
                            <label for="<?php echo $key; ?>"><?php echo $label; ?> <?php echo $is_required ? '<span class="ufsc-form-required">*</span>' : ''; ?></label>
                            <div>
                                <input type="file" name="<?php echo $key; ?>" id="<?php echo $key; ?>" accept=".pdf,image/*" <?php echo $is_required && empty($club->{$key}) ? 'required' : ''; ?>>
                                <?php if (isset($club->{$key}) && !empty($club->{$key})): ?>
                                    <div class="ufsc-form-hint">
                                        <i class="dashicons dashicons-yes-alt"></i> Document déjà téléchargé. 
                                        <a href="<?php echo esc_url($club->{$key}); ?>" target="_blank">Voir le document</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Bouton de soumission -->
            <div class="ufsc-form-row" style="justify-content: flex-end; margin-top: 30px;">
                <div></div>
                <div>
                    <button type="submit" class="ufsc-btn ufsc-btn-red" name="ufsc_save_club_submit">
                        <?php
                            if ($is_affiliation) {
                                echo $is_edit ? 'Mettre à jour et continuer' : 'Valider et procéder au paiement';
                            } else {
                                echo $is_edit ? 'Mettre à jour le club' : 'Créer le club';
                            }
 ?>
                    </button>
                    
                    <?php if ($is_frontend): ?>
                    <div class="ufsc-form-hint" style="margin-top:10px; text-align:center;">
                        En soumettant ce formulaire, vous acceptez que les données saisies soient utilisées pour la gestion de votre club.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    <?php
}
