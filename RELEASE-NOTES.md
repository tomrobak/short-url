# Short URL Release Notes

## Latest Version: 1.1.18

We're pleased to announce the latest release of Short URL, which addresses an important issue with automatic updates.

### What's New

- **Fixed Automatic Updates**: Resolved issue with GitHub updater failing to find new releases
- **Correct Repository URL**: Updated the GitHub repository reference to point to the correct location

### How to Update

1. **Manual Update**: For this release, you need to manually download the ZIP file from GitHub and install it via the WordPress plugin uploader or by replacing the files on your server.
2. **Future Updates**: After installing this update, automatic updates should work correctly going forward.

### Technical Details

This release fixes a critical configuration issue where the GitHub updater was looking for updates at the wrong repository URL:

```
tomrobert/short-url (incorrect)
tomrobak/short-url (correct)
```

This resulted in 404 errors when checking for updates, as shown in the debug logs:
```
Short URL: GitHub API returned non-200 status code: 404
Short URL: Failed to get release info from GitHub or missing tag_name
```

After this update, the plugin will correctly check for updates at the proper GitHub repository.

### Tested With

- WordPress: 6.7
- PHP: 8.0+

### Previous Version (1.1.17)

The previous release included:

- Fixed PHP warnings in analytics-detail.php
- Improved analytics page layout and visualization
- Fixed country flag image display

### Additional Information

- Full changelog: [CHANGELOG.md](https://github.com/tomrobak/short-url/blob/main/CHANGELOG.md)
- Documentation: [Wiki](https://github.com/tomrobak/short-url/wiki)
- Support: [Issues](https://github.com/tomrobak/short-url/issues)

---

Thank you for using Short URL! If you encounter any issues with this update, please let us know by opening an issue on GitHub. 