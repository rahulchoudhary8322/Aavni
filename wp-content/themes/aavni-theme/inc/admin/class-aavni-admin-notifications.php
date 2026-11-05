<?php
/**
 * AAVNI Luxury E-Commerce Notification Engine
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Notifications {

    /**
     * Send HTML Email with AAVNI Luxury Branding
     */
    public static function send_email($to, $subject, $heading, $body_html, $cta_text = '', $cta_url = '') {
        if (empty($to) || !is_email($to)) {
            return false;
        }

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: AAVNI Handcrafted <' . get_option('admin_email') . '>',
        ];

        $html = self::get_email_template($heading, $body_html, $cta_text, $cta_url);

        try {
            return wp_mail($to, $subject, $html, $headers);
        } catch (\Throwable $t) {
            error_log('AAVNI Notification Error: ' . $t->getMessage());
            return false;
        }
    }

    /**
     * Send Order Milestone & Dispatch Update to Customer
     */
    public static function notify_order_milestone($order, $milestone, $courier = '', $awb = '', $tracking_url = '', $note = '') {
        // Accept either a WC_Order object or an integer order ID
        if (is_numeric($order)) {
            $order = wc_get_order(absint($order));
        }
        if (!is_a($order, 'WC_Order')) {
            return false;
        }

        $to = $order->get_billing_email();
        if (empty($to)) {
            return false;
        }

        $order_num = method_exists($order, 'get_order_number') ? $order->get_order_number() : $order->get_id();
        $defs = function_exists('aavni_get_milestone_definitions') ? aavni_get_milestone_definitions() : [];
        $title = isset($defs[$milestone]) ? $defs[$milestone]['label'] : ucfirst(str_replace('_', ' ', $milestone));

        $subject = sprintf(__('Shipment Update: Order #%s is now %s — AAVNI', 'aavni'), $order_num, $title);
        $heading = sprintf(__('Order #%s Status: %s', 'aavni'), $order_num, $title);

        $body = '<p style="font-size:15px; color:#2B2B2B; line-height:1.6;">Dear ' . esc_html($order->get_billing_first_name() ?: 'Valued Customer') . ',</p>';
        $body .= '<p style="font-size:14px; color:#555; line-height:1.6;">Great news! The fulfilment status for your handcrafted AAVNI order <strong>#' . esc_html($order_num) . '</strong> has been updated to: <strong style="color:#A85A48;">' . esc_html($title) . '</strong>.</p>';

        if (!empty($courier) || !empty($awb)) {
            $body .= '<div style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; padding:16px; margin:20px 0;">';
            $body .= '<h4 style="margin:0 0 10px; color:#1B1A18; font-size:14px;">Shipment & Courier Information</h4>';
            if ($courier) {
                $body .= '<div style="font-size:13px; color:#555; margin-bottom:6px;"><strong>Carrier:</strong> ' . esc_html($courier) . '</div>';
            }
            if ($awb) {
                $body .= '<div style="font-size:13px; color:#555; margin-bottom:6px;"><strong>Tracking / AWB #:</strong> <code style="background:#fff; padding:2px 6px; border:1px solid #ddd; border-radius:4px;">' . esc_html($awb) . '</code></div>';
            }
            if ($note) {
                $body .= '<div style="font-size:13px; color:#666; margin-top:8px;"><strong>Status Note:</strong> ' . esc_html($note) . '</div>';
            }
            $body .= '</div>';
        }

        $tracking_link = !empty($tracking_url) ? $tracking_url : wc_get_account_endpoint_url('orders');
        return self::send_email($to, $subject, $heading, $body, __('Track Your Package Live', 'aavni'), $tracking_link);
    }

    /**
     * Send Return Decision Update to Customer
     */
    public static function notify_return_decision($return_id, $order, $status, $admin_notes = '') {
        if (!is_a($order, 'WC_Order')) {
            return false;
        }

        $to = $order->get_billing_email();
        if (empty($to)) {
            return false;
        }

        $order_num = method_exists($order, 'get_order_number') ? $order->get_order_number() : $order->get_id();
        $status_label = ucfirst(str_replace('_', ' ', $status));
        $subject = sprintf(__('Return Request #%d Update: %s — AAVNI', 'aavni'), $return_id, $status_label);
        $heading = sprintf(__('Return Request #%d: %s', 'aavni'), $return_id, $status_label);

        $body = '<p style="font-size:15px; color:#2B2B2B; line-height:1.6;">Dear ' . esc_html($order->get_billing_first_name() ?: 'Valued Customer') . ',</p>';
        $body .= '<p style="font-size:14px; color:#555; line-height:1.6;">Our Quality & Concierge team has reviewed your return/exchange request for Order <strong>#' . esc_html($order_num) . '</strong>.</p>';
        $body .= '<p style="font-size:14px; color:#555; line-height:1.6;">Current Request Status: <strong style="color:#A85A48;">' . esc_html($status_label) . '</strong></p>';

        if (!empty($admin_notes)) {
            $body .= '<div style="background:#FAF8F5; border-left:4px solid #A85A48; padding:14px 16px; margin:18px 0; border-radius:4px;">';
            $body .= '<strong>Message from AAVNI Customer Care:</strong><br>';
            $body .= '<p style="margin:6px 0 0; color:#444; font-size:13.5px; line-height:1.5;">' . nl2br(esc_html($admin_notes)) . '</p>';
            $body .= '</div>';
        }

        $returns_url = wc_get_account_endpoint_url('returns');
        return self::send_email($to, $subject, $heading, $body, __('View Return Status in My Account', 'aavni'), $returns_url);
    }

    /**
     * Send Support Ticket Reply to Customer
     */
    public static function notify_ticket_reply($ticket, $reply_message, $author_name) {
        $customer = get_user_by('id', $ticket->customer_id);
        if (!$customer || empty($customer->user_email)) {
            return false;
        }

        $subject = sprintf(__('Reply to Support Ticket [%s]: %s — AAVNI', 'aavni'), $ticket->ticket_code, $ticket->subject);
        $heading = sprintf(__('New Reply on Ticket %s', 'aavni'), $ticket->ticket_code);

        $body = '<p style="font-size:15px; color:#2B2B2B; line-height:1.6;">Dear ' . esc_html($customer->display_name) . ',</p>';
        $body .= '<p style="font-size:14px; color:#555; line-height:1.6;">You have received a response from our Customer Care desk regarding your inquiry: <strong>"' . esc_html($ticket->subject) . '"</strong>.</p>';

        $body .= '<div style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; padding:16px; margin:18px 0;">';
        $body .= '<div style="font-size:12.5px; color:#A85A48; font-weight:700; margin-bottom:6px;">🛡️ ' . esc_html($author_name) . '</div>';
        $body .= '<div style="font-size:14px; color:#2B2B2B; line-height:1.6;">' . nl2br(esc_html($reply_message)) . '</div>';
        $body .= '</div>';

        $support_url = wc_get_account_endpoint_url('support-tickets');
        return self::send_email($customer->user_email, $subject, $heading, $body, __('Reply on Customer Support Desk', 'aavni'), $support_url);
    }

    /**
     * Template generator
     */
    private static function get_email_template($heading, $body_html, $cta_text = '', $cta_url = '') {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { margin: 0; padding: 0; background-color: #FAF8F5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
                .email-container { max-width: 600px; margin: 30px auto; background: #FFFFFF; border: 1px solid #EAE5DE; border-radius: 8px; overflow: hidden; }
                .email-header { background: #A85A48; padding: 28px 24px; text-align: center; }
                .email-logo { color: #FFFFFF; font-family: Georgia, serif; font-size: 26px; font-weight: 700; letter-spacing: 3px; text-transform: uppercase; margin: 0; }
                .email-tagline { color: rgba(255,255,255,0.85); font-size: 11px; letter-spacing: 1.5px; margin-top: 4px; text-transform: uppercase; }
                .email-body { padding: 32px 28px; }
                .email-heading { font-family: Georgia, serif; font-size: 20px; color: #1B1A18; margin: 0 0 18px; }
                .email-btn { display: inline-block; background: #A85A48; color: #FFFFFF !important; text-decoration: none; padding: 12px 26px; border-radius: 4px; font-weight: 600; font-size: 13.5px; margin-top: 14px; }
                .email-footer { background: #F5EFEB; padding: 20px 24px; text-align: center; font-size: 11.5px; color: #888; border-top: 1px solid #EAE5DE; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1 class="email-logo">AAVNI</h1>
                    <div class="email-tagline">Handcrafted Luxury · Jaipur</div>
                </div>
                <div class="email-body">
                    <h2 class="email-heading"><?php echo esc_html($heading); ?></h2>
                    <?php echo $body_html; ?>
                    <?php if (!empty($cta_text) && !empty($cta_url)) : ?>
                        <div style="text-align: center; margin: 28px 0 14px;">
                            <a href="<?php echo esc_url($cta_url); ?>" class="email-btn" target="_blank"><?php echo esc_html($cta_text); ?> &rarr;</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="email-footer">
                    <p style="margin: 0 0 6px;">Handcrafted Ethnic & Contemporary Wear &bull; Jaipur, Rajasthan, India</p>
                    <p style="margin: 0;">Care Helpline: +91 9828103222 &bull; Email: care@aavniclothing.com</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
