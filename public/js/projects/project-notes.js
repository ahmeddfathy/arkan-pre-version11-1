

class ProjectNotesManager {
    constructor(projectId) {
        this.projectId = projectId;
        this.projectUsers = [];
        this.projectDepartments = [];
        this.currentNotes = [];
        this.currentPage = 1;
        this.currentFilters = {
            query: '',
            note_type: '',
            user_id: '',
            filter: 'all',
            target_department: ''
        };

        this.editingNoteId = null;
        this.mentionsCursor = -1; // للتحكم بالـ keyboard navigation
        this.isSubmitting = false; // لمنع الإرسال المتكرر

        this.init();
    }

    init() {
        // التحقق من صحة project ID
        if (!this.projectId) {
            console.error('❌ Project ID not found!');
            this.showToast('خطأ في تحميل بيانات المشروع', 'error');
            return;
        }

        console.log('🚀 تهيئة نظام الملاحظات للمشروع:', this.projectId);

        this.loadProjectUsers();
        this.bindEvents();
        this.loadNotes();
        this.loadNotesStats();
        this.setupMentionsSystem();
        this.loadProjectDepartments();
    }


    async loadProjectUsers() {
        try {
            const response = await fetch(`/projects/${this.projectId}/users-for-mentions`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // التحقق من أن الاستجابة JSON صحيحة
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('غير صحيح JSON response في تحميل المستخدمين:', text);
                throw new Error('خطأ في استجابة الخادم');
            }

            const data = await response.json();

            if (data.success) {
                this.projectUsers = data.users || [];
                console.log('تم تحميل مستخدمي المشروع:', this.projectUsers.length);
            } else {
                console.error('فشل في تحميل المستخدمين:', data.message);
            }
        } catch (error) {
            console.error('خطأ في تحميل المستخدمين:', error);
            this.projectUsers = [];
        }
    }

    /**
     * تحميل أقسام المشروع
     */
    async loadProjectDepartments() {
        try {
            if (this.projectUsers.length === 0) {
                console.log('⚠️ لم يتم تحميل المستخدمين بعد، انتظار...');
                setTimeout(() => this.loadProjectDepartments(), 500);
                return;
            }

            // استخراج الأقسام من المستخدمين المحملين
            const departments = [...new Set(
                this.projectUsers
                    .filter(user => user.department && user.department.trim())
                    .map(user => user.department.trim())
            )].sort();

            this.projectDepartments = departments;
            console.log('🏢 تم تحميل أقسام المشروع:', departments);

            // ملء فلتر الأقسام
            this.populateDepartmentFilter();

        } catch (error) {
            console.error('خطأ في تحميل أقسام المشروع:', error);
            this.projectDepartments = [];
        }
    }

    /**
     * ملء فلتر الأقسام
     */
    populateDepartmentFilter() {
        const departmentSelect = document.getElementById('targetDepartment');

        if (!departmentSelect) {
            console.warn('⚠️ فلتر الأقسام غير موجود');
            return;
        }

        // مسح الخيارات الموجودة (ما عدا "كل الأقسام")
        const defaultOption = departmentSelect.querySelector('option[value=""]');
        departmentSelect.innerHTML = '';
        if (defaultOption) {
            departmentSelect.appendChild(defaultOption);
        }

        // إضافة أقسام المشروع
        this.projectDepartments.forEach(department => {
            const option = document.createElement('option');
            option.value = department;
            option.textContent = department;
            departmentSelect.appendChild(option);
        });

        console.log('📋 تم ملء فلتر الأقسام بنجاح');
    }

    /**
     * تحديث النصائح بناءً على القسم المختار
     */
    updateNoteTips(selectedDepartment) {
        const tipsElement = document.querySelector('.note-tips');

        if (!tipsElement) {
            // إذا لم توجد، إنشاؤها
            const tipsContainer = document.querySelector('.mt-2');
            if (tipsContainer) {
                tipsContainer.querySelector('small').classList.add('note-tips');
            }
        }

        const tipsContent = document.querySelector('.note-tips');
        if (tipsContent) {
            let tipsText = '💡 <strong>نصائح:</strong> ';

            if (selectedDepartment) {
                tipsText += `📂 هذه الملاحظة ستكون مرئية لقسم "${selectedDepartment}" فقط. `;
                tipsText += 'للإشعارات، استخدم <code>@اسم_المستخدم</code> أو <code>@اسم_القسم</code>';
            } else {
                tipsText += 'للإشعارات: استخدم <code>@اسم_المستخدم</code> لذكر شخص معين، أو <code>@اسم_القسم</code> لذكر كل أعضاء القسم';
            }

            tipsContent.innerHTML = tipsText;
        }
    }

    /**
     * ربط الأحداث
     */
    bindEvents() {
        // إرسال ملاحظة جديدة
        document.getElementById('addNoteForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitNote();
        });

        // إلغاء إضافة ملاحظة
        document.getElementById('cancelNote').addEventListener('click', () => {
            this.resetAddNoteForm();
        });

