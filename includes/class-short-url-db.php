<?php
/**
 * Short URL Database Operations
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Operations Class
 */
class Short_URL_DB {
    /**
     * URLs table name
     *
     * @var string
     */
    private $table_urls;
    
    /**
     * Analytics table name
     *
     * @var string
     */
    private $table_analytics;
    
    /**
     * Groups table name
     *
     * @var string
     */
    private $table_groups;
    
    /**
     * Domains table name
     *
     * @var string
     */
    private $table_domains;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        
        $this->table_urls = $wpdb->prefix . 'short_urls';
        $this->table_analytics = $wpdb->prefix . 'short_url_analytics';
        $this->table_groups = $wpdb->prefix . 'short_url_groups';
        $this->table_domains = $wpdb->prefix . 'short_url_domains';
    }
    
    /**
     * Create a new short URL
     *
     * @param array $args URL data
     * @return int|false URL ID on success, false on failure
     */
    public function create_url($args) {
        global $wpdb;
        
        $defaults = array(
            'slug' => '',
            'destination_url' => '',
            'title' => '',
            'description' => '',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'expires_at' => null,
            'password' => null,
            'redirect_type' => 301,
            'nofollow' => 0,
            'sponsored' => 0,
            'forward_parameters' => 0,
            'track_visits' => 1,
            'visits' => 0,
            'group_id' => null,
            'is_active' => 1,
        );
        
        $data = wp_parse_args($args, $defaults);
        
        // Ensure required fields
        if (empty($data['slug']) || empty($data['destination_url'])) {
            return false;
        }
        
        // Insert the URL
        $result = $wpdb->insert(
            $this->table_urls,
            $data,
            array(
                '%s', // slug
                '%s', // destination_url
                '%s', // title
                '%s', // description
                '%d', // created_by
                '%s', // created_at
                '%s', // updated_at
                '%s', // expires_at
                '%s', // password
                '%d', // redirect_type
                '%d', // nofollow
                '%d', // sponsored
                '%d', // forward_parameters
                '%d', // track_visits
                '%d', // visits
                '%d', // group_id
                '%d', // is_active
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        $url_id = $wpdb->insert_id;
        
        // If this URL belongs to a group, update the group's link count
        if (!empty($data['group_id'])) {
            $this->update_group_links_count($data['group_id']);
        }
        
        return $url_id;
    }
    
    /**
     * Update a short URL
     *
     * @param int   $id   URL ID
     * @param array $args URL data
     * @return bool True on success, false on failure
     */
    public function update_url($id, $args) {
        global $wpdb;
        
        // Get the current URL data
        $current_url = $this->get_url($id);
        
        if (!$current_url) {
            return false;
        }
        
        $data = array_merge((array) $current_url, $args);
        
        // Always update the updated_at timestamp
        $data['updated_at'] = current_time('mysql');
        
        // Update the URL
        $result = $wpdb->update(
            $this->table_urls,
            $data,
            array('id' => $id),
            array(
                '%s', // slug
                '%s', // destination_url
                '%s', // title
                '%s', // description
                '%d', // created_by
                '%s', // created_at
                '%s', // updated_at
                '%s', // expires_at
                '%s', // password
                '%d', // redirect_type
                '%d', // nofollow
                '%d', // sponsored
                '%d', // forward_parameters
                '%d', // track_visits
                '%d', // visits
                '%d', // group_id
                '%d', // is_active
            ),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        // If the group_id changed, update both old and new group link counts
        if (isset($args['group_id']) && $current_url->group_id !== $args['group_id']) {
            if (!empty($current_url->group_id)) {
                $this->update_group_links_count($current_url->group_id);
            }
            
            if (!empty($args['group_id'])) {
                $this->update_group_links_count($args['group_id']);
            }
        }
        
        return true;
    }
    
    /**
     * Delete a short URL
     *
     * @param int $id URL ID
     * @return bool True on success, false on failure
     */
    public function delete_url($id) {
        global $wpdb;
        
        // Get the URL to check if it belongs to a group
        $url = $this->get_url($id);
        
        if (!$url) {
            return false;
        }
        
        // Delete associated analytics data
        $wpdb->delete(
            $this->table_analytics,
            array('url_id' => $id),
            array('%d')
        );
        
        // Delete the URL
        $result = $wpdb->delete(
            $this->table_urls,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        // Update the group's link count if needed
        if (!empty($url->group_id)) {
            $this->update_group_links_count($url->group_id);
        }
        
        return true;
    }
    
    /**
     * Get a short URL by ID
     *
     * @param int $id URL ID
     * @return object|null URL object or null if not found
     */
    public function get_url($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_urls} WHERE id = %d",
                $id
            )
        );
    }
    
    /**
     * Get a short URL by slug
     *
     * @param string $slug URL slug
     * @return object|null URL object or null if not found
     */
    public function get_url_by_slug($slug) {
        global $wpdb;
        
        // Check if case sensitivity is enabled
        $case_sensitive = get_option('short_url_case_sensitive', false);
        
        if ($case_sensitive) {
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_urls} WHERE slug = %s AND is_active = 1",
                    $slug
                )
            );
        } else {
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$this->table_urls} WHERE LOWER(slug) = LOWER(%s) AND is_active = 1",
                    $slug
                )
            );
        }
    }
    
    /**
     * Get URLs with pagination
     *
     * @param array $args Query arguments
     * @return array Array of URLs and pagination info
     */
    public function get_urls($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => '',
            'group_id' => null,
            'include_inactive' => false,
        );
        
        $args = wp_parse_args($args, $defaults);
        $limit = $args['per_page'];
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Build the query
        $query = "SELECT * FROM {$this->table_urls} WHERE 1=1";
        $count_query = "SELECT COUNT(*) FROM {$this->table_urls} WHERE 1=1";
        
        $query_args = array();
        
        // Search
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query .= " AND (slug LIKE %s OR destination_url LIKE %s OR title LIKE %s)";
            $count_query .= " AND (slug LIKE %s OR destination_url LIKE %s OR title LIKE %s)";
            $query_args[] = $search_term;
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        // Filter by group
        if (isset($args['group_id']) && $args['group_id'] !== null) {
            $query .= " AND group_id = %d";
            $count_query .= " AND group_id = %d";
            $query_args[] = $args['group_id'];
        }
        
        // Include inactive URLs
        if (!$args['include_inactive']) {
            $query .= " AND is_active = 1";
            $count_query .= " AND is_active = 1";
        }
        
        // Order
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $orderby_options = array('created_at', 'updated_at', 'visits', 'slug', 'destination_url');
        $orderby = in_array($args['orderby'], $orderby_options) ? $args['orderby'] : 'created_at';
        
        $query .= " ORDER BY {$orderby} {$order}";
        
        // Pagination
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $limit;
        $query_args[] = $offset;
        
        // Prepare the queries
        $prepared_query = !empty($query_args) ? $wpdb->prepare($query, $query_args) : $query;
        $count_query_args = array_slice($query_args, 0, -2); // Remove LIMIT and OFFSET args
        $prepared_count_query = !empty($count_query_args) ? $wpdb->prepare($count_query, $count_query_args) : $count_query;
        
        // Get the results
        $results = $wpdb->get_results($prepared_query);
        $total = $wpdb->get_var($prepared_count_query);
        
        return array(
            'items' => $results,
            'total' => (int) $total,
            'total_pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        );
    }
    
    /**
     * Get a URL specifically for redirection
     *
     * @param string $slug URL slug
     * @return array|null URL array or null if not found
     */
    public function get_url_for_redirect($slug) {
        global $wpdb;
        
        // The fields we need for redirection
        $fields = "id, slug, destination_url, redirect_type, nofollow, sponsored, forward_parameters, track_visits, expires_at, password, is_active";
        
        // Check if case sensitivity is enabled
        $case_sensitive = get_option('short_url_case_sensitive', false);
        
        if ($case_sensitive) {
            $query = $wpdb->prepare(
                "SELECT {$fields} FROM {$this->table_urls} WHERE slug = %s",
                $slug
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT {$fields} FROM {$this->table_urls} WHERE LOWER(slug) = LOWER(%s)",
                $slug
            );
        }
        
        $url = $wpdb->get_row($query, ARRAY_A);
        
        if (!$url) {
            return null;
        }
        
        // Check if the URL is active
        if (!$url['is_active']) {
            return null;
        }
        
        // Check if the URL has expired
        if (!empty($url['expires_at']) && strtotime($url['expires_at']) < time()) {
            return null;
        }
        
        return $url;
    }
    
    /**
     * Increment the visit count for a URL
     *
     * @param int $id URL ID
     * @return bool True on success, false on failure
     */
    public function increment_url_visits($id) {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_urls} SET visits = visits + 1 WHERE id = %d",
                $id
            )
        );
    }
    
    /**
     * Record analytics data for a URL visit
     *
     * @param array $data Analytics data
     * @return int|false Analytics ID on success, false on failure
     */
    public function record_analytics($data) {
        global $wpdb;
        
        $defaults = array(
            'url_id' => 0,
            'visitor_ip' => null,
            'visitor_user_agent' => null,
            'referrer_url' => null,
            'visited_at' => current_time('mysql'),
            'browser' => null,
            'browser_version' => null,
            'operating_system' => null,
            'device_type' => null,
            'country_code' => null,
            'country_name' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Ensure URL ID is set
        if (empty($data['url_id'])) {
            return false;
        }
        
        // Insert the analytics data
        $result = $wpdb->insert(
            $this->table_analytics,
            $data,
            array(
                '%d', // url_id
                '%s', // visitor_ip
                '%s', // visitor_user_agent
                '%s', // referrer_url
                '%s', // visited_at
                '%s', // browser
                '%s', // browser_version
                '%s', // operating_system
                '%s', // device_type
                '%s', // country_code
                '%s', // country_name
                '%s', // region
                '%s', // city
                '%f', // latitude
                '%f', // longitude
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Create a new group
     *
     * @param array $args Group data
     * @return int|false Group ID on success, false on failure
     */
    public function create_group($args) {
        global $wpdb;
        
        $defaults = array(
            'name' => '',
            'description' => '',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'links_count' => 0,
        );
        
        $data = wp_parse_args($args, $defaults);
        
        // Ensure required fields
        if (empty($data['name'])) {
            return false;
        }
        
        // Insert the group
        $result = $wpdb->insert(
            $this->table_groups,
            $data,
            array(
                '%s', // name
                '%s', // description
                '%d', // created_by
                '%s', // created_at
                '%s', // updated_at
                '%d', // links_count
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update a group
     *
     * @param int   $id   Group ID
     * @param array $args Group data
     * @return bool True on success, false on failure
     */
    public function update_group($id, $args) {
        global $wpdb;
        
        // Get the current group data
        $current_group = $this->get_group($id);
        
        if (!$current_group) {
            return false;
        }
        
        $data = array_merge((array) $current_group, $args);
        
        // Always update the updated_at timestamp
        $data['updated_at'] = current_time('mysql');
        
        // Update the group
        $result = $wpdb->update(
            $this->table_groups,
            $data,
            array('id' => $id),
            array(
                '%s', // name
                '%s', // description
                '%d', // created_by
                '%s', // created_at
                '%s', // updated_at
                '%d', // links_count
            ),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a group
     *
     * @param int $id Group ID
     * @return bool True on success, false on failure
     */
    public function delete_group($id) {
        global $wpdb;
        
        // Delete the group
        $result = $wpdb->delete(
            $this->table_groups,
            array('id' => $id),
            array('%d')
        );
        
        if ($result === false) {
            return false;
        }
        
        // Update all URLs in this group to have no group
        $wpdb->update(
            $this->table_urls,
            array('group_id' => null),
            array('group_id' => $id),
            array('%d'),
            array('%d')
        );
        
        return true;
    }
    
    /**
     * Get a group by ID
     *
     * @param int $id Group ID
     * @return object|null Group object or null if not found
     */
    public function get_group($id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_groups} WHERE id = %d",
                $id
            )
        );
    }
    
    /**
     * Get groups with pagination
     *
     * @param array $args Query arguments
     * @return array Array of groups and pagination info
     */
    public function get_groups($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'name',
            'order' => 'ASC',
            'search' => '',
            'count' => false,
        );
        
        $args = wp_parse_args($args, $defaults);
        $limit = $args['per_page'];
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Build the query
        $query = "SELECT * FROM {$this->table_groups} WHERE 1=1";
        $count_query = "SELECT COUNT(*) FROM {$this->table_groups} WHERE 1=1";
        
        $query_args = array();
        
        // Search
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $query .= " AND (name LIKE %s OR description LIKE %s)";
            $count_query .= " AND (name LIKE %s OR description LIKE %s)";
            $query_args[] = $search_term;
            $query_args[] = $search_term;
        }
        
        // Order
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $orderby_options = array('name', 'created_at', 'links_count');
        $orderby = in_array($args['orderby'], $orderby_options) ? $args['orderby'] : 'name';
        
        $query .= " ORDER BY {$orderby} {$order}";
        
        // If we just want the count, return it
        if ($args['count']) {
            $count_query_args = array_slice($query_args, 0, -2); // Remove LIMIT and OFFSET args if they exist
            $prepared_count_query = !empty($count_query_args) ? $wpdb->prepare($count_query, $count_query_args) : $count_query;
            return (int) $wpdb->get_var($prepared_count_query);
        }
        
        // Pagination
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $limit;
        $query_args[] = $offset;
        
        // Prepare the queries
        $prepared_query = !empty($query_args) ? $wpdb->prepare($query, $query_args) : $query;
        
        // Get the results
        return $wpdb->get_results($prepared_query);
    }
    
    /**
     * Update a group's links count
     *
     * @param int $group_id Group ID
     * @return bool True on success, false on failure
     */
    private function update_group_links_count($group_id) {
        global $wpdb;
        
        // Count links in this group
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_urls} WHERE group_id = %d",
                $group_id
            )
        );
        
        // Update the group
        return $this->update_group($group_id, array('links_count' => (int) $count));
    }
    
    /**
     * Clean up old analytics data based on retention period
     *
     * @return int Number of records deleted
     */
    public function cleanup_analytics_data() {
        global $wpdb;
        
        // Get retention period
        $retention_days = (int) get_option('short_url_data_retention_period', 365);
        
        if ($retention_days <= 0) {
            return 0;
        }
        
        $date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Delete old records
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_analytics} WHERE visited_at < %s",
                $date
            )
        );
        
        return $result !== false ? $result : 0;
    }
    
    /**
     * Get analytics for a specific URL with pagination
     *
     * @param int   $url_id URL ID
     * @param array $args   Query arguments
     * @return array Array of analytics and pagination info
     */
    public function get_url_analytics($url_id, $args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'visited_at',
            'order' => 'DESC',
            'date_from' => null,
            'date_to' => null,
        );
        
        $args = wp_parse_args($args, $defaults);
        $limit = $args['per_page'];
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Build the query
        $query = "SELECT * FROM {$this->table_analytics} WHERE url_id = %d";
        $count_query = "SELECT COUNT(*) FROM {$this->table_analytics} WHERE url_id = %d";
        
        $query_args = array($url_id);
        
        // Date range
        if (!empty($args['date_from'])) {
            $query .= " AND visited_at >= %s";
            $count_query .= " AND visited_at >= %s";
            $query_args[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $query .= " AND visited_at <= %s";
            $count_query .= " AND visited_at <= %s";
            $query_args[] = $args['date_to'];
        }
        
        // Order
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $orderby_options = array('visited_at', 'country_code', 'browser', 'device_type');
        $orderby = in_array($args['orderby'], $orderby_options) ? $args['orderby'] : 'visited_at';
        
        $query .= " ORDER BY {$orderby} {$order}";
        
        // Pagination
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $limit;
        $query_args[] = $offset;
        
        // Prepare the queries
        $prepared_query = $wpdb->prepare($query, $query_args);
        $count_query_args = array_slice($query_args, 0, -2); // Remove LIMIT and OFFSET args
        $prepared_count_query = $wpdb->prepare($count_query, $count_query_args);
        
        // Get the results
        $results = $wpdb->get_results($prepared_query);
        $total = $wpdb->get_var($prepared_count_query);
        
        return array(
            'items' => $results,
            'total' => (int) $total,
            'total_pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
            'per_page' => $args['per_page'],
        );
    }
    
    /**
     * Get analytics summary for a URL
     *
     * @param int $url_id URL ID
     * @return array Analytics summary
     */
    public function get_url_analytics_summary($url_id) {
        global $wpdb;
        
        // Get total visits
        $total_visits = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_analytics} WHERE url_id = %d",
                $url_id
            )
        );
        
        // Get top countries
        $top_countries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT country_code, country_name, COUNT(*) as count 
                FROM {$this->table_analytics} 
                WHERE url_id = %d AND country_code IS NOT NULL 
                GROUP BY country_code 
                ORDER BY count DESC 
                LIMIT 5",
                $url_id
            )
        );
        
        // Get top browsers
        $top_browsers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT browser, COUNT(*) as count 
                FROM {$this->table_analytics} 
                WHERE url_id = %d AND browser IS NOT NULL 
                GROUP BY browser 
                ORDER BY count DESC 
                LIMIT 5",
                $url_id
            )
        );
        
        // Get top devices
        $top_devices = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT device_type, COUNT(*) as count 
                FROM {$this->table_analytics} 
                WHERE url_id = %d AND device_type IS NOT NULL 
                GROUP BY device_type 
                ORDER BY count DESC 
                LIMIT 3",
                $url_id
            )
        );
        
        // Get top referrers
        $top_referrers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT referrer_url, COUNT(*) as count 
                FROM {$this->table_analytics} 
                WHERE url_id = %d AND referrer_url IS NOT NULL 
                GROUP BY referrer_url 
                ORDER BY count DESC 
                LIMIT 5",
                $url_id
            )
        );
        
        // Get visits by day for the last 30 days
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        $visits_by_day = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(visited_at) as date, COUNT(*) as count 
                FROM {$this->table_analytics} 
                WHERE url_id = %d AND DATE(visited_at) >= %s 
                GROUP BY DATE(visited_at) 
                ORDER BY date ASC",
                $url_id,
                $thirty_days_ago
            )
        );
        
        return array(
            'total_visits' => $total_visits,
            'top_countries' => $top_countries,
            'top_browsers' => $top_browsers,
            'top_devices' => $top_devices,
            'top_referrers' => $top_referrers,
            'visits_by_day' => $visits_by_day,
        );
    }
} 