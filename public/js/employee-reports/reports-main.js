
document.addEventListener('DOMContentLoaded', function() {

    // تم إزالة تحكم التاريخ - نستخدم اليوم فقط


    initEmployeeSearch();


    initCharts();
});




function initEmployeeSearch() {
    const employeeSearch = document.getElementById('employeeSearch');
    const employeeList = document.getElementById('employeeList');
    const employeeItems = document.querySelectorAll('.employee-item');

    if (!employeeSearch || !employeeList) return;

    let searchTimeout;

    // تحسين البحث مع debouncing لتحسين الأداء
    employeeSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = this.value.trim().toLowerCase();
            let visibleCount = 0;

            employeeItems.forEach(item => {
                const employeeName = item.getAttribute('data-employee-name') ||
                                   item.querySelector('h6').textContent.toLowerCase();

                if (searchTerm === '' || employeeName.includes(searchTerm)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // إظهار رسالة عند عدم وجود نتائج
            updateSearchResults(visibleCount, searchTerm);
        }, 300);
    });

    // تحسين التمرير للأعداد الكبيرة
    if (employeeItems.length > 100) {
        initVirtualScrolling();
    }
}

function updateSearchResults(visibleCount, searchTerm) {
    const employeeList = document.getElementById('employeeList');
    let searchResultsElement = employeeList.querySelector('.search-results');

    // إزالة رسالة النتائج السابقة
    if (searchResultsElement) {
        searchResultsElement.remove();
    }

    // إضافة رسالة النتائج الجديدة
    if (searchTerm !== '' && visibleCount === 0) {
        const noResultsMsg = document.createElement('div');
        noResultsMsg.className = 'search-results text-center p-4 text-muted';
        noResultsMsg.innerHTML = `
            <i class="fas fa-search fa-2x mb-2"></i>
            <p>لم يتم العثور على موظفين بالبحث: "${searchTerm}"</p>
            <small>جرب استخدام كلمات مختلفة أو تحقق من الإملاء</small>
        `;
        employeeList.appendChild(noResultsMsg);
    } else if (searchTerm !== '' && visibleCount > 0) {
        const resultsMsg = document.createElement('div');
        resultsMsg.className = 'search-results text-center p-2 border-top';
        resultsMsg.innerHTML = `
            <small class="text-muted">
                <i class="fas fa-check-circle text-success"></i>
                تم العثور على ${visibleCount} موظف
            </small>
        `;
        employeeList.appendChild(resultsMsg);
    }
}

function initVirtualScrolling() {
    const employeeList = document.getElementById('employeeList');
    const employeeItems = document.querySelectorAll('.employee-item');

    // تحسين الأداء للقوائم الطويلة
    let isScrolling = false;

    employeeList.addEventListener('scroll', function() {
        if (!isScrolling) {
            window.requestAnimationFrame(function() {
                // تحسين عرض العناصر أثناء التمرير
                const scrollTop = employeeList.scrollTop;
                const listHeight = employeeList.clientHeight;
                const itemHeight = 80; // متوسط ارتفاع العنصر

                const startIndex = Math.floor(scrollTop / itemHeight);
                const endIndex = Math.min(
                    employeeItems.length - 1,
                    Math.floor((scrollTop + listHeight) / itemHeight) + 2
                );

                // إخفاء العناصر غير المرئية لتحسين الأداء
                employeeItems.forEach((item, index) => {
                    if (index < startIndex - 5 || index > endIndex + 5) {
                        item.style.transform = 'translateZ(0)'; // تحسين الـ GPU rendering
                    }
                });

                isScrolling = false;
            });
        }
        isScrolling = true;
    });
}


function initCharts() {

}


function initReportVisualizations(config) {
    if (!config) {
        console.warn('لا توجد بيانات لعرض التقارير');
        return;
    }

    console.log('تهيئة تصور التقارير...', config);



        // إنشاء timeline مخصص للمهام والشيفت
    const ganttContainer = document.getElementById('ganttChart');

    if (window.shiftData && window.shiftData.timeline) {
        try {
            // إزالة رسالة التحميل
            const loadingMessage = document.getElementById('ganttLoadingMessage');
            if (loadingMessage) {
                loadingMessage.style.display = 'none';
            }

            createCustomTimeline(ganttContainer, window.shiftData, config.ganttTasks || []);
            console.log('✅ تم إنشاء Timeline مخصص بنجاح');
        } catch (error) {
            console.error('خطأ في إنشاء Timeline:', error);
            if (ganttContainer) {
                ganttContainer.innerHTML = `
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <h5>تعذر عرض Timeline</h5>
                        <p class="mb-0">حدث خطأ أثناء تحميل العرض الزمني.</p>
                    </div>
                `;
            }
        }
    } else {
        console.log('لا توجد بيانات شيفت للعرض');
        if (ganttContainer) {
            ganttContainer.innerHTML = `
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <h5>لا توجد بيانات للعرض</h5>
                    <p class="mb-0">لا توجد بيانات شيفت للموظف المحدد.</p>
                </div>
            `;
        }
    }

    // ✅ إنشاء الرسوم البيانية
    if (config.taskStatusData) {
        try {
            const reportCharts = new EmployeeReportCharts();

            // رسم بياني لحالات المهام
            reportCharts.createTaskStatusChart(
                config.taskStatusData.completed || 0,
                config.taskStatusData.inProgress || 0,
                config.taskStatusData.paused || 0,
                config.taskStatusData.new || 0,
                config.taskStatusData.cancelled || 0
            );

            // رسم بياني لتوزيع الوقت
            if (config.timeData && config.timeData.labels && config.timeData.values) {
                reportCharts.createTimeDistributionChart(
                    config.timeData.labels,
                    config.timeData.values
                );
            }

            console.log('تم إنشاء الرسوم البيانية بنجاح');
        } catch (error) {
            console.error('خطأ في إنشاء الرسوم البيانية:', error);
        }
    }
}





