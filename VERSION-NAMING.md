# Short URL Version Naming Convention

## Overview

Short URL uses a combination of semantic versioning and descriptive codenames to make versions more memorable and professional.

## Format

The version format is: `X.Y.Z "Codename"`

Where:
- `X` is the major version number (incremented for incompatible API changes)
- `Y` is the minor version number (incremented for new functionality in a backward-compatible manner)
- `Z` is the patch version number (incremented for backward-compatible bug fixes)
- `"Codename"` is a descriptive name that reflects the main focus or theme of the release

## Examples

- `1.2.5 "QR Connect"` - Focused on QR code functionality improvements
- `1.2.6 "Smooth Updater"` - Focused on update system improvements
- `1.2.7 "UI Refresh"` - Focused on user interface improvements
- `1.2.8 "Clear Headers"` - Focused on fixing header issues and improving code quality

## Codename Themes

Codenames typically follow these patterns:

1. **Feature Focus**: Names that describe the main feature that was improved or added
   - Example: "QR Connect" for QR code improvements

2. **Functionality Type**: Names that describe the type of functionality that was improved
   - Example: "Smooth Updater" for update system improvements

3. **User Experience**: Names that reflect improvements to the user experience
   - Example: "UI Refresh" for interface improvements

4. **Technical Improvements**: Names that indicate technical enhancements
   - Example: "Clear Headers" for fixing header-related issues

## Internal Representation

In the codebase, versions are represented by these constants:

- `SHORT_URL_VERSION`: The semantic version number (e.g., "1.2.8")
- `SHORT_URL_VERSION_NAME`: The codename (e.g., "Clear Headers")
- `SHORT_URL_FULL_VERSION`: The complete version string (e.g., "1.2.8 "Clear Headers"")

## Version History

For a complete history of versions and their codenames, please refer to the [CHANGELOG.md](CHANGELOG.md) file. 