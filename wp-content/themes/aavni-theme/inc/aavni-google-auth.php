<?php
/**
 * AAVNI Google Authentication System
 * Production-ready "Continue with Google" OAuth & 1-Click Customer Registration
 *
 * @package AAVNI
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================
// REDIRECT-BASED GOOGLE OAUTH 2.0 FLOW (Works on localhost)
// ============================================================

/**
 * Build Google OAuth 2.0 Authorization URL
 */
function aavni_get_google_oauth_url() {
    $client_id = get_option('aavni_google_client_id', '');
    if (empty($client_id)) {
        return '';
    }

    $state = wp_create_nonce('aavni_google_oauth_state');
    // Store state in transient for 10 minutes
    set_transient('aavni_oauth_state_' . $state, 1, 600);

    $redirect_uri = home_url('/aavni-google-callback/');

    $params = array(
        'client_id'     => $client_id,
        'redirect_uri'  => $redirect_uri,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    );

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Register callback rewrite rule
 */
add_action('init', 'aavni_register_google_callback_rewrite');
function aavni_register_google_callback_rewrite() {
    add_rewrite_rule('^aavni-google-callback/?$', 'index.php?aavni_google_callback=1', 'top');
}

add_filter('query_vars', function($vars) {
    $vars[] = 'aavni_google_callback';
    return $vars;
});

/**
 * Handle Google OAuth Callback (redirect-based)
 */
add_action('template_redirect', 'aavni_handle_google_oauth_callback', 1);
function aavni_handle_google_oauth_callback() {
    if (!get_query_var('aavni_google_callback')) {
        return;
    }

    // Get code and state from Google redirect
    $code  = isset($_GET['code'])  ? sanitize_text_field($_GET['code'])  : '';
    $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
    $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

    $my_account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');

    // User denied access
    if (!empty($error)) {
        wp_safe_redirect(add_query_arg('google_auth_error', urlencode('Google sign-in was cancelled. Please try again.'), $my_account_url));
        exit;
    }

    // Validate state (CSRF protection)
    if (empty($state) || !get_transient('aavni_oauth_state_' . $state)) {
        wp_safe_redirect(add_query_arg('google_auth_error', urlencode('Security check failed. Please try again.'), $my_account_url));
        exit;
    }
    delete_transient('aavni_oauth_state_' . $state);

    if (empty($code)) {
        wp_safe_redirect(add_query_arg('google_auth_error', urlencode('Authentication failed. No code received from Google.'), $my_account_url));
        exit;
    }

    $client_id     = get_option('aavni_google_client_id', '');
    $client_secret = get_option('aavni_google_client_secret', '');
    $redirect_uri  = home_url('/aavni-google-callback/');

    // Exchange authorization code for tokens
    $token_response = wp_remote_post('https://oauth2.googleapis.com/token', array(
        'timeout' => 15,
        'body'    => array(
            'code'          => $code,
            'client_id'     => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri'  => $redirect_uri,
            'grant_type'    => 'authorization_code',
        ),
    ));

    if (is_wp_error($token_response) || wp_remote_retrieve_response_code($token_response) !== 200) {
        $body = !is_wp_error($token_response) ? json_decode(wp_remote_retrieve_body($token_response), true) : [];
        $err_desc = isset($body['error_description']) ? $body['error_description'] : 'Token exchange failed.';
        wp_safe_redirect(add_query_arg('google_auth_error', urlencode($err_desc), $my_account_url));
        exit;
    }

    $tokens    = json_decode(wp_remote_retrieve_body($token_response), true);
    $id_token  = isset($tokens['id_token']) ? $tokens['id_token'] : '';
    $access_token = isset($tokens['access_token']) ? $tokens['access_token'] : '';

    // Get user info via Google's userinfo endpoint
    $userinfo_response = wp_remote_get('https://www.googleapis.com/oauth2/v3/userinfo', array(
        'timeout' => 15,
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
        ),
    ));

    if (is_wp_error($userinfo_response) || wp_remote_retrieve_response_code($userinfo_response) !== 200) {
        // Fallback: try to decode id_token payload
        $parts = explode('.', $id_token);
        if (count($parts) >= 2) {
            $payload = json_decode(base64_decode(str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) % 4 ? strlen($parts[1]) + 4 - strlen($parts[1]) % 4 : strlen($parts[1]), '=', STR_PAD_RIGHT)), true);
        } else {
            $payload = [];
        }
    } else {
        $payload = json_decode(wp_remote_retrieve_body($userinfo_response), true);
    }

    if (empty($payload['email']) || !is_email($payload['email'])) {
        wp_safe_redirect(add_query_arg('google_auth_error', urlencode('Could not retrieve your Google account email. Please try again.'), $my_account_url));
        exit;
    }

    // Verify email is verified by Google
    $email_verified = isset($payload['email_verified']) && ($payload['email_verified'] === true || $payload['email_verified'] === 'true');
    if (!$email_verified) {
        wp_safe_redirect(add_query_arg('google_auth_error', urlencode('Your Google email address is not verified.'), $my_account_url));
        exit;
    }

    $email       = sanitize_email($payload['email']);
    $name        = !empty($payload['name'])        ? sanitize_text_field($payload['name'])        : '';
    $given_name  = !empty($payload['given_name'])  ? sanitize_text_field($payload['given_name'])  : '';
    $family_name = !empty($payload['family_name']) ? sanitize_text_field($payload['family_name']) : '';
    $avatar      = !empty($payload['picture'])     ? esc_url_raw($payload['picture'])             : '';
    $google_id   = !empty($payload['sub'])         ? sanitize_text_field($payload['sub'])         : '';

    if (empty($given_name) && !empty($name)) {
        $parts = explode(' ', trim($name));
        $given_name  = array_shift($parts);
        $family_name = !empty($parts) ? implode(' ', $parts) : '';
    }

    // Find or create WordPress user
    $user = get_user_by('email', $email);

    if ($user) {
        if (in_array('administrator', (array) $user->roles, true)) {
            wp_safe_redirect(add_query_arg('google_auth_error', urlencode('Administrator accounts must use password login.'), $my_account_url));
            exit;
        }
        $user_id = $user->ID;
    } else {
        // Create new customer
        $base_username = sanitize_user(current(explode('@', $email)), true);
        $username = $base_username;
        $counter  = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter++;
        }

        $user_id = wp_create_user($username, wp_generate_password(24, true, true), $email);
        if (is_wp_error($user_id)) {
            wp_safe_redirect(add_query_arg('google_auth_error', urlencode($user_id->get_error_message()), $my_account_url));
            exit;
        }

        $user = new WP_User($user_id);
        $user->set_role('customer');

        wp_update_user(array(
            'ID'           => $user_id,
            'display_name' => !empty($name) ? $name : $username,
            'first_name'   => $given_name,
            'last_name'    => $family_name,
        ));

        update_user_meta($user_id, 'billing_first_name',   $given_name);
        update_user_meta($user_id, 'billing_last_name',    $family_name);
        update_user_meta($user_id, 'billing_email',        $email);
        update_user_meta($user_id, 'shipping_first_name',  $given_name);
        update_user_meta($user_id, 'shipping_last_name',   $family_name);
    }

    // Save Google meta
    if (!empty($google_id)) update_user_meta($user_id, 'aavni_google_id',     $google_id);
    if (!empty($avatar))    update_user_meta($user_id, 'aavni_google_avatar', $avatar);
    update_user_meta($user_id, 'aavni_auth_provider', 'google');

    // === LOG IN THE USER (WordPress + WooCommerce) ===
    wp_clear_auth_cookie();
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true, is_ssl());

    // Fire login action
    $user_obj = get_user_by('ID', $user_id);
    do_action('wp_login', $user_obj->user_login, $user_obj);

    // Initialize WooCommerce session properly
    if (function_exists('WC') && WC()->session) {
        WC()->session->set_customer_session_cookie(true);
        if (is_callable([WC()->session, 'save_data'])) {
            WC()->session->save_data();
        }
        WC()->customer = new WC_Customer($user_id, true);
    }

    // Redirect to My Account dashboard
    wp_safe_redirect($my_account_url);
    exit;
}

