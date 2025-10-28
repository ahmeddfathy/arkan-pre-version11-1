function initializeMyTasksDragDrop() {
}

function addDragDropToCard(cardElement) {
    if (!cardElement || !cardElement.length) {
        console.warn('⚠️ addDragDropToCard: cardElement غير موجود');
        return;
    }

    // ✅ التحقق من المهام المنقولة والمعتمدة - منع السحب
    const isTransferred = cardElement.attr('data-is-transferred') === 'true';
    const isAdditionalTask = cardElement.attr('data-is-additional-task') === 'true';
    const isApproved = cardElement.attr('data-is-approved') === 'true';

    // إذا كانت مهمة منقولة أو معتمدة، لا تفعّل السحب
    if (isTransferred || isAdditionalTask || isApproved) {
        cardElement.attr('draggable', 'false');
        cardElement.css('cursor', 'not-allowed');

        if (isApproved) {
            console.log('🔒 منع السحب - مهمة معتمدة:', cardElement.attr('data-task-id'));
        } else {
            console.log('🚫 منع السحب - مهمة منقولة:', cardElement.attr('data-task-id'));
        }
        return;
    }

    // التأكد من أن draggable attribute موجود
    cardElement.attr('draggable', 'true');
    const element = cardElement[0];

    // إزالة الـ event listeners القديمة إن وجدت (لتجنب التكرار)
    const oldElement = element;
    if (oldElement._dragListenersAdded) {
        console.log('⚠️ الـ Drag Listeners موجودة بالفعل لهذا الكارد');
        return;
    }

    // إضافة event listeners
    element.addEventListener('dragstart', function(e) {
        // ✅ التحقق من المهام المنقولة والمعتمدة عند محاولة السحب
        const isTransferred = this.getAttribute('data-is-transferred') === 'true';
        const isAdditionalTask = this.getAttribute('data-is-additional-task') === 'true';
        const isApproved = this.getAttribute('data-is-approved') === 'true';

        if (isTransferred || isAdditionalTask || isApproved) {
            e.preventDefault();

            if (isApproved) {
                console.log('🔒 ممنوع السحب - مهمة معتمدة');
                // عرض رسالة للمستخدم
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'lock',
                        title: 'مهمة معتمدة',
                        text: 'لا يمكن سحب المهام المعتمدة - تم اعتمادها مسبقاً',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            } else {
                console.log('🚫 ممنوع السحب - مهمة منقولة');
                // عرض رسالة للمستخدم
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'غير مسموح',
                        text: 'لا يمكن سحب المهام المنقولة',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }
            return false;
        }

        const taskId = this.getAttribute('data-task-id');
        const taskUserId = this.getAttribute('data-task-user-id') || taskId;
        const isTemplate = this.getAttribute('data-is-template') === 'true';
        const taskType = isTemplate ? 'template_task' : 'regular_task';

        console.log(`🎯 بدء السحب - Task ID: ${taskId}, User ID: ${taskUserId}`);

        const dragData = {
            taskId: taskId,
            taskUserId: taskUserId,
            taskType: taskType,
            isTemplate: isTemplate
        };
        e.dataTransfer.setData('text/plain', JSON.stringify(dragData));
        this.classList.add('dragging');
    });

    element.addEventListener('dragend', function(e) {
        console.log('✋ انتهى السحب');
        this.classList.remove('dragging');
    });

    // تعليم الـ element كـ "تمت إضافة الـ listeners"
    element._dragListenersAdded = true;

    console.log(`✅ تم إضافة Drag & Drop للكارد #${cardElement.attr('data-task-id')}`);
}

