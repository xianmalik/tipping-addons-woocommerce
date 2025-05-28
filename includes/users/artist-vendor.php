<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ArtistVendor
{
    // Add this to your ArtistVendor class constructor
    public function __construct()
    {
        // Create custom role on plugin activation
        register_activation_hook(plugin_dir_path(dirname(dirname(__FILE__))) . 'tipping-addons-jetengine.php', [$this, 'create_artist_role']);

        // Registration form shortcode
        add_shortcode('artist_registration_form', [$this, 'registration_form_shortcode']);

        // Process registration
        add_action('wp_ajax_nopriv_register_artist', [$this, 'process_registration']);
        add_action('wp_ajax_register_artist', [$this, 'process_registration']);

        // Add custom endpoint for artist sales
        add_action('init', [$this, 'add_endpoints']);
        add_filter('woocommerce_account_menu_items', [$this, 'add_artist_menu_items']);
        add_action('woocommerce_account_artist-sales_endpoint', [$this, 'artist_sales_content']);

        // Import and use product management functionality
        require_once plugin_dir_path(__FILE__) . 'songs/add.php';
        require_once plugin_dir_path(__FILE__) . 'songs/edit.php';
        require_once plugin_dir_path(__FILE__) . 'songs/delete.php';
        require_once plugin_dir_path(__FILE__) . 'songs/manage.php';

        $manage_handler = new ManageSongsHandler();
        $add_handler = new AddProductHandler();
        $edit_handler = new EditProductHandler();
        $delete_handler = new DeleteProductHandler();

        add_action('woocommerce_account_add-song_endpoint', [$add_handler, 'handle_add']);
        add_action('woocommerce_account_edit-song_endpoint', [$edit_handler, 'handle_edit']);
        add_action('woocommerce_account_delete-song_endpoint', [$delete_handler, 'handle_delete']);
        add_action('woocommerce_account_manage-songs_endpoint', [$manage_handler, 'handle_manage']);
        add_action('woocommerce_account_artist-profile_endpoint', [$this, 'artist_profile_content']);

        // Process product submission
        add_action('wp_ajax_submit_artist_product', [$add_handler, 'process_product_submission']);
        add_action('wp_ajax_update_artist_product', [$edit_handler, 'process_product_update']);

        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Restrict access to artist endpoints
        add_action('template_redirect', [$this, 'check_user_logged_in']);

        // Add uploads directory to WooCommerce's allowed download paths
        add_filter('woocommerce_downloadable_products_folder_path', [$this, 'add_uploads_to_allowed_paths']);

        // Add product management capabilities to all users
        add_action('init', [$this, 'add_product_capabilities_to_users']);

        // Add signup prompt to login form
        add_action('woocommerce_login_form_start', [$this, 'add_signup_prompt_to_login']);


        add_action('wp_ajax_update_artist_profile', [$this, 'process_profile_update']);
    }

    public function add_uploads_to_allowed_paths($path)
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'];
    }

    public function create_artist_role()
    {
        // Create Music Artist - Vendor role with specific capabilities
        add_role(
            'music_artist_vendor',
            'Music Artist - Vendor',
            [
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            ]
        );
    }

    public function ensure_artist_role_exists()
    {
        // Check if the role exists, if not create it
        $role = get_role('music_artist_vendor');
        if (!$role) {
            $this->create_artist_role();
        }
    }

    public function enqueue_scripts()
    {
        // Enqueue login form styles globally on all pages
        wp_enqueue_style(
            'tipping-addons-woocommerce-login',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/woocommerce-login.css',
            array(),
            '1.0.0'
        );

        // Enqueue artist vendor styles globally
        wp_enqueue_style(
            'tipping-addons-registration',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/artist-vendor.css',
            array(),
            '1.0.1'
        );

        // Enqueue my account styles globally
        wp_enqueue_style(
            'tipping-addons-my-account',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/my-account.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'tipping-addons-artist-vendor',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/artist-vendor.js',
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('tipping-addons-artist-vendor', 'artist_vendor_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('artist_vendor_nonce')
        ));
    }

    public function add_endpoints()
    {
        add_rewrite_endpoint('artist-sales', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('manage-songs', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('add-song', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('edit-song', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('delete-song', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('artist-profile', EP_ROOT | EP_PAGES);

        // Flush rewrite rules only once
        if (!get_option('artist_vendor_flush_rewrite_rules')) {
            flush_rewrite_rules();
            update_option('artist_vendor_flush_rewrite_rules', true);
        }

        // Force flush rewrite rules
        flush_rewrite_rules();
    }

    public function add_artist_menu_items($items)
    {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $is_artist = in_array('music_artist_vendor', $user->roles);
            $is_admin = in_array('administrator', $user->roles);

            if ($is_artist || $is_admin) {
                $new_items = [];

                foreach ($items as $key => $item) {
                    if ($key !== 'orders' && $key !== 'downloads' && $key !== 'song-downloads' && $key !== 'payment-methods') {
                        $new_items[$key] = $item;
                    }

                    if ($key === 'dashboard') {
                        $new_items['artist-profile'] = __('Artist Profile', 'tipping-addons-jetengine');
                        $new_items['artist-sales'] = __('My Tips', 'tipping-addons-jetengine');
                        $new_items['manage-songs'] = __('Manage Songs', 'tipping-addons-jetengine');
                    }
                }

                return $new_items;
            }
        }
        return $items;
    }

    public function check_user_logged_in()
    {
        global $wp_query;

        $endpoints = ['artist-sales', 'manage-songs', 'add-song', 'edit-song', 'delete-song'];

        foreach ($endpoints as $endpoint) {
            if (isset($wp_query->query_vars[$endpoint]) && !is_user_logged_in()) {
                wp_redirect(wc_get_page_permalink('myaccount'));
                exit;
            }
        }
    }

    public function registration_form_shortcode()
    {
        ob_start();

        // If user is logged in, show a message
        if (is_user_logged_in()) {
            echo '<p>' . __('You are already registered and logged in.', 'tipping-addons-jetengine') . '</p>';
            return ob_get_clean();
        }

        // Enqueue specific styles for the registration form
        wp_enqueue_style(
            'tipping-addons-registration',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/artist-vendor.css',
            array(),
            '1.0.1'
        );

        // Show the registration form
?>
        <div class="artist-registration-form">
            <h2><?php _e('Register as Artist', 'tipping-addons-jetengine'); ?></h2>

            <div class="registration-message"></div>

            <form id="artist-registration-form" method="post">
                <p>
                    <label for="artist_username"><?php _e('Username', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="text" name="artist_username" id="artist_username" placeholder="Choose a username" required />
                </p>

                <p>
                    <label for="artist_email"><?php _e('Email', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="email" name="artist_email" id="artist_email" placeholder="Your email address" required />
                </p>

                <p class="password-field-container">
                    <label for="artist_password"><?php _e('Password', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="password" name="artist_password" id="artist_password" placeholder="Create a password" required />
                    <span class="password-toggle" onclick="togglePasswordVisibility('artist_password')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </p>

                <p class="password-field-container">
                    <label for="artist_password_confirm"><?php _e('Confirm Password', 'tipping-addons-jetengine'); ?> <span class="required">*</span></label>
                    <input type="password" name="artist_password_confirm" id="artist_password_confirm" placeholder="Confirm your password" required />
                    <span class="password-toggle" onclick="togglePasswordVisibility('artist_password_confirm')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </p>

                <p>
                    <label for="artist_first_name"><?php _e('First Name', 'tipping-addons-jetengine'); ?></label>
                    <input type="text" name="artist_first_name" id="artist_first_name" placeholder="Your first name" />
                </p>

                <p>
                    <label for="artist_last_name"><?php _e('Last Name', 'tipping-addons-jetengine'); ?></label>
                    <input type="text" name="artist_last_name" id="artist_last_name" placeholder="Your last name" />
                </p>

                <p class="form-submit">
                    <input type="hidden" name="action" value="register_artist" />
                    <input type="hidden" name="artist_nonce" value="<?php echo wp_create_nonce('artist_registration_nonce'); ?>" />
                    <button type="submit" class="register-button"><?php _e('Register', 'tipping-addons-jetengine'); ?></button>
                </p>

                <!-- <div class="social-login-divider">
                    <span><?php _e('Or register with', 'tipping-addons-jetengine'); ?></span>
                </div>

                <div class="social-login-buttons">
                    <button type="button" class="social-login-button google-login">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                            <path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L12.545,10.239z" fill="#4285F4" />
                        </svg>
                        <?php _e('Google', 'tipping-addons-jetengine'); ?>
                    </button>
                    <button type="button" class="social-login-button facebook-login">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                            <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z" fill="#1877F2" />
                        </svg>
                        <?php _e('Facebook', 'tipping-addons-jetengine'); ?>
                    </button>
                    <button type="button" class="social-login-button apple-login">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24">
                            <path d="M22 17.607c-.786 2.28-3.139 6.317-5.563 6.361-1.608.031-2.125-.953-3.963-.953-1.837 0-2.412.923-3.932.983-2.572.099-6.542-5.827-6.542-10.995 0-4.747 3.308-7.1 6.198-7.143 1.55-.028 3.014 1.045 3.959 1.045.949 0 2.727-1.29 4.596-1.101.782.033 2.979.315 4.389 2.377-3.741 2.442-3.158 7.549.858 9.426zm-5.222-17.607c-2.826.114-5.132 3.079-4.81 5.531 2.612.203 5.118-2.725 4.81-5.531z" />
                        </svg>
                        <?php _e('Apple', 'tipping-addons-jetengine'); ?>
                    </button>
                </div> -->

                <p class="login-link">
                    <?php _e('Already have an account?', 'tipping-addons-jetengine'); ?>
                    <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php _e('Log in', 'tipping-addons-jetengine'); ?></a>
                </p>
            </form>
        </div>

        <script>
            function togglePasswordVisibility(inputId) {
                var input = document.getElementById(inputId);
                if (input.type === "password") {
                    input.type = "text";
                } else {
                    input.type = "password";
                }
            }
        </script>
    <?php
        return ob_get_clean();
    }

    public function process_registration()
    {
        // Verify nonce
        if (!isset($_POST['artist_nonce']) || !wp_verify_nonce($_POST['artist_nonce'], 'artist_registration_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'tipping-addons-jetengine')]);
        }

        // Validate required fields
        $username = sanitize_user($_POST['artist_username']);
        $email = sanitize_email($_POST['artist_email']);
        $password = $_POST['artist_password'];
        $password_confirm = $_POST['artist_password_confirm'];

        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(['message' => __('Please fill all required fields', 'tipping-addons-jetengine')]);
        }

        if ($password !== $password_confirm) {
            wp_send_json_error(['message' => __('Passwords do not match', 'tipping-addons-jetengine')]);
        }

        // Check if username or email already exists
        if (username_exists($username)) {
            wp_send_json_error(['message' => __('Username already exists', 'tipping-addons-jetengine')]);
        }

        if (email_exists($email)) {
            wp_send_json_error(['message' => __('Email already exists', 'tipping-addons-jetengine')]);
        }

        // Create the user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        // Set user role
        $user = new WP_User($user_id);
        $user->set_role('music_artist_vendor');

        // Update user meta
        if (!empty($_POST['artist_first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['artist_first_name']));
        }

        if (!empty($_POST['artist_last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['artist_last_name']));
        }

        // Auto login the user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success([
            'message' => __('Registration successful! Redirecting...', 'tipping-addons-jetengine'),
            'redirect' => wc_get_page_permalink('myaccount')
        ]);
    }

    public function artist_sales_content()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();

        // Get products created by this artist
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'author' => $user_id,
            'post_status' => ['publish', 'draft', 'pending']
        ];

        $products = get_posts($args);
        $product_ids = wp_list_pluck($products, 'ID');

        if (empty($product_ids)) {
            echo '<p>' . __('You haven\'t created any songs yet.', 'tipping-addons-jetengine') . '</p>';
            echo '<p><a href="' . wc_get_account_endpoint_url('add-song') . '" class="button">' . __('Add Your First Product', 'tipping-addons-jetengine') . '</a></p>';
            return;
        }

        // Get orders containing these products
        $orders = wc_get_orders([
            'limit' => -1,
            'status' => ['completed', 'processing'],
            'return' => 'ids',
        ]);

        $tips_data = [];
        $total_sales = 0;

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);

            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();

                if (in_array($product_id, $product_ids)) {
                    $product = wc_get_product($product_id);

                    if (!isset($tips_data[$product_id])) {
                        $tips_data[$product_id] = [
                            'name' => $product->get_name(),
                            'quantity' => 0,
                            'total' => 0
                        ];
                    }

                    $tips_data[$product_id]['quantity'] += $item->get_quantity();
                    $tips_data[$product_id]['total'] += $item->get_total();
                    $total_sales += $item->get_total();
                }
            }
        }

        if ($total_sales > 0) {
            ?>
            <div class="tips-summary">
                <p class="total-tips"><?php printf(__('Total Tips: %s', 'tipping-addons-jetengine'), wc_price($total_sales)); ?></p>
                <button type="button" class="withdraw-money-btn button">
                    <?php _e('Withdraw Money', 'tipping-addons-jetengine'); ?>
                </button>
            </div>

            <div class="sales-list">
                <?php
                foreach ($tips_data as $product_id => $tip): ?>
                    <div class="sale-item">
                        <div class="sale-details">
                            <div class="sale-main">
                                <h3><?php echo esc_html($tip['name']); ?></h3>
                                <span class="sale-date"><?php echo date('j M Y', strtotime($tip->date_created)); ?></span>
                            </div>
                            <div class="sale-meta">
                                <span class="sale-amount">
                                    <span class="sale-currency"><?php echo get_woocommerce_currency_symbol(); ?></span>
                                    <?php echo number_format($tip['total'], 2); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
        } else {
            ?>
            <p><?php _e('You haven\'t received any tip yet.', 'tipping-addons-jetengine'); ?></p>
            <?php
        }
    }

    public function process_product_submission()
    {
        // Verify nonce and check if user is logged in
        if (
            !isset($_POST['product_nonce']) ||
            !wp_verify_nonce($_POST['product_nonce'], 'submit_artist_product_nonce') ||
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'tipping-addons-jetengine')]);
        }

        $user_id = get_current_user_id();
        $song_count = $this->get_artist_song_count($user_id);
        $max_songs = 5;

        // Check if artist has reached the song limit
        if ($song_count >= $max_songs) {
            wp_send_json_error([
                'message' => __('You have reached the maximum limit of 5 songs.', 'tipping-addons-jetengine')
            ]);
        }

        // Validate required fields
        $name = sanitize_text_field($_POST['product_name']);
        $description = wp_kses_post($_POST['product_description']);

        if (empty($name) || empty($description)) {
            wp_send_json_error(['message' => __('Please fill all required fields with valid values', 'tipping-addons-jetengine')]);
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

        // Set the current user as the author
        $product->set_props([
            'author' => get_current_user_id()
        ]);

        // Save the product to get an ID
        $product_id = $product->save();

        // Upload product image if provided
        if (!empty($_FILES['product_image']['name'])) {
            $image_id = $this->upload_product_image('product_image', $product_id);
            if (!is_wp_error($image_id)) {
                $product->set_image_id($image_id);
                $product->save();
            }
        }

        // Handle downloadable files using WooCommerce's functions
        $downloads = [];

        // Upload MP3
        if (!empty($_FILES['product_mp3']['name'])) {
            $mp3_id = $this->upload_product_file('product_mp3', $product_id);
            if (is_wp_error($mp3_id)) {
                wp_send_json_error(['message' => $mp3_id->get_error_message()]);
            }

            // Save as product meta
            update_post_meta($product_id, 'song_mp3', $mp3_id);

            // Get the file URL
            $mp3_url = wp_get_attachment_url($mp3_id);

            // Add to downloadable files using URL
            $downloads[md5($mp3_url)] = [
                'id' => md5($mp3_url),
                'name' => 'MP3 Version',
                'file' => $mp3_url
            ];
        }

        // Upload WAV if provided
        if (!empty($_FILES['product_wav']['name'])) {
            $wav_id = $this->upload_product_file('product_wav', $product_id);
            if (is_wp_error($wav_id)) {
                wp_send_json_error(['message' => $wav_id->get_error_message()]);
            }

            // Save as product meta
            update_post_meta($product_id, 'song_wav', $wav_id);

            // Get the file URL
            $wav_url = wp_get_attachment_url($wav_id);

            // Add to downloadable files using URL
            $downloads[md5($wav_url)] = [
                'id' => md5($wav_url),
                'name' => 'WAV Version',
                'file' => $wav_url
            ];
        }

        // Save downloadable files
        if (!empty($downloads)) {
            update_post_meta($product_id, '_downloadable_files', $downloads);
        }

        wp_send_json_success([
            'message' => __('Product added successfully! It will be reviewed by an admin before publishing.', 'tipping-addons-jetengine'),
            'redirect' => wc_get_account_endpoint_url('manage-songs')
        ]);
    }

    public function process_product_update()
    {
        // Verify nonce and check if user is logged in
        if (
            !isset($_POST['product_nonce']) ||
            !wp_verify_nonce($_POST['product_nonce'], 'update_artist_product_nonce') ||
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'tipping-addons-jetengine')]);
        }

        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);
        $product_post = get_post($product_id);

        if (!$product || $product_post->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => __('You do not have permission to edit this product', 'tipping-addons-jetengine')]);
        }

        // Update basic product data
        $name = sanitize_text_field($_POST['product_name']);
        $description = wp_kses_post($_POST['product_description']);

        if (empty($name) || empty($description)) {
            wp_send_json_error(['message' => __('Please fill all required fields with valid values', 'tipping-addons-jetengine')]);
        }

        $product->set_name($name);
        $product->set_description($description);
        $product->set_status('pending'); // Set to pending for admin review

        // Get existing downloads
        $downloads = $product->get_downloads();

        // Handle Music Preview update
        if (!empty($_FILES['product_preview']['name'])) {
            $preview_id = $this->upload_product_file('product_preview', $product_id);
            if (is_wp_error($preview_id)) {
                wp_send_json_error(['message' => $preview_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_preview', $preview_id);
        }

        // Handle MP3 file update
        if (!empty($_FILES['product_mp3']['name'])) {
            $mp3_id = $this->upload_product_file('product_mp3', $product_id);
            if (is_wp_error($mp3_id)) {
                wp_send_json_error(['message' => $mp3_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_mp3', $mp3_id);

            $mp3_url = wp_get_attachment_url($mp3_id);
            $downloads[md5($mp3_url)] = [
                'id' => md5($mp3_url),
                'name' => 'MP3 Version',
                'file' => $mp3_url
            ];
        }

        // Handle WAV file update
        if (!empty($_FILES['product_wav']['name'])) {
            $wav_id = $this->upload_product_file('product_wav', $product_id);
            if (is_wp_error($wav_id)) {
                wp_send_json_error(['message' => $wav_id->get_error_message()]);
            }
            update_post_meta($product_id, 'song_wav', $wav_id);

            $wav_url = wp_get_attachment_url($wav_id);
            $downloads[md5($wav_url)] = [
                'id' => md5($wav_url),
                'name' => 'WAV Version',
                'file' => $wav_url
            ];
        }

        // Update downloadable files
        if (!empty($downloads)) {
            $product->set_downloads($downloads);
        }

        // Handle product image update
        if (!empty($_FILES['product_image']['name'])) {
            $image_id = $this->upload_product_image('product_image', $product_id);
            if (!is_wp_error($image_id)) {
                $product->set_image_id($image_id);
            }
        }

        // Save all changes
        $product->save();

        wp_send_json_success([
            'message' => __('Product updated successfully! It will be reviewed by an admin before publishing.', 'tipping-addons-jetengine'),
            'redirect' => wc_get_account_endpoint_url('manage-songs')
        ]);
    }

    /**
     * Allow uploads directory for downloadable files
     */
    public function allow_uploads_directory($paths)
    {
        $upload_dir = wp_upload_dir();
        $paths[] = $upload_dir['basedir'];
        $paths[] = ABSPATH . 'wp-content/uploads';
        return $paths;
    }

    /**
     * Custom validation for downloadable files
     */
    public function validate_downloadable_file_exists($exists, $file_url)
    {
        // If WooCommerce already validated it as existing, return true
        if ($exists) {
            return true;
        }

        // Check if it's a URL to our uploads directory
        if (strpos($file_url, 'wp-content/uploads') !== false) {
            // Convert URL to server path
            $upload_dir = wp_upload_dir();
            $file_path = str_replace(
                $upload_dir['baseurl'],
                $upload_dir['basedir'],
                $file_url
            );

            // Check if file exists
            if (file_exists($file_path)) {
                return true;
            }
        }

        return $exists;
    }

    private function upload_product_file($file_key, $product_id)
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Check if file exists
        if (empty($_FILES[$file_key]['name'])) {
            return new WP_Error('missing_file', __('No file was uploaded.', 'tipping-addons-jetengine'));
        }

        // Check file type
        $file_type = wp_check_filetype($_FILES[$file_key]['name']);
        $allowed_types = [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav'
        ];

        if (!in_array($file_type['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', __('Invalid file type. Only MP3 and WAV files are allowed.', 'tipping-addons-jetengine'));
        }

        // Upload the file
        $attachment_id = media_handle_upload($file_key, $product_id);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        return $attachment_id;
    }

    private function upload_product_image($file_key, $product_id)
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Check if file exists
        if (empty($_FILES[$file_key]['name'])) {
            return new WP_Error('missing_file', __('No image was uploaded.', 'tipping-addons-jetengine'));
        }

        // Check file type
        $file_type = wp_check_filetype($_FILES[$file_key]['name']);
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($file_type['type'], $allowed_types)) {
            return new WP_Error('invalid_file_type', __('Invalid file type. Only JPG, PNG and GIF images are allowed.', 'tipping-addons-jetengine'));
        }

        // Upload the file
        $attachment_id = media_handle_upload($file_key, $product_id);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        return $attachment_id;
    }

    /**
     * Add product management capabilities to all user roles
     */
    public function add_product_capabilities_to_users()
    {
        $roles = ['subscriber', 'customer', 'contributor', 'author'];

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('upload_files');
                $role->add_cap('edit_products');
                $role->add_cap('edit_published_products');
                $role->add_cap('delete_products');
                $role->add_cap('publish_products');
                $role->add_cap('read_private_products');
                $role->add_cap('manage_product_terms');
                $role->add_cap('assign_product_terms');
            }
        }
    }

    public function add_signup_prompt_to_login()
    {
    ?>
        <p class="signup-prompt">
            <?php _e('New to Musicbae? ', 'tipping-addons-jetengine'); ?>
            <a href="<?php echo esc_url(site_url('/artist-registration/')); ?>"><?php _e('Sign up', 'tipping-addons-jetengine'); ?></a>
        </p>
    <?php
    }

    public function artist_profile_content()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);

        // Get existing meta
        $artist_bio = get_user_meta($user_id, 'artist_bio', true);
        $profile_image_id = get_user_meta($user_id, 'profile_image_id', true);
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        $display_name = $user->display_name;
    ?>

        <div class="artist-profile-wrapper">
            <h2><?php _e('Artist Profile Settings', 'tipping-addons-jetengine'); ?></h2>

            <form id="artist-profile-form" method="post" enctype="multipart/form-data">
                <div class="form-message"></div>

                <div class="profile-image-section">
                    <label><?php _e('Profile Picture', 'tipping-addons-jetengine'); ?></label>
                    <div class="current-profile-image">
                        <?php if ($profile_image_id) :
                            echo wp_get_attachment_image($profile_image_id, 'thumbnail');
                        else : ?>
                            <div class="profile-placeholder">
                                <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="profile_image" id="profile_image" accept="image/*" />
                    <small><?php _e('Recommended size: 300x300 pixels', 'tipping-addons-jetengine'); ?></small>
                </div>

                <p>
                    <label for="first_name"><?php _e('First Name', 'tipping-addons-jetengine'); ?></label>
                    <input type="text"
                        name="first_name"
                        id="first_name"
                        value="<?php echo esc_attr($first_name); ?>"
                        placeholder="<?php _e('Enter your first name', 'tipping-addons-jetengine'); ?>" />
                </p>

                <p>
                    <label for="last_name"><?php _e('Last Name', 'tipping-addons-jetengine'); ?></label>
                    <input type="text"
                        name="last_name"
                        id="last_name"
                        value="<?php echo esc_attr($last_name); ?>"
                        placeholder="<?php _e('Enter your last name', 'tipping-addons-jetengine'); ?>" />
                </p>

                <p>
                    <label for="display_name"><?php _e('Display Name', 'tipping-addons-jetengine'); ?></label>
                    <input type="text"
                        name="display_name"
                        id="display_name"
                        value="<?php echo esc_attr($display_name); ?>"
                        readonly
                        disabled />
                    <small><?php _e('This is how your name will appear publicly. You can change your display name from account details tab', 'tipping-addons-jetengine'); ?></small>
                </p>

                <p>
                    <label for="artist_bio"><?php _e('Bio', 'tipping-addons-jetengine'); ?></label>
                    <textarea name="artist_bio"
                        id="artist_bio"
                        rows="5"
                        placeholder="<?php _e('Tell your fans about yourself', 'tipping-addons-jetengine'); ?>"><?php echo esc_textarea($artist_bio); ?></textarea>
                    <small><?php _e('Write a short bio to introduce yourself to your fans', 'tipping-addons-jetengine'); ?></small>
                </p>

                <p>
                    <input type="hidden" name="action" value="update_artist_profile" />
                    <input type="hidden" name="profile_nonce" value="<?php echo wp_create_nonce('update_artist_profile_nonce'); ?>" />
                    <button type="submit" class="button"><?php _e('Save Changes', 'tipping-addons-jetengine'); ?></button>
                </p>
            </form>

            <script>
                jQuery(document).ready(function($) {
                    $('#artist-profile-form').on('submit', function(e) {
                        e.preventDefault();

                        var formData = new FormData(this);

                        $.ajax({
                            url: artist_vendor_params.ajax_url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                if (response.success) {
                                    $('.form-message').removeClass('error').addClass('success').html(response.data.message);
                                    if (response.data.reload) {
                                        setTimeout(function() {
                                            window.location.reload();
                                        }, 1000);
                                    }
                                } else {
                                    $('.form-message').removeClass('success').addClass('error').html(response.data.message);
                                }
                            }
                        });
                    });
                });
            </script>
        </div>
