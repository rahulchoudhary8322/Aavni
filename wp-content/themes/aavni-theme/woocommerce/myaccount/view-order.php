<?php
/**
 * AAVNI View Order Template Override
 *
 * @package AAVNI
 */

defined('ABSPATH') || exit;

$order_id   = $order->get_id();
$status     = $order->get_status();
$status_name = wc_get_order_status_name($status);
$milestone  = aavni_get_order_current_milestone($order);
$courier    = $order->get_meta('_aavni_courier_name');
$tracking_no = $order->get_meta('_aavni_tracking_number');

$can_cancel = in_array($status, ['pending', 'on-hold', 'processing'], true) && !in_array($milestone, ['packed', 'shipped', 'out_for_delivery', 'delivered'], true);
$cancel_url = wp_nonce_url(add_query_arg(['aavni_action' => 'cancel_order', 'order_id' => $order_id], wc_get_account_endpoint_url('orders')), 'aavni_cancel_order_' . $order_id);
$invoice_url = wp_nonce_url(add_query_arg(['aavni_action' => 'print_invoice', 'order_id' => $order_id], home_url('/')), 'aavni_invoice_' . $order_id);
$track_url = add_query_arg('order_id', $order_id, wc_get_account_endpoint_url('track-order'));
?>

<div class="aavni-panel-section">
    <!-- Top Action Toolbar -->
    <div class="aavni-view-order-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
        <div>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" style="font-size:13px; color:var(--aavni-terracotta); font-weight:600; text-decoration:none;">
                &larr; <?php esc_html_e('Back to All Orders', 'aavni'); ?>
            </a>
            <h2 class="aavni-panel-title" style="margin:4px 0 0;">
                <?php printf(esc_html__('Order #%s Details', 'aavni'), esc_html($order->get_order_number())); ?>
            </h2>
        </div>

        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="<?php echo esc_url($track_url); ?>" class="aavni-btn aavni-btn-sm aavni-btn-primary">
                🚚 <?php esc_html_e('Track Shipment', 'aavni'); ?>
            </a>
            <a href="<?php echo esc_url($invoice_url); ?>" target="_blank" class="aavni-btn aavni-btn-sm aavni-btn-outline">
                📄 <?php esc_html_e('Print Tax Invoice', 'aavni'); ?>
            </a>
            <?php if ($can_cancel) : ?>
                <a href="<?php echo esc_url($cancel_url); ?>" onclick="return confirm('Are you sure you want to cancel this order?');" class="aavni-btn aavni-btn-sm" style="color:#991B1B; background:#FEF2F2; border:1px solid #FCA5A5;">
                    ✕ <?php esc_html_e('Cancel Order', 'aavni'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Notice Card -->
    <div class="aavni-status-banner" style="padding:18px 20px; background:#FAF8F5; border:1px solid var(--aavni-border); border-radius:8px; margin-bottom:24px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <div style="font-size:12px; color:var(--aavni-text-subtle); text-transform:uppercase; letter-spacing:0.5px;">
                <?php esc_html_e('CURRENT STATUS', 'aavni'); ?>
            </div>
            <div style="font-size:18px; font-weight:700; color:var(--aavni-text); margin-top:2px;">
                <?php echo esc_html($status_name); ?>
                <span class="aavni-status-pill aavni-status-<?php echo esc_attr($status); ?>" style="font-size:12px; vertical-align:middle; margin-left:8px;">
                    <?php echo esc_html(strtoupper($milestone)); ?>
                </span>
            </div>
            <div style="font-size:13px; color:var(--aavni-text-muted); margin-top:4px;">
                <?php printf(esc_html__('Placed on %s via %s', 'aavni'), esc_html($order->get_date_created()->date('F j, Y, g:i A')), esc_html($order->get_payment_method_title())); ?>
            </div>
        </div>

        <?php if ($courier || $tracking_no) : ?>
            <div style="font-size:13px; background:#fff; padding:10px 14px; border-radius:6px; border:1px solid var(--aavni-border-light);">
                <strong>Courier:</strong> <?php echo esc_html($courier ?: 'Express Delivery'); ?><br>
                <?php if ($tracking_no) : ?>
                    <strong>AWB:</strong> <code><?php echo esc_html($tracking_no); ?></code>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Items Table -->
    <div class="aavni-table-responsive" style="margin-bottom:24px;">
        <table class="aavni-custom-table">
            <thead>
                <tr>
                    <th style="width:55%;"><?php esc_html_e('Product', 'aavni'); ?></th>
                    <th style="text-align:center;"><?php esc_html_e('Qty', 'aavni'); ?></th>
                    <th style="text-align:right;"><?php esc_html_e('Unit Price', 'aavni'); ?></th>
                    <th style="text-align:right;"><?php esc_html_e('Total', 'aavni'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order->get_items() as $item_id => $item) :
                    $product = $item->get_product();
                ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <?php if ($product && $product->get_image_id()) : ?>
                                    <div style="width:48px; height:60px; flex-shrink:0; border-radius:4px; overflow:hidden; background:#f5f5f5;">
                                        <?php echo wp_get_attachment_image($product->get_image_id(), [48, 60]); ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong>
                                        <?php if ($product) : ?>
                                            <a href="<?php echo esc_url($product->get_permalink()); ?>" style="color:var(--aavni-text); text-decoration:none;">
                                                <?php echo esc_html($item->get_name()); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo esc_html($item->get_name()); ?>
                                        <?php endif; ?>
                                    </strong>
                                    <?php if ($product && $product->get_sku()) : ?>
                                        <div style="font-size:11.5px; color:#888;">SKU: <?php echo esc_html($product->get_sku()); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td style="text-align:center;"><?php echo esc_html($item->get_quantity()); ?></td>
                        <td style="text-align:right;"><?php echo wc_price($order->get_item_subtotal($item, false, false), ['currency' => $order->get_currency()]); ?></td>
                        <td style="text-align:right; font-weight:600;"><?php echo wc_price($order->get_line_total($item, false, false), ['currency' => $order->get_currency()]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <?php foreach ($order->get_order_item_totals() as $key => $total) : ?>
                    <tr>
                        <th colspan="3" style="text-align:right; font-weight:500; font-size:13.5px;"><?php echo esc_html($total['label']); ?></th>
                        <td style="text-align:right; font-weight:600; font-size:14px; <?php echo ($key === 'order_total') ? 'color:var(--aavni-terracotta); font-size:16px;' : ''; ?>"><?php echo wp_kses_post($total['value']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tfoot>
        </table>
    </div>

    <!-- Addresses Grid -->
    <div class="aavni-addresses-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">
        <div class="aavni-address-card" style="background:#FAF8F5; border:1px solid var(--aavni-border); border-radius:8px; padding:18px;">
            <h4 style="margin:0 0 10px; font-family:var(--font-heading); font-size:16px;">📍 <?php esc_html_e('Shipping Address', 'aavni'); ?></h4>
            <div style="font-size:13.5px; line-height:1.6; color:var(--aavni-text-muted);">
                <?php echo wp_kses_post($order->get_formatted_shipping_address() ?: $order->get_formatted_billing_address()); ?>
            </div>
        </div>

        <div class="aavni-address-card" style="background:#FAF8F5; border:1px solid var(--aavni-border); border-radius:8px; padding:18px;">
            <h4 style="margin:0 0 10px; font-family:var(--font-heading); font-size:16px;">💳 <?php esc_html_e('Billing & Payment Details', 'aavni'); ?></h4>
            <div style="font-size:13.5px; line-height:1.6; color:var(--aavni-text-muted);">
                <?php echo wp_kses_post($order->get_formatted_billing_address()); ?><br>
                <strong>Phone:</strong> <?php echo esc_html($order->get_billing_phone()); ?><br>
                <strong>Email:</strong> <?php echo esc_html($order->get_billing_email()); ?><br>
                <strong>Payment Method:</strong> <?php echo esc_html($order->get_payment_method_title()); ?>
            </div>
        </div>
    </div>
</div>