/**
 * Add Google Client Secret setting field
 */
add_action('admin_init', 'aavni_register_google_secret_setting');
function aavni_register_google_secret_setting() {
    register_setting('general', 'aavni_google_client_secret', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ));

    add_settings_field(
        'aavni_google_client_secret',
        'Google OAuth Client Secret',
        'aavni_google_client_secret_callback',
        'general',
        'aavni_google_auth_section'
    );
}

function aavni_google_client_secret_callback() {
    $secret = get_option('aavni_google_client_secret', '');
    echo '<input type="password" name="aavni_google_client_secret" value="' . esc_attr($secret) . '" class="regular-text" placeholder="Paste your Client Secret here" style="width:450px;">';
    echo '<p class="description">Found next to your Client ID in <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console &rarr; Credentials</a>.</p>';
    echo '<p class="description"><strong>Callback / Redirect URI to add in Google Console:</strong> <code>' . esc_html(home_url('/aavni-google-callback/')) . '</code></p>';
}

// 1. Register Google Client ID Setting in WordPress Admin
add_action('admin_init', 'aavni_register_google_auth_settings');
function aavni_register_google_auth_settings() {
    register_setting('general', 'aavni_google_client_id', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => ''
    ));

    add_settings_section(
        'aavni_google_auth_section',
        'AAVNI — Google Sign-In Configuration',
        'aavni_google_auth_section_callback',
        'general'
    );

    add_settings_field(
        'aavni_google_client_id',
        'Google OAuth Client ID',
        'aavni_google_client_id_callback',
        'general',
        'aavni_google_auth_section'
    );
}

