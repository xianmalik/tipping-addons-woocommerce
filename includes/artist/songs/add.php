<?php
if (!defined('ABSPATH')) {
    exit;
}

class AddProductHandler
{
    public function handle_add()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id    = get_current_user_id();
        $song_count = ArtistQuery::get_song_count($user_id);
        $max_songs  = 5;

        if ($song_count >= $max_songs) {
            wc_add_notice(__('You have reached the maximum number of songs allowed.', 'paper-tipping-addons'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-songs'));
            exit;
        }

        include PAPER_TIPPING_PATH . 'templates/artist/songs/add-form.php';
    }

    public function process_product_submission()
    {
        if (
            !isset($_POST['product_nonce']) ||
            !wp_verify_nonce($_POST['product_nonce'], 'submit_artist_product_nonce') ||
            !is_user_logged_in()
        ) {
            $this->send_error(__('Security check failed', 'paper-tipping-addons'), 'manage-songs');
        }

        $user_id    = get_current_user_id();
        $song_count = ArtistQuery::get_song_count($user_id);

        if ($song_count >= 5) {
            $this->send_error(__('You have reached the maximum limit of 5 songs.', 'paper-tipping-addons'), 'manage-songs');
        }

        $name        = sanitize_text_field($_POST['product_name'] ?? '');
        $description = wp_kses_post($_POST['product_description'] ?? '');

        if (empty($name)) {
            $this->send_error(__('Please fill all required fields with valid values', 'paper-tipping-addons'), 'add-song');
        }

        if (empty($_FILES['product_preview']['name']) || empty($_FILES['product_mp3']['name'])) {
            $this->send_error(__('Please upload both preview audio and full song MP3 files', 'paper-tipping-addons'), 'add-song');
        }

        if (
            $_FILES['product_preview']['error'] !== UPLOAD_ERR_OK ||
            $_FILES['product_mp3']['error'] !== UPLOAD_ERR_OK
        ) {
            $this->send_error(__('There was an error uploading your files. Please try again.', 'paper-tipping-addons'), 'add-song');
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
        $product->set_props(['author' => $user_id]);
        $product_id = $product->save();

        // Cover image (optional)
        if (!empty($_FILES['product_image']['name'])) {
            $image_id = UploadHandler::upload_image('product_image', $product_id);
            if (!is_wp_error($image_id)) {
                $product->set_image_id($image_id);
                $product->save();
            }
        }

        // Preview audio (optional but validated above)
        if (!empty($_FILES['product_preview']['name'])) {
            $preview_id = UploadHandler::upload_audio('product_preview', $product_id);
            if (!is_wp_error($preview_id)) {
                update_post_meta($product_id, 'song_preview', $preview_id);
            }
        }

        $downloads = [];

        // Full MP3 (required)
        $mp3_id = UploadHandler::upload_audio('product_mp3', $product_id);
        if (is_wp_error($mp3_id)) {
            $this->send_error($mp3_id->get_error_message(), 'add-song');
        }
        update_post_meta($product_id, 'song_mp3', $mp3_id);
        $mp3_url              = wp_get_attachment_url($mp3_id);
        $downloads[md5($mp3_url)] = ['id' => md5($mp3_url), 'name' => 'MP3 Version', 'file' => $mp3_url];

        // Full WAV (optional)
        if (!empty($_FILES['product_wav']['name'])) {
            $wav_id = UploadHandler::upload_audio('product_wav', $product_id);
            if (is_wp_error($wav_id)) {
                $this->send_error($wav_id->get_error_message(), 'add-song');
            }
            update_post_meta($product_id, 'song_wav', $wav_id);
            $wav_url              = wp_get_attachment_url($wav_id);
            $downloads[md5($wav_url)] = ['id' => md5($wav_url), 'name' => 'WAV Version', 'file' => $wav_url];
        }

        if (!empty($downloads)) {
            update_post_meta($product_id, '_downloadable_files', $downloads);
        }

        $success_msg = __('Song added successfully! It will be reviewed by an admin before publishing.', 'paper-tipping-addons');

        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json_success(['message' => $success_msg, 'redirect' => wc_get_account_endpoint_url('manage-songs')]);
        } else {
            wc_add_notice($success_msg, 'success');
            wp_redirect(wc_get_account_endpoint_url('manage-songs'));
            exit;
        }
    }

    private function send_error(string $message, string $redirect_endpoint)
    {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json_error(['message' => $message]);
        } else {
            wc_add_notice($message, 'error');
            wp_redirect(wc_get_account_endpoint_url($redirect_endpoint));
            exit;
        }
    }
}
