<?php

namespace UFSC\Helpers;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * UFSC CSV Export Class
 */
class CSVExport
{
    /**
     * Export clubs in UFSC-compliant format
     * Based on "20242025 - DEMANDE D'affiliation club VIERGE.csv" structure
     *
     * @param array $clubs Array of club objects
     * @param string $filename Optional filename
     */
    public static function export_clubs($clubs, $filename = null)
    {
        if (empty($filename)) {
            $filename = 'clubs_ufsc_' . date('Y-m-d') . '.csv';
        }

        // Set proper HTTP headers to prevent HTML contamination
        self::set_csv_headers($filename);

        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8 encoding
        fprintf($output, "\xEF\xBB\xBF");

        // UFSC-compliant headers for club affiliation
        $headers = [
            'Nom du club',
            'Sigle', 
            'Adresse',
            'Complément d\'adresse',
            'Code postal',
            'Ville',
            'Région',
            'Téléphone',
            'Email',
            'Site web',
            'SIREN',
            'Code APE',
            'Numéro RNA',
            'Président nom',
            'Président prénom', 
            'Président email',
            'Président téléphone',
            'Secrétaire nom',
            'Secrétaire prénom',
            'Secrétaire email',
            'Secrétaire téléphone',
            'Trésorier nom',
            'Trésorier prénom',
            'Trésorier email',
            'Trésorier téléphone',
            'Date de création'
        ];

        // Write headers with semicolon separator
        self::write_csv_row($output, $headers);

        // Write data rows
        foreach ($clubs as $club) {
            $row = [
                $club->nom ?? '',
                $club->sigle ?? '',
                $club->adresse ?? '',
                $club->complement_adresse ?? '',
                $club->code_postal ?? '',
                $club->ville ?? '',
                $club->region ?? '',
                $club->telephone ?? '',
                $club->email ?? '',
                $club->url_site ?? '',
                $club->siren ?? '',
                $club->ape ?? '',
                $club->rna_number ?? '',
                $club->president_nom ?? '',
                $club->president_prenom ?? '',
                $club->president_email ?? '',
                $club->president_tel ?? '',
                $club->secretaire_nom ?? '',
                $club->secretaire_prenom ?? '',
                $club->secretaire_email ?? '',
                $club->secretaire_tel ?? '',
                $club->tresorier_nom ?? '',
                $club->tresorier_prenom ?? '',
                $club->tresorier_email ?? '',
                $club->tresorier_tel ?? '',
                $club->date_creation ?? ''
            ];
            
            self::write_csv_row($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Export licenses in UFSC-compliant format
     * Based on "fichier-type_demande de licence UFSC_2024_2025.csv" structure
     *
     * @param array $licenses Array of license objects
     * @param string $filename Optional filename
     */
    public static function export_licenses($licenses, $filename = null)
    {
        if (empty($filename)) {
            $filename = 'licences_ufsc_' . date('Y-m-d') . '.csv';
        }

        // Set proper HTTP headers to prevent HTML contamination
        self::set_csv_headers($filename);

        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8 encoding
        fprintf($output, "\xEF\xBB\xBF");

        // UFSC-compliant headers for license requests
        $headers = [
            'Nom',
            'Prénom',
            'Sexe',
            'Date de naissance',
            'Email',
            'Adresse',
            'Complément d\'adresse',
            'Code postal',
            'Ville',
            'Région',
            'Téléphone fixe',
            'Téléphone mobile',
            'Profession',
            'Club',
            'Type de licence',
            'Compétition',
            'Date d\'inscription'
        ];

        // Write headers with semicolon separator
        self::write_csv_row($output, $headers);

        // Write data rows
        foreach ($licenses as $license) {
            $row = [
                $license->nom ?? '',
                $license->prenom ?? '',
                $license->sexe ?? '',
                $license->date_naissance ?? '',
                $license->email ?? '',
                $license->adresse ?? '',
                $license->suite_adresse ?? '',
                $license->code_postal ?? '',
                $license->ville ?? '',
                $license->region ?? '',
                $license->tel_fixe ?? '',
                $license->tel_mobile ?? '',
                $license->profession ?? '',
                $license->club_nom ?? '',
                $license->is_included ? 'Incluse' : 'Payante',
                $license->competition ? 'Oui' : 'Non',
                $license->date_inscription ?? ''
            ];
            
            self::write_csv_row($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Set proper HTTP headers for CSV download
     * Prevents HTML contamination in CSV files
     *
     * @param string $filename
     */
    public static function set_csv_headers($filename)
    {
        // Clear any output buffer to prevent HTML contamination
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers to force download and prevent caching
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    }

    /**
     * Write a CSV row with semicolon separator
     * Ensures UFSC compliance with semicolon separator
     *
     * @param resource $output File handle
     * @param array $data Row data
     */
    private static function write_csv_row($output, $data)
    {
        // Clean data to prevent HTML/special characters
        $clean_data = array_map(function($value) {
            // Remove HTML tags and decode entities
            $value = wp_strip_all_tags($value);
            $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            
            // Remove any remaining special characters that could cause issues
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
            
            return $value;
        }, $data);

        // Use semicolon as separator for UFSC compliance
        fputcsv($output, $clean_data, ';', '"', '\\');
    }

    /**
     * Get available status options for filtering
     *
     * @return array
     */
    public static function get_status_options()
    {
        return [
            'en_attente' => 'En attente',
            'validee' => 'Validée',
            'refusee' => 'Refusée'
        ];
    }
}
