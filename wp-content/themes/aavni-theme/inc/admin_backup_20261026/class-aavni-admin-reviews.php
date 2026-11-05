<?php
/**
 * AAVNI Product Reviews & Ratings Moderation Hub
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Reviews {

    /**
     * Handle Review Moderation Actions
     */
    public static function handle_actions() {
        if (!current_user_can('moderate_comments')) {
            return;
        }

        if (isset($_GET['action']) && isset($_GET['comment_id'])) {
            $comment_id = absint($_GET['comment_id']);
            check_admin_referer('aavni_review_action_' . $comment_id);

            $action = sanitize_text_field($_GET['action']);

            if ($action === 'approve') {
                wp_set_comment_status($comment_id, 'approve');
            } elseif ($action === 'unapprove') {
                wp_set_comment_status($comment_id, 'hold');
            } elseif ($action === 'spam') {
                wp_spam_comment($comment_id);
            } elseif ($action === 'trash') {
                wp_trash_comment($comment_id);
            }

            wp_safe_redirect(remove_query_arg(['action', 'comment_id', '_wpnonce']));
            exit;
        }
    }

    /**
     * Render Reviews Moderation Screen
     */
    public static function render() {
        if (!current_user_can('moderate_comments')) {
            wp_die(__('You do not have sufficient permissions to moderate reviews.', 'aavni'));
        }

        self::handle_actions();

        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
        $rating_filter = isset($_GET['rating']) ? absint($_GET['rating']) : 0;
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $limit = 15;
        $offset = ($paged - 1) * $limit;

        $args = [
            'type'   => 'review',
            'number' => $limit,
            'offset' => $offset,
        ];

        if ($status === 'approved') {
            $args['status'] = 'approve';
        } elseif ($status === 'pending') {
            $args['status'] = 'hold';
        }

        if ($rating_filter > 0) {
            $args['meta_query'] = [
                [
                    'key'   => 'rating',
                    'value' => $rating_filter,
                ],
            ];
        }

        $comments_query = new WP_Comment_Query();
        $reviews = $comments_query->query($args);

        // Counts
        $total_reviews = wp_count_comments();
        ?>
        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">⭐</div>
                    <div>
                        <h1 class="aavni-header-title">Product Reviews & Ratings Moderation</h1>
                        <p class="aavni-header-subtitle">Moderate customer feedback, verify purchases, manage star ratings & protect store reputation</p>
                    </div>
                </div>
            </div>

            <!-- Submenu Navigation Pills -->
            <?php AAVNI_Admin_Core::render_nav_bar('reviews'); ?>

            <!-- Filter Bar -->
            <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="aavni-filter-bar">
                <input type="hidden" name="page" value="aavni-reviews" />

                <div class="aavni-filter-group">
                    <select name="status">
                        <option value="all" <?php selected($status, 'all'); ?>>All Reviews Status</option>
                        <option value="approved" <?php selected($status, 'approved'); ?>>Approved Only</option>
                        <option value="pending" <?php selected($status, 'pending'); ?>>Pending Approval (Hold)</option>
                    </select>

                    <select name="rating">
                        <option value="0" <?php selected($rating_filter, 0); ?>>All Star Ratings</option>
                        <option value="5" <?php selected($rating_filter, 5); ?>>⭐⭐⭐⭐⭐ (5 Stars)</option>
                        <option value="4" <?php selected($rating_filter, 4); ?>>⭐⭐⭐⭐ (4 Stars)</option>
                        <option value="3" <?php selected($rating_filter, 3); ?>>⭐⭐⭐ (3 Stars)</option>
                        <option value="2" <?php selected($rating_filter, 2); ?>>⭐⭐ (2 Stars)</option>
                        <option value="1" <?php selected($rating_filter, 1); ?>>⭐ (1 Star)</option>
                    </select>

                    <button type="submit" class="aavni-btn aavni-btn-primary aavni-btn-sm">Filter</button>
                    <?php if ($status !== 'all' || $rating_filter) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-reviews')); ?>" class="aavni-btn aavni-btn-secondary aavni-btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Reviews Table -->
            <div class="aavni-admin-card">
                <div class="aavni-table-wrap">
                    <table class="aavni-admin-table">
                        <thead>
                            <tr>
                                <th style="width:100px;">Rating</th>
                                <th>Author / Customer</th>
                                <th>Product Reviewed</th>
                                <th>Review Content</th>
                                <th>Verified Buyer</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th style="text-align:right;">Moderation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reviews)) : ?>
                                <?php foreach ($reviews as $rev) :
                                    $rating = (int) get_comment_meta($rev->comment_ID, 'rating', true);
                                    $prod_id = $rev->comment_post_ID;
                                    $product = wc_get_product($prod_id);
                                    $is_verified = wc_review_is_from_verified_owner($rev->comment_ID);
                                    $is_approved = ($rev->comment_approved == '1');

                                    $nonce_action = 'aavni_review_action_' . $rev->comment_ID;
                                    $approve_url = wp_nonce_url(add_query_arg(['action' => 'approve', 'comment_id' => $rev->comment_ID]), $nonce_action);
                                    $unapprove_url = wp_nonce_url(add_query_arg(['action' => 'unapprove', 'comment_id' => $rev->comment_ID]), $nonce_action);
                                    $spam_url = wp_nonce_url(add_query_arg(['action' => 'spam', 'comment_id' => $rev->comment_ID]), $nonce_action);
                                    $trash_url = wp_nonce_url(add_query_arg(['action' => 'trash', 'comment_id' => $rev->comment_ID]), $nonce_action);
                                ?>
                                    <tr>
                                        <!-- Rating Stars -->
                                        <td>
                                            <span style="color:#F59E0B; font-size:14px; letter-spacing:1px;">
                                                <?php echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating); ?>
                                            </span>
                                            <div style="font-size:11px; color:#888;"><?php echo esc_html($rating); ?> of 5 stars</div>
                                        </td>

                                        <!-- Author -->
                                        <td>
                                            <strong><?php echo esc_html($rev->comment_author); ?></strong>
                                            <div style="font-size:11px; color:#888;"><?php echo esc_html($rev->comment_author_email); ?></div>
                                        </td>

                                        <!-- Product -->
                                        <td>
                                            <?php if ($product) : ?>
                                                <a href="<?php echo esc_url($product->get_permalink()); ?>" target="_blank" style="font-weight:600; color:var(--aavni-admin-terracotta); text-decoration:none;">
                                                    <?php echo esc_html($product->get_name()); ?>
                                                </a>
                                            <?php else : ?>
                                                <span>Product #<?php echo esc_html($prod_id); ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Content -->
                                        <td style="max-width:280px; font-size:13px;">
                                            <p style="margin:0; line-height:1.4;"><?php echo esc_html($rev->comment_content); ?></p>
                                        </td>

                                        <!-- Verified Purchase -->
                                        <td>
                                            <?php if ($is_verified) : ?>
                                                <span class="aavni-badge aavni-badge-delivered" title="Verified Customer Purchase">✓ Verified</span>
                                            <?php else : ?>
                                                <span style="color:#888; font-size:11.5px;">Unverified</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Status -->
                                        <td>
                                            <?php if ($is_approved) : ?>
                                                <span class="aavni-badge aavni-badge-completed">Approved</span>
                                            <?php else : ?>
                                                <span class="aavni-badge aavni-badge-under_review">Pending Hold</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Date -->
                                        <td style="font-size:11.5px; color:#888;">
                                            <?php echo esc_html(date_i18n('d M Y', strtotime($rev->comment_date))); ?>
                                        </td>

                                        <!-- Actions -->
                                        <td style="text-align:right; white-space:nowrap;">
                                            <?php if ($is_approved) : ?>
                                                <a href="<?php echo esc_url($unapprove_url); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Unapprove / Hold">
                                                    Hold
                                                </a>
                                            <?php else : ?>
                                                <a href="<?php echo esc_url($approve_url); ?>" class="aavni-btn aavni-btn-sm aavni-btn-primary" title="Approve Review">
                                                    ✓ Approve
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo esc_url($spam_url); ?>" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Mark as Spam">
                                                Spam
                                            </a>
                                            <a href="<?php echo esc_url($trash_url); ?>" class="aavni-btn aavni-btn-sm aavni-btn-danger" title="Trash">
                                                🗑️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:36px; color:#888;">
                                        No customer product reviews found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
