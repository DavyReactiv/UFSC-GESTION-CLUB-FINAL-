<?php
/**
 * Tests for UFSC Club Form Shortcode visibility based on user login status.
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test the club form shortcode behaviour.
 */
class Test_UFSC_Club_Form_Shortcode extends WP_UnitTestCase
{
    /**
     * Post ID used for permalink generation.
     *
     * @var int
     */
    protected $post_id;

    /**
     * Set up test environment.
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!function_exists('ufsc_formulaire_club_shortcode')) {
            $this->markTestSkipped('UFSC club form shortcode not available');
        }

        // Create a post to ensure get_permalink() returns a valid URL.
        $this->post_id = $this->factory->post->create();
        $GLOBALS['post'] = get_post($this->post_id);
    }

    /**
     * Ensure no user remains logged in after tests.
     */
    public function tearDown(): void
    {
        wp_set_current_user(0);
        parent::tearDown();
    }

    /**
     * Test that non-logged-in users see the login/registration form.
     */
    public function test_non_logged_in_sees_login_register_form()
    {
        wp_set_current_user(0);
        $output = ufsc_formulaire_club_shortcode([]);

        $this->assertStringContainsString('ufsc-login-register-wrapper', $output);
        $this->assertStringNotContainsString('ufsc_club_nonce', $output);
    }

    /**
     * Test that logged-in users see the club form.
     */
    public function test_logged_in_sees_club_form()
    {
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);

        $output = ufsc_formulaire_club_shortcode([]);

        $this->assertStringContainsString('ufsc_club_nonce', $output);
        $this->assertStringNotContainsString('ufsc-login-register-wrapper', $output);
    }
}

