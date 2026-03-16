<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WooCommerce')) {
    return;
}

class ArtistWithdrawal
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_ajax_process_artist_withdrawal', [$this, 'process_withdrawal']);
        add_action('init', [$this, 'add_withdrawal_endpoint']);
        add_action('woocommerce_account_artist-withdrawal_endpoint', [$this, 'withdrawal_content']);
        add_action('wp_ajax_check_withdrawal_status', [$this, 'check_withdrawal_status']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_withdrawal_scripts']);
    }

    public function enqueue_withdrawal_scripts()
    {
        if (!is_wc_endpoint_url('artist-withdrawal') && !is_wc_endpoint_url('artist-sales')) {
            return;
        }

        wp_enqueue_style('tipping-withdrawal', PAPER_TIPPING_URL . 'assets/css/artist/withdrawal.css', [], '1.0.0');

        wp_enqueue_script('tipping-withdrawal', PAPER_TIPPING_URL . 'assets/js/artist/withdrawal.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('tipping-withdrawal-status', PAPER_TIPPING_URL . 'assets/js/artist/withdrawal-status.js', ['jquery'], '1.0.0', true);

        wp_localize_script('tipping-withdrawal', 'tipping_addons', [
            'ajax_url'         => admin_url('admin-ajax.php'),
            'withdrawal_nonce' => wp_create_nonce('artist_withdrawal_nonce'),
            'i18n'             => [
                'withdrawal_title' => __('Withdraw Funds', 'paper-tipping-addons'),
                'amount'           => __('Amount', 'paper-tipping-addons'),
                'paypal_email'     => __('PayPal Email', 'paper-tipping-addons'),
                'withdraw'         => __('Process Withdrawal', 'paper-tipping-addons'),
            ],
        ]);
    }

    public function process_withdrawal()
    {
        if (
            !isset($_POST['withdrawal_nonce']) ||
            !wp_verify_nonce($_POST['withdrawal_nonce'], 'artist_withdrawal_nonce') ||
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'paper-tipping-addons')]);
        }

        $user_id      = get_current_user_id();
        $amount       = floatval($_POST['amount']);
        $paypal_email = sanitize_email($_POST['paypal_email']);

        if ($amount < 10) {
            wp_send_json_error(['message' => __('Minimum withdrawal amount is $10', 'paper-tipping-addons')]);
        }

        $available_balance = $this->calculate_available_balance($user_id);

        if ($amount > $available_balance) {
            wp_send_json_error(['message' => __('Insufficient balance', 'paper-tipping-addons')]);
        }

        $withdrawal_history   = $this->get_withdrawal_history($user_id);
        $withdrawal_id        = uniqid('withdrawal_');
        $withdrawal_history[] = [
            'id'           => $withdrawal_id,
            'amount'       => $amount,
            'date'         => time(),
            'status'       => 'pending',
            'paypal_email' => $paypal_email,
        ];
        update_user_meta($user_id, 'artist_withdrawal_history', $withdrawal_history);
        update_user_meta($user_id, 'artist_paypal_email', $paypal_email);

        try {
            $paypal = new PayPalIntegration();
            $result = $paypal->process_payout(
                $paypal_email,
                $amount,
                sprintf(__('Withdrawal of %s from your artist earnings', 'paper-tipping-addons'), wc_price($amount))
            );

            if ($result['success']) {
                $last = count($withdrawal_history) - 1;
                $withdrawal_history[$last]['status']   = 'completed';
                $withdrawal_history[$last]['batch_id'] = $result['batch_id'];
                update_user_meta($user_id, 'artist_withdrawal_history', $withdrawal_history);

                wp_send_json_success([
                    'message'     => __('Withdrawal processed successfully!', 'paper-tipping-addons'),
                    'new_balance' => wc_price($available_balance - $amount),
                ]);
            }
        } catch (Exception $e) {
            $last = count($withdrawal_history) - 1;
            $withdrawal_history[$last]['status'] = 'failed';
            update_user_meta($user_id, 'artist_withdrawal_history', $withdrawal_history);

            wp_send_json_error(['message' => __('Withdrawal failed. Please try again.', 'paper-tipping-addons')]);
        }
    }

    public function add_withdrawal_endpoint()
    {
        add_rewrite_endpoint('artist-withdrawal', EP_ROOT | EP_PAGES);

        if (!get_option('withdrawal_endpoint_added')) {
            flush_rewrite_rules();
            update_option('withdrawal_endpoint_added', true);
        }
    }

    public function withdrawal_content()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id           = get_current_user_id();
        $available_balance = $this->calculate_available_balance($user_id);
        $withdrawal_history = $this->get_withdrawal_history($user_id);
        $paypal_email      = get_user_meta($user_id, 'artist_paypal_email', true);

        include PAPER_TIPPING_PATH . 'templates/artist/withdrawal.php';
    }

    public function check_withdrawal_status()
    {
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

    /**
     * Available balance = total earnings minus completed/pending withdrawals.
     * Uses 'status !== failed' so pending withdrawals are still reserved.
     */
    private function calculate_available_balance(int $user_id): float
    {
        $total_earnings    = ArtistQuery::get_total_earnings($user_id);
        $withdrawal_history = $this->get_withdrawal_history($user_id);
        $total_withdrawn   = 0.0;

        foreach ($withdrawal_history as $w) {
            if ($w['status'] !== 'failed') {
                $total_withdrawn += $w['amount'];
            }
        }

        return $total_earnings - $total_withdrawn;
    }

    private function get_withdrawal_history(int $user_id): array
    {
        $history = get_user_meta($user_id, 'artist_withdrawal_history', true);
        return is_array($history) ? $history : [];
    }
}

ArtistWithdrawal::get_instance();
