<?php
/**
 * Shortcode Handler
 *
 * Handles the [blessurewijzer] shortcode.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/frontend
 */

class Bracefox_BW_Shortcode {

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
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     */
    public function render($atts) {
        // Check if plugin is enabled
        if (!get_option('bracefox_bw_enabled', true)) {
            return '';
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<div class="bw-error">' .
                   esc_html__('Blessurewijzer requires WooCommerce to be installed and activated.', 'bracefox-blessurewijzer') .
                   '</div>';
        }

        // Check if API key is configured
        $api_key = get_option('bracefox_bw_api_key', '');
        if (empty($api_key)) {
            if (current_user_can('manage_options')) {
                return '<div class="bw-error">' .
                       esc_html__('Please configure your OpenAI API key in the Blessurewijzer settings.', 'bracefox-blessurewijzer') .
                       '</div>';
            }
            return '';
        }

        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => __('Bracefox Blessurewijzer', 'bracefox-blessurewijzer'),
            'placeholder' => __('Beschrijf je klacht...', 'bracefox-blessurewijzer'),
        ), $atts, 'blessurewijzer');

        // Start output buffering
        ob_start();

        // Include template
        require BRACEFOX_BW_PLUGIN_DIR . 'templates/frontend/chat-widget.php';

        // Return buffered content
        return ob_get_clean();
    }
}
