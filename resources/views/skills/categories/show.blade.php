@extends('layouts.app')

@section('title', 'تفاصيل تصنيف المهارات')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/skills.css') }}">
<style>
    .details-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .details-header {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: white;
        padding: 2rem;
        text-align: center;
    }

    .details-header h2 {
        margin: 0 0 0.5rem 0;
        font-size: 1.8rem;
        font-weight: 600;
    }

    .details-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 1rem;
    }

    .details-body {
        padding: 2rem;
    }

    .detail-row {
        padding: 1.5rem;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: flex-start;
        gap: 2rem;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        min-width: 180px;
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .detail-label i {
        color: #667eea;
        font-size: 1.1rem;
    }

    .detail-value {
        flex: 1;
        color: #1f2937;
    }

    .description-box {
        background: #f9fafb;
        border-right: 4px solid #667eea;
        padding: 1rem;
        border-radius: 8px;
        line-height: 1.6;
    }

    .no-description {
        color: #9ca3af;
        font-style: italic;
    }

    .actions-footer {
        background: #f9fafb;
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        border-top: 2px solid #e5e7eb;
    }

    .skills-table-section {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }

    .quick-action-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transition: all 0.3s;
        text-decoration: none;
        display: block;
    }

    .quick-action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .quick-action-icon {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .quick-action-title {
        color: #1f2937;
        font-weight: 600;
        margin: 0;
    }
</style>
@endpush

@section('content')
<div class="simple-container">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>📖 تفاصيل التصنيف</h1>
            <p>معلومات تفصيلية عن تصنيف المهارات</p>
        </div>

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

        <!-- Statistics Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">{{ $skillCategory->skills->count() }}</div>
                <div class="stat-label">عدد المهارات</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    {{ $skillCategory->created_at->diffInDays(now()) }}
                </div>
                <div class="stat-label">عمر التصنيف (أيام)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    @if($skillCategory->skills->count() > 0)
                        مستخدم
                    @else
                        فارغ
                    @endif
                </div>
                <div class="stat-label">الحالة</div>
            </div>
        </div>

        <!-- Details Container -->
        <div class="details-container">
            <div class="details-header">
                <h2>{{ $skillCategory->name }}</h2>
                <p>معلومات كاملة عن التصنيف</p>
            </div>

            <div class="details-body">
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-tag"></i>
                        اسم التصنيف:
                    </div>
                    <div class="detail-value">
                        <h4 style="margin: 0; color: #667eea;">{{ $skillCategory->name }}</h4>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-align-left"></i>
                        الوصف:
                    </div>
                    <div class="detail-value">
                        @if($skillCategory->description)
                            <div class="description-box">
                                {{ $skillCategory->description }}
                            </div>
                        @else
                            <span class="no-description">
                                <i class="fas fa-minus-circle ml-1"></i>
                                لا يوجد وصف متاح لهذا التصنيف
                            </span>
                        @endif
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-star"></i>
                        عدد المهارات:
                    </div>
                    <div class="detail-value">
                        @if($skillCategory->skills->count() > 0)
                            <span class="status-badge status-completed">
                                <i class="fas fa-trophy ml-1"></i>
                                {{ $skillCategory->skills->count() }} مهارة
                            </span>
                        @else
                            <span class="status-badge status-cancelled">
                                <i class="fas fa-inbox ml-1"></i>
                                لا توجد مهارات
                            </span>
                        @endif
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-toggle-on"></i>
                        حالة التصنيف:
                    </div>
                    <div class="detail-value">
                        @if($skillCategory->skills->count() > 0)
                            <span class="status-badge status-in-progress">
                                <i class="fas fa-check-circle ml-1"></i>
                                مستخدم
                            </span>
                        @else
                            <span class="status-badge status-new">
                                <i class="fas fa-times-circle ml-1"></i>
                                غير مستخدم
                            </span>
                            <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle ml-1"></i>
                                لم يتم إضافة أي مهارات لهذا التصنيف بعد
                            </small>
                        @endif
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-calendar-plus"></i>
                        تاريخ الإنشاء:
                    </div>
                    <div class="detail-value">
                        <span class="status-badge status-new">
                            <i class="fas fa-clock ml-1"></i>
                            {{ $skillCategory->created_at->format('d/m/Y') }} في {{ $skillCategory->created_at->format('H:i') }}
                        </span>
                        <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                            منذ {{ $skillCategory->created_at->diffForHumans() }}
                        </small>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-calendar-check"></i>
                        آخر تحديث:
                    </div>
                    <div class="detail-value">
                        <span class="status-badge status-in-progress">
                            <i class="fas fa-clock ml-1"></i>
                            {{ $skillCategory->updated_at->format('d/m/Y') }} في {{ $skillCategory->updated_at->format('H:i') }}
                        </span>
                        <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                            منذ {{ $skillCategory->updated_at->diffForHumans() }}
                        </small>
                    </div>
                </div>
            </div>

            <div class="actions-footer">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <form action="{{ route('skill-categories.destroy', $skillCategory) }}"
                          method="POST"
                          id="deleteForm"
                          data-category-name="{{ $skillCategory->name }}"
                          data-skills-count="{{ $skillCategory->skills->count() }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="services-btn" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <i class="fas fa-trash-alt ml-1"></i>
                            حذف التصنيف
                        </button>
                    </form>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="{{ route('skill-categories.edit', $skillCategory) }}" class="services-btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="fas fa-edit ml-1"></i>
                        تعديل التصنيف
                    </a>
                    <a href="{{ route('skills.create') }}?category_id={{ $skillCategory->id }}" class="services-btn" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <i class="fas fa-plus-circle ml-1"></i>
                        إضافة مهارة جديدة
                    </a>
                </div>
            </div>
        </div>

        <!-- Skills Table -->
        @if($skillCategory->skills->count() > 0)
        <div class="skills-table-section">
            <div class="table-header">
                <h2>⭐ المهارات في هذا التصنيف ({{ $skillCategory->skills->count() }})</h2>
            </div>

            <table class="projects-table">
                <thead>
                    <tr>
                        <th>المهارة</th>
                        <th>النقاط القصوى</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($skillCategory->skills as $skill)
                    <tr class="project-row">
                        <td>
                            <div class="project-info">
                                <div class="project-avatar">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="project-details">
                                    <h4>{{ $skill->name }}</h4>
                                    @if($skill->description)
                                        <p>{{ Str::limit($skill->description, 60) }}</p>
                                    @endif
                                </div>
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
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <a href="{{ route('skills.show', $skill) }}" class="services-btn" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                    <i class="fas fa-eye"></i>
                                    عرض
                                </a>
                                <a href="{{ route('skills.edit', $skill) }}" class="services-btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                    <i class="fas fa-edit"></i>
                                    تعديل
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="projects-table-container">
            <div class="table-header">
                <h2>⚡ إجراءات سريعة</h2>
            </div>
            <div style="padding: 2rem;">
                <div class="quick-actions-grid">
                    <a href="{{ route('skill-categories.edit', $skillCategory) }}" class="quick-action-card">
                        <div class="quick-action-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white;">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h4 class="quick-action-title">تعديل التصنيف</h4>
                    </a>

                    <a href="{{ route('skills.create') }}?category_id={{ $skillCategory->id }}" class="quick-action-card">
                        <div class="quick-action-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <h4 class="quick-action-title">إضافة مهارة جديدة</h4>
                    </a>

                    <a href="{{ route('skills.index') }}" class="quick-action-card">
                        <div class="quick-action-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                            <i class="fas fa-list"></i>
                        </div>
                        <h4 class="quick-action-title">عرض جميع المهارات</h4>
                    </a>

                    <a href="{{ route('skill-categories.index') }}" class="quick-action-card">
                        <div class="quick-action-icon" style="background: linear-gradient(135deg, #6b7280, #4b5563); color: white;">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <h4 class="quick-action-title">العودة للتصنيفات</h4>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('deleteForm');

    deleteForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const categoryName = deleteForm.dataset.categoryName;
        const skillsCount = parseInt(deleteForm.dataset.skillsCount);

        if (skillsCount > 0) {
            Swal.fire({
                icon: 'error',
                title: 'لا يمكن الحذف',
                html: 'لا يمكن حذف تصنيف "<strong>' + categoryName + '</strong>" لأنه يحتوي على <strong>' + skillsCount + '</strong> مهارة.<br><br>يجب حذف أو نقل جميع المهارات أولاً.',
                confirmButtonColor: '#ef4444'
            });
            return false;
        }

        Swal.fire({
            title: 'هل أنت متأكد؟',
            html: 'هل تريد حذف تصنيف "<strong>' + categoryName + '</strong>"؟<br><br>' +
                  '<span style="color: #dc2626;">تحذير: هذا الإجراء لا يمكن التراجع عنه!</span><br><br>' +
                  'سيتم حذف:<br>' +
                  '• بيانات التصنيف<br>' +
                  '• جميع المراجع المرتبطة به',
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
                deleteForm.submit();
            }
        });
    });
});
</script>
@endpush
