<?php
/**
 * Ensure that the frontend-pro.js asset is present.
 *
 * @package UFSC_Gestion_Club
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class Test_UFSC_Frontend_Pro_Asset extends WP_UnitTestCase {
    /**
     * Test that the frontend-pro.js file exists.
     */
    public function test_frontend_pro_js_exists() {
        $path = dirname(__DIR__, 2) . '/assets/js/frontend-pro.js';
        $this->assertFileExists($path, 'The frontend-pro.js file is missing.');
    }
}
