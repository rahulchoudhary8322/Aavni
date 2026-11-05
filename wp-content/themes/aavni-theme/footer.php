<!-- Footer -->
<footer class="aavni-footer">
    <div class="aavni-container">
        <div class="aavni-footer-grid">
            
            <!-- Brand Column -->
            <div style="max-width: 360px;">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="aavni-logo-wrap" style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; text-decoration: none;">
                    <img src="<?php echo esc_url(aavni_get_logo_url()); ?>" alt="AAVNI" style="height: 42px; width: auto; max-width: 120px; object-fit: contain;">
                    <div style="display: flex; flex-direction: column;">
                        <span class="aavni-brand-name" style="font-family: var(--font-heading); font-size: 22px; font-weight: 600; letter-spacing: 0.12em; line-height: 1;">AAVNI</span>
                        <span style="font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.16em; color: var(--aavni-text-muted); margin-top: 3px;"><?php echo esc_html(aavni_get_brand_tagline()); ?></span>
                    </div>
                </a>
                <p style="color: var(--aavni-text-muted); font-size: 14px; line-height: 1.6; margin-bottom: 20px;">
                    Handcrafted everyday elegance. Thoughtfully designed Indian ethnic wear, contemporary kurtis, and loungewear made with 100% breathable pure cotton.
                </p>
                <div style="font-size: 13.5px; color: var(--aavni-text-muted);">
                    <div><strong>Customer Care:</strong> <?php echo esc_html(aavni_get_support_email()); ?></div>
                    <div style="margin-top: 4px;"><strong>Hours:</strong> <?php echo esc_html(aavni_get_operating_hours()); ?></div>
                </div>
            </div>

            <!-- Collections Column -->
            <div>
                <h4 class="aavni-footer-title">Collections</h4>
                <ul class="aavni-footer-links">
                    <li><a href="<?php echo esc_url(home_url('/product-category/long-kurti/')); ?>">Long Kurti</a></li>
                    <li><a href="<?php echo esc_url(home_url('/product-category/short-kurti/')); ?>">Short Kurti</a></li>
                    <li><a href="<?php echo esc_url(home_url('/product-category/tops/')); ?>">Tops &amp; Tunics</a></li>
                    <li><a href="<?php echo esc_url(home_url('/product-category/nighty/')); ?>">Nightwear &amp; Loungewear</a></li>
                    <li><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">All Products</a></li>
                </ul>
            </div>

            <!-- Customer Service Column -->
            <div>
                <h4 class="aavni-footer-title">Customer Care</h4>
                <ul class="aavni-footer-links">
                    <li><a href="<?php echo esc_url(home_url('/size-guide/')); ?>" class="aavni-size-guide-toggle">Size Guide</a></li>
                    <li><a href="<?php echo esc_url(home_url('/shipping-policy/')); ?>">Shipping &amp; Delivery</a></li>
                    <li><a href="<?php echo esc_url(home_url('/refund_returns/')); ?>">Returns &amp; Exchanges</a></li>
                    <li><a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a></li>
                    <li><a href="<?php echo esc_url(home_url('/terms-and-conditions/')); ?>">Terms &amp; Conditions</a></li>
                    <li><a href="<?php echo esc_url(home_url('/faq/')); ?>">FAQ &amp; Help</a></li>
                </ul>
            </div>

            <!-- Newsletter & Social -->
            <div>
                <h4 class="aavni-footer-title">Stay Connected</h4>
                <p style="color: var(--aavni-text-muted); font-size: 13.5px; margin-bottom: 16px;">
                    Subscribe to receive exclusive access to new launches, private sales, and seasonal edits.
                </p>
                <form id="aavniFooterNewsletter" style="display: flex; gap: 8px; margin-bottom: 20px;">
                    <input type="email" placeholder="Enter your email" required style="flex-grow: 1; padding: 10px 14px; border: 1px solid var(--aavni-border); border-radius: 4px; font-size: 13px; font-family: inherit;">
                    <button type="submit" class="aavni-btn aavni-btn-primary" style="padding: 10px 18px; font-size: 11px;">Join</button>
                </form>
                <div style="display: flex; gap: 14px; color: var(--aavni-text-muted);">
                    <?php if ($ig = aavni_get_social_link('instagram')) : ?>
                        <a href="<?php echo esc_url($ig); ?>" target="_blank" rel="noopener" aria-label="Instagram" style="color: inherit;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($fb = aavni_get_social_link('facebook')) : ?>
                        <a href="<?php echo esc_url($fb); ?>" target="_blank" rel="noopener" aria-label="Facebook" style="color: inherit;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($pin = aavni_get_social_link('pinterest')) : ?>
                        <a href="<?php echo esc_url($pin); ?>" target="_blank" rel="noopener" aria-label="Pinterest" style="color: inherit;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 12c.5 2.5 1.5 5 2.5 7"></path><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Footer Bottom Bar -->
        <div class="aavni-footer-bottom">
            <div>
                &copy; <?php echo date('Y'); ?> <?php echo esc_html(aavni_get_footer_copyright()); ?>
            </div>
            <div style="display: flex; gap: 16px; align-items: center; font-size: 12px; color: var(--aavni-text-subtle);">
                <span>100% Secure Payments:</span>
                <span>UPI &bull; RuPay &bull; Cards &bull; NetBanking &bull; COD</span>
            </div>
        </div>
    </div>
