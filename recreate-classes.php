<?php
/**
 * Class Files Recreator
 * 
 * Upload ke: wp-content/plugins/adesurya-tastes/recreate-classes.php
 * Akses via: http://yoursite.com/wp-content/plugins/adesurya-tastes/recreate-classes.php
 * 
 * WARNING: This will OVERWRITE existing class files!
 * Only use if files are confirmed corrupt/missing
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
    <title>Recreate Class Files</title>
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
        .warning {
            background: #fcf3cf;
            border-left: 4px solid #dba617;
            padding: 15px;
            margin: 20px 0;
            color: #614200;
        }
        .success {
            background: #d7f0d7;
            border-left: 4px solid #00a32a;
            padding: 15px;
            margin: 20px 0;
            color: #00500d;
        }
        .error {
            background: #fcebea;
            border-left: 4px solid #d63638;
            padding: 15px;
            margin: 20px 0;
            color: #5a0001;
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
            font-size: 14px;
        }
        .button:hover { background: #135e96; }
        .button-danger {
            background: #d63638;
        }
        .button-danger:hover {
            background: #b32d2e;
        }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            overflow-x: auto;
            border-radius: 6px;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🔧 Recreate Class Files</h1>
    
    <?php
    if ($action === 'recreate' && $confirmed === 'yes') {
        echo '<h2>⏳ Creating Class Files...</h2>';
        
        $includes_path = WP_PLUGIN_DIR . '/tastes/includes/';
        
        // Ensure directory exists
        if (!is_dir($includes_path)) {
            wp_mkdir_p($includes_path);
        }
        
        $results = array();
        
        // 1. AI_Rewriter_API
        $api_content = '<?php
/**
 * AI Rewriter API Handler
 * Handles OpenAI API communication
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

class AI_Rewriter_API {
    private $api_key;
    private $model = \'gpt-3.5-turbo\';
    private $temperature = 0.7;
    private $max_tokens = 2000;
    private $api_url = \'https://api.openai.com/v1/chat/completions\';
    
    public function __construct($api_key = null) {
        if ($api_key) {
            $this->api_key = $api_key;
        }
    }
    
    public function set_config($config) {
        if (isset($config[\'api_key\'])) {
            $this->api_key = $config[\'api_key\'];
        }
        if (isset($config[\'model\'])) {
            $this->model = $config[\'model\'];
        }
        if (isset($config[\'temperature\'])) {
            $this->temperature = floatval($config[\'temperature\']);
        }
        if (isset($config[\'max_tokens\'])) {
            $this->max_tokens = intval($config[\'max_tokens\']);
        }
    }
    
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                \'success\' => false,
                \'message\' => \'API key is empty\'
            );
        }
        
        try {
            $response = wp_remote_post($this->api_url, array(
                \'headers\' => array(
                    \'Authorization\' => \'Bearer \' . $this->api_key,
                    \'Content-Type\' => \'application/json\'
                ),
                \'body\' => wp_json_encode(array(
                    \'model\' => $this->model,
                    \'messages\' => array(
                        array(\'role\' => \'user\', \'content\' => \'Hello\')
                    ),
                    \'max_tokens\' => 10
                )),
                \'timeout\' => 30
            ));
            
            if (is_wp_error($response)) {
                return array(
                    \'success\' => false,
                    \'message\' => $response->get_error_message()
                );
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data[\'error\'])) {
                return array(
                    \'success\' => false,
                    \'message\' => $data[\'error\'][\'message\'] ?? \'Unknown error\'
                );
            }
            
            return array(
                \'success\' => true,
                \'message\' => \'Connection successful!\'
            );
            
        } catch (Exception $e) {
            return array(
                \'success\' => false,
                \'message\' => $e->getMessage()
            );
        }
    }
    
    public function rewrite_content($prompt) {
        if (empty($this->api_key)) {
            throw new Exception(\'API key not configured\');
        }
        
        $response = wp_remote_post($this->api_url, array(
            \'headers\' => array(
                \'Authorization\' => \'Bearer \' . $this->api_key,
                \'Content-Type\' => \'application/json\'
            ),
            \'body\' => wp_json_encode(array(
                \'model\' => $this->model,
                \'messages\' => array(
                    array(\'role\' => \'user\', \'content\' => $prompt)
                ),
                \'temperature\' => $this->temperature,
                \'max_tokens\' => $this->max_tokens
            )),
            \'timeout\' => 60
        ));
        
        if (is_wp_error($response)) {
            throw new Exception(\'API request failed: \' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data[\'error\'])) {
            throw new Exception(\'OpenAI error: \' . ($data[\'error\'][\'message\'] ?? \'Unknown error\'));
        }
        
        if (!isset($data[\'choices\'][0][\'message\'][\'content\'])) {
            throw new Exception(\'Invalid API response format\');
        }
        
        return array(
            \'content\' => $data[\'choices\'][0][\'message\'][\'content\'],
            \'tokens_used\' => $data[\'usage\'][\'total_tokens\'] ?? 0,
            \'cost\' => $this->calculate_cost($data[\'usage\'] ?? array())
        );
    }
    
    private function calculate_cost($usage) {
        $prompt_tokens = $usage[\'prompt_tokens\'] ?? 0;
        $completion_tokens = $usage[\'completion_tokens\'] ?? 0;
        
        // Pricing per 1K tokens (as of 2024)
        $rates = array(
            \'gpt-3.5-turbo\' => array(\'input\' => 0.0015, \'output\' => 0.002),
            \'gpt-4\' => array(\'input\' => 0.03, \'output\' => 0.06),
            \'gpt-4-turbo\' => array(\'input\' => 0.01, \'output\' => 0.03)
        );
        
        $rate = $rates[$this->model] ?? $rates[\'gpt-3.5-turbo\'];
        
        $cost = ($prompt_tokens / 1000 * $rate[\'input\']) + 
                ($completion_tokens / 1000 * $rate[\'output\']);
        
        return round($cost, 4);
    }
    
    public function get_available_models() {
        return array(
            \'gpt-3.5-turbo\',
            \'gpt-4\',
            \'gpt-4-turbo\',
            \'gpt-4o\',
            \'gpt-4o-mini\'
        );
    }
}';

        $results[] = array(
            'file' => 'class-ai-api.php',
            'success' => file_put_contents($includes_path . 'class-ai-api.php', $api_content) !== false
        );
        
        // 2. AI_Rewriter_Content_Parser
        $parser_content = '<?php
/**
 * Content Parser for AI Rewriter
 * Handles prompt generation and content parsing
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

class AI_Rewriter_Content_Parser {
    private $language = \'Indonesian\';
    private $writing_style = \'professional\';
    
    public function set_language($lang) {
        $this->language = $lang;
    }
    
    public function set_writing_style($style) {
        $this->writing_style = $style;
    }
    
    public function generate_prompt($title, $content, $custom_prompt = \'\', $instructions = \'\') {
        $clean_content = wp_strip_all_tags($content);
        $clean_content = preg_replace(\'/\s+/\', \' \', $clean_content);
        
        if (!empty($custom_prompt)) {
            $prompt = str_replace(
                array(\'{title}\', \'{content}\'),
                array($title, $clean_content),
                $custom_prompt
            );
        } else {
            $prompt = $this->get_default_prompt($title, $clean_content);
        }
        
        if (!empty($instructions)) {
            $prompt .= "\n\nAdditional Instructions: " . $instructions;
        }
        
        return $prompt;
    }
    
    private function get_default_prompt($title, $content) {
        return "Rewrite the following article in {$this->language} with a {$this->writing_style} tone. 
        
Title: {$title}

Content:
{$content}

Please provide a completely rewritten version with:
1. A new engaging title
2. Fresh perspective and wording
3. Maintain the core message and key information
4. Use natural language and flow
5. Format with proper paragraphs

Response format:
TITLE: [new title]
CONTENT: [rewritten content]";
    }
    
    public function parse_rewritten_content($content) {
        $title = \'\';
        $body = $content;
        
        // Try to extract title
        if (preg_match(\'/TITLE:\s*(.+?)(?=CONTENT:|$)/s\', $content, $matches)) {
            $title = trim($matches[1]);
            $body = preg_replace(\'/TITLE:\s*.+?CONTENT:\s*/s\', \'\', $content);
        } elseif (preg_match(\'/^(.+?)\n\n/s\', $content, $matches)) {
            $title = trim($matches[1]);
            $body = substr($content, strlen($matches[0]));
        }
        
        $body = str_replace(\'CONTENT:\', \'\', $body);
        $body = trim($body);
        
        if (empty($title)) {
            $title = $this->extract_title_from_content($body);
        }
        
        return array(
            \'title\' => $title,
            \'content\' => $body
        );
    }
    
    private function extract_title_from_content($content) {
        $sentences = preg_split(\'/(?<=[.!?])\s+/\', $content, 2);
        return !empty($sentences[0]) ? substr($sentences[0], 0, 100) : \'Rewritten Article\';
    }
    
    public function format_for_wordpress($content) {
        $paragraphs = explode("\n\n", $content);
        $formatted = \'\';
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            $formatted .= \'<p>\' . nl2br(esc_html($paragraph)) . \'</p>\' . "\n";
        }
        
        return $formatted;
    }
    
    public function extract_keywords($title, $content, $count = 5) {
        $text = strtolower($title . \' \' . wp_strip_all_tags($content));
        
        $stopwords = array(\'the\', \'is\', \'at\', \'which\', \'on\', \'a\', \'an\', \'and\', \'or\', \'but\', \'in\', \'with\', \'to\', \'for\', \'of\', \'as\', \'by\', \'that\', \'this\', \'it\', \'from\', \'be\', \'are\', \'was\', \'were\', \'been\', \'have\', \'has\', \'had\', \'do\', \'does\', \'did\', \'will\', \'would\', \'could\', \'should\');
        
        $words = str_word_count($text, 1);
        $words = array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 3 && !in_array($word, $stopwords);
        });
        
        $word_counts = array_count_values($words);
        arsort($word_counts);
        
        return array_slice(array_keys($word_counts), 0, $count);
    }
}';

        $results[] = array(
            'file' => 'class-content-parser.php',
            'success' => file_put_contents($includes_path . 'class-content-parser.php', $parser_content) !== false
        );
        
        // 3. AI_Rewriter_Logger
        $logger_content = '<?php
