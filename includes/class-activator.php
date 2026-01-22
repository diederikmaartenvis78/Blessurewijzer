<?php
/**
 * Fired during plugin activation
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes
 */

class Bracefox_BW_Activator {

    /**
     * Activation hook callback.
     * Creates database tables and sets default options.
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Sessions table
        $sql_sessions = "CREATE TABLE " . BRACEFOX_BW_TABLE_SESSIONS . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL UNIQUE,
            user_ip VARCHAR(45),
            user_agent TEXT,
            started_at DATETIME NOT NULL,
            ended_at DATETIME,
            status ENUM('active', 'completed', 'abandoned') DEFAULT 'active',
            INDEX idx_session_id (session_id),
            INDEX idx_started_at (started_at),
            INDEX idx_status (status)
        ) $charset_collate;";

        dbDelta($sql_sessions);

        // Messages table
        $sql_messages = "CREATE TABLE " . BRACEFOX_BW_TABLE_MESSAGES . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            role ENUM('user', 'assistant', 'system') NOT NULL,
            content TEXT NOT NULL,
            tokens_used INT UNSIGNED,
            created_at DATETIME NOT NULL,
            INDEX idx_session_id (session_id),
            INDEX idx_created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql_messages);

        // Recommendations table
        $sql_recommendations = "CREATE TABLE " . BRACEFOX_BW_TABLE_RECOMMENDATIONS . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            clicked TINYINT(1) DEFAULT 0,
            clicked_at DATETIME,
            created_at DATETIME NOT NULL,
            INDEX idx_session_id (session_id),
            INDEX idx_product_id (product_id),
            INDEX idx_clicked (clicked)
        ) $charset_collate;";

        dbDelta($sql_recommendations);

        // Store database version
        update_option('bracefox_bw_db_version', '1.0');
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        $defaults = array(
            'bracefox_bw_api_provider' => 'openai',
            'bracefox_bw_api_key' => '',
            'bracefox_bw_model' => 'gpt-4o-mini',
            'bracefox_bw_temperature' => 0.7,
            'bracefox_bw_max_tokens' => 1500,
            'bracefox_bw_timeout' => 30,
            'bracefox_bw_cache_ttl' => 3600,
            'bracefox_bw_rate_limit_max' => 10,
            'bracefox_bw_rate_limit_window' => 60,
            'bracefox_bw_enabled' => true,
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
