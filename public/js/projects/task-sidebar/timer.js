// Task Timer Functions

function startSidebarTimer(task) {
    // Clear any existing timer
    if (sidebarTimerInterval) {
        clearInterval(sidebarTimerInterval);
        sidebarTimerInterval = null;
    }

    const timerElement = document.getElementById(`sidebar-timer-${task.id}`);
    if (!timerElement) return;

    let totalSeconds = (task.actual_minutes || 0) * 60;

    if (task.status === 'in_progress') {
        let startTime = task.started_at || task.start_date;

        if (startTime) {
            let startedAtTimestamp;
            if (typeof startTime === 'string') {
                if (startTime.match(/^\d+$/)) {
                    startedAtTimestamp = parseInt(startTime);
                } else {
                    startedAtTimestamp = new Date(startTime).getTime();
                }
            } else {
                startedAtTimestamp = new Date(startTime).getTime();
            }

            if (startedAtTimestamp && !isNaN(startedAtTimestamp)) {
                const currentTime = new Date().getTime();
                const elapsedSeconds = Math.floor((currentTime - startedAtTimestamp) / 1000);
                totalSeconds += elapsedSeconds;
            }
        }
    }

    updateSidebarTimerDisplay(timerElement, totalSeconds);

    sidebarTimerInterval = setInterval(() => {
        totalSeconds++;
        updateSidebarTimerDisplay(timerElement, totalSeconds);
    }, 1000);
}

function updateSidebarTimerDisplay(timerElement, totalSeconds) {
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const timeDisplay = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    timerElement.textContent = timeDisplay;
}

function stopSidebarTimer() {
    if (sidebarTimerInterval) {
        clearInterval(sidebarTimerInterval);
        sidebarTimerInterval = null;
    }
}

// Start new task function
async function startTask(event, taskType, taskId) {
    try {
        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري البدء...';

        let endpoint;
        if (taskType === 'template') {
            endpoint = `/template-tasks/${taskId}/update-status`;
        } else {
            endpoint = `/task-users/${taskId}/update-status`;
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status: 'in_progress' })
        });

        const result = await response.json();

        if (result.success) {
            // Show success message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: 'تم بدء المهمة بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            const taskUserId = taskType === 'template' ? taskId : taskId;
            loadTaskDetails(taskType, taskUserId);

            if (window.location.href.includes('/projects/')) {
                setTimeout(() => {
                    if (typeof loadKanbanBoard === 'function') {
                        loadKanbanBoard();
                    } else {
                        window.location.reload();
                    }
                }, 500);
            }

        } else {
            throw new Error(result.message || 'حدث خطأ في بدء المهمة');
        }

    } catch (error) {
        console.error('Error starting task:', error);

        // Show error message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'خطأ!',
                text: 'حدث خطأ في بدء المهمة: ' + error.message,
                icon: 'error',
                confirmButtonText: 'موافق'
            });
        } else {
            alert('حدث خطأ في بدء المهمة: ' + error.message);
        }

        // Restore button state
        if (button) {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }
}

// Resume paused task function
async function resumeTask(event, taskType, taskId) {
    try {
        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>جاري الاستئناف...';

        let endpoint;
        if (taskType === 'template') {
            endpoint = `/template-tasks/${taskId}/update-status`;
        } else {
            endpoint = `/task-users/${taskId}/update-status`;
        }

        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ status: 'in_progress' })
        });

        const result = await response.json();

        if (result.success) {
            // Show success message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: 'تم استئناف المهمة بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            const taskUserId = taskType === 'template' ? taskId : taskId;
            loadTaskDetails(taskType, taskUserId);

            if (window.location.href.includes('/projects/')) {
                setTimeout(() => {
                    if (typeof loadKanbanBoard === 'function') {
                        loadKanbanBoard();
                    } else {
                        window.location.reload();
                    }
                }, 500);
            }

        } else {
            throw new Error(result.message || 'حدث خطأ في استئناف المهمة');
        }

    } catch (error) {
        console.error('Error resuming task:', error);

        // Show error message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'خطأ!',
                text: 'حدث خطأ في استئناف المهمة: ' + error.message,
                icon: 'error',
                confirmButtonText: 'موافق'
            });
        } else {
            alert('حدث خطأ في استئناف المهمة: ' + error.message);
        }

        // Restore button state
        if (button) {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }
}
