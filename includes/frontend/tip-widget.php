<?php

namespace PaperTippingAddons\Widgets;

if (!defined('ABSPATH')) {
    exit;
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

    public function get_style_depends()
    {
        return ['tip-widget-styles'];
    }

    public function get_script_depends()
    {
        return ['tip-widget-script'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'paper-tipping-addons'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'default_amount',
            [
                'label'   => esc_html__('Default Amount', 'paper-tipping-addons'),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'default' => 5,
                'min'     => 1,
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings       = $this->get_settings_for_display();
        $default_amount = $settings['default_amount'];

        include PAPER_TIPPING_PATH . 'templates/frontend/tip-widget.php';
    }
}

/**
 * Register tip-widget assets so Elementor can load them on demand.
 */
add_action('wp_enqueue_scripts', function () {
    wp_register_style(
        'tip-widget-styles',
        PAPER_TIPPING_URL . 'assets/css/frontend/tip-widget.css',
        [],
        '1.0.0'
    );

    wp_register_script(
        'tip-widget-script',
        PAPER_TIPPING_URL . 'assets/js/frontend/tip-widget.js',
        ['jquery'],
        '1.0.0',
        true
    );
});
