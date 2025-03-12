/**
 * Short URL Admin JavaScript
 *
 * @package Short_URL
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initialize clipboard functionality
        initializeClipboard();
        
        // Initialize datepicker fields
        initializeDatepicker();
        
        // Initialize URL creation form
        initializeUrlForm();
        
        // Initialize URL table functionality
        initializeUrlTable();
        
        // Initialize analytics page
        initializeAnalytics();
        
        // Initialize charts
        initCharts();
    });

    /**
     * Initialize clipboard functionality
     */
    function initializeClipboard() {
        if (typeof ClipboardJS !== 'undefined') {
            var clipboard = new ClipboardJS('.short-url-copy-button');
            
            clipboard.on('success', function(e) {
                var $button = $(e.trigger);
                
                // Show success feedback
                $button.addClass('copied');
                
                // Add tooltip if it doesn't exist
                if (!$button.find('.short-url-tooltip').length) {
                    $button.append('<span class="short-url-tooltip">Copied!</span>');
                }
                
                // Remove after delay
                setTimeout(function() {
                    $button.removeClass('copied');
                    $button.find('.short-url-tooltip').remove();
                }, 2000);
                
                e.clearSelection();
            });
        }
    }

    /**
     * Initialize datepicker fields
     */
    function initializeDatepicker() {
        if ($.fn.datepicker) {
            $('.short-url-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                minDate: 0
            });

            // Set default time on the date-time fields
            $('.short-url-datepicker').on('change', function() {
                var $timeField = $(this).siblings('.short-url-time');
                if ($timeField.length > 0 && $timeField.val() === '') {
                    $timeField.val('23:59');
                }
            });
        }
    }

    /**
     * Initialize URL form
     */
    function initializeUrlForm() {
        // Custom slug toggle
        $('#short_url_use_custom_slug').on('change', function() {
            if ($(this).is(':checked')) {
                $('.short-url-custom-slug-field').slideDown(200);
            } else {
                $('.short-url-custom-slug-field').slideUp(200);
            }
        }).trigger('change');

        // Password protection toggle
        $('#short_url_password_protected').on('change', function() {
            if ($(this).is(':checked')) {
                $('.short-url-password-field').slideDown(200);
            } else {
                $('.short-url-password-field').slideUp(200);
            }
        }).trigger('change');

        // Expiration toggle
        $('#short_url_expires').on('change', function() {
            if ($(this).is(':checked')) {
                $('.short-url-expiration-fields').slideDown(200);
            } else {
                $('.short-url-expiration-fields').slideUp(200);
            }
        }).trigger('change');

        // UTM parameters toggle
        $('#short_url_add_utm').on('change', function() {
            if ($(this).is(':checked')) {
                $('.short-url-utm-fields').slideDown(200);
            } else {
                $('.short-url-utm-fields').slideUp(200);
            }
        }).trigger('change');

        // Slug generator
        $('#short_url_generate_slug').on('click', function(e) {
            e.preventDefault();
            var $slugField = $('#short_url_custom_slug');
            
            // Get the default length from the data attribute
            var length = $(this).data('length') || 6;
            
            // Show loading state
            $(this).prop('disabled', true).text('Generating...');
            
            // Make an AJAX request to generate a slug
            $.ajax({
                url: shortURLAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'short_url_generate_slug',
                    length: length,
                    nonce: shortURLAdmin.adminNonce
                },
                success: function(response) {
                    if (response.success && response.data.slug) {
                        $slugField.val(response.data.slug);
                    }
                },
                complete: function() {
                    // Restore button state
                    $('#short_url_generate_slug').prop('disabled', false).text('Generate');
                }
            });
        });

        // Form validation
        $('.short-url-add-form').on('submit', function(e) {
            var $destinationUrl = $('#destination_url');
            
            // Basic URL validation
            if ($destinationUrl.val().trim() === '') {
                e.preventDefault();
                alert('Please enter a destination URL');
                $destinationUrl.focus();
                return false;
            }
            
            // Check custom slug pattern if enabled
            if ($('#short_url_use_custom_slug').is(':checked')) {
                var $customSlug = $('#short_url_custom_slug');
                var slug = $customSlug.val().trim();
                
                if (slug !== '' && !/^[a-zA-Z0-9-]+$/.test(slug)) {
                    e.preventDefault();
                    alert('Custom slug can only contain letters, numbers, and hyphens');
                    $customSlug.focus();
                    return false;
                }
            }
            
            // Check password if enabled
            if ($('#short_url_password_protected').is(':checked') && $('#short_url_password').val().trim() === '') {
                e.preventDefault();
                alert('Please enter a password or disable password protection');
                $('#short_url_password').focus();
                return false;
            }
            
            return true;
        });
    }

    /**
     * Initialize URL table functionality
     */
    function initializeUrlTable() {
        // Bulk action confirmation
        $('.short-url-list-table-form').on('submit', function() {
            var action = $(this).find('select[name="action"]').val() || 
                         $(this).find('select[name="action2"]').val();
            
            if (action === 'delete') {
                return confirm('Are you sure you want to delete these URLs? This action cannot be undone.');
            }
        });
        
        // Delete link confirmation
        $('.short-url-delete').on('click', function() {
            return confirm('Are you sure you want to delete this URL? This action cannot be undone.');
        });
        
        // Quick edit functionality
        $('.short-url-quick-edit').on('click', function(e) {
            e.preventDefault();
            
            var $row = $(this).closest('tr');
            var urlId = $(this).data('id');
            
            // Only proceed if we're not already editing
            if ($row.next('.short-url-quick-edit-row').length === 0) {
                // Get URL data
                $.ajax({
                    url: shortURLAdmin.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'short_url_get_url_data',
                        url_id: urlId,
                        nonce: shortURLAdmin.adminNonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            // Insert quick edit form
                            $row.after(buildQuickEditRow(response.data, $row));
                            
                            // Initialize the form elements
                            initializeQuickEditForm($row.next());
                        }
                    }
                });
            }
        });
        
        // Inline editing close
        $(document).on('click', '.short-url-quick-edit-cancel', function() {
            $(this).closest('tr').remove();
        });
        
        // QR code modal - Original handler
        $('.short-url-show-qr').on('click', function(e) {
            e.preventDefault();
            showQrModal($(this).data('id'), $(this).data('url'));
        });
        
        // QR code modal - Fix for the button with different class name
        $(document).on('click', '.short-url-generate-qr', function(e) {
            e.preventDefault();
            showQrModal($(this).data('url-id'), $(this).data('url'));
        });
    }
    
    /**
     * Build quick edit row HTML
     */
    function buildQuickEditRow(urlData, $originalRow) {
        var $template = $(
            '<tr class="short-url-quick-edit-row">' +
                '<td colspan="' + $originalRow.find('td').length + '">' +
                    '<div class="short-url-quick-edit-form">' +
                        '<input type="hidden" name="url_id" value="' + urlData.id + '">' +
                        '<div class="short-url-edit-fields">' +
                            '<div class="short-url-form-row">' +
                                '<label>Destination URL:</label>' +
                                '<input type="url" name="destination_url" value="' + urlData.destination_url + '" class="regular-text">' +
                            '</div>' +
                            '<div class="short-url-form-row">' +
                                '<label>Short URL:</label>' +
                                '<div class="short-url-slug-field">' +
                                    '<span class="short-url-prefix">' + shortURLAdmin.homeUrl + '</span>' +
                                    '<input type="text" name="short_url" value="' + urlData.short_url + '" pattern="[a-zA-Z0-9-]+" title="Only letters, numbers, and hyphens are allowed">' +
                                '</div>' +
                            '</div>' +
                            '<div class="short-url-form-row">' +
                                '<div class="short-url-checkbox-field">' +
                                    '<input type="checkbox" id="quick_edit_password_protected_' + urlData.id + '" name="password_protected" ' + (urlData.password_protected ? 'checked' : '') + '>' +
                                    '<label for="quick_edit_password_protected_' + urlData.id + '">Password Protected</label>' +
                                '</div>' +
                                '<div class="short-url-password-field" style="' + (urlData.password_protected ? '' : 'display:none;') + '">' +
                                    '<input type="password" name="password" value="' + (urlData.password_protected ? 'password-set' : '') + '" placeholder="Password" autocomplete="new-password">' +
                                    '<p class="description">Leave as is to keep current password or enter a new one</p>' +
                                '</div>' +
                            '</div>' +
                            '<div class="short-url-form-row">' +
                                '<div class="short-url-checkbox-field">' +
                                    '<input type="checkbox" id="quick_edit_expires_' + urlData.id + '" name="expires" ' + (urlData.expires ? 'checked' : '') + '>' +
                                    '<label for="quick_edit_expires_' + urlData.id + '">URL Expires</label>' +
                                '</div>' +
                                '<div class="short-url-expiration-field" style="' + (urlData.expires ? '' : 'display:none;') + '">' +
                                    '<input type="text" name="expiry_date" class="short-url-datepicker" value="' + (urlData.expiry_date || '') + '" placeholder="YYYY-MM-DD">' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="short-url-edit-actions">' +
                            '<button type="button" class="button button-primary short-url-quick-edit-save">Update</button>' +
                            '<button type="button" class="button short-url-quick-edit-cancel">Cancel</button>' +
                        '</div>' +
                    '</div>' +
                '</td>' +
            '</tr>'
        );
        
        return $template;
    }
    
    /**
     * Initialize quick edit form
     */
    function initializeQuickEditForm($row) {
        // Initialize datepicker
        $row.find('.short-url-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            minDate: 0
        });
        
        // Password toggle
        $row.find('input[name="password_protected"]').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).closest('.short-url-form-row').find('.short-url-password-field').slideDown(200);
            } else {
                $(this).closest('.short-url-form-row').find('.short-url-password-field').slideUp(200);
            }
        });
        
        // Expiration toggle
        $row.find('input[name="expires"]').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).closest('.short-url-form-row').find('.short-url-expiration-field').slideDown(200);
            } else {
                $(this).closest('.short-url-form-row').find('.short-url-expiration-field').slideUp(200);
            }
        });
        
        // Save button
        $row.find('.short-url-quick-edit-save').on('click', function() {
            var $form = $(this).closest('.short-url-quick-edit-form');
            var urlId = $form.find('input[name="url_id"]').val();
            var data = {
                action: 'short_url_update_url',
                url_id: urlId,
                destination_url: $form.find('input[name="destination_url"]').val(),
                short_url: $form.find('input[name="short_url"]').val(),
                password_protected: $form.find('input[name="password_protected"]').is(':checked') ? 1 : 0,
                password: $form.find('input[name="password"]').val(),
                expires: $form.find('input[name="expires"]').is(':checked') ? 1 : 0,
                expiry_date: $form.find('input[name="expiry_date"]').val(),
                nonce: shortURLAdmin.adminNonce
            };
            
            // Validate form
            if (data.destination_url.trim() === '') {
                alert('Please enter a destination URL');
                $form.find('input[name="destination_url"]').focus();
                return;
            }
            
            if (data.short_url.trim() === '') {
                alert('Please enter a short URL');
                $form.find('input[name="short_url"]').focus();
                return;
            }
            
            if (!/^[a-zA-Z0-9-]+$/.test(data.short_url)) {
                alert('Short URL can only contain letters, numbers, and hyphens');
                $form.find('input[name="short_url"]').focus();
                return;
            }
            
            if (data.expires && data.expiry_date.trim() === '') {
                alert('Please select an expiration date or disable expiration');
                $form.find('input[name="expiry_date"]').focus();
                return;
            }
            
            // Show loading state
            var $button = $(this);
            $button.prop('disabled', true).text('Saving...');
            
            // Submit the form
            $.ajax({
                url: shortURLAdmin.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show updated data
                        window.location.reload();
                    } else {
                        alert(response.data.message || 'Failed to update URL');
                        $button.prop('disabled', false).text('Update');
                    }
                },
                error: function() {
                    alert('Failed to update URL');
                    $button.prop('disabled', false).text('Update');
                }
            });
        });
    }

    /**
     * Initialize analytics page
     */
    function initializeAnalytics() {
        // Fast date range selector
        $('.short-url-date-range-link').on('click', function(e) {
            e.preventDefault();
            var range = $(this).data('range');
            $('#range').val(range).trigger('change');
        });
    }
    
    function generateSlug() {
        // Show loading
        $('#short-url-slug').val(shortURLAdmin.strings.generating);
        
        // Make API request to generate slug
        $.ajax({
            url: shortURLAdmin.apiRoot + '/urls/generate-slug',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', shortURLAdmin.apiNonce);
            },
            data: {},
            success: function(response) {
                $('#short-url-slug').val(response.slug);
            },
            error: function() {
                $('#short-url-slug').val('');
                alert(shortURLAdmin.strings.error);
            }
        });
    }
    
    /**
     * Initialize charts
     */
    function initCharts() {
        // Check if Chart.js is available
        if (typeof Chart === 'undefined') {
            console.log('Chart.js is not available');
            return;
        }
        
        // Use requestAnimationFrame to optimize chart rendering
        // This prevents performance issues by ensuring charts are rendered in the next animation frame
        window.requestAnimationFrame(function() {
            // Initialize dashboard chart
            var $clicksChart = $('#short-url-clicks-chart');
            
            if ($clicksChart.length && typeof shortURLChartData !== 'undefined') {
                try {
                    var ctx = $clicksChart[0].getContext('2d');
                    
                    // Check if a chart instance already exists for this canvas
                    try {
                        var existingChart = Chart.getChart ? Chart.getChart(ctx.canvas) : null;
                        if (existingChart) {
                            console.log('Chart already initialized, skipping initialization');
                            return;
                        }
                    } catch (err) {
                        console.log('Could not check for existing chart, continuing with initialization');
                    }
                    
                    // Limit data points to improve performance
                    var optimizedData = optimizeChartData(shortURLChartData);
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: optimizedData.dates || [],
                            datasets: [{
                                label: optimizedData.label || 'Clicks',
                                data: optimizedData.counts || [],
                                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                borderColor: 'rgba(0, 115, 170, 1)',
                                borderWidth: 2,
                                pointBackgroundColor: 'rgba(0, 115, 170, 1)',
                                pointRadius: 3,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 0 // Disable animations for better performance
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    enabled: true,
                                    mode: 'index',
                                    intersect: false
                                }
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error initializing clicks chart:', e);
                }
            }
            
            // Initialize the rest of the charts with a slight delay to prevent performance issues
            setTimeout(initializeRemainingCharts, 100);
        });
    }
    
    /**
     * Initialize remaining charts with a delay to prevent performance issues
     */
    function initializeRemainingCharts() {
        // Also check for the clicksChart on the analytics page (different ID)
        var $analyticsClicksChart = $('#clicksChart');
        
        if ($analyticsClicksChart.length) {
            try {
                var analyticsCtx = $analyticsClicksChart[0].getContext('2d');
                
                // Check if a chart instance already exists for this canvas
                try {
                    var existingAnalyticsChart = Chart.getChart ? Chart.getChart(analyticsCtx.canvas) : null;
                    if (existingAnalyticsChart) {
                        console.log('Analytics chart already initialized, skipping initialization');
                        return;
                    }
                } catch (err) {
                    console.log('Could not check for existing analytics chart, continuing with initialization');
                }
                
                // Check if chart data is available in the page
                if (typeof chartData !== 'undefined') {
                    new Chart(analyticsCtx, {
                        type: 'line',
                        data: chartData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            height: 320,
                            animation: {
                                duration: 0 // Disable animations for better performance
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            },
                            plugins: {
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('Error initializing analytics clicks chart:', e);
            }
        }
        
        // Initialize analytics charts with a slight delay
        setTimeout(initializeAnalyticsCharts, 100);
    }
    
    /**
     * Initialize analytics charts
     */
    function initializeAnalyticsCharts() {
        // Initialize analytics charts
        var $browserChart = $('#short-url-browser-chart');
        var $deviceChart = $('#short-url-device-chart');
        var $countryChart = $('#short-url-country-chart');
        
        // Check for analytics data (support both variable names)
        var analyticsData = typeof shortURLAnalyticsData !== 'undefined' ? shortURLAnalyticsData : 
                           (typeof shortURLAnalytics !== 'undefined' ? shortURLAnalytics : null);
        
        if ($browserChart.length && analyticsData) {
            try {
                if (analyticsData.browsers) {
                    // Handle different data formats
                    var browserData = {
                        labels: Object.keys(analyticsData.browsers),
                        values: Object.values(analyticsData.browsers)
                    };
                    initPieChart($browserChart[0].getContext('2d'), browserData);
                }
                
                // Add a slight delay between chart initializations
                setTimeout(function() {
                    if (analyticsData.devices) {
                        // Handle different data formats
                        var deviceData = {
                            labels: Object.keys(analyticsData.devices),
                            values: Object.values(analyticsData.devices)
                        };
                        initPieChart($deviceChart[0].getContext('2d'), deviceData);
                    }
                }, 50);
                
                // Add a slight delay between chart initializations
                setTimeout(function() {
                    if (analyticsData.countries) {
                        // Handle different data formats
                        var countryData = {
                            labels: Object.keys(analyticsData.countries),
                            values: Object.values(analyticsData.countries)
                        };
                        initPieChart($countryChart[0].getContext('2d'), countryData);
                    }
                }, 100);
            } catch (e) {
                console.error('Error initializing analytics charts:', e);
            }
        }
    }
    
    /**
     * Optimize chart data to improve performance
     * 
     * @param {Object} data The chart data
     * @return {Object} Optimized chart data
     */
    function optimizeChartData(data) {
        if (!data || !data.dates || !data.counts) {
            return {
                dates: [],
                counts: [],
                label: 'Clicks'
            };
        }
        
        // If we have more than 30 data points, sample them to improve performance
        if (data.dates.length > 30) {
            var step = Math.ceil(data.dates.length / 30);
            var optimizedDates = [];
            var optimizedCounts = [];
            
            for (var i = 0; i < data.dates.length; i += step) {
                optimizedDates.push(data.dates[i]);
                optimizedCounts.push(data.counts[i]);
            }
            
            return {
                dates: optimizedDates,
                counts: optimizedCounts,
                label: data.label
            };
        }
        
        return data;
    }

    /**
     * Initialize a pie chart
     *
     * @param {CanvasRenderingContext2D} ctx     Canvas context
     * @param {Array}                    data    Chart data
     */
    function initPieChart(ctx, data) {
        if (!ctx || !data || !data.labels || !data.values) {
            console.error('Invalid data for pie chart');
            return;
        }
        
        try {
            // Check if a chart instance already exists for this canvas
            try {
                var existingChart = Chart.getChart ? Chart.getChart(ctx.canvas) : null;
                if (existingChart) {
                    console.log('Pie chart already initialized, skipping initialization');
                    return;
                }
            } catch (err) {
                console.log('Could not check for existing pie chart, continuing with initialization');
            }
            
            // Limit the number of data points to improve performance
            var optimizedData = optimizePieChartData(data);
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: optimizedData.labels || [],
                    datasets: [{
                        data: optimizedData.values || [],
                        backgroundColor: optimizedData.colors || generateDefaultColors(optimizedData.labels.length),
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 0 // Disable animations for better performance
                    },
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            enabled: true
                        }
                    }
                }
            });
        } catch (e) {
            console.error('Error initializing pie chart:', e);
        }
    }
    
    /**
     * Optimize pie chart data to improve performance
     * 
     * @param {Object} data The chart data
     * @return {Object} Optimized chart data
     */
    function optimizePieChartData(data) {
        if (!data || !data.labels || !data.values) {
            return {
                labels: [],
                values: []
            };
        }
        
        // If we have more than 8 data points, combine the smallest ones into "Other"
        if (data.labels.length > 8) {
            // Create a combined array of labels and values
            var combined = [];
            for (var i = 0; i < data.labels.length; i++) {
                combined.push({
                    label: data.labels[i],
                    value: data.values[i]
                });
            }
            
            // Sort by value (descending)
            combined.sort(function(a, b) {
                return b.value - a.value;
            });
            
            // Take the top 7 items
            var topItems = combined.slice(0, 7);
            
            // Combine the rest into "Other"
            var otherValue = 0;
            for (var j = 7; j < combined.length; j++) {
                otherValue += combined[j].value;
            }
            
            // Create the optimized data
            var optimizedLabels = topItems.map(function(item) {
                return item.label;
            });
            
            var optimizedValues = topItems.map(function(item) {
                return item.value;
            });
            
            // Add the "Other" category if it has a value
            if (otherValue > 0) {
                optimizedLabels.push('Other');
                optimizedValues.push(otherValue);
            }
            
            return {
                labels: optimizedLabels,
                values: optimizedValues
            };
        }
        
        return data;
    }

    /**
     * Generate default colors for charts
     *
     * @param {number} count    Number of colors needed
     * @return {Array}         Array of color strings
     */
    function generateDefaultColors(count) {
        var colors = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(40, 159, 64, 0.8)',
            'rgba(210, 199, 199, 0.8)'
        ];
        
        // If we need more colors than in our default set, just repeat them
        var result = [];
        for (var i = 0; i < count; i++) {
            result.push(colors[i % colors.length]);
        }
        
        return result;
    }

    /**
     * Show QR Code Modal
     *
     * @param {number} urlId     URL ID
     * @param {string} fullUrl   Full URL to encode
     */
    function showQrModal(urlId, fullUrl) {
        // Create and show modal
        var $modal = $('<div class="short-url-modal-backdrop"></div>');
        var $modalContent = $(
            '<div class="short-url-modal">' +
                '<div class="short-url-modal-header">' +
                    '<h3>QR Code for ' + fullUrl + '</h3>' +
                    '<button class="short-url-modal-close dashicons dashicons-no-alt"></button>' +
                '</div>' +
                '<div class="short-url-modal-body">' +
                    '<div class="short-url-modal-loading">Loading...</div>' +
                '</div>' +
            '</div>'
        );
        
        $modal.append($modalContent);
        $('body').append($modal);
        
        // Prevent scrolling
        $('body').addClass('short-url-modal-open');
        
        // Load QR code
        $.ajax({
            url: shortURLAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'short_url_qr_code',
                url_id: urlId,
                nonce: shortURLAdmin.adminNonce,
                size: 300 // Default size for initial load
            },
            success: function(response) {
                if (response.success && response.data.qr_url) {
                    var $qrDisplay = $(
                        '<div class="short-url-qr-display">' +
                            '<img src="' + response.data.qr_url + '" alt="QR Code" id="short-url-qr-img" class="short-url-qr-img">' +
                            '<p>Scan this QR code to visit the short URL</p>' +
                            '<div class="short-url-qr-settings">' +
                                '<div class="short-url-qr-size">' +
                                    '<label for="short-url-qr-size-select">Size:</label>' +
                                    '<select id="short-url-qr-size-select">' +
                                        '<option value="100">Small (100x100)</option>' +
                                        '<option value="200">Medium (200x200)</option>' +
                                        '<option value="300" selected>Large (300x300)</option>' +
                                        '<option value="500">Extra Large (500x500)</option>' +
                                    '</select>' +
                                '</div>' +
                                '<div class="short-url-qr-format">' +
                                    '<label for="short-url-qr-format-select">Format:</label>' +
                                    '<select id="short-url-qr-format-select">' +
                                        '<option value="png" selected>PNG</option>' +
                                        '<option value="jpg">JPG</option>' +
                                    '</select>' +
                                '</div>' +
                            '</div>' +
                            '<div class="short-url-qr-download">' +
                                '<a href="' + response.data.qr_url + '" download="qr-code-' + urlId + '.png" class="button short-url-download-btn">' +
                                    '<span class="dashicons dashicons-download"></span> Download QR Code' +
                                '</a>' +
                            '</div>' +
                        '</div>'
                    );

                    $modalContent.find('.short-url-modal-body').html($qrDisplay);

                    // Handle size change
                    $('#short-url-qr-size-select').on('change', function() {
                        updateQRCode(urlId, $(this).val(), $('#short-url-qr-format-select').val());
                    });

                    // Handle format change
                    $('#short-url-qr-format-select').on('change', function() {
                        updateQRCode(urlId, $('#short-url-qr-size-select').val(), $(this).val());
                    });
                } else {
                    $modalContent.find('.short-url-modal-body').html('<div class="short-url-error">Failed to load QR code</div>');
                }
            },
            error: function() {
                $modalContent.find('.short-url-modal-body').html('<div class="short-url-error">Failed to load QR code</div>');
            }
        });
        
        // Close modal on backdrop click or close button
        $modal.on('click', function(e) {
            if ($(e.target).hasClass('short-url-modal-backdrop') || 
                $(e.target).hasClass('short-url-modal-close')) {
                closeModal();
            }
        });
        
        // Close on ESC key
        $(document).on('keydown.shortUrlModal', function(e) {
            if (e.keyCode === 27) { // ESC key
                closeModal();
            }
        });
        
        // Function to close the modal
        function closeModal() {
            $modal.remove();
            $('body').removeClass('short-url-modal-open');
            $(document).off('keydown.shortUrlModal');
        }
    }

    /**
     * Function to update QR code based on size and format
     *
     * @param {number} urlId     URL ID
     * @param {string} size      QR code size
     * @param {string} format    QR code format
     */
    function updateQRCode(urlId, size, format) {
        var $qrImg = $('#short-url-qr-img');
        var $downloadBtn = $('.short-url-download-btn');
        
        // Show loading state
        $qrImg.css('opacity', 0.5);
        
        // Request new QR code
        $.ajax({
            url: shortURLAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'short_url_qr_code',
                url_id: urlId,
                nonce: shortURLAdmin.adminNonce,
                size: size,
                format: format
            },
            success: function(response) {
                if (response.success && response.data.qr_url) {
                    // Update image
                    $qrImg.attr('src', response.data.qr_url).css('opacity', 1);
                    
                    // Update download link
                    $downloadBtn.attr('href', response.data.qr_url);
                    $downloadBtn.attr('download', 'qr-code-' + urlId + '.' + format);
                }
            },
            error: function() {
                $qrImg.css('opacity', 1);
                alert('Failed to update QR code. Please try again.');
            }
        });
    }
})(jQuery); 