</footer>

<!-- Global Modal Overlay -->
<div class="aavni-modal-overlay" id="aavniModalOverlay"></div>

<!-- Slide-in Cart Drawer -->
<div class="aavni-cart-drawer" id="aavniCartDrawer">
    <div class="aavni-cart-drawer-header">
        <div style="display: flex; align-items: center; gap: 8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span class="aavni-heading" style="font-size: 18px; margin: 0;">Shopping Bag</span>
        </div>
        <button id="aavniCartDrawerClose" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--aavni-text-subtle);">&times;</button>
    </div>

    <!-- Free Shipping Progress Tracker -->
    <div style="background: var(--aavni-bg); padding: 12px 24px; font-size: 12.5px; border-bottom: 1px solid var(--aavni-border-light);">
        <?php
        $cart_total = WC()->cart ? WC()->cart->get_subtotal() : 0;
        $free_shipping_threshold = 999;
        if ($cart_total >= $free_shipping_threshold) {
            echo '<span style="color: #2e7d32; font-weight: 600;">✓ Congratulations! You have qualified for FREE Express Shipping!</span>';
        } else {
            $diff = $free_shipping_threshold - $cart_total;
            echo 'Add <strong>₹' . esc_html($diff) . '</strong> more to unlock <strong>FREE Express Shipping</strong>!';
        }
        ?>
    </div>

    <!-- Cart items container -->
    <div class="aavni-cart-drawer-body" id="aavniCartDrawerBody">
        <?php
        // Render initial cart drawer state
        if (WC()->cart && !WC()->cart->is_empty()) :
        ?>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0) :
                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                ?>
                        <li style="display: flex; gap: 14px; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid var(--aavni-border-light);">
                            <div style="width: 64px; height: 80px; flex-shrink: 0; border-radius: 4px; overflow: hidden; background: #f5f5f5;">
                                <?php echo $_product->get_image(array(64, 80)); ?>
                            </div>
                            <div style="flex-grow: 1;">
                                <h4 style="font-size: 14px; margin: 0 0 6px 0;">
                                    <a href="<?php echo esc_url($product_permalink); ?>" style="color: var(--aavni-text);"><?php echo wp_kses_post($_product->get_name()); ?></a>
                                </h4>
                                <div style="font-size: 13px; color: var(--aavni-text-muted);">
                                    Qty: <?php echo esc_html($cart_item['quantity']); ?> &times; <?php echo WC()->cart->get_product_price($_product); ?>
                                </div>
                            </div>
                            <div>
                                <a href="<?php echo esc_url(wc_get_cart_remove_url($cart_item_key)); ?>" style="color: var(--aavni-text-subtle); font-size: 18px;">&times;</a>
                            </div>
                        </li>
                <?php
                    endif;
                endforeach;
                ?>
            </ul>
        <?php else : ?>
            <div style="text-align: center; padding: 48px 0;">
                <p style="color: var(--aavni-text-muted); font-size: 15px; margin-bottom: 20px;">Your shopping bag is currently empty.</p>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="aavni-btn aavni-btn-primary" style="padding: 10px 24px; font-size: 12px;">Discover Collections</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cart Drawer Footer -->
    <div class="aavni-cart-drawer-footer">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; font-size: 16px;">
            <span style="font-weight: 500;">Subtotal</span>
            <span style="font-weight: 700; color: var(--aavni-text);"><?php echo WC()->cart ? WC()->cart->get_cart_subtotal() : '₹0'; ?></span>
        </div>
        <p style="font-size: 12px; color: var(--aavni-text-muted); margin: 0 0 16px 0;">Taxes and shipping calculated at checkout.</p>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="aavni-btn aavni-btn-primary" style="width: 100%; box-sizing: border-box; text-align: center;">Proceed to Checkout</a>
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="aavni-btn aavni-btn-outline" style="width: 100%; box-sizing: border-box; text-align: center; padding: 10px 0;">View Full Bag</a>
        </div>
    </div>
