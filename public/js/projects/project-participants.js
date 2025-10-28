
document.addEventListener('DOMContentLoaded', function() {
    initParticipantManagement();

    // إخفاء موديل المهام عند إغلاقه
    const templateModal = document.getElementById('templateTasksModal');
    if (templateModal) {
        templateModal.addEventListener('hidden.bs.modal', function() {
            this.style.display = 'none !important';

        });
    }


});

function initParticipantManagement() {
    // ربط أزرار الحذف - كلا النوعين من CSS classes
    document.querySelectorAll('.remove-participant-btn, .remove-participant-btn-badge').forEach(btn => {
        btn.addEventListener('click', handleParticipantRemoval);
    });

    document.querySelectorAll('.add-participant-form').forEach(form => {
        form.addEventListener('submit', handleAddParticipant);
    });

    // إضافة معالج للـ forms الخاصة بإضافة مالك الفريق
    document.querySelectorAll('.add-team-owner-form').forEach(form => {
        form.addEventListener('submit', handleAddTeamOwner);
    });
}

function handleParticipantRemoval() {
    const userId = this.dataset.userId;
    const serviceId = this.dataset.serviceId;
    const projectId = this.dataset.projectId;
    const userName = this.dataset.userName;
    const serviceName = this.dataset.serviceName;

    Swal.fire({
        title: 'تأكيد الإزالة',
        html: `هل أنت متأكد من إزالة <strong>${userName}</strong> من خدمة <strong>${serviceName}</strong>؟<br><br><span class="text-warning">سيتم حذف جميع المهام غير المبدوءة.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'نعم، إزالة',
        cancelButtonText: 'إلغاء'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/projects/${projectId}/remove-participant`,
                type: 'POST',
                data: {
                    user_id: userId,
                    service_id: serviceId,
                    _token: document.querySelector('meta[name="csrf-token"]').content
                },
                success: function(response) {
                    if (response.success) {
                        // إزالة العنصر من الـ UI فوراً
                        const participantElement = this.closest('.badge') ||
                                                 this.closest('.participant-card') ||
                                                 this.closest('[class*="participant"]');

                        if (participantElement) {
                            participantElement.style.transition = 'opacity 0.3s';
                            participantElement.style.opacity = '0';
                            setTimeout(() => {
                                participantElement.remove();

                                // فحص إذا لم يعد هناك مشاركون
                                const container = participantElement.closest('.participants-container') ||
                                                participantElement.closest('.d-flex.flex-wrap');
                                if (container && container.children.length === 0) {
                                    container.innerHTML = '<span class="text-muted">لا يوجد مشاركون بعد</span>';
                                }
                            }, 300);
                        }

                        Swal.fire({
                            title: 'تم بنجاح!',
                            html: response.message,
                            icon: 'success',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });

                        // تحديث الجدول عن طريق AJAX بتأخير قصير
                        setTimeout(() => {
                            refreshParticipantsTable();
                        }, 500);
                    } else {
                        Swal.fire({
                            title: 'خطأ',
                            text: response.message,
                            icon: 'error'
                        });
                    }
                }.bind(this),
                error: function(xhr) {
                    let errorMessage = 'حدث خطأ أثناء إزالة المستخدم';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;

                        if (xhr.responseJSON.active_tasks > 0) {
                            Swal.fire({
                                title: 'لا يمكن إزالة المستخدم',
                                html: `لا يمكن إزالة <strong>${userName}</strong> من المشروع.<br>لديه <strong>${xhr.responseJSON.active_tasks}</strong> مهمة قيد التنفيذ أو مكتملة.<br><br>يجب إكمال أو نقل هذه المهام أولاً.`,
                                icon: 'error',
                                confirmButtonText: 'فهمت'
                            });
                            return;
                        }
                    }
                    Swal.fire({
                        title: 'خطأ',
                        text: errorMessage,
                        icon: 'error'
                    });
                }
            });
        }
    });
}

function handleAddParticipant(e) {
    e.preventDefault();

    const form = this;
    const select = form.querySelector('select');
    if (!select) return;

    const selectNameMatch = select.name.match(/\[(\d+)\]/);
    if (!selectNameMatch) return;

    const serviceId = selectNameMatch[1];
    const userId = select.value;
    const userName = select.options[select.selectedIndex]?.text;

    if (!userId || userId === '') {
        toastr.warning('يرجى اختيار موظف أولاً', 'تنبيه');
        return;
    }

    // فتح sidebar إضافة المشارك
    openAddParticipantSidebar(serviceId, userId, userName, form);
}

// ✅ ملء قائمة الأدوار بناءً على الخدمة
function populateRoleDropdown(serviceId) {
    const roleSection = document.getElementById('roleSelectionSection');
    const roleSelect = document.getElementById('participantRoleId');

    if (!roleSection || !roleSelect) return;

    // البحث عن الخدمة في البيانات
    const service = window.packageServicesData ? window.packageServicesData.find(s => s.id == serviceId) : null;

    // إعادة تعيين القائمة
    roleSelect.innerHTML = '<option value="">-- اختر الدور --</option>';

    // إذا كانت الخدمة لديها أدوار مطلوبة
    if (service && service.required_roles && service.required_roles.length > 0) {
        service.required_roles.forEach(role => {
            const option = document.createElement('option');
            option.value = role.id;
            option.textContent = role.name;
            roleSelect.appendChild(option);
        });

        // إظهار قسم الأدوار
        roleSection.style.display = 'block';
    } else {
        // إخفاء قسم الأدوار إذا لم تكن هناك أدوار مطلوبة
        roleSection.style.display = 'none';
    }
}

// ✅ فتح sidebar إضافة المشارك
function openAddParticipantSidebar(serviceId, userId, userName, form) {
    const sidebar = document.getElementById('addParticipantSidebar');
    const overlay = document.getElementById('addParticipantSidebarOverlay');

    // ملء البيانات في الـ sidebar
    document.getElementById('participantServiceId').value = serviceId;
    document.getElementById('participantUserId').value = userId;
    document.getElementById('participantUserName').value = userName;
    document.getElementById('selectedEmployeeName').textContent = userName;

    // جلب اسم الخدمة
    const serviceBadge = form.closest('tr').querySelector('.badge.bg-primary, .badge.bg-info');
    const serviceName = serviceBadge ? serviceBadge.textContent.trim() : 'غير محدد';
    document.getElementById('selectedServiceName').textContent = serviceName;

    // ملء قائمة الأدوار المطلوبة للخدمة
    populateRoleDropdown(serviceId);

    // إظهار الـ sidebar
    sidebar.classList.add('show');
    overlay.classList.add('show');
    document.body.classList.add('add-participant-sidebar-open');

    // إعداد event listeners
    setupAddParticipantSidebarEvents(form);
}

// ✅ إغلاق sidebar إضافة المشارك
function closeAddParticipantSidebar() {
    const sidebar = document.getElementById('addParticipantSidebar');
    const overlay = document.getElementById('addParticipantSidebarOverlay');

    sidebar.classList.remove('show');
    overlay.classList.remove('show');
    document.body.classList.remove('add-participant-sidebar-open');

    // إعادة تعيين النموذج
    resetAddParticipantForm();
}

