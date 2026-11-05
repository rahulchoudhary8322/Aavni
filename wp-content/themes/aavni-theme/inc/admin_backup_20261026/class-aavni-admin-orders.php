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
     * Handle Order Actions (Milestone update, Note addition, Cancellation)
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

            $old_milestone = $order->get_meta('_aavni_order_milestone') ?: 'confirmed';

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

            // Native Order Status synchronization
            if ($new_milestone === 'delivered' && $order->get_status() !== 'completed') {
                $order->update_status('completed', __('Package marked as Delivered in AAVNI Fulfilment Hub.', 'aavni'));
            } elseif ($new_milestone === 'cancelled' && $order->get_status() !== 'cancelled') {
                $order->update_status('cancelled', __('Order cancelled in AAVNI Fulfilment Hub.', 'aavni'));
            }

            $order->save();

            // Add note to order
            $order->add_order_note(sprintf(__('Shipment milestone updated to: %s. %s', 'aavni'), $title, $note_text), false);

            wp_safe_redirect(add_query_arg(['page' => 'aavni-orders', 'updated' => 'milestone'], admin_url('admin.php')));
            exit;
        }

        // 2. Add Order Note
        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'add_order_note') {
            check_admin_referer('aavni_add_note_nonce');

            $order_id = absint($_POST['order_id']);
            $order = wc_get_order($order_id);
            if (!$order) return;

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
     * Render Orders & Fulfilment Hub Screen
     */
    public static function render() {
        if (!current_user_can('edit_shop_orders') && !current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to manage store orders.', 'aavni'));
        }

        self::handle_actions();

        // Filters
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $milestone_filter = isset($_GET['milestone']) ? sanitize_text_field($_GET['milestone']) : '';
        $payment_filter = isset($_GET['payment']) ? sanitize_text_field($_GET['payment']) : '';
        $search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $limit = 15;

        // Query args
        $query_args = [
            'limit'    => $limit,
            'page'     => $paged,
            'paginate' => true,
            'orderby'  => 'date',
            'order'    => 'DESC',
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

                <div class="aavni-filter-group">
                    <!-- Search Input -->
                    <input type="search" name="search" value="<?php echo esc_attr($search_query); ?>" placeholder="Search Order # or Customer..." style="width:230px;" />

                    <!-- Status Filter -->
                    <select name="status">
                        <option value="all"><?php esc_html_e('All Order Statuses', 'aavni'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending Payment</option>
                        <option value="processing" <?php selected($status_filter, 'processing'); ?>>Processing</option>
                        <option value="on-hold" <?php selected($status_filter, 'on-hold'); ?>>On Hold</option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                        <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
                        <option value="refunded" <?php selected($status_filter, 'refunded'); ?>>Refunded</option>
                    </select>

                    <!-- Milestone Filter -->
                    <select name="milestone">
                        <option value="all"><?php esc_html_e('All Shipment Milestones', 'aavni'); ?></option>
                        <?php foreach ($milestones as $key => $m) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($milestone_filter, $key); ?>>
                                <?php echo esc_html($m['icon'] . ' ' . $m['label']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="cancelled" <?php selected($milestone_filter, 'cancelled'); ?>>❌ Cancelled</option>
                    </select>

                    <!-- Payment Method Filter -->
                    <select name="payment">
                        <option value="all"><?php esc_html_e('All Payment Methods', 'aavni'); ?></option>
                        <option value="cod" <?php selected($payment_filter, 'cod'); ?>>Cash on Delivery (COD)</option>
                        <option value="razorpay" <?php selected($payment_filter, 'razorpay'); ?>>Razorpay Online</option>
                    </select>

                    <button type="submit" class="aavni-btn aavni-btn-primary aavni-btn-sm">Filter</button>
                    <?php if ($status_filter || $milestone_filter || $payment_filter || $search_query) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders')); ?>" class="aavni-btn aavni-btn-secondary aavni-btn-sm">Reset</a>
                    <?php endif; ?>
                </div>

                <div style="font-size:13px; color:var(--aavni-admin-muted);">
                    Showing <strong><?php echo esc_html(count($orders)); ?></strong> of <strong><?php echo esc_html($total_orders); ?></strong> orders
                </div>
            </form>

            <!-- Orders Table -->
            <div class="aavni-admin-card">
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th style="width:110px;">Order #</th>
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
                                    $order_id = $order->get_id();
                                    $order_num = $order->get_order_number();
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

                                    $invoice_url = wp_nonce_url(add_query_arg([
                                        'aavni_action' => 'print_invoice',
                                        'order_id'     => $order_id,
                                    ], home_url('/')), 'aavni_invoice_' . $order_id);
                                ?>
                                    <tr>
                                        <!-- Order Number -->
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" style="font-weight:700; color:var(--aavni-admin-terracotta); text-decoration:none;">
                                                #<?php echo esc_html($order_num); ?>
                                            </a>
                                        </td>

                                        <!-- Date -->
                                        <td style="font-size:12px; white-space:nowrap;">
                                            <div><?php echo esc_html($order->get_date_created()->date('d M Y')); ?></div>
                                            <div style="color:#888; font-size:11px;"><?php echo esc_html($order->get_date_created()->date('h:i A')); ?></div>
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

                                        <!-- Shipment Milestone -->
                                        <td>
                                            <span class="aavni-badge aavni-badge-<?php echo esc_attr($milestone); ?>">
                                                <?php
                                                $label = isset($milestones[$milestone]) ? $milestones[$milestone]['label'] : ucfirst($milestone);
                                                echo esc_html($label);
                                                ?>
                                            </span>
                                        </td>

                                        <!-- Courier & AWB -->
                                        <td style="font-size:12px;">
                                            <?php if ($courier || $awb) : ?>
                                                <strong><?php echo esc_html($courier ?: 'Standard Delivery'); ?></strong>
                                                <?php if ($awb) : ?>
                                                    <div>AWB: <code><?php echo esc_html($awb); ?></code></div>
                                                <?php endif; ?>
                                                <?php if ($tracking_url) : ?>
                                                    <div><a href="<?php echo esc_url($tracking_url); ?>" target="_blank" style="color:var(--aavni-admin-terracotta); font-size:11px;">Track ↗</a></div>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                <span style="color:#999; font-style:italic;">Not assigned</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Quick Actions -->
                                        <td style="text-align:right; white-space:nowrap;">
                                            <!-- Milestone Update Trigger -->
                                            <button type="button" 
                                                    class="aavni-btn aavni-btn-sm aavni-btn-primary aavni-trigger-milestone-modal"
                                                    data-order-id="<?php echo esc_attr($order_id); ?>"
                                                    data-order-number="<?php echo esc_attr($order_num); ?>"
                                                    data-current-milestone="<?php echo esc_attr($milestone); ?>"
                                                    data-courier="<?php echo esc_attr($courier); ?>"
                                                    data-awb="<?php echo esc_attr($awb); ?>"
                                                    data-tracking-url="<?php echo esc_attr($tracking_url); ?>">
                                                🚚 Milestone
                                            </button>

                                            <!-- Invoice Link -->
                                            <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="View & Print Tax Invoice">
                                                🧾
                                            </a>

                                            <!-- WooCommerce Edit Link -->
                                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Open in WooCommerce">
                                                ✏️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="9" style="text-align:center; padding:36px; color:#888;">
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
                                Updating milestone updates customer's visual tracking timeline, records an audit log entry, and notifies customer tracking.
                            </p>

                            <p>
                                <label for="aavni_modal_milestone"><strong>Shipment Milestone:</strong></label><br>
                                <select name="order_milestone" id="aavni_modal_milestone" style="width:100%; margin-top:6px;">
                                    <?php foreach ($milestones as $key => $m) : ?>
                                        <option value="<?php echo esc_attr($key); ?>">
                                            <?php echo esc_html($m['icon'] . ' ' . $m['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="cancelled">❌ Cancelled</option>
                                    <option value="refunded">🔄 Refunded</option>
                                </select>
                            </p>

                            <p>
                                <label for="aavni_modal_courier"><strong>Courier Partner:</strong></label><br>
                                <input type="text" name="courier_name" id="aavni_modal_courier" placeholder="e.g. Delhivery / Blue Dart / Shiprocket" style="width:100%; margin-top:6px;" />
                            </p>

                            <p>
                                <label for="aavni_modal_awb"><strong>AWB / Tracking Number:</strong></label><br>
                                <input type="text" name="tracking_number" id="aavni_modal_awb" placeholder="e.g. 142358962" style="width:100%; margin-top:6px;" />
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
