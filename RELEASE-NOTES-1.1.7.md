# Short URL 1.1.7 Release Notes

We're pleased to announce the release of Short URL 1.1.7, a hotfix release that addresses chart initialization issues that were causing JavaScript errors.

## What's Fixed

- **Chart Initialization Conflict**: Fixed an issue where charts were being initialized multiple times, causing "Canvas is already in use" errors
- **Duplicate Prevention**: Added checks to prevent duplicate chart initialization
- **Error Handling**: Improved error handling for Chart.js initialization to provide better diagnostics
- **Compatibility**: Enhanced compatibility with different versions of Chart.js

## How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

## After Updating

After updating the plugin, please clear your browser cache to ensure that the new JavaScript files are properly loaded.

## Technical Details

The "Canvas is already in use" error was occurring because both the inline JavaScript in analytics-detail.php and the global chart initialization in short-url-admin.js were trying to initialize charts on the same canvas elements. This update adds checks to detect if a chart is already initialized on a canvas before attempting to create a new one.

## Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 