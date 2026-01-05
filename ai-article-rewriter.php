<?php
/**
 * Plugin Name: AI Article Rewriter Enhanced
 * Plugin URI: https://github.com/adesurya/ai-article-rewriter
 * Description: Advanced AI-powered article rewriter with automatic image replacement and publishing
 * Version: 2.0.0
 * Author: Ade Surya
 * License: GPL v2 or later
 * Text Domain: ai-article-rewriter
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent duplicate loading
if (defined('AI_REWRITER_VERSION')) {
    return;
}

// Plugin constants
define('AI_REWRITER_VERSION', '2.0.0');
define('AI_REWRITER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_REWRITER_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main Plugin Class - Enhanced with API Endpoints
 */
class AI_Article_Rewriter {
    
    private $api;
    private $content_parser;
    private $logger;
    private $api_endpoints;
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        if (self::$instance !== null) {
            return;
        }
        
        // Core hooks
        add_action('plugins_loaded', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_rewrite_article', array($this, 'ajax_rewrite_article'));
        add_action('wp_ajax_test_api_connection', array($this, 'ajax_test_api'));
        add_action('wp_ajax_reset_processed_posts', array($this, 'ajax_reset_processed_posts'));
        add_action('wp_ajax_clear_activity_logs', array($this, 'ajax_clear_logs'));
        add_action('wp_ajax_get_recent_activity', array($this, 'ajax_get_recent_activity'));
        add_action('wp_ajax_get_available_models', array($this, 'ajax_get_available_models'));
        add_action('wp_ajax_clear_auto_rewrite_queue', array($this, 'ajax_clear_auto_rewrite_queue'));
        add_action('wp_ajax_process_auto_queue_now', array($this, 'ajax_process_auto_queue_now'));
        add_action('wp_ajax_get_auto_rewrite_status', array($this, 'ajax_get_auto_rewrite_status'));
        add_action('wp_ajax_dismiss_auto_rewrite_notice', array($this, 'ajax_dismiss_auto_rewrite_notice'));
        add_action('wp_ajax_regenerate_api_key', array($this, 'ajax_regenerate_api_key')); // NEW
        
        // Background processing hooks
        add_action('ai_rewriter_process_bulk_batch', 'ai_rewriter_process_bulk_batch');
        add_action('ai_rewriter_cleanup_batch', array($this, 'cleanup_batch'));
        
        // Auto rewrite hooks
        add_action('transition_post_status', array($this, 'handle_post_status_change'), 10, 3);
        add_action('wp_insert_post', array($this, 'handle_new_post'), 10, 2);
        add_action('ai_rewriter_auto_process_queue', array($this, 'process_auto_rewrite_queue'));
        add_action('ai_rewriter_delayed_auto_process', array($this, 'handle_delayed_auto_process'));
        
        // Plugin lifecycle
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_filter('cron_schedules', array($this, 'add_custom_cron_intervals'));
        
        self::$instance = $this;
    }
    
