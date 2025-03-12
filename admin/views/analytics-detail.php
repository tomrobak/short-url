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
    $analytics = array(
        'items' => array(),
        'total' => 0,
        'total_pages' => 0,
        'page' => 1,
        'per_page' => 20
    );
}

// Ensure we have summary data
if (!isset($summary) || !$summary) {
    $summary = array(
        'total_visits' => 0,
        'top_countries' => array(),
        'top_browsers' => array(),
        'top_devices' => array(),
        'top_referrers' => array(),
        'visits_by_day' => array()
    );
}

// Process summary data for the view
$total_clicks = isset($summary['total_visits']) ? $summary['total_visits'] : 0;
$unique_visitors = isset($summary['unique_visitors']) ? $summary['unique_visitors'] : 0;

// Process top data
$top_country = '';
if (!empty($summary['top_countries']) && is_array($summary['top_countries'])) {
    $top_country = isset($summary['top_countries'][0]->country_name) ? $summary['top_countries'][0]->country_name : '';
}

$top_browser = '';
if (!empty($summary['top_browsers']) && is_array($summary['top_browsers'])) {
    $top_browser = isset($summary['top_browsers'][0]->browser) ? $summary['top_browsers'][0]->browser : '';
}

$top_device = '';
if (!empty($summary['top_devices']) && is_array($summary['top_devices'])) {
    $top_device = isset($summary['top_devices'][0]->device_type) ? $summary['top_devices'][0]->device_type : '';
}

$top_referrer = '';
if (!empty($summary['top_referrers']) && is_array($summary['top_referrers'])) {
    $top_referrer = isset($summary['top_referrers'][0]->referrer_url) ? $summary['top_referrers'][0]->referrer_url : '';
}

// Prepare chart data
$chart_data = array(
    'dates' => array(),
    'counts' => array(),
    'label' => __('Clicks', 'short-url')
);

if (!empty($summary['visits_by_day']) && is_array($summary['visits_by_day'])) {
    foreach ($summary['visits_by_day'] as $day) {
        if (isset($day->date) && isset($day->count)) {
            $chart_data['dates'][] = $day->date;
            $chart_data['counts'][] = (int) $day->count;
        }
    }
}

// Process analytics data for display
$processed_analytics = array(
    'browsers' => array(),
    'devices' => array(),
    'countries' => array(),
    'referrers' => array()
);

// Process browsers data
if (!empty($summary['top_browsers']) && is_array($summary['top_browsers'])) {
    $processed_analytics['browsers'] = $summary['top_browsers'];
}

// Process devices data
if (!empty($summary['top_devices']) && is_array($summary['top_devices'])) {
    $processed_analytics['devices'] = $summary['top_devices'];
}

// Process countries data
if (!empty($summary['top_countries']) && is_array($summary['top_countries'])) {
    $processed_analytics['countries'] = $summary['top_countries'];
}

// Process referrers data
if (!empty($summary['top_referrers']) && is_array($summary['top_referrers'])) {
    $processed_analytics['referrers'] = $summary['top_referrers'];
}

// Prepare analytics data for JavaScript
$analytics_js_data = array(
    'browsers' => array(),
    'devices' => array(),
    'countries' => array()
);

// Process browser data for charts
if (!empty($processed_analytics['browsers'])) {
    foreach ($processed_analytics['browsers'] as $browser) {
        if (isset($browser->browser) && isset($browser->count)) {
            $analytics_js_data['browsers'][$browser->browser] = (int) $browser->count;
        }
    }
}

// Process device data for charts
if (!empty($processed_analytics['devices'])) {
    foreach ($processed_analytics['devices'] as $device) {
        if (isset($device->device_type) && isset($device->count)) {
            $analytics_js_data['devices'][$device->device_type] = (int) $device->count;
        }
    }
}

// Process country data for charts
if (!empty($processed_analytics['countries'])) {
    foreach ($processed_analytics['countries'] as $country) {
        if (isset($country->country_name) && isset($country->count)) {
            $analytics_js_data['countries'][$country->country_name] = (int) $country->count;
        }
    }
}
?>

