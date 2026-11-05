<?php
/**
 * AAVNI My Orders Template Override
 *
 * @package AAVNI
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_orders', $has_orders); ?>

<div class="aavni-panel-section">
    <div class="aavni-panel-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 class="aavni-panel-title">🛍️ <?php esc_html_e('My Order History', 'aavni'); ?></h2>
            <p class="aavni-panel-subtitle"><?php esc_html_e('View detailed receipts, download tax invoices, track shipments, and request size exchanges.', 'aavni'); ?></p>
        </div>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('track-order')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-outline">
            🚚 <?php esc_html_e('Go to Order Tracker', 'aavni'); ?>
        </a>
    </div>

    <?php if ($has_orders) : ?>
        <div class="aavni-orders-cards-list" style="margin-top:20px;">
            <?php
            foreach ($customer_orders->orders as $customer_order) :
                $order      = wc_get_order($customer_order);
                $item_count = $order->get_item_count();
                $status     = $order->get_status();
                $status_name = wc_get_order_status_name($status);
                $order_id   = $order->get_id();
                $items      = $order->get_items();
                $milestone  = aavni_get_order_current_milestone($order);

                // Cancellation eligibility: pending, on-hold, processing (before packed/shipped)
                $can_cancel = in_array($status, ['pending', 'on-hold', 'processing'], true) && !in_array($milestone, ['packed', 'shipped', 'out_for_delivery', 'delivered'], true);
                $cancel_url = wp_nonce_url(add_query_arg(['aavni_action' => 'cancel_order', 'order_id' => $order_id], wc_get_account_endpoint_url('orders')), 'aavni_cancel_order_' . $order_id);
                $invoice_url = wp_nonce_url(add_query_arg(['aavni_action' => 'print_invoice', 'order_id' => $order_id], home_url('/')), 'aavni_invoice_' . $order_id);
            ?>
                <div class="aavni-order-item-card">
                    <!-- Top Summary Bar -->
                    <div class="aavni-order-card-header">
                        <div class="aavni-order-header-meta">
                            <div>
                                <span class="aavni-card-meta-label"><?php esc_html_e('ORDER PLACED', 'aavni'); ?></span>
                                <span class="aavni-card-meta-val"><?php echo esc_html($order->get_date_created()->date('d M Y')); ?></span>
                            </div>
                            <div>
                                <span class="aavni-card-meta-label"><?php esc_html_e('TOTAL AMOUNT', 'aavni'); ?></span>
                                <span class="aavni-card-meta-val" style="color:var(--aavni-terracotta);"><?php echo wc_price($order->get_total(), ['currency' => $order->get_currency()]); ?></span>
                            </div>
                            <div>
                                <span class="aavni-card-meta-label"><?php esc_html_e('SHIP TO', 'aavni'); ?></span>
                                <span class="aavni-card-meta-val"><?php echo esc_html($order->get_formatted_billing_full_name()); ?></span>
                            </div>
                        </div>

                        <div class="aavni-order-header-right">
                            <span class="aavni-card-meta-label">ORDER #<?php echo esc_html($order->get_order_number()); ?></span>
                            <span class="aavni-status-pill aavni-status-<?php echo esc_attr($status); ?>">
                                <?php echo esc_html($status_name); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Products Thumbnail & Details Row -->
                    <div class="aavni-order-card-body">
                        <div class="aavni-order-products-col">
                            <?php foreach ($items as $item) :
                                $product = $item->get_product();
                            ?>
                                <div class="aavni-order-product-row">
                                    <div class="aavni-order-product-thumb">
                                        <?php if ($product && $product->get_image_id()) : ?>
                                            <?php echo wp_get_attachment_image($product->get_image_id(), [60, 75]); ?>
                                        <?php else : ?>
                                            <div style="width:60px; height:75px; background:#f5f5f5; display:flex; align-items:center; justify-content:center; color:#999; font-size:20px;">👗</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="aavni-order-product-info">
                                        <h4 class="aavni-order-product-title">
                                            <?php if ($product) : ?>
                                                <a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($item->get_name()); ?></a>
                                            <?php else : ?>
                                                <?php echo esc_html($item->get_name()); ?>
                                            <?php endif; ?>
                                        </h4>
                                        <div class="aavni-order-product-meta">
                                            <span>Qty: <?php echo esc_html($item->get_quantity()); ?></span>
                                            <span>&bull;</span>
                                            <span><?php echo wc_price($order->get_line_total($item, false, false), ['currency' => $order->get_currency()]); ?></span>
                                            <span>&bull;</span>
                                            <span style="color:var(--aavni-text-subtle);">Payment: <?php echo esc_html($order->get_payment_method_title()); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Action Buttons Col -->
                        <div class="aavni-order-card-actions">
                            <a href="<?php echo esc_url($order->get_view_order_url()); ?>" class="aavni-btn aavni-btn-sm aavni-btn-primary" style="width:100%; text-align:center;">
                                <?php esc_html_e('View Order Details', 'aavni'); ?>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg('order_id', $order_id, wc_get_account_endpoint_url('track-order'))); ?>" class="aavni-btn aavni-btn-sm aavni-btn-outline" style="width:100%; text-align:center;">
                                🚚 <?php esc_html_e('Track Package', 'aavni'); ?>
                            </a>
                            <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="aavni-btn aavni-btn-sm aavni-btn-ghost" style="width:100%; text-align:center;">
                                📄 <?php esc_html_e('Download Invoice', 'aavni'); ?>
                            </a>
                            <?php if ($can_cancel) : ?>
                                <a href="<?php echo esc_url($cancel_url); ?>" onclick="return confirm('Are you sure you want to cancel this order? This will cancel your order immediately.');" class="aavni-btn aavni-btn-sm aavni-btn-cancel" style="width:100%; text-align:center; color:#991B1B; border:1px solid #FCA5A5; background:#FEF2F2;">
                                    ✕ <?php esc_html_e('Cancel Order', 'aavni'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php do_action('woocommerce_before_account_orders_pagination'); ?>

        <?php if (1 < $customer_orders->max_num_pages) : ?>
            <div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination" style="margin-top:24px; text-align:center;">
                <?php if (1 !== $current_page) : ?>
                    <a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous aavni-btn aavni-btn-outline" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page - 1)); ?>"><?php esc_html_e('Previous', 'aavni'); ?></a>
                <?php endif; ?>

                <?php if (intval($customer_orders->max_num_pages) !== $current_page) : ?>
                    <a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next aavni-btn aavni-btn-outline" href="<?php echo esc_url(wc_get_endpoint_url('orders', $current_page + 1)); ?>"><?php esc_html_e('Next', 'aavni'); ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else : ?>
        <div class="aavni-empty-state" style="padding:40px; background:#fff; border:1px solid var(--aavni-border); border-radius:12px; text-align:center; margin-top:20px;">
            <div style="font-size:42px; margin-bottom:12px;">👗</div>
            <h3 style="margin:0 0 8px; font-family:var(--font-heading);"><?php esc_html_e('No orders placed yet', 'aavni'); ?></h3>
            <p style="color:var(--aavni-text-muted); font-size:14px; margin-bottom:20px;">
                <?php esc_html_e('Discover our authentic handcrafted Rajasthani Long Kurtis, Short Kurtis & Loungewear.', 'aavni'); ?>
            </p>
            <a class="aavni-btn aavni-btn-primary" href="<?php echo esc_url(apply_filters('woocommerce_return_to_shop_redirect', wc_get_page_permalink('shop'))); ?>">
                <?php esc_html_e('Explore Handcrafted Catalog', 'aavni'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php do_action('woocommerce_after_account_orders', $has_orders); ?>
