<?php
/**
 * Short URL Redirect
 *
 * @package Short_URL
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Redirect Class
 */
class Short_URL_Redirect {
    /**
     * Redirect a short URL
     *
     * @param string $slug URL slug
     * @return void
     */
    public static function redirect($slug) {
        // Check if this is a valid slug
        if (empty($slug)) {
            return;
        }
        
        // Get the URL data
        $db = new Short_URL_DB();
        $url = $db->get_url_for_redirect($slug);
        
        if (!$url) {
            return;
        }
        
        // Check if password protected
        if (!empty($url['password'])) {
            self::handle_password_protection($url);
            return;
        }
        
        // Prepare the destination URL
        $destination_url = self::prepare_destination_url($url);
        
        // Track the visit if enabled
        if ($url['track_visits']) {
            self::track_visit($url['id']);
        }
        
        // Perform the redirect
        self::perform_redirect($destination_url, $url['redirect_type'], $url['nofollow'], $url['sponsored']);
        exit;
    }
    
    /**
     * Handle password protection
     *
     * @param array $url URL data
     * @return void
     */
    private static function handle_password_protection($url) {
        // If the password is correct and stored in session, proceed with redirect
        if (isset($_SESSION['short_url_password'][$url['id']])) {
            // Prepare the destination URL
            $destination_url = self::prepare_destination_url($url);
            
            // Track the visit if enabled
            if ($url['track_visits']) {
                self::track_visit($url['id']);
            }
            
            // Perform the redirect
            self::perform_redirect($destination_url, $url['redirect_type'], $url['nofollow'], $url['sponsored']);
            exit;
        }
        
        // If form submitted, check password
        if (isset($_POST['short_url_password'])) {
            $submitted_password = sanitize_text_field($_POST['short_url_password']);
            
            if (wp_check_password($submitted_password, $url['password'])) {
                // Store in session
                if (!session_id()) {
                    session_start();
                }
                
                $_SESSION['short_url_password'][$url['id']] = true;
                
                // Redirect to the same page to avoid form resubmission
                wp_redirect(trailingslashit(site_url()) . $url['slug']);
                exit;
            } else {
                $password_error = __('Incorrect password. Please try again.', 'short-url');
            }
        }
        
        // Output password form
        self::output_password_form($url, isset($password_error) ? $password_error : '');
        exit;
    }
    
