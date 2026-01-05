<?php
// templates/stats-page.php
if (!defined('ABSPATH')) {
    exit;
}

// Get usage statistics
$stats = isset($stats) ? $stats : array();
$summary = isset($stats['summary']) ? $stats['summary'] : array();
$daily_stats = isset($stats['daily_stats']) ? $stats['daily_stats'] : array();
$monthly_stats = isset($stats['monthly_stats']) ? $stats['monthly_stats'] : array();
$error_stats = isset($stats['error_stats']) ? $stats['error_stats'] : array();
$word_stats = isset($stats['word_stats']) ? $stats['word_stats'] : null;

// Default values
$total_cost = isset($summary['total_cost']) ? $summary['total_cost'] : 0;
$total_operations = isset($summary['total_operations']) ? $summary['total_operations'] : 0;
$successful_operations = isset($summary['successful_operations']) ? $summary['successful_operations'] : 0;
$failed_operations = isset($summary['failed_operations']) ? $summary['failed_operations'] : 0;
$success_rate = isset($summary['success_rate']) ? $summary['success_rate'] : 0;
$avg_cost_per_operation = isset($summary['avg_cost_per_operation']) ? $summary['avg_cost_per_operation'] : 0;
?>

<div class="wrap">
    <h1><?php _e('📊 Usage Statistics & Information', 'ai-article-rewriter'); ?></h1>
    
    <div class="ai-rewriter-container">
        
        <!-- Summary Cards -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('📈 Overview Summary', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                    
                    <!-- Total Cost -->
                    <div style="background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #f44336;">
                        <h3 style="margin: 0; color: #c62828; font-size: 28px;">$<?php echo number_format($total_cost, 4); ?></h3>
                        <p style="margin: 5px 0 0 0; color: #c62828; font-weight: 600;">💰 Total API Cost</p>
                        <small style="color: #666; font-size: 11px;">
                            <?php echo $successful_operations > 0 ? '$' . number_format($avg_cost_per_operation, 4) . ' per operation' : 'No operations yet'; ?>
                        </small>
                    </div>
                    
                    <!-- Total Operations -->
                    <div style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #2196f3;">
                        <h3 style="margin: 0; color: #1976d2; font-size: 28px;"><?php echo number_format($total_operations); ?></h3>
                        <p style="margin: 5px 0 0 0; color: #1976d2; font-weight: 600;">🔄 Total Operations</p>
                        <small style="color: #666; font-size: 11px;">All rewrite attempts</small>
                    </div>
                    
                    <!-- Success Rate -->
                    <div style="background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%); padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #4caf50;">
                        <h3 style="margin: 0; color: #388e3c; font-size: 28px;"><?php echo number_format($success_rate, 1); ?>%</h3>
                        <p style="margin: 5px 0 0 0; color: #388e3c; font-weight: 600;">✅ Success Rate</p>
                        <small style="color: #666; font-size: 11px;">
                            <?php echo number_format($successful_operations); ?> successful, <?php echo number_format($failed_operations); ?> failed
                        </small>
                    </div>
                    
                    <!-- Average Cost -->
                    <div style="background: linear-gradient(135deg, #fff3e0 0%, #ffcc80 100%); padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #ff9800;">
                        <h3 style="margin: 0; color: #f57c00; font-size: 28px;">$<?php echo number_format($avg_cost_per_operation, 4); ?></h3>
                        <p style="margin: 5px 0 0 0; color: #f57c00; font-weight: 600;">📊 Avg. Cost/Article</p>
                        <small style="color: #666; font-size: 11px;">Per successful operation</small>
                    </div>
                </div>
                
                <?php if ($word_stats): ?>
                <!-- Word Statistics -->
                <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #6c757d;">
                    <h4 style="margin-top: 0; color: #495057;">📝 Content Statistics</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; font-size: 13px;">
                        <div>
                            <strong>Average Original Words:</strong><br>
                            <span style="color: #007cba; font-size: 16px; font-weight: 600;">
                                <?php echo number_format($word_stats->avg_original_words); ?>
                            </span>
                        </div>
                        <div>
                            <strong>Average New Words:</strong><br>
                            <span style="color: #28a745; font-size: 16px; font-weight: 600;">
                                <?php echo number_format($word_stats->avg_new_words); ?>
                            </span>
                        </div>
                        <div>
                            <strong>Average Change:</strong><br>
                            <span style="color: <?php echo $word_stats->avg_word_change >= 0 ? '#28a745' : '#dc3545'; ?>; font-size: 16px; font-weight: 600;">
                                <?php echo $word_stats->avg_word_change >= 0 ? '+' : ''; ?><?php echo number_format($word_stats->avg_word_change); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Daily Activity Chart -->
        <?php if (!empty($daily_stats)): ?>
        <div class="postbox">
            <h2 class="hndle"><?php _e('📅 Daily Activity (Last 30 Days)', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <div style="overflow-x: auto;">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Operations', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Successful', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Success Rate', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Cost', 'ai-article-rewriter'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($daily_stats, 0, 15) as $day): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($day->date)); ?></td>
                                <td><?php echo number_format($day->operations); ?></td>
                                <td><?php echo number_format($day->successful); ?></td>
                                <td>
                                    <?php 
                                    $day_success_rate = $day->operations > 0 ? ($day->successful / $day->operations) * 100 : 0;
                                    echo number_format($day_success_rate, 1) . '%';
                                    ?>
                                </td>
                                <td>$<?php echo number_format($day->cost, 4); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($daily_stats) > 15): ?>
                <p style="text-align: center; margin-top: 15px; color: #666; font-size: 13px;">
                    Showing last 15 days. <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-logs'); ?>">View full activity logs</a>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Monthly Trends -->
        <?php if (!empty($monthly_stats)): ?>
        <div class="postbox">
            <h2 class="hndle"><?php _e('📊 Monthly Trends (Last 12 Months)', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <div style="overflow-x: auto;">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Month', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Total Operations', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Successful', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Success Rate', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Total Cost', 'ai-article-rewriter'); ?></th>
                                <th><?php _e('Avg Cost/Operation', 'ai-article-rewriter'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_stats as $month): ?>
                            <?php 
                            $month_success_rate = $month->operations > 0 ? ($month->successful / $month->operations) * 100 : 0;
                            $month_avg_cost = $month->successful > 0 ? $month->cost / $month->successful : 0;
                            ?>
                            <tr>
                                <td><?php echo date('M Y', strtotime($month->month . '-01')); ?></td>
                                <td><?php echo number_format($month->operations); ?></td>
                                <td><?php echo number_format($month->successful); ?></td>
                                <td>
                                    <span style="color: <?php echo $month_success_rate >= 90 ? '#28a745' : ($month_success_rate >= 70 ? '#ffc107' : '#dc3545'); ?>;">
                                        <?php echo number_format($month_success_rate, 1); ?>%
                                    </span>
                                </td>
                                <td>$<?php echo number_format($month->cost, 4); ?></td>
                                <td>$<?php echo number_format($month_avg_cost, 4); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Error Analysis -->
        <?php if (!empty($error_stats)): ?>
        <div class="postbox">
            <h2 class="hndle"><?php _e('⚠️ Error Analysis', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <p><?php _e('Most common errors encountered during rewriting operations:', 'ai-article-rewriter'); ?></p>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Error Message', 'ai-article-rewriter'); ?></th>
                            <th><?php _e('Occurrences', 'ai-article-rewriter'); ?></th>
                            <th><?php _e('Last Seen', 'ai-article-rewriter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($error_stats as $error): ?>
                        <tr>
                            <td>
                                <code style="background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-size: 12px;">
                                    <?php echo esc_html(wp_trim_words($error->message, 10)); ?>
                                </code>
                            </td>
                            <td>
                                <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                                    <?php echo number_format($error->error_count); ?>
                                </span>
                            </td>
                            <td><?php echo human_time_diff(strtotime($error->last_occurrence), current_time('timestamp')) . ' ago'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Cost Breakdown -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('💸 Cost Breakdown & Optimization', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    
                    <!-- Cost Analysis -->
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #17a2b8;">
                        <h4 style="margin-top: 0; color: #17a2b8;">💰 Cost Analysis</h4>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
                            <li style="margin-bottom: 8px;">
                                <strong>Total Spent:</strong> $<?php echo number_format($total_cost, 4); ?>
                            </li>
                            <li style="margin-bottom: 8px;">
                                <strong>Per Article:</strong> $<?php echo number_format($avg_cost_per_operation, 4); ?>
                            </li>
                            <li style="margin-bottom: 8px;">
                                <strong>Estimated Monthly:</strong> $<?php echo number_format($total_cost * 30 / max(1, count($daily_stats)), 2); ?>
                            </li>
                            <?php if ($word_stats && $word_stats->avg_new_words > 0): ?>
                            <li style="margin-bottom: 8px;">
                                <strong>Cost per 1000 words:</strong> $<?php echo number_format(($avg_cost_per_operation / $word_stats->avg_new_words) * 1000, 4); ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Optimization Tips -->
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 6px; border-left: 4px solid #28a745;">
                        <h4 style="margin-top: 0; color: #28a745;">🎯 Cost Optimization Tips</h4>
                        <ul style="font-size: 13px; margin: 0;">
                            <?php if ($avg_cost_per_operation > 0.05): ?>
                            <li>Consider using GPT-3.5 Turbo instead of GPT-4 for lower costs</li>
                            <?php endif; ?>
                            <?php if (isset($word_stats->avg_new_words) && $word_stats->avg_new_words > 1000): ?>
                            <li>Reduce max tokens to lower costs per article</li>
                            <?php endif; ?>
                            <li>Use lower temperature settings for more consistent results</li>
                            <li>Process articles in bulk to maximize efficiency</li>
                            <?php if ($success_rate < 90): ?>
                            <li>Improve prompt engineering to reduce failed attempts</li>
                            <?php endif; ?>
                            <li>Monitor usage regularly to stay within budget</li>
                        </ul>
                    </div>
                    
                    <!-- Model Comparison -->
                    <div style="background: #fff3e0; padding: 15px; border-radius: 6px; border-left: 4px solid #ff9800;">
                        <h4 style="margin-top: 0; color: #ff9800;">🤖 Model Comparison</h4>
                        <table style="width: 100%; font-size: 12px;">
                            <thead>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <th style="text-align: left; padding: 4px;">Model</th>
                                    <th style="text-align: right; padding: 4px;">Cost/1K</th>
                                    <th style="text-align: right; padding: 4px;">Quality</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 4px;">GPT-3.5 Turbo</td>
                                    <td style="text-align: right; padding: 4px; color: #28a745;">$0.002</td>
                                    <td style="text-align: right; padding: 4px;">Good</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px;">GPT-4</td>
                                    <td style="text-align: right; padding: 4px; color: #ffc107;">$0.06</td>
                                    <td style="text-align: right; padding: 4px;">Excellent</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px;">GPT-4 Turbo</td>
                                    <td style="text-align: right; padding: 4px; color: #ff9800;">$0.03</td>
                                    <td style="text-align: right; padding: 4px;">Excellent</td>
                                </tr>
                            </tbody>
                        </table>
                        <p style="margin: 10px 0 0 0; font-size: 11px; color: #666;">
                            Current model: <strong><?php echo get_option('ai_rewriter_model', 'gpt-3.5-turbo'); ?></strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('⚙️ System Information', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    
                    <!-- Plugin Info -->
                    <div>
                        <h4><?php _e('Plugin Information', 'ai-article-rewriter'); ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
                            <li><strong>Version:</strong> <?php echo AI_REWRITER_VERSION; ?></li>
                            <li><strong>Database Version:</strong> 
                                <?php 
                                global $wpdb;
                                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}ai_rewriter_logs'");
                                echo $table_exists ? 'Latest' : 'Not installed';
                                ?>
                            </li>
                            <li><strong>Total Records:</strong> 
                                <?php 
                                if ($table_exists) {
                                    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ai_rewriter_logs");
                                    echo number_format($total_records);
                                } else {
                                    echo '0';
                                }
                                ?>
                            </li>
                            <li><strong>Last Activity:</strong> 
                                <?php 
                                if ($table_exists) {
                                    $last_activity = $wpdb->get_var("SELECT MAX(created_at) FROM {$wpdb->prefix}ai_rewriter_logs");
                                    if ($last_activity) {
                                        echo human_time_diff(strtotime($last_activity), current_time('timestamp')) . ' ago';
                                    } else {
                                        echo 'No activity yet';
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Current Settings -->
                    <div>
                        <h4><?php _e('Current Configuration', 'ai-article-rewriter'); ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
                            <li><strong>Model:</strong> <?php echo get_option('ai_rewriter_model', 'gpt-3.5-turbo'); ?></li>
                            <li><strong>Language:</strong> <?php echo get_option('ai_rewriter_language', 'Indonesian'); ?></li>
                            <li><strong>Temperature:</strong> <?php echo get_option('ai_rewriter_temperature', 0.7); ?></li>
                            <li><strong>Max Tokens:</strong> <?php echo number_format(get_option('ai_rewriter_max_tokens', 2000)); ?></li>
                            <li><strong>Auto Publish:</strong> <?php echo get_option('ai_rewriter_auto_publish', 1) ? 'Yes' : 'No'; ?></li>
                            <li><strong>Auto Images:</strong> <?php echo get_option('ai_rewriter_auto_replace_images', 0) ? 'Yes' : 'No'; ?></li>
                        </ul>
                    </div>
                    
                    <!-- WordPress Info -->
                    <div>
                        <h4><?php _e('WordPress Environment', 'ai-article-rewriter'); ?></h4>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
                            <li><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?></li>
                            <li><strong>PHP:</strong> <?php echo PHP_VERSION; ?></li>
                            <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
                            <li><strong>Max Execution:</strong> <?php echo ini_get('max_execution_time'); ?>s</li>
                            <li><strong>Timezone:</strong> <?php echo get_option('timezone_string') ?: 'UTC'; ?></li>
                            <li><strong>Site Language:</strong> <?php echo get_locale(); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('📤 Export & Reports', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    
                    <div>
                        <h4><?php _e('Export Statistics', 'ai-article-rewriter'); ?></h4>
                        <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                            <?php _e('Download detailed reports for analysis and record keeping.', 'ai-article-rewriter'); ?>
                        </p>
                        <button type="button" id="export-stats-csv" class="button button-secondary">
                            📊 <?php _e('Export Statistics (CSV)', 'ai-article-rewriter'); ?>
                        </button>
                        <button type="button" id="export-logs-csv" class="button button-secondary" style="margin-left: 10px;">
                            📋 <?php _e('Export Activity Logs (CSV)', 'ai-article-rewriter'); ?>
                        </button>
                    </div>
                    
                    <div>
                        <h4><?php _e('Quick Actions', 'ai-article-rewriter'); ?></h4>
                        <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                            <?php _e('Useful actions for maintenance and optimization.', 'ai-article-rewriter'); ?>
                        </p>
                        <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-settings'); ?>" class="button button-primary">
                            ⚙️ <?php _e('Optimize Settings', 'ai-article-rewriter'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-logs'); ?>" class="button button-secondary" style="margin-left: 10px;">
                            📋 <?php _e('View Detailed Logs', 'ai-article-rewriter'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($total_operations == 0): ?>
        <!-- Getting Started -->
        <div class="postbox">
            <h2 class="hndle"><?php _e('🚀 Getting Started', 'ai-article-rewriter'); ?></h2>
            <div class="inside">
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; border: 2px dashed #dee2e6;">
                    <h3 style="color: #6c757d; margin-bottom: 15px;">📊 No Data Yet</h3>
                    <p style="color: #6c757d; margin-bottom: 20px; font-size: 14px;">
                        <?php _e('Start rewriting articles to see detailed statistics and cost analysis here.', 'ai-article-rewriter'); ?>
                    </p>
                    <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter'); ?>" class="button button-primary">
                        🤖 <?php _e('Start Rewriting Articles', 'ai-article-rewriter'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=ai-article-rewriter-settings'); ?>" class="button button-secondary" style="margin-left: 10px;">
                        ⚙️ <?php _e('Configure Settings', 'ai-article-rewriter'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Export statistics as CSV
    $('#export-stats-csv').on('click', function() {
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('📊 Exporting...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_statistics_csv',
                nonce: '<?php echo wp_create_nonce('ai_rewriter_export'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([response.data], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'ai-rewriter-statistics-' + new Date().toISOString().split('T')[0] + '.csv';
                    link.click();
                    window.URL.revokeObjectURL(url);
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>📋 Activity logs exported successfully!</p></div>')
                        .insertAfter('.wrap h1').delay(3000).fadeOut();
                } else {
                    alert('Export failed: ' + response.data);
                }
            },
            error: function() {
                alert('Export failed due to network error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Auto-refresh stats every 5 minutes if there's active processing
    let refreshInterval;
    
    function startAutoRefresh() {
        refreshInterval = setInterval(function() {
            // Check if there are any processing indicators on the main page
            if (window.location.href.indexOf('ai-article-rewriter-stats') !== -1) {
                // Refresh only if we're still on the stats page
                location.reload();
            }
        }, 300000); // 5 minutes
    }
    
    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    }
    
    // Start auto-refresh if there are recent activities
    <?php if ($total_operations > 0 && !empty($daily_stats)): ?>
    const hasRecentActivity = <?php echo (count($daily_stats) > 0 && strtotime($daily_stats[0]->date) > strtotime('-1 day')) ? 'true' : 'false'; ?>;
    if (hasRecentActivity) {
        startAutoRefresh();
    }
    <?php endif; ?>
    
    // Stop auto-refresh when leaving page
    $(window).on('beforeunload', function() {
        stopAutoRefresh();
    });
    
    // Enhanced tooltips for cost breakdown
    $('.cost-breakdown').hover(function() {
        const tooltip = $(this).data('tooltip');
        if (tooltip) {
            $('body').append('<div class="ai-stats-tooltip">' + tooltip + '</div>');
            
            const $tooltip = $('.ai-stats-tooltip');
            const offset = $(this).offset();
            
            $tooltip.css({
                position: 'absolute',
                top: offset.top - $tooltip.outerHeight() - 10,
                left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                zIndex: 10000
            });
        }
    }, function() {
        $('.ai-stats-tooltip').remove();
    });
    
    // Real-time cost calculator
    function calculateProjectedCosts() {
        const totalCost = <?php echo $total_cost; ?>;
        const totalOperations = <?php echo $successful_operations; ?>;
        const avgCost = totalOperations > 0 ? totalCost / totalOperations : 0;
        
        if (avgCost > 0) {
            // Add projected cost calculator
            const calculatorHtml = `
                <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #2196f3;">
                    <h4 style="margin-top: 0; color: #1976d2;">🧮 Cost Calculator</h4>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <label style="font-size: 13px;">Articles to process:</label>
                        <input type="number" id="cost-calculator-input" min="1" max="1000" value="10" style="width: 80px; padding: 4px;">
                        <span style="font-size: 13px;">Estimated cost: <strong id="cost-calculator-result">${(avgCost * 10).toFixed(4)}</strong></span>
                    </div>
                </div>
            `;
            
            $('.postbox h2:contains("Cost Breakdown")').siblings('.inside').append(calculatorHtml);
            
            // Update calculator on input
            $('#cost-calculator-input').on('input', function() {
                const articles = parseInt($(this).val()) || 0;
                const estimatedCost = (avgCost * articles).toFixed(4);
                $('#cost-calculator-result').text('notice notice-success is-dismissible"><p>📊 Statistics exported successfully!</p></div>')
                        .insertAfter('.wrap h1').delay(3000).fadeOut();
                } else {
                    alert('Export failed: ' + response.data);
                }
            },
            error: function() {
                alert('Export failed due to network error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Export activity logs as CSV
    $('#export-logs-csv').on('click', function() {
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('📋 Exporting...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_logs_csv',
                nonce: '<?php echo wp_create_nonce('ai_rewriter_export'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([response.data], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'ai-rewriter-logs-' + new Date().toISOString().split('T')[0] + '.csv';
                    link.click();
                    window.URL.revokeObjectURL(url);
                    
                    // Show success message
                    $('<div class=" + estimatedCost);
            });
        }
    }
    
    calculateProjectedCosts();
    
    // Add visual indicators for cost efficiency
    function addCostEfficiencyIndicators() {
        const avgCost = <?php echo $avg_cost_per_operation; ?>;
        const currentModel = '<?php echo get_option('ai_rewriter_model', 'gpt-3.5-turbo'); ?>';
        
        let efficiencyClass = 'good';
        let efficiencyText = 'Efficient';
        let efficiencyColor = '#28a745';
        
        if (currentModel === 'gpt-4' && avgCost > 0.1) {
            efficiencyClass = 'expensive';
            efficiencyText = 'Consider GPT-3.5 Turbo';
            efficiencyColor = '#ffc107';
        } else if (avgCost > 0.05) {
            efficiencyClass = 'moderate';
            efficiencyText = 'Moderate cost';
            efficiencyColor = '#ff9800';
        }
        
        if (avgCost > 0) {
            const indicatorHtml = `
                <div style="margin-top: 10px; padding: 8px 12px; background: rgba(${efficiencyColor === '#28a745' ? '40, 167, 69' : efficiencyColor === '#ffc107' ? '255, 193, 7' : '255, 152, 0'}, 0.1); border-radius: 4px; border-left: 3px solid ${efficiencyColor};">
                    <small style="color: ${efficiencyColor}; font-weight: 600;">💡 ${efficiencyText}</small>
                </div>
            `;
            
            $('.cost-breakdown').append(indicatorHtml);
        }
    }
    
    addCostEfficiencyIndicators();
    
    // Enhanced error analysis with solutions
    $('.error-analysis').each(function() {
        const $errorRows = $(this).find('tbody tr');
        
        $errorRows.each(function() {
            const errorMessage = $(this).find('code').text().toLowerCase();
            let suggestion = '';
            
            if (errorMessage.includes('api key') || errorMessage.includes('unauthorized')) {
                suggestion = '💡 Check your OpenAI API key and billing status';
            } else if (errorMessage.includes('rate limit') || errorMessage.includes('429')) {
                suggestion = '💡 Increase delay between requests in settings';
            } else if (errorMessage.includes('timeout') || errorMessage.includes('network')) {
                suggestion = '💡 Check network connection and reduce max tokens';
            } else if (errorMessage.includes('quota') || errorMessage.includes('billing')) {
                suggestion = '💡 Check your OpenAI account billing and usage limits';
            } else if (errorMessage.includes('model') || errorMessage.includes('invalid')) {
                suggestion = '💡 Verify selected model is available for your account';
            }
            
            if (suggestion) {
                $(this).find('td:last').append(`<br><small style="color: #007cba;">${suggestion}</small>`);
            }
        });
    });
    
    // Performance monitoring
    function addPerformanceMetrics() {
        const successRate = <?php echo $success_rate; ?>;
        const totalOperations = <?php echo $total_operations; ?>;
        
        if (totalOperations > 10) {
            let performanceLevel = 'Excellent';
            let performanceColor = '#28a745';
            
            if (successRate < 70) {
                performanceLevel = 'Needs Improvement';
                performanceColor = '#dc3545';
            } else if (successRate < 85) {
                performanceLevel = 'Good';
                performanceColor = '#ffc107';
            } else if (successRate < 95) {
                performanceLevel = 'Very Good';
                performanceColor = '#28a745';
            }
            
            const performanceHtml = `
                <div style="background: rgba(${performanceColor === '#28a745' ? '40, 167, 69' : performanceColor === '#ffc107' ? '255, 193, 7' : '220, 53, 69'}, 0.1); padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid ${performanceColor};">
                    <h4 style="margin-top: 0; color: ${performanceColor};">⚡ Performance Rating</h4>
                    <p style="margin: 0; font-size: 14px;">
                        <strong style="color: ${performanceColor};">${performanceLevel}</strong> 
                        (${successRate.toFixed(1)}% success rate over ${totalOperations} operations)
                    </p>
                </div>
            `;
            
            $('.postbox h2:contains("Overview Summary")').siblings('.inside').append(performanceHtml);
        }
    }
    
    addPerformanceMetrics();
    
    // Add keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+R to refresh stats
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            location.reload();
        }
        
        // Ctrl+E to export stats
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            $('#export-stats-csv').click();
        }
        
        // Ctrl+L to export logs
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            $('#export-logs-csv').click();
        }
    });
    
    // Add keyboard shortcuts info
    if ($('.keyboard-shortcuts-info').length === 0) {
        $('<div class="keyboard-shortcuts-info" style="position: fixed; bottom: 20px; right: 20px; background: #2c3338; color: #f0f0f1; padding: 10px; border-radius: 6px; font-size: 11px; z-index: 1000; display: none;">')
            .html('⌨️ Shortcuts: Ctrl+R (Refresh) | Ctrl+E (Export Stats) | Ctrl+L (Export Logs)')
            .appendTo('body');
    }
    
    // Show shortcuts on Ctrl key
    $(document).on('keydown', function(e) {
        if (e.ctrlKey) {
            $('.keyboard-shortcuts-info').fadeIn();
        }
    }).on('keyup', function(e) {
        if (!e.ctrlKey) {
            $('.keyboard-shortcuts-info').fadeOut();
        }
    });
    
    // Enhanced responsive behavior
    function handleResponsiveLayout() {
        if ($(window).width() < 782) {
            // Mobile optimizations
            $('.postbox .inside > div[style*="grid"]').css({
                'grid-template-columns': '1fr',
                'gap': '10px'
            });
            
            // Simplify tables on mobile
            $('table.widefat').each(function() {
                if ($(this).find('th').length > 4) {
                    $(this).addClass('mobile-responsive');
                }
            });
        }
    }
    
    handleResponsiveLayout();
    $(window).on('resize', handleResponsiveLayout);
    
    console.log('📊 AI Rewriter Statistics page loaded successfully');
});
</script>

<style>
/* Statistics page specific styles */
.ai-rewriter-container .postbox {
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.ai-rewriter-container .postbox h2 {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid #007cba;
    margin: 0;
    padding: 15px 20px;
    font-size: 16px;
    font-weight: 600;
}

.ai-rewriter-container .postbox .inside {
    padding: 20px;
}

/* Enhanced table styles */
.widefat {
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.widefat th {
    background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
    color: white;
    font-weight: 600;
    padding: 12px 15px;
    border: none;
}

.widefat td {
    padding: 12px 15px;
    border-bottom: 1px solid #f1f1f1;
    vertical-align: middle;
}

.widefat tr:last-child td {
    border-bottom: none;
}

.widefat.striped tbody tr:nth-child(odd) {
    background: rgba(0,124,186,0.02);
}

.widefat tr:hover {
    background: rgba(0,124,186,0.05);
    transition: background-color 0.2s ease;
}

/* Cost indicators */
.cost-high {
    color: #dc3545;
    font-weight: 600;
}

.cost-medium {
    color: #ffc107;
    font-weight: 600;
}

.cost-low {
    color: #28a745;
    font-weight: 600;
}

/* Success rate indicators */
.success-excellent {
    color: #28a745;
    font-weight: 600;
}

.success-good {
    color: #ffc107;
    font-weight: 600;
}

.success-poor {
    color: #dc3545;
    font-weight: 600;
}

/* Enhanced tooltips */
.ai-stats-tooltip {
    background: #2c3338;
    color: #f0f0f1;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    max-width: 200px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 10000;
}

.ai-stats-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border: 5px solid transparent;
    border-top-color: #2c3338;
}

/* Loading states */
.button.loading {
    position: relative;
    opacity: 0.7;
    pointer-events: none;
}

.button.loading::after {
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

/* Responsive design */
@media screen and (max-width: 782px) {
    .ai-rewriter-container .postbox .inside {
        padding: 15px;
    }
    
    .widefat.mobile-responsive,
    .widefat.mobile-responsive thead,
    .widefat.mobile-responsive tbody,
    .widefat.mobile-responsive th,
    .widefat.mobile-responsive td,
    .widefat.mobile-responsive tr {
        display: block;
    }
    
    .widefat.mobile-responsive thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
    }
    
    .widefat.mobile-responsive tr {
        border: 1px solid #dee2e6;
        margin-bottom: 15px;
        padding: 15px;
        border-radius: 6px;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .widefat.mobile-responsive td {
        border: none;
        border-bottom: 1px solid #f1f1f1;
        position: relative;
        padding-left: 35%;
        padding-top: 8px;
        padding-bottom: 8px;
    }
    
    .widefat.mobile-responsive td:before {
        content: attr(data-label);
        position: absolute;
        left: 6px;
        width: 30%;
        padding-right: 10px;
        white-space: nowrap;
        font-weight: 600;
        color: #007cba;
        font-size: 11px;
        text-transform: uppercase;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .ai-rewriter-container .postbox {
        background: #2c3338;
        border-color: #646970;
    }
    
    .ai-rewriter-container .postbox h2 {
        background: linear-gradient(135deg, #23282d 0%, #1d2327 100%);
        color: #f0f0f1;
        border-color: #00a0d2;
    }
    
    .ai-rewriter-container .postbox .inside {
        background: #2c3338;
        color: #f0f0f1;
    }
    
    .widefat th {
        background: linear-gradient(135deg, #00a0d2 0%, #0073aa 100%);
    }
    
    .widefat tr:hover {
        background: rgba(0,160,210,0.1);
    }
}

/* Print styles */
@media print {
    .button, .keyboard-shortcuts-info {
        display: none !important;
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
    
    .widefat th {
        background: #f0f0f0 !important;
        color: #000 !important;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .widefat tr:hover {
        background: #ffff00 !important;
        color: #000 !important;
    }
    
    .ai-rewriter-container .postbox {
        border: 2px solid #000;
    }
}

/* Animation for new data */
.stats-highlight {
    animation: highlightFade 3s ease-out;
}

@keyframes highlightFade {
    0% {
        background-color: rgba(255, 193, 7, 0.3);
    }
    100% {
        background-color: transparent;
    }
}
</style>notice notice-success is-dismissible"><p>📊 Statistics exported successfully!</p></div>')
                        .insertAfter('.wrap h1').delay(3000).fadeOut();
                } else {
                    alert('Export failed: ' + response.data);
                }
            },
            error: function() {
                alert('Export failed due to network error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Export activity logs as CSV
    $('#export-logs-csv').on('click', function() {
        const $button = $(this);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('📋 Exporting...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_logs_csv',
                nonce: '<?php echo wp_create_nonce('ai_rewriter_export'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const blob = new Blob([response.data], { type: 'text/csv' });
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'ai-rewriter-logs-' + new Date().toISOString().split('T')[0] + '.csv';
                    link.click();
                    window.URL.revokeObjectURL(url);
                    
                    // Show success message
                    $('<div class="