<?php
/**
 * Short URL Generator
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Short URL Generator Class
 */
class Short_URL_Generator {
    /**
     * Generate a unique slug
     *
     * @param int  $length          Slug length
     * @param bool $include_numbers Whether to include numbers in the slug
     * @param bool $include_special Whether to include special characters
     * @return string Unique slug
     */
    public static function generate_slug($length = null, $include_numbers = true, $include_special = false) {
        global $wpdb;
        
        if (is_null($length)) {
            $length = (int) get_option('short_url_slug_length', 4);
        }
        
        // Use character set settings if no specific parameters are provided
        $use_settings = func_num_args() == 1 || func_num_args() == 0;
        
        if ($use_settings) {
            $use_lowercase = get_option('short_url_use_lowercase', 1);
            $use_uppercase = get_option('short_url_use_uppercase', 1);
            $use_numbers = get_option('short_url_use_numbers', 1);
            $use_special = get_option('short_url_use_special', 0);
            
            $chars = '';
            
            if ($use_lowercase) {
                $chars .= 'abcdefghijklmnopqrstuvwxyz';
            }
            
            if ($use_uppercase) {
                $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            }
            
            if ($use_numbers) {
                $chars .= '0123456789';
            }
            
            if ($use_special) {
                $chars .= '-_';  // Limit to URL-friendly special chars
            }
            
            // Fallback if nothing is selected
            if (empty($chars)) {
                $chars = 'abcdefghijklmnopqrstuvwxyz';
            }
        } else {
            // Use the provided parameters (backwards compatibility)
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            
            if ($include_numbers) {
                $chars .= '0123456789';
            }
            
            if ($include_special) {
                $chars .= '-_';
            }
        }
        
        $max_attempts = 10;
        $attempts = 0;
        $db = new Short_URL_DB();
        
        // Keep generating until we find a unique slug
        do {
            $slug = '';
            $chars_length = strlen($chars);
            
            for ($i = 0; $i < $length; $i++) {
                $slug .= $chars[mt_rand(0, $chars_length - 1)];
            }
            
            // Check if the slug exists
            $exists = $db->get_url_by_slug($slug);
            $attempts++;
            
            // If we've made too many attempts with this length, increase the length
            if ($attempts >= $max_attempts && $exists) {
                $length++;
                $attempts = 0;
            }
        } while ($exists && $attempts < $max_attempts);
        
        return $slug;
    }
    
    /**
     * Create a short URL for a post
     *
     * @param int    $post_id     Post ID
     * @param string $custom_slug Custom slug (optional)
     * @return array|WP_Error Short URL data or error
     */
    public static function create_for_post($post_id, $custom_slug = '') {
        // Check if post exists
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', __('Post not found.', 'short-url'));
        }
        
        // Check if this post already has a short URL
        $existing_url_id = get_post_meta($post_id, '_short_url_id', true);
        
        if ($existing_url_id) {
            $db = new Short_URL_DB();
            $url = $db->get_url($existing_url_id);
            
            if ($url) {
                return array(
                    'url_id' => $url->id,
                    'slug' => $url->slug,
                    'short_url' => self::get_short_url($url->slug),
                    'destination_url' => $url->destination_url,
                );
            }
        }
        
        // Generate a slug if none provided
        if (empty($custom_slug)) {
            // Check if we should use a prefix
            $prefix = get_option('short_url_link_prefix', '');
            $slug = $prefix . self::generate_slug();
        } else {
            $slug = $custom_slug;
        }
        
        // Create the short URL
        $db = new Short_URL_DB();
        
        $url_id = $db->create_url(array(
            'slug' => $slug,
            'destination_url' => get_permalink($post_id),
            'title' => $post->post_title,
            'description' => wp_trim_words(strip_tags($post->post_content), 20),
            'redirect_type' => (int) get_option('short_url_redirect_type', 301),
            'track_visits' => (bool) get_option('short_url_track_visits', true),
        ));
        
        if (!$url_id) {
            return new WP_Error('url_creation_failed', __('Failed to create short URL.', 'short-url'));
        }
        
        // Save the URL ID to post meta
        update_post_meta($post_id, '_short_url_id', $url_id);
        
