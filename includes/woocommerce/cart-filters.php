<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Restore custom tip price from session.
 */
add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values) {
    if (isset($values['custom_price'])) {
        $cart_item['data']->set_price($values['custom_price']);
    }
    return $cart_item;
}, 10, 2);

/**
 * Display custom price in cart.
 */
add_filter('woocommerce_cart_item_price', function ($price, $cart_item) {
    if (isset($cart_item['custom_price'])) {
        return wc_price($cart_item['custom_price']);
    }
    return $price;
}, 10, 3);

/**
 * Apply custom price when recalculating cart totals.
 */
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['custom_price'])) {
            $cart_item['data']->set_price($cart_item['custom_price']);
        }
    }
}, 10, 1);

/**
 * Show "Includes $X tip" label on cart item name.
 */
add_filter('woocommerce_cart_item_name', function ($name, $cart_item) {
    if (isset($cart_item['tip_amount']) && $cart_item['tip_amount'] > 0) {
        return $name . ' <span class="tip-amount-display">('
            . sprintf(__('Includes $%s tip', 'paper-tipping-addons'), number_format($cart_item['tip_amount'], 2))
            . ')</span>';
    }
    return $name;
}, 10, 3);

/**
 * Save tip amount and post metadata to the order line item.
 */
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
    if (isset($values['tip_amount']) && $values['tip_amount'] > 0) {
        $item->add_meta_data(__('Tip Amount', 'paper-tipping-addons'), wc_price($values['tip_amount']));
        $item->add_meta_data('_tip_amount', $values['tip_amount'], true);
    }

    if (isset($values['post_title'])) {
        $item->add_meta_data('post_title', $values['post_title']);
    }
    if (isset($values['post_id'])) {
        $item->add_meta_data('post_id', $values['post_id']);
    }
}, 10, 4);

/**
 * Show "Tip for: <song>" in order details and emails.
 */
add_filter('woocommerce_order_item_name', function ($name, $item) {
    if ($item->get_meta('post_title')) {
        return sprintf(__('Tip for: %s', 'paper-tipping-addons'), $item->get_meta('post_title'));
    }
    return $name;
}, 10, 2);

/**
 * Hide internal post_id meta from order details display.
 */
add_filter('woocommerce_order_item_get_formatted_meta_data', function ($formatted_meta) {
    foreach ($formatted_meta as $key => $meta) {
        if ($meta->key === 'post_id') {
            unset($formatted_meta[$key]);
        }
    }
    return $formatted_meta;
}, 10, 2);
