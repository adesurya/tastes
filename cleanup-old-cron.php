<?php
/**
 * Cleanup Script untuk Menghapus Old Cron Schedules
 * 
 * CARA PENGGUNAAN:
 * 1. Upload file ini ke folder plugin Anda
 * 2. Akses via browser: http://yoursite.com/wp-content/plugins/your-plugin/cleanup-old-cron.php
 * 3. Atau jalankan sekali via functions.php kemudian hapus
 */

// Jika dijalankan via browser
if (!defined('ABSPATH')) {
    // Load WordPress
    require_once('../../../wp-load.php');
    
    // Check user authorization
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
}

/**
 * Function untuk cleanup old cron schedules
 */
function ai_rewriter_cleanup_old_cron() {
    echo "<h2>🔧 AI Rewriter Cron Cleanup</h2>";
    
    // 1. Clear semua schedule dengan hook ai_rewriter_process_drafts
    echo "<h3>Step 1: Clearing old schedules...</h3>";
    $cleared = wp_clear_scheduled_hook('ai_rewriter_process_drafts');
    if ($cleared !== false) {
        echo "<p style='color: green;'>✅ Cleared " . $cleared . " old schedule(s)</p>";
    } else {
        echo "<p style='color: orange;'>ℹ️ No old schedules found or already cleared</p>";
    }
    
    // 2. Cek current cron status
    echo "<h3>Step 2: Checking current cron status...</h3>";
    $next_scheduled = wp_next_scheduled('ai_rewriter_process_drafts');
    if ($next_scheduled) {
        echo "<p style='color: orange;'>⚠️ Still has scheduled event at: " . date('Y-m-d H:i:s', $next_scheduled) . "</p>";
        echo "<p>Clearing it...</p>";
        wp_unschedule_event($next_scheduled, 'ai_rewriter_process_drafts');
        echo "<p style='color: green;'>✅ Cleared!</p>";
    } else {
        echo "<p style='color: green;'>✅ No active schedules found</p>";
    }
    
    // 3. List all cron events (untuk verifikasi)
    echo "<h3>Step 3: Listing all WordPress cron events...</h3>";
    $cron_array = _get_cron_array();
    $found_ai_events = false;
    
    if (is_array($cron_array)) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Time</th><th>Hook</th><th>Action</th></tr>";
        
        foreach ($cron_array as $timestamp => $cron) {
            foreach ($cron as $hook => $events) {
                if (strpos($hook, 'ai_rewriter') !== false) {
                    $found_ai_events = true;
                    echo "<tr>";
                    echo "<td>" . date('Y-m-d H:i:s', $timestamp) . "</td>";
                    echo "<td>{$hook}</td>";
                    echo "<td><button onclick='clearEvent(\"{$hook}\", {$timestamp})'>Clear This Event</button></td>";
                    echo "</tr>";
                }
            }
        }
        
        echo "</table>";
        
        if (!$found_ai_events) {
            echo "<p style='color: green;'>✅ No AI Rewriter events found in cron array</p>";
        }
    }
    
    // 4. Verify custom intervals
    echo "<h3>Step 4: Checking custom intervals...</h3>";
    $schedules = wp_get_schedules();
    $ai_schedules = array();
    
    foreach ($schedules as $key => $schedule) {
        if (strpos($key, 'ai_rewriter') !== false) {
            $ai_schedules[$key] = $schedule;
        }
    }
    
    if (!empty($ai_schedules)) {
        echo "<p style='color: green;'>✅ Found " . count($ai_schedules) . " custom AI Rewriter interval(s):</p>";
        echo "<ul>";
        foreach ($ai_schedules as $key => $schedule) {
            echo "<li><strong>{$key}</strong>: {$schedule['display']} ({$schedule['interval']}s)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>⚠️ No custom intervals registered (this is OK if using default intervals)</p>";
    }
    
    // 5. Clean up options
    echo "<h3>Step 5: Cleaning up related options...</h3>";
    $options_to_check = array(
        'ai_rewriter_last_schedule_time',
        'ai_rewriter_schedule_interval',
        'ai_rewriter_last_cron_run',
        'ai_rewriter_cron_error',
        'ai_rewriter_cron_fallback',
        'ai_rewriter_last_cron_check'
    );
    
    foreach ($options_to_check as $option) {
        $value = get_option($option);
        if ($value !== false) {
            echo "<p>Found option: <strong>{$option}</strong> = " . print_r($value, true) . "</p>";
        }
    }
    
    echo "<p><button onclick='if(confirm(\"Clear all AI Rewriter cron options?\")) { location.href=\"?clear_options=1\"; }'>Clear All Options</button></p>";
    
    // Handle clear options
    if (isset($_GET['clear_options'])) {
        foreach ($options_to_check as $option) {
            delete_option($option);
        }
        echo "<p style='color: green;'>✅ All options cleared!</p>";
    }
    
    // 6. Final recommendations
    echo "<h3>✨ Cleanup Complete!</h3>";
    echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50;'>";
    echo "<h4>Next Steps:</h4>";
    echo "<ol>";
    echo "<li>Upload file <code>class-cron-scheduler.php</code> yang baru ke folder <code>includes/</code></li>";
    echo "<li>Update main plugin file untuk load class yang baru</li>";
    echo "<li>Rename atau hapus file cron yang lama (<code>class-cron.php</code>, <code>class-cron-manager.php</code>, <code>class-cronnew.php</code>)</li>";
    echo "<li>Enable auto-processing di plugin settings</li>";
    echo "<li>Verify cron berjalan dengan melihat log file atau menggunakan WP Crontrol plugin</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p><a href='" . admin_url('admin.php?page=ai-rewriter') . "' class='button button-primary'>← Back to AI Rewriter Settings</a></p>";
}

// Run cleanup
ai_rewriter_cleanup_old_cron();

// Add JavaScript for inline clearing
?>
<script>
function clearEvent(hook, timestamp) {
    if (confirm('Clear event: ' + hook + ' at ' + timestamp + '?')) {
        // Redirect to WordPress AJAX handler
        window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=ai_clear_specific_event&hook=' + hook + '&timestamp=' + timestamp;
    }
}
</script>