    public function init() {
        $this->load_dependencies();
        
        try {
            if (class_exists('AI_Rewriter_API')) {
                $this->api = new AI_Rewriter_API();
            }
            if (class_exists('AI_Rewriter_Content_Parser')) {
                $this->content_parser = new AI_Rewriter_Content_Parser();
            }
            if (class_exists('AI_Rewriter_Logger')) {
                $this->logger = new AI_Rewriter_Logger();
            }
            if (class_exists('AI_Rewriter_API_Endpoints')) {
                $this->api_endpoints = new AI_Rewriter_API_Endpoints($this);
            }
        } catch (Exception $e) {
            error_log('AI Rewriter init error: ' . $e->getMessage());
        }
        
        $this->setup_database();
        
        load_plugin_textdomain('ai-article-rewriter', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        if (get_option('ai_rewriter_auto_rewrite_enabled', 0)) {
            $this->schedule_auto_processing();
        }
    }
    
    public function load_dependencies() {
        $includes_path = AI_REWRITER_PLUGIN_PATH . 'includes/';
        
        // Enhanced debugging
        error_log('=== AI Rewriter Dependency Loading Debug ===');
        error_log('AI Rewriter: Plugin path = ' . AI_REWRITER_PLUGIN_PATH);
        error_log('AI Rewriter: Includes path = ' . $includes_path);
        error_log('AI Rewriter: Directory exists = ' . (is_dir($includes_path) ? 'YES' : 'NO'));
        
        if (is_dir($includes_path)) {
            $files_in_dir = scandir($includes_path);
            error_log('AI Rewriter: Files in includes directory: ' . implode(', ', $files_in_dir));
        }
        
        $required_files = array(
            'class-ai-api.php',
            'class-content-parser.php', 
            'class-logger.php',
            'class-image-handler.php',
            'class-api-endpoints.php'
        );
        
        foreach ($required_files as $file) {
            $file_path = $includes_path . $file;
            
            error_log("AI Rewriter: Checking file: {$file_path}");
            error_log("AI Rewriter: File exists: " . (file_exists($file_path) ? 'YES' : 'NO'));
            
            if (file_exists($file_path)) {
                $file_size = filesize($file_path);
                error_log("AI Rewriter: File size: {$file_size} bytes");
                
                if ($file_size < 100) {
                    error_log("AI Rewriter: WARNING - File {$file} is suspiciously small ({$file_size} bytes)");
                    
                    // Read and log first few lines of small files
                    $content = file_get_contents($file_path, false, null, 0, 200);
                    error_log("AI Rewriter: File content preview: " . substr($content, 0, 100));
                }
                
                try {
                    // Include with full error reporting
                    $before_classes = get_declared_classes();
                    require_once $file_path;
                    $after_classes = get_declared_classes();
                    $new_classes = array_diff($after_classes, $before_classes);
                    
                    error_log("AI Rewriter: Successfully included {$file}");
                    if (!empty($new_classes)) {
                        error_log("AI Rewriter: New classes from {$file}: " . implode(', ', $new_classes));
                    } else {
                        error_log("AI Rewriter: WARNING - No new classes found after including {$file}");
                    }
                    
                } catch (ParseError $e) {
                    error_log("AI Rewriter: Parse error in {$file}: " . $e->getMessage());
                    error_log("AI Rewriter: Error on line: " . $e->getLine());
                } catch (Exception $e) {
                    error_log("AI Rewriter: Error loading {$file}: " . $e->getMessage());
                } catch (Throwable $e) {
                    error_log("AI Rewriter: Fatal error loading {$file}: " . $e->getMessage());
                }
            } else {
                error_log("AI Rewriter: Missing dependency file: " . $file_path);
                
                // Try to create the missing file
                $this->create_missing_dependency($file_path, $file);
                
                // Try to include it after creation
                if (file_exists($file_path)) {
                    try {
                        require_once $file_path;
                        error_log("AI Rewriter: Successfully loaded created placeholder: {$file}");
                    } catch (Exception $e) {
                        error_log("AI Rewriter: Failed to load created placeholder {$file}: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Verify critical classes are loaded with detailed logging
        $critical_classes = array(
            'AI_Rewriter_API',
            'AI_Rewriter_Content_Parser', 
            'AI_Rewriter_Logger',
            'AI_Rewriter_API_Endpoints'
        );
        
        error_log('=== Class Verification ===');
        foreach ($critical_classes as $class) {
            $exists = class_exists($class);
            error_log("AI Rewriter: Class {$class} exists: " . ($exists ? 'YES' : 'NO'));
            
            if ($exists) {
                $methods = get_class_methods($class);
                error_log("AI Rewriter: Class {$class} has " . count($methods) . " methods");
                
                // Log a few method names for verification
                if (!empty($methods)) {
                    $sample_methods = array_slice($methods, 0, 3);
                    error_log("AI Rewriter: Sample methods in {$class}: " . implode(', ', $sample_methods));
                }
            } else {
                // Try to figure out why the class doesn't exist
                error_log("AI Rewriter: Class {$class} not found - checking possible causes...");
                
                // Check if any files contain this class name
                foreach ($required_files as $file) {
                    $file_path = $includes_path . $file;
                    if (file_exists($file_path)) {
                        $content = file_get_contents($file_path);
                        if (strpos($content, $class) !== false) {
                            error_log("AI Rewriter: Class {$class} definition found in {$file}");
                            
                            // Check for syntax errors by trying to get file tokens
                            $tokens = token_get_all($content);
                            $class_found = false;
                            foreach ($tokens as $token) {
                                if (is_array($token) && $token[0] === T_CLASS) {
                                    $class_found = true;
                                    break;
                                }
                            }
                            
                            if (!$class_found) {
                                error_log("AI Rewriter: No valid class token found in {$file} - possible syntax error");
                            }
                        }
                    }
                }
            }
        }
        
        error_log('=== End Debug ===');
    }

    /**
     * Create minimal placeholder files for missing dependencies
     */
    private function create_missing_dependency($file_path, $filename) {
        $dir = dirname($file_path);
        
        // Ensure directory exists
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        
        $placeholder_content = '';
        
        switch ($filename) {
            case 'class-ai-api.php':
                $placeholder_content = '<?php
    if (!defined("ABSPATH")) exit;

    class AI_Rewriter_API {
        public function __construct($api_key = null) {
            error_log("AI Rewriter: AI_API placeholder class loaded");
        }
        
        public function set_config($config) {
            // Placeholder method
        }
        
        public function test_connection() {
            return array("success" => false, "message" => "OpenAI API class not properly implemented");
        }
        
        public function rewrite_content($prompt) {
            throw new Exception("OpenAI API not implemented - this is a placeholder");
        }
        
        public function get_available_models() {
            return array("gpt-3.5-turbo", "gpt-4");
        }
    }';
                break;
                
            case 'class-content-parser.php':
                $placeholder_content = '<?php
    if (!defined("ABSPATH")) exit;

    class AI_Rewriter_Content_Parser {
        public function __construct() {
            error_log("AI Rewriter: Content Parser placeholder class loaded");
        }
        
        public function set_language($lang) {}
        public function set_writing_style($style) {}
        
        public function generate_prompt($title, $content, $custom_prompt = "", $instructions = "") {
            return "Rewrite this article: " . $title . "\n\n" . $content;
        }
        
        public function parse_rewritten_content($content) {
            return array(
                "title" => "Rewritten: " . substr(strip_tags($content), 0, 50),
                "content" => $content
            );
        }
        
        public function format_for_wordpress($content) {
            return $content;
        }
        
        public function extract_keywords($title, $content, $count = 5) {
            return array("keyword1", "keyword2");
        }
    }';
                break;
                
            case 'class-logger.php':
                $placeholder_content = '<?php
    if (!defined("ABSPATH")) exit;

    class AI_Rewriter_Logger {
        public function __construct() {
            error_log("AI Rewriter: Logger placeholder class loaded");
        }
        
        public function log_rewrite($post_id, $status, $message, $original_title = "", $new_title = "", $cost = 0) {
            error_log("AI Rewriter Log: Post {$post_id} - {$status} - {$message}");
        }
        
        public function log_activity($message, $type = "info") {
            error_log("AI Rewriter Activity: {$message}");
        }
        
        public function get_formatted_logs($limit = 10) {
            return array();
        }
        
        public function clear_all_logs() {
            return true;
        }
    }';
                break;
                
            case 'class-image-handler.php':
                $placeholder_content = '<?php
    if (!defined("ABSPATH")) exit;

    class AI_Rewriter_Image_Handler {
        public function __construct() {
            error_log("AI Rewriter: Image Handler placeholder class loaded");
        }
        
        public function search_and_upload_image($keyword, $post_id) {
            // Placeholder - no image processing
            return false;
        }
    }';
                break;
        }
        
        if (!empty($placeholder_content)) {
            file_put_contents($file_path, $placeholder_content);
            error_log("AI Rewriter: Created placeholder file: " . $filename);
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('AI Article Rewriter', 'ai-article-rewriter'),
            __('AI Rewriter', 'ai-article-rewriter'),
            'manage_options',
            'ai-article-rewriter',
            array($this, 'admin_page'),
            'dashicons-edit',
            30
        );
        
        add_submenu_page(
            'ai-article-rewriter',
            __('Settings', 'ai-article-rewriter'),
            __('Settings', 'ai-article-rewriter'),
            'manage_options',
            'ai-article-rewriter-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ai-article-rewriter') === false) {
            return;
        }
        
        $js_file = AI_REWRITER_PLUGIN_URL . 'assets/admin.js';
        $css_file = AI_REWRITER_PLUGIN_URL . 'assets/admin.css';
        
        if (file_exists(AI_REWRITER_PLUGIN_PATH . 'assets/admin.js')) {
            wp_enqueue_script('ai-rewriter-admin', $js_file, array('jquery'), AI_REWRITER_VERSION, true);
        }
        
        if (file_exists(AI_REWRITER_PLUGIN_PATH . 'assets/admin.css')) {
            wp_enqueue_style('ai-rewriter-admin', $css_file, array(), AI_REWRITER_VERSION);
        }
        
        wp_localize_script('ai-rewriter-admin', 'aiRewriter', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_rewriter_nonce'),
            'loading_text' => __('Processing...', 'ai-article-rewriter'),
            'retry_text' => __('Retry', 'ai-article-rewriter'),
            'publish_text' => __('Published!', 'ai-article-rewriter')
        ));
    }
    
    // Auto Rewrite Functions
    public function handle_post_status_change($new_status, $old_status, $post) {
        if ($new_status !== 'draft' || $post->post_type !== 'post' || !get_option('ai_rewriter_auto_rewrite_enabled', 0)) {
            return;
        }
        
        if (get_post_meta($post->ID, '_ai_rewriter_processed', true)) {
            return;
        }
        
        $word_count = str_word_count(strip_tags($post->post_content));
        $min_words = get_option('ai_rewriter_auto_min_words', 50);
        
        if ($word_count >= $min_words) {
            $this->add_to_auto_queue($post->ID);
        }
    }
    
    public function handle_new_post($post_id, $post) {
        if ($post->post_status !== 'draft' || $post->post_type !== 'post' || !get_option('ai_rewriter_auto_rewrite_enabled', 0)) {
            return;
        }
        
        $delay = get_option('ai_rewriter_auto_delay_minutes', 5);
        
        if ($delay > 0) {
            wp_schedule_single_event(time() + ($delay * 60), 'ai_rewriter_delayed_auto_process', array($post_id));
        } else {
            $this->add_to_auto_queue($post_id);
        }
    }
    
    private function add_to_auto_queue($post_id) {
        global $wpdb;
        
        $table_queue = $wpdb->prefix . 'ai_rewriter_queue';
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_queue} WHERE post_id = %d AND status IN ('pending', 'processing')",
            $post_id
        ));
        
        if (!$existing) {
            $wpdb->insert(
                $table_queue,
                array(
                    'post_id' => $post_id,
                    'status' => 'pending',
                    'scheduled_time' => current_time('mysql'),
                    'retry_count' => 0
                ),
                array('%d', '%s', '%s', '%d')
            );
        }
    }
    
    public function process_auto_rewrite_queue() {
        global $wpdb;
        
        if (!get_option('ai_rewriter_auto_rewrite_enabled', 0)) {
            return;
        }
        
        $table_queue = $wpdb->prefix . 'ai_rewriter_queue';
        $batch_size = get_option('ai_rewriter_auto_batch_size', 1);
        
        $queue_items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_queue} WHERE status = 'pending' AND scheduled_time <= %s ORDER BY scheduled_time ASC LIMIT %d",
            current_time('mysql'),
            $batch_size
        ));
        
        foreach ($queue_items as $item) {
            $this->process_auto_queue_item($item);
        }
        
        $this->schedule_next_auto_processing();
    }
    
    private function process_auto_queue_item($item) {
        global $wpdb;
        
        try {
            $post = get_post($item->post_id);
            if (!$post || $post->post_status !== 'draft') {
                throw new Exception('Post not found or not a draft');
            }
            
            $this->configure_api();
            
            $prompt = $this->content_parser->generate_prompt(
                $post->post_title,
                $post->post_content,
                get_option('ai_rewriter_custom_prompt', ''),
                get_option('ai_rewriter_prompt_instructions', '')
            );
            
            $result = $this->api->rewrite_content($prompt);
            $parsed = $this->content_parser->parse_rewritten_content($result['content']);
            
            $auto_publish = get_option('ai_rewriter_auto_publish_after_rewrite', 0);
            
            wp_update_post(array(
                'ID' => $item->post_id,
                'post_title' => $parsed['title'],
                'post_content' => $this->content_parser->format_for_wordpress($parsed['content']),
                'post_status' => $auto_publish ? 'publish' : 'draft'
            ));
            
            if (get_option('ai_rewriter_auto_replace_images', 0)) {
                $this->handle_images($item->post_id, $parsed['title'], $parsed['content']);
            }
            
            update_post_meta($item->post_id, '_ai_rewriter_processed', current_time('mysql'));
            update_post_meta($item->post_id, '_ai_rewriter_auto_processed', 1);
            
            $wpdb->update(
                $wpdb->prefix . 'ai_rewriter_queue',
                array('status' => 'completed'),
                array('id' => $item->id)
            );
            
        } catch (Exception $e) {
            $retry_count = intval($item->retry_count) + 1;
            $max_retries = get_option('ai_rewriter_auto_max_retries', 3);
            
            if ($retry_count <= $max_retries) {
                $wpdb->update(
                    $wpdb->prefix . 'ai_rewriter_queue',
                    array(
                        'status' => 'pending',
                        'retry_count' => $retry_count,
                        'scheduled_time' => date('Y-m-d H:i:s', time() + 1800),
                        'error_message' => $e->getMessage()
                    ),
                    array('id' => $item->id)
                );
            } else {
                $wpdb->update(
                    $wpdb->prefix . 'ai_rewriter_queue',
                    array('status' => 'failed', 'error_message' => $e->getMessage()),
                    array('id' => $item->id)
                );
            }
        }
    }
    
    private function schedule_auto_processing() {
        if (!wp_next_scheduled('ai_rewriter_auto_process_queue')) {
            $interval = get_option('ai_rewriter_auto_check_interval', 15);
            wp_schedule_event(time(), 'ai_rewriter_' . $interval . '_minutes', 'ai_rewriter_auto_process_queue');
        }
    }
    
    private function schedule_next_auto_processing() {
        $interval = get_option('ai_rewriter_auto_check_interval', 15);
        wp_schedule_single_event(time() + ($interval * 60), 'ai_rewriter_auto_process_queue');
    }
    
    public function handle_delayed_auto_process($post_id) {
        if (get_option('ai_rewriter_auto_rewrite_enabled', 0)) {
            $this->add_to_auto_queue($post_id);
        }
    }
    
    // API Methods for Bulk Processing
    public function process_single_article_api($post_id, $settings = array()) {
        try {
            $post = get_post($post_id);
            
            if (!$post || $post->post_status !== 'draft') {
                return array(
                    'success' => false,
                    'message' => 'Post not found or not a draft',
                    'post_id' => $post_id
                );
            }
            
            // Check if already processed (if exclude_processed is true)
            if (!empty($settings['exclude_processed']) && get_post_meta($post_id, '_ai_rewriter_processed', true)) {
                return array(
                    'success' => false,
                    'message' => 'Article already processed',
                    'post_id' => $post_id,
                    'title' => $post->post_title
                );
            }
            
            // Check minimum words
            $word_count = str_word_count(strip_tags($post->post_content));
            $min_words = isset($settings['min_words']) ? $settings['min_words'] : 50;
            
            if ($word_count < $min_words) {
                return array(
                    'success' => false,
                    'message' => sprintf('Article too short (%d words, minimum %d)', $word_count, $min_words),
                    'post_id' => $post_id,
                    'title' => $post->post_title
                );
            }
            
            // Configure API and content parser
            $this->configure_api();
            
            // Generate prompt
            $prompt = $this->content_parser->generate_prompt(
                $post->post_title,
                $post->post_content,
                get_option('ai_rewriter_custom_prompt', ''),
                get_option('ai_rewriter_prompt_instructions', '')
            );
            
            // Call AI API
            $result = $this->api->rewrite_content($prompt);
            $parsed = $this->content_parser->parse_rewritten_content($result['content']);
            
            // Determine final status
            $auto_publish = isset($settings['auto_publish']) ? $settings['auto_publish'] : true;
            $final_status = $auto_publish ? 'publish' : 'draft';
            
            // Update post
            $update_result = wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $parsed['title'],
                'post_content' => $this->content_parser->format_for_wordpress($parsed['content']),
                'post_status' => $final_status
            ));
            
            if (is_wp_error($update_result)) {
                return array(
                    'success' => false,
                    'message' => 'Failed to update post: ' . $update_result->get_error_message(),
                    'post_id' => $post_id,
                    'title' => $post->post_title
                );
            }
            
            // Process images if enabled
            if (!empty($settings['process_images']) && get_option('ai_rewriter_auto_replace_images', 0)) {
                try {
                    $this->handle_images($post_id, $parsed['title'], $parsed['content']);
                } catch (Exception $e) {
                    // Log image processing error but don't fail the entire operation
                    error_log('Image processing failed for post ' . $post_id . ': ' . $e->getMessage());
                }
            }
            
            // Mark as processed
            update_post_meta($post_id, '_ai_rewriter_processed', current_time('mysql'));
            update_post_meta($post_id, '_ai_rewriter_api_processed', 1);
            update_post_meta($post_id, '_ai_rewriter_original_title', $post->post_title);
            update_post_meta($post_id, '_ai_rewriter_processing_cost', isset($result['cost']) ? $result['cost'] : 0);
            
            // Log activity if logger is available
            if ($this->logger) {
                $this->logger->log_rewrite(
                    $post_id,
                    'success',
                    sprintf('Article rewritten via API: %s -> %s', $post->post_title, $parsed['title']),
                    $post->post_title,
                    $parsed['title'],
                    isset($result['cost']) ? $result['cost'] : 0
                );
            }
            
            return array(
                'success' => true,
                'message' => sprintf('Article successfully rewritten and %s', $auto_publish ? 'published' : 'saved as draft'),
                'post_id' => $post_id,
                'title' => $parsed['title'],
                'original_title' => $post->post_title,
                'status' => $final_status,
                'word_count' => str_word_count(strip_tags($parsed['content'])),
                'cost' => isset($result['cost']) ? $result['cost'] : 0,
                'tokens_used' => isset($result['tokens_used']) ? $result['tokens_used'] : 0
            );
            
        } catch (Exception $e) {
            // Log error if logger is available
            if ($this->logger) {
                $this->logger->log_rewrite(
                    $post_id,
                    'error',
                    'API rewrite failed: ' . $e->getMessage(),
                    isset($post->post_title) ? $post->post_title : 'Unknown title',
                    '',
                    0
                );
            }
            
            return array(
                'success' => false,
                'message' => 'Rewrite failed: ' . $e->getMessage(),
                'post_id' => $post_id,
                'title' => isset($post->post_title) ? $post->post_title : 'Unknown title',
                'error_code' => $e->getCode(),
                'error_details' => $e->getMessage()
            );
        }
    }
    
    public function get_api_endpoint_key() {
        $key = get_option('ai_rewriter_api_endpoint_key', '');
        
        if (empty($key)) {
            // Generate new API key if not exists
            $key = 'air_' . wp_generate_password(32, false, false);
            update_option('ai_rewriter_api_endpoint_key', $key);
        }
        
        return $key;
    }
    
    public function regenerate_api_endpoint_key() {
        $new_key = 'air_' . wp_generate_password(32, false, false);
        update_option('ai_rewriter_api_endpoint_key', $new_key);
        return $new_key;
    }
    
    public function cleanup_batch($batch_id) {
        delete_option('ai_rewriter_batch_' . $batch_id);
        
        // Log that batch was cleaned up
        if ($this->logger) {
            $this->logger->log_activity("Batch {$batch_id} data cleaned up", 'info');
        }
    }
    
    // AJAX Handlers
    public function ajax_rewrite_article() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post || $post->post_status !== 'draft') {
            wp_send_json_error('Invalid post or post is not a draft');
            return;
        }
        
        try {
            $this->configure_api();
            
            $prompt = $this->content_parser->generate_prompt(
                $post->post_title,
                $post->post_content,
                get_option('ai_rewriter_custom_prompt', ''),
                get_option('ai_rewriter_prompt_instructions', '')
            );
            
            $result = $this->api->rewrite_content($prompt);
            $parsed = $this->content_parser->parse_rewritten_content($result['content']);
            
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $parsed['title'],
                'post_content' => $this->content_parser->format_for_wordpress($parsed['content']),
                'post_status' => 'publish'
            ));
            
            if (get_option('ai_rewriter_auto_replace_images', 0)) {
                $this->handle_images($post_id, $parsed['title'], $parsed['content']);
            }
            
            update_post_meta($post_id, '_ai_rewriter_processed', current_time('mysql'));
            
            wp_send_json_success(array(
                'title' => $parsed['title'],
                'content' => wp_trim_words($parsed['content'], 20),
                'status' => 'published'
            ));
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    private function handle_images($post_id, $title, $content) {
        if (!class_exists('AI_Rewriter_Image_Handler') || !$this->content_parser) {
            return array('featured_image_set' => false, 'images_added' => 0);
        }
        
        try {
            $image_handler = new AI_Rewriter_Image_Handler();
            $keywords = $this->content_parser->extract_keywords($title, $content, 5);
            
            if (!empty($keywords)) {
                $featured_image_id = $image_handler->search_and_upload_image($keywords[0], $post_id);
                if ($featured_image_id) {
                    set_post_thumbnail($post_id, $featured_image_id);
                }
            }
        } catch (Exception $e) {
            error_log('Image processing error: ' . $e->getMessage());
        }
    }
    
    private function configure_api() {
        $api_key = get_option('ai_rewriter_api_key', '');
        if (empty($api_key)) {
            throw new Exception('API key not configured');
        }
        
        // Ensure API class is loaded and initialized
        if (!$this->api || !is_object($this->api)) {
            if (!class_exists('AI_Rewriter_API')) {
                $this->load_dependencies();
                
                if (!class_exists('AI_Rewriter_API')) {
                    throw new Exception('API class not available');
                }
            }
            
            $this->api = new AI_Rewriter_API();
        }
        
        $this->api->set_config(array(
            'api_key' => $api_key,
            'model' => get_option('ai_rewriter_model', 'gpt-3.5-turbo'),
            'temperature' => get_option('ai_rewriter_temperature', 0.7),
            'max_tokens' => get_option('ai_rewriter_max_tokens', 2000)
        ));
        
        // Ensure content parser is initialized
        if (!$this->content_parser || !is_object($this->content_parser)) {
            if (!class_exists('AI_Rewriter_Content_Parser')) {
                $this->load_dependencies();
                
                if (!class_exists('AI_Rewriter_Content_Parser')) {
                    throw new Exception('Content parser class not available');
                }
            }
            
            $this->content_parser = new AI_Rewriter_Content_Parser();
        }
        
        if ($this->content_parser) {
            $this->content_parser->set_language(get_option('ai_rewriter_language', 'Indonesian'));
            $this->content_parser->set_writing_style(get_option('ai_rewriter_writing_style', 'professional'));
        }
    }
    
    public function ajax_test_api() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
            return;
        }
        
        // Check if class exists
        if (!class_exists('AI_Rewriter_API')) {
            $this->load_dependencies();
            
            if (!class_exists('AI_Rewriter_API')) {
                wp_send_json_error('API class not available. Please check plugin installation.');
                return;
            }
        }
        
        try {
            $test_api = new AI_Rewriter_API($api_key);
            $result = $test_api->test_connection();
            
            if ($result['success']) {
                wp_send_json_success($result['message']);
            } else {
                wp_send_json_error($result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error('Connection failed: ' . $e->getMessage());
        }
    }
    
    public function ajax_get_available_models() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
            return;
        }
        
        // Check if class exists
        if (!class_exists('AI_Rewriter_API')) {
            $this->load_dependencies();
            
            if (!class_exists('AI_Rewriter_API')) {
                wp_send_json_error('API class not available. Please check plugin installation.');
                return;
            }
        }
        
        try {
            $api = new AI_Rewriter_API($api_key);
            $models = $api->get_available_models();
            
            if (!empty($models)) {
                wp_send_json_success(array('models' => $models));
            } else {
                wp_send_json_error('No models found');
            }
        } catch (Exception $e) {
            wp_send_json_error('Failed to get models: ' . $e->getMessage());
        }
    }
    
    public function ajax_regenerate_api_key() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $new_key = $this->regenerate_api_endpoint_key();
        
        wp_send_json_success(array(
            'new_key' => $new_key,
            'message' => 'API key regenerated successfully'
        ));
    }
    
    public function ajax_clear_auto_rewrite_queue() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if ($this->clear_auto_queue()) {
            wp_send_json_success('Queue cleared successfully');
        } else {
            wp_send_json_error('Failed to clear queue');
        }
    }
    
    public function ajax_process_auto_queue_now() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        wp_schedule_single_event(time() + 5, 'ai_rewriter_auto_process_queue');
        wp_send_json_success('Processing started');
    }
    
    public function ajax_get_auto_rewrite_status() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        wp_send_json_success(array(
            'enabled' => get_option('ai_rewriter_auto_rewrite_enabled', 0),
            'queue_status' => $this->get_auto_queue_status()
        ));
    }
    
    public function ajax_dismiss_auto_rewrite_notice() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        update_option('ai_rewriter_auto_notice_dismissed', 1);
        wp_send_json_success();
    }
    
    public function ajax_reset_processed_posts() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $wpdb->delete($wpdb->postmeta, array('meta_key' => '_ai_rewriter_processed'));
        wp_send_json_success('Processing history reset');
    }
    
    public function ajax_clear_logs() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if ($this->logger && $this->logger->clear_all_logs()) {
            wp_send_json_success('Logs cleared');
        } else {
            wp_send_json_error('Failed to clear logs');
        }
    }
    
    public function ajax_get_recent_activity() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $html = '<p>📝 Activity will be shown here...</p>';
        
        if ($this->logger) {
            $logs = $this->logger->get_formatted_logs(10);
            $html = '';
            
            foreach ($logs as $log) {
                $html .= '<div>' . $log['icon'] . ' ' . esc_html($log['message']) . ' <em>(' . $log['time'] . ')</em></div>';
            }
        }
        
        wp_send_json_success($html);
    }
    
    // Admin Pages
    public function admin_page() {
        $draft_posts = get_posts(array(
            'post_status' => 'draft',
            'post_type' => 'post',
            'numberposts' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $template_file = AI_REWRITER_PLUGIN_PATH . 'templates/admin-page.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            // Basic fallback interface
            echo '<div class="wrap">';
            echo '<h1>AI Article Rewriter</h1>';
            
            if (!empty($draft_posts)) {
                echo '<h2>Draft Articles (' . count($draft_posts) . ')</h2>';
                echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">';
                
                foreach ($draft_posts as $post) {
                    $processed = get_post_meta($post->ID, '_ai_rewriter_processed', true);
                    $word_count = str_word_count(strip_tags($post->post_content));
                    
                    echo '<div style="border: 1px solid #ccc; padding: 15px; border-radius: 8px;">';
                    echo '<h3>' . esc_html($post->post_title) . '</h3>';
                    echo '<p>Words: ' . $word_count . ' | Date: ' . get_the_date('M j, Y', $post->ID) . '</p>';
                    
                    if ($processed) {
                        echo '<span style="color: green;">✅ Processed</span>';
                    } else {
                        echo '<button class="button button-primary rewrite-btn" data-post-id="' . $post->ID . '">🤖 Rewrite & Publish</button>';
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>';
            } else {
                echo '<p>No draft articles found.</p>';
            }
            
            echo '</div>';
            
            // Basic JavaScript
            echo '<script>
            jQuery(document).ready(function($) {
                $(".rewrite-btn").click(function() {
                    var postId = $(this).data("post-id");
                    var $btn = $(this);
                    
                    $btn.text("Processing...").prop("disabled", true);
                    
                    $.post(ajaxurl, {
                        action: "rewrite_article",
                        post_id: postId,
                        nonce: "' . wp_create_nonce('ai_rewriter_nonce') . '"
                    }, function(response) {
                        if (response.success) {
                            $btn.parent().html("<span style=\"color: green;\">✅ Published Successfully!</span>");
                        } else {
                            alert("Error: " + response.data);
                            $btn.text("🤖 Rewrite & Publish").prop("disabled", false);
                        }
                    });
                });
            });
            </script>';
        }
    }
    
    public function settings_page() {
        if ($_POST && check_admin_referer('ai_rewriter_settings', 'ai_rewriter_nonce')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        $template_file = AI_REWRITER_PLUGIN_PATH . 'templates/settings-page.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            // Basic fallback settings form
            echo '<div class="wrap">';
            echo '<h1>AI Article Rewriter Settings</h1>';
            
            echo '<form method="post" action="">';
            wp_nonce_field('ai_rewriter_settings', 'ai_rewriter_nonce');
            
            echo '<table class="form-table">';
            echo '<tr><th>OpenAI API Key</th><td><input type="text" name="ai_rewriter_api_key" value="' . esc_attr(get_option('ai_rewriter_api_key', '')) . '" class="regular-text" /></td></tr>';
            echo '<tr><th>Model</th><td><select name="ai_rewriter_model">';
            $models = array('gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo');
            foreach ($models as $model) {
                echo '<option value="' . $model . '"' . selected(get_option('ai_rewriter_model', 'gpt-3.5-turbo'), $model, false) . '>' . $model . '</option>';
            }
            echo '</select></td></tr>';
            echo '<tr><th>Language</th><td><select name="ai_rewriter_language">';
            $languages = array('Indonesian', 'English', 'Spanish', 'French');
            foreach ($languages as $lang) {
                echo '<option value="' . $lang . '"' . selected(get_option('ai_rewriter_language', 'Indonesian'), $lang, false) . '>' . $lang . '</option>';
            }
            echo '</select></td></tr>';
            echo '<tr><th>Auto Rewrite</th><td><input type="checkbox" name="ai_rewriter_auto_rewrite_enabled" value="1" ' . checked(get_option('ai_rewriter_auto_rewrite_enabled', 0), 1, false) . ' /> Enable automatic rewriting</td></tr>';
            echo '<tr><th>Auto Replace Images</th><td><input type="checkbox" name="ai_rewriter_auto_replace_images" value="1" ' . checked(get_option('ai_rewriter_auto_replace_images', 0), 1, false) . ' /> Auto replace images</td></tr>';
            echo '<tr><th>Auto Publish</th><td><input type="checkbox" name="ai_rewriter_auto_publish_after_rewrite" value="1" ' . checked(get_option('ai_rewriter_auto_publish_after_rewrite', 0), 1, false) . ' /> Auto publish after rewrite</td></tr>';
            echo '</table>';
            
            submit_button();
            echo '</form>';
            echo '</div>';
        }
    }
    
    private function save_settings() {
        $settings = array(
            'ai_rewriter_api_key' => sanitize_text_field($_POST['ai_rewriter_api_key'] ?? ''),
            'ai_rewriter_model' => sanitize_text_field($_POST['ai_rewriter_model'] ?? 'gpt-3.5-turbo'),
            'ai_rewriter_language' => sanitize_text_field($_POST['ai_rewriter_language'] ?? 'Indonesian'),
            'ai_rewriter_temperature' => floatval($_POST['ai_rewriter_temperature'] ?? 0.7),
            'ai_rewriter_max_tokens' => intval($_POST['ai_rewriter_max_tokens'] ?? 2000),
            'ai_rewriter_writing_style' => sanitize_text_field($_POST['ai_rewriter_writing_style'] ?? 'professional'),
            'ai_rewriter_use_custom_prompt' => isset($_POST['ai_rewriter_use_custom_prompt']) ? 1 : 0,
            'ai_rewriter_custom_prompt' => sanitize_textarea_field($_POST['ai_rewriter_custom_prompt'] ?? ''),
            'ai_rewriter_prompt_instructions' => sanitize_textarea_field($_POST['ai_rewriter_prompt_instructions'] ?? ''),
            'ai_rewriter_auto_replace_images' => isset($_POST['ai_rewriter_auto_replace_images']) ? 1 : 0,
            'ai_rewriter_image_source' => sanitize_text_field($_POST['ai_rewriter_image_source'] ?? 'google'),
            'ai_rewriter_max_images' => intval($_POST['ai_rewriter_max_images'] ?? 2),
            'ai_rewriter_google_api_key' => sanitize_text_field($_POST['ai_rewriter_google_api_key'] ?? ''),
            'ai_rewriter_google_search_engine_id' => sanitize_text_field($_POST['ai_rewriter_google_search_engine_id'] ?? ''),
            'ai_rewriter_pexels_api_key' => sanitize_text_field($_POST['ai_rewriter_pexels_api_key'] ?? ''),
            'ai_rewriter_auto_rewrite_enabled' => isset($_POST['ai_rewriter_auto_rewrite_enabled']) ? 1 : 0,
            'ai_rewriter_auto_publish_after_rewrite' => isset($_POST['ai_rewriter_auto_publish_after_rewrite']) ? 1 : 0,
            'ai_rewriter_auto_process_immediately' => isset($_POST['ai_rewriter_auto_process_immediately']) ? 1 : 0,
            'ai_rewriter_auto_min_words' => intval($_POST['ai_rewriter_auto_min_words'] ?? 50) ?: 50,
            'ai_rewriter_auto_delay_minutes' => intval($_POST['ai_rewriter_auto_delay_minutes'] ?? 5),
            'ai_rewriter_auto_batch_size' => intval($_POST['ai_rewriter_auto_batch_size'] ?? 1),
            'ai_rewriter_auto_check_interval' => intval($_POST['ai_rewriter_auto_check_interval'] ?? 15),
            'ai_rewriter_auto_max_retries' => intval($_POST['ai_rewriter_auto_max_retries'] ?? 3),
            'ai_rewriter_auto_retry_delay' => intval($_POST['ai_rewriter_auto_retry_delay'] ?? 30),
            'ai_rewriter_auto_processing_delay' => intval($_POST['ai_rewriter_auto_processing_delay'] ?? 2),
            // NEW API SETTINGS
            'ai_rewriter_api_enabled' => isset($_POST['ai_rewriter_api_enabled']) ? 1 : 0,
            'ai_rewriter_api_require_auth' => isset($_POST['ai_rewriter_api_require_auth']) ? 1 : 0,
            'ai_rewriter_api_log_requests' => isset($_POST['ai_rewriter_api_log_requests']) ? 1 : 0,
            'ai_rewriter_api_rate_limit_hourly' => intval($_POST['ai_rewriter_api_rate_limit_hourly'] ?? 10),
            'ai_rewriter_api_max_concurrent_batches' => intval($_POST['ai_rewriter_api_max_concurrent_batches'] ?? 2)
        );
        
        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }
        
        // Handle auto rewrite scheduling
        if ($settings['ai_rewriter_auto_rewrite_enabled']) {
            wp_clear_scheduled_hook('ai_rewriter_auto_process_queue');
            $this->schedule_auto_processing();
        } else {
            wp_clear_scheduled_hook('ai_rewriter_auto_process_queue');
        }
    }
    
    // Dashboard and Notices
    public function add_dashboard_widget() {
        if (get_option('ai_rewriter_auto_rewrite_enabled', 0) || get_option('ai_rewriter_api_enabled', 0) || current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'ai_rewriter_status',
                '🤖 AI Article Rewriter Status',
                array($this, 'dashboard_widget_content')
            );
        }
    }
    
    public function dashboard_widget_content() {
        $auto_enabled = get_option('ai_rewriter_auto_rewrite_enabled', 0);
        $api_enabled = get_option('ai_rewriter_api_enabled', 0);
        $queue_status = $this->get_auto_queue_status();
        
        echo '<div style="font-size: 13px;">';
        
        // Auto rewrite status
        if ($auto_enabled) {
            echo '<div style="background: #e8f5e8; padding: 10px; border-radius: 4px; margin-bottom: 10px;">';
            echo '<strong style="color: #28a745;">✅ Auto Rewrite Active</strong>';
            echo '</div>';
        } else {
            echo '<div style="background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 10px;">';
            echo '<strong style="color: #dc3545;">❌ Auto Rewrite Disabled</strong>';
            echo '</div>';
        }
        
        // API status
        if ($api_enabled) {
            echo '<div style="background: #e3f2fd; padding: 10px; border-radius: 4px; margin-bottom: 10px;">';
            echo '<strong style="color: #1976d2;">🔗 API Endpoints Active</strong>';
            echo '</div>';
        } else {
            echo '<div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin-bottom: 10px;">';
            echo '<strong style="color: #856404;">🔗 API Endpoints Disabled</strong>';
            echo '</div>';
        }
        
        // Queue status
        if ($queue_status && $queue_status->total_items > 0) {
            echo '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 10px;">';
            echo '<strong>📋 Queue:</strong> ' . $queue_status->pending_items . ' pending, ' . $queue_status->completed_items . ' completed';
            echo '</div>';
        }
        
        // Recent API batches
        $recent_batches = $this->get_recent_api_batches(3);
        if (!empty($recent_batches)) {
            echo '<div style="background: #f1f3f4; padding: 10px; border-radius: 4px; margin-bottom: 10px;">';
            echo '<strong>🚀 Recent API Batches:</strong><br>';
            foreach ($recent_batches as $batch) {
                $status_icon = $batch['status'] === 'completed' ? '✅' : 
                              ($batch['status'] === 'processing' ? '⏳' : 
                              ($batch['status'] === 'failed' ? '❌' : '⏸️'));
                echo '<small>' . $status_icon . ' ' . substr($batch['batch_id'], -8) . 
                     ' (' . $batch['progress_percentage'] . '% - ' . $batch['status'] . ')</small><br>';
            }
            echo '</div>';
        }
        
        echo '<div style="text-align: center; margin-top: 10px;">';
        echo '<a href="' . admin_url('admin.php?page=ai-article-rewriter') . '" class="button button-primary button-small" style="margin-right: 5px;">Dashboard</a>';
        echo '<a href="' . admin_url('admin.php?page=ai-article-rewriter-settings') . '" class="button button-secondary button-small">Settings</a>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Get recent API batches for dashboard
     */
    private function get_recent_api_batches($limit = 5) {
        $batches = array();
        $options = wp_load_alloptions();
        
        foreach ($options as $key => $value) {
            if (strpos($key, 'ai_rewriter_batch_') === 0) {
                $batch_data = maybe_unserialize($value);
                if (is_array($batch_data) && isset($batch_data['batch_id'])) {
                    $batches[] = array(
                        'batch_id' => $batch_data['batch_id'],
                        'status' => $batch_data['status'],
                        'progress_percentage' => $batch_data['total_articles'] > 0 ? 
                            round(($batch_data['processed_count'] / $batch_data['total_articles']) * 100, 1) : 0,
                        'start_time' => $batch_data['start_time']
                    );
                }
            }
        }
        
        // Sort by start time (newest first)
        usort($batches, function($a, $b) {
            return strtotime($b['start_time']) - strtotime($a['start_time']);
        });
        
        return array_slice($batches, 0, $limit);
    }
    
    public function show_admin_notices() {
        $screen = get_current_screen();
        
        if (!$screen || !in_array($screen->base, array('dashboard', 'edit', 'post', 'toplevel_page_ai-article-rewriter'))) {
            return;
        }
        
        $auto_enabled = get_option('ai_rewriter_auto_rewrite_enabled', 0);
        $api_enabled = get_option('ai_rewriter_api_enabled', 0);
        $openai_api_key = get_option('ai_rewriter_api_key', '');
        
        // OpenAI API key warning
        if (($auto_enabled || $api_enabled) && empty($openai_api_key)) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>🤖 AI Rewriter:</strong> Auto rewrite or API endpoints are enabled but no OpenAI API key configured. ';
            echo '<a href="' . admin_url('admin.php?page=ai-article-rewriter-settings') . '">Configure API key</a></p>';
            echo '</div>';
        }
        
        // API endpoint security warning
        if ($api_enabled && !get_option('ai_rewriter_api_require_auth', 1)) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>🔗 AI Rewriter API:</strong> API endpoints are enabled without authentication. This could be a security risk. ';
            echo '<a href="' . admin_url('admin.php?page=ai-article-rewriter-settings#api_security_row') . '">Enable authentication</a></p>';
            echo '</div>';
        }
        
        // Show API endpoint info on main plugin page
        if ($screen->base === 'toplevel_page_ai-article-rewriter' && $api_enabled) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>🚀 API Ready:</strong> You can now trigger bulk rewrites via REST API. ';
            echo '<a href="' . admin_url('admin.php?page=ai-article-rewriter-settings#api_endpoints_info') . '">View API documentation</a></p>';
            echo '</div>';
        }
    }
    
    // Utility Functions
    public function add_custom_cron_intervals($schedules) {
        $schedules['ai_rewriter_15_minutes'] = array(
            'interval' => 15 * 60,
            'display' => __('Every 15 Minutes', 'ai-article-rewriter')
        );
        $schedules['ai_rewriter_30_minutes'] = array(
            'interval' => 30 * 60,
            'display' => __('Every 30 Minutes', 'ai-article-rewriter')
        );
        
        return $schedules;
    }
    
    public function get_auto_queue_status() {
        global $wpdb;
        
        $table_queue = $wpdb->prefix . 'ai_rewriter_queue';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_queue}'") != $table_queue) {
            return null;
        }
        
        return $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_items,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_items,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_items,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_items
             FROM {$table_queue}"
        );
    }
    
    public function clear_auto_queue($status = null) {
        global $wpdb;
        
        $table_queue = $wpdb->prefix . 'ai_rewriter_queue';
        
        if ($status) {
            $result = $wpdb->delete($table_queue, array('status' => $status), array('%s'));
        } else {
            $result = $wpdb->query("TRUNCATE TABLE {$table_queue}");
        }
        
        return $result !== false;
    }
    
    // Database Setup
    public function setup_database() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Logs table
        $table_logs = $wpdb->prefix . 'ai_rewriter_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text,
            original_title text,
            new_title text,
            api_cost decimal(10,4) DEFAULT '0.0000',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Queue table
        $table_queue = $wpdb->prefix . 'ai_rewriter_queue';
        $sql_queue = "CREATE TABLE $table_queue (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            scheduled_time datetime DEFAULT CURRENT_TIMESTAMP,
            processed_time datetime DEFAULT NULL,
            error_message text,
            retry_count int DEFAULT '0',
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_logs);
        dbDelta($sql_queue);
    }
    
    // Plugin Lifecycle
    public function activate() {
        $this->setup_database();
        
        $defaults = array(
            'ai_rewriter_model' => 'gpt-3.5-turbo',
            'ai_rewriter_language' => 'Indonesian',
            'ai_rewriter_writing_style' => 'professional',
            'ai_rewriter_temperature' => 0.7,
            'ai_rewriter_max_tokens' => 2000,
            'ai_rewriter_auto_replace_images' => 1,
            'ai_rewriter_max_images' => 2,
            'ai_rewriter_image_source' => 'google',
            'ai_rewriter_auto_rewrite_enabled' => 0,
            'ai_rewriter_auto_min_words' => 50,
            'ai_rewriter_auto_delay_minutes' => 5,
            'ai_rewriter_auto_publish_after_rewrite' => 0,
            'ai_rewriter_auto_batch_size' => 1,
            'ai_rewriter_auto_max_retries' => 3,
            'ai_rewriter_auto_check_interval' => 15,
            'ai_rewriter_auto_retry_delay' => 30,
            'ai_rewriter_auto_processing_delay' => 2,
            // NEW API DEFAULTS
            'ai_rewriter_api_enabled' => 1,
            'ai_rewriter_api_require_auth' => 1,
            'ai_rewriter_api_log_requests' => 1,
            'ai_rewriter_api_rate_limit_hourly' => 10,
            'ai_rewriter_api_max_concurrent_batches' => 2
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
        
        // Create uploads directory for logs
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/ai-rewriter-logs/';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Generate initial API key
        if (!get_option('ai_rewriter_api_endpoint_key')) {
            $this->get_api_endpoint_key();
        }
        
        // Flush rewrite rules to ensure API endpoints work
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('ai_rewriter_auto_process_queue');
        wp_clear_scheduled_hook('ai_rewriter_delayed_auto_process');
        wp_clear_scheduled_hook('ai_rewriter_process_bulk_batch');
        wp_clear_scheduled_hook('ai_rewriter_cleanup_batch');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Background processor function for bulk API batches
 * This function is called by WordPress cron
 */
function ai_rewriter_process_bulk_batch($batch_id) {
    $batch_info = get_option('ai_rewriter_batch_' . $batch_id);
    
    if (!$batch_info || $batch_info['status'] !== 'processing') {
        error_log('AI Rewriter: Batch ' . $batch_id . ' not found or not processing');
        return;
    }
    
    try {
        // Get plugin instance
        if (!class_exists('AI_Article_Rewriter')) {
            throw new Exception('AI Article Rewriter plugin not available');
        }
        
        $plugin = AI_Article_Rewriter::get_instance();
        
        // Validasi OpenAI key masih ada
        $openai_key = get_option('ai_rewriter_api_key', '');
        if (empty($openai_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        // Get articles for this batch iteration
        $articles_to_process = array_slice(
            $batch_info['articles'], 
            $batch_info['processed_count'], 
            $batch_info['settings']['batch_size']
        );
        
        if (empty($articles_to_process)) {
            throw new Exception('No more articles to process');
        }
        
        foreach ($articles_to_process as $post_id) {
            // Check if batch was cancelled
            $current_batch = get_option('ai_rewriter_batch_' . $batch_id);
            if (!$current_batch || $current_batch['status'] !== 'processing') {
                error_log('AI Rewriter: Batch ' . $batch_id . ' was cancelled or completed');
                break;
            }
            
            try {
                // Validasi post masih ada dan masih draft
                $post = get_post($post_id);
                if (!$post || $post->post_status !== 'draft') {
                    throw new Exception('Post not found or not a draft');
                }
                
                // Process single article using plugin method
                $result = $plugin->process_single_article_api($post_id, $batch_info['settings']);
                
                $batch_info['results'][] = array(
                    'post_id' => $post_id,
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'title' => $result['title'] ?? '',
                    'original_title' => $result['original_title'] ?? '',
                    'status' => $result['status'] ?? '',
                    'word_count' => $result['word_count'] ?? 0,
                    'cost' => $result['cost'] ?? 0,
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'timestamp' => current_time('mysql')
                );
                
                if ($result['success']) {
                    $batch_info['success_count']++;
                } else {
                    $batch_info['error_count']++;
                }
                
                $batch_info['processed_count']++;
                
                // Update progress setiap artikel
                update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
                
                // Add processing delay to respect API rate limits
                $delay = get_option('ai_rewriter_auto_processing_delay', 2);
                sleep($delay);
                
            } catch (Exception $e) {
                $batch_info['error_count']++;
                $batch_info['processed_count']++;
                $batch_info['results'][] = array(
                    'post_id' => $post_id,
                    'success' => false,
                    'message' => 'Processing error: ' . $e->getMessage(),
                    'timestamp' => current_time('mysql')
                );
                
                update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
                error_log('AI Rewriter: Error processing post ' . $post_id . ' in batch ' . $batch_id . ': ' . $e->getMessage());
            }
        }
        
        // Check if all articles processed
        if ($batch_info['processed_count'] >= $batch_info['total_articles']) {
            // Batch completed
            $batch_info['status'] = 'completed';
            $batch_info['end_time'] = current_time('mysql');
            $batch_info['total_time'] = human_time_diff(strtotime($batch_info['start_time']), current_time('timestamp'));
            $batch_info['final_message'] = sprintf(
                __('Batch completed successfully! %d articles processed: %d successful, %d errors.', 'ai-article-rewriter'),
                $batch_info['total_articles'],
                $batch_info['success_count'],
                $batch_info['error_count']
            );
            
            // Send completion webhook if configured
            if (!empty($batch_info['callback_url'])) {
                ai_rewriter_send_webhook($batch_info['callback_url'], array(
                    'event' => 'batch_completed',
                    'batch_id' => $batch_id,
                    'total_articles' => $batch_info['total_articles'],
                    'success_count' => $batch_info['success_count'],
                    'error_count' => $batch_info['error_count'],
                    'total_time' => $batch_info['total_time'],
                    'timestamp' => current_time('c')
                ));
            }
            
            // Schedule cleanup after 24 hours
            wp_schedule_single_event(time() + (24 * 60 * 60), 'ai_rewriter_cleanup_batch', array($batch_id));
            
        } else {
            // Schedule next batch processing in 30 seconds
            wp_schedule_single_event(time() + 30, 'ai_rewriter_process_bulk_batch', array($batch_id));
        }
        
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        
        error_log('AI Rewriter: Batch ' . $batch_id . ' processed ' . count($articles_to_process) . ' articles. Progress: ' . $batch_info['processed_count'] . '/' . $batch_info['total_articles']);
        
    } catch (Exception $e) {
        // Mark batch as failed
        $batch_info['status'] = 'failed';
        $batch_info['end_time'] = current_time('mysql');
        $batch_info['final_message'] = 'Batch processing failed: ' . $e->getMessage();
        
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        error_log('AI Rewriter: Batch ' . $batch_id . ' failed: ' . $e->getMessage());
        
        // Send failure webhook if configured
        if (!empty($batch_info['callback_url'])) {
            ai_rewriter_send_webhook($batch_info['callback_url'], array(
                'event' => 'batch_failed',
                'batch_id' => $batch_id,
                'error' => $e->getMessage(),
                'processed_count' => $batch_info['processed_count'],
                'total_articles' => $batch_info['total_articles'],
                'timestamp' => current_time('c')
            ));
        }
    }
}

remove_action('rest_api_init', 'ai_rewriter_force_register_endpoints', 5);
add_action('rest_api_init', 'ai_rewriter_force_register_endpoints', 5);

// Ganti background processor yang lama  
remove_action('ai_rewriter_process_bulk_batch', 'ai_rewriter_process_bulk_batch');
add_action('ai_rewriter_process_bulk_batch', 'ai_rewriter_process_bulk_batch');

/**
 * Async Bulk Rewrite Callback - Immediate Response
 * Langsung return batch_id tanpa processing artikel sama sekali
 */
function ai_rewriter_async_bulk_callback($request) {
    // Ambil parameter
    $params = $request->get_params();
    $auto_publish = $params['auto_publish'] ?? true;
    $batch_size = $params['batch_size'] ?? 5;
    $max_articles = $params['max_articles'] ?? 0;
    $min_words = $params['min_words'] ?? 50;
    $exclude_processed = $params['exclude_processed'] ?? true;
    $process_images = $params['process_images'] ?? true;
    $callback_url = $params['callback_url'] ?? '';
    
    // Quick validation
    $openai_key = get_option('ai_rewriter_api_key', '');
    if (empty($openai_key)) {
        return new WP_Error(
            'missing_openai_key',
            'OpenAI API key tidak dikonfigurasi.',
            array('status' => 400)
        );
    }
    
    try {
        // Cek draft posts availability dengan query cepat
        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'posts_per_page' => $max_articles > 0 ? $max_articles : 100,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids'
        );
        
        if ($exclude_processed) {
            $query_args['meta_query'] = array(
                array(
                    'key' => '_ai_rewriter_processed',
                    'compare' => 'NOT EXISTS'
                )
            );
        }
        
        $posts = get_posts($query_args);
        
        if (empty($posts)) {
            return new WP_Error(
                'no_drafts_found',
                'Tidak ada artikel draft yang ditemukan.',
                array('status' => 404)
            );
        }
        
        // Filter cepat berdasarkan word count (hanya sampel untuk estimasi)
        $sample_eligible = 0;
        $sample_size = min(5, count($posts)); // Cek maksimal 5 artikel untuk estimasi
        
        for ($i = 0; $i < $sample_size; $i++) {
            $post = get_post($posts[$i]);
            if ($post) {
                $word_count = str_word_count(strip_tags($post->post_content));
                if ($word_count >= $min_words) {
                    $sample_eligible++;
                }
            }
        }
        
        // Estimasi total eligible articles
        $estimated_eligible = count($posts);
        if ($sample_size > 0) {
            $eligible_ratio = $sample_eligible / $sample_size;
            $estimated_eligible = round(count($posts) * $eligible_ratio);
        }
        
        if ($estimated_eligible == 0) {
            return new WP_Error(
                'no_eligible_articles',
                sprintf('Tidak ada artikel yang memenuhi kriteria (minimal %d kata).', $min_words),
                array('status' => 404)
            );
        }
        
        // Generate unique batch ID
        $batch_id = 'batch_' . uniqid() . '_' . time();
        
        // Setup batch information untuk background processing
        $batch_info = array(
            'batch_id' => $batch_id,
            'status' => 'queued', // Status awal: queued
            'start_time' => current_time('mysql'),
            'queue_time' => current_time('mysql'),
            'processing_start_time' => null,
            'end_time' => null,
            'total_articles' => 0, // Will be determined in background
            'estimated_articles' => $estimated_eligible,
            'processed_count' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'articles' => $posts, // All candidate posts
            'results' => array(),
            'settings' => array(
                'auto_publish' => $auto_publish,
                'batch_size' => min($batch_size, 5),
                'min_words' => $min_words,
                'max_articles' => $max_articles,
                'exclude_processed' => $exclude_processed,
                'process_images' => $process_images
            ),
            'callback_url' => $callback_url,
            'final_message' => '',
            'total_time' => '',
            'last_activity' => current_time('mysql')
        );
        
        // Simpan batch info
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        
        // Schedule immediate background processing (5 seconds delay)
        wp_schedule_single_event(time() + 5, 'ai_rewriter_process_async_batch', array($batch_id));
        
        // Send webhook untuk batch queued
        if (!empty($callback_url)) {
            ai_rewriter_send_webhook($callback_url, array(
                'event' => 'batch_queued',
                'batch_id' => $batch_id,
                'estimated_articles' => $estimated_eligible,
                'settings' => $batch_info['settings'],
                'timestamp' => current_time('c')
            ));
        }
        
        // Log activity
        error_log("AI Rewriter: Batch {$batch_id} queued with {$estimated_eligible} estimated articles");
        
        // IMMEDIATE RESPONSE - No processing here!
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Bulk rewrite batch queued successfully!',
            'batch_id' => $batch_id,
            'status' => 'queued',
            'estimated_articles' => $estimated_eligible,
            'estimated_completion_minutes' => ceil($estimated_eligible * 0.75), // ~45 seconds per article
            'settings' => $batch_info['settings'],
            'status_check_url' => rest_url('ai-rewriter/v1/batch-status/' . $batch_id),
            'polling_interval_seconds' => 30,
            'note' => 'Processing will start in 5 seconds. Use status_check_url to monitor progress.',
            'timestamp' => current_time('c')
        ), 202); // 202 Accepted - Request accepted for async processing
        
    } catch (Exception $e) {
        error_log('AI Rewriter Async Batch Error: ' . $e->getMessage());
        
        return new WP_Error(
            'batch_setup_error',
            'Gagal menyiapkan batch processing: ' . $e->getMessage(),
            array('status' => 500)
        );
    }
}

/**
 * Enhanced Batch Status dengan lebih banyak informasi
 */
function ai_rewriter_enhanced_batch_status_callback($request) {
    $batch_id = $request->get_param('batch_id');
    
    if (empty($batch_id)) {
        return new WP_Error(
            'missing_batch_id',
            'Batch ID is required',
            array('status' => 400)
        );
    }
    
    $batch_info = get_option('ai_rewriter_batch_' . $batch_id);
    
    if (!$batch_info) {
        return new WP_Error(
            'batch_not_found',
            'Batch not found or expired',
            array('status' => 404)
        );
    }
    
    // Calculate progress percentage
    $progress_percentage = 0;
    if ($batch_info['total_articles'] > 0) {
        $progress_percentage = round(($batch_info['processed_count'] / $batch_info['total_articles']) * 100, 1);
    } elseif ($batch_info['estimated_articles'] > 0 && $batch_info['status'] === 'queued') {
        $progress_percentage = 0;
    }
    
    // Calculate processing speed (articles per minute)
    $processing_speed = 0;
    $estimated_remaining_minutes = 0;
    
    if ($batch_info['processing_start_time'] && $batch_info['processed_count'] > 0) {
        $processing_duration = time() - strtotime($batch_info['processing_start_time']);
        if ($processing_duration > 0) {
            $processing_speed = round(($batch_info['processed_count'] / $processing_duration) * 60, 2);
            
            $remaining_articles = $batch_info['total_articles'] - $batch_info['processed_count'];
            if ($processing_speed > 0) {
                $estimated_remaining_minutes = round($remaining_articles / $processing_speed);
            }
        }
    }
    
    // Determine next check interval based on status
    $suggested_poll_interval = match($batch_info['status']) {
        'queued' => 10, // Check every 10 seconds when queued
        'processing' => 30, // Check every 30 seconds when processing
        'completed', 'failed' => 0, // No need to poll anymore
        default => 30
    };
    
    return new WP_REST_Response(array(
        'success' => true,
        'batch_id' => $batch_id,
        'status' => $batch_info['status'],
        'progress_percentage' => $progress_percentage,
        
        // Article counts
        'total_articles' => $batch_info['total_articles'],
        'estimated_articles' => $batch_info['estimated_articles'] ?? 0,
        'processed_count' => $batch_info['processed_count'],
        'success_count' => $batch_info['success_count'],
        'error_count' => $batch_info['error_count'],
        'remaining' => max(0, $batch_info['total_articles'] - $batch_info['processed_count']),
        
        // Timing information
        'queue_time' => $batch_info['queue_time'] ?? $batch_info['start_time'],
        'processing_start_time' => $batch_info['processing_start_time'],
        'last_activity' => $batch_info['last_activity'] ?? $batch_info['start_time'],
        'end_time' => $batch_info['end_time'],
        'total_time' => $batch_info['total_time'] ?? '',
        
        // Performance metrics
        'processing_speed_per_minute' => $processing_speed,
        'estimated_remaining_minutes' => $estimated_remaining_minutes,
        
        // Status messages
        'final_message' => $batch_info['final_message'] ?? '',
        'current_activity' => ai_rewriter_get_batch_activity_message($batch_info),
        
        // Recent results (last 3)
        'recent_results' => array_slice($batch_info['results'], -3),
        'total_results_count' => count($batch_info['results']),
        
        // Settings used
        'settings' => $batch_info['settings'] ?? array(),
        
        // Client instructions
        'suggested_poll_interval_seconds' => $suggested_poll_interval,
        'is_final' => in_array($batch_info['status'], ['completed', 'failed']),
        
        'timestamp' => current_time('c')
    ), 200);
}

/**
 * Helper function untuk generate activity message
 */
function ai_rewriter_get_batch_activity_message($batch_info) {
    switch ($batch_info['status']) {
        case 'queued':
            return 'Batch is queued and will start processing shortly...';
            
        case 'initializing':
            return 'Scanning and filtering eligible articles...';
            
        case 'processing':
            if ($batch_info['total_articles'] > 0) {
                $remaining = $batch_info['total_articles'] - $batch_info['processed_count'];
                return "Processing articles... {$remaining} articles remaining";
            }
            return 'Processing articles in progress...';
            
        case 'completed':
            return $batch_info['final_message'] ?: 'Batch completed successfully';
            
        case 'failed':
            return $batch_info['final_message'] ?: 'Batch processing failed';
            
        default:
            return 'Status unknown';
    }
}

/**
 * Background Processor untuk Async Batch - Kompletely separated dari request
 */
function ai_rewriter_process_async_batch($batch_id) {
    $batch_info = get_option('ai_rewriter_batch_' . $batch_id);
    
    if (!$batch_info || !in_array($batch_info['status'], ['queued', 'processing'])) {
        error_log('AI Rewriter: Async batch ' . $batch_id . ' not found or not processable');
        return;
    }
    
    try {
        // Update status ke initializing
        $batch_info['status'] = 'initializing';
        $batch_info['processing_start_time'] = current_time('mysql');
        $batch_info['last_activity'] = current_time('mysql');
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        
        // Get plugin instance
        if (!class_exists('AI_Article_Rewriter')) {
            throw new Exception('AI Article Rewriter plugin not available');
        }
        
        $plugin = AI_Article_Rewriter::get_instance();
        
        // Re-validate OpenAI key
        $openai_key = get_option('ai_rewriter_api_key', '');
        if (empty($openai_key)) {
            throw new Exception('OpenAI API key not configured');
        }
        
        // Filter eligible posts dengan detail checking
        $eligible_posts = array();
        foreach ($batch_info['articles'] as $post_id) {
            $post = get_post($post_id);
            if (!$post || $post->post_status !== 'draft') {
                continue;
            }
            
            // Skip jika sudah diproses dan exclude_processed = true
            if ($batch_info['settings']['exclude_processed'] && 
                get_post_meta($post_id, '_ai_rewriter_processed', true)) {
                continue;
            }
            
            // Check minimum words
            $word_count = str_word_count(strip_tags($post->post_content));
            if ($word_count < $batch_info['settings']['min_words']) {
                continue;
            }
            
            $eligible_posts[] = $post_id;
        }
        
        // Limit jika ada max_articles
        if ($batch_info['settings']['max_articles'] > 0 && 
            count($eligible_posts) > $batch_info['settings']['max_articles']) {
            $eligible_posts = array_slice($eligible_posts, 0, $batch_info['settings']['max_articles']);
        }
        
        if (empty($eligible_posts)) {
            throw new Exception('No eligible articles found for processing');
        }
        
        // Update batch dengan actual article count
        $batch_info['articles'] = $eligible_posts;
        $batch_info['total_articles'] = count($eligible_posts);
        $batch_info['status'] = 'processing';
        $batch_info['last_activity'] = current_time('mysql');
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        
        // Send webhook batch started dengan actual count
        if (!empty($batch_info['callback_url'])) {
            ai_rewriter_send_webhook($batch_info['callback_url'], array(
                'event' => 'batch_started',
                'batch_id' => $batch_id,
                'total_articles' => $batch_info['total_articles'],
                'timestamp' => current_time('c')
            ));
        }
        
        error_log("AI Rewriter: Starting async processing for batch {$batch_id} with {$batch_info['total_articles']} articles");
        
        // Process pertama batch langsung
        $first_batch_size = min($batch_info['settings']['batch_size'], $batch_info['total_articles']);
        $first_batch_articles = array_slice($eligible_posts, 0, $first_batch_size);
        
        foreach ($first_batch_articles as $post_id) {
            // Check if batch was cancelled
            $current_batch = get_option('ai_rewriter_batch_' . $batch_id);
            if (!$current_batch || $current_batch['status'] !== 'processing') {
                error_log('AI Rewriter: Batch ' . $batch_id . ' was cancelled');
                return;
            }
            
            try {
                // Process single article
                $result = $plugin->process_single_article_api($post_id, $batch_info['settings']);
                
                $batch_info['results'][] = array(
                    'post_id' => $post_id,
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'title' => $result['title'] ?? '',
                    'original_title' => $result['original_title'] ?? '',
                    'status' => $result['status'] ?? '',
                    'word_count' => $result['word_count'] ?? 0,
                    'cost' => $result['cost'] ?? 0,
                    'tokens_used' => $result['tokens_used'] ?? 0,
                    'timestamp' => current_time('mysql')
                );
                
                if ($result['success']) {
                    $batch_info['success_count']++;
                } else {
                    $batch_info['error_count']++;
                }
                
                $batch_info['processed_count']++;
                $batch_info['last_activity'] = current_time('mysql');
                
                // Update progress
                update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
                
                error_log("AI Rewriter: Processed article {$post_id} in batch {$batch_id}. Progress: {$batch_info['processed_count']}/{$batch_info['total_articles']}");
                
                // Processing delay
                $delay = get_option('ai_rewriter_auto_processing_delay', 2);
                sleep($delay);
                
            } catch (Exception $e) {
                $batch_info['error_count']++;
                $batch_info['processed_count']++;
                $batch_info['results'][] = array(
                    'post_id' => $post_id,
                    'success' => false,
                    'message' => 'Processing error: ' . $e->getMessage(),
                    'timestamp' => current_time('mysql')
                );
                
                $batch_info['last_activity'] = current_time('mysql');
                update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
                
                error_log('AI Rewriter: Error processing post ' . $post_id . ' in batch ' . $batch_id . ': ' . $e->getMessage());
            }
        }
        
        // Check if completed
        if ($batch_info['processed_count'] >= $batch_info['total_articles']) {
            // Mark as completed
            $batch_info['status'] = 'completed';
            $batch_info['end_time'] = current_time('mysql');
            $batch_info['total_time'] = human_time_diff(
                strtotime($batch_info['processing_start_time']), 
                current_time('timestamp')
            );
            $batch_info['final_message'] = sprintf(
                'Batch completed successfully! %d articles processed: %d successful, %d errors in %s.',
                $batch_info['total_articles'],
                $batch_info['success_count'],
                $batch_info['error_count'],
                $batch_info['total_time']
            );
            
            update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
            
            // Send completion webhook
            if (!empty($batch_info['callback_url'])) {
                ai_rewriter_send_webhook($batch_info['callback_url'], array(
                    'event' => 'batch_completed',
                    'batch_id' => $batch_id,
                    'total_articles' => $batch_info['total_articles'],
                    'success_count' => $batch_info['success_count'],
                    'error_count' => $batch_info['error_count'],
                    'total_time' => $batch_info['total_time'],
                    'timestamp' => current_time('c')
                ));
            }
            
            // Schedule cleanup
            wp_schedule_single_event(time() + (24 * 60 * 60), 'ai_rewriter_cleanup_batch', array($batch_id));
            
            error_log("AI Rewriter: Batch {$batch_id} completed successfully");
            
        } else {
            // Schedule next batch processing
            wp_schedule_single_event(time() + 30, 'ai_rewriter_process_async_batch_continuation', array($batch_id));
            error_log("AI Rewriter: Scheduled continuation for batch {$batch_id}");
        }
        
    } catch (Exception $e) {
        // Mark batch as failed
        $batch_info['status'] = 'failed';
        $batch_info['end_time'] = current_time('mysql');
        $batch_info['final_message'] = 'Batch processing failed: ' . $e->getMessage();
        $batch_info['last_activity'] = current_time('mysql');
        
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        error_log('AI Rewriter: Batch ' . $batch_id . ' failed: ' . $e->getMessage());
        
        // Send failure webhook
        if (!empty($batch_info['callback_url'])) {
            ai_rewriter_send_webhook($batch_info['callback_url'], array(
                'event' => 'batch_failed',
                'batch_id' => $batch_id,
                'error' => $e->getMessage(),
                'processed_count' => $batch_info['processed_count'],
                'total_articles' => $batch_info['total_articles'],
                'timestamp' => current_time('c')
            ));
        }
    }
}

/**
 * Continuation processor untuk batch yang belum selesai
 */
function ai_rewriter_process_async_batch_continuation($batch_id) {
    $batch_info = get_option('ai_rewriter_batch_' . $batch_id);
    
    if (!$batch_info || $batch_info['status'] !== 'processing') {
        return;
    }
    
    // Continue processing dengan logic yang sama seperti ai_rewriter_process_async_batch
    // tapi dimulai dari $batch_info['processed_count']
    
    try {
        $plugin = AI_Article_Rewriter::get_instance();
        
        // Get remaining articles
        $remaining_articles = array_slice(
            $batch_info['articles'], 
            $batch_info['processed_count'], 
            $batch_info['settings']['batch_size']
        );
        
        foreach ($remaining_articles as $post_id) {
            // Same processing logic as above
            try {
                $result = $plugin->process_single_article_api($post_id, $batch_info['settings']);
                
                $batch_info['results'][] = array(
                    'post_id' => $post_id,
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'title' => $result['title'] ?? '',
                    'original_title' => $result['original_title'] ?? '',
                    'status' => $result['status'] ?? '',
                    'word_count' => $result['word_count'] ?? 0,
                    'cost' => $result['cost'] ?? 0,
                    'timestamp' => current_time('mysql')
                );
                
                if ($result['success']) {
                    $batch_info['success_count']++;
                } else {
                    $batch_info['error_count']++;
                }
                
                $batch_info['processed_count']++;
                $batch_info['last_activity'] = current_time('mysql');
                update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
                
                $delay = get_option('ai_rewriter_auto_processing_delay', 2);
                sleep($delay);
                
            } catch (Exception $e) {
                $batch_info['error_count']++;
                $batch_info['processed_count']++;
                $batch_info['results'][] = array(
                    'post_id' => $post_id,
                    'success' => false,
                    'message' => 'Processing error: ' . $e->getMessage(),
                    'timestamp' => current_time('mysql')
                );
                $batch_info['last_activity'] = current_time('mysql');
                update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
            }
        }
        
        // Check completion
        if ($batch_info['processed_count'] >= $batch_info['total_articles']) {
            // Complete the batch (same logic as above)
            $batch_info['status'] = 'completed';
            $batch_info['end_time'] = current_time('mysql');
            $batch_info['total_time'] = human_time_diff(
                strtotime($batch_info['processing_start_time']), 
                current_time('timestamp')
            );
            $batch_info['final_message'] = sprintf(
                'Batch completed! %d/%d successful, %d errors in %s.',
                $batch_info['success_count'],
                $batch_info['total_articles'],
                $batch_info['error_count'],
                $batch_info['total_time']
            );
            
            update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
            
            if (!empty($batch_info['callback_url'])) {
                ai_rewriter_send_webhook($batch_info['callback_url'], array(
                    'event' => 'batch_completed',
                    'batch_id' => $batch_id,
                    'total_articles' => $batch_info['total_articles'],
                    'success_count' => $batch_info['success_count'],
                    'error_count' => $batch_info['error_count'],
                    'total_time' => $batch_info['total_time'],
                    'timestamp' => current_time('c')
                ));
            }
            
            wp_schedule_single_event(time() + (24 * 60 * 60), 'ai_rewriter_cleanup_batch', array($batch_id));
        } else {
            // Schedule next continuation
            wp_schedule_single_event(time() + 30, 'ai_rewriter_process_async_batch_continuation', array($batch_id));
        }
        
    } catch (Exception $e) {
        $batch_info['status'] = 'failed';
        $batch_info['end_time'] = current_time('mysql');
        $batch_info['final_message'] = 'Batch continuation failed: ' . $e->getMessage();
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        
        error_log('AI Rewriter: Batch continuation ' . $batch_id . ' failed: ' . $e->getMessage());
    }
}

/**
 * Register Enhanced Async Endpoints
 */
function ai_rewriter_register_async_endpoints() {
    if (!get_option('ai_rewriter_api_enabled', 1)) {
        return;
    }
    
    $namespace = 'ai-rewriter/v1';
    
    // Status endpoint (unchanged)
    register_rest_route($namespace, '/status', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_status_callback',
        'permission_callback' => 'ai_rewriter_check_permissions'
    ));
    
    // Async bulk rewrite endpoint - langsung return batch_id
    register_rest_route($namespace, '/bulk-rewrite-all', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'ai_rewriter_async_bulk_callback',
        'permission_callback' => 'ai_rewriter_check_permissions',
        'args' => array(
            'auto_publish' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'description' => 'Auto publish articles after rewrite'
            ),
            'batch_size' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 3,
                'minimum' => 1,
                'maximum' => 5,
                'description' => 'Articles processed per batch iteration'
            ),
            'max_articles' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 0,
                'minimum' => 0,
                'description' => 'Maximum articles to process (0 = no limit)'
            ),
            'min_words' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 50,
                'minimum' => 1,
                'description' => 'Minimum word count for processing'
            ),
            'exclude_processed' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'description' => 'Skip already processed articles'
            ),
            'process_images' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'description' => 'Process and replace images'
            ),
            'callback_url' => array(
                'required' => false,
                'type' => 'string',
                'format' => 'uri',
                'description' => 'Webhook URL for batch notifications'
            )
        )
    ));
    
    // Enhanced batch status endpoint
    register_rest_route($namespace, '/batch-status/(?P<batch_id>[a-zA-Z0-9_]+)', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_enhanced_batch_status_callback',
        'permission_callback' => 'ai_rewriter_check_permissions',
        'args' => array(
            'batch_id' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Batch ID to check status'
            )
        )
    ));
    
    error_log('AI Rewriter: Registered 3 async API endpoints');
}

