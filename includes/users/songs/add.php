<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AddProductHandler {
    public function handle_add() {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $song_count = $this->get_artist_song_count($user_id);
        $max_songs = 5;

        if ($song_count >= $max_songs) {
            wc_add_notice(__('You have reached the maximum number of songs allowed.', 'tipping-addons-jetengine'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-products'));
            exit;
        }

        // Show the add product form
        $this->render_add_form();
    }

    private function get_artist_song_count($user_id) {
        $args = [
            'post_type' => 'product',
            'author' => $user_id,
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => -1,
        ];

        $products = get_posts($args);
        return count($products);
    }

    private function render_add_form() {
        ?>
        <div class="add-product-form">
            <h2><?php _e('Add New Song', 'tipping-addons-jetengine'); ?></h2>
            
            <form id="artist-product-form" method="post" enctype="multipart/form-data">
                <p class="form-row">
                    <label for="product_name"><?php _e('Song Title', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="text" name="product_name" id="product_name" required />
                </p>

                <p class="form-row">
                    <label for="product_description"><?php _e('Description', 'tipping-addons-jetengine'); ?></label>
                    <textarea name="product_description" id="product_description" rows="5"></textarea>
                </p>

                <p class="form-row">
                    <label for="product_price"><?php _e('Price', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="number" name="product_price" id="product_price" step="0.01" min="0" required />
                </p>

                <p class="form-row">
                    <label for="product_image"><?php _e('Song Cover Image', 'tipping-addons-jetengine'); ?></label>
                    <input type="file" name="product_image" id="product_image" accept="image/*" />
                </p>

                <p class="form-row">
                    <label for="song_preview"><?php _e('Preview Audio (30s)', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="file" name="song_preview" id="song_preview" accept="audio/*" required />
                </p>

                <p class="form-row">
                    <label for="song_mp3"><?php _e('Full Song (MP3)', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="file" name="song_mp3" id="song_mp3" accept=".mp3" required />
                </p>

                <p class="form-row">
                    <label for="song_wav"><?php _e('Full Song (WAV)', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="file" name="song_wav" id="song_wav" accept=".wav" required />
                </p>

                <p class="form-submit">
                    <input type="hidden" name="action" value="submit_artist_product" />
                    <?php wp_nonce_field('submit_artist_product'); ?>
                    <button type="submit" class="button"><?php _e('Add Song', 'tipping-addons-jetengine'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }
}