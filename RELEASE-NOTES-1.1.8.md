# Short URL 1.1.8 Release Notes

We're pleased to announce the release of Short URL 1.1.8, which focuses on fixing the "Check for updates" functionality to make plugin updates more seamless and user-friendly.

## What's Fixed

- **Update Checker**: Fixed the "Check for updates" functionality that was previously just refreshing the page
- **Interactive Updates**: Added popup messages to clearly indicate if updates are available
- **AJAX Implementation**: Implemented AJAX for update checking to provide a smoother user experience
- **Confirmation Dialog**: Added a confirmation dialog when updates are available to let users decide whether to update immediately
- **Cache Management**: Added proper cache clearing to ensure accurate update detection

## How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

## Technical Details

The "Check for updates" link was previously just refreshing the page, making it unclear if an update was available. We've implemented proper AJAX-based checking that shows popup messages to clearly indicate if updates are available. When an update is found, users are given the option to proceed to the update screen or continue what they were doing.

## Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 