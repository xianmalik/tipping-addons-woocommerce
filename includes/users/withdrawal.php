<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// WordPress dependencies
require_once(ABSPATH . 'wp-admin/includes/plugin.php');
require_once(ABSPATH . 'wp-includes/option.php');
require_once(ABSPATH . 'wp-includes/rewrite.php');
require_once(ABSPATH . 'wp-includes/user.php');
require_once(ABSPATH . 'wp-includes/pluggable.php');

// WooCommerce check
if (!class_exists('WooCommerce')) {
    return;
}

class ArtistWithdrawal {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_process_artist_withdrawal', [$this, 'process_withdrawal']);
        add_action('init', [$this, 'add_withdrawal_endpoint']);
        add_action('woocommerce_account_artist-withdrawal_endpoint', [$this, 'withdrawal_content']);
        add_action('wp_ajax_check_withdrawal_status', [$this, 'check_withdrawal_status']);

        // Add scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_withdrawal_scripts']);
    }

    public function enqueue_withdrawal_scripts() {
        if (is_wc_endpoint_url('artist-withdrawal') || is_wc_endpoint_url('artist-sales')) {
            // Enqueue withdrawal CSS
            wp_enqueue_style(
                'tipping-withdrawal',
                plugin_dir_url(dirname(__FILE__)) . '../assets/css/withdrawal.css',
                [],
                '1.0.0'
            );

            // Enqueue withdrawal.js
            wp_enqueue_script(
                'tipping-withdrawal',
                plugin_dir_url(dirname(__FILE__)) . '../assets/js/withdrawal.js',
                ['jquery'],
                '1.0.0',
                true
            );

            // Enqueue withdrawal-status.js
            wp_enqueue_script(
                'tipping-withdrawal-status',
                plugin_dir_url(dirname(__FILE__)) . '../assets/js/withdrawal-status.js',
                ['jquery'],
                '1.0.0',
                true
            );

            // Localize scripts
            wp_localize_script('tipping-withdrawal', 'tipping_addons', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'withdrawal_nonce' => wp_create_nonce('artist_withdrawal_nonce'),
                'i18n' => [
                    'withdrawal_title' => __('Withdraw Funds', 'paper-tipping-addons'),
                    'amount' => __('Amount', 'paper-tipping-addons'),
                    'paypal_email' => __('PayPal Email', 'paper-tipping-addons'),
                    'withdraw' => __('Process Withdrawal', 'paper-tipping-addons')
                ]
            ]);
        }
    }

    public function process_withdrawal() {
        // Verify nonce and check if user is logged in
        if (!isset($_POST['withdrawal_nonce']) || 
            !wp_verify_nonce($_POST['withdrawal_nonce'], 'artist_withdrawal_nonce') || 
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'paper-tipping-addons')]);
        }

        $user_id = get_current_user_id();
        $amount = floatval($_POST['amount']);
        $paypal_email = sanitize_email($_POST['paypal_email']);

        // Validate withdrawal amount
        if ($amount < 10) {
            wp_send_json_error(['message' => __('Minimum withdrawal amount is $10', 'paper-tipping-addons')]);
        }

        // Get current balance
        $total_sales = $this->get_artist_total_sales($user_id);
        $withdrawal_history = get_user_meta($user_id, 'artist_withdrawal_history', true);
        if (!is_array($withdrawal_history)) {
            $withdrawal_history = array();
        }

        // Calculate available balance
        $total_withdrawn = 0;
        foreach ($withdrawal_history as $withdrawal) {
            if ($withdrawal['status'] !== 'failed') {
                $total_withdrawn += $withdrawal['amount'];
            }
        }
        $available_balance = $total_sales - $total_withdrawn;

        if ($amount > $available_balance) {
            wp_send_json_error(['message' => __('Insufficient balance', 'paper-tipping-addons')]);
        }

        // Process PayPal payment
        $withdrawal_id = uniqid('withdrawal_');
        $withdrawal = [
            'id' => $withdrawal_id,
            'amount' => $amount,
            'date' => time(),
            'status' => 'pending',
            'paypal_email' => $paypal_email
        ];

        // Add withdrawal to history
        $withdrawal_history[] = $withdrawal;
        update_user_meta($user_id, 'artist_withdrawal_history', $withdrawal_history);

        // Save PayPal email for future use
        update_user_meta($user_id, 'artist_paypal_email', $paypal_email);

        // Initialize PayPal payment
        try {
            $paypal = new PayPalIntegration();
            $result = $paypal->process_payout(
                $paypal_email,
                $amount,
                sprintf(__('Withdrawal of %s from your artist earnings', 'paper-tipping-addons'), 
                    wc_price($amount)
                )
            );

            if ($result['success']) {
                $withdrawal_history[count($withdrawal_history) - 1]['status'] = 'completed';
                $withdrawal_history[count($withdrawal_history) - 1]['batch_id'] = $result['batch_id'];
                update_user_meta($user_id, 'artist_withdrawal_history', $withdrawal_history);

                wp_send_json_success([
                    'message' => __('Withdrawal processed successfully!', 'paper-tipping-addons'),
                    'new_balance' => wc_price($available_balance - $amount)
                ]);
            }
        } catch (Exception $e) {
            $withdrawal_history[count($withdrawal_history) - 1]['status'] = 'failed';
            update_user_meta($user_id, 'artist_withdrawal_history', $withdrawal_history);
            
            wp_send_json_error([
                'message' => __('Withdrawal failed. Please try again.', 'paper-tipping-addons')
            ]);
        }
    }

    public function add_withdrawal_endpoint() {
        add_rewrite_endpoint('artist-withdrawal', EP_ROOT | EP_PAGES);
        
        if (!get_option('withdrawal_endpoint_added')) {
            flush_rewrite_rules();
            update_option('withdrawal_endpoint_added', true);
        }
    }

    public function withdrawal_content() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $total_sales = $this->get_artist_total_sales($user_id);
        $withdrawal_history = get_user_meta($user_id, 'artist_withdrawal_history', true);
        $paypal_email = get_user_meta($user_id, 'artist_paypal_email', true);

        // Calculate available balance
        $total_withdrawn = 0;
        if (is_array($withdrawal_history)) {
            foreach ($withdrawal_history as $withdrawal) {
                if ($withdrawal['status'] === 'completed') {
                    $total_withdrawn += $withdrawal['amount'];
                }
            }
        }
        $available_balance = $total_sales - $total_withdrawn;
        ?>
        <div class="artist-withdrawal-wrapper">
            <div class="balance-info">
                <p class="available-balance">
                    <?php printf(__('Available Balance: %s', 'paper-tipping-addons'), wc_price($available_balance)); ?>
                </p>
                <?php if ($available_balance < 10) : ?>
                    <p class="minimum-balance-notice">
                        <?php _e('You need a minimum balance of $10 to make a withdrawal.', 'paper-tipping-addons'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ($available_balance >= 10) : ?>
                <button class="withdraw-money-btn button" 
                    data-balance="<?php echo esc_attr($available_balance); ?>"
                    data-email="<?php echo esc_attr($paypal_email); ?>">
                    <?php _e('Withdraw Funds', 'paper-tipping-addons'); ?>
                </button>
            <?php endif; ?>

            <?php if (!empty($withdrawal_history) && is_array($withdrawal_history)) : ?>
                <div class="withdrawal-history">
                    <h3><?php _e('Withdrawal History', 'paper-tipping-addons'); ?></h3>
                    <table>
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'paper-tipping-addons'); ?></th>
                                <th><?php _e('Amount', 'paper-tipping-addons'); ?></th>
                                <th><?php _e('PayPal Email', 'paper-tipping-addons'); ?></th>
                                <th><?php _e('Status', 'paper-tipping-addons'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($withdrawal_history as $withdrawal) : ?>
                                <tr>
                                    <td><?php echo date_i18n(get_option('date_format'), $withdrawal['date']); ?></td>
                                    <td><?php echo wc_price($withdrawal['amount']); ?></td>
                                    <td><?php echo esc_html($withdrawal['paypal_email']); ?></td>
                                    <td class="<?php echo esc_attr($withdrawal['status']); ?>">
                                        <?php echo esc_html(ucfirst($withdrawal['status'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function check_withdrawal_status() {
        if (!isset($_POST['batch_id']) || !wp_verify_nonce($_POST['security'], 'check_withdrawal_status')) {
            wp_send_json_error();
        }

        $paypal = new PayPalIntegration();
        $status = $paypal->get_payout_status(sanitize_text_field($_POST['batch_id']));

        if ($status) {
            wp_send_json_success(['status' => $status]);
        } else {
            wp_send_json_error();
        }
    }

    private function get_artist_total_sales($user_id) {
        $args = [
            'post_type' => 'product',
            'author' => $user_id,
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
        ];

        $products = get_posts($args);
        $product_ids = wp_list_pluck($products, 'ID');
        $total_sales = 0;

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
                        $total_sales += $item->get_total();
                    }
                }
            }
        }

        return $total_sales;
    }
}

ArtistWithdrawal::get_instance();
