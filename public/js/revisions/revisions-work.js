// ====================================
// 🎯 Revisions Page - Work Actions & Timers
// ====================================

// Get action buttons based on revision status (for sidebar)
function getRevisionActionButtons(revision) {
    let buttons = '';

    switch(revision.status) {
        case 'new':
            buttons = `
                <button class="btn btn-success" onclick="startRevisionWork(${revision.id})">
                    <i class="fas fa-play me-1"></i>بدء العمل
                </button>
            `;
            break;

        case 'in_progress':
            buttons = `
                <button class="btn btn-warning" onclick="pauseRevisionWork(${revision.id})">
                    <i class="fas fa-pause me-1"></i>إيقاف مؤقت
                </button>
                <button class="btn btn-success" onclick="completeRevisionWork(${revision.id})">
                    <i class="fas fa-check me-1"></i>إكمال
                </button>
            `;
            break;

        case 'paused':
            buttons = `
                <button class="btn btn-primary" onclick="resumeRevisionWork(${revision.id})">
                    <i class="fas fa-play me-1"></i>استئناف
                </button>
                <button class="btn btn-success" onclick="completeRevisionWork(${revision.id})">
                    <i class="fas fa-check me-1"></i>إكمال
                </button>
            `;
            break;

        case 'completed':
            buttons = `<span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>مكتمل</span>`;
            break;
    }

    return buttons;
}

// Get compact action buttons for table (icons only)
function getRevisionActionButtonsCompact(revision) {
    let buttons = '';

    switch(revision.status) {
        case 'new':
            buttons = `
                <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); startRevisionWork(${revision.id});" title="بدء العمل">
                    <i class="fas fa-play"></i>
                </button>
            `;
            break;

        case 'in_progress':
            buttons = `
                <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); pauseRevisionWork(${revision.id});" title="إيقاف مؤقت">
                    <i class="fas fa-pause"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); completeRevisionWork(${revision.id});" title="إكمال">
                    <i class="fas fa-check"></i>
                </button>
            `;
            break;

        case 'paused':
            buttons = `
                <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); resumeRevisionWork(${revision.id});" title="استئناف">
                    <i class="fas fa-play"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); completeRevisionWork(${revision.id});" title="إكمال">
                    <i class="fas fa-check"></i>
                </button>
            `;
            break;

        case 'completed':
            buttons = `
                <span class="badge bg-success">
                    <i class="fas fa-check-circle me-1"></i>تم
                </span>
            `;
            break;
    }

    return buttons;
}

// Start revision work
async function startRevisionWork(revisionId) {
    console.log('Starting revision work for ID:', revisionId);

    try {
        const response = await fetch(`/task-revisions/${revisionId}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        console.log('Response status:', response.status);
        const result = await response.json();
        console.log('Result:', result);

        if (result.success) {
            // ✅ استخدام SweetAlert2 للرسائل
            Swal.fire({
                title: 'نجح!',
                text: result.message || 'تم بدء العمل على التعديل',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
            // تحديث بدون loading
            setTimeout(() => {
                const activeTab = $('#revisionTabs .nav-link.active').attr('data-bs-target');
                if (activeTab === '#my-revisions') {
                    $.get('/revision-page/my-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'my');
                            initializeRevisionTimers();
                        }
                    });
                } else if (activeTab === '#my-created-revisions') {
                    $.get('/revision-page/my-created-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'myCreated');
                            initializeRevisionTimers();
                        }
                    });
                } else {
                    $.get('/revision-page/all-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'all');
                            initializeRevisionTimers();
                        }
                    });
                }
            }, 100);
        } else {
            // التحقق من وجود تعديل آخر نشط
            if (result.active_revision_id) {
                Swal.fire({
                    title: 'تنبيه!',
                    html: `
                        <p>${result.message}</p>
                        <p class="mt-2"><strong>التعديل النشط:</strong> ${result.active_revision_title}</p>
                    `,
                    icon: 'warning',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#3085d6',
                    customClass: {
                        popup: 'rtl-swal'
                    }
                });
            } else {
                Swal.fire({
                    title: 'خطأ!',
                    text: result.message || 'حدث خطأ',
                    icon: 'error',
                    confirmButtonText: 'حسناً',
                    customClass: {
                        popup: 'rtl-swal'
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error starting revision work:', error);
        Swal.fire({
            title: 'خطأ!',
            text: 'حدث خطأ في بدء العمل',
            icon: 'error',
            confirmButtonText: 'حسناً',
            customClass: {
                popup: 'rtl-swal'
            }
        });
    }
}

// Pause revision work
async function pauseRevisionWork(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/pause`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: 'تم الإيقاف!',
                text: `تم الإيقاف المؤقت. الوقت: ${result.session_minutes} دقيقة`,
                icon: 'info',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
            closeSidebar();
            // تحديث بدون loading
            setTimeout(() => {
                const activeTab = $('#revisionTabs .nav-link.active').attr('data-bs-target');
                if (activeTab === '#my-revisions') {
                    $.get('/revision-page/my-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'my');
                            initializeRevisionTimers();
                        }
                    });
                } else if (activeTab === '#my-created-revisions') {
                    $.get('/revision-page/my-created-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'myCreated');
                            initializeRevisionTimers();
                        }
                    });
                } else {
                    $.get('/revision-page/all-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'all');
                            initializeRevisionTimers();
                        }
                    });
                }
            }, 100);
        } else {
            Swal.fire({
                title: 'خطأ!',
                text: result.message,
                icon: 'error',
                confirmButtonText: 'حسناً',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
        }
    } catch (error) {
        console.error('Error pausing revision work:', error);
        Swal.fire({
            title: 'خطأ!',
            text: 'حدث خطأ في الإيقاف المؤقت',
            icon: 'error',
            confirmButtonText: 'حسناً',
            customClass: {
                popup: 'rtl-swal'
            }
        });
    }
}

