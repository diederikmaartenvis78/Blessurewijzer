<?php
/**
 * Admin Area Handler
 *
 * Handles admin menu, pages, and functionality.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/admin
 */

class Bracefox_BW_Admin {

    /**
     * Plugin name
     */
    private $plugin_name;

    /**
     * Plugin version
     */
    private $version;

    /**
     * Constructor
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Blessurewijzer', 'bracefox-blessurewijzer'),
            __('Blessurewijzer', 'bracefox-blessurewijzer'),
            'manage_options',
            'bracefox-blessurewijzer',
            array($this, 'render_analytics_page'),
            'dashicons-heart',
            30
        );

        // Analytics submenu (duplicate of main)
        add_submenu_page(
            'bracefox-blessurewijzer',
            __('Analytics', 'bracefox-blessurewijzer'),
            __('Analytics', 'bracefox-blessurewijzer'),
            'manage_options',
            'bracefox-blessurewijzer',
            array($this, 'render_analytics_page')
        );

        // Settings submenu
        add_submenu_page(
            'bracefox-blessurewijzer',
            __('Settings', 'bracefox-blessurewijzer'),
            __('Settings', 'bracefox-blessurewijzer'),
            'manage_options',
            'bracefox-blessurewijzer-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        require_once BRACEFOX_BW_PLUGIN_DIR . 'templates/admin/analytics-dashboard.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        require_once BRACEFOX_BW_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'bracefox-blessurewijzer') === false) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name . '-admin',
            BRACEFOX_BW_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'bracefox-blessurewijzer') === false) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name . '-admin',
            BRACEFOX_BW_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            $this->plugin_name . '-admin',
            'bracefoxBWAdmin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_admin_nonce'),
            )
        );
    }
}
