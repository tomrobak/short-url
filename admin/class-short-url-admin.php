<?php
/**
 * Short URL Admin
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Class
 */
class Short_URL_Admin {
    /**
     * Instance of this class
     *
     * @var Short_URL_Admin
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Short_URL_Admin
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Admin notices
        add_action('admin_notices', array('Short_URL_Deactivator', 'show_deactivation_notice'));
        add_action('admin_notices', array('Short_URL_Deactivator', 'show_data_deleted_notice'));
        add_action('admin_notices', array($this, 'show_welcome_notice'));
        add_action('admin_notices', array($this, 'show_bulk_shortlink_notice'));
        
        // AJAX handlers
        add_action('wp_ajax_short_url_dismiss_welcome', array($this, 'dismiss_welcome_notice'));
        add_action('wp_ajax_short_url_create', array($this, 'ajax_create_url'));
        add_action('wp_ajax_short_url_qr_code', array($this, 'ajax_generate_qr_code'));
        add_action('wp_ajax_short_url_generate_slug', array($this, 'ajax_generate_slug'));
        add_action('wp_ajax_short_url_get_post_url', array($this, 'ajax_get_post_url'));
        add_action('wp_ajax_short_url_get_url_data', array($this, 'ajax_get_url_data'));
        add_action('wp_ajax_short_url_update_url', array($this, 'ajax_update_url'));
        add_action('wp_ajax_short_url_update_maxmind', array($this, 'ajax_update_maxmind'));
        
        // Admin post handlers
        add_action('admin_post_short_url_delete_data', array('Short_URL_Deactivator', 'handle_data_deletion'));
        
        // Post editor integration
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Bulk actions
        add_filter('bulk_actions-edit-post', array($this, 'register_bulk_actions'));
        add_filter('handle_bulk_actions-edit-post', array($this, 'handle_bulk_actions'), 10, 3);
        
        // Add bulk actions for all enabled post types
        $post_types = get_option('short_url_display_metabox_post_types', array('post', 'page'));
        if (is_array($post_types)) {
            foreach ($post_types as $post_type) {
                if ($post_type !== 'post') { // Already added for 'post'
                    add_filter('bulk_actions-edit-' . $post_type, array($this, 'register_bulk_actions'));
                    add_filter('handle_bulk_actions-edit-' . $post_type, array($this, 'handle_bulk_actions'), 10, 3);
                }
            }
        }
        
        // Plugin action links
        add_filter('plugin_action_links_' . SHORT_URL_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // Admin footer text
        add_filter('admin_footer_text', array($this, 'admin_footer_text'), 10, 1);
        
        // Welcome redirect
        add_action('admin_init', array($this, 'welcome_redirect'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register all settings used in the settings page
        register_setting('short_url_settings', 'short_url_slug_length');
        register_setting('short_url_settings', 'short_url_link_prefix');
        register_setting('short_url_settings', 'short_url_redirect_type');
        register_setting('short_url_settings', 'short_url_track_visits');
        register_setting('short_url_settings', 'short_url_track_referrer');
        register_setting('short_url_settings', 'short_url_track_ip');
        register_setting('short_url_settings', 'short_url_track_device');
        register_setting('short_url_settings', 'short_url_track_location');
        register_setting('short_url_settings', 'short_url_auto_create_post_types');
        register_setting('short_url_settings', 'short_url_display_metabox_post_types');
        register_setting('short_url_settings', 'short_url_display_in_content');
        register_setting('short_url_settings', 'short_url_display_position');
        register_setting('short_url_settings', 'short_url_anonymize_ip');
        register_setting('short_url_settings', 'short_url_data_retention');
        register_setting('short_url_settings', 'short_url_character_sets');
        register_setting('short_url_settings', 'short_url_disable_footer');
        register_setting('short_url_settings', 'short_url_use_maxmind');
        register_setting('short_url_settings', 'short_url_maxmind_account_id');
        register_setting('short_url_settings', 'short_url_maxmind_license_key');
    }

    /**
     * Register admin menu items
     */
    public function register_admin_menu() {
        // Main menu
        add_menu_page(
            __('Short URL', 'short-url'),
            __('Short URL', 'short-url'),
            'manage_short_urls',
            'short-url',
            array($this, 'display_dashboard_page'),
            'dashicons-admin-links',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'short-url',
            __('Dashboard', 'short-url'),
            __('Dashboard', 'short-url'),
            'manage_short_urls',
            'short-url',
            array($this, 'display_dashboard_page')
        );
        
        // URLs submenu
        add_submenu_page(
            'short-url',
            __('All URLs', 'short-url'),
            __('All URLs', 'short-url'),
            'manage_short_urls',
            'short-url-urls',
            array($this, 'display_urls_page')
        );
        
        // Add URL submenu
        add_submenu_page(
            'short-url',
            __('Add New', 'short-url'),
            __('Add New', 'short-url'),
            'create_short_urls',
            'short-url-add',
            array($this, 'display_add_url_page')
        );
        
        // Groups submenu
        add_submenu_page(
            'short-url',
            __('Groups', 'short-url'),
            __('Groups', 'short-url'),
            'manage_short_url_groups',
            'short-url-groups',
            array($this, 'display_groups_page')
        );
        
        // Analytics submenu
        add_submenu_page(
            'short-url',
            __('Analytics', 'short-url'),
            __('Analytics', 'short-url'),
            'view_short_url_analytics',
            'short-url-analytics',
            array($this, 'display_analytics_page')
        );
        
        // Tools submenu
        add_submenu_page(
            'short-url',
            __('Tools', 'short-url'),
            __('Tools', 'short-url'),
            'import_export_short_urls',
            'short-url-tools',
            array($this, 'display_tools_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'short-url',
            __('Settings', 'short-url'),
            __('Settings', 'short-url'),
            'manage_short_url_settings',
            'short-url-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page
     */
    public function enqueue_assets($hook) {
        // Check if we are on a relevant page (plugin pages or post edit screens)
        $is_plugin_page = strpos($hook, 'short-url') !== false;
        $is_post_edit_page = in_array($hook, array('post.php', 'post-new.php'));

        if (!$is_plugin_page && !$is_post_edit_page) {
            return; // Exit if not on a relevant page
        }
        
        // Admin styles
        wp_enqueue_style(
            'short-url-admin',
            SHORT_URL_PLUGIN_URL . 'admin/css/short-url-admin.css',
            array(),
            SHORT_URL_VERSION
        );
        
        // Load chart.js on all admin pages
        wp_enqueue_script(
            'chartjs',
            SHORT_URL_PLUGIN_URL . 'admin/js/chart.min.js',
            array(),
            '3.7.0',
            true
        );
        
        // Admin scripts
        wp_enqueue_script(
            'short-url-admin',
            SHORT_URL_PLUGIN_URL . 'admin/js/short-url-admin.js',
            array('jquery', 'wp-api', 'wp-util', 'jquery-ui-datepicker', 'chartjs'),
            SHORT_URL_VERSION,
            true
        );
        
        // Add data for JavaScript
        wp_localize_script('short-url-admin', 'shortURLAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'apiRoot' => esc_url_raw(rest_url('short-url/v1')),
            'apiNonce' => wp_create_nonce('wp_rest'),
            'adminNonce' => wp_create_nonce('short_url_admin'),
            'homeUrl' => trailingslashit(home_url()),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this item? This cannot be undone.', 'short-url'),
                'copied' => __('Copied!', 'short-url'),
                'copyFailed' => __('Copy failed. Please try again.', 'short-url'),
                'generating' => __('Generating...', 'short-url'),
                'error' => __('An error occurred. Please try again.', 'short-url'),
            ),
            'texts' => array(
                'qrCodeFor' => __('QR Code for', 'short-url'),
                'loading' => __('Loading...', 'short-url'),
                'scanQrCode' => __('Scan this QR code to visit the short URL', 'short-url'),
                'appearance' => __('Appearance', 'short-url'),
                'size' => __('Size', 'short-url'),
                'format' => __('Format', 'short-url'),
                'small' => __('Small', 'short-url'),
                'medium' => __('Medium', 'short-url'),
                'large' => __('Large', 'short-url'),
                'extraLarge' => __('Extra Large', 'short-url'),
                'download' => __('Download QR Code', 'short-url'),
                'print' => __('Print QR Code', 'short-url'),
                'failedToLoad' => __('Failed to load QR code', 'short-url'),
            ),
        ));
        
        // Load clipboard.js on URL pages AND post edit pages (for the meta box)
        if ($is_plugin_page || $is_post_edit_page) {
             wp_enqueue_script(
                 'clipboard',
                 SHORT_URL_PLUGIN_URL . 'admin/js/clipboard.min.js',
                 array('jquery'), // Add jquery dependency
                 '2.0.11',
                 true
             );
        }
        
        // Load Font Awesome for icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
            array(),
            '5.15.4'
        );
    }

