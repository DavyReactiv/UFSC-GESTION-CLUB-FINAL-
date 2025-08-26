<?php
/**
 * Dashboard MVP Test Script
 * 
 * Test script to verify the dashboard MVP implementation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('This is a WordPress plugin file. It cannot be executed directly.');
}

/**
 * Test the dashboard MVP implementation
 */
function ufsc_test_dashboard_mvp() {
    ?>
    <div class="wrap">
        <h1>Dashboard MVP Test</h1>
        
        <h2>1. Test Dashboard Shortcode</h2>
        <?php
        // Test the shortcode function
        if (function_exists('ufsc_club_dashboard_mvp_shortcode')) {
            echo '<p style="color: green;">✓ Dashboard MVP shortcode function exists</p>';
        } else {
            echo '<p style="color: red;">✗ Dashboard MVP shortcode function missing</p>';
        }
        ?>
        
        <h2>2. Test Helper Functions</h2>
        <?php
        $helper_functions = [
            'ufsc_get_club_stats',
            'ufsc_get_club_detailed_stats',
            'ufsc_get_quota_pack_info',
            'ufsc_resolve_document_url',
            'ufsc_render_club_status_badge',
            'ufsc_render_license_status_badge'
        ];
        
        foreach ($helper_functions as $function) {
            if (function_exists($function)) {
                echo '<p style="color: green;">✓ ' . $function . ' exists</p>';
            } else {
                echo '<p style="color: red;">✗ ' . $function . ' missing</p>';
            }
        }
        ?>
        
        <h2>3. Test Asset Files</h2>
        <?php
        $asset_files = [
            'assets/css/dashboard-mvp.css',
            'assets/js/ufsc-club-logo.js',
            'includes/frontend/ajax/logo-upload.php'
        ];
        
        foreach ($asset_files as $file) {
            $full_path = UFSC_PLUGIN_PATH . $file;
            if (file_exists($full_path)) {
                echo '<p style="color: green;">✓ ' . $file . ' exists</p>';
            } else {
                echo '<p style="color: red;">✗ ' . $file . ' missing</p>';
            }
        }
        ?>
        
        <h2>4. Test Dummy Dashboard Render</h2>
        <?php
        // Create dummy club object for testing
        $dummy_club = (object) [
            'id' => 1,
            'nom' => 'Club Test UFSC',
            'statut' => 'Actif',
            'num_affiliation' => 'TEST001',
            'logo_attachment_id' => '',
            'logo_url' => '',
            'quota_licences' => 10,
            'doc_attestation_affiliation' => '',
            'doc_attestation_assurance' => ''
        ];
        
        // Test statistics functions with dummy data
        try {
            $stats = ufsc_get_club_stats($dummy_club->id);
            echo '<p style="color: green;">✓ Club stats function works</p>';
            echo '<pre>Stats: ' . print_r($stats, true) . '</pre>';
        } catch (Exception $e) {
            echo '<p style="color: red;">✗ Club stats function error: ' . $e->getMessage() . '</p>';
        }
        
        try {
            $detailed_stats = ufsc_get_club_detailed_stats($dummy_club->id);
            echo '<p style="color: green;">✓ Detailed stats function works</p>';
        } catch (Exception $e) {
            echo '<p style="color: red;">✗ Detailed stats function error: ' . $e->getMessage() . '</p>';
        }
        
        try {
            $quota_info = ufsc_get_quota_pack_info($dummy_club->id);
            echo '<p style="color: green;">✓ Quota pack function works</p>';
        } catch (Exception $e) {
            echo '<p style="color: red;">✗ Quota pack function error: ' . $e->getMessage() . '</p>';
        }
        ?>
        
        <h2>5. Test Badge Rendering</h2>
        <?php
        $statuses = ['Actif', 'En cours de validation', 'Refusé', 'Autre'];
        foreach ($statuses as $status) {
            echo '<p>Status "' . $status . '": ' . ufsc_render_club_status_badge($status) . '</p>';
        }
        ?>
        
        <h2>6. Dashboard Preview (Basic)</h2>
        <div style="border: 1px solid #ccc; padding: 20px; background: #f9f9f9;">
            <p><strong>Note:</strong> This is a basic preview without full WordPress context</p>
            <?php
            // Enqueue assets for preview
            ufsc_enqueue_dashboard_mvp_assets();
            ?>
            <div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
                <h3>🏢 Club Test UFSC <span class="ufsc-badge ufsc-badge-success">Validé</span></h3>
                <p>N° Affiliation: TEST001</p>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px;">📊</div>
                        <div style="font-size: 24px; font-weight: bold; color: #333;">0</div>
                        <div style="color: #666; font-size: 14px;">Licences totales</div>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px;">✅</div>
                        <div style="font-size: 24px; font-weight: bold; color: #333;">0</div>
                        <div style="color: #666; font-size: 14px;">Licences actives</div>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <div style="font-size: 24px;">🎯</div>
                        <div style="font-size: 24px; font-weight: bold; color: #333;">10</div>
                        <div style="color: #666; font-size: 14px;">Licences disponibles</div>
                    </div>
                </div>
            </div>
        </div>
        
        <h2>7. Integration Check</h2>
        <?php
        // Check if shortcode is registered
        global $shortcode_tags;
        if (isset($shortcode_tags['ufsc_club_dashboard'])) {
            echo '<p style="color: green;">✓ Shortcode [ufsc_club_dashboard] is registered</p>';
        } else {
            echo '<p style="color: red;">✗ Shortcode [ufsc_club_dashboard] not registered</p>';
        }
        
        // Check if AJAX actions are registered
        if (has_action('wp_ajax_ufsc_set_club_logo')) {
            echo '<p style="color: green;">✓ AJAX action ufsc_set_club_logo is registered</p>';
        } else {
            echo '<p style="color: red;">✗ AJAX action ufsc_set_club_logo not registered</p>';
        }
        ?>
        
    </div>
    <?php
}

// If we're in admin and this is called directly, run the test
if (is_admin() && isset($_GET['test_dashboard_mvp'])) {
    add_action('admin_notices', function() {
        ufsc_test_dashboard_mvp();
    });
}