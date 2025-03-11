<?php
/**
 * Tools page view
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap short-url-tools-page">
    <h1 class="wp-heading-inline"><?php _e('Short URL Tools', 'short-url'); ?></h1>
    
    <?php
    // Display admin notices
    if (isset($_GET['message'])) {
        $message = sanitize_text_field($_GET['message']);
        if ($message === 'import_success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('URLs imported successfully.', 'short-url') . '</p></div>';
        } elseif ($message === 'export_success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('URLs exported successfully.', 'short-url') . '</p></div>';
        } elseif ($message === 'import_error') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Error importing URLs. Please check the file format and try again.', 'short-url') . '</p></div>';
        }
    }
    ?>
    
    <div class="short-url-tools-container">
        <div class="short-url-tool-card">
            <h2><?php _e('Export URLs', 'short-url'); ?></h2>
            <p><?php _e('Export all your short URLs to a CSV file for backup or migration.', 'short-url'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('short_url_export', 'short_url_export_nonce'); ?>
                <input type="hidden" name="action" value="short_url_export">
                
                <div class="short-url-form-group">
                    <label for="export_type"><?php _e('Export Type:', 'short-url'); ?></label>
                    <select name="export_type" id="export_type">
                        <option value="all"><?php _e('All URLs', 'short-url'); ?></option>
                        <option value="active"><?php _e('Active URLs Only', 'short-url'); ?></option>
                        <option value="inactive"><?php _e('Inactive URLs Only', 'short-url'); ?></option>
                    </select>
                </div>
                
                <div class="short-url-form-group">
                    <label for="include_analytics"><?php _e('Include Analytics:', 'short-url'); ?></label>
                    <input type="checkbox" name="include_analytics" id="include_analytics" value="1">
                    <span class="description"><?php _e('Include visit data in the export', 'short-url'); ?></span>
                </div>
                
                <div class="short-url-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Export to CSV', 'short-url'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="short-url-tool-card">
            <h2><?php _e('Import URLs', 'short-url'); ?></h2>
            <p><?php _e('Import short URLs from a CSV file. The file should have the following columns: slug, destination_url, title, group_id, status, password, expiry_date.', 'short-url'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('short_url_import', 'short_url_import_nonce'); ?>
                <input type="hidden" name="action" value="short_url_import">
                
                <div class="short-url-form-group">
                    <label for="import_file"><?php _e('CSV File:', 'short-url'); ?></label>
                    <input type="file" name="import_file" id="import_file" accept=".csv" required>
                </div>
                
                <div class="short-url-form-group">
                    <label for="import_options"><?php _e('Import Options:', 'short-url'); ?></label>
                    <select name="import_options" id="import_options">
                        <option value="skip"><?php _e('Skip existing URLs', 'short-url'); ?></option>
                        <option value="update"><?php _e('Update existing URLs', 'short-url'); ?></option>
                    </select>
                </div>
                
                <div class="short-url-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Import from CSV', 'short-url'); ?></button>
                </div>
            </form>
        </div>
        
        <div class="short-url-tool-card">
            <h2><?php _e('Database Cleanup', 'short-url'); ?></h2>
            <p><?php _e('Clean up expired URLs and optimize the database tables.', 'short-url'); ?></p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('short_url_cleanup', 'short_url_cleanup_nonce'); ?>
                <input type="hidden" name="action" value="short_url_cleanup">
                
                <div class="short-url-form-group">
                    <label>
                        <input type="checkbox" name="cleanup_expired" value="1" checked>
                        <?php _e('Remove expired URLs', 'short-url'); ?>
                    </label>
                </div>
                
                <div class="short-url-form-group">
                    <label>
                        <input type="checkbox" name="cleanup_analytics" value="1">
                        <?php _e('Clean up old analytics data (older than 1 year)', 'short-url'); ?>
                    </label>
                </div>
                
                <div class="short-url-form-group">
                    <label>
                        <input type="checkbox" name="optimize_tables" value="1" checked>
                        <?php _e('Optimize database tables', 'short-url'); ?>
                    </label>
                </div>
                
                <div class="short-url-form-actions">
                    <button type="submit" class="button button-primary"><?php _e('Run Cleanup', 'short-url'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.short-url-tools-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-top: 20px;
}

.short-url-tool-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
    border-radius: 4px;
    flex: 1 1 300px;
    max-width: 500px;
}

.short-url-tool-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.short-url-form-group {
    margin-bottom: 15px;
}

.short-url-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.short-url-form-actions {
    margin-top: 20px;
}
</style> 