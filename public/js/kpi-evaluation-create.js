// KPI Evaluation Create Page - JavaScript
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const criteriaInputs = document.querySelectorAll('.criteria-input');

    // حساب الإجماليات
    function calculateTotals() {
        let positiveTotal = 0;
        let negativeTotal = 0;
        let bonusTotal = 0;

        criteriaInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            const type = input.dataset.type;

            switch(type) {
                case 'positive':
                    positiveTotal += value;
                    break;
                case 'negative':
                    negativeTotal += value;
                    break;
                case 'bonus':
                    bonusTotal += value;
                    break;
            }
        });

        const finalTotal = positiveTotal + bonusTotal - negativeTotal;

        document.getElementById('positiveTotal').textContent = positiveTotal;
        document.getElementById('negativeTotal').textContent = negativeTotal;
        document.getElementById('bonusTotal').textContent = bonusTotal;
        document.getElementById('finalTotal').textContent = finalTotal;

        // تغيير لون الإجمالي النهائي
        const finalElement = document.getElementById('finalTotal');
        if (finalTotal < 0) {
            finalElement.className = 'text-danger';
        } else if (finalTotal > 0) {
            finalElement.className = 'text-success';
        } else {
            finalElement.className = 'text-secondary';
        }
    }

    // ربط الأحداث
    criteriaInputs.forEach(input => {
        input.addEventListener('input', function() {
            const max = parseInt(this.dataset.max);
            if (parseInt(this.value) > max) {
                this.value = max;
            }
            calculateTotals();
        });
    });

    // حساب أولي
    calculateTotals();

    // جلب المشاريع عند اختيار الموظف
    const userSelect = document.querySelector('select[name="user_id"]');
    if (userSelect) {
        userSelect.addEventListener('change', function() {
            const userId = this.value;
            const roleId = userSelect.dataset.roleId;

            if (userId) {
                fetchUserProjects(userId, roleId);
            } else {
                hideProjectSection();
            }
        });
    }

    function fetchUserProjects(userId, roleId) {
        const projectSection = document.getElementById('projectEvaluationSection');
        const container = document.getElementById('userProjectsContainer');

        // عرض loader
        container.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="mt-2">جاري تحميل المشاريع...</p>
            </div>
        `;

        projectSection.style.display = 'block';

        // طلب AJAX لجلب المشاريع
        const reviewMonth = document.querySelector('input[name="review_month"]')?.value || new Date().toISOString().slice(0, 7);
        const ajaxUrl = userSelect.dataset.ajaxUrl;

        fetch(`${ajaxUrl}?user_id=${userId}&role_id=${roleId}&review_month=${reviewMonth}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayUserProjects(data.projects, data.criteria);
                } else {
                    container.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.error || 'حدث خطأ في تحميل المشاريع'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        حدث خطأ في الاتصال بالخادم
                    </div>
                `;
            });
    }

    function displayUserProjects(projects, criteria) {
        const container = document.getElementById('userProjectsContainer');

        if (projects.length === 0) {
            container.innerHTML = `
                <div class="text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <h5>لا توجد مشاريع</h5>
                    <p>هذا الموظف لم يشارك في أي مشاريع بعد</p>
                </div>
            `;
            return;
        }

        if (criteria.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    لا توجد بنود تقييم مرتبطة بالمشاريع لهذا الدور
                </div>
            `;
            return;
        }

        let html = '<div class="row">';

        projects.forEach((project, projectIndex) => {
            html += `
                <div class="col-12 mb-4">
                    <div class="project-card ${project.is_evaluated ? 'evaluated' : ''}">
                        <div class="project-header">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="fas fa-project-diagram me-2"></i>
                                        ${project.name}
                                        ${project.is_evaluated ? '<span class="badge bg-success ms-2"><i class="fas fa-check me-1"></i>مُقيَّم</span>' : '<span class="badge bg-warning text-dark ms-2"><i class="fas fa-clock me-1"></i>بانتظار التقييم</span>'}
                                    </h6>
                                    <small class="text-muted">
                                        العميل: ${project.client_name} | الحالة: ${project.status}
                                    </small>
                                </div>
                                ${project.is_evaluated ? `
                                    <div class="text-end">
                                        <small class="text-success">
                                            <i class="fas fa-star me-1"></i>${project.evaluation_score} نقطة
                                        </small><br>
                                        <small class="text-muted">
                                            قيَّمه: ${project.evaluator_name}<br>
                                            ${project.evaluation_date}
                                        </small>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="project-criteria mt-3">
                            <div class="row">
            `;

            criteria.forEach(criterion => {
                if (project.is_evaluated) {
                    // عرض النقاط المحفوظة للمشروع المُقيَّم
                    const savedScore = project.evaluation_criteria_scores && project.evaluation_criteria_scores[criterion.id]
                                     ? project.evaluation_criteria_scores[criterion.id].score
                                     : 'غير محدد';

                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="criteria-card evaluated-criteria">
                                <label class="form-label fw-bold text-success">
                                    ${criterion.name}
                                    <span class="badge bg-success">✓ ${savedScore}/${criterion.max_points}</span>
                                </label>
                                ${criterion.description ? `<p class="text-muted small">${criterion.description}</p>` : ''}
                                <div class="alert alert-success mb-0 py-2">
                                    <i class="fas fa-check-circle me-2"></i>
                                    تم التقييم مسبقاً: <strong>${savedScore} من ${criterion.max_points} نقطة</strong>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    // عرض حقول الإدخال للمشاريع غير المُقيَّمة
                    html += `
                        <div class="col-md-6 mb-3">
                            <div class="criteria-card">
                                <label class="form-label fw-bold">
                                    ${criterion.name}
                                    <span class="badge bg-primary">+${criterion.max_points} نقطة</span>
                                </label>
                                ${criterion.description ? `<p class="text-muted small">${criterion.description}</p>` : ''}
                                <div class="input-group">
                                    <input type="number"
                                           name="project_criteria[${project.id}][${criterion.id}]"
                                           class="form-control project-criteria-input"
                                           min="0"
                                           max="${criterion.max_points}"
                                           value="0"
                                           data-max="${criterion.max_points}"
                                           data-type="project"
                                           data-project-id="${project.id}"
                                           data-criteria-id="${criterion.id}">
                                    <span class="input-group-text">/ ${criterion.max_points}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });

            html += `
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;

        // ربط أحداث التقييم للمشاريع
        bindProjectCriteriaEvents();
    }

    function bindProjectCriteriaEvents() {
        const projectInputs = document.querySelectorAll('.project-criteria-input');
        projectInputs.forEach(input => {
            input.addEventListener('input', function() {
                const max = parseInt(this.dataset.max);
                const value = parseInt(this.value);

                if (value > max) {
                    this.value = max;
                }
                if (value < 0) {
                    this.value = 0;
                }

                // إعادة حساب الإجماليات مع المشاريع
                calculateTotalsWithProjects();
            });
        });
    }

    function calculateTotalsWithProjects() {
        let positiveTotal = 0;
        let negativeTotal = 0;
        let bonusTotal = 0;
        let projectTotal = 0;

        // حساب البنود العادية
        criteriaInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            const type = input.dataset.type;

            switch(type) {
                case 'positive':
                    positiveTotal += value;
                    break;
                case 'negative':
                    negativeTotal += value;
                    break;
                case 'bonus':
                    bonusTotal += value;
                    break;
            }
        });

        // حساب بنود المشاريع
        const projectInputs = document.querySelectorAll('.project-criteria-input');
        projectInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            projectTotal += value;
        });

        const finalTotal = positiveTotal + bonusTotal + projectTotal - negativeTotal;

        document.getElementById('positiveTotal').textContent = positiveTotal;
        document.getElementById('negativeTotal').textContent = negativeTotal;
        document.getElementById('bonusTotal').textContent = bonusTotal + projectTotal;
        document.getElementById('finalTotal').textContent = finalTotal;

        // تغيير لون الإجمالي النهائي
        const finalElement = document.getElementById('finalTotal');
        if (finalTotal < 0) {
            finalElement.className = 'text-danger';
        } else if (finalTotal > 0) {
            finalElement.className = 'text-success';
        } else {
            finalElement.className = 'text-secondary';
        }
    }

    function hideProjectSection() {
        const projectSection = document.getElementById('projectEvaluationSection');
        projectSection.style.display = 'none';
    }

    // تفعيل/تعطيل زرار عرض التفاصيل
    const userSelectForDetails = document.getElementById('userSelect');
    const viewDetailsBtn = document.getElementById('viewDetailsBtn');

    if (userSelectForDetails && viewDetailsBtn) {
        userSelectForDetails.addEventListener('change', function() {
            if (this.value) {
                viewDetailsBtn.disabled = false;
            } else {
                viewDetailsBtn.disabled = true;
            }
        });

        viewDetailsBtn.addEventListener('click', function() {
            const userId = userSelectForDetails.value;
            const month = document.getElementById('reviewMonthInput').value;

            if (userId && month) {
                openDetailsSidebar(userId, month);
            }
        });
    }
});

// Change Evaluation Type
function changeEvaluationType() {
    const selectedType = document.getElementById('evaluationTypeSelector').value;
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('evaluation_type', selectedType);

    // إعادة تحميل الصفحة مع نوع التقييم الجديد
    window.location.href = currentUrl.toString();
}

// تحديث hidden input عند تغيير النوع
document.addEventListener('DOMContentLoaded', function() {
    const selector = document.getElementById('evaluationTypeSelector');
    const hiddenInput = document.querySelector('input[name="evaluation_type"]');

    if (selector && hiddenInput) {
        selector.addEventListener('change', function() {
            hiddenInput.value = this.value;
        });
    }

    // تحديث نص فترة التقييم عند تغيير الشهر أو نوع التقييم
    updateEvaluationPeriodHint();

    const reviewMonthInput = document.getElementById('reviewMonthInput');
    const evaluationTypeSelector = document.getElementById('evaluationTypeSelector');

    if (reviewMonthInput) {
        reviewMonthInput.addEventListener('change', updateEvaluationPeriodHint);
    }

    if (evaluationTypeSelector) {
        evaluationTypeSelector.addEventListener('change', updateEvaluationPeriodHint);
    }
});

/**
 * تحديث نص فترة التقييم بناءً على الشهر ونوع التقييم
 */
function updateEvaluationPeriodHint() {
    const reviewMonthInput = document.getElementById('reviewMonthInput');
    const evaluationTypeSelector = document.getElementById('evaluationTypeSelector');
    const periodText = document.getElementById('periodText');

    if (!reviewMonthInput || !evaluationTypeSelector || !periodText) return;

    const selectedMonth = reviewMonthInput.value; // بصيغة Y-m (مثال: 2025-11)
    const evaluationType = evaluationTypeSelector.value; // monthly أو bi_weekly

    if (!selectedMonth) return;

    const period = calculateEvaluationPeriod(selectedMonth, evaluationType);

    if (evaluationType === 'bi_weekly') {
        periodText.innerHTML = `<strong>الفترة:</strong> من ${period.startFormatted} إلى ${period.endFormatted} (تقييم نصف شهري)`;
    } else {
        periodText.innerHTML = `<strong>الفترة:</strong> من ${period.startFormatted} إلى ${period.endFormatted} (تقييم شهري)`;
    }
}

/**
 * حساب فترة التقييم بنفس منطق الـ PHP
 * @param {string} reviewMonth - الشهر بصيغة Y-m (مثال: 2025-11)
 * @param {string} evaluationType - نوع التقييم (monthly أو bi_weekly)
 * @returns {object} - {start, end, startFormatted, endFormatted}
 */
function calculateEvaluationPeriod(reviewMonth, evaluationType) {
    const [year, month] = reviewMonth.split('-').map(Number);

    let startDate, endDate;

    if (evaluationType === 'bi_weekly') {
        // نصف شهري: من يوم 15 الشهر السابق إلى يوم 14 الشهر الحالي
        const prevMonth = month === 1 ? 12 : month - 1;
        const prevYear = month === 1 ? year - 1 : year;

        startDate = new Date(prevYear, prevMonth - 1, 15);
        endDate = new Date(year, month - 1, 14);
    } else {
        // شهري: من يوم 26 الشهر السابق إلى يوم 25 الشهر الحالي
        const prevMonth = month === 1 ? 12 : month - 1;
        const prevYear = month === 1 ? year - 1 : year;

        startDate = new Date(prevYear, prevMonth - 1, 26);
        endDate = new Date(year, month - 1, 25);
    }

    return {
        start: startDate,
        end: endDate,
        startFormatted: formatDate(startDate),
        endFormatted: formatDate(endDate)
    };
}

/**
 * تنسيق التاريخ بصيغة DD/MM/YYYY
 */
function formatDate(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Details Sidebar Functions
// =========================

let detailsSidebarOpen = false;

function openDetailsSidebar(userId, month) {
    const sidebar = document.getElementById('detailsSidebar');
    const loading = document.getElementById('detailsLoading');
    const content = document.getElementById('detailsContent');

    if (!sidebar) return;

    sidebar.classList.add('active');
    detailsSidebarOpen = true;

    loading.style.display = 'flex';
    content.style.display = 'none';

    // عرض فترة التقييم في السايدبار
    displaySidebarPeriod(month);

    // الحصول على نوع التقييم من الصفحة
    const evaluationTypeSelector = document.getElementById('evaluationTypeSelector');
    const evaluationType = evaluationTypeSelector ? evaluationTypeSelector.value : 'monthly';

    const baseUrl = sidebar.dataset.ajaxUrl || '/kpi-evaluation/user-details';
    const url = `${baseUrl}/${userId}/${month}?evaluation_type=${evaluationType}`;

    fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            displayUserDetails(data.data);
            loading.style.display = 'none';
            content.style.display = 'block';
            setTimeout(() => {
                const firstTab = document.getElementById('revisions-tab');
                if (firstTab) firstTab.click();
            }, 100);
        } else {
            throw new Error(data.message || 'خطأ في تحميل البيانات');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        loading.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                <h5>خطأ في التحميل</h5>
                <p class="text-muted">${error.message}</p>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm me-2" onclick="openDetailsSidebar(${userId}, '${month}')">
                        <i class="fas fa-redo me-2"></i>إعادة المحاولة
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="closeDetailsSidebar()">
                        <i class="fas fa-times me-1"></i>إغلاق
                    </button>
                </div>
            </div>
        `;
    });
}