// Register hooks untuk async processing
add_action('rest_api_init', 'ai_rewriter_register_async_endpoints', 5);
add_action('ai_rewriter_process_async_batch', 'ai_rewriter_process_async_batch');
add_action('ai_rewriter_process_async_batch_continuation', 'ai_rewriter_process_async_batch_continuation');

function test_ai_rewriter_async_response() {
    // Test quick response - should return in <5 seconds
    $start_time = microtime(true);
    
    $response = wp_remote_post(home_url('/wp-json/ai-rewriter/v1/bulk-rewrite-all'), array(
        'headers' => array(
            'X-API-Key' => get_option('ai_rewriter_api_endpoint_key', ''),
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'max_articles' => 1,
            'auto_publish' => false
        )),
        'timeout' => 10
    ));
    
    $end_time = microtime(true);
    $duration = round(($end_time - $start_time), 2);
    
    error_log("AI Rewriter Async Test: Response time = {$duration} seconds");
    
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['success'] && isset($data['batch_id'])) {
            error_log("AI Rewriter Async Test: SUCCESS - Batch ID: {$data['batch_id']}");
        } else {
            error_log("AI Rewriter Async Test: FAILED - " . print_r($data, true));
        }
    } else {
        error_log("AI Rewriter Async Test: ERROR - " . $response->get_error_message());
    }
}

