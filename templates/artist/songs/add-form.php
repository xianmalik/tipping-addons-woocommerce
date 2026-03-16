<?php
if (!defined('ABSPATH')) {
    exit;
}
// No variables expected — form is self-contained
?>
<div class="add-product-form">
    <h2><?php _e('Add New Song', 'paper-tipping-addons'); ?></h2>

    <form id="add-artist-product-form" method="post" enctype="multipart/form-data" action="">
        <div class="form-row">
            <label for="product_name"><?php _e('Song Title', 'paper-tipping-addons'); ?> <span class="required">*</span></label>
            <input type="text" name="product_name" id="product_name" required />
        </div>

        <div class="form-row">
            <label for="product_description"><?php _e('Description', 'paper-tipping-addons'); ?></label>
            <textarea name="product_description" id="product_description" rows="5"></textarea>
        </div>

        <div class="form-row">
            <label for="product_image"><?php _e('Song Cover Image', 'paper-tipping-addons'); ?></label>
            <input type="file" name="product_image" id="product_image" accept="image/*" />
        </div>

        <div class="form-row">
            <label for="product_preview"><?php _e('Preview Audio (recommend duration ~ 30s)', 'paper-tipping-addons'); ?> <span class="required">*</span></label>
            <input type="file" name="product_preview" id="product_preview" accept=".mp3,.wav,.ogg,.m4a,.aac,.flac" required />
        </div>

        <div class="form-row">
            <label for="product_mp3"><?php _e('Full Song (MP3)', 'paper-tipping-addons'); ?> <span class="required">*</span></label>
            <input type="file" name="product_mp3" id="product_mp3" accept=".mp3" required />
        </div>

        <div class="form-row">
            <label for="product_wav"><?php _e('Full Song (WAV)', 'paper-tipping-addons'); ?></label>
            <input type="file" name="product_wav" id="product_wav" accept=".wav" />
        </div>

        <div class="form-submit">
            <input type="hidden" name="action" value="submit_artist_product" />
            <?php wp_nonce_field('submit_artist_product_nonce', 'product_nonce'); ?>
            <button type="submit" class="button"><?php _e('Upload', 'paper-tipping-addons'); ?></button>
        </div>
    </form>
</div>
