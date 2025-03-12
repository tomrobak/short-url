# Short URL 1.1.5

This release focuses on fixing important bugs and improving the user experience of the Short URL plugin.

## What's New

### UI Improvements
- Added redirect type selection to the URL edit screen, allowing users to choose between 301, 302, and 307 redirects
- Updated admin footer message with wplove.co community link for photographers and videographers
- Created missing CSS files for the Short URL block in both the editor and frontend

## Bug Fixes

### Major Fixes
- Fixed "Check for updates" functionality to properly detect and notify about updates from GitHub
- Fixed Chart.js loading issues that were causing JavaScript errors on dashboard and analytics pages
- Added the missing get_domain_from_url utility function used in analytics detail view
- Fixed JavaScript errors in the admin interface

### Technical Fixes
- Properly included Chart.js on all plugin admin pages
- Fixed missing block CSS and JS files
- Improved error handling in the updater class
- Enhanced GitHub API request handling with better timeout and error management

## Update Instructions

1. Backup your site before updating
2. Update via the WordPress plugin updater or download the ZIP file
3. No database changes in this release, safe to update directly

For more details, see the [changelog](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md).

## 🚀 Key Features

- **URL Shortening**: Create branded short URLs for your WordPress site
- **Analytics Tracking**: Monitor clicks, referrers, and visitor data
- **Gutenberg Integration**: Easily add short URLs to your content
- **QR Code Generation**: Create QR codes for your short URLs
- **Password Protection**: Secure access to destination URLs
- **Expiration Dates**: Set URLs to expire after a specific date
- **User Permissions**: Control who can create and manage short URLs
- **Custom Slugs**: Create memorable, branded short URLs
- **Group Management**: Organize URLs into groups
- **Advanced Dashboard**: View comprehensive statistics
- **Responsive Design**: Works on all devices and screen sizes
- **Developer APIs**: Integrate with other plugins and applications

## 💾 Installation

1. Download the zip file from this release
2. Go to your WordPress admin → Plugins → Add New → Upload Plugin
3. Upload the zip file and activate the plugin
4. Start creating short URLs under the "Short URL" menu

## 📖 Documentation

For detailed documentation on using the plugin, visit our [GitHub Wiki](https://github.com/tomrobak/short-url/wiki).

## 🐞 Issues & Feedback

Please report any bugs or feature requests by creating an [issue on GitHub](https://github.com/tomrobak/short-url/issues).

## 🙏 Credits

Developed by [wplove.co](https://wplove.co/) - WordPress for photographers 