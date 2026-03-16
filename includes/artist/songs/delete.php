<?php
if (!defined('ABSPATH')) {
    exit;
}

class DeleteProductHandler
{
    public function handle_delete()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
        $nonce      = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

        if (!wp_verify_nonce($nonce, 'delete_artist_product_' . $product_id)) {
            wc_add_notice(__('Security check failed.', 'paper-tipping-addons'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-songs'));
            exit;
        }

        $product      = wc_get_product($product_id);
        $product_post = get_post($product_id);

        if (!$product || $product_post->post_author != get_current_user_id()) {
            wc_add_notice(__('You do not have permission to delete this product.', 'paper-tipping-addons'), 'error');
            wp_redirect(wc_get_account_endpoint_url('manage-songs'));
            exit;
        }

        if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
            wp_update_post(['ID' => $product_id, 'post_status' => 'private']);
            wc_add_notice(__('Song deleted successfully.', 'paper-tipping-addons'), 'success');
            wp_redirect(wc_get_account_endpoint_url('manage-songs'));
            exit;
        }

        include PAPER_TIPPING_PATH . 'templates/artist/songs/delete-confirm.php';
    }
}
