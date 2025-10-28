

document.addEventListener('DOMContentLoaded', function() {
    // Chart.js Configuration
    Chart.defaults.font.family = 'Cairo, sans-serif';
    Chart.defaults.color = '#4a5568';
    Chart.defaults.plugins.tooltip.rtl = true;
    Chart.defaults.plugins.tooltip.titleAlign = 'right';
    Chart.defaults.plugins.tooltip.bodyAlign = 'right';

    // Ensure charts are responsive
    const resizeCharts = () => {
        const chartContainers = document.querySelectorAll('.hr-chart-body');
        chartContainers.forEach(container => {
            const canvas = container.querySelector('canvas');
            if (canvas) {
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.height = container.offsetHeight;
                canvas.width = container.offsetWidth;
            }
        });
    };

    // Call resize on load and window resize
    resizeCharts();
    window.addEventListener('resize', resizeCharts);

    // Ensure charts are visible after load
    setTimeout(() => {
        document.querySelectorAll('.hr-chart-card').forEach(card => {
            card.classList.add('fade-in');
        });

        // Force redraw of charts
        window.dispatchEvent(new Event('resize'));
    }, 100);

    // Common Colors
    const colors = {
        approved: {
            bg: 'rgba(25, 135, 84, 0.2)',
            border: 'rgb(25, 135, 84)'
        },
        pending: {
            bg: 'rgba(255, 193, 7, 0.2)',
            border: 'rgb(255, 193, 7)'
        },
        rejected: {
            bg: 'rgba(220, 53, 69, 0.2)',
            border: 'rgb(220, 53, 69)'
        },
        onTime: {
            bg: 'rgba(13, 110, 253, 0.2)',
            border: 'rgb(13, 110, 253)'
        },
        late: {
            bg: 'rgba(220, 53, 69, 0.2)',
            border: 'rgb(220, 53, 69)'
        }
    };

    // Common Chart Options
    const commonPieOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                rtl: true,
                labels: {
                    font: {
                        family: 'Cairo, sans-serif'
                    },
                    padding: 20
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((value * 100) / total).toFixed(1) : 0;
                        return `${label}: ${value} (${percentage}%)`;
                    }
                }
            }
        }
    };

    const commonBarOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            x: {
                ticks: {
                    font: {
                        family: 'Cairo, sans-serif'
                    }
                }
            }
        }
    };

    // Initialize Chart.js Configuration
    function initializeChartDefaults() {
        Chart.defaults.font.family = 'Cairo, sans-serif';
        Chart.defaults.color = '#4a5568';
        Chart.defaults.plugins.tooltip.rtl = true;
        Chart.defaults.plugins.tooltip.titleAlign = 'right';
        Chart.defaults.plugins.tooltip.bodyAlign = 'right';
    }

    // Ensure charts are responsive
    function setupChartResponsiveness() {
        const resizeCharts = () => {
            const chartContainers = document.querySelectorAll('.hr-chart-body');
            chartContainers.forEach(container => {
                const canvas = container.querySelector('canvas');
                if (canvas) {
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                    canvas.height = container.offsetHeight;
                    canvas.width = container.offsetWidth;
                }
            });
        };

        resizeCharts();
        window.addEventListener('resize', resizeCharts);

        setTimeout(() => {
            document.querySelectorAll('.hr-chart-card').forEach(card => {
                card.classList.add('fade-in');
            });
            window.dispatchEvent(new Event('resize'));
        }, 100);
    }

    // Initialize Personal Charts
    function initializePersonalCharts(statistics) {
        if (!statistics?.personal) return;

        const personalRequestsCtx = document.getElementById('personalRequestsChart')?.getContext('2d');
        if (personalRequestsCtx) {
            new Chart(personalRequestsCtx, {
                type: 'pie',
                data: {
                    labels: ['تمت الموافقة', 'معلقة', 'مرفوضة'],
                    datasets: [{
                        data: [
                            statistics.personal.approved_requests,
                            statistics.personal.pending_requests,
                            statistics.personal.rejected_requests
                        ],
                        backgroundColor: [colors.approved.bg, colors.pending.bg, colors.rejected.bg],
                        borderColor: [colors.approved.border, colors.pending.border, colors.rejected.border],
                        borderWidth: 1
                    }]
                },
                options: commonPieOptions
            });
        }

        const personalMinutesCtx = document.getElementById('personalMinutesChart')?.getContext('2d');
        if (personalMinutesCtx) {
            new Chart(personalMinutesCtx, {
                type: 'bar',
                data: {
                    labels: ['الدقائق المستخدمة', 'عودة في الوقت', 'عودة متأخرة'],
                    datasets: [{
                        label: 'العدد',
                        data: [
                            statistics.personal.total_minutes,
                            statistics.personal.on_time_returns,
                            statistics.personal.late_returns
                        ],
                        backgroundColor: [colors.approved.bg, colors.onTime.bg, colors.late.bg],
                        borderColor: [colors.approved.border, colors.onTime.border, colors.late.border],
                        borderWidth: 1
                    }]
                },
                options: {
                    ...commonBarOptions,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const index = context.dataIndex;
                                    if (index === 0) {
                                        return `الدقائق المستخدمة: ${context.parsed.y} دقيقة`;
                                    } else {
                                        return `العدد: ${context.parsed.y}`;
                                    }
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Initialize Team Charts
    function initializeTeamCharts(statistics) {
        if (!statistics?.team) return;

        const teamRequestsCtx = document.getElementById('teamRequestsChart')?.getContext('2d');
        if (teamRequestsCtx) {
            const totalRequests = statistics.team.total_requests;
            const exceededLimit = statistics.team.employees_exceeded_limit;
            const withinLimit = totalRequests - exceededLimit;

            new Chart(teamRequestsCtx, {
                type: 'pie',
                data: {
                    labels: ['ضمن الحد المسموح', 'تجاوزوا الحد'],
                    datasets: [{
                        data: [withinLimit, exceededLimit],
                        backgroundColor: [colors.approved.bg, colors.rejected.bg],
                        borderColor: [colors.approved.border, colors.rejected.border],
                        borderWidth: 1
                    }]
                },
                options: commonPieOptions
            });
        }

        const teamMinutesCtx = document.getElementById('teamMinutesChart')?.getContext('2d');
        if (teamMinutesCtx) {
            if (statistics.team.team_employees?.length > 0) {
                const sortedEmployees = statistics.team.team_employees
                    .sort((a, b) => b.minutes - a.minutes)
                    .slice(0, 5);

                new Chart(teamMinutesCtx, {
                    type: 'bar',
                    data: {
                        labels: sortedEmployees.map(emp => emp.name),
                        datasets: [{
                            label: 'الدقائق المستخدمة',
                            data: sortedEmployees.map(emp => emp.minutes),
                            backgroundColor: colors.approved.bg,
                            borderColor: colors.approved.border,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        ...commonBarOptions,
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.parsed.y} دقيقة`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            },
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                new Chart(teamMinutesCtx, {
                    type: 'bar',
                    data: {
                        labels: ['إجمالي دقائق الفريق'],
                        datasets: [{
                            label: 'الدقائق',
                            data: [statistics.team.total_minutes],
                            backgroundColor: colors.approved.bg,
                            borderColor: colors.approved.border,
                            borderWidth: 1
                        }]
                    },
                    options: commonBarOptions
                });
            }
        }
    }

    // Initialize HR Charts
    function initializeHRCharts(statistics) {
        if (!statistics?.hr) return;

        // HR Requests Chart
        const hrRequestsCtx = document.getElementById('hrRequestsChart')?.getContext('2d');
        if (hrRequestsCtx) {
            const totalRequests = statistics.hr.total_requests;
            const pendingRequests = statistics.hr.pending_requests;
            const exceededLimit = statistics.hr.employees_exceeded_limit;
            const normalRequests = totalRequests - pendingRequests - exceededLimit;

            new Chart(hrRequestsCtx, {
                type: 'pie',
                data: {
                    labels: ['طلبات عادية', 'طلبات معلقة', 'تجاوزوا الحد'],
                    datasets: [{
                        data: [normalRequests, pendingRequests, exceededLimit],
                        backgroundColor: [colors.approved.bg, colors.pending.bg, colors.rejected.bg],
                        borderColor: [colors.approved.border, colors.pending.border, colors.rejected.border],
                        borderWidth: 1
                    }]
                },
                options: commonPieOptions
            });
        }

        // Return Status Chart
        const hrReturnStatusCtx = document.getElementById('hrReturnStatusChart')?.getContext('2d');
        if (hrReturnStatusCtx && statistics.hr.return_status_stats) {
            const returnStats = statistics.hr.return_status_stats;

            new Chart(hrReturnStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['عاد في الوقت', 'تأخر عن العودة', 'غير محدد'],
                    datasets: [{
                        data: [
                            returnStats.on_time_returns || 0,
                            returnStats.late_returns || 0,
                            returnStats.undefined_returns || 0
                        ],
                        backgroundColor: [colors.onTime.bg, colors.late.bg, colors.pending.bg],
                        borderColor: [colors.onTime.border, colors.late.border, colors.pending.border],
                        borderWidth: 1
                    }]
                },
                options: {
                    ...commonPieOptions,
                    plugins: {
                        ...commonPieOptions.plugins,
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value * 100) / total).toFixed(1) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // Department Minutes Chart
        initializeDepartmentChart(statistics);

        // Daily Trend Chart
        initializeDailyTrendChart(statistics);

        // Busiest Days Chart
        initializeBusiestDaysChart(statistics);

        // Busiest Hours Chart
        initializeBusiestHoursChart(statistics);
    }

    // Make colors and options available globally
    window.chartColors = colors;
    window.chartOptions = {
        commonPieOptions,
        commonBarOptions
    };

    // Initialize all charts
    initializeChartDefaults();
    setupChartResponsiveness();

    if (window.permissionStatistics) {
        if (window.permissionStatistics.personal) {
            initializePersonalCharts(window.permissionStatistics);
        }
        if (window.permissionStatistics.team) {
            initializeTeamCharts(window.permissionStatistics);
        }
        if (window.permissionStatistics.hr) {
            initializeHRCharts(window.permissionStatistics);
        }
    }
});
