/**
 * التفاعلات والتأثيرات البصرية
 */

class UIInteractions {
    constructor() {
        this.searchTimeout = null;
    }

    /**
     * تهيئة جميع التفاعلات
     */
    init() {
        this.initTooltips();
        this.initSearch();
        this.initFormInteractions();
        this.initChartInteractions();

        console.log('🎨 تم تهيئة التفاعلات والتأثيرات البصرية');
    }

    /**
     * تفعيل tooltips
     */
    initTooltips() {
        $('[data-toggle="tooltip"]').tooltip({
            html: true,
            placement: 'top',
            container: 'body',
            trigger: 'hover focus',
            delay: { show: 200, hide: 100 }
        });
    }

    /**
     * إعادة تفعيل tooltips بعد التحديث
     */
    refreshTooltips() {
        $('[data-toggle="tooltip"]').tooltip('dispose');
        this.initTooltips();
    }

    /**
     * تهيئة البحث عن الموظفين
     */
    initSearch() {
        const $searchInput = $('#employeeSearch');
        const $employeeList = $('.employee-list .employee-item');

        if ($searchInput.length === 0 || $employeeList.length === 0) return;

        $searchInput.on('input', (e) => {
            const searchTerm = $(e.target).val().toLowerCase().trim();

            // إلغاء البحث السابق
            if (this.searchTimeout) {
                clearTimeout(this.searchTimeout);
            }

            // بحث مع تأخير قصير لتحسين الأداء
            this.searchTimeout = setTimeout(() => {
                this.performEmployeeSearch(searchTerm, $employeeList);
            }, 150);
        });

        // مسح البحث عند الضغط على Escape
        $searchInput.on('keydown', (e) => {
            if (e.key === 'Escape') {
                $(e.target).val('');
                this.performEmployeeSearch('', $employeeList);
            }
        });
    }

    /**
     * تنفيذ البحث عن الموظفين
     */
    performEmployeeSearch(searchTerm, $employeeList) {
        if (!searchTerm) {
            // إظهار جميع الموظفين
            $employeeList.show().removeClass('search-hidden');
            $('.employee-list').removeClass('search-active');
            return;
        }

        $('.employee-list').addClass('search-active');
        let visibleCount = 0;

        $employeeList.each(function() {
            const $item = $(this);
            const employeeName = $item.data('employee-name') || '';
            const employeeJob = $item.find('.small').text().toLowerCase();

            const isVisible = employeeName.includes(searchTerm) || employeeJob.includes(searchTerm);

            if (isVisible) {
                $item.show().removeClass('search-hidden');
                visibleCount++;
            } else {
                $item.hide().addClass('search-hidden');
            }
        });

        // إظهار رسالة "لا توجد نتائج" إذا لزم الأمر
        this.toggleNoResultsMessage(visibleCount === 0, searchTerm);
    }

    /**
     * إظهار/إخفاء رسالة "لا توجد نتائج"
     */
    toggleNoResultsMessage(show, searchTerm) {
        const $employeeList = $('.employee-list');
        const messageId = 'no-search-results';

        $(`#${messageId}`).remove();

        if (show) {
            const message = `
                <div id="${messageId}" class="text-center p-4 text-muted">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p>لا توجد نتائج للبحث: <strong>"${searchTerm}"</strong></p>
                    <small>جرب البحث بكلمات مختلفة</small>
                </div>
            `;
            $employeeList.append(message);
        }
    }