// ✅ إعداد event listeners للـ sidebar
function setupAddParticipantSidebarEvents(form) {
    // معالجة checkbox "لا يوجد موعد نهائي"
    const noDeadlineCheckbox = document.getElementById('noDeadlineCheck');
    const deadlineInput = document.getElementById('participantDeadline');

    if (noDeadlineCheckbox && deadlineInput) {
        noDeadlineCheckbox.addEventListener('change', function() {
            deadlineInput.disabled = this.checked;
            if (this.checked) {
                deadlineInput.value = '';
            }
        });
    }

    // معالجة زر إضافة المشارك
    const confirmBtn = document.getElementById('confirmAddParticipantBtn');
    if (confirmBtn) {
        confirmBtn.onclick = () => {
            handleAddParticipantFromSidebar(form);
        };
    }
}

// ✅ معالجة إضافة المشارك من الـ sidebar
function handleAddParticipantFromSidebar(form) {
    const serviceId = document.getElementById('participantServiceId').value;
    const userId = document.getElementById('participantUserId').value;
    const userName = document.getElementById('participantUserName').value;
    const deadline = document.getElementById('participantDeadline').value;
    const noDeadline = document.getElementById('noDeadlineCheck').checked;
    const confirmBtn = document.getElementById('confirmAddParticipantBtn');

        // التحقق من البيانات
    if (!serviceId || !userId || !userName) {
        closeAddParticipantSidebar();
        setTimeout(() => {
            Swal.fire({
                title: 'خطأ',
                text: 'بيانات غير صحيحة',
                icon: 'error',
                confirmButtonText: 'حسناً'
            });
        }, 100);
        return;
    }

    // التحقق من صحة التاريخ إذا كان محدد
    if (!noDeadline && deadline) {
        const deadlineDate = new Date(deadline);
        const now = new Date();

        if (deadlineDate <= now) {
            closeAddParticipantSidebar();
            setTimeout(() => {
                Swal.fire({
                    title: 'خطأ',
                    text: 'يجب أن يكون الموعد النهائي في المستقبل',
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
            }, 100);
            return;
        }
    }

        // إظهار loading state
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الإضافة...';
    }

    // إضافة deadline للنموذج إذا كان محدد
    if (!noDeadline && deadline) {
                const deadlineInput = document.createElement('input');
                deadlineInput.type = 'hidden';
                deadlineInput.name = `deadlines[${userId}]`;
        deadlineInput.value = deadline;
                form.appendChild(deadlineInput);
    }

    // معالجة إضافة المشارك (بدون إغلاق الـ sidebar)
    processAddParticipantFromSidebar(form, serviceId, userId, userName);
}

// ✅ معالجة إضافة المشارك من الـ sidebar
function processAddParticipantFromSidebar(form, serviceId, userId, userName) {
    const formData = new FormData();
    formData.append(`participants[${serviceId}][]`, userId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    // إضافة بيانات deadlines إذا كانت موجودة في النموذج
    const deadlineInputs = form.querySelectorAll('input[name^="deadlines["]');
    deadlineInputs.forEach(input => {
        formData.append(input.name, input.value);
    });

    // إضافة نسبة المشاركة (project_share)
    const projectShare = document.getElementById('participantProjectShare');
    if (projectShare && projectShare.value) {
        formData.append(`project_shares[${userId}]`, projectShare.value);
    }

    // إضافة الدور المختار (role_id)
    const roleId = document.getElementById('participantRoleId');
    if (roleId && roleId.value) {
        formData.append(`participant_role[${serviceId}]`, roleId.value);
    }

    console.log("Sending request to:", form.action);
    console.log("Form data:", formData);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log("Response status:", response.status);
        console.log("Response headers:", response.headers);

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        return response.text().then(text => {
            console.log("Raw response text:", text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error("JSON parsing error:", e);
                console.error("Response text that failed to parse:", text);
                throw new Error("Invalid JSON response");
            }
        });
    })
    .then(data => {
        console.log("Response data:", data);

        if (data.success) {
            // إغلاق الـ sidebar
            closeAddParticipantSidebar();

            // تحديث الجدول عن طريق AJAX
            refreshParticipantsTable();

            // إظهار SweetAlert في الصفحة الرئيسية
    setTimeout(() => {
                Swal.fire({
                    title: 'تم بنجاح!',
                    html: `تم إضافة <strong>${userName}</strong> إلى المشروع بنجاح.`,
                    icon: 'success',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
    }, 100);

        } else {
            // إغلاق الـ sidebar
            closeAddParticipantSidebar();

            // إظهار رسالة خطأ كـ SweetAlert في الصفحة الرئيسية
    setTimeout(() => {
                Swal.fire({
                    title: 'خطأ',
                    text: data.message || 'حدث خطأ أثناء إضافة المستخدم',
                    icon: 'error',
                    confirmButtonText: 'حسناً'
                });
            }, 100);
        }
    })
    .catch(error => {
        console.error("Error:", error);

        // إغلاق الـ sidebar
        closeAddParticipantSidebar();

        // إظهار رسالة خطأ كـ SweetAlert في الصفحة الرئيسية
        setTimeout(() => {
            Swal.fire({
                title: 'خطأ في الاتصال',
                text: 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.',
                icon: 'error',
                confirmButtonText: 'حسناً'
            });
    }, 100);
    });
}

// ✅ تحديث جدول المشاركين عن طريق AJAX
function refreshParticipantsTable() {
    console.log('Refreshing participants table...');

    // البحث عن container المشاركين بدقة أكبر
    const participantsTable = document.querySelector('.table-responsive');

    if (!participantsTable) {
        console.log('No table found to refresh');
        return;
    }

    // إظهار loading indicator
    participantsTable.style.opacity = '0.5';
    participantsTable.style.pointerEvents = 'none';

    // جلب الصفحة الحالية مرة أخرى للحصول على البيانات المحدثة
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.text();
    })
    .then(html => {
        // استبدال محتوى الجدول
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // البحث عن الجدول الجديد
        const newTable = doc.querySelector('.table-responsive');

        if (newTable && participantsTable) {
            // استبدال محتوى الجدول فقط
            participantsTable.innerHTML = newTable.innerHTML;
            console.log('Participants table updated successfully');

            // إعادة ربط event listeners
            bindParticipantEventListeners();
        } else {
            console.log('Could not find new table content');
        }
    })
    .catch(error => {
        console.error('Error refreshing participants table:', error);
    })
    .finally(() => {
        // إخفاء loading indicator
        if (participantsTable) {
            participantsTable.style.opacity = '1';
            participantsTable.style.pointerEvents = 'auto';
        }
    });
}

