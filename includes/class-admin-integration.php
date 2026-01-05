<!DOCTYPE html>
<html>
<head>
    <title>AI Rewriter Cron Management Interface</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f1f1f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
        .section h3 { margin-top: 0; padding: 10px; background: #f7f7f7; margin: -20px -20px 20px -20px; border-radius: 4px 4px 0 0; }
        .button { background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .button:hover { background: #005a87; }
        .button-secondary { background: #666; }
        .button-danger { background: #dc3545; }
        .button-success { background: #28a745; }
        .status-box { padding: 15px; border-radius: 4px; margin: 10px 0; }
        .status-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-info { background: #e3f2fd; color: #0c5460; border: 1px solid #bee5eb; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .log-output { background: #1e1e1e; color: #ffffff; padding: 15px; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .progress { width: 100%; height: 20px; background: #f0f0f0; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-bar { height: 100%; background: #0073aa; transition: width 0.3s ease; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 AI Rewriter Cron Management</h1>
        
        <!-- Quick Status Overview -->
        <div class="section">
            <h3>📊 System Status</h3>
            <div class="grid">
                <div id="status-overview">
                    <p>Loading system status...</p>
                </div>
                <div id="cron-status">
                    <p>Loading cron status...</p>
                </div>
                <div id="dependency-status">
                    <p>Loading dependency status...</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="section">
            <h3>⚡ Quick Actions</h3>
            <div class="grid">
                <div>
                    <h4>Immediate Actions</h4>
                    <button class="button button-success" onclick="executeManualCron()">🚀 Run Now</button>
                    <button class="button" onclick="checkDependencies()">🔍 Check Dependencies</button>
                    <button class="button" onclick="forceLoadClasses()">📥 Force Load Classes</button>
                    <button class="button button-secondary" onclick="refreshAllStatus()">🔄 Refresh Status</button>
                </div>
                <div>
                    <h4>Cron Management</h4>
                    <button class="button" onclick="rescheduleNow()">⏰ Reschedule Now</button>
                    <button class="button" onclick="clearSchedule()">🗑️ Clear Schedule</button>
                    <button class="button button-danger" onclick="resetAllProcessed()">🔄 Reset Processed</button>
                </div>
            </div>
            <div id="action-result" class="status-box" style="display: none;"></div>
        </div>
        
        <!-- Custom Scheduling -->
        <div class="section">
            <h3>📅 Custom Scheduling</h3>
            <div class="grid">
                <div>
                    <div class="form-group">
                        <label>Schedule Type:</label>
                        <select id="schedule-type" onchange="toggleScheduleOptions()">
                            <option value="now">Run in 30 seconds</option>
                            <option value="minutes">Run in X minutes</option>
                            <option value="specific">Run at specific time</option>
                            <option value="recurring">Recurring schedule</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="minutes-option" style="display: none;">
                        <label>Minutes from now:</label>
                        <input type="number" id="minutes-input" value="5" min="1" max="1440">
                    </div>
                    
                    <div class="form-group" id="specific-option" style="display: none;">
                        <label>Specific time:</label>
                        <input type="datetime-local" id="specific-time">
                    </div>
                    
                    <div class="form-group" id="recurring-option" style="display: none;">
                        <label>Interval:</label>
                        <select id="recurring-interval">
                            <option value="5">5 minutes</option>
                            <option value="15" selected>15 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="60">1 hour</option>
                        </select>
                    </div>
                    
                    <button class="button button-success" onclick="customSchedule()">📅 Schedule</button>
                </div>
                <div id="schedule-result">
                    <p>Select schedule type and click Schedule to set custom timing.</p>
                </div>
            </div>
        </div>
        
        <!-- Dependency Management -->
        <div class="section">
            <h3>🔧 Dependency Management</h3>
            <div class="grid">
                <div>
                    <h4>Class Status</h4>
                    <div id="class-status">
                        <p>Loading class status...</p>
                    </div>
                    <button class="button" onclick="checkClassStatus()">🔍 Check Classes</button>
                    <button class="button" onclick="forceLoadClasses()">🔄 Force Load</button>
                </div>
                <div>
                    <h4>File Paths</h4>
                    <div id="path-status">
                        <p>Loading path status...</p>
                    </div>
                    <button class="button" onclick="checkPaths()">📁 Check Paths</button>
                </div>
            </div>
        </div>
        
        <!-- Processing Status -->
        <div class="section">
            <h3>📝 Processing Status</h3>
            <div class="grid">
                <div>
                    <h4>Draft Posts</h4>
                    <div id="posts-status">
                        <p>Loading posts status...</p>
                    </div>
                    <button class="button" onclick="getPostsStatus()">📊 Check Posts</button>
                </div>
                <div>
                    <h4>Processing Stats</h4>
                    <div id="processing-stats">
                        <p>Loading processing statistics...</p>
                    </div>
                    <button class="button" onclick="getProcessingStats()">📈 Get Stats</button>
                </div>
            </div>
        </div>
        
        <!-- Real-time Logs -->
        <div class="section">
            <h3>📋 Real-time Logs</h3>
            <div>
                <button class="button" onclick="loadLogs()">🔄 Refresh Logs</button>
                <button class="button button-secondary" onclick="clearLogs()">🗑️ Clear Logs</button>
                <button class="button" onclick="toggleAutoRefresh()">⏰ <span id="auto-refresh-status">Enable</span> Auto-refresh</button>
            </div>
            <div id="logs-container" class="log-output">
                <p>Click "Refresh Logs" to load recent activity...</p>
            </div>
        </div>
        
        <!-- Advanced Tools -->
        <div class="section">
            <h3>🛠️ Advanced Tools</h3>
            <div class="grid">
                <div>
                    <h4>Manual Processing</h4>
                    <p>Process specific posts manually:</p>
                    <div class="form-group">
                        <label>Post ID (optional):</label>
                        <input type="number" id="manual-post-id" placeholder="Leave empty for auto-select">
                    </div>
                    <button class="button" onclick="manualProcess()">🔧 Manual Process</button>
                </div>
                <div>
                    <h4>Batch Operations</h4>
                    <p>Bulk operations on processed posts:</p>
                    <button class="button button-secondary" onclick="resetProcessedFlags()">🔄 Reset Flags</button>
                    <button class="button button-secondary" onclick="cleanupErrors()">🧹 Cleanup Errors</button>
                    <button class="button button-danger" onclick="emergencyStop()">🛑 Emergency Stop</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let autoRefreshEnabled = false;
        let autoRefreshInterval = null;
        
        // Set default datetime to 1 hour from now
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            now.setHours(now.getHours() + 1);
            document.getElementById('specific-time').value = now.toISOString().slice(0, 16);
            
            // Initial status load
            refreshAllStatus();
        });
        
        // Toggle schedule options based on type
        function toggleScheduleOptions() {
            const type = document.getElementById('schedule-type').value;
            
            document.getElementById('minutes-option').style.display = type === 'minutes' ? 'block' : 'none';
            document.getElementById('specific-option').style.display = type === 'specific' ? 'block' : 'none';
            document.getElementById('recurring-option').style.display = type === 'recurring' ? 'block' : 'none';
        }
        
        // Show status message
        function showStatus(message, type = 'info') {
            const resultDiv = document.getElementById('action-result');
            resultDiv.className = `status-box status-${type}`;
            resultDiv.innerHTML = `<strong>${new Date().toLocaleTimeString()}:</strong> ${message}`;
            resultDiv.style.display = 'block';
            
            // Auto-hide after 10 seconds for success messages
            if (type === 'success') {
                setTimeout(() => {
                    resultDiv.style.display = 'none';
                }, 10000);
            }
        }
        
        // Execute manual cron
        async function executeManualCron() {
            showStatus('🚀 Starting manual cron execution...', 'info');
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_manual_cron',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus(`✅ ${data.data}`, 'success');
                    setTimeout(refreshAllStatus, 2000);
                } else {
                    showStatus(`❌ Error: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Check dependencies
        async function checkDependencies() {
            showStatus('🔍 Checking dependencies...', 'info');
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_check_dependencies',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const status = data.data;
                    let html = '<h4>📋 Dependency Check Results:</h4>';
                    
                    html += '<p><strong>Overall Status:</strong> ' + (status.dependencies_loaded ? '✅ OK' : '❌ Issues Found') + '</p>';
                    
                    html += '<h5>Classes:</h5><ul>';
                    for (const [className, available] of Object.entries(status.classes)) {
                        html += `<li>${className}: ${available ? '✅' : '❌'}</li>`;
                    }
                    html += '</ul>';
                    
                    html += '<h5>Constants:</h5><ul>';
                    for (const [constName, value] of Object.entries(status.constants)) {
                        html += `<li>${constName}: ${value !== 'Not defined' ? '✅' : '❌'} (${value})</li>`;
                    }
                    html += '</ul>';
                    
                    document.getElementById('dependency-status').innerHTML = html;
                    showStatus('✅ Dependency check completed', 'success');
                } else {
                    showStatus(`❌ Error checking dependencies: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Force load classes
        async function forceLoadClasses() {
            showStatus('📥 Force loading classes...', 'info');
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_force_load_classes',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus(`✅ ${data.data}`, 'success');
                    setTimeout(checkDependencies, 1000);
                } else {
                    showStatus(`❌ Error: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Custom schedule
        async function customSchedule() {
            const type = document.getElementById('schedule-type').value;
            let scheduleData = {
                action: 'ai_rewriter_schedule_custom',
                nonce: aiRewriter.nonce,
                schedule_type: type
            };
            
            // Add type-specific data
            if (type === 'minutes') {
                scheduleData.minutes_from_now = document.getElementById('minutes-input').value;
            } else if (type === 'specific') {
                scheduleData.custom_time = document.getElementById('specific-time').value;
            } else if (type === 'recurring') {
                scheduleData.interval = document.getElementById('recurring-interval').value;
            }
            
            showStatus('📅 Setting custom schedule...', 'info');
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(scheduleData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const result = data.data;
                    let html = `<div class="status-success">`;
                    html += `<h4>✅ ${result.message}</h4>`;
                    html += `<p><strong>Next Run:</strong> ${result.next_run || 'N/A'}</p>`;
                    if (result.next_run_local) {
                        html += `<p><strong>Local Time:</strong> ${result.next_run_local}</p>`;
                    }
                    html += `<p><strong>Type:</strong> ${result.type}</p>`;
                    html += `</div>`;
                    
                    document.getElementById('schedule-result').innerHTML = html;
                    showStatus('✅ Custom schedule set successfully', 'success');
                    setTimeout(refreshAllStatus, 2000);
                } else {
                    showStatus(`❌ Error setting schedule: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Reschedule now
        async function rescheduleNow() {
            showStatus('⏰ Rescheduling for immediate execution...', 'info');
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_reschedule_now',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus(`✅ ${data.data.message}`, 'success');
                    setTimeout(refreshAllStatus, 2000);
                } else {
                    showStatus(`❌ Error: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Clear schedule
        async function clearSchedule() {
            if (!confirm('Are you sure you want to clear the current schedule?')) {
                return;
            }
            
            showStatus('🗑️ Clearing schedule...', 'info');
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_clear_schedule',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus(`✅ ${data.data}`, 'success');
                    setTimeout(refreshAllStatus, 1000);
                } else {
                    showStatus(`❌ Error: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Load logs
        async function loadLogs() {
            document.getElementById('logs-container').innerHTML = '<p>Loading logs...</p>';
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_get_logs',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const logs = data.data;
                    if (logs.length === 0) {
                        document.getElementById('logs-container').innerHTML = '<p>No logs available.</p>';
                    } else {
                        document.getElementById('logs-container').innerHTML = logs.join('<br>');
                    }
                } else {
                    document.getElementById('logs-container').innerHTML = `<p style="color: #ff6b6b;">Error loading logs: ${data.data}</p>`;
                }
            } catch (error) {
                document.getElementById('logs-container').innerHTML = `<p style="color: #ff6b6b;">Connection error: ${error.message}</p>`;
            }
        }
        
        // Clear logs
        async function clearLogs() {
            if (!confirm('Are you sure you want to clear all logs?')) {
                return;
            }
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_clear_logs',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus('✅ Logs cleared successfully', 'success');
                    loadLogs();
                } else {
                    showStatus(`❌ Error clearing logs: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Toggle auto-refresh
        function toggleAutoRefresh() {
            const statusSpan = document.getElementById('auto-refresh-status');
            
            if (autoRefreshEnabled) {
                // Disable auto-refresh
                autoRefreshEnabled = false;
                clearInterval(autoRefreshInterval);
                statusSpan.textContent = 'Enable';
                showStatus('🔴 Auto-refresh disabled', 'info');
            } else {
                // Enable auto-refresh
                autoRefreshEnabled = true;
                autoRefreshInterval = setInterval(() => {
                    loadLogs();
                    refreshAllStatus();
                }, 15000); // Every 15 seconds
                statusSpan.textContent = 'Disable';
                showStatus('🟢 Auto-refresh enabled (every 15 seconds)', 'success');
            }
        }
        
        // Refresh all status
        async function refreshAllStatus() {
            await Promise.all([
                checkSystemStatus(),
                checkCronStatus(),
                checkDependencies()
            ]);
        }
        
        // Check system status
        async function checkSystemStatus() {
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_get_status',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const status = data.data;
                    let html = '<h4>🖥️ System Status</h4>';
                    html += `<p><strong>Auto Processing:</strong> ${status.enabled ? '✅ Enabled' : '❌ Disabled'}</p>`;
                    html += `<p><strong>API Key:</strong> ${status.api_key_set ? '✅ Set' : '❌ Not Set'}</p>`;
                    html += `<p><strong>Total Drafts:</strong> ${status.total_drafts}</p>`;
                    html += `<p><strong>Pending:</strong> ${status.unprocessed}</p>`;
                    html += `<p><strong>Processed:</strong> ${status.processed}</p>`;
                    html += `<p><strong>Errors:</strong> ${status.errors}</p>`;
                    
                    document.getElementById('status-overview').innerHTML = html;
                }
            } catch (error) {
                document.getElementById('status-overview').innerHTML = '<p style="color: #ff6b6b;">Error loading system status</p>';
            }
        }
        
        // Check cron status
        async function checkCronStatus() {
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_check_cron',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const status = data.data;
                    let html = '<h4>⏰ Cron Status</h4>';
                    html += `<p><strong>Next Run:</strong> ${status.next_scheduled_formatted || 'Not scheduled'}</p>`;
                    html += `<p><strong>WP Cron:</strong> ${status.wp_cron_disabled ? '❌ Disabled' : '✅ Enabled'}</p>`;
                    html += `<p><strong>Intervals:</strong> ${status.our_intervals_registered} registered</p>`;
                    
                    if (status.is_stuck) {
                        html += '<p style="color: #dc3545;"><strong>⚠️ Cron appears to be stuck!</strong></p>';
                    }
                    
                    document.getElementById('cron-status').innerHTML = html;
                }
            } catch (error) {
                document.getElementById('cron-status').innerHTML = '<p style="color: #ff6b6b;">Error loading cron status</p>';
            }
        }
        
        // Reset processed flags
        async function resetProcessedFlags() {
            if (!confirm('This will reset all processing flags and allow posts to be reprocessed. Continue?')) {
                return;
            }
            
            showStatus('🔄 Resetting processed flags...', 'info');
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_reset',
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus(`✅ ${data.data}`, 'success');
                    setTimeout(refreshAllStatus, 2000);
                } else {
                    showStatus(`❌ Error: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Emergency stop
        async function emergencyStop() {
            if (!confirm('This will immediately clear all schedules and stop all processing. Continue?')) {
                return;
            }
            
            showStatus('🛑 Emergency stop initiated...', 'warning');
            
            try {
                await clearSchedule();
                showStatus('🛑 Emergency stop completed - all processing halted', 'warning');
            } catch (error) {
                showStatus(`❌ Error during emergency stop: ${error.message}`, 'error');
            }
        }
        
        // Manual process specific post
        async function manualProcess() {
            const postId = document.getElementById('manual-post-id').value;
            showStatus('🔧 Starting manual processing...', 'info');
            
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'ai_rewriter_manual_process',
                        post_id: postId,
                        nonce: aiRewriter.nonce
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatus(`✅ Manual processing completed: ${data.data}`, 'success');
                    setTimeout(refreshAllStatus, 2000);
                } else {
                    showStatus(`❌ Error: ${data.data}`, 'error');
                }
            } catch (error) {
                showStatus(`❌ Connection error: ${error.message}`, 'error');
            }
        }
        
        // Initialize AJAX variables (these would normally be provided by WordPress)
        const ajaxurl = '/wp-admin/admin-ajax.php';
        const aiRewriter = {
            nonce: 'your-nonce-here' // This would be generated by WordPress
        };
    </script>
</body>
</html>