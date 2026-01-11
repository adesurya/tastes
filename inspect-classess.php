<?php
/**
 * Class File Inspector & Validator
 * 
 * Upload ke: wp-content/plugins/adesurya-tastes/inspect-classes.php
 * Akses via: http://yoursite.com/wp-content/plugins/adesurya-tastes/inspect-classes.php
 * 
 * Untuk diagnose kenapa classes tidak ter-load
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Class File Inspector</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 20px;
            background: #f0f0f1;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { color: #d63638; }
        h2 { color: #2271b1; margin-top: 30px; }
        .file-box {
            background: #f6f7f7;
            border: 2px solid #dcdcde;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .file-box.error {
            border-color: #d63638;
            background: #fcebea;
        }
        .file-box.success {
            border-color: #00a32a;
            background: #d7f0d7;
        }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            overflow-x: auto;
            border-radius: 6px;
            font-size: 12px;
            max-height: 400px;
        }
        .code-line {
            padding: 2px 5px;
        }
        .code-line.highlight {
            background: #ffd700;
            color: #000;
        }
        .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-ok { background: #00a32a; color: white; }
        .status-error { background: #d63638; color: white; }
        .status-warning { background: #dba617; color: white; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dcdcde;
        }
        th {
            background: #f6f7f7;
            font-weight: 600;
        }
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
    <h1>🔍 Class File Inspector & Validator</h1>
    <p style="color: #787c82;">Diagnose kenapa classes tidak ter-load meskipun file ada</p>
    
    <?php
    $includes_path = WP_PLUGIN_DIR . '/tastes/includes/';
    
    $files_to_check = array(
        'class-ai-api.php' => 'AI_Rewriter_API',
        'class-content-parser.php' => 'AI_Rewriter_Content_Parser',
        'class-logger.php' => 'AI_Rewriter_Logger',
        'class-image-handler.php' => 'AI_Rewriter_Image_Handler'
    );
    
    echo '<h2>📋 Quick Summary</h2>';
    echo '<table>';
    echo '<tr><th>File</th><th>Exists</th><th>Size</th><th>Readable</th><th>PHP Valid</th><th>Class Found</th><th>Class Loaded</th></tr>';
    
    foreach ($files_to_check as $filename => $expected_class) {
        $filepath = $includes_path . $filename;
        $exists = file_exists($filepath);
        $size = $exists ? filesize($filepath) : 0;
        $readable = $exists && is_readable($filepath);
        
        $php_valid = false;
        $class_found_in_file = false;
        $class_loaded = class_exists($expected_class);
        
        if ($exists && $readable) {
            $content = file_get_contents($filepath);
            
            // Check if valid PHP
            $php_valid = strpos($content, '<?php') === 0;
            
            // Check if class definition exists
            $class_found_in_file = preg_match('/class\s+' . preg_quote($expected_class) . '\s*(\{|extends|implements)/i', $content);
        }
        
        echo '<tr>';
        echo '<td><strong>' . $filename . '</strong></td>';
        echo '<td>' . ($exists ? '<span class="status status-ok">✅</span>' : '<span class="status status-error">❌</span>') . '</td>';
        echo '<td>' . ($size > 0 ? number_format($size) . ' bytes' : '-') . '</td>';
        echo '<td>' . ($readable ? '<span class="status status-ok">✅</span>' : '<span class="status status-error">❌</span>') . '</td>';
        echo '<td>' . ($php_valid ? '<span class="status status-ok">✅</span>' : '<span class="status status-error">❌</span>') . '</td>';
        echo '<td>' . ($class_found_in_file ? '<span class="status status-ok">✅</span>' : '<span class="status status-error">❌</span>') . '</td>';
        echo '<td>' . ($class_loaded ? '<span class="status status-ok">✅</span>' : '<span class="status status-error">❌</span>') . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Detailed inspection
    echo '<h2>🔬 Detailed File Inspection</h2>';
    
    foreach ($files_to_check as $filename => $expected_class) {
        $filepath = $includes_path . $filename;
        
        if (!file_exists($filepath)) {
            echo '<div class="file-box error">';
            echo '<h3>❌ ' . $filename . '</h3>';
            echo '<p><strong>Status:</strong> FILE NOT FOUND</p>';
            echo '<p>Path: <code>' . $filepath . '</code></p>';
            echo '</div>';
            continue;
        }
        
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);
        $total_lines = count($lines);
        
        // Analysis
        $has_php_tag = strpos($content, '<?php') !== false;
        $has_class = preg_match('/class\s+' . preg_quote($expected_class) . '/i', $content, $matches);
        $has_closing_tag = strpos($content, '?>') !== false;
        $class_loaded = class_exists($expected_class);
        
        // Check for syntax errors
        $syntax_error = null;
        $temp_file = sys_get_temp_dir() . '/' . $filename . '.tmp';
        file_put_contents($temp_file, $content);
        
        $output = shell_exec('php -l ' . escapeshellarg($temp_file) . ' 2>&1');
        if (strpos($output, 'No syntax errors') === false) {
            $syntax_error = $output;
        }
        
        unlink($temp_file);
        
        // Determine status
        $box_class = 'file-box';
        if (!$has_php_tag || !$has_class || $syntax_error) {
            $box_class .= ' error';
        } elseif ($class_loaded) {
            $box_class .= ' success';
        }
        
        echo '<div class="' . $box_class . '">';
        echo '<h3>' . $filename . ' → ' . $expected_class . '</h3>';
        
        echo '<table style="width: auto; margin: 10px 0;">';
        echo '<tr><td><strong>File Size:</strong></td><td>' . number_format(filesize($filepath)) . ' bytes</td></tr>';
        echo '<tr><td><strong>Total Lines:</strong></td><td>' . $total_lines . '</td></tr>';
        echo '<tr><td><strong>PHP Opening Tag:</strong></td><td>' . ($has_php_tag ? '<span class="status status-ok">✅ Found</span>' : '<span class="status status-error">❌ Missing</span>') . '</td></tr>';
        echo '<tr><td><strong>Class Definition:</strong></td><td>' . ($has_class ? '<span class="status status-ok">✅ Found</span>' : '<span class="status status-error">❌ Missing</span>') . '</td></tr>';
        echo '<tr><td><strong>Closing PHP Tag:</strong></td><td>' . ($has_closing_tag ? '<span class="status status-warning">⚠️ Present (not recommended)</span>' : '<span class="status status-ok">✅ Not present (good)</span>') . '</td></tr>';
        echo '<tr><td><strong>Syntax Check:</strong></td><td>' . ($syntax_error ? '<span class="status status-error">❌ Syntax Error</span>' : '<span class="status status-ok">✅ Valid PHP</span>') . '</td></tr>';
        echo '<tr><td><strong>Class Loaded:</strong></td><td>' . ($class_loaded ? '<span class="status status-ok">✅ Loaded</span>' : '<span class="status status-error">❌ Not Loaded</span>') . '</td></tr>';
        echo '</table>';
        
        if ($syntax_error) {
            echo '<div style="background: #fcebea; padding: 10px; border-radius: 4px; margin: 10px 0;">';
            echo '<strong style="color: #d63638;">Syntax Error Detected:</strong><br>';
            echo '<pre style="background: #fff; color: #d63638;">' . esc_html($syntax_error) . '</pre>';
            echo '</div>';
        }
        
        // Show first 30 lines
        echo '<details style="margin: 15px 0;">';
        echo '<summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f6f7f7; border-radius: 4px;">📄 Show First 30 Lines</summary>';
        echo '<pre style="margin-top: 10px;">';
        
        $show_lines = min(30, $total_lines);
        for ($i = 0; $i < $show_lines; $i++) {
            $line_num = $i + 1;
            $line_content = htmlspecialchars($lines[$i]);
            
            // Highlight important lines
            $highlight = '';
            if (stripos($lines[$i], 'class ' . $expected_class) !== false) {
                $highlight = ' highlight';
            } elseif (stripos($lines[$i], '<?php') !== false) {
                $highlight = ' highlight';
            }
            
            echo '<div class="code-line' . $highlight . '">';
            echo sprintf('%3d | %s', $line_num, $line_content ?: '(empty line)');
            echo '</div>';
        }
        
        if ($total_lines > 30) {
            echo '<div style="color: #787c82; margin-top: 10px;">... (' . ($total_lines - 30) . ' more lines)</div>';
        }
        
        echo '</pre>';
        echo '</details>';
        
        // Try to require and test
        echo '<details style="margin: 15px 0;">';
        echo '<summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #e5f5fa; border-radius: 4px;">🧪 Test Load File</summary>';
        echo '<div style="padding: 15px; background: #f6f7f7; margin-top: 10px; border-radius: 4px;">';
        
        if (!$class_loaded) {
            echo '<p><strong>Attempting to require file...</strong></p>';
            
            ob_start();
            try {
                require_once $filepath;
                
                if (class_exists($expected_class)) {
                    echo '<p style="color: #00a32a;">✅ SUCCESS! Class loaded after require_once.</p>';
                    echo '<p><strong>Methods in class:</strong></p>';
                    echo '<ul>';
                    $methods = get_class_methods($expected_class);
                    foreach (array_slice($methods, 0, 10) as $method) {
                        echo '<li><code>' . $method . '()</code></li>';
                    }
                    if (count($methods) > 10) {
                        echo '<li>... and ' . (count($methods) - 10) . ' more methods</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p style="color: #d63638;">❌ File required but class still not found!</p>';
                    echo '<p>This suggests the class name in the file does not match: <code>' . $expected_class . '</code></p>';
                }
            } catch (ParseError $e) {
                echo '<p style="color: #d63638;">❌ Parse Error: ' . $e->getMessage() . '</p>';
                echo '<p>Line: ' . $e->getLine() . '</p>';
            } catch (Exception $e) {
                echo '<p style="color: #d63638;">❌ Exception: ' . $e->getMessage() . '</p>';
            }
            
            $output = ob_get_clean();
            echo $output;
        } else {
            echo '<p style="color: #00a32a;">✅ Class already loaded in this session.</p>';
        }
        
        echo '</div>';
        echo '</details>';
        
        echo '</div>'; // Close file-box
    }
    
    // Check WordPress debug.log
    echo '<h2>📝 WordPress Debug Log (Last 50 Lines)</h2>';
    
    $debug_log = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($debug_log)) {
        $log_lines = file($debug_log);
        $ai_lines = array_filter($log_lines, function($line) {
            return stripos($line, 'AI Rewriter') !== false || 
                   stripos($line, 'ai_rewriter') !== false ||
                   stripos($line, 'class-') !== false;
        });
        
        if (!empty($ai_lines)) {
            $recent_ai_lines = array_slice($ai_lines, -50);
            echo '<pre>';
            echo esc_html(implode('', $recent_ai_lines));
            echo '</pre>';
        } else {
            echo '<p>No AI Rewriter entries found in debug.log</p>';
            echo '<p style="color: #787c82;">Enable WP_DEBUG and WP_DEBUG_LOG in wp-config.php to see detailed error messages.</p>';
        }
    } else {
        echo '<p style="color: #dba617;">⚠️ Debug log not found at: ' . $debug_log . '</p>';
        echo '<p>Enable debugging in wp-config.php:</p>';
        echo '<pre>define(\'WP_DEBUG\', true);
define(\'WP_DEBUG_LOG\', true);
define(\'WP_DEBUG_DISPLAY\', false);</pre>';
    }
    
    // Actions
    echo '<h2>🔧 Recommended Actions</h2>';
    
    $issues_found = array();
    foreach ($files_to_check as $filename => $expected_class) {
        $filepath = $includes_path . $filename;
        if (!file_exists($filepath)) {
            $issues_found[] = "Missing file: $filename";
        } elseif (!class_exists($expected_class)) {
            $issues_found[] = "Class not loaded: $expected_class from $filename";
        }
    }
    
    if (empty($issues_found)) {
        echo '<div style="background: #d7f0d7; padding: 15px; border-radius: 6px; border-left: 4px solid #00a32a;">';
        echo '<strong style="color: #00500d;">✅ All files OK!</strong><br>';
        echo 'All class files exist and classes are loaded properly.';
        echo '</div>';
    } else {
        echo '<div style="background: #fcebea; padding: 15px; border-radius: 6px; border-left: 4px solid #d63638;">';
        echo '<strong style="color: #5a0001;">❌ Issues Found:</strong>';
        echo '<ol style="margin: 10px 0;">';
        foreach ($issues_found as $issue) {
            echo '<li>' . esc_html($issue) . '</li>';
        }
        echo '</ol>';
        
        echo '<p><strong>Next Steps:</strong></p>';
        echo '<ol>';
        echo '<li>Check detailed inspection above for specific errors</li>';
        echo '<li>Look at WordPress debug.log for detailed error messages</li>';
        echo '<li>If files are corrupt, use the recreate tool below</li>';
        echo '<li>If syntax errors exist, manually fix them or recreate files</li>';
        echo '</ol>';
        echo '</div>';
        
        echo '<p style="margin-top: 20px;">';
        echo '<a href="recreate-classes.php" class="button button-danger">🔧 Recreate All Class Files</a>';
        echo '<a href="?" class="button">🔄 Refresh Check</a>';
        echo '</p>';
    }
    ?>
    
    <hr style="margin: 40px 0; border: none; border-top: 1px solid #dcdcde;">
    
    <p style="color: #787c82; font-size: 12px;">
        <strong>⚠️ Security:</strong> Delete this file after diagnosing the issue!<br>
        File: <code>/wp-content/plugins/tastes/inspect-classes.php</code>
    </p>
    
</div>

</body>
</html>