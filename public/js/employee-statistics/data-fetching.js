function sanitizeHTML(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setInnerHTML(element, html) {
    if (typeof DOMPurify !== 'undefined') {
        element.innerHTML = DOMPurify.sanitize(html);
    } else {
        console.warn('DOMPurify is not available. Using basic sanitization instead.');

        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
        element.textContent = "Content cannot be displayed safely without DOMPurify. Please check your console.";
        console.error("Raw content could not be displayed safely:", html);
    }
}

function getMonthsDifference(startDate, endDate) {
    // Convert dates to Date objects
    const start = new Date(startDate);
    const end = new Date(endDate);

    // Calculate months difference
    let months = (end.getFullYear() - start.getFullYear()) * 12;
    months += end.getMonth() - start.getMonth();

    // التعامل مع حالة الشهر الواحد المقسم على شهرين ميلاديين
    if (months < 1 && start.getMonth() !== end.getMonth()) {
        months = 1;
    }

    // Ensure value is at least 1
    return Math.max(1, months);
}

function showDetails(employeeId) {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    // Calculate months difference for acceptable thresholds
    const monthsDifference = getMonthsDifference(startDate, endDate);
    const maxAcceptableDelays = 120 * monthsDifference; // 120 minutes per month
    const maxAcceptablePermissions = 180 * monthsDifference; // 180 minutes per month

    // تقريب الأرقام للعرض
    const displayMonthsDifference = Math.round(monthsDifference);
    const displayMaxAcceptableDelays = Math.round(maxAcceptableDelays);
    const displayMaxAcceptablePermissions = Math.round(maxAcceptablePermissions);

    fetch(`/employee-statistics/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            const content = document.getElementById('modalContent');
            let html = `
                <div class="text-center mb-4">
                    <h4>${sanitizeHTML(data.employee.name)}</h4>
                    <small class="text-muted">${sanitizeHTML(data.employee.employee_id)}</small>
                    <div class="mt-2">${sanitizeHTML(data.employee.department || 'غير محدد')}</div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted mb-2">أيام العمل</div>
                            <span class="badge bg-secondary">
                                ${sanitizeHTML(data.statistics.total_working_days)} يوم
                            </span>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted mb-2">أيام الحضور</div>
                            <span class="badge bg-primary">
                                ${sanitizeHTML(data.statistics.actual_attendance_days)} يوم
                            </span>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="text-center">
                            <div class="text-muted mb-2">نسبة الحضور</div>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar ${
                                    data.statistics.attendance_percentage >= 90 ? 'bg-success' :
                                    (data.statistics.attendance_percentage >= 75 ? 'bg-warning' : 'bg-danger')
                                }"
                                role="progressbar"
                                style="width: ${sanitizeHTML(data.statistics.attendance_percentage)}%">
                                    ${sanitizeHTML(data.statistics.attendance_percentage)}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="text-center">
                            <div class="text-muted mb-2">الغياب</div>
                            <span class="badge bg-${data.statistics.absences > 0 ? 'danger' : 'success'}"
                                style="cursor: pointer;"
                                onclick="showAbsenceDetails('${sanitizeHTML(data.employee.employee_id)}', '${sanitizeHTML(startDate)}', '${sanitizeHTML(endDate)}')">
                                ${sanitizeHTML(data.statistics.absences)} أيام
                            </span>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="text-center">
                            <div class="text-muted mb-2">الأذونات</div>
                            <span class="badge bg-info"
                                style="cursor: pointer;"
                                onclick="showPermissionDetails('${sanitizeHTML(data.employee.employee_id)}', '${sanitizeHTML(startDate)}', '${sanitizeHTML(endDate)}')">
                                ${sanitizeHTML(data.statistics.permissions)} مرات
                            </span>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="text-center">
                            <div class="text-muted mb-2">الوقت الإضافي</div>
                            <span class="badge bg-primary"
                                style="cursor: pointer;"
                                onclick="showOvertimeDetails('${sanitizeHTML(data.employee.employee_id)}', '${sanitizeHTML(startDate)}', '${sanitizeHTML(endDate)}')">
                                ${sanitizeHTML(data.statistics.overtimes)} ساعات
                            </span>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted mb-2">إجمالي التأخير</div>
                            <span class="badge bg-${data.statistics.delays > 0 ? 'warning' : 'success'}">
                                ${sanitizeHTML(data.statistics.delays)} دقيقة
                            </span>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted mb-2">الإجازات المأخوذة</div>
                            <div>
                                <span class="badge bg-info"
                                    style="cursor: pointer;"
                                    onclick="showLeaveDetails('${sanitizeHTML(data.employee.employee_id)}', '${sanitizeHTML(startDate)}', '${sanitizeHTML(endDate)}')">
                                    ${sanitizeHTML(data.statistics.taken_leaves)} يوم
                                </span>
                                <small class="text-muted d-block mt-1">من أصل ${sanitizeHTML(data.employee.max_allowed_absence_days)} يوم</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted mb-2">الإجازات المتبقية</div>
                            <span class="badge ${data.statistics.remaining_leaves > 0 ? 'bg-success' : 'bg-danger'}">
                                ${sanitizeHTML(data.statistics.remaining_leaves)} يوم
                            </span>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="text-center">
                            <div class="text-muted mb-2">إجازات الشهر الحالي</div>
                            <div>
                                <span class="badge bg-purple"
                                    style="cursor: pointer;"
                                    onclick="showCurrentMonthLeaves('${sanitizeHTML(data.employee.employee_id)}', '${sanitizeHTML(startDate)}', '${sanitizeHTML(endDate)}')">
                                    ${sanitizeHTML(data.statistics.current_month_leaves)} يوم
                                </span>
                                <small class="text-muted d-block mt-1">
                                    ${new Date(startDate).toLocaleDateString('ar', { day: '2-digit', month: '2-digit' })} -
                                    ${new Date(endDate).toLocaleDateString('ar', { day: '2-digit', month: '2-digit' })}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- سجل الحضور التفصيلي -->
                <div class="mt-4">
                    <h6 class="border-bottom pb-2">
                        <i class="fas fa-calendar-check me-2"></i>سجل الحضور التفصيلي
                    </h6>
                    <div class="list-group mt-3">
                        ${data.statistics.attendance.map(record => `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>${sanitizeHTML(record.attendance_date)}</span>
                                    <span class="badge ${
                                        record.status === 'حضـور' ? 'bg-success' :
                                        record.status === 'غيــاب' ? 'bg-danger' :
                                        record.status === 'عطله إسبوعية' ? 'bg-info' : 'bg-secondary'
                                    }">${sanitizeHTML(record.status)}</span>
                                </div>
                                ${record.entry_time ? `
                                    <div class="small mt-1">
                                        <span>الدخول: ${sanitizeHTML(record.entry_time)}</span>
                                        ${record.exit_time ? `<span class="ms-2">الخروج: ${sanitizeHTML(record.exit_time)}</span>` : ''}
                                        ${record.delay_minutes > 0 ? `
                                            <span class="text-warning ms-2">
                                                <i class="fas fa-clock"></i> تأخير: ${sanitizeHTML(record.delay_minutes)} دقيقة
                                            </span>
                                        ` : ''}
                                    </div>
                                ` : ''}
                            </div>
                        `).join('')}
                    </div>
                </div>

                <!-- تفاصيل النقاط المخصومة -->
                ${data.statistics.attendance_percentage < 100 || data.statistics.delays > maxAcceptableDelays ? `
                <div class="mt-4">
                    <h6 class="border-bottom pb-2">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>تفاصيل النقاط المخصومة
                    </h6>

                    <div class="card mt-3 border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">تفاصيل تقييم الأداء</h6>

                            <div class="row">
                                <!-- مؤشر الحضور -->
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 ${data.statistics.attendance_percentage < 80 ? 'border-danger' : (data.statistics.attendance_percentage < 90 ? 'border-warning' : 'border-success')}">
                                        <div class="card-body p-3 text-center">
                                            <h6 class="card-title mb-1">الحضور</h6>
                                            <h3 class="mb-0 ${data.statistics.attendance_percentage < 80 ? 'text-danger' : (data.statistics.attendance_percentage < 90 ? 'text-warning' : 'text-success')}">
                                                ${sanitizeHTML(data.statistics.attendance_percentage)}%
                                            </h3>
                                            <small class="text-muted">الوزن: 45% من التقييم الكلي</small>

                                            ${data.statistics.attendance_percentage < 100 ? `
                                            <div class="alert alert-light mt-2 mb-0 p-2 text-start">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    تم خصم <strong>${sanitizeHTML(100 - data.statistics.attendance_percentage)}%</strong> بسبب
                                                    الغياب لمدة <strong>${sanitizeHTML(data.statistics.total_working_days - data.statistics.actual_attendance_days)} أيام</strong>
                                                </small>
                                            </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>

                                <!-- مؤشر الانضباط -->
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 ${data.statistics.delays > maxAcceptableDelays ? 'border-danger' : 'border-success'}">
                                        <div class="card-body p-3 text-center">
                                            <h6 class="card-title mb-1">الانضباط</h6>
                                            <h3 class="mb-0 ${data.statistics.delays > maxAcceptableDelays ? 'text-danger' : 'text-success'}">
                                                ${sanitizeHTML(data.statistics.delays <= maxAcceptableDelays ? '100%' : Math.max(0, Math.round(100 - ((data.statistics.delays - maxAcceptableDelays) / maxAcceptableDelays) * 100)) + '%')}
                                            </h3>
                                            <small class="text-muted">الوزن: 20% من التقييم الكلي</small>

                                            ${data.statistics.delays > maxAcceptableDelays ? `
                                            <div class="alert alert-light mt-2 mb-0 p-2 text-start">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    تم خصم <strong>${sanitizeHTML(Math.min(100, Math.round(((data.statistics.delays - maxAcceptableDelays) / maxAcceptableDelays) * 100)))}%</strong> بسبب
                                                    تجاوز التأخير <strong>${sanitizeHTML(Math.round(data.statistics.delays - maxAcceptableDelays))} دقيقة</strong> عن الحد المسموح (${sanitizeHTML(displayMaxAcceptableDelays)} دقيقة لفترة ${sanitizeHTML(displayMonthsDifference)} شهر)
                                                </small>
                                            </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>

                                <!-- مؤشر ساعات العمل -->
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 border-info">
                                        <div class="card-body p-3 text-center">
                                            <h6 class="card-title mb-1">ساعات العمل</h6>
                                            <h3 class="mb-0 text-info">
                                                ${typeof data.statistics.average_working_hours !== 'undefined' ?
                                                sanitizeHTML(Math.min(100, Math.round((data.statistics.average_working_hours / 8) * 100)) + '%') :
                                                '-'}
                                            </h3>
                                            <small class="text-muted">الوزن: 35% من التقييم الكلي</small>

                                            ${typeof data.statistics.average_working_hours !== 'undefined' && data.statistics.average_working_hours < 8 ? `
                                            <div class="alert alert-light mt-2 mb-0 p-2 text-start">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    متوسط ساعات العمل <strong>${sanitizeHTML(data.statistics.average_working_hours)}</strong> من أصل <strong>8</strong> ساعات
                                                </small>
                                            </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- تأثير النقاط المخصومة -->
                            <div class="mt-3 pt-3 border-top">
                                <h6 class="mb-3">التأثير على النتيجة النهائية</h6>

                                <ul class="list-unstyled mb-0">
                                    ${data.statistics.attendance_percentage < 100 ? `
                                    <li class="mb-2">
                                        <i class="fas fa-minus-circle text-danger me-1"></i>
                                        خصم <strong>${sanitizeHTML(Math.round((100 - data.statistics.attendance_percentage) * 0.45, 1))}%</strong>
                                        من التقييم النهائي بسبب الغياب
                                    </li>
                                    ` : ''}

                                    ${data.statistics.delays > maxAcceptableDelays ? `
                                    <li class="mb-2">
                                        <i class="fas fa-minus-circle text-danger me-1"></i>
                                        خصم <strong>${sanitizeHTML(Math.round(Math.min(100, ((data.statistics.delays - maxAcceptableDelays) / maxAcceptableDelays) * 100) * 0.2, 1))}%</strong>
                                        من التقييم النهائي بسبب التأخير
                                    </li>
                                    ` : ''}

                                    ${typeof data.statistics.average_working_hours !== 'undefined' && data.statistics.average_working_hours < 8 ? `
                                    <li class="mb-2">
                                        <i class="fas fa-minus-circle text-danger me-1"></i>
                                        خصم <strong>${sanitizeHTML(Math.round((100 - Math.min(100, Math.round((data.statistics.average_working_hours / 8) * 100))) * 0.35, 1))}%</strong>
                                        من التقييم النهائي بسبب قلة ساعات العمل
                                    </li>
                                    ` : ''}
                                </ul>
                            </div>

                            <!-- النتيجة النهائية -->
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">النتيجة النهائية</h6>

                                    <div class="text-center">
                                        <span class="badge bg-${
                                            calculateOverallScore(data) >= 90 ? 'success' :
                                            (calculateOverallScore(data) >= 80 ? 'primary' :
                                            (calculateOverallScore(data) >= 70 ? 'info' :
                                            (calculateOverallScore(data) >= 60 ? 'warning' : 'danger')))
                                        } p-2 fs-6">
                                            ${sanitizeHTML(calculateOverallScore(data))}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                ` : ''}
            `;

            setInnerHTML(content, html);
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        });
}


function showAbsenceDetails(employeeId, startDate, endDate) {
    console.log('Fetching absences:', { employeeId, startDate, endDate });

    fetch(`/employee-statistics/absences/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received absences data:', data);
            const modalTitle = document.getElementById('detailsDataModalTitle');
            const content = document.getElementById('detailsDataContent');

            modalTitle.textContent = 'تفاصيل الغياب';

            if (!data || data.length === 0) {
                setInnerHTML(content, `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        لا يوجد غياب في هذه الفترة
                    </div>
                `);
            } else {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.map(record => `
                                    <tr>
                                        <td>${sanitizeHTML(record.date)}</td>
                                        <td>${sanitizeHTML(record.reason)}</td>
                                        <td>
                                            <span class="badge bg-danger">
                                                ${sanitizeHTML(record.status)}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                setInnerHTML(content, html);
            }

            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            const content = document.getElementById('detailsDataContent');
            setInnerHTML(content, `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    حدث خطأ أثناء جلب البيانات
                </div>
            `);
            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        });
}

