<?php
/*
Plugin Name: Trendyol Stok Entegrasyonu 
Description: Woocommerce ve Trendyol arasında eşzamanlı stok entegrasyonu
Version: 1.0
Requires at least: 5.0
Requires PHP: 7.0
Tested up to: 6.2
Author: Ergüncan Keçelioğlu
Author URI: http://erguncan.com
Text Domain: trendyol-stok-entegrasyonu
Domain Path: /languages
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Tags: woocommerce, trendyol, stock, integration, sync, entegrasyon,
WC tested up to: 7.6
*/



if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$plugin_dir = plugin_dir_path(__FILE__);

require_once $plugin_dir . '/trendyol-order-sync.php';
require_once $plugin_dir . '/trendyol-integration-utils.php';
require_once $plugin_dir . '/trendyol-integration-admin-panel.php';
require_once $plugin_dir . '/woocommerce-order-integration.php';

date_default_timezone_set('Europe/Istanbul');
$trendyol_id = get_option('trendyol_id');

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('woocommerce_order_status_changed', 'trendyol_update_stock', 10, 4);
}
