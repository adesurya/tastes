<?php
/**
 * Quick Manual Trigger untuk AI Rewriter Cron
 * 
 * Upload ke: wp-content/plugins/adesurya-tastes/trigger-cron-manual.php
 * Akses via: http://yoursite.com/wp-content/plugins/adesurya-tastes/trigger-cron-manual.php
 * 
 * Script sederhana untuk test cron secara manual
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Cron Trigger</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { color: #2271b1; }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .success { background: #d7f0d7; border-color: #00a32a; }
        .error { background: #fcebea; border-color: #d63638; }
        .info { background: #e5f5fa; border-color: #00a0d2; }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            overflow-x: auto;
            border-radius: 5px;
            font-size: 12px;
        }
        .button {
            background: #2271b1;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .button:hover { background: #135e96; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚀 Manual Cron Trigger</h1>
    
    <?php
    if (isset($_GET['trigger'])) {
        echo '<div class="status info"><strong>⏳ Triggering cron...</strong></div>';
        
        // Start output buffering untuk catch semua output
        ob_start();
        
        $start_time = microtime(true);
        
        // Trigger the cron
        do_action('ai_rewriter_process_drafts');
        
        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 2);
        
        // Get any output that was generated
        $output = ob_get_clean();
        
        echo '<div class="status success">';
        echo '<strong>✅ Cron triggered successfully!</strong><br>';
        echo 'Execution time: ' . $execution_time . ' seconds';
        echo '</div>';
        
        if (!empty($output)) {
            echo '<h3>Output:</h3>';
            echo '<pre>' . esc_html($output) . '</pre>';
        }
        
        // Show recent logs
        $log_file = wp_upload_dir()['basedir'] . '/ai-rewriter-cron.log';
        if (file_exists($log_file)) {
            echo '<h3>Recent Log Entries:</h3>';
            $lines = file($log_file);
            $recent = array_slice($lines, -20);
            echo '<pre>' . esc_html(implode('', $recent)) . '</pre>';
        }
        
        echo '<p><a href="?" class="button">← Back</a></p>';
        
    } else {
        ?>
        
        <div class="status info">
            <p><strong>ℹ️ Tentang Manual Trigger:</strong></p>
            <p>Script ini akan menjalankan cron <code>ai_rewriter_process_drafts</code> secara manual untuk testing.</p>
        </div>
        
        <h3>📊 Current Status:</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px;"><strong>Auto Processing</strong></td>
                <td style="padding: 10px;">
                    <?php 
                    $auto = get_option('ai_rewriter_auto_enabled', 0);
                    echo $auto ? '✅ Enabled' : '❌ Disabled';
                    ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px;"><strong>API Key Configured</strong></td>
                <td style="padding: 10px;">
                    <?php 
                    $key = get_option('ai_rewriter_api_key', '');
                    echo !empty($key) ? '✅ Yes' : '❌ No';
                    ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px;"><strong>Next Scheduled Run</strong></td>
                <td style="padding: 10px;">
                    <?php 
                    $next = wp_next_scheduled('ai_rewriter_process_drafts');
                    echo $next ? date('Y-m-d H:i:s', $next) : '❌ Not scheduled';
                    ?>
                </td>
            </tr>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px;"><strong>Last Run</strong></td>
                <td style="padding: 10px;">
                    <?php 
                    $last = get_option('ai_rewriter_last_cron_run', 0);
                    echo $last ? date('Y-m-d H:i:s', $last) . ' (' . human_time_diff($last) . ' ago)' : 'Never';
                    ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px;"><strong>Unprocessed Drafts</strong></td>
                <td style="padding: 10px;">
                    <?php 
                    global $wpdb;
                    $count = $wpdb->get_var("
                        SELECT COUNT(*)
                        FROM {$wpdb->posts} p
                        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_ai_rewriter_processed'
                        WHERE p.post_type = 'post' 
                        AND p.post_status = 'draft'
                        AND pm.meta_id IS NULL
                    ");
                    echo $count . ' posts';
                    ?>
                </td>
            </tr>
        </table>
        
        <p style="margin-top: 30px;">
            <a href="?trigger=1" class="button" onclick="return confirm('Run cron now?')">
                ▶️ Trigger Cron Now
            </a>
        </p>
        
        <p style="color: #787c82; font-size: 14px; margin-top: 30px;">
            <strong>Note:</strong> Ini akan menjalankan cron synchronously. Jika ada banyak draft posts, 
            proses bisa memakan waktu beberapa menit.
        </p>
        
        <?php
    }
    ?>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    
    <p style="color: #787c82; font-size: 12px;">
        <strong>⚠️ HAPUS FILE INI</strong> setelah debugging selesai!<br>
        File: <code>/wp-content/plugins/adesurya-tastes/trigger-cron-manual.php</code>
    </p>
</div>
</body>
</html>