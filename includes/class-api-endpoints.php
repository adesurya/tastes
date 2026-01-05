<?php
/**
 * File: includes/class-api-endpoints.php
 * API Endpoints for AI Rewriter - FIXED VERSION with actual processing
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_API_Endpoints {
    
    private $plugin;
    private $namespace = 'ai-rewriter/v1';
    
    public function __construct($plugin_instance) {
        $this->plugin = $plugin_instance;
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }
    
    public function register_endpoints() {
        // Status endpoint
        register_rest_route($this->namespace, '/status', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        // Single article rewrite
        register_rest_route($this->namespace, '/rewrite/(?P<post_id>\d+)', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'rewrite_single_article'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'auto_publish' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true
                ),
                'replace_images' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true
                )
            )
        ));
        
        // Bulk rewrite all drafts - FIXED VERSION
        register_rest_route($this->namespace, '/bulk-rewrite-all', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'bulk_rewrite_all_articles'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'auto_publish' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true
                ),
                'batch_size' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 1,
                    'minimum' => 1,
                    'maximum' => 5
                ),
                'max_articles' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 0
                ),
                'min_words' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 50
                ),
                'replace_images' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true
                ),
                'processing_mode' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'immediate',
                    'enum' => array('immediate', 'batch')
                )
            )
        ));
        
        // Batch status
        register_rest_route($this->namespace, '/batch/(?P<batch_id>[a-zA-Z0-9]+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_batch_status'),
            'permission_callback' => array($this, 'check_permissions')
        ));
        
        // List drafts
        register_rest_route($this->namespace, '/drafts', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'list_draft_articles'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'min_words' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 50
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'default' => 20,
                    'maximum' => 100
                )
            )
        ));
        
        error_log('AI Rewriter: API endpoints registered successfully');
    }
    
    public function check_permissions($request) {
        // Check if API is enabled
        if (!get_option('ai_rewriter_api_enabled', 1)) {
            return new WP_Error(
                'api_disabled',
                'API endpoints are disabled',
                array('status' => 403)
            );
        }
        
        // If authentication not required, allow access
        if (!get_option('ai_rewriter_api_require_auth', 1)) {
            return true;
        }
        
        // Check API key
        $api_key = $request->get_header('X-API-Key');
        if (empty($api_key)) {
            return new WP_Error(
                'missing_api_key',
                'API key required. Include X-API-Key header.',
                array('status' => 401)
            );
        }
        
        $stored_key = get_option('ai_rewriter_api_endpoint_key', '');
        if (empty($stored_key) || $api_key !== $stored_key) {
            return new WP_Error(
                'invalid_api_key',
                'Invalid API key provided.',
                array('status' => 401)
            );
        }
        
        return true;
    }
    
    public function get_status($request) {
        $openai_key = get_option('ai_rewriter_api_key', '');
        
        // Test OpenAI connection if key is configured
        $openai_status = false;
        $openai_message = 'Not configured';
        
        if (!empty($openai_key)) {
            try {
                // Load API class
                if (!class_exists('AI_Rewriter_API')) {
                    require_once AI_REWRITER_PLUGIN_PATH . 'includes/class-ai-api.php';
                }
                
                if (class_exists('AI_Rewriter_API')) {
                    $api = new AI_Rewriter_API($openai_key);
                    $test_result = $api->test_connection();
                    $openai_status = $test_result['success'];
                    $openai_message = $test_result['message'];
                }
            } catch (Exception $e) {
                $openai_message = 'Error: ' . $e->getMessage();
            }
        }
        
        // Count drafts
        $draft_count = wp_count_posts('post')->draft;
        
        // Get processing queue status
        $queue_status = $this->plugin->get_auto_queue_status();
        
        return new WP_REST_Response(array(
            'success' => true,
            'plugin_version' => defined('AI_REWRITER_VERSION') ? AI_REWRITER_VERSION : '2.0.0',
            'api_enabled' => get_option('ai_rewriter_api_enabled', 1),
            'openai_configured' => !empty($openai_key),
            'openai_status' => $openai_status,
            'openai_message' => $openai_message,
            'auth_required' => get_option('ai_rewriter_api_require_auth', 1),
            'draft_articles_count' => $draft_count,
            'auto_rewrite_enabled' => get_option('ai_rewriter_auto_rewrite_enabled', 0),
            'image_replacement_enabled' => get_option('ai_rewriter_auto_replace_images', 0),
            'queue_status' => $queue_status,
            'timestamp' => current_time('c')
        ), 200);
    }
    
    public function list_draft_articles($request) {
        $min_words = $request->get_param('min_words');
        $limit = $request->get_param('limit');
        
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $filtered_articles = array();
        
        foreach ($posts as $post) {
            $word_count = str_word_count(strip_tags($post->post_content));
            
            if ($word_count >= $min_words) {
                $processed = get_post_meta($post->ID, '_ai_rewriter_processed', true);
                
                $filtered_articles[] = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'word_count' => $word_count,
                    'date' => get_the_date('c', $post->ID),
                    'processed' => !empty($processed),
                    'processed_date' => $processed ?: null,
                    'edit_url' => get_edit_post_link($post->ID),
                    'preview_url' => get_preview_post_link($post->ID)
                );
            }
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'articles' => $filtered_articles,
            'total_found' => count($filtered_articles),
            'filters' => array(
                'min_words' => $min_words,
                'limit' => $limit
            )
        ), 200);
    }
    
    public function rewrite_single_article($request) {
        $post_id = $request->get_param('post_id');
        $auto_publish = $request->get_param('auto_publish');
        $replace_images = $request->get_param('replace_images');
        
        // Log API request if enabled
        if (get_option('ai_rewriter_api_log_requests', 1)) {
            error_log("AI Rewriter API: Single rewrite request for post {$post_id}");
        }
        
        try {
            // Use the existing plugin method for processing
            $result = $this->plugin->process_single_article_api($post_id, array(
                'auto_publish' => $auto_publish,
                'process_images' => $replace_images,
                'exclude_processed' => false
            ));
            
            if ($result['success']) {
                return new WP_REST_Response($result, 200);
            } else {
                return new WP_Error(
                    'processing_failed',
                    $result['message'],
                    array('status' => 400, 'data' => $result)
                );
            }
            
        } catch (Exception $e) {
            return new WP_Error(
                'processing_error',
                'Failed to process article: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
    
    public function bulk_rewrite_all_articles($request) {
        $params = $request->get_params();
        $auto_publish = $params['auto_publish'] ?? true;
        $batch_size = $params['batch_size'] ?? 1;
        $max_articles = $params['max_articles'] ?? 0;
        $min_words = $params['min_words'] ?? 50;
        $replace_images = $params['replace_images'] ?? true;
        $processing_mode = $params['processing_mode'] ?? 'immediate';
        
        // Validate OpenAI API key
        $openai_key = get_option('ai_rewriter_api_key', '');
        if (empty($openai_key)) {
            return new WP_Error(
                'missing_openai_key',
                'OpenAI API key not configured. Please configure it in plugin settings.',
                array('status' => 400)
            );
        }
        
        // Get draft posts
        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'posts_per_page' => $max_articles > 0 ? $max_articles : 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids'
        );
        
        $post_ids = get_posts($query_args);
        
        if (empty($post_ids)) {
            return new WP_Error(
                'no_drafts_found',
                'No draft articles found to process.',
                array('status' => 404)
            );
        }
        
        // Filter by word count
        $eligible_posts = array();
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $word_count = str_word_count(strip_tags($post->post_content));
                if ($word_count >= $min_words) {
                    $eligible_posts[] = array(
                        'id' => $post_id,
                        'title' => $post->post_title,
                        'word_count' => $word_count,
                        'date' => get_the_date('Y-m-d H:i:s', $post_id)
                    );
                }
            }
        }
        
        if (empty($eligible_posts)) {
            return new WP_Error(
                'no_eligible_articles',
                sprintf('No articles found that meet the criteria (minimum %d words).', $min_words),
                array('status' => 404)
            );
        }
        
        // Process immediately or create batch
        if ($processing_mode === 'immediate' && $batch_size === 1) {
            return $this->process_articles_immediately($eligible_posts, array(
                'auto_publish' => $auto_publish,
                'replace_images' => $replace_images,
                'min_words' => $min_words
            ));
        } else {
            return $this->create_processing_batch($eligible_posts, array(
                'auto_publish' => $auto_publish,
                'replace_images' => $replace_images,
                'batch_size' => $batch_size,
                'min_words' => $min_words
            ));
        }
    }
    
    private function process_articles_immediately($articles, $settings) {
        $results = array();
        $success_count = 0;
        $error_count = 0;
        $total_cost = 0;
        $total_tokens = 0;
        
        $start_time = microtime(true);
        
        foreach ($articles as $article) {
            try {
                // Add processing delay to respect API rate limits
                if (count($results) > 0) {
                    $delay = get_option('ai_rewriter_auto_processing_delay', 2);
                    sleep($delay);
                }
                
                $result = $this->plugin->process_single_article_api($article['id'], array(
                    'auto_publish' => $settings['auto_publish'],
                    'process_images' => $settings['replace_images'],
                    'exclude_processed' => false,
                    'min_words' => $settings['min_words']
                ));
                
                $results[] = $result;
                
                if ($result['success']) {
                    $success_count++;
                    $total_cost += $result['cost'] ?? 0;
                    $total_tokens += $result['tokens_used'] ?? 0;
                } else {
                    $error_count++;
                }
                
            } catch (Exception $e) {
                $error_result = array(
                    'success' => false,
                    'post_id' => $article['id'],
                    'title' => $article['title'],
                    'message' => 'Processing error: ' . $e->getMessage(),
                    'error_code' => $e->getCode()
                );
                
                $results[] = $error_result;
                $error_count++;
                
                error_log('AI Rewriter API bulk processing error for post ' . $article['id'] . ': ' . $e->getMessage());
            }
        }
        
        $end_time = microtime(true);
        $processing_time = round($end_time - $start_time, 2);
        
        // Log successful bulk processing
        if (get_option('ai_rewriter_api_log_requests', 1)) {
            error_log("AI Rewriter API: Bulk processing completed - {$success_count} success, {$error_count} errors, {$processing_time}s total");
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'processing_mode' => 'immediate',
            'total_articles' => count($articles),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'results' => $results,
            'statistics' => array(
                'total_cost' => $total_cost,
                'total_tokens' => $total_tokens,
                'processing_time_seconds' => $processing_time,
                'average_time_per_article' => count($articles) > 0 ? round($processing_time / count($articles), 2) : 0
            ),
            'timestamp' => current_time('c')
        ), 200);
    }
    
    private function create_processing_batch($articles, $settings) {
        $batch_id = 'batch_' . time() . '_' . wp_generate_password(8, false, false);
        
        $batch_data = array(
            'batch_id' => $batch_id,
            'status' => 'processing',
            'start_time' => current_time('mysql'),
            'end_time' => null,
            'total_articles' => count($articles),
            'processed_count' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'articles' => wp_list_pluck($articles, 'id'),
            'settings' => $settings,
            'results' => array(),
            'created_via' => 'api'
        );
        
        // Save batch data
        update_option('ai_rewriter_batch_' . $batch_id, $batch_data);
        
        // Schedule immediate processing
        wp_schedule_single_event(time() + 10, 'ai_rewriter_process_bulk_batch', array($batch_id));
        
        return new WP_REST_Response(array(
            'success' => true,
            'processing_mode' => 'batch',
            'batch_id' => $batch_id,
            'status' => 'created',
            'total_articles' => count($articles),
            'articles' => $articles,
            'settings' => $settings,
            'status_url' => rest_url($this->namespace . '/batch/' . $batch_id),
            'message' => 'Batch processing started. Use status_url to check progress.',
            'timestamp' => current_time('c')
        ), 202);
    }
    
    public function get_batch_status($request) {
        $batch_id = $request->get_param('batch_id');
        
        $batch_data = get_option('ai_rewriter_batch_' . $batch_id);
        
        if (!$batch_data) {
            return new WP_Error(
                'batch_not_found',
                'Batch not found or has been cleaned up',
                array('status' => 404)
            );
        }
        
        // Calculate progress
        $progress_percentage = $batch_data['total_articles'] > 0 ? 
            round(($batch_data['processed_count'] / $batch_data['total_articles']) * 100, 1) : 0;
        
        // Calculate statistics
        $total_cost = 0;
        $total_tokens = 0;
        
        foreach ($batch_data['results'] as $result) {
            if (isset($result['cost'])) {
                $total_cost += $result['cost'];
            }
            if (isset($result['tokens_used'])) {
                $total_tokens += $result['tokens_used'];
            }
        }
        
        $response_data = array(
            'success' => true,
            'batch_id' => $batch_id,
            'status' => $batch_data['status'],
            'progress_percentage' => $progress_percentage,
            'total_articles' => $batch_data['total_articles'],
            'processed_count' => $batch_data['processed_count'],
            'success_count' => $batch_data['success_count'],
            'error_count' => $batch_data['error_count'],
            'start_time' => $batch_data['start_time'],
            'end_time' => $batch_data['end_time'],
            'statistics' => array(
                'total_cost' => $total_cost,
                'total_tokens' => $total_tokens
            ),
            'timestamp' => current_time('c')
        );
        
        // Add completion details if finished
        if (in_array($batch_data['status'], array('completed', 'failed'))) {
            $response_data['final_message'] = $batch_data['final_message'] ?? '';
            $response_data['results'] = $batch_data['results'];
            
            if (isset($batch_data['total_time'])) {
                $response_data['total_time'] = $batch_data['total_time'];
            }
        }
        
        return new WP_REST_Response($response_data, 200);
    }
}

/**
 * MISSING CLASS: AI_Rewriter_API for OpenAI Integration
 * This class was referenced but missing from the original files
 */
