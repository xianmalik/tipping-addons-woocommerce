<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AddProductHandler
{
    public function handle_add()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $song_count = $this->get_artist_song_count($user_id);
        $max_songs = 5;

        if ($song_count >= $max_songs) {
            wc_add_notice(__('You have reached the maximum number of songs allowed.', 'tipping-addons-jetengine'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-songs'));
            exit;
        }

        // Show the add product form
        $this->render_add_form();
    }

    private function get_artist_song_count($user_id)
    {
        $args = [
            'post_type' => 'product',
            'author' => $user_id,
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
        ];

        $products = get_posts($args);
        return count($products);
    }

    private function render_add_form()
    {
?>
        <div class="add-product-form">
            <h2><?php _e('Add New Song', 'tipping-addons-jetengine'); ?></h2>

            <form id="add-artist-product-form" method="post" enctype="multipart/form-data">
                <p class="form-row">
                    <label for="product_name"><?php _e('Song Title', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="text" name="product_name" id="product_name" required />
                </p>

                <p class="form-row">
                    <label for="product_description"><?php _e('Description', 'tipping-addons-jetengine'); ?></label>
                    <textarea name="product_description" id="product_description" rows="5"></textarea>
                </p>

                <p class="form-row">
                    <label for="product_image"><?php _e('Song Cover Image', 'tipping-addons-jetengine'); ?></label>
                    <input type="file" name="product_image" id="product_image" accept="image/*" />
                </p>

                <p class="form-row">
                    <label for="song_preview"><?php _e('Preview Audio (recommend duration ~ 30s)', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="file" name="song_preview" id="song_preview" accept="audio/*" required />
                </p>

                <p class="form-row">
                    <label for="song_mp3"><?php _e('Full Song (MP3)', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="file" name="song_mp3" id="song_mp3" accept=".mp3" required />
                </p>

                <p class="form-row">
                    <label for="song_wav"><?php _e('Full Song (WAV)', 'tipping-addons-jetengine'); ?>
                        <input type="file" name="song_wav" id="song_wav" accept=".wav" />
                </p>

                <p class="form-submit">
                    <input type="hidden" name="action" value="submit_artist_product" />
                    <?php wp_nonce_field('submit_artist_product_nonce', 'product_nonce'); ?>
                    <button type="submit" class="button"><?php _e('Upload', 'tipping-addons-jetengine'); ?></button>
                </p>
            </form>
        </div>
<?php
    }

    public function process_product_submission()
    {
        // Verify nonce and check if user is logged in
        if (
            !isset($_POST['product_nonce']) ||
            !wp_verify_nonce($_POST['product_nonce'], 'submit_artist_product_nonce') ||
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'tipping-addons-jetengine')]);
        }

        $user_id = get_current_user_id();
        $song_count = $this->get_artist_song_count($user_id);
        $max_songs = 5;

        // Check if artist has reached the song limit
        if ($song_count >= $max_songs) {
            wp_send_json_error([
                'message' => __('You have reached the maximum limit of 5 songs.', 'tipping-addons-jetengine')
            ]);
        }

        // Validate required fields
        $name = sanitize_text_field($_POST['product_name']);
        $description = wp_kses_post($_POST['product_description']);

        if (empty($name) || empty($description)) {
            wp_send_json_error(['message' => __('Please fill all required fields with valid values', 'tipping-addons-jetengine')]);
        }

        // Create product
        $product = new WC_Product_Simple();
        $product->set_name($name);
        $product->set_description($description);
        $product->set_status('pending');
        $product->set_catalog_visibility('visible');
        $product->set_downloadable(true);
        $product->set_virtual(true);
        $product->set_download_limit(-1);
        $product->set_download_expiry(-1);

        // Set the current user as the author
        $product->set_props([
            'author' => get_current_user_id()
        ]);

        // Save the product to get an ID
        $product_id = $product->save();

        // Upload product image if provided
        if (!empty($_FILES['product_image']['name'])) {
            $image_id = $this->upload_product_image('product_image', $product_id);
            if (!is_wp_error($image_id)) {
                $product->set_image_id($image_id);
                $product->save();
            }
        }

        // Handle downloadable files using WooCommerce's functions
        $downloads = [];

        // Upload MP3
        if (!empty($_FILES['product_mp3']['name'])) {
            $mp3_id = $this->upload_product_file('product_mp3', $product_id);
            if (is_wp_error($mp3_id)) {
                wp_send_json_error(['message' => $mp3_id->get_error_message()]);
            }

            // Save as product meta
            update_post_meta($product_id, 'song_mp3', $mp3_id);

            // Get the file URL
            $mp3_url = wp_get_attachment_url($mp3_id);

            // Add to downloadable files using URL
            $downloads[md5($mp3_url)] = [
                'id' => md5($mp3_url),
                'name' => 'MP3 Version',
                'file' => $mp3_url
            ];
        }

        // Upload WAV if provided
        if (!empty($_FILES['product_wav']['name'])) {
            $wav_id = $this->upload_product_file('product_wav', $product_id);
            if (is_wp_error($wav_id)) {
                wp_send_json_error(['message' => $wav_id->get_error_message()]);
            }

            // Save as product meta
            update_post_meta($product_id, 'song_wav', $wav_id);

            // Get the file URL
            $wav_url = wp_get_attachment_url($wav_id);

            // Add to downloadable files using URL
            $downloads[md5($wav_url)] = [
                'id' => md5($wav_url),
                'name' => 'WAV Version',
                'file' => $wav_url
            ];
        }

        // Save downloadable files
        if (!empty($downloads)) {
            update_post_meta($product_id, '_downloadable_files', $downloads);
        }

        wp_send_json_success([
            'message' => __('Product added successfully! It will be reviewed by an admin before publishing.', 'tipping-addons-jetengine'),
            'redirect' => wc_get_account_endpoint_url('manage-songs')
        ]);
    }

    private function upload_product_image($file_key, $product_id)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return new WP_Error('invalid_product', __('Invalid product', 'tipping-addons-jetengine'));
        }

        $image_id = $product->upload_image($_FILES[$file_key]['tmp_name']);
        if (is_wp_error($image_id)) {
            return $image_id;
        }

        return $image_id;
    }

    private function upload_product_file($file_key, $product_id)
    {
        if (!function_exists('media_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
        }

        $attachment_id = media_handle_upload($file_key, $product_id);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        return $attachment_id;
    }
}
