<?php
/**
 * Performance fixes for the site
 */
class TippingAddons_Performance_Fixes {
    
    public function __construct() {
        // Increase execution time for admin pages
        add_action('admin_init', [$this, 'increase_execution_time']);
        
        // Fix deprecated PHP notices
        add_action('init', [$this, 'fix_deprecated_notices']);
        
        // Disable problematic plugins on WooCommerce admin pages
        add_action('admin_init', [$this, 'disable_problematic_plugins_on_woocommerce']);
    }
    
    /**
     * Disable problematic plugins on WooCommerce admin pages
     */
    public function disable_problematic_plugins_on_woocommerce() {
        global $pagenow;
        
        // Check if we're on a WooCommerce admin page
        $is_woo_admin = (
            is_admin() && 
            (
                ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'product') ||
                ($pagenow == 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) == 'product') ||
                ($pagenow == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == 'product')
            )
        );
        
        if ($is_woo_admin) {
            // Disable Ultimate Social Media Icons plugin on WooCommerce pages
            if (function_exists('sfsi_activate_plugin')) {
                remove_action('admin_notices', 'sfsi_admin_notice', 10);
                remove_action('admin_footer', 'sfsi_check_popup', 10);
                remove_action('admin_footer', 'sfsi_frontline_admin_notice', 10);
                
                // Remove any other hooks from the plugin that might be causing issues
                global $wp_filter;
                foreach ($wp_filter as $tag => $hook) {
                    if (strpos($tag, 'sfsi_') === 0) {
                        remove_all_actions($tag);
                    }
                }
            }
        }
    }
    
    /**
     * Increase PHP execution time for admin pages
     */
    public function increase_execution_time() {
        // Only apply to admin pages
        if (is_admin()) {
            // Set execution time to 120 seconds (2 minutes)
            @ini_set('max_execution_time', 120);
            
            // Also set memory limit higher if needed
            @ini_set('memory_limit', '256M');
        }
    }
    
    /**
     * Fix deprecated PHP notices by adding null checks
     */
    public function fix_deprecated_notices() {
        // Add filters to common WordPress functions that might receive null values
        add_filter('sanitize_title', [$this, 'null_safe_filter'], 5);
        add_filter('the_title', [$this, 'null_safe_filter'], 5);
        add_filter('the_content', [$this, 'null_safe_filter'], 5);
    }
    
    /**
     * Make sure string functions don't receive null values
     */
    public function null_safe_filter($input) {
        // Convert null to empty string to prevent PHP 8+ deprecation notices
        if ($input === null) {
            return '';
        }
        return $input;
    }
}

// Initialize the class
new TippingAddons_Performance_Fixes();