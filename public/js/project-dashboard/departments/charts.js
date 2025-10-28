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
                        window.departmentData.projectStats.new,
                        window.departmentData.projectStats.in_progress,
                        window.departmentData.projectStats.completed,
                        window.departmentData.projectStats.cancelled
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
                                if (total === 0) return `${context.label}: 0`;
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
                            window.departmentData.taskStats.regular.new,
                            window.departmentData.taskStats.regular.in_progress,
                            window.departmentData.taskStats.regular.paused,
                            window.departmentData.taskStats.regular.completed
                        ],
                        backgroundColor: colors.progress,
                        borderRadius: 6,
                        borderSkipped: false
                    },
                    {
                        label: 'مهام القوالب',
                        data: [
                            window.departmentData.taskStats.template.new,
                            window.departmentData.taskStats.template.in_progress,
                            window.departmentData.taskStats.template.paused,
                            window.departmentData.taskStats.template.completed
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
});
