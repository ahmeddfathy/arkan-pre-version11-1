/**
 * Charts Initialization JavaScript
 * تهيئة وإعداد الرسوم البيانية لعرض إحصائيات الموظفين
 */

// Charts initialization
document.addEventListener('DOMContentLoaded', function() {
    // استخدام المتغير العام المعرف في ملف index.blade.php
    if (!window.employeesData || window.employeesData.length === 0) {
        console.warn('بيانات الموظفين غير متوفرة أو فارغة');
        return;
    }

    // استخدام إجمالي البيانات من totalStats
    const totalStats = window.totalStats || {
        total_attendance_days: 0,
        total_absence_days: 0,
        total_permissions: 0,
        departments_stats: {}
    };

    const employeesData = window.employeesData;

    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart');
    if (!attendanceCtx) {
        console.warn('عنصر attendanceChart غير موجود في الصفحة');
        return;
    }

    new Chart(attendanceCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['أيام الحضور', 'أيام الغياب'],
            datasets: [{
                data: [
                    totalStats.total_attendance_days,
                    totalStats.total_absence_days
                ],
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw;
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Leaves Chart
    const leavesCtx = document.getElementById('leavesChart').getContext('2d');

    new Chart(leavesCtx, {
        type: 'bar',
        data: {
            labels: ['الإجازات المأخوذة', 'الإجازات المتبقية', 'الأذونات'],
            datasets: [{
                label: 'عدد الأيام',
                data: [
                    totalStats.total_taken_leaves || 0,
                    totalStats.total_remaining_leaves || 0,
                    totalStats.total_permissions || 0
                ],
                backgroundColor: ['#17a2b8', '#28a745', '#ffc107'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
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
                }
            }
        }
    });

    // Time Chart (Delays and Overtime)
    const timeCtx = document.getElementById('timeChart').getContext('2d');

    // إعداد المخطط البياني حسب الأقسام
    const departments = Object.keys(totalStats.departments_stats || {});

    new Chart(timeCtx, {
        type: 'bar',
        data: {
            labels: departments,
            datasets: [
                {
                    label: 'أيام الحضور',
                    data: departments.map(dept => totalStats.departments_stats[dept].attendance),
                    backgroundColor: '#28a745',
                    borderWidth: 1
                },
                {
                    label: 'أيام الغياب',
                    data: departments.map(dept => totalStats.departments_stats[dept].absence),
                    backgroundColor: '#dc3545',
                    borderWidth: 1
                },
                {
                    label: 'الأذونات',
                    data: departments.map(dept => totalStats.departments_stats[dept].permissions),
                    backgroundColor: '#ffc107',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    ticks: {
                        autoSkip: true,
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
});
