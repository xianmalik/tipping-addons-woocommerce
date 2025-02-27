<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

/**
 * Modify the product price for tips in cart
 */
add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values) {
  if (isset($values['custom_price'])) {
    $cart_item['data']->set_price($values['custom_price']);
  }
  return $cart_item;
}, 10, 2);

/**
 * Display custom price in cart
 */
add_filter('woocommerce_cart_item_price', function ($price, $cart_item, $cart_item_key) {
  if (isset($cart_item['custom_price'])) {
    return wc_price($cart_item['custom_price']);
  }
  return $price;
}, 10, 3);

/**
 * Ensure the custom price is used throughout the order
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
 * Remove tip product from cart after successful order
 */
add_action('woocommerce_thankyou', function ($order_id) {
  $tip_product_id = get_option('tipping_product_id');
  if (!$tip_product_id) return;

  // Clear cart after successful order
  WC()->cart->remove_cart_item_by_id($tip_product_id);
  WC()->cart->empty_cart();
});

/**
 * Remove tip product when checkout fails
 */
add_action('woocommerce_checkout_order_failed', function () {
  $tip_product_id = get_option('tipping_product_id');
  if (!$tip_product_id) return;

  // Remove tip product from cart
  foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
    if ($cart_item['product_id'] == $tip_product_id) {
      WC()->cart->remove_cart_item($cart_item_key);
    }
  }
});

/**
 * Clear cart when order is cancelled
 */
add_action('woocommerce_cancelled_order', function ($order_id) {
  $tip_product_id = get_option('tipping_product_id');
  if (!$tip_product_id) return;

  // Remove tip product from cart
  foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
    if ($cart_item['product_id'] == $tip_product_id) {
      WC()->cart->remove_cart_item($cart_item_key);
    }
  }
});

// Add filter to modify the product title in cart
add_filter('woocommerce_cart_item_name', function ($name, $cart_item, $cart_item_key) {
  if (isset($cart_item['post_title'])) {
    return sprintf(
      __('Tip for: %s', 'tipping-addons-jetengine'),
      $cart_item['post_title']
    );
  }
  return $name;
}, 10, 3);

// Add filter for checkout page product title
add_filter('woocommerce_order_item_name', function ($name, $item) {
  if ($item->get_meta('post_title')) {
    return sprintf(
      __('Tip for: %s', 'tipping-addons-jetengine'),
      $item->get_meta('post_title')
    );
  }
  return $name;
}, 10, 2);

// Ensure post data is saved in order
add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
  if (isset($values['post_title'])) {
    $item->add_meta_data('post_title', $values['post_title']);
  }
  if (isset($values['post_id'])) {
    $item->add_meta_data('post_id', $values['post_id']);
  }
}, 10, 4);

// Add filter for order details and emails
add_filter('woocommerce_order_item_get_formatted_meta_data', function ($formatted_meta, $item) {
  foreach ($formatted_meta as $key => $meta) {
    if ($meta->key === 'post_id') {
      unset($formatted_meta[$key]);
    }
  }
  return $formatted_meta;
}, 10, 2);
