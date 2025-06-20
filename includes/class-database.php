<?php

/**
 * Database handler for TranslatePress Translation Map
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TRP_TM_Database
{

    private $table_name;
    private $wpdb;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'trp_translate_map';
    }

    /**
     * Create database tables
     */
    public function create_tables()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
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
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert or update translation mapping
     */
    public function save_translation($original_text, $translated_text, $language_code)
    {
        return $this->wpdb->replace(
            $this->table_name,
            array(
                'original_text' => sanitize_text_field($original_text),
                'translated_text' => sanitize_textarea_field($translated_text),
                'language_code' => sanitize_text_field($language_code),
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get translation for specific text and language
     */
    public function get_translation($original_text, $language_code)
    {
        return $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT translated_text FROM {$this->table_name} 
                WHERE original_text = %s AND language_code = %s AND status = 'active'",
                $original_text,
                $language_code
            )
        );
    }

    /**
     * Get all translations for a specific language
     */
    public function get_translations_by_language($language_code)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT original_text, translated_text FROM {$this->table_name} 
                WHERE language_code = %s AND status = 'active'",
                $language_code
            ),
            ARRAY_A
        );
    }

    /**
     * Get all translations
     */
    public function get_all_translations()
    {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE status = 'active' ORDER BY language_code, original_text",
            ARRAY_A
        );
    }

    /**
     * Delete translation
     */
    public function delete_translation($id)
    {
        return $this->wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Get translation by ID
     */
    public function get_translation_by_id($id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
    }

    /**
     * Search translations
     */
    public function search_translations($search_term, $language_code = '')
    {
        $where_clause = "WHERE (original_text LIKE %s OR translated_text LIKE %s) AND status = 'active'";
        $params = array('%' . $search_term . '%', '%' . $search_term . '%');

        if (!empty($language_code)) {
            $where_clause .= " AND language_code = %s";
            $params[] = $language_code;
        }

        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} {$where_clause} ORDER BY language_code, original_text",
                ...$params
            ),
            ARRAY_A
        );
    }

    /**
     * Get translation count by language
     */
    public function get_translation_count_by_language()
    {
        return $this->wpdb->get_results(
            "SELECT language_code, COUNT(*) as count FROM {$this->table_name} 
            WHERE status = 'active' GROUP BY language_code",
            ARRAY_A
        );
    }

    /**
     * Bulk import translations
     */
    public function bulk_import_translations($translations)
    {
        $success_count = 0;
        $error_count = 0;

        foreach ($translations as $translation) {
            if (isset($translation['original_text'], $translation['translated_text'], $translation['language_code'])) {
                $result = $this->save_translation(
                    $translation['original_text'],
                    $translation['translated_text'],
                    $translation['language_code']
                );

                if ($result !== false) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            } else {
                $error_count++;
            }
        }

        return array(
            'success' => $success_count,
            'errors' => $error_count
        );
    }

    /**
     * Export translations
     */
    public function export_translations($language_code = '')
    {
        if (!empty($language_code)) {
            return $this->get_translations_by_language($language_code);
        }

        return $this->get_all_translations();
    }
}