/**
 * Logger for AI Rewriter
 * Handles activity logging and database operations
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

class AI_Rewriter_Logger {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . \'ai_rewriter_logs\';
    }
    
    public function log_rewrite($post_id, $status, $message, $original_title = \'\', $new_title = \'\', $cost = 0) {
        global $wpdb;
        
        $wpdb->insert(
            $this->table_name,
            array(
                \'post_id\' => $post_id,
                \'action\' => \'rewrite\',
                \'status\' => $status,
                \'message\' => $message,
                \'original_title\' => $original_title,
                \'new_title\' => $new_title,
                \'api_cost\' => $cost,
                \'created_at\' => current_time(\'mysql\')
            ),
            array(\'%d\', \'%s\', \'%s\', \'%s\', \'%s\', \'%s\', \'%f\', \'%s\')
        );
        
        $this->log_activity($message, $status);
    }
    
    public function log_activity($message, $type = \'info\') {
        error_log(\'[AI Rewriter] \' . strtoupper($type) . \': \' . $message);
    }
    
    public function get_formatted_logs($limit = 10) {
        global $wpdb;
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
        
        $formatted = array();
        foreach ($logs as $log) {
            $icon = $log->status === \'success\' ? \'✅\' : ($log->status === \'error\' ? \'❌\' : \'⏳\');
            
            $formatted[] = array(
                \'icon\' => $icon,
                \'message\' => $log->message,
                \'time\' => human_time_diff(strtotime($log->created_at), current_time(\'timestamp\')) . \' ago\',
                \'status\' => $log->status
            );
        }
        
        return $formatted;
    }
    
    public function clear_all_logs() {
        global $wpdb;
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}") !== false;
    }
    
    public function get_statistics() {
        global $wpdb;
        
        return array(
            \'total\' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}"),
            \'success\' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = \'success\'"),
            \'errors\' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = \'error\'"),
            \'total_cost\' => $wpdb->get_var("SELECT SUM(api_cost) FROM {$this->table_name}")
        );
    }
}';

        $results[] = array(
            'file' => 'class-logger.php',
            'success' => file_put_contents($includes_path . 'class-logger.php', $logger_content) !== false
        );
        
        // 4. AI_Rewriter_Image_Handler
        $image_content = '<?php
/**
 * Image Handler for AI Rewriter
 * Handles image search and upload
 */

