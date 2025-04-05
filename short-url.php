<?php
/**
 * Plugin Name: Short URL
 * Plugin URI: https://github.com/tomrobak/short-url
 * Description: A modern URL shortener with analytics, custom domains, and more. The fastest way to link without sacrificing your brand or analytics!
 * Version: 1.2.9.2
 * Author: Tom Robak
 * Author URI: https://tomrobak.com
 * Text Domain: short-url
 * Domain Path: /languages
 * Requires at least: 6.7
 * Requires PHP: 8.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Plugin Folder Name: short-url
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check PHP version
if (version_compare(PHP_VERSION, '8.0', '<')) {
    function short_url_php_requirement_notice() {
        ?>
        <div class="notice notice-error">
            <p>Short URL requires PHP 8.0 or higher. Please upgrade your PHP version to use this plugin.</p>
        </div>
        <?php
    }
    add_action('admin_notices', 'short_url_php_requirement_notice');
    return;
}

// Check WordPress version
if (version_compare(get_bloginfo('version'), '6.7', '<')) {
    function short_url_wp_requirement_notice() {
        ?>
        <div class="notice notice-error">
            <p>Short URL requires WordPress 6.7 or higher. Please upgrade your WordPress installation to use this plugin.</p>
        </div>
        <?php
    }
    add_action('admin_notices', 'short_url_wp_requirement_notice');
    return;
}

// Define plugin constants
define('SHORT_URL_VERSION', '1.2.9.2');
define('SHORT_URL_VERSION_NAME', ''); // No codename for patch
define('SHORT_URL_FULL_VERSION', SHORT_URL_VERSION); // No codename for patch
define('SHORT_URL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SHORT_URL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SHORT_URL_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SHORT_URL_SITE_URL', site_url());

/**
 * Main Plugin Class
 */
final class Short_URL {
    /**
     * Instance of the plugin
     *
     * @var Short_URL
     */
    private static ?Short_URL $instance = null;

    /** @var bool Flag for DB setup error */
    private static bool $db_error = false;
    /** @var bool Flag for capability setup error */
    private static bool $caps_error = false;

    /**
     * Get the singleton instance
     *
     * @return Short_URL
     */
    public static function get_instance(): Short_URL {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Class constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
        
        // Load text domain on init hook with priority 1 to ensure it loads before anything else
        add_action('init', array($this, 'load_textdomain'), 1);

        // Hook for displaying admin notices based on flags set during verification
        add_action('admin_notices', array($this, 'display_setup_notices'));
    }

    /**
     * Include required files
     */
    private function includes(): void {
        // Core includes
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-activator.php';
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-deactivator.php';
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-db.php';
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-generator.php';
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-analytics.php';
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-redirect.php';
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-api.php';
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-utils.php';
        require_once SHORT_URL_PLUGIN_DIR . 'includes/class-short-url-updater.php';
        
        // Admin includes
        if (is_admin()) {
            require_once SHORT_URL_PLUGIN_DIR . 'admin/class-short-url-admin.php';
            require_once SHORT_URL_PLUGIN_DIR . 'admin/class-short-url-gutenberg.php';
            
            // Initialize admin classes after translations are loaded
            add_action('init', function() {
                Short_URL_Admin::get_instance();
                Short_URL_Gutenberg::get_instance();
            }, 20); // Priority 20 to ensure it runs well after load_textdomain which is on priority 1
        }
    }

