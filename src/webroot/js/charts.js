/**
 * Dashboard Charts - Chart.js initialization
 *
 * Renders uptime and response time charts on the admin dashboard.
 * Expects window.dashboardData to be set with { uptime, responseTime } arrays.
 */
document.addEventListener('DOMContentLoaded', function () {
    var data = window.dashboardData;
    if (!data) {
        return;
    }

    // Color palette
    var colors = {
        primary: '#3b82f6',
        primaryLight: 'rgba(59, 130, 246, 0.1)',
        success: '#22c55e',
        successLight: 'rgba(34, 197, 94, 0.2)',
        danger: '#ef4444',
        warning: '#f59e0b',
        gray: '#6b7280'
    };

    // Uptime Line Chart
    var uptimeCtx = document.getElementById('uptimeChart');
    if (uptimeCtx && data.uptime && data.uptime.length > 0) {
        var uptimeLabels = data.uptime.map(function (item) { return item.name; });
        var uptimeValues = data.uptime.map(function (item) { return item.uptime; });

        // Color each point based on uptime percentage
        var pointColors = uptimeValues.map(function (val) {
            if (val >= 99) return colors.success;
            if (val >= 90) return colors.warning;
            return colors.danger;
        });

        new Chart(uptimeCtx, {
            type: 'line',
            data: {
                labels: uptimeLabels,
                datasets: [{
                    label: 'Uptime %',
                    data: uptimeValues,
                    borderColor: colors.primary,
                    backgroundColor: colors.primaryLight,
                    pointBackgroundColor: pointColors,
                    pointBorderColor: pointColors,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function (value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + context.parsed.y + '%';
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Response Time Bar Chart
    var responseCtx = document.getElementById('responseTimeChart');
    if (responseCtx && data.responseTime && data.responseTime.length > 0) {
        var rtLabels = data.responseTime.map(function (item) { return item.name; });
        var rtValues = data.responseTime.map(function (item) { return item.avg_response_time; });

        // Color bars based on response time
        var barColors = rtValues.map(function (val) {
            if (val <= 200) return colors.success;
            if (val <= 500) return colors.warning;
            return colors.danger;
        });

        new Chart(responseCtx, {
            type: 'bar',
            data: {
                labels: rtLabels,
                datasets: [{
                    label: 'Tempo de Resposta (ms)',
                    data: rtValues,
                    backgroundColor: barColors,
                    borderColor: barColors,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return value + ' ms';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' ms';
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
});