/**
 * Batch Management Endpoints - untuk cancel, pause, resume
 */
function ai_rewriter_batch_cancel_callback($request) {
    $batch_id = $request->get_param('batch_id');
    
    if (empty($batch_id)) {
        return new WP_Error('missing_batch_id', 'Batch ID is required', array('status' => 400));
    }
    
    $batch_info = get_option('ai_rewriter_batch_' . $batch_id);
    
    if (!$batch_info) {
        return new WP_Error('batch_not_found', 'Batch not found', array('status' => 404));
    }
    
    if (in_array($batch_info['status'], ['completed', 'failed', 'cancelled'])) {
        return new WP_Error('batch_not_cancellable', 'Batch cannot be cancelled in current state', array('status' => 400));
    }
    
    // Cancel the batch
    $batch_info['status'] = 'cancelled';
    $batch_info['end_time'] = current_time('mysql');
    $batch_info['final_message'] = 'Batch cancelled by user request';
    $batch_info['last_activity'] = current_time('mysql');
    
    update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
    
    // Send webhook
    if (!empty($batch_info['callback_url'])) {
        ai_rewriter_send_webhook($batch_info['callback_url'], array(
            'event' => 'batch_cancelled',
            'batch_id' => $batch_id,
            'processed_count' => $batch_info['processed_count'],
            'success_count' => $batch_info['success_count'],
            'error_count' => $batch_info['error_count'],
            'timestamp' => current_time('c')
        ));
    }
    
    return new WP_REST_Response(array(
        'success' => true,
        'message' => 'Batch cancelled successfully',
        'batch_id' => $batch_id,
        'final_stats' => array(
            'processed' => $batch_info['processed_count'],
            'success' => $batch_info['success_count'],
            'errors' => $batch_info['error_count']
        )
    ), 200);
}

