// Chart functionality for absence requests

document.addEventListener('DOMContentLoaded', function() {
    console.log('statistics.js: DOMContentLoaded fired');

    // رسم المخططات البيانية
    if (!window.absenceStatistics) {
        console.error('statistics.js: window.absenceStatistics is not defined!');
        return;
    }

    console.log('statistics.js: Creating charts with data:', window.absenceStatistics);

    // مخطط حالة الطلبات الشخصية
    if (document.getElementById('personalStatusChart')) {
        console.log('statistics.js: Creating personalStatusChart');
        try {
            const personalStatusCtx = document.getElementById('personalStatusChart').getContext('2d');
            new Chart(personalStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['موافق عليه', 'مرفوض', 'معلق'],
                    datasets: [{
                        data: [
                            window.absenceStatistics.personal.approved,
                            window.absenceStatistics.personal.rejected,
                            window.absenceStatistics.personal.pending
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: true,
                            labels: {
                                font: {
                                    family: 'Cairo, sans-serif'
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'حالة طلباتي',
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 16
                            }
                        }
                    }
                }
            });
            console.log('statistics.js: personalStatusChart created successfully');
        } catch (error) {
            console.error('Error creating personalStatusChart:', error);
        }
    } else {
        console.warn('statistics.js: personalStatusChart element not found');
    }

    // مخطط اتجاه الطلبات الشخصية
    if (document.getElementById('personalTrendChart')) {
        console.log('statistics.js: Creating personalTrendChart');
        try {
            const personalTrendCtx = document.getElementById('personalTrendChart').getContext('2d');
            new Chart(personalTrendCtx, {
                type: 'bar',
                data: {
                    labels: ['إجمالي الطلبات', 'أيام الاجازة'],
                    datasets: [{
                        label: 'عدد الطلبات/الأيام',
                        data: [
                            window.absenceStatistics.personal.total,
                            window.absenceStatistics.personal.days
                        ],
                        backgroundColor: [
                            'rgba(13, 110, 253, 0.7)',
                            'rgba(108, 117, 125, 0.7)'
                        ],
                        borderColor: [
                            'rgba(13, 110, 253, 1)',
                            'rgba(108, 117, 125, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'إحصائيات الطلبات',
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 16
                            }
                        }
                    }
                }
            });
            console.log('statistics.js: personalTrendChart created successfully');
        } catch (error) {
            console.error('Error creating personalTrendChart:', error);
        }
    } else {
        console.warn('statistics.js: personalTrendChart element not found');
    }

    // مخططات الفريق
    if (window.absenceStatistics.team) {
        // مخطط حالة طلبات الفريق
        if (document.getElementById('teamStatusChart')) {
            const teamStatusCtx = document.getElementById('teamStatusChart').getContext('2d');
            new Chart(teamStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['موافق عليه', 'مرفوض', 'معلق'],
                    datasets: [{
                        data: [
                            window.absenceStatistics.team.approved,
                            window.absenceStatistics.team.rejected,
                            window.absenceStatistics.team.pending
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: true,
                            labels: {
                                font: {
                                    family: 'Cairo, sans-serif'
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'حالة طلبات الفريق',
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 16
                            }
                        }
                    }
                }
            });
        }

        // مخطط إحصائيات الفريق
        if (document.getElementById('teamMembersChart')) {
            const teamMembersCtx = document.getElementById('teamMembersChart').getContext('2d');
            new Chart(teamMembersCtx, {
                type: 'bar',
                data: {
                    labels: ['إجمالي الطلبات', 'أيام الاجازة', 'تجاوزوا الحد'],
                    datasets: [{
                        label: 'عدد الطلبات/الأيام/الموظفين',
                        data: [
                            window.absenceStatistics.team.total,
                            window.absenceStatistics.team.days,
                            window.absenceStatistics.team.exceeded
                        ],
                        backgroundColor: [
                            'rgba(13, 110, 253, 0.7)',
                            'rgba(108, 117, 125, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderColor: [
                            'rgba(13, 110, 253, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'إحصائيات الفريق',
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 16
                            }
                        }
                    }
                }
            });
        }
    }

    // مخططات HR
    if (window.absenceStatistics.hr) {
        // مخطط حالة طلبات الشركة
        if (document.getElementById('hrStatusChart')) {
            const hrStatusCtx = document.getElementById('hrStatusChart').getContext('2d');
            new Chart(hrStatusCtx, {
                type: 'pie',
                data: {
                    labels: ['موافق عليه', 'مرفوض', 'معلق'],
                    datasets: [{
                        data: [
                            window.absenceStatistics.hr.approved,
                            window.absenceStatistics.hr.rejected,
                            window.absenceStatistics.hr.pending
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.7)',
                            'rgba(220, 53, 69, 0.7)',
                            'rgba(255, 193, 7, 0.7)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: true,
                            labels: {
                                font: {
                                    family: 'Cairo, sans-serif'
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'حالة طلبات الشركة',
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 16
                            }
                        }
                    }
                }
            });
        }

        // مخطط إحصائيات الشركة
        if (document.getElementById('hrDepartmentsChart')) {
            const hrDepartmentsCtx = document.getElementById('hrDepartmentsChart').getContext('2d');
            new Chart(hrDepartmentsCtx, {
                type: 'bar',
                data: {
                    labels: ['إجمالي الطلبات', 'أيام الاجازة', 'تجاوزوا الحد'],
                    datasets: [{
                        label: 'عدد الطلبات/الأيام/الموظفين',
                        data: [
                            window.absenceStatistics.hr.total,
                            window.absenceStatistics.hr.days,
                            window.absenceStatistics.hr.exceeded
                        ],
                        backgroundColor: [
                            'rgba(13, 110, 253, 0.7)',
                            'rgba(108, 117, 125, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderColor: [
                            'rgba(13, 110, 253, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'إحصائيات الشركة',
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 16
                            }
                        }
                    }
                }
            });
        }

        // مخطط الاتجاه الشهري
        if (document.getElementById('hrMonthlyTrendChart')) {
            const hrMonthlyTrendCtx = document.getElementById('hrMonthlyTrendChart').getContext('2d');
            new Chart(hrMonthlyTrendCtx, {
                type: 'line',
                data: {
                    labels: ['الشهر الحالي'],
                    datasets: [{
                        label: 'طلبات الاجازة',
                        data: [window.absenceStatistics.hr.total],
                        borderColor: 'rgba(13, 110, 253, 1)',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'اتجاه الطلبات',
                            font: {
                                family: 'Cairo, sans-serif',
                                size: 16
                            }
                        }
                    }
                }
            });
        }
    }
});
