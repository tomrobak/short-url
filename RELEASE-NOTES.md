# Short URL Release Notes

## Latest Version: 1.1.10

We're pleased to announce the latest release of Short URL, which fixes issues with the GitHub updater.

### What's New

- **Update Detection**: Fixed GitHub updater to properly detect and install updates from GitHub releases
- **Error Handling**: Added robust error handling and logging for troubleshooting update issues
- **User Interface**: Improved the "Check for updates" button with better visual feedback
- **Performance**: Reduced GitHub API request cache time to ensure updates are detected faster
- **Diagnostics**: Added detailed debugging information to help identify and resolve update problems

### How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

### Technical Details

This release focuses on fixing the GitHub updater functionality. The update checker now properly detects new versions released on GitHub and shows appropriate notifications in the WordPress admin. We've improved error handling, added detailed logging, and enhanced the user interface for a better update experience.

### Previous Version (1.1.9)

The previous release included:

- **UI Enhancements**: Fixed various UI issues reported by users for a better overall experience
- **QR Code Functionality**: Enhanced QR code generation features for improved reliability
- **Analytics Page**: Improved analytics page rendering performance and fixed display issues
- **Bug Fixes**: Fixed deactivation notice incorrectly showing during plugin activation
- **Release Notes**: Consolidated to a single RELEASE-NOTES.md file for easier tracking of changes

### Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 