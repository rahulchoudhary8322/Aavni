<?php
/**
 * AAVNI Luxury Lost Password Form Template
 *
 * @package AAVNI
 * @version 9.2.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_lost_password_form');
?>

<div class="aavni-lost-password-wrapper" style="max-width:520px; margin:50px auto 80px; padding:0 20px;">
    <div style="background:#FFFFFF; border:1px solid #EAE5DE; border-radius:12px; padding:36px 32px; box-shadow:0 4px 20px rgba(0,0,0,0.03);">
        <div style="margin-bottom:20px; text-align:left;">
            <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" style="font-size:12.5px; font-weight:600; color:#A85A48; text-decoration:none;">
                &larr; <?php esc_html_e('Back to Login', 'aavni'); ?>
            </a>
            <h2 style="font-family:'Playfair Display', Georgia, serif; font-size:24px; color:#2C1810; margin:10px 0 6px; font-weight:600;">
                <?php esc_html_e('Reset Your Password', 'aavni'); ?>
            </h2>
            <p style="font-size:13.5px; color:#6B7280; line-height:1.5; margin:0;">
                <?php echo apply_filters('woocommerce_lost_password_message', esc_html__('Please enter your account username or email address below. You will receive a secure link to create a new password.', 'woocommerce')); ?>
            </p>
        </div>

        <form method="post" class="woocommerce-ResetPassword lost_reset_password" style="margin:0;">
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" style="margin-bottom:20px;">
                <label for="user_login" style="display:block; font-size:13px; font-weight:600; color:#2C1810; margin-bottom:6px;">
                    <?php esc_html_e('Username or Email', 'woocommerce'); ?>&nbsp;<span class="required" style="color:#DC2626;">*</span>
                </label>
                <input class="woocommerce-Input woocommerce-Input--text input-text" type="text" name="user_login" id="user_login" autocomplete="username" required aria-required="true" style="width:100%; padding:11px 14px; border:1px solid #D1C7BD; border-radius:6px; font-size:14px; background:#FAF8F5;" />
            </p>

            <div class="clear"></div>

            <?php do_action('woocommerce_lostpassword_form'); ?>

            <p class="woocommerce-form-row form-row" style="margin:0;">
                <input type="hidden" name="wc_reset_password" value="true" />
                <?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>
                <button type="submit" class="woocommerce-Button button" value="<?php esc_attr_e('Reset password', 'woocommerce'); ?>" style="width:100%; padding:13px; background:#2C1810; color:#FFFFFF; border:none; border-radius:6px; font-size:14px; font-weight:600; cursor:pointer;">
                    <?php esc_html_e('Send Password Reset Link &rarr;', 'aavni'); ?>
                </button>
            </p>
        </form>
    </div>
</div>

<?php
do_action('woocommerce_after_lost_password_form');

