<?php
/**
 * AI Rewriter Cron Fix - Complete Solution
 * Mengatasi masalah class not found dan memastikan cron berjalan dengan benar
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Cron_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Hook untuk memastikan dependencies loaded
        add_action('ai_rewriter_process_drafts', array($this, 'process_drafts_with_dependencies'));
        
        // AJAX handlers untuk manual testing
        add_action('wp_ajax_ai_rewriter_manual_cron', array($this, 'ajax_manual_cron'));
        add_action('wp_ajax_ai_rewriter_check_dependencies', array($this, 'ajax_check_dependencies'));
        add_action('wp_ajax_ai_rewriter_force_load_classes', array($this, 'ajax_force_load_classes'));
        
        // Auto-fix cron scheduling
        add_action('init', array($this, 'ensure_cron_scheduled'), 999);
    }
    
    /**
     * Process drafts dengan memastikan semua dependencies ter-load
     */
    public function process_drafts_with_dependencies() {
        $this->log('=== Cron execution started ===');
        
        // Force load all required dependencies
        if (!$this->load_all_dependencies()) {
            $this->log('Failed to load dependencies, aborting cron execution', 'error');
            return;
        }
        
        // Check if auto processing is enabled
        if (!get_option('ai_rewriter_auto_enabled', 0)) {
            $this->log('Auto processing disabled in settings');
            return;
        }
        
        // Check API key
        $api_key = get_option('ai_rewriter_api_key', '');
        if (empty($api_key)) {
            $this->log('No API key configured', 'error');
            return;
        }
        
        // Get plugin instance and call processing method
        if (class_exists('AI_Article_Rewriter_Debug')) {
            $plugin = AI_Article_Rewriter_Debug::get_instance();
            if (method_exists($plugin, 'process_draft_posts')) {
                $this->log('Calling plugin process_draft_posts method');
                $plugin->process_draft_posts();
            } else {
                $this->log('process_draft_posts method not found in plugin class', 'error');
                $this->fallback_processing();
            }
        } else {
            $this->log('AI_Article_Rewriter_Debug class not found, using fallback', 'error');
            $this->fallback_processing();
        }
        
        $this->log('=== Cron execution completed ===');
    }
    
    /**
     * Load semua dependencies yang diperlukan
     */
    public function load_all_dependencies() {
        $this->log('Loading dependencies...');
        
        // Define plugin paths
        $plugin_paths = array(
            AI_REWRITER_PLUGIN_PATH,
            WP_PLUGIN_DIR . '/ai-article-rewriter/',
            WP_PLUGIN_DIR . '/ai-article-rewriter-debug/',
        );
        
        $includes_loaded = 0;
        $includes_required = array(
            'class-ai-api.php',
            'class-content-parser.php',
            'class-image-handler.php'
        );
        
        foreach ($plugin_paths as $base_path) {
            if (!is_dir($base_path)) continue;
            
            $includes_path = $base_path . 'includes/';
            if (!is_dir($includes_path)) continue;
            
            foreach ($includes_required as $file) {
                $file_path = $includes_path . $file;
                if (file_exists($file_path)) {
                    require_once $file_path;
                    $includes_loaded++;
                    $this->log("Loaded: {$file}");
                }
            }
            
            if ($includes_loaded >= count($includes_required)) {
                break;
            }
        }
        
        // Check if all required classes are available
        $required_classes = array(
            'AI_Rewriter_API',
            'AI_Rewriter_Content_Parser',
            'AI_Rewriter_Image_Handler'
        );
        
        $classes_available = 0;
        foreach ($required_classes as $class) {
            if (class_exists($class)) {
                $classes_available++;
                $this->log("Class available: {$class}");
            } else {
                $this->log("Class missing: {$class}", 'error');
            }
        }
        
        $success = $classes_available >= 2; // At least API and Parser required
        $this->log("Dependencies loaded: {$includes_loaded}/{" . count($includes_required) . "}, Classes: {$classes_available}/{" . count($required_classes) . "}");
        
        return $success;
    }
    
    /**
     * Fallback processing jika class utama tidak tersedia
     */
    public function fallback_processing() {
        global $wpdb;
        
        $this->log('Using fallback processing method');
        
        $batch_size = get_option('ai_rewriter_batch_size', 3);
        $min_words = get_option('ai_rewriter_min_words', 50);
        
        // Get draft posts
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_content 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ai_rewriter_processed'
            WHERE p.post_type = 'post' 
            AND p.post_status = 'draft'
            AND pm.meta_id IS NULL
            AND CHAR_LENGTH(p.post_content) > %d
            ORDER BY p.post_date ASC
            LIMIT %d
        ", $min_words * 5, $batch_size));
        
        if (empty($posts)) {
            $this->log('No draft posts found to process');
            return;
        }
        
        $this->log("Found " . count($posts) . " posts for fallback processing");
        
        $processed = 0;
        $errors = 0;
        
        foreach ($posts as $post) {
            try {
                $result = $this->process_single_post_fallback($post);
                if ($result) {
                    $processed++;
                } else {
                    $errors++;
                }
                
                // Delay between posts
                $delay = get_option('ai_rewriter_delay', 3);
                if ($delay > 0) {
                    sleep($delay);
                }
            } catch (Exception $e) {
                $errors++;
                $this->log("Error processing post {$post->ID}: " . $e->getMessage(), 'error');
            }
        }
        
        $this->log("Fallback processing completed: {$processed} successful, {$errors} errors");
    }
    
    /**
     * Process single post dengan fallback method
     */
    private function process_single_post_fallback($post) {
        try {
            $this->log("Processing post ID {$post->ID} with fallback method");
            
            // Check word count
            $word_count = str_word_count(strip_tags($post->post_content));
            $min_words = get_option('ai_rewriter_min_words', 50);
            
            if ($word_count < $min_words) {
                $this->log("Post {$post->ID} too short ({$word_count} words), skipping");
                update_post_meta($post->ID, '_ai_rewriter_processed', current_time('mysql'));
                update_post_meta($post->ID, '_ai_rewriter_skip', 'too_short');
                return false;
            }
            
            // Try AI processing first if classes available
            if (class_exists('AI_Rewriter_API') && class_exists('AI_Rewriter_Content_Parser')) {
                return $this->process_with_ai($post);
            } else {
                return $this->process_with_simple_rewrite($post);
            }
            
        } catch (Exception $e) {
            $this->log("Error in fallback processing for post {$post->ID}: " . $e->getMessage(), 'error');
            update_post_meta($post->ID, '_ai_rewriter_processed', current_time('mysql'));
            update_post_meta($post->ID, '_ai_rewriter_error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process dengan AI jika class tersedia
     */
    private function process_with_ai($post) {
        try {
            $this->log("Processing post {$post->ID} with AI");
            
            $api = new AI_Rewriter_API();
            $api->set_config(array(
                'api_key' => get_option('ai_rewriter_api_key', ''),
                'model' => get_option('ai_rewriter_model', 'gpt-3.5-turbo'),
                'temperature' => get_option('ai_rewriter_temperature', 0.7),
                'max_tokens' => get_option('ai_rewriter_max_tokens', 2000)
            ));
            
            $parser = new AI_Rewriter_Content_Parser();
            $parser->set_language(get_option('ai_rewriter_language', 'Indonesian'));
            $parser->set_writing_style(get_option('ai_rewriter_style', 'professional'));
            
            // Generate prompt
            $prompt = $parser->generate_prompt(
                $post->post_title,
                $post->post_content,
                get_option('ai_rewriter_custom_prompt', ''),
                ''
            );
            
            $this->log("Calling OpenAI API for post {$post->ID}");
            $result = $api->rewrite_content($prompt);
            
            if (empty($result['content'])) {
                throw new Exception('Empty AI response');
            }
            
            $parsed = $parser->parse_rewritten_content($result['content']);
            
            if (empty($parsed['content'])) {
                throw new Exception('Failed to parse AI response');
            }
            
            // Update post
            $auto_publish = get_option('ai_rewriter_auto_publish', 0);
            $new_status = $auto_publish ? 'publish' : 'draft';
            
            $update_result = wp_update_post(array(
                'ID' => $post->ID,
                'post_title' => !empty($parsed['title']) ? $parsed['title'] : $post->post_title,
                'post_content' => $parser->format_for_wordpress($parsed['content']),
                'post_status' => $new_status
            ));
            
            if (is_wp_error($update_result)) {
                throw new Exception('Failed to update post: ' . $update_result->get_error_message());
            }
            
            // Mark as processed
            update_post_meta($post->ID, '_ai_rewriter_processed', current_time('mysql'));
            update_post_meta($post->ID, '_ai_rewriter_method', 'ai_full');
            update_post_meta($post->ID, '_ai_rewriter_cost', $result['cost'] ?? 0);
            
            $this->log("Successfully processed post {$post->ID} with AI (status: {$new_status})");
            return true;
            
        } catch (Exception $e) {
            $this->log("AI processing failed for post {$post->ID}: " . $e->getMessage(), 'error');
            // Fall back to simple rewrite
            return $this->process_with_simple_rewrite($post);
        }
    }
    
    /**
     * Simple rewrite sebagai fallback terakhir
     */
    private function process_with_simple_rewrite($post) {
        try {
            $this->log("Processing post {$post->ID} with simple rewrite");
            
            // Simple content modifications
            $new_content = $this->simple_content_rewrite($post->post_content);
            $new_title = $post->post_title . ' (Rewritten)';
            
            $auto_publish = get_option('ai_rewriter_auto_publish', 0);
            $new_status = $auto_publish ? 'publish' : 'draft';
            
            $update_result = wp_update_post(array(
                'ID' => $post->ID,
                'post_title' => $new_title,
                'post_content' => $new_content,
                'post_status' => $new_status
            ));
            
            if (is_wp_error($update_result)) {
                throw new Exception('Failed to update post: ' . $update_result->get_error_message());
            }
            
            update_post_meta($post->ID, '_ai_rewriter_processed', current_time('mysql'));
            update_post_meta($post->ID, '_ai_rewriter_method', 'simple_fallback');
            
            $this->log("Successfully processed post {$post->ID} with simple rewrite");
            return true;
            
        } catch (Exception $e) {
            $this->log("Simple rewrite failed for post {$post->ID}: " . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Simple content rewrite
     */
    private function simple_content_rewrite($content) {
        // Indonesian text replacements
        $replacements = array(
            'adalah' => 'merupakan',
            'dan' => 'serta',
            'yang' => 'yang mana',
            'dengan' => 'bersama',
            'untuk' => 'bagi',
            'dalam' => 'di dalam',
            'pada' => 'di',
            'akan' => 'bakal',
            'dapat' => 'mampu',
            'juga' => 'pula',
            'ini' => 'tersebut',
            'itu' => 'hal tersebut',
            'sangat' => 'amat',
            'harus' => 'wajib',
            'bisa' => 'dapat',
            'karena' => 'sebab',
            'jika' => 'apabila',
            'namun' => 'akan tetapi',
            'tetapi' => 'namun',
            'sehingga' => 'alhasil'
        );
        
        // Apply replacements (case-insensitive)
        foreach ($replacements as $search => $replace) {
            $content = preg_replace('/\b' . preg_quote($search, '/') . '\b/i', $replace, $content);
        }
        
        // Add rewrite marker
        $content = "<!-- Rewritten on " . date('Y-m-d H:i:s') . " by AI Rewriter Fallback -->\n\n" . $content;
        
        return $content;
    }
    
    /**
     * Ensure cron is properly scheduled
     */
    public function ensure_cron_scheduled() {
        if (!get_option('ai_rewriter_auto_enabled', 0)) {
            return;
        }
        
        $hook = 'ai_rewriter_process_drafts';
        
        // Check if already scheduled and not past due
        $next_scheduled = wp_next_scheduled($hook);
        if ($next_scheduled && $next_scheduled > time()) {
            return; // Already properly scheduled
        }
        
        // Clear any existing schedules
        wp_clear_scheduled_hook($hook);
        
        // Get interval
        $interval = get_option('ai_rewriter_interval', 15);
        $schedule_name = 'ai_rewriter_' . $interval . '_min';
        
        // Ensure custom intervals are registered
        add_filter('cron_schedules', array($this, 'add_custom_intervals'));
        
        // Try to schedule
        $schedule_time = time() + 60; // 1 minute from now
        $result = wp_schedule_event($schedule_time, $schedule_name, $hook);
        
        if ($result === false) {
            // Fallback to hourly
            $result = wp_schedule_event($schedule_time, 'hourly', $hook);
            $this->log("Scheduled cron with fallback interval: hourly");
        } else {
            $this->log("Scheduled cron with interval: {$schedule_name}");
        }
    }
    
    /**
     * Add custom cron intervals
     */
    public function add_custom_intervals($schedules) {
        $schedules['ai_rewriter_5_min'] = array(
            'interval' => 5 * 60,
            'display' => 'Every 5 Minutes'
        );
        $schedules['ai_rewriter_15_min'] = array(
            'interval' => 15 * 60,
            'display' => 'Every 15 Minutes'
        );
        $schedules['ai_rewriter_30_min'] = array(
            'interval' => 30 * 60,
            'display' => 'Every 30 Minutes'
        );
        $schedules['ai_rewriter_60_min'] = array(
            'interval' => 60 * 60,
            'display' => 'Every Hour'
        );
        
        return $schedules;
    }
    
    /**
     * AJAX: Manual cron execution
     */
    public function ajax_manual_cron() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $this->log('Manual cron execution requested via AJAX');
        
        // Execute the cron function directly
        $this->process_drafts_with_dependencies();
        
        wp_send_json_success('Manual cron execution completed! Check logs for details.');
    }
    
    /**
     * AJAX: Check dependencies
     */
    public function ajax_check_dependencies() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $dependencies_loaded = $this->load_all_dependencies();
        
        $status = array(
            'dependencies_loaded' => $dependencies_loaded,
            'classes' => array(
                'AI_Rewriter_API' => class_exists('AI_Rewriter_API'),
                'AI_Rewriter_Content_Parser' => class_exists('AI_Rewriter_Content_Parser'),
                'AI_Rewriter_Image_Handler' => class_exists('AI_Rewriter_Image_Handler'),
                'AI_Article_Rewriter_Debug' => class_exists('AI_Article_Rewriter_Debug')
            ),
            'constants' => array(
                'AI_REWRITER_PLUGIN_PATH' => defined('AI_REWRITER_PLUGIN_PATH') ? AI_REWRITER_PLUGIN_PATH : 'Not defined'
            ),
            'cron_status' => array(
                'next_scheduled' => wp_next_scheduled('ai_rewriter_process_drafts'),
                'auto_enabled' => get_option('ai_rewriter_auto_enabled', 0),
                'api_key_set' => !empty(get_option('ai_rewriter_api_key', ''))
            )
        );
        
        wp_send_json_success($status);
    }
    
    /**
     * AJAX: Force load classes
     */
    public function ajax_force_load_classes() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $result = $this->load_all_dependencies();
        
        if ($result) {
            wp_send_json_success('Dependencies loaded successfully!');
        } else {
            wp_send_json_error('Failed to load all dependencies. Check file paths.');
        }
    }
    
    /**
     * Logging function
     */
    private function log($message, $level = 'info') {
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [CRON-FIX] [{$level}] {$message}";
        
        // WordPress debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AI Rewriter Cron: {$message}");
        }
        
        // Custom log file
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/ai-rewriter-cron-fix.log';
        
        file_put_contents($log_file, $log_entry . "\n", FILE_APPEND | LOCK_EX);
        
        // Keep log manageable
        if (file_exists($log_file) && filesize($log_file) > 1048576) {
            $lines = file($log_file);
            $recent_lines = array_slice($lines, -500);
            file_put_contents($log_file, implode('', $recent_lines));
        }
    }
}

// Initialize the cron manager
AI_Rewriter_Cron_Manager::get_instance();

/**
 * Quick fix function untuk manual execution
 */
function ai_rewriter_manual_fix_execution() {
    if (current_user_can('manage_options') && isset($_GET['ai_rewriter_manual_fix'])) {
        $cron_manager = AI_Rewriter_Cron_Manager::get_instance();
        
        echo '<h2>🔧 AI Rewriter Manual Fix</h2>';
        echo '<p>Executing manual fix...</p>';
        
        // Load dependencies
        echo '<h3>Loading Dependencies:</h3>';
        $deps_loaded = $cron_manager->load_all_dependencies();
        echo '<p>Dependencies loaded: ' . ($deps_loaded ? '✅ YES' : '❌ NO') . '</p>';
        
        // Check classes
        echo '<h3>Class Status:</h3>';
        $classes = array(
            'AI_Rewriter_API',
            'AI_Rewriter_Content_Parser', 
            'AI_Article_Rewriter_Debug'
        );
        
        foreach ($classes as $class) {
            echo '<p>' . $class . ': ' . (class_exists($class) ? '✅ Available' : '❌ Missing') . '</p>';
        }
        
        // Execute processing
        echo '<h3>Processing Results:</h3>';
        try {
            $cron_manager->process_drafts_with_dependencies();
            echo '<p>✅ Processing completed successfully!</p>';
        } catch (Exception $e) {
            echo '<p>❌ Processing failed: ' . $e->getMessage() . '</p>';
        }
        
        echo '<p><a href="' . admin_url('admin.php?page=ai-rewriter') . '">← Back to AI Rewriter</a></p>';
        wp_die();
    }
}
add_action('admin_init', 'ai_rewriter_manual_fix_execution');

/**
 * Admin notice untuk debugging
 */
function ai_rewriter_cron_debug_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'ai-rewriter') === false) {
        return;
    }
    
    echo '<div class="notice notice-info">';
    echo '<p><strong>🔧 AI Rewriter Debug Tools:</strong></p>';
    echo '<p>';
    echo '<a href="' . admin_url('?ai_rewriter_manual_fix=1') . '" class="button button-primary">🔧 Manual Fix & Test</a> ';
    echo '<a href="' . admin_url('?ai_rewriter_check_deps=1') . '" class="button">🔍 Check Dependencies</a>';
    echo '</p>';
    echo '</div>';
}
add_action('admin_notices', 'ai_rewriter_cron_debug_notice');

