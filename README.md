# TranslatePress - Translation Map Manager

A WordPress plugin that provides custom translation mappings for TranslatePress, with special support for AJAX-loaded content.

## Description

TranslatePress Translation Map Manager allows you to create custom translation mappings that work with dynamically loaded content. It's perfect for single-page applications, AJAX-loaded content, and other dynamic elements that traditional translation plugins might miss.

### Key Features

- **Custom Translation Mappings**: Create direct translations for specific text strings
- **AJAX Support**: Automatically translates dynamically loaded content
- **Language Detection**: Works with TranslatePress language detection
- **Easy Management**: User-friendly admin interface for managing translations
- **Import/Export**: Bulk import and export translations
- **Real-time Updates**: No page refreshes needed in the admin interface

## Installation

1. Upload the `translatepress-translate-map` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > TranslatePress Map to manage translations

## Usage

### Adding Translations

1. Navigate to Settings > TranslatePress Map
2. Click on the "Add Translation" tab
3. Enter the original text, translated text, and select the language
4. Click "Add Translation"

### Managing Translations

1. Go to the "Manage Translations" tab
2. Use the search and filter options to find translations
3. Edit or delete translations as needed

### Importing/Exporting Translations

1. Navigate to the "Import/Export" tab
2. For importing:
   - Paste translations in the format: `Original|Translation|Language`
   - Click "Import"
3. For exporting:
   - Select the language
   - Click "Export"
   - Download the CSV file

### Settings

1. Go to the "Settings" tab
2. Enable/disable frontend translation
3. Set translation priority

## Frontend Usage

The plugin automatically translates content when the page language matches one of your configured languages. For AJAX-loaded content, the plugin uses a MutationObserver to detect and translate new content as it's added to the page.

### Example

When a user visits your Arabic version of the site:

```html
<!-- Original English content -->
<div id="dashboard-menu">Upcoming</div>

<!-- Automatically translated to Arabic -->
<div id="dashboard-menu">القادمة</div>
```

## Technical Details

### Database Structure

The plugin creates a custom table `wp_trp_translate_map` with the following structure:

- `id` (int): Primary key
- `original_text` (text): The original text to translate
- `translated_text` (text): The translated text
- `language_code` (varchar): The language code (e.g., 'ar', 'en_US')
- `status` (enum): 'active' or 'inactive'
- `created_at` (datetime): When the translation was created
- `updated_at` (datetime): When the translation was last updated

### Hooks

The plugin provides several hooks for developers:

- `trp_tm_before_add_translation`: Fires before adding a translation
- `trp_tm_after_add_translation`: Fires after adding a translation
- `trp_tm_before_update_translation`: Fires before updating a translation
- `trp_tm_after_update_translation`: Fires after updating a translation
- `trp_tm_before_delete_translation`: Fires before deleting a translation
- `trp_tm_after_delete_translation`: Fires after deleting a translation

### JavaScript API

For advanced users, the plugin exposes a JavaScript API:

```javascript
// Manually translate a string
const translated = TrpTranslateMap.translate('Hello', 'ar');
console.log(translated); // مرحبا

// Force retranslation of dynamic content
TrpTranslateMap.retranslateContent(document.querySelector('#dynamic-content'));
```

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- TranslatePress plugin (recommended but not required)

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by [Developerjillur](https://github.com/developerjillur) 