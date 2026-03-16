<?php

/**
 * Plugin Name: Paper Tipping Addons
 * Description: A music artist marketplace and tipping system for WooCommerce.
 * Version: 1.8.5
 * Author: Malik Zubayer
 * Text Domain: paper-tipping-addons
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PAPER_TIPPING_PATH', plugin_dir_path(__FILE__));
define('PAPER_TIPPING_URL',  plugin_dir_url(__FILE__));

class PaperTippingAddons
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
        register_activation_hook(__FILE__, [$this, 'plugin_activation']);
    }

    public function init()
    {
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

        $this->init_components();
    }

    public function init_components()
    {
        // Core utilities (loaded first — other modules depend on them)
        require_once PAPER_TIPPING_PATH . 'includes/core/class-artist-query.php';
        require_once PAPER_TIPPING_PATH . 'includes/core/class-upload-handler.php';

        // Feature modules
        require_once PAPER_TIPPING_PATH . 'includes/woocommerce/cart-filters.php';
        require_once PAPER_TIPPING_PATH . 'includes/woocommerce/cart-integration.php';
        require_once PAPER_TIPPING_PATH . 'includes/admin/admin-panel.php';
        require_once PAPER_TIPPING_PATH . 'includes/frontend/sticky-cart.php';
        require_once PAPER_TIPPING_PATH . 'includes/artist/artist-vendor.php';
        require_once PAPER_TIPPING_PATH . 'includes/integrations/paypal.php';
        require_once PAPER_TIPPING_PATH . 'includes/artist/withdrawal.php';
        require_once PAPER_TIPPING_PATH . 'includes/admin/performance-fixes.php';

        // Ensure artist role exists at runtime
        $this->ensure_artist_role_exists();

        // Sticky cart shortcode
        new StickyCart();

        // Elementor widget (registered after Elementor fully boots)
        add_action('elementor/init', function () {
            require_once PAPER_TIPPING_PATH . 'includes/frontend/tip-widget.php';
            add_action('elementor/widgets/register', [$this, 'register_widgets']);
        });

        // Admin panel
        if (is_admin()) {
            new TippingAdminPanel();
        }
    }

    public function ensure_artist_role_exists()
    {
        if (!get_role('music_artist_vendor')) {
            add_role(
                'music_artist_vendor',
                'Music Artist - Vendor',
                [
                    'read'         => true,
                    'edit_posts'   => false,
                    'delete_posts' => false,
                    'publish_posts'=> false,
                    'upload_files' => true,
                ]
            );
        }
    }

    public function plugin_activation()
    {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'song_tips';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id             bigint(20)     NOT NULL AUTO_INCREMENT,
            song_id        bigint(20)     NOT NULL,
            tip_amount     decimal(10,2)  NOT NULL,
            customer_name  varchar(255)   NOT NULL DEFAULT '',
            customer_id    bigint(20)     NOT NULL DEFAULT 0,
            order_id       bigint(20)     NOT NULL DEFAULT 0,
            song_mp3       varchar(255)   NOT NULL DEFAULT '',
            song_wav       varchar(255)   NOT NULL DEFAULT '',
            created_at     datetime       DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $this->ensure_artist_role_exists();
        flush_rewrite_rules();
    }

    public function register_widgets($widgets_manager)
    {
        $widgets_manager->register(new \PaperTippingAddons\Widgets\TipWidget());
    }

    public function missing_elementor_notice()
    {
        echo '<div class="notice notice-warning"><p>'
            . esc_html__('Paper Tipping Addons requires Elementor to be installed and activated.', 'paper-tipping-addons')
            . '</p></div>';
    }

    public function missing_jetengine_notice()
    {
        echo '<div class="notice notice-warning"><p>'
            . esc_html__('Paper Tipping Addons requires JetEngine to be installed and activated.', 'paper-tipping-addons')
            . '</p></div>';
    }

    public function missing_woocommerce_notice()
    {
        echo '<div class="notice notice-warning"><p>'
            . esc_html__('Paper Tipping Addons requires WooCommerce to be installed and activated.', 'paper-tipping-addons')
            . '</p></div>';
    }
}

PaperTippingAddons::get_instance();
