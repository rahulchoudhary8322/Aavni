<?php
/**
 * Template Name: AAVNI Luxury Homepage
 *
 * @package AAVNI
 */

get_header();

$upload_dir = wp_upload_dir();
$banner_img = $upload_dir['baseurl'] . '/aavni/aavni-hero-banner.png';
$long_kurti_img = $upload_dir['baseurl'] . '/aavni/long-kurti-1.jpg';
$short_kurti_img = $upload_dir['baseurl'] . '/aavni/short-kurti-1.jpg';
$chic_top_img = $upload_dir['baseurl'] . '/aavni/chic-top-1.jpg';
$nightwear_img = $upload_dir['baseurl'] . '/aavni/nightwear-1.jpg';
?>

<main id="primary" class="site-main">

    <?php
    $slides = aavni_get_hero_slides();
    $slider_settings = aavni_get_slider_settings();
    $active_slides = array_filter($slides, function($s) { return !empty($s['active']); });
    if (empty($active_slides)) {
        $active_slides = $slides;
    }
    $active_slides = array_values($active_slides);
    ?>

    <?php if (!empty($active_slides) && !empty($slider_settings['enabled'])) : ?>
    <!-- Section A: Full-Width Edge-to-Edge Hero Carousel Slider -->
    <section class="aavni-hero-slider-section" aria-label="AAVNI Featured Collections">
        <div class="aavni-hero-slider" id="aavniHeroSlider" 
             data-autoplay="<?php echo !empty($slider_settings['autoplay']) ? 'true' : 'false'; ?>" 
             data-autoplay-interval="<?php echo esc_attr(intval($slider_settings['interval'] ?? 5) * 1000); ?>">
            <div class="aavni-hero-slides-wrapper" id="aavniHeroWrapper">
                
                <?php foreach ($active_slides as $idx => $slide) :
                    $overlay_style = '';
                    if (isset($slide['overlay_opacity']) && $slide['overlay_opacity'] !== '') {
                        $op = floatval($slide['overlay_opacity']) / 100;
                        $op_left = min(0.88, max(0.55, $op * 1.6));
                        $op_mid  = min(0.60, max(0.25, $op * 1.1));
                        $overlay_style = 'background: linear-gradient(90deg, rgba(27, 24, 22, ' . esc_attr($op_left) . ') 0%, rgba(27, 24, 22, ' . esc_attr($op_mid) . ') 48%, rgba(27, 24, 22, 0.08) 82%, rgba(27, 24, 22, 0) 100%);';
                    }
                ?>
                    <div class="aavni-hero-slide <?php echo $idx === 0 ? 'active' : ''; ?>" data-slide-index="<?php echo esc_attr($idx); ?>">
                        <div class="aavni-hero-slide-bg">
                            <img src="<?php echo esc_url($slide['image_url']); ?>" alt="<?php echo esc_attr($slide['title']); ?>" class="aavni-hero-banner-img">
                        </div>
                        <div class="aavni-hero-overlay" <?php if (!empty($overlay_style)) echo 'style="' . $overlay_style . '"'; ?>>
                            <div class="aavni-hero-content">
                                <?php if (!empty($slide['tagline'])) : ?>
                                    <span class="aavni-hero-tagline"><?php echo esc_html($slide['tagline']); ?></span>
                                <?php endif; ?>

                                <?php if ($idx === 0) : ?>
                                    <h1 class="aavni-hero-title"><?php echo esc_html($slide['title']); ?></h1>
                                <?php else : ?>
                                    <h2 class="aavni-hero-title"><?php echo esc_html($slide['title']); ?></h2>
                                <?php endif; ?>

                                <?php if (!empty($slide['desc'])) : ?>
                                    <p class="aavni-hero-desc">
                                        <?php echo esc_html($slide['desc']); ?>
                                    </p>
                                <?php endif; ?>

                                <?php if (!empty($slide['btn1_text']) || !empty($slide['btn2_text'])) : ?>
                                    <div class="aavni-hero-ctas">
                                        <?php if (!empty($slide['btn1_text'])) : ?>
                                            <a href="<?php echo esc_url($slide['btn1_url']); ?>" class="aavni-btn aavni-btn-primary"><?php echo esc_html($slide['btn1_text']); ?></a>
                                        <?php endif; ?>
                                        <?php if (!empty($slide['btn2_text'])) : ?>
                                            <a href="<?php echo esc_url($slide['btn2_url']); ?>" class="aavni-btn aavni-btn-outline" style="border-color: #FFFFFF; color: #FFFFFF;"><?php echo esc_html($slide['btn2_text']); ?></a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <?php if (count($active_slides) > 1) : ?>
                <!-- Slider Arrows -->
                <button class="aavni-slider-btn aavni-slider-prev" id="aavniHeroPrev" aria-label="Previous Slide">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button class="aavni-slider-btn aavni-slider-next" id="aavniHeroNext" aria-label="Next Slide">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>

                <!-- Slider Dots -->
                <div class="aavni-slider-dots" id="aavniHeroDots">
                    <?php foreach ($active_slides as $idx => $slide) : ?>
                        <button class="aavni-slider-dot <?php echo $idx === 0 ? 'active' : ''; ?>" data-index="<?php echo esc_attr($idx); ?>" aria-label="Go to slide <?php echo esc_attr($idx + 1); ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php
    $cat_section = aavni_get_homepage_categories_section();
    $categories = aavni_get_homepage_categories();
    ?>
    <?php if (!empty($cat_section['enabled']) && !empty($categories)) : ?>
    <!-- Section B: Shop by Verified Categories -->
    <section class="aavni-container" style="margin-bottom: 72px;">
        <div class="aavni-section-header">
            <?php if (!empty($cat_section['subheading'])) : ?>
                <span class="aavni-subheading"><?php echo esc_html($cat_section['subheading']); ?></span>
            <?php endif; ?>
            <h2 class="aavni-section-title"><?php echo esc_html($cat_section['title']); ?></h2>
            <?php if (!empty($cat_section['desc'])) : ?>
                <p class="aavni-section-desc"><?php echo esc_html($cat_section['desc']); ?></p>
            <?php endif; ?>
        </div>

        <div class="aavni-category-grid">
            <?php foreach ($categories as $cat) : ?>
                <a href="<?php echo esc_url($cat['url']); ?>" class="aavni-cat-card">
                    <div class="aavni-cat-img-wrap">
                        <img src="<?php echo esc_url($cat['image_url']); ?>" alt="<?php echo esc_attr($cat['title']); ?>" class="aavni-cat-img" loading="lazy">
                    </div>
                    <div class="aavni-cat-info">
                        <div>
                            <h3 class="aavni-cat-title"><?php echo esc_html($cat['title']); ?></h3>
                            <?php if (!empty($cat['subtitle'])) : ?>
                                <span style="font-size: 12px; color: var(--aavni-text-subtle);"><?php echo esc_html($cat['subtitle']); ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="aavni-cat-action"><?php echo esc_html(!empty($cat['action_text']) ? $cat['action_text'] : 'Explore →'); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Section C: New Arrivals WooCommerce Grid -->
    <section class="aavni-container" style="margin-bottom: 72px;">
        <div class="aavni-section-header">
            <span class="aavni-subheading">Just Landed</span>
            <h2 class="aavni-section-title">New Arrivals</h2>
            <p class="aavni-section-desc">Carefully tailored with artisanal precision and lightweight, skin-friendly textiles.</p>
        </div>

        <div class="aavni-product-grid">
            <?php
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => 4,
                'orderby' => 'date',
                'order' => 'DESC',
            );
            $loop = new WP_Query($args);
            if ($loop->have_posts()) :
                while ($loop->have_posts()) : $loop->the_post();
                    global $product;
                    $image_url = get_the_post_thumbnail_url($product->get_id(), 'large');
                    if (!$image_url) {
                        $image_url = $long_kurti_img;
                    }
                    $cats = wc_get_product_category_list($product->get_id(), ', ');
            ?>
                    <div class="aavni-product-card">
                        <div class="aavni-pcard-media">
                            <?php if ($product->is_on_sale()) : 
                                $regular_price = (float) $product->get_regular_price();
                                $sale_price = (float) $product->get_sale_price();
                                $discount_pct = ($regular_price > 0 && $sale_price > 0) ? round((($regular_price - $sale_price) / $regular_price) * 100) : 0;
                            ?>
                                <span class="aavni-badge-sale" title="<?php echo esc_attr($discount_pct > 0 ? $discount_pct . '% Off' : 'On Sale'); ?>">
                                    <?php echo ($discount_pct > 0) ? esc_html($discount_pct . '% OFF') : 'SALE'; ?>
                                </span>
                            <?php endif; ?>
                            
                            <button type="button" class="aavni-wishlist-toggle" data-product-id="<?php echo esc_attr($product->get_id()); ?>" aria-label="Add to wishlist" title="Add to Wishlist">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2D2424" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </button>

                            <a href="<?php the_permalink(); ?>">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title_attribute(); ?>" class="aavni-pcard-img" loading="lazy">
                            </a>
                        </div>
                        <div class="aavni-pcard-content">
                            <span class="aavni-pcard-category"><?php echo wp_strip_all_tags($cats); ?></span>
                            <h3 class="aavni-pcard-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            <div class="aavni-pcard-price">
                                <?php echo $product->get_price_html(); ?>
                            </div>
                            
                            <div style="margin-top: 14px; display: flex; gap: 8px;">
                                <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" data-quantity="1" class="aavni-btn aavni-btn-primary aavni-pcard-btn button add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo esc_attr($product->get_id()); ?>" aria-label="<?php echo esc_attr($product->add_to_cart_description()); ?>" rel="nofollow">
                                    Add to Bag
                                </a>
                            </div>
                        </div>
                    </div>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
            ?>
                <p style="grid-column: 1 / -1; text-align: center; color: var(--aavni-text-muted);">No products found in New Arrivals yet.</p>
            <?php endif; ?>
        </div>

        <div style="text-align: center;">
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="aavni-btn aavni-btn-outline">View All In Shop</a>
        </div>
    </section>

    <?php
    $story = aavni_get_homepage_story();
    ?>
    <?php if (!empty($story['enabled'])) : ?>
    <!-- Section E: Brand Story Editorial Section -->
    <section class="aavni-container">
        <div class="aavni-brand-story">
            <div class="aavni-story-content">
                <?php if (!empty($story['subheading'])) : ?>
                    <span class="aavni-subheading"><?php echo esc_html($story['subheading']); ?></span>
                <?php endif; ?>
                <?php if (!empty($story['quote'])) : ?>
                    <blockquote class="aavni-story-quote">
                        "<?php echo esc_html($story['quote']); ?>"
                    </blockquote>
                <?php endif; ?>
                <?php if (!empty($story['text'])) : ?>
                    <p class="aavni-story-text">
                        <?php echo nl2br(esc_html($story['text'])); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($story['badges']) && is_array($story['badges'])) : ?>
                    <div class="aavni-story-badges">
                        <?php foreach ($story['badges'] as $badge_text) : ?>
                            <div class="aavni-story-badge-item">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                                <span><?php echo esc_html($badge_text); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="border-radius: 8px; overflow: hidden; background: #e8dcd5;">
                <img src="<?php echo esc_url($story['image_url']); ?>" alt="<?php echo esc_attr($story['subheading']); ?>" style="width: 100%; height: 100%; object-fit: cover; max-height: 480px;" loading="lazy">
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    $benefits = aavni_get_homepage_benefits();
    ?>
    <?php if (!empty($benefits['enabled']) && !empty($benefits['items'])) : ?>
    <!-- Section G: Brand Benefits / Trust Strip -->
    <section class="aavni-container">
        <div class="aavni-benefits-strip">
            <?php foreach ($benefits['items'] as $benefit) : ?>
                <div class="aavni-benefit-item">
                    <div class="aavni-benefit-icon">
                        <span style="font-size: 22px;"><?php echo esc_html($benefit['icon'] ?? '✨'); ?></span>
                    </div>
                    <div>
                        <h4 class="aavni-benefit-title"><?php echo esc_html($benefit['title']); ?></h4>
                        <p class="aavni-benefit-desc"><?php echo esc_html($benefit['desc']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php
    $newsletter = aavni_get_homepage_newsletter();
    ?>
    <?php if (!empty($newsletter['enabled'])) : ?>
    <!-- Section H: Newsletter Form -->
    <section class="aavni-container">
        <div class="aavni-newsletter-section">
            <span class="aavni-subheading" style="color: var(--aavni-gold);"><?php echo esc_html($newsletter['subheading']); ?></span>
            <h2 class="aavni-newsletter-title"><?php echo esc_html($newsletter['title']); ?></h2>
            <p class="aavni-newsletter-desc">
                <?php echo esc_html($newsletter['desc']); ?>
            </p>
            <form id="aavniNewsletterForm" class="aavni-newsletter-form">
                <input type="email" class="aavni-newsletter-input" placeholder="Your email address" required aria-label="Email address for newsletter">
                <button type="submit" class="aavni-btn aavni-btn-gold">Subscribe</button>
            </form>
            <div id="aavniNewsletterResponse" style="display: none; margin-top: 16px;"></div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php
get_footer();
