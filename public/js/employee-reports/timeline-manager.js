/**
 * إدارة Timeline والـ minute boxes
 */

class TimelineManager {
    constructor() {
        this.initialized = false;
        this.updateInterval = null;
    }

    /**
     * تهيئة minute-boxes عند تحميل الصفحة
     */
    initMinuteBoxes() {
        // إزالة Event listeners السابقة
        $('.minute-box').off('click mouseenter mouseleave');

        // إضافة click handler
        $('.minute-box').on('click', (e) => {
            this.handleMinuteBoxClick($(e.currentTarget));
        });

        // إضافة hover effects
        $('.minute-box').on('mouseenter', (e) => {
            this.handleMinuteBoxHover($(e.currentTarget), true);
        }).on('mouseleave', (e) => {
            this.handleMinuteBoxHover($(e.currentTarget), false);
        });

        // إضافة إحصائيات لكل ساعة
        this.addHourlyStats();

        this.initialized = true;
        console.log('🎯 تم تهيئة ' + $('.minute-box').length + ' مربع دقيقة في ' + $('.hour-row').length + ' ساعة بنجاح');
    }

    /**
     * معالجة click على minute box
     */
    handleMinuteBoxClick($box) {
        const time = $box.data('time');
        const status = $box.data('status');
        const isActive = $box.data('is-active');
        const hour = $box.data('hour');

        // إنشاء الرسالة
        let message = `الوقت: ${time}`;
        let icon = '🕐';
        let toastType = 'info';

        if (status === 'working') {
            message += isActive === 'true' ? '\nالحالة: نشط الآن ⚡' : '\nالحالة: عمل مكتمل ✅';
            icon = isActive === 'true' ? '🟢' : '✅';
            toastType = 'success';
        } else if (status === 'idle') {
            message += '\nالحالة: وقت فراغ 😴';
            icon = '🔴';
            toastType = 'warning';
        } else {
            message += '\nالحالة: لم يحن بعد ⏰';
            icon = '⏳';
            toastType = 'info';
        }

        message += `\nالساعة: ${hour}:xx`;

        // عرض التنبيه
        if (typeof toastr !== 'undefined') {
            toastr[toastType](message, icon + ' تفاصيل الدقيقة', {
                timeOut: 3000,
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        } else {
            alert(message);
        }
    }

    /**
     * معالجة hover على minute box
     */
    handleMinuteBoxHover($box, isEnter) {
        if (isEnter) {
            $box.css('transform', 'scale(1.15)');
            // إضافة تأثير للساعة المحتوية
            $box.closest('.hour-row').addClass('hour-highlighted');
        } else {
            if (!$box.hasClass('active-pulse')) {
                $box.css('transform', 'scale(1)');
            }
            // إزالة تأثير الساعة المحتوية
            $box.closest('.hour-row').removeClass('hour-highlighted');
        }
    }

    /**
     * إضافة إحصائيات سريعة لكل ساعة
     */
    addHourlyStats() {
        $('.hour-row').each(function() {
            const $hourRow = $(this);
            const workingMinutes = $hourRow.find('.minute-box[data-status="working"]').length;
            const idleMinutes = $hourRow.find('.minute-box[data-status="idle"]').length;
            const futureMinutes = $hourRow.find('.minute-box[data-status="future"]').length;
            const totalMinutes = workingMinutes + idleMinutes + futureMinutes;

            // حساب الكفاءة
            const efficiency = totalMinutes > 0 ? Math.round((workingMinutes / (workingMinutes + idleMinutes)) * 100) : 0;
            const badgeClass = efficiency >= 70 ? 'success' : (efficiency >= 50 ? 'warning' : 'danger');

            // إضافة badge للإحصائيات (فقط إذا كان هناك دقائق مضت)
            if (workingMinutes > 0 || idleMinutes > 0) {
                // إزالة Badge السابق إن وجد
                $hourRow.find('.hour-header .badge').remove();

                $hourRow.find('.hour-header div').append(
                    `<span class="badge badge-${badgeClass} ml-2" style="font-size: 10px;" title="كفاءة العمل: ${workingMinutes} دقيقة عمل من أصل ${workingMinutes + idleMinutes} دقيقة">${efficiency}% كفاءة</span>`
                );
            }
        });
    }

    /**
     * إعادة تحديث Timeline بعد Real-time update
     */
    refreshAfterUpdate() {
        if (this.initialized) {
            // إعادة تهيئة minute-boxes الجديدة
            this.initMinuteBoxes();

            // إعادة تفعيل tooltips
            $('[data-toggle="tooltip"]').tooltip('dispose');
            $('[data-toggle="tooltip"]').tooltip({
                html: true,
                placement: 'top',
                container: 'body',
                trigger: 'hover focus',
                delay: { show: 200, hide: 100 }
            });

            console.log('✅ تم تحديث Timeline مع ' + $('.minute-box').length + ' مربع دقيقة');
        }
    }

    /**
     * إضافة مؤشر الوقت الحالي (للـ timeline القديم)
     */
    addCurrentTimeIndicator() {
        const now = new Date();
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();

        // فقط إذا كان الوقت الحالي في نطاق العمل (8-17)
        if (currentHour >= 8 && currentHour <= 17) {
            const totalMinutesFromStart = (currentHour - 8) * 60 + currentMinute;
            const position = (totalMinutesFromStart / (10 * 60)) * 100; // 10 ساعات عمل

            $('.timeline-body').append(
                `<div class="current-time-indicator" style="left: ${position}%; position: absolute; top: 0; height: 100%; width: 3px; background: #dc3545; z-index: 15; box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);">
                    <div style="position: absolute; top: -30px; left: -15px; background: #dc3545; color: white; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; white-space: nowrap;">
                        <i class="fas fa-clock"></i> ${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}
                    </div>
                </div>`
            );
        }
    }

    /**
     * تحديث مؤشر الوقت الحالي
     */
    updateTimeIndicator() {
        $('.current-time-indicator').remove();
        this.addCurrentTimeIndicator();
    }

    /**
     * إضافة تأثيرات تفاعلية للمخطط الزمني القديم
     */
    initLegacyTimelineEffects() {
        $('.timeline-task').hover(
            function() {
                $(this).find('.timeline-task-content').css('transform', 'scale(1.05)');
            },
            function() {
                $(this).find('.timeline-task-content').css('transform', 'scale(1)');
            }
        );
    }
}

// إنشاء instance عمومي
window.timelineManager = new TimelineManager();
