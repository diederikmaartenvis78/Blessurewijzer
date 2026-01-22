<?php
/**
 * Assets Handler
 *
 * Handles enqueueing of frontend CSS and JavaScript.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/frontend
 */

class Bracefox_BW_Assets {

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
     * Enqueue frontend styles
     */
    public function enqueue_styles() {
        // Only load if shortcode is present or we're on relevant pages
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            BRACEFOX_BW_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Only load if shortcode is present or we're on relevant pages
        if (!$this->should_load_assets()) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            BRACEFOX_BW_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            $this->plugin_name,
            'bracefoxBW',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bw_chat_nonce'),
                'i18n' => array(
                    'thinking' => __('Even denken...', 'bracefox-blessurewijzer'),
                    'analyzing' => __('Klacht analyseren', 'bracefox-blessurewijzer'),
                    'searching' => __('Beste product zoeken', 'bracefox-blessurewijzer'),
                    'composing' => __('Advies samenstellen', 'bracefox-blessurewijzer'),
                    'error_generic' => __('Er ging iets mis. Probeer het opnieuw.', 'bracefox-blessurewijzer'),
                    'error_rate_limited' => __('Je hebt te veel vragen gesteld. Wacht even en probeer het opnieuw.', 'bracefox-blessurewijzer'),
                    'error_empty_message' => __('Typ eerst een bericht.', 'bracefox-blessurewijzer'),
                    'new_question' => __('Stel een andere vraag', 'bracefox-blessurewijzer'),
                    'send' => __('Verstuur', 'bracefox-blessurewijzer'),
                ),
            )
        );
    }

    /**
     * Check if assets should be loaded
     *
     * @return bool True if assets should load
     */
    private function should_load_assets() {
        global $post;

        // Always load in admin
        if (is_admin()) {
            return false;
        }

        // Check if shortcode is present in post content
        if ($post && has_shortcode($post->post_content, 'blessurewijzer')) {
            return true;
        }

        // Check if we're on a page that might have the shortcode
        // You can customize this logic based on your needs
        return false;
    }
}
