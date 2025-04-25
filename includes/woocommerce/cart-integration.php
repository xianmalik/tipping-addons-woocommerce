<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class TippingCartIntegration {
    public function __construct() {
        add_action('wp_ajax_add_tip_to_cart', [$this, 'add_tip_to_cart']);
        add_action('wp_ajax_nopriv_add_tip_to_cart', [$this, 'add_tip_to_cart']);
        add_action('woocommerce_checkout_order_processed', [$this, 'process_tip_order'], 10, 3);
        add_action('woocommerce_order_status_completed', [$this, 'send_download_email'], 10, 1);

        // Add endpoint for My Account downloads
        add_action('init', [$this, 'add_endpoints']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_download_menu_item']);
        add_action('woocommerce_account_song-downloads_endpoint', [$this, 'song_downloads_content']);
    }

    public function add_tip_to_cart() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_tip_to_cart')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $page_title = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : '';

        if ($amount <= 0) {
            wp_send_json_error('Invalid amount');
            return;
        }

        // Use the post_id as the product_id since we're on a product page
        $product_id = $post_id;

        if ($product_id <= 0) {
            wp_send_json_error('Invalid product ID');
            return;
        }

        // Get the product
        $product = wc_get_product($product_id);

        if (!$product) {
            wp_send_json_error('Product not found');
            return;
        }

        // Get the original product price
        $original_price = $product->get_price();

        // Calculate the total price (original price + tip)
        $total_price = $original_price + $amount;

        // Add to cart with custom price
        WC()->cart->add_to_cart($product_id, 1, 0, array(), array(
            'custom_price' => $total_price,
            'original_price' => $original_price,
            'tip_amount' => $amount,
            'post_id' => $post_id,
            'page_title' => $page_title
        ));

        wp_send_json_success();
    }

    private function create_tip_product($product_name, $amount, $post_id)
    {
        // Create a consistent SKU for the same post
        $sku = 'TIP-' . $post_id;

        // Check if product exists with this SKU
        $product_id = wc_get_product_id_by_sku($sku);

        // Get all song files from post meta
        $post_meta = get_post_meta($post_id);
        $song_mp3 = isset($post_meta['song_mp3'][0]) ? wp_get_attachment_url($post_meta['song_mp3'][0]) : '';
        $song_wav = isset($post_meta['song_wav'][0]) ? wp_get_attachment_url($post_meta['song_wav'][0]) : '';
        $song_file = isset($post_meta['song_file'][0]) ? wp_get_attachment_url($post_meta['song_file'][0]) : '';

        // Prepare downloadable files in WooCommerce format
        $downloads = array();
        if ($song_mp3) {
            $downloads[md5($song_mp3)] = array(
                'id' => md5($song_mp3),
                'name' => 'MP3 Version',
                'file' => $song_mp3,
                'previous_hash' => ''
            );
        }
        if ($song_wav) {
            $downloads[md5($song_wav)] = array(
                'id' => md5($song_wav),
                'name' => 'WAV Version',
                'file' => $song_wav,
                'previous_hash' => ''
            );
        }
        if ($song_file) {
            $downloads[md5($song_file)] = array(
                'id' => md5($song_file),
                'name' => 'Original File',
                'file' => $song_file,
                'previous_hash' => ''
            );
        }

        if (!$product_id) {
            $product = new WC_Product_Simple();
            $product->set_name($product_name);
            $product->set_status('publish');
            $product->set_catalog_visibility('hidden');
            $product->set_sku($sku);
            $product->set_virtual(true);
            $product->set_downloadable(true);
        } else {
            $product = wc_get_product($product_id);
            $product->set_downloadable(true);
        }

        // Always update these properties
        $product->set_price($amount);
        $product->set_regular_price($amount);
        $product->set_downloads($downloads);
        $product->set_download_limit(-1);
        $product->set_download_expiry(-1);

        // Set featured image if available
        if (!empty($_POST['featured_image_id'])) {
            $product->set_image_id(intval($_POST['featured_image_id']));
        }

        $product->save();

        return $product->get_id();
    }

    public function process_tip_order($order_id, $posted_data, $order) {
        global $wpdb;

        // Get order items
        $order = wc_get_order($order_id);

        foreach ($order->get_items() as $item_id => $item) {
            // Check if this item has a tip
            $tip_amount = $item->get_meta('_tip_amount', true);

            if ($tip_amount > 0) {
                $product_id = $item->get_product_id();

                // Get the post ID if it was stored
                $post_id = $item->get_meta('post_id', true);
                if (!$post_id) {
                    $post_id = $product_id; // Fallback to product ID
                }

                // Get customer info
                $customer_id = $order->get_customer_id();
                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

                // Insert into tips table
                $table_name = $wpdb->prefix . 'song_tips';
                $wpdb->insert(
                    $table_name,
                    array(
                        'song_id' => $post_id,
                        'tip_amount' => $tip_amount,
                        'customer_name' => $customer_name,
                        'customer_id' => $customer_id,
                        'order_id' => $order_id,
                        'created_at' => current_time('mysql')
                    )
                );
            }
        }
    }

    public function send_download_email($order_id)
    {
        $order = wc_get_order($order_id);
        $customer_email = $order->get_billing_email();

        foreach ($order->get_items() as $item) {
            $tip_data = $item->get_meta('tip_data');
            if ($tip_data) {
                $song_mp3 = jet_engine()->meta_boxes->get_meta($tip_data['post_id'], 'song_mp3');
                $song_wav = jet_engine()->meta_boxes->get_meta($tip_data['post_id'], 'song_wav');
                $song_title = get_the_title($tip_data['post_id']);

                // Email content
                $subject = sprintf('Your downloads for %s are ready', $song_title);
                $message = sprintf(
                    "Thank you for your tip!\n\n" .
                        "You can download %s from your account dashboard:\n" .
                        "%s\n\n" .
                        "Or click here to download directly:\n" .
                        "MP3 Version: %s\n" .
                        "WAV Version: %s",
                    $song_title,
                    wc_get_account_endpoint_url('song-downloads'),
                    $song_mp3,
                    $song_wav
                );

                $mail_sent = wp_mail($customer_email, $subject, $message);

                if (!$mail_sent) {
                    // Log the error
                    error_log(sprintf(
                        'Failed to send download email for Order #%d to %s for song: %s',
                        $order_id,
                        $customer_email,
                        $song_title
                    ));

                    // Add order note about email failure
                    $order->add_order_note(sprintf(
                        'Failed to send download email for song: %s',
                        $song_title
                    ));
                } else {
                    // Add success note to order
                    $order->add_order_note(sprintf(
                        'Download email sent successfully for song: %s',
                        $song_title
                    ));
                }
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

        if (!empty($downloads)) {
            echo '<h2>Your Song Downloads</h2>';
            echo '<table class="woocommerce-orders-table">';
            echo '<thead><tr>';
            echo '<th>Song</th>';
            echo '<th>Date</th>';
            echo '<th>Downloads</th>';
            echo '</tr></thead>';
            echo '<tbody>';

            foreach ($downloads as $download) {
                $song_title = get_the_title($download->song_id);
                echo '<tr>';
                echo '<td>' . esc_html($song_title) . '</td>';
                echo '<td>' . esc_html($download->created_at) . '</td>';
                echo '<td>';
                if ($download->song_mp3) {
                    echo '<a href="' . esc_url($download->song_mp3) . '" class="button" style="margin-right: 10px;">MP3</a>';
                }
                if ($download->song_wav) {
                    echo '<a href="' . esc_url($download->song_wav) . '" class="button">WAV</a>';
                }
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        } else {
            echo '<p>No downloads available.</p>';
        }
    }

    public function add_endpoints()
    {
        add_rewrite_endpoint('song-downloads', EP_ROOT | EP_PAGES);
    }

    public function add_download_menu_item($items)
    {
        $items['song-downloads'] = 'Song Downloads';
        return $items;
    }
}

new TippingCartIntegration();