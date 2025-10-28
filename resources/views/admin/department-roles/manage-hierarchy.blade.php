@extends('layouts.app')

@section('title', 'إدارة ترتيب الأدوار')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-12">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-text">
                        <h1 class="page-title">
                            <i class="fas fa-sort-amount-up"></i>
                            إدارة ترتيب الأدوار
                        </h1>
                        <p class="page-subtitle">تحديد المستويات الهرمية للأدوار المختلفة في النظام</p>
                    </div>
                    <div class="header-actions">
                        <a href="{{ route('department-roles.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i>
                            العودة
                        </a>
                        <button type="button" class="btn btn-success" id="saveHierarchyBtn">
                            <i class="fas fa-save"></i>
                            <span id="save-text">حفظ الترتيب</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="help-card">
                <div class="help-header">
                    <i class="fas fa-info-circle"></i>
                    <span>دليل المستويات الهرمية</span>
                </div>
                <div class="help-content">
                    <div class="simple-help">
                        <div class="help-text">
                            <i class="fas fa-arrow-up text-success"></i>
                            <strong>كلما زاد الرقم، كلما ارتفع مستوى الدور في الهيكل التنظيمي</strong>
                        </div>
                        <div class="help-example">
                            <span class="example-badge">1</span> → أقل مستوى
                            <span class="mx-3">•••</span>
                            <span class="example-badge high">10</span> → أعلى مستوى
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="form-card">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="form-title">
                        <h3>إدارة الترتيب الهرمي</h3>
                        <p>قم بتحديد المستوى الهرمي لكل دور في النظام</p>
                    </div>
                </div>

                <div class="form-body">


                    <form id="hierarchyForm" data-existing-count="{{ $rolesWithHierarchy->count() ?? 0 }}">
                        @csrf

                                                <!-- الأدوار المسجلة بالفعل -->
                        @if($rolesWithHierarchy->count() > 0)
                        <div class="form-section">
                            <label class="form-label-modern">
                                <i class="fas fa-list-ol"></i>
                                الأدوار المسجلة
                                <span class="label-hint">يمكنك تعديل المستوى الهرمي أو الوصف لكل دور</span>
                            </label>

                            <div class="roles-hierarchy-grid" id="existingRoles">
                                @foreach($rolesWithHierarchy as $roleHierarchy)
                                <div class="role-hierarchy-card existing-role" data-role-id="{{ $roleHierarchy->role_id }}">
                                    <div class="role-header">
                                        <div class="role-icon level-{{ min($roleHierarchy->hierarchy_level, 4) }}">
                                            @if($roleHierarchy->hierarchy_level >= 8)
                                                <i class="fas fa-crown"></i>
                                            @elseif($roleHierarchy->hierarchy_level >= 6)
                                                <i class="fas fa-star"></i>
                                            @elseif($roleHierarchy->hierarchy_level >= 4)
                                                <i class="fas fa-shield-alt"></i>
                                            @elseif($roleHierarchy->hierarchy_level >= 2)
                                                <i class="fas fa-user-tie"></i>
                                            @else
                                                <i class="fas fa-user"></i>
                                            @endif
                                        </div>
                                        <div class="role-info">
                                            <h4>{{ $roleHierarchy->role->name }}</h4>
                                            <p>دور {{ $roleHierarchy->role->name }}</p>
                                        </div>
                                        <button type="button" class="btn-remove remove-role" title="حذف من الترتيب">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>

                                    <div class="hierarchy-inputs">
                                        <input type="hidden" name="roles[{{ $loop->index }}][role_id]" value="{{ $roleHierarchy->role_id }}">

                                        <div class="input-group">
                                            <label class="input-label">المستوى الهرمي</label>
                                            <input type="number"
                                                   class="form-control-modern hierarchy-input"
                                                   name="roles[{{ $loop->index }}][hierarchy_level]"
                                                   value="{{ $roleHierarchy->hierarchy_level }}"
                                                   min="1" max="100" required>
                                        </div>

                                        <div class="input-group">
                                            <label class="input-label">الوصف (اختياري)</label>
                                            <input type="text"
                                                   class="form-control-modern"
                                                   name="roles[{{ $loop->index }}][description]"
                                                   value="{{ $roleHierarchy->description }}"
                                                   placeholder="مثل: مدير القسم، قائد الفريق">
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                                                <!-- الأدوار غير المسجلة -->
                        @if($rolesWithoutHierarchy->count() > 0)
                        <div class="form-section">
                            <label class="form-label-modern">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                                أدوار بدون ترتيب هرمي
                                <span class="label-hint">انقر على الزائد لتوسيع الكارد وإدخال المستوى الهرمي</span>
                            </label>

                                                        <div class="unassigned-roles-grid">
                                @foreach($rolesWithoutHierarchy as $role)
                                <div class="unassigned-role-card" data-role-id="{{ $role->id }}">
                                    <div class="role-header-unassigned">
                                        <div class="role-icon-unassigned">
                                            <i class="fas fa-user-tag"></i>
                                        </div>
                                        <div class="role-details">
                                            <h5>{{ $role->name }}</h5>
                                            <p>دور غير مُصنف</p>
                                        </div>
                                        <button type="button"
                                                class="btn-add-role expand-role-btn"
                                                data-role-id="{{ $role->id }}"
                                                data-role-name="{{ $role->name }}">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>

                                    <!-- منطقة الإدخال المخفية -->
                                    <div class="role-input-area" style="display: none;">
                                        <div class="hierarchy-inputs">
                                            <div class="input-group">
                                                <label class="input-label">المستوى الهرمي</label>
                                                <input type="number"
                                                       class="form-control-modern hierarchy-input-new"
                                                       value="5"
                                                       min="1" max="100" required>
                                            </div>

                                            <div class="input-group">
                                                <label class="input-label">الوصف (اختياري)</label>
                                                <input type="text"
                                                       class="form-control-modern description-input-new"
                                                       placeholder="مثل: مدير القسم، قائد الفريق">
                                            </div>
                                        </div>

                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-success btn-sm confirm-add-role">
                                                <i class="fas fa-check"></i> إضافة
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm cancel-add-role">
                                                <i class="fas fa-times"></i> إلغاء
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- منطقة الأدوار الجديدة -->
                        <div id="newRolesContainer" class="form-section" style="display: none;">
                            <label class="form-label-modern">
                                <i class="fas fa-plus-circle text-success"></i>
                                الأدوار المضافة حديثاً
                                <span class="label-hint">الأدوار التي تم إضافتها في هذه الجلسة</span>
                            </label>

                            <div class="roles-hierarchy-grid" id="newRolesTable">
                                <!-- سيتم إضافة الأدوار الجديدة هنا بواسطة JavaScript -->
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/department-roles.css') }}">
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let newRoleIndex = parseInt($('#hierarchyForm').data('existing-count')) || 0;

        // توسيع الكارد عند الضغط على الزائد أو الكارد نفسه
    $(document).on('click', '.expand-role-btn, .role-header-unassigned', function(e) {
        // منع تشغيل الحدث مرتين إذا تم الضغط على الزر داخل header
        if ($(e.target).closest('.expand-role-btn').length && !$(this).hasClass('expand-role-btn')) {
            return;
        }
        const card = $(this).closest('.unassigned-role-card');
        const inputArea = card.find('.role-input-area');
        const isExpanded = card.hasClass('expanded');

        if (!isExpanded) {
            // فتح الكارد
            card.addClass('expanded');
            $(this).addClass('expanded');
            $(this).find('i').removeClass('fa-plus').addClass('fa-times');
            inputArea.slideDown(300);

            // التركيز على أول input
            setTimeout(() => {
                card.find('.hierarchy-input-new').focus();
            }, 350);
        } else {
            // إغلاق الكارد
            closeExpandedCard(card);
        }
    });

    // إغلاق الكارد
    function closeExpandedCard(card) {
        const inputArea = card.find('.role-input-area');
        const btn = card.find('.expand-role-btn');

        card.removeClass('expanded');
        btn.removeClass('expanded');
        btn.find('i').removeClass('fa-times').addClass('fa-plus');
        inputArea.slideUp(300);

        // مسح القيم
        card.find('.hierarchy-input-new').val(5);
        card.find('.description-input-new').val('');
    }

    // إلغاء إضافة الدور
    $(document).on('click', '.cancel-add-role', function() {
        const card = $(this).closest('.unassigned-role-card');
        closeExpandedCard(card);
    });

    // تأكيد إضافة الدور
    $(document).on('click', '.confirm-add-role', function() {
        const card = $(this).closest('.unassigned-role-card');
        const roleId = card.data('role-id');
        const roleName = card.find('.role-details h5').text();
        const hierarchyLevel = card.find('.hierarchy-input-new').val();
        const description = card.find('.description-input-new').val();

        // التحقق من صحة البيانات
        if (!hierarchyLevel || hierarchyLevel < 1 || hierarchyLevel > 100) {
            alert('يرجى إدخال مستوى هرمي صحيح (1-100)');
            card.find('.hierarchy-input-new').focus();
            return;
        }

        // إضافة الدور إلى القائمة الجديدة
        addNewRoleRowWithData(roleId, roleName, hierarchyLevel, description);

        // إخفاء الكارد من قائمة الأدوار غير المسجلة
        card.fadeOut(300, function() {
            $(this).remove();
        });

        // إظهار منطقة الأدوار الجديدة
        $('#newRolesContainer').show().addClass('fade-in');
    });

            // إضافة صف جديد للدور مع البيانات
    function addNewRoleRowWithData(roleId, roleName, hierarchyLevel, description) {
        // تحديد الأيقونة بناءً على المستوى الهرمي
        let iconClass = 'fas fa-user';
        if (hierarchyLevel >= 8) {
            iconClass = 'fas fa-crown';
        } else if (hierarchyLevel >= 6) {
            iconClass = 'fas fa-star';
        } else if (hierarchyLevel >= 4) {
            iconClass = 'fas fa-shield-alt';
        } else if (hierarchyLevel >= 2) {
            iconClass = 'fas fa-user-tie';
        }

        const cardHtml = `
            <div class="role-hierarchy-card new-role" data-role-id="${roleId}">
                <div class="role-header">
                    <div class="role-icon level-${Math.min(parseInt(hierarchyLevel), 4)}">
                        <i class="${iconClass}"></i>
                    </div>
                    <div class="role-info">
                        <h4>${roleName}</h4>
                        <p>دور جديد - مستوى ${hierarchyLevel}</p>
                    </div>
                    <button type="button" class="btn-remove remove-new-role" title="حذف">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="hierarchy-inputs">
                    <input type="hidden" name="roles[${newRoleIndex}][role_id]" value="${roleId}">

                    <div class="input-group">
                        <label class="input-label">المستوى الهرمي</label>
                        <input type="number"
                               class="form-control-modern hierarchy-input"
                               name="roles[${newRoleIndex}][hierarchy_level]"
                               value="${hierarchyLevel}"
                               min="1" max="100" required>
                    </div>

                    <div class="input-group">
                        <label class="input-label">الوصف (اختياري)</label>
                        <input type="text"
                               class="form-control-modern"
                               name="roles[${newRoleIndex}][description]"
                               value="${description}"
                               placeholder="مثل: مدير القسم، قائد الفريق">
                    </div>
                </div>
            </div>
        `;

        $('#newRolesTable').append(cardHtml);

        // إضافة تأثير fade-in
        const newCard = $('#newRolesTable').children().last();
        newCard.addClass('fade-in');

        newRoleIndex++;
    }

        // حذف دور من الجدول الجديد
    $(document).on('click', '.remove-new-role', function() {
        const card = $(this).closest('.role-hierarchy-card');
        const roleId = card.data('role-id');

        // إزالة الكارد
        card.fadeOut(300, function() {
            $(this).remove();

            // إخفاء منطقة الأدوار الجديدة إذا كانت فارغة
            if ($('#newRolesTable .role-hierarchy-card').length === 0) {
                $('#newRolesContainer').hide();
            }
        });
    });

    // حذف دور موجود
    $(document).on('click', '.remove-role', function() {
        if (confirm('هل أنت متأكد من حذف هذا الدور من الترتيب الهرمي؟')) {
            $(this).closest('.role-hierarchy-card').fadeOut(300, function() {
                $(this).remove();
            });
        }
    });

    // حفظ الترتيب
    $('#saveHierarchyBtn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();

        // تعطيل الزر وإظهار تحميل
        btn.prop('disabled', true);
        $('#save-text').html('جاري الحفظ...');
        btn.find('i').removeClass('fa-save').addClass('fa-spinner fa-spin');

        // جمع البيانات
        const formData = new FormData($('#hierarchyForm')[0]);

        // إرسال البيانات
        $.ajax({
            url: '{{ route("department-roles.save-hierarchy") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحفظ بنجاح',
                        text: response.message,
                        confirmButtonText: 'موافق'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: response.message || 'حدث خطأ غير متوقع',
                        confirmButtonText: 'موافق'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'حدث خطأ أثناء الحفظ';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('\n');
                }

                Swal.fire({
                    icon: 'error',
                    title: 'فشل في الحفظ',
                    text: errorMessage,
                    confirmButtonText: 'موافق'
                });
            },
            complete: function() {
                // إعادة تفعيل الزر
                btn.prop('disabled', false);
                $('#save-text').html('حفظ الترتيب');
                btn.find('i').removeClass('fa-spinner fa-spin').addClass('fa-save');
            }
        });
    });

    // تحديث الأيقونة عند تغيير المستوى الهرمي
    function updateRoleIcon(hierarchyInput) {
        const level = parseInt(hierarchyInput.val());
        const card = hierarchyInput.closest('.role-hierarchy-card');
        const roleIcon = card.find('.role-icon');
        const iconElement = roleIcon.find('i');

        // تحديد الأيقونة والمستوى
        let iconClass = 'fas fa-user';
        let levelClass = 'level-1';

        if (level >= 8) {
            iconClass = 'fas fa-crown';
            levelClass = 'level-4';
        } else if (level >= 6) {
            iconClass = 'fas fa-star';
            levelClass = 'level-3';
        } else if (level >= 4) {
            iconClass = 'fas fa-shield-alt';
            levelClass = 'level-2';
        } else if (level >= 2) {
            iconClass = 'fas fa-user-tie';
            levelClass = 'level-1';
        }

        // تحديث الأيقونة
        iconElement.attr('class', iconClass);

        // تحديث لون البادج
        roleIcon.removeClass('level-1 level-2 level-3 level-4').addClass(levelClass);
    }

    // التحقق من التداخل في المستويات وتحديث الأيقونة
    $(document).on('input', '.hierarchy-input', function() {
        const currentValue = parseInt($(this).val());
        const currentCard = $(this).closest('.role-hierarchy-card');
        let duplicateFound = false;

        // التحقق من التداخل
        $('.hierarchy-input').each(function() {
            const otherCard = $(this).closest('.role-hierarchy-card');
            const otherValue = parseInt($(this).val());

            if (currentCard[0] !== otherCard[0] && currentValue === otherValue && !isNaN(currentValue)) {
                duplicateFound = true;
                return false;
            }
        });

        // عرض رسالة الخطأ إذا وجد تداخل
        if (duplicateFound) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">هذا المستوى مستخدم بالفعل</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }

        // تحديث الأيقونة بناءً على المستوى الجديد
        if (!isNaN(currentValue) && currentValue >= 1 && currentValue <= 100) {
            updateRoleIcon($(this));
        }
    });
});
</script>
@endpush
