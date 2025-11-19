/**
 * Monitor Form - Dynamic Field Management
 *
 * Handles showing/hiding monitor type-specific fields based on the selected type.
 */

document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('monitor-type');

    if (!typeSelect) {
        return; // Not on monitor form page
    }

    const httpFields = document.getElementById('http-fields');
    const pingFields = document.getElementById('ping-fields');
    const portFields = document.getElementById('port-fields');

    /**
     * Update visible fields based on selected monitor type
     */
    function updateFields() {
        const type = typeSelect.value;

        // Hide all type-specific fields
        if (httpFields) httpFields.style.display = 'none';
        if (pingFields) pingFields.style.display = 'none';
        if (portFields) portFields.style.display = 'none';

        // Show relevant fields based on type
        switch (type) {
            case 'http':
                if (httpFields) httpFields.style.display = 'block';
                updateTargetPlaceholder('https://example.com or http://api.example.com/health');
                updateTargetHelp('Full URL including protocol (http:// or https://)');
                break;

            case 'ping':
                if (pingFields) pingFields.style.display = 'block';
                updateTargetPlaceholder('example.com or 192.168.1.1');
                updateTargetHelp('Hostname or IP address for ICMP ping');
                break;

            case 'port':
                if (portFields) portFields.style.display = 'block';
                updateTargetPlaceholder('example.com or 192.168.1.1');
                updateTargetHelp('Hostname or IP address for TCP/UDP port check');
                break;

            default:
                updateTargetPlaceholder('example.com or 192.168.1.1');
                updateTargetHelp('Target address for monitoring');
        }

        // Enable/disable required fields based on type
        updateRequiredFields(type);
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
