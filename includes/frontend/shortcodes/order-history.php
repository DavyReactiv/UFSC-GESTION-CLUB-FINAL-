<?php
/**
 * UFSC Order History Shortcode
 *
 * Displays a table of WooCommerce orders for the current user.
 *
 * @package UFSC_Gestion_Club
 * @since 1.3.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Order history shortcode callback.
 *
 * @param array $atts Shortcode attributes (currently unused)
 * @return string HTML output
 */
function ufsc_order_history_shortcode($atts = array()) {
    return ufsc_shortcode_with_login_check('order_history', function () {
        $orders = wc_get_orders(array(
            'customer' => get_current_user_id(),
            'limit'    => -1,
        ));

        // Enqueue CSS for order history table
        if (defined('UFSC_PLUGIN_URL')) {
            wp_enqueue_style(
                'ufsc-order-history',
                UFSC_PLUGIN_URL . 'assets/css/order-history.css',
                array(),
                UFSC_GESTION_CLUB_VERSION
            );
        }

        if (empty($orders)) {
            return '<p>' . esc_html__("Aucune commande trouvée.", 'plugin-ufsc-gestion-club-13072025') . '</p>';
        }

        $output  = '<table class="ufsc-order-history">';
        $output .= '<thead><tr>';
        $output .= '<th>' . esc_html__("Commande", 'plugin-ufsc-gestion-club-13072025') . '</th>';
        $output .= '<th>' . esc_html__("Date", 'plugin-ufsc-gestion-club-13072025') . '</th>';
        $output .= '<th>' . esc_html__("Statut", 'plugin-ufsc-gestion-club-13072025') . '</th>';
        $output .= '<th>' . esc_html__("Total", 'plugin-ufsc-gestion-club-13072025') . '</th>';
        $output .= '</tr></thead><tbody>';

        foreach ($orders as $order) {
            /** @var WC_Order $order */
            $order_id    = $order->get_id();
            $order_url   = $order->get_view_order_url();
            $date_created = $order->get_date_created();
            $date_display = $date_created ? date_i18n(get_option('date_format'), $date_created->getTimestamp()) : '';
            $status      = wc_get_order_status_name($order->get_status());
            $total       = $order->get_formatted_order_total();

            $output .= '<tr>';
            $output .= '<td><a href="' . esc_url($order_url) . '">#' . esc_html($order_id) . '</a></td>';
            $output .= '<td>' . esc_html($date_display) . '</td>';
            $output .= '<td>' . esc_html($status) . '</td>';
            $output .= '<td>' . wp_kses_post($total) . '</td>';
            $output .= '</tr>';
        }

        $output .= '</tbody></table>';

        return $output;
    }, $atts);
}
