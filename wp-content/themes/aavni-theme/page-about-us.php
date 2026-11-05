<?php
/**
 * Template Name: About AAVNI
 *
 * @package AAVNI
 */

get_header();

$upload_dir = wp_upload_dir();
$banner_img = $upload_dir['baseurl'] . '/aavni/aavni-hero-banner.png';
$story_img = $upload_dir['baseurl'] . '/aavni/long-kurti-1.jpg';
?>

<main id="primary" class="site-main" style="padding: 48px 0 80px 0;">
    <div class="aavni-container">
        
        <!-- Breadcrumbs -->
        <nav style="font-size: 13px; color: var(--aavni-text-subtle); margin-bottom: 32px;" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; <span>Our Story</span>
        </nav>

        <div class="aavni-section-header" style="max-width: 780px; margin: 0 auto 56px auto;">
            <span class="aavni-subheading">About Us</span>
            <h1 class="aavni-section-title" style="font-size: 42px;">Dress To Express. Live To Impress.</h1>
            <p class="aavni-section-desc" style="font-size: 17px; line-height: 1.7;">
                AAVNI is an Indian contemporary clothing label committed to honoring everyday comfort without ever compromising on refined sartorial elegance.
            </p>
        </div>

        <div style="border-radius: 8px; overflow: hidden; margin-bottom: 64px; box-shadow: var(--shadow-sm);">
            <img src="<?php echo esc_url($banner_img); ?>" alt="AAVNI Brand Vision" style="width: 100%; height: auto;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; margin-bottom: 80px;">
            <div>
                <span class="aavni-subheading">Our Heritage</span>
                <h2 class="aavni-heading" style="font-size: 32px; margin-bottom: 20px;">Textiles Born From Passion &amp; Purity</h2>
                <p style="color: var(--aavni-text-muted); font-size: 16px; line-height: 1.8; margin-bottom: 18px;">
                    We believe that modern ethnic wear should move with you. Our kurtis, tops, and loungewear are made from pure, hand-selected cotton that breathes naturally in the Indian climate.
                </p>
                <p style="color: var(--aavni-text-muted); font-size: 16px; line-height: 1.8;">
                    Every stitch is checked for durability, seams are carefully finished, and silhouettes are engineered to drape gracefully on diverse Indian body types.
                </p>
            </div>
            <div style="border-radius: 8px; overflow: hidden;">
                <img src="<?php echo esc_url($story_img); ?>" alt="AAVNI Kurti Craft" style="width: 100%; height: auto;">
            </div>
        </div>

        <!-- Brand Values -->
        <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 48px; margin-bottom: 64px;">
            <h3 class="aavni-heading" style="font-size: 26px; text-align: center; margin-bottom: 36px;">Our Core Promises</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; text-align: center;">
                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px;">Genuine Cotton</h4>
                    <p style="font-size: 14px; color: var(--aavni-text-muted); line-height: 1.6;">Breathable, soft, and skin-friendly natural fibers that stand up to daily washes.</p>
                </div>
                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px;">Honest Pricing</h4>
                    <p style="font-size: 14px; color: var(--aavni-text-muted); line-height: 1.6;">Direct-to-consumer value without middleman retail inflation or synthetic shortcuts.</p>
                </div>
                <div>
                    <h4 style="font-size: 18px; margin-bottom: 10px;">Inclusive Sizing</h4>
                    <p style="font-size: 14px; color: var(--aavni-text-muted); line-height: 1.6;">Thoughtfully graded sizing from XS to XXL with verified ease around the chest and hips.</p>
                </div>
            </div>
        </div>

    </div>
</main>

<?php
get_footer();
