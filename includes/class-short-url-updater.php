<?php
/**
 * Short URL Updater
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Updater Class
 * 
 * Handles plugin updates via GitHub
 */
class Short_URL_Updater {
    /**
     * Plugin file
     *
     * @var string
     */
    private $file;
    
    /**
     * GitHub repository
     *
     * @var string
     */
    private $repo;
    
    /**
     * Plugin version
     *
     * @var string
     */
    private $version;
    
    /**
     * GitHub API URL
     *
     * @var string
     */
    private $api_url;
    
    /**
     * GitHub raw URL
     *
     * @var string
     */
    private $raw_url;
    
    /**
     * GitHub releases URL
     *
     * @var string
     */
    private $releases_url;
    
    /**
     * Plugin data
     *
     * @var array
     */
    private $plugin_data;
    
    /**
     * Plugin slug
     *
     * @var string
     */
    private $slug;
    
    /**
     * Initialize the updater
     *
     * @param string $file    Plugin file
     * @param string $repo    GitHub repository (e.g. username/repo)
     * @param string $version Plugin version
     */
    public function __construct($file, $repo, $version) {
        $this->file = $file;
        $this->repo = $repo;
        $this->version = $version;
        $this->slug = plugin_basename($file);
        $this->api_url = 'https://api.github.com/repos/' . $repo;
        $this->raw_url = 'https://raw.githubusercontent.com/' . $repo . '/main';
        $this->releases_url = 'https://github.com/' . $repo . '/releases';
        
        // Get plugin data
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $this->plugin_data = get_plugin_data($file);
        
        // Hooks
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        add_filter('upgrader_post_install', array($this, 'post_install'), 10, 3);
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add plugin action links
        add_filter('plugin_action_links_' . $this->slug, array($this, 'add_action_links'));
        
        // Add update message in plugins list
        add_action('in_plugin_update_message-' . $this->slug, array($this, 'update_message'), 10, 2);

        // Handle manual update check
        add_action('admin_init', array($this, 'check_for_manual_update'));
        
        // Clear the cache when plugin is updated
        register_activation_hook($file, array($this, 'clear_cache'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('short_url_settings', 'short_url_license_key');
    }
    
    /**
     * Check for updates
     *
     * @param object $transient Update transient
     * @return object Update transient
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get release info
        $release_info = $this->get_release_info();
        
        if (!$release_info || !isset($release_info->tag_name)) {
            return $transient;
        }
        
        // Strip v prefix if present
        $version = $release_info->tag_name;
        if (substr($version, 0, 1) === 'v') {
            $version = substr($version, 1);
        }
        
        // Check if a new version is available
        if (version_compare($version, $this->version, '>')) {
            // Build package URL
            $package_url = '';
            if (isset($release_info->assets) && is_array($release_info->assets) && !empty($release_info->assets)) {
                foreach ($release_info->assets as $asset) {
                    if (isset($asset->browser_download_url) && strpos($asset->browser_download_url, '.zip') !== false) {
                        $package_url = $asset->browser_download_url;
                        break;
                    }
                }
            }
            
            // If no package URL was found, use the default GitHub release ZIP
            if (empty($package_url)) {
                $package_url = sprintf(
                    'https://github.com/%s/releases/download/%s/short-url.zip',
                    $this->repo,
                    $release_info->tag_name
                );
            }
            
            $plugin = (object) array(
                'id' => $this->slug,
                'slug' => dirname($this->slug),
                'plugin' => $this->slug,
                'new_version' => $version,
                'url' => isset($this->plugin_data['PluginURI']) ? $this->plugin_data['PluginURI'] : '',
                'package' => $package_url,
                'icons' => array(
                    '1x' => isset($this->plugin_data['IconURL']) ? $this->plugin_data['IconURL'] : '',
                    '2x' => isset($this->plugin_data['IconURL']) ? $this->plugin_data['IconURL'] : '',
                ),
                'banners' => array(
                    'low' => isset($this->plugin_data['BannerURL']) ? $this->plugin_data['BannerURL'] : '',
                    'high' => isset($this->plugin_data['BannerURL']) ? $this->plugin_data['BannerURL'] : '',
                ),
                'tested' => isset($release_info->body) ? $this->get_tested_wp_version($release_info->body) : '6.7',
                'requires_php' => isset($this->plugin_data['RequiresPHP']) ? $this->plugin_data['RequiresPHP'] : '8.0',
                'compatibility' => new stdClass(),
            );
            
            $transient->response[$this->slug] = $plugin;
        }
        
        return $transient;
    }
    
    /**
     * Plugin info for the wp-admin/plugin-install.php page
     *
     * @param object $result Result object
     * @param string $action WordPress.org API action
     * @param object $args   Plugin arguments
     * @return object Plugin info
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== dirname($this->slug)) {
            return $result;
        }
        
        // Get release info
        $release_info = $this->get_release_info();
        
        if ($release_info === false) {
            return $result;
        }
        
        // Get the changelog
        $changelog = $this->get_changelog();
        
        // Parse release body to extract sections if available
        $sections = array(
            'description' => isset($this->plugin_data['Description']) ? $this->plugin_data['Description'] : '',
            'changelog' => $changelog,
        );
        
        // Try to parse installation instructions from README.md
        $readme_url = $this->raw_url . '/README.md';
        $readme_content = @file_get_contents($readme_url);
        
        if ($readme_content) {
            // Extract installation section
            if (preg_match('/## Installation(.*?)(?:^##|\z)/sm', $readme_content, $matches)) {
                $sections['installation'] = trim($matches[1]);
            }
            
            // Extract FAQ section
            if (preg_match('/## FAQ(.*?)(?:^##|\z)/sm', $readme_content, $matches)) {
                $sections['faq'] = trim($matches[1]);
            }
        }
        
        // Current and latest versions for compatibility display
        $current_version = explode('.', $this->version);
        $latest_version = explode('.', ltrim($release_info->tag_name, 'v'));
        
        // WordPress version compatibility
        $requires_wp = isset($this->plugin_data['RequiresWP']) ? $this->plugin_data['RequiresWP'] : '5.0';
        $tested_wp = isset($this->plugin_data['TestedUpTo']) ? $this->plugin_data['TestedUpTo'] : (defined('WP_VERSION') ? WP_VERSION : '6.7');
        
        // PHP version compatibility
        $requires_php = isset($this->plugin_data['RequiresPHP']) ? $this->plugin_data['RequiresPHP'] : '7.0';
        
        $plugin_info = (object) array(
            'name' => $this->plugin_data['Name'],
            'slug' => dirname($this->slug),
            'version' => ltrim($release_info->tag_name, 'v'),
            'author' => $this->plugin_data['Author'],
            'author_profile' => $this->plugin_data['AuthorURI'],
            'requires' => $requires_wp,
            'tested' => $tested_wp,
            'requires_php' => $requires_php,
            'rating' => 90, // Default to 90% until we have actual ratings
            'num_ratings' => 10,
            'downloaded' => 1000,
            'last_updated' => $release_info->published_at,
            'homepage' => $this->plugin_data['PluginURI'],
            'sections' => $sections,
            'download_link' => sprintf(
                'https://github.com/%s/releases/download/%s/short-url.zip',
                $this->repo,
                $release_info->tag_name
            ),
            'banners' => array(
                'low' => SHORT_URL_PLUGIN_URL . 'assets/banner-772x250.jpg',
                'high' => SHORT_URL_PLUGIN_URL . 'assets/banner-1544x500.jpg',
            ),
            'icons' => array(
                '1x' => SHORT_URL_PLUGIN_URL . 'assets/icon-128x128.png',
                '2x' => SHORT_URL_PLUGIN_URL . 'assets/icon-256x256.png',
            ),
            'upgrade_notice' => isset($release_info->body) ? $release_info->body : '',
        );
        
        return $plugin_info;
    }
    
    /**
     * After installation, make sure the plugin is properly activated
     *
     * @param bool  $response   Installation response
     * @param array $hook_extra Extra arguments
     * @param array $result     Installation result
     * @return array Installation result
     */
    public function post_install($response, $hook_extra, $result) {
        global $wp_filesystem;
        
        if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->slug) {
            return $result;
        }
        
        // Move the plugin to the correct location
        $plugin_dir = plugin_dir_path($this->file);
        $wp_filesystem->move($result['destination'], $plugin_dir);
        $result['destination'] = $plugin_dir;
        
        // Activate the plugin
        if (is_plugin_inactive($this->slug)) {
            activate_plugin($this->slug);
        }
        
        return $result;
    }
    
    /**
     * Get release info from GitHub
     *
     * @param bool $force_refresh Whether to force a refresh of the data
     * @return object|false Release info or false on failure
     */
    private function get_release_info($force_refresh = false) {
        // Check cache first
        $cache_key = 'short_url_github_release_info';
        $release_info = get_transient($cache_key);
        
        if ($release_info !== false && !$force_refresh) {
            return json_decode($release_info);
        }
        
        // Make API request
        $response = wp_remote_get($this->api_url . '/releases/latest', array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            ),
            'timeout' => 15,
            'sslverify' => true,
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }
        
        $release_info = wp_remote_retrieve_body($response);
        if (empty($release_info)) {
            return false;
        }
        
        // Cache for 6 hours
        set_transient($cache_key, $release_info, 6 * HOUR_IN_SECONDS);
        
        return json_decode($release_info);
    }
    
