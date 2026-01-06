<?php
/**
 * Cron Fix untuk AI Article Rewriter
 * Tambahkan kode ini ke plugin utama atau buat sebagai file terpisah
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Cron_Fix {
    
    public function __construct() {
        // Hook untuk memastikan cron berjalan
        add_action('init', array($this, 'fix_cron_scheduling'), 999);
        add_action('admin_init', array($this, 'check_and_repair_cron'));
        
        // Manual cron trigger untuk debugging
        add_action('wp_ajax_ai_rewriter_manual_cron', array($this, 'manual_cron_trigger'));
        add_action('wp_ajax_ai_rewriter_check_cron', array($this, 'check_cron_status'));
        add_action('wp_ajax_ai_rewriter_force_schedule', array($this, 'force_schedule_cron'));
        
        // Hook ke WordPress cron system
        add_action('wp', array($this, 'schedule_on_wp_load'));
    }
    
    /**
     * Fix cron scheduling issues
     */
    public function fix_cron_scheduling() {
        // Pastikan custom intervals terdaftar
        add_filter('cron_schedules', array($this, 'ensure_custom_intervals'));
        
        // Check if auto rewrite is enabled
        if (get_option('ai_rewriter_auto_enabled', 0)) {
            $this->ensure_cron_scheduled();
        }
    }
    
    /**
     * Ensure custom cron intervals are registered
     */
    public function ensure_custom_intervals($schedules) {
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
     * Ensure cron is scheduled
     */
    public function ensure_cron_scheduled() {
        $hook = 'ai_rewriter_process_drafts';
        
        // Clear any existing schedules first
        wp_clear_scheduled_hook($hook);
        
        // Get interval setting
        $interval = get_option('ai_rewriter_interval', 15);
        $schedule_name = 'ai_rewriter_' . $interval . '_min';
        
        // Try to schedule
        $scheduled = wp_schedule_event(time() + 60, $schedule_name, $hook);
        
        if ($scheduled === false) {
            // Fallback to WordPress default intervals
            $fallback_intervals = array(
                5 => 'five_minutes',
                15 => 'hourly', // WordPress doesn't have 15min by default
                30 => 'hourly',
                60 => 'hourly'
            );
            
            $fallback_schedule = isset($fallback_intervals[$interval]) ? $fallback_intervals[$interval] : 'hourly';
            
            // Try with fallback schedule
            $scheduled = wp_schedule_event(time() + 60, $fallback_schedule, $hook);
            
            if ($scheduled !== false) {
                error_log("AI Rewriter: Scheduled with fallback interval: {$fallback_schedule}");
                update_option('ai_rewriter_cron_fallback', $fallback_schedule);
            } else {
                error_log("AI Rewriter: Failed to schedule cron even with fallback");
                update_option('ai_rewriter_cron_error', 'Failed to schedule');
            }
        } else {
            error_log("AI Rewriter: Successfully scheduled cron with interval: {$schedule_name}");
            delete_option('ai_rewriter_cron_error');
            delete_option('ai_rewriter_cron_fallback');
        }
        
        // Log cron status
        $this->log_cron_status();
    }
    
    /**
     * Schedule on WordPress load
     */
    public function schedule_on_wp_load() {
        if (get_option('ai_rewriter_auto_enabled', 0)) {
            if (!wp_next_scheduled('ai_rewriter_process_drafts')) {
                $this->ensure_cron_scheduled();
            }
        }
    }
    
    /**
     * Check and repair cron on admin init
     */
    public function check_and_repair_cron() {
        // Only run in admin and not on every page load
        if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }
        
        // Check if we need to repair cron
        $last_check = get_option('ai_rewriter_last_cron_check', 0);
        $check_interval = 300; // Check every 5 minutes
        
        if ((time() - $last_check) > $check_interval) {
            update_option('ai_rewriter_last_cron_check', time());
            
            if (get_option('ai_rewriter_auto_enabled', 0)) {
                if (!wp_next_scheduled('ai_rewriter_process_drafts')) {
                    error_log("AI Rewriter: Cron not scheduled, attempting to repair...");
                    $this->ensure_cron_scheduled();
                }
            }
        }
    }
    
    /**
     * Log cron status for debugging
     */
    private function log_cron_status() {
        $next_scheduled = wp_next_scheduled('ai_rewriter_process_drafts');
        $cron_array = _get_cron_array();
        
        error_log("AI Rewriter Cron Status:");
        error_log("- Next scheduled: " . ($next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : 'NOT SCHEDULED'));
        error_log("- Current time: " . date('Y-m-d H:i:s'));
        error_log("- Auto enabled: " . (get_option('ai_rewriter_auto_enabled', 0) ? 'YES' : 'NO'));
        error_log("- WP Cron enabled: " . (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? 'NO' : 'YES'));
        
        // Check if our hook exists in cron array
        $found_in_cron = false;
        if (is_array($cron_array)) {
            foreach ($cron_array as $timestamp => $cron) {
                if (isset($cron['ai_rewriter_process_drafts'])) {
                    $found_in_cron = true;
                    error_log("- Found in cron array at timestamp: " . $timestamp . " (" . date('Y-m-d H:i:s', $timestamp) . ")");
                    break;
                }
            }
        }
        
        if (!$found_in_cron) {
            error_log("- NOT found in WordPress cron array");
        }
    }
    
    /**
     * AJAX: Manual cron trigger
     */
    public function manual_cron_trigger() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        error_log("AI Rewriter: Manual cron trigger initiated");
        
        // Directly call the processing function
        if (class_exists('AI_Article_Rewriter_Debug')) {
            $plugin = AI_Article_Rewriter_Debug::get_instance();
            $plugin->process_draft_posts();
            wp_send_json_success('Manual processing completed. Check logs for details.');
        } else {
            // Fallback: trigger the hook
            do_action('ai_rewriter_process_drafts');
            wp_send_json_success('Manual cron hook triggered.');
        }
    }
    
    /**
     * AJAX: Check cron status
     */
    public function check_cron_status() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $next_scheduled = wp_next_scheduled('ai_rewriter_process_drafts');
        $cron_array = _get_cron_array();
        $schedules = wp_get_schedules();
        
        // Check if our intervals are registered
        $our_intervals = array();
        foreach ($schedules as $key => $schedule) {
            if (strpos($key, 'ai_rewriter_') === 0) {
                $our_intervals[$key] = $schedule;
            }
        }
        
        // Check WordPress cron configuration
        $wp_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $alternate_cron = defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON;
        
        // Find our job in cron array
        $cron_job_details = null;
        if (is_array($cron_array)) {
            foreach ($cron_array as $timestamp => $cron) {
                if (isset($cron['ai_rewriter_process_drafts'])) {
                    $cron_job_details = array(
                        'timestamp' => $timestamp,
                        'datetime' => date('Y-m-d H:i:s', $timestamp),
                        'time_until' => human_time_diff(time(), $timestamp),
                        'details' => $cron['ai_rewriter_process_drafts']
                    );
                    break;
                }
            }
        }
        
        $status = array(
            'next_scheduled' => $next_scheduled,
            'next_scheduled_formatted' => $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : null,
            'time_until_next' => $next_scheduled ? human_time_diff(time(), $next_scheduled) : null,
            'wp_cron_disabled' => $wp_cron_disabled,
            'alternate_cron' => $alternate_cron,
            'our_intervals_registered' => count($our_intervals),
            'our_intervals' => $our_intervals,
            'cron_job_details' => $cron_job_details,
            'auto_enabled' => get_option('ai_rewriter_auto_enabled', 0),
            'last_error' => get_option('ai_rewriter_cron_error', ''),
            'fallback_used' => get_option('ai_rewriter_cron_fallback', ''),
            'current_time' => date('Y-m-d H:i:s'),
            'total_cron_jobs' => count($cron_array)
        );
        
        wp_send_json_success($status);
    }
    
    /**
     * AJAX: Force schedule cron
     */
    public function force_schedule_cron() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Clear existing schedules
        wp_clear_scheduled_hook('ai_rewriter_process_drafts');
        
        // Force enable auto rewrite if not enabled
        if (!get_option('ai_rewriter_auto_enabled', 0)) {
            update_option('ai_rewriter_auto_enabled', 1);
        }
        
        // Try multiple scheduling approaches
        $results = array();
        
        // Method 1: Using our custom interval
        $interval = get_option('ai_rewriter_interval', 15);
        $schedule_name = 'ai_rewriter_' . $interval . '_min';
        $result1 = wp_schedule_event(time() + 60, $schedule_name, 'ai_rewriter_process_drafts');
        $results['custom_interval'] = $result1 !== false;
        
        if ($result1 === false) {
            // Method 2: Using WordPress built-in intervals
            $builtin_schedules = array('hourly', 'daily');
            foreach ($builtin_schedules as $schedule) {
                $result = wp_schedule_event(time() + 60, $schedule, 'ai_rewriter_process_drafts');
                if ($result !== false) {
                    $results['builtin_' . $schedule] = true;
                    break;
                } else {
                    $results['builtin_' . $schedule] = false;
                }
            }
        }
        
        // Method 3: Single event as last resort
        if (!wp_next_scheduled('ai_rewriter_process_drafts')) {
            $single_event = wp_schedule_single_event(time() + 300, 'ai_rewriter_process_drafts'); // 5 minutes from now
            $results['single_event'] = $single_event !== false;
        }
        
        $final_status = wp_next_scheduled('ai_rewriter_process_drafts');
        
        wp_send_json_success(array(
            'message' => $final_status ? 'Cron successfully scheduled!' : 'Failed to schedule cron',
            'scheduled' => $final_status !== false,
            'next_run' => $final_status ? date('Y-m-d H:i:s', $final_status) : null,
            'attempts' => $results
        ));
    }
}

