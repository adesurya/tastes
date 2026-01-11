<?php
/**
 * WordPress Standard Cron Scheduler untuk AI Article Rewriter
 * Menggunakan best practices WordPress
 * 
 * @package AI_Article_Rewriter
 * @version 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Cron_Scheduler {
    
    private static $instance = null;
    private $cron_hook = 'ai_rewriter_process_drafts';
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Daftar custom intervals
        add_filter('cron_schedules', array($this, 'register_custom_intervals'));
        
        // Hook ke action cron
        add_action($this->cron_hook, array($this, 'execute_scheduled_task'));
        
        // Setup scheduling pada plugin activation
        add_action('admin_init', array($this, 'maybe_schedule_cron'), 10);
        
        // AJAX handlers untuk manual control
        add_action('wp_ajax_ai_rewriter_manual_run', array($this, 'ajax_manual_run'));
        add_action('wp_ajax_ai_rewriter_get_status', array($this, 'ajax_get_status'));
        add_action('wp_ajax_ai_rewriter_update_schedule', array($this, 'ajax_update_schedule'));
    }
    
    /**
     * Daftar interval custom ke WordPress
     * Filter ini HANYA dipanggil ketika WordPress memerlukan list schedules
     */
    public function register_custom_intervals($schedules) {
        // Hanya tambahkan jika belum ada
        if (!isset($schedules['ai_rewriter_5min'])) {
            $schedules['ai_rewriter_5min'] = array(
                'interval' => 5 * MINUTE_IN_SECONDS,
                'display'  => __('Every 5 Minutes', 'ai-rewriter')
            );
        }
        
        if (!isset($schedules['ai_rewriter_15min'])) {
            $schedules['ai_rewriter_15min'] = array(
                'interval' => 15 * MINUTE_IN_SECONDS,
                'display'  => __('Every 15 Minutes', 'ai-rewriter')
            );
        }
        
        if (!isset($schedules['ai_rewriter_30min'])) {
            $schedules['ai_rewriter_30min'] = array(
                'interval' => 30 * MINUTE_IN_SECONDS,
                'display'  => __('Every 30 Minutes', 'ai-rewriter')
            );
        }
        
        return $schedules;
    }
    
    /**
     * Setup atau update scheduling
     * Hanya dipanggil ketika settings berubah atau plugin baru diaktifkan
     */
    public function maybe_schedule_cron() {
        // Hanya proses jika auto-processing enabled
        $auto_enabled = get_option('ai_rewriter_auto_enabled', 0);
        
        if (!$auto_enabled) {
            // Jika disabled, clear schedule
            $this->unschedule_cron();
            return;
        }
        
        // Cek apakah sudah ada schedule yang valid
        $next_scheduled = wp_next_scheduled($this->cron_hook);
        
        // Jika sudah ada dan masih valid, tidak perlu reschedule
        if ($next_scheduled && $next_scheduled > time()) {
            return;
        }
        
        // Schedule baru
        $this->schedule_cron();
    }
    
    /**
     * Schedule cron event
     */
    public function schedule_cron() {
        // Clear schedule lama terlebih dahulu
        $this->unschedule_cron();
        
        // Dapatkan interval dari settings
        $interval = get_option('ai_rewriter_interval', 15);
        $schedule_name = 'ai_rewriter_' . $interval . 'min';
        
        // Validasi apakah interval tersedia
        $schedules = wp_get_schedules();
        if (!isset($schedules[$schedule_name])) {
            // Fallback ke hourly jika custom interval tidak tersedia
            $schedule_name = 'hourly';
            $this->log('Custom interval not available, using hourly fallback', 'warning');
        }
        
        // Schedule event (mulai dalam 1 menit)
        $timestamp = time() + MINUTE_IN_SECONDS;
        $scheduled = wp_schedule_event($timestamp, $schedule_name, $this->cron_hook);
        
        if ($scheduled !== false) {
            $this->log("Cron scheduled successfully with interval: {$schedule_name}");
            update_option('ai_rewriter_last_schedule_time', time());
            update_option('ai_rewriter_schedule_interval', $schedule_name);
            return true;
        } else {
            $this->log('Failed to schedule cron', 'error');
            return false;
        }
    }
    
    /**
     * Unschedule cron event
     */
    public function unschedule_cron() {
        $timestamp = wp_next_scheduled($this->cron_hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $this->cron_hook);
            $this->log('Cron unscheduled');
        }
    }
    
    /**
     * Execute scheduled task
     * Ini adalah fungsi yang dipanggil oleh WordPress cron
     */
    public function execute_scheduled_task() {
        $this->log('=== Cron Execution Started ===');
        
        // Validasi prerequisites
        if (!$this->validate_prerequisites()) {
            $this->log('Prerequisites validation failed', 'error');
            return;
        }
        
        // Jalankan processing
        $this->process_draft_posts();
        
        $this->log('=== Cron Execution Completed ===');
        
        // Update last run time
        update_option('ai_rewriter_last_cron_run', time());
    }
    
    /**
     * Validasi prerequisites sebelum processing
     */
    private function validate_prerequisites() {
        // Check jika auto processing enabled
        if (!get_option('ai_rewriter_auto_enabled', 0)) {
            $this->log('Auto processing is disabled');
            return false;
        }
        
        // Check API key
        $api_key = get_option('ai_rewriter_api_key', '');
        if (empty($api_key)) {
            $this->log('API key not configured', 'error');
            return false;
        }
        
        // Check required classes tersedia
        $required_classes = array(
            'AI_Rewriter_API',
            'AI_Rewriter_Content_Parser',
        );
        
        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                $this->log("Required class not found: {$class}", 'error');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Process draft posts
     */
    private function process_draft_posts() {
        global $wpdb;
        
        $batch_size = get_option('ai_rewriter_batch_size', 3);
        $min_words = get_option('ai_rewriter_min_words', 50);
        
        // Query draft posts yang belum diproses
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_content 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm 
                ON p.ID = pm.post_id 
                AND pm.meta_key = '_ai_rewriter_processed'
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
        
        $this->log("Found " . count($posts) . " posts to process");
        
        $processed = 0;
        $errors = 0;
        
        foreach ($posts as $post) {
            try {
                $result = $this->process_single_post($post);
                if ($result) {
                    $processed++;
                } else {
                    $errors++;
                }
                
                // Delay antara posts untuk menghindari rate limiting
                $delay = get_option('ai_rewriter_delay', 3);
                if ($delay > 0 && $processed < count($posts)) {
                    sleep($delay);
                }
                
            } catch (Exception $e) {
                $errors++;
                $this->log("Error processing post {$post->ID}: " . $e->getMessage(), 'error');
            }
        }
        
        $this->log("Processing completed: {$processed} successful, {$errors} errors");
        
        // Update statistics
        $this->update_statistics($processed, $errors);
    }
    
    /**
     * Process single post
     */
    private function process_single_post($post) {
        $this->log("Processing post ID: {$post->ID}");
        
        try {
            // Validasi word count
            $word_count = str_word_count(strip_tags($post->post_content));
            $min_words = get_option('ai_rewriter_min_words', 50);
            
            if ($word_count < $min_words) {
                $this->log("Post {$post->ID} too short ({$word_count} words)");
                update_post_meta($post->ID, '_ai_rewriter_processed', current_time('mysql'));
                update_post_meta($post->ID, '_ai_rewriter_skip_reason', 'too_short');
                return false;
            }
            
            // Initialize API
            $api = new AI_Rewriter_API();
            $api->set_config(array(
                'api_key' => get_option('ai_rewriter_api_key'),
                'model' => get_option('ai_rewriter_model', 'gpt-3.5-turbo'),
                'temperature' => floatval(get_option('ai_rewriter_temperature', 0.7)),
                'max_tokens' => intval(get_option('ai_rewriter_max_tokens', 2000))
            ));
            
            // Initialize Parser
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
            
            // Call API
            $this->log("Calling OpenAI API for post {$post->ID}");
            $result = $api->rewrite_content($prompt);
            
            if (empty($result['content'])) {
                throw new Exception('Empty API response');
            }
            
            // Parse hasil
            $parsed = $parser->parse_rewritten_content($result['content']);
            
            if (empty($parsed['content'])) {
                throw new Exception('Failed to parse API response');
            }
            
            // Update post
            $auto_publish = get_option('ai_rewriter_auto_publish', 0);
            $new_status = $auto_publish ? 'publish' : 'draft';
            
            $update_data = array(
                'ID' => $post->ID,
                'post_content' => $parser->format_for_wordpress($parsed['content']),
                'post_status' => $new_status
            );
            
            // Update title jika ada
            if (!empty($parsed['title'])) {
                $update_data['post_title'] = $parsed['title'];
            }
            
            $update_result = wp_update_post($update_data, true);
            
            if (is_wp_error($update_result)) {
                throw new Exception('Failed to update post: ' . $update_result->get_error_message());
            }
            
            // Mark sebagai processed
            update_post_meta($post->ID, '_ai_rewriter_processed', current_time('mysql'));
            update_post_meta($post->ID, '_ai_rewriter_method', 'ai_api');
            update_post_meta($post->ID, '_ai_rewriter_cost', floatval($result['cost'] ?? 0));
            update_post_meta($post->ID, '_ai_rewriter_status', $new_status);
            
            $this->log("Successfully processed post {$post->ID} (status: {$new_status})");
            return true;
            
        } catch (Exception $e) {
            $this->log("Error processing post {$post->ID}: " . $e->getMessage(), 'error');
            update_post_meta($post->ID, '_ai_rewriter_processed', current_time('mysql'));
            update_post_meta($post->ID, '_ai_rewriter_error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update statistics
     */
    private function update_statistics($processed, $errors) {
        $stats = get_option('ai_rewriter_statistics', array(
            'total_processed' => 0,
            'total_errors' => 0,
            'last_batch' => array()
        ));
        
        $stats['total_processed'] += $processed;
        $stats['total_errors'] += $errors;
        $stats['last_batch'] = array(
            'time' => current_time('mysql'),
            'processed' => $processed,
            'errors' => $errors
        );
        
        update_option('ai_rewriter_statistics', $stats);
    }
    
    /**
     * AJAX: Manual run
     */
    public function ajax_manual_run() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $this->log('Manual execution triggered via AJAX');
        $this->execute_scheduled_task();
        
        wp_send_json_success('Manual execution completed. Check logs for details.');
    }
    
    /**
     * AJAX: Get cron status
     */
    public function ajax_get_status() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $next_scheduled = wp_next_scheduled($this->cron_hook);
        
        $status = array(
            'is_scheduled' => $next_scheduled !== false,
            'next_run_timestamp' => $next_scheduled,
            'next_run_formatted' => $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : null,
            'time_until_next' => $next_scheduled ? human_time_diff(time(), $next_scheduled) : null,
            'current_time' => date('Y-m-d H:i:s'),
            'auto_enabled' => (bool) get_option('ai_rewriter_auto_enabled', 0),
            'schedule_interval' => get_option('ai_rewriter_schedule_interval', 'hourly'),
            'last_run' => get_option('ai_rewriter_last_cron_run', 0),
            'last_run_formatted' => get_option('ai_rewriter_last_cron_run', 0) 
                ? date('Y-m-d H:i:s', get_option('ai_rewriter_last_cron_run')) 
                : 'Never',
            'wp_cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON
        );
        
        wp_send_json_success($status);
    }
    
    /**
     * AJAX: Update schedule
     */
    public function ajax_update_schedule() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $interval = intval($_POST['interval'] ?? 15);
        
        // Update interval setting
        update_option('ai_rewriter_interval', $interval);
        
        // Reschedule
        $result = $this->schedule_cron();
        
        if ($result) {
            wp_send_json_success('Schedule updated successfully');
        } else {
            wp_send_json_error('Failed to update schedule');
        }
    }
    
    /**
     * Logging function
     */
    private function log($message, $level = 'info') {
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] [CRON] [{$level}] {$message}";
        
        // WordPress debug log
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log("AI Rewriter: {$message}");
        }
        
        // Custom log file
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/ai-rewriter-cron.log';
        
        // Tambahkan ke log file
        file_put_contents($log_file, $log_entry . "\n", FILE_APPEND | LOCK_EX);
        
        // Keep log file manageable (max 1MB)
        if (file_exists($log_file) && filesize($log_file) > 1048576) {
            $lines = file($log_file);
            $recent_lines = array_slice($lines, -500); // Keep last 500 lines
            file_put_contents($log_file, implode('', $recent_lines));
        }
    }
}

// Initialize cron scheduler
function ai_rewriter_init_cron() {
    return AI_Rewriter_Cron_Scheduler::get_instance();
}
add_action('plugins_loaded', 'ai_rewriter_init_cron');