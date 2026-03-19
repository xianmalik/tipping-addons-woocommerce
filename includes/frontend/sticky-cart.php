<?php
if (!defined('ABSPATH')) {
    exit;
}

class StickyCart
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('paperhouse_cart_icon', [$this, 'cart_icon_shortcode']);
    }

    public function enqueue_assets()
    {
        wp_enqueue_style(
            'sticky-cart-styles',
            PAPER_TIPPING_URL . 'assets/css/frontend/sticky-cart.css',
            [],
            '1.2.1'
        );

        wp_enqueue_script(
            'sticky-cart-script',
            PAPER_TIPPING_URL . 'assets/js/frontend/sticky-cart.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public function cart_icon_shortcode()
    {
        $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        $cart_url   = wc_get_cart_url();
        $icon_url   = PAPER_TIPPING_URL . 'assets/images/cart-icon.png';

        ob_start();
        include PAPER_TIPPING_PATH . 'templates/frontend/sticky-cart.php';
        return ob_get_clean();
    }
}
