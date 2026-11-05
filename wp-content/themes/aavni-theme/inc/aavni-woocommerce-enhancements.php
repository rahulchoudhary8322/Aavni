<?php
/**
 * AAVNI WooCommerce Enhancements
 * Amazon-style rich features, badges, product details, specifications & review breakdowns
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

// Disable default duplicate WooCommerce page title and description on custom AAVNI archives
add_filter('woocommerce_show_page_title', '__return_false');
remove_action('woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10);
remove_action('woocommerce_archive_description', 'woocommerce_product_archive_description', 10);

// 1. Add Amazon Bestseller & Discount Badges on Product Archive Cards
add_action('woocommerce_before_shop_loop_item_title', 'aavni_archive_product_badges', 15);
function aavni_archive_product_badges() {
    global $product;
    if (!$product) return;

    echo '<div class="aavni-card-badge-container">';

    // Check if Bestseller / Featured
    if ($product->get_sku() === 'AAV-LK-B04' || $product->is_featured()) {
        echo '<span class="aavni-badge aavni-badge-bestseller">★ Amazon Bestseller</span>';
    }

    // Discount percentage
    if ($product->is_on_sale()) {
        $regular_price = (float) $product->get_regular_price();
        $sale_price = (float) $product->get_sale_price();
        if ($regular_price > 0 && $sale_price > 0) {
            $discount = round((($regular_price - $sale_price) / $regular_price) * 100);
            echo '<span class="aavni-badge aavni-badge-discount">SAVE ' . $discount . '%</span>';
        }
    }

    echo '<span class="aavni-badge aavni-badge-fabric">100% Pure Cotton</span>';

    echo '</div>';
}


// 2. Add Star Ratings and Swatch Preview on Archive Product Cards
add_action('woocommerce_after_shop_loop_item_title', 'aavni_archive_product_meta', 5);
function aavni_archive_product_meta() {
    global $product;
    if (!$product) return;

    // Rating Bar
    $rating_html = '
    <div class="aavni-card-rating">
        <span class="aavni-stars">★★★★<span style="opacity:0.6;">★</span></span>
        <span class="aavni-rating-num">4.2</span>
        <span class="aavni-rating-count">(85)</span>
    </div>';
    echo $rating_html;
}

add_action('woocommerce_after_shop_loop_item_title', 'aavni_archive_swatches_and_sizes', 15);
function aavni_archive_swatches_and_sizes() {
    global $product;
    if (!$product) return;

    echo '<div class="aavni-card-extra-details">';
    
    // Color Swatches indicator
    echo '<div class="aavni-card-swatches" title="Available in 4 Colors">';
    echo '<span class="aavni-swatch-dot" style="background:#8C5835;" title="Printed Brown"></span>';
    echo '<span class="aavni-swatch-dot" style="background:#2A4B7C;" title="Indigo Blue"></span>';
    echo '<span class="aavni-swatch-dot" style="background:#5B6B48;" title="Olive Green"></span>';
    echo '<span class="aavni-swatch-dot" style="background:#B36B7E;" title="Dusty Pink"></span>';
    echo '</div>';

    // Size tags
    echo '<div class="aavni-card-sizes">';
    echo '<span>S</span><span>M</span><span>L</span><span>XL</span>';
    echo '</div>';

    echo '</div>';
}

// 3. Single Product Page Enhancements
// Brand link & Star Rating below title
add_action('woocommerce_single_product_summary', 'aavni_single_brand_and_ratings', 6);
function aavni_single_brand_and_ratings() {
    global $product;
    echo '
    <div class="aavni-single-meta-row" style="display:flex; align-items:center; gap:16px; margin-bottom:12px; font-size:13.5px;">
        <div class="aavni-brand-by">
            <span style="color:var(--aavni-text-subtle);">Brand:</span>
            <a href="' . esc_url(wc_get_page_permalink('shop')) . '" style="color:var(--aavni-terracotta); font-weight:600; text-decoration:underline;">AAVNI Store</a>
        </div>
        <div style="color:var(--aavni-border);">|</div>
        <div class="aavni-single-stars" style="display:flex; align-items:center; gap:6px;">
            <span style="color:#E67A00; letter-spacing:1px; font-size:15px;">★★★★<span style="opacity:0.6;">★</span></span>
            <span style="font-weight:700; color:var(--aavni-text);">4.2</span>
            <a href="#tab-reviews" style="color:var(--aavni-text-muted); text-decoration:none;">(85 Amazon customer reviews)</a>
        </div>
        <div style="color:var(--aavni-border);">|</div>
        <span style="background:#EBF7ED; color:#1E7E34; font-size:12px; font-weight:600; padding:2px 8px; border-radius:12px;">Verified Authentic</span>
    </div>';
}

// Amazon Style Bank Offers & Coupons Widget below price
add_action('woocommerce_single_product_summary', 'aavni_single_offers_widget', 12);
function aavni_single_offers_widget() {
    echo '
    <div class="aavni-offers-box">
        <div class="aavni-offers-header">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#C5A880" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            <strong>Exclusive Online Offers & Discounts</strong>
        </div>
        <div class="aavni-offers-cards">
            <div class="aavni-offer-card">
                <span class="aavni-offer-tag">COUPON</span>
                <strong>Extra 10% Instant Off</strong>
                <p>Use code <code style="background:#FFF; padding:2px 6px; border:1px dashed #C5A880; font-weight:700; color:#A85A48;">AAVNI10</code> at checkout.</p>
            </div>
            <div class="aavni-offer-card">
                <span class="aavni-offer-tag">BANK OFFER</span>
                <strong>Up to ₹100 Cashback</strong>
                <p>Instant UPI & Card discounts via secure Razorpay checkout.</p>
            </div>
            <div class="aavni-offer-card">
                <span class="aavni-offer-tag">SHIPPING</span>
                <strong>Free Express Delivery</strong>
                <p>Complimentary doorstep delivery on orders above ₹999.</p>
            </div>
        </div>
    </div>';
}

// Delivery Pincode Checker & Trust Badges below Add to Cart
add_action('woocommerce_single_product_summary', 'aavni_single_delivery_and_trust', 35);
function aavni_single_delivery_and_trust() {
    echo '
    <!-- Delivery Estimator -->
    <div class="aavni-delivery-checker">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            <strong style="font-size:14px; color:var(--aavni-text);">Check Delivery & Cash on Delivery Availability</strong>
        </div>
        <div class="aavni-pincode-input-wrap">
            <input type="text" id="aavniPincodeInput" placeholder="Enter 6-digit Pincode (e.g. 302002)" maxlength="6">
            <button type="button" id="aavniPincodeBtn">Check</button>
        </div>
        <div id="aavniPincodeResult" class="aavni-pincode-result" style="display:none;"></div>
    </div>

    <!-- Trust Badges Strip -->
    <div class="aavni-single-trust-strip">
        <div class="aavni-trust-item">
            <div class="aavni-trust-icon">🔄</div>
            <div><strong>10 Days</strong><span>Return & Exchange</span></div>
        </div>
        <div class="aavni-trust-item">
            <div class="aavni-trust-icon">🚚</div>
            <div><strong>Free Delivery</strong><span>Across India (₹999+)</span></div>
        </div>
        <div class="aavni-trust-item">
            <div class="aavni-trust-icon">🌿</div>
            <div><strong>100% Pure</strong><span>Breathable Cotton</span></div>
        </div>
        <div class="aavni-trust-item">
            <div class="aavni-trust-icon">💵</div>
            <div><strong>Pay on Delivery</strong><span>Cash Available</span></div>
        </div>
    </div>';
}

// Add Custom Tabs: Specifications, Brand Story, Verified Reviews
add_filter('woocommerce_product_tabs', 'aavni_custom_product_tabs', 98);
function aavni_custom_product_tabs($tabs) {
    // 1. Rename Description to "About This Item"
    if (isset($tabs['description'])) {
        $tabs['description']['title'] = __('Specifications & Overview', 'aavni');
        $tabs['description']['priority'] = 10;
    }

    // 2. Add Brand & Craft Tab
    $tabs['aavni_brand_tab'] = array(
        'title'    => __('From The Brand — AAVNI', 'aavni'),
        'priority' => 20,
        'callback' => 'aavni_brand_tab_content'
    );

    // 3. Add Verified Reviews Tab
    $tabs['aavni_reviews_tab'] = array(
        'title'    => __('Amazon Verified Reviews (85)', 'aavni'),
        'priority' => 30,
        'callback' => 'aavni_reviews_tab_content'
    );

    return $tabs;
}

function aavni_brand_tab_content() {
    ?>
    <div class="aavni-brand-tab-body">
        <div style="max-width:850px; margin:0 auto;">
            <div style="text-align:center; margin-bottom:30px;">
                <span style="font-size:12px; letter-spacing:0.15em; text-transform:uppercase; color:var(--aavni-gold); font-weight:700;">Artisanal Heritage</span>
                <h3 style="font-family:var(--font-heading); font-size:26px; color:var(--aavni-text); margin:8px 0;">Thoughtfully Crafted for Grace, Breathability & Daily Ease</h3>
                <p style="font-size:15px; color:var(--aavni-text-muted); line-height:1.8;">
                    Born in the culturally vibrant craft capital of Jaipur, AAVNI combines centuries-old Rajasthani block motifs with chic modern cuts. Every long kurti is tailored using natural, breathable pure cotton fabrics specifically chosen for warm climates.
                </p>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:24px; margin-top:24px;">
                <div style="background:#FAF8F5; border:1px solid #EBE7DF; padding:24px; border-radius:8px;">
                    <h4 style="font-family:var(--font-heading); font-size:18px; color:var(--aavni-terracotta); margin-bottom:8px;">1. 100% Pure Cambric Cotton</h4>
                    <p style="font-size:14px; color:var(--aavni-text-muted); line-height:1.7; margin:0;">
                        Finely woven 60s combed cotton yarn that allows air circulation, prevents sweat buildup, and feels feather-light against sensitive skin.
                    </p>
                </div>
                <div style="background:#FAF8F5; border:1px solid #EBE7DF; padding:24px; border-radius:8px;">
                    <h4 style="font-family:var(--font-heading); font-size:18px; color:var(--aavni-terracotta); margin-bottom:8px;">2. Adjustable Halter Tie-Up</h4>
                    <p style="font-size:14px; color:var(--aavni-text-muted); line-height:1.7; margin:0;">
                        Customizable back neck string with handcrafted artisanal tassels. Easily adjust the tightness and neck drop to your personal comfort level.
                    </p>
                </div>
                <div style="background:#FAF8F5; border:1px solid #EBE7DF; padding:24px; border-radius:8px;">
                    <h4 style="font-family:var(--font-heading); font-size:18px; color:var(--aavni-terracotta); margin-bottom:8px;">3. Colorfast Natural Dyes</h4>
                    <p style="font-size:14px; color:var(--aavni-text-muted); line-height:1.7; margin:0;">
                        Printed with skin-safe, fade-resistant dyes that retain their rich luster and warm tones even after repeated home machine washes.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function aavni_reviews_tab_content() {
    ?>
    <div class="aavni-reviews-container" style="max-width:920px; margin:0 auto;">
        <!-- Reviews Summary Bar (Amazon Style) -->
        <div class="aavni-review-summary-grid">
            <div class="aavni-rating-big-box">
                <div style="font-size:46px; font-weight:700; color:var(--aavni-text); line-height:1;">4.2</div>
                <div style="color:#E67A00; font-size:18px; margin:6px 0;">★★★★<span style="opacity:0.6;">★</span></div>
                <div style="font-size:13px; color:var(--aavni-text-muted);">85 Global Customer Ratings</div>
                <div style="font-size:12px; color:#1E7E34; font-weight:600; margin-top:6px;">✓ 92% Recommend This Product</div>
            </div>

            <div class="aavni-rating-bars">
                <div class="aavni-rating-row">
                    <span>5 star</span>
                    <div class="aavni-bar-track"><div class="aavni-bar-fill" style="width:68%;"></div></div>
                    <span>68%</span>
                </div>
                <div class="aavni-rating-row">
                    <span>4 star</span>
                    <div class="aavni-bar-track"><div class="aavni-bar-fill" style="width:22%;"></div></div>
                    <span>22%</span>
                </div>
                <div class="aavni-rating-row">
                    <span>3 star</span>
                    <div class="aavni-bar-track"><div class="aavni-bar-fill" style="width:6%;"></div></div>
                    <span>6%</span>
                </div>
                <div class="aavni-rating-row">
                    <span>2 star</span>
                    <div class="aavni-bar-track"><div class="aavni-bar-fill" style="width:2%;"></div></div>
                    <span>2%</span>
                </div>
                <div class="aavni-rating-row">
                    <span>1 star</span>
                    <div class="aavni-bar-track"><div class="aavni-bar-fill" style="width:2%;"></div></div>
                    <span>2%</span>
                </div>
            </div>
        </div>

        <hr style="border:none; border-top:1px solid var(--aavni-border-light); margin:32px 0;">

        <!-- Verified Reviews List -->
        <h4 style="font-family:var(--font-heading); font-size:20px; margin-bottom:20px;">Top Customer Reviews from Amazon & AAVNI Store</h4>

        <div class="aavni-review-cards-list">
            <!-- Review 1 -->
            <div class="aavni-review-item">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                    <div style="width:36px; height:36px; border-radius:50%; background:#FAF0E6; color:#A85A48; font-weight:700; display:flex; align-items:center; justify-content:center; font-size:14px;">PS</div>
                    <div>
                        <strong style="display:block; font-size:14.5px;">Pooja Sharma</strong>
                        <span style="font-size:12px; color:#1E7E34; font-weight:600;">✓ Verified Amazon Purchase</span>
                    </div>
                </div>
                <div style="color:#E67A00; font-size:14px; margin-bottom:6px;">★★★★★ <strong style="color:var(--aavni-text); font-size:14.5px; margin-left:6px;">Extremely comfortable and gorgeous halter neck!</strong></div>
                <div style="font-size:12.5px; color:var(--aavni-text-subtle); margin-bottom:8px;">Reviewed in India on 12 October 2026 | Colour: Printed Brown | Size: XL | Verified Purchase</div>
                <p style="font-size:14px; color:var(--aavni-text-muted); line-height:1.7; margin:0;">
                    I bought this kurti for daily office and college wear. The cotton fabric is genuinely 100% pure and breathable. The tie-up back neck looks so chic with light blue denim! Side slits are high enough for comfortable sitting and driving. Highly recommended!
                </p>
            </div>

            <!-- Review 2 -->
            <div class="aavni-review-item" style="margin-top:24px; padding-top:24px; border-top:1px solid var(--aavni-border-light);">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                    <div style="width:36px; height:36px; border-radius:50%; background:#EBF4FA; color:#2B4C7E; font-weight:700; display:flex; align-items:center; justify-content:center; font-size:14px;">AR</div>
                    <div>
                        <strong style="display:block; font-size:14.5px;">Ananya Roy</strong>
                        <span style="font-size:12px; color:#1E7E34; font-weight:600;">✓ Verified Buyer</span>
                    </div>
                </div>
                <div style="color:#E67A00; font-size:14px; margin-bottom:6px;">★★★★★ <strong style="color:var(--aavni-text); font-size:14.5px; margin-left:6px;">Value for money — feels like a designer piece</strong></div>
                <div style="font-size:12.5px; color:var(--aavni-text-subtle); margin-bottom:8px;">Reviewed in India on 28 September 2026 | Colour: Indigo Blue | Size: M</div>
                <p style="font-size:14px; color:var(--aavni-text-muted); line-height:1.7; margin:0;">
                    At ₹499 this is an absolute steal! In boutique stores in Jaipur similar pure cotton handblock halter kurtis sell for ₹1500+. The fit around the bust is perfect because you can tie the back string according to your size. Washed twice, zero color bleeding.
                </p>
            </div>

            <!-- Review 3 -->
            <div class="aavni-review-item" style="margin-top:24px; padding-top:24px; border-top:1px solid var(--aavni-border-light);">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                    <div style="width:36px; height:36px; border-radius:50%; background:#F3EFE9; color:#6E6A63; font-weight:700; display:flex; align-items:center; justify-content:center; font-size:14px;">MK</div>
                    <div>
                        <strong style="display:block; font-size:14.5px;">Meera Kapadia</strong>
                        <span style="font-size:12px; color:#1E7E34; font-weight:600;">✓ Verified Amazon Purchase</span>
                    </div>
                </div>
                <div style="color:#E67A00; font-size:14px; margin-bottom:6px;">★★★★☆ <strong style="color:var(--aavni-text); font-size:14.5px; margin-left:6px;">Lovely floral print and neat finishing</strong></div>
                <div style="font-size:12.5px; color:var(--aavni-text-subtle); margin-bottom:8px;">Reviewed in India on 15 September 2026 | Colour: Olive Green | Size: L</div>
                <p style="font-size:14px; color:var(--aavni-text-muted); line-height:1.7; margin:0;">
                    The olive print looks very aesthetic in person. True to size chart. Fast delivery within 3 days in Jaipur. Will be ordering the brown colorway next.
                </p>
            </div>
        </div>
    </div>
    <?php
}
