<?php
/**
 * Cache Service
 *
 * Handles caching of product data, blog posts, and AI responses.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/services
 */

class Bracefox_BW_Cache_Service {

    /**
     * Cache key prefix
     */
    private $prefix = 'bw_';

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed|false Cached data or false if not found
     */
    public function get($key) {
        $cache_key = $this->prefix . $key;
        return get_transient($cache_key);
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $ttl Time to live in seconds (default from settings)
     * @return bool True on success, false on failure
     */
    public function set($key, $data, $ttl = null) {
        if ($ttl === null) {
            $ttl = get_option('bracefox_bw_cache_ttl', 3600);
        }

        $cache_key = $this->prefix . $key;
        return set_transient($cache_key, $data, $ttl);
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool True on success, false on failure
     */
    public function delete($key) {
        $cache_key = $this->prefix . $key;
        return delete_transient($cache_key);
    }

    /**
     * Clear all plugin caches
     *
     * @return void
     */
    public function clear_all() {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options}
                WHERE option_name LIKE %s
                OR option_name LIKE %s",
                '_transient_' . $this->prefix . '%',
                '_transient_timeout_' . $this->prefix . '%'
            )
        );
    }

    /**
     * Get or set cached data (get with callback)
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate data if cache miss
     * @param int $ttl Time to live in seconds
     * @return mixed Cached or generated data
     */
    public function remember($key, $callback, $ttl = null) {
        $data = $this->get($key);

        if ($data === false) {
            $data = $callback();
            $this->set($key, $data, $ttl);
        }

        return $data;
    }

    /**
     * Invalidate product-related caches
     * Called when products are updated
     */
    public function invalidate_products() {
        $this->delete('products_all');
        $this->delete('products_catalog');
    }

    /**
     * Invalidate blog-related caches
     * Called when blog posts are updated
     */
    public function invalidate_blogs() {
        $this->delete('blogs_all');
        $this->delete('blogs_catalog');
    }
}
