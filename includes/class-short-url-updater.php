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
        $this->api_url = 'https://api.github.com/repos/' . $repo;
        $this->raw_url = 'https://raw.githubusercontent.com/' . $repo . '/main';
        $this->releases_url = 'https://github.com/' . $repo . '/releases';
        
        // Get plugin data
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $this->plugin_data = get_plugin_data($file);
        $this->slug = plugin_basename($file);
        
        // Hooks
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add plugin action links
        add_filter('plugin_action_links_' . $this->slug, array($this, 'add_plugin_action_links'));
        
        // Add update message in plugins list
        add_action('in_plugin_update_message-' . $this->slug, array($this, 'update_message'), 10, 2);
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
        
        if ($release_info === false) {
            return $transient;
        }
        
        // Check if a new version is available
        if (version_compare($release_info->tag_name, $this->version, '>')) {
            $plugin = (object) array(
                'id' => $this->slug,
                'slug' => dirname($this->slug),
                'plugin' => $this->slug,
                'new_version' => $release_info->tag_name,
                'url' => $this->plugin_data['PluginURI'],
                'package' => sprintf(
                    'https://github.com/%s/releases/download/%s/short-url.zip',
                    $this->repo,
                    $release_info->tag_name
                ),
                'icons' => array(
                    '1x' => SHORT_URL_PLUGIN_URL . 'assets/icon-128x128.png',
                    '2x' => SHORT_URL_PLUGIN_URL . 'assets/icon-256x256.png',
                ),
                'banners' => array(
                    'low' => SHORT_URL_PLUGIN_URL . 'assets/banner-772x250.jpg',
                    'high' => SHORT_URL_PLUGIN_URL . 'assets/banner-1544x500.jpg',
                ),
                'tested' => '6.7.0',
                'requires_php' => '8.0',
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
    public function after_install($response, $hook_extra, $result) {
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
     * @return object|false Release info or false on failure
     */
    private function get_release_info() {
        // Check cache first
        $cache_key = 'short_url_github_release_info';
        $release_info = get_transient($cache_key);
        
        if ($release_info !== false) {
            return json_decode($release_info);
        }
        
        // Make API request
        $response = wp_remote_get($this->api_url . '/releases/latest', array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
            'timeout' => 10,
        ));
        
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }
        
        $release_info = wp_remote_retrieve_body($response);
        
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
     * Check if manual updates are available
     *
     * @return bool|array False if no update, array with update info if available
     */
    public function check_manual_update() {
        // Get release info
        $release_info = $this->get_release_info();
        
        if ($release_info === false) {
            return false;
        }
        
        // Check if a new version is available
        if (version_compare($release_info->tag_name, $this->version, '>')) {
            return array(
                'version' => $release_info->tag_name,
                'download_url' => isset($release_info->assets[0]) ? $release_info->assets[0]->browser_download_url : '',
                'release_url' => $this->releases_url . '/tag/' . $release_info->tag_name,
                'published_at' => date_i18n(get_option('date_format'), strtotime($release_info->published_at)),
            );
        }
        
        return false;
    }
    
    /**
     * Install an update manually
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function install_manual_update() {
        // Check for update
        $update_info = $this->check_manual_update();
        
        if (!$update_info) {
            return new WP_Error('no_update', __('No update available.', 'short-url'));
        }
        
        // Download the update
        $download_file = download_url($update_info['download_url']);
        
        if (is_wp_error($download_file)) {
            return $download_file;
        }
        
        // Unzip the file
        $upgrade_folder = WP_CONTENT_DIR . '/upgrade/';
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->slug) . '/';
        
        // Create upgrade folder if it doesn't exist
        if (!is_dir($upgrade_folder)) {
            mkdir($upgrade_folder);
        }
        
        // Unzip plugin
        $unzip_result = unzip_file($download_file, $upgrade_folder);
        
        // Remove the downloaded zip file
        @unlink($download_file);
        
        if (is_wp_error($unzip_result)) {
            return $unzip_result;
        }
        
        // Copy files to plugin folder
        if (!is_dir($upgrade_folder . dirname($this->slug))) {
            return new WP_Error('unzip_failed', __('Failed to extract update package.', 'short-url'));
        }
        
        // Backup current plugin
        $backup_folder = WP_CONTENT_DIR . '/upgrade/backup-' . dirname($this->slug) . '-' . time();
        
        if (is_dir($plugin_folder) && !@rename($plugin_folder, $backup_folder)) {
            return new WP_Error('backup_failed', __('Failed to backup current plugin.', 'short-url'));
        }
        
        // Move new plugin
        if (!@rename($upgrade_folder . dirname($this->slug), $plugin_folder)) {
            // Try to restore backup
            if (is_dir($backup_folder)) {
                @rename($backup_folder, $plugin_folder);
            }
            
            return new WP_Error('move_failed', __('Failed to move new plugin files.', 'short-url'));
        }
        
        // Delete backup
        if (is_dir($backup_folder)) {
            $this->rmdir_recursive($backup_folder);
        }
        
        // Activate plugin
        activate_plugin($this->slug);
        
        return true;
    }
    
    /**
     * Recursively remove a directory
     *
     * @param string $dir Directory to remove
     * @return bool True on success
     */
    private function rmdir_recursive($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->rmdir_recursive($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
    
    /**
     * Add plugin action links
     * 
     * @param array $links Plugin action links
     * @return array Modified action links
     */
    public function add_plugin_action_links($links) {
        // Check for updates link
        $check_update_link = '<a href="' . wp_nonce_url(admin_url('plugins.php?short-url-check-update=1'), 'short-url-check-update') . '">' . __('Check for updates', 'short-url') . '</a>';
        
        // Add the link to the beginning of the array
        array_unshift($links, $check_update_link);
        
        return $links;
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
} 