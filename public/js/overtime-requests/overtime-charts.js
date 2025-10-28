// Charts for Overtime Requests
document.addEventListener('DOMContentLoaded', function() {
    // Define common chart colors and options
    const chartColors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(255, 205, 86, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(201, 203, 207, 0.7)'
    ];

    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    font: {
                        size: 12,
                        family: 'Cairo, sans-serif'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.7)',
                padding: 10,
                titleFont: {
                    size: 14,
                    family: 'Cairo, sans-serif'
                },
                bodyFont: {
                    size: 13,
                    family: 'Cairo, sans-serif'
                }
            }
        }
    };

    // Add responsive options for different screen sizes
    const responsiveOptions = {
        scales: {
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45,
                    autoSkip: true,
                    maxTicksLimit: 6
                }
            },
            y: {
                ticks: {
                    precision: 0,
                    font: {
                        size: 12
                    }
                }
            }
        }
    };

    // Personal Statistics Chart
    const personalStatsCtx = document.getElementById('personalStatsChart');
    if (personalStatsCtx) {
        const personalData = JSON.parse(personalStatsCtx.getAttribute('data-statistics') || '{}');

        new Chart(personalStatsCtx, {
            type: 'bar',
            data: {
                labels: ['إجمالي الطلبات', 'الطلبات المعتمدة', 'الطلبات المعلقة', 'الطلبات المرفوضة'],
                datasets: [{
                    label: 'عدد الطلبات',
                    data: [
                        personalData.total_requests,
                        personalData.approved_requests,
                        personalData.pending_requests,
                        personalData.total_requests - personalData.approved_requests - personalData.pending_requests
                    ],
                    backgroundColor: [
                        chartColors[0],
                        chartColors[1],
                        chartColors[3],
                        chartColors[2]
                    ],
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                ...responsiveOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'توزيع حالات طلباتي',
                        font: {
                            size: 16,
                            family: 'Cairo, sans-serif'
                        }
                    }
                }
            }
        });
    }

    // Team Statistics Chart
    const teamStatsCtx = document.getElementById('teamStatsChart');
    if (teamStatsCtx) {
        const teamData = JSON.parse(teamStatsCtx.getAttribute('data-statistics') || '{}');

        new Chart(teamStatsCtx, {
            type: 'pie',
            data: {
                labels: ['معتمد', 'معلق', 'مرفوض'],
                datasets: [{
                    data: [
                        teamData.approved_requests,
                        teamData.pending_requests,
                        teamData.total_requests - teamData.approved_requests - teamData.pending_requests
                    ],
                    backgroundColor: [
                        chartColors[1],
                        chartColors[3],
                        chartColors[2]
                    ],
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'توزيع حالات طلبات الفريق',
                        font: {
                            size: 16,
                            family: 'Cairo, sans-serif'
                        }
                    }
                }
            }
        });
    }

    // HR Statistics Charts
    const hrStatsCtx = document.getElementById('hrStatsChart');
    if (hrStatsCtx) {
        const hrData = JSON.parse(hrStatsCtx.getAttribute('data-statistics') || '{}');

        new Chart(hrStatsCtx, {
            type: 'doughnut',
            data: {
                labels: ['معتمد', 'معلق', 'مرفوض'],
                datasets: [{
                    data: [
                        hrData.total_company_requests - hrData.pending_requests - hrData.rejected_requests,
                        hrData.pending_requests,
                        hrData.rejected_requests
                    ],
                    backgroundColor: chartColors.slice(0, 3),
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                ...chartOptions,
                cutout: '50%',
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'توزيع حالات طلبات العمل الإضافي',
                        font: {
                            size: 16,
                            family: 'Cairo, sans-serif'
                        }
                    }
                }
            }
        });
    }

    // Day of Week Chart
    const dayOfWeekCtx = document.getElementById('dayOfWeekChart');
    if (dayOfWeekCtx) {
        // Get chart data from HTML data attribute
        const dayData = JSON.parse(dayOfWeekCtx.getAttribute('data-statistics') || '[]');

        // Map numeric day of week to day name
        const dayNames = {
            1: 'الأحد',
            2: 'الاثنين',
            3: 'الثلاثاء',
            4: 'الأربعاء',
            5: 'الخميس',
            6: 'الجمعة',
            7: 'السبت'
        };

        // Prepare data
        const labels = [];
        const requestData = [];
        const hoursData = [];

        dayData.forEach(day => {
            labels.push(dayNames[day.day_of_week]);
            requestData.push(day.total_requests);
            hoursData.push(day.total_hours);
        });

        new Chart(dayOfWeekCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'عدد الطلبات',
                    data: requestData,
                    backgroundColor: chartColors[0],
                    borderColor: chartColors[0].replace('0.7', '1'),
                    borderWidth: 1,
                    borderRadius: 4
                }, {
                    label: 'إجمالي الساعات',
                    data: hoursData,
                    backgroundColor: chartColors[1],
                    borderColor: chartColors[1].replace('0.7', '1'),
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                ...chartOptions,
                ...responsiveOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'عدد الطلبات',
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'عدد الساعات',
                            font: {
                                family: 'Cairo, sans-serif'
                            }
                        }
                    }
                },
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'توزيع طلبات العمل الإضافي على أيام الأسبوع',
                        font: {
                            size: 16,
                            family: 'Cairo, sans-serif'
                        }
                    }
                }
            }
        });
    }

    // Monthly Trends Chart
    const monthlyTrendsCtx = document.getElementById('monthlyTrendsChart');
    if (monthlyTrendsCtx) {
        // Get chart data from HTML data attribute
        const monthlyData = JSON.parse(monthlyTrendsCtx.getAttribute('data-statistics') || '[]');

        // Map numeric month to month name
        const monthNames = {
            1: 'يناير', 2: 'فبراير', 3: 'مارس', 4: 'أبريل',
            5: 'مايو', 6: 'يونيو', 7: 'يوليو', 8: 'أغسطس',
            9: 'سبتمبر', 10: 'أكتوبر', 11: 'نوفمبر', 12: 'ديسمبر'
        };

        // Prepare data
        const labels = [];
        const approvedData = [];
        const rejectedData = [];
        const pendingData = [];

        monthlyData.forEach(trend => {
            labels.push(monthNames[trend.month] + ' ' + trend.year);
            approvedData.push(trend.approved_requests);
            rejectedData.push(trend.rejected_requests);
            pendingData.push(trend.pending_requests);
        });

        new Chart(monthlyTrendsCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'طلبات معتمدة',
                    data: approvedData,
                    backgroundColor: chartColors[1],
                    borderColor: chartColors[1].replace('0.7', '1'),
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 4
                }, {
                    label: 'طلبات مرفوضة',
                    data: rejectedData,
                    backgroundColor: chartColors[2],
                    borderColor: chartColors[2].replace('0.7', '1'),
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 4
                }, {
                    label: 'طلبات معلقة',
                    data: pendingData,
                    backgroundColor: chartColors[3],
                    borderColor: chartColors[3].replace('0.7', '1'),
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'عدد الطلبات'
                        }
                    }
                },
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'اتجاهات طلبات العمل الإضافي عبر الأشهر',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    }

    // Departments Chart
    const deptStatsCtx = document.getElementById('departmentsStatsChart');
    if (deptStatsCtx) {
        // Get chart data from HTML data attribute
        const deptData = JSON.parse(deptStatsCtx.getAttribute('data-statistics') || '[]');

        // Prepare data
        const labels = [];
        const hoursData = [];
        const requestsData = [];

        deptData.forEach(dept => {
            labels.push(dept.department);
            hoursData.push(dept.total_hours);
            requestsData.push(dept.total_requests);
        });

        new Chart(deptStatsCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'إجمالي الساعات',
                    data: hoursData,
                    backgroundColor: chartColors[0],
                    borderColor: chartColors[0].replace('0.7', '1'),
                    borderWidth: 1,
                    borderRadius: 4
                }, {
                    label: 'عدد الطلبات',
                    data: requestsData,
                    backgroundColor: chartColors[2],
                    borderColor: chartColors[2].replace('0.7', '1'),
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                ...chartOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'العدد'
                        }
                    }
                },
                plugins: {
                    ...chartOptions.plugins,
                    title: {
                        display: true,
                        text: 'تحليل أقسام الشركة',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    }
});