// ✅ ربط event listeners للمشاركين
function bindParticipantEventListeners() {
    // ربط event listeners للأزرار الجديدة - كلا النوعين من CSS classes
    document.querySelectorAll('.remove-participant-btn, .remove-participant-btn-badge').forEach(btn => {
        btn.addEventListener('click', handleParticipantRemoval);
    });

    // ربط event listeners للنماذج الجديدة
    document.querySelectorAll('.add-participant-form').forEach(form => {
        form.addEventListener('submit', handleAddParticipant);
    });

    // ربط event listeners للفرق
    document.querySelectorAll('.add-team-owner-form').forEach(form => {
        form.addEventListener('submit', handleAddTeamOwner);
    });

    // إعادة تطبيق دالة إضافة أزرار الموعد النهائي
    addDeadlineButtonsToExistingUsers();
}

// ✅ إضافة المشارك للـ UI
function addParticipantToUI(form, serviceId, userId, userName) {
    // البحث عن الـ table row بناءً على serviceId
    let tableRow = null;

    const allRows = document.querySelectorAll('table tbody tr');
    for (let row of allRows) {
        const rowForm = row.querySelector('form');
        if (rowForm && rowForm.action.includes(`/projects/`)) {

            const formData = new FormData(rowForm);
            for (let [key, value] of formData.entries()) {
                if (key.includes('participants[') && key.includes(`[${serviceId}]`)) {
                    tableRow = row;
                    break;
                }
            }
        }
        if (tableRow) break;
    }

    // إذا لم نجد الـ row، نستخدم أول row في الـ table
    if (!tableRow) {
        tableRow = document.querySelector('table tbody tr');
    }

        if (!tableRow) {
        console.error('Table row not found for serviceId:', serviceId);
        return;
    }

    console.log('Found table row:', tableRow);

    const participantsContainer = tableRow.querySelector('.d-flex.flex-wrap');

    if (!participantsContainer) {
        console.error('Participants container not found in table row');
        console.log('Available elements in row:', tableRow.innerHTML);
        return;
    }

    console.log('Found participants container:', participantsContainer);

    const emptyMessage = participantsContainer.querySelector('.text-muted');
    if (emptyMessage) {
        participantsContainer.innerHTML = '';
    }

    const projectId = document.querySelector('meta[name="project-id"]')?.content ||
                      document.getElementById('project-container')?.dataset.projectId ||
                      form.action.match(/\/projects\/(\d+)/)?.[1];

    const serviceName = tableRow.querySelector('.badge.bg-info')?.textContent;

    // إنشاء badge المستخدم الجديد
    const newBadge = document.createElement('div');
    newBadge.className = 'badge bg-success mb-1 p-2';

    newBadge.innerHTML = `
        <i class="fas fa-user me-1"></i>
        ${userName}
        <span class="remove-participant-btn ms-2"
            data-user-id="${userId}"
            data-service-id="${serviceId}"
            data-project-id="${projectId}"
            data-user-name="${userName}"
            data-service-name="${serviceName}">
            <i class="fas fa-times" style="cursor: pointer;"></i>
        </span>
    `;

    console.log('Created new badge:', newBadge);
    console.log('Adding badge to container:', participantsContainer);

    newBadge.style.opacity = '0';
    participantsContainer.appendChild(newBadge);

    console.log('Badge added to container. Container children count:', participantsContainer.children.length);

    setTimeout(() => {
        newBadge.style.transition = 'opacity 0.5s';
        newBadge.style.opacity = '1';
        console.log('Badge animation started');
    }, 10);

    // إزالة المستخدم من القائمة المنسدلة في الـ table row
    const select = tableRow.querySelector('select');
    console.log('Found select element:', select);
    if (select) {
        const optionToRemove = select.querySelector(`option[value="${userId}"]`);
        console.log('Option to remove:', optionToRemove);
        optionToRemove?.remove();
        select.value = '';
        console.log('Select value cleared');
    } else {
        console.log('No select element found in table row');
    }

    // إضافة event listener للزر حذف
    const newRemoveBtn = newBadge.querySelector('.remove-participant-btn');
    if (newRemoveBtn) {
        newRemoveBtn.addEventListener('click', handleParticipantRemoval);
    }
}



// ✅ إعادة تعيين نموذج إضافة المشارك
function resetAddParticipantForm() {
    const form = document.getElementById('addParticipantForm');
    if (form) {
        form.reset();
    }

    // إعادة تعيين القيم المخفية
    document.getElementById('participantServiceId').value = '';
    document.getElementById('participantUserId').value = '';
    document.getElementById('participantUserName').value = '';
    document.getElementById('selectedEmployeeName').textContent = '-';
    document.getElementById('selectedServiceName').textContent = '-';

    // إعادة تعيين حالة الزر
    const confirmBtn = document.getElementById('confirmAddParticipantBtn');
    if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-user-plus me-1"></i> إضافة المشارك';
    }
}

// ✅ إغلاق الـ sidebar عند الضغط على Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('addParticipantSidebar');
        if (sidebar && sidebar.classList.contains('show')) {
            closeAddParticipantSidebar();
        }
    }
});



