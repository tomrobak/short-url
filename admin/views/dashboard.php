<?php
/**
 * Admin Dashboard Page
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
        <h1><?php esc_html_e('Short URL Dashboard', 'short-url'); ?></h1>
        <span class="short-url-version"><?php echo esc_html(sprintf(__('Version %s', 'short-url'), SHORT_URL_VERSION)); ?></span>
    </div>
    
    <div class="short-url-cards">
        <div class="short-url-card">
            <h3 class="short-url-card-title"><?php esc_html_e('Total URLs', 'short-url'); ?></h3>
            <p class="short-url-card-value"><?php echo esc_html(number_format_i18n($summary['total_urls'])); ?></p>
            <span class="short-url-card-icon dashicons dashicons-admin-links"></span>
        </div>
        
        <div class="short-url-card">
            <h3 class="short-url-card-title"><?php esc_html_e('Total Clicks', 'short-url'); ?></h3>
            <p class="short-url-card-value"><?php echo esc_html(number_format_i18n($summary['total_clicks'])); ?></p>
            <span class="short-url-card-icon dashicons dashicons-chart-bar"></span>
        </div>
        
        <div class="short-url-card">
            <h3 class="short-url-card-title"><?php esc_html_e('Avg. Clicks per URL', 'short-url'); ?></h3>
            <p class="short-url-card-value">
                <?php 
                if ($summary['total_urls'] > 0) {
                    echo esc_html(number_format_i18n($summary['total_clicks'] / $summary['total_urls'], 1));
                } else {
                    echo '0';
                }
                ?>
            </p>
            <span class="short-url-card-icon dashicons dashicons-performance"></span>
        </div>
        
        <div class="short-url-card">
            <h3 class="short-url-card-title"><?php esc_html_e('Last 30 Days', 'short-url'); ?></h3>
            <p class="short-url-card-value">
                <?php
                $count_last_30_days = 0;
                foreach ($summary['clicks_chart']['counts'] as $count) {
                    $count_last_30_days += $count;
                }
                echo esc_html(number_format_i18n($count_last_30_days));
                ?>
            </p>
            <span class="short-url-card-icon dashicons dashicons-calendar-alt"></span>
        </div>
    </div>
    
    <div class="short-url-chart-container">
        <h3 class="short-url-chart-title"><?php esc_html_e('Clicks Over Time', 'short-url'); ?></h3>
        <div class="short-url-chart">
            <canvas id="short-url-clicks-chart"></canvas>
        </div>
    </div>
    
    <div class="short-url-top-urls">
        <h3 class="short-url-top-urls-title"><?php esc_html_e('Top URLs', 'short-url'); ?></h3>
        
        <?php if (!empty($summary['top_urls'])) : ?>
            <table class="short-url-top-urls-list">
                <thead>
                    <tr>
                        <th><?php esc_html_e('URL', 'short-url'); ?></th>
                        <th><?php esc_html_e('Destination', 'short-url'); ?></th>
                        <th><?php esc_html_e('Clicks', 'short-url'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary['top_urls'] as $url) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(Short_URL_Generator::get_short_url($url->slug)); ?>" target="_blank" class="short-url-top-urls-slug">
                                    <?php echo esc_html(Short_URL_Generator::get_short_url($url->slug)); ?>
                                </a>
                                <?php if (current_user_can('view_short_url_analytics')) : ?>
                                    <div class="row-actions">
                                        <span class="stats">
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-analytics&id=' . $url->id)); ?>">
                                                <?php esc_html_e('View Stats', 'short-url'); ?>
                                            </a>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html(Short_URL_Utils::truncate($url->destination_url, 40)); ?>
                            </td>
                            <td class="short-url-top-urls-clicks">
                                <?php echo esc_html(number_format_i18n($url->clicks)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No URLs have been clicked yet.', 'short-url'); ?></p>
        <?php endif; ?>
        
        <?php if (current_user_can('manage_short_urls')) : ?>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-urls')); ?>" class="button">
                    <?php esc_html_e('View All URLs', 'short-url'); ?>
                </a>
                
                <?php if (current_user_can('view_short_url_analytics')) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-analytics')); ?>" class="button">
                        <?php esc_html_e('Detailed Analytics', 'short-url'); ?>
                    </a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
// Prepare chart data for JavaScript
$chart_data = array(
    'dates' => $summary['clicks_chart']['dates'],
    'counts' => $summary['clicks_chart']['counts'],
    'label' => __('Clicks', 'short-url'),
);
?>

<script type="text/javascript">
    // Chart data
    var shortURLChartData = <?php echo wp_json_encode($chart_data); ?>;
</script> 