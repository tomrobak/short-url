<?php
/**
 * Short URL Utilities
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utilities Class
 */
class Short_URL_Utils {
    /**
     * Sanitize a URL for output
     *
     * @param string $url URL to sanitize
     * @return string Sanitized URL
     */
    public static function sanitize_url($url) {
        return esc_url($url);
    }
    
    /**
     * Get admin page URL
     *
     * @param string $page Admin page
     * @param array  $args Additional query args
     * @return string Admin page URL
     */
    public static function get_admin_page_url($page = '', $args = array()) {
        $url = admin_url('admin.php?page=short-url');
        
        if (!empty($page)) {
            $url = admin_url('admin.php?page=short-url-' . $page);
        }
        
        if (!empty($args)) {
            $url = add_query_arg($args, $url);
        }
        
        return $url;
    }
    
    /**
     * Format a date for display
     *
     * @param string $date       Date string
     * @param bool   $show_time  Whether to show time
     * @param bool   $human_diff Whether to show human readable time difference
     * @return string Formatted date
     */
    public static function format_date($date, $show_time = true, $human_diff = false) {
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        
        if ($human_diff) {
            return human_time_diff($timestamp, current_time('timestamp')) . ' ' . __('ago', 'short-url');
        }
        
        $format = get_option('date_format');
        
        if ($show_time) {
            $format .= ' ' . get_option('time_format');
        }
        
        return date_i18n($format, $timestamp);
    }
    
    /**
     * Format a number for display
     *
     * @param int $number Number to format
     * @return string Formatted number
     */
    public static function format_number($number) {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        
        return number_format_i18n($number);
    }
    
    /**
     * Truncate a string
     *
     * @param string $string String to truncate
     * @param int    $length Maximum length
     * @param string $more   Text to append if truncated
     * @return string Truncated string
     */
    public static function truncate($string, $length = 50, $more = '&hellip;') {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        $string = substr($string, 0, $length);
        
        // If the string has been truncated, and the last character is a space, trim it
        if ($string[strlen($string) - 1] === ' ') {
            $string = rtrim($string);
        }
        
        return $string . $more;
    }
    
