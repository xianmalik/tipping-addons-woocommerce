<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $cart_count (int), $cart_url (string), $icon_url (string)
?>
<a href="<?php echo esc_url($cart_url); ?>" class="cart-icon-link">
    <div class="cart-icon-wrapper" id="cart-icon-wrapper" style="max-width: 32px;">
        <img src="<?php echo esc_url($icon_url); ?>" alt="<?php esc_attr_e('Cart', 'paper-tipping-addons'); ?>">
        <span class="cart-item-count" id="cart-item-count"><?php echo esc_html($cart_count); ?></span>
    </div>
</a>
