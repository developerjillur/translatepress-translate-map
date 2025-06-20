<?php

/**
 * AJAX handler for TranslatePress Translation Map
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TRP_TM_Ajax
{

    private $database;

    public function __construct()
    {
        $this->database = TRP_Translate_Map::get_instance()->get_database();
        $this->init_hooks();
    }

    /**
     * Initialize AJAX hooks
     */
    private function init_hooks()
    {
        // Admin AJAX actions
        add_action('wp_ajax_trp_tm_add_translation', array($this, 'add_translation'));
        add_action('wp_ajax_trp_tm_update_translation', array($this, 'update_translation'));
        add_action('wp_ajax_trp_tm_delete_translation', array($this, 'delete_translation'));
        add_action('wp_ajax_trp_tm_get_translation', array($this, 'get_translation'));
        add_action('wp_ajax_trp_tm_search_translations', array($this, 'search_translations'));
        add_action('wp_ajax_trp_tm_bulk_add_translations', array($this, 'bulk_add_translations'));
        add_action('wp_ajax_trp_tm_import_translations', array($this, 'import_translations'));
        add_action('wp_ajax_trp_tm_export_translations', array($this, 'export_translations'));
        add_action('wp_ajax_trp_tm_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_trp_tm_get_language_statistics', array($this, 'get_language_statistics'));

        // Frontend AJAX actions (if needed)
        add_action('wp_ajax_trp_tm_get_frontend_translations', array($this, 'get_frontend_translations'));
        add_action('wp_ajax_nopriv_trp_tm_get_frontend_translations', array($this, 'get_frontend_translations'));
    }

    /**
     * Add new translation
     */
    public function add_translation()
    {
        // Verify nonce and permissions
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $original_text = sanitize_text_field($_POST['original_text']);
        $translated_text = sanitize_textarea_field($_POST['translated_text']);
        $language_code = sanitize_text_field($_POST['language_code']);

        if (empty($original_text) || empty($translated_text) || empty($language_code)) {
            wp_send_json_error(array('message' => __('All fields are required.', 'trp-translate-map')));
        }

        $result = $this->database->save_translation($original_text, $translated_text, $language_code);

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Translation added successfully.', 'trp-translate-map'),
                'translation' => array(
                    'id' => $result,
                    'original_text' => $original_text,
                    'translated_text' => $translated_text,
                    'language_code' => $language_code,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                )
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to add translation.', 'trp-translate-map')));
        }
    }

    /**
     * Update existing translation
     */
    public function update_translation()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $translation_id = intval($_POST['translation_id']);
        $translated_text = sanitize_textarea_field($_POST['translated_text']);
        $language_code = sanitize_text_field($_POST['language_code']);

        if (empty($translation_id) || empty($translated_text) || empty($language_code)) {
            wp_send_json_error(array('message' => __('Invalid data provided.', 'trp-translate-map')));
        }

        // Get current translation to get original text
        $current = $this->database->get_translation_by_id($translation_id);
        if (!$current) {
            wp_send_json_error(array('message' => __('Translation not found.', 'trp-translate-map')));
        }

        $result = $this->database->save_translation($current['original_text'], $translated_text, $language_code);

        if ($result !== false) {
            wp_send_json_success(array('message' => __('Translation updated successfully.', 'trp-translate-map')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update translation.', 'trp-translate-map')));
        }
    }

    /**
     * Delete translation
     */
    public function delete_translation()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $translation_id = intval($_POST['translation_id']);

        if (empty($translation_id)) {
            wp_send_json_error(array('message' => __('Invalid translation ID.', 'trp-translate-map')));
        }

        $result = $this->database->delete_translation($translation_id);

        if ($result !== false) {
            wp_send_json_success(array('message' => __('Translation deleted successfully.', 'trp-translate-map')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete translation.', 'trp-translate-map')));
        }
    }

    /**
     * Get translation by ID
     */
    public function get_translation()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $translation_id = intval($_POST['translation_id']);

        if (empty($translation_id)) {
            wp_send_json_error(array('message' => __('Invalid translation ID.', 'trp-translate-map')));
        }

        $translation = $this->database->get_translation_by_id($translation_id);

        if ($translation) {
            wp_send_json_success($translation);
        } else {
            wp_send_json_error(array('message' => __('Translation not found.', 'trp-translate-map')));
        }
    }

    /**
     * Search translations
     */
    public function search_translations()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $search_term = sanitize_text_field($_POST['search_term']);
        $language_code = sanitize_text_field($_POST['language_code']);

        $translations = $this->database->search_translations($search_term, $language_code);

        wp_send_json_success($translations);
    }

    /**
     * Bulk add translations
     */
    public function bulk_add_translations()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $bulk_data = sanitize_textarea_field($_POST['bulk_data']);

        if (empty($bulk_data)) {
            wp_send_json_error(array('message' => __('No data provided.', 'trp-translate-map')));
        }

        $lines = explode("\n", $bulk_data);
        $translations = array();
        $errors = array();

        foreach ($lines as $line_number => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = explode('|', $line);
            if (count($parts) !== 3) {
                $errors[] = sprintf(__('Line %d: Invalid format. Expected: Original|Translation|Language', 'trp-translate-map'), $line_number + 1);
                continue;
            }

            $translations[] = array(
                'original_text' => trim($parts[0]),
                'translated_text' => trim($parts[1]),
                'language_code' => trim($parts[2])
            );
        }

        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode('<br>', $errors)));
        }

        $result = $this->database->bulk_import_translations($translations);

        wp_send_json_success(array(
            'message' => sprintf(
                __('Bulk import completed. %d translations added, %d errors.', 'trp-translate-map'),
                $result['success'],
                $result['errors']
            ),
            'result' => $result
        ));
    }

    /**
     * Import translations from CSV
     */
    public function import_translations()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('File upload failed.', 'trp-translate-map')));
        }

        $file = $_FILES['import_file'];
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (strtolower($file_ext) !== 'csv') {
            wp_send_json_error(array('message' => __('Only CSV files are allowed.', 'trp-translate-map')));
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            wp_send_json_error(array('message' => __('Could not read the file.', 'trp-translate-map')));
        }

        $translations = array();
        $line_number = 0;

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $line_number++;

            // Skip header row
            if ($line_number === 1 && (strtolower($data[0]) === 'original text' || strtolower($data[0]) === 'original_text')) {
                continue;
            }

            if (count($data) >= 3) {
                $translations[] = array(
                    'original_text' => trim($data[0]),
                    'translated_text' => trim($data[1]),
                    'language_code' => trim($data[2])
                );
            }
        }

        fclose($handle);

        if (empty($translations)) {
            wp_send_json_error(array('message' => __('No valid translations found in the file.', 'trp-translate-map')));
        }

        $result = $this->database->bulk_import_translations($translations);

        wp_send_json_success(array(
            'message' => sprintf(
                __('Import completed. %d translations added, %d errors.', 'trp-translate-map'),
                $result['success'],
                $result['errors']
            ),
            'result' => $result
        ));
    }

    /**
     * Export translations to CSV
     */
    public function export_translations()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $language_code = sanitize_text_field($_POST['language_code']);
        $translations = $this->database->export_translations($language_code);

        if (empty($translations)) {
            wp_send_json_error(array('message' => __('No translations found to export.', 'trp-translate-map')));
        }

        // Create CSV content
        $csv_content = "Original Text,Translation,Language Code\n";
        foreach ($translations as $translation) {
            $csv_content .= sprintf(
                '"%s","%s","%s"' . "\n",
                str_replace('"', '""', $translation['original_text']),
                str_replace('"', '""', $translation['translated_text']),
                $translation['language_code']
            );
        }

        // Generate filename
        $filename = 'trp-translations';
        if (!empty($language_code)) {
            $filename .= '-' . $language_code;
        }
        $filename .= '-' . date('Y-m-d-H-i-s') . '.csv';

        wp_send_json_success(array(
            'filename' => $filename,
            'content' => $csv_content,
            'count' => count($translations)
        ));
    }

    /**
     * Save plugin settings
     */
    public function save_settings()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $enable_frontend = isset($_POST['enable_frontend']) ? 1 : 0;
        $translation_priority = sanitize_text_field($_POST['translation_priority']);

        update_option('trp_tm_enable_frontend', $enable_frontend);
        update_option('trp_tm_translation_priority', $translation_priority);

        wp_send_json_success(array('message' => __('Settings saved successfully.', 'trp-translate-map')));
    }

    /**
     * Get frontend translations for current language
     */
    public function get_frontend_translations()
    {
        if (!$this->verify_frontend_request()) {
            wp_die();
        }

        $language_code = sanitize_text_field($_POST['language_code']);

        if (empty($language_code)) {
            wp_send_json_error(array('message' => __('Language code is required.', 'trp-translate-map')));
        }

        $translations = $this->database->get_translations_by_language($language_code);
        $translation_map = array();

        foreach ($translations as $translation) {
            $translation_map[$translation['original_text']] = $translation['translated_text'];
        }

        wp_send_json_success($translation_map);
    }

    /**
     * Get language statistics
     */
    public function get_language_statistics()
    {
        if (!$this->verify_admin_request()) {
            wp_die();
        }

        $statistics = $this->database->get_translation_count_by_language();

        wp_send_json_success($statistics);
    }

    /**
     * Verify admin AJAX request
     */
    private function verify_admin_request()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'trp_tm_admin_nonce')) {
            return false;
        }

        return true;
    }

    /**
     * Verify frontend AJAX request
     */
    private function verify_frontend_request()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'trp_tm_frontend_nonce')) {
            return false;
        }

        return true;
    }
}
