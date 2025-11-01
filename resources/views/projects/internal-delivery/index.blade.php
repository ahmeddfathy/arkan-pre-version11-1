@extends('layouts.app')

@section('title', 'التسليم الداخلي للمشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-internal-delivery.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>التسليم الداخلي للمشاريع</h1>
            <p>اختر مشروعاً من القائمة لإتمام تسليمه الداخلي وتغيير حالته</p>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="{{ route('projects.internal-delivery.index') }}" id="filterForm">
                <div class="filters-row">
                    <!-- Project Search with Datalist -->
                    <div class="filter-group">
                        <label for="project_search" class="filter-label">
                            اختر مشروع (اكتب للبحث - مرتبة من الأحدث)
                        </label>
                        <input type="text"
                            id="project_search"
                            name="project_search"
                            class="filter-select search-input"
                            placeholder="اكتب اسم المشروع أو الكود للبحث..."
                            value="{{ request('project_search') }}"
                            list="projects-datalist"
                            autocomplete="off">
                        <datalist id="projects-datalist">
                            @foreach($allProjects as $proj)
                            <option value="{{ $proj->code ?? 'بدون كود' }} - {{ $proj->name }} ({{ $proj->created_at->format('Y/m/d') }})" data-project-id="{{ $proj->id }}">
                                {{ $proj->code ?? 'بدون كود' }} - {{ $proj->name }} ({{ $proj->created_at->format('Y/m/d') }})
                            </option>
                            @endforeach
                        </datalist>
                        <input type="hidden" id="project_id" name="project_id" value="{{ request('project_id') }}">
                    </div>

                    <!-- Status Filter -->
                    <div class="filter-group">
                        <label for="status_filter" class="filter-label">
                            فلتر بالحالة
                        </label>
                        <select id="status_filter" name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع الحالات</option>
                            <option value="جديد" {{ request('status') == 'جديد' ? 'selected' : '' }}>جديد</option>
                            <option value="جاري التنفيذ" {{ request('status') == 'جاري التنفيذ' ? 'selected' : '' }}>جاري التنفيذ</option>
                            <option value="مكتمل" {{ request('status') == 'مكتمل' ? 'selected' : '' }}>مكتمل</option>
                            <option value="ملغي" {{ request('status') == 'ملغي' ? 'selected' : '' }}>ملغي</option>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">بحث</label>
                        <button type="submit" class="search-btn">
                            بحث
                        </button>
                    </div>

                    <!-- Clear Filters -->
                    @if(request()->hasAny(['status', 'project_id', 'project_search']))
                    <div class="filter-group">
                        <label class="filter-label" style="opacity: 0;">مسح</label>
                        <a href="{{ route('projects.internal-delivery.index') }}" class="clear-filters-btn">
                            مسح الفلاتر
                        </a>
                    </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Projects Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>قائمة المشاريع - التسليم الداخلي</h2>
            </div>

            <table class="projects-table">
                <thead>
                    <tr>
                        <th>المشروع</th>
                        <th>كود المشروع</th>
                        <th>العميل</th>
                        <th>الحالة الحالية</th>
                        <th>نوع التسليم</th>
                        <th>التاريخ المتفق</th>
                        <th>تاريخ التسليم الفعلي</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    <tr class="project-row">
                        <td>
                            <div class="project-info">
                                <div class="project-avatar">
                                </div>
                                <div class="project-details">
                                    @if($project->code)
                                    <div class="project-code-display">{{ $project->code }}</div>
                                    @endif
                                    <h4>{{ $project->name }}</h4>
                                    @if($project->description)
                                    <p>{{ Str::limit($project->description, 50) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="color: #6b7280; font-size: 0.9rem;">
                                {{ $project->code ?? 'غير محدد' }}
                            </div>
                        </td>
                        <td>
                            <div class="client-info">
                                {{ optional($project->client)->name ?? 'غير محدد' }}
                            </div>
                        </td>
                        <td>
                            @php
                            $statusClasses = [
                            'جديد' => 'status-new',
                            'جاري التنفيذ' => 'status-in-progress',
                            'مكتمل' => 'status-completed',
                            'ملغي' => 'status-cancelled'
                            ];
                            $statusClass = $statusClasses[$project->status] ?? 'status-new';
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                {{ $project->status }}
                            </span>
                        </td>
                        <td>
                            @if($project->delivery_type)
                            @if($project->delivery_type == 'مسودة')
                            <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; background: #fef3c7; color: #92400e;">
                                مسودة
                            </span>
                            @else
                            <span style="display: inline-block; padding: 0.4rem 0.8rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; background: #dcfce7; color: #16a34a;">
                                كامل
                            </span>
                            @endif
                            @else
                            <span style="color: #9ca3af; font-size: 0.85rem;">لم يتم التحديد</span>
                            @endif
                        </td>
                        <td>
                            @if($project->team_delivery_date)
                            <div style="color: #374151; font-size: 0.9rem; font-weight: 500;">
                                {{ $project->team_delivery_date->format('Y/m/d') }}
                            </div>
                            @else
                            <span style="color: #9ca3af; font-size: 0.85rem;">غير محدد</span>
                            @endif
                        </td>
                        <td>
                            @if($project->actual_delivery_date)
                            <div style="color: #374151; font-size: 0.9rem;">
                                <div>{{ $project->actual_delivery_date->format('Y/m/d') }}</div>
                                <div style="color: #6b7280; font-size: 0.8rem;">{{ $project->actual_delivery_date->format('h:i A') }}</div>

                                @if($project->team_delivery_date)
                                @php
                                $teamDate = $project->team_delivery_date->startOfDay();
                                $actualDate = $project->actual_delivery_date->startOfDay();
                                $diffDays = $actualDate->diffInDays($teamDate, false);
                                @endphp

                                @if($diffDays > 0)
                                <div style="margin-top: 0.35rem; padding: 0.25rem 0.5rem; background: #dcfce7; color: #16a34a; border-radius: 4px; font-size: 0.75rem; font-weight: 600; display: inline-block;">
                                    ✓ مبكر {{ abs($diffDays) }}{{ abs($diffDays) == 1 ? ' يوم' : ' أيام' }}
                                </div>
                                @elseif($diffDays < 0)
                                    <div style="margin-top: 0.35rem; padding: 0.25rem 0.5rem; background: #fee2e2; color: #dc2626; border-radius: 4px; font-size: 0.75rem; font-weight: 600; display: inline-block;">
                                    ⚠ متأخر {{ abs($diffDays) }}{{ abs($diffDays) == 1 ? ' يوم' : ' أيام' }}
                            </div>
                            @else
                            <div style="margin-top: 0.35rem; padding: 0.25rem 0.5rem; background: #dbeafe; color: #1e40af; border-radius: 4px; font-size: 0.75rem; font-weight: 600; display: inline-block;">
                                ✓ في الموعد
                            </div>
                            @endif
                            @endif
        </div>
        @else
        <span style="color: #9ca3af; font-size: 0.85rem;">لم يتم التسليم</span>
        @endif
        </td>
        <td>
            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                <a href="{{ route('projects.internal-delivery.change-status', $project) }}" class="services-btn">
                    تسليم داخلي
                </a>
            </div>
        </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" class="empty-state">
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const projectSearch = document.getElementById('project_search');
        const projectIdInput = document.getElementById('project_id');
        const datalist = document.getElementById('projects-datalist');
        let searchTimeout;

        // Handle project datalist selection
        if (projectSearch && projectIdInput && datalist) {
            projectSearch.addEventListener('input', function() {
                const value = this.value.trim();

                // Find matching option
                const options = datalist.querySelectorAll('option');
                let foundProjectId = null;

                options.forEach(function(option) {
                    if (option.value === value) {
                        foundProjectId = option.getAttribute('data-project-id');
                    }
                });

                if (foundProjectId) {
                    projectIdInput.value = foundProjectId;
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        document.getElementById('filterForm').submit();
                    }, 300);
                } else if (value === '') {
                    projectIdInput.value = '';
                }
            });

            projectSearch.addEventListener('change', function() {
                const value = this.value.trim();
                const options = datalist.querySelectorAll('option');

                options.forEach(function(option) {
                    if (option.value === value) {
                        const projectId = option.getAttribute('data-project-id');
                        if (projectId) {
                            projectIdInput.value = projectId;
                            document.getElementById('filterForm').submit();
                        }
                    }
                });
            });
        }

        // Keyboard shortcut for search (Ctrl/Cmd + K)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (projectSearch) {
                    projectSearch.focus();
                    projectSearch.select();
                }
            }
        });
    });
</script>
@endpush