<?php
/**
 * Shiprocket Admin AJAX Handlers & UI Integration
 *
 * Implements secure nonced endpoints for:
 * - Test connection
 * - Fetch pickup locations
 * - Check courier serviceability
 * - Create shipment
 * - Assign AWB
 * - Get / Print Label
 * - Book Pickup
 * - Generate Manifest
 * - Sync Tracking
 * - Cancel Shipment
 * - Bulk Shipment Operations
 * - Clear Logs
 *
 * @package AAVNI_Shiprocket
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Shiprocket_Admin_UI {

    /**
     * Register AJAX hooks
     */
    public static function init() {
        // Test connection
        add_action('wp_ajax_aavni_sr_test_connection', [__CLASS__, 'ajax_test_connection']);
        // Fetch pickups
        add_action('wp_ajax_aavni_sr_fetch_pickups', [__CLASS__, 'ajax_fetch_pickups']);
        // Serviceability
        add_action('wp_ajax_aavni_sr_check_serviceability', [__CLASS__, 'ajax_check_serviceability']);
        // Create Shipment
        add_action('wp_ajax_aavni_sr_create_shipment', [__CLASS__, 'ajax_create_shipment']);
        // Assign AWB
        add_action('wp_ajax_aavni_sr_assign_awb', [__CLASS__, 'ajax_assign_awb']);
        // Get Label
        add_action('wp_ajax_aavni_sr_get_label', [__CLASS__, 'ajax_get_label']);
        // Request Pickup
        add_action('wp_ajax_aavni_sr_request_pickup', [__CLASS__, 'ajax_request_pickup']);
        // Generate Manifest
        add_action('wp_ajax_aavni_sr_generate_manifest', [__CLASS__, 'ajax_generate_manifest']);
        // Sync Tracking
        add_action('wp_ajax_aavni_sr_sync_tracking', [__CLASS__, 'ajax_sync_tracking']);
        // Cancel Shipment
        add_action('wp_ajax_aavni_sr_cancel_shipment', [__CLASS__, 'ajax_cancel_shipment']);
        // Bulk Process
        add_action('wp_ajax_aavni_sr_bulk_action', [__CLASS__, 'ajax_bulk_action']);
        // Clear Logs
        add_action('wp_ajax_aavni_sr_clear_logs', [__CLASS__, 'ajax_clear_logs']);

        // Form post handler for settings save
        add_action('admin_init', [__CLASS__, 'handle_form_posts']);
        add_action('init', [__CLASS__, 'handle_form_posts']);

        // Enqueue Assets in Admin and Standalone Portal
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    /**
     * Handle non-AJAX POSTs (e.g. settings save)
     */
    public static function handle_form_posts() {
        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'save_shiprocket_settings') {
            check_admin_referer('aavni_save_shiprocket_settings_nonce');
            $res = AAVNI_Shiprocket_Settings::save_settings($_POST);

            $redirect_url = admin_url('admin.php?page=aavni-settings&sr_updated=1');
            if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin-portal') !== false) {
                $redirect_url = home_url('/admin-portal/?tab=settings&sr_updated=1');
            }

            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Enqueue Admin JS & CSS
     */
    public static function enqueue_assets() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_portal = (function_exists('is_page') && is_page('admin-portal')) || (isset($_GET['page']) && strpos($_GET['page'], 'aavni-') === 0);

        if (!$is_portal && (!$screen || strpos($screen->id, 'aavni') === false)) {
            return;
        }

        wp_enqueue_style(
            'aavni-shiprocket-admin-css',
            AAVNI_SHIPROCKET_PLUGIN_URL . 'assets/css/aavni-shiprocket.css',
            [],
            AAVNI_SHIPROCKET_VERSION
        );

        wp_enqueue_script(
            'aavni-shiprocket-admin-js',
            AAVNI_SHIPROCKET_PLUGIN_URL . 'assets/js/aavni-shiprocket.js',
            ['jquery'],
            AAVNI_SHIPROCKET_VERSION,
            true
        );

        wp_localize_script('aavni-shiprocket-admin-js', 'aavniShiprocketParams', [
            'ajax_url'   => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('aavni_shiprocket_nonce'),
            'is_enabled' => AAVNI_Shiprocket_API::is_enabled(),
        ]);
    }

    /**
     * Permission & Nonce check helper
     */
    private static function verify_access() {
        check_ajax_referer('aavni_shiprocket_nonce', 'nonce');
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied. You do not have permission to manage shipments.']);
        }
    }

    /**
     * 1. AJAX: Test API Connection
     */
    public static function ajax_test_connection() {
        self::verify_access();

        AAVNI_Shiprocket_API::clear_token();
        $res = AAVNI_Shiprocket_API::get_token(true);

        if ($res['success']) {
            wp_send_json_success([
                'message'        => '✓ Connected successfully to official Shiprocket API v1!',
                'last_connected' => current_time('mysql'),
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Connection failed: ' . $res['message'],
            ]);
        }
    }

    /**
     * 2. AJAX: Fetch Pickup Locations
     */
    public static function ajax_fetch_pickups() {
        self::verify_access();

        $res = AAVNI_Shiprocket_API::get_pickup_locations();
        if (!$res['success'] || empty($res['data']['data']['shipping_address'])) {
            $msg = !empty($res['message']) ? $res['message'] : 'Could not fetch pickup locations from Shiprocket.';
            wp_send_json_error(['message' => $msg]);
        }

        $addresses = $res['data']['data']['shipping_address'];
        $clean_list = [];
        foreach ($addresses as $addr) {
            $clean_list[] = [
                'pickup_location' => $addr['pickup_location'] ?? '',
                'address'         => $addr['address'] ?? '',
                'city'            => $addr['city'] ?? '',
                'state'           => $addr['state'] ?? '',
                'pin_code'        => $addr['pin_code'] ?? '',
                'phone'           => $addr['phone'] ?? '',
                'name'            => $addr['name'] ?? '',
            ];
        }

        wp_send_json_success([
            'count'     => count($clean_list),
            'locations' => $clean_list,
        ]);
    }

    /**
     * 3. AJAX: Check Courier Serviceability
     */
    public static function ajax_check_serviceability() {
        self::verify_access();

        $order_id = absint($_POST['order_id'] ?? 0);
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $weight = isset($_POST['weight']) ? max(0.05, floatval($_POST['weight'])) : null;
        $res = AAVNI_Shiprocket_Shipment::check_order_serviceability($order, $weight);

        if (!$res['success'] || empty($res['data']['data']['available_courier_companies'])) {
            $msg = !empty($res['message']) ? $res['message'] : 'No couriers serviceable for this pincode.';
            wp_send_json_error(['message' => $msg]);
        }

        $couriers = $res['data']['data']['available_courier_companies'];
        $formatted = [];
        foreach ($couriers as $c) {
            $formatted[] = [
                'courier_company_id' => $c['courier_company_id'],
                'courier_name'       => $c['courier_name'],
                'rate'               => $c['rate'] ?? 0,
                'etd'                => $c['etd'] ?? 'N/A',
                'rating'             => $c['rating'] ?? 0,
                'cod'                => !empty($c['cod']),
            ];
        }

        // Sort by lowest shipping charge
        usort($formatted, function($a, $b) {
            return ($a['rate'] <=> $b['rate']);
        });

        wp_send_json_success([
            'couriers' => $formatted,
            'count'    => count($formatted),
        ]);
    }

    /**
     * 4. AJAX: Create Shipment
     */
    public static function ajax_create_shipment() {
        self::verify_access();

        $order_id = absint($_POST['order_id'] ?? 0);
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $weight = max(0.05, floatval($_POST['weight'] ?? 0.45));
        $length = max(1.0, floatval($_POST['length'] ?? 30.0));
        $breadth = max(1.0, floatval($_POST['breadth'] ?? 25.0));
        $height = max(1.0, floatval($_POST['height'] ?? 4.0));
        $courier_id = !empty($_POST['courier_id']) ? absint($_POST['courier_id']) : null;

        $specs = [
            'weight'  => $weight,
            'length'  => $length,
            'breadth' => $breadth,
            'height'  => $height,
        ];

        $res = AAVNI_Shiprocket_Shipment::create_shipment_for_order($order, $specs, $courier_id);
        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    /**
     * 5. AJAX: Assign AWB
     */
    public static function ajax_assign_awb() {
        self::verify_access();

        $order_id = absint($_POST['order_id'] ?? 0);
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $courier_id = !empty($_POST['courier_id']) ? absint($_POST['courier_id']) : null;
        $res = AAVNI_Shiprocket_Shipment::assign_awb_for_order($order, $courier_id);

        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    /**
     * 6. AJAX: Get Label
     */
    public static function ajax_get_label() {
        self::verify_access();

        $order_id = absint($_POST['order_id'] ?? 0);
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $force = !empty($_POST['force']);
        $res = AAVNI_Shiprocket_Shipment::get_label_for_order($order, $force);

        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    /**
     * 7. AJAX: Request Warehouse Pickup
     */
    public static function ajax_request_pickup() {
        self::verify_access();

        $order_id = absint($_POST['order_id'] ?? 0);
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $res = AAVNI_Shiprocket_Shipment::request_pickup_for_order($order);
        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    /**
     * 8. AJAX: Generate Manifest
     */
    public static function ajax_generate_manifest() {
        self::verify_access();

        $order_id = absint($_POST['order_id'] ?? 0);
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $res = AAVNI_Shiprocket_Shipment::generate_manifest_for_order($order);
        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    /**
     * 9. AJAX: Sync Tracking
     */
    public static function ajax_sync_tracking() {
        self::verify_access();

        $order_id = absint($_POST['order_id'] ?? 0);
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $res = AAVNI_Shiprocket_Shipment::sync_tracking_for_order($order);
        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    /**
     * 10. AJAX: Cancel Shipment
     */
    public static function ajax_cancel_shipment() {
        self::verify_access();

        $order_id = absint($_POST['order_id'] ?? 0);
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $res = AAVNI_Shiprocket_Shipment::cancel_shipment_for_order($order);
        if ($res['success']) {
            wp_send_json_success($res);
        } else {
            wp_send_json_error($res);
        }
    }

    /**
     * 11. AJAX: Bulk Action Processor
     */
    public static function ajax_bulk_action() {
        self::verify_access();

        $action_type = sanitize_text_field($_POST['bulk_action_type'] ?? '');
        $order_ids = isset($_POST['order_ids']) ? array_filter(array_map('absint', (array) $_POST['order_ids'])) : [];

        if (empty($order_ids)) {
            wp_send_json_error(['message' => 'No orders selected for bulk operation.']);
        }

        $results = [];
        $success_count = 0;
        $fail_count = 0;

        foreach ($order_ids as $oid) {
            $order = wc_get_order($oid);
            if (!$order) {
                $results[] = ['order_id' => $oid, 'success' => false, 'message' => 'Order not found.'];
                $fail_count++;
                continue;
            }

            switch ($action_type) {
                case 'create_shipment':
                    $r = AAVNI_Shiprocket_Shipment::create_shipment_for_order($order);
                    break;
                case 'assign_awb':
                    $r = AAVNI_Shiprocket_Shipment::assign_awb_for_order($order);
                    break;
                case 'get_label':
                    $r = AAVNI_Shiprocket_Shipment::get_label_for_order($order);
                    break;
                case 'request_pickup':
                    $r = AAVNI_Shiprocket_Shipment::request_pickup_for_order($order);
                    break;
                case 'sync_tracking':
                    $r = AAVNI_Shiprocket_Shipment::sync_tracking_for_order($order);
                    break;
                default:
                    $r = ['success' => false, 'message' => 'Unknown bulk action.'];
                    break;
            }

            if ($r['success']) {
                $success_count++;
            } else {
                $fail_count++;
            }

            $results[] = [
                'order_id' => $oid,
                'number'   => method_exists($order, 'get_order_number') ? $order->get_order_number() : $oid,
                'success'  => $r['success'],
                'awb'      => $order->get_meta(AAVNI_Shiprocket_Shipment::META_AWB_CODE) ?: '',
                'courier'  => $order->get_meta(AAVNI_Shiprocket_Shipment::META_COURIER_NAME) ?: '',
                'message'  => $r['message'] ?? '',
            ];
        }

        wp_send_json_success([
            'action'        => $action_type,
            'total'         => count($order_ids),
            'success_count' => $success_count,
            'fail_count'    => $fail_count,
            'results'       => $results,
            'message'       => "Bulk {$action_type} completed: {$success_count} succeeded, {$fail_count} failed.",
        ]);
    }

    /**
     * 12. AJAX: Clear Logs
     */
    public static function ajax_clear_logs() {
        self::verify_access();
        delete_option('aavni_shiprocket_recent_logs');
        wp_send_json_success(['message' => 'Logs cleared successfully.']);
    }

    /**
     * Helper to render the Shiprocket Action Box inside Order Breakdown Details Modal
     */
    public static function render_order_details_shiprocket_box() {
        ?>
        <div id="aavni_sr_order_panel" style="margin-top:20px; padding:18px; background:#FAF8F5; border:1.5px solid #C4956E; border-radius:8px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #EAE5DE; padding-bottom:8px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:18px;">🚀</span>
                    <strong style="font-size:13.5px; color:#2C1810;">Shiprocket Live Shipping &amp; Logistics</strong>
                    <span id="sr_panel_status_badge" class="aavni-badge" style="margin-left:6px; font-size:10.5px;"></span>
                </div>
                <div style="font-size:11px; color:#888;">Real Courier API Integration</div>
            </div>

            <!-- Pre-Shipment Configuration Controls -->
            <div id="sr_panel_pre_booking_wrap" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:10px; margin-bottom:12px;">
                <div>
                    <label style="font-size:11px; font-weight:700;">Weight (kg):</label>
                    <input type="number" step="0.01" min="0.05" id="sr_pkg_weight" style="width:100%; font-size:12px; padding:4px 8px; margin-top:2px;" />
                </div>
                <div>
                    <label style="font-size:11px; font-weight:700;">Length (cm):</label>
                    <input type="number" step="0.5" id="sr_pkg_l" style="width:100%; font-size:12px; padding:4px 8px; margin-top:2px;" />
                </div>
                <div>
                    <label style="font-size:11px; font-weight:700;">Breadth (cm):</label>
                    <input type="number" step="0.5" id="sr_pkg_b" style="width:100%; font-size:12px; padding:4px 8px; margin-top:2px;" />
                </div>
                <div>
                    <label style="font-size:11px; font-weight:700;">Height (cm):</label>
                    <input type="number" step="0.5" id="sr_pkg_h" style="width:100%; font-size:12px; padding:4px 8px; margin-top:2px;" />
                </div>
            </div>

            <!-- Serviceability Check & Courier Picker -->
            <div style="margin-bottom:14px;">
                <div style="display:flex; gap:8px; align-items:center;">
                    <button type="button" id="sr_btn_check_serviceability" class="aavni-btn aavni-btn-sm aavni-btn-secondary" style="font-size:11.5px;">
                        🔍 Check Courier Rates &amp; Serviceability
                    </button>
                    <select id="sr_courier_select" style="flex:1; font-size:12px; padding:5px 8px; display:none;">
                        <option value="">Select Serviceable Courier...</option>
                    </select>
                </div>
                <div id="sr_serviceability_msg" style="font-size:11.5px; margin-top:6px; display:none;"></div>
            </div>

            <!-- Shipment Identifiers Card (When Shipment Exists) -->
            <div id="sr_panel_identifiers" style="display:none; background:#fff; border:1px solid #EAE5DE; border-radius:6px; padding:12px; margin-bottom:14px; font-size:12px; line-height:1.7;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <div><strong>Shipment ID:</strong> <code id="sr_info_shipment_id"></code></div>
                    <div><strong>Shiprocket Order ID:</strong> <span id="sr_info_order_id"></span></div>
                    <div><strong>Courier Partner:</strong> <span id="sr_info_courier"></span></div>
                    <div><strong>Real AWB Number:</strong> <code id="sr_info_awb"></code></div>
                </div>
            </div>

            <!-- Action Buttons Strip -->
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <!-- 1. Create Shipment Button -->
                <button type="button" id="sr_btn_create_shipment" class="aavni-btn aavni-btn-sm aavni-btn-primary" style="font-size:12px;">
                    🚀 Create Shipment
                </button>

                <!-- 2. Assign AWB Button -->
                <button type="button" id="sr_btn_assign_awb" class="aavni-btn aavni-btn-sm aavni-btn-secondary" style="font-size:12px; display:none;">
                    🏷️ Assign AWB
                </button>

                <!-- 3. View / Print Label -->
                <a id="sr_btn_print_label" href="" target="_blank" class="aavni-btn aavni-btn-sm aavni-btn-secondary" style="font-size:12px; display:none;">
                    📄 Print Shipping Label
                </a>

                <!-- 4. Book Pickup -->
                <button type="button" id="sr_btn_book_pickup" class="aavni-btn aavni-btn-sm aavni-btn-secondary" style="font-size:12px; display:none;">
                    📦 Book Warehouse Pickup
                </button>

                <!-- 5. Print Manifest -->
                <a id="sr_btn_print_manifest" href="" target="_blank" class="aavni-btn aavni-btn-sm aavni-btn-secondary" style="font-size:12px; display:none;">
                    📋 Print Manifest
                </a>

                <!-- 6. Sync Tracking -->
                <button type="button" id="sr_btn_sync_tracking" class="aavni-btn aavni-btn-sm aavni-btn-gold" style="font-size:12px; display:none;">
                    🔄 Refresh Tracking
                </button>

                <!-- 7. Cancel Shipment -->
                <button type="button" id="sr_btn_cancel_shipment" class="aavni-btn aavni-btn-sm aavni-btn-danger" style="font-size:12px; display:none;">
                    ✕ Cancel Shipment
                </button>
            </div>

            <div id="sr_panel_action_msg" style="margin-top:10px; font-size:12px; display:none;"></div>
        </div>
        <?php
    }
}

