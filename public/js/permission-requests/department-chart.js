// Initialize Department Chart
function initializeDepartmentChart(statistics) {
    const hrDepartmentMinutesCtx = document.getElementById('hrDepartmentMinutesChart')?.getContext('2d');
    if (!hrDepartmentMinutesCtx) return;

    if (statistics.hr.departments?.length > 0) {
        const departments = statistics.hr.departments;
        const labels = departments.map(dept => dept.name);
        const minutes = departments.map(dept => dept.total_minutes);
        const avgMinutes = departments.map(dept => dept.avg_minutes);
        const requestCounts = departments.map(dept => dept.request_count);

        new Chart(hrDepartmentMinutesCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'إجمالي الدقائق',
                        data: minutes,
                        backgroundColor: window.chartColors.approved.bg,
                        borderColor: window.chartColors.approved.border,
                        borderWidth: 1,
                        yAxisID: 'minutes'
                    },
                    {
                        label: 'متوسط الدقائق للموظف',
                        data: avgMinutes,
                        backgroundColor: window.chartColors.onTime.bg,
                        borderColor: window.chartColors.onTime.border,
                        borderWidth: 1,
                        yAxisID: 'minutes'
                    },
                    {
                        label: 'عدد الطلبات',
                        data: requestCounts,
                        backgroundColor: 'rgba(153, 102, 255, 0.7)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        yAxisID: 'requests'
                    }
                ]
            },
            options: {
                ...window.chartOptions.commonBarOptions,
                scales: {
                    minutes: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'الدقائق'
                        }
                    },
                    requests: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'عدد الطلبات'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterLabel: function(context) {
                                const dept = departments[context.dataIndex];
                                return [
                                    `عدد الموظفين: ${dept.employee_count}`,
                                    `عدد الطلبات: ${dept.request_count}`,
                                    `عادوا في الوقت: ${dept.on_time_returns}`,
                                    `تأخروا عن العودة: ${dept.late_returns}`
                                ];
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'إحصائيات الاستئذان حسب الأقسام الإدارية',
                        padding: {
                            top: 10,
                            bottom: 20
                        },
                        font: {
                            size: 14
                        }
                    }
                }
            }
        });
    } else {
        new Chart(hrDepartmentMinutesCtx, {
            type: 'bar',
            data: {
                labels: ['لا توجد بيانات'],
                datasets: [{
                    label: 'الدقائق',
                    data: [statistics.hr.total_minutes],
                    backgroundColor: window.chartColors.approved.bg,
                    borderColor: window.chartColors.approved.border,
                    borderWidth: 1
                }]
            },
            options: window.chartOptions.commonBarOptions
        });
    }
}

// Initialize Daily Trend Chart
function initializeDailyTrendChart(statistics) {
    const hrDailyTrendCtx = document.getElementById('hrDailyTrendChart')?.getContext('2d');
    if (!hrDailyTrendCtx) return;

    if (statistics.hr.daily_stats?.length > 0) {
        const dailyStats = statistics.hr.daily_stats;
        const dates = dailyStats.map(item => item.date);
        const dailyRequests = dailyStats.map(item => item.total_requests);
        const dailyMinutes = dailyStats.map(item => item.total_minutes);

        new Chart(hrDailyTrendCtx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: 'عدد الطلبات',
                        data: dailyRequests,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'requests'
                    },
                    {
                        label: 'إجمالي الدقائق',
                        data: dailyMinutes,
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        yAxisID: 'minutes'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    requests: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'عدد الطلبات'
                        }
                    },
                    minutes: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'إجمالي الدقائق'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    } else {
        new Chart(hrDailyTrendCtx, {
            type: 'bar',
            data: {
                labels: ['لا توجد بيانات كافية'],
                datasets: [{
                    label: 'الطلبات',
                    data: [0],
                    backgroundColor: window.chartColors.approved.bg,
                    borderColor: window.chartColors.approved.border,
                    borderWidth: 1
                }]
            },
            options: window.chartOptions.commonBarOptions
        });
    }
}

