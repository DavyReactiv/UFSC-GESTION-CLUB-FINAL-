<?php
/**
 * Database validation script for UFSC plugin
 * This can be run by administrators to verify database fixes
 * 
 * Usage: Add ?ufsc_validate_db=1 to any admin page URL
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validate UFSC database schema
 */
function ufsc_validate_database_schema() {
    global $wpdb;
    
    $issues = [];
    $fixes_applied = [];
    
    $clubs_table = $wpdb->prefix . 'ufsc_clubs';
    $licences_table = $wpdb->prefix . 'ufsc_licences';
    
    // Check if tables exist
    $clubs_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $clubs_table)) === $clubs_table;
    $licences_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $licences_table)) === $licences_table;
    
    if (!$clubs_exists) {
        $issues[] = "Clubs table does not exist";
        return $issues;
    }
    
    if (!$licences_exists) {
        $issues[] = "Licences table does not exist";
        return $issues;
    }
    
    // 1. Check responsable_id column
    $responsable_id_column = $wpdb->get_var("SHOW COLUMNS FROM $clubs_table LIKE 'responsable_id'");
    if (!$responsable_id_column) {
        $issues[] = "Missing responsable_id column in clubs table";
    } else {
        $fixes_applied[] = "✅ responsable_id column exists in clubs table";
    }
    
    // 2. Check column types for foreign key compatibility
    $clubs_id_info = $wpdb->get_row("SHOW COLUMNS FROM $clubs_table LIKE 'id'");
    $licences_club_id_info = $wpdb->get_row("SHOW COLUMNS FROM $licences_table LIKE 'club_id'");
    
    $clubs_id_type = $clubs_id_info ? strtolower(str_replace(' ', '', $clubs_id_info->Type)) : '';
    $licences_club_id_type = $licences_club_id_info ? strtolower(str_replace(' ', '', $licences_club_id_info->Type)) : '';
    
    if ($clubs_id_type !== $licences_club_id_type) {
        $issues[] = "Column type mismatch: clubs.id ({$clubs_id_info->Type}) vs licences.club_id ({$licences_club_id_info->Type})";
    } else if (strpos($clubs_id_type, 'mediumint(9)unsigned') !== false) {
        $fixes_applied[] = "✅ Column types are compatible for foreign key (both MEDIUMINT(9) UNSIGNED)";
    } else {
        $issues[] = "Column types are not MEDIUMINT(9) UNSIGNED: clubs.id ({$clubs_id_info->Type}), licences.club_id ({$licences_club_id_info->Type})";
    }
    
    // 3. Check foreign key constraint
    // Note: Table names cannot be prepared, but they are controlled by WordPress prefixes
    $foreign_keys = $wpdb->get_results($wpdb->prepare("
        SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = %s
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ", $licences_table));
    
    $has_fk = false;
    foreach ($foreign_keys as $fk) {
        if ($fk->REFERENCED_TABLE_NAME === $clubs_table && $fk->REFERENCED_COLUMN_NAME === 'id') {
            $has_fk = true;
            $fixes_applied[] = "✅ Foreign key constraint exists: {$fk->CONSTRAINT_NAME}";
            break;
        }
    }
    
    if (!$has_fk) {
        $issues[] = "Foreign key constraint missing between licences.club_id and clubs.id";
    }
    
    // 4. Test basic operations
    try {
        // Test if we can query both tables with JOIN (this would fail if foreign key is broken)
        $test_query = $wpdb->prepare("
            SELECT c.id, c.nom, COUNT(l.id) as licence_count 
            FROM $clubs_table c 
            LEFT JOIN $licences_table l ON c.id = l.club_id 
            WHERE c.id > %d 
            GROUP BY c.id 
            LIMIT 1
        ", 0);
        
        $wpdb->get_results($test_query);
        $fixes_applied[] = "✅ JOIN query between tables works correctly";
        
    } catch (Exception $e) {
        $issues[] = "Database JOIN operation failed: " . $e->getMessage();
    }
    
    return [
        'issues' => $issues,
        'fixes_applied' => $fixes_applied,
        'status' => empty($issues) ? 'success' : 'warning'
    ];
}

/**
 * Display validation results
 */
function ufsc_display_validation_results() {
    if (!current_user_can('manage_ufsc')) {
        wp_die('Access denied');
    }
    
    $results = ufsc_validate_database_schema();
    
    echo '<div class="wrap ufsc-ui">';
    echo '<h1>🔧 UFSC Database Schema Validation</h1>';
    
    if ($results['status'] === 'success') {
        echo '<div class="notice notice-success"><p><strong>✅ All database schema issues have been resolved!</strong></p></div>';
    } else {
        echo '<div class="notice notice-warning"><p><strong>⚠️ Some issues were found that may need attention.</strong></p></div>';
    }
    
    if (!empty($results['fixes_applied'])) {
        echo '<h2>✅ Fixes Applied Successfully</h2>';
        echo '<ul>';
        foreach ($results['fixes_applied'] as $fix) {
            echo '<li>' . esc_html($fix) . '</li>';
        }
        echo '</ul>';
    }
    
    if (!empty($results['issues'])) {
        echo '<h2>⚠️ Issues Found</h2>';
        echo '<ul>';
        foreach ($results['issues'] as $issue) {
            echo '<li style="color: #d63638;">' . esc_html($issue) . '</li>';
        }
        echo '</ul>';
        
        echo '<p><strong>Recommendation:</strong> Try deactivating and reactivating the plugin to trigger the database patches.</p>';
    }
    
    echo '<hr>';
    echo '<p><em>This validation was run on ' . current_time('Y-m-d H:i:s') . '</em></p>';
    echo '</div>';
}

// Hook for admin access
if (is_admin() && isset($_GET['ufsc_validate_db']) && $_GET['ufsc_validate_db'] === '1') {
    add_action('admin_init', function() {
        ufsc_display_validation_results();
        exit;
    });
}