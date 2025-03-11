<?php
/**
 * URL Display shortcode view
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the URL parameters
$url_id = isset($atts['id']) ? intval($atts['id']) : 0;
$slug = isset($atts['slug']) ? sanitize_text_field($atts['slug']) : '';
$show_title = isset($atts['title']) && $atts['title'] === 'true';
$show_stats = isset($atts['stats']) && $atts['stats'] === 'true';
$show_qr = isset($atts['qr']) && $atts['qr'] === 'true';
$qr_size = isset($atts['qr_size']) ? intval($atts['qr_size']) : 150;
$custom_title = isset($atts['custom_title']) ? sanitize_text_field($atts['custom_title']) : '';

// Get post ID by post meta if the parameter exists
if (isset($atts['post_id'])) {
    $post_id = intval($atts['post_id']);
    $meta_url_id = get_post_meta($post_id, '_short_url_id', true);
    if ($meta_url_id) {
        $url_id = $meta_url_id;
    }
}

// Get the URL data
$url_data = null;
$db = new Short_URL_DB();

if ($url_id > 0) {
    $url_data = $db->get_url($url_id);
} elseif (!empty($slug)) {
    $url_data = $db->get_url_by_slug($slug);
}

// If we don't have URL data, display an error
if (!$url_data) {
    echo '<div class="short-url-error">';
    esc_html_e('Short URL not found.', 'short-url');
    echo '</div>';
    return;
}

// Get the full short URL
$full_url = SHORT_URL_SITE_URL . '/' . $url_data->short_url;

// Get QR code URL if needed
$qr_code_url = '';
if ($show_qr) {
    $qr_code_url = Short_URL_Utils::get_qr_code_url($full_url, $qr_size);
}

// Generate a unique ID for this URL display
$display_id = 'short-url-display-' . uniqid();

// Get stats if needed
$stats = null;
if ($show_stats) {
    $stats = $db->get_url_stats($url_id);
}

// Get custom title or use slug
$title = empty($custom_title) ? $url_data->short_url : $custom_title;
?>

<div class="short-url-block" id="<?php echo esc_attr($display_id); ?>">
    <?php if ($show_title) : ?>
        <h3 class="short-url-title"><?php echo esc_html($title); ?></h3>
    <?php endif; ?>
    
    <div class="short-url-content">
        <div class="short-url-link-container">
            <a href="<?php echo esc_url($full_url); ?>" class="short-url-link" target="_blank">
                <?php echo esc_html($full_url); ?>
            </a>
            <button class="short-url-copy-button" data-clipboard-text="<?php echo esc_url($full_url); ?>">
                <span class="dashicons dashicons-clipboard"></span>
            </button>
        </div>
        
        <?php if ($show_stats && $stats) : ?>
            <div class="short-url-stats">
                <div class="short-url-stat">
                    <span class="short-url-stat-label"><?php esc_html_e('Clicks:', 'short-url'); ?></span>
                    <span class="short-url-stat-value"><?php echo number_format($stats->clicks); ?></span>
                </div>
                
                <?php if (!empty($stats->last_click)) : ?>
                    <div class="short-url-stat">
                        <span class="short-url-stat-label"><?php esc_html_e('Last Click:', 'short-url'); ?></span>
                        <span class="short-url-stat-value">
                            <?php echo Short_URL_Utils::format_date($stats->last_click, false, true); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($show_qr && !empty($qr_code_url)) : ?>
            <div class="short-url-qr-container">
                <img 
                    src="<?php echo esc_url($qr_code_url); ?>" 
                    alt="<?php echo esc_attr(sprintf(__('QR Code for %s', 'short-url'), $full_url)); ?>" 
                    class="short-url-qr-code"
                    width="<?php echo esc_attr($qr_size); ?>"
                    height="<?php echo esc_attr($qr_size); ?>"
                />
            </div>
        <?php endif; ?>
        
        <?php if (isset($atts['share']) && $atts['share'] === 'true') : ?>
            <div class="short-url-sharing">
                <span class="short-url-sharing-label"><?php esc_html_e('Share:', 'short-url'); ?></span>
                <div class="short-url-sharing-buttons">
                    <?php 
                    $share_text = isset($atts['share_text']) ? sanitize_text_field($atts['share_text']) : '';
                    $sharing_links = Short_URL_Utils::get_social_sharing_links($full_url, $share_text);
                    
                    foreach ($sharing_links as $network => $link) : 
                    ?>
                        <a href="<?php echo esc_url($link); ?>" target="_blank" class="short-url-share-button short-url-share-<?php echo esc_attr($network); ?>" title="<?php echo esc_attr(sprintf(__('Share on %s', 'short-url'), ucfirst($network))); ?>">
                            <span class="dashicons dashicons-share"></span>
                            <span class="short-url-share-label"><?php echo esc_html(ucfirst($network)); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        var displayId = '<?php echo esc_js($display_id); ?>';
        var $displayBlock = $('#' + displayId);
        
        // Initialize clipboard
        var clipboard = new ClipboardJS('#' + displayId + ' .short-url-copy-button');
        
        clipboard.on('success', function(e) {
            var $button = $(e.trigger);
            
            // Show success feedback
            $button.addClass('copied');
            
            // Add tooltip if it doesn't exist
            if (!$button.find('.short-url-tooltip').length) {
                $button.append('<span class="short-url-tooltip"><?php esc_html_e('Copied!', 'short-url'); ?></span>');
            }
            
            // Remove after delay
            setTimeout(function() {
                $button.removeClass('copied');
                $button.find('.short-url-tooltip').remove();
            }, 2000);
            
            e.clearSelection();
        });
    });
</script> 