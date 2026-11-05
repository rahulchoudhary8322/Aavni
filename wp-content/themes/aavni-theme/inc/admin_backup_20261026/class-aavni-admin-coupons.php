<?php
/**
 * AAVNI Coupons & Promotions Management Hub
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Coupons {

    /**
     * Handle Coupon Actions (Create, Delete)
     */
    public static function handle_actions() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // Quick Create Coupon
        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'quick_create_coupon') {
            check_admin_referer('aavni_create_coupon_nonce');

            $code = sanitize_text_field($_POST['coupon_code']);
            $type = sanitize_text_field($_POST['discount_type']);
            $amount = sanitize_text_field($_POST['coupon_amount']);
            $expiry = sanitize_text_field($_POST['expiry_date']);
            $min_spend = sanitize_text_field($_POST['min_spend']);
            $usage_limit = isset($_POST['usage_limit']) ? absint($_POST['usage_limit']) : 0;

            if (empty($code) || empty($amount)) {
                wp_die(__('Coupon Code and Discount Amount are required.', 'aavni'));
            }

            // Check duplicate
            $existing_id = wc_get_coupon_id_by_code($code);
            if ($existing_id) {
                wp_die(sprintf(__('Coupon code "%s" already exists.', 'aavni'), esc_html($code)));
            }

            $coupon = new WC_Coupon();
            $coupon->set_code($code);
            $coupon->set_discount_type($type);
            $coupon->set_amount($amount);
            if (!empty($expiry)) {
                $coupon->set_date_expires($expiry);
            }
            if (!empty($min_spend)) {
                $coupon->set_minimum_amount($min_spend);
            }
            if ($usage_limit > 0) {
                $coupon->set_usage_limit($usage_limit);
            }

            $coupon->save();

            wp_safe_redirect(add_query_arg(['page' => 'aavni-coupons', 'created' => $code], admin_url('admin.php')));
            exit;
        }

        // Trash Coupon
        if (isset($_GET['action']) && $_GET['action'] === 'trash_coupon' && isset($_GET['coupon_id'])) {
            $coupon_id = absint($_GET['coupon_id']);
            check_admin_referer('aavni_trash_coupon_' . $coupon_id);

            wp_trash_post($coupon_id);

            wp_safe_redirect(add_query_arg(['page' => 'aavni-coupons', 'trashed' => $coupon_id], admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Render Coupons Management Screen
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to manage coupons.', 'aavni'));
        }

        self::handle_actions();

        $coupon_posts = get_posts([
            'post_type'      => 'shop_coupon',
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">🏷️</div>
                    <div>
                        <h1 class="aavni-header-title">Coupons & Promotions Hub</h1>
                        <p class="aavni-header-subtitle">Create seasonal discounts, festival coupon codes, cart-level offers & track coupon usage</p>
                    </div>
                </div>
                <div class="aavni-header-actions">
                    <button type="button" class="aavni-btn aavni-btn-gold" data-aavni-modal="aavniCreateCouponModal">
                        + Create New Coupon
                    </button>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('coupons'); ?>

            <?php if (isset($_GET['created'])) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px;">
                    <p><?php printf(esc_html__('Coupon code "%s" created successfully and ready for checkout.', 'aavni'), esc_html($_GET['created'])); ?></p>
                </div>
            <?php elseif (isset($_GET['trashed'])) : ?>
                <div class="notice notice-warning is-dismissible" style="margin-bottom:20px;">
                    <p><?php esc_html_e('Coupon moved to trash.', 'aavni'); ?></p>
                </div>
            <?php endif; ?>

            <!-- Coupons Table -->
            <div class="aavni-admin-card">
                <div class="aavni-card-header">
                    <h3 class="aavni-card-title">Active Store Coupons</h3>
                </div>
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th>Coupon Code</th>
                                <th>Coupon Type</th>
                                <th>Coupon Amount</th>
                                <th>Usage / Limit</th>
                                <th>Expiry Date</th>
                                <th>Minimum Spend</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($coupon_posts)) : ?>
                                <?php foreach ($coupon_posts as $post) :
                                    $coupon = new WC_Coupon($post->ID);
                                    $code = $coupon->get_code();
                                    $type = $coupon->get_discount_type();
                                    $amount = $coupon->get_amount();
                                    $usage_count = $coupon->get_usage_count();
                                    $usage_limit = $coupon->get_usage_limit();
                                    $expiry_date = $coupon->get_date_expires();
                                    $min_amount = $coupon->get_minimum_amount();

                                    $type_labels = [
                                        'percent'       => 'Percentage Discount',
                                        'fixed_cart'    => 'Fixed Cart Discount',
                                        'fixed_product' => 'Fixed Product Discount',
                                    ];
                                    $type_label = isset($type_labels[$type]) ? $type_labels[$type] : ucfirst($type);

                                    $trash_url = wp_nonce_url(admin_url('admin.php?page=aavni-coupons&action=trash_coupon&coupon_id=' . $post->ID), 'aavni_trash_coupon_' . $post->ID);
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="font-size:14px; color:var(--aavni-admin-terracotta);">
                                                <code><?php echo esc_html(strtoupper($code)); ?></code>
                                            </strong>
                                        </td>
                                        <td>
                                            <?php echo esc_html($type_label); ?>
                                        </td>
                                        <td>
                                            <strong>
                                                <?php if ($type === 'percent') : ?>
                                                    <?php echo esc_html($amount); ?>%
                                                <?php else : ?>
                                                    ₹<?php echo esc_html($amount); ?>
                                                <?php endif; ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <span><?php echo esc_html($usage_count); ?></span>
                                            <?php if ($usage_limit > 0) : ?>
                                                <span style="color:#888;"> / <?php echo esc_html($usage_limit); ?></span>
                                            <?php else : ?>
                                                <span style="color:#888;"> / &infin;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-size:12px;">
                                            <?php if ($expiry_date) : ?>
                                                <span><?php echo esc_html($expiry_date->date('d M Y')); ?></span>
                                            <?php else : ?>
                                                <span style="color:#888;">No expiry</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($min_amount) ? '₹' . esc_html($min_amount) : '—'; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <a href="<?php echo esc_url($trash_url); ?>" onclick="return confirm('Delete this coupon?');" class="aavni-btn aavni-btn-sm aavni-btn-danger">
                                                🗑️ Trash
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:36px; color:#888;">
                                        No coupon promotions created yet. Click "+ Create New Coupon" above.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Create Coupon Modal -->
            <div id="aavniCreateCouponModal" class="aavni-modal-overlay">
                <div class="aavni-modal-box">
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-coupons')); ?>">
                        <?php wp_nonce_field('aavni_create_coupon_nonce'); ?>
                        <input type="hidden" name="aavni_action" value="quick_create_coupon" />

                        <div class="aavni-modal-header">
                            <h3 class="aavni-modal-title">🏷️ Create Promotional Coupon</h3>
                            <button type="button" class="aavni-modal-close" data-aavni-dismiss="modal">&times;</button>
                        </div>

                        <div class="aavni-modal-body">
                            <p>
                                <label for="c_code"><strong>Coupon Promo Code *:</strong></label><br>
                                <input type="text" name="coupon_code" id="c_code" required placeholder="e.g. AAVNI10 or FESTIVE200" style="width:100%; margin-top:6px; text-transform:uppercase;" />
                            </p>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <div>
                                    <label for="c_type"><strong>Discount Type:</strong></label><br>
                                    <select name="discount_type" id="c_type" style="width:100%; margin-top:6px;">
                                        <option value="percent">Percentage Discount (%)</option>
                                        <option value="fixed_cart">Fixed Cart Discount (₹)</option>
                                        <option value="fixed_product">Fixed Product Discount (₹)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="c_amt"><strong>Discount Amount *:</strong></label><br>
                                    <input type="number" step="0.01" min="1" name="coupon_amount" id="c_amt" required placeholder="e.g. 10 or 150" style="width:100%; margin-top:6px;" />
                                </div>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:12px;">
                                <div>
                                    <label for="c_exp"><strong>Expiry Date (Optional):</strong></label><br>
                                    <input type="date" name="expiry_date" id="c_exp" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="c_min"><strong>Minimum Cart Spend (₹):</strong></label><br>
                                    <input type="number" step="1" min="0" name="min_spend" id="c_min" placeholder="e.g. 999" style="width:100%; margin-top:6px;" />
                                </div>
                            </div>

                            <p style="margin-top:12px;">
                                <label for="c_limit"><strong>Usage Limit Per Coupon (0 for Unlimited):</strong></label><br>
                                <input type="number" min="0" name="usage_limit" id="c_limit" value="0" style="width:100%; margin-top:6px;" />
                            </p>
                        </div>

                        <div class="aavni-modal-footer">
                            <button type="button" class="aavni-btn aavni-btn-secondary" data-aavni-dismiss="modal">Cancel</button>
                            <button type="submit" class="aavni-btn aavni-btn-primary">Save & Activate Coupon</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
