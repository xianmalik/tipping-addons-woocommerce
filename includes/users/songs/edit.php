<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class EditProductHandler
{
    public function handle_edit()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $product = wc_get_product($product_id);
        $product_post = get_post($product_id);

        if (!$product || $product_post->post_author != get_current_user_id()) {
            wc_add_notice(__('You do not have permission to edit this product.', 'tipping-addons-jetengine'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-songs'));
            exit;
        }

        // Show the edit form
        $this->render_edit_form($product);
    }

    private function render_edit_form($product)
    {
?>
        <div class="edit-product-form">
            <h2><?php _e('Edit Song', 'tipping-addons-jetengine'); ?></h2>

            <form id="edit-artist-product-form" method="post" enctype="multipart/form-data">
                <p class="form-row">
                    <label for="product_name"><?php _e('Song Title', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="text" name="product_name" id="product_name" value="<?php echo esc_attr($product->get_name()); ?>" required />
                </p>

                <p class="form-row">
                    <label for="product_description"><?php _e('Description', 'tipping-addons-jetengine'); ?></label>
                    <textarea name="product_description" id="product_description" rows="5"><?php echo esc_textarea($product->get_description()); ?></textarea>
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
                <label for="song_preview"><?php _e('Preview Audio (recommend duration ~ 30s)', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                <?php if (get_post_meta($product->get_id(), 'song_preview', true)): ?>
            <div class="current-file">
                <span class="file-name"><?php _e('Current preview file', 'tipping-addons-jetengine'); ?></span>
                <span class="remove-file"><?php _e('Replace', 'tipping-addons-jetengine'); ?></span>
            </div>
        <?php endif; ?>
        <input type="file" name="song_preview" id="song_preview" accept=".mp3,.wav,.ogg,.m4a,.aac,.flac" />
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

    public function process_product_update()
    {
        // Verify nonce and check if user is logged in
        if (
            !isset($_POST['product_nonce']) ||
            !wp_verify_nonce($_POST['product_nonce'], 'update_artist_product_nonce') ||
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'tipping-addons-jetengine')]);
        }

        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);
        $product_post = get_post($product_id);

        if (!$product || $product_post->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => __('You do not have permission to edit this product', 'tipping-addons-jetengine')]);
        }

        // Update basic product data
        $name = sanitize_text_field($_POST['product_name']);
        $description = wp_kses_post($_POST['product_description']);

        if (empty($name) || empty($description)) {
            wp_send_json_error(['message' => __('Please fill all required fields with valid values', 'tipping-addons-jetengine')]);
        }

        $product->set_name($name);
        $product->set_description($description);
        $product->set_status('pending'); // Set to pending for admin review

        // Get existing downloads
        $downloads = $product->get_downloads();

        // Handle Music Preview update
        if (!empty($_FILES['product_preview']['name'])) {
            $preview_id = $this->upload_product_file('product_preview', $product_id);
            if (is_wp_error($preview_id)) {
                wp_send_json_error(['message' => $preview_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_preview', $preview_id);
        }

        // Handle MP3 file update
        if (!empty($_FILES['product_mp3']['name'])) {
            $mp3_id = $this->upload_product_file('product_mp3', $product_id);
            if (is_wp_error($mp3_id)) {
                wp_send_json_error(['message' => $mp3_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_mp3', $mp3_id);

            $mp3_url = wp_get_attachment_url($mp3_id);
            $downloads[md5($mp3_url)] = [
                'id' => md5($mp3_url),
                'name' => 'MP3 Version',
                'file' => $mp3_url
            ];
        }

        // Handle WAV file update
        if (!empty($_FILES['product_wav']['name'])) {
            $wav_id = $this->upload_product_file('product_wav', $product_id);
            if (is_wp_error($wav_id)) {
                wp_send_json_error(['message' => $wav_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_wav', $wav_id);

            $wav_url = wp_get_attachment_url($wav_id);
            $downloads[md5($wav_url)] = [
                'id' => md5($wav_url),
                'name' => 'WAV Version',
                'file' => $wav_url
            ];
        }

        // Update downloadable files
        if (!empty($downloads)) {
            $product->set_downloads($downloads);
        }

        // Handle product image update
        if (!empty($_FILES['product_image']['name'])) {
            $image_id = $this->upload_product_image('product_image', $product_id);
            if (!is_wp_error($image_id)) {
                $product->set_image_id($image_id);
            }
        }

        // Save all changes
        $product->save();

        wp_send_json_success([
            'message' => __('Product updated successfully! It will be reviewed by an admin before publishing.', 'tipping-addons-jetengine'),
            'redirect' => wc_get_account_endpoint_url('manage-songs')
        ]);
    }
}
