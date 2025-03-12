# Short URL for WordPress

A modern URL shortener with analytics, custom domains, and more for WordPress websites. The fastest way to link without sacrificing your brand or analytics!

## Features

- 🔗 Create branded short URLs for your WordPress site
- 📊 Track clicks and analyze visitor data
- 📱 Mobile-friendly with responsive design
- 🧩 Gutenberg block for easy embedding
- 🔒 Password protect links
- 📅 Set expiration dates for links
- 👥 Multiple user support with different permission levels
- 🌐 Custom domain support
- 📂 Organize links into groups
- 🔄 Import/export functionality
- ⚡ High performance with minimal impact on site speed
- 🧠 Smart redirection with parameter forwarding
- 📱 QR code generation
- 🛡️ GDPR compliant with data anonymization options

## Requirements

- WordPress 6.7 or higher
- PHP 8.0 or higher
- MySQL 5.6 or higher

## Installation

### Automatic Installation

1. Log in to your WordPress admin panel and go to "Plugins" → "Add New"
2. Search for "Short URL"
3. Click "Install Now" and then "Activate"

### Manual Installation

1. Download the plugin ZIP file from the [releases page](https://github.com/tomrobak/short-url/releases)
2. Log in to your WordPress admin panel and go to "Plugins" → "Add New" → "Upload Plugin"
3. Choose the downloaded ZIP file and click "Install Now"
4. After installation, click "Activate Plugin"

## Usage

### Creating Short URLs

1. Go to "Short URL" → "Add New" in your WordPress admin panel
2. Enter the destination URL you want to shorten
3. Customize the slug or let the plugin generate one automatically
4. Add a title and description (optional)
5. Configure additional options like expiration date, password protection, etc.
6. Click "Create Short URL"

### Managing Short URLs

- View all your short URLs at "Short URL" → "All URLs"
- Edit, delete, or deactivate URLs as needed
- View click statistics by clicking on "Stats" for each URL

### Bulk Actions for Posts

- Select multiple posts in the WordPress post list
- Choose "Generate Shortlinks" from the Bulk Actions dropdown
- Click "Apply" to generate short URLs for all selected posts
- Works with any post type that has shortlinks enabled in the settings

### Embedding Short URLs in Content

- Use the Short URL block in the Gutenberg editor
- Alternatively, use the shortcode `[short-url id="123"]` where 123 is the ID of your short URL

### Analytics

- View overall statistics at "Short URL" → "Analytics"
- See detailed analytics for each URL by clicking on "Stats"
- Track visits, browsers, devices, locations, and more

### Organizing URLs

- Create groups at "Short URL" → "Groups"
- Assign URLs to groups when creating or editing them
- Filter URLs by group on the "All URLs" page

### Tools and Settings

- Configure plugin settings at "Short URL" → "Settings"
- Import/export URLs at "Short URL" → "Tools"
- Check for updates automatically or manually

## WooCommerce Integration

The plugin seamlessly integrates with WooCommerce to create short URLs for your products. This allows for:

- Tracking product link clicks
- Creating shorter, more shareable product links
- Including product links in marketing campaigns with analytics

## API Documentation

Short URL provides a REST API for developers to interact with the plugin programmatically. Documentation for the API endpoints can be found in the [Wiki](https://github.com/tomrobak/short-url/wiki/API-Documentation).

## Staying Updated

The plugin checks for updates automatically and can be updated through the WordPress admin panel. You can also:

1. Follow the [GitHub repository](https://github.com/tomrobak/short-url) for code updates
2. Visit [wplove.co](https://wplove.co/) for news and tutorials

## Support

If you need help with the plugin, you can:

- Check the [documentation](https://github.com/tomrobak/short-url/wiki)
- Open an [issue](https://github.com/tomrobak/short-url/issues) on GitHub
- Contact [wplove.co](https://wplove.co/) for premium support

## Contributing

We welcome contributions to the Short URL plugin! Please feel free to submit pull requests or open issues on GitHub.

## License

Short URL is licensed under the [GPL v2 or later](LICENSE).

## Credits

Developed by [wplove.co](https://wplove.co/) - WordPress for photographers
