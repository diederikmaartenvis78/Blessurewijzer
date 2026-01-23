<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes
 */

class Bracefox_BW_Plugin {

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->plugin_name = 'bracefox-blessurewijzer';
        $this->version = BRACEFOX_BW_VERSION;

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_ajax_hooks();

        // Register shortcode - use init hook if not yet fired, otherwise register immediately
        if (did_action('init')) {
            $this->define_frontend_hooks();
        } else {
            add_action('init', array($this, 'define_frontend_hooks'), 1);
        }
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Services
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/services/class-cache-service.php';
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/services/class-rate-limiter.php';
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/services/class-prompt-builder.php';

        // Repositories
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/repositories/class-product-repository.php';
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/repositories/class-blog-repository.php';
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/repositories/class-stats-repository.php';

        // API
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/api/class-openai-client.php';
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/api/class-ajax-handler.php';

        // Admin
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/admin/class-admin.php';
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/admin/class-settings.php';
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/admin/class-analytics.php';

        // Frontend
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/frontend/class-shortcode.php';
        require_once BRACEFOX_BW_PLUGIN_DIR . 'includes/frontend/class-assets.php';
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $admin = new Bracefox_BW_Admin($this->get_plugin_name(), $this->get_version());

        add_action('admin_menu', array($admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));

        // Settings
        $settings = new Bracefox_BW_Settings();
        add_action('admin_init', array($settings, 'register_settings'));
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     * Note: Shortcode is registered directly in main plugin file for maximum compatibility.
     */
    public function define_frontend_hooks() {
        // Assets are enqueued conditionally and also directly in shortcode render for page builder compatibility
        $assets = new Bracefox_BW_Assets($this->get_plugin_name(), $this->get_version());
        add_action('wp_enqueue_scripts', array($assets, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($assets, 'enqueue_scripts'));
    }

    /**
     * Register all AJAX hooks.
     */
    private function define_ajax_hooks() {
        $ajax_handler = new Bracefox_BW_Ajax_Handler();

        // Public AJAX endpoints
        add_action('wp_ajax_bw_send_message', array($ajax_handler, 'send_message'));
        add_action('wp_ajax_nopriv_bw_send_message', array($ajax_handler, 'send_message'));

        add_action('wp_ajax_bw_track_click', array($ajax_handler, 'track_product_click'));
        add_action('wp_ajax_nopriv_bw_track_click', array($ajax_handler, 'track_product_click'));

        // Admin AJAX endpoints
        add_action('wp_ajax_bw_get_analytics', array($ajax_handler, 'get_analytics'));
        add_action('wp_ajax_bw_test_connection', array($ajax_handler, 'test_connection'));
        add_action('wp_ajax_bw_clear_cache', array($ajax_handler, 'clear_cache'));
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // Plugin is now loaded and hooks are registered
    }

    /**
     * The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