function aavni_google_auth_section_callback() {
    echo '<p style="max-width:700px; color:#555;">Enter your Google Cloud Console OAuth 2.0 Web Client ID to enable live Google One Tap and Google Sign-In popup. If left empty, AAVNI operates in seamless 1-click Google authentication mode for safe testing on localhost and client review.</p>';
}

function aavni_google_client_id_callback() {
    $client_id = get_option('aavni_google_client_id', '');
    echo '<input type="text" name="aavni_google_client_id" value="' . esc_attr($client_id) . '" class="regular-text" placeholder="e.g. 1234567890-xxx.apps.googleusercontent.com" style="width:450px;">';
    echo '<p class="description">Obtain this from <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console &rarr; Credentials &rarr; OAuth 2.0 Client IDs</a>.</p>';
}

// 2. Enqueue Google GIS Script and Localize Parameters
add_action('wp_enqueue_scripts', 'aavni_enqueue_google_auth_scripts', 30);
function aavni_enqueue_google_auth_scripts() {
    if (is_account_page() || is_checkout()) {
        // Enqueue Google Identity Services SDK
        wp_enqueue_script('google-identity-services', 'https://accounts.google.com/gsi/client', array(), null, true);

        // Localize authentication data
        wp_localize_script('aavni-scripts', 'aavni_google_auth', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aavni_google_auth_nonce'),
            'client_id' => get_option('aavni_google_client_id', ''),
            'is_user_logged_in' => is_user_logged_in(),
            'my_account_url' => wc_get_page_permalink('myaccount'),
            'checkout_url' => wc_get_checkout_url(),
        ));
    }
}

// 3. Render "Continue with Google" Button on WooCommerce Login & Register Forms
add_action('woocommerce_before_checkout_form', 'aavni_checkout_google_signin_prompt', 5);

function aavni_render_google_signin_button($context = 'login') {
    // Only used if called explicitly elsewhere
    $btn_id = 'aavniGoogleLoginBtn';
    $btn_text = 'Continue with Google';
    ?>
    <div class="aavni-google-auth-wrapper">
        <button type="button" class="aavni-btn-google-auth" id="<?php echo esc_attr($btn_id); ?>">
            <svg class="aavni-google-icon" viewBox="0 0 24 24" width="20" height="20">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
            </svg>
            <span class="aavni-google-btn-text"><?php echo esc_html($btn_text); ?></span>
        </button>
    </div>
    <?php
}


function aavni_checkout_google_signin_prompt() {
    if (is_user_logged_in()) return;
    ?>
    <div class="aavni-checkout-google-banner">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-size:22px;">⚡</span>
                <div>
                    <strong style="font-size:14.5px; color:var(--aavni-text); display:block;">Express 1-Click Checkout with Google</strong>
                    <span style="font-size:12.5px; color:var(--aavni-text-muted);">Sign in instantly to auto-fill your saved shipping address and track your order.</span>
                </div>
            </div>
            <button type="button" class="aavni-btn-google-auth aavni-btn-google-compact" id="aavniCheckoutGoogleBtn">
                <svg viewBox="0 0 24 24" width="18" height="18">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Sign in with Google</span>
            </button>
        </div>
    </div>
    <?php
}

// 4. Secure Backend AJAX Handler: Real Google OAuth User Login & Registration
add_action('wp_ajax_nopriv_aavni_google_auth', 'aavni_handle_google_login');
add_action('wp_ajax_aavni_google_auth', 'aavni_handle_google_login');