// Resume revision work
async function resumeRevisionWork(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/resume`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: 'تم الاستئناف!',
                text: result.message || 'تم استئناف العمل',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
            closeSidebar();
            // تحديث بدون loading
            setTimeout(() => {
                const activeTab = $('#revisionTabs .nav-link.active').attr('data-bs-target');
                if (activeTab === '#my-revisions') {
                    $.get('/revision-page/my-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'my');
                            initializeRevisionTimers();
                        }
                    });
                } else if (activeTab === '#my-created-revisions') {
                    $.get('/revision-page/my-created-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'myCreated');
                            initializeRevisionTimers();
                        }
                    });
                } else {
                    $.get('/revision-page/all-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'all');
                            initializeRevisionTimers();
                        }
                    });
                }
            }, 100);
        } else {
            // التحقق من وجود تعديل آخر نشط
            if (result.active_revision_id) {
                Swal.fire({
                    title: 'تنبيه!',
                    html: `
                        <p>${result.message}</p>
                        <p class="mt-2"><strong>التعديل النشط:</strong> ${result.active_revision_title}</p>
                    `,
                    icon: 'warning',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#3085d6',
                    customClass: {
                        popup: 'rtl-swal'
                    }
                });
            } else {
                Swal.fire({
                    title: 'خطأ!',
                    text: result.message,
                    icon: 'error',
                    confirmButtonText: 'حسناً',
                    customClass: {
                        popup: 'rtl-swal'
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error resuming revision work:', error);
        Swal.fire({
            title: 'خطأ!',
            text: 'حدث خطأ في استئناف العمل',
            icon: 'error',
            confirmButtonText: 'حسناً',
            customClass: {
                popup: 'rtl-swal'
            }
        });
    }
}

// Complete revision work
async function completeRevisionWork(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: 'تم الإكمال!',
                text: `تم إكمال التعديل! الوقت الكلي: ${formatRevisionTimeInMinutes(result.total_minutes)}`,
                icon: 'success',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
            closeSidebar();
            // تحديث بدون loading
            setTimeout(() => {
                const activeTab = $('#revisionTabs .nav-link.active').attr('data-bs-target');
                if (activeTab === '#my-revisions') {
                    $.get('/revision-page/my-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'my');
                            initializeRevisionTimers();
                        }
                    });
                } else if (activeTab === '#my-created-revisions') {
                    $.get('/revision-page/my-created-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'myCreated');
                            initializeRevisionTimers();
                        }
                    });
                } else {
                    $.get('/revision-page/all-revisions').done(function(response) {
                        if (response.success) {
                            renderRevisions(response.revisions, 'all');
                            initializeRevisionTimers();
                        }
                    });
                }
            }, 100);
        } else {
            Swal.fire({
                title: 'خطأ!',
                text: result.message,
                icon: 'error',
                confirmButtonText: 'حسناً',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
        }
    } catch (error) {
        console.error('Error completing revision work:', error);
        Swal.fire({
            title: 'خطأ!',
            text: 'حدث خطأ في إكمال التعديل',
            icon: 'error',
            confirmButtonText: 'حسناً',
            customClass: {
                popup: 'rtl-swal'
            }
        });
    }
}

// ⏰ دوال التايمر للتعديلات

// تهيئة التايمرات للتعديلات قيد التنفيذ
function initializeRevisionTimers() {
    console.log('🔄 تهيئة التايمرات...');

    console.log('📊 revisionTimers object:', revisionTimers);

    // بدء التايمرات للتعديلات قيد التنفيذ (بدون إيقاف التايمرات السابقة)
    Object.keys(revisionTimers).forEach(revisionId => {
        console.log('🔍 فحص التعديل:', revisionId, 'حالته:', revisionTimers[revisionId].status);

        if (revisionTimers[revisionId].status === 'in_progress') {
            // فحص لو التايمر شغال فعلاً
            if (revisionTimerIntervals[revisionId]) {
                console.log('⏭️ التايمر شغال بالفعل للتعديل:', revisionId);
                return;
            }

            console.log('🚀 بدء تايمر جديد للتعديل:', revisionId, 'البيانات:', revisionTimers[revisionId]);
            startRevisionTimer(revisionId);

            setTimeout(() => {
                console.log('🔍 فحص الـ interval بعد الإنشاء للتعديل:', revisionId);
                console.log('⏱️ revisionTimerIntervals:', revisionTimerIntervals);
                console.log('🎯 هل الـ interval موجود؟', !!revisionTimerIntervals[revisionId]);
            }, 100);
        } else {
            console.log('⏭️ تم تخطي التعديل:', revisionId, 'حالته مش in_progress');
        }
    });

    console.log('✅ تم تهيئة تايمرات التنفيذ');

    // بدء تايمرات المراجعة
    console.log('📊 reviewTimers object:', reviewTimers);
    Object.keys(reviewTimers).forEach(revisionId => {
        console.log('🔍 فحص المراجعة:', revisionId, 'حالتها:', reviewTimers[revisionId].status);

        if (reviewTimers[revisionId].status === 'in_progress') {
            if (reviewTimerIntervals[revisionId]) {
                console.log('⏭️ تايمر المراجعة شغال بالفعل:', revisionId);
                return;
            }

            console.log('🚀 بدء تايمر مراجعة جديد:', revisionId);
            startReviewTimer(revisionId);
        } else {
            console.log('⏭️ تم تخطي المراجعة:', revisionId, 'حالتها مش in_progress');
        }
    });

    console.log('✅ تم تهيئة تايمرات المراجعة');

    // logging للتأكد من النتيجة النهائية
    console.log('🎉 النتيجة النهائية بعد التهيئة:');
    console.log('📊 revisionTimers:', revisionTimers);
    console.log('⏱️ revisionTimerIntervals:', revisionTimerIntervals);
    console.log('📊 reviewTimers:', reviewTimers);
    console.log('⏱️ reviewTimerIntervals:', reviewTimerIntervals);
    console.log('🎯 التايمرات النشطة (تنفيذ):', Object.keys(revisionTimerIntervals).length);
    console.log('🎯 التايمرات النشطة (مراجعة):', Object.keys(reviewTimerIntervals).length);
}

// بدء تايمر لتعديل معين
function startRevisionTimer(revisionId) {
    console.log('⏰ بدء تايمر للتعديل:', revisionId);
    console.log('🔍 البيانات الحالية للتعديل:', revisionTimers[revisionId]);

    if (revisionTimerIntervals[revisionId]) {
        console.log('🛑 إيقاف التايمر السابق للتعديل:', revisionId);
        clearInterval(revisionTimerIntervals[revisionId]);
        delete revisionTimerIntervals[revisionId];
    }

    console.log('🚀 إنشاء تايمر جديد للتعديل:', revisionId);
    console.log('⏱️ revisionTimers[revisionId] قبل الـ setInterval:', revisionTimers[revisionId]);

    const intervalId = setInterval(() => {
        console.log('🔄 تشغيل setInterval للتعديل:', revisionId);

        if (revisionTimers[revisionId]) {
            revisionTimers[revisionId].seconds++;
            console.log('⏱️ تحديث تايمر التعديل', revisionId, 'الثواني:', revisionTimers[revisionId].seconds);
            updateRevisionTimerDisplay(revisionId, revisionTimers[revisionId].seconds);
        } else {
            console.log('⚠️ مش موجود في revisionTimers أثناء التشغيل:', revisionId);
            console.log('📊 revisionTimers الحالي:', revisionTimers);
            clearInterval(intervalId);
            delete revisionTimerIntervals[revisionId];
        }
    }, 1000);

    revisionTimerIntervals[revisionId] = intervalId;

    console.log('✅ تم إنشاء التايمر للتعديل:', revisionId, 'مع intervalId:', intervalId);
    console.log('⏱️ revisionTimerIntervals بعد الإنشاء:', revisionTimerIntervals);
}

// إيقاف تايمر لتعديل معين
function stopRevisionTimer(revisionId) {
    if (revisionTimerIntervals[revisionId]) {
        clearInterval(revisionTimerIntervals[revisionId]);
        delete revisionTimerIntervals[revisionId];
    }
}

// إيقاف جميع التايمرات
function stopAllRevisionTimers() {
    Object.keys(revisionTimerIntervals).forEach(revisionId => {
        clearInterval(revisionTimerIntervals[revisionId]);
    });
    revisionTimerIntervals = {};
    revisionTimers = {};

    Object.keys(reviewTimerIntervals).forEach(revisionId => {
        clearInterval(reviewTimerIntervals[revisionId]);
    });
    reviewTimerIntervals = {};
    reviewTimers = {};
}

// ====================================
// ⏰ دوال تايمر المراجعة
// ====================================

// بدء تايمر للمراجعة
function startReviewTimer(revisionId) {
    console.log('⏰ بدء تايمر المراجعة:', revisionId);

    if (reviewTimerIntervals[revisionId]) {
        console.log('🛑 إيقاف تايمر المراجعة السابق:', revisionId);
        clearInterval(reviewTimerIntervals[revisionId]);
        delete reviewTimerIntervals[revisionId];
    }

    const intervalId = setInterval(() => {
        if (reviewTimers[revisionId]) {
            reviewTimers[revisionId].seconds++;
            updateReviewTimerDisplay(revisionId, reviewTimers[revisionId].seconds);
        } else {
            clearInterval(intervalId);
            delete reviewTimerIntervals[revisionId];
        }
    }, 1000);

    reviewTimerIntervals[revisionId] = intervalId;
    console.log('✅ تم إنشاء تايمر المراجعة:', revisionId);
}

// إيقاف تايمر المراجعة
function stopReviewTimer(revisionId) {
    if (reviewTimerIntervals[revisionId]) {
        clearInterval(reviewTimerIntervals[revisionId]);
        delete reviewTimerIntervals[revisionId];
    }
}

// تحديث عرض تايمر المراجعة
function updateReviewTimerDisplay(revisionId, seconds) {
    const timerElement = document.querySelector(`#review-timer-${revisionId}`);
    if (timerElement) {
        timerElement.textContent = formatRevisionTime(seconds);
    }
}