function closeDetailsSidebar() {
    const sidebar = document.getElementById('detailsSidebar');
    if (sidebar) {
        sidebar.classList.remove('active');
        detailsSidebarOpen = false;
    }
}

function displayUserDetails(data) {
    // عرض التعديلات
    const revisionsContent = document.getElementById('revisionsContent');
    if (revisionsContent) {
        if (data.revisions && data.revisions.length > 0) {
            let revisionsHtml = `
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>عدد التعديلات:</strong> ${data.revisions.length} تعديل خلال فترة التقييم
                </div>
            `;
            data.revisions.forEach(revision => {
                revisionsHtml += `
                    <div class="revision-item mb-3">
                        <div class="revision-header d-flex justify-content-between align-items-start">
                            <div class="revision-info">
                                <h6 class="revision-title mb-1">${revision.title}</h6>
                                <div class="revision-meta">
                                    <span class="badge bg-${revision.status_color} me-2">${revision.status_text}</span>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>${revision.creator_name} |
                                        <i class="fas fa-calendar-alt me-1"></i>${revision.revision_date}
                                    </small>
                                </div>
                            </div>
                            <div class="revision-type">
                                <span class="badge bg-${revision.revision_source_color}">
                                    <i class="${revision.revision_source_icon} me-1"></i>
                                    ${revision.revision_source_text}
                                </span>
                            </div>
                        </div>
                        <div class="revision-body mt-2">
                            <p class="revision-description mb-2">${revision.description || 'لا يوجد وصف'}</p>

                            ${revision.responsibility_notes ? `
                                <div class="alert alert-danger mb-2 py-2">
                                    <i class="fas fa-user-times me-1"></i>
                                    <strong>سبب المسؤولية:</strong> ${revision.responsibility_notes}
                                </div>
                            ` : ''}

                            <div class="row mb-2">
                                ${revision.responsible_user_name ? `
                                    <div class="col-6">
                                        <small class="text-danger">
                                            <i class="fas fa-user-shield me-1"></i>
                                            <strong>المسؤول:</strong> ${revision.responsible_user_name}
                                        </small>
                                    </div>
                                ` : ''}
                                ${revision.executor_user_name ? `
                                    <div class="col-6">
                                        <small class="text-primary">
                                            <i class="fas fa-user-cog me-1"></i>
                                            <strong>المنفذ:</strong> ${revision.executor_user_name}
                                        </small>
                                    </div>
                                ` : ''}
                            </div>

                            ${revision.attachment_name ? `
                                <div class="revision-attachment">
                                    <i class="${revision.attachment_icon} me-1"></i>
                                    <a href="${revision.attachment_url}" target="_blank">${revision.attachment_name}</a>
                                    <span class="text-muted ms-2">(${revision.formatted_attachment_size})</span>
                                </div>
                            ` : ''}
                            ${revision.notes ? `<div class="revision-notes mt-2 p-2 bg-light rounded"><small>${revision.notes}</small></div>` : ''}
                        </div>
                    </div>
                `;
            });
            revisionsContent.innerHTML = revisionsHtml;
        } else {
            revisionsContent.innerHTML = `
                <div class="empty-state text-center py-4">
                    <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">لا توجد تعديلات</h6>
                </div>
            `;
        }
    }

    // عرض الأخطاء
    const errorsContent = document.getElementById('errorsContent');
    if (errorsContent) {
        if (data.employee_errors && data.employee_errors.length > 0) {
            // حساب عدد الأخطاء حسب النوع
            const criticalErrors = data.employee_errors.filter(e => e.error_type === 'critical').length;
            const normalErrors = data.employee_errors.filter(e => e.error_type === 'normal').length;

            let errorsHtml = `
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>إجمالي الأخطاء:</strong> ${data.employee_errors.length} خطأ
                    ${criticalErrors > 0 ? `<span class="badge bg-danger ms-2">${criticalErrors} جوهري</span>` : ''}
                    ${normalErrors > 0 ? `<span class="badge bg-warning text-dark ms-2">${normalErrors} عادي</span>` : ''}
                </div>
            `;
            data.employee_errors.forEach(error => {
                const errorTypeColor = error.error_type === 'critical' ? 'danger' : 'warning';
                const errorIcon = error.error_type === 'critical' ? 'fa-skull-crossbones' : 'fa-exclamation-circle';

                errorsHtml += `
                    <div class="error-item mb-3 border border-${errorTypeColor} rounded p-3" style="background: ${error.error_type === 'critical' ? 'rgba(220, 53, 69, 0.05)' : 'rgba(255, 193, 7, 0.05)'};">
                        <div class="error-header d-flex justify-content-between align-items-start mb-2">
                            <div class="error-info flex-grow-1">
                                <h6 class="error-title mb-1 text-${errorTypeColor}">
                                    <i class="fas ${errorIcon} me-1"></i>
                                    ${error.title}
                                </h6>
                                <p class="error-description mb-2 small text-muted">${error.description}</p>
                            </div>
                            <span class="badge bg-${errorTypeColor}">${error.error_type_text}</span>
                        </div>
                        <div class="error-meta border-top pt-2">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-tag me-1"></i>${error.error_category_text}
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>${error.reported_by}
                                    </small>
                                </div>
                                <div class="col-12 mt-1">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>${error.created_at}
                                        <span class="ms-2">(${error.created_at_human})</span>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            errorsContent.innerHTML = errorsHtml;
        } else {
            errorsContent.innerHTML = `
                <div class="empty-state text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h6 class="text-success">لا توجد أخطاء مسجلة</h6>
                    <p class="text-muted">أداء ممتاز بدون أخطاء في هذا الشهر</p>
                </div>
            `;
        }
    }

    // عرض المشاريع المتأخرة
    const delayedProjectsContent = document.getElementById('delayedProjectsContent');
    if (delayedProjectsContent) {
        if (data.delayed_projects && data.delayed_projects.length > 0) {
            // حساب إجمالي أيام التأخير
            const totalDelayDays = data.delayed_projects.reduce((sum, p) => sum + (p.delay_days || 0), 0);
            const avgDelay = Math.round(totalDelayDays / data.delayed_projects.length);

            let projectsHtml = `
                <div class="alert alert-danger mb-3">
                    <i class="fas fa-clock me-2"></i>
                    <strong>المشاريع المتأخرة:</strong> ${data.delayed_projects.length} مشروع
                    <span class="badge bg-dark ms-2">متوسط التأخير: ${avgDelay} يوم</span>
                </div>
            `;
            data.delayed_projects.forEach(project => {
                const badgeClass = project.is_delivered ? 'bg-warning text-dark' : 'bg-danger';
                const statusText = project.is_delivered ? 'تم التسليم متأخراً' : 'لم يسلم بعد (متأخر)';
                const statusIcon = project.is_delivered ? 'fa-clock' : 'fa-exclamation-triangle';

                projectsHtml += `
                    <div class="delayed-project-item mb-3 border-start border-${project.is_delivered ? 'warning' : 'danger'} border-3">
                        <div class="project-header d-flex justify-content-between align-items-start mb-2">
                            <div class="project-info flex-grow-1">
                                <h6 class="project-name mb-1">
                                    <i class="fas fa-project-diagram me-1"></i>
                                    ${project.project_name}
                                </h6>
                                <div class="project-details">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>${project.client_name}
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>الموعد النهائي: <strong>${project.deadline}</strong>
                                    </small>
                                    <br>
                                    <small class="${project.is_delivered ? 'text-warning' : 'text-danger'}">
                                        <i class="fas fa-calendar-check me-1"></i>التسليم: <strong>${project.delivery_date}</strong>
                                    </small>
                                </div>
                            </div>
                            <div class="delay-info text-end">
                                <span class="badge ${badgeClass} mb-1">
                                    <i class="fas ${statusIcon} me-1"></i>
                                    ${project.delay_days} يوم تأخير
                                </span>
                                <br>
                                <small class="badge bg-secondary">${statusText}</small>
                            </div>
                        </div>
                        <div class="project-status mt-2">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar ${project.is_delivered ? 'bg-warning' : 'bg-danger'}"
                                     style="width: 100%"></div>
                            </div>
                            <small class="${project.is_delivered ? 'text-warning' : 'text-danger'} mt-1 d-block">
                                <i class="fas fa-info-circle me-1"></i>
                                ${project.is_delivered
                                    ? `سُلّم متأخراً بـ ${project.delay_days} أيام`
                                    : `متأخر ${project.delay_days} يوم ولم يسلم بعد`}
                            </small>
                        </div>
                    </div>
                `;
            });
            delayedProjectsContent.innerHTML = projectsHtml;
        } else {
            delayedProjectsContent.innerHTML = `
                <div class="empty-state text-center py-4">
                    <i class="fas fa-clock fa-3x text-success mb-3"></i>
                    <h6 class="text-success">لا توجد مشاريع متأخرة</h6>
                    <p class="text-muted">جميع المشاريع تم تسليمها في الوقت المحدد</p>
                </div>
            `;
        }
    }

    // عرض المهام المتأخرة
    const delayedTasksContent = document.getElementById('delayedTasksContent');
    if (delayedTasksContent) {
        if (data.delayed_tasks && data.delayed_tasks.length > 0) {
            // حساب إجمالي ساعات التأخير
            const totalDelayHours = data.delayed_tasks.reduce((sum, t) => sum + (t.delay_hours || 0), 0);
            const avgDelay = Math.round(totalDelayHours / data.delayed_tasks.length);

            let tasksHtml = `
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-tasks me-2"></i>
                    <strong>المهام المتأخرة:</strong> ${data.delayed_tasks.length} مهمة
                    <span class="badge bg-dark ms-2">متوسط التأخير: ${avgDelay} ساعة</span>
                </div>
            `;
            data.delayed_tasks.forEach(task => {
                const badgeClass = task.is_completed ? 'bg-warning text-dark' : 'bg-danger';
                const statusText = task.is_completed ? 'اكتملت متأخرة' : 'لم تكتمل بعد (متأخرة)';
                const statusIcon = task.is_completed ? 'fa-clock' : 'fa-exclamation-triangle';

                tasksHtml += `
                    <div class="delayed-task-item mb-3 border-start border-${task.is_completed ? 'warning' : 'danger'} border-3">
                        <div class="task-header d-flex justify-content-between align-items-start mb-2">
                            <div class="task-info flex-grow-1">
                                <h6 class="task-name mb-1">
                                    <i class="fas fa-tasks me-1"></i>
                                    ${task.task_name}
                                </h6>
                                <div class="task-details">
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>الموعد النهائي: <strong>${task.due_date}</strong>
                                    </small>
                                    <br>
                                    <small class="${task.is_completed ? 'text-warning' : 'text-danger'}">
                                        <i class="fas fa-calendar-check me-1"></i>الإنجاز: <strong>${task.completed_date}</strong>
                                    </small>
                                </div>
                            </div>
                            <div class="delay-info text-end">
                                <span class="badge ${badgeClass} mb-1">
                                    <i class="fas ${statusIcon} me-1"></i>
                                    ${task.delay_hours} ساعة تأخير
                                </span>
                                <br>
                                <small class="badge bg-secondary">${statusText}</small>
                            </div>
                        </div>
                        <div class="task-type mt-2">
                            <span class="badge bg-${task.task_type === 'regular' ? 'primary' : 'secondary'} me-2">
                                ${task.task_type === 'regular' ? 'مهمة عادية' : 'مهمة قالب'}
                            </span>
                            ${task.project_name ? `<span class="badge bg-info">${task.project_name}</span>` : ''}
                        </div>
                    </div>
                `;
            });
            delayedTasksContent.innerHTML = tasksHtml;
        } else {
            delayedTasksContent.innerHTML = `
                <div class="empty-state text-center py-4">
                    <i class="fas fa-tasks fa-3x text-success mb-3"></i>
                    <h6 class="text-success">لا توجد مهام متأخرة</h6>
                    <p class="text-muted">جميع المهام تم إنجازها في الوقت المحدد</p>
                </div>
            `;
        }
    }

    // عرض المهام المنقولة
    const transferredTasksContent = document.getElementById('transferredTasksContent');
    if (transferredTasksContent) {
        if (data.transferred_tasks && data.transferred_tasks.length > 0) {
            let transferredHtml = `
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exchange-alt me-2"></i>
                    <strong>المهام المنقولة:</strong> ${data.transferred_tasks.length} مهمة تم نقلها
                </div>
            `;
            data.transferred_tasks.forEach(task => {
                transferredHtml += `
                    <div class="transferred-task-item mb-3 border-start border-warning border-3">
                        <div class="task-header d-flex justify-content-between align-items-start mb-2">
                            <div class="task-info flex-grow-1">
                                <h6 class="task-name mb-1">
                                    <i class="fas fa-exchange-alt me-1 text-warning"></i>
                                    ${task.task_name}
                                </h6>
                                <div class="task-details">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>نُقلت إلى: <strong>${task.transferred_to}</strong>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>تاريخ النقل: <strong>${task.transferred_at}</strong>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>الموعد النهائي الأصلي: <strong>${task.due_date}</strong>
                                    </small>
                                </div>
                            </div>
                            <div class="transfer-info text-end">
                                <span class="badge bg-warning text-dark mb-1">
                                    <i class="fas fa-exchange-alt me-1"></i>
                                    منقولة
                                </span>
                            </div>
                        </div>
                        <div class="transfer-reason mt-2">
                            <div class="alert alert-light mb-0 py-2">
                                <small><strong>سبب النقل:</strong> ${task.transfer_reason}</small>
                            </div>
                        </div>
                        <div class="task-type mt-2">
                            <span class="badge bg-${task.task_type === 'regular' ? 'primary' : 'secondary'}">
                                ${task.task_type === 'regular' ? 'مهمة عادية' : 'مهمة قالب'}
                            </span>
                        </div>
                    </div>
                `;
            });
            transferredTasksContent.innerHTML = transferredHtml;
        } else {
            transferredTasksContent.innerHTML = `
                <div class="empty-state text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h6 class="text-success">لا توجد مهام منقولة</h6>
                    <p class="text-muted">لم يتم نقل أي مهام في هذه الفترة</p>
                </div>
            `;
        }
    }

    // عرض المشاريع المسلّمة
    const deliveredProjectsContent = document.getElementById('deliveredProjectsContent');
    if (deliveredProjectsContent) {
        if (data.delivered_projects && data.delivered_projects.length > 0) {
            let projectsHtml = `
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>عدد المشاريع المسلّمة:</strong> ${data.delivered_projects.length} مشروع
                </div>
            `;
            
            data.delivered_projects.forEach(project => {
                projectsHtml += `
                    <div class="delivered-project-item mb-3 border rounded p-3" style="background: rgba(40, 167, 69, 0.05); border-color: rgba(40, 167, 69, 0.3) !important;">
                        <div class="project-header d-flex justify-content-between align-items-start mb-2">
                            <div class="project-info flex-grow-1">
                                <h6 class="project-name mb-1">
                                    <i class="fas fa-project-diagram text-success me-2"></i>${project.project_name}
                                    ${project.project_code ? `<span class="badge bg-secondary ms-2">${project.project_code}</span>` : ''}
                                </h6>
                                <div class="project-details">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>${project.client_name} |
                                        <i class="fas fa-cog me-1"></i>${project.service_name} |
                                        <i class="fas fa-calendar-check me-1"></i>${project.delivered_at_formatted || project.delivered_at}
                                    </small>
                                </div>
                            </div>
                            <div class="approval-status">
                                ${project.has_all_approvals ? 
                                    '<span class="badge bg-success"><i class="fas fa-check-double me-1"></i>مكتمل الاعتماد</span>' : 
                                    '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>في انتظار الاعتماد</span>'
                                }
                            </div>
                        </div>
                        
                        <!-- Approval Notes Section -->
                        <div class="approval-notes mt-3 pt-2 border-top">
                            ${project.needs_administrative ? `
                                <div class="approval-note-item mb-2">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-user-tie text-primary me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <strong class="d-block mb-1">ملاحظة الاعتماد الإداري:</strong>
                                            <div class="note-content p-2 bg-light rounded">
                                                ${project.administrative_note || 'لا يوجد'}
                                            </div>
                                            ${project.has_administrative_approval && project.administrative_approver_name ? `
                                                <small class="text-muted d-block mt-1">
                                                    اعتمدها: ${project.administrative_approver_name}
                                                    ${project.administrative_approval_at ? ` - ${project.administrative_approval_at}` : ''}
                                                </small>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${project.needs_technical ? `
                                <div class="approval-note-item mb-2">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-tools text-info me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <strong class="d-block mb-1">ملاحظة الاعتماد الفني:</strong>
                                            <div class="note-content p-2 bg-light rounded">
                                                ${project.technical_note || 'لا يوجد'}
                                            </div>
                                            ${project.has_technical_approval && project.technical_approver_name ? `
                                                <small class="text-muted d-block mt-1">
                                                    اعتمدها: ${project.technical_approver_name}
                                                    ${project.technical_approval_at ? ` - ${project.technical_approval_at}` : ''}
                                                </small>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                            
                            ${!project.needs_administrative && !project.needs_technical ? `
                                <div class="text-muted text-center py-2">
                                    <small><i class="fas fa-info-circle me-1"></i>لا يتطلب هذا المشروع اعتمادات إدارية أو فنية</small>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            deliveredProjectsContent.innerHTML = projectsHtml;
        } else {
            deliveredProjectsContent.innerHTML = `
                <div class="empty-state text-center py-4">
                    <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">لا توجد مشاريع مسلّمة</h6>
                    <p class="text-muted">لم يتم تسليم أي مشاريع في هذه الفترة</p>
                </div>
            `;
        }
    }
}

/**
 * عرض فترة التقييم في السايدبار
 */
function displaySidebarPeriod(month) {
    const periodBanner = document.getElementById('periodInfoBanner');
    const periodDates = document.getElementById('sidebarPeriodDates');
    const evaluationTypeBadge = document.getElementById('sidebarEvaluationType');

    if (!periodBanner || !periodDates || !evaluationTypeBadge) return;

    // الحصول على نوع التقييم الحالي
    const evaluationTypeSelector = document.getElementById('evaluationTypeSelector');
    const evaluationType = evaluationTypeSelector ? evaluationTypeSelector.value : 'monthly';

    // حساب الفترة
    const period = calculateEvaluationPeriod(month, evaluationType);

    // عرض المعلومات
    periodDates.textContent = `من ${period.startFormatted} إلى ${period.endFormatted}`;

    if (evaluationType === 'bi_weekly') {
        evaluationTypeBadge.textContent = 'تقييم نصف شهري';
        evaluationTypeBadge.className = 'badge bg-warning text-dark';
    } else {
        evaluationTypeBadge.textContent = 'تقييم شهري';
        evaluationTypeBadge.className = 'badge bg-info';
    }

    // إظهار البانر
    periodBanner.style.display = 'block';
}

// إغلاق Sidebar عند الضغط على Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && detailsSidebarOpen) {
        closeDetailsSidebar();
    }
});

