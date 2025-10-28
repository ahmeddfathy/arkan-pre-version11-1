<!-- قسم الملاحظات -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-gradient-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-sticky-note me-2"></i>
                        ملاحظات المشروع
                        <span class="badge bg-light text-dark ms-2" id="notesCount">0</span>
                    </h5>

                    <div class="d-flex gap-2">
                        <!-- إحصائيات سريعة -->
                        <div class="d-flex align-items-center gap-2 me-3">
                            <span class="badge bg-warning text-dark" id="importantNotesCount" title="ملاحظات مهمة">
                                <i class="fas fa-star"></i> <span>0</span>
                            </span>
                            <span class="badge bg-primary" id="pinnedNotesCount" title="ملاحظات مثبتة">
                                <i class="fas fa-thumbtack"></i> <span>0</span>
                            </span>
                        </div>

                        <!-- فلاتر سريعة -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-light" id="filterAllNotes" data-filter="all">
                                الكل
                            </button>
                            <button type="button" class="btn btn-outline-light" id="filterImportantNotes" data-filter="important">
                                مهم
                            </button>
                            <button type="button" class="btn btn-outline-light" id="filterPinnedNotes" data-filter="pinned">
                                مثبت
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <!-- نموذج إضافة ملاحظة جديدة -->
                <div class="add-note-section mb-4">
                    <form id="addNoteForm" class="add-note-form">
                        @csrf
                        <div class="d-flex align-items-start gap-3">
                            <!-- صورة المستخدم -->
                            <img src="{{ Auth::user()->avatar ?? asset('avatars/man.gif') }}"
                                 class="rounded-circle" width="45" height="45" alt="{{ Auth::user()->name }}">

                            <div class="flex-grow-1">
                                <!-- منطقة النص مع الـ mentions -->
                                <div class="position-relative">
                                    <textarea class="form-control note-textarea"
                                              id="noteContent"
                                              name="content"
                                              rows="3"
                                              placeholder="أضف ملاحظة للمشروع... (استخدم @ لذكر أحد المشاركين)"
                                              style="resize: vertical; min-height: 80px;"></textarea>

                                    <!-- قائمة المستخدمين المنسدلة للـ mentions -->
                                    <div id="mentionsDropdown" class="mentions-dropdown position-absolute w-100 d-none shadow-lg border rounded bg-white"
                                         style="top: 100%; z-index: 1050; max-height: 200px; overflow-y: auto;">
                                        <!-- سيتم ملؤها بـ JavaScript -->
                                    </div>
                                </div>

                                <!-- شريط الأدوات -->
                                <div class="note-toolbar d-flex justify-content-between align-items-center mt-3">
                                    <div class="note-options d-flex gap-3">
                                        <!-- نوع الملاحظة -->
                                        <select class="form-select form-select-sm" id="noteType" name="note_type" style="width: auto;">
                                            <option value="general">عام</option>
                                            <option value="update">تحديث</option>
                                            <option value="issue">مشكلة</option>
                                            <option value="question">سؤال</option>
                                            <option value="solution">حل</option>
                                        </select>

                                        <!-- فلتر القسم -->
                                        <div class="d-flex align-items-center gap-2">
                                            <small class="text-muted" style="white-space: nowrap;">📂 مرئية لـ:</small>
                                            <select class="form-select form-select-sm" id="targetDepartment" name="target_department" style="width: auto; min-width: 120px;" title="اختر قسم معين لعرض الملاحظة له فقط، أو اتركه فارغ لعرضها لجميع الأقسام">
                                                <option value="">كل الأقسام</option>
                                                <!-- سيتم ملؤها بـ JavaScript -->
                                            </select>
                                        </div>

                                        <!-- خيارات إضافية -->
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isImportant" name="is_important">
                                            <label class="form-check-label text-warning" for="isImportant">
                                                <i class="fas fa-star"></i> مهم
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="isPinned" name="is_pinned">
                                            <label class="form-check-label text-primary" for="isPinned">
                                                <i class="fas fa-thumbtack"></i> تثبيت
                                            </label>
                                        </div>
                                    </div>

                                    <!-- أزرار الإجراءات -->
                                    <div class="note-actions d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelNote">
                                            <i class="fas fa-times"></i> إلغاء
                                        </button>
                                        <button type="submit" class="btn btn-primary btn-sm" id="submitNote">
                                            <i class="fas fa-paper-plane"></i> إضافة ملاحظة
                                        </button>
                                    </div>
                                </div>

                                <!-- نصائح للمستخدم -->
                                <div class="mt-2">
                                    <small class="text-muted d-block note-tips">
                                        💡 <strong>نصائح:</strong> للإشعارات: استخدم <code>@اسم_المستخدم</code> لذكر شخص معين، أو <code>@اسم_القسم</code> لذكر كل أعضاء القسم
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- قسم البحث والفلترة المتقدم -->
                <div class="notes-filters mb-3 d-none" id="advancedFilters">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm"
                                   id="searchNotes" placeholder="بحث في الملاحظات...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterNoteType">
                                <option value="">جميع الأنواع</option>
                                <option value="general">عام</option>
                                <option value="update">تحديث</option>
                                <option value="issue">مشكلة</option>
                                <option value="question">سؤال</option>
                                <option value="solution">حل</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="filterNoteAuthor">
                                <option value="">جميع المؤلفين</option>
                                <!-- سيتم ملؤها بـ JavaScript -->
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-secondary btn-sm w-100" id="toggleAdvancedFilters">
                                <i class="fas fa-times"></i> إخفاء
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm" id="toggleAdvancedFilters">
                            <i class="fas fa-filter"></i> فلاتر متقدمة
                        </button>
                        <button class="btn btn-outline-info btn-sm" id="refreshNotes">
                            <i class="fas fa-sync-alt"></i> تحديث
                        </button>
                        <button class="btn btn-outline-warning btn-sm" id="resetNotesSystem" title="إعادة تعيين نظام الملاحظات في حالة حدوث مشكلة">
                            <i class="fas fa-tools"></i> إصلاح
                        </button>
                    </div>

                    <div class="notes-sorting">
                        <select class="form-select form-select-sm" id="notesSort">
                            <option value="newest">الأحدث أولاً</option>
                            <option value="oldest">الأقدم أولاً</option>
                            <option value="important">المهم أولاً</option>
                            <option value="pinned">المثبت أولاً</option>
                        </select>
                    </div>
                </div>

                <!-- قائمة الملاحظات -->
                <div id="notesList" class="notes-list">
                    <!-- Loading spinner -->
                    <div class="text-center py-5" id="notesLoading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <div class="mt-2 text-muted">جاري تحميل الملاحظات...</div>
                    </div>
                </div>

                <!-- رسالة فارغة -->
                <div id="emptyNotesMessage" class="text-center py-5 d-none">
                    <div class="mb-3">
                        <i class="fas fa-sticky-note fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted">لا توجد ملاحظات بعد</h5>
                    <p class="text-muted">كن أول من يضيف ملاحظة لهذا المشروع!</p>
                </div>

                <!-- pagination -->
                <div id="notesPagination" class="d-flex justify-content-center mt-4">
                    <!-- سيتم ملؤها بـ JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تحرير الملاحظة -->
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-labelledby="editNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editNoteModalLabel">
                    <i class="fas fa-edit me-2"></i>تحرير الملاحظة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editNoteForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editNoteId">

                    <div class="mb-3">
                        <label for="editNoteContent" class="form-label">المحتوى</label>
                        <div class="position-relative">
                            <textarea class="form-control" id="editNoteContent" name="content"
                                      rows="4" style="resize: vertical;"></textarea>

                            <!-- mentions dropdown for edit modal -->
                            <div id="editMentionsDropdown" class="mentions-dropdown position-absolute w-100 d-none shadow-lg border rounded bg-white"
                                 style="top: 100%; z-index: 1060; max-height: 200px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label for="editNoteType" class="form-label">النوع</label>
                            <select class="form-select" id="editNoteType" name="note_type">
                                <option value="general">عام</option>
                                <option value="update">تحديث</option>
                                <option value="issue">مشكلة</option>
                                <option value="question">سؤال</option>
                                <option value="solution">حل</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="editIsImportant" name="is_important">
                                <label class="form-check-label text-warning" for="editIsImportant">
                                    <i class="fas fa-star"></i> مهم
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="editIsPinned" name="is_pinned">
                                <label class="form-check-label text-primary" for="editIsPinned">
                                    <i class="fas fa-thumbtack"></i> مثبت
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveEditedNote">
                    <i class="fas fa-save"></i> حفظ التغييرات
                </button>
            </div>
        </div>
    </div>
