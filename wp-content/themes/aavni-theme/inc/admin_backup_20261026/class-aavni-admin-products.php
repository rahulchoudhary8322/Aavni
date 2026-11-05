<?php
/**
 * AAVNI Catalog & Product Management Hub
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Products {

    /**
     * Handle Product Actions (Quick Add, Trash)
     */
    public static function handle_actions() {
        if (!current_user_can('edit_products')) {
            return;
        }

        // Quick Add Product Handler
        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'quick_add_product') {
            check_admin_referer('aavni_quick_add_product_nonce');

            $title = sanitize_text_field($_POST['product_title']);
            $sku = sanitize_text_field($_POST['product_sku']);
            $regular_price = sanitize_text_field($_POST['regular_price']);
            $sale_price = sanitize_text_field($_POST['sale_price']);
            $stock_qty = isset($_POST['stock_qty']) ? intval($_POST['stock_qty']) : 10;
            $category_id = isset($_POST['product_cat']) ? absint($_POST['product_cat']) : 0;
            $short_desc = sanitize_textarea_field($_POST['short_description']);
            $status = isset($_POST['product_status']) && in_array($_POST['product_status'], ['publish', 'draft'], true) ? $_POST['product_status'] : 'publish';

            if (empty($title) || empty($regular_price)) {
                wp_die(__('Product Name and Regular Price are required.', 'aavni'));
            }

            // SKU validation if provided
            if (!empty($sku)) {
                $existing_id = wc_get_product_id_by_sku($sku);
                if ($existing_id) {
                    wp_die(sprintf(__('SKU "%s" is already assigned to Product #%d. Please use a unique SKU.', 'aavni'), esc_html($sku), $existing_id));
                }
            }

            $product = new WC_Product_Simple();
            $product->set_name($title);
            $product->set_status($status);
            $product->set_regular_price($regular_price);
            if (!empty($sale_price) && (float) $sale_price < (float) $regular_price) {
                $product->set_sale_price($sale_price);
                $product->set_price($sale_price);
            } else {
                $product->set_price($regular_price);
            }

            if (!empty($sku)) {
                $product->set_sku($sku);
            }

            $product->set_manage_stock(true);
            $product->set_stock_quantity($stock_qty);
            $product->set_stock_status($stock_qty > 0 ? 'instock' : 'outofstock');
            $product->set_short_description($short_desc);

            if ($category_id > 0) {
                $product->set_category_ids([$category_id]);
            }

            $new_product_id = $product->save();

            wp_safe_redirect(add_query_arg(['page' => 'aavni-products', 'added' => $new_product_id], admin_url('admin.php')));
            exit;
        }

        // Trash Product Handler
        if (isset($_GET['action']) && $_GET['action'] === 'trash' && isset($_GET['product_id'])) {
            $product_id = absint($_GET['product_id']);
            check_admin_referer('aavni_trash_product_' . $product_id);

            wp_trash_post($product_id);

            wp_safe_redirect(add_query_arg(['page' => 'aavni-products', 'trashed' => $product_id], admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Render Catalog & Product Management Screen
     */
    public static function render() {
        if (!current_user_can('edit_products')) {
            wp_die(__('You do not have sufficient permissions to manage store catalog.', 'aavni'));
        }

        self::handle_actions();

        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $cat_filter = isset($_GET['cat']) ? absint($_GET['cat']) : 0;
        $stock_filter = isset($_GET['stock']) ? sanitize_text_field($_GET['stock']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $limit = 15;

        $args = [
            'limit'    => $limit,
            'page'     => $paged,
            'paginate' => true,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ];

        if (!empty($status_filter)) {
            $args['status'] = $status_filter;
        }
        if (!empty($stock_filter)) {
            $args['stock_status'] = $stock_filter;
        }
        if ($cat_filter > 0) {
            $term = get_term($cat_filter, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $args['category'] = [$term->slug];
            }
        }
        if (!empty($search)) {
            $args['s'] = $search;
        }

        $results = wc_get_products($args);
        $products = is_object($results) && isset($results->products) ? $results->products : (array) $results;
        $total_products = is_object($results) && isset($results->total) ? $results->total : count($products);
        $total_pages = is_object($results) && isset($results->max_num_pages) ? $results->max_num_pages : 1;

        // Categories list
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">👗</div>
                    <div>
                        <h1 class="aavni-header-title">Catalog & Product Hub</h1>
                        <p class="aavni-header-subtitle">Manage store catalog, prices, categories, inventory stock & storefront visibility</p>
                    </div>
                </div>
                <div class="aavni-header-actions">
                    <button type="button" class="aavni-btn aavni-btn-gold" data-aavni-modal="aavniQuickAddModal">
                        ⚡ Quick Add Product
                    </button>
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=product')); ?>" class="aavni-btn aavni-btn-primary">
                        + Full WooCommerce Editor
                    </a>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('products'); ?>

            <?php if (isset($_GET['added'])) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px;">
                    <p><?php printf(esc_html__('Product #%d created successfully and published to storefront.', 'aavni'), absint($_GET['added'])); ?></p>
                </div>
            <?php elseif (isset($_GET['trashed'])) : ?>
                <div class="notice notice-warning is-dismissible" style="margin-bottom:20px;">
                    <p><?php printf(esc_html__('Product #%d moved to trash.', 'aavni'), absint($_GET['trashed'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Search & Filter Bar -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aavni-filter-bar">
                <input type="hidden" name="page" value="aavni-products" />

                <div class="aavni-filter-group">
                    <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search product name or SKU..." style="width:230px;" />

                    <!-- Category Filter -->
                    <select name="cat">
                        <option value="0"><?php esc_html_e('All Categories', 'aavni'); ?></option>
                        <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected($cat_filter, $cat->term_id); ?>>
                                    <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <!-- Stock Status Filter -->
                    <select name="stock">
                        <option value=""><?php esc_html_e('All Stock Statuses', 'aavni'); ?></option>
                        <option value="instock" <?php selected($stock_filter, 'instock'); ?>>In Stock</option>
                        <option value="outofstock" <?php selected($stock_filter, 'outofstock'); ?>>Out of Stock</option>
                    </select>

                    <!-- Status Filter -->
                    <select name="status">
                        <option value=""><?php esc_html_e('All Publication Statuses', 'aavni'); ?></option>
                        <option value="publish" <?php selected($status_filter, 'publish'); ?>>Published</option>
                        <option value="draft" <?php selected($status_filter, 'draft'); ?>>Draft</option>
                    </select>

                    <button type="submit" class="aavni-btn aavni-btn-primary aavni-btn-sm">Filter</button>
                    <?php if ($search || $cat_filter || $stock_filter || $status_filter) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-products')); ?>" class="aavni-btn aavni-btn-secondary aavni-btn-sm">Reset</a>
                    <?php endif; ?>
                </div>

                <div style="font-size:13px; color:var(--aavni-admin-muted);">
                    Showing <strong><?php echo esc_html(count($products)); ?></strong> of <strong><?php echo esc_html($total_products); ?></strong> products
                </div>
            </form>

            <!-- Products Table -->
            <div class="aavni-admin-card">
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">Image</th>
                                <th>Product Name & Type</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Stock Status & Qty</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)) : ?>
                                <?php foreach ($products as $prod) :
                                    $prod_id = $prod->get_id();
                                    $sku = $prod->get_sku() ?: '—';
                                    $price_html = $prod->get_price_html();
                                    $stock_status = $prod->get_stock_status();
                                    $stock_qty = $prod->managing_stock() ? $prod->get_stock_quantity() : null;
                                    $cats = wc_get_product_category_list($prod_id, ', ');
                                    $image_id = $prod->get_image_id();
                                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src();
                                    $trash_url = wp_nonce_url(admin_url('admin.php?page=aavni-products&action=trash&product_id=' . $prod_id), 'aavni_trash_product_' . $prod_id);
                                ?>
                                    <tr>
                                        <!-- Thumbnail -->
                                        <td>
                                            <img src="<?php echo esc_url($image_url); ?>" alt="" style="width:48px; height:58px; object-fit:cover; border-radius:4px; border:1px solid #eee;" />
                                        </td>

                                        <!-- Product Name -->
                                        <td>
                                            <strong>
                                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $prod_id . '&action=edit')); ?>" style="color:var(--aavni-admin-dark); text-decoration:none;">
                                                    <?php echo esc_html($prod->get_name()); ?>
                                                </a>
                                            </strong>
                                            <div style="font-size:11.5px; color:#888;">
                                                ID: #<?php echo esc_html($prod_id); ?> &bull; Type: <?php echo esc_html(ucfirst($prod->get_type())); ?>
                                            </div>
                                        </td>

                                        <!-- SKU -->
                                        <td>
                                            <code><?php echo esc_html($sku); ?></code>
                                        </td>

                                        <!-- Price -->
                                        <td>
                                            <strong><?php echo wp_kses_post($price_html); ?></strong>
                                        </td>

                                        <!-- Stock Status & Quantity -->
                                        <td>
                                            <?php if ($stock_status === 'instock') : ?>
                                                <span class="aavni-badge aavni-badge-delivered">In Stock</span>
                                                <?php if ($stock_qty !== null) : ?>
                                                    <span style="font-size:12px; font-weight:600; margin-left:4px;">(<?php echo esc_html($stock_qty); ?>)</span>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                <span class="aavni-badge aavni-badge-cancelled">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Categories -->
                                        <td style="font-size:12.5px; color:var(--aavni-admin-muted);">
                                            <?php echo wp_kses_post($cats ?: 'Uncategorized'); ?>
                                        </td>

                                        <!-- Publication Status -->
                                        <td>
                                            <span class="aavni-badge aavni-badge-<?php echo $prod->get_status() === 'publish' ? 'confirmed' : 'low'; ?>">
                                                <?php echo esc_html(ucfirst($prod->get_status())); ?>
                                            </span>
                                        </td>

                                        <!-- Actions -->
                                        <td style="text-align:right; white-space:nowrap;">
                                            <a href="<?php echo esc_url($prod->get_permalink()); ?>" target="_blank" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="View on Storefront">
                                                👁️
                                            </a>
                                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $prod_id . '&action=edit')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-primary" title="Edit in WooCommerce">
                                                ✏️ Edit
                                            </a>
                                            <a href="<?php echo esc_url($trash_url); ?>" onclick="return confirm('Move this product to trash? Historical order items will be preserved.');" class="aavni-btn aavni-btn-sm aavni-btn-danger" title="Trash Product">
                                                🗑️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:36px; color:#888;">
                                        No products found matching the criteria.
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

            <!-- Quick Add Product Modal -->
            <div id="aavniQuickAddModal" class="aavni-modal-overlay">
                <div class="aavni-modal-box">
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-products')); ?>">
                        <?php wp_nonce_field('aavni_quick_add_product_nonce'); ?>
                        <input type="hidden" name="aavni_action" value="quick_add_product" />

                        <div class="aavni-modal-header">
                            <h3 class="aavni-modal-title">⚡ Quick Add Product to Storefront</h3>
                            <button type="button" class="aavni-modal-close" data-aavni-dismiss="modal">&times;</button>
                        </div>

                        <div class="aavni-modal-body">
                            <p style="margin-top:0; font-size:13px; color:var(--aavni-admin-muted);">
                                Creates a real WooCommerce product that will immediately appear on the shop, categories, search, cart, and checkout.
                            </p>

                            <p>
                                <label for="qa_title"><strong>Product Title *:</strong></label><br>
                                <input type="text" name="product_title" id="qa_title" required placeholder="e.g. AAVNI Golden Radiance Embroidered Silk Kurti" style="width:100%; margin-top:6px;" />
                            </p>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                                <div>
                                    <label for="qa_sku"><strong>SKU:</strong></label><br>
                                    <input type="text" name="product_sku" id="qa_sku" placeholder="e.g. AAV-LK-010" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="qa_cat"><strong>Category:</strong></label><br>
                                    <select name="product_cat" id="qa_cat" style="width:100%; margin-top:6px;">
                                        <option value="0">Select Category...</option>
                                        <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                                            <?php foreach ($categories as $cat) : ?>
                                                <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-top:12px;">
                                <div>
                                    <label for="qa_reg_price"><strong>Regular Price (₹) *:</strong></label><br>
                                    <input type="number" step="1" min="0" name="regular_price" id="qa_reg_price" required placeholder="e.g. 1299" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="qa_sale_price"><strong>Sale Price (₹):</strong></label><br>
                                    <input type="number" step="1" min="0" name="sale_price" id="qa_sale_price" placeholder="e.g. 899" style="width:100%; margin-top:6px;" />
                                </div>
                                <div>
                                    <label for="qa_stock"><strong>Stock Qty:</strong></label><br>
                                    <input type="number" step="1" min="0" name="stock_qty" id="qa_stock" value="15" style="width:100%; margin-top:6px;" />
                                </div>
                            </div>

                            <p style="margin-top:14px;">
                                <label for="qa_desc"><strong>Short Description / Highlights:</strong></label><br>
                                <textarea name="short_description" id="qa_desc" rows="3" placeholder="Handcrafted with pure cotton fabric, artisanal embroidery, lightweight and breathable..." style="width:100%; margin-top:6px; border:1px solid var(--aavni-admin-border); border-radius:6px; padding:8px;"></textarea>
                            </p>

                            <p>
                                <label for="qa_status"><strong>Status:</strong></label><br>
                                <select name="product_status" id="qa_status" style="width:100%; margin-top:6px;">
                                    <option value="publish">Publish Immediately to Storefront</option>
                                    <option value="draft">Save as Draft (Private)</option>
                                </select>
                            </p>
                        </div>

                        <div class="aavni-modal-footer">
                            <button type="button" class="aavni-btn aavni-btn-secondary" data-aavni-dismiss="modal">Cancel</button>
                            <button type="submit" class="aavni-btn aavni-btn-primary">Create & Publish Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
