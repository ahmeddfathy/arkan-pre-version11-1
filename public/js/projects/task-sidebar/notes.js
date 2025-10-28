// Task Notes Functions
let isLoadingNotes = false;

async function loadTaskNotes(taskType, taskUserId) {
    // Prevent multiple simultaneous loading
    if (isLoadingNotes) {
        return;
    }

    isLoadingNotes = true;
    currentTaskType = taskType;
    currentTaskUserId = taskUserId;

    const notesContainer = document.getElementById('notesContainer');

    // Check if notesContainer exists before proceeding
    if (!notesContainer) {
        console.error('notesContainer not found');
        isLoadingNotes = false;
        return;
    }

    // Only show loading spinner if container is empty or showing error
    const hasNotes = notesContainer && !notesContainer.innerHTML.includes('لا توجد ملاحظات') &&
                    !notesContainer.innerHTML.includes('حدث خطأ') &&
                    !notesContainer.innerHTML.includes('جاري تحميل');

    if (!hasNotes) {
        notesContainer.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">جاري تحميل الملاحظات...</span>
                </div>
                <p class="mt-2 text-muted mb-0" style="font-size: 12px;">جاري تحميل الملاحظات...</p>
            </div>
        `;
    }

    try {
        const response = await fetch(`/task-notes/${taskType}/${taskUserId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            displayNotes(result.notes);
        } else {
            showNotesError('حدث خطأ في تحميل الملاحظات');
        }
    } catch (error) {
        console.error('Error loading notes:', error);
        showNotesError('حدث خطأ في الاتصال بالخادم');
    } finally {
        isLoadingNotes = false;
    }
}

