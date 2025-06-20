// TranslatePress Translation Map Frontend JavaScript
// This file handles additional frontend functionality for the translation map

jQuery(document).ready(function ($) {
  "use strict";

  // Check if frontend object exists
  if (typeof trpTmFrontend === "undefined") {
    return;
  }

  // Additional frontend functionality can be added here
  console.log("TranslatePress Translation Map Frontend Loaded");

  // Example: Log current language and available translations
  if (trpTmFrontend.current_language !== trpTmFrontend.default_language) {
    console.log("Current Language:", trpTmFrontend.current_language);
    console.log(
      "Available Translations:",
      Object.keys(trpTmFrontend.translations).length
    );
  }
});
