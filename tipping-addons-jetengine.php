<?php

/**
 * Plugin Name: JetEngine Tipping Addons
 * Description: A tipping system integrated with JetEngine and Elementor
 * Version: 1.1.0
 * Author: Malik Zubayer
 * Text Domain: tipping-addons-jetengine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class TippingAddonsJetEngine
{
    private static $instance = null;

    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'init']);
        add_action('wp_footer', [$this, 'render_sticky_cart']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_sticky_cart_styles']);
    }

    public function init()
    {
        // Check if Elementor and JetEngine are installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'missing_elementor_notice']);
            return;
        }

        if (!class_exists('Jet_Engine')) {
            add_action('admin_notices', [$this, 'missing_jetengine_notice']);
            return;
        }

        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'missing_woocommerce_notice']);
            return;
        }

        // Initialize plugin components
        $this->init_components();
    }

    public function init_components()
    {
        // Load plugin files
        require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-panel.php';
        require_once plugin_dir_path(__FILE__) . 'includes/woocommerce/cart-integration.php';
        require_once plugin_dir_path(__FILE__) . 'includes/frontend/sticky-cart.php';

        // Initialize sticky cart
        new StickyCart();

        // Load widget after Elementor is fully initialized
        add_action('elementor/init', function () {
            require_once plugin_dir_path(__FILE__) . 'includes/widgets/tip-widget.php';
            // Register Elementor widget
            add_action('elementor/widgets/register', [$this, 'register_widgets']);
        });

        // Initialize admin panel
        if (is_admin()) {
            new TippingAdminPanel();
        }
    }

    public function register_widgets($widgets_manager)
    {
        $widgets_manager->register(new \TippingAddonsJetEngine\Widgets\TipWidget());
    }

    public function missing_elementor_notice()
    {
        echo '<div class="notice notice-warning"><p>' .
            esc_html__('JetEngine Tipping Addons requires Elementor to be installed and activated.', 'tipping-addons-jetengine') .
            '</p></div>';
    }

    public function missing_jetengine_notice()
    {
        echo '<div class="notice notice-warning"><p>' .
            esc_html__('JetEngine Tipping Addons requires JetEngine to be installed and activated.', 'tipping-addons-jetengine') .
            '</p></div>';
    }

    public function missing_woocommerce_notice()
    {
        echo '<div class="notice notice-warning"><p>' .
            esc_html__('JetEngine Tipping Addons requires WooCommerce to be installed and activated.', 'tipping-addons-jetengine') .
            '</p></div>';
    }

    public function enqueue_sticky_cart_styles()
    {
        wp_enqueue_style('dashicons');
        wp_add_inline_style('dashicons', '
            .sticky-cart-icon {
                position: fixed;
                top: 20%;
                right: 20px;
                background: transparent;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999;
                text-decoration: none;
                transition: transform 0.3s ease;
                padding: 10px;
                box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.2);
                background: #fafafa;
            }
            .sticky-cart-icon:hover {
                transform: scale(1.1);
            }
            .sticky-cart-icon img {
                width: 100%;
                height: 100%;
                object-fit: contain;
            }
            .cart-item-count {
                position: absolute;
                top: -5px;
                right: -5px;
                background: #ff4444;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                font-size: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
            }
        ');
    }

    public function render_sticky_cart()
    {
        if (!is_cart() && !is_checkout()) {
            $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
            $cart_url = wc_get_cart_url();
            $icon_url = plugins_url('/includes/assets/wmremove-transformed.png', __FILE__);
?>
            <a href="<?php echo esc_url($cart_url); ?>" class="sticky-cart-icon">
                <img src="<?php echo esc_url($icon_url); ?>" alt="Cart">
                <?php if ($cart_count > 0) : ?>
                    <span class="cart-item-count"><?php echo esc_html($cart_count); ?></span>
                <?php endif; ?>
            </a>
<?php
        }
    }
}

// Initialize the plugin
TippingAddonsJetEngine::get_instance();
