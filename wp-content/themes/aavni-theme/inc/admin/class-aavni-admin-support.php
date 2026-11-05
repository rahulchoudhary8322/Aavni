<?php
/**
 * AAVNI Customer Support & Helpdesk Hub
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Support {

    /**
     * Handle Admin Support Actions (Reply, Status/Priority Update)
     */
    public static function handle_actions() {
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_shop_orders')) {
            return;
        }

        if (isset($_POST['aavni_action']) && $_POST['aavni_action'] === 'reply_support_ticket') {
            check_admin_referer('aavni_support_ticket_nonce');

            global $wpdb;
            $table = $wpdb->prefix . 'aavni_support_tickets';

            $ticket_id = absint($_POST['ticket_id']);
            $new_status = sanitize_text_field($_POST['ticket_status']);
            $new_priority = sanitize_text_field($_POST['ticket_priority']);
            $reply_message = sanitize_textarea_field($_POST['admin_reply']);

            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $ticket_id));
            if (!$existing) {
                return;
            }

            $replies = json_decode($existing->replies, true) ?: [];

            if (!empty($reply_message)) {
                $replies[] = [
                    'author'  => wp_get_current_user()->display_name . ' (AAVNI Customer Care)',
                    'message' => $reply_message,
                    'time'    => current_time('mysql'),
                ];
            }

            $wpdb->update(
                $table,
                [
                    'status'     => $new_status,
                    'priority'   => $new_priority,
                    'replies'    => wp_json_encode($replies),
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $ticket_id],
                ['%s', '%s', '%s', '%s'],
                ['%d']
            );

            // Send notification to customer
            if (!empty($reply_message) && class_exists('AAVNI_Admin_Notifications')) {
                AAVNI_Admin_Notifications::notify_ticket_reply($ticket_id, $reply_message, $new_status);
            }

            wp_safe_redirect(add_query_arg(['page' => 'aavni-support-tickets', 'updated' => $ticket_id], admin_url('admin.php')));
            exit;
        }
    }

    /**
     * Render Support Desk Screen
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_shop_orders')) {
            wp_die(__('You do not have sufficient permissions to access the customer support desk.', 'aavni'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'aavni_support_tickets';

        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $priority_filter = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : '';
        $cat_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $view_ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $limit = 15;
        $offset = ($paged - 1) * $limit;

        $where_clauses = ["1=1"];
        $where_params = [];

        if (!empty($status_filter) && $status_filter !== 'all') {
            $where_clauses[] = "status = %s";
            $where_params[] = $status_filter;
        }
        if (!empty($priority_filter) && $priority_filter !== 'all') {
            $where_clauses[] = "priority = %s";
            $where_params[] = $priority_filter;
        }
        if (!empty($cat_filter) && $cat_filter !== 'all') {
            $where_clauses[] = "category = %s";
            $where_params[] = $cat_filter;
        }
        if (!empty($search)) {
            $where_clauses[] = "(ticket_code LIKE %s OR subject LIKE %s OR description LIKE %s)";
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where_params[] = $like;
            $where_params[] = $like;
            $where_params[] = $like;
        }

        $where_sql = implode(' AND ', $where_clauses);
        $total_items = !empty($where_params) ? (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE $where_sql", $where_params)) : (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where_sql");
        $total_pages = ceil($total_items / $limit);

        $fetch_sql = "SELECT * FROM $table WHERE $where_sql ORDER BY id DESC LIMIT %d OFFSET %d";
        $fetch_params = array_merge($where_params, [$limit, $offset]);
        $tickets = $wpdb->get_results($wpdb->prepare($fetch_sql, $fetch_params));

        // Metric Counts
        $open_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'open'");
        $in_prog_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'in_progress'");
        $resolved_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'resolved'");
        $high_pri_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE priority = 'high' AND status != 'closed'");

        // Selected Ticket for conversation view
        $selected_ticket = null;
        if ($view_ticket_id > 0) {
            $selected_ticket = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $view_ticket_id));
        } elseif (!empty($tickets)) {
            $selected_ticket = $tickets[0];
        }
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">💬</div>
                    <div>
                        <h1 class="aavni-header-title">Customer Support & Helpdesk</h1>
                        <p class="aavni-header-subtitle">Manage customer inquiries, reply to support tickets & resolve order complaints</p>
                    </div>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('support-tickets'); ?>

            <?php if (isset($_GET['updated'])) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:20px;">
                    <p><?php esc_html_e('Support ticket response recorded and conversation updated in customer panel.', 'aavni'); ?></p>
                </div>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="aavni-kpi-grid">
                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-support-tickets&status=open')); ?>" class="aavni-kpi-card <?php echo $open_count > 0 ? 'is-accent-terracotta' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Open Tickets</span>
                        <div class="aavni-kpi-icon">📬</div>
                    </div>
                    <div class="aavni-kpi-value"><?php echo esc_html($open_count); ?></div>
                    <div class="aavni-kpi-meta">Awaiting first response</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-support-tickets&status=in_progress')); ?>" class="aavni-kpi-card is-accent-warning">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">In Progress</span>
                        <div class="aavni-kpi-icon">⏳</div>
                    </div>
                    <div class="aavni-kpi-value" style="color:#D97706;"><?php echo esc_html($in_prog_count); ?></div>
                    <div class="aavni-kpi-meta">Ongoing support chats</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-support-tickets&priority=high')); ?>" class="aavni-kpi-card <?php echo $high_pri_count > 0 ? 'is-accent-danger' : ''; ?>">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">High Priority</span>
                        <div class="aavni-kpi-icon">🔥</div>
                    </div>
                    <div class="aavni-kpi-value" style="color:#DC2626;"><?php echo esc_html($high_pri_count); ?></div>
                    <div class="aavni-kpi-meta">Urgent issues</div>
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-support-tickets&status=resolved')); ?>" class="aavni-kpi-card is-accent-success">
                    <div class="aavni-kpi-top">
                        <span class="aavni-kpi-label">Resolved</span>
                        <div class="aavni-kpi-icon">✅</div>
                    </div>
                    <div class="aavni-kpi-value" style="color:#059669;"><?php echo esc_html($resolved_count); ?></div>
                    <div class="aavni-kpi-meta">Closed inquiries</div>
                </a>
            </div>

            <!-- Main Support Desk Layout: 2-Columns (List on left, Conversation thread on right) -->
            <div class="aavni-grid-2" style="grid-template-columns: 1.2fr 1fr;">
                <!-- Left Column: Tickets Queue -->
                <div>
                    <!-- Filter bar -->
                    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aavni-filter-bar" style="margin-bottom:14px; padding:10px 14px;">
                        <input type="hidden" name="page" value="aavni-support-tickets" />

                        <div class="aavni-filter-group">
                            <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="Code, Subject..." style="width:160px; height:32px; font-size:12px;" />

                            <select name="status" style="height:32px; font-size:12px;">
                                <option value="all">All Statuses</option>
                                <option value="open" <?php selected($status_filter, 'open'); ?>>Open</option>
                                <option value="in_progress" <?php selected($status_filter, 'in_progress'); ?>>In Progress</option>
                                <option value="resolved" <?php selected($status_filter, 'resolved'); ?>>Resolved</option>
                                <option value="closed" <?php selected($status_filter, 'closed'); ?>>Closed</option>
                            </select>

                            <select name="priority" style="height:32px; font-size:12px;">
                                <option value="all">All Priorities</option>
                                <option value="low" <?php selected($priority_filter, 'low'); ?>>Low</option>
                                <option value="medium" <?php selected($priority_filter, 'medium'); ?>>Medium</option>
                                <option value="high" <?php selected($priority_filter, 'high'); ?>>High</option>
                            </select>

                            <button type="submit" class="aavni-btn aavni-btn-primary aavni-btn-sm">Filter</button>
                        </div>
                    </form>

                    <!-- Tickets List -->
                    <div class="aavni-admin-card">
                        <div class="aavni-table-wrap">
                            <table class="aavni-admin-table" style="font-size:12.5px;">
                                <thead>
                                    <tr>
                                        <th>Ticket Code</th>
                                        <th>Customer</th>
                                        <th>Subject & Category</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($tickets)) : ?>
                                        <?php foreach ($tickets as $tkt) :
                                            $cust = get_user_by('id', $tkt->customer_id);
                                            $cust_name = $cust ? $cust->display_name : 'Customer #' . $tkt->customer_id;
                                            $is_active_tkt = $selected_ticket && $selected_ticket->id === $tkt->id;
                                        ?>
                                            <tr style="<?php echo $is_active_tkt ? 'background:#FAF4F2;' : ''; ?>">
                                                <td>
                                                    <strong><code><?php echo esc_html($tkt->ticket_code); ?></code></strong>
                                                    <div style="font-size:11px; color:#888;"><?php echo esc_html(date_i18n('d M', strtotime($tkt->created_at))); ?></div>
                                                </td>
                                                <td>
                                                    <div style="font-weight:600;"><?php echo esc_html($cust_name); ?></div>
                                                    <?php if ($tkt->order_id) : ?>
                                                        <div style="font-size:11px; color:var(--aavni-admin-terracotta);">Order #<?php echo esc_html($tkt->order_id); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="font-weight:600; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                        <?php echo esc_html($tkt->subject); ?>
                                                    </div>
                                                    <span class="aavni-badge aavni-badge-<?php echo esc_attr($tkt->priority); ?>" style="font-size:10px; padding:2px 6px;">
                                                        <?php echo esc_html(strtoupper($tkt->priority)); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="aavni-badge aavni-badge-<?php echo esc_attr($tkt->status); ?>" style="font-size:10px; padding:2px 6px;">
                                                        <?php echo esc_html(str_replace('_', ' ', $tkt->status)); ?>
                                                    </span>
                                                </td>
                                                <td style="white-space:nowrap;">
                                                    <a href="<?php echo esc_url(add_query_arg('ticket_id', $tkt->id)); ?>" class="aavni-btn aavni-btn-sm <?php echo $is_active_tkt ? 'aavni-btn-primary' : 'aavni-btn-secondary'; ?>">
                                                        Open &rarr;
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5" style="text-align:center; padding:30px; color:#888;">
                                                No support tickets found.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Conversation Thread & Reply Box -->
                <div>
                    <?php if ($selected_ticket) :
                        $cust = get_user_by('id', $selected_ticket->customer_id);
                        $cust_name = $cust ? $cust->display_name : 'Customer #' . $selected_ticket->customer_id;
                        $replies = json_decode($selected_ticket->replies, true) ?: [];
                    ?>
                        <div class="aavni-admin-card">
                            <div class="aavni-card-header" style="background:#FAF8F5;">
                                <div>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <h3 class="aavni-card-title"><?php echo esc_html($selected_ticket->ticket_code); ?></h3>
                                        <span class="aavni-badge aavni-badge-<?php echo esc_attr($selected_ticket->status); ?>">
                                            <?php echo esc_html(str_replace('_', ' ', $selected_ticket->status)); ?>
                                        </span>
                                    </div>
                                    <div class="aavni-card-subtitle" style="margin-top:4px;">
                                        <strong><?php echo esc_html($selected_ticket->subject); ?></strong> &bull; Category: <?php echo esc_html(ucfirst($selected_ticket->category)); ?>
                                    </div>
                                </div>
                                <?php if ($selected_ticket->order_id) : ?>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $selected_ticket->order_id . '&action=edit')); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary">
                                        Order #<?php echo esc_html($selected_ticket->order_id); ?> ↗
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="aavni-card-body">
                                <!-- Conversation Thread Container -->
                                <div class="aavni-thread-box">
                                    <!-- Original message -->
                                    <div class="aavni-thread-bubble aavni-thread-customer">
                                        <div class="aavni-thread-meta">
                                            <strong>👤 <?php echo esc_html($cust_name); ?></strong>
                                            <span><?php echo esc_html(date_i18n('d M Y, h:i A', strtotime($selected_ticket->created_at))); ?></span>
                                        </div>
                                        <div><?php echo nl2br(esc_html($selected_ticket->description)); ?></div>
                                    </div>

                                    <!-- Replies -->
                                    <?php if (!empty($replies)) : ?>
                                        <?php foreach ($replies as $rep) : ?>
                                            <div class="aavni-thread-bubble aavni-thread-admin">
                                                <div class="aavni-thread-meta">
                                                    <strong>🛡️ <?php echo esc_html($rep['author']); ?></strong>
                                                    <span><?php echo esc_html(isset($rep['time']) ? date_i18n('d M Y, h:i A', strtotime($rep['time'])) : ''); ?></span>
                                                </div>
                                                <div><?php echo nl2br(esc_html($rep['message'])); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <!-- Reply Form -->
                                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aavni-support-tickets')); ?>">
                                    <?php wp_nonce_field('aavni_support_ticket_nonce'); ?>
                                    <input type="hidden" name="aavni_action" value="reply_support_ticket" />
                                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr($selected_ticket->id); ?>" />

                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                                        <div>
                                            <label for="rep_status" style="font-size:12px; font-weight:700;">Status:</label>
                                            <select name="ticket_status" id="rep_status" style="width:100%; margin-top:4px; height:34px; font-size:12.5px;">
                                                <option value="open" <?php selected($selected_ticket->status, 'open'); ?>>Open</option>
                                                <option value="in_progress" <?php selected($selected_ticket->status, 'in_progress'); ?>>In Progress</option>
                                                <option value="resolved" <?php selected($selected_ticket->status, 'resolved'); ?>>Resolved</option>
                                                <option value="closed" <?php selected($selected_ticket->status, 'closed'); ?>>Closed</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="rep_priority" style="font-size:12px; font-weight:700;">Priority:</label>
                                            <select name="ticket_priority" id="rep_priority" style="width:100%; margin-top:4px; height:34px; font-size:12.5px;">
                                                <option value="low" <?php selected($selected_ticket->priority, 'low'); ?>>Low</option>
                                                <option value="medium" <?php selected($selected_ticket->priority, 'medium'); ?>>Medium</option>
                                                <option value="high" <?php selected($selected_ticket->priority, 'high'); ?>>High</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="rep_msg" style="font-size:12px; font-weight:700;">Send Official Support Reply:</label>
                                        <textarea name="admin_reply" id="rep_msg" rows="4" placeholder="Type response to customer... This message will instantly appear in their My Account > Help & Support tab." style="width:100%; margin-top:6px; border:1px solid var(--aavni-admin-border); border-radius:6px; padding:10px; font-size:13px;"></textarea>
                                    </div>

                                    <div style="margin-top:12px; text-align:right;">
                                        <button type="submit" class="aavni-btn aavni-btn-primary">
                                            ✉️ Send Reply & Update Status
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="aavni-admin-card" style="padding:40px; text-align:center; color:#888;">
                            Select a ticket from the left queue to view and send replies.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
