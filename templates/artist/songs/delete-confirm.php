<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $product (WC_Product)
?>
<div class="delete-product-confirmation">
    <h2><?php _e('Delete Song', 'paper-tipping-addons'); ?></h2>

    <div class="product-info">
        <div class="product-image">
            <?php echo $product->get_image('thumbnail'); ?>
        </div>
        <div class="product-details-delete">
            <h3><?php echo esc_html($product->get_name()); ?></h3>
            <p class="warning-text">
                <?php _e('Are you sure you want to delete this song? This action cannot be undone.', 'paper-tipping-addons'); ?>
            </p>
        </div>
    </div>

    <form method="post" class="delete-confirmation-form">
        <input type="hidden" name="confirm_delete" value="yes">
        <?php wp_nonce_field('delete_artist_product_' . $product->get_id()); ?>

        <div class="button-group">
            <button type="submit" class="button delete-button">
                <?php _e('Yes, Delete Song', 'paper-tipping-addons'); ?>
            </button>
            <a href="<?php echo wc_get_account_endpoint_url('manage-songs'); ?>" class="button cancel-button">
                <?php _e('No, Keep Song', 'paper-tipping-addons'); ?>
            </a>
        </div>
    </form>
</div>
