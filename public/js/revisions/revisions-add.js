let editingRevisionId = null;

function showAddRevisionModal() {
    editingRevisionId = null;
    window.selectedReviewers = [];

    const sidebar = document.getElementById("addRevisionSidebar");
    const overlay = document.getElementById("addRevisionOverlay");

    if (!sidebar || !overlay) {
        console.error("Sidebar elements not found");
        return;
    }

    document.getElementById("addRevisionForm").reset();
    document.getElementById("newRevisionType").value = "";
    document.getElementById("projectSelectContainer").classList.add("d-none");
    document.getElementById("attachmentTypeOptions").style.display = "none";
    document.getElementById("newFileContainer").style.display = "none";
    document.getElementById("newLinkContainer").style.display = "none";

    const reviewersList = document.getElementById("reviewersList");
    const noReviewersMsg = document.getElementById("noReviewersMsg");
    if (reviewersList && noReviewersMsg) {
        reviewersList.innerHTML = "";
        reviewersList.appendChild(noReviewersMsg);
        noReviewersMsg.style.display = "block";
    }

    overlay.style.visibility = "visible";
    overlay.style.opacity = "1";

    sidebar.style.right = "0";

    document.body.style.overflow = "hidden";
}

function closeAddRevisionSidebar() {
    const sidebar = document.getElementById("addRevisionSidebar");
    const overlay = document.getElementById("addRevisionOverlay");

    if (!sidebar || !overlay) return;

    editingRevisionId = null;

    window.selectedReviewers = [];

    const sidebarTitle = sidebar.querySelector("h5");
    if (sidebarTitle) {
        sidebarTitle.innerHTML =
            '<i class="fas fa-plus me-2"></i>إضافة تعديل جديد';
    }

    // إعادة تعيين زر الحفظ
    resetSaveButton();

    const saveButton = sidebar.querySelector(
        'button[onclick*="saveNewRevision"]'
    );
    if (saveButton) {
        saveButton.innerHTML =
            '<i class="fas fa-save me-1"></i><span id="saveRevisionBtnText">حفظ التعديل</span><span id="saveRevisionBtnSpinner" class="spinner-border spinner-border-sm ms-1 d-none" role="status"><span class="visually-hidden">جاري الحفظ...</span></span>';
    }

    sidebar.style.right = "-600px";

    overlay.style.opacity = "0";
    setTimeout(() => {
        overlay.style.visibility = "hidden";
    }, 300);

    document.body.style.overflow = "auto";
}

function toggleRevisionTypeOptions() {
    const type = document.getElementById("newRevisionType").value;
    const projectContainer = document.getElementById("projectSelectContainer");
    const responsibilitySection = document.getElementById(
        "responsibilitySection"
    );
    const attachmentTypeOptions = document.getElementById(
        "attachmentTypeOptions"
    );
    const fileContainer = document.getElementById("newFileContainer");
    const linkContainer = document.getElementById("newLinkContainer");

    if (type === "project") {
        projectContainer.classList.remove("d-none");
        document.getElementById("newRevisionProjectId").required = true;

        attachmentTypeOptions.style.display = "none";
        fileContainer.style.display = "none";
        linkContainer.style.display = "block";

        document.getElementById("newRevisionAttachment").value = "";
    } else {
        projectContainer.classList.add("d-none");
        responsibilitySection.classList.add("d-none");
        attachmentTypeOptions.style.display = "none";
        fileContainer.style.display = "none";
        linkContainer.style.display = "none";
    }
}

async function loadProjectParticipantsForRevision() {
    const projectId = document.getElementById("newRevisionProjectId").value;
    const responsibilitySection = document.getElementById(
        "responsibilitySection"
    );

    if (!projectId) {
        responsibilitySection.classList.add("d-none");
        return;
    }

    try {
        responsibilitySection.classList.remove("d-none");

        const projectResponse = await fetch(
            `/projects/${projectId}/participants`,
            {
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    Accept: "application/json",
                },
            }
        );

        const projectResult = await projectResponse.json();

        const allUsersResponse = await fetch("/users/all", {
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                Accept: "application/json",
            },
        });

        const allUsersResult = await allUsersResponse.json();

        if (projectResult.success && allUsersResult.success) {
            const projectParticipantIds = projectResult.participants.map(
                (p) => p.id
            );
            const allUsers = allUsersResult.users || [];

            const usersInProject = allUsers.filter((user) =>
                projectParticipantIds.includes(user.id)
            );
            const usersNotInProject = allUsers.filter(
                (user) => !projectParticipantIds.includes(user.id)
            );
            const sortedUsers = [...usersInProject, ...usersNotInProject];

            const executorDatalist =
                document.getElementById("executorUsersList");
            executorDatalist.innerHTML = "";

            sortedUsers.forEach((user) => {
                const option = document.createElement("option");
                const isInProject = projectParticipantIds.includes(user.id);
                option.value =
                    user.name + (isInProject ? " ✅ من المشروع" : "");
                option.setAttribute("data-user-id", user.id);
                option.setAttribute("data-user-name", user.name);
                option.setAttribute("data-in-project", isInProject);
                executorDatalist.appendChild(option);
            });

            window.allUsersForRevision = allUsers;
            window.projectParticipantIds = projectParticipantIds;

            await loadResponsibleUsersForRevision(projectId);
            await loadReviewerUsersForRevision(
                projectId,
                allUsers,
                projectParticipantIds
            );
        }
    } catch (error) {
        console.error("Error loading project participants:", error);
    }
}

function handleExecutorSelection() {
    const searchInput = document.getElementById("newExecutorUserSearch");
    const hiddenInput = document.getElementById("newExecutorUserId");
    const deadlineContainer = document.getElementById(
        "executorDeadlineContainer"
    );
    const deadlineInput = document.getElementById("newExecutorDeadline");

    if (!searchInput || !hiddenInput) return;

    const inputValue = searchInput.value;
    // إزالة " ✅ من المشروع" و "- القسم" من النص للبحث
    const searchName = inputValue
        .replace(" ✅ من المشروع", "")
        .replace(/\s*-\s*[^-]+$/, "")
        .trim();

    if (window.allUsersForRevision) {
        const selectedUser = window.allUsersForRevision.find(
            (user) => user.name === searchName
        );

        if (selectedUser) {
            hiddenInput.value = selectedUser.id;

            if (deadlineContainer) {
                deadlineContainer.style.display = "block";

                if (deadlineInput) {
                    updateExecutorDeadlineMin();
                }
            }
        } else {
            hiddenInput.value = "";

            if (deadlineContainer) {
                deadlineContainer.style.display = "none";
            }
            if (deadlineInput) {
                deadlineInput.value = "";
            }
        }
    }
}

function handleResponsibleSelection() {
    const searchInput = document.getElementById("newResponsibleUserSearch");
    const hiddenInput = document.getElementById("newResponsibleUserId");

    if (!searchInput || !hiddenInput) return;

    const inputValue = searchInput.value;
    // إزالة " ✅ من المشروع" و "- القسم" من النص للبحث
    const searchName = inputValue
        .replace(" ✅ من المشروع", "")
        .replace(/\s*-\s*[^-]+$/, "")
        .trim();

    if (window.allUsersForRevision) {
        const selectedUser = window.allUsersForRevision.find(
            (user) => user.name === searchName
        );

        if (selectedUser) {
            hiddenInput.value = selectedUser.id;
        } else {
            hiddenInput.value = "";
        }
    }
}

function handleReviewerSelection() {}

window.selectedReviewers = [];

