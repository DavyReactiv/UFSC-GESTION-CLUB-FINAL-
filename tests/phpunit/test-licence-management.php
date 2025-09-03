<?php
/**
 * Tests for licence management and WooCommerce synchronization
 *
 * @package UFSC_Gestion_Club
 * @subpackage Tests
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Provide stub for wc_get_order if WooCommerce not installed
if ( ! function_exists( 'wc_get_order' ) ) {
    function wc_get_order( $order_id ) {
        return Test_Licence_Management::$mock_order;
    }
}

/**
 * Licence management tests
 */
class Test_Licence_Management extends WP_UnitTestCase {
    public static $mock_order;

    /**
     * Set up environment
     */
    public function setUp(): void {
        parent::setUp();

        if ( ! class_exists( 'UFSC_Club_Manager' ) || ! class_exists( 'UFSC_Licence_Manager' ) ) {
            $this->markTestSkipped( 'UFSC plugin classes not available' );
        }
    }

    /**
     * Test licence creation and cancellation
     */
    public function test_licence_creation_and_cancellation() {
        $club_manager    = UFSC_Club_Manager::get_instance();
        $licence_manager = new UFSC_Licence_Manager();

        // Create test club
        $club_id = $club_manager->add_club( [
            'nom'   => 'Club Test Gestion',
            'email' => 'club@example.com',
        ] );
        $this->assertGreaterThan( 0, $club_id );

        // Use helper for status options
        $statuses = UFSC_CSV_Export::get_status_options();
        $this->assertArrayHasKey( 'en_attente', $statuses );

        // Create licence
        $licence_id = $licence_manager->create_licence( [
            'club_id'    => $club_id,
            'nom'        => 'Doe',
            'prenom'     => 'John',
            'sexe'       => 'M',
            'date_naissance' => '2000-01-01',
            'email'      => 'john@example.com',
            'adresse'    => '1 rue test',
            'suite_adresse' => '',
            'code_postal' => '75000',
            'ville'      => 'Paris',
            'tel_fixe'   => '',
            'tel_mobile' => '0600000000',
            'reduction_benevole' => 0,
            'reduction_postier'  => 0,
            'identifiant_laposte' => '',
            'identifiant_laposte_flag' => 0,
            'profession' => '',
            'fonction_publique' => 0,
            'competition' => 0,
            'licence_delegataire' => 0,
            'numero_licence_delegataire' => '',
            'diffusion_image' => 0,
            'infos_fsasptt' => 0,
            'infos_asptt' => 0,
            'infos_cr' => 0,
            'infos_partenaires' => 0,
            'honorabilite' => 1,
            'assurance_dommage_corporel' => 1,
            'assurance_assistance' => 1,
            'note' => '',
            'region' => 'Île-de-France',
            'statut' => 'en_attente',
            'is_included' => 0,
        ] );

        $this->assertGreaterThan( 0, $licence_id, 'Licence should be created' );

        $licence = $licence_manager->get_licence_by_id( $licence_id );
        $this->assertEquals( 'en_attente', $licence->statut );

        // Cancel licence
        $licence_manager->update_licence_status( $licence_id, 'revoked' );
        $updated = $licence_manager->get_licence_by_id( $licence_id );
        $this->assertEquals( 'revoked', $updated->statut );
    }

    /**
     * Test WooCommerce order cancellation sync
     */
    public function test_woocommerce_cancellation_sync() {
        // Prepare licence and order
        $club_manager    = UFSC_Club_Manager::get_instance();
        $licence_manager = new UFSC_Licence_Manager();

        $club_id = $club_manager->add_club( [
            'nom'   => 'Club Sync',
            'email' => 'sync@example.com',
        ] );

        $licence_id = $licence_manager->create_licence( [
            'club_id' => $club_id,
            'nom'     => 'Smith',
            'prenom'  => 'Anna',
            'sexe'    => 'F',
            'date_naissance' => '1990-01-01',
            'email'   => 'anna@example.com',
            'adresse' => '2 rue sync',
            'suite_adresse' => '',
            'code_postal' => '69000',
            'ville'   => 'Lyon',
            'tel_fixe' => '',
            'tel_mobile' => '0700000000',
            'reduction_benevole' => 0,
            'reduction_postier'  => 0,
            'identifiant_laposte' => '',
            'identifiant_laposte_flag' => 0,
            'profession' => '',
            'fonction_publique' => 0,
            'competition' => 0,
            'licence_delegataire' => 0,
            'numero_licence_delegataire' => '',
            'diffusion_image' => 0,
            'infos_fsasptt' => 0,
            'infos_asptt' => 0,
            'infos_cr' => 0,
            'infos_partenaires' => 0,
            'honorabilite' => 1,
            'assurance_dommage_corporel' => 1,
            'assurance_assistance' => 1,
            'note' => '',
            'region' => 'Auvergne-Rhône-Alpes',
            'statut' => 'validee',
            'is_included' => 0,
        ] );

        $this->assertGreaterThan( 0, $licence_id );

        // Mock WooCommerce order structure
        $item = new class( $licence_id ) {
            private $licence_id;
            public function __construct( $licence_id ) { $this->licence_id = $licence_id; }
            public function get_meta( $key ) {
                return $key === 'ufsc_licence_id' ? $this->licence_id : null;
            }
        };

        self::$mock_order = new class( $item ) {
            private $item;
            public function __construct( $item ) { $this->item = $item; }
            public function get_items() { return [ $this->item ]; }
        };

        // Execute cancellation sync
        ufsc_handle_order_cancellation( 999 );

        $updated = $licence_manager->get_licence_by_id( $licence_id );
        $this->assertEquals( 'revoked', $updated->statut );
    }
}
