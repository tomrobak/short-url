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
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Admin notices
        add_action('admin_notices', array('Short_URL_Deactivator', 'show_deactivation_notice'));
        add_action('admin_notices', array('Short_URL_Deactivator', 'show_data_deleted_notice'));
        add_action('admin_notices', array($this, 'show_welcome_notice'));
        
        // AJAX handlers
        add_action('wp_ajax_short_url_dismiss_welcome', array($this, 'dismiss_welcome_notice'));
        add_action('wp_ajax_short_url_create', array($this, 'ajax_create_url'));
        add_action('wp_ajax_short_url_qr_code', array($this, 'ajax_generate_qr_code'));
        
        // Admin post handlers
        add_action('admin_post_short_url_delete_data', array('Short_URL_Deactivator', 'handle_data_deletion'));
        
        // Post editor integration
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . SHORT_URL_PLUGIN_BASENAME, array($this, 'plugin_action_links'));
        
        // Admin footer text
        add_filter('admin_footer_text', array($this, 'admin_footer_text'), 10, 1);
        
        // Welcome redirect
        add_action('admin_init', array($this, 'welcome_redirect'));
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
        // Only enqueue on our plugin pages
        if (strpos($hook, 'short-url') === false) {
            return;
        }
        
        // Admin styles
        wp_enqueue_style(
            'short-url-admin',
            SHORT_URL_PLUGIN_URL . 'admin/css/short-url-admin.css',
            array(),
            SHORT_URL_VERSION
        );
        
        // Admin scripts
        wp_enqueue_script(
            'short-url-admin',
            SHORT_URL_PLUGIN_URL . 'admin/js/short-url-admin.js',
            array('jquery', 'wp-api', 'wp-util', 'jquery-ui-datepicker'),
            SHORT_URL_VERSION,
            true
        );
        
        // Add data for JavaScript
        wp_localize_script('short-url-admin', 'shortURLAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'apiRoot' => esc_url_raw(rest_url('short-url/v1')),
            'apiNonce' => wp_create_nonce('wp_rest'),
            'homeUrl' => trailingslashit(home_url()),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this item? This cannot be undone.', 'short-url'),
                'copied' => __('Copied!', 'short-url'),
                'copyFailed' => __('Copy failed. Please try again.', 'short-url'),
                'generating' => __('Generating...', 'short-url'),
                'error' => __('An error occurred. Please try again.', 'short-url'),
            ),
        ));
        
        // Load chart.js on analytics pages
        if (strpos($hook, 'short-url-analytics') !== false || $hook === 'toplevel_page_short-url') {
            wp_enqueue_script(
                'chartjs',
                SHORT_URL_PLUGIN_URL . 'admin/js/chart.min.js',
                array(),
                '3.7.0',
                true
            );
        }
        
        // Load clipboard.js on URL pages
        if (strpos($hook, 'short-url-urls') !== false || strpos($hook, 'short-url-add') !== false) {
            wp_enqueue_script(
                'clipboard',
                SHORT_URL_PLUGIN_URL . 'admin/js/clipboard.min.js',
                array(),
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
            'redirect_type' => isset($_POST['short_url_redirect_type']) ? intval($_POST['short_url_redirect_type']) : 301,
            'track_visits' => isset($_POST['short_url_track_visits']) ? 1 : 0,
            'anonymize_ip' => isset($_POST['short_url_anonymize_ip']) ? 1 : 0,
            'filter_bots' => isset($_POST['filter_bots']) ? 1 : 0,
            'case_sensitive' => isset($_POST['case_sensitive']) ? 1 : 0,
            'auto_create_for_post_types' => isset($_POST['auto_create_for_post_types']) ? (array) $_POST['auto_create_for_post_types'] : array(),
            'excluded_ips' => isset($_POST['excluded_ips']) ? sanitize_textarea_field($_POST['excluded_ips']) : '',
            'data_retention_period' => isset($_POST['data_retention_period']) ? absint($_POST['data_retention_period']) : 365,
            'public_url_form' => isset($_POST['public_url_form']) ? 1 : 0,
            'use_meta_refresh' => isset($_POST['use_meta_refresh']) ? 1 : 0,
            'use_lowercase' => isset($_POST['short_url_use_lowercase']) ? 1 : 0,
            'use_uppercase' => isset($_POST['short_url_use_uppercase']) ? 1 : 0,
            'use_numbers' => isset($_POST['short_url_use_numbers']) ? 1 : 0,
            'use_special' => isset($_POST['short_url_use_special']) ? 1 : 0,
        );
        
        // Make sure at least one character type is selected
        if (!$settings['use_lowercase'] && !$settings['use_uppercase'] && !$settings['use_numbers'] && !$settings['use_special']) {
            $settings['use_lowercase'] = 1; // Default to lowercase if nothing selected
        }
        
        // Display settings
        $display_settings = array();
        $post_types = get_post_types(array('public' => true), 'names');
        
        foreach ($post_types as $post_type) {
            $display_settings[$post_type] = array(
                'above' => isset($_POST['display_above_' . $post_type]) ? 1 : 0,
                'below' => isset($_POST['display_below_' . $post_type]) ? 1 : 0,
            );
        }
        
        $settings['display_short_url'] = $display_settings;
        
        // Save all settings
        foreach ($settings as $key => $value) {
            // Special handling for excluded IPs (convert textarea to array)
            if ($key === 'excluded_ips') {
                $ips = explode("\n", $value);
                $ips = array_map('trim', $ips);
                $ips = array_filter($ips);
                update_option('short_url_' . $key, $ips);
            } else {
                update_option('short_url_' . $key, $value);
            }
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
        // Check if nonce is set
        if (!isset($_POST['short_url_meta_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['short_url_meta_nonce'], 'short_url_save_meta')) {
            return;
        }
        
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
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
        
        // Get URL
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $size = isset($_POST['size']) ? intval($_POST['size']) : 150;
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('URL is required.', 'short-url')));
        }
        
        // Get QR code URL
        $qr_url = Short_URL_Utils::get_qr_code_url($url, $size);
        
        wp_send_json_success(array('qr_url' => $qr_url));
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
     * Customize admin footer text on plugin pages
     *
     * @param string $text Footer text
     * @return string Modified footer text
     */
    public function admin_footer_text($text) {
        global $current_screen;
        
        if (strpos($current_screen->id, 'short-url') !== false) {
            $text = sprintf(
                __('If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'short-url'),
                '<strong>Short URL</strong>',
                '<a href="https://wordpress.org/support/plugin/short-url/reviews/?filter=5#new-post" target="_blank">★★★★★</a>'
            );
        }
        
        return $text;
    }
} 