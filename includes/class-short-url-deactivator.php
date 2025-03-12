<?php
/**
 * Short URL Plugin Deactivator
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Deactivator Class
 */
class Short_URL_Deactivator {
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Ask user if they want to delete all data
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            self::add_deactivation_transient();
        }
    }
    
    /**
     * Clear scheduled events
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('short_url_cleanup_analytics');
        wp_clear_scheduled_hook('short_url_track_visit');
    }
    
    /**
     * Add a transient to show a notice about data deletion
     */
    private static function add_deactivation_transient() {
        set_transient('short_url_deactivated', true, 5 * MINUTE_IN_SECONDS);
    }
    
    /**
     * Delete all plugin data
     */
    public static function delete_all_data() {
        global $wpdb;
        
        // Delete plugin tables
        $tables = array(
            $wpdb->prefix . 'short_urls',
            $wpdb->prefix . 'short_url_analytics',
            $wpdb->prefix . 'short_url_groups',
            $wpdb->prefix . 'short_url_domains',
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        // Delete all options
        $options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'short_url_%'"
        );
        
        foreach ($options as $option) {
            delete_option($option->option_name);
        }
        
        // Delete post meta
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_short_url_id'");
        
        // Delete user capabilities
        $capabilities = array(
            'manage_short_urls',
            'create_short_urls',
            'edit_short_urls',
            'delete_short_urls',
            'manage_short_url_groups',
            'view_short_url_analytics',
            'manage_short_url_settings',
            'import_export_short_urls',
        );
        
        // Get all roles
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        // Remove capabilities from all roles
        foreach ($wp_roles->roles as $role_name => $role_info) {
            $role = $wp_roles->get_role($role_name);
            
            if (!$role) {
                continue;
            }
            
            foreach ($capabilities as $cap) {
                $role->remove_cap($cap);
            }
        }
        
        // Delete transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_short_url_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_short_url_%'");
    }
    
    /**
     * Show deactivation notice
     */
    public static function show_deactivation_notice() {
        // Only show the notice if the transient exists and we're on the plugins page
        if (!get_transient('short_url_deactivated') || !is_admin() || !function_exists('get_current_screen')) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'plugins') {
            return;
        }
        
        // Delete the transient
        delete_transient('short_url_deactivated');
        
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('Short URL Plugin Deactivated', 'short-url'); ?></strong>
            </p>
            <p>
                <?php esc_html_e('Would you like to delete all Short URL data from the database?', 'short-url'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=short_url_delete_data'), 'short_url_delete_data')); ?>" class="button button-primary" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete all Short URL data? This cannot be undone!', 'short-url'); ?>');">
                    <?php esc_html_e('Delete All Data', 'short-url'); ?>
                </a>
                <a href="#" class="button" onclick="jQuery(this).closest('.notice').remove(); return false;">
                    <?php esc_html_e('Keep Data', 'short-url'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Handle the data deletion request
     */
    public static function handle_data_deletion() {
        // Check nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'short_url_delete_data')) {
            wp_die(__('Security check failed.', 'short-url'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'short-url'));
        }
        
        // Delete all data
        self::delete_all_data();
        
        // Redirect back to plugins page with a success message
        wp_safe_redirect(add_query_arg('short_url_data_deleted', '1', admin_url('plugins.php')));
        exit;
    }
    
    /**
     * Show a success notice after data deletion
     */
    public static function show_data_deleted_notice() {
        if (!isset($_GET['short_url_data_deleted'])) {
            return;
        }
        
        ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <?php esc_html_e('All Short URL data has been deleted from the database.', 'short-url'); ?>
            </p>
        </div>
        <?php
    }
} 