<div class="wrap short-url-container">
    <div class="short-url-header">
        <h1 class="wp-heading-inline">
            <?php 
            printf(
                /* translators: %s: short URL slug */
                esc_html__('Analytics for %s', 'short-url'), 
                '<code>' . esc_html($url->slug) . '</code>'
            ); 
            ?>
        </h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-urls')); ?>" class="page-title-action"><?php esc_html_e('Back to URLs', 'short-url'); ?></a>
    </div>
    
    <!-- Stats Cards -->
    <div class="short-url-stats-cards">
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Total Clicks', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo number_format_i18n($total_clicks); ?></p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Avg. Clicks/Day', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo number_format_i18n($avg_clicks_per_day, 1); ?></p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Unique Visitors', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo number_format_i18n($unique_visitors); ?></p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-site"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Top Referrer', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo !empty($top_referrer) ? esc_html($top_referrer) : esc_html__('Direct', 'short-url'); ?></p>
            </div>
        </div>
    </div>
    
    <div class="short-url-url-info">
        <h2><?php esc_html_e('URL Information', 'short-url'); ?></h2>
        <div class="short-url-url-details">
            <div class="short-url-detail">
                <span class="detail-label"><?php esc_html_e('Short URL:', 'short-url'); ?></span>
                <span class="detail-value">
                    <a href="<?php echo esc_url(Short_URL_Generator::get_short_url($url->slug)); ?>" target="_blank">
                        <?php echo esc_html(Short_URL_Generator::get_short_url($url->slug)); ?>
                    </a>
                    <button type="button" class="short-url-copy-button" data-clipboard-text="<?php echo esc_attr(Short_URL_Generator::get_short_url($url->slug)); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </span>
            </div>
            
            <div class="short-url-detail">
                <span class="detail-label"><?php esc_html_e('Destination URL:', 'short-url'); ?></span>
                <span class="detail-value">
                    <a href="<?php echo esc_url($url->destination_url); ?>" target="_blank">
                        <?php echo esc_html($url->destination_url); ?>
                    </a>
                </span>
            </div>
            
            <div class="short-url-detail">
                <span class="detail-label"><?php esc_html_e('Created:', 'short-url'); ?></span>
                <span class="detail-value">
                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($url->created_at))); ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="short-url-chart-container">
        <h3 class="short-url-chart-title"><?php esc_html_e('Clicks Over Time', 'short-url'); ?></h3>
        <canvas id="short-url-visits-chart"></canvas>
    </div>
    
    <div class="short-url-metrics-grid">
        <div class="short-url-metric-block">
            <h3 class="short-url-metric-title"><?php esc_html_e('Countries', 'short-url'); ?></h3>
            <?php if (!empty($processed_analytics['countries'])) : ?>
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
                        <?php foreach ($processed_analytics['countries'] as $country) : ?>
                            <tr>
                                <td>
                                    <?php if (!empty($country->country_code)) : ?>
                                        <span class="country-flag-placeholder" data-country="<?php echo esc_attr(strtolower($country->country_code)); ?>"></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($country->country_name ?? esc_html__('Unknown', 'short-url')); ?>
                                </td>
                                <td><?php echo esc_html(number_format_i18n($country->count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n(($country->count / $total_clicks) * 100, 1)); ?>%</td>
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
            <?php if (!empty($processed_analytics['devices'])) : ?>
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
                        <?php foreach ($processed_analytics['devices'] as $device) : ?>
                            <tr>
                                <td><?php echo esc_html($device->device_type ?? esc_html__('Unknown', 'short-url')); ?></td>
                                <td><?php echo esc_html(number_format_i18n($device->count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n(($device->count / $total_clicks) * 100, 1)); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No device data available.', 'short-url'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="short-url-metrics-grid">
        <div class="short-url-metric-block">
            <h3 class="short-url-metric-title"><?php esc_html_e('Browsers', 'short-url'); ?></h3>
            <?php if (!empty($processed_analytics['browsers'])) : ?>
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
                        <?php foreach ($processed_analytics['browsers'] as $browser) : ?>
                            <tr>
                                <td><?php echo esc_html($browser->browser ?? esc_html__('Unknown', 'short-url')); ?></td>
                                <td><?php echo esc_html(number_format_i18n($browser->count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n(($browser->count / $total_clicks) * 100, 1)); ?>%</td>
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
            <?php if (!empty($processed_analytics['referrers'])) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Referrer', 'short-url'); ?></th>
                            <th><?php esc_html_e('Clicks', 'short-url'); ?></th>
                            <th><?php esc_html_e('Percentage', 'short-url'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processed_analytics['referrers'] as $referrer) : ?>
                            <tr>
                                <td><?php echo !empty($referrer->referrer) ? esc_html($referrer->referrer) : esc_html__('Direct', 'short-url'); ?></td>
                                <td><?php echo esc_html(number_format_i18n($referrer->count)); ?></td>
                                <td><?php echo esc_html(number_format_i18n(($referrer->count / $total_clicks) * 100, 1)); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No referrer data available.', 'short-url'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="short-url-recent-clicks">
        <h2><?php esc_html_e('Recent Clicks', 'short-url'); ?></h2>
        <?php if (!empty($analytics['items'])) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'short-url'); ?></th>
                        <th><?php esc_html_e('IP Address', 'short-url'); ?></th>
                        <th><?php esc_html_e('Country', 'short-url'); ?></th>
                        <th><?php esc_html_e('Browser', 'short-url'); ?></th>
                        <th><?php esc_html_e('Device', 'short-url'); ?></th>
                        <th><?php esc_html_e('Referrer', 'short-url'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['items'] as $item) : ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->visited_at))); ?></td>
                            <td><?php echo isset($item->ip_address) ? esc_html($item->ip_address) : esc_html__('Unknown', 'short-url'); ?></td>
                            <td>
                                <?php if (!empty($item->country_code)) : ?>
                                    <img src="<?php echo esc_url(SHORT_URL_PLUGIN_URL . 'admin/images/flags/' . strtolower($item->country_code) . '.png'); ?>" 
                                         alt="<?php echo esc_attr($item->country_name); ?>" 
                                         class="short-url-flag" />
                                    <?php echo esc_html($item->country_name); ?>
                                <?php else : ?>
                                    <?php esc_html_e('Unknown', 'short-url'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($item->browser) ? esc_html($item->browser) : esc_html__('Unknown', 'short-url'); ?></td>
                            <td><?php echo !empty($item->device_type) ? esc_html($item->device_type) : esc_html__('Unknown', 'short-url'); ?></td>
                            <td>
                                <?php 
                                if (empty($item->referrer_url)) {
                                    esc_html_e('Direct', 'short-url');
                                } else {
                                    echo esc_html($item->referrer_url);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($analytics['total_pages'] > 1) : ?>
                <div class="short-url-pagination">
                    <?php
                    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                    $base_url = add_query_arg(array('page' => 'short-url-analytics', 'id' => $url->id), admin_url('admin.php'));
                    
                    // Previous page
                    if ($current_page > 1) {
                        $prev_url = add_query_arg('paged', $current_page - 1, $base_url);
                        echo '<a href="' . esc_url($prev_url) . '" class="button">&laquo; ' . esc_html__('Previous', 'short-url') . '</a>';
                    }
                    
                    // Page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($analytics['total_pages'], $current_page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $page_url = add_query_arg('paged', $i, $base_url);
                        $class = $i === $current_page ? 'button button-primary' : 'button';
                        echo '<a href="' . esc_url($page_url) . '" class="' . esc_attr($class) . '">' . esc_html($i) . '</a>';
                    }
                    
                    // Next page
                    if ($current_page < $analytics['total_pages']) {
                        $next_url = add_query_arg('paged', $current_page + 1, $base_url);
                        echo '<a href="' . esc_url($next_url) . '" class="button">' . esc_html__('Next', 'short-url') . ' &raquo;</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <p><?php esc_html_e('No click data available.', 'short-url'); ?></p>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
    // Prepare chart data for JavaScript with proper error checking
    var shortURLChartData = <?php echo !empty($chart_data) ? json_encode($chart_data) : '{"dates":[],"counts":[],"label":"Clicks"}'; ?>;
    var shortURLAnalyticsData = <?php echo !empty($analytics_js_data) ? json_encode($analytics_js_data) : '{"browsers":{},"devices":{},"countries":{}}'; ?>;
</script>

<script>
    jQuery(document).ready(function($) {
        // Initialize charts
        var visitsChartCtx = document.getElementById('short-url-visits-chart').getContext('2d');
        var visitsChart = new Chart(visitsChartCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($visits_by_day)); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Clicks', 'short-url'); ?>',
                    data: <?php echo json_encode(array_values($visits_by_day)); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    tension: 0.4
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
        
        // Initialize countries chart if data exists
        <?php if (!empty($analytics_js_data['countries'])) : ?>
            var countriesChartCtx = document.getElementById('short-url-countries-chart').getContext('2d');
            var countriesChart = new Chart(countriesChartCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($analytics_js_data['countries'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($analytics_js_data['countries'])); ?>,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(199, 199, 199, 0.8)',
                            'rgba(83, 102, 255, 0.8)',
                            'rgba(40, 159, 64, 0.8)',
                            'rgba(143, 92, 255, 0.8)'
                        ],
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
                                boxWidth: 15
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
        
        // Initialize devices chart if data exists
        <?php if (!empty($analytics_js_data['devices'])) : ?>
            var devicesChartCtx = document.getElementById('short-url-devices-chart').getContext('2d');
            var devicesChart = new Chart(devicesChartCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($analytics_js_data['devices'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($analytics_js_data['devices'])); ?>,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)'
                        ],
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
                                boxWidth: 15
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
        
        // Initialize browsers chart if data exists
        <?php if (!empty($analytics_js_data['browsers'])) : ?>
            var browsersChartCtx = document.getElementById('short-url-browsers-chart').getContext('2d');
            var browsersChart = new Chart(browsersChartCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_keys($analytics_js_data['browsers'])); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_values($analytics_js_data['browsers'])); ?>,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(75, 192, 192, 0.8)',
                            'rgba(153, 102, 255, 0.8)',
                            'rgba(255, 159, 64, 0.8)',
                            'rgba(199, 199, 199, 0.8)'
                        ],
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
                                boxWidth: 15
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
        
        // Replace country flag placeholders with flag images
        $('.country-flag-placeholder').each(function() {
            var countryCode = $(this).data('country');
            if (countryCode) {
                // Use Flagpedia.net for flag images - a reliable external source
                var flagUrl = 'https://flagcdn.com/w20/' + countryCode.toLowerCase() + '.png';
                $(this).replaceWith('<img src="' + flagUrl + '" class="country-flag" alt="' + countryCode + '">');
            }
        });
    });
</script> 