@extends('layouts.app')

@section('title', 'أرشيف المشاريع')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/projects-archive.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📦 أرشيف المشاريع</h1>
            <p>عرض شامل لجميع المشاريع مع بياناتها والحقول المخصصة</p>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" action="{{ route('projects.archive') }}">
                <div class="filters-row">
                    <!-- Search Filter -->
                    <div class="filter-group">
                        <label for="search" class="filter-label">
                            <i class="fas fa-search"></i>
                            البحث
                        </label>
                        <input type="text"
                               id="search"
                               name="search"
                               class="filter-select"
                               placeholder="ابحث في اسم المشروع، الكود، الوصف..."
                               value="{{ request('search') }}">
                    </div>

                    <!-- Custom Fields Filters -->
                    @foreach($customFields as $field)
                        @if(isset($customFieldsOptions[$field->id]) && $customFieldsOptions[$field->id]->count() > 0)
                        <div class="filter-group">
                            <label for="custom_field_{{ $field->id }}" class="filter-label">
                                <i class="fas fa-filter"></i>
                                {{ $field->name }}
                            </label>
                            @if($field->field_type == 'date')
                                <input type="date"
                                       id="custom_field_{{ $field->id }}"
                                       name="custom_field_{{ $field->id }}"
                                       class="filter-select"
                                       value="{{ request('custom_field_' . $field->id) }}">
                            @else
                                <select id="custom_field_{{ $field->id }}"
                                        name="custom_field_{{ $field->id }}"
                                        class="filter-select">
                                    <option value="">الكل</option>
                                    @foreach($customFieldsOptions[$field->id] as $option)
                                        <option value="{{ $option }}"
                                                {{ request('custom_field_' . $field->id) == $option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        @endif
                    @endforeach

                    <!-- Submit Button -->
                    <div class="filter-group">
                        <button type="submit" class="clear-filters-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-filter"></i>
                            فلتر
                        </button>
                    </div>

                    <!-- Clear Filters -->
                    @php
                        $hasFilters = request('search');
                        foreach($customFields as $field) {
                            if(request('custom_field_' . $field->id)) {
                                $hasFilters = true;
                                break;
                            }
                        }
                    @endphp
                    @if($hasFilters)
                    <div class="filter-group">
                        <a href="{{ route('projects.archive') }}" class="clear-filters-btn">
                            <i class="fas fa-times"></i>
                            مسح الفلاتر
                        </a>
                    </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Statistics Row -->
        @php
            $totalProjects = $projects->total();
        @endphp
        <div class="stats-row" style="grid-template-columns: 1fr; max-width: 400px; margin: 0 auto 1.5rem;">
            <div class="stat-card">
                <div class="stat-number">{{ $totalProjects }}</div>
                <div class="stat-label">إجمالي المشاريع المعروضة</div>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>📋 قائمة المشاريع</h2>
            </div>

            <div class="table-responsive archive-table-wrapper">
                <table class="projects-table archive-table">
                    <thead>
                        <tr>
                            <th class="sticky-col">المشروع</th>
                            <th>الكود</th>
                            @foreach($customFields as $customField)
                                <th>{{ $customField->name }}</th>
                            @endforeach
                            <th class="sticky-col sticky-right">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($projects as $project)
                        <tr class="project-row">
                            <td class="sticky-col">
                                <div class="project-name-simple">
                                    @if($project->is_urgent)
                                        <span class="urgent-indicator">🚨 مستعجل</span>
                                    @endif
                                    {{ $project->name }}
                                </div>
                            </td>
                            <td>
                                @if($project->code)
                                    <span class="project-code">{{ $project->code }}</span>
                                @else
                                    <span class="text-muted">غير محدد</span>
                                @endif
                            </td>
                            @php
                                $customFieldsData = $project->getCustomFieldsWithInfo();
                                $customFieldsMap = $customFieldsData->keyBy(function($item) {
                                    return $item['field']->id;
                                });
                            @endphp
                            @foreach($customFields as $customField)
                                <td>
                                    @php
                                        $fieldData = $customFieldsMap->get($customField->id);
                                        $value = $fieldData['value'] ?? null;
                                    @endphp
                                    @if($value !== null && $value !== '')
                                        @switch($customField->field_type)
                                            @case('date')
                                                <div style="color: #6b7280; font-size: 0.9rem;">
                                                    <i class="fas fa-calendar-alt" style="color: #667eea; margin-left: 0.25rem;"></i>
                                                    {{ \Carbon\Carbon::parse($value)->format('Y/m/d') }}
                                                </div>
                                                @break
                                            @case('number')
                                                <span style="font-weight: 600; color: #374151;">{{ number_format($value) }}</span>
                                                @break
                                            @case('textarea')
                                                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; color: #374151;" title="{{ $value }}">
                                                    {{ Str::limit($value, 30) }}
                                                </div>
                                                @break
                                            @default
                                                <span style="color: #374151;">{{ Str::limit($value, 30) }}</span>
                                        @endswitch
                                    @else
                                        <span class="text-muted" style="font-size: 0.85rem;">غير محدد</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="sticky-col sticky-right">
                                <a href="{{ route('projects.show', $project) }}"
                                   class="services-btn"
                                   title="عرض التفاصيل"
                                   style="text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ 2 + $customFields->count() + 1 }}" class="empty-state">
                                <i class="fas fa-folder-open"></i>
                                <h4>لا توجد مشاريع</h4>
                                <p>لم يتم العثور على أي مشاريع تطابق معايير البحث المحددة.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($projects->hasPages())
            <div class="pagination">
                {{ $projects->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

