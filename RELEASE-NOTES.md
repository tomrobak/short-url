# Short URL Release Notes

## Latest Version: 1.1.13

We're pleased to announce the latest release of Short URL, which improves the update mechanism and fixes several issues.

### What's New

- **Update System**: Fixed automatic update detection from GitHub
- **Update Mechanism**: Improved to properly detect and install new versions
- **Performance**: Reduced update cache time to check more frequently for new releases
- **Stability**: Enhanced error handling and logging for update processes
- **Compatibility**: Fixed package URL construction for GitHub releases

### How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

### Technical Details

This release focuses on improving the update system:

- Fixed issues with the GitHub updater not properly detecting new versions
- Reduced the cache time for GitHub API calls from 6 hours to 30 minutes
- Improved error handling and logging for better troubleshooting
- Fixed the package URL construction to ensure proper download links
- Enhanced the update check process to be more reliable

### Tested With

- WordPress: 6.7
- PHP: 8.0+

### Previous Version (1.1.11)

The previous release included:

- **URL Management**: Fixed header modification error when activating or deactivating URLs
- **Gutenberg Block**: Enhanced Short URL block with improved styling and better visibility
- **Analytics**: Fixed critical error on the analytics detail page for specific URLs
- **Performance**: Optimized chart rendering to prevent performance issues on analytics pages
- **Error Handling**: Improved error handling in analytics data processing
- **Data Optimization**: Added intelligent data sampling for charts with many data points to improve performance

### Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 