(function() {
    var slug = document.body.dataset.slug;
    if (!slug) return;

    setInterval(async function() {
        try {
            var res = await fetch('/api/v2/public/status/' + slug);
            var data = await res.json();
            if (!data.success) return;

            // Update monitor statuses
            (data.data.monitors || []).forEach(function(m) {
                var el = document.querySelector('[data-monitor-id="' + m.id + '"]');
                if (!el) return;
                var dot = el.querySelector('.sp-dot');
                if (dot) { dot.className = 'sp-dot sp-dot-' + m.status; }
                var uptime = el.querySelector('.sp-monitor-uptime');
                if (uptime) { uptime.textContent = parseFloat(m.uptime_percentage || 0).toFixed(1) + '%'; }
            });

            // Update overall status
            var overall = document.querySelector('.sp-overall');
            if (overall && data.data.overall_status) {
                overall.className = 'sp-overall sp-overall-' + data.data.overall_status;
            }

            // Update last-checked timestamp
            var ts = document.querySelector('.sp-last-updated');
            if (ts) { ts.textContent = 'Last updated: ' + new Date().toLocaleTimeString(); }
        } catch (e) { /* silent */ }
    }, 30000);
})();
