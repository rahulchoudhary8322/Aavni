<?php
/**
 * Shiprocket API Client for AAVNI
 *
 * Implements official Shiprocket REST API v1 external endpoints:
 * - Authentication (POST /v1/external/auth/login) with transient caching
 * - Pickup Locations (GET /v1/external/settings/company/pickup)
 * - Courier Serviceability (GET /v1/external/courier/serviceability/)
 * - Custom Order/Shipment Creation (POST /v1/external/orders/create/adhoc)
 * - AWB Generation (POST /v1/external/courier/assign/awb)
 * - Label Generation (POST /v1/external/courier/generate/label)
 * - Invoice Generation (POST /v1/external/orders/print/invoice)
 * - Pickup Request (POST /v1/external/courier/generate/pickup)
 * - Manifest Generation (POST /v1/external/manifests/generate)
 * - Manifest Print (POST /v1/external/manifests/print)
 * - Tracking by AWB (GET /v1/external/courier/track/awb/{awb})
 * - Tracking by Shipment ID (GET /v1/external/courier/track/shipment/{id})
 * - Order Cancellation (POST /v1/external/orders/cancel)
 *
 * @package AAVNI_Shiprocket
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Shiprocket_API {

    const API_BASE_URL = 'https://apiv2.shiprocket.in/v1/external';
    const TOKEN_TRANSIENT = 'aavni_shiprocket_jwt_token';
    const TOKEN_EXPIRY = 86400; // 24 hours (Shiprocket tokens expire in 10 days, refresh daily for safety)

    /**
     * Get API credentials from settings or constants
     */
    public static function get_credentials() {
        $email = defined('AAVNI_SHIPROCKET_EMAIL') ? AAVNI_SHIPROCKET_EMAIL : get_option('aavni_shiprocket_email', '');
        $password = defined('AAVNI_SHIPROCKET_PASSWORD') ? AAVNI_SHIPROCKET_PASSWORD : get_option('aavni_shiprocket_password', '');

        return [
            'email'    => trim($email),
            'password' => trim($password),
        ];
    }

    /**
     * Check if Shiprocket integration is configured
     */
    public static function is_configured() {
        $creds = self::get_credentials();
        return !empty($creds['email']) && !empty($creds['password']);
    }

    /**
     * Check if Shiprocket integration is enabled
     */
    public static function is_enabled() {
        return get_option('aavni_shiprocket_enabled', 'no') === 'yes' && self::is_configured();
    }

    /**
     * Authenticate and return cached JWT token
     *
     * @param bool $force_refresh Force re-login
     * @return array ['success' => bool, 'token' => string, 'message' => string]
     */
    public static function get_token($force_refresh = false) {
        if (!$force_refresh) {
            $cached = get_transient(self::TOKEN_TRANSIENT);
            if (!empty($cached)) {
                return ['success' => true, 'token' => $cached, 'message' => 'Token retrieved from cache.'];
            }
        }

        $creds = self::get_credentials();
        if (empty($creds['email']) || empty($creds['password'])) {
            return [
                'success' => false,
                'token'   => '',
                'message' => __('Shiprocket API email or password is not configured in Settings.', 'aavni')
            ];
        }

        $response = wp_remote_post(self::API_BASE_URL . '/auth/login', [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'    => wp_json_encode([
                'email'    => $creds['email'],
                'password' => $creds['password'],
            ]),
        ]);

        if (is_wp_error($response)) {
            $err = $response->get_error_message();
            self::log('Login error: ' . $err, 'error');
            return ['success' => false, 'token' => '', 'message' => 'Connection failed: ' . $err];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && !empty($body['token'])) {
            $token = $body['token'];
            set_transient(self::TOKEN_TRANSIENT, $token, self::TOKEN_EXPIRY);
            update_option('aavni_shiprocket_last_connected', current_time('mysql'));
            update_option('aavni_shiprocket_status', 'connected');
            update_option('aavni_shiprocket_last_error', '');

            self::log('Successfully authenticated with Shiprocket API.', 'info');
            return ['success' => true, 'token' => $token, 'message' => 'Connected successfully!'];
        }

        $msg = !empty($body['message']) ? $body['message'] : 'Invalid credentials or HTTP error ' . $code;
        update_option('aavni_shiprocket_status', 'error');
        update_option('aavni_shiprocket_last_error', $msg);
        self::log('Login failed (' . $code . '): ' . $msg, 'error');

        return ['success' => false, 'token' => '', 'message' => $msg];
    }

    /**
     * Clear cached token
     */
    public static function clear_token() {
        delete_transient(self::TOKEN_TRANSIENT);
    }

    /**
     * Generic API Request Handler with automatic token retry
     */
    public static function request($endpoint, $method = 'GET', $data = [], $retry_on_auth_fail = true) {
        $token_res = self::get_token();
        if (!$token_res['success']) {
            return $token_res;
        }

        $url = self::API_BASE_URL . '/' . ltrim($endpoint, '/');
        $args = [
            'method'  => strtoupper($method),
            'timeout' => 30,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer ' . $token_res['token'],
            ],
        ];

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
            $args['body'] = wp_json_encode($data);
        } elseif (!empty($data) && strtoupper($method) === 'GET') {
            $url = add_query_arg($data, $url);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            $err = $response->get_error_message();
            self::log("API [{$method}] {$endpoint} Error: {$err}", 'error');
            return ['success' => false, 'code' => 0, 'data' => null, 'message' => $err];
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);
        $body = json_decode($raw_body, true);

        // Handle expired token (401)
        if ($code === 401 && $retry_on_auth_fail) {
            self::log("Received 401 on {$endpoint}. Refreshing token and retrying...", 'warning');
            self::clear_token();
            $refreshed = self::get_token(true);
            if ($refreshed['success']) {
                return self::request($endpoint, $method, $data, false);
            }
        }

        $is_success = ($code >= 200 && $code < 300);
        $message = '';
        if (!$is_success) {
            $message = !empty($body['message']) ? $body['message'] : "HTTP error {$code}";
            if (empty($message) && !empty($body['errors'])) {
                $message = is_array($body['errors']) ? implode(', ', array_map(function($v) {
                    return is_array($v) ? implode(' ', $v) : $v;
                }, $body['errors'])) : (string)$body['errors'];
            }
            self::log("API [{$method}] {$endpoint} failed ({$code}): {$message}", 'error');
        }

        return [
            'success' => $is_success,
            'code'    => $code,
            'data'    => $body,
            'message' => $message,
            'raw'     => $raw_body,
        ];
    }

    /**
     * 1. Get Pickup Locations from Shiprocket
     */
    public static function get_pickup_locations() {
        return self::request('/settings/company/pickup', 'GET');
    }

    /**
     * 2. Check Courier Serviceability
     */
    public static function check_serviceability($params) {
        $query = [
            'pickup_postcode'   => sanitize_text_field($params['pickup_postcode']),
            'delivery_postcode' => sanitize_text_field($params['delivery_postcode']),
            'weight'            => floatval($params['weight']),
            'cod'               => !empty($params['cod']) ? 1 : 0,
        ];

        if (!empty($params['order_id'])) {
            $query['order_id'] = absint($params['order_id']);
        }

        return self::request('/courier/serviceability/', 'GET', $query);
    }

    /**
     * 3. Create Adhoc Custom Order / Shipment
     */
    public static function create_order($payload) {
        return self::request('/orders/create/adhoc', 'POST', $payload);
    }

    /**
     * 4. Assign AWB to Shipment
     */
    public static function assign_awb($shipment_id, $courier_id = null) {
        $body = ['shipment_id' => absint($shipment_id)];
        if (!empty($courier_id)) {
            $body['courier_id'] = absint($courier_id);
        }

        return self::request('/courier/assign/awb', 'POST', $body);
    }

    /**
     * 5. Generate Shipping Label
     */
    public static function generate_label($shipment_id) {
        return self::request('/courier/generate/label', 'POST', [
            'shipment_id' => [absint($shipment_id)],
        ]);
    }

    /**
     * 6. Generate Order Tax Invoice
     */
    public static function print_invoice($order_ids) {
        $ids = is_array($order_ids) ? array_map('absint', $order_ids) : [absint($order_ids)];
        return self::request('/orders/print/invoice', 'POST', [
            'ids' => $ids,
        ]);
    }

    /**
     * 7. Request Pickup from Warehouse
     */
    public static function request_pickup($shipment_id) {
        return self::request('/courier/generate/pickup', 'POST', [
            'shipment_id' => [absint($shipment_id)],
        ]);
    }

    /**
     * 8. Generate Manifest
     */
    public static function generate_manifest($shipment_id) {
        return self::request('/manifests/generate', 'POST', [
            'shipment_id' => [absint($shipment_id)],
        ]);
    }

    /**
     * 9. Print Manifest
     */
    public static function print_manifest($order_ids) {
        $ids = is_array($order_ids) ? array_map('absint', $order_ids) : [absint($order_ids)];
        return self::request('/manifests/print', 'POST', [
            'order_ids' => $ids,
        ]);
    }

    /**
     * 10. Track by AWB Number
     */
    public static function track_awb($awb) {
        $clean_awb = sanitize_text_field($awb);
        return self::request('/courier/track/awb/' . rawurlencode($clean_awb), 'GET');
    }

    /**
     * 11. Track by Shipment ID
     */
    public static function track_shipment($shipment_id) {
        return self::request('/courier/track/shipment/' . absint($shipment_id), 'GET');
    }

    /**
     * 12. Cancel Order on Shiprocket
     */
    public static function cancel_order($order_ids) {
        $ids = is_array($order_ids) ? array_map('absint', $order_ids) : [absint($order_ids)];
        return self::request('/orders/cancel', 'POST', [
            'ids' => $ids,
        ]);
    }

    /**
     * Safe Internal Diagnostic Logger with Secrets Redacted
     */
    public static function log($message, $level = 'info') {
        $logging_enabled = get_option('aavni_shiprocket_debug_log', 'yes') === 'yes';
        if (!$logging_enabled) {
            return;
        }

        // Redact any credentials or tokens
        $redacted = preg_replace('/"password"\s*:\s*"[^"]*"/', '"password":"[REDACTED]"', $message);
        $redacted = preg_replace('/Bearer\s+[A-Za-z0-9\-_\.]+/', 'Bearer [TOKEN_REDACTED]', $redacted);

        $logs = get_option('aavni_shiprocket_recent_logs', []);
        if (!is_array($logs)) {
            $logs = [];
        }

        $entry = [
            'time'    => current_time('mysql'),
            'level'   => strtoupper($level),
            'message' => $redacted,
        ];

        array_unshift($logs, $entry);
        if (count($logs) > 60) {
            $logs = array_slice($logs, 0, 60);
        }

        update_option('aavni_shiprocket_recent_logs', $logs);

        // Also write to WooCommerce logger if available
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $context = ['source' => 'aavni-shiprocket'];
            if ($level === 'error') {
                $logger->error($redacted, $context);
            } elseif ($level === 'warning') {
                $logger->warning($redacted, $context);
            } else {
                $logger->info($redacted, $context);
            }
        }
    }
}