function addReviewerRow() {
    const reviewersList = document.getElementById("reviewersList");
    const noReviewersMsg = document.getElementById("noReviewersMsg");

    if (noReviewersMsg) {
        noReviewersMsg.style.display = "none";
    }

    const order = window.selectedReviewers.length + 1;
    const reviewerId = `reviewer_${Date.now()}`;

    const reviewerRow = document.createElement("div");
    reviewerRow.className = "reviewer-row mb-3 p-3 bg-white border rounded";
    reviewerRow.id = reviewerId;
    reviewerRow.innerHTML = `
        <div class="mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary" style="min-width: 30px;">${order}</span>
                <input type="text"
                       class="form-control form-control-sm reviewer-search"
                       list="reviewerUsersList"
                       placeholder="ابحث عن المراجع..."
                       data-reviewer-id="${reviewerId}"
                       oninput="handleReviewerInputChange('${reviewerId}')">
                <input type="hidden" class="reviewer-user-id" data-reviewer-id="${reviewerId}">
                <button type="button" class="btn btn-sm btn-danger" onclick="removeReviewer('${reviewerId}')">
                    <i class="fas fa-trash"></i>
                </button>
                ${
                    order > 1
                        ? `<button type="button" class="btn btn-sm btn-secondary" onclick="moveReviewerUp('${reviewerId}')">
                    <i class="fas fa-arrow-up"></i>
                </button>`
                        : ""
                }
                ${
                    window.selectedReviewers.length > 0
                        ? `<button type="button" class="btn btn-sm btn-secondary" onclick="moveReviewerDown('${reviewerId}')">
                    <i class="fas fa-arrow-down"></i>
                </button>`
                        : ""
                }
            </div>
        </div>
        <div class="reviewer-deadline-container" style="display: none;">
            <label class="form-label" style="font-size: 12px;">
                <i class="fas fa-clock me-1"></i>
                ديدلاين المراجع <span class="text-muted" style="font-size: 10px;">(اختياري)</span>
            </label>
            <input type="datetime-local"
                   class="form-control form-control-sm reviewer-deadline"
                   data-reviewer-id="${reviewerId}"
                   min=""
                   onchange="validateReviewerDeadlineOrder('${reviewerId}')">
            <small class="text-muted" style="font-size: 10px;">
                تاريخ ووقت الانتهاء المتوقع للمراجع (يجب أن يكون بعد المراجع السابق)
            </small>
        </div>
    `;

    reviewersList.appendChild(reviewerRow);

    window.selectedReviewers.push({
        id: reviewerId,
        userId: null,
        userName: null,
        order: order,
    });
}

function handleReviewerInputChange(reviewerId) {
    const searchInput = document.querySelector(
        `.reviewer-search[data-reviewer-id="${reviewerId}"]`
    );
    const hiddenInput = document.querySelector(
        `.reviewer-user-id[data-reviewer-id="${reviewerId}"]`
    );

    if (!searchInput || !hiddenInput) return;

    const inputValue = searchInput.value;
    // إزالة " ✅ من المشروع" و "- القسم" من النص للبحث
    const searchName = inputValue
        .replace(" ✅ من المشروع", "")
        .replace(/\s*-\s*[^-]+$/, "")
        .trim();

    if (window.allUsersForRevision) {
        const selectedUser = window.allUsersForRevision.find(
            (user) => user.name === searchName
        );

        if (selectedUser) {
            const executorUserId =
                document.getElementById("newExecutorUserId")?.value;
            if (executorUserId && selectedUser.id == executorUserId) {
                Swal.fire(
                    "تحذير",
                    "لا يمكن أن يكون المنفذ مراجعاً. اختر شخصاً آخر.",
                    "warning"
                );
                searchInput.value = "";
                hiddenInput.value = "";
                const reviewer = window.selectedReviewers.find(
                    (r) => r.id === reviewerId
                );
                if (reviewer) {
                    reviewer.userId = null;
                    reviewer.userName = null;
                }
                return;
            }

            const reviewerRow = document.getElementById(reviewerId);
            if (reviewerRow) {
                const deadlineContainer = reviewerRow.querySelector(
                    ".reviewer-deadline-container"
                );
                const deadlineInput =
                    reviewerRow.querySelector(".reviewer-deadline");
                if (deadlineContainer) {
                    deadlineContainer.style.display = "block";
                    if (deadlineInput) {
                        updateReviewerDeadlineMin(reviewerId);
                    }
                }
            }

            const alreadyAdded = window.selectedReviewers.some(
                (r) => r.id !== reviewerId && r.userId == selectedUser.id
            );
            if (alreadyAdded) {
                Swal.fire(
                    "تحذير",
                    "هذا المراجع مضاف بالفعل. اختر شخصاً آخر.",
                    "warning"
                );
                searchInput.value = "";
                hiddenInput.value = "";
                const reviewer = window.selectedReviewers.find(
                    (r) => r.id === reviewerId
                );
                if (reviewer) {
                    reviewer.userId = null;
                    reviewer.userName = null;
                }
                return;
            }

            hiddenInput.value = selectedUser.id;

            const reviewer = window.selectedReviewers.find(
                (r) => r.id === reviewerId
            );
            if (reviewer) {
                reviewer.userId = selectedUser.id;
                reviewer.userName = selectedUser.name;
            }
        } else {
            hiddenInput.value = "";
            const reviewer = window.selectedReviewers.find(
                (r) => r.id === reviewerId
            );
            if (reviewer) {
                reviewer.userId = null;
                reviewer.userName = null;
            }
        }
    }
}

function removeReviewer(reviewerId) {
    const reviewerRow = document.getElementById(reviewerId);
    if (reviewerRow) {
        const deadlineContainer = reviewerRow.querySelector(
            ".reviewer-deadline-container"
        );
        const deadlineInput = reviewerRow.querySelector(".reviewer-deadline");
        if (deadlineContainer) {
            deadlineContainer.style.display = "none";
        }
        if (deadlineInput) {
            deadlineInput.value = "";
        }
        reviewerRow.remove();
    }

    window.selectedReviewers = window.selectedReviewers.filter(
        (r) => r.id !== reviewerId
    );

    reorderReviewers();

    if (window.selectedReviewers.length === 0) {
        const noReviewersMsg = document.getElementById("noReviewersMsg");
        if (noReviewersMsg) {
            noReviewersMsg.style.display = "block";
        }
    }
}

function moveReviewerUp(reviewerId) {
    const index = window.selectedReviewers.findIndex(
        (r) => r.id === reviewerId
    );
    if (index > 0) {
        [window.selectedReviewers[index], window.selectedReviewers[index - 1]] =
            [
                window.selectedReviewers[index - 1],
                window.selectedReviewers[index],
            ];

        const reviewersList = document.getElementById("reviewersList");
        const currentRow = document.getElementById(reviewerId);
        const previousRow = currentRow.previousElementSibling;

        if (previousRow && previousRow.className.includes("reviewer-row")) {
            reviewersList.insertBefore(currentRow, previousRow);
        }

        reorderReviewers();
    }
}

function moveReviewerDown(reviewerId) {
    const index = window.selectedReviewers.findIndex(
        (r) => r.id === reviewerId
    );
    if (index < window.selectedReviewers.length - 1) {
        [window.selectedReviewers[index], window.selectedReviewers[index + 1]] =
            [
                window.selectedReviewers[index + 1],
                window.selectedReviewers[index],
            ];

        const reviewersList = document.getElementById("reviewersList");
        const currentRow = document.getElementById(reviewerId);
        const nextRow = currentRow.nextElementSibling;

        if (nextRow && nextRow.className.includes("reviewer-row")) {
            reviewersList.insertBefore(nextRow, currentRow);
        }

        reorderReviewers();
    }
}