// تحديث عرض التايمر
function updateRevisionTimerDisplay(revisionId, seconds) {
    console.log('🎨 تحديث عرض التايمر للتعديل:', revisionId, 'الثواني:', seconds);

    const timerElement = document.querySelector(`#revision-timer-${revisionId}`);

    if (timerElement) {
        const formattedTime = formatRevisionTime(seconds);
        console.log('✏️ تحديث العنصر:', timerElement, 'بالوقت:', formattedTime);
        console.log('📍 موقع العنصر في الصفحة:', timerElement.getBoundingClientRect());
        timerElement.textContent = formattedTime;
        console.log('✅ تم تحديث النص للعنصر');
    } else {
        console.log('❌ مش لاقي العنصر #revision-timer-' + revisionId);

        // بحث أوسع عن العنصر
        const allTimerElements = document.querySelectorAll('[id*="timer"]');
        console.log('🔍 جميع العناصر اللي فيها timer في الـ ID:', allTimerElements.length);
        allTimerElements.forEach(el => {
            console.log('   - العنصر:', el.id, 'النص:', el.textContent);
        });

        // بحث عن العنصر في الجدول
        const tableRows = document.querySelectorAll('#allRevisionsContainer tbody tr, #myRevisionsContainer tbody tr, #myCreatedRevisionsContainer tbody tr');
        console.log('🔍 عدد الصفوف في الجدول:', tableRows.length);
        tableRows.forEach(row => {
            const timerInRow = row.querySelector(`#revision-timer-${revisionId}`);
            if (timerInRow) {
                console.log('✅ لقيت العنصر في الصف:', row);
            }
        });
    }
}

