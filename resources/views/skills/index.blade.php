@extends('layouts.app')

@section('title', 'إدارة المهارات')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/skills.css') }}">
<style>
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>⭐ إدارة المهارات</h1>
            <p>إدارة وتنظيم جميع المهارات وتقييماتها</p>
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

        <!-- Quick Actions Section -->
        <div class="filters-section">
            <div class="filters-row">
                <div class="filter-group">
                    <a href="{{ route('skills.create') }}" class="search-btn" style="width: 100%; text-decoration: none; text-align: center;">
                        <i class="fas fa-plus-circle ml-1"></i>
                        إضافة مهارة جديدة
                    </a>
                </div>
                <div class="filter-group">
                    <a href="{{ route('skill-categories.index') }}" class="clear-filters-btn" style="width: 100%; text-decoration: none; text-align: center; background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-list-alt ml-1"></i>
                        تصنيفات المهارات
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Row -->
        @if($skills->count() > 0)
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $skills->count() }}</div>
                <div class="stat-label">إجمالي المهارات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $skills->where('is_active', true)->count() }}</div>
                <div class="stat-label">المهارات النشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $skills->where('is_active', false)->count() }}</div>
                <div class="stat-label">غير النشطة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $skills->sum('max_points') }}</div>
                <div class="stat-label">إجمالي النقاط</div>
            </div>
        </div>
        @endif

        <!-- Skills Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>📋 قائمة المهارات</h2>
            </div>

            <table class="projects-table">
                <thead>
                    <tr>
                        <th>المهارة</th>
                        <th>التصنيف</th>
                        <th>النقاط</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skills as $skill)
                    <tr class="project-row">
                        <td>
                            <div class="project-info">
                                <div class="project-avatar">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="project-details" style="width: 100%;">
                                    <h4>{{ $skill->name }}</h4>
                                    @if($skill->description)
                                        <p>{{ Str::limit($skill->description, 100) }}</p>
                                    @else
                                        <p style="color: #9ca3af; font-style: italic;">لا يوجد وصف متاح</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                <span class="status-badge status-new">
                                    <i class="fas fa-tag ml-1"></i>
                                    {{ optional($skill->category)->name ?? 'بدون تصنيف' }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                <span class="status-badge status-in-progress">
                                    <i class="fas fa-trophy ml-1"></i>
                                    {{ $skill->max_points }} نقطة
                                </span>
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                @if($skill->is_active)
                                    <span class="status-badge status-completed">
                                        <i class="fas fa-check-circle ml-1"></i>
                                        نشط
                                    </span>
                                @else
                                    <span class="status-badge status-cancelled">
                                        <i class="fas fa-times-circle ml-1"></i>
                                        غير نشط
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <a href="{{ route('skills.show', $skill) }}"
                                   class="services-btn"
                                   style="background: linear-gradient(135deg, #3b82f6, #2563eb);"
                                   title="عرض التفاصيل">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                                <a href="{{ route('skills.edit', $skill) }}"
                                   class="services-btn"
                                   style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"
                                   title="تعديل">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                                <form action="{{ route('skills.destroy', $skill) }}"
                                      method="POST"
                                      class="d-inline"
                                      data-skill-name="{{ $skill->name }}"
                                      data-skill-active="{{ $skill->is_active ? 'true' : 'false' }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="services-btn"
                                            style="background: linear-gradient(135deg, #ef4444, #dc2626);"
                                            title="حذف">
                                        <i class="fas fa-trash"></i>
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="fas fa-star"></i>
                            <h4>لا توجد مهارات</h4>
                            <p>لم يتم إضافة أي مهارات حتى الآن. ابدأ بإضافة أول مهارة!</p>
                            <a href="{{ route('skills.create') }}" class="services-btn" style="margin-top: 1rem;">
                                <i class="fas fa-plus-circle ml-1"></i>
                                إضافة مهارة جديدة
                            </a>
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
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete forms
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (form.hasAttribute('data-skill-name')) {
            e.preventDefault();

            const skillName = form.dataset.skillName;
            const isActive = form.dataset.skillActive === 'true';

            let message = 'هل تريد حذف مهارة "<strong>' + skillName + '</strong>"؟<br><br>' +
                         '<span style="color: #dc2626;">تحذير: هذا الإجراء لا يمكن التراجع عنه!</span><br><br>' +
                         'سيتم حذف:<br>' +
                         '• بيانات المهارة<br>' +
                         '• جميع التقييمات المرتبطة بها<br>' +
                         '• النقاط المحسوبة للموظفين';

            if (isActive) {
                message += '<br><br><span style="color: #f59e0b;">ملاحظة: هذه المهارة نشطة حالياً وقد تؤثر على التقييمات الجارية.</span>';
            }

            Swal.fire({
                title: 'هل أنت متأكد؟',
                html: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء',
                input: 'text',
                inputPlaceholder: 'اكتب "حذف" للتأكيد',
                inputValidator: (value) => {
                    if (value !== 'حذف') {
                        return 'يجب كتابة "حذف" للتأكيد'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
    });
});
</script>
@endpush
