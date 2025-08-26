<?php

/**
 * Notifications Management for Club Dashboard
 * 
 * Handles notifications, alerts, and communication for clubs.
 * Provides secure notification system with proper access control.
 *
 * @package UFSC_Gestion_Club
 * @subpackage Frontend\Club
 * @since 1.0.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render notifications section for club dashboard
 *
 * @param object $club Club object
 * @return string HTML output for notifications section
 */
function ufsc_club_render_notifications($club)
{
    // Security check: verify club access
    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé : vous ne pouvez accéder qu\'aux données de votre propre club.</div>';
    }

    $output = '<h2 class="ufsc-section-title">Notifications et communications</h2>';

    // Handle notification actions
    if (isset($_POST['ufsc_mark_read']) || isset($_POST['ufsc_mark_all_read'])) {
        $output .= ufsc_handle_notification_actions($club);
    }

    // Unread notifications summary
    $output .= ufsc_render_notifications_summary($club);

    // Recent notifications list
    $output .= ufsc_render_notifications_list($club);

    // Notification preferences
    $output .= ufsc_render_notification_preferences($club);

    // Communication center
    $output .= ufsc_render_communication_center($club);

    return $output;
}

/**
 * Render notifications summary
 *
 * @param object $club Club object
 * @return string HTML output for notifications summary
 */
function ufsc_render_notifications_summary($club)
{
    $notifications_stats = ufsc_get_notifications_stats($club->id);

    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-bell"></i> Résumé des notifications';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    if ($notifications_stats['unread_count'] > 0) {
        $output .= '<div class="ufsc-notifications-summary">';
        $output .= '<div class="ufsc-summary-item ufsc-summary-urgent">';
        $output .= '<div class="ufsc-summary-icon">';
        $output .= '<i class="dashicons dashicons-warning"></i>';
        $output .= '</div>';
        $output .= '<div class="ufsc-summary-content">';
        $output .= '<h4>Notifications non lues</h4>';
        $output .= '<p>Vous avez <strong>' . $notifications_stats['unread_count'] . '</strong> notification' . ($notifications_stats['unread_count'] > 1 ? 's' : '') . ' non lue' . ($notifications_stats['unread_count'] > 1 ? 's' : '') . '.</p>';
        $output .= '</div>';
        $output .= '<div class="ufsc-summary-actions">';
        $output .= '<form method="post" class="ufsc-inline">';
        $output .= wp_nonce_field('ufsc_notifications_actions', 'ufsc_notifications_nonce', true, false);
        $output .= '<button type="submit" name="ufsc_mark_all_read" class="ufsc-btn ufsc-btn-outline">Tout marquer comme lu</button>';
        $output .= '</form>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
    } else {
        $output .= '<div class="ufsc-alert ufsc-alert-success">';
        $output .= '<p><i class="dashicons dashicons-yes-alt"></i> Toutes vos notifications sont lues.</p>';
        $output .= '</div>';
    }

    // Quick stats
    $output .= '<div class="ufsc-notifications-stats">';
    $output .= '<div class="ufsc-stat-item">';
    $output .= '<span class="ufsc-stat-number">' . $notifications_stats['total_count'] . '</span>';
    $output .= '<span class="ufsc-stat-label">Total notifications</span>';
    $output .= '</div>';
    $output .= '<div class="ufsc-stat-item">';
    $output .= '<span class="ufsc-stat-number">' . $notifications_stats['important_count'] . '</span>';
    $output .= '<span class="ufsc-stat-label">Importantes</span>';
    $output .= '</div>';
    $output .= '<div class="ufsc-stat-item">';
    $output .= '<span class="ufsc-stat-number">' . $notifications_stats['this_week'] . '</span>';
    $output .= '<span class="ufsc-stat-label">Cette semaine</span>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render notifications list
 *
 * @param object $club Club object
 * @return string HTML output for notifications list
 */
