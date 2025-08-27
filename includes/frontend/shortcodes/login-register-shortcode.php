<?php

/**
 * UFSC Login/Register Shortcode
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Login/Register shortcode function
 * 
 * Displays login and registration forms for club members
 * 
 * @param array $atts Shortcode attributes
 * @return string HTML output
 * @since 1.3.0
 */
function ufsc_login_register_shortcode($atts = array()) {
    // Parse attributes
    $atts = shortcode_atts(array(
        'redirect' => '',
        'show_register' => 'auto' // auto, yes, no
    ), $atts, 'ufsc_login_register');
    
    // If user is already logged in, show message and dashboard link
    if (is_user_logged_in()) {
        return '<div class="ufsc-container"><div class="ufsc-grid">'
            . ufsc_render_logged_in_message() . '</div></div>';
    }
    
    // Handle registration form submission
    if (isset($_POST['ufsc_register_submit']) && wp_verify_nonce($_POST['ufsc_register_nonce'], 'ufsc_register_nonce')) {
        $registration_result = ufsc_handle_registration_form();
        if ($registration_result['success']) {
            // Registration successful - redirect or show success
            $redirect_url = $registration_result['redirect_url'];
            if ($redirect_url) {
                wp_redirect($redirect_url);
                exit;
            }
        }
        // If there are errors, they will be displayed in the form
    }
    
    $output = '<div class="ufsc-container"><div class="ufsc-grid"><div class="ufsc-login-register-wrapper">';
    
    // Add any error messages
    if (isset($registration_result) && !$registration_result['success']) {
        $output .= '<div class="ufsc-alert ufsc-alert-error">';
        $output .= '<p>' . esc_html($registration_result['message']) . '</p>';
        $output .= '</div>';
    }
    
    // Show registration form if enabled
    $show_register = $atts['show_register'];
    if ($show_register === 'auto') {
        $show_register = get_option('users_can_register') ? 'yes' : 'no';
    }
    
    if ($show_register === 'yes') {
        $output .= ufsc_render_registration_form();
    }
    
    // Show login form
    $output .= ufsc_render_login_form($atts['redirect']);
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Render message for already logged in users
 * 
 * @return string HTML output
 */
function ufsc_render_logged_in_message() {
    $current_user  = wp_get_current_user();
    $dashboard_url = ufsc_get_dashboard_url();

    // Determine redirect target for logout if provided
    $logout_redirect = '';
    if (isset($_REQUEST['redirect_to'])) {
        $logout_redirect = esc_url_raw($_REQUEST['redirect_to']);
    } elseif (isset($_REQUEST['redirect'])) {
        $logout_redirect = esc_url_raw($_REQUEST['redirect']);
    } else {
        $logout_redirect = get_permalink();
    }

    $logout_url = wp_logout_url($logout_redirect);

    $output  = '<div class="ufsc-card">';
    $output .= '<h3>' . __('Déjà connecté', 'plugin-ufsc-gestion-club-13072025') . '</h3>';
    $output .= '<p>' . sprintf(__('Bonjour %s, vous êtes déjà connecté.', 'plugin-ufsc-gestion-club-13072025'), esc_html($current_user->display_name)) . '</p>';

    if ($dashboard_url) {
        $output .= '<a href="' . esc_url($dashboard_url) . '" class="ufsc-btn ufsc-btn-primary">';
        $output .= __('Accéder au tableau de bord', 'plugin-ufsc-gestion-club-13072025');
        $output .= '</a>';
    }

    $output .= '<a href="' . esc_url($logout_url) . '" class="ufsc-btn ufsc-btn-secondary">';
    $output .= __('Se déconnecter', 'plugin-ufsc-gestion-club-13072025');
    $output .= '</a>';

    $output .= '</div></div></div>';

    return $output;
}

/**
 * Render registration form
 * 
 * @return string HTML output
 */
function ufsc_render_registration_form() {
    $output = '<div class="ufsc-card">';
    $output .= '<h3>' . __('Inscription', 'plugin-ufsc-gestion-club-13072025') . '</h3>';
    $output .= '<form method="post" class="ufsc-form ufsc-auto-2cols" id="ufsc-register-form">';
    
    // Nonce field for security
    $output .= wp_nonce_field('ufsc_register_nonce', 'ufsc_register_nonce', true, false);
    
    // Email field
    $output .= '<div class="ufsc-form-row ufsc-full">';
    $output .= '<label for="ufsc_register_email">' . __('Email', 'plugin-ufsc-gestion-club-13072025') . ' <span class="required">*</span></label>';
    $output .= '<input type="email" id="ufsc_register_email" name="ufsc_register_email" required value="' . esc_attr( wp_unslash( $_POST['ufsc_register_email'] ?? '' ) ) . '">';
    $output .= '</div>';
    
    // Password field
    $output .= '<div class="ufsc-form-row ufsc-full">';
    $output .= '<label for="ufsc_register_password">' . __('Mot de passe', 'plugin-ufsc-gestion-club-13072025') . ' <span class="required">*</span></label>';
    $output .= '<input type="password" id="ufsc_register_password" name="ufsc_register_password" required minlength="8">';
    $output .= '<small class="description">' . __('Minimum 8 caractères', 'plugin-ufsc-gestion-club-13072025') . '</small>';
    $output .= '</div>';
    
    // Password confirmation field
    $output .= '<div class="ufsc-form-row ufsc-full">';
    $output .= '<label for="ufsc_register_password_confirm">' . __('Confirmer le mot de passe', 'plugin-ufsc-gestion-club-13072025') . ' <span class="required">*</span></label>';
    $output .= '<input type="password" id="ufsc_register_password_confirm" name="ufsc_register_password_confirm" required minlength="8">';
    $output .= '</div>';
    
    // Submit button
    $output .= '<div class="ufsc-form-row ufsc-full">';
    $output .= '<button type="submit" name="ufsc_register_submit" class="ufsc-btn ufsc-btn-primary">';
    $output .= __('S\'inscrire', 'plugin-ufsc-gestion-club-13072025');
    $output .= '</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    
    return $output;
}

/**
 * Render login form
 * 
 * @param string $redirect_url Redirect URL after login
 * @return string HTML output
 */
function ufsc_render_login_form($redirect_url = '') {
    if (empty($redirect_url)) {
        $redirect_url = ufsc_get_dashboard_url();
    }
    
    if (empty($redirect_url)) {
        $redirect_url = home_url();
    }
    
    $output = '<div class="ufsc-card">';
    $output .= '<h3>' . __('Connexion', 'plugin-ufsc-gestion-club-13072025') . '</h3>';
    
    // Use WordPress native login form
    $args = array(
        'redirect' => esc_url($redirect_url),
        'form_id' => 'ufsc-login-form',
        'id_username' => 'ufsc_user_login',
        'id_password' => 'ufsc_user_pass',
        'id_remember' => 'ufsc_remember_me',
        'id_submit' => 'ufsc_wp_submit',
        'label_username' => __('Email ou nom d\'utilisateur', 'plugin-ufsc-gestion-club-13072025'),
        'label_password' => __('Mot de passe', 'plugin-ufsc-gestion-club-13072025'),
        'label_remember' => __('Se souvenir de moi', 'plugin-ufsc-gestion-club-13072025'),
        'label_log_in' => __('Se connecter', 'plugin-ufsc-gestion-club-13072025'),
        'value_username' => '',
        'value_remember' => false,
        'echo' => false // Return the form instead of echoing it
    );
    
    $output .= wp_login_form($args);
    $output .= '</div>';
    
    return $output;
}

/**
 * Handle registration form submission
 * 
 * @return array Array with success status, message, and redirect URL
 */
function ufsc_handle_registration_form() {
    // Validate required fields
    if (empty($_POST['ufsc_register_email']) || empty($_POST['ufsc_register_password']) || empty($_POST['ufsc_register_password_confirm'])) {
        return array(
            'success' => false,
            'message' => __('Tous les champs sont obligatoires.', 'plugin-ufsc-gestion-club-13072025')
        );
    }
    
    $email = sanitize_email($_POST['ufsc_register_email']);
    $password = $_POST['ufsc_register_password'];
    $password_confirm = $_POST['ufsc_register_password_confirm'];
    
    // Validate email
    if (!is_email($email)) {
        return array(
            'success' => false,
            'message' => __('Adresse email invalide.', 'plugin-ufsc-gestion-club-13072025')
        );
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        return array(
            'success' => false,
            'message' => __('Cette adresse email est déjà utilisée.', 'plugin-ufsc-gestion-club-13072025')
        );
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        return array(
            'success' => false,
            'message' => __('Le mot de passe doit contenir au moins 8 caractères.', 'plugin-ufsc-gestion-club-13072025')
        );
    }
    
    // Check password confirmation
    if ($password !== $password_confirm) {
        return array(
            'success' => false,
            'message' => __('Les mots de passe ne correspondent pas.', 'plugin-ufsc-gestion-club-13072025')
        );
    }
    
    // Create username from email
    $username = sanitize_user(current(explode('@', $email)), true);
    
    // Make sure username is unique
    if (username_exists($username)) {
        $username = $username . '_' . wp_rand(100, 999);
    }
    
    // Create user
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        return array(
            'success' => false,
            'message' => $user_id->get_error_message()
        );
    }
    
    // Set user role to subscriber by default
    $user = new WP_User($user_id);
    $user->set_role('subscriber');
    
    // Log the user in automatically
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    
    // Determine redirect URL
    $redirect_url = ufsc_get_dashboard_url();
    if (!$redirect_url) {
        $redirect_url = home_url();
    }
    
    return array(
        'success' => true,
        'message' => __('Inscription réussie ! Vous êtes maintenant connecté.', 'plugin-ufsc-gestion-club-13072025'),
        'redirect_url' => $redirect_url
    );
}

/**
 * Get dashboard URL from options
 * 
 * @return string|false Dashboard URL or false if not configured
 */
function ufsc_get_dashboard_url() {
    $dashboard_page_id = get_option('ufsc_club_dashboard_page_id', 0);
    
    if ($dashboard_page_id && get_post_status($dashboard_page_id) === 'publish') {
        return get_permalink($dashboard_page_id);
    }
    
    return false;
}

// Register the shortcode
add_shortcode('ufsc_login_register', 'ufsc_login_register_shortcode');