function displayNotes(notes) {
    const notesContainer = document.getElementById('notesContainer');

    // Check if notesContainer exists before proceeding
    if (!notesContainer) {
        console.error('notesContainer not found in displayNotes');
        return;
    }

    if (!notes || notes.length === 0) {
        notesContainer.innerHTML = `
            <div class="text-center py-3 text-muted">
                <i class="fas fa-sticky-note" style="font-size: 24px; opacity: 0.3;"></i>
                <p class="mt-2 mb-0" style="font-size: 12px;">لا توجد ملاحظات بعد</p>
                <small style="font-size: 11px; opacity: 0.7;">اضغط "إضافة ملاحظة" لكتابة ملاحظتك الأولى</small>
            </div>
        `;
        return;
    }

    const notesHtml = notes.map(note => `
        <div class="note-item mb-3" data-note-id="${note.id}">
            <div class="border rounded p-3" style="background: #ffffff; border-color: #e9ecef !important;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="note-content" style="flex: 1;">
                        <p class="mb-0" style="font-size: 13px; line-height: 1.5; word-wrap: break-word;">${escapeHtml(note.content)}</p>
                    </div>
                    <div class="note-actions ms-2">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false"
                                    style="font-size: 11px; padding: 2px 6px;">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="editNote(${note.id}, '${escapeHtml(note.content)}')">
                                    <i class="fas fa-edit me-1"></i>تعديل
                                </a></li>
                                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteNote(${note.id})">
                                    <i class="fas fa-trash me-1"></i>حذف
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <small class="text-muted" style="font-size: 11px;">
                        <i class="fas fa-clock me-1"></i>
                        ${formatDateTime(note.created_at)}
                    </small>
                    ${note.created_at !== note.updated_at ? `
                        <small class="text-muted" style="font-size: 10px;">
                            <i class="fas fa-edit me-1"></i>معدلة
                        </small>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');

    notesContainer.innerHTML = notesHtml;
}

function showAddNoteForm(taskType, taskUserId) {
    const addNoteForm = document.getElementById('addNoteForm');
    const noteContent = document.getElementById('noteContent');

    currentTaskType = taskType;
    currentTaskUserId = taskUserId;

    noteContent.value = '';
    addNoteForm.style.display = 'block';
    noteContent.focus();
}

function hideAddNoteForm() {
    const addNoteForm = document.getElementById('addNoteForm');
    const noteContent = document.getElementById('noteContent');

    addNoteForm.style.display = 'none';
    noteContent.value = '';
}

/**
 * Save new note
 */
async function saveNote() {
    const noteContent = document.getElementById('noteContent');
    const content = noteContent.value.trim();

    if (!content) {
        alert('يرجى كتابة محتوى الملاحظة');
        return;
    }

    try {
        const requestData = {
            task_type: currentTaskType,
            content: content
        };

        if (currentTaskType === 'regular') {
            requestData.task_user_id = currentTaskUserId;
        } else {
            requestData.template_task_user_id = currentTaskUserId;
        }

        const response = await fetch('/task-notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(requestData)
        });

        const result = await response.json();

        if (result.success) {
            hideAddNoteForm();
            loadTaskNotes(currentTaskType, currentTaskUserId);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم!',
                    text: 'تم إضافة الملاحظة بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            alert('خطأ: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving note:', error);
        alert('حدث خطأ في حفظ الملاحظة');
    }
}

/**
 * Edit existing note
 */
function editNote(noteId, currentContent) {
    const noteItem = document.querySelector(`[data-note-id="${noteId}"]`);
    const noteContentDiv = noteItem.querySelector('.note-content');

    // Create edit form
    const editForm = `
        <div class="edit-note-form">
            <textarea class="form-control mb-2" rows="3" style="font-size: 13px;">${escapeHtml(currentContent)}</textarea>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm" onclick="updateNote(${noteId})">
                    <i class="fas fa-save me-1"></i>حفظ
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="cancelEditNote(${noteId}, '${escapeHtml(currentContent)}')">
                    إلغاء
                </button>
            </div>
        </div>
    `;

    noteContentDiv.innerHTML = editForm;
    noteContentDiv.querySelector('textarea').focus();
}

/**
 * Cancel edit note
 */
function cancelEditNote(noteId, originalContent) {
    const noteItem = document.querySelector(`[data-note-id="${noteId}"]`);
    const noteContentDiv = noteItem.querySelector('.note-content');

    noteContentDiv.innerHTML = `<p class="mb-0" style="font-size: 13px; line-height: 1.5; word-wrap: break-word;">${escapeHtml(originalContent)}</p>`;
}

/**
 * Update existing note
 */
async function updateNote(noteId) {
    const noteItem = document.querySelector(`[data-note-id="${noteId}"]`);
    const textarea = noteItem.querySelector('textarea');
    const content = textarea.value.trim();

    if (!content) {
        alert('يرجى كتابة محتوى الملاحظة');
        return;
    }

    try {
        const response = await fetch(`/task-notes/${noteId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ content: content })
        });

        const result = await response.json();

        if (result.success) {
            loadTaskNotes(currentTaskType, currentTaskUserId);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم!',
                    text: 'تم تحديث الملاحظة بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            alert('خطأ: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating note:', error);
        alert('حدث خطأ في تحديث الملاحظة');
    }
}

/**
 * Delete note
 */
async function deleteNote(noteId) {
    if (typeof Swal !== 'undefined') {
        const result = await Swal.fire({
            title: 'تأكيد الحذف',
            text: 'هل أنت متأكد من حذف هذه الملاحظة؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء'
        });

        if (!result.isConfirmed) return;
    } else {
        if (!confirm('هل أنت متأكد من حذف هذه الملاحظة؟')) return;
    }

    try {
        const response = await fetch(`/task-notes/${noteId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const result = await response.json();

        if (result.success) {
            loadTaskNotes(currentTaskType, currentTaskUserId);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم!',
                    text: 'تم حذف الملاحظة بنجاح',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            alert('خطأ: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting note:', error);
        alert('حدث خطأ في حذف الملاحظة');
    }
}

/**
 * Show notes error
 */
function showNotesError(message) {
    const notesContainer = document.getElementById('notesContainer');

    // Check if notesContainer exists before proceeding
    if (!notesContainer) {
        console.error('notesContainer not found in showNotesError');
        return;
    }

    notesContainer.innerHTML = `
        <div class="text-center py-3 text-danger">
            <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i>
            <p class="mt-2 mb-0" style="font-size: 12px;">${message}</p>
            <button class="btn btn-outline-primary btn-sm mt-2" onclick="loadTaskNotes(currentTaskType, currentTaskUserId)" style="font-size: 11px;">
                <i class="fas fa-refresh me-1"></i>إعادة المحاولة
            </button>
        </div>
    `;
}
