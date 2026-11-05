<?php
/**
 * AAVNI Google OAuth Setup - Run Once Then DELETE this file!
 */
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/wordpress/Aavni/';
require_once __DIR__ . '/wp-load.php';

// Set Google OAuth credentials
update_option('aavni_google_client_id',     '64463288312-asm67apkp0ga6qgs3m2pgpu2oasdsu2n.apps.googleusercontent.com');
update_option('aavni_google_client_secret', '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');

// Flush rewrite rules so /aavni-google-callback/ works
flush_rewrite_rules(true);

$client_id     = get_option('aavni_google_client_id');
$client_secret = get_option('aavni_google_client_secret');
$callback_url  = home_url('/aavni-google-callback/');

echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px;}
.ok{color:#166534;background:#dcfce7;padding:12px 16px;border-radius:8px;margin:10px 0;border:1px solid #86efac;}
.warn{color:#92400e;background:#fef3c7;padding:12px 16px;border-radius:8px;margin:10px 0;border:1px solid #fcd34d;}
.danger{color:#991b1b;background:#fee2e2;padding:12px 16px;border-radius:8px;border:1px solid #fca5a5;font-weight:bold;font-size:15px;}
code{background:#f3f4f6;padding:3px 8px;border-radius:4px;font-size:13px;}
</style></head><body>';

echo '<h2>&#9989; AAVNI Google OAuth Setup</h2>';

if ($client_id && $client_secret) {
    echo '<div class="ok">&#10003; <strong>Client ID saved:</strong> <code>' . esc_html(substr($client_id, 0, 40)) . '...</code></div>';
    echo '<div class="ok">&#10003; <strong>Client Secret saved:</strong> <code>****' . esc_html(substr($client_secret, -4)) . '</code></div>';
    echo '<div class="ok">&#10003; <strong>Rewrite rules flushed</strong></div>';
    echo '<div class="ok">&#10003; <strong>Callback URL:</strong> <code>' . esc_html($callback_url) . '</code></div>';

    $oauth_url = aavni_get_google_oauth_url();
    if ($oauth_url) {
        echo '<div class="ok">&#10003; <strong>OAuth URL generated successfully!</strong><br><a href="' . esc_url($oauth_url) . '" style="color:#166534;">&#128279; Test Google Login Now &rarr;</a></div>';
    }

    echo '<hr>';
    echo '<p><strong>Setup complete!</strong> Now test: <a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">Go to My Account Login Page</a></p>';
} else {
    echo '<div class="warn">&#9888; Something went wrong saving options. Try again.</div>';
}

echo '<div class="danger">&#128680; DELETE this file immediately after testing!<br>File: <code>flush-rewrites.php</code> in your Aavni root folder.</div>';
echo '</body></html>';
