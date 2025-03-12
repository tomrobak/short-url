<?php
/**
 * Admin Groups Page
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if we're editing a group
$editing = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$group_id = $editing ? intval($_GET['id']) : 0;
$group = null;

// Get group data if editing
if ($editing) {
    $db = new Short_URL_DB();
    $group = $db->get_group($group_id);
    
    if (!$group) {
        wp_die(__('Group not found.', 'short-url'));
    }
}

// Process form submission
if (isset($_POST['short_url_group_nonce']) && wp_verify_nonce($_POST['short_url_group_nonce'], 'short_url_save_group')) {
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    
    // Validate
    if (empty($name)) {
        $error = __('Group name is required.', 'short-url');
    } else {
        $db = new Short_URL_DB();
        
        // Create or update group
        if ($editing) {
            $result = $db->update_group($group_id, array(
                'name' => $name,
                'description' => $description,
            ));
            
            if ($result) {
                wp_redirect(admin_url('admin.php?page=short-url-groups&message=updated'));
                exit;
            } else {
                $error = __('Failed to update group.', 'short-url');
            }
        } else {
            $result = $db->create_group(array(
                'name' => $name,
                'description' => $description,
                'created_by' => get_current_user_id(),
            ));
            
            if ($result) {
                wp_redirect(admin_url('admin.php?page=short-url-groups&message=created'));
                exit;
            } else {
                $error = __('Failed to create group.', 'short-url');
            }
        }
    }
}
?>

<div class="wrap short-url-container">
    <?php if ($editing || isset($_GET['action']) && $_GET['action'] === 'new') : ?>
        <h1>
            <?php $editing ? esc_html_e('Edit Group', 'short-url') : esc_html_e('Add New Group', 'short-url'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-groups')); ?>" class="page-title-action">
                <?php esc_html_e('Cancel', 'short-url'); ?>
            </a>
        </h1>
        
        <?php if (isset($error)) : ?>
            <div class="notice notice-error">
                <p><?php echo esc_html($error); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <?php wp_nonce_field('short_url_save_group', 'short_url_group_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="name"><?php esc_html_e('Name', 'short-url'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="name" id="name" class="regular-text" value="<?php echo $editing ? esc_attr($group->name) : ''; ?>" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="description"><?php esc_html_e('Description', 'short-url'); ?></label>
                    </th>
                    <td>
                        <textarea name="description" id="description" class="large-text" rows="3"><?php echo $editing ? esc_textarea($group->description) : ''; ?></textarea>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php $editing ? esc_attr_e('Update Group', 'short-url') : esc_attr_e('Add Group', 'short-url'); ?>">
            </p>
        </form>
    <?php else : ?>
        <div class="short-url-header">
            <h1 class="wp-heading-inline"><?php esc_html_e('Groups', 'short-url'); ?></h1>
            <?php if (current_user_can('manage_short_url_groups')) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-groups&action=new')); ?>" class="page-title-action">
                    <?php esc_html_e('Add New', 'short-url'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <?php
        // Show messages
        if (isset($_GET['message'])) {
            switch ($_GET['message']) {
                case 'created':
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Group created successfully.', 'short-url') . '</p></div>';
                    break;
                    
                case 'updated':
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Group updated successfully.', 'short-url') . '</p></div>';
                    break;
                    
                case 'deleted':
                    $count = isset($_GET['count']) ? intval($_GET['count']) : 1;
                    $message = sprintf(
                        _n(
                            '%d group deleted successfully.',
                            '%d groups deleted successfully.',
                            $count,
                            'short-url'
                        ),
                        $count
                    );
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
                    break;
                    
                case 'delete_failed':
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to delete group(s).', 'short-url') . '</p></div>';
                    break;
            }
        }
        ?>
        
        <form method="get">
            <input type="hidden" name="page" value="short-url-groups" />
            
            <?php
            // Show search box
            $list_table->search_box(__('Search Groups', 'short-url'), 'short-url-search');
            
            // Display the table
            $list_table->display();
            ?>
        </form>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Confirm delete action
        $('.short-url-delete-group').on('click', function(e) {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this group? All URLs will still exist but will be unassigned from this group.', 'short-url')); ?>')) {
                e.preventDefault();
                return false;
            }
        });
    });
</script> 