/**
 * List Active Batches Endpoint
 */
function ai_rewriter_list_batches_callback($request) {
    $status_filter = $request->get_param('status');
    $limit = min(intval($request->get_param('limit') ?: 10), 50);
    
    $batches = array();
    $options = wp_load_alloptions();
    
    foreach ($options as $key => $value) {
        if (strpos($key, 'ai_rewriter_batch_') === 0) {
            $batch_data = maybe_unserialize($value);
            if (is_array($batch_data) && isset($batch_data['batch_id'])) {
                // Filter by status if specified
                if ($status_filter && $batch_data['status'] !== $status_filter) {
                    continue;
                }
                
                $progress = 0;
                if ($batch_data['total_articles'] > 0) {
                    $progress = round(($batch_data['processed_count'] / $batch_data['total_articles']) * 100, 1);
                }
                
                $batches[] = array(
                    'batch_id' => $batch_data['batch_id'],
                    'status' => $batch_data['status'],
                    'progress_percentage' => $progress,
                    'total_articles' => $batch_data['total_articles'],
                    'processed_count' => $batch_data['processed_count'],
                    'success_count' => $batch_data['success_count'],
                    'error_count' => $batch_data['error_count'],
                    'start_time' => $batch_data['start_time'],
                    'end_time' => $batch_data['end_time'],
                    'last_activity' => $batch_data['last_activity'] ?? $batch_data['start_time'],
                    'settings' => $batch_data['settings'] ?? array()
                );
            }
        }
    }
    
    // Sort by start time (newest first)
    usort($batches, function($a, $b) {
        return strtotime($b['start_time']) - strtotime($a['start_time']);
    });
    
    $batches = array_slice($batches, 0, $limit);
    
    return new WP_REST_Response(array(
        'success' => true,
        'batches' => $batches,
        'total_found' => count($batches),
        'filter_applied' => $status_filter ? "status: {$status_filter}" : 'none',
        'timestamp' => current_time('c')
    ), 200);
}