function initializeDropZones() {
    const dropZones = document.querySelectorAll('.kanban-drop-zone');
    console.log(`🎯 تهيئة ${dropZones.length} Drop Zones للـ Drag & Drop`);

    dropZones.forEach(zone => {
        // إزالة الـ listeners القديمة لتجنب التكرار
        if (zone._dropListenersAdded) {
            console.log(`⚠️ Drop Zone "${zone.getAttribute('data-status')}" مُهيأة بالفعل`);
            return;
        }

        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        zone.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            const newStatus = this.getAttribute('data-status');
            console.log(`📥 إفلات الكارد في منطقة "${newStatus}"`);

            try {
                const dragData = JSON.parse(e.dataTransfer.getData('text/plain'));
                // ✅ البحث عن الكارد بكلا الكلاسين (my-kanban-card و kanban-card)
                let card = $(`.my-kanban-card[data-task-id="${dragData.taskId}"]`);
                if (!card.length) {
                    card = $(`.kanban-card[data-task-id="${dragData.taskId}"]`);
                }

                if (!card.length) {
                    console.error(`❌ لم يتم العثور على الكارد #${dragData.taskId}`);
                    return;
                }

                const currentStatus = card.data('status') || card.attr('data-status');
                console.log(`🔍 الكارد موجود: status="${currentStatus}", newStatus="${newStatus}"`);

                if (currentStatus !== newStatus) {
                    console.log(`🔄 تحديث حالة المهمة من "${currentStatus}" إلى "${newStatus}"`);
                    updateMyTaskStatus(dragData, newStatus, card);
                } else {
                    console.log(`ℹ️ المهمة بالفعل في نفس الحالة "${newStatus}"`);
                }
            } catch (error) {
                console.error('❌ خطأ في معالجة Drop:', error);
            }
        });

        // تعليم الـ zone كـ "تمت إضافة الـ listeners"
        zone._dropListenersAdded = true;
        console.log(`✅ Drop Zone "${zone.getAttribute('data-status')}" جاهزة`);
    });

    console.log('✅ تم تهيئة جميع Drop Zones بنجاح');
}

window.myTasksAlertShown = false;

