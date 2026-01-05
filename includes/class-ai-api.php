<?php
/**
 * File: includes/class-ai-api.php
 * AI Rewriter API Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_API {
    
    private $api_key;
    private $model = 'gpt-3.5-turbo';
    private $temperature = 0.7;
    private $max_tokens = 2000;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    
    public function __construct($api_key = null) {
        if ($api_key) {
            $this->api_key = $api_key;
        }
    }
    
    public function set_config($config) {
        if (isset($config['api_key'])) {
            $this->api_key = $config['api_key'];
        }
        if (isset($config['model'])) {
            $this->model = $config['model'];
        }
        if (isset($config['temperature'])) {
            $this->temperature = floatval($config['temperature']);
        }
        if (isset($config['max_tokens'])) {
            $this->max_tokens = intval($config['max_tokens']);
        }
    }
    
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key is required'
            );
        }
        
        $response = wp_remote_post('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return array(
                'success' => true,
                'message' => 'API connection successful! ✅'
            );
        } elseif ($status_code === 401) {
            return array(
                'success' => false,
                'message' => 'Invalid API key. Please check your OpenAI API key.'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'API error: HTTP ' . $status_code
            );
        }
    }
    
    public function get_available_models() {
        if (empty($this->api_key)) {
            return array();
        }
        
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['data']) && is_array($data['data'])) {
            // Filter untuk model GPT yang relevan
            $gpt_models = array();
            foreach ($data['data'] as $model) {
                if (strpos($model['id'], 'gpt') !== false) {
                    $gpt_models[] = array(
                        'id' => $model['id'],
                        'description' => isset($model['description']) ? $model['description'] : 'OpenAI Model'
                    );
                }
            }
            return $gpt_models;
        }
        
        return array();
    }
    
    public function rewrite_content($prompt) {
        if (empty($this->api_key)) {
            throw new Exception('API key not configured');
        }
        
        $messages = array(
            array(
                'role' => 'system',
                'content' => 'You are a professional content rewriter. Rewrite the given content to be unique, engaging, and SEO-friendly while maintaining the original meaning and structure.'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        );
        
        $data = array(
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens
        );
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 120
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = 'API error: HTTP ' . $status_code;
            if (isset($result['error']['message'])) {
                $error_message .= ' - ' . $result['error']['message'];
            }
            throw new Exception($error_message);
        }
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        $content = $result['choices'][0]['message']['content'];
        $cost = $this->calculate_cost($result);
        
        return array(
            'content' => $content,
            'cost' => $cost,
            'model' => $this->model,
            'tokens_used' => isset($result['usage']['total_tokens']) ? $result['usage']['total_tokens'] : 0
        );
    }
    
    private function calculate_cost($result) {
        if (!isset($result['usage'])) {
            return 0;
        }
        
        $usage = $result['usage'];
        $prompt_tokens = isset($usage['prompt_tokens']) ? $usage['prompt_tokens'] : 0;
        $completion_tokens = isset($usage['completion_tokens']) ? $usage['completion_tokens'] : 0;
        
        // Pricing per 1K tokens (as of 2024)
        $pricing = array(
            'gpt-3.5-turbo' => array('prompt' => 0.0015, 'completion' => 0.002),
            'gpt-4' => array('prompt' => 0.03, 'completion' => 0.06),
            'gpt-4-turbo' => array('prompt' => 0.01, 'completion' => 0.03),
            'gpt-4o' => array('prompt' => 0.005, 'completion' => 0.015),
            'gpt-4o-mini' => array('prompt' => 0.00015, 'completion' => 0.0006)
        );
        
        $model_pricing = isset($pricing[$this->model]) ? $pricing[$this->model] : $pricing['gpt-3.5-turbo'];
        
        $prompt_cost = ($prompt_tokens / 1000) * $model_pricing['prompt'];
        $completion_cost = ($completion_tokens / 1000) * $model_pricing['completion'];
        
        return $prompt_cost + $completion_cost;
    }
}
?>