<?php
/**
 * WordPress User Profile Enhancement for Club Association
 * 
 * Adds a club selection field to the WordPress user profile page (user-edit.php)
 * allowing administrators to associate users with clubs.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize user profile hooks
 */
function ufsc_init_user_profile_hooks()
{
    // Only add hooks if user has admin capabilities
    if (current_user_can('manage_ufsc')) {
        add_action('show_user_profile', 'ufsc_add_club_field_to_user_profile');
        add_action('edit_user_profile', 'ufsc_add_club_field_to_user_profile');
        add_action('personal_options_update', 'ufsc_save_user_club_association');
        add_action('edit_user_profile_update', 'ufsc_save_user_club_association');
    }
}

/**
 * Add club selection field to user profile page
 *
 * @param WP_User $user The user object
 */
function ufsc_add_club_field_to_user_profile($user)
{
    // Only show for admins
    if (!current_user_can('manage_ufsc')) {
        return;
    }

    // Get current club association
    $current_club = ufsc_get_user_club($user->ID);
    $current_club_id = $current_club ? $current_club->id : 0;

    // Get all clubs for dropdown
    $clubs = ufsc_get_all_clubs_for_user_association();
    
    ?>
    <h3><?php _e('Association Club UFSC', 'plugin-ufsc-gestion-club-13072025'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="ufsc_club_id"><?php _e('Club associé', 'plugin-ufsc-gestion-club-13072025'); ?></label></th>
            <td>
                <select name="ufsc_club_id" id="ufsc_club_id" class="regular-text">
                    <option value=""><?php _e('-- Aucun club associé --', 'plugin-ufsc-gestion-club-13072025'); ?></option>
                    <?php foreach ($clubs as $club): ?>
                        <?php
                        // Check if this club is already associated with another user
                        $is_already_used = $club->responsable_id && $club->responsable_id != $user->ID;
                        $disabled = $is_already_used ? 'disabled' : '';
                        $title = $is_already_used ? 
                            sprintf(__('Ce club est déjà associé à l\'utilisateur %s', 'plugin-ufsc-gestion-club-13072025'), 
                                ufsc_get_user_display_name($club->responsable_id)) : '';
                        ?>
                        <option value="<?php echo esc_attr($club->id); ?>" 
                                <?php selected($current_club_id, $club->id); ?>
                                <?php echo $disabled; ?>
                                title="<?php echo esc_attr($title); ?>">
                            <?php echo esc_html($club->nom . ' (' . $club->ville . ')'); ?>
                            <?php if ($is_already_used): ?>
                                <?php echo ' - ' . __('Déjà associé', 'plugin-ufsc-gestion-club-13072025'); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php _e('Associer cet utilisateur à un club UFSC. Un utilisateur ne peut être associé qu\'à un seul club.', 'plugin-ufsc-gestion-club-13072025'); ?>
                    <?php if ($current_club): ?>
                        <br><strong><?php _e('Actuellement associé à:', 'plugin-ufsc-gestion-club-13072025'); ?></strong> 
                        <?php echo esc_html($current_club->nom . ' (' . $current_club->ville . ')'); ?>
                    <?php endif; ?>
                </p>
                <?php wp_nonce_field('ufsc_save_user_club_association', 'ufsc_user_club_nonce'); ?>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Save user club association from profile page
 *
 * @param int $user_id The user ID
 */
function ufsc_save_user_club_association($user_id)
{
    // Security checks
    if (!current_user_can('manage_ufsc')) {
        return;
    }

    if (!wp_verify_nonce($_POST['ufsc_user_club_nonce'] ?? '', 'ufsc_save_user_club_association')) {
        return;
    }

    $new_club_id = isset($_POST['ufsc_club_id']) ? intval($_POST['ufsc_club_id']) : 0;
    $current_club = ufsc_get_user_club($user_id);
    $current_club_id = $current_club ? $current_club->id : 0;

    // No change needed
    if ($new_club_id == $current_club_id) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';

    try {
        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Remove old association if exists
        if ($current_club_id) {
            $wpdb->update(
                $table_name,
                ['responsable_id' => null],
                ['id' => $current_club_id],
                ['%s'],
                ['%d']
            );
        }

        // Add new association if selected
        if ($new_club_id > 0) {
            // Verify club exists
            $club_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE id = %d",
                $new_club_id
            ));

            if (!$club_exists) {
                throw new Exception(__('Le club sélectionné n\'existe pas.', 'plugin-ufsc-gestion-club-13072025'));
            }

            // Check if club is already associated with another user
            $existing_user = $wpdb->get_var($wpdb->prepare(
                "SELECT responsable_id FROM {$table_name} WHERE id = %d AND responsable_id IS NOT NULL AND responsable_id != %d",
                $new_club_id,
                $user_id
            ));

            if ($existing_user) {
                throw new Exception(__('Ce club est déjà associé à un autre utilisateur.', 'plugin-ufsc-gestion-club-13072025'));
            }

            // Associate user with new club
            $result = $wpdb->update(
                $table_name,
                ['responsable_id' => $user_id],
                ['id' => $new_club_id],
                ['%d'],
                ['%d']
            );

            if ($result === false) {
                throw new Exception(__('Erreur lors de l\'association de l\'utilisateur au club.', 'plugin-ufsc-gestion-club-13072025'));
            }
        }

        // Commit transaction
        $wpdb->query('COMMIT');

        // Add success message
        add_action('admin_notices', function() use ($new_club_id, $current_club_id) {
            if ($new_club_id > 0) {
                $club = ufsc_get_club_by_id($new_club_id);
                echo '<div class="notice notice-success"><p>' . 
                     sprintf(__('Utilisateur associé avec succès au club: %s', 'plugin-ufsc-gestion-club-13072025'), 
                             esc_html($club->nom ?? '')) . '</p></div>';
            } else if ($current_club_id > 0) {
                echo '<div class="notice notice-success"><p>' . 
                     __('Association avec le club supprimée avec succès.', 'plugin-ufsc-gestion-club-13072025') . '</p></div>';
            }
        });

    } catch (Exception $e) {
        // Rollback transaction
        $wpdb->query('ROLLBACK');
        
        // Add error message
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>' . 
                 __('Erreur: ', 'plugin-ufsc-gestion-club-13072025') . esc_html($e->getMessage()) . '</p></div>';
        });
    }
}

/**
 * Get all clubs for user association dropdown
 *
 * @return array Array of club objects
 */
function ufsc_get_all_clubs_for_user_association()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    
    return $wpdb->get_results(
        "SELECT id, nom, ville, responsable_id FROM {$table_name} ORDER BY nom ASC"
    );
}

/**
 * Get club by ID
 *
 * @param int $club_id Club ID
 * @return object|null Club object or null if not found
 */
function ufsc_get_club_by_id($club_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'ufsc_clubs';
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE id = %d",
        $club_id
    ));
}

/**
 * Get user display name for given user ID
 *
 * @param int $user_id User ID
 * @return string User display name or "Utilisateur introuvable"
 */
function ufsc_get_user_display_name($user_id)
{
    $user = get_userdata($user_id);
    return $user ? $user->display_name : __('Utilisateur introuvable', 'plugin-ufsc-gestion-club-13072025');
}

// Initialize hooks when WordPress is loaded
add_action('init', 'ufsc_init_user_profile_hooks');