// ✅ معالجة إضافة المستخدم
function processAddParticipant(form, serviceId, userId, userName) {
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...';

    const formData = new FormData();
    formData.append(`participants[${serviceId}][]`, userId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        // إضافة بيانات deadlines إذا كانت موجودة في النموذج
    const deadlineInputs = form.querySelectorAll('input[name^="deadlines["]');
    deadlineInputs.forEach(input => {
        formData.append(input.name, input.value);
    });

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json().catch(e => {
            console.error("JSON parsing error:", e);
            throw new Error("Invalid JSON response");
        });
    })
    .then(data => {
        console.log("Response data:", data);

        if (data.success) {
            const participantsContainer = form.closest('tr').querySelector('.d-flex.flex-wrap');

            const emptyMessage = participantsContainer.querySelector('.text-muted');
            if (emptyMessage) {
                participantsContainer.innerHTML = '';
            }

            const projectId = document.querySelector('meta[name="project-id"]')?.content ||
                              document.getElementById('project-container')?.dataset.projectId ||
                              form.action.match(/\/projects\/(\d+)/)?.[1];

            const serviceName = form.closest('tr').querySelector('.badge.bg-info')?.textContent;

                        // إنشاء badge المستخدم الجديد
            const newBadge = document.createElement('div');
            newBadge.className = 'badge bg-success mb-1 p-2';

            newBadge.innerHTML = `
                <i class="fas fa-user me-1"></i>
                ${userName}
                <span class="remove-participant-btn ms-2"
                    data-user-id="${userId}"
                    data-service-id="${serviceId}"
                    data-project-id="${projectId}"
                    data-user-name="${userName}"
                    data-service-name="${serviceName}">
                    <i class="fas fa-times" style="cursor: pointer;"></i>
                </span>
            `;

            newBadge.style.opacity = '0';
            participantsContainer.appendChild(newBadge);
            setTimeout(() => {
                newBadge.style.transition = 'opacity 0.5s';
                newBadge.style.opacity = '1';
            }, 10);

            const select = form.querySelector('select');
            select.querySelector(`option[value="${userId}"]`)?.remove();
            select.value = '';

            Swal.fire({
                title: 'تم بنجاح!',
                html: `تم إضافة <strong>${userName}</strong> إلى المشروع بنجاح.`,
                icon: 'success',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });

            const newRemoveBtn = newBadge.querySelector('.remove-participant-btn');
            if (newRemoveBtn) {
                newRemoveBtn.addEventListener('click', handleParticipantRemoval);
            }


        } else {
            Swal.fire({
                title: 'خطأ',
                text: data.message || 'حدث خطأ أثناء إضافة المستخدم',
                icon: 'error'
            });
        }
    })
    .catch(error => {
        console.error("Error:", error);

        const participantsContainer = form.closest('tr').querySelector('.d-flex.flex-wrap');

        if (participantsContainer && !participantsContainer.querySelector(`[data-user-id="${userId}"]`)) {
            Swal.fire({
                title: 'تنبيه',
                text: 'حدث خطأ في العرض، لكن قد تكون الإضافة نجحت. يمكنك تحديث الصفحة للتأكد.',
                icon: 'warning',
                showConfirmButton: true,
                confirmButtonText: 'تحديث الصفحة',
                showCancelButton: true,
                cancelButtonText: 'إغلاق'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
        }
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-plus me-1"></i> إضافة';
    });
}

function handleAddTeamOwner(e) {
    e.preventDefault();

    const form = this;
    const teamSelect = form.querySelector('select[name="team_id"]');
    if (!teamSelect) return;

    const teamId = teamSelect.value;
    const teamName = teamSelect.options[teamSelect.selectedIndex]?.text;
    const serviceId = form.querySelector('input[name="service_id"]').value;

    if (!teamId || teamId === '') {
        toastr.warning('يرجى اختيار فريق أولاً', 'تنبيه');
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري إضافة مالك الفريق...';

    const formData = new FormData();
    formData.append('team_id', teamId);
    formData.append('service_id', serviceId);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json().catch(e => {
            console.error("JSON parsing error:", e);
            throw new Error("Invalid JSON response");
        });
    })
    .then(data => {
        console.log("Response data:", data);

        if (data.success) {
            const participantsContainer = form.closest('tr').querySelector('.d-flex.flex-wrap');

            const emptyMessage = participantsContainer.querySelector('.text-muted');
            if (emptyMessage) {
                participantsContainer.innerHTML = '';
            }

            const projectId = document.querySelector('meta[name="project-id"]')?.content ||
                              document.getElementById('project-container')?.dataset.projectId ||
                              form.action.match(/\/projects\/(\d+)/)?.[1];

            const serviceName = form.closest('tr').querySelector('.badge.bg-info')?.textContent;

            const newBadge = document.createElement('div');
            newBadge.className = 'badge bg-success mb-1 p-2';
            newBadge.innerHTML = `
                <i class="fas fa-user-crown me-1"></i>
                ${data.owner_name} (مالك الفريق)
                <span class="remove-participant-btn ms-2"
                    data-user-id="${data.owner_id}"
                    data-service-id="${serviceId}"
                    data-project-id="${projectId}"
                    data-user-name="${data.owner_name}"
                    data-service-name="${serviceName}">
                    <i class="fas fa-times" style="cursor: pointer;"></i>
                </span>
            `;

            newBadge.style.opacity = '0';
            participantsContainer.appendChild(newBadge);
            setTimeout(() => {
                newBadge.style.transition = 'opacity 0.5s';
                newBadge.style.opacity = '1';
            }, 10);

            // إزالة الفريق من القائمة المنسدلة
            teamSelect.querySelector(`option[value="${teamId}"]`)?.remove();
            teamSelect.value = '';

            Swal.fire({
                title: 'تم بنجاح!',
                html: `تم إضافة <strong>${data.owner_name}</strong> (مالك فريق ${teamName}) إلى المشروع بنجاح.`,
                icon: 'success',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });

            const newRemoveBtn = newBadge.querySelector('.remove-participant-btn');
            if (newRemoveBtn) {
                newRemoveBtn.addEventListener('click', handleParticipantRemoval);
            }
        } else {
            Swal.fire({
                title: 'خطأ',
                text: data.message || 'حدث خطأ أثناء إضافة مالك الفريق',
                icon: 'error'
            });
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire({
            title: 'خطأ',
            text: 'حدث خطأ أثناء إضافة مالك الفريق',
            icon: 'error'
        });
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-users me-1"></i> إضافة الفريق';
    });
}

function fadeOutAndRemove(element, duration = 300) {
    if (!element) return;

    element.style.transition = `opacity ${duration}ms`;
    element.style.opacity = '0';

    setTimeout(() => {
        element.remove();

        const container = element.closest('.d-flex.flex-wrap');
        if (container && container.children.length === 0) {
            container.innerHTML = '<span class="text-muted">لا يوجد مشاركون بعد</span>';
        }
    }, duration);
}

// ✅ دالة تحديث الموعد النهائي لمستخدم موجود
function updateExistingUserDeadline(userId, serviceId, projectId) {
    Swal.fire({
        title: 'تحديث الموعد النهائي',
        html: `
            <div class="text-start">
                <div class="form-group">
                    <label for="updateDeadline" class="form-label">الموعد النهائي الجديد:</label>
                    <input type="datetime-local" id="updateDeadline" class="form-control"
                           min="${new Date().toISOString().slice(0, 16)}">
                    <div class="form-text">اختر التاريخ والوقت المطلوب للانتهاء من المهام</div>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="removeDeadline">
                    <label class="form-check-label" for="removeDeadline">
                        حذف الموعد النهائي
                    </label>
                </div>
            </div>
        `,
        width: '600px',
        customClass: {
            container: 'swal-wide'
        },
        showCancelButton: true,
        confirmButtonText: 'تحديث',
        cancelButtonText: 'إلغاء',
        preConfirm: () => {
            const deadline = document.getElementById('updateDeadline').value;
            const removeDeadline = document.getElementById('removeDeadline').checked;

            if (removeDeadline) {
                return null; // حذف الموعد النهائي
            }

            if (!deadline) {
                Swal.showValidationMessage('يرجى تحديد موعد نهائي أو اختيار "حذف الموعد النهائي"');
                return false;
            }

            return deadline;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // إرسال طلب تحديث الموعد النهائي
            $.ajax({
                url: `/projects/${projectId}/update-user-deadline`,
                type: 'POST',
                data: {
                    user_id: userId,
                    service_id: serviceId,
                    deadline: result.value,
                    _token: document.querySelector('meta[name="csrf-token"]').content
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, 'تم التحديث ✅');

                        // تحديث الواجهة إذا لزم الأمر
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message, 'خطأ في التحديث ❌');
                    }
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'حدث خطأ أثناء تحديث الموعد النهائي';
                    toastr.error(errorMessage, 'خطأ في التحديث ❌');
                }
            });
        }
    });

    // معالجة checkbox "حذف الموعد النهائي"
    setTimeout(() => {
        const removeDeadlineCheckbox = document.getElementById('removeDeadline');
        const deadlineInput = document.getElementById('updateDeadline');

        if (removeDeadlineCheckbox && deadlineInput) {
            removeDeadlineCheckbox.addEventListener('change', function() {
                deadlineInput.disabled = this.checked;
                if (this.checked) {
                    deadlineInput.value = '';
                }
            });
        }
    }, 200);
}

