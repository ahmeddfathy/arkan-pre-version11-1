/**
 * جافا سكريبت صفحة إضافة بند التقييم
 * Evaluation Criteria Create Page JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // تحديث لون البند حسب النوع
    const criteriaTypeSelect = document.getElementById('criteria_type');

    if (criteriaTypeSelect) {
        criteriaTypeSelect.addEventListener('change', function() {
            const cardHeader = document.querySelector('.card-header');

            switch(this.value) {
                case 'positive':
                    cardHeader.className = 'card-header bg-success text-white';
                    break;
                case 'negative':
                    cardHeader.className = 'card-header bg-danger text-white';
                    break;
                case 'bonus':
                    cardHeader.className = 'card-header bg-warning text-dark';
                    break;
                default:
                    cardHeader.className = 'card-header bg-primary text-white';
            }
        });
    }

    // معالجة Toast عند النجاح
    const hasSuccessToast = window.hasSuccessToast || false;

    if (hasSuccessToast) {
        // تحديث العداد
        updateCriteriaCounter();

        // تفريغ النموذج بعد النجاح (إلا الدور المحدد)
        setTimeout(resetFormAfterSuccess, 1000);

        // إخفاء Toast تلقائياً بعد 5 ثوان
        setTimeout(hideSuccessToast, 5000);
    }
});

/**
 * تحديث عداد البنود مع تأثير بصري
 */
function updateCriteriaCounter() {
    const criteriaCounter = document.getElementById('criteriaCounter');
    if (criteriaCounter) {
        // تأثير بصري للعداد
        criteriaCounter.classList.add('badge-success');
        criteriaCounter.classList.remove('bg-primary');

        // استخراج العدد الحالي وزيادته
        const counterText = criteriaCounter.textContent;
        const currentNum = parseInt(counterText.match(/\d+/)[0]);
        const newNum = currentNum + 1;

        criteriaCounter.innerHTML = '<i class="fas fa-list"></i> ' + newNum + ' بند موجود';

        // إعادة اللون الأصلي بعد 3 ثوان
        setTimeout(function() {
            criteriaCounter.classList.remove('badge-success');
            criteriaCounter.classList.add('bg-primary');
        }, 3000);
    }
}

/**
 * تفريغ النموذج بعد النجاح مع الحفاظ على الدور المحدد
 */
function resetFormAfterSuccess() {
    const form = document.querySelector('form[action*="evaluation-criteria"]');
    if (form) {
        // حفظ قيمة الدور المحدد
        const roleId = document.querySelector('input[name="role_id"]')?.value;

        // تفريغ الحقول عدا الدور
        form.reset();

        // إعادة تعيين الدور إذا كان محدداً
        if (roleId) {
            const roleInput = document.querySelector('input[name="role_id"]');
            if (roleInput) {
                roleInput.value = roleId;
            }
        }

        // تفعيل checkbox "نشط" افتراضياً
        const isActiveCheckbox = document.querySelector('input[name="is_active"]');
        if (isActiveCheckbox) {
            isActiveCheckbox.checked = true;
        }

        // تركيز على حقل اسم البند
        const criteriaNameInput = document.querySelector('input[name="criteria_name"]');
        if (criteriaNameInput) {
            criteriaNameInput.focus();
        }
    }
}

/**
 * إخفاء Toast النجاح
 */
function hideSuccessToast() {
    const toast = document.getElementById('successToast');
    if (toast) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            const bsToast = new bootstrap.Toast(toast);
            bsToast.hide();
        } else {
            // Fallback إذا لم يكن Bootstrap متاحاً
            toast.style.display = 'none';
        }
    }
}

/**
 * إعداد متغيرات النجاح من الخادم
 * يتم استدعاؤها من صفحة Blade
 */
function setSuccessState(hasSuccess) {
    window.hasSuccessToast = hasSuccess;
}
