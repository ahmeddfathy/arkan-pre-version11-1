
class EmployeeReportCharts {
    constructor(options = {}) {
        this.options = Object.assign({
            taskStatusChartId: 'taskStatusChart',
            timeDistributionChartId: 'timeDistributionChart'
        }, options);

        this.taskStatusChart = null;
        this.timeDistributionChart = null;
    }


    createTaskStatusChart(completedTasks, inProgressTasks, pausedTasks, newTasks, cancelledTasks) {
        const ctx = document.getElementById(this.options.taskStatusChartId).getContext('2d');

        this.taskStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['مكتملة', 'قيد التنفيذ', 'متوقفة', 'جديدة', 'ملغاة'],
                datasets: [{
                    data: [
                        completedTasks,
                        inProgressTasks,
                        pausedTasks,
                        newTasks,
                        cancelledTasks
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#4BAAD4',
                        '#ffc107',
                        '#6c757d',
                        '#dc3545'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    }
                }
            }
        });

        return this.taskStatusChart;
    }


    createTimeDistributionChart(dateLabels, timeData) {
        const ctx = document.getElementById(this.options.timeDistributionChartId).getContext('2d');

        this.timeDistributionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dateLabels,
                datasets: [{
                    label: 'الساعات المسجلة',
                    data: timeData,
                    backgroundColor: 'rgba(75, 170, 212, 0.7)',
                    borderColor: 'rgba(75, 170, 212, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'الساعات'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'التاريخ'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        return this.timeDistributionChart;
    }


    updateTaskStatusChart(completedTasks, inProgressTasks, pausedTasks, newTasks, cancelledTasks) {
        if (!this.taskStatusChart) return;

        this.taskStatusChart.data.datasets[0].data = [
            completedTasks,
            inProgressTasks,
            pausedTasks,
            newTasks,
            cancelledTasks
        ];

        this.taskStatusChart.update();
    }


    updateTimeDistributionChart(dateLabels, timeData) {
        if (!this.timeDistributionChart) return;

        this.timeDistributionChart.data.labels = dateLabels;
        this.timeDistributionChart.data.datasets[0].data = timeData;

        this.timeDistributionChart.update();
    }
}
