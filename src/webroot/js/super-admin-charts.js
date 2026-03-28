/**
 * Super Admin Charts - Chart.js initialization
 *
 * Renders charts for the Super Admin Dashboard and Revenue pages.
 * Expects window.superAdminData to be set with relevant data.
 */
document.addEventListener('DOMContentLoaded', function () {
    var data = window.superAdminData;
    if (!data) {
        return;
    }

    var isMobile = window.innerWidth <= 768;

    // Super Admin color palette
    var colors = {
        primary: '#3b82f6',
        primaryLight: 'rgba(59, 130, 246, 0.1)',
        success: '#22c55e',
        successLight: 'rgba(34, 197, 94, 0.2)',
        danger: '#ef4444',
        dangerLight: 'rgba(239, 68, 68, 0.2)',
        warning: '#f59e0b',
        warningLight: 'rgba(245, 158, 11, 0.2)',
        purple: '#8b5cf6',
        purpleLight: 'rgba(139, 92, 246, 0.2)',
        accent: '#e94560',
        accentLight: 'rgba(233, 69, 96, 0.2)',
        gray: '#6b7280'
    };

    var planColors = {
        free: colors.gray,
        pro: colors.warning,
        business: colors.accent
    };

    // ==========================================
    // Dashboard: Plan Distribution Doughnut Chart
    // ==========================================
    var planDistCtx = document.getElementById('planDistributionChart');
    if (planDistCtx && data.planDistribution) {
        var planLabels = Object.keys(data.planDistribution).map(function (k) {
            return k.charAt(0).toUpperCase() + k.slice(1);
        });
        var planValues = Object.values(data.planDistribution);
        var planBgColors = Object.keys(data.planDistribution).map(function (k) {
            return planColors[k] || colors.gray;
        });

        new Chart(planDistCtx, {
            type: 'doughnut',
            data: {
                labels: planLabels,
                datasets: [{
                    data: planValues,
                    backgroundColor: planBgColors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 100,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            font: {
                                size: isMobile ? 11 : 13
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var total = context.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                return context.label + ': ' + context.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // ==========================================
    // Dashboard: New Signups Line Chart
    // ==========================================
    var signupsCtx = document.getElementById('signupsChart');
    if (signupsCtx && data.signupsByDay) {
        var signupLabels = Object.keys(data.signupsByDay).map(function (d) {
            // Format as MM-DD
            var parts = d.split('-');
            return parts[1] + '-' + parts[2];
        });
        var signupValues = Object.values(data.signupsByDay);

        new Chart(signupsCtx, {
            type: 'line',
            data: {
                labels: signupLabels,
                datasets: [{
                    label: 'New Signups',
                    data: signupValues,
                    borderColor: colors.accent,
                    backgroundColor: colors.accentLight,
                    pointBackgroundColor: colors.accent,
                    pointBorderColor: colors.accent,
                    pointRadius: isMobile ? 2 : 3,
                    pointHoverRadius: isMobile ? 4 : 6,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 100,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: isMobile ? 10 : 12
                            }
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: isMobile ? 90 : 45,
                            minRotation: 0,
                            font: {
                                size: isMobile ? 9 : 11
                            },
                            maxTicksLimit: isMobile ? 8 : 15
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.parsed.y + ' signup' + (context.parsed.y !== 1 ? 's' : '');
                            }
                        }
                    }
                }
            }
        });
    }

    // ==========================================
    // Revenue: Revenue by Plan Horizontal Bar Chart
    // ==========================================
    var revenueByPlanCtx = document.getElementById('revenueByPlanChart');
    if (revenueByPlanCtx && data.revenueByPlan) {
        var revLabels = Object.keys(data.revenueByPlan).map(function (k) {
            return k.charAt(0).toUpperCase() + k.slice(1);
        });
        var revValues = Object.values(data.revenueByPlan);
        var revColors = Object.keys(data.revenueByPlan).map(function (k) {
            return planColors[k] || colors.gray;
        });

        new Chart(revenueByPlanCtx, {
            type: 'bar',
            data: {
                labels: revLabels,
                datasets: [{
                    label: 'Monthly Revenue ($)',
                    data: revValues,
                    backgroundColor: revColors,
                    borderColor: revColors,
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 100,
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '$' + value;
                            },
                            font: {
                                size: isMobile ? 10 : 12
                            }
                        }
                    },
                    y: {
                        ticks: {
                            font: {
                                size: isMobile ? 11 : 13
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return '$' + context.parsed.x.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }

    // ==========================================
    // Revenue: Plan Distribution Doughnut Chart
    // ==========================================
    var revPlanDistCtx = document.getElementById('revenuePlanDistChart');
    if (revPlanDistCtx && data.revenueByPlan) {
        var distLabels = Object.keys(data.revenueByPlan).map(function (k) {
            return k.charAt(0).toUpperCase() + k.slice(1);
        });
        var distValues = Object.values(data.revenueByPlan);
        var distColors = Object.keys(data.revenueByPlan).map(function (k) {
            return planColors[k] || colors.gray;
        });

        new Chart(revPlanDistCtx, {
            type: 'doughnut',
            data: {
                labels: distLabels,
                datasets: [{
                    data: distValues,
                    backgroundColor: distColors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                resizeDelay: 100,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            font: {
                                size: isMobile ? 11 : 13
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': $' + context.parsed.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    }
});
