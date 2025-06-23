<?php

/**
 * Frontend handler for TranslatePress Translation Map
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TRP_TM_Frontend
{

    private $database;
    private $current_language;
    private $trp_settings;

    public function __construct()
    {
        $this->database = TRP_Translate_Map::get_instance()->get_database();

        // Get current language from TranslatePress
        if (class_exists('TRP_Translate_Press')) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $trp_settings_obj = $trp->get_component('settings');
            $this->trp_settings = $trp_settings_obj->get_settings();

            $url_converter = $trp->get_component('url_converter');
            $this->current_language = $url_converter->get_lang_from_url_string();
        }
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts()
    {
        // Only load if frontend translation is enabled
        if (!get_option('trp_tm_enable_frontend', 1)) {
            return;
        }

        wp_enqueue_script(
            'trp-tm-frontend',
            TRP_TM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            TRP_TM_PLUGIN_VERSION,
            true
        );

        // Get translations for current language
        $translations = $this->get_current_language_translations();

        wp_localize_script('trp-tm-frontend', 'trpTmFrontend', array(
            'current_language' => $this->current_language,
            'default_language' => isset($this->trp_settings['default-language']) ? $this->trp_settings['default-language'] : 'en',
            'translations' => $translations,
            'priority' => get_option('trp_tm_translation_priority', 'high'),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('trp_tm_frontend_nonce')
        ));

        // Don't load CSS here - let JavaScript handle it dynamically
        // This prevents double loading when language switching occurs
    }

    /**
     * Load language-specific CSS dynamically
     * Note: This method is now handled by JavaScript to prevent duplicate loading
     * and ensure proper language switching behavior
     */
    public function load_language_css()
    {
        // This method is intentionally left empty
        // CSS loading is now handled entirely by frontend JavaScript
        // to prevent duplicate loading and ensure proper language switching
        return;
    }

    /**
     * Render translation script in footer
     */
    public function render_translation_script()
    {
        // Only render if frontend translation is enabled
        if (!get_option('trp_tm_enable_frontend', 1)) {
            return;
        }

        // Only render for non-default languages
        if ($this->current_language === $this->get_default_language()) {
            return;
        }

        $translations = $this->get_current_language_translations();

        if (empty($translations)) {
            return;
        }

?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Translation map for current language
                const translationMap = <?php echo wp_json_encode($translations); ?>;
                const currentLanguage = '<?php echo esc_js($this->current_language); ?>';
                const priority = '<?php echo esc_js(get_option('trp_tm_translation_priority', 'high')); ?>';

                // Function to apply translations
                function applyTranslations() {
                    if (!translationMap || Object.keys(translationMap).length === 0) {
                        return;
                    }

                    // Apply translations to various elements
                    Object.keys(translationMap).forEach(function(originalText) {
                        const translatedText = translationMap[originalText];

                        // Find elements with exact text match
                        $("*:contains('" + originalText + "')").filter(function() {
                            return $(this).children().length === 0 && $(this).text().trim() === originalText;
                        }).each(function() {
                            // Skip if element has no-translation attribute
                            if ($(this).attr('data-no-translation') || $(this).hasClass('notranslate')) {
                                return;
                            }

                            // Apply translation
                            $(this).text(translatedText);
                            $(this).attr('data-trp-tm-translated', 'true');
                        });

                        // Handle specific selectors
                        const specificSelectors = [
                            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                            '.page-title', '.entry-title',
                            '.menu-item a', '.nav-link',
                            'button', '.button', '.btn',
                            'label', '.form-label',
                            '.widget-title', '.sidebar-title'
                        ];

                        specificSelectors.forEach(function(selector) {
                            $(selector).filter(function() {
                                return $(this).text().trim() === originalText &&
                                    !$(this).attr('data-no-translation') &&
                                    !$(this).hasClass('notranslate');
                            }).text(translatedText).attr('data-trp-tm-translated', 'true');
                        });
                    });
                }

                // Apply translations on page load
                applyTranslations();

                // Apply translations after AJAX requests
                $(document).ajaxComplete(function() {
                    setTimeout(applyTranslations, 100);
                });

                // Watch for DOM changes (for dynamic content)
                if (window.MutationObserver) {
                    const observer = new MutationObserver(function(mutations) {
                        let shouldApply = false;
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                                shouldApply = true;
                            }
                        });

                        if (shouldApply) {
                            setTimeout(applyTranslations, 50);
                        }
                    });

                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }

                // Handle URL changes (for single-page applications)
                let currentUrl = location.href;
                setInterval(function() {
                    if (location.href !== currentUrl) {
                        currentUrl = location.href;
                        setTimeout(applyTranslations, 200);
                    }
                }, 1000);
            });
        </script>
    <?php
    }

    /**
     * Get translations for current language
     */
    private function get_current_language_translations()
    {
        if (!$this->current_language || $this->current_language === $this->get_default_language()) {
            return array();
        }

        $translations = $this->database->get_translations_by_language($this->current_language);
        $translation_map = array();

        foreach ($translations as $translation) {
            $translation_map[$translation['original_text']] = $translation['translated_text'];
        }

        return $translation_map;
    }

    /**
     * Get default language
     */
    private function get_default_language()
    {
        return isset($this->trp_settings['default-language']) ? $this->trp_settings['default-language'] : 'en';
    }

    /**
     * Handle TranslatePress integration
     */
    public function integrate_with_translatepress()
    {
        // Add filter to modify TranslatePress translations
        add_filter('trp_translate_string', array($this, 'override_translatepress_string'), 999, 4);
    }

    /**
     * Override TranslatePress string translation
     */
    public function override_translatepress_string($translated_string, $original_string, $language_code)
    {
        // Check if we have a custom translation
        $custom_translation = $this->database->get_translation($original_string, $language_code);

        if (!empty($custom_translation)) {
            $priority = get_option('trp_tm_translation_priority', 'high');

            // High priority: always use custom translation
            if ($priority === 'high') {
                return $custom_translation;
            }

            // Low priority: only use custom translation if TranslatePress didn't translate
            if ($priority === 'low' && empty($translated_string)) {
                return $custom_translation;
            }
        }

        return $translated_string;
    }

    /**
     * Add CSS for translated elements and custom language CSS
     */
    public function add_frontend_styles()
    {
        // Get all available languages
        $languages = array();
        if (!empty($this->trp_settings['translation-languages'])) {
            $languages = $this->trp_settings['translation-languages'];
        }

    ?>
        <style type="text/css">
            /* Base styles for translated elements */
            [data-trp-tm-translated] {
                /* Optional: Add subtle indicator for translated elements */
            }

            /* RTL support for Arabic and other RTL languages */
            html[lang="ar"] [data-trp-tm-translated],
            html[lang="he"] [data-trp-tm-translated],
            html[lang="fa"] [data-trp-tm-translated],
            html[lang="ur"] [data-trp-tm-translated] {
                direction: rtl;
                text-align: right;
            }

            html[lang="ar"] [data-no-translation],
            html[lang="he"] [data-no-translation],
            html[lang="fa"] [data-no-translation],
            html[lang="ur"] [data-no-translation] {
                direction: ltr !important;
                text-align: left !important;
            }

            <?php
            // Output custom CSS for each language
            foreach ($languages as $language_code) {
                $custom_css = get_option('trp_tm_processed_css_' . $language_code, '');
                if (!empty($custom_css)) {
                    echo "\n/* Custom CSS for " . esc_html($language_code) . " */\n";
                    echo wp_strip_all_tags($custom_css) . "\n";
                }
            }
            ?>
        </style>
<?php
    }
}
