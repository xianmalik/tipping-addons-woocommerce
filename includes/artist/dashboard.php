<?php
if (!defined('ABSPATH')) {
    exit;
}

class DashboardHandler
{
    public function __construct()
    {
        remove_action('woocommerce_account_dashboard', 'woocommerce_account_dashboard');

        add_action('template_redirect', function () {
            global $wp;
            $current_url = home_url($wp->request);
            if (trailingslashit($current_url) === trailingslashit(wc_get_page_permalink('myaccount'))) {
                remove_action('woocommerce_account_content', 'woocommerce_account_content');
                add_action('woocommerce_account_content', [$this, 'custom_dashboard_content'], 20);
            }
        });

        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }

    public function enqueue_dashboard_assets()
    {
        if (is_account_page()) {
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
            wp_enqueue_style('dashboard-cards', PAPER_TIPPING_URL . 'assets/css/artist/dashboard-cards.css');
        }
    }

    public function custom_dashboard_content()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user      = wp_get_current_user();
        $is_artist = in_array('music_artist_vendor', $user->roles);

        if ($is_artist) {
            $user_id     = get_current_user_id();
            $total_tips  = ArtistQuery::get_total_earnings($user_id);
            $total_songs = ArtistQuery::get_song_count($user_id);

            include PAPER_TIPPING_PATH . 'templates/artist/dashboard-cards.php';
        } else {
            $tipped_songs = ArtistQuery::get_customer_tipped_songs(get_current_user_id());

            include PAPER_TIPPING_PATH . 'templates/customer/dashboard-user.php';
        }
    }
}
