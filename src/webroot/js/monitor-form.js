/**
 * Monitor Form - Dynamic Field Management
 *
 * Handles showing/hiding monitor type-specific fields based on the selected type.
 * Also handles the Quick Setup / Advanced mode toggle.
 */

/**
 * Switch between Quick Setup and Advanced mode
 */
function switchMode(mode) {
    var quickPanel = document.getElementById('quick-setup-panel');
    var advancedPanel = document.getElementById('advanced-panel');
    var btnQuick = document.getElementById('btn-quick-setup');
    var btnAdvanced = document.getElementById('btn-advanced');

    if (!quickPanel || !advancedPanel) {
        return;
    }

    if (mode === 'quick') {
        quickPanel.style.display = 'block';
        advancedPanel.style.display = 'none';
        btnQuick.classList.add('mode-btn-active');
        btnAdvanced.classList.remove('mode-btn-active');
    } else {
        quickPanel.style.display = 'none';
        advancedPanel.style.display = 'block';
        btnQuick.classList.remove('mode-btn-active');
        btnAdvanced.classList.add('mode-btn-active');
    }
}

/**
 * Initialize the Quick Setup mode functionality
 */
function initQuickSetup() {
    var urlInput = document.getElementById('quick-url');
    var nameInput = document.getElementById('quick-name');
    var typeInput = document.getElementById('quick-type');
    var targetInput = document.getElementById('quick-target');
    var typeIndicator = document.getElementById('quick-type-indicator');
    var typeBadge = document.getElementById('quick-type-badge');
    var typeLabel = document.getElementById('quick-type-label');

    if (!urlInput) {
        return;
    }

    urlInput.addEventListener('input', function() {
        var url = this.value.trim();

        if (!url) {
            if (typeIndicator) typeIndicator.style.display = 'none';
            if (targetInput) targetInput.value = '';
            return;
        }

        // Auto-fill name from domain
        try {
            var parsedUrl = new URL(url);
            if (nameInput) nameInput.value = parsedUrl.hostname;
        } catch(e) {
            // Not a valid URL — use the raw input as name
            if (nameInput) {
                // Strip port if present
                var cleanName = url.replace(/:\d+$/, '');
                nameInput.value = cleanName;
            }
        }

        // Auto-detect monitor type
        var detectedType = 'http';
        var detectedLabel = 'HTTP/HTTPS monitor';
        var target = url;

        // Check if it looks like an IP address (with optional port)
        var ipPattern = /^(\d{1,3}\.){3}\d{1,3}(:\d+)?$/;

        if (url.match(/^https?:\/\//i)) {
            // Starts with http/https -> HTTP monitor
            detectedType = 'http';
            detectedLabel = 'HTTP/HTTPS monitor — will check URL response';
            target = url;
        } else if (url.match(/:\d+$/)) {
            // Has a port number -> Port monitor
            detectedType = 'port';
            detectedLabel = 'Port monitor — will check TCP connectivity';
            // Extract host and port
            var parts = url.split(':');
            target = parts.slice(0, -1).join(':');
        } else if (ipPattern.test(url)) {
            // Looks like a plain IP address -> Ping monitor
            detectedType = 'ping';
            detectedLabel = 'Ping monitor — will send ICMP ping';
            target = url;
        } else {
            // Default: assume it is a website, prepend https://
            detectedType = 'http';
            detectedLabel = 'HTTP/HTTPS monitor — will check URL response';
            target = 'https://' + url;
        }

        // Update hidden fields
        if (typeInput) typeInput.value = detectedType;
        if (targetInput) targetInput.value = target;

        // Show type indicator
        if (typeIndicator) typeIndicator.style.display = 'flex';
        if (typeBadge) {
            typeBadge.textContent = detectedType.toUpperCase();
        }
        if (typeLabel) typeLabel.textContent = detectedLabel;
    });

    // Quick setup form submission — populate the target field before submit
    var quickForm = document.getElementById('quick-setup-form');
    if (quickForm) {
        quickForm.addEventListener('submit', function(e) {
            var url = urlInput.value.trim();
            if (!url) {
                e.preventDefault();
                urlInput.focus();
                return false;
            }

            // Ensure target is set
            if (targetInput && !targetInput.value) {
                targetInput.value = url.match(/^https?:\/\//) ? url : 'https://' + url;
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Quick Setup mode
    initQuickSetup();

    const typeSelect = document.getElementById('monitor-type');

    if (!typeSelect) {
        return; // Not on monitor form page or in quick setup mode only
    }

    const httpFields = document.getElementById('http-fields');
    const pingFields = document.getElementById('ping-fields');
    const portFields = document.getElementById('port-fields');
    const heartbeatFields = document.getElementById('heartbeat-fields');
    const sslFields = document.getElementById('ssl-fields');

    /**
     * Update visible fields based on selected monitor type
     */
    function updateFields() {
        const type = typeSelect.value;

        // Hide all type-specific fields and disable their inputs
        if (httpFields) {
            httpFields.style.display = 'none';
            disableFieldInputs(httpFields);
        }
        if (pingFields) {
            pingFields.style.display = 'none';
            disableFieldInputs(pingFields);
        }
        if (portFields) {
            portFields.style.display = 'none';
            disableFieldInputs(portFields);
        }
        if (heartbeatFields) {
            heartbeatFields.style.display = 'none';
            disableFieldInputs(heartbeatFields);
        }
        if (sslFields) {
            sslFields.style.display = 'none';
            disableFieldInputs(sslFields);
        }

        // Show relevant fields based on type and enable their inputs
        switch (type) {
            case 'http':
                if (httpFields) {
                    httpFields.style.display = 'block';
                    enableFieldInputs(httpFields);
                }
                updateTargetPlaceholder('https://example.com or http://api.example.com/health');
                updateTargetHelp('Full URL including protocol (http:// or https://)');
                break;

            case 'ping':
                if (pingFields) {
                    pingFields.style.display = 'block';
                    enableFieldInputs(pingFields);
                }
                updateTargetPlaceholder('example.com or 192.168.1.1');
                updateTargetHelp('Hostname or IP address for ICMP ping');
                break;

            case 'port':
                if (portFields) {
                    portFields.style.display = 'block';
                    enableFieldInputs(portFields);
                }
                updateTargetPlaceholder('example.com or 192.168.1.1');
                updateTargetHelp('Hostname or IP address for TCP/UDP port check');
                break;

            case 'heartbeat':
                if (heartbeatFields) {
                    heartbeatFields.style.display = 'block';
                    enableFieldInputs(heartbeatFields);
                }
                updateTargetPlaceholder('');
                updateTargetHelp('Target is not needed for heartbeat monitors — a ping URL will be generated.');
                break;

            case 'keyword':
                if (httpFields) {
                    httpFields.style.display = 'block';
                    enableFieldInputs(httpFields);
                }
                updateTargetPlaceholder('https://example.com');
                updateTargetHelp('URL to check for keyword presence');
                break;

            case 'ssl':
                if (sslFields) {
                    sslFields.style.display = 'block';
                    enableFieldInputs(sslFields);
                }
                updateTargetPlaceholder('example.com');
                updateTargetHelp('Domain name to check SSL certificate');
                break;

            default:
                updateTargetPlaceholder('example.com or 192.168.1.1');
                updateTargetHelp('Target address for monitoring');
        }

        // Enable/disable required fields based on type
        updateRequiredFields(type);
    }

    /**
     * Disable all inputs within a container
     */
    function disableFieldInputs(container) {
        const inputs = container.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.disabled = true;
        });
    }

    /**
     * Enable all inputs within a container
     */
    function enableFieldInputs(container) {
        const inputs = container.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.disabled = false;
        });
    }

    /**
     * Update target input placeholder
     */
    function updateTargetPlaceholder(text) {
        const targetInput = document.querySelector('input[name="target"]');
        if (targetInput) {
            targetInput.placeholder = text;
        }
    }

    /**
     * Update target help text
     */
    function updateTargetHelp(text) {
        const targetHelp = document.querySelector('.target-help');
        if (targetHelp) {
            targetHelp.textContent = text;
        }
    }

    /**
     * Update required attributes for type-specific fields
     */
    function updateRequiredFields(type) {
        // HTTP fields
        const httpMethod = document.querySelector('select[name="configuration[method]"]');
        const expectedStatus = document.querySelector('input[name="expected_status_code"]');

        // Ping fields
        const packetCount = document.querySelector('input[name="configuration[packet_count]"]');
        const maxLatency = document.querySelector('input[name="configuration[max_latency]"]');

        // Port fields
        const port = document.querySelector('input[name="port"]');
        const protocol = document.querySelector('select[name="configuration[protocol]"]');

        // Remove all required attributes first
        [httpMethod, expectedStatus, packetCount, maxLatency, port, protocol].forEach(field => {
            if (field) field.removeAttribute('required');
        });

        // Add required based on type
        switch (type) {
            case 'http':
                if (httpMethod) httpMethod.setAttribute('required', 'required');
                if (expectedStatus) expectedStatus.setAttribute('required', 'required');
                break;

            case 'ping':
                if (packetCount) packetCount.setAttribute('required', 'required');
                break;

            case 'port':
                if (port) port.setAttribute('required', 'required');
                if (protocol) protocol.setAttribute('required', 'required');
                break;
        }
    }

    /**
     * Validate HTTP headers JSON format
     */
    function validateHttpHeaders() {
        const headersTextarea = document.querySelector('textarea[name="configuration[headers]"]');
        if (!headersTextarea || !headersTextarea.value.trim()) {
            return true;
        }

        try {
            JSON.parse(headersTextarea.value);
            headersTextarea.setCustomValidity('');
            return true;
        } catch (e) {
            headersTextarea.setCustomValidity('Invalid JSON format. Example: {"Authorization": "Bearer token"}');
            return false;
        }
    }

    // Attach event listeners
    typeSelect.addEventListener('change', updateFields);

    // Validate HTTP headers on blur
    const headersTextarea = document.querySelector('textarea[name="configuration[headers]"]');
    if (headersTextarea) {
        headersTextarea.addEventListener('blur', validateHttpHeaders);
    }

    // Form validation before submit
    const form = typeSelect.closest('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateHttpHeaders()) {
                e.preventDefault();
                alert('Please fix the HTTP headers JSON format before submitting.');
                return false;
            }
        });
    }

    // Initial call to set correct state
    updateFields();
});
