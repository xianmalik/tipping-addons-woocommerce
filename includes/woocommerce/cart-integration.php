<?php
if (!defined('ABSPATH')) {
    exit;
}

class TippingCartIntegration
{
    public function __construct()
    {
        add_action('wp_ajax_add_tip_to_cart',        [$this, 'add_tip_to_cart']);
        add_action('wp_ajax_nopriv_add_tip_to_cart', [$this, 'add_tip_to_cart']);
        add_action('woocommerce_checkout_order_processed', [$this, 'process_tip_order'], 10, 3);
        add_action('woocommerce_order_status_completed',   [$this, 'send_download_email'], 10, 1);

        add_action('init',                                         [$this, 'add_endpoints']);
        add_filter('woocommerce_account_menu_items',               [$this, 'add_download_menu_item']);
        add_action('woocommerce_account_song-downloads_endpoint',  [$this, 'song_downloads_content']);
    }

    public function add_tip_to_cart()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_tip_to_cart')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $amount     = isset($_POST['amount'])     ? floatval($_POST['amount'])                       : 0;
        $post_id    = isset($_POST['post_id'])    ? intval($_POST['post_id'])                         : 0;
        $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title'])         : '';

        if ($amount <= 0 || $post_id <= 0) {
            wp_send_json_error('Invalid amount or product ID');
            return;
        }

        $product = wc_get_product($post_id);
        if (!$product) {
            wp_send_json_error('Product not found');
            return;
        }

        WC()->cart->add_to_cart($post_id, 1, 0, [], [
            'custom_price'   => $amount,
            'original_price' => 0,
            'tip_amount'     => $amount,
            'post_id'        => $post_id,
            'page_title'     => $page_title,
        ]);

        wp_send_json_success(['cart_count' => WC()->cart->get_cart_contents_count()]);
    }

    public function process_tip_order($order_id, $posted_data, $order)
    {
        global $wpdb;

        $order = wc_get_order($order_id);

        foreach ($order->get_items() as $item_id => $item) {
            $tip_amount = $item->get_meta('_tip_amount', true);

            if ($tip_amount > 0) {
                $product_id    = $item->get_product_id();
                $post_id       = $item->get_meta('post_id', true) ?: $product_id;
                $customer_id   = $order->get_customer_id();
                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

                $wpdb->insert(
                    $wpdb->prefix . 'song_tips',
                    [
                        'song_id'       => $post_id,
                        'tip_amount'    => $tip_amount,
                        'customer_name' => $customer_name,
                        'customer_id'   => $customer_id,
                        'order_id'      => $order_id,
                        'created_at'    => current_time('mysql'),
                    ]
                );
            }
        }
    }

    public function send_download_email($order_id)
    {
        $order          = wc_get_order($order_id);
        $customer_email = $order->get_billing_email();

        foreach ($order->get_items() as $item) {
            $tip_data = $item->get_meta('tip_data');
            if (!$tip_data) {
                continue;
            }

            $song_title = get_the_title($tip_data['post_id']);
            $song_mp3   = get_post_meta($tip_data['post_id'], 'song_mp3', true);
            $song_wav   = get_post_meta($tip_data['post_id'], 'song_wav', true);
            $mp3_url    = $song_mp3 ? wp_get_attachment_url($song_mp3) : '';
            $wav_url    = $song_wav ? wp_get_attachment_url($song_wav) : '';

            $subject = sprintf('Your downloads for %s are ready', $song_title);
            $message = sprintf(
                "Thank you for your tip!\n\nYou can download %s from your account dashboard:\n%s\n\nMP3: %s\nWAV: %s",
                $song_title,
                wc_get_account_endpoint_url('song-downloads'),
                $mp3_url,
                $wav_url
            );

            if (!wp_mail($customer_email, $subject, $message)) {
                error_log(sprintf('Failed to send download email for Order #%d to %s for song: %s', $order_id, $customer_email, $song_title));
                $order->add_order_note(sprintf('Failed to send download email for song: %s', $song_title));
            } else {
                $order->add_order_note(sprintf('Download email sent successfully for song: %s', $song_title));
            }
        }
    }

    public function song_downloads_content()
    {
        if (!is_user_logged_in()) {
            return;
        }

        global $wpdb;
        $customer_id = get_current_user_id();

        $downloads = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}song_tips WHERE customer_id = %d",
            $customer_id
        ));

        include PAPER_TIPPING_PATH . 'templates/customer/song-downloads.php';
    }

    public function add_endpoints()
    {
        add_rewrite_endpoint('song-downloads', EP_ROOT | EP_PAGES);
    }

    public function add_download_menu_item($items)
    {
        $items['song-downloads'] = __('Song Downloads', 'paper-tipping-addons');
        return $items;
    }
}

new TippingCartIntegration();