function reorderReviewers() {
    const reviewerRows = document.querySelectorAll(".reviewer-row");
    reviewerRows.forEach((row, index) => {
        const badge = row.querySelector(".badge");
        if (badge) {
            badge.textContent = index + 1;
        }

        const reviewerId = row.id;
        const reviewer = window.selectedReviewers.find(
            (r) => r.id === reviewerId
        );
        if (reviewer) {
            reviewer.order = index + 1;
        }
    });
}

async function loadResponsibleUsersForRevision(projectId) {
    try {
        const responsibleDatalist = document.getElementById(
            "responsibleUsersList"
        );
        responsibleDatalist.innerHTML = "";

        const allUsers = window.allUsersForRevision || [];
        const projectParticipantIds = window.projectParticipantIds || [];

        // عرض فقط الأشخاص الموجودين في المشروع
        const usersInProject = allUsers.filter((user) =>
            projectParticipantIds.includes(user.id)
        );

        usersInProject.forEach((user) => {
            const option = document.createElement("option");
            option.value = user.name + " ✅ من المشروع";
            option.setAttribute("data-user-id", user.id);
            option.setAttribute("data-user-name", user.name);
            option.setAttribute("data-in-project", true);
            responsibleDatalist.appendChild(option);
        });
    } catch (error) {
        console.error("Error loading responsible users:", error);
    }
}

async function loadReviewerUsersForRevision(
    projectId,
    allUsers,
    projectParticipantIds
) {
    try {
        const response = await fetch(
            `/task-revisions/reviewers-only?project_id=${projectId}`,
            {
                headers: {
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                    Accept: "application/json",
                },
            }
        );

        const result = await response.json();

        const reviewerDatalist = document.getElementById("reviewerUsersList");
        reviewerDatalist.innerHTML = "";

        if (result.success && result.reviewers && result.reviewers.length > 0) {
            // ✅ الترتيب: المراجعين الحاليين أولاً، ثم باقي الموظفين النشطين
            const reviewerIds = result.reviewers.map((r) => r.id);

            // 1️⃣ عرض المراجعين الحاليين أولاً
            result.reviewers.forEach((user) => {
                const option = document.createElement("option");
                const isInProject = projectParticipantIds.includes(user.id);
                const departmentText = user.department
                    ? ` - ${user.department}`
                    : "";
                option.value =
                    user.name +
                    departmentText +
                    (isInProject ? " ✅ من المشروع" : "");
                option.setAttribute("data-user-id", user.id);
                option.setAttribute("data-user-name", user.name);
                option.setAttribute("data-in-project", isInProject);
                reviewerDatalist.appendChild(option);
            });

            // 2️⃣ عرض باقي الموظفين النشطين (اللي مش من المراجعين)
            const otherUsers = allUsers.filter(
                (user) => !reviewerIds.includes(user.id)
            );
            otherUsers.forEach((user) => {
                const option = document.createElement("option");
                const isInProject = projectParticipantIds.includes(user.id);
                const departmentText = user.department
                    ? ` - ${user.department}`
                    : "";
                option.value =
                    user.name +
                    departmentText +
                    (isInProject ? " ✅ من المشروع" : "");
                option.setAttribute("data-user-id", user.id);
                option.setAttribute("data-user-name", user.name);
                option.setAttribute("data-in-project", isInProject);
                reviewerDatalist.appendChild(option);
            });
        } else {
            console.log(
                "⚠️ No results from API, showing all users as fallback"
            );

            const usersInProject = allUsers.filter((user) =>
                projectParticipantIds.includes(user.id)
            );
            const usersNotInProject = allUsers.filter(
                (user) => !projectParticipantIds.includes(user.id)
            );
            const sortedUsers = [...usersInProject, ...usersNotInProject];

            sortedUsers.forEach((user) => {
                const option = document.createElement("option");
                const isInProject = projectParticipantIds.includes(user.id);
                const departmentText = user.department
                    ? ` - ${user.department}`
                    : "";
                option.value =
                    user.name +
                    departmentText +
                    (isInProject ? " ✅ من المشروع" : "");
                option.setAttribute("data-user-id", user.id);
                option.setAttribute("data-user-name", user.name);
                option.setAttribute("data-in-project", isInProject);
                reviewerDatalist.appendChild(option);
            });
        }
    } catch (error) {
        console.error("Error loading reviewer users:", error);
        const usersInProject = allUsers.filter((user) =>
            projectParticipantIds.includes(user.id)
        );
        const usersNotInProject = allUsers.filter(
            (user) => !projectParticipantIds.includes(user.id)
        );
        const sortedUsers = [...usersInProject, ...usersNotInProject];

        const reviewerDatalist = document.getElementById("reviewerUsersList");
        reviewerDatalist.innerHTML = "";

        sortedUsers.forEach((user) => {
            const option = document.createElement("option");
            const isInProject = projectParticipantIds.includes(user.id);
            const departmentText = user.department
                ? ` - ${user.department}`
                : "";
            option.value =
                user.name +
                departmentText +
                (isInProject ? " ✅ من المشروع" : "");
            option.setAttribute("data-user-id", user.id);
            option.setAttribute("data-user-name", user.name);
            option.setAttribute("data-in-project", isInProject);
            reviewerDatalist.appendChild(option);
        });
    }
}

function toggleNewAttachmentType(type) {
    const fileContainer = document.getElementById("newFileContainer");
    const linkContainer = document.getElementById("newLinkContainer");

    if (type === "file") {
        fileContainer.style.display = "block";
        linkContainer.style.display = "none";
        document.getElementById("newRevisionAttachmentLink").value = "";
    } else {
        fileContainer.style.display = "none";
        linkContainer.style.display = "block";
        document.getElementById("newRevisionAttachment").value = "";
    }
}

// دالة مساعدة لإعادة تفعيل زر الحفظ
function resetSaveButton() {
    const saveBtn = document.getElementById("saveRevisionBtn");
    const saveBtnText = document.getElementById("saveRevisionBtnText");
    const saveBtnSpinner = document.getElementById("saveRevisionBtnSpinner");

    if (saveBtn && saveBtnText && saveBtnSpinner) {
        saveBtn.disabled = false;
        saveBtnText.textContent = editingRevisionId
            ? "حفظ التعديلات"
            : "حفظ التعديل";
        saveBtnSpinner.classList.add("d-none");
    }
}

