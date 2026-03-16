<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $products (WP_Post[]), $song_count (int), $max_songs (int)
?>
<div class="product-management-header">
    <h2><?php _e('Products', 'paper-tipping-addons'); ?></h2>
    <?php if ($song_count < $max_songs) : ?>
        <a href="<?php echo wc_get_account_endpoint_url('add-song'); ?>" class="see-all">
            <?php _e('Add New', 'paper-tipping-addons'); ?>
        </a>
    <?php endif; ?>
</div>

<div class="song-limit-info">
    <p><?php printf(__('Songs: %d of %d created', 'paper-tipping-addons'), $song_count, $max_songs); ?></p>
    <p><?php _e('Add your 5 best songs to your profile', 'paper-tipping-addons'); ?></p>
</div>

<?php if (!empty($products)) : ?>
    <div class="product-list">
        <?php foreach ($products as $post) :
            $wc_product   = wc_get_product($post->ID);
            $status_class = $post->post_status === 'publish' ? 'status-live' : 'status-pending';
        ?>
            <div class="product-item">
                <div class="product-icon">
                    <?php echo $wc_product->get_image('thumbnail'); ?>
                </div>
                <div class="product-details">
                    <div class="product-main">
                        <h3><?php echo esc_html($post->post_title); ?></h3>
                        <span class="product-date"><?php echo get_the_date('j M Y', $post->ID); ?></span>
                    </div>
                    <div class="product-meta">
                        <span class="product-status <?php echo $status_class; ?>">
                            <?php echo $post->post_status === 'publish' ? __('Published', 'paper-tipping-addons') : __('Pending', 'paper-tipping-addons'); ?>
                        </span>
                        <span class="product-price">
                            <?php echo $wc_product->get_price_html(); ?>
                        </span>
                        <a href="<?php echo add_query_arg('product_id', $post->ID, wc_get_account_endpoint_url('edit-song')); ?>"
                            class="edit-button">
                            <?php _e('Edit', 'paper-tipping-addons'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(
                            add_query_arg(['product_id' => $post->ID], wc_get_account_endpoint_url('delete-song')),
                            'delete_artist_product_' . $post->ID
                        ); ?>" class="delete-button">
                            <?php _e('Delete', 'paper-tipping-addons'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <p><?php _e("You haven't uploaded any songs yet.", 'paper-tipping-addons'); ?></p>
<?php endif; ?>
