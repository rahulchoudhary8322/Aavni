<?php
/**
 * AAVNI Storefront, Slider & Customer Content Manager
 *
 * Dedicated admin controller allowing full customization of the customer-facing
 * storefront including homepage hero slider, announcement bar, category showcases,
 * brand story, trust badges, headers, and footer details.
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Admin_Storefront {

    /**
     * Default slides generator
     */
    public static function get_default_slides() {
        $upload_dir = wp_upload_dir();
        $banner_img = $upload_dir['baseurl'] . '/aavni/aavni-hero-banner.png';

        return [
            [
                'id'              => 'slide_1',
                'title'           => 'Made for Every Mood, Every Moment.',
                'tagline'         => 'Autumn • Festive • Everyday',
                'desc'            => 'Explore our handcrafted clothing designed with pure, breathable fabrics and timeless silhouettes. Crafted for women who dress to express.',
                'image_url'       => $banner_img,
                'btn1_text'       => 'Shop All Collections',
                'btn1_url'        => wc_get_page_permalink('shop') ?: home_url('/shop/'),
                'btn2_text'       => 'Explore Long Kurtis',
                'btn2_url'        => home_url('/product-category/long-kurti/'),
                'overlay_opacity' => '35',
                'active'          => 1,
            ],
            [
                'id'              => 'slide_2',
                'title'           => 'Pure Cambric Cotton & Authentic Prints.',
                'tagline'         => 'Jaipur Artisanal Heritage • 100% Pure Cotton',
                'desc'            => 'Pre-shrunk 60s combed pure cotton kurtis with intricate boota prints, tie-up halter backs, and breezy cuts tailored for effortless grace.',
                'image_url'       => $banner_img,
                'btn1_text'       => 'Discover Long Kurtis',
                'btn1_url'        => home_url('/product-category/long-kurti/'),
                'btn2_text'       => 'Shop Short Kurtis',
                'btn2_url'        => home_url('/product-category/short-kurti/'),
                'overlay_opacity' => '35',
                'active'          => 1,
            ],
            [
                'id'              => 'slide_3',
                'title'           => 'Earthy Tunic Tops & Breathable Nightwear.',
                'tagline'         => 'Contemporary Coordinates • Relaxed Comfort',
                'desc'            => 'From versatile daytime tops to ultra-soft pure cotton nightwear crafted with traditional Rajasthani block motifs for every day.',
                'image_url'       => $banner_img,
                'btn1_text'       => 'Shop Tops & Tunics',
                'btn1_url'        => home_url('/product-category/tops/'),
                'btn2_text'       => 'Explore Nightwear',
                'btn2_url'        => home_url('/product-category/nighty/'),
                'overlay_opacity' => '35',
                'active'          => 1,
            ],
        ];
    }

    /**
     * Get slides from options or defaults
     */
    public static function get_slides() {
        $slides = get_option('aavni_homepage_slides', null);
        if ($slides === null || !is_array($slides)) {
            return self::get_default_slides();
        }
        return $slides;
    }

    /**
     * Get slider global settings
     */
    public static function get_slider_settings() {
        $defaults = [
            'enabled'  => 1,
            'autoplay' => 1,
            'interval' => 5,
        ];
        $settings = get_option('aavni_slider_settings', $defaults);
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Get Announcement Bar settings
     */
    public static function get_announcement() {
        $defaults = [
            'enabled'    => 1,
            'text1'      => 'Free Express Shipping Across India on Orders Above ₹999',
            'text2'      => 'COD Available',
            'text3'      => 'Easy 7-Day Exchange',
            'bg_color'   => '#1B1A18',
            'text_color' => '#FFFFFF',
        ];
        $settings = get_option('aavni_announcement_bar', $defaults);
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Get Featured Categories
     */
    public static function get_categories() {
        $upload_dir = wp_upload_dir();
        $defaults = [
            [
                'id'          => 'cat_1',
                'title'       => 'Long Kurti',
                'subtitle'    => 'Statement Elegance',
                'action_text' => 'Explore →',
                'image_url'   => $upload_dir['baseurl'] . '/aavni/long-kurti-1.jpg',
                'url'         => home_url('/product-category/long-kurti/'),
            ],
            [
                'id'          => 'cat_2',
                'title'       => 'Short Kurti',
                'subtitle'    => 'Everyday Chic',
                'action_text' => 'Explore →',
                'image_url'   => $upload_dir['baseurl'] . '/aavni/short-kurti-1.jpg',
                'url'         => home_url('/product-category/short-kurti/'),
            ],
            [
                'id'          => 'cat_3',
                'title'       => 'Tops & Tunics',
                'subtitle'    => 'Contemporary Handloom',
                'action_text' => 'Explore →',
                'image_url'   => $upload_dir['baseurl'] . '/aavni/chic-top-1.jpg',
                'url'         => home_url('/product-category/tops/'),
            ],
            [
                'id'          => 'cat_4',
                'title'       => 'Nightwear',
                'subtitle'    => 'Breathable Pure Cotton',
                'action_text' => 'Explore →',
                'image_url'   => $upload_dir['baseurl'] . '/aavni/nightwear-1.jpg',
                'url'         => home_url('/product-category/nighty/'),
            ],
        ];

        $cats = get_option('aavni_homepage_categories', null);
        if ($cats === null || !is_array($cats)) {
            return $defaults;
        }
        return $cats;
    }

    /**
     * Get Categories section meta
     */
    public static function get_categories_section() {
        $defaults = [
            'enabled'    => 1,
            'subheading' => 'Curated Wardrobe',
            'title'      => 'Shop by Category',
            'desc'       => 'From statement traditional long kurtis to breezy daily tops and luxurious cotton nightwear.',
        ];
        $meta = get_option('aavni_homepage_categories_section', $defaults);
        return wp_parse_args($meta, $defaults);
    }

    /**
     * Get Brand Story
     */
    public static function get_brand_story() {
        $upload_dir = wp_upload_dir();
        $defaults = [
            'enabled'    => 1,
            'subheading' => 'Our Philosophy',
            'quote'      => 'Dress to express. Live to impress.',
            'text'       => 'AAVNI was founded on a simple yet profound devotion: creating timeless Indian clothing that celebrates authentic craft and everyday comfort. Every piece in our collection is crafted with breathable pure cotton, refined cuts, and thoughtful details that transition effortlessly from home to celebrations.',
            'image_url'  => $upload_dir['baseurl'] . '/aavni/long-kurti-1.jpg',
            'badges'     => [
                '100% Breathable Cotton',
                'Artisanal Finishing',
                'All-Day Ease & Fit',
                'Transparent Sizing',
            ],
        ];
        $story = get_option('aavni_homepage_story', $defaults);
        return wp_parse_args($story, $defaults);
    }

    /**
     * Get Benefits Strip
     */
    public static function get_benefits() {
        $defaults = [
            'enabled' => 1,
            'items'   => [
                [
                    'icon'  => '🚚',
                    'title' => 'Complimentary Express Shipping',
                    'desc'  => 'Free delivery on prepaid orders above ₹999 across India.',
                ],
                [
                    'icon'  => '💵',
                    'title' => 'Cash on Delivery Available',
                    'desc'  => 'Pay upon delivery with verified doorstep handling.',
                ],
                [
                    'icon'  => '🔄',
                    'title' => 'Hassle-Free 7-Day Exchange',
                    'desc'  => 'Easy size exchange policy for complete peace of mind.',
                ],
                [
                    'icon'  => '💬',
                    'title' => 'Dedicated Support',
                    'desc'  => 'Quick assistance on orders and fit guidance via WhatsApp & Email.',
                ],
            ],
        ];
        $benefits = get_option('aavni_homepage_benefits', $defaults);
        return wp_parse_args($benefits, $defaults);
    }

    /**
     * Get Newsletter config
     */
    public static function get_newsletter() {
        $defaults = [
            'enabled'    => 1,
            'subheading' => 'The AAVNI Circle',
            'title'      => 'Be The First To Experience Our Edits',
            'desc'       => 'Subscribe to our newsletter for exclusive previews of limited festive collections and styling notes.',
        ];
        $nl = get_option('aavni_homepage_newsletter', $defaults);
        return wp_parse_args($nl, $defaults);
    }

    /**
     * Get Branding & Footer
     */
    public static function get_branding() {
        return [
            'tagline'         => get_option('aavni_store_tagline', 'Dress To Express • Live To Impress'),
            'care_phone'      => get_option('aavni_store_care_phone', '+91 9828103222'),
            'support_email'   => get_option('aavni_store_support_email', 'care@aavniclothing.com'),
            'operating_hours' => get_option('aavni_store_operating_hours', 'Mon - Sat: 10:00 AM - 7:00 PM IST'),
            'instagram'       => get_option('aavni_social_instagram', 'https://instagram.com/aavniclothing'),
            'facebook'        => get_option('aavni_social_facebook', 'https://facebook.com/aavniclothing'),
            'pinterest'       => get_option('aavni_social_pinterest', 'https://pinterest.com/aavniclothing'),
            'whatsapp'        => get_option('aavni_social_whatsapp', 'https://wa.me/919828103222'),
            'copyright'       => get_option('aavni_footer_copyright', 'AAVNI. All rights reserved. Registered Indian Apparel Brand.'),
        ];
    }

    /**
     * Action Dispatch Handler
     */
    public static function handle_actions() {
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_theme_options')) {
            return;
        }

        $action = isset($_REQUEST['aavni_action']) ? sanitize_text_field($_REQUEST['aavni_action']) : '';
        if (empty($action)) {
            return;
        }

        // 1. Save Slider Global Settings
        if ($action === 'save_aavni_slider_settings') {
            check_admin_referer('aavni_storefront_nonce');

            $settings = [
                'enabled'  => isset($_POST['slider_enabled']) ? 1 : 0,
                'autoplay' => isset($_POST['slider_autoplay']) ? 1 : 0,
                'interval' => max(2, min(30, absint($_POST['slider_interval'] ?? 5))),
            ];

            update_option('aavni_slider_settings', $settings);
            self::redirect_with_message('slider', 'Slider settings updated successfully.');
        }

        // 2. Add or Edit Slide
        if ($action === 'save_aavni_slide') {
            check_admin_referer('aavni_storefront_nonce');

            $slide_index = isset($_POST['slide_index']) ? intval($_POST['slide_index']) : -1;
            $title       = sanitize_text_field($_POST['slide_title'] ?? '');
            $tagline     = sanitize_text_field($_POST['slide_tagline'] ?? '');
            $desc        = sanitize_textarea_field($_POST['slide_desc'] ?? '');
            $image_url   = esc_url_raw($_POST['slide_image_url'] ?? '');
            $btn1_text   = sanitize_text_field($_POST['slide_btn1_text'] ?? '');
            $btn1_url    = esc_url_raw($_POST['slide_btn1_url'] ?? '');
            $btn2_text   = sanitize_text_field($_POST['slide_btn2_text'] ?? '');
            $btn2_url    = esc_url_raw($_POST['slide_btn2_url'] ?? '');
            $overlay_op  = max(0, min(90, absint($_POST['slide_overlay_opacity'] ?? 35)));
            $active      = isset($_POST['slide_active']) ? 1 : 0;

            if (empty($title)) {
                $title = 'AAVNI Featured Edit';
            }
            if (empty($image_url)) {
                $upload_dir = wp_upload_dir();
                $image_url = $upload_dir['baseurl'] . '/aavni/aavni-hero-banner.png';
            }

            $slide_data = [
                'id'              => ($slide_index >= 0) ? 'slide_' . ($slide_index + 1) : 'slide_' . time(),
                'title'           => $title,
                'tagline'         => $tagline,
                'desc'            => $desc,
                'image_url'       => $image_url,
                'btn1_text'       => $btn1_text,
                'btn1_url'        => $btn1_url,
                'btn2_text'       => $btn2_text,
                'btn2_url'        => $btn2_url,
                'overlay_opacity' => (string) $overlay_op,
                'active'          => $active,
            ];

            $slides = self::get_slides();

            if ($slide_index >= 0 && isset($slides[$slide_index])) {
                $slides[$slide_index] = $slide_data;
                $msg = 'Slide updated successfully.';
            } else {
                $slides[] = $slide_data;
                $msg = 'New slide added to hero carousel.';
            }

            update_option('aavni_homepage_slides', $slides);
            self::redirect_with_message('slider', $msg);
        }

        // 3. Delete Slide
        if ($action === 'delete_aavni_slide') {
            check_admin_referer('aavni_storefront_nonce');

            $slide_index = isset($_POST['slide_index']) ? intval($_POST['slide_index']) : -1;
            $slides = self::get_slides();

            if ($slide_index >= 0 && isset($slides[$slide_index])) {
                array_splice($slides, $slide_index, 1);
                update_option('aavni_homepage_slides', $slides);
                self::redirect_with_message('slider', 'Slide removed.');
            }
            self::redirect_with_message('slider', 'Slide not found.');
        }

        // 4. Move Slide (Re-order)
        if ($action === 'move_aavni_slide') {
            check_admin_referer('aavni_storefront_nonce');

            $slide_index = isset($_POST['slide_index']) ? intval($_POST['slide_index']) : -1;
            $direction   = sanitize_text_field($_POST['direction'] ?? '');
            $slides      = self::get_slides();

            if ($slide_index >= 0 && isset($slides[$slide_index])) {
                if ($direction === 'up' && $slide_index > 0) {
                    $temp = $slides[$slide_index - 1];
                    $slides[$slide_index - 1] = $slides[$slide_index];
                    $slides[$slide_index] = $temp;
                    update_option('aavni_homepage_slides', $slides);
                } elseif ($direction === 'down' && $slide_index < count($slides) - 1) {
                    $temp = $slides[$slide_index + 1];
                    $slides[$slide_index + 1] = $slides[$slide_index];
                    $slides[$slide_index] = $temp;
                    update_option('aavni_homepage_slides', $slides);
                }
            }
            self::redirect_with_message('slider', 'Slide order updated.');
        }

        // 5. Save Announcement Bar
        if ($action === 'save_aavni_announcement') {
            check_admin_referer('aavni_storefront_nonce');

            $announcement = [
                'enabled'    => isset($_POST['announcement_enabled']) ? 1 : 0,
                'text1'      => sanitize_text_field($_POST['announcement_text1'] ?? ''),
                'text2'      => sanitize_text_field($_POST['announcement_text2'] ?? ''),
                'text3'      => sanitize_text_field($_POST['announcement_text3'] ?? ''),
                'bg_color'   => sanitize_hex_color($_POST['announcement_bg_color'] ?? '#1B1A18') ?: '#1B1A18',
                'text_color' => sanitize_hex_color($_POST['announcement_text_color'] ?? '#FFFFFF') ?: '#FFFFFF',
            ];

            update_option('aavni_announcement_bar', $announcement);
            self::redirect_with_message('announcement', 'Announcement Bar updated successfully.');
        }

        // 6. Save Categories Showcase
        if ($action === 'save_aavni_categories') {
            check_admin_referer('aavni_storefront_nonce');

            $meta = [
                'enabled'    => isset($_POST['categories_enabled']) ? 1 : 0,
                'subheading' => sanitize_text_field($_POST['cat_section_subheading'] ?? ''),
                'title'      => sanitize_text_field($_POST['cat_section_title'] ?? ''),
                'desc'       => sanitize_textarea_field($_POST['cat_section_desc'] ?? ''),
            ];
            update_option('aavni_homepage_categories_section', $meta);

            $cats_input = isset($_POST['categories']) && is_array($_POST['categories']) ? $_POST['categories'] : [];
            $sanitized_cats = [];

            foreach ($cats_input as $idx => $item) {
                $sanitized_cats[] = [
                    'id'          => 'cat_' . ($idx + 1),
                    'title'       => sanitize_text_field($item['title'] ?? ''),
                    'subtitle'    => sanitize_text_field($item['subtitle'] ?? ''),
                    'action_text' => sanitize_text_field($item['action_text'] ?? 'Explore →'),
                    'image_url'   => esc_url_raw($item['image_url'] ?? ''),
                    'url'         => esc_url_raw($item['url'] ?? ''),
                ];
            }

            if (!empty($sanitized_cats)) {
                update_option('aavni_homepage_categories', $sanitized_cats);
            }

            self::redirect_with_message('categories', 'Shop by Category cards updated successfully.');
        }

        // 7. Save Brand Story & Trust
        if ($action === 'save_aavni_brand_story') {
            check_admin_referer('aavni_storefront_nonce');

            $badges_input = isset($_POST['story_badges']) && is_array($_POST['story_badges']) ? array_map('sanitize_text_field', $_POST['story_badges']) : [];

            $story = [
                'enabled'    => isset($_POST['story_enabled']) ? 1 : 0,
                'subheading' => sanitize_text_field($_POST['story_subheading'] ?? ''),
                'quote'      => sanitize_text_field($_POST['story_quote'] ?? ''),
                'text'       => sanitize_textarea_field($_POST['story_text'] ?? ''),
                'image_url'  => esc_url_raw($_POST['story_image_url'] ?? ''),
                'badges'     => array_values(array_filter($badges_input)),
            ];
            update_option('aavni_homepage_story', $story);

            // Trust benefits
            $benefits_items_input = isset($_POST['benefits']) && is_array($_POST['benefits']) ? $_POST['benefits'] : [];
            $sanitized_benefits = [];
            foreach ($benefits_items_input as $b) {
                $sanitized_benefits[] = [
                    'icon'  => sanitize_text_field($b['icon'] ?? '✨'),
                    'title' => sanitize_text_field($b['title'] ?? ''),
                    'desc'  => sanitize_text_field($b['desc'] ?? ''),
                ];
            }

            $benefits = [
                'enabled' => isset($_POST['benefits_enabled']) ? 1 : 0,
                'items'   => $sanitized_benefits,
            ];
            update_option('aavni_homepage_benefits', $benefits);

            self::redirect_with_message('story', 'Brand Story and Trust Badges saved.');
        }

        // 8. Save Contact & Footer
        if ($action === 'save_aavni_branding_footer') {
            check_admin_referer('aavni_storefront_nonce');

            $tagline    = sanitize_text_field($_POST['store_tagline'] ?? '');
            $phone      = sanitize_text_field($_POST['care_phone'] ?? '');
            $email      = sanitize_email($_POST['support_email'] ?? '');
            $hours      = sanitize_text_field($_POST['operating_hours'] ?? '');
            $instagram  = esc_url_raw($_POST['social_instagram'] ?? '');
            $facebook   = esc_url_raw($_POST['social_facebook'] ?? '');
            $pinterest  = esc_url_raw($_POST['social_pinterest'] ?? '');
            $whatsapp   = esc_url_raw($_POST['social_whatsapp'] ?? '');
            $copyright  = sanitize_text_field($_POST['footer_copyright'] ?? '');

            update_option('aavni_store_tagline', $tagline ?: 'Dress To Express • Live To Impress');
            update_option('aavni_store_care_phone', $phone ?: '+91 9828103222');
            update_option('aavni_store_support_email', $email ?: 'care@aavniclothing.com');
            update_option('aavni_store_operating_hours', $hours ?: 'Mon - Sat: 10:00 AM - 7:00 PM IST');
            update_option('aavni_social_instagram', $instagram);
            update_option('aavni_social_facebook', $facebook);
            update_option('aavni_social_pinterest', $pinterest);
            update_option('aavni_social_whatsapp', $whatsapp);
            update_option('aavni_footer_copyright', $copyright);

            self::redirect_with_message('branding', 'Branding, contact info, and footer details updated.');
        }
    }

    /**
     * Helper to safely redirect back to storefront section
     */
    private static function redirect_with_message($section, $message) {
        $is_frontend = isset($_REQUEST['aavni_frontend_portal']) || 
            (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin-portal') !== false);

        if ($is_frontend) {
            $url = add_query_arg([
                'tab'     => 'storefront',
                'section' => $section,
                'msg'     => rawurlencode($message),
            ], home_url('/admin-portal/'));
        } else {
            $url = add_query_arg([
                'page'    => 'aavni-storefront',
                'section' => $section,
                'msg'     => rawurlencode($message),
            ], admin_url('admin.php'));
        }

        wp_safe_redirect($url);
        exit;
    }

    /**
     * Render the Storefront Customizer Workspace
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce') && !current_user_can('edit_theme_options')) {
            wp_die(__('You do not have sufficient permissions to customize storefront content.', 'aavni'));
        }

        $active_section = isset($_GET['section']) ? sanitize_key($_GET['section']) : 'slider';
        $valid_sections = ['slider', 'announcement', 'categories', 'story', 'branding'];
        if (!in_array($active_section, $valid_sections, true)) {
            $active_section = 'slider';
        }

        $is_frontend = !is_admin() || (function_exists('is_page') && is_page('admin-portal'));
        $base_url = $is_frontend ? home_url('/admin-portal/?tab=storefront') : admin_url('admin.php?page=aavni-storefront');

        $flash_msg = isset($_GET['msg']) ? sanitize_text_field(rawurldecode($_GET['msg'])) : '';
        ?>

        <div class="wrap aavni-admin-wrap">
            <!-- Header -->
            <div class="aavni-admin-header">
                <div class="aavni-header-title-group">
                    <div class="aavni-header-logo-badge">🎨</div>
                    <div>
                        <h1 class="aavni-header-title">Storefront & Customer Content Manager</h1>
                        <p class="aavni-header-subtitle">Easily edit homepage hero slides, promo banners, categories, editorial story, trust strips & contact info without touching code.</p>
                    </div>
                </div>
                <div class="aavni-header-actions">
                    <a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" class="aavni-btn aavni-btn-outline" style="display:inline-flex; align-items:center; gap:6px;">
                        <span>👁️ View Live Storefront</span>
                    </a>
                </div>
            </div>

            <!-- Submenu Navigation Pills (Admin Core) -->
            <?php AAVNI_Admin_Core::render_nav_bar('storefront'); ?>

            <?php if (!empty($flash_msg)) : ?>
                <div class="notice notice-success is-dismissible" style="margin-bottom:24px; padding:12px 18px; border-left:4px solid #10B981; background:#ECFDF5; border-radius:6px;">
                    <p style="margin:0; font-size:14px; color:#065F46; font-weight:600;">✓ <?php echo esc_html($flash_msg); ?></p>
                </div>
            <?php endif; ?>

            <!-- Storefront Sub-Tabs -->
            <div class="aavni-storefront-tabs-bar" style="display:flex; gap:8px; border-bottom:2px solid #E8E3DC; margin-bottom:28px; padding-bottom:8px; overflow-x:auto;">
                <a href="<?php echo esc_url(add_query_arg(['section' => 'slider'], $base_url)); ?>" 
                   class="aavni-sf-tab <?php echo ($active_section === 'slider') ? 'is-active' : ''; ?>">
                    🖼️ Hero Slider
                </a>
                <a href="<?php echo esc_url(add_query_arg(['section' => 'announcement'], $base_url)); ?>" 
                   class="aavni-sf-tab <?php echo ($active_section === 'announcement') ? 'is-active' : ''; ?>">
                    📢 Announcement Bar
                </a>
                <a href="<?php echo esc_url(add_query_arg(['section' => 'categories'], $base_url)); ?>" 
                   class="aavni-sf-tab <?php echo ($active_section === 'categories') ? 'is-active' : ''; ?>">
                    👗 Shop by Category
                </a>
                <a href="<?php echo esc_url(add_query_arg(['section' => 'story'], $base_url)); ?>" 
                   class="aavni-sf-tab <?php echo ($active_section === 'story') ? 'is-active' : ''; ?>">
                    ✨ Brand Story & Trust
                </a>
                <a href="<?php echo esc_url(add_query_arg(['section' => 'branding'], $base_url)); ?>" 
                   class="aavni-sf-tab <?php echo ($active_section === 'branding') ? 'is-active' : ''; ?>">
                    📞 Contact & Footer
                </a>
            </div>

            <!-- Section Content -->
            <?php
            switch ($active_section) {
                case 'announcement':
                    self::render_announcement_section($base_url);
                    break;
                case 'categories':
                    self::render_categories_section($base_url);
                    break;
                case 'story':
                    self::render_story_section($base_url);
                    break;
                case 'branding':
                    self::render_branding_section($base_url);
                    break;
                case 'slider':
                default:
                    self::render_slider_section($base_url);
                    break;
            }
            ?>
        </div>

        <style>
            .aavni-sf-tab {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 18px;
                background: #FFFFFF;
                border: 1px solid #D5CECE;
                border-radius: 8px;
                color: #2D2424;
                text-decoration: none;
                font-weight: 600;
                font-size: 13.5px;
                transition: all 0.2s ease;
                white-space: nowrap;
            }
            .aavni-sf-tab:hover {
                background: #F4EFEB;
                border-color: #A85A48;
                color: #A85A48;
            }
            .aavni-sf-tab.is-active {
                background: #1B1A18;
                color: #FFFFFF;
                border-color: #1B1A18;
                box-shadow: 0 4px 12px rgba(27, 26, 24, 0.15);
            }
            .aavni-slide-card {
                background: #FFFFFF;
                border: 1px solid var(--aavni-admin-border);
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 14px rgba(0,0,0,0.04);
                transition: transform 0.2s, box-shadow 0.2s;
                display: flex;
                flex-direction: column;
            }
            .aavni-slide-card:hover {
                box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            }
            .aavni-slide-thumb-wrap {
                position: relative;
                height: 190px;
                background: #2D2424;
                overflow: hidden;
            }
            .aavni-slide-thumb-img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                opacity: 0.85;
            }
            .aavni-slide-overlay-content {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 16px 20px;
                background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0) 100%);
                color: #FFFFFF;
            }
        </style>
        <?php
    }

    /**
     * 1. Render Hero Slider Manager
     */
    private static function render_slider_section($base_url) {
        $slides = self::get_slides();
        $settings = self::get_slider_settings();
        ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div>
                <h2 style="margin:0; font-family:var(--aavni-font-display); font-size:22px; color:var(--aavni-admin-dark);">Homepage Hero Carousel Slides</h2>
                <p style="margin:4px 0 0; color:var(--aavni-admin-muted); font-size:13px;">Manage full-width hero slides, banner photos, taglines, headlines, and call-to-action buttons.</p>
            </div>
            <button type="button" class="aavni-btn aavni-btn-primary" id="aavni_btn_add_slide" style="display:inline-flex; align-items:center; gap:6px;">
                <span>➕ Add New Slide</span>
            </button>
        </div>

        <!-- Slider Global Settings -->
        <div class="aavni-admin-card" style="margin-bottom:28px;">
            <div class="aavni-card-header">
                <h3 class="aavni-card-title">⚙️ Slider Carousel Settings</h3>
            </div>
            <div class="aavni-card-body">
                <form method="post" action="<?php echo esc_url($base_url); ?>">
                    <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                    <input type="hidden" name="aavni_action" value="save_aavni_slider_settings" />
                    <input type="hidden" name="section" value="slider" />

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; align-items:center;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="slider_enabled" value="1" <?php checked($settings['enabled'], 1); ?> style="width:18px; height:18px;" />
                            <span style="font-weight:600; font-size:14px;">Display Hero Slider on Homepage</span>
                        </label>

                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="slider_autoplay" value="1" <?php checked($settings['autoplay'], 1); ?> style="width:18px; height:18px;" />
                            <span style="font-weight:600; font-size:14px;">Enable Automatic Slide Transitions</span>
                        </label>

                        <div style="display:flex; align-items:center; gap:10px;">
                            <label for="slider_interval" style="font-weight:600; font-size:13.5px; white-space:nowrap;">Auto Transition Speed:</label>
                            <input type="number" name="slider_interval" id="slider_interval" min="2" max="30" value="<?php echo esc_attr($settings['interval']); ?>" style="width:70px; padding:6px 10px; border:1px solid #ccc; border-radius:6px;" />
                            <span style="font-size:12px; color:#666;">seconds</span>
                        </div>

                        <div>
                            <button type="submit" class="aavni-btn aavni-btn-sm aavni-btn-secondary">Save Slider Settings</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Slides Grid -->
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap:24px; margin-bottom:40px;">
            <?php foreach ($slides as $idx => $slide) : 
                $is_active = !empty($slide['active']);
                $slide_json = esc_attr(wp_json_encode($slide));
            ?>
                <div class="aavni-slide-card">
                    <!-- Thumbnail Preview -->
                    <div class="aavni-slide-thumb-wrap">
                        <img src="<?php echo esc_url($slide['image_url']); ?>" alt="<?php echo esc_attr($slide['title']); ?>" class="aavni-slide-thumb-img">
                        <div style="position:absolute; top:12px; left:12px; display:flex; gap:6px;">
                            <span class="aavni-badge" style="background:#1B1A18; color:#FFFFFF; font-weight:700;">Slide #<?php echo ($idx + 1); ?></span>
                            <?php if ($is_active) : ?>
                                <span class="aavni-badge aavni-badge-delivered">Active</span>
                            <?php else : ?>
                                <span class="aavni-badge aavni-badge-cancelled">Hidden</span>
                            <?php endif; ?>
                        </div>
                        <div class="aavni-slide-overlay-content">
                            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#C5A880; font-weight:600;"><?php echo esc_html($slide['tagline']); ?></div>
                            <h4 style="margin:4px 0 0; font-family:var(--aavni-font-display); font-size:17px; color:#FFFFFF; line-height:1.25;"><?php echo esc_html($slide['title']); ?></h4>
                        </div>
                    </div>

                    <!-- Details Body -->
                    <div style="padding:18px 20px; flex-grow:1; display:flex; flex-direction:column; justify-content:space-between;">
                        <p style="margin:0 0 16px; font-size:13px; color:#666; line-height:1.5;">
                            <?php echo esc_html(wp_trim_words($slide['desc'], 18)); ?>
                        </p>

                        <!-- Buttons Preview -->
                        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px;">
                            <?php if (!empty($slide['btn1_text'])) : ?>
                                <span style="background:#F4EFEB; color:#2D2424; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">
                                    CTA 1: <?php echo esc_html($slide['btn1_text']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($slide['btn2_text'])) : ?>
                                <span style="background:#F4EFEB; color:#2D2424; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">
                                    CTA 2: <?php echo esc_html($slide['btn2_text']); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Card Action Buttons -->
                        <div style="border-top:1px solid #EAE5DE; padding-top:14px; display:flex; align-items:center; justify-content:space-between; gap:6px;">
                            <div style="display:flex; gap:6px;">
                                <!-- Edit Button -->
                                <button type="button" class="aavni-btn aavni-btn-sm aavni-btn-primary aavni-btn-edit-slide" data-slide-index="<?php echo esc_attr($idx); ?>" data-slide='<?php echo $slide_json; ?>'>
                                    ✏️ Edit
                                </button>
                                
                                <!-- Move Up -->
                                <?php if ($idx > 0) : ?>
                                    <form method="post" action="<?php echo esc_url($base_url); ?>" style="display:inline;">
                                        <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                                        <input type="hidden" name="aavni_action" value="move_aavni_slide" />
                                        <input type="hidden" name="section" value="slider" />
                                        <input type="hidden" name="slide_index" value="<?php echo esc_attr($idx); ?>" />
                                        <input type="hidden" name="direction" value="up" />
                                        <button type="submit" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Move Up">▲</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Move Down -->
                                <?php if ($idx < count($slides) - 1) : ?>
                                    <form method="post" action="<?php echo esc_url($base_url); ?>" style="display:inline;">
                                        <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                                        <input type="hidden" name="aavni_action" value="move_aavni_slide" />
                                        <input type="hidden" name="section" value="slider" />
                                        <input type="hidden" name="slide_index" value="<?php echo esc_attr($idx); ?>" />
                                        <input type="hidden" name="direction" value="down" />
                                        <button type="submit" class="aavni-btn aavni-btn-sm aavni-btn-secondary" title="Move Down">▼</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <!-- Delete Form -->
                            <form method="post" action="<?php echo esc_url($base_url); ?>" onsubmit="return confirm('Are you sure you want to remove this slide?');" style="margin:0;">
                                <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                                <input type="hidden" name="aavni_action" value="delete_aavni_slide" />
                                <input type="hidden" name="section" value="slider" />
                                <input type="hidden" name="slide_index" value="<?php echo esc_attr($idx); ?>" />
                                <button type="submit" class="aavni-btn aavni-btn-sm aavni-btn-danger" title="Delete Slide">🗑️</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Add / Edit Slide Modal -->
        <div class="aavni-modal-overlay" id="aavniSlideModal">
            <div class="aavni-modal-box" style="max-width: 680px;">
                <div class="aavni-modal-header">
                    <h3 class="aavni-modal-title" id="aavni_slide_modal_heading">Add New Hero Slide</h3>
                    <button type="button" class="aavni-modal-close" data-aavni-dismiss="modal">&times;</button>
                </div>
                <form method="post" action="<?php echo esc_url($base_url); ?>">
                    <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                    <input type="hidden" name="aavni_action" value="save_aavni_slide" />
                    <input type="hidden" name="section" value="slider" />
                    <input type="hidden" name="slide_index" id="modal_slide_index" value="-1" />

                    <div class="aavni-modal-body">
                        <!-- Banner Image Upload with WP Media Library -->
                        <div style="margin-bottom:18px;">
                            <label style="display:block; font-weight:700; font-size:13px; margin-bottom:8px;">Slide Banner Background Image *</label>
                            <div style="display:flex; gap:16px; align-items:center;">
                                <div id="modal_slide_img_preview" style="width:140px; height:80px; border-radius:6px; border:2px dashed #C5A880; overflow:hidden; background:#F4EFEB; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <span style="font-size:11px; color:#888;">No Image</span>
                                </div>
                                <div style="flex-grow:1;">
                                    <input type="text" name="slide_image_url" id="modal_slide_image_url" placeholder="https://... image URL" required style="width:100%; margin-bottom:8px;" />
                                    <button type="button" class="aavni-btn aavni-btn-sm aavni-btn-secondary aavni-media-upload-btn" data-target-input="#modal_slide_image_url" data-target-preview="#modal_slide_img_preview" data-frame-title="Select Hero Slide Image" data-frame-btn="Use As Slide Image">
                                        🖼️ Select from Media Library
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tagline -->
                        <div style="margin-bottom:16px;">
                            <label for="modal_slide_tagline" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Tagline / Subheading</label>
                            <input type="text" name="slide_tagline" id="modal_slide_tagline" placeholder="e.g. Autumn • Festive • Everyday" style="width:100%;" />
                        </div>

                        <!-- Main Title -->
                        <div style="margin-bottom:16px;">
                            <label for="modal_slide_title" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Main Headline / Title *</label>
                            <input type="text" name="slide_title" id="modal_slide_title" placeholder="e.g. Made for Every Mood, Every Moment." required style="width:100%; font-size:15px; font-weight:600;" />
                        </div>

                        <!-- Description -->
                        <div style="margin-bottom:18px;">
                            <label for="modal_slide_desc" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Slide Description Text</label>
                            <textarea name="slide_desc" id="modal_slide_desc" rows="3" placeholder="Brief narrative highlighting the fabric, collection, or mood..." style="width:100%;"></textarea>
                        </div>

                        <!-- Buttons 1 & 2 -->
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:18px;">
                            <div>
                                <label style="display:block; font-weight:700; font-size:12.5px; margin-bottom:6px;">Primary CTA Button</label>
                                <input type="text" name="slide_btn1_text" id="modal_slide_btn1_text" placeholder="e.g. Shop All Collections" style="width:100%; margin-bottom:6px;" />
                                <input type="text" name="slide_btn1_url" id="modal_slide_btn1_url" placeholder="Link URL: /shop/" style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; font-size:12.5px; margin-bottom:6px;">Secondary CTA Button</label>
                                <input type="text" name="slide_btn2_text" id="modal_slide_btn2_text" placeholder="e.g. Explore Long Kurtis" style="width:100%; margin-bottom:6px;" />
                                <input type="text" name="slide_btn2_url" id="modal_slide_btn2_url" placeholder="Link URL: /product-category/long-kurti/" style="width:100%;" />
                            </div>
                        </div>

                        <!-- Overlay Darkness & Status -->
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:center;">
                            <div>
                                <label for="modal_slide_overlay_opacity" style="display:block; font-weight:700; font-size:12.5px; margin-bottom:6px;">Dark Overlay Tint (%)</label>
                                <input type="number" name="slide_overlay_opacity" id="modal_slide_overlay_opacity" min="0" max="90" value="35" style="width:100px;" />
                                <span style="font-size:11px; color:#888; margin-left:6px;">% Darkness over photo</span>
                            </div>
                            <div>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin-top:20px;">
                                    <input type="checkbox" name="slide_active" id="modal_slide_active" value="1" checked style="width:18px; height:18px;" />
                                    <span style="font-weight:700; font-size:13.5px;">Slide is Active & Visible</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="aavni-modal-footer">
                        <button type="button" class="aavni-btn aavni-btn-secondary" data-aavni-dismiss="modal">Cancel</button>
                        <button type="submit" class="aavni-btn aavni-btn-primary" id="modal_slide_submit_btn">Save Slide</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * 2. Render Top Announcement Bar Customizer
     */
    private static function render_announcement_section($base_url) {
        $announcement = self::get_announcement();
        ?>
        <div class="aavni-admin-card" style="max-width:880px;">
            <div class="aavni-card-header">
                <h3 class="aavni-card-title">📢 Store Announcement Bar Customizer</h3>
            </div>
            <div class="aavni-card-body">
                <form method="post" action="<?php echo esc_url($base_url); ?>">
                    <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                    <input type="hidden" name="aavni_action" value="save_aavni_announcement" />
                    <input type="hidden" name="section" value="announcement" />

                    <!-- Live Preview Box -->
                    <div style="margin-bottom:28px;">
                        <label style="display:block; font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#888; margin-bottom:8px;">Live Customer View Preview</label>
                        <div id="live_announcement_preview" style="background:<?php echo esc_attr($announcement['bg_color']); ?>; color:<?php echo esc_attr($announcement['text_color']); ?>; padding:12px 18px; text-align:center; font-size:13px; font-weight:500; letter-spacing:0.5px; border-radius:6px; display:flex; justify-content:center; align-items:center; gap:10px; flex-wrap:wrap; transition:all 0.2s;">
                            <span id="prev_text1"><?php echo esc_html($announcement['text1']); ?></span>
                            <span>&bull;</span>
                            <span id="prev_text2" style="color:#C5A880; font-weight:700;"><?php echo esc_html($announcement['text2']); ?></span>
                            <span>&bull;</span>
                            <span id="prev_text3"><?php echo esc_html($announcement['text3']); ?></span>
                        </div>
                    </div>

                    <!-- Enable Toggle -->
                    <div style="margin-bottom:24px; padding:14px 18px; background:#F4EFEB; border-radius:8px;">
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                            <input type="checkbox" name="announcement_enabled" value="1" <?php checked($announcement['enabled'], 1); ?> style="width:18px; height:18px;" />
                            <div>
                                <strong style="font-size:14px; color:#1B1A18;">Display Announcement Bar on Consumer Storefront</strong>
                                <div style="font-size:12px; color:#666;">When enabled, this bar appears at the very top of all customer-facing pages.</div>
                            </div>
                        </label>
                    </div>

                    <!-- Text Fields -->
                    <div style="margin-bottom:18px;">
                        <label for="announcement_text1" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Message Part 1 (Left Notification)</label>
                        <input type="text" name="announcement_text1" id="announcement_text1" value="<?php echo esc_attr($announcement['text1']); ?>" style="width:100%;" />
                    </div>

                    <div style="margin-bottom:18px;">
                        <label for="announcement_text2" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Message Part 2 (Gold Highlight Badge)</label>
                        <input type="text" name="announcement_text2" id="announcement_text2" value="<?php echo esc_attr($announcement['text2']); ?>" style="width:100%;" />
                    </div>

                    <div style="margin-bottom:24px;">
                        <label for="announcement_text3" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Message Part 3 (Right Assurance Tag)</label>
                        <input type="text" name="announcement_text3" id="announcement_text3" value="<?php echo esc_attr($announcement['text3']); ?>" style="width:100%;" />
                    </div>

                    <!-- Colors -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px;">
                        <div>
                            <label for="announcement_bg_color" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Bar Background Color</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="color" id="announcement_bg_color_picker" value="<?php echo esc_attr($announcement['bg_color']); ?>" style="width:42px; height:38px; border:none; border-radius:6px; cursor:pointer;" />
                                <input type="text" name="announcement_bg_color" id="announcement_bg_color" value="<?php echo esc_attr($announcement['bg_color']); ?>" style="width:140px;" />
                            </div>
                        </div>
                        <div>
                            <label for="announcement_text_color" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Text Color</label>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <input type="color" id="announcement_text_color_picker" value="<?php echo esc_attr($announcement['text_color']); ?>" style="width:42px; height:38px; border:none; border-radius:6px; cursor:pointer;" />
                                <input type="text" name="announcement_text_color" id="announcement_text_color" value="<?php echo esc_attr($announcement['text_color']); ?>" style="width:140px;" />
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="aavni-btn aavni-btn-primary">Save Announcement Bar</button>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * 3. Render Shop by Category Section Customizer
     */
    private static function render_categories_section($base_url) {
        $meta = self::get_categories_section();
        $cats = self::get_categories();
        ?>
        <div style="max-width:980px;">
            <form method="post" action="<?php echo esc_url($base_url); ?>">
                <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                <input type="hidden" name="aavni_action" value="save_aavni_categories" />
                <input type="hidden" name="section" value="categories" />

                <!-- Section Meta -->
                <div class="aavni-admin-card" style="margin-bottom:28px;">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">👗 Category Grid Header Information</h3>
                    </div>
                    <div class="aavni-card-body">
                        <div style="margin-bottom:18px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="categories_enabled" value="1" <?php checked($meta['enabled'], 1); ?> style="width:18px; height:18px;" />
                                <span style="font-weight:700; font-size:14px;">Display "Shop by Category" on Homepage</span>
                            </label>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:16px;">
                            <div>
                                <label style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Subheading Label</label>
                                <input type="text" name="cat_section_subheading" value="<?php echo esc_attr($meta['subheading']); ?>" style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Section Main Title</label>
                                <input type="text" name="cat_section_title" value="<?php echo esc_attr($meta['title']); ?>" style="width:100%;" />
                            </div>
                        </div>
                        <div>
                            <label style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Description Paragraph</label>
                            <input type="text" name="cat_section_desc" value="<?php echo esc_attr($meta['desc']); ?>" style="width:100%;" />
                        </div>
                    </div>
                </div>

                <!-- 4 Category Cards Grid -->
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:28px;">
                    <?php foreach ($cats as $idx => $cat) : ?>
                        <div class="aavni-admin-card" style="margin-bottom:0; display:flex; flex-direction:column;">
                            <div class="aavni-card-header" style="padding:12px 16px;">
                                <strong style="font-size:14px;">Card #<?php echo ($idx + 1); ?></strong>
                            </div>
                            <div class="aavni-card-body" style="padding:16px; flex-grow:1;">
                                <!-- Image Preview & Uploader -->
                                <div style="margin-bottom:14px;">
                                    <div id="cat_preview_<?php echo $idx; ?>" style="height:150px; border-radius:6px; overflow:hidden; background:#F4EFEB; margin-bottom:8px; border:1px solid #EAE5DE;">
                                        <img src="<?php echo esc_url($cat['image_url']); ?>" alt="<?php echo esc_attr($cat['title']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <input type="hidden" name="categories[<?php echo $idx; ?>][image_url]" id="cat_input_<?php echo $idx; ?>" value="<?php echo esc_attr($cat['image_url']); ?>" />
                                    <button type="button" class="aavni-btn aavni-btn-sm aavni-btn-secondary aavni-media-upload-btn" data-target-input="#cat_input_<?php echo $idx; ?>" data-target-preview="#cat_preview_<?php echo $idx; ?>" style="width:100%; font-size:12px;">
                                        📷 Change Photo
                                    </button>
                                </div>

                                <div style="margin-bottom:10px;">
                                    <label style="font-size:12px; font-weight:700; color:#555;">Title</label>
                                    <input type="text" name="categories[<?php echo $idx; ?>][title]" value="<?php echo esc_attr($cat['title']); ?>" style="width:100%;" required />
                                </div>

                                <div style="margin-bottom:10px;">
                                    <label style="font-size:12px; font-weight:700; color:#555;">Subtitle / Tag</label>
                                    <input type="text" name="categories[<?php echo $idx; ?>][subtitle]" value="<?php echo esc_attr($cat['subtitle']); ?>" style="width:100%;" />
                                </div>

                                <div style="margin-bottom:10px;">
                                    <label style="font-size:12px; font-weight:700; color:#555;">Link URL</label>
                                    <input type="text" name="categories[<?php echo $idx; ?>][url]" value="<?php echo esc_attr($cat['url']); ?>" style="width:100%;" required />
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="aavni-btn aavni-btn-primary">Save Category Highlights</button>
            </form>
        </div>
        <?php
    }

    /**
     * 4. Render Brand Story & Trust Badges Section
     */
    private static function render_story_section($base_url) {
        $story = self::get_brand_story();
        $benefits = self::get_benefits();
        ?>
        <div style="max-width:980px;">
            <form method="post" action="<?php echo esc_url($base_url); ?>">
                <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                <input type="hidden" name="aavni_action" value="save_aavni_brand_story" />
                <input type="hidden" name="section" value="story" />

                <!-- Brand Story Section -->
                <div class="aavni-admin-card" style="margin-bottom:28px;">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">📖 Editorial Brand Story ("Our Philosophy")</h3>
                    </div>
                    <div class="aavni-card-body">
                        <div style="margin-bottom:18px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="story_enabled" value="1" <?php checked($story['enabled'], 1); ?> style="width:18px; height:18px;" />
                                <span style="font-weight:700; font-size:14px;">Show Brand Story on Homepage</span>
                            </label>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                            <div>
                                <label style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Section Subheading</label>
                                <input type="text" name="story_subheading" value="<?php echo esc_attr($story['subheading']); ?>" style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Signature Quote</label>
                                <input type="text" name="story_quote" value="<?php echo esc_attr($story['quote']); ?>" style="width:100%;" />
                            </div>
                        </div>

                        <div style="margin-bottom:18px;">
                            <label style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Brand Narrative Paragraph</label>
                            <textarea name="story_text" rows="4" style="width:100%;"><?php echo esc_textarea($story['text']); ?></textarea>
                        </div>

                        <!-- Story Image -->
                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-weight:700; font-size:13px; margin-bottom:8px;">Editorial Feature Image</label>
                            <div style="display:flex; gap:16px; align-items:center;">
                                <div id="story_img_preview" style="width:140px; height:90px; border-radius:6px; overflow:hidden; background:#F4EFEB; border:1px solid #ddd; flex-shrink:0;">
                                    <img src="<?php echo esc_url($story['image_url']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                </div>
                                <div style="flex-grow:1;">
                                    <input type="text" name="story_image_url" id="story_image_url" value="<?php echo esc_attr($story['image_url']); ?>" style="width:100%; margin-bottom:8px;" />
                                    <button type="button" class="aavni-btn aavni-btn-sm aavni-btn-secondary aavni-media-upload-btn" data-target-input="#story_image_url" data-target-preview="#story_img_preview">
                                        🖼️ Select from Media Library
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Value Badges -->
                        <div>
                            <label style="display:block; font-weight:700; font-size:13px; margin-bottom:8px;">4 Signature Value Points</label>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <?php for ($i = 0; $i < 4; $i++) : 
                                    $val = isset($story['badges'][$i]) ? $story['badges'][$i] : '';
                                ?>
                                    <input type="text" name="story_badges[]" value="<?php echo esc_attr($val); ?>" placeholder="e.g. 100% Breathable Cotton" style="width:100%;" />
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trust Strip -->
                <div class="aavni-admin-card" style="margin-bottom:28px;">
                    <div class="aavni-card-header">
                        <h3 class="aavni-card-title">🛡️ 4 Trust & Service Benefit Badges</h3>
                    </div>
                    <div class="aavni-card-body">
                        <div style="margin-bottom:18px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="benefits_enabled" value="1" <?php checked($benefits['enabled'], 1); ?> style="width:18px; height:18px;" />
                                <span style="font-weight:700; font-size:14px;">Display Trust Strip on Homepage</span>
                            </label>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <?php foreach ($benefits['items'] as $b_idx => $b_item) : ?>
                                <div style="background:#F9F7F5; padding:16px; border-radius:8px; border:1px solid #EAE5DE;">
                                    <div style="display:flex; gap:10px; margin-bottom:8px; align-items:center;">
                                        <input type="text" name="benefits[<?php echo $b_idx; ?>][icon]" value="<?php echo esc_attr($b_item['icon'] ?? '✨'); ?>" style="width:50px; text-align:center; font-size:18px;" />
                                        <input type="text" name="benefits[<?php echo $b_idx; ?>][title]" value="<?php echo esc_attr($b_item['title']); ?>" style="flex-grow:1; font-weight:600;" placeholder="Benefit Title" />
                                    </div>
                                    <input type="text" name="benefits[<?php echo $b_idx; ?>][desc]" value="<?php echo esc_attr($b_item['desc']); ?>" style="width:100%; font-size:12.5px;" placeholder="Short Explanation" />
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <button type="submit" class="aavni-btn aavni-btn-primary">Save Brand Story & Trust Badges</button>
            </form>
        </div>
        <?php
    }

    /**
     * 5. Render Header, Footer & Contact Info Customizer
     */
    private static function render_branding_section($base_url) {
        $branding = self::get_branding();
        ?>
        <div class="aavni-admin-card" style="max-width:880px;">
            <div class="aavni-card-header">
                <h3 class="aavni-card-title">📞 Brand Tagline, Customer Care & Footer Links</h3>
            </div>
            <div class="aavni-card-body">
                <form method="post" action="<?php echo esc_url($base_url); ?>">
                    <?php wp_nonce_field('aavni_storefront_nonce'); ?>
                    <input type="hidden" name="aavni_action" value="save_aavni_branding_footer" />
                    <input type="hidden" name="section" value="branding" />

                    <!-- Brand Tagline -->
                    <div style="margin-bottom:20px;">
                        <label for="store_tagline" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Official Brand Slogan / Tagline</label>
                        <input type="text" name="store_tagline" id="store_tagline" value="<?php echo esc_attr($branding['tagline']); ?>" style="width:100%; font-size:15px; font-weight:600;" />
                        <span style="font-size:11.5px; color:#888;">Appears under the AAVNI logo in both the primary header and footer.</span>
                    </div>

                    <!-- Contact Details -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
                        <div>
                            <label for="care_phone" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Customer Helpline Phone / WhatsApp</label>
                            <input type="text" name="care_phone" id="care_phone" value="<?php echo esc_attr($branding['care_phone']); ?>" style="width:100%;" />
                        </div>
                        <div>
                            <label for="support_email" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Customer Support Email</label>
                            <input type="email" name="support_email" id="support_email" value="<?php echo esc_attr($branding['support_email']); ?>" style="width:100%;" />
                        </div>
                    </div>

                    <div style="margin-bottom:24px;">
                        <label for="operating_hours" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Support Operating Hours</label>
                        <input type="text" name="operating_hours" id="operating_hours" value="<?php echo esc_attr($branding['operating_hours']); ?>" style="width:100%;" />
                        <span style="font-size:11.5px; color:#888;">Shown on the Contact Us page and footer care details.</span>
                    </div>

                    <!-- Social Links -->
                    <div style="border-top:1px solid #EAE5DE; padding-top:20px; margin-bottom:24px;">
                        <h4 style="margin:0 0 14px; font-size:15px; color:#1B1A18;">Social Media Channels</h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div>
                                <label style="display:block; font-weight:600; font-size:12.5px; margin-bottom:4px;">Instagram Profile URL</label>
                                <input type="url" name="social_instagram" value="<?php echo esc_attr($branding['instagram']); ?>" placeholder="https://instagram.com/..." style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; font-size:12.5px; margin-bottom:4px;">Facebook Page URL</label>
                                <input type="url" name="social_facebook" value="<?php echo esc_attr($branding['facebook']); ?>" placeholder="https://facebook.com/..." style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; font-size:12.5px; margin-bottom:4px;">Pinterest Profile URL</label>
                                <input type="url" name="social_pinterest" value="<?php echo esc_attr($branding['pinterest']); ?>" placeholder="https://pinterest.com/..." style="width:100%;" />
                            </div>
                            <div>
                                <label style="display:block; font-weight:600; font-size:12.5px; margin-bottom:4px;">WhatsApp Direct Chat Link</label>
                                <input type="url" name="social_whatsapp" value="<?php echo esc_attr($branding['whatsapp']); ?>" placeholder="https://wa.me/..." style="width:100%;" />
                            </div>
                        </div>
                    </div>

                    <!-- Footer Copyright -->
                    <div style="border-top:1px solid #EAE5DE; padding-top:20px; margin-bottom:28px;">
                        <label for="footer_copyright" style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">Footer Copyright Disclaimer</label>
                        <input type="text" name="footer_copyright" id="footer_copyright" value="<?php echo esc_attr($branding['copyright']); ?>" style="width:100%;" />
                        <span style="font-size:11.5px; color:#888;">Rendered alongside the current year: © <?php echo date('Y'); ?> [Your Text].</span>
                    </div>

                    <button type="submit" class="aavni-btn aavni-btn-primary">Save Branding & Contact Details</button>
                </form>
            </div>
        </div>
        <?php
    }
}

/**
 * Global Template Helper Functions for consumer storefront
 */
function aavni_get_hero_slides() {
    return AAVNI_Admin_Storefront::get_slides();
}

function aavni_get_slider_settings() {
    return AAVNI_Admin_Storefront::get_slider_settings();
}

function aavni_get_announcement_bar() {
    return AAVNI_Admin_Storefront::get_announcement();
}

function aavni_get_homepage_categories() {
    return AAVNI_Admin_Storefront::get_categories();
}

function aavni_get_homepage_categories_section() {
    return AAVNI_Admin_Storefront::get_categories_section();
}

function aavni_get_homepage_story() {
    return AAVNI_Admin_Storefront::get_brand_story();
}

function aavni_get_homepage_benefits() {
    return AAVNI_Admin_Storefront::get_benefits();
}

function aavni_get_homepage_newsletter() {
    return AAVNI_Admin_Storefront::get_newsletter();
}

function aavni_get_brand_tagline() {
    $branding = AAVNI_Admin_Storefront::get_branding();
    return $branding['tagline'];
}

function aavni_get_care_phone() {
    $branding = AAVNI_Admin_Storefront::get_branding();
    return $branding['care_phone'];
}

function aavni_get_support_email() {
    $branding = AAVNI_Admin_Storefront::get_branding();
    return $branding['support_email'];
}

function aavni_get_operating_hours() {
    $branding = AAVNI_Admin_Storefront::get_branding();
    return $branding['operating_hours'];
}

function aavni_get_social_link($platform) {
    $branding = AAVNI_Admin_Storefront::get_branding();
    return isset($branding[$platform]) ? $branding[$platform] : '';
}

function aavni_get_footer_copyright() {
    $branding = AAVNI_Admin_Storefront::get_branding();
    return $branding['copyright'];
}