    /**
     * Initialize plugin hooks
     */
    private function init_hooks(): void {
        // Activation / Deactivation
        register_activation_hook(__FILE__, array('Short_URL_Activator', 'activate'));
        register_deactivation_hook(__FILE__, array('Short_URL_Deactivator', 'deactivate'));

        // Verify installation on admin init (checks DB tables, capabilities)
        add_action('admin_init', array($this, 'verify_installation'));

        // Check for updates
        add_action('admin_init', array($this, 'check_for_updates'));
        
        // Handle redirects
        add_action('init', array($this, 'handle_redirect'), 5);
        
        // Register REST API
        add_action('rest_api_init', array($this, 'register_rest_api'));
        
        // Schedule cleanup
        add_action('wp', array($this, 'schedule_events'));
        
        // Handle AJAX visit tracking
        add_action('wp_ajax_nopriv_short_url_track_visit', array($this, 'track_visit'));
        add_action('wp_ajax_short_url_track_visit', array($this, 'track_visit'));
        
        // Handle scheduled visit tracking
        add_action('short_url_track_visit', array('Short_URL_Analytics', 'record_visit'));
        
        // Handle scheduled cleanup
        add_action('short_url_cleanup_analytics', array('Short_URL_Analytics', 'run_cleanup'));
        
        // Add WooCommerce integration
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_after_add_to_cart_form', array($this, 'add_short_url_to_product'));
            add_filter('woocommerce_structured_data_product', array($this, 'add_short_url_to_structured_data'), 10, 2);
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain(): void {
        // First try to load from the languages directory in the plugin
        $loaded = load_plugin_textdomain(
            'short-url',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
        
        if (!$loaded) {
            // Log the failure to load the textdomain
            error_log('Short URL: Failed to load textdomain from ' . dirname(plugin_basename(__FILE__)) . '/languages');
            
            // Try to load from the WP languages directory as a fallback
            $loaded = load_textdomain(
                'short-url',
                WP_LANG_DIR . '/plugins/short-url-' . determine_locale() . '.mo'
            );
            
            if ($loaded) {
                error_log('Short URL: Successfully loaded textdomain from WP_LANG_DIR');
            } else {
                error_log('Short URL: Failed to load textdomain from WP_LANG_DIR');
            }
        } else {
            error_log('Short URL: Successfully loaded textdomain from plugin directory');
        }
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_rest_api(): void {
        if (class_exists('Short_URL_API')) {
            $api = new Short_URL_API();
            $api->register_routes();
        }
    }
    
    /**
     * Schedule events
     */
    public function schedule_events(): void {
        Short_URL_Analytics::schedule_cleanup();
    }
    
    /**
     * Track a visit via AJAX
     */
    public function track_visit(): void {
        // Check nonce for frontend requests
        if (!check_ajax_referer('short_url_track_visit', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get URL ID
        $url_id = isset($_POST['url_id']) ? intval($_POST['url_id']) : 0;
        
        if (!$url_id) {
            wp_send_json_error('Invalid URL ID');
        }
        
        // Record visit
        $result = Short_URL_Analytics::record_visit($url_id);
        
        if ($result) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to record visit');
        }
    }
    
    /**
     * Handle redirect for short URLs
     */
    public function handle_redirect(): void {
        // Get the requested URL path
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
        
        // Skip if in admin area or if path is empty
        if (is_admin() || empty($path)) {
            return;
        }
        
        // Check if this is a short URL
        if (class_exists('Short_URL_Redirect')) {
            Short_URL_Redirect::redirect($path);
        }
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_updates(): void {
        // This method is no longer needed as we're initializing the updater on plugins_loaded
    }
    
    /**
     * Add short URL to WooCommerce product page
     * 
     * @param int $product WC_Product object
     */
    public function add_short_url_to_product(): void {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        $url_id = get_post_meta($product_id, '_short_url_id', true);
        
        if (!$url_id) {
            // Create a short URL for the product if it doesn't exist
            $auto_create = get_option('short_url_auto_create_for_post_types', array());
            
            if (in_array('product', $auto_create)) {
                $result = Short_URL_Generator::create_for_post($product_id);
                
                if (!is_wp_error($result)) {
                    $url_id = $result['url_id'];
                }
            } else {
                return;
            }
        }
        
        if ($url_id) {
            $db = new Short_URL_DB();
            $url = $db->get_url($url_id);
            
            if ($url) {
                $short_url = Short_URL_Generator::get_short_url($url->slug);
                
                // Check if we should display the short URL
                $display_settings = get_option('short_url_display_short_url', array());
                
                if (isset($display_settings['product']) && 
                    (isset($display_settings['product']['below']) && $display_settings['product']['below'])) {
                    
                    echo '<div class="short-url-product">';
                    echo '<p class="short-url-product-label">' . esc_html__('Share this product:', 'short-url') . '</p>';
                    echo '<div class="short-url-product-link">';
                    echo '<a href="' . esc_url($short_url) . '" target="_blank">' . esc_html($short_url) . '</a>';
                    echo '<button class="short-url-copy-button" data-clipboard-text="' . esc_attr($short_url) . '">';
                    echo '<span class="short-url-copy-icon"></span>';
                    echo '</button>';
                    echo '</div>';
                    echo '</div>';
                    
                    // Enqueue script for copy button
                    wp_enqueue_script(
                        'clipboard',
                        SHORT_URL_PLUGIN_URL . 'admin/js/clipboard.min.js',
                        array(),
                        '2.0.11',
                        true
                    );
                    
                    wp_add_inline_script('clipboard', '
                        document.addEventListener("DOMContentLoaded", function() {
                            var clipboard = new ClipboardJS(".short-url-copy-button");
                            
                            clipboard.on("success", function(e) {
                                var button = e.trigger;
                                button.innerHTML = "<span class=\"short-url-copy-success\">' . esc_js(__('Copied!', 'short-url')) . '</span>";
                                
                                setTimeout(function() {
                                    button.innerHTML = "<span class=\"short-url-copy-icon\"></span>";
                                }, 2000);
                            });
                        });
                    ');
                    
                    wp_enqueue_style(
                        'short-url-frontend',
                        SHORT_URL_PLUGIN_URL . 'public/css/short-url-frontend.css',
                        array(),
                        SHORT_URL_VERSION
                    );
                }
            }
        }
    }
    
    /**
     * Add short URL to WooCommerce structured data
     * 
     * @param array      $markup  Structured data
     * @param WC_Product $product Product object
     * @return array Modified structured data
     */
    public function add_short_url_to_structured_data($markup, $product): array {
        if (!$product) {
            return $markup;
        }
        
        $product_id = $product->get_id();
        $url_id = get_post_meta($product_id, '_short_url_id', true);
        
        if ($url_id) {
            $db = new Short_URL_DB();
            $url = $db->get_url($url_id);
            
            if ($url) {
                $short_url = Short_URL_Generator::get_short_url($url->slug);
                
                // Add short URL as sameAs property
                if (!isset($markup['sameAs'])) {
                    $markup['sameAs'] = array();
                } elseif (!is_array($markup['sameAs'])) {
                    $markup['sameAs'] = array($markup['sameAs']);
                }
                
                $markup['sameAs'][] = $short_url;
            }
        }
        
        return $markup;
    }

    /**
     * Verify plugin installation (DB tables, capabilities) and run upgrades if needed.
     * Runs on admin_init to ensure everything is set up correctly.
     */
    public function verify_installation(): void {
        $installed_version = get_option('short_url_install_verified_version', '0');

        // Only run checks if the version is different or first install
        if (version_compare($installed_version, SHORT_URL_VERSION, '<')) {
            error_log('Short URL: Verifying installation/upgrade for version ' . SHORT_URL_VERSION . ' (previously verified: ' . $installed_version . ')');

            // --- Check 1: Database Tables ---
            $tables_ok = self::check_database_tables();
            if (!$tables_ok) {
                error_log('Short URL: Database tables check failed. Attempting to recreate...');
                // Re-run table creation from activator
                Short_URL_Activator::create_database_tables();
                // Re-check after attempt
                if (!self::check_database_tables()) {
                    // Set flag for admin notice
                    self::$db_error = true;
                    error_log('Short URL: Failed to create/verify database tables after activation.');
                    // Potentially stop further checks if DB is fundamental
                    // return;
                } else {
                     error_log('Short URL: Database tables successfully created/verified.');
                }
            } else {
                 error_log('Short URL: Database tables verified.');
            }

            // --- Check 2: Capabilities ---
            $caps_ok = self::check_capabilities();
            if (!$caps_ok) {
                 error_log('Short URL: Capabilities check failed. Attempting to recreate...');
                // Re-run capability creation from activator
                Short_URL_Activator::create_capabilities();
                 // Re-check after attempt
                if (!self::check_capabilities()) {
                    // Set flag for admin notice
                    self::$caps_error = true;
                    error_log('Short URL: Failed to create/verify capabilities after activation.');
                } else {
                    error_log('Short URL: Capabilities successfully created/verified.');
                }
            } else {
                 error_log('Short URL: Capabilities verified.');
            }

            // --- Update verified version ---
            // Update only if both checks passed or were successfully fixed? Or always update?
            // Let's update regardless for now, so it doesn't run repeatedly if there's a persistent issue fixed later.
            update_option('short_url_install_verified_version', SHORT_URL_VERSION);
            error_log('Short URL: Installation verification complete. Verified version set to ' . SHORT_URL_VERSION);
        }
    }

    /**
     * Check if required database tables exist.
     *
     * @return bool True if all tables exist, false otherwise.
     */
    private static function check_database_tables(): bool {
        global $wpdb;
        $tables_exist = true;
        $required_tables = [
            $wpdb->prefix . 'short_urls',
            $wpdb->prefix . 'short_url_analytics',
            $wpdb->prefix . 'short_url_groups',
            $wpdb->prefix . 'short_url_domains',
        ];

        foreach ($required_tables as $table_name) {
            // Use SHOW TABLES LIKE for better performance than information_schema on some systems
            if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
                error_log("Short URL: Database table check failed - table '{$table_name}' not found.");
                $tables_exist = false;
                // break; // Stop checking once one is missing
            }
        }
        return $tables_exist;
    }

    /**
     * Display setup-related admin notices based on flags.
     * Hooked to admin_notices.
     */
    public function display_setup_notices(): void {
        if (self::$db_error) {
            $this->show_db_error_notice();
        }
        if (self::$caps_error) {
            $this->show_caps_error_notice();
        }
    }

    /**
     * Check if the administrator role has the core capabilities.
     *
     * @return bool True if all core capabilities exist for admin, false otherwise.
     */
    private static function check_capabilities(): bool {
        $role = get_role('administrator');
        if (!$role) {
            error_log('Short URL: Could not get administrator role object during capability check.');
            return false; // Cannot check if role doesn't exist
        }

        $required_caps = [
            'manage_short_urls',
            'create_short_urls',
            'manage_short_url_groups',
            'view_short_url_analytics',
            'manage_short_url_settings',
        ];
        $caps_exist = true;

        foreach ($required_caps as $cap) {
            if (!$role->has_cap($cap)) {
                 error_log("Short URL: Capability check failed - administrator role missing '{$cap}'.");
                $caps_exist = false;
                // break; // Stop checking once one is missing
            }
        }
        return $caps_exist;
    }

    /**
     * Display admin notice for database errors.
     */
    public function show_db_error_notice(): void {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php esc_html_e('Short URL Plugin Error:', 'short-url'); ?></strong>
                <?php esc_html_e('Failed to create or verify required database tables. Some plugin features may not work correctly.', 'short-url'); ?>
                <?php esc_html_e('Please try deactivating and reactivating the plugin. If the problem persists, check your database user permissions or contact support.', 'short-url'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Display admin notice for capability errors.
     */
    public function show_caps_error_notice(): void {
         ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php esc_html_e('Short URL Plugin Error:', 'short-url'); ?></strong>
                <?php esc_html_e('Failed to assign necessary capabilities to the Administrator role. You might not be able to access all plugin features.', 'short-url'); ?>
                 <?php esc_html_e('Please try deactivating and reactivating the plugin. If the problem persists, contact support.', 'short-url'); ?>
            </p>
        </div>
        <?php
    }
}

// Initialize the plugin
function short_url(): Short_URL {
    static $instance = null;
    
    if ($instance === null) {
        $instance = Short_URL::get_instance();
        
        // Initialize the updater if we're in the admin area
        if (is_admin()) {
            // Include the updater class
            require_once plugin_dir_path(__FILE__) . 'includes/class-short-url-updater.php';
            
            if (class_exists('Short_URL_Updater')) {
                $updater = new Short_URL_Updater(
                    __FILE__,
                    'tomrobak/short-url',
                    SHORT_URL_VERSION
                );
                
                // Log that the updater was initialized
                error_log('Short URL: Updater initialized with version ' . SHORT_URL_VERSION);
            } else {
                error_log('Short URL: Updater class not found');
            }
        }
    }
    
    return $instance;
}

// Initialize the plugin after WordPress loads
add_action('plugins_loaded', 'short_url');
// Let's roll! 