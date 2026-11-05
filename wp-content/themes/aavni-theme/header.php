<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php
$announcement = aavni_get_announcement_bar();
if (!empty($announcement['enabled'])) :
    $bar_style = '';
    if (!empty($announcement['bg_color'])) {
        $bar_style .= 'background-color:' . esc_attr($announcement['bg_color']) . ';';
    }
    if (!empty($announcement['text_color'])) {
        $bar_style .= 'color:' . esc_attr($announcement['text_color']) . ';';
    }
?>
<!-- Announcement Bar -->
<div class="aavni-announcement-bar" <?php if (!empty($bar_style)) echo 'style="' . $bar_style . '"'; ?>>
    <span><?php echo esc_html($announcement['text1']); ?></span>
    <?php if (!empty($announcement['text2'])) : ?>
        <span>&bull;</span>
        <span class="highlight"><?php echo esc_html($announcement['text2']); ?></span>
    <?php endif; ?>
    <?php if (!empty($announcement['text3'])) : ?>
        <span>&bull;</span>
        <span><?php echo esc_html($announcement['text3']); ?></span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Header -->
<header class="aavni-header" id="aavniHeader">
    <div class="aavni-container">
        <div class="aavni-header-inner">
            
            <!-- Mobile Toggle -->
            <button class="aavni-mobile-toggle" id="aavniMobileToggle" aria-label="Open Navigation Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <!-- Brand Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="aavni-logo-wrap" style="display: flex; align-items: center; gap: 12px; text-decoration: none; flex-shrink: 0;">
                <img src="<?php echo esc_url(aavni_get_logo_url()); ?>" alt="AAVNI Logo" class="aavni-logo-img" width="120" height="42" style="height: 42px !important; width: auto !important; max-width: 120px !important; max-height: 42px !important; object-fit: contain !important; flex-shrink: 0 !important; display: block !important;">
                <div class="aavni-logo-text-group" style="display: flex; flex-direction: column;">
                    <span class="aavni-brand-name">AAVNI</span>
                    <span class="aavni-brand-tagline"><?php echo esc_html(aavni_get_brand_tagline()); ?></span>
                </div>
            </a>

            <!-- Desktop Navigation -->
            <nav class="aavni-nav" aria-label="Main Navigation">
                <ul class="aavni-nav-menu">
                    <li class="aavni-nav-item"><a href="<?php echo esc_url(home_url('/')); ?>">Home</a></li>
                    <li class="aavni-nav-item"><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">All Collections</a></li>
                    <li class="aavni-nav-item"><a href="<?php echo esc_url(home_url('/product-category/long-kurti/')); ?>">Long Kurti</a></li>
                    <li class="aavni-nav-item"><a href="<?php echo esc_url(home_url('/product-category/short-kurti/')); ?>">Short Kurti</a></li>
                    <li class="aavni-nav-item"><a href="<?php echo esc_url(home_url('/product-category/tops/')); ?>">Tops</a></li>
                    <li class="aavni-nav-item"><a href="<?php echo esc_url(home_url('/product-category/nighty/')); ?>">Nighty</a></li>
                    <li class="aavni-nav-item"><a href="<?php echo esc_url(home_url('/about-us/')); ?>">Our Story</a></li>
                    <li class="aavni-nav-item"><a href="<?php echo esc_url(home_url('/contact-us/')); ?>">Contact</a></li>
                </ul>
            </nav>

            <!-- Action Icons -->
            <div class="aavni-header-actions">
                <!-- Search Button -->
                <button class="aavni-action-btn aavni-search-toggle" aria-label="Search Catalog">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>

                <!-- Customer Account -->
                <?php if (is_user_logged_in()) : 
                    $current_user = wp_get_current_user();
                    $display_name = !empty($current_user->first_name) ? $current_user->first_name : $current_user->display_name;
                ?>
                    <div class="aavni-account-dropdown-wrap" style="position:relative; display:inline-flex; align-items:center;">
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="aavni-action-btn aavni-account-btn" aria-label="Customer Account: <?php echo esc_attr($display_name); ?>" title="<?php echo esc_attr($display_name); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </a>
                    </div>
                <?php else : ?>
                    <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="aavni-action-btn aavni-account-btn" aria-label="Customer Sign In or Register" title="Sign In / Register">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                <?php endif; ?>

                <!-- Wishlist -->
                <button class="aavni-action-btn aavni-wishlist-nav-btn" aria-label="Wishlist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                    <span class="aavni-badge-count aavni-wishlist-count" style="display: none;">0</span>
                </button>

                <!-- Shopping Cart Bag -->
                <a href="<?php echo esc_url(wc_get_page_permalink('cart')); ?>" class="aavni-action-btn aavni-cart-toggle" aria-label="Shopping Bag">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <path d="M16 10a4 4 0 0 1-8 0"></path>
                    </svg>
                    <span class="aavni-badge-count aavni-cart-count">
                        <?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
                    </span>
                </a>
            </div>

        </div>
    </div>
</header>

<!-- Rich Mobile Navigation Drawer -->
<div class="aavni-mobile-drawer" id="aavniMobileMenu">
    <div class="aavni-mobile-drawer-header">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="aavni-mobile-drawer-brand">
            <img src="<?php echo esc_url(aavni_get_logo_url()); ?>" alt="AAVNI" class="aavni-mobile-drawer-logo">
            <span class="aavni-mobile-drawer-title">AAVNI</span>
        </a>
        <button id="aavniMobileMenuClose" class="aavni-mobile-drawer-close" aria-label="Close menu">&times;</button>
    </div>

    <!-- Mobile User Welcome Card -->
    <div class="aavni-mobile-user-card">
        <div class="aavni-mobile-user-avatar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>
        <div class="aavni-mobile-user-info">
            <?php if (is_user_logged_in()) : 
                $m_user = wp_get_current_user();
                $m_name = !empty($m_user->first_name) ? $m_user->first_name : $m_user->display_name;
            ?>
                <span class="aavni-mobile-greeting">Namaste, <?php echo esc_html($m_name); ?></span>
                <div style="display:flex; gap:10px; margin-top:4px;">
                    <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="aavni-mobile-account-link" style="font-size:12px; font-weight:600;">My Account &rarr;</a>
                    <span style="color:#C5A880;">|</span>
                    <a href="<?php echo esc_url(wc_logout_url()); ?>" class="aavni-mobile-account-link" style="font-size:12px; color:#A85A48; font-weight:600;">Logout</a>
                </div>
            <?php else : ?>
                <span class="aavni-mobile-greeting">Welcome to AAVNI</span>
                <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="aavni-mobile-account-link">Sign In / Register &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Navigation Links -->
    <div class="aavni-mobile-drawer-body">
        <div class="aavni-mobile-nav-group-title">Curated Categories</div>
        <ul class="aavni-mobile-nav-list">
            <li>
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <span>Home</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(home_url('/product-category/long-kurti/')); ?>">
                    <span>Long Kurti</span>
                    <span class="aavni-mobile-nav-badge bestseller">Bestseller</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(home_url('/product-category/short-kurti/')); ?>">
                    <span>Short Kurti</span>
                    <span class="aavni-mobile-nav-badge">Trending</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(home_url('/product-category/tops/')); ?>">
                    <span>Tops &amp; Tunics</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(home_url('/product-category/nighty/')); ?>">
                    <span>Nightwear &amp; Loungewear</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">
                    <span>All Collections</span>
                    <span class="aavni-mobile-nav-badge">New</span>
                </a>
            </li>
        </ul>

        <div class="aavni-mobile-nav-group-title" style="margin-top: 20px;">Customer Service</div>
        <ul class="aavni-mobile-nav-list secondary">
            <li>
                <a href="<?php echo esc_url(home_url('/about-us/')); ?>">
                    <span>Our Artisanal Story</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(home_url('/size-guide/')); ?>" class="aavni-size-guide-toggle">
                    <span>Size &amp; Fit Guide</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(home_url('/contact-us/')); ?>">
                    <span>Contact Us &amp; Support</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(home_url('/refund_returns/')); ?>">
                    <span>Easy 7-Day Exchange</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Mobile Drawer Footer Strip -->
    <div class="aavni-mobile-drawer-footer">
        <div class="aavni-mobile-shipping-note">
            <span class="aavni-flag-emoji">🇮🇳</span>
            <span>Free Express Shipping Across India on ₹999+</span>
        </div>
    </div>
</div>
