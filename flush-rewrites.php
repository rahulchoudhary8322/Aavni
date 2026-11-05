
<?php
/**
 * AAVNI Google OAuth Setup
 * Run once, test, then DELETE this file.
 *
 * Credentials must be configured as Windows Environment Variables:
 * GOOGLE_CLIENT_ID
 * GOOGLE_CLIENT_SECRET
 */

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/wordpress/Aavni/';

require_once __DIR__ . '/wp-load.php';

// Load credentials from Windows Environment Variables
$client_id     = getenv('GOOGLE_CLIENT_ID');
$client_secret = getenv('GOOGLE_CLIENT_SECRET');

// Stop if credentials are missing
if (!$client_id || !$client_secret) {
    wp_die(
        'Google OAuth credentials are missing. Configure GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in Windows Environment Variables.'
    );
}

// Save credentials in WordPress database
update_option('aavni_google_client_id', $client_id);
update_option('aavni_google_client_secret', $client_secret);

// Flush rewrite rules
flush_rewrite_rules(true);

// Read saved credentials
$saved_client_id = get_option('aavni_google_client_id');
$saved_secret    = get_option('aavni_google_client_secret');

$callback_url = home_url('/aavni-google-callback/');

echo '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>AAVNI Google OAuth Setup</title>
<style>
body {
    font-family: sans-serif;
    max-width: 700px;
    margin: 40px auto;
    padding: 20px;
}
.ok {
    color: #166534;
    background: #dcfce7;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 10px 0;
}
.warn {
    color: #92400e;
    background: #fef3c7;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 10px 0;
}
.danger {
    color: #991b1b;
    background: #fee2e2;
    padding: 12px;
    border-radius: 8px;
    font-weight: bold;
}
code {
    background: #f3f4f6;
    padding: 3px 8px;
    border-radius: 4px;
}
</style>
</head>
<body>';

echo '<h2>AAVNI Google OAuth Setup</h2>';

if ($saved_client_id && $saved_secret) {

    echo '<div class="ok">Client ID saved successfully.</div>';
    echo '<div class="ok">Client Secret saved successfully.</div>';
    echo '<div class="ok">Rewrite rules flushed successfully.</div>';

    echo '<div class="ok">
        Callback URL: <code>' .
        esc_html($callback_url) .
        '</code>
    </div>';

    if (function_exists('aavni_get_google_oauth_url')) {

        $oauth_url = aavni_get_google_oauth_url();

        if ($oauth_url) {
            echo '<div class="ok">
                OAuth URL generated successfully!<br>
                <a href="' . esc_url($oauth_url) . '">
                Test Google Login
                </a>
            </div>';
        } else {
            echo '<div class="warn">
                OAuth URL could not be generated.
                Check your Google OAuth configuration.
            </div>';
        }

    } else {
        echo '<div class="warn">
            Google OAuth function was not found.
            Check that your AAVNI plugin or theme is active.
        </div>';
    }

    echo '<hr>';

    if (function_exists('wc_get_page_permalink')) {
        echo '<p>
        <a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">
        Go to My Account Login Page
        </a>
        </p>';
    }

} else {

    echo '<div class="warn">
    Something went wrong saving Google OAuth credentials.
    </div>';

}

echo '<div class="danger">
IMPORTANT: Delete flush-rewrites.php immediately after setup and testing.
</div>';

echo '</body></html>';
