<?php
/**
 * Public URL submission form
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get settings
$placeholder = isset($atts['placeholder']) ? sanitize_text_field($atts['placeholder']) : __('Enter a URL to shorten', 'short-url');
$button_text = isset($atts['button']) ? sanitize_text_field($atts['button']) : __('Shorten URL', 'short-url');
$show_qr = isset($atts['qr']) && $atts['qr'] === 'true';
$success_message = isset($atts['success']) ? sanitize_text_field($atts['success']) : __('Your short URL has been created!', 'short-url');
$required_login = isset($atts['login']) && $atts['login'] === 'true';
$group_id = isset($atts['group']) ? intval($atts['group']) : 0;

// Check if user needs to be logged in
if ($required_login && !is_user_logged_in()) {
    $login_url = wp_login_url(get_permalink());
    echo '<div class="short-url-login-required">';
    printf(
        /* translators: %s: login URL */
        esc_html__('You need to %s to create short URLs.', 'short-url'),
        '<a href="' . esc_url($login_url) . '">' . esc_html__('log in', 'short-url') . '</a>'
    );
    echo '</div>';
    return;
}

// Form ID
$form_id = 'short-url-form-' . uniqid();

// Check for form submission
$result = array(
    'success' => false,
    'short_url' => '',
    'message' => '',
);

if (isset($_POST['short_url_form_nonce']) && wp_verify_nonce($_POST['short_url_form_nonce'], 'short_url_create_public')) {
    $long_url = isset($_POST['long_url']) ? esc_url_raw($_POST['long_url']) : '';
    $custom_slug = isset($_POST['custom_slug']) ? sanitize_text_field($_POST['custom_slug']) : '';
    
    if (empty($long_url)) {
        $result['message'] = __('Please enter a valid URL.', 'short-url');
    } else {
        // Create URL
        $db = new Short_URL_DB();
        $url_data = array(
            'destination_url' => $long_url,
            'created_by' => get_current_user_id(),
            'group_id' => $group_id,
        );
        
        if (!empty($custom_slug)) {
            $url_data['short_url'] = $custom_slug;
        }
        
        $url_id = $db->create_url($url_data);
        
        if ($url_id) {
            $url = $db->get_url($url_id);
            $result['success'] = true;
            $result['short_url'] = $url->short_url;
            $result['message'] = $success_message;
            
            if ($show_qr) {
                $result['qr_code'] = Short_URL_Utils::get_qr_code_url(
                    SHORT_URL_SITE_URL . '/' . $url->short_url,
                    200
                );
            }
        } else {
            $result['message'] = __('Failed to create short URL. Please try again.', 'short-url');
        }
    }
}
?>

<div class="short-url-public-form" id="<?php echo esc_attr($form_id); ?>">
    <?php if ($result['success']) : ?>
        <div class="short-url-success">
            <p class="short-url-success-message"><?php echo esc_html($result['message']); ?></p>
            
            <div class="short-url-result">
                <div class="short-url-link-container">
                    <a href="<?php echo esc_url(SHORT_URL_SITE_URL . '/' . $result['short_url']); ?>" class="short-url-link" target="_blank">
                        <?php echo esc_html(SHORT_URL_SITE_URL . '/' . $result['short_url']); ?>
                    </a>
                    <button class="short-url-copy-button" data-clipboard-text="<?php echo esc_url(SHORT_URL_SITE_URL . '/' . $result['short_url']); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
                
                <?php if ($show_qr && !empty($result['qr_code'])) : ?>
                    <div class="short-url-qr-container">
                        <img src="<?php echo esc_url($result['qr_code']); ?>" alt="QR Code" class="short-url-qr-code" />
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="short-url-create-another">
                <button class="short-url-reset-button">
                    <?php esc_html_e('Create Another', 'short-url'); ?>
                </button>
            </div>
        </div>
    <?php else : ?>
        <?php if (!empty($result['message'])) : ?>
            <div class="short-url-error">
                <p><?php echo esc_html($result['message']); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" class="short-url-form">
            <?php wp_nonce_field('short_url_create_public', 'short_url_form_nonce'); ?>
            
            <div class="short-url-form-group">
                <label for="long_url_<?php echo esc_attr($form_id); ?>" class="screen-reader-text">
                    <?php esc_html_e('URL to Shorten', 'short-url'); ?>
                </label>
                <input 
                    type="url" 
                    name="long_url" 
                    id="long_url_<?php echo esc_attr($form_id); ?>" 
                    placeholder="<?php echo esc_attr($placeholder); ?>" 
                    required
                />
            </div>
            
            <?php if (isset($atts['custom']) && $atts['custom'] === 'true') : ?>
                <div class="short-url-form-group">
                    <label for="custom_slug_<?php echo esc_attr($form_id); ?>" class="screen-reader-text">
                        <?php esc_html_e('Custom Slug (Optional)', 'short-url'); ?>
                    </label>
                    <input 
                        type="text" 
                        name="custom_slug" 
                        id="custom_slug_<?php echo esc_attr($form_id); ?>" 
                        placeholder="<?php esc_attr_e('Custom slug (optional)', 'short-url'); ?>"
                    />
                    <small class="short-url-form-hint">
                        <?php esc_html_e('Letters, numbers, and hyphens only. Leave blank for auto-generation.', 'short-url'); ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <div class="short-url-form-submit">
                <button type="submit" class="short-url-submit-button">
                    <?php echo esc_html($button_text); ?>
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        var formId = '<?php echo esc_js($form_id); ?>';
        var $form = $('#' + formId);
        
        // Initialize clipboard
        var clipboard = new ClipboardJS('.short-url-copy-button', {
            container: $form[0]
        });
        
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
        
        // Reset form to create another
        $form.find('.short-url-reset-button').on('click', function() {
            $form.find('.short-url-success').hide();
            $form.find('.short-url-form').show();
            $form.find('form')[0].reset();
        });
    });
</script> 