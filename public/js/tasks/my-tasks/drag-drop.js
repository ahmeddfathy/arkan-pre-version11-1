function initializeMyTasksDragDrop() {}

function addDragDropToCard(cardElement) {
    if (!cardElement || !cardElement.length) {
        console.warn("⚠️ addDragDropToCard: cardElement غير موجود");
        return;
    }

    const isTransferred = cardElement.attr("data-is-transferred") === "true";
    const isAdditionalTask =
        cardElement.attr("data-is-additional-task") === "true";
    const isApproved = cardElement.attr("data-is-approved") === "true";
    const projectStatus = cardElement.attr("data-project-status") || "";
    const isProjectCancelled = projectStatus === "ملغي";
    const taskStatus = cardElement.attr("data-status") || "";
    const isCancelled = taskStatus === "cancelled";

    if (
        isTransferred ||
        isAdditionalTask ||
        isApproved ||
        isProjectCancelled ||
        isCancelled
    ) {
        cardElement.attr("draggable", "false");
        cardElement.css("cursor", "not-allowed");
        return;
    }

    cardElement.attr("draggable", "true");
    const element = cardElement[0];
    const oldElement = element;
    if (oldElement._dragListenersAdded) {
        return;
    }

    element.addEventListener("dragstart", function (e) {
        const isTransferred =
            this.getAttribute("data-is-transferred") === "true";
        const isAdditionalTask =
            this.getAttribute("data-is-additional-task") === "true";
        const isApproved = this.getAttribute("data-is-approved") === "true";
        const projectStatus = this.getAttribute("data-project-status") || "";
        const isProjectCancelled = projectStatus === "ملغي";
        const taskStatus = this.getAttribute("data-status") || "";
        const isCancelled = taskStatus === "cancelled";

        if (
            isTransferred ||
            isAdditionalTask ||
            isApproved ||
            isProjectCancelled ||
            isCancelled
        ) {
            e.preventDefault();
            if (isProjectCancelled) {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "error",
                        title: "المشروع تم إلغاؤه",
                        text: "لا يمكن تحديث حالة المهمة لأن المشروع تم إلغاؤه",
                        timer: 3000,
                        showConfirmButton: false,
                    });
                }
            } else if (isApproved) {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "lock",
                        title: "مهمة معتمدة",
                        text: "لا يمكن سحب المهام المعتمدة - تم اعتمادها مسبقاً",
                        timer: 3000,
                        showConfirmButton: false,
                    });
                }
            } else if (isCancelled) {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "error",
                        title: "مهمة ملغاة",
                        text: "لا يمكن سحب المهام الملغاة",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            } else {
                if (typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "warning",
                        title: "غير مسموح",
                        text: "لا يمكن سحب المهام المنقولة",
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }
            }
            return false;
        }

        const taskId = this.getAttribute("data-task-id");
        const taskUserId = this.getAttribute("data-task-user-id") || taskId;
        const isTemplate = this.getAttribute("data-is-template") === "true";
        const taskType = isTemplate ? "template_task" : "regular_task";

        const dragData = {
            taskId: taskId,
            taskUserId: taskUserId,
            taskType: taskType,
            isTemplate: isTemplate,
        };
        e.dataTransfer.setData("text/plain", JSON.stringify(dragData));
        this.classList.add("dragging");
    });

    element.addEventListener("dragend", function (e) {
        this.classList.remove("dragging");
    });

    element._dragListenersAdded = true;
}

function initializeDropZones() {
    const dropZones = document.querySelectorAll(".kanban-drop-zone");

    dropZones.forEach((zone) => {
        if (zone._dropListenersAdded) {
            return;
        }

        zone.addEventListener("dragover", function (e) {
            e.preventDefault();
            this.classList.add("drag-over");
        });
        zone.addEventListener("dragleave", function (e) {
            this.classList.remove("drag-over");
        });
        zone.addEventListener("drop", function (e) {
            e.preventDefault();
            this.classList.remove("drag-over");
            const newStatus = this.getAttribute("data-status");
            try {
                const dragData = JSON.parse(
                    e.dataTransfer.getData("text/plain")
                );
                let card = $(
                    `.my-kanban-card[data-task-id="${dragData.taskId}"]`
                );
                if (!card.length) {
                    card = $(`.kanban-card[data-task-id="${dragData.taskId}"]`);
                }

                if (!card.length) {
                    return;
                }

                const currentStatus =
                    card.data("status") || card.attr("data-status");
                if (currentStatus !== newStatus) {
                    updateMyTaskStatus(dragData, newStatus, card);
                }
            } catch (error) {}
        });

        zone._dropListenersAdded = true;
    });
}

window.myTasksAlertShown = false;

