<?php
if (!defined('ABSPATH')) exit;

function ufsc_render_licence_form($args = array()){
    $defaults = array(
        'context' => 'shortcode',
        'club' => null,
        'show_title' => true,
        'submit_button_text' => 'Ajouter au panier',
        'form_id' => 'ufsc-licence-form',
        'redirect_to_product' => false,
    );
    $args = wp_parse_args($args, $defaults);

    // Resolve club of current user
    if (empty($args['club'])){
        if (function_exists('ufsc_check_frontend_access')){
            $access = ufsc_check_frontend_access('licence');
            if (!$access['allowed']) {
                return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card">'
                    . $access['error_message'] . '</div></div></div>';
            }
            $club = $access['club'];
        } else {
            return '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-card"><div class="ufsc-error">Accès refusé.</div></div></div></div>';
        }
    } else {
        $club = $args['club'];
    }

    // Prefill data if editing
    $prefill = array();
    if (!empty($_GET['licence_id'])){
        global $wpdb; $t = $wpdb->prefix.'ufsc_licences';
        $lic_id = absint($_GET['licence_id']);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d AND club_id=%d", $lic_id, (int)$club->id), ARRAY_A);
        if ($row) $prefill = $row;
    }

    $v = function($key) use ($prefill){ return isset($prefill[$key]) ? esc_attr($prefill[$key]) : ''; };
    $is_checked = function($key) use ($prefill){ return !empty($prefill[$key]) ? ' checked' : ''; };
    $sel = function($key,$val) use ($prefill){ return (isset($prefill[$key]) && (string)$prefill[$key]===(string)$val) ? ' selected' : ''; };

    ob_start();
    ?>
    <div class="ufsc-container">
    <div class="ufsc-grid">
    <div class="ufsc-card">
    <?php if (!empty($args['show_title'])): ?>
      <h3>Nouvelle licence sportive</h3>
    <?php endif; ?>

    <form id="<?php echo esc_attr($args['form_id']); ?>" class="ufsc-licence-form ufsc-licence-form ufsc-form" method="post" action="<?php echo esc_url( get_permalink() ); ?>">

      <?php echo ufsc_nonce_field('ufsc_add_licence_nonce'); ?>
      <?php echo ufsc_nonce_field('ufsc_add_licence_to_cart', '_ufsc_licence_nonce'); ?>
      <input type="hidden" name="action" value="ufsc_submit_licence">
      <input type="hidden" name="club_id" value="<?php echo esc_attr($club->id); ?>">

      <?php wp_nonce_field('ufsc_add_licence_nonce', 'ufsc_nonce'); ?>
      <?php wp_nonce_field('ufsc_add_licence_to_cart', '_ufsc_licence_nonce'); ?>
        <input type="hidden" name="action" value="ufsc_submit_licence">
        <input type="hidden" name="club_id" value="<?php echo esc_attr($club->id); ?>">

      <input type="hidden" name="context" value="<?php echo esc_attr($args['context']); ?>">
      <?php if (!empty($prefill['id'])): ?>
        <input type="hidden" name="licence_id" value="<?php echo esc_attr($prefill['id']); ?>">
      <?php endif; ?>

      <fieldset class="ufsc-form-section">
        <legend>Informations personnelles</legend>
        <div class="ufsc-form-grid ufsc-auto-2cols">
          <div class="ufsc-form-field">
            <label for="nom">Nom *</label>
            <input type="text" id="nom" name="nom" required maxlength="100" value="<?php echo $v('nom'); ?>">
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="prenom">Prénom *</label>
            <input type="text" id="prenom" name="prenom" required maxlength="100" value="<?php echo $v('prenom'); ?>">
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
        </div>

        <div class="ufsc-form-grid ufsc-auto-2cols">
          <div class="ufsc-form-field">
            <label for="date_naissance">Date de naissance *</label>
            <input type="date" id="date_naissance" name="date_naissance" required value="<?php echo $v('date_naissance'); ?>">
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="sexe">Sexe *</label>
            <select id="sexe" name="sexe" required>
              <option value="">Sélectionner...</option>
              <option value="M"<?php echo $sel('sexe','M'); ?>>Masculin</option>
              <option value="F"<?php echo $sel('sexe','F'); ?>>Féminin</option>
            </select>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
        </div>

        <div class="ufsc-form-field ufsc-full">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required maxlength="150" value="<?php echo $v('email'); ?>">
          <p class="ufsc-form-hint"></p>
          <span class="ufsc-form-error"></span>
        </div>
      </fieldset>

      <fieldset class="ufsc-form-section">
        <legend>Coordonnées</legend>
        <div class="ufsc-form-field ufsc-full">
          <label for="adresse">Adresse</label>
          <input type="text" id="adresse" name="adresse" maxlength="200" value="<?php echo $v('adresse'); ?>">
          <p class="ufsc-form-hint"></p>
          <span class="ufsc-form-error"></span>
        </div>
        <div class="ufsc-form-grid ufsc-auto-2cols">
          <div class="ufsc-form-field">
            <label for="suite_adresse">Complément d'adresse</label>
            <input type="text" id="suite_adresse" name="suite_adresse" maxlength="200" value="<?php echo $v('suite_adresse'); ?>">
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="code_postal">Code postal</label>
            <input type="text" id="code_postal" name="code_postal" maxlength="10" value="<?php echo $v('code_postal'); ?>">
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
        </div>
        <div class="ufsc-form-field ufsc-full">
          <label for="ville">Ville</label>
          <input type="text" id="ville" name="ville" maxlength="120" value="<?php echo $v('ville'); ?>">
          <p class="ufsc-form-hint"></p>
          <span class="ufsc-form-error"></span>
        </div>
        <div class="ufsc-form-grid ufsc-auto-2cols">
          <div class="ufsc-form-field">
            <label for="tel_mobile">Téléphone</label>
            <input type="text" id="tel_mobile" name="tel_mobile" maxlength="25" value="<?php echo $v('tel_mobile'); ?>">
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="profession">Profession</label>
            <input type="text" id="profession" name="profession" maxlength="100" value="<?php echo $v('profession'); ?>">
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
        </div>
      </fieldset>

      <fieldset class="ufsc-form-section">
        <legend>Rôle & type</legend>
        <div class="ufsc-form-grid ufsc-auto-2cols">
          <div class="ufsc-form-field">
            <label for="fonction">Fonction</label>
            <select id="fonction" name="fonction">
              <option value="">—</option>
              <option value="president"<?php echo $sel('fonction','president'); ?>>Président</option>
              <option value="secretaire"<?php echo $sel('fonction','secretaire'); ?>>Secrétaire</option>
              <option value="tresorier"<?php echo $sel('fonction','tresorier'); ?>>Trésorier</option>
              <option value="entraineur"<?php echo $sel('fonction','entraineur'); ?>>Entraîneur</option>
              <option value="adherent"<?php echo $sel('fonction','adherent'); ?>>Adhérent</option>
            </select>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="competition">Compétition</label>
            <input type="checkbox" id="competition" name="competition" value="1"<?php echo $is_checked('competition'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
        </div>
        <div class="ufsc-form-grid ufsc-auto-2cols">
          <div class="ufsc-form-field">
            <label for="licence_delegataire">Licence délégataire</label>
            <input type="checkbox" id="licence_delegataire" name="licence_delegataire" value="1"<?php echo $is_checked('licence_delegataire'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field ufsc-hidden" id="numero_licence_delegataire_field">
            <label for="numero_licence_delegataire">N° délégataire</label>
            <input type="text" id="numero_licence_delegataire" name="numero_licence_delegataire" value="<?php echo $v('numero_licence_delegataire'); ?>">
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
      </div>
      </fieldset>

      <fieldset class="ufsc-form-section">
        <legend>Autorisations et communications</legend>
        <div class="ufsc-form-grid ufsc-auto-2cols">
          <div class="ufsc-form-field">
            <label for="diffusion_image">Consentement diffusion image</label>
            <input type="checkbox" id="diffusion_image" name="diffusion_image" value="1"<?php echo $is_checked('diffusion_image'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="infos_fsasptt">Recevoir les infos FSASPTT</label>
            <input type="checkbox" id="infos_fsasptt" name="infos_fsasptt" value="1"<?php echo $is_checked('infos_fsasptt'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="infos_asptt">Recevoir les infos ASPTT</label>
            <input type="checkbox" id="infos_asptt" name="infos_asptt" value="1"<?php echo $is_checked('infos_asptt'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="infos_cr">Recevoir les infos Comité Régional</label>
            <input type="checkbox" id="infos_cr" name="infos_cr" value="1"<?php echo $is_checked('infos_cr'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="infos_partenaires">Recevoir les infos partenaires</label>
            <input type="checkbox" id="infos_partenaires" name="infos_partenaires" value="1"<?php echo $is_checked('infos_partenaires'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
        </div>
      </fieldset>

      <fieldset class="ufsc-form-section">
        <legend>Déclarations et assurances</legend>
        <div class="ufsc-form-grid ufsc-auto-2cols">
          <div class="ufsc-form-field">
            <label for="honorabilite">Déclaration d'honorabilité</label>
            <input type="checkbox" id="honorabilite" name="honorabilite" value="1"<?php echo $is_checked('honorabilite'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="assurance_dommage_corporel">Assurance dommage corporel</label>
            <input type="checkbox" id="assurance_dommage_corporel" name="assurance_dommage_corporel" value="1"<?php echo $is_checked('assurance_dommage_corporel'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
          <div class="ufsc-form-field">
            <label for="assurance_assistance">Assurance assistance</label>
            <input type="checkbox" id="assurance_assistance" name="assurance_assistance" value="1"<?php echo $is_checked('assurance_assistance'); ?>>
            <p class="ufsc-form-hint"></p>
            <span class="ufsc-form-error"></span>
          </div>
        </div>
      </fieldset>

      <fieldset class="ufsc-form-section">
        <legend>Règlements</legend>
        <div class="ufsc-form-field ufsc-full">
          <label for="ufsc_rules_ack">J'ai pris connaissance des règlements — <a href="https://ufsc-france.fr/ufsc-reglements-sportifs-techniques-interieur/" target="_blank" rel="noopener">Lire les règlements</a></label>
          <input type="checkbox" id="ufsc_rules_ack" name="ufsc_rules_ack" value="1" required>
          <p class="ufsc-form-hint"></p>
          <span class="ufsc-form-error"></span>
        </div>
      </fieldset>

      <div class="ufsc-form-actions">
        <button type="button" id="ufsc-save-draft" class="ufsc-btn ufsc-btn-secondary">💾 Enregistrer brouillon</button>
        <button type="submit" class="ufsc-btn"><?php echo esc_html($args['submit_button_text']); ?></button>
      </div>
    </form>
    </div>
    </div>
    </div>

    <script>
    (function($){
      $(function(){
        function toggleDelegataire(){
          if ($('#licence_delegataire').is(':checked')){
            $('#numero_licence_delegataire_field').show();
            $('#numero_licence_delegataire').attr('required', true);
          } else {
            $('#numero_licence_delegataire_field').hide();
            $('#numero_licence_delegataire').attr('required', false);
          }
        }
        $('#licence_delegataire').on('change', toggleDelegataire);
        toggleDelegataire();
      });
    })(jQuery);
    </script>
    <?php
    return ob_get_clean();
}
