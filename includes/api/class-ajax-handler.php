<?php
/**
 * AJAX Handler
 *
 * Handles all AJAX requests from the frontend.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/api
 */

class Bracefox_BW_Ajax_Handler {

    /**
     * OpenAI client
     */
    private $ai_client;

    /**
     * Rate limiter
     */
    private $rate_limiter;

    /**
     * Stats repository
     */
    private $stats_repo;

    /**
     * Product repository
     */
    private $product_repo;

    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_client = new Bracefox_BW_OpenAI_Client();
        $this->rate_limiter = new Bracefox_BW_Rate_Limiter();
        $this->stats_repo = new Bracefox_BW_Stats_Repository();
        $this->product_repo = new Bracefox_BW_Product_Repository();
    }

    /**
     * Handle send message AJAX request
     */
    public function send_message() {
        // Verify nonce
        if (!check_ajax_referer('bw_chat_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'bracefox-blessurewijzer'),
            ), 403);
        }

        // Check rate limiting
        if (!$this->rate_limiter->check()) {
            wp_send_json_error(array(
                'message' => __('Je hebt te veel vragen gesteld. Wacht even en probeer het opnieuw.', 'bracefox-blessurewijzer'),
                'code' => 'rate_limited',
            ), 429);
        }

        // Get and sanitize input
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $history = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : array();

        // Validate input
        if (empty($message)) {
            wp_send_json_error(array(
                'message' => __('Bericht mag niet leeg zijn.', 'bracefox-blessurewijzer'),
            ), 400);
        }

        // Generate session ID if not provided
        if (empty($session_id)) {
            $session_id = $this->generate_session_id();
            $this->stats_repo->save_session($session_id);
        }

        // Check for severe symptoms
        $has_severe_symptoms = $this->detect_severe_symptoms($message);

        // Build conversation messages
        $messages = $history;
        $messages[] = array(
            'role' => 'user',
            'content' => $message,
        );

        // Save user message
        $this->stats_repo->save_message($session_id, 'user', $message);

        // Get AI response
        $ai_response = $this->ai_client->chat($messages);

        if (is_wp_error($ai_response)) {
            // Log error
            error_log('Blessurewijzer AI Error: ' . $ai_response->get_error_message());

            wp_send_json_error(array(
                'message' => __('Er ging iets mis bij het verwerken van je vraag. Probeer het later opnieuw.', 'bracefox-blessurewijzer'),
                'code' => 'ai_error',
            ), 500);
        }

        $response_content = $ai_response['content'];
        $tokens_used = $ai_response['tokens_used'];

        // Validate response structure
        $validation = $this->ai_client->validate_response($response_content);
        if (is_wp_error($validation)) {
            error_log('Blessurewijzer Response Validation Error: ' . $validation->get_error_message());

            wp_send_json_error(array(
                'message' => __('Het antwoord kon niet worden verwerkt. Probeer je vraag anders te formuleren.', 'bracefox-blessurewijzer'),
                'code' => 'parse_error',
            ), 500);
        }

        // Save assistant message
        $this->stats_repo->save_message($session_id, 'assistant', $ai_response['raw_content'], $tokens_used);

        // If advice with product recommendation, save it
        if ($response_content['message_type'] === 'advice' && isset($response_content['product_recommendation'])) {
            $product_id = $response_content['product_recommendation']['product_id'];
            $this->stats_repo->save_recommendation($session_id, $product_id);

            // Enrich product data
            $product_data = $this->product_repo->get_product($product_id);
            if ($product_data) {
                $response_content['product_recommendation']['product_data'] = $product_data;
            }
        }

        // Enrich blog data
        if (isset($response_content['related_blogs']) && !empty($response_content['related_blogs'])) {
            $blog_repo = new Bracefox_BW_Blog_Repository();
            $enriched_blogs = array();

            foreach ($response_content['related_blogs'] as $blog_id) {
                $blog_data = $blog_repo->get_blog($blog_id);
                if ($blog_data) {
                    $enriched_blogs[] = $blog_data;
                }
            }

            $response_content['related_blogs_data'] = $enriched_blogs;
        }

        // Add severe symptoms warning if detected
        if ($has_severe_symptoms) {
            $response_content['severity_warning'] = true;
        }

        // Update session status
        if ($response_content['message_type'] === 'advice') {
            $this->stats_repo->update_session_status($session_id, 'completed');
        }

        // Send response
        wp_send_json_success(array(
            'session_id' => $session_id,
            'response' => $response_content,
            'tokens_used' => $tokens_used,
        ));
    }

    /**
     * Handle product click tracking
     */
    public function track_product_click() {
        // Verify nonce
        if (!check_ajax_referer('bw_chat_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'bracefox-blessurewijzer'),
            ), 403);
        }

        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

        if (empty($session_id) || empty($product_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid parameters.', 'bracefox-blessurewijzer'),
            ), 400);
        }

        $this->stats_repo->track_product_click($session_id, $product_id);

        wp_send_json_success(array(
            'message' => __('Click tracked.', 'bracefox-blessurewijzer'),
        ));
    }

    /**
     * Get analytics data (admin only)
     */
    public function get_analytics() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized.', 'bracefox-blessurewijzer'),
            ), 403);
        }

        // Verify nonce
        if (!check_ajax_referer('bw_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'bracefox-blessurewijzer'),
            ), 403);
        }

        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : null;
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : null;

        $analytics = array(
            'total_conversations' => $this->stats_repo->get_total_conversations($start_date, $end_date),
            'conversion_rate' => $this->stats_repo->get_conversion_rate($start_date, $end_date),
            'top_products' => $this->stats_repo->get_top_products(10, $start_date, $end_date),
            'common_complaints' => $this->stats_repo->get_common_complaints(20),
            'session_stats' => $this->stats_repo->get_session_stats($start_date, $end_date),
            'avg_messages' => $this->stats_repo->get_avg_messages_per_session(),
        );

        wp_send_json_success($analytics);
    }

    /**
     * Generate unique session ID
     *
     * @return string Session ID
     */
    private function generate_session_id() {
        return 'bw_' . wp_generate_password(32, false);
    }

    /**
     * Test API connection (admin only)
     */
    public function test_connection() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized.', 'bracefox-blessurewijzer'),
            ), 403);
        }

        // Verify nonce
        if (!check_ajax_referer('bw_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'bracefox-blessurewijzer'),
            ), 403);
        }

        $result = $this->ai_client->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
            ));
        }

        wp_send_json_success(array(
            'message' => __('Connection successful!', 'bracefox-blessurewijzer'),
        ));
    }

    /**
     * Clear cache (admin only)
     */
    public function clear_cache() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('Unauthorized.', 'bracefox-blessurewijzer'),
            ), 403);
        }

        // Verify nonce
        if (!check_ajax_referer('bw_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'bracefox-blessurewijzer'),
            ), 403);
        }

        // Clear all product and blog caches
        delete_transient('bw_all_products');
        delete_transient('bw_all_blogs');

        wp_send_json_success(array(
            'message' => __('Cache cleared successfully!', 'bracefox-blessurewijzer'),
        ));
    }

    /**
     * Detect severe symptoms in user message
     *
     * @param string $message User message
     * @return bool True if severe symptoms detected
     */
    private function detect_severe_symptoms($message) {
        $message_lower = strtolower($message);

        $severe_keywords = array(
            'heel veel pijn',
            'extreme pijn',
            'ondraaglijke pijn',
            'kan niet lopen',
            'kan niet bewegen',
            'opgezwollen',
            'dik opgezwollen',
            'erg gezwollen',
            'roodheid',
            'heel rood',
            'warm aanvoelt',
            'warmte',
            'koorts',
            'naar ziekenhuis',
            'ambulance',
            'ongeluk gehad',
            'gevallen',
            'trauma',
            'gebroken',
            'knak gehoord',
            'krakend geluid',
            'kan niet staan',
            'verlamming',
            'geen gevoel',
            'gevoelloos',
        );

        foreach ($severe_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