<?php
    }

    public function process_profile_update()
    {
        // Verify nonce and check if user is logged in
        if (
            !isset($_POST['profile_nonce']) ||
            !wp_verify_nonce($_POST['profile_nonce'], 'update_artist_profile_nonce') ||
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'tipping-addons-jetengine')]);
        }

        $user_id = get_current_user_id();

        // Update basic user data
        $userdata = array(
            'ID'           => $user_id,
            'first_name'   => sanitize_text_field($_POST['first_name']),
            'last_name'    => sanitize_text_field($_POST['last_name']),
            'display_name' => sanitize_text_field($_POST['first_name'] . ' ' . $_POST['last_name'])
        );

        $user_id = wp_update_user($userdata);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        // Update artist bio
        update_user_meta($user_id, 'artist_bio', wp_kses_post($_POST['artist_bio']));

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            // If there's an existing profile image, delete it
            $old_image_id = get_user_meta($user_id, 'profile_image_id', true);
            if ($old_image_id) {
                wp_delete_attachment($old_image_id, true);
            }

            // Upload new image
            $profile_image_id = media_handle_upload('profile_image', 0);
            if (is_wp_error($profile_image_id)) {
                wp_send_json_error(['message' => $profile_image_id->get_error_message()]);
            } else {
                update_user_meta($user_id, 'profile_image_id', $profile_image_id);
            }
        }

        // Also update these fields in user meta for compatibility
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));

        wp_send_json_success([
            'message' => __('Profile updated successfully!', 'tipping-addons-jetengine'),
            'reload' => true
        ]);
    }

    private function get_artist_song_count($user_id)
    {
        $args = [
            'post_type' => 'product',
            'author' => $user_id,
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => -1,
        ];

        $products = get_posts($args);
        return count($products);
    }
}

// Initialize the class
new ArtistVendor();
