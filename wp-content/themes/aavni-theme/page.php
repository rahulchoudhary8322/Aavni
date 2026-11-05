<?php
/**
 * Standard Page Template for AAVNI
 *
 * @package AAVNI
 */

get_header();
?>

<main id="primary" class="site-main" style="<?php echo is_account_page() ? 'padding: 20px 0 60px 0;' : 'padding: 40px 0 80px 0;'; ?>">
    <div class="aavni-container">

        <?php
        while (have_posts()) : the_post();
        ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php if (!is_front_page() && !is_account_page()) : ?>
                    <header class="entry-header" style="margin-bottom: 32px; text-align: center;">
                        <h1 class="entry-title aavni-heading" style="font-size: 34px;"><?php the_title(); ?></h1>
                    </header>
                <?php endif; ?>

                <div class="entry-content<?php echo is_account_page() ? ' aavni-account-entry-content' : ''; ?>" <?php if (!is_account_page()) : ?>style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: var(--radius-sm); padding: 36px;"<?php else : ?>style="padding:0; background:transparent; border:none;"<?php endif; ?>>
                    <?php
                    the_content();

                    wp_link_pages(array(
                        'before' => '<div class="page-links">' . esc_html__('Pages:', 'aavni'),
                        'after'  => '</div>',
                    ));
                    ?>
                </div>
            </article>
        <?php
        endwhile;
        ?>

    </div>
</main>

<style>
/* Cart & Checkout Page Enhancements */
.woocommerce-cart table.cart,
.woocommerce-checkout #order_review {
    background: #FFFFFF;
    border-radius: var(--radius-sm);
}
.woocommerce-checkout .col2-set .col-1,
.woocommerce-checkout .col2-set .col-2 {
    background: #FFFFFF;
    padding: 24px;
    border: 1px solid var(--aavni-border-light);
    border-radius: var(--radius-sm);
    box-sizing: border-box;
    margin-bottom: 24px;
}
.woocommerce-checkout label {
    font-size: 13.5px;
    font-weight: 600;
    color: var(--aavni-text);
}
.woocommerce-checkout input.input-text,
.woocommerce-checkout select {
    padding: 12px;
    border: 1px solid var(--aavni-border);
    border-radius: var(--radius-sm);
    font-family: inherit;
    font-size: 14px;
}
.woocommerce-checkout input.input-text:focus,
.woocommerce-checkout select:focus {
    outline: none;
    border-color: var(--aavni-gold);
}
#payment {
    background: var(--aavni-bg) !important;
    border-radius: var(--radius-sm);
    padding: 20px;
}
#payment ul.payment_methods {
    border-bottom: 1px solid var(--aavni-border);
}
</style>

<?php
get_footer();
