<?php
/**
 * AAVNI Luxury Customer Account Dashboard
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$first_name = $current_user->first_name ?: $current_user->display_name;

// Calculate real metrics
$customer_orders = wc_get_orders(['customer' => $user_id, 'limit' => -1]);
$total_orders_count = count($customer_orders);

$active_orders_count = 0;
foreach ($customer_orders as $ord) {
    if (in_array($ord->get_status(), ['pending', 'processing', 'on-hold'], true)) {
        $active_orders_count++;
    }
}

// Check saved addresses
$billing_address_1 = get_user_meta($user_id, 'billing_address_1', true);
$shipping_address_1 = get_user_meta($user_id, 'shipping_address_1', true);
$saved_addresses_count = ($billing_address_1 ? 1 : 0) + ($shipping_address_1 ? 1 : 0);

// Returns count
global $wpdb;
$table_returns = $wpdb->prefix . 'aavni_returns';
$pending_returns_count = 0;
if ($wpdb->get_var("SHOW TABLES LIKE '$table_returns'") === $table_returns) {
    $pending_returns_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_returns WHERE customer_id = %d AND status IN ('submitted', 'under_review')", $user_id));
}

// Recent 3 orders
$recent_orders = array_slice($customer_orders, 0, 3);
?>

<div class="aavni-dashboard-container">
    <!-- Welcome Header Banner -->
    <div class="aavni-welcome-banner">
        <div class="aavni-welcome-text">
            <span class="aavni-welcome-subtitle"><?php esc_html_e('ACCOUNT OVERVIEW', 'aavni'); ?></span>
            <h1 class="aavni-welcome-title"><?php printf(esc_html__('Welcome back, %s!', 'aavni'), esc_html($first_name)); ?></h1>
            <p class="aavni-welcome-desc">
                <?php esc_html_e('From your personal account panel you can track real-time delivery milestones, manage saved addresses, request exchanges, and view past purchases.', 'aavni'); ?>
            </p>
        </div>
        <div class="aavni-welcome-badge">
            <span class="aavni-gold-seal">✦ AAVNI PRIVILEGE ✦</span>
            <small><?php esc_html_e('Handcrafted Luxury Care', 'aavni'); ?></small>
        </div>
    </div>

    <!-- 4 Stats Cards Grid -->
    <div class="aavni-stats-grid">
        <div class="aavni-stat-card">
            <div class="aavni-stat-icon-wrapper" style="background:#EFF6FF; color:#1D4ED8;">
                🚚
            </div>
            <div class="aavni-stat-data">
                <span class="aavni-stat-number"><?php echo esc_html($active_orders_count); ?></span>
                <span class="aavni-stat-label"><?php esc_html_e('Active Orders', 'aavni'); ?></span>
            </div>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('track-order')); ?>" class="aavni-stat-link">
                <?php esc_html_e('Track Now &rarr;', 'aavni'); ?>
            </a>
        </div>

        <div class="aavni-stat-card">
            <div class="aavni-stat-icon-wrapper" style="background:#FAF8F5; color:#A85A48;">
                🛍️
            </div>
            <div class="aavni-stat-data">
                <span class="aavni-stat-number"><?php echo esc_html($total_orders_count); ?></span>
                <span class="aavni-stat-label"><?php esc_html_e('Total Orders', 'aavni'); ?></span>
            </div>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="aavni-stat-link">
                <?php esc_html_e('View History &rarr;', 'aavni'); ?>
            </a>
        </div>

        <div class="aavni-stat-card">
            <div class="aavni-stat-icon-wrapper" style="background:#ECFDF5; color:#047857;">
                📍
            </div>
            <div class="aavni-stat-data">
                <span class="aavni-stat-number"><?php echo esc_html($saved_addresses_count); ?></span>
                <span class="aavni-stat-label"><?php esc_html_e('Saved Addresses', 'aavni'); ?></span>
            </div>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>" class="aavni-stat-link">
                <?php esc_html_e('Manage &rarr;', 'aavni'); ?>
            </a>
        </div>

        <div class="aavni-stat-card">
            <div class="aavni-stat-icon-wrapper" style="background:#FEF3C7; color:#B45309;">
                🔄
            </div>
            <div class="aavni-stat-data">
                <span class="aavni-stat-number"><?php echo esc_html($pending_returns_count); ?></span>
                <span class="aavni-stat-label"><?php esc_html_e('Return Requests', 'aavni'); ?></span>
            </div>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('returns')); ?>" class="aavni-stat-link">
                <?php esc_html_e('Details &rarr;', 'aavni'); ?>
            </a>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="aavni-dashboard-section" style="margin-top:32px;">
        <div class="aavni-section-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 class="aavni-section-title" style="margin:0; font-family:var(--font-heading); font-size:20px;">
                <?php esc_html_e('Recent Purchases', 'aavni'); ?>
            </h2>
            <?php if ($total_orders_count > 0) : ?>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="aavni-link-more" style="font-size:13.5px; font-weight:600; color:var(--aavni-terracotta);">
                    <?php esc_html_e('View All Orders &rarr;', 'aavni'); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($recent_orders)) : ?>
            <div class="aavni-table-responsive">
                <table class="aavni-custom-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order) :
                            $status = $order->get_status();
                            $status_name = wc_get_order_status_name($status);
                            $milestone = aavni_get_order_current_milestone($order);
                        ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo esc_html($order->get_order_number()); ?></strong>
                                </td>
                                <td>
                                    <?php echo esc_html($order->get_date_created()->date('d M Y')); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($order->get_item_count()); ?> item(s)
                                </td>
                                <td>
                                    <span class="aavni-status-pill aavni-status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html($status_name); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo wc_price($order->get_total(), ['currency' => $order->get_currency()]); ?></strong>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; gap:6px;">
                                        <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="aavni-btn aavni-btn-sm aavni-btn-outline">
                                            <?php esc_html_e('View', 'aavni'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(add_query_arg('order_id', $order->get_id(), wc_get_account_endpoint_url('track-order'))); ?>" class="aavni-btn aavni-btn-sm aavni-btn-primary">
                                            <?php esc_html_e('Track', 'aavni'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="aavni-empty-state-card" style="background:#fff; border:1px solid var(--aavni-border); border-radius:12px; padding:36px; text-align:center;">
                <div style="font-size:36px; margin-bottom:10px;">🛍️</div>
                <h3 style="margin:0 0 6px; font-family:var(--font-heading);"><?php esc_html_e('No orders placed yet', 'aavni'); ?></h3>
                <p style="color:var(--aavni-text-muted); font-size:14px; margin-bottom:18px;">
                    <?php esc_html_e('Explore our handcrafted pure cotton Long Kurtis, Short Kurtis, and Nightwear.', 'aavni'); ?>
                </p>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="aavni-btn aavni-btn-primary">
                    <?php esc_html_e('Discover Collections', 'aavni'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Help & WhatsApp Strip -->
    <div class="aavni-quick-support-strip" style="margin-top:36px; padding:20px; background:#FAF8F5; border:1px solid var(--aavni-border); border-radius:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <div style="width:44px; height:44px; border-radius:50%; background:#25D366; color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px;">
                💬
            </div>
            <div>
                <strong style="font-size:15px; display:block; color:var(--aavni-text);"><?php esc_html_e('Need immediate help with sizing, delivery or exchanges?', 'aavni'); ?></strong>
                <span style="font-size:13px; color:var(--aavni-text-muted);"><?php esc_html_e('Our Jaipur boutique team is available on WhatsApp Mon &ndash; Sat (10 AM &ndash; 7 PM).', 'aavni'); ?></span>
            </div>
        </div>
        <a href="https://wa.me/919828103222?text=<?php echo urlencode('Hi AAVNI team, I need help with my account.'); ?>" target="_blank" rel="noopener" class="aavni-btn" style="background:#25D366; color:#fff; text-decoration:none;">
            <?php esc_html_e('Chat on WhatsApp: 9828103222', 'aavni'); ?>
        </a>
    </div>
</div>