    /**
     * Get changelog from GitHub
     *
     * @return string Changelog
     */
    private function get_changelog() {
        // Check cache first
        $cache_key = 'short_url_github_changelog';
        $changelog = get_transient($cache_key);
        
        if ($changelog !== false) {
            return $changelog;
        }
        
        // Make API request
        $response = wp_remote_get($this->raw_url . '/CHANGELOG.md', array(
            'timeout' => 10,
        ));
        
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return 'No changelog available.';
        }
        
        $changelog = wp_remote_retrieve_body($response);
        
        // Cache for 6 hours
        set_transient($cache_key, $changelog, 6 * HOUR_IN_SECONDS);
        
        return $changelog;
    }
    
    /**
     * Show update message
     *
     * @param array  $plugin_data Plugin data
     * @param object $response    Update response
     */
    public function update_message($plugin_data, $response) {
        if (!empty($response->upgrade_notice)) {
            echo ' <strong>' . esc_html__('Upgrade Notice:', 'short-url') . '</strong> ' . esc_html($response->upgrade_notice);
        }
        
        // Get the changelog if available
        $changelog = $this->get_changelog();
        if (!empty($changelog)) {
            echo '<div class="short-url-update-message">';
            echo '<p><strong>' . esc_html__('What\'s New:', 'short-url') . '</strong></p>';
            echo '<pre class="short-url-changelog">' . esc_html($changelog) . '</pre>';
            echo '</div>';
            
            // Add some inline styling
            echo '<style>
                .short-url-update-message { margin-top: 10px; }
                .short-url-changelog { max-height: 150px; overflow-y: auto; background: #f6f7f7; padding: 10px; margin-top: 8px; font-size: 12px; }
            </style>';
        }
    }
    
    /**
     * Clear update cache
     */
    public function clear_cache() {
        delete_transient('short_url_github_release_info');
        delete_transient('short_url_github_changelog');
        delete_site_transient('update_plugins');
    }
    
    /**
     * Handle manual update check via AJAX
     */
    public function check_for_manual_update() {
        // Register AJAX action for update check
        add_action('wp_ajax_short_url_check_update', array($this, 'ajax_check_update'));
        
        // Enqueue JavaScript for the update check
        add_action('admin_enqueue_scripts', array($this, 'enqueue_update_script'));
        
        // Legacy method (fallback for non-JS browsers)
        if (isset($_GET['short-url-check-update']) && $_GET['short-url-check-update'] == 1) {
            // Verify nonce
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'short-url-check-update')) {
                wp_die(__('Security check failed.', 'short-url'));
            }
            
            // Clear cache
            $this->clear_cache();
            
            // Check for update
            $update_info = $this->get_update_info();
            
            if ($update_info && isset($update_info['has_update']) && $update_info['has_update']) {
                // Add admin notice for available update
                add_action('admin_notices', function() use ($update_info) {
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php printf(
                            __('A new version of Short URL (%s) is available! <a href="%s" target="_blank">View release details</a> or <a href="%s">update now</a>.', 'short-url'),
                            esc_html($update_info['version']),
                            esc_url($update_info['release_url']),
                            esc_url(admin_url('update-core.php'))
                        ); ?></p>
                    </div>
                    <?php
                });
            } else {
                // Add admin notice for no updates
                add_action('admin_notices', function() {
                    ?>
                    <div class="notice notice-info is-dismissible">
                        <p><?php _e('Your Short URL plugin is up to date!', 'short-url'); ?></p>
                    </div>
                    <?php
                });
            }
            
            // Redirect back to plugins page
            wp_redirect(admin_url('plugins.php'));
            exit;
        }
    }
    
    /**
     * Enqueue JavaScript for update check
     */
    public function enqueue_update_script($hook) {
        if ($hook !== 'plugins.php') {
            return;
        }
        
        // Add inline script
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $(document).on("click", ".short-url-check-update", function(e) {
                    e.preventDefault();
                    
                    var $link = $(this);
                    $link.text("' . esc_js(__('Checking...', 'short-url')) . '");
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "short_url_check_update",
                            nonce: "' . wp_create_nonce('short_url_ajax_check_update') . '"
                        },
                        success: function(response) {
                            $link.text("' . esc_js(__('Check for updates', 'short-url')) . '");
                            
                            if (response.success) {
                                if (response.data.has_update) {
                                    alert("' . esc_js(__('A new version is available!', 'short-url')) . ' " + response.data.message);
                                    if (confirm("' . esc_js(__('Would you like to update now?', 'short-url')) . '")) {
                                        window.location.href = response.data.update_url;
                                    }
                                } else {
                                    alert("' . esc_js(__('Your Short URL plugin is up to date!', 'short-url')) . '");
                                }
                            } else {
                                alert("' . esc_js(__('Error checking for updates. Please try again.', 'short-url')) . '");
                            }
                        },
                        error: function() {
                            $link.text("' . esc_js(__('Check for updates', 'short-url')) . '");
                            alert("' . esc_js(__('Error checking for updates. Please try again.', 'short-url')) . '");
                        }
                    });
                });
            });
        ');
    }
    
    /**
     * Handle AJAX update check
     */
    public function ajax_check_update() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'short_url_ajax_check_update')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'short-url')));
            return;
        }
        
        // Clear cache
        $this->clear_cache();
        
        // Check for update
        $update_info = $this->get_update_info();
        
        if ($update_info && isset($update_info['has_update']) && $update_info['has_update']) {
            wp_send_json_success(array(
                'has_update' => true,
                'message' => sprintf(
                    __('Version %s is available (you have %s).', 'short-url'),
                    $update_info['version'],
                    $this->version
                ),
                'version' => $update_info['version'],
                'release_url' => $update_info['release_url'],
                'update_url' => admin_url('update-core.php'),
            ));
        } else {
            wp_send_json_success(array(
                'has_update' => false,
                'message' => sprintf(
                    __('Your plugin is up to date (version %s).', 'short-url'),
                    $this->version
                ),
            ));
        }
    }
    
    /**
     * Get update info
     *
     * @return array|false Update info or false on failure
     */
    private function get_update_info() {
        $release_info = $this->get_release_info(true);
        
        if (!$release_info || !isset($release_info->tag_name)) {
            return false;
        }
        
        // Strip v prefix if present
        $version = $release_info->tag_name;
        if (substr($version, 0, 1) === 'v') {
            $version = substr($version, 1);
        }
        
        $has_update = version_compare($version, $this->version, '>');
        
        return array(
            'has_update' => $has_update,
            'version' => $version,
            'release_url' => isset($release_info->html_url) ? $release_info->html_url : '#',
            'release_date' => isset($release_info->published_at) ? date_i18n(get_option('date_format'), strtotime($release_info->published_at)) : '',
            'download_url' => isset($release_info->assets[0]) ? $release_info->assets[0]->browser_download_url : '#',
        );
    }
    
    /**
     * Add action links to plugins page
     *
     * @param array $links Plugin action links
     * @return array Modified plugin action links
     */
    public function add_action_links($links) {
        // Add check for updates link
        $check_update_link = '<a href="' . wp_nonce_url(admin_url('plugins.php?short-url-check-update=1'), 'short-url-check-update') . '" class="short-url-check-update">' . __('Check for updates', 'short-url') . '</a>';
        
        // Add to the beginning of the links
        array_unshift($links, $check_update_link);
        
        return $links;
    }
} 