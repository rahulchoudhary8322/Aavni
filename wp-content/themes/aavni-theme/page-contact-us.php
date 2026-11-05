<?php
/**
 * Template Name: Contact AAVNI
 *
 * @package AAVNI
 */

get_header();

$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aavni_contact_submit'])) {
    if (!isset($_POST['aavni_nonce']) || !wp_verify_nonce($_POST['aavni_nonce'], 'aavni_contact_action')) {
        $error_message = 'Security check failed. Please refresh and try again.';
    } else {
        $name = sanitize_text_field($_POST['aavni_name']);
        $email = sanitize_email($_POST['aavni_email']);
        $phone = sanitize_text_field($_POST['aavni_phone']);
        $subject = sanitize_text_field($_POST['aavni_subject']);
        $msg = sanitize_textarea_field($_POST['aavni_message']);

        if (empty($name) || empty($email) || empty($msg)) {
            $error_message = 'Please fill out all required fields.';
        } else {
            // Success state
            $message_sent = true;
        }
    }
}
?>

<main id="primary" class="site-main" style="padding: 48px 0 80px 0;">
    <div class="aavni-container">

        <nav style="font-size: 13px; color: var(--aavni-text-subtle); margin-bottom: 32px;" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> &nbsp;/&nbsp; <span>Contact Us</span>
        </nav>

        <div class="aavni-section-header" style="max-width: 680px; margin: 0 auto 56px auto;">
            <span class="aavni-subheading">Get In Touch</span>
            <h1 class="aavni-section-title">We'd Love To Hear From You</h1>
            <p class="aavni-section-desc">
                Have questions regarding your order, sizing, exchange, or custom requests? Our customer care team is here to assist you.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1.4fr; gap: 48px;">
            
            <!-- Contact Details -->
            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 40px;">
                <h3 class="aavni-heading" style="font-size: 22px; margin-bottom: 24px;">Support Channels</h3>
                
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--aavni-gold-hover); font-weight: 600; margin-bottom: 4px;">Email Support</div>
                    <div style="font-size: 16px; font-weight: 500; color: var(--aavni-text);"><?php echo esc_html(aavni_get_support_email()); ?></div>
                    <div style="font-size: 13px; color: var(--aavni-text-muted); margin-top: 2px;">Typical response time: within 24 hours</div>
                </div>

                <div style="margin-bottom: 24px;">
                    <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--aavni-gold-hover); font-weight: 600; margin-bottom: 4px;">Operating Hours</div>
                    <div style="font-size: 15px; color: var(--aavni-text);"><?php echo esc_html(aavni_get_operating_hours()); ?></div>
                </div>

                <div style="margin-bottom: 24px;">
                    <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--aavni-gold-hover); font-weight: 600; margin-bottom: 4px;">Dispatch Center</div>
                    <div style="font-size: 14.5px; color: var(--aavni-text); line-height: 1.5;">
                        AAVNI Apparel Logistics<br>
                        India
                    </div>
                </div>

                <div style="background: var(--aavni-bg); padding: 16px; border-radius: 4px; font-size: 13px; color: var(--aavni-text-muted);">
                    <strong>Track Order:</strong> You will receive real-time SMS and email tracking links the moment your order is dispatched.
                </div>
            </div>

            <!-- Contact Form -->
            <div style="background: #FFFFFF; border: 1px solid var(--aavni-border-light); border-radius: 8px; padding: 40px;">
                <h3 class="aavni-heading" style="font-size: 22px; margin-bottom: 20px;">Send Us A Message</h3>
                
                <?php if ($message_sent) : ?>
                    <div style="background: #e8f5e9; border: 1px solid #c8e6c9; color: #2e7d32; padding: 18px; border-radius: 4px; margin-bottom: 20px;">
                        <strong>Thank you!</strong> Your message has been received. Our team will review your inquiry and respond shortly.
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)) : ?>
                    <div style="background: #ffebee; border: 1px solid #ffcdd2; color: #c62828; padding: 18px; border-radius: 4px; margin-bottom: 20px;">
                        <?php echo esc_html($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php wp_nonce_field('aavni_contact_action', 'aavni_nonce'); ?>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Your Name *</label>
                            <input type="text" name="aavni_name" required style="width: 100%; padding: 12px; border: 1px solid var(--aavni-border); border-radius: 4px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Email Address *</label>
                            <input type="email" name="aavni_email" required style="width: 100%; padding: 12px; border: 1px solid var(--aavni-border); border-radius: 4px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Phone Number (Optional)</label>
                            <input type="text" name="aavni_phone" placeholder="+91" style="width: 100%; padding: 12px; border: 1px solid var(--aavni-border); border-radius: 4px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Subject *</label>
                            <input type="text" name="aavni_subject" placeholder="e.g. Order Status, Sizing Advice" required style="width: 100%; padding: 12px; border: 1px solid var(--aavni-border); border-radius: 4px; font-family: inherit; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Your Message *</label>
                        <textarea name="aavni_message" rows="5" required style="width: 100%; padding: 12px; border: 1px solid var(--aavni-border); border-radius: 4px; font-family: inherit; font-size: 14px; box-sizing: border-box;"></textarea>
                    </div>

                    <button type="submit" name="aavni_contact_submit" class="aavni-btn aavni-btn-primary" style="width: 100%;">Send Message</button>
                </form>
            </div>

        </div>

    </div>
</main>

<?php
get_footer();