async function saveNewRevision() {
    // ✅ تعطيل الزر وإظهار loading
    const saveBtn = document.getElementById("saveRevisionBtn");
    const saveBtnText = document.getElementById("saveRevisionBtnText");
    const saveBtnSpinner = document.getElementById("saveRevisionBtnSpinner");

    if (saveBtn && saveBtnText && saveBtnSpinner) {
        saveBtn.disabled = true;
        saveBtnText.textContent = "جاري الحفظ...";
        saveBtnSpinner.classList.remove("d-none");
    }

    const revisionType = document.getElementById("newRevisionType").value;
    const revisionSource = document.getElementById("newRevisionSource").value;
    const title = document.getElementById("newRevisionTitle").value.trim();
    const description = document
        .getElementById("newRevisionDescription")
        .value.trim();
    const notes = document.getElementById("newRevisionNotes").value.trim();

    if (!revisionType || !title || !description) {
        resetSaveButton();
        Swal.fire("خطأ", "الرجاء ملء جميع الحقول المطلوبة", "error");
        return;
    }

    const formData = new FormData();
    formData.append("revision_type", revisionType);
    formData.append("revision_source", revisionSource);
    formData.append("title", title);
    formData.append("description", description);

    if (notes) {
        formData.append("notes", notes);
    }

    const revisionDeadline = document.getElementById(
        "newRevisionDeadline"
    )?.value;
    if (revisionDeadline) {
        const revisionDeadlineDate = new Date(revisionDeadline);

        const executorDeadline = document.getElementById(
            "newExecutorDeadline"
        )?.value;
        if (executorDeadline) {
            const executorDeadlineDate = new Date(executorDeadline);
            if (revisionDeadlineDate < executorDeadlineDate) {
                resetSaveButton();
                Swal.fire(
                    "خطأ في الترتيب",
                    "ديدلاين التعديل يجب أن يكون بعد ديدلاين المنفذ",
                    "error"
                );
                return;
            }
        }

        if (window.selectedReviewers && window.selectedReviewers.length > 0) {
            for (let reviewer of window.selectedReviewers) {
                if (reviewer.userId) {
                    const reviewerRow = document.getElementById(reviewer.id);
                    if (reviewerRow) {
                        const reviewerDeadlineInput =
                            reviewerRow.querySelector(".reviewer-deadline");
                        if (
                            reviewerDeadlineInput &&
                            reviewerDeadlineInput.value
                        ) {
                            const reviewerDeadlineDate = new Date(
                                reviewerDeadlineInput.value
                            );
                            if (revisionDeadlineDate < reviewerDeadlineDate) {
                                resetSaveButton();
                                Swal.fire(
                                    "خطأ في الترتيب",
                                    `ديدلاين التعديل يجب أن يكون بعد ديدلاين المراجع ${reviewer.order}`,
                                    "error"
                                );
                                return;
                            }
                        }
                    }
                }
            }
        }

        const formattedDeadline = revisionDeadlineDate
            .toISOString()
            .slice(0, 19)
            .replace("T", " ");
        formData.append("revision_deadline", formattedDeadline);
    }

    if (revisionType === "project") {
        const projectId = document.getElementById("newRevisionProjectId").value;
        if (!projectId) {
            resetSaveButton();
            Swal.fire("خطأ", "الرجاء اختيار المشروع", "error");
            return;
        }
        formData.append("project_id", projectId);

        const responsibleUserId = document.getElementById(
            "newResponsibleUserId"
        ).value;
        const executorUserId =
            document.getElementById("newExecutorUserId").value;
        const responsibilityNotes = document
            .getElementById("newResponsibilityNotes")
            .value.trim();

        if (!responsibleUserId) {
            resetSaveButton();
            Swal.fire("خطأ", "الرجاء اختيار المسؤول", "error");
            return;
        }

        if (!executorUserId) {
            resetSaveButton();
            Swal.fire("خطأ", "الرجاء اختيار المنفذ", "error");
            return;
        }

        formData.append("responsible_user_id", responsibleUserId);
        formData.append("executor_user_id", executorUserId);

        const executorDeadline = document.getElementById(
            "newExecutorDeadline"
        )?.value;
        if (executorDeadline) {
            const executorDeadlineDate = new Date(executorDeadline);

            if (
                window.selectedReviewers &&
                window.selectedReviewers.length > 0
            ) {
                const sortedReviewers = [...window.selectedReviewers].sort(
                    (a, b) => {
                        const aOrder =
                            parseInt(
                                document.querySelector(`#${a.id} .badge`)
                                    ?.textContent || a.order
                            ) || 0;
                        const bOrder =
                            parseInt(
                                document.querySelector(`#${b.id} .badge`)
                                    ?.textContent || b.order
                            ) || 0;
                        return aOrder - bOrder;
                    }
                );

                const firstReviewer = sortedReviewers.find((r) => r.userId);
                if (firstReviewer) {
                    const firstReviewerRow = document.getElementById(
                        firstReviewer.id
                    );
                    if (firstReviewerRow) {
                        const firstReviewerDeadlineInput =
                            firstReviewerRow.querySelector(
                                ".reviewer-deadline"
                            );
                        if (
                            firstReviewerDeadlineInput &&
                            firstReviewerDeadlineInput.value
                        ) {
                            const firstReviewerDeadlineDate = new Date(
                                firstReviewerDeadlineInput.value
                            );
                            if (
                                executorDeadlineDate > firstReviewerDeadlineDate
                            ) {
                                resetSaveButton();
                                Swal.fire(
                                    "خطأ في الترتيب",
                                    "ديدلاين المنفذ يجب أن يكون قبل ديدلاين المراجع الأول",
                                    "error"
                                );
                                return;
                            }
                        }
                    }
                }
            }

            const formattedDeadline = executorDeadlineDate
                .toISOString()
                .slice(0, 19)
                .replace("T", " ");
            formData.append("executor_deadline", formattedDeadline);
        }

        if (window.selectedReviewers && window.selectedReviewers.length > 0) {
            const reviewersData = window.selectedReviewers
                .filter((r) => r.userId)
                .map((r, index) => ({
                    reviewer_id: r.userId,
                    order: index + 1,
                    status: r.status || "pending",
                    completed_at: r.completed_at || null,
                }));

            const duplicateReviewers = reviewersData.filter(
                (r) => r.reviewer_id == executorUserId
            );
            if (duplicateReviewers.length > 0) {
                resetSaveButton();
                Swal.fire(
                    "تحذير",
                    "لا يمكن أن يكون المنفذ مراجعاً في نفس التعديل. الرجاء اختيار مراجعين مختلفين.",
                    "warning"
                );
                return;
            }

            const reviewerIds = reviewersData.map((r) => r.reviewer_id);
            const uniqueReviewerIds = [...new Set(reviewerIds)];
            if (reviewerIds.length !== uniqueReviewerIds.length) {
                resetSaveButton();
                Swal.fire(
                    "تحذير",
                    "لا يمكن إضافة نفس المراجع أكثر من مرة. الرجاء اختيار مراجعين مختلفين.",
                    "warning"
                );
                return;
            }

            console.log("📊 Reviewers being sent to backend:", reviewersData);

            if (reviewersData.length > 0) {
                formData.append("reviewers", JSON.stringify(reviewersData));
            }

            const reviewerDeadlines = [];

            const sortedReviewers = [...window.selectedReviewers].sort(
                (a, b) => {
                    const aOrder =
                        parseInt(
                            document.querySelector(`#${a.id} .badge`)
                                ?.textContent || a.order
                        ) || 0;
                    const bOrder =
                        parseInt(
                            document.querySelector(`#${b.id} .badge`)
                                ?.textContent || b.order
                        ) || 0;
                    return aOrder - bOrder;
                }
            );

            sortedReviewers.forEach((reviewer, index) => {
                if (reviewer.userId) {
                    const reviewerRow = document.getElementById(reviewer.id);
                    if (reviewerRow) {
                        const deadlineInput =
                            reviewerRow.querySelector(".reviewer-deadline");
                        if (deadlineInput && deadlineInput.value) {
                            const deadlineDate = new Date(deadlineInput.value);
                            const formattedDeadline = deadlineDate
                                .toISOString()
                                .slice(0, 19)
                                .replace("T", " ");

                            if (index > 0) {
                                const previousReviewer =
                                    sortedReviewers[index - 1];
                                const previousReviewerRow =
                                    document.getElementById(
                                        previousReviewer.id
                                    );
                                if (previousReviewerRow) {
                                    const previousDeadlineInput =
                                        previousReviewerRow.querySelector(
                                            ".reviewer-deadline"
                                        );
                                    if (
                                        previousDeadlineInput &&
                                        previousDeadlineInput.value
                                    ) {
                                        const previousDeadlineDate = new Date(
                                            previousDeadlineInput.value
                                        );
                                        if (
                                            deadlineDate < previousDeadlineDate
                                        ) {
                                            resetSaveButton();
                                            Swal.fire(
                                                "خطأ في الترتيب",
                                                `ديدلاين المراجع ${reviewer.order} يجب أن يكون بعد ديدلاين المراجع ${previousReviewer.order}`,
                                                "error"
                                            );
                                            return;
                                        }
                                    }
                                }
                            }

                            if (index === 0 && executorUserId) {
                                const executorDeadline =
                                    document.getElementById(
                                        "newExecutorDeadline"
                                    )?.value;
                                if (executorDeadline) {
                                    const executorDeadlineDate = new Date(
                                        executorDeadline
                                    );
                                    if (deadlineDate < executorDeadlineDate) {
                                        resetSaveButton();
                                        Swal.fire(
                                            "خطأ في الترتيب",
                                            "ديدلاين المراجع الأول يجب أن يكون بعد ديدلاين المنفذ",
                                            "error"
                                        );
                                        return;
                                    }
                                }
                            }

                            reviewerDeadlines.push({
                                reviewer_id: reviewer.userId,
                                deadline: formattedDeadline,
                            });
                        }
                    }
                }
            });

            if (reviewerDeadlines.length > 0) {
                formData.append(
                    "reviewer_deadlines",
                    JSON.stringify(reviewerDeadlines)
                );
            }
        }

        if (responsibilityNotes) {
            formData.append("responsibility_notes", responsibilityNotes);
        }
    }

    const attachmentType = document.querySelector(
        'input[name="newAttachmentType"]:checked'
    ).value;

    if (attachmentType === "file") {
        const fileInput = document.getElementById("newRevisionAttachment");
        if (fileInput.files[0]) {
            formData.append("attachment", fileInput.files[0]);
            formData.append("attachment_type", "file");
        }
    } else {
        const link = document
            .getElementById("newRevisionAttachmentLink")
            .value.trim();
        if (link) {
            formData.append("attachment_link", link);
            formData.append("attachment_type", "link");
        }
    }

    try {
        let url, method;

        if (editingRevisionId) {
            url = `/general-revisions/${editingRevisionId}`;
            method = "POST";
            formData.append("_method", "PUT");
        } else {
            url = "/general-revisions";
            method = "POST";
        }

        const response = await fetch(url, {
            method: method,
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: formData,
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                title: "تم بنجاح!",
                text: editingRevisionId
                    ? "تم تحديث التعديل بنجاح"
                    : "تم إضافة التعديل بنجاح",
                icon: "success",
                timer: 2000,
                showConfirmButton: false,
            });

            closeAddRevisionSidebar();
            refreshData();
        } else {
            resetSaveButton();
            Swal.fire(
                "خطأ",
                result.message || "حدث خطأ في حفظ التعديل",
                "error"
            );
        }
    } catch (error) {
        resetSaveButton();
        console.error("Error saving revision:", error);
        Swal.fire("خطأ", "حدث خطأ في الاتصال بالخادم", "error");
    }
}

