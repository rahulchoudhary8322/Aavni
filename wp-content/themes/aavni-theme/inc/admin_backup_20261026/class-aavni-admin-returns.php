<?php
/**
 * AAVNI Returns, Exchanges & Refunds Center
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Returns {

    /**
     * Handle Admin Return Review & Status Updates
     */
    public static function handle_actions() {
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_shop_orders')) {
            return;
        }

        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'review_return_request') {
            check_admin_referer('aavni_review_return_nonce');

            global $wpdb;
            $table = $wpdb->prefix . 'aavni_returns';

            $return_id = absint($_POST['return_id']);
            $new_status = sanitize_text_field($_POST['return_status']);
            $admin_notes = sanitize_textarea_field($_POST['admin_notes']);

            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $return_id));
            if (!$existing) {
                return;
            }

            $wpdb->update(
                $table,
                [
                    'status'      => $new_status,
                    'admin_notes' => $admin_notes,
                    'updated_at'  => current_time('mysql'),
                ],
                ['id' => $return_id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            // Add note to WooCommerce order
            $order = wc_get_order($existing->order_id);
            if ($order) {
                $status_label = ucfirst(str_replace('_', ' ', $new_status));
                $note_msg = sprintf(__('Return Request #%d status updated to "%s" by Staff. Note: %s', 'aavni'), $return_id, $status_label, $admin_notes);
                $order->add_order_note($note_msg, false);
            }

            wp_safe_redirect(add_query_arg(['page' => 'aavni-returns', 'updated' => $return_id], admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Render Returns Management Screen
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_shop_orders')) {
            wp_die(__('You do not have sufficient permissions to manage return requests.', 'aavni'));
        }

        self::handle_actions();

        global $wpdb;
        $table = $wpdb->prefix . 'aavni_returns';

        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $limit = 15;
        $offset = ($paged - 1) * $limit;

        $where_clauses = ["1=1"];
        $where_params = [];

        if (!empty($status_filter) && $status_filter !== 'all') {
            $where_clauses[] = "status = %s";
            $where_params[] = $status_filter;
        }

        if (!empty($type_filter) && $type_filter !== 'all') {
            $where_clauses[] = "request_type = %s";
            $where_params[] = $type_filter;
        }

        if (!empty($search)) {
            $where_clauses[] = "(order_id LIKE %s OR reason LIKE %s OR description LIKE %s)";
            $like_term = '%' . $wpdb->esc_like($search) . '%';
            $where_params[] = $like_term;
            $where_params[] = $like_term;
            $where_params[] = $like_term;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Count
        $count_sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";
        $total_items = !empty($where_params) ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $where_params)) : (int) $wpdb->get_var($count_sql);
        $total_pages = ceil($total_items / $limit);

        // Fetch
        $fetch_sql = "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC LIMIT %d OFFSET %d";
        $fetch_params = array_merge($where_params, [$limit, $offset]);
        $requests = $wpdb->get_results($wpdb->prepare($fetch_sql, $fetch_params));

        // Metric Counts
        $submitted_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'submitted'");
        $review_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'under_review'");
        $approved_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'approved'");
        $completed_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'completed'");
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">🔄</div>
                    <div>
                        <h1 class="aavni-header-title">Returns, Exchanges & Refunds Center</h1>
                        <p class="aavni-header-subtitle">Inspect customer return requests, examine photo evidence, record decisions & process replacements</p>
                    </div>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('returns'); ?>

            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px;">
                    <p><?php printf(esc_html__('Return Request #%d updated successfully.', 'aavni'), absint($_GET['updated'])); ?></p>
                </div>
            <?php endif; ?>

            <!-- Return Status KPI Strip -->
            <div class="aavni-kpi-grid">
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-returns&status=submitted')); ?>" class="aavni-kpi-card <?php echo $status_filter === 'submitted' ? 'is-accent-gold' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">New Submissions</span>
                        <div class="aavni-kpi-icon">📥</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($submitted_count); ?></div>
                    <div class="aavni-kpi-meta">Awaiting review</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-returns&status=under_review')); ?>" class="aavni-kpi-card is-accent-warning <?php echo $status_filter === 'under_review' ? 'is-accent-gold' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Under Investigation</span>
                        <div class="aavni-kpi-icon">🔍</div>
                    </div>
                    <div class="aavni-kpi-value" style="color:#D97706;"><?php echo esc_html($review_count); ?></div>
                    <div class="aavni-kpi-meta">QC checking photos</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-returns&status=approved')); ?>" class="aavni-kpi-card is-accent-success <?php echo $status_filter === 'approved' ? 'is-accent-gold' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Approved (Pickup / Refund)</span>
                        <div class="aavni-kpi-icon">✅</div>
                    </div>
                    <div class="aavni-kpi-value" style="color:#059669;"><?php echo esc_html($approved_count); ?></div>
                    <div class="aavni-kpi-meta">Return approved</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-returns&status=completed')); ?>" class="aavni-kpi-card <?php echo $status_filter === 'completed' ? 'is-accent-gold' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Completed & Closed</span>
                        <div class="aavni-kpi-icon">🏁</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($completed_count); ?></div>
                    <div class="aavni-kpi-meta">Resolved requests</div>
                </a>
            </div>

            <!-- Search and Filter Bar -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aavni-filter-bar">
                <input type="hidden" name="page" value="aavni-returns" />

                <div class="aavni-filter-group">
                    <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Search Order # or Reason..." style="width:230px;" />

                    <select name="status">
                        <option value="all"><?php esc_html_e('All Statuses', 'aavni'); ?></option>
                        <option value="submitted" <?php selected($status_filter, 'submitted'); ?>>Submitted</option>
                        <option value="under_review" <?php selected($status_filter, 'under_review'); ?>>Under Review</option>
                        <option value="approved" <?php selected($status_filter, 'approved'); ?>>Approved</option>
                        <option value="rejected" <?php selected($status_filter, 'rejected'); ?>>Rejected</option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                    </select>

                    <select name="type">
                        <option value="all"><?php esc_html_e('All Request Types', 'aavni'); ?></option>
                        <option value="return" <?php selected($type_filter, 'return'); ?>>Return & Refund</option>
                        <option value="exchange" <?php selected($type_filter, 'exchange'); ?>>Size/Color Exchange</option>
                    </select>

                    <button type="submit" class="aavni-btn aavni-btn-primary aavni-btn-sm">Filter</button>
                    <?php if ($search || $status_filter || $type_filter) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-returns')); ?>" class="aavni-btn aavni-btn-secondary aavni-btn-sm">Reset</a>
                    <?php endif; ?>
                </div>

                <div style="font-size:13px; color:var(--aavni-admin-muted);">
                    Showing <strong><?php echo esc_html(count($requests)); ?></strong> of <strong><?php echo esc_html($total_items); ?></strong> requests
                </div>
            </form>

            <!-- Table -->
            <div class="aavni-admin-card">
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th style="width:80px;">Request</th>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Product Claimed</th>
                                <th>Type & Reason</th>
                                <th>Photos</th>
                                <th>Status</th>
                                <th>Admin Notes</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($requests)) : ?>
                                <?php foreach ($requests as $req) :
                                    $order = wc_get_order($req->order_id);
                                    $product = wc_get_product($req->product_id);
                                    $customer = get_user_by('id', $req->customer_id);
                                    $cust_name = $customer ? $customer->display_name : 'Customer #' . $req->customer_id;
                                    $prod_name = $product ? $product->get_name() : 'Product #' . $req->product_id;
                                    $photos = json_decode($req->photos, true) ?: [];
                                ?>
                                    <tr>
                                        <!-- Request ID & Date -->
                                        <td>
                                            <strong>#<?php echo esc_html($req->id); ?></strong>
                                            <div style="font-size:11px; color:#888;"><?php echo esc_html(date_i18n('d M Y', strtotime($req->created_at))); ?></div>
                                        </td>

                                        <!-- Order Number -->
                                        <td>
                                            <?php if ($order) : ?>
                                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $req->order_id . '&action=edit')); ?>" style="font-weight:700; color:var(--aavni-admin-terracotta); text-decoration:none;">
                                                    #<?php echo esc_html($order->get_order_number()); ?>
                                                </a>
                                                <div style="font-size:11px; color:#888;"><?php echo wc_price($order->get_total()); ?></div>
                                            <?php else : ?>
                                                <span>#<?php echo esc_html($req->order_id); ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Customer -->
                                        <td>
                                            <div style="font-weight:600;"><?php echo esc_html($cust_name); ?></div>
                                            <div style="font-size:11px; color:#888;"><?php echo esc_html($customer ? $customer->user_email : ''); ?></div>
                                        </td>

                                        <!-- Product -->
                                        <td>
                                            <div style="font-weight:600; font-size:13px; max-width:200px;"><?php echo esc_html($prod_name); ?></div>
                                            <?php if ($req->quantity > 0) : ?>
                                                <div style="font-size:11px; color:#888;">Qty: <?php echo esc_html($req->quantity); ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Type & Reason -->
                                        <td>
                                            <span class="aavni-badge aavni-badge-<?php echo $req->request_type === 'exchange' ? 'shipped' : 'under_review'; ?>">
                                                <?php echo esc_html(strtoupper($req->request_type)); ?>
                                            </span>
                                            <div style="font-weight:600; font-size:12px; margin-top:4px;"><?php echo esc_html($req->reason); ?></div>
                                            <?php if (!empty($req->description)) : ?>
                                                <div style="font-size:11.5px; color:#666; max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?php echo esc_attr($req->description); ?>">
                                                    &ldquo;<?php echo esc_html($req->description); ?>&rdquo;
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Photos Preview -->
                                        <td>
                                            <?php if (!empty($photos)) : ?>
                                                <div class="aavni-photo-gallery">
                                                    <?php foreach ($photos as $img_url) : ?>
                                                        <a href="<?php echo esc_url($img_url); ?>" target="_blank" title="Inspect Photo">
                                                            <img src="<?php echo esc_url($img_url); ?>" class="aavni-photo-thumb" alt="Evidence" />
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else : ?>
                                                <span style="font-size:11px; color:#999;">None</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Status Badge -->
                                        <td>
                                            <span class="aavni-badge aavni-badge-<?php echo esc_attr($req->status); ?>">
                                                <?php echo esc_html(str_replace('_', ' ', $req->status)); ?>
                                            </span>
                                        </td>

                                        <!-- Admin Notes -->
                                        <td style="font-size:12px; max-width:180px;">
                                            <?php if (!empty($req->admin_notes)) : ?>
                                                <span style="color:#2B2B2B;"><?php echo esc_html($req->admin_notes); ?></span>
                                            <?php else : ?>
                                                <span style="color:#999; font-style:italic;">No notes</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Actions -->
                                        <td style="text-align:right; white-space:nowrap;">
                                            <button type="button" 
                                                    class="aavni-btn aavni-btn-sm aavni-btn-primary aavni-trigger-return-modal"
                                                    data-return-id="<?php echo esc_attr($req->id); ?>"
                                                    data-order-id="<?php echo esc_attr($req->order_id); ?>"
                                                    data-customer="<?php echo esc_attr($cust_name); ?>"
                                                    data-product="<?php echo esc_attr($prod_name); ?>"
                                                    data-type="<?php echo esc_attr($req->request_type); ?>"
                                                    data-reason="<?php echo esc_attr($req->reason); ?>"
                                                    data-description="<?php echo esc_attr($req->description); ?>"
                                                    data-status="<?php echo esc_attr($req->status); ?>"
                                                    data-notes="<?php echo esc_attr($req->admin_notes); ?>"
                                                    data-photos='<?php echo esc_attr(wp_json_encode($photos)); ?>'>
                                                🔍 Review Request
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="9" style="text-align:center; padding:36px; color:#888;">
                                        No return or exchange requests found for this filter.
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

            <!-- Review Return Modal -->
            <div id="aavniReturnModal" class="aavni-modal-overlay">
                <div class="aavni-modal-box">
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-returns')); ?>">
                        <?php wp_nonce_field('aavni_review_return_nonce'); ?>
                        <input type="hidden" name="aavni_action" value="review_return_request" />
                        <input type="hidden" name="return_id" id="aavni_return_modal_id" value="" />

                        <div class="aavni-modal-header">
                            <h3 class="aavni-modal-title" id="aavni_return_modal_title">Review Return</h3>
                            <button type="button" class="aavni-modal-close" data-aavni-dismiss="modal">&times;</button>
                        </div>

                        <div class="aavni-modal-body">
                            <!-- Summary details box -->
                            <div style="background:var(--aavni-admin-bg); padding:14px; border-radius:8px; border:1px solid var(--aavni-admin-border); margin-bottom:18px; font-size:13px;">
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:8px;">
                                    <div><strong>Customer:</strong> <span id="aavni_return_modal_customer"></span></div>
                                    <div><strong>Claim Type:</strong> <span id="aavni_return_modal_type" style="font-weight:700; color:var(--aavni-admin-terracotta);"></span></div>
                                </div>
                                <div style="margin-bottom:8px;"><strong>Product:</strong> <span id="aavni_return_modal_product"></span></div>
                                <div style="margin-bottom:8px;"><strong>Reason:</strong> <span id="aavni_return_modal_reason" style="font-weight:600;"></span></div>
                                <div><strong>Customer Remarks:</strong> <p id="aavni_return_modal_desc" style="margin:4px 0 0; color:#555; font-style:italic;"></p></div>
                            </div>

                            <!-- Photos Evidence -->
                            <div style="margin-bottom:18px;">
                                <label><strong>Customer Uploaded Photos:</strong></label>
                                <div id="aavni_return_modal_photos" style="margin-top:8px;"></div>
                            </div>

                            <!-- Status Dropdown -->
                            <p>
                                <label for="aavni_return_modal_status"><strong>Update Status:</strong></label><br>
                                <select name="return_status" id="aavni_return_modal_status" style="width:100%; margin-top:6px;">
                                    <option value="submitted">📥 Submitted</option>
                                    <option value="under_review">🔍 Under Review</option>
                                    <option value="approved">✅ Approved (Initiate Pickup)</option>
                                    <option value="rejected">❌ Rejected</option>
                                    <option value="completed">🏁 Completed & Closed</option>
                                </select>
                            </p>

                            <!-- Customer-Facing Note -->
                            <p>
                                <label for="aavni_return_modal_notes"><strong>Customer-Facing Note / Store Decision:</strong></label><br>
                                <textarea name="admin_notes" id="aavni_return_modal_notes" rows="3" placeholder="e.g. Return approved. Our courier partner will pick up the parcel within 2 business days. Please keep tags intact." style="width:100%; margin-top:6px; border:1px solid var(--aavni-admin-border); border-radius:6px; padding:8px;"></textarea>
                                <span style="font-size:11.5px; color:#888;">This note will immediately display to the customer in their My Account > Returns tab.</span>
                            </p>
                        </div>

                        <div class="aavni-modal-footer">
                            <button type="button" class="aavni-btn aavni-btn-secondary" data-aavni-dismiss="modal">Cancel</button>
                            <button type="submit" class="aavni-btn aavni-btn-primary">Save Decision & Notify Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
