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
        // Primary hook for WordPress to check for updates
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'), 10, 1);
        
        // Provide plugin information for the update modal
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        
        // Add "Check for updates" link to plugins page
        add_filter('plugin_action_links_' . $this->slug, array($this, 'add_action_links'));
        
        // Handle manual update check
        add_action('admin_init', array($this, 'check_for_manual_update'));
        
        // Force recheck after WordPress updates
        add_action('upgrader_process_complete', array($this, 'upgrader_process_complete'), 10, 2);
        
        // Run on plugin activation/deactivation
        register_activation_hook($this->file, array($this, 'clear_cache'));
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('short_url_settings', 'short_url_license_key');
    }
    
    /**
     * Check for plugin updates
     *
     * @param object $transient Update transient
     * @return object Modified update transient
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Log that we're checking for updates
        error_log('Short URL: Checking for updates via pre_set_site_transient_update_plugins hook');
        
        // Make sure the plugin is in the checked list
        if (!isset($transient->checked[$this->slug])) {
            $transient->checked[$this->slug] = $this->version;
        }
        
        // Get release info
        $release_info = $this->get_release_info();
        
        if (!$release_info || !isset($release_info->tag_name)) {
            error_log('Short URL: Failed to get release info from GitHub or missing tag_name');
            return $transient;
        }
        
        // Strip v prefix if present
        $version = $release_info->tag_name;
        if (substr($version, 0, 1) === 'v') {
            $version = substr($version, 1);
        }
        
        // Log the version comparison
        error_log(sprintf('Short URL: Comparing versions - GitHub: %s, Current: %s', $version, $this->version));
        
        // Check if a new version is available
        if (version_compare($version, $this->version, '>')) {
            error_log('Short URL: New version detected! GitHub: ' . $version . ', Current: ' . $this->version);
            
            // Build package URL
            $package_url = '';
            if (isset($release_info->assets) && is_array($release_info->assets) && !empty($release_info->assets)) {
                foreach ($release_info->assets as $asset) {
                    if (isset($asset->browser_download_url) && strpos($asset->browser_download_url, '.zip') !== false) {
                        $package_url = $asset->browser_download_url;
                        error_log('Short URL: Found download URL in assets: ' . $package_url);
                        break;
                    }
                }
            }
            
            // If no package URL was found, use the default GitHub release ZIP
            if (empty($package_url)) {
                $package_url = sprintf(
                    'https://github.com/%s/archive/refs/tags/%s.zip',
                    $this->repo,
                    $release_info->tag_name
                );
                error_log('Short URL: Using default download URL: ' . $package_url);
            }
            
            // Create the plugin info object
            $plugin = (object) array(
                'id' => $this->slug,
                'slug' => dirname($this->slug),
                'plugin' => $this->slug,
                'new_version' => $version,
                'url' => isset($this->plugin_data['PluginURI']) ? $this->plugin_data['PluginURI'] : '',
                'package' => $package_url,
                'icons' => array(
                    '1x' => isset($this->plugin_data['IconURL']) ? $this->plugin_data['IconURL'] : SHORT_URL_PLUGIN_URL . 'assets/icon-128x128.png',
                    '2x' => isset($this->plugin_data['IconURL']) ? $this->plugin_data['IconURL'] : SHORT_URL_PLUGIN_URL . 'assets/icon-256x256.png',
                ),
                'banners' => array(
                    'low' => isset($this->plugin_data['BannerURL']) ? $this->plugin_data['BannerURL'] : SHORT_URL_PLUGIN_URL . 'assets/banner-772x250.jpg',
                    'high' => isset($this->plugin_data['BannerURL']) ? $this->plugin_data['BannerURL'] : SHORT_URL_PLUGIN_URL . 'assets/banner-1544x500.jpg',
                ),
                'tested' => isset($release_info->body) ? $this->get_tested_wp_version($release_info->body) : '6.7',
                'requires_php' => isset($this->plugin_data['RequiresPHP']) ? $this->plugin_data['RequiresPHP'] : '8.0',
                'compatibility' => new stdClass(),
            );
            
            // Add to the updates list
            $transient->response[$this->slug] = $plugin;
            
            error_log('Short URL: Update object added to transient');
        } else {
            error_log('Short URL: No new version detected');
            
            // Make sure the plugin is in the no_update list if no update is available
            if (!isset($transient->no_update[$this->slug])) {
                $plugin = (object) array(
                    'id' => $this->slug,
                    'slug' => dirname($this->slug),
                    'plugin' => $this->slug,
                    'new_version' => $this->version,
                    'url' => isset($this->plugin_data['PluginURI']) ? $this->plugin_data['PluginURI'] : '',
                    'package' => '',
                    'icons' => array(
                        '1x' => isset($this->plugin_data['IconURL']) ? $this->plugin_data['IconURL'] : SHORT_URL_PLUGIN_URL . 'assets/icon-128x128.png',
                        '2x' => isset($this->plugin_data['IconURL']) ? $this->plugin_data['IconURL'] : SHORT_URL_PLUGIN_URL . 'assets/icon-256x256.png',
                    ),
                    'banners' => array(
                        'low' => isset($this->plugin_data['BannerURL']) ? $this->plugin_data['BannerURL'] : SHORT_URL_PLUGIN_URL . 'assets/banner-772x250.jpg',
                        'high' => isset($this->plugin_data['BannerURL']) ? $this->plugin_data['BannerURL'] : SHORT_URL_PLUGIN_URL . 'assets/banner-1544x500.jpg',
                    ),
                    'tested' => isset($this->plugin_data['Tested up to']) ? $this->plugin_data['Tested up to'] : '6.7',
                    'requires_php' => isset($this->plugin_data['RequiresPHP']) ? $this->plugin_data['RequiresPHP'] : '8.0',
                    'compatibility' => new stdClass(),
                );
                
                $transient->no_update[$this->slug] = $plugin;
                
                error_log('Short URL: Added to no_update list in transient');
            }
        }
        
        return $transient;
    }
    
    /**
     * Process requests for plugin information
     * 
     * @param false|object|array $result The result object or array
     * @param string $action The type of information being requested
     * @param object $args Plugin arguments
     * @return object Plugin information
     */
    public function plugin_info($result, $action, $args) {
        // Check if this is the right plugin
        if ('plugin_information' !== $action || empty($args->slug) || $this->slug !== $args->slug) {
            return $result;
        }

        // Get plugin data
        $plugin_data = get_plugin_data($this->file);
        
        // Get the latest release information
        $release_info = $this->get_release_info();
        if (!$release_info) {
            return $result;
        }
        
        // Get the changelog content
        $changelog = $this->get_changelog();
        
        // Version without v prefix
        $version = $release_info->tag_name;
        if (substr($version, 0, 1) === 'v') {
            $version = substr($version, 1);
        }
        
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
                'https://github.com/%s/archive/refs/tags/%s.zip',
                $this->repo,
                $release_info->tag_name
            );
        }
        
        // Get release notes for the description
        $release_notes = $this->get_release_notes();
        $description = $plugin_data['Description'];
        
        // Format the release notes for display
        $formatted_release_notes = '';
        if (!empty($release_notes)) {
            $formatted_release_notes = $this->format_changelog($release_notes);
        }
        
        // Format the changelog for display
        $formatted_changelog = $this->format_changelog($changelog);
        
        // Create sections for the plugin info
        $sections = array(
            'description' => '<div class="short-url-plugin-description">' . wpautop($description) . '</div>',
        );
        
        // Add release notes section if available
        if (!empty($formatted_release_notes)) {
            $sections['release_notes'] = '<div class="short-url-release-notes">' . $formatted_release_notes . '</div>';
        }
        
        // Add changelog section
        $sections['changelog'] = '<div class="short-url-changelog">' . $formatted_changelog . '</div>';
        
        // Add installation section
        $sections['installation'] = '<div class="short-url-installation">
            <h3>' . __('Installation', 'short-url') . '</h3>
            <ol>
                <li>' . __('Upload the plugin files to the <code>/wp-content/plugins/short-url</code> directory, or install the plugin through the WordPress plugins screen directly.', 'short-url') . '</li>
                <li>' . __('Activate the plugin through the \'Plugins\' screen in WordPress.', 'short-url') . '</li>
                <li>' . __('Use the Settings->Short URL screen to configure the plugin.', 'short-url') . '</li>
            </ol>
        </div>';
        
        // Add FAQ section
        $sections['faq'] = '<div class="short-url-faq">
            <h3>' . __('Frequently Asked Questions', 'short-url') . '</h3>
            <h4>' . __('How do I create a short URL?', 'short-url') . '</h4>
            <p>' . __('Go to Short URL->Add New in your WordPress admin, enter the destination URL and optional settings, then click "Create Short URL".', 'short-url') . '</p>
            <h4>' . __('Can I customize the short URL slug?', 'short-url') . '</h4>
            <p>' . __('Yes, you can specify a custom slug when creating a short URL, or let the plugin generate one automatically.', 'short-url') . '</p>
            <h4>' . __('Does this plugin provide analytics?', 'short-url') . '</h4>
            <p>' . __('Yes, Short URL tracks clicks and provides detailed analytics including device, browser, country, and referrer information.', 'short-url') . '</p>
        </div>';
        
        // Create a plugin information object
        $plugin_info = new stdClass();
        $plugin_info->name = $plugin_data['Name'];
        $plugin_info->slug = $this->slug;
        $plugin_info->version = $version;
        $plugin_info->author = $plugin_data['Author'];
        $plugin_info->author_profile = isset($plugin_data['AuthorURI']) ? $plugin_data['AuthorURI'] : '';
        $plugin_info->homepage = isset($plugin_data['PluginURI']) ? $plugin_data['PluginURI'] : '';
        $plugin_info->requires = isset($plugin_data['RequiresWP']) ? $plugin_data['RequiresWP'] : '6.0';
        $plugin_info->requires_php = isset($plugin_data['RequiresPHP']) ? $plugin_data['RequiresPHP'] : '8.0';
        $plugin_info->tested = isset($plugin_data['TestedUpTo']) ? $plugin_data['TestedUpTo'] : '6.7';
        $plugin_info->downloaded = 0;
        $plugin_info->last_updated = isset($release_info->published_at) ? $release_info->published_at : '';
        $plugin_info->sections = $sections;
        $plugin_info->download_link = $package_url;
        
        // Add banners if available
        $plugin_info->banners = array(
            'low' => SHORT_URL_PLUGIN_URL . 'assets/banner-772x250.jpg',
            'high' => SHORT_URL_PLUGIN_URL . 'assets/banner-1544x500.jpg'
        );
        
        // Add icons if available
        $plugin_info->icons = array(
            '1x' => SHORT_URL_PLUGIN_URL . 'assets/icon-128x128.png',
            '2x' => SHORT_URL_PLUGIN_URL . 'assets/icon-256x256.png'
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
     * @param bool $force_refresh Force fresh data from GitHub
     * @return object|false Release info or false on error
     */
    private function get_release_info($force_refresh = false) {
        // Check cache first
        $cache_key = 'short_url_github_release_info';
        $release_info = get_transient($cache_key);
        
        if ($release_info !== false && !$force_refresh) {
            error_log('Short URL: Using cached release info from transient');
            return json_decode($release_info);
        }
        
        error_log('Short URL: Fetching fresh release info from GitHub API: ' . $this->api_url . '/releases/latest');
        
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
            error_log('Short URL: Error fetching from GitHub: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('Short URL: GitHub API returned non-200 status code: ' . $response_code);
            return false;
        }
        
        $release_info = wp_remote_retrieve_body($response);
        if (empty($release_info)) {
            error_log('Short URL: Empty response body from GitHub');
            return false;
        }
        
        // Validate the JSON
        $decoded = json_decode($release_info);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Short URL: Invalid JSON response from GitHub: ' . json_last_error_msg());
            return false;
        }
        
        // Make sure tag_name exists
        if (!isset($decoded->tag_name)) {
            error_log('Short URL: GitHub response missing tag_name field');
            return false;
        }
        
        error_log('Short URL: Successfully retrieved release info for tag: ' . $decoded->tag_name);
        
        // Cache for 30 minutes (reduced from 6 hours to check more frequently)
        set_transient($cache_key, $release_info, 30 * MINUTE_IN_SECONDS);
        
        return $decoded;
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
        
        // First try to get the RELEASE-NOTES.md file
        $release_notes = $this->get_release_notes();
        
        // Then get the CHANGELOG.md file
        $response = wp_remote_get($this->raw_url . '/CHANGELOG.md', array(
            'timeout' => 10,
        ));
        
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            // If we have release notes but no changelog, just return the release notes
            if (!empty($release_notes)) {
                // Cache for 6 hours
                set_transient($cache_key, $release_notes, 6 * HOUR_IN_SECONDS);
                return $release_notes;
            }
            return 'No changelog available.';
        }
        
        $changelog = wp_remote_retrieve_body($response);
        
        // If we have release notes, prepend them to the changelog
        if (!empty($release_notes)) {
            $changelog = $release_notes . "\n\n" . $changelog;
        }
        
        // Cache for 6 hours
        set_transient($cache_key, $changelog, 6 * HOUR_IN_SECONDS);
        
        return $changelog;
    }
    
    /**
     * Get release notes from GitHub
     *
     * @return string Release notes or empty string
     */
    private function get_release_notes() {
        // Make API request
        $response = wp_remote_get($this->raw_url . '/RELEASE-NOTES.md', array(
            'timeout' => 10,
        ));
        
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return '';
        }
        
        return wp_remote_retrieve_body($response);
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
            // Format the changelog for better readability
            $formatted_changelog = $this->format_changelog($changelog);
            
            echo '<div class="short-url-update-message">';
            echo '<p><strong>' . esc_html__('What\'s New:', 'short-url') . '</strong></p>';
            echo '<div class="short-url-changelog">' . $formatted_changelog . '</div>';
            echo '</div>';
            
            // Add some inline styling
            echo '<style>
                .short-url-update-message { margin-top: 10px; }
                .short-url-changelog { max-height: 300px; overflow-y: auto; background: #f6f7f7; padding: 10px; margin-top: 8px; font-size: 13px; line-height: 1.5; }
                .short-url-changelog h3 { margin: 10px 0 5px; font-size: 14px; color: #1d2327; }
                .short-url-changelog h4 { margin: 12px 0 8px; font-size: 13px; color: #3c434a; }
                .short-url-changelog ul { margin: 5px 0 10px 20px; padding: 0; }
                .short-url-changelog li { margin-bottom: 5px; }
                .short-url-changelog li span { display: inline-block; vertical-align: middle; }
                .short-url-changelog p { margin: 5px 0; }
            </style>';
        }
    }
    
    /**
     * Format changelog content for display
     *
     * @param string $changelog Changelog content
     * @return string Formatted changelog
     */
    private function format_changelog($changelog) {
        // Convert to HTML that preserves formatting
        $formatted = '';
        
        // Check for Markdown-style headings and format appropriately
        $lines = explode("\n", $changelog);
        $in_list = false;
        $current_section = '';
        $in_release_notes = false;
        $in_code_block = false;
        
        foreach ($lines as $line) {
            // Handle code blocks
            if (strpos($line, '```') === 0) {
                if ($in_code_block) {
                    $formatted .= "</code></pre>\n";
                    $in_code_block = false;
                } else {
                    // Extract language if specified
                    $language = trim(substr($line, 3));
                    $formatted .= "<pre><code class=\"language-{$language}\">\n";
                    $in_code_block = true;
                }
                continue;
            }
            
            // If we're in a code block, just add the line as is
            if ($in_code_block) {
                $formatted .= esc_html($line) . "\n";
                continue;
            }
            
            // Check if we're in the RELEASE-NOTES.md section
            if (strpos($line, '# Short URL - Version') === 0) {
                $in_release_notes = true;
                
                if ($in_list) {
                    $formatted .= "</ul>\n";
                    $in_list = false;
                }
                
                // Extract version and name from the heading
                if (preg_match('/# Short URL - Version ([\d\.]+)\s*"?([^"]*)"?/', $line, $matches)) {
                    $version = $matches[1];
                    $version_name = !empty($matches[2]) ? $matches[2] : '';
                    
                    $heading = '<h3 class="short-url-version-heading">Version ' . esc_html($version);
                    if (!empty($version_name)) {
                        $heading .= ' <span class="short-url-version-name">"' . esc_html($version_name) . '"</span>';
                    }
                    $heading .= '</h3>';
                    
                    $formatted .= $heading . "\n";
                } else {
                    $formatted .= '<h3 class="short-url-version-heading">' . esc_html($line) . '</h3>' . "\n";
                }
                continue;
            }
            
            // Process headings
            if (preg_match('/^## \[([\d\.]+)\]\s*"?([^"]*)"?\s*(.*)$/', $line, $matches)) {
                // Version heading
                $version = $matches[1];
                $version_name = !empty($matches[2]) ? $matches[2] : '';
                $version_extra = !empty($matches[3]) ? $matches[3] : '';
                
                if ($in_list) {
                    $formatted .= "</ul>\n";
                    $in_list = false;
                }
                
                // If we're already processed release notes for this version, skip this heading
                if ($in_release_notes && $version === $this->version) {
                    continue;
                }
                
                $in_release_notes = false; // We're now in the regular changelog
                
                $heading = '<h3 class="short-url-version-heading">Version ' . esc_html($version);
                if (!empty($version_name)) {
                    $heading .= ' <span class="short-url-version-name">"' . esc_html($version_name) . '"</span>';
                }
                if (!empty($version_extra)) {
                    $heading .= ' ' . esc_html($version_extra);
                }
                $heading .= '</h3>';
                
                $formatted .= $heading . "\n";
                continue;
            }
            
            // Process release notes section headings (## 🚀 Release Highlights)
            if ($in_release_notes && preg_match('/^## (.+)$/', $line, $matches)) {
                $section = $matches[1];
                
                if ($in_list) {
                    $formatted .= "</ul>\n";
                    $in_list = false;
                }
                
                $formatted .= '<h4 class="short-url-section-heading">' . esc_html($section) . '</h4>' . "\n";
                continue;
            }
            
            // Process section headings (like ### Added, ### Fixed, etc.)
            if (preg_match('/^### (.+)$/', $line, $matches)) {
                $section = $matches[1];
                
                if ($in_list) {
                    $formatted .= "</ul>\n";
                    $in_list = false;
                }
                
                $formatted .= '<h4 class="short-url-section-heading">' . esc_html($section) . '</h4>' . "\n";
                $current_section = $section;
                continue;
            }
            
            // Process bullet points
            if (preg_match('/^[\*\-] (.+)$/', $line, $matches)) {
                $item = $matches[1];
                
                if (!$in_list) {
                    $formatted .= "<ul class=\"short-url-changelog-list\">\n";
                    $in_list = true;
                }
                
                // Check for emoji at the beginning and add a class if found
                $emoji_class = '';
                if (preg_match('/^([\x{1F300}-\x{1F6FF}\x{2600}-\x{26FF}])\s+(.+)$/u', $item, $emoji_matches)) {
                    $emoji = $emoji_matches[1];
                    $item_text = $emoji_matches[2];
                    $emoji_class = ' class="short-url-emoji-item"';
                    $item = '<span class="short-url-emoji">' . $emoji . '</span> ' . $item_text;
                }
                
                // Check for bold text
                $item = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $item);
                
                $formatted .= "<li{$emoji_class}>" . $item . "</li>\n";
                continue;
            }
            
            // Process horizontal rule
            if (trim($line) === '---') {
                if ($in_list) {
                    $formatted .= "</ul>\n";
                    $in_list = false;
                }
                
                $formatted .= "<hr>\n";
                continue;
            }
            
            // Process links
            $line = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $line);
            
            // Process regular paragraph
            if (trim($line) !== '') {
                if ($in_list) {
                    $formatted .= "</ul>\n";
                    $in_list = false;
                }
                
                $formatted .= "<p>" . $line . "</p>\n";
            } elseif (!$in_list) {
                // Add a spacer for empty lines outside lists
                $formatted .= "<div class=\"short-url-spacer\"></div>\n";
            }
        }
        
        // Close any open list
        if ($in_list) {
            $formatted .= "</ul>\n";
        }
        
        // Close any open code block
        if ($in_code_block) {
            $formatted .= "</code></pre>\n";
        }
        
        // Add custom CSS for better display
        $formatted = '<style>
            .short-url-version-heading {
                margin-top: 20px;
                padding-bottom: 8px;
                border-bottom: 1px solid #eee;
                color: #1d2327;
            }
            .short-url-version-name {
                color: #2271b1;
                font-weight: normal;
            }
            .short-url-section-heading {
                margin-top: 15px;
                margin-bottom: 10px;
                color: #3c434a;
            }
            .short-url-changelog-list {
                margin-left: 20px;
                list-style-type: disc;
            }
            .short-url-emoji-item {
                list-style-type: none;
                margin-left: -20px;
            }
            .short-url-emoji {
                display: inline-block;
                width: 20px;
                text-align: center;
                margin-right: 8px;
            }
            .short-url-spacer {
                height: 10px;
            }
            .short-url-release-notes {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
                border-left: 4px solid #2271b1;
            }
            pre {
                background: #f6f7f7;
                padding: 15px;
                border-radius: 3px;
                overflow: auto;
                font-family: monospace;
            }
        </style>' . $formatted;
        
        return $formatted;
    }
    
    /**
     * Clear update cache
     * 
     * @return bool True on success
     */
    public function clear_cache() {
        // Delete our custom transients
        delete_transient('short_url_github_release_info');
        delete_transient('short_url_github_changelog');
        
        // Delete WordPress update transients to force a fresh check
        delete_site_transient('update_plugins');
        delete_site_transient('update_themes');
        delete_site_transient('update_core');
        
        // Force WordPress to check for updates
        wp_clean_plugins_cache(true);
        
        // Log the cache clearing for debugging
        error_log('Short URL: Update cache cleared at ' . current_time('mysql'));
        
        return true;
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
        
        // Enqueue jQuery
        wp_enqueue_script('jquery');
        
        // Add inline script with proper dependencies
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $(document).on("click", ".short-url-check-update", function(e) {
                    e.preventDefault();
                    
                    var $link = $(this);
                    var originalText = $link.text();
                    
                    // Change button text and disable it
                    $link.text("' . esc_js(__('Checking...', 'short-url')) . '");
                    $link.css("opacity", "0.7");
                    $link.prop("disabled", true);
                    
                    // Remove any existing notices
                    $(".short-url-update-notice").remove();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "short_url_check_update",
                            nonce: "' . wp_create_nonce('short_url_check_update') . '"
                        },
                        success: function(response) {
                            // Reset button
                            $link.text(originalText);
                            $link.css("opacity", "1");
                            $link.prop("disabled", false);
                            
                            console.log("Update check response:", response);
                            
                            if (response.success) {
                                if (response.data.has_update) {
                                    // Show a simple alert with version info
                                    alert("' . esc_js(__('A new version of Short URL is available:', 'short-url')) . ' " + response.data.latest_version);
                                    
                                    // Refresh the plugins page
                                    window.location.reload();
                                } else {
                                    // Create notice that no update is available
                                    var $notice = $("<div class=\'notice notice-info short-url-update-notice is-dismissible\'><p>' . esc_js(__('Your Short URL plugin is up to date!', 'short-url')) . '</p></div>");
                                    
                                    // Add notice before the table
                                    $(".wp-list-table").before($notice);
                                }
                            } else {
                                // Create error notice
                                var $notice = $("<div class=\'notice notice-error short-url-update-notice is-dismissible\'><p>' . esc_js(__('Error checking for updates. Please try again.', 'short-url')) . '</p></div>");
                                
                                // Add notice before the table
                                $(".wp-list-table").before($notice);
                                
                                console.error("Update check error:", response);
                            }
                            
                            // Initialize the dismiss button functionality for notices
                            if (typeof wp !== "undefined" && wp.updates && wp.updates.ajaxDismissNotices) {
                                wp.updates.ajaxDismissNotices();
                            }
                        },
                        error: function(xhr, status, error) {
                            // Reset button
                            $link.text(originalText);
                            $link.css("opacity", "1");
                            $link.prop("disabled", false);
                            
                            // Create error notice
                            var $notice = $("<div class=\'notice notice-error short-url-update-notice is-dismissible\'><p>' . esc_js(__('Error checking for updates. Please try again.', 'short-url')) . '</p></div>");
                            
                            // Add notice before the table
                            $(".wp-list-table").before($notice);
                            
                            console.error("AJAX error:", xhr, status, error);
                        }
                    });
                });
            });
        ');
    }
    
    /**
     * AJAX callback for checking updates
     */
    public function ajax_check_update() {
        // Verify nonce
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'short_url_check_update')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'short-url'),
                'error' => 'invalid_nonce'
            ));
            return;
        }
        
        error_log('Short URL: Manual update check initiated at ' . current_time('mysql'));
        
        // Clear cache to force fresh check
        $this->clear_cache();
        
        // Get latest release info from GitHub
        $release_info = $this->get_release_info(true); // Force refresh
        
        if (!$release_info) {
            error_log('Short URL: Failed to retrieve release info from GitHub');
            wp_send_json_error(array(
                'message' => __('Could not retrieve update information from GitHub.', 'short-url'),
                'error' => 'github_api_error'
            ));
            return;
        }
        
        if (!isset($release_info->tag_name)) {
            error_log('Short URL: Invalid release info - missing tag_name');
            wp_send_json_error(array(
                'message' => __('Invalid release information received from GitHub.', 'short-url'),
                'error' => 'invalid_release_info'
            ));
            return;
        }
        
        // Strip v prefix if present
        $latest_version = $release_info->tag_name;
        if (substr($latest_version, 0, 1) === 'v') {
            $latest_version = substr($latest_version, 1);
        }
        
        // Check if update is available
        $has_update = version_compare($latest_version, $this->version, '>');
        
        // Force WordPress to refresh the update transient
        delete_site_transient('update_plugins');
        wp_update_plugins();
        
        // Get update details
        $update_info = $this->get_update_info($release_info);
        
        if ($has_update) {
            error_log(sprintf('Short URL: Update available! Latest: %s, Current: %s', $latest_version, $this->version));
            
            // Force the transient to include our update
            $transient = get_site_transient('update_plugins');
            if ($transient && is_object($transient)) {
                // Ensure our update is included in the response
                $this->check_update($transient);
                set_site_transient('update_plugins', $transient);
            }
        } else {
            error_log(sprintf('Short URL: No update available. Latest: %s, Current: %s', $latest_version, $this->version));
        }
        
        // Send response
        wp_send_json_success($update_info);
    }
    
    /**
     * Get update information for display
     *
     * @param object $release_info Release information from GitHub
     * @return array Update information
     */
    private function get_update_info($release_info) {
        // Strip v prefix if present
        $latest_version = $release_info->tag_name;
        if (substr($latest_version, 0, 1) === 'v') {
            $latest_version = substr($latest_version, 1);
        }
        
        // Check if update is available
        $has_update = version_compare($latest_version, $this->version, '>');
        
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
                'https://github.com/%s/archive/refs/tags/%s.zip',
                $this->repo,
                $release_info->tag_name
            );
        }
        
        // Create update info array
        $update_info = array(
            'has_update' => $has_update,
            'current_version' => $this->version,
            'latest_version' => $latest_version,
            'release_url' => isset($release_info->html_url) ? $release_info->html_url : $this->releases_url,
            'release_date' => isset($release_info->published_at) ? date_i18n(get_option('date_format'), strtotime($release_info->published_at)) : '',
            'download_url' => $package_url,
            'message' => $has_update 
                ? sprintf(__('Version %s is available! You have %s.', 'short-url'), $latest_version, $this->version)
                : sprintf(__('You have the latest version (%s).', 'short-url'), $this->version),
            'update_url' => admin_url('update-core.php?action=upgrade-plugin&plugin=' . urlencode($this->slug) . '&_wpnonce=' . wp_create_nonce('upgrade-plugin_' . $this->slug))
        );
        
        error_log(sprintf(
            'Short URL: Update check result - Has update: %s, Current: %s, Latest: %s',
            $has_update ? 'Yes' : 'No',
            $this->version,
            $latest_version
        ));
        
        return $update_info;
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
    
    /**
     * Extract the tested WordPress version from release notes
     *
     * @param string $release_body Release notes body
     * @return string WordPress version or default
     */
    private function get_tested_wp_version($release_body) {
        // Default to current WordPress version
        $default = defined('WP_VERSION') ? WP_VERSION : '6.7';
        
        if (empty($release_body)) {
            return $default;
        }
        
        // Try to extract "Tested up to: X.X" from the release notes
        if (preg_match('/[tT]ested up to:?\s*(\d+\.\d+(?:\.\d+)?)/i', $release_body, $matches)) {
            return $matches[1];
        }
        
        // Try to extract "Tested: WordPress X.X" from the release notes
        if (preg_match('/[tT]ested:?\s*[wW]ord[pP]ress\s*(\d+\.\d+(?:\.\d+)?)/i', $release_body, $matches)) {
            return $matches[1];
        }
        
        // Try to extract from README.md format
        if (preg_match('/\*\*[tT]ested [uU]p to\*\*:?\s*(\d+\.\d+(?:\.\d+)?)/i', $release_body, $matches)) {
            return $matches[1];
        }
        
        // Return the default
        return $default;
    }
    
    /**
     * Process after WordPress update
     * 
     * @param object $upgrader WordPress upgrader instance
     * @param array  $options  Upgrader options
     */
    public function upgrader_process_complete($upgrader, $options) {
        // Only proceed if a plugin update was performed
        if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
            return;
        }
        
        // Clear cache to force fresh checks
        $this->clear_cache();
        
        error_log('Short URL: Upgrader process complete, update cache cleared');
    }
} 