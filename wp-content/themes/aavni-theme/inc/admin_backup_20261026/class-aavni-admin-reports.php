<?php
/**
 * AAVNI Sales Reports & Business Analytics Hub
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Reports {

    /**
     * Render Analytics & Reports Screen
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce') && !current_user_can('view_woocommerce_reports')) {
            wp_die(__('You do not have sufficient permissions to view business reports.', 'aavni'));
        }

        $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'all';

        $query_args = [
            'limit'  => -1,
            'return' => 'objects',
        ];

        if ($period === 'last_7_days') {
            $query_args['date_created'] = '>' . (time() - 7 * DAY_IN_SECONDS);
        } elseif ($period === 'last_30_days') {
            $query_args['date_created'] = '>' . (time() - 30 * DAY_IN_SECONDS);
        } elseif ($period === 'this_month') {
            $query_args['date_created'] = '>' . strtotime('first day of this month 00:00:00');
        }

        $orders = wc_get_orders($query_args);

        // Calculate Analytics
        $total_orders = count($orders);
        $completed_orders = 0;
        $cancelled_orders = 0;
        $gross_sales = 0.0;
        $total_tax = 0.0;
        $total_shipping = 0.0;
        $total_refunds = 0.0;
        $total_items_sold = 0;

        $payment_split = ['cod' => 0.0, 'other' => 0.0];
        $payment_counts = ['cod' => 0, 'other' => 0];
        $category_revenue = [];
        $product_sales = [];

        foreach ($orders as $o) {
            $st = $o->get_status();
            $total = (float) $o->get_total();
            $refunded = (float) $o->get_total_refunded();
            $method = $o->get_payment_method();

            if ($st === 'cancelled') {
                $cancelled_orders++;
                continue;
            }

            if (in_array($st, ['processing', 'completed', 'on-hold'], true)) {
                $completed_orders++;
                $gross_sales += $total;
                $total_tax += (float) $o->get_total_tax();
                $total_shipping += (float) $o->get_shipping_total();
                $total_refunds += $refunded;

                // Payment split
                if ($method === 'cod') {
                    $payment_split['cod'] += $total;
                    $payment_counts['cod']++;
                } else {
                    $payment_split['other'] += $total;
                    $payment_counts['other']++;
                }

                // Line items
                foreach ($o->get_items() as $item) {
                    $qty = $item->get_quantity();
                    $line_total = (float) $item->get_total();
                    $total_items_sold += $qty;

                    $prod = $item->get_product();
                    if ($prod) {
                        $pname = $prod->get_name();
                        if (!isset($product_sales[$pname])) {
                            $product_sales[$pname] = ['qty' => 0, 'revenue' => 0.0];
                        }
                        $product_sales[$pname]['qty'] += $qty;
                        $product_sales[$pname]['revenue'] += $line_total;

                        // Categories
                        $cat_ids = $prod->get_category_ids();
                        foreach ($cat_ids as $cid) {
                            $term = get_term($cid, 'product_cat');
                            if ($term && !is_wp_error($term)) {
                                if (!isset($category_revenue[$term->name])) {
                                    $category_revenue[$term->name] = 0.0;
                                }
                                $category_revenue[$term->name] += $line_total;
                            }
                        }
                    }
                }
            }
        }

        $net_sales = max(0, $gross_sales - $total_refunds);
        $aov = $completed_orders > 0 ? ($gross_sales / $completed_orders) : 0.0;

        // Sort Top Products
        uasort($product_sales, function($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        $top_products = array_slice($product_sales, 0, 5, true);

        // Sort Top Categories
        arsort($category_revenue);
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">📈</div>
                    <div>
                        <h1 class="aavni-header-title">Sales Reports & Business Analytics</h1>
                        <p class="aavni-header-subtitle">Comprehensive performance metrics, revenue by category, payment method breakdown & AOV</p>
                    </div>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('reports'); ?>

            <!-- Date Period Filter Bar -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aavni-filter-bar">
                <input type="hidden" name="page" value="aavni-reports" />

                <div class="aavni-filter-group">
                    <span style="font-weight:700; font-size:13px;">Date Period:</span>
                    <select name="period" onchange="this.form.submit()">
                        <option value="all" <?php selected($period, 'all'); ?>>All Time Recorded</option>
                        <option value="last_7_days" <?php selected($period, 'last_7_days'); ?>>Last 7 Days</option>
                        <option value="last_30_days" <?php selected($period, 'last_30_days'); ?>>Last 30 Days</option>
                        <option value="this_month" <?php selected($period, 'this_month'); ?>>This Month</option>
                    </select>
                </div>
            </form>

            <!-- Revenue Metrics Grid -->
            <div class="aavni-kpi-grid">
                <div class="aavni-kpi-card is-accent-gold">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Gross Revenue</span>
                        <div class="aavni-kpi-icon">💰</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo wc_price($gross_sales); ?></div>
                    <div class="aavni-kpi-meta">All confirmed sales</div>
                </div>

                <div class="aavni-kpi-card is-accent-terracotta">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Net Sales</span>
                        <div class="aavni-kpi-icon">✨</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo wc_price($net_sales); ?></div>
                    <div class="aavni-kpi-meta">Gross minus refunds</div>
                </div>

                <div class="aavni-kpi-card is-accent-success">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Average Order Value (AOV)</span>
                        <div class="aavni-kpi-icon">🎯</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo wc_price($aov); ?></div>
                    <div class="aavni-kpi-meta">Per customer checkout</div>
                </div>

                <div class="aavni-kpi-card">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Items Sold</span>
                        <div class="aavni-kpi-icon">📦</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($total_items_sold); ?></div>
                    <div class="aavni-kpi-meta"><?php echo esc_html($completed_orders); ?> fulfilled orders</div>
                </div>
            </div>

            <!-- 2-Column Breakdown -->
            <div class="aavni-grid-2">
                <!-- Payment Method Split -->
                <div class="aavni-admin-card">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">💳 Payment Method Distribution</h3>
                    </div>
                    <div class="aavni-card-body">
                        <div style="margin-bottom:18px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-weight:600;">
                                <span>Cash on Delivery (COD)</span>
                                <span><?php echo wc_price($payment_split['cod']); ?> (<?php echo esc_html($payment_counts['cod']); ?> orders)</span>
                            </div>
                            <div style="background:#eee; height:10px; border-radius:5px; overflow:hidden;">
                                <?php
                                $cod_pct = $gross_sales > 0 ? round(($payment_split['cod'] / $gross_sales) * 100) : 100;
                                ?>
                                <div style="background:var(--aavni-admin-terracotta); width:<?php echo esc_attr($cod_pct); ?>%; height:100%;"></div>
                            </div>
                            <div style="font-size:12px; color:#888; margin-top:4px;"><?php echo esc_html($cod_pct); ?>% of total revenue</div>
                        </div>

                        <div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-weight:600;">
                                <span>Online / Prepaid (Razorpay)</span>
                                <span><?php echo wc_price($payment_split['other']); ?> (<?php echo esc_html($payment_counts['other']); ?> orders)</span>
                            </div>
                            <div style="background:#eee; height:10px; border-radius:5px; overflow:hidden;">
                                <?php $online_pct = 100 - $cod_pct; ?>
                                <div style="background:var(--aavni-admin-gold); width:<?php echo esc_attr($online_pct); ?>%; height:100%;"></div>
                            </div>
                            <div style="font-size:12px; color:#888; margin-top:4px;">
                                <?php echo esc_html($online_pct); ?>% of total revenue &bull; <em>Online payments currently ON HOLD</em>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Performance -->
                <div class="aavni-admin-card">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">👗 Revenue by Category</h3>
                    </div>
                    <div class="aavni-table-wrap">
                        <table class="aavni-admin-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th style="text-align:right;">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($category_revenue)) : ?>
                                    <?php foreach ($category_revenue as $cname => $rev) : ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($cname); ?></strong></td>
                                            <td style="text-align:right; font-weight:700; color:var(--aavni-admin-terracotta);">
                                                <?php echo wc_price($rev); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="2" style="text-align:center; color:#888; padding:20px;">
                                            No categorized revenue yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Selling Products Table -->
            <div class="aavni-admin-card">
                <div class="aavni-card-header">
                    <h3 class="aavni-card-title">🏆 Top Selling Products</h3>
                </div>
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Units Sold</th>
                                <th style="text-align:right;">Total Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($top_products)) : ?>
                                <?php foreach ($top_products as $pname => $data) : ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($pname); ?></strong></td>
                                        <td><span><?php echo esc_html($data['qty']); ?> units</span></td>
                                        <td style="text-align:right; font-weight:700; color:var(--aavni-admin-dark);">
                                            <?php echo wc_price($data['revenue']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; color:#888; padding:30px;">
                                        No sales records available yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