    /**
     * Output password protection form
     *
     * @param array  $url   URL data
     * @param string $error Error message
     * @return void
     */
    private static function output_password_form($url, $error = '') {
        // Get site name
        $site_name = get_bloginfo('name');
        
        // Get short URL
        $short_url = Short_URL_Generator::get_short_url($url['slug']);
        
        // Output the form
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html__('Protected Link', 'short-url') . ' - ' . esc_html($site_name); ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    background-color: #f5f5f5;
                    margin: 0;
                    padding: 0;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    color: #333;
                }
                .container {
                    max-width: 400px;
                    width: 100%;
                    padding: 30px;
                    background-color: #fff;
                    border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                h1 {
                    font-size: 24px;
                    margin-top: 0;
                    margin-bottom: 20px;
                    text-align: center;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 500;
                }
                input[type="password"] {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 16px;
                    box-sizing: border-box;
                }
                button {
                    width: 100%;
                    padding: 12px;
                    background-color: #0073aa;
                    color: #fff;
                    border: none;
                    border-radius: 4px;
                    font-size: 16px;
                    cursor: pointer;
                    transition: background-color 0.3s;
                }
                button:hover {
                    background-color: #005a87;
                }
                .error {
                    color: #d63638;
                    font-size: 14px;
                    margin-top: 5px;
                }
                .info {
                    text-align: center;
                    font-size: 14px;
                    color: #666;
                    margin-top: 20px;
                }
                .url-info {
                    text-align: center;
                    margin-bottom: 20px;
                    word-break: break-all;
                    font-size: 14px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1><?php echo esc_html__('Protected Link', 'short-url'); ?></h1>
                <div class="url-info">
                    <?php echo esc_html($short_url); ?>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label for="short_url_password"><?php echo esc_html__('Enter Password', 'short-url'); ?></label>
                        <input type="password" id="short_url_password" name="short_url_password" required autofocus>
                        <?php if (!empty($error)) : ?>
                            <div class="error"><?php echo esc_html($error); ?></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit"><?php echo esc_html__('Access Link', 'short-url'); ?></button>
                </form>
                <div class="info">
                    <?php echo esc_html__('This link is password protected.', 'short-url'); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    /**
     * Prepare the destination URL with parameters if needed
     *
     * @param array $url URL data
     * @return string Prepared destination URL
     */
    private static function prepare_destination_url($url) {
        $destination_url = $url['destination_url'];
        
        // Forward parameters if enabled
        if ($url['forward_parameters'] && !empty($_SERVER['QUERY_STRING'])) {
            $separator = (strpos($destination_url, '?') !== false) ? '&' : '?';
            $destination_url .= $separator . $_SERVER['QUERY_STRING'];
        }
        
        return $destination_url;
    }
    
    /**
     * Track a visit
     *
     * @param int $url_id URL ID
     * @return void
     */
    private static function track_visit($url_id) {
        // Track the visit asynchronously to avoid delaying the redirect
        if (function_exists('wp_schedule_single_event')) {
            wp_schedule_single_event(time(), 'short_url_track_visit', array($url_id));
        } else {
            // If not possible, track synchronously
            Short_URL_Analytics::record_visit($url_id);
        }
    }
    
    /**
     * Perform the redirect
     *
     * @param string $destination_url Destination URL
     * @param int    $redirect_type   Redirect type (301, 302, 307)
     * @param bool   $nofollow        Whether to add nofollow attribute
     * @param bool   $sponsored       Whether to add sponsored attribute
     * @return void
     */
    private static function perform_redirect($destination_url, $redirect_type, $nofollow, $sponsored) {
        // Validate the redirect type
        $valid_types = array(301, 302, 307);
        $redirect_type = in_array((int) $redirect_type, $valid_types) ? (int) $redirect_type : 301;
        
        // Set rel attributes if using meta refresh
        $rel_attrs = array();
        
        if ($nofollow) {
            $rel_attrs[] = 'nofollow';
        }
        
        if ($sponsored) {
            $rel_attrs[] = 'sponsored';
        }
        
        $rel_attr = !empty($rel_attrs) ? ' rel="' . implode(' ', $rel_attrs) . '"' : '';
        
        // Check if using meta refresh (determined by theme option)
        $use_meta_refresh = get_option('short_url_use_meta_refresh', false);
        
        if ($use_meta_refresh) {
            echo '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="' . get_bloginfo('charset') . '">
                <meta http-equiv="refresh" content="0;url=' . esc_url($destination_url) . '">
                <link rel="canonical" href="' . esc_url($destination_url) . '"' . $rel_attr . '>
                <title>' . esc_html__('Redirecting...', 'short-url') . '</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        background-color: #f5f5f5;
                        color: #333;
                        text-align: center;
                        padding: 50px;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                    }
                    h1 {
                        font-size: 24px;
                        margin-bottom: 20px;
                    }
                    p {
                        margin-bottom: 20px;
                    }
                    a {
                        color: #0073aa;
                        text-decoration: none;
                    }
                    a:hover {
                        text-decoration: underline;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>' . esc_html__('Redirecting...', 'short-url') . '</h1>
                    <p>' . esc_html__('You are being redirected to the destination. If you are not redirected automatically, please click the link below:', 'short-url') . '</p>
                    <p><a href="' . esc_url($destination_url) . '"' . $rel_attr . '>' . esc_html__('Click here to continue', 'short-url') . '</a></p>
                </div>
                <script>
                    window.location.href = "' . esc_url($destination_url) . '";
                </script>
            </body>
            </html>';
            exit;
        }
        
        // Set the appropriate status code
        switch ($redirect_type) {
            case 302:
                $status = 'Found';
                break;
            case 307:
                $status = 'Temporary Redirect';
                break;
            case 301:
            default:
                $status = 'Moved Permanently';
                break;
        }
        
        // Set headers for rel attributes if needed
        if (!empty($rel_attrs)) {
            header('Link: <' . esc_url($destination_url) . '>; rel="' . implode(' ', $rel_attrs) . '"');
        }
        
        // Perform the redirect
        header('HTTP/1.1 ' . $redirect_type . ' ' . $status);
        header('Location: ' . $destination_url);
        exit;
    }
} 