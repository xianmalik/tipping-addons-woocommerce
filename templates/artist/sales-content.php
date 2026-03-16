<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $tips_data (array), $total_sales (float)
?>
<div class="tips-summary">
    <p class="total-tips"><?php printf(__('Total Tips: %s', 'paper-tipping-addons'), wc_price($total_sales)); ?></p>

    <?php
    $withdrawal = ArtistWithdrawal::get_instance();
    $withdrawal->withdrawal_content();
    ?>
</div>

<div class="sales-list">
    <?php foreach ($tips_data as $product_id => $tip) : ?>
        <div class="sale-item">
            <div class="sale-details">
                <div class="sale-main">
                    <h3><?php echo esc_html($tip['name']); ?></h3>
                    <span class="sale-date"><?php echo date('j M Y'); ?></span>
                </div>
                <div class="sale-meta">
                    <span class="sale-amount">
                        <span class="sale-currency"><?php echo get_woocommerce_currency_symbol(); ?></span>
                        <?php echo number_format($tip['total'], 2); ?>
                    </span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
