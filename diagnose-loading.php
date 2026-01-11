<?php
/**
 * Advanced Class Loading Diagnostic Tool
 * 
 * Upload ke: wp-content/plugins/adesurya-tastes/diagnose-loading.php
 * Akses via: http://yoursite.com/wp-content/plugins/adesurya-tastes/diagnose-loading.php
 * 
 * Diagnoses cache, autoloader, and loading order issues
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
    <title>Advanced Loading Diagnostics</title>
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
        .section {
            background: #f6f7f7;
            border: 2px solid #dcdcde;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .section.success { border-color: #00a32a; background: #d7f0d7; }
        .section.error { border-color: #d63638; background: #fcebea; }
        .section.warning { border-color: #dba617; background: #fcf3cf; }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            overflow-x: auto;
            border-radius: 6px;
            font-size: 12px;
            max-height: 400px;
        }
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
        .status-ok { color: #00a32a; font-weight: 600; }
        .status-error { color: #d63638; font-weight: 600; }
        .status-warning { color: #dba617; font-weight: 600; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔬 Advanced Class Loading Diagnostics</h1>
    <p style="color: #787c82;">Deep diagnosis for cache, autoloader, and loading order issues</p>
    
    <?php
    $includes_path = WP_PLUGIN_DIR . '/adesurya-tastes/includes/';
    
    $classes_to_check = array(
        'AI_Rewriter_API' => 'class-ai-api.php',
        'AI_Rewriter_Content_Parser' => 'class-content-parser.php',
        'AI_Rewriter_Logger' => 'class-logger.php',
        'AI_Rewriter_Image_Handler' => 'class-image-handler.php'
    );
    
    // ACTION: Clear OPcache
    if ($action === 'clear_opcache') {
        echo '<div class="section warning">';
        echo '<h3>🔄 Clearing OPcache...</h3>';
        
        if (function_exists('opcache_reset')) {
            $result = opcache_reset();
            if ($result) {
                echo '<p class="status-ok">✅ OPcache cleared successfully!</p>';
                echo '<p>Refresh this page to see if classes load now.</p>';
            } else {
                echo '<p class="status-error">❌ Failed to clear OPcache</p>';
            }
        } else {
            echo '<p class="status-warning">⚠️ OPcache not available or not enabled</p>';
        }
        
        echo '<p><a href="?" class="button">← Back to Diagnostics</a></p>';
        echo '</div>';
    }
    
    // ACTION: Force reload classes
    elseif ($action === 'force_reload') {
        echo '<div class="section warning">';
        echo '<h3>🔄 Force Reloading Classes...</h3>';
        
        foreach ($classes_to_check as $classname => $filename) {
            $filepath = $includes_path . $filename;
            
            echo '<p><strong>' . $classname . ':</strong> ';
            
            if (!file_exists($filepath)) {
                echo '<span class="status-error">❌ File not found</span></p>';
                continue;
            }
            
            // Force include
            try {
                include $filepath; // Use include instead of require_once to force reload
                
                if (class_exists($classname)) {
                    echo '<span class="status-ok">✅ Loaded successfully!</span></p>';
                } else {
                    echo '<span class="status-error">❌ Class still not found after include</span></p>';
                }
            } catch (Exception $e) {
                echo '<span class="status-error">❌ Error: ' . esc_html($e->getMessage()) . '</span></p>';
            }
        }
        
        echo '<p style="margin-top: 20px;"><a href="?" class="button">← Back to Diagnostics</a></p>';
        echo '</div>';
    }
    
    // MAIN DIAGNOSTICS
    else {
        
        // 1. PHP Environment Check
        echo '<h2>1️⃣ PHP Environment</h2>';
        echo '<div class="section">';
        
        echo '<table>';
        echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
        
        $php_version = PHP_VERSION;
        $php_ok = version_compare($php_version, '7.4', '>=');
        echo '<tr>';
        echo '<td>PHP Version</td>';
        echo '<td>' . $php_version . '</td>';
        echo '<td>' . ($php_ok ? '<span class="status-ok">✅ OK</span>' : '<span class="status-error">❌ Too old</span>') . '</td>';
        echo '</tr>';
        
        $memory_limit = ini_get('memory_limit');
        echo '<tr>';
        echo '<td>Memory Limit</td>';
        echo '<td>' . $memory_limit . '</td>';
        echo '<td><span class="status-ok">✅</span></td>';
        echo '</tr>';
        
        $max_execution = ini_get('max_execution_time');
        echo '<tr>';
        echo '<td>Max Execution Time</td>';
        echo '<td>' . $max_execution . 's</td>';
        echo '<td><span class="status-ok">✅</span></td>';
        echo '</tr>';
        
        $display_errors = ini_get('display_errors');
        echo '<tr>';
        echo '<td>Display Errors</td>';
        echo '<td>' . ($display_errors ? 'On' : 'Off') . '</td>';
        echo '<td>' . ($display_errors ? '<span class="status-warning">⚠️ Should be Off</span>' : '<span class="status-ok">✅ OK</span>') . '</td>';
        echo '</tr>';
        
        echo '</table>';
        
        echo '</div>';
        
        // 2. OPcache Status
        echo '<h2>2️⃣ OPcache Status</h2>';
        echo '<div class="section">';
        
        if (function_exists('opcache_get_status')) {
            $opcache_status = opcache_get_status(false);
            
            if ($opcache_status && $opcache_status['opcache_enabled']) {
                echo '<p class="status-warning">⚠️ <strong>OPcache is ENABLED</strong></p>';
                echo '<p>This might cache old versions of your class files!</p>';
                
                echo '<table>';
                echo '<tr><th>Metric</th><th>Value</th></tr>';
                echo '<tr><td>Enabled</td><td>YES</td></tr>';
                echo '<tr><td>Cache Full</td><td>' . ($opcache_status['cache_full'] ? 'YES' : 'NO') . '</td></tr>';
                echo '<tr><td>Cached Scripts</td><td>' . $opcache_status['opcache_statistics']['num_cached_scripts'] . '</td></tr>';
                echo '<tr><td>Hits</td><td>' . number_format($opcache_status['opcache_statistics']['hits']) . '</td></tr>';
                echo '<tr><td>Misses</td><td>' . number_format($opcache_status['opcache_statistics']['misses']) . '</td></tr>';
                echo '</table>';
                
                echo '<p><strong>Solution:</strong></p>';
                echo '<ul>';
                echo '<li>Clear OPcache using button below</li>';
                echo '<li>Or restart PHP-FPM/Apache</li>';
                echo '<li>Or wait for cache to expire</li>';
                echo '</ul>';
                
                echo '<p><a href="?action=clear_opcache" class="button button-danger">🧹 Clear OPcache Now</a></p>';
            } else {
                echo '<p class="status-ok">✅ OPcache is disabled or not installed</p>';
            }
        } else {
            echo '<p class="status-ok">✅ OPcache not available (not an issue)</p>';
        }
        
        echo '</div>';
        
        // 3. WordPress Object Cache
        echo '<h2>3️⃣ WordPress Object Cache</h2>';
        echo '<div class="section">';
        
        global $wp_object_cache;
        
        if (is_object($wp_object_cache)) {
            echo '<table>';
            echo '<tr><th>Property</th><th>Value</th></tr>';
            echo '<tr><td>Cache Type</td><td>' . get_class($wp_object_cache) . '</td></tr>';
            
            $using_persistent = method_exists($wp_object_cache, 'redis_status') || 
                              method_exists($wp_object_cache, 'memcache_status') ||
                              get_class($wp_object_cache) !== 'WP_Object_Cache';
            
            if ($using_persistent) {
                echo '<tr><td>Persistent Cache</td><td><span class="status-warning">⚠️ YES (Redis/Memcached)</span></td></tr>';
                echo '</table>';
                
                echo '<p><strong>Persistent cache might cache class loading!</strong></p>';
                echo '<p><strong>Solutions:</strong></p>';
                echo '<ul>';
                echo '<li>Flush Redis/Memcached cache</li>';
                echo '<li>Restart cache server</li>';
                echo '<li>Or temporarily disable object cache</li>';
                echo '</ul>';
            } else {
                echo '<tr><td>Persistent Cache</td><td><span class="status-ok">✅ NO (Default WP cache)</span></td></tr>';
                echo '</table>';
            }
        } else {
            echo '<p class="status-ok">✅ Object cache not available</p>';
        }
        
        echo '</div>';
        
        // 4. Autoloader Check
        echo '<h2>4️⃣ Autoloader Check</h2>';
        echo '<div class="section">';
        
        $autoloaders = spl_autoload_functions();
        
        if (!empty($autoloaders)) {
            echo '<p class="status-warning">⚠️ <strong>' . count($autoloaders) . ' autoloader(s) registered</strong></p>';
            echo '<p>Autoloaders might interfere with class loading</p>';
            
            echo '<table>';
            echo '<tr><th>#</th><th>Autoloader</th></tr>';
            
            foreach ($autoloaders as $index => $autoloader) {
                echo '<tr>';
                echo '<td>' . ($index + 1) . '</td>';
                echo '<td>';
                
                if (is_array($autoloader)) {
                    if (is_object($autoloader[0])) {
                        echo get_class($autoloader[0]) . '::' . $autoloader[1];
                    } else {
                        echo $autoloader[0] . '::' . $autoloader[1];
                    }
                } elseif (is_string($autoloader)) {
                    echo $autoloader;
                } else {
                    echo 'Closure';
                }
                
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<p class="status-ok">✅ No autoloaders registered</p>';
        }
        
        echo '</div>';
        
        // 5. Class Conflict Check
        echo '<h2>5️⃣ Class Name Conflict Check</h2>';
        echo '<div class="section">';
        
        echo '<p>Checking if another plugin/theme already registered these class names...</p>';
        
        echo '<table>';
        echo '<tr><th>Class Name</th><th>Already Exists?</th><th>Source</th></tr>';
        
        foreach ($classes_to_check as $classname => $filename) {
            echo '<tr>';
            echo '<td>' . $classname . '</td>';
            
            if (class_exists($classname, false)) {
                echo '<td><span class="status-warning">⚠️ YES</span></td>';
                
                // Try to get class source file
                $reflection = new ReflectionClass($classname);
                $source_file = $reflection->getFileName();
                
                echo '<td>';
                if ($source_file) {
                    $relative_path = str_replace(ABSPATH, '', $source_file);
                    echo '<code>' . $relative_path . '</code>';
                    
                    if (strpos($source_file, 'adesurya-tastes') !== false) {
                        echo '<br><span class="status-ok">✅ From your plugin</span>';
                    } else {
                        echo '<br><span class="status-error">❌ From different location!</span>';
                    }
                } else {
                    echo 'Unknown source';
                }
                echo '</td>';
            } else {
                echo '<td><span class="status-error">❌ NO</span></td>';
                echo '<td>Not loaded</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '</div>';
        
        // 6. File Loading Test
        echo '<h2>6️⃣ Direct File Loading Test</h2>';
        echo '<div class="section">';
        
        echo '<p>Testing if files can be loaded directly...</p>';
        
        echo '<table>';
        echo '<tr><th>File</th><th>Exists</th><th>Readable</th><th>Size</th><th>Can Include</th><th>Class After Include</th></tr>';
        
        foreach ($classes_to_check as $classname => $filename) {
            $filepath = $includes_path . $filename;
            
            echo '<tr>';
            echo '<td>' . $filename . '</td>';
            
            $exists = file_exists($filepath);
            echo '<td>' . ($exists ? '<span class="status-ok">✅</span>' : '<span class="status-error">❌</span>') . '</td>';
            
            $readable = is_readable($filepath);
            echo '<td>' . ($readable ? '<span class="status-ok">✅</span>' : '<span class="status-error">❌</span>') . '</td>';
            
            if ($exists) {
                $size = filesize($filepath);
                echo '<td>' . number_format($size) . ' bytes</td>';
                
                // Try to include
                $can_include = false;
                $class_loaded = false;
                
                try {
                    // Capture any output
                    ob_start();
                    include $filepath;
                    $output = ob_get_clean();
                    
                    $can_include = true;
                    $class_loaded = class_exists($classname, false);
                    
                    echo '<td><span class="status-ok">✅ YES</span>';
                    if (!empty($output)) {
                        echo '<br><small style="color: #dba617;">⚠️ File has output!</small>';
                    }
                    echo '</td>';
                    
                    echo '<td>';
                    if ($class_loaded) {
                        echo '<span class="status-ok">✅ LOADED!</span>';
                    } else {
                        echo '<span class="status-error">❌ Not loaded</span>';
                    }
                    echo '</td>';
                    
                } catch (ParseError $e) {
                    echo '<td><span class="status-error">❌ Parse Error</span></td>';
                    echo '<td>' . esc_html($e->getMessage()) . '</td>';
                } catch (Exception $e) {
                    echo '<td><span class="status-error">❌ Error</span></td>';
                    echo '<td>' . esc_html($e->getMessage()) . '</td>';
                }
            } else {
                echo '<td>-</td>';
                echo '<td>-</td>';
                echo '<td>-</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '</div>';
        
        // 7. Plugin Loading Order
        echo '<h2>7️⃣ Plugin Loading Order</h2>';
        echo '<div class="section">';
        
        $active_plugins = get_option('active_plugins', array());
        
        echo '<p>Your plugin loading order (first to last):</p>';
        echo '<ol>';
        
        $your_plugin_position = 0;
        foreach ($active_plugins as $index => $plugin) {
            $plugin_name = dirname($plugin);
            
            if ($plugin_name === 'adesurya-tastes' || strpos($plugin, 'ai-article-rewriter') !== false) {
                echo '<li><strong style="color: #2271b1;">' . $plugin . ' ← YOUR PLUGIN</strong></li>';
                $your_plugin_position = $index + 1;
            } else {
                echo '<li>' . $plugin . '</li>';
            }
        }
        
        echo '</ol>';
        
        if ($your_plugin_position > 10) {
            echo '<p class="status-warning">⚠️ Your plugin loads late in the sequence (position ' . $your_plugin_position . ')</p>';
            echo '<p>Consider moving it earlier by renaming folder to start with "0-" or "aa-"</p>';
        } else {
            echo '<p class="status-ok">✅ Plugin loads early enough (position ' . $your_plugin_position . ')</p>';
        }
        
        echo '</div>';
        
        // 8. WordPress Debug Info
        echo '<h2>8️⃣ WordPress Debug Status</h2>';
        echo '<div class="section">';
        
        echo '<table>';
        echo '<tr><th>Constant</th><th>Value</th></tr>';
        echo '<tr><td>WP_DEBUG</td><td>' . (defined('WP_DEBUG') && WP_DEBUG ? '<span class="status-ok">✅ Enabled</span>' : '<span class="status-warning">⚠️ Disabled</span>') . '</td></tr>';
        echo '<tr><td>WP_DEBUG_LOG</td><td>' . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '<span class="status-ok">✅ Enabled</span>' : '<span class="status-warning">⚠️ Disabled</span>') . '</td></tr>';
        echo '<tr><td>WP_DEBUG_DISPLAY</td><td>' . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? '<span class="status-warning">⚠️ Enabled</span>' : '<span class="status-ok">✅ Disabled</span>') . '</td></tr>';
        echo '<tr><td>SCRIPT_DEBUG</td><td>' . (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'Enabled' : 'Disabled') . '</td></tr>';
        echo '</table>';
        
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            echo '<p class="status-warning">⚠️ <strong>WP_DEBUG is disabled</strong></p>';
            echo '<p>Enable it to see detailed error messages:</p>';
            echo '<pre>define(\'WP_DEBUG\', true);
define(\'WP_DEBUG_LOG\', true);
define(\'WP_DEBUG_DISPLAY\', false);</pre>';
        }
        
        echo '</div>';
        
        // 9. Recommendations
        echo '<h2>9️⃣ Recommended Actions</h2>';
        echo '<div class="section warning">';
        
        echo '<p><strong>Based on diagnostics, try these solutions in order:</strong></p>';
        echo '<ol>';
        
        // Check if OPcache is the likely culprit
        if (function_exists('opcache_get_status')) {
            $opcache_status = opcache_get_status(false);
            if ($opcache_status && $opcache_status['opcache_enabled']) {
                echo '<li><strong>🔥 MOST LIKELY FIX:</strong> Clear OPcache';
                echo ' <a href="?action=clear_opcache" class="button button-danger">Clear Now</a></li>';
            }
        }
        
        echo '<li>Force reload classes <a href="?action=force_reload" class="button">Force Reload</a></li>';
        echo '<li>Restart PHP-FPM or Apache server</li>';
        echo '<li>Temporarily disable other plugins to test for conflicts</li>';
        echo '<li>Check WordPress debug.log for detailed errors</li>';
        echo '<li>If persistent cache is active (Redis/Memcached), flush it</li>';
        echo '</ol>';
        
        echo '</div>';
        
        // 10. Quick Actions
        echo '<h2>🚀 Quick Actions</h2>';
        echo '<p>';
        echo '<a href="?action=clear_opcache" class="button button-danger">🧹 Clear OPcache</a>';
        echo '<a href="?action=force_reload" class="button">🔄 Force Reload Classes</a>';
        echo '<a href="debug-ai-rewriter-cron.php" class="button">📊 Main Debug Tool</a>';
        echo '<a href="?" class="button">🔄 Refresh Diagnostics</a>';
        echo '</p>';
    }
    ?>
    
    <hr style="margin: 40px 0; border: none; border-top: 1px solid #dcdcde;">
    
    <p style="color: #787c82; font-size: 12px;">
        <strong>⚠️ Security:</strong> Delete this file after diagnosing!<br>
        File: <code>/wp-content/plugins/adesurya-tastes/diagnose-loading.php</code>
    </p>
    
</div>

</body>
</html>