async function openEditRevisionForm(revisionId) {
    try {
        const response = await fetch(`/revision-page/revision/${revisionId}`, {
            headers: {
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
                Accept: "application/json",
            },
        });

        const result = await response.json();

        if (!result.success || !result.revision) {
            Swal.fire("خطأ", "لم يتم العثور على التعديل", "error");
            return;
        }

        const revision = result.revision;

        console.log("📝 Revision data loaded for editing:", {
            id: revision.id,
            type: revision.revision_type,
            project_id: revision.project_id,
            project_name: revision.project?.name,
            project: revision.project,
            responsible_user_id: revision.responsible_user_id,
            responsible_user:
                revision.responsible_user || revision.responsibleUser,
            executor_user_id: revision.executor_user_id,
            executor_user: revision.executor_user || revision.executorUser,
            reviewers_count: revision.reviewers?.length || 0,
            reviewers: revision.reviewers,
            reviewers_with_data:
                revision.reviewers_with_data || revision.reviewersWithData,
        });

        const currentUserId =
            typeof AUTH_USER_ID !== "undefined" ? AUTH_USER_ID : "";
        if (revision.created_by != currentUserId) {
            Swal.fire("غير مصرح", "فقط منشئ التعديل يمكنه تعديله", "error");
            return;
        }

        editingRevisionId = revisionId;

        closeSidebar();

        document.getElementById("newRevisionType").value =
            revision.revision_type || "";
        document.getElementById("newRevisionSource").value =
            revision.revision_source || "";
        document.getElementById("newRevisionTitle").value =
            revision.title || "";
        document.getElementById("newRevisionDescription").value =
            revision.description || "";
        document.getElementById("newRevisionNotes").value =
            revision.notes || "";

        const revisionDeadlineInput = document.getElementById(
            "newRevisionDeadline"
        );
        if (revisionDeadlineInput && revision.revision_deadline) {
            const deadlineDate = new Date(revision.revision_deadline);
            const localDateTime = new Date(
                deadlineDate.getTime() -
                    deadlineDate.getTimezoneOffset() * 60000
            )
                .toISOString()
                .slice(0, 16);
            revisionDeadlineInput.value = localDateTime;

            const now = new Date();
            const minDateTime = new Date(
                now.getTime() - now.getTimezoneOffset() * 60000
            )
                .toISOString()
                .slice(0, 16);
            revisionDeadlineInput.min = minDateTime;
        } else if (revisionDeadlineInput) {
            const now = new Date();
            const minDateTime = new Date(
                now.getTime() - now.getTimezoneOffset() * 60000
            )
                .toISOString()
                .slice(0, 16);
            revisionDeadlineInput.min = minDateTime;
        }

        toggleRevisionTypeOptions();

        if (revision.revision_type === "project" && revision.project_id) {
            document.getElementById("newRevisionProjectId").value =
                revision.project_id;

            if (revision.project && revision.project.name) {
                document.getElementById("newRevisionProjectSearch").value =
                    revision.project.name;
            }

            await loadProjectParticipantsForRevision();

            console.log(
                "✅ Project participants loaded, now filling form fields..."
            );

            setTimeout(() => {
                console.log(
                    "⏱️ Filling responsibility fields after timeout..."
                );
                if (revision.responsible_user_id) {
                    document.getElementById("newResponsibleUserId").value =
                        revision.responsible_user_id;
                    const responsibleUserData =
                        revision.responsible_user || revision.responsibleUser;
                    if (responsibleUserData && responsibleUserData.name) {
                        const responsibleSearch = document.getElementById(
                            "newResponsibleUserSearch"
                        );
                        if (responsibleSearch) {
                            const isInProject =
                                window.projectParticipantIds?.includes(
                                    revision.responsible_user_id
                                );
                            responsibleSearch.value =
                                responsibleUserData.name +
                                (isInProject ? " ✅ من المشروع" : "");
                            console.log(
                                "✅ Responsible user name set:",
                                responsibleUserData.name
                            );
                        }
                    }
                }

                if (revision.executor_user_id) {
                    document.getElementById("newExecutorUserId").value =
                        revision.executor_user_id;
                    const executorUserData =
                        revision.executor_user || revision.executorUser;
                    if (executorUserData && executorUserData.name) {
                        const executorSearch = document.getElementById(
                            "newExecutorUserSearch"
                        );
                        if (executorSearch) {
                            executorSearch.value = executorUserData.name;
                            console.log(
                                "✅ Executor user name set:",
                                executorUserData.name
                            );
                        }

                        const executorDeadlineInput = document.getElementById(
                            "newExecutorDeadline"
                        );
                        const executorDeadlineContainer =
                            document.getElementById(
                                "executorDeadlineContainer"
                            );

                        if (executorDeadlineContainer) {
                            executorDeadlineContainer.style.display = "block";
                            if (executorDeadlineInput) {
                                const now = new Date();
                                const localDateTime = new Date(
                                    now.getTime() -
                                        now.getTimezoneOffset() * 60000
                                )
                                    .toISOString()
                                    .slice(0, 16);
                                executorDeadlineInput.min = localDateTime;
                            }
                        }

                        if (
                            revision.executor_deadline ||
                            (revision.deadlines &&
                                revision.deadlines.length > 0)
                        ) {
                            const executorDeadline =
                                revision.executor_deadline ||
                                revision.deadlines?.find(
                                    (d) => d.deadline_type === "executor"
                                );

                            if (executorDeadline && executorDeadlineInput) {
                                const deadlineDate = new Date(
                                    executorDeadline.deadline_date ||
                                        executorDeadline
                                );
                                const localDateTime = new Date(
                                    deadlineDate.getTime() -
                                        deadlineDate.getTimezoneOffset() * 60000
                                )
                                    .toISOString()
                                    .slice(0, 16);
                                executorDeadlineInput.value = localDateTime;
                                console.log(
                                    "✅ Executor deadline loaded:",
                                    localDateTime
                                );
                            }
                        }
                    } else {
                        console.warn("⚠️ Executor user data not found!", {
                            executor_user_id: revision.executor_user_id,
                            executor_user: revision.executor_user,
                            executorUser: revision.executorUser,
                        });
                    }
                }

                const reviewersData =
                    revision.reviewers_with_data ||
                    revision.reviewersWithData ||
                    revision.reviewers;

                if (
                    reviewersData &&
                    Array.isArray(reviewersData) &&
                    reviewersData.length > 0
                ) {
                    const reviewersList =
                        document.getElementById("reviewersList");
                    const noReviewersMsg =
                        document.getElementById("noReviewersMsg");

                    if (reviewersList && noReviewersMsg) {
                        noReviewersMsg.style.display = "none";

                        window.selectedReviewers = [];

                        console.log("📋 Loading reviewers:", reviewersData);

                        reviewersData.forEach((reviewer, index) => {
                            let userName = "مستخدم غير معروف";
                            let userId = reviewer.reviewer_id;

                            if (reviewer.user && reviewer.user.name) {
                                userName = reviewer.user.name;
                            } else {
                                const user = window.allUsers?.find(
                                    (u) => u.id == reviewer.reviewer_id
                                );
                                if (user) {
                                    userName = user.name;
                                } else {
                                    console.warn(
                                        "⚠️ User not found for reviewer:",
                                        reviewer.reviewer_id
                                    );
                                }
                            }

                            const reviewerId = `reviewer_edit_${userId}_${reviewer.order}`;
                            window.selectedReviewers.push({
                                id: reviewerId,
                                userId: userId,
                                userName: userName,
                                order: reviewer.order,
                                status: reviewer.status,
                                completed_at: reviewer.completed_at,
                            });

                            const reviewerDeadline =
                                revision.deadlines &&
                                revision.deadlines.find(
                                    (d) =>
                                        d.deadline_type === "reviewer" &&
                                        d.reviewer_order === reviewer.order
                                );
                            let deadlineValue = "";
                            if (reviewerDeadline) {
                                const deadlineDate = new Date(
                                    reviewerDeadline.deadline_date
                                );
                                deadlineValue = new Date(
                                    deadlineDate.getTime() -
                                        deadlineDate.getTimezoneOffset() * 60000
                                )
                                    .toISOString()
                                    .slice(0, 16);
                            }

                            const reviewerItem = document.createElement("div");
                            reviewerItem.className =
                                "reviewer-row mb-3 p-3 bg-white border rounded";
                            reviewerItem.id = reviewerId;
                            reviewerItem.innerHTML = `
                                <div class="mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary" style="min-width: 30px;">${
                                            reviewer.order
                                        }</span>
                                        <input type="text"
                                               class="form-control form-control-sm reviewer-search"
                                               list="reviewerUsersList"
                                               placeholder="ابحث عن المراجع..."
                                               value="${userName}"
                                               data-reviewer-id="${reviewerId}"
                                               readonly
                                               style="background-color: #f8f9fa;">
                                        <input type="hidden" class="reviewer-user-id" data-reviewer-id="${reviewerId}" value="${userId}">
                                        ${
                                            reviewer.status !== "completed"
                                                ? `
                                            <button type="button" class="btn btn-sm btn-danger" onclick="removeReviewer('${reviewerId}')" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        `
                                                : '<small class="text-success"><i class="fas fa-lock me-1"></i>مكتمل</small>'
                                        }
                                    </div>
                                    ${
                                        reviewer.status === "completed"
                                            ? '<i class="fas fa-check-circle text-success ms-2" title="مكتمل"></i>'
                                            : ""
                                    }
                                    ${
                                        reviewer.status === "in_progress"
                                            ? '<i class="fas fa-spinner text-warning ms-2" title="جاري المراجعة"></i>'
                                            : ""
                                    }
                                    ${
                                        reviewer.status === "pending"
                                            ? '<i class="fas fa-clock text-secondary ms-2" title="في الانتظار"></i>'
                                            : ""
                                    }
                                </div>
                                <div class="reviewer-deadline-container" style="display: block;">
                                    <label class="form-label" style="font-size: 12px;">
                                        <i class="fas fa-clock me-1"></i>
                                        ديدلاين المراجع <span class="text-muted" style="font-size: 10px;">(اختياري)</span>
                                    </label>
            <input type="datetime-local"
                   class="form-control form-control-sm reviewer-deadline"
                   data-reviewer-id="${reviewerId}"
                   value="${deadlineValue}"
                   min="${(() => {
                       const now = new Date();
                       return new Date(
                           now.getTime() - now.getTimezoneOffset() * 60000
                       )
                           .toISOString()
                           .slice(0, 16);
                   })()}"
                   onchange="validateReviewerDeadlineOrder('${reviewerId}')">
                                    <small class="text-muted" style="font-size: 10px;">
                                        تاريخ ووقت الانتهاء المتوقع للمراجع
                                    </small>
                                </div>
                            `;

                            reviewersList.appendChild(reviewerItem);
                        });
                    }
                }

                if (revision.responsibility_notes) {
                    document.getElementById("newResponsibilityNotes").value =
                        revision.responsibility_notes;
                }
            }, 300);
        }

        const sidebarTitle = document.querySelector("#addRevisionSidebar h5");
        if (sidebarTitle) {
            sidebarTitle.innerHTML =
                '<i class="fas fa-edit me-2"></i>تعديل التعديل';
        }

        const saveButton = document.querySelector(
            '#addRevisionSidebar button[onclick*="saveNewRevision"]'
        );
        if (saveButton) {
            saveButton.innerHTML =
                '<i class="fas fa-save me-1"></i>حفظ التعديلات';
        }

        const sidebar = document.getElementById("addRevisionSidebar");
        const overlay = document.getElementById("addRevisionOverlay");

        overlay.style.visibility = "visible";
        overlay.style.opacity = "1";
        sidebar.style.right = "0";
        document.body.style.overflow = "hidden";
    } catch (error) {
        console.error("Error loading revision for edit:", error);
        Swal.fire("خطأ", "حدث خطأ في تحميل بيانات التعديل", "error");
    }
}