    /**
     * Display dashboard page
     */
    public function display_dashboard_page() {
        // Get analytics summary for the dashboard
        $summary = Short_URL_Analytics::get_dashboard_summary();
        
        // Include the view
        include_once SHORT_URL_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Display URLs page
     */
    public function display_urls_page() {
        // Include URL list table class
        require_once SHORT_URL_PLUGIN_DIR . 'admin/class-short-url-list-table.php';
        
        // Create an instance of the list table
        $list_table = new Short_URL_List_Table();
        $list_table->prepare_items();
        
        // Include the view
        include_once SHORT_URL_PLUGIN_DIR . 'admin/views/urls.php';
    }

    /**
     * Display add URL page
     */
    public function display_add_url_page() {
        // Check if we're editing an existing URL
        $url_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $url = null;
        
        if ($url_id > 0) {
            $db = new Short_URL_DB();
            $url = $db->get_url($url_id);
            
            // Check if URL exists
            if (!$url) {
                wp_die(__('URL not found.', 'short-url'));
            }
            
            // Check edit permissions
            if (!current_user_can('edit_short_urls')) {
                wp_die(__('You do not have permission to edit this URL.', 'short-url'));
            }
        }
        
        // Get groups for dropdown
        $db = new Short_URL_DB();
        $groups = $db->get_groups();
        
        // Include the view
        include_once SHORT_URL_PLUGIN_DIR . 'admin/views/add-new.php';
    }

    /**
     * Display groups page
     */
    public function display_groups_page() {
        // Include Group list table class
        require_once SHORT_URL_PLUGIN_DIR . 'admin/class-short-url-group-list-table.php';
        
        // Create an instance of the list table
        $list_table = new Short_URL_Group_List_Table();
        $list_table->prepare_items();
        
        // Include the view
        include_once SHORT_URL_PLUGIN_DIR . 'admin/views/groups.php';
    }

    /**
     * Display analytics page
     */
    public function display_analytics_page() {
        // Get URL ID from query string
        $url_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // If URL ID is provided, show detailed analytics for that URL
        if ($url_id > 0) {
            $db = new Short_URL_DB();
            $url = $db->get_url($url_id);
            
            // Check if URL exists
            if (!$url) {
                wp_die(__('URL not found.', 'short-url'));
            }
            
            // Get analytics data
            $analytics = $db->get_url_analytics($url_id);
            $summary = $db->get_url_analytics_summary($url_id);
            
            // Include the detailed view
            include_once SHORT_URL_PLUGIN_DIR . 'admin/views/analytics-detail.php';
        } else {
            // Show overall analytics
            $summary = Short_URL_Analytics::get_dashboard_summary(array('days' => 90));
            
            // Include the overview view
            include_once SHORT_URL_PLUGIN_DIR . 'admin/views/analytics.php';
        }
    }

    /**
     * Display tools page
     */
    public function display_tools_page() {
        // Include the view
        include_once SHORT_URL_PLUGIN_DIR . 'admin/views/tools.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        // Check if settings are being saved
        if (isset($_POST['short_url_settings_nonce']) && 
            wp_verify_nonce($_POST['short_url_settings_nonce'], 'short_url_save_settings')) {
            
            // Save settings
            $this->save_settings();
            
            // Show success message
            add_settings_error(
                'short_url_settings',
                'short_url_settings_updated',
                __('Settings saved successfully.', 'short-url'),
                'success'
            );
        }
        
        // Include the view
        include_once SHORT_URL_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Save settings
     */
    private function save_settings() {
        // General settings
        $settings = array(
            'slug_length' => isset($_POST['short_url_slug_length']) ? absint($_POST['short_url_slug_length']) : 4,
            'link_prefix' => isset($_POST['short_url_link_prefix']) ? sanitize_text_field($_POST['short_url_link_prefix']) : '',
            'redirect_type' => isset($_POST['short_url_redirect_type']) ? sanitize_text_field($_POST['short_url_redirect_type']) : '301',
            'track_visits' => isset($_POST['short_url_track_visits']) ? 1 : 0,
            'track_referrer' => isset($_POST['short_url_track_referrer']) ? 1 : 0,
            'track_ip' => isset($_POST['short_url_track_ip']) ? 1 : 0,
            'track_device' => isset($_POST['short_url_track_device']) ? 1 : 0,
            'track_location' => isset($_POST['short_url_track_location']) ? 1 : 0,
            'anonymize_ip' => isset($_POST['short_url_anonymize_ip']) ? 1 : 0,
            'auto_create_post_types' => isset($_POST['short_url_auto_create_post_types']) ? (array) $_POST['short_url_auto_create_post_types'] : array(),
            'display_metabox_post_types' => isset($_POST['short_url_display_metabox_post_types']) ? (array) $_POST['short_url_display_metabox_post_types'] : array(),
            'display_in_content' => isset($_POST['short_url_display_in_content']) ? 1 : 0,
            'display_position' => isset($_POST['short_url_display_position']) ? sanitize_text_field($_POST['short_url_display_position']) : 'after',
            'data_retention' => isset($_POST['short_url_data_retention']) ? absint($_POST['short_url_data_retention']) : 365,
            'use_lowercase' => isset($_POST['short_url_use_lowercase']) ? 1 : 0,
            'use_uppercase' => isset($_POST['short_url_use_uppercase']) ? 1 : 0,
            'use_numbers' => isset($_POST['short_url_use_numbers']) ? 1 : 0,
            'use_special' => isset($_POST['short_url_use_special']) ? 1 : 0,
            'disable_footer' => isset($_POST['short_url_disable_footer']) ? 1 : 0,
            'use_maxmind' => isset($_POST['short_url_use_maxmind']) ? 1 : 0,
            'maxmind_account_id' => isset($_POST['short_url_maxmind_account_id']) ? sanitize_text_field($_POST['short_url_maxmind_account_id']) : '',
            'maxmind_license_key' => isset($_POST['short_url_maxmind_license_key']) ? sanitize_text_field($_POST['short_url_maxmind_license_key']) : '',
        );
        
        // Make sure at least one character type is selected
        if (!$settings['use_lowercase'] && !$settings['use_uppercase'] && !$settings['use_numbers'] && !$settings['use_special']) {
            $settings['use_lowercase'] = 1; // Default to lowercase if nothing selected
        }
        
        // Save all settings
        foreach ($settings as $key => $value) {
            update_option('short_url_' . $key, $value);
        }
    }

    /**
     * Add meta boxes to post edit screen
     */
    public function add_meta_boxes() {
        // Get post types to add meta box to
        $post_types = get_option('short_url_auto_create_for_post_types', array('post', 'page'));
        
        // Add meta box to each post type
        foreach ($post_types as $post_type) {
            add_meta_box(
                'short-url-meta-box',
                __('Short URL', 'short-url'),
                array($this, 'display_meta_box'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Display meta box content
     *
     * @param WP_Post $post Post object
     */
    public function display_meta_box($post) {
        // Get existing short URL for this post
        $url_id = get_post_meta($post->ID, '_short_url_id', true);
        $url_data = null;
        
        if ($url_id) {
            $db = new Short_URL_DB();
            $url = $db->get_url($url_id);
            
            if ($url) {
                $url_data = array(
                    'slug' => $url->slug,
                    'short_url' => Short_URL_Generator::get_short_url($url->slug),
                    'visits' => $url->visits,
                );
            }
        }
        
        // Nonce field
        wp_nonce_field('short_url_save_meta', 'short_url_meta_nonce');
        
        // Include meta box view
        include SHORT_URL_PLUGIN_DIR . 'admin/views/meta-box.php';
    }

    /**
     * Save post meta
     *
     * @param int $post_id Post ID
     */
    public function save_post_meta($post_id) {
        // Start output buffering to prevent headers already sent errors
        ob_start();
        
        // Check if nonce is set
        if (!isset($_POST['short_url_meta_nonce'])) {
            ob_end_clean();
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['short_url_meta_nonce'], 'short_url_save_meta')) {
            ob_end_clean();
            return;
        }
        
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            ob_end_clean();
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            ob_end_clean();
            return;
        }
        
        // Check if post should automatically have a short URL
        $post_type = get_post_type($post_id);
        $auto_create_post_types = get_option('short_url_auto_create_for_post_types', array('post', 'page'));
        
        // Check if post is published
        $post_status = get_post_status($post_id);
        
        if ($post_status === 'publish' && in_array($post_type, $auto_create_post_types)) {
            // Check if a custom slug was provided
            $custom_slug = isset($_POST['short_url_custom_slug']) ? 
                sanitize_text_field($_POST['short_url_custom_slug']) : '';
            
            // Create or update short URL
            $existing_url_id = get_post_meta($post_id, '_short_url_id', true);
            
            if (!empty($custom_slug) && $existing_url_id) {
                // Update existing URL with new slug
                Short_URL_Generator::update_url($existing_url_id, array('slug' => $custom_slug));
            } elseif (!empty($custom_slug)) {
                // Create new URL with custom slug
                Short_URL_Generator::create_for_post($post_id, $custom_slug);
            } elseif (!$existing_url_id) {
                // Create new URL with auto-generated slug
                Short_URL_Generator::create_for_post($post_id);
            }
        }
        
        // End output buffering
        ob_end_clean();
    }

    /**
     * Ajax handler for creating a short URL
     */
    public function ajax_create_url() {
        // Check nonce
        check_ajax_referer('short_url_admin', 'nonce');
        
        // Check permissions
        if (!current_user_can('create_short_urls')) {
            wp_send_json_error(array('message' => __('You do not have permission to create short URLs.', 'short-url')));
        }
        
        // Get parameters
        $destination_url = isset($_POST['destination_url']) ? 
            esc_url_raw($_POST['destination_url']) : '';
        
        $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';
        
        // Validate URL
        if (empty($destination_url)) {
            wp_send_json_error(array('message' => __('Destination URL is required.', 'short-url')));
        }
        
        // Create short URL
        $result = Short_URL_Generator::create_url($destination_url, array(
            'slug' => $slug,
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'expires_at' => isset($_POST['expires_at']) && !empty($_POST['expires_at']) ? 
                sanitize_text_field($_POST['expires_at']) : null,
            'password' => isset($_POST['password']) && !empty($_POST['password']) ? 
                sanitize_text_field($_POST['password']) : null,
            'redirect_type' => isset($_POST['redirect_type']) ? intval($_POST['redirect_type']) : 301,
            'nofollow' => isset($_POST['nofollow']) && $_POST['nofollow'] === 'true',
            'sponsored' => isset($_POST['sponsored']) && $_POST['sponsored'] === 'true',
            'forward_parameters' => isset($_POST['forward_parameters']) && $_POST['forward_parameters'] === 'true',
            'track_visits' => isset($_POST['track_visits']) && $_POST['track_visits'] === 'true',
            'group_id' => isset($_POST['group_id']) && !empty($_POST['group_id']) ? 
                intval($_POST['group_id']) : null,
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }

    /**
     * Ajax handler for generating a QR code
     */
    public function ajax_generate_qr_code() {
        // Check nonce
        check_ajax_referer('short_url_admin', 'nonce');
        
        // Get URL by ID or direct URL
        if (isset($_POST['url_id']) && !empty($_POST['url_id'])) {
            $url_id = intval($_POST['url_id']);
            $db = new Short_URL_DB();
            $url_data = $db->get_url($url_id);
            
            if (!$url_data) {
                wp_send_json_error(array('message' => __('URL not found.', 'short-url')));
            }
            
            $base_url = Short_URL_Utils::get_base_url();
            $url = trailingslashit($base_url) . $url_data->slug;
        } else {
            $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        }
        
        $size = isset($_POST['size']) ? intval($_POST['size']) : 150;
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'png';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('URL is required.', 'short-url')));
        }
        
        // Get QR code URL
        $qr_url = Short_URL_Utils::get_qr_code_url($url, $size, $format);
        
        wp_send_json_success(array(
            'qr_url' => $qr_url,
            'format' => $format,
            'size' => $size
        ));
    }

    /**
     * AJAX handler for generating a unique slug
     */
    public function ajax_generate_slug() {
        // Check nonce
        check_ajax_referer('short_url_admin', 'nonce');

        // Check permissions
        if (!current_user_can('create_short_urls')) {
            wp_send_json_error(__('You do not have permission to create short URLs.', 'short-url'));
        }

        // Generate a unique slug
        try {
            $slug = Short_URL_Generator::generate_unique_slug();
            wp_send_json_success(array('slug' => $slug));
        } catch (Exception $e) {
            error_log("Short URL: Error generating unique slug via AJAX: " . $e->getMessage());
            wp_send_json_error(__('Could not generate a unique slug. Please try again or enter one manually.', 'short-url'));
        }
    }

    /**
     * Show welcome notice for new installations
     */
    public function show_welcome_notice() {
        // Only show to admins
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if notice has been dismissed
        if (get_option('short_url_welcome_dismissed')) {
            return;
        }
        
        ?>
        <div class="notice notice-info is-dismissible short-url-welcome-notice">
            <h3><?php esc_html_e('Welcome to Short URL!', 'short-url'); ?></h3>
            <p><?php esc_html_e('Thank you for installing Short URL. Create branded short links, track clicks, and improve your marketing!', 'short-url'); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=short-url')); ?>" class="button button-primary">
                    <?php esc_html_e('Get Started', 'short-url'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-settings')); ?>" class="button">
                    <?php esc_html_e('Settings', 'short-url'); ?>
                </a>
                <a href="#" class="short-url-dismiss-welcome" style="margin-left: 10px;"><?php esc_html_e('Dismiss', 'short-url'); ?></a>
            </p>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.short-url-dismiss-welcome').on('click', function(e) {
                    e.preventDefault();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'short_url_dismiss_welcome',
                            nonce: '<?php echo esc_js(wp_create_nonce('short_url_dismiss_welcome')); ?>'
                        }
                    });
                    
                    $(this).closest('.notice').fadeOut();
                });
                
