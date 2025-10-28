document.addEventListener('DOMContentLoaded', function () {
    function startAggregateTimer(el) {
        if (!el) return;
        const initialMinutes = parseInt(el.getAttribute('data-initial-minutes') || '0', 10);
        const activeCount = parseInt(el.getAttribute('data-active-count') || '0', 10);
        let totalSeconds = initialMinutes * 60;
        const tickPerSecond = activeCount > 0 ? 1 : 0;

        function format(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }

        el.textContent = format(totalSeconds);

        setInterval(() => {
            totalSeconds += tickPerSecond; // ✅ ثانية واحدة فقط لو في مهام نشطة
            el.textContent = format(totalSeconds);
        }, 1000);
    }

    // ✅ إضافة Page Visibility API لحل مشكلة توقف التايمر في صفحة الأقسام
    function initializeDepartmentPageVisibilityHandler() {
        // الكشف عن تغيير حالة الصفحة (نشطة/غير نشطة)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // المستخدم عاد للتاب - نحديث التايمر
                syncDepartmentTimerWithRealTime();
            }
        });

        // تحديث التايمر كل 10 ثوان كـ backup عندما التاب نشط
        setInterval(function() {
            if (!document.hidden) {
                syncDepartmentTimerWithRealTime();
            }
        }, 10000);

        // تحديث التايمر عند النقر على أي مكان في الصفحة
        document.addEventListener('click', function() {
            if (!document.hidden) {
                setTimeout(() => {
                    syncDepartmentTimerWithRealTime();
                }, 100);
            }
        });
    }

    function syncDepartmentTimerWithRealTime() {
        // ✅ تحديث التايمر بالوقت الفعلي بناءً على timestamp الحقيقي
        const timerElement = document.getElementById('dept-actual-timer');
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

                    timerElement.textContent = format(totalSeconds);
                }
            }
        }
    }

    startAggregateTimer(document.getElementById('dept-actual-timer'));

    // ✅ تهيئة Page Visibility Handler
    initializeDepartmentPageVisibilityHandler();
});
