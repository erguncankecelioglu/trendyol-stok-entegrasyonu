<?php
function get_start_date_milliseconds()
{
    $order_sync_interval_minutes = get_option('trendyol_order_sync_interval', 60);
    $interval_with_offset = $order_sync_interval_minutes + 10;

    $date = new DateTime();
    $date->modify("-{$interval_with_offset} minutes");
    return $date->getTimestamp() * 1000;
}

function trendyol_orders_check()
{
    $trendyol_id = get_option('trendyol_id');
    $auth_header = Trendyol_Integration_Utils::generate_basic_auth_header();

    $start_date = get_start_date_milliseconds();
    $url = "https://api.trendyol.com/sapigw/suppliers/{$trendyol_id}/orders?startDate={$start_date}";
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => $auth_header,
            'User-Agent' => $trendyol_id . ' - SelfIntegration'
        ),
    ));

    if (is_wp_error($response)) {
        Trendyol_Integration_Utils::trendyol_error("Siparişler alınırken hata: " . $response->get_error_message());
        return;
    }
    if (wp_remote_retrieve_response_code($response) !== 200) {
        Trendyol_Integration_Utils::trendyol_error('Trendyol APIden ürün çekilirken hata oluştu. Lütfen api key, api secret ve satıcı id bilgilerinizi kontrol ediniz. : ' . wp_remote_retrieve_response_message($response));
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($data['content'])) {
        foreach ($data['content'] as $order) {
            if (isset($order['lines'])) {
                foreach ($order['lines'] as $line) {
                    $barcode = $line['barcode'];


                    $product_url = "https://api.trendyol.com/sapigw/suppliers/{$trendyol_id}/products?barcode={$barcode}";

                    $product_response = wp_remote_get($product_url, array(
                        'headers' => array(
                            'Authorization' => $auth_header,
                            'User-Agent' => $trendyol_id . ' - SelfIntegration'
                        ),
                    ));
                    if (wp_remote_retrieve_response_code($response) !== 200) {
                        Trendyol_Integration_Utils::trendyol_error('Trendyol APIden ürün çekilirken hata oluştu. Lütfen api key, api secret ve satıcı id bilgilerinizi kontrol ediniz.: ' . wp_remote_retrieve_response_message($response));
                        return;
                    }
                    if (is_wp_error($product_response)) {
                        Trendyol_Integration_Utils::trendyol_error("Hata. Ürünler alnamadı: " . $product_response->get_error_message());
                        continue;
                    }

                    $product_data = json_decode(wp_remote_retrieve_body($product_response), true);

                    if (isset($product_data['content'][0])) {
                        $trendyol_quantity = $product_data['content'][0]['quantity'];

                        $product = wc_get_product_id_by_sku($line['sku']);

                        if ($product) {
                            $wc_product = wc_get_product($product);
                            $wc_quantity = $wc_product->get_stock_quantity();

                            if ($wc_quantity != $trendyol_quantity) {
                                $wc_product->set_stock_quantity($trendyol_quantity);
                                $wc_product->save();
                            }
                        } else {
                            Trendyol_Integration_Utils::trendyol_error("Hata: WooCommerce ürünü" . $line['sku'] . "stok kodu için bulunamadı.");
                        }
                    } else {
                        Trendyol_Integration_Utils::trendyol_error("Hata: Trendyol ürünü" . $barcode . "barkod için bulunamadı.");
                    }
                }
            }
        }
    }
}


add_action('trendyol_orders_check_event', 'trendyol_orders_check');
