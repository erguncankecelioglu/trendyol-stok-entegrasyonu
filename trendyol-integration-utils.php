<?php
class Trendyol_Integration_Utils
{

    public static function trendyol_error($message)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'trendyol_error';
        $log_message = $message;

        $wpdb->insert(
            $table_name,
            array(
                'log_text' => $log_message,
            )
        );
    }

    public static function generate_basic_auth_header()
    {
        $api_key = get_option('trendyol_api_key');
        $api_secret = get_option('trendyol_api_secret');
        $credentials = $api_key . ':' . $api_secret;
        $encoded_credentials = base64_encode($credentials);
        return 'Basic ' . $encoded_credentials;
    }
}

function create_trendyol_log_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'trendyol_error';

    $charset_collate = $wpdb->get_charset_collate();
    date_default_timezone_set('Europe/Istanbul');

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        log_text text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__DIR__ . '/trendyol-integration.php', 'create_trendyol_log_table');


function custom_cron_schedules($schedules)
{

    $schedules['order_sync_interval1'] = array(
        'interval' => 60 * 60,
        'display' => __('Order Sync Interval1')
    );

    $schedules['stock_sync_interval1'] = array(
        'interval' => 720 * 60,
        'display' => __('Stock Sync Interval1')
    );

    $schedules['order_sync_interval2'] = array(
        'interval' => 90 * 60,
        'display' => __('Order Sync Interval1')
    );

    return $schedules;
}
add_filter('cron_schedules', 'custom_cron_schedules');
