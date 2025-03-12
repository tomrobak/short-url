# Short URL - Version 1.2.6 "UpdateMaster"

## 🚀 Release Highlights

This release focuses on fixing compatibility issues and improving the update experience:

- 🐛 **Fixed Translation Loading**: Resolved the WordPress 6.7 translation loading error
- 🎨 **Improved Update Details**: Enhanced how release notes are displayed when viewing update details
- 🔄 **Better Changelog Formatting**: Completely revamped changelog display with better styling

## 📋 What's Changed

### Fixed
- Resolved the "Function _load_textdomain_just_in_time was called incorrectly" warning in WordPress 6.7
- Fixed issue where clicking "View version details" didn't properly display release notes
- Corrected the initialization sequence to ensure translations are loaded at the right time

### Added
- Enhanced styling for changelog and release notes display
- Better formatting for emoji in changelog entries
- Improved code block display in documentation

### Changed
- Moved text domain loading to the init hook with proper priority
- Improved the formatting of release notes with better CSS styling
- Enhanced the readability of the changelog with better spacing and typography

## 🧰 Technical Details

- Fixed translation loading by ensuring it happens at the correct priority in the init hook
- Completely revamped the changelog formatter to better handle Markdown syntax
- Added custom CSS styling to make update information more readable
- Improved handling of emoji and formatting in release notes

---

Need help? Visit our [GitHub repository](https://github.com/tomrobak/short-url) for documentation and support.

