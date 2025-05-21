<?php
if (!defined('ABSPATH')) {
    exit;
}

class StickyCart
{
    public function __construct()
    {
        add_action('wp_footer', [$this, 'render_sticky_cart']);
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
        <a href="<?php echo esc_url($cart_url); ?>" class="cart-icon-link">
            <div class="cart-icon-wrapper">
                <img src="<?php echo esc_url($icon_url); ?>" alt="Cart">
                <?php if ($cart_count > 0) : ?>
                    <span class="cart-item-count"><?php echo esc_html($cart_count); ?></span>
                <?php endif; ?>
            </div>
        </a>
<?php
        return ob_get_clean();
    }
}
