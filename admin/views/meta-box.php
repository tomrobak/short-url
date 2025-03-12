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
    <div id="short-url-display-area" <?php echo !$url_data ? 'style="display:none;"' : ''; ?> class="short-url-display-container">
        <div class="short-url-meta-box-link">
            <div class="short-url-header">
                <span class="short-url-label"><?php esc_html_e('Short URL', 'short-url'); ?></span>
                <?php if (current_user_can('view_short_url_analytics') && $url_data && $url_data['visits'] > 0) : ?>
                    <div class="short-url-meta-box-stats">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php echo esc_html(sprintf(
                            _n('%s visit', '%s visits', $url_data['visits'], 'short-url'),
                            number_format_i18n($url_data['visits'])
                        )); ?>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-analytics&id=' . $url_id)); ?>" target="_blank" class="short-url-stats-link">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('View Stats', 'short-url'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="short-url-display">
                <div class="short-url-input-group">
                    <a id="short-url-link" href="<?php echo esc_url($url_data ? $url_data['short_url'] : ''); ?>" target="_blank" class="short-url-value">
                        <?php echo esc_html($url_data ? $url_data['short_url'] : ''); ?>
                    </a>
                </div>
                
                <div class="short-url-actions">
                    <button type="button" class="short-url-copy-button full-width" data-clipboard-text="<?php echo esc_attr($url_data ? $url_data['short_url'] : ''); ?>">
                        <span class="short-url-copy-icon dashicons dashicons-clipboard"></span>
                        <span class="short-url-copy-text"><?php esc_html_e('Copy URL', 'short-url'); ?></span>
                    </button>
                    
                    <div class="short-url-secondary-actions">
                        <a href="<?php echo esc_url($url_data ? $url_data['short_url'] : ''); ?>" target="_blank" class="short-url-action-button short-url-open-button">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e('Open', 'short-url'); ?>
                        </a>
                        
                        <?php if (current_user_can('edit_short_urls')) : ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-add-new&id=' . $url_id)); ?>" target="_blank" class="short-url-action-button short-url-edit-button">
                                <span class="dashicons dashicons-edit"></span>
                                <?php esc_html_e('Edit', 'short-url'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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

.short-url-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.short-url-label {
    font-weight: 600;
    font-size: 14px;
    color: #1e1e1e;
}

.short-url-meta-box-stats {
    display: flex;
    align-items: center;
    font-size: 12px;
    color: #666;
}

.short-url-meta-box-stats .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-right: 4px;
    color: #0073aa;
}

.short-url-stats-link {
    margin-left: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    color: #0073aa;
    font-weight: 500;
    transition: color 0.2s;
}

.short-url-stats-link:hover {
    color: #00a0d2;
}

