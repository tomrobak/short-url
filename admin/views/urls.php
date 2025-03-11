<?php
/**
 * Admin URLs Page
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap short-url-container">
    <div class="short-url-header">
        <h1 class="wp-heading-inline"><?php esc_html_e('Short URLs', 'short-url'); ?></h1>
        <?php if (current_user_can('create_short_urls')) : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-add')); ?>" class="page-title-action">
                <?php esc_html_e('Add New', 'short-url'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php
    // Show messages
    if (isset($_GET['message'])) {
        switch ($_GET['message']) {
            case 'deleted':
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Short URL deleted successfully.', 'short-url') . '</p></div>';
                break;
            case 'activated':
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Short URL(s) activated successfully.', 'short-url') . '</p></div>';
                break;
            case 'deactivated':
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Short URL(s) deactivated successfully.', 'short-url') . '</p></div>';
                break;
        }
    }
    ?>
    
    <form method="get">
        <input type="hidden" name="page" value="short-url-urls" />
        
        <?php
        // Show search box
        $list_table->search_box(__('Search URLs', 'short-url'), 'short-url-search');
        
        // Display the table
        $list_table->display();
        ?>
    </form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Initialize ClipboardJS
        if (typeof ClipboardJS !== 'undefined') {
            var clipboard = new ClipboardJS('.short-url-copy-button');
            
            clipboard.on('success', function(e) {
                // Create a tooltip
                var $button = $(e.trigger);
                var tooltip = document.createElement('span');
                tooltip.textContent = '<?php echo esc_js(__('Copied!', 'short-url')); ?>';
                tooltip.className = 'short-url-copy-tooltip short-url-tooltip-success';
                $button.append(tooltip);
                
                // Remove tooltip after a delay
                setTimeout(function() {
                    $(tooltip).remove();
                }, 2000);
            });
        }
        
        // Confirm delete action
        $('.short-url-delete').on('click', function(e) {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this URL? This cannot be undone.', 'short-url')); ?>')) {
                e.preventDefault();
                return false;
            }
        });
    });
</script> 