function updateRevisionDeadlineMin() {
    const revisionDeadlineInput = document.getElementById(
        "newRevisionDeadline"
    );
    if (!revisionDeadlineInput) return;

    const now = new Date();
    let minDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);

    const executorDeadlineInput = document.getElementById(
        "newExecutorDeadline"
    );
    if (executorDeadlineInput && executorDeadlineInput.value) {
        const executorDeadlineDate = new Date(executorDeadlineInput.value);
        if (executorDeadlineDate > minDate) {
            minDate = executorDeadlineDate;
        }
    }

    if (window.selectedReviewers && window.selectedReviewers.length > 0) {
        const sortedReviewers = [...window.selectedReviewers].sort((a, b) => {
            const aOrder =
                parseInt(
                    document.querySelector(`#${a.id} .badge`)?.textContent ||
                        a.order
                ) || 0;
            const bOrder =
                parseInt(
                    document.querySelector(`#${b.id} .badge`)?.textContent ||
                        b.order
                ) || 0;
            return aOrder - bOrder;
        });

        for (let reviewer of sortedReviewers) {
            if (reviewer.userId) {
                const reviewerRow = document.getElementById(reviewer.id);
                if (reviewerRow) {
                    const reviewerDeadlineInput =
                        reviewerRow.querySelector(".reviewer-deadline");
                    if (reviewerDeadlineInput && reviewerDeadlineInput.value) {
                        const reviewerDeadlineDate = new Date(
                            reviewerDeadlineInput.value
                        );
                        if (reviewerDeadlineDate > minDate) {
                            minDate = reviewerDeadlineDate;
                        }
                    }
                }
            }
        }
    }

    const localDateTime = minDate.toISOString().slice(0, 16);
    revisionDeadlineInput.min = localDateTime;
}