    /**
     * Check if a URL is an affiliate link
     *
     * @param string $url URL to check
     * @return bool Whether the URL is an affiliate link
     */
    public static function is_affiliate_link($url) {
        // Common affiliate URL patterns
        $affiliate_patterns = array(
            'ref=',
            'affiliate=',
            'aff=',
            'partner=',
            'referrer=',
            'referral=',
        );
        
        foreach ($affiliate_patterns as $pattern) {
            if (strpos($url, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate QR code URL
     *
     * @param string $short_url Short URL
     * @param int    $size      QR code size in pixels
     * @param string $format    Image format (png or jpg)
     * @return string QR code URL
     */
    public static function get_qr_code_url($short_url, $size = 150, $format = 'png') {
        // Validate size
        $size = max(50, min(1000, intval($size)));
        
        // Validate format
        $format = strtolower($format);
        if (!in_array($format, array('png', 'jpg'))) {
            $format = 'png';
        }
        
        // Use Google Charts API
        $url = 'https://chart.googleapis.com/chart?cht=qr&chs=' . $size . 'x' . $size . '&chl=' . urlencode($short_url);
        
        // Add format if jpg
        if ($format === 'jpg') {
            $url .= '&chof=jpg';
        }
        
        return $url;
    }
    
    /**
     * Generate social sharing links
     *
     * @param string $url  URL to share
     * @param string $text Text to share
     * @return array Social sharing links
     */
    public static function get_social_sharing_links($url, $text = '') {
        $encoded_url = urlencode($url);
        $encoded_text = urlencode($text);
        
        return array(
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url,
            'twitter' => 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_text,
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url,
            'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $encoded_url . '&description=' . $encoded_text,
            'whatsapp' => 'https://wa.me/?text=' . $encoded_text . '%20' . $encoded_url,
            'telegram' => 'https://t.me/share/url?url=' . $encoded_url . '&text=' . $encoded_text,
            'email' => 'mailto:?subject=' . $encoded_text . '&body=' . $encoded_url,
        );
    }
    
    /**
     * Create UTM URL
     *
     * @param string $url    Base URL
     * @param array  $params UTM parameters
     * @return string URL with UTM parameters
     */
    public static function create_utm_url($url, $params = array()) {
        $defaults = array(
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_term' => '',
            'utm_content' => '',
        );
        
        $params = array_filter(wp_parse_args($params, $defaults));
        
        return add_query_arg($params, $url);
    }
    
    /**
     * Parse UTM parameters from a URL
     *
     * @param string $url URL to parse
     * @return array UTM parameters
     */
    public static function parse_utm_params($url) {
        $query = parse_url($url, PHP_URL_QUERY);
        
        if (empty($query)) {
            return array();
        }
        
        parse_str($query, $params);
        
        $utm_params = array(
            'utm_source' => isset($params['utm_source']) ? $params['utm_source'] : '',
            'utm_medium' => isset($params['utm_medium']) ? $params['utm_medium'] : '',
            'utm_campaign' => isset($params['utm_campaign']) ? $params['utm_campaign'] : '',
            'utm_term' => isset($params['utm_term']) ? $params['utm_term'] : '',
            'utm_content' => isset($params['utm_content']) ? $params['utm_content'] : '',
        );
        
        return array_filter($utm_params);
    }
    
    /**
     * Get country flag emoji
     *
     * @param string $country_code Two-letter country code
     * @return string Flag emoji
     */
    public static function get_country_flag($country_code) {
        if (empty($country_code) || strlen($country_code) !== 2) {
            return '';
        }
        
        // Convert country code to regional indicator symbols (flag emoji)
        $country_code = strtoupper($country_code);
        $flag = '';
        
        for ($i = 0; $i < 2; $i++) {
            $flag .= mb_chr(ord($country_code[$i]) - ord('A') + 0x1F1E6);
        }
        
        return $flag;
    }
    
    /**
     * Detect device type from user agent
     *
     * @param string $user_agent User agent string
     * @return string Device type (desktop, mobile, tablet)
     */
    public static function detect_device_type($user_agent) {
        $tablet_patterns = array(
            'ipad',
            'tablet',
            'playbook',
            'silk',
            'android 3',
        );
        
        $mobile_patterns = array(
            'mobile',
            'android',
            'iphone',
            'ipod',
            'blackberry',
            'windows phone',
            'opera mini',
            'opera mobi',
        );
        
        // Check for tablet first
        foreach ($tablet_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                return 'tablet';
            }
        }
        
        // Then check for mobile
        foreach ($mobile_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                return 'mobile';
            }
        }
        
        // Default to desktop
        return 'desktop';
    }
    
    /**
     * Get device icon
     *
     * @param string $device_type Device type
     * @return string Device icon HTML
     */
    public static function get_device_icon($device_type) {
        switch ($device_type) {
            case 'mobile':
                return '<span class="dashicons dashicons-smartphone"></span>';
            case 'tablet':
                return '<span class="dashicons dashicons-tablet"></span>';
            case 'desktop':
                return '<span class="dashicons dashicons-desktop"></span>';
            default:
                return '<span class="dashicons dashicons-desktop"></span>';
        }
    }
    
    /**
     * Get browser icon
     *
     * @param string $browser Browser name
     * @return string Browser icon HTML
     */
    public static function get_browser_icon($browser) {
        $browser_icons = array(
            'Chrome' => 'fab fa-chrome',
            'Firefox' => 'fab fa-firefox',
            'Safari' => 'fab fa-safari',
            'Internet Explorer' => 'fab fa-internet-explorer',
            'Edge' => 'fab fa-edge',
            'Opera' => 'fab fa-opera',
        );
        
        if (isset($browser_icons[$browser])) {
            return '<i class="' . $browser_icons[$browser] . '"></i>';
        }
        
        return '<i class="fas fa-globe"></i>';
    }
    
    /**
     * Get OS icon
     *
     * @param string $os OS name
     * @return string OS icon HTML
     */
    public static function get_os_icon($os) {
        $os_icons = array(
            'Windows' => 'fab fa-windows',
            'Mac OS' => 'fab fa-apple',
            'iOS' => 'fab fa-apple',
            'Android' => 'fab fa-android',
            'Linux' => 'fab fa-linux',
            'Ubuntu' => 'fab fa-ubuntu',
        );
        
        if (isset($os_icons[$os])) {
            return '<i class="' . $os_icons[$os] . '"></i>';
        }
        
        return '<i class="fas fa-desktop"></i>';
    }
    
    /**
     * Check if a string is a valid JSON
     *
     * @param string $string String to check
     * @return bool Whether the string is a valid JSON
     */
    public static function is_json($string) {
        if (!is_string($string) || empty($string)) {
            return false;
        }
        
        json_decode($string);
        
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * Get color based on a string (for charts)
     *
     * @param string $string String to hash
     * @param int    $index  Color index
     * @return string Hex color
     */
    public static function string_to_color($string, $index = 0) {
        // Predefined colors for charts
        $colors = array(
            '#4285F4', // Blue
            '#EA4335', // Red
            '#FBBC05', // Yellow
            '#34A853', // Green
            '#FF6D01', // Orange
            '#46BFBD', // Teal
            '#AC92EC', // Purple
            '#FF8A80', // Light Red
            '#A7FFEB', // Light Teal
            '#FFD180', // Light Orange
        );
        
        if (isset($colors[$index])) {
            return $colors[$index];
        }
        
        // If index is out of range, generate a color based on the string
        $hash = md5($string);
        $hex = '#' . substr($hash, 0, 6);
        
        return $hex;
    }
    
    /**
     * Generate a random string
     *
     * @param int  $length          Length of the string
     * @param bool $include_numbers Whether to include numbers
     * @param bool $include_special Whether to include special characters
     * @return string Random string
     */
    public static function random_string($length = 8, $include_numbers = true, $include_special = false) {
        // Check if we should use settings instead of parameters
        $use_settings = func_num_args() == 1;
        
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
            // Use the function parameters (backward compatibility)
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            
            if ($include_numbers) {
                $chars .= '0123456789';
            }
            
            if ($include_special) {
                $chars .= '-_'; // Limit to URL-friendly special chars
            }
        }
        
        $string = '';
        $chars_length = strlen($chars);
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[rand(0, $chars_length - 1)];
        }
        
        return $string;
    }
    
    /**
     * Clean up old transients
     *
     * @return int Number of deleted transients
     */
    public static function cleanup_transients() {
        global $wpdb;
        
        // Get expired transients
        $transients = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} 
            WHERE option_name LIKE '%_transient_timeout_%' 
            AND option_value < " . time()
        );
        
        $count = 0;
        
        foreach ($transients as $transient) {
            $name = str_replace('_transient_timeout_', '', $transient);
            
            if (delete_transient($name)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Extract domain or slug from a URL or slug
     * 
     * @param string $url URL or slug to extract domain from
     * @return string Domain or slug
     */
    public static function get_domain_from_url($url) {
        // If it's already a full URL
        if (strpos($url, 'http') === 0) {
            // Parse the URL and extract the host
            $parsed_url = parse_url($url);
            if (isset($parsed_url['host'])) {
                return $parsed_url['host'];
            }
        }
        
        // If it's just a slug, return it as is
        return $url;
    }
} 