<?php
/**
 * Shiprocket Settings & Configuration Manager for AAVNI
 *
 * @package AAVNI_Shiprocket
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Shiprocket_Settings {

    /**
     * Get default settings array
     */
    public static function get_defaults() {
        return [
            'enabled'            => 'no',
            'email'              => '',
            'pickup_location'    => '',
            'default_weight'     => 0.45, // 450 grams for an Indian kurti/apparel
            'default_length'     => 30.0, // cm
            'default_breadth'    => 25.0, // cm
            'default_height'     => 4.0,  // cm
            'default_mode'       => 'Surface',
            'auto_assign_awb'    => 'yes',
            'sync_tracking_cron' => 'yes',
            'sync_interval'      => 'twicedaily',
            'debug_log'          => 'yes',
        ];
    }

    /**
     * Save settings from POST request
     */
    public static function save_settings($post_data) {
        if (!current_user_can('manage_woocommerce')) {
            return ['success' => false, 'message' => 'Permission denied.'];
        }

        $enabled = isset($post_data['shiprocket_enabled']) && $post_data['shiprocket_enabled'] === 'yes' ? 'yes' : 'no';
        $email = sanitize_email($post_data['shiprocket_email'] ?? '');
        $password = trim($post_data['shiprocket_password'] ?? '');
        $pickup = sanitize_text_field($post_data['shiprocket_pickup_location'] ?? '');
        $weight = max(0.05, floatval($post_data['shiprocket_default_weight'] ?? 0.45));
        $length = max(1.0, floatval($post_data['shiprocket_default_length'] ?? 30.0));
        $breadth = max(1.0, floatval($post_data['shiprocket_default_breadth'] ?? 25.0));
        $height = max(1.0, floatval($post_data['shiprocket_default_height'] ?? 4.0));
        $mode = in_array($post_data['shiprocket_default_mode'] ?? '', ['Air', 'Surface'], true) ? $post_data['shiprocket_default_mode'] : 'Surface';
        $auto_awb = isset($post_data['shiprocket_auto_assign_awb']) && $post_data['shiprocket_auto_assign_awb'] === 'yes' ? 'yes' : 'no';
        $sync_cron = isset($post_data['shiprocket_sync_tracking_cron']) && $post_data['shiprocket_sync_tracking_cron'] === 'yes' ? 'yes' : 'no';
        $debug_log = isset($post_data['shiprocket_debug_log']) && $post_data['shiprocket_debug_log'] === 'yes' ? 'yes' : 'no';

        update_option('aavni_shiprocket_enabled', $enabled);
        update_option('aavni_shiprocket_email', $email);
        if (!empty($password)) {
            update_option('aavni_shiprocket_password', $password);
            // Invalidate cached token so new credentials take effect
            AAVNI_Shiprocket_API::clear_token();
        }
        update_option('aavni_shiprocket_pickup_location', $pickup);
        update_option('aavni_shiprocket_default_weight', $weight);
        update_option('aavni_shiprocket_default_length', $length);
        update_option('aavni_shiprocket_default_breadth', $breadth);
        update_option('aavni_shiprocket_default_height', $height);
        update_option('aavni_shiprocket_default_mode', $mode);
        update_option('aavni_shiprocket_auto_assign_awb', $auto_awb);
        update_option('aavni_shiprocket_sync_tracking_cron', $sync_cron);
        update_option('aavni_shiprocket_debug_log', $debug_log);

        AAVNI_Shiprocket_API::log('Shiprocket settings updated by administrator.', 'info');

        return ['success' => true, 'message' => 'Shiprocket settings saved successfully!'];
    }

    /**
     * Render the Shiprocket Settings Card / Section
     */
    public static function render_settings_section() {
        $enabled = get_option('aavni_shiprocket_enabled', 'no');
        $email = get_option('aavni_shiprocket_email', '');
        $has_password = !empty(get_option('aavni_shiprocket_password', ''));
        $pickup = get_option('aavni_shiprocket_pickup_location', '');
        $weight = get_option('aavni_shiprocket_default_weight', 0.45);
        $length = get_option('aavni_shiprocket_default_length', 30.0);
        $breadth = get_option('aavni_shiprocket_default_breadth', 25.0);
        $height = get_option('aavni_shiprocket_default_height', 4.0);
        $mode = get_option('aavni_shiprocket_default_mode', 'Surface');
        $auto_awb = get_option('aavni_shiprocket_auto_assign_awb', 'yes');
        $sync_cron = get_option('aavni_shiprocket_sync_tracking_cron', 'yes');
        $debug_log = get_option('aavni_shiprocket_debug_log', 'yes');

        $status = get_option('aavni_shiprocket_status', 'unconfigured');
        $last_connected = get_option('aavni_shiprocket_last_connected', '');
        $last_error = get_option('aavni_shiprocket_last_error', '');
        $logs = get_option('aavni_shiprocket_recent_logs', []);
        ?>
        <div class="aavni-admin-card" style="margin-top:24px; border:2px solid #C4956E;">
            <div class="aavni-card-header" style="background:#FAF8F5; border-bottom:1px solid #EAE5DE; padding:18px 24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="font-size:26px;">🚀</span>
                    <div>
                        <h2 style="margin:0; font-family:'Playfair Display',serif; font-size:18px; color:#2C1810; font-weight:700;">
                            Shiprocket E-Commerce Logistics Integration
                        </h2>
                        <span style="font-size:12.5px; color:#6B655C;">Official REST API v1 • Real Couriers, AWB Generation, Shipping Labels &amp; Tracking</span>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <?php if ($status === 'connected') : ?>
                        <span class="aavni-badge" style="background:#DCFCE7; color:#166534; border:1px solid #86EFAC; font-weight:700;">
                            ✓ API Connected
                        </span>
                    <?php elseif ($status === 'error') : ?>
                        <span class="aavni-badge" style="background:#FEE2E2; color:#991B1B; border:1px solid #FCA5A5; font-weight:700;">
                            ⚠ Connection Error
                        </span>
                    <?php else : ?>
                        <span class="aavni-badge" style="background:#F3F4F6; color:#4B5563; border:1px solid #D1D5DB; font-weight:700;">
                            Not Connected
                        </span>
                    <?php endif; ?>

                    <button type="button" id="aavni_test_shiprocket_btn" class="aavni-btn aavni-btn-sm aavni-btn-gold">
                        ⚡ Test Connection
                    </button>
                </div>
            </div>

            <div class="aavni-card-body" style="padding:24px;">
                <?php if (!empty($last_error) && $status === 'error') : ?>
                    <div style="padding:12px 16px; background:#FEF2F2; border-left:4px solid #DC2626; border-radius:4px; margin-bottom:20px; font-size:13px; color:#991B1B;">
                        <strong>Last Error:</strong> <?php echo esc_html($last_error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($last_connected) : ?>
                    <div style="font-size:12px; color:#6B7280; margin-bottom:16px;">
                        ✓ Last successful API connection: <strong><?php echo esc_html(date_i18n('d M Y, h:i:s A', strtotime($last_connected))); ?></strong>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-settings')); ?>">
                    <?php wp_nonce_field('aavni_save_shiprocket_settings_nonce'); ?>
                    <input type="hidden" name="aavni_action" value="save_shiprocket_settings" />

                    <!-- Section 1: Activation & Credentials -->
                    <div style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; padding:18px; margin-bottom:20px;">
                        <h4 style="margin:0 0 14px 0; font-size:14px; color:#2C1810; font-weight:700;">
                            1. Authentication &amp; API Credentials
                        </h4>

                        <p style="margin-bottom:16px;">
                            <label style="font-size:13.5px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="shiprocket_enabled" value="yes" <?php checked($enabled, 'yes'); ?> />
                                Enable Shiprocket Automated Shipping &amp; Tracking
                            </label>
                            <span style="display:block; font-size:12px; color:#6B655C; margin-left:24px; margin-top:2px;">
                                When enabled, real Shiprocket courier selection, AWB assignment, and label printing will be active in the Orders Hub.
                            </span>
                        </p>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div>
                                <label for="shiprocket_email"><strong>Shiprocket Account Email *:</strong></label>
                                <input type="email" name="shiprocket_email" id="shiprocket_email" value="<?php echo esc_attr($email); ?>" required placeholder="e.g. logistics@aavniclothing.com" style="width:100%; margin-top:6px;" />
                                <span style="font-size:11.5px; color:#888;">Your registered Shiprocket account email.</span>
                            </div>
                            <div>
                                <label for="shiprocket_password"><strong>Shiprocket Account Password *:</strong></label>
                                <input type="password" name="shiprocket_password" id="shiprocket_password" placeholder="<?php echo $has_password ? '•••••••••••• (Leave blank to keep unchanged)' : 'Enter your Shiprocket password'; ?>" style="width:100%; margin-top:6px;" />
                                <span style="font-size:11.5px; color:#888;">
                                    <?php echo $has_password ? '✓ Password is saved securely. Re-enter only to change.' : 'Required for JWT token authentication (POST /v1/external/auth/login).'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Warehouse Pickup Location -->
                    <div style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; padding:18px; margin-bottom:20px;">
                        <h4 style="margin:0 0 14px 0; font-size:14px; color:#2C1810; font-weight:700;">
                            2. Warehouse Pickup Location
                        </h4>
                        <p style="font-size:12.5px; color:#6B655C; margin-top:0;">
                            The pickup location name exactly as registered inside your Shiprocket Dashboard (Settings &rarr; Company &rarr; Pickup Locations).
                        </p>

                        <div style="display:grid; grid-template-columns:2fr 1fr; gap:16px; align-items:start;">
                            <div>
                                <label for="shiprocket_pickup_location"><strong>Shiprocket Pickup Location Nickname *:</strong></label>
                                <input type="text" name="shiprocket_pickup_location" id="shiprocket_pickup_location" value="<?php echo esc_attr($pickup); ?>" placeholder="e.g. Primary / Jaipur Central / Aavni Warehouse" style="width:100%; margin-top:6px;" />
                                <span style="font-size:11.5px; color:#888;">Must match the exact pickup nickname registered in Shiprocket.</span>
                            </div>
                            <div>
                                <label>&nbsp;</label>
                                <button type="button" id="aavni_fetch_pickups_btn" class="aavni-btn aavni-btn-secondary" style="width:100%; margin-top:6px; font-size:12.5px;">
                                    📍 Fetch from Shiprocket
                                </button>
                                <span style="font-size:11px; color:#888; display:block; margin-top:3px;">Auto-fetch verified addresses</span>
                            </div>
                        </div>
                        <div id="aavni_pickup_fetch_result" style="margin-top:10px; font-size:12px; display:none;"></div>
                    </div>

                    <!-- Section 3: Default Apparel Package Dimensions & Weight -->
                    <div style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; padding:18px; margin-bottom:20px;">
                        <h4 style="margin:0 0 14px 0; font-size:14px; color:#2C1810; font-weight:700;">
                            3. Default Clothing Package Specifications
                        </h4>
                        <p style="font-size:12.5px; color:#6B655C; margin-top:0;">
                            Used as a fallback if individual WooCommerce product weights or dimensions are missing. You can always edit these per-shipment before booking.
                        </p>

                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:12px;">
                            <div>
                                <label for="shiprocket_default_weight"><strong>Dead Weight (kg):</strong></label>
                                <input type="number" step="0.01" min="0.05" name="shiprocket_default_weight" id="shiprocket_default_weight" value="<?php echo esc_attr($weight); ?>" required style="width:100%; margin-top:6px;" />
                                <span style="font-size:11px; color:#888;">e.g. 0.45 kg (450g)</span>
                            </div>
                            <div>
                                <label for="shiprocket_default_length"><strong>Length (cm):</strong></label>
                                <input type="number" step="0.5" min="1" name="shiprocket_default_length" id="shiprocket_default_length" value="<?php echo esc_attr($length); ?>" required style="width:100%; margin-top:6px;" />
                                <span style="font-size:11px; color:#888;">e.g. 30 cm</span>
                            </div>
                            <div>
                                <label for="shiprocket_default_breadth"><strong>Breadth (cm):</strong></label>
                                <input type="number" step="0.5" min="1" name="shiprocket_default_breadth" id="shiprocket_default_breadth" value="<?php echo esc_attr($breadth); ?>" required style="width:100%; margin-top:6px;" />
                                <span style="font-size:11px; color:#888;">e.g. 25 cm</span>
                            </div>
                            <div>
                                <label for="shiprocket_default_height"><strong>Height (cm):</strong></label>
                                <input type="number" step="0.5" min="0.5" name="shiprocket_default_height" id="shiprocket_default_height" value="<?php echo esc_attr($height); ?>" required style="width:100%; margin-top:6px;" />
                                <span style="font-size:11px; color:#888;">e.g. 4 cm</span>
                            </div>
                            <div>
                                <label for="shiprocket_default_mode"><strong>Default Transit:</strong></label>
                                <select name="shiprocket_default_mode" id="shiprocket_default_mode" style="width:100%; margin-top:6px;">
                                    <option value="Surface" <?php selected($mode, 'Surface'); ?>>Surface (Economical)</option>
                                    <option value="Air" <?php selected($mode, 'Air'); ?>>Air (Express)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Automation & Sync Settings -->
                    <div style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:8px; padding:18px; margin-bottom:20px;">
                        <h4 style="margin:0 0 14px 0; font-size:14px; color:#2C1810; font-weight:700;">
                            4. Fulfilment Pipeline Automation &amp; Tracking Sync
                        </h4>

                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <label style="font-size:13px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="shiprocket_auto_assign_awb" value="yes" <?php checked($auto_awb, 'yes'); ?> />
                                Automatically request real AWB assignment when order shipment is created
                            </label>

                            <label style="font-size:13px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="shiprocket_sync_tracking_cron" value="yes" <?php checked($sync_cron, 'yes'); ?> />
                                Enable automatic background tracking synchronization (WordPress Cron twice-daily)
                            </label>

                            <label style="font-size:13px; font-weight:600; cursor:pointer;">
                                <input type="checkbox" name="shiprocket_debug_log" value="yes" <?php checked($debug_log, 'yes'); ?> />
                                Enable diagnostic logging (stores sanitized API error messages for troubleshooting)
                            </label>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end;">
                        <button type="submit" class="aavni-btn aavni-btn-primary">
                            💾 Save Shiprocket Configuration
                        </button>
                    </div>
                </form>

                <!-- Section 5: Real-time Diagnostics & Logs -->
                <?php if (!empty($logs)) : ?>
                    <div style="margin-top:24px; border-top:1px solid #EAE5DE; padding-top:18px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <h4 style="margin:0; font-size:13px; color:#2C1810;">Recent Integration Logs (Sanitized)</h4>
                            <button type="button" id="aavni_clear_shiprocket_logs_btn" style="background:none; border:none; color:#A85A48; font-size:11px; cursor:pointer; text-decoration:underline;">
                                Clear Logs
                            </button>
                        </div>
                        <div style="background:#1E1E1E; color:#D4D4D4; font-family:Consolas, monospace; font-size:11.5px; padding:12px; border-radius:6px; max-height:180px; overflow-y:auto; line-height:1.5;">
                            <?php foreach ($logs as $log) :
                                $color = $log['level'] === 'ERROR' ? '#F87171' : ($log['level'] === 'WARNING' ? '#FBBF24' : '#60A5FA');
                            ?>
                                <div>
                                    <span style="color:#888;">[<?php echo esc_html($log['time']); ?>]</span>
                                    <span style="color:<?php echo $color; ?>; font-weight:bold;">[<?php echo esc_html($log['level']); ?>]</span>
                                    <span><?php echo esc_html($log['message']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