if (!defined(\'ABSPATH\')) {
    exit;
}

class AI_Rewriter_Image_Handler {
    private $source = \'google\';
    
    public function __construct() {
        $this->source = get_option(\'ai_rewriter_image_source\', \'google\');
    }
    
    public function search_and_upload_image($keyword, $post_id) {
        try {
            $image_url = $this->search_image($keyword);
            
            if (!$image_url) {
                return false;
            }
            
            return $this->upload_to_media_library($image_url, $keyword, $post_id);
            
        } catch (Exception $e) {
            error_log(\'Image handler error: \' . $e->getMessage());
            return false;
        }
    }
    
    private function search_image($keyword) {
        if ($this->source === \'google\') {
            return $this->search_google_image($keyword);
        } elseif ($this->source === \'pexels\') {
            return $this->search_pexels_image($keyword);
        }
        
        return false;
    }
    
    private function search_google_image($keyword) {
        $api_key = get_option(\'ai_rewriter_google_api_key\', \'\');
        $search_engine_id = get_option(\'ai_rewriter_google_search_engine_id\', \'\');
        
        if (empty($api_key) || empty($search_engine_id)) {
            return false;
        }
        
        $url = add_query_arg(array(
            \'key\' => $api_key,
            \'cx\' => $search_engine_id,
            \'q\' => urlencode($keyword),
            \'searchType\' => \'image\',
            \'num\' => 1,
            \'imgSize\' => \'large\',
            \'safe\' => \'active\'
        ), \'https://www.googleapis.com/customsearch/v1\');
        
        $response = wp_remote_get($url, array(\'timeout\' => 15));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data[\'items\'][0][\'link\'] ?? false;
    }
    
    private function search_pexels_image($keyword) {
        $api_key = get_option(\'ai_rewriter_pexels_api_key\', \'\');
        
        if (empty($api_key)) {
            return false;
        }
        
        $url = add_query_arg(array(
            \'query\' => urlencode($keyword),
            \'per_page\' => 1,
            \'orientation\' => \'landscape\'
        ), \'https://api.pexels.com/v1/search\');
        
        $response = wp_remote_get($url, array(
            \'headers\' => array(\'Authorization\' => $api_key),
            \'timeout\' => 15
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data[\'photos\'][0][\'src\'][\'large\'] ?? false;
    }
    
    private function upload_to_media_library($image_url, $keyword, $post_id) {
        require_once(ABSPATH . \'wp-admin/includes/media.php\');
        require_once(ABSPATH . \'wp-admin/includes/file.php\');
        require_once(ABSPATH . \'wp-admin/includes/image.php\');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = array(
            \'name\' => sanitize_file_name($keyword . \'.jpg\'),
            \'tmp_name\' => $tmp
        );
        
        $id = media_handle_sideload($file_array, $post_id);
        
        if (is_wp_error($id)) {
            @unlink($tmp);
            return false;
        }
        
        return $id;
    }
}';

        $results[] = array(
            'file' => 'class-image-handler.php',
            'success' => file_put_contents($includes_path . 'class-image-handler.php', $image_content) !== false
        );
        
        // Display results
        echo '<div class="success">';
        echo '<h3>✅ File Creation Complete!</h3>';
        echo '<table style="width: 100%; margin-top: 15px;">';
        echo '<tr><th>File</th><th>Status</th></tr>';
        
        foreach ($results as $result) {
            $status = $result['success'] ? '<span style="color: #00a32a;">✅ Created</span>' : '<span style="color: #d63638;">❌ Failed</span>';
            echo '<tr><td>' . $result['file'] . '</td><td>' . $status . '</td></tr>';
        }
        
        echo '</table>';
        echo '</div>';
        
        // Verify classes can be loaded
        echo '<h3>🧪 Verifying Classes...</h3>';
        
        $verified = array();
        $classes_to_verify = array(
            'class-ai-api.php' => 'AI_Rewriter_API',
            'class-content-parser.php' => 'AI_Rewriter_Content_Parser',
            'class-logger.php' => 'AI_Rewriter_Logger',
            'class-image-handler.php' => 'AI_Rewriter_Image_Handler'
        );
        
        foreach ($classes_to_verify as $file => $class) {
            $filepath = $includes_path . $file;
            
            if (file_exists($filepath)) {
                require_once $filepath;
                $verified[] = array(
                    'class' => $class,
                    'loaded' => class_exists($class)
                );
            }
        }
        
        echo '<table style="width: 100%; margin: 15px 0;">';
        echo '<tr><th>Class</th><th>Loaded</th></tr>';
        
        foreach ($verified as $v) {
            $status = $v['loaded'] ? '<span style="color: #00a32a;">✅ Loaded</span>' : '<span style="color: #d63638;">❌ Not Loaded</span>';
            echo '<tr><td>' . $v['class'] . '</td><td>' . $status . '</td></tr>';
        }
        
        echo '</table>';
        
        $all_loaded = count(array_filter($verified, function($v) { return $v['loaded']; })) === count($verified);
        
        if ($all_loaded) {
            echo '<div class="success">';
            echo '<h3>🎉 SUCCESS!</h3>';
            echo '<p>All classes created and loaded successfully!</p>';
            echo '<p><strong>Next steps:</strong></p>';
            echo '<ol>';
            echo '<li>Go to debug tool to verify all systems working: <a href="debug-ai-rewriter-cron.php">debug-ai-rewriter-cron.php</a></li>';
            echo '<li>Configure OpenAI API key in settings if not done yet</li>';
            echo '<li>Test cron with manual trigger</li>';
            echo '</ol>';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<h3>⚠️ Some classes failed to load</h3>';
            echo '<p>Files were created but classes still cannot be loaded. This might be due to:</p>';
            echo '<ul>';
            echo '<li>PHP version compatibility issues</li>';
            echo '<li>Server configuration problems</li>';
            echo '<li>File permissions</li>';
            echo '</ul>';
            echo '<p>Check WordPress debug.log for detailed errors.</p>';
            echo '</div>';
        }
        
        echo '<p><a href="debug-ai-rewriter-cron.php" class="button">📊 Go to Debug Tool</a></p>';
        
    } else {
        // Show confirmation screen
        ?>
        
        <div class="warning">
            <h3>⚠️ WARNING: This Will Overwrite Existing Files!</h3>
            <p><strong>This action will:</strong></p>
            <ul>
                <li>Overwrite ALL class files in the includes/ directory</li>
                <li>Replace potentially corrupt files with fresh copies</li>
                <li>Reset any custom modifications you may have made</li>
            </ul>
            <p><strong>Files that will be overwritten:</strong></p>
            <ul>
                <li>class-ai-api.php</li>
                <li>class-content-parser.php</li>
                <li>class-logger.php</li>
                <li>class-image-handler.php</li>
            </ul>
        </div>
        
        <h3>📋 Pre-Recreation Checklist:</h3>
        <ol>
            <li>✅ Backed up current plugin files (if needed)</li>
            <li>✅ Confirmed files are corrupt/missing via inspect tool</li>
            <li>✅ Understood this will overwrite existing files</li>
        </ol>
        
        <p style="margin-top: 30px;">
            <a href="?action=recreate&confirm=yes" class="button button-danger" onclick="return confirm('Are you ABSOLUTELY SURE you want to overwrite all class files? This cannot be undone!')">
                🔧 YES, Recreate All Files Now
            </a>
            
            <a href="inspect-classes.php" class="button">
                ← Back to Inspector
            </a>
        </p>
        
        <?php
    }
    ?>
    
    <hr style="margin: 40px 0; border: none; border-top: 1px solid #dcdcde;">
    
    <p style="color: #787c82; font-size: 12px;">
        <strong>⚠️ Security:</strong> Delete this file after fixing the issue!<br>
        File: <code>/wp-content/plugins/tastes/recreate-classes.php</code>
    </p>
    
</div>

</body>
</html>