// تنسيق الوقت
function formatRevisionTime(seconds) {
    // التأكد من أن الوقت ليس سالباً
    if (seconds < 0) {
        console.error('⚠️ وقت سالب تم اكتشافه:', seconds);
        seconds = 0;
    }

    const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
    const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
    const s = String(seconds % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
}

// حساب الوقت الأولي من قاعدة البيانات وإضافة الوقت المنقضي
function calculateInitialRevisionTime(revision) {
    console.log('🧮 حساب الوقت الأولي للتعديل:', revision.id);

    let totalSeconds = 0;

    // إضافة الوقت المحفوظ في قاعدة البيانات
    if (revision.actual_minutes) {
        totalSeconds = revision.actual_minutes * 60;
        console.log('📊 الوقت المحفوظ في قاعدة البيانات:', revision.actual_minutes, 'دقيقة =', totalSeconds, 'ثانية');
    } else {
        console.log('📊 مفيش وقت محفوظ في قاعدة البيانات');
    }

    // إضافة الوقت المنقضي منذ بدء الجلسة الحالية
    if (revision.current_session_start) {
        const sessionStart = new Date(revision.current_session_start);
        const now = new Date();
        const elapsedSeconds = Math.floor((now - sessionStart) / 1000);

        // التأكد من أن الوقت المنقضي ليس سالباً
        if (elapsedSeconds < 0) {
            console.warn('⚠️ الوقت المنقضي سالب:', elapsedSeconds, 'ثانية - سيتم تجاهله');
            console.warn('📅 تاريخ بدء الجلسة:', sessionStart);
            console.warn('📅 الوقت الحالي:', now);
        } else {
            totalSeconds += elapsedSeconds;
            console.log('⏱️ الوقت المنقضي منذ البدء:', elapsedSeconds, 'ثانية، الإجمالي:', totalSeconds, 'ثانية');
        }
    } else {
        console.log('⏱️ مفيش جلسة نشطة حالياً');
    }

    console.log('✅ الإجمالي النهائي:', totalSeconds, 'ثانية');
    return totalSeconds;
}

// حساب الوقت الأولي للمراجعة من قاعدة البيانات وإضافة الوقت المنقضي
function calculateInitialReviewTime(revision) {
    console.log('🧮 حساب الوقت الأولي للمراجعة:', revision.id);

    let totalSeconds = 0;

    // إضافة الوقت المحفوظ في قاعدة البيانات
    if (revision.review_actual_minutes) {
        totalSeconds = revision.review_actual_minutes * 60;
        console.log('📊 الوقت المحفوظ للمراجعة في قاعدة البيانات:', revision.review_actual_minutes, 'دقيقة =', totalSeconds, 'ثانية');
    } else {
        console.log('📊 مفيش وقت محفوظ للمراجعة في قاعدة البيانات');
    }

    // إضافة الوقت المنقضي منذ بدء جلسة المراجعة الحالية
    if (revision.review_current_session_start) {
        const sessionStart = new Date(revision.review_current_session_start);
        const now = new Date();
        const elapsedSeconds = Math.floor((now - sessionStart) / 1000);

        // التأكد من أن الوقت المنقضي ليس سالباً
        if (elapsedSeconds < 0) {
            console.warn('⚠️ وقت المراجعة المنقضي سالب:', elapsedSeconds, 'ثانية - سيتم تجاهله');
            console.warn('📅 تاريخ بدء جلسة المراجعة:', sessionStart);
            console.warn('📅 الوقت الحالي:', now);
        } else {
            totalSeconds += elapsedSeconds;
            console.log('⏱️ وقت المراجعة المنقضي منذ البدء:', elapsedSeconds, 'ثانية، الإجمالي:', totalSeconds, 'ثانية');
        }
    } else {
        console.log('⏱️ مفيش جلسة مراجعة نشطة حالياً');
    }

    console.log('✅ الإجمالي النهائي للمراجعة:', totalSeconds, 'ثانية');
    return totalSeconds;
}

// تحديث التايمر بعد أي عملية (بدون reload كامل)
function refreshRevisionTimers() {
    console.log('🔄 تحديث التايمرات بدون reload...');

    // فقط إعادة تهيئة التايمرات بدون عمل reload للبيانات
    initializeRevisionTimers();

    console.log('✅ انتهى من تحديث التايمرات');
}

// تنظيف التايمرات عند مغادرة الصفحة
$(window).on('beforeunload', function() {
    stopAllRevisionTimers();
});

// ====================================
// 🔄 Reassignment Functions
// ====================================

/**
 * إعادة تعيين المنفذ
 */
async function reassignExecutor(revisionId, projectId) {
    try {
        // جلب المشاركين في المشروع
        const response = await fetch(`/projects/${projectId}/participants`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!result.success || !result.participants || result.participants.length === 0) {
            Swal.fire('خطأ', 'لم يتم العثور على مشاركين في المشروع', 'error');
            return;
        }

        // تحضير خيارات الـ dropdown
        const options = {};
        result.participants.forEach(user => {
            options[user.id] = user.name;
        });

        // عرض نافذة الاختيار
        const { value: selectedUserId } = await Swal.fire({
            title: '🔨 إعادة تعيين المنفذ',
            html: `
                <div class="text-end mb-3">
                    <label class="form-label">اختر المنفذ الجديد:</label>
                    <select id="swal-executor-select" class="form-control">
                        <option value="">-- اختر المنفذ --</option>
                        ${Object.entries(options).map(([id, name]) =>
                            `<option value="${id}">${name}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="text-end">
                    <label class="form-label">سبب إعادة التعيين (اختياري):</label>
                    <textarea id="swal-reason" class="form-control" rows="2" placeholder="مثال: الشخص مشغول بمشاريع أخرى..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'تعيين',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#0d6efd',
            preConfirm: () => {
                const userId = document.getElementById('swal-executor-select').value;
                const reason = document.getElementById('swal-reason').value;

                if (!userId) {
                    Swal.showValidationMessage('الرجاء اختيار المنفذ');
                    return false;
                }

                return { userId, reason };
            }
        });

        if (!selectedUserId) return;

        // إرسال الطلب
        const reassignResponse = await fetch(`/task-revisions/${revisionId}/reassign-executor`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                to_user_id: selectedUserId.userId,
                reason: selectedUserId.reason
            })
        });

        const reassignResult = await reassignResponse.json();

        if (reassignResult.success) {
            Swal.fire({
                title: 'تم بنجاح!',
                text: 'تم إعادة تعيين المنفذ بنجاح',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // تحديث العرض
            if (typeof refreshData === 'function') {
                refreshData();
            }

            // إغلاق الـ sidebar وإعادة فتحه لعرض البيانات المحدثة
            if (typeof closeSidebar === 'function') {
                closeSidebar();
            }
        } else {
            Swal.fire('خطأ', reassignResult.message || 'حدث خطأ في إعادة التعيين', 'error');
        }

    } catch (error) {
        console.error('Error reassigning executor:', error);
        Swal.fire('خطأ', 'حدث خطأ في الاتصال بالخادم', 'error');
    }
}

