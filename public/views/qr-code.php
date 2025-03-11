<?php
/**
 * QR Code display
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get the URL
$url_id = isset($atts['id']) ? intval($atts['id']) : 0;
$slug = isset($atts['slug']) ? sanitize_text_field($atts['slug']) : '';
$show_link = isset($atts['link']) && $atts['link'] === 'true';
$size = isset($atts['size']) ? intval($atts['size']) : 200;

// Size constraints
$size = max(100, min(500, $size));

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

// Get QR code URL
$qr_code_url = Short_URL_Utils::get_qr_code_url($full_url, $size);

// Generate a unique ID for this QR code
$qr_id = 'short-url-qr-' . uniqid();
?>

<div class="short-url-qr-block" id="<?php echo esc_attr($qr_id); ?>">
    <div class="short-url-qr-container">
        <img 
            src="<?php echo esc_url($qr_code_url); ?>" 
            alt="<?php echo esc_attr(sprintf(__('QR Code for %s', 'short-url'), $full_url)); ?>" 
            class="short-url-qr-code"
            width="<?php echo esc_attr($size); ?>"
            height="<?php echo esc_attr($size); ?>"
        />
    </div>
    
    <?php if ($show_link) : ?>
        <div class="short-url-link-container">
            <a href="<?php echo esc_url($full_url); ?>" class="short-url-link" target="_blank">
                <?php echo esc_html($full_url); ?>
            </a>
            <button class="short-url-copy-button" data-clipboard-text="<?php echo esc_url($full_url); ?>">
                <span class="dashicons dashicons-clipboard"></span>
            </button>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        var qrId = '<?php echo esc_js($qr_id); ?>';
        var $qrBlock = $('#' + qrId);
        
        // Initialize clipboard
        var clipboard = new ClipboardJS('#' + qrId + ' .short-url-copy-button');
        
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