        // الفلاتر السريعة
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.applyQuickFilter(e.target.dataset.filter);
            });
        });

        // تبديل الفلاتر المتقدمة
        document.getElementById('toggleAdvancedFilters').addEventListener('click', () => {
            this.toggleAdvancedFilters();
        });

        // البحث المباشر
        document.getElementById('searchNotes').addEventListener('input', (e) => {
            this.debounce(() => {
                this.currentFilters.query = e.target.value;
                this.loadNotes();
            }, 300);
        });

        // فلاتر متقدمة
        ['filterNoteType', 'filterNoteAuthor', 'notesSort'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('change', () => this.applyAdvancedFilters());
            }
        });

        // فلتر القسم في إضافة الملاحظة
        const targetDepartmentSelect = document.getElementById('targetDepartment');
        if (targetDepartmentSelect) {
            targetDepartmentSelect.addEventListener('change', (e) => {
                this.currentFilters.target_department = e.target.value;
                console.log('🏢 تم تغيير فلتر القسم:', e.target.value || 'كل الأقسام');

                // تحديث النصائح بناءً على الاختيار
                this.updateNoteTips(e.target.value);
            });
        }

        // تحديث الملاحظات
        document.getElementById('refreshNotes').addEventListener('click', () => {
            this.refreshNotes();
        });

        // إعادة تعيين نظام الملاحظات
        const resetBtn = document.getElementById('resetNotesSystem');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.fullSystemReset();
            });
        }

        // حفظ التعديل
        document.getElementById('saveEditedNote').addEventListener('click', () => {
            this.saveEditedNote();
        });
    }

    /**
     * إعداد نظام الـ mentions
     */
    setupMentionsSystem() {
        // للنص الرئيسي
        this.setupMentionsForTextarea('noteContent', 'mentionsDropdown');

        // لنص التحرير
        this.setupMentionsForTextarea('editNoteContent', 'editMentionsDropdown');
    }

    /**
     * إعداد نظام mentions لـ textarea معين
     */
    setupMentionsForTextarea(textareaId, dropdownId) {
        const textarea = document.getElementById(textareaId);
        const dropdown = document.getElementById(dropdownId);

        if (!textarea || !dropdown) return;

        textarea.addEventListener('input', (e) => {
            this.handleMentionTyping(e, dropdown);
        });

        textarea.addEventListener('keydown', (e) => {
            this.handleMentionKeyNavigation(e, dropdown);
        });

        // إخفاء الـ dropdown عند النقر خارجه
        document.addEventListener('click', (e) => {
            if (!textarea.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('d-none');
            }
        });
    }

    /**
     * التعامل مع كتابة @ mentions
     */
    handleMentionTyping(event, dropdown) {
        const textarea = event.target;
        const text = textarea.value;
        const cursorPosition = textarea.selectionStart;

        // البحث عن @ قبل المؤشر
        const textBeforeCursor = text.substring(0, cursorPosition);
        const atIndex = textBeforeCursor.lastIndexOf('@');

        if (atIndex !== -1) {
            const searchTerm = textBeforeCursor.substring(atIndex + 1);

            // إذا لم يكن هناك مسافة بعد @ ولم ينته البحث بمسافة
            if (!searchTerm.includes(' ') && !searchTerm.includes('\n')) {
                this.showMentionsDropdown(searchTerm, dropdown, textarea, atIndex);
                return;
            }
        }

        this.hideMentionsDropdown(dropdown);
    }

    /**
     * التعامل مع التنقل بـ keyboard في قائمة الـ mentions
     */
    handleMentionKeyNavigation(event, dropdown) {
        if (dropdown.classList.contains('d-none')) return;

        const items = dropdown.querySelectorAll('.list-group-item:not(.disabled)');
        if (items.length === 0) return;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.mentionsCursor = Math.min(this.mentionsCursor + 1, items.length - 1);
                this.updateMentionsHighlight(items);
                break;

            case 'ArrowUp':
                event.preventDefault();
                this.mentionsCursor = Math.max(this.mentionsCursor - 1, 0);
                this.updateMentionsHighlight(items);
                break;

            case 'Enter':
            case 'Tab':
                event.preventDefault();
                if (this.mentionsCursor >= 0 && items[this.mentionsCursor]) {
                    this.selectMention(items[this.mentionsCursor], event.target);
                }
                break;

            case 'Escape':
                this.hideMentionsDropdown(dropdown);
                break;
        }
    }

    /**
     * تحديث التمييز في قائمة الـ mentions
     */
    updateMentionsHighlight(items) {
        items.forEach((item, index) => {
            item.classList.toggle('active', index === this.mentionsCursor);
        });
    }

    /**
     * عرض قائمة المستخدمين والأقسام للـ mentions
     */
    showMentionsDropdown(searchTerm, dropdown, textarea, atPosition) {
        const filteredUsers = this.projectUsers.filter(user =>
            user.name.toLowerCase().includes(searchTerm.toLowerCase())
        );

        const filteredDepartments = this.projectDepartments.filter(department =>
            department.toLowerCase().includes(searchTerm.toLowerCase())
        );

        if (filteredUsers.length === 0 && filteredDepartments.length === 0) {
            this.hideMentionsDropdown(dropdown);
            return;
        }

        let dropdownHTML = '<div class="list-group">';
        let itemIndex = 0;

        // إضافة الأقسام أولاً
        if (filteredDepartments.length > 0) {
            dropdownHTML += '<div class="list-group-item disabled bg-light"><small class="text-muted fw-bold">🏢 الأقسام</small></div>';
            filteredDepartments.forEach((department) => {
                dropdownHTML += `
                    <div class="list-group-item list-group-item-action d-flex align-items-center ${itemIndex === 0 ? 'active' : ''}"
                         data-type="department"
                         data-department-name="${department}"
                         data-at-position="${atPosition}">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2"
                             style="width: 32px; height: 32px;">
                            <i class="fas fa-users fa-sm"></i>
                        </div>
                        <div>
                            <div class="fw-bold">@${department}</div>
                            <small class="text-muted">كل أعضاء القسم</small>
                        </div>
                    </div>
                `;
                itemIndex++;
            });
        }

        // إضافة المستخدمين
        if (filteredUsers.length > 0) {
            if (filteredDepartments.length > 0) {
                dropdownHTML += '<div class="list-group-item disabled bg-light"><small class="text-muted fw-bold">👤 المستخدمين</small></div>';
            }
            filteredUsers.forEach((user) => {
                dropdownHTML += `
                    <div class="list-group-item list-group-item-action d-flex align-items-center ${(filteredDepartments.length === 0 && itemIndex === 0) ? 'active' : ''}"
                         data-type="user"
                         data-user-id="${user.id}"
                         data-user-name="${user.name}"
                         data-at-position="${atPosition}">
                        <img src="${user.avatar}"
                             class="rounded-circle me-2"
                             width="32" height="32"
                             alt="${user.name}">
                        <div>
                            <div class="fw-bold">${user.name}</div>
                            <small class="text-muted">${user.department || 'لا يوجد قسم'}</small>
                        </div>
                    </div>
                `;
                itemIndex++;
            });
        }

        dropdownHTML += '</div>';

        dropdown.innerHTML = dropdownHTML;
        dropdown.classList.remove('d-none');

        // إعادة تعيين المؤشر
        this.mentionsCursor = 0;

        // ربط النقر على العناصر
        dropdown.querySelectorAll('.list-group-item:not(.disabled)').forEach(item => {
            item.addEventListener('click', () => {
                this.selectMention(item, textarea);
            });
        });
    }

    /**
     * اختيار مستخدم أو قسم للـ mention
     */
    selectMention(item, textarea) {
        const type = item.dataset.type;
        const atPosition = parseInt(item.dataset.atPosition);

        let mentionText = '';

        if (type === 'department') {
            const departmentName = item.dataset.departmentName;
            mentionText = '@' + departmentName;
        } else if (type === 'user') {
            const userName = item.dataset.userName;
            mentionText = '@' + userName;
        } else {
            // Fallback للكود القديم
            const userName = item.dataset.userName;
            mentionText = '@' + userName;
        }

        const currentText = textarea.value;
        const textBefore = currentText.substring(0, atPosition);
        const textAfter = currentText.substring(textarea.selectionStart);

        // استبدال @ مع النص المناسب
        const newText = textBefore + mentionText + ' ' + textAfter;
        textarea.value = newText;

        // تحديد المؤشر بعد النص
        const newPosition = atPosition + mentionText.length + 1;
        textarea.setSelectionRange(newPosition, newPosition);
        textarea.focus();

        this.hideMentionsDropdown(item.closest('.mentions-dropdown'));

        console.log(`✅ تم اختيار ${type === 'department' ? 'القسم' : 'المستخدم'}:`, mentionText);
    }

    /**
     * إخفاء قائمة الـ mentions
     */
    hideMentionsDropdown(dropdown) {
        dropdown.classList.add('d-none');
        this.mentionsCursor = -1;
    }

    /**
     * إرسال ملاحظة جديدة
     */
        async submitNote() {
        const form = document.getElementById('addNoteForm');
        const submitBtn = document.getElementById('submitNote');

        if (!form) {
            console.error('❌ Form not found!');
            this.showToast('خطأ في العثور على النموذج', 'error');
            return;
        }

        if (!submitBtn) {
            console.error('❌ Submit button not found!');
            this.showToast('خطأ في العثور على زر الإرسال', 'error');
            return;
        }

        // إعادة تعيين وقائية للحالة قبل البدء
        if (this.isSubmitting || submitBtn.disabled) {
            console.log('🔄 إعادة تعيين وقائية للحالة قبل البدء');
            this.isSubmitting = false;
            submitBtn.disabled = false;
        }

        // التحقق من حالة الزر والإرسال أولاً مع logging مفصل
        console.log('🔍 فحص حالة الإرسال:', {
            isSubmitting: this.isSubmitting,
            buttonDisabled: submitBtn.disabled,
            buttonText: submitBtn.innerHTML
        });

        const formData = new FormData(form);
        const originalText = submitBtn.innerHTML;

        // تحقق من وجود محتوى
        const content = formData.get('content').trim();
        if (!content) {
            this.showToast('يرجى إدخال محتوى الملاحظة', 'error');
            return;
        }

        // تحقق من طول المحتوى
        if (content.length > 5000) {
            this.showToast('المحتوى طويل جداً (الحد الأقصى 5000 حرف)', 'error');
            return;
        }

        try {
            // تعطيل الزر وضبط حالة الإرسال
            this.isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإضافة...';

            const targetDepartment = formData.get('target_department');
            console.log('🚀 إرسال ملاحظة جديدة...', {
                projectId: this.projectId,
                content: content.substring(0, 50) + (content.length > 50 ? '...' : ''),
                targetDepartment: targetDepartment || 'كل الأقسام',
                hasMentions: content.includes('@')
            });

            // عرض رسالة أثناء الانتظار
            this.showToast('جاري إضافة الملاحظة...', 'info');

            // التحقق من وجود CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                throw new Error('CSRF token not found');
            }

            // إضافة timeout محسن للطلب (10 ثواني)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => {
                console.log('⏰ انتهت مهلة الطلب - إلغاء الطلب');
                controller.abort();
            }, 10000);

            const response = await fetch(`/projects/${this.projectId}/notes`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            console.log('📡 Response status:', response.status);
            console.log('📡 Response ok:', response.ok);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Response error:', errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const data = await response.json();
            console.log('📄 Response data:', data);

            if (data.success) {
                let successMessage = 'تم إضافة الملاحظة بنجاح';

                // إضافة تفاصيل القسم المستهدف
                const targetDept = formData.get('target_department');
                if (targetDept) {
                    successMessage += ` لقسم "${targetDept}"`;
                }

                this.showToast(successMessage, 'success');

                // إظهار رسالة عن الإشعارات إن وجدت mentions فقط
                if (data.mentions_count > 0) {
                    setTimeout(() => {
                        this.showToast(`سيتم إرسال إشعارات لـ ${data.mentions_count} مستخدم مذكور في الخلفية`, 'info');
                    }, 1000);
                }

                                // إعادة تعيين النموذج أولاً
                this.resetAddNoteForm();

                // التحقق من وجود بيانات الملاحظة
                if (data.note && data.note.id) {
                    // إضافة الملاحظة الجديدة مباشرة للـ UI
                    console.log('➕ إضافة الملاحظة الجديدة للـ UI...');
                    this.addNoteToUI(data.note);
                } else {
                    // إعادة تحميل كامل إذا لم توجد بيانات الملاحظة
                    console.log('🔄 إعادة تحميل كامل للملاحظات...');
                    await this.loadNotes();
                }

                // إعادة تحميل الإحصائيات
                await this.loadNotesStats();

                console.log('✅ تم تحديث الملاحظات بنجاح');

            } else {
                console.error('❌ Server error:', data.message);
                this.showToast(data.message || 'حدث خطأ أثناء إضافة الملاحظة', 'error');
            }

        } catch (error) {
            console.error('❌ خطأ في إضافة الملاحظة:', error);

            let errorMessage = 'حدث خطأ في الاتصال';

            if (error.name === 'AbortError') {
                errorMessage = 'انتهت مهلة الطلب - يرجى المحاولة مرة أخرى';
                console.log('⏰ تم إلغاء الطلب بسبب انتهاء المهلة');
            } else if (error.message) {
                errorMessage = error.message;
                console.log('📝 رسالة خطأ مخصصة:', error.message);
            }

            // إضافة معلومات إضافية عن الخطأ
            console.log('🔍 تفاصيل الخطأ الكاملة:', {
                name: error.name,
                message: error.message,
                stack: error.stack,
                type: typeof error
            });

            this.showToast(errorMessage, 'error');
        } finally {
            // إعادة تعيين حالة الإرسال وتفعيل الزر دائماً في النهاية
            console.log('🔧 إعادة تعيين حالة الإرسال في finally block');
            this.isSubmitting = false;
            this.resetSubmitButton();
        }
    }

    /**
     * إعادة تعيين نموذج إضافة ملاحظة
     */
    resetAddNoteForm() {
        const form = document.getElementById('addNoteForm');
        form.reset();

        // إخفاء قائمة الـ mentions
        this.hideMentionsDropdown(document.getElementById('mentionsDropdown'));
    }

        /**
     * إعادة تعيين حالة زر الإرسال
     */
    resetSubmitButton() {
        try {
            const submitBtn = document.getElementById('submitNote');
            if (submitBtn) {
                // إزالة setTimeout وإجراء التعيين مباشرة
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> إضافة ملاحظة';
                console.log('🔄 تم إعادة تعيين زر الإرسال مباشرة');
            } else {
                console.warn('⚠️ زر الإرسال غير موجود');
            }
        } catch (error) {
            console.error('❌ خطأ في إعادة تعيين زر الإرسال:', error);
        }
    }

    /**
     * إعادة تعيين قسرية لحالة الإرسال (في حالات الطوارئ)
     */
    forceResetSubmissionState() {
        try {
            console.log('🚨 إعادة تعيين قسرية لحالة الإرسال');
            this.isSubmitting = false;

            const submitBtn = document.getElementById('submitNote');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> إضافة ملاحظة';
                console.log('✅ تم تصحيح حالة الزر بنجاح');
            }

            this.showToast('تم إعادة تعيين حالة الإرسال، يمكنك المحاولة مرة أخرى', 'info');
        } catch (error) {
            console.error('❌ خطأ في إعادة التعيين القسرية:', error);
        }
    }

    /**
     * إضافة ملاحظة جديدة مباشرة للـ UI
     */
    addNoteToUI(noteData) {
        console.log('➕ إضافة ملاحظة للـ UI:', noteData);

        const notesList = document.getElementById('notesList');
        const emptyMessage = document.getElementById('emptyNotesMessage');

        if (!notesList) {
            console.error('❌ notesList element not found!');
            return;
        }

        // إخفاء رسالة "لا توجد ملاحظات"
        if (emptyMessage) {
            emptyMessage.classList.add('d-none');
        }

        // إضافة الملاحظة الجديدة لبداية المصفوفة
        this.currentNotes.unshift(noteData);

        // إنشاء HTML للملاحظة الجديدة
        const noteHTML = this.createNoteHTML(noteData);

        // إضافة الملاحظة في بداية القائمة
        if (notesList.innerHTML.trim() === '') {
            notesList.innerHTML = noteHTML;
                } else {
            notesList.insertAdjacentHTML('afterbegin', noteHTML);
        }

        // إضافة تأثير بصري للملاحظة الجديدة
        const newNoteElement = notesList.querySelector(`[data-note-id="${noteData.id}"]`);
        if (newNoteElement) {
            newNoteElement.classList.add('newly-added');
            // إزالة الكلاس بعد انتهاء الأنيميشن
            setTimeout(() => {
                newNoteElement.classList.remove('newly-added');
            }, 500);
        }

        console.log('✅ تم إضافة الملاحظة للـ UI بنجاح');
    }

    /**
     * إنشاء HTML لملاحظة واحدة
     */
    createNoteHTML(note) {
            const isImportant = note.is_important;
            const isPinned = note.is_pinned;
            const noteClasses = [
                'note-item',
                'mb-3',
                'p-3',
                'border',
                'rounded',
                'shadow-sm'
            ];

            if (isImportant) noteClasses.push('important');
            if (isPinned) noteClasses.push('pinned');

            const mentionedUsers = note.mentioned_users || [];
            const mentionsHTML = mentionedUsers.length > 0 ?
                `<div class="mt-2">
                    <small class="text-muted">
                        <i class="fas fa-at me-1"></i>
                        ذكر: ${mentionedUsers.map(u => `<span class="mention">${u.name}</span>`).join(', ')}
                    </small>
                </div>` : '';

        return `
                <div class="${noteClasses.join(' ')}" data-note-id="${note.id}">
                    <div class="d-flex align-items-start gap-3">
                        <!-- صورة المؤلف -->
                    <img src="${note.user.avatar || '/avatars/man.gif'}"
                             class="rounded-circle"
                             width="40" height="40"
                         alt="${note.user.name}"
                         onerror="this.src='/avatars/man.gif'">

                        <div class="flex-grow-1">
                            <!-- معلومات المؤلف والتاريخ -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center gap-2">
                                <strong>${note.user.name || 'مستخدم غير معروف'}</strong>
                                <span class="badge note-type-badge bg-${note.note_type_color || 'secondary'}">
                                    <i class="${note.note_type_icon || 'fas fa-sticky-note'}"></i>
                                    ${note.note_type_arabic || 'عام'}
                                    </span>
                                    ${isImportant ? '<i class="fas fa-star text-warning" title="مهم"></i>' : ''}
                                    ${isPinned ? '<i class="fas fa-thumbtack text-primary" title="مثبت"></i>' : ''}
                                </div>

                                <div class="d-flex align-items-center gap-2">
                                <small class="text-muted">${note.created_at_human || 'الآن'}</small>
                                    <div class="dropdown note-actions">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="projectNotes.editNote(${note.id})">
                                                    <i class="fas fa-edit me-2"></i>تحرير
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="projectNotes.toggleNotePin(${note.id})">
                                                    <i class="fas fa-thumbtack me-2"></i>
                                                    ${isPinned ? 'إلغاء التثبيت' : 'تثبيت'}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="projectNotes.toggleNoteImportant(${note.id})">
                                                    <i class="fas fa-star me-2"></i>
                                                    ${isImportant ? 'إلغاء الأهمية' : 'مهم'}
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" onclick="projectNotes.deleteNote(${note.id})">
                                                    <i class="fas fa-trash me-2"></i>حذف
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- محتوى الملاحظة -->
                        <div class="note-content">${note.formatted_content || note.content || ''}</div>

                            <!-- المستخدمين المذكورين -->
                            ${mentionsHTML}

                            <!-- معلومات إضافية -->
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                ${note.created_at_formatted || new Date().toLocaleString('ar-EG')}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
    }

        /**
     * تحميل الملاحظات
     */
    async loadNotes() {
        const notesList = document.getElementById('notesList');
        const loading = document.getElementById('notesLoading');
        const emptyMessage = document.getElementById('emptyNotesMessage');

        console.log('🔄 بدء تحميل الملاحظات...', {
            projectId: this.projectId,
            currentPage: this.currentPage,
            filters: this.currentFilters
        });

        try {
            // إظهار التحميل
            if (loading) loading.classList.remove('d-none');
            if (emptyMessage) emptyMessage.classList.add('d-none');

            const params = new URLSearchParams({
                page: this.currentPage,
                per_page: 20,
                ...this.currentFilters
            });

            const url = `/projects/${this.projectId}/notes?${params}`;
            console.log('🌐 Fetching URL:', url);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            console.log('📡 Response status:', response.status);
            console.log('📡 Response ok:', response.ok);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Response error:', errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            // التحقق من أن الاستجابة JSON صحيحة
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('❌ غير صحيح JSON response:', text);
                throw new Error('الخادم لم يرسل استجابة JSON صحيحة');
            }

            const data = await response.json();
            console.log('📄 Response data:', data);

            if (data.success) {
                this.currentNotes = data.notes.data || [];
                console.log('📝 Notes loaded:', this.currentNotes.length, 'notes');

                this.renderNotes();
                this.renderPagination(data.notes);

                // إخفاء التحميل
                if (loading) loading.classList.add('d-none');

                // عرض رسالة فارغة إذا لم توجد ملاحظات
                if (this.currentNotes.length === 0) {
                    if (emptyMessage) emptyMessage.classList.remove('d-none');
                    if (notesList) notesList.innerHTML = '';
                } else {
                    if (emptyMessage) emptyMessage.classList.add('d-none');
                }

                console.log('✅ تم تحميل الملاحظات بنجاح');

            } else {
                console.error('❌ Server error:', data.message);
                throw new Error(data.message || 'فشل في تحميل الملاحظات');
            }

        } catch (error) {
            console.error('❌ خطأ في تحميل الملاحظات:', error);
            if (loading) loading.classList.add('d-none');
            if (notesList) {
                notesList.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        حدث خطأ أثناء تحميل الملاحظات: ${error.message}
                        <button class="btn btn-sm btn-outline-danger ms-2" onclick="projectNotes.loadNotes()">
                            <i class="fas fa-refresh"></i> إعادة المحاولة
                        </button>
                    </div>
                `;
            }
        }
    }

    /**
     * عرض الملاحظات
     */
    renderNotes() {
        const notesList = document.getElementById('notesList');

        console.log('🎨 بدء عرض الملاحظات...', {
            notesCount: this.currentNotes.length,
            notesList: !!notesList
        });

        if (!notesList) {
            console.error('❌ notesList element not found!');
            return;
        }

        if (this.currentNotes.length === 0) {
            console.log('📝 لا توجد ملاحظات للعرض');
            notesList.innerHTML = '';
            return;
        }

        let notesHTML = '';

        this.currentNotes.forEach((note, index) => {
            console.log(`📝 معالجة الملاحظة ${index + 1}:`, note.id);
            notesHTML += this.createNoteHTML(note);
        });

        console.log('🎨 تم إنشاء HTML للملاحظات:', notesHTML.length, 'characters');
        notesList.innerHTML = notesHTML;
        console.log('✅ تم عرض الملاحظات بنجاح');
    }

    /**
     * إعادة تحميل الملاحظات والإحصائيات
     */
    async refreshNotes() {
        console.log('🔄 إعادة تحميل الملاحظات...');
        try {
            await this.loadNotes();
            await this.loadNotesStats();
            this.showToast('تم تحديث الملاحظات', 'success');
        } catch (error) {
            console.error('❌ خطأ في إعادة تحميل الملاحظات:', error);
            this.showToast('حدث خطأ في تحديث الملاحظات', 'error');
        }
    }

    /**
     * عرض الـ pagination
     */
    renderPagination(paginationData) {
        const container = document.getElementById('notesPagination');

        if (!paginationData || paginationData.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<nav><ul class="pagination">';

        // Previous page
        if (paginationData.current_page > 1) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="projectNotes.goToPage(${paginationData.current_page - 1})">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            `;
        }

        // Page numbers
        for (let i = 1; i <= paginationData.last_page; i++) {
            if (i === paginationData.current_page) {
                paginationHTML += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                paginationHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="projectNotes.goToPage(${i})">${i}</a>
                    </li>
                `;
            }
        }

        // Next page
        if (paginationData.current_page < paginationData.last_page) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="projectNotes.goToPage(${paginationData.current_page + 1})">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        paginationHTML += '</ul></nav>';
        container.innerHTML = paginationHTML;
    }

    /**
     * الانتقال لصفحة معينة
     */
    goToPage(page) {
        this.currentPage = page;
        this.loadNotes();
    }

        /**
     * تحميل إحصائيات الملاحظات
     */
    async loadNotesStats() {
        try {
            const response = await fetch(`/projects/${this.projectId}/notes/stats`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // التحقق من أن الاستجابة JSON صحيحة
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('غير صحيح JSON response في تحميل الإحصائيات:', text);
                return;
            }

            const data = await response.json();

            if (data.success) {
                const stats = data.stats;

                document.getElementById('notesCount').textContent = stats.total || 0;
                document.querySelector('#importantNotesCount span').textContent = stats.important || 0;
                document.querySelector('#pinnedNotesCount span').textContent = stats.pinned || 0;
            } else {
                console.error('فشل في تحميل الإحصائيات:', data.message);
            }
        } catch (error) {
            console.error('خطأ في تحميل الإحصائيات:', error);
        }
    }

    /**
     * تطبيق الفلاتر السريعة
     */
    applyQuickFilter(filter) {
        // تحديث الأزرار
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-light');
        });

        document.querySelector(`[data-filter="${filter}"]`).classList.add('btn-primary');
        document.querySelector(`[data-filter="${filter}"]`).classList.remove('btn-outline-light');

        // تطبيق الفلتر
        this.currentFilters.filter = filter;
        this.currentPage = 1;
        this.loadNotes();
    }

    /**
     * تبديل الفلاتر المتقدمة
     */
    toggleAdvancedFilters() {
        const filtersDiv = document.getElementById('advancedFilters');
        const btn = document.getElementById('toggleAdvancedFilters');

        filtersDiv.classList.toggle('d-none');

        if (filtersDiv.classList.contains('d-none')) {
            btn.innerHTML = '<i class="fas fa-filter"></i> فلاتر متقدمة';
        } else {
            btn.innerHTML = '<i class="fas fa-times"></i> إخفاء الفلاتر';
        }
    }

    /**
     * تطبيق الفلاتر المتقدمة
     */
    applyAdvancedFilters() {
        this.currentFilters.note_type = document.getElementById('filterNoteType').value;
        this.currentFilters.user_id = document.getElementById('filterNoteAuthor').value;

        const sort = document.getElementById('notesSort').value;
        this.currentFilters.sort = sort;

        this.currentPage = 1;
        this.loadNotes();
    }

    /**
     * تحرير ملاحظة
     */
    async editNote(noteId) {
        const note = this.currentNotes.find(n => n.id === noteId);
        if (!note) return;

        // ملء النموذج
        document.getElementById('editNoteId').value = noteId;
        document.getElementById('editNoteContent').value = note.content;
        document.getElementById('editNoteType').value = note.note_type;
        document.getElementById('editIsImportant').checked = note.is_important;
        document.getElementById('editIsPinned').checked = note.is_pinned;

        this.editingNoteId = noteId;

        // إظهار النافذة المنبثقة
        const modal = new bootstrap.Modal(document.getElementById('editNoteModal'));
        modal.show();
    }

    /**
     * حفظ التعديلات
     */
    async saveEditedNote() {
        if (!this.editingNoteId) return;

        const form = document.getElementById('editNoteForm');
        const formData = new FormData(form);
        const saveBtn = document.getElementById('saveEditedNote');
        const originalText = saveBtn.innerHTML;

        try {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';

            const response = await fetch(`/projects/${this.projectId}/notes/${this.editingNoteId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('تم تحديث الملاحظة بنجاح', 'success');

                // إغلاق النافذة وإعادة تحميل البيانات
                bootstrap.Modal.getInstance(document.getElementById('editNoteModal')).hide();
                this.loadNotes();
                this.loadNotesStats();

            } else {
                this.showToast(data.message || 'حدث خطأ أثناء تحديث الملاحظة', 'error');
            }

        } catch (error) {
            console.error('خطأ في تحديث الملاحظة:', error);
            this.showToast('حدث خطأ في الاتصال', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    /**
     * حذف ملاحظة
     */
    async deleteNote(noteId) {
        const confirmed = await this.confirmDialog(
            'تأكيد الحذف',
            'هل أنت متأكد من حذف هذه الملاحظة؟ لا يمكن التراجع عن هذا الإجراء.',
            'danger'
        );

        if (!confirmed) return;

        try {
            const response = await fetch(`/projects/${this.projectId}/notes/${noteId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('تم حذف الملاحظة بنجاح', 'success');
                this.loadNotes();
                this.loadNotesStats();
            } else {
                this.showToast(data.message || 'حدث خطأ أثناء حذف الملاحظة', 'error');
            }

        } catch (error) {
            console.error('خطأ في حذف الملاحظة:', error);
            this.showToast('حدث خطأ في الاتصال', 'error');
        }
    }

    /**
     * تبديل تثبيت الملاحظة
     */
    async toggleNotePin(noteId) {
        try {
            const response = await fetch(`/projects/${this.projectId}/notes/${noteId}/toggle-pin`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showToast(data.message, 'success');
                this.loadNotes();
                this.loadNotesStats();
            } else {
                this.showToast(data.message || 'حدث خطأ', 'error');
            }

        } catch (error) {
            console.error('خطأ في تثبيت الملاحظة:', error);
            this.showToast('حدث خطأ في الاتصال', 'error');
        }
    }

    /**
     * تبديل أهمية الملاحظة
     */
    async toggleNoteImportant(noteId) {
        try {
            const response = await fetch(`/projects/${this.projectId}/notes/${noteId}/toggle-important`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showToast(data.message, 'success');
                this.loadNotes();
                this.loadNotesStats();
            } else {
                this.showToast(data.message || 'حدث خطأ', 'error');
            }

        } catch (error) {
            console.error('خطأ في تغيير أهمية الملاحظة:', error);
            this.showToast('حدث خطأ في الاتصال', 'error');
        }
    }

    // ====== Debug & Recovery Methods ======

    /**
     * تشخيص حالة نظام الملاحظات
     */
    diagnoseNotesSystem() {
        console.log('🔍 تشخيص نظام الملاحظات:');
        console.log('📊 الحالة الحالية:', {
            projectId: this.projectId,
            isSubmitting: this.isSubmitting,
            currentNotesCount: this.currentNotes.length,
            projectUsersCount: this.projectUsers.length
        });

        const form = document.getElementById('addNoteForm');
        const submitBtn = document.getElementById('submitNote');
        const notesList = document.getElementById('notesList');

        console.log('🔍 حالة العناصر:', {
            formExists: !!form,
            submitBtnExists: !!submitBtn,
            submitBtnDisabled: submitBtn?.disabled,
            submitBtnText: submitBtn?.innerHTML,
            notesListExists: !!notesList
        });

        if (submitBtn) {
            console.log('🎯 تفاصيل زر الإرسال:', {
                disabled: submitBtn.disabled,
                innerHTML: submitBtn.innerHTML,
                classList: Array.from(submitBtn.classList),
                style: submitBtn.style.cssText
            });
        }

        return {
            system: 'notes',
            status: this.isSubmitting ? 'submitting' : 'ready',
            elements: {
                form: !!form,
                submitBtn: !!submitBtn,
                notesList: !!notesList
            }
        };
    }

    /**
     * إعادة تعيين كامل لنظام الملاحظات
     */
    fullSystemReset() {
        console.log('🔄 إعادة تعيين كامل لنظام الملاحظات...');

        try {
            // إعادة تعيين المتغيرات
            this.isSubmitting = false;
            this.editingNoteId = null;
            this.mentionsCursor = -1;

            // إعادة تعيين زر الإرسال
            this.resetSubmitButton();

            // إعادة تعيين النموذج
            this.resetAddNoteForm();

            // إخفاء dropdowns
            const mentionsDropdown = document.getElementById('mentionsDropdown');
            if (mentionsDropdown) {
                this.hideMentionsDropdown(mentionsDropdown);
            }

            console.log('✅ تم إعادة تعيين النظام بالكامل');
            this.showToast('تم إعادة تعيين نظام الملاحظات بنجاح', 'success');

            return true;
        } catch (error) {
            console.error('❌ خطأ في إعادة التعيين الكامل:', error);
            this.showToast('حدث خطأ في إعادة التعيين', 'error');
            return false;
        }
    }

    // ====== Helper Methods ======

    /**
     * عرض رسالة toast
     */
    showToast(message, type = 'info') {
        // استخدام toastr إذا كان متوفراً
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
            return;
        }

        // أو استخدام alert عادي
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };

        alert(`${icons[type] || 'ℹ️'} ${message}`);
    }

    /**
     * نافذة تأكيد
     */
    async confirmDialog(title, message, type = 'warning') {
        // استخدام SweetAlert2 إذا كان متوفراً
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                title: title,
                text: message,
                icon: type,
                showCancelButton: true,
                confirmButtonText: 'تأكيد',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: type === 'danger' ? '#dc3545' : '#0d6efd'
            });

            return result.isConfirmed;
        }

        // أو استخدام confirm عادي
        return confirm(`${title}\n\n${message}`);
    }

    /**
     * تأخير تنفيذ دالة (debounce)
     */
    debounce(func, delay) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(func, delay);
    }
}

