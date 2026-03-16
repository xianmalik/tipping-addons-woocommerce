<?php
if (!defined('ABSPATH')) {
    exit;
}
// Expected: $product (WC_Product)
$product_id = $product->get_id();
$preview_id = get_post_meta($product_id, 'song_preview', true);
$mp3_id     = get_post_meta($product_id, 'song_mp3', true);
$wav_id     = get_post_meta($product_id, 'song_wav', true);
?>
<div class="edit-product-form">
    <h2><?php _e('Edit Song', 'paper-tipping-addons'); ?></h2>

    <form id="edit-artist-product-form" method="post" enctype="multipart/form-data">
        <div class="form-message"></div>

        <div class="form-row">
            <label for="product_name"><?php _e('Song Title', 'paper-tipping-addons'); ?> <span class="required">*</span></label>
            <input type="text" name="product_name" id="product_name" value="<?php echo esc_attr($product->get_name()); ?>" required />
        </div>

        <div class="form-row">
            <label for="product_description"><?php _e('Description', 'paper-tipping-addons'); ?></label>
            <textarea name="product_description" id="product_description" rows="5"><?php echo esc_textarea($product->get_description()); ?></textarea>
        </div>

        <div class="form-row">
            <label for="product_image"><?php _e('Song Cover Image', 'paper-tipping-addons'); ?></label>
            <?php if ($product->get_image_id()) : ?>
                <div class="current-image"><?php echo $product->get_image('thumbnail'); ?></div>
            <?php endif; ?>
            <input type="file" name="product_image" id="product_image" accept="image/*" />
        </div>

        <div class="form-row">
            <label for="product_preview"><?php _e('Preview Audio (recommend duration ~ 30s)', 'paper-tipping-addons'); ?></label>
            <?php if ($preview_id) :
                $preview_filename = basename(get_attached_file($preview_id) ?: '') ?: __('Preview file', 'paper-tipping-addons'); ?>
                <div class="current-file"><span class="file-name"><?php echo esc_html($preview_filename); ?></span></div>
            <?php endif; ?>
            <input type="file" name="product_preview" id="product_preview" accept=".mp3,.wav,.ogg,.m4a,.aac,.flac" />
        </div>

        <div class="form-row">
            <label for="product_mp3"><?php _e('Full Song (MP3)', 'paper-tipping-addons'); ?></label>
            <?php if ($mp3_id) :
                $mp3_filename = basename(get_attached_file($mp3_id) ?: '') ?: __('MP3 file', 'paper-tipping-addons'); ?>
                <div class="current-file"><span class="file-name"><?php echo esc_html($mp3_filename); ?></span></div>
            <?php endif; ?>
            <input type="file" name="product_mp3" id="product_mp3" accept=".mp3" />
        </div>

        <div class="form-row">
            <label for="product_wav"><?php _e('Full Song (WAV)', 'paper-tipping-addons'); ?></label>
            <?php if ($wav_id) :
                $wav_filename = basename(get_attached_file($wav_id) ?: '') ?: __('WAV file', 'paper-tipping-addons'); ?>
                <div class="current-file"><span class="file-name"><?php echo esc_html($wav_filename); ?></span></div>
            <?php endif; ?>
            <input type="file" name="product_wav" id="product_wav" accept=".wav" />
        </div>

        <div class="form-submit">
            <input type="hidden" name="action" value="update_artist_product" />
            <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>" />
            <?php wp_nonce_field('update_artist_product_nonce', 'product_nonce'); ?>
            <button type="submit" class="button"><?php _e('Update Song', 'paper-tipping-addons'); ?></button>
        </div>
    </form>
</div>