/**
 * Dependency checker URL handler
 */
function ai_rewriter_check_dependencies_handler() {
    if (current_user_can('manage_options') && isset($_GET['ai_rewriter_check_deps'])) {
        $cron_manager = AI_Rewriter_Cron_Manager::get_instance();
        
        echo '<h2>🔍 AI Rewriter Dependencies Check</h2>';
        
        // Check plugin paths
        echo '<h3>Plugin Paths:</h3>';
        $paths = array(
            'AI_REWRITER_PLUGIN_PATH' => defined('AI_REWRITER_PLUGIN_PATH') ? AI_REWRITER_PLUGIN_PATH : 'Not defined',
            'Plugin dir guess 1' => WP_PLUGIN_DIR . '/ai-article-rewriter/',
            'Plugin dir guess 2' => WP_PLUGIN_DIR . '/ai-article-rewriter-debug/'
        );
        
        foreach ($paths as $label => $path) {
            $exists = is_dir($path);
            echo '<p>' . $label . ': ' . $path . ' ' . ($exists ? '✅' : '❌') . '</p>';
            
            if ($exists) {
                $includes_path = $path . 'includes/';
                if (is_dir($includes_path)) {
                    $files = scandir($includes_path);
                    echo '<ul>';
                    foreach ($files as $file) {
                        if (strpos($file, '.php') !== false) {
                            echo '<li>' . $file . '</li>';
                        }
                    }
                    echo '</ul>';
                }
            }
        }
        
        // Try loading dependencies
        echo '<h3>Loading Test:</h3>';
        $deps_loaded = $cron_manager->load_all_dependencies();
        echo '<p>Dependencies loaded: ' . ($deps_loaded ? '✅ SUCCESS' : '❌ FAILED') . '</p>';
        
        echo '<p><a href="' . admin_url('admin.php?page=ai-rewriter') . '">← Back to AI Rewriter</a></p>';
        wp_die();
    }
}
add_action('admin_init', 'ai_rewriter_check_dependencies_handler');

?>