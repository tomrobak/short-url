# Changelog

All notable changes to the Short URL plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.7] - 2023-07-20

### Fixed
- Fixed chart initialization conflict that was causing "Canvas is already in use" errors
- Added checks to prevent duplicate chart initialization
- Improved error handling for Chart.js initialization
- Enhanced compatibility with different Chart.js versions

## [1.1.6] - 2023-07-15

### Fixed
- Fixed JavaScript syntax error in short-url-admin.js where the initializeAnalytics function was missing a closing brace
- Fixed settings page that wasn't saving settings properly by improving form submission handling
- Fixed analytics page issues by adding better error handling and support for different data formats
- Added robust error checking for chart initialization to prevent JavaScript errors
- Improved overall code quality and stability

## [1.1.5] - 2023-06-25

### Added
- Created missing CSS files for the Short URL block in frontend and editor
- Added redirect type selection to the single URL edit screen

### Fixed
- Updated the admin footer message with new community link
- Fixed Chart.js loading issues by properly including it on all plugin pages
- Fixed the "Check for updates" functionality to properly detect updates from GitHub
- Added the missing get_domain_from_url utility function
- Fixed JavaScript errors in the admin interface
- Fixed missing block CSS and JS files

## [1.1.4] - 2023-06-20

### Added
- Created missing analytics-detail.php file for URL-specific analytics
- Added setting to disable the promotional footer message
- Enhanced dashboard layout with improved styling for cards and metrics
- Added visual feedback for hover states on dashboard cards

### Fixed
- QR code functionality now works correctly with URL IDs
- URL status changes (activate/deactivate) now function properly for both row actions and bulk actions
- Group functionality has been repaired, with proper filtering of URLs by group
- Admin footer message is now more professional and can be disabled
- Redirect messages for group operations now include proper count information
- Multiple minor UI and CSS improvements for better user experience

## [1.1.3] - 2023-06-18

### Added
- Bulk action for generating shortlinks for multiple posts at once
- Support for bulk generating shortlinks for any enabled post type

### Fixed
- "Check for updates" functionality now properly displays update status
- Improved GitHub update checker with clearer notifications

## [1.1.2] - 2023-06-16

### Fixed
- Fixed URL list table errors related to property access on arrays
- Ensured consistent handling of database results as objects

## [1.1.1] - 2023-06-15

### Fixed
- Settings page registration issue that prevented saving options
- Division by zero error in list tables pagination
- Missing global analytics method causing fatal error on analytics page
- Incorrect GitHub repository and documentation links

## [1.1.0] - 2023-06-14

### Added
- New character set options in settings to fully customize URL structure
- Control over lowercase letters, uppercase letters, numbers, and special characters in generated slugs
- Comprehensive tools page for importing/exporting URLs and database maintenance
- Enhanced settings page with all configuration options

### Fixed
- Division by zero error in list tables pagination
- Missing files and incorrect file references
- Empty settings page
- Database method for retrieving groups
- CSS loading issues
- Various PHP warnings and notices

### Improved
- Character generation algorithm for more reliable short URLs
- Settings handling with better validation
- Documentation and inline code comments
- Overall plugin stability and performance

## [1.0.0] - 2023-06-10

### Added
- Initial release with core functionality
- Short URL generation and management
- Analytics tracking with detailed statistics
- Gutenberg block integration
- Custom short URL slug generation
- Password protection for links
- Expiration dates for links
- Custom database tables for performance
- QR code generation
- WooCommerce integration
- GDPR compliance features
- Admin UI with modern design
- User permission system
- API endpoints for developers
- Link grouping functionality
- Import/export tools
- Automatic updates via GitHub 