document.addEventListener('DOMContentLoaded', function () {
    // Real-time Timer for Team Performance
    function startTeamTimer(el) {
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
        const realTimeAddition = el.querySelector('.real-time-addition');

        if (realTimeIndicator) {
            el.innerHTML = displayText + el.innerHTML.substring(el.innerHTML.indexOf('<span'));
        } else if (realTimeAddition) {
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
                } else if (realTimeAddition) {
                    el.innerHTML = newDisplayText + el.innerHTML.substring(el.innerHTML.indexOf('<span'));
                } else {
                    el.textContent = newDisplayText;
                }
            }, 1000);
        }
    }

    startTeamTimer(document.getElementById('team-actual-timer'));
    startTeamTimer(document.getElementById('team-efficiency-timer'));
});
