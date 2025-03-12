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
        <div class="short-url-display-container">
            <div class="short-url-display">
                <div class="short-url-input-group">
                    <a id="short-url-link" href="<?php echo esc_url($url_data['short_url']); ?>" target="_blank" class="short-url-value">
                        <?php echo esc_html($url_data['short_url']); ?>
                    </a>
                </div>
                
                <div class="short-url-actions">
                    <button type="button" class="short-url-copy-button" data-clipboard-text="<?php echo esc_attr($url_data['short_url']); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
            </div>
            
            <div class="short-url-meta-box-stats">
                <?php 
                printf(
                    '<a href="%s" target="_blank">%s</a>',
                    esc_url(admin_url('admin.php?page=short-url-analytics&url_id=' . $url_id)),
                    sprintf(
                        _n('%s click', '%s clicks', $url_data['visits'], 'short-url'),
                        '<strong>' . number_format_i18n($url_data['visits']) . '</strong>'
                    )
                ); 
                ?>
            </div>
        </div>
    <?php else : ?>
        <p class="short-url-notice">
            <?php if ($post->post_status === 'publish') : ?>
                <?php esc_html_e('No short URL has been created yet.', 'short-url'); ?>
            <?php else : ?>
                <?php esc_html_e('A short URL will be created when you publish this post.', 'short-url'); ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>
    
    <div class="short-url-meta-box-field">
        <label for="short_url_custom_slug" class="short-url-meta-box-label">
            <?php $url_data ? esc_html_e('Custom Slug', 'short-url') : esc_html_e('Custom Slug', 'short-url'); ?>
            <span class="short-url-optional"><?php esc_html_e('(optional)', 'short-url'); ?></span>
        </label>
        
        <div class="short-url-slug-input-group">
            <input type="text" name="short_url_custom_slug" id="short_url_custom_slug" class="short-url-meta-box-custom-slug" 
                value="<?php echo $url_data ? esc_attr($url_data['slug']) : ''; ?>" 
                placeholder="<?php esc_attr_e('Leave empty for auto-generation', 'short-url'); ?>" />
                
            <?php if (!$url_data) : ?>
                <button type="button" class="short-url-generate-button" id="short_url_generate_slug">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Generate', 'short-url'); ?>
                </button>
            <?php endif; ?>
        </div>
        
        <p class="description">
            <?php esc_html_e('The slug is the unique part of the short URL. Only letters, numbers, and hyphens are allowed.', 'short-url'); ?>
        </p>
        
        <?php if ($post->post_status !== 'publish') : ?>
            <p class="short-url-notice">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('The short URL will be created when you publish the post.', 'short-url'); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<style>
/* Modern styling for the Short URL metabox */
.short-url-meta-box {
    padding: 12px;
    margin: -6px -12px;
    color: #1e1e1e;
}

.short-url-display-container {
    background-color: #f9f9f9;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 16px;
    border: 1px solid #e0e0e0;
}

.short-url-display {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.short-url-input-group {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.short-url-value {
    font-family: monospace;
    font-size: 13px;
    color: #2271b1;
    text-decoration: none;
    font-weight: 500;
}

.short-url-value:hover {
    color: #135e96;
    text-decoration: underline;
}

.short-url-copy-button {
    background: transparent;
    border: none;
    color: #2271b1;
    cursor: pointer;
    padding: 4px;
    border-radius: 3px;
    margin-left: 5px;
}

.short-url-copy-button:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.short-url-copy-button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.short-url-meta-box-stats {
    margin-top: 8px;
    font-size: 13px;
    color: #50575e;
}

.short-url-meta-box-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.short-url-optional {
    color: #757575;
    font-weight: normal;
    font-size: 12px;
}

.short-url-slug-input-group {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
}

.short-url-meta-box-custom-slug {
    flex: 1;
}

.short-url-generate-button {
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    margin-left: 5px;
    padding: 0 8px;
    cursor: pointer;
    height: 30px;
    display: flex;
    align-items: center;
}

.short-url-generate-button:hover {
    background: #f6f7f7;
}

.short-url-generate-button .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-right: 3px;
}

.short-url-notice {
    display: flex;
    align-items: center;
    color: #757575;
    font-size: 12px;
    margin: 8px 0 0;
}

.short-url-notice .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-right: 5px;
    color: #72aee6;
}

.short-url-secondary-actions {
    display: flex;
    margin-top: 10px;
    gap: 5px;
}

.short-url-action-button {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-size: 12px;
    color: #2271b1;
    padding: 3px 6px;
    border-radius: 3px;
}

.short-url-action-button:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: #135e96;
}

.short-url-action-button .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    margin-right: 3px;
}

.short-url-copy-button.full-width {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 5px;
    margin: 0 0 8px;
    background: #f0f0f1;
    border: 1px solid #c3c4c7;
    border-radius: 3px;
    color: #3c434a;
}

.short-url-copy-button.full-width:hover {
    background: #f6f7f7;
}

.short-url-copy-icon {
    margin-right: 5px;
}

.short-url-copy-text {
    font-size: 12px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize clipboard.js
    var clipboard = new ClipboardJS('.short-url-copy-button');
    
    clipboard.on('success', function(e) {
        var $button = $(e.trigger);
        var originalHtml = $button.html();
        
        // Show success message
        $button.html('<span class="dashicons dashicons-yes"></span>');
        
        // Reset after 2 seconds
        setTimeout(function() {
            $button.html(originalHtml);
        }, 2000);
        
        e.clearSelection();
    });
    
    // Generate slug button
    $('#short_url_generate_slug').on('click', function() {
        var $button = $(this);
        var $input = $('#short_url_custom_slug');
        
        // Show loading state
        $button.prop('disabled', true);
        $button.find('.dashicons').addClass('dashicons-update-alt').removeClass('dashicons-update');
        $button.find('.dashicons').css('animation', 'rotation 2s infinite linear');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'short_url_generate_slug',
                nonce: '<?php echo wp_create_nonce('short_url_generate_slug'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.slug) {
                    $input.val(response.data.slug);
                }
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false);
                $button.find('.dashicons').removeClass('dashicons-update-alt').addClass('dashicons-update');
                $button.find('.dashicons').css('animation', '');
            }
        });
    });
});

@keyframes rotation {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(359deg);
    }
}
</script> 