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
            if (current_user_can('manage_options')) {
                return '<div class="bw-error" style="padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;">' .
                       esc_html__('Blessurewijzer plugin is disabled in settings.', 'bracefox-blessurewijzer') .
                       '</div>';
            }
            return '';
        }

        // Enqueue assets when shortcode is rendered (fixes Elementor/page builder compatibility)
        $this->enqueue_assets();

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<div class="bw-error" style="padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;">' .
                   esc_html__('Blessurewijzer requires WooCommerce to be installed and activated.', 'bracefox-blessurewijzer') .
                   '</div>';
        }

        // Check if API key is configured
        $api_key = get_option('bracefox_bw_api_key', '');
        if (empty($api_key)) {
            if (current_user_can('manage_options')) {
                return '<div class="bw-error" style="padding:20px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:4px;">' .
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

    /**
     * Enqueue frontend assets
     * Called when shortcode is actually rendered (ensures compatibility with page builders)
     */
    private function enqueue_assets() {
        // Enqueue CSS
        wp_enqueue_style(
            $this->plugin_name,
            BRACEFOX_BW_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version,
            'all'
        );

        // Enqueue JS
        wp_enqueue_script(
            $this->plugin_name,
            BRACEFOX_BW_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script (only if not already done)
        if (!wp_script_is($this->plugin_name, 'localized')) {
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
    }
}
