<?php
/**
 * Short URL Analytics
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics Class
 */
class Short_URL_Analytics {
    /**
     * Record a visit to a short URL
     *
     * @param int   $url_id URL ID
     * @param array $data   Additional data
     * @return int|false Analytics ID on success, false on failure
     */
    public static function record_visit($url_id, $data = array()) {
        // Skip if tracking is disabled
        $track_visits = get_option('short_url_track_visits', true);
        
        if (!$track_visits) {
            return false;
        }
        
        // Get visitor data
        $visitor_data = self::get_visitor_data();
        
        // Filter bots if enabled
        $filter_bots = get_option('short_url_filter_bots', true);
        
        if ($filter_bots && $visitor_data['is_bot']) {
            return false;
        }
        
        // Check for excluded IPs
        $excluded_ips = get_option('short_url_excluded_ips', array());
        
        if (!empty($excluded_ips) && in_array($visitor_data['ip'], $excluded_ips)) {
            return false;
        }
        
        // Anonymize IP if enabled
        $anonymize_ip = get_option('short_url_anonymize_ip', false);
        
        if ($anonymize_ip) {
            $visitor_data['ip'] = self::anonymize_ip($visitor_data['ip']);
        }
        
        // Get geolocation data
        $geo_data = self::get_geolocation_data($visitor_data['ip']);
        
        // Prepare data for recording
        $analytics_data = array(
            'url_id' => $url_id,
            'visitor_ip' => $visitor_data['ip'],
            'visitor_user_agent' => $visitor_data['user_agent'],
            'referrer_url' => $visitor_data['referrer'],
            'browser' => $visitor_data['browser']['name'],
            'browser_version' => $visitor_data['browser']['version'],
            'operating_system' => $visitor_data['os']['name'],
            'device_type' => $visitor_data['device_type'],
            'country_code' => $geo_data['country_code'],
            'country_name' => $geo_data['country_name'],
            'region' => $geo_data['region'],
            'city' => $geo_data['city'],
            'latitude' => $geo_data['latitude'],
            'longitude' => $geo_data['longitude'],
        );
        
        // Merge with additional data
        $analytics_data = array_merge($analytics_data, $data);
        
        // Record the visit
        $db = new Short_URL_DB();
        $result = $db->record_analytics($analytics_data);
        
        if ($result) {
            // Increment the URL visit count
            $db->increment_url_visits($url_id);
        }
        
        return $result;
    }
    
    /**
     * Get visitor data
     *
     * @return array Visitor data
     */
    public static function get_visitor_data() {
        // Get IP address
        $ip = self::get_ip_address();
        
        // Get user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        
        // Get referrer
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        // Parse user agent
        $ua_data = self::parse_user_agent($user_agent);
        
        return array(
            'ip' => $ip,
            'user_agent' => $user_agent,
            'referrer' => $referrer,
            'browser' => $ua_data['browser'],
            'os' => $ua_data['os'],
            'device_type' => $ua_data['device_type'],
            'is_bot' => $ua_data['is_bot'],
        );
    }
    
