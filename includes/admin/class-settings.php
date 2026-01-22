<?php
/**
 * Settings Handler
 *
 * Registers and handles plugin settings.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/admin
 */

class Bracefox_BW_Settings {

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting('bracefox_bw_settings', 'bracefox_bw_api_provider');
        register_setting('bracefox_bw_settings', 'bracefox_bw_api_key');
        register_setting('bracefox_bw_settings', 'bracefox_bw_model');
        register_setting('bracefox_bw_settings', 'bracefox_bw_temperature');
        register_setting('bracefox_bw_settings', 'bracefox_bw_max_tokens');
        register_setting('bracefox_bw_settings', 'bracefox_bw_timeout');
        register_setting('bracefox_bw_settings', 'bracefox_bw_cache_ttl');
        register_setting('bracefox_bw_settings', 'bracefox_bw_rate_limit_max');
        register_setting('bracefox_bw_settings', 'bracefox_bw_rate_limit_window');
        register_setting('bracefox_bw_settings', 'bracefox_bw_enabled');

        // Add settings sections
        add_settings_section(
            'bracefox_bw_api_section',
            __('API Configuration', 'bracefox-blessurewijzer'),
            array($this, 'api_section_callback'),
            'bracefox-blessurewijzer-settings'
        );

        add_settings_section(
            'bracefox_bw_performance_section',
            __('Performance & Caching', 'bracefox-blessurewijzer'),
            array($this, 'performance_section_callback'),
            'bracefox-blessurewijzer-settings'
        );

        add_settings_section(
            'bracefox_bw_security_section',
            __('Security', 'bracefox-blessurewijzer'),
            array($this, 'security_section_callback'),
            'bracefox-blessurewijzer-settings'
        );

        // Add settings fields - API Section
        add_settings_field(
            'bracefox_bw_api_key',
            __('OpenAI API Key', 'bracefox-blessurewijzer'),
            array($this, 'api_key_field_callback'),
            'bracefox-blessurewijzer-settings',
            'bracefox_bw_api_section'
        );

        add_settings_field(
            'bracefox_bw_model',
            __('Model', 'bracefox-blessurewijzer'),
            array($this, 'model_field_callback'),
            'bracefox-blessurewijzer-settings',
            'bracefox_bw_api_section'
        );

        add_settings_field(
            'bracefox_bw_temperature',
            __('Temperature', 'bracefox-blessurewijzer'),
            array($this, 'temperature_field_callback'),
            'bracefox-blessurewijzer-settings',
            'bracefox_bw_api_section'
        );

        add_settings_field(
            'bracefox_bw_max_tokens',
            __('Max Tokens', 'bracefox-blessurewijzer'),
            array($this, 'max_tokens_field_callback'),
            'bracefox-blessurewijzer-settings',
            'bracefox_bw_api_section'
        );

        add_settings_field(
            'bracefox_bw_timeout',
            __('Timeout (seconds)', 'bracefox-blessurewijzer'),
            array($this, 'timeout_field_callback'),
            'bracefox-blessurewijzer-settings',
            'bracefox_bw_api_section'
        );

        // Performance Section
        add_settings_field(
            'bracefox_bw_cache_ttl',
            __('Cache TTL (seconds)', 'bracefox-blessurewijzer'),
            array($this, 'cache_ttl_field_callback'),
            'bracefox-blessurewijzer-settings',
            'bracefox_bw_performance_section'
        );

        // Security Section
        add_settings_field(
            'bracefox_bw_rate_limit_max',
            __('Rate Limit (max requests)', 'bracefox-blessurewijzer'),
            array($this, 'rate_limit_max_field_callback'),
            'bracefox-blessurewijzer-settings',
            'bracefox_bw_security_section'
        );

