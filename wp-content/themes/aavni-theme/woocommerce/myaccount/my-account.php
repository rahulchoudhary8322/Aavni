<?php
/**
 * AAVNI My Account Main Layout Override
 *
 * @package AAVNI
 */

defined('ABSPATH') || exit;
?>

<div class="aavni-myaccount-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="aavni-account-sidebar">
        <?php do_action('woocommerce_account_navigation'); ?>
    </aside>

    <!-- Main Content Area -->
    <main class="aavni-account-main woocommerce-MyAccount-content">
        <?php do_action('woocommerce_account_content'); ?>
    </main>
</div>
