<?php
/**
 * OpenAI Client
 *
 * Handles communication with OpenAI API for AI-powered chat.
 *
 * @package    Bracefox_Blessurewijzer
 * @subpackage Bracefox_Blessurewijzer/includes/api
 */

class Bracefox_BW_OpenAI_Client {

    /**
     * API endpoint
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * API key
     */
    private $api_key;

    /**
     * Model to use
     */
    private $model;

    /**
     * Temperature setting
     */
    private $temperature;

    /**
     * Max tokens
     */
    private $max_tokens;

    /**
     * Timeout in seconds
     */
    private $timeout;

    /**
     * Prompt builder
     */
    private $prompt_builder;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_key = get_option('bracefox_bw_api_key', '');
        $this->model = get_option('bracefox_bw_model', 'gpt-4o-mini');
        $this->temperature = floatval(get_option('bracefox_bw_temperature', 0.7));
        $this->max_tokens = intval(get_option('bracefox_bw_max_tokens', 1500));
        $this->timeout = intval(get_option('bracefox_bw_timeout', 30));

        $this->prompt_builder = new Bracefox_BW_Prompt_Builder();
    }

    /**
     * Send chat message and get AI response
     *
     * @param array $messages Array of conversation messages
     * @return array|WP_Error Response data or error
     */
    public function chat($messages) {
        if (empty($this->api_key)) {
            return new WP_Error(
                'no_api_key',
                __('OpenAI API key not configured.', 'bracefox-blessurewijzer')
            );
        }

        // Build system prompt with context
        $user_message = '';
        foreach ($messages as $msg) {
            if ($msg['role'] === 'user') {
                $user_message = $msg['content'];
                break;
            }
        }

        $system_prompt = $this->prompt_builder->build_system_prompt($user_message);

        // Prepare messages array for API
        $api_messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt,
            )
        );

        // Add conversation history
        foreach ($messages as $message) {
            $api_messages[] = array(
                'role' => $message['role'],
                'content' => $message['content'],
            );
        }

        // Prepare request body
        $body = array(
            'model' => $this->model,
            'messages' => $api_messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens,
            'response_format' => array('type' => 'json_object'),
        );

        // Make API request
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body' => wp_json_encode($body),
            'timeout' => $this->timeout,
        ));

        // Handle response
        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(
                    __('OpenAI API error: %s', 'bracefox-blessurewijzer'),
                    $response_body
                ),
                array('status' => $response_code)
            );
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'json_error',
                __('Failed to parse API response.', 'bracefox-blessurewijzer')
            );
        }

        // Extract response content
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error(
                'invalid_response',
                __('Invalid API response format.', 'bracefox-blessurewijzer')
            );
        }

        $content = $data['choices'][0]['message']['content'];
        $tokens_used = isset($data['usage']['total_tokens']) ? $data['usage']['total_tokens'] : 0;

        // Parse JSON response from AI
        $parsed_response = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'ai_json_error',
                __('AI response is not valid JSON.', 'bracefox-blessurewijzer'),
                array('raw_content' => $content)
            );
        }

        return array(
            'content' => $parsed_response,
            'raw_content' => $content,
            'tokens_used' => $tokens_used,
            'model' => $this->model,
        );
    }

    /**
     * Validate AI response structure
     *
     * @param array $response Parsed AI response
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    public function validate_response($response) {
        $required_fields = array('message_type', 'personal_message');

        foreach ($required_fields as $field) {
            if (!isset($response[$field])) {
                return new WP_Error(
                    'invalid_response',
                    sprintf(
                        __('Missing required field: %s', 'bracefox-blessurewijzer'),
                        $field
                    )
                );
            }
        }

        // Validate message_type
        if (!in_array($response['message_type'], array('question', 'advice'))) {
            return new WP_Error(
                'invalid_message_type',
                __('Invalid message_type in AI response.', 'bracefox-blessurewijzer')
            );
        }

        // If advice, check for product recommendation
        if ($response['message_type'] === 'advice') {
            if (!isset($response['product_recommendation']) || !isset($response['health_advice'])) {
                return new WP_Error(
                    'incomplete_advice',
                    __('Advice response missing product or health advice.', 'bracefox-blessurewijzer')
                );
            }
        }

        return true;
    }

    /**
     * Test API connection
     *
     * @return bool|WP_Error True if successful, WP_Error on failure
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error(
                'no_api_key',
                __('No API key provided.', 'bracefox-blessurewijzer')
            );
        }

        $test_messages = array(
            array(
                'role' => 'user',
                'content' => 'Test connection',
            )
        );

        $response = $this->chat($test_messages);

        if (is_wp_error($response)) {
            return $response;
        }

        return true;
    }
}