    /**
     * Get the visitor's IP address
     *
     * @return string IP address
     */
    public static function get_ip_address() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return '127.0.0.1'; // Default to localhost
    }
    
    /**
     * Anonymize an IP address
     *
     * @param string $ip IP address
     * @return string Anonymized IP address
     */
    public static function anonymize_ip($ip) {
        if (empty($ip)) {
            return '';
        }
        
        $is_ipv6 = strpos($ip, ':') !== false;
        
        if ($is_ipv6) {
            // Anonymize IPv6 - keep first 4 blocks
            return preg_replace('/^([0-9a-f]{0,4}:[0-9a-f]{0,4}:[0-9a-f]{0,4}:[0-9a-f]{0,4}):.*$/i', '$1:0:0:0:0', $ip);
        } else {
            // Anonymize IPv4 - keep first 3 octets
            return preg_replace('/^(\d{1,3}\.\d{1,3}\.\d{1,3})\.\d{1,3}$/i', '$1.0', $ip);
        }
    }
    
    /**
     * Parse a user agent string
     *
     * @param string $user_agent User agent string
     * @return array Parsed data
     */
    public static function parse_user_agent($user_agent) {
        // Default values
        $data = array(
            'browser' => array(
                'name' => 'Unknown',
                'version' => '',
            ),
            'os' => array(
                'name' => 'Unknown',
                'version' => '',
            ),
            'device_type' => 'desktop',
            'is_bot' => false,
        );
        
        if (empty($user_agent)) {
            return $data;
        }
        
        // Check for known bots
        $bot_patterns = array(
            'googlebot' => 'GoogleBot',
            'bingbot' => 'BingBot',
            'slurp' => 'Yahoo! Slurp',
            'duckduckbot' => 'DuckDuckBot',
            'baiduspider' => 'Baidu Spider',
            'yandexbot' => 'YandexBot',
            'facebookexternalhit' => 'Facebook Bot',
            'twitterbot' => 'Twitter Bot',
            'rogerbot' => 'Moz Bot',
            'linkedinbot' => 'LinkedIn Bot',
            'semrushbot' => 'SEMrush Bot',
            'ahrefsbot' => 'Ahrefs Bot',
            'mj12bot' => 'Majestic Bot',
            'seznambot' => 'Seznam Bot',
            'robot' => 'Robot',
            'spider' => 'Spider',
            'crawler' => 'Crawler',
            'scraper' => 'Scraper',
            'bot' => 'Bot',
        );
        
        foreach ($bot_patterns as $pattern => $name) {
            if (stripos($user_agent, $pattern) !== false) {
                $data['is_bot'] = true;
                $data['browser']['name'] = $name;
                return $data;
            }
        }
        
        // Check for browser
        $browsers = array(
            'MSIE' => 'Internet Explorer',
            'Trident' => 'Internet Explorer',
            'Edge' => 'Microsoft Edge',
            'Edg' => 'Microsoft Edge',
            'OPR' => 'Opera',
            'Opera' => 'Opera',
            'Firefox' => 'Firefox',
            'Chrome' => 'Chrome',
            'Safari' => 'Safari',
            'Vivaldi' => 'Vivaldi',
            'Brave' => 'Brave',
            'UCBrowser' => 'UC Browser',
            'SamsungBrowser' => 'Samsung Browser',
            'YaBrowser' => 'Yandex Browser',
        );
        
        foreach ($browsers as $pattern => $name) {
            if (stripos($user_agent, $pattern) !== false) {
                $data['browser']['name'] = $name;
                
                // Get version
                if (preg_match('/' . $pattern . '[\s\/]([0-9.]+)/i', $user_agent, $matches)) {
                    $data['browser']['version'] = $matches[1];
                }
                
                break;
            }
        }
        
        // Check for OS
        $operating_systems = array(
            'Windows NT 10.0' => array('Windows', '10'),
            'Windows NT 6.3' => array('Windows', '8.1'),
            'Windows NT 6.2' => array('Windows', '8'),
            'Windows NT 6.1' => array('Windows', '7'),
            'Windows NT 6.0' => array('Windows', 'Vista'),
            'Windows NT 5.1' => array('Windows', 'XP'),
            'Windows NT 5.0' => array('Windows', '2000'),
            'Macintosh' => array('Mac OS', ''),
            'Mac OS X' => array('Mac OS', ''),
            'iPhone' => array('iOS', ''),
            'iPad' => array('iOS', ''),
            'Android' => array('Android', ''),
            'Linux' => array('Linux', ''),
            'Ubuntu' => array('Ubuntu', ''),
            'CrOS' => array('Chrome OS', ''),
        );
        
        foreach ($operating_systems as $pattern => $os_info) {
            if (stripos($user_agent, $pattern) !== false) {
                $data['os']['name'] = $os_info[0];
                $data['os']['version'] = $os_info[1];
                
                // Get version for Mac OS
                if ($pattern === 'Mac OS X' && preg_match('/Mac OS X ([0-9_]+)/i', $user_agent, $matches)) {
                    $data['os']['version'] = str_replace('_', '.', $matches[1]);
                }
                
                // Get version for iOS
                if (($pattern === 'iPhone' || $pattern === 'iPad') && preg_match('/OS ([0-9_]+)/i', $user_agent, $matches)) {
                    $data['os']['name'] = 'iOS';
                    $data['os']['version'] = str_replace('_', '.', $matches[1]);
                }
                
                // Get version for Android
                if ($pattern === 'Android' && preg_match('/Android ([0-9.]+)/i', $user_agent, $matches)) {
                    $data['os']['version'] = $matches[1];
                }
                
                break;
            }
        }
        
        // Determine device type
        $mobile_patterns = array('Mobile', 'Android', 'iPhone', 'iPad', 'Windows Phone', 'BlackBerry', 'Opera Mini', 'Opera Mobi');
        $tablet_patterns = array('iPad', 'Tablet', 'PlayBook', 'Nexus 7', 'Nexus 10', 'KFAPWI');
        
        foreach ($tablet_patterns as $pattern) {
            if (stripos($user_agent, $pattern) !== false) {
                $data['device_type'] = 'tablet';
                break;
            }
        }
        
        if ($data['device_type'] === 'desktop') {
            foreach ($mobile_patterns as $pattern) {
                if (stripos($user_agent, $pattern) !== false) {
                    $data['device_type'] = 'mobile';
                    break;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Get geolocation data for an IP address
     *
     * @param string $ip IP address
     * @return array Geolocation data
     */
    public static function get_geolocation_data($ip) {
        // Default data
        $data = array(
            'country_code' => null,
            'country_name' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
        );
        
        // Skip for local or private IPs
        if (empty($ip) || $ip === '127.0.0.1' || self::is_private_ip($ip)) {
            return $data;
        }
        
        // Try to use native WP geolocation if available (WooCommerce)
        if (function_exists('WC_Geolocation::geolocate_ip')) {
            $geo = WC_Geolocation::geolocate_ip($ip);
            
            if (!empty($geo['country'])) {
                $data['country_code'] = $geo['country'];
                
                // Load countries list for the name
                if (function_exists('WC') && isset(WC()->countries)) {
                    $countries = WC()->countries->get_countries();
                    $data['country_name'] = isset($countries[$geo['country']]) ? $countries[$geo['country']] : null;
                }
            }
            
            return $data;
        }
        
        // Use ip-api.com free geo IP API
        $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city,lat,lon");
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $geo_data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($geo_data['status']) && $geo_data['status'] === 'success') {
                $data['country_code'] = isset($geo_data['countryCode']) ? $geo_data['countryCode'] : null;
                $data['country_name'] = isset($geo_data['country']) ? $geo_data['country'] : null;
                $data['region'] = isset($geo_data['regionName']) ? $geo_data['regionName'] : null;
                $data['city'] = isset($geo_data['city']) ? $geo_data['city'] : null;
                $data['latitude'] = isset($geo_data['lat']) ? $geo_data['lat'] : null;
                $data['longitude'] = isset($geo_data['lon']) ? $geo_data['lon'] : null;
            }
        }
        
        return $data;
    }
    
    /**
     * Check if an IP address is private
     *
     * @param string $ip IP address
     * @return bool Whether the IP is private
     */
    public static function is_private_ip($ip) {
        $private_ranges = array(
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            'fc00::/7',
            'fe80::/10',
        );
        
        foreach ($private_ranges as $range) {
            if (self::ip_in_range($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if an IP address is in a range
     *
     * @param string $ip    IP address
     * @param string $range IP range in CIDR notation
     * @return bool Whether the IP is in range
     */
    private static function ip_in_range($ip, $range) {
        // IPv4 range check
        if (strpos($ip, ':') === false && strpos($range, ':') === false) {
            list($subnet, $bits) = explode('/', $range);
            $ip_decimal = ip2long($ip);
            $subnet_decimal = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            $subnet_decimal &= $mask;
            
            return ($ip_decimal & $mask) === $subnet_decimal;
        }
        
        // IPv6 range check (simplified)
        return false; // Simplified for now
    }
    
    /**
     * Get a dashboard summary of analytics
     *
     * @param array $args Query arguments
     * @return array Analytics summary
     */
    public static function get_dashboard_summary($args = array()) {
        global $wpdb;
        
        $db = new Short_URL_DB();
        $table_urls = $wpdb->prefix . 'short_urls';
        $table_analytics = $wpdb->prefix . 'short_url_analytics';
        
        $defaults = array(
            'days' => 30,
            'limit' => 5,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Calculate date range
        $end_date = current_time('mysql');
        $start_date = date('Y-m-d H:i:s', strtotime("-{$args['days']} days", strtotime($end_date)));
        
        // Get total clicks in the period
        $total_clicks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_analytics} WHERE visited_at BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));
        
        // Get total URLs
        $total_urls = $wpdb->get_var("SELECT COUNT(*) FROM {$table_urls}");
        
        // Get URLs with most clicks in the period
        $top_urls = $wpdb->get_results($wpdb->prepare(
            "SELECT u.id, u.slug, u.destination_url, u.title, COUNT(a.id) as clicks
            FROM {$table_urls} u
            JOIN {$table_analytics} a ON u.id = a.url_id
            WHERE a.visited_at BETWEEN %s AND %s
            GROUP BY u.id
            ORDER BY clicks DESC
            LIMIT %d",
            $start_date,
            $end_date,
            $args['limit']
        ));
        
        // Get top countries
        $top_countries = $wpdb->get_results($wpdb->prepare(
            "SELECT country_code, country_name, COUNT(*) as count
            FROM {$table_analytics}
            WHERE visited_at BETWEEN %s AND %s AND country_code IS NOT NULL
            GROUP BY country_code
            ORDER BY count DESC
            LIMIT %d",
            $start_date,
            $end_date,
            $args['limit']
        ));
        
        // Get clicks by day
        $clicks_by_day = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(visited_at) as date, COUNT(*) as count
            FROM {$table_analytics}
            WHERE visited_at BETWEEN %s AND %s
            GROUP BY DATE(visited_at)
            ORDER BY date ASC",
            $start_date,
            $end_date
        ));
        
        // Format clicks by day for charts
        $dates = array();
        $counts = array();
        
        foreach ($clicks_by_day as $day) {
            $dates[] = $day->date;
            $counts[] = (int) $day->count;
        }
        
        return array(
            'total_clicks' => (int) $total_clicks,
            'total_urls' => (int) $total_urls,
            'top_urls' => $top_urls,
            'top_countries' => $top_countries,
            'clicks_chart' => array(
                'dates' => $dates,
                'counts' => $counts,
            ),
        );
    }
    
    /**
     * Schedule data cleanup
     */
    public static function schedule_cleanup() {
        if (!wp_next_scheduled('short_url_cleanup_analytics')) {
            wp_schedule_event(time(), 'daily', 'short_url_cleanup_analytics');
        }
    }
    
    /**
     * Run data cleanup
     */
    public static function run_cleanup() {
        $db = new Short_URL_DB();
        $db->cleanup_analytics_data();
    }
} 