.short-url-stats-link .dashicons {
    margin-right: 4px;
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.short-url-display {
    margin-top: 8px;
}

.short-url-input-group {
    display: block;
    margin-bottom: 10px;
}

.short-url-value {
    width: 100%;
    padding: 8px 12px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    color: #0073aa;
    text-decoration: none;
    overflow-wrap: break-word;
    word-break: break-all;
    display: block;
    box-sizing: border-box;
    margin-bottom: 8px;
}

.short-url-copy-button {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #0073aa;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
    margin-bottom: 10px;
}

.short-url-copy-button.full-width {
    width: 100%;
}

.short-url-copy-button:hover {
    background-color: #00a0d2;
}

.short-url-copy-button .short-url-copy-icon {
    margin-right: 4px;
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.short-url-actions {
    display: block;
}

.short-url-secondary-actions {
    display: flex;
    gap: 8px;
    justify-content: space-between;
}

.short-url-action-button {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    font-size: 12px;
    color: #0073aa;
    padding: 4px 8px;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.short-url-action-button:hover {
    background-color: rgba(0, 115, 170, 0.1);
    color: #00a0d2;
}

.short-url-action-button .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
    margin-right: 4px;
}

.short-url-meta-box-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}

.short-url-optional {
    font-weight: normal;
    color: #666;
    font-size: 12px;
    margin-left: 4px;
}

.short-url-slug-input-group {
    display: flex;
    margin-bottom: 8px;
}

.short-url-meta-box-custom-slug {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 6px 8px;
    font-size: 13px;
}

.short-url-generate-button {
    display: flex;
    align-items: center;
    background-color: #f0f0f0;
    border: 1px solid #ddd;
    border-left: none;
    border-radius: 0 4px 4px 0;
    padding: 0 12px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.short-url-generate-button:hover {
    background-color: #e0e0e0;
}

.short-url-generate-button .dashicons {
    margin-right: 4px;
}

.description {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
    margin-bottom: 8px;
}

.short-url-notice {
    display: flex;
    align-items: flex-start;
    background-color: #f0f8ff;
    padding: 8px 12px;
    border-radius: 4px;
    border-left: 4px solid #0073aa;
    font-size: 12px;
    color: #444;
}

.short-url-notice .dashicons {
    color: #0073aa;
    margin-right: 8px;
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>

<script type="text/javascript">
    (function($) {
        // Initialize clipboard
        if (typeof ClipboardJS !== 'undefined') {
            var clipboard = new ClipboardJS('.short-url-copy-button');
            
            clipboard.on('success', function(e) {
                var $button = $(e.trigger);
                var $icon = $button.find('.short-url-copy-icon');
                var $text = $button.find('.short-url-copy-text');
                
                // Save original content
                var originalIcon = $icon.attr('class');
                var originalText = $text.text();
                
                // Change to success state
                $icon.attr('class', 'dashicons dashicons-yes-alt');
                $text.text('Copied!');
                
                // Revert after delay
                setTimeout(function() {
                    $icon.attr('class', originalIcon);
                    $text.text(originalText);
                }, 2000);
                
                e.clearSelection();
            });
        }
        
        // Generate slug button
        $('#short_url_generate_slug').on('click', function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'short_url_generate_slug',
                    nonce: '<?php echo wp_create_nonce('short_url_admin'); ?>'
                },
                beforeSend: function() {
                    $('#short_url_generate_slug').prop('disabled', true);
                    $('#short_url_generate_slug').html('<span class="dashicons dashicons-update dashicons-spin"></span> <?php esc_html_e('Generating...', 'short-url'); ?>');
                },
                success: function(response) {
                    if (response.success && response.data.slug) {
                        $('#short_url_custom_slug').val(response.data.slug);
                    }
                },
                complete: function() {
                    $('#short_url_generate_slug').prop('disabled', false);
                    $('#short_url_generate_slug').html('<span class="dashicons dashicons-update"></span> <?php esc_html_e('Generate', 'short-url'); ?>');
                }
            });
        });
        
        // AJAX update for shortlink after save
        $(document).on('ajaxSuccess', function(event, xhr, settings) {
            // Check if this is a post save or update
            if (settings.data && (settings.data.indexOf('action=heartbeat') !== -1 || 
                settings.data.indexOf('action=inline-save') !== -1 ||
                settings.data.indexOf('action=edit-post') !== -1)) {
                
                var postId = <?php echo intval($post->ID); ?>;
                
                // Make AJAX call to get updated short URL
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'short_url_get_post_url',
                        post_id: postId,
                        security: '<?php echo wp_create_nonce('short_url_get_post_url_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data.url) {
                            // Update the displayed URL
                            $('#short-url-link').attr('href', response.data.url).text(response.data.url);
                            $('.short-url-copy-button').attr('data-clipboard-text', response.data.url);
                            $('.short-url-open-button').attr('href', response.data.url);
                            $('#short-url-display-area').show();
                        }
                    }
                });
            }
        });
        
        // Add spinning animation for dashicons-update
        $('<style>.dashicons-spin { animation: dashicons-spin 1s infinite; } @keyframes dashicons-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
    })(jQuery);
</script> 