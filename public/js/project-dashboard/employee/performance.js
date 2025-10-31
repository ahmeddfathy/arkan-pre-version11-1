document.addEventListener('DOMContentLoaded', function () {
    // Real-time Timer for Employee Performance
    function startEmployeeTimer(el) {
        if (!el) return;
        const initialMinutes = parseInt(el.getAttribute('data-initial-minutes') || '0', 10);
        const activeCount = parseInt(el.getAttribute('data-active-count') || '0', 10);
        let totalSeconds = initialMinutes * 60;
        // ✅ ثانية واحدة فقط إذا كان هناك مهام نشطة (بغض النظر عن العدد)
        const tickPerSecond = activeCount > 0 ? 1 : 0;

        function format(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        // Update initial display
        const displayText = format(totalSeconds);
        const realTimeIndicator = el.querySelector('.real-time-indicator');
        if (realTimeIndicator) {
            el.innerHTML = displayText + el.innerHTML.substring(el.innerHTML.indexOf('<span'));
        } else {
            el.textContent = displayText;
        }

        if (tickPerSecond > 0) {
            setInterval(() => {
                totalSeconds += tickPerSecond;
                const newDisplayText = format(totalSeconds);
                if (realTimeIndicator) {
                    el.innerHTML = newDisplayText + el.innerHTML.substring(el.innerHTML.indexOf('<span'));
                } else {
                    el.textContent = newDisplayText;
                }
            }, 1000);
        }
    }

    // ✅ إضافة Page Visibility API لحل مشكلة توقف التايمر في صفحة الموظفين
    function initializeEmployeePageVisibilityHandler() {
        // الكشف عن تغيير حالة الصفحة (نشطة/غير نشطة)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // المستخدم عاد للتاب - نحديث جميع التايمرات
                syncAllEmployeeTimersWithRealTime();
            }
        });

        // تحديث التايمرات كل 10 ثوان كـ backup عندما التاب نشط
        setInterval(function() {
            if (!document.hidden) {
                syncAllEmployeeTimersWithRealTime();
            }
        }, 10000);

        // تحديث التايمرات عند النقر على أي مكان في الصفحة
        document.addEventListener('click', function() {
            if (!document.hidden) {
                setTimeout(() => {
                    syncAllEmployeeTimersWithRealTime();
                }, 100);
            }
        });
    }

    function syncAllEmployeeTimersWithRealTime() {
        // ✅ تحديث جميع التايمرات بالوقت الفعلي
        const timerElements = [
            'employee-actual-timer',
            'employee-time-hours',
            'employee-efficiency-timer',
            'employee-analytics-timer'
        ];

        timerElements.forEach(timerId => {
            const timerElement = document.getElementById(timerId);
            if (timerElement) {
                const initialMinutes = parseInt(timerElement.getAttribute('data-initial-minutes') || '0', 10);
                const activeCount = parseInt(timerElement.getAttribute('data-active-count') || '0', 10);
                const startedAt = timerElement.getAttribute('data-started-at');

                if (activeCount > 0 && startedAt && startedAt !== 'null' && startedAt !== '') {
                    // ✅ حساب الوقت الفعلي من البداية
                    const startTimestamp = parseInt(startedAt);
                    if (!isNaN(startTimestamp)) {
                        const now = new Date().getTime();
                        const elapsedSeconds = Math.floor((now - startTimestamp) / 1000);
                        const totalSeconds = (initialMinutes * 60) + elapsedSeconds;

                        function format(seconds) {
                            const h = Math.floor(seconds / 3600);
                            const m = Math.floor((seconds % 3600) / 60);
                            const s = seconds % 60;
                            return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                        }

                        const newDisplayText = format(totalSeconds);
                        const realTimeIndicator = timerElement.querySelector('.real-time-indicator');
                        if (realTimeIndicator) {
                            timerElement.innerHTML = newDisplayText + timerElement.innerHTML.substring(timerElement.innerHTML.indexOf('<span'));
                        } else {
                            timerElement.textContent = newDisplayText;
                        }
                    }
                }
            }
        });
    }

    startEmployeeTimer(document.getElementById('employee-actual-timer'));
    startEmployeeTimer(document.getElementById('employee-time-hours'));
    startEmployeeTimer(document.getElementById('employee-efficiency-timer'));
    startEmployeeTimer(document.getElementById('employee-analytics-timer'));

    // ✅ تهيئة Page Visibility Handler
    initializeEmployeePageVisibilityHandler();

    // ✅ تفعيل تأثيرات Modal للمشاريع
    const projectModal = document.getElementById('projectCompletionModal');
    if (projectModal) {
        projectModal.addEventListener('show.bs.modal', function () {
            // إضافة تأثير loading للجدول
            const tableBody = projectModal.querySelector('tbody');
            if (tableBody) {
                tableBody.style.opacity = '0.5';
                setTimeout(() => {
                    tableBody.style.opacity = '1';
                }, 300);
            }
        });

        // تأثير عند إغلاق الموديل
        projectModal.addEventListener('hide.bs.modal', function () {
            const modalDialog = this.querySelector('.modal-dialog');
            modalDialog.style.transform = 'scale(0.95)';
            modalDialog.style.opacity = '0.8';
        });

        // إعادة تعيين التأثيرات عند الإغلاق الكامل
        projectModal.addEventListener('hidden.bs.modal', function () {
            const modalDialog = this.querySelector('.modal-dialog');
            modalDialog.style.transform = '';
            modalDialog.style.opacity = '';
        });
    }

    // Charts and other functionality below...
    // Chart Colors
    const colors = {
        inProgress: '#3498db',
        waitingForm: '#f39c12',
        waitingQuestions: '#3498db',
        waitingClient: '#e67e22',
        waitingCall: '#9b59b6',
        paused: '#e74c3c',
        draftDelivery: '#f093fb',
        finalDelivery: '#00f2fe',
        template: '#8b5cf6',
        completed: '#10b981'
    };

    // Project Status Chart (Pie Chart)
    const projectCtx = document.getElementById('projectStatusChart');
    if (projectCtx) {
        new Chart(projectCtx, {
            type: 'doughnut',
            data: {
                labels: ['جاري', 'واقف (نموذج)', 'واقف (أسئلة)', 'واقف (عميل)', 'واقف (مكالمة)', 'موقوف', 'تسليم مسودة', 'تسليم نهائي'],
                datasets: [{
                    data: [
                        window.performanceData.projectStats.in_progress || 0,
                        window.performanceData.projectStats.waiting_form || 0,
                        window.performanceData.projectStats.waiting_questions || 0,
                        window.performanceData.projectStats.waiting_client || 0,
                        window.performanceData.projectStats.waiting_call || 0,
                        window.performanceData.projectStats.paused || 0,
                        window.performanceData.projectStats.draft_delivery || 0,
                        window.performanceData.projectStats.final_delivery || 0
                    ],
                    backgroundColor: [
                        colors.inProgress,
                        colors.waitingForm,
                        colors.waitingQuestions,
                        colors.waitingClient,
                        colors.waitingCall,
                        colors.paused,
                        colors.draftDelivery,
                        colors.finalDelivery
                    ],
                    borderWidth: 0,
                    cutout: '60%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                onHover: (event, activeElements) => {
                    // Disable hover effects
                    event.native.target.style.cursor = 'default';
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
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
                labels: ['جديدة', 'قيد التنفيذ', 'متوقفة', 'مكتملة', 'منقولة'],
                datasets: [
                    {
                        label: 'المهام العادية',
                        data: [
                            window.performanceData.taskStats.regular.new,
                            window.performanceData.taskStats.regular.in_progress,
                            window.performanceData.taskStats.regular.paused,
                            window.performanceData.taskStats.regular.completed,
                            window.performanceData.taskStats.regular.transferred_out || 0
                        ],
                        backgroundColor: colors.progress,
                        borderRadius: 6,
                        borderSkipped: false
                    },
                    {
                        label: 'مهام القوالب',
                        data: [
                            window.performanceData.taskStats.template.new,
                            window.performanceData.taskStats.template.in_progress,
                            window.performanceData.taskStats.template.paused,
                            window.performanceData.taskStats.template.completed,
                            window.performanceData.taskStats.template.transferred_out || 0
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
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                onHover: (event, activeElements) => {
                    // Disable hover effects
                    event.native.target.style.cursor = 'default';
                },
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
                    },
                    tooltip: {
                        enabled: false
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

    // Monthly Performance Chart (Line Chart)
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: window.performanceData.monthlyStats.map(stat => stat.month),
                datasets: [{
                    label: 'المهام المكتملة',
                    data: window.performanceData.monthlyStats.map(stat => stat.completed_tasks),
                    borderColor: colors.completed,
                    backgroundColor: colors.completed + '20',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.completed,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                onHover: (event, activeElements) => {
                    // Disable hover effects
                    event.native.target.style.cursor = 'default';
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
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
                        window.performanceData.timeStats.estimated_hours,
                        window.performanceData.timeStats.spent_hours
                    ],
                    backgroundColor: [colors.new, colors.completed],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                onHover: (event, activeElements) => {
                    // Disable hover effects
                    event.native.target.style.cursor = 'default';
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
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
