<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PayPalIntegration {
    private $client_id;
    private $client_secret;
    private $is_sandbox;

    public function __construct() {
        $this->client_id = get_option('tipping_paypal_client_id');
        $this->client_secret = get_option('tipping_paypal_client_secret');
        $this->is_sandbox = get_option('tipping_paypal_sandbox', true);

        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting('tipping_options', 'tipping_paypal_client_id');
        register_setting('tipping_options', 'tipping_paypal_client_secret');
        register_setting('tipping_options', 'tipping_paypal_sandbox');
    }

    public function get_access_token() {
        $url = $this->is_sandbox ? 
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' : 
            'https://api-m.paypal.com/v1/oauth2/token';

        $response = wp_remote_post($url, [
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Language' => 'en_US',
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
            ],
            'body' => 'grant_type=client_credentials'
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        return isset($body->access_token) ? $body->access_token : false;
    }

    public function process_payout($email, $amount, $note = '') {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return [
                'success' => false,
                'message' => __('Failed to authenticate with PayPal', 'paper-tipping-addons')
            ];
        }

        $url = $this->is_sandbox ?
            'https://api-m.sandbox.paypal.com/v1/payments/payouts' :
            'https://api-m.paypal.com/v1/payments/payouts';

        $body = [
            'sender_batch_header' => [
                'sender_batch_id' => uniqid('payout_batch_'),
                'email_subject' => __('You have received a payout!', 'paper-tipping-addons'),
                'email_message' => $note ?: __('You have received a payout from your artist earnings.', 'paper-tipping-addons')
            ],
            'items' => [
                [
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value' => number_format($amount, 2, '.', ''),
                        'currency' => 'USD'
                    ],
                    'note' => $note,
                    'receiver' => $email,
                    'notification_language' => 'en-US'
                ]
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ],
            'body' => json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        
        if (isset($body->batch_header->payout_batch_id)) {
            return [
                'success' => true,
                'batch_id' => $body->batch_header->payout_batch_id,
                'status' => $body->batch_header->batch_status
            ];
        }

        return [
            'success' => false,
            'message' => isset($body->message) ? $body->message : __('Unknown error occurred', 'paper-tipping-addons')
        ];
    }

    public function get_payout_status($batch_id) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }

        $url = $this->is_sandbox ?
            "https://api-m.sandbox.paypal.com/v1/payments/payouts/{$batch_id}" :
            "https://api-m.paypal.com/v1/payments/payouts/{$batch_id}";

        $response = wp_remote_get($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token
            ]
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        return isset($body->batch_header->batch_status) ? $body->batch_header->batch_status : false;
    }
}

// Initialize the PayPal integration
new PayPalIntegration();
