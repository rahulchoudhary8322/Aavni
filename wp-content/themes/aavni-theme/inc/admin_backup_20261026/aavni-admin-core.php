<?php
/**
 * AAVNI E-Commerce Admin Panel Core Controller
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Require All Modular Subsystem Classes
require_once __DIR__ . '/class-aavni-admin-dashboard.php';
require_once __DIR__ . '/class-aavni-admin-orders.php';
require_once __DIR__ . '/class-aavni-admin-products.php';
require_once __DIR__ . '/class-aavni-admin-inventory.php';
require_once __DIR__ . '/class-aavni-admin-returns.php';
require_once __DIR__ . '/class-aavni-admin-support.php';
require_once __DIR__ . '/class-aavni-admin-customers.php';
require_once __DIR__ . '/class-aavni-admin-reviews.php';
require_once __DIR__ . '/class-aavni-admin-coupons.php';
require_once __DIR__ . '/class-aavni-admin-reports.php';
require_once __DIR__ . '/class-aavni-admin-settings.php';

class AAVNI_Admin_Core {

    /**
     * Initialize Admin Hooks
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_admin_menus'], 9);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('wp_ajax_aavni_admin_quick_stock_update', [__CLASS__, 'ajax_quick_stock_update']);
    }

    /**
     * Register AAVNI Top-Level & Submenu Pages in WordPress Admin
     */
    public static function register_admin_menus() {
        $cap = 'edit_shop_orders'; // Accessible to Administrator and Shop Manager

        // Top-Level Menu
        add_menu_page(
            __('AAVNI Store Manager', 'aavni'),
            __('AAVNI Store', 'aavni'),
            $cap,
            'aavni-dashboard',
            ['AAVNI_Admin_Dashboard', 'render'],
            'dashicons-store',
            55
        );

        // Submenus
        add_submenu_page(
            'aavni-dashboard',
            __('AAVNI Executive Dashboard', 'aavni'),
            __('Dashboard', 'aavni'),
            $cap,
            'aavni-dashboard',
            ['AAVNI_Admin_Dashboard', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Orders & Fulfilment Hub', 'aavni'),
            __('Orders & Fulfilment', 'aavni'),
            $cap,
            'aavni-orders',
            ['AAVNI_Admin_Orders', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Catalog & Products Hub', 'aavni'),
            __('Catalog & Products', 'aavni'),
            'edit_products',
            'aavni-products',
            ['AAVNI_Admin_Products', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Inventory & Stock Control', 'aavni'),
            __('Inventory & Stock', 'aavni'),
            'edit_products',
            'aavni-inventory',
            ['AAVNI_Admin_Inventory', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Returns & Exchanges Center', 'aavni'),
            __('Returns & Exchanges', 'aavni'),
            $cap,
            'aavni-returns',
            ['AAVNI_Admin_Returns', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Customer Support Desk', 'aavni'),
            __('Support Tickets', 'aavni'),
            $cap,
            'aavni-support-tickets',
            ['AAVNI_Admin_Support', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Customer Management Directory', 'aavni'),
            __('Customers Directory', 'aavni'),
            'list_users',
            'aavni-customers',
            ['AAVNI_Admin_Customers', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Product Reviews Moderation', 'aavni'),
            __('Reviews & Ratings', 'aavni'),
            'moderate_comments',
            'aavni-reviews',
            ['AAVNI_Admin_Reviews', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Coupons & Promotions Hub', 'aavni'),
            __('Coupons & Offers', 'aavni'),
            'manage_woocommerce',
            'aavni-coupons',
            ['AAVNI_Admin_Coupons', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Sales Reports & Analytics', 'aavni'),
            __('Sales & Analytics', 'aavni'),
            $cap,
            'aavni-reports',
            ['AAVNI_Admin_Reports', 'render']
        );

        add_submenu_page(
            'aavni-dashboard',
            __('Shipping, Taxes & Store Setup', 'aavni'),
            __('Store Setup & Taxes', 'aavni'),
            'manage_woocommerce',
            'aavni-settings',
            ['AAVNI_Admin_Settings', 'render']
        );
    }

    /**
     * Enqueue Admin Styles & Scripts on AAVNI Screens
     */
    public static function enqueue_admin_assets($hook) {
        if (strpos($hook, 'aavni') === false) {
            return;
        }

        // Google Fonts
        wp_enqueue_style('aavni-google-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Urbanist:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap', [], null);

        // AAVNI Admin CSS
        wp_enqueue_style(
            'aavni-admin-css',
            get_stylesheet_directory_uri() . '/assets/css/aavni-admin.css',
            [],
            filemtime(get_stylesheet_directory() . '/assets/css/aavni-admin.css')
        );

        // AAVNI Admin JS
        wp_enqueue_script(
            'aavni-admin-js',
            get_stylesheet_directory_uri() . '/assets/js/aavni-admin.js',
            ['jquery'],
            filemtime(get_stylesheet_directory() . '/assets/js/aavni-admin.js'),
            true
        );
    }

    /**
     * Render Submenu Navigation Pills Bar on every AAVNI Admin Page
     */
    public static function render_nav_bar($active_slug) {
        global $wpdb;
        $returns_table = $wpdb->prefix . 'aavni_returns';
        $tickets_table = $wpdb->prefix . 'aavni_support_tickets';

        $pending_returns = (int) $wpdb->get_var("SELECT COUNT(*) FROM $returns_table WHERE status IN ('submitted', 'under_review')");
        $open_tickets = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tickets_table WHERE status IN ('open', 'in_progress')");

        $nav_items = [
            'dashboard'       => ['label' => 'Dashboard', 'icon' => '✦', 'badge' => 0],
            'orders'          => ['label' => 'Orders & Fulfilment', 'icon' => '📦', 'badge' => 0],
            'products'        => ['label' => 'Catalog & Products', 'icon' => '👗', 'badge' => 0],
            'inventory'       => ['label' => 'Inventory & Stock', 'icon' => '📊', 'badge' => 0],
            'returns'         => ['label' => 'Returns & Exchanges', 'icon' => '🔄', 'badge' => $pending_returns],
            'support-tickets' => ['label' => 'Support Desk', 'icon' => '💬', 'badge' => $open_tickets],
            'customers'       => ['label' => 'Customers', 'icon' => '👥', 'badge' => 0],
            'reviews'         => ['label' => 'Reviews', 'icon' => '⭐', 'badge' => 0],
            'coupons'         => ['label' => 'Coupons', 'icon' => '🏷️', 'badge' => 0],
            'reports'         => ['label' => 'Analytics', 'icon' => '📈', 'badge' => 0],
            'settings'        => ['label' => 'Store Setup', 'icon' => '⚙️', 'badge' => 0],
        ];
        ?>
        <nav class="aavni-admin-nav-bar" aria-label="AAVNI Store Navigation">
            <?php foreach ($nav_items as $slug => $item) :
                $is_active = ($active_slug === $slug);
                $url = admin_url('admin.php?page=aavni-' . $slug);
            ?>
                <a href="<?php echo esc_url($url); ?>" class="aavni-nav-pill <?php echo $is_active ? 'is-active' : ''; ?>">
                    <span><?php echo esc_html($item['icon']); ?></span>
                    <span><?php echo esc_html($item['label']); ?></span>
                    <?php if (!empty($item['badge']) && $item['badge'] > 0) : ?>
                        <span class="aavni-nav-pill-count"><?php echo esc_html($item['badge']); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    /**
     * AJAX Handler for Inline Stock Adjustment
     */
    public static function ajax_quick_stock_update() {
        check_ajax_referer('aavni_admin_quick_stock');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $prod_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $stock_qty = isset($_POST['stock_qty']) ? intval($_POST['stock_qty']) : 0;

        $product = wc_get_product($prod_id);
        if (!$product) {
            wp_send_json_error(['message' => 'Product not found.']);
        }

        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock_qty);
        $product->set_stock_status($stock_qty > 0 ? 'instock' : 'outofstock');
        $product->save();

        wp_send_json_success([
            'product_id' => $prod_id,
            'new_stock'  => $stock_qty,
            'status'     => $stock_qty > 0 ? 'instock' : 'outofstock',
        ]);
    }
}

// Initialize AAVNI Admin Controller
AAVNI_Admin_Core::init();
