<?php

/**
 * Plugin Name: JetEngine Tipping Addons
 * Description: A tipping system integrated with JetEngine and Elementor
 * Version: 1.8.0
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
        require_once plugin_dir_path(__FILE__) . 'includes/users/artist-vendor.php';

        // Include the performance fixes
        require_once plugin_dir_path(__FILE__) . 'includes/admin/performance-fixes.php';

        // Ensure artist role exists
        $this->ensure_artist_role_exists();

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
        
        // Register activation hook for database setup
        register_activation_hook(__FILE__, [$this, 'plugin_activation']);
    }
    
    /**
     * Ensure the artist role exists
     */
    public function ensure_artist_role_exists() {
        // Check if the role already exists
        if (!get_role('music_artist_vendor')) {
            // Create artist role if it doesn't exist
            add_role(
                'music_artist_vendor',
                'Music Artist - Vendor',
                [
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'publish_posts' => false,
                    'upload_files' => true,
                ]
            );
        }
    }
    
    public function plugin_activation() {
        // Create database table for song tips if it doesn't exist
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'song_tips';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            song_id bigint(20) NOT NULL,
            tip_amount decimal(10,2) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            song_mp3 varchar(255) DEFAULT '',
            song_wav varchar(255) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create artist role
        $this->ensure_artist_role_exists();
        
        // Flush rewrite rules
        flush_rewrite_rules();
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
}

// Initialize the plugin
TippingAddonsJetEngine::get_instance();
