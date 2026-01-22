<?php
/**
 * Fired during plugin deactivation
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes
 */

class Bracefox_BW_Deactivator {

    /**
     * Deactivation hook callback.
     * Cleans up transients and flushes rewrite rules.
     */
    public static function deactivate() {
        // Clear all transient caches
        self::clear_transients();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear all plugin transients
     */
    private static function clear_transients() {
        global $wpdb;

        // Delete all transients that start with our prefix
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_bw_%'
            OR option_name LIKE '_transient_timeout_bw_%'"
        );
    }
}
