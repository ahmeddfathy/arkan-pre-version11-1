// ✨ Task Items Management for Create Task Modal
// نظام إدارة بنود المهمة في فورم الإنشاء

let createTaskItems = []; // Array to store task items

$(document).ready(function() {
    initializeTaskItemsManagement();
});

function initializeTaskItemsManagement() {
    // زر إضافة بند
    $('#addTaskItemBtn').click(function() {
        $('#itemFormContainer').slideDown(300);
        $('#newItemTitleInput').focus();
    });

    // زر إلغاء
    $('#cancelItemBtn').click(function() {
        clearItemForm();
        $('#itemFormContainer').slideUp(300);
    });

    // زر حفظ البند
    $('#saveItemBtn').click(function() {
        const title = $('#newItemTitleInput').val().trim();
        const description = $('#newItemDescInput').val().trim();

        if (!title) {
            Swal.fire({
                icon: 'warning',
                title: 'تنبيه',
                text: 'يرجى إدخال عنوان البند',
                confirmButtonText: 'حسناً'
            });
            return;
        }

        addTaskItem(title, description);
        clearItemForm();
        $('#itemFormContainer').slideUp(300);
    });

    // حذف بند
    $(document).on('click', '.remove-task-item', function() {
        const index = $(this).data('index');
        removeTaskItem(index);
    });

    // Reset items when modal closes
    $('#createTaskModal').on('hidden.bs.modal', function() {
        createTaskItems = [];
        renderTaskItems();
        clearItemForm();
        $('#itemFormContainer').hide();
    });
}

function addTaskItem(title, description) {
    const item = {
        title: title,
        description: description || '',
        status: 'pending',
        order: createTaskItems.length + 1
    };

    createTaskItems.push(item);
    renderTaskItems();

    // Show success message
    Swal.fire({
        icon: 'success',
        title: 'تم الإضافة',
        text: 'تم إضافة البند بنجاح',
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

function removeTaskItem(index) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم حذف هذا البند',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            createTaskItems.splice(index, 1);
            renderTaskItems();

            Swal.fire({
                icon: 'success',
                title: 'تم الحذف',
                text: 'تم حذف البند بنجاح',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    });
}

function renderTaskItems() {
    const container = $('#taskItemsContainer');
    const noItemsMsg = $('#noItemsMsg');

    if (createTaskItems.length === 0) {
        container.html(`
            <p class="text-muted text-center py-2 mb-0 small" id="noItemsMsg">
                <i class="fas fa-info-circle"></i> لم يتم إضافة بنود بعد
            </p>
        `);
        return;
    }

    let html = '<div class="list-group list-group-flush">';

    createTaskItems.forEach((item, index) => {
        html += `
            <div class="list-group-item px-2 py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-check-circle text-success me-2" style="font-size: 14px;"></i>
                            <strong class="text-dark" style="font-size: 13px;">${item.title}</strong>
                        </div>
                        ${item.description ? `
                            <p class="text-muted mb-0 ms-4" style="font-size: 12px;">
                                ${item.description}
                            </p>
                        ` : ''}
                    </div>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger remove-task-item ms-2"
                            data-index="${index}"
                            title="حذف البند">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.html(html);
}

function clearItemForm() {
    $('#newItemTitleInput').val('');
    $('#newItemDescInput').val('');
}

// Export for use in forms.js
window.createTaskItems = createTaskItems;
window.addTaskItem = addTaskItem;
window.removeTaskItem = removeTaskItem;
window.renderTaskItems = renderTaskItems;

