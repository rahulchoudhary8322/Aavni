<?php
/**
 * AAVNI 100% Secure Google-Only Customer Sign-In
 *
 * @package AAVNI
 * @version 10.0.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_customer_login_form');
?>

<div class="aavni-auth-page-container" style="max-width:520px; margin:50px auto 90px; padding:0 20px;">

    <div class="aavni-auth-card aavni-login-card" style="background:#FFFFFF; border:1px solid #EAE5DE; border-radius:16px; padding:48px 36px; box-shadow:0 10px 30px rgba(44, 24, 16, 0.05); text-align:center;">
        
        <!-- Luxury Brand Badge & Header -->
        <div style="margin-bottom:30px;">
            <span style="font-size:12px; font-weight:700; color:#A85A48; text-transform:uppercase; letter-spacing:1.5px; display:inline-block; margin-bottom:8px; padding:4px 14px; background:rgba(168, 90, 72, 0.08); border-radius:20px;">
                ✦ AAVNI PRIVILEGE ACCESS
            </span>
            <h2 style="font-family:'Playfair Display', Georgia, serif; font-size:30px; color:#2C1810; margin:8px 0 10px; font-weight:600;">
                Customer Sign In
            </h2>
            <p style="font-size:14px; color:#6B7280; margin:0 auto; line-height:1.6; max-width:380px;">
                Sign in securely with your verified Google account to view orders, track delivery &amp; manage saved addresses.
            </p>
        </div>

        <?php
        // Show Google Auth Error if any
        if (!empty($_GET['google_auth_error'])) {
            $auth_error = sanitize_text_field(urldecode($_GET['google_auth_error']));
            echo '<div style="margin-bottom:18px; padding:12px 16px; background:#FEF2F2; border:1px solid #FCA5A5; border-radius:8px; color:#991B1B; font-size:13.5px; text-align:left;">&#9888;&#65039; ' . esc_html($auth_error) . '</div>';
        }
        ?>

        <!-- 1-Click Real Google Sign-In Primary Action -->
        <div class="aavni-google-auth-box" style="margin:24px 0 10px; display:flex; justify-content:center;">
            <?php
            $google_oauth_url = function_exists('aavni_get_google_oauth_url') ? aavni_get_google_oauth_url() : '';

            if (!empty($google_oauth_url)) {
                // Use our own redirect-based OAuth flow
                ?>
                <?php $oauth_url_escaped = esc_url($google_oauth_url); ?>
                <a href="<?php echo $oauth_url_escaped; ?>" 
                   class="aavni-google-oauth-btn" 
                   id="aavniOAuthLink"
                   style="width:100%; display:flex; align-items:center; justify-content:center; gap:14px; padding:15px 20px; background:#FFFFFF; border:1.5px solid #4285F4; border-radius:10px; color:#1F2937; font-size:15px; font-weight:600; text-decoration:none; box-shadow:0 2px 8px rgba(66, 133, 244, 0.15); transition:all 0.2s ease; cursor:pointer;">
                    <svg viewBox="0 0 24 24" width="22" height="22" style="flex-shrink:0;">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span style="letter-spacing:0.2px;">Continue with Google</span>
                </a>
                <?php
            } else {
                // Not configured — show admin setup notice or customer message
                if (current_user_can('manage_options')) {
                    echo '<div style="width:100%; padding:16px; background:#FFF7ED; border:1px solid #FED7AA; border-radius:8px; font-size:13px; color:#92400E; text-align:left;">
                        <strong>&#9881;&#65039; Admin Notice:</strong> Google OAuth is not configured.<br>
                        Go to <a href="' . esc_url(admin_url('options-general.php')) . '#aavni_google_auth_section" style="color:#A85A48; font-weight:600;">Settings &rarr; General &rarr; AAVNI Google Sign-In</a> and enter your <strong>Client ID</strong> and <strong>Client Secret</strong>.
                    </div>';
                } else {
                    echo '<div style="width:100%; padding:16px; background:#FFF7ED; border:1px solid #FED7AA; border-radius:8px; font-size:13px; color:#92400E;">
                        Google Sign-In is temporarily unavailable. Please use the staff login below.
                    </div>';
                }
            }
            ?>
        </div>

        <!-- Security Trust Note -->
        <div style="margin-top:28px; padding-top:22px; border-top:1px solid #F0ECE6; display:flex; align-items:center; justify-content:center; gap:8px; font-size:12.5px; color:#8C827A;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#047857" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span>Verified Google OAuth 2.0 Protection &bull; Zero Spam</span>
        </div>

        <!-- Admin Access Toggle -->
        <div style="margin-top:18px;">
            <a href="javascript:void(0);" id="aavniToggleAdminPass" style="font-size:12px; color:#A85A48; text-decoration:none;" onclick="var f=document.getElementById('aavniAdminPassForm'); f.style.display=(f.style.display==='none'?'block':'none');">
                Staff &amp; Admin Sign In &darr;
            </a>
            
            <div id="aavniAdminPassForm" style="display:none; margin-top:16px; padding:18px; background:#FAF8F5; border-radius:8px; text-align:left;">
                <form class="woocommerce-form woocommerce-form-login login" method="post">
                    <p class="form-row form-row-wide" style="margin-bottom:12px;">
                        <label for="username" style="font-size:12px; font-weight:600; color:#2C1810;"><?php esc_html_e('Username or Email', 'aavni'); ?></label>
                        <input type="text" class="input-text" name="username" id="username" autocomplete="username" style="width:100%; padding:9px 12px; font-size:13px; border:1px solid #D1C7BD; border-radius:5px;" required />
                    </p>
                    <p class="form-row form-row-wide" style="margin-bottom:12px;">
                        <label for="password" style="font-size:12px; font-weight:600; color:#2C1810;"><?php esc_html_e('Password', 'aavni'); ?></label>
                        <input type="password" class="input-text" name="password" id="password" autocomplete="current-password" style="width:100%; padding:9px 12px; font-size:13px; border:1px solid #D1C7BD; border-radius:5px;" required />
                    </p>
                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                    <button type="submit" class="button" name="login" value="<?php esc_attr_e('Log in', 'aavni'); ?>" style="width:100%; padding:10px; background:#2C1810; color:#fff; border:none; border-radius:5px; font-size:13px; cursor:pointer;">
                        Sign In as Staff
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>

<?php do_action('woocommerce_after_customer_login_form'); ?>