        add_settings_field(
            'bracefox_bw_rate_limit_window',
            __('Rate Limit Window (seconds)', 'bracefox-blessurewijzer'),
            array($this, 'rate_limit_window_field_callback'),
            'bracefox-blessurewijzer-settings',
            'bracefox_bw_security_section'
        );
    }

    /**
     * Section callbacks
     */
    public function api_section_callback() {
        echo '<p>' . esc_html__('Configure OpenAI API settings for the AI chat functionality.', 'bracefox-blessurewijzer') . '</p>';
    }

    public function performance_section_callback() {
        echo '<p>' . esc_html__('Optimize performance with caching settings.', 'bracefox-blessurewijzer') . '</p>';
    }

    public function security_section_callback() {
        echo '<p>' . esc_html__('Protect your site from abuse with rate limiting.', 'bracefox-blessurewijzer') . '</p>';
    }

    /**
     * Field callbacks
     */
    public function api_key_field_callback() {
        $value = get_option('bracefox_bw_api_key', '');
        echo '<input type="password" name="bracefox_bw_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Your OpenAI API key. Get it from platform.openai.com', 'bracefox-blessurewijzer') . '</p>';
    }

    public function model_field_callback() {
        $value = get_option('bracefox_bw_model', 'gpt-4o-mini');
        $models = array(
            'gpt-4o-mini' => 'GPT-4o Mini (Recommended)',
            'gpt-4o' => 'GPT-4o',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        );

        echo '<select name="bracefox_bw_model">';
        foreach ($models as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('AI model to use. GPT-4o Mini is fastest and cheapest.', 'bracefox-blessurewijzer') . '</p>';
    }

    public function temperature_field_callback() {
        $value = get_option('bracefox_bw_temperature', 0.7);
        echo '<input type="number" name="bracefox_bw_temperature" value="' . esc_attr($value) . '" step="0.1" min="0" max="2" />';
        echo '<p class="description">' . esc_html__('0-2. Lower = more focused, higher = more creative. Default: 0.7', 'bracefox-blessurewijzer') . '</p>';
    }

    public function max_tokens_field_callback() {
        $value = get_option('bracefox_bw_max_tokens', 1500);
        echo '<input type="number" name="bracefox_bw_max_tokens" value="' . esc_attr($value) . '" step="100" min="500" max="4000" />';
        echo '<p class="description">' . esc_html__('Maximum tokens per response. Default: 1500', 'bracefox-blessurewijzer') . '</p>';
    }

    public function timeout_field_callback() {
        $value = get_option('bracefox_bw_timeout', 30);
        echo '<input type="number" name="bracefox_bw_timeout" value="' . esc_attr($value) . '" step="5" min="10" max="60" />';
        echo '<p class="description">' . esc_html__('API request timeout. Default: 30 seconds', 'bracefox-blessurewijzer') . '</p>';
    }

    public function cache_ttl_field_callback() {
        $value = get_option('bracefox_bw_cache_ttl', 3600);
        echo '<input type="number" name="bracefox_bw_cache_ttl" value="' . esc_attr($value) . '" step="300" min="0" max="86400" />';
        echo '<p class="description">' . esc_html__('How long to cache product/blog data. Default: 3600 (1 hour)', 'bracefox-blessurewijzer') . '</p>';
    }

    public function rate_limit_max_field_callback() {
        $value = get_option('bracefox_bw_rate_limit_max', 10);
        echo '<input type="number" name="bracefox_bw_rate_limit_max" value="' . esc_attr($value) . '" step="1" min="1" max="100" />';
        echo '<p class="description">' . esc_html__('Maximum requests per IP per time window. Default: 10', 'bracefox-blessurewijzer') . '</p>';
    }

    public function rate_limit_window_field_callback() {
        $value = get_option('bracefox_bw_rate_limit_window', 60);
        echo '<input type="number" name="bracefox_bw_rate_limit_window" value="' . esc_attr($value) . '" step="10" min="10" max="3600" />';
        echo '<p class="description">' . esc_html__('Time window for rate limiting. Default: 60 seconds', 'bracefox-blessurewijzer') . '</p>';
    }
}
