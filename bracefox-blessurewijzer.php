<?php
/**
 * Plugin Name: Bracefox Blessurewijzer 2.0
 * Plugin URI: https://bracefox.nl
 * Description: AI-gestuurde conversational assistant voor blessure advies en product aanbevelingen
 * Version: 2.0.0
 * Author: Bracefox
 * Author URI: https://bracefox.nl
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bracefox-blessurewijzer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('BRACEFOX_BW_VERSION', '2.0.0');

// Plugin paths
define('BRACEFOX_BW_PLUGIN_FILE', __FILE__);
define('BRACEFOX_BW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BRACEFOX_BW_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRACEFOX_BW_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Database table names
global $wpdb;
define('BRACEFOX_BW_TABLE_SESSIONS', $wpdb->prefix . 'blessurewijzer_sessions');
define('BRACEFOX_BW_TABLE_MESSAGES', $wpdb->prefix . 'blessurewijzer_messages');
define('BRACEFOX_BW_TABLE_RECOMMENDATIONS', $wpdb->prefix . 'blessurewijzer_recommendations');

/**
 * The code that runs during plugin activation.
 */
function activate_bracefox_blessurewijzer() {
    require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/class-activator.php';
    Bracefox_BW_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_bracefox_blessurewijzer() {
    require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/class-deactivator.php';
    Bracefox_BW_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_bracefox_blessurewijzer');
register_deactivation_hook(__FILE__, 'deactivate_bracefox_blessurewijzer');

/**
 * The core plugin class
 */
require BRACEFOX_BW_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Begins execution of the plugin.
 *
 * Uses plugins_loaded hook to ensure all plugins are loaded,
 * or runs immediately if plugins_loaded has already fired.
 */
function run_bracefox_blessurewijzer() {
    global $bracefox_blessurewijzer;
    $bracefox_blessurewijzer = new Bracefox_BW_Plugin();
    $bracefox_blessurewijzer->run();
}

// Run on plugins_loaded to ensure proper WordPress initialization
if (did_action('plugins_loaded')) {
    run_bracefox_blessurewijzer();
} else {
    add_action('plugins_loaded', 'run_bracefox_blessurewijzer');
}
