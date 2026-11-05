<?php
/**
 * AAVNI Executive Admin Dashboard
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Dashboard {

    /**
     * Render Executive Dashboard Screen
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_shop_orders')) {
            wp_die(__('You do not have sufficient permissions to access the AAVNI Store Manager.', 'aavni'));
        }

        global $wpdb;
        $returns_table = $wpdb->prefix . 'aavni_returns';
        $tickets_table = $wpdb->prefix . 'aavni_support_tickets';

        // 1. Fetch Real Order Statistics using WooCommerce HPOS / Native APIs
        $all_orders = wc_get_orders([
            'limit' => -1,
            'return' => 'objects',
        ]);

        $total_orders_count = count($all_orders);
        $today_start = strtotime('today midnight');
        $today_orders_count = 0;
        $awaiting_processing_count = 0;
        $in_fulfilment_count = 0;
        $delivered_count = 0;
        $cancelled_count = 0;
        $gross_sales = 0.0;
        $total_refunds = 0.0;

        // Daily trend data calculation (last 7 days)
        $daily_stats = [];
        for ($i = 6; $i >= 0; $i--) {
            $day_timestamp = strtotime("-{$i} days");
            $day_key = date('Y-m-d', $day_timestamp);
            $day_label = date('D, j M', $day_timestamp);
            $daily_stats[$day_key] = [
                'date'    => $day_label,
                'revenue' => 0.0,
                'orders'  => 0,
            ];
        }

        foreach ($all_orders as $order) {
            $status = $order->get_status();
            $date_created = $order->get_date_created();
            $created_ts = $date_created ? $date_created->getTimestamp() : 0;
            $order_total = (float) $order->get_total();
            $order_refunded = (float) $order->get_total_refunded();
            $milestone = $order->get_meta('_aavni_order_milestone');

            // Today's orders
            if ($created_ts >= $today_start) {
                $today_orders_count++;
            }

            // Status buckets
            if (in_array($status, ['processing', 'pending', 'on-hold'], true)) {
                $awaiting_processing_count++;
            }

            if (in_array($milestone, ['packed', 'shipped', 'out_for_delivery'], true)) {
                $in_fulfilment_count++;
            }

            if ($milestone === 'delivered' || $status === 'completed') {
                $delivered_count++;
            }

            if ($status === 'cancelled' || $milestone === 'cancelled') {
                $cancelled_count++;
            }

            // Sales calculations (completed + processing contribute to sales)
            if (in_array($status, ['processing', 'completed'], true)) {
                $gross_sales += $order_total;
                $total_refunds += $order_refunded;

                $day_key = date('Y-m-d', $created_ts);
                if (isset($daily_stats[$day_key])) {
                    $daily_stats[$day_key]['revenue'] += $order_total;
                    $daily_stats[$day_key]['orders'] += 1;
                }
            }
        }

        $net_sales = max(0, $gross_sales - $total_refunds);

        // 2. Inventory Metrics
        $products = wc_get_products(['limit' => -1]);
        $low_stock_count = 0;
        $out_of_stock_count = 0;

        foreach ($products as $p) {
            if ($p->is_type('variable')) {
                $children = $p->get_children();
                foreach ($children as $child_id) {
                    $child = wc_get_product($child_id);
                    if ($child && $child->managing_stock()) {
                        $qty = $child->get_stock_quantity();
                        if ($qty <= 0 || $child->get_stock_status() === 'outofstock') {
                            $out_of_stock_count++;
                        } elseif ($qty <= 5) {
                            $low_stock_count++;
                        }
                    }
                }
            } else {
                if ($p->managing_stock()) {
                    $qty = $p->get_stock_quantity();
                    if ($qty <= 0 || $p->get_stock_status() === 'outofstock') {
                        $out_of_stock_count++;
                    } elseif ($qty <= 5) {
                        $low_stock_count++;
                    }
                } elseif ($p->get_stock_status() === 'outofstock') {
                    $out_of_stock_count++;
                }
            }
        }

        // 3. Returns & Tickets Metrics
        $pending_returns = (int) $wpdb->get_var("SELECT COUNT(*) FROM $returns_table WHERE status IN ('submitted', 'under_review')");
        $open_tickets = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tickets_table WHERE status IN ('open', 'in_progress')");
        $high_priority_tickets = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tickets_table WHERE status IN ('open', 'in_progress') AND priority = 'high'");

        // 4. Recent Orders (Top 6)
        $recent_orders = array_slice($all_orders, 0, 6);

        // 5. Recent Customers
        $recent_customers = get_users([
            'role'    => 'customer',
            'number'  => 5,
            'orderby' => 'registered',
            'order'   => 'DESC',
        ]);

        // Output chart data into JS
        $chart_json = wp_json_encode(array_values($daily_stats));
        ?>
        <script>
            window.aavniSalesChartData = <?php echo $chart_json ?: '[]'; ?>;
        </script>

        <div class="wrap aavni-admin-wrap">
            <!-- Header Banner -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">✦</div>
                    <div>
                        <h1 class="aavni-header-title">AAVNI E-Commerce Executive Dashboard</h1>
                        <p class="aavni-header-subtitle">Real-time store performance, fulfilment metrics & customer service control center</p>
                    </div>
                </div>
                <div class="aavni-header-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders')); ?>" class="aavni-btn aavni-btn-gold">
                        📦 View Orders (<?php echo esc_html($total_orders_count); ?>)
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-products&action=new')); ?>" class="aavni-btn aavni-btn-primary">
                        + Add New Product
                    </a>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('dashboard'); ?>

            <!-- Primary KPI Metric Cards -->
            <div class="aavni-kpi-grid">
                <!-- Gross Sales -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-reports')); ?>" class="aavni-kpi-card is-accent-gold">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label"><?php esc_html_e('Gross Revenue', 'aavni'); ?></span>
                        <div class="aavni-kpi-icon">💰</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo wc_price($gross_sales); ?></div>
                    <div class="aavni-kpi-meta">
                        <span>Net: <?php echo wc_price($net_sales); ?></span>
                        <?php if ($total_refunds > 0) : ?>
                            <span style="color:#EF4444;">(Refunds: <?php echo wc_price($total_refunds); ?>)</span>
                        <?php endif; ?>
                    </div>
                </a>

                <!-- Today's Orders -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders')); ?>" class="aavni-kpi-card is-accent-terracotta">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label"><?php esc_html_e('Today\'s Orders', 'aavni'); ?></span>
                        <div class="aavni-kpi-icon">🛍️</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($today_orders_count); ?></div>
                    <div class="aavni-kpi-meta">
                        <span>Total orders: <?php echo esc_html($total_orders_count); ?></span>
                    </div>
                </a>

                <!-- Awaiting Processing -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders&status=processing')); ?>" class="aavni-kpi-card is-accent-warning">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label"><?php esc_html_e('Needs Processing', 'aavni'); ?></span>
                        <div class="aavni-kpi-icon">⚙️</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($awaiting_processing_count); ?></div>
                    <div class="aavni-kpi-meta">
                        <span>Pending packaging & QC</span>
                    </div>
                </a>

                <!-- In Transit / Fulfilment -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders&milestone=shipped')); ?>" class="aavni-kpi-card is-accent-terracotta">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label"><?php esc_html_e('In Fulfilment', 'aavni'); ?></span>
                        <div class="aavni-kpi-icon">🚚</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($in_fulfilment_count); ?></div>
                    <div class="aavni-kpi-meta">
                        <span>Packed / In Transit</span>
                    </div>
                </a>

                <!-- Delivered Orders -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders&status=completed')); ?>" class="aavni-kpi-card is-accent-success">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label"><?php esc_html_e('Delivered', 'aavni'); ?></span>
                        <div class="aavni-kpi-icon">🎉</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($delivered_count); ?></div>
                    <div class="aavni-kpi-meta">
                        <span>Successfully fulfilled</span>
                    </div>
                </a>

                <!-- Pending Returns -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-returns&status=submitted')); ?>" class="aavni-kpi-card <?php echo $pending_returns > 0 ? 'is-accent-danger' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label"><?php esc_html_e('Return Requests', 'aavni'); ?></span>
                        <div class="aavni-kpi-icon">🔄</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($pending_returns); ?></div>
                    <div class="aavni-kpi-meta">
                        <span>Awaiting staff decision</span>
                    </div>
                </a>

                <!-- Open Support Tickets -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-support-tickets&status=open')); ?>" class="aavni-kpi-card <?php echo $high_priority_tickets > 0 ? 'is-accent-danger' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label"><?php esc_html_e('Support Tickets', 'aavni'); ?></span>
                        <div class="aavni-kpi-icon">💬</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($open_tickets); ?></div>
                    <div class="aavni-kpi-meta">
                        <?php if ($high_priority_tickets > 0) : ?>
                            <strong style="color:#EF4444;"><?php echo esc_html($high_priority_tickets); ?> high priority</strong>
                        <?php else : ?>
                            <span>Customer inquiries</span>
                        <?php endif; ?>
                    </div>
                </a>

                <!-- Inventory Alerts -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-inventory')); ?>" class="aavni-kpi-card <?php echo ($low_stock_count + $out_of_stock_count > 0) ? 'is-accent-warning' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label"><?php esc_html_e('Stock Alerts', 'aavni'); ?></span>
                        <div class="aavni-kpi-icon">⚠️</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($low_stock_count + $out_of_stock_count); ?></div>
                    <div class="aavni-kpi-meta">
                        <span><?php echo esc_html($low_stock_count); ?> low stock, <?php echo esc_html($out_of_stock_count); ?> out</span>
                    </div>
                </a>
            </div>

            <!-- Sales Trend Chart Section -->
            <div class="aavni-admin-card">
                <div class="aavni-card-header">
                    <div>
                        <h3 class="aavni-card-title">📈 7-Day Sales Trend & Order Velocity</h3>
                        <div class="aavni-card-subtitle">Real database-backed sales totals from confirmed customer orders</div>
                    </div>
                    <div>
                        <span class="aavni-badge aavni-badge-confirmed">Last 7 Days</span>
                    </div>
                </div>
                <div class="aavni-card-body">
                    <div class="aavni-chart-container">
                        <canvas id="aavniSalesTrendCanvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- 2-Column Section: Recent Orders & Customer Overview -->
            <div class="aavni-grid-2">
                <!-- Recent Orders -->
                <div class="aavni-admin-card">
                    <div class="aavni-card-header">
                        <div>
                            <h3 class="aavni-card-title">🛍️ Recent Store Orders</h3>
                            <div class="aavni-card-subtitle">Latest customer purchases awaiting or under fulfilment</div>
                        </div>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                            View All &rarr;
                        </a>
                    </div>
                    <div class="aavni-table-wrap">
                        <table class="aavni-admin-table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Milestone</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_orders)) : ?>
                                    <?php foreach ($recent_orders as $ord) :
                                        $cust_id = $ord->get_customer_id();
                                        $cust_name = $ord->get_formatted_billing_full_name() ?: ($cust_id ? get_user_by('id', $cust_id)->display_name : 'Guest');
                                        $milestone = aavni_get_order_current_milestone($ord);
                                        $order_status = $ord->get_status();
                                        $invoice_url = wp_nonce_url(add_query_arg([
                                            'aavni_action' => 'print_invoice',
                                            'order_id'     => $ord->get_id(),
                                        ], home_url('/')), 'aavni_invoice_' . $ord->get_id());
                                    ?>
                                        <tr>
                                            <td>
                                                <strong>
                                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $ord->get_id() . '&action=edit')); ?>" style="color:var(--aavni-admin-terracotta); text-decoration:none;">
                                                        #<?php echo esc_html($ord->get_order_number()); ?>
                                                    </a>
                                                </strong>
                                                <div style="font-size:11.5px; color:#888;"><?php echo esc_html($ord->get_date_created()->date('d M, h:i A')); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-weight:600;"><?php echo esc_html($cust_name); ?></div>
                                                <div style="font-size:11.5px; color:#888;"><?php echo esc_html($ord->get_billing_phone() ?: $ord->get_billing_email()); ?></div>
                                            </td>
                                            <td>
                                                <strong style="color:var(--aavni-admin-dark);"><?php echo wc_price($ord->get_total(), ['currency' => $ord->get_currency()]); ?></strong>
                                                <div style="font-size:11px; color:#888;"><?php echo esc_html($ord->get_payment_method_title()); ?></div>
                                            </td>
                                            <td>
                                                <span class="aavni-badge aavni-badge-<?php echo esc_attr($milestone); ?>">
                                                    <?php echo esc_html(str_replace('_', ' ', $milestone)); ?>
                                                </span>
                                            </td>
                                            <td style="text-align:right; white-space:nowrap;">
                                                <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Print Tax Invoice">
                                                    🧾
                                                </a>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders&search=' . $ord->get_id())); ?>" class="aavni-btn aavni-btn-sm aavni-btn-primary">
                                                    Manage
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:30px; color:#888;">
                                            No orders placed yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Customers & Best Sellers -->
                <div>
                    <!-- Recent Customer Registrations -->
                    <div class="aavni-admin-card" style="margin-bottom:24px;">
                        <div class="aavni-card-header">
                            <div>
                                <h3 class="aavni-card-title">👥 Recent Customer Registrations</h3>
                                <div class="aavni-card-subtitle">Verified users joined AAVNI Privileged Customer club</div>
                            </div>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-customers')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                                View Directory &rarr;
                            </a>
                        </div>
                        <div class="aavni-table-wrap">
                            <table class="aavni-admin-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Email / Mobile</th>
                                        <th>Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_customers)) : ?>
                                        <?php foreach ($recent_customers as $cust) :
                                            $phone = get_user_meta($cust->ID, 'billing_phone', true);
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc_html($cust->display_name); ?></strong>
                                                    <div style="font-size:11px; color:#888;">@<?php echo esc_html($cust->user_login); ?></div>
                                                </td>
                                                <td>
                                                    <div><?php echo esc_html($cust->user_email); ?></div>
                                                    <?php if ($phone) : ?>
                                                        <div style="font-size:11.5px; color:#6B655C;">📞 <?php echo esc_html($phone); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="font-size:12px; color:#888;">
                                                    <?php echo esc_html(date_i18n('d M Y', strtotime($cust->user_registered))); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="3" style="text-align:center; color:#888; padding:20px;">
                                                No registered customers found.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Store Actions Strip -->
                    <div class="aavni-admin-card">
                        <div class="aavni-card-header">
                            <h3 class="aavni-card-title">⚡ Quick Store Actions</h3>
                        </div>
                        <div class="aavni-card-body" style="display:flex; flex-wrap:wrap; gap:10px;">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-orders&milestone=confirmed')); ?>" class="aavni-btn aavni-btn-secondary">
                                📦 Confirm Packings
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-returns')); ?>" class="aavni-btn aavni-btn-secondary">
                                🔄 Review Returns (<?php echo esc_html($pending_returns); ?>)
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-support-tickets')); ?>" class="aavni-btn aavni-btn-secondary">
                                💬 Reply Tickets (<?php echo esc_html($open_tickets); ?>)
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-inventory&filter=low_stock')); ?>" class="aavni-btn aavni-btn-secondary">
                                ⚠️ Restock Alerts (<?php echo esc_html($low_stock_count); ?>)
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-coupons')); ?>" class="aavni-btn aavni-btn-secondary">
                                🏷️ Create Promo Coupon
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
