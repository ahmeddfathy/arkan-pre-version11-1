/**
 * معالج أخطاء نقل المهام - نسخة محسّنة
 * يعرض رسائل واضحة للمهام غير المُعيَّنة
 */

// إضافة دالة لعرض أخطاء النقل مع التفاصيل
function showTransferError(xhr) {
    let message = 'حدث خطأ أثناء النقل';
    let errorDetails = '';

    if (xhr.responseJSON) {
        const response = xhr.responseJSON;
        message = response.message || message;

        // إضافة تفاصيل للمهام غير المعينة
        if (response.reason || response.solution) {
            errorDetails = `
                <div class="mt-3 text-start">
                    ${response.reason ? `
                        <div class="alert alert-warning mb-2">
                            <strong><i class="fas fa-exclamation-triangle me-2"></i>السبب:</strong><br>
                            ${response.reason}
                        </div>
                    ` : ''}
                    ${response.solution ? `
                        <div class="alert alert-info mb-2">
                            <strong><i class="fas fa-lightbulb me-2"></i>الحل المقترح:</strong><br>
                            ${response.solution}
                        </div>
                    ` : ''}
                    ${response.task_name ? `
                        <div class="alert alert-light mb-0">
                            <strong><i class="fas fa-tasks me-2"></i>اسم المهمة:</strong> ${response.task_name}
                        </div>
                    ` : ''}
                </div>
            `;
        }
    }

    const taskData = {
        name: currentTaskData?.name || 'مهمة غير معروفة'
    };

    console.log('🚫 خطأ في طلب النقل', {
        status: xhr.status,
        responseJSON: xhr.responseJSON,
        message: message,
        hasDetails: !!errorDetails
    });

    // التحقق من وجود SweetAlert
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'فشل النقل! ❌',
            html: `
                <div class="text-center">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    </div>
                    <p class="mb-2"><strong>المهمة:</strong> ${taskData.name}</p>
                    <div class="alert alert-danger mb-3">
                        ${message}
                    </div>
                    ${errorDetails}
                </div>
            `,
            icon: 'error',
            confirmButtonText: 'موافق',
            confirmButtonColor: '#dc3545',
            width: '500px',
            customClass: {
                htmlContainer: 'text-start'
            }
        });
    } else {
        console.warn('SweetAlert غير متوفر، سيتم استخدام showError');
        if (typeof showError === 'function') {
            showError(message);
        } else {
            alert(message);
        }
    }

    // إعادة تفعيل زر النقل
    $('#confirmTransferBtn').prop('disabled', false).html('<span class="btn-text">نقل المهمة</span>');
}

// تحديث executeTransfer لاستخدام المعالج الجديد
$(document).ready(function() {
    // تحديث معالج الخطأ في executeTransfer
    if (typeof executeTransfer !== 'undefined') {
        console.log('✅ معالج أخطاء النقل المحسّن جاهز');
    }
});
