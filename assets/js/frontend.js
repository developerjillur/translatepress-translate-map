// TranslatePress Translation Map Frontend JavaScript
// This file handles dynamic CSS loading and language switching for the translation map

jQuery(document).ready(function ($) {
  "use strict";

  // Check if frontend object exists
  if (typeof trpTmFrontend === "undefined") {
    return;
  }

  // Initialize TRP Translation Map Frontend
  var TRPTMFrontend = {
    currentLanguage: trpTmFrontend.current_language,
    defaultLanguage: trpTmFrontend.default_language,
    translations: trpTmFrontend.translations,
    ajaxUrl: trpTmFrontend.ajax_url,
    nonce: trpTmFrontend.nonce,
    cssLoadedLanguages: [],

    init: function () {
      console.log("🚀 TranslatePress Translation Map Frontend Initializing...");

      // Update current language from page
      this.currentLanguage = this.getCurrentLanguageFromPage();

      console.log("🌐 Detected Language:", this.currentLanguage);
      console.log("🏠 Default Language:", this.defaultLanguage);

      this.detectLanguageChange();
      this.bindEvents();

      // Load CSS for initial language
      this.loadLanguageCSS();

      console.log("✅ TranslatePress Translation Map Frontend Loaded");
      if (this.currentLanguage !== this.defaultLanguage) {
        console.log(
          "📝 Available Translations:",
          Object.keys(this.translations).length
        );
      }
    },

    /**
     * Detect language changes (for dynamic language switching)
     */
    detectLanguageChange: function () {
      var self = this;
      var currentUrl = window.location.href;

      // Check for language changes in URL
      setInterval(function () {
        if (window.location.href !== currentUrl) {
          currentUrl = window.location.href;
          self.handleLanguageChange();
        }
      }, 1000);

      // Watch for html lang attribute changes
      if (window.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            if (
              mutation.type === "attributes" &&
              mutation.attributeName === "lang"
            ) {
              self.handleLanguageChange();
            }
          });
        });

        observer.observe(document.documentElement, {
          attributes: true,
          attributeFilter: ["lang"],
        });
      }
    },

    /**
     * Handle language change events
     */
    handleLanguageChange: function () {
      // Get language from multiple sources for better detection
      var newLanguage = this.getCurrentLanguageFromPage();

      if (newLanguage !== this.currentLanguage) {
        console.log(
          "Language changed from",
          this.currentLanguage,
          "to",
          newLanguage
        );

        // Clear loaded languages to force reload
        this.cssLoadedLanguages = [];
        this.currentLanguage = newLanguage;

        // Load CSS for new language
        this.loadLanguageCSS();
        this.updateTranslations();
      }
    },

    /**
     * Get current language from various page sources
     */
    getCurrentLanguageFromPage: function () {
      // Try multiple methods to detect current language
      var language = null;

      // Method 1: HTML lang attribute
      if (document.documentElement.lang) {
        language = document.documentElement.lang;
      }

      // Method 2: Check URL for language code
      if (!language) {
        var urlPath = window.location.pathname;
        var pathParts = urlPath.split("/").filter(function (part) {
          return part.length > 0;
        });

        // Common language codes that might be in URL
        var commonLangCodes = [
          "en",
          "ar",
          "es",
          "fr",
          "de",
          "it",
          "pt",
          "ru",
          "zh",
          "ja",
          "ko",
        ];

        for (var i = 0; i < pathParts.length; i++) {
          if (commonLangCodes.indexOf(pathParts[i]) !== -1) {
            language = pathParts[i];
            break;
          }
        }
      }

      // Method 3: Check for TranslatePress language in URL params
      if (!language) {
        var urlParams = new URLSearchParams(window.location.search);
        var trpLang = urlParams.get("trp-edit-translation");
        if (trpLang) {
          language = trpLang;
        }
      }

      // Method 4: Check body class for language
      if (!language) {
        var bodyClasses = document.body.className.split(" ");
        for (var i = 0; i < bodyClasses.length; i++) {
          if (bodyClasses[i].startsWith("lang-")) {
            language = bodyClasses[i].replace("lang-", "");
            break;
          }
        }
      }

      // Fallback to default language
      return language || this.defaultLanguage;
    },

    /**
     * Load CSS for current language
     */
    loadLanguageCSS: function () {
      var self = this;

      console.log("Attempting to load CSS for language:", this.currentLanguage);

      // Always remove any existing language-specific CSS first
      $("#trp-tm-dynamic-css").remove();
      $("style[data-trp-language]").remove();

      // If default language, don't load any language-specific CSS
      if (this.currentLanguage === this.defaultLanguage) {
        console.log(
          "Default language detected, no language-specific CSS needed"
        );
        this.cssLoadedLanguages = [];
        return;
      }

      // Check if CSS is already loaded for this language
      if (this.cssLoadedLanguages.indexOf(this.currentLanguage) !== -1) {
        console.log("CSS already loaded for language:", this.currentLanguage);
        return;
      }

      console.log("Loading CSS via AJAX for language:", this.currentLanguage);

      // Load CSS for current language via AJAX
      $.ajax({
        url: this.ajaxUrl,
        type: "POST",
        data: {
          action: "trp_tm_get_language_css",
          language_code: this.currentLanguage,
          nonce: this.nonce,
        },
        success: function (response) {
          console.log("📦 CSS AJAX response:", response);

          if (response.success) {
            console.log("🔍 Debug info:", response.data.debug);

            if (response.data.css_content && response.data.css_content.trim()) {
              // Create a unique style element for this language
              var styleElement = $(
                '<style id="trp-tm-dynamic-css" type="text/css" data-trp-language="' +
                  self.currentLanguage +
                  '">'
              ).text(response.data.css_content);

              // Add CSS to head
              $("head").append(styleElement);

              // Mark this language as loaded
              self.cssLoadedLanguages = [self.currentLanguage]; // Only keep current language

              console.log(
                "✅ CSS successfully loaded for language:",
                self.currentLanguage
              );
              console.log(
                "📏 CSS Length:",
                response.data.css_content.length,
                "characters"
              );
              console.log(
                "📄 CSS Preview:",
                response.data.css_content.substring(0, 200) + "..."
              );

              // Verify CSS was added to DOM
              var addedStyle = $("#trp-tm-dynamic-css");
              if (addedStyle.length > 0) {
                console.log("✅ CSS element successfully added to DOM");
              } else {
                console.error("❌ CSS element not found in DOM after adding");
              }
            } else {
              console.log(
                "ℹ️ No CSS content found for language:",
                self.currentLanguage
              );
              console.log("📊 CSS data:", response.data);
            }
          } else {
            console.error("❌ CSS AJAX request failed:", response);
          }
        },
        error: function (xhr, status, error) {
          console.error(
            "❌ Failed to load CSS for language:",
            self.currentLanguage
          );
          console.error("Error details:", status, error);
        },
      });
    },

    /**
     * Update translations for current language
     */
    updateTranslations: function () {
      var self = this;

      // Get translations for new language
      $.ajax({
        url: this.ajaxUrl,
        type: "POST",
        data: {
          action: "trp_tm_get_frontend_translations",
          language_code: this.currentLanguage,
          nonce: this.nonce,
        },
        success: function (response) {
          if (response.success) {
            self.translations = response.data;
            console.log(
              "Translations updated for language:",
              self.currentLanguage
            );
          }
        },
        error: function () {
          console.warn(
            "Failed to load translations for language:",
            self.currentLanguage
          );
        },
      });
    },

    /**
     * Bind events
     */
    bindEvents: function () {
      var self = this;

      // Handle TranslatePress language switcher clicks
      $(document).on(
        "click",
        ".trp-language-switcher a, .trp-ls-language-name",
        function () {
          console.log("Language switcher clicked");
          setTimeout(function () {
            self.handleLanguageChange();
          }, 500); // Increased timeout to ensure DOM updates
        }
      );

      // Handle any other language switcher
      $(document).on(
        "click",
        'a[href*="trp-edit-translation"], a[data-trp-language]',
        function () {
          console.log("Language link clicked");
          setTimeout(function () {
            self.handleLanguageChange();
          }, 500);
        }
      );

      // Handle page navigation that might change language
      $(window).on("popstate", function () {
        setTimeout(function () {
          self.handleLanguageChange();
        }, 200);
      });
    },
  };

  // Initialize when DOM is ready
  TRPTMFrontend.init();

  // Make it globally accessible
  window.TRPTMFrontend = TRPTMFrontend;

  // Add debugging functions to window for testing
  window.trpTmDebug = {
    getCurrentLanguage: function () {
      return TRPTMFrontend.currentLanguage;
    },
    getLoadedLanguages: function () {
      return TRPTMFrontend.cssLoadedLanguages;
    },
    forceReloadCSS: function () {
      TRPTMFrontend.cssLoadedLanguages = [];
      TRPTMFrontend.loadLanguageCSS();
    },
    testLanguageSwitch: function (langCode) {
      console.log("🧪 Testing language switch to:", langCode);
      TRPTMFrontend.currentLanguage = langCode;
      TRPTMFrontend.cssLoadedLanguages = [];
      TRPTMFrontend.loadLanguageCSS();
    },
  };

  console.log("🔧 Debug functions available: window.trpTmDebug");
});
