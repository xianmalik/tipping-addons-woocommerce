<?php
if (!defined('ABSPATH')) {
    exit;
}

class EditProductHandler
{
    public function handle_edit()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $product_id   = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $product      = wc_get_product($product_id);
        $product_post = get_post($product_id);

        if (!$product || $product_post->post_author != get_current_user_id()) {
            wc_add_notice(__('You do not have permission to edit this product.', 'paper-tipping-addons'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-songs'));
            exit;
        }

        include PAPER_TIPPING_PATH . 'templates/artist/songs/edit-form.php';
    }

    public function process_product_update()
    {
        if (
            !isset($_POST['product_nonce']) ||
            !wp_verify_nonce($_POST['product_nonce'], 'update_artist_product_nonce') ||
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'paper-tipping-addons')]);
        }

        $product_id   = intval($_POST['product_id']);
        $product      = wc_get_product($product_id);
        $product_post = get_post($product_id);

        if (!$product || $product_post->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => __('You do not have permission to edit this product', 'paper-tipping-addons')]);
        }

        $name        = sanitize_text_field($_POST['product_name'] ?? '');
        $description = wp_kses_post($_POST['product_description'] ?? '');

        if (empty($name)) {
            wp_send_json_error(['message' => __('Please fill all required fields with valid values', 'paper-tipping-addons')]);
        }

        $product->set_name($name);
        $product->set_description($description);
        $product->set_status('pending');

        $downloads = $product->get_downloads();

        if (!empty($_FILES['product_preview']['name'])) {
            $preview_id = UploadHandler::upload_audio('product_preview', $product_id);
            if (is_wp_error($preview_id)) {
                wp_send_json_error(['message' => $preview_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_preview', $preview_id);
        }

        if (!empty($_FILES['product_mp3']['name'])) {
            $mp3_id = UploadHandler::upload_audio('product_mp3', $product_id);
            if (is_wp_error($mp3_id)) {
                wp_send_json_error(['message' => $mp3_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_mp3', $mp3_id);
            $mp3_url              = wp_get_attachment_url($mp3_id);
            $downloads[md5($mp3_url)] = ['id' => md5($mp3_url), 'name' => 'MP3 Version', 'file' => $mp3_url];
        }

        if (!empty($_FILES['product_wav']['name'])) {
            $wav_id = UploadHandler::upload_audio('product_wav', $product_id);
            if (is_wp_error($wav_id)) {
                wp_send_json_error(['message' => $wav_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_wav', $wav_id);
            $wav_url              = wp_get_attachment_url($wav_id);
            $downloads[md5($wav_url)] = ['id' => md5($wav_url), 'name' => 'WAV Version', 'file' => $wav_url];
        }

        if (!empty($downloads)) {
            $product->set_downloads($downloads);
        }

        if (!empty($_FILES['product_image']['name'])) {
            $image_id = UploadHandler::upload_image('product_image', $product_id);
            if (!is_wp_error($image_id)) {
                $product->set_image_id($image_id);
            }
        }

        $product->save();

        wp_send_json_success([
            'message'  => __('Product updated successfully! It will be reviewed by an admin before publishing.', 'paper-tipping-addons'),
            'redirect' => wc_get_account_endpoint_url('manage-songs'),
        ]);
    }
}
