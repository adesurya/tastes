<?php
// templates/admin-page.php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('🤖 AI Article Rewriter', 'ai-article-rewriter'); ?></h1>
    
    <div class="ai-rewriter-container">
        
        <!-- Quick Status Overview -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('📊 Overview', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <?php
                $total_drafts = count($draft_posts);
                $processed_posts = get_option('ai_rewriter_processed_posts', array());
                $total_processed = count($processed_posts);
                $api_key = get_option('ai_rewriter_api_key', '');
                $auto_images = get_option('ai_rewriter_auto_replace_images', 0);
                ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    <div style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #2196f3;">
                        <h3 style="margin: 0; color: #1976d2; font-size: 24px;"><?php echo $total_drafts; ?></h3>
                        <p style="margin: 5px 0 0 0; color: #1976d2;">📝 Draft Articles Ready</p>
                    </div>
                    <div style="background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%); padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #4caf50;">
                        <h3 style="margin: 0; color: #388e3c; font-size: 24px;"><?php echo $total_processed; ?></h3>
                        <p style="margin: 5px 0 0 0; color: #388e3c;">✅ Articles Processed</p>
                    </div>
                    <div style="background: linear-gradient(135deg, #fff3e0 0%, #ffcc80 100%); padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #ff9800;">
                        <h3 style="margin: 0; color: #f57c00; font-size: 24px;"><?php echo empty($api_key) ? '❌' : '✅'; ?></h3>
                        <p style="margin: 5px 0 0 0; color: #f57c00;">🔑 API Status</p>
                    </div>
                    <div style="background: linear-gradient(135deg, #f3e5f5 0%, #ce93d8 100%); padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #9c27b0;">
                        <h3 style="margin: 0; color: #7b1fa2; font-size: 24px;"><?php echo $auto_images ? '🖼️' : '📝'; ?></h3>
                        <p style="margin: 5px 0 0 0; color: #7b1fa2;"><?php echo $auto_images ? 'Images Auto-add' : 'Text Only'; ?></p>
                    </div>
                </div>
                
                <?php if (empty($api_key)) : ?>
                    <div style="background: #ffebee; padding: 15px; border-radius: 6px; border-left: 4px solid #f44336; margin-bottom: 20px;">
                        <strong>⚠️ <?php _e('Setup Required:', 'ai-article-rewriter'); ?></strong>
                        <?php _e('Please configure your OpenAI API key in', 'ai-article-rewriter'); ?>
                        <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-settings'); ?>" class="button button-primary" style="margin-left: 10px;">
                            🔧 <?php _e('Settings', 'ai-article-rewriter'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('📄 Draft Articles Ready for Rewriting', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <?php if (empty($draft_posts)) : ?>
                    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
                        <h3 style="color: #6c757d; margin-bottom: 10px;">📝 No Draft Articles Found</h3>
                        <p style="color: #6c757d; margin-bottom: 20px;">
                            <?php _e('Create some draft articles first, then come back here to rewrite them with AI.', 'ai-article-rewriter'); ?>
                        </p>
                        <a href="<?php echo admin_url('post-new.php'); ?>" class="button button-primary">
                            ➕ <?php _e('Create New Article', 'ai-article-rewriter'); ?>
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_status=draft&post_type=post'); ?>" class="button button-secondary" style="margin-left: 10px;">
                            📋 <?php _e('View All Drafts', 'ai-article-rewriter'); ?>
                        </a>
                    </div>
                <?php else : ?>
                    
                    <!-- Help Instructions -->
                    <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                        <h4 style="margin-top: 0;">💡 <?php _e('How to Use:', 'ai-article-rewriter'); ?></h4>
                        <ul style="margin-bottom: 0;">
                            <li><strong><?php _e('Single Rewrite:', 'ai-article-rewriter'); ?></strong> <?php _e('Click "Rewrite" button on any article', 'ai-article-rewriter'); ?></li>
                            <li><strong><?php _e('Bulk Processing:', 'ai-article-rewriter'); ?></strong> <?php _e('Select multiple articles using checkboxes', 'ai-article-rewriter'); ?></li>
                            <li><strong><?php _e('Smart Features:', 'ai-article-rewriter'); ?></strong> <?php _e('Auto-numbering fix, image replacement, duplicate prevention', 'ai-article-rewriter'); ?></li>
                            <li><strong><?php _e('Keyboard Shortcuts:', 'ai-article-rewriter'); ?></strong> <?php _e('Ctrl+Shift+R for bulk rewrite, Ctrl+A to select all', 'ai-article-rewriter'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="ai-rewriter-posts">
                        <table class="widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 5%">
                                        <input type="checkbox" id="select-all-posts" title="<?php _e('Select All Articles', 'ai-article-rewriter'); ?>">
                                    </th>
                                    <th style="width: 45%"><?php _e('📄 Title & Content', 'ai-article-rewriter'); ?></th>
                                    <th style="width: 15%"><?php _e('📅 Date Created', 'ai-article-rewriter'); ?></th>
                                    <th style="width: 15%"><?php _e('📊 Word Count', 'ai-article-rewriter'); ?></th>
                                    <th style="width: 20%"><?php _e('⚡ Action', 'ai-article-rewriter'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($draft_posts as $post) : ?>
                                    <?php
                                    $word_count = str_word_count(strip_tags($post->post_content));
                                    $content_preview = wp_trim_words(strip_tags($post->post_content), 15);
                                    $is_processed = get_post_meta($post->ID, '_ai_rewriter_processed', true);
                                    ?>
                                    <tr data-post-id="<?php echo $post->ID; ?>" <?php echo $is_processed ? 'class="already-processed"' : ''; ?>>
                                        <td data-label="Select">
                                            <?php if (!$is_processed) : ?>
                                                <input type="checkbox" class="bulk-checkbox" style="margin-right: 10px;">
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Title">
                                            <div>
                                                <strong style="font-size: 14px; color: #23282d;">
                                                    <?php echo esc_html($post->post_title ?: __('(No title)', 'ai-article-rewriter')); ?>
                                                </strong>
                                                <?php if ($is_processed) : ?>
                                                    <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-left: 8px;">
                                                        ✅ <?php _e('Processed', 'ai-article-rewriter'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="color: #666; font-size: 13px; margin-top: 5px; line-height: 1.4;">
                                                <?php echo esc_html($content_preview); ?>
                                                <?php if (strlen(strip_tags($post->post_content)) > 100) echo '...'; ?>
                                            </div>
                                            <div class="row-actions" style="margin-top: 8px;">
                                                <span class="edit">
                                                    <a href="<?php echo get_edit_post_link($post->ID); ?>" title="<?php _e('Edit this article', 'ai-article-rewriter'); ?>">
                                                        ✏️ <?php _e('Edit', 'ai-article-rewriter'); ?>
                                                    </a> |
                                                </span>
                                                <span class="view">
                                                    <a href="<?php echo get_permalink($post->ID); ?>" target="_blank" title="<?php _e('Preview this article', 'ai-article-rewriter'); ?>">
                                                        👁️ <?php _e('Preview', 'ai-article-rewriter'); ?>
                                                    </a>
                                                </span>
                                                <?php if ($is_processed) : ?>
                                                    <span style="color: #28a745; font-size: 12px; margin-left: 10px;">
                                                        | ✅ <?php echo sprintf(__('Processed on %s', 'ai-article-rewriter'), date_i18n(get_option('date_format'), strtotime($is_processed))); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="Date">
                                            <div style="font-size: 13px;">
                                                📅 <?php echo date_i18n(get_option('date_format'), strtotime($post->post_date)); ?>
                                            </div>
                                            <div style="color: #666; font-size: 12px;">
                                                <?php echo human_time_diff(strtotime($post->post_date), current_time('timestamp')); ?> <?php _e('ago', 'ai-article-rewriter'); ?>
                                            </div>
                                        </td>
                                        <td data-label="Words">
                                            <div style="font-size: 14px; font-weight: 600;">
                                                📊 <?php echo number_format($word_count); ?> <?php _e('words', 'ai-article-rewriter'); ?>
                                            </div>
                                            <div style="color: #666; font-size: 12px;">
                                                <?php
                                                if ($word_count < 50) {
                                                    echo '<span style="color: #dc3545;">⚠️ ' . __('Too short', 'ai-article-rewriter') . '</span>';
                                                } elseif ($word_count > 4000) {
                                                    echo '<span style="color: #ffc107;">⚠️ ' . __('Very long', 'ai-article-rewriter') . '</span>';
                                                } else {
                                                    echo '<span style="color: #28a745;">✅ ' . __('Good length', 'ai-article-rewriter') . '</span>';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td data-label="Action">
                                            <?php if (!$is_processed) : ?>
                                                <button type="button" class="button button-primary rewrite-btn" 
                                                        data-post-id="<?php echo $post->ID; ?>"
                                                        title="<?php _e('Rewrite this article with AI', 'ai-article-rewriter'); ?>">
                                                    🤖 <?php _e('Rewrite', 'ai-article-rewriter'); ?>
                                                </button>
                                                <span class="spinner" style="float: none; visibility: hidden;"></span>
                                            <?php else : ?>
                                                <button type="button" class="button button-secondary" disabled>
                                                    ✅ <?php _e('Already Processed', 'ai-article-rewriter'); ?>
                                                </button>
                                                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                                    <?php _e('Processed articles are protected from duplicate processing', 'ai-article-rewriter'); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($draft_posts)) : ?>
            <!-- Bulk Actions -->
            <div class="postbox">
                <h2 class="hndle"><?php _e('🚀 Bulk Actions', 'ai-article-rewriter'); ?></h2>
                <div class="inside">
                    <p><?php _e('Select multiple articles to rewrite them all at once. This is more efficient for processing many articles.', 'ai-article-rewriter'); ?></p>
                    
                    <div class="bulk-actions-container">
                        <label style="margin-bottom: 15px; display: block;">
                            <input type="checkbox" id="select-all-posts-bulk" style="margin-right: 8px;"> 
                            <?php _e('Select All Available Articles', 'ai-article-rewriter'); ?>
                            <span style="font-size: 12px; color: #666; margin-left: 10px;">
                                (<?php echo count(array_filter($draft_posts, function($post) { return !get_post_meta($post->ID, '_ai_rewriter_processed', true); })); ?> articles available)
                            </span>
                        </label>
                        
                        <div style="margin-bottom: 15px;">
                            <button type="button" class="button button-secondary" id="bulk-rewrite-btn" disabled>
                                🚀 <?php _e('Rewrite Selected Articles', 'ai-article-rewriter'); ?>
                            </button>
                            <span class="keyboard-shortcut" style="margin-left: 10px;">Ctrl+Shift+R</span>
                        </div>
                        
                        <div class="bulk-progress" style="display: none; margin-top: 20px;">
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <div class="progress-text">0 / 0 articles processed</div>
                        </div>
                        
                        <!-- Performance metrics will be inserted here by JavaScript -->
                        <div class="performance-container"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Activity Log -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('📋 Recent Activity Log', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <div id="activity-log">
                    <?php
                    $activity_logs = get_option('ai_rewriter_activity_log', array());
                    if (empty($activity_logs)) {
                        echo '<p>' . __('📝 Activity will be shown here...', 'ai-article-rewriter') . '</p>';
                    } else {
                        $recent_logs = array_slice(array_reverse($activity_logs), 0, 10);
                        foreach ($recent_logs as $log) {
                            $level = isset($log['level']) ? $log['level'] : 'info';
                            $icons = array(
                                'info' => 'ℹ️',
                                'success' => '✅',
                                'error' => '❌',
                                'warning' => '⚠️',
                                'debug' => '🔧'
                            );
                            $icon = isset($icons[$level]) ? $icons[$level] : 'ℹ️';
                            
                            echo '<div class="activity-item ' . esc_attr($level) . '">';
                            echo '<span class="activity-time">' . esc_html(date('H:i:s', strtotime($log['time']))) . '</span> ';
                            echo '<span class="activity-message">' . $icon . ' ' . esc_html($log['message']) . '</span>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                
                <?php if (!empty($activity_logs)) : ?>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-logs'); ?>" class="button button-secondary">
                            📊 <?php _e('View Full Activity Log', 'ai-article-rewriter'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Settings & Help -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            
            <!-- Quick Settings -->
            <div class="postbox">
                <h2 class="hndle"><?php _e('⚙️ Quick Settings', 'ai-article-rewriter'); ?></h2>
                <div class="inside">
                    <?php
                    $current_language = get_option('ai_rewriter_language', 'Indonesian');
                    $current_style = get_option('ai_rewriter_writing_style', 'professional');
                    $auto_images = get_option('ai_rewriter_auto_replace_images', 0);
                    ?>
                    
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Current Language:', 'ai-article-rewriter'); ?></strong>
                        <span style="margin-left: 10px; padding: 4px 8px; background: #e3f2fd; border-radius: 4px; font-size: 12px;">
                            <?php echo esc_html($current_language); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Writing Style:', 'ai-article-rewriter'); ?></strong>
                        <span style="margin-left: 10px; padding: 4px 8px; background: #e8f5e8; border-radius: 4px; font-size: 12px;">
                            <?php echo esc_html(ucfirst($current_style)); ?>
                        </span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong><?php _e('Auto Images:', 'ai-article-rewriter'); ?></strong>
                        <span style="margin-left: 10px; padding: 4px 8px; background: <?php echo $auto_images ? '#e8f5e8' : '#fff3cd'; ?>; border-radius: 4px; font-size: 12px;">
                            <?php echo $auto_images ? '✅ Enabled' : '❌ Disabled'; ?>
                        </span>
                    </div>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-settings'); ?>" class="button button-primary">
                        🔧 <?php _e('Modify Settings', 'ai-article-rewriter'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Help & Tips -->
            <div class="postbox">
                <h2 class="hndle"><?php _e('💡 Tips & Help', 'ai-article-rewriter'); ?></h2>
                <div class="inside">
                    <div style="margin-bottom: 15px;">
                        <strong style="color: #007cba;">🎯 Best Practices:</strong>
                        <ul style="margin-top: 8px; font-size: 13px; color: #666;">
                            <li>Articles should be 50+ words for best results</li>
                            <li>Review rewritten content before publishing</li>
                            <li>Use bulk processing for efficiency</li>
                            <li>Check activity log for processing status</li>
                        </ul>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <strong style="color: #28a745;">✨ Smart Features:</strong>
                        <ul style="margin-top: 8px; font-size: 13px; color: #666;">
                            <li>Auto-fixes numbered lists (1., 2., 3.)</li>
                            <li>Prevents duplicate processing</li>
                            <li>Adds relevant images automatically</li>
                            <li>Real-time progress tracking</li>
                        </ul>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 4px solid #007cba;">
                        <strong style="font-size: 12px; color: #007cba;">ℹ️ <?php _e('Need Help?', 'ai-article-rewriter'); ?></strong>
                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                            <?php _e('Check the activity log for detailed processing information and error messages.', 'ai-article-rewriter'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template for activity log item (used by JavaScript) -->
<script type="text/template" id="activity-log-template">
    <div class="activity-item <%= status %>">
        <span class="activity-time"><%= time %></span>
        <span class="activity-message"><%= message %></span>
    </div>
</script>

<!-- Enhanced JavaScript for admin page -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Sync the two select-all checkboxes
    $('#select-all-posts').on('change', function() {
        $('#select-all-posts-bulk').prop('checked', $(this).is(':checked'));
    });
    
    $('#select-all-posts-bulk').on('change', function() {
        $('#select-all-posts').prop('checked', $(this).is(':checked')).trigger('change');
    });
    
    // Enhanced row interactions
    $('.ai-rewriter-posts tbody tr').hover(
        function() {
            if (!$(this).hasClass('already-processed')) {
                $(this).addClass('hovered');
            }
        },
        function() {
            $(this).removeClass('hovered');
        }
    );
    
    // Auto-refresh activity log every 30 seconds when processing
    let refreshInterval;
    let isCurrentlyProcessing = false;
    
    function startLogRefresh() {
        refreshInterval = setInterval(function() {
            if (isCurrentlyProcessing) {
                refreshActivityLog();
            }
        }, 30000);
    }
    
    function stopLogRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    }
    
    function refreshActivityLog() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_recent_activity',
                nonce: aiRewriter.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    const $log = $('#activity-log');
                    $log.html(response.data);
                }
            }
        });
    }
    
    // Track processing state
    $(document).on('processing-start', function() {
        isCurrentlyProcessing = true;
    });
    
    $(document).on('processing-end', function() {
        isCurrentlyProcessing = false;
    });
    
    // Initialize refresh when page loads
    startLogRefresh();
    
    // Stop refresh when page unloads
    $(window).on('beforeunload', function() {
        stopLogRefresh();
    });
    
    // Add visual feedback for successful operations
    $(document).on('rewrite-success', function(e, data) {
        const $row = $('tr[data-post-id="' + data.postId + '"]');
        $row.addClass('success-highlight');
        setTimeout(() => $row.removeClass('success-highlight'), 2000);
    });
    
    // Enhanced error handling display
    $(document).on('rewrite-error', function(e, data) {
        const $row = $('tr[data-post-id="' + data.postId + '"]');
        $row.addClass('error-highlight');
        setTimeout(() => $row.removeClass('error-highlight'), 3000);
    });
    
    // Add contextual tooltips
    $('.rewrite-btn').each(function() {
        const postId = $(this).data('post-id');
        const $row = $(this).closest('tr');
        const wordCountText = $row.find('[data-label="Words"]').text();
        const wordCount = wordCountText.match(/\d+/);
        if (wordCount) {
            $(this).attr('title', `Rewrite article with ${wordCount[0]} words using AI`);
        }
    });
    
    // Prevent accidental page refresh during processing
    let processingCount = 0;
    
    function updateProcessingCount(increment) {
        processingCount += increment;
        processingCount = Math.max(0, processingCount);
        updatePageTitle();
        
        if (processingCount > 0) {
            $(document).trigger('processing-start');
        } else {
            $(document).trigger('processing-end');
        }
    }
    
    function updatePageTitle() {
        const originalTitle = document.title.replace(/^\(\d+\)\s*/, '');
        if (processingCount > 0) {
            document.title = `(${processingCount}) ${originalTitle}`;
        } else {
            document.title = originalTitle;
        }
    }
    
    // Track AJAX requests
    $(document).ajaxStart(function() {
        $('.rewrite-btn:not(.loading)').prop('disabled', true).addClass('ajax-loading');
    }).ajaxStop(function() {
        $('.rewrite-btn.ajax-loading').prop('disabled', false).removeClass('ajax-loading');
    });
    
    // Enhanced accessibility
    $('.bulk-checkbox').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            $(this).click();
        }
    });
    
    // Add processing indicators for individual articles
    $('.rewrite-btn').on('click', function() {
        const $button = $(this);
        const $row = $button.closest('tr');
        
        updateProcessingCount(1);
        $row.addClass('processing-individual');
        
        // Add estimated time based on word count
        const wordCountText = $row.find('[data-label="Words"]').text();
        const wordCount = parseInt(wordCountText.match(/\d+/)[0]);
        const estimatedTime = Math.max(10, Math.round(wordCount / 100));
        
        if (!$button.siblings('.processing-estimate').length) {
            $button.after(`<div class="processing-estimate" style="font-size: 11px; color: #666; margin-top: 5px;">⏱️ Estimated: ~${estimatedTime}s</div>`);
        }
    });
    
    // Cleanup processing indicators on completion
    $(document).on('ajax-complete', function() {
        updateProcessingCount(-1);
        $('.processing-individual').removeClass('processing-individual');
        $('.processing-estimate').fadeOut(300, function() {
            $(this).remove();
        });
    });
    
    // Add visual feedback for API connection status
    function checkAPIStatus() {
        const hasApiKey = <?php echo !empty(get_option('ai_rewriter_api_key', '')) ? 'true' : 'false'; ?>;
        if (!hasApiKey) {
            $('.ai-rewriter-container').prepend(`
                <div class="notice notice-warning" style="margin-bottom: 20px;">
                    <p><strong>⚠️ Setup Required:</strong> Please configure your OpenAI API key to start rewriting articles. 
                    <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-settings'); ?>" class="button button-small">Configure Now</a></p>
                </div>
            `);
        }
    }
    
    checkAPIStatus();
    
    // Add smooth animations for state changes
    $('.ai-rewriter-posts tbody tr').on('transitionend', function() {
        $(this).removeClass('transitioning');
    });
    
    // Enhanced bulk checkbox behavior
    $('.bulk-checkbox').on('change', function() {
        const $row = $(this).closest('tr');
        if ($(this).is(':checked')) {
            $row.addClass('selected');
        } else {
            $row.removeClass('selected');
        }
    });
    
    // Add keyboard navigation
    $(document).on('keydown', function(e) {
        // Escape to clear selections
        if (e.key === 'Escape') {
            $('.bulk-checkbox').prop('checked', false).trigger('change');
            $('#select-all-posts, #select-all-posts-bulk').prop('checked', false);
        }
        
        // Space to toggle checkbox when focused
        if (e.key === ' ' && $(e.target).hasClass('bulk-checkbox')) {
            e.preventDefault();
            $(e.target).click();
        }
    });
    
    // Real-time stats update
    function updateBulkStats() {
        const totalArticles = $('.bulk-checkbox').length;
        const selectedArticles = $('.bulk-checkbox:checked').length;
        const processedArticles = $('.already-processed').length;
        
        // Update bulk action text
        const $bulkBtn = $('#bulk-rewrite-btn');
        if (selectedArticles > 0) {
            $bulkBtn.text(`🚀 Rewrite ${selectedArticles} Selected Article${selectedArticles > 1 ? 's' : ''}`);
        } else {
            $bulkBtn.text('🚀 Rewrite Selected Articles');
        }
    }
    
    // Update stats when checkboxes change
    $(document).on('change', '.bulk-checkbox, #select-all-posts, #select-all-posts-bulk', updateBulkStats);
    
    // Initialize stats
    updateBulkStats();
    
    // Auto-save scroll position
    let scrollPosition = sessionStorage.getItem('ai-rewriter-scroll');
    if (scrollPosition) {
        $(window).scrollTop(scrollPosition);
        sessionStorage.removeItem('ai-rewriter-scroll');
    }
    
    $(window).on('scroll', function() {
        sessionStorage.setItem('ai-rewriter-scroll', $(window).scrollTop());
    });
    
    // Add loading indicators to overview cards
    $('.postbox h2:contains("Overview")').siblings('.inside').find('div[style*="grid"]').on('click', 'div', function() {
        const $card = $(this);
        const cardType = $card.find('p').text().toLowerCase();
        
        if (cardType.includes('api')) {
            window.location.href = '<?php echo admin_url('admin.php?page=ai-article-rewriter-settings'); ?>';
        } else if (cardType.includes('draft')) {
            $('html, body').animate({
                scrollTop: $('.ai-rewriter-posts').offset().top - 100
            }, 500);
        }
    });
    
    // Add tooltips to overview cards
    $('.postbox h2:contains("Overview")').siblings('.inside').find('div[style*="grid"] > div').each(function() {
        const $card = $(this);
        const cardText = $card.find('p').text();
        
        if (cardText.includes('Draft')) {
            $card.attr('title', 'Click to scroll to articles list').css('cursor', 'pointer');
        } else if (cardText.includes('API')) {
            $card.attr('title', 'Click to go to Settings').css('cursor', 'pointer');
        }
    });
    
    // Enhanced error recovery
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled promise rejection:', event.reason);
        
        // Add to activity log if possible
        if (typeof addActivityLog === 'function') {
            addActivityLog(`💥 Unexpected error: ${event.reason}`, 'error');
        }
    });
    
    // Auto-hide success messages after delay
    $('.notice-success').delay(5000).fadeOut();
    
    // Add focus management for accessibility
    $('.rewrite-btn').on('focus', function() {
        $(this).closest('tr').addClass('focused');
    }).on('blur', function() {
        $(this).closest('tr').removeClass('focused');
    });
    
    // Initialize tooltips
    if (typeof $.fn.tooltip === 'function') {
        $('[title]').tooltip({
            position: { my: "left+15 center", at: "right center" }
        });
    }
    
    // Performance optimization: Virtual scrolling for large lists
    if ($('.ai-rewriter-posts tbody tr').length > 50) {
        console.log('Large article list detected, consider implementing virtual scrolling');
    }
    
    // Add print styles handling
    window.addEventListener('beforeprint', function() {
        $('.spinner, .bulk-actions-container, #activity-log').hide();
    });
    
    window.addEventListener('afterprint', function() {
        $('.spinner, .bulk-actions-container, #activity-log').show();
    });
});
</script>