// Initialize the cron fix
new AI_Rewriter_Cron_Fix();

/**
 * Add debugging functions to main plugin class
 * Tambahkan method ini ke class AI_Article_Rewriter_Debug
 */

// Method untuk ditambahkan ke class utama:
public function add_cron_debug_methods() {
    // Add AJAX handlers untuk cron debugging
    add_action('wp_ajax_ai_rewriter_manual_cron', array($this, 'ajax_manual_cron'));
    add_action('wp_ajax_ai_rewriter_check_cron', array($this, 'ajax_check_cron'));
    add_action('wp_ajax_ai_rewriter_force_schedule', array($this, 'ajax_force_schedule'));
}

public function ajax_manual_cron() {
    check_ajax_referer('ai_rewriter_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $this->log('Manual cron execution requested');
    
    // Execute processing directly
    $this->process_draft_posts();
    
    wp_send_json_success('Manual processing completed! Check logs for details.');
}

public function ajax_check_cron() {
    check_ajax_referer('ai_rewriter_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $cron_fix = new AI_Rewriter_Cron_Fix();
    $cron_fix->check_cron_status();
}

public function ajax_force_schedule() {
    check_ajax_referer('ai_rewriter_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $cron_fix = new AI_Rewriter_Cron_Fix();
    $cron_fix->force_schedule_cron();
}

/**
 * Enhanced admin page dengan cron debugging
 * Tambahkan HTML ini ke admin page
 */
?>

<!-- Tambahkan section ini ke admin page -->
<div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
    <h2 style="margin-top: 0;">🔧 Cron Debugging & Repair</h2>
    <div style="margin-bottom: 15px;">
        <button type="button" id="check-cron-status" class="button">🔍 Check Cron Status</button>
        <button type="button" id="force-schedule-cron" class="button button-primary">🚀 Force Schedule Cron</button>
        <button type="button" id="manual-cron-trigger" class="button button-secondary">⚡ Manual Trigger</button>
    </div>
    <div id="cron-debug-display" style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;"></div>
</div>

<script>
// Tambahkan JavaScript ini ke admin page
jQuery(document).ready(function($) {
    
    // Check Cron Status
    $('#check-cron-status').click(function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('🔄 Checking...');
        
        $.ajax({
            url: aiRewriter.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_rewriter_check_cron',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<h4>🔍 Cron Status Details:</h4>';
                    html += '<p><strong>Next Scheduled:</strong> ' + (data.next_scheduled_formatted || 'NOT SCHEDULED') + '</p>';
                    html += '<p><strong>Time Until Next:</strong> ' + (data.time_until_next || 'N/A') + '</p>';
                    html += '<p><strong>WordPress Cron Disabled:</strong> ' + (data.wp_cron_disabled ? 'YES ⚠️' : 'NO ✅') + '</p>';
                    html += '<p><strong>Auto Processing Enabled:</strong> ' + (data.auto_enabled ? 'YES ✅' : 'NO ❌') + '</p>';
                    html += '<p><strong>Custom Intervals Registered:</strong> ' + data.our_intervals_registered + '</p>';
                    html += '<p><strong>Total Cron Jobs:</strong> ' + data.total_cron_jobs + '</p>';
                    
                    if (data.last_error) {
                        html += '<p><strong>Last Error:</strong> <span style="color: red;">' + data.last_error + '</span></p>';
                    }
                    
                    if (data.fallback_used) {
                        html += '<p><strong>Fallback Schedule Used:</strong> <span style="color: orange;">' + data.fallback_used + '</span></p>';
                    }
                    
                    if (data.cron_job_details) {
                        html += '<h5>📋 Cron Job Details:</h5>';
                        html += '<p>- Timestamp: ' + data.cron_job_details.timestamp + '</p>';
                        html += '<p>- Scheduled for: ' + data.cron_job_details.datetime + '</p>';
                        html += '<p>- Time until execution: ' + data.cron_job_details.time_until + '</p>';
                    }
                    
                    $('#cron-debug-display').html(html);
                } else {
                    $('#cron-debug-display').html('<p style="color: red;">Error checking cron status: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#cron-debug-display').html('<p style="color: red;">Connection error checking cron status</p>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('🔍 Check Cron Status');
            }
        });
    });
    
    // Force Schedule Cron
    $('#force-schedule-cron').click(function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('🔄 Scheduling...');
        
        $.ajax({
            url: aiRewriter.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_rewriter_force_schedule',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<h4>🚀 Force Schedule Result:</h4>';
                    html += '<p><strong>Status:</strong> ' + (data.scheduled ? '✅ SUCCESS' : '❌ FAILED') + '</p>';
                    html += '<p><strong>Message:</strong> ' + data.message + '</p>';
                    
                    if (data.next_run) {
                        html += '<p><strong>Next Run:</strong> ' + data.next_run + '</p>';
                    }
                    
                    html += '<h5>📋 Scheduling Attempts:</h5>';
                    for (var method in data.attempts) {
                        html += '<p>- ' + method + ': ' + (data.attempts[method] ? '✅' : '❌') + '</p>';
                    }
                    
                    $('#cron-debug-display').html(html);
                    
                    if (data.scheduled) {
                        // Refresh status after successful scheduling
                        setTimeout(function() {
                            $('#check-status').click();
                        }, 2000);
                    }
                } else {
                    $('#cron-debug-display').html('<p style="color: red;">Error scheduling cron: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#cron-debug-display').html('<p style="color: red;">Connection error scheduling cron</p>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('🚀 Force Schedule Cron');
            }
        });
    });
    
    // Manual Cron Trigger
    $('#manual-cron-trigger').click(function() {
        if (!confirm('This will manually trigger the processing function. Continue?')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('⚡ Triggering...');
        
        $.ajax({
            url: aiRewriter.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_rewriter_manual_cron',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#cron-debug-display').html('<p style="color: green;">✅ ' + response.data + '</p>');
                    
                    // Refresh status and check logs
                    setTimeout(function() {
                        $('#check-status').click();
                    }, 2000);
                } else {
                    $('#cron-debug-display').html('<p style="color: red;">❌ Error: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#cron-debug-display').html('<p style="color: red;">❌ Connection error during manual trigger</p>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('⚡ Manual Trigger');
            }
        });
    });
    
    // Auto-check cron status on page load
    setTimeout(function() {
        $('#check-cron-status').click();
    }, 2000);
});
</script>

<?php
/**
 * Alternative: Simple manual scheduling function
 * Jalankan ini di WordPress admin atau tambahkan ke functions.php sementara
 */
function ai_rewriter_emergency_schedule() {
    // Clear any existing schedules
    wp_clear_scheduled_hook('ai_rewriter_process_drafts');
    
    // Force enable auto processing
    update_option('ai_rewriter_auto_enabled', 1);
    
    // Try scheduling with built-in WordPress interval
    $scheduled = wp_schedule_event(time() + 300, 'hourly', 'ai_rewriter_process_drafts');
    
    if ($scheduled !== false) {
        echo "✅ Emergency scheduling successful!<br>";
        echo "Next run: " . date('Y-m-d H:i:s', wp_next_scheduled('ai_rewriter_process_drafts'));
    } else {
        echo "❌ Emergency scheduling failed!<br>";
        echo "Check WordPress cron configuration.";
    }
}

// Uncomment line berikut untuk menjalankan emergency scheduling:
// add_action('admin_init', 'ai_rewriter_emergency_schedule');
?>