// ✅ إضافة أزرار تحديث الموعد النهائي للمستخدمين الموجودين
function addDeadlineButtonsToExistingUsers() {
    // البحث في كلا النوعين من المشاركين
    const participantElements = [
        ...document.querySelectorAll('.badge.bg-success, .badge.bg-primary'),
        ...document.querySelectorAll('.participant-card')
    ];

    participantElements.forEach(element => {
        const removeBtn = element.querySelector('.remove-participant-btn, .remove-participant-btn-badge');
        if (removeBtn && !element.querySelector('.deadline-update-btn')) {
            const userId = removeBtn.dataset.userId;
            const serviceId = removeBtn.dataset.serviceId;
            const projectId = removeBtn.dataset.projectId;

            if (userId && serviceId && projectId) {
                // إضافة زر تحديث الموعد النهائي
                const deadlineBtn = document.createElement('button');
                deadlineBtn.className = 'btn btn-xs btn-outline-warning ms-1 p-0 deadline-update-btn';
                deadlineBtn.innerHTML = '<i class="fas fa-clock" style="font-size: 10px;"></i>';
                deadlineBtn.title = 'تحديث الموعد النهائي';
                deadlineBtn.style.cssText = 'font-size: 10px; padding: 1px 3px !important; border-radius: 3px;';
                deadlineBtn.onclick = () => updateExistingUserDeadline(userId, serviceId, projectId);

                // إدراج الزر قبل زر الحذف
                removeBtn.parentNode.insertBefore(deadlineBtn, removeBtn);
            }
        }
    });
}

// ✅ تشغيل عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // إضافة أزرار الموعد النهائي للمستخدمين الموجودين
    addDeadlineButtonsToExistingUsers();

    // إضافة مراقب للتغييرات في DOM لإضافة الأزرار للمستخدمين الجدد
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                addDeadlineButtonsToExistingUsers();
            }
        });
    });

    // مراقبة التغييرات في container المشاركين
    const participantsContainer = document.querySelector('.card-body');
    if (participantsContainer) {
        observer.observe(participantsContainer, {
            childList: true,
            subtree: true
        });
    }
});

// ✅ عرض sidebar اختيار مهام القوالب (بدلاً من modal)
function showTemplateTasksModal(userId, userName, serviceId, serviceName, projectId) {
    // استخدام السايد بار بدلاً من المودال
    showTemplateTasksSidebar(userId, userName, serviceId, serviceName, projectId);
}

// ✅ عرض sidebar اختيار مهام القوالب
function showTemplateTasksSidebar(userId, userName, serviceId, serviceName, projectId) {
    // تحديث معلومات المستخدم والخدمة في Sidebar
    document.getElementById('sidebarSelectedUserName').textContent = userName;
    document.getElementById('sidebarSelectedServiceName').textContent = serviceName;
    document.getElementById('sidebarSelectedUserName2').textContent = userName;
    document.getElementById('sidebarSelectedServiceName2').textContent = serviceName;

    // حفظ البيانات في Sidebar
    const sidebar = document.getElementById('templateTasksSidebar');
    sidebar.dataset.userId = userId;
    sidebar.dataset.serviceId = serviceId;
    sidebar.dataset.projectId = projectId;

    // فتح السايد بار
    openTemplateTasksSidebar();

    // تحميل المهام المتاحة
    loadAvailableTemplateTasksForSidebar(projectId, serviceId, userId);
}

// ✅ فتح سايد بار مهام القوالب
function openTemplateTasksSidebar() {
    const sidebar = document.getElementById('templateTasksSidebar');
    const overlay = document.getElementById('templateTasksSidebarOverlay');

    if (sidebar && overlay) {
        sidebar.style.right = '0px';
        overlay.style.visibility = 'visible';
        overlay.style.opacity = '1';

        // منع scroll للـ body
        document.body.style.overflow = 'hidden';
    }
}

// ✅ إغلاق سايد بار مهام القوالب
function closeTemplateTasksSidebar() {
    const sidebar = document.getElementById('templateTasksSidebar');
    const overlay = document.getElementById('templateTasksSidebarOverlay');

    if (sidebar && overlay) {
        sidebar.style.right = '-700px';
        overlay.style.visibility = 'hidden';
        overlay.style.opacity = '0';

        // إعادة تفعيل scroll للـ body
        document.body.style.overflow = '';

        // إعادة تعيين المحتوى
        setTimeout(() => {
            const content = document.getElementById('templateTasksSidebarContent');
            if (content) {
                content.innerHTML = `
                    <div class="d-flex justify-content-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                `;
            }

            // إعادة تعيين checkbox "select all"
            const selectAllCheckbox = document.getElementById('sidebarSelectAllTasks');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }

            // إعادة تعيين زر الإضافة
            const assignBtn = document.getElementById('sidebarAssignSelectedTasksBtn');
            const countSpan = document.getElementById('sidebarSelectedTasksCount');
            if (assignBtn && countSpan) {
                assignBtn.disabled = true;
                countSpan.textContent = '0';
            }
        }, 300);
    }
}

