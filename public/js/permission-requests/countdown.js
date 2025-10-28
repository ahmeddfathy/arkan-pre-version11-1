function startCountdown(element) {
    const returnTime = new Date(element.dataset.returnTime).getTime();
    const shiftEndTime = new Date(element.dataset.shiftEndTime).getTime();
    let timerLabel = element.querySelector('.timer-label');

    function updateTimer() {
        const now = new Date().getTime();
        const distance = returnTime - now;

        if (distance < 0) {
            timerLabel.textContent = "متأخر بـ";
            element.classList.add('danger');

            let overtime;

            if (now > shiftEndTime) {
                overtime = shiftEndTime - returnTime;
            } else {
                overtime = now - returnTime;
            }

            overtime = Math.max(0, overtime);

            const hours = Math.floor((overtime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((overtime % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((overtime % (1000 * 60)) / 1000);

            let timeDisplay = '';
            if (hours > 0) {
                timeDisplay += `${hours}:`;
            }
            timeDisplay += `${minutes < 10 && hours > 0 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            element.querySelector('.timer-value').innerHTML = timeDisplay;
            return true;
        }

        timerLabel.textContent = "الوقت المتبقي";
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        element.classList.remove('warning', 'danger');
        if (minutes < 5) {
            element.classList.add('danger');
        } else if (minutes < 10) {
            element.classList.add('warning');
        }

        let timeDisplay = '';
        if (hours > 0) {
            timeDisplay += `${hours}:`;
        }
        timeDisplay += `${minutes < 10 && hours > 0 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

        element.querySelector('.timer-value').innerHTML = timeDisplay;
        return true;
    }

    if (updateTimer()) {
        return setInterval(updateTimer, 1000);
    }
}

// Initializes all countdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    const timers = [];
    console.log("محاولة تهيئة العدادات، عدد العناصر:", document.querySelectorAll('.countdown').length);

    document.querySelectorAll('.countdown').forEach(element => {
        const returnTime = new Date(element.dataset.returnTime);
        const now = new Date();
        console.log("معلومات العداد:", {
            elementExists: !!element,
            returnTime: element.dataset.returnTime,
            parsedReturnTime: returnTime,
            currentTime: now,
            hasDiffInDays: returnTime.toDateString() !== now.toDateString(),
            timerLabelExists: !!element.querySelector('.timer-label'),
            timerValueExists: !!element.querySelector('.timer-value')
        });

        const timer = startCountdown(element);
        if (timer) {
            timers.push(timer);
            console.log("تمت إضافة المؤقت بنجاح");
        } else {
            console.log("فشل إضافة المؤقت");
        }
    });

    // Clean up timers when the page is unloaded
    window.addEventListener('beforeunload', () => {
        timers.forEach(timer => clearInterval(timer));
    });
});
