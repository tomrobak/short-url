# Short URL Release Notes

## Latest Version: 1.1.11

We're pleased to announce the latest release of Short URL, which addresses several important issues to improve stability and user experience.

### What's New

- **URL Management**: Fixed header modification error when activating or deactivating URLs
- **Gutenberg Block**: Enhanced Short URL block with improved styling and better visibility
- **Analytics**: Fixed critical error on the analytics detail page for specific URLs
- **Performance**: Optimized chart rendering to prevent performance issues on analytics pages
- **Error Handling**: Improved error handling in analytics data processing
- **Data Optimization**: Added intelligent data sampling for charts with many data points to improve performance

### How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

### Technical Details

This release focuses on fixing several issues reported by users:
- The "headers already sent" error when activating URLs has been resolved by implementing AJAX-based activation/deactivation
- The Short URL block in Gutenberg now displays URLs more prominently with better styling
- Chart rendering on analytics pages has been optimized to prevent performance issues
- Error handling has been improved throughout the plugin

### Previous Version (1.1.10)

The previous release included:

- **Update Detection**: Fixed GitHub updater to properly detect and install updates from GitHub releases
- **Error Handling**: Added robust error handling and logging for troubleshooting update issues
- **User Interface**: Improved the "Check for updates" button with better visual feedback
- **Performance**: Reduced GitHub API request cache time to ensure updates are detected faster
- **Diagnostics**: Added detailed debugging information to help identify and resolve update problems

### Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 