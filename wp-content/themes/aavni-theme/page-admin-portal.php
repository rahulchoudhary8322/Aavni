<?php
/**
 * Template Name: AAVNI Standalone Admin Operations Suite
 *
 * Dedicated, standalone, full-screen executive dashboard web application
 * for AAVNI Luxury E-Commerce store management.
 * Completely isolated from the consumer storefront:
 * - NO consumer website navbar or menus (no Kurti, Tops, etc.)
 * - NO store announcement bar
 * - NO customer WhatsApp float widget
 * - NO WordPress default admin bar
 * - Dedicated luxury authentication gate demanding Admin credentials (Aavni / Aavni@0042)
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Completely suppress the WordPress default top admin bar
show_admin_bar(false);
add_filter('show_admin_bar', '__return_false');

// 2. Check Dedicated Admin Operations Suite Session Lock
$is_portal_unlocked   = false;
$portal_user          = null;
$session_cookie_name  = 'aavni_ops_session';
$cookie_path          = !empty(COOKIEPATH) ? COOKIEPATH : '/';

if (isset($_COOKIE[$session_cookie_name])) {
    $raw_token = sanitize_text_field($_COOKIE[$session_cookie_name]);
    $parts = explode(':', $raw_token);
    if (count($parts) === 3) {
        $c_uid       = absint($parts[0]);
        $c_hash      = $parts[1];
        $c_timestamp = absint($parts[2]);

        // Inactivity timeout: 12 hours (43200 seconds)
        if ($c_uid > 0 && (time() - $c_timestamp) < 43200) {
            $saved_token = get_user_meta($c_uid, '_aavni_ops_session_token', true);
            if (!empty($saved_token)) {
                $expected_hash = wp_hash($c_uid . '|' . $saved_token);
                if (hash_equals($expected_hash, $c_hash)) {
                    $u = get_user_by('id', $c_uid);
                    if ($u && (user_can($u, 'manage_woocommerce') || user_can($u, 'edit_shop_orders') || user_can($u, 'upload_files'))) {
                        $is_portal_unlocked = true;
                        $portal_user        = $u;
                        wp_set_current_user($c_uid);

                        // Ensure standard WordPress authentication cookie is also synchronized
                        if (!headers_sent() && (!is_user_logged_in() || get_current_user_id() !== $c_uid)) {
                            wp_set_auth_cookie($c_uid, true);
                        }

                        // Slide expiration forward
                        $updated_cookie = $c_uid . ':' . $c_hash . ':' . time();
                        setcookie($session_cookie_name, $updated_cookie, 0, $cookie_path, COOKIE_DOMAIN, is_ssl(), true);
                        setcookie($session_cookie_name, $updated_cookie, 0, '/', COOKIE_DOMAIN, is_ssl(), true);
                    }
                }
            }
        }
    }
}

// 3. Handle Logout / Lock Action
if (isset($_GET['action']) && in_array($_GET['action'], ['logout', 'lock'], true)) {
    $uid_to_clear = ($portal_user && $portal_user->ID > 0) ? $portal_user->ID : get_current_user_id();
    if ($uid_to_clear > 0) {
        delete_user_meta($uid_to_clear, '_aavni_ops_session_token');
        delete_user_meta($uid_to_clear, '_aavni_ops_session_time');
        delete_user_meta($uid_to_clear, '_aavni_portal_session_token');
        delete_user_meta($uid_to_clear, '_aavni_portal_session_time');
    }
    // Delete session cookies across all potential paths
    setcookie($session_cookie_name, '', time() - 86400, $cookie_path, COOKIE_DOMAIN, is_ssl(), true);
    setcookie($session_cookie_name, '', time() - 86400, '/', COOKIE_DOMAIN, is_ssl(), true);
    setcookie('aavni_portal_auth_token', '', time() - 86400, $cookie_path, COOKIE_DOMAIN, is_ssl(), true);
    setcookie('aavni_portal_auth_token', '', time() - 86400, '/', COOKIE_DOMAIN, is_ssl(), true);
    unset($_COOKIE[$session_cookie_name]);
    unset($_COOKIE['aavni_portal_auth_token']);
    wp_logout();
    wp_safe_redirect(home_url('/admin-portal/'));
    exit;
}

// 4. Handle Login Submission
$auth_error = '';
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aavni_admin_auth_submit'])) {
    check_admin_referer('aavni_admin_auth_action', 'aavni_admin_auth_nonce');

    $username = isset($_POST['admin_user']) ? trim(sanitize_text_field($_POST['admin_user'])) : '';
    $password = isset($_POST['admin_pass']) ? $_POST['admin_pass'] : '';

    if (empty($username) || empty($password)) {
        $auth_error = 'Please enter both your administrator username and password.';
    } else {
        // Strictly authenticate credentials against WordPress core
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            $auth_error = 'Invalid credentials. Please verify your admin username and password.';
        } else {
            // Strictly enforce administrative / manager privileges
            if (user_can($user, 'manage_woocommerce') || user_can($user, 'edit_shop_orders')) {
                // Generate fresh 32-char session token
                $session_token = wp_generate_password(32, false);
                update_user_meta($user->ID, '_aavni_ops_session_token', $session_token);
                update_user_meta($user->ID, '_aavni_ops_session_time', time());

                $hash = wp_hash($user->ID . '|' . $session_token);
                $cookie_val = $user->ID . ':' . $hash . ':' . time();

                // Set session-only cookie (cleared when browser closes)
                setcookie($session_cookie_name, $cookie_val, 0, $cookie_path, COOKIE_DOMAIN, is_ssl(), true);
                setcookie($session_cookie_name, $cookie_val, 0, '/', COOKIE_DOMAIN, is_ssl(), true);

                // Authenticate WordPress user context
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID, false);

                wp_safe_redirect(add_query_arg(['tab' => 'dashboard'], home_url('/admin-portal/')));
                exit;
            } else {
                wp_logout();
                $auth_error = 'Access Denied: This account does not possess Store Administrator or Manager privileges.';
            }
        }
    }
}

// 5. Enforce Access Gate
// The portal is accessible ONLY if unlocked via dedicated session token.
$can_access   = $is_portal_unlocked;
$current_user = $portal_user ?: wp_get_current_user();

// Tabs Configuration
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'dashboard';
$tabs = [
    'dashboard'       => ['title' => 'Executive Dashboard',     'icon' => '📊', 'subtitle' => 'Live Store Performance & Real-time KPIs'],
    'orders'          => ['title' => 'Orders & Fulfilment Hub',  'icon' => '📦', 'subtitle' => 'Shipments, Milestone Tracking & Processing'],
    'products'        => ['title' => 'Catalog & Products',      'icon' => '🏷️', 'subtitle' => 'Inventory Management, Pricing & Variants'],
    'inventory'       => ['title' => 'Inventory & Stock Control','icon' => '📋', 'subtitle' => 'Low Stock Alerts, Reorder Levels & Audit'],
    'storefront'      => ['title' => 'Storefront & Slider',     'icon' => '🎨', 'subtitle' => 'Home Slider, Banners, Announcement Bar & Content'],
    'returns'         => ['title' => 'Returns & Exchanges',     'icon' => '🔄', 'subtitle' => 'Return Requests, Approvals & Pickups'],
    'support-tickets' => ['title' => 'Customer Support Desk',   'icon' => '💬', 'subtitle' => 'Helpdesk Inquiries, Tickets & Resolutions'],
    'customers'       => ['title' => 'Customer Directory',      'icon' => '👥', 'subtitle' => 'Customer Profiles, Order History & LTV'],
    'reviews'         => ['title' => 'Reviews Moderation',      'icon' => '⭐', 'subtitle' => 'Customer Feedback, Ratings & Approvals'],
    'coupons'         => ['title' => 'Coupons & Promotions',    'icon' => '🎟️', 'subtitle' => 'Discount Codes, Usage Limits & Campaigns'],
    'reports'         => ['title' => 'Sales Analytics',         'icon' => '📈', 'subtitle' => 'Revenue Trends, Average Order Value & Reports'],
    'settings'        => ['title' => 'Store Setup & Taxes',     'icon' => '⚙️', 'subtitle' => 'Shipping Rules, GST Configuration & Gateway Status'],
];

if (!array_key_exists($current_tab, $tabs)) {
    $current_tab = 'dashboard';
}

// Fetch live counts for sidebar badges
global $wpdb;
$pending_orders_count = 0;
$pending_returns_count = 0;
$open_tickets_count = 0;

if ($can_access) {
    // 1. Orders needing processing
    $processing_orders = wc_get_orders([
        'status' => ['processing', 'pending'],
        'limit'  => -1,
        'return' => 'ids',
    ]);
    $pending_orders_count = count($processing_orders);

    // 2. Returns
    $returns_table = $wpdb->prefix . 'aavni_returns';
    if ($wpdb->get_var("SHOW TABLES LIKE '$returns_table'") === $returns_table) {
        $pending_returns_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $returns_table WHERE status IN ('submitted', 'under_review')");
    }

    // 3. Tickets
    $tickets_table = $wpdb->prefix . 'aavni_support_tickets';
    if ($wpdb->get_var("SHOW TABLES LIKE '$tickets_table'") === $tickets_table) {
        $open_tickets_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tickets_table WHERE status IN ('open', 'in_progress')");
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($tabs[$current_tab]['title']); ?> &bull; AAVNI Store Operations</title>

    <!-- Google Fonts: Urbanist & Playfair Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Urbanist:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    <!-- Enqueue WordPress Head (Media Uploader, jQuery, Admin CSS) -->
    <?php wp_head(); ?>

    <script>
        window.aavniAdminParams = window.aavniAdminParams || {};
        window.aavniAdminParams.ajax_url = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
        window.aavniAdminParams.stock_nonce = <?php echo json_encode(wp_create_nonce('aavni_admin_quick_stock')); ?>;
        window.aavniAdminParams.milestone_nonce = <?php echo json_encode(wp_create_nonce('aavni_quick_milestone_advance')); ?>;
    </script>

    <style>
        /* Standalone Admin Application Reset & Architecture */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            font-family: 'Urbanist', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #FDFBF7;
            color: #1B1A18;
            -webkit-font-smoothing: antialiased;
        }

        /* Suppress Any WordPress Core or Storefront Elements */
        #wpadminbar,
        .aavni-header,
        .aavni-announcement-bar,
        .aavni-bottom-nav,
        .aavni-footer,
        .aavni-whatsapp-float,
        .whatsapp-float,
        #whatsapp-float {
            display: none !important;
        }

        /* Dedicated Authentication Gate Screen */
        .aavni-auth-gate {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 20%, #2A2421 0%, #141311 100%);
            padding: 24px;
            box-sizing: border-box;
            position: relative;
            overflow: hidden;
        }

        .aavni-auth-gate::before {
            content: '';
            position: absolute;
            top: -150px;
            right: -150px;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(197, 168, 128, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .aavni-auth-gate::after {
            content: '';
            position: absolute;
            bottom: -150px;
            left: -150px;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(168, 90, 72, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .aavni-auth-card {
            width: 100%;
            max-width: 440px;
            background: rgba(27, 26, 24, 0.92);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(197, 168, 128, 0.28);
            border-radius: 18px;
            padding: 44px 38px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6), 0 0 40px rgba(168, 90, 72, 0.1);
            color: #FFFFFF;
            position: relative;
            z-index: 2;
            box-sizing: border-box;
        }

        .aavni-auth-emblem {
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, #A85A48, #C5A880);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin: 0 auto 20px;
            box-shadow: 0 6px 20px rgba(168, 90, 72, 0.45);
        }

        .aavni-auth-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 27px;
            font-weight: 700;
            text-align: center;
            margin: 0 0 6px;
            color: #FFFFFF;
            letter-spacing: 0.8px;
        }

        .aavni-auth-sub {
            font-size: 11.5px;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: #C5A880;
            text-align: center;
            font-weight: 600;
            margin: 0 0 24px;
        }

        .aavni-auth-alert {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #FCA5A5;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.45;
            margin-bottom: 22px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .aavni-auth-field {
            margin-bottom: 18px;
        }

        .aavni-auth-label {
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #E8E3DC;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .aavni-auth-input-wrap {
            position: relative;
        }

        .aavni-auth-input {
            width: 100%;
            padding: 13px 14px;
            background: #141311;
            border: 1px solid #3A3530;
            border-radius: 8px;
            color: #FFFFFF;
            font-size: 14px;
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .aavni-auth-input:focus {
            outline: none;
            border-color: #C5A880;
            box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.2);
        }

        .aavni-auth-toggle-pwd {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9E988F;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
        }

        .aavni-auth-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #A85A48 0%, #C5A880 100%);
            border: none;
            border-radius: 8px;
            color: #FFFFFF;
            font-weight: 700;
            font-size: 14.5px;
            letter-spacing: 0.5px;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(168, 90, 72, 0.35);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .aavni-auth-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(168, 90, 72, 0.5);
            filter: brightness(1.05);
        }

        /* Standalone App Layout */
        .aavni-app-layout {
            display: flex;
            min-height: 100vh;
            background: #FDFBF7;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
            position: relative;
        }

        /* Fixed Left Sidebar */
        .aavni-app-sidebar {
            width: 270px;
            background: #141311;
            border-right: 1px solid rgba(197, 168, 128, 0.2);
            color: #FFFFFF;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.25);
        }

        .aavni-sidebar-brand {
            padding: 24px 22px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .aavni-sidebar-emblem {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #A85A48, #C5A880);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFF;
            font-size: 18px;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(168, 90, 72, 0.35);
        }

        .aavni-sidebar-title {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            color: #FFFFFF;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .aavni-sidebar-tag {
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #C5A880;
            font-weight: 600;
            margin-top: 3px;
        }

        .aavni-sidebar-status {
            padding: 10px 22px;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11.5px;
            color: #9E988F;
        }

        .aavni-status-dot {
            width: 8px;
            height: 8px;
            background: #10B981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10B981;
        }

        .aavni-sidebar-nav {
            flex-grow: 1;
            padding: 14px 12px;
            overflow-y: auto;
        }

        .aavni-nav-header {
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #6B655C;
            padding: 10px 12px 6px;
            font-weight: 700;
        }

        .aavni-sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: #D1CBC3;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.15s ease;
            margin-bottom: 3px;
        }

        .aavni-sidebar-link:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #FFFFFF;
        }

        .aavni-sidebar-link.is-active {
            background: linear-gradient(135deg, rgba(168, 90, 72, 0.3) 0%, rgba(197, 168, 128, 0.2) 100%);
            color: #FFFFFF;
            font-weight: 700;
            border-left: 3px solid #C5A880;
        }

        .aavni-sidebar-badge {
            margin-left: auto;
            background: #A85A48;
            color: #FFF;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
        }

        .aavni-sidebar-badge.is-warn {
            background: #D97706;
        }

        .aavni-sidebar-user {
            padding: 16px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: #0E0D0C;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .aavni-user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .aavni-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #C5A880, #A85A48);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #FFF;
        }

        .aavni-user-logout {
            color: #F87171;
            text-decoration: none;
            font-size: 12.5px;
            font-weight: 600;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid rgba(239, 68, 68, 0.25);
            transition: all 0.2s ease;
        }

        .aavni-user-logout:hover {
            background: rgba(239, 68, 68, 0.15);
            border-color: #EF4444;
            color: #FFF;
        }

        /* Workspace Main Column */
        .aavni-app-main {
            margin-left: 270px;
            width: calc(100% - 270px);
            max-width: calc(100% - 270px);
            min-width: 0;
            flex-grow: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            overflow-x: hidden;
            background: #FDFBF7;
        }

        /* Top Bar */
        .aavni-app-topbar {
            height: 68px;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            background: rgba(253, 251, 247, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #E8E3DC;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 500;
        }

        .aavni-topbar-title {
            font-family: 'Playfair Display', serif;
            font-size: 21px;
            font-weight: 700;
            margin: 0;
            color: #1B1A18;
        }

        .aavni-topbar-breadcrumb {
            font-size: 12px;
            color: #6B655C;
            margin-top: 2px;
        }

        .aavni-topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .aavni-topbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .aavni-topbar-btn-store {
            background: #FFFFFF;
            color: #1B1A18;
            border: 1px solid #D5CECE;
        }

        .aavni-topbar-btn-store:hover {
            background: #F4EFEB;
            border-color: #A85A48;
            color: #A85A48;
        }

        .aavni-topbar-btn-wpadmin {
            background: #1B1A18;
            color: #FFFFFF;
            border: 1px solid #1B1A18;
        }

        .aavni-topbar-btn-wpadmin:hover {
            background: #332F2D;
        }

        /* Workspace Content Body */
        .aavni-app-content {
            padding: 24px 32px 60px;
            flex-grow: 1;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }

        /* Clean up redundant horizontal nav pills in standalone layout */
        .aavni-app-content .aavni-admin-nav-bar {
            display: none !important;
        }

        .aavni-app-content .aavni-admin-wrap {
            margin: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            box-sizing: border-box !important;
        }

        /* Mobile & Tablet Responsiveness */
        @media (max-width: 1024px) {
            .aavni-app-sidebar {
                width: 230px;
            }
            .aavni-app-main {
                margin-left: 230px;
                width: calc(100% - 230px);
                max-width: calc(100% - 230px);
            }
            .aavni-app-topbar,
            .aavni-app-content {
                padding-left: 20px;
                padding-right: 20px;
            }
        }

        @media (max-width: 768px) {
            .aavni-app-sidebar {
                position: static;
                width: 100%;
                height: auto;
            }
            .aavni-app-layout {
                flex-direction: column;
            }
            .aavni-app-main {
                margin-left: 0;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<?php if (!$can_access) : ?>
    <!-- 1. Dedicated Admin Authentication Gate -->
    <div class="aavni-auth-gate">
        <div class="aavni-auth-card">
            <div class="aavni-auth-emblem">✦</div>
            <h1 class="aavni-auth-title">AAVNI Store Manager</h1>
            <div class="aavni-auth-sub">Administrative Command Suite</div>

            <?php if (!empty($auth_error)) : ?>
                <div class="aavni-auth-alert">
                    <span>⚠️</span>
                    <div><?php echo esc_html($auth_error); ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(home_url('/admin-portal/')); ?>">
                <?php wp_nonce_field('aavni_admin_auth_action', 'aavni_admin_auth_nonce'); ?>

                <div class="aavni-auth-field">
                    <label for="admin_user" class="aavni-auth-label">Administrator Username</label>
                    <div class="aavni-auth-input-wrap">
                        <input type="text" name="admin_user" id="admin_user" class="aavni-auth-input" 
                               value="<?php echo esc_attr(isset($_POST['admin_user']) ? $_POST['admin_user'] : ''); ?>" 
                               placeholder="Enter Administrator Username"
                               required autofocus autocomplete="off" />
                    </div>
                </div>

                <div class="aavni-auth-field">
                    <label for="admin_pass" class="aavni-auth-label">Password</label>
                    <div class="aavni-auth-input-wrap">
                        <input type="password" name="admin_pass" id="admin_pass" class="aavni-auth-input" 
                               placeholder="Enter Password"
                               required autocomplete="new-password" />
                        <button type="button" class="aavni-auth-toggle-pwd" id="aavniTogglePwd" aria-label="Toggle password visibility">
                            👁️
                        </button>
                    </div>
                </div>

                <div style="margin-bottom:20px; font-size:12.5px; color:#C5A880; display:flex; align-items:center; gap:8px;">
                    <span>🔒</span>
                    <span>Direct Access Restricted &bull; Password Verification Required</span>
                </div>

                <button type="submit" name="aavni_admin_auth_submit" value="1" class="aavni-auth-btn">
                    Authenticate & Enter Admin Portal &rarr;
                </button>
            </form>

            <div style="margin-top:28px; text-align:center; font-size:11.5px; color:#6B655C; border-top:1px solid rgba(255,255,255,0.06); padding-top:16px;">
                🔒 Secure Operations Gateway &bull; Session Encrypted
            </div>
        </div>
    </div>

    <script>
        document.getElementById('aavniTogglePwd')?.addEventListener('click', function() {
            var input = document.getElementById('admin_pass');
            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = '🙈';
            } else {
                input.type = 'password';
                this.textContent = '👁️';
            }
        });
    </script>

<?php else : ?>
    <!-- 2. Authenticated Standalone Admin Application Shell -->
    <div class="aavni-app-layout">
        
        <!-- Left Fixed Navigation Sidebar -->
        <aside class="aavni-app-sidebar">
            <div class="aavni-sidebar-brand">
                <div class="aavni-sidebar-emblem">✦</div>
                <div>
                    <div class="aavni-sidebar-title">AAVNI</div>
                    <div class="aavni-sidebar-tag">LUXURY ADMIN SUITE</div>
                </div>
            </div>

            <div class="aavni-sidebar-status">
                <span class="aavni-status-dot"></span>
                <span>Live Store &bull; COD Operational</span>
            </div>

            <nav class="aavni-sidebar-nav">
                <div class="aavni-nav-header">Store Management</div>

                <?php foreach ($tabs as $slug => $item) : 
                    $is_active = ($current_tab === $slug);
                    $url = add_query_arg(['tab' => $slug], home_url('/admin-portal/'));
                ?>
                    <a href="<?php echo esc_url($url); ?>" class="aavni-sidebar-link <?php echo $is_active ? 'is-active' : ''; ?>">
                        <span style="font-size:16px;"><?php echo esc_html($item['icon']); ?></span>
                        <span><?php echo esc_html($item['title']); ?></span>
                        
                        <?php if ($slug === 'orders' && $pending_orders_count > 0) : ?>
                            <span class="aavni-sidebar-badge"><?php echo esc_html($pending_orders_count); ?></span>
                        <?php elseif ($slug === 'returns' && $pending_returns_count > 0) : ?>
                            <span class="aavni-sidebar-badge is-warn"><?php echo esc_html($pending_returns_count); ?></span>
                        <?php elseif ($slug === 'support-tickets' && $open_tickets_count > 0) : ?>
                            <span class="aavni-sidebar-badge is-warn"><?php echo esc_html($open_tickets_count); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="aavni-sidebar-user">
                <div class="aavni-user-profile">
                    <div class="aavni-user-avatar">
                        <?php echo esc_html(strtoupper(substr($current_user->display_name ?: $current_user->user_login, 0, 1))); ?>
                    </div>
                    <div>
                        <div style="font-size:13px; font-weight:700; color:#FFFFFF; line-height:1.2;">
                            <?php echo esc_html($current_user->display_name ?: $current_user->user_login); ?>
                        </div>
                        <div style="font-size:11px; color:#C5A880;">Administrator</div>
                    </div>
                </div>
                <div style="display:flex; gap:6px;">
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'lock'], home_url('/admin-portal/'))); ?>" 
                       class="aavni-user-logout" style="color:#C5A880; border-color:rgba(197,168,128,0.3);" title="Lock Dashboard Immediately">
                        🔒 Lock
                    </a>
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'logout'], home_url('/admin-portal/'))); ?>" 
                       class="aavni-user-logout" title="Sign out of Admin Portal">
                        🚪 Logout
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <div class="aavni-app-main">
            <!-- Top App Bar -->
            <header class="aavni-app-topbar">
                <div>
                    <h1 class="aavni-topbar-title"><?php echo esc_html($tabs[$current_tab]['title']); ?></h1>
                    <div class="aavni-topbar-breadcrumb">
                        <span>AAVNI Operations</span> &bull; <span><?php echo esc_html($tabs[$current_tab]['subtitle']); ?></span>
                    </div>
                </div>
                <div class="aavni-topbar-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="aavni-topbar-btn aavni-topbar-btn-store">
                        🛍️ Live Storefront ↗
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=aavni-' . $current_tab)); ?>" class="aavni-topbar-btn aavni-topbar-btn-wpadmin" title="Open in standard WordPress backend">
                        ⚙️ WP-Admin
                    </a>
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'lock'], home_url('/admin-portal/'))); ?>" 
                       class="aavni-topbar-btn" style="background:#2A2421; color:#C5A880; border:1px solid rgba(197,168,128,0.35);" title="Lock Admin Portal Now">
                        🔒 Lock Portal
                    </a>
                    <a href="<?php echo esc_url(add_query_arg(['action' => 'logout'], home_url('/admin-portal/'))); ?>" 
                       class="aavni-topbar-btn" style="background:#EF4444; color:#FFF;" title="Sign out completely">
                        🚪 Sign Out
                    </a>
                </div>
            </header>

            <!-- Workspace Subsystem Content -->
            <main class="aavni-app-content">
                <?php
                switch ($current_tab) {
                    case 'orders':
                        AAVNI_Admin_Orders::render();
                        break;
                    case 'products':
                        AAVNI_Admin_Products::render();
                        break;
                    case 'inventory':
                        AAVNI_Admin_Inventory::render();
                        break;
                    case 'storefront':
                        AAVNI_Admin_Storefront::render();
                        break;
                    case 'returns':
                        AAVNI_Admin_Returns::render();
                        break;
                    case 'support-tickets':
                        AAVNI_Admin_Support::render();
                        break;
                    case 'customers':
                        AAVNI_Admin_Customers::render();
                        break;
                    case 'reviews':
                        AAVNI_Admin_Reviews::render();
                        break;
                    case 'coupons':
                        AAVNI_Admin_Coupons::render();
                        break;
                    case 'reports':
                        AAVNI_Admin_Reports::render();
                        break;
                    case 'settings':
                        AAVNI_Admin_Settings::render();
                        break;
                    case 'dashboard':
                    default:
                        AAVNI_Admin_Dashboard::render();
                        break;
                }
                ?>
            </main>
        </div>

    </div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
