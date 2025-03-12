<?php
/**
 * Short URL Group List Table
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
 * Group List Table Class
 */
class Short_URL_Group_List_Table extends WP_List_Table {
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
            'singular' => 'group',
            'plural'   => 'groups',
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
            'cb'          => '<input type="checkbox" />',
            'name'        => __('Name', 'short-url'),
            'description' => __('Description', 'short-url'),
            'links_count' => __('URLs', 'short-url'),
            'created_by'  => __('Created By', 'short-url'),
            'created_at'  => __('Created', 'short-url'),
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
            'name'        => array('name', false),
            'links_count' => array('links_count', true),
            'created_at'  => array('created_at', true),
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
            'delete' => __('Delete', 'short-url'),
        );
        
        return $actions;
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
            case 'description':
                return empty($item->description) ? '—' : esc_html($item->description);
            
            case 'links_count':
                return number_format_i18n($item->links_count);
            
            case 'created_by':
                $user = get_userdata($item->created_by);
                return $user ? esc_html($user->display_name) : __('Unknown', 'short-url');
            
            case 'created_at':
                return Short_URL_Utils::format_date($item->created_at, true, true);
            
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
            '<input type="checkbox" name="groups[]" value="%s" />',
            $item->id
        );
    }
    
    /**
     * Column name
     *
     * @param object $item Item
     * @return string Column content
     */
    public function column_name($item) {
        // Build row actions
        $actions = array();
        
        // Edit action
        if (current_user_can('manage_short_url_groups')) {
            $actions['edit'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=short-url-groups&action=edit&id=' . $item->id)),
                __('Edit', 'short-url')
            );
        }
        
        // View links action - check if there are any links in this group
        if ($item->links_count > 0) {
            $actions['view_links'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('admin.php?page=short-url-urls&group=' . $item->id)),
                __('View URLs', 'short-url')
            );
        }
        
        // Delete action
        if (current_user_can('manage_short_url_groups')) {
            $actions['delete'] = sprintf(
                '<a href="%s" class="short-url-delete-group" data-id="%d">%s</a>',
                esc_url(wp_nonce_url(admin_url('admin.php?page=short-url-groups&action=delete&id=' . $item->id), 'short_url_delete_group_' . $item->id)),
                $item->id,
                __('Delete', 'short-url')
            );
        }
        
        // Return name with actions
        return sprintf(
            '<strong><a href="%s">%s</a></strong>%s',
            esc_url(admin_url('admin.php?page=short-url-groups&action=edit&id=' . $item->id)),
            esc_html($item->name),
            $this->row_actions($actions)
        );
    }
    
    /**
     * Process bulk action
     */
    public function process_bulk_action() {
        // Check if a bulk action is requested
        $action = $this->current_action();
        
        if (!$action) {
            return;
        }
        
        // Check if groups are selected
        if (!isset($_REQUEST['groups']) || !is_array($_REQUEST['groups'])) {
            return;
        }
        
        // Security check
        check_admin_referer('bulk-' . $this->_args['plural']);
        
        // Get selected groups
        $groups = array_map('intval', $_REQUEST['groups']);
        
        // Process action
        switch ($action) {
            case 'delete':
                $deleted = 0;
                foreach ($groups as $group_id) {
                    $result = $this->db->delete_group($group_id);
                    if ($result) {
                        $deleted++;
                    }
                }
                
                // Redirect with message
                if ($deleted > 0) {
                    wp_redirect(admin_url('admin.php?page=short-url-groups&message=deleted&count=' . $deleted));
                } else {
                    wp_redirect(admin_url('admin.php?page=short-url-groups&message=delete_failed'));
                }
                break;
        }
        
        // Redirect to avoid resubmission
        wp_redirect(admin_url('admin.php?page=short-url-groups'));
        exit;
    }
    
    /**
     * Process single actions
     */
    public function process_single_action() {
        // Check if a single action is requested
        if (!isset($_GET['action']) || !isset($_GET['id'])) {
            return;
        }
        
        $action = sanitize_key($_GET['action']);
        $group_id = intval($_GET['id']);
        
        // Process action
        switch ($action) {
            case 'delete':
                // Security check
                check_admin_referer('short_url_delete_group_' . $group_id);
                
                // Delete group
                $this->db->delete_group($group_id);
                
                // Redirect to avoid resubmission
                wp_redirect(admin_url('admin.php?page=short-url-groups&message=deleted'));
                exit;
        }
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
        
        // Search
        if (isset($_REQUEST['s']) && !empty($_REQUEST['s'])) {
            $args['search'] = sanitize_text_field($_REQUEST['s']);
        }
        
        // Order
        if (isset($_REQUEST['orderby'])) {
            $args['orderby'] = sanitize_key($_REQUEST['orderby']);
            $args['order'] = isset($_REQUEST['order']) ? sanitize_key($_REQUEST['order']) : 'asc';
        }
        
        // Get groups
        $this->items = $this->db->get_groups($args);
        $total_items = $this->db->get_groups(array_merge($args, array('count' => true)));
        
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
            _e('No groups found matching your search.', 'short-url');
        } else {
            _e('No groups found.', 'short-url');
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
        
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?>:</label>
            <input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php echo esc_attr(isset($_REQUEST['s']) ? $_REQUEST['s'] : ''); ?>" />
            <?php submit_button($text, '', '', false, array('id' => 'search-submit')); ?>
        </p>
        <?php
    }
} 