// متغير عام للوصول للكلاس من أي مكان
let projectNotes;

// دوال مساعدة للتشخيص السريع من الـ console
window.debugNotes = function() {
    if (projectNotes) {
        return projectNotes.diagnoseNotesSystem();
    } else {
        console.error('❌ نظام الملاحظات غير مفعل');
        return null;
    }
};

window.resetNotesSystem = function() {
    if (projectNotes) {
        return projectNotes.fullSystemReset();
    } else {
        console.error('❌ نظام الملاحظات غير مفعل');
        return false;
    }
};

window.forceResetNotes = function() {
    if (projectNotes) {
        return projectNotes.forceResetSubmissionState();
    } else {
        console.error('❌ نظام الملاحظات غير مفعل');
        return false;
    }
};

// تفعيل النظام عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    const projectId = document.querySelector('[data-project-id]')?.dataset.projectId;

    if (projectId && document.getElementById('notesList')) {
        projectNotes = new ProjectNotesManager(projectId);

        // رسالة ترحيب مع إرشادات debug
        console.log('🎉 تم تفعيل نظام الملاحظات بنجاح!');
        console.log('🛠️ دوال التشخيص المتاحة:');
        console.log('   - debugNotes() - تشخيص حالة النظام');
        console.log('   - resetNotesSystem() - إعادة تعيين كامل');
        console.log('   - forceResetNotes() - إعادة تعيين قسرية للحالة');
        console.log('📧 خاصيات الملاحظات:');
        console.log('   - فلتر الأقسام: لتصفية عرض الملاحظات حسب القسم');
        console.log('   - @اسم_القسم: لإرسال إشعارات لكل أعضاء القسم');
        console.log('   - @اسم_المستخدم: لإرسال إشعار لمستخدم محدد');
        console.log('   ⚠️ الإشعارات تُرسل فقط عند استخدام @ (المنشن)');

        // إعداد toastr إذا كان متوفراً
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-left",
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut",
                "rtl": true
            };
        }
    }
});
