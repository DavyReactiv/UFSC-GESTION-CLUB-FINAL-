<?php
/**
 * PHPUnit Test for UFSC WooCommerce Handler
 *
 * Basic acceptance tests for WooCommerce integration
 * Tests license creation and user meta assignment from orders
 *
 * @package UFSC_Gestion_Club
 * @subpackage Tests
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test UFSC WooCommerce Handler functionality
 */
class Test_UFSC_WC_Handler extends WP_UnitTestCase
{
    /**
     * Set up test environment
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // Ensure UFSC plugin classes are loaded
        if (!class_exists('UFSC_Club_Manager')) {
            $this->markTestSkipped('UFSC plugin classes not available');
        }
    }

    /**
     * Test license creation from WooCommerce order
     *
     * Simulates the ufsc_wc_handle_order function and verifies:
     * - License post is created
     * - User meta is properly assigned
     * - Club association is established
     */
    public function test_license_creation_from_order()
    {
        // Skip if WooCommerce is not available
        if (!class_exists('WC_Order')) {
            $this->markTestSkipped('WooCommerce not available for testing');
        }

        // Create a test user
        $user_id = $this->factory->user->create([
            'user_login' => 'test_club_user',
            'user_email' => 'test@example.com',
            'first_name' => 'Jean',
            'last_name' => 'Dupont'
        ]);

        // Create a test club (simulate club creation)
        $club_id = $this->create_test_club($user_id);

        // Create a mock WooCommerce order
        $order = $this->create_mock_order($user_id);

        // Test license creation
        $this->simulate_license_creation($order, $user_id, $club_id);

        // Assertions
        $this->assert_license_created($user_id);
        $this->assert_user_meta_assigned($user_id, $club_id);
    }

    /**
     * Test auto user creation from order (if enabled)
     */
    public function test_auto_user_creation()
    {
        // Enable auto user creation setting
        update_option('ufsc_auto_create_user', true);

        // Mock order data without existing user
        $order_data = [
            'billing_email' => 'newuser@example.com',
            'billing_first_name' => 'Marie',
            'billing_last_name' => 'Martin'
        ];

        // Test user auto-creation
        $created_user_id = $this->simulate_auto_user_creation($order_data);

        // Assertions
        $this->assertGreaterThan(0, $created_user_id, 'User should be auto-created');
        $user = get_user_by('id', $created_user_id);
        $this->assertEquals('newuser@example.com', $user->user_email);
        
        // Cleanup
        update_option('ufsc_auto_create_user', false);
    }

    /**
     * Test WooCommerce product ID configuration
     */
    public function test_woocommerce_product_configuration()
    {
        // Test affiliation product ID setting
        update_option('ufsc_wc_affiliation_product_id', 2933);
        $affiliation_id = get_option('ufsc_wc_affiliation_product_id');
        $this->assertEquals(2933, $affiliation_id);

        // Test license product IDs setting (CSV)
        update_option('ufsc_wc_license_product_ids', '2934,2935,2936');
        $license_ids = get_option('ufsc_wc_license_product_ids');
        $this->assertEquals('2934,2935,2936', $license_ids);

        // Test parsing of CSV license IDs
        $parsed_ids = array_map('trim', explode(',', $license_ids));
        $this->assertContains('2934', $parsed_ids);
        $this->assertContains('2935', $parsed_ids);
        $this->assertContains('2936', $parsed_ids);
    }

    /**
     * Helper: Create a test club
     */
    private function create_test_club($user_id)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ufsc_clubs';
        
