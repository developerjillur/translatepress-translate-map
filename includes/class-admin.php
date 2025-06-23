<?php

/**
 * Admin interface for TranslatePress Translation Map
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TRP_TM_Admin
{

    private $database;
    private $trp_settings;

    public function __construct()
    {
        $this->database = TRP_Translate_Map::get_instance()->get_database();

        // Get TranslatePress settings
        if (class_exists('TRP_Translate_Press')) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $trp_settings_obj = $trp->get_component('settings');
            $this->trp_settings = $trp_settings_obj->get_settings();
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'options-general.php',
            __('Translation Map Manager', 'trp-translate-map'),
            __('Translation Map', 'trp-translate-map'),
            'manage_options',
            'trp-translate-map',
            array($this, 'admin_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        if ('settings_page_trp-translate-map' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'trp-tm-admin',
            TRP_TM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            TRP_TM_PLUGIN_VERSION,
            true
        );

        wp_enqueue_style(
            'trp-tm-admin',
            TRP_TM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TRP_TM_PLUGIN_VERSION
        );

        wp_localize_script('trp-tm-admin', 'trpTmAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('trp_tm_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this translation?', 'trp-translate-map'),
                'saving' => __('Saving...', 'trp-translate-map'),
                'saved' => __('Saved!', 'trp-translate-map'),
                'error' => __('Error occurred!', 'trp-translate-map')
            )
        ));
    }

    /**
     * Admin page content
     */
    public function admin_page()
    {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'manage';
?>
        <div class="wrap">
            <h1><?php _e('Translation Map Manager', 'trp-translate-map'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=trp-translate-map&tab=manage" class="nav-tab <?php echo $active_tab === 'manage' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Manage Translations', 'trp-translate-map'); ?>
                </a>
                <a href="?page=trp-translate-map&tab=add" class="nav-tab <?php echo $active_tab === 'add' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Add Translation', 'trp-translate-map'); ?>
                </a>
                <a href="?page=trp-translate-map&tab=css" class="nav-tab <?php echo $active_tab === 'css' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Custom CSS', 'trp-translate-map'); ?>
                </a>
                <a href="?page=trp-translate-map&tab=import" class="nav-tab <?php echo $active_tab === 'import' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Import/Export', 'trp-translate-map'); ?>
                </a>
                <a href="?page=trp-translate-map&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Settings', 'trp-translate-map'); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'add':
                        $this->render_add_tab();
                        break;
                    case 'css':
                        $this->render_css_tab();
                        break;
                    case 'import':
                        $this->render_import_tab();
                        break;
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    default:
                        $this->render_manage_tab();
                        break;
                }
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Render manage translations tab
     */
    private function render_manage_tab()
    {
        $translations = $this->database->get_all_translations();
        $language_counts = $this->database->get_translation_count_by_language();
    ?>
        <div class="trp-tm-manage-tab">
            <div class="trp-tm-stats">
                <h3><?php _e('Translation Statistics', 'trp-translate-map'); ?></h3>
                <div class="trp-tm-stats-grid">
                    <?php foreach ($language_counts as $lang_count): ?>
                        <div class="trp-tm-stat-item">
                            <strong><?php echo esc_html($this->get_language_name($lang_count['language_code'])); ?></strong>
                            <span><?php echo intval($lang_count['count']); ?> <?php _e('translations', 'trp-translate-map'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="trp-tm-search">
                <input type="text" id="trp-tm-search" placeholder="<?php _e('Search translations...', 'trp-translate-map'); ?>">
                <select id="trp-tm-language-filter">
                    <option value=""><?php _e('All languages', 'trp-translate-map'); ?></option>
                    <?php foreach ($this->get_available_languages() as $code => $name): ?>
                        <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="trp-tm-translations-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Original Text', 'trp-translate-map'); ?></th>
                            <th><?php _e('Translation', 'trp-translate-map'); ?></th>
                            <th><?php _e('Language', 'trp-translate-map'); ?></th>
                            <th><?php _e('Updated', 'trp-translate-map'); ?></th>
                            <th><?php _e('Actions', 'trp-translate-map'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="trp-tm-translations-tbody">
                        <?php foreach ($translations as $translation): ?>
                            <tr data-id="<?php echo intval($translation['id']); ?>">
                                <td><?php echo esc_html($translation['original_text']); ?></td>
                                <td><?php echo esc_html($translation['translated_text']); ?></td>
                                <td><?php echo esc_html($this->get_language_name($translation['language_code'])); ?></td>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($translation['updated_at']))); ?></td>
                                <td>
                                    <button class="button button-small trp-tm-edit-btn" data-id="<?php echo intval($translation['id']); ?>">
                                        <?php _e('Edit', 'trp-translate-map'); ?>
                                    </button>
                                    <button class="button button-small trp-tm-delete-btn" data-id="<?php echo intval($translation['id']); ?>">
                                        <?php _e('Delete', 'trp-translate-map'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="trp-tm-edit-modal" class="trp-tm-modal" style="display: none;">
            <div class="trp-tm-modal-content">
                <span class="trp-tm-close">&times;</span>
                <h3><?php _e('Edit Translation', 'trp-translate-map'); ?></h3>
                <form id="trp-tm-edit-form">
                    <input type="hidden" id="edit-translation-id">
                    <p>
                        <label><?php _e('Original Text', 'trp-translate-map'); ?></label>
                        <input type="text" id="edit-original-text" readonly>
                    </p>
                    <p>
                        <label><?php _e('Translation', 'trp-translate-map'); ?></label>
                        <textarea id="edit-translated-text" rows="3"></textarea>
                    </p>
                    <p>
                        <label><?php _e('Language', 'trp-translate-map'); ?></label>
                        <select id="edit-language-code">
                            <?php foreach ($this->get_available_languages() as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <button type="submit" class="button button-primary"><?php _e('Update Translation', 'trp-translate-map'); ?></button>
                        <button type="button" class="button trp-tm-cancel"><?php _e('Cancel', 'trp-translate-map'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    <?php
    }

    /**
     * Render add translation tab
     */
    private function render_add_tab()
    {
    ?>
        <div class="trp-tm-add-tab">
            <h3><?php _e('Add New Translation', 'trp-translate-map'); ?></h3>
            <form id="trp-tm-add-form" class="trp-tm-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="original-text"><?php _e('Original Text', 'trp-translate-map'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="original-text" name="original_text" class="regular-text" required>
                            <p class="description"><?php _e('Enter the original text that needs translation.', 'trp-translate-map'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="translated-text"><?php _e('Translation', 'trp-translate-map'); ?></label>
                        </th>
                        <td>
                            <textarea id="translated-text" name="translated_text" rows="3" class="large-text" required></textarea>
                            <p class="description"><?php _e('Enter the translated text.', 'trp-translate-map'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="language-code"><?php _e('Language', 'trp-translate-map'); ?></label>
                        </th>
                        <td>
                            <select id="language-code" name="language_code" required>
                                <option value=""><?php _e('Select Language', 'trp-translate-map'); ?></option>
                                <?php foreach ($this->get_available_languages() as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Select the target language for translation.', 'trp-translate-map'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Add Translation', 'trp-translate-map'); ?></button>
                </p>
            </form>

            <div class="trp-tm-bulk-add">
                <h3><?php _e('Bulk Add Translations', 'trp-translate-map'); ?></h3>
                <p><?php _e('Add multiple translations at once. One per line in format: Original Text|Translation|Language Code', 'trp-translate-map'); ?></p>
                <form id="trp-tm-bulk-add-form">
                    <textarea id="bulk-translations" rows="10" class="large-text" placeholder="<?php _e('Example:\nUpcoming|القادمة|ar\nProfile|الملف الشخصي|ar\nMy Calendar|تقويمي|ar', 'trp-translate-map'); ?>"></textarea>
                    <p class="submit">
                        <button type="submit" class="button button-secondary"><?php _e('Bulk Add Translations', 'trp-translate-map'); ?></button>
                    </p>
                </form>
            </div>
        </div>
    <?php
    }

    /**
     * Render custom CSS tab
     */
    private function render_css_tab()
    {
        $current_language = isset($_GET['css_lang']) ? sanitize_text_field($_GET['css_lang']) : '';
        $css_content = '';
        $minify_setting = false;

        if ($current_language) {
            $css_content = get_option('trp_tm_custom_css_' . $current_language, '');
            $minify_setting = get_option('trp_tm_minify_css_' . $current_language, false);
        }
    ?>
        <div class="trp-tm-css-tab">
            <h3><?php _e('Custom CSS Editor', 'trp-translate-map'); ?></h3>
            <p class="description">
                <?php _e('Add custom CSS for specific languages. The CSS will be automatically applied when the specific language is active on the frontend.', 'trp-translate-map'); ?>
            </p>

            <div class="trp-tm-css-language-selector">
                <label for="css-language-selector"><?php _e('Select Language:', 'trp-translate-map'); ?></label>
                <select id="css-language-selector" name="css_language">
                    <option value=""><?php _e('Select a language to edit CSS', 'trp-translate-map'); ?></option>
                    <?php foreach ($this->get_available_languages() as $code => $name): ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($current_language, $code); ?>>
                            <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($current_language): ?>
                <div class="trp-tm-css-editor-container">
                    <div class="trp-tm-css-editor-header">
                        <h4>
                            <?php printf(__('Custom CSS for %s', 'trp-translate-map'), $this->get_language_name($current_language)); ?>
                            <span class="trp-tm-css-lang-code">(<?php echo esc_html($current_language); ?>)</span>
                        </h4>
                        <p class="description">
                            <?php printf(__('CSS will be automatically prefixed with: %s', 'trp-translate-map'), '<code>html[lang="' . esc_html($current_language) . '"]</code>'); ?>
                        </p>
                    </div>

                    <form id="trp-tm-css-form" method="post">
                        <div class="trp-tm-css-editor-wrapper">
                            <div class="trp-tm-css-editor-toolbar">
                                <span class="trp-tm-css-info"><?php _e('CSS Code Editor', 'trp-translate-map'); ?></span>
                                <div class="trp-tm-css-toolbar-buttons">
                                    <button type="button" id="trp-tm-css-format" class="button button-small" title="<?php _e('Format CSS', 'trp-translate-map'); ?>">
                                        <span class="dashicons dashicons-editor-code"></span>
                                    </button>
                                    <button type="button" id="trp-tm-css-clear" class="button button-small" title="<?php _e('Clear CSS', 'trp-translate-map'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>

                            <div class="trp-tm-css-editor">
                                <textarea id="trp-tm-css-editor" name="custom_css" rows="25" class="trp-tm-code-editor" spellcheck="false" placeholder="/* Enter your CSS code here */&#10;.my-class {&#10;    color: #333;&#10;    font-size: 16px;&#10;}&#10;&#10;/* This will be automatically prefixed as: */&#10;/* html[lang='<?php echo esc_attr($current_language); ?>'] .my-class { ... } */"><?php echo esc_textarea($css_content); ?></textarea>
                            </div>
                        </div>

                        <div class="trp-tm-css-options">
                            <label>
                                <input type="checkbox" id="trp-tm-minify-css" name="minify_css" value="1" <?php checked($minify_setting); ?>>
                                <?php _e('Load minified CSS (recommended for production)', 'trp-translate-map'); ?>
                            </label>
                            <p class="description"><?php _e('When enabled, CSS will be minified to reduce file size and improve loading speed.', 'trp-translate-map'); ?></p>
                        </div>

                        <div class="trp-tm-css-actions">
                            <input type="hidden" name="css_language" value="<?php echo esc_attr($current_language); ?>">
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e('Save CSS', 'trp-translate-map'); ?>
                                </button>
                                <button type="button" id="trp-tm-css-validate" class="button button-secondary">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Validate CSS', 'trp-translate-map'); ?>
                                </button>
                            </p>
                        </div>
                    </form>

                    <div class="trp-tm-css-example">
                        <h4><?php _e('Example Usage:', 'trp-translate-map'); ?></h4>
                        <div class="trp-tm-css-example-content">
                            <div class="trp-tm-css-example-input">
                                <strong><?php _e('You write:', 'trp-translate-map'); ?></strong>
                                <pre><code>.jet-search__popup-trigger-container {
    margin-right: 20px;
}
.login-password__wrapper svg {
    left: 15px;
    right: inherit !important;
}</code></pre>
                            </div>
                            <div class="trp-tm-css-example-output">
                                <strong><?php _e('Output CSS:', 'trp-translate-map'); ?></strong>
                                <pre><code>html[lang="<?php echo esc_html($current_language); ?>"] .jet-search__popup-trigger-container {
    margin-right: 20px;
}
html[lang="<?php echo esc_html($current_language); ?>"] .login-password__wrapper svg {
    left: 15px;
    right: inherit !important;
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="trp-tm-css-status" id="trp-tm-css-status" style="display: none;">
                        <div class="notice">
                            <p></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="trp-tm-css-no-language">
                    <p><?php _e('Please select a language above to start editing CSS.', 'trp-translate-map'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php
    }

    /**
     * Render import/export tab
     */
    private function render_import_tab()
    {
    ?>
        <div class="trp-tm-import-tab">
            <div class="trp-tm-two-columns">
                <div class="trp-tm-column">
                    <h3><?php _e('Import Translations', 'trp-translate-map'); ?></h3>
                    <form id="trp-tm-import-form" enctype="multipart/form-data">
                        <p>
                            <label for="import-file"><?php _e('Choose CSV file', 'trp-translate-map'); ?></label>
                            <input type="file" id="import-file" name="import_file" accept=".csv" required>
                        </p>
                        <p class="description">
                            <?php _e('CSV format: Original Text, Translation, Language Code', 'trp-translate-map'); ?>
                        </p>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Import Translations', 'trp-translate-map'); ?></button>
                        </p>
                    </form>
                </div>

                <div class="trp-tm-column">
                    <h3><?php _e('Export Translations', 'trp-translate-map'); ?></h3>
                    <form id="trp-tm-export-form">
                        <p>
                            <label for="export-language"><?php _e('Language', 'trp-translate-map'); ?></label>
                            <select id="export-language" name="export_language">
                                <option value=""><?php _e('All languages', 'trp-translate-map'); ?></option>
                                <?php foreach ($this->get_available_languages() as $code => $name): ?>
                                    <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p class="submit">
                            <button type="submit" class="button button-secondary"><?php _e('Export as CSV', 'trp-translate-map'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Render settings tab
     */
    private function render_settings_tab()
    {
    ?>
        <div class="trp-tm-settings-tab">
            <h3><?php _e('Settings', 'trp-translate-map'); ?></h3>
            <form id="trp-tm-settings-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable-frontend"><?php _e('Enable Frontend Translation', 'trp-translate-map'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable-frontend" name="enable_frontend" value="1" <?php checked(get_option('trp_tm_enable_frontend', 1)); ?>>
                            <p class="description"><?php _e('Enable automatic translation replacement on frontend.', 'trp-translate-map'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="translation-priority"><?php _e('Translation Priority', 'trp-translate-map'); ?></label>
                        </th>
                        <td>
                            <select id="translation-priority" name="translation_priority">
                                <option value="high" <?php selected(get_option('trp_tm_translation_priority', 'high'), 'high'); ?>><?php _e('High (Override TranslatePress)', 'trp-translate-map'); ?></option>
                                <option value="low" <?php selected(get_option('trp_tm_translation_priority', 'high'), 'low'); ?>><?php _e('Low (Fallback only)', 'trp-translate-map'); ?></option>
                            </select>
                            <p class="description"><?php _e('Set whether custom translations should override or supplement TranslatePress translations.', 'trp-translate-map'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Save Settings', 'trp-translate-map'); ?></button>
                </p>
            </form>
        </div>
<?php
    }

    /**
     * Get available languages from TranslatePress
     */
    private function get_available_languages()
    {
        $languages = array();

        if (!empty($this->trp_settings['translation-languages'])) {
            if (class_exists('TRP_Languages')) {
                $trp_languages = new TRP_Languages();
                $language_names = $trp_languages->get_language_names($this->trp_settings['translation-languages']);

                foreach ($this->trp_settings['translation-languages'] as $lang_code) {
                    if (isset($language_names[$lang_code])) {
                        $languages[$lang_code] = $language_names[$lang_code];
                    }
                }
            }
        }

        return $languages;
    }

    /**
     * Get language name by code
     */
    private function get_language_name($language_code)
    {
        $languages = $this->get_available_languages();
        return isset($languages[$language_code]) ? $languages[$language_code] : $language_code;
    }
}
