<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class DashboardHandler
{
    public function __construct()
    {
        remove_action('woocommerce_account_content', 'woocommerce_account_content');
        remove_action('woocommerce_account_dashboard', 'woocommerce_account_dashboard');
        // Add our custom dashboard content after the default content
        add_action('woocommerce_account_content', [$this, 'custom_dashboard_content'], 20);
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

            echo '<div class="dashboard-summary">';
            echo '<div class="summary-item">';
            echo '<h3>' . __('Total Tips Received', 'tipping-addons-jetengine') . '</h3>';
            echo '<p class="total-amount">' . wc_price($total_tips) . '</p>';
            echo '</div>';

            echo '<div class="summary-item">';
            echo '<h3>' . __('Total Songs Added', 'tipping-addons-jetengine') . '</h3>';
            echo '<p class="total-count">' . $total_songs . '</p>';
            echo '</div>';
            echo '</div>';
        } else {
            // Get songs user has tipped
            $tipped_songs = $this->get_user_tipped_songs();

            if (!empty($tipped_songs)) {
                echo '<div class="tipped-songs">';
                echo '<h3>' . __('Songs You Have Tipped', 'tipping-addons-jetengine') . '</h3>';
                echo '<ul class="song-list">';
                foreach ($tipped_songs as $song) {
                    echo '<li>';
                    echo '<span class="song-name">' . esc_html($song['name']) . '</span>';
                    echo '<span class="tip-amount">' . wc_price($song['tip_amount']) . '</span>';
                    echo '</li>';
                }
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<p>' . __('You haven\'t tipped any songs yet.', 'tipping-addons-jetengine') . '</p>';
            }
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