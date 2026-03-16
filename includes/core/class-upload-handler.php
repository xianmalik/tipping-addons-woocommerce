<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Static utility class for media uploads.
 * Replaces 6 duplicate upload_product_file / upload_product_image methods
 * spread across artist-vendor.php, songs/add.php, and songs/edit.php.
 */
class UploadHandler
{
    private static function load_dependencies(): void
    {
        if (!function_exists('media_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }
    }

    /**
     * Upload an audio file (MP3, WAV, OGG, M4A, AAC, FLAC).
     *
     * @param string $file_key    $_FILES key
     * @param int    $post_parent Attach to this post (0 = unattached)
     * @return int|WP_Error       Attachment ID on success
     */
    public static function upload_audio(string $file_key, int $post_parent = 0)
    {
        self::load_dependencies();

        if (empty($_FILES[$file_key]['name'])) {
            return new WP_Error('missing_file', __('No file was uploaded.', 'paper-tipping-addons'));
        }

        $file_type = wp_check_filetype($_FILES[$file_key]['name']);
        $allowed   = [
            'audio/mpeg',
            'audio/mp3',
            'audio/wav',
            'audio/x-wav',
            'audio/ogg',
            'audio/aac',
            'audio/x-m4a',
            'audio/flac',
        ];

        if (!in_array($file_type['type'], $allowed, true)) {
            return new WP_Error(
                'invalid_file_type',
                __('Invalid file type. Only MP3, WAV, OGG, M4A, AAC, and FLAC audio files are allowed.', 'paper-tipping-addons')
            );
        }

        return media_handle_upload($file_key, $post_parent);
    }

    /**
     * Upload an image (JPEG, PNG, GIF, WebP).
     *
     * @param string $file_key    $_FILES key
     * @param int    $post_parent Attach to this post (0 = unattached)
     * @return int|WP_Error       Attachment ID on success
     */
    public static function upload_image(string $file_key, int $post_parent = 0)
    {
        self::load_dependencies();

        if (empty($_FILES[$file_key]['name'])) {
            return new WP_Error('missing_file', __('No image was uploaded.', 'paper-tipping-addons'));
        }

        $file_type = wp_check_filetype($_FILES[$file_key]['name']);
        $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file_type['type'], $allowed, true)) {
            return new WP_Error(
                'invalid_file_type',
                __('Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.', 'paper-tipping-addons')
            );
        }

        return media_handle_upload($file_key, $post_parent);
    }
}
