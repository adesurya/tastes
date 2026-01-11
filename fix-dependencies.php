<?php
/**
 * AI Rewriter Dependencies Fixer
 * 
 * Upload ke: wp-content/plugins/adesurya-tastes/fix-dependencies.php
 * Akses via: http://yoursite.com/wp-content/plugins/adesurya-tastes/fix-dependencies.php
 * 
 * Tool untuk diagnose dan fix masalah dependencies yang tidak ter-load
 */

require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Rewriter Dependencies Fixer</title>
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
        h1 { color: #d63638; border-bottom: 3px solid #d63638; padding-bottom: 10px; }
        h2 { color: #2271b1; margin-top: 30px; }
        .status { padding: 15px; margin: 15px 0; border-radius: 6px; border-left: 4px solid; }
        .success { background: #d7f0d7; border-color: #00a32a; color: #00500d; }
        .error { background: #fcebea; border-color: #d63638; color: #5a0001; }
        .warning { background: #fcf3cf; border-color: #dba617; color: #614200; }
        .info { background: #e5f5fa; border-color: #00a0d2; color: #004a5e; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #dcdcde; }
        th { background: #f6f7f7; font-weight: 600; }
        tr:hover { background: #f6f7f7; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; overflow-x: auto; border-radius: 6px; font-size: 12px; }
        .button { background: #2271b1; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #135e96; }
        .button-danger { background: #d63638; }
        .button-danger:hover { background: #b32d2e; }
        .code { background: #f6f7f7; padding: 3px 6px; border-radius: 3px; font-family: Consolas, Monaco, monospace; font-size: 13px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #00a32a; color: white; }
        .badge-error { background: #d63638; color: white; }
        .badge-warning { background: #dba617; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 AI Rewriter Dependencies Fixer</h1>
    
    <?php
    // Define required files and their classes
    $required_files = array(
        'class-ai-api.php' => 'AI_Rewriter_API',
        'class-content-parser.php' => 'AI_Rewriter_Content_Parser',
        'class-logger.php' => 'AI_Rewriter_Logger',
        'class-image-handler.php' => 'AI_Rewriter_Image_Handler',
        'class-cron-scheduler.php' => 'AI_Rewriter_Cron_Scheduler'
    );
    
    // Possible plugin paths
    $possible_paths = array(
        WP_PLUGIN_DIR . '/adesurya-tastes/',
        WP_PLUGIN_DIR . '/ai-article-rewriter/',
        WP_PLUGIN_DIR . '/ai-article-rewriter-enhanced/',
        defined('AI_REWRITER_PLUGIN_PATH') ? AI_REWRITER_PLUGIN_PATH : ''
    );
    
    // Filter empty paths
    $possible_paths = array_filter($possible_paths);
    
    echo '<h2>🔍 Step 1: Locating Plugin Directory</h2>';
    
    $plugin_path = '';
    $includes_path = '';
    
    foreach ($possible_paths as $path) {
        if (is_dir($path)) {
            echo '<div class="status success">';
            echo '<strong>✅ Found plugin directory:</strong><br>';
            echo '<code class="code">' . $path . '</code>';
            echo '</div>';
            
            $plugin_path = $path;
            $includes_path = $path . 'includes/';
            break;
        }
    }
    
    if (empty($plugin_path)) {
        echo '<div class="status error">';
        echo '<strong>❌ Plugin directory not found!</strong><br>';
        echo 'Searched paths:<br>';
        foreach ($possible_paths as $path) {
            echo '- <code class="code">' . $path . '</code> (not found)<br>';
        }
        echo '</div>';
        echo '<div class="status warning">';
        echo '<strong>Manual Action Required:</strong><br>';
        echo '1. Check plugin is uploaded to <code class="code">wp-content/plugins/</code><br>';
        echo '2. Verify plugin folder name matches one of the expected names above<br>';
        echo '3. Check folder permissions (should be 755)';
        echo '</div>';
        echo '</div></body></html>';
        exit;
    }
    
    echo '<h2>📁 Step 2: Checking Includes Directory</h2>';
    
    if (!is_dir($includes_path)) {
        echo '<div class="status error">';
        echo '<strong>❌ Includes directory not found!</strong><br>';
        echo 'Expected path: <code class="code">' . $includes_path . '</code>';
        echo '</div>';
        
        // Try to create it
        echo '<div class="status info">';
        echo '<strong>Attempting to create includes directory...</strong><br>';
        if (wp_mkdir_p($includes_path)) {
            echo '✅ Directory created successfully!';
        } else {
            echo '❌ Failed to create directory. Please create manually via FTP.';
        }
        echo '</div>';
    } else {
        echo '<div class="status success">';
        echo '<strong>✅ Includes directory exists:</strong><br>';
        echo '<code class="code">' . $includes_path . '</code>';
        echo '</div>';
    }
    
    echo '<h2>📄 Step 3: Checking Required Files</h2>';
    
    $file_status = array();
    
    echo '<table>';
    echo '<tr><th>File Name</th><th>Status</th><th>Size</th><th>Permissions</th><th>Class</th></tr>';
    
    foreach ($required_files as $filename => $classname) {
        $filepath = $includes_path . $filename;
        $exists = file_exists($filepath);
        $readable = is_readable($filepath);
        $size = $exists ? filesize($filepath) : 0;
        $perms = $exists ? substr(sprintf('%o', fileperms($filepath)), -4) : 'N/A';
        $class_exists_check = class_exists($classname);
        
        $file_status[$filename] = array(
            'exists' => $exists,
            'readable' => $readable,
            'size' => $size,
            'perms' => $perms,
            'class_loaded' => $class_exists_check,
            'path' => $filepath
        );
        
        echo '<tr>';
        echo '<td><code class="code">' . $filename . '</code></td>';
        echo '<td>';
        if ($exists && $readable && $size > 100) {
            echo '<span class="badge badge-success">✅ OK</span>';
        } elseif ($exists && $size < 100) {
            echo '<span class="badge badge-warning">⚠️ Too Small</span>';
        } elseif ($exists && !$readable) {
            echo '<span class="badge badge-error">❌ Not Readable</span>';
        } else {
            echo '<span class="badge badge-error">❌ Missing</span>';
        }
        echo '</td>';
        echo '<td>' . ($exists ? number_format($size) . ' bytes' : '-') . '</td>';
        echo '<td>' . $perms . '</td>';
        echo '<td>';
        if ($class_exists_check) {
            echo '<span class="badge badge-success">✅ Loaded</span>';
        } else {
            echo '<span class="badge badge-error">❌ Not Loaded</span>';
        }
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Handle auto-fix
    if (isset($_GET['auto_fix'])) {
        echo '<h2>🔧 Step 4: Auto-Fixing Dependencies</h2>';
        
        echo '<div class="status info"><strong>⏳ Loading dependencies manually...</strong></div>';
        
        $loaded_count = 0;
        $errors = array();
        
        foreach ($required_files as $filename => $classname) {
            $filepath = $includes_path . $filename;
            
            if (file_exists($filepath) && !class_exists($classname)) {
                try {
                    require_once $filepath;
                    
                    if (class_exists($classname)) {
                        echo '<div class="status success">✅ Loaded: <code class="code">' . $classname . '</code></div>';
                        $loaded_count++;
                    } else {
                        $errors[] = "File loaded but class $classname not found in $filename";
                        echo '<div class="status error">❌ Failed to load class: <code class="code">' . $classname . '</code></div>';
                    }
                } catch (Exception $e) {
                    $errors[] = "Error loading $filename: " . $e->getMessage();
                    echo '<div class="status error">❌ Error loading <code class="code">' . $filename . '</code>: ' . $e->getMessage() . '</div>';
                } catch (ParseError $e) {
                    $errors[] = "Parse error in $filename: " . $e->getMessage();
                    echo '<div class="status error">❌ Parse error in <code class="code">' . $filename . '</code>: ' . $e->getMessage() . '</div>';
                }
            } elseif (class_exists($classname)) {
                echo '<div class="status success">✅ Already loaded: <code class="code">' . $classname . '</code></div>';
                $loaded_count++;
            }
        }
        
        echo '<div class="status ' . ($loaded_count == count($required_files) ? 'success' : 'warning') . '">';
        echo '<strong>Result:</strong> ' . $loaded_count . ' / ' . count($required_files) . ' classes loaded';
        echo '</div>';
        
        if (!empty($errors)) {
            echo '<div class="status error">';
            echo '<strong>Errors encountered:</strong><ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        }
    }
    
    // Check if any files are missing or problematic
    $missing_files = array();
    $small_files = array();
    $unreadable_files = array();
    $classes_not_loaded = array();
    
    foreach ($file_status as $filename => $status) {
        if (!$status['exists']) {
            $missing_files[] = $filename;
        } elseif ($status['size'] < 100) {
            $small_files[] = $filename;
        } elseif (!$status['readable']) {
            $unreadable_files[] = $filename;
        }
        
        if (!$status['class_loaded']) {
            $classes_not_loaded[] = $required_files[$filename];
        }
    }
    
    echo '<h2>📋 Summary & Actions</h2>';
    
    if (empty($missing_files) && empty($small_files) && empty($unreadable_files) && empty($classes_not_loaded)) {
        echo '<div class="status success">';
        echo '<strong>🎉 All dependencies are OK!</strong><br>';
        echo 'All required files exist and classes are loaded properly.';
        echo '</div>';
    } else {
        // Show issues
        if (!empty($missing_files)) {
            echo '<div class="status error">';
            echo '<strong>❌ Missing Files (' . count($missing_files) . '):</strong><ul>';
            foreach ($missing_files as $file) {
                echo '<li><code class="code">' . $file . '</code></li>';
            }
            echo '</ul>';
            echo '<strong>Action:</strong> These files need to be uploaded to <code class="code">' . $includes_path . '</code>';
            echo '</div>';
        }
        
        if (!empty($small_files)) {
            echo '<div class="status warning">';
            echo '<strong>⚠️ Suspiciously Small Files (' . count($small_files) . '):</strong><ul>';
            foreach ($small_files as $file) {
                echo '<li><code class="code">' . $file . '</code> (' . $file_status[$file]['size'] . ' bytes)</li>';
            }
            echo '</ul>';
            echo '<strong>Action:</strong> These files might be empty or corrupted. Check file content.';
            echo '</div>';
        }
        
        if (!empty($unreadable_files)) {
            echo '<div class="status error">';
            echo '<strong>❌ Unreadable Files (' . count($unreadable_files) . '):</strong><ul>';
            foreach ($unreadable_files as $file) {
                echo '<li><code class="code">' . $file . '</code></li>';
            }
            echo '</ul>';
            echo '<strong>Action:</strong> Fix permissions with: <code class="code">chmod 644 ' . $includes_path . '*.php</code>';
            echo '</div>';
        }
        
        if (!empty($classes_not_loaded)) {
            echo '<div class="status error">';
            echo '<strong>❌ Classes Not Loaded (' . count($classes_not_loaded) . '):</strong><ul>';
            foreach ($classes_not_loaded as $class) {
                echo '<li><code class="code">' . $class . '</code></li>';
            }
            echo '</ul>';
            echo '<strong>Action:</strong> Try auto-fix below, or check if main plugin file loads these dependencies.';
            echo '</div>';
        }
        
        // Auto-fix button
        if (!empty($classes_not_loaded) && empty($missing_files)) {
            echo '<div class="status info">';
            echo '<strong>🔧 Auto-Fix Available:</strong><br>';
            echo 'Files exist but classes not loaded. Try manual loading:<br><br>';
            echo '<a href="?auto_fix=1" class="button">🔧 Auto-Fix Dependencies</a>';
            echo '</div>';
        }
    }
    
    // Check main plugin file
    echo '<h2>🔍 Step 5: Main Plugin File Check</h2>';
    
    $main_plugin_files = array(
        $plugin_path . 'ai-article-rewriter.php',
        $plugin_path . 'adesurya-tastes.php',
        $plugin_path . 'plugin.php',
        $plugin_path . 'index.php'
    );
    
    $main_file = '';
    foreach ($main_plugin_files as $file) {
        if (file_exists($file)) {
            $main_file = $file;
            break;
        }
    }
    
    if ($main_file) {
        echo '<div class="status success">';
        echo '<strong>✅ Main plugin file found:</strong><br>';
        echo '<code class="code">' . basename($main_file) . '</code>';
        echo '</div>';
        
        // Check if it loads dependencies
        $content = file_get_contents($main_file);
        $has_load_deps = strpos($content, 'load_dependencies') !== false;
        $has_require_cron = strpos($content, 'class-cron-scheduler.php') !== false;
        
        echo '<table>';
        echo '<tr><th>Check</th><th>Status</th></tr>';
        echo '<tr><td>Has <code class="code">load_dependencies()</code> method</td><td>';
        echo $has_load_deps ? '<span class="badge badge-success">✅ Yes</span>' : '<span class="badge badge-error">❌ No</span>';
        echo '</td></tr>';
        echo '<tr><td>Loads <code class="code">class-cron-scheduler.php</code></td><td>';
        echo $has_require_cron ? '<span class="badge badge-success">✅ Yes</span>' : '<span class="badge badge-error">❌ No</span>';
        echo '</td></tr>';
        echo '</table>';
        
        if (!$has_load_deps) {
            echo '<div class="status error">';
            echo '<strong>❌ Missing load_dependencies() method!</strong><br>';
            echo 'Main plugin file tidak meload dependency files.<br><br>';
            echo '<strong>Solution:</strong> Add this to your main plugin file:<br>';
            echo '<pre>public function load_dependencies() {
    $includes_path = plugin_dir_path(__FILE__) . \'includes/\';
    
    $required_files = array(
        \'class-ai-api.php\',
        \'class-content-parser.php\',
        \'class-logger.php\',
        \'class-image-handler.php\',
        \'class-cron-scheduler.php\'
    );
    
    foreach ($required_files as $file) {
        $file_path = $includes_path . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}</pre>';
            echo '</div>';
        }
    } else {
        echo '<div class="status error">';
        echo '<strong>❌ Main plugin file not found!</strong><br>';
        echo 'Searched for:<ul>';
        foreach ($main_plugin_files as $file) {
            echo '<li><code class="code">' . basename($file) . '</code></li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    
    // Show directory structure
    echo '<h2>📂 Step 6: Current Directory Structure</h2>';
    
    if (is_dir($includes_path)) {
        $files = scandir($includes_path);
        echo '<div class="status info">';
        echo '<strong>Files in includes/ directory:</strong><br>';
        echo '<ul>';
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $filepath = $includes_path . $file;
                $size = filesize($filepath);
                $is_php = pathinfo($file, PATHINFO_EXTENSION) === 'php';
                
                echo '<li>';
                echo '<code class="code">' . $file . '</code> ';
                echo '(' . number_format($size) . ' bytes)';
                if ($is_php && $size < 100) {
                    echo ' <span class="badge badge-warning">⚠️ TOO SMALL</span>';
                }
                echo '</li>';
            }
        }
        echo '</ul>';
        echo '</div>';
    }
    
    // Final recommendations
    echo '<h2>✅ Recommended Actions</h2>';
    
    $actions = array();
    
    if (!empty($missing_files)) {
        $actions[] = array(
            'priority' => 'HIGH',
            'action' => 'Upload missing files',
            'files' => $missing_files,
            'location' => $includes_path
        );
    }
    
    if (!empty($small_files)) {
        $actions[] = array(
            'priority' => 'HIGH',
            'action' => 'Re-upload corrupted files',
            'files' => $small_files,
            'location' => $includes_path
        );
    }
    
    if (!empty($unreadable_files)) {
        $actions[] = array(
            'priority' => 'MEDIUM',
            'action' => 'Fix file permissions',
            'command' => 'chmod 644 ' . $includes_path . '*.php'
        );
    }
    
    if (!empty($classes_not_loaded) && empty($missing_files) && empty($small_files)) {
        $actions[] = array(
            'priority' => 'HIGH',
            'action' => 'Try auto-fix or check plugin initialization',
            'button' => true
        );
    }
    
    if (!empty($actions)) {
        echo '<ol>';
        foreach ($actions as $action) {
            echo '<li><strong style="color: ' . ($action['priority'] == 'HIGH' ? '#d63638' : '#dba617') . '">[' . $action['priority'] . ']</strong> ';
            echo $action['action'];
            
            if (isset($action['files'])) {
                echo '<br>Files: <code class="code">' . implode(', ', $action['files']) . '</code>';
                echo '<br>Upload to: <code class="code">' . $action['location'] . '</code>';
            }
            
            if (isset($action['command'])) {
                echo '<br>Command: <code class="code">' . $action['command'] . '</code>';
            }
            
            if (isset($action['button']) && $action['button']) {
                echo '<br><a href="?auto_fix=1" class="button" style="margin-top: 10px;">🔧 Auto-Fix Now</a>';
            }
            
            echo '</li>';
        }
        echo '</ol>';
    } else {
        echo '<div class="status success">';
        echo '<strong>🎉 No actions needed!</strong><br>';
        echo 'All dependencies are properly loaded and working.';
        echo '</div>';
    }
    
    ?>
    
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">
    
    <p style="color: #787c82; font-size: 12px;">
        <strong>⚠️ HAPUS FILE INI</strong> setelah dependencies fixed!<br>
        File: <code class="code">/wp-content/plugins/adesurya-tastes/fix-dependencies.php</code>
    </p>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter'); ?>" class="button">← Back to Plugin</a>
        <a href="?" class="button">🔄 Refresh Check</a>
    </p>
    
</div>

</body>
</html>