/**
 */
function updateReviewerDeadlineMin(reviewerId) {
    const reviewerRow = document.getElementById(reviewerId);
    if (!reviewerRow) return;

    const reviewerDeadlineInput =
        reviewerRow.querySelector(".reviewer-deadline");
    if (!reviewerDeadlineInput) return;

    const now = new Date();
    let minDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);

    const reviewerBadge = reviewerRow.querySelector(".badge");
    const currentOrder = parseInt(reviewerBadge?.textContent || "0") || 0;

    if (currentOrder === 1) {
        const executorDeadlineInput = document.getElementById(
            "newExecutorDeadline"
        );
        if (executorDeadlineInput && executorDeadlineInput.value) {
            const executorDeadlineDate = new Date(executorDeadlineInput.value);
            if (executorDeadlineDate > minDate) {
                minDate = executorDeadlineDate;
            }
        }
    }

    const sortedReviewers = [...window.selectedReviewers].sort((a, b) => {
        const aOrder =
            parseInt(
                document.querySelector(`#${a.id} .badge`)?.textContent ||
                    a.order
            ) || 0;
        const bOrder =
            parseInt(
                document.querySelector(`#${b.id} .badge`)?.textContent ||
                    b.order
            ) || 0;
        return aOrder - bOrder;
    });

    const currentIndex = sortedReviewers.findIndex((r) => r.id === reviewerId);

    if (currentIndex > 0) {
        const previousReviewer = sortedReviewers[currentIndex - 1];
        const previousReviewerRow = document.getElementById(
            previousReviewer.id
        );
        if (previousReviewerRow) {
            const previousDeadlineInput =
                previousReviewerRow.querySelector(".reviewer-deadline");
            if (previousDeadlineInput && previousDeadlineInput.value) {
                const previousDeadlineDate = new Date(
                    previousDeadlineInput.value
                );
                if (previousDeadlineDate > minDate) {
                    minDate = previousDeadlineDate;
                }
            }
        }
    }

    const localDateTime = minDate.toISOString().slice(0, 16);
    reviewerDeadlineInput.min = localDateTime;

    const revisionDeadlineInput = document.getElementById(
        "newRevisionDeadline"
    );
    if (revisionDeadlineInput && revisionDeadlineInput.value) {
        const revisionDeadlineDate = new Date(revisionDeadlineInput.value);
        reviewerDeadlineInput.max = new Date(
            revisionDeadlineDate.getTime() -
                revisionDeadlineDate.getTimezoneOffset() * 60000
        )
            .toISOString()
            .slice(0, 16);
    } else {
        reviewerDeadlineInput.removeAttribute("max");
    }
}

/**
 */
function updateAllReviewersDeadlineMin() {
    if (!window.selectedReviewers || window.selectedReviewers.length === 0)
        return;

    const sortedReviewers = [...window.selectedReviewers].sort((a, b) => {
        const aOrder =
            parseInt(
                document.querySelector(`#${a.id} .badge`)?.textContent ||
                    a.order
            ) || 0;
        const bOrder =
            parseInt(
                document.querySelector(`#${b.id} .badge`)?.textContent ||
                    b.order
            ) || 0;
        return aOrder - bOrder;
    });

    sortedReviewers.forEach((reviewer) => {
        if (reviewer.userId) {
            updateReviewerDeadlineMin(reviewer.id);
        }
    });
}

/**
 */
function validateRevisionDeadlineOrder() {
    const revisionDeadlineInput = document.getElementById(
        "newRevisionDeadline"
    );
    if (!revisionDeadlineInput || !revisionDeadlineInput.value) {
        return true;
    }

    const revisionDeadlineDate = new Date(revisionDeadlineInput.value);

    const executorDeadlineInput = document.getElementById(
        "newExecutorDeadline"
    );
    if (executorDeadlineInput && executorDeadlineInput.value) {
        const executorDeadlineDate = new Date(executorDeadlineInput.value);
        if (revisionDeadlineDate < executorDeadlineDate) {
            Swal.fire({
                icon: "error",
                title: "خطأ في الترتيب",
                text: "ديدلاين التعديل يجب أن يكون بعد ديدلاين المنفذ",
                confirmButtonText: "حسناً",
            });
            revisionDeadlineInput.focus();
            return false;
        }
    }

    if (window.selectedReviewers && window.selectedReviewers.length > 0) {
        for (let reviewer of window.selectedReviewers) {
            if (reviewer.userId) {
                const reviewerRow = document.getElementById(reviewer.id);
                if (reviewerRow) {
                    const reviewerDeadlineInput =
                        reviewerRow.querySelector(".reviewer-deadline");
                    if (reviewerDeadlineInput && reviewerDeadlineInput.value) {
                        const reviewerDeadlineDate = new Date(
                            reviewerDeadlineInput.value
                        );
                        if (revisionDeadlineDate < reviewerDeadlineDate) {
                            Swal.fire({
                                icon: "error",
                                title: "خطأ في الترتيب",
                                text: `ديدلاين التعديل يجب أن يكون بعد ديدلاين المراجع ${reviewer.order}`,
                                confirmButtonText: "حسناً",
                            });
                            revisionDeadlineInput.focus();
                            return false;
                        }
                    }
                }
            }
        }
    }

    updateRevisionDeadlineMin();

    return true;
}

function updateExecutorDeadlineMin() {
    const executorDeadlineInput = document.getElementById(
        "newExecutorDeadline"
    );
    if (!executorDeadlineInput) return;

    const now = new Date();
    const localDateTime = new Date(
        now.getTime() - now.getTimezoneOffset() * 60000
    )
        .toISOString()
        .slice(0, 16);
    executorDeadlineInput.min = localDateTime;

    const revisionDeadlineInput = document.getElementById(
        "newRevisionDeadline"
    );
    if (revisionDeadlineInput && revisionDeadlineInput.value) {
        const revisionDeadlineDate = new Date(revisionDeadlineInput.value);
        executorDeadlineInput.max = new Date(
            revisionDeadlineDate.getTime() -
                revisionDeadlineDate.getTimezoneOffset() * 60000
        )
            .toISOString()
            .slice(0, 16);
    } else {
        executorDeadlineInput.removeAttribute("max");
    }
}

