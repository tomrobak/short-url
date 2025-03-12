<?php
/**
 * Plugin Name: Short URL
 * Plugin URI: https://github.com/tomrobak/short-url
 * Description: A modern URL shortener with analytics, custom domains, and more. The fastest way to link without sacrificing your brand or analytics!
 * Version: 1.2.2
 * Author: wplove.co
 * Author URI: https://wplove.co/
 * Text Domain: short-url
 * Domain Path: /languages
 * Requires at least: 6.7
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
            <p><?php _e('Short URL requires PHP 8.0 or higher. Please upgrade your PHP version to use this plugin.', 'short-url'); ?></p>
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
            <p><?php _e('Short URL requires WordPress 6.7 or higher. Please upgrade your WordPress installation to use this plugin.', 'short-url'); ?></p>
        </div>
        <?php
    }
    add_action('admin_notices', 'short_url_wp_requirement_notice');
    return;
}

// Define plugin constants
define('SHORT_URL_VERSION', '1.2.2');
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
        
        // Load text domain - moved to init hook to fix loading too early
        add_action('init', array($this, 'load_textdomain'));
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
            
            // Initialize admin classes
            Short_URL_Admin::get_instance();
            Short_URL_Gutenberg::get_instance();
        }
    }

    /**
     * Initialize plugin hooks
     */
    private function init_hooks(): void {
        // Activation
        register_activation_hook(__FILE__, array('Short_URL_Activator', 'activate'));
        
        // Deactivation
        register_deactivation_hook(__FILE__, array('Short_URL_Deactivator', 'deactivate'));

        // Check for updates
        add_action('admin_init', array($this, 'check_for_updates'));
        
        // Handle redirects
        add_action('init', array($this, 'handle_redirect'), 1);
        
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
        load_plugin_textdomain('short-url', false, dirname(plugin_basename(__FILE__)) . '/languages');
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

// Let's roll!
short_url(); 