/**
 * نظام تأكيد المرفقات
 * يدير طلبات تأكيد المرفقات من مسؤول المشروع
 */

class AttachmentConfirmation {
    constructor() {
        this.init();
    }

    init() {
        console.log('AttachmentConfirmation: Initializing...');
        this.bindEvents();
        this.loadConfirmationStatuses();
        console.log('AttachmentConfirmation: Initialized successfully');
    }

    bindEvents() {
        console.log('AttachmentConfirmation: Binding events...');

        // Checkbox للتأكيد
        $(document).on('change', '.confirmation-checkbox', this.handleConfirmationCheckbox.bind(this));
        console.log('AttachmentConfirmation: Checkbox event bound');

        // زرار تأكيد الطلب (في صفحة المسؤول)
        $(document).on('click', '.confirm-attachment-btn', this.handleConfirmAttachment.bind(this));

        // زرار رفض الطلب (في صفحة المسؤول)
        $(document).on('click', '.reject-attachment-btn', this.handleRejectAttachment.bind(this));

        // زرار إعادة تعيين الطلب (في صفحة المسؤول)
        $(document).on('click', '.reset-confirmation-btn', this.handleResetConfirmation.bind(this));
        console.log('AttachmentConfirmation: Reset button event bound');

        console.log('AttachmentConfirmation: All events bound');
    }

