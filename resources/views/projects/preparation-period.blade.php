@extends('layouts.app')

@section('title', 'المشاريع في فترة التحضير')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-preparation-period.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="projects-modern-container">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="preparation-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-2">
                            <i class="fas fa-clock me-3"></i>
                            المشاريع في فترة التحضير
                        </h2>
                        <p class="mb-0 opacity-75">
                            عرض جميع المشاريع التي تم تفعيل فترة التحضير لها مع تتبع طلبات تأكيد المرفقات مع العملاء
                        </p>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-success me-3" data-bs-toggle="modal" data-bs-target="#addPreparationModal">
                            <i class="fas fa-plus-circle me-2"></i>
                            إضافة مشروع لفترة التحضير
                        </button>
                        <div class="badge bg-white text-primary p-3" style="font-size: 1.1rem;">
                            <i class="fas fa-project-diagram me-2"></i>
                            {{ $projects->total() }} مشروع
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4" style="border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
                <div class="card-body p-3">
                    @if(request('month'))
                        <div class="alert alert-info mb-3 d-flex align-items-center justify-content-between">
                            <span>
                                <i class="fas fa-filter me-2"></i>
                                <strong>فلتر نشط:</strong> عرض مشاريع
                                @php
                                    $monthYear = request('month'); // Format: YYYY-MM
                                    $date = \Carbon\Carbon::createFromFormat('Y-m', $monthYear);
                                    $monthNames = [
                                        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                                        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                                        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
                                    ];
                                    $monthName = $monthNames[$date->month];
                                    $year = $date->year;
                                @endphp
                                <strong>{{ $monthName }} {{ $year }}</strong>
                            </span>
                        </div>
                    @endif
                    <form method="GET" action="{{ route('projects.preparation-period') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>
                                الشهر والسنة
                            </label>
                            <input type="month"
                                   name="month"
                                   class="form-control"
                                   value="{{ request('month') }}"
                                   onchange="this.form.submit()">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-filter me-1"></i>
                                حالة فترة التحضير
                            </label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">الكل</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                                    جارية حالياً
                                </option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                    لم تبدأ بعد
                                </option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>
                                    انتهت
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-sort-amount-down me-1"></i>
                                ترتيب حسب
                            </label>
                            <select name="sort" class="form-select" onchange="this.form.submit()">
                                <option value="start_date_desc" {{ request('sort', 'start_date_desc') == 'start_date_desc' ? 'selected' : '' }}>
                                    الأحدث بداية
                                </option>
                                <option value="start_date_asc" {{ request('sort') == 'start_date_asc' ? 'selected' : '' }}>
                                    الأقدم بداية
                                </option>
                                <option value="end_date_asc" {{ request('sort') == 'end_date_asc' ? 'selected' : '' }}>
                                    الأقرب انتهاءً
                                </option>
                                <option value="days_asc" {{ request('sort') == 'days_asc' ? 'selected' : '' }}>
                                    الأقل أياماً
                                </option>
                                <option value="days_desc" {{ request('sort') == 'days_desc' ? 'selected' : '' }}>
                                    الأكثر أياماً
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-search me-1"></i>
                                بحث
                            </label>
                            <input type="text" name="search" class="form-control" placeholder="ابحث عن مشروع..." value="{{ request('search') }}">
                        </div>

                        <div class="col-md-3">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>
                                    بحث
                                </button>
                                @if(request()->hasAny(['status', 'sort', 'search', 'month']))
                                    <a href="{{ route('projects.preparation-period') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo me-1"></i>
                                        إعادة تعيين
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Projects Table -->
            @if($projects->count() > 0)
                <div class="projects-table-container">
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>
                                    <i class="fas fa-project-diagram me-2"></i>
                                    اسم المشروع
                                </th>
                                <th>
                                    <i class="fas fa-building me-2"></i>
                                    العميل
                                </th>
                                <th>
                                    <i class="fas fa-calendar-day me-2"></i>
                                    بداية التحضير
                                </th>
                                <th>
                                    <i class="fas fa-calendar-check me-2"></i>
                                    نهاية التحضير
                                </th>
                                <th>
                                    <i class="fas fa-hashtag me-2"></i>
                                    عدد الأيام
                                </th>
                                <th>
                                    <i class="fas fa-hourglass-half me-2"></i>
                                    الأيام المتبقية
                                </th>
                                <th>
                                    <i class="fas fa-tasks me-2"></i>
                                    الحالة
                                </th>
                                <th>
                                    <i class="fas fa-file-check me-2"></i>
                                    طلبات التأكيد
                                </th>
                                <th>
                                    <i class="fas fa-cogs me-2"></i>
                                    الإجراءات
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $project)
                                <tr>
                                    <td>
                                        <div>
                                            <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-dark fw-bold">
                                                {{ $project->name }}
                                            </a>
                                            @if($project->is_urgent)
                                                <i class="fas fa-exclamation-triangle text-danger ms-2" title="مشروع مستعجل"></i>
                                            @endif
                                            @if($project->code)
                                                <br>
                                                <small class="badge bg-light text-dark mt-1">
                                                    <i class="fas fa-qrcode me-1"></i>
                                                    {{ $project->code }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-user-tie text-primary me-1"></i>
                                        {{ $project->client?->name ?? 'غير محدد' }}
                                    </td>
                                    <td>
                                        {{ $project->preparation_start_date ? $project->preparation_start_date->format('Y-m-d H:i') : 'غير محدد' }}
                                    </td>
                                    <td>
                                        {{ $project->preparation_end_date ? $project->preparation_end_date->format('Y-m-d H:i') : 'غير محدد' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $project->preparation_days }} يوم</span>
                                    </td>
                                    <td class="text-center">
                                        @if($project->isInPreparationPeriod())
                                            <span class="badge bg-warning text-dark">
                                                {{ $project->remaining_preparation_days }} يوم
                                            </span>
                                            <div class="preparation-progress mt-2">
                                                <div class="preparation-progress-fill" style="width: {{ $project->preparation_progress_percentage }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ $project->preparation_progress_percentage }}%</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($project->preparation_status === 'active')
                                            <div class="preparation-badge active">
                                                <i class="fas fa-spinner fa-pulse"></i>
                                                جارية
                                            </div>
                                        @elseif($project->preparation_status === 'pending')
                                            <div class="preparation-badge pending">
                                                <i class="fas fa-clock"></i>
                                                لم تبدأ
                                            </div>
                                        @elseif($project->preparation_status === 'completed')
                                            <div class="preparation-badge completed">
                                                <i class="fas fa-check-circle"></i>
                                                انتهت
                                            </div>
                                        @endif
                                    </td>
                                    <td class="confirmations-cell text-center">
                                        @php
                                            $totalConfirmations = $project->total_attachment_confirmations;
                                            $duringPreparation = $project->attachment_confirmations_during_preparation;
                                            $afterPreparation = $project->attachment_confirmations_after_preparation;
                                        @endphp

                                        @if($totalConfirmations > 0)
                                            <div class="d-flex flex-column">
                                                <a href="{{ route('attachment-confirmations.index', ['project_id' => $project->id]) }}"
                                                   class="badge bg-primary text-white text-decoration-none"
                                                   style="font-size: 0.9rem;"
                                                   title="عرض جميع طلبات التأكيد">
                                                    <i class="fas fa-list-check me-1"></i>
                                                    الإجمالي: {{ $totalConfirmations }}
                                                </a>
                                                @if($duringPreparation > 0)
                                                    <div class="badge bg-success"
                                                         style="font-size: 0.85rem;"
                                                         title="طلبات تأكيد تمت من {{ $project->preparation_start_date->format('Y-m-d') }} إلى {{ $project->preparation_end_date->format('Y-m-d') }}">
                                                        <i class="fas fa-clock me-1"></i>
                                                        خلال التحضير: {{ $duringPreparation }}
                                                    </div>
                                                @endif
                                                @if($afterPreparation > 0)
                                                    <div class="badge bg-warning text-dark"
                                                         style="font-size: 0.85rem;"
                                                         title="طلبات تأكيد تمت بعد {{ $project->preparation_end_date->format('Y-m-d') }}">
                                                        <i class="fas fa-forward me-1"></i>
                                                        بعد التحضير: {{ $afterPreparation }}
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted" title="لا توجد طلبات تأكيد مرفقات لهذا المشروع">
                                                <i class="fas fa-minus-circle"></i>
                                                لا توجد
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            @php
                                                $userMaxHierarchy = \App\Models\RoleHierarchy::getUserMaxHierarchyLevel(Auth::user());
                                            @endphp
                                            @if($userMaxHierarchy && $userMaxHierarchy >= 4)
                                                <button type="button" class="btn btn-sm btn-info" title="التفاصيل الكاملة" onclick="openProjectSidebar({{ $project->id }})">
                                                    <i class="fas fa-info-circle"></i>
                                                </button>
                                            @endif
                                            <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-primary" title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Include Sidebar -->
                @include('projects.partials._project_sidebar')
            @else
                <div class="projects-empty-state">
                    <div class="projects-empty-icon">📋</div>
                    <h4 class="projects-empty-title">لا توجد مشاريع في فترة التحضير</h4>
                    <p class="projects-empty-subtitle">جميع المشاريع إما لم يتم تفعيل فترة التحضير لها أو انتهت فترة التحضير</p>
                    <a href="{{ route('projects.index') }}" class="projects-empty-btn">
                        <i class="fas fa-arrow-left me-2"></i>
                        العودة لقائمة المشاريع
                    </a>
                </div>
            @endif

            <!-- Pagination -->
            @if($projects->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal: إضافة مشروع لفترة التحضير -->
<div class="modal fade" id="addPreparationModal" tabindex="-1" aria-labelledby="addPreparationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title" id="addPreparationModalLabel">
                    <i class="fas fa-clock me-2"></i>
                    إضافة مشروع لفترة التحضير
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="{{ route('projects.add-preparation-period') }}" id="preparationForm">
                @csrf
                <div class="modal-body p-4">
                    <!-- اختيار المشروع -->
                    <div class="mb-4">
                        <label for="project_search" class="form-label fw-bold">
                            <i class="fas fa-project-diagram text-primary me-1"></i>
                            اختر المشروع
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text"
                                   class="form-control @error('project_id') is-invalid @enderror"
                                   id="project_search"
                                   list="projectsList"
                                   placeholder="ابحث عن المشروع بالكود أو الاسم..."
                                   autocomplete="off"
                                   style="border-right: none;">
                        </div>
                        <datalist id="projectsList"></datalist>
                        <input type="hidden" id="project_id" name="project_id" required>
                        @error('project_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            ابدأ بكتابة كود أو اسم المشروع واختر من القائمة
                        </small>
                    </div>

                    <!-- معلومات المشروع (تظهر بعد الاختيار) -->
                    <div id="projectInfo" style="display: none;">
                        <div class="alert alert-info d-flex align-items-start" style="border-radius: 12px;">
                            <i class="fas fa-info-circle me-3 mt-1" style="font-size: 1.5rem;"></i>
                            <div class="flex-grow-1">
                                <h6 class="mb-2">معلومات المشروع</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>الكود:</strong>
                                        <div id="projectCode" class="text-dark"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>اسم المشروع:</strong>
                                        <div id="projectName" class="text-dark"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>العميل:</strong>
                                        <div id="projectClient" class="text-dark"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- تاريخ بداية التحضير -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="preparation_start_date" class="form-label fw-bold">
                                    <i class="fas fa-calendar-day text-success me-1"></i>
                                    تاريخ بداية فترة التحضير
                                </label>
                                <input type="datetime-local"
                                       class="form-control @error('preparation_start_date') is-invalid @enderror"
                                       id="preparation_start_date"
                                       name="preparation_start_date"
                                       value="{{ old('preparation_start_date') }}"
                                       required>
                                @error('preparation_start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="preparation_end_date" class="form-label fw-bold">
                                    <i class="fas fa-calendar-check text-danger me-1"></i>
                                    تاريخ نهاية فترة التحضير
                                </label>
                                <input type="datetime-local"
                                       class="form-control @error('preparation_end_date') is-invalid @enderror"
                                       id="preparation_end_date"
                                       name="preparation_end_date"
                                       value="{{ old('preparation_end_date') }}"
                                       required>
                                @error('preparation_end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- عرض عدد الأيام المحسوبة -->
                        <div class="alert alert-info d-flex align-items-center" style="border-radius: 12px;">
                            <i class="fas fa-calculator me-3" style="font-size: 1.5rem;"></i>
                            <div>
                                <strong>المدة المحسوبة:</strong>
                                <span id="calculatedDays" class="fs-5 fw-bold ms-2">0 يوم</span>
                            </div>
                        </div>
                    </div>

                    @if($errors->any() && !$errors->has('project_id'))
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="modal-footer" style="border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        إلغاء
                    </button>
                    <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                        <i class="fas fa-check-circle me-1"></i>
                        تفعيل فترة التحضير
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    console.log('📋 صفحة المشاريع في فترة التحضير');

    let projectsData = [];

    // جلب قائمة المشاريع عند فتح الـ Modal
    document.getElementById('addPreparationModal')?.addEventListener('show.bs.modal', function () {
        loadProjectsList();
    });

    // تحميل قائمة المشاريع
    function loadProjectsList() {
        const datalistElement = document.getElementById('projectsList');
        const searchInput = document.getElementById('project_search');

        if (!datalistElement) return;

        // عرض loader
        if (searchInput) {
            searchInput.placeholder = 'جاري تحميل المشاريع...';
            searchInput.disabled = true;
        }

        fetch(`{{ route('projects.list-for-preparation') }}`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.projects) {
                projectsData = data.projects;

                // ملء الـ datalist
                datalistElement.innerHTML = '';
                data.projects.forEach(project => {
                    const option = document.createElement('option');
                    option.value = project.display_text;
                    option.dataset.id = project.id;
                    option.dataset.code = project.code;
                    option.dataset.name = project.name;
                    option.dataset.client = project.client_name;
                    datalistElement.appendChild(option);
                });

                if (searchInput) {
                    searchInput.disabled = false;
                    searchInput.placeholder = 'ابحث عن المشروع بالكود أو الاسم...';
                }
            } else {
                alert('حدث خطأ أثناء تحميل المشاريع');
                if (searchInput) {
                    searchInput.placeholder = 'لا توجد مشاريع متاحة';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء تحميل المشاريع');
            if (searchInput) {
                searchInput.placeholder = 'خطأ في التحميل';
            }
        });
    }

    // عند الكتابة أو الاختيار من الـ datalist
    document.getElementById('project_search')?.addEventListener('input', function() {
        const searchValue = this.value.trim();
        const hiddenInput = document.getElementById('project_id');

        if (!searchValue) {
            hiddenInput.value = '';
            document.getElementById('projectInfo').style.display = 'none';
            document.getElementById('submitBtn').disabled = true;
            return;
        }

        // البحث عن المشروع المطابق
        const selectedProject = projectsData.find(p => p.display_text === searchValue);

        if (selectedProject) {
            // تعيين الـ ID في الـ hidden input
            hiddenInput.value = selectedProject.id;

            // عرض معلومات المشروع
            document.getElementById('projectCode').textContent = selectedProject.code || 'غير محدد';
            document.getElementById('projectName').textContent = selectedProject.name || 'غير محدد';
            document.getElementById('projectClient').textContent = selectedProject.client_name || 'غير محدد';
            document.getElementById('projectInfo').style.display = 'block';
            document.getElementById('submitBtn').disabled = false;

            // تعيين تاريخ افتراضي (الآن)
            const now = new Date();
            const nowStr = now.toISOString().slice(0, 16);
            document.getElementById('preparation_start_date').value = nowStr;

            // تعيين تاريخ النهاية (بعد 7 أيام مثلاً)
            const endDate = new Date(now);
            endDate.setDate(endDate.getDate() + 7);
            const endStr = endDate.toISOString().slice(0, 16);
            document.getElementById('preparation_end_date').value = endStr;

            // حساب عدد الأيام
            calculateDays();
        } else {
            // لم يتم العثور على مطابقة
            hiddenInput.value = '';
            document.getElementById('projectInfo').style.display = 'none';
            document.getElementById('submitBtn').disabled = true;
        }
    });

    // حساب عدد الأيام تلقائياً
    function calculateDays() {
        const startDate = document.getElementById('preparation_start_date').value;
        const endDate = document.getElementById('preparation_end_date').value;

        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            document.getElementById('calculatedDays').textContent = diffDays + ' يوم';
        }
    }

    // حساب عدد الأيام عند تغيير التواريخ
    document.getElementById('preparation_start_date')?.addEventListener('change', calculateDays);
    document.getElementById('preparation_end_date')?.addEventListener('change', calculateDays);

    // إعادة تعيين الفورم عند إغلاق الـ Modal
    const modal = document.getElementById('addPreparationModal');
    modal?.addEventListener('hidden.bs.modal', function () {
        document.getElementById('preparationForm').reset();
        document.getElementById('project_search').value = '';
        document.getElementById('project_id').value = '';
        document.getElementById('projectInfo').style.display = 'none';
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('calculatedDays').textContent = '0 يوم';
    });

    @if($errors->any())
        // إعادة فتح الـ Modal إذا كانت هناك أخطاء
        const addModal = new bootstrap.Modal(document.getElementById('addPreparationModal'));
        addModal.show();
    @endif
</script>
@endpush