/**
 * إعادة تعيين المراجع
 */
async function reassignReviewer(revisionId, projectId, reviewerOrder) {
    try {
        // جلب المراجعين المؤهلين
        const response = await fetch(`/task-revisions/reviewers-only?project_id=${projectId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const result = await response.json();

        if (!result.success || !result.reviewers || result.reviewers.length === 0) {
            Swal.fire('خطأ', 'لم يتم العثور على مراجعين مؤهلين في المشروع', 'error');
            return;
        }

        // تحضير خيارات الـ dropdown
        const options = {};
        result.reviewers.forEach(user => {
            options[user.id] = user.name;
        });

        // عرض نافذة الاختيار
        const { value: selectedUserId } = await Swal.fire({
            title: `✅ إعادة تعيين المراجع رقم ${reviewerOrder}`,
            html: `
                <div class="text-end mb-3">
                    <label class="form-label">اختر المراجع الجديد:</label>
                    <select id="swal-reviewer-select" class="form-control">
                        <option value="">-- اختر المراجع --</option>
                        ${Object.entries(options).map(([id, name]) =>
                            `<option value="${id}">${name}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="text-end">
                    <label class="form-label">سبب إعادة التعيين (اختياري):</label>
                    <textarea id="swal-reason" class="form-control" rows="2" placeholder="مثال: تغيير في المسؤوليات..."></textarea>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'تعيين',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#198754',
            preConfirm: () => {
                const userId = document.getElementById('swal-reviewer-select').value;
                const reason = document.getElementById('swal-reason').value;

                if (!userId) {
                    Swal.showValidationMessage('الرجاء اختيار المراجع');
                    return false;
                }

                return { userId, reason };
            }
        });

        if (!selectedUserId) return;

        // إرسال الطلب
        const reassignResponse = await fetch(`/task-revisions/${revisionId}/reassign-reviewer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                to_user_id: selectedUserId.userId,
                reviewer_order: reviewerOrder,
                reason: selectedUserId.reason
            })
        });

        const reassignResult = await reassignResponse.json();

        if (reassignResult.success) {
            Swal.fire({
                title: 'تم بنجاح!',
                text: 'تم إعادة تعيين المراجع بنجاح',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });

            // تحديث العرض
            if (typeof refreshData === 'function') {
                refreshData();
            }

            // إغلاق الـ sidebar وإعادة فتحه لعرض البيانات المحدثة
            if (typeof closeSidebar === 'function') {
                closeSidebar();
            }
        } else {
            Swal.fire('خطأ', reassignResult.message || 'حدث خطأ في إعادة التعيين', 'error');
        }

    } catch (error) {
        console.error('Error reassigning reviewer:', error);
        Swal.fire('خطأ', 'حدث خطأ في الاتصال بالخادم', 'error');
    }
}


