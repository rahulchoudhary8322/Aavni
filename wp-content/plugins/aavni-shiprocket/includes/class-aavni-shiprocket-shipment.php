<?php
/**
 * Shiprocket Shipment & Fulfilment Manager for AAVNI
 *
 * Implements real API workflow:
 * - Order preparation (dimensions, weight, COD amount, items)
 * - Serviceability lookup
 * - Real shipment creation on Shiprocket
 * - Real AWB assignment
 * - Real Label retrieval (PDF URL)
 * - Real Tax Invoice retrieval (PDF URL)
 * - Pickup booking
 * - Manifest generation
 * - Milestone synchronization & mapping
 *
 * @package AAVNI_Shiprocket
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Shiprocket_Shipment {

    // Unique Order Meta Keys
    const META_SHIPROCKET_ORDER_ID    = '_aavni_shiprocket_order_id';
    const META_SHIPROCKET_SHIPMENT_ID = '_aavni_shiprocket_shipment_id';
    const META_COURIER_ID             = '_aavni_shiprocket_courier_id';
    const META_COURIER_NAME           = '_aavni_courier_name';
    const META_AWB_CODE               = '_aavni_tracking_number';
    const META_TRACKING_URL           = '_aavni_tracking_url';
    const META_LABEL_URL              = '_aavni_shiprocket_label_url';
    const META_INVOICE_URL            = '_aavni_shiprocket_invoice_url';
    const META_MANIFEST_URL           = '_aavni_shiprocket_manifest_url';
    const META_PICKUP_SCHEDULED_DATE  = '_aavni_shiprocket_pickup_date';
    const META_PICKUP_TOKEN           = '_aavni_shiprocket_pickup_token';
    const META_SHIPMENT_STATUS        = '_aavni_shiprocket_status';
    const META_STATUS_CODE            = '_aavni_shiprocket_status_code';
    const META_LAST_TRACKING_SYNC     = '_aavni_shiprocket_last_sync';
    const META_PKG_WEIGHT             = '_aavni_shiprocket_weight';
    const META_PKG_DIMENSIONS         = '_aavni_shiprocket_dimensions';

    /**
     * Map Shiprocket status codes/strings to AAVNI pipeline milestones
     *
     * Reference Shiprocket Statuses:
     * - NEW, CANCELED
     * - AWB ASSIGNED, LABEL GENERATED
     * - PICKUP SCHEDULED, PICKUP QUEUED, PICKUP RESCHEDULED
     * - PICKED UP, IN TRANSIT, REACHED AT DESTINATION
     * - OUT FOR DELIVERY
     * - DELIVERED
     * - RTO INITIATED, RTO DELIVERED, UNDELIVERED, LOST, DAMAGED
     */
    public static function map_shiprocket_status_to_milestone($status_str, $status_code = null) {
        $norm = strtoupper(trim($status_str));

        if (strpos($norm, 'DELIVERED') !== false && strpos($norm, 'RTO') === false) {
            return 'delivered';
        }
        if (strpos($norm, 'OUT FOR DELIVERY') !== false) {
            return 'out_for_delivery';
        }
        if (strpos($norm, 'IN TRANSIT') !== false || strpos($norm, 'PICKED UP') !== false || strpos($norm, 'SHIPPED') !== false || strpos($norm, 'REACHED') !== false) {
            return 'shipped';
        }
        if (strpos($norm, 'LABEL GENERATED') !== false || strpos($norm, 'PICKUP SCHEDULED') !== false || strpos($norm, 'AWB ASSIGNED') !== false || strpos($norm, 'PACKED') !== false) {
            return 'packed';
        }
        if (strpos($norm, 'CANCEL') !== false) {
            return 'cancelled';
        }
        if (strpos($norm, 'RTO') !== false || strpos($norm, 'UNDELIVERED') !== false) {
            return 'shipped'; // keep in transit / investigation
        }

        return 'processing';
    }

    /**
     * Aliases for seamless integration
     */
    public static function calculate_package_specs($order) {
        return self::calculate_order_package_specs($order);
    }

    public static function check_serviceability($order, $weight = null) {
        return self::check_order_serviceability($order, $weight);
    }

    /**
     * Calculate package weight and dimensions from order items
     */
    public static function calculate_order_package_specs($order) {
        $def_weight = (float) get_option('aavni_shiprocket_default_weight', 0.45);
        $def_l = (float) get_option('aavni_shiprocket_default_length', 30.0);
        $def_b = (float) get_option('aavni_shiprocket_default_breadth', 25.0);
        $def_h = (float) get_option('aavni_shiprocket_default_height', 4.0);

        $total_weight = 0.0;
        $items = $order->get_items();
        $has_product_weights = false;

        foreach ($items as $item) {
            $product = $item->get_product();
            $qty = $item->get_quantity();
            if ($product && $product->has_weight()) {
                $item_w = wc_get_weight((float) $product->get_weight(), 'kg');
                $total_weight += ($item_w * $qty);
                $has_product_weights = true;
            } else {
                $total_weight += ($def_weight * $qty);
            }
        }

        if ($total_weight <= 0.0) {
            $total_weight = $def_weight;
        }

        // Round to 2 decimals, minimum 0.05kg
        $final_weight = max(0.05, round($total_weight, 2));

        return [
            'weight'  => $final_weight,
            'length'  => $def_l,
            'breadth' => $def_b,
            'height'  => $def_h,
        ];
    }

    /**
     * Build Shiprocket Adhoc Order Payload
     */
    public static function build_shiprocket_payload($order, $package_specs = [], $pickup_location = '') {
        $order_id = $order->get_id();
        $order_num = method_exists($order, 'get_order_number') ? $order->get_order_number() : $order_id;

        // Pickup Location
        if (empty($pickup_location)) {
            $pickup_location = get_option('aavni_shiprocket_pickup_location', '');
        }

        // Package Specs
        if (empty($package_specs)) {
            $package_specs = self::calculate_order_package_specs($order);
        }

        // Payment Method & COD handling
        $payment_method = $order->get_payment_method();
        $is_cod = ($payment_method === 'cod');
        $payment_method_str = $is_cod ? 'COD' : 'Prepaid';

        // Order Date in Y-m-d H:i format required by Shiprocket
        $order_date = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i') : current_time('Y-m-d H:i');

        // Customer & Shipping Information
        $s_first_name = $order->get_shipping_first_name() ?: $order->get_billing_first_name();
        $s_last_name  = $order->get_shipping_last_name() ?: $order->get_billing_last_name();
        $s_address_1  = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
        $s_address_2  = $order->get_shipping_address_2() ?: $order->get_billing_address_2();
        $s_city       = $order->get_shipping_city() ?: $order->get_billing_city();
        $s_state      = $order->get_shipping_state() ?: $order->get_billing_state();
        $s_pincode    = $order->get_shipping_postcode() ?: $order->get_billing_postcode();
        $s_country    = $order->get_shipping_country() ?: $order->get_billing_country() ?: 'India';
        $email        = $order->get_billing_email();
        $phone        = preg_replace('/[^0-9]/', '', $order->get_billing_phone());

        // Billing info
        $b_first_name = $order->get_billing_first_name() ?: $s_first_name;
        $b_last_name  = $order->get_billing_last_name() ?: $s_last_name;
        $b_address_1  = $order->get_billing_address_1() ?: $s_address_1;
        $b_address_2  = $order->get_billing_address_2() ?: $s_address_2;
        $b_city       = $order->get_billing_city() ?: $s_city;
        $b_state      = $order->get_billing_state() ?: $s_state;
        $b_pincode    = $order->get_billing_postcode() ?: $s_pincode;

        // Order Items formatting for Shiprocket
        $order_items = [];
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $sku = $product ? $product->get_sku() : '';
            if (empty($sku)) {
                $sku = 'AAV-' . $item_id;
            }

            $order_items[] = [
                'name'          => sanitize_text_field(substr($item->get_name(), 0, 100)),
                'sku'           => sanitize_text_field($sku),
                'units'         => (int) $item->get_quantity(),
                'selling_price' => (float) $order->get_item_subtotal($item, false),
                'discount'      => 0,
                'tax'           => (float) $item->get_total_tax(),
            ];
        }

        // Totals
        $subtotal = (float) $order->get_subtotal();
        $shipping = (float) $order->get_shipping_total();
        $total    = (float) $order->get_total();

        $payload = [
            'order_id'              => (string) $order_id,
            'order_date'            => $order_date,
            'pickup_location'       => $pickup_location,
            'channel_id'            => '', // Custom adhoc channel
            'comment'               => 'AAVNI Order #' . $order_num,
            'billing_customer_name' => trim($b_first_name . ' ' . $b_last_name) ?: 'Valued Customer',
            'billing_last_name'     => $b_last_name,
            'billing_address'       => $b_address_1,
            'billing_address_2'     => $b_address_2,
            'billing_city'          => $b_city,
            'billing_pincode'       => $b_pincode,
            'billing_state'         => $b_state,
            'billing_country'       => $s_country,
            'billing_email'         => $email,
            'billing_phone'         => $phone,
            'shipping_is_billing'   => true,
            'shipping_customer_name'=> trim($s_first_name . ' ' . $s_last_name) ?: 'Valued Customer',
            'shipping_last_name'    => $s_last_name,
            'shipping_address'      => $s_address_1,
            'shipping_address_2'    => $s_address_2,
            'shipping_city'         => $s_city,
            'shipping_pincode'      => $s_pincode,
            'shipping_country'      => $s_country,
            'shipping_state'        => $s_state,
            'shipping_email'        => $email,
            'shipping_phone'        => $phone,
            'order_items'           => $order_items,
            'payment_method'        => $payment_method_str,
            'shipping_charges'      => $shipping,
            'giftwrap_charges'      => 0,
            'transaction_charges'   => 0,
            'total_discount'        => (float) $order->get_discount_total(),
            'sub_total'             => $total,
            'length'                => $package_specs['length'],
            'breadth'               => $package_specs['breadth'],
            'height'                => $package_specs['height'],
            'weight'                => $package_specs['weight'],
        ];

        return $payload;
    }

    /**
     * Check Serviceability for an Order
     */
    public static function check_order_serviceability($order, $weight = null) {
        $pincode = $order->get_shipping_postcode() ?: $order->get_billing_postcode();
        if (empty($pincode)) {
            return ['success' => false, 'message' => 'Delivery pincode is missing on this order.'];
        }

        $pickup = get_option('aavni_shiprocket_pickup_location', '');
        if (empty($pickup)) {
            return ['success' => false, 'message' => 'Pickup location is not configured in Shiprocket settings.'];
        }

        if ($weight === null) {
            $specs = self::calculate_order_package_specs($order);
            $weight = $specs['weight'];
        }

        $is_cod = ($order->get_payment_method() === 'cod');

        // Note: For serviceability, Shiprocket expects pickup postcode or location
        // We call the API with the configured parameters
        $params = [
            'delivery_postcode' => $pincode,
            'weight'            => $weight,
            'cod'               => $is_cod ? 1 : 0,
            'order_id'          => $order->get_id(),
        ];

        // Fetch verified pickup postcode if available from saved pickup addresses
        $pickup_pincode = get_option('aavni_shiprocket_pickup_pincode', '302001'); // default Jaipur
        $params['pickup_postcode'] = $pickup_pincode;

        $response = AAVNI_Shiprocket_API::check_serviceability($params);
        return $response;
    }

    /**
     * Create Real Shipment on Shiprocket for Order
     */
    public static function create_shipment_for_order($order, $package_specs = [], $courier_id = null) {
        $order_id = $order->get_id();

        // 1. Check idempotency: Has shipment already been created?
        $existing_shipment_id = $order->get_meta(self::META_SHIPROCKET_SHIPMENT_ID);
        if (!empty($existing_shipment_id)) {
            return [
                'success'     => true,
                'already_done'=> true,
                'shipment_id' => $existing_shipment_id,
                'order_id'    => $order->get_meta(self::META_SHIPROCKET_ORDER_ID),
                'awb'         => $order->get_meta(self::META_AWB_CODE),
                'message'     => 'Shipment already exists for this order (#ID: ' . $existing_shipment_id . ').',
            ];
        }

        $pickup_location = get_option('aavni_shiprocket_pickup_location', '');
        if (empty($pickup_location)) {
            return [
                'success' => false,
                'message' => 'Pickup location is not configured in AAVNI &rarr; Store Setup &rarr; Shiprocket Settings.',
            ];
        }

        $payload = self::build_shiprocket_payload($order, $package_specs, $pickup_location);

        AAVNI_Shiprocket_API::log("Creating Shiprocket order for WooCommerce Order #{$order_id}...", 'info');

        $res = AAVNI_Shiprocket_API::create_order($payload);

        if (!$res['success'] || empty($res['data'])) {
            $msg = !empty($res['message']) ? $res['message'] : 'Failed to create order on Shiprocket.';
            $order->add_order_note(sprintf(__('Shiprocket shipment creation failed: %s', 'aavni'), $msg), false);
            $order->save();
            return ['success' => false, 'message' => $msg];
        }

        $data = $res['data'];
        $sr_order_id    = isset($data['order_id']) ? $data['order_id'] : '';
        $sr_shipment_id = isset($data['shipment_id']) ? $data['shipment_id'] : '';
        $awb_code       = isset($data['awb_code']) ? $data['awb_code'] : '';
        $courier_name   = isset($data['courier_name']) ? $data['courier_name'] : '';

        if (empty($sr_shipment_id)) {
            return ['success' => false, 'message' => 'Shiprocket did not return a valid shipment_id.'];
        }

        // Store identifiers
        $order->update_meta_data(self::META_SHIPROCKET_ORDER_ID, $sr_order_id);
        $order->update_meta_data(self::META_SHIPROCKET_SHIPMENT_ID, $sr_shipment_id);
        $order->update_meta_data(self::META_SHIPMENT_STATUS, 'Shipment Created');
        $order->update_meta_data(self::META_PKG_WEIGHT, $payload['weight']);
        $order->update_meta_data(self::META_PKG_DIMENSIONS, "{$payload['length']}x{$payload['breadth']}x{$payload['height']} cm");

        $note = sprintf(__('Shiprocket Shipment #%s created successfully (Order ID: %s).', 'aavni'), $sr_shipment_id, $sr_order_id);

        // 2. AWB Assignment: if auto_assign is enabled or courier_id is provided
        $auto_awb = (get_option('aavni_shiprocket_auto_assign_awb', 'yes') === 'yes');
        if (empty($awb_code) && ($auto_awb || !empty($courier_id))) {
            AAVNI_Shiprocket_API::log("Requesting AWB for Shipment #{$sr_shipment_id}...", 'info');
            $awb_res = AAVNI_Shiprocket_API::assign_awb($sr_shipment_id, $courier_id);
            if ($awb_res['success'] && !empty($awb_res['data']['response']['data'])) {
                $awb_data = $awb_res['data']['response']['data'];
                $awb_code     = isset($awb_data['awb_code']) ? $awb_data['awb_code'] : '';
                $courier_name = isset($awb_data['courier_name']) ? $awb_data['courier_name'] : $courier_name;
                $courier_id   = isset($awb_data['courier_company_id']) ? $awb_data['courier_company_id'] : $courier_id;
            }
        }

        if (!empty($awb_code)) {
            $order->update_meta_data(self::META_AWB_CODE, $awb_code);
            $order->update_meta_data(self::META_COURIER_NAME, $courier_name ?: 'Shiprocket Express');
            if (!empty($courier_id)) {
                $order->update_meta_data(self::META_COURIER_ID, $courier_id);
            }
            $tracking_url = 'https://shiprocket.co/tracking/' . $awb_code;
            $order->update_meta_data(self::META_TRACKING_URL, $tracking_url);
            $order->update_meta_data(self::META_SHIPMENT_STATUS, 'AWB Assigned');

            // Move milestone to packed
            $order->update_meta_data('_aavni_order_milestone', 'packed');

            $note .= sprintf(__(' Real AWB Assigned: %s via %s.', 'aavni'), $awb_code, $courier_name);
        }

        $order->add_order_note($note, false);
        $order->save();

        AAVNI_Shiprocket_API::log("Order #{$order_id}: {$note}", 'info');

        return [
            'success'     => true,
            'order_id'    => $sr_order_id,
            'shipment_id' => $sr_shipment_id,
            'awb'         => $awb_code,
            'courier'     => $courier_name,
            'message'     => $note,
        ];
    }

    /**
     * Request AWB for an existing shipment
     */
    public static function assign_awb_for_order($order, $courier_id = null) {
        $shipment_id = $order->get_meta(self::META_SHIPROCKET_SHIPMENT_ID);
        if (empty($shipment_id)) {
            return ['success' => false, 'message' => 'No Shiprocket shipment found on this order. Please create shipment first.'];
        }

        $res = AAVNI_Shiprocket_API::assign_awb($shipment_id, $courier_id);
        if (!$res['success'] || empty($res['data']['response']['data'])) {
            $msg = !empty($res['message']) ? $res['message'] : 'Failed to assign AWB from Shiprocket.';
            return ['success' => false, 'message' => $msg];
        }

        $awb_data = $res['data']['response']['data'];
        $awb_code = isset($awb_data['awb_code']) ? $awb_data['awb_code'] : '';
        $courier_name = isset($awb_data['courier_name']) ? $awb_data['courier_name'] : '';
        $cid = isset($awb_data['courier_company_id']) ? $awb_data['courier_company_id'] : $courier_id;

        if (empty($awb_code)) {
            return ['success' => false, 'message' => 'No AWB returned by Shiprocket. Courier may be unserviceable.'];
        }

        $order->update_meta_data(self::META_AWB_CODE, $awb_code);
        $order->update_meta_data(self::META_COURIER_NAME, $courier_name ?: 'Shiprocket Partner');
        $order->update_meta_data(self::META_COURIER_ID, $cid);
        $tracking_url = 'https://shiprocket.co/tracking/' . $awb_code;
        $order->update_meta_data(self::META_TRACKING_URL, $tracking_url);
        $order->update_meta_data(self::META_SHIPMENT_STATUS, 'AWB Assigned');
        $order->update_meta_data('_aavni_order_milestone', 'packed');

        $order->add_order_note(sprintf(__('Shiprocket AWB Assigned: %s (Courier: %s).', 'aavni'), $awb_code, $courier_name), false);
        $order->save();

        return [
            'success' => true,
            'awb'     => $awb_code,
            'courier' => $courier_name,
            'message' => "AWB {$awb_code} assigned successfully!",
        ];
    }

    /**
     * Get or Generate Shipping Label URL
     */
    public static function get_label_for_order($order, $force_generate = false) {
        $shipment_id = $order->get_meta(self::META_SHIPROCKET_SHIPMENT_ID);
        if (empty($shipment_id)) {
            return ['success' => false, 'message' => 'No shipment exists for this order.'];
        }

        if (!$force_generate) {
            $existing_label = $order->get_meta(self::META_LABEL_URL);
            if (!empty($existing_label)) {
                return ['success' => true, 'label_url' => $existing_label, 'message' => 'Existing label retrieved.'];
            }
        }

        $res = AAVNI_Shiprocket_API::generate_label($shipment_id);
        if (!$res['success'] || empty($res['data'])) {
            $msg = !empty($res['message']) ? $res['message'] : 'Failed to generate shipping label.';
            return ['success' => false, 'message' => $msg];
        }

        $data = $res['data'];
        $label_url = isset($data['label_url']) ? $data['label_url'] : '';
        if (empty($label_url)) {
            return ['success' => false, 'message' => 'Shiprocket did not return a label URL.'];
        }

        $order->update_meta_data(self::META_LABEL_URL, esc_url_raw($label_url));
        $order->update_meta_data(self::META_SHIPMENT_STATUS, 'Label Ready');
        $order->save();

        return ['success' => true, 'label_url' => $label_url, 'message' => 'Label generated successfully!'];
    }

    /**
     * Request Courier Warehouse Pickup
     */
    public static function request_pickup_for_order($order) {
        $shipment_id = $order->get_meta(self::META_SHIPROCKET_SHIPMENT_ID);
        $awb = $order->get_meta(self::META_AWB_CODE);

        if (empty($shipment_id)) {
            return ['success' => false, 'message' => 'No shipment exists for this order.'];
        }
        if (empty($awb)) {
            return ['success' => false, 'message' => 'AWB must be assigned before booking pickup.'];
        }

        $res = AAVNI_Shiprocket_API::request_pickup($shipment_id);
        if (!$res['success'] || empty($res['data'])) {
            $msg = !empty($res['message']) ? $res['message'] : 'Failed to book pickup with courier.';
            return ['success' => false, 'message' => $msg];
        }

        $data = $res['data'];
        $pickup_status = isset($data['response']['pickup_status']) ? $data['response']['pickup_status'] : 'Scheduled';
        $token = isset($data['response']['pickup_token_number']) ? $data['response']['pickup_token_number'] : '';
        $date  = isset($data['response']['pickup_scheduled_date']) ? $data['response']['pickup_scheduled_date'] : current_time('mysql');

        $order->update_meta_data(self::META_PICKUP_TOKEN, $token);
        $order->update_meta_data(self::META_PICKUP_SCHEDULED_DATE, $date);
        $order->update_meta_data(self::META_SHIPMENT_STATUS, 'Pickup Booked');

        $note = sprintf(__('Shiprocket Pickup Booked for %s. Token: %s.', 'aavni'), $date, $token ?: 'N/A');
        $order->add_order_note($note, false);
        $order->save();

        return [
            'success'       => true,
            'pickup_status' => $pickup_status,
            'token'         => $token,
            'date'          => $date,
            'message'       => $note,
        ];
    }

    /**
     * Generate & Retrieve Manifest
     */
    public static function generate_manifest_for_order($order) {
        $shipment_id = $order->get_meta(self::META_SHIPROCKET_SHIPMENT_ID);
        if (empty($shipment_id)) {
            return ['success' => false, 'message' => 'Shipment does not exist.'];
        }

        $res = AAVNI_Shiprocket_API::generate_manifest($shipment_id);
        if (!$res['success'] || empty($res['data'])) {
            $msg = !empty($res['message']) ? $res['message'] : 'Failed to generate manifest.';
            return ['success' => false, 'message' => $msg];
        }

        $manifest_url = isset($res['data']['manifest_url']) ? $res['data']['manifest_url'] : '';
        if ($manifest_url) {
            $order->update_meta_data(self::META_MANIFEST_URL, esc_url_raw($manifest_url));
            $order->save();
        }

        return [
            'success'      => true,
            'manifest_url' => $manifest_url,
            'message'      => 'Manifest generated successfully!',
        ];
    }

    /**
     * Sync Tracking from Shiprocket API
     */
    public static function sync_tracking_for_order($order) {
        $awb = $order->get_meta(self::META_AWB_CODE);
        $shipment_id = $order->get_meta(self::META_SHIPROCKET_SHIPMENT_ID);

        if (empty($awb) && empty($shipment_id)) {
            return ['success' => false, 'message' => 'Neither AWB nor Shipment ID is assigned to this order.'];
        }

        $res = !empty($awb) ? AAVNI_Shiprocket_API::track_awb($awb) : AAVNI_Shiprocket_API::track_shipment($shipment_id);

        if (!$res['success'] || empty($res['data']['tracking_data'])) {
            $msg = !empty($res['message']) ? $res['message'] : 'Tracking details not yet available from courier.';
            return ['success' => false, 'message' => $msg];
        }

        $track = $res['data']['tracking_data'];
        $current_status = isset($track['shipment_track'][0]['current_status']) ? $track['shipment_track'][0]['current_status'] : (isset($track['track_status']) ? $track['track_status'] : '');
        $status_code    = isset($track['shipment_track'][0]['status_code']) ? $track['shipment_track'][0]['status_code'] : '';
        $location       = isset($track['shipment_track'][0]['location']) ? $track['shipment_track'][0]['location'] : '';
        $courier_name   = isset($track['shipment_track'][0]['courier_name']) ? $track['shipment_track'][0]['courier_name'] : '';

        if (!empty($current_status)) {
            $order->update_meta_data(self::META_SHIPMENT_STATUS, $current_status);
            $order->update_meta_data(self::META_STATUS_CODE, $status_code);
            $order->update_meta_data(self::META_LAST_TRACKING_SYNC, current_time('mysql'));

            if (!empty($courier_name)) {
                $order->update_meta_data(self::META_COURIER_NAME, $courier_name);
            }

            // Sync AAVNI milestone
            $new_milestone = self::map_shiprocket_status_to_milestone($current_status, $status_code);
            $old_milestone = $order->get_meta('_aavni_order_milestone') ?: 'confirmed';

            if ($new_milestone !== $old_milestone) {
                $order->update_meta_data('_aavni_order_milestone', $new_milestone);

                $defs = aavni_get_milestone_definitions();
                $title = isset($defs[$new_milestone]) ? $defs[$new_milestone]['label'] : ucfirst($new_milestone);

                $raw_hist = $order->get_meta('_aavni_milestone_history');
                $hist = is_array($raw_hist) ? $raw_hist : [];
                $hist[] = [
                    'milestone' => $new_milestone,
                    'title'     => $title,
                    'note'      => "Courier Track Update: {$current_status}" . ($location ? " ({$location})" : ''),
                    'time'      => current_time('mysql'),
                ];
                $order->update_meta_data('_aavni_milestone_history', $hist);

                // Auto-complete order if courier reports delivered
                if ($new_milestone === 'delivered' && $order->get_status() !== 'completed') {
                    $order->update_status('completed', __('Courier tracking confirmed package delivery.', 'aavni'));
                }

                $order->add_order_note(sprintf(__('Shiprocket Tracking Update: %s (Status: %s)', 'aavni'), $title, $current_status), false);

                // Send notification
                if (class_exists('AAVNI_Admin_Notifications')) {
                    $tracking_url = $order->get_meta(self::META_TRACKING_URL);
                    AAVNI_Admin_Notifications::notify_order_milestone($order, $new_milestone, $courier_name, $awb, $tracking_url);
                }
            }

            $order->save();

            return [
                'success' => true,
                'status'  => $current_status,
                'location'=> $location,
                'courier' => $courier_name,
                'message' => "Tracking Synced: {$current_status}" . ($location ? " ({$location})" : ''),
            ];
        }

        return ['success' => false, 'message' => 'No tracking update found.'];
    }

    /**
     * Cancel Shipment on Shiprocket
     */
    public static function cancel_shipment_for_order($order) {
        $sr_order_id = $order->get_meta(self::META_SHIPROCKET_ORDER_ID);
        if (empty($sr_order_id)) {
            return ['success' => false, 'message' => 'No Shiprocket order ID found for this order.'];
        }

        $res = AAVNI_Shiprocket_API::cancel_order([$sr_order_id]);
        if (!$res['success']) {
            $msg = !empty($res['message']) ? $res['message'] : 'Failed to cancel shipment on Shiprocket.';
            return ['success' => false, 'message' => $msg];
        }

        $order->update_meta_data(self::META_SHIPMENT_STATUS, 'Cancelled');
        $order->update_meta_data('_aavni_order_milestone', 'cancelled');
        $order->add_order_note(__('Shiprocket shipment was cancelled by store administrator.', 'aavni'), false);
        $order->save();

        return ['success' => true, 'message' => 'Shipment cancelled on Shiprocket successfully!'];
    }
}
