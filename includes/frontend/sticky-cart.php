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
        <script>
            jQuery(document).ready(function($) {
                // Function to safely update cart icon
                function updateCartIcon(cartCount) {
                    // Update count if element exists
                    var countElement = document.getElementById('cart-item-count');
                    if (countElement) {
                        countElement.textContent = cartCount;
                    }

                    // Add bounce animation if element exists
                    var cartIcon = document.querySelector('.cart-icon-wrapper');
                    if (cartIcon) {
                        cartIcon.classList.remove('cart-bounce');
                        void cartIcon.offsetWidth; // Force reflow
                        cartIcon.classList.add('cart-bounce');
                    }
                }

                // Handle successful tip addition
                $(document).on('added_to_cart', function(event, fragments) {
                    try {
                        var cartCount = 0;
                        if (fragments && fragments['div.widget_shopping_cart_content']) {
                            cartCount = $(fragments['div.widget_shopping_cart_content']).find('.cart-items-count').text() || 0;
                        } else {
                            cartCount = WC().cart ? WC().cart.get_cart_contents_count() : 0;
                        }
                        updateCartIcon(cartCount);
                    } catch (e) {
                        console.log('Error updating cart icon:', e);
                    }
                });

                // Also handle direct AJAX success for tip addition
                $(document).ajaxSuccess(function(event, xhr, settings) {
                    if (settings.url && settings.url.indexOf('add_tip_to_cart') !== -1) {
                        try {
                            var response = xhr.responseJSON;
                            if (response && response.success) {
                                var cartCount = WC().cart ? WC().cart.get_cart_contents_count() : 0;
                                updateCartIcon(cartCount);
                            }
                        } catch (e) {
                            console.log('Error handling tip addition:', e);
                        }
                    }
                });
            });
        </script>
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