// Get review action buttons based on review status (for sidebar)
function getReviewActionButtons(revision) {
    let buttons = '';

    switch(revision.review_status) {
        case 'new':
            buttons = `
                <button class="btn btn-success" onclick="startReview(${revision.id})">
                    <i class="fas fa-play me-1"></i>بدء المراجعة
                </button>
            `;
            break;

        case 'in_progress':
            buttons = `
                <button class="btn btn-warning" onclick="pauseReview(${revision.id})">
                    <i class="fas fa-pause me-1"></i>إيقاف مؤقت
                </button>
                <button class="btn btn-success" onclick="completeReview(${revision.id})">
                    <i class="fas fa-check me-1"></i>إكمال المراجعة
                </button>
            `;
            break;

        case 'paused':
            buttons = `
                <button class="btn btn-primary" onclick="resumeReview(${revision.id})">
                    <i class="fas fa-play me-1"></i>استئناف
                </button>
                <button class="btn btn-success" onclick="completeReview(${revision.id})">
                    <i class="fas fa-check me-1"></i>إكمال المراجعة
                </button>
            `;
            break;

        case 'completed':
            buttons = `<span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i>مراجعة مكتملة</span>`;
            break;
    }

    return buttons;
}

// Get compact review action buttons for table (icons only)
function getReviewActionButtonsCompact(revision) {
    let buttons = '';

    switch(revision.review_status) {
        case 'new':
            buttons = `
                <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); startReview(${revision.id});" title="بدء المراجعة">
                    <i class="fas fa-play"></i>
                </button>
            `;
            break;

        case 'in_progress':
            buttons = `
                <button class="btn btn-sm btn-warning" onclick="event.stopPropagation(); pauseReview(${revision.id});" title="إيقاف مؤقت">
                    <i class="fas fa-pause"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); completeReview(${revision.id});" title="إكمال المراجعة">
                    <i class="fas fa-check"></i>
                </button>
            `;
            break;

        case 'paused':
            buttons = `
                <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); resumeReview(${revision.id});" title="استئناف المراجعة">
                    <i class="fas fa-play"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); completeReview(${revision.id});" title="إكمال المراجعة">
                    <i class="fas fa-check"></i>
                </button>
            `;
            break;

        case 'completed':
            buttons = `
                <span class="badge bg-success">
                    <i class="fas fa-check-circle me-1"></i>تم
                </span>
            `;
            break;
    }

    return buttons;
}

