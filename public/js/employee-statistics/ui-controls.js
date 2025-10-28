/**
 * UI Controls JavaScript
 * يتعامل مع التفاعلات وعناصر واجهة المستخدم مثل الأزرار والانهيارات
 */

// سكريبت خاص بعرض وإخفاء قواعد التقييم
document.addEventListener('DOMContentLoaded', function() {
    const rulesToggleBtn = document.getElementById('rulesToggleBtn');
    const rulesCollapse = document.getElementById('rulesCollapse');
    const rulesIcon = document.getElementById('rulesIcon');

    if (rulesToggleBtn && rulesCollapse) {
        rulesToggleBtn.addEventListener('click', function() {
            // تبديل حالة العرض
            if (rulesCollapse.style.display === 'none') {
                // عرض القواعد
                rulesCollapse.style.display = 'block';
                // إضافة تأثير حركي للعرض
                rulesCollapse.style.opacity = '0';
                rulesCollapse.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    rulesCollapse.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    rulesCollapse.style.opacity = '1';
                    rulesCollapse.style.transform = 'translateY(0)';
                }, 10);
                // تدوير الأيقونة
                rulesIcon.classList.remove('fa-chevron-down');
                rulesIcon.classList.add('fa-chevron-up');
            } else {
                // إضافة تأثير حركي للإخفاء
                rulesCollapse.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                rulesCollapse.style.opacity = '0';
                rulesCollapse.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    rulesCollapse.style.display = 'none';
                }, 300);
                // تدوير الأيقونة
                rulesIcon.classList.remove('fa-chevron-up');
                rulesIcon.classList.add('fa-chevron-down');
            }
        });
    }
});

// Animation for new rows
document.addEventListener('DOMContentLoaded', function() {
    if (typeof gsap !== 'undefined') {
        gsap.from(".request-row", {
            duration: 0.5,
            opacity: 0,
            y: 20,
            stagger: 0.1
        });
    }
});

// إزالة قيود التاريخ
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // إزالة أي قيود
        input.removeAttribute('min');
        input.removeAttribute('max');

        // منع أي أحداث JavaScript تقيد اختيار التاريخ
        input.addEventListener('mousedown', function(e) {
            e.stopPropagation();
        }, true);
    });
});

// تهيئة tooltips
document.addEventListener("DOMContentLoaded", function() {
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                html: true,
                container: 'body'
            });
        });
    }
});
