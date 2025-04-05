# 🎉 Short URL Changelog: The Journey So Far!

All the cool updates and improvements to your favorite URL shortener are documented here. Grab a coffee and enjoy the ride through our development journey!

This changelog follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) guidelines 
and [Semantic Versioning](https://semver.org/spec/v2.0.0.html) principles, but with more fun! 😄
## [1.2.9.7] "Direct DB & AJAX Fix" 🔩⚡

- 🐛 Replaced unreliable `dbDelta` with direct `$wpdb->query()` for table creation.
- 🐛 Fixed fatal error by adding missing `ajax_generate_slug` method in `Short_URL_Admin`.
- 🛠️ Fixed `file_get_contents` warning by passing correct full path to `Short_URL_Updater`.
- 🪵 Added more detailed logging during table creation and verification.

### ✨ What's New & Fixed
- **Reliable Table Creation!** 🐛 Switched from using `dbDelta` to direct `CREATE TABLE` queries via `$wpdb->query()` in the activator. This resolves issues where `dbDelta` reported success but failed to create tables in some environments.
- **AJAX Slug Generation Fix!** ⚡ Added the missing `ajax_generate_slug` method to the `Short_URL_Admin` class, resolving a fatal error when trying to generate slugs via AJAX in the admin area.
- **Updater Path Fix!** 🛠️ Corrected a persistent PHP warning by ensuring the `Short_URL_Updater` class is instantiated with the full path to the main plugin file (`__FILE__`) instead of the relative basename.
- **Enhanced Diagnostics!** 🪵 Added more specific logging messages during the table creation process within the activator to better diagnose potential failures (e.g., database permission issues).

### 🎵 Behind the Scenes
This release tackles a persistent and tricky bug where database tables weren't being created correctly during activation or verification. By switching to direct SQL queries, we ensure more reliable table setup. We also fixed a fatal AJAX error and a persistent path warning in the updater initialization, making the plugin significantly more stable.


## [1.2.9.5] Final Initialization & Logging Fixes

- 🛠️ Moved updater initialization to the `init` hook to resolve translation timing notice.
- 🪵 Improved activator logging for `dbDelta` and `add_cap` results.
- 🧹 Removed potentially misleading internal verification checks from activator methods.

### ✨ What's New & Fixed
- **Translation Timing Fix!** 🛠️ Moved the plugin updater initialization to the `init` hook (priority 20) to ensure it runs after the text domain is loaded, resolving the final "translation loading too early" notice.
- **Improved Activation Logging!** 🪵 Enhanced logging within the `Short_URL_Activator` class to better report the results of `dbDelta` and capability additions, including potential `$wpdb->last_error` after table creation.
- **Simplified Verification!** 🧹 Removed internal verification checks immediately following table/capability creation within the activator, relying solely on the robust `verify_installation` check hooked to `admin_init` for accuracy.

### 🎵 Behind the Scenes
This release addresses the persistent translation loading notice by ensuring the updater class initializes at the correct point in the WordPress lifecycle. It also refines activation logging for better debugging and simplifies the verification process by removing redundant internal checks.

## [1.2.9.4] Initialization and Visibility Fixes

- 🐛 Fixed fatal error calling private activator methods during verification checks.
- 🛠️ Fixed persistent "translation loading too early" notice by delaying admin class instantiation.

### ✨ What's New & Fixed
- **Fatal Error Fix!** 🐛 Corrected visibility of `Short_URL_Activator` methods (`create_database_tables`, `create_capabilities`) to `public static` so they can be called correctly by the verification routine.
- **Translation Timing Fix!** 🛠️ Delayed the instantiation of admin-specific classes (`Short_URL_Admin`, `Short_URL_Gutenberg`) until the `init` hook to ensure the text domain is loaded first, resolving the persistent "translation loading too early" notice.

### 🎵 Behind the Scenes
Addressed a fatal error caused by incorrect method visibility and resolved the remaining translation timing issue by ensuring admin classes are loaded after WordPress initialization is complete and translations are ready.

## [1.2.9.3] Update Modal Fix

- 🐛 Fixed "View version details" modal showing "closed" if changelog fetch failed.

### ✨ What's New & Fixed
- **Update Modal Robustness!** 🐛 Made the "View version details" display more resilient by only including changelog/release notes sections if the content was successfully fetched from GitHub.

### 🎵 Behind the Scenes
Improved the updater's `plugin_info` method to handle cases where fetching changelog files from GitHub might fail, preventing a broken display in the WordPress update modal.

## [1.2.9.2] Translation Timing Fix

- 🛠️ Fixed persistent "translation loading too early" notice related to manual update checks.

### ✨ What's New & Fixed
- **Translation Timing Fix!** 🛠️ Refactored the manual update check logic to ensure translatable strings are only processed during the `admin_notices` hook, resolving the persistent "translation loading too early" notice.

### 🎵 Behind the Scenes
Addressed a stubborn translation timing issue by separating the manual update check trigger (on `admin_init`) from the notice display (on `admin_notices`), ensuring all translation functions execute at the appropriate time in the WordPress load sequence.

## [1.2.9.1] "Copy That" 📋

- 🐛 Fixed copy-to-clipboard button in post editor meta box.

### ✨ What's New & Fixed
- **Copy Button Fix!** 📋 Corrected an issue where the copy-to-clipboard button in the Short URL meta box on the post edit screen wasn't working due to a missing JavaScript library.

### 🎵 Behind the Scenes
A quick patch to ensure the ClipboardJS library is loaded correctly on post edit screens, restoring the functionality of the copy button in the meta box.

## [1.2.9] "Robust Activation" 🛡️

- ✅ Added installation verification routine to check DB tables & capabilities on admin load.
- 🐛 Fixed potential activation failures causing missing tables or capabilities.
- 🪵 Enhanced error logging during activation and database operations.

### ✨ What's New & Fixed
- **Robust Activation!** ✅ Added an installation verification routine that runs on `admin_init`. It checks if required database tables and administrator capabilities exist, attempting to fix them automatically if missing.
- **Activation Failure Fix!** 🐛 Addressed issues where plugin activation could fail silently, leading to missing database tables or capabilities for administrators.
- **Better Diagnostics!** 🪵 Enhanced error logging during the activation process and for database operations (like URL creation) to provide clearer information when troubleshooting.

### 🎵 Behind the Scenes
We've significantly improved the plugin's activation process. A new verification step ensures that essential components like database tables and user permissions are correctly set up *after* activation, automatically attempting repairs if needed. This prevents common setup problems reported by users and makes the plugin more resilient. We've also added more detailed logging to help diagnose any future issues quickly.

## [1.2.8] "Clear Headers" 🛠️

- 🛠️ Fixed "headers already sent" error in group management
- 🎨 Simplified post edit screen UI for better usability
- 🔄 Fixed empty destination URL when editing short URLs
- 🌍 Improved translation loading with better error handling
- 📝 Implemented more professional version naming convention

### ✨ What's New & Fixed
- **Header Handling Improvements!** 🛠️ Fixed the "headers already sent" error in group management
- **Streamlined UI!** 🎨 Simplified the post edit screen for a cleaner, more intuitive experience
- **URL Editing Fix!** 🔄 Resolved the issue where destination URLs were empty when editing
- **Translation Enhancements!** 🌍 Improved translation system with better error handling
- **Professional Versioning!** 📝 Introduced a more professional version naming convention

### 🎵 Behind the Scenes
We've made significant improvements to the codebase, fixing several important issues that were affecting usability. The form processing in group management now happens before any HTML output, preventing those pesky header warnings. We've also improved URL data retrieval with better error checking and enhanced the translation system with proper error logging and fallbacks. Plus, we've implemented a more professional version naming convention with descriptive codenames!

## [1.2.7] "UI Refresh" 🎨

- 🐛 Fixed "headers already sent" error in group management
- 🎨 Simplified post edit screen UI for better usability
- 🔄 Fixed empty destination URL when editing short URLs
- 🌍 Improved translation loading with better error handling
- 📝 Implemented better version naming convention with codenames

### ✨ What's New & Fixed
- **No More Header Warnings!** 🚫 Fixed the annoying "headers already sent" error in group management
- **Cleaner UI!** 🧹 Simplified the post edit screen for a more intuitive experience
- **Working Edits!** 🔄 Fixed the issue where destination URLs were empty when editing
- **Better Translations!** 🌍 Enhanced translation system with improved error handling and debugging
- **Descriptive Versions!** 📝 Implemented a more meaningful version naming convention with codenames

### 🎵 Behind the Scenes
We've made significant improvements to the codebase, fixing several important issues that were affecting usability. The form processing in group management now happens before any HTML output, preventing those pesky header warnings. We've also improved URL data retrieval with better error checking and enhanced the translation system with proper error logging and fallbacks. Plus, we've implemented a more descriptive version naming convention to make it easier to reference specific releases!

## [1.2.6] "Smooth Updater" 🔧

- 🐛 Fixed translation loading error in WordPress 6.7
- 🎨 Improved release notes display in plugin update details
- 🔄 Enhanced changelog formatting for better readability

### ✨ What's New & Fixed
- **Translation Fix!** 🌍 Properly moved text domain loading to the init hook with correct priority
- **Better Updates!** 📝 Improved how release notes are displayed when viewing plugin update details
- **Prettier Changelog!** 🎨 Enhanced formatting of changelog and release notes with better styling

### 🎵 Behind the Scenes
We've fixed the translation loading issue that was causing warnings in WordPress 6.7 and improved how update information is displayed. Now when you click "View version details" you'll see a beautifully formatted changelog with all the information you need about the update!

## [1.2.5] "QR Connect" 📱

- 🔄 Replaced Google Charts API with QR Server API for QR code generation
- 🎨 Redesigned QR code modal with modern UI/UX
- 🔧 Fixed QR code download and print functionality
- 🌍 Added internationalization for all QR code related text

### ✨ What's New & Fixed
- **Reliable QR Codes!** 🔄 Fixed 404 errors by switching to a more reliable QR code generation service
- **Modern Design!** 🎨 Completely redesigned QR code modal with beautiful styling and improved user experience
- **More Options!** 🎛️ Added size and format selection for QR codes to fit your specific needs
- **Print Support!** 🖨️ Added ability to print QR codes directly from the modal
- **Fully Localized!** 🌍 All QR code functionality now supports translations for global users

### 🎵 Behind the Scenes
We've completely overhauled the QR code system to provide a more reliable and user-friendly experience. The new QR code modal is not only more visually appealing but also offers more functionality with size and format options, making it easier to customize your QR codes for different use cases.

## [1.2.4] "CompatMaster" 🛠️

- 🐛 Fixed translation loading too early warning in WordPress 6.7
- 🔧 Improved compatibility with the latest WordPress version

### ✨ What's New & Fixed
- **WordPress 6.7 Compatibility!** 🐛 Fixed the translation loading warning by properly moving text domain loading to the init hook
- **Cleaner Code!** 🧹 Removed early translation function calls that were causing warnings
- **Better Performance!** ⚡ Improved plugin initialization to be more compatible with the latest WordPress version

### 🎵 Behind the Scenes
We've made the plugin work more harmoniously with WordPress 6.7, ensuring a smooth experience for site administrators. These changes might seem small, but they eliminate those annoying warnings and make your WordPress dashboard cleaner!

## [1.2.3] "FlagMaster" 🌍

- 🌍 Fixed flag display by converting 2-letter country codes to 3-letter codes
- 🔄 Improved country flag display in analytics

## [1.2.2] "UpdateMaster" 🔄

- 🔄 Improved update system with simplified notifications
- 📊 Enhanced changelog formatting in update screen
- 🔄 Added automatic page refresh after update notifications
- 📚 Updated documentation

## [1.2.1] "GeoMaster" 🌍

- 🌍 Added country flags to analytics
- 📊 Improved analytics display
- 🔧 Fixed minor bugs

## [1.2.0] "AnalyticsPro" 📊

- 📊 Enhanced analytics with device detection
- 📱 Added browser and OS tracking
- 🌍 Added geolocation for better insights
- 🔍 Improved search functionality
- 🔧 Various bug fixes and improvements

## [1.1.0] "GroupMaster" 👥

- 👥 Added link groups for better organization
- 🔄 Improved redirect handling
- 🔧 Fixed minor bugs
- ⚡ Performance improvements

## [1.0.0] "Initial Release" 🚀

- 🔗 Basic URL shortening functionality
- 📊 Simple click tracking
- 🎨 Clean, modern admin interface
- 🔒 Secure and efficient redirects

## [1.2.2] "UpdateMaster" 🔄

### ✨ What's New & Fixed
- **Prettier Changelog!** 📋 Fixed changelog formatting in the WordPress update screen for better readability
- **Smarter Updates!** 🧠 Enhanced the update notification system to be cleaner and more user-friendly
- **Smooth Refreshing!** 🔄 The plugins page now refreshes automatically after clicking OK on the update notification
- **Better Compatibility!** 🔧 Fixed compatibility issues with various WordPress versions and setups

### 🎵 Behind the Scenes
We're constantly polishing the user experience, and these improvements make your update process smooth as silk. Simple changes, big impact!

## [1.2.1] "GeoMaster" 🌍

### ✨ What's New & Fixed
- **QR Codes Work Again!** 📱 Fixed that pesky undefined `get_base_url()` method that was breaking your QR codes
- **Geography Superpowers!** 🌍 Added MaxMind GeoIP integration so your analytics knows exactly where those clicks are coming from
- **Eye Candy Alert!** 🏳️ Beautiful SVG country flags make your analytics page look professional and vibrant
- **Pretty Updates!** 📝 Plugin update information now looks cleaner and easier to read (because we care about your eyes!)

### 🎵 Behind the Scenes
We've been working hard to make your URL shortening experience smoother than ever. These improvements might seem small, but they make a big difference in daily use!

## [1.2.0]

### Fixed
- Fixed analytics page issues with country flags by using local SVG files instead of external services
- Fixed undefined variable `$avg_clicks_per_day` in analytics-detail.php
- Fixed potential fatal error with `array_keys()` being called on null values
- Improved error handling for empty data arrays in chart generation
- Enhanced display of country flags with proper sizing and borders
- Fixed IP address display in analytics detail view

## [1.1.19]

### Fixed
- Fixed QR code functionality that was failing to load due to JavaScript errors
- Corrected the references to JavaScript variables and method calls in admin scripts
- Fixed incorrect static method call in QR code generator

## [1.1.18]

### Fixed
- Fixed GitHub updater repository URL to correctly point to tomrobak/short-url instead of tomrobert/short-url
- Resolved issue with automatic updates failing due to 404 errors from GitHub API

## [1.1.17]

### Fixed
- Fixed PHP warning about undefined property `ip_address` in analytics-detail.php
- Fixed missing country flag images by using an external flag API
- Improved layout of analytics detail page for better readability 
- Enhanced display of metrics (total clicks, avg clicks/day, unique visitors, top referrer)
- Reorganized analytics sections in a grid layout for better user experience
- Fixed responsive design issues on smaller screens

## [1.1.16]

### Fixed
- Further improvements to fix text domain loading issue by removing all translation functions from early plugin initialization
- Addressed persistent WordPress 6.7+ warning about translations loading too early

## [1.1.15]

### Fixed
- Fixed text domain loading being triggered too early by moving it to the init hook
- Addresses WordPress 6.7+ warning: "Translation loading for the short-url domain was triggered too early"

## [1.1.14]

### Fixed
- Fixed fatal error with class-short-url.php include
- Fixed fatal error in analytics-detail.php with undefined method Short_URL_Utils::get_short_url()
- Fixed PHP warning with undefined property stdClass::$short_url
- Improved short URL box design on post edit screen for better visibility
- Enhanced UI for short URL display in the editor
- Added better CSS styling for the public-facing short URL block
- Improved plugin packaging to ensure correct directory structure

## [1.1.13]

### Fixed
- Fixed automatic update detection from GitHub
- Improved update mechanism to properly detect and install new versions
- Reduced update cache time to check more frequently for new releases
- Enhanced error handling and logging for update processes
- Fixed package URL construction for GitHub releases

## [1.1.12]

### Fixed
- Fixed fatal error in AJAX handling related to missing get_url_by_post_id method
- Fixed charts container growing infinitely in height on analytics pages
- Improved chart rendering with proper height constraints and aspect ratio handling

## [1.1.11]

### Fixed
- Fixed header modification error when activating/deactivating URLs via AJAX
- Enhanced Short URL block display with improved styling and visibility
- Fixed critical error on the analytics detail page for specific URLs
- Optimized chart rendering to prevent performance issues with requestAnimationFrame
- Improved error handling in analytics data processing
- Added data optimization for charts to improve performance on pages with many data points

## [1.1.10]

### Fixed
- Fixed GitHub updater to properly detect and install updates from GitHub releases
- Added more robust error handling and logging for update checks
- Improved the "Check for updates" button functionality with better UI feedback
- Reduced GitHub API request cache time to ensure updates are detected faster
- Added detailed debugging information to help troubleshoot update issues

## [1.1.9]

### Changed
- Switched from version-specific release notes to a consolidated RELEASE-NOTES.md file
- Fixed various UI issues reported by users
- Enhanced QR code generation functionality
- Improved analytics page rendering
- Fixed deactivation notice incorrectly showing during activation

## [1.1.8]

### Fixed
- Fixed "Check for updates" functionality to properly show popup messages
- Added missing `clear_cache` method for better update detection
- Improved update checking with AJAX for a smoother user experience
- Added confirmation dialog when updates are available

## [1.1.7]

### Fixed
- Fixed chart initialization conflict that was causing "Canvas is already in use" errors
- Added checks to prevent duplicate chart initialization
- Improved error handling for Chart.js initialization
- Enhanced compatibility with different Chart.js versions

## [1.1.6]

### Fixed
- Fixed JavaScript syntax error in short-url-admin.js where the initializeAnalytics function was missing a closing brace
- Fixed settings page that wasn't saving settings properly by improving form submission handling
- Fixed analytics page issues by adding better error handling and support for different data formats
- Added robust error checking for chart initialization to prevent JavaScript errors
- Improved overall code quality and stability

## [1.1.5]

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

## [1.1.4]

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

## [1.1.3]

### Added
- Bulk action for generating shortlinks for multiple posts at once
- Support for bulk generating shortlinks for any enabled post type

### Fixed
- "Check for updates" functionality now properly displays update status
- Improved GitHub update checker with clearer notifications

## [1.1.2]

### Fixed
- Fixed URL list table errors related to property access on arrays
- Ensured consistent handling of database results as objects

## [1.1.1]

### Fixed
- Settings page registration issue that prevented saving options
- Division by zero error in list tables pagination
- Missing global analytics method causing fatal error on analytics page
- Incorrect GitHub repository and documentation links

## [1.1.0]

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

## [1.0.0]

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