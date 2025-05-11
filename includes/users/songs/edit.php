<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class EditProductHandler {
    public function handle_edit() {
        if (!is_user_logged_in()) {
            return;
        }

        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $product = wc_get_product($product_id);
        $product_post = get_post($product_id);

        if (!$product || $product_post->post_author != get_current_user_id()) {
            wc_add_notice(__('You do not have permission to edit this product.', 'tipping-addons-jetengine'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-products'));
            exit;
        }

        // Show the edit form
        $this->render_edit_form($product);
    }

    private function render_edit_form($product) {
        ?>
        <div class="edit-product-form">
            <h2><?php _e('Edit Song', 'tipping-addons-jetengine'); ?></h2>
            
            <form id="artist-product-form" method="post" enctype="multipart/form-data">
                <p class="form-row">
                    <label for="product_name"><?php _e('Song Title', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="text" name="product_name" id="product_name" value="<?php echo esc_attr($product->get_name()); ?>" required />
                </p>

                <p class="form-row">
                    <label for="product_description"><?php _e('Description', 'tipping-addons-jetengine'); ?></label>
                    <textarea name="product_description" id="product_description" rows="5"><?php echo esc_textarea($product->get_description()); ?></textarea>
                </p>

                <p class="form-row">
                    <label for="product_price"><?php _e('Price', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="number" name="product_price" id="product_price" step="0.01" min="0" value="<?php echo esc_attr($product->get_regular_price()); ?>" required />
                </p>

                <p class="form-row">
                    <label for="product_image"><?php _e('Song Cover Image', 'tipping-addons-jetengine'); ?></label>
                    <?php if ($product->get_image_id()): ?>
                        <div class="current-image">
                            <?php echo $product->get_image('thumbnail'); ?>
                            <span class="remove-image"><?php _e('Remove', 'tipping-addons-jetengine'); ?></span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="product_image" id="product_image" accept="image/*" />
                </p>

                <p class="form-row">
                    <label for="song_preview"><?php _e('Preview Audio (30s)', 'tipping-addons-jetengine'); ?></label>
                    <?php if (get_post_meta($product->get_id(), 'song_preview', true)): ?>
                        <div class="current-file">
                            <span class="file-name"><?php _e('Current preview file', 'tipping-addons-jetengine'); ?></span>
                            <span class="remove-file"><?php _e('Replace', 'tipping-addons-jetengine'); ?></span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="song_preview" id="song_preview" accept="audio/*" />
                </p>

                <p class="form-row">
                    <label for="song_mp3"><?php _e('Full Song (MP3)', 'tipping-addons-jetengine'); ?></label>
                    <?php if (get_post_meta($product->get_id(), 'song_mp3', true)): ?>
                        <div class="current-file">
                            <span class="file-name"><?php _e('Current MP3 file', 'tipping-addons-jetengine'); ?></span>
                            <span class="remove-file"><?php _e('Replace', 'tipping-addons-jetengine'); ?></span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="song_mp3" id="song_mp3" accept=".mp3" />
                </p>

                <p class="form-row">
                    <label for="song_wav"><?php _e('Full Song (WAV)', 'tipping-addons-jetengine'); ?></label>
                    <?php if (get_post_meta($product->get_id(), 'song_wav', true)): ?>
                        <div class="current-file">
                            <span class="file-name"><?php _e('Current WAV file', 'tipping-addons-jetengine'); ?></span>
                            <span class="remove-file"><?php _e('Replace', 'tipping-addons-jetengine'); ?></span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="song_wav" id="song_wav" accept=".wav" />
                </p>

                <p class="form-submit">
                    <input type="hidden" name="action" value="update_artist_product" />
                    <input type="hidden" name="product_id" value="<?php echo esc_attr($product->get_id()); ?>" />
                    <?php wp_nonce_field('update_artist_product'); ?>
                    <button type="submit" class="button"><?php _e('Update Song', 'tipping-addons-jetengine'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }
}