<?php
/**
 * Stats Repository
 *
 * Handles fetching analytics and statistics data.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/repositories
 */

class Bracefox_BW_Stats_Repository {

    /**
     * Get total conversation count
     *
     * @param string $start_date Start date (Y-m-d format)
     * @param string $end_date End date (Y-m-d format)
     * @return int Total conversations
     */
    public function get_total_conversations($start_date = null, $end_date = null) {
        global $wpdb;

        $sql = "SELECT COUNT(*) FROM " . BRACEFOX_BW_TABLE_SESSIONS . " WHERE 1=1";

        if ($start_date) {
            $sql .= $wpdb->prepare(" AND started_at >= %s", $start_date . ' 00:00:00');
        }

        if ($end_date) {
            $sql .= $wpdb->prepare(" AND started_at <= %s", $end_date . ' 23:59:59');
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Get conversion rate (clicks / total conversations)
     *
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return float Conversion rate (0-100)
     */
    public function get_conversion_rate($start_date = null, $end_date = null) {
        global $wpdb;

        $total = $this->get_total_conversations($start_date, $end_date);

        if ($total === 0) {
            return 0.0;
        }

        $sql = "SELECT COUNT(DISTINCT session_id) FROM " . BRACEFOX_BW_TABLE_RECOMMENDATIONS . " WHERE clicked = 1";

        if ($start_date || $end_date) {
            $sql .= " AND session_id IN (
                SELECT session_id FROM " . BRACEFOX_BW_TABLE_SESSIONS . " WHERE 1=1";

            if ($start_date) {
                $sql .= $wpdb->prepare(" AND started_at >= %s", $start_date . ' 00:00:00');
            }

            if ($end_date) {
                $sql .= $wpdb->prepare(" AND started_at <= %s", $end_date . ' 23:59:59');
            }

            $sql .= ")";
        }

        $conversions = (int) $wpdb->get_var($sql);

        return ($conversions / $total) * 100;
    }

    /**
     * Get most recommended products
     *
     * @param int $limit Number of products to return
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Array of product IDs with counts
     */
    public function get_top_products($limit = 10, $start_date = null, $end_date = null) {
        global $wpdb;

        $sql = "SELECT product_id, COUNT(*) as count
                FROM " . BRACEFOX_BW_TABLE_RECOMMENDATIONS;

        $where_clauses = array();

        if ($start_date || $end_date) {
            $session_filter = "session_id IN (
                SELECT session_id FROM " . BRACEFOX_BW_TABLE_SESSIONS . " WHERE 1=1";

            if ($start_date) {
                $session_filter .= $wpdb->prepare(" AND started_at >= %s", $start_date . ' 00:00:00');
            }

            if ($end_date) {
                $session_filter .= $wpdb->prepare(" AND started_at <= %s", $end_date . ' 23:59:59');
            }

            $session_filter .= ")";
            $where_clauses[] = $session_filter;
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $sql .= " GROUP BY product_id ORDER BY count DESC LIMIT %d";

        $results = $wpdb->get_results($wpdb->prepare($sql, $limit), ARRAY_A);

        return $results ? $results : array();
    }

    /**
     * Get most common complaints (word frequency analysis)
     *
     * @param int $limit Number of terms to return
     * @return array Array of common words
     */
    public function get_common_complaints($limit = 20) {
        global $wpdb;

        // Get all user messages
        $sql = "SELECT content FROM " . BRACEFOX_BW_TABLE_MESSAGES . "
                WHERE role = 'user'
                ORDER BY created_at DESC
                LIMIT 500";

        $messages = $wpdb->get_col($sql);

        if (empty($messages)) {
            return array();
        }

        // Simple word frequency analysis
        $word_counts = array();
        $stopwords = $this->get_dutch_stopwords();

        foreach ($messages as $message) {
            $words = preg_split('/\s+/', strtolower($message));

            foreach ($words as $word) {
                // Clean word
                $word = preg_replace('/[^a-zà-ÿ]/u', '', $word);

                // Skip short words and stopwords
                if (strlen($word) < 3 || in_array($word, $stopwords)) {
                    continue;
                }

                if (!isset($word_counts[$word])) {
                    $word_counts[$word] = 0;
                }

                $word_counts[$word]++;
            }
        }

        // Sort by frequency
        arsort($word_counts);

        // Return top N
        return array_slice($word_counts, 0, $limit, true);
    }

    /**
     * Get session statistics
     *
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return array Session stats
     */
    public function get_session_stats($start_date = null, $end_date = null) {
        global $wpdb;

        $where = "1=1";

        if ($start_date) {
            $where .= $wpdb->prepare(" AND started_at >= %s", $start_date . ' 00:00:00');
        }

        if ($end_date) {
            $where .= $wpdb->prepare(" AND started_at <= %s", $end_date . ' 23:59:59');
        }

        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'abandoned' THEN 1 ELSE 0 END) as abandoned,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active
                FROM " . BRACEFOX_BW_TABLE_SESSIONS . "
                WHERE $where";

        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Get average messages per session
     *
     * @return float Average messages
     */
    public function get_avg_messages_per_session() {
        global $wpdb;

        $sql = "SELECT AVG(message_count) FROM (
                    SELECT COUNT(*) as message_count
                    FROM " . BRACEFOX_BW_TABLE_MESSAGES . "
                    GROUP BY session_id
                ) as counts";

        $avg = $wpdb->get_var($sql);

        return $avg ? floatval($avg) : 0.0;
    }

    /**
     * Dutch stopwords for word frequency analysis
     */
    private function get_dutch_stopwords() {
        return array(
            'de', 'het', 'een', 'en', 'van', 'ik', 'te', 'dat', 'die', 'in',
            'een', 'hij', 'het', 'op', 'aan', 'met', 'als', 'voor', 'van',
            'zijn', 'er', 'maar', 'om', 'hem', 'dan', 'zou', 'of', 'wat',
            'mijn', 'men', 'dit', 'zo', 'door', 'over', 'ze', 'zich', 'bij',
            'ook', 'tot', 'je', 'mij', 'uit', 'der', 'daar', 'haar', 'naar',
            'heb', 'hoe', 'heeft', 'hebben', 'deze', 'u', 'want', 'nog',
            'zal', 'me', 'zij', 'nu', 'ge', 'geen', 'omdat', 'iets', 'worden',
            'toch', 'al', 'waren', 'veel', 'meer', 'doen', 'toen', 'moet',
            'ben', 'zonder', 'kan', 'hun', 'dus', 'alles', 'onder', 'ja',
            'eens', 'hier', 'wie', 'werd', 'altijd', 'doch', 'wordt', 'wezen',
            'kunnen', 'ons', 'zelf', 'tegen', 'na', 'reeds', 'wil', 'kon',
            'niets', 'uw', 'iemand', 'geweest', 'andere', 'waar', 'mee', 'heeft',
        );
    }

    /**
     * Save session data
     *
     * @param string $session_id Session ID
     * @param array $data Session data
     * @return bool Success
     */
    public function save_session($session_id, $data = array()) {
        global $wpdb;

        $defaults = array(
            'user_ip' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'started_at' => current_time('mysql'),
            'status' => 'active',
        );

        $data = wp_parse_args($data, $defaults);
        $data['session_id'] = $session_id;

        return $wpdb->insert(
            BRACEFOX_BW_TABLE_SESSIONS,
            $data,
            array('%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * Update session status
     *
     * @param string $session_id Session ID
     * @param string $status New status
     * @return bool Success
     */
    public function update_session_status($session_id, $status) {
        global $wpdb;

        return $wpdb->update(
            BRACEFOX_BW_TABLE_SESSIONS,
            array(
                'status' => $status,
                'ended_at' => current_time('mysql'),
            ),
            array('session_id' => $session_id),
            array('%s', '%s'),
            array('%s')
        );
    }

    /**
     * Save message
     *
     * @param string $session_id Session ID
     * @param string $role Message role (user/assistant/system)
     * @param string $content Message content
     * @param int $tokens Tokens used
     * @return bool Success
     */
    public function save_message($session_id, $role, $content, $tokens = 0) {
        global $wpdb;

        return $wpdb->insert(
            BRACEFOX_BW_TABLE_MESSAGES,
            array(
                'session_id' => $session_id,
                'role' => $role,
                'content' => $content,
                'tokens_used' => $tokens,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
    }

    /**
     * Save product recommendation
     *
     * @param string $session_id Session ID
     * @param int $product_id Product ID
     * @return bool Success
     */
    public function save_recommendation($session_id, $product_id) {
        global $wpdb;

        return $wpdb->insert(
            BRACEFOX_BW_TABLE_RECOMMENDATIONS,
            array(
                'session_id' => $session_id,
                'product_id' => $product_id,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%s')
        );
    }

    /**
     * Track product click
     *
     * @param string $session_id Session ID
     * @param int $product_id Product ID
     * @return bool Success
     */
    public function track_product_click($session_id, $product_id) {
        global $wpdb;

        return $wpdb->update(
            BRACEFOX_BW_TABLE_RECOMMENDATIONS,
            array(
                'clicked' => 1,
                'clicked_at' => current_time('mysql'),
            ),
            array(
                'session_id' => $session_id,
                'product_id' => $product_id,
            ),
            array('%d', '%s'),
            array('%s', '%d')
        );
    }

    /**
     * Get client IP address
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

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}