function ufsc_render_notifications_list($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-list-view"></i> Notifications récentes';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    // Get notifications
    $notifications = ufsc_get_club_notifications($club->id);

    if (empty($notifications)) {
        $output .= '<div class="ufsc-empty-state">';
        $output .= '<div class="ufsc-empty-icon"><i class="dashicons dashicons-bell"></i></div>';
        $output .= '<h3>Aucune notification</h3>';
        $output .= '<p>Vous n\'avez pas encore reçu de notifications.</p>';
        $output .= '</div>';
    } else {
        $output .= '<div class="ufsc-notifications-list">';
        
        foreach ($notifications as $notification) {
            $unread_class = !$notification['read'] ? 'ufsc-notification-unread' : '';
            $priority_class = 'ufsc-priority-' . $notification['priority'];
            
            $output .= '<div class="ufsc-notification-item ' . $unread_class . ' ' . $priority_class . '">';
            $output .= '<div class="ufsc-notification-icon">';
            $output .= '<i class="dashicons dashicons-' . ufsc_get_notification_icon($notification['type']) . '"></i>';
            $output .= '</div>';
            $output .= '<div class="ufsc-notification-content">';
            $output .= '<div class="ufsc-notification-header">';
            $output .= '<h4>' . esc_html($notification['title']) . '</h4>';
            $output .= '<span class="ufsc-notification-date">' . esc_html($notification['date']) . '</span>';
            $output .= '</div>';
            $output .= '<p>' . esc_html($notification['message']) . '</p>';
            
            // Action buttons
            if (!empty($notification['action_url'])) {
                $output .= '<div class="ufsc-notification-action">';
                $output .= '<a href="' . esc_url($notification['action_url']) . '" class="ufsc-btn ufsc-btn-sm">' . esc_html($notification['action_text']) . '</a>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
            $output .= '<div class="ufsc-notification-actions">';
            
            if (!$notification['read']) {
                $output .= '<form method="post" class="ufsc-inline">';
                $output .= wp_nonce_field('ufsc_notifications_actions', 'ufsc_notifications_nonce', true, false);
                $output .= '<input type="hidden" name="notification_id" value="' . esc_attr($notification['id']) . '">';
                $output .= '<button type="submit" name="ufsc_mark_read" class="ufsc-btn-sm" title="Marquer comme lu">';
                $output .= '<i class="dashicons dashicons-yes"></i>';
                $output .= '</button>';
                $output .= '</form>';
            }
            
            $output .= '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Pagination if needed
        if (count($notifications) >= 10) {
            $output .= '<div class="ufsc-notifications-pagination">';
            $output .= '<a href="#" class="ufsc-btn ufsc-btn-outline">Voir plus de notifications</a>';
            $output .= '</div>';
        }
    }

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render notification preferences
 *
 * @param object $club Club object
 * @return string HTML output for notification preferences
 */
function ufsc_render_notification_preferences($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-admin-settings"></i> Préférences de notification';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    // Handle preferences update
    if (isset($_POST['ufsc_update_preferences'])) {
        $output .= ufsc_handle_preferences_update($club);
    }

    // Get current preferences
    $preferences = ufsc_get_notification_preferences($club->id);

    $output .= '<form method="post" class="ufsc-preferences-form">';
    $output .= wp_nonce_field('ufsc_notification_preferences', 'ufsc_preferences_nonce', true, false);

    $output .= '<h4>Types de notifications à recevoir :</h4>';
    $output .= '<div class="ufsc-preferences-grid">';

    $notification_types = [
        'licence_expiry' => [
            'label' => 'Expiration de licences',
            'description' => 'Alertes avant expiration des licences de vos membres'
        ],
        'payment_reminders' => [
            'label' => 'Rappels de paiement',
            'description' => 'Notifications pour les échéances de paiement'
        ],
        'admin_messages' => [
            'label' => 'Messages administratifs',
            'description' => 'Communications importantes de l\'administration UFSC'
        ],
        'club_updates' => [
            'label' => 'Mises à jour du club',
            'description' => 'Confirmations de modifications sur votre club'
        ],
        'competitions' => [
            'label' => 'Compétitions et événements',
            'description' => 'Informations sur les compétitions et événements UFSC'
        ],
        'news' => [
            'label' => 'Actualités UFSC',
            'description' => 'Nouvelles et informations générales de la fédération'
        ]
    ];

    foreach ($notification_types as $type => $info) {
        $checked = isset($preferences[$type]) && $preferences[$type] ? 'checked' : '';
        
        $output .= '<div class="ufsc-preference-item">';
        $output .= '<label class="ufsc-checkbox-label">';
        $output .= '<input type="checkbox" name="preferences[' . $type . ']" value="1" ' . $checked . '>';
        $output .= '<span class="ufsc-checkbox-custom"></span>';
        $output .= '<div class="ufsc-preference-info">';
        $output .= '<strong>' . esc_html($info['label']) . '</strong>';
        $output .= '<p>' . esc_html($info['description']) . '</p>';
        $output .= '</div>';
        $output .= '</label>';
        $output .= '</div>';
    }

    $output .= '</div>';

    $output .= '<h4>Mode de réception :</h4>';
    $output .= '<div class="ufsc-delivery-methods">';

    $delivery_methods = [
        'email' => 'Email uniquement',
        'dashboard' => 'Tableau de bord uniquement',
        'both' => 'Email et tableau de bord'
    ];

    $current_method = $preferences['delivery_method'] ?? 'both';

    foreach ($delivery_methods as $method => $label) {
        $checked = $current_method === $method ? 'checked' : '';
        
        $output .= '<label class="ufsc-radio-label">';
        $output .= '<input type="radio" name="delivery_method" value="' . $method . '" ' . $checked . '>';
        $output .= '<span class="ufsc-radio-custom"></span>';
        $output .= esc_html($label);
        $output .= '</label>';
    }

    $output .= '</div>';

    $output .= '<div class="ufsc-form-actions">';
    $output .= '<button type="submit" name="ufsc_update_preferences" class="ufsc-btn ufsc-btn-primary">';
    $output .= '<i class="dashicons dashicons-saved"></i> Enregistrer les préférences';
    $output .= '</button>';
    $output .= '</div>';

    $output .= '</form>';
    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Render communication center
 *
 * @param object $club Club object
 * @return string HTML output for communication center
 */
function ufsc_render_communication_center($club)
{
    $output = '<div class="ufsc-card">';
    $output .= '<div class="ufsc-card-header">';
    $output .= '<i class="dashicons dashicons-email"></i> Centre de communication';
    $output .= '</div>';
    $output .= '<div class="ufsc-card-body">';

    $output .= '<div class="ufsc-communication-options">';

    // Quick contact options
    $contact_options = [
        'support' => [
            'title' => 'Support technique',
            'description' => 'Aide pour l\'utilisation de la plateforme',
            'email' => 'support@ufsc-france.org',
            'icon' => 'sos'
        ],
        'admin' => [
            'title' => 'Administration',
            'description' => 'Questions administratives et affiliation',
            'email' => 'administration@ufsc-france.org',
            'icon' => 'businessman'
        ],
        'competitions' => [
            'title' => 'Compétitions',
            'description' => 'Inscriptions et informations compétitions',
            'email' => 'competitions@ufsc-france.org',
            'icon' => 'awards'
        ],
        'finance' => [
            'title' => 'Comptabilité',
            'description' => 'Questions sur les paiements et factures',
            'email' => 'comptabilite@ufsc-france.org',
            'icon' => 'money-alt'
        ]
    ];

    foreach ($contact_options as $option) {
        $output .= '<div class="ufsc-contact-option">';
        $output .= '<div class="ufsc-contact-icon">';
        $output .= '<i class="dashicons dashicons-' . $option['icon'] . '"></i>';
        $output .= '</div>';
        $output .= '<div class="ufsc-contact-info">';
        $output .= '<h4>' . esc_html($option['title']) . '</h4>';
        $output .= '<p>' . esc_html($option['description']) . '</p>';
        $output .= '</div>';
        $output .= '<div class="ufsc-contact-action">';
        
        $subject = 'Contact depuis l\'espace club - ' . $option['title'] . ' - Club ' . $club->nom;
        $body = "Bonjour,\n\n";
        $body .= "Club: " . $club->nom . "\n";
        $body .= "N° d'affiliation: " . ($club->num_affiliation ?? 'En cours') . "\n\n";
        $body .= "Votre message:\n\n";
        
        $mailto_url = 'mailto:' . $option['email'] . '?subject=' . urlencode($subject) . '&body=' . urlencode($body);
        
        $output .= '<a href="' . esc_url($mailto_url) . '" class="ufsc-btn ufsc-btn-outline">';
        $output .= '<i class="dashicons dashicons-email"></i> Contacter';
        $output .= '</a>';
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';

    // Emergency contact
    $output .= '<div class="ufsc-emergency-contact">';
    $output .= '<div class="ufsc-alert ufsc-alert-info">';
    $output .= '<h4><i class="dashicons dashicons-phone"></i> Contact téléphonique</h4>';
    $output .= '<p>En cas d\'urgence ou pour une assistance immédiate :</p>';
    $output .= '<p><strong>01 23 45 67 89</strong> (du lundi au vendredi, 9h-17h)</p>';
    $output .= '</div>';
    $output .= '</div>';

    $output .= '</div>';
    $output .= '</div>';

    return $output;
}

/**
 * Get notifications statistics
 *
 * @param int $club_id Club ID
 * @return array Notifications statistics
 */
function ufsc_get_notifications_stats($club_id)
{
    // This would typically query a notifications table
    // For now, return sample data
    
    return [
        'unread_count' => 3,
        'total_count' => 25,
        'important_count' => 2,
        'this_week' => 5
    ];
}

/**
 * Get club notifications
 *
 * @param int $club_id Club ID
 * @param int $limit Number of notifications to retrieve
 * @return array Notifications array
 */
function ufsc_get_club_notifications($club_id, $limit = 10)
{
    // This would typically query a notifications table
    // For now, return sample notifications
    
    return [
        [
            'id' => 1,
            'type' => 'licence_expiry',
            'priority' => 'high',
            'title' => 'Licences expirant bientôt',
            'message' => '2 licences de votre club expirent dans les 30 prochains jours.',
            'date' => date('d/m/Y H:i', strtotime('-2 days')),
            'read' => false,
            'action_url' => add_query_arg(['section' => 'licences'], get_permalink()),
            'action_text' => 'Voir les licences'
        ],
        [
            'id' => 2,
            'type' => 'admin_message',
            'priority' => 'normal',
            'title' => 'Nouvelle procédure d\'affiliation',
            'message' => 'Nous avons mis à jour nos procédures d\'affiliation. Consultez le guide mis à jour.',
            'date' => date('d/m/Y H:i', strtotime('-1 week')),
            'read' => false,
            'action_url' => '#',
            'action_text' => 'Voir les détails'
        ],
        [
            'id' => 3,
            'type' => 'payment_reminder',
            'priority' => 'high',
            'title' => 'Rappel de paiement',
            'message' => 'Votre cotisation annuelle est due dans 15 jours.',
            'date' => date('d/m/Y H:i', strtotime('-3 days')),
            'read' => false,
            'action_url' => add_query_arg(['section' => 'paiements'], get_permalink()),
            'action_text' => 'Voir les paiements'
        ],
        [
            'id' => 4,
            'type' => 'club_update',
            'priority' => 'low',
            'title' => 'Profil club mis à jour',
            'message' => 'Les informations de votre club ont été mises à jour avec succès.',
            'date' => date('d/m/Y H:i', strtotime('-1 week')),
            'read' => true,
            'action_url' => null,
            'action_text' => null
        ]
    ];
}

/**
 * Get notification preferences
 *
 * @param int $club_id Club ID
 * @return array Notification preferences
 */
function ufsc_get_notification_preferences($club_id)
{
    // This would typically query user meta or preferences table
    // For now, return default preferences
    
    return [
        'licence_expiry' => true,
        'payment_reminders' => true,
        'admin_messages' => true,
        'club_updates' => true,
        'competitions' => false,
        'news' => false,
        'delivery_method' => 'both'
    ];
}

/**
 * Get notification icon based on type
 *
 * @param string $type Notification type
 * @return string Dashicon name
 */
function ufsc_get_notification_icon($type)
{
    $icons = [
        'licence_expiry' => 'id',
        'payment_reminder' => 'money-alt',
        'admin_message' => 'megaphone',
        'club_update' => 'edit',
        'competition' => 'awards',
        'news' => 'admin-post',
        'system' => 'admin-tools'
    ];

    return $icons[$type] ?? 'info';
}

/**
 * Handle notification actions (mark as read, etc.)
 *
 * @param object $club Club object
 * @return string Success or error message HTML
 */
function ufsc_handle_notification_actions($club)
{
    // Security checks
    if (!wp_verify_nonce($_POST['ufsc_notifications_nonce'], 'ufsc_notifications_actions')) {
        return '<div class="ufsc-alert ufsc-alert-error">Erreur de sécurité. Veuillez réessayer.</div>';
    }

    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé.</div>';
    }

    if (isset($_POST['ufsc_mark_read'])) {
        $notification_id = intval($_POST['notification_id']);
        // Here you would update the notification as read in the database
        return '<div class="ufsc-alert ufsc-alert-success">Notification marquée comme lue.</div>';
    }

    if (isset($_POST['ufsc_mark_all_read'])) {
        // Here you would mark all notifications as read for this club
        return '<div class="ufsc-alert ufsc-alert-success">Toutes les notifications ont été marquées comme lues.</div>';
    }

    return '';
}

/**
 * Handle notification preferences update
 *
 * @param object $club Club object
 * @return string Success or error message HTML
 */
function ufsc_handle_preferences_update($club)
{
    // Security checks
    if (!wp_verify_nonce($_POST['ufsc_preferences_nonce'], 'ufsc_notification_preferences')) {
        return '<div class="ufsc-alert ufsc-alert-error">Erreur de sécurité. Veuillez réessayer.</div>';
    }

    if (!ufsc_verify_club_access($club->id)) {
        return '<div class="ufsc-alert ufsc-alert-error">Accès refusé.</div>';
    }

    $preferences = $_POST['preferences'] ?? [];
    $delivery_method = sanitize_text_field($_POST['delivery_method'] ?? 'both');

    // Here you would save the preferences to the database
    // For now, just return success message

    return '<div class="ufsc-alert ufsc-alert-success">Vos préférences de notification ont été enregistrées avec succès.</div>';
}