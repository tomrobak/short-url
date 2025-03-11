<?php
/**
 * Meta Box Template
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="short-url-meta-box">
    <?php if ($url_data) : ?>
        <div class="short-url-meta-box-link">
            <strong><?php esc_html_e('Short URL:', 'short-url'); ?></strong><br>
            <a href="<?php echo esc_url($url_data['short_url']); ?>" target="_blank"><?php echo esc_html($url_data['short_url']); ?></a>
            
            <button type="button" class="button button-small short-url-copy-button" data-clipboard-text="<?php echo esc_attr($url_data['short_url']); ?>">
                <span class="dashicons dashicons-clipboard"></span> <?php esc_html_e('Copy', 'short-url'); ?>
            </button>
            
            <?php if (current_user_can('view_short_url_analytics') && $url_data['visits'] > 0) : ?>
                <div class="short-url-meta-box-stats">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php echo esc_html(sprintf(
                        _n('%s visit', '%s visits', $url_data['visits'], 'short-url'),
                        number_format_i18n($url_data['visits'])
                    )); ?>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-analytics&id=' . $url_id)); ?>" target="_blank">
                        <?php esc_html_e('View Stats', 'short-url'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="short-url-meta-box-field">
        <label for="short_url_custom_slug" class="short-url-meta-box-label">
            <?php $url_data ? esc_html_e('Change Slug:', 'short-url') : esc_html_e('Custom Slug:', 'short-url'); ?>
        </label>
        
        <input type="text" name="short_url_custom_slug" id="short_url_custom_slug" class="short-url-meta-box-custom-slug" 
               value="<?php echo $url_data ? esc_attr($url_data['slug']) : ''; ?>" 
               placeholder="<?php esc_attr_e('Leave empty for auto-generation', 'short-url'); ?>" />
               
        <p class="description">
            <?php esc_html_e('The slug is the unique part of the short URL. Only alphanumeric characters, dashes, and underscores are allowed.', 'short-url'); ?>
        </p>
        
        <?php if ($post->post_status !== 'publish') : ?>
            <p class="description">
                <strong><?php esc_html_e('Note:', 'short-url'); ?></strong>
                <?php esc_html_e('The short URL will be created when you publish the post.', 'short-url'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    (function($) {
        // Initialize clipboard
        if (typeof ClipboardJS !== 'undefined') {
            var clipboard = new ClipboardJS('.short-url-copy-button');
            
            clipboard.on('success', function(e) {
                var $button = $(e.trigger);
                var originalText = $button.html();
                
                $button.html('<span class="dashicons dashicons-yes"></span> <?php esc_html_e('Copied!', 'short-url'); ?>');
                
                setTimeout(function() {
                    $button.html(originalText);
                }, 2000);
            });
        }
    })(jQuery);
</script> 