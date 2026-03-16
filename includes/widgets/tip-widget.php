<?php

namespace PaperTippingAddons\Widgets;

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
        return esc_html__('Tip Widget', 'paper-tipping-addons');
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
                'label' => esc_html__('Content', 'paper-tipping-addons'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'default_amount',
            [
                'label' => esc_html__('Default Amount', 'paper-tipping-addons'),
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
                <div class="amount-wrapper">
                    <span class="dollar-sign">$</span>
                    <input type="text" class="tip-amount" value="<?php echo number_format($default_amount, 2); ?>" min="1" step="1" onkeyup="validateTip(this)">
                </div>
                <button class="tip-increase" onclick="increaseTip(this)">+</button>
            </div>

            <style>
                .tip-widget-container {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 15px;
                    padding: 20px;
                }

                .tip-widget-container .tip-amount-control {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .tip-widget-container .tip-amount {
                    width: 60px;
                    text-align: left;
                    border: none;
                    padding: 4px;
                    font-size: 16px;
                    background: transparent;
                }

                .tip-widget-container .tip-decrease,
                .tip-widget-container .tip-increase {
                    padding: 8px 12px;
                    background-color: #f0f0f0;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    cursor: pointer;
                    color: #000 !important;
                }

                .tip-widget-container .tip-now-button {
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                }

                .tip-widget-container .tip-now-button:hover {
                    background-color: #45a049;
                }

                .tip-widget-container .amount-wrapper {
                    position: relative;
                    display: flex;
                    align-items: center;
                    border: 2px solid #f7941d;
                    border-radius: 4px;
                    padding: 4px 8px;
                }

                .tip-widget-container .dollar-sign {
                    font-size: 16px;
                    color: #333;
                    margin-right: 2px;
                }

                .tip-widget-container .tip-amount:focus {
                    outline: none;
                }

                /* Hide spinner buttons */
                .tip-widget-container .tip-amount::-webkit-outer-spin-button,
                .tip-widget-container .tip-amount::-webkit-inner-spin-button {
                    -webkit-appearance: none;
                    margin: 0;
                }

                .tip-widget-container .tip-amount[type=number] {
                    -moz-appearance: textfield;
                }
            </style>
            <button class="tip-now-button" onclick="addTipToCart(this)">
                <?php echo esc_html__('Tip Now', 'paper-tipping-addons'); ?>
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
                input.value = parseFloat(input.value) + 1;
                validateTip(input);
            }

            function decreaseTip(button) {
                const input = button.parentElement.querySelector('.tip-amount');
                input.value = Math.max(1, parseFloat(input.value) - 1);
                validateTip(input);
            }

            const validateTip = i => {
                console.log({
                    i
                });
                let v = i.value;
                const ss = i.selectionStart;
                const resetCursor = () => i.setSelectionRange(ss, ss);
                if (/^[0]*.00$/.test(v)) {
                    i.value = '';
                } else if (/^[0-9.]+$/.test(v)) {
                    let p = v.indexOf('..');
                    if (p >= 0) {
                        i.value = v.replace('..', '.');
                        resetCursor();
                        process(i);
                    } else if ([...v].filter(c => c === '.').length > 1) {
                        let j = v.indexOf('.');
                        i.value = [...v].filter((c, k) => k <= j || c !== '.').join('');
                        resetCursor();
                        process(i);
                    } else {
                        i.value = (+v).toFixed(2);
                        resetCursor();
                    }
                } else {
                    v = v.replace(/[^0-9.]/g, '');
                    i.value = v;
                    resetCursor();
                }
            }

            function addTipToCart(button) {
                const container = button.closest('.tip-widget-container');
                const amount = container.querySelector('.tip-amount').value;
                const postId = '<?php echo get_the_ID(); ?>';
                const pageTitle = '<?php echo esc_js(get_the_title()); ?>';
                const featuredImageId = '<?php echo get_post_thumbnail_id(); ?>';

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'add_tip_to_cart',
                        amount: amount,
                        post_id: postId,
                        page_title: pageTitle,
                        featured_image_id: featuredImageId,
                        nonce: '<?php echo wp_create_nonce("add_tip_to_cart"); ?>'
                    },
                    beforeSend: function() {
                        console.log('Sending request to:', ajaxurl);
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update cart count
                            const cartIcon = document.querySelector('.cart-icon-wrapper');
                            const cartCount = document.querySelector('.cart-item-count');
                            const newCount = parseInt(cartCount?.textContent || '0') + 1;

                            // Update or create count element
                            if (cartCount) {
                                cartCount.textContent = newCount;
                            } else {
                                const newCountElement = document.createElement('span');
                                newCountElement.className = 'cart-item-count';
                                newCountElement.textContent = '1';
                                cartIcon.appendChild(newCountElement);
                            }

                            // Add bounce animation
                            cartIcon.classList.add('bounce');
                            setTimeout(() => {
                                cartIcon.classList.remove('bounce');
                            }, 1000);
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

        <style>
            /* Add bounce animation */
            @keyframes bounce {

                0%,
                20%,
                50%,
                80%,
                100% {
                    transform: translateY(0);
                }

                40% {
                    transform: translateY(-20px);
                }

                60% {
                    transform: translateY(-10px);
                }
            }

            .bounce {
                animation: bounce 1s ease;
            }
        </style>
<?php
    }
}