async function updateMyTaskStatus(dragData, newStatus, cardElement) {

    if (window.myTasksAlertShown) {
        return;
    }

    try {
        let url;
        const requestData = { status: newStatus };
        const taskUserId = cardElement.data('task-user-id') || dragData.taskUserId;
        const isTemplate = dragData.isTemplate === 'true' || dragData.isTemplate === true ||
                          cardElement.data('is-template') === 'true' || cardElement.data('is-template') === true;
        if (isTemplate) {
            url = `/template-tasks/${taskUserId}/update-status`;
        } else {
            url = `/task-users/${taskUserId}/update-status`;
        }
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();

        // ✅ التحقق من النجاح بشكل صحيح
        if (!response.ok || result.success === false) {
            // معالجة الأخطاء بشكل صحيح
            const errorMessage = result.message || 'حدث خطأ أثناء تحديث حالة المهمة';

            // عرض البنود المعلقة إذا كانت موجودة
            if (result.pending_items && result.pending_items.length > 0) {
                const itemsList = result.pending_items.map(item => `• ${item.title}`).join('\n');
                throw new Error(`${errorMessage}\n\nالبنود المتبقية:\n${itemsList}`);
            }

            throw new Error(errorMessage);
        }

        if (result.success === true) {
            console.log('✅ تم تحديث الحالة في السيرفر بنجاح');

            const newColumn = $(`#my-cards-${newStatus}`);
            console.log(`📦 نقل الكارد إلى العمود: #my-cards-${newStatus}`, newColumn.length ? 'موجود' : 'غير موجود');

            if (newColumn.length) {
                newColumn.append(cardElement);
                cardElement.data('status', newStatus);
                cardElement.attr('data-status', newStatus);
                console.log('✅ تم نقل الكارد بنجاح');

                // ✅ تحديث العدادات بعد النقل
                if (window.MyTasksKanban && window.MyTasksKanban.updateCardCounters) {
                    window.MyTasksKanban.updateCardCounters();
                }
            } else {
                console.error('❌ العمود الجديد غير موجود!');
            }
            if (newStatus === 'in_progress') {
                if (result.task && result.task.started_at) {
                    const startedAtDate = new Date(result.task.started_at);
                    const startedAtTimestamp = startedAtDate.getTime();
                    cardElement.attr('data-started-at', startedAtTimestamp);
                } else {
                    const currentTimestamp = new Date().getTime();
                    cardElement.attr('data-started-at', currentTimestamp);
                }
            } else if (newStatus !== 'in_progress') {
                cardElement.attr('data-started-at', '');
                if (result.minutesSpent !== undefined) {
                    const currentMinutes = parseInt(cardElement.attr('data-initial-minutes') || '0');
                    const newTotalMinutes = currentMinutes + result.minutesSpent;
                    cardElement.attr('data-initial-minutes', newTotalMinutes);
                }
            }
            window.MyTasksUtils.updateMyTasksCounters();
            handleMyTaskTimerStatusChange(dragData.taskUserId, newStatus);
            setTimeout(() => {
                if (window.MyTasksTimers && window.MyTasksTimers.calculateInitialTotalTime) {
                    window.MyTasksTimers.calculateInitialTotalTime();
                }
            }, 500);


            if (typeof Swal !== 'undefined' && !window.myTasksAlertShown) {
                window.myTasksAlertShown = true;
                Swal.fire({
                    icon: 'success',
                    title: 'نجح!',
                    text: 'تم تحديث حالة المهمة بنجاح',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {

                    setTimeout(() => {
                        window.myTasksAlertShown = false;
                    }, 100);
                });
            } else if (!window.myTasksAlertShown) {
                window.myTasksAlertShown = true;
                alert('تم تحديث حالة المهمة بنجاح');
                setTimeout(() => {
                    window.myTasksAlertShown = false;
                }, 2000);
            }
        }
    } catch (error) {
        console.error('❌ خطأ في تحديث الحالة:', error);

        // استخراج رسالة الخطأ من الـ Error object
        const errorMessage = error.message || 'حدث خطأ أثناء تحديث حالة المهمة';

        if (typeof Swal !== 'undefined' && !window.myTasksAlertShown) {
            window.myTasksAlertShown = true;

            // عرض رسالة مفصلة إذا كانت تحتوي على قائمة بنود
            const isItemsError = errorMessage.includes('البنود المتبقية:');

            Swal.fire({
                icon: 'warning',
                title: isItemsError ? '⚠️ يجب إكمال البنود أولاً!' : 'خطأ!',
                html: errorMessage.replace(/\n/g, '<br>'),
                confirmButtonText: 'حسناً',
                width: isItemsError ? '500px' : '400px',
                customClass: {
                    popup: 'text-end',
                    htmlContainer: isItemsError ? 'text-start' : ''
                }
            }).then(() => {
                // إعادة تعيين المتغير بعد اختفاء SweetAlert
                setTimeout(() => {
                    window.myTasksAlertShown = false;
                }, 100);
            });
        } else if (!window.myTasksAlertShown) {
            window.myTasksAlertShown = true;
            alert(errorMessage);
            setTimeout(() => {
                window.myTasksAlertShown = false;
            }, 3000);
        }
    }
}

function handleMyTaskTimerStatusChange(taskUserId, newStatus) {
    const task = document.querySelector(`.my-kanban-card[data-task-user-id="${taskUserId}"]`);
    if (!task) return;
    task.classList.remove('task-in-progress', 'task-paused', 'task-completed', 'task-new');
    task.classList.add(`task-${newStatus}`);
    window.MyTasksUtils.dispatchTimerEvent(newStatus, taskUserId);
    switch (newStatus) {
        case 'in_progress':
            window.MyTasksTimers.startTimer(taskUserId);
            break;
        case 'paused':
            window.MyTasksTimers.pauseTimer(taskUserId);
            break;
        case 'completed':
            window.MyTasksTimers.finishTimer(taskUserId);
            break;
        default:
            window.MyTasksTimers.pauseTimer(taskUserId);
            break;
    }
}

window.MyTasksDragDrop = {
    initializeMyTasksDragDrop,
    addDragDropToCard,
    initializeDropZones,
    updateMyTaskStatus,
    handleMyTaskTimerStatusChange
};

console.log('✅ MyTasksDragDrop Module Loaded Successfully', window.MyTasksDragDrop);
