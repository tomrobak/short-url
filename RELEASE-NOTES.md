# Short URL Release Notes

## Latest Version: 1.1.16

We're pleased to announce the latest release of Short URL, which provides a more comprehensive fix for WordPress 6.7+ compatibility.

### What's New

- **Complete WordPress 6.7+ Compatibility**: Removed all translation functions from early plugin initialization
- **Error Notice Resolution**: Fully eliminated the warning about translations being loaded too early
- **Improved Plugin Architecture**: Further enhanced plugin initialization sequence

### How to Update

1. **Automatic Update**: Visit your WordPress dashboard and go to Updates. If an update is available for Short URL, you'll see it in the list.
2. **Manual Update**: Download the ZIP file from this release and install it via the WordPress plugin uploader or by replacing the files on your server.

### Technical Details

This release completely addresses an important notice that was still appearing in WordPress 6.7+ after our previous fix:

```
PHP Notice: Function _load_textdomain_just_in_time was called incorrectly. 
Translation loading for the short-url domain was triggered too early. 
This is usually an indicator for some code in the plugin or theme running too early. 
Translations should be loaded at the init action or later.
```

While our previous update moved the textdomain loading to the `init` hook, we discovered that translation functions (`__()`) in the version check code were still triggering text domain loading too early. This update replaces those functions with non-translated alternatives for immediate feedback, ensuring no translation is attempted before WordPress is ready.

### Tested With

- WordPress: 6.7
- PHP: 8.0+

### Previous Version (1.1.15)

The previous release included:

- Initial fix for text domain loading by moving to the `init` hook
- First attempt to address WordPress 6.7+ compatibility warnings

### Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 