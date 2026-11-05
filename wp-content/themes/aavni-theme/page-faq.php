<?php
/**
 * Template Name: FAQ
 *
 * @package AAVNI
 */

get_header();
?>

<main id="primary" class="site-main" style="padding: 48px 0 80px 0;">
    <div class="aavni-container">

        <nav style="font-size: 13px; color: var(--aavni-text-subtle); margin-bottom: 32px;" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; <span>Frequently Asked Questions</span>
        </nav>

        <div class="aavni-section-header" style="max-width: 680px; margin: 0 auto 56px auto;">
            <span class="aavni-subheading">Help Center</span>
            <h1 class="aavni-section-title">Frequently Asked Questions</h1>
            <p class="aavni-section-desc">Everything you need to know about shopping with AAVNI.</p>
        </div>

        <div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 20px;">
            
            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 24px;">
                <h3 style="font-size: 17px; margin-bottom: 10px; color: var(--aavni-text);">What fabrics are used in AAVNI clothing?</h3>
                <p style="font-size: 14.5px; color: var(--aavni-text-muted); line-height: 1.6; margin: 0;">We exclusively use 100% premium breathable cotton, cambric, and artisanal cotton blends chosen for soft skin-feel, all-day comfort, and longevity.</p>
            </div>

            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 24px;">
                <h3 style="font-size: 17px; margin-bottom: 10px; color: var(--aavni-text);">Do you provide Cash on Delivery (COD)?</h3>
                <p style="font-size: 14.5px; color: var(--aavni-text-muted); line-height: 1.6; margin: 0;">Yes! Cash on Delivery is available across serviceable PIN codes throughout India. You can select Cash on Delivery during the checkout step.</p>
            </div>

            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 24px;">
                <h3 style="font-size: 17px; margin-bottom: 10px; color: var(--aavni-text);">How long does shipping take?</h3>
                <p style="font-size: 14.5px; color: var(--aavni-text-muted); line-height: 1.6; margin: 0;">Orders are typically dispatched within 24-48 business hours. Delivery usually takes 3 to 5 business days depending on your delivery location.</p>
            </div>

            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 24px;">
                <h3 style="font-size: 17px; margin-bottom: 10px; color: var(--aavni-text);">What is your exchange and return policy?</h3>
                <p style="font-size: 14.5px; color: var(--aavni-text-muted); line-height: 1.6; margin: 0;">We offer a hassle-free 7-day exchange policy for size mismatches or defects. Simply email care@aavniclothing.com with your Order ID to initiate an exchange.</p>
            </div>

            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 24px;">
                <h3 style="font-size: 17px; margin-bottom: 10px; color: var(--aavni-text);">How do I care for my cotton kurtis?</h3>
                <p style="font-size: 14.5px; color: var(--aavni-text-muted); line-height: 1.6; margin: 0;">We recommend gentle machine wash or hand wash in cold water with mild detergent. Wash dark colors separately and dry inside out in the shade to preserve vibrant hues.</p>
            </div>

        </div>

    </div>
</main>

<?php
get_footer();
