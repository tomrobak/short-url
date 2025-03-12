<?php
/**
 * Short URL List Table
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * URL List Table Class
 */
class Short_URL_List_Table extends WP_List_Table {
    /**
     * Database instance
     *
     * @var Short_URL_DB
     */
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'url',
            'plural'   => 'urls',
            'ajax'     => false,
        ));
        
        $this->db = new Short_URL_DB();
    }
    
    /**
     * Get columns
     *
     * @return array Columns
     */
    public function get_columns() {
        $columns = array(
            'cb'             => '<input type="checkbox" />',
            'slug'           => __('Short URL', 'short-url'),
            'destination_url' => __('Destination URL', 'short-url'),
            'title'          => __('Title', 'short-url'),
            'visits'         => __('Visits', 'short-url'),
            'created_by'     => __('Created By', 'short-url'),
            'created_at'     => __('Created', 'short-url'),
            'expires_at'     => __('Expires', 'short-url'),
            'status'         => __('Status', 'short-url'),
        );
        
        return $columns;
    }
    
    /**
     * Get sortable columns
     *
     * @return array Sortable columns
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'slug'           => array('slug', false),
            'destination_url' => array('destination_url', false),
            'title'          => array('title', false),
            'visits'         => array('visits', true),
            'created_at'     => array('created_at', true),
            'expires_at'     => array('expires_at', false),
            'status'         => array('is_active', true),
        );
        
        return $sortable_columns;
    }
    
    /**
     * Get bulk actions
     *
     * @return array Bulk actions
     */
    public function get_bulk_actions() {
        $actions = array(
            'activate'   => __('Activate', 'short-url'),
            'deactivate' => __('Deactivate', 'short-url'),
            'delete'     => __('Delete', 'short-url'),
        );
        
        return $actions;
    }
    
    /**
     * Get views
     *
     * @return array Views
     */
    public function get_views() {
        $current = isset($_REQUEST['status']) ? $_REQUEST['status'] : 'all';
        
        // Get URL counts
        $total_count = $this->db->get_urls(array('count' => true));
        $active_count = $this->db->get_urls(array('count' => true, 'is_active' => 1));
        $inactive_count = $this->db->get_urls(array('count' => true, 'is_active' => 0));
        
        // Base URL
        $base_url = admin_url('admin.php?page=short-url-urls');
        
        $views = array(
            'all' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                $base_url,
                $current === 'all' ? 'current' : '',
                __('All', 'short-url'),
                number_format_i18n($total_count)
            ),
            'active' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                esc_url(add_query_arg('status', 'active', $base_url)),
                $current === 'active' ? 'current' : '',
                __('Active', 'short-url'),
                number_format_i18n($active_count)
            ),
            'inactive' => sprintf(
                '<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
                esc_url(add_query_arg('status', 'inactive', $base_url)),
                $current === 'inactive' ? 'current' : '',
                __('Inactive', 'short-url'),
                number_format_i18n($inactive_count)
            ),
        );
        
        return $views;
    }
    
    /**
     * Column default
     *
     * @param object $item        Item
     * @param string $column_name Column name
     * @return string Column content
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'destination_url':
                $url = esc_url($item->destination_url);
                $display_url = Short_URL_Utils::truncate($url, 50);
                return sprintf(
                    '<a href="%s" target="_blank" title="%s">%s</a>',
                    $url,
                    esc_attr($url),
                    esc_html($display_url)
                );
            
            case 'title':
                return esc_html($item->title);
            
            case 'visits':
                return number_format_i18n($item->visits);
            
            case 'created_by':
                $user = get_userdata($item->created_by);
                return $user ? esc_html($user->display_name) : __('Unknown', 'short-url');
            
            case 'created_at':
                return Short_URL_Utils::format_date($item->created_at, true, true);
            
            case 'expires_at':
                if (empty($item->expires_at)) {
                    return __('Never', 'short-url');
                }
                
                $expires = strtotime($item->expires_at);
                $now = current_time('timestamp');
                
                if ($expires < $now) {
                    return sprintf(
                        '<span class="short-url-expired">%s</span>',
                        __('Expired', 'short-url')
                    );
                }
                
                return Short_URL_Utils::format_date($item->expires_at, true, false);
            
            case 'status':
                $status = $item->is_active ? 'active' : 'inactive';
                $label = $item->is_active ? __('Active', 'short-url') : __('Inactive', 'short-url');
                
                return sprintf(
                    '<span class="short-url-status short-url-status-%s">%s</span>',
                    $status,
                    $label
                );
            
            default:
                return print_r($item, true);
        }
    }
    
    /**
     * Column checkbox
     *
     * @param object $item Item
     * @return string Column content
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="urls[]" value="%s" />',
            $item->id
        );
    }
    
    /**
     * Column slug
     *
     * @param object $item Item
     * @return string Column content
     */
    public function column_slug($item) {
        // Build short URL
        $short_url = Short_URL_Generator::get_short_url($item->slug);
        
        // Build row actions
        $actions = array();
        
        // Edit action
        if (current_user_can('edit_short_urls')) {
            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=short-url-add&id=' . $item->id)),
                __('Edit', 'short-url')
            );
        }
        
        // Stats action
        if (current_user_can('view_short_url_analytics')) {
            $actions['stats'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=short-url-analytics&id=' . $item->id)),
                __('Stats', 'short-url')
            );
        }
        
        // Clone action
        if (current_user_can('create_short_urls')) {
            $actions['clone'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin.php?page=short-url-add&clone=' . $item->id), 'short_url_clone')),
                __('Clone', 'short-url')
            );
        }
        
        // Activate/Deactivate action
        if (current_user_can('edit_short_urls')) {
            if ($item->is_active) {
                $actions['deactivate'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url(wp_nonce_url(admin_url('admin.php?page=short-url-urls&action=deactivate&id=' . $item->id), 'short_url_deactivate_' . $item->id)),
                    __('Deactivate', 'short-url')
                );
            } else {
                $actions['activate'] = sprintf(
                    '<a href="%s">%s</a>',
                    esc_url(wp_nonce_url(admin_url('admin.php?page=short-url-urls&action=activate&id=' . $item->id), 'short_url_activate_' . $item->id)),
                    __('Activate', 'short-url')
                );
            }
        }
        
        // Delete action
        if (current_user_can('delete_short_urls')) {
            $actions['delete'] = sprintf(
                '<a href="%s" class="short-url-delete" data-id="%d">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin.php?page=short-url-urls&action=delete&id=' . $item->id), 'short_url_delete_' . $item->id)),
                $item->id,
                __('Delete', 'short-url')
            );
        }
        
        // Return slug with URL and actions
        return sprintf(
            '<div class="short-url-slug-container">
                <a href="%s" target="_blank" class="short-url-slug">%s</a>
                <button type="button" class="short-url-copy-button" data-clipboard-text="%s" title="%s">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            </div>
            <div class="short-url-qr-button">
                <button type="button" class="button button-small short-url-generate-qr" data-url="%s" data-url-id="%d">
                    <span class="dashicons dashicons-visibility"></span> %s
                </button>
            </div>
            %s',
            esc_url($short_url),
            esc_html($short_url),
            esc_attr($short_url),
            esc_attr__('Copy to clipboard', 'short-url'),
            esc_attr($short_url),
            $item->id,
            esc_html__('Show QR', 'short-url'),
            $this->row_actions($actions)
        );
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        // Check if a bulk action is requested
        $action = $this->current_action();
        
        if (!$action) {
            return;
        }
        
        // Check if URLs are selected
        if (!isset($_REQUEST['urls']) || !is_array($_REQUEST['urls'])) {
            return;
        }
        
        // Security check
        check_admin_referer('bulk-' . $this->_args['plural']);
        
        // Get selected URLs
        $urls = array_map('intval', $_REQUEST['urls']);
        
        // Process action
        switch ($action) {
            case 'activate':
                foreach ($urls as $url_id) {
                    $this->db->update_url($url_id, array('is_active' => 1));
                }
                $redirect_message = 'activated';
                break;
            
            case 'deactivate':
                foreach ($urls as $url_id) {
                    $this->db->update_url($url_id, array('is_active' => 0));
                }
                $redirect_message = 'deactivated';
                break;
            
            case 'delete':
                foreach ($urls as $url_id) {
                    Short_URL_Generator::delete_url($url_id);
                }
                $redirect_message = 'deleted';
                break;
                
            default:
                $redirect_message = '';
        }
        
        // Redirect to avoid resubmission
        if (!empty($redirect_message)) {
            wp_redirect(admin_url('admin.php?page=short-url-urls&message=' . $redirect_message));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=short-url-urls'));
            exit;
        }
    }
    
    /**
     * Process single actions
     */
    public function process_single_action() {
        // Start output buffering to prevent headers already sent errors
        ob_start();
        
        // Get action and URL ID
        $action = $this->current_action();
        $url_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        
        if (!$action || !$url_id) {
            ob_end_clean();
            return;
        }
        
        switch ($action) {
            case 'delete':
                // Security check
                check_admin_referer('short_url_delete_' . $url_id);
                
                // Delete URL
                $this->db->delete_url($url_id);
                
                // Redirect to avoid resubmission
                wp_redirect(admin_url('admin.php?page=short-url-urls&message=deleted'));
                exit;
                
            case 'activate':
                // Security check
                check_admin_referer('short_url_activate_' . $url_id);
                
                // Activate URL
                $this->db->update_url($url_id, array('is_active' => 1));
                
                // Redirect to avoid resubmission
                wp_redirect(admin_url('admin.php?page=short-url-urls&message=activated'));
                exit;
                
            case 'deactivate':
                // Security check
                check_admin_referer('short_url_deactivate_' . $url_id);
                
                // Deactivate URL
                $this->db->update_url($url_id, array('is_active' => 0));
                
                // Redirect to avoid resubmission
                wp_redirect(admin_url('admin.php?page=short-url-urls&message=deactivated'));
                exit;
        }
        
        // End output buffering if we didn't exit
        ob_end_clean();
    }
    
    /**
     * Prepare items for display
     */
    public function prepare_items() {
        // Process actions
        $this->process_bulk_action();
        $this->process_single_action();
        
        // Columns
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        // Get items
        $args = array(
            'per_page' => $per_page,
            'page' => $current_page,
        );
        
        // Filter by status
        if (isset($_REQUEST['status'])) {
            if ($_REQUEST['status'] === 'active') {
                $args['is_active'] = 1;
            } elseif ($_REQUEST['status'] === 'inactive') {
                $args['is_active'] = 0;
            }
        }
        
        // Search
        if (isset($_REQUEST['s']) && !empty($_REQUEST['s'])) {
            $args['search'] = sanitize_text_field($_REQUEST['s']);
        }
        
        // Filter by group - support both group_id and group parameters
        if (isset($_REQUEST['group_id']) && intval($_REQUEST['group_id']) > 0) {
            $args['group_id'] = intval($_REQUEST['group_id']);
        } elseif (isset($_REQUEST['group']) && intval($_REQUEST['group']) > 0) {
            $args['group_id'] = intval($_REQUEST['group']);
        }
        
        // Order
        if (isset($_REQUEST['orderby'])) {
            $args['orderby'] = sanitize_key($_REQUEST['orderby']);
            $args['order'] = isset($_REQUEST['order']) ? sanitize_key($_REQUEST['order']) : 'asc';
        }
        
        // Get URLs
        $url_data = $this->db->get_urls($args);
        $this->items = isset($url_data['items']) ? $url_data['items'] : array();
        $total_items = $this->db->get_urls(array_merge($args, array('count' => true)));
        
        // Set pagination args
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => $per_page > 0 ? ceil($total_items / $per_page) : 0,
        ));
    }
    
    /**
     * Display no items text
     */
    public function no_items() {
        if (isset($_REQUEST['s']) && !empty($_REQUEST['s'])) {
            _e('No URLs found matching your search.', 'short-url');
        } else {
            _e('No URLs found.', 'short-url');
        }
    }
    
    /**
     * Display the search box
     *
     * @param string $text     Button text
     * @param string $input_id Input ID
     */
    public function search_box($text, $input_id) {
        if (empty($_REQUEST['s']) && !$this->has_items()) {
            return;
        }
        
        $input_id = $input_id . '-search-input';
        
        if (!empty($_REQUEST['orderby'])) {
            echo '<input type="hidden" name="orderby" value="' . esc_attr($_REQUEST['orderby']) . '" />';
        }
        
        if (!empty($_REQUEST['order'])) {
            echo '<input type="hidden" name="order" value="' . esc_attr($_REQUEST['order']) . '" />';
        }
        
        if (!empty($_REQUEST['status'])) {
            echo '<input type="hidden" name="status" value="' . esc_attr($_REQUEST['status']) . '" />';
        }
        
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php echo esc_attr(isset($_REQUEST['s']) ? $_REQUEST['s'] : ''); ?>" />
            <?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
        </p>
        <?php
    }
    
    /**
     * Display extra filter controls
     */
    public function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        
        // Get groups for filter
        $groups = $this->db->get_groups();
        
        if (empty($groups)) {
            return;
        }
        
        $current_group = isset($_REQUEST['group_id']) ? intval($_REQUEST['group_id']) : 0;
        
        ?>
        <div class="alignleft actions">
            <label for="filter-by-group" class="screen-reader-text"><?php _e('Filter by group', 'short-url'); ?></label>
            <select name="group_id" id="filter-by-group">
                <option value="0"><?php _e('All Groups', 'short-url'); ?></option>
                <?php foreach ($groups as $group) : ?>
                    <option value="<?php echo esc_attr($group->id); ?>" <?php selected($current_group, $group->id); ?>>
                        <?php echo esc_html($group->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php submit_button(__('Filter', 'short-url'), 'button', 'filter_action', false); ?>
        </div>
        <?php
    }
} 