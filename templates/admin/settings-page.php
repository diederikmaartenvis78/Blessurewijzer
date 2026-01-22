<?php
/**
 * Admin Settings Page Template
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/templates/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap bw-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <div class="bw-admin-container">
        <div class="bw-admin-main">
            <form method="post" action="options.php">
                <?php
                settings_fields('bracefox_bw_settings');
                do_settings_sections('bracefox-blessurewijzer-settings');
                submit_button();
                ?>
            </form>

            <div class="bw-test-connection">
                <h2><?php esc_html_e('Test Connection', 'bracefox-blessurewijzer'); ?></h2>
                <p><?php esc_html_e('Test your OpenAI API connection to make sure everything is configured correctly.', 'bracefox-blessurewijzer'); ?></p>
                <button type="button" id="bw-test-api" class="button button-secondary">
                    <?php esc_html_e('Test API Connection', 'bracefox-blessurewijzer'); ?>
                </button>
                <div id="bw-test-result" style="margin-top: 10px;"></div>
            </div>

            <div class="bw-cache-controls">
                <h2><?php esc_html_e('Cache Management', 'bracefox-blessurewijzer'); ?></h2>
                <p><?php esc_html_e('Clear cached data to force refresh of products and blog posts.', 'bracefox-blessurewijzer'); ?></p>
                <button type="button" id="bw-clear-cache" class="button button-secondary">
                    <?php esc_html_e('Clear All Caches', 'bracefox-blessurewijzer'); ?>
                </button>
                <div id="bw-cache-result" style="margin-top: 10px;"></div>
            </div>
        </div>

        <div class="bw-admin-sidebar">
            <div class="bw-admin-box">
                <h3><?php esc_html_e('Quick Start', 'bracefox-blessurewijzer'); ?></h3>
                <ol>
                    <li><?php esc_html_e('Enter your OpenAI API key above', 'bracefox-blessurewijzer'); ?></li>
                    <li><?php esc_html_e('Select your preferred AI model', 'bracefox-blessurewijzer'); ?></li>
                    <li><?php esc_html_e('Test the connection', 'bracefox-blessurewijzer'); ?></li>
                    <li><?php esc_html_e('Add the shortcode [blessurewijzer] to any page', 'bracefox-blessurewijzer'); ?></li>
                </ol>
            </div>

            <div class="bw-admin-box">
                <h3><?php esc_html_e('Shortcode Usage', 'bracefox-blessurewijzer'); ?></h3>
                <p><?php esc_html_e('Use this shortcode to display the chat widget:', 'bracefox-blessurewijzer'); ?></p>
                <code>[blessurewijzer]</code>
                <p style="margin-top: 10px;"><?php esc_html_e('With custom title:', 'bracefox-blessurewijzer'); ?></p>
                <code>[blessurewijzer title="Stel je vraag"]</code>
            </div>

            <div class="bw-admin-box">
                <h3><?php esc_html_e('Documentation', 'bracefox-blessurewijzer'); ?></h3>
                <ul>
                    <li><a href="https://platform.openai.com/docs" target="_blank"><?php esc_html_e('OpenAI API Docs', 'bracefox-blessurewijzer'); ?></a></li>
                    <li><a href="https://bracefox.nl" target="_blank"><?php esc_html_e('Bracefox Support', 'bracefox-blessurewijzer'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Test API connection
    $('#bw-test-api').on('click', function() {
        var $button = $(this);
        var $result = $('#bw-test-result');

        $button.prop('disabled', true).text('<?php esc_html_e('Testing...', 'bracefox-blessurewijzer'); ?>');
        $result.html('');

        $.ajax({
            url: bracefoxBWAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'bw_test_connection',
                nonce: bracefoxBWAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p>' +
                        '<?php esc_html_e('Connection successful!', 'bracefox-blessurewijzer'); ?>' +
                        '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error inline"><p>' +
                        response.data.message +
                        '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error inline"><p>' +
                    '<?php esc_html_e('Request failed. Please try again.', 'bracefox-blessurewijzer'); ?>' +
                    '</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_html_e('Test API Connection', 'bracefox-blessurewijzer'); ?>');
            }
        });
    });

    // Clear cache
    $('#bw-clear-cache').on('click', function() {
        var $button = $(this);
        var $result = $('#bw-cache-result');

        $button.prop('disabled', true).text('<?php esc_html_e('Clearing...', 'bracefox-blessurewijzer'); ?>');
        $result.html('');

        $.ajax({
            url: bracefoxBWAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'bw_clear_cache',
                nonce: bracefoxBWAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p>' +
                        '<?php esc_html_e('Cache cleared successfully!', 'bracefox-blessurewijzer'); ?>' +
                        '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error inline"><p>' +
                        response.data.message +
                        '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error inline"><p>' +
                    '<?php esc_html_e('Request failed. Please try again.', 'bracefox-blessurewijzer'); ?>' +
                    '</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_html_e('Clear All Caches', 'bracefox-blessurewijzer'); ?>');
            }
        });
    });
});
</script>
