<?php
/**
 * Manual Cron Scheduler untuk AI Article Rewriter
 * Fix timezone issues dan manual scheduling
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Rewriter_Manual_Scheduler {
    
    public function __construct() {
        // AJAX handlers untuk manual scheduling
        add_action('wp_ajax_ai_rewriter_schedule_custom', array($this, 'ajax_schedule_custom'));
        add_action('wp_ajax_ai_rewriter_reschedule_now', array($this, 'ajax_reschedule_now'));
        add_action('wp_ajax_ai_rewriter_timezone_info', array($this, 'ajax_timezone_info'));
        add_action('wp_ajax_ai_rewriter_clear_schedule', array($this, 'ajax_clear_schedule'));
    }
    
    /**
     * Get timezone information
     */
    public function get_timezone_info() {
        $wp_timezone = get_option('timezone_string');
        $gmt_offset = get_option('gmt_offset');
        
        // If no timezone string, try to determine from GMT offset
        if (empty($wp_timezone)) {
            $wp_timezone = timezone_name_from_abbr('', $gmt_offset * 3600, 0);
            if ($wp_timezone === false) {
                $wp_timezone = 'UTC' . ($gmt_offset >= 0 ? '+' : '') . $gmt_offset;
            }
        }
        
        $server_timezone = date_default_timezone_get();
        $current_wp_time = current_time('mysql');
        $current_gmt_time = current_time('mysql', true);
        $current_server_time = date('Y-m-d H:i:s');
        
        return array(
            'wp_timezone' => $wp_timezone,
            'server_timezone' => $server_timezone,
            'gmt_offset' => $gmt_offset,
            'current_wp_time' => $current_wp_time,
            'current_gmt_time' => $current_gmt_time,
            'current_server_time' => $current_server_time,
            'wp_timestamp' => current_time('timestamp'),
            'gmt_timestamp' => current_time('timestamp', true),
            'server_timestamp' => time()
        );
    }
    
    /**
     * AJAX: Get timezone info
     */
    public function ajax_timezone_info() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $timezone_info = $this->get_timezone_info();
        
        // Add next scheduled info
        $next_scheduled = wp_next_scheduled('ai_rewriter_process_drafts');
        $timezone_info['next_scheduled_timestamp'] = $next_scheduled;
        $timezone_info['next_scheduled_wp_time'] = $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled + (get_option('gmt_offset') * 3600)) : null;
        $timezone_info['next_scheduled_server_time'] = $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : null;
        
        // Check if scheduled time is in the past
        $timezone_info['is_past_due'] = $next_scheduled && $next_scheduled < time();
        $timezone_info['time_diff_seconds'] = $next_scheduled ? ($next_scheduled - time()) : null;
        
        wp_send_json_success($timezone_info);
    }
    
    /**
     * AJAX: Schedule custom time
     */
    public function ajax_schedule_custom() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $schedule_type = sanitize_text_field($_POST['schedule_type'] ?? '');
        $custom_time = sanitize_text_field($_POST['custom_time'] ?? '');
        $minutes_from_now = intval($_POST['minutes_from_now'] ?? 0);
        
        // Clear existing schedule
        wp_clear_scheduled_hook('ai_rewriter_process_drafts');
        
        $schedule_time = 0;
        $message = '';
        
        switch ($schedule_type) {
            case 'now':
                $schedule_time = time() + 30; // 30 seconds from now
                $message = 'Scheduled to run in 30 seconds';
                break;
                
            case 'minutes':
                if ($minutes_from_now > 0 && $minutes_from_now <= 1440) { // Max 24 hours
                    $schedule_time = time() + ($minutes_from_now * 60);
                    $message = "Scheduled to run in {$minutes_from_now} minutes";
                } else {
                    wp_send_json_error('Invalid minutes value (1-1440)');
                    return;
                }
                break;
                
            case 'specific':
                if (!empty($custom_time)) {
                    // Parse custom time (format: YYYY-MM-DD HH:MM)
                    $parsed_time = strtotime($custom_time);
                    if ($parsed_time === false || $parsed_time < time()) {
                        wp_send_json_error('Invalid time or time in the past');
                        return;
                    }
                    
                    // Adjust for WordPress timezone
                    $gmt_offset = get_option('gmt_offset') * 3600;
                    $schedule_time = $parsed_time - $gmt_offset;
                    $message = "Scheduled for {$custom_time}";
                } else {
                    wp_send_json_error('Custom time is required');
                    return;
                }
                break;
                
            case 'recurring':
                $interval = sanitize_text_field($_POST['interval'] ?? '15');
                $schedule_name = 'ai_rewriter_' . $interval . '_min';
                
                // Check if interval exists
                $schedules = wp_get_schedules();
                if (!isset($schedules[$schedule_name])) {
                    // Fallback to hourly
                    $schedule_name = 'hourly';
                }
                
                $schedule_time = time() + 60; // Start in 1 minute
                $result = wp_schedule_event($schedule_time, $schedule_name, 'ai_rewriter_process_drafts');
                
                if ($result !== false) {
                    wp_send_json_success(array(
                        'message' => "Recurring schedule set ({$interval} min interval)",
                        'next_run' => date('Y-m-d H:i:s', wp_next_scheduled('ai_rewriter_process_drafts')),
                        'type' => 'recurring'
                    ));
                } else {
                    wp_send_json_error('Failed to schedule recurring event');
                }
                return;
                
            default:
                wp_send_json_error('Invalid schedule type');
                return;
        }
        
        // Schedule single event
        if ($schedule_time > 0) {
            $result = wp_schedule_single_event($schedule_time, 'ai_rewriter_process_drafts');
            
            if ($result !== false) {
                $next_scheduled = wp_next_scheduled('ai_rewriter_process_drafts');
                wp_send_json_success(array(
                    'message' => $message,
                    'next_run' => date('Y-m-d H:i:s', $next_scheduled),
                    'next_run_local' => date('Y-m-d H:i:s', $next_scheduled + (get_option('gmt_offset') * 3600)),
                    'timestamp' => $next_scheduled,
                    'type' => 'single'
                ));
            } else {
                wp_send_json_error('Failed to schedule event');
            }
        } else {
            wp_send_json_error('Invalid schedule time');
        }
    }
    
    /**
     * AJAX: Reschedule to run now (fix timezone issues)
     */
    public function ajax_reschedule_now() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        // Clear existing schedule
        wp_clear_scheduled_hook('ai_rewriter_process_drafts');
        
        // Schedule to run in 30 seconds with proper timezone handling
        $schedule_time = current_time('timestamp', true) + 30; // GMT time + 30 seconds
        
        $result = wp_schedule_single_event($schedule_time, 'ai_rewriter_process_drafts');
        
        if ($result !== false) {
            $next_scheduled = wp_next_scheduled('ai_rewriter_process_drafts');
            wp_send_json_success(array(
                'message' => 'Rescheduled to run in 30 seconds (timezone corrected)',
                'next_run_gmt' => date('Y-m-d H:i:s', $next_scheduled),
                'next_run_local' => current_time('mysql'),
                'timestamp' => $next_scheduled
            ));
        } else {
            wp_send_json_error('Failed to reschedule');
        }
    }
    
    /**
     * AJAX: Clear schedule
     */
    public function ajax_clear_schedule() {
        check_ajax_referer('ai_rewriter_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $cleared = wp_clear_scheduled_hook('ai_rewriter_process_drafts');
        
        if ($cleared !== false) {
            wp_send_json_success('Schedule cleared successfully');
        } else {
            wp_send_json_error('Failed to clear schedule');
        }
    }
}

