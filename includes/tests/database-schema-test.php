<?php
/**
 * Test file for database schema fixes
 * This file tests the foreign key constraint and responsable_id column fixes
 * 
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for database schema fixes
 */
class UFSC_Database_Schema_Test
{
    /**
     * Run all database schema tests
     */
    public static function run_tests()
    {
        $results = [];
        
        $results[] = self::test_clubs_table_structure();
        $results[] = self::test_licences_table_structure();
        $results[] = self::test_responsable_id_column();
        $results[] = self::test_foreign_key_compatibility();
        $results[] = self::test_foreign_key_constraint();
        
        return $results;
    }
    
    /**
     * Test clubs table has correct structure
     */
    private static function test_clubs_table_structure()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ufsc_clubs';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if (!$table_exists) {
            return [
                'test' => 'Clubs Table Structure',
                'passed' => false,
                'message' => 'Clubs table does not exist'
            ];
        }
        
        // Check id column type
        $id_column = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'id'");
        $id_correct = $id_column && stripos($id_column->Type, 'mediumint(9) unsigned') !== false;
        
        return [
            'test' => 'Clubs Table Structure',
            'passed' => $id_correct,
            'message' => $id_correct 
                ? 'Clubs table id column has correct type: ' . $id_column->Type
                : 'Clubs table id column has incorrect type: ' . ($id_column ? $id_column->Type : 'column missing')
        ];
    }
    
    /**
     * Test licences table has correct structure
     */
    private static function test_licences_table_structure()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ufsc_licences';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        if (!$table_exists) {
            return [
                'test' => 'Licences Table Structure',
                'passed' => false,
                'message' => 'Licences table does not exist'
            ];
        }
        
        // Check club_id column type
        $club_id_column = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'club_id'");
        $club_id_correct = $club_id_column && stripos($club_id_column->Type, 'mediumint(9) unsigned') !== false;
        
        return [
            'test' => 'Licences Table Structure',
            'passed' => $club_id_correct,
            'message' => $club_id_correct 
                ? 'Licences table club_id column has correct type: ' . $club_id_column->Type
                : 'Licences table club_id column has incorrect type: ' . ($club_id_column ? $club_id_column->Type : 'column missing')
        ];
    }
    
    /**
     * Test responsable_id column exists in clubs table
     */
    private static function test_responsable_id_column()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ufsc_clubs';
        
        // Check if responsable_id column exists
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'responsable_id'");
        
        if (!$column_exists) {
            return [
                'test' => 'Responsable ID Column',
                'passed' => false,
                'message' => 'responsable_id column is missing from clubs table'
            ];
        }
        
        // Check column type
        $column_info = $wpdb->get_row("SHOW COLUMNS FROM $table_name LIKE 'responsable_id'");
        $type_correct = $column_info && (
            stripos($column_info->Type, 'int(11)') !== false || 
            stripos($column_info->Type, 'int') !== false
        );
        
        return [
            'test' => 'Responsable ID Column',
            'passed' => $type_correct,
            'message' => $type_correct 
                ? 'responsable_id column exists with correct type: ' . $column_info->Type
                : 'responsable_id column has incorrect type: ' . ($column_info ? $column_info->Type : 'unknown')
        ];
    }
    
    /**
     * Test foreign key compatibility between tables
     */
    private static function test_foreign_key_compatibility()
    {
        global $wpdb;
        
        $clubs_table = $wpdb->prefix . 'ufsc_clubs';
        $licences_table = $wpdb->prefix . 'ufsc_licences';
        
        // Get column types
        $clubs_id_column = $wpdb->get_row("SHOW COLUMNS FROM $clubs_table LIKE 'id'");
        $licences_club_id_column = $wpdb->get_row("SHOW COLUMNS FROM $licences_table LIKE 'club_id'");
        
        if (!$clubs_id_column || !$licences_club_id_column) {
            return [
                'test' => 'Foreign Key Compatibility',
                'passed' => false,
                'message' => 'One or both columns are missing'
            ];
        }
        
        // Check if types match (both should be mediumint(9) unsigned)
        $clubs_type = strtolower(str_replace(' ', '', $clubs_id_column->Type));
        $licences_type = strtolower(str_replace(' ', '', $licences_club_id_column->Type));
        
        $types_match = $clubs_type === $licences_type;
        
        return [
            'test' => 'Foreign Key Compatibility',
            'passed' => $types_match,
            'message' => $types_match 
                ? "Column types match: clubs.id ({$clubs_id_column->Type}) = licences.club_id ({$licences_club_id_column->Type})"
                : "Column types don't match: clubs.id ({$clubs_id_column->Type}) ≠ licences.club_id ({$licences_club_id_column->Type})"
        ];
    }
    
    /**
     * Test foreign key constraint exists and works
     */
    private static function test_foreign_key_constraint()
    {
        global $wpdb;
        
        $licences_table = $wpdb->prefix . 'ufsc_licences';
        
        // Check if foreign key constraint exists
        $foreign_keys = $wpdb->get_results("
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$licences_table' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $has_constraint = false;
        $constraint_details = '';
        
        foreach ($foreign_keys as $fk) {
            if (strpos($fk->CONSTRAINT_NAME, 'fk_licence_club') !== false || 
                $fk->REFERENCED_TABLE_NAME === $wpdb->prefix . 'ufsc_clubs') {
                $has_constraint = true;
                $constraint_details = "Constraint: {$fk->CONSTRAINT_NAME} references {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}";
                break;
            }
        }
        
        return [
            'test' => 'Foreign Key Constraint',
            'passed' => $has_constraint,
            'message' => $has_constraint 
                ? "Foreign key constraint exists: $constraint_details"
                : 'Foreign key constraint is missing'
        ];
    }
    
    /**
     * Display test results
     */
    public static function display_results($results)
    {
        echo "<div style='background: #fff; padding: 20px; margin: 20px; border: 1px solid #ddd;'>";
        echo "<h2>🔧 UFSC Database Schema Test Results</h2>";
        
        $total_tests = count($results);
        $passed_tests = count(array_filter($results, function($r) { return $r['passed']; }));
        
        echo "<p><strong>Overall: {$passed_tests}/{$total_tests} tests passed</strong></p>";
        
        foreach ($results as $result) {
            $icon = $result['passed'] ? '✅' : '❌';
            $style = $result['passed'] ? 'color: green;' : 'color: red;';
            
            echo "<div style='margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid " . ($result['passed'] ? 'green' : 'red') . ";'>";
            echo "<strong style='{$style}'>{$icon} {$result['test']}</strong><br>";
            echo "<span>{$result['message']}</span>";
            echo "</div>";
        }
        
        if ($passed_tests === $total_tests) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; margin-top: 20px;'>";
            echo "<strong>🎉 All tests passed! Database schema is correctly configured.</strong>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; margin-top: 20px;'>";
            echo "<strong>⚠️ Some tests failed. Database schema needs attention.</strong>";
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    /**
     * Trigger database table creation (for testing)
     */
    public static function trigger_table_creation()
    {
        if (class_exists('UFSC_Club_Manager')) {
            $manager = UFSC_Club_Manager::get_instance();
            $manager->create_table();
            return true;
        }
        return false;
    }
}

// Auto-run tests if this file is accessed directly (for development)
// Use proper WordPress hooks to avoid calling functions before WordPress is loaded
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_loaded', function() {
        // Check user capabilities only after WordPress is fully loaded
        if (isset($_GET['run_ufsc_schema_tests']) && ufsc_safe_current_user_can('ufsc_manage')) {
            // Trigger table creation first
            if (isset($_GET['create_tables'])) {
                UFSC_Database_Schema_Test::trigger_table_creation();
                echo "<div style='background: #d1ecf1; color: #0c5460; padding: 15px; margin: 20px; border: 1px solid #bee5eb; border-radius: 4px;'>";
                echo "<strong>📋 Tables created/updated. Running tests...</strong>";
                echo "</div>";
            }
            
            $results = UFSC_Database_Schema_Test::run_tests();
            UFSC_Database_Schema_Test::display_results($results);
            exit;
        }
    });
}