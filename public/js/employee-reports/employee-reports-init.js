/**
 * Employee Reports Initialization Script
 * تهيئة تقارير الموظفين
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 تكوين تقارير الموظفين...');

    // إعداد البيانات الأساسية
    const config = {
        employeeId: window.employeeReportConfig?.employeeId || null,
        selectedDate: window.employeeReportConfig?.selectedDate || '',
        isToday: window.employeeReportConfig?.isToday || false,
        hasActiveSessions: window.employeeReportConfig?.hasActiveSessions || false,
        activeSessionsCount: window.employeeReportConfig?.activeSessionsCount || 0,
        taskData: window.employeeReportConfig?.taskData || []
    };

    // إضافة بيانات الشيفت إذا توفرت
    if (window.employeeReportConfig?.shiftData) {
        config.shiftData = window.employeeReportConfig.shiftData;
    }

    // تهيئة التطبيق الرئيسي
    if (window.employeeReportsMain) {
        window.employeeReportsMain.init(config);
    } else {
        console.error('❌ لم يتم تحميل employeeReportsMain بشكل صحيح');
    }

    // تحديث بسيط للدقائق (بدون API calls)
    if (config.isToday) {
        // تحديث فوري
        updateLocalTimeline();

        // تحديث كل دقيقة
        setInterval(updateLocalTimeline, 60000);
    }

    /**
     * تحديث الجدول الزمني المحلي
     */
    function updateLocalTimeline() {
        const now = new Date();
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();
        const currentTimeStr = currentHour.toString().padStart(2, '0') + ':' + currentMinute.toString().padStart(2, '0');

        // تحديث البوكسات بناءً على الوقت الحالي فقط
        document.querySelectorAll('.minute-box').forEach(function(box) {
            const boxTime = box.getAttribute('data-time');
            const status = box.getAttribute('data-status');

            // إذا كان الوقت قد مر وما زال مستقبلي، غيره لفراغ
            if (boxTime <= currentTimeStr && status === 'future') {
                box.style.background = '#dc3545'; // أحمر للفراغ
                box.style.borderColor = '#c82333';
                box.setAttribute('data-status', 'idle');
                box.setAttribute('title', 'فراغ - ' + boxTime);
            }
        });

        // تحديث مؤشر الوقت الحالي بدون إعادة إنشاء
        updateCurrentTimeIndicator(currentHour, currentMinute, currentTimeStr);
    }

    /**
     * تحديث مؤشر الوقت الحالي
     */
    function updateCurrentTimeIndicator(currentHour, currentMinute, currentTimeStr) {
        // العثور على المؤشر الحالي وتحديث موقعه
        const existingPointer = document.querySelector('.current-time-pointer');

        if (existingPointer) {
            // تحديث النص والموقع
            const hourStr = currentHour.toString().padStart(2, '0');
            const targetHourRow = document.querySelector(`.hour-header h6:contains("الساعة ${hourStr}:")`);

            if (targetHourRow) {
                const minutesRow = targetHourRow.closest('.hour-row').querySelector('.minutes-row');
                const minutePosition = currentMinute * (18 + 3);

                existingPointer.style.left = minutePosition + 'px';
                existingPointer.querySelector('div').innerHTML = `<i class="fas fa-clock" style="margin-left: 6px;"></i>${currentTimeStr}
                    <div style="position: absolute; top: 100%; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 8px solid transparent; border-right: 8px solid transparent; border-top: 10px solid #ffc107;"></div>`;
            }
        }
    }
});