// Start review
async function startReview(revisionId) {
    console.log('Starting review for ID:', revisionId);

    try {
        const response = await fetch(`/task-revisions/${revisionId}/start-review`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: 'نجح!',
                text: result.message || 'تم بدء المراجعة',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
            closeSidebar();
            // تحديث البيانات
            setTimeout(() => refreshRevisionData(), 100);
        } else {
            // التحقق من وجود مراجعة أخرى نشطة
            if (result.active_review_id) {
                Swal.fire({
                    title: 'تنبيه!',
                    html: `
                        <p>${result.message}</p>
                        <p class="mt-2"><strong>المراجعة النشطة:</strong> ${result.active_review_title}</p>
                    `,
                    icon: 'warning',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#3085d6',
                    customClass: {
                        popup: 'rtl-swal'
                    }
                });
            } else {
                Swal.fire({
                    title: 'خطأ!',
                    text: result.message || 'حدث خطأ في بدء المراجعة',
                    icon: 'error',
                    confirmButtonText: 'حسناً',
                    customClass: {
                        popup: 'rtl-swal'
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error starting review:', error);
        Swal.fire({
            title: 'خطأ!',
            text: 'حدث خطأ في بدء المراجعة',
            icon: 'error',
            confirmButtonText: 'حسناً',
            customClass: {
                popup: 'rtl-swal'
            }
        });
    }
}

// Pause review
async function pauseReview(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/pause-review`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: 'تم الإيقاف!',
                text: `تم الإيقاف المؤقت. الوقت: ${result.session_minutes} دقيقة`,
                icon: 'info',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
            closeSidebar();
            setTimeout(() => refreshRevisionData(), 100);
        } else {
            Swal.fire({
                title: 'خطأ!',
                text: result.message,
                icon: 'error',
                confirmButtonText: 'حسناً',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
        }
    } catch (error) {
        console.error('Error pausing review:', error);
        Swal.fire({
            title: 'خطأ!',
            text: 'حدث خطأ في الإيقاف المؤقت',
            icon: 'error',
            confirmButtonText: 'حسناً',
            customClass: {
                popup: 'rtl-swal'
            }
        });
    }
}

// Resume review
async function resumeReview(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/resume-review`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: 'نجح!',
                text: result.message || 'تم استئناف المراجعة',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
            closeSidebar();
            setTimeout(() => refreshRevisionData(), 100);
        } else {
            // التحقق من وجود مراجعة أخرى نشطة
            if (result.active_review_id) {
                Swal.fire({
                    title: 'تنبيه!',
                    html: `
                        <p>${result.message}</p>
                        <p class="mt-2"><strong>المراجعة النشطة:</strong> ${result.active_review_title}</p>
                    `,
                    icon: 'warning',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: '#3085d6',
                    customClass: {
                        popup: 'rtl-swal'
                    }
                });
            } else {
                Swal.fire({
                    title: 'خطأ!',
                    text: result.message || 'حدث خطأ في استئناف المراجعة',
                    icon: 'error',
                    confirmButtonText: 'حسناً',
                    customClass: {
                        popup: 'rtl-swal'
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error resuming review:', error);
        Swal.fire({
            title: 'خطأ!',
            text: 'حدث خطأ في استئناف المراجعة',
            icon: 'error',
            confirmButtonText: 'حسناً',
            customClass: {
                popup: 'rtl-swal'
            }
        });
    }
}

// Complete review
async function completeReview(revisionId) {
    try {
        const response = await fetch(`/task-revisions/${revisionId}/complete-review`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: 'تم الإكمال!',
                text: `تم إكمال المراجعة! الوقت الكلي: ${formatRevisionTimeInMinutes(result.total_minutes)}`,
                icon: 'success',
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
            closeSidebar();
            setTimeout(() => refreshRevisionData(), 100);
        } else {
            Swal.fire({
                title: 'خطأ!',
                text: result.message,
                icon: 'error',
                confirmButtonText: 'حسناً',
                customClass: {
                    popup: 'rtl-swal'
                }
            });
        }
    } catch (error) {
        console.error('Error completing review:', error);
        Swal.fire({
            title: 'خطأ!',
            text: 'حدث خطأ في إكمال المراجعة',
            icon: 'error',
            confirmButtonText: 'حسناً',
            customClass: {
                popup: 'rtl-swal'
            }
        });
    }
}

// Helper function to refresh revision data
function refreshRevisionData() {
    const activeTab = $('#revisionTabs .nav-link.active').attr('data-bs-target');
    if (activeTab === '#my-revisions') {
        $.get('/revision-page/my-revisions').done(function(response) {
            if (response.success) {
                renderRevisions(response.revisions, 'my');
                initializeRevisionTimers();
            }
        });
    } else if (activeTab === '#my-created-revisions') {
        $.get('/revision-page/my-created-revisions').done(function(response) {
            if (response.success) {
                renderRevisions(response.revisions, 'myCreated');
                initializeRevisionTimers();
            }
        });
    } else {
        $.get('/revision-page/all-revisions').done(function(response) {
            if (response.success) {
                renderRevisions(response.revisions, 'all');
                initializeRevisionTimers();
            }
        });
    }

    // Update Kanban if in Kanban view
    if (typeof updateKanbanOnTabChange === 'function') {
        setTimeout(() => {
            updateKanbanOnTabChange();
        }, 300);
    }
}

// ====================================
// 🔄 Reopen Functions (إعادة فتح)
// ====================================

// Reopen work (إعادة فتح العمل)
async function reopenWork(revisionId) {
    const result = await Swal.fire({
        title: 'إعادة فتح العمل',
        text: 'هل أنت متأكد من إعادة فتح هذا التعديل؟ سيتم إرجاعه لحالة "متوقف مؤقتاً"',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، إعادة فتح',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#f39c12',
        cancelButtonColor: '#6c757d'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(`/task-revisions/${revisionId}/reopen-work`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const data = await response.json();

        if (data.success) {
            toastr.success('تم إعادة فتح العمل بنجاح');
            closeSidebar();
            setTimeout(() => refreshRevisionData(), 100);
        } else {
            toastr.error(data.message || 'حدث خطأ في إعادة فتح العمل');
        }
    } catch (error) {
        console.error('Error reopening work:', error);
        toastr.error('حدث خطأ في الاتصال بالخادم');
    }
}

// Reopen review (إعادة فتح المراجعة)
async function reopenReview(revisionId) {
    const result = await Swal.fire({
        title: 'إعادة فتح المراجعة',
        text: 'هل أنت متأكد من إعادة فتح المراجعة؟ سيتم إرجاعها لحالة "متوقفة مؤقتاً"',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، إعادة فتح',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#f39c12',
        cancelButtonColor: '#6c757d'
    });

    if (!result.isConfirmed) return;

    try {
        const response = await fetch(`/task-revisions/${revisionId}/reopen-review`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const data = await response.json();

        if (data.success) {
            toastr.success('تم إعادة فتح المراجعة بنجاح');
            closeSidebar();
            setTimeout(() => refreshRevisionData(), 100);
        } else {
            toastr.error(data.message || 'حدث خطأ في إعادة فتح المراجعة');
        }
    } catch (error) {
        console.error('Error reopening review:', error);
        toastr.error('حدث خطأ في الاتصال بالخادم');
    }
}

