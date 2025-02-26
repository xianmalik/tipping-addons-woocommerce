<?php
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

class TippingAdminPanel
{
  public function __construct()
  {
    add_action('admin_menu', [$this, 'add_admin_menu']);
    add_action('admin_init', [$this, 'create_tips_table']);
  }

  public function add_admin_menu()
  {
    add_menu_page(
      __('Song Tips', 'tipping-addons-jetengine'),
      __('Song Tips', 'tipping-addons-jetengine'),
      'manage_options',
      'song-tips',
      [$this, 'render_admin_page'],
      'dashicons-money-alt',
      30
    );
  }

  public function create_tips_table()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'song_tips';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                song_id bigint(20) NOT NULL,
                tip_amount decimal(10,2) NOT NULL,
                customer_name varchar(100),
                customer_id bigint(20),
                order_id bigint(20),
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
    }
  }

  public function render_admin_page()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'song_tips';
    $tips = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
?>
    <div class="wrap">
      <h1><?php echo esc_html__('Song Tips', 'tipping-addons-jetengine'); ?></h1>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th><?php echo esc_html__('Song', 'tipping-addons-jetengine'); ?></th>
            <th><?php echo esc_html__('Tip Amount', 'tipping-addons-jetengine'); ?></th>
            <th><?php echo esc_html__('Customer', 'tipping-addons-jetengine'); ?></th>
            <th><?php echo esc_html__('Date', 'tipping-addons-jetengine'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tips as $tip): ?>
            <tr>
              <td>
                <?php
                $song_title = get_the_title($tip->song_id);
                echo esc_html($song_title ? $song_title : "#$tip->song_id");
                ?>
              </td>
              <td><?php echo esc_html(wc_price($tip->tip_amount)); ?></td>
              <td>
                <?php
                if ($tip->customer_name) {
                  echo esc_html($tip->customer_name);
                } else {
                  echo esc_html__('Anonymous', 'tipping-addons-jetengine');
                }
                ?>
              </td>
              <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tip->created_at))); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($tips)): ?>
            <tr>
              <td colspan="4"><?php echo esc_html__('No tips found.', 'tipping-addons-jetengine'); ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
<?php
  }
}
