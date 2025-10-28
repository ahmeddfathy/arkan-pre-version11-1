@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/employee-errors.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4 employee-errors-page">
    <!-- Header -->
    <div class="error-detail-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <a href="{{ route('employee-errors.index') }}" class="btn btn-outline-secondary mb-3">
                    <i class="fas fa-arrow-right"></i> العودة للقائمة
                </a>
                <h2>تفاصيل الخطأ</h2>
            </div>

            <div class="btn-group-modern">
                @if(Auth::user()->hasRole(['admin', 'super-admin', 'hr', 'project_manager']) || $error->reported_by === Auth::id())
                <button onclick="openEditModal()" class="btn btn-outline-primary">
                    <i class="fas fa-edit"></i> تعديل
                </button>
                @endif

                @if(Auth::user()->hasRole(['admin', 'super-admin', 'hr']) || $error->reported_by === Auth::id())
                <button onclick="deleteError()" class="btn btn-outline-danger">
                    <i class="fas fa-trash"></i> حذف
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- معلومات الخطأ الرئيسية -->
        <div class="col-lg-8 mb-4">
            <div class="error-detail-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5>{{ $error->title }}</h5>
                        <div>
                            @if($error->error_type === 'critical')
                                <span class="badge badge-critical">جوهري</span>
                            @else
                                <span class="badge badge-normal">عادي</span>
                            @endif
                            <span class="badge badge-category">{{ $error->error_category_text }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="text-muted mb-2">وصف الخطأ</h6>
                    <p class="mb-4">{{ $error->description }}</p>

                    <hr>

                    <!-- معلومات المصدر -->
                    <h6 class="text-muted mb-3">معلومات المصدر</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-light rounded p-2 me-3">
                                    @if($error->errorable_type === 'App\Models\TaskUser')
                                        <i class="fas fa-tasks text-purple"></i>
                                    @elseif($error->errorable_type === 'App\Models\TemplateTaskUser')
                                        <i class="fas fa-file-alt text-indigo"></i>
                                    @elseif($error->errorable_type === 'App\Models\ProjectServiceUser')
                                        <i class="fas fa-project-diagram text-success"></i>
                                    @endif
                                </div>
                                <div>
                                    <small class="text-muted">نوع المصدر</small>
                                    <p class="mb-0 fw-bold">
                                        @if($error->errorable_type === 'App\Models\TaskUser')
                                            مهمة عادية
                                        @elseif($error->errorable_type === 'App\Models\TemplateTaskUser')
                                            مهمة قالب
                                        @elseif($error->errorable_type === 'App\Models\ProjectServiceUser')
                                            مشروع
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        @if($error->errorable)
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-light rounded p-2 me-3">
                                    <i class="fas fa-link"></i>
                                </div>
                                <div>
                                    <small class="text-muted">ارتباط</small>
                                    <p class="mb-0 fw-bold">
                                        @if($error->errorable_type === 'App\Models\TaskUser')
                                            <a href="{{ route('tasks.show', $error->errorable->task_id) }}" class="text-decoration-none">
                                                عرض المهمة
                                            </a>
                                        @elseif($error->errorable_type === 'App\Models\ProjectServiceUser')
                                            <a href="{{ route('projects.show', $error->errorable->project_id) }}" class="text-decoration-none">
                                                عرض المشروع
                                            </a>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- معلومات جانبية -->
        <div class="col-lg-4 mb-4">
            <!-- معلومات الموظف -->
            <div class="user-info-card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">الموظف المخطئ</h6>
                    <div class="d-flex align-items-center mb-3">
                        <img src="{{ $error->user->profile_photo_url ?? asset('avatars/man.gif') }}"
                             class="rounded-circle me-3"
                             width="50" height="50"
                             alt="{{ $error->user->name }}">
                        <div>
                            <h6 class="mb-0">{{ $error->user->name }}</h6>
                            <small class="text-muted">{{ $error->user->email }}</small>
                        </div>
                    </div>

                    @if($error->user->department)
                    <div class="mb-2">
                        <small class="text-muted">القسم</small>
                        <p class="mb-0">{{ $error->user->department->name ?? 'غير محدد' }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- مسجل الخطأ -->
            <div class="user-info-card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">مسجل الخطأ</h6>
                    <div class="d-flex align-items-center">
                        <img src="{{ $error->reportedBy->profile_photo_url ?? asset('avatars/man.gif') }}"
                             class="rounded-circle me-3"
                             width="50" height="50"
                             alt="{{ $error->reportedBy->name }}">
                        <div>
                            <h6 class="mb-0">{{ $error->reportedBy->name }}</h6>
                            <small class="text-muted">{{ $error->reportedBy->email }}</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- معلومات زمنية -->
            <div class="user-info-card">
                <div class="card-body">
                    <h6 class="card-title mb-3">معلومات زمنية</h6>

                    <div class="mb-3">
                        <small class="text-muted">تاريخ التسجيل</small>
                        <p class="mb-0">{{ $error->created_at->format('Y-m-d h:i A') }}</p>
                        <small class="text-muted">{{ $error->created_at->diffForHumans() }}</small>
                    </div>

                    @if($error->updated_at != $error->created_at)
                    <div>
                        <small class="text-muted">آخر تعديل</small>
                        <p class="mb-0">{{ $error->updated_at->format('Y-m-d h:i A') }}</p>
                        <small class="text-muted">{{ $error->updated_at->diffForHumans() }}</small>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الموظف -->
    <div class="stats-grid">
        <h5 class="mb-4">إحصائيات أخطاء الموظف</h5>
        <div class="row text-center">
            <div class="col-md-3">
                <div class="stat-item">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    <h4>{{ $error->user->errors_count }}</h4>
                    <small>إجمالي الأخطاء</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <i class="fas fa-times fa-2x text-danger"></i>
                    <h4 class="text-danger">{{ $error->user->critical_errors_count }}</h4>
                    <small>أخطاء جوهرية</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <i class="fas fa-exclamation-circle fa-2x text-warning"></i>
                    <h4 class="text-warning">{{ $error->user->normal_errors_count }}</h4>
                    <small>أخطاء عادية</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-item">
                    <i class="fas fa-chart-line fa-2x text-primary"></i>
                    <h4>{{ $error->user->errors_count > 0 ? round(($error->user->critical_errors_count / $error->user->errors_count) * 100, 1) : 0 }}%</h4>
                    <small>نسبة الجوهرية</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit Error -->
<div class="modal fade modal-modern" id="editErrorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل الخطأ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editErrorForm" onsubmit="submitEditError(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">عنوان الخطأ</label>
                        <input type="text" name="title" id="edit_title" value="{{ $error->title }}" required class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">وصف الخطأ</label>
                        <textarea name="description" id="edit_description" required rows="4" class="form-control">{{ $error->description }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">تصنيف الخطأ</label>
                        <select name="error_category" id="edit_category" required class="form-select">
                            <option value="quality" {{ $error->error_category == 'quality' ? 'selected' : '' }}>جودة</option>
                            <option value="deadline" {{ $error->error_category == 'deadline' ? 'selected' : '' }}>موعد نهائي</option>
                            <option value="communication" {{ $error->error_category == 'communication' ? 'selected' : '' }}>تواصل</option>
                            <option value="technical" {{ $error->error_category == 'technical' ? 'selected' : '' }}>فني</option>
                            <option value="procedural" {{ $error->error_category == 'procedural' ? 'selected' : '' }}>إجرائي</option>
                            <option value="other" {{ $error->error_category == 'other' ? 'selected' : '' }}>أخرى</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">نوع الخطأ</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="error_type" id="edit_type_normal" value="normal" {{ $error->error_type == 'normal' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="edit_type_normal">عادي</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="error_type" id="edit_type_critical" value="critical" {{ $error->error_type == 'critical' ? 'checked' : '' }} required>
                                    <label class="form-check-label text-danger" for="edit_type_critical">جوهري</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditModal() {
        new bootstrap.Modal(document.getElementById('editErrorModal')).show();
    }

    function submitEditError(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        fetch(`/employee-errors/{{ $error->id }}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.success('تم تحديث الخطأ بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                Toast.error(data.message || 'حدث خطأ أثناء التحديث');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.error('حدث خطأ أثناء التحديث');
        });
    }

    function deleteError() {
        if (!confirm('هل أنت متأكد من حذف هذا الخطأ؟')) {
            return;
        }

        fetch(`/employee-errors/{{ $error->id }}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Toast.success('تم حذف الخطأ بنجاح');
                setTimeout(() => window.location.href = '/employee-errors', 1000);
            } else {
                Toast.error(data.message || 'حدث خطأ أثناء الحذف');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.error('حدث خطأ أثناء الحذف');
        });
    }
</script>
@endpush