// دالة عرض تفاصيل الإذونات
function showPermissionDetails(employeeId, startDate, endDate) {
    console.log('Fetching permissions:', { employeeId, startDate, endDate });

    fetch(`/employee-statistics/permissions/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received permissions data:', data);
            const modalTitle = document.getElementById('detailsDataModalTitle');
            const content = document.getElementById('detailsDataContent');

            modalTitle.textContent = 'تفاصيل الأذونات';

            if (!data || data.length === 0) {
                setInnerHTML(content, `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        لا توجد إذونات في هذه الفترة
                    </div>
                `);
            } else {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>وقت المغادرة</th>
                                    <th>وقت العودة</th>
                                    <th>عدد الساعات</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.map(record => `
                                    <tr>
                                        <td>${sanitizeHTML(record.date)}</td>
                                        <td>${sanitizeHTML(record.departure_time)}</td>
                                        <td>${sanitizeHTML(record.return_time)}</td>
                                        <td>${sanitizeHTML(record.minutes)} دقيقة</td>
                                        <td>${sanitizeHTML(record.reason || 'غير محدد')}</td>
                                        <td>
                                            <span class="badge bg-success">
                                                ${sanitizeHTML(record.status)}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                setInnerHTML(content, html);
            }

            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            const content = document.getElementById('detailsDataContent');
            setInnerHTML(content, `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    حدث خطأ أثناء جلب البيانات
                </div>
            `);
            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        });
}

