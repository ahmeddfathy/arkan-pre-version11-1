@extends('layouts.app')

@section('title', 'مشاريع الفريق')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-services.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>👥 مشاريع الفريق - قائد الفريق</h1>
            <p>إدارة ومتابعة جميع مشاريع الفريق مع تفاصيل أعضاء كل خدمة</p>
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
                                <option value="{{ $proj->code }}">{{ $proj->code }} - {{ $proj->name }}</option>
                            @endforeach
                        </datalist>
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
                <div class="stat-number">{{ $stats['total_services'] }}</div>
                <div class="stat-label">إجمالي الخدمات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['completed_services'] }}</div>
                <div class="stat-label">خدمات مكتملة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['in_progress_services'] }}</div>
                <div class="stat-label">جاري العمل</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['overdue_services'] }}</div>
                <div class="stat-label">متأخرة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['total_members'] }}</div>
                <div class="stat-label">أعضاء الفريق</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $stats['avg_completion'] }}%</div>
                <div class="stat-label">متوسط الإنجاز</div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>📋 قائمة مشاريع الفريق</h2>
            </div>

            <table class="projects-table">
                <thead>
                    <tr>
                        <th style="width: 30px;"></th>
                        <th>المشروع / الخدمة</th>
                        <th>الحالة</th>
                        <th>الموعد النهائي</th>
                        <th>عدد الأعضاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupedProjects as $index => $projectService)
                        <!-- Main Service Row -->
                        <tr class="service-row" data-target="members-{{ $index }}">
                            <td>
                                <button class="expand-btn" onclick="toggleMembers({{ $index }})">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </td>
                            <td>
                                <div class="project-info">
                                    <div class="project-avatar">
                                        <i class="fas fa-project-diagram"></i>
                                    </div>
                                    <div class="project-details">
                                        @if($projectService['project']->code)
                                            <div class="project-code-display">{{ $projectService['project']->code }}</div>
                                        @endif
                                        <h4>{{ $projectService['project']->name }}</h4>
                                        <p>
                                            <i class="fas fa-cog"></i>
                                            {{ $projectService['service']->name ?? 'غير محدد' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    // تحديد اللون حسب الحالة
                                    $statusColorMap = [
                                        App\Models\ProjectServiceUser::STATUS_IN_PROGRESS => 'info',
                                        App\Models\ProjectServiceUser::STATUS_WAITING_FORM => 'warning',
                                        App\Models\ProjectServiceUser::STATUS_WAITING_QUESTIONS => 'warning',
                                        App\Models\ProjectServiceUser::STATUS_WAITING_CLIENT => 'warning',
                                        App\Models\ProjectServiceUser::STATUS_WAITING_CALL => 'warning',
                                        App\Models\ProjectServiceUser::STATUS_PAUSED => 'secondary',
                                        App\Models\ProjectServiceUser::STATUS_DRAFT_DELIVERY => 'primary',
                                        App\Models\ProjectServiceUser::STATUS_FINAL_DELIVERY => 'success',
                                    ];
                                    $statusColorClass = $statusColorMap[$projectService['service_status']] ?? 'secondary';

                                    // إذا كانت الخدمة متأخرة، نعرض تحذير
                                    $hasOverdue = $projectService['stats']['overdue'] > 0;
                                @endphp
                                <div>
                                    <button onclick="showStatusModal({{ $projectService['project']->id }}, {{ $projectService['service']->id }}, '{{ $projectService['service_status'] }}', {{ $index }})"
                                            class="status-badge-main status-{{ $statusColorClass }}"
                                            style="cursor: pointer; border: none;">
                                        {{ $projectService['service_status'] }}
                                        <i class="fas fa-edit" style="margin-right: 5px; font-size: 0.8rem;"></i>
                                    </button>
                                    @if($hasOverdue)
                                        <div style="margin-top: 5px;">
                                            <span class="status-badge-main status-danger" style="font-size: 0.75rem; padding: 4px 8px;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                {{ $projectService['stats']['overdue'] }} متأخر
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($projectService['earliest_deadline'])
                                    <div style="color: #6b7280; font-size: 0.9rem;">
                                        {{ \Carbon\Carbon::parse($projectService['earliest_deadline'])->format('Y/m/d') }}

                                        @php
                                            $deadline = \Carbon\Carbon::parse($projectService['earliest_deadline']);
                                            $now = \Carbon\Carbon::now();
                                            $daysRemaining = $now->diffInDays($deadline, false);
                                        @endphp

                                        @if($daysRemaining < 0 && $projectService['service_status'] != 'مكتمل')
                                            <div style="color: #dc3545; font-size: 0.8rem; margin-top: 4px;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                متأخر {{ abs($daysRemaining) }} يوم
                                            </div>
                                        @elseif($daysRemaining <= 3 && $daysRemaining >= 0)
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
                            <td style="text-align: center;">
                                <div class="members-count">
                                    <span class="count-badge">
                                        {{ $projectService['stats']['total'] }}
                                    </span>
                                    <div style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">
                                        <span style="color: #10b981;">✓{{ $projectService['stats']['completed'] }}</span>
                                        <span style="color: #3b82f6; margin: 0 5px;">●{{ $projectService['stats']['in_progress'] }}</span>
                                        @if($projectService['stats']['overdue'] > 0)
                                            <span style="color: #ef4444;">!{{ $projectService['stats']['overdue'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('projects.show', $projectService['project']->id) }}"
                                   class="services-btn"
                                   title="عرض تفاصيل المشروع">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                            </td>
                        </tr>

                        <!-- Expanded Members Row (Hidden by default) -->
                        <tr class="members-row" id="members-{{ $index }}" style="display: none;">
                            <td colspan="6" style="padding: 0; background: #f9fafb;">
                                <div class="members-expanded-content">
                                    <div class="service-header-actions">
                                        <h4 class="members-section-title">
                                            <i class="fas fa-users-cog"></i>
                                            أعضاء الفريق في هذه الخدمة ({{ $projectService['stats']['total'] }} عضو)
                                        </h4>

                                        <!-- تغيير حالة الخدمة بالكامل -->
                                        <div class="service-status-control">
                                            <label for="serviceStatus-{{ $index }}" style="font-weight: 600; color: #374151; margin-left: 10px;">
                                                <i class="fas fa-edit"></i>
                                                تغيير حالة الخدمة:
                                            </label>
                                            <select id="serviceStatus-{{ $index }}" class="service-status-select">
                                                <option value="">-- اختر الحالة --</option>
                                                @foreach(App\Models\ProjectServiceUser::getAvailableStatuses() as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <button onclick="updateServiceStatus({{ $projectService['project']->id }}, {{ $projectService['service']->id }}, {{ $index }})"
                                                    class="btn-update-service">
                                                <i class="fas fa-check-circle"></i>
                                                تطبيق على الكل
                                            </button>
                                        </div>
                                    </div>

                                    <table class="members-inner-table">
                                        <thead>
                                            <tr>
                                                <th>الموظف</th>
                                                <th>الحالة</th>
                                                <th>الموعد النهائي</th>
                                                <th>تاريخ التسليم</th>
                                                <th>نسبة المشاركة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($projectService['members'] as $member)
                                                <tr>
                                                    <td>
                                                        <div class="member-info">
                                                            <div class="member-avatar">
                                                                @if($member->user->profile_photo_path)
                                                                    <img src="{{ asset('storage/' . $member->user->profile_photo_path) }}" alt="{{ $member->user->name }}">
                                                                @else
                                                                    <i class="fas fa-user"></i>
                                                                @endif
                                                            </div>
                                                            <div class="member-details">
                                                                <h5>{{ $member->user->name }}</h5>
                                                                <p>{{ $member->user->email }}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-{{ $member->getStatusColor() }}">
                                                            {{ $member->status }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($member->deadline)
                                                            <div style="color: #6b7280; font-size: 0.9rem;">
                                                                {{ $member->deadline->format('Y/m/d') }}
                                                                @php
                                                                    $daysRemaining = $member->getDaysRemaining();
                                                                @endphp
                                                                @if($member->isOverdue() && $member->status != App\Models\ProjectServiceUser::STATUS_FINAL_DELIVERY)
                                                                    <small style="color: #dc3545; font-size: 0.8rem; display: block; margin-top: 4px;">
                                                                        <i class="fas fa-exclamation-triangle"></i>
                                                                        متأخر {{ abs($daysRemaining) }} يوم
                                                                    </small>
                                                                @elseif($member->isDueSoon(3))
                                                                    <small style="color: #ffc107; font-size: 0.8rem; display: block; margin-top: 4px;">
                                                                        <i class="fas fa-clock"></i>
                                                                        باقي {{ $daysRemaining }} يوم
                                                                    </small>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span style="color: #9ca3af;">غير محدد</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($member->delivered_at)
                                                            <div style="color: #10b981; font-size: 0.9rem; text-align: center;">
                                                                <i class="fas fa-check-circle"></i>
                                                                {{ $member->delivered_at->format('Y/m/d') }}
                                                                <div style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">
                                                                    {{ $member->delivered_at->format('h:i A') }}
                                                                </div>
                                                            </div>
                                                        @else
                                                            <span style="color: #9ca3af;">لم يتم التسليم</span>
                                                        @endif
                                                    </td>
                                                    <td style="text-align: center; font-weight: 600;">
                                                        {{ $member->getProjectShareLabel() }}
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('projects.show', $member->project->id) }}"
                                                           class="services-btn services-btn-sm"
                                                           title="عرض تفاصيل المشروع">
                                                            <i class="fas fa-eye"></i>
                                                            عرض
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h4>لا توجد مشاريع</h4>
                                <p>لم يتم العثور على مشاريع مطابقة للفلاتر المحددة</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function toggleMembers(index) {
        const membersRow = document.getElementById('members-' + index);
        const expandBtn = event.currentTarget;
        const icon = expandBtn.querySelector('i');

        if (membersRow.style.display === 'none' || membersRow.style.display === '') {
            membersRow.style.display = 'table-row';
            icon.style.transform = 'rotate(180deg)';
            expandBtn.classList.add('expanded');
        } else {
            membersRow.style.display = 'none';
            icon.style.transform = 'rotate(0deg)';
            expandBtn.classList.remove('expanded');
        }
    }

    // عرض modal لاختيار الحالة الجديدة
    function showStatusModal(projectId, serviceId, currentStatus, index) {
        const statuses = {
            'جاري': { label: 'جاري', icon: 'fa-spinner', color: '#3b82f6' },
            'واقف ع النموذج': { label: 'واقف ع النموذج', icon: 'fa-file-alt', color: '#f59e0b' },
            'واقف ع الأسئلة': { label: 'واقف ع الأسئلة', icon: 'fa-question-circle', color: '#f59e0b' },
            'واقف ع العميل': { label: 'واقف ع العميل', icon: 'fa-user-clock', color: '#f59e0b' },
            'واقف ع مكالمة': { label: 'واقف ع مكالمة', icon: 'fa-phone', color: '#f59e0b' },
            'موقوف': { label: 'موقوف', icon: 'fa-pause-circle', color: '#ef4444' },
            'تسليم مسودة': { label: 'تسليم مسودة', icon: 'fa-file-export', color: '#8b5cf6' },
            'تم تسليم نهائي': { label: 'تم تسليم نهائي', icon: 'fa-check-circle', color: '#10b981' }
        };

        let optionsHtml = '';
        for (const [key, data] of Object.entries(statuses)) {
            const selected = key === currentStatus ? 'selected' : '';
            optionsHtml += `<option value="${key}" ${selected} data-icon="${data.icon}" data-color="${data.color}">
                ${data.label}
            </option>`;
        }

        const currentStatusData = statuses[currentStatus] || { label: currentStatus, icon: 'fa-circle', color: '#6b7280' };

        Swal.fire({
            title: '<i class="fas fa-edit" style="color: #3b82f6; margin-left: 10px;"></i>تحديث حالتك في الخدمة',
            html: `
                <div style="text-align: right; padding: 0 10px;">
                    <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
                                border-right: 4px solid ${currentStatusData.color};
                                padding: 15px;
                                border-radius: 12px;
                                margin-bottom: 25px;
                                box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <p style="margin: 0; color: #64748b; font-size: 0.9rem; font-weight: 500;">
                            الحالة الحالية
                        </p>
                        <p style="margin: 8px 0 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;">
                            <i class="fas ${currentStatusData.icon}" style="color: ${currentStatusData.color}; margin-left: 8px;"></i>
                            ${currentStatus}
                        </p>
                    </div>

                    <label style="display: block;
                                  margin-bottom: 12px;
                                  font-weight: 700;
                                  color: #1e293b;
                                  font-size: 1rem;">
                        <i class="fas fa-tasks" style="color: #3b82f6; margin-left: 8px;"></i>
                        اختر الحالة الجديدة
                    </label>

                    <select id="newStatusSelect"
                            style="width: 100%;
                                   padding: 14px 16px;
                                   border: 2px solid #cbd5e1;
                                   border-radius: 12px;
                                   font-size: 1rem;
                                   font-weight: 600;
                                   color: #1e293b;
                                   background: white;
                                   transition: all 0.3s ease;
                                   cursor: pointer;"
                            onchange="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)';">
                        ${optionsHtml}
                    </select>

                    <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
                                border-radius: 12px;
                                padding: 15px;
                                margin-top: 20px;
                                border: 1px solid #bfdbfe;
                                box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);">
                        <p style="margin: 0; font-size: 0.95rem; color: #1e40af; font-weight: 500; line-height: 1.6;">
                            <i class="fas fa-info-circle" style="color: #3b82f6; margin-left: 8px; font-size: 1.1rem;"></i>
                            سيتم تحديث <strong>حالتك الشخصية</strong> و<strong>حالة الخدمة</strong> في هذا المشروع فقط
                        </p>
                    </div>
                </div>
            `,
            width: '550px',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: '<i class="fas fa-check-circle" style="margin-left: 8px;"></i>تطبيق الحالة',
            cancelButtonText: '<i class="fas fa-times" style="margin-left: 8px;"></i>إلغاء',
            buttonsStyling: true,
            customClass: {
                popup: 'status-modal-popup',
                confirmButton: 'swal2-confirm-modern',
                cancelButton: 'swal2-cancel-modern',
                title: 'swal2-title-modern'
            },
            didOpen: () => {
                // تحسين شكل الـ buttons
                const confirmBtn = Swal.getConfirmButton();
                const cancelBtn = Swal.getCancelButton();

                if (confirmBtn) {
                    confirmBtn.style.cssText = `
                        padding: 12px 28px !important;
                        font-size: 1rem !important;
                        font-weight: 700 !important;
                        border-radius: 10px !important;
                        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
                        transition: all 0.3s ease !important;
                    `;
                }

                if (cancelBtn) {
                    cancelBtn.style.cssText = `
                        padding: 12px 28px !important;
                        font-size: 1rem !important;
                        font-weight: 600 !important;
                        border-radius: 10px !important;
                        transition: all 0.3s ease !important;
                    `;
                }
            },
            preConfirm: () => {
                const newStatus = document.getElementById('newStatusSelect').value;
                if (!newStatus) {
                    Swal.showValidationMessage('⚠️ الرجاء اختيار حالة جديدة');
                    return false;
                }
                if (newStatus === currentStatus) {
                    Swal.showValidationMessage('⚠️ الرجاء اختيار حالة مختلفة عن الحالة الحالية');
                    return false;
                }
                return newStatus;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                applyStatusChange(projectId, serviceId, result.value);
            }
        });
    }

    // تطبيق تغيير الحالة
    function applyStatusChange(projectId, serviceId, newStatus) {
        Swal.fire({
            title: 'جاري التحديث...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(`/employee/projects/service/${projectId}/${serviceId}/update-status`, {
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

    // تحديث حالة الخدمة بالكامل (من داخل الـ accordion)
    function updateServiceStatus(projectId, serviceId, index) {
        const statusSelect = document.getElementById('serviceStatus-' + index);
        const newStatus = statusSelect.value;

        if (!newStatus) {
            Swal.fire({
                icon: 'warning',
                title: 'تنبيه',
                text: 'الرجاء اختيار حالة أولاً'
            });
            return;
        }

        // تأكيد التغيير
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'سيتم تغيير حالة جميع الأعضاء في هذه الخدمة',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'نعم، تطبيق',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                applyStatusChange(projectId, serviceId, newStatus);
            }
        });
    }
</script>
@endpush

@push('styles')
<style>
    /* Expand Button */
    .expand-btn {
        background: transparent;
        border: none;
        color: #3b82f6;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 5px;
        transition: all 0.3s ease;
    }

    .expand-btn i {
        transition: transform 0.3s ease;
    }

    .expand-btn:hover {
        background: #dbeafe;
    }

    .expand-btn.expanded {
        background: #3b82f6;
        color: white;
    }

    /* Service Row */
    .service-row {
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .service-row:hover {
        background: #f9fafb;
    }

    /* Team Badge */
    .team-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #e0e7ff;
        color: #4338ca;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Status Badges */
    .status-badge-main {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.9rem;
        display: inline-block;
    }

    .status-badge-main.status-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .status-badge-main.status-info {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
    }

    .status-badge-main.status-primary {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .status-badge-main.status-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .status-badge-main.status-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .status-badge-main.status-secondary {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
    }

    /* Status Button Hover Effect */
    button.status-badge-main:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    button.status-badge-main {
        transition: all 0.3s ease;
    }

    /* SweetAlert Modal Styling */
    .status-modal-popup {
        font-family: inherit !important;
    }

    .swal2-select {
        text-align: right !important;
        direction: rtl !important;
    }

    /* Progress Bar */
    .progress-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .progress-bar-wrapper {
        width: 100px;
        height: 8px;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
        border-radius: 10px;
        transition: width 0.5s ease;
    }

    .progress-text {
        font-size: 0.85rem;
        font-weight: 700;
        color: #10b981;
        min-width: 40px;
    }

    /* Members Count */
    .members-count {
        text-align: center;
    }

    .count-badge {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.9rem;
        display: inline-block;
    }

    /* Members Expanded Content */
    .members-expanded-content {
        padding: 25px;
        background: white;
        border-top: 3px solid #3b82f6;
    }

    .service-header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .members-section-title {
        color: #1f2937;
        font-size: 1.1rem;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
    }

    .service-status-control {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f9fafb;
        padding: 12px 20px;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
    }

    .service-status-select {
        padding: 8px 12px;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        font-size: 0.9rem;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 180px;
    }

    .service-status-select:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .btn-update-service {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-update-service:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .members-inner-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .members-inner-table thead {
        background: linear-gradient(135deg, #f9fafb, #f3f4f6);
    }

    .members-inner-table th {
        padding: 12px;
        text-align: right;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        font-size: 0.9rem;
    }

    .members-inner-table td {
        padding: 12px;
        border-bottom: 1px solid #f3f4f6;
    }

    .members-inner-table tbody tr:hover {
        background: #f9fafb;
    }

    .services-btn-sm {
        padding: 6px 12px;
        font-size: 0.85rem;
    }

    /* Member Info */
    .member-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .member-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        overflow: hidden;
    }

    .member-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .member-details h5 {
        margin: 0;
        font-size: 0.95rem;
        color: #1f2937;
        font-weight: 600;
    }

    .member-details p {
        margin: 2px 0 0;
        font-size: 0.8rem;
        color: #6b7280;
    }

    /* Status Badges for Members */
    .status-badge {
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-block;
    }

    .status-badge.status-warning { background: #fef3c7; color: #92400e; }
    .status-badge.status-info { background: #dbeafe; color: #1e40af; }
    .status-badge.status-primary { background: #e0e7ff; color: #4338ca; }
    .status-badge.status-danger { background: #fee2e2; color: #991b1b; }
    .status-badge.status-success { background: #d1fae5; color: #065f46; }
    .status-badge.status-secondary { background: #f3f4f6; color: #374151; }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 20px;
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

    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            align-items: flex-start;
        }

        .service-status-section {
            width: 100%;
            justify-content: space-between;
        }

        .members-table {
            font-size: 0.85rem;
        }

        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* تحسين ديزاين الـ SweetAlert Modal */
    .swal2-popup.status-modal-popup {
        border-radius: 20px !important;
        padding: 30px !important;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15) !important;
    }

    .swal2-title-modern {
        font-size: 1.5rem !important;
        font-weight: 800 !important;
        color: #1e293b !important;
        padding: 0 0 20px 0 !important;
        border-bottom: 2px solid #e2e8f0 !important;
        margin-bottom: 20px !important;
    }

    .swal2-html-container {
        margin: 0 !important;
        padding: 0 !important;
    }

    .swal2-actions {
        margin-top: 25px !important;
        gap: 15px !important;
    }

    .swal2-confirm-modern:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4) !important;
    }

    .swal2-cancel-modern:hover {
        transform: translateY(-2px) !important;
        background-color: #64748b !important;
    }

    /* تحسين شكل الـ select داخل الـ modal */
    #newStatusSelect:focus {
        outline: none !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
    }

    #newStatusSelect option {
        padding: 12px !important;
        font-weight: 600 !important;
    }
</style>
@endpush

