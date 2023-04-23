<?php
function trendyol_update_stock($order_id)
{
    $order = wc_get_order($order_id);
    $items = $order->get_items();
    $trendyol_id = get_option('trendyol_id');
    $auth_header = Trendyol_Integration_Utils::generate_basic_auth_header();


    $api_items = [];
    foreach ($items as $item) {
        $product = $item->get_product();
        $stock_quantity = $product->get_stock_quantity();
        $barcode = $product->get_sku();
        $api_items[] = [
            "barcode" => $barcode,
            "quantity" => $stock_quantity,
        ];
    }

    $api_url = 'https://api.trendyol.com/sapigw/suppliers/' . $trendyol_id . '/products/price-and-inventory';
    $headers = [
        'Authorization' => $auth_header,
        'User-Agent' =>  $trendyol_id . ' - SelfIntegration',
        'Content-Type' => 'application/json',
    ];

    $response = wp_remote_post($api_url, [
        'headers' => $headers,
        'body' => json_encode(['items' => $api_items]),
    ]);
    if (wp_remote_retrieve_response_code($response) !== 200) {
        Trendyol_Integration_Utils::trendyol_error('Hata. ' . $order_id . ' için trendyol stoğu güncellenemedi: ' . wp_remote_retrieve_response_message($response));
    };
    if (is_wp_error($response)) {
        Trendyol_Integration_Utils::trendyol_error('Hata. ' . $order_id . ' için trendyol stoğu güncellenemedi: ' . $response->get_error_message());
    }
}