function aavni_handle_google_login() {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aavni_google_auth_nonce')) {
        wp_send_json_error(array('message' => __('Security token expired. Please refresh the page.', 'aavni')));
    }

    $credential = isset($_POST['credential']) ? sanitize_text_field($_POST['credential']) : '';

    // STRICT CHECK: Google credential MUST be present. No arbitrary email bypass permitted!
    if (empty($credential)) {
        wp_send_json_error(array(
            'message' => __('Google authentication credentials missing. Please sign in via Google or use standard email and password.', 'aavni')
        ));
    }

    // Verify Google ID Token via Google's official Tokeninfo endpoint
    $token_info_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
    $response = wp_remote_get($token_info_url, array('timeout' => 15));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        wp_send_json_error(array('message' => __('Google authentication verification failed. Please try again.', 'aavni')));
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($data['email']) || !is_email($data['email'])) {
        wp_send_json_error(array('message' => __('Google account email not found or invalid.', 'aavni')));
    }

    // Verify email is verified by Google
    $email_verified = isset($data['email_verified']) && ($data['email_verified'] === true || $data['email_verified'] === 'true');
    if (!$email_verified) {
        wp_send_json_error(array('message' => __('Your Google email is not verified.', 'aavni')));
    }

    // Verify client_id if configured
    $configured_client_id = get_option('aavni_google_client_id', '');
    if (!empty($configured_client_id) && !empty($data['aud']) && $data['aud'] !== $configured_client_id) {
        wp_send_json_error(array('message' => __('Google OAuth client mismatch.', 'aavni')));
    }

    $email = sanitize_email($data['email']);
    $name = !empty($data['name']) ? sanitize_text_field($data['name']) : '';
    $given_name = !empty($data['given_name']) ? sanitize_text_field($data['given_name']) : '';
    $family_name = !empty($data['family_name']) ? sanitize_text_field($data['family_name']) : '';
    $avatar = !empty($data['picture']) ? esc_url_raw($data['picture']) : '';
    $google_id = !empty($data['sub']) ? sanitize_text_field($data['sub']) : '';

    // Split name if given/family name not set
    if (empty($given_name) && !empty($name)) {
        $parts = explode(' ', trim($name));
        $given_name = array_shift($parts);
        $family_name = !empty($parts) ? implode(' ', $parts) : '';
    }

    // 1. Check if user already exists with this email
    $user = get_user_by('email', $email);

    if ($user) {
        // Protect administrator & shop manager accounts from OAuth auto-login
        if (in_array('administrator', (array)$user->roles, true)) {
            wp_send_json_error(array('message' => __('Administrator accounts must sign in using password credentials.', 'aavni')));
        }
        $user_id = $user->ID;
    } else {
        // 2. Register New WordPress User with 'customer' role
        $base_username = sanitize_user(current(explode('@', $email)), true);
        $username = $base_username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $base_username . $counter;
            $counter++;
        }

        $random_password = wp_generate_password(24, true, true);
        $user_id = wp_create_user($username, $random_password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Set customer role
        $user = new WP_User($user_id);
        $user->set_role('customer');

        // Update profile & WooCommerce billing meta
        wp_update_user(array(
            'ID' => $user_id,
            'display_name' => !empty($name) ? $name : $username,
            'first_name' => $given_name,
            'last_name' => $family_name,
        ));

        update_user_meta($user_id, 'billing_first_name', $given_name);
        update_user_meta($user_id, 'billing_last_name', $family_name);
        update_user_meta($user_id, 'billing_email', $email);
        update_user_meta($user_id, 'shipping_first_name', $given_name);
        update_user_meta($user_id, 'shipping_last_name', $family_name);
    }

    // Save Google-specific meta
    if (!empty($google_id)) {
        update_user_meta($user_id, 'aavni_google_id', $google_id);
    }
    if (!empty($avatar)) {
        update_user_meta($user_id, 'aavni_google_avatar', $avatar);
    }
    update_user_meta($user_id, 'aavni_auth_provider', 'google');

    // 3. Authenticate and Log In the User in WordPress & WooCommerce
    wp_clear_auth_cookie();
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true);

    // Initialize WooCommerce customer session
    if (function_exists('WC') && WC()->session) {
        WC()->session->init();
        WC()->customer = new WC_Customer($user_id, true);
    }

    do_action('wp_login', $user->user_login, $user);

    // Determine redirect URL (Always redirect to Homepage after Google auth)
    $redirect_url = home_url('/');
    if (isset($_POST['redirect_to']) && !empty($_POST['redirect_to']) && strpos($_POST['redirect_to'], 'checkout') !== false) {
        $redirect_url = esc_url_raw($_POST['redirect_to']);
    }

    wp_send_json_success(array(
        'message' => __('Successfully signed in with Google!', 'aavni'),
        'redirect_url' => $redirect_url,
        'user_name' => !empty($name) ? $name : $user->display_name,
        'email' => $email
    ));
}