// دالة عرض تفاصيل الوقت الإضافي
function showOvertimeDetails(employeeId, startDate, endDate) {
    console.log('Fetching overtimes:', { employeeId, startDate, endDate });

    fetch(`/employee-statistics/overtimes/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received overtimes data:', data);
            const modalTitle = document.getElementById('detailsDataModalTitle');
            const content = document.getElementById('detailsDataContent');

            modalTitle.textContent = 'تفاصيل الوقت الإضافي';

            if (!data || data.length === 0) {
                setInnerHTML(content, `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        لا يوجد وقت إضافي في هذه الفترة
                    </div>
                `);
            } else {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>وقت البداية</th>
                                    <th>وقت النهاية</th>
                                    <th>عدد الساعات</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.map(record => `
                                    <tr>
                                        <td>${sanitizeHTML(record.date)}</td>
                                        <td>${sanitizeHTML(record.start_time)}</td>
                                        <td>${sanitizeHTML(record.end_time)}</td>
                                        <td>${sanitizeHTML(record.minutes)} دقيقة</td>
                                        <td>${sanitizeHTML(record.reason || 'غير محدد')}</td>
                                        <td>
                                            <span class="badge bg-success">
                                                ${sanitizeHTML(record.status)}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                setInnerHTML(content, html);
            }

            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            const content = document.getElementById('detailsDataContent');
            setInnerHTML(content, `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    حدث خطأ أثناء جلب البيانات
                </div>
            `);
            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        });
}

