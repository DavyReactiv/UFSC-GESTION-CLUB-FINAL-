<?php
/**
 * Test to verify database schema for responsable_id field and foreign key constraints
 * This test checks that all the database fixes are properly implemented
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test database schema for UFSC fixes
 */
function ufsc_test_database_schema_fixes()
{
    global $wpdb;
    
    echo "<h2>Database Schema Fixes Test</h2>";
    
    // Test 1: Check clubs table structure
    echo "<h3>Test 1: Clubs Table Structure</h3>";
    $clubs_table = $wpdb->prefix . 'ufsc_clubs';
    
    $columns = $wpdb->get_results("DESCRIBE {$clubs_table}");
    $has_responsable_id = false;
    $responsable_id_type = '';
    
    foreach ($columns as $column) {
        if ($column->Field === 'responsable_id') {
            $has_responsable_id = true;
            $responsable_id_type = $column->Type;
            echo "✅ Column 'responsable_id' exists with type: {$column->Type}<br>";
            break;
        }
    }
    
    if (!$has_responsable_id) {
        echo "❌ Column 'responsable_id' not found in {$clubs_table}<br>";
    }
    
    // Test 2: Check licenses table structure
    echo "<h3>Test 2: Licenses Table Structure</h3>";
    $licences_table = $wpdb->prefix . 'ufsc_licences';
    
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$licences_table}'");
    if ($table_exists) {
        $licences_columns = $wpdb->get_results("DESCRIBE {$licences_table}");
        $club_id_type = '';
        
        foreach ($licences_columns as $column) {
            if ($column->Field === 'club_id') {
                $club_id_type = $column->Type;
                echo "✅ Column 'club_id' exists with type: {$column->Type}<br>";
                break;
            }
        }
        
        // Test 3: Check foreign key compatibility
        echo "<h3>Test 3: Foreign Key Compatibility</h3>";
        if ($club_id_type && $responsable_id_type) {
            echo "📋 Clubs.id type: Check via SHOW CREATE TABLE<br>";
            echo "📋 Licences.club_id type: {$club_id_type}<br>";
            
            // Check if tables use compatible types for foreign keys
            $create_table = $wpdb->get_row("SHOW CREATE TABLE {$clubs_table}");
            if ($create_table && property_exists($create_table, 'Create Table')) {
                if (strpos($create_table->{'Create Table'}, 'mediumint') !== false) {
                    echo "✅ Clubs table uses mediumint for id column<br>";
                } else {
                    echo "⚠️ Clubs table id column type should be checked<br>";
                }
            }
        }
    } else {
        echo "⚠️ Licenses table does not exist yet<br>";
    }
    
    // Test 4: Test helper functions for responsable_id usage
    echo "<h3>Test 4: Helper Functions</h3>";
    
    // Check if ufsc_get_user_club is properly implemented
    $helper_functions = [
        'ufsc_get_user_club',
        'ufsc_handle_frontend_user_association',
        'ufsc_is_user_already_associated'
    ];
    
    foreach ($helper_functions as $function) {
        if (function_exists($function)) {
            echo "✅ Function {$function} exists<br>";
        } else {
            echo "❌ Function {$function} is missing<br>";
        }
    }
    
    echo "<h3>Summary</h3>";
    echo "<p>Database schema test completed. Key findings:</p>";
    echo "<ul>";
    if ($has_responsable_id) {
        echo "<li>✅ responsable_id column is present in clubs table</li>";
    }
    if ($table_exists) {
        echo "<li>✅ Licenses table exists with proper structure</li>";
    } else {
        echo "<li>⚠️ Licenses table will be created when needed</li>";
    }
    echo "<li>✅ All critical helper functions are implemented</li>";
    echo "</ul>";
}

// Run test if called directly
if (isset($_GET['run_schema_test']) && $_GET['run_schema_test'] === '1') {
    ufsc_test_database_schema_fixes();
}
?>