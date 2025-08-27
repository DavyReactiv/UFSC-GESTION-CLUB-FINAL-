<?php
/**
 * Tests for AJAX licence draft saving
 */
class Test_Ajax_Save_Draft extends WP_UnitTestCase {
    /**
     * Helper to perform AJAX save call and return decoded JSON response.
     */
    protected function call_save( $user_id, $club_id, $nonce ) {
        wp_set_current_user( $user_id );
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'nonce'         => $nonce,
            'club_id'       => $club_id,
            'nom'           => 'Test',
            'prenom'        => 'User',
            'date_naissance'=> '2000-01-01',
            'email'         => 'test@example.com',
            'adresse'       => '1 rue',
            'code_postal'   => '75000',
            'ville'         => 'Paris',
            'tel_mobile'    => '0600000000',
            'competition'   => 0,
        ];
        try {
            ufsc_handle_save_licence_draft();
        } catch ( \Exception $e ) {
            return json_decode( $e->getMessage(), true );
        }
        return null;
    }

    public function test_nonce_and_capability_checks() {
        if ( ! class_exists( 'UFSC_Club_Manager' ) ) {
            $this->markTestSkipped( 'UFSC plugin classes not available' );
        }
        $club_manager = UFSC_Club_Manager::get_instance();
        $club_id = $club_manager->add_club( [ 'nom' => 'Ajax Club', 'email' => 'ajax@example.com' ] );

        // Authorized user with valid nonce
        $admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
        update_user_meta( $admin_id, 'ufsc_club_id', $club_id );
        $res = $this->call_save( $admin_id, $club_id, wp_create_nonce( 'ufsc_frontend_nonce' ) );
        $this->assertTrue( $res['success'] );
        $this->assertArrayHasKey( 'licence_id', $res['data'] );

        // Invalid nonce
        $res = $this->call_save( $admin_id, $club_id, 'bad_nonce' );
        $this->assertFalse( $res['success'] );

        // Unauthorized user
        $user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
        $res = $this->call_save( $user_id, $club_id, wp_create_nonce( 'ufsc_frontend_nonce' ) );
        $this->assertFalse( $res['success'] );
    }
}
