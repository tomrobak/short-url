<?php
/**
 * Short URL Plugin Activator
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Activator Class
 */
class Short_URL_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        self::create_database_tables();
        self::create_capabilities();
        self::set_default_options();
        
        // Add a flag to redirect to the welcome page
        set_transient('short_url_activation_redirect', true, 30);
    }

    /**
     * Create database tables
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // URLs table
        $table_urls = $wpdb->prefix . 'short_urls';
        
        $sql_urls = "CREATE TABLE $table_urls (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(255) NOT NULL,
            destination_url text NOT NULL,
            title varchar(255) DEFAULT '',
            description text DEFAULT '',
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            password varchar(255) DEFAULT NULL,
            redirect_type smallint(3) DEFAULT 301,
            nofollow tinyint(1) DEFAULT 0,
            sponsored tinyint(1) DEFAULT 0,
            forward_parameters tinyint(1) DEFAULT 0,
            track_visits tinyint(1) DEFAULT 1,
            visits bigint(20) unsigned DEFAULT 0,
            group_id bigint(20) unsigned DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY destination_url (destination_url(191)),
            KEY created_by (created_by),
            KEY group_id (group_id)
        ) $charset_collate;";
        
        // Analytics table
        $table_analytics = $wpdb->prefix . 'short_url_analytics';
        
        $sql_analytics = "CREATE TABLE $table_analytics (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url_id bigint(20) unsigned NOT NULL,
            visitor_ip varchar(45) DEFAULT NULL,
            visitor_user_agent text DEFAULT NULL,
            referrer_url text DEFAULT NULL,
            visited_at datetime NOT NULL,
            browser varchar(255) DEFAULT NULL,
            browser_version varchar(255) DEFAULT NULL,
            operating_system varchar(255) DEFAULT NULL,
            device_type varchar(20) DEFAULT NULL,
            country_code varchar(2) DEFAULT NULL,
            country_name varchar(255) DEFAULT NULL,
            region varchar(255) DEFAULT NULL,
            city varchar(255) DEFAULT NULL,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY url_id (url_id),
            KEY visited_at (visited_at),
            KEY country_code (country_code),
            KEY browser (browser),
            KEY device_type (device_type)
        ) $charset_collate;";
        
        // Link groups table
        $table_groups = $wpdb->prefix . 'short_url_groups';
        
        $sql_groups = "CREATE TABLE $table_groups (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT '',
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            links_count bigint(20) unsigned DEFAULT 0,
            PRIMARY KEY  (id),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Custom domains table
        $table_domains = $wpdb->prefix . 'short_url_domains';
        
        $sql_domains = "CREATE TABLE $table_domains (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            domain_name varchar(255) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY domain_name (domain_name),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Include WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create the tables
        // Create the tables and check results
        $dbDelta_results = dbDelta(array($sql_urls, $sql_analytics, $sql_groups, $sql_domains), false); // Pass false to prevent direct output

        // Log any errors reported by dbDelta
        foreach ($dbDelta_results as $table_name => $result) {
            if (strpos($result, 'Created table') !== false || strpos($result, 'Updated table') !== false) {
                 error_log("Short URL Activator: dbDelta successfully processed table '{$table_name}'. Result: {$result}");
            } else {
                // Log potential errors or unexpected messages from dbDelta
                error_log("Short URL Activator Warning/Error: dbDelta result for table '{$table_name}': {$result}");
            }
        }

        // Optional: Add a secondary check immediately after dbDelta
        global $wpdb;
        $tables_to_check = [
            $wpdb->prefix . 'short_urls' => $table_urls,
            $wpdb->prefix . 'short_url_analytics' => $table_analytics,
            $wpdb->prefix . 'short_url_groups' => $table_groups,
            $wpdb->prefix . 'short_url_domains' => $table_domains,
        ];
        foreach ($tables_to_check as $prefixed_name => $variable_name) {
             if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefixed_name)) != $prefixed_name) {
                 error_log("Short URL Activator Error: Verification failed immediately after dbDelta for table '{$prefixed_name}'.");
             }
        }
        
        // Save database version
        update_option('short_url_db_version', SHORT_URL_VERSION);
    }
    
    /**
     * Create user capabilities
     */
    private static function create_capabilities() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        // Capabilities for admin
        $capabilities = array(
            'manage_short_urls' => true,
            'create_short_urls' => true,
            'edit_short_urls' => true,
            'delete_short_urls' => true,
            'manage_short_url_groups' => true,
            'view_short_url_analytics' => true,
            'manage_short_url_settings' => true,
            'import_export_short_urls' => true,
        );
        
        $roles = array('administrator', 'editor');
        
        foreach ($roles as $role) {
            $role_obj = $wp_roles->get_role($role);
            
            if (!$role_obj) {
                continue;
            }
            
            foreach ($capabilities as $cap => $grant) {
                // Add only for administrators
                if ($role === 'administrator' || ($role === 'editor' && $cap !== 'manage_short_url_settings')) {
                    $result = $role_obj->add_cap($cap, $grant);
                    if (!$result) {
                        // This might not return false on failure, WP capability functions often don't.
                        // But we add logging just in case, or if behavior changes.
                        error_log("Short URL Activator Warning: Attempted to add capability '{$cap}' to role '{$role}', but add_cap returned false (or non-true).");
                    }
                    // We can also immediately check if the cap was added
                    // Note: Re-getting the role object might be needed if it's not updated by reference.
                    $role_check = get_role($role);
                    if ($role_check && !$role_check->has_cap($cap)) {
                         error_log("Short URL Activator Error: Verification failed immediately after adding capability '{$cap}' to role '{$role}'.");
                    }
                }
            }
        }
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $options = array(
            'version' => SHORT_URL_VERSION,
            'slug_length' => 4,
            'auto_create_for_post_types' => array('post', 'page'),
            'redirect_type' => 301,
            'track_visits' => true,
            'anonymize_ip' => false,
            'filter_bots' => true,
            'excluded_ips' => array(),
            'data_retention_period' => 365, // days
            'display_short_url' => array(
                'posts' => array(
                    'above' => false,
                    'below' => true,
                ),
                'pages' => array(
                    'above' => false,
                    'below' => true,
                ),
                'excerpt' => array(
                    'above' => false,
                    'below' => true,
                ),
            ),
            'public_url_form' => false,
            'link_prefix' => '',
            'case_sensitive' => false,
        );
        
        // Only set options if they don't exist
        foreach ($options as $option_name => $option_value) {
            if (get_option('short_url_' . $option_name) === false) {
                update_option('short_url_' . $option_name, $option_value);
            }
        }
    }
} 