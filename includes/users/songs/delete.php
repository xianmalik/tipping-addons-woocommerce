<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class DeleteProductHandler {
    public function handle_delete() {
        if (!is_user_logged_in()) {
            return;
        }

        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

        if (!wp_verify_nonce($nonce, 'delete_artist_product_' . $product_id)) {
            wc_add_notice(__('Security check failed.', 'tipping-addons-jetengine'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-products'));
            exit;
        }

        $product = wc_get_product($product_id);
        $product_post = get_post($product_id);

        if (!$product || $product_post->post_author != get_current_user_id()) {
            wc_add_notice(__('You do not have permission to delete this product.', 'tipping-addons-jetengine'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-products'));
            exit;
        }

        // If form is submitted
        if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            // Delete product files
            $file_types = ['song_preview', 'song_mp3', 'song_wav'];
            foreach ($file_types as $type) {
                $file_id = get_post_meta($product_id, $type, true);
                if ($file_id) {
                    wp_delete_attachment($file_id, true);
                }
            }

            // Delete product thumbnail if exists
            $thumbnail_id = get_post_thumbnail_id($product_id);
            if ($thumbnail_id) {
                wp_delete_attachment($thumbnail_id, true);
            }

            // Delete the product
            wp_delete_post($product_id, true);

            wc_add_notice(__('Product deleted successfully.', 'tipping-addons-jetengine'), 'success');
            wp_redirect(wc_get_account_endpoint_url('manage-products'));
            exit;
        }

        // Show confirmation page
        $this->render_delete_confirmation($product);
    }

    private function render_delete_confirmation($product) {
        ?>
        <div class="delete-product-confirmation">
            <h2><?php _e('Delete Song', 'tipping-addons-jetengine'); ?></h2>
            
            <div class="product-info">
                <div class="product-image">
                    <?php echo $product->get_image('thumbnail'); ?>
                </div>
                <div class="product-details">
                    <h3><?php echo esc_html($product->get_name()); ?></h3>
                    <p class="warning-text">
                        <?php _e('Are you sure you want to delete this song? This action cannot be undone.', 'tipping-addons-jetengine'); ?>
                    </p>
                </div>
            </div>

            <form method="post" class="delete-confirmation-form">
                <input type="hidden" name="confirm_delete" value="yes">
                <?php wp_nonce_field('delete_artist_product_' . $product->get_id()); ?>
                
                <div class="button-group">
                    <button type="submit" class="button delete-button">
                        <?php _e('Yes, Delete Song', 'tipping-addons-jetengine'); ?>
                    </button>
                    <a href="<?php echo wc_get_account_endpoint_url('manage-products'); ?>" class="button cancel-button">
                        <?php _e('No, Keep Song', 'tipping-addons-jetengine'); ?>
                    </a>
                </div>
            </form>
        </div>

        <style>
            .delete-product-confirmation {
                max-width: 500px;
                margin: 0 auto;
                padding: 20px;
            }

            .product-info {
                display: flex;
                align-items: center;
                gap: 20px;
                margin: 20px 0;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 4px;
            }

            .product-image img {
                width: 80px;
                height: 80px;
                object-fit: cover;
                border-radius: 4px;
            }

            .warning-text {
                color: #dc3545;
                margin-top: 10px;
            }

            .button-group {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }

            .delete-button {
                background: #dc3545 !important;
                color: white !important;
            }

            .delete-button:hover {
                background: #bd2130 !important;
            }

            .cancel-button {
                background: #6c757d !important;
                color: white !important;
            }

            .cancel-button:hover {
                background: #5a6268 !important;
            }
        </style>
        <?php
    }
}
?>