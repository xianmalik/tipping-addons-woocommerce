<?php

namespace TippingAddonsJetEngine\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class TipWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'tip-widget';
    }

    public function get_title()
    {
        return esc_html__('Tip Widget', 'tipping-addons-jetengine');
    }

    public function get_icon()
    {
        return 'eicon-price-table';
    }

    public function get_categories()
    {
        return ['general'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'tipping-addons-jetengine'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'default_amount',
            [
                'label' => esc_html__('Default Amount', 'tipping-addons-jetengine'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 5,
                'min' => 1,
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $default_amount = $settings['default_amount'];
?>
        <div class="tip-widget-container">
            <div class="tip-amount-control">
                <button class="tip-decrease" onclick="decreaseTip(this)">-</button>
                <input type="number" class="tip-amount" value="<?php echo esc_attr($default_amount); ?>" min="1" onchange="validateTip(this)">
                <button class="tip-increase" onclick="increaseTip(this)">+</button>
            </div>
            <button class="tip-now-button" onclick="addTipToCart(this)">
                <?php echo esc_html__('Tip Now', 'tipping-addons-jetengine'); ?>
            </button>
        </div>

        <style>
            .tip-widget-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 15px;
                padding: 20px;
            }

            .tip-amount-control {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .tip-amount {
                width: 80px;
                text-align: center;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .tip-decrease,
            .tip-increase {
                padding: 8px 12px;
                background-color: #f0f0f0;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
            }

            .tip-now-button {
                padding: 10px 20px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
            }

            .tip-now-button:hover {
                background-color: #45a049;
            }
        </style>

        <script>
            // Define ajaxurl for frontend
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

            function increaseTip(button) {
                const input = button.parentElement.querySelector('.tip-amount');
                input.value = parseInt(input.value) + 1;
                validateTip(input);
            }

            function decreaseTip(button) {
                const input = button.parentElement.querySelector('.tip-amount');
                input.value = Math.max(1, parseInt(input.value) - 1);
                validateTip(input);
            }

            function validateTip(input) {
                input.value = Math.max(1, parseInt(input.value) || 1);
            }

            function addTipToCart(button) {
                const container = button.closest('.tip-widget-container');
                const amount = container.querySelector('.tip-amount').value;
                const postId = '<?php echo get_the_ID(); ?>';

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'add_tip_to_cart',
                        amount: amount,
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce("add_tip_to_cart"); ?>'
                    },
                    beforeSend: function() {
                        console.log('Sending request to:', ajaxurl);
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '<?php echo wc_get_cart_url(); ?>';
                        } else {
                            console.error('Error: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', {
                            xhr,
                            status,
                            error
                        });
                        alert('Error adding tip to cart: ' + error);
                    }
                });
            }
        </script>
<?php
    }
}
