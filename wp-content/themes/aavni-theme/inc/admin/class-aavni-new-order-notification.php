<?php
/**
 * AAVNI New Order Owner Notification System
 *
 * Sends a professional HTML email to the store owner whenever
 * a customer successfully places a new order.
 *
 * Features:
 * - Deduplication via order meta (one email per order, guaranteed)
 * - Works with COD and online payments
 * - SMTP support via WordPress filter (no hardcoded credentials)
 * - Error logging and admin-visible retry mechanism
 * - Does NOT send on milestone updates — only on new order creation
 *
 * @package AAVNI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAVNI_New_Order_Notification {

    /** Meta key used to flag that owner email was already sent for this order */
    const SENT_META_KEY   = '_aavni_owner_notif_sent';

    /** Meta key storing any send failure reason */
    const FAILED_META_KEY = '_aavni_owner_notif_failed';

    /** Option key for owner notification email */
    const EMAIL_OPTION    = 'aavni_owner_notification_email';

    /** Default owner email */
    const DEFAULT_EMAIL   = 'rahulchoudhary8322@gmail.com';

    /**
     * Register all hooks. Called once from AAVNI_Admin_Core::init().
     */
    public static function init() {
        /*
         * PRIMARY HOOK: woocommerce_checkout_order_created
         * Fires immediately after a new order object is created in the database,
         * before payment processing. Works for both COD and online orders.
         */
        add_action( 'woocommerce_checkout_order_created', [ __CLASS__, 'handle_new_order' ], 10, 1 );

        /*
         * SECONDARY HOOK (safety net): catches orders created via REST API or
         * programmatically (e.g. buy-now, guest checkout edge-cases).
         */
        add_action( 'woocommerce_new_order', [ __CLASS__, 'handle_new_order_by_id' ], 10, 1 );

        /*
         * PAYMENT COMPLETE HOOK: For online payment gateways (Razorpay, etc.)
         * where payment confirmation comes after order creation.
         * We only send here if the primary hook notification was NOT already sent.
         */
        add_action( 'woocommerce_payment_complete', [ __CLASS__, 'handle_payment_complete' ], 10, 1 );

        /*
         * SMTP Configuration hook — reads settings saved in AAVNI Store Settings.
         * Only activates if admin has configured SMTP credentials.
         */
        add_action( 'phpmailer_init', [ __CLASS__, 'configure_smtp' ], 10, 1 );

        /*
         * AJAX: Admin retry for failed notifications.
         */
        add_action( 'wp_ajax_aavni_retry_owner_notification', [ __CLASS__, 'ajax_retry_notification' ] );
    }

    /* =========================================================
     * HOOK HANDLERS
     * ========================================================= */

    /**
     * Handle new order from checkout (receives WC_Order object).
     *
     * @param WC_Order $order
     */
    public static function handle_new_order( $order ) {
        if ( ! $order instanceof WC_Order ) {
            return;
        }
        self::maybe_send( $order );
    }

    /**
     * Handle new order by ID (from woocommerce_new_order hook).
     *
     * @param int $order_id
     */
    public static function handle_new_order_by_id( $order_id ) {
        $order = wc_get_order( absint( $order_id ) );
        if ( ! $order || ! $order instanceof WC_Order || $order instanceof WC_Order_Refund ) {
            return;
        }
        self::maybe_send( $order );
    }

    /**
     * Handle payment complete (for Razorpay/online gateways).
     * Only sends if primary new-order email was NOT already dispatched.
     *
     * @param int $order_id
     */
    public static function handle_payment_complete( $order_id ) {
        $order = wc_get_order( absint( $order_id ) );
        if ( ! $order || ! $order instanceof WC_Order ) {
            return;
        }
        // Only send if we haven't sent yet (primary hook may have fired)
        if ( $order->get_meta( self::SENT_META_KEY ) ) {
            return;
        }
        self::maybe_send( $order );
    }

    /**
     * AJAX handler: Admin retry for a failed notification.
     */
    public static function ajax_retry_notification() {
        check_ajax_referer( 'aavni_retry_owner_notif', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => 'Permission denied.' ] );
        }

        $order_id = absint( $_POST['order_id'] ?? 0 );
        $order    = wc_get_order( $order_id );

        if ( ! $order ) {
            wp_send_json_error( [ 'message' => 'Order not found.' ] );
        }

        // Clear sent/failed flags so retry is allowed
        $order->delete_meta_data( self::SENT_META_KEY );
        $order->delete_meta_data( self::FAILED_META_KEY );
        $order->save();

        $result = self::send_owner_notification( $order );

        if ( $result ) {
            wp_send_json_success( [ 'message' => 'Notification email sent successfully.' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Email send attempt failed. Check SMTP settings and error logs.' ] );
        }
    }

    /* =========================================================
     * CORE SEND LOGIC
     * ========================================================= */

    /**
     * Guard: only send if notification hasn't been dispatched yet for this order.
     *
     * @param WC_Order $order
     */
    private static function maybe_send( WC_Order $order ) {
        // Deduplication: skip if already sent
        if ( $order->get_meta( self::SENT_META_KEY ) ) {
            return;
        }

        // Skip refund objects
        if ( $order instanceof WC_Order_Refund ) {
            return;
        }

        self::send_owner_notification( $order );
    }

    /**
     * Build and send the owner notification email.
     *
     * @param  WC_Order $order
     * @return bool
     */
    public static function send_owner_notification( WC_Order $order ) {
        $owner_email = self::get_owner_email();

        if ( ! is_email( $owner_email ) ) {
            self::log( 'Owner email address is invalid or not configured. Order #' . $order->get_id() );
            return false;
        }

        $order_id  = $order->get_id();
        $order_num = method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : $order_id;

        $subject = sprintf( 'New Order Received — AAVNI Order #%s', $order_num );
        $html    = self::build_email_html( $order );
        $text    = self::build_email_text( $order );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: AAVNI Store <' . ( get_option( 'admin_email' ) ?: $owner_email ) . '>',
            'X-AAVNI-Order: ' . $order_id,
        ];

        // Use wp_mail with both HTML and text (multipart handled by PHPMailer)
        $sent = false;
        try {
            // Temporarily inject plain-text alternative body
            add_action( 'phpmailer_init', [ __CLASS__, 'set_alt_body' ], 20 );
            self::$_alt_body = $text;

            $sent = wp_mail( $owner_email, $subject, $html, $headers );

            remove_action( 'phpmailer_init', [ __CLASS__, 'set_alt_body' ], 20 );
        } catch ( \Throwable $e ) {
            remove_action( 'phpmailer_init', [ __CLASS__, 'set_alt_body' ], 20 );
            self::log( 'Exception sending owner notification for Order #' . $order_id . ': ' . $e->getMessage() );
        }

        if ( $sent ) {
            // Mark as sent to prevent duplicates
            $order->update_meta_data( self::SENT_META_KEY, current_time( 'mysql' ) );
            $order->delete_meta_data( self::FAILED_META_KEY );
            $order->save();
            self::log( 'Owner notification sent successfully for Order #' . $order_id . ' to ' . $owner_email );
        } else {
            $last_error = self::get_last_mail_error();
            $fail_msg   = $last_error ?: 'wp_mail() returned false. Check PHP mail / SMTP configuration.';
            $order->update_meta_data( self::FAILED_META_KEY, $fail_msg );
            $order->save();
            self::log( 'FAILED to send owner notification for Order #' . $order_id . ': ' . $fail_msg );
        }

        return $sent;
    }

    /* =========================================================
     * EMAIL CONTENT BUILDERS
     * ========================================================= */

    /**
     * Build rich HTML email body.
     *
     * @param  WC_Order $order
     * @return string
     */
    private static function build_email_html( WC_Order $order ) {
        $order_id      = $order->get_id();
        $order_num     = method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : $order_id;
        $date          = $order->get_date_created() ? $order->get_date_created()->date( 'd M Y, h:i A' ) : current_time( 'd M Y, h:i A' );
        $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ?: 'Guest Customer';
        $phone         = $order->get_billing_phone() ?: '—';
        $email         = $order->get_billing_email() ?: '—';
        $ship_addr     = $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address() ?: '—';
        $bill_addr     = $order->get_formatted_billing_address() ?: '—';
        $payment_title = $order->get_payment_method_title() ?: 'Cash on Delivery';
        $payment_id    = $order->get_payment_method();
        $order_status  = wc_get_order_status_name( $order->get_status() );
        $customer_note = $order->get_customer_note() ?: '';
        $order_total   = wc_price( $order->get_total(), [ 'currency' => $order->get_currency() ] );
        $subtotal      = wc_price( $order->get_subtotal(), [ 'currency' => $order->get_currency() ] );
        $shipping_cost = (float) $order->get_shipping_total();
        $tax_total     = (float) $order->get_total_tax();
        $discount      = (float) $order->get_discount_total();

        // Payment status label
        $pay_status_label = self::get_payment_status_label( $order );

        // Admin order URL
        $admin_order_url = admin_url( 'admin.php?page=aavni-orders' );

        // Items
        $items_html = '';
        foreach ( $order->get_items() as $item ) {
            $product   = $item->get_product();
            $item_name = $item->get_name();
            $qty       = $item->get_quantity();
            $line_tot  = wc_price( $item->get_total(), [ 'currency' => $order->get_currency() ] );
            $sku       = $product ? $product->get_sku() : '';

            // Variation meta (size, color, etc.)
            $variation_html = '';
            if ( $item->get_variation_id() ) {
                foreach ( $item->get_formatted_meta_data( '_', true ) as $meta ) {
                    $variation_html .= '<br><span style="font-size:11.5px;color:#888;">' . esc_html( $meta->display_key ) . ': <strong>' . wp_kses_post( $meta->display_value ) . '</strong></span>';
                }
            }

            $items_html .= '
            <tr>
                <td style="padding:10px 12px; border-bottom:1px solid #F0EBE4; font-size:13.5px; color:#1B1A18;">
                    <strong>' . esc_html( $item_name ) . '</strong>'
                    . ( $sku ? '<br><span style="font-size:11px;color:#999;">SKU: ' . esc_html( $sku ) . '</span>' : '' )
                    . $variation_html . '
                </td>
                <td style="padding:10px 12px; border-bottom:1px solid #F0EBE4; text-align:center; font-size:13.5px; color:#1B1A18;">' . esc_html( $qty ) . '</td>
                <td style="padding:10px 12px; border-bottom:1px solid #F0EBE4; text-align:right; font-size:13.5px; color:#1B1A18; font-weight:600;">' . $line_tot . '</td>
            </tr>';
        }

        // Payment info box
        $is_cod = ( $payment_id === 'cod' );
        $pay_box_bg    = $is_cod ? '#FFF8EC' : '#EBF7ED';
        $pay_box_color = $is_cod ? '#7D5C00' : '#155724';
        $pay_box_border= $is_cod ? '#F7D97A' : '#C3E6CB';
        $pay_icon      = $is_cod ? '💵' : '✅';

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { margin:0; padding:0; background:#FAF8F5; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif; }
  table { border-collapse:collapse; }
  .wrap { max-width:640px; margin:24px auto; }
  a { color:#A85A48; }
</style>
</head>
<body>
<div class="wrap">
  <!-- Header -->
  <table width="100%" style="background:#A85A48; border-radius:8px 8px 0 0;">
    <tr>
      <td style="padding:28px 32px;">
        <div style="font-family:Georgia,serif; font-size:28px; font-weight:700; letter-spacing:3px; color:#fff; text-transform:uppercase;">AAVNI</div>
        <div style="font-size:11px; letter-spacing:1.5px; color:rgba(255,255,255,0.8); text-transform:uppercase; margin-top:4px;">Handcrafted Luxury · Jaipur, Rajasthan</div>
      </td>
      <td style="padding:28px 32px; text-align:right;">
        <div style="background:rgba(255,255,255,0.18); border:2px solid rgba(255,255,255,0.6); border-radius:8px; display:inline-block; padding:10px 20px;">
          <div style="font-size:11px; font-weight:700; color:rgba(255,255,255,0.85); letter-spacing:0.1em; text-transform:uppercase;">NEW ORDER</div>
          <div style="font-size:22px; font-weight:700; color:#fff; margin-top:2px;">#<?php echo esc_html( $order_num ); ?></div>
        </div>
      </td>
    </tr>
  </table>

  <!-- Alert banner -->
  <table width="100%" style="background:#D4501C;">
    <tr>
      <td style="padding:14px 32px; font-size:16px; font-weight:700; color:#fff; letter-spacing:0.05em;">
        🛍️ NEW ORDER RECEIVED! — <?php echo esc_html( $date ); ?>
      </td>
    </tr>
  </table>

  <!-- Main body -->
  <table width="100%" style="background:#fff; border:1px solid #EAE5DE; border-top:none;">
    <tr>
      <td style="padding:28px 32px;">

        <!-- Order total hero -->
        <table width="100%" style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; margin-bottom:24px;">
          <tr>
            <td style="padding:18px 22px;">
              <div style="font-size:12px; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:0.08em;">Order Total</div>
              <div style="font-size:32px; font-weight:700; color:#A85A48; margin-top:4px; font-family:Georgia,serif;"><?php echo $order_total; ?></div>
              <div style="font-size:13px; color:#666; margin-top:4px;">Order #<?php echo esc_html( $order_num ); ?> &nbsp;·&nbsp; Status: <strong><?php echo esc_html( $order_status ); ?></strong></div>
            </td>
            <td style="padding:18px 22px; text-align:right; vertical-align:middle;">
              <a href="<?php echo esc_url( $admin_order_url ); ?>"
                 style="display:inline-block; background:#A85A48; color:#fff; text-decoration:none; padding:13px 24px; border-radius:6px; font-weight:700; font-size:13.5px; letter-spacing:0.04em;">
                📋 VIEW IN ADMIN PANEL →
              </a>
            </td>
          </tr>
        </table>

        <!-- Payment Status -->
        <table width="100%" style="background:<?php echo $pay_box_bg; ?>; border:1px solid <?php echo $pay_box_border; ?>; border-radius:8px; margin-bottom:24px;">
          <tr>
            <td style="padding:14px 20px;">
              <strong style="font-size:13px; color:<?php echo $pay_box_color; ?>; display:block; margin-bottom:4px;">
                <?php echo $pay_icon; ?> PAYMENT METHOD: <?php echo esc_html( strtoupper( $payment_title ) ); ?>
              </strong>
              <span style="font-size:13px; color:<?php echo $pay_box_color; ?>; font-weight:700;">
                PAYMENT STATUS: <?php echo esc_html( $pay_status_label ); ?>
              </span>
            </td>
          </tr>
        </table>

        <!-- Customer & Delivery Grid -->
        <table width="100%" style="margin-bottom:24px;" cellspacing="0" cellpadding="0">
          <tr>
            <td width="48%" style="vertical-align:top; padding-right:10px;">
              <div style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; padding:16px 18px;">
                <div style="font-size:12px; font-weight:700; color:#A85A48; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:10px;">👤 Customer Details</div>
                <div style="font-size:14px; font-weight:700; color:#1B1A18; margin-bottom:4px;"><?php echo esc_html( $customer_name ); ?></div>
                <div style="font-size:13px; color:#555; margin-bottom:3px;">📞 <?php echo esc_html( $phone ); ?></div>
                <div style="font-size:13px; color:#555; margin-bottom:10px;">✉️ <?php echo esc_html( $email ); ?></div>
                <div style="font-size:11.5px; font-weight:700; color:#888; text-transform:uppercase; margin-bottom:5px;">Billing Address</div>
                <div style="font-size:13px; color:#555; line-height:1.5;"><?php echo wp_kses_post( $bill_addr ); ?></div>
              </div>
            </td>
            <td width="4%"></td>
            <td width="48%" style="vertical-align:top; padding-left:10px;">
              <div style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; padding:16px 18px;">
                <div style="font-size:12px; font-weight:700; color:#A85A48; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:10px;">📦 Shipping Address</div>
                <div style="font-size:13px; color:#555; line-height:1.5;"><?php echo wp_kses_post( $ship_addr ); ?></div>
              </div>
            </td>
          </tr>
        </table>

        <!-- Items Table -->
        <div style="font-size:12px; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:8px;">🛍️ Ordered Items</div>
        <table width="100%" style="border:1px solid #EAE5DE; border-radius:8px; overflow:hidden; margin-bottom:20px;">
          <thead>
            <tr style="background:#F3EFE9;">
              <th style="padding:10px 12px; text-align:left; font-size:12px; font-weight:700; color:#6B655C; text-transform:uppercase; letter-spacing:0.05em;">Product</th>
              <th style="padding:10px 12px; text-align:center; font-size:12px; font-weight:700; color:#6B655C; text-transform:uppercase; letter-spacing:0.05em;">Qty</th>
              <th style="padding:10px 12px; text-align:right; font-size:12px; font-weight:700; color:#6B655C; text-transform:uppercase; letter-spacing:0.05em;">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php echo $items_html; ?>
          </tbody>
        </table>

        <!-- Order Summary Totals -->
        <table width="100%" style="margin-bottom:24px;">
          <tr>
            <td></td>
            <td width="280px" style="border:1px solid #EAE5DE; border-radius:8px; padding:16px 18px;">
              <?php if ( $discount > 0 ) : ?>
              <div style="display:flex; justify-content:space-between; font-size:13px; color:#555; margin-bottom:6px;">
                <span>Discount:</span>
                <span style="color:#1E7E34; font-weight:700;">−<?php echo wc_price( $discount, [ 'currency' => $order->get_currency() ] ); ?></span>
              </div>
              <?php endif; ?>
              <div style="display:flex; justify-content:space-between; font-size:13px; color:#555; margin-bottom:6px;">
                <span>Subtotal:</span>
                <span><?php echo $subtotal; ?></span>
              </div>
              <div style="display:flex; justify-content:space-between; font-size:13px; color:#555; margin-bottom:6px;">
                <span>Shipping:</span>
                <span><?php echo $shipping_cost > 0 ? wc_price( $shipping_cost, [ 'currency' => $order->get_currency() ] ) : '<strong style="color:#1E7E34;">FREE</strong>'; ?></span>
              </div>
              <?php if ( $tax_total > 0 ) : ?>
              <div style="display:flex; justify-content:space-between; font-size:13px; color:#555; margin-bottom:6px;">
                <span>GST / Tax:</span>
                <span><?php echo wc_price( $tax_total, [ 'currency' => $order->get_currency() ] ); ?></span>
              </div>
              <?php endif; ?>
              <div style="display:flex; justify-content:space-between; font-size:15px; font-weight:700; color:#A85A48; border-top:2px solid #EAE5DE; padding-top:10px; margin-top:8px;">
                <span>ORDER TOTAL:</span>
                <span><?php echo $order_total; ?></span>
              </div>
            </td>
          </tr>
        </table>

        <?php if ( ! empty( $customer_note ) ) : ?>
        <!-- Customer Note -->
        <div style="background:#FFFBF0; border:1px solid #F7D97A; border-radius:8px; padding:14px 18px; margin-bottom:24px;">
          <div style="font-size:12px; font-weight:700; color:#7D5C00; text-transform:uppercase; margin-bottom:6px;">📝 Customer Note</div>
          <div style="font-size:13.5px; color:#555; line-height:1.5;"><?php echo esc_html( $customer_note ); ?></div>
        </div>
        <?php endif; ?>

        <!-- CTA Button -->
        <table width="100%" style="margin-bottom:16px;">
          <tr>
            <td style="text-align:center; padding:10px 0;">
              <a href="<?php echo esc_url( $admin_order_url ); ?>"
                 style="display:inline-block; background:#A85A48; color:#fff; text-decoration:none; padding:16px 36px; border-radius:6px; font-weight:700; font-size:14px; letter-spacing:0.06em; text-transform:uppercase;">
                VIEW ORDER IN ADMIN PANEL →
              </a>
            </td>
          </tr>
        </table>

        <p style="font-size:12px; color:#999; text-align:center; margin:0;">
          This is an automated store management notification. Do not reply to this email.<br>
          AAVNI Store Admin Panel · <a href="<?php echo esc_url( admin_url() ); ?>" style="color:#A85A48;"><?php echo esc_url( admin_url() ); ?></a>
        </p>

      </td>
    </tr>
  </table>

  <!-- Footer -->
  <table width="100%" style="background:#F5EFEB; border:1px solid #EAE5DE; border-top:none; border-radius:0 0 8px 8px;">
    <tr>
      <td style="padding:16px 32px; text-align:center; font-size:11.5px; color:#888;">
        <p style="margin:0 0 4px;">AAVNI Handcrafted Ethnic &amp; Contemporary Wear · Jaipur, Rajasthan, India · PIN 302001</p>
        <p style="margin:0;">Care: <?php echo esc_html( get_option( 'aavni_store_care_phone', '+91 9828103222' ) ); ?> &bull; <?php echo esc_html( get_option( 'aavni_store_support_email', 'care@aavniclothing.com' ) ); ?></p>
      </td>
    </tr>
  </table>
</div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Build plain-text fallback email body.
     *
     * @param  WC_Order $order
     * @return string
     */
    private static function build_email_text( WC_Order $order ) {
        $order_num     = method_exists( $order, 'get_order_number' ) ? $order->get_order_number() : $order->get_id();
        $date          = $order->get_date_created() ? $order->get_date_created()->date( 'd M Y, h:i A' ) : current_time( 'd M Y, h:i A' );
        $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ?: 'Guest Customer';
        $phone         = $order->get_billing_phone() ?: '—';
        $email         = $order->get_billing_email() ?: '—';
        $payment_title = $order->get_payment_method_title() ?: 'Cash on Delivery';
        $pay_status    = self::get_payment_status_label( $order );
        $ship_addr     = wp_strip_all_tags( $order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address() ?: '—' );
        $order_total   = strip_tags( wc_price( $order->get_total() ) );
        $customer_note = $order->get_customer_note() ?: '';

        $lines = [
            '========================================',
            'AAVNI STORE — NEW ORDER RECEIVED!',
            '========================================',
            '',
            'ORDER #: ' . $order_num,
            'DATE   : ' . $date,
            'TOTAL  : ' . $order_total,
            'STATUS : ' . wc_get_order_status_name( $order->get_status() ),
            '',
            '--- CUSTOMER ---',
            'Name  : ' . $customer_name,
            'Phone : ' . $phone,
            'Email : ' . $email,
            '',
            '--- SHIPPING ADDRESS ---',
            $ship_addr,
            '',
            '--- PAYMENT ---',
            'Method : ' . $payment_title,
            'Status : ' . $pay_status,
            '',
            '--- ITEMS ---',
        ];

        foreach ( $order->get_items() as $item ) {
            $lines[] = '  · ' . $item->get_name() . ' × ' . $item->get_quantity() . ' = ' . strip_tags( wc_price( $item->get_total() ) );
        }

        $lines[] = '';
        $lines[] = 'ORDER TOTAL: ' . $order_total;

        if ( ! empty( $customer_note ) ) {
            $lines[] = '';
            $lines[] = '--- CUSTOMER NOTE ---';
            $lines[] = $customer_note;
        }

        $lines[] = '';
        $lines[] = '--- ADMIN PANEL LINK ---';
        $lines[] = admin_url( 'admin.php?page=aavni-orders' );
        $lines[] = '';
        $lines[] = '========================================';
        $lines[] = 'AAVNI Handcrafted Ethnic & Contemporary Wear · Jaipur';
        $lines[] = get_option( 'aavni_store_care_phone', '+91 9828103222' );
        $lines[] = '========================================';

        return implode( "\n", $lines );
    }

    /* =========================================================
     * SMTP CONFIGURATION
     * ========================================================= */

    /**
     * Configure PHPMailer for SMTP if credentials are stored in options.
     * This only activates if the admin has saved SMTP settings.
     * Credentials are NEVER hardcoded here — read from database options.
     *
     * @param PHPMailer $phpmailer
     */
    public static function configure_smtp( $phpmailer ) {
        $smtp_host = get_option( 'aavni_smtp_host', '' );
        $smtp_user = get_option( 'aavni_smtp_username', '' );
        $smtp_pass = get_option( 'aavni_smtp_password', '' ); // stored encrypted via option
        $smtp_port = (int) get_option( 'aavni_smtp_port', 587 );
        $smtp_enc  = get_option( 'aavni_smtp_encryption', 'tls' );

        if ( empty( $smtp_host ) || empty( $smtp_user ) || empty( $smtp_pass ) ) {
            return; // SMTP not configured — fall back to PHP mail()
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = $smtp_host;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = $smtp_user;
        $phpmailer->Password   = $smtp_pass;
        $phpmailer->Port       = $smtp_port;
        $phpmailer->SMTPSecure = ( $smtp_enc === 'ssl' ) ? 'ssl' : 'tls';
        $phpmailer->CharSet    = 'UTF-8';
    }

    /* =========================================================
     * HELPERS
     * ========================================================= */

    /** @var string Alt body for plain-text email part */
    private static $_alt_body = '';

    /**
     * Inject plain-text alt body into PHPMailer.
     *
     * @param PHPMailer $phpmailer
     */
    public static function set_alt_body( $phpmailer ) {
        if ( ! empty( self::$_alt_body ) ) {
            $phpmailer->AltBody = self::$_alt_body;
        }
    }

    /**
     * Get the configured owner email (falls back to default).
     *
     * @return string
     */
    public static function get_owner_email() {
        return get_option( self::EMAIL_OPTION, self::DEFAULT_EMAIL );
    }

    /**
     * Human-readable payment status label.
     *
     * @param  WC_Order $order
     * @return string
     */
    private static function get_payment_status_label( WC_Order $order ) {
        $payment_id = $order->get_payment_method();
        $status     = $order->get_status();

        if ( $payment_id === 'cod' ) {
            return 'PAYMENT PENDING / PAY ON DELIVERY';
        }

        if ( $status === 'completed' || $status === 'processing' ) {
            return 'PAID — PAYMENT RECEIVED ONLINE';
        }

        if ( $status === 'pending' ) {
            return 'PAYMENT PENDING — AWAITING CONFIRMATION';
        }

        if ( $status === 'failed' ) {
            return 'PAYMENT FAILED';
        }

        return strtoupper( wc_get_order_status_name( $status ) );
    }

    /**
     * Capture and return the last wp_mail error.
     *
     * @return string
     */
    private static function get_last_mail_error() {
        global $phpmailer;
        if ( isset( $phpmailer ) && is_object( $phpmailer ) && ! empty( $phpmailer->ErrorInfo ) ) {
            return $phpmailer->ErrorInfo;
        }
        return '';
    }

    /**
     * Write to WordPress/WooCommerce error log.
     *
     * @param string $message
     */
    private static function log( $message ) {
        if ( class_exists( 'WC_Logger' ) ) {
            $logger  = wc_get_logger();
            $context = [ 'source' => 'aavni-owner-notifications' ];
            $logger->info( '[AAVNI New Order Notification] ' . $message, $context );
        } else {
            error_log( '[AAVNI New Order Notification] ' . $message );
        }
    }

    /**
     * Check if owner notification was sent for an order.
     * Useful for admin UI display.
     *
     * @param  int $order_id
     * @return string|false  Timestamp if sent, false if not.
     */
    public static function was_sent( $order_id ) {
        $order = wc_get_order( absint( $order_id ) );
        if ( ! $order ) return false;
        return $order->get_meta( self::SENT_META_KEY ) ?: false;
    }

    /**
     * Get failure reason for an order, if any.
     *
     * @param  int $order_id
     * @return string|false
     */
    public static function get_failure_reason( $order_id ) {
        $order = wc_get_order( absint( $order_id ) );
        if ( ! $order ) return false;
        return $order->get_meta( self::FAILED_META_KEY ) ?: false;
    }
}
