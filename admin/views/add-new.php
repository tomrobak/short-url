<?php
/**
 * Admin Add New URL Page
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if we're editing an existing URL
$editing = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$url_id = $editing ? intval($_GET['id']) : 0;
$url_data = null;

// Get settings options
$default_slug_length = get_option('short_url_slug_length', 6);

// Get the URL data if editing
if ($editing) {
    $db = new Short_URL_DB();
    $url_data = $db->get_url($url_id);
    
    if (!$url_data) {
        wp_die(__('URL not found.', 'short-url'));
    }
}

// Process form submission
if (isset($_POST['short_url_nonce']) && wp_verify_nonce($_POST['short_url_nonce'], 'short_url_save')) {
    // Get form data
    $destination_url = isset($_POST['destination_url']) ? esc_url_raw($_POST['destination_url']) : '';
    $custom_slug = isset($_POST['short_url_custom_slug']) ? sanitize_text_field($_POST['short_url_custom_slug']) : '';
    $use_custom_slug = isset($_POST['short_url_use_custom_slug']) && $_POST['short_url_use_custom_slug'] === 'on';
    $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
    $password_protected = isset($_POST['short_url_password_protected']) && $_POST['short_url_password_protected'] === 'on';
    $password = isset($_POST['short_url_password']) ? sanitize_text_field($_POST['short_url_password']) : '';
    $expires = isset($_POST['short_url_expires']) && $_POST['short_url_expires'] === 'on';
    $expiry_date = isset($_POST['short_url_expiry_date']) ? sanitize_text_field($_POST['short_url_expiry_date']) : '';
    $expiry_time = isset($_POST['short_url_expiry_time']) ? sanitize_text_field($_POST['short_url_expiry_time']) : '23:59';
    $add_utm = isset($_POST['short_url_add_utm']) && $_POST['short_url_add_utm'] === 'on';
    
    // UTM parameters
    $utm_params = array();
    if ($add_utm) {
        $utm_fields = array('source', 'medium', 'campaign', 'term', 'content');
        foreach ($utm_fields as $field) {
            $param_key = 'short_url_utm_' . $field;
            $utm_params['utm_' . $field] = isset($_POST[$param_key]) ? sanitize_text_field($_POST[$param_key]) : '';
        }
    }
    
    // Validate destination URL
    if (empty($destination_url)) {
        $error = __('Destination URL is required.', 'short-url');
    } else {
        // Prepare URL data
        $url = array(
            'destination_url' => $destination_url,
            'group_id' => $group_id > 0 ? $group_id : null,
            'created_by' => get_current_user_id(),
        );
        
        // Add custom slug if provided
        if ($use_custom_slug && !empty($custom_slug)) {
            $url['short_url'] = $custom_slug;
        }
        
        // Add password if enabled
        if ($password_protected && !empty($password)) {
            $url['password_protected'] = 1;
            $url['password'] = wp_hash_password($password);
        } else {
            $url['password_protected'] = 0;
            $url['password'] = null;
        }
        
        // Add expiration if enabled
        if ($expires && !empty($expiry_date)) {
            $expiry_datetime = $expiry_date . ' ' . $expiry_time;
            $url['expires'] = 1;
            $url['expiry_date'] = $expiry_datetime;
        } else {
            $url['expires'] = 0;
            $url['expiry_date'] = null;
        }
        
        // Add UTM parameters
        if ($add_utm && !empty($utm_params)) {
            // Create a URL with UTM parameters
            $url['destination_url'] = Short_URL_Utils::create_utm_url($destination_url, $utm_params);
        }
        
        $db = new Short_URL_DB();
        
        // Create or update the URL
        if ($editing) {
            $result = $db->update_url($url_id, $url);
            $success_message = __('URL updated successfully.', 'short-url');
            $redirect_url = admin_url('admin.php?page=short-url&message=updated');
        } else {
            $result = $db->create_url($url);
            $success_message = __('URL created successfully.', 'short-url');
            $redirect_url = admin_url('admin.php?page=short-url&message=created');
        }
        
        if ($result) {
            wp_redirect($redirect_url);
            exit;
        } else {
            $error = $editing ? __('Failed to update URL.', 'short-url') : __('Failed to create URL.', 'short-url');
        }
    }
}

// Get available groups for the select field
$db = new Short_URL_DB();
$groups = $db->get_groups();
?>

<div class="wrap short-url-container">
    <h1 class="wp-heading-inline">
        <?php echo $editing ? esc_html__('Edit URL', 'short-url') : esc_html__('Add New URL', 'short-url'); ?>
    </h1>
    
    <a href="<?php echo esc_url(admin_url('admin.php?page=short-url')); ?>" class="page-title-action">
        <?php esc_html_e('Back to URLs', 'short-url'); ?>
    </a>
    
    <hr class="wp-header-end">
    
    <?php if (isset($error)) : ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" class="short-url-form">
        <?php wp_nonce_field('short_url_save', 'short_url_nonce'); ?>
        
        <div class="short-url-form-section">
            <h3><?php esc_html_e('URL Details', 'short-url'); ?></h3>
            
            <div class="short-url-form-row">
                <label for="destination_url"><?php esc_html_e('Destination URL', 'short-url'); ?> *</label>
                <input 
                    type="url" 
                    id="destination_url" 
                    name="destination_url" 
                    value="<?php echo $editing ? esc_url($url_data->destination_url) : ''; ?>" 
                    placeholder="https://example.com/your-long-url" 
                    required
                >
                <p class="description"><?php esc_html_e('The long URL that users will be redirected to.', 'short-url'); ?></p>
            </div>
            
            <div class="short-url-form-row">
                <div class="short-url-checkbox-field">
                    <input 
                        type="checkbox" 
                        id="short_url_use_custom_slug" 
                        name="short_url_use_custom_slug" 
                        <?php checked($editing || isset($_POST['short_url_use_custom_slug'])); ?>
                    >
                    <label for="short_url_use_custom_slug"><?php esc_html_e('Use Custom Slug', 'short-url'); ?></label>
                </div>
                
                <div class="short-url-custom-slug-field" style="display: none;">
                    <div class="short-url-slug-field">
                        <span class="short-url-prefix"><?php echo esc_html(SHORT_URL_SITE_URL . '/'); ?></span>
                        <input 
                            type="text" 
                            id="short_url_custom_slug" 
                            name="short_url_custom_slug" 
                            value="<?php echo $editing ? esc_attr($url_data->short_url) : ''; ?>" 
                            pattern="[a-zA-Z0-9-]+" 
                            title="<?php esc_attr_e('Only letters, numbers, and hyphens are allowed', 'short-url'); ?>"
                        >
                        <button type="button" id="short_url_generate_slug" class="button" data-length="<?php echo esc_attr($default_slug_length); ?>">
                            <?php esc_html_e('Generate', 'short-url'); ?>
                        </button>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Custom part of the URL. Only letters, numbers, and hyphens are allowed.', 'short-url'); ?>
                    </p>
                </div>
            </div>
            
            <?php if (!empty($groups)) : ?>
            <div class="short-url-form-row">
                <label for="group_id"><?php esc_html_e('Group', 'short-url'); ?></label>
                <select id="group_id" name="group_id">
                    <option value="0"><?php esc_html_e('None', 'short-url'); ?></option>
                    <?php foreach ($groups as $group) : ?>
                        <option value="<?php echo esc_attr($group->id); ?>" <?php selected($editing && $url_data->group_id == $group->id); ?>>
                            <?php echo esc_html($group->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('Assign this URL to a group for better organization.', 'short-url'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="short-url-form-section">
            <h3><?php esc_html_e('Advanced Options', 'short-url'); ?></h3>
            
            <div class="short-url-form-row">
                <div class="short-url-checkbox-field">
                    <input 
                        type="checkbox" 
                        id="short_url_password_protected" 
                        name="short_url_password_protected" 
                        <?php checked($editing && $url_data->password_protected); ?>
                    >
                    <label for="short_url_password_protected"><?php esc_html_e('Password Protected', 'short-url'); ?></label>
                </div>
                
                <div class="short-url-password-field" style="display: none;">
                    <input 
                        type="password" 
                        id="short_url_password" 
                        name="short_url_password" 
                        autocomplete="new-password"
                        placeholder="<?php esc_attr_e('Enter password', 'short-url'); ?>"
                    >
                    <p class="description">
                        <?php 
                        if ($editing && $url_data->password_protected) {
                            esc_html_e('Leave empty to keep existing password.', 'short-url');
                        } else {
                            esc_html_e('Users will need to enter this password to access the destination URL.', 'short-url');
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="short-url-form-row">
                <div class="short-url-checkbox-field">
                    <input 
                        type="checkbox" 
                        id="short_url_expires" 
                        name="short_url_expires" 
                        <?php checked($editing && $url_data->expires); ?>
                    >
                    <label for="short_url_expires"><?php esc_html_e('URL Expires', 'short-url'); ?></label>
                </div>
                
                <div class="short-url-expiration-fields" style="display: none;">
                    <div class="short-url-date-time-fields">
                        <input 
                            type="text" 
                            id="short_url_expiry_date" 
                            name="short_url_expiry_date" 
                            class="short-url-datepicker"
                            placeholder="<?php esc_attr_e('YYYY-MM-DD', 'short-url'); ?>"
                            value="<?php echo $editing && $url_data->expires ? esc_attr(substr($url_data->expiry_date, 0, 10)) : ''; ?>"
                        >
                        <input 
                            type="time" 
                            id="short_url_expiry_time" 
                            name="short_url_expiry_time" 
                            class="short-url-time"
                            value="<?php echo $editing && $url_data->expires ? esc_attr(substr($url_data->expiry_date, 11, 5)) : '23:59'; ?>"
                        >
                    </div>
                    <p class="description">
                        <?php esc_html_e('The URL will stop working after this date and time.', 'short-url'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="short-url-form-section">
            <h3><?php esc_html_e('UTM Parameters', 'short-url'); ?></h3>
            
            <div class="short-url-form-row">
                <div class="short-url-checkbox-field">
                    <input 
                        type="checkbox" 
                        id="short_url_add_utm" 
                        name="short_url_add_utm" 
                        <?php checked(isset($_POST['short_url_add_utm'])); ?>
                    >
                    <label for="short_url_add_utm"><?php esc_html_e('Add UTM Parameters', 'short-url'); ?></label>
                    <p class="description">
                        <?php esc_html_e('Add tracking parameters to the destination URL.', 'short-url'); ?>
                    </p>
                </div>
                
                <div class="short-url-utm-fields" style="display: none;">
                    <div class="short-url-form-row">
                        <label for="short_url_utm_source"><?php esc_html_e('Source', 'short-url'); ?></label>
                        <input 
                            type="text" 
                            id="short_url_utm_source" 
                            name="short_url_utm_source" 
                            placeholder="<?php esc_attr_e('e.g., newsletter', 'short-url'); ?>"
                            value="<?php echo isset($_POST['short_url_utm_source']) ? esc_attr($_POST['short_url_utm_source']) : ''; ?>"
                        >
                        <p class="description"><?php esc_html_e('The referrer: google, newsletter, billboard, etc.', 'short-url'); ?></p>
                    </div>
                    
                    <div class="short-url-form-row">
                        <label for="short_url_utm_medium"><?php esc_html_e('Medium', 'short-url'); ?></label>
                        <input 
                            type="text" 
                            id="short_url_utm_medium" 
                            name="short_url_utm_medium" 
                            placeholder="<?php esc_attr_e('e.g., email', 'short-url'); ?>"
                            value="<?php echo isset($_POST['short_url_utm_medium']) ? esc_attr($_POST['short_url_utm_medium']) : ''; ?>"
                        >
                        <p class="description"><?php esc_html_e('Marketing medium: email, cpc, social, etc.', 'short-url'); ?></p>
                    </div>
                    
                    <div class="short-url-form-row">
                        <label for="short_url_utm_campaign"><?php esc_html_e('Campaign', 'short-url'); ?></label>
                        <input 
                            type="text" 
                            id="short_url_utm_campaign" 
                            name="short_url_utm_campaign" 
                            placeholder="<?php esc_attr_e('e.g., spring_sale', 'short-url'); ?>"
                            value="<?php echo isset($_POST['short_url_utm_campaign']) ? esc_attr($_POST['short_url_utm_campaign']) : ''; ?>"
                        >
                        <p class="description"><?php esc_html_e('The name of your campaign.', 'short-url'); ?></p>
                    </div>
                    
                    <div class="short-url-form-row">
                        <label for="short_url_utm_term"><?php esc_html_e('Term', 'short-url'); ?> (<?php esc_html_e('Optional', 'short-url'); ?>)</label>
                        <input 
                            type="text" 
                            id="short_url_utm_term" 
                            name="short_url_utm_term" 
                            placeholder="<?php esc_attr_e('e.g., running+shoes', 'short-url'); ?>"
                            value="<?php echo isset($_POST['short_url_utm_term']) ? esc_attr($_POST['short_url_utm_term']) : ''; ?>"
                        >
                        <p class="description"><?php esc_html_e('Identify paid keywords.', 'short-url'); ?></p>
                    </div>
                    
                    <div class="short-url-form-row">
                        <label for="short_url_utm_content"><?php esc_html_e('Content', 'short-url'); ?> (<?php esc_html_e('Optional', 'short-url'); ?>)</label>
                        <input 
                            type="text" 
                            id="short_url_utm_content" 
                            name="short_url_utm_content" 
                            placeholder="<?php esc_attr_e('e.g., logolink or textlink', 'short-url'); ?>"
                            value="<?php echo isset($_POST['short_url_utm_content']) ? esc_attr($_POST['short_url_utm_content']) : ''; ?>"
                        >
                        <p class="description"><?php esc_html_e('Use to differentiate ads or links that point to the same URL.', 'short-url'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="short-url-form-submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $editing ? esc_attr__('Update URL', 'short-url') : esc_attr__('Create URL', 'short-url'); ?>">
        </div>
    </form>
</div> 