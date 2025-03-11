<?php
/**
 * Short URL Gutenberg Integration
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gutenberg Integration Class
 */
class Short_URL_Gutenberg {
    /**
     * Instance of this class
     *
     * @var Short_URL_Gutenberg
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return Short_URL_Gutenberg
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
        // Register block
        add_action('init', array($this, 'register_block'));
        
        // Add Short URL data to REST API for post types
        add_action('rest_api_init', array($this, 'register_rest_fields'));
    }

    /**
     * Register Gutenberg block
     */
    public function register_block() {
        // Only register if Gutenberg is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Register script
        wp_register_script(
            'short-url-block-editor',
            SHORT_URL_PLUGIN_URL . 'admin/js/blocks/short-url-block.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components', 'wp-data'),
            SHORT_URL_VERSION
        );
        
        // Register style
        wp_register_style(
            'short-url-block-editor',
            SHORT_URL_PLUGIN_URL . 'admin/css/blocks/short-url-block.css',
            array(),
            SHORT_URL_VERSION
        );
        
        // Register frontend style
        wp_register_style(
            'short-url-block',
            SHORT_URL_PLUGIN_URL . 'public/css/short-url-block.css',
            array(),
            SHORT_URL_VERSION
        );
        
        // Register block
        register_block_type('short-url/url-block', array(
            'editor_script' => 'short-url-block-editor',
            'editor_style' => 'short-url-block-editor',
            'style' => 'short-url-block',
            'attributes' => array(
                'urlId' => array(
                    'type' => 'number',
                ),
                'url' => array(
                    'type' => 'string',
                ),
                'title' => array(
                    'type' => 'string',
                ),
                'slug' => array(
                    'type' => 'string',
                ),
                'shortUrl' => array(
                    'type' => 'string',
                ),
                'qrCode' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
                'qrSize' => array(
                    'type' => 'number',
                    'default' => 150,
                ),
                'showTitle' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'showCopyButton' => array(
                    'type' => 'boolean',
                    'default' => true,
                ),
                'className' => array(
                    'type' => 'string',
                ),
                'align' => array(
                    'type' => 'string',
                ),
                'textAlign' => array(
                    'type' => 'string',
                    'default' => 'center',
                ),
                'showStats' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
            'render_callback' => array($this, 'render_short_url_block'),
        ));
        
        // Register URL selection block
        register_block_type('short-url/url-select', array(
            'editor_script' => 'short-url-block-editor',
            'editor_style' => 'short-url-block-editor',
            'attributes' => array(
                'urlId' => array(
                    'type' => 'number',
                ),
                'className' => array(
                    'type' => 'string',
                ),
            ),
        ));
        
        // Add translations
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('short-url-block-editor', 'short-url');
        }
        