// ✅ تحميل مهام القوالب للسايد بار
async function loadAvailableTemplateTasksForSidebar(projectId, serviceId, userId) {
    const container = document.getElementById('templateTasksSidebarContent');

    try {
        // عرض loader
        container.innerHTML = `
            <div class="d-flex justify-content-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
            </div>
        `;

        // التأكد من استخدام المعرف الصحيح للمشروع (مُشفر أو رقمي حسب السياق)
        const finalProjectId = projectId;

        const response = await fetch(`/projects/${finalProjectId}/available-template-tasks?service_id=${serviceId}&user_id=${userId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.template_tasks && data.template_tasks.length > 0) {
            // إضافة رسالة توضيحية أن المهام مفلترة حسب الدور
            const infoAlert = `
                <div class="alert alert-success mb-3">
                    <i class="fas fa-filter me-2"></i>
                    <strong>تم العثور على ${data.template_tasks.length} مهمة متاحة</strong><br>
                    <small>المهام المعروضة متاحة للمستخدم حسب دوره أو المهام العامة</small>
                </div>
            `;

            displayTemplateTasksInSidebar(data.template_tasks, infoAlert);
        } else {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-user-times fa-3x text-warning mb-3"></i>
                    <h5 class="text-warning">لا توجد مهام متاحة لهذا المستخدم</h5>
                    <p class="text-muted">لا توجد مهام قوالب متاحة لدور هذا المستخدم في هذه الخدمة.<br>
                    يمكنك التحقق من أدوار المستخدم أو إضافة مهام عامة للخدمة.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading template tasks:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                حدث خطأ أثناء تحميل مهام القوالب. يرجى المحاولة مرة أخرى.
            </div>
        `;
    }
}

async function loadAvailableTemplateTasks(projectId, serviceId, userId) {
    const container = document.getElementById('templateTasksContainer');

    try {
        // عرض loader
        container.innerHTML = `
            <div class="d-flex justify-content-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
            </div>
        `;

        // التأكد من استخدام المعرف الصحيح للمشروع (مُشفر أو رقمي حسب السياق)
        const finalProjectId = projectId;

        const response = await fetch(`/projects/${finalProjectId}/available-template-tasks?service_id=${serviceId}&user_id=${userId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success && data.template_tasks && data.template_tasks.length > 0) {
            // إضافة رسالة توضيحية أن المهام مفلترة حسب الدور
            const infoAlert = `
                <div class="alert alert-success mb-3">
                    <i class="fas fa-filter me-2"></i>
                    <strong>تم العثور على ${data.template_tasks.length} مهمة متاحة</strong><br>
                    <small>المهام المعروضة متاحة للمستخدم حسب دوره أو المهام العامة</small>
                </div>
            `;

            displayTemplateTasks(data.template_tasks, infoAlert);
        } else {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-user-times fa-3x text-warning mb-3"></i>
                    <h5 class="text-warning">لا توجد مهام متاحة لهذا المستخدم</h5>
                    <p class="text-muted">لا توجد مهام قوالب متاحة لدور هذا المستخدم في هذه الخدمة.<br>
                    يمكنك التحقق من أدوار المستخدم أو إضافة مهام عامة للخدمة.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading template tasks:', error);
        container.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                حدث خطأ أثناء تحميل مهام القوالب. يرجى المحاولة مرة أخرى.
            </div>
        `;
    }
}

// ✅ عرض مهام القوالب في السايد بار
function displayTemplateTasksInSidebar(tasks, infoAlert = '') {
    const container = document.getElementById('templateTasksSidebarContent');

    let html = infoAlert + '<div class="row">';

    tasks.forEach(task => {
        const estimatedTime = `${task.estimated_hours}س ${task.estimated_minutes}د`;
        const hasAssignedUsers = task.assigned_users && task.assigned_users.length > 0;

        const roleDisplay = task.is_role_specific ?
            `<div class="d-flex align-items-center mb-2">
                <i class="fas fa-user-tag me-1 text-primary"></i>
                <span class="badge bg-primary">${task.role_display}</span>
            </div>` : '';

        html += `
            <div class="col-md-6 mb-3">
                <div class="card h-100 ${hasAssignedUsers ? 'border-info' : ''} ${task.is_role_specific ? 'border-primary' : ''}">
                    ${hasAssignedUsers ? '<div class="card-header bg-info text-white py-1"><small><i class="fas fa-users me-1"></i>مخصصة لموظفين</small></div>' : ''}
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input sidebar-task-checkbox" type="checkbox"
                                   value="${task.id}" id="sidebar_task_${task.id}">
                            <label class="form-check-label w-100" for="sidebar_task_${task.id}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">${task.name}</h6>
                                        <small class="text-muted">قالب: ${task.template_name}</small>
                                        ${roleDisplay}
                                    </div>
                                    <span class="badge bg-secondary">${estimatedTime}</span>
                                </div>
                                ${task.description ? `<p class="text-muted small mt-2 mb-0">${task.description}</p>` : ''}

                                ${hasAssignedUsers ? `
                                    <div class="mt-2 p-2 bg-light rounded border">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-info-circle text-info me-1"></i>
                                            <small class="fw-bold text-info">مخصصة للموظفين:</small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1">
                                            ${task.assigned_users.map(user => {
                                                const statusColor = user.status === 'completed' ? 'success' :
                                                                  user.status === 'in_progress' ? 'primary' :
                                                                  user.status === 'paused' ? 'warning' : 'secondary';
                                                return `
                                                    <span class="badge bg-${statusColor} me-1" title="${user.email}">
                                                        <i class="fas fa-user me-1"></i>${user.name}
                                                        ${user.status !== 'new' ? `<span class="badge bg-light text-dark ms-1">${getStatusText(user.status)}</span>` : ''}
                                                        ${user.actual_minutes > 0 ? `<span class="badge bg-dark ms-1">${user.actual_minutes}د</span>` : ''}
                                                    </span>
                                                `;
                                            }).join('')}
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            يمكنك إضافة هذه المهمة لموظف آخر أيضاً
                                        </small>
                                    </div>
                                ` : ''}

                                <!-- حقل الموعد النهائي لكل مهمة -->
                                <div class="mt-3 border-top pt-2 sidebar-task-deadline-section" style="display: none;" id="sidebar_deadline_section_${task.id}">
                                    <div class="row">
                                        <div class="col-8">
                                            <label class="form-label small fw-bold text-warning">
                                                <i class="fas fa-clock me-1"></i>
                                                الموعد النهائي
                                            </label>
                                            <input type="datetime-local"
                                                   class="form-control form-control-sm sidebar-task-deadline-input"
                                                   id="sidebar_deadline_${task.id}"
                                                   min="${new Date().toISOString().slice(0, 16)}">
                                        </div>
                                        <div class="col-4 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="sidebar_no_deadline_${task.id}">
                                                <label class="form-check-label small text-muted" for="sidebar_no_deadline_${task.id}">
                                                    بدون موعد
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;

    // إضافة event listeners للسايد بار
    setupSidebarTaskCheckboxEvents();
}

// ✅ عرض مهام القوالب في Modal
function displayTemplateTasks(tasks, infoAlert = '') {
    const container = document.getElementById('templateTasksContainer');

    let html = infoAlert + '<div class="row">';

        tasks.forEach(task => {
        const estimatedTime = `${task.estimated_hours}س ${task.estimated_minutes}د`;
        const hasAssignedUsers = task.assigned_users && task.assigned_users.length > 0;

        const roleDisplay = task.is_role_specific ?
            `<div class="d-flex align-items-center mb-2">
                <i class="fas fa-user-tag me-1 text-primary"></i>
                <span class="badge bg-primary">${task.role_display}</span>
            </div>` : '';

        html += `
            <div class="col-md-6 mb-3">
                <div class="card h-100 ${hasAssignedUsers ? 'border-info' : ''} ${task.is_role_specific ? 'border-primary' : ''}">
                    ${hasAssignedUsers ? '<div class="card-header bg-info text-white py-1"><small><i class="fas fa-users me-1"></i>مخصصة لموظفين</small></div>' : ''}
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input task-checkbox" type="checkbox"
                                   value="${task.id}" id="task_${task.id}">
                            <label class="form-check-label w-100" for="task_${task.id}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">${task.name}</h6>
                                        <small class="text-muted">قالب: ${task.template_name}</small>
                                        ${roleDisplay}
                                    </div>
                                    <span class="badge bg-secondary">${estimatedTime}</span>
                                </div>
                                ${task.description ? `<p class="text-muted small mt-2 mb-0">${task.description}</p>` : ''}

                                ${hasAssignedUsers ? `
                                    <div class="mt-2 p-2 bg-light rounded border">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-info-circle text-info me-1"></i>
                                            <small class="fw-bold text-info">مخصصة للموظفين:</small>
                                        </div>
                                        <div class="d-flex flex-wrap gap-1">
                                            ${task.assigned_users.map(user => {
                                                const statusColor = user.status === 'completed' ? 'success' :
                                                                  user.status === 'in_progress' ? 'primary' :
                                                                  user.status === 'paused' ? 'warning' : 'secondary';
                                                return `
                                                    <span class="badge bg-${statusColor} me-1" title="${user.email}">
                                                        <i class="fas fa-user me-1"></i>${user.name}
                                                        ${user.status !== 'new' ? `<span class="badge bg-light text-dark ms-1">${getStatusText(user.status)}</span>` : ''}
                                                        ${user.actual_minutes > 0 ? `<span class="badge bg-dark ms-1">${user.actual_minutes}د</span>` : ''}
                                                    </span>
                                                `;
                                            }).join('')}
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-lightbulb me-1"></i>
                                            يمكنك إضافة هذه المهمة لموظف آخر أيضاً
                                        </small>
                                    </div>
                                ` : ''}

                                <!-- حقل الموعد النهائي لكل مهمة -->
                                <div class="mt-3 border-top pt-2 task-deadline-section" style="display: none;" id="deadline_section_${task.id}">
                                    <div class="row">
                                        <div class="col-8">
                                            <label class="form-label small fw-bold text-warning">
                                                <i class="fas fa-clock me-1"></i>
                                                الموعد النهائي
                                            </label>
                                            <input type="datetime-local"
                                                   class="form-control form-control-sm task-deadline-input"
                                                   id="deadline_${task.id}"
                                                   min="${new Date().toISOString().slice(0, 16)}">
                                        </div>
                                        <div class="col-4 d-flex align-items-end">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="no_deadline_${task.id}">
                                                <label class="form-check-label small text-muted" for="no_deadline_${task.id}">
                                                    بدون موعد
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;

    // إضافة event listeners
    setupTaskCheckboxEvents();
}

// ✅ ترجمة حالات المهام
function getStatusText(status) {
    const statusTexts = {
        'new': 'جديدة',
        'in_progress': 'قيد التنفيذ',
        'paused': 'متوقفة',
        'completed': 'مكتملة'
    };
    return statusTexts[status] || status;
}

// ✅ إعداد أحداث checkboxes المهام للسايد بار
function setupSidebarTaskCheckboxEvents() {
    const checkboxes = document.querySelectorAll('.sidebar-task-checkbox');
    const selectAllCheckbox = document.getElementById('sidebarSelectAllTasks');
    const assignBtn = document.getElementById('sidebarAssignSelectedTasksBtn');
    const countSpan = document.getElementById('sidebarSelectedTasksCount');

    // تحديث عداد المهام المختارة
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.sidebar-task-checkbox:checked').length;
        countSpan.textContent = selectedCount;
        assignBtn.disabled = selectedCount === 0;

        // تحديث حالة "تحديد الكل"
        selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        selectAllCheckbox.checked = selectedCount === checkboxes.length;
    }

    // أحداث checkboxes المهام الفردية
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();

            // إظهار/إخفاء حقل deadline للمهمة
            const taskId = this.value;
            const deadlineSection = document.getElementById(`sidebar_deadline_section_${taskId}`);
            const noDeadlineCheck = document.getElementById(`sidebar_no_deadline_${taskId}`);
            const deadlineInput = document.getElementById(`sidebar_deadline_${taskId}`);

            if (deadlineSection) {
                deadlineSection.style.display = this.checked ? 'block' : 'none';

                // إعادة تعيين القيم عند إلغاء التحديد
                if (!this.checked) {
                    if (deadlineInput) deadlineInput.value = '';
                    if (noDeadlineCheck) noDeadlineCheck.checked = false;
                }
            }
        });
    });

    // إضافة أحداث للـ checkboxes "بدون موعد"
    setTimeout(() => {
        checkboxes.forEach(checkbox => {
            const taskId = checkbox.value;
            const noDeadlineCheck = document.getElementById(`sidebar_no_deadline_${taskId}`);
            const deadlineInput = document.getElementById(`sidebar_deadline_${taskId}`);

            if (noDeadlineCheck && deadlineInput) {
                noDeadlineCheck.addEventListener('change', function() {
                    deadlineInput.disabled = this.checked;
                    if (this.checked) {
                        deadlineInput.value = '';
                    }
                });
            }
        });
    }, 100);

    // حدث "تحديد الكل"
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        checkboxes.forEach(checkbox => {
            checkbox.checked = isChecked;

            // إظهار/إخفاء حقول deadline
            const taskId = checkbox.value;
            const deadlineSection = document.getElementById(`sidebar_deadline_section_${taskId}`);
            const noDeadlineCheck = document.getElementById(`sidebar_no_deadline_${taskId}`);
            const deadlineInput = document.getElementById(`sidebar_deadline_${taskId}`);

            if (deadlineSection) {
                deadlineSection.style.display = isChecked ? 'block' : 'none';

                // إعادة تعيين القيم عند إلغاء التحديد
                if (!isChecked) {
                    if (deadlineInput) deadlineInput.value = '';
                    if (noDeadlineCheck) noDeadlineCheck.checked = false;
                }
            }
        });
        updateSelectedCount();
    });

    // حدث زر الإضافة
    assignBtn.addEventListener('click', assignSelectedTasksFromSidebar);

    // تحديث أولي
    updateSelectedCount();
}

