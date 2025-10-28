@extends('layouts.app')

@section('title', 'تصنيفات المهارات')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/skills.css') }}">
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📚 تصنيفات المهارات</h1>
            <p>إدارة وتنظيم تصنيفات المهارات بسهولة</p>
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
                    <a href="{{ route('skill-categories.create') }}" class="search-btn" style="width: 100%; text-decoration: none; text-align: center;">
                        <i class="fas fa-plus-circle ml-1"></i>
                        إضافة تصنيف جديد
                    </a>
                </div>
                <div class="filter-group">
                    <a href="{{ route('skills.index') }}" class="clear-filters-btn" style="width: 100%; text-decoration: none; text-align: center; background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-star ml-1"></i>
                        المهارات
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Row -->
        @if($categories->count() > 0)
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $categories->count() }}</div>
                <div class="stat-label">إجمالي التصنيفات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $categories->sum('skills_count') }}</div>
                <div class="stat-label">إجمالي المهارات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $categories->where('skills_count', '>', 0)->count() }}</div>
                <div class="stat-label">التصنيفات المستخدمة</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">{{ $categories->where('skills_count', 0)->count() }}</div>
                <div class="stat-label">تصنيفات فارغة</div>
            </div>
        </div>
        @endif

        <!-- Categories Table -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>📋 قائمة التصنيفات</h2>
            </div>

            <table class="projects-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>التصنيف</th>
                        <th>عدد المهارات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $index => $category)
                    <tr class="project-row">
                        <td>
                            <div class="project-avatar">
                                <i class="fas fa-list-alt"></i>
                            </div>
                        </td>
                        <td>
                            <div class="project-info">
                                <div class="project-details" style="width: 100%;">
                                    <h4>{{ $category->name }}</h4>
                                    @if($category->description)
                                        <p>{{ Str::limit($category->description, 100) }}</p>
                                    @else
                                        <p style="color: #9ca3af; font-style: italic;">لا يوجد وصف متاح</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="text-align: center;">
                                @if($category->skills_count > 0)
                                    <span class="status-badge status-completed">
                                        <i class="fas fa-star ml-1"></i>
                                        {{ $category->skills_count }} مهارة
                                    </span>
                                @else
                                    <span class="status-badge status-cancelled">
                                        <i class="fas fa-inbox ml-1"></i>
                                        فارغ
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <a href="{{ route('skill-categories.show', $category) }}"
                                   class="services-btn"
                                   style="background: linear-gradient(135deg, #3b82f6, #2563eb);"
                                   title="عرض التفاصيل">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                                <a href="{{ route('skill-categories.edit', $category) }}"
                                   class="services-btn"
                                   style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);"
                                   title="تعديل">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                                <form action="{{ route('skill-categories.destroy', $category) }}"
                                      method="POST"
                                      class="d-inline"
                                      data-category-name="{{ $category->name }}"
                                      data-skills-count="{{ $category->skills_count }}">
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
                        <td colspan="4" class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>لا توجد تصنيفات مهارات</h4>
                            <p>لم يتم إضافة أي تصنيفات للمهارات حتى الآن. ابدأ بإضافة أول تصنيف!</p>
                            <a href="{{ route('skill-categories.create') }}" class="services-btn" style="margin-top: 1rem;">
                                <i class="fas fa-plus-circle ml-1"></i>
                                إضافة تصنيف جديد
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
        if (form.hasAttribute('data-category-name')) {
            e.preventDefault();

            const categoryName = form.dataset.categoryName;
            const skillsCount = parseInt(form.dataset.skillsCount);

            if (skillsCount > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'لا يمكن الحذف',
                    text: 'لا يمكن حذف تصنيف "' + categoryName + '" لأنه يحتوي على ' + skillsCount + ' مهارة. يجب حذف أو نقل جميع المهارات أولاً.',
                    confirmButtonColor: '#ef4444'
                });
                return false;
            }

            Swal.fire({
                title: 'هل أنت متأكد؟',
                html: 'هل تريد حذف تصنيف "<strong>' + categoryName + '</strong>"؟<br><br><span style="color: #dc2626;">تحذير: هذا الإجراء لا يمكن التراجع عنه!</span>',
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
