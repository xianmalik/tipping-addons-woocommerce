<?php
if (!defined('ABSPATH')) {
    exit;
}

class ArtistVendor
{
    public function __construct()
    {
        // Registration shortcode
        add_shortcode('artist_registration_form', [$this, 'registration_form_shortcode']);

        // Registration AJAX
        add_action('wp_ajax_nopriv_register_artist', [$this, 'process_registration']);
        add_action('wp_ajax_register_artist',        [$this, 'process_registration']);

        // Dashboard
        require_once PAPER_TIPPING_PATH . 'includes/artist/dashboard.php';
        new DashboardHandler();

        // Song management handlers
        require_once PAPER_TIPPING_PATH . 'includes/artist/songs/add.php';
        require_once PAPER_TIPPING_PATH . 'includes/artist/songs/edit.php';
        require_once PAPER_TIPPING_PATH . 'includes/artist/songs/delete.php';
        require_once PAPER_TIPPING_PATH . 'includes/artist/songs/manage.php';

        $add_handler    = new AddProductHandler();
        $edit_handler   = new EditProductHandler();
        $delete_handler = new DeleteProductHandler();
        $manage_handler = new ManageSongsHandler();

        // WooCommerce account endpoints
        add_action('init',                               [$this, 'add_endpoints']);
        add_filter('woocommerce_account_menu_items',     [$this, 'add_artist_menu_items']);
        add_action('template_redirect',                  [$this, 'check_user_logged_in']);

        add_action('woocommerce_account_artist-sales_endpoint',   [$this, 'artist_sales_content']);
        add_action('woocommerce_account_artist-profile_endpoint', [$this, 'artist_profile_content']);
        add_action('woocommerce_account_manage-songs_endpoint',   [$manage_handler, 'handle_manage']);
        add_action('woocommerce_account_add-song_endpoint',       [$add_handler,    'handle_add']);
        add_action('woocommerce_account_edit-song_endpoint',      [$edit_handler,   'handle_edit']);
        add_action('woocommerce_account_delete-song_endpoint',    [$delete_handler, 'handle_delete']);

        // Product submission AJAX
        add_action('wp_ajax_submit_artist_product', [$add_handler,  'process_product_submission']);
        add_action('wp_ajax_update_artist_product', [$edit_handler, 'process_product_update']);

        // Profile update AJAX
        add_action('wp_ajax_update_artist_profile', [$this, 'process_profile_update']);

        // Allow WooCommerce to serve files from the uploads directory
        add_filter('woocommerce_downloadable_products_folder_path', [$this, 'add_uploads_to_allowed_paths']);

        // Grant product management capabilities to standard user roles (required for artist uploads)
        add_action('init', [$this, 'add_product_capabilities_to_users']);

        // Login form signup prompt
        add_action('woocommerce_login_form_start', [$this, 'add_signup_prompt_to_login']);

        // Enqueue global styles and scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    // -------------------------------------------------------------------------
    // Endpoints & menus
    // -------------------------------------------------------------------------

    public function add_endpoints()
    {
        add_rewrite_endpoint('artist-sales',   EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('manage-songs',   EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('add-song',       EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('edit-song',      EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('delete-song',    EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('artist-profile', EP_ROOT | EP_PAGES);

        if (!get_option('artist_vendor_flush_rewrite_rules')) {
            flush_rewrite_rules();
            update_option('artist_vendor_flush_rewrite_rules', true);
        }
    }

    public function add_artist_menu_items($items)
    {
        if (!is_user_logged_in()) {
            return $items;
        }

        $user      = wp_get_current_user();
        $is_artist = in_array('music_artist_vendor', $user->roles);
        $is_admin  = in_array('administrator', $user->roles);

        if (!$is_artist && !$is_admin) {
            return $items;
        }

        $hidden = ['orders', 'downloads', 'song-downloads', 'payment-methods', 'edit-address'];
        $new_items = [];

        foreach ($items as $key => $label) {
            if (!in_array($key, $hidden, true)) {
                $new_items[$key] = $label;
            }

            if ($key === 'dashboard') {
                $new_items['artist-profile'] = __('Artist Profile', 'paper-tipping-addons');
                $new_items['artist-sales']   = __('My Tips', 'paper-tipping-addons');
                $new_items['manage-songs']   = __('Manage Songs', 'paper-tipping-addons');
            }
        }

        return $new_items;
    }

    public function check_user_logged_in()
    {
        global $wp_query;

        $protected = ['artist-sales', 'manage-songs', 'add-song', 'edit-song', 'delete-song'];

        foreach ($protected as $endpoint) {
            if (isset($wp_query->query_vars[$endpoint]) && !is_user_logged_in()) {
                wp_redirect(wc_get_page_permalink('myaccount'));
                exit;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    public function registration_form_shortcode()
    {
        if (is_user_logged_in()) {
            return '<p>' . __('You are already registered and logged in.', 'paper-tipping-addons') . '</p>';
        }

        ob_start();
        include PAPER_TIPPING_PATH . 'templates/artist/registration-form.php';
        return ob_get_clean();
    }

    public function process_registration()
    {
        if (!isset($_POST['artist_nonce']) || !wp_verify_nonce($_POST['artist_nonce'], 'artist_registration_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'paper-tipping-addons')]);
        }

        $username         = sanitize_user($_POST['artist_username'] ?? '');
        $email            = sanitize_email($_POST['artist_email'] ?? '');
        $password         = $_POST['artist_password'] ?? '';
        $password_confirm = $_POST['artist_password_confirm'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(['message' => __('Please fill all required fields', 'paper-tipping-addons')]);
        }

        if ($password !== $password_confirm) {
            wp_send_json_error(['message' => __('Passwords do not match', 'paper-tipping-addons')]);
        }

        if (username_exists($username)) {
            wp_send_json_error(['message' => __('Username already exists', 'paper-tipping-addons')]);
        }

        if (email_exists($email)) {
            wp_send_json_error(['message' => __('Email already exists', 'paper-tipping-addons')]);
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        $user = new WP_User($user_id);
        $user->set_role('music_artist_vendor');

        if (!empty($_POST['artist_first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['artist_first_name']));
        }
        if (!empty($_POST['artist_last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['artist_last_name']));
        }

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success([
            'message'  => __('Registration successful! Redirecting...', 'paper-tipping-addons'),
            'redirect' => wc_get_page_permalink('myaccount'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Artist profile
    // -------------------------------------------------------------------------

    public function artist_profile_content()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id          = get_current_user_id();
        $user             = get_userdata($user_id);
        $artist_bio       = get_user_meta($user_id, 'artist_bio', true);
        $profile_image_id = get_user_meta($user_id, 'profile_image_id', true);
        $first_name       = get_user_meta($user_id, 'first_name', true);
        $last_name        = get_user_meta($user_id, 'last_name', true);
        $display_name     = $user->display_name;

        include PAPER_TIPPING_PATH . 'templates/artist/profile-form.php';
    }

    public function process_profile_update()
    {
        if (
            !isset($_POST['profile_nonce']) ||
            !wp_verify_nonce($_POST['profile_nonce'], 'update_artist_profile_nonce') ||
            !is_user_logged_in()
        ) {
            wp_send_json_error(['message' => __('Security check failed', 'paper-tipping-addons')]);
        }

        $user_id    = get_current_user_id();
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name  = sanitize_text_field($_POST['last_name'] ?? '');

        $result = wp_update_user([
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        update_user_meta($user_id, 'artist_bio',   wp_kses_post($_POST['artist_bio'] ?? ''));
        update_user_meta($user_id, 'first_name',   $first_name);
        update_user_meta($user_id, 'last_name',    $last_name);

        if (!empty($_FILES['profile_image']['name'])) {
            $old_image_id = get_user_meta($user_id, 'profile_image_id', true);
            if ($old_image_id) {
                wp_delete_attachment($old_image_id, true);
            }

            if (!function_exists('media_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }

            $profile_image_id = media_handle_upload('profile_image', 0);
            if (is_wp_error($profile_image_id)) {
                wp_send_json_error(['message' => $profile_image_id->get_error_message()]);
            }
            update_user_meta($user_id, 'profile_image_id', $profile_image_id);
        }

        wp_send_json_success(['message' => __('Profile updated successfully!', 'paper-tipping-addons'), 'reload' => true]);
    }

    // -------------------------------------------------------------------------
    // Artist sales
    // -------------------------------------------------------------------------

    public function artist_sales_content()
    {
        if (!is_user_logged_in()) {
            return;
        }

        $user_id     = get_current_user_id();
        $products    = ArtistQuery::get_artist_products($user_id);
        $product_ids = wp_list_pluck($products, 'ID');

        if (empty($product_ids)) {
            echo '<p>' . __("You haven't created any songs yet.", 'paper-tipping-addons') . '</p>';
            echo '<p><a href="' . wc_get_account_endpoint_url('add-song') . '" class="button">' . __('Add Your First Product', 'paper-tipping-addons') . '</a></p>';
            return;
        }

        $orders    = wc_get_orders(['limit' => -1, 'status' => ['completed', 'processing'], 'return' => 'ids']);
        $tips_data = [];
        $total_sales = 0.0;

        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                $pid = $item->get_product_id();
                if (!in_array($pid, $product_ids, true)) {
                    continue;
                }
                if (!isset($tips_data[$pid])) {
                    $tips_data[$pid] = ['name' => $item->get_product()->get_name(), 'quantity' => 0, 'total' => 0];
                }
                $tips_data[$pid]['quantity'] += $item->get_quantity();
                $tips_data[$pid]['total']    += $item->get_total();
                $total_sales                 += $item->get_total();
            }
        }

        if ($total_sales > 0) {
            include PAPER_TIPPING_PATH . 'templates/artist/sales-content.php';
        } else {
            echo '<p>' . __('No tips data available yet.', 'paper-tipping-addons') . '</p>';
        }
    }

    // -------------------------------------------------------------------------
    // Capabilities & misc
    // -------------------------------------------------------------------------

    public function add_uploads_to_allowed_paths($path)
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'];
    }

    public function add_product_capabilities_to_users()
    {
        $caps  = ['upload_files', 'edit_products', 'edit_published_products', 'delete_products', 'publish_products', 'read_private_products', 'manage_product_terms', 'assign_product_terms'];
        $roles = ['subscriber', 'customer', 'contributor', 'author'];

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($caps as $cap) {
                    $role->add_cap($cap);
                }
            }
        }
    }

    public function add_signup_prompt_to_login()
    {
        ?>
        <p class="signup-prompt">
            <?php _e('New to Musicbae? ', 'paper-tipping-addons'); ?>
            <a href="<?php echo esc_url(site_url('/artist-registration/')); ?>"><?php _e('Sign up', 'paper-tipping-addons'); ?></a>
        </p>
        <?php
    }

    public function enqueue_scripts()
    {
        wp_enqueue_style('tipping-addons-woocommerce-login', PAPER_TIPPING_URL . 'assets/css/woocommerce/woocommerce-login.css', [], '1.0.0');
        wp_enqueue_style('tipping-addons-registration',      PAPER_TIPPING_URL . 'assets/css/artist/artist-vendor.css', [], '1.0.1');
        wp_enqueue_style('tipping-addons-my-account',        PAPER_TIPPING_URL . 'assets/css/artist/my-account.css', [], '1.0.0');

        wp_enqueue_script('tipping-addons-artist-vendor', PAPER_TIPPING_URL . 'assets/js/artist/artist-vendor.js', ['jquery'], '1.0.0', true);

        wp_localize_script('tipping-addons-artist-vendor', 'artist_vendor_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('artist_vendor_nonce'),
        ]);

        // Artist profile JS — only on the profile account page
        if (is_wc_endpoint_url('artist-profile')) {
            wp_enqueue_script('tipping-addons-artist-profile', PAPER_TIPPING_URL . 'assets/js/artist/artist-profile.js', ['jquery'], '1.0.0', true);
        }

        // Delete song CSS — only on the delete-song page
        if (is_wc_endpoint_url('delete-song')) {
            wp_enqueue_style('tipping-addons-delete-song', PAPER_TIPPING_URL . 'assets/css/artist/delete-song.css', [], '1.0.0');
        }
    }
}

new ArtistVendor();
