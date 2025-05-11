<?php
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

class ManageSongsHandler
{
  public function handle_manage()
  {
    if (!is_user_logged_in()) {
      return;
    }

    $user_id = get_current_user_id();
    $song_count = $this->get_artist_song_count($user_id);
    $max_songs = 5;

    // Get products created by this artist
    $args = [
      'post_type' => 'product',
      'posts_per_page' => -1,
      'author' => $user_id,
      'post_status' => ['publish', 'draft', 'pending']
    ];

    $products = get_posts($args);

    $this->render_manage_page($products, $song_count, $max_songs);
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

  private function render_manage_page($products, $song_count, $max_songs)
  {
?>
    <div class="product-management-header">
      <h2><?php _e('Products', 'tipping-addons-jetengine'); ?></h2>
      <?php if ($song_count < $max_songs) : ?>
        <a href="<?php echo wc_get_account_endpoint_url('add-song'); ?>" class="see-all">
          <?php _e('Add New', 'tipping-addons-jetengine'); ?>
        </a>
      <?php endif; ?>
    </div>

    <div class="song-limit-info">
      <p><?php printf(__('Songs: %d of %d created', 'tipping-addons-jetengine'), $song_count, $max_songs); ?></p>
      <p>Add your 5 best songs to your profile</p>
    </div>

    <?php if (!empty($products)) : ?>
      <div class="product-list">
        <?php foreach ($products as $product) :
          $wc_product = wc_get_product($product->ID);
          $status_class = $product->post_status === 'publish' ? 'status-live' : 'status-pending';
        ?>
          <div class="product-item">
            <div class="product-icon">
              <?php echo $wc_product->get_image('thumbnail'); ?>
            </div>
            <div class="product-details">
              <div class="product-main">
                <h3><?php echo esc_html($product->post_title); ?></h3>
                <span class="product-date"><?php echo get_the_date('j M Y', $product->ID); ?></span>
              </div>
              <div class="product-meta">
                <span class="product-status <?php echo $status_class; ?>">
                  <?php echo $product->post_status === 'publish' ? 'Published' : 'Pending'; ?>
                </span>
                <span class="product-price">
                  <?php echo $wc_product->get_price_html(); ?>
                </span>
                <a href="<?php echo add_query_arg('product_id', $product->ID, wc_get_account_endpoint_url('edit-song')); ?>"
                  class="edit-button">
                  <?php _e('Edit', 'tipping-addons-jetengine'); ?>
                </a>
                <a href="<?php
                          echo wp_nonce_url(
                            add_query_arg(
                              array(
                                'product_id' => $product->ID
                              ),
                              wc_get_account_endpoint_url('delete-song')
                            ),
                            'delete_artist_product_' . $product->ID
                          ); ?>"
                  class="delete-button">
                  <?php _e('Delete', 'tipping-addons-jetengine'); ?>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else : ?>
      <p><?php _e('You haven\'t created any songs yet.', 'tipping-addons-jetengine'); ?></p>
<?php endif;
  }
}
