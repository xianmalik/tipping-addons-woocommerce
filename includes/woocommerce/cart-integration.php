<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class TippingCartIntegration {
    public function __construct() {
        add_action('wp_ajax_add_tip_to_cart', [$this, 'add_tip_to_cart']);
        add_action('wp_ajax_nopriv_add_tip_to_cart', [$this, 'add_tip_to_cart']);
        add_action('woocommerce_checkout_order_processed', [$this, 'process_tip_order'], 10, 3);
    }

    public function add_tip_to_cart() {
        if (!wp_verify_nonce($_POST['nonce'], 'add_tip_to_cart')) {
            wp_send_json_error('Invalid nonce');
            return;
        }

        $amount = floatval($_POST['amount']);
        $post_id = intval($_POST['post_id']);

        if ($amount < 1 || !$post_id) {
            wp_send_json_error('Invalid amount or post ID');
            return;
        }

        $cart_item_data = [
            'tip_data' => [
                'song_id' => $post_id,
                'amount' => $amount
            ]
        ];

        $product_id = $this->get_or_create_tip_product();
        
        if (!$product_id) {
            wp_send_json_error('Failed to create tip product');
            return;
        }

        WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);
        wp_send_json_success();
    }

    private function get_or_create_tip_product() {
        $product_id = get_option('tipping_product_id');
        $product = $product_id ? wc_get_product($product_id) : null;

        if (!$product) {
            $product = new WC_Product_Simple();
            $product->set_name('Song Tip');
            $product->set_status('private');
            $product->set_catalog_visibility('hidden');
            $product->set_price(0);
            $product->set_regular_price(0);
            $product->set_virtual(true);
            $product_id = $product->save();

            if ($product_id) {
                update_option('tipping_product_id', $product_id);
            }
        }

        return $product_id;
    }

    public function process_tip_order($order_id, $posted_data, $order) {
        global $wpdb;

        foreach ($order->get_items() as $item) {
            $tip_data = $item->get_meta('tip_data');
            
            if ($tip_data) {
                $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                $customer_id = $order->get_customer_id();

                $wpdb->insert(
                    $wpdb->prefix . 'song_tips',
                    [
                        'song_id' => $tip_data['song_id'],
                        'tip_amount' => $tip_data['amount'],
                        'customer_name' => $customer_name,
                        'customer_id' => $customer_id,
                        'order_id' => $order_id
                    ],
                    ['%d', '%f', '%s', '%d', '%d']
                );
            }
        }
    }
}

new TippingCartIntegration();