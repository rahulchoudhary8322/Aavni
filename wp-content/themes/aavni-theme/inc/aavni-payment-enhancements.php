<?php
/**
 * AAVNI Luxury Indian E-Commerce Payment Enhancements
 * Production-ready payment flow, Indian gateway branding (Razorpay UPI, Cards, NetBanking, COD),
 * security trust badges, order management, and retry handling.
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. Enhance Payment Gateway Titles & Logos (Classic Checkout + Blocks Compatibility)
 */
add_filter('woocommerce_gateway_title', 'aavni_enhance_gateway_title', 20, 2);
function aavni_enhance_gateway_title($title, $gateway_id) {
    if ($gateway_id === 'razorpay') {
        $enhanced = '<span class="aavni-payment-method-header">';
        $enhanced .= '<span class="aavni-payment-name">Cards, UPI, NetBanking, Wallets</span>';
        $enhanced .= '<span class="aavni-gateway-badges">';
        $enhanced .= '<span class="aavni-chip aavni-chip-upi" title="Google Pay, PhonePe, Paytm, BHIM, UPI QR">UPI / QR</span>';
        $enhanced .= '<span class="aavni-chip aavni-chip-cards" title="Visa, MasterCard, RuPay, Maestro">Cards</span>';
        $enhanced .= '<span class="aavni-chip aavni-chip-nb" title="All Major Indian Banks">NetBanking</span>';
        $enhanced .= '<span class="aavni-chip aavni-chip-wallets" title="Paytm, Mobikwik">Wallets</span>';
        $enhanced .= '</span>';
        $enhanced .= '</span>';
        return $enhanced;
    }

    if ($gateway_id === 'cod') {
        $enhanced = '<span class="aavni-payment-method-header">';
        $enhanced .= '<span class="aavni-payment-name">Cash on Delivery (COD)</span>';
        $enhanced .= '<span class="aavni-gateway-badges">';
        $enhanced .= '<span class="aavni-chip aavni-chip-cod" title="Pay at Doorstep">Pay on Delivery</span>';
        $enhanced .= '</span>';
        $enhanced .= '</span>';
        return $enhanced;
    }

    return $title;
}

/**
 * 2. Enhance Payment Gateway Descriptions with Indian E-Commerce Trust Cues
 */
