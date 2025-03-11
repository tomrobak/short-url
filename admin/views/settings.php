<?php
/**
 * Settings page view
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get settings
$slug_length = get_option('short_url_slug_length', 6);
$link_prefix = get_option('short_url_link_prefix', '');
$redirect_type = get_option('short_url_redirect_type', '302');
$track_visits = get_option('short_url_track_visits', 1);
$track_referrer = get_option('short_url_track_referrer', 1);
$track_ip = get_option('short_url_track_ip', 1);
$track_device = get_option('short_url_track_device', 1);
$track_location = get_option('short_url_track_location', 1);
$auto_create_post_types = get_option('short_url_auto_create_post_types', array('post', 'page'));
$display_metabox_post_types = get_option('short_url_display_metabox_post_types', array('post', 'page'));
$display_in_content = get_option('short_url_display_in_content', 0);
$display_position = get_option('short_url_display_position', 'after');
$anonymize_ip = get_option('short_url_anonymize_ip', 1);
$data_retention = get_option('short_url_data_retention', 365);

// Get all post types
$post_types = get_post_types(array('public' => true), 'objects');
?>

<div class="wrap">
    <h1><?php _e('Short URL Settings', 'short-url'); ?></h1>
    
    <?php settings_errors(); ?>
    
    <form method="post" action="options.php">
        <?php settings_fields('short_url_settings'); ?>
        
        <div class="short-url-settings-container">
            <div class="short-url-settings-main">
                <div class="short-url-settings-section">
                    <h2><?php _e('General Settings', 'short-url'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="short_url_slug_length"><?php _e('Default Slug Length', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="short_url_slug_length" name="short_url_slug_length" value="<?php echo esc_attr($slug_length); ?>" min="3" max="20" class="small-text">
                                <p class="description"><?php _e('The default length of automatically generated slugs.', 'short-url'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_link_prefix"><?php _e('URL Prefix', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="short_url_link_prefix" name="short_url_link_prefix" value="<?php echo esc_attr($link_prefix); ?>" class="regular-text">
                                <p class="description"><?php _e('Optional prefix for short URLs (e.g., "go" will create URLs like example.com/go/abc123). Leave empty to use the root URL.', 'short-url'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_redirect_type"><?php _e('Redirect Type', 'short-url'); ?></label>
                            </th>
                            <td>
                                <select id="short_url_redirect_type" name="short_url_redirect_type">
                                    <option value="301" <?php selected($redirect_type, '301'); ?>><?php _e('301 - Permanent Redirect', 'short-url'); ?></option>
                                    <option value="302" <?php selected($redirect_type, '302'); ?>><?php _e('302 - Temporary Redirect', 'short-url'); ?></option>
                                    <option value="307" <?php selected($redirect_type, '307'); ?>><?php _e('307 - Temporary Redirect (Strict)', 'short-url'); ?></option>
                                </select>
                                <p class="description"><?php _e('The HTTP status code used for redirects.', 'short-url'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label><?php _e('Character Set', 'short-url'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php _e('Character Set', 'short-url'); ?></legend>
                                    <label>
                                        <input type="checkbox" name="short_url_use_lowercase" value="1" <?php checked(get_option('short_url_use_lowercase', 1), 1); ?>>
                                        <?php _e('Lowercase letters (a-z)', 'short-url'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="short_url_use_uppercase" value="1" <?php checked(get_option('short_url_use_uppercase', 1), 1); ?>>
                                        <?php _e('Uppercase letters (A-Z)', 'short-url'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="short_url_use_numbers" value="1" <?php checked(get_option('short_url_use_numbers', 1), 1); ?>>
                                        <?php _e('Numbers (0-9)', 'short-url'); ?>
                                    </label><br>
                                    
                                    <label>
                                        <input type="checkbox" name="short_url_use_special" value="1" <?php checked(get_option('short_url_use_special', 0), 1); ?>>
                                        <?php _e('Special characters (e.g., -_)', 'short-url'); ?>
                                    </label>
                                    
                                    <p class="description"><?php _e('Select which character types to include in automatically generated slugs. At least one option must be selected.', 'short-url'); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="short-url-settings-section">
                    <h2><?php _e('Post Integration', 'short-url'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label><?php _e('Auto-Create URLs for', 'short-url'); ?></label>
                            </th>
                            <td>
                                <?php foreach ($post_types as $post_type): ?>
                                <label>
                                    <input type="checkbox" name="short_url_auto_create_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, (array) $auto_create_post_types)); ?>>
                                    <?php echo esc_html($post_type->labels->singular_name); ?>
                                </label><br>
                                <?php endforeach; ?>
                                <p class="description"><?php _e('Automatically create short URLs when publishing these post types.', 'short-url'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label><?php _e('Display Meta Box for', 'short-url'); ?></label>
                            </th>
                            <td>
                                <?php foreach ($post_types as $post_type): ?>
                                <label>
                                    <input type="checkbox" name="short_url_display_metabox_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, (array) $display_metabox_post_types)); ?>>
                                    <?php echo esc_html($post_type->labels->singular_name); ?>
                                </label><br>
                                <?php endforeach; ?>
                                <p class="description"><?php _e('Show the Short URL meta box when editing these post types.', 'short-url'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_display_in_content"><?php _e('Display in Content', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="short_url_display_in_content" name="short_url_display_in_content" value="1" <?php checked($display_in_content, 1); ?>>
                                <label for="short_url_display_in_content"><?php _e('Automatically display short URL in post content', 'short-url'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_display_position"><?php _e('Display Position', 'short-url'); ?></label>
                            </th>
                            <td>
                                <select id="short_url_display_position" name="short_url_display_position">
                                    <option value="before" <?php selected($display_position, 'before'); ?>><?php _e('Before content', 'short-url'); ?></option>
                                    <option value="after" <?php selected($display_position, 'after'); ?>><?php _e('After content', 'short-url'); ?></option>
                                </select>
                                <p class="description"><?php _e('Where to display the short URL in the post content.', 'short-url'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="short-url-settings-section">
                    <h2><?php _e('Analytics & Privacy', 'short-url'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="short_url_track_visits"><?php _e('Track Visits', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="short_url_track_visits" name="short_url_track_visits" value="1" <?php checked($track_visits, 1); ?>>
                                <label for="short_url_track_visits"><?php _e('Track the number of visits to each short URL', 'short-url'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_track_referrer"><?php _e('Track Referrer', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="short_url_track_referrer" name="short_url_track_referrer" value="1" <?php checked($track_referrer, 1); ?>>
                                <label for="short_url_track_referrer"><?php _e('Track the referrer URL', 'short-url'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_track_ip"><?php _e('Track IP Address', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="short_url_track_ip" name="short_url_track_ip" value="1" <?php checked($track_ip, 1); ?>>
                                <label for="short_url_track_ip"><?php _e('Track visitor IP addresses', 'short-url'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_track_device"><?php _e('Track Device Info', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="short_url_track_device" name="short_url_track_device" value="1" <?php checked($track_device, 1); ?>>
                                <label for="short_url_track_device"><?php _e('Track browser, device, and OS information', 'short-url'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_track_location"><?php _e('Track Location', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="short_url_track_location" name="short_url_track_location" value="1" <?php checked($track_location, 1); ?>>
                                <label for="short_url_track_location"><?php _e('Track visitor location based on IP address', 'short-url'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_anonymize_ip"><?php _e('Anonymize IP Addresses', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="short_url_anonymize_ip" name="short_url_anonymize_ip" value="1" <?php checked($anonymize_ip, 1); ?>>
                                <label for="short_url_anonymize_ip"><?php _e('Anonymize the last octet of IP addresses for privacy', 'short-url'); ?></label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="short_url_data_retention"><?php _e('Data Retention Period', 'short-url'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="short_url_data_retention" name="short_url_data_retention" value="<?php echo esc_attr($data_retention); ?>" min="1" class="small-text">
                                <label for="short_url_data_retention"><?php _e('days', 'short-url'); ?></label>
                                <p class="description"><?php _e('How long to keep analytics data. Set to 0 for indefinite storage.', 'short-url'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="short-url-settings-sidebar">
                <div class="short-url-settings-box">
                    <h3><?php _e('About Short URL', 'short-url'); ?></h3>
                    <p><?php _e('Version', 'short-url'); ?>: <?php echo SHORT_URL_VERSION; ?></p>
                    <p><?php _e('A powerful URL shortener plugin for WordPress with analytics, QR codes, and more.', 'short-url'); ?></p>
                    <p><a href="https://github.com/tomrobak/short-url" target="_blank"><?php _e('GitHub Repository', 'short-url'); ?></a></p>
                </div>
                
                <div class="short-url-settings-box">
                    <h3><?php _e('Need Help?', 'short-url'); ?></h3>
                    <p><?php _e('Check out the documentation for help with setup and usage.', 'short-url'); ?></p>
                    <p><a href="https://github.com/tomrobak/short-url/wiki" target="_blank" class="button"><?php _e('Documentation', 'short-url'); ?></a></p>
                </div>
                
                <div class="short-url-settings-box">
                    <h3><?php _e('Quick Links', 'short-url'); ?></h3>
                    <ul>
                        <li><a href="<?php echo admin_url('admin.php?page=short-url'); ?>"><?php _e('Dashboard', 'short-url'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=short-url-urls'); ?>"><?php _e('Manage URLs', 'short-url'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=short-url-add-new'); ?>"><?php _e('Add New URL', 'short-url'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=short-url-groups'); ?>"><?php _e('Manage Groups', 'short-url'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=short-url-analytics'); ?>"><?php _e('Analytics', 'short-url'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=short-url-tools'); ?>"><?php _e('Tools', 'short-url'); ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php submit_button(); ?>
    </form>
</div>

<style>
.short-url-settings-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.short-url-settings-main {
    flex: 1;
    min-width: 0;
}

.short-url-settings-sidebar {
    width: 300px;
    flex-shrink: 0;
}

.short-url-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
    padding: 0 20px 20px;
}

.short-url-settings-section h2 {
    padding: 12px 0;
    margin: 0 -20px 20px;
    border-bottom: 1px solid #eee;
    padding-left: 20px;
}

.short-url-settings-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
    padding: 15px;
}

.short-url-settings-box h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.short-url-settings-box ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.short-url-settings-box ul li {
    margin-bottom: 8px;
}

@media screen and (max-width: 782px) {
    .short-url-settings-container {
        flex-direction: column;
    }
    
    .short-url-settings-sidebar {
        width: 100%;
    }
}
</style> 