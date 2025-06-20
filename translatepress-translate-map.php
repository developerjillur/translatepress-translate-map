<?php

/**
 * Plugin Name: TranslatePress - Translation Map Manager
 * Plugin URI: https://translatepress.com/
 * Description: Advanced translation word mapping manager for TranslatePress that allows custom translation overrides with dynamic language switching.
 * Version: 1.0.0
 * Author: Developerjillur
 * Author URI: https://translatepress.com/
 * Text Domain: trp-translate-map
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 6.4
 * Requires PHP: 5.6
 * Network: false
 * License: GPL2
 *
 * == Copyright ==
 * Copyright 2024 TranslatePress
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TRP_TM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TRP_TM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TRP_TM_PLUGIN_BASE', plugin_basename(__FILE__));
define('TRP_TM_PLUGIN_VERSION', '1.0.0');
define('TRP_TM_PLUGIN_SLUG', 'trp-translate-map');

/**
 * Main plugin class
 */
class TRP_Translate_Map
{

    private static $instance = null;
    private $admin;
    private $frontend;
    private $database;

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Check if TranslatePress is active
        if (!$this->is_translatepress_active()) {
            add_action('admin_notices', array($this, 'translatepress_missing_notice'));
            return;
        }

        $this->load_dependencies();
        $this->initialize_components();
        $this->define_hooks();

        // Load textdomain
        load_plugin_textdomain('trp-translate-map', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Load required files
     */
    private function load_dependencies()
    {
        require_once TRP_TM_PLUGIN_DIR . 'includes/class-database.php';
        require_once TRP_TM_PLUGIN_DIR . 'includes/class-admin.php';
        require_once TRP_TM_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once TRP_TM_PLUGIN_DIR . 'includes/class-ajax.php';
    }

    /**
     * Initialize components
     */
    private function initialize_components()
    {
        $this->database = new TRP_TM_Database();
        $this->admin = new TRP_TM_Admin();
        $this->frontend = new TRP_TM_Frontend();
        new TRP_TM_Ajax();
    }

    /**
     * Define hooks
     */
    private function define_hooks()
    {
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this->admin, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_admin_scripts'));
        }

        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this->frontend, 'enqueue_frontend_scripts'));
        add_action('wp_footer', array($this->frontend, 'render_translation_script'));
    }

    /**
     * Check if TranslatePress is active
     */
    private function is_translatepress_active()
    {
        return class_exists('TRP_Translate_Press');
    }

    /**
     * Show notice if TranslatePress is not active
     */
    public function translatepress_missing_notice()
    {
?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    __('TranslatePress - Translation Map Manager requires %s to be installed and active.', 'trp-translate-map'),
                    '<strong>TranslatePress</strong>'
                );
                ?>
            </p>
        </div>
<?php
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        if (!$this->is_translatepress_active()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('TranslatePress - Translation Map Manager requires TranslatePress to be installed and active.', 'trp-translate-map'));
        }

        // Create database tables
        require_once TRP_TM_PLUGIN_DIR . 'includes/class-database.php';
        $database = new TRP_TM_Database();
        $database->create_tables();

        // Set default options
        add_option('trp_tm_version', TRP_TM_PLUGIN_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        // Clean up if needed
    }

    /**
     * Get database instance
     */
    public function get_database()
    {
        return $this->database;
    }

    /**
     * Get admin instance
     */
    public function get_admin()
    {
        return $this->admin;
    }

    /**
     * Get frontend instance
     */
    public function get_frontend()
    {
        return $this->frontend;
    }
}

// Initialize plugin
TRP_Translate_Map::get_instance();
