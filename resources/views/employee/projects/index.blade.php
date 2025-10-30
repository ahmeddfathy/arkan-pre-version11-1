@extends('layouts.app')

@section('title', 'مشاريعي')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📊 مشاريعي</h1>
            <p>عرض وإدارة جميع مشاريعي مع الديدلاين والحالات</p>
        </div>

        <!-- Success/Error Messages -->
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

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="{{ route('employee.projects.index') }}" id="filterForm">
                <div class="filters-row">
                    <!-- Status Filter -->
                    <div class="filter-group">
                        <label for="statusFilter" class="filter-label">
                            <i class="fas fa-flag"></i>
                            حالة المشروع
                        </label>
                        <select id="statusFilter" name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع الحالات</option>
                            @foreach(App\Models\ProjectServiceUser::getAvailableStatuses() as $key => $label)
                                <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Deadline Filter -->
                    <div class="filter-group">
                        <label for="deadlineFilter" class="filter-label">
                            <i class="fas fa-calendar-alt"></i>
                            الموعد النهائي
                        </label>
                        <select id="deadlineFilter" name="deadline_filter" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">الكل</option>
                            <option value="today" {{ request('deadline_filter') == 'today' ? 'selected' : '' }}>اليوم</option>
                            <option value="this_week" {{ request('deadline_filter') == 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                            <option value="this_month" {{ request('deadline_filter') == 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                            <option value="overdue" {{ request('deadline_filter') == 'overdue' ? 'selected' : '' }}>متأخر</option>
                            <option value="upcoming" {{ request('deadline_filter') == 'upcoming' ? 'selected' : '' }}>قادم</option>
                        </select>
                    </div>

                    <!-- Search Filter -->
                    <div class="filter-group">
                        <label for="searchInput" class="filter-label">
                            <i class="fas fa-search"></i>
                            بحث بالكود أو الاسم
                        </label>
                        <input type="text"
                               id="searchInput"
                               name="search"
                               class="filter-select search-input"
                               placeholder="اكتب كود المشروع أو اختر من القائمة..."
                               value="{{ request('search') }}"
                               list="projectsList"
                               autocomplete="off">
                        <datalist id="projectsList">
                            @foreach($allProjects as $proj)
                                <option value="{{ $proj->code }}">{{ $proj->name }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    <!-- Project Filter -->
                    <div class="filter-group">
                        <label for="projectFilter" class="filter-label">
                            <i class="fas fa-project-diagram"></i>
                            المشروع
                        </label>
                        <select id="projectFilter" name="project_id" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع المشاريع</option>
                            @foreach($allProjects as $proj)
                                <option value="{{ $proj->id }}" {{ request('project_id') == $proj->id ? 'selected' : '' }}>
                                    {{ $proj->code }} - {{ $proj->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sort By -->
                    <div class="filter-group">
                        <label for="sortBy" class="filter-label">
                            <i class="fas fa-sort"></i>
                            الترتيب
                        </label>
                        <select id="sortBy" name="sort_by" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="deadline" {{ request('sort_by') == 'deadline' ? 'selected' : '' }}>حسب الديدلاين</option>
                            <option value="status" {{ request('sort_by') == 'status' ? 'selected' : '' }}>حسب الحالة</option>
                            <option value="project_name" {{ request('sort_by') == 'project_name' ? 'selected' : '' }}>حسب اسم المشروع</option>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">بحث</label>
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                            بحث
                        </button>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['status', 'deadline_filter', 'project_id', 'sort_by', 'search']))
                        <div class="filter-group">
                            <label class="filter-label" style="opacity: 0;">مسح</label>
                            <a href="{{ route('employee.projects.index') }}" class="clear-filters-btn">
                                <i class="fas fa-times"></i>
                                مسح الفلاتر
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $stats['total'] }}</div>
                <div class="stat-label">إجمالي المشاريع</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['in_progress'] }}</div>
                <div class="stat-label">جاري</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['this_week'] }}</div>
                <div class="stat-label">هذا الأسبوع</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['this_month'] }}</div>
                <div class="stat-label">هذا الشهر</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['draft_delivery'] }}</div>
                <div class="stat-label">تسليم مسودة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['overdue'] }}</div>
                <div class="stat-label">متأخرة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['final_delivery'] }}</div>
                <div class="stat-label">تم التسليم النهائي</div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>📋 قائمة مشاريعي</h2>
            </div>

            <table class="projects-table">
                <thead>
                    <tr>
                        <th>المشروع</th>
                        <th>الخدمة</th>
                        <th>الحالة</th>
                        <th>الموعد النهائي</th>
                        <th>تاريخ التسليم</th>
                        <th>نسبة المشاركة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $projectUser)
                    <tr class="project-row">
                        <td>
                            <div class="project-info">
                                <div class="project-avatar">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <div class="project-details">
                                    @if($projectUser->project->code)
                                        <div class="project-code-display">{{ $projectUser->project->code }}</div>
                                    @endif
                                    <h4>{{ $projectUser->project->name }}</h4>
                                    @if($projectUser->project->description)
                                        <p>{{ Str::limit($projectUser->project->description, 50) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="client-info">
                                {{ optional($projectUser->service)->name ?? 'غير محدد' }}
                            </div>
                        </td>
                        <td>
                            <div class="status-wrapper">
                                @php
                                    $statusColorClass = $projectUser->getStatusColor();
                                @endphp
                                <select class="status-select status-color-{{ $statusColorClass }}"
                                        onchange="updateStatus({{ $projectUser->id }}, this.value)"
                                        data-project-user-id="{{ $projectUser->id }}">
                                    @foreach(App\Models\ProjectServiceUser::getAvailableStatuses() as $key => $label)
                                        <option value="{{ $key }}" {{ $projectUser->status == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>
                        <td>
                            @if($projectUser->deadline)
                                <div style="color: #6b7280; font-size: 0.9rem;">
                                    {{ $projectUser->deadline->format('Y/m/d') }}

                                    @php
                                        $daysRemaining = $projectUser->getDaysRemaining();
                                    @endphp

                                    @if($projectUser->isOverdue() && $projectUser->status != App\Models\ProjectServiceUser::STATUS_FINAL_DELIVERY)
                                        <div style="color: #dc3545; font-size: 0.8rem; margin-top: 4px;">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            متأخر {{ abs($daysRemaining) }} يوم
                                        </div>
                                    @elseif($projectUser->isDueSoon(3))
                                        <div style="color: #ffc107; font-size: 0.8rem; margin-top: 4px;">
                                            <i class="fas fa-clock"></i>
                                            باقي {{ $daysRemaining }} يوم
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span style="color: #9ca3af;">غير محدد</span>
                            @endif
                        </td>
                        <td>
                            @if($projectUser->delivered_at)
                                <div style="color: #10b981; font-size: 0.9rem; text-align: center;">
                                    <i class="fas fa-check-circle"></i>
                                    {{ $projectUser->delivered_at->format('Y/m/d') }}
                                    <div style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">
                                        {{ $projectUser->delivered_at->format('h:i A') }}
                                    </div>
                                </div>
                            @else
                                <div style="text-align: center;">
                                    <span style="color: #9ca3af;">لم يتم التسليم</span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="text-align: center; font-weight: 600;">
                                {{ $projectUser->getProjectShareLabel() }}
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                @if(!$projectUser->delivered_at)
                                    <button onclick="deliverProject({{ $projectUser->id }}, '{{ $projectUser->status }}')"
                                            class="services-btn"
                                            style="background: linear-gradient(135deg, #10b981, #059669); color: white;"
                                            title="تسليم المشروع">
                                        <i class="fas fa-check"></i>
                                        تسليم
                                    </button>
                                @else
                                    @if($projectUser->canBeUndelivered())
                                        <button onclick="undeliverProject({{ $projectUser->id }})"
                                                class="services-btn"
                                                style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white;"
                                                title="إلغاء التسليم">
                                            <i class="fas fa-times"></i>
                                            إلغاء التسليم
                                        </button>
                                    @endif
                                @endif

                                <a href="{{ route('projects.show', $projectUser->project->id) }}"
                                   class="services-btn"
                                   title="عرض تفاصيل المشروع">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>لا توجد مشاريع</h4>
                            <p>لم يتم العثور على مشاريع مطابقة للفلاتر المحددة</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Pagination -->
            @if($projects->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    /* Additional styles for status select dropdown */
    .status-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .status-select {
        border: none;
        border-radius: 25px;
        padding: 10px 20px;
        font-size: 0.9rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        outline: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        min-width: 180px;
        text-align: center;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='white' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 10px center;
        padding-left: 35px;
    }

    .status-select:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    .status-select:focus {
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.3);
        transform: translateY(-2px);
    }

    .status-select option {
        background-color: white;
        color: #333;
        padding: 10px;
        font-weight: 600;
    }

    /* Status Colors */
    .status-color-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        color: white !important;
    }

    .status-color-info {
        background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
        color: white !important;
    }

    .status-color-primary {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed) !important;
        color: white !important;
    }

    .status-color-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        color: white !important;
    }

    .status-color-success {
        background: linear-gradient(135deg, #10b981, #059669) !important;
        color: white !important;
    }

    .status-color-secondary {
        background: linear-gradient(135deg, #6b7280, #4b5563) !important;
        color: white !important;
    }

    .search-input {
        background: white;
        border: 2px solid #e5e7eb;
        padding: 10px 15px;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        width: 100%;
    }

    .search-input:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .search-input::placeholder {
        color: #9ca3af;
    }

    .search-btn {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-block;
        width: 100%;
    }

    .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .clear-filters-btn {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        width: 100%;
    }

    .clear-filters-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        color: white;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .filters-row {
            flex-direction: column;
        }

        .filter-group {
            width: 100%;
        }

        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<script>
    function updateStatus(projectUserId, newStatus) {
        // Show loading
        Swal.fire({
            title: 'جاري التحديث...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send AJAX request
        fetch(`/employee/projects/${projectUserId}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: data.message || 'حدث خطأ أثناء التحديث'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'حدث خطأ أثناء التحديث'
            });
        });
    }

    // تغيير الحالة والتسليم مباشرة
    function changeStatusAndDeliver(projectUserId, newStatus) {
        Swal.fire({
            title: 'جاري التحديث والتسليم...',
            html: '<i class="fas fa-spinner fa-spin" style="font-size: 3rem; color: #3b82f6;"></i>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        // الخطوة 1: تغيير الحالة
        fetch(`/employee/projects/${projectUserId}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // الخطوة 2: التسليم
                return fetch(`/employee/projects/${projectUserId}/deliver`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });
            } else {
                throw new Error(data.message || 'فشل تحديث الحالة');
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم بنجاح',
                    html: `
                        <div style="text-align: center;">
                            <p style="margin: 10px 0; font-size: 1.1rem;">✅ تم تغيير الحالة إلى: <strong>${newStatus}</strong></p>
                            <p style="margin: 10px 0; font-size: 1.1rem;">📦 تم التسليم بنجاح</p>
                        </div>
                    `,
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: data.message || 'حدث خطأ أثناء التسليم'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: error.message || 'حدث خطأ أثناء العملية'
            });
        });
    }

    // تسليم المشروع
    function deliverProject(projectUserId, currentStatus) {
        // ✅ التحقق من الحالة قبل التسليم
        const validDeliveryStatuses = ['تسليم مسودة', 'تم تسليم نهائي'];

        if (!validDeliveryStatuses.includes(currentStatus)) {
            // عرض alert مع خيار تغيير الحالة مباشرة
            Swal.fire({
                icon: 'warning',
                title: 'يجب تغيير الحالة أولاً',
                html: `
                    <div style="text-align: right; padding: 10px;">
                        <p style="margin-bottom: 15px; color: #64748b; font-size: 1rem;">
                            الحالة الحالية: <strong style="color: #ef4444;">${currentStatus}</strong>
                        </p>

                        <div style="background: #f0f9ff; border-right: 4px solid #3b82f6; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <p style="margin: 0 0 10px 0; color: #1e40af; font-weight: 600;">
                                <i class="fas fa-info-circle" style="margin-left: 5px;"></i>
                                اختر حالة التسليم المناسبة:
                            </p>
                            <select id="deliveryStatusSelect"
                                    style="width: 100%;
                                           padding: 12px;
                                           border: 2px solid #3b82f6;
                                           border-radius: 8px;
                                           font-size: 1rem;
                                           font-weight: 600;
                                           color: #1e293b;
                                           background: white;
                                           cursor: pointer;
                                           text-align: right;">
                                <option value="">-- اختر الحالة --</option>
                                <option value="تسليم مسودة">📝 تسليم مسودة</option>
                                <option value="تم تسليم نهائي">✅ تم تسليم نهائي</option>
                            </select>
                        </div>

                        <p style="margin: 15px 0 0; color: #059669; font-size: 0.9rem; font-weight: 500;">
                            <i class="fas fa-check-circle" style="margin-left: 5px;"></i>
                            سيتم تغيير الحالة والتسليم مباشرة
                        </p>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-check" style="margin-left: 5px;"></i>تغيير الحالة والتسليم',
                cancelButtonText: '<i class="fas fa-times" style="margin-left: 5px;"></i>إلغاء',
                width: '550px',
                preConfirm: () => {
                    const selectedStatus = document.getElementById('deliveryStatusSelect').value;
                    if (!selectedStatus) {
                        Swal.showValidationMessage('⚠️ الرجاء اختيار حالة التسليم');
                        return false;
                    }
                    return selectedStatus;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const newStatus = result.value;
                    // تغيير الحالة أولاً ثم التسليم
                    changeStatusAndDeliver(projectUserId, newStatus);
                }
            });
            return;
        }

        // إذا كانت الحالة صحيحة، نسأل التأكيد فقط
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'هل تريد تسليم هذا المشروع؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'نعم، تسليم',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'جاري التسليم...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send AJAX request
                fetch(`/employee/projects/${projectUserId}/deliver`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: data.message || 'حدث خطأ أثناء التسليم'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'حدث خطأ أثناء التسليم'
                    });
                });
            }
        });
    }

    // إلغاء تسليم المشروع
    function undeliverProject(projectUserId) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'هل تريد إلغاء تسليم هذا المشروع؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'نعم، إلغاء التسليم',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'جاري الإلغاء...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send AJAX request
                fetch(`/employee/projects/${projectUserId}/undeliver`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم بنجاح',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: data.message || 'حدث خطأ أثناء إلغاء التسليم'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'حدث خطأ أثناء إلغاء التسليم'
                    });
                });
            }
        });
    }
</script>
@endpush