// ✅ إعداد أحداث checkboxes المهام
function setupTaskCheckboxEvents() {
    const checkboxes = document.querySelectorAll('.task-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllTasks');
    const assignBtn = document.getElementById('assignSelectedTasksBtn');
    const countSpan = document.getElementById('selectedTasksCount');

    // تحديث عداد المهام المختارة
    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.task-checkbox:checked').length;
        countSpan.textContent = selectedCount;
        assignBtn.disabled = selectedCount === 0;

        // تحديث حالة "تحديد الكل"
        selectAllCheckbox.indeterminate = selectedCount > 0 && selectedCount < checkboxes.length;
        selectAllCheckbox.checked = selectedCount === checkboxes.length;
    }

    // أحداث checkboxes المهام الفردية
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();

            // إظهار/إخفاء حقل deadline للمهمة
            const taskId = this.value;
            const deadlineSection = document.getElementById(`deadline_section_${taskId}`);
            const noDeadlineCheck = document.getElementById(`no_deadline_${taskId}`);
            const deadlineInput = document.getElementById(`deadline_${taskId}`);

            if (deadlineSection) {
                deadlineSection.style.display = this.checked ? 'block' : 'none';

                // إعادة تعيين القيم عند إلغاء التحديد
                if (!this.checked) {
                    if (deadlineInput) deadlineInput.value = '';
                    if (noDeadlineCheck) noDeadlineCheck.checked = false;
                }
            }
        });
    });

    // إضافة أحداث للـ checkboxes "بدون موعد"
    setTimeout(() => {
        checkboxes.forEach(checkbox => {
            const taskId = checkbox.value;
            const noDeadlineCheck = document.getElementById(`no_deadline_${taskId}`);
            const deadlineInput = document.getElementById(`deadline_${taskId}`);

            if (noDeadlineCheck && deadlineInput) {
                noDeadlineCheck.addEventListener('change', function() {
                    deadlineInput.disabled = this.checked;
                    if (this.checked) {
                        deadlineInput.value = '';
                    }
                });
            }
        });
    }, 100);

    // حدث "تحديد الكل"
    selectAllCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        checkboxes.forEach(checkbox => {
            checkbox.checked = isChecked;

            // إظهار/إخفاء حقول deadline
            const taskId = checkbox.value;
            const deadlineSection = document.getElementById(`deadline_section_${taskId}`);
            const noDeadlineCheck = document.getElementById(`no_deadline_${taskId}`);
            const deadlineInput = document.getElementById(`deadline_${taskId}`);

            if (deadlineSection) {
                deadlineSection.style.display = isChecked ? 'block' : 'none';

                // إعادة تعيين القيم عند إلغاء التحديد
                if (!isChecked) {
                    if (deadlineInput) deadlineInput.value = '';
                    if (noDeadlineCheck) noDeadlineCheck.checked = false;
                }
            }
        });
        updateSelectedCount();
    });

    // حدث زر الإضافة
    assignBtn.addEventListener('click', assignSelectedTasks);

    // تحديث أولي
    updateSelectedCount();
}

