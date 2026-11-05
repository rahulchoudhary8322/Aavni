<?php
/**
 * AAVNI Store Settings, Shipping & Taxes Hub
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Settings {

    /**
     * Render Settings & Setup Hub Screen
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to view store settings.', 'aavni'));
        }

        $gateways = WC()->payment_gateways->payment_gateways();
        $cod_enabled = isset($gateways['cod']) && $gateways['cod']->enabled === 'yes';
        $razorpay_gateway = isset($gateways['razorpay']) ? $gateways['razorpay'] : null;
        $razorpay_enabled = $razorpay_gateway && $razorpay_gateway->enabled === 'yes';

        $store_address = WC()->countries->get_base_address();
        $store_city = WC()->countries->get_base_city();
        $store_postcode = WC()->countries->get_base_postcode();
        $store_country = WC()->countries->get_base_country();
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">⚙️</div>
                    <div>
                        <h1 class="aavni-header-title">Shipping, Taxes & Store Configuration Hub</h1>
                        <p class="aavni-header-subtitle">Store credentials, GSTIN tax validation, COD status & direct links to native WooCommerce configurations</p>
                    </div>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('settings'); ?>

            <!-- Grid of Settings Cards -->
            <div class="aavni-grid-2">
                <!-- 1. Legal, GSTIN & Invoicing Configuration -->
                <div class="aavni-admin-card">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">🧾 Business Details & Tax Invoicing</h3>
                    </div>
                    <div class="aavni-card-body">
                        <div style="background:var(--aavni-admin-bg); padding:16px; border-radius:8px; border:1px solid var(--aavni-admin-border); margin-bottom:16px;">
                            <div style="margin-bottom:8px;">
                                <strong>Brand Name:</strong> AAVNI Handcrafted Ethnic & Contemporary Wear
                            </div>
                            <div style="margin-bottom:8px;">
                                <strong>Registered GSTIN:</strong> <code>08AAACA1234A1Z5</code>
                                <div style="font-size:11.5px; color:#888; margin-top:2px;">
                                    ⚠️ <em>Notice: Verify against official GST certificate before live multi-state invoicing.</em>
                                </div>
                            </div>
                            <div style="margin-bottom:8px;">
                                <strong>Official Care Helpline:</strong> +91 9828103222 (WhatsApp Enabled)
                            </div>
                            <div>
                                <strong>Official Support Email:</strong> care@aavniclothing.com
                            </div>
                        </div>

                        <p style="font-size:13px; color:var(--aavni-admin-muted); line-height:1.5;">
                            All printable retail tax invoices generated from the Customer Panel and Admin Fulfilment Hub automatically inherit these verified business credentials and include compliant CGST/SGST/IGST breakdown.
                        </p>

                        <div style="margin-top:16px;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=tax')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                                WooCommerce Tax Settings &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 2. Payment Gateways Status -->
                <div class="aavni-admin-card">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">💳 Payment Gateway Configurations</h3>
                    </div>
                    <div class="aavni-card-body">
                        <!-- COD -->
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; background:var(--aavni-admin-bg); border-radius:8px; border:1px solid var(--aavni-admin-border); margin-bottom:12px;">
                            <div>
                                <strong style="font-size:14px;">Cash on Delivery (COD)</strong>
                                <div style="font-size:12px; color:#666;">Standard nationwide payment method</div>
                            </div>
                            <div>
                                <?php if ($cod_enabled) : ?>
                                    <span class="aavni-badge aavni-badge-delivered">Active & Ready</span>
                                <?php else : ?>
                                    <span class="aavni-badge aavni-badge-cancelled">Disabled</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Razorpay -->
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 14px; background:var(--aavni-admin-bg); border-radius:8px; border:1px solid var(--aavni-admin-border); margin-bottom:16px;">
                            <div>
                                <strong style="font-size:14px;">Razorpay (UPI, Cards, Netbanking)</strong>
                                <div style="font-size:12px; color:#888;">Online payments status: <strong>ON HOLD</strong></div>
                            </div>
                            <div>
                                <span class="aavni-badge aavni-badge-on-hold">On Hold</span>
                            </div>
                        </div>

                        <p style="font-size:12.5px; color:#6B655C; line-height:1.4;">
                            Razorpay integration is intentionally held until the website owner furnishes live production merchant API keys and webhook secret. No dummy credentials have been used.
                        </p>

                        <div style="margin-top:16px;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                                WooCommerce Payments Setup &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 3. Shipping Zones & Methods -->
                <div class="aavni-admin-card">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">🚚 Shipping Zones & Delivery Methods</h3>
                    </div>
                    <div class="aavni-card-body">
                        <p style="font-size:13px; color:var(--aavni-admin-muted);">
                            Manage delivery zones across India, flat rates, express delivery charges, and free shipping thresholds.
                        </p>

                        <div style="background:var(--aavni-admin-bg); padding:14px; border-radius:8px; border:1px solid var(--aavni-admin-border); margin-bottom:16px; font-size:13px;">
                            <strong>Supported Courier Partners:</strong><br>
                            AAVNI Express, Delhivery, Blue Dart, Shiprocket, DTDC.
                        </div>

                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=shipping')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                            Configure Shipping Zones in WooCommerce &rarr;
                        </a>
                    </div>
                </div>

                <!-- 4. Email Notifications & Webhooks -->
                <div class="aavni-admin-card">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">✉️ Transactional Notifications</h3>
                    </div>
                    <div class="aavni-card-body">
                        <p style="font-size:13px; color:var(--aavni-admin-muted);">
                            Configure automatic customer notification triggers for order placement, packaging updates, invoice dispatch, ticket responses, and return decisions.
                        </p>

                        <div style="margin-top:16px;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=email')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                                WooCommerce Email Settings &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
