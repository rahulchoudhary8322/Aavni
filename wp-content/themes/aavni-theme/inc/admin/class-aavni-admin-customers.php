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
     * Handle Customer Profile and Address Updates
     */
    public static function handle_actions() {
        if (!current_user_can('edit_users') && !current_user_can('manage_woocommerce')) {
            return;
        }

        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'update_customer_profile') {
            check_admin_referer('aavni_edit_customer_nonce');

            $customer_id = absint($_POST['customer_id']);
            $user = get_user_by('id', $customer_id);
            if (!$user) {
                wp_die(__('Invalid customer ID.', 'aavni'));
            }

            // Ensure we do not edit admins or shop managers from this customer-specific screen
            if (!in_array('customer', (array) $user->roles, true) && !in_array('subscriber', (array) $user->roles, true)) {
                wp_die(__('Only registered customer accounts can be modified from this screen.', 'aavni'));
            }

            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
            $email = sanitize_email($_POST['user_email']);
            $phone = sanitize_text_field($_POST['billing_phone']);

            $b_addr1 = sanitize_text_field($_POST['billing_address_1'] ?? '');
            $b_addr2 = sanitize_text_field($_POST['billing_address_2'] ?? '');
            $b_city = sanitize_text_field($_POST['billing_city'] ?? '');
            $b_state = sanitize_text_field($_POST['billing_state'] ?? '');
            $b_postcode = sanitize_text_field($_POST['billing_postcode'] ?? '');

            $s_addr1 = sanitize_text_field($_POST['shipping_address_1'] ?? '');
            $s_addr2 = sanitize_text_field($_POST['shipping_address_2'] ?? '');
            $s_city = sanitize_text_field($_POST['shipping_city'] ?? '');
            $s_state = sanitize_text_field($_POST['shipping_state'] ?? '');
            $s_postcode = sanitize_text_field($_POST['shipping_postcode'] ?? '');

            if (empty($email) || !is_email($email)) {
                wp_die(__('A valid email address is required.', 'aavni'));
            }

            $email_owner = email_exists($email);
            if ($email_owner && (int) $email_owner !== (int) $customer_id) {
                wp_die(__('This email address is already registered to another user account.', 'aavni'));
            }

            // Update via WC_Customer API
            $wc_customer = new WC_Customer($customer_id);
            $wc_customer->set_first_name($first_name);
            $wc_customer->set_last_name($last_name);
            $display_name = trim($first_name . ' ' . $last_name);
            if (!empty($display_name)) {
                $wc_customer->set_display_name($display_name);
            }
            $wc_customer->set_email($email);
            $wc_customer->set_billing_phone($phone);
            $wc_customer->set_billing_address_1($b_addr1);
            $wc_customer->set_billing_address_2($b_addr2);
            $wc_customer->set_billing_city($b_city);
            $wc_customer->set_billing_state($b_state);
            $wc_customer->set_billing_postcode($b_postcode);

            $wc_customer->set_shipping_address_1($s_addr1);
            $wc_customer->set_shipping_address_2($s_addr2);
            $wc_customer->set_shipping_city($s_city);
            $wc_customer->set_shipping_state($s_state);
            $wc_customer->set_shipping_postcode($s_postcode);

            $wc_customer->save();

            wp_safe_redirect(add_query_arg(['page' => 'aavni-customers', 'customer_id' => $customer_id, 'updated' => 'customer'], admin_url('admin.php')));
            exit;
        }
    }

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
                $customer_orders = wc_get_orders(['customer' => $view_user_id, 'limit' => 10, 'type' => 'shop_order']);
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

            <?php if (isset($_GET['updated']) && $_GET['updated'] === 'customer') : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px;">
                    <p><?php esc_html_e('Customer profile, contact details and addresses updated successfully.', 'aavni'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Customer Detail Drawer / Modal if requested -->
            <?php if ($inspected_customer) :
                $first_name = get_user_meta($view_user_id, 'first_name', true) ?: $inspected_customer->display_name;
                $last_name = get_user_meta($view_user_id, 'last_name', true);
                $phone = get_user_meta($view_user_id, 'billing_phone', true);
                $b_addr1 = get_user_meta($view_user_id, 'billing_address_1', true);
                $b_addr2 = get_user_meta($view_user_id, 'billing_address_2', true);
                $b_city = get_user_meta($view_user_id, 'billing_city', true);
                $b_state = get_user_meta($view_user_id, 'billing_state', true);
                $b_postcode = get_user_meta($view_user_id, 'billing_postcode', true);

                $s_addr1 = get_user_meta($view_user_id, 'shipping_address_1', true);
                $s_addr2 = get_user_meta($view_user_id, 'shipping_address_2', true);
                $s_city = get_user_meta($view_user_id, 'shipping_city', true);
                $s_state = get_user_meta($view_user_id, 'shipping_state', true);
                $s_postcode = get_user_meta($view_user_id, 'shipping_postcode', true);

                $billing_str = implode(', ', array_filter([$b_addr1, $b_addr2, $b_city, $b_state, $b_postcode]));

                // Calculate LTV excluding refunds and only counting valid orders
                $ltv = 0.0;
                foreach ($customer_orders as $co) {
                    if (!is_a($co, 'WC_Order') || is_a($co, 'WC_Order_Refund')) {
                        continue;
                    }
                    if (in_array($co->get_status(), ['completed', 'processing'], true)) {
                        $ref = method_exists($co, 'get_total_refunded') ? (float) $co->get_total_refunded() : 0.0;
                        $ltv += max(0, (float) $co->get_total() - $ref);
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
                        <div style="display:flex; gap:8px;">
                            <button type="button" 
                                    class="aavni-btn aavni-btn-sm aavni-btn-primary aavni-trigger-customer-edit"
                                    data-id="<?php echo esc_attr($view_user_id); ?>"
                                    data-first-name="<?php echo esc_attr($first_name); ?>"
                                    data-last-name="<?php echo esc_attr($last_name); ?>"
                                    data-email="<?php echo esc_attr($inspected_customer->user_email); ?>"
                                    data-phone="<?php echo esc_attr($phone); ?>"
                                    data-b-addr1="<?php echo esc_attr($b_addr1); ?>"
                                    data-b-addr2="<?php echo esc_attr($b_addr2); ?>"
                                    data-b-city="<?php echo esc_attr($b_city); ?>"
                                    data-b-state="<?php echo esc_attr($b_state); ?>"
                                    data-b-postcode="<?php echo esc_attr($b_postcode); ?>"
                                    data-s-addr1="<?php echo esc_attr($s_addr1); ?>"
                                    data-s-addr2="<?php echo esc_attr($s_addr2); ?>"
                                    data-s-city="<?php echo esc_attr($s_city); ?>"
                                    data-s-state="<?php echo esc_attr($s_state); ?>"
                                    data-s-postcode="<?php echo esc_attr($s_postcode); ?>">
                                ✏️ Edit Profile & Addresses
                            </button>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-customers')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                                &times; Close Profile
                            </a>
                        </div>
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
                                <span style="font-size:11px; text-transform:uppercase; color:#888; font-weight:700;">Saved Billing Address</span>
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
                                            if (!is_a($co, 'WC_Order') || is_a($co, 'WC_Order_Refund')) {
                                                continue;
                                            }
                                            $m = aavni_get_order_current_milestone($co);
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo esc_html($co->get_order_number()); ?></strong></td>
                                                <td><?php echo esc_html($co->get_date_created() ? $co->get_date_created()->date('d M Y') : '—'); ?></td>
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
                                    $c_fn = get_user_meta($cid, 'first_name', true);
                                    $c_ln = get_user_meta($cid, 'last_name', true);
                                    $phone = get_user_meta($cid, 'billing_phone', true);
                                    $b_addr1 = get_user_meta($cid, 'billing_address_1', true);
                                    $b_addr2 = get_user_meta($cid, 'billing_address_2', true);
                                    $city = get_user_meta($cid, 'billing_city', true);
                                    $state = get_user_meta($cid, 'billing_state', true);
                                    $postcode = get_user_meta($cid, 'billing_postcode', true);

                                    $s_addr1 = get_user_meta($cid, 'shipping_address_1', true);
                                    $s_addr2 = get_user_meta($cid, 'shipping_address_2', true);
                                    $s_city = get_user_meta($cid, 'shipping_city', true);
                                    $s_state = get_user_meta($cid, 'shipping_state', true);
                                    $s_postcode = get_user_meta($cid, 'shipping_postcode', true);

                                    $cust_orders = wc_get_orders(['customer' => $cid, 'limit' => -1, 'type' => 'shop_order']);
                                    $order_count = count($cust_orders);
                                    $ltv = 0.0;
                                    foreach ($cust_orders as $co) {
                                        if (!is_a($co, 'WC_Order') || is_a($co, 'WC_Order_Refund')) {
                                            continue;
                                        }
                                        if (in_array($co->get_status(), ['completed', 'processing'], true)) {
                                            $ref = method_exists($co, 'get_total_refunded') ? (float) $co->get_total_refunded() : 0.0;
                                            $ltv += max(0, (float) $co->get_total() - $ref);
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
                                            <a href="<?php echo esc_url(add_query_arg('customer_id', $cid)); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                                                👤 View Profile
                                            </a>
                                            <button type="button" 
                                                    class="aavni-btn aavni-btn-sm aavni-btn-primary aavni-trigger-customer-edit"
                                                    data-id="<?php echo esc_attr($cid); ?>"
                                                    data-first-name="<?php echo esc_attr($c_fn); ?>"
                                                    data-last-name="<?php echo esc_attr($c_ln); ?>"
                                                    data-email="<?php echo esc_attr($c->user_email); ?>"
                                                    data-phone="<?php echo esc_attr($phone); ?>"
                                                    data-b-addr1="<?php echo esc_attr($b_addr1); ?>"
                                                    data-b-addr2="<?php echo esc_attr($b_addr2); ?>"
                                                    data-b-city="<?php echo esc_attr($city); ?>"
                                                    data-b-state="<?php echo esc_attr($state); ?>"
                                                    data-b-postcode="<?php echo esc_attr($postcode); ?>"
                                                    data-s-addr1="<?php echo esc_attr($s_addr1); ?>"
                                                    data-s-addr2="<?php echo esc_attr($s_addr2); ?>"
                                                    data-s-city="<?php echo esc_attr($s_city); ?>"
                                                    data-s-state="<?php echo esc_attr($s_state); ?>"
                                                    data-s-postcode="<?php echo esc_attr($s_postcode); ?>">
                                                ✏️ Edit
                                            </button>
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

            <!-- In-Place Edit Customer Profile Modal -->
            <div id="aavniCustomerEditModal" class="aavni-modal-overlay">
                <div class="aavni-modal-box" style="max-width:680px; max-height:90vh; overflow-y:auto;">
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-customers')); ?>">
                        <?php wp_nonce_field('aavni_edit_customer_nonce'); ?>
                        <input type="hidden" name="aavni_action" value="update_customer_profile" />
                        <input type="hidden" name="customer_id" id="edit_cust_id" value="" />

                        <div class="aavni-modal-header">
                            <h3 class="aavni-modal-title">✏️ Edit Customer Profile & Addresses</h3>
                            <button type="button" class="aavni-modal-close" data-aavni-dismiss="modal">&times;</button>
                        </div>

                        <div class="aavni-modal-body">
                            <!-- Account Details -->
                            <h4 style="margin:0 0 10px; font-size:14px;">Contact & Name</h4>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                                <div>
                                    <label for="edit_cust_first_name"><strong>First Name:</strong></label><br>
                                    <input type="text" name="first_name" id="edit_cust_first_name" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="edit_cust_last_name"><strong>Last Name:</strong></label><br>
                                    <input type="text" name="last_name" id="edit_cust_last_name" style="width:100%; margin-top:6px;" />
                                </div>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:18px;">
                                <div>
                                    <label for="edit_cust_email"><strong>Email Address *:</strong></label><br>
                                    <input type="email" name="user_email" id="edit_cust_email" required style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="edit_cust_phone"><strong>Billing Phone:</strong></label><br>
                                    <input type="tel" name="billing_phone" id="edit_cust_phone" style="width:100%; margin-top:6px;" />
                                </div>
                            </div>

                            <!-- Billing Address -->
                            <h4 style="margin:16px 0 10px; font-size:14px; border-top:1px solid #eee; padding-top:14px;">Billing Address</h4>
                            <p style="margin-bottom:10px;">
                                <label for="edit_cust_b_addr1"><strong>Street Address Line 1:</strong></label><br>
                                <input type="text" name="billing_address_1" id="edit_cust_b_addr1" style="width:100%; margin-top:6px;" />
                            </p>
                            <p style="margin-bottom:10px;">
                                <label for="edit_cust_b_addr2"><strong>Street Address Line 2:</strong></label><br>
                                <input type="text" name="billing_address_2" id="edit_cust_b_addr2" style="width:100%; margin-top:6px;" />
                            </p>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:18px;">
                                <div>
                                    <label for="edit_cust_b_city"><strong>City:</strong></label><br>
                                    <input type="text" name="billing_city" id="edit_cust_b_city" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="edit_cust_b_state"><strong>State:</strong></label><br>
                                    <input type="text" name="billing_state" id="edit_cust_b_state" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="edit_cust_b_postcode"><strong>PIN / Postcode:</strong></label><br>
                                    <input type="text" name="billing_postcode" id="edit_cust_b_postcode" style="width:100%; margin-top:6px;" />
                                </div>
                            </div>

                            <!-- Shipping Address -->
                            <h4 style="margin:16px 0 10px; font-size:14px; border-top:1px solid #eee; padding-top:14px;">Shipping Address</h4>
                            <p style="margin-bottom:10px;">
                                <label for="edit_cust_s_addr1"><strong>Street Address Line 1:</strong></label><br>
                                <input type="text" name="shipping_address_1" id="edit_cust_s_addr1" style="width:100%; margin-top:6px;" />
                            </p>
                            <p style="margin-bottom:10px;">
                                <label for="edit_cust_s_addr2"><strong>Street Address Line 2:</strong></label><br>
                                <input type="text" name="shipping_address_2" id="edit_cust_s_addr2" style="width:100%; margin-top:6px;" />
                            </p>
                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                                <div>
                                    <label for="edit_cust_s_city"><strong>City:</strong></label><br>
                                    <input type="text" name="shipping_city" id="edit_cust_s_city" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="edit_cust_s_state"><strong>State:</strong></label><br>
                                    <input type="text" name="shipping_state" id="edit_cust_s_state" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="edit_cust_s_postcode"><strong>PIN / Postcode:</strong></label><br>
                                    <input type="text" name="shipping_postcode" id="edit_cust_s_postcode" style="width:100%; margin-top:6px;" />
                                </div>
                            </div>
                        </div>

                        <div class="aavni-modal-footer">
                            <button type="button" class="aavni-btn aavni-btn-secondary" data-aavni-dismiss="modal">Cancel</button>
                            <button type="submit" class="aavni-btn aavni-btn-primary">Save Customer Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
