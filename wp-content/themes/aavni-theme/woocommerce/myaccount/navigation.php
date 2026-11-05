<?php
/**
 * AAVNI My Account Navigation Sidebar Override
 *
 * @package AAVNI
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$first_name = $current_user->first_name ?: $current_user->display_name;
$initials = strtoupper(substr($first_name, 0, 1) . substr($current_user->last_name, 0, 1));
if (empty($initials)) {
    $initials = strtoupper(substr($current_user->user_login, 0, 2));
}

$icons = [
    'dashboard'       => '📊',
    'orders'          => '🛍️',
    'track-order'     => '🚚',
    'edit-address'    => '📍',
    'returns'         => '🔄',
    'my-reviews'      => '⭐',
    'help-support'    => '💬',
    'edit-account'    => '👤',
    'customer-logout' => '🚪',
];
?>

<div class="aavni-nav-card">
    <!-- User Profile Header in Sidebar -->
    <div class="aavni-nav-user-header">
        <div class="aavni-nav-avatar">
            <?php echo esc_html($initials); ?>
        </div>
        <div class="aavni-nav-user-info">
            <h3 class="aavni-nav-user-name"><?php echo esc_html($current_user->display_name); ?></h3>
            <span class="aavni-nav-user-email"><?php echo esc_html($current_user->user_email); ?></span>
            <span class="aavni-member-badge">✨ AAVNI Privileged Customer</span>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="woocommerce-MyAccount-navigation aavni-custom-account-nav" aria-label="<?php esc_html_e('Account navigation', 'aavni'); ?>">
        <ul class="aavni-nav-list">
            <?php foreach (wc_get_account_menu_items() as $endpoint => $label) :
                $icon = isset($icons[$endpoint]) ? $icons[$endpoint] : '•';
                $is_active = is_wc_endpoint_url($endpoint) || ($endpoint === 'dashboard' && is_account_page() && empty(WC()->query->get_current_endpoint()));
            ?>
                <li class="aavni-nav-item <?php echo $is_active ? 'is-active' : ''; ?> <?php echo wc_get_account_menu_item_classes($endpoint); ?>">
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url($endpoint)); ?>" class="aavni-nav-link">
                        <span class="aavni-nav-icon"><?php echo esc_html($icon); ?></span>
                        <span class="aavni-nav-label"><?php echo esc_html($label); ?></span>
                        <?php if ($is_active) : ?>
                            <span class="aavni-nav-chevron">&rsaquo;</span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>

            <?php if (current_user_can('edit_shop_orders') || current_user_can('manage_woocommerce')) : ?>
                <li class="aavni-nav-item" style="border-top:1px dashed #E8E3DC; margin-top:8px; padding-top:8px;">
                    <a href="<?php echo esc_url(home_url('/admin-portal/')); ?>" class="aavni-nav-link" style="color:var(--aavni-terracotta, #A85A48); font-weight:700;">
                        <span class="aavni-nav-icon">⚡</span>
                        <span class="aavni-nav-label">Store Admin Portal</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