    /**
     * تهيئة تفاعلات النماذج
     */
    initFormInteractions() {
        // تحسين تفاعل date picker
        $('input[type="date"]').on('change', function() {
            $(this).closest('form').addClass('date-changed');

            // تحديد لون خاص للتاريخ المحدد
            if ($(this).val()) {
                $(this).addClass('has-value');
            } else {
                $(this).removeClass('has-value');
            }
        });

        // تحسين select box للموظفين
        $('#employee_id').on('change', function() {
            const $option = $(this).find('option:selected');
            const employeeId = $(this).val();

            if (employeeId) {
                $(this).addClass('has-selection');

                // إظهار معاينة سريعة
                if (typeof toastr !== 'undefined') {
                    toastr.info(`تم اختيار: ${$option.text()}`, 'اختيار الموظف', {
                        timeOut: 2000,
                        positionClass: 'toast-top-right'
                    });
                }
            } else {
                $(this).removeClass('has-selection');
            }
        });

        // تأثيرات على الأزرار
        $('.btn').on('mouseenter', function() {
            $(this).addClass('btn-hover');
        }).on('mouseleave', function() {
            $(this).removeClass('btn-hover');
        });
    }

    /**
     * تفاعلات الرسوم البيانية
     */
    initChartInteractions() {
        // إضافة loading state للرسوم البيانية
        $('.chart-container canvas').each(function() {
            const $canvas = $(this);
            const $container = $canvas.closest('.chart-container');

            // إضافة loading indicator
            if (!$container.find('.chart-loading').length) {
                $container.append(`
                    <div class="chart-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <div class="mt-2 text-muted">جاري تحميل الرسم البياني...</div>
                    </div>
                `);
            }
        });
    }

    /**
     * إظهار loading للرسوم البيانية
     */
    showChartLoading($container) {
        $container.find('canvas').hide();
        $container.find('.chart-loading').show();
    }

    /**
     * إخفاء loading للرسوم البيانية
     */
    hideChartLoading($container) {
        $container.find('.chart-loading').hide();
        $container.find('canvas').show();
    }

    /**
     * إضافة تأثيرات للبطاقات
     */
    initCardEffects() {
        $('.stat-card, .action-card, .task-card').on('mouseenter', function() {
            $(this).addClass('card-elevated');
        }).on('mouseleave', function() {
            $(this).removeClass('card-elevated');
        });
    }

    /**
     * تأثيرات التمرير السلس
     */
    initSmoothScrolling() {
        // تمرير سلس للروابط الداخلية
        $('a[href^="#"]').on('click', function(e) {
            const target = $($(this).attr('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 80
                }, 500);
            }
        });
    }

    /**
     * إضافة keyboard shortcuts
     */
    initKeyboardShortcuts() {
        $(document).on('keydown', (e) => {
            // Ctrl/Cmd + F للبحث
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                const $search = $('#employeeSearch');
                if ($search.length && !$search.is(':focus')) {
                    e.preventDefault();
                    $search.focus().select();
                }
            }

            // Escape لمسح البحث
            if (e.key === 'Escape') {
                const $search = $('#employeeSearch');
                if ($search.is(':focus') && $search.val()) {
                    $search.val('');
                    this.performEmployeeSearch('', $('.employee-list .employee-item'));
                }
            }
        });
    }

    /**
     * إضافة إشعارات للإجراءات الناجحة
     */
    showSuccessMessage(message, title = 'نجح العملية') {
        if (typeof toastr !== 'undefined') {
            toastr.success(message, title, {
                timeOut: 3000,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }
    }

    /**
     * إضافة إشعارات للأخطاء
     */
    showErrorMessage(message, title = 'خطأ') {
        if (typeof toastr !== 'undefined') {
            toastr.error(message, title, {
                timeOut: 5000,
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-top-right'
            });
        }
    }

    /**
     * إضافة تأثيرات loading للأزرار
     */
    setButtonLoading($button, isLoading = true) {
        if (isLoading) {
            const originalText = $button.data('original-text') || $button.html();
            $button.data('original-text', originalText);
            $button.html('<i class="fas fa-spinner fa-spin"></i> جاري التحميل...');
            $button.prop('disabled', true).addClass('btn-loading');
        } else {
            const originalText = $button.data('original-text');
            if (originalText) {
                $button.html(originalText);
            }
            $button.prop('disabled', false).removeClass('btn-loading');
        }
    }
}

// إنشاء instance عمومي
window.uiInteractions = new UIInteractions();
