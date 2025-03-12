# Short URL 1.1.6 Release Notes

We're excited to announce the release of Short URL 1.1.6! This release focuses on fixing several important issues to improve stability and usability.

## What's Fixed

- **JavaScript Syntax Error**: Fixed a syntax error in short-url-admin.js where the initializeAnalytics function was missing a closing brace, causing JavaScript console errors.
- **Settings Page**: Fixed an issue where the settings page wasn't saving options properly. The form submission has been improved to correctly save all settings.
- **Analytics Page**: Fixed issues with the analytics display by adding better error handling and support for different data formats.
- **Chart Initialization**: Added robust error checking for chart initialization to prevent JavaScript errors when displaying analytics.
- **Overall Stability**: Improved code quality and stability throughout the plugin.

## How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

## After Updating

After updating the plugin, please clear your browser cache to ensure that the new JavaScript files are properly loaded.

## Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you enjoy the plugin, please consider giving it a star on GitHub or writing a review. 