</div>

<!-- Size Guide Modal -->
<div class="aavni-modal-overlay" id="aavniSizeGuideModal">
    <div class="aavni-modal-container">
        <button class="aavni-modal-close" id="aavniSizeGuideClose">&times;</button>
        <div style="text-align: center; margin-bottom: 24px;">
            <span class="aavni-subheading">Fit &amp; Sizing</span>
            <h3 class="aavni-section-title" style="font-size: 26px; margin-bottom: 6px;">Women's Ethnic Wear Size Guide</h3>
            <p style="color: var(--aavni-text-muted); font-size: 14px;">All measurements are in inches. Pure cotton garment measurements are tailored with a relaxed, comfortable ease.</p>
        </div>
        <table class="aavni-size-table">
            <thead>
                <tr>
                    <th>Size</th>
                    <th>Bust (in)</th>
                    <th>Waist (in)</th>
                    <th>Hip (in)</th>
                    <th>Kurti Length (in)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>XS (34)</strong></td>
                    <td>34"</td>
                    <td>30"</td>
                    <td>38"</td>
                    <td>44" - 46"</td>
                </tr>
                <tr>
                    <td><strong>S (36)</strong></td>
                    <td>36"</td>
                    <td>32"</td>
                    <td>40"</td>
                    <td>44" - 46"</td>
                </tr>
                <tr>
                    <td><strong>M (38)</strong></td>
                    <td>38"</td>
                    <td>34"</td>
                    <td>42"</td>
                    <td>45" - 47"</td>
                </tr>
                <tr>
                    <td><strong>L (40)</strong></td>
                    <td>40"</td>
                    <td>36"</td>
                    <td>44"</td>
                    <td>45" - 47"</td>
                </tr>
                <tr>
                    <td><strong>XL (42)</strong></td>
                    <td>42"</td>
                    <td>38"</td>
                    <td>46"</td>
                    <td>46" - 48"</td>
                </tr>
                <tr>
                    <td><strong>XXL (44)</strong></td>
                    <td>44"</td>
                    <td>40"</td>
                    <td>48"</td>
                    <td>46" - 48"</td>
                </tr>
            </tbody>
        </table>
        <div style="margin-top: 24px; font-size: 13px; color: var(--aavni-text-muted); background: var(--aavni-bg); padding: 14px; border-radius: 4px;">
            <strong>Measuring Tips:</strong> Measure around the fullest part of your bust and hips while keeping the tape comfortably loose. For cotton fabrics, we suggest choosing your exact regular size.
        </div>
    </div>
</div>

<!-- Search Overlay Modal -->
<div class="aavni-modal-overlay" id="aavniSearchModal">
    <div class="aavni-modal-container aavni-search-modal-container" style="max-width: 620px; text-align: left;">
        <button class="aavni-modal-close" id="aavniSearchClose">&times;</button>
        <div style="text-align: center; margin-bottom: 20px;">
            <span class="aavni-subheading">Product Catalog</span>
            <h3 class="aavni-section-title" style="font-size: 24px; margin: 4px 0 0 0;">Search AAVNI Apparel</h3>
        </div>
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="aavni-search-form" id="aavniSearchForm" style="display: flex; gap: 10px;">
            <div style="position: relative; flex-grow: 1;">
                <input type="search" name="s" id="aavniSearchInput" placeholder="Search kurtis, tops, nightwear, fabrics..." autocomplete="off" required style="width: 100%; box-sizing: border-box; padding: 14px 44px 14px 18px; border: 1px solid var(--aavni-border); border-radius: 4px; font-size: 15px; font-family: inherit; outline: none; transition: border-color 0.2s;">
                <span id="aavniSearchSpinner" style="display: none; position: absolute; right: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; border: 2px solid #ddd; border-top-color: var(--aavni-accent); border-radius: 50%; animation: aavniSpin 0.7s linear infinite;"></span>
            </div>
            <input type="hidden" name="post_type" value="product">
            <button type="submit" class="aavni-btn aavni-btn-primary" style="padding: 14px 26px; white-space: nowrap;">Search</button>
        </form>

        <!-- Popular Searches Quick Tags -->
        <div class="aavni-popular-searches" id="aavniPopularSearches" style="margin-top: 16px;">
            <span style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: var(--aavni-text-subtle); margin-right: 6px;">Popular:</span>
            <div class="aavni-popular-chips" style="display: inline-flex; flex-wrap: wrap; gap: 6px; vertical-align: middle;">
                <button type="button" class="aavni-search-tag" data-query="Long Kurti">Long Kurti</button>
                <button type="button" class="aavni-search-tag" data-query="Tops">Tops</button>
                <button type="button" class="aavni-search-tag" data-query="Cotton Nighty">Cotton Nighty</button>
                <button type="button" class="aavni-search-tag" data-query="Short Kurti">Short Kurti</button>
                <button type="button" class="aavni-search-tag" data-query="Halter Neck">Halter Neck</button>
                <button type="button" class="aavni-search-tag" data-query="Floral Print">Floral Print</button>
            </div>
        </div>

        <!-- Live Instant Search Results Container -->
        <div id="aavniLiveSearchResults" class="aavni-live-search-container" style="display: none; margin-top: 20px; max-height: 380px; overflow-y: auto;">
        </div>
    </div>
