<?php
/**
 * Tests for UFSC_Licence_List_Table filtering
 *
 * @package UFSC_Gestion_Club
 * @subpackage Tests
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Verify prepare_items handles filtered and unfiltered queries
 */
class Test_Licence_List_Table extends WP_UnitTestCase {
    /**
     * Seed database with sample clubs and licences
     */
    private function seed_data() {
        global $wpdb;

        $clubs_table    = $wpdb->prefix . 'ufsc_clubs';
        $licences_table = $wpdb->prefix . 'ufsc_licences';

        // Reset tables to ensure predictable results
        $wpdb->query( "TRUNCATE TABLE {$licences_table}" );
        $wpdb->query( "TRUNCATE TABLE {$clubs_table}" );

        $club_manager = UFSC_Club_Manager::get_instance();
        $club_id      = $club_manager->add_club(
            [
                'nom'   => 'Club List',
                'email' => 'club@example.com',
            ]
        );

        $wpdb->insert(
            $licences_table,
            [
                'club_id'         => $club_id,
                'nom'             => 'Doe',
                'prenom'          => 'John',
                'email'           => 'john@example.com',
                'statut'          => 'validee',
                'date_inscription'=> current_time( 'mysql' ),
            ]
        );

        $wpdb->insert(
            $licences_table,
            [
                'club_id'         => $club_id,
                'nom'             => 'Smith',
                'prenom'          => 'Anna',
                'email'           => 'anna@example.com',
                'statut'          => 'validee',
                'date_inscription'=> current_time( 'mysql' ),
            ]
        );
    }

    /**
     * Ensure unfiltered results return items
     */
    public function test_prepare_items_unfiltered() {
        if ( ! class_exists( 'UFSC_Licence_List_Table' ) ) {
            $this->markTestSkipped( 'List table class not available' );
        }

        $this->seed_data();

        $table = new UFSC_Licence_List_Table();
        $table->prepare_items();

        $this->assertGreaterThanOrEqual( 2, count( $table->items ), 'Expected unfiltered items' );
    }

    /**
     * Ensure filtered results return specific items
     */
    public function test_prepare_items_filtered() {
        if ( ! class_exists( 'UFSC_Licence_List_Table' ) ) {
            $this->markTestSkipped( 'List table class not available' );
        }

        $this->seed_data();
        $_REQUEST['s'] = 'Smith';

        $table = new UFSC_Licence_List_Table();
        $table->prepare_items();

        $this->assertCount( 1, $table->items );
        $this->assertEquals( 'Smith', $table->items[0]['nom'] );

        unset( $_REQUEST['s'] );
    }

    /**
     * Ensure set_external_data populates date_licence when missing
     */
    public function test_set_external_data_defaults_date_licence() {
        if ( ! class_exists( 'UFSC_Licence_List_Table' ) ) {
            $this->markTestSkipped( 'List table class not available' );
        }

        $table = new UFSC_Licence_List_Table();
        $date  = '2024-01-01 00:00:00';

        $data = [
            (object) [
                'id'               => 1,
                'nom'              => 'Doe',
                'prenom'           => 'John',
                'email'            => 'john@example.com',
                'statut'           => 'validee',
                'date_inscription' => $date,
            ],
        ];

        $table->set_external_data( $data, 1, 20 );
        $table->prepare_items();

        $this->assertArrayHasKey( 'date_licence', $table->items[0] );
        $this->assertEquals( $date, $table->items[0]['date_licence'] );
    }
}

