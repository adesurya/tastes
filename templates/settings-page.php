<?php
// File: templates/settings-page.php
// Template Settings Page - Lengkap dengan Semua Fitur

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors('ai_rewriter_settings'); ?>
    
    <div class="ai-rewriter-settings">
        <form method="post" action="" id="ai-rewriter-settings-form">
            <?php wp_nonce_field('ai_rewriter_settings', 'ai_rewriter_nonce'); ?>
            
            <!-- API Settings -->
            <div class="postbox">
                <h3 class="hndle"><span><?php _e('🔑 API Configuration', 'ai-article-rewriter'); ?></span></h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('OpenAI API Key', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="password" name="ai_rewriter_api_key" id="api_key_input"
                                       value="<?php echo esc_attr(get_option('ai_rewriter_api_key', '')); ?>" 
                                       class="regular-text" placeholder="sk-..." />
                                <button type="button" id="test_api_btn" class="button"><?php _e('Test Connection', 'ai-article-rewriter'); ?></button>
                                <button type="button" id="load_models_btn" class="button"><?php _e('Load Models', 'ai-article-rewriter'); ?></button>
                                <div id="api_test_result"></div>
                                <p class="description"><?php _e('Enter your OpenAI API key to enable AI rewriting functionality', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('AI Model', 'ai-article-rewriter'); ?></th>
                            <td>
                                <select name="ai_rewriter_model" id="ai_model_select">
                                    <?php
                                    $current_model = get_option('ai_rewriter_model', 'gpt-3.5-turbo');
                                    $default_models = array(
                                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Recommended)',
                                        'gpt-4' => 'GPT-4 (Premium)',
                                        'gpt-4-turbo' => 'GPT-4 Turbo (Latest)',
                                        'gpt-4o' => 'GPT-4o (Optimized)',
                                        'gpt-4o-mini' => 'GPT-4o Mini (Fast & Efficient)'
                                    );
                                    
                                    foreach ($default_models as $model => $label) {
                                        echo '<option value="' . esc_attr($model) . '"' . selected($current_model, $model, false) . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                                <div id="dynamic_models_container"></div>
                                <p class="description"><?php _e('Select the AI model to use for rewriting. GPT-4 models provide better quality but cost more.', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Temperature', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="range" name="ai_rewriter_temperature" id="temperature_slider"
                                       value="<?php echo esc_attr(get_option('ai_rewriter_temperature', 0.7)); ?>" 
                                       min="0" max="1" step="0.1" />
                                <span id="temperature_value"><?php echo esc_attr(get_option('ai_rewriter_temperature', 0.7)); ?></span>
                                <p class="description"><?php _e('Controls creativity. Lower = more focused, Higher = more creative (0.0 - 1.0)', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Max Tokens', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="number" name="ai_rewriter_max_tokens" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_max_tokens', 2000)); ?>" 
                                       min="100" max="4000" step="100" />
                                <p class="description"><?php _e('Maximum tokens for AI response. Higher values allow longer articles but cost more.', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Content Settings -->
            <div class="postbox">
                <h3 class="hndle"><span><?php _e('📝 Content Settings', 'ai-article-rewriter'); ?></span></h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Language', 'ai-article-rewriter'); ?></th>
                            <td>
                                <select name="ai_rewriter_language">
                                    <?php
                                    $languages = array(
                                        'Indonesian' => 'Bahasa Indonesia',
                                        'English' => 'English',
                                        'Spanish' => 'Español',
                                        'French' => 'Français',
                                        'German' => 'Deutsch',
                                        'Portuguese' => 'Português',
                                        'Italian' => 'Italiano',
                                        'Dutch' => 'Nederlands',
                                        'Russian' => 'Русский',
                                        'Chinese' => '中文',
                                        'Japanese' => '日本語',
                                        'Korean' => '한국어',
                                        'Arabic' => 'العربية',
                                        'Hindi' => 'हिन्दी'
                                    );
                                    
                                    $current_lang = get_option('ai_rewriter_language', 'Indonesian');
                                    foreach ($languages as $code => $name) {
                                        echo '<option value="' . esc_attr($code) . '"' . selected($current_lang, $code, false) . '>' . esc_html($name) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Select the target language for rewritten content', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Writing Style', 'ai-article-rewriter'); ?></th>
                            <td>
                                <select name="ai_rewriter_writing_style">
                                    <?php
                                    $styles = array(
                                        'professional' => 'Professional',
                                        'casual' => 'Casual & Friendly',
                                        'formal' => 'Formal & Academic',
                                        'conversational' => 'Conversational',
                                        'journalistic' => 'Journalistic',
                                        'creative' => 'Creative & Engaging',
                                        'technical' => 'Technical & Detailed',
                                        'persuasive' => 'Persuasive & Compelling'
                                    );
                                    
                                    $current_style = get_option('ai_rewriter_writing_style', 'professional');
                                    foreach ($styles as $style => $label) {
                                        echo '<option value="' . esc_attr($style) . '"' . selected($current_style, $style, false) . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Choose the writing style for rewritten articles', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Custom Prompting -->
            <div class="postbox">
                <h3 class="hndle"><span><?php _e('🎯 Custom Prompting', 'ai-article-rewriter'); ?></span></h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Use Custom Prompt', 'ai-article-rewriter'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ai_rewriter_use_custom_prompt" value="1" 
                                           <?php checked(get_option('ai_rewriter_use_custom_prompt', 0), 1); ?> 
                                           id="use_custom_prompt" />
                                    <?php _e('Enable custom prompt system', 'ai-article-rewriter'); ?>
                                </label>
                                <p class="description"><?php _e('Override default prompts with your custom instructions', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="custom_prompt_row" style="display: <?php echo get_option('ai_rewriter_use_custom_prompt', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Custom Prompt Template', 'ai-article-rewriter'); ?></th>
                            <td>
                                <textarea name="ai_rewriter_custom_prompt" rows="8" cols="80" class="large-text"
                                          placeholder="<?php esc_attr_e('Enter your custom prompt template here. Use {title} and {content} as placeholders.', 'ai-article-rewriter'); ?>"><?php echo esc_textarea(get_option('ai_rewriter_custom_prompt', '')); ?></textarea>
                                <p class="description">
                                    <?php _e('Available placeholders: {title}, {content}, {language}, {style}', 'ai-article-rewriter'); ?><br>
                                    <?php _e('Example: "Rewrite the following article in {language} with a {style} tone. Original title: {title}. Content: {content}"', 'ai-article-rewriter'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr id="prompt_instructions_row" style="display: <?php echo get_option('ai_rewriter_use_custom_prompt', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Additional Instructions', 'ai-article-rewriter'); ?></th>
                            <td>
                                <textarea name="ai_rewriter_prompt_instructions" rows="4" cols="80" class="large-text"
                                          placeholder="<?php esc_attr_e('Add specific instructions for the AI (SEO keywords, tone guidelines, etc.)', 'ai-article-rewriter'); ?>"><?php echo esc_textarea(get_option('ai_rewriter_prompt_instructions', '')); ?></textarea>
                                <p class="description"><?php _e('Additional context and instructions for better rewriting results', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Image Settings -->
            <div class="postbox">
                <h3 class="hndle"><span><?php _e('🖼️ Image Replacement', 'ai-article-rewriter'); ?></span></h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Auto Replace Images', 'ai-article-rewriter'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ai_rewriter_auto_replace_images" value="1" 
                                           <?php checked(get_option('ai_rewriter_auto_replace_images', 0), 1); ?> 
                                           id="auto_replace_images" />
                                    <?php _e('Automatically replace images with relevant ones', 'ai-article-rewriter'); ?>
                                </label>
                                <p class="description"><?php _e('Search and replace images based on article content keywords', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="image_settings_row" style="display: <?php echo get_option('ai_rewriter_auto_replace_images', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Image Source', 'ai-article-rewriter'); ?></th>
                            <td>
                                <select name="ai_rewriter_image_source" id="image_source_select">
                                    <?php
                                    $sources = array(
                                        'google' => 'Google Images (Free)',
                                        'pexels' => 'Pexels (Free)',
                                        'unsplash' => 'Unsplash (Free)',
                                        'pixabay' => 'Pixabay (Free)'
                                    );
                                    
                                    $current_source = get_option('ai_rewriter_image_source', 'google');
                                    foreach ($sources as $source => $label) {
                                        echo '<option value="' . esc_attr($source) . '"' . selected($current_source, $source, false) . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr id="max_images_row" style="display: <?php echo get_option('ai_rewriter_auto_replace_images', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Maximum Images', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="number" name="ai_rewriter_max_images" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_max_images', 2)); ?>" 
                                       min="1" max="10" />
                                <p class="description"><?php _e('Maximum number of images to add per article', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <!-- Google Custom Search API -->
                        <tr id="google_api_row" style="display: <?php echo (get_option('ai_rewriter_auto_replace_images', 0) && get_option('ai_rewriter_image_source', 'google') === 'google') ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Google API Key', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="text" name="ai_rewriter_google_api_key" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_google_api_key', '')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Required for Google Images search', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="google_search_engine_row" style="display: <?php echo (get_option('ai_rewriter_auto_replace_images', 0) && get_option('ai_rewriter_image_source', 'google') === 'google') ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Search Engine ID', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="text" name="ai_rewriter_google_search_engine_id" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_google_search_engine_id', '')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Google Custom Search Engine ID', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <!-- Pexels API -->
                        <tr id="pexels_api_row" style="display: <?php echo (get_option('ai_rewriter_auto_replace_images', 0) && get_option('ai_rewriter_image_source', 'google') === 'pexels') ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Pexels API Key', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="text" name="ai_rewriter_pexels_api_key" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_pexels_api_key', '')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('Required for Pexels image search', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Auto Rewrite Settings -->
            <div class="postbox">
                <h3 class="hndle"><span><?php _e('🤖 Auto Rewrite Settings', 'ai-article-rewriter'); ?></span></h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Auto Rewrite', 'ai-article-rewriter'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ai_rewriter_auto_rewrite_enabled" value="1" 
                                           <?php checked(get_option('ai_rewriter_auto_rewrite_enabled', 0), 1); ?> 
                                           id="auto_rewrite_enabled" />
                                    <?php _e('Automatically rewrite draft posts in the background', 'ai-article-rewriter'); ?>
                                </label>
                                <p class="description"><?php _e('Draft posts will be automatically processed when saved', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="auto_settings_row" style="display: <?php echo get_option('ai_rewriter_auto_rewrite_enabled', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Auto Rewrite Options', 'ai-article-rewriter'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="ai_rewriter_auto_publish_after_rewrite" value="1" 
                                               <?php checked(get_option('ai_rewriter_auto_publish_after_rewrite', 0), 1); ?> />
                                        <?php _e('Auto publish articles after rewriting', 'ai-article-rewriter'); ?>
                                    </label>
                                    <br><br>
                                    
                                    <label>
                                        <input type="checkbox" name="ai_rewriter_auto_process_immediately" value="1" 
                                               <?php checked(get_option('ai_rewriter_auto_process_immediately', 0), 1); ?> />
                                        <?php _e('Process articles immediately (otherwise queued)', 'ai-article-rewriter'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr id="min_words_row" style="display: <?php echo get_option('ai_rewriter_auto_rewrite_enabled', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Minimum Words', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="number" name="ai_rewriter_auto_min_words" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_auto_min_words', 50)); ?>" 
                                       min="10" max="1000" />
                                <span><?php _e('words', 'ai-article-rewriter'); ?></span>
                                <p class="description"><?php _e('Only process articles with at least this many words', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="delay_minutes_row" style="display: <?php echo get_option('ai_rewriter_auto_rewrite_enabled', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Processing Delay', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="number" name="ai_rewriter_auto_delay_minutes" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_auto_delay_minutes', 5)); ?>" 
                                       min="0" max="60" />
                                <span><?php _e('minutes', 'ai-article-rewriter'); ?></span>
                                <p class="description"><?php _e('Wait this many minutes before processing new drafts', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="batch_size_row" style="display: <?php echo get_option('ai_rewriter_auto_rewrite_enabled', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Batch Size', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="number" name="ai_rewriter_auto_batch_size" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_auto_batch_size', 1)); ?>" 
                                       min="1" max="10" />
                                <span><?php _e('articles per batch', 'ai-article-rewriter'); ?></span>
                                <p class="description"><?php _e('Number of articles to process in each batch', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="check_interval_row" style="display: <?php echo get_option('ai_rewriter_auto_rewrite_enabled', 0) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Check Interval', 'ai-article-rewriter'); ?></th>
                            <td>
                                <select name="ai_rewriter_auto_check_interval">
                                    <?php
                                    $intervals = array(
                                        '5' => '5 minutes',
                                        '15' => '15 minutes',
                                        '30' => '30 minutes',
                                        '60' => '1 hour'
                                    );
                                    
                                    $current_interval = get_option('ai_rewriter_auto_check_interval', 15);
                                    foreach ($intervals as $value => $label) {
                                        echo '<option value="' . esc_attr($value) . '"' . selected($current_interval, $value, false) . '>' . esc_html($label) . '</option>';
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('How often to check for new articles to process', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Queue Management -->
                    <div id="queue_management" style="display: <?php echo get_option('ai_rewriter_auto_rewrite_enabled', 0) ? 'block' : 'none'; ?>;">
                        <h4><?php _e('Queue Management', 'ai-article-rewriter'); ?></h4>
                        <div id="queue_status_display">
                            <button type="button" id="get_queue_status" class="button"><?php _e('Check Queue Status', 'ai-article-rewriter'); ?></button>
                            <button type="button" id="process_queue_now" class="button"><?php _e('Process Queue Now', 'ai-article-rewriter'); ?></button>
                            <button type="button" id="clear_queue" class="button button-secondary"><?php _e('Clear Queue', 'ai-article-rewriter'); ?></button>
                        </div>
                        <div id="queue_status_result"></div>
                    </div>
                </div>
            </div>
            
            <!-- Advanced Settings -->
            <div class="postbox">
                <h3 class="hndle"><span><?php _e('⚙️ Advanced Settings', 'ai-article-rewriter'); ?></span></h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Max Retries (Auto Mode)', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="number" name="ai_rewriter_auto_max_retries" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_auto_max_retries', 3)); ?>" 
                                       min="1" max="10" />
                                <p class="description"><?php _e('Maximum retry attempts for failed auto rewrite tasks', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Retry Delay', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="number" name="ai_rewriter_auto_retry_delay" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_auto_retry_delay', 30)); ?>" 
                                       min="5" max="120" />
                                <span><?php _e('minutes', 'ai-article-rewriter'); ?></span>
                                <p class="description"><?php _e('Wait time before retrying failed tasks', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Processing Delay Between Items', 'ai-article-rewriter'); ?></th>
                            <td>
                                <input type="number" name="ai_rewriter_auto_processing_delay" 
                                       value="<?php echo esc_attr(get_option('ai_rewriter_auto_processing_delay', 2)); ?>" 
                                       min="0" max="60" />
                                <span><?php _e('seconds', 'ai-article-rewriter'); ?></span>
                                <p class="description"><?php _e('Delay between processing queue items to avoid API rate limits', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Data Management', 'ai-article-rewriter'); ?></th>
                            <td>
                                <button type="button" id="reset_processed_posts" class="button button-secondary"><?php _e('Reset Processing History', 'ai-article-rewriter'); ?></button>
                                <button type="button" id="clear_activity_logs" class="button button-secondary"><?php _e('Clear Activity Logs', 'ai-article-rewriter'); ?></button>
                                <p class="description"><?php _e('Reset processing history to allow re-processing of previously handled articles', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="postbox">
                <h3 class="hndle"><span><?php _e('🔗 REST API Settings', 'ai-article-rewriter'); ?></span></h3>
                <div class="inside">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable REST API', 'ai-article-rewriter'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ai_rewriter_api_enabled" value="1" 
                                        <?php checked(get_option('ai_rewriter_api_enabled', 1), 1); ?> 
                                        id="api_enabled" />
                                    <?php _e('Enable REST API endpoints for external access', 'ai-article-rewriter'); ?>
                                </label>
                                <p class="description"><?php _e('Allow external applications to trigger bulk rewrite operations', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="api_key_row" style="display: <?php echo get_option('ai_rewriter_api_enabled', 1) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('API Authentication Key', 'ai-article-rewriter'); ?></th>
                            <td>
                                <?php
                                $plugin = AI_Article_Rewriter::get_instance();
                                $current_key = $plugin->get_api_endpoint_key();
                                ?>
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <input type="text" id="api_endpoint_key_display" value="<?php echo esc_attr($current_key); ?>" 
                                        readonly class="regular-text" style="font-family: monospace; background: #f1f1f1;" />
                                    <button type="button" id="copy_api_key" class="button" title="<?php _e('Copy to clipboard', 'ai-article-rewriter'); ?>">
                                        📋 <?php _e('Copy', 'ai-article-rewriter'); ?>
                                    </button>
                                    <button type="button" id="regenerate_api_key" class="button button-secondary">
                                        🔄 <?php _e('Regenerate', 'ai-article-rewriter'); ?>
                                    </button>
                                </div>
                                <p class="description">
                                    <?php _e('Use this key for API authentication. Include it as "X-API-Key" header or "api_key" parameter.', 'ai-article-rewriter'); ?>
                                    <br><strong><?php _e('Keep this key secure!', 'ai-article-rewriter'); ?></strong>
                                </p>
                            </td>
                        </tr>
                        
                        <tr id="api_endpoints_info" style="display: <?php echo get_option('ai_rewriter_api_enabled', 1) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Available Endpoints', 'ai-article-rewriter'); ?></th>
                            <td>
                                <?php $base_url = rest_url('ai-rewriter/v1/'); ?>
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px;">
                                    <div style="margin-bottom: 10px;">
                                        <strong style="color: #007cba;">🚀 Bulk Rewrite All Drafts:</strong><br>
                                        <code style="background: #e1f5fe; padding: 2px 4px; border-radius: 3px;">POST <?php echo esc_html($base_url); ?>bulk-rewrite-all</code>
                                    </div>
                                    
                                    <div style="margin-bottom: 10px;">
                                        <strong style="color: #28a745;">📊 Check Status:</strong><br>
                                        <code style="background: #e8f5e8; padding: 2px 4px; border-radius: 3px;">GET <?php echo esc_html($base_url); ?>bulk-rewrite-status</code>
                                    </div>
                                    
                                    <div>
                                        <strong style="color: #dc3545;">❌ Cancel Processing:</strong><br>
                                        <code style="background: #ffebee; padding: 2px 4px; border-radius: 3px;">POST <?php echo esc_html($base_url); ?>bulk-rewrite-cancel</code>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 10px;">
                                    <button type="button" id="test_api_endpoint" class="button">
                                        🧪 <?php _e('Test API Connection', 'ai-article-rewriter'); ?>
                                    </button>
                                    <button type="button" id="view_api_docs" class="button button-secondary">
                                        📖 <?php _e('View Documentation', 'ai-article-rewriter'); ?>
                                    </button>
                                </div>
                                
                                <div id="api_test_result" style="margin-top: 10px; display: none;"></div>
                            </td>
                        </tr>
                        
                        <tr id="api_security_row" style="display: <?php echo get_option('ai_rewriter_api_enabled', 1) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('API Security', 'ai-article-rewriter'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="ai_rewriter_api_require_auth" value="1" 
                                            <?php checked(get_option('ai_rewriter_api_require_auth', 1), 1); ?> />
                                        <?php _e('Require authentication for all API requests', 'ai-article-rewriter'); ?>
                                    </label>
                                    <br><br>
                                    
                                    <label>
                                        <input type="checkbox" name="ai_rewriter_api_log_requests" value="1" 
                                            <?php checked(get_option('ai_rewriter_api_log_requests', 1), 1); ?> />
                                        <?php _e('Log all API requests for security monitoring', 'ai-article-rewriter'); ?>
                                    </label>
                                </fieldset>
                                <p class="description"><?php _e('Security settings for API access control and monitoring.', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="api_rate_limits" style="display: <?php echo get_option('ai_rewriter_api_enabled', 1) ? 'table-row' : 'none'; ?>;">
                            <th scope="row"><?php _e('Rate Limits', 'ai-article-rewriter'); ?></th>
                            <td>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label><?php _e('Requests per hour:', 'ai-article-rewriter'); ?></label>
                                        <input type="number" name="ai_rewriter_api_rate_limit_hourly" 
                                            value="<?php echo esc_attr(get_option('ai_rewriter_api_rate_limit_hourly', 10)); ?>" 
                                            min="1" max="100" />
                                    </div>
                                    <div>
                                        <label><?php _e('Concurrent batches:', 'ai-article-rewriter'); ?></label>
                                        <input type="number" name="ai_rewriter_api_max_concurrent_batches" 
                                            value="<?php echo esc_attr(get_option('ai_rewriter_api_max_concurrent_batches', 2)); ?>" 
                                            min="1" max="5" />
                                    </div>
                                </div>
                                <p class="description"><?php _e('Limit API usage to prevent abuse and server overload.', 'ai-article-rewriter'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- API Documentation Modal -->
                    <div id="api_docs_modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 8px; max-width: 800px; max-height: 80vh; overflow-y: auto;">
                            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                                <h2 style="margin: 0;">📖 API Documentation</h2>
                                <button type="button" id="close_api_docs" style="background: none; border: none; font-size: 24px; cursor: pointer; float: right;">×</button>
                            </div>
                            
                            <div id="api_docs_content">
                                <h3>🚀 Bulk Rewrite All Drafts</h3>
                                <p><strong>Endpoint:</strong> <code>POST /wp-json/ai-rewriter/v1/bulk-rewrite-all</code></p>
                                <p><strong>Description:</strong> Start bulk rewriting process for all draft articles.</p>
                                
                                <h4>Request Parameters:</h4>
                                <table style="width: 100%; border-collapse: collapse; margin: 10px 0;">
                                    <tr style="background: #f1f1f1;">
                                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Parameter</th>
                                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Type</th>
                                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Default</th>
                                        <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">Description</th>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;"><code>api_key</code></td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">string</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">-</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">Authentication key (or use X-API-Key header)</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;"><code>auto_publish</code></td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">boolean</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">true</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">Publish articles after rewriting</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;"><code>batch_size</code></td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">integer</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">5</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">Articles per batch (1-20)</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;"><code>process_images</code></td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">boolean</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">true</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">Process and replace images</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;"><code>min_words</code></td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">integer</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">50</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">Minimum word count</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;"><code>max_articles</code></td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">integer</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">0</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">Max articles to process (0 = no limit)</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; border: 1px solid #ddd;"><code>callback_url</code></td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">string</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">-</td>
                                        <td style="padding: 8px; border: 1px solid #ddd;">Webhook URL for progress updates</td>
                                    </tr>
                                </table>
                                
                                <h4>Example Request:</h4>
                                <pre style="background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto;"><code>curl -X POST <?php echo esc_html($base_url); ?>bulk-rewrite-all \
            -H "Content-Type: application/json" \
            -H "X-API-Key: YOUR_API_KEY_HERE" \
            -d '{
            "auto_publish": true,
            "batch_size": 5,
            "process_images": true,
            "min_words": 100,
            "max_articles": 10,
            "callback_url": "https://your-site.com/webhook"
            }'</code></pre>
                                
                                <h4>Success Response (202 Accepted):</h4>
                                <pre style="background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto;"><code>{
            "success": true,
            "message": "Bulk rewrite started successfully. Processing 8 articles in background.",
            "data": {
                "batch_id": "bulk_1703123456_abc123",
                "status": "processing",
                "total_articles": 8,
                "settings": {
                "auto_publish": true,
                "batch_size": 5,
                "process_images": true
                },
                "status_url": "/wp-json/ai-rewriter/v1/bulk-rewrite-status?batch_id=bulk_1703123456_abc123",
                "estimated_time_minutes": 4
            }
            }</code></pre>

                                <hr style="margin: 20px 0;">
                                
                                <h3>📊 Check Status</h3>
                                <p><strong>Endpoint:</strong> <code>GET /wp-json/ai-rewriter/v1/bulk-rewrite-status</code></p>
                                <p><strong>Description:</strong> Check the progress of bulk rewrite operations.</p>
                                
                                <h4>Parameters:</h4>
                                <ul>
                                    <li><code>batch_id</code> (optional) - Get specific batch status</li>
                                    <li>If no batch_id provided, returns list of recent batches</li>
                                </ul>
                                
                                <h4>Example Response:</h4>
                                <pre style="background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto;"><code>{
            "success": true,
            "data": {
                "batch_id": "bulk_1703123456_abc123",
                "status": "processing",
                "progress": {
                "total_articles": 8,
                "processed_count": 3,
                "success_count": 2,
                "error_count": 1,
                "remaining_count": 5,
                "progress_percentage": 37.5
                },
                "timing": {
                "start_time": "2024-01-15 10:30:00",
                "estimated_completion": "2024-01-15 10:35:00",
                "elapsed_time": "2.5 minutes"
                }
            }
            }</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php submit_button(__('Save Settings', 'ai-article-rewriter'), 'primary', 'submit', true, array('id' => 'save_settings_btn')); ?>
        </form>
        
        <!-- Activity Monitor -->
        <div class="postbox">
            <h3 class="hndle"><span><?php _e('📊 Recent Activity', 'ai-article-rewriter'); ?></span></h3>
            <div class="inside">
                <button type="button" id="refresh_activity" class="button"><?php _e('Refresh Activity', 'ai-article-rewriter'); ?></button>
                <div id="recent_activity_display">
                    <p><?php _e('Loading recent activity...', 'ai-article-rewriter'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    #api_docs_modal {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

#api_docs_content h3 {
    color: #007cba;
    border-bottom: 2px solid #007cba;
    padding-bottom: 5px;
}

#api_docs_content h4 {
    color: #333;
    margin-top: 20px;
}

#api_docs_content code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
}

#api_docs_content pre {
    font-size: 12px;
    line-height: 1.4;
}

#api_docs_content table {
    font-size: 13px;
}

.ai-rewriter-settings .postbox {
    margin-bottom: 20px;
}

.ai-rewriter-settings .postbox h3 {
    padding: 12px;
    margin: 0;
    background: #f7f7f7;
    border-bottom: 1px solid #ddd;
}

.ai-rewriter-settings .inside {
    padding: 20px;
}

#temperature_slider {
    width: 200px;
    margin-right: 10px;
}

#api_test_result {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

#dynamic_models_container {
    margin-top: 10px;
}

.model-option {
    margin: 5px 0;
}

.button-group {
    margin-top: 10px;
}

.button-group .button {
    margin-right: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
    
     $('#api_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#api_key_row, #api_endpoints_info, #api_security_row, #api_rate_limits').show();
        } else {
            $('#api_key_row, #api_endpoints_info, #api_security_row, #api_rate_limits').hide();
        }
    });
    
    // Copy API key
    $('#copy_api_key').click(function() {
        var keyField = $('#api_endpoint_key_display');
        keyField.select();
        document.execCommand('copy');
        
        var $btn = $(this);
        var originalText = $btn.text();
        $btn.text('✅ Copied!').prop('disabled', true);
        
        setTimeout(function() {
            $btn.text(originalText).prop('disabled', false);
        }, 2000);
    });
    
    // Regenerate API key
    $('#regenerate_api_key').click(function() {
        if (!confirm('Are you sure? This will invalidate the current API key and any applications using it will need to be updated.')) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('🔄 Generating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'regenerate_api_key',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#api_endpoint_key_display').val(response.data.new_key);
                    alert('✅ New API key generated successfully!');
                } else {
                    alert('❌ Error: ' + response.data);
                }
            },
            error: function() {
                alert('❌ Connection error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).text('🔄 Regenerate');
            }
        });
    });
    
    // Test API connection
    $('#test_api_endpoint').click(function() {
        var $btn = $(this);
        var $result = $('#api_test_result');
        var apiKey = $('#api_endpoint_key_display').val();
        
        $btn.prop('disabled', true).text('🧪 Testing...');
        $result.hide();
        
        $.ajax({
            url: '<?php echo rest_url("ai-rewriter/v1/bulk-rewrite-status"); ?>',
            type: 'GET',
            headers: {
                'X-API-Key': apiKey
            },
            success: function(response) {
                $result.removeClass('error').addClass('success')
                       .html('<span style="color: green;">✅ API endpoint is working correctly!</span>')
                       .show();
            },
            error: function(xhr) {
                var errorMsg = 'API connection failed';
                if (xhr.status === 401) {
                    errorMsg = 'Authentication failed - check your API key';
                } else if (xhr.status === 404) {
                    errorMsg = 'API endpoints not found - check if REST API is enabled';
                }
                
                $result.removeClass('success').addClass('error')
                       .html('<span style="color: red;">❌ ' + errorMsg + '</span>')
                       .show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('🧪 Test API Connection');
            }
        });
    });
    
    // View API documentation
    $('#view_api_docs').click(function() {
        $('#api_docs_modal').show();
    });
    
    // Close API documentation
    $('#close_api_docs, #api_docs_modal').click(function(e) {
        if (e.target === this) {
            $('#api_docs_modal').hide();
        }
    });

    // Temperature slider
    $('#temperature_slider').on('input', function() {
        $('#temperature_value').text($(this).val());
    });
    
    // Custom prompt toggle
    $('#use_custom_prompt').change(function() {
        if ($(this).is(':checked')) {
            $('#custom_prompt_row, #prompt_instructions_row').show();
        } else {
            $('#custom_prompt_row, #prompt_instructions_row').hide();
        }
    });
    
    // Image replacement toggle
    $('#auto_replace_images').change(function() {
        if ($(this).is(':checked')) {
            $('#image_settings_row, #max_images_row').show();
            toggleImageSourceFields();
        } else {
            $('#image_settings_row, #max_images_row, #google_api_row, #google_search_engine_row, #pexels_api_row').hide();
        }
    });
    
    // Image source change
    $('#image_source_select').change(function() {
        toggleImageSourceFields();
    });
    
    function toggleImageSourceFields() {
        var source = $('#image_source_select').val();
        $('#google_api_row, #google_search_engine_row, #pexels_api_row').hide();
        
        if (source === 'google') {
            $('#google_api_row, #google_search_engine_row').show();
        } else if (source === 'pexels') {
            $('#pexels_api_row').show();
        }
    }
    
    // Auto rewrite toggle
    $('#auto_rewrite_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#auto_settings_row, #min_words_row, #delay_minutes_row, #batch_size_row, #check_interval_row, #queue_management').show();
        } else {
            $('#auto_settings_row, #min_words_row, #delay_minutes_row, #batch_size_row, #check_interval_row, #queue_management').hide();
        }
    });
    
    // Test API Connection
    $('#test_api_btn').click(function() {
        var apiKey = $('#api_key_input').val();
        var $btn = $(this);
        var $result = $('#api_test_result');
        
        if (!apiKey) {
            $result.removeClass('success').addClass('error').text('Please enter an API key first').show();
            return;
        }
        
        $btn.prop('disabled', true).text('Testing...');
        $result.hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_api_connection',
                api_key: apiKey,
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.removeClass('error').addClass('success').text(response.data).show();
                } else {
                    $result.removeClass('success').addClass('error').text(response.data).show();
                }
            },
            error: function() {
                $result.removeClass('success').addClass('error').text('Connection error occurred').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('Test Connection');
            }
        });
    });
    
    // Load Available Models
    $('#load_models_btn').click(function() {
        var apiKey = $('#api_key_input').val();
        var $btn = $(this);
        var $container = $('#dynamic_models_container');
        
        if (!apiKey) {
            alert('Please enter an API key first');
            return;
        }
        
        $btn.prop('disabled', true).text('Loading...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_available_models',
                api_key: apiKey,
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success && response.data.models) {
                    var html = '<h4>Available Models from API:</h4>';
                    response.data.models.forEach(function(model) {
                        html += '<div class="model-option">';
                        html += '<label><input type="radio" name="ai_rewriter_model" value="' + model.id + '"> ';
                        html += model.id + ' (' + (model.description || 'No description') + ')</label>';
                        html += '</div>';
                    });
                    $container.html(html);
                } else {
                    $container.html('<p style="color: red;">Failed to load models: ' + (response.data || 'Unknown error') + '</p>');
                }
            },
            error: function() {
                $container.html('<p style="color: red;">Error loading models</p>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Load Models');
            }
        });
    });
    
    // Queue Status
    $('#get_queue_status').click(function() {
        var $btn = $(this);
        var $result = $('#queue_status_result');
        
        $btn.prop('disabled', true).text('Checking...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_auto_rewrite_status',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var html = '<h4>Queue Status:</h4>';
                    
                    if (data.queue_status) {
                        html += '<p><strong>Total Items:</strong> ' + data.queue_status.total_items + '</p>';
                        html += '<p><strong>Pending:</strong> ' + data.queue_status.pending_items + '</p>';
                        html += '<p><strong>Processing:</strong> ' + data.queue_status.processing_items + '</p>';
                        html += '<p><strong>Completed:</strong> ' + data.queue_status.completed_items + '</p>';
                        html += '<p><strong>Failed:</strong> ' + data.queue_status.failed_items + '</p>';
                    } else {
                        html += '<p>Queue is empty</p>';
                    }
                    
                    if (data.next_run) {
                        html += '<p><strong>Next Run:</strong> ' + data.next_run + '</p>';
                    }
                    
                    $result.html(html).show();
                } else {
                    $result.html('<p style="color: red;">Error: ' + response.data + '</p>').show();
                }
            },
            error: function() {
                $result.html('<p style="color: red;">Connection error</p>').show();
            },
            complete: function() {
                $btn.prop('disabled', false).text('Check Queue Status');
            }
        });
    });
    
    // Process Queue Now
    $('#process_queue_now').click(function() {
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'process_auto_queue_now',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Queue processing started successfully');
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Connection error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Process Queue Now');
            }
        });
    });
    
    // Clear Queue
    $('#clear_queue').click(function() {
        if (!confirm('Are you sure you want to clear the entire queue?')) {
            return;
        }
        
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Clearing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_auto_rewrite_queue',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Queue cleared successfully');
                    $('#get_queue_status').click(); // Refresh status
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Connection error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Clear Queue');
            }
        });
    });
    
    // Reset Processing History
    $('#reset_processed_posts').click(function() {
        if (!confirm('Are you sure? This will allow all previously processed posts to be rewritten again.')) {
            return;
        }
        
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Resetting...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_processed_posts',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Processing history reset successfully');
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Connection error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Reset Processing History');
            }
        });
    });
    
    // Clear Activity Logs
    $('#clear_activity_logs').click(function() {
        if (!confirm('Are you sure you want to clear all activity logs?')) {
            return;
        }
        
        var $btn = $(this);
        
        $btn.prop('disabled', true).text('Clearing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_activity_logs',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Activity logs cleared successfully');
                    loadRecentActivity(); // Refresh activity
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Connection error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Clear Activity Logs');
            }
        });
    });
    
    // Refresh Activity
    $('#refresh_activity').click(function() {
        loadRecentActivity();
    });
    
    function loadRecentActivity() {
        var $display = $('#recent_activity_display');
        
        $display.html('<p>Loading recent activity...</p>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_recent_activity',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success) {
                    $display.html(response.data);
                } else {
                    $display.html('<p style="color: red;">Error loading activity: ' + response.data + '</p>');
                }
            },
            error: function() {
                $display.html('<p style="color: red;">Connection error occurred</p>');
            }
        });
    }
    
    // Load recent activity on page load
    loadRecentActivity();
    
    // Auto-refresh activity every 30 seconds if auto rewrite is enabled
    if ($('#auto_rewrite_enabled').is(':checked')) {
        setInterval(function() {
            loadRecentActivity();
        }, 30000);
    }
});
</script>