add_filter('woocommerce_gateway_description', 'aavni_enhance_gateway_description', 20, 2);
function aavni_enhance_gateway_description($description, $gateway_id) {
    if ($gateway_id === 'razorpay') {
        $html = '<div class="aavni-payment-desc-box">';
        $html .= '<p class="aavni-desc-intro">Pay instantly and securely using your preferred Indian payment method:</p>';
        $html .= '<div class="aavni-payment-methods-grid">';
        $html .= '<div class="aavni-pm-item"><strong>⚡ UPI / QR:</strong> Google Pay, PhonePe, Paytm, BHIM, Cred, and any UPI App</div>';
        $html .= '<div class="aavni-pm-item"><strong>💳 Cards:</strong> RuPay, Visa, MasterCard, Maestro, and American Express</div>';
        $html .= '<div class="aavni-pm-item"><strong>🏦 NetBanking:</strong> SBI, HDFC, ICICI, Axis, Kotak & 50+ Indian Banks</div>';
        $html .= '<div class="aavni-pm-item"><strong>👛 Wallets:</strong> Paytm Wallet, PhonePe, MobiKwik & more</div>';
        $html .= '</div>';
        $html .= '<div class="aavni-security-footer">';
        $html .= '<span class="aavni-sec-badge">🔒 256-Bit SSL Encryption</span>';
        $html .= '<span class="aavni-sec-badge">🛡️ RBI Compliant Payment Gateway</span>';
        $html .= '<span class="aavni-sec-badge">⚡ Instant Confirmation</span>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    if ($gateway_id === 'cod') {
        $html = '<div class="aavni-payment-desc-box aavni-cod-desc-box">';
        $html .= '<p class="aavni-desc-intro">Pay conveniently with cash or UPI at your doorstep when your package arrives.</p>';
        $html .= '<div class="aavni-security-footer">';
        $html .= '<span class="aavni-sec-badge">📦 Doorstep Delivery</span>';
        $html .= '<span class="aavni-sec-badge">✓ Exact Change Appreciated</span>';
        $html .= '<span class="aavni-sec-badge">🔄 Easy 7-Day Exchange</span>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    return $description;
}

/**
 * 3. Add Trust & Guarantees Banner below Payment Section on Checkout
 */
add_action('woocommerce_review_order_after_payment', 'aavni_render_checkout_trust_badges');
add_action('woocommerce_after_checkout_form', 'aavni_render_checkout_trust_badges');
function aavni_render_checkout_trust_badges() {
    static $rendered = false;
    if ($rendered) return;
    $rendered = true;
    ?>
    <div class="aavni-checkout-trust-banner">
        <div class="aavni-trust-item">
            <span class="aavni-trust-icon">🔒</span>
            <div class="aavni-trust-info">
                <strong>100% Secure Checkout</strong>
                <span>Bank-grade 256-bit encryption</span>
            </div>
        </div>
        <div class="aavni-trust-item">
            <span class="aavni-trust-icon">🚚</span>
            <div class="aavni-trust-info">
                <strong>Free Express Shipping</strong>
                <span>All India dispatch within 24-48 hrs</span>
            </div>
        </div>
        <div class="aavni-trust-item">
            <span class="aavni-trust-icon">🔄</span>
            <div class="aavni-trust-info">
                <strong>7-Day Easy Exchange</strong>
                <span>Hassle-free size & style exchange</span>
            </div>
        </div>
        <div class="aavni-trust-item">
            <span class="aavni-trust-icon">💬</span>
            <div class="aavni-trust-info">
                <strong>Instant WhatsApp Help</strong>
                <span>+91 9828103222 (9 AM - 9 PM)</span>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 4. Display Payment Gateway Meta in Admin Order Details
 */
add_action('woocommerce_admin_order_data_after_billing_address', 'aavni_admin_order_payment_meta', 10, 1);
function aavni_admin_order_payment_meta($order) {
    if (!$order) return;

    $payment_method = $order->get_payment_method();
    $transaction_id = $order->get_transaction_id();
    $rzp_order_id   = $order->get_meta('razorpay_order_id') ?: $order->get_meta('razorpay_order_id' . $order->get_id());

    echo '<div class="aavni-admin-payment-meta" style="margin-top:15px; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">';
    echo '<h4 style="margin:0 0 8px 0; font-size:13px; color:#1e293b;">Indian Payment Gateway Details</h4>';
    echo '<p style="margin:2px 0; font-size:12px;"><strong>Method:</strong> ' . esc_html($order->get_payment_method_title()) . ' (' . esc_html($payment_method) . ')</p>';

    if (!empty($transaction_id)) {
        echo '<p style="margin:2px 0; font-size:12px;"><strong>Transaction / Payment ID:</strong> <code style="background:#e0f2fe; padding:2px 6px; border-radius:4px; color:#0369a1;">' . esc_html($transaction_id) . '</code></p>';
    }

    if (!empty($rzp_order_id)) {
        echo '<p style="margin:2px 0; font-size:12px;"><strong>Razorpay Order ID:</strong> <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;">' . esc_html($rzp_order_id) . '</code></p>';
    }

    echo '<p style="margin:2px 0; font-size:12px;"><strong>Order Status:</strong> <span style="font-weight:600; text-transform:uppercase;">' . esc_html($order->get_status()) . '</span></p>';
    echo '</div>';
}

/**
 * 5. Handle Payment Retry on Failed or Cancelled Orders
 */
add_filter('woocommerce_order_button_text', 'aavni_custom_checkout_button_text');
function aavni_custom_checkout_button_text($text) {
    if (is_checkout_pay_page()) {
        return __('Proceed to Secure Payment', 'aavni');
    }
    return __('Place Order', 'aavni');
}
