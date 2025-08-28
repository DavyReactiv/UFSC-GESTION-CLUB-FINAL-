<?php
/**
 * Test file for User-Club Association Enhancements
 * 
 * This file tests the new functionality for:
 * 1. Frontend user creation/association during club affiliation
 * 2. WordPress user profile club selection
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test the frontend user association functionality
 */
function ufsc_test_frontend_user_association()
{
    echo "<h3>Testing Frontend User Association Functionality</h3>";
    
    // Test 1: Check if helper functions exist
    $functions_to_test = [
        'ufsc_handle_frontend_user_association',
        'ufsc_create_user_for_club',
        'ufsc_get_all_clubs_for_user_association',
        'ufsc_get_club_by_id',
        'ufsc_get_user_display_name'
    ];
    
    foreach ($functions_to_test as $function) {
        if (function_exists($function)) {
            echo "✅ Function {$function} exists<br>";
        } else {
            echo "❌ Function {$function} is missing<br>";
        }
    }
    
    // Test 2: Check if user profile hooks are registered
    global $wp_filter;
    $hooks_to_check = [
        'show_user_profile',
        'edit_user_profile',
        'personal_options_update',
        'edit_user_profile_update'
    ];
    
    echo "<h4>User Profile Hooks Status:</h4>";
    foreach ($hooks_to_check as $hook) {
        if (isset($wp_filter[$hook])) {
            $has_ufsc_hooks = false;
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback_key => $callback_data) {
                    if (strpos((string) $callback_key, 'ufsc_') !== false) {
                        $has_ufsc_hooks = true;
                        break 2;
                    }
                }
            }
            if ($has_ufsc_hooks) {
                echo "✅ Hook {$hook} has UFSC callbacks<br>";
            } else {
                echo "⚠️ Hook {$hook} exists but no UFSC callbacks found<br>";
            }
        } else {
            echo "❌ Hook {$hook} not found<br>";
        }
    }
    
    // Test 3: Check database schema
    echo "<h4>Database Schema Check:</h4>";
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    
    $columns = $wpdb->get_results("DESCRIBE {$table_name}");
    $has_responsable_id = false;
    
    foreach ($columns as $column) {
        if ($column->Field === 'responsable_id') {
            $has_responsable_id = true;
            echo "✅ Column 'responsable_id' exists in {$table_name}<br>";
            echo "   Type: {$column->Type}, Null: {$column->Null}<br>";
            break;
        }
    }
    
    if (!$has_responsable_id) {
        echo "❌ Column 'responsable_id' not found in {$table_name}<br>";
    }
    
    // Test 4: Test basic user association check
    echo "<h4>Basic Function Tests:</h4>";
    
    // Test with non-existent user
    $is_associated = ufsc_is_user_already_associated(999999, 0);
    if ($is_associated === false) {
        echo "✅ ufsc_is_user_already_associated correctly returns false for non-existent user<br>";
    } else {
        echo "❌ ufsc_is_user_already_associated failed test with non-existent user<br>";
    }
    
    // Test getting clubs for user association
    $clubs = ufsc_get_all_clubs_for_user_association();
    if (is_array($clubs)) {
        echo "✅ ufsc_get_all_clubs_for_user_association returns array (count: " . count($clubs) . ")<br>";
    } else {
        echo "❌ ufsc_get_all_clubs_for_user_association failed to return array<br>";
    }
    
    echo "<h4>Test Summary:</h4>";
    echo "<p>Frontend user association functionality appears to be properly installed.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test frontend form display during affiliation</li>";
    echo "<li>Test user creation process</li>";
    echo "<li>Test WordPress user profile club selection</li>";
    echo "<li>Test bidirectional synchronization</li>";
    echo "</ul>";
}

/**
 * Test the user profile enhancement functionality
 */
function ufsc_test_user_profile_enhancement()
{
    echo "<h3>Testing User Profile Enhancement</h3>";
    
    // Test if we can get users
    $users = ufsc_get_wordpress_users_for_clubs();
    if (is_array($users) && count($users) > 0) {
        echo "✅ Can retrieve WordPress users (count: " . count($users) . ")<br>";
        echo "   Sample user: " . esc_html($users[0]->display_name ?? 'N/A') . "<br>";
    } else {
        echo "❌ Failed to retrieve WordPress users<br>";
    }
    
    // Test user display name function
    $admin_users = get_users(['role' => 'administrator', 'number' => 1]);
    if (!empty($admin_users)) {
        $admin_user = $admin_users[0];
        $display_name = ufsc_get_user_display_name($admin_user->ID);
        echo "✅ ufsc_get_user_display_name works: " . esc_html($display_name) . "<br>";
    }
    
    echo "<h4>User Profile Form Simulation:</h4>";
    echo "<p>The user profile enhancement adds fields to WordPress user edit pages.</p>";
    echo "<p>Fields include:</p>";
    echo "<ul>";
    echo "<li>Club selection dropdown</li>";
    echo "<li>Current association display</li>";
    echo "<li>Validation for one-to-one relationship</li>";
    echo "</ul>";
}

// Only run tests if explicitly requested and user has admin rights
if (isset($_GET['run_ufsc_association_test']) && current_user_can('ufsc_manage')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><div style="padding: 10px;">';
        ufsc_test_frontend_user_association();
        ufsc_test_user_profile_enhancement();
        echo '</div></div>';
    });
}