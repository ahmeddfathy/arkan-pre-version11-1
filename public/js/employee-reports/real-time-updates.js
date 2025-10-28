/**
 * التحديثات المباشرة والـ real-time functionality
 */

class RealTimeUpdates {
    constructor() {
        this.updateInterval = null;
        this.activeTasksUpdateInterval = null;
        this.timeIndicatorInterval = null;
        this.employeeId = null;
        this.isToday = false;
    }

    /**
     * تهيئة التحديثات المباشرة
     */
    init(employeeId, isToday = false, hasActiveSessions = false) {
        this.employeeId = employeeId;
        this.isToday = isToday;

        // إضافة مؤشر الوقت الحالي إذا كان اليوم الحالي
        if (this.isToday) {
            this.initTimeIndicator();
        }

        // إضافة مؤشر "مباشر" للتايم لاين
        this.addLiveIndicator();

        // بدء التحديثات إذا كان هناك جلسات نشطة
        if (hasActiveSessions) {
            this.startRealTimeUpdates();
        }

        console.log('🔴 تم تهيئة التحديثات المباشرة للموظف:', employeeId);
    }

    /**
     * إضافة مؤشر "مباشر" للتايم لاين
     */
    addLiveIndicator() {
        if ($('.real-time-timeline-container').length > 0) {
            // إزالة المؤشر السابق إن وجد
            $('.live-indicator').remove();

            $('.real-time-timeline-container').prepend(
                `<div class="live-indicator" style="position: absolute; top: 5px; right: 5px; z-index: 20;">
                    <span class="badge badge-success" style="animation: badge-pulse 2s infinite;">
                        <i class="fas fa-circle" style="font-size: 6px; margin-right: 3px;"></i>مباشر
                    </span>
                </div>`
            );
        }
    }

    /**
     * بدء التحديثات المباشرة
     */
    startRealTimeUpdates() {
        // تحديث كل دقيقة
        this.updateInterval = setInterval(() => {
            this.updateRealTimeTimeline();
        }, 60000);

        // تحديث فوري بعد 5 ثوان إذا كان اليوم الحالي
        if (this.isToday) {
            setTimeout(() => {
                this.updateRealTimeTimeline();
            }, 5000);
        }

        // تحديث الجلسات النشطة كل 30 ثانية
        this.activeTasksUpdateInterval = setInterval(() => {
            this.updateActiveTasksRealTime();
        }, 30000);

        console.log('▶️ بدأت التحديثات المباشرة');
    }