/**
 * Batch Results Detail Endpoint
 */
function ai_rewriter_batch_results_callback($request) {
    $batch_id = $request->get_param('batch_id');
    $page = max(1, intval($request->get_param('page') ?: 1));
    $per_page = min(intval($request->get_param('per_page') ?: 20), 100);
    $status_filter = $request->get_param('result_status'); // success, error
    
    if (empty($batch_id)) {
        return new WP_Error('missing_batch_id', 'Batch ID is required', array('status' => 400));
    }
    
    $batch_info = get_option('ai_rewriter_batch_' . $batch_id);
    
    if (!$batch_info) {
        return new WP_Error('batch_not_found', 'Batch not found', array('status' => 404));
    }
    
    $results = $batch_info['results'] ?? array();
    
    // Filter by result status if specified
    if ($status_filter) {
        $results = array_filter($results, function($result) use ($status_filter) {
            return ($status_filter === 'success' && $result['success']) ||
                   ($status_filter === 'error' && !$result['success']);
        });
    }
    
    $total_results = count($results);
    $offset = ($page - 1) * $per_page;
    $paged_results = array_slice($results, $offset, $per_page);
    
    // Add post details to results
    foreach ($paged_results as &$result) {
        if (isset($result['post_id'])) {
            $post = get_post($result['post_id']);
            if ($post) {
                $result['current_post_status'] = $post->post_status;
                $result['current_post_title'] = $post->post_title;
                $result['post_url'] = get_permalink($post->ID);
                $result['edit_url'] = get_edit_post_link($post->ID, 'raw');
            }
        }
    }
    
    return new WP_REST_Response(array(
        'success' => true,
        'batch_id' => $batch_id,
        'batch_status' => $batch_info['status'],
        'results' => $paged_results,
        'pagination' => array(
            'page' => $page,
            'per_page' => $per_page,
            'total_results' => $total_results,
            'total_pages' => ceil($total_results / $per_page)
        ),
        'filter_applied' => $status_filter ?: 'none',
        'timestamp' => current_time('c')
    ), 200);
}