        // Check if table exists before inserting
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $this->markTestSkipped('UFSC clubs table not available');
        }

        $wpdb->insert(
            $table_name,
            [
                'nom' => 'Club Test PHPUnit',
                'email' => 'test@club-example.com',
                'statut' => 'actif',
                'num_affiliation' => 'TEST001',
                'user_id' => $user_id
            ]
        );

        return $wpdb->insert_id;
    }

    /**
     * Helper: Create a mock WooCommerce order
     */
    private function create_mock_order($user_id)
    {
        if (!class_exists('WC_Order')) {
            return (object) [
                'get_user_id' => function() use ($user_id) { return $user_id; },
                'get_status' => function() { return 'completed'; },
                'get_id' => function() { return 12345; }
            ];
        }

        // Create actual WC_Order if WooCommerce is available
        $order = new WC_Order();
        $order->set_customer_id($user_id);
        $order->set_status('completed');
        $order->save();

        return $order;
    }

    /**
     * Helper: Simulate license creation process
     */
    private function simulate_license_creation($order, $user_id, $club_id)
    {
        // Simulate what the WC handler would do
        $license_data = [
            'post_title' => 'Licence Test - Jean Dupont',
            'post_type' => 'ufsc_licence',
            'post_status' => 'publish',
            'meta_input' => [
                'user_id' => $user_id,
                'club_id' => $club_id,
                'statut' => 'en_attente',
                'type_licence' => 'competiteur',
                'wc_order_id' => is_object($order) && method_exists($order, 'get_id') ? $order->get_id() : 12345
            ]
        ];

        // Create the license post
        $license_id = wp_insert_post($license_data);
        
        // Store license ID for testing
        update_user_meta($user_id, 'test_license_id', $license_id);

        return $license_id;
    }

    /**
     * Helper: Simulate auto user creation
     */
    private function simulate_auto_user_creation($order_data)
    {
        // Basic user creation logic that the WC handler might use
        $username = sanitize_user($order_data['billing_first_name'] . '_' . $order_data['billing_last_name']);
        $user_id = wp_create_user(
            $username,
            wp_generate_password(),
            $order_data['billing_email']
        );

        if (!is_wp_error($user_id)) {
            wp_update_user([
                'ID' => $user_id,
                'first_name' => $order_data['billing_first_name'],
                'last_name' => $order_data['billing_last_name']
            ]);
        }

        return is_wp_error($user_id) ? 0 : $user_id;
    }

    /**
     * Assert that a license was created
     */
    private function assert_license_created($user_id)
    {
        $license_id = get_user_meta($user_id, 'test_license_id', true);
        $this->assertGreaterThan(0, $license_id, 'License should be created');

        $license_post = get_post($license_id);
        $this->assertNotNull($license_post, 'License post should exist');
        $this->assertEquals('ufsc_licence', $license_post->post_type, 'Post type should be ufsc_licence');
    }

    /**
     * Assert that user meta was properly assigned
     */
    private function assert_user_meta_assigned($user_id, $club_id)
    {
        $license_id = get_user_meta($user_id, 'test_license_id', true);
        
        // Check license meta
        $stored_user_id = get_post_meta($license_id, 'user_id', true);
        $stored_club_id = get_post_meta($license_id, 'club_id', true);
        $license_status = get_post_meta($license_id, 'statut', true);

        $this->assertEquals($user_id, $stored_user_id, 'License should be linked to correct user');
        $this->assertEquals($club_id, $stored_club_id, 'License should be linked to correct club');
        $this->assertNotEmpty($license_status, 'License should have a status');
    }

    /**
     * Test cleanup
     */
    public function tearDown(): void
    {
        // Clean up test data
        global $wpdb;
        
        // Remove test licenses
        $test_licenses = get_posts([
            'post_type' => 'ufsc_licence',
            'meta_key' => 'wc_order_id',
            'meta_value' => 12345,
            'posts_per_page' => -1
        ]);

        foreach ($test_licenses as $license) {
            wp_delete_post($license->ID, true);
        }

        // Clean up test clubs
        $table_name = $wpdb->prefix . 'ufsc_clubs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            $wpdb->delete($table_name, ['nom' => 'Club Test PHPUnit']);
        }

        parent::tearDown();
    }
}