<?php
/**
 * Short URL API
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Class
 */
class Short_URL_API {
    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'short-url/v1';
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Register routes
        register_rest_route($this->namespace, '/urls', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_urls'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_url'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
                'args' => $this->get_create_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/urls/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_url'),
                'permission_callback' => array($this, 'get_item_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_url'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args' => $this->get_update_args(),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_url'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
            ),
        ));
        
        register_rest_route($this->namespace, '/urls/stats/(?P<id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_url_stats'),
            'permission_callback' => array($this, 'get_stats_permissions_check'),
        ));
        
        register_rest_route($this->namespace, '/groups', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_groups'),
                'permission_callback' => array($this, 'get_items_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_group'),
                'permission_callback' => array($this, 'create_item_permissions_check'),
                'args' => $this->get_group_create_args(),
            ),
        ));
        
        register_rest_route($this->namespace, '/groups/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_group'),
                'permission_callback' => array($this, 'get_item_permissions_check'),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_group'),
                'permission_callback' => array($this, 'update_item_permissions_check'),
                'args' => $this->get_group_update_args(),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_group'),
                'permission_callback' => array($this, 'delete_item_permissions_check'),
            ),
        ));
    }
    
    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full details about the request
     * @return bool|WP_Error
     */
    public function get_items_permissions_check($request) {
        return current_user_can('manage_short_urls');
    }
    
    /**
     * Check if a given request has access to get a specific item
     *
     * @param WP_REST_Request $request Full details about the request
     * @return bool|WP_Error
     */
    public function get_item_permissions_check($request) {
        return current_user_can('manage_short_urls');
    }
    
    /**
     * Check if a given request has access to create items
     *
     * @param WP_REST_Request $request Full details about the request
     * @return bool|WP_Error
     */
    public function create_item_permissions_check($request) {
        return current_user_can('create_short_urls');
    }
    
    /**
     * Check if a given request has access to update a specific item
     *
     * @param WP_REST_Request $request Full details about the request
     * @return bool|WP_Error
     */
    public function update_item_permissions_check($request) {
        return current_user_can('edit_short_urls');
    }
    
    /**
     * Check if a given request has access to delete a specific item
     *
     * @param WP_REST_Request $request Full details about the request
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check($request) {
        return current_user_can('delete_short_urls');
    }
    
    /**
     * Check if a given request has access to get stats
     *
     * @param WP_REST_Request $request Full details about the request
     * @return bool|WP_Error
     */
    public function get_stats_permissions_check($request) {
        return current_user_can('view_short_url_analytics');
    }
    
    /**
     * Get a collection of URLs
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function get_urls($request) {
        $db = new Short_URL_DB();
        
        $args = array(
            'per_page' => $request->get_param('per_page') ? absint($request->get_param('per_page')) : 20,
            'page' => $request->get_param('page') ? absint($request->get_param('page')) : 1,
            'orderby' => $request->get_param('orderby') ? sanitize_text_field($request->get_param('orderby')) : 'created_at',
            'order' => $request->get_param('order') ? sanitize_text_field($request->get_param('order')) : 'DESC',
            'search' => $request->get_param('search') ? sanitize_text_field($request->get_param('search')) : '',
            'group_id' => $request->get_param('group_id') ? absint($request->get_param('group_id')) : null,
            'include_inactive' => $request->get_param('include_inactive') ? (bool) $request->get_param('include_inactive') : false,
        );
        
        $result = $db->get_urls($args);
        
        // Format the items
        $urls = array();
        
        foreach ($result['items'] as $url) {
            $urls[] = $this->prepare_url_for_response($url);
        }
        
        $response = new WP_REST_Response($urls);
        
        // Add pagination headers
        $response->header('X-WP-Total', $result['total']);
        $response->header('X-WP-TotalPages', $result['total_pages']);
        
        return $response;
    }
    
    /**
     * Get a single URL
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function get_url($request) {
        $id = $request->get_param('id');
        $db = new Short_URL_DB();
        
        $url = $db->get_url($id);
        
        if (!$url) {
            return new WP_Error('short_url_not_found', __('URL not found', 'short-url'), array('status' => 404));
        }
        
        return new WP_REST_Response($this->prepare_url_for_response($url));
    }
    
    /**
     * Create a URL
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function create_url($request) {
        $params = $request->get_params();
        
        $args = array(
            'destination_url' => $params['destination_url'],
            'slug' => isset($params['slug']) ? $params['slug'] : '',
            'title' => isset($params['title']) ? $params['title'] : '',
            'description' => isset($params['description']) ? $params['description'] : '',
            'redirect_type' => isset($params['redirect_type']) ? $params['redirect_type'] : 301,
            'nofollow' => isset($params['nofollow']) ? (bool) $params['nofollow'] : false,
            'sponsored' => isset($params['sponsored']) ? (bool) $params['sponsored'] : false,
            'forward_parameters' => isset($params['forward_parameters']) ? (bool) $params['forward_parameters'] : false,
            'track_visits' => isset($params['track_visits']) ? (bool) $params['track_visits'] : true,
            'group_id' => isset($params['group_id']) ? $params['group_id'] : null,
            'expires_at' => isset($params['expires_at']) ? $params['expires_at'] : null,
            'password' => isset($params['password']) ? $params['password'] : null,
        );
        
        $result = Short_URL_Generator::create_url($params['destination_url'], $args);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $db = new Short_URL_DB();
        $url = $db->get_url($result['url_id']);
        
        $response = new WP_REST_Response($this->prepare_url_for_response($url));
        $response->set_status(201);
        
        return $response;
    }
    
    /**
     * Update a URL
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function update_url($request) {
        $id = $request->get_param('id');
        $params = $request->get_params();
        
        $db = new Short_URL_DB();
        $url = $db->get_url($id);
        
        if (!$url) {
            return new WP_Error('short_url_not_found', __('URL not found', 'short-url'), array('status' => 404));
        }
        
        $args = array();
        
        // Only update fields that were passed
        if (isset($params['destination_url'])) {
            $args['destination_url'] = $params['destination_url'];
        }
        
        if (isset($params['slug'])) {
            $args['slug'] = $params['slug'];
        }
        
        if (isset($params['title'])) {
            $args['title'] = $params['title'];
        }
        
        if (isset($params['description'])) {
            $args['description'] = $params['description'];
        }
        
        if (isset($params['redirect_type'])) {
            $args['redirect_type'] = $params['redirect_type'];
        }
        
        if (isset($params['nofollow'])) {
            $args['nofollow'] = (bool) $params['nofollow'];
        }
        
        if (isset($params['sponsored'])) {
            $args['sponsored'] = (bool) $params['sponsored'];
        }
        
        if (isset($params['forward_parameters'])) {
            $args['forward_parameters'] = (bool) $params['forward_parameters'];
        }
        
        if (isset($params['track_visits'])) {
            $args['track_visits'] = (bool) $params['track_visits'];
        }
        
        if (isset($params['group_id'])) {
            $args['group_id'] = $params['group_id'];
        }
        
        if (isset($params['expires_at'])) {
            $args['expires_at'] = $params['expires_at'];
        }
        
        if (isset($params['password'])) {
            $args['password'] = $params['password'];
        }
        
        if (isset($params['is_active'])) {
            $args['is_active'] = (bool) $params['is_active'];
        }
        
        $result = Short_URL_Generator::update_url($id, $args);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $url = $db->get_url($id);
        
        return new WP_REST_Response($this->prepare_url_for_response($url));
    }
    
    /**
     * Delete a URL
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_url($request) {
        $id = $request->get_param('id');
        $db = new Short_URL_DB();
        
        $url = $db->get_url($id);
        
        if (!$url) {
            return new WP_Error('short_url_not_found', __('URL not found', 'short-url'), array('status' => 404));
        }
        
        $result = Short_URL_Generator::delete_url($id);
        
        if (!$result) {
            return new WP_Error('short_url_delete_failed', __('Failed to delete URL', 'short-url'), array('status' => 500));
        }
        
        return new WP_REST_Response(null, 204);
    }
    
    /**
     * Get URL stats
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function get_url_stats($request) {
        $id = $request->get_param('id');
        $db = new Short_URL_DB();
        
        $url = $db->get_url($id);
        
        if (!$url) {
            return new WP_Error('short_url_not_found', __('URL not found', 'short-url'), array('status' => 404));
        }
        
        $summary = $db->get_url_analytics_summary($id);
        
        return new WP_REST_Response($summary);
    }
    
    /**
     * Get a collection of groups
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function get_groups($request) {
        $db = new Short_URL_DB();
        
        $args = array(
            'per_page' => $request->get_param('per_page') ? absint($request->get_param('per_page')) : 20,
            'page' => $request->get_param('page') ? absint($request->get_param('page')) : 1,
            'orderby' => $request->get_param('orderby') ? sanitize_text_field($request->get_param('orderby')) : 'name',
            'order' => $request->get_param('order') ? sanitize_text_field($request->get_param('order')) : 'ASC',
            'search' => $request->get_param('search') ? sanitize_text_field($request->get_param('search')) : '',
        );
        
        $result = $db->get_groups($args);
        
        $response = new WP_REST_Response($result['items']);
        
        // Add pagination headers
        $response->header('X-WP-Total', $result['total']);
        $response->header('X-WP-TotalPages', $result['total_pages']);
        
        return $response;
    }
    
    /**
     * Get a single group
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function get_group($request) {
        $id = $request->get_param('id');
        $db = new Short_URL_DB();
        
        $group = $db->get_group($id);
        
        if (!$group) {
            return new WP_Error('short_url_group_not_found', __('Group not found', 'short-url'), array('status' => 404));
        }
        
        return new WP_REST_Response($group);
    }
    
    /**
     * Create a group
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function create_group($request) {
        $params = $request->get_params();
        
        if (empty($params['name'])) {
            return new WP_Error('short_url_group_name_required', __('Group name is required', 'short-url'), array('status' => 400));
        }
        
        $db = new Short_URL_DB();
        
        $args = array(
            'name' => $params['name'],
            'description' => isset($params['description']) ? $params['description'] : '',
        );
        
        $group_id = $db->create_group($args);
        
        if (!$group_id) {
            return new WP_Error('short_url_group_create_failed', __('Failed to create group', 'short-url'), array('status' => 500));
        }
        
        $group = $db->get_group($group_id);
        
        $response = new WP_REST_Response($group);
        $response->set_status(201);
        
        return $response;
    }
    
    /**
     * Update a group
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function update_group($request) {
        $id = $request->get_param('id');
        $params = $request->get_params();
        
        $db = new Short_URL_DB();
        $group = $db->get_group($id);
        
        if (!$group) {
            return new WP_Error('short_url_group_not_found', __('Group not found', 'short-url'), array('status' => 404));
        }
        
        $args = array();
        
        if (isset($params['name'])) {
            $args['name'] = $params['name'];
        }
        
        if (isset($params['description'])) {
            $args['description'] = $params['description'];
        }
        
        $result = $db->update_group($id, $args);
        
        if (!$result) {
            return new WP_Error('short_url_group_update_failed', __('Failed to update group', 'short-url'), array('status' => 500));
        }
        
        $group = $db->get_group($id);
        
        return new WP_REST_Response($group);
    }
    
    /**
     * Delete a group
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_group($request) {
        $id = $request->get_param('id');
        $db = new Short_URL_DB();
        
        $group = $db->get_group($id);
        
        if (!$group) {
            return new WP_Error('short_url_group_not_found', __('Group not found', 'short-url'), array('status' => 404));
        }
        
        $result = $db->delete_group($id);
        
        if (!$result) {
            return new WP_Error('short_url_group_delete_failed', __('Failed to delete group', 'short-url'), array('status' => 500));
        }
        
        return new WP_REST_Response(null, 204);
    }
    
    /**
     * Prepare URL for response
     *
     * @param object $url URL object
     * @return array Prepared URL
     */
    private function prepare_url_for_response($url) {
        return array(
            'id' => (int) $url->id,
            'slug' => $url->slug,
            'short_url' => Short_URL_Generator::get_short_url($url->slug),
            'destination_url' => $url->destination_url,
            'title' => $url->title,
            'description' => $url->description,
            'created_by' => (int) $url->created_by,
            'created_at' => $url->created_at,
            'updated_at' => $url->updated_at,
            'expires_at' => $url->expires_at,
            'has_password' => !empty($url->password),
            'redirect_type' => (int) $url->redirect_type,
            'nofollow' => (bool) $url->nofollow,
            'sponsored' => (bool) $url->sponsored,
            'forward_parameters' => (bool) $url->forward_parameters,
            'track_visits' => (bool) $url->track_visits,
            'visits' => (int) $url->visits,
            'group_id' => (int) $url->group_id ?: null,
            'is_active' => (bool) $url->is_active,
        );
    }
    
    /**
     * Get endpoint arguments for creating a URL
     *
     * @return array
     */
    private function get_create_args() {
        return array(
            'destination_url' => array(
                'required' => true,
                'type' => 'string',
                'description' => __('The destination URL', 'short-url'),
                'validate_callback' => function($param) {
                    return filter_var($param, FILTER_VALIDATE_URL) !== false;
                },
            ),
            'slug' => array(
                'type' => 'string',
                'description' => __('The URL slug (if empty, one will be generated)', 'short-url'),
                'validate_callback' => function($param) {
                    return Short_URL_Generator::is_valid_slug($param);
                },
            ),
            'title' => array(
                'type' => 'string',
                'description' => __('The URL title', 'short-url'),
            ),
            'description' => array(
                'type' => 'string',
                'description' => __('The URL description', 'short-url'),
            ),
            'redirect_type' => array(
                'type' => 'integer',
                'description' => __('The redirect type (301, 302, 307)', 'short-url'),
                'enum' => array(301, 302, 307),
                'default' => 301,
            ),
            'nofollow' => array(
                'type' => 'boolean',
                'description' => __('Whether to add nofollow attribute', 'short-url'),
                'default' => false,
            ),
            'sponsored' => array(
                'type' => 'boolean',
                'description' => __('Whether to add sponsored attribute', 'short-url'),
                'default' => false,
            ),
            'forward_parameters' => array(
                'type' => 'boolean',
                'description' => __('Whether to forward query parameters', 'short-url'),
                'default' => false,
            ),
            'track_visits' => array(
                'type' => 'boolean',
                'description' => __('Whether to track visits', 'short-url'),
                'default' => true,
            ),
            'group_id' => array(
                'type' => 'integer',
                'description' => __('The group ID', 'short-url'),
            ),
            'expires_at' => array(
                'type' => 'string',
                'description' => __('The expiration date (ISO 8601)', 'short-url'),
                'format' => 'date-time',
            ),
            'password' => array(
                'type' => 'string',
                'description' => __('The password (if any)', 'short-url'),
            ),
        );
    }
    
    /**
     * Get endpoint arguments for updating a URL
     *
     * @return array
     */
    private function get_update_args() {
        return array_merge($this->get_create_args(), array(
            'destination_url' => array(
                'type' => 'string',
                'description' => __('The destination URL', 'short-url'),
                'validate_callback' => function($param) {
                    return filter_var($param, FILTER_VALIDATE_URL) !== false;
                },
            ),
            'is_active' => array(
                'type' => 'boolean',
                'description' => __('Whether the URL is active', 'short-url'),
            ),
        ));
    }
    
    /**
     * Get endpoint arguments for creating a group
     *
     * @return array
     */
    private function get_group_create_args() {
        return array(
            'name' => array(
                'required' => true,
                'type' => 'string',
                'description' => __('The group name', 'short-url'),
            ),
            'description' => array(
                'type' => 'string',
                'description' => __('The group description', 'short-url'),
            ),
        );
    }
    
    /**
     * Get endpoint arguments for updating a group
     *
     * @return array
     */
    private function get_group_update_args() {
        return array(
            'name' => array(
                'type' => 'string',
                'description' => __('The group name', 'short-url'),
            ),
            'description' => array(
                'type' => 'string',
                'description' => __('The group description', 'short-url'),
            ),
        );
    }
} 