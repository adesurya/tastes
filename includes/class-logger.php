<?php
/**
 * Logger for AI Rewriter
 * Handles activity logging and database operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Logger {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ai_rewriter_logs';
    }
    
    public function log_rewrite($post_id, $status, $message, $original_title = '', $new_title = '', $cost = 0) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'post_id' => $post_id,
                'action' => 'rewrite',
                'status' => $status,
                'message' => $message,
                'original_title' => $original_title,
                'new_title' => $new_title,
                'api_cost' => $cost,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%f', '%s')
        );
        
        $this->log_activity($message, $status);
    }
    
    public function log_activity($message, $type = 'info') {
        error_log('[AI Rewriter] ' . strtoupper($type) . ': ' . $message);
    }
    
    public function get_formatted_logs($limit = 10) {
        global $wpdb;
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
        
        $formatted = array();
        foreach ($logs as $log) {
            $icon = $log->status === 'success' ? '✅' : ($log->status === 'error' ? '❌' : '⏳');
            
            $formatted[] = array(
                'icon' => $icon,
                'message' => $log->message,
                'time' => human_time_diff(strtotime($log->created_at), current_time('timestamp')) . ' ago',
                'status' => $log->status
            );
        }
        
        return $formatted;
    }
    
    public function clear_all_logs() {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}") !== false;
    }
    
    public function get_statistics() {
        global $wpdb;
        
        return array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}"),
            'success' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'success'"),
            'errors' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'error'"),
            'total_cost' => $wpdb->get_var("SELECT SUM(api_cost) FROM {$this->table_name}")
        );
    }
}