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
    
    <div class="short-url-stats-cards">
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-links"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Total URLs', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo esc_html(number_format_i18n($summary['total_urls'])); ?></p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Total Clicks', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo esc_html(number_format_i18n($summary['total_clicks'])); ?></p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-performance"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Avg. Clicks per URL', 'short-url'); ?></h3>
                <p class="stat-value">
                    <?php 
                    if ($summary['total_urls'] > 0) {
                        echo esc_html(number_format_i18n($summary['total_clicks'] / $summary['total_urls'], 1));
                    } else {
                        echo '0';
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Last 30 Days', 'short-url'); ?></h3>
                <p class="stat-value">
                    <?php
                    $count_last_30_days = 0;
                    foreach ($summary['clicks_chart']['counts'] as $count) {
                        $count_last_30_days += $count;
                    }
                    echo esc_html(number_format_i18n($count_last_30_days));
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="short-url-chart-container">
        <h3 class="short-url-chart-title"><?php esc_html_e('Clicks Over Time', 'short-url'); ?></h3>
        <div class="short-url-chart">
            <canvas id="short-url-clicks-chart"></canvas>
        </div>
    </div>
    
    <div class="short-url-metric-block">
        <h3 class="short-url-top-urls-title"><?php esc_html_e('Top URLs', 'short-url'); ?></h3>
        
        <?php if (!empty($summary['top_urls'])) : ?>
            <table class="wp-list-table widefat fixed striped">
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
            <p class="short-url-actions">
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
    
    jQuery(document).ready(function($) {
        // Initialize chart
        if(document.getElementById('short-url-clicks-chart')) {
            var ctx = document.getElementById('short-url-clicks-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: shortURLChartData.dates,
                    datasets: [{
                        label: shortURLChartData.label,
                        data: shortURLChartData.counts,
                        backgroundColor: 'rgba(34, 113, 177, 0.2)',
                        borderColor: 'rgba(34, 113, 177, 1)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: 'rgba(34, 113, 177, 1)',
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    });
</script> 