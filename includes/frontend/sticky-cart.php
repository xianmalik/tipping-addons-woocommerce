<?php
if (!defined('ABSPATH')) {
    exit;
}

class StickyCart {
    public function __construct() {
        add_action('wp_footer', [$this, 'render_sticky_cart']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'sticky-cart-styles',
            plugins_url('/assets/css/sticky-cart.css', dirname(__FILE__)),
            [],
            '1.0.0'
        );
    }

    public function render_sticky_cart() {
        if (!is_cart() && !is_checkout()) {
            $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
            $cart_url = wc_get_cart_url();
            $icon_url = plugins_url('/includes/assets/wmremove-transformed.png', dirname(dirname(__FILE__)));
            
            include plugin_dir_path(__FILE__) . 'templates/sticky-cart.php';
        }
    }
}