// Main JavaScript for Overtime Requests
document.addEventListener('DOMContentLoaded', function() {
    // تأكد من أن Bootstrap Modal متاح
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap is not loaded!');
        return;
    }

    // تأكد من أن Chart.js متاح (إذا كانت هناك رسومات بيانية)
    if (typeof Chart === 'undefined' && document.getElementById('personalStatsChart')) {
        console.error('Chart.js is not loaded and charts are required!');
    }

    // Load scripts in order
    console.log('Overtime Requests module initialized');
});
