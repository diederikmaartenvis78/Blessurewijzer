<?php
/**
 * Rate Limiter Service
 *
 * Prevents abuse by limiting requests per IP address.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/services
 */

class Bracefox_BW_Rate_Limiter {

    /**
     * Transient prefix for rate limiting
     */
    private $prefix = 'bw_rate_';

    /**
     * Maximum requests allowed
     */
    private $max_requests;

    /**
     * Time window in seconds
     */
    private $window;

    /**
     * Constructor
     */
    public function __construct() {
        $this->max_requests = get_option('bracefox_bw_rate_limit_max', 10);
        $this->window = get_option('bracefox_bw_rate_limit_window', 60);
    }

    /**
     * Check if request is allowed
     *
     * @param string $identifier Unique identifier (usually IP address)
     * @return bool True if allowed, false if rate limited
     */
    public function check($identifier = null) {
        if ($identifier === null) {
            $identifier = $this->get_client_ip();
        }

        $key = $this->prefix . md5($identifier);
        $count = get_transient($key);

        if ($count === false) {
            // First request in this window
            set_transient($key, 1, $this->window);
            return true;
        }

        if ($count >= $this->max_requests) {
            // Rate limit exceeded
            return false;
        }

        // Increment counter
        set_transient($key, $count + 1, $this->window);
        return true;
    }

    /**
     * Get remaining requests
     *
     * @param string $identifier Unique identifier
     * @return int Number of remaining requests
     */
    public function get_remaining($identifier = null) {
        if ($identifier === null) {
            $identifier = $this->get_client_ip();
        }

        $key = $this->prefix . md5($identifier);
        $count = get_transient($key);

        if ($count === false) {
            return $this->max_requests;
        }

        return max(0, $this->max_requests - $count);
    }

    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Sanitize IP
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    /**
     * Reset rate limit for an identifier
     *
     * @param string $identifier Unique identifier
     * @return bool True on success
     */
    public function reset($identifier = null) {
        if ($identifier === null) {
            $identifier = $this->get_client_ip();
        }

        $key = $this->prefix . md5($identifier);
        return delete_transient($key);
    }
}
