<?php
/**
 * AAVNI Customer Management Directory
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Customers {

    /**
     * Render Customer Directory Screen
     */
    public static function render() {
        if (!current_user_can('list_users') && !current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to view the customer directory.', 'aavni'));
        }

        global $wpdb;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $view_user_id = isset($_GET['customer_id']) ? absint($_GET['customer_id']) : 0;
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $limit = 15;
        $offset = ($paged - 1) * $limit;

        // Query customers
        $user_query_args = [
            'role'   => 'customer',
            'number' => $limit,
            'offset' => $offset,
            'count_total' => true,
            'orderby' => 'registered',
            'order'   => 'DESC',
        ];

        if (!empty($search)) {
            $user_query_args['search'] = '*' . $search . '*';
            $user_query_args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $user_query = new WP_User_Query($user_query_args);
        $customers = $user_query->get_results();
        $total_customers = $user_query->get_total();
        $total_pages = ceil($total_customers / $limit);

        // If inspecting a specific customer
        $inspected_customer = null;
        $customer_orders = [];
        $customer_returns = [];
        $customer_tickets = [];

        if ($view_user_id > 0) {
            $inspected_customer = get_user_by('id', $view_user_id);
            if ($inspected_customer) {
                $customer_orders = wc_get_orders(['customer' => $view_user_id, 'limit' => 10]);
                $customer_returns = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aavni_returns WHERE customer_id = %d ORDER BY id DESC LIMIT 10", $view_user_id));
                $customer_tickets = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aavni_support_tickets WHERE customer_id = %d ORDER BY id DESC LIMIT 10", $view_user_id));
            }
        }
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">👥</div>
                    <div>
                        <h1 class="aavni-header-title">Customer Management Directory</h1>
                        <p class="aavni-header-subtitle">Registered customer profiles, lifetime order history, saved addresses & support activity</p>
                    </div>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('customers'); ?>

            <!-- Customer Detail Drawer / Modal if requested -->
            <?php if ($inspected_customer) :
                $first_name = get_user_meta($view_user_id, 'first_name', true) ?: $inspected_customer->display_name;
                $last_name = get_user_meta($view_user_id, 'last_name', true);
                $phone = get_user_meta($view_user_id, 'billing_phone', true);
                $billing_address = [
                    get_user_meta($view_user_id, 'billing_address_1', true),
                    get_user_meta($view_user_id, 'billing_city', true),
                    get_user_meta($view_user_id, 'billing_state', true),
                    get_user_meta($view_user_id, 'billing_postcode', true),
                ];
                $billing_str = implode(', ', array_filter($billing_address));

                // Calculate LTV
                $ltv = 0.0;
                foreach ($customer_orders as $co) {
                    if (in_array($co->get_status(), ['completed', 'processing'], true)) {
                        $ltv += (float) $co->get_total();
                    }
                }
            ?>
                <div class="aavni-admin-card" style="border:2px solid var(--aavni-admin-gold); margin-bottom:28px;">
                    <div class="aavni-card-header" style="background:#FAF8F5;">
                        <div>
                            <h3 class="aavni-card-title">
                                👤 Customer Profile: <?php echo esc_html($inspected_customer->display_name); ?>
                            </h3>
                            <div class="aavni-card-subtitle">
                                Registered: <?php echo esc_html(date_i18n('d M Y, h:i A', strtotime($inspected_customer->user_registered))); ?> &bull; User ID: #<?php echo esc_html($view_user_id); ?>
                            </div>
                        </div>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-customers')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                            &times; Close Profile
                        </a>
                    </div>
                    <div class="aavni-card-body">
                        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:20px;">
                            <div style="background:var(--aavni-admin-bg); padding:14px; border-radius:8px; border:1px solid var(--aavni-admin-border);">
                                <span style="font-size:11px; text-transform:uppercase; color:#888; font-weight:700;">Email & Mobile</span>
                                <div style="font-weight:600; font-size:14px; margin-top:4px;"><?php echo esc_html($inspected_customer->user_email); ?></div>
                                <div style="font-size:12.5px; color:#555;"><?php echo esc_html($phone ?: 'No phone saved'); ?></div>
                            </div>
                            <div style="background:var(--aavni-admin-bg); padding:14px; border-radius:8px; border:1px solid var(--aavni-admin-border);">
                                <span style="font-size:11px; text-transform:uppercase; color:#888; font-weight:700;">Lifetime Value (LTV)</span>
                                <div style="font-weight:700; font-size:18px; margin-top:4px; color:var(--aavni-admin-terracotta);"><?php echo wc_price($ltv); ?></div>
                                <div style="font-size:12px; color:#888;"><?php echo esc_html(count($customer_orders)); ?> total orders</div>
                            </div>
                            <div style="background:var(--aavni-admin-bg); padding:14px; border-radius:8px; border:1px solid var(--aavni-admin-border);">
                                <span style="font-size:11px; text-transform:uppercase; color:#888; font-weight:700;">Saved Address</span>
                                <div style="font-size:12.5px; margin-top:4px; line-height:1.4;">
                                    <?php echo esc_html($billing_str ?: 'No address saved yet.'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Recent Orders -->
                        <h4 style="margin:20px 0 10px; font-size:15px;">Customer Orders</h4>
                        <div class="aavni-table-wrap">
                            <table class="aavni-admin-table" style="font-size:12.5px;">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Milestone</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($customer_orders)) : ?>
                                        <?php foreach ($customer_orders as $co) :
                                            $m = aavni_get_order_current_milestone($co);
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo esc_html($co->get_order_number()); ?></strong></td>
                                                <td><?php echo esc_html($co->get_date_created()->date('d M Y')); ?></td>
                                                <td><?php echo wc_price($co->get_total()); ?></td>
                                                <td><span class="aavni-badge aavni-badge-<?php echo esc_attr($co->get_status()); ?>"><?php echo esc_html($co->get_status()); ?></span></td>
                                                <td><span class="aavni-badge aavni-badge-<?php echo esc_attr($m); ?>"><?php echo esc_html($m); ?></span></td>
                                                <td style="text-align:right;">
                                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $co->get_id() . '&action=edit')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">View Order</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr><td colspan="6" style="text-align:center; color:#888;">No orders placed.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Search Bar -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aavni-filter-bar">
                <input type="hidden" name="page" value="aavni-customers" />
                <div class="aavni-filter-group">
                    <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search customer name, email, login..." style="width:280px;" />
                    <button type="submit" class="aavni-btn aavni-btn-primary aavni-btn-sm">Search Customers</button>
                    <?php if ($search) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-customers')); ?>" class="aavni-btn aavni-btn-secondary aavni-btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
                <div style="font-size:13px; color:var(--aavni-admin-muted);">
                    Total Registered Customers: <strong><?php echo esc_html($total_customers); ?></strong>
                </div>
            </form>

            <!-- Customers Table -->
            <div class="aavni-admin-card">
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th>Avatar & Name</th>
                                <th>Email</th>
                                <th>Mobile Phone</th>
                                <th>Registration Date</th>
                                <th>Total Orders</th>
                                <th>Lifetime Spend (LTV)</th>
                                <th>City / State</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($customers)) : ?>
                                <?php foreach ($customers as $c) :
                                    $cid = $c->ID;
                                    $phone = get_user_meta($cid, 'billing_phone', true);
                                    $city = get_user_meta($cid, 'billing_city', true);
                                    $state = get_user_meta($cid, 'billing_state', true);
                                    $cust_orders = wc_get_orders(['customer' => $cid, 'limit' => -1]);
                                    $order_count = count($cust_orders);
                                    $ltv = 0.0;
                                    foreach ($cust_orders as $co) {
                                        if (in_array($co->get_status(), ['completed', 'processing'], true)) {
                                            $ltv += (float) $co->get_total();
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="width:36px; height:36px; border-radius:50%; background:var(--aavni-admin-terracotta-light); color:var(--aavni-admin-terracotta); font-weight:700; display:flex; align-items:center; justify-content:center; font-size:14px;">
                                                    <?php echo esc_html(strtoupper(substr($c->display_name, 0, 1))); ?>
                                                </div>
                                                <div>
                                                    <strong><?php echo esc_html($c->display_name); ?></strong>
                                                    <div style="font-size:11px; color:#888;">@<?php echo esc_html($c->user_login); ?> &bull; ID #<?php echo esc_html($cid); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo esc_html($c->user_email); ?>
                                        </td>
                                        <td>
                                            <?php echo esc_html($phone ?: '—'); ?>
                                        </td>
                                        <td style="font-size:12px; color:#666;">
                                            <?php echo esc_html(date_i18n('d M Y', strtotime($c->user_registered))); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($order_count); ?></strong>
                                        </td>
                                        <td>
                                            <strong style="color:var(--aavni-admin-terracotta);"><?php echo wc_price($ltv); ?></strong>
                                        </td>
                                        <td style="font-size:12px; color:#666;">
                                            <?php echo esc_html(implode(', ', array_filter([$city, $state])) ?: '—'); ?>
                                        </td>
                                        <td style="text-align:right; white-space:nowrap;">
                                            <a href="<?php echo esc_url(add_query_arg('customer_id', $cid)); ?>" class="aavni-btn aavni-btn-sm aavni-btn-primary">
                                                👤 View Profile
                                            </a>
                                            <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $cid)); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Edit in WordPress">
                                                ✏️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:36px; color:#888;">
                                        No customer records found.
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
        </div>
        <?php
    }
}
