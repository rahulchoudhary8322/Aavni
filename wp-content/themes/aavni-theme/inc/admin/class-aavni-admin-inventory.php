<?php
/**
 * AAVNI Inventory & Stock Control Hub
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Inventory {

    /**
     * Handle Restock Actions
     */
    public static function handle_actions() {
        if (!current_user_can('edit_products')) {
            return;
        }

        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'bulk_restock') {
            check_admin_referer('aavni_bulk_restock_nonce');

            $prod_id = absint($_POST['product_id']);
            $add_qty = intval($_POST['add_quantity']);
            $product = wc_get_product($prod_id);

            if ($product && $add_qty > 0) {
                $current_stock = $product->get_stock_quantity() ?: 0;
                $new_stock = $current_stock + $add_qty;

                $product->set_manage_stock(true);
                $product->set_stock_quantity($new_stock);
                $product->set_stock_status('instock');
                $product->save();

                wp_safe_redirect(add_query_arg(['page' => 'aavni-inventory', 'restocked' => $prod_id, 'added' => $add_qty], admin_url('admin.php')));
                exit;
            }
        }
    }

    /**
     * Render Inventory Management Screen
     */
    public static function render() {
        if (!current_user_can('edit_products')) {
            wp_die(__('You do not have sufficient permissions to manage store inventory.', 'aavni'));
        }

        $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $limit = 20;

        $args = [
            'limit'    => -1,
            'return'   => 'objects',
            'orderby'  => 'title',
            'order'    => 'ASC',
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $all_products = wc_get_products($args);

        // Calculate inventory metrics
        $total_skus = count($all_products);
        $low_stock_count = 0;
        $out_of_stock_count = 0;
        $in_stock_count = 0;
        $filtered_items = [];

        foreach ($all_products as $p) {
            $is_managing = $p->managing_stock();
            $qty = $is_managing ? $p->get_stock_quantity() : null;
            $status = $p->get_stock_status();

            $is_out = ($status === 'outofstock' || ($is_managing && $qty <= 0));
            $is_low = (!$is_out && $is_managing && $qty <= 5);

            if ($is_out) {
                $out_of_stock_count++;
            } elseif ($is_low) {
                $low_stock_count++;
            } else {
                $in_stock_count++;
            }

            // Filtering
            if ($filter === 'low_stock' && !$is_low) {
                continue;
            }
            if ($filter === 'out_of_stock' && !$is_out) {
                continue;
            }
            if ($filter === 'in_stock' && ($is_low || $is_out)) {
                continue;
            }

            $filtered_items[] = [
                'product'   => $p,
                'qty'       => $qty,
                'is_low'    => $is_low,
                'is_out'    => $is_out,
                'managing'  => $is_managing,
            ];
        }

        $total_filtered = count($filtered_items);
        $total_pages = ceil($total_filtered / $limit);
        $page_items = array_slice($filtered_items, ($paged - 1) * $limit, $limit);

        $stock_nonce = wp_create_nonce('aavni_admin_quick_stock');
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">📊</div>
                    <div>
                        <h1 class="aavni-header-title">Inventory & Stock Control Hub</h1>
                        <p class="aavni-header-subtitle">Real-time stock monitoring, low-stock warnings, inline quantity adjustments & restock management</p>
                    </div>
                </div>
                <div class="aavni-header-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-products')); ?>" class="aavni-btn aavni-btn-gold">
                        👗 View Catalog
                    </a>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('inventory'); ?>

            <?php if (isset($_GET['restocked'])) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px;">
                    <p><?php printf(esc_html__('Product #%d successfully restocked (+%d units).', 'aavni'), absint($_GET['restocked']), absint($_GET['added'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Inventory Health KPI Cards -->
            <div class="aavni-kpi-grid">
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-inventory&filter=all')); ?>" class="aavni-kpi-card <?php echo $filter === 'all' ? 'is-accent-gold' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Total Catalog SKUs</span>
                        <div class="aavni-kpi-icon">📦</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($total_skus); ?></div>
                    <div class="aavni-kpi-meta">All store items</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-inventory&filter=in_stock')); ?>" class="aavni-kpi-card is-accent-success <?php echo $filter === 'in_stock' ? 'is-accent-gold' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Healthy Stock (>5)</span>
                        <div class="aavni-kpi-icon">✅</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($in_stock_count); ?></div>
                    <div class="aavni-kpi-meta">Sufficient units available</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-inventory&filter=low_stock')); ?>" class="aavni-kpi-card is-accent-warning <?php echo $filter === 'low_stock' ? 'is-accent-gold' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Low Stock Alert (&le;5)</span>
                        <div class="aavni-kpi-icon">⚠️</div>
                    </div>
                    <div class="aavni-kpi-value" style="color:#D97706;"><?php echo esc_html($low_stock_count); ?></div>
                    <div class="aavni-kpi-meta">Restocking recommended</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-inventory&filter=out_of_stock')); ?>" class="aavni-kpi-card is-accent-danger <?php echo $filter === 'out_of_stock' ? 'is-accent-gold' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Out of Stock</span>
                        <div class="aavni-kpi-icon">🚫</div>
                    </div>
                    <div class="aavni-kpi-value" style="color:#DC2626;"><?php echo esc_html($out_of_stock_count); ?></div>
                    <div class="aavni-kpi-meta">Unavailable for purchase</div>
                </a>
            </div>

            <!-- Search and Filter Bar -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aavni-filter-bar">
                <input type="hidden" name="page" value="aavni-inventory" />

                <div class="aavni-filter-group">
                    <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search SKU or product title..." style="width:240px;" />

                    <select name="filter">
                        <option value="all" <?php selected($filter, 'all'); ?>>All Inventory</option>
                        <option value="low_stock" <?php selected($filter, 'low_stock'); ?>>Low Stock Only (&le;5)</option>
                        <option value="out_of_stock" <?php selected($filter, 'out_of_stock'); ?>>Out of Stock Only</option>
                        <option value="in_stock" <?php selected($filter, 'in_stock'); ?>>Healthy Stock (>5)</option>
                    </select>

                    <button type="submit" class="aavni-btn aavni-btn-primary aavni-btn-sm">Filter</button>
                    <?php if ($filter !== 'all' || $search) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-inventory')); ?>" class="aavni-btn aavni-btn-secondary aavni-btn-sm">Reset</a>
                    <?php endif; ?>
                </div>

                <div style="font-size:13px; color:var(--aavni-admin-muted);">
                    Showing <strong><?php echo esc_html(count($page_items)); ?></strong> of <strong><?php echo esc_html($total_filtered); ?></strong> inventory records
                </div>
            </form>

            <!-- Inventory Table -->
            <div class="aavni-admin-card">
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">Image</th>
                                <th>Product Title</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Stock Status</th>
                                <th style="width:160px;">Inline Qty Adjustment</th>
                                <th>Quick Restock</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($page_items)) : ?>
                                <?php foreach ($page_items as $item) :
                                    $p = $item['product'];
                                    $pid = $p->get_id();
                                    $image_id = $p->get_image_id();
                                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src();
                                    $qty = $item['qty'];
                                ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo esc_url($image_url); ?>" alt="" style="width:44px; height:54px; object-fit:cover; border-radius:4px; border:1px solid #eee;" />
                                        </td>
                                        <td>
                                            <strong>
                                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $pid . '&action=edit')); ?>" style="color:var(--aavni-admin-dark); text-decoration:none;">
                                                    <?php echo esc_html($p->get_name()); ?>
                                                </a>
                                            </strong>
                                            <div style="font-size:11.5px; color:#888;">Type: <?php echo esc_html(ucfirst($p->get_type())); ?></div>
                                        </td>
                                        <td>
                                            <code><?php echo esc_html($p->get_sku() ?: '—'); ?></code>
                                        </td>
                                        <td>
                                            <?php echo wp_kses_post($p->get_price_html()); ?>
                                        </td>
                                        <td>
                                            <?php if ($item['is_out']) : ?>
                                                <span class="aavni-badge aavni-badge-cancelled">Out of Stock</span>
                                            <?php elseif ($item['is_low']) : ?>
                                                <span class="aavni-badge aavni-badge-under_review">Low Stock</span>
                                            <?php else : ?>
                                                <span class="aavni-badge aavni-badge-delivered">In Stock</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Inline Quick Adjust Input -->
                                        <td>
                                            <?php if ($item['managing']) : ?>
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <input type="number" 
                                                           class="aavni-stock-input" 
                                                           data-product-id="<?php echo esc_attr($pid); ?>" 
                                                           data-nonce="<?php echo esc_attr($stock_nonce); ?>" 
                                                           value="<?php echo esc_attr($qty); ?>" 
                                                           min="0" 
                                                           style="width:80px; height:32px; font-weight:700; text-align:center;" />
                                                    <span id="stock_status_<?php echo esc_attr($pid); ?>"></span>
                                                </div>
                                            <?php else : ?>
                                                <span style="font-size:12px; color:#888;">Not managed</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Quick Restock Actions (+10 / +25) -->
                                        <td>
                                            <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-inventory')); ?>" style="display:inline-flex; gap:6px;">
                                                <?php wp_nonce_field('aavni_bulk_restock_nonce'); ?>
                                                <input type="hidden" name="aavni_action" value="bulk_restock" />
                                                <input type="hidden" name="product_id" value="<?php echo esc_attr($pid); ?>" />

                                                <button type="submit" name="add_quantity" value="10" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Add +10 Units">
                                                    +10
                                                </button>
                                                <button type="submit" name="add_quantity" value="25" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Add +25 Units">
                                                    +25
                                                </button>
                                                <button type="submit" name="add_quantity" value="50" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Add +50 Units">
                                                    +50
                                                </button>
                                            </form>
                                        </td>

                                        <!-- Actions -->
                                        <td style="text-align:right;">
                                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $pid . '&action=edit')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-primary">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:36px; color:#888;">
                                        No inventory records found for this filter.
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
