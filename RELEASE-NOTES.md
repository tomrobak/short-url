# Short URL Release Notes

## Latest Version: 1.1.12

We're pleased to announce the latest release of Short URL, which resolves critical issues and improves stability.

### What's New

- **AJAX Handling**: Fixed a fatal error related to missing get_url_by_post_id method
- **Analytics Charts**: Fixed charts container growing infinitely in height on analytics pages
- **User Interface**: Improved chart rendering with proper height constraints and aspect ratio handling

### How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

### Technical Details

This release focuses on fixing critical issues:
- The fatal error in AJAX handling is now resolved by implementing the missing get_url_by_post_id method
- The infinite height growth of charts on analytics pages has been fixed by setting proper constraints
- Chart rendering has been optimized with improved aspect ratio handling

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