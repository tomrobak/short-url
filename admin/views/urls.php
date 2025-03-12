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
        
        // AJAX handling for activate/deactivate actions
        $('.row-actions .activate a, .row-actions .deactivate a').on('click', function(e) {
            e.preventDefault();
            
            var $link = $(this);
            var url = $link.attr('href');
            var actionType = $link.closest('.activate').length ? 'activate' : 'deactivate';
            var $row = $link.closest('tr');
            
            // Show loading state
            $row.css('opacity', '0.5');
            
            // Extract nonce from URL
            var nonce = url.match(/_wpnonce=([^&]*)/)[1];
            var id = url.match(/id=([^&]*)/)[1];
            
            // Make AJAX request
            $.ajax({
                url: url + '&_ajax_nonce=' + nonce,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        var message = actionType === 'activate' ? 
                            '<?php echo esc_js(__('URL activated successfully.', 'short-url')); ?>' : 
                            '<?php echo esc_js(__('URL deactivated successfully.', 'short-url')); ?>';
                        
                        // Add notice
                        $('.short-url-header').after('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
                        
                        // Update row status
                        var statusCell = $row.find('.column-status .short-url-status');
                        if (actionType === 'activate') {
                            statusCell.removeClass('short-url-status-inactive').addClass('short-url-status-active');
                            statusCell.text('<?php echo esc_js(__('Active', 'short-url')); ?>');
                            // Update row actions
                            $row.find('.row-actions .activate').html('<span class="deactivate"><a href="<?php echo esc_url(admin_url('admin.php?page=short-url-urls&action=deactivate&id=')); ?>' + id + '&_wpnonce=' + '<?php echo wp_create_nonce('short_url_deactivate_PLACEHOLDER'); ?>'.replace('PLACEHOLDER', id) + '"><?php echo esc_js(__('Deactivate', 'short-url')); ?></a></span>');
                        } else {
                            statusCell.removeClass('short-url-status-active').addClass('short-url-status-inactive');
                            statusCell.text('<?php echo esc_js(__('Inactive', 'short-url')); ?>');
                            // Update row actions
                            $row.find('.row-actions .deactivate').html('<span class="activate"><a href="<?php echo esc_url(admin_url('admin.php?page=short-url-urls&action=activate&id=')); ?>' + id + '&_wpnonce=' + '<?php echo wp_create_nonce('short_url_activate_PLACEHOLDER'); ?>'.replace('PLACEHOLDER', id) + '"><?php echo esc_js(__('Activate', 'short-url')); ?></a></span>');
                        }
                        
                        // Reset row opacity
                        $row.css('opacity', '1');
                        
                        // Reinitialize click handlers for new links
                        setTimeout(function() {
                            $('.row-actions .activate a, .row-actions .deactivate a').off('click').on('click', function(e) {
                                $(this).trigger('click');
                            });
                        }, 500);
                        
                        // Make notices dismissible
                        if (typeof wp !== 'undefined' && wp.hasOwnProperty('a11y') && wp.a11y.hasOwnProperty('speak')) {
                            $('.notice.is-dismissible').find('.notice-dismiss').on('click', function() {
                                $(this).parent().remove();
                            });
                        }
                    } else {
                        $row.css('opacity', '1');
                        alert('<?php echo esc_js(__('An error occurred. Please try again.', 'short-url')); ?>');
                    }
                },
                error: function() {
                    $row.css('opacity', '1');
                    alert('<?php echo esc_js(__('An error occurred. Please try again.', 'short-url')); ?>');
                }
            });
            
            return false;
        });
    });
</script> 