/**
 * System Stats Endpoint - untuk monitoring
 */
function ai_rewriter_system_stats_callback($request) {
    global $wpdb;
    
    // Count draft posts
    $draft_count = wp_count_posts('post')->draft;
    
    // Count processed posts
    $processed_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_ai_rewriter_processed'"
    );
    
    // Active batches
    $active_batches = 0;
    $completed_batches = 0;
    $failed_batches = 0;
    
    $options = wp_load_alloptions();
    foreach ($options as $key => $value) {
        if (strpos($key, 'ai_rewriter_batch_') === 0) {
            $batch_data = maybe_unserialize($value);
            if (is_array($batch_data) && isset($batch_data['status'])) {
                switch ($batch_data['status']) {
                    case 'queued':
                    case 'processing':
                    case 'initializing':
                        $active_batches++;
                        break;
                    case 'completed':
                        $completed_batches++;
                        break;
                    case 'failed':
                        $failed_batches++;
                        break;
                }
            }
        }
    }
    
    // OpenAI config status
    $openai_configured = !empty(get_option('ai_rewriter_api_key', ''));
    $auto_rewrite_enabled = get_option('ai_rewriter_auto_rewrite_enabled', 0);
    
    // Cron status
    $next_auto_process = wp_next_scheduled('ai_rewriter_auto_process_queue');
    
    return new WP_REST_Response(array(
        'success' => true,
        'system_status' => array(
            'plugin_version' => defined('AI_REWRITER_VERSION') ? AI_REWRITER_VERSION : '2.0.0',
            'api_enabled' => get_option('ai_rewriter_api_enabled', 1),
            'openai_configured' => $openai_configured,
            'auto_rewrite_enabled' => $auto_rewrite_enabled,
            'next_auto_process' => $next_auto_process ? date('Y-m-d H:i:s', $next_auto_process) : null
        ),
        'content_stats' => array(
            'total_draft_posts' => intval($draft_count),
            'total_processed_posts' => intval($processed_count),
            'unprocessed_drafts' => max(0, intval($draft_count) - intval($processed_count))
        ),
        'batch_stats' => array(
            'active_batches' => $active_batches,
            'completed_batches' => $completed_batches,
            'failed_batches' => $failed_batches,
            'total_batches' => $active_batches + $completed_batches + $failed_batches
        ),
        'performance_info' => array(
            'average_processing_time' => '45-60 seconds per article',
            'recommended_batch_size' => '3-5 articles',
            'estimated_cost_per_article' => '$0.002-0.005 (GPT-3.5) or $0.04-0.10 (GPT-4)'
        ),
        'timestamp' => current_time('c')
    ), 200);
}

/**
 * Enhanced endpoint registration dengan batch management
 */
function ai_rewriter_register_enhanced_async_endpoints() {
    if (!get_option('ai_rewriter_api_enabled', 1)) {
        return;
    }
    
    $namespace = 'ai-rewriter/v1';
    
    // Core endpoints
    register_rest_route($namespace, '/status', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_status_callback',
        'permission_callback' => 'ai_rewriter_check_permissions'
    ));
    
    register_rest_route($namespace, '/bulk-rewrite-all', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'ai_rewriter_async_bulk_callback',
        'permission_callback' => 'ai_rewriter_check_permissions',
        'args' => array(
            'auto_publish' => array('required' => false, 'type' => 'boolean', 'default' => true),
            'batch_size' => array('required' => false, 'type' => 'integer', 'default' => 3, 'minimum' => 1, 'maximum' => 5),
            'max_articles' => array('required' => false, 'type' => 'integer', 'default' => 0, 'minimum' => 0),
            'min_words' => array('required' => false, 'type' => 'integer', 'default' => 50, 'minimum' => 1),
            'exclude_processed' => array('required' => false, 'type' => 'boolean', 'default' => true),
            'process_images' => array('required' => false, 'type' => 'boolean', 'default' => true),
            'callback_url' => array('required' => false, 'type' => 'string', 'format' => 'uri')
        )
    ));
    
    register_rest_route($namespace, '/batch-status/(?P<batch_id>[a-zA-Z0-9_]+)', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_enhanced_batch_status_callback',
        'permission_callback' => 'ai_rewriter_check_permissions'
    ));
    
    // Batch management endpoints
    register_rest_route($namespace, '/batch/(?P<batch_id>[a-zA-Z0-9_]+)/cancel', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'ai_rewriter_batch_cancel_callback',
        'permission_callback' => 'ai_rewriter_check_permissions'
    ));
    
    register_rest_route($namespace, '/batches', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_list_batches_callback',
        'permission_callback' => 'ai_rewriter_check_permissions',
        'args' => array(
            'status' => array('required' => false, 'type' => 'string', 'enum' => ['queued', 'processing', 'completed', 'failed', 'cancelled']),
            'limit' => array('required' => false, 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 50)
        )
    ));
    
    register_rest_route($namespace, '/batch/(?P<batch_id>[a-zA-Z0-9_]+)/results', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_batch_results_callback',
        'permission_callback' => 'ai_rewriter_check_permissions',
        'args' => array(
            'page' => array('required' => false, 'type' => 'integer', 'default' => 1, 'minimum' => 1),
            'per_page' => array('required' => false, 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100),
            'result_status' => array('required' => false, 'type' => 'string', 'enum' => ['success', 'error'])
        )
    ));
    
    register_rest_route($namespace, '/system-stats', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_system_stats_callback',
        'permission_callback' => 'ai_rewriter_check_permissions'
    ));
    
    error_log('AI Rewriter: Registered 7 enhanced async API endpoints with batch management');
}
// Replace endpoint registration
remove_action('rest_api_init', 'ai_rewriter_register_async_endpoints', 5);
add_action('rest_api_init', 'ai_rewriter_register_enhanced_async_endpoints', 5);

/**
 * Cleanup expired/old batches - scheduled task
 */
function ai_rewriter_cleanup_old_batches() {
    $options = wp_load_alloptions();
    $cutoff_time = time() - (7 * 24 * 60 * 60); // 7 days ago
    $cleaned_count = 0;
    
    foreach ($options as $key => $value) {
        if (strpos($key, 'ai_rewriter_batch_') === 0) {
            $batch_data = maybe_unserialize($value);
            if (is_array($batch_data) && isset($batch_data['start_time'])) {
                $batch_time = strtotime($batch_data['start_time']);
                
                // Clean up old completed/failed batches
                if ($batch_time < $cutoff_time && 
                    in_array($batch_data['status'] ?? '', ['completed', 'failed', 'cancelled'])) {
                    delete_option($key);
                    $cleaned_count++;
                }
            }
        }
    }
    
    if ($cleaned_count > 0) {
        error_log("AI Rewriter: Cleaned up {$cleaned_count} old batch records");
    }
}

// Schedule cleanup task
if (!wp_next_scheduled('ai_rewriter_cleanup_old_batches')) {
    wp_schedule_event(time(), 'daily', 'ai_rewriter_cleanup_old_batches');
}
add_action('ai_rewriter_cleanup_old_batches', 'ai_rewriter_cleanup_old_batches');

/**
 * Debug helper untuk troubleshooting async processing
 */
function ai_rewriter_debug_batch($batch_id) {
    $batch_info = get_option('ai_rewriter_batch_' . $batch_id);
    
    if (!$batch_info) {
        error_log("DEBUG: Batch {$batch_id} not found");
        return;
    }
    
    error_log("DEBUG: Batch {$batch_id} status: {$batch_info['status']}");
    error_log("DEBUG: Processed: {$batch_info['processed_count']}/{$batch_info['total_articles']}");
    error_log("DEBUG: Success: {$batch_info['success_count']}, Errors: {$batch_info['error_count']}");
    error_log("DEBUG: Last activity: " . ($batch_info['last_activity'] ?? 'N/A'));
    
    // Check if cron jobs are scheduled
    $next_async = wp_next_scheduled('ai_rewriter_process_async_batch', array($batch_id));
    $next_continuation = wp_next_scheduled('ai_rewriter_process_async_batch_continuation', array($batch_id));
    
    error_log("DEBUG: Next async scheduled: " . ($next_async ? date('Y-m-d H:i:s', $next_async) : 'None'));
    error_log("DEBUG: Next continuation scheduled: " . ($next_continuation ? date('Y-m-d H:i:s', $next_continuation) : 'None'));
}

// Helper function for webhook notifications (can be called from anywhere)
function ai_rewriter_send_webhook($url, $data) {
    if (empty($url)) {
        return false;
    }
    
    return wp_remote_post($url, array(
        'method' => 'POST',
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'AI-Article-Rewriter/' . (defined('AI_REWRITER_VERSION') ? AI_REWRITER_VERSION : '2.0.0')
        ),
        'body' => wp_json_encode($data),
        'blocking' => false // Don't wait for response
    ));
}

/**
 * Debug function to check if API endpoints are being registered
 * Add this temporarily to troubleshoot the issue
 */
function debug_ai_rewriter_api() {
    // Check if the main plugin class exists
    if (!class_exists('AI_Article_Rewriter')) {
        error_log('AI Rewriter: Main plugin class not found');
        return;
    }
    
    // Check if API endpoints class exists
    if (!class_exists('AI_Rewriter_API_Endpoints')) {
        error_log('AI Rewriter: API Endpoints class not found - check if includes/class-api-endpoints.php exists');
        return;
    }
    
    // Check if API is enabled
    $api_enabled = get_option('ai_rewriter_api_enabled', 1);
    error_log('AI Rewriter: API enabled = ' . ($api_enabled ? 'YES' : 'NO'));
    
    // Check if OpenAI key is configured
    $openai_key = get_option('ai_rewriter_api_key', '');
    error_log('AI Rewriter: OpenAI key configured = ' . (empty($openai_key) ? 'NO' : 'YES'));
    
    // Check if API endpoint key exists
    $endpoint_key = get_option('ai_rewriter_api_endpoint_key', '');
    error_log('AI Rewriter: Endpoint API key = ' . (empty($endpoint_key) ? 'NOT SET' : substr($endpoint_key, 0, 10) . '...'));
}

// Hook the debug function
add_action('rest_api_init', 'debug_ai_rewriter_api');

/**
 * Simple test endpoint to verify REST API is working
 */
function register_ai_rewriter_test_endpoint() {
    register_rest_route('ai-rewriter/v1', '/test', array(
        'methods' => 'GET',
        'callback' => function() {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'AI Rewriter API test endpoint working!',
                'timestamp' => current_time('c'),
                'version' => defined('AI_REWRITER_VERSION') ? AI_REWRITER_VERSION : '2.0.0'
            ), 200);
        },
        'permission_callback' => '__return_true'
    ));
}

/**
 * Sync Process
 */
function ai_rewriter_force_register_endpoints() {
    if (!get_option('ai_rewriter_api_enabled', 1)) {
        return;
    }
    
    $namespace = 'ai-rewriter/v1';
    
    // Status endpoint
    register_rest_route($namespace, '/status', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_status_callback',
        'permission_callback' => 'ai_rewriter_check_permissions'
    ));
    
    // Bulk rewrite endpoint dengan parameter yang lebih lengkap
    register_rest_route($namespace, '/bulk-rewrite-all', array(
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'ai_rewriter_bulk_callback',
        'permission_callback' => 'ai_rewriter_check_permissions',
        'args' => array(
            'auto_publish' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'description' => 'Auto publish articles after rewrite'
            ),
            'batch_size' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 5,
                'minimum' => 1,
                'maximum' => 5,
                'description' => 'Number of articles to process per batch'
            ),
            'max_articles' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 0,
                'minimum' => 0,
                'description' => 'Maximum number of articles to process (0 = no limit)'
            ),
            'min_words' => array(
                'required' => false,
                'type' => 'integer',
                'default' => 50,
                'minimum' => 1,
                'description' => 'Minimum word count for articles to be processed'
            ),
            'exclude_processed' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'description' => 'Exclude already processed articles'
            ),
            'process_images' => array(
                'required' => false,
                'type' => 'boolean',
                'default' => true,
                'description' => 'Process and replace images'
            ),
            'callback_url' => array(
                'required' => false,
                'type' => 'string',
                'format' => 'uri',
                'description' => 'Webhook URL for batch completion notifications'
            )
        )
    ));
    
    // Batch status endpoint
    register_rest_route($namespace, '/batch-status/(?P<batch_id>[a-zA-Z0-9_]+)', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'ai_rewriter_batch_status_callback',
        'permission_callback' => 'ai_rewriter_check_permissions',
        'args' => array(
            'batch_id' => array(
                'required' => true,
                'type' => 'string',
                'description' => 'Batch ID to check status for'
            )
        )
    ));
    
    error_log('AI Rewriter: Registered 3 enhanced API endpoints with full rewrite functionality');
}

//async process
// function ai_rewriter_force_register_endpoints() {
//     if (!get_option('ai_rewriter_api_enabled', 1)) {
//         return;
//     }
    
//     $namespace = 'ai-rewriter/v1';
    
//     // Status endpoint
//     register_rest_route($namespace, '/status', array(
//         'methods' => WP_REST_Server::READABLE,
//         'callback' => 'ai_rewriter_status_callback',
//         'permission_callback' => 'ai_rewriter_check_permissions'
//     ));
    