// Initialize manual scheduler
new AI_Rewriter_Manual_Scheduler();

/**
 * HTML untuk Manual Scheduler Interface
 * Tambahkan ini ke admin page
 */
?>

<!-- Manual Cron Scheduler Interface -->
<div style="background: white; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px; border-left: 4px solid #ff9800;">
    <h2 style="margin-top: 0;">⏰ Manual Cron Scheduler</h2>
    
    <!-- Timezone Info -->
    <div id="timezone-info" style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <h4>🌍 Timezone Information</h4>
        <button type="button" id="check-timezone" class="button">🔄 Check Timezone</button>
        <div id="timezone-display" style="margin-top: 10px; font-family: monospace; font-size: 12px;"></div>
    </div>
    
    <!-- Quick Actions -->
    <div style="margin-bottom: 20px;">
        <h4>⚡ Quick Actions</h4>
        <button type="button" id="schedule-now" class="button button-primary">🚀 Run in 30 Seconds</button>
        <button type="button" id="clear-schedule" class="button button-secondary">🗑️ Clear Schedule</button>
        <button type="button" id="fix-timezone" class="button">🔧 Fix Timezone Issues</button>
    </div>
    
    <!-- Custom Scheduling -->
    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 4px;">
        <h4>📅 Custom Scheduling</h4>
        
        <div style="margin-bottom: 15px;">
            <label>
                <input type="radio" name="schedule_type" value="minutes" checked> 
                Run in 
                <input type="number" id="minutes-input" value="5" min="1" max="1440" style="width: 60px;"> 
                minutes
            </label>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label>
                <input type="radio" name="schedule_type" value="specific"> 
                Run at specific time: 
                <input type="datetime-local" id="custom-time" style="margin-left: 10px;">
            </label>
        </div>
        
        <div style="margin-bottom: 15px;">
            <label>
                <input type="radio" name="schedule_type" value="recurring"> 
                Recurring every 
                <select id="recurring-interval">
                    <option value="5">5 minutes</option>
                    <option value="15" selected>15 minutes</option>
                    <option value="30">30 minutes</option>
                    <option value="60">1 hour</option>
                </select>
            </label>
        </div>
        
        <button type="button" id="schedule-custom" class="button button-primary">📅 Schedule</button>
    </div>
    
    <div id="scheduler-result" style="margin-top: 15px;"></div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Set default datetime to current time + 1 hour
    var now = new Date();
    now.setHours(now.getHours() + 1);
    $('#custom-time').val(now.toISOString().slice(0, 16));
    
    // Check Timezone Info
    $('#check-timezone').click(function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('🔄 Checking...');
        
        $.ajax({
            url: aiRewriter.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_rewriter_timezone_info',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px;">';
                    html += '<div><strong>WordPress Timezone:</strong> ' + data.wp_timezone + '</div>';
                    html += '<div><strong>Server Timezone:</strong> ' + data.server_timezone + '</div>';
                    html += '<div><strong>GMT Offset:</strong> ' + data.gmt_offset + ' hours</div>';
                    html += '<div><strong>WordPress Time:</strong> ' + data.current_wp_time + '</div>';
                    html += '<div><strong>Server Time:</strong> ' + data.current_server_time + '</div>';
                    html += '<div><strong>GMT Time:</strong> ' + data.current_gmt_time + '</div>';
                    html += '</div>';
                    
                    if (data.next_scheduled_timestamp) {
                        html += '<div style="margin-top: 10px; padding: 10px; border-radius: 4px; ' + 
                               (data.is_past_due ? 'background: #ffebee; color: #c62828;' : 'background: #e8f5e8; color: #2e7d32;') + '">';
                        html += '<strong>Next Scheduled:</strong><br>';
                        html += '- Server Time: ' + data.next_scheduled_server_time + '<br>';
                        html += '- WordPress Time: ' + data.next_scheduled_wp_time + '<br>';
                        html += '- Status: ' + (data.is_past_due ? '⚠️ PAST DUE (' + Math.abs(data.time_diff_seconds) + 's ago)' : '✅ Scheduled (' + data.time_diff_seconds + 's from now)');
                        html += '</div>';
                    }
                    
                    $('#timezone-display').html(html);
                } else {
                    $('#timezone-display').html('<p style="color: red;">Error: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#timezone-display').html('<p style="color: red;">Connection error</p>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('🔄 Check Timezone');
            }
        });
    });
    
    // Schedule Now (30 seconds)
    $('#schedule-now').click(function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('🔄 Scheduling...');
        
        $.ajax({
            url: aiRewriter.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_rewriter_reschedule_now',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    $('#scheduler-result').html(
                        '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px;">' +
                        '<strong>✅ ' + data.message + '</strong><br>' +
                        'Next run: ' + data.next_run_local + ' (local time)' +
                        '</div>'
                    );
                    
                    // Auto-refresh status
                    setTimeout(function() {
                        $('#check-status').click();
                    }, 2000);
                } else {
                    $('#scheduler-result').html(
                        '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;">' +
                        '❌ Error: ' + response.data +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#scheduler-result').html(
                    '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;">' +
                    '❌ Connection error' +
                    '</div>'
                );
            },
            complete: function() {
                $btn.prop('disabled', false).text('🚀 Run in 30 Seconds');
            }
        });
    });
    
    // Clear Schedule
    $('#clear-schedule').click(function() {
        if (!confirm('Are you sure you want to clear the current schedule?')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('🔄 Clearing...');
        
        $.ajax({
            url: aiRewriter.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_rewriter_clear_schedule',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#scheduler-result').html(
                        '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px;">' +
                        '✅ ' + response.data +
                        '</div>'
                    );
                    
                    setTimeout(function() {
                        $('#check-status').click();
                    }, 1000);
                } else {
                    $('#scheduler-result').html(
                        '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;">' +
                        '❌ Error: ' + response.data +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#scheduler-result').html(
                    '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;">' +
                    '❌ Connection error' +
                    '</div>'
                );
            },
            complete: function() {
                $btn.prop('disabled', false).text('🗑️ Clear Schedule');
            }
        });
    });
    
    // Fix Timezone Issues
    $('#fix-timezone').click(function() {
        $('#schedule-now').click(); // Just reschedule to now
    });
    
    // Custom Scheduling
    $('#schedule-custom').click(function() {
        var scheduleType = $('input[name="schedule_type"]:checked').val();
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('📅 Scheduling...');
        
        var data = {
            action: 'ai_rewriter_schedule_custom',
            nonce: aiRewriter.nonce,
            schedule_type: scheduleType
        };
        
        if (scheduleType === 'minutes') {
            data.minutes_from_now = $('#minutes-input').val();
        } else if (scheduleType === 'specific') {
            data.custom_time = $('#custom-time').val();
        } else if (scheduleType === 'recurring') {
            data.interval = $('#recurring-interval').val();
        }
        
        $.ajax({
            url: aiRewriter.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    var result = response.data;
                    $('#scheduler-result').html(
                        '<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px;">' +
                        '<strong>✅ ' + result.message + '</strong><br>' +
                        'Next run: ' + result.next_run + 
                        (result.next_run_local ? '<br>Local time: ' + result.next_run_local : '') +
                        '<br>Type: ' + result.type +
                        '</div>'
                    );
                    
                    setTimeout(function() {
                        $('#check-status').click();
                    }, 2000);
                } else {
                    $('#scheduler-result').html(
                        '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;">' +
                        '❌ Error: ' + response.data +
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#scheduler-result').html(
                    '<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;">' +
                    '❌ Connection error' +
                    '</div>'
                );
            },
            complete: function() {
                $btn.prop('disabled', false).text('📅 Schedule');
            }
        });
    });
    
    // Auto-check timezone on page load
    setTimeout(function() {
        $('#check-timezone').click();
    }, 1000);
});
</script>

<?php
/**
 * Quick fix function - tambahkan ke functions.php jika diperlukan
 */
function ai_rewriter_quick_timezone_fix() {
    if (current_user_can('manage_options') && isset($_GET['ai_rewriter_fix_timezone'])) {
        // Clear existing schedule
        wp_clear_scheduled_hook('ai_rewriter_process_drafts');
        
        // Schedule with proper timezone handling
        $schedule_time = current_time('timestamp', true) + 300; // 5 minutes from now in GMT
        $result = wp_schedule_single_event($schedule_time, 'ai_rewriter_process_drafts');
        
        if ($result !== false) {
            wp_die('✅ Cron rescheduled successfully! Next run: ' . date('Y-m-d H:i:s', wp_next_scheduled('ai_rewriter_process_drafts')));
        } else {
            wp_die('❌ Failed to reschedule cron');
        }
    }
}
add_action('admin_init', 'ai_rewriter_quick_timezone_fix');

// Quick fix URL: yoursite.com/wp-admin/?ai_rewriter_fix_timezone=1
?>