<style>
/* Enhanced admin page specific styles */
.ai-rewriter-posts tr {
    transition: all 0.2s ease;
}

.ai-rewriter-posts tr:hover {
    background-color: #f8f9fa !important;
}

.ai-rewriter-posts tr.hovered {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.ai-rewriter-posts tr.already-processed {
    background-color: #f8f9f8;
    opacity: 0.8;
}

.ai-rewriter-posts tr.already-processed td {
    color: #6c757d;
}

.ai-rewriter-posts tr.selected {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
    border-left: 4px solid #2196f3;
}

.ai-rewriter-posts tr.focused {
    outline: 2px solid #007cba;
    outline-offset: 2px;
}

.ai-rewriter-posts tr.success-highlight {
    background: linear-gradient(90deg, #d4edda, #c3e6cb, #d4edda);
    animation: successPulse 2s ease-out;
}

.ai-rewriter-posts tr.error-highlight {
    background: linear-gradient(90deg, #f8d7da, #f5c6cb, #f8d7da);
    animation: errorPulse 2s ease-out;
}

.ai-rewriter-posts tr.processing-individual {
    background: linear-gradient(90deg, #fff3cd, #ffeaa7, #fff3cd);
    animation: processingPulse 2s infinite;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}

@keyframes errorPulse {
    0% { transform: scale(1); }
    25% { transform: scale(1.01); }
    75% { transform: scale(1.01); }
    100% { transform: scale(1); }
}

@keyframes processingPulse {
    0% { opacity: 1; }
    50% { opacity: 0.8; }
    100% { opacity: 1; }
}

.button.ajax-loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.button.ajax-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border: 2px solid #ffffff;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Overview cards hover effects */
.postbox h2:contains("Overview") + .inside div[style*="grid"] > div {
    transition: all 0.3s ease;
    cursor: default;
}

.postbox h2:contains("Overview") + .inside div[style*="grid"] > div:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Responsive improvements */
@media screen and (max-width: 782px) {
    .ai-rewriter-container > div[style*="grid"] {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    
    .ai-rewriter-posts table {
        font-size: 14px;
    }
    
    .bulk-actions-container {
        text-align: center;
    }
    
    .keyboard-shortcut {
        display: none;
    }
    
    .ai-rewriter-posts tr.selected {
        border-left: none;
        border-top: 4px solid #2196f3;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .ai-rewriter-posts tr.already-processed {
        background-color: #2d3336;
    }
    
    .ai-rewriter-posts tr:hover {
        background-color: #3c434a !important;
    }
    
    .ai-rewriter-posts tr.selected {
        background: linear-gradient(135deg, #1e2a36 0%, #2a3a4a 100%) !important;
    }
}

/* Print styles */
@media print {
    .button, .bulk-actions-container, #activity-log, .spinner {
        display: none !important;
    }
    
    .ai-rewriter-posts table {
        border-collapse: collapse;
        page-break-inside: auto;
    }
    
    .ai-rewriter-posts th,
    .ai-rewriter-posts td {
        border: 1px solid #ddd;
        padding: 8px;
        page-break-inside: avoid;
    }
    
    .ai-rewriter-posts tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    .postbox {
        border: 1px solid #000;
        page-break-inside: avoid;
        margin-bottom: 20px;
    }
    
    .postbox h2 {
        background: #f0f0f0 !important;
        color: #000 !important;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .ai-rewriter-posts tr.selected {
        background: #ffff00 !important;
        color: #000 !important;
    }
    
    .ai-rewriter-posts tr:hover {
        background: #e0e0e0 !important;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .ai-rewriter-posts tr,
    .button,
    .postbox > div {
        transition: none !important;
        animation: none !important;
    }
    
    .ai-rewriter-posts tr.hovered {
        transform: none !important;
    }
}

/* Focus indicators for keyboard navigation */
.bulk-checkbox:focus {
    outline: 2px solid #007cba;
    outline-offset: 2px;
}

#select-all-posts:focus,
#select-all-posts-bulk:focus {
    outline: 2px solid #007cba;
    outline-offset: 2px;
}

/* Loading state improvements */
.processing-estimate {
    background: rgba(255, 193, 7, 0.1);
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

/* Accessibility improvements */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Enhanced hover states */
.rewrite-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
}

/* Status badges */
.ai-rewriter-posts .status-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.processed {
    background: #28a745;
    color: white;
}

.status-badge.ready {
    background: #007cba;
    color: white;
}

.status-badge.warning {
    background: #ffc107;
    color: #212529;
}

.status-badge.error {
    background: #dc3545;
    color: white;
}
</style>