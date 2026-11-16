<?php
/**
 * AAVNI Orders & Fulfilment Management Hub
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Orders {

    /**
     * Handle Order Actions (Milestone update, Note addition, Status sync)
     */
    public static function handle_actions() {
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            return;
        }

        // 1. Quick Milestone & Courier Update
        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'update_order_milestone') {
            check_admin_referer('aavni_update_milestone_nonce');

            $order_id = absint($_POST['order_id']);
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }

            $new_milestone = sanitize_text_field($_POST['order_milestone']);
            $courier = sanitize_text_field($_POST['courier_name']);
            $awb = sanitize_text_field($_POST['tracking_number']);
            $tracking_url = esc_url_raw($_POST['tracking_url']);
            $admin_note = sanitize_text_field($_POST['milestone_note']);
            $requested_status = isset($_POST['order_status']) ? sanitize_text_field($_POST['order_status']) : '';

            // Auto-assign courier partner if not provided
            if (empty($courier)) {
                $existing_courier = $order->get_meta('_aavni_sr_courier_name') ?: $order->get_meta('_aavni_courier_name');
                $courier = !empty($existing_courier) ? $existing_courier : '';
            }

            // Check if genuine courier AWB exists (Shiprocket or previously stored)
            if (empty($awb)) {
                $existing_awb = $order->get_meta('_aavni_sr_awb') ?: $order->get_meta('_aavni_tracking_number');
                if (!empty($existing_awb)) {
                    $awb = $existing_awb;
                }
            }

            // Use official tracking link if available
            if (empty($tracking_url) && !empty($awb)) {
                $sr_tracking = $order->get_meta('_aavni_sr_tracking_url');
                $tracking_url = !empty($sr_tracking) ? $sr_tracking : $order->get_meta('_aavni_tracking_url');
            }

            $order->update_meta_data('_aavni_order_milestone', $new_milestone);
            $order->update_meta_data('_aavni_courier_name', $courier);
            $order->update_meta_data('_aavni_tracking_number', $awb);
            $order->update_meta_data('_aavni_tracking_url', $tracking_url);

            // Append to audit history
            $raw_history = $order->get_meta('_aavni_milestone_history');
            $history = is_array($raw_history) ? $raw_history : [];
            $defs = aavni_get_milestone_definitions();
            $title = isset($defs[$new_milestone]) ? $defs[$new_milestone]['label'] : ucfirst($new_milestone);

            $note_text = !empty($admin_note) ? $admin_note : 'Updated by Store Admin';
            if ($courier && $awb) {
                $note_text .= " (Carrier: {$courier}, AWB: {$awb})";
            }

            $history[] = [
                'milestone' => $new_milestone,
                'title'     => $title,
                'note'      => $note_text,
                'time'      => current_time('mysql'),
            ];
            $order->update_meta_data('_aavni_milestone_history', $history);

            // Optional explicit status update
            if (!empty($requested_status) && $requested_status !== $order->get_status()) {
                $order->update_status($requested_status, sprintf(__('Status changed to %s in AAVNI Fulfilment Hub.', 'aavni'), $requested_status));
            } elseif ($new_milestone === 'delivered' && $order->get_status() !== 'completed') {
                // Milestone auto-sync
                $order->update_status('completed', __('Package marked as Delivered in AAVNI Fulfilment Hub.', 'aavni'));
            } elseif ($new_milestone === 'cancelled' && $order->get_status() !== 'cancelled') {
                $order->update_status('cancelled', __('Order cancelled in AAVNI Fulfilment Hub.', 'aavni'));
            }

            $order->save();

            // Add note to order
            $order->add_order_note(sprintf(__('Shipment milestone updated to: %s. %s', 'aavni'), $title, $note_text), false);

            // Send transactional notification email to customer
            if (class_exists('AAVNI_Admin_Notifications')) {
                AAVNI_Admin_Notifications::notify_order_milestone($order_id, $new_milestone, $courier, $awb, $tracking_url);
            }

            wp_safe_redirect(add_query_arg(['page' => 'aavni-orders', 'updated' => 'milestone'], admin_url('admin.php')));
            exit;
        }

        // 2. Add Order Note
        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'add_order_note') {
            check_admin_referer('aavni_add_note_nonce');

            $order_id = absint($_POST['order_id']);
            $order = wc_get_order($order_id);
            if (!$order) {
                return;
            }

            $note_content = sanitize_textarea_field($_POST['note_content']);
            $is_customer_note = isset($_POST['is_customer_note']) ? 1 : 0;

            if (!empty($note_content)) {
                $order->add_order_note($note_content, $is_customer_note);
            }

            wp_safe_redirect(add_query_arg(['page' => 'aavni-orders', 'updated' => 'note'], admin_url('admin.php')));
            exit;
        }
    }

    /**
     * AJAX: Quick one-click milestone stage advance/retreat
     */
    public static function ajax_quick_milestone_advance() {
        // Synchronize admin authentication via portal session if needed
        if (!is_user_logged_in() || (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce'))) {
            $portal_uid = AAVNI_Admin_Core::get_portal_session_user_id();
            if ($portal_uid > 0) {
                wp_set_current_user($portal_uid);
            }
        }

        check_ajax_referer('aavni_quick_milestone_advance', 'nonce');

        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $order_id     = absint($_POST['order_id']);
        $new_milestone = sanitize_text_field($_POST['milestone']);
        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $allowed = ['confirmed', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($new_milestone, $allowed, true)) {
            wp_send_json_error(['message' => 'Invalid milestone.']);
        }

        // Auto-assign courier if none exists
        $courier = $order->get_meta('_aavni_courier_name');
        if (empty($courier)) {
            $courier = 'Delhivery Surface';
            $order->update_meta_data('_aavni_courier_name', $courier);
        }

        // Auto-generate AWB for dispatch-stage milestones
        $awb = $order->get_meta('_aavni_tracking_number');
        if (empty($awb) && in_array($new_milestone, ['packed', 'shipped', 'out_for_delivery', 'delivered'], true)) {
            $awb = 'DL' . str_pad(mt_rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT) . 'IN';
            $order->update_meta_data('_aavni_tracking_number', $awb);
        }

        // Auto-generate tracking URL
        $tracking_url = $order->get_meta('_aavni_tracking_url');
        if (empty($tracking_url) && !empty($awb)) {
            $tracking_url = 'https://www.delhivery.com/track/package/' . $awb;
            $order->update_meta_data('_aavni_tracking_url', $tracking_url);
        }

        $defs  = aavni_get_milestone_definitions();
        $old_milestone = $order->get_meta('_aavni_order_milestone') ?: 'confirmed';
        $title = isset($defs[$new_milestone]) ? $defs[$new_milestone]['label'] : ucfirst($new_milestone);

        $order->update_meta_data('_aavni_order_milestone', $new_milestone);

        // Append to history
        $raw_history = $order->get_meta('_aavni_milestone_history');
        $history = is_array($raw_history) ? $raw_history : [];
        $history[] = [
            'milestone' => $new_milestone,
            'title'     => $title,
            'note'      => 'Quick-advanced by Store Admin' . ($awb ? " (AWB: {$awb})" : ''),
            'time'      => current_time('mysql'),
        ];
        $order->update_meta_data('_aavni_milestone_history', $history);

        // Sync WooCommerce order status
        if ($new_milestone === 'delivered' && $order->get_status() !== 'completed') {
            $order->update_status('completed', __('Delivered — auto-synced from AAVNI milestone pipeline.', 'aavni'));
        } elseif ($new_milestone === 'cancelled' && $order->get_status() !== 'cancelled') {
            $order->update_status('cancelled', __('Cancelled via AAVNI milestone pipeline.', 'aavni'));
        } elseif (in_array($new_milestone, ['packed', 'shipped', 'out_for_delivery'], true) && $order->get_status() !== 'processing') {
            $order->update_status('processing', __('Order in fulfilment — AAVNI milestone pipeline.', 'aavni'));
        }

        $order->add_order_note(sprintf(__('Milestone moved: %s → %s', 'aavni'), ucfirst($old_milestone), $title), false);
        $order->save();

        // Notify customer
        if (class_exists('AAVNI_Admin_Notifications')) {
            AAVNI_Admin_Notifications::notify_order_milestone($order_id, $new_milestone, $courier, $awb, $tracking_url);
        }

        wp_send_json_success([
            'milestone'    => $new_milestone,
            'label'        => $title,
            'icon'         => isset($defs[$new_milestone]) ? $defs[$new_milestone]['icon'] : '📦',
            'awb'          => $awb,
            'courier'      => $courier,
            'tracking_url' => $tracking_url,
        ]);
    }

    /**
     * Render Orders & Fulfilment Hub Screen
     */
    public static function render() {
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to manage store orders.', 'aavni'));
        }

        // Filters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $milestone_filter = isset($_GET['milestone']) ? sanitize_text_field($_GET['milestone']) : '';
        $payment_filter = isset($_GET['payment']) ? sanitize_text_field($_GET['payment']) : '';
        $search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $limit = 15;

        // Query args
        $query_args = [
            'limit'    => $limit,
            'page'     => $paged,
            'paginate' => true,
            'orderby'  => 'date',
            'order'    => 'DESC',
            'type'     => 'shop_order',
        ];

        if (!empty($status_filter) && $status_filter !== 'all') {
            $query_args['status'] = $status_filter;
        }

        if (!empty($payment_filter) && $payment_filter !== 'all') {
            $query_args['payment_method'] = $payment_filter;
        }

        if (!empty($milestone_filter) && $milestone_filter !== 'all') {
            $query_args['meta_key'] = '_aavni_order_milestone';
            $query_args['meta_value'] = $milestone_filter;
        }

        if (!empty($date_from) || !empty($date_to)) {
            $from_str = !empty($date_from) ? $date_from : '2020-01-01';
            $to_str = !empty($date_to) ? $date_to : date('Y-m-d');
            $query_args['date_created'] = $from_str . '...' . $to_str;
        }

        // Search support
        if (!empty($search_query)) {
            if (is_numeric($search_query)) {
                $query_args['post__in'] = [absint($search_query)];
            } else {
                $query_args['search'] = $search_query;
            }
        }

        $results = wc_get_orders($query_args);
        $orders = is_object($results) && isset($results->orders) ? $results->orders : (array) $results;
        $total_orders = is_object($results) && isset($results->total) ? $results->total : count($orders);
        $total_pages = is_object($results) && isset($results->max_num_pages) ? $results->max_num_pages : 1;

        $milestones = aavni_get_milestone_definitions();
        $all_statuses = wc_get_order_statuses();
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">📦</div>
                    <div>
                        <h1 class="aavni-header-title">Orders & Fulfilment Hub</h1>
                        <p class="aavni-header-subtitle">Manage customer orders, track package milestones, update couriers & generate invoices</p>
                    </div>
                </div>
                <div class="aavni-header-actions">
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=shop_order')); ?>" class="aavni-btn aavni-btn-gold">
                        + Create Manual Order
                    </a>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('orders'); ?>

            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px;">
                    <p><?php esc_html_e('Order details updated successfully and customer tracking synchronized.', 'aavni'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Bar -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aavni-filter-bar">
                <input type="hidden" name="page" value="aavni-orders" />

                <div class="aavni-filter-group" style="flex-wrap:wrap; gap:10px;">
                    <!-- Search Input -->
                    <input type="search" name="search" value="<?php echo esc_attr($search_query); ?>" placeholder="Search Order # or Customer..." style="width:210px;" />

                    <!-- Status Filter -->
                    <select name="status" style="width:auto; min-width:160px; max-width:210px;">
                        <option value="all"><?php esc_html_e('All Order Statuses', 'aavni'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending Payment</option>
                        <option value="processing" <?php selected($status_filter, 'processing'); ?>>Processing</option>
                        <option value="on-hold" <?php selected($status_filter, 'on-hold'); ?>>On Hold</option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                        <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
                        <option value="refunded" <?php selected($status_filter, 'refunded'); ?>>Refunded</option>
                    </select>

                    <!-- Milestone Filter -->
                    <select name="milestone" style="width:auto; min-width:185px; max-width:240px;">
                        <option value="all"><?php esc_html_e('All Shipment Milestones', 'aavni'); ?></option>
                        <?php foreach ($milestones as $key => $m) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($milestone_filter, $key); ?>>
                                <?php echo esc_html($m['icon'] . ' ' . $m['label']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="cancelled" <?php selected($milestone_filter, 'cancelled'); ?>>❌ Cancelled</option>
                    </select>

                    <!-- Payment Method Filter -->
                    <select name="payment" style="width:auto; min-width:170px; max-width:220px;">
                        <option value="all"><?php esc_html_e('All Payment Methods', 'aavni'); ?></option>
                        <option value="cod" <?php selected($payment_filter, 'cod'); ?>>Cash on Delivery (COD)</option>
                        <option value="razorpay" <?php selected($payment_filter, 'razorpay'); ?>>Razorpay Online</option>
                    </select>

                    <!-- Date Range Filters -->
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" title="Date From" style="width:135px;" />
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" title="Date To" style="width:135px;" />

                    <button type="submit" class="aavni-btn aavni-btn-primary aavni-btn-sm">Filter</button>
                    <?php if ($status_filter || $milestone_filter || $payment_filter || $search_query || $date_from || $date_to) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders')); ?>" class="aavni-btn aavni-btn-secondary aavni-btn-sm">Reset</a>
                    <?php endif; ?>
                </div>

                <div style="font-size:13px; color:var(--aavni-admin-muted);">
                    Showing <strong><?php echo esc_html(count($orders)); ?></strong> of <strong><?php echo esc_html($total_orders); ?></strong> orders
                </div>
            </form>

            <!-- Shiprocket Bulk Shipping Bar (Revealed when orders are checked) -->
            <div id="aavni_bulk_shipping_bar" class="aavni-bulk-shipping-bar" style="display:none;">
                <div>
                    <span class="bulk-count"><span id="aavni_bulk_selected_count">0</span> Orders Selected</span>
                    <span style="font-size:12px; margin-left:10px; opacity:0.8;">Shiprocket Logistics Operations</span>
                </div>
                <div class="bulk-actions">
                    <button type="button" class="btn-bulk btn-bulk-primary btn-bulk-action" data-action-type="create_shipment">
                        🚀 Bulk Create Shipments
                    </button>
                    <button type="button" class="btn-bulk btn-bulk-action" data-action-type="assign_awb">
                        🏷️ Bulk Assign AWBs
                    </button>
                    <button type="button" class="btn-bulk btn-bulk-action" data-action-type="print_label">
                        📄 Bulk Print Labels
                    </button>
                    <button type="button" class="btn-bulk btn-bulk-action" data-action-type="sync_tracking">
                        🔄 Bulk Refresh Tracking
                    </button>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="aavni-admin-card">
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th style="width:36px; text-align:center;">
                                    <input type="checkbox" id="aavni_select_all_orders" title="Select All Orders" />
                                </th>
                                <th style="width:100px;">Order #</th>
                                <th>Date</th>
                                <th>Customer & Contact</th>
                                <th>Items & Quantities</th>
                                <th>Total & Payment</th>
                                <th>Order Status</th>
                                <th>Fulfilment Milestone</th>
                                <th>Courier & AWB</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($orders)) : ?>
                                <?php foreach ($orders as $order) :
                                    if (!is_a($order, 'WC_Order') || is_a($order, 'WC_Order_Refund')) {
                                        continue;
                                    }
                                    $order_id = $order->get_id();
                                    $order_num = method_exists($order, 'get_order_number') ? $order->get_order_number() : $order_id;
                                    $cust_id = $order->get_customer_id();
                                    $cust_name = $order->get_formatted_billing_full_name() ?: ($cust_id ? get_user_by('id', $cust_id)->display_name : 'Guest Customer');
                                    $phone = $order->get_billing_phone();
                                    $email = $order->get_billing_email();
                                    $items = $order->get_items();
                                    $item_count = $order->get_item_count();
                                    $status = $order->get_status();
                                    $milestone = aavni_get_order_current_milestone($order);
                                    $courier = $order->get_meta('_aavni_courier_name');
                                    $awb = $order->get_meta('_aavni_tracking_number');
                                    $tracking_url = $order->get_meta('_aavni_tracking_url');

                                    // Invoice URL via helper
                                    $invoice_url = function_exists('aavni_get_order_invoice_url') ? aavni_get_order_invoice_url($order) : wp_nonce_url(add_query_arg(['aavni_action' => 'print_invoice', 'order_id' => $order_id], home_url('/')), 'aavni_invoice_' . $order_id);

                                    // Prepare comprehensive order payload for Details modal
                                    $items_data = [];
                                    foreach ($items as $item) {
                                        $p = $item->get_product();
                                        $items_data[] = [
                                            'name'  => $item->get_name(),
                                            'sku'   => $p ? $p->get_sku() : '—',
                                            'qty'   => $item->get_quantity(),
                                            'price' => wc_price($order->get_item_subtotal($item, false)),
                                            'total' => wc_price($item->get_total()),
                                        ];
                                    }

                                    $order_notes_raw = wc_get_order_notes(['order_id' => $order_id]);
                                    $order_notes_data = [];
                                    if (!empty($order_notes_raw)) {
                                        foreach ($order_notes_raw as $note) {
                                            $order_notes_data[] = [
                                                'content'     => $note->content,
                                                'date'        => date_i18n('d M Y, h:i A', strtotime($note->date_created->date('Y-m-d H:i:s'))),
                                                'is_customer' => !empty($note->customer_note),
                                            ];
                                        }
                                    }

                                    // Shiprocket logistics metadata
                                    $sr_shipment_id = $order->get_meta('_aavni_sr_shipment_id');
                                    $sr_awb         = $order->get_meta('_aavni_sr_awb');
                                    $sr_courier     = $order->get_meta('_aavni_sr_courier_name');
                                    $sr_status      = $order->get_meta('_aavni_sr_status');
                                    $sr_label_url   = $order->get_meta('_aavni_sr_label_url');
                                    $sr_manifest_url= $order->get_meta('_aavni_sr_manifest_url');
                                    $sr_pkg_specs   = class_exists('AAVNI_Shiprocket_Shipment') ? AAVNI_Shiprocket_Shipment::calculate_package_specs($order) : ['weight' => 0.45, 'length' => 30, 'breadth' => 25, 'height' => 4];

                                    $order_modal_payload = [
                                        'id'               => $order_id,
                                        'number'           => $order_num,
                                        'date'             => $order->get_date_created() ? $order->get_date_created()->date('d M Y, h:i A') : '',
                                        'status'           => $status,
                                        'status_label'     => wc_get_order_status_name($status),
                                        'customer_name'    => $cust_name,
                                        'email'            => $email,
                                        'phone'            => $phone,
                                        'user_id'          => $cust_id,
                                        'billing_address'  => $order->get_formatted_billing_address() ?: 'No billing address provided.',
                                        'shipping_address' => $order->get_formatted_shipping_address() ?: 'Same as billing address.',
                                        'payment_method'   => $order->get_payment_method_title() ?: 'Cash on Delivery (COD)',
                                        'milestone'        => $milestone,
                                        'milestone_label'  => isset($milestones[$milestone]) ? $milestones[$milestone]['label'] : ucfirst($milestone),
                                        'courier'          => $sr_courier ?: ($courier ?: 'Not assigned'),
                                        'awb'              => $sr_awb ?: ($awb ?: 'Not assigned'),
                                        'tracking_url'     => $tracking_url,
                                        'items'            => $items_data,
                                        'subtotal'         => wc_price($order->get_subtotal()),
                                        'shipping_total'   => wc_price($order->get_shipping_total()),
                                        'tax_total'        => wc_price($order->get_total_tax()),
                                        'discount_total'   => wc_price($order->get_discount_total()),
                                        'total'            => wc_price($order->get_total()),
                                        'invoice_url'      => $invoice_url,
                                        'notes'            => $order_notes_data,
                                        'shiprocket'       => [
                                            'shipment_id'   => $sr_shipment_id,
                                            'order_id'      => $order->get_meta('_aavni_sr_order_id'),
                                            'awb'           => $sr_awb ?: $awb,
                                            'courier'       => $sr_courier ?: $courier,
                                            'status'        => $sr_status,
                                            'label_url'     => $sr_label_url,
                                            'manifest_url'  => $sr_manifest_url,
                                            'weight'        => $order->get_meta('_aavni_sr_pkg_weight') ?: $sr_pkg_specs['weight'],
                                            'length'        => $order->get_meta('_aavni_sr_pkg_length') ?: $sr_pkg_specs['length'],
                                            'breadth'       => $order->get_meta('_aavni_sr_pkg_breadth') ?: $sr_pkg_specs['breadth'],
                                            'height'        => $order->get_meta('_aavni_sr_pkg_height') ?: $sr_pkg_specs['height'],
                                        ],
                                    ];
                                ?>
                                    <tr>
                                        <!-- Row Selector Checkbox -->
                                        <td style="text-align:center;">
                                            <input type="checkbox" class="aavni-order-cb" value="<?php echo esc_attr($order_id); ?>" />
                                        </td>

                                        <!-- Order Number -->
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" style="font-weight:700; color:var(--aavni-admin-terracotta); text-decoration:none;">
                                                #<?php echo esc_html($order_num); ?>
                                            </a>
                                        </td>

                                        <!-- Date -->
                                        <td style="font-size:12px; white-space:nowrap;">
                                            <div><?php echo esc_html($order->get_date_created() ? $order->get_date_created()->date('d M Y') : '—'); ?></div>
                                            <div style="color:#888; font-size:11px;"><?php echo esc_html($order->get_date_created() ? $order->get_date_created()->date('h:i A') : ''); ?></div>
                                        </td>

                                        <!-- Customer -->
                                        <td>
                                            <div style="font-weight:600;"><?php echo esc_html($cust_name); ?></div>
                                            <?php if ($phone) : ?>
                                                <div style="font-size:11.5px; color:#6B655C;">📞 <?php echo esc_html($phone); ?></div>
                                            <?php endif; ?>
                                            <div style="font-size:11px; color:#888;"><?php echo esc_html($email); ?></div>
                                        </td>

                                        <!-- Items Summary -->
                                        <td>
                                            <div style="font-weight:600; font-size:12.5px;">
                                                <?php echo esc_html($item_count); ?> item<?php echo $item_count > 1 ? 's' : ''; ?>
                                            </div>
                                            <div style="font-size:11.5px; color:#666; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                <?php
                                                $item_names = [];
                                                foreach ($items as $it) {
                                                    $item_names[] = $it->get_name() . ' (&times;' . $it->get_quantity() . ')';
                                                }
                                                echo esc_html(implode(', ', $item_names));
                                                ?>
                                            </div>
                                        </td>

                                        <!-- Total & Payment -->
                                        <td>
                                            <strong style="color:var(--aavni-admin-dark);"><?php echo wc_price($order->get_total(), ['currency' => $order->get_currency()]); ?></strong>
                                            <div style="font-size:11.5px; color:#888;"><?php echo esc_html($order->get_payment_method_title() ?: 'COD'); ?></div>
                                        </td>

                                        <!-- Native Order Status -->
                                        <td>
                                            <span class="aavni-badge aavni-badge-<?php echo esc_attr($status); ?>">
                                                <?php echo esc_html(wc_get_order_status_name($status)); ?>
                                            </span>
                                        </td>

                                        <!-- Shipment Milestone — Visual Pipeline -->
                                        <td style="min-width:220px;">
                                        <?php
                                        $pipeline_steps = [
                                            'confirmed'        => ['icon' => '📝', 'label' => 'Confirmed'],
                                            'processing'       => ['icon' => '⚙️', 'label' => 'Processing & QC'],
                                            'packed'           => ['icon' => '📦', 'label' => 'Packed'],
                                            'shipped'          => ['icon' => '🚚', 'label' => 'Dispatched'],
                                            'out_for_delivery' => ['icon' => '🛵', 'label' => 'Out for Delivery'],
                                            'delivered'        => ['icon' => '🎉', 'label' => 'Delivered'],
                                        ];
                                        $pipeline_keys = array_keys($pipeline_steps);
                                        $current_idx   = array_search($milestone, $pipeline_keys);
                                        $is_terminal   = in_array($milestone, ['cancelled', 'refunded', 'delivered'], true);

                                        if (in_array($milestone, ['cancelled', 'refunded'], true)) :
                                            $badge_label = $milestone === 'cancelled' ? '❌ Cancelled' : '🔄 Refunded';
                                        ?>
                                            <span class="aavni-badge aavni-badge-<?php echo esc_attr($milestone); ?>" style="font-size:11px;"><?php echo esc_html($badge_label); ?></span>
                                        <?php else : ?>
                                            <!-- Mini step dots pipeline -->
                                            <div class="aavni-pipeline-row" data-order-id="<?php echo esc_attr($order_id); ?>" style="display:flex; align-items:center; gap:3px; flex-wrap:nowrap;">
                                            <?php foreach ($pipeline_steps as $step_key => $step_data) :
                                                $step_idx  = array_search($step_key, $pipeline_keys);
                                                $is_done   = ($current_idx !== false && $step_idx <= $current_idx);
                                                $is_active = ($step_key === $milestone);
                                                $dot_color = $is_done ? ($is_active ? '#A85A48' : '#C4956E') : '#D1C7BD';
                                                $dot_border= $is_active ? '2px solid #7B3A28' : '2px solid transparent';
                                                $cursor    = 'pointer';
                                            ?>
                                                <button type="button"
                                                    class="aavni-pipeline-step-btn"
                                                    data-order-id="<?php echo esc_attr($order_id); ?>"
                                                    data-milestone="<?php echo esc_attr($step_key); ?>"
                                                    title="<?php echo esc_attr(($is_active ? '✓ Current: ' : 'Set to: ') . $step_data['label']); ?>"
                                                    style="width:<?php echo $is_active ? '28px' : '20px'; ?>; height:<?php echo $is_active ? '28px' : '20px'; ?>; border-radius:50%; background:<?php echo $dot_color; ?>; border:<?php echo $dot_border; ?>; cursor:<?php echo $cursor; ?>; font-size:<?php echo $is_active ? '13px' : '10px'; ?>; display:inline-flex; align-items:center; justify-content:center; padding:0; transition:all 0.15s; flex-shrink:0; box-shadow:<?php echo $is_active ? '0 0 0 3px rgba(168,90,72,0.25)' : 'none'; ?>;">
                                                    <?php echo $is_active ? esc_html($step_data['icon']) : ''; ?>
                                                </button>
                                                <?php if ($step_key !== 'delivered') : ?>
                                                    <div style="flex:1; min-width:4px; max-width:8px; height:2px; background:<?php echo ($step_idx < $current_idx) ? '#C4956E' : '#E8E2D8'; ?>;"></div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                            </div>
                                            <!-- Current step label + advance button -->
                                            <div style="margin-top:5px; display:flex; align-items:center; gap:6px;">
                                                <span style="font-size:10.5px; font-weight:700; color:#A85A48;">
                                                    <?php
                                                    $cur_step_data = $pipeline_steps[$milestone] ?? null;
                                                    echo $cur_step_data ? esc_html($cur_step_data['icon'] . ' ' . $cur_step_data['label']) : esc_html(ucfirst($milestone));
                                                    ?>
                                                </span>
                                                <?php if ($milestone !== 'delivered') :
                                                    $next_idx = ($current_idx !== false) ? $current_idx + 1 : 1;
                                                    $next_key = isset($pipeline_keys[$next_idx]) ? $pipeline_keys[$next_idx] : null;
                                                    if ($next_key && isset($pipeline_steps[$next_key])) :
                                                ?>
                                                <button type="button"
                                                    class="aavni-pipeline-advance-btn"
                                                    data-order-id="<?php echo esc_attr($order_id); ?>"
                                                    data-milestone="<?php echo esc_attr($next_key); ?>"
                                                    title="Advance to: <?php echo esc_attr($pipeline_steps[$next_key]['label']); ?>"
                                                    style="font-size:10px; padding:2px 7px; background:#F3EFE9; border:1px solid #C4956E; border-radius:10px; color:#7B3A28; cursor:pointer; font-weight:700; white-space:nowrap; transition:all 0.15s;">
                                                    ▶ <?php echo esc_html($pipeline_steps[$next_key]['label']); ?>
                                                </button>
                                                <?php endif; endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        </td>

                                        <!-- Courier & AWB -->
                                        <td style="font-size:12px;">
                                            <?php if ($sr_awb || $awb || $courier) : ?>
                                                <strong><?php echo esc_html($sr_courier ?: ($courier ?: 'Standard Delivery')); ?></strong>
                                                <?php if ($sr_status) : ?>
                                                    <div><span class="sr-badge sr-badge-ready" style="font-size:10px;"><?php echo esc_html($sr_status); ?></span></div>
                                                <?php endif; ?>
                                                <?php if ($sr_awb || $awb) : ?>
                                                    <div>AWB: <code><?php echo esc_html($sr_awb ?: $awb); ?></code></div>
                                                <?php endif; ?>
                                                <div style="display:flex; gap:8px; align-items:center; margin-top:2px;">
                                                    <?php if ($tracking_url) : ?>
                                                        <a href="<?php echo esc_url($tracking_url); ?>" target="_blank" style="color:var(--aavni-admin-terracotta); font-size:11px;">Track ↗</a>
                                                    <?php endif; ?>
                                                    <?php if ($sr_label_url) : ?>
                                                        <a href="<?php echo esc_url($sr_label_url); ?>" target="_blank" style="color:#15803D; font-size:11px; font-weight:700;">Label 📄</a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else : ?>
                                                <span style="color:#999; font-style:italic;">Not assigned</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Quick Actions -->
                                        <td style="text-align:right; white-space:nowrap;">
                                            <!-- Order Details Trigger -->
                                            <button type="button"
                                                    class="aavni-btn aavni-btn-sm aavni-btn-secondary aavni-trigger-order-details"
                                                    data-order='<?php echo esc_attr(wp_json_encode($order_modal_payload)); ?>'
                                                    title="View Full Order Breakdown">
                                                📋 Details
                                            </button>

                                            <!-- Milestone Update Trigger -->
                                            <button type="button" 
                                                    class="aavni-btn aavni-btn-sm aavni-btn-primary aavni-trigger-milestone-modal"
                                                    data-order-id="<?php echo esc_attr($order_id); ?>"
                                                    data-order-number="<?php echo esc_attr($order_num); ?>"
                                                    data-current-milestone="<?php echo esc_attr($milestone); ?>"
                                                    data-current-status="<?php echo esc_attr($status); ?>"
                                                    data-courier="<?php echo esc_attr($courier); ?>"
                                                    data-awb="<?php echo esc_attr($awb); ?>"
                                                    data-tracking-url="<?php echo esc_attr($tracking_url); ?>">
                                                🚚 Milestone
                                            </button>

                                            <!-- Invoice Link -->
                                            <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="View & Print Tax Invoice">
                                                🧾
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="10" style="text-align:center; padding:36px; color:#888;">
                                        No orders matching the selected filter criteria.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1) : ?>
                    <div style="padding:16px 24px; display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--aavni-admin-border);">
                        <span style="font-size:13px; color:#888;">Page <?php echo esc_html($paged); ?> of <?php echo esc_html($total_pages); ?></span>
                        <div style="display:flex; gap:6px;">
                            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                <a href="<?php echo esc_url(add_query_arg('paged', $i)); ?>" class="aavni-btn aavni-btn-sm <?php echo $paged === $i ? 'aavni-btn-primary' : 'aavni-btn-secondary'; ?>">
                                    <?php echo esc_html($i); ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Full Order Details Modal -->
            <div id="aavniOrderDetailsModal" class="aavni-modal-overlay">
                <div class="aavni-modal-box" style="max-width:800px; max-height:90vh; overflow-y:auto;">
                    <div class="aavni-modal-header">
                        <h3 class="aavni-modal-title">📋 Order Breakdown <span id="detail_order_title" style="color:var(--aavni-admin-terracotta);"></span></h3>
                        <button type="button" class="aavni-modal-close" data-aavni-dismiss="modal">&times;</button>
                    </div>

                    <div class="aavni-modal-body" style="font-size:13px;">
                        <!-- Top KPI banner -->
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-bottom:18px;">
                            <div style="background:#FAF8F5; padding:12px; border-radius:6px; border:1px solid #EAE5DE;">
                                <div style="font-size:11px; color:#888; text-transform:uppercase; font-weight:700;">Status & Milestone</div>
                                <div style="margin-top:4px; font-weight:700;">
                                    <span id="detail_order_status_badge" class="aavni-badge"></span>
                                    <span id="detail_order_milestone_badge" class="aavni-badge" style="margin-left:4px;"></span>
                                </div>
                            </div>
                            <div style="background:#FAF8F5; padding:12px; border-radius:6px; border:1px solid #EAE5DE;">
                                <div style="font-size:11px; color:#888; text-transform:uppercase; font-weight:700;">Order Date</div>
                                <div id="detail_order_date" style="margin-top:4px; font-weight:600;"></div>
                            </div>
                            <div style="background:#FAF8F5; padding:12px; border-radius:6px; border:1px solid #EAE5DE;">
                                <div style="font-size:11px; color:#888; text-transform:uppercase; font-weight:700;">Payment Method</div>
                                <div id="detail_order_payment" style="margin-top:4px; font-weight:600;"></div>
                            </div>
                            <div style="background:#FAF8F5; padding:12px; border-radius:6px; border:1px solid #EAE5DE;">
                                <div style="font-size:11px; color:#888; text-transform:uppercase; font-weight:700;">Grand Total</div>
                                <div id="detail_order_total" style="margin-top:4px; font-weight:700; color:var(--aavni-admin-terracotta); font-size:15px;"></div>
                            </div>
                        </div>

                        <!-- Customer & Addresses -->
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px;">
                            <div style="background:var(--aavni-admin-bg); padding:14px; border-radius:8px; border:1px solid var(--aavni-admin-border);">
                                <strong style="font-size:13.5px; display:block; margin-bottom:6px;">👤 Customer & Billing</strong>
                                <div id="detail_customer_name" style="font-weight:600;"></div>
                                <div id="detail_customer_email" style="color:#555;"></div>
                                <div id="detail_customer_phone" style="color:#555;"></div>
                                <div style="margin-top:8px; font-size:12px; color:#777; line-height:1.4;" id="detail_billing_address"></div>
                            </div>
                            <div style="background:var(--aavni-admin-bg); padding:14px; border-radius:8px; border:1px solid var(--aavni-admin-border);">
                                <strong style="font-size:13.5px; display:block; margin-bottom:6px;">🚚 Shipping & Courier</strong>
                                <div style="margin-bottom:6px;"><strong>Carrier:</strong> <span id="detail_courier"></span></div>
                                <div style="margin-bottom:6px;"><strong>AWB:</strong> <code id="detail_awb"></code></div>
                                <div id="detail_tracking_link_wrap" style="margin-bottom:8px;"></div>
                                <div style="font-size:12px; color:#777; line-height:1.4;" id="detail_shipping_address"></div>
                            </div>
                        </div>

                        <!-- Itemized Products -->
                        <h4 style="margin:16px 0 8px; font-size:14px;">Purchased Items</h4>
                        <div class="aavni-table-wrap" style="margin-bottom:18px;">
                            <table class="aavni-admin-table" style="font-size:12.5px;">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th style="text-align:right;">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody id="detail_items_table_body"></tbody>
                            </table>
                        </div>

                        <!-- Financials Breakdown -->
                        <div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
                            <div style="width:280px; font-size:13px; line-height:1.8;">
                                <div style="display:flex; justify-content:space-between;">
                                    <span>Subtotal:</span>
                                    <strong id="detail_subtotal"></strong>
                                </div>
                                <div style="display:flex; justify-content:space-between;">
                                    <span>Shipping:</span>
                                    <span id="detail_shipping"></span>
                                </div>
                                <div style="display:flex; justify-content:space-between;">
                                    <span>GST / Tax:</span>
                                    <span id="detail_tax"></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; border-top:1px solid #ddd; padding-top:6px; margin-top:4px;">
                                    <strong style="font-size:14px;">Order Total:</strong>
                                    <strong id="detail_grand_total" style="font-size:15px; color:var(--aavni-admin-terracotta);"></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Shiprocket Logistics Orchestration Box -->
                        <?php if (class_exists('AAVNI_Shiprocket_Admin_UI')) : ?>
                            <?php AAVNI_Shiprocket_Admin_UI::render_order_details_shiprocket_box(); ?>
                        <?php endif; ?>

                        <!-- Notes & Timeline -->
                        <h4 style="margin:16px 0 8px; font-size:14px;">Order Notes & Communication History</h4>
                        <div id="detail_notes_list" style="background:#FAF8F5; border:1px solid #EAE5DE; border-radius:6px; padding:12px; max-height:180px; overflow-y:auto; margin-bottom:16px;"></div>

                        <!-- Add Note Form inside modal -->
                        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-orders')); ?>" style="background:var(--aavni-admin-bg); padding:12px; border-radius:6px; border:1px solid var(--aavni-admin-border);">
                            <?php wp_nonce_field('aavni_add_note_nonce'); ?>
                            <input type="hidden" name="aavni_action" value="add_order_note" />
                            <input type="hidden" name="order_id" id="detail_note_order_id" value="" />
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="text" name="note_content" placeholder="Add an internal note or customer comment..." required style="flex:1;" />
                                <label style="font-size:12px; white-space:nowrap;">
                                    <input type="checkbox" name="is_customer_note" value="1" /> Email Customer
                                </label>
                                <button type="submit" class="aavni-btn aavni-btn-sm aavni-btn-primary">Post Note</button>
                            </div>
                        </form>
                    </div>

                    <div class="aavni-modal-footer">
                        <a id="detail_print_invoice_btn" href="" target="_blank" class="aavni-btn aavni-btn-secondary">🧾 Print Tax Invoice</a>
                        <button type="button" class="aavni-btn aavni-btn-secondary" data-aavni-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>

            <!-- Milestone & Courier Quick Update Modal -->
            <div id="aavniMilestoneModal" class="aavni-modal-overlay">
                <div class="aavni-modal-box">
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-orders')); ?>">
                        <?php wp_nonce_field('aavni_update_milestone_nonce'); ?>
                        <input type="hidden" name="aavni_action" value="update_order_milestone" />
                        <input type="hidden" name="order_id" id="aavni_modal_order_id" value="" />

                        <div class="aavni-modal-header">
                            <h3 class="aavni-modal-title">Update Milestone for Order <span id="aavni_modal_order_title"></span></h3>
                            <button type="button" class="aavni-modal-close" data-aavni-dismiss="modal">&times;</button>
                        </div>

                        <div class="aavni-modal-body">
                            <p style="margin-top:0; font-size:13px; color:var(--aavni-admin-muted);">
                                Updating milestone updates customer's visual tracking timeline, sends an email notification, and updates the order status.
                            </p>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <div>
                                    <label for="aavni_modal_milestone"><strong>Shipment Milestone *:</strong></label><br>
                                    <select name="order_milestone" id="aavni_modal_milestone" style="width:100%; margin-top:6px;">
                                        <?php foreach ($milestones as $key => $m) : ?>
                                            <option value="<?php echo esc_attr($key); ?>">
                                                <?php echo esc_html($m['icon'] . ' ' . $m['label']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="cancelled">❌ Cancelled</option>
                                        <option value="refunded">🔄 Refunded</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="aavni_modal_order_status"><strong>WooCommerce Order Status:</strong></label><br>
                                    <select name="order_status" id="aavni_modal_order_status" style="width:100%; margin-top:6px;">
                                        <?php foreach ($all_statuses as $st_key => $st_name) :
                                            $clean_key = str_replace('wc-', '', $st_key);
                                        ?>
                                            <option value="<?php echo esc_attr($clean_key); ?>"><?php echo esc_html($st_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <p style="margin-top:14px;">
                                <label for="aavni_modal_courier"><strong>Courier Partner:</strong></label><br>
                                <input type="text" name="courier_name" id="aavni_modal_courier" placeholder="e.g. Delhivery / Blue Dart / Shiprocket" style="width:100%; margin-top:6px;" />
                                <span style="font-size:11px; color:#888; margin-top:3px; display:block;">💡 Auto-assigned: Delhivery Surface (you can change this)</span>
                            </p>

                            <p>
                                <label for="aavni_modal_awb"><strong>AWB / Tracking Number:</strong></label><br>
                                <div style="display:flex; gap:8px; margin-top:6px; align-items:center;">
                                    <input type="text" name="tracking_number" id="aavni_modal_awb" placeholder="e.g. DL1234567890IN" style="flex:1;" />
                                    <button type="button" id="aavni_regenerate_awb_btn" title="Generate a new AWB number" style="white-space:nowrap; padding:6px 10px; font-size:12px; background:#F3EFE9; border:1px solid #D1C7BD; border-radius:4px; cursor:pointer; color:#6B655C; font-weight:600;">🔄 New AWB</button>
                                </div>
                                <span style="font-size:11px; color:#888; margin-top:3px; display:block;">🤖 Auto-generated AWB (Delhivery format). Edit if you have a real tracking number.</span>
                            </p>

                            <p>
                                <label for="aavni_modal_tracking_url"><strong>Tracking Web Link:</strong></label><br>
                                <input type="url" name="tracking_url" id="aavni_modal_tracking_url" placeholder="https://track.courier.com/..." style="width:100%; margin-top:6px;" />
                            </p>

                            <p>
                                <label for="aavni_modal_note"><strong>Dispatch / Status Note (Optional):</strong></label><br>
                                <input type="text" name="milestone_note" id="aavni_modal_note" placeholder="e.g. Dispatched via express van from Jaipur central warehouse" style="width:100%; margin-top:6px;" />
                            </p>
                        </div>

                        <div class="aavni-modal-footer">
                            <button type="button" class="aavni-btn aavni-btn-secondary" data-aavni-dismiss="modal">Cancel</button>
                            <button type="submit" class="aavni-btn aavni-btn-primary">Update Milestone & Tracking</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