    /**
     * معالجة تغيير checkbox التأكيد
     */
    async handleConfirmationCheckbox(e) {
        console.log('AttachmentConfirmation: Checkbox changed!');
        const checkbox = $(e.currentTarget);
        const isChecked = checkbox.prop('checked');
        const attachmentId = checkbox.data('attachment-id');
        const attachmentName = checkbox.data('attachment-name');

        console.log('Checkbox data:', { isChecked, attachmentId, attachmentName });

        if (isChecked) {
            // طلب تأكيد المرفق - مع اختيار نوع الملف
            const fileTypes = [
                'مسودة', 'تعديلات', 'نهائي', 'دراسة مصغرة', 'ريف', 'ترجمة', 'ملخص',
                'عروض أسعار', 'بروبوزيت', '2d', '3d', 'مسودة نهائي',
                'دراسة مصغرة او ريف او نها', 'تسويقي فقط', 'تقييم مالي',
                'تقرير افضل فرصة او مدينة', 'فورم', 'خطة عمل', 'تقرير',
                'تعديل باوربوينت', 'تغيير ورق', 'جهات تمويل', 'شيت اكسيل',
                'اشتراطات', 'تقرير افضل حي', 'ترجمة مرفقات', 'خطة توسع',
                'تعديل علي دراسة', 'بحث علمي', 'موردين', 'تعديل ع الترجمة',
                'خطة داخلية', 'دراسة فنية', 'دراسة فنية ومالية', 'خطة تسويقيه',
                'تحديد افضل سيناريو', 'خطة تشغيل', 'نهائي الخطة', 'خطة تنفيذية'
            ];

            const result = await Swal.fire({
                title: 'طلب تأكيد المرفق',
                html: `
                    <div class="text-start mb-3">
                        <p>هل تريد إرسال طلب تأكيد للمسؤول عن المشروع؟</p>
                        <p><strong>${attachmentName}</strong></p>
                        <label for="swal-file-type" class="form-label mt-3">نوع الملف:</label>
                        <select id="swal-file-type" class="form-select">
                            <option value="">اختر النوع...</option>
                            ${fileTypes.map(type => `<option value="${type}">${type}</option>`).join('')}
                        </select>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، أرسل الطلب',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                preConfirm: () => {
                    return document.getElementById('swal-file-type').value;
                }
            });

            if (!result.isConfirmed) {
                // إلغاء التحديد
                checkbox.prop('checked', false);
                return;
            }

            const fileType = result.value;

            // تعطيل الـ checkbox مؤقتاً
            checkbox.prop('disabled', true);

            try {
                const response = await fetch(`/attachments/${attachmentId}/request-confirmation`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        file_type: fileType
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // تحديث الحالة - checkbox يبقى محدد ومعطل
                    checkbox.prop('disabled', true);
                    checkbox.closest('.confirmation-checkbox-container')
                            .addClass('pending-confirmation')
                            .attr('title', 'قيد انتظار التأكيد من المسؤول');

                    // تغيير لون الـ label
                    checkbox.next('label').removeClass('text-muted')
                                         .addClass('text-warning fw-bold')
                                         .text('قيد الانتظار');

                    this.showToast('تم إرسال طلب التأكيد بنجاح', 'success');
                } else {
                    // إلغاء التحديد في حالة الفشل
                    checkbox.prop('checked', false).prop('disabled', false);
                    this.showToast(data.message || 'حدث خطأ أثناء إرسال الطلب', 'error');
                }

            } catch (error) {
                console.error('Error requesting confirmation:', error);
                checkbox.prop('checked', false).prop('disabled', false);
                this.showToast('حدث خطأ في الاتصال بالخادم', 'error');
            }
        } else {
            // منع إلغاء التحديد بعد الإرسال
            checkbox.prop('checked', true);
        }
    }

    /**
     * تأكيد المرفق من المسؤول
     */
    async handleConfirmAttachment(e) {
        e.preventDefault();

        const button = $(e.currentTarget);
        const confirmationId = button.data('confirmation-id');
        const attachmentName = button.data('attachment-name');

        // طلب ملاحظات اختيارية
        const result = await Swal.fire({
            title: 'تأكيد المرفق',
            html: `هل تريد تأكيد هذا المرفق؟<br><strong>${attachmentName}</strong>`,
            input: 'textarea',
            inputLabel: 'ملاحظات (اختياري)',
            inputPlaceholder: 'أضف ملاحظاتك هنا...',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، أكد المرفق',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        });

        if (!result.isConfirmed) {
            return;
        }

        // تعطيل الزرار
        button.prop('disabled', true);
        const originalHtml = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i> جاري التأكيد...');

        try {
            const response = await fetch(`/attachment-confirmations/${confirmationId}/confirm`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    notes: result.value || null
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showToast(data.message || 'تم تأكيد المرفق بنجاح', 'success');

                // إعادة تحميل الصفحة فوراً بدون cache
                setTimeout(() => {
                    window.location.href = window.location.href.split('?')[0] + '?t=' + Date.now();
                }, 800);
            } else {
                button.prop('disabled', false).html(originalHtml);
                this.showToast(data.message || 'حدث خطأ أثناء التأكيد', 'error');
            }

        } catch (error) {
            console.error('Error confirming attachment:', error);
            button.prop('disabled', false).html(originalHtml);
            this.showToast('حدث خطأ في الاتصال بالخادم', 'error');
        }
    }

    /**
     * رفض المرفق من المسؤول
     */
    async handleRejectAttachment(e) {
        e.preventDefault();

        const button = $(e.currentTarget);
        const confirmationId = button.data('confirmation-id');
        const attachmentName = button.data('attachment-name');

        // طلب سبب الرفض
        const result = await Swal.fire({
            title: 'رفض المرفق',
            html: `هل تريد رفض هذا المرفق؟<br><strong>${attachmentName}</strong>`,
            input: 'textarea',
            inputLabel: 'سبب الرفض (مطلوب)',
            inputPlaceholder: 'اكتب سبب الرفض...',
            inputValidator: (value) => {
                if (!value) {
                    return 'يجب كتابة سبب الرفض';
                }
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، ارفض المرفق',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        });

        if (!result.isConfirmed) {
            return;
        }

        // تعطيل الزرار
        button.prop('disabled', true);
        const originalHtml = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i> جاري الرفض...');

        try {
            const response = await fetch(`/attachment-confirmations/${confirmationId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    notes: result.value
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showToast(data.message || 'تم رفض المرفق', 'info');

                // إعادة تحميل الصفحة فوراً بدون cache
                setTimeout(() => {
                    window.location.href = window.location.href.split('?')[0] + '?t=' + Date.now();
                }, 800);
            } else {
                button.prop('disabled', false).html(originalHtml);
                this.showToast(data.message || 'حدث خطأ أثناء الرفض', 'error');
            }

        } catch (error) {
            console.error('Error rejecting attachment:', error);
            button.prop('disabled', false).html(originalHtml);
            this.showToast('حدث خطأ في الاتصال بالخادم', 'error');
        }
    }

    /**
     * إعادة تعيين طلب التأكيد
     */
    async handleResetConfirmation(e) {
        e.preventDefault();

        const button = $(e.currentTarget);
        const confirmationId = button.data('confirmation-id');
        const attachmentName = button.data('attachment-name');

        // تأكيد إعادة التعيين
        const result = await Swal.fire({
            title: 'إعادة تعيين طلب التأكيد',
            html: `هل تريد إعادة تعيين طلب التأكيد؟<br><strong>${attachmentName}</strong><br><small class="text-muted">سيتم إرجاع الطلب إلى حالة "قيد الانتظار"</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، أعد تعيين',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#6c757d'
        });

        if (!result.isConfirmed) return;

        // إظهار loading
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري...');

        try {
            const response = await fetch(`/attachment-confirmations/${confirmationId}/reset`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showToast(data.message || 'تم إعادة تعيين الطلب بنجاح', 'success');

                // إعادة توجيه للصفحة مع فلتر pending لعرض الطلب المعاد تعيينه
                setTimeout(() => {
                    const baseUrl = window.location.href.split('?')[0];
                    window.location.href = baseUrl + '?status=pending&t=' + Date.now();
                }, 800);
            } else {
                button.prop('disabled', false).html('<i class="fas fa-undo"></i> إعادة تعيين');
                this.showToast(data.message || 'حدث خطأ أثناء إعادة تعيين الطلب', 'error');
            }
        } catch (error) {
            console.error('Error resetting confirmation:', error);
            button.prop('disabled', false).html('<i class="fas fa-undo"></i> إعادة تعيين');
            this.showToast('حدث خطأ في الاتصال بالخادم', 'error');
        }
    }

    /**
     * تحميل حالات التأكيد للمرفقات
     */
    async loadConfirmationStatuses() {
        const attachmentItems = document.querySelectorAll('li[data-attachment-id]');
        console.log(`Found ${attachmentItems.length} attachment items`);

        const checkboxes = document.querySelectorAll('.confirmation-checkbox');
        console.log(`Found ${checkboxes.length} confirmation checkboxes`);

        const resetButtons = document.querySelectorAll('.reset-confirmation-btn');
        console.log(`Found ${resetButtons.length} reset buttons`);

        // تحميل جميع الحالات بشكل متزامن بدلاً من واحدة تلو الأخرى
        const promises = Array.from(attachmentItems).map(async (item) => {
            const attachmentId = item.dataset.attachmentId;

            try {
                const response = await fetch(`/attachments/${attachmentId}/confirmation-status`);
                const data = await response.json();

                if (data.success && data.status) {
                    this.updateAttachmentStatus(item, data.status);
                }
            } catch (error) {
                console.error(`Error loading status for attachment ${attachmentId}:`, error);
            }
        });

        // انتظار جميع الطلبات في نفس الوقت
        await Promise.all(promises);
        console.log('All confirmation statuses loaded');
    }

    /**
     * تحديث حالة المرفق في الواجهة
     */
    updateAttachmentStatus(item, status) {
        const checkbox = $(item).find('.confirmation-checkbox');
        const label = checkbox.next('label');
        const container = checkbox.closest('.confirmation-checkbox-container');

        // إزالة جميع الحالات السابقة
        container.removeClass('pending-confirmation confirmed rejected');

        switch (status.status) {
            case 'pending':
                checkbox.prop('checked', true).prop('disabled', true);
                container.addClass('pending-confirmation')
                         .attr('title', 'قيد انتظار التأكيد من المسؤول');
                label.html('<i class="fas fa-clock me-1"></i>انتظار');
                break;

            case 'confirmed':
                checkbox.prop('checked', true).prop('disabled', true);
                container.addClass('confirmed')
                         .attr('title', `مؤكد من: ${status.manager?.name || 'المسؤول'}`);
                label.html('<i class="fas fa-check-circle me-1"></i>مؤكد');
                break;

            case 'rejected':
                checkbox.prop('checked', false).prop('disabled', true);
                container.addClass('rejected')
                         .attr('title', `مرفوض: ${status.notes || ''}`);
                label.html('<i class="fas fa-times-circle me-1"></i>مرفوض');
                break;
        }
    }

    /**
     * عرض رسالة توست
     */
    showToast(message, type = 'info') {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        const titles = {
            success: 'نجاح',
            error: 'خطأ',
            warning: 'تحذير',
            info: 'معلومة'
        };

        toastr[type](message, `${icons[type]} ${titles[type]}`);
    }
}

// تهيئة النظام عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // التحقق من وجود jQuery
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded! AttachmentConfirmation cannot initialize.');
        return;
    }

    // التحقق من وجود SweetAlert2
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded! AttachmentConfirmation cannot initialize.');
        return;
    }

    console.log('jQuery and SweetAlert2 are available, initializing AttachmentConfirmation...');
    window.attachmentConfirmation = new AttachmentConfirmation();
});
