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
    // Main menu
    add_menu_page(
      __('Song Tips', 'paper-tipping-addons'),
      __('Song Tips', 'paper-tipping-addons'),
      'manage_options',
      'song-tips',
      [$this, 'render_admin_page'],
      'dashicons-money-alt',
      30
    );

    // PayPal Settings submenu
    add_submenu_page(
      'song-tips',
      __('PayPal Settings', 'paper-tipping-addons'),
      __('PayPal Settings', 'paper-tipping-addons'),
      'manage_options',
      'song-tips-paypal',
      [$this, 'render_paypal_settings']
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
      <h1><?php echo esc_html__('Song Tips', 'paper-tipping-addons'); ?></h1>
      <table class="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th><?php echo esc_html__('Song', 'paper-tipping-addons'); ?></th>
            <th><?php echo esc_html__('Tip Amount', 'paper-tipping-addons'); ?></th>
            <th><?php echo esc_html__('Customer', 'paper-tipping-addons'); ?></th>
            <th><?php echo esc_html__('Date', 'paper-tipping-addons'); ?></th>
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
                  echo esc_html__('Anonymous', 'paper-tipping-addons');
                }
                ?>
              </td>
              <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tip->created_at))); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($tips)): ?>
            <tr>
              <td colspan="4"><?php echo esc_html__('No tips found.', 'paper-tipping-addons'); ?></td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
<?php
  }

  public function render_paypal_settings()
  {
    if (isset($_POST['submit_paypal_settings'])) {
      if (
        isset($_POST['tipping_paypal_client_id']) &&
        isset($_POST['tipping_paypal_client_secret']) &&
        isset($_POST['tipping_paypal_sandbox'])
      ) {
        update_option('tipping_paypal_client_id', sanitize_text_field($_POST['tipping_paypal_client_id']));
        update_option('tipping_paypal_client_secret', sanitize_text_field($_POST['tipping_paypal_client_secret']));
        update_option('tipping_paypal_sandbox', isset($_POST['tipping_paypal_sandbox']) ? '1' : '0');
        
        echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully!', 'paper-tipping-addons') . '</p></div>';
      }
    }

    $client_id = get_option('tipping_paypal_client_id', '');
    $client_secret = get_option('tipping_paypal_client_secret', '');
    $sandbox_mode = get_option('tipping_paypal_sandbox', '1');
    ?>
    <div class="wrap">
      <h1><?php echo esc_html__('PayPal Settings', 'paper-tipping-addons'); ?></h1>
      <form method="post" action="">
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="tipping_paypal_client_id"><?php echo esc_html__('Client ID', 'paper-tipping-addons'); ?></label>
            </th>
            <td>
              <input type="text" id="tipping_paypal_client_id" name="tipping_paypal_client_id" 
                     value="<?php echo esc_attr($client_id); ?>" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="tipping_paypal_client_secret"><?php echo esc_html__('Client Secret', 'paper-tipping-addons'); ?></label>
            </th>
            <td>
              <input type="password" id="tipping_paypal_client_secret" name="tipping_paypal_client_secret" 
                     value="<?php echo esc_attr($client_secret); ?>" class="regular-text">
            </td>
          </tr>
          <tr>
            <th scope="row">
              <?php echo esc_html__('Environment', 'paper-tipping-addons'); ?>
            </th>
            <td>
              <fieldset>
                <label>
                  <input type="checkbox" name="tipping_paypal_sandbox" value="1" 
                         <?php checked('1', $sandbox_mode); ?>>
                  <?php echo esc_html__('Sandbox Mode', 'paper-tipping-addons'); ?>
                </label>
                <p class="description">
                  <?php echo esc_html__('Check this to use PayPal Sandbox for testing.', 'paper-tipping-addons'); ?>
                </p>
              </fieldset>
            </td>
          </tr>
        </table>
        <p class="submit">
          <input type="submit" name="submit_paypal_settings" class="button button-primary" 
                 value="<?php echo esc_attr__('Save Changes', 'paper-tipping-addons'); ?>">
        </p>
      </form>
    </div>
    <?php
  }
}
