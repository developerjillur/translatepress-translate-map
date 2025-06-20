# TranslatePress - Translation Map Manager

A powerful WordPress plugin that extends TranslatePress functionality with custom translation word mapping capabilities. This plugin allows you to create, manage, and apply custom translation mappings that work seamlessly with TranslatePress multilingual plugin.

## Features

### 🎯 Core Features
- **Custom Translation Mapping**: Create custom word-to-word translations for any language
- **TranslatePress Integration**: Seamlessly integrates with existing TranslatePress setup
- **Language Auto-Detection**: Automatically detects and uses TranslatePress configured languages
- **Dynamic Frontend Translation**: Real-time translation replacement on frontend
- **AJAX-Powered Interface**: Smooth, responsive admin experience

### 📋 Management Features
- **Translation Management**: Add, edit, delete, and search translations
- **Bulk Operations**: Import/export translations via CSV
- **Language Statistics**: View translation counts per language
- **Search & Filter**: Find translations quickly with advanced search
- **Translation Priority**: Set override preferences for TranslatePress conflicts

### 🔧 Technical Features
- **Database Optimization**: Efficient database storage with proper indexing
- **AJAX Dynamic Loading**: Handle dynamic content and single-page applications
- **Translation Caching**: Optimized performance with smart caching
- **RTL Language Support**: Full support for Arabic and other RTL languages
- **Responsive Design**: Mobile-friendly admin interface

## Installation

### Requirements
- WordPress 4.0 or higher
- PHP 5.6 or higher
- TranslatePress plugin (required)

### Installation Steps

1. **Download the Plugin**
   ```
   /wp-content/plugins/translatepress-translate-map/
   ```

2. **Activate Dependencies**
   - Ensure TranslatePress is installed and activated
   - Configure your languages in TranslatePress settings

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Activate "TranslatePress - Translation Map Manager"

4. **Database Setup**
   - Plugin will automatically create required database tables on activation

## Usage

### Quick Start

1. **Access the Plugin**
   - Go to Settings → Translation Map in WordPress admin

2. **Add Your First Translation**
   - Click "Add Translation" tab
   - Enter original text: `Upcoming`
   - Enter translation: `القادمة`
   - Select language: `Arabic`
   - Click "Add Translation"

3. **View on Frontend**
   - Visit your Arabic site
   - The word "Upcoming" will automatically display as "القادمة"

### Bulk Import Example

Use the following format for bulk import:
```
Upcoming|القادمة|ar
Profile|الملف الشخصي|ar
My Calendar|تقويمي|ar
My Appointments|مواعيدي|ar
My Space Booking|حجز المساحة الخاص بي|ar
```

### CSV Import Format
```csv
Original Text,Translation,Language Code
Upcoming,القادمة,ar
Profile,الملف الشخصي,ar
My Calendar,تقويمي,ar
```

## Configuration

### Plugin Settings

Navigate to Settings → Translation Map → Settings:

#### Frontend Translation
- **Enable**: Turn on/off automatic frontend translation
- **Default**: Enabled

#### Translation Priority
- **High**: Override TranslatePress translations
- **Low**: Use only when TranslatePress doesn't have translation
- **Default**: High

### Integration Settings

The plugin automatically detects:
- TranslatePress configured languages
- Current active language
- Default site language

## Developer Information

### Database Schema

The plugin creates one main table:

```sql
CREATE TABLE wp_trp_translate_map (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    original_text varchar(500) NOT NULL,
    translated_text text NOT NULL,
    language_code varchar(10) NOT NULL,
    status varchar(20) DEFAULT 'active',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY original_text (original_text),
    KEY language_code (language_code),
    KEY status (status),
    UNIQUE KEY unique_translation (original_text, language_code)
);
```

### Hooks and Filters

#### Actions
- `trp_tm_translation_added` - Fired when translation is added
- `trp_tm_translation_updated` - Fired when translation is updated
- `trp_tm_translation_deleted` - Fired when translation is deleted

#### Filters
- `trp_tm_frontend_translations` - Modify frontend translations
- `trp_tm_translation_priority` - Modify translation priority
- `trp_translate_string` - TranslatePress integration hook

### JavaScript Integration

#### Frontend Translation Override
```javascript
// Exclude specific elements from translation
$('.no-translate').attr('data-no-translation', '');

// Force translation on specific elements
$('.force-translate').attr('data-trp-tm-translated', 'true');
```

#### Admin Extensions
```javascript
// Custom admin functionality
jQuery(document).on('trp_tm_translation_saved', function(event, translation) {
    console.log('Translation saved:', translation);
});
```

## Troubleshooting

### Common Issues

1. **Translations Not Showing**
   - Ensure TranslatePress is active and configured
   - Check that frontend translation is enabled in settings
   - Verify language codes match TranslatePress configuration

2. **AJAX Errors**
   - Check WordPress debug logs
   - Verify user permissions (manage_options capability required)
   - Ensure nonce verification is working

3. **Performance Issues**
   - Use translation priority "Low" to reduce conflicts
   - Check for JavaScript console errors
   - Ensure proper caching configuration

### Debug Mode

Enable WordPress debug mode to see detailed error logs:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

### Documentation
- [TranslatePress Documentation](https://translatepress.com/docs/)
- [WordPress Plugin Development](https://developer.wordpress.org/plugins/)

### Compatibility
- TranslatePress Multilingual: ✅ Compatible
- TranslatePress Developer Add-ons: ✅ Compatible
- WordPress Multisite: ✅ Compatible
- Popular Themes: ✅ Compatible
- Page Builders (Elementor, etc.): ✅ Compatible

## Changelog

### Version 1.0.0
- Initial release
- Core translation mapping functionality
- TranslatePress integration
- Admin interface with CRUD operations
- Bulk import/export features
- Frontend dynamic translation
- AJAX-powered interface
- RTL language support

## License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## Contributing

Contributions are welcome! Please feel free to submit pull requests or create issues for bugs and feature requests.

---

**Made with ❤️ for the WordPress community** 