        // Localize script with data
        wp_localize_script('short-url-block-editor', 'shortUrlBlock', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'apiRoot' => esc_url_raw(rest_url('short-url/v1')),
            'apiNonce' => wp_create_nonce('wp_rest'),
            'currentPostId' => get_the_ID(),
            'strings' => array(
                'title' => __('Short URL', 'short-url'),
                'description' => __('Display a short URL with optional QR code.', 'short-url'),
                'select' => __('Select Short URL', 'short-url'),
                'selectURL' => __('Select a URL', 'short-url'),
                'createNew' => __('Create New', 'short-url'),
                'loading' => __('Loading URLs...', 'short-url'),
                'noUrls' => __('No URLs found.', 'short-url'),
                'settings' => __('Display Settings', 'short-url'),
                'qrCode' => __('Show QR Code', 'short-url'),
                'qrSize' => __('QR Code Size', 'short-url'),
                'showTitle' => __('Show Title', 'short-url'),
                'showCopyButton' => __('Show Copy Button', 'short-url'),
                'textAlign' => __('Text Alignment', 'short-url'),
                'left' => __('Left', 'short-url'),
                'center' => __('Center', 'short-url'),
                'right' => __('Right', 'short-url'),
                'showStats' => __('Show Statistics', 'short-url'),
                'create' => __('Create Short URL', 'short-url'),
                'enterURL' => __('Enter URL', 'short-url'),
                'creating' => __('Creating...', 'short-url'),
                'error' => __('Error', 'short-url'),
            ),
        ));
    }

    /**
     * Register REST API fields
     */
    public function register_rest_fields() {
        // Get post types with short URL support
        $post_types = get_option('short_url_auto_create_for_post_types', array('post', 'page'));
        
        // Register field for each post type
        foreach ($post_types as $post_type) {
            register_rest_field($post_type, 'short_url', array(
                'get_callback' => array($this, 'get_post_short_url'),
                'schema' => array(
                    'description' => __('Short URL data', 'short-url'),
                    'type' => 'object',
                ),
            ));
        }
    }

    /**
     * Get short URL data for a post
     *
     * @param array $post Post object
     * @return array|null Short URL data
     */
    public function get_post_short_url($post) {
        $post_id = $post['id'];
        $url_id = get_post_meta($post_id, '_short_url_id', true);
        
        if (!$url_id) {
            return null;
        }
        
        $db = new Short_URL_DB();
        $url = $db->get_url($url_id);
        
        if (!$url) {
            return null;
        }
        
        return array(
            'id' => $url->id,
            'slug' => $url->slug,
            'short_url' => Short_URL_Generator::get_short_url($url->slug),
            'visits' => $url->visits,
        );
    }

    /**
     * Render short URL block
     *
     * @param array $attributes Block attributes
     * @return string Rendered block
     */
    public function render_short_url_block($attributes) {
        // Get attributes
        $url_id = isset($attributes['urlId']) ? intval($attributes['urlId']) : 0;
        $short_url = isset($attributes['shortUrl']) ? esc_url($attributes['shortUrl']) : '';
        $title = isset($attributes['title']) ? esc_html($attributes['title']) : '';
        $show_qr = isset($attributes['qrCode']) ? (bool) $attributes['qrCode'] : false;
        $qr_size = isset($attributes['qrSize']) ? intval($attributes['qrSize']) : 150;
        $show_title = isset($attributes['showTitle']) ? (bool) $attributes['showTitle'] : true;
        $show_copy = isset($attributes['showCopyButton']) ? (bool) $attributes['showCopyButton'] : true;
        $text_align = isset($attributes['textAlign']) ? sanitize_key($attributes['textAlign']) : 'center';
        $show_stats = isset($attributes['showStats']) ? (bool) $attributes['showStats'] : false;
        
        // Check if we have a URL
        if (empty($short_url)) {
            return '';
        }
        
        // Get additional data if needed
        $visits = 0;
        
        if ($url_id && $show_stats) {
            $db = new Short_URL_DB();
            $url = $db->get_url($url_id);
            
            if ($url) {
                $visits = $url->visits;
            }
        }
        
        // Get CSS classes
        $classes = array('short-url-block');
        
        if (isset($attributes['className'])) {
            $classes[] = $attributes['className'];
        }
        
        if (isset($attributes['align'])) {
            $classes[] = 'align' . $attributes['align'];
        }
        
        $classes[] = 'has-text-align-' . $text_align;
        
        // Build block output
        $output = '<div class="' . esc_attr(implode(' ', $classes)) . '">';
        
        // Show title if enabled
        if ($show_title && !empty($title)) {
            $output .= '<h4 class="short-url-title">' . esc_html($title) . '</h4>';
        }
        
        // URL container
        $output .= '<div class="short-url-link-container">';
        $output .= '<a href="' . esc_url($short_url) . '" class="short-url-link" target="_blank" rel="noopener">' . esc_html($short_url) . '</a>';
        
        // Copy button
        if ($show_copy) {
            $output .= '<button class="short-url-copy-button" data-clipboard-text="' . esc_attr($short_url) . '">';
            $output .= '<span class="short-url-copy-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"></path></svg></span>';
            $output .= '<span class="short-url-copy-tooltip">' . esc_html__('Copy', 'short-url') . '</span>';
            $output .= '</button>';
        }
        
        $output .= '</div>';
        
        // Show QR code if enabled
        if ($show_qr) {
            $qr_url = Short_URL_Utils::get_qr_code_url($short_url, $qr_size);
            $output .= '<div class="short-url-qr-container">';
            $output .= '<img src="' . esc_url($qr_url) . '" alt="' . esc_attr__('QR Code', 'short-url') . '" class="short-url-qr-code" width="' . esc_attr($qr_size) . '" height="' . esc_attr($qr_size) . '">';
            $output .= '</div>';
        }
        
        // Show stats if enabled
        if ($show_stats) {
            $output .= '<div class="short-url-stats">';
            $output .= '<span class="short-url-visits">' . esc_html(sprintf(
                _n('%s visit', '%s visits', $visits, 'short-url'),
                number_format_i18n($visits)
            )) . '</span>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        // Add clipboard.js script if copy button is shown
        if ($show_copy) {
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
                        var tooltip = e.trigger.querySelector(".short-url-copy-tooltip");
                        var originalText = tooltip.textContent;
                        
                        tooltip.textContent = "' . esc_js(__('Copied!', 'short-url')) . '";
                        tooltip.classList.add("short-url-tooltip-success");
                        
                        setTimeout(function() {
                            tooltip.textContent = originalText;
                            tooltip.classList.remove("short-url-tooltip-success");
                        }, 2000);
                    });
                });
            ');
        }
        
        return $output;
    }
} 