jQuery(document).ready(function ($) {
  "use strict";

  // Initialize admin functionality
  initTranslationManagement();
  initModalHandlers();
  initFormHandlers();
  initSearchAndFilter();
  initImportExport();
  initTabNavigation();
  initCSSEditor();

  /**
   * Initialize translation management
   */
  function initTranslationManagement() {
    // Edit translation button
    $(document).on("click", ".trp-tm-edit-btn", function (e) {
      e.preventDefault();
      var translationId = $(this).data("id");
      loadTranslationForEdit(translationId);
    });

    // Delete translation button
    $(document).on("click", ".trp-tm-delete-btn", function (e) {
      e.preventDefault();
      var translationId = $(this).data("id");

      if (confirm(trpTmAdmin.strings.confirm_delete)) {
        deleteTranslation(translationId);
      }
    });
  }

  /**
   * Initialize modal handlers
   */
  function initModalHandlers() {
    // Close modal
    $(document).on("click", ".trp-tm-close, .trp-tm-cancel", function () {
      $("#trp-tm-edit-modal").hide();
    });

    // Close modal when clicking outside
    $(window).on("click", function (e) {
      if (e.target.id === "trp-tm-edit-modal") {
        $("#trp-tm-edit-modal").hide();
      }
    });
  }

  /**
   * Initialize form handlers
   */
  function initFormHandlers() {
    // Add translation form
    $("#trp-tm-add-form").on("submit", function (e) {
      e.preventDefault();
      addTranslation();
    });

    // Edit translation form
    $("#trp-tm-edit-form").on("submit", function (e) {
      e.preventDefault();
      updateTranslation();
    });

    // Bulk add form
    $("#trp-tm-bulk-add-form").on("submit", function (e) {
      e.preventDefault();
      bulkAddTranslations();
    });

    // Settings form
    $("#trp-tm-settings-form").on("submit", function (e) {
      e.preventDefault();
      saveSettings();
    });

    // Import form
    $("#trp-tm-import-form").on("submit", function (e) {
      e.preventDefault();
      importTranslations();
    });

    // Export form
    $("#trp-tm-export-form").on("submit", function (e) {
      e.preventDefault();
      exportTranslations();
    });
  }

  /**
   * Initialize search and filter
   */
  function initSearchAndFilter() {
    var searchTimeout;

    // Search input
    $("#trp-tm-search").on("input", function () {
      clearTimeout(searchTimeout);
      var searchTerm = $(this).val();
      var languageCode = $("#trp-tm-language-filter").val();

      searchTimeout = setTimeout(function () {
        searchTranslations(searchTerm, languageCode);
      }, 500);
    });

    // Language filter
    $("#trp-tm-language-filter").on("change", function () {
      var searchTerm = $("#trp-tm-search").val();
      var languageCode = $(this).val();
      searchTranslations(searchTerm, languageCode);
    });
  }

  /**
   * Initialize import/export
   */
  function initImportExport() {
    // Handle CSV download for export
    $(document).on("click", "#download-csv", function (e) {
      e.preventDefault();
      var csvContent = $(this).data("content");
      var filename = $(this).data("filename");
      downloadCSV(csvContent, filename);
    });
  }

  /**
   * Initialize tab navigation with scroll position preservation
   */
  function initTabNavigation() {
    // Store scroll position before tab navigation
    var scrollPosition = 0;

    // Handle tab clicks
    $(document).on("click", ".nav-tab-wrapper .nav-tab", function (e) {
      e.preventDefault();

      // Store current scroll position
      scrollPosition = $(window).scrollTop();

      // Get the tab URL
      var tabUrl = $(this).attr("href");

      // Navigate to the tab while preserving scroll position
      window.history.pushState(null, null, tabUrl);

      // Reload the page content but maintain scroll position
      setTimeout(function () {
        window.location.href = tabUrl;
      }, 50);
    });

    // Restore scroll position after page load
    $(window).on("load", function () {
      if (sessionStorage.getItem("trp_scroll_position")) {
        var savedPosition = parseInt(
          sessionStorage.getItem("trp_scroll_position")
        );
        $(window).scrollTop(savedPosition);
        sessionStorage.removeItem("trp_scroll_position");
      }
    });

    // Store scroll position before page unload
    $(window).on("beforeunload", function () {
      sessionStorage.setItem("trp_scroll_position", $(window).scrollTop());
    });
  }

  /**
   * Initialize CSS editor functionality
   */
  function initCSSEditor() {
    // Language selector change
    $(document).on("change", "#css-language-selector", function () {
      var selectedLanguage = $(this).val();
      if (selectedLanguage) {
        // Redirect to same page with language parameter
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set("css_lang", selectedLanguage);
        window.location.href = currentUrl.toString();
      }
    });

    // CSS form submission
    $(document).on("submit", "#trp-tm-css-form", function (e) {
      e.preventDefault();
      saveCSSForLanguage();
    });

    // Format CSS button
    $(document).on("click", "#trp-tm-css-format", function (e) {
      e.preventDefault();
      formatCSS();
    });

    // Clear CSS button
    $(document).on("click", "#trp-tm-css-clear", function (e) {
      e.preventDefault();
      if (confirm("Are you sure you want to clear all CSS code?")) {
        $("#trp-tm-css-editor").val("");
        showCSSStatus("CSS cleared successfully.", "success");
      }
    });

    // Validate CSS button
    $(document).on("click", "#trp-tm-css-validate", function (e) {
      e.preventDefault();
      validateCSS();
    });

    // Enhanced textarea for better editing experience
    $(document).on("keydown", "#trp-tm-css-editor", function (e) {
      // Handle Tab key for proper indentation
      if (e.key === "Tab") {
        e.preventDefault();
        var start = this.selectionStart;
        var end = this.selectionEnd;
        var value = this.value;
        this.value = value.substring(0, start) + "    " + value.substring(end);
        this.selectionStart = this.selectionEnd = start + 4;
      }
    });
  }

  /**
   * Add new translation
   */
  function addTranslation() {
    var $form = $("#trp-tm-add-form");
    var $button = $form.find('button[type="submit"]');
    var originalText = $button.text();

    // Get form values
    var originalTextValue = $("#original-text").val().trim();
    var translatedTextValue = $("#translated-text").val().trim();
    var languageCodeValue = $("#language-code").val();

    // Validation
    if (!originalTextValue || !translatedTextValue || !languageCodeValue) {
      showNotice("Please fill in all fields.", "error");
      return;
    }

    $button.text(trpTmAdmin.strings.saving).prop("disabled", true);

    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_add_translation",
        nonce: trpTmAdmin.nonce,
        original_text: originalTextValue,
        translated_text: translatedTextValue,
        language_code: languageCodeValue,
      },
      success: function (response) {
        if (response.success) {
          showNotice(response.data.message, "success");

          // Store the selected language before resetting form
          var selectedLanguage = $("#language-code").val();

          // Reset form
          $form[0].reset();

          // Restore the selected language
          $("#language-code").val(selectedLanguage);

          // Add new translation to table via AJAX instead of page reload
          addTranslationToTable(response.data.translation);

          // Update statistics
          updateLanguageStatistics();
        } else {
          showNotice(response.data.message, "error");
        }
      },
      error: function () {
        showNotice(trpTmAdmin.strings.error, "error");
      },
      complete: function () {
        $button.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Load translation for editing
   */
  function loadTranslationForEdit(translationId) {
    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_get_translation",
        nonce: trpTmAdmin.nonce,
        translation_id: translationId,
      },
      success: function (response) {
        if (response.success) {
          var translation = response.data;
          $("#edit-translation-id").val(translation.id);
          $("#edit-original-text").val(translation.original_text);
          $("#edit-translated-text").val(translation.translated_text);
          $("#edit-language-code").val(translation.language_code);
          $("#trp-tm-edit-modal").show();
        } else {
          showNotice(response.data.message, "error");
        }
      },
      error: function () {
        showNotice(trpTmAdmin.strings.error, "error");
      },
    });
  }

  /**
   * Update translation
   */
  function updateTranslation() {
    var $form = $("#trp-tm-edit-form");
    var $button = $form.find('button[type="submit"]');
    var originalText = $button.text();

    $button.text(trpTmAdmin.strings.saving).prop("disabled", true);

    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_update_translation",
        nonce: trpTmAdmin.nonce,
        translation_id: $("#edit-translation-id").val(),
        translated_text: $("#edit-translated-text").val(),
        language_code: $("#edit-language-code").val(),
      },
      success: function (response) {
        if (response.success) {
          showNotice(response.data.message, "success");
          $("#trp-tm-edit-modal").hide();
          refreshTranslationsTable();
        } else {
          showNotice(response.data.message, "error");
        }
      },
      error: function () {
        showNotice(trpTmAdmin.strings.error, "error");
      },
      complete: function () {
        $button.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Delete translation
   */
  function deleteTranslation(translationId) {
    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_delete_translation",
        nonce: trpTmAdmin.nonce,
        translation_id: translationId,
      },
      success: function (response) {
        if (response.success) {
          showNotice(response.data.message, "success");
          $('tr[data-id="' + translationId + '"]').fadeOut(300, function () {
            $(this).remove();
          });
        } else {
          showNotice(response.data.message, "error");
        }
      },
      error: function () {
        showNotice(trpTmAdmin.strings.error, "error");
      },
    });
  }

  /**
   * Search translations
   */
  function searchTranslations(searchTerm, languageCode) {
    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_search_translations",
        nonce: trpTmAdmin.nonce,
        search_term: searchTerm,
        language_code: languageCode,
      },
      success: function (response) {
        if (response.success) {
          updateTranslationsTable(response.data);
        }
      },
    });
  }

  /**
   * Bulk add translations
   */
  function bulkAddTranslations() {
    var $form = $("#trp-tm-bulk-add-form");
    var $button = $form.find('button[type="submit"]');
    var originalText = $button.text();
    var bulkData = $("#bulk-translations").val();

    if (!bulkData.trim()) {
      showNotice("Please enter some translations.", "error");
      return;
    }

    $button.text(trpTmAdmin.strings.saving).prop("disabled", true);

    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_bulk_add_translations",
        nonce: trpTmAdmin.nonce,
        bulk_data: bulkData,
      },
      success: function (response) {
        if (response.success) {
          showNotice(response.data.message, "success");
          $("#bulk-translations").val("");
          refreshTranslationsTable();
        } else {
          showNotice(response.data.message, "error");
        }
      },
      error: function () {
        showNotice(trpTmAdmin.strings.error, "error");
      },
      complete: function () {
        $button.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Import translations
   */
  function importTranslations() {
    var $form = $("#trp-tm-import-form");
    var $button = $form.find('button[type="submit"]');
    var originalText = $button.text();
    var fileInput = $("#import-file")[0];

    if (!fileInput.files || !fileInput.files[0]) {
      showNotice("Please select a file to import.", "error");
      return;
    }

    var formData = new FormData();
    formData.append("action", "trp_tm_import_translations");
    formData.append("nonce", trpTmAdmin.nonce);
    formData.append("import_file", fileInput.files[0]);

    $button.text(trpTmAdmin.strings.saving).prop("disabled", true);

    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          showNotice(response.data.message, "success");
          $form[0].reset();
          refreshTranslationsTable();
        } else {
          showNotice(response.data.message, "error");
        }
      },
      error: function () {
        showNotice(trpTmAdmin.strings.error, "error");
      },
      complete: function () {
        $button.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Export translations
   */
  function exportTranslations() {
    var $form = $("#trp-tm-export-form");
    var $button = $form.find('button[type="submit"]');
    var originalText = $button.text();

    $button.text("Exporting...").prop("disabled", true);

    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_export_translations",
        nonce: trpTmAdmin.nonce,
        language_code: $("#export-language").val(),
      },
      success: function (response) {
        if (response.success) {
          downloadCSV(response.data.content, response.data.filename);
          showNotice(
            "Export completed. " +
              response.data.count +
              " translations exported.",
            "success"
          );
        } else {
          showNotice(response.data.message, "error");
        }
      },
      error: function () {
        showNotice(trpTmAdmin.strings.error, "error");
      },
      complete: function () {
        $button.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Save settings
   */
  function saveSettings() {
    var $form = $("#trp-tm-settings-form");
    var $button = $form.find('button[type="submit"]');
    var originalText = $button.text();

    $button.text(trpTmAdmin.strings.saving).prop("disabled", true);

    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_save_settings",
        nonce: trpTmAdmin.nonce,
        enable_frontend: $("#enable-frontend").is(":checked") ? 1 : 0,
        translation_priority: $("#translation-priority").val(),
      },
      success: function (response) {
        if (response.success) {
          showNotice(response.data.message, "success");
        } else {
          showNotice(response.data.message, "error");
        }
      },
      error: function () {
        showNotice(trpTmAdmin.strings.error, "error");
      },
      complete: function () {
        $button.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Update translations table
   */
  function updateTranslationsTable(translations) {
    var $tbody = $("#trp-tm-translations-tbody");
    $tbody.empty();

    if (translations.length === 0) {
      $tbody.append('<tr><td colspan="5">No translations found.</td></tr>');
      return;
    }

    translations.forEach(function (translation) {
      var row =
        '<tr data-id="' +
        translation.id +
        '">' +
        "<td>" +
        escapeHtml(translation.original_text) +
        "</td>" +
        "<td>" +
        escapeHtml(translation.translated_text) +
        "</td>" +
        "<td>" +
        escapeHtml(translation.language_code) +
        "</td>" +
        "<td>" +
        translation.updated_at +
        "</td>" +
        "<td>" +
        '<button class="button button-small trp-tm-edit-btn" data-id="' +
        translation.id +
        '">Edit</button> ' +
        '<button class="button button-small trp-tm-delete-btn" data-id="' +
        translation.id +
        '">Delete</button>' +
        "</td>" +
        "</tr>";
      $tbody.append(row);
    });
  }

  /**
   * Add new translation to table (AJAX)
   */
  function addTranslationToTable(translation) {
    var $tbody = $("#trp-tm-translations-tbody");

    // Check if "No translations found" message exists and remove it
    var $noResults = $tbody.find('tr td[colspan="5"]');
    if ($noResults.length > 0) {
      $noResults.parent().remove();
    }

    // Get language name for display
    var languageName = getLanguageName(translation.language_code);

    // Create new row with proper ID
    var translationId = translation.id || "new-" + Date.now();
    var newRow =
      '<tr data-id="' +
      translationId +
      '" class="trp-tm-new-row">' +
      "<td>" +
      escapeHtml(translation.original_text) +
      "</td>" +
      "<td>" +
      escapeHtml(translation.translated_text) +
      "</td>" +
      "<td>" +
      escapeHtml(languageName) +
      "</td>" +
      "<td>Just now</td>" +
      "<td>" +
      '<button class="button button-small trp-tm-edit-btn" data-id="' +
      translationId +
      '">Edit</button> ' +
      '<button class="button button-small trp-tm-delete-btn" data-id="' +
      translationId +
      '">Delete</button>' +
      "</td>" +
      "</tr>";

    // Add to top of table with animation
    $tbody.prepend(newRow);

    // Highlight the new row
    $(".trp-tm-new-row")
      .hide()
      .fadeIn(500)
      .delay(2000)
      .queue(function () {
        $(this).removeClass("trp-tm-new-row").dequeue();
      });
  }

  /**
   * Update language statistics (AJAX)
   */
  function updateLanguageStatistics() {
    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_get_language_statistics",
        nonce: trpTmAdmin.nonce,
      },
      success: function (response) {
        if (response.success && response.data) {
          updateStatisticsDisplay(response.data);
        }
      },
      error: function () {
        // Silently fail - statistics update is not critical
        console.log("Could not update statistics");
      },
    });
  }

  /**
   * Update statistics display
   */
  function updateStatisticsDisplay(statistics) {
    var $statsGrid = $(".trp-tm-stats-grid");
    $statsGrid.empty();

    if (statistics && statistics.length > 0) {
      statistics.forEach(function (stat) {
        var languageName = getLanguageName(stat.language_code);
        var statItem =
          '<div class="trp-tm-stat-item">' +
          "<strong>" +
          escapeHtml(languageName) +
          "</strong>" +
          "<span>" +
          parseInt(stat.count) +
          " translations</span>" +
          "</div>";
        $statsGrid.append(statItem);
      });
    } else {
      $statsGrid.append(
        '<div class="trp-tm-stat-item"><strong>No translations yet</strong><span>Add your first translation!</span></div>'
      );
    }
  }

  /**
   * Get language name from code
   */
  function getLanguageName(languageCode) {
    // Try to get from language filter options
    var $option = $(
      '#trp-tm-language-filter option[value="' + languageCode + '"]'
    );
    if ($option.length > 0) {
      return $option.text();
    }

    // Try to get from add form options
    $option = $('#language-code option[value="' + languageCode + '"]');
    if ($option.length > 0) {
      return $option.text();
    }

    // Fallback to language code
    return languageCode;
  }

  /**
   * Refresh translations table
   */
  function refreshTranslationsTable() {
    window.location.reload();
  }

  /**
   * Download CSV file
   */
  function downloadCSV(content, filename) {
    var blob = new Blob([content], { type: "text/csv;charset=utf-8;" });
    var link = document.createElement("a");

    if (link.download !== undefined) {
      var url = URL.createObjectURL(blob);
      link.setAttribute("href", url);
      link.setAttribute("download", filename);
      link.style.visibility = "hidden";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }

  /**
   * Show admin notice
   */
  function showNotice(message, type) {
    var noticeClass = type === "success" ? "notice-success" : "notice-error";
    var notice =
      '<div class="notice ' +
      noticeClass +
      ' is-dismissible">' +
      "<p>" +
      message +
      "</p>" +
      '<button type="button" class="notice-dismiss">' +
      '<span class="screen-reader-text">Dismiss this notice.</span>' +
      "</button>" +
      "</div>";

    $(".wrap h1").after(notice);

    // Auto dismiss after 5 seconds
    setTimeout(function () {
      $(".notice").fadeOut();
    }, 5000);

    // Manual dismiss
    $(document).on("click", ".notice-dismiss", function () {
      $(this).parent().fadeOut();
    });
  }

  /**
   * Escape HTML
   */
  function escapeHtml(text) {
    var map = {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  /**
   * Save CSS for selected language
   */
  function saveCSSForLanguage() {
    var $form = $("#trp-tm-css-form");
    var $button = $form.find('button[type="submit"]');
    var originalText = $button.text();

    var languageCode = $form.find('input[name="css_language"]').val();
    var customCSS = $("#trp-tm-css-editor").val();
    var minifyCSS = $("#trp-tm-minify-css").is(":checked");

    if (!languageCode) {
      showCSSStatus("Language code is missing.", "error");
      return;
    }

    $button.text("Saving...").prop("disabled", true);

    $.ajax({
      url: trpTmAdmin.ajax_url,
      type: "POST",
      data: {
        action: "trp_tm_save_css",
        nonce: trpTmAdmin.nonce,
        language_code: languageCode,
        custom_css: customCSS,
        minify_css: minifyCSS ? 1 : 0,
      },
      success: function (response) {
        if (response.success) {
          showCSSStatus("CSS saved successfully!", "success");

          // Update the example output
          updateCSSExample(response.data.processed_css, languageCode);
        } else {
          showCSSStatus(response.data.message, "error");
        }
      },
      error: function () {
        showCSSStatus("Error occurred while saving CSS!", "error");
      },
      complete: function () {
        $button.text(originalText).prop("disabled", false);
      },
    });
  }

  /**
   * Format CSS code
   */
  function formatCSS() {
    var cssContent = $("#trp-tm-css-editor").val();

    if (!cssContent.trim()) {
      showCSSStatus("No CSS content to format.", "warning");
      return;
    }

    try {
      // Basic CSS formatting
      var formatted = cssContent
        // Remove extra whitespace
        .replace(/\s+/g, " ")
        // Add proper spacing around braces
        .replace(/\s*{\s*/g, " {\n    ")
        .replace(/;\s*/g, ";\n    ")
        .replace(/\s*}\s*/g, "\n}\n\n")
        // Clean up extra newlines
        .replace(/\n\s*\n\s*\n/g, "\n\n")
        .trim();

      $("#trp-tm-css-editor").val(formatted);
      showCSSStatus("CSS formatted successfully.", "success");
    } catch (error) {
      showCSSStatus("Error formatting CSS: " + error.message, "error");
    }
  }

  /**
   * Validate CSS syntax
   */
  function validateCSS() {
    var cssContent = $("#trp-tm-css-editor").val();

    if (!cssContent.trim()) {
      showCSSStatus("No CSS content to validate.", "warning");
      return;
    }

    var errors = [];
    var warnings = [];

    // Basic CSS validation
    var lines = cssContent.split("\n");
    var openBraces = 0;
    var inRule = false;

    for (var i = 0; i < lines.length; i++) {
      var line = lines[i].trim();
      var lineNumber = i + 1;

      if (
        !line ||
        line.startsWith("/*") ||
        line.startsWith("*") ||
        line.startsWith("*/")
      ) {
        continue;
      }

      // Count braces
      var openCount = (line.match(/\{/g) || []).length;
      var closeCount = (line.match(/\}/g) || []).length;
      openBraces += openCount - closeCount;

      // Check for unclosed declarations
      if (line.includes("{")) {
        inRule = true;
      }
      if (line.includes("}")) {
        inRule = false;
      }

      // Basic syntax checking
      if (
        inRule &&
        line.includes(":") &&
        !line.includes(";") &&
        !line.includes("{") &&
        !line.includes("}")
      ) {
        warnings.push("Line " + lineNumber + ": Missing semicolon");
      }

      // Check for common errors
      if (line.includes(";;")) {
        warnings.push("Line " + lineNumber + ": Double semicolon");
      }
    }

    if (openBraces !== 0) {
      errors.push(
        "Mismatched braces: " +
          Math.abs(openBraces) +
          " " +
          (openBraces > 0 ? "unclosed" : "extra closing") +
          " brace(s)"
      );
    }

    // Display results
    if (errors.length === 0 && warnings.length === 0) {
      showCSSStatus("CSS validation passed successfully!", "success");
    } else if (errors.length > 0) {
      showCSSStatus("CSS validation failed: " + errors.join(", "), "error");
    } else {
      showCSSStatus(
        "CSS validation passed with warnings: " + warnings.join(", "),
        "warning"
      );
    }
  }

  /**
   * Show CSS status message
   */
  function showCSSStatus(message, type) {
    var $statusDiv = $("#trp-tm-css-status");
    var $notice = $statusDiv.find(".notice");
    var $message = $notice.find("p");

    $notice
      .removeClass("notice-success notice-error notice-warning")
      .addClass("notice-" + type);
    $message.text(message);
    $statusDiv.show();

    // Auto hide after 5 seconds
    setTimeout(function () {
      $statusDiv.fadeOut();
    }, 5000);
  }

  /**
   * Process CSS with language prefix (client-side preview)
   */
  function processCSSWithPrefix(css, languageCode) {
    if (!css.trim()) {
      return "";
    }

    var prefix = `html[lang="${languageCode}"]`;
    var processedCSS = "";

    // Split CSS into rules
    var rules = css.split("}");

    rules.forEach(function (rule) {
      rule = rule.trim();
      if (!rule) return;

      rule += "}";

      // Find the selector (everything before the first {)
      var bracePos = rule.indexOf("{");
      if (bracePos === -1) return;

      var selector = rule.substring(0, bracePos).trim();
      var declarations = rule.substring(bracePos);

      // Handle multiple selectors separated by commas
      var selectors = selector.split(",");
      var prefixedSelectors = [];

      selectors.forEach(function (singleSelector) {
        singleSelector = singleSelector.trim();
        if (singleSelector) {
          // Check if selector already has html prefix
          if (singleSelector.indexOf("html[lang=") === 0) {
            prefixedSelectors.push(singleSelector);
          } else {
            prefixedSelectors.push(prefix + " " + singleSelector);
          }
        }
      });

      if (prefixedSelectors.length > 0) {
        processedCSS +=
          prefixedSelectors.join(", ") + " " + declarations + "\n";
      }
    });

    return processedCSS;
  }

  /**
   * Update CSS example output
   */
  function updateCSSExample(processedCSS, languageCode) {
    var $exampleOutput = $(".trp-tm-css-example-output code");
    if ($exampleOutput.length > 0) {
      $exampleOutput.text(processedCSS);
    }
  }
});
