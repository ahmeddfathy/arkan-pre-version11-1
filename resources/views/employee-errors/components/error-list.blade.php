{{--
    مكون لعرض قائمة الأخطاء لمهمة أو مشروع معين

    الاستخدام:
    @include('employee-errors.components.error-list', [
        'errorable' => $taskUser,  // أو $templateTaskUser أو $projectServiceUser
        'canReport' => $canReportErrors  // صلاحية تسجيل أخطاء
    ])
--}}

<link rel="stylesheet" href="{{ asset('css/employee-errors.css') }}">

<div class="error-list-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>
            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
            أخطاء الموظف
            @if($errorable->errors_count > 0)
                <span class="badge {{ $errorable->critical_errors_count > 0 ? 'badge-critical' : 'badge-normal' }} ms-2">
                    {{ $errorable->errors_count }}
                </span>
            @endif
        </h5>

        @if(isset($canReport) && $canReport)
            <button onclick="openErrorModal()" class="btn btn-sm btn-danger">
                <i class="fas fa-plus"></i>
                تسجيل خطأ
            </button>
        @endif
    </div>

    <!-- قائمة الأخطاء -->
    <div class="card-body" id="errors-list-{{ $errorable->id }}">
        @forelse($errorable->errors as $error)
            <div class="error-list-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <!-- العنوان والشارات -->
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h6 class="mb-0">{{ $error->title }}</h6>

                            @if($error->error_type === 'critical')
                                <span class="badge badge-critical">جوهري</span>
                            @else
                                <span class="badge badge-normal">عادي</span>
                            @endif

                            <span class="badge badge-category">{{ $error->error_category_text }}</span>
                        </div>

                        <!-- الوصف -->
                        <p class="text-muted small mb-2">{{ Str::limit($error->description, 100) }}</p>

                        <!-- المعلومات -->
                        <div class="d-flex gap-3 small text-muted">
                            <span><i class="fas fa-user"></i> سجله: {{ $error->reportedBy->name }}</span>
                            <span><i class="fas fa-clock"></i> {{ $error->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    <!-- رابط التفاصيل -->
                    <div>
                        <a href="{{ route('employee-errors.show', $error->id) }}" class="btn btn-sm btn-outline-primary">
                            التفاصيل
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <i class="fas fa-check-circle fa-3x"></i>
                <p>لا توجد أخطاء مسجلة</p>
            </div>
        @endforelse
    </div>
</div>

@if(isset($canReport) && $canReport)
<!-- Modal لتسجيل خطأ جديد -->
<div class="modal fade modal-modern" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تسجيل خطأ جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="errorForm" onsubmit="submitError(event)">
                <div class="modal-body">
                    <!-- عنوان الخطأ -->
                    <div class="mb-3">
                        <label class="form-label">عنوان الخطأ <span class="text-danger">*</span></label>
                        <input type="text" name="title" required class="form-control" placeholder="مثال: تأخر في التسليم">
                    </div>

                    <!-- وصف الخطأ -->
                    <div class="mb-3">
                        <label class="form-label">وصف الخطأ <span class="text-danger">*</span></label>
                        <textarea name="description" required rows="4" class="form-control" placeholder="وصف تفصيلي للخطأ..."></textarea>
                    </div>

                    <!-- تصنيف الخطأ -->
                    <div class="mb-3">
                        <label class="form-label">تصنيف الخطأ <span class="text-danger">*</span></label>
                        <select name="error_category" required class="form-select">
                            <option value="">اختر التصنيف</option>
                            <option value="quality">جودة</option>
                            <option value="deadline">موعد نهائي</option>
                            <option value="communication">تواصل</option>
                            <option value="technical">فني</option>
                            <option value="procedural">إجرائي</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>

                    <!-- نوع الخطأ -->
                    <div class="mb-3">
                        <label class="form-label">نوع الخطأ <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-6">
                                <div class="error-type-card">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="error_type" value="normal" required id="error_type_normal">
                                        <label class="form-check-label" for="error_type_normal">
                                            <strong>عادي</strong>
                                            <p>خطأ بسيط قابل للتصحيح</p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="error-type-card critical-type">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="error_type" value="critical" required id="error_type_critical">
                                        <label class="form-check-label" for="error_type_critical">
                                            <strong class="text-danger">جوهري</strong>
                                            <p>خطأ خطير يؤثر على العمل</p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تسجيل الخطأ</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openErrorModal() {
        new bootstrap.Modal(document.getElementById('errorModal')).show();
    }

    function submitError(event) {
        event.preventDefault();

        const form = event.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // تحديد نوع الـ errorable
        const errorableType = '{{ get_class($errorable) }}';
        const errorableId = '{{ $errorable->id }}';

        let url = '';
        if (errorableType === 'App\\Models\\TaskUser') {
            url = `/employee-errors/task/${errorableId}`;
        } else if (errorableType === 'App\\Models\\TemplateTaskUser') {
            url = `/employee-errors/template-task/${errorableId}`;
        } else if (errorableType === 'App\\Models\\ProjectServiceUser') {
            url = `/employee-errors/project/${errorableId}`;
        }

        fetch(url, {
            method: 'POST',
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
                Toast.success('تم تسجيل الخطأ بنجاح');
                bootstrap.Modal.getInstance(document.getElementById('errorModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                Toast.error(data.message || 'حدث خطأ أثناء التسجيل');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Toast.error('حدث خطأ أثناء التسجيل');
        });
    }
</script>
@endpush
@endif