//     // PENTING: Gunakan ASYNC callback, bukan yang lama!
//     register_rest_route($namespace, '/bulk-rewrite-all', array(
//         'methods' => WP_REST_Server::CREATABLE,
//         'callback' => 'ai_rewriter_async_bulk_callback', // <-- INI YANG PENTING!
//         'permission_callback' => 'ai_rewriter_check_permissions',
//         'args' => array(
//             'auto_publish' => array(
//                 'required' => false,
//                 'type' => 'boolean',
//                 'default' => true,
//                 'description' => 'Auto publish articles after rewrite'
//             ),
//             'batch_size' => array(
//                 'required' => false,
//                 'type' => 'integer',
//                 'default' => 3,
//                 'minimum' => 1,
//                 'maximum' => 5,
//                 'description' => 'Articles processed per batch iteration'
//             ),
//             'max_articles' => array(
//                 'required' => false,
//                 'type' => 'integer',
//                 'default' => 0,
//                 'minimum' => 0,
//                 'description' => 'Maximum articles to process (0 = no limit)'
//             ),
//             'min_words' => array(
//                 'required' => false,
//                 'type' => 'integer',
//                 'default' => 50,
//                 'minimum' => 1,
//                 'description' => 'Minimum word count for processing'
//             ),
//             'exclude_processed' => array(
//                 'required' => false,
//                 'type' => 'boolean',
//                 'default' => true,
//                 'description' => 'Skip already processed articles'
//             ),
//             'process_images' => array(
//                 'required' => false,
//                 'type' => 'boolean',
//                 'default' => true,
//                 'description' => 'Process and replace images'
//             ),
//             'callback_url' => array(
//                 'required' => false,
//                 'type' => 'string',
//                 'format' => 'uri',
//                 'description' => 'Webhook URL for batch notifications'
//             )
//         )
//     ));
    
//     // Enhanced batch status endpoint
//     register_rest_route($namespace, '/batch-status/(?P<batch_id>[a-zA-Z0-9_]+)', array(
//         'methods' => WP_REST_Server::READABLE,
//         'callback' => 'ai_rewriter_enhanced_batch_status_callback',
//         'permission_callback' => 'ai_rewriter_check_permissions',
//         'args' => array(
//             'batch_id' => array(
//                 'required' => true,
//                 'type' => 'string',
//                 'description' => 'Batch ID to check status'
//             )
//         )
//     ));
    
//     // Batch management endpoints
//     register_rest_route($namespace, '/batch/(?P<batch_id>[a-zA-Z0-9_]+)/cancel', array(
//         'methods' => WP_REST_Server::CREATABLE,
//         'callback' => 'ai_rewriter_batch_cancel_callback',
//         'permission_callback' => 'ai_rewriter_check_permissions'
//     ));
    
//     register_rest_route($namespace, '/batches', array(
//         'methods' => WP_REST_Server::READABLE,
//         'callback' => 'ai_rewriter_list_batches_callback',
//         'permission_callback' => 'ai_rewriter_check_permissions',
//         'args' => array(
//             'status' => array('required' => false, 'type' => 'string', 'enum' => ['queued', 'processing', 'completed', 'failed', 'cancelled']),
//             'limit' => array('required' => false, 'type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 50)
//         )
//     ));
    
//     register_rest_route($namespace, '/batch/(?P<batch_id>[a-zA-Z0-9_]+)/results', array(
//         'methods' => WP_REST_Server::READABLE,
//         'callback' => 'ai_rewriter_batch_results_callback',
//         'permission_callback' => 'ai_rewriter_check_permissions',
//         'args' => array(
//             'page' => array('required' => false, 'type' => 'integer', 'default' => 1, 'minimum' => 1),
//             'per_page' => array('required' => false, 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100),
//             'result_status' => array('required' => false, 'type' => 'string', 'enum' => ['success', 'error'])
//         )
//     ));
    
//     register_rest_route($namespace, '/system-stats', array(
//         'methods' => WP_REST_Server::READABLE,
//         'callback' => 'ai_rewriter_system_stats_callback',
//         'permission_callback' => 'ai_rewriter_check_permissions'
//     ));
    
//     error_log('AI Rewriter: Registered 7 ASYNC API endpoints (NO MORE TIMEOUT!)');
// }

/**
 * Check API permissions
 */
function ai_rewriter_check_permissions($request) {
    // Jika tidak butuh autentikasi
    if (!get_option('ai_rewriter_api_require_auth', 1)) {
        return true;
    }
    
    // Cek API key dari header
    $api_key = $request->get_header('X-API-Key');
    if (empty($api_key)) {
        return new WP_Error(
            'missing_api_key',
            'API key required. Include X-API-Key header.',
            array('status' => 401)
        );
    }
    
    // Validasi API key
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

/**
 * Status endpoint callback
 */
function ai_rewriter_status_callback($request) {
    return new WP_REST_Response(array(
        'success' => true,
        'plugin_version' => defined('AI_REWRITER_VERSION') ? AI_REWRITER_VERSION : '2.0.0',
        'api_enabled' => get_option('ai_rewriter_api_enabled', 1),
        'openai_configured' => !empty(get_option('ai_rewriter_api_key', '')),
        'api_key_configured' => !empty(get_option('ai_rewriter_api_endpoint_key', '')),
        'auth_required' => get_option('ai_rewriter_api_require_auth', 1),
        'timestamp' => current_time('c'),
        'message' => 'AI Rewriter API is working properly!'
    ), 200);
}

/**
 * Bulk rewrite callback
 */
function ai_rewriter_bulk_callback($request) {
    // Ambil parameter
    $params = $request->get_params();
    $auto_publish = $params['auto_publish'] ?? true;
    $batch_size = $params['batch_size'] ?? 5;
    $max_articles = $params['max_articles'] ?? 0;
    $min_words = $params['min_words'] ?? 50;
    $exclude_processed = $params['exclude_processed'] ?? true;
    $process_images = $params['process_images'] ?? true;
    $callback_url = $params['callback_url'] ?? '';
    
    // Validasi OpenAI API key
    $openai_key = get_option('ai_rewriter_api_key', '');
    if (empty($openai_key)) {
        return new WP_Error(
            'missing_openai_key',
            'OpenAI API key tidak dikonfigurasi. Silakan konfigurasikan di pengaturan plugin.',
            array('status' => 400)
        );
    }
    
    // Cek apakah plugin instance tersedia
    if (!class_exists('AI_Article_Rewriter')) {
        return new WP_Error(
            'plugin_not_available',
            'AI Article Rewriter plugin tidak tersedia.',
            array('status' => 500)
        );
    }
    
    try {
        // Get plugin instance
        $plugin = AI_Article_Rewriter::get_instance();
        
        // Ambil draft posts dengan query yang lebih spesifik
        $query_args = array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'posts_per_page' => $max_articles > 0 ? $max_articles : 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids'
        );
        
        // Exclude processed posts jika diminta
        if ($exclude_processed) {
            $query_args['meta_query'] = array(
                array(
                    'key' => '_ai_rewriter_processed',
                    'compare' => 'NOT EXISTS'
                )
            );
        }
        
        $posts = get_posts($query_args);
        
        if (empty($posts)) {
            return new WP_Error(
                'no_drafts_found',
                'Tidak ada artikel draft yang ditemukan untuk diproses.',
                array('status' => 404)
            );
        }
        
        // Filter berdasarkan minimum words dan validasi
        $eligible_posts = array();
        foreach ($posts as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;
            
            $word_count = str_word_count(strip_tags($post->post_content));
            
            // Skip jika sudah diproses dan exclude_processed = true
            if ($exclude_processed && get_post_meta($post_id, '_ai_rewriter_processed', true)) {
                continue;
            }
            
            // Skip jika kurang dari minimum words
            if ($word_count < $min_words) {
                continue;
            }
            
            $eligible_posts[] = $post_id;
        }
        
        if (empty($eligible_posts)) {
            return new WP_Error(
                'no_eligible_articles',
                sprintf('Tidak ada artikel yang memenuhi kriteria (minimal %d kata, belum diproses).', $min_words),
                array('status' => 404)
            );
        }
        
        // Batasi jumlah artikel jika diminta
        if ($max_articles > 0 && count($eligible_posts) > $max_articles) {
            $eligible_posts = array_slice($eligible_posts, 0, $max_articles);
        }
        
        // Generate unique batch ID
        $batch_id = 'batch_' . uniqid() . '_' . time();
        
        // Setup batch information
        $batch_info = array(
            'batch_id' => $batch_id,
            'status' => 'processing',
            'start_time' => current_time('mysql'),
            'end_time' => null,
            'total_articles' => count($eligible_posts),
            'processed_count' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'articles' => $eligible_posts,
            'results' => array(),
            'settings' => array(
                'auto_publish' => $auto_publish,
                'batch_size' => min($batch_size, 5), // Maksimal 5 untuk menghindari timeout
                'min_words' => $min_words,
                'exclude_processed' => $exclude_processed,
                'process_images' => $process_images
            ),
            'callback_url' => $callback_url,
            'final_message' => '',
            'total_time' => ''
        );
        
        // Simpan batch info
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        
        // Proses artikel pertama batch langsung untuk memberikan feedback cepat
        $initial_batch = array_slice($eligible_posts, 0, min(2, $batch_size));
        $processed_results = array();
        
        foreach ($initial_batch as $post_id) {
            try {
                // Gunakan method process_single_article_api dari plugin
                $result = $plugin->process_single_article_api($post_id, $batch_info['settings']);
                
                $processed_results[] = array(
                    'post_id' => $post_id,
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'title' => $result['title'] ?? '',
                    'original_title' => $result['original_title'] ?? '',
                    'status' => $result['status'] ?? '',
                    'word_count' => $result['word_count'] ?? 0,
                    'cost' => $result['cost'] ?? 0,
                    'timestamp' => current_time('mysql')
                );
                
                if ($result['success']) {
                    $batch_info['success_count']++;
                } else {
                    $batch_info['error_count']++;
                }
                
                $batch_info['processed_count']++;
                
                // Add processing delay untuk menghindari rate limit
                sleep(2);
                
            } catch (Exception $e) {
                $processed_results[] = array(
                    'post_id' => $post_id,
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'timestamp' => current_time('mysql')
                );
                
                $batch_info['error_count']++;
                $batch_info['processed_count']++;
            }
        }
        
        // Update batch info dengan hasil pemrosesan awal
        $batch_info['results'] = $processed_results;
        update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        
        // Schedule pemrosesan sisanya jika masih ada
        if ($batch_info['processed_count'] < $batch_info['total_articles']) {
            wp_schedule_single_event(time() + 30, 'ai_rewriter_process_bulk_batch', array($batch_id));
        } else {
            // Jika semua sudah diproses (batch kecil), mark sebagai completed
            $batch_info['status'] = 'completed';
            $batch_info['end_time'] = current_time('mysql');
            update_option('ai_rewriter_batch_' . $batch_id, $batch_info);
        }
        
        // Send start webhook jika ada callback URL
        if (!empty($callback_url)) {
            ai_rewriter_send_webhook($callback_url, array(
                'event' => 'batch_started',
                'batch_id' => $batch_id,
                'total_articles' => $batch_info['total_articles'],
                'processed_immediately' => count($processed_results),
                'timestamp' => current_time('c')
            ));
        }
        
        // Response sukses dengan detail
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Bulk rewrite started successfully!',
            'batch_id' => $batch_id,
            'status' => $batch_info['status'],
            'total_articles' => $batch_info['total_articles'],
            'processed_immediately' => $batch_info['processed_count'],
            'success_count' => $batch_info['success_count'],
            'error_count' => $batch_info['error_count'],
            'remaining' => $batch_info['total_articles'] - $batch_info['processed_count'],
            'initial_results' => $processed_results,
            'settings' => $batch_info['settings'],
            'estimated_completion' => date('Y-m-d H:i:s', time() + (($batch_info['total_articles'] - $batch_info['processed_count']) * 35)), // 35 detik per artikel
            'status_check_url' => rest_url('ai-rewriter/v1/batch-status/' . $batch_id),
            'timestamp' => current_time('c')
        ), 200);
        
    } catch (Exception $e) {
        error_log('AI Rewriter Bulk API Error: ' . $e->getMessage());
        
        return new WP_Error(
            'processing_error',
            'Gagal memproses bulk rewrite: ' . $e->getMessage(),
            array('status' => 500)
        );
    }
}

/**
 * Endpoint untuk cek status batch
 */
function ai_rewriter_batch_status_callback($request) {
    $batch_id = $request->get_param('batch_id');
    
    if (empty($batch_id)) {
        return new WP_Error(
            'missing_batch_id',
            'Batch ID is required',
            array('status' => 400)
        );
    }
    
    $batch_info = get_option('ai_rewriter_batch_' . $batch_id);
    
    if (!$batch_info) {
        return new WP_Error(
            'batch_not_found',
            'Batch not found or expired',
            array('status' => 404)
        );
    }
    
    // Hitung progress percentage
    $progress_percentage = $batch_info['total_articles'] > 0 ? 
        round(($batch_info['processed_count'] / $batch_info['total_articles']) * 100, 1) : 0;
    
    return new WP_REST_Response(array(
        'success' => true,
        'batch_id' => $batch_id,
        'status' => $batch_info['status'],
        'progress_percentage' => $progress_percentage,
        'total_articles' => $batch_info['total_articles'],
        'processed_count' => $batch_info['processed_count'],
        'success_count' => $batch_info['success_count'],
        'error_count' => $batch_info['error_count'],
        'remaining' => $batch_info['total_articles'] - $batch_info['processed_count'],
        'start_time' => $batch_info['start_time'],
        'end_time' => $batch_info['end_time'],
        'final_message' => $batch_info['final_message'] ?? '',
        'total_time' => $batch_info['total_time'] ?? '',
        'recent_results' => array_slice($batch_info['results'], -5), // 5 hasil terakhir
        'timestamp' => current_time('c')
    ), 200);
}

// Hook endpoints registration dengan prioritas tinggi
add_action('rest_api_init', 'ai_rewriter_force_register_endpoints', 5);


/**
 * Initialize the plugin with API support
 */
function ai_rewriter_init() {
    if (!function_exists('add_action')) {
        return;
    }
    
    // Ensure WordPress REST API is available
    if (!class_exists('WP_REST_Controller')) {
        error_log('AI Rewriter: WordPress REST API not available');
        return;
    }
    
    AI_Article_Rewriter::get_instance();
}

// Hook into WordPress
add_action('plugins_loaded', 'ai_rewriter_init', 10);
add_action('rest_api_init', 'register_ai_rewriter_test_endpoint');

// Register background processing hooks
add_action('ai_rewriter_process_bulk_batch', 'ai_rewriter_process_bulk_batch');

// Also initialize immediately if WordPress is already loaded
if (did_action('plugins_loaded')) {
    ai_rewriter_init();
}

?>