/**
 */
function validateExecutorDeadlineOrder() {
    const executorDeadlineInput = document.getElementById(
        "newExecutorDeadline"
    );
    if (!executorDeadlineInput || !executorDeadlineInput.value) {
        return true;
    }

    const executorDeadlineDate = new Date(executorDeadlineInput.value);

    if (window.selectedReviewers && window.selectedReviewers.length > 0) {
        const sortedReviewers = [...window.selectedReviewers].sort((a, b) => {
            const aOrder =
                parseInt(
                    document.querySelector(`#${a.id} .badge`)?.textContent ||
                        a.order
                ) || 0;
            const bOrder =
                parseInt(
                    document.querySelector(`#${b.id} .badge`)?.textContent ||
                        b.order
                ) || 0;
            return aOrder - bOrder;
        });

        const firstReviewer = sortedReviewers.find((r) => r.userId);
        if (firstReviewer) {
            const firstReviewerRow = document.getElementById(firstReviewer.id);
            if (firstReviewerRow) {
                const firstReviewerDeadlineInput =
                    firstReviewerRow.querySelector(".reviewer-deadline");
                if (
                    firstReviewerDeadlineInput &&
                    firstReviewerDeadlineInput.value
                ) {
                    const firstReviewerDeadlineDate = new Date(
                        firstReviewerDeadlineInput.value
                    );
                    if (executorDeadlineDate > firstReviewerDeadlineDate) {
                        Swal.fire({
                            icon: "error",
                            title: "خطأ في الترتيب",
                            text: "ديدلاين المنفذ يجب أن يكون قبل ديدلاين المراجع الأول",
                            confirmButtonText: "حسناً",
                        });
                        executorDeadlineInput.focus();
                        return false;
                    }
                }
            }
        }
    }

    const revisionDeadlineInput = document.getElementById(
        "newRevisionDeadline"
    );
    if (revisionDeadlineInput && revisionDeadlineInput.value) {
        const revisionDeadlineDate = new Date(revisionDeadlineInput.value);
        if (executorDeadlineDate > revisionDeadlineDate) {
            Swal.fire({
                icon: "error",
                title: "خطأ في الترتيب",
                text: "ديدلاين المنفذ يجب أن يكون قبل ديدلاين التعديل العام",
                confirmButtonText: "حسناً",
            });
            executorDeadlineInput.focus();
            return false;
        }
    }

    updateAllReviewersDeadlineMin();
    updateRevisionDeadlineMin();

    return true;
}

/**
 */
function validateReviewerDeadlineOrder(reviewerId) {
    const reviewerRow = document.getElementById(reviewerId);
    if (!reviewerRow) {
        return true;
    }

    const reviewerDeadlineInput =
        reviewerRow.querySelector(".reviewer-deadline");
    if (!reviewerDeadlineInput || !reviewerDeadlineInput.value) {
        return true;
    }

    const reviewerDeadlineDate = new Date(reviewerDeadlineInput.value);

    const reviewerBadge = reviewerRow.querySelector(".badge");
    const currentOrder = parseInt(reviewerBadge?.textContent || "0") || 0;

    const sortedReviewers = [...window.selectedReviewers].sort((a, b) => {
        const aOrder =
            parseInt(
                document.querySelector(`#${a.id} .badge`)?.textContent ||
                    a.order
            ) || 0;
        const bOrder =
            parseInt(
                document.querySelector(`#${b.id} .badge`)?.textContent ||
                    b.order
            ) || 0;
        return aOrder - bOrder;
    });

    const currentIndex = sortedReviewers.findIndex((r) => r.id === reviewerId);

    if (currentIndex > 0) {
        const previousReviewer = sortedReviewers[currentIndex - 1];
        const previousReviewerRow = document.getElementById(
            previousReviewer.id
        );
        if (previousReviewerRow) {
            const previousDeadlineInput =
                previousReviewerRow.querySelector(".reviewer-deadline");
            if (previousDeadlineInput && previousDeadlineInput.value) {
                const previousDeadlineDate = new Date(
                    previousDeadlineInput.value
                );
                if (reviewerDeadlineDate < previousDeadlineDate) {
                    Swal.fire({
                        icon: "error",
                        title: "خطأ في الترتيب",
                        text: `ديدلاين المراجع ${currentOrder} يجب أن يكون بعد ديدلاين المراجع ${previousReviewer.order}`,
                        confirmButtonText: "حسناً",
                    });
                    reviewerDeadlineInput.focus();
                    return false;
                }
            }
        }
    }

    if (currentIndex === 0) {
        const executorDeadlineInput = document.getElementById(
            "newExecutorDeadline"
        );
        if (executorDeadlineInput && executorDeadlineInput.value) {
            const executorDeadlineDate = new Date(executorDeadlineInput.value);
            if (reviewerDeadlineDate < executorDeadlineDate) {
                Swal.fire({
                    icon: "error",
                    title: "خطأ في الترتيب",
                    text: "ديدلاين المراجع الأول يجب أن يكون بعد ديدلاين المنفذ",
                    confirmButtonText: "حسناً",
                });
                reviewerDeadlineInput.focus();
                return false;
            }
        }
    }

    if (currentIndex < sortedReviewers.length - 1) {
        const nextReviewer = sortedReviewers[currentIndex + 1];
        const nextReviewerRow = document.getElementById(nextReviewer.id);
        if (nextReviewerRow) {
            const nextDeadlineInput =
                nextReviewerRow.querySelector(".reviewer-deadline");
            if (nextDeadlineInput && nextDeadlineInput.value) {
                const nextDeadlineDate = new Date(nextDeadlineInput.value);
                if (reviewerDeadlineDate > nextDeadlineDate) {
                    Swal.fire({
                        icon: "error",
                        title: "خطأ في الترتيب",
                        text: `ديدلاين المراجع ${currentOrder} يجب أن يكون قبل ديدلاين المراجع ${nextReviewer.order}`,
                        confirmButtonText: "حسناً",
                    });
                    reviewerDeadlineInput.focus();
                    return false;
                }
            }
        }
    }

    const revisionDeadlineInput = document.getElementById(
        "newRevisionDeadline"
    );
    if (revisionDeadlineInput && revisionDeadlineInput.value) {
        const revisionDeadlineDate = new Date(revisionDeadlineInput.value);
        if (reviewerDeadlineDate > revisionDeadlineDate) {
            Swal.fire({
                icon: "error",
                title: "خطأ في الترتيب",
                text: `ديدلاين المراجع ${currentOrder} يجب أن يكون قبل ديدلاين التعديل العام`,
                confirmButtonText: "حسناً",
            });
            reviewerDeadlineInput.focus();
            return false;
        }
    }

    updateAllReviewersDeadlineMin();
    updateRevisionDeadlineMin();

    return true;
}

window.validateRevisionDeadlineOrder = validateRevisionDeadlineOrder;
window.validateExecutorDeadlineOrder = validateExecutorDeadlineOrder;
window.validateReviewerDeadlineOrder = validateReviewerDeadlineOrder;
window.updateExecutorDeadlineMin = updateExecutorDeadlineMin;
window.updateReviewerDeadlineMin = updateReviewerDeadlineMin;
window.updateRevisionDeadlineMin = updateRevisionDeadlineMin;
window.updateAllReviewersDeadlineMin = updateAllReviewersDeadlineMin;