/**
 * إنشاء Timeline مخصص مع الساعات في الأعلى والمهام كخطوط ملونة
 */
function createCustomTimeline(container, shiftData, tasks) {
    if (!container || !shiftData || !shiftData.timeline) {
        return;
    }

    console.log('إنشاء Timeline مخصص...', shiftData, tasks);

    // إنشاء هيكل HTML للـ Timeline
    let timelineHTML = `
        <div class="custom-timeline">
            <div class="timeline-header">
                <div class="timeline-hours">
                    ${createTimelineHours(shiftData)}
                </div>
            </div>
            <div class="timeline-body">
                ${createTaskTimelines(shiftData, tasks)}
            </div>
        </div>
    `;

    container.innerHTML = timelineHTML;

    // إضافة event listeners للـ hover
    addTimelineEventListeners();
}

/**
 * إنشاء شريط الساعات في الأعلى
 */
function createTimelineHours(shiftData) {
    let hoursHTML = '';

    shiftData.timeline.forEach((slot, index) => {
        hoursHTML += `
            <div class="hour-slot" data-time="${slot.start}">
                <div class="hour-time">${slot.start}</div>
            </div>
        `;
    });

    return hoursHTML;
}

/**
 * إنشاء خط واحد بسيط للشيفت
 */
function createTaskTimelines(shiftData, tasks) {
    // عرض شريط واحد بسيط للشيفت
    const tasksHTML = `
        <div class="task-timeline-row">
            <div class="task-info">
                <div class="task-name">حالة العمل</div>
                <div class="task-details">
                    <small class="text-muted">عمل: ${shiftData.workingTime.formatted}, راحة: ${shiftData.idleTime.formatted}</small>
                </div>
            </div>
            <div class="task-timeline-slots">
                ${createGeneralTimelineSlots(shiftData)}
            </div>
        </div>
    `;

    return tasksHTML;
}



/**
 * إنشاء فترات زمنية عامة للشيفت
 */
function createGeneralTimelineSlots(shiftData) {
    let slotsHTML = '';

    shiftData.timeline.forEach((slot, index) => {
        const slotClass = slot.status; // working, idle, future

        // إنشاء قائمة المهام للفترة
        let tasksInSlot = '';
        if (slot.tasks && slot.tasks.length > 0) {
            tasksInSlot = slot.tasks.map(task => task.name).join(', ');
        }

        slotsHTML += `
            <div class="timeline-slot ${slotClass}"
                 data-slot-index="${index}"
                 data-time="${slot.start}-${slot.end}"
                 data-status="${slotClass}"
                 data-tasks="${tasksInSlot}"
                 title="${slot.start} إلى ${slot.end} - ${slotClass === 'working' ? 'يعمل' : (slotClass === 'future' ? 'لم يحن الوقت' : 'راحة')}">
            </div>
        `;
    });

    return slotsHTML;
}

/**
 * إضافة مستمعي الأحداث للـ Timeline
 */
function addTimelineEventListeners() {
    // إضافة hover effects للفترات الزمنية
    document.querySelectorAll('.timeline-slot').forEach(slot => {
        slot.addEventListener('mouseenter', function() {
            this.classList.add('hovered');

            // إظهار tooltip مخصص
            showTimelineTooltip(this);
        });

        slot.addEventListener('mouseleave', function() {
            this.classList.remove('hovered');
            hideTimelineTooltip();
        });

        slot.addEventListener('click', function() {
            // اختيار الفترة
            document.querySelectorAll('.timeline-slot.selected').forEach(s => s.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
}

/**
 * إظهار tooltip مخصص للفترة الزمنية
 */
function showTimelineTooltip(element) {
    const time = element.getAttribute('data-time');
    const status = element.getAttribute('data-status');
    const tasks = element.getAttribute('data-tasks');

    let statusText = '';
    switch(status) {
        case 'working': statusText = 'يعمل'; break;
        case 'idle': statusText = 'راحة'; break;
        case 'future': statusText = 'لم يحن الوقت'; break;
        default: statusText = 'غير محدد';
    }

    // إزالة tooltip سابق
    hideTimelineTooltip();

    const tooltip = document.createElement('div');
    tooltip.className = 'timeline-tooltip';

    let tooltipContent = `
        <strong>الوقت: ${time}</strong><br>
        <span style="color: ${status === 'working' ? '#20c997' : (status === 'future' ? '#adb5bd' : '#fd7e14')};">
            الحالة: ${statusText}
        </span>
    `;

    // إضافة المهام إذا كانت الفترة للعمل وهناك مهام
    if (status === 'working' && tasks && tasks.trim() !== '') {
        tooltipContent += `<br><br><strong>المهام الشغالة:</strong><br><span style="color: #20c997;">${tasks}</span>`;
    } else if (status === 'working') {
        tooltipContent += `<br><br><em>لا توجد مهام محددة</em>`;
    }

    tooltip.innerHTML = tooltipContent;

    document.body.appendChild(tooltip);

    const rect = element.getBoundingClientRect();
    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
}

/**
 * إخفاء tooltip
 */
function hideTimelineTooltip() {
    const tooltip = document.querySelector('.timeline-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}