// ✅ إضافة المهام المختارة من السايد بار
async function assignSelectedTasksFromSidebar() {
    const sidebar = document.getElementById('templateTasksSidebar');
    const userId = sidebar.dataset.userId;
    const serviceId = sidebar.dataset.serviceId;
    const projectId = sidebar.dataset.projectId;

    const selectedTaskIds = Array.from(document.querySelectorAll('.sidebar-task-checkbox:checked'))
        .map(checkbox => checkbox.value);

    if (selectedTaskIds.length === 0) {
        toastr.warning('يرجى اختيار مهمة واحدة على الأقل', 'تنبيه');
        return;
    }

    // الحصول على deadlines لكل مهمة
    const taskDeadlines = {};
    selectedTaskIds.forEach(taskId => {
        const deadlineInput = document.getElementById(`sidebar_deadline_${taskId}`);
        const noDeadlineCheck = document.getElementById(`sidebar_no_deadline_${taskId}`);

        if (!noDeadlineCheck.checked && deadlineInput.value) {
            taskDeadlines[taskId] = deadlineInput.value;
        } else {
            taskDeadlines[taskId] = null;
        }
    });

    const assignBtn = document.getElementById('sidebarAssignSelectedTasksBtn');
    const originalBtnText = assignBtn.innerHTML;

    try {
        // تعطيل الزر وعرض loader
        assignBtn.disabled = true;
        assignBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الإضافة...';

        // التأكد من استخدام المعرف الصحيح للمشروع
        const finalProjectId = projectId;

        const response = await fetch(`/projects/${finalProjectId}/assign-selected-template-tasks`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                user_id: userId,
                service_id: serviceId,
                selected_task_ids: selectedTaskIds,
                task_deadlines: taskDeadlines
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            // إغلاق السايد بار
            closeTemplateTasksSidebar();

            // عرض رسالة نجاح
            Swal.fire({
                title: 'تم بنجاح!',
                html: `تم إضافة <strong>${data.assigned_tasks.length}</strong> مهمة من القوالب بنجاح.`,
                icon: 'success',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });

            // تحديث الصفحة لعرض المهام الجديدة
            setTimeout(() => {
                window.location.reload();
            }, 1000);

        } else {
            throw new Error(data.message || 'حدث خطأ أثناء إضافة المهام');
        }

    } catch (error) {
        console.error('Error assigning tasks:', error);
        toastr.error(error.message || 'حدث خطأ أثناء إضافة المهام', 'خطأ');
    } finally {
        // إعادة تفعيل الزر
        assignBtn.disabled = false;
        assignBtn.innerHTML = originalBtnText;
    }
}

// ✅ إضافة المهام المختارة
async function assignSelectedTasks() {
    const modal = document.getElementById('templateTasksModal');
    const userId = modal.dataset.userId;
    const serviceId = modal.dataset.serviceId;
    const projectId = modal.dataset.projectId;

    const selectedTaskIds = Array.from(document.querySelectorAll('.task-checkbox:checked'))
        .map(checkbox => checkbox.value);

    if (selectedTaskIds.length === 0) {
        toastr.warning('يرجى اختيار مهمة واحدة على الأقل', 'تنبيه');
        return;
    }

        // الحصول على deadlines لكل مهمة
    const taskDeadlines = {};
    selectedTaskIds.forEach(taskId => {
        const deadlineInput = document.getElementById(`deadline_${taskId}`);
        const noDeadlineCheck = document.getElementById(`no_deadline_${taskId}`);

        if (!noDeadlineCheck.checked && deadlineInput.value) {
            taskDeadlines[taskId] = deadlineInput.value;
        } else {
            taskDeadlines[taskId] = null;
        }
    });

    const assignBtn = document.getElementById('assignSelectedTasksBtn');
    const originalBtnText = assignBtn.innerHTML;

    try {
        // تعطيل الزر وعرض loader
        assignBtn.disabled = true;
        assignBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الإضافة...';

        // التأكد من استخدام المعرف الصحيح للمشروع
        const finalProjectId = projectId;

        const response = await fetch(`/projects/${finalProjectId}/assign-selected-template-tasks`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                user_id: userId,
                service_id: serviceId,
                selected_task_ids: selectedTaskIds,
                task_deadlines: taskDeadlines
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            // إغلاق Modal
            const modalInstance = bootstrap.Modal.getInstance(modal);
            modalInstance.hide();

            // عرض رسالة نجاح
            Swal.fire({
                title: 'تم بنجاح!',
                html: `تم إضافة <strong>${data.assigned_tasks.length}</strong> مهمة من القوالب بنجاح.`,
                icon: 'success',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });

            // تحديث الصفحة لعرض المهام الجديدة
            setTimeout(() => {
                window.location.reload();
            }, 1000);

        } else {
            throw new Error(data.message || 'حدث خطأ أثناء إضافة المهام');
        }

    } catch (error) {
        console.error('Error assigning tasks:', error);
        toastr.error(error.message || 'حدث خطأ أثناء إضافة المهام', 'خطأ');
    } finally {
        // إعادة تفعيل الزر
        assignBtn.disabled = false;
        assignBtn.innerHTML = originalBtnText;
    }
}
