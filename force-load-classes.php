<?php
/**
 * NUCLEAR OPTION - Force Class Loader
 * 
 * Upload ke: wp-content/plugins/adesurya-tastes/force-load-classes.php
 * Akses via: http://yoursite.com/wp-content/plugins/adesurya-tastes/force-load-classes.php
 * 
 * This will FORCE load classes bypassing ALL caches
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

$action = $_GET['action'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Load Classes</title>
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
        .section {
            padding: 15px;
            margin: 15px 0;
            border-radius: 6px;
            border-left: 4px solid;
        }
        .success { background: #d7f0d7; border-color: #00a32a; color: #00500d; }
        .error { background: #fcebea; border-color: #d63638; color: #5a0001; }
        .warning { background: #fcf3cf; border-color: #dba617; color: #614200; }
        .info { background: #e5f5fa; border-color: #00a0d2; color: #004a5e; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 6px; font-size: 12px; }
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
    <h1>💥 NUCLEAR OPTION - Force Load Classes</h1>
    <p style="color: #787c82;">This will force load classes using PHP's `include` (not `require_once`)</p>
    
    <?php
    $includes_path = WP_PLUGIN_DIR . '/tastes/includes/';
    
    $classes_to_load = array(
        'AI_Rewriter_API' => 'class-ai-api.php',
        'AI_Rewriter_Content_Parser' => 'class-content-parser.php',
        'AI_Rewriter_Logger' => 'class-logger.php',
        'AI_Rewriter_Image_Handler' => 'class-image-handler.php'
    );
    
    if ($action === 'force_load') {
        echo '<div class="section info">';
        echo '<h2>⏳ Force Loading Classes...</h2>';
        
        $results = array();
        $success_count = 0;
        
        foreach ($classes_to_load as $classname => $filename) {
            $filepath = $includes_path . $filename;
            
            echo '<p><strong>' . $classname . ':</strong> ';
            
            if (!file_exists($filepath)) {
                echo '<span style="color: #d63638;">❌ File not found</span></p>';
                $results[$classname] = 'file_not_found';
                continue;
            }
            
            try {
                // NUCLEAR: Use include instead of require_once to bypass OPcache
                ob_start();
                include $filepath;
                $output = ob_get_clean();
                
                // Check if class now exists
                if (class_exists($classname, false)) {
                    echo '<span style="color: #00a32a;">✅ SUCCESS! Class loaded!</span>';
                    
                    // Get method count
                    $methods = get_class_methods($classname);
                    echo ' (' . count($methods) . ' methods)';
                    
                    $results[$classname] = 'success';
                    $success_count++;
                } else {
                    echo '<span style="color: #d63638;">❌ FAILED - Class not found after include</span>';
                    $results[$classname] = 'class_not_found';
                }
                
                if (!empty($output)) {
                    echo '<br><small style="color: #dba617;">⚠️ Warning: File produced output</small>';
                }
                
                echo '</p>';
                
            } catch (ParseError $e) {
                echo '<span style="color: #d63638;">❌ Parse Error: ' . esc_html($e->getMessage()) . '</span></p>';
                $results[$classname] = 'parse_error';
            } catch (Exception $e) {
                echo '<span style="color: #d63638;">❌ Exception: ' . esc_html($e->getMessage()) . '</span></p>';
                $results[$classname] = 'exception';
            }
        }
        
        echo '</div>';
        
        // Summary
        if ($success_count === count($classes_to_load)) {
            echo '<div class="section success">';
            echo '<h2>🎉 COMPLETE SUCCESS!</h2>';
            echo '<p>All ' . $success_count . ' classes loaded successfully!</p>';
            echo '<p><strong>Next Steps:</strong></p>';
            echo '<ol>';
            echo '<li>✅ Classes are now loaded in this session</li>';
            echo '<li>✅ Go to <a href="debug-ai-rewriter-cron.php">debug-ai-rewriter-cron.php</a> to verify</li>';
            echo '<li>⚠️ However, this is TEMPORARY - you need to fix the root cause</li>';
            echo '<li>🔧 Run <a href="diagnose-loading.php">diagnose-loading.php</a> to find root cause</li>';
            echo '</ol>';
            echo '</div>';
            
            echo '<div class="section warning">';
            echo '<h3>⚠️ IMPORTANT: Permanent Fix Needed</h3>';
            echo '<p>Classes loaded successfully NOW, but this is temporary!</p>';
            echo '<p><strong>Root cause is likely:</strong></p>';
            echo '<ul>';
            echo '<li>OPcache caching old file versions → Clear OPcache</li>';
            echo '<li>Object cache (Redis/Memcached) → Flush cache</li>';
            echo '<li>Plugin load order issue → Check main plugin file</li>';
            echo '</ul>';
            echo '<p><a href="diagnose-loading.php" class="button">🔬 Diagnose Root Cause</a></p>';
            echo '</div>';
            
        } elseif ($success_count > 0) {
            echo '<div class="section warning">';
            echo '<h2>⚠️ Partial Success</h2>';
            echo '<p>' . $success_count . ' out of ' . count($classes_to_load) . ' classes loaded.</p>';
            echo '<p>Check failed classes above for error details.</p>';
            echo '</div>';
        } else {
            echo '<div class="section error">';
            echo '<h2>❌ All Failed</h2>';
            echo '<p>No classes could be loaded. This indicates a serious issue.</p>';
            echo '<p><strong>Possible causes:</strong></p>';
            echo '<ul>';
            echo '<li>Files are corrupted or incomplete</li>';
            echo '<li>Syntax errors in PHP code</li>';
            echo '<li>PHP version incompatibility</li>';
            echo '<li>File permissions issue</li>';
            echo '</ul>';
            echo '<p><a href="inspect-classes.php" class="button">🔍 Inspect Files</a></p>';
            echo '</div>';
        }
        
        // Show current class status
        echo '<div class="section info">';
        echo '<h3>📊 Current Class Status</h3>';
        echo '<table style="width: 100%; border-collapse: collapse;">';
        echo '<tr><th style="padding: 10px; text-align: left; border-bottom: 1px solid #dcdcde;">Class</th><th style="padding: 10px; text-align: left; border-bottom: 1px solid #dcdcde;">Exists</th><th style="padding: 10px; text-align: left; border-bottom: 1px solid #dcdcde;">Methods</th></tr>';
        
        foreach ($classes_to_load as $classname => $filename) {
            echo '<tr>';
            echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;">' . $classname . '</td>';
            
            if (class_exists($classname, false)) {
                $methods = get_class_methods($classname);
                echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;"><span style="color: #00a32a;">✅ YES</span></td>';
                echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;">' . count($methods) . ' methods</td>';
            } else {
                echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;"><span style="color: #d63638;">❌ NO</span></td>';
                echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;">-</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</div>';
        
        echo '<p><a href="debug-ai-rewriter-cron.php" class="button">📊 Check Cron Status</a></p>';
        
    } else {
        // Show current status
        echo '<div class="section info">';
        echo '<h2>📊 Current Status</h2>';
        
        echo '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        echo '<tr><th style="padding: 10px; text-align: left; background: #f6f7f7; border-bottom: 1px solid #dcdcde;">Class</th><th style="padding: 10px; text-align: left; background: #f6f7f7; border-bottom: 1px solid #dcdcde;">File</th><th style="padding: 10px; text-align: left; background: #f6f7f7; border-bottom: 1px solid #dcdcde;">File Exists</th><th style="padding: 10px; text-align: left; background: #f6f7f7; border-bottom: 1px solid #dcdcde;">Class Loaded</th></tr>';
        
        $any_missing = false;
        
        foreach ($classes_to_load as $classname => $filename) {
            $filepath = $includes_path . $filename;
            $exists = file_exists($filepath);
            $loaded = class_exists($classname, false);
            
            if (!$loaded) {
                $any_missing = true;
            }
            
            echo '<tr>';
            echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;">' . $classname . '</td>';
            echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;"><code>' . $filename . '</code></td>';
            echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;">' . ($exists ? '<span style="color: #00a32a;">✅</span>' : '<span style="color: #d63638;">❌</span>') . '</td>';
            echo '<td style="padding: 10px; border-bottom: 1px solid #dcdcde;">' . ($loaded ? '<span style="color: #00a32a;">✅ YES</span>' : '<span style="color: #d63638;">❌ NO</span>') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '</div>';
        
        if ($any_missing) {
            echo '<div class="section error">';
            echo '<h3>❌ Problem Detected</h3>';
            echo '<p>Some classes are not loaded even though files exist.</p>';
            echo '<p><strong>This Nuclear Option will:</strong></p>';
            echo '<ul>';
            echo '<li>🔥 Force load files using <code>include</code> (not <code>require_once</code>)</li>';
            echo '<li>🔥 Bypass OPcache and all caching mechanisms</li>';
            echo '<li>🔥 Verify each class is loaded</li>';
            echo '<li>🔥 Report detailed results</li>';
            echo '</ul>';
            echo '<p><strong>⚠️ Warning:</strong> This is a temporary fix. You need to find root cause!</p>';
            echo '</div>';
            
            echo '<p><a href="?action=force_load" class="button button-danger" onclick="return confirm(\'Force load all classes? This will bypass all caching.\')">💥 FORCE LOAD NOW</a></p>';
        } else {
            echo '<div class="section success">';
            echo '<h3>✅ All Classes Loaded</h3>';
            echo '<p>All classes are currently loaded in this session.</p>';
            echo '<p>If your cron is still not working, the issue is elsewhere.</p>';
            echo '<p><a href="debug-ai-rewriter-cron.php" class="button">📊 Check Cron Status</a></p>';
            echo '</div>';
        }
    }
    ?>
    
    <hr style="margin: 40px 0; border: none; border-top: 1px solid #dcdcde;">
    
    <h3>📖 About This Tool</h3>
    
    <div class="section info">
        <p><strong>What is the "Nuclear Option"?</strong></p>
        <p>This tool uses PHP's <code>include</code> statement instead of <code>require_once</code>. This:</p>
        <ul>
            <li>✅ Bypasses OPcache (opcode cache)</li>
            <li>✅ Forces fresh file read</li>
            <li>✅ Ignores previous include attempts</li>
            <li>✅ Works even with aggressive caching</li>
        </ul>
        
        <p><strong>When to use this?</strong></p>
        <ul>
            <li>When files exist but classes don't load</li>
            <li>When OPcache is causing issues</li>
            <li>When server restart is not possible</li>
            <li>For immediate temporary fix</li>
        </ul>
        
        <p><strong>Important:</strong></p>
        <ul>
            <li>⚠️ This is a TEMPORARY fix for current session</li>
            <li>⚠️ You MUST fix root cause (usually cache)</li>
            <li>⚠️ Run diagnostics to find permanent solution</li>
        </ul>
    </div>
    
    <p style="color: #787c82; font-size: 12px; margin-top: 40px;">
        <strong>⚠️ Security:</strong> Delete this file after fixing!<br>
        File: <code>/wp-content/plugins/adesurya-tastes/force-load-classes.php</code>
    </p>
    
</div>

</body>
</html>