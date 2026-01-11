<?php
/**
 * AI Rewriter Cron Debugger
 * 
 * Upload ke: wp-content/plugins/adesurya-tastes/debug-ai-rewriter-cron.php
 * Akses via: http://yoursite.com/wp-content/plugins/adesurya-tastes/debug-ai-rewriter-cron.php
 * 
 * HAPUS FILE INI SETELAH DEBUGGING SELESAI!
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

// Auto-refresh setiap 30 detik jika diminta
$auto_refresh = isset($_GET['refresh']) ? intval($_GET['refresh']) : 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Rewriter Cron Debugger</title>
    <?php if ($auto_refresh > 0): ?>
    <meta http-equiv="refresh" content="<?php echo $auto_refresh; ?>">
    <?php endif; ?>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            margin: 20px; 
            background: #f0f0f1;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { 
            color: #1d2327; 
            border-bottom: 3px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 { 
            color: #2271b1; 
            margin-top: 30px;
            border-left: 4px solid #2271b1;
            padding-left: 15px;
        }
        .status-box { 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 6px;
            border-left: 4px solid;
        }
        .success { 
            background: #d7f0d7; 
            border-color: #00a32a;
            color: #00500d;
        }
        .warning { 
            background: #fcf3cf; 
            border-color: #dba617;
            color: #614200;
        }
        .error { 
            background: #fcebea; 
            border-color: #d63638;
            color: #5a0001;
        }
        .info { 
            background: #e5f5fa; 
            border-color: #00a0d2;
            color: #004a5e;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0;
            background: white;
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #dcdcde;
        }
        th { 
            background: #f6f7f7; 
            font-weight: 600;
            color: #1d2327;
        }
        tr:hover { 
            background: #f6f7f7; 
        }
        pre { 
            background: #1e1e1e; 
            color: #d4d4d4;
            padding: 15px; 
            overflow-x: auto; 
            border-radius: 6px;
            font-size: 12px;
            line-height: 1.6;
        }
        .button { 
            background: #2271b1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .button:hover { 
            background: #135e96;
        }
        .button-secondary {
            background: #dcdcde;
            color: #2c3338;
        }
        .button-secondary:hover {
            background: #c3c4c7;
        }
        .button-danger {
            background: #d63638;
        }
        .button-danger:hover {
            background: #b32d2e;
        }
        .code-inline { 
            background: #f6f7f7;
            padding: 3px 6px;
            border-radius: 3px;
            font-family: Consolas, Monaco, monospace;
            font-size: 13px;
        }
        .timestamp {
            color: #787c82;
            font-size: 12px;
        }
        .section {
            margin-bottom: 30px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .card {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dcdcde;
        }
        .card h3 {
            margin-top: 0;
            color: #1d2327;
            font-size: 14px;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { background: #00a32a; color: white; }
        .badge-error { background: #d63638; color: white; }
        .badge-warning { background: #dba617; color: white; }
        .badge-info { background: #00a0d2; color: white; }
        
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            z-index: 1000;
        }
    </style>
</head>
<body>

<div class="action-buttons">
    <a href="?refresh=30" class="button button-secondary">Auto-Refresh 30s</a>
    <a href="?" class="button button-secondary">Stop Refresh</a>
    <a href="?clear_logs=1" class="button button-danger" onclick="return confirm('Clear all logs?')">Clear Logs</a>
</div>

<div class="container">
    <h1>🔍 AI Rewriter Cron Debugger</h1>
    <p class="timestamp">Last checked: <?php echo date('Y-m-d H:i:s'); ?></p>

    <?php
    // Handle actions
    if (isset($_GET['clear_logs'])) {
        $log_file = wp_upload_dir()['basedir'] . '/ai-rewriter-cron.log';
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            echo '<div class="status-box success">✅ Logs cleared successfully!</div>';
        }
    }

    if (isset($_GET['trigger_now'])) {
        echo '<div class="status-box info">⏳ Triggering cron now...</div>';
        do_action('ai_rewriter_process_drafts');
        echo '<div class="status-box success">✅ Cron triggered! Check logs below.</div>';
    }

    if (isset($_GET['reschedule'])) {
        wp_clear_scheduled_hook('ai_rewriter_process_drafts');
        if (class_exists('AI_Rewriter_Cron_Scheduler')) {
            $scheduler = AI_Rewriter_Cron_Scheduler::get_instance();
            $scheduler->schedule_cron();
            echo '<div class="status-box success">✅ Cron rescheduled!</div>';
        } else {
            echo '<div class="status-box error">❌ AI_Rewriter_Cron_Scheduler class not found!</div>';
        }
    }
    ?>

    <!-- SECTION 1: QUICK STATUS -->
    <div class="section">
        <h2>📊 Quick Status Overview</h2>
        <div class="grid">
            <?php
            // Check class exists
            $class_exists = class_exists('AI_Rewriter_Cron_Scheduler');
            ?>
            <div class="card">
                <h3>Scheduler Class</h3>
                <?php if ($class_exists): ?>
                    <span class="badge badge-success">✅ Loaded</span>
                    <p><code class="code-inline">AI_Rewriter_Cron_Scheduler</code></p>
                <?php else: ?>
                    <span class="badge badge-error">❌ Not Found</span>
                    <p style="color: #d63638;">Class tidak ditemukan! Check include path.</p>
                <?php endif; ?>
            </div>

            <?php
            // Check cron scheduled
            $next_run = wp_next_scheduled('ai_rewriter_process_drafts');
            $is_scheduled = $next_run !== false;
            ?>
            <div class="card">
                <h3>Cron Schedule</h3>
                <?php if ($is_scheduled): ?>
                    <span class="badge badge-success">✅ Scheduled</span>
                    <p><strong>Next Run:</strong><br><?php echo date('Y-m-d H:i:s', $next_run); ?></p>
                    <p><strong>Time Until:</strong><br><?php echo human_time_diff($next_run, time()); ?></p>
                <?php else: ?>
                    <span class="badge badge-error">❌ Not Scheduled</span>
                    <p style="color: #d63638;">Cron tidak terjadwal!</p>
                <?php endif; ?>
            </div>

            <?php
            // Check auto enabled
            $auto_enabled = get_option('ai_rewriter_auto_enabled', 0);
            ?>
            <div class="card">
                <h3>Auto Processing</h3>
                <?php if ($auto_enabled): ?>
                    <span class="badge badge-success">✅ Enabled</span>
                <?php else: ?>
                    <span class="badge badge-warning">⚠️ Disabled</span>
                    <p style="color: #996800;">Auto processing disabled di settings!</p>
                <?php endif; ?>
            </div>

            <?php
            // Check API key
            $api_key = get_option('ai_rewriter_api_key', '');
            ?>
            <div class="card">
                <h3>OpenAI API Key</h3>
                <?php if (!empty($api_key)): ?>
                    <span class="badge badge-success">✅ Configured</span>
                    <p><code class="code-inline"><?php echo substr($api_key, 0, 10); ?>...</code></p>
                <?php else: ?>
                    <span class="badge badge-error">❌ Not Set</span>
                    <p style="color: #d63638;">API key belum dikonfigurasi!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SECTION 2: DETAILED CRON STATUS -->
    <div class="section">
        <h2>⚙️ Detailed Cron Status</h2>
        
        <?php
        $cron_array = _get_cron_array();
        $wp_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $schedules = wp_get_schedules();
        
        // Find AI Rewriter events
        $ai_events = array();
        if (is_array($cron_array)) {
            foreach ($cron_array as $timestamp => $cron) {
                foreach ($cron as $hook => $events) {
                    if (strpos($hook, 'ai_rewriter') !== false) {
                        $ai_events[] = array(
                            'hook' => $hook,
                            'timestamp' => $timestamp,
                            'datetime' => date('Y-m-d H:i:s', $timestamp),
                            'time_until' => human_time_diff(time(), $timestamp),
                            'is_past' => $timestamp < time(),
                            'events' => $events
                        );
                    }
                }
            }
        }
        ?>

        <table>
            <tr>
                <th>Configuration</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td><strong>WP Cron Status</strong></td>
                <td><?php echo $wp_cron_disabled ? 'DISABLED' : 'ENABLED'; ?></td>
                <td>
                    <?php if ($wp_cron_disabled): ?>
                        <span class="badge badge-error">❌ Cron Disabled</span>
                    <?php else: ?>
                        <span class="badge badge-success">✅ Working</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>AI Rewriter Events Found</strong></td>
                <td><?php echo count($ai_events); ?> event(s)</td>
                <td>
                    <?php if (count($ai_events) > 0): ?>
                        <span class="badge badge-success">✅ Found</span>
                    <?php else: ?>
                        <span class="badge badge-error">❌ None</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Last Schedule Time</strong></td>
                <td>
                    <?php 
                    $last_schedule = get_option('ai_rewriter_last_schedule_time', 0);
                    echo $last_schedule ? date('Y-m-d H:i:s', $last_schedule) . ' (' . human_time_diff($last_schedule) . ' ago)' : 'Never';
                    ?>
                </td>
                <td>
                    <?php if ($last_schedule && (time() - $last_schedule) < 86400): ?>
                        <span class="badge badge-success">✅ Recent</span>
                    <?php else: ?>
                        <span class="badge badge-warning">⚠️ Old/Never</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Last Cron Run</strong></td>
                <td>
                    <?php 
                    $last_run = get_option('ai_rewriter_last_cron_run', 0);
                    echo $last_run ? date('Y-m-d H:i:s', $last_run) . ' (' . human_time_diff($last_run) . ' ago)' : 'Never';
                    ?>
                </td>
                <td>
                    <?php if ($last_run && (time() - $last_run) < 3600): ?>
                        <span class="badge badge-success">✅ Recent</span>
                    <?php elseif ($last_run): ?>
                        <span class="badge badge-warning">⚠️ Old</span>
                    <?php else: ?>
                        <span class="badge badge-error">❌ Never</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Schedule Interval</strong></td>
                <td>
                    <?php 
                    $interval_name = get_option('ai_rewriter_schedule_interval', 'hourly');
                    echo $interval_name;
                    if (isset($schedules[$interval_name])) {
                        echo ' (' . ($schedules[$interval_name]['interval'] / 60) . ' minutes)';
                    }
                    ?>
                </td>
                <td>
                    <?php if (isset($schedules[$interval_name])): ?>
                        <span class="badge badge-success">✅ Valid</span>
                    <?php else: ?>
                        <span class="badge badge-error">❌ Invalid</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- SECTION 3: SCHEDULED EVENTS -->
    <?php if (!empty($ai_events)): ?>
    <div class="section">
        <h2>📅 Scheduled AI Rewriter Events</h2>
        <table>
            <tr>
                <th>Hook Name</th>
                <th>Next Run</th>
                <th>Time Until / Status</th>
                <th>Schedule Type</th>
            </tr>
            <?php foreach ($ai_events as $event): ?>
            <tr>
                <td><code class="code-inline"><?php echo $event['hook']; ?></code></td>
                <td><?php echo $event['datetime']; ?></td>
                <td>
                    <?php if ($event['is_past']): ?>
                        <span class="badge badge-error">OVERDUE</span>
                        <?php echo $event['time_until']; ?> ago
                    <?php else: ?>
                        <span class="badge badge-success">SCHEDULED</span>
                        in <?php echo $event['time_until']; ?>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    foreach ($event['events'] as $event_detail) {
                        echo isset($event_detail['schedule']) ? $event_detail['schedule'] : 'Single Event';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php else: ?>
    <div class="section">
        <h2>📅 Scheduled Events</h2>
        <div class="status-box error">
            <strong>❌ No AI Rewriter events scheduled!</strong>
            <p>Ini adalah masalah utama. Cron tidak terjadwal sama sekali.</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTION 4: CUSTOM INTERVALS -->
    <div class="section">
        <h2>⏱️ Custom Cron Intervals</h2>
        <?php
        $custom_intervals = array();
        foreach ($schedules as $key => $schedule) {
            if (strpos($key, 'ai_rewriter') !== false) {
                $custom_intervals[$key] = $schedule;
            }
        }
        ?>
        
        <?php if (!empty($custom_intervals)): ?>
        <table>
            <tr>
                <th>Interval Name</th>
                <th>Display Name</th>
                <th>Interval (seconds)</th>
                <th>Interval (minutes)</th>
            </tr>
            <?php foreach ($custom_intervals as $key => $interval): ?>
            <tr>
                <td><code class="code-inline"><?php echo $key; ?></code></td>
                <td><?php echo $interval['display']; ?></td>
                <td><?php echo $interval['interval']; ?> seconds</td>
                <td><?php echo round($interval['interval'] / 60, 1); ?> minutes</td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div class="status-box warning">
            <strong>⚠️ No custom intervals registered</strong>
            <p>Custom intervals belum terdaftar. Check filter 'cron_schedules'.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- SECTION 5: DEPENDENCIES CHECK -->
    <div class="section">
        <h2>📦 Dependencies Check</h2>
        <?php
        $required_classes = array(
            'AI_Rewriter_Cron_Scheduler' => 'Cron Scheduler (Main)',
            'AI_Rewriter_API' => 'OpenAI API Handler',
            'AI_Rewriter_Content_Parser' => 'Content Parser',
            'AI_Rewriter_Logger' => 'Logger',
            'AI_Rewriter_Image_Handler' => 'Image Handler'
        );
        ?>
        <table>
            <tr>
                <th>Class Name</th>
                <th>Description</th>
                <th>Status</th>
                <th>Methods Count</th>
            </tr>
            <?php foreach ($required_classes as $class => $desc): ?>
            <tr>
                <td><code class="code-inline"><?php echo $class; ?></code></td>
                <td><?php echo $desc; ?></td>
                <td>
                    <?php if (class_exists($class)): ?>
                        <span class="badge badge-success">✅ Loaded</span>
                    <?php else: ?>
                        <span class="badge badge-error">❌ Missing</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php 
                    if (class_exists($class)) {
                        $methods = get_class_methods($class);
                        echo count($methods) . ' methods';
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- SECTION 6: DRAFT POSTS AVAILABLE -->
    <div class="section">
        <h2>📝 Draft Posts Available for Processing</h2>
        <?php
        global $wpdb;
        
        $min_words = get_option('ai_rewriter_min_words', 50);
        
        // Get unprocessed drafts
        $unprocessed_drafts = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_content, p.post_date
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ai_rewriter_processed'
            WHERE p.post_type = 'post' 
            AND p.post_status = 'draft'
            AND pm.meta_id IS NULL
            ORDER BY p.post_date DESC
            LIMIT 10
        "));
        
        $total_drafts = count($unprocessed_drafts);
        ?>
        
        <?php if ($total_drafts > 0): ?>
        <div class="status-box success">
            <strong>✅ Found <?php echo $total_drafts; ?> unprocessed draft post(s)</strong>
        </div>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Word Count</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($unprocessed_drafts as $draft): ?>
            <?php 
            $word_count = str_word_count(strip_tags($draft->post_content));
            $is_eligible = $word_count >= $min_words;
            ?>
            <tr>
                <td><?php echo $draft->ID; ?></td>
                <td><?php echo esc_html(wp_trim_words($draft->post_title, 10)); ?></td>
                <td><?php echo $word_count; ?> words</td>
                <td><?php echo date('Y-m-d H:i', strtotime($draft->post_date)); ?></td>
                <td>
                    <?php if ($is_eligible): ?>
                        <span class="badge badge-success">✅ Eligible</span>
                    <?php else: ?>
                        <span class="badge badge-warning">⚠️ Too Short</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div class="status-box warning">
            <strong>⚠️ No unprocessed draft posts available</strong>
            <p>Tidak ada draft post yang belum diproses. Cron tidak akan melakukan apa-apa.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- SECTION 7: RECENT LOGS -->
    <div class="section">
        <h2>📄 Recent Cron Logs (Last 50 lines)</h2>
        <?php
        $log_file = wp_upload_dir()['basedir'] . '/ai-rewriter-cron.log';
        if (file_exists($log_file) && filesize($log_file) > 0):
            $lines = file($log_file);
            $recent_lines = array_slice($lines, -50);
        ?>
        <pre><?php echo esc_html(implode('', $recent_lines)); ?></pre>
        <?php else: ?>
        <div class="status-box warning">
            <strong>⚠️ No log file found or empty</strong>
            <p>Log file: <code class="code-inline"><?php echo $log_file; ?></code></p>
            <p>Logs akan muncul setelah cron dijalankan pertama kali.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- SECTION 8: WORDPRESS DEBUG LOG -->
    <div class="section">
        <h2>🐛 WordPress Debug Log (Last 30 lines)</h2>
        <?php
        $debug_log = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($debug_log) && filesize($debug_log) > 0):
            $lines = file($debug_log);
            // Filter only AI Rewriter related lines
            $ai_lines = array_filter($lines, function($line) {
                return stripos($line, 'ai rewriter') !== false || stripos($line, 'ai_rewriter') !== false;
            });
            $recent_ai_lines = array_slice($ai_lines, -30);
            
            if (!empty($recent_ai_lines)):
        ?>
        <pre><?php echo esc_html(implode('', $recent_ai_lines)); ?></pre>
        <?php else: ?>
        <div class="status-box info">
            <strong>ℹ️ No AI Rewriter entries in debug log</strong>
            <p>Aktifkan WP_DEBUG di wp-config.php untuk melihat debug messages.</p>
        </div>
        <?php 
            endif;
        else: 
        ?>
        <div class="status-box warning">
            <strong>⚠️ Debug log not found or empty</strong>
            <p>Enable debugging in wp-config.php:</p>
            <pre>define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>
        </div>
        <?php endif; ?>
    </div>

    <!-- SECTION 9: DIAGNOSTIC ACTIONS -->
    <div class="section">
        <h2>🔧 Diagnostic Actions</h2>
        <div class="grid">
            <div class="card">
                <h3>Manual Trigger</h3>
                <p>Test cron execution immediately</p>
                <a href="?trigger_now=1" class="button" onclick="return confirm('Trigger cron now?')">▶️ Trigger Now</a>
            </div>
            
            <div class="card">
                <h3>Reschedule Cron</h3>
                <p>Clear dan jadwalkan ulang cron</p>
                <a href="?reschedule=1" class="button" onclick="return confirm('Reschedule cron?')">🔄 Reschedule</a>
            </div>
            
            <div class="card">
                <h3>Clear Logs</h3>
                <p>Hapus semua log untuk fresh start</p>
                <a href="?clear_logs=1" class="button button-danger" onclick="return confirm('Clear all logs?')">🗑️ Clear Logs</a>
            </div>
            
            <div class="card">
                <h3>View Settings</h3>
                <p>Go to plugin settings page</p>
                <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-settings'); ?>" class="button button-secondary">⚙️ Settings</a>
            </div>
        </div>
    </div>

    <!-- SECTION 10: DIAGNOSTIC SUMMARY -->
    <div class="section">
        <h2>📋 Diagnostic Summary</h2>
        <?php
        // Calculate issues
        $issues = array();
        $warnings = array();
        $success = array();
        
        // Check class
        if (!class_exists('AI_Rewriter_Cron_Scheduler')) {
            $issues[] = 'AI_Rewriter_Cron_Scheduler class tidak ditemukan';
        } else {
            $success[] = 'Scheduler class loaded';
        }
        
        // Check scheduled
        if (!$is_scheduled) {
            $issues[] = 'Cron tidak terjadwal (wp_next_scheduled = false)';
        } else {
            $success[] = 'Cron terjadwal untuk ' . date('Y-m-d H:i:s', $next_run);
        }
        
        // Check auto enabled
        if (!$auto_enabled) {
            $issues[] = 'Auto processing disabled di settings';
        } else {
            $success[] = 'Auto processing enabled';
        }
        
        // Check API key
        if (empty($api_key)) {
            $issues[] = 'OpenAI API key belum dikonfigurasi';
        } else {
            $success[] = 'OpenAI API key configured';
        }
        
        // Check draft posts
        if ($total_drafts == 0) {
            $warnings[] = 'Tidak ada draft posts untuk diproses';
        } else {
            $success[] = $total_drafts . ' draft posts tersedia';
        }
        
        // Check dependencies
        $missing_deps = array();
        foreach ($required_classes as $class => $desc) {
            if (!class_exists($class)) {
                $missing_deps[] = $class;
            }
        }
        if (!empty($missing_deps)) {
            $issues[] = 'Missing dependencies: ' . implode(', ', $missing_deps);
        }
        
        // Check WP Cron
        if ($wp_cron_disabled) {
            $issues[] = 'WP Cron is disabled (DISABLE_WP_CRON = true)';
        } else {
            $success[] = 'WP Cron enabled';
        }
        ?>
        
        <?php if (!empty($issues)): ?>
        <div class="status-box error">
            <strong>❌ Critical Issues Found (<?php echo count($issues); ?>):</strong>
            <ul>
                <?php foreach ($issues as $issue): ?>
                <li><?php echo $issue; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($warnings)): ?>
        <div class="status-box warning">
            <strong>⚠️ Warnings (<?php echo count($warnings); ?>):</strong>
            <ul>
                <?php foreach ($warnings as $warning): ?>
                <li><?php echo $warning; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="status-box success">
            <strong>✅ Working Properly (<?php echo count($success); ?>):</strong>
            <ul>
                <?php foreach ($success as $item): ?>
                <li><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (empty($issues) && empty($warnings)): ?>
        <div class="status-box success">
            <strong>🎉 Everything looks good!</strong>
            <p>Semua komponen berfungsi dengan baik. Cron seharusnya berjalan otomatis.</p>
            <p>Jika masih tidak berjalan, coba:</p>
            <ul>
                <li>Tunggu hingga waktu scheduled berikutnya</li>
                <li>Trigger manual untuk testing</li>
                <li>Check WordPress debug log untuk errors</li>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dcdcde; color: #787c82; font-size: 12px;">
        <p><strong>⚠️ SECURITY WARNING:</strong> Hapus file ini setelah debugging selesai!</p>
        <p>File location: <code class="code-inline">/wp-content/plugins/adesurya-tastes/debug-ai-rewriter-cron.php</code></p>
    </div>

</div>

</body>
</html>