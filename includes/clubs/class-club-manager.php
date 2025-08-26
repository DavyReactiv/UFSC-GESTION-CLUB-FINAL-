<?php

/**
 * Club Manager Class
 *
 * Handles operations related to clubs
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Club Manager Class
 */
class UFSC_Club_Manager
{
    /**
     * Database table name
     */
    private $table_name;

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return UFSC_Club_Manager
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ufsc_clubs';
    }

    /**
     * Create database tables for clubs and licences
     * 
     * @return void
     */
    public function create_table()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create clubs table
        $sql_clubs = "CREATE TABLE {$this->table_name} (
            id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
            nom varchar(255) NOT NULL,
            email varchar(100),
            telephone varchar(20),
            adresse text,
            complement_adresse varchar(255),
            code_postal varchar(10),
            ville varchar(100),
            region varchar(100),
            precision_distribution varchar(100),
            url_site varchar(255),
            url_facebook varchar(255),
            url_instagram varchar(255),
            rna_number varchar(100),
            iban varchar(34),
            logo_url varchar(500),
            siren varchar(14),
            ape varchar(10),
            ccn varchar(100),
            ancv varchar(50),
            num_declaration varchar(100),
            date_declaration date,
            president_nom varchar(255),
            president_prenom varchar(255),
            president_email varchar(100),
            president_tel varchar(20),
            secretaire_nom varchar(255),
            secretaire_prenom varchar(255),
            secretaire_email varchar(100),
            secretaire_tel varchar(20),
            tresorier_nom varchar(255),
            tresorier_prenom varchar(255),
            tresorier_email varchar(100),
            tresorier_tel varchar(20),
            entraineur_nom varchar(255),
            entraineur_prenom varchar(255),
            entraineur_email varchar(100),
            entraineur_tel varchar(20),
            statut varchar(50) DEFAULT 'en_attente',
            date_creation datetime DEFAULT CURRENT_TIMESTAMP,
            date_affiliation datetime,
            num_affiliation varchar(50),
            quota_licences int DEFAULT 0,
            responsable_id int(11) NULL,
            contact varchar(255),
            doc_statuts varchar(255),
            doc_recepisse varchar(255),
            doc_jo varchar(255),
            doc_pv_ag varchar(255),
            doc_cer varchar(255),
            doc_attestation_cer varchar(255),
            doc_attestation_affiliation varchar(255),
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Create licences table with proper foreign key
        $licences_table = $wpdb->prefix . 'ufsc_licences';
        $sql_licences = "CREATE TABLE {$licences_table} (
            id mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
            club_id mediumint(9) unsigned NOT NULL,
            nom varchar(255) NOT NULL,
            prenom varchar(255) NOT NULL,
            sexe char(1) DEFAULT 'M',
            date_naissance date,
            email varchar(100),
            adresse text,
            suite_adresse varchar(255),
            code_postal varchar(10),
            ville varchar(100),
            tel_fixe varchar(20),
            tel_mobile varchar(20),
            reduction_benevole tinyint(1) DEFAULT 0,
            reduction_postier tinyint(1) DEFAULT 0,
            identifiant_laposte varchar(100),
            profession varchar(255),
            fonction_publique tinyint(1) DEFAULT 0,
            competition tinyint(1) DEFAULT 0,
            licence_delegataire tinyint(1) DEFAULT 0,
            numero_licence_delegataire varchar(100),
            diffusion_image tinyint(1) DEFAULT 0,
            infos_fsasptt tinyint(1) DEFAULT 0,
            infos_asptt tinyint(1) DEFAULT 0,
            infos_cr tinyint(1) DEFAULT 0,
            infos_partenaires tinyint(1) DEFAULT 0,
            honorabilite tinyint(1) DEFAULT 0,
            assurance_dommage_corporel tinyint(1) DEFAULT 0,
            assurance_assistance tinyint(1) DEFAULT 0,
            note text,
            region varchar(100),
            statut ENUM('en_attente', 'validee', 'refusee') DEFAULT 'en_attente',
            is_included tinyint(1) DEFAULT 0,
            date_inscription datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY club_id (club_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_clubs);
        dbDelta($sql_licences);

        // Apply patches for existing installations
        $this->apply_database_patches();
    }

    /**
     * Apply database patches for existing installations
     * 
     * @return void
     */
    private function apply_database_patches()
    {
        global $wpdb;
        
        $licences_table = $wpdb->prefix . 'ufsc_licences';
        $clubs_table = $this->table_name;
        
        // First, fix clubs table structure
        $clubs_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $clubs_table)) === $clubs_table;
        
        if ($clubs_table_exists) {
            // Ensure the clubs.id column is unsigned to match foreign key requirements
            $clubs_id_column_info = $wpdb->get_row("SHOW COLUMNS FROM $clubs_table LIKE 'id'");
            if ($clubs_id_column_info && stripos($clubs_id_column_info->Type, 'mediumint(9) unsigned') === false) {
                $wpdb->query("ALTER TABLE $clubs_table MODIFY COLUMN id mediumint(9) unsigned NOT NULL AUTO_INCREMENT");
            }
            
            // Add responsable_id column if it doesn't exist
            $responsable_id_column = $wpdb->get_var("SHOW COLUMNS FROM $clubs_table LIKE 'responsable_id'");
            if (!$responsable_id_column) {
                $wpdb->query("ALTER TABLE $clubs_table ADD COLUMN responsable_id int(11) NULL AFTER quota_licences");
            }
        }
        
        // Then, fix licences table structure and foreign key
        $licences_table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $licences_table)) === $licences_table;
        
        if ($licences_table_exists) {
            // Remove existing foreign key constraints that might be incompatible
            $foreign_keys = $wpdb->get_results("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = '$licences_table' 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");
            
            foreach ($foreign_keys as $fk) {
                if (strpos($fk->CONSTRAINT_NAME, 'fk_licence_club') !== false || 
                    strpos($fk->CONSTRAINT_NAME, 'club') !== false) {
                    $wpdb->query("ALTER TABLE $licences_table DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                }
            }
            
            // Ensure club_id column has the right type to match clubs.id
            $licences_club_id_column_info = $wpdb->get_row("SHOW COLUMNS FROM $licences_table LIKE 'club_id'");
            if ($licences_club_id_column_info && stripos($licences_club_id_column_info->Type, 'mediumint(9) unsigned') === false) {
                $wpdb->query("ALTER TABLE $licences_table MODIFY COLUMN club_id mediumint(9) unsigned NOT NULL");
            }
            
            // Ensure the licences.id column is also unsigned for consistency
            $licences_id_column_info = $wpdb->get_row("SHOW COLUMNS FROM $licences_table LIKE 'id'");
            if ($licences_id_column_info && stripos($licences_id_column_info->Type, 'mediumint(9) unsigned') === false) {
                $wpdb->query("ALTER TABLE $licences_table MODIFY COLUMN id mediumint(9) unsigned NOT NULL AUTO_INCREMENT");
            }
            
            // Now add the foreign key constraint with the proper column types
            $wpdb->query("ALTER TABLE $licences_table ADD CONSTRAINT fk_licence_club FOREIGN KEY (club_id) REFERENCES {$clubs_table} (id) ON DELETE CASCADE");
            
            // Add statut column for license validation system if it doesn't exist
            $statut_column = $wpdb->get_var("SHOW COLUMNS FROM $licences_table LIKE 'statut'");
            if (!$statut_column) {
                $wpdb->query("ALTER TABLE $licences_table ADD COLUMN statut ENUM('en_attente', 'validee', 'refusee') DEFAULT 'en_attente' AFTER region");
            }
            
            // Add attestation_url column for individual license attestations if it doesn't exist
            $attestation_column = $wpdb->get_var("SHOW COLUMNS FROM $licences_table LIKE 'attestation_url'");
            if (!$attestation_column) {
                $wpdb->query("ALTER TABLE $licences_table ADD COLUMN attestation_url VARCHAR(255) NULL AFTER statut");
            }
        }
        
        // Apply additional patches for clubs table - legacy field additions
        if ($clubs_table_exists) {
            // Check if region column exists
            $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $clubs_table LIKE 'region'");
            if (!$column_exists) {
                $wpdb->query("ALTER TABLE $clubs_table ADD COLUMN region varchar(100) AFTER code_postal");
            }
            
            // Add prénom columns for dirigeants if they don't exist
            $dirigeant_roles = ['president', 'secretaire', 'tresorier', 'entraineur'];
            foreach ($dirigeant_roles as $role) {
                $prenom_column = $role . '_prenom';
                $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $clubs_table LIKE '$prenom_column'");
                if (!$column_exists) {
                    $nom_column = $role . '_nom';
                    $wpdb->query("ALTER TABLE $clubs_table ADD COLUMN $prenom_column varchar(255) AFTER $nom_column");
                }
            }
            
            // Check if contact column exists
            $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $clubs_table LIKE 'contact'");
            if (!$column_exists) {
                $wpdb->query("ALTER TABLE $clubs_table ADD COLUMN contact varchar(255) AFTER quota_licences");
            }

            // Add missing required fields for the new form
            $new_fields = [
                'complement_adresse' => 'varchar(255)',
                'precision_distribution' => 'varchar(100)',
                'url_site' => 'varchar(255)',
                'url_facebook' => 'varchar(255)',
                'url_instagram' => 'varchar(255)',
                'rna_number' => 'varchar(100)',
                'iban' => 'varchar(34)',
                'logo_url' => 'varchar(500)',
                'siren' => 'varchar(14)',
                'ape' => 'varchar(10)',
                'ccn' => 'varchar(100)',
                'ancv' => 'varchar(50)',
                'num_declaration' => 'varchar(100)',
                'date_declaration' => 'date',
                'entraineur_nom' => 'varchar(255)',
                'entraineur_email' => 'varchar(100)',
                'entraineur_tel' => 'varchar(20)',
                'doc_statuts' => 'varchar(255)',
                'doc_recepisse' => 'varchar(255)',
                'doc_jo' => 'varchar(255)',
                'doc_pv_ag' => 'varchar(255)',
                'doc_cer' => 'varchar(255)',
                'doc_attestation_cer' => 'varchar(255)',
                'doc_attestation_affiliation' => 'varchar(255)'
            ];

            foreach ($new_fields as $field => $type) {
                $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $clubs_table LIKE '$field'");
                if (!$column_exists) {
                    $wpdb->query("ALTER TABLE $clubs_table ADD COLUMN $field $type");
                }
            }
        }
    }

    /**
     * Get all clubs
     * 
     * @return array
     */
    public function get_clubs()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY nom ASC");
    }

    /**
     * Get a single club by ID
     * 
     * @param int $club_id
     * @return object|null
     */
    public function get_club($club_id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $club_id));
    }

    /**
     * Add a new club
     * 
     * @param array $club_data
     * @return int|false
     */
    public function add_club($club_data)
    {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            $club_data,
            $this->get_format_array($club_data)
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update an existing club
     * 
     * @param int $club_id
     * @param array $club_data
     * @return bool
     */
    public function update_club($club_id, $club_data)
    {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            $club_data,
            ['id' => $club_id],
            $this->get_format_array($club_data),
            ['%d']
        ) !== false;
    }

    /**
     * Delete a club
     * 
     * @param int $club_id
     * @return bool
     */
    public function delete_club($club_id)
    {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $club_id],
            ['%d']
        ) !== false;
    }

    /**
     * Get format array for wpdb operations
     * 
     * @param array $data
     * @return array
     */
    private function get_format_array($data)
    {
        $formats = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['id', 'quota_licences', 'responsable_id'])) {
                $formats[] = '%d';
            } elseif (in_array($key, ['date_creation', 'date_affiliation', 'date_declaration'])) {
                $formats[] = '%s';
            } else {
                $formats[] = '%s';
            }
        }
        return $formats;
    }

    /**
     * Update club document URL
     * 
     * @param int $club_id
     * @param string $document_type
     * @param string $document_url
     * @return bool
     */
    public function update_club_document($club_id, $document_type, $document_url)
    {
        global $wpdb;
        
        // Map document types to database columns
        $document_columns = [
            'statuts' => 'doc_statuts',
            'recepisse' => 'doc_recepisse',
            'jo' => 'doc_jo',
            'pv_ag' => 'doc_pv_ag',
            'cer' => 'doc_cer',
            'attestation_cer' => 'doc_attestation_cer',
            'attestation_affiliation' => 'doc_attestation_affiliation'
        ];

        if (!isset($document_columns[$document_type])) {
            return false;
        }

        // First, add the column if it doesn't exist
        $column_name = $document_columns[$document_type];
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$this->table_name} LIKE '$column_name'");
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD COLUMN $column_name varchar(255)");
        }

        return $wpdb->update(
            $this->table_name,
            [$column_name => $document_url],
            ['id' => $club_id],
            ['%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Update a specific field for a club
     * 
     * @param int $club_id Club ID
     * @param string $field_name Field name to update
     * @param mixed $value New value for the field
     * @return bool Success status
     */
    public function update_club_field($club_id, $field_name, $value)
    {
        global $wpdb;

        // Security: whitelist allowed fields for update
        $allowed_fields = [
            'nom', 'email', 'telephone', 'adresse', 'complement_adresse',
            'code_postal', 'ville', 'region', 'url_site', 'url_facebook',
            'url_instagram', 'siren', 'rna_number', 'iban', 'logo_url',
            'type', 'statut', 'num_affiliation', 'quota_licences',
            'president_nom', 'president_prenom', 'president_email', 'president_tel',
            'secretaire_nom', 'secretaire_prenom', 'secretaire_email', 'secretaire_tel',
            'tresorier_nom', 'tresorier_prenom', 'tresorier_email', 'tresorier_tel',
            'entraineur_nom', 'entraineur_prenom', 'entraineur_email', 'entraineur_tel'
        ];

        if (!in_array($field_name, $allowed_fields)) {
            return false;
        }

        // Determine format based on field type
        $format = '%s';
        if (in_array($field_name, ['id', 'quota_licences', 'responsable_id'])) {
            $format = '%d';
            $value = intval($value);
        } elseif (in_array($field_name, ['email'])) {
            $value = sanitize_email($value);
        } elseif (in_array($field_name, ['url_site', 'url_facebook', 'url_instagram'])) {
            $value = esc_url_raw($value);
        } else {
            $value = sanitize_text_field($value);
        }

        return $wpdb->update(
            $this->table_name,
            [$field_name => $value],
            ['id' => $club_id],
            [$format],
            ['%d']
        ) !== false;
    }

    /**
     * CORRECTION: Get licenses by club ID
     * 
     * This method delegates to the License Manager to maintain compatibility
     * with existing code while using the proper License Manager class.
     * Fixes crashes when checking club license quota.
     * 
     * @param int $club_id Club ID
     * @return array Array of license objects
     * @since 1.0.2 Delegation to License Manager for compatibility
     */
    public function get_licences_by_club($club_id)
    {
        // Use License Manager for proper license operations
        if (!class_exists('UFSC_Licence_Manager')) {
            require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        }
        
        $licence_manager = new UFSC_Licence_Manager();
        return $licence_manager->get_licences(['club_id' => $club_id]);
    }

    /**
     * CORRECTION: Add license method for backward compatibility
     * 
     * This method delegates to the License Manager to maintain compatibility
     * with existing WooCommerce integration while using proper separation of concerns.
     * Fixes critical crash when purchasing licenses through WooCommerce.
     * 
     * @param array $licence_data License data
     * @return int License ID
     * @since 1.0.2 Delegation to License Manager for compatibility
     */
    public function add_licence($licence_data)
    {
        // Use License Manager for proper license operations  
        if (!class_exists('UFSC_Licence_Manager')) {
            require_once UFSC_PLUGIN_PATH . 'includes/licences/class-licence-manager.php';
        }
        
        $licence_manager = new UFSC_Licence_Manager();
        return $licence_manager->add_licence($licence_data);
    }
}
