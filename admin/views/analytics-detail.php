<?php
/**
 * Analytics Detail View
 *
 * @package Short_URL
 * @since 1.1.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Ensure we have URL data
if (!isset($url) || !$url) {
    echo '<div class="notice notice-error"><p>' . esc_html__('URL not found.', 'short-url') . '</p></div>';
    return;
}

// Ensure we have analytics data
if (!isset($analytics) || !$analytics) {
    $analytics = array();
}

// Ensure we have summary data
if (!isset($summary) || !$summary) {
    $summary = array(
        'total_clicks' => 0,
        'unique_visitors' => 0,
        'top_country' => '',
        'top_device' => '',
        'top_browser' => '',
        'top_referrer' => '',
        'chart_data' => array(
            'dates' => array(),
            'counts' => array()
        )
    );
}

// Prepare chart data
$chart_data = array(
    'dates' => isset($summary['chart_data']['dates']) ? $summary['chart_data']['dates'] : array(),
    'counts' => isset($summary['chart_data']['counts']) ? $summary['chart_data']['counts'] : array(),
    'label' => __('Clicks', 'short-url')
);
?>

<div class="wrap short-url-container">
    <div class="short-url-header">
        <h1><?php echo esc_html(sprintf(__('Analytics for %s', 'short-url'), Short_URL_Utils::get_domain_from_url($url->slug))); ?></h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-analytics')); ?>" class="page-title-action">
            <?php esc_html_e('Back to Analytics', 'short-url'); ?>
        </a>
    </div>
    
    <div class="short-url-analytics-url-info">
        <p>
            <strong><?php esc_html_e('Short URL:', 'short-url'); ?></strong> 
            <a href="<?php echo esc_url(Short_URL_Generator::get_short_url($url->slug)); ?>" target="_blank">
                <?php echo esc_html(Short_URL_Generator::get_short_url($url->slug)); ?>
            </a>
            <button type="button" class="short-url-copy-button" data-clipboard-text="<?php echo esc_attr(Short_URL_Generator::get_short_url($url->slug)); ?>">
                <span class="dashicons dashicons-clipboard"></span>
            </button>
        </p>
        <p>
            <strong><?php esc_html_e('Destination:', 'short-url'); ?></strong>
            <a href="<?php echo esc_url($url->destination_url); ?>" target="_blank">
                <?php echo esc_html(Short_URL_Utils::truncate($url->destination_url, 50)); ?>
            </a>
        </p>
    </div>
    
    <div class="short-url-date-filter">
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="short-url-analytics">
            <input type="hidden" name="id" value="<?php echo intval($url->id); ?>">
            
            <label for="start_date"><?php esc_html_e('Date Range:', 'short-url'); ?></label>
            <input type="text" id="start_date" name="start_date" class="short-url-datepicker" value="<?php echo isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : ''; ?>" placeholder="<?php esc_attr_e('Start date', 'short-url'); ?>"> -
            <input type="text" id="end_date" name="end_date" class="short-url-datepicker" value="<?php echo isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : ''; ?>" placeholder="<?php esc_attr_e('End date', 'short-url'); ?>">
            
            <button type="submit" class="button"><?php esc_html_e('Filter', 'short-url'); ?></button>
            
            <?php if (isset($_GET['start_date']) || isset($_GET['end_date'])) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-analytics&id=' . intval($url->id))); ?>" class="button">
                    <?php esc_html_e('Reset', 'short-url'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="short-url-stats-cards">
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
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Avg. Clicks / Day', 'short-url'); ?></h3>
                <p class="stat-value">
                    <?php
                    $days = count($chart_data['dates']);
                    if ($days > 0) {
                        echo esc_html(number_format_i18n($summary['total_clicks'] / $days, 1));
                    } else {
                        echo '0';
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Unique Visitors', 'short-url'); ?></h3>
                <p class="stat-value">
                    <?php echo esc_html(number_format_i18n($summary['unique_visitors'])); ?>
                </p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-site"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Top Referrer', 'short-url'); ?></h3>
                <p class="stat-value">
                    <?php 
                    if (!empty($summary['top_referrer'])) {
                        echo esc_html($summary['top_referrer']);
                    } else {
                        esc_html_e('Direct', 'short-url');
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="short-url-chart-container">
        <h3 class="short-url-chart-title"><?php esc_html_e('Clicks Over Time', 'short-url'); ?></h3>
        <?php if (!empty($chart_data['dates']) && !empty($chart_data['counts'])) : ?>
            <div class="short-url-chart">
                <canvas id="short-url-clicks-chart"></canvas>
            </div>
        <?php else : ?>
            <p><?php esc_html_e('No click data available for the selected period.', 'short-url'); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="short-url-metrics-grid">
        <div class="short-url-metric-block">
            <h3 class="short-url-metric-title"><?php esc_html_e('Top Countries', 'short-url'); ?></h3>
            <?php if (!empty($analytics['countries'])) : ?>
                <div class="short-url-chart-container">
                    <canvas id="short-url-countries-chart"></canvas>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Country', 'short-url'); ?></th>
                            <th><?php esc_html_e('Clicks', 'short-url'); ?></th>
                            <th><?php esc_html_e('Percentage', 'short-url'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['countries'] as $country) : ?>
                            <tr>
                                <td><?php echo esc_html($country->country); ?></td>
                                <td><?php echo esc_html(number_format_i18n($country->count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n(($country->count / $summary['total_clicks']) * 100, 1)); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No country data available.', 'short-url'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="short-url-metric-block">
            <h3 class="short-url-metric-title"><?php esc_html_e('Devices', 'short-url'); ?></h3>
            <?php if (!empty($analytics['devices'])) : ?>
                <div class="short-url-chart-container">
                    <canvas id="short-url-devices-chart"></canvas>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Device', 'short-url'); ?></th>
                            <th><?php esc_html_e('Clicks', 'short-url'); ?></th>
                            <th><?php esc_html_e('Percentage', 'short-url'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['devices'] as $device) : ?>
                            <tr>
                                <td><?php echo esc_html($device->device); ?></td>
                                <td><?php echo esc_html(number_format_i18n($device->count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n(($device->count / $summary['total_clicks']) * 100, 1)); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No device data available.', 'short-url'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="short-url-metric-block">
            <h3 class="short-url-metric-title"><?php esc_html_e('Browsers', 'short-url'); ?></h3>
            <?php if (!empty($analytics['browsers'])) : ?>
                <div class="short-url-chart-container">
                    <canvas id="short-url-browsers-chart"></canvas>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Browser', 'short-url'); ?></th>
                            <th><?php esc_html_e('Clicks', 'short-url'); ?></th>
                            <th><?php esc_html_e('Percentage', 'short-url'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['browsers'] as $browser) : ?>
                            <tr>
                                <td><?php echo esc_html($browser->browser); ?></td>
                                <td><?php echo esc_html(number_format_i18n($browser->count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n(($browser->count / $summary['total_clicks']) * 100, 1)); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No browser data available.', 'short-url'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="short-url-metric-block">
            <h3 class="short-url-metric-title"><?php esc_html_e('Referrers', 'short-url'); ?></h3>
            <?php if (!empty($analytics['referrers'])) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Referrer', 'short-url'); ?></th>
                            <th><?php esc_html_e('Clicks', 'short-url'); ?></th>
                            <th><?php esc_html_e('Percentage', 'short-url'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analytics['referrers'] as $referrer) : ?>
                            <tr>
                                <td>
                                    <?php 
                                    if (empty($referrer->referrer)) {
                                        esc_html_e('Direct', 'short-url');
                                    } else {
                                        echo esc_html($referrer->referrer);
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html(number_format_i18n($referrer->count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n(($referrer->count / $summary['total_clicks']) * 100, 1)); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No referrer data available.', 'short-url'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($chart_data['dates']) && !empty($chart_data['counts'])) : ?>
<script type="text/javascript">
    // Chart data
    var shortURLChartData = <?php echo wp_json_encode($chart_data); ?>;
    
    // Analytics data for pie charts
    var shortURLAnalytics = {
        countries: <?php echo !empty($analytics['countries']) ? wp_json_encode(wp_list_pluck($analytics['countries'], 'count', 'country')) : '{}'; ?>,
        devices: <?php echo !empty($analytics['devices']) ? wp_json_encode(wp_list_pluck($analytics['devices'], 'count', 'device')) : '{}'; ?>,
        browsers: <?php echo !empty($analytics['browsers']) ? wp_json_encode(wp_list_pluck($analytics['browsers'], 'count', 'browser')) : '{}'; ?>
    };
    
    jQuery(document).ready(function($) {
        // Initialize line chart for clicks
        if (document.getElementById('short-url-clicks-chart')) {
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
        
        // Initialize pie charts for countries, devices, and browsers
        if (document.getElementById('short-url-countries-chart') && Object.keys(shortURLAnalytics.countries).length) {
            initPieChart('short-url-countries-chart', shortURLAnalytics.countries);
        }
        
        if (document.getElementById('short-url-devices-chart') && Object.keys(shortURLAnalytics.devices).length) {
            initPieChart('short-url-devices-chart', shortURLAnalytics.devices);
        }
        
        if (document.getElementById('short-url-browsers-chart') && Object.keys(shortURLAnalytics.browsers).length) {
            initPieChart('short-url-browsers-chart', shortURLAnalytics.browsers);
        }
        
        // Helper function to initialize pie charts
        function initPieChart(elementId, data) {
            var ctx = document.getElementById(elementId).getContext('2d');
            
            // Generate colors
            var backgroundColors = [];
            var hue = 200; // Start hue (blue)
            Object.keys(data).forEach(function(key, index) {
                // Vary the hue for each segment
                backgroundColors.push('hsl(' + (hue + (index * 25)) + ', 70%, 60%)');
            });
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        data: Object.values(data),
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 15,
                                padding: 15
                            }
                        }
                    }
                }
            });
        }
    });
</script>
<?php endif; ?> 