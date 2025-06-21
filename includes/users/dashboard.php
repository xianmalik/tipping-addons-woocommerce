<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class DashboardHandler
{
    public function __construct()
    {
        // Remove default dashboard content
        remove_action('woocommerce_account_dashboard', 'woocommerce_account_dashboard');
        
        // Add our custom dashboard content only on the main /my-account/ URL
        add_action('template_redirect', function() {
            global $wp;
            $current_url = home_url($wp->request);
            // Check if we're on exactly /my-account/ URL
            if (trailingslashit($current_url) === trailingslashit(wc_get_page_permalink('myaccount'))) {
                remove_action('woocommerce_account_content', 'woocommerce_account_content');
                add_action('woocommerce_account_content', [$this, 'custom_dashboard_content'], 20);
            }
        });

        // Enqueue Font Awesome for icons
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }

    public function enqueue_dashboard_assets()
    {
        if (is_account_page()) {
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
            wp_enqueue_style('dashboard-cards', plugins_url('assets/css/dashboard-cards.css', dirname(dirname(__FILE__))));
        }
    }

    public function custom_dashboard_content()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        $is_artist = in_array('music_artist_vendor', $user->roles);

        if ($is_artist) {
            // Get total tips
            $total_tips = $this->get_artist_total_tips();

            // Get total songs
            $total_songs = $this->get_artist_song_count(get_current_user_id());

            // Load the template
            include plugin_dir_path(dirname(__FILE__)) . 'templates/dashboard-cards.php';
        } else {
            // Get songs user has tipped
            $tipped_songs = $this->get_user_tipped_songs();

            // Load the template for tipped songs
            include plugin_dir_path(dirname(__FILE__)) . 'templates/dashboard-user.php';
        }
    }

    private function get_artist_total_tips()
    {
        $user_id = get_current_user_id();
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'author' => $user_id,
            'post_status' => ['publish', 'draft', 'pending']
        ];

        $products = get_posts($args);
        $product_ids = wp_list_pluck($products, 'ID');
        $total_tips = 0;

        if (!empty($product_ids)) {
            $orders = wc_get_orders([
                'limit' => -1,
                'status' => ['completed', 'processing'],
                'return' => 'ids',
            ]);

            foreach ($orders as $order_id) {
                $order = wc_get_order($order_id);
                foreach ($order->get_items() as $item) {
                    if (in_array($item->get_product_id(), $product_ids)) {
                        $total_tips += $item->get_total();
                    }
                }
            }
        }

        return $total_tips;
    }

    private function get_user_tipped_songs()
    {
        $user_id = get_current_user_id();
        $tipped_songs = [];

        $orders = wc_get_orders([
            'customer' => $user_id,
            'limit' => -1,
            'status' => ['completed', 'processing'],
            'return' => 'ids',
        ]);

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product) {
                    $tipped_songs[] = [
                        'name' => $product->get_name(),
                        'tip_amount' => $item->get_total()
                    ];
                }
            }
        }

        return $tipped_songs;
    }

    private function get_artist_song_count($user_id)
    {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'author' => $user_id,
            'post_status' => ['publish', 'draft', 'pending']
        ];

        $products = get_posts($args);
        return count($products);
    }
}