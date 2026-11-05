<?php
/**
 * Plugin Name: AAVNI Shiprocket Shipping & Logistics Integration
 * Plugin URI:  https://aavniclothing.com
 * Description: Official Shiprocket API v1 shipping, live courier serviceability, automated AWB generation, thermal label printing, pickup booking, and tracking synchronization for AAVNI Luxury Ethnic Wear.
 * Version:     1.0.0
 * Author:      AAVNI Core Engineering
 * Author URI:  https://aavniclothing.com
 * Text Domain: aavni-shiprocket
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 9.5
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AAVNI_SHIPROCKET_VERSION', '1.0.0');
define('AAVNI_SHIPROCKET_FILE', __FILE__);
define('AAVNI_SHIPROCKET_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAVNI_SHIPROCKET_PLUGIN_URL', plugin_dir_url(__FILE__));

// Declare WooCommerce HPOS (High-Performance Order Storage) Compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Require Core Classes
require_once AAVNI_SHIPROCKET_PLUGIN_DIR . 'includes/class-aavni-shiprocket-api.php';
require_once AAVNI_SHIPROCKET_PLUGIN_DIR . 'includes/class-aavni-shiprocket-settings.php';
require_once AAVNI_SHIPROCKET_PLUGIN_DIR . 'includes/class-aavni-shiprocket-shipment.php';
require_once AAVNI_SHIPROCKET_PLUGIN_DIR . 'includes/class-aavni-shiprocket-sync.php';
require_once AAVNI_SHIPROCKET_PLUGIN_DIR . 'includes/class-aavni-shiprocket-admin-ui.php';

// Activation & Deactivation hooks
register_activation_hook(__FILE__, function() {
    AAVNI_Shiprocket_Sync::register_cron();
});

register_deactivation_hook(__FILE__, function() {
    AAVNI_Shiprocket_Sync::deregister_cron();
});

// Bootstrap Plugin
add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    AAVNI_Shiprocket_Sync::init();
    AAVNI_Shiprocket_Admin_UI::init();
});