</div>

<!-- أنماط CSS للملاحظات -->
<style>
.notes-list {
    max-height: 600px;
    overflow-y: auto;
}

.note-item {
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.note-item.important {
    border-left-color: #ffc107;
    background-color: rgba(255, 193, 7, 0.1);
}

.note-item.pinned {
    border-left-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.note-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.mention {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.mention:hover {
    background-color: rgba(13, 110, 253, 0.2);
    color: #0a58ca;
}

.mentions-dropdown {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    max-height: 200px;
    overflow-y: auto;
}

.mentions-dropdown .list-group-item {
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.mentions-dropdown .list-group-item:hover {
    background-color: #f8f9fa;
}

.mentions-dropdown .list-group-item.active {
    background-color: #e7f3ff;
    color: #0d6efd;
}

.note-type-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.note-actions .btn {
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.note-item:hover .note-actions .btn {
    opacity: 1;
}

.note-textarea:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

@keyframes noteAdded {
    0% {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
        background-color: rgba(13, 110, 253, 0.1);
    }
    50% {
        background-color: rgba(13, 110, 253, 0.05);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
        background-color: transparent;
    }
}

.note-item.newly-added {
    animation: noteAdded 0.8s ease-out;
    border-left: 4px solid #0d6efd !important;
}

.note-item.newly-added .note-content {
    animation: fadeInContent 0.5s ease-out 0.3s both;
}

@keyframes fadeInContent {
    0% { opacity: 0; }
    100% { opacity: 1; }
}
</style>
