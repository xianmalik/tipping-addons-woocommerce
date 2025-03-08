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

        // Create a unique product name for each page
        $product_name = sprintf('Tip for: %s', $page_title);

        // Create or get the product - Fix: Call the method using $this
        $product_id = $this->create_tip_product($product_name, $amount, $post_id);

        if (!$product_id) {
            wp_send_json_error('Failed to create product');
            return;
        }

        // Add to cart
        WC()->cart->add_to_cart($product_id, 1, 0, array(), array(
            'tip_amount' => $amount,
            'post_id' => $post_id,
            'page_title' => $page_title
        ));

        wp_send_json_success();
    }

    private function create_tip_product($product_name, $amount, $post_id)
    {
        // Create a unique SKU
        $sku = 'TIP-' . $post_id . '-' . uniqid();

        // Check if product exists with this SKU
        $product_id = wc_get_product_id_by_sku($sku);

        if (!$product_id) {
            $product = new WC_Product_Simple();
            $product->set_name($product_name);
            $product->set_status('publish');
            $product->set_catalog_visibility('hidden');
            $product->set_price($amount);
            $product->set_regular_price($amount);
            $product->set_sku($sku);
            $product->set_virtual(true);

            // Set featured image if available
            if (!empty($_POST['featured_image_id'])) {
                $product->set_image_id(intval($_POST['featured_image_id']));
            }

            $product->save();

            return $product->get_id();
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