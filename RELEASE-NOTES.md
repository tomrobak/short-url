# Short URL Release Notes

## Latest Version: 1.1.15

We're pleased to announce the latest release of Short URL, which addresses a WordPress 6.7+ compatibility issue.

### What's New

- **WordPress 6.7+ Compatibility**: Fixed text domain loading to comply with WordPress 6.7+ requirements
- **Error Notice Fix**: Eliminated the warning about translations being loaded too early
- **Performance**: Improved plugin initialization sequence for better compatibility

### How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

### Technical Details

This release addresses an important notice that appears in WordPress 6.7+:

```
PHP Notice: Function _load_textdomain_just_in_time was called incorrectly. 
Translation loading for the short-url domain was triggered too early. 
This is usually an indicator for some code in the plugin or theme running too early. 
Translations should be loaded at the init action or later.
```

The fix moves the textdomain loading from the `plugins_loaded` hook to the `init` hook, which is the recommended approach in WordPress 6.7 and later.

### Tested With

- WordPress: 6.7
- PHP: 8.0+

### Previous Version (1.1.14)

The previous release included:

- **Critical Fixes**: Fixed fatal errors with class inclusion and undefined methods
- **User Interface**: Improved the short URL display on post edit screens
- **Visibility**: Enhanced the short URL field to show the full URL with better layout
- **Styling**: Updated CSS for both admin and public-facing areas
- **Packaging**: Improved plugin installation to ensure correct directory structure

### Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 