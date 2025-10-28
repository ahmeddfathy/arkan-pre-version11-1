/**
 * الملف الرئيسي لتقارير الموظفين
 * يجمع جميع الوظائف ويدير التفاعل بينها
 */

class EmployeeReportsMain {
    constructor() {
        this.config = {
            employeeId: null,
            selectedDate: null,
            hasActiveSessions: false,
            isToday: false,
            shiftData: null,
            taskData: []
        };
        this.initialized = false;
    }

    /**
     * تهيئة التطبيق الرئيسي
     */
    init(config = {}) {
        this.config = { ...this.config, ...config };

        console.log('🚀 بدء تهيئة تقارير الموظفين:', this.config);

        // تهيئة جميع المكونات
        this.initializeComponents();

        // تهيئة التايم لاين إذا وجد
        if (this.hasTimeline()) {
            this.initializeTimeline();
        }

        // تهيئة التحديثات المباشرة إذا كان هناك جلسات نشطة
        if (this.config.hasActiveSessions) {
            this.initializeRealTimeUpdates();
        }

        // تهيئة الرسوم البيانية إذا وجدت بيانات
        if (this.config.taskData && this.config.taskData.length > 0) {
            this.initializeCharts();
        }

        this.initialized = true;
        console.log('✅ تم تهيئة تقارير الموظفين بنجاح');
    }

    /**
     * تهيئة جميع المكونات الأساسية
     */
    initializeComponents() {
        // تهيئة UI Interactions
        if (window.uiInteractions) {
            window.uiInteractions.init();
        }

        // تهيئة Timeline Manager
        if (window.timelineManager) {
            window.timelineManager.initMinuteBoxes();
            window.timelineManager.initLegacyTimelineEffects();
        }

        // إظهار إشعار للجلسات النشطة
        if (this.config.hasActiveSessions && this.config.selectedDate) {
            this.showActiveSessionsNotification();
        }
    }

    /**
     * التحقق من وجود تايم لاين
     */
    hasTimeline() {
        return $('.real-time-timeline-container').length > 0 || $('.timeline-body').length > 0;
    }

    /**
     * تهيئة التايم لاين
     */
    initializeTimeline() {
        // تهيئة مؤشر الوقت الحالي للتايم لاين القديم
        if ($('.timeline-body').length > 0 && this.config.isToday) {
            if (window.timelineManager) {
                window.timelineManager.addCurrentTimeIndicator();

                // تحديث مؤشر الوقت كل دقيقة
                setInterval(() => {
                    window.timelineManager.updateTimeIndicator();
                    if (window.realTimeUpdates) {
                        window.realTimeUpdates.updateActiveTasksRealTime();
                    }
                }, 60000);

                // تحديث أسرع للجلسات النشطة كل 30 ثانية
                setInterval(() => {
                    if (window.realTimeUpdates) {
                        window.realTimeUpdates.updateActiveTasksRealTime();
                    }
                }, 30000);
            }
        }

        console.log('📊 تم تهيئة Timeline');
    }

    /**
     * تهيئة التحديثات المباشرة
     */
    initializeRealTimeUpdates() {
        if (window.realTimeUpdates && this.config.employeeId) {
            window.realTimeUpdates.init(
                this.config.employeeId,
                this.config.isToday,
                this.config.hasActiveSessions
            );
        }
    }

    /**
     * تهيئة الرسوم البيانية
     */
    initializeCharts() {
        // إعداد بيانات التقرير للرسوم البيانية
        if (window.employeeReportData) {
            const reportConfig = {
                taskData: window.employeeReportData.taskData || [],
                startDate: window.employeeReportData.startDate,
                endDate: window.employeeReportData.endDate,
                taskStatusData: window.employeeReportData.taskStatusData,
                timeData: window.employeeReportData.timeData
            };

            // إضافة بيانات الشيفت
            if (this.config.shiftData) {
                window.shiftData = this.config.shiftData;
                console.log('📊 بيانات الشيفت:', window.shiftData);
            }

            // تهيئة الرسوم البيانية
            if (typeof initReportVisualizations === 'function') {
                console.log('📈 إعداد الرسوم البيانية:', reportConfig);
                initReportVisualizations(reportConfig);
            }
        }
    }

    
    showActiveSessionsNotification() {
        if (window.realTimeUpdates) {
            window.realTimeUpdates.showActiveSessionsNotification(
                this.config.activeSessionsCount || 1,
                this.config.selectedDate
            );
        }
    }


    refresh(newConfig = {}) {
        this.config = { ...this.config, ...newConfig };

        if (window.timelineManager && this.hasTimeline()) {
            window.timelineManager.refreshAfterUpdate();
        }

        if (window.uiInteractions) {
            window.uiInteractions.refreshTooltips();
        }

        console.log('🔄 تم تحديث التطبيق:', this.config);
    }


    cleanup() {
        if (window.realTimeUpdates) {
            window.realTimeUpdates.stopRealTimeUpdates();
        }

        $('.minute-box').off('click mouseenter mouseleave');
        $('.timeline-task').off('mouseenter mouseleave');

        this.initialized = false;
        console.log('🧹 تم تنظيف موارد التطبيق');
    }

    handleError(error, context = 'عملية غير محددة') {
        console.error(`❌ خطأ في ${context}:`, error);

        if (window.uiInteractions) {
            window.uiInteractions.showErrorMessage(
                `حدث خطأ أثناء ${context}. يرجى المحاولة مرة أخرى.`,
                'خطأ في النظام'
            );
        }
    }


    exportData(format = 'json') {
        const exportData = {
            config: this.config,
            timestamp: new Date().toISOString(),
            timeline: this.extractTimelineData(),
            stats: this.calculateStats()
        };

        if (format === 'json') {
            this.downloadJSON(exportData, `employee-report-${this.config.employeeId}-${this.config.selectedDate}.json`);
        }
    }


    extractTimelineData() {
        const timelineData = [];
        $('.minute-box').each(function() {
            const $box = $(this);
            timelineData.push({
                time: $box.data('time'),
                status: $box.data('status'),
                isActive: $box.data('is-active'),
                hour: $box.data('hour')
            });
        });
        return timelineData;
    }

    calculateStats() {
        const $boxes = $('.minute-box');
        const working = $boxes.filter('[data-status="working"]').length;
        const idle = $boxes.filter('[data-status="idle"]').length;
        const future = $boxes.filter('[data-status="future"]').length;

        return {
            totalMinutes: $boxes.length,
            workingMinutes: working,
            idleMinutes: idle,
            futureMinutes: future,
            efficiency: working + idle > 0 ? Math.round((working / (working + idle)) * 100) : 0
        };
    }


    downloadJSON(data, filename) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}


window.employeeReportsMain = new EmployeeReportsMain();


$(document).ready(function() {
    console.log('📋 جاري تهيئة تقارير الموظفين...');

    const config = {
        initialized: true
    };

    window.employeeReportsMain.init(config);

    setTimeout(() => {
        if (window.realTimeUpdates) {
            window.realTimeUpdates.updateActiveTasksRealTime();
        }
    }, 1000);
});


$(window).on('beforeunload', function() {
    if (window.employeeReportsMain) {
        window.employeeReportsMain.cleanup();
    }
});
