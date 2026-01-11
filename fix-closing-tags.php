<?php
/**
 * Automatic PHP Closing Tag Remover
 * 
 * Upload ke: wp-content/plugins/tastes/fix-closing-tags.php
 * Akses via: http://yoursite.com/wp-content/plugins/tastes/fix-closing-tags.php
 * 
 * Fixes the closing ?> tag issue that prevents classes from loading
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

$action = $_GET['action'] ?? '';
$confirmed = $_GET['confirm'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix PHP Closing Tags</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 20px;
            background: #f0f0f1;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { color: #d63638; }
        h2 { color: #2271b1; margin-top: 30px; }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
            border-left: 4px solid;
        }
        .success { background: #d7f0d7; border-color: #00a32a; color: #00500d; }
        .error { background: #fcebea; border-color: #d63638; color: #5a0001; }
        .warning { background: #fcf3cf; border-color: #dba617; color: #614200; }
        .info { background: #e5f5fa; border-color: #00a0d2; color: #004a5e; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; overflow-x: auto; border-radius: 6px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #dcdcde; }
        th { background: #f6f7f7; font-weight: 600; }
        .button {
            background: #2271b1;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            border: none;
            cursor: pointer;
        }
        .button:hover { background: #135e96; }
        .button-danger { background: #d63638; }
        .button-danger:hover { background: #b32d2e; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 Fix PHP Closing Tags</h1>
    <p style="color: #787c82;">This tool removes problematic <code>&lt;?php</code> closing tags from class files</p>
    
    <?php
    $includes_path = WP_PLUGIN_DIR . '/tastes/includes/';
    
    $files_to_fix = array(
        'class-ai-api.php' => 'AI_Rewriter_API',
        'class-content-parser.php' => 'AI_Rewriter_Content_Parser',
        'class-logger.php' => 'AI_Rewriter_Logger',
        'class-image-handler.php' => 'AI_Rewriter_Image_Handler'
    );
    
    if ($action === 'fix' && $confirmed === 'yes') {
        echo '<h2>⏳ Fixing Files...</h2>';
        
        $results = array();
        $total_fixed = 0;
        
        foreach ($files_to_fix as $filename => $classname) {
            $filepath = $includes_path . $filename;
            
            if (!file_exists($filepath)) {
                $results[] = array(
                    'file' => $filename,
                    'status' => 'error',
                    'message' => 'File not found'
                );
                continue;
            }
            
            // Read file content
            $content = file_get_contents($filepath);
            $original_content = $content;
            
            // Check if file has closing tag
            $has_closing_tag = preg_match('/\?>\s*$/s', $content);
            
            if ($has_closing_tag) {
                // Remove closing PHP tag and trailing whitespace
                $content = preg_replace('/\s*\?>\s*$/s', '', $content);
                
                // Ensure file ends with single newline
                $content = rtrim($content) . "\n";
                
                // Backup original file
                $backup_path = $filepath . '.backup.' . date('Y-m-d-His');
                file_put_contents($backup_path, $original_content);
                
                // Write fixed content
                $write_result = file_put_contents($filepath, $content);
                
                if ($write_result !== false) {
                    $results[] = array(
                        'file' => $filename,
                        'status' => 'success',
                        'message' => 'Closing tag removed',
                        'backup' => basename($backup_path)
                    );
                    $total_fixed++;
                } else {
                    $results[] = array(
                        'file' => $filename,
                        'status' => 'error',
                        'message' => 'Failed to write file'
                    );
                }
            } else {
                $results[] = array(
                    'file' => $filename,
                    'status' => 'skipped',
                    'message' => 'No closing tag found'
                );
            }
        }
        
        // Display results
        echo '<table>';
        echo '<tr><th>File</th><th>Status</th><th>Details</th></tr>';
        
        foreach ($results as $result) {
            $status_class = $result['status'];
            $status_icon = $result['status'] === 'success' ? '✅' : 
                          ($result['status'] === 'skipped' ? '⏭️' : '❌');
            
            echo '<tr>';
            echo '<td>' . $result['file'] . '</td>';
            echo '<td>' . $status_icon . ' ' . ucfirst($result['status']) . '</td>';
            echo '<td>' . $result['message'];
            
            if (isset($result['backup'])) {
                echo '<br><small style="color: #787c82;">Backup: ' . $result['backup'] . '</small>';
            }
            
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        if ($total_fixed > 0) {
            echo '<div class="status success">';
            echo '<h3>✅ SUCCESS!</h3>';
            echo '<p>' . $total_fixed . ' file(s) fixed successfully!</p>';
            echo '</div>';
            
            // Verify classes can now be loaded
            echo '<h3>🧪 Verifying Classes...</h3>';
            
            $verification_results = array();
            
            foreach ($files_to_fix as $filename => $classname) {
                $filepath = $includes_path . $filename;
                
                if (file_exists($filepath)) {
                    // Clear any previous class definition
                    if (class_exists($classname)) {
                        $verification_results[] = array(
                            'class' => $classname,
                            'status' => 'already_loaded'
                        );
                    } else {
                        try {
                            require_once $filepath;
                            
                            if (class_exists($classname)) {
                                $verification_results[] = array(
                                    'class' => $classname,
                                    'status' => 'success'
                                );
                            } else {
                                $verification_results[] = array(
                                    'class' => $classname,
                                    'status' => 'failed'
                                );
                            }
                        } catch (Exception $e) {
                            $verification_results[] = array(
                                'class' => $classname,
                                'status' => 'error',
                                'message' => $e->getMessage()
                            );
                        }
                    }
                }
            }
            
            echo '<table>';
            echo '<tr><th>Class</th><th>Load Status</th></tr>';
            
            $all_loaded = true;
            foreach ($verification_results as $v) {
                $is_loaded = in_array($v['status'], array('success', 'already_loaded'));
                if (!$is_loaded) $all_loaded = false;
                
                echo '<tr>';
                echo '<td>' . $v['class'] . '</td>';
                echo '<td>';
                
                if ($is_loaded) {
                    echo '<span style="color: #00a32a;">✅ Loaded Successfully</span>';
                } else {
                    echo '<span style="color: #d63638;">❌ Load Failed</span>';
                    if (isset($v['message'])) {
                        echo '<br><small>' . esc_html($v['message']) . '</small>';
                    }
                }
                
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            
            if ($all_loaded) {
                echo '<div class="status success">';
                echo '<h3>🎉 COMPLETE SUCCESS!</h3>';
                echo '<p><strong>All classes loaded successfully!</strong></p>';
                echo '<p><strong>Next Steps:</strong></p>';
                echo '<ol>';
                echo '<li>✅ Go to <a href="debug-ai-rewriter-cron.php">debug-ai-rewriter-cron.php</a> to verify all systems working</li>';
                echo '<li>✅ Test manual trigger with <a href="trigger-cron-manual.php">trigger-cron-manual.php</a></li>';
                echo '<li>✅ Monitor auto-processing for draft posts</li>';
                echo '<li>✅ Delete all debug tools after confirming everything works</li>';
                echo '</ol>';
                echo '</div>';
            } else {
                echo '<div class="status warning">';
                echo '<h3>⚠️ Partial Success</h3>';
                echo '<p>Closing tags removed but some classes still not loading.</p>';
                echo '<p>This might indicate other issues in the files. Check WordPress debug.log for details.</p>';
                echo '</div>';
            }
            
        } else {
            echo '<div class="status info">';
            echo '<p>No files needed fixing (no closing tags found).</p>';
            echo '</div>';
        }
        
        echo '<p style="margin-top: 30px;">';
        echo '<a href="debug-ai-rewriter-cron.php" class="button">📊 Go to Debug Tool</a>';
        echo '<a href="?" class="button">🔄 Check Again</a>';
        echo '</p>';
        
    } else {
        // Show scan results
        echo '<h2>📊 Scanning Files...</h2>';
        
        $scan_results = array();
        $needs_fix = false;
        
        foreach ($files_to_fix as $filename => $classname) {
            $filepath = $includes_path . $filename;
            
            if (!file_exists($filepath)) {
                $scan_results[] = array(
                    'file' => $filename,
                    'exists' => false,
                    'has_closing_tag' => false,
                    'class_loaded' => false,
                    'issue' => 'File not found'
                );
                continue;
            }
            
            $content = file_get_contents($filepath);
            $has_closing_tag = preg_match('/\?>\s*$/s', $content);
            $class_loaded = class_exists($classname);
            
            if ($has_closing_tag) {
                $needs_fix = true;
            }
            
            // Get last 5 characters to show
            $last_chars = substr($content, -20);
            $last_chars_display = str_replace(array("\n", "\r", "\t"), array('\\n', '\\r', '\\t'), $last_chars);
            
            $scan_results[] = array(
                'file' => $filename,
                'class' => $classname,
                'exists' => true,
                'size' => filesize($filepath),
                'has_closing_tag' => $has_closing_tag,
                'class_loaded' => $class_loaded,
                'last_chars' => $last_chars_display,
                'issue' => $has_closing_tag ? 'Has closing PHP tag' : 'OK'
            );
        }
        
        echo '<table>';
        echo '<tr><th>File</th><th>Size</th><th>Closing Tag</th><th>Class Loaded</th><th>Issue</th></tr>';
        
        foreach ($scan_results as $result) {
            if (!$result['exists']) {
                echo '<tr>';
                echo '<td>' . $result['file'] . '</td>';
                echo '<td colspan="4"><span style="color: #d63638;">❌ File not found</span></td>';
                echo '</tr>';
                continue;
            }
            
            echo '<tr>';
            echo '<td>' . $result['file'] . '</td>';
            echo '<td>' . number_format($result['size']) . ' bytes</td>';
            echo '<td>';
            
            if ($result['has_closing_tag']) {
                echo '<span style="color: #d63638;">❌ YES</span>';
            } else {
                echo '<span style="color: #00a32a;">✅ NO</span>';
            }
            
            echo '</td>';
            echo '<td>';
            
            if ($result['class_loaded']) {
                echo '<span style="color: #00a32a;">✅ YES</span>';
            } else {
                echo '<span style="color: #d63638;">❌ NO</span>';
            }
            
            echo '</td>';
            echo '<td>';
            
            if ($result['has_closing_tag']) {
                echo '<span style="color: #d63638;">❌ ' . $result['issue'] . '</span>';
            } else {
                echo '<span style="color: #00a32a;">✅ ' . $result['issue'] . '</span>';
            }
            
            echo '</td>';
            echo '</tr>';
            
            // Show last characters for debugging
            if ($result['has_closing_tag']) {
                echo '<tr>';
                echo '<td colspan="5" style="background: #fcf3cf; font-size: 12px;">';
                echo '<strong>Last 20 chars:</strong> <code>' . esc_html($result['last_chars']) . '</code>';
                echo '</td>';
                echo '</tr>';
            }
        }
        
        echo '</table>';
        
        if ($needs_fix) {
            echo '<div class="status error">';
            echo '<h3>❌ Problem Found: PHP Closing Tags!</h3>';
            echo '<p><strong>Issue:</strong> Your files have <code>&lt;?php</code> closing tags at the end.</p>';
            echo '<p><strong>Why this is bad:</strong></p>';
            echo '<ul>';
            echo '<li>❌ WordPress coding standards prohibit closing tags</li>';
            echo '<li>❌ Can cause "headers already sent" errors</li>';
            echo '<li>❌ Prevents proper class registration</li>';
            echo '<li>❌ Causes whitespace/output buffering issues</li>';
            echo '</ul>';
            echo '<p><strong>Solution:</strong> Remove the closing <code>?&gt;</code> tags from affected files.</p>';
            echo '</div>';
            
            echo '<div class="status info">';
            echo '<h3>🔧 Automatic Fix Available</h3>';
            echo '<p>This tool can automatically:</p>';
            echo '<ul>';
            echo '<li>✅ Remove closing PHP tags from all affected files</li>';
            echo '<li>✅ Create backup of original files</li>';
            echo '<li>✅ Verify classes load properly after fix</li>';
            echo '<li>✅ Clean up trailing whitespace</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<p style="margin-top: 30px;">';
            echo '<a href="?action=fix&confirm=yes" class="button button-danger" onclick="return confirm(\'Fix all files by removing closing PHP tags? Backups will be created.\')">🔧 FIX ALL FILES NOW</a>';
            echo '</p>';
            
        } else {
            echo '<div class="status success">';
            echo '<h3>✅ No Closing Tags Found</h3>';
            echo '<p>All files are correctly formatted without closing PHP tags.</p>';
            echo '<p>If classes are still not loading, the issue is elsewhere. Run <a href="inspect-classes.php">inspect-classes.php</a> for detailed diagnosis.</p>';
            echo '</div>';
        }
    }
    ?>
    
    <hr style="margin: 40px 0; border: none; border-top: 1px solid #dcdcde;">
    
    <h3>📖 About PHP Closing Tags</h3>
    
    <div class="status info">
        <p><strong>WordPress Coding Standard:</strong></p>
        <p>Files should <strong>NOT</strong> have a closing PHP tag at the end.</p>
        
        <p><strong>✅ CORRECT FORMAT:</strong></p>
        <pre><?php echo htmlspecialchars('<?php
class My_Class {
    public function my_method() {
        // code
    }
}
// File ends here - NO closing tag'); ?></pre>
        
        <p><strong>❌ WRONG FORMAT (Your Current Files):</strong></p>
        <pre><?php echo htmlspecialchars('<?php
class My_Class {
    public function my_method() {
        // code
    }
}
?>'); ?> ← Remove this!</pre>
        
        <p><strong>Why?</strong></p>
        <ul>
            <li>Prevents accidental whitespace after closing tag</li>
            <li>Avoids "headers already sent" errors</li>
            <li>Ensures proper class registration</li>
            <li>Follows PSR and WordPress standards</li>
        </ul>
    </div>
    
    <p style="color: #787c82; font-size: 12px; margin-top: 40px;">
        <strong>⚠️ Security:</strong> Delete this file after fixing!<br>
        File: <code>/wp-content/plugins/tastes/fix-closing-tags.php</code>
    </p>
    
</div>

</body>
</html>