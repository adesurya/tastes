/**
 * AI Article Rewriter Admin JavaScript
 * File: assets/admin.js
 */

(function($) {
    'use strict';

    // Main plugin object
    const AIRewriter = {
        init: function() {
            this.bindEvents();
            this.initComponents();
            this.checkAPIStatus();
        },

        bindEvents: function() {
            // Article rewriting
            $(document).on('click', '.rewrite-btn', this.handleRewriteClick);
            
            // API testing
            $(document).on('click', '#test-api-connection', this.testAPIConnection);
            
            // Settings management
            $(document).on('click', '#regenerate-api-key', this.regenerateAPIKey);
            $(document).on('change', '#ai_rewriter_api_key', this.onAPIKeyChange);
            
            // Activity log refresh
            $(document).on('click', '#refresh-activity', this.refreshActivity);
            
            // Clear functions
            $(document).on('click', '#clear-logs', this.clearLogs);
            $(document).on('click', '#reset-processed', this.resetProcessed);
            
            // Auto-save settings
            $(document).on('change', '.ai-rewriter-auto-save', this.autoSaveSettings);
            
            // Bulk operations
            $(document).on('click', '#bulk-rewrite-selected', this.bulkRewriteSelected);
            $(document).on('change', '#select-all-articles', this.toggleSelectAll);
            
            // Notifications
            $(document).on('click', '.ai-rewriter-notification-dismiss', this.dismissNotification);
        },

        initComponents: function() {
            this.initProgressBars();
            this.initTooltips();
            this.initActivityLogRefresh();
            this.initRealTimeStats();
        },

        // Article rewriting functionality
        handleRewriteClick: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $card = $btn.closest('.ai-rewriter-article-card');
            const postId = $btn.data('post-id');
            
            if (!postId) {
                AIRewriter.showNotification('Error: Invalid post ID', 'error');
                return;
            }

            AIRewriter.processArticle(postId, $btn, $card);
        },

        processArticle: function(postId, $btn, $card) {
            // Update UI to processing state
            $btn.prop('disabled', true)
                .html('<span class="dashicons dashicons-update-alt"></span> Processing...')
                .addClass('ai-rewriter-loading');
            
            $card.addClass('processing');

            // Add progress bar
            const $progress = $('<div class="ai-rewriter-progress ai-rewriter-progress-indeterminate"><div class="ai-rewriter-progress-bar"></div></div>');
            $card.find('.ai-rewriter-article-actions').before($progress);

            $.ajax({
                url: aiRewriter.ajaxurl,
                method: 'POST',
                data: {
                    action: 'rewrite_article',
                    post_id: postId,
                    nonce: aiRewriter.nonce
                },
                timeout: 120000, // 2 minutes timeout
                success: function(response) {
                    if (response.success) {
                        AIRewriter.handleRewriteSuccess(response.data, $card, $btn);
                    } else {
                        AIRewriter.handleRewriteError(response.data, $card, $btn);
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = 'Network error occurred';
                    if (status === 'timeout') {
                        errorMsg = 'Request timed out. The article may still be processing.';
                    } else if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMsg = xhr.responseJSON.data;
                    }
                    AIRewriter.handleRewriteError(errorMsg, $card, $btn);
                },
                complete: function() {
                    $progress.remove();
                }
            });
        },

        handleRewriteSuccess: function(data, $card, $btn) {
            $card.removeClass('processing').addClass('completed ai-rewriter-fade-in');
            
            // Update card content
            const successHTML = `
                <div class="ai-rewriter-text-center">
                    <div style="color: #28a745; font-size: 48px; margin-bottom: 10px;">✅</div>
                    <h3 style="color: #28a745; margin-bottom: 15px;">Successfully Rewritten & Published!</h3>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                        <strong>New Title:</strong><br>
                        <em>${this.escapeHtml(data.title)}</em>
                    </div>
                    <div style="font-size: 13px; color: #666;">
                        Status: ${data.status} | Content: ${this.escapeHtml(data.content)}
                    </div>
                </div>
            `;
            
            $card.html(successHTML);
            
            this.showNotification('Article successfully rewritten and published!', 'success');
            this.updateStats();
        },

        handleRewriteError: function(errorMsg, $card, $btn) {
            $card.removeClass('processing');
            
            $btn.prop('disabled', false)
                .removeClass('ai-rewriter-loading')
                .html('<span class="dashicons dashicons-edit"></span> Retry Rewrite');
            
            this.showNotification('Error: ' + errorMsg, 'error');
        },

        // API testing
        testAPIConnection: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $status = $('#api-status');
            const apiKey = $('#ai_rewriter_api_key').val();
            
            if (!apiKey || apiKey.trim() === '') {
                AIRewriter.showNotification('Please enter an API key first', 'warning');
                return;
            }
            
            $btn.prop('disabled', true).text('Testing...');
            $status.html('<span style="color: #ffc107;">🔄 Testing connection...</span>');
            
            $.ajax({
                url: aiRewriter.ajaxurl,
                method: 'POST',
                data: {
                    action: 'test_api_connection',
                    api_key: apiKey,
                    nonce: aiRewriter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $status.html('<span style="color: #28a745;">✅ ' + response.data + '</span>');
                        AIRewriter.showNotification('API connection successful!', 'success');
                    } else {
                        $status.html('<span style="color: #dc3545;">❌ ' + response.data + '</span>');
                        AIRewriter.showNotification('API connection failed: ' + response.data, 'error');
                    }
                },
                error: function() {
                    $status.html('<span style="color: #dc3545;">❌ Network error</span>');
                    AIRewriter.showNotification('Network error during API test', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Test Connection');
                }
            });
        },

        onAPIKeyChange: function() {
            $('#api-status').html('<span style="color: #666;">Enter API key and test connection</span>');
        },

        // Settings management
        regenerateAPIKey: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to regenerate the API key? This will invalidate the current key and may break existing integrations.')) {
                return;
            }
            
            const $btn = $(this);
            $btn.prop('disabled', true).text('Regenerating...');
            
            $.ajax({
                url: aiRewriter.ajaxurl,
                method: 'POST',
                data: {
                    action: 'regenerate_api_key',
                    nonce: aiRewriter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#current-api-key').text(response.data.new_key);
                        AIRewriter.showNotification('API key regenerated successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        AIRewriter.showNotification('Failed to regenerate API key', 'error');
                    }
                },
                error: function() {
                    AIRewriter.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Regenerate');
                }
            });
        },

        autoSaveSettings: function() {
            const $field = $(this);
            const fieldName = $field.attr('name');
            const fieldValue = $field.is(':checkbox') ? ($field.is(':checked') ? 1 : 0) : $field.val();
            
            // Show saving indicator
            const $indicator = $('<span class="ai-rewriter-save-indicator" style="color: #ffc107; margin-left: 10px;">💾 Saving...</span>');
            $field.after($indicator);
            
            $.ajax({
                url: aiRewriter.ajaxurl,
                method: 'POST',
                data: {
                    action: 'auto_save_setting',
                    field_name: fieldName,
                    field_value: fieldValue,
                    nonce: aiRewriter.nonce
                },
                success: function(response) {
                    $indicator.html('<span style="color: #28a745;">✅ Saved</span>');
                    setTimeout(() => $indicator.fadeOut(), 2000);
                },
                error: function() {
                    $indicator.html('<span style="color: #dc3545;">❌ Error</span>');
                    setTimeout(() => $indicator.fadeOut(), 3000);
                }
            });
        },

        // Activity and logs
        refreshActivity: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const $container = $('#recent-activity');
            
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> Refreshing...');
            
            $.ajax({
                url: aiRewriter.ajaxurl,
                method: 'POST',
                data: {
                    action: 'get_recent_activity',
                    nonce: aiRewriter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data).addClass('ai-rewriter-fade-in');
                    }
                },
                error: function() {
                    $container.html('<p style="color: #dc3545;">Failed to load activity</p>');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Refresh');
                }
            });
        },

        clearLogs: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to clear all activity logs? This action cannot be undone.')) {
                return;
            }
            
            const $btn = $(this);
            $btn.prop('disabled', true).text('Clearing...');
            
            $.ajax({
                url: aiRewriter.ajaxurl,
                method: 'POST',
                data: {
                    action: 'clear_activity_logs',
                    nonce: aiRewriter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#recent-activity').html('<p>No activity logged yet.</p>');
                        AIRewriter.showNotification('Activity logs cleared successfully', 'success');
                    } else {
                        AIRewriter.showNotification('Failed to clear logs', 'error');
                    }
                },
                error: function() {
                    AIRewriter.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Clear Logs');
                }
            });
        },

        resetProcessed: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to reset the processing history? This will allow all articles to be rewritten again.')) {
                return;
            }
            
            const $btn = $(this);
            $btn.prop('disabled', true).text('Resetting...');
            
            $.ajax({
                url: aiRewriter.ajaxurl,
                method: 'POST',
                data: {
                    action: 'reset_processed_posts',
                    nonce: aiRewriter.nonce
                },
                success: function(response) {
                    if (response.success) {
                        AIRewriter.showNotification('Processing history reset successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        AIRewriter.showNotification('Failed to reset processing history', 'error');
                    }
                },
                error: function() {
                    AIRewriter.showNotification('Network error occurred', 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Reset History');
                }
            });
        },

        // Bulk operations
        bulkRewriteSelected: function(e) {
            e.preventDefault();
            
            const $selected = $('.article-checkbox:checked');
            
            if ($selected.length === 0) {
                AIRewriter.showNotification('Please select at least one article', 'warning');
                return;
            }
            
            if (!confirm(`Are you sure you want to rewrite ${$selected.length} selected article(s)?`)) {
                return;
            }
            
            const postIds = $selected.map(function() {
                return $(this).val();
            }).get();
            
            AIRewriter.processBulkRewrite(postIds);
        },

        processBulkRewrite: function(postIds) {
            const $btn = $('#bulk-rewrite-selected');
            $btn.prop('disabled', true).text(`Processing ${postIds.length} articles...`);
            
            let completed = 0;
            const total = postIds.length;
            
            // Process articles one by one to avoid overwhelming the server
            const processNext = () => {
                if (completed >= total) {
                    $btn.prop('disabled', false).text('Rewrite Selected');
                    AIRewriter.showNotification(`Bulk processing completed! ${completed}/${total} articles processed.`, 'success');
                    setTimeout(() => location.reload(), 2000);
                    return;
                }
                
                const postId = postIds[completed];
                const $card = $(`.ai-rewriter-article-card[data-post-id="${postId}"]`);
                const $cardBtn = $card.find('.rewrite-btn');
                
                $.ajax({
                    url: aiRewriter.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'rewrite_article',
                        post_id: postId,
                        nonce: aiRewriter.nonce
                    },
                    success: function(response) {
                        completed++;
                        $btn.text(`Processing ${completed}/${total}...`);
                        
                        if (response.success) {
                            AIRewriter.handleRewriteSuccess(response.data, $card, $cardBtn);
                        } else {
                            AIRewriter.handleRewriteError(response.data, $card, $cardBtn);
                        }
                        
                        // Process next after delay
                        setTimeout(processNext, 2000);
                    },
                    error: function(xhr, status, error) {
                        completed++;
                        $btn.text(`Processing ${completed}/${total}...`);
                        AIRewriter.handleRewriteError('Network error', $card, $cardBtn);
                        setTimeout(processNext, 2000);
                    }
                });
            };
            
            processNext();
        },

        toggleSelectAll: function() {
            const isChecked = $(this).is(':checked');
            $('.article-checkbox').prop('checked', isChecked);
            AIRewriter.updateBulkActionsUI();
        },

        updateBulkActionsUI: function() {
            const $selected = $('.article-checkbox:checked');
            const $bulkActions = $('.bulk-actions');
            
            if ($selected.length > 0) {
                $bulkActions.show();
                $('#bulk-rewrite-selected').text(`Rewrite Selected (${$selected.length})`);
            } else {
                $bulkActions.hide();
            }
        },

        // UI Components
        initProgressBars: function() {
            $('.ai-rewriter-progress-bar').each(function() {
                const $bar = $(this);
                const percentage = $bar.data('percentage') || 0;
                
                setTimeout(() => {
                    $bar.css('width', percentage + '%');
                }, 100);
            });
        },

        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $element = $(this);
                const tooltipText = $element.data('tooltip');
                
                $element.attr('title', tooltipText);
            });
        },

        initActivityLogRefresh: function() {
            // Auto-refresh activity log every 30 seconds
            if ($('#recent-activity').length > 0) {
                setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        AIRewriter.refreshActivity({ preventDefault: () => {} });
                    }
                }, 30000);
            }
        },

        initRealTimeStats: function() {
            // Update statistics periodically
            if ($('.ai-rewriter-stat-number').length > 0) {
                setInterval(AIRewriter.updateStats, 60000); // Every minute
            }
        },

        // Statistics and monitoring
        updateStats: function() {
            $.ajax({
                url: aiRewriter.ajaxurl,
                method: 'POST',
                data: {
                    action: 'get_plugin_stats',
                    nonce: aiRewriter.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        const stats = response.data;
                        
                        // Update stat numbers with animation
                        Object.keys(stats).forEach(key => {
                            const $stat = $(`.ai-rewriter-stat-number[data-stat="${key}"]`);
                            if ($stat.length > 0) {
                                AIRewriter.animateNumber($stat, parseInt($stat.text()) || 0, stats[key]);
                            }
                        });
                    }
                }
            });
        },

        animateNumber: function($element, from, to) {
            const duration = 1000;
            const steps = 20;
            const stepValue = (to - from) / steps;
            const stepTime = duration / steps;
            
            let current = from;
            
            const interval = setInterval(() => {
                current += stepValue;
                
                if ((stepValue > 0 && current >= to) || (stepValue < 0 && current <= to)) {
                    current = to;
                    clearInterval(interval);
                }
                
                $element.text(Math.round(current));
            }, stepTime);
        },

        // API status monitoring
        checkAPIStatus: function() {
            const $statusIndicator = $('#api-status-indicator');
            
            if ($statusIndicator.length === 0) return;
            
            $.ajax({
                url: window.location.origin + '/wp-json/ai-rewriter/v1/status',
                method: 'GET',
                timeout: 10000,
                success: function(response) {
                    if (response.success) {
                        $statusIndicator
                            .removeClass('status-error status-warning')
                            .addClass('status-success')
                            .html('<span style="color: #28a745;">✅ API Ready</span>');
                    } else {
                        $statusIndicator
                            .removeClass('status-success')
                            .addClass('status-warning')
                            .html('<span style="color: #ffc107;">⚠️ API Issues</span>');
                    }
                },
                error: function() {
                    $statusIndicator
                        .removeClass('status-success status-warning')
                        .addClass('status-error')
                        .html('<span style="color: #dc3545;">❌ API Offline</span>');
                }
            });
        },

        // Notifications system
        showNotification: function(message, type = 'info', duration = 5000) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            const $notification = $(`
                <div class="ai-rewriter-notification ai-rewriter-notification-${type} ai-rewriter-slide-in">
                    <span class="ai-rewriter-notification-icon">${icons[type] || icons.info}</span>
                    <span class="ai-rewriter-notification-message">${this.escapeHtml(message)}</span>
                    <button class="ai-rewriter-notification-dismiss">&times;</button>
                </div>
            `);
            
            // Add to notification container or create one
            let $container = $('#ai-rewriter-notifications');
            if ($container.length === 0) {
                $container = $('<div id="ai-rewriter-notifications" style="position: fixed; top: 32px; right: 20px; z-index: 9999; max-width: 400px;"></div>');
                $('body').append($container);
            }
            
            $container.prepend($notification);
            
            // Auto-dismiss after duration
            if (duration > 0) {
                setTimeout(() => {
                    $notification.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, duration);
            }
        },

        dismissNotification: function(e) {
            e.preventDefault();
            $(this).closest('.ai-rewriter-notification').fadeOut(300, function() {
                $(this).remove();
            });
        },

        // Utility functions
        escapeHtml: function(text) {
            if (typeof text !== 'string') return text;
            
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },

        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        formatBytes: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        formatDuration: function(seconds) {
            if (seconds < 60) {
                return seconds + 's';
            } else if (seconds < 3600) {
                return Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's';
            } else {
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                return hours + 'h ' + minutes + 'm';
            }
        },

        // Advanced features
        initKeyboardShortcuts: function() {
            $(document).keydown(function(e) {
                // Ctrl/Cmd + R: Refresh activity
                if ((e.ctrlKey || e.metaKey) && e.key === 'r' && e.shiftKey) {
                    e.preventDefault();
                    $('#refresh-activity').click();
                }
                
                // Ctrl/Cmd + S: Save settings (if on settings page)
                if ((e.ctrlKey || e.metaKey) && e.key === 's' && $('.ai-rewriter-settings-section').length > 0) {
                    e.preventDefault();
                    $('#submit').click();
                }
                
                // Escape: Dismiss notifications
                if (e.key === 'Escape') {
                    $('.ai-rewriter-notification').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        },

        initSearch: function() {
            const $searchInput = $('#article-search');
            
            if ($searchInput.length === 0) return;
            
            let searchTimeout;
            
            $searchInput.on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val().toLowerCase();
                
                searchTimeout = setTimeout(() => {
                    $('.ai-rewriter-article-card').each(function() {
                        const $card = $(this);
                        const title = $card.find('h3').text().toLowerCase();
                        const content = $card.text().toLowerCase();
                        
                        if (query === '' || title.includes(query) || content.includes(query)) {
                            $card.show().addClass('ai-rewriter-fade-in');
                        } else {
                            $card.hide();
                        }
                    });
                }, 300);
            });
        },

        initDragAndDrop: function() {
            // Enable drag and drop file upload for batch processing
            const $dropZone = $('#bulk-upload-zone');
            
            if ($dropZone.length === 0) return;
            
            $dropZone.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            $dropZone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            $dropZone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                AIRewriter.handleFileUpload(files);
            });
        },

        handleFileUpload: function(files) {
            // Process uploaded files (CSV, JSON, etc.)
            Array.from(files).forEach(file => {
                if (file.type === 'text/csv' || file.type === 'application/json') {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        try {
                            let data;
                            if (file.type === 'text/csv') {
                                data = AIRewriter.parseCSV(e.target.result);
                            } else {
                                data = JSON.parse(e.target.result);
                            }
                            
                            AIRewriter.processBatchData(data);
                        } catch (error) {
                            AIRewriter.showNotification('Error parsing file: ' + error.message, 'error');
                        }
                    };
                    
                    reader.readAsText(file);
                } else {
                    AIRewriter.showNotification('Unsupported file type. Please upload CSV or JSON files.', 'warning');
                }
            });
        },

        parseCSV: function(csvText) {
            const lines = csvText.split('\n');
            const headers = lines[0].split(',').map(h => h.trim());
            const data = [];
            
            for (let i = 1; i < lines.length; i++) {
                if (lines[i].trim() === '') continue;
                
                const values = lines[i].split(',').map(v => v.trim());
                const row = {};
                
                headers.forEach((header, index) => {
                    row[header] = values[index] || '';
                });
                
                data.push(row);
            }
            
            return data;
        },

        processBatchData: function(data) {
            // Process batch data for bulk operations
            AIRewriter.showNotification(`Processing ${data.length} items from uploaded file...`, 'info');
            
            // Implementation depends on data structure
            console.log('Batch data:', data);
        },

        // Export functionality
        exportData: function(type) {
            const data = {
                action: 'export_data',
                type: type,
                nonce: aiRewriter.nonce
            };
            
            // Create temporary form to trigger download
            const $form = $('<form>', {
                method: 'POST',
                action: aiRewriter.ajaxurl,
                style: 'display: none;'
            });
            
            Object.keys(data).forEach(key => {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: key,
                    value: data[key]
                }));
            });
            
            $('body').append($form);
            $form.submit();
            $form.remove();
            
            AIRewriter.showNotification('Export started. Download will begin shortly.', 'info');
        },

        // Error handling and recovery
        handleError: function(error, context = '') {
            console.error('AI Rewriter Error:', error, context);
            
            const errorMsg = error.message || error.toString();
            AIRewriter.showNotification(`Error${context ? ' in ' + context : ''}: ${errorMsg}`, 'error');
            
            // Send error report if debugging is enabled
            if (window.aiRewriterDebug) {
                $.ajax({
                    url: aiRewriter.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'log_js_error',
                        error: errorMsg,
                        context: context,
                        url: window.location.href,
                        userAgent: navigator.userAgent,
                        nonce: aiRewriter.nonce
                    }
                });
            }
        },

        // Performance monitoring
        measurePerformance: function(name, fn) {
            const start = performance.now();
            
            try {
                const result = fn();
                
                if (result && typeof result.then === 'function') {
                    // Handle promises
                    return result.finally(() => {
                        const end = performance.now();
                        console.log(`AI Rewriter Performance [${name}]: ${(end - start).toFixed(2)}ms`);
                    });
                } else {
                    const end = performance.now();
                    console.log(`AI Rewriter Performance [${name}]: ${(end - start).toFixed(2)}ms`);
                    return result;
                }
            } catch (error) {
                const end = performance.now();
                console.error(`AI Rewriter Performance [${name}] ERROR after ${(end - start).toFixed(2)}ms:`, error);
                throw error;
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        try {
            AIRewriter.init();
            AIRewriter.initKeyboardShortcuts();
            AIRewriter.initSearch();
            AIRewriter.initDragAndDrop();
            
            // Global error handler
            window.addEventListener('error', function(e) {
                AIRewriter.handleError(e.error, 'Global error handler');
            });
            
            // Unhandled promise rejection handler
            window.addEventListener('unhandledrejection', function(e) {
                AIRewriter.handleError(e.reason, 'Unhandled promise rejection');
            });
            
        } catch (error) {
            console.error('Failed to initialize AI Rewriter:', error);
        }
    });

    // Expose AIRewriter to global scope for debugging
    window.AIRewriter = AIRewriter;

    // Add event listeners for article checkboxes
    $(document).on('change', '.article-checkbox', function() {
        AIRewriter.updateBulkActionsUI();
    });

    // Real-time connection monitoring
    if (navigator.onLine !== undefined) {
        window.addEventListener('online', function() {
            AIRewriter.showNotification('Internet connection restored', 'success', 3000);
            AIRewriter.checkAPIStatus();
        });
        
        window.addEventListener('offline', function() {
            AIRewriter.showNotification('Internet connection lost', 'warning', 0);
        });
    }

})(jQuery);