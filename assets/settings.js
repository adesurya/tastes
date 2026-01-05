// AI Article Rewriter - Settings Page JavaScript (Enhanced with Dynamic Models)
jQuery(document).ready(function($) {
    
    var hasUnsavedChanges = false;
    var initialFormData = {};
    var availableModels = {};
    
    // Enhanced settings configuration
    var aiRewriterSettings = {
        nonce: window.aiRewriter ? window.aiRewriter.nonce : '',
        ajaxurl: window.ajaxurl || '/wp-admin/admin-ajax.php',
        unsaved_changes_warning: 'You have unsaved changes that will be lost if you leave this page.'
    };
    
    // Capture initial form state
    function captureInitialState() {
        var $form = $('#ai-rewriter-settings-form');
        if ($form.length) {
            initialFormData = getFormData($form);
        }
    }
    
    // Get current form data
    function getFormData($form) {
        var data = {};
        $form.find('input, select, textarea').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            
            if (name) {
                if ($field.attr('type') === 'checkbox') {
                    data[name] = $field.is(':checked');
                } else if ($field.attr('type') === 'radio') {
                    if ($field.is(':checked')) {
                        data[name] = $field.val();
                    }
                } else {
                    data[name] = $field.val();
                }
            }
        });
        return data;
    }
    
    // Compare form data to detect changes
    function hasFormChanged() {
        var $form = $('#ai-rewriter-settings-form');
        if (!$form.length) return false;
        
        var currentData = getFormData($form);
        
        // Compare each field
        for (var key in initialFormData) {
            if (initialFormData[key] !== currentData[key]) {
                return true;
            }
        }
        
        // Check for new fields
        for (var key in currentData) {
            if (!(key in initialFormData)) {
                return true;
            }
        }
        
        return false;
    }
    
    // Update unsaved changes flag
    function updateUnsavedChangesFlag() {
        hasUnsavedChanges = hasFormChanged();
        
        // Update UI to show unsaved changes
        if (hasUnsavedChanges) {
            $('input[type="submit"]').addClass('unsaved-changes');
            if (!$('.unsaved-indicator').length) {
                $('h1').append(' <span class="unsaved-indicator" style="color: #dc3545; font-size: 14px; font-weight: normal;">(Unsaved Changes)</span>');
            }
        } else {
            $('input[type="submit"]').removeClass('unsaved-changes');
            $('.unsaved-indicator').remove();
        }
    }
    
    // Monitor form changes
    function setupChangeMonitoring() {
        var $form = $('#ai-rewriter-settings-form');
        
        // Monitor all form inputs
        $form.on('input change', 'input, select, textarea', function() {
            setTimeout(updateUnsavedChangesFlag, 100);
        });
        
        // Special handling for checkboxes
        $form.on('change', 'input[type="checkbox"]', function() {
            setTimeout(updateUnsavedChangesFlag, 100);
        });
        
        // Monitor temperature slider specifically
        $form.on('input', '#temperature', function() {
            setTimeout(updateUnsavedChangesFlag, 100);
        });
    }
    
    // Setup beforeunload warning - ONLY when there are unsaved changes
    function setupBeforeUnloadWarning() {
        $(window).on('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                var message = aiRewriterSettings.unsaved_changes_warning;
                e.originalEvent.returnValue = message;
                return message;
            }
            return undefined;
        });
    }
    
    // Clear unsaved changes flag when form is submitted
    function setupFormSubmitHandler() {
        var $form = $('#ai-rewriter-settings-form');
        
        $form.on('submit', function() {
            hasUnsavedChanges = false;
            $('.unsaved-indicator').remove();
            $('input[type="submit"]').removeClass('unsaved-changes');
            initialFormData = getFormData($form);
        });
    }
    
    // Enhanced API key validation
    function setupAPIKeyValidation() {
        var $apiKeyField = $('#ai_rewriter_api_key');
        var $testBtn = $('#test-api-btn');
        var $loadModelsBtn = $('#load-models-btn');
        
        $apiKeyField.on('input', function() {
            var apiKey = $(this).val().trim();
            var $validation = $('#api-key-validation');
            
            // Remove existing validation
            $validation.remove();
            
            if (!apiKey) {
                $testBtn.prop('disabled', true);
                $loadModelsBtn.prop('disabled', true);
                return;
            }
            
            // Create validation element
            if (!$validation.length) {
                $(this).after('<div id="api-key-validation" style="margin-top: 5px; font-size: 12px;"></div>');
            }
            
            var $validationMsg = $('#api-key-validation');
            
            // Validate format
            if (!apiKey.startsWith('sk-')) {
                $testBtn.prop('disabled', true);
                $loadModelsBtn.prop('disabled', true);
                $validationMsg.html('<span style="color: #dc3545;">⚠️ API key should start with "sk-"</span>');
            } else if (apiKey.length < 20) {
                $testBtn.prop('disabled', true);
                $loadModelsBtn.prop('disabled', true);
                $validationMsg.html('<span style="color: #ffc107;">⚠️ API key seems too short</span>');
            } else if (apiKey.length < 40) {
                $testBtn.prop('disabled', true);
                $loadModelsBtn.prop('disabled', true);
                $validationMsg.html('<span style="color: #ffc107;">⚠️ API key might be incomplete</span>');
            } else {
                $testBtn.prop('disabled', false);
                $loadModelsBtn.prop('disabled', false);
                $validationMsg.html('<span style="color: #28a745;">✅ API key format looks correct</span>');
            }
        });
        
        // Auto-test on paste if key looks complete
        $apiKeyField.on('paste', function() {
            var $input = $(this);
            setTimeout(function() {
                var apiKey = $input.val().trim();
                if (apiKey.startsWith('sk-') && apiKey.length > 40) {
                    setTimeout(function() {
                        if (confirm('Would you like to test the API connection with the pasted key?')) {
                            $testBtn.click();
                        }
                    }, 500);
                }
            }, 100);
        });
    }
    
    // Load available models dynamically
    function loadAvailableModels() {
        var apiKey = $('#ai_rewriter_api_key').val().trim();
        var $loadBtn = $('#load-models-btn');
        var $modelSelect = $('#ai_rewriter_model');
        var $countInfo = $('#model-count-info');
        
        if (!apiKey) {
            showNotification('❌ Please enter your OpenAI API key first', 'error');
            $('#ai_rewriter_api_key').focus();
            return;
        }
        
        if (!apiKey.startsWith('sk-')) {
            showNotification('❌ Invalid API key format. OpenAI API keys start with "sk-"', 'error');
            $('#ai_rewriter_api_key').focus();
            return;
        }
        
        $loadBtn.prop('disabled', true).text('🔄 Loading Models...');
        
        $.ajax({
            url: aiRewriterSettings.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_available_models',
                api_key: apiKey,
                nonce: aiRewriterSettings.nonce
            },
            timeout: 30000,
            success: function(response) {
                if (response.success && response.data.models) {
                    var models = response.data.models;
                    var currentModel = $modelSelect.val();
                    
                    // Clear existing options
                    $modelSelect.empty();
                    
                    // Add new options with better organization
                    var modelCategories = organizeModels(models);
                    
                    $.each(modelCategories, function(category, categoryModels) {
                        if (Object.keys(categoryModels).length > 0) {
                            // Add category optgroup
                            var $optgroup = $('<optgroup label="' + getCategoryLabel(category) + '"></optgroup>');
                            
                            $.each(categoryModels, function(value, label) {
                                var selected = value === currentModel ? 'selected' : '';
                                $optgroup.append('<option value="' + value + '" ' + selected + '>' + label + '</option>');
                            });
                            
                            $modelSelect.append($optgroup);
                        }
                    });
                    
                    // Store models for later use
                    availableModels = models;
                    
                    // Update count info
                    $countInfo.text('✅ ' + Object.keys(models).length + ' models loaded from OpenAI API').css('color', '#28a745');
                    
                    // Show success message
                    showNotification('🎉 Successfully loaded ' + Object.keys(models).length + ' AI models from OpenAI!', 'success');
                    
                    // Update model info
                    updateModelInfo();
                    
                    // Show model comparison
                    showModelComparison(models);
                    
                } else {
                    showNotification('❌ Failed to load models: ' + (response.data || 'Unknown error'), 'error');
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Failed to load models';
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out - OpenAI API might be slow';
                } else if (xhr.status === 401) {
                    errorMessage = 'Invalid API key - please check your OpenAI API key';
                } else if (xhr.status === 429) {
                    errorMessage = 'Rate limit exceeded - please try again later';
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error - check your internet connection';
                } else {
                    errorMessage = 'HTTP ' + xhr.status + ': ' + error;
                }
                
                showNotification('❌ ' + errorMessage, 'error');
                $countInfo.text('❌ Failed to load models').css('color', '#dc3545');
            },
            complete: function() {
                $loadBtn.prop('disabled', false).text('🔄 Load Models');
            }
        });
    }
    
    // Organize models into categories
    function organizeModels(models) {
        var categories = {
            'gpt-4o': {},
            'gpt-4': {},
            'gpt-3.5': {},
            'other': {}
        };
        
        $.each(models, function(model, name) {
            if (model.indexOf('gpt-4o') !== -1) {
                categories['gpt-4o'][model] = name;
            } else if (model.indexOf('gpt-4') !== -1) {
                categories['gpt-4'][model] = name;
            } else if (model.indexOf('gpt-3.5') !== -1) {
                categories['gpt-3.5'][model] = name;
            } else {
                categories['other'][model] = name;
            }
        });
        
        return categories;
    }
    
    // Get category label
    function getCategoryLabel(category) {
        var labels = {
            'gpt-4o': '🚀 GPT-4o Series (Latest & Fastest)',
            'gpt-4': '🎯 GPT-4 Series (High Quality)',
            'gpt-3.5': '💰 GPT-3.5 Series (Cost Effective)',
            'other': '🔧 Other Models'
        };
        
        return labels[category] || category;
    }
    
    // Update model information display
    function updateModelInfo() {
        var selectedModel = $('#ai_rewriter_model').val();
        var $modelInfo = $('#model-info');
        var $description = $('#model-description');
        var $pricing = $('#model-pricing');
        
        if (!selectedModel) {
            $modelInfo.hide();
            return;
        }
        
        // Enhanced model information database
        var modelInfo = getModelInfo(selectedModel);
        
        $description.html('<strong>📝 Description:</strong> ' + modelInfo.description);
        $pricing.html('<strong>💰 Pricing:</strong> ' + modelInfo.pricing);
        
        // Add performance indicators
        var performanceHtml = '<div style="margin-top: 8px;"><strong>⚡ Performance:</strong> ' + modelInfo.performance + '</div>';
        $pricing.after(performanceHtml);
        
        // Color code by category
        var categoryColors = {
            'premium': '#ff9800',
            'efficient': '#4caf50',
            'standard': '#2196f3',
            'other': '#666'
        };
        
        $modelInfo.css('border-left', '4px solid ' + (categoryColors[modelInfo.category] || '#666')).show();
    }
    
    // Get detailed model information
    function getModelInfo(model) {
        var modelDatabase = {
            // GPT-4o series
            'gpt-4o': {
                description: 'Latest multimodal model with enhanced capabilities and speed',
                pricing: 'Input: $0.005/1K tokens, Output: $0.015/1K tokens',
                performance: 'Fastest, most capable',
                category: 'premium'
            },
            'gpt-4o-mini': {
                description: 'Efficient and fast model for most rewriting tasks',
                pricing: 'Input: $0.00015/1K tokens, Output: $0.0006/1K tokens',
                performance: 'Very fast, cost-effective',
                category: 'efficient'
            },
            'gpt-4o-2024-08-06': {
                description: 'August 2024 version of GPT-4o with improvements',
                pricing: 'Input: $0.0025/1K tokens, Output: $0.01/1K tokens',
                performance: 'Fast, enhanced capabilities',
                category: 'premium'
            },
            
            // GPT-4 series
            'gpt-4-turbo': {
                description: 'Enhanced GPT-4 with improved performance and efficiency',
                pricing: 'Input: $0.01/1K tokens, Output: $0.03/1K tokens',
                performance: 'High quality, good speed',
                category: 'premium'
            },
            'gpt-4': {
                description: 'High-quality model for complex rewriting tasks',
                pricing: 'Input: $0.03/1K tokens, Output: $0.06/1K tokens',
                performance: 'Highest quality, slower',
                category: 'premium'
            },
            'gpt-4-turbo-preview': {
                description: 'Preview version of GPT-4 Turbo',
                pricing: 'Input: $0.01/1K tokens, Output: $0.03/1K tokens',
                performance: 'High quality, preview features',
                category: 'premium'
            },
            
            // GPT-3.5 series
            'gpt-3.5-turbo': {
                description: 'Fast and cost-effective for most rewriting needs',
                pricing: 'Input: $0.0015/1K tokens, Output: $0.002/1K tokens',
                performance: 'Good quality, very fast',
                category: 'standard'
            },
            'gpt-3.5-turbo-1106': {
                description: 'November 2023 version of GPT-3.5 Turbo',
                pricing: 'Input: $0.001/1K tokens, Output: $0.002/1K tokens',
                performance: 'Good quality, fast',
                category: 'standard'
            },
            'gpt-3.5-turbo-0125': {
                description: 'January 2024 version with reduced prices',
                pricing: 'Input: $0.0005/1K tokens, Output: $0.0015/1K tokens',
                performance: 'Good quality, most economical',
                category: 'standard'
            }
        };
        
        return modelDatabase[model] || {
            description: 'Advanced language model for content rewriting',
            pricing: 'Pricing varies by model - check OpenAI documentation',
            performance: 'Performance varies',
            category: 'other'
        };
    }
    
    // Show model comparison table
    function showModelComparison(models) {
        var $modelSelect = $('#ai_rewriter_model');
        var $existingComparison = $('#model-comparison');
        
        // Remove existing comparison
        $existingComparison.remove();
        
        if (Object.keys(models).length === 0) return;
        
        var tableRows = '';
        $.each(models, function(model, name) {
            var info = getModelInfo(model);
            var costs = extractCosts(info.pricing);
            var categoryIcon = getCategoryIcon(info.category);
            
            tableRows += '<tr style="border-bottom: 1px solid #f1f1f1;">' +
                '<td style="padding: 8px; font-weight: 500;">' + categoryIcon + ' ' + model + '</td>' +
                '<td style="padding: 8px; color: #666;">' + info.category + '</td>' +
                '<td style="padding: 8px; text-align: right; color: #28a745;">' + costs.input + '</td>' +
                '<td style="padding: 8px; text-align: right; color: #dc3545;">' + costs.output + '</td>' +
                '</tr>';
        });
        
        var comparisonHtml = '<div id="model-comparison" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #2196f3;">' +
            '<h4 style="margin-top: 0; margin-bottom: 12px;">🤖 Available Models (' + Object.keys(models).length + ')</h4>' +
            '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">' +
            '<table style="width: 100%; font-size: 12px; border-collapse: collapse;">' +
            '<thead style="background: #e9ecef; position: sticky; top: 0;">' +
            '<tr>' +
            '<th style="padding: 8px; text-align: left; border-bottom: 1px solid #dee2e6;">Model</th>' +
            '<th style="padding: 8px; text-align: left; border-bottom: 1px solid #dee2e6;">Category</th>' +
            '<th style="padding: 8px; text-align: right; border-bottom: 1px solid #dee2e6;">Input Cost</th>' +
            '<th style="padding: 8px; text-align: right; border-bottom: 1px solid #dee2e6;">Output Cost</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' + tableRows + '</tbody>' +
            '</table>' +
            '</div>' +
            '<div style="margin-top: 10px; font-size: 11px; color: #666;">' +
            '💡 Costs are per 1,000 tokens. Lower input costs save money on article processing.' +
            '</div>' +
            '</div>';
        
        $modelSelect.closest('td').append(comparisonHtml);
    }
    
    // Extract costs from pricing string
    function extractCosts(pricing) {
        var inputMatch = pricing.match(/Input: \$([0-9.]+)/);
        var outputMatch = pricing.match(/Output: \$([0-9.]+)/);
        
        return {
            input: inputMatch ? '$' + inputMatch[1] : 'N/A',
            output: outputMatch ? '$' + outputMatch[1] : 'N/A'
        };
    }
    
    // Get category icon
    function getCategoryIcon(category) {
        var icons = {
            'premium': '🚀',
            'efficient': '⚡',
            'standard': '💰',
            'other': '🔧'
        };
        
        return icons[category] || '📝';
    }
    
    // Enhanced temperature slider
    function setupTemperatureSlider() {
        var $slider = $('#temperature');
        var $value = $('.temperature-value');
        var $description = $('.temperature-description');
        
        $slider.on('input', function() {
            var value = parseFloat($(this).val());
            $value.text(value);
            
            // Dynamic descriptions with recommendations
            var description = '';
            var recommendation = '';
            
            if (value <= 0.3) {
                description = '🎯 Very focused and consistent output';
                recommendation = 'Best for factual content and technical writing';
            } else if (value <= 0.5) {
                description = '📝 Balanced creativity and consistency';
                recommendation = 'Good for most rewriting tasks';
            } else if (value <= 0.7) {
                description = '🎨 Creative and varied output';
                recommendation = 'Ideal for engaging blog posts and articles';
            } else if (value <= 1.0) {
                description = '🌟 Highly creative and diverse';
                recommendation = 'Great for creative writing and marketing copy';
            } else {
                description = '🔥 Maximum creativity (may be unpredictable)';
                recommendation = 'Use with caution - may produce inconsistent results';
            }
            
            $description.html(description + '<br><small style="color: #666; font-style: normal;">' + recommendation + '</small>');
        });
    }
    
    // Enhanced conditional fields
    function setupConditionalFields() {
        // Image settings
        var $autoImages = $('input[name="ai_rewriter_auto_replace_images"]');
        var $imageFields = $('.image-settings-group');
        
        function toggleImageSettings() {
            if ($autoImages.is(':checked')) {
                $imageFields.slideDown(300);
            } else {
                $imageFields.slideUp(300);
            }
        }
        
        $autoImages.on('change', toggleImageSettings);
        toggleImageSettings(); // Initial state
        
        // Custom prompt settings
        var $useCustomPrompt = $('input[name="ai_rewriter_use_custom_prompt"]');
        var $promptFields = $('.custom-prompt-group');
        
        function togglePromptSettings() {
            if ($useCustomPrompt.is(':checked')) {
                $promptFields.slideDown(300);
            } else {
                $promptFields.slideUp(300);
            }
        }
        
        $useCustomPrompt.on('change', togglePromptSettings);
        togglePromptSettings(); // Initial state
        
        // Image source fields
        var $imageSource = $('select[name="ai_rewriter_image_source"]');
        var $googleFields = $('.google-api-fields');
        var $pexelsFields = $('.pexels-api-fields');
        
        function toggleImageSourceFields() {
            var source = $imageSource.val();
            
            if (source === 'google') {
                $googleFields.slideDown(300);
                $pexelsFields.slideUp(300);
            } else if (source === 'pexels') {
                $pexelsFields.slideDown(300);
                $googleFields.slideUp(300);
            } else {
                $googleFields.slideUp(300);
                $pexelsFields.slideUp(300);
            }
        }
        
        $imageSource.on('change', toggleImageSourceFields);
        toggleImageSourceFields(); // Initial state
    }
    
    // Enhanced API testing
    function setupEnhancedAPITesting() {
        $('#test-api-btn').on('click', function() {
            var $button = $(this);
            var $result = $('#api-test-result');
            var originalText = $button.text();
            var apiKey = $('#ai_rewriter_api_key').val().trim();
            
            // Clear previous results
            $result.html('');
            $('.api-test-notification').remove();
            
            // Validate API key locally first
            if (!apiKey) {
                $result.html('<span style="color: red;">❌ Please enter an API key first</span>');
                return;
            }
            
            if (apiKey.indexOf('sk-') !== 0) {
                $result.html('<span style="color: red;">❌ Invalid API key format (should start with "sk-")</span>');
                return;
            }
            
            if (apiKey.length < 20) {
                $result.html('<span style="color: red;">❌ API key seems too short</span>');
                return;
            }
            
            // Show testing state
            $button.prop('disabled', true)
                   .addClass('loading')
                   .text('🔍 Testing...');
            $result.html('<span style="color: blue;">⏳ Connecting to OpenAI API...</span>');
            
            var testStart = Date.now();
            
            // Send AJAX request
            $.ajax({
                url: aiRewriterSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_api_connection',
                    api_key: apiKey,
                    nonce: aiRewriterSettings.nonce
                },
                timeout: 30000,
                success: function(response) {
                    var duration = ((Date.now() - testStart) / 1000).toFixed(1);
                    
                    if (response.success) {
                        $result.html('<span style="color: green;">✅ ' + response.data + ' (' + duration + 's)</span>');
                        showNotification('🎉 API connection successful! Response time: ' + duration + 's', 'success');
                        
                        // Auto-suggest loading models
                        setTimeout(function() {
                            if (confirm('🔄 API connection successful! Would you like to load available models now?')) {
                                loadAvailableModels();
                            }
                        }, 1000);
                        
                    } else {
                        $result.html('<span style="color: red;">❌ ' + response.data + '</span>');
                        showNotification('❌ API test failed: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    var duration = ((Date.now() - testStart) / 1000).toFixed(1);
                    var errorMessage = 'Connection failed';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out - OpenAI API might be slow';
                    } else if (xhr.status === 401) {
                        errorMessage = 'Invalid API key - check your OpenAI account';
                    } else if (xhr.status === 429) {
                        errorMessage = 'Rate limit exceeded - try again later';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Network error - check internet connection';
                    } else {
                        errorMessage = 'HTTP ' + xhr.status + ': ' + error;
                    }
                    
                    $result.html('<span style="color: red;">❌ ' + errorMessage + ' (' + duration + 's)</span>');
                    showNotification('❌ API test failed: ' + errorMessage, 'error');
                },
                complete: function() {
                    // Reset button state
                    $button.prop('disabled', false)
                           .removeClass('loading')
                           .text(originalText);
                }
            });
        });
    }
    
    // Show notifications
    function showNotification(message, type) {
        type = type || 'info';
        
        // Remove existing notifications
        $('.settings-notification').remove();
        
        var icons = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        
        var icon = icons[type] || 'ℹ️';
        
        // Create notification
        var $notification = $('<div class="settings-notification notice notice-' + type + ' is-dismissible" style="margin: 15px 0; opacity: 0;">' +
            '<p>' + icon + ' ' + message + '</p>' +
            '<button type="button" class="notice-dismiss">' +
            '<span class="screen-reader-text">Dismiss this notice.</span>' +
            '</button>' +
            '</div>');
        
        // Insert after the main heading
        $('.wrap h1').after($notification);
        
        // Animate in
        $notification.animate({opacity: 1}, 300);
        
        // Auto-remove after 5 seconds for success/info
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $notification.animate({opacity: 0}, 300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Manual dismiss
        $notification.on('click', '.notice-dismiss', function() {
            $notification.animate({opacity: 0}, 300, function() {
                $(this).remove();
            });
        });
    }
    
    // Reset functionality
    function setupResetFunctions() {
        // Reset processed posts
        $('#reset-processed-btn').on('click', function() {
            if (confirm('⚠️ Are you sure you want to reset all processing history?\n\nThis will allow reprocessing of previously processed articles.')) {
                var $button = $(this);
                $button.prop('disabled', true).text('🔄 Resetting...');
                
                $.ajax({
                    url: aiRewriterSettings.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'reset_processed_posts',
                        nonce: aiRewriterSettings.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('🔄 Processing history has been reset successfully!', 'success');
                            location.reload();
                        } else {
                            showNotification('❌ Failed to reset: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        showNotification('❌ Network error while resetting history', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('🔄 Reset Processing History');
                    }
                });
            }
        });
        
        // Clear logs
        $('#clear-logs-btn').on('click', function() {
            if (confirm('🗑️ Are you sure you want to clear all activity logs?\n\nThis action cannot be undone.')) {
                var $button = $(this);
                $button.prop('disabled', true).text('🗑️ Clearing...');
                
                $.ajax({
                    url: aiRewriterSettings.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clear_activity_logs',
                        nonce: aiRewriterSettings.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification('🗑️ Activity logs cleared successfully!', 'success');
                            location.reload();
                        } else {
                            showNotification('❌ Failed to clear logs: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        showNotification('❌ Network error while clearing logs', 'error');
                    },
                    complete: function() {
                        $button.prop('disabled', false).text('🗑️ Clear Activity Logs');
                    }
                });
            }
        });
    }
    
    // Enhanced form validation
    function setupFormValidation() {
        var $form = $('#ai-rewriter-settings-form');
        
        $form.on('submit', function(e) {
            var errors = [];
            
            // Validate API key
            var apiKey = $('#ai_rewriter_api_key').val().trim();
            if (!apiKey) {
                errors.push('OpenAI API key is required');
            } else if (apiKey.indexOf('sk-') !== 0) {
                errors.push('OpenAI API key should start with "sk-"');
            }
            
            // Validate Google API settings if image replacement is enabled
            var autoImages = $('input[name="ai_rewriter_auto_replace_images"]').is(':checked');
            var imageSource = $('select[name="ai_rewriter_image_source"]').val();
            
            if (autoImages) {
                if (imageSource === 'google') {
                    var googleApiKey = $('#ai_rewriter_google_api_key').val().trim();
                    var searchEngineId = $('#ai_rewriter_google_search_engine_id').val().trim();
                    
                    if (!googleApiKey) {
                        errors.push('Google API key is required when using Google Image Search');
                    }
                    if (!searchEngineId) {
                        errors.push('Google Search Engine ID is required when using Google Image Search');
                    }
                } else if (imageSource === 'pexels') {
                    var pexelsApiKey = $('#ai_rewriter_pexels_api_key').val().trim();
                    if (!pexelsApiKey) {
                        errors.push('Pexels API key is required when using Pexels');
                    }
                }
            }
            
            // Validate temperature range
            var temperature = parseFloat($('#temperature').val());
            if (temperature < 0 || temperature > 2) {
                errors.push('Temperature must be between 0 and 2');
            }
            
            // Validate max tokens
            var maxTokens = parseInt($('#ai_rewriter_max_tokens').val());
            if (maxTokens < 100 || maxTokens > 8000) {
                errors.push('Max tokens must be between 100 and 8000');
            }
            
            // Validate max images
            var maxImages = parseInt($('#ai_rewriter_max_images').val());
            if (autoImages && (maxImages < 1 || maxImages > 10)) {
                errors.push('Max images must be between 1 and 10');
            }
            
            // Show errors if any
            if (errors.length > 0) {
                e.preventDefault();
                
                var errorMessage = 'Please fix the following errors:\n\n';
                for (var i = 0; i < errors.length; i++) {
                    errorMessage += (i + 1) + '. ' + errors[i] + '\n';
                }
                
                alert(errorMessage);
                return false;
            }
            
            // Show saving indicator
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.val('💾 Saving...').prop('disabled', true);
        });
    }
    
    // Model selection change handler
    function setupModelChangeHandler() {
        $('#ai_rewriter_model').on('change', function() {
            updateModelInfo();
            
            // Show cost warning for expensive models
            var selectedModel = $(this).val();
            var info = getModelInfo(selectedModel);
            
            if (info.category === 'premium' && selectedModel.indexOf('gpt-4') !== -1 && selectedModel.indexOf('gpt-4o') === -1) {
                showNotification('💰 Note: GPT-4 models are more expensive. Consider GPT-4o or GPT-4o-mini for better cost efficiency.', 'warning');
            }
            
            updateUnsavedChangesFlag();
        });
    }
    
    // Keyboard shortcuts
    function setupKeyboardShortcuts() {
        $(document).on('keydown', function(e) {
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                $('form').submit();
            }
            
            // Ctrl+T to test API
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                $('#test-api-btn').click();
            }
            
            // Ctrl+L to load models
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                $('#load-models-btn').click();
            }
            
            // Escape to clear notifications
            if (e.key === 'Escape') {
                $('.settings-notification').fadeOut();
            }
        });
        
        // Show keyboard shortcuts info
        var $shortcutsInfo = $('<div id="keyboard-shortcuts" style="position: fixed; bottom: 20px; right: 20px; background: #2c3338; color: #f0f0f1; padding: 10px; border-radius: 6px; font-size: 11px; z-index: 1000; display: none;">' +
            '⌨️ Shortcuts: Ctrl+S (Save) | Ctrl+T (Test API) | Ctrl+L (Load Models) | Esc (Clear notifications)' +
            '</div>');
        
        $('body').append($shortcutsInfo);
        
        // Show/hide shortcuts on Ctrl key
        $(document).on('keydown', function(e) {
            if (e.ctrlKey && !$('#keyboard-shortcuts').is(':visible')) {
                $('#keyboard-shortcuts').fadeIn();
            }
        }).on('keyup', function(e) {
            if (!e.ctrlKey) {
                $('#keyboard-shortcuts').fadeOut();
            }
        });
    }
    
    // Initialize everything
    function initializeSettingsPage() {
        // Only run on settings page
        if (!$('#ai-rewriter-settings-form').length) {
            return;
        }
        
        console.log('🚀 Initializing AI Rewriter Settings page with dynamic model loading...');
        
        // Core functionality
        captureInitialState();
        setupChangeMonitoring();
        setupBeforeUnloadWarning();
        setupFormSubmitHandler();
        
        // Enhanced features
        setupAPIKeyValidation();
        setupTemperatureSlider();
        setupConditionalFields();
        setupFormValidation();
        setupEnhancedAPITesting();
        setupResetFunctions();
        setupModelChangeHandler();
        setupKeyboardShortcuts();
        
        // Event handlers for new features
        $('#load-models-btn').on('click', loadAvailableModels);
        
        // Trigger initial states
        $('#temperature').trigger('input');
        $('input[name="ai_rewriter_auto_replace_images"]').trigger('change');
        $('input[name="ai_rewriter_use_custom_prompt"]').trigger('change');
        $('#ai_rewriter_image_source').trigger('change');
        $('#ai_rewriter_api_key').trigger('input');
        
        updateModelInfo();
        updateUnsavedChangesFlag();
        
        console.log('✅ AI Rewriter Settings page initialized successfully');
    }
    
    // Add enhanced CSS styles
    function addEnhancedStyles() {
        var css = [
            '.unsaved-changes {',
            '    background-color: #ffc107 !important;',
            '    border-color: #ffc107 !important;',
            '    color: #212529 !important;',
            '    animation: pulse 2s infinite;',
            '}',
            '',
            '.unsaved-indicator {',
            '    animation: pulse 2s infinite;',
            '}',
            '',
            '@keyframes pulse {',
            '    0% { opacity: 1; }',
            '    50% { opacity: 0.5; }',
            '    100% { opacity: 1; }',
            '}',
            '',
            '.settings-notification {',
            '    border-left: 4px solid;',
            '    animation: slideInDown 0.3s ease;',
            '}',
            '',
            '.settings-notification.notice-success {',
            '    border-left-color: #28a745;',
            '}',
            '',
            '.settings-notification.notice-error {',
            '    border-left-color: #dc3545;',
            '}',
            '',
            '.settings-notification.notice-warning {',
            '    border-left-color: #ffc107;',
            '}',
            '',
            '.settings-notification.notice-info {',
            '    border-left-color: #17a2b8;',
            '}',
            '',
            '@keyframes slideInDown {',
            '    from {',
            '        transform: translateY(-20px);',
            '        opacity: 0;',
            '    }',
            '    to {',
            '        transform: translateY(0);',
            '        opacity: 1;',
            '    }',
            '}',
            '',
            '.button.loading {',
            '    position: relative;',
            '    opacity: 0.7;',
            '}',
            '',
            '.button.loading::after {',
            '    content: "";',
            '    position: absolute;',
            '    top: 50%;',
            '    left: 50%;',
            '    width: 16px;',
            '    height: 16px;',
            '    margin: -8px 0 0 -8px;',
            '    border: 2px solid #ffffff;',
            '    border-top: 2px solid transparent;',
            '    border-radius: 50%;',
            '    animation: spin 1s linear infinite;',
            '}',
            '',
            '@keyframes spin {',
            '    0% { transform: rotate(0deg); }',
            '    100% { transform: rotate(360deg); }',
            '}',
            '',
            '#model-comparison table tr:hover {',
            '    background-color: rgba(0, 123, 255, 0.1);',
            '}',
            '',
            '#keyboard-shortcuts {',
            '    box-shadow: 0 4px 12px rgba(0,0,0,0.3);',
            '}',
            '',
            '#model-info {',
            '    transition: all 0.3s ease;',
            '}',
            '',
            '.image-settings-group, ',
            '.custom-prompt-group, ',
            '.google-api-fields, ',
            '.pexels-api-fields {',
            '    transition: all 0.3s ease;',
            '}',
            '',
            '/* Responsive design */',
            '@media screen and (max-width: 782px) {',
            '    #model-comparison {',
            '        overflow-x: auto;',
            '    }',
            '    ',
            '    #model-comparison table {',
            '        min-width: 500px;',
            '    }',
            '    ',
            '    #keyboard-shortcuts {',
            '        bottom: 10px;',
            '        right: 10px;',
            '        font-size: 10px;',
            '        padding: 8px;',
            '    }',
            '}',
            '',
            '/* Dark mode support */',
            '@media (prefers-color-scheme: dark) {',
            '    .settings-notification {',
            '        background: #2c3338 !important;',
            '        color: #f0f0f1 !important;',
            '        border-color: #646970 !important;',
            '    }',
            '    ',
            '    #model-comparison {',
            '        background: #2c3338 !important;',
            '        color: #f0f0f1 !important;',
            '    }',
            '    ',
            '    #model-comparison table {',
            '        background: #23282d !important;',
            '    }',
            '    ',
            '    #model-comparison thead {',
            '        background: #1d2327 !important;',
            '    }',
            '    ',
            '    #keyboard-shortcuts {',
            '        background: #23282d !important;',
            '    }',
            '}',
            '',
            '/* Reduced motion support */',
            '@media (prefers-reduced-motion: reduce) {',
            '    .settings-notification,',
            '    .unsaved-changes,',
            '    .unsaved-indicator,',
            '    .button.loading,',
            '    #model-info,',
            '    .image-settings-group,',
            '    .custom-prompt-group,',
            '    .google-api-fields,',
            '    .pexels-api-fields {',
            '        animation: none !important;',
            '        transition: none !important;',
            '    }',
            '}'
        ].join('\n');
        
        $('<style type="text/css">' + css + '</style>').appendTo('head');
    }
    
    // Initialize when DOM is ready
    addEnhancedStyles();
    initializeSettingsPage();
    
});