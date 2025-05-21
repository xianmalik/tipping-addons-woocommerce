<?php
if (!defined('ABSPATH')) {
    exit;
}

class StickyCart
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_shortcode('musicbae_cart_icon', [$this, 'cart_icon_shortcode']);
    }

    public function enqueue_styles()
    {
        wp_enqueue_style(
            'sticky-cart-styles',
            plugins_url('/assets/css/sticky-cart.css', dirname(__FILE__)),
            [],
            '1.2.1'
        );
    }

    public function cart_icon_shortcode()
    {
        ob_start();
        $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        $cart_url = wc_get_cart_url();
        $icon_url = plugins_url('/includes/assets/MusicBaeCart.png', dirname(dirname(__FILE__)));
?>
        <style>
            @keyframes cartBounce {

                0%,
                100% {
                    transform: scale(1);
                }

                50% {
                    transform: scale(1.2);
                }
            }

            .cart-bounce {
                animation: cartBounce 0.5s ease-in-out;
            }
        </style>
        <a href="<?php echo esc_url($cart_url); ?>" class="cart-icon-link">
            <div class="cart-icon-wrapper" id="cart-icon-wrapper" style="max-width: 32px;">
                <img src="<?php echo esc_url($icon_url); ?>" alt="Cart">
                <span class="cart-item-count" id="cart-item-count"><?php echo esc_html($cart_count); ?></span>
            </div>
        </a>
<?php
        return ob_get_clean();
    }
}