// Initialize Busiest Days Chart
function initializeBusiestDaysChart(statistics) {
    const hrBusiestDaysCtx = document.getElementById('hrBusiestDaysChart')?.getContext('2d');
    if (!hrBusiestDaysCtx) return;

    if (statistics.hr.busiest_days?.length > 0) {
        const busiestDays = statistics.hr.busiest_days;

        // تعريب أسماء أيام الأسبوع
        const arabicDayNames = {
            'Sunday': 'الأحد',
            'Monday': 'الإثنين',
            'Tuesday': 'الثلاثاء',
            'Wednesday': 'الأربعاء',
            'Thursday': 'الخميس',
            'Friday': 'الجمعة',
            'Saturday': 'السبت'
        };

        const dayNames = busiestDays.map(day => arabicDayNames[day.day_name] || day.day_name);
        const dayRequests = busiestDays.map(day => day.total_requests);
        const dayMinutes = busiestDays.map(day => day.total_minutes);

        new Chart(hrBusiestDaysCtx, {
            type: 'bar',
            data: {
                labels: dayNames,
                datasets: [
                    {
                        label: 'عدد الطلبات',
                        data: dayRequests,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'requests'
                    },
                    {
                        label: 'إجمالي الدقائق',
                        data: dayMinutes,
                        backgroundColor: 'rgba(255, 159, 64, 0.7)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1,
                        yAxisID: 'minutes'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    requests: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'عدد الطلبات'
                        }
                    },
                    minutes: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'إجمالي الدقائق'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    } else {
        new Chart(hrBusiestDaysCtx, {
            type: 'bar',
            data: {
                labels: ['لا توجد بيانات كافية'],
                datasets: [{
                    label: 'الطلبات',
                    data: [0],
                    backgroundColor: window.chartColors.approved.bg,
                    borderColor: window.chartColors.approved.border,
                    borderWidth: 1
                }]
            },
            options: window.chartOptions.commonBarOptions
        });
    }
}

// Initialize Busiest Hours Chart
function initializeBusiestHoursChart(statistics) {
    const hrBusiestHoursCtx = document.getElementById('hrBusiestHoursChart')?.getContext('2d');
    if (!hrBusiestHoursCtx) return;

    if (statistics.hr.busiest_hours?.length > 0) {
        const busiestHours = statistics.hr.busiest_hours;
        const hourLabels = busiestHours.map(hour => hour.hour_formatted);
        const hourRequests = busiestHours.map(hour => hour.total_requests);
        const hourMinutes = busiestHours.map(hour => hour.total_minutes);

        new Chart(hrBusiestHoursCtx, {
            type: 'bar',
            data: {
                labels: hourLabels,
                datasets: [
                    {
                        label: 'عدد الطلبات',
                        data: hourRequests,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        yAxisID: 'requests'
                    },
                    {
                        label: 'إجمالي الدقائق',
                        data: hourMinutes,
                        backgroundColor: 'rgba(153, 102, 255, 0.7)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        yAxisID: 'minutes'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    requests: {
                        type: 'linear',
                        position: 'left',
                        title: {
                            display: true,
                            text: 'عدد الطلبات'
                        }
                    },
                    minutes: {
                        type: 'linear',
                        position: 'right',
                        title: {
                            display: true,
                            text: 'إجمالي الدقائق'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    } else {
        new Chart(hrBusiestHoursCtx, {
            type: 'bar',
            data: {
                labels: ['لا توجد بيانات كافية'],
                datasets: [{
                    label: 'الطلبات',
                    data: [0],
                    backgroundColor: window.chartColors.approved.bg,
                    borderColor: window.chartColors.approved.border,
                    borderWidth: 1
                }]
            },
            options: window.chartOptions.commonBarOptions
        });
    }
}
