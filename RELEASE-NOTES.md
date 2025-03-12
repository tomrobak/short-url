# Short URL Release Notes

## Latest Version: 1.1.19

We're pleased to announce the latest release of Short URL, which fixes an issue with the QR code functionality.

### What's New

- **Fixed QR Code Generation**: Resolved issue with QR codes not displaying properly
- **Improved JavaScript**: Fixed variable references and method calls in admin scripts
- **Enhanced Stability**: Fixed incorrect static method call in QR code generator

### How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

### Technical Details

This release fixes a JavaScript error that was preventing QR codes from loading:

```
Uncaught ReferenceError: short_url_admin is not defined
    at showQrModal (short-url-admin.js?ver=1.1.18:862:18)
```

The issue was caused by mismatches between the variable names used in PHP localization and JavaScript code. In addition, an incorrect static method call in the QR code generator was fixed.

### Tested With

- WordPress: 6.7
- PHP: 8.0+

### Previous Version (1.1.18)

The previous release included:

- Fixed GitHub updater repository URL to correctly point to tomrobak/short-url
- Resolved issue with automatic updates failing due to 404 errors from GitHub API

### Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 