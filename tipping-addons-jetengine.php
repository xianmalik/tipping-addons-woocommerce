<?php

/**
 * Plugin Name: JetEngine Tipping Addons
 * Description: A tipping system integrated with JetEngine and Elementor
 * Version: 1.0.2
 * Author: Malik Zubayer
 * Text Domain: tipping-addons-jetengine
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class TippingAddonsJetEngine {
    private static $instance = null;

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
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

    public function init_components() {
        // Load plugin files
        require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
        require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-panel.php';
        require_once plugin_dir_path(__FILE__) . 'includes/woocommerce/cart-integration.php';
        
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

    public function register_widgets($widgets_manager) {
        $widgets_manager->register(new \TippingAddonsJetEngine\Widgets\TipWidget());
    }

    public function missing_elementor_notice() {
        echo '<div class="notice notice-warning"><p>' . 
             esc_html__('JetEngine Tipping Addons requires Elementor to be installed and activated.', 'tipping-addons-jetengine') . 
             '</p></div>';
    }

    public function missing_jetengine_notice() {
        echo '<div class="notice notice-warning"><p>' . 
             esc_html__('JetEngine Tipping Addons requires JetEngine to be installed and activated.', 'tipping-addons-jetengine') . 
             '</p></div>';
    }

    public function missing_woocommerce_notice() {
        echo '<div class="notice notice-warning"><p>' . 
             esc_html__('JetEngine Tipping Addons requires WooCommerce to be installed and activated.', 'tipping-addons-jetengine') . 
             '</p></div>';
    }
}

// Initialize the plugin
TippingAddonsJetEngine::get_instance();