async function updateMyTaskStatus(dragData, newStatus, cardElement) {
    if (window.myTasksAlertShown) {
        return;
    }

    // منع تغيير حالة المهام الملغاة
    const currentStatus =
        cardElement.data("status") || cardElement.attr("data-status");
    if (currentStatus === "cancelled") {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "مهمة ملغاة",
                text: "لا يمكن تغيير حالة المهام الملغاة",
                timer: 2000,
                showConfirmButton: false,
            });
        }
        return;
    }

    try {
        let url;
        const requestData = { status: newStatus };
        const taskUserId =
            cardElement.data("task-user-id") || dragData.taskUserId;
        const isTemplate =
            dragData.isTemplate === "true" ||
            dragData.isTemplate === true ||
            cardElement.data("is-template") === "true" ||
            cardElement.data("is-template") === true;
        if (isTemplate) {
            url = `/template-tasks/${taskUserId}/update-status`;
        } else {
            url = `/task-users/${taskUserId}/update-status`;
        }
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
                Accept: "application/json",
            },
            body: JSON.stringify(requestData),
        });

        const result = await response.json();

        if (!response.ok || result.success === false) {
            const errorMessage =
                result.message || "حدث خطأ أثناء تحديث حالة المهمة";

            const isProjectCancelledError =
                errorMessage.includes("المشروع تم إلغاؤه") ||
                errorMessage.includes("إلغاؤه") ||
                result.code === 403;

            if (result.pending_items && result.pending_items.length > 0) {
                const itemsList = result.pending_items
                    .map((item) => `• ${item.title}`)
                    .join("\n");
                throw new Error(
                    `${errorMessage}\n\nالبنود المتبقية:\n${itemsList}`
                );
            }

            if (isProjectCancelledError && typeof Swal !== "undefined") {
                window.myTasksAlertShown = true;
                Swal.fire({
                    icon: "error",
                    title: "المشروع تم إلغاؤه",
                    html: errorMessage.replace(/\n/g, "<br>"),
                    confirmButtonText: "حسناً",
                    customClass: {
                        popup: "text-end",
                    },
                }).then(() => {
                    setTimeout(() => {
                        window.myTasksAlertShown = false;
                    }, 100);
                });
                return;
            }

            throw new Error(errorMessage);
        }

        if (result.success === true) {
            const newColumn = $(`#my-cards-${newStatus}`);

            if (newColumn.length) {
                newColumn.append(cardElement);
                cardElement.data("status", newStatus);
                cardElement.attr("data-status", newStatus);

                if (
                    window.MyTasksKanban &&
                    window.MyTasksKanban.updateCardCounters
                ) {
                    window.MyTasksKanban.updateCardCounters();
                }
            }
            if (newStatus === "in_progress") {
                if (result.task && result.task.started_at) {
                    const startedAtDate = new Date(result.task.started_at);
                    const startedAtTimestamp = startedAtDate.getTime();
                    cardElement.attr("data-started-at", startedAtTimestamp);
                } else {
                    const currentTimestamp = new Date().getTime();
                    cardElement.attr("data-started-at", currentTimestamp);
                }
            } else {
                cardElement.attr("data-started-at", "");
                if (result.minutesSpent !== undefined) {
                    const currentMinutes = parseInt(
                        cardElement.attr("data-initial-minutes") || "0"
                    );
                    const newTotalMinutes =
                        currentMinutes + result.minutesSpent;
                    cardElement.attr("data-initial-minutes", newTotalMinutes);
                }
            }

            if (result.task) {
                updateActualTimeDisplay(cardElement, result.task);
            }
            window.MyTasksUtils.updateMyTasksCounters();
            handleMyTaskTimerStatusChange(dragData.taskUserId, newStatus);
            setTimeout(() => {
                if (
                    window.MyTasksTimers &&
                    window.MyTasksTimers.calculateInitialTotalTime
                ) {
                    window.MyTasksTimers.calculateInitialTotalTime();
                }
            }, 500);

            if (typeof Swal !== "undefined" && !window.myTasksAlertShown) {
                window.myTasksAlertShown = true;
                Swal.fire({
                    icon: "success",
                    title: "نجح!",
                    text: "تم تحديث حالة المهمة بنجاح",
                    showConfirmButton: false,
                    timer: 2000,
                }).then(() => {
                    setTimeout(() => {
                        window.myTasksAlertShown = false;
                    }, 100);
                });
            } else if (!window.myTasksAlertShown) {
                window.myTasksAlertShown = true;
                alert("تم تحديث حالة المهمة بنجاح");
                setTimeout(() => {
                    window.myTasksAlertShown = false;
                }, 2000);
            }
        }
    } catch (error) {
        const errorMessage = error.message || "حدث خطأ أثناء تحديث حالة المهمة";

        if (typeof Swal !== "undefined" && !window.myTasksAlertShown) {
            window.myTasksAlertShown = true;

            const isItemsError = errorMessage.includes("البنود المتبقية:");

            Swal.fire({
                icon: "warning",
                title: isItemsError ? "⚠️ يجب إكمال البنود أولاً!" : "خطأ!",
                html: errorMessage.replace(/\n/g, "<br>"),
                confirmButtonText: "حسناً",
                width: isItemsError ? "500px" : "400px",
                customClass: {
                    popup: "text-end",
                    htmlContainer: isItemsError ? "text-start" : "",
                },
            }).then(() => {
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
    const task = document.querySelector(
        `.my-kanban-card[data-task-user-id="${taskUserId}"]`
    );
    if (!task) {
        // Try with kanban-card class (without my- prefix)
        const taskAlt = document.querySelector(
            `.kanban-card[data-task-user-id="${taskUserId}"]`
        );
        if (taskAlt) {
            handleTimerUIUpdate(taskAlt, taskUserId, newStatus);
        }
        return;
    }

    handleTimerUIUpdate(task, taskUserId, newStatus);
}

function handleTimerUIUpdate(task, taskUserId, newStatus) {
    // Update CSS classes
    task.classList.remove(
        "task-in-progress",
        "task-paused",
        "task-completed",
        "task-new"
    );
    task.classList.add(`task-${newStatus}`);

    // Update data-status attribute
    task.setAttribute("data-status", newStatus);

    // Get or create timer container
    const timerContainer = task.querySelector(
        ".kanban-card-timer, .my-kanban-card-timer"
    );
    const taskId = task.getAttribute("data-task-id") || taskUserId;

    // Dispatch event
    window.MyTasksUtils.dispatchTimerEvent(newStatus, taskUserId);

    switch (newStatus) {
        case "in_progress":
            // إضافة/إظهار التايمر
            if (!timerContainer) {
                // Create timer element if it doesn't exist
                const newTimer = document.createElement("div");
                newTimer.className = "kanban-card-timer";
                newTimer.innerHTML = `<i class="fas fa-clock"></i> <span id="my-kanban-timer-${taskId}">00:00:00</span>`;

                // Insert timer before due date or actions
                const dueDate = task.querySelector(".kanban-card-due-date");
                const actions = task.querySelector(".kanban-card-actions");
                if (dueDate) {
                    dueDate.parentNode.insertBefore(newTimer, dueDate);
                } else if (actions) {
                    actions.parentNode.insertBefore(newTimer, actions);
                } else {
                    task.appendChild(newTimer);
                }
            } else {
                // Show existing timer
                timerContainer.style.display = "block";
            }

            // Start the timer
            window.MyTasksTimers.startTimer(taskUserId);
            break;

        case "paused":
            // إخفاء التايمر وإيقافه
            if (timerContainer) {
                timerContainer.style.display = "none";
            }
            window.MyTasksTimers.pauseTimer(taskUserId);
            break;

        case "completed":
            // إخفاء التايمر وإنهائه
            if (timerContainer) {
                timerContainer.style.display = "none";
            }
            window.MyTasksTimers.finishTimer(taskUserId);
            break;

        default:
            // إخفاء التايمر وإيقافه
            if (timerContainer) {
                timerContainer.style.display = "none";
            }
            window.MyTasksTimers.pauseTimer(taskUserId);
            break;
    }
}

function updateActualTimeDisplay(cardElement, taskData) {
    let actualHours = 0;
    let actualMinutes = 0;

    if (taskData.actual_hours !== undefined) {
        actualHours = parseInt(taskData.actual_hours) || 0;
    }
    if (taskData.actual_minutes !== undefined) {
        actualMinutes = parseInt(taskData.actual_minutes) || 0;
    }

    const timeContainer = cardElement.find(".kanban-card-time");
    if (timeContainer.length) {
        const spans = timeContainer.find("span");
        if (spans.length >= 2) {
            const actualTimeSpan = spans.eq(1);
            const formattedMinutes = String(actualMinutes).padStart(2, "0");
            actualTimeSpan.text(`فعلي: ${actualHours}:${formattedMinutes}`);
        } else if (spans.length === 1) {
            const actualTimeSpan = spans.first();
            if (actualTimeSpan.text().includes("فعلي:")) {
                const formattedMinutes = String(actualMinutes).padStart(2, "0");
                actualTimeSpan.text(`فعلي: ${actualHours}:${formattedMinutes}`);
            }
        }
    }

    cardElement.attr("data-initial-minutes", actualHours * 60 + actualMinutes);
}

window.MyTasksDragDrop = {
    initializeMyTasksDragDrop,
    addDragDropToCard,
    initializeDropZones,
    updateMyTaskStatus,
    handleMyTaskTimerStatusChange,
    updateActualTimeDisplay,
};