    /**
     * إيقاف التحديثات المباشرة
     */
    stopRealTimeUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }

        if (this.activeTasksUpdateInterval) {
            clearInterval(this.activeTasksUpdateInterval);
            this.activeTasksUpdateInterval = null;
        }

        if (this.timeIndicatorInterval) {
            clearInterval(this.timeIndicatorInterval);
            this.timeIndicatorInterval = null;
        }

        console.log('⏹️ تم إيقاف التحديثات المباشرة');
    }

    /**
     * تحديث Timeline في الوقت الحقيقي
     */
    updateRealTimeTimeline() {
        if (!this.employeeId) return;

        const currentUrl = window.location.href;
        if (currentUrl.indexOf(`employee_id=${this.employeeId}`) === -1) return;

        $.get(currentUrl)
            .done((data) => {
                // تحديث الـ Timeline الحقيقي
                const newTimelineContent = $(data).find('.real-time-timeline-container');
                if (newTimelineContent.length > 0) {
                    $('.real-time-timeline-container').replaceWith(newTimelineContent);

                    // إضافة مؤشر "مباشر" مرة أخرى
                    this.addLiveIndicator();

                    // تحديث Timeline Manager
                    if (window.timelineManager) {
                        window.timelineManager.refreshAfterUpdate();
                    }
                }

                // تحديث إحصائيات جلسات العمل
                const newStatsContent = $(data).find('.card.border-primary .card-body .row').first();
                if (newStatsContent.length > 0) {
                    $('.card.border-primary .card-body .row').first().replaceWith(newStatsContent);
                }

                // تحديث قائمة Time Logs
                const newTimeLogsTable = $(data).find('.table');
                if (newTimeLogsTable.length > 0) {
                    $('.table').replaceWith(newTimeLogsTable);
                }

                // إشعار بالتحديث
                this.showUpdateNotification('success', '🔄 تم تحديث البيانات المباشرة');

                console.log('✅ تم تحديث Timeline الحقيقي بنجاح');
            })
            .fail(() => {
                console.error('❌ فشل في تحديث البيانات المباشرة');
                this.showUpdateNotification('error', 'فشل في تحديث البيانات', 'خطأ في الشبكة');
            });
    }

    /**
     * عرض إشعار التحديث
     */
    showUpdateNotification(type, message, title = '') {
        if (typeof toastr !== 'undefined') {
            const options = {
                timeOut: 2000,
                positionClass: 'toast-bottom-right',
                showMethod: 'fadeIn',
                hideMethod: 'fadeOut'
            };

            if (type === 'error') {
                options.timeOut = 5000;
                options.closeButton = true;
            }

            toastr[type](message, title, options);
        }
    }

    /**
     * تحديث الجلسات النشطة في الوقت الفعلي
     */
    updateActiveTasksRealTime() {
        $('.real-time-task').each(function() {
            const $task = $(this);
            const isActive = $task.data('is-active') === 'true';

            if (isActive) {
                const startTime = parseInt($task.data('start-time')) * 1000; // تحويل إلى milliseconds
                const now = new Date().getTime();
                const workDayStart = new Date().setHours(8, 0, 0, 0); // 8:00 AM
                const workDayMinutes = 10 * 60;

                const currentDurationSeconds = (now - startTime) / 1000;
                const currentDuration = Math.floor(currentDurationSeconds / 60);

                const startMinutesFromWorkStart = Math.max(0, (startTime - workDayStart) / (1000 * 60));
                const currentPosition = startMinutesFromWorkStart + (currentDurationSeconds / 60);

                const startPosition = (startMinutesFromWorkStart / workDayMinutes) * 100;
                const currentPositionPercent = (currentPosition / workDayMinutes) * 100;
                const newWidth = Math.max(0.5, Math.min(currentPositionPercent - startPosition, 100 - startPosition));

                // تحديث العرض بسلاسة
                $task.stop().animate({
                    width: newWidth + '%'
                }, 800, 'easeOutQuad');

                // تحديث النص الداخلي
                const hours = Math.floor(currentDuration / 60);
                const minutes = currentDuration % 60;
                let durationText = '';
                if (hours > 0) durationText += hours + 'h ';
                if (minutes > 0) durationText += minutes + 'm';
                if (durationText === '') durationText = '0m';

                $task.find('.task-duration').text(durationText);

                // تحديث tooltip
                const currentTitle = $task.attr('title');
                if (currentTitle) {
                    const newTooltip = currentTitle.replace(/⏱️ \d+h \d+m/, '⏱️ ' + durationText);
                    $task.attr('title', newTooltip);
                }

                console.log('Updated active task width:', newWidth + '%', 'Duration:', durationText);
            }
        });
    }

    /**
     * تهيئة مؤشر الوقت الحالي
     */
    initTimeIndicator() {
        // إضافة مؤشر الوقت عند تحميل الصفحة
        if ($('.timeline-body').length > 0) {
            window.timelineManager.addCurrentTimeIndicator();

            // تحديث مؤشر الوقت كل دقيقة
            this.timeIndicatorInterval = setInterval(() => {
                window.timelineManager.updateTimeIndicator();
                this.updateActiveTasksRealTime();
            }, 60000);
        }
    }

    /**
     * إظهار إشعار للجلسات النشطة
     */
    showActiveSessionsNotification(activeSessionsCount, selectedDate) {
        const storageKey = `activeSessionNotified_${selectedDate}`;

        if (!sessionStorage.getItem(storageKey)) {
            const message = `🔴 يوجد ${activeSessionsCount} جلسة عمل نشطة - يتم التحديث كل 30 ثانية`;

            if (typeof toastr !== 'undefined') {
                toastr.success(message, 'المخطط الزمني المباشر', {
                    timeOut: 8000,
                    progressBar: true
                });
            } else {
                console.log(`🔴 Real-time tracking active: ${activeSessionsCount} sessions`);
            }

            sessionStorage.setItem(storageKey, 'true');
        }
    }
}

// إنشاء instance عمومي
window.realTimeUpdates = new RealTimeUpdates();
