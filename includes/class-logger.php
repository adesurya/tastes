<?php
/**
 * Logger Class - Handles all logging activities
 * File: includes/class-logger.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Logger {
    
    private $table_logs;
    private $max_logs;
    private $log_levels;
    
    public function __construct() {
        global $wpdb;
        $this->table_logs = $wpdb->prefix . 'ai_rewriter_logs';
        $this->max_logs = 1000; // Maximum logs to keep
        
        $this->log_levels = array(
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
            'success' => 4
        );
    }
    
    /**
     * Log rewriting activity
     */
    public function log_rewrite($post_id, $status, $message, $original_title = '', $new_title = '', $cost = 0.0) {
        global $wpdb;
        
        $original_word_count = 0;
        $new_word_count = 0;
        
        // Get word counts if titles are provided
        if (!empty($original_title)) {
            $post = get_post($post_id);
            if ($post) {
                $original_word_count = str_word_count(strip_tags($post->post_content));
            }
        }
        
        if (!empty($new_title)) {
            $post = get_post($post_id);
            if ($post) {
                $new_word_count = str_word_count(strip_tags($post->post_content));
            }
        }
        
        $result = $wpdb->insert(
            $this->table_logs,
            array(
                'post_id' => $post_id,
                'action' => 'rewrite',
                'status' => $status,
                'message' => $message,
                'original_title' => $original_title,
                'new_title' => $new_title,
                'original_word_count' => $original_word_count,
                'new_word_count' => $new_word_count,
                'api_cost' => $cost,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s')
        );
        
        if ($result === false) {
            error_log('AI Rewriter: Failed to insert log entry - ' . $wpdb->last_error);
        }
        
        // Clean up old logs
        $this->cleanup_old_logs();
        
        return $result;
    }
    
    /**
     * Log general activity
     */
    public function log_activity($message, $level = 'info', $post_id = 0) {
        global $wpdb;
        
        // Validate log level
        if (!isset($this->log_levels[$level])) {
            $level = 'info';
        }
        
        $result = $wpdb->insert(
            $this->table_logs,
            array(
                'post_id' => $post_id,
                'action' => 'system',
                'status' => $level,
                'message' => $message,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        // Also log to WordPress debug log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AI Rewriter: [{$level}] {$message}");
        }
        
        return $result;
    }
    
    /**
     * Log API test
     */
    public function log_api_test($success, $message, $key_length = 0) {
        $status = $success ? 'success' : 'error';
        $log_message = "API test requested. Key from DB length: {$key_length}, Result: {$message}";
        
        return $this->log_activity($log_message, $status);
    }
    
    /**
     * Log bulk operation
     */
    public function log_bulk_operation($action, $post_count, $success_count, $error_count, $duration = 0) {
        $message = "Bulk {$action}: {$post_count} posts processed, {$success_count} successful, {$error_count} failed";
        if ($duration > 0) {
            $message .= " in {$duration}s";
        }
        
        $level = $error_count === 0 ? 'success' : ($success_count > 0 ? 'warning' : 'error');
        
        return $this->log_activity($message, $level);
    }
    
    /**
     * Log settings change
     */
    public function log_settings_change($setting_name, $old_value, $new_value) {
        // Don't log sensitive information like API keys
        if (strpos($setting_name, 'api_key') !== false || strpos($setting_name, 'key') !== false) {
            $old_value = !empty($old_value) ? '[HIDDEN]' : '[EMPTY]';
            $new_value = !empty($new_value) ? '[HIDDEN]' : '[EMPTY]';
        }
        
        $message = "Setting changed: {$setting_name} from '{$old_value}' to '{$new_value}'";
        return $this->log_activity($message, 'info');
    }
    
    /**
     * Log image processing
     */
    public function log_image_processing($post_id, $images_added, $source, $keywords_used) {
        $message = "Images processed for post {$post_id}: {$images_added} images added from {$source}";
        if (!empty($keywords_used)) {
            $message .= " using keywords: " . implode(', ', array_slice($keywords_used, 0, 3));
        }
        
        return $this->log_activity($message, 'info', $post_id);
    }
    
    /**
     * Log error with context
     */
    public function log_error($message, $context = array(), $post_id = 0) {
        $error_message = $message;
        
        if (!empty($context)) {
            $context_str = json_encode($context);
            $error_message .= " Context: {$context_str}";
        }
        
        return $this->log_activity($error_message, 'error', $post_id);
    }
    
    /**
     * Get recent logs
     */
    public function get_recent_logs($limit = 50, $level = null, $post_id = null) {
        global $wpdb;
        
        $where_conditions = array();
        $values = array();
        
        if (!is_null($level)) {
            $where_conditions[] = 'status = %s';
            $values[] = $level;
        }
        
        if (!is_null($post_id) && $post_id > 0) {
            $where_conditions[] = 'post_id = %d';
            $values[] = $post_id;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $values[] = intval($limit);
        
        $query = "SELECT * FROM {$this->table_logs} {$where_clause} ORDER BY created_at DESC LIMIT %d";
        
        if (!empty($values)) {
            $prepared_query = $wpdb->prepare($query, $values);
        } else {
            $prepared_query = $wpdb->prepare($query, $limit);
        }
        
        return $wpdb->get_results($prepared_query);
    }
    
    /**
     * Get logs for specific post
     */
    public function get_post_logs($post_id, $limit = 20) {
        return $this->get_recent_logs($limit, null, $post_id);
    }
    
    /**
     * Get logs by date range
     */
    public function get_logs_by_date($start_date, $end_date, $limit = 100) {
        global $wpdb;
        
        $query = "SELECT * FROM {$this->table_logs} 
                  WHERE created_at BETWEEN %s AND %s 
                  ORDER BY created_at DESC 
                  LIMIT %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $start_date, $end_date, $limit)
        );
    }
    
    /**
     * Get log statistics
     */
    public function get_log_stats($days = 7) {
        global $wpdb;
        
        $start_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Get counts by status
        $status_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count 
             FROM {$this->table_logs} 
             WHERE created_at >= %s 
             GROUP BY status",
            $start_date
        ));
        
        // Get counts by action
        $action_counts = $wpdb->get_results($wpdb->prepare(
            "SELECT action, COUNT(*) as count 
             FROM {$this->table_logs} 
             WHERE created_at >= %s 
             GROUP BY action",
            $start_date
        ));
        
        // Get total cost
        $total_cost = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(api_cost) 
             FROM {$this->table_logs} 
             WHERE created_at >= %s AND api_cost > 0",
            $start_date
        ));
        
        // Get processing stats
        $processing_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_operations,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_operations,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_operations,
                AVG(CASE WHEN new_word_count > 0 THEN new_word_count ELSE NULL END) as avg_word_count
             FROM {$this->table_logs} 
             WHERE created_at >= %s AND action = 'rewrite'",
            $start_date
        ));
        
        return array(
            'period_days' => $days,
            'status_counts' => $status_counts,
            'action_counts' => $action_counts,
            'total_cost' => floatval($total_cost),
            'processing_stats' => $processing_stats
        );
    }
    
    /**
     * Export logs to CSV
     */
    public function export_logs_csv($start_date = null, $end_date = null) {
        global $wpdb;
        
        $where_clause = '';
        $values = array();
        
        if ($start_date && $end_date) {
            $where_clause = 'WHERE created_at BETWEEN %s AND %s';
            $values = array($start_date, $end_date);
        }
        
        $query = "SELECT * FROM {$this->table_logs} {$where_clause} ORDER BY created_at DESC";
        
        if (!empty($values)) {
            $logs = $wpdb->get_results($wpdb->prepare($query, $values));
        } else {
            $logs = $wpdb->get_results($query);
        }
        
        $csv_data = array();
        $csv_data[] = array(
            'ID', 'Post ID', 'Action', 'Status', 'Message', 'Original Title', 'New Title',
            'Original Word Count', 'New Word Count', 'API Cost', 'Created At'
        );
        
        foreach ($logs as $log) {
            $csv_data[] = array(
                $log->id,
                $log->post_id,
                $log->action,
                $log->status,
                $log->message,
                $log->original_title,
                $log->new_title,
                $log->original_word_count,
                $log->new_word_count,
                $log->api_cost,
                $log->created_at
            );
        }
        
        return $csv_data;
    }
    
    /**
     * Clear old logs
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        // Keep only the most recent logs
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_logs} 
             WHERE id NOT IN (
                 SELECT id FROM (
                     SELECT id FROM {$this->table_logs} 
                     ORDER BY created_at DESC 
                     LIMIT %d
                 ) as recent_logs
             )",
            $this->max_logs
        ));
    }
    
    /**
     * Clear all logs
     */
    public function clear_all_logs() {
        global $wpdb;
        
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_logs}");
        
        if ($result !== false) {
            $this->log_activity('All logs cleared by user', 'info');
        }
        
        return $result !== false;
    }
    
    /**
     * Delete logs older than specified days
     */
    public function delete_old_logs($days = 30) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_logs} WHERE created_at < %s",
            $cutoff_date
        ));
        
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_logs} WHERE created_at < %s",
            $cutoff_date
        ));
        
        if ($result !== false) {
            $this->log_activity("Deleted {$deleted_count} logs older than {$days} days", 'info');
        }
        
        return $result !== false;
    }
    
    /**
     * Get log level priority
     */
    private function get_log_priority($level) {
        return isset($this->log_levels[$level]) ? $this->log_levels[$level] : 1;
    }
    
    /**
     * Format log message for display
     */
    public function format_log_message($log) {
        $icons = array(
            'debug' => '🔧',
            'info' => 'ℹ️',
            'warning' => '⚠️',
            'error' => '❌',
            'success' => '✅'
        );
        
        $icon = isset($icons[$log->status]) ? $icons[$log->status] : 'ℹ️';
        $time = date('H:i:s', strtotime($log->created_at));
        
        return array(
            'icon' => $icon,
            'time' => $time,
            'message' => $log->message,
            'level' => $log->status,
            'post_id' => $log->post_id
        );
    }
    
    /**
     * Get logs formatted for admin display
     */
    public function get_formatted_logs($limit = 20) {
        $logs = $this->get_recent_logs($limit);
        $formatted_logs = array();
        
        foreach ($logs as $log) {
            $formatted_logs[] = $this->format_log_message($log);
        }
        
        return $formatted_logs;
    }
    
    /**
     * Search logs
     */
    public function search_logs($search_term, $limit = 50) {
        global $wpdb;
        
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        $query = "SELECT * FROM {$this->table_logs} 
                  WHERE message LIKE %s 
                     OR original_title LIKE %s 
                     OR new_title LIKE %s 
                  ORDER BY created_at DESC 
                  LIMIT %d";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, $search_term, $search_term, $search_term, $limit)
        );
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics($hours = 24) {
        global $wpdb;
        
        $start_time = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $metrics = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_operations,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_operations,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_operations,
                AVG(CASE WHEN new_word_count > 0 AND original_word_count > 0 
                    THEN (new_word_count - original_word_count) ELSE NULL END) as avg_word_change,
                SUM(api_cost) as total_cost,
                MIN(created_at) as first_operation,
                MAX(created_at) as last_operation
             FROM {$this->table_logs} 
             WHERE created_at >= %s AND action = 'rewrite'",
            $start_time
        ));
        
        if ($metrics) {
            // Calculate processing rate
            if ($metrics->first_operation && $metrics->last_operation) {
                $time_span = strtotime($metrics->last_operation) - strtotime($metrics->first_operation);
                $metrics->operations_per_hour = $time_span > 0 ? 
                    ($metrics->total_operations / ($time_span / 3600)) : 0;
            } else {
                $metrics->operations_per_hour = 0;
            }
            
            // Calculate success rate
            $metrics->success_rate = $metrics->total_operations > 0 ? 
                ($metrics->successful_operations / $metrics->total_operations) * 100 : 0;
        }
        
        return $metrics;
    }
    
    /**
     * Log system information
     */
    public function log_system_info() {
        $info = array(
            'WordPress Version' => get_bloginfo('version'),
            'PHP Version' => PHP_VERSION,
            'Memory Limit' => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time'),
            'Plugin Version' => AI_REWRITER_VERSION,
            'Current Time' => current_time('mysql'),
            'Timezone' => get_option('timezone_string')
        );
        
        $message = 'System Info: ' . json_encode($info);
        return $this->log_activity($message, 'debug');
    }
    
    /**
     * Log queue status
     */
    public function log_queue_status() {
        global $wpdb;
        
        $queue_table = $wpdb->prefix . 'ai_rewriter_queue';
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_items,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_items,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_items,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_items
             FROM {$queue_table}"
        );
        
        if ($stats) {
            $message = "Queue Status: {$stats->total_items} total, {$stats->pending_items} pending, " .
                      "{$stats->processing_items} processing, {$stats->completed_items} completed, " .
                      "{$stats->failed_items} failed";
            
            return $this->log_activity($message, 'info');
        }
        
        return false;
    }
    
    /**
     * Set maximum logs to keep
     */
    public function set_max_logs($max_logs) {
        $this->max_logs = intval($max_logs);
    }
    
    /**
     * Get current log count
     */
    public function get_log_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_logs}");
    }
    
    /**
     * Archive old logs (move to separate table or file)
     */
    public function archive_old_logs($days = 90) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Get logs to archive
        $logs_to_archive = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_logs} WHERE created_at < %s",
            $cutoff_date
        ));
        
        if (empty($logs_to_archive)) {
            return 0;
        }
        
        // Create archive file
        $upload_dir = wp_upload_dir();
        $archive_dir = $upload_dir['basedir'] . '/ai-rewriter-logs/';
        
        if (!file_exists($archive_dir)) {
            wp_mkdir_p($archive_dir);
        }
        
        $archive_file = $archive_dir . 'archived-logs-' . date('Y-m-d') . '.json';
        
        // Save to file
        $archive_data = array(
            'archived_at' => current_time('mysql'),
            'logs_count' => count($logs_to_archive),
            'logs' => $logs_to_archive
        );
        
        $result = file_put_contents($archive_file, json_encode($archive_data, JSON_PRETTY_PRINT));
        
        if ($result !== false) {
            // Delete archived logs from database
            $deleted_count = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table_logs} WHERE created_at < %s",
                $cutoff_date
            ));
            
            $this->log_activity("Archived {$deleted_count} logs to {$archive_file}", 'info');
            
            return $deleted_count;
        }
        
        return 0;
    }
}