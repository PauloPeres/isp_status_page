/**
 * DateTime Utilities
 *
 * Converts UTC dates to user's local timezone
 */

/**
 * Format date to user's local timezone
 * @param {string} utcString - ISO 8601 date string
 * @returns {string} Formatted date in pt-BR locale
 */
function formatLocalDateTime(utcString) {
    const date = new Date(utcString);
    return date.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

/**
 * Format date to user's local date only (no time)
 * @param {string} utcString - ISO 8601 date string
 * @returns {string} Formatted date in pt-BR locale
 */
function formatLocalDate(utcString) {
    const date = new Date(utcString);
    return date.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/**
 * Format time ago in Portuguese
 * @param {string} utcString - ISO 8601 date string
 * @returns {string} Relative time string (e.g., "há 5 minutos")
 */
function formatTimeAgo(utcString) {
    const date = new Date(utcString);
    const now = new Date();
    const diffMs = now - date;
    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffSecs < 60) {
        return 'agora';
    } else if (diffMins < 60) {
        return diffMins === 1 ? 'há 1 minuto' : `há ${diffMins} minutos`;
    } else if (diffHours < 24) {
        const mins = diffMins % 60;
        if (mins > 0) {
            return `há ${diffHours}h ${mins}min`;
        }
        return diffHours === 1 ? 'há 1 hora' : `há ${diffHours} horas`;
    } else {
        return diffDays === 1 ? 'há 1 dia' : `há ${diffDays} dias`;
    }
}

/**
 * Convert all UTC dates on page to local timezone
 * Call this function on DOMContentLoaded
 */
function convertAllDatesToLocal() {
    // Convert full datetime
    document.querySelectorAll('.local-datetime').forEach(function(element) {
        const utc = element.getAttribute('data-utc');
        if (utc) {
            element.textContent = formatLocalDateTime(utc);
        }
    });

    // Convert date only (no time)
    document.querySelectorAll('.local-date').forEach(function(element) {
        const utc = element.getAttribute('data-utc');
        if (utc) {
            element.textContent = formatLocalDate(utc);
        }
    });

    // Convert time ago
    document.querySelectorAll('.time-ago').forEach(function(element) {
        const utc = element.getAttribute('data-utc');
        if (utc) {
            element.textContent = formatTimeAgo(utc);
        }
    });
}

// Auto-initialize when document is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', convertAllDatesToLocal);
} else {
    // Document already loaded
    convertAllDatesToLocal();
}