        return array(
            'url_id' => $url_id,
            'slug' => $slug,
            'short_url' => self::get_short_url($slug),
            'destination_url' => get_permalink($post_id),
        );
    }
    
    /**
     * Create a short URL
     *
     * @param string $destination_url Destination URL
     * @param array  $args            Additional arguments
     * @return array|WP_Error Short URL data or error
     */
    public static function create_url($destination_url, $args = array()) {
        if (empty($destination_url)) {
            return new WP_Error('missing_destination_url', __('Destination URL is required.', 'short-url'));
        }
        
        // Validate URL
        if (!filter_var($destination_url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', __('Please enter a valid destination URL.', 'short-url'));
        }
        
        $defaults = array(
            'slug' => '',
            'title' => '',
            'description' => '',
            'created_by' => get_current_user_id(),
            'expires_at' => null,
            'password' => null,
            'redirect_type' => (int) get_option('short_url_redirect_type', 301),
            'nofollow' => false,
            'sponsored' => false,
            'forward_parameters' => false,
            'track_visits' => (bool) get_option('short_url_track_visits', true),
            'group_id' => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Generate a slug if none provided
        if (empty($args['slug'])) {
            // Check if we should use a prefix
            $prefix = get_option('short_url_link_prefix', '');
            $args['slug'] = $prefix . self::generate_slug();
        }
        
        // Create the short URL
        $db = new Short_URL_DB();
        
        $url_id = $db->create_url(array(
            'slug' => $args['slug'],
            'destination_url' => $destination_url,
            'title' => $args['title'],
            'description' => $args['description'],
            'created_by' => $args['created_by'],
            'expires_at' => $args['expires_at'],
            'password' => $args['password'] ? wp_hash_password($args['password']) : null,
            'redirect_type' => $args['redirect_type'],
            'nofollow' => $args['nofollow'] ? 1 : 0,
            'sponsored' => $args['sponsored'] ? 1 : 0,
            'forward_parameters' => $args['forward_parameters'] ? 1 : 0,
            'track_visits' => $args['track_visits'] ? 1 : 0,
            'group_id' => $args['group_id'],
        ));
        
        if (!$url_id) {
            return new WP_Error('url_creation_failed', __('Failed to create short URL.', 'short-url'));
        }
        
        return array(
            'url_id' => $url_id,
            'slug' => $args['slug'],
            'short_url' => self::get_short_url($args['slug']),
            'destination_url' => $destination_url,
        );
    }
    
    /**
     * Update a short URL
     *
     * @param int   $url_id URL ID
     * @param array $args   Arguments
     * @return array|WP_Error Updated URL data or error
     */
    public static function update_url($url_id, $args) {
        $db = new Short_URL_DB();
        
        // Get the current URL
        $url = $db->get_url($url_id);
        
        if (!$url) {
            return new WP_Error('url_not_found', __('Short URL not found.', 'short-url'));
        }
        
        // Update password if provided
        if (isset($args['password']) && !empty($args['password'])) {
            $args['password'] = wp_hash_password($args['password']);
        } elseif (isset($args['password']) && $args['password'] === '') {
            $args['password'] = null;
        } else {
            unset($args['password']); // Don't update if not provided
        }
        
        // Convert boolean values
        $boolean_fields = array('nofollow', 'sponsored', 'forward_parameters', 'track_visits', 'is_active');
        
        foreach ($boolean_fields as $field) {
            if (isset($args[$field])) {
                $args[$field] = $args[$field] ? 1 : 0;
            }
        }
        
        // Update the URL
        $result = $db->update_url($url_id, $args);
        
        if (!$result) {
            return new WP_Error('update_failed', __('Failed to update short URL.', 'short-url'));
        }
        
        // Get the updated URL
        $updated_url = $db->get_url($url_id);
        
        return array(
            'url_id' => $updated_url->id,
            'slug' => $updated_url->slug,
            'short_url' => self::get_short_url($updated_url->slug),
            'destination_url' => $updated_url->destination_url,
        );
    }
    
    /**
     * Delete a short URL
     *
     * @param int $url_id URL ID
     * @return bool Whether the URL was deleted
     */
    public static function delete_url($url_id) {
        $db = new Short_URL_DB();
        
        // Check if this URL is associated with a post
        global $wpdb;
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_short_url_id' AND meta_value = %d",
            $url_id
        ));
        
        if ($post_id) {
            // Remove the association
            delete_post_meta($post_id, '_short_url_id');
        }
        
        return $db->delete_url($url_id);
    }
    
    /**
     * Bulk generate short URLs for posts
     *
     * @param array $post_types Post types to generate URLs for
     * @param int   $limit      Maximum number of URLs to generate
     * @return array Results
     */
    public static function bulk_generate_for_posts($post_types = array(), $limit = 100) {
        if (empty($post_types)) {
            $post_types = get_option('short_url_auto_create_for_post_types', array('post', 'page'));
        }
        
        // Get posts without short URLs
        $args = array(
            'post_type' => $post_types,
            'posts_per_page' => $limit,
            'meta_query' => array(
                array(
                    'key' => '_short_url_id',
                    'compare' => 'NOT EXISTS',
                ),
            ),
            'post_status' => 'publish',
        );
        
        $query = new WP_Query($args);
        $results = array(
            'total' => $query->found_posts,
            'generated' => 0,
            'skipped' => 0,
            'errors' => 0,
        );
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $result = self::create_for_post($post_id);
                
                if (is_wp_error($result)) {
                    $results['errors']++;
                } else {
                    $results['generated']++;
                }
            }
            
            wp_reset_postdata();
        }
        
        $results['skipped'] = $results['total'] - $results['generated'] - $results['errors'];
        
        return $results;
    }
    
    /**
     * Get the full short URL
     *
     * @param string $slug URL slug
     * @return string Full short URL
     */
    public static function get_short_url($slug) {
        $custom_domain = get_option('short_url_custom_domain');
        
        if (!empty($custom_domain)) {
            return trailingslashit($custom_domain) . $slug;
        }
        
        return trailingslashit(site_url()) . $slug;
    }
    
    /**
     * Parse a URL to extract components
     *
     * @param string $url URL to parse
     * @return array URL components
     */
    public static function parse_url($url) {
        $parsed = parse_url($url);
        
        if (!$parsed) {
            return array();
        }
        
        // Add a scheme if not present
        if (!isset($parsed['scheme'])) {
            $with_scheme = 'https://' . $url;
            $parsed = parse_url($with_scheme);
            
            if (!$parsed) {
                return array();
            }
        }
        
        return $parsed;
    }
    
    /**
     * Check if a slug is valid
     *
     * @param string $slug Slug to check
     * @return bool Whether the slug is valid
     */
    public static function is_valid_slug($slug) {
        // Check for valid characters
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            return false;
        }
        
        // Check for WordPress reserved terms
        $reserved_terms = array(
            'admin', 'wp-admin', 'wp-content', 'wp-includes', 'wp-json',
            'feed', 'index.php', 'wp-login.php', 'wp-register.php',
            'comments', 'comment-page', 'edit', 'dashboard', 'admin-ajax.php',
            'plugins', 'themes', 'uploads', 'files', 'search', 'short-url',
        );
        
        if (in_array(strtolower($slug), $reserved_terms)) {
            return false;
        }
        
        return true;
    }
} 