                $(document).on('click', '.short-url-welcome-notice .notice-dismiss', function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'short_url_dismiss_welcome',
                            nonce: '<?php echo esc_js(wp_create_nonce('short_url_dismiss_welcome')); ?>'
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Ajax handler for dismissing welcome notice
     */
    public function dismiss_welcome_notice() {
        // Check nonce
        check_ajax_referer('short_url_dismiss_welcome', 'nonce');
        
        // Update option
        update_option('short_url_welcome_dismissed', 1);
        
        wp_die();
    }

    /**
     * Redirect to welcome page on activation
     */
    public function welcome_redirect() {
        // Check if we should redirect
        if (!get_transient('short_url_activation_redirect')) {
            return;
        }
        
        // Delete the transient
        delete_transient('short_url_activation_redirect');
        
        // Don't redirect if doing an AJAX request
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        // Don't redirect if multiple plugins are being activated
        if (isset($_GET['activate-multi'])) {
            return;
        }
        
        // Redirect to welcome page
        wp_safe_redirect(admin_url('admin.php?page=short-url'));
        exit;
    }

    /**
     * Add plugin action links
     *
     * @param array $links Plugin action links
     * @return array Modified plugin action links
     */
    public function plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=short-url') . '">' . __('Dashboard', 'short-url') . '</a>',
            '<a href="' . admin_url('admin.php?page=short-url-settings') . '">' . __('Settings', 'short-url') . '</a>',
        );
        
