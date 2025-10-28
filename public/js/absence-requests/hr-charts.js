// HR advanced chart functionality for absence requests

document.addEventListener('DOMContentLoaded', function() {
    if (!window.absenceStatistics || !window.absenceStatistics.hr || !window.absenceStatistics.hr.charts_data) return;

    // HR Detailed Statistics Charts
    // Monthly Analysis Chart
    if (document.getElementById('hrMonthlyChart')) {
        const monthlyData = window.absenceStatistics.hr.charts_data.monthly_stats;
        const monthlyCtx = document.getElementById('hrMonthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'إجمالي الطلبات',
                    data: monthlyData.map(item => item.total_requests),
                    borderColor: 'rgba(13, 110, 253, 1)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true
                }, {
                    label: 'الطلبات المقبولة',
                    data: monthlyData.map(item => item.approved_count),
                    borderColor: 'rgba(40, 167, 69, 1)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'تحليل الاجازات الشهري',
                        font: { family: 'Cairo, sans-serif', size: 16 }
                    }
                }
            }
        });
    }

    // Department Statistics Chart
    if (document.getElementById('hrDepartmentChart')) {
        const departmentData = window.absenceStatistics.hr.charts_data.department_stats;
        const departmentCtx = document.getElementById('hrDepartmentChart').getContext('2d');
        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: departmentData.map(item => item.department),
                datasets: [{
                    label: 'معدل الموافقة (%)',
                    data: departmentData.map(item => item.approval_rate),
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
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
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'معدل الاجازات حسب القسم',
                        font: {
                            family: 'Cairo, sans-serif',
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const department = departmentData[context.dataIndex];
                                return [
                                    `معدل الموافقة: ${department.approval_rate}%`,
                                    `عدد الموظفين: ${department.employee_count}`,
                                    `عدد الطلبات: ${department.request_count}`,
                                    `الطلبات الموافق عليها: ${department.approved_count}`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }

    // Reasons Chart
    if (document.getElementById('hrReasonsChart')) {
        const reasonsData = window.absenceStatistics.hr.charts_data.reasons_stats;
        const reasonsCtx = document.getElementById('hrReasonsChart').getContext('2d');
        new Chart(reasonsCtx, {
            type: 'pie',
            data: {
                labels: reasonsData.map(item => item.reason),
                datasets: [{
                    data: reasonsData.map(item => item.count),
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: { font: { family: 'Cairo, sans-serif' } }
                    },
                    title: {
                        display: true,
                        text: 'أسباب الاجازات',
                        font: { family: 'Cairo, sans-serif', size: 16 }
                    }
                }
            }
        });
    }

    // Weekday Statistics Chart
    if (document.getElementById('hrWeekdayChart')) {
        const weekdayData = window.absenceStatistics.hr.charts_data.weekday_stats;
        const weekdayCtx = document.getElementById('hrWeekdayChart').getContext('2d');
        new Chart(weekdayCtx, {
            type: 'bar',
            data: {
                labels: weekdayData.map(item => item.weekday),
                datasets: [{
                    label: 'عدد الطلبات',
                    data: weekdayData.map(item => item.count),
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'توزيع الاجازات على أيام الأسبوع',
                        font: { family: 'Cairo, sans-serif', size: 16 }
                    }
                }
            }
        });
    }

    // Age Group Statistics Chart
    if (document.getElementById('hrAgeGroupChart')) {
        const ageData = window.absenceStatistics.hr.charts_data.age_group_stats;
        const ageCtx = document.getElementById('hrAgeGroupChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ageData.map(item => item.age_group),
                datasets: [{
                    label: 'معدل الموافقة (%)',
                    data: ageData.map(item => item.approval_rate),
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'معدل الموافقة حسب الفئة العمرية',
                        font: { family: 'Cairo, sans-serif', size: 16 }
                    }
                }
            }
        });
    }
});
