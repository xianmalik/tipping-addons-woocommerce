<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $default_amount (float)
// Data attributes pass per-post context to the external JS file.
?>
<div class="tip-widget-container"
    data-post-id="<?php echo esc_attr(get_the_ID()); ?>"
    data-post-title="<?php echo esc_attr(get_the_title()); ?>"
    data-nonce="<?php echo esc_attr(wp_create_nonce('add_tip_to_cart')); ?>"
    data-ajax-url="<?php echo esc_attr(admin_url('admin-ajax.php')); ?>">

    <div class="tip-amount-control">
        <button class="tip-decrease">-</button>
        <div class="amount-wrapper">
            <span class="dollar-sign">$</span>
            <input type="text" class="tip-amount" value="<?php echo esc_attr(number_format($default_amount, 2)); ?>" min="1" step="1">
        </div>
        <button class="tip-increase">+</button>
    </div>

    <button class="tip-now-button">
        <?php echo esc_html__('Tip Now', 'paper-tipping-addons'); ?>
    </button>
</div>
