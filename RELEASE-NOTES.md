# Short URL Release Notes

## Latest Version: 1.1.14

We're pleased to announce the latest release of Short URL, which fixes critical errors and improves the user interface.

### What's New

- **Critical Fixes**: Fixed fatal errors with class inclusion and undefined methods
- **User Interface**: Improved the short URL display on post edit screens
- **Visibility**: Enhanced the short URL field to show the full URL with better layout
- **Styling**: Updated CSS for both admin and public-facing areas
- **Packaging**: Improved plugin installation to ensure correct directory structure

### How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

### Technical Details

This release focuses on fixing critical errors and improving the user experience:

- Fixed fatal error with missing class-short-url.php include
- Fixed fatal error with undefined method Short_URL_Utils::get_short_url()
- Fixed PHP warning with undefined property stdClass::$short_url
- Improved short URL box design on post edit screen for better readability
- Enhanced short URL display in the editor for better user experience
- Added better CSS styling for the public-facing short URL block
- Improved plugin packaging to ensure it always installs to the correct directory

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