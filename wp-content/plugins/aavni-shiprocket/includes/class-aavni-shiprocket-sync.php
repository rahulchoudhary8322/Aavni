<?php
/**
 * Shiprocket Cron Tracking Synchronizer
 *
 * Runs on WordPress Cron to periodically refresh in-transit shipments
 *
 * @package AAVNI_Shiprocket
 */

if (!defined('ABSPATH')) {
    exit;
}

class AAVNI_Shiprocket_Sync {

    const CRON_HOOK = 'aavni_shiprocket_tracking_sync_event';

    /**
     * Initialize Cron hooks
     */
    public static function init() {
        add_action(self::CRON_HOOK, [__CLASS__, 'run_scheduled_sync']);

        // Register custom schedule interval if needed
        add_filter('cron_schedules', function($schedules) {
            if (!isset($schedules['six_hours'])) {
                $schedules['six_hours'] = [
                    'interval' => 6 * 3600,
                    'display'  => __('Every 6 Hours', 'aavni'),
                ];
            }
            return $schedules;
        });

        // Ensure schedule is active if enabled
        if (get_option('aavni_shiprocket_sync_tracking_cron', 'yes') === 'yes') {
            if (!wp_next_scheduled(self::CRON_HOOK)) {
                wp_schedule_event(time() + 300, 'six_hours', self::CRON_HOOK);
            }
        } else {
            $timestamp = wp_next_scheduled(self::CRON_HOOK);
            if ($timestamp) {
                wp_unschedule_event($timestamp, self::CRON_HOOK);
            }
        }
    }

    /**
     * Run the scheduled background synchronization
     */
    public static function run_scheduled_sync() {
        if (!AAVNI_Shiprocket_API::is_enabled()) {
            return;
        }

        AAVNI_Shiprocket_API::log('Scheduled Shiprocket tracking sync job started.', 'info');

        // Find active in-transit orders with AWB
        $orders = wc_get_orders([
            'limit'        => 20,
            'status'       => ['processing', 'pending'],
            'meta_key'     => AAVNI_Shiprocket_Shipment::META_AWB_CODE,
            'meta_compare' => 'EXISTS',
        ]);

        $updated_count = 0;
        foreach ($orders as $order) {
            $milestone = $order->get_meta('_aavni_order_milestone');
            if (in_array($milestone, ['delivered', 'cancelled', 'refunded'], true)) {
                continue;
            }

            $res = AAVNI_Shiprocket_Shipment::sync_tracking_for_order($order);
            if ($res['success']) {
                $updated_count++;
            }
            // Small pause to prevent hitting rate limits
            usleep(250000); // 250ms
        }

        AAVNI_Shiprocket_API::log("Scheduled tracking sync finished. Updated {$updated_count} orders.", 'info');
        update_option('aavni_shiprocket_last_cron_sync', current_time('mysql'));
    }

    /**
     * Register cron on plugin activation
     */
    public static function register_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 300, 'twicedaily', self::CRON_HOOK);
        }
    }

    /**
     * Deregister cron on plugin deactivation
     */
    public static function deregister_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }
}