        return array_merge($plugin_links, $links);
    }

    /**
     * Customize the admin footer text
     *
     * @param string $text
     * @return string
     */
    public function admin_footer_text($text) {
        $current_screen = get_current_screen();
        
        // Only modify text on our plugin pages and if footer message isn't disabled
        if (strpos($current_screen->id, 'short-url') !== false && !get_option('short_url_disable_footer', false)) {
            $text = sprintf(
                __('If %1$s plugin helped you, imagine what it can do for your friends. Spread the word! 🔥 Tell your friends to join %2$s - community for photographers and videographers', 'short-url'),
                '<strong>wplove.co</strong>',
                '<a href="https://wplove.co" target="_blank">wplove.co</a>'
            );
        }
        
        return $text;
    }

    /**
     * AJAX handler to get a post's short URL
     * 
     * @since 1.1.2
     * @return void
     */
    public function ajax_get_post_url() {
        // Check nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'short_url_get_post_url_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'short-url')));
            return;
        }
        
        // Check if post ID is set
        if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'short-url')));
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Check if post exists
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => __('Post not found.', 'short-url')));
            return;
        }
        
        // Get the short URL for this post
        $db = new Short_URL_DB();
        $url_data = $db->get_url_by_post_id($post_id);
        
        if (!$url_data) {
            // URL doesn't exist yet, try to create it
            $generator = new Short_URL_Generator();
            $url_data = $generator->create_url_for_post($post);
            
            if (!$url_data) {
                wp_send_json_error(array('message' => __('Failed to create short URL.', 'short-url')));
                return;
            }
        }
        
        // Format full URL
        $full_url = SHORT_URL_SITE_URL . '/' . $url_data->slug;
        
        wp_send_json_success(array(
            'url' => $full_url,
            'slug' => $url_data->slug,
            'visits' => isset($url_data->visits) ? $url_data->visits : 0
        ));
    }

    /**
     * Register bulk actions for posts
     *
     * @param array $bulk_actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public function register_bulk_actions($bulk_actions) {
        $bulk_actions['generate_shortlinks'] = __('Generate Shortlinks', 'short-url');
        return $bulk_actions;
    }

    /**
     * Handle bulk actions for posts
     *
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action name
     * @param array $post_ids Selected post IDs
     * @return string Modified redirect URL
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'generate_shortlinks') {
            return $redirect_to;
        }
        
        // Verify nonce for security
        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
        if (!wp_verify_nonce($nonce, 'bulk-posts')) {
            wp_die(__('Security check failed.', 'short-url'));
        }

        $success_count = 0;
        $error_count = 0;
        
        // Get post types that should have shortlinks
        $auto_create_post_types = get_option('short_url_auto_create_post_types', array('post', 'page'));
        
        // Allow developers to filter the post IDs
        $post_ids = apply_filters('short_url_bulk_generate_post_ids', $post_ids, $auto_create_post_types);

        foreach ($post_ids as $post_id) {
            // Check if post exists and is published
            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'publish') {
                $error_count++;
                continue;
            }
            
            // Check if post type is enabled for shortlinks
            if (!in_array($post->post_type, $auto_create_post_types)) {
                $error_count++;
                continue;
            }

            // Check if post already has a short URL
            $existing_url_id = get_post_meta($post_id, '_short_url_id', true);
            if ($existing_url_id) {
                // URL already exists, count as success
                $success_count++;
                continue;
            }

            // Generate short URL for post
            $result = Short_URL_Generator::create_for_post($post_id);
            if (!is_wp_error($result)) {
                $success_count++;
            } else {
                $error_count++;
            }
        }

        // Add query args to redirect URL for admin notice
        $redirect_to = add_query_arg(
            array(
                'bulk_shortlinks_generated' => $success_count,
                'bulk_shortlinks_failed' => $error_count,
            ),
            $redirect_to
        );
        
        // Allow developers to filter the redirect URL
        $redirect_to = apply_filters('short_url_bulk_generate_redirect', $redirect_to, $success_count, $error_count);

        return $redirect_to;
    }

    /**
     * Show admin notice after bulk shortlink generation
     */
    public function show_bulk_shortlink_notice() {
        if (!empty($_REQUEST['bulk_shortlinks_generated']) || !empty($_REQUEST['bulk_shortlinks_failed'])) {
            $success_count = isset($_REQUEST['bulk_shortlinks_generated']) ? intval($_REQUEST['bulk_shortlinks_generated']) : 0;
            $error_count = isset($_REQUEST['bulk_shortlinks_failed']) ? intval($_REQUEST['bulk_shortlinks_failed']) : 0;
            
            $message = '';
            
            if ($success_count > 0) {
                $message .= sprintf(
                    _n(
                        'Successfully generated shortlink for %d post.',
                        'Successfully generated shortlinks for %d posts.',
                        $success_count,
                        'short-url'
                    ),
                    $success_count
                );
            }
            
            if ($error_count > 0) {
                if ($message) {
                    $message .= ' ';
                }
                
                $message .= sprintf(
                    _n(
                        'Failed to generate shortlink for %d post.',
                        'Failed to generate shortlinks for %d posts.',
                        $error_count,
                        'short-url'
                    ),
                    $error_count
                );
            }
            
            if ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
        }
    }

    /**
     * AJAX handler for getting URL data for editing
     */
    public function ajax_get_url_data() {
        // Check nonce
        check_ajax_referer('short_url_admin', 'nonce');
        
        // Check if URL ID is provided
        if (!isset($_POST['url_id']) || empty($_POST['url_id'])) {
            wp_send_json_error(array('message' => __('URL ID is required.', 'short-url')));
        }
        
        $url_id = intval($_POST['url_id']);
        
        // Get URL data
        $db = new Short_URL_DB();
        $url = $db->get_url($url_id);
        
        if (!$url) {
            wp_send_json_error(array('message' => __('URL not found.', 'short-url')));
        }
        
        // Format data for the edit form
        $data = array(
            'id' => $url->id,
            'destination_url' => $url->destination_url,
            'short_url' => $url->slug,
            'password_protected' => !empty($url->password),
            'expires' => !empty($url->expiry_date),
            'expiry_date' => $url->expiry_date ? date('Y-m-d', strtotime($url->expiry_date)) : '',
            'is_active' => (bool) $url->is_active,
            'created_at' => $url->created_at,
            'updated_at' => $url->updated_at
        );
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for updating a URL
     */
    public function ajax_update_url() {
        // Check nonce
        check_ajax_referer('short_url_admin', 'nonce');
        
        // Check if URL ID is provided
        if (!isset($_POST['url_id']) || empty($_POST['url_id'])) {
            wp_send_json_error(array('message' => __('URL ID is required.', 'short-url')));
        }
        
        $url_id = intval($_POST['url_id']);
        
        // Get URL data
        $db = new Short_URL_DB();
        $url = $db->get_url($url_id);
        
        if (!$url) {
            wp_send_json_error(array('message' => __('URL not found.', 'short-url')));
        }
        
        // Prepare data for update
        $data = array();
        
        // Destination URL
        if (isset($_POST['destination_url']) && !empty($_POST['destination_url'])) {
            $data['destination_url'] = esc_url_raw($_POST['destination_url']);
        } else {
            wp_send_json_error(array('message' => __('Destination URL is required.', 'short-url')));
        }
        
        // Short URL slug
        if (isset($_POST['short_url']) && !empty($_POST['short_url'])) {
            $slug = sanitize_text_field($_POST['short_url']);
            
            // Check if slug is valid
            if (!preg_match('/^[a-zA-Z0-9-]+$/', $slug)) {
                wp_send_json_error(array('message' => __('Short URL can only contain letters, numbers, and hyphens.', 'short-url')));
            }
            
            // Check if slug is already in use by another URL
            $existing_url = $db->get_url_by_slug($slug);
            if ($existing_url && $existing_url->id != $url_id) {
                wp_send_json_error(array('message' => __('This short URL is already in use. Please choose another one.', 'short-url')));
            }
            
            $data['slug'] = $slug;
        } else {
            wp_send_json_error(array('message' => __('Short URL is required.', 'short-url')));
        }
        
        // Password protection
        $data['password'] = '';
        if (isset($_POST['password_protected']) && $_POST['password_protected']) {
            if (isset($_POST['password']) && $_POST['password'] !== 'password-set') {
                $data['password'] = sanitize_text_field($_POST['password']);
            } else if ($url->password) {
                $data['password'] = $url->password; // Keep existing password
            }
        }
        
        // Expiration
        $data['expiry_date'] = null;
        if (isset($_POST['expires']) && $_POST['expires'] && isset($_POST['expiry_date']) && !empty($_POST['expiry_date'])) {
            $expiry_date = sanitize_text_field($_POST['expiry_date']);
            if (strtotime($expiry_date)) {
                $data['expiry_date'] = $expiry_date;
            } else {
                wp_send_json_error(array('message' => __('Invalid expiration date format.', 'short-url')));
            }
        }
        
        // Update the URL
        $result = $db->update_url($url_id, $data);
        
        if ($result) {
            wp_send_json_success(array('message' => __('URL updated successfully.', 'short-url')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update URL.', 'short-url')));
        }
    }

    /**
     * AJAX handler for updating MaxMind database
     */
    public function ajax_update_maxmind() {
        // Check nonce
        check_ajax_referer('short_url_update_maxmind', 'nonce');
        
        // Verify user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'short-url')));
            return;
        }
        
        // Get account ID and license key
        $account_id = isset($_POST['account_id']) ? sanitize_text_field($_POST['account_id']) : '';
        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        
        if (empty($account_id) || empty($license_key)) {
            wp_send_json_error(array('message' => __('Account ID and License Key are required.', 'short-url')));
            return;
        }
        
        // Save the credentials
        update_option('short_url_maxmind_account_id', $account_id);
        update_option('short_url_maxmind_license_key', $license_key);
        update_option('short_url_use_maxmind', true);
        
        // Update the database
        $result = Short_URL_Analytics::update_maxmind_database($account_id, $license_key);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success(array('message' => __('MaxMind database updated successfully!', 'short-url')));
    }
} 