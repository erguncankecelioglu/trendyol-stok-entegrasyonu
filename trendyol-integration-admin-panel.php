<?php
date_default_timezone_set('Europe/Istanbul');
function trendyol_sync_admin_menu()
{
    add_submenu_page('woocommerce', 'Trendyol Senkronizasyon Ayarları', 'Trendyol Senkronizasyon', 'manage_options', 'trendyol-sync-settings', 'trendyol_sync_page_callback');
}

add_action('admin_menu', 'trendyol_sync_admin_menu');

function trendyol_sync_add_settings_link($actions, $plugin_file)
{
    static $plugin;

    if (!isset($plugin)) {
        $plugin = plugin_basename('trendyol-stok-entegrasyonu/trendyol-integration.php');
    }

    if ($plugin == $plugin_file) {
        $settings = array('settings' => '<a href="admin.php?page=trendyol-sync-settings">' . __('Ayarlar', 'trendyol-sync-settings') . '</a>');
        $actions = array_merge($settings, $actions);
    }

    return $actions;
}

add_filter('plugin_action_links', 'trendyol_sync_add_settings_link', 10, 2);


function trendyol_sync_page_callback()
{

    if (!current_user_can('manage_options')) {
        wp_die(__('Bu sayfaya erişim izniniz yok.'));
    }

    if (!class_exists('WooCommerce')) {
        wp_die(__('WooCommerce yüklenmemiş veya etkin değil. Bu eklentiyi kullanmak için lütfen WooCommerce eklentisini yükleyin ve etkinleştirin.'));
    }
    if (isset($_POST['trendyol_order_sync_button']) && check_admin_referer('trendyol_sync_nonce_action')) {

        add_settings_error('trendyol_sync', 'order_sync', 'Sipariş kontrolü manuel başlatıldı.', 'success');
        trendyol_orders_check();
    }

    register_setting('trendyol_sync', 'trendyol_sync');
    if (isset($_POST['trendyol_sync_submit'])) {
        update_option('trendyol_id', sanitize_text_field($_POST['trendyol_id']));
        update_option('trendyol_api_key', sanitize_text_field($_POST['trendyol_api_key']));
        update_option('trendyol_api_secret', sanitize_text_field($_POST['trendyol_api_secret']));
        add_settings_error('trendyol_sync', 'save_settings', 'Ayarlar kaydedildi.', 'success');
        update_option('trendyol_order_sync_interval', (int)$_POST['order_sync_interval']);
        wp_clear_scheduled_hook('trendyol_orders_check_event');
        $order_sync_interval = get_option('trendyol_order_sync_interval');
        $order_sync_interval_name = $order_sync_interval == 60 ? 'hourly' : 'daily';
        wp_schedule_event(time(), $order_sync_interval_name, 'trendyol_orders_check_event');
    }
?>
    <style>
        .trendyol-container {
            display: flex;
            justify-content: space-between;
        }

        .trendyol-container>div {
            width: 49%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
    <div class="wrap">
        <h1>Trendyol Senkronizasyon Ayarları</h1>
        <?php
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
        ?>
        <h2 class="nav-tab-wrapper">
            <a href="?page=trendyol-sync-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Ayarlar</a>
            <a href="?page=trendyol-sync-settings&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Son Hatalar</a>
            <a href="?page=trendyol-sync-settings&tab=premium" class="nav-tab premium-tab <?php echo $active_tab == 'premium' ? 'nav-tab-active' : ''; ?>">Premium</a>
        </h2>
        <?php if ($active_tab == 'premium') { ?>
            <div>
                <div>
                    <h4>Premium özellikleri: <br><br>
                        - Trendyol ile stok eşitleme (Trendyoldaki tüm ürünlerin stoklarını Trendyol'dan çekip WooCommerce ile eşitler böylece trendyolda iade, iptal gibi durumlarda da stoklarınız güncel olur. Stoklarınızı her daim senkronize tutabilirsiniz.) <br> <br>
                        - Sipariş, stok eşitleme aralıklarını ihtiyaçlarınıza göre belirlemenize olanak tanır. (Örn: 10 dakikada bir Trendyol siparişlerini kontrol edecek şekilde veya her 3 saatte bir stokları eşitleyecek şekilde ayarlayabilirsiniz.) <br> <br>
                        - Güncellenen stokları ve bilgi mesajlarını "Hatalar" gibi "Mesajlar" adı altında bir sekmede kaydeder ve size gösterir, böylece eklenti hangi verileri güncellemiş görebilirsiniz.<br> <br>
                        - Hangi ürünleriniz Trendyol'da var WooCommerce üzerinde yok veya hangi ürünleriniz WooCommerce üzerinde var ve Trendyol'da yok görebilirsiniz.
                        <br> <br>
                        <h4>Premium satın almak mı istiyorsunuz? (Çok Yakında Eklenecektir. Eklenti güncellemelerini takip edin.)</h4>
                        <div style="display: flex;">
                            <a href="https://www.linkedin.com/in/erguncan/" class="button button-primary" target="_blank">Premium Sürümü Satın Al</a>
                        </div>
                </div>
            <?php } ?>
            <?php if ($active_tab == 'settings') {
            ?>

                <div>
                    <br>
                    <?php settings_errors('trendyol_sync'); ?>

                    <form method="post">
                        <?php wp_nonce_field('trendyol_sync_nonce_action'); ?>
                        <p>
                            <label for="order_sync_interval">Sipariş kontrolü zaman aralığı (dakika):</label><br>
                            <select id="order_sync_interval" name="order_sync_interval" style="width: 100%; max-width: 300px; padding: 5px;">
                                <option value="60" <?php echo get_option('trendyol_order_sync_interval') == 60 ? 'selected' : ''; ?>>60</option>
                                <option value="90" <?php echo get_option('trendyol_order_sync_interval') == 90 ? 'selected' : ''; ?>>90</option>
                            </select>
                        </p>
                        <label for="trendyol_api_key">Satıcı Id:</label><br>
                        <input type="text" id="trendyol_id" name="trendyol_id" value="<?php echo get_option('trendyol_id', ''); ?>" required style="width: 100%; max-width: 300px; padding: 5px;"><br>
                        <label for="trendyol_api_key">API Key:</label><br>
                        <input type="text" id="trendyol_api_key" name="trendyol_api_key" value="<?php echo get_option('trendyol_api_key', ''); ?>" required style="width: 100%; max-width: 300px; padding: 5px;"><br>

                        <label for="trendyol_api_secret">API Secret:</label><br>
                        <input type="password" id="trendyol_api_secret" name="trendyol_api_secret" value="<?php echo get_option('trendyol_api_secret', ''); ?>" required style="width: 100%; max-width: 300px; padding: 5px;"><br><br>
                        <p>
                            <input type="submit" name="trendyol_sync_submit" value="Kaydet" class="button button-primary" style="padding: 10px 20px;">
                        </p>
                    </form>
                </div>
                <div>
                    <h2>Manuel Senkronizasyon</h2>
                    <div style="display: flex;">
                        <form method="post">
                            <?php wp_nonce_field('trendyol_sync_nonce_action'); ?>
                            <input type="submit" name="trendyol_order_sync_button" value="Sipariş Kontrolünü Manuel Başlat" class="button button-primary" style="padding: 10px 20px;">
                        </form>
                    </div>
                </div>
            <?php }
            if ($active_tab == 'logs') { ?>
                <div>
                    <br>
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'trendyol_error';

                    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
                    if (isset($_POST['submit_error_feedback'])) {
                        $topic = sanitize_text_field($_POST['topic']);
                        $message_content = sanitize_textarea_field($_POST['message']);


                        $to = 'erguncan06@gmail.com';
                        $subject = "Trendyol Entegrasyon Hata/Geri Bildirim Formu";
                        $message = "Konu: {$topic} \n\n Gönderen: " . get_option('admin_email') . "\n\n" . $message_content;
                        $headers = array('Content-Type: text/plain; charset=UTF-8');


                        if (wp_mail($to, $subject, $message, $headers)) {
                            echo '<div class="notice notice-success is-dismissible"><p>Email başarıyla gönderildi.</p></div>';
                        } else {
                            echo '<div class="notice notice-error is-dismissible"><p>Email gönderilemedi. Lütfen tekrar deneyin.</p></div>';
                        }
                    }
                    ?>
                    <div style="height: 400px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); border-radius: 5px; background-color: #f9f9f9;">
                        <?php if (!empty($results)) : ?>
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Tarih - Saat</th>
                                        <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Hata</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $row_count = 0; ?>
                                    <?php foreach ($results as $row) : ?>
                                        <tr style="<?php echo ($row_count % 2 === 0) ? 'background-color: #f2f2f2;' : ''; ?>">
                                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo $row->timestamp; ?></td>
                                            <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?php echo $row->log_text; ?></td>
                                        </tr>
                                        <?php $row_count++; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p>Veri bulunamadı.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3>Hatalar ve Geri Bildirimler</h3>
                        <p>Lütfen hatalar, öneri ve geri bildirimlerinizi bize iletin.</p>
                        <form method="post" action="">
                            <label for="topic">Konu:</label><br>
                            <input type="text" id="topic" name="topic" required style="width: 100%; max-width: 300px; padding: 5px;"><br>
                            <label for="message">Mesaj:</label><br>
                            <textarea id="message" name="message" rows="5" required style="width: 100%; max-width: 300px; padding: 5px;"></textarea><br><br>
                            <input type="submit" name="submit_error_feedback" value="Gönder" class="button button-primary" style="padding: 10px 20px;">
                        </form>
                    </div>
                </div>
            <?php }; ?>
            </div>
        <?php

    }
