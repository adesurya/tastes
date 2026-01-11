<?php
/**
 * AI Rewriter API Handler
 * Handles OpenAI API communication
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
                'message' => 'API key is empty'
            );
        }
        
        try {
            $response = wp_remote_post($this->api_url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode(array(
                    'model' => $this->model,
                    'messages' => array(
                        array('role' => 'user', 'content' => 'Hello')
                    ),
                    'max_tokens' => 10
                )),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => $response->get_error_message()
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['error'])) {
                return array(
                    'success' => false,
                    'message' => $data['error']['message'] ?? 'Unknown error'
                );
            }
            
            return array(
                'success' => true,
                'message' => 'Connection successful!'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    public function rewrite_content($prompt) {
        if (empty($this->api_key)) {
            throw new Exception('API key not configured');
        }
        
        $response = wp_remote_post($this->api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'model' => $this->model,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => $this->temperature,
                'max_tokens' => $this->max_tokens
            )),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            throw new Exception('OpenAI error: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response format');
        }
        
        return array(
            'content' => $data['choices'][0]['message']['content'],
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'cost' => $this->calculate_cost($data['usage'] ?? array())
        );
    }
    
    private function calculate_cost($usage) {
        $prompt_tokens = $usage['prompt_tokens'] ?? 0;
        $completion_tokens = $usage['completion_tokens'] ?? 0;
        
        // Pricing per 1K tokens (as of 2024)
        $rates = array(
            'gpt-3.5-turbo' => array('input' => 0.0015, 'output' => 0.002),
            'gpt-4' => array('input' => 0.03, 'output' => 0.06),
            'gpt-4-turbo' => array('input' => 0.01, 'output' => 0.03)
        );
        
        $rate = $rates[$this->model] ?? $rates['gpt-3.5-turbo'];
        
        $cost = ($prompt_tokens / 1000 * $rate['input']) + 
                ($completion_tokens / 1000 * $rate['output']);
        
        return round($cost, 4);
    }
    
    public function get_available_models() {
        return array(
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4o',
            'gpt-4o-mini'
        );
    }
}