if (!class_exists('AI_Rewriter_API')) {
    class AI_Rewriter_API {
        
        private $api_key;
        private $model = 'gpt-3.5-turbo';
        private $temperature = 0.7;
        private $max_tokens = 2000;
        private $base_url = 'https://api.openai.com/v1';
        
        public function __construct($api_key = null) {
            if ($api_key) {
                $this->api_key = $api_key;
            } else {
                $this->api_key = get_option('ai_rewriter_api_key', '');
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
                    'message' => 'API key not configured'
                );
            }
            
            $response = wp_remote_post($this->base_url . '/chat/completions', array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode(array(
                    'model' => $this->model,
                    'messages' => array(
                        array(
                            'role' => 'user',
                            'content' => 'Test connection'
                        )
                    ),
                    'max_tokens' => 10
                ))
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => 'Connection failed: ' . $response->get_error_message()
                );
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($status_code === 200) {
                return array(
                    'success' => true,
                    'message' => 'Connection successful'
                );
            } else {
                $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
                return array(
                    'success' => false,
                    'message' => 'API Error: ' . $error_message
                );
            }
        }
        
        public function rewrite_content($prompt) {
            if (empty($this->api_key)) {
                throw new Exception('OpenAI API key not configured');
            }
            
            $messages = array(
                array(
                    'role' => 'system',
                    'content' => 'You are an expert Indonesian news writer. Rewrite articles in professional Indonesian style while maintaining accuracy and readability.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            );
            
            $request_data = array(
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $this->temperature,
                'max_tokens' => $this->max_tokens
            );
            
            $response = wp_remote_post($this->base_url . '/chat/completions', array(
                'timeout' => 120,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode($request_data)
            ));
            
            if (is_wp_error($response)) {
                throw new Exception('OpenAI API request failed: ' . $response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($status_code !== 200) {
                $error_message = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
                throw new Exception('OpenAI API Error: ' . $error_message);
            }
            
            if (!isset($data['choices'][0]['message']['content'])) {
                throw new Exception('Invalid response from OpenAI API');
            }
            
            $content = trim($data['choices'][0]['message']['content']);
            $tokens_used = $data['usage']['total_tokens'] ?? 0;
            
            // Calculate approximate cost (GPT-3.5-turbo pricing as example)
            $cost = $this->calculate_cost($tokens_used, $this->model);
            
            return array(
                'content' => $content,
                'tokens_used' => $tokens_used,
                'cost' => $cost,
                'model' => $this->model
            );
        }
        
        private function calculate_cost($tokens, $model) {
            // Pricing per 1K tokens (as of 2024 - adjust as needed)
            $pricing = array(
                'gpt-3.5-turbo' => 0.002,
                'gpt-4' => 0.03,
                'gpt-4-turbo' => 0.01
            );
            
            $rate = $pricing[$model] ?? $pricing['gpt-3.5-turbo'];
            return ($tokens / 1000) * $rate;
        }
        
        public function get_available_models() {
            return array(
                'gpt-3.5-turbo',
                'gpt-4',
                'gpt-4-turbo',
                'gpt-4o'
            );
        }
    }
}
?>