document.addEventListener('DOMContentLoaded', function () {
    // Chart Colors
    const colors = {
        new: '#f59e0b',
        progress: '#3b82f6',
        completed: '#10b981',
        cancelled: '#ef4444',
        paused: '#f97316',
        template: '#8b5cf6'
    };

    // Project Status Chart (Pie Chart)
    const projectCtx = document.getElementById('projectStatusChart');
    if (projectCtx) {
        new Chart(projectCtx, {
            type: 'doughnut',
            data: {
                labels: ['جديد', 'قيد التنفيذ', 'مكتمل', 'ملغي'],
                datasets: [{
                    data: [
                        window.teamData.projectStats.new,
                        window.teamData.projectStats.in_progress,
                        window.teamData.projectStats.completed,
                        window.teamData.projectStats.cancelled
                    ],
                    backgroundColor: [
                        colors.new,
                        colors.progress,
                        colors.completed,
                        colors.cancelled
                    ],
                    borderWidth: 0,
                    cutout: '60%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Tasks Chart (Bar Chart)
    const tasksCtx = document.getElementById('tasksChart');
    if (tasksCtx) {
        new Chart(tasksCtx, {
            type: 'bar',
            data: {
                labels: ['جديدة', 'قيد التنفيذ', 'متوقفة', 'مكتملة'],
                datasets: [
                    {
                        label: 'المهام العادية',
                        data: [
                            window.teamData.taskStats.regular.new,
                            window.teamData.taskStats.regular.in_progress,
                            window.teamData.taskStats.regular.paused,
                            window.teamData.taskStats.regular.completed
                        ],
                        backgroundColor: colors.progress,
                        borderRadius: 6,
                        borderSkipped: false
                    },
                    {
                        label: 'مهام القوالب',
                        data: [
                            window.teamData.taskStats.template.new,
                            window.teamData.taskStats.template.in_progress,
                            window.teamData.taskStats.template.paused,
                            window.teamData.taskStats.template.completed
                        ],
                        backgroundColor: colors.template,
                        borderRadius: 6,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            font: {
                                family: 'Cairo',
                                size: 12
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: false,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            font: {
                                family: 'Cairo'
                            }
                        }
                    },
                    x: {
                        stacked: false,
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Cairo'
                            }
                        }
                    }
                }
            }
        });
    }

    // Time Chart (Bar Chart)
    const timeCtx = document.getElementById('timeChart');
    if (timeCtx) {
        new Chart(timeCtx, {
            type: 'bar',
            data: {
                labels: ['الوقت المقدر', 'الوقت الفعلي'],
                datasets: [{
                    label: 'الساعات',
                    data: [
                        window.teamData.timeStats.estimated_hours,
                        window.teamData.timeStats.spent_hours
                    ],
                    backgroundColor: [colors.new, colors.completed],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed.y} ساعة`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + 'h';
                            },
                            font: {
                                family: 'Cairo'
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Cairo'
                            }
                        }
                    }
                }
            }
        });
    }
});
