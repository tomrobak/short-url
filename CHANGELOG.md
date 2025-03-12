# Changelog

All notable changes to the Short URL plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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