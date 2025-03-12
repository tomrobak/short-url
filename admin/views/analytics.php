<?php
/**
 * Admin Analytics Page
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get URL ID and data
$url_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$date_range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '30days';
$db = new Short_URL_DB();

// If URL ID is provided, show specific analytics
if ($url_id) {
    $url_data = $db->get_url($url_id);
    if (!$url_data) {
        wp_die(__('URL not found.', 'short-url'));
    }
    
    // Get analytics data
    $analytics = $db->get_url_analytics($url_id, $date_range);
    
    // Check if there is any data
    $has_data = !empty($analytics['visits_by_day']);
} else {
    // Global analytics
    $analytics = $db->get_global_analytics($date_range);
    $has_data = !empty($analytics['visits_by_day']);
}

// Setup chart data
$chart_data = array(
    'labels' => array_keys($analytics['visits_by_day']),
    'datasets' => array(
        array(
            'label' => __('Clicks', 'short-url'),
            'data' => array_values($analytics['visits_by_day']),
            'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
            'borderColor' => 'rgba(54, 162, 235, 1)',
            'borderWidth' => 1
        )
    )
);
?>

<div class="wrap short-url-container short-url-analytics">
    <div class="short-url-header">
        <h1 class="wp-heading-inline">
            <?php 
            if ($url_id) {
                printf(
                    /* translators: %s: short URL */
                    esc_html__('Analytics for %s', 'short-url'), 
                    '<code>' . esc_html($url_data->short_url) . '</code>'
                );
            } else {
                esc_html_e('Global Analytics', 'short-url');
            }
            ?>
        </h1>
        
        <?php if ($url_id) : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=short-url-analytics')); ?>" class="page-title-action">
                <?php esc_html_e('Global Analytics', 'short-url'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Date range filter -->
    <div class="short-url-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="short-url-analytics" />
            <?php if ($url_id) : ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($url_id); ?>" />
            <?php endif; ?>
            
            <select name="range" id="range">
                <option value="7days" <?php selected($date_range, '7days'); ?>>
                    <?php esc_html_e('Last 7 Days', 'short-url'); ?>
                </option>
                <option value="30days" <?php selected($date_range, '30days'); ?>>
                    <?php esc_html_e('Last 30 Days', 'short-url'); ?>
                </option>
                <option value="90days" <?php selected($date_range, '90days'); ?>>
                    <?php esc_html_e('Last 90 Days', 'short-url'); ?>
                </option>
                <option value="year" <?php selected($date_range, 'year'); ?>>
                    <?php esc_html_e('Last Year', 'short-url'); ?>
                </option>
                <option value="all" <?php selected($date_range, 'all'); ?>>
                    <?php esc_html_e('All Time', 'short-url'); ?>
                </option>
            </select>
            
            <button type="submit" class="button"><?php esc_html_e('Apply', 'short-url'); ?></button>
        </form>
    </div>
    
    <!-- Statistics cards -->
    <div class="short-url-stats-cards">
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Total Clicks', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo isset($analytics['total_clicks']) ? number_format((int)$analytics['total_clicks']) : '0'; ?></p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-calendar"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Average Per Day', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo isset($analytics['avg_clicks_per_day']) ? number_format((float)$analytics['avg_clicks_per_day'], 1) : '0.0'; ?></p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Unique Visitors', 'short-url'); ?></h3>
                <p class="stat-value"><?php echo isset($analytics['unique_visitors']) ? number_format((int)$analytics['unique_visitors']) : '0'; ?></p>
            </div>
        </div>
        
        <div class="short-url-stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-site"></span>
            </div>
            <div class="stat-content">
                <h3><?php esc_html_e('Top Referrer', 'short-url'); ?></h3>
                <p class="stat-value">
                    <?php echo !empty($analytics['top_referrer']) ? esc_html($analytics['top_referrer']) : esc_html__('Direct', 'short-url'); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Chart -->
    <div class="short-url-chart-container">
        <h2><?php esc_html_e('Clicks Over Time', 'short-url'); ?></h2>
        <?php if ($has_data) : ?>
            <canvas id="clicksChart" width="400" height="100"></canvas>
            <script type="text/javascript">
                // Define chart data for the clicks chart
                var chartData = <?php echo json_encode($chart_data); ?>;
            </script>
        <?php else : ?>
            <div class="short-url-no-data">
                <p><?php esc_html_e('No analytics data available for the selected period.', 'short-url'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Top Countries -->
    <?php if (!empty($analytics['countries']) && is_array($analytics['countries'])) : ?>
    <div class="short-url-metric-block">
        <h2><?php esc_html_e('Top Countries', 'short-url'); ?></h2>
        <div class="short-url-metrics-grid">
            <?php foreach ($analytics['countries'] as $country_code => $clicks) : ?>
                <div class="short-url-metric-item">
                    <span class="country-flag"><?php echo Short_URL_Utils::get_country_flag($country_code); ?></span>
                    <span class="country-name"><?php echo esc_html($country_code); ?></span>
                    <span class="metric-value"><?php echo number_format($clicks); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Devices -->
    <?php if (!empty($analytics['devices']) && is_array($analytics['devices'])) : ?>
    <div class="short-url-metric-block">
        <h2><?php esc_html_e('Devices', 'short-url'); ?></h2>
        <div class="short-url-metrics-grid">
            <?php foreach ($analytics['devices'] as $device => $clicks) : ?>
                <div class="short-url-metric-item">
                    <?php echo Short_URL_Utils::get_device_icon($device); ?>
                    <span class="metric-name"><?php echo esc_html($device); ?></span>
                    <span class="metric-value"><?php echo number_format($clicks); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Browsers -->
    <?php if (!empty($analytics['browsers']) && is_array($analytics['browsers'])) : ?>
    <div class="short-url-metric-block">
        <h2><?php esc_html_e('Browsers', 'short-url'); ?></h2>
        <div class="short-url-metrics-grid">
            <?php foreach ($analytics['browsers'] as $browser => $clicks) : ?>
                <div class="short-url-metric-item">
                    <?php echo Short_URL_Utils::get_browser_icon($browser); ?>
                    <span class="metric-name"><?php echo esc_html($browser); ?></span>
                    <span class="metric-value"><?php echo number_format($clicks); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Referrers -->
    <?php if (!empty($analytics['referrers']) && is_array($analytics['referrers'])) : ?>
    <div class="short-url-metric-block">
        <h2><?php esc_html_e('Top Referrers', 'short-url'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Referrer', 'short-url'); ?></th>
                    <th><?php esc_html_e('Clicks', 'short-url'); ?></th>
                    <th><?php esc_html_e('Percentage', 'short-url'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($analytics['referrers'] as $referrer => $data) : ?>
                    <tr>
                        <td>
                            <?php 
                            if (empty($referrer) || $referrer === 'direct') {
                                echo esc_html__('Direct / None', 'short-url');
                            } else {
                                echo esc_html($referrer);
                            }
                            ?>
                        </td>
                        <td><?php echo number_format($data['count']); ?></td>
                        <td>
                            <div class="short-url-progress-bar">
                                <div class="progress" style="width: <?php echo esc_attr($data['percentage']); ?>%"></div>
                                <span><?php echo esc_html(round($data['percentage'], 1)); ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if ($url_id) : ?>
    <!-- Recent Clicks -->
    <div class="short-url-metric-block">
        <h2><?php esc_html_e('Recent Clicks', 'short-url'); ?></h2>
        <?php if (!empty($analytics['recent_clicks'])) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'short-url'); ?></th>
                        <th><?php esc_html_e('IP Address', 'short-url'); ?></th>
                        <th><?php esc_html_e('Referrer', 'short-url'); ?></th>
                        <th><?php esc_html_e('Device', 'short-url'); ?></th>
                        <th><?php esc_html_e('Browser', 'short-url'); ?></th>
                        <th><?php esc_html_e('OS', 'short-url'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['recent_clicks'] as $click) : ?>
                        <tr>
                            <td><?php echo Short_URL_Utils::format_date($click->created_at, true, true); ?></td>
                            <td><?php echo esc_html($click->ip_address); ?></td>
                            <td>
                                <?php 
                                if (empty($click->referrer)) {
                                    echo esc_html__('Direct / None', 'short-url');
                                } else {
                                    echo esc_html($click->referrer);
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo Short_URL_Utils::get_device_icon($click->device_type); ?>
                                <?php echo esc_html($click->device_type); ?>
                            </td>
                            <td>
                                <?php echo Short_URL_Utils::get_browser_icon($click->browser); ?>
                                <?php echo esc_html($click->browser); ?>
                            </td>
                            <td>
                                <?php echo Short_URL_Utils::get_os_icon($click->os); ?>
                                <?php echo esc_html($click->os); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="short-url-no-data">
                <p><?php esc_html_e('No recent clicks available.', 'short-url'); ?></p>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Initialize charts if data is available
        <?php if ($has_data) : ?>
        var ctx = document.getElementById('clicksChart').getContext('2d');
        var chartData = <?php echo json_encode($chart_data); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: chartData,
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
        <?php endif; ?>
        
        // Change date range on select change
        $('#range').on('change', function() {
            $(this).closest('form').submit();
        });
    });
</script> 