</div>

<!-- Mobile Sticky Bottom Navigation Bar (Zara / Myntra / Amazon App Style) -->
<nav class="aavni-mobile-bottom-nav" aria-label="Mobile Bottom Navigation">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="aavni-bottom-nav-item <?php echo is_front_page() ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            <polyline points="9 22 9 12 15 12 15 22"></polyline>
        </svg>
        <span>Home</span>
    </a>

    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="aavni-bottom-nav-item <?php echo (is_shop() || is_product_category()) ? 'active' : ''; ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
        </svg>
        <span>Shop</span>
    </a>

    <button type="button" class="aavni-bottom-nav-item aavni-search-toggle" aria-label="Search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <span>Search</span>
    </button>

    <button type="button" class="aavni-bottom-nav-item aavni-wishlist-nav-btn" aria-label="Wishlist">
        <div class="aavni-bottom-nav-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
            <span class="aavni-badge-count aavni-wishlist-count" style="display: none;">0</span>
        </div>
        <span>Wishlist</span>
    </button>

    <button type="button" class="aavni-bottom-nav-item aavni-cart-toggle" aria-label="Bag">
        <div class="aavni-bottom-nav-icon-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <span class="aavni-badge-count aavni-cart-count">
                <?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
            </span>
        </div>
        <span>Bag</span>
    </button>
</nav>

<!-- Floating WhatsApp Support Widget (Direct to 9828103222) -->
<a href="https://wa.me/919828103222?text=Hello%20AAVNI%2C%20I%20have%20an%20inquiry%20about%20your%20collection." 
   class="aavni-whatsapp-float" 
   target="_blank" 
   rel="noopener noreferrer" 
   aria-label="Chat with AAVNI on WhatsApp (9828103222)"
   title="Chat on WhatsApp: 9828103222">
    <span class="aavni-whatsapp-pulse"></span>
    <span class="aavni-whatsapp-tooltip">Chat with us</span>
    <div class="aavni-whatsapp-icon-wrap">
        <svg viewBox="0 0 32 32" width="32" height="32" fill="#FFFFFF" aria-hidden="true">
            <path d="M16.01 2.002c-7.72 0-14 6.27-14 13.988 0 2.47.645 4.88 1.87 7.004L2 30l7.207-1.85a13.957 13.957 0 006.803 1.74h.006c7.717 0 13.996-6.27 13.996-13.99 0-3.738-1.455-7.25-4.103-9.897A13.91 13.91 0 0016.01 2.002zm0 25.597c-2.1 0-4.155-.564-5.952-1.63l-.426-.253-4.42 1.134 1.182-4.22-.278-.442c-1.173-1.864-1.794-4.02-1.794-6.2 0-6.425 5.23-11.645 11.66-11.645 3.115 0 6.042 1.214 8.248 3.42 2.203 2.204 3.416 5.13 3.414 8.245 0 6.427-5.232 11.59-11.632 11.59zm6.39-8.718c-.35-.175-2.072-1.022-2.394-1.14-.32-.116-.554-.174-.788.176-.234.35-.905 1.14-1.11 1.374-.204.234-.41.263-.76.088-.35-.175-1.478-.545-2.816-1.737-1.042-.93-1.745-2.078-1.95-2.428-.204-.35-.022-.54.153-.714.157-.156.35-.41.525-.613.176-.205.234-.35.35-.584.117-.234.059-.438-.03-.613-.087-.175-.788-1.9-1.08-2.6-.285-.684-.574-.59-.788-.602-.204-.01-.438-.013-.672-.013-.234 0-.613.088-.934.438-.32.35-1.228 1.2-1.228 2.924 0 1.724 1.257 3.39 1.432 3.624.175.234 2.474 3.777 5.992 5.297.837.362 1.49.578 2 .74.84.267 1.605.23 2.21.139.674-.101 2.072-.847 2.364-1.665.292-.818.292-1.52.204-1.666-.087-.145-.32-.233-.67-.408z"/>
        </svg>
    </div>
</a>

<?php wp_footer(); ?>
</body>
</html>
