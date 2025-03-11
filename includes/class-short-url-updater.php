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
        
        // Add settings
        add_action('admin_init', array($this, 'register_settings'));
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
                'package' => isset($release_info->assets[0]) ? $release_info->assets[0]->browser_download_url : '',
                'icons' => array(
                    '1x' => SHORT_URL_PLUGIN_URL . 'admin/images/icon-128x128.png',
                    '2x' => SHORT_URL_PLUGIN_URL . 'admin/images/icon-256x256.png',
                ),
                'banners' => array(
                    'low' => SHORT_URL_PLUGIN_URL . 'admin/images/banner-772x250.jpg',
                    'high' => SHORT_URL_PLUGIN_URL . 'admin/images/banner-1544x500.jpg',
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
     * Get plugin info for the update
     *
     * @param mixed  $result  Plugin info
     * @param string $action  Action
     * @param object $args    Arguments
     * @return mixed Plugin info
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
        
        $plugin_info = (object) array(
            'name' => $this->plugin_data['Name'],
            'slug' => dirname($this->slug),
            'version' => $release_info->tag_name,
            'author' => $this->plugin_data['Author'],
            'author_profile' => $this->plugin_data['AuthorURI'],
            'homepage' => $this->plugin_data['PluginURI'],
            'requires' => '6.7',
            'tested' => '6.7.0',
            'requires_php' => '8.0',
            'downloaded' => 0,
            'last_updated' => $release_info->published_at,
            'sections' => array(
                'description' => $this->plugin_data['Description'],
                'changelog' => $this->get_changelog(),
            ),
            'download_link' => isset($release_info->assets[0]) ? $release_info->assets[0]->browser_download_url : '',
            'screenshots' => array(),
            'tags' => array(
                'url shortener',
                'link shortener',
                'short links',
                'analytics',
                'qr codes',
            ),
            'icons' => array(
                '1x' => SHORT_URL_PLUGIN_URL . 'admin/images/icon-128x128.png',
                '2x' => SHORT_URL_PLUGIN_URL . 'admin/images/icon-256x256.png',
            ),
            'banners' => array(
                'low' => SHORT_URL_PLUGIN_URL . 'admin/images/banner-772x250.jpg',
                'high' => SHORT_URL_PLUGIN_URL . 'admin/images/banner-1544x500.jpg',
            ),
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
} 