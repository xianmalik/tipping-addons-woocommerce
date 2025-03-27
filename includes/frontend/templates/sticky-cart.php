<a href="<?php echo esc_url($cart_url); ?>" class="sticky-cart-icon">
    <div class="sticky-cart-icon-wrapper">
        <img src="<?php echo esc_url($icon_url); ?>" alt="Cart">
    </div>
    <?php if ($cart_count > 0) : ?>
        <span class="cart-item-count"><?php echo esc_html($cart_count); ?></span>
    <?php endif; ?>
</a>