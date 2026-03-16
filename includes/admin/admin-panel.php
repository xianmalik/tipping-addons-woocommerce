<?php
if (!defined('ABSPATH')) {
    exit;
}

class TippingAdminPanel
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __('Song Tips', 'paper-tipping-addons'),
            __('Song Tips', 'paper-tipping-addons'),
            'manage_options',
            'song-tips',
            [$this, 'render_admin_page'],
            'dashicons-money-alt',
            30
        );

        add_submenu_page(
            'song-tips',
            __('PayPal Settings', 'paper-tipping-addons'),
            __('PayPal Settings', 'paper-tipping-addons'),
            'manage_options',
            'song-tips-paypal',
            [$this, 'render_paypal_settings']
        );
    }

    public function render_admin_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'song_tips';
        $tips       = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        include PAPER_TIPPING_PATH . 'templates/admin/tips-table.php';
    }

    public function render_paypal_settings()
    {
        $saved_notice = false;

        if (
            isset($_POST['submit_paypal_settings']) &&
            isset($_POST['paypal_settings_nonce']) &&
            wp_verify_nonce($_POST['paypal_settings_nonce'], 'save_paypal_settings')
        ) {
            update_option('tipping_paypal_client_id',     sanitize_text_field($_POST['tipping_paypal_client_id'] ?? ''));
            update_option('tipping_paypal_client_secret', sanitize_text_field($_POST['tipping_paypal_client_secret'] ?? ''));
            update_option('tipping_paypal_sandbox',       isset($_POST['tipping_paypal_sandbox']) ? '1' : '0');
            $saved_notice = true;
        }

        $client_id     = get_option('tipping_paypal_client_id', '');
        $client_secret = get_option('tipping_paypal_client_secret', '');
        $sandbox_mode  = get_option('tipping_paypal_sandbox', '1');

        include PAPER_TIPPING_PATH . 'templates/admin/paypal-settings.php';
    }
}
