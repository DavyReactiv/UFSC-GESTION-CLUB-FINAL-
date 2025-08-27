<?php
if (!defined('ABSPATH')) exit;

/**
 * Persist a licence from POST data into DB (ufsc_licences).
 * Returns licence_id (int) on success or 0 on failure.
 *
 * @param int   $club_id
 * @param int   $licence_id Existing ID to update (0 to insert)
 * @param array $extra Override fields like 'statut'
 * @return int
 */
function ufsc_detect_licence_table_name(){
  global $wpdb;
  return $wpdb->prefix . 'ufsc_licences';
}

function ufsc_persist_licence_from_post($club_id, $licence_id = 0, $extra = array()){
  if (!$club_id) return 0;
  global $wpdb;
  $t = ufsc_detect_licence_table_name();

  // Map incoming POST keys to DB columns (canonical names)
  $map = array(
    'nom' => 'nom',
    'prenom' => 'prenom',
    'email' => 'email',
    'date_naissance' => 'date_naissance',
    'sexe' => 'sexe',
    'adresse' => 'adresse',
    'suite_adresse' => 'suite_adresse',
    'code_postal' => 'code_postal',
    'ville' => 'ville',
    'region' => 'region',
    'pays' => 'pays',
    'tel' => 'telephone',
    'tel_mobile' => 'tel_mobile',
    'tel_fixe' => 'tel_fixe',
    'profession' => 'profession',
    'fonction' => 'fonction',
    'competition' => 'competition',
    'diffusion_image' => 'diffusion_image',
    'honorabilite' => 'honorabilite',
    'infos_fsasptt' => 'infos_fsasptt',
    'infos_asptt' => 'infos_asptt',
    'infos_cr' => 'infos_cr',
    'infos_partenaires' => 'infos_partenaires',
    'licence_delegataire' => 'licence_delegataire',
    'numero_licence_delegataire' => 'numero_licence_delegataire',
    'reduction_benevole' => 'reduction_benevole',
    'reduction_postier' => 'reduction_postier',
    'identifiant_laposte' => 'identifiant_laposte',
    'fonction_publique' => 'fonction_publique',
    'assurance_dommage_corporel' => 'assurance_dommage_corporel',
    'assurance_assistance' => 'assurance_assistance',
    'note' => 'note',
    'is_included' => 'is_included',
  );

  $data = array('club_id' => (int)$club_id);
  foreach ($map as $post_key => $col){
    if (isset($_POST[$post_key])){
      $val = is_array($_POST[$post_key]) ? wp_json_encode(array_map('sanitize_text_field', wp_unslash($_POST[$post_key]))) : sanitize_text_field(wp_unslash($_POST[$post_key]));
      $data[$col] = $val;
    }
  }

  // Booleans
  $bools = array('competition','diffusion_image','honorabilite','infos_fsasptt','infos_asptt','infos_cr','infos_partenaires','licence_delegataire','reduction_benevole','reduction_postier','fonction_publique','assurance_dommage_corporel','assurance_assistance');
  foreach ($bools as $k){
    $data[$k] = !empty($_POST[$k]) ? 1 : 0;
  }

  // Merge extras
  foreach ((array)$extra as $k=>$v){ $data[$k] = $v; }

  // Ensure creation date for new rows
  if (empty($licence_id) && empty($data['date_creation'])){
    $data['date_creation'] = current_time('mysql');
  }

  // Upsert
  if ($licence_id){
    $ok = $wpdb->update($t, $data, array('id'=>(int)$licence_id, 'club_id'=>(int)$club_id));
    return $ok !== false ? (int)$licence_id : 0;
  } else {
    $wpdb->insert($t, $data);
    return (int)$wpdb->insert_id;
  }
}
