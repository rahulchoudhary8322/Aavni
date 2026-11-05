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
     * Handle Settings Save Action
     */
    public static function handle_actions() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'save_aavni_settings') {
            check_admin_referer('aavni_save_settings_nonce');

            $brand_name = sanitize_text_field($_POST['store_brand_name'] ?? '');
            $gstin = sanitize_text_field($_POST['store_gstin'] ?? '');
            $phone = sanitize_text_field($_POST['store_care_phone'] ?? '');
            $email = sanitize_email($_POST['store_support_email'] ?? '');
            $return_days = absint($_POST['store_return_days'] ?? 7);

            // Owner notification settings
            $owner_email   = sanitize_email($_POST['aavni_owner_email'] ?? '');
            $smtp_host     = sanitize_text_field($_POST['aavni_smtp_host'] ?? '');
            $smtp_user     = sanitize_email($_POST['aavni_smtp_username'] ?? '');
            $smtp_port     = absint($_POST['aavni_smtp_port'] ?? 587);
            $smtp_enc      = sanitize_text_field($_POST['aavni_smtp_encryption'] ?? 'tls');

            update_option('aavni_store_legal_name', $brand_name ?: 'AAVNI Handcrafted Ethnic & Contemporary Wear');
            update_option('aavni_store_gstin', $gstin ?: '08AAACA1234A1Z5');
            update_option('aavni_store_care_phone', $phone ?: '+91 9828103222');
            update_option('aavni_store_support_email', $email ?: 'care@aavniclothing.com');
            update_option('aavni_store_return_days', $return_days ?: 7);

            // Save owner email
            if (!empty($owner_email) && is_email($owner_email)) {
                update_option(AAVNI_New_Order_Notification::EMAIL_OPTION, $owner_email);
            }

            // Save SMTP settings (password only if provided — write-only)
            if (!empty($smtp_host)) update_option('aavni_smtp_host', $smtp_host);
            if (!empty($smtp_user)) update_option('aavni_smtp_username', $smtp_user);
            if (!empty($_POST['aavni_smtp_password'])) {
                update_option('aavni_smtp_password', sanitize_text_field($_POST['aavni_smtp_password']));
            }
            if (!empty($smtp_port)) update_option('aavni_smtp_port', $smtp_port);
            update_option('aavni_smtp_encryption', in_array($smtp_enc, ['tls', 'ssl', 'none']) ? $smtp_enc : 'tls');

            wp_safe_redirect(add_query_arg(['page' => 'aavni-settings', 'updated' => '1'], admin_url('admin.php')));
            exit;
        }
    }

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

        $legal_name = get_option('aavni_store_legal_name', 'AAVNI Handcrafted Ethnic & Contemporary Wear');
        $gstin = get_option('aavni_store_gstin', '08AAACA1234A1Z5');
        $care_phone = get_option('aavni_store_care_phone', '+91 9828103222');
        $support_email = get_option('aavni_store_support_email', 'care@aavniclothing.com');
        $return_days = get_option('aavni_store_return_days', 7);
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

            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px;">
                    <p><?php esc_html_e('Store settings and invoicing credentials saved successfully.', 'aavni'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Grid of Settings Cards -->
            <div class="aavni-grid-2">
                <!-- 1. Legal, GSTIN & Invoicing Configuration -->
                <div class="aavni-admin-card">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">🧾 Business Details & Tax Invoicing</h3>
                    </div>
                    <div class="aavni-card-body">
                        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-settings')); ?>">
                            <?php wp_nonce_field('aavni_save_settings_nonce'); ?>
                            <input type="hidden" name="aavni_action" value="save_aavni_settings" />

                            <p style="margin-top:0;">
                                <label for="store_brand_name"><strong>Store Legal / Brand Name:</strong></label><br>
                                <input type="text" name="store_brand_name" id="store_brand_name" value="<?php echo esc_attr($legal_name); ?>" required style="width:100%; margin-top:6px;" />
                            </p>

                            <p>
                                <label for="store_gstin"><strong>Registered GSTIN:</strong></label><br>
                                <input type="text" name="store_gstin" id="store_gstin" value="<?php echo esc_attr($gstin); ?>" required style="width:100%; margin-top:6px;" />
                                <span style="font-size:11.5px; color:#888;">Shown on all customer tax invoices and credit notes.</span>
                            </p>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div>
                                    <label for="store_care_phone"><strong>Care Helpline Phone:</strong></label><br>
                                    <input type="text" name="store_care_phone" id="store_care_phone" value="<?php echo esc_attr($care_phone); ?>" required style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="store_support_email"><strong>Support Email:</strong></label><br>
                                    <input type="email" name="store_support_email" id="store_support_email" value="<?php echo esc_attr($support_email); ?>" required style="width:100%; margin-top:6px;" />
                                </div>
                            </div>

                            <p style="margin-top:12px;">
                                <label for="store_return_days"><strong>Customer Return Window (Days):</strong></label><br>
                                <input type="number" min="1" max="60" name="store_return_days" id="store_return_days" value="<?php echo esc_attr($return_days); ?>" style="width:120px; margin-top:6px;" />
                                <span style="font-size:11.5px; color:#888; margin-left:8px;">Days from package delivery during which returns can be requested.</span>
                            </p>

                            <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
                                <button type="submit" class="aavni-btn aavni-btn-primary">Save Business Settings</button>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=tax')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                                    WooCommerce Tax Settings &rarr;
                                </a>
                            </div>
                        </form>
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
                            Razorpay integration is intentionally held until the website owner furnishes live production merchant API keys and webhook secret. COD remains active and functional.
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

                <!-- 3B. Shiprocket Official Logistics Integration -->
                <?php if (class_exists('AAVNI_Shiprocket_Settings')) : ?>
                    <div style="grid-column: 1 / -1;">
                        <?php AAVNI_Shiprocket_Settings::render_settings_section(); ?>
                    </div>
                <?php endif; ?>

                <!-- 4. Owner Notifications & SMTP -->
                <div class="aavni-admin-card" style="grid-column:1/-1;">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">✉️ New Order Email Notifications (Owner Alert)</h3>
                    </div>
                    <div class="aavni-card-body">
                        <?php
                        $owner_email  = get_option(AAVNI_New_Order_Notification::EMAIL_OPTION, AAVNI_New_Order_Notification::DEFAULT_EMAIL);
                        $smtp_host    = get_option('aavni_smtp_host', '');
                        $smtp_user    = get_option('aavni_smtp_username', '');
                        $smtp_port    = get_option('aavni_smtp_port', 587);
                        $smtp_enc     = get_option('aavni_smtp_encryption', 'tls');
                        $smtp_saved   = !empty($smtp_host) && !empty($smtp_user);
                        ?>

                        <!-- Status indicator -->
                        <?php
                        // Detect recent failures across last 10 orders
                        $args = ['limit' => 10, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'ids'];
                        $recent_orders = wc_get_orders($args);
                        $failed_orders = [];
                        foreach ($recent_orders as $oid) {
                            $f = AAVNI_New_Order_Notification::get_failure_reason($oid);
                            if ($f) $failed_orders[$oid] = $f;
                        }
                        if (!empty($failed_orders)) : ?>
                            <div style="background:#FEE2E2; border:1px solid #FCA5A5; border-radius:8px; padding:14px 18px; margin-bottom:20px;">
                                <strong style="color:#991B1B;">⚠️ Email Delivery Failures Detected</strong>
                                <p style="font-size:13px; color:#7F1D1D; margin:8px 0 0;">Some recent order notifications failed to send. This usually means PHP mail is not configured on your server (common on XAMPP). <strong>Configure SMTP below to fix this.</strong></p>
                                <ul style="font-size:12px; color:#7F1D1D; margin:10px 0 0; padding-left:18px;">
                                <?php foreach ($failed_orders as $oid => $reason) : ?>
                                    <li>Order #<?php echo esc_html($oid); ?>: <?php echo esc_html(wp_strip_all_tags($reason)); ?>
                                        <button type="button"
                                            class="aavni-retry-notif-btn"
                                            data-order-id="<?php echo esc_attr($oid); ?>"
                                            data-nonce="<?php echo esc_attr(wp_create_nonce('aavni_retry_owner_notif')); ?>"
                                            style="margin-left:8px; font-size:11px; padding:2px 8px; background:#fff; border:1px solid #FCA5A5; border-radius:4px; color:#991B1B; cursor:pointer; font-weight:700;">
                                            🔄 Retry
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else : ?>
                            <div style="background:#D1FAE5; border:1px solid #6EE7B7; border-radius:8px; padding:12px 18px; margin-bottom:20px; font-size:13px; color:#065F46; font-weight:600;">
                                ✅ New order email notification system is active. Owner email: <strong><?php echo esc_html($owner_email); ?></strong>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-settings')); ?>">
                            <?php wp_nonce_field('aavni_save_settings_nonce'); ?>
                            <input type="hidden" name="aavni_action" value="save_aavni_settings" />

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                                <!-- Owner Email -->
                                <div style="grid-column:1/-1;">
                                    <label for="aavni_owner_email"><strong>📬 Owner Notification Email:</strong></label><br>
                                    <input type="email" name="aavni_owner_email" id="aavni_owner_email"
                                           value="<?php echo esc_attr($owner_email); ?>"
                                           required style="width:100%; max-width:440px; margin-top:6px;" />
                                    <span style="font-size:12px; color:#666; display:block; margin-top:4px;">Every new customer order notification will be sent to this address.</span>
                                </div>

                                <!-- SMTP Section -->
                                <div style="grid-column:1/-1; margin-top:10px;">
                                    <div style="background:#FAF8F5; border:1px solid #D1C7BD; border-radius:8px; padding:18px 20px;">
                                        <h4 style="margin:0 0 6px; color:#1B1A18; font-size:14px;">⚙️ SMTP Email Configuration (Required for Local / XAMPP)</h4>
                                        <p style="font-size:12.5px; color:#6B655C; margin:0 0 16px; line-height:1.5;">
                                            PHP mail() does not work on XAMPP. Use Gmail SMTP with an App Password to reliably send emails.
                                            <strong>Never use your main Gmail password here</strong> — create a Google App Password instead:
                                            <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#A85A48;">myaccount.google.com/apppasswords →</a>
                                        </p>

                                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                            <div>
                                                <label for="aavni_smtp_host"><strong>SMTP Host:</strong></label><br>
                                                <input type="text" name="aavni_smtp_host" id="aavni_smtp_host"
                                                       value="<?php echo esc_attr($smtp_host ?: 'smtp.gmail.com'); ?>"
                                                       placeholder="smtp.gmail.com" style="width:100%; margin-top:4px;" />
                                            </div>
                                            <div>
                                                <label for="aavni_smtp_port"><strong>SMTP Port:</strong></label><br>
                                                <input type="number" name="aavni_smtp_port" id="aavni_smtp_port"
                                                       value="<?php echo esc_attr($smtp_port ?: 587); ?>"
                                                       placeholder="587" style="width:100%; margin-top:4px;" />
                                            </div>
                                            <div>
                                                <label for="aavni_smtp_username"><strong>SMTP Username (Gmail Address):</strong></label><br>
                                                <input type="email" name="aavni_smtp_username" id="aavni_smtp_username"
                                                       value="<?php echo esc_attr($smtp_user); ?>"
                                                       placeholder="yourstore@gmail.com" style="width:100%; margin-top:4px;" />
                                            </div>
                                            <div>
                                                <label for="aavni_smtp_password"><strong>SMTP App Password:</strong></label><br>
                                                <input type="password" name="aavni_smtp_password" id="aavni_smtp_password"
                                                       value="" autocomplete="new-password"
                                                       placeholder="<?php echo $smtp_saved ? '(saved — enter new to change)' : 'xxxx xxxx xxxx xxxx'; ?>"
                                                       style="width:100%; margin-top:4px; font-family:monospace; letter-spacing:0.1em;" />
                                                <span style="font-size:11.5px; color:#888;">Leave blank to keep existing password. <a href="https://myaccount.google.com/apppasswords" target="_blank" style="color:#A85A48;">Get App Password →</a></span>
                                            </div>
                                            <div>
                                                <label for="aavni_smtp_encryption"><strong>Encryption:</strong></label><br>
                                                <select name="aavni_smtp_encryption" id="aavni_smtp_encryption" style="width:100%; margin-top:4px;">
                                                    <option value="tls" <?php selected($smtp_enc, 'tls'); ?>>TLS (Port 587 — Recommended)</option>
                                                    <option value="ssl" <?php selected($smtp_enc, 'ssl'); ?>>SSL (Port 465)</option>
                                                    <option value="none" <?php selected($smtp_enc, 'none'); ?>>None (Not Recommended)</option>
                                                </select>
                                            </div>
                                            <div style="display:flex; align-items:flex-end;">
                                                <?php if ($smtp_saved) : ?>
                                                <span style="font-size:12px; color:#065F46; font-weight:700;">✅ SMTP configured: <?php echo esc_html($smtp_host); ?></span>
                                                <?php else : ?>
                                                <span style="font-size:12px; color:#7F1D1D; font-weight:700;">⚠️ SMTP not yet configured — emails may not send on local server.</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top:20px; display:flex; align-items:center; gap:12px;">
                                <button type="submit" class="aavni-btn aavni-btn-primary">Save Notification & SMTP Settings</button>
                                <span style="font-size:12.5px; color:#888;">Check WooCommerce Logs → WooCommerce → Status → Logs → aavni-owner-notifications</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Retry notification button
            $(document).on('click', '.aavni-retry-notif-btn', function(e) {
                e.preventDefault();
                var btn = $(this);
                var orderId = btn.data('order-id');
                var nonce   = btn.data('nonce');
                btn.text('Sending...').prop('disabled', true);
                $.post(ajaxurl, {
                    action:   'aavni_retry_owner_notification',
                    nonce:    nonce,
                    order_id: orderId
                }, function(res) {
                    if (res && res.success) {
                        btn.closest('li').html('<span style="color:#065F46; font-weight:700;">✅ Order #' + orderId + ': Notification sent!</span>');
                    } else {
                        btn.text('Failed — check SMTP').prop('disabled', false).css('border-color','#dc2626');
                        alert('Error: ' + ((res && res.data && res.data.message) ? res.data.message : 'Unknown error'));
                    }
                }).fail(function() {
                    btn.text('Server Error').prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }
}