function showLeaveDetails(employeeId, startDate, endDate) {
    fetch(`/employee-statistics/leaves/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const modalTitle = document.getElementById('detailsDataModalTitle');
            const content = document.getElementById('detailsDataContent');

            const year = new Date(startDate).getFullYear();
            modalTitle.textContent = `تفاصيل الإجازات لسنة ${year}`;

            if (!data || data.length === 0) {
                setInnerHTML(content, `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        لا توجد إجازات في هذه السنة
                    </div>
                `);
            } else {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.map(record => `
                                    <tr>
                                        <td>${sanitizeHTML(record.date)}</td>
                                        <td>${sanitizeHTML(record.reason || 'غير محدد')}</td>
                                        <td>
                                            <span class="badge bg-success">
                                                ${sanitizeHTML(record.status)}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                setInnerHTML(content, html);
            }

            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            const content = document.getElementById('detailsDataContent');
            setInnerHTML(content, `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    حدث خطأ أثناء جلب البيانات
                </div>
            `);
            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        });
}

// دالة عرض تفاصيل إجازات الشهر الحالي
function showCurrentMonthLeaves(employeeId, startDate, endDate) {
    console.log('Fetching leaves for:', { employeeId, startDate, endDate });

    fetch(`/employee-statistics/current-month-leaves/${employeeId}?start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            const modalTitle = document.getElementById('detailsDataModalTitle');
            const content = document.getElementById('detailsDataContent');

            modalTitle.textContent = 'تفاصيل إجازات الشهر الحالي';

            if (!data || data.length === 0) {
                setInnerHTML(content, `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        لا توجد إجازات في هذه الفترة
                    </div>
                `);
            } else {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.map(record => `
                                    <tr>
                                        <td>${sanitizeHTML(record.date)}</td>
                                        <td>${sanitizeHTML(record.reason || 'غير محدد')}</td>
                                        <td>
                                            <span class="badge bg-success">
                                                ${sanitizeHTML(record.status)}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
                setInnerHTML(content, html);
            }

            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            const content = document.getElementById('detailsDataContent');
            setInnerHTML(content, `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    حدث خطأ أثناء جلب البيانات
                </div>
            `);
            new bootstrap.Modal(document.getElementById('detailsDataModal')).show();
        });
}
