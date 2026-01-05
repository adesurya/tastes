<?php
/**
 * Quick API Test Script
 * Create this as: wp-content/plugins/tastes/test-api.php
 * Access via: http://localhost/news01/wp-content/plugins/tastes/test-api.php
 */

// Include WordPress
require_once('../../../wp-load.php');

// Force load the plugin if not already loaded
if (!class_exists('AI_Article_Rewriter')) {
    require_once('ai-article-rewriter.php');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>AI Rewriter API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .endpoint-test { margin: 10px 0; }
    </style>
</head>
<body>
    <h1>AI Rewriter API Endpoints Test</h1>
    
    <div class="test-section">
        <h2>1. Plugin Status Check</h2>
        <?php
        if (class_exists('AI_Article_Rewriter')) {
            echo "<p class='success'>✅ Main plugin class loaded</p>";
            
            $plugin = AI_Article_Rewriter::get_instance();
            echo "<p class='success'>✅ Plugin instance created</p>";
            
        } else {
            echo "<p class='error'>❌ Main plugin class not loaded</p>";
        }
        
        if (class_exists('AI_Rewriter_API_Endpoints')) {
            echo "<p class='success'>✅ API Endpoints class loaded</p>";
        } else {
            echo "<p class='error'>❌ API Endpoints class not loaded</p>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. WordPress REST API Routes</h2>
        <?php
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        
        $ai_routes = array();
        foreach ($routes as $route => $handlers) {
            if (strpos($route, 'ai-rewriter') !== false) {
                $ai_routes[$route] = $handlers;
            }
        }
        
        if (empty($ai_routes)) {
            echo "<p class='error'>❌ No AI Rewriter routes found!</p>";
            echo "<p>This means the API endpoints are not being registered.</p>";
        } else {
            echo "<p class='success'>✅ Found " . count($ai_routes) . " AI Rewriter routes:</p>";
            foreach ($ai_routes as $route => $handlers) {
                echo "<p class='info'>• {$route}</p>";
            }
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. API Endpoints Test</h2>
        
        <div class="endpoint-test">
            <h3>Test Basic WordPress REST API:</h3>
            <p><strong>URL:</strong> <a href="<?php echo rest_url(); ?>" target="_blank"><?php echo rest_url(); ?></a></p>
            
            <?php
            $wp_api_test = wp_remote_get(rest_url('wp/v2/'));
            if (is_wp_error($wp_api_test)) {
                echo "<p class='error'>❌ WordPress REST API failed: " . $wp_api_test->get_error_message() . "</p>";
            } else {
                $response_code = wp_remote_retrieve_response_code($wp_api_test);
                echo "<p class='success'>✅ WordPress REST API working (Status: {$response_code})</p>";
            }
            ?>
        </div>
        
        <div class="endpoint-test">
            <h3>Test AI Rewriter Status Endpoint:</h3>
            <p><strong>URL:</strong> <a href="<?php echo rest_url('ai-rewriter/v1/status'); ?>" target="_blank"><?php echo rest_url('ai-rewriter/v1/status'); ?></a></p>
            
            <?php
            // Test without authentication first
            $status_test = wp_remote_get(rest_url('ai-rewriter/v1/status'));
            if (is_wp_error($status_test)) {
                echo "<p class='error'>❌ Status endpoint failed: " . $status_test->get_error_message() . "</p>";
            } else {
                $response_code = wp_remote_retrieve_response_code($status_test);
                $response_body = wp_remote_retrieve_body($status_test);
                
                if ($response_code === 200) {
                    echo "<p class='success'>✅ Status endpoint working (Status: {$response_code})</p>";
                    echo "<pre>" . $response_body . "</pre>";
                } elseif ($response_code === 401) {
                    echo "<p class='info'>🔐 Status endpoint requires authentication (Status: 401)</p>";
                    echo "<p>This is normal if authentication is enabled.</p>";
                } else {
                    echo "<p class='error'>❌ Status endpoint returned: {$response_code}</p>";
                    echo "<pre>" . $response_body . "</pre>";
                }
            }
            ?>
        </div>
        
        <div class="endpoint-test">
            <h3>Test with API Key:</h3>
            <?php
            $api_key = get_option('ai_rewriter_api_endpoint_key', '');
            if (empty($api_key)) {
                echo "<p class='error'>❌ No API key configured</p>";
            } else {
                echo "<p class='info'>API Key: " . substr($api_key, 0, 10) . "...</p>";
                
                // Test with API key
                $headers = array('X-API-Key' => $api_key);
                $status_test_auth = wp_remote_get(rest_url('ai-rewriter/v1/status'), array('headers' => $headers));
                
                if (is_wp_error($status_test_auth)) {
                    echo "<p class='error'>❌ Authenticated request failed: " . $status_test_auth->get_error_message() . "</p>";
                } else {
                    $response_code = wp_remote_retrieve_response_code($status_test_auth);
                    $response_body = wp_remote_retrieve_body($status_test_auth);
                    
                    if ($response_code === 200) {
                        echo "<p class='success'>✅ Authenticated status endpoint working!</p>";
                        echo "<pre>" . $response_body . "</pre>";
                    } else {
                        echo "<p class='error'>❌ Authenticated request returned: {$response_code}</p>";
                        echo "<pre>" . $response_body . "</pre>";
                    }
                }
            }
            ?>
        </div>
    </div>
    
    <div class="test-section">
        <h2>4. CURL Commands to Test</h2>
        <p>Use these commands in your terminal:</p>
        
        <h3>Test WordPress REST API:</h3>
        <pre>curl "<?php echo home_url(); ?>/wp-json/"</pre>
        
        <h3>Test AI Rewriter Status (without auth):</h3>
        <pre>curl "<?php echo home_url(); ?>/wp-json/ai-rewriter/v1/status"</pre>
        
        <h3>Test AI Rewriter Status (with API key):</h3>
        <pre>curl -H "X-API-Key: <?php echo get_option('ai_rewriter_api_endpoint_key', 'YOUR_API_KEY'); ?>" \
     "<?php echo home_url(); ?>/wp-json/ai-rewriter/v1/status"</pre>
        
        <h3>Test Bulk Rewrite Endpoint:</h3>
        <pre>curl -X POST \
  -H "Content-Type: application/json" \
  -H "X-API-Key: <?php echo get_option('ai_rewriter_api_endpoint_key', 'YOUR_API_KEY'); ?>" \
  -d '{"auto_publish": true, "batch_size": 1, "max_articles": 1}' \
  "<?php echo home_url(); ?>/wp-json/ai-rewriter/v1/bulk-rewrite-all"</pre>
    </div>
    
    <div class="test-section">
        <h2>5. Quick Fixes</h2>
        <p><a href="?action=flush_permalinks">🔄 Flush Permalinks</a></p>
        <p><a href="?action=regenerate_api_key">🔑 Regenerate API Key</a></p>
        <p><a href="?action=toggle_auth">🔐 Toggle API Authentication</a></p>
        
        <?php
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'flush_permalinks':
                    flush_rewrite_rules();
                    echo "<p class='success'>✅ Permalinks flushed!</p>";
                    break;
                    
                case 'regenerate_api_key':
                    $new_key = 'air_' . wp_generate_password(32, false, false);
                    update_option('ai_rewriter_api_endpoint_key', $new_key);
                    echo "<p class='success'>✅ New API key generated: " . substr($new_key, 0, 10) . "...</p>";
                    break;
                    
                case 'toggle_auth':
                    $current_auth = get_option('ai_rewriter_api_require_auth', 1);
                    $new_auth = $current_auth ? 0 : 1;
                    update_option('ai_rewriter_api_require_auth', $new_auth);
                    echo "<p class='success'>✅ API authentication " . ($new_auth ? 'enabled' : 'disabled') . "</p>";
                    break;
